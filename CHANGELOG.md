# Changelog

All notable changes to PAUSATF Terraform infrastructure will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to semantic versioning.

---

## [Unreleased]

### Added
- GitHub repository management module
- GitHub environment configuration for managing all PAUSATF repositories
- Automated branch protection rules with required GPG signing
- Dependabot security updates configuration via Terraform
- Required status checks for Terraform, Ansible, and Scripts repositories
- Comprehensive documentation for GitHub repository management

---

## [1.0.0] - 2025-12-21

### Added
- Initial Terraform infrastructure repository setup
- Production environment configuration (s-4vcpu-8gb droplet, MySQL 8 database)
- Staging environment configuration (s-2vcpu-4gb droplet, MySQL 8 database)
- VPC networking for production (10.10.0.0/16) and staging (10.20.0.0/16)
- Firewall rules for HTTP, HTTPS, and SSH access
- Database firewall rules restricting access to droplets only
- Remote state backend configuration (DigitalOcean Spaces)
- Complete variable definitions for both environments
- Terraform outputs for droplet IPs, database connections, VPC IDs
- Pre-commit hooks (terraform fmt, validate, tfsec, tflint, checkov)
- GitHub Actions workflows for validation and planning
- Security scanning with tfsec and checkov
- Secret detection with gitleaks and detect-secrets
- Comprehensive README and CONTRIBUTING documentation

### Security
- All database credentials marked as sensitive
- SSH access configurable per environment
- Database firewalls restrict connections to droplets only
- Production backups enabled, staging disabled for cost savings
- Multi-layer security scanning in CI/CD pipeline

---

## Infrastructure Details

### Production
- Droplet: pausatf-prod (ID: 355909945)
- IP: 64.225.40.54
- Size: s-4vcpu-8gb (8GB RAM, 4 vCPUs, 160GB disk)
- Database: pausatf-production-db (MySQL 8, db-s-2vcpu-4gb)
- Region: sfo2
- Backups: Enabled

### Staging
- Droplet: pausatf-stage (ID: 538411208)
- IP: 64.227.85.73
- Size: s-2vcpu-4gb (4GB RAM, 2 vCPUs, 80GB disk)
- Database: pausatf-stage-db (MySQL 8, db-s-1vcpu-1gb)
- Region: sfo2
- Backups: Disabled
