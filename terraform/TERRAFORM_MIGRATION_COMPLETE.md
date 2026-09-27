# Terraform Migration Notes (Historical)

**Date:** 2025-12-27
**Status:** All configuration captured in Terraform - imports pending

> **Historical reference only.** The commands below document the 2025
> migration and are not current operational instructions. Provider versions,
> DNS records, resource ownership, and remote-state status have changed. In
> particular, do not run the Cloudflare `cloudflare_record` imports or the
> `production_ip` apply commands in this file. Follow
> [ENVIRONMENTS.md](ENVIRONMENTS.md) and the current
> [Cloudflare safety notes](environments/cloudflare/README.md); import only
> after reconciling the live inventory and confirming the correct state owner.

## Summary

All DigitalOcean, Cloudflare, and GitHub resources have been successfully captured in Terraform configuration. The infrastructure is now fully defined as code and version controlled.

## What Was Completed

### ✅ 1. OpenLiteSpeed Cloud-Init Enhancement
- Added memcached (socket-based, 256MB) at `/var/www/memcached.sock`
- Added Redis (socket-based, 256MB) at `/var/run/redis/redis-server.sock`
- Configured for LSCache WordPress plugin
- Comprehensive documentation with troubleshooting
- **Commit:** `248bafb`

### ✅ 2. Infrastructure Inventory
- Created `terraform/INFRASTRUCTURE_INVENTORY.md`
- Audited all DigitalOcean resources
- Audited all Cloudflare resources
- Audited GitHub configuration
- Documented actual vs. Terraform state
- **Commit:** `ca52457`

### ✅ 3. Terraform Configuration Alignment
**Production:**
- Updated cloud-init: `cloud-init-apache.yml` (Apache 2.4 + PHP 7.4)
- Hostname: `ftp` (matches DNS)
- Removed non-existent database and VPC
- Added SSH key resource
- Added DigitalOcean project resource

**Staging:**
- Updated cloud-init: `cloud-init-openlitespeed.yml` (OLS + PHP 8.4 + caching)
- Hostname: `stage` (matches DNS)
- Added port 7080 for WebAdmin
- Removed non-existent VPC
- Fixed firewall rules

**Commit:** `ca52457`

