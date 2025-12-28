#!/usr/bin/env bash
# Import existing DigitalOcean resources into Terraform state (Production)
# Usage: ./import-resources.sh

set -euo pipefail

echo "=========================================="
echo "Production Environment - Resource Import"
echo "=========================================="
echo ""

# Check required environment variables
if [ -z "${TF_VAR_ssh_public_key:-}" ]; then
  echo "⚠ Warning: TF_VAR_ssh_public_key not set"
  echo "Set it with: export TF_VAR_ssh_public_key=\"\$(cat ~/.ssh/id_ed25519.pub)\""
fi

if [ -z "${AWS_ACCESS_KEY_ID:-}" ]; then
  echo "⚠ Warning: AWS_ACCESS_KEY_ID not set (needed for backend)"
  echo "Set it with: export AWS_ACCESS_KEY_ID='your-do-spaces-key'"
fi

echo ""
echo "Initializing Terraform..."
terraform init

echo ""
echo "Importing resources..."

# SSH Key
echo -n "1. SSH Key (m3 laptop)... "
if terraform import digitalocean_ssh_key.m3_laptop 46721354 2>/dev/null; then
  echo "✓ Imported"
else
  if terraform state show digitalocean_ssh_key.m3_laptop >/dev/null 2>&1; then
    echo "⚠ Already imported"
  else
    echo "✗ Failed"
  fi
fi

# DigitalOcean Project
echo -n "2. DigitalOcean Project (PAUSATF)... "
if terraform import digitalocean_project.pausatf 8ddce7ba-f064-4611-8460-0771dd817342 2>/dev/null; then
  echo "✓ Imported"
else
  if terraform state show digitalocean_project.pausatf >/dev/null 2>&1; then
    echo "⚠ Already imported"
  else
    echo "✗ Failed"
  fi
fi

# Production Droplet (optional - may already be in state)
echo -n "3. Production Droplet (pausatf-prod)... "
if terraform state show digitalocean_droplet.production >/dev/null 2>&1; then
  echo "⚠ Already in state"
else
  if terraform import digitalocean_droplet.production 355909945 2>/dev/null; then
    echo "✓ Imported"
  else
    echo "⚠ Skipped (may need to be created fresh with cloud-init)"
  fi
fi

# Production Firewall (optional)
echo -n "4. Production Firewall... "
if terraform state show digitalocean_firewall.production >/dev/null 2>&1; then
  echo "⚠ Already in state"
else
  if terraform import digitalocean_firewall.production a4e42798-ab22-467f-a821-daa290f56655 2>/dev/null; then
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
  echo "  - Droplet is using old snapshot instead of cloud-init template"
  echo "  - Firewall rules differ from Terraform definition"
  echo "  - Project resources list differs"
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
echo "Note: Production droplet uses a custom snapshot from 2023-05-16."
echo "To use the new cloud-init template, you'll need to recreate the droplet."
