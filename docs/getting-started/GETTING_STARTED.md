# Getting Started with Infrastructure as Code

Welcome! This guide will help you get started with managing your infrastructure using this repository.

## What Has Been Created

This repository contains a complete Infrastructure as Code setup for your entire infrastructure:

### Repository Structure
```
infrastructure-as-code/
├── terraform/              # Infrastructure provisioning
│   ├── modules/           # Reusable Terraform modules
│   └── environments/      # Environment-specific configs
├── ansible/               # Server configuration management
│   ├── playbooks/        # Ansible playbooks
│   └── roles/            # Ansible roles
├── docker/               # Container configurations
├── scripts/              # Utility scripts
├── docs/                 # Documentation
│   └── runbooks/        # Operational guides
└── .github/workflows/   # CI/CD pipelines
```

### What's Managed

✅ **DigitalOcean Resources**
- Production web server (pausatf-prod): 8GB RAM, 4 vCPUs
- Staging web server (pausatf-stage): 4GB RAM, 2 vCPUs
- Staging MySQL database (pausatf-stage-db)
- Firewalls, VPCs, SSH keys

✅ **Cloudflare Resources**
- pausatf.org DNS zone and all records
- SSL/TLS configuration
- Security and performance settings

✅ **Server Configuration**
- Nginx web server
- PHP 8.3 with required extensions
- WordPress configuration
- Security hardening (fail2ban, UFW, SSH)
- Automatic security updates

✅ **Integrations**
- Google Workspace email (MX records)
- SendGrid transactional email (DKIM, SPF)
- Let's Encrypt SSL certificates

## Next Steps

### 1. Set Up Your Environment

#### Install Required Tools

**macOS:**
```bash
brew install terraform ansible docker doctl pre-commit
```

**Linux (Ubuntu/Debian):**
```bash
# Terraform
wget -O- https://apt.releases.hashicorp.com/gpg | sudo gpg --dearmor -o /usr/share/keyrings/hashicorp-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/hashicorp-archive-keyring.gpg] https://apt.releases.hashicorp.com $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/hashicorp.list
sudo apt update && sudo apt install terraform ansible

# Other tools
sudo apt install python3-pip docker.io
pip3 install pre-commit
```

#### Configure Authentication

```bash
# DigitalOcean
export DIGITALOCEAN_ACCESS_TOKEN="your-do-token"
doctl auth init

# Cloudflare
export CLOUDFLARE_API_TOKEN="your-cf-token"
export CLOUDFLARE_EMAIL="your-email@example.com"

# Add to your ~/.zshrc or ~/.bashrc for persistence
echo 'export DIGITALOCEAN_ACCESS_TOKEN="your-do-token"' >> ~/.zshrc
echo 'export CLOUDFLARE_API_TOKEN="your-cf-token"' >> ~/.zshrc
```

### 2. Set Up Terraform Cloud (Recommended)

Terraform Cloud provides remote state storage with encryption and versioning:

