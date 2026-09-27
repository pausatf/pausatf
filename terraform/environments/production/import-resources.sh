#!/usr/bin/env bash
# Historical import helper retained as a fail-closed migration notice.
set -euo pipefail
cat >&2 <<'NOTICE'
This import script targets retired resource addresses and the former DigitalOcean
Spaces backend. Production state now lives in HCP Terraform workspace
"pausatf-production". Do not run the historical import commands in this file.

See terraform/INFRASTRUCTURE_INVENTORY.md for the imported resource list and
terraform/README.md for current HCP Terraform operations.
NOTICE
exit 2
