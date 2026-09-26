#!/usr/bin/env python3
"""Fail PHPCS only for violations on added or changed PHP lines."""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
from pathlib import Path


DIFF_FILE = re.compile(r"^diff --git a/.* b/(.*)$")
HUNK = re.compile(r"^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@")


def changed_lines(base: str, root: Path) -> dict[str, list[tuple[int, int]]]:
    """Return added line ranges by repository-relative PHP path."""
    result = subprocess.run(
        ["git", "diff", "--no-ext-diff", "--unified=0", f"{base}...HEAD", "--", "*.php"],
        cwd=root,
        check=True,
        capture_output=True,
        text=True,
    )

    files: dict[str, list[tuple[int, int]]] = {}
    current_file: str | None = None
    for line in result.stdout.splitlines():
        file_match = DIFF_FILE.match(line)
        if file_match:
            current_file = file_match.group(1)
            continue

        hunk_match = HUNK.match(line)
        if hunk_match and current_file:
            start = int(hunk_match.group(1))
            count = int(hunk_match.group(2) or "1")
            if count:
                files.setdefault(current_file, []).append((start, start + count - 1))

    return files


def normalized_path(path: str, root: Path) -> str:
    """Normalize a PHPCS report path to a repository-relative path."""
    candidate = Path(path)
    if not candidate.is_absolute():
        candidate = root / candidate
    return candidate.resolve().relative_to(root.resolve()).as_posix()


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
        ranges = changed.get(relative_path, [])
        for item in file_data.get("messages", []):
            total_messages += 1
            line = int(item["line"])
            if any(start <= line <= end for start, end in ranges):
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
        f"{ignored} existing violation(s) on unchanged lines were not blocking."
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
