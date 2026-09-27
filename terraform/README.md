# PAUSATF Terraform Infrastructure

Complete infrastructure as code for PAUSATF (Pan African Ultimate Sports & Training Foundation).

[![Terraform](https://img.shields.io/badge/Terraform-1.6+-623CE4?logo=terraform)](https://www.terraform.io/)
[![DigitalOcean](https://img.shields.io/badge/DigitalOcean-Provider-0080FF?logo=digitalocean)](https://www.digitalocean.com/)
[![Cloudflare](https://img.shields.io/badge/Cloudflare-Provider-F38020?logo=cloudflare)](https://www.cloudflare.com/)

## 🏗️ Architecture Overview

This repository manages infrastructure through separate Terraform states for
production, staging, development, Cloudflare, and GitHub. The Cloudflare state
is currently empty and is being reconciled against the live DNS inventory; do
not apply it until that reconciliation is complete.

```
terraform/
├── environments/           # Environment-specific configurations
│   ├── production/        # Production infrastructure
│   ├── staging/           # Staging infrastructure and stage DNS
│   ├── cloudflare/        # Zone, central DNS, settings, and rulesets
│   ├── github/            # Repository configuration
│   └── dev/               # Development environment
├── modules/               # Reusable Terraform modules
│   ├── cloudflare/        # Cloudflare zone and DNS modules
│   ├── digitalocean/      # DigitalOcean droplet and database modules
│   ├── github/            # GitHub repository module
│   └── droplet/           # Cloud-init templates (Apache, OpenLiteSpeed)
├── import-all-resources.sh        # Master import script
├── INFRASTRUCTURE_INVENTORY.md    # Current state documentation
├── TERRAFORM_MIGRATION_COMPLETE.md # Migration guide
└── README.md                      # This file
```

## 📊 State and resource ownership

| Environment | State ownership |
|-------------|-----------------|
| **Production** | Production droplet, network, firewall, and database resources |
| **Staging** | Staging droplet, network, firewall, database, and `stage.pausatf.org` Tunnel CNAME |
| **Development** | Development resources and `dev.pausatf.org` A record |
| **Cloudflare** | Zone, 37 central DNS records, 5 zone settings, and 4 rulesets; backend state is empty pending reconciliation |
| **GitHub** | Repository settings and branch protection |

The live Cloudflare zone has 39 DNS records. The `dev` and `stage` records are
owned by their environment states and must not also be declared in the central
Cloudflare environment. Import existing resources and require a reviewed
no-change plan before applying to an empty state.

## 🚀 Quick Start

### Prerequisites

- Terraform >= 1.6.0
- DigitalOcean account and API token
- Cloudflare account and API token
- GitHub personal access token
- DigitalOcean Spaces for state storage

### 1. Set Environment Variables

```bash
# Cloudflare
export TF_VAR_cloudflare_api_token="your-cloudflare-api-token"

# GitHub
export TF_VAR_github_token="your-github-personal-access-token"

# DigitalOcean Spaces (for Terraform backend)
export AWS_ACCESS_KEY_ID="your-do-spaces-key"
export AWS_SECRET_ACCESS_KEY="your-do-spaces-secret"
export AWS_REGION="us-east-1"
export AWS_DEFAULT_REGION="us-east-1"

# Production SSH Key
export TF_VAR_ssh_public_key="$(cat ~/.ssh/id_ed25519.pub)"
```

### 2. Start the Production and Staging Imports

The orchestration script imports the production and staging resources covered
by its child scripts. It does not import the central Cloudflare inventory or
GitHub resources. The Cloudflare zone and central DNS records, plus the staging
`stage.pausatf.org` record, still need explicit reconciliation and import under
issue #137. The staging provider needs the Cloudflare token above. The child
scripts initialize the DigitalOcean Spaces backend from `backend.hcl`.

```bash
cd terraform
./import-all-resources.sh
```

Review each script's plan and import output. Do not interpret a successful run
as a complete infrastructure import; confirm the resource list against the
environment inventories and the actual remote states.

### 3. Verify Configuration

```bash
# Read-only plans; review differences before making any changes.
cd environments/production && terraform plan
cd environments/staging && terraform plan
cd environments/cloudflare && terraform plan
cd environments/github && terraform plan
```

The Cloudflare and staging states were empty when last checked. Do not apply
either configuration until all existing resources have been imported and the
reviewed plan shows no unexplained changes. The root import script does not
import GitHub resources; reconcile and import those separately before planning
changes there.

### 4. Apply Reviewed Changes

Apply only after the relevant environment's state is reconciled, its plan has
been reviewed, and the resource owner has approved the proposed changes. Never
apply an empty or partially imported state: Terraform may create duplicates or
change live infrastructure.

## 📁 Environments

### Production (`environments/production/`)

**Infrastructure:**
- **Droplet:** pausatf-prod (REDACTED_PROD_NEW_IP)
  - Size: s-4vcpu-8gb (4 vCPUs, 8GB RAM)
  - Image: Custom snapshot (2023-05-16)
  - Cloud-init: Apache 2.4 + PHP 7.4
- **Firewall:** HTTP, HTTPS, SSH (restricted)
- **SSH Key:** m3 laptop (46721354)
- **Project:** PAUSATF

**DNS:** `pausatf.org` and `www` route through the production Cloudflare Tunnel.
Direct service records are listed in the [Cloudflare environment inventory](environments/cloudflare/README.md).

### Staging (`environments/staging/`)

**Infrastructure:**
- **Droplet:** pausatf-stage (REDACTED_STAGE_IP)
  - Size: s-2vcpu-4gb (2 vCPUs, 4GB RAM)
  - Cloud-init: OpenLiteSpeed + PHP 8.4 + memcached + Redis
- **Database:** MySQL 8 (db-s-1vcpu-1gb, 1 node)
- **Firewall:** HTTP, HTTPS, SSH, OpenLiteSpeed WebAdmin (7080)

**Features:**
- Object caching (memcached + Redis, socket-based)
- LSCache plugin ready
- WebAdmin on port 7080

**DNS:** `stage.pausatf.org` is a proxied CNAME to the staging Cloudflare
Tunnel. The staging state is currently empty; import the existing record
before applying a plan.

### Cloudflare (`environments/cloudflare/`)

The central Cloudflare configuration declares 47 resources: the zone, 37 DNS
records, 5 zone settings, and 4 rulesets. It excludes `dev.pausatf.org` and
`stage.pausatf.org`, which are owned by the dev and staging states. The central
Cloudflare state is empty, and the staging state was also empty when checked on
2026-09-27. The ruleset and zone-setting declarations still need verification
with a Cloudflare API token that can read them. See the
[Cloudflare environment safety notes](environments/cloudflare/README.md).

### GitHub (`environments/github/`)

**Repository:** pausatf (monorepo)

**Features:**
- Branch protection on main (signed commits, required reviews)
- Required CI checks: terraform-validate, terraform-fmt, ansible-lint, shellcheck
- 13 topics: infrastructure-as-code, terraform, ansible, wordpress, etc.

## 🔧 Common Operations

### Update a DNS record

Edit the resource in its owning environment, then review its Terraform plan.
Production web traffic uses Cloudflare Tunnel CNAMEs; the staging and dev
records have their own environment states. Direct A records are inventory
values and should be changed only after confirming the destination and owner.
Do not apply Cloudflare or staging plans until existing records are imported
and the plan has no unreviewed changes.

### Add DNS Record

```bash
cd environments/cloudflare
# Edit the owning environment's main.tf to add a cloudflare_dns_record resource
terraform plan
terraform apply
```

### Recreate Droplet with Cloud-Init

```bash
cd environments/production  # or staging
terraform taint digitalocean_droplet.production
terraform apply
```

## 📚 Documentation

- **[INFRASTRUCTURE_INVENTORY.md](INFRASTRUCTURE_INVENTORY.md)** - Current state analysis
- **[TERRAFORM_MIGRATION_COMPLETE.md](TERRAFORM_MIGRATION_COMPLETE.md)** - Migration guide
- **[modules/droplet/README.md](modules/droplet/README.md)** - Cloud-init templates
- **[environments/cloudflare/README.md](environments/cloudflare/README.md)** - DNS management

## 🔐 State Management

All Terraform state is stored in DigitalOcean Spaces (S3-compatible):

```
Bucket: pausatf-terraform-state
Region: sfo2 (us-west-1)
Endpoint: sfo2.digitaloceanspaces.com

States:
  - production/terraform.tfstate
  - staging/terraform.tfstate
  - cloudflare/terraform.tfstate
  - github/terraform.tfstate
```

**Features:** Encryption at rest, versioning enabled, state locking

## 🛠️ Troubleshooting

### State Lock

```bash
terraform force-unlock LOCK_ID
```

### Import Drift

```bash
terraform refresh
terraform show
# Update Terraform config to match reality
```

### Backend Access

```bash
# Verify credentials
echo $AWS_ACCESS_KEY_ID
terraform init -reconfigure
```

## 🔒 Security Best Practices

✅ Never commit secrets - use environment variables
✅ Use state locking - prevent concurrent modifications
✅ Review plans - always `terraform plan` before `apply`
✅ Limit SSH access - IP whitelisting in production
✅ Enable branch protection - require reviews
✅ Rotate API tokens - regularly update credentials
✅ Use signed commits - verify authorship
✅ Backup state - DigitalOcean Spaces versioning enabled

## 📈 Monitoring

```bash
# List all resources
terraform state list

# Show specific resource
terraform state show RESOURCE_NAME

# Check for drift
terraform plan
```

## 🤝 Contributing

1. Create feature branch from `main`
2. Make infrastructure changes
3. Run `terraform plan`
4. Commit with descriptive message
5. Create pull request
6. Get 1 approval + pass CI checks
7. Merge to main
8. Run `terraform apply`

## 📞 Support

- Check documentation in `terraform/` directory
- Review `INFRASTRUCTURE_INVENTORY.md`
- Create GitHub issue in pausatf/pausatf

---

**Maintained by:** PAUSATF Infrastructure Team
**Last Updated:** 2025-12-27
**Terraform Version:** >= 1.6.0
**Total Resources:** 39
