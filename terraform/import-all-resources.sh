#!/usr/bin/env bash
# Master script to import all Terraform resources
# Usage: ./import-all-resources.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=========================================="
echo "PAUSATF Infrastructure - Production and Staging Import"
echo "=========================================="
echo ""
echo "This script imports production and staging resources into Terraform state."
echo "Central Cloudflare-zone import is intentionally skipped until issue #137 reconciliation is complete."
echo "The staging state still owns stage.pausatf.org and uses the Cloudflare provider."
echo ""

# Check required environment variables
MISSING_VARS=0

if [ -z "${TF_VAR_cloudflare_api_token:-}" ]; then
  echo "⚠ Missing: TF_VAR_cloudflare_api_token (required by the staging Cloudflare DNS provider)"
  MISSING_VARS=1
fi

if [ -z "${TF_VAR_ssh_public_key:-}" ]; then
  echo "⚠ Missing: TF_VAR_ssh_public_key"
  MISSING_VARS=1
fi

if [ -z "${AWS_ACCESS_KEY_ID:-}" ]; then
  echo "⚠ Missing: AWS_ACCESS_KEY_ID (DigitalOcean Spaces)"
  MISSING_VARS=1
fi

if [ -z "${AWS_SECRET_ACCESS_KEY:-}" ]; then
  echo "⚠ Missing: AWS_SECRET_ACCESS_KEY (DigitalOcean Spaces)"
  MISSING_VARS=1
fi

if [ "$MISSING_VARS" -eq 1 ]; then
  echo ""
  echo "=========================================="
  echo "Environment Variables Required"
  echo "=========================================="
  echo ""
echo "Please set the following environment variables:"
echo ""
echo "  export TF_VAR_cloudflare_api_token='your-cloudflare-api-token'"
echo "  export TF_VAR_ssh_public_key=\"\$(cat ~/.ssh/id_ed25519.pub)\""
  echo "  export AWS_ACCESS_KEY_ID='your-do-spaces-key'"
  echo "  export AWS_SECRET_ACCESS_KEY='your-do-spaces-secret'"
  echo ""
  echo "Then run this script again."
  exit 1
fi

echo "✓ All required environment variables are set"
echo ""

# Confirmation prompt
read -r -p "This will import production and staging resources into Terraform state. Continue? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
  echo "Import cancelled."
  exit 0
fi

echo ""
echo "=========================================="
echo "Step 1/3: Import Production Resources"
echo "=========================================="
echo ""

cd "${SCRIPT_DIR}/environments/production"
bash import-resources.sh

echo ""
echo "=========================================="
echo "Step 2/3: Import Staging Resources"
echo "=========================================="
echo ""

cd "${SCRIPT_DIR}/environments/staging"
bash import-resources.sh

echo ""
echo "=========================================="
echo "Step 3/3: Cloudflare DNS Import Skipped"
echo "=========================================="
echo ""

echo "Central Cloudflare-zone declarations and state are still being reconciled in issue #137."
echo "Do not import or apply Cloudflare resources until the inventory is verified."
echo "The staging state also owns stage.pausatf.org; import that Tunnel CNAME before applying staging."

echo ""
echo "=========================================="
echo "Production and staging import steps completed."
echo "=========================================="
echo ""
echo "Summary:"
echo "  - Production: SSH key, project, droplet, firewall"
echo "  - Staging: Droplet, database, firewall"
echo "  - Central Cloudflare zone: skipped pending DNS and state reconciliation"
echo "  - Staging DNS: stage.pausatf.org is configured in staging state; its import is pending"
echo ""
echo "Next steps:"
echo ""
echo "1. Verify each environment:"
echo "   cd terraform/environments/production && terraform plan"
echo "   cd terraform/environments/staging && terraform plan"
echo "   cd terraform/environments/cloudflare && terraform plan"
echo ""
echo "2. Review any detected changes"
echo ""
echo "3. Apply GitHub configuration:"
echo "   cd terraform/environments/github"
echo "   terraform init"
echo "   terraform apply"
echo ""
echo "4. Keep Terraform state in the configured DigitalOcean Spaces backend; do not commit state files or backups."
echo ""
echo "Production and staging import steps are complete; Cloudflare and GitHub remain separate follow-up work."