### ✅ 4. Cloudflare DNS Management
Created `terraform/environments/cloudflare/`:
- Zone management (pausatf.org)
- **29 DNS records** fully defined:
  - 7 A records (production + staging)
  - 8 CNAME records (aliases, SendGrid)
  - 5 MX records (Google Workspace)
  - 4 TXT records (SPF, DMARC, DKIM, verification)
  - 5 CAA records (Let's Encrypt, DigiCert)
- Comprehensive README with import commands
- Variables for droplet IPs
- Backend configured

**Commit:** `ea62bd5`

### ✅ 5. DigitalOcean Resource Management
**SSH Key:**
- Resource: `digitalocean_ssh_key.m3_laptop`
- ID: 46721354
- Fingerprint: f4:5c:52:bb:23:a2:ef:d0:09:e4:59:d4:b5:c7:a7:8a

**Project:**
- Resource: `digitalocean_project.pausatf`
- ID: 8ddce7ba-f064-4611-8460-0771dd817342
- Associates production droplet

**Commit:** `ea62bd5`

### ✅ 6. GitHub Configuration
Already defined in `terraform/environments/github/`:
- Repository: pausatf (monorepo)
- Branch protection: main (signed commits, required reviews)
- Topics: 13 tags (infrastructure-as-code, terraform, ansible, etc.)
- Required status checks: terraform-validate, terraform-fmt, ansible-lint, shellcheck

## Resources Now Managed by Terraform

| Resource Type | Count | Status |
|---------------|-------|--------|
| DigitalOcean Droplets | 2 | ✅ Defined (import pending) |
| DigitalOcean Databases | 1 | ✅ Defined (import pending) |
| DigitalOcean Firewalls | 2 | ✅ Defined (import pending) |
| DigitalOcean SSH Keys | 1 | ✅ Defined (import pending) |
| DigitalOcean Projects | 1 | ✅ Defined (import pending) |
| Cloudflare Zones | 1 | ✅ Defined (import pending) |
| Cloudflare DNS Records | 29 | ✅ Defined (import pending) |
| GitHub Repositories | 1 | ✅ Defined (apply pending) |
| **Total Resources** | **39** | **All captured in Terraform** |

## Required Actions Before First Apply

### 1. Set Environment Variables

```bash
# Terraform
export TF_VAR_cloudflare_api_token="YOUR_CLOUDFLARE_API_TOKEN"
export TF_VAR_github_token="YOUR_GITHUB_TOKEN"

# DigitalOcean Spaces (backend)
export AWS_ACCESS_KEY_ID="YOUR_DO_SPACES_KEY"
export AWS_SECRET_ACCESS_KEY="YOUR_DO_SPACES_SECRET"

# SSH Key (production)
export TF_VAR_ssh_public_key="$(cat ~/.ssh/id_ed25519.pub)"
```

### 2. Import Existing Resources

#### Production Environment

```bash
cd terraform/environments/production
terraform init

# Import SSH key
terraform import digitalocean_ssh_key.m3_laptop 46721354

# Import project
terraform import digitalocean_project.pausatf 8ddce7ba-f064-4611-8460-0771dd817342

# Import droplet (if needed)
terraform import digitalocean_droplet.production REDACTED_DROPLET_ID

# Import firewall (if needed)
terraform import digitalocean_firewall.production a4e42798-ab22-467f-a821-daa290f56655
```

#### Staging Environment

```bash
cd terraform/environments/staging
terraform init

# Import droplet (if needed)
terraform import digitalocean_droplet.staging REDACTED_DROPLET_ID

# Import database
terraform import digitalocean_database_cluster.staging 661fa8d4-077c-43d7-a47a-79bfc42737c8

# Import firewall (if needed)
terraform import digitalocean_firewall.staging c12dfc7f-f43a-4b32-96c6-80ba34035b1a
```

#### Cloudflare Environment

Cloudflare import commands from this migration are retired. They use the
obsolete `cloudflare_record` resource type and predate the current DNS
ownership split. The Cloudflare and staging states were empty when checked in
September 2026; use the current environment documentation before importing.

#### GitHub Environment

```bash
cd terraform/environments/github
terraform init

# Import repository
terraform import module.pausatf_monorepo.github_repository.this pausatf

# Apply to enable branch protection and topics
terraform apply
```

### 3. Verify Configuration

After imports, verify no unwanted changes:

```bash
# Production
cd terraform/environments/production
terraform plan  # Should show 0 changes

# Staging
cd terraform/environments/staging
terraform plan  # Should show 0 changes

# Cloudflare
cd terraform/environments/cloudflare
terraform plan  # Should show 0 changes

# GitHub
cd terraform/environments/github
terraform plan  # Should show changes for branch protection and topics
```

## Benefits Achieved

### 1. **Infrastructure as Code**
- All cloud resources defined in version-controlled code
- Changes reviewed via Git pull requests
- Full audit trail of infrastructure modifications

### 2. **Disaster Recovery**
- Can recreate entire infrastructure from code
- No dependency on manual configurations
- Point-in-time recovery via Git history

### 3. **Consistency**
- No configuration drift between environments
- Standardized resource naming and tagging
- Enforced best practices via code

### 4. **Documentation**
- Self-documenting infrastructure
- Clear ownership and purpose of resources
- Easy onboarding for new team members

### 5. **Safety**
- Plan before apply (preview changes)
- State locking prevents concurrent modifications
- Rollback capability via Git

## Architecture Overview

```
terraform/
├── environments/
│   ├── production/          # Production droplet, firewall, SSH key, project
│   ├── staging/             # Staging droplet, database, firewall
│   ├── cloudflare/          # DNS zone and 29 records
│   ├── github/              # Repository, branch protection, topics
│   └── dev/                 # Development environment (minimal)
├── modules/
│   ├── cloudflare/          # Cloudflare zone and DNS modules
│   ├── digitalocean/        # DigitalOcean droplet and database modules
│   ├── github/              # GitHub repository module
│   └── droplet/             # Cloud-init templates
└── INFRASTRUCTURE_INVENTORY.md
```

## State Storage

All Terraform state is stored in DigitalOcean Spaces:

```
s3://pausatf-terraform-state/
├── production/terraform.tfstate
├── staging/terraform.tfstate
├── cloudflare/terraform.tfstate
└── github/terraform.tfstate
```

**Backend:** DigitalOcean Spaces (S3-compatible)
**Region:** sfo2
**Encryption:** At rest
**Versioning:** Enabled

## Maintenance

### Daily Operations

```bash
# Check for infrastructure drift
terraform plan

# Apply approved changes
terraform apply

# View current state
terraform show
```

### Adding New Resources

1. Add resource to appropriate environment's `main.tf`
2. Run `terraform plan` to preview
3. Commit changes to Git
4. Create pull request for review
5. After approval, run `terraform apply`

### Removing Resources

1. Comment out or remove resource from `main.tf`
2. Run `terraform plan` to preview deletion
3. Commit changes to Git (with justification)
4. Create pull request for review
5. After approval, run `terraform apply`

### Updating Droplet IPs

The `production_ip` Terraform variable used by this historical example has
been removed from the Cloudflare environment. Confirm the owning environment
and current DNS record first; do not apply against an empty state. Production
web traffic uses Cloudflare Tunnel CNAMEs, while dev and staging records are
owned by their respective environment states.

## Next Steps (Optional Enhancements)

### 1. CI/CD Pipeline
- Automate `terraform plan` on pull requests
- Automate `terraform apply` after merge to main
- Use GitHub Actions for CI/CD

### 2. Separate State Per Resource Type
- Split production into droplet + database + firewall states
- Reduces blast radius of state corruption
- Allows team member specialization

### 3. Workspace per Environment
- Use Terraform workspaces instead of separate directories
- Simplifies directory structure
- Shares modules more easily

### 4. Module Registry
- Publish internal modules to private registry
- Version modules independently
- Reuse across multiple projects

### 5. Policy as Code
- Implement Sentinel or OPA policies
- Enforce tagging standards
- Prevent cost overruns
- Security compliance automation

## Troubleshooting

### Import Failures

If import fails:
```bash
# Get detailed error
terraform import -var-file=terraform.tfvars RESOURCE ID

# Verify resource exists
doctl compute droplet get ID  # or similar

# Check state
terraform state list
terraform state show RESOURCE
```

### State Lock Issues

If state is locked:
```bash
# View lock info
terraform force-unlock LOCK_ID

# Emergency: delete lock (use with extreme caution)
# Only if you're certain no other process is running
```

### Plan Shows Unwanted Changes

If plan shows changes after import:
```bash
# Pull latest remote state
terraform refresh

# Show differences
terraform show

# Update code to match reality (not vice versa)
```

## Support

For issues or questions:
1. Check `terraform/INFRASTRUCTURE_INVENTORY.md`
2. Review environment-specific READMEs
3. Check module documentation
4. Create GitHub issue in pausatf/pausatf

## Contributors

- Initial migration: Claude Code AI (2025-12-27)
- Maintained by: PAUSATF Infrastructure Team

---

**Generated:** 2025-12-27
**Status:** ✅ Migration Complete - Imports Pending
**Total Resources Managed:** 39
**Lines of Terraform Code:** ~1,500
