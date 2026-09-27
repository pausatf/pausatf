#!/usr/bin/env bash
set -euo pipefail

cat >&2 <<'MESSAGE'
This importer is disabled because its resource map targets an obsolete provider
type and does not match the live Cloudflare DNS inventory. Do not import records
with this script. Reconcile the inventory and use the current provider's import
IDs only after confirming the remote backend and state lock.
MESSAGE
exit 1
