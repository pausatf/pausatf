#!/usr/bin/env python3
"""Fail PHPCS when changed PHP files introduce new violations."""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
from pathlib import Path


DIFF_FILE = re.compile(r"^diff --git a/.* b/(.*)$")
HUNK = re.compile(r"^@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@")


def changed_lines(base: str, root: Path) -> dict[str, list[tuple[int, int, int, int]]]:
    """Return old/new line ranges for changed PHP lines by repository-relative path."""
    result = subprocess.run(
        ["git", "diff", "--no-ext-diff", "--unified=0", f"{base}...HEAD", "--", "*.php"],
        cwd=root,
        check=True,
        capture_output=True,
        text=True,
    )

    files: dict[str, list[tuple[int, int, int, int]]] = {}
    current_file: str | None = None
    for line in result.stdout.splitlines():
        file_match = DIFF_FILE.match(line)
        if file_match:
            current_file = file_match.group(1)
            continue

        hunk_match = HUNK.match(line)
        if hunk_match and current_file:
            if current_file.startswith("content/calendar/"):
                continue
            old_start = int(hunk_match.group(1))
            old_count = int(hunk_match.group(2) or "1")
            new_start = int(hunk_match.group(3))
            new_count = int(hunk_match.group(4) or "1")
            if new_count:
                files.setdefault(current_file, []).append(
                    (old_start, old_count, new_start, new_count)
                )

    return files


def normalized_path(path: str, root: Path) -> str:
    """Normalize a PHPCS report path to a repository-relative path."""
    candidate = Path(path)
    if not candidate.is_absolute():
        candidate = root / candidate
    return candidate.resolve().relative_to(root.resolve()).as_posix()


def base_line_for_changed_line(
    line: int, hunks: list[tuple[int, int, int, int]]
) -> int | None:
    """Map a changed line to its replaced base line, if the hunk has one."""
    for old_start, old_count, new_start, new_count in hunks:
        if new_start <= line < new_start + new_count:
            offset = line - new_start
            if old_count == 0:
                return None
            # A replacement can expand one old line into several new lines,
            # as happens when a PHPDoc block is added after ``<?php``. Map
            # every expanded line to the replaced base line so existing
            # findings on the original code remain non-blocking.
            return old_start + min(offset, old_count - 1)
    return None


def baseline_violations(
    base: str, paths: list[str], phpcs: str, root: Path
) -> dict[str, dict[tuple[int, str, str], int]]:
    """Index base violations by line, source, and message for each changed file."""
    result: dict[str, dict[tuple[int, str, str], int]] = {}
    for path in paths:
        source = subprocess.run(
            ["git", "show", f"{base}:{path}"],
            cwd=root,
            capture_output=True,
            text=True,
            check=False,
        )
        if source.returncode:
            continue

        report = subprocess.run(
            [
                phpcs,
                "-q",
                f"--stdin-path={path}",
                "--standard=phpcs.xml.dist",
                "--report=json",
                "-",
            ],
            cwd=root,
            input=source.stdout,
            capture_output=True,
            text=True,
            check=False,
        )
        try:
            data = json.loads(report.stdout)
        except json.JSONDecodeError:
            continue

        counts: dict[tuple[int, str, str], int] = {}
        for file_data in data.get("files", {}).values():
            for item in file_data.get("messages", []):
                finding = (
                    int(item["line"]),
                    item.get("source", ""),
                    item.get("message", ""),
                )
                counts[finding] = counts.get(finding, 0) + 1
        result[path] = counts
    return result


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base", required=True, help="Base commit or ref to compare against")
    parser.add_argument("--phpcs", required=True, help="Path to the PHPCS executable")
    args = parser.parse_args()

    root = Path(__file__).resolve().parents[1]
    changed = changed_lines(args.base, root)
    if not changed:
        print("No PHP lines changed; PHPCS check skipped.")
        return 0

    paths = sorted(changed)
    existing = baseline_violations(args.base, paths, args.phpcs, root)
    report = subprocess.run(
        [args.phpcs, "-q", "--standard=phpcs.xml.dist", "--report=json", *paths],
        cwd=root,
        capture_output=True,
        text=True,
        check=False,
    )
    try:
        phpcs_report = json.loads(report.stdout)
    except json.JSONDecodeError:
        print(report.stdout, file=sys.stderr)
        print(report.stderr, file=sys.stderr)
        print("PHPCS did not return a valid JSON report.", file=sys.stderr)
        return report.returncode or 1

    violations: list[tuple[str, int, str, str, str]] = []
    ignored = 0
    total_messages = 0
    for reported_path, file_data in phpcs_report.get("files", {}).items():
        relative_path = normalized_path(reported_path, root)
        hunks = changed.get(relative_path, [])
        for item in file_data.get("messages", []):
            total_messages += 1
            line = int(item["line"])
            base_line = base_line_for_changed_line(line, hunks)
            if any(new_start <= line < new_start + new_count for _, _, new_start, new_count in hunks):
                finding = (
                    base_line if base_line is not None else -1,
                    item.get("source", ""),
                    item.get("message", ""),
                )
                previous = existing.get(relative_path, {}).get(finding, 0)
                if previous:
                    existing[relative_path][finding] -= 1
                    ignored += 1
                    continue
                violations.append(
                    (
                        relative_path,
                        line,
                        item.get("type", ""),
                        item.get("source", ""),
                        item.get("message", ""),
                    )
                )
            else:
                ignored += 1

    if violations:
        for path, line, kind, source, message in violations:
            print(f"{path}:{line}: {kind} [{source}] {message}")
        print(f"Found {len(violations)} PHPCS violation(s) on changed lines.", file=sys.stderr)
        return 1

    if report.returncode > 3 or (report.returncode and total_messages == 0):
        if report.stderr:
            print(report.stderr, file=sys.stderr)
        print(
            f"PHPCS exited with status {report.returncode} without reporting violations.",
            file=sys.stderr,
        )
        return report.returncode

    print(
        f"PHPCS passed on {len(paths)} changed PHP file(s); "
        f"{ignored} unchanged or location-matched baseline violation(s) were not blocking."
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
