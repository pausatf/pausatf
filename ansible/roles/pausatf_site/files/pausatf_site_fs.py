#!/usr/bin/env python3
"""Install a pausatf-site release and retire superseded plugin entries.

wp-content and its plugin directories are writable by the web server, so
every operation here works through directory file descriptors opened without
following links, stages content in a root-only directory on the same
filesystem, and replaces entries with a single rename. Nothing is changed in
place under a path the web server could redirect.

Prints one JSON object. Exit status 0 means success (see "changed"), 2 means
the request was refused and nothing the role did not create was changed.
"""

import argparse
import ctypes
import json
import os
import pwd
import re
import secrets
import shutil
import stat
import subprocess
import sys
import time

MARKER = ".pausatf-site-release"
SHA_RE = re.compile(r"[0-9a-f]{40}")
RENAME_NOREPLACE = 1
RENAME_EXCHANGE = 2
DIR_FLAGS = os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW | os.O_CLOEXEC

_libc = ctypes.CDLL(None, use_errno=True)
_libc.renameat2.argtypes = [
    ctypes.c_int,
    ctypes.c_char_p,
    ctypes.c_int,
    ctypes.c_char_p,
    ctypes.c_uint,
]


class Refused(Exception):
    """The filesystem is not in a state this tool may change."""


def renameat2(src_fd, src, dst_fd, dst, flags):
    if _libc.renameat2(src_fd, os.fsencode(src), dst_fd, os.fsencode(dst), flags) != 0:
        err = ctypes.get_errno()
        raise OSError(err, os.strerror(err), dst)


def lstat_at(dir_fd, name):
    try:
        return os.stat(name, dir_fd=dir_fd, follow_symlinks=False)
    except FileNotFoundError:
        return None


def open_dir_at(dir_fd, name):
    return os.open(name, DIR_FLAGS, dir_fd=dir_fd)


def open_content_dir(docroot, kind):
    """Return descriptors for wp-content and wp-content/<kind>, refusing links."""
    # The docroot path itself is operator configuration and may be a link;
    # everything below it is opened without following links.
    root_fd = os.open(docroot, os.O_RDONLY | os.O_DIRECTORY | os.O_CLOEXEC)
    try:
        content_fd = open_dir_at(root_fd, "wp-content")
    except OSError as error:
        raise Refused(
            f"{docroot}/wp-content is not a plain directory: {error.strerror}"
        ) from error
    finally:
        os.close(root_fd)
    try:
        kind_fd = open_dir_at(content_fd, kind)
    except OSError as error:
        os.close(content_fd)
        raise Refused(
            f"{docroot}/wp-content/{kind} is not a plain directory: {error.strerror}"
        ) from error
    if os.fstat(kind_fd).st_dev != os.fstat(content_fd).st_dev:
        raise Refused(f"{docroot}/wp-content/{kind} is on another filesystem")
    return content_fd, kind_fd


def make_stage(content_fd):
    """Create a root-only working directory beside the content directories."""
    name = f".pausatf-site-stage-{secrets.token_hex(8)}"
    os.mkdir(name, 0o700, dir_fd=content_fd)
    fd = open_dir_at(content_fd, name)
    info = os.fstat(fd)
    if info.st_uid != 0 or stat.S_IMODE(info.st_mode) != 0o700:
        os.close(fd)
        raise Refused("the staging directory was replaced while it was created")
    return name, fd


def remove_stage(content_fd, name, fd):
    """Remove the staging directory only if it is still ours and empty."""
    info = os.fstat(fd)
    os.close(fd)
    current = lstat_at(content_fd, name)
    if current and (current.st_dev, current.st_ino) == (info.st_dev, info.st_ino):
        try:
            os.rmdir(name, dir_fd=content_fd)
        except OSError:
            pass


def read_marker(dir_fd):
    """Return the SHA recorded in a release directory, or None."""
    try:
        fd = os.open(MARKER, os.O_RDONLY | os.O_NOFOLLOW | os.O_CLOEXEC, dir_fd=dir_fd)
    except OSError:
        return None
    try:
        info = os.fstat(fd)
        if not stat.S_ISREG(info.st_mode) or info.st_uid != 0 or info.st_nlink != 1:
            return None
        value = os.read(fd, 64).decode("ascii", "replace").strip()
    finally:
        os.close(fd)
    return value if SHA_RE.fullmatch(value) else None


