# PAUSATF Terraform Infrastructure

**Infrastructure as Code for pausatf.org using Terraform**

[![Terraform](https://img.shields.io/badge/Terraform-1.6+-623CE4?logo=terraform)](https://www.terraform.io/)
[![DigitalOcean](https://img.shields.io/badge/DigitalOcean-Provider-0080FF?logo=digitalocean)](https://www.digitalocean.com/)
[![Cloudflare](https://img.shields.io/badge/Cloudflare-Provider-F38020?logo=cloudflare)](https://www.cloudflare.com/)

---

## Overview

This repository contains Terraform configurations for managing PAUSATF.org infrastructure:

- **DigitalOcean Droplets** - Production and staging web servers
- **DigitalOcean Databases** - MySQL clusters for WordPress
- **DigitalOcean Networking** - VPC, firewalls, load balancers
- **Cloudflare** - DNS, CDN, security, and SSL configuration
- **GitHub Repositories** - Repository settings, branch protection, Dependabot
- **Monitoring** - Uptime monitoring and alerting

---

## Repository Structure

```
pausatf-terraform/
├── environments/
│   ├── production/          # Production environment
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   ├── terraform.tfvars.example
│   │   └── backend.tf
│   ├── staging/             # Staging environment
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   ├── terraform.tfvars.example
│   │   └── backend.tf
│   ├── github/              # GitHub repository management
│   │   ├── main.tf
│   │   ├── variables.tf
│   │   ├── terraform.tfvars.example
│   │   └── README.md
│   └── shared/              # Shared resources (DNS, etc.)
│       ├── main.tf
│       └── variables.tf
├── modules/
│   ├── droplet/             # DigitalOcean droplet module
│   ├── database/            # DigitalOcean database module
│   ├── networking/          # VPC, firewall, load balancer
│   ├── cloudflare/          # Cloudflare DNS and security
│   └── monitoring/          # Monitoring and alerts
├── .github/
│   └── workflows/
│       ├── terraform-plan.yml    # PR validation
│       ├── terraform-apply.yml   # Deploy on merge
│       └── security-scan.yml     # tfsec, checkov scans
├── .gitignore               # Terraform-specific ignores
├── .pre-commit-config.yaml  # Pre-commit hooks
├── .tflint.hcl             # TFLint configuration
├── README.md               # This file
└── CONTRIBUTING.md         # Contribution guidelines
```

---

## Prerequisites

### Required Tools

```bash
# Terraform
brew install terraform

# TFLint (Terraform linter)
brew install tflint

# tfsec (Security scanner)
brew install tfsec

# Pre-commit
brew install pre-commit
```

### Required Credentials

Set these environment variables:

```bash
# DigitalOcean
export DIGITALOCEAN_TOKEN="your-do-token"

# Cloudflare
export CLOUDFLARE_API_TOKEN="your-cf-token"
export CLOUDFLARE_ZONE_ID="your-zone-id"
```

**IMPORTANT:** Never commit tokens to the repository!

---

## Getting Started

### 1. Clone Repository

```bash
git clone https://github.com/pausatf/pausatf-terraform.git
cd pausatf-terraform
```

### 2. Install Pre-commit Hooks

```bash
pre-commit install
```

### 3. Initialize Terraform

```bash
cd environments/production
cp terraform.tfvars.example terraform.tfvars
# Edit terraform.tfvars with your values
terraform init
```

### 4. Plan Changes

```bash
terraform plan
```

### 5. Apply Changes

```bash
terraform apply
```

---

## Best Practices

### State Management

- **Remote State:** Stored in DigitalOcean Spaces (S3-compatible)
- **State Locking:** Enabled via DynamoDB-compatible backend
- **Encryption:** State files encrypted at rest

### Workflow

1. **Create Feature Branch**
   ```bash
   git checkout -b feature/add-staging-db
   ```

2. **Make Changes**
   - Edit `.tf` files
   - Run `terraform fmt`
   - Run `terraform validate`

3. **Test Locally**
   ```bash
   terraform plan
   ```

4. **Create Pull Request**
   - GitHub Actions will run:
     - `terraform fmt -check`
     - `terraform validate`
     - `tfsec` security scan
     - `checkov` compliance scan
     - `terraform plan`

5. **Review & Approve**
   - Requires 1 approval
   - All checks must pass

6. **Merge to Main**
   - Automatically runs `terraform apply` for production
   - Manual approval required

### Security

- ✅ **No Hardcoded Secrets** - Use variables and environment vars
- ✅ **Pre-commit Hooks** - Prevent committing secrets
- ✅ **tfsec Scanning** - Security misconfiguration detection
- ✅ **Checkov Scanning** - Policy as code compliance
- ✅ **Least Privilege** - Minimal IAM permissions
- ✅ **Encryption** - All data encrypted at rest and in transit

### Code Quality

- ✅ **Formatting:** `terraform fmt` enforced
- ✅ **Validation:** `terraform validate` required
- ✅ **Linting:** TFLint checks for best practices
- ✅ **Documentation:** All modules documented
- ✅ **Versioning:** Provider versions pinned

---

## Environments

### Production

- **Droplet:** pausatf-prod (4 vCPU, 8GB RAM)
- **Database:** pausatf-production-db (MySQL 8.0)
- **Domain:** www.pausatf.org
- **Cloudflare:** Full protection enabled

### Staging

- **Droplet:** pausatf-stage (2 vCPU, 4GB RAM)
- **Database:** pausatf-stage-db (MySQL 8.0)
- **Domain:** staging.pausatf.org
- **Cloudflare:** Testing configuration

---

## Common Tasks

### Add New Droplet

```bash
cd environments/production
# Edit main.tf to add droplet module
terraform plan
terraform apply
```

### Update DNS Record

```bash
cd environments/shared
# Edit cloudflare module configuration
terraform plan
terraform apply
```

### Destroy Staging Environment

```bash
cd environments/staging
terraform destroy
```

### View Current State

```bash
terraform show
terraform state list
```

---

## CI/CD Pipeline

### Pull Request Workflow

```yaml
On PR → Run:
  1. terraform fmt -check
  2. terraform validate
  3. tfsec scan
  4. checkov scan
  5. terraform plan
  6. Comment plan output on PR
```

### Main Branch Workflow

```yaml
On Merge → Run:
  1. terraform plan
  2. Wait for manual approval
  3. terraform apply
  4. Post results to Slack
```

---

## Troubleshooting

### State Lock Issues

```bash
# View lock info
terraform force-unlock <lock-id>

# Only use if you're sure no other process is running!
```

### Import Existing Resources

```bash
# Import existing droplet
terraform import module.web_server.digitalocean_droplet.main <droplet-id>
```

### Drift Detection

```bash
# Check for configuration drift
terraform plan -refresh-only
```

---

## Security

### Secrets Management

- **DO NOT** commit `.tfvars` files
- **DO NOT** commit state files
- **USE** environment variables for tokens
- **USE** encrypted Terraform Cloud/Spaces for state

### Pre-commit Hooks

Automatically prevent commits with:
- Hardcoded secrets (gitleaks, detect-secrets)
- Unformatted code (terraform fmt)
- Invalid syntax (terraform validate)
- Security issues (tfsec)

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

**Key Requirements:**
- All changes via Pull Request
- 1 approval required
- All CI checks must pass
- Follow HCL style guide
- Document all modules

---

## Support

- **Documentation:** https://github.com/pausatf/pausatf-infrastructure-docs
- **Issues:** https://github.com/pausatf/pausatf-terraform/issues
- **DigitalOcean:** https://docs.digitalocean.com/
- **Terraform:** https://www.terraform.io/docs

---

## License

Private repository - Internal use only

**Maintained by:** Thomas Vincent
**Organization:** Pacific Association of USA Track and Field (PAUSATF)
**Last Updated:** 2025-12-21
