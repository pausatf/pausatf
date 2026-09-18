#!/usr/bin/env python3
"""Select old snapshots only after the replacement is visible in the API."""

import argparse
import json
import re
import sys


def candidates(snapshots, droplet_id, completed_name, keep=7):
    if keep < 1 or not str(droplet_id).isdigit():
        raise ValueError("A numeric droplet ID and positive retention are required")
    owned = [
        s for s in snapshots
        if s.get("resource_type") == "droplet"
        and str(s.get("resource_id")) == str(droplet_id)
        and re.fullmatch(r"prod-nightly-\d{8}-\d{6}", s.get("name", ""))
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
    args = parser.parse_args()
    try:
        selected = candidates(json.load(sys.stdin), args.droplet_id, args.completed_name, args.keep)
    except (ValueError, KeyError, TypeError) as exc:
        parser.exit(1, f"Snapshot retention failed closed: {exc}\n")
    if selected:
        print("\n".join(selected))