def verify_release(plugins_fd, slug):
    """Return (lstat, sha) for an existing release this tool installed."""
    info = lstat_at(plugins_fd, slug)
    if info is None:
        return None, None
    if not stat.S_ISDIR(info.st_mode) or info.st_uid != 0:
        raise Refused(f"{slug} is not a root-owned directory installed by this role")
    fd = open_dir_at(plugins_fd, slug)
    try:
        opened = os.fstat(fd)
        if (opened.st_dev, opened.st_ino) != (info.st_dev, info.st_ino):
            raise Refused(f"{slug} changed while it was inspected")
        sha = read_marker(fd)
    finally:
        os.close(fd)
    if sha is None:
        raise Refused(f"{slug} is not a release installed by this role")
    return info, sha


def child_in(fd):
    def enter():
        os.umask(0o022)
        os.fchdir(fd)

    return enter


def release(args):
    if not SHA_RE.fullmatch(args.sha):
        raise Refused("the release version must be a full commit SHA")
    git_info = os.stat(args.gitdir, follow_symlinks=False)
    if not stat.S_ISDIR(git_info.st_mode) or git_info.st_uid != 0:
        raise Refused(f"{args.gitdir} is not a root-owned directory")
    content_fd, plugins_fd = open_content_dir(args.docroot, "plugins")
    existing, current = verify_release(plugins_fd, args.slug)
    if current == args.sha:
        return {"changed": False, "sha": args.sha}
    if args.check:
        return {"changed": True, "sha": args.sha, "previous": current}
    subprocess.run(
        ["git", f"--git-dir={args.gitdir}", "cat-file", "-e", f"{args.sha}^{{commit}}"],
        check=True,
    )

    stage_name, stage_fd = make_stage(content_fd)
    keep_stage = False
    try:
        os.mkdir(args.slug, 0o755, dir_fd=stage_fd)
        new_fd = open_dir_at(stage_fd, args.slug)
        try:
            archive = subprocess.Popen(
                [
                    "git",
                    f"--git-dir={args.gitdir}",
                    "archive",
                    "--format=tar",
                    args.sha,
                ],
                stdout=subprocess.PIPE,
            )
            extract = subprocess.run(
                ["tar", "-x", "--no-same-owner", "--no-same-permissions", "-f", "-"],
                stdin=archive.stdout,
                preexec_fn=child_in(new_fd),
                check=False,
            )
            archive.stdout.close()
            if archive.wait() != 0 or extract.returncode != 0:
                raise Refused("extracting the release failed")
            marker = os.open(
                MARKER,
                os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW | os.O_CLOEXEC,
                0o644,
                dir_fd=new_fd,
            )
            os.write(marker, (args.sha + "\n").encode("ascii"))
            os.fsync(marker)
            os.close(marker)
            os.fchmod(new_fd, 0o755)
        finally:
            os.close(new_fd)

        # Re-check the destination immediately before the swap.
        again = lstat_at(plugins_fd, args.slug)
        if existing is None:
            if again is not None:
                raise Refused(f"{args.slug} appeared during the release")
            renameat2(stage_fd, args.slug, plugins_fd, args.slug, RENAME_NOREPLACE)
        else:
            if again is None or (again.st_dev, again.st_ino) != (
                existing.st_dev,
                existing.st_ino,
            ):
                raise Refused(f"{args.slug} changed during the release")
            renameat2(stage_fd, args.slug, plugins_fd, args.slug, RENAME_EXCHANGE)
            old = lstat_at(stage_fd, args.slug)
            if old is None or (old.st_dev, old.st_ino) != (
                existing.st_dev,
                existing.st_ino,
            ):
                # Something other than the verified release was swapped out; keep it for inspection.
                keep_stage = True
                raise Refused(
                    f"an unexpected entry was replaced; it is kept in wp-content/{stage_name}"
                )
    finally:
        # Whatever remains in the stage is this run's extraction or the verified previous release.
        if not keep_stage:
            if lstat_at(stage_fd, args.slug) is not None:
                shutil.rmtree(args.slug, dir_fd=stage_fd)
            remove_stage(content_fd, stage_name, stage_fd)
        else:
            os.close(stage_fd)
        os.close(plugins_fd)
        os.close(content_fd)
    return {"changed": True, "sha": args.sha, "previous": current}


