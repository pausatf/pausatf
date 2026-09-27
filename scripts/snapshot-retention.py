#!/usr/bin/env python3
"""Select old snapshots only after the replacement is visible in the API."""

import argparse
import json
import re
import sys


def candidates(snapshots, droplet_id, completed_name, keep=7, name_prefix="prod-nightly"):
    if keep < 1 or not str(droplet_id).isdigit():
        raise ValueError("A numeric droplet ID and positive retention are required")
    if not re.fullmatch(r"[a-z][a-z0-9-]*", name_prefix):
        raise ValueError("A lowercase snapshot name prefix is required")
    if not isinstance(snapshots, list) or any(not isinstance(s, dict) for s in snapshots):
        raise ValueError("Expected a JSON list of snapshot objects")
    name_pattern = re.compile(rf"{re.escape(name_prefix)}-\d{{8}}-\d{{6}}")
    owned = [
        s for s in snapshots
        if s.get("resource_type") == "droplet"
        and str(s.get("resource_id")) == str(droplet_id)
        and name_pattern.fullmatch(s.get("name", ""))
    ]
    if not any(s["name"] == completed_name for s in owned):
        raise ValueError("Completed replacement snapshot is not visible; refusing to prune")
    ordered = sorted(owned, key=lambda s: s["created_at"], reverse=True)
    return [str(s["id"]) for s in ordered[keep:] if s["name"] != completed_name]


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--droplet-id", required=True)
    parser.add_argument("--completed-name", required=True)
    parser.add_argument("--keep", type=int, default=7)
    parser.add_argument("--name-prefix", default="prod-nightly")
    args = parser.parse_args()
    try:
        selected = candidates(
            json.load(sys.stdin),
            args.droplet_id,
            args.completed_name,
            args.keep,
            args.name_prefix,
        )
    except (ValueError, KeyError, TypeError) as exc:
        parser.exit(1, f"Snapshot retention failed closed: {exc}\n")
    if selected:
        print("\n".join(selected))