1. Create account at [app.terraform.io](https://app.terraform.io)
2. Create organization: `thomasvincent-infrastructure`
3. Create workspaces:
   - `infrastructure-production`
   - `infrastructure-staging`
4. Login locally:
   ```bash
   terraform login
   ```

### 3. Import Existing Resources

Since your infrastructure already exists, you need to import it into Terraform state:

```bash
cd terraform/environments/production
terraform init

# Import droplets
terraform import module.pausatf_prod.digitalocean_droplet.this 355909945
terraform import module.pausatf_stage.digitalocean_droplet.this 538411208

# Import database
terraform import module.pausatf_stage_db.digitalocean_database_cluster.this 661fa8d4-077c-43d7-a47a-79bfc42737c8

# Import Cloudflare zone
terraform import module.pausatf_cloudflare_zone.cloudflare_zone.this 67b87131144a68ad5ed43ebfd4e6d811

# Verify
terraform plan
```

### 4. Configure Ansible Inventory

```bash
cd ansible

# Copy example inventory
cp inventory/hosts.yml.example inventory/hosts.yml

# Edit with your actual IPs (already populated with current values)
vim inventory/hosts.yml

# Test connection
ansible all -i inventory/hosts.yml -m ping
```

### 5. Set Up Pre-commit Hooks

```bash
cd infrastructure-as-code
pre-commit install

# Test hooks
pre-commit run --all-files
```

### 6. Configure GitHub Secrets

Add these secrets to your GitHub repository (Settings → Secrets and variables → Actions):

- `TF_API_TOKEN`: Terraform Cloud API token
- `DIGITALOCEAN_ACCESS_TOKEN`: DigitalOcean API token
- `CLOUDFLARE_API_TOKEN`: Cloudflare API token
- `CLOUDFLARE_EMAIL`: Cloudflare account email

## Common Tasks

### Make Infrastructure Changes

```bash
cd terraform/environments/production

# Plan changes
terraform plan -out=tfplan

# Review the plan carefully
terraform show tfplan

# Apply changes
terraform apply tfplan
```

### Configure Servers

```bash
cd ansible

# Run full configuration
ansible-playbook -i inventory/hosts.yml playbooks/site.yml

# Run specific role
ansible-playbook -i inventory/hosts.yml playbooks/site.yml --tags wordpress

# Test changes first
ansible-playbook -i inventory/hosts.yml playbooks/site.yml --check
```

### View Infrastructure Status

```bash
# DigitalOcean resources
doctl compute droplet list
doctl databases list

# Terraform state
cd terraform/environments/production
terraform show

# DNS records
dig pausatf.org
```

## Safety Practices

### Before Making Changes

1. **Always review plans**
   ```bash
   terraform plan
   ansible-playbook --check playbooks/site.yml
   ```

2. **Test in staging first**
   ```bash
   cd terraform/environments/staging
   terraform plan
   ```

3. **Verify backups**
   ```bash
   doctl compute droplet-action list 355909945
   doctl databases backups list pausatf-stage-db
   ```

4. **Create change tickets** for significant changes

### After Making Changes

1. **Verify functionality**
   ```bash
   curl -I https://pausatf.org
   ssh root@pausatf-prod "systemctl status nginx php8.3-fpm"
   ```

2. **Check monitoring** for any alerts

3. **Update documentation** if procedures changed

4. **Update CHANGELOG.md** with changes made

## Disaster Recovery

If something goes wrong:

1. **Check runbooks**: `docs/runbooks/disaster-recovery.md`
2. **Rollback Terraform**:
   ```bash
   terraform apply -target=module.RESOURCE_NAME
   ```
3. **Rollback Ansible**:
   ```bash
   git checkout HEAD~1
   ansible-playbook playbooks/site.yml
   ```
4. **Restore from backup**:
   ```bash
   doctl compute droplet-action restore DROPLET_ID --image-id BACKUP_ID
   ```

## Resources

- **Main README**: [README.md](../../README.md)
- **Deployment Guide**: [docs/runbooks/deployment.md](../runbooks/deployment.md)
- **Disaster Recovery**: [docs/runbooks/disaster-recovery.md](../runbooks/disaster-recovery.md)
- **Contributing**: [CONTRIBUTING.md](../../CONTRIBUTING.md)
- **Changelog**: [CHANGELOG.md](../CHANGELOG.md)

## Getting Help

1. Check documentation in `docs/`
2. Review example configurations in `.example` files
3. Create GitHub issue
4. Contact infrastructure team

## Security Reminders

- ✅ Never commit secrets to Git
- ✅ Use environment variables for credentials
- ✅ Encrypt sensitive Ansible vars with `ansible-vault`
- ✅ Enable 2FA on all service accounts
- ✅ Review firewall rules regularly
- ✅ Keep dependencies updated

## Next Actions

1. [ ] Import existing resources into Terraform state
2. [ ] Set up Terraform Cloud backend
3. [ ] Configure GitHub Actions secrets
4. [ ] Test Ansible playbooks in staging
5. [ ] Review and customize configurations
6. [ ] Set up monitoring alerts
7. [ ] Schedule regular backup testing
8. [ ] Document any custom procedures

Welcome to Infrastructure as Code! 🚀
