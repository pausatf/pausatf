#!/usr/bin/env bash
# Master script to import all Terraform resources
# Usage: ./import-all-resources.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=========================================="
echo "PAUSATF Infrastructure - Import All Resources"
echo "=========================================="
echo ""
echo "This script will import all existing cloud resources into Terraform state."
echo ""

# Check required environment variables
MISSING_VARS=0

if [ -z "${TF_VAR_cloudflare_api_token:-}" ]; then
  echo "⚠ Missing: TF_VAR_cloudflare_api_token"
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
read -p "This will import 39 resources into Terraform state. Continue? (yes/no): " CONFIRM
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
echo "Step 3/3: Import Cloudflare DNS Records"
echo "=========================================="
echo ""

cd "${SCRIPT_DIR}/environments/cloudflare"
bash import-dns-records.sh

echo ""
echo "=========================================="
echo "🎉 All Resources Imported Successfully!"
echo "=========================================="
echo ""
echo "Summary:"
echo "  - Production: SSH key, project, droplet, firewall"
echo "  - Staging: Droplet, database, firewall"
echo "  - Cloudflare: Zone + 29 DNS records"
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
echo "4. Commit Terraform state to version control"
echo ""
echo "All infrastructure is now managed by Terraform! 🚀"