def tree_problems(dir_fd, name, owners, expect_dir):
    """List reasons the isolated entry may not be backed up and removed."""
    top = lstat_at(dir_fd, name)
    problems = []
    if top is None:
        return ["the entry disappeared"]
    if expect_dir and not stat.S_ISDIR(top.st_mode):
        return ["not a plain directory"]
    if not expect_dir and not stat.S_ISREG(top.st_mode):
        return ["not a regular file"]

    def check(info, label):
        if stat.S_ISLNK(info.st_mode):
            problems.append(f"{label} is a link")
        elif not (stat.S_ISDIR(info.st_mode) or stat.S_ISREG(info.st_mode)):
            problems.append(f"{label} is not a file or directory")
        elif info.st_uid not in owners:
            problems.append(f"{label} has an unexpected owner")
        elif stat.S_ISREG(info.st_mode) and info.st_nlink != 1:
            problems.append(f"{label} has other hard links")

    check(top, name)
    if expect_dir:
        for root, dirs, files, root_fd in os.fwalk(
            name, dir_fd=dir_fd, follow_symlinks=False
        ):
            for entry in dirs + files:
                check(
                    os.stat(entry, dir_fd=root_fd, follow_symlinks=False),
                    os.path.join(root, entry),
                )
    return problems


def retire(args):
    owners = {
        int(user) if user.isdigit() else pwd.getpwnam(user).pw_uid
        for user in args.owners.split(",")
    }
    expect_dir = args.kind == "plugins"
    backup = os.stat(args.backup_dir, follow_symlinks=False)
    if (
        not stat.S_ISDIR(backup.st_mode)
        or backup.st_uid != 0
        or stat.S_IMODE(backup.st_mode) & 0o077
    ):
        raise Refused(f"{args.backup_dir} is not a root-only directory")
    content_fd, kind_fd = open_content_dir(args.docroot, args.kind)
    try:
        found = lstat_at(kind_fd, args.name)
        if found is None:
            return {"changed": False}
        if stat.S_ISLNK(found.st_mode):
            raise Refused(
                f"wp-content/{args.kind}/{args.name} is a link; remove it by hand after inspection"
            )
        if args.check:
            return {"changed": True}

        stage_name, stage_fd = make_stage(content_fd)
        try:
            # Isolate the entry first, so the web server can no longer change it.
            renameat2(kind_fd, args.name, stage_fd, args.name, RENAME_NOREPLACE)
            problems = tree_problems(stage_fd, args.name, owners, expect_dir)
            if problems:
                try:
                    renameat2(stage_fd, args.name, kind_fd, args.name, RENAME_NOREPLACE)
                    where = "it was left in place"
                except OSError:
                    where = f"it is kept in wp-content/{stage_name}"
                raise Refused(
                    f"wp-content/{args.kind}/{args.name} was not retired ({'; '.join(problems[:5])}); "
                    f"{where}"
                )
            target = os.path.join(
                args.backup_dir,
                f"{args.kind}-{args.name}-{time.strftime('%Y%m%dT%H%M%S')}.tar",
            )
            subprocess.run(
                [
                    "tar",
                    "--create",
                    "--numeric-owner",
                    f"--file={target}",
                    "--",
                    args.name,
                ],
                preexec_fn=child_in(stage_fd),
                check=True,
            )
            if os.stat(target).st_size == 0:
                raise Refused("the backup archive is empty")
            if expect_dir:
                shutil.rmtree(args.name, dir_fd=stage_fd)
            else:
                os.unlink(args.name, dir_fd=stage_fd)
        finally:
            remove_stage(content_fd, stage_name, stage_fd)
    finally:
        os.close(kind_fd)
        os.close(content_fd)
    return {"changed": True, "backup": target}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest="command", required=True)
    rel = sub.add_parser("release")
    rel.add_argument("--docroot", required=True)
    rel.add_argument("--slug", required=True)
    rel.add_argument("--gitdir", required=True)
    rel.add_argument("--sha", required=True)
    rel.add_argument("--check", action="store_true")
    ret = sub.add_parser("retire")
    ret.add_argument("--docroot", required=True)
    ret.add_argument("--kind", choices=["plugins", "mu-plugins"], required=True)
    ret.add_argument("--name", required=True)
    ret.add_argument(
        "--owners",
        required=True,
        help="comma-separated user names or IDs allowed to own retired entries",
    )
    ret.add_argument("--backup-dir", required=True)
    ret.add_argument("--check", action="store_true")
    args = parser.parse_args()
    for value in (getattr(args, "slug", None), getattr(args, "name", None)):
        if value is not None and (not value or "/" in value or value in (".", "..")):
            parser.error("names must be single path components")
    try:
        result = release(args) if args.command == "release" else retire(args)
    except Refused as error:
        print(json.dumps({"changed": False, "refused": str(error)}))
        return 2
    print(json.dumps(result))
    return 0


if __name__ == "__main__":
    sys.exit(main())
