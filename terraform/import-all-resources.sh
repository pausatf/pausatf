#!/usr/bin/env bash
# Historical orchestration helper retained as a fail-closed migration notice.
set -euo pipefail
cat >&2 <<'NOTICE'
This import script targets retired resource addresses and the former DigitalOcean
Spaces backend. Production and staging states now live in HCP Terraform. The
current DigitalOcean resources are already imported; Cloudflare imports require
the scoped API token and resource ownership described in the inventory.

Do not run the historical import scripts. See terraform/README.md and
terraform/INFRASTRUCTURE_INVENTORY.md for current state and operations.
NOTICE
exit 2
