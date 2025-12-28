#!/usr/bin/env bash
# Import existing DigitalOcean resources into Terraform state (Staging)
# Usage: ./import-resources.sh

set -euo pipefail

echo "=========================================="
echo "Staging Environment - Resource Import"
echo "=========================================="
echo ""

# Check required environment variables
if [ -z "${AWS_ACCESS_KEY_ID:-}" ]; then
  echo "⚠ Warning: AWS_ACCESS_KEY_ID not set (needed for backend)"
  echo "Set it with: export AWS_ACCESS_KEY_ID='your-do-spaces-key'"
fi

echo ""
echo "Initializing Terraform..."
terraform init

echo ""
echo "Importing resources..."

# Staging Droplet (optional - may already be in state)
echo -n "1. Staging Droplet (pausatf-stage)... "
if terraform state show digitalocean_droplet.staging >/dev/null 2>&1; then
  echo "⚠ Already in state"
else
  if terraform import digitalocean_droplet.staging 538411208 2>/dev/null; then
    echo "✓ Imported"
  else
    echo "⚠ Skipped (may need to be created fresh with cloud-init)"
  fi
fi

# Staging Database
echo -n "2. Staging Database (pausatf-stage-db)... "
if terraform import digitalocean_database_cluster.staging 661fa8d4-077c-43d7-a47a-79bfc42737c8 2>/dev/null; then
  echo "✓ Imported"
else
  if terraform state show digitalocean_database_cluster.staging >/dev/null 2>&1; then
    echo "⚠ Already imported"
  else
    echo "✗ Failed"
  fi
fi

# Database Firewall
echo -n "3. Database Firewall... "
if terraform import digitalocean_database_firewall.staging 661fa8d4-077c-43d7-a47a-79bfc42737c8 2>/dev/null; then
  echo "✓ Imported"
else
  if terraform state show digitalocean_database_firewall.staging >/dev/null 2>&1; then
    echo "⚠ Already imported"
  else
    echo "✗ Failed"
  fi
fi

# Staging Firewall (optional)
echo -n "4. Staging Firewall... "
if terraform state show digitalocean_firewall.staging >/dev/null 2>&1; then
  echo "⚠ Already in state"
else
  if terraform import digitalocean_firewall.staging c12dfc7f-f43a-4b32-96c6-80ba34035b1a 2>/dev/null; then
    echo "✓ Imported"
  else
    echo "⚠ Skipped (firewall may need manual import or recreation)"
  fi
fi

echo ""
echo "=========================================="
echo "Verifying imports..."
echo "=========================================="
echo ""

# Run terraform plan to check for drift
echo "Running terraform plan..."
if terraform plan -detailed-exitcode >/dev/null 2>&1; then
  echo "✓ No changes detected - imports successful!"
else
  echo "⚠ Changes detected. Review with: terraform plan"
  echo ""
  echo "This is expected if:"
  echo "  - Droplet is using marketplace image instead of cloud-init template"
  echo "  - Firewall rules differ from Terraform definition"
  echo "  - Database firewall rules differ"
fi

echo ""
echo "=========================================="
echo "Import Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Review changes: terraform plan"
echo "  2. If changes look correct: terraform apply"
echo "  3. Commit state to version control"
echo ""
echo "Note: Staging droplet uses DigitalOcean's OpenLiteSpeed marketplace image."
echo "To use the new cloud-init template with memcached/Redis, recreate the droplet."
