# PAUSATF Terraform Infrastructure

Complete infrastructure as code for PAUSATF (Pan African Ultimate Sports & Training Foundation).

[![Terraform](https://img.shields.io/badge/Terraform-1.6+-623CE4?logo=terraform)](https://www.terraform.io/)
[![DigitalOcean](https://img.shields.io/badge/DigitalOcean-Provider-0080FF?logo=digitalocean)](https://www.digitalocean.com/)
[![Cloudflare](https://img.shields.io/badge/Cloudflare-Provider-F38020?logo=cloudflare)](https://www.cloudflare.com/)

## 🏗️ Architecture Overview

This Terraform configuration manages **39 cloud resources** across:
- **DigitalOcean** - Compute, databases, networking (8 resources)
- **Cloudflare** - DNS and CDN (30 resources)
- **GitHub** - Repository and CI/CD (1 resource)

```
terraform/
├── environments/           # Environment-specific configurations
│   ├── production/        # Production environment (4 resources)
│   ├── staging/           # Staging environment (4 resources)
│   ├── cloudflare/        # DNS management (30 resources)
│   ├── github/            # Repository configuration (1 resource)
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

## 📊 Resources Managed

| Environment | Resources | Description |
|-------------|-----------|-------------|
| **Production** | 4 | Droplet (Apache + PHP 7.4), firewall, SSH key, project |
| **Staging** | 4 | Droplet (OpenLiteSpeed + PHP 8.4 + caching), MySQL 8, firewalls |
| **Cloudflare** | 30 | Zone + 29 DNS records (A, CNAME, MX, TXT, CAA) |
| **GitHub** | 1 | Repository with branch protection and topics |
| **Total** | **39** | All cloud resources managed as code |

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

# Production SSH Key
export TF_VAR_ssh_public_key="$(cat ~/.ssh/id_ed25519.pub)"
```

### 2. Import Existing Resources

If starting from existing infrastructure:

```bash
cd terraform
./import-all-resources.sh
```

This will import all 39 resources into Terraform state.

### 3. Verify Configuration

```bash
# Check each environment
cd environments/production && terraform plan
cd environments/staging && terraform plan
cd environments/cloudflare && terraform plan
cd environments/github && terraform plan
```

### 4. Apply Configuration

```bash
# Apply GitHub configuration (branch protection, topics)
cd environments/github
terraform init
terraform apply

# Production/staging/cloudflare (if changes needed)
cd environments/production && terraform apply
```

## 📁 Environments

### Production (`environments/production/`)

**Infrastructure:**
- **Droplet:** pausatf-prod (64.225.40.54)
  - Size: s-4vcpu-8gb (4 vCPUs, 8GB RAM)
  - Image: Custom snapshot (2023-05-16)
  - Cloud-init: Apache 2.4 + PHP 7.4
- **Firewall:** HTTP, HTTPS, SSH (restricted)
- **SSH Key:** m3 laptop (46721354)
- **Project:** PAUSATF

**DNS:** pausatf.org, www, ftp, mail, monitor (all → 64.225.40.54)

### Staging (`environments/staging/`)

**Infrastructure:**
- **Droplet:** pausatf-stage (64.227.85.73)
  - Size: s-2vcpu-4gb (2 vCPUs, 4GB RAM)
  - Cloud-init: OpenLiteSpeed + PHP 8.4 + memcached + Redis
- **Database:** MySQL 8 (db-s-1vcpu-1gb, 1 node)
- **Firewall:** HTTP, HTTPS, SSH, OpenLiteSpeed WebAdmin (7080)

**Features:**
- Object caching (memcached + Redis, socket-based)
- LSCache plugin ready
- WebAdmin on port 7080

**DNS:** stage.pausatf.org, staging.pausatf.org

### Cloudflare (`environments/cloudflare/`)

**Manages 30 resources:**
- **Zone:** pausatf.org
- **7 A records:** root, www (proxied), ftp, mail, monitor, stage, staging
- **8 CNAME records:** prod, SendGrid email/DKIM
- **5 MX records:** Google Workspace
- **4 TXT records:** SPF, DMARC, DKIM, Google verification
- **5 CAA records:** Let's Encrypt, DigiCert, iodef

### GitHub (`environments/github/`)

**Repository:** pausatf (monorepo)

**Features:**
- Branch protection on main (signed commits, required reviews)
- Required CI checks: terraform-validate, terraform-fmt, ansible-lint, shellcheck
- 13 topics: infrastructure-as-code, terraform, ansible, wordpress, etc.

## 🔧 Common Operations

### Update Droplet IP

```bash
# Production
cd environments/production
terraform apply -var="production_ip=NEW_IP"

# Update Cloudflare DNS
cd ../cloudflare
terraform apply -var="production_ip=NEW_IP"
```

### Add DNS Record

```bash
cd environments/cloudflare
# Edit main.tf to add cloudflare_record resource
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
