# PAUSATF Ansible Configuration Management

Comprehensive Ansible playbooks and roles for managing PAUSATF WordPress infrastructure across production, staging, and development environments.

## Table of Contents

- [Infrastructure Overview](#infrastructure-overview)
- [Environment Inventory](#environment-inventory)
- [Quick Start](#quick-start)
- [Playbooks](#playbooks)
- [Roles](#roles)
- [Operations](#operations)
- [Security](#security)
- [Troubleshooting](#troubleshooting)

## Infrastructure Overview

| Component | Production | Staging | Development |
|-----------|------------|---------|-------------|
| **Provider** | DigitalOcean | DigitalOcean | Local Docker |
| **Hostname** | ftp.pausatf.org | stage.pausatf.org | dev.pausatf.org |
| **IP Address** | (GitHub Secret) | (GitHub Secret) | localhost |
| **Droplet ID** | (GitHub Secret) | (GitHub Secret) | N/A |
| **Size** | 8GB / 4 vCPU | 4GB / 2 vCPU | N/A |
| **Web Server** | Apache 2.4 | OpenLiteSpeed 1.8 | OpenLiteSpeed |
| **PHP** | 7.4 | 8.4 | 8.4 |
| **WordPress** | 6.9 | 6.9 | 6.9 |
| **Document Root** | /var/www/html | /var/www/html | /var/www/html |
| **Legacy Path** | /var/www/legacy | N/A | N/A |
| **SSH User** | deploy | root | N/A |

## Environment Inventory

### Production Environment
```yaml
pausatf-prod:
  ansible_host: ftp.pausatf.org
  ansible_user: deploy                    # Non-root deploy user
  ansible_ssh_private_key_file: ~/.ssh/pausatf-prod
  ansible_python_interpreter: /usr/bin/python3
  wordpress_path: /var/www/html
  legacy_path: /var/www/legacy/public_html
  web_server: apache
  php_version: "7.4"
```

**Production Access:**
- SSH: `ssh -i ~/.ssh/pausatf-prod deploy@ftp.pausatf.org`
- Deploy user is member of `www-data` and `sudo` groups
- WordPress operations require `sg www-data -c "wp ..."` wrapper
- Read-only operations for inventory capture

### Staging Environment
```yaml
pausatf-stage:
  ansible_host: stage.pausatf.org
  ansible_user: root
  ansible_python_interpreter: /usr/bin/python3
  wordpress_path: /var/www/html
  web_server: openlitespeed
  php_version: "8.4"
```

### Development Environment
```yaml
pausatf-dev:
  ansible_host: dev.pausatf.org
  ansible_user: root
  ansible_python_interpreter: /usr/bin/python3
  wordpress_path: /var/www/html
  web_server: openlitespeed
  php_version: "8.4"
```

## Required GitHub Secrets

Server IPs and sensitive configuration are stored as GitHub Secrets and must be
set as environment variables before running playbooks locally.

| Secret | Description |
|--------|-------------|
| `PROD_SERVER_IP` | Production droplet public IP |
| `DEV_SERVER_IP` | Development droplet public IP |
| `STAGE_SERVER_IP` | Staging droplet public IP |
| `PROD_NEW_SERVER_IP` | Migration target droplet IP |
| `DO_VPC_UUID` | DigitalOcean VPC UUID |
| `DO_PRIVATE_IP` | Droplet private/VPC IP |
| `ALLOWED_SSH_NETWORKS` | JSON array of SSH allowlist CIDRs |
| `FAIL2BAN_IGNOREIP` | Space-separated admin IPs for fail2ban |
| `WP_ADMIN_EMAIL` | WordPress admin email address |
| `SENDGRID_SUBDOMAIN` | SendGrid sending subdomain |

For local runs, export these from your environment or pass via `--extra-vars`:

```bash
export PROD_SERVER_IP="<ip>"
export DEV_SERVER_IP="<ip>"
export STAGE_SERVER_IP="<ip>"
```

## Quick Start

### Prerequisites

```bash
# Install Ansible
pip install ansible

# Or via Homebrew (macOS)
brew install ansible
```

### Test Connectivity

```bash
# Test all environments
ansible all -i inventory/hosts.yml -m ping

# Test specific environment
ansible production -i inventory/hosts.yml -m ping
```

### Run Playbooks

```bash
# Capture production WordPress inventory (read-only)
ansible-playbook -i inventory/hosts.yml \
  playbooks/capture-wp-inventory.yml -l production

# Create production migration backup
ansible-playbook -i inventory/hosts.yml \
  playbooks/migrate-prod.yml -l production

# Restore to new droplet
ansible-playbook -i inventory/hosts.yml \
  playbooks/restore-prod.yml -l production
```

## Playbooks

### Core Playbooks

| Playbook | Purpose | Target | Safe for Prod |
|----------|---------|--------|---------------|
| **site.yml** | Full infrastructure deployment | All | ⚠️ Review first |
| **wordpress.yml** | WordPress-specific configuration | All | ⚠️ Review first |
| **security.yml** | Security hardening tasks | All | ⚠️ Review first |

### Production Operations Playbooks

| Playbook | Purpose | Read-Only | Description |
|----------|---------|-----------|-------------|
| **capture-wp-inventory.yml** | Capture WordPress state | ✅ Yes | Captures plugins, themes, theme_mods from production |
| **migrate-prod.yml** | Create migration backup | ✅ Yes | Full production backup for migration |
| **restore-prod.yml** | Restore from backup | ❌ No | Restore backup to new droplet |

#### capture-wp-inventory.yml

**Purpose:** Capture current WordPress configuration from production

**What it captures:**
- All installed plugins (name, status, version, auto_update)
- All installed themes (name, status, version, auto_update)
- Active template and stylesheet
- Theme customizations (theme_mods for parent and child themes)
- Navigation menus (term_id, name, slug, locations, item count)
- Menu locations, custom CSS, sidebars, widgets
- WordPress users (username, email, roles, registration date, last login)
- User activity tracking (last login timestamps via Wordfence Login Security)
- Site configuration (URL, site name, admin email, permalink structure, language)
- URL rewriting configuration (rewrite rules count)

**Output:** `ansible/group_vars/production/wordpress.yml`

**Usage:**
```bash
ANSIBLE_HOST_KEY_CHECKING=false ansible-playbook \
  -i inventory/hosts.yml \
  playbooks/capture-wp-inventory.yml \
  -l production
```

**Current Inventory (as of 2025-12-28):**
- 24 plugins (accordions, jetpack, cloudflare, updraftplus, etc.)
- 3 themes (TheSource parent v4.8.13, TheSource-child active, twentytwentyfour)
- 3 navigation menus (Top_Menu with 47 items active, Bottom_Menu, Officials)
- Theme configuration: menus, custom CSS, sidebars, widgets
- 27 WordPress users (11 administrators, 16 editors)
  - 6 users never logged in
  - 6 users inactive (last login >1 year ago)
  - 15 users active (logged in within last year)
- Site: https://www.pausatf.org
- Permalink structure: /%postname%/ (pretty permalinks)
- 123 rewrite rules configured

#### migrate-prod.yml

**Purpose:** Create comprehensive backup for migration to new droplet

**What it captures:**
- ✅ Cron jobs (root, www-data, deploy users)
- ✅ Custom scripts (monitor_and_purge.sh, httpd-check.sh)
- ✅ Apache configuration (sites, modules, configs)
- ✅ WordPress database (compressed SQL export)
- ✅ WordPress directory (excludes cache)
- ✅ Legacy directory (with all hidden files)
- ✅ System info (packages, PHP/WP-CLI versions)
- ✅ Migration manifest with restoration steps

**Output:** `backups/migration-YYYYMMDD-HHMMSS/`

**Usage:**
```bash
ansible-playbook -i inventory/hosts.yml \
  playbooks/migrate-prod.yml \
  -l production
```

**Archive Contents:**
```
backups/migration-YYYYMMDD-HHMMSS/
├── MANIFEST.txt                      # Migration instructions
├── README.txt                        # Quick start guide
├── crontab-root.txt                  # Root crontab
├── crontab-www-data.txt             # www-data crontab
├── crontab-deploy.txt               # deploy crontab
├── cron.d.tar.gz                    # System cron.d directory
├── monitor_and_purge.sh             # Cloudflare purge script
├── httpd-check.sh                   # Apache health check
├── apache-sites-available.tar.gz    # Apache site configs
├── apache-sites-enabled.tar.gz      # Enabled sites
├── apache-conf-enabled.tar.gz       # Apache conf
├── apache-modules.txt               # Enabled modules list
├── wordpress.tar.gz                 # Full WordPress directory
├── wordpress-db.sql.gz              # Database export
├── legacy.tar.gz                    # Legacy directory
├── installed-packages.txt           # dpkg -l output
├── php-version.txt                  # PHP info
├── php-modules.txt                  # PHP modules
├── wp-cli-version.txt               # WP-CLI version
└── system-info.txt                  # OS and kernel info
```

#### restore-prod.yml

**Purpose:** Restore production backup to new droplet

**Prerequisites:**
- New Ubuntu 20.04+ droplet provisioned
- Update inventory with new droplet IP
- SSH access configured

**What it does:**
- ✅ Installs LAMP stack (Apache, MySQL, PHP)
- ✅ Restores WordPress files and sets permissions
- ✅ Creates database and imports data
- ✅ Restores legacy directory
- ✅ Restores Apache configuration
- ✅ Restores custom scripts
- ✅ Creates deploy user with proper groups
- ⚠️ Provides instructions for manual crontab restoration

**Usage:**
```bash
ansible-playbook -i inventory/hosts.yml \
  playbooks/restore-prod.yml \
  -l production
```

**Interactive prompts:**
1. Path to migration backup directory
2. Confirmation (must type 'yes')

## Roles

### Available Roles

| Role | Purpose | Production Status |
|------|---------|-------------------|
| **common** | Base system packages and configuration | ✅ Active |
| **apache** | Apache web server (production) | ✅ Active |
| **openlitespeed** | OpenLiteSpeed web server (staging/dev) | ✅ Active |
| **lsphp** | LiteSpeed PHP configuration | ✅ Active |
| **mysql** | MySQL database server | ✅ Active |
| **wordpress** | WordPress core installation | ✅ Active |
| **cloudflare** | Cloudflare DNS/CDN management | ✅ Active |
| **fail2ban** | Intrusion prevention | ✅ Active |
| **monitoring** | System monitoring | 🚧 Development |

## Operations

### WordPress Inventory Management

**Capture current state:**
```bash
ansible-playbook -i inventory/hosts.yml \
  playbooks/capture-wp-inventory.yml \
  -l production
```

**Review captured inventory:**
```bash
cat ansible/group_vars/production/wordpress.yml
```

**Commit changes:**
```bash
git add ansible/group_vars/production/wordpress.yml
git commit -m "Update WordPress inventory"
git push
```

### Production Migration

**Step 1: Create migration backup**
```bash
ansible-playbook -i inventory/hosts.yml \
  playbooks/migrate-prod.yml \
  -l production

# Review the backup
ls -lh backups/migration-*/
cat backups/migration-*/MANIFEST.txt
```

**Step 2: Provision new droplet**
```bash
# Via doctl
doctl compute droplet create pausatf-prod-new \
  --image ubuntu-22-04-x64 \
  --size s-4vcpu-8gb \
  --region sfo3 \
  --ssh-keys YOUR_SSH_KEY_ID

# Via Terraform (recommended)
cd terraform/environments/production
terraform plan
terraform apply
```

**Step 3: Update inventory**
```yaml
# ansible/inventory/hosts.yml
pausatf-prod:
  ansible_host: NEW_DROPLET_IP  # Update this
  ansible_user: root            # Initial setup as root
  # ... rest of config
```

**Step 4: Restore to new droplet**
```bash
ansible-playbook -i inventory/hosts.yml \
  playbooks/restore-prod.yml \
  -l production

# When prompted:
# - Enter path: backups/migration-YYYYMMDD-HHMMSS
# - Confirm: yes
```

**Step 5: Post-restoration**
```bash
# SSH to new droplet
ssh root@NEW_DROPLET_IP

# Restore crontabs (manual step)
crontab /tmp/pausatf-restore/crontab-root.txt
crontab -u www-data /tmp/pausatf-restore/crontab-www-data.txt

# Test WordPress
curl -I http://NEW_DROPLET_IP/

# Update DNS when ready
```

### WordPress Operations (Read-Only)

**List plugins:**
```bash
ssh -i ~/.ssh/pausatf-prod deploy@ftp.pausatf.org \
  'sg www-data -c "wp plugin list --path=/var/www/html"'
```

**List themes:**
```bash
ssh -i ~/.ssh/pausatf-prod deploy@ftp.pausatf.org \
  'sg www-data -c "wp theme list --path=/var/www/html"'
```

**Check WordPress version:**
```bash
ssh -i ~/.ssh/pausatf-prod deploy@ftp.pausatf.org \
  'sg www-data -c "wp core version --path=/var/www/html"'
```

**Get site info:**
```bash
ssh -i ~/.ssh/pausatf-prod deploy@ftp.pausatf.org \
  'sg www-data -c "wp option get home --path=/var/www/html"'
```

## Security

### SSH Access

**Production access via deploy user:**
- SSH key: `~/.ssh/pausatf-prod`
- User: `deploy` (member of www-data, sudo)
- Key stored in GitHub secret: `PROD_SSH_PRIVATE_KEY`

**Key generation (reference):**
```bash
# Already created - do not regenerate
ssh-keygen -t ed25519 -C "pausatf-prod-20251228" \
  -f ~/.ssh/pausatf-prod -N ""
```

**Add key to server:**
```bash
ssh-copy-id -i ~/.ssh/pausatf-prod.pub deploy@ftp.pausatf.org
```

### Ansible Vault

**Encrypt secrets:**
```bash
ansible-vault encrypt group_vars/vault.yml
```

**Edit encrypted file:**
```bash
ansible-vault edit group_vars/vault.yml
```

**Run playbook with vault:**
```bash
ansible-playbook -i inventory/hosts.yml site.yml --ask-vault-pass
```

### Production Safety

**Read-only operations:**
- ✅ `capture-wp-inventory.yml` - Safe, no changes
- ✅ `migrate-prod.yml` - Safe, creates backup only

**Write operations:**
- ⚠️ `restore-prod.yml` - Destructive, requires confirmation
- ⚠️ `site.yml` - Full deployment, review before running

**Best practices:**
1. Always use `--check --diff` for dry runs
2. Test playbooks on staging first
3. Create backups before making changes
4. Use tags to limit scope: `--tags wordpress`
5. Limit to specific hosts: `-l production`

## Automated Backups

### Nightly Inventory Capture

**GitHub Action:** `.github/workflows/capture-prod-inventory.yml`

Runs nightly to capture WordPress plugins/themes/config and commit to repo.

### Nightly Legacy Backup

**GitHub Action:** `.github/workflows/backup-legacy.yml`

Rsyncs `/var/www/legacy` to `backups/legacy/` in this repo nightly.

### DigitalOcean Snapshots

**GitHub Action:** `.github/workflows/do-nightly-snapshot.yml`

Creates nightly droplet snapshots with timestamp.

**Required GitHub Secrets:**
- `PROD_SSH_PRIVATE_KEY` - Deploy user SSH key
- `DO_TOKEN` - DigitalOcean API token
- `DO_PROD_DROPLET_ID` - Production droplet ID (REDACTED_DROPLET_ID)

## Troubleshooting

### Common Issues

**Permission denied for deploy user:**
```bash
# Verify deploy user in www-data group
ssh root@ftp.pausatf.org 'id deploy'

# If not, add to group
ssh root@ftp.pausatf.org 'usermod -a -G www-data deploy'
```

**WP-CLI fails with permission error:**
```bash
# Always use sg wrapper for deploy user
sg www-data -c "wp plugin list --path=/var/www/html"
```

**Ansible Python version error (prod):**
```bash
# Production uses Python 3.8, Ansible requires 3.9+
# Solution: Use raw module in playbooks (already configured)
```

**Database connection error:**
```bash
# Check MySQL is running
ssh root@ftp.pausatf.org 'systemctl status mysql'

# Check wp-config.php credentials
ssh deploy@ftp.pausatf.org 'grep DB_ /var/www/html/wp-config.php'
```

### Debug Mode

**Enable verbose output:**
```bash
ansible-playbook -vvv -i inventory/hosts.yml playbooks/capture-wp-inventory.yml
```

**Check mode (dry run):**
```bash
ansible-playbook --check --diff -i inventory/hosts.yml site.yml
```

## Directory Structure

```
ansible/
├── README.md                     # This file
├── ansible.cfg                   # Ansible configuration
├── inventory/
│   └── hosts.yml                # Environment inventory
├── group_vars/
│   ├── all.yml                  # Global variables
│   ├── production/
│   │   └── wordpress.yml        # Captured WordPress state
│   ├── staging.yml              # Staging variables
│   └── vault.yml                # Encrypted secrets (use ansible-vault)
├── playbooks/
│   ├── site.yml                 # Main deployment playbook
│   ├── wordpress.yml            # WordPress deployment
│   ├── capture-wp-inventory.yml # Capture WP state (read-only)
│   ├── migrate-prod.yml         # Create migration backup
│   └── restore-prod.yml         # Restore from backup
└── roles/
    ├── common/                  # Base system configuration
    ├── apache/                  # Apache web server
    ├── openlitespeed/          # OpenLiteSpeed web server
    ├── lsphp/                  # LiteSpeed PHP
    ├── mysql/                  # MySQL database
    ├── wordpress/              # WordPress application
    ├── cloudflare/             # Cloudflare integration
    └── fail2ban/               # Security
```

## Resources

### Documentation
- [Ansible Documentation](https://docs.ansible.com/)
- [WordPress CLI](https://wp-cli.org/)
- [DigitalOcean API](https://docs.digitalocean.com/reference/api/)
- [Cloudflare API](https://developers.cloudflare.com/api/)

### Internal Docs
- [Production Operations Guide](../docs/runbooks/production-operations.md)
- [Deployment Runbook](../docs/runbooks/deployment.md)
- [Disaster Recovery](../docs/runbooks/disaster-recovery.md)
- [Migration Guide](../docs/MIGRATION.md)

## Support

For issues with Ansible playbooks:
- Open an issue in the monorepo
- Review the [troubleshooting guide](#troubleshooting)
- Check recent commits to `ansible/group_vars/production/wordpress.yml`

---

**Last Updated:** December 28, 2025
**Maintained by:** Thomas Vincent
**Production Status:** Active (Ubuntu 20.04, Apache 2.4, PHP 7.4, WordPress 6.9)
