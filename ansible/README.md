# PAUSATF WordPress Server Ansible Playbook

Complete Ansible playbook for managing the PAUSATF WordPress server infrastructure.

## Infrastructure Overview

| Component | Details |
|-----------|---------|
| **Hosting** | DigitalOcean |
| **Region** | San Francisco 2 (sfo2) |
| **Droplet** | pausatforg20230516-primary |
| **Size** | s-4vcpu-8gb (4 vCPU, 8GB RAM, 160GB disk) |
| **IP** | 64.225.40.54 |
| **CDN/DNS** | Cloudflare (Free plan) |
| **OS** | Ubuntu 20.04.6 LTS (Focal Fossa) |

## Software Stack

| Component | Version |
|-----------|---------|
| Apache | 2.4.63 |
| PHP (active) | 7.2 |
| PHP (available) | 7.4, 8.1, 8.4 |
| MySQL | 5.7.42 |
| WordPress (main) | 6.8.3 |

## WordPress Sites

| Site | Domain | Path | Status |
|------|--------|------|--------|
| Main | www.pausatf.org | /var/www/html | Production |
| Transit | - | /var/www/transit/html | Legacy (WP 5.0.3) |

## Cloudflare Configuration

| Setting | Value |
|---------|-------|
| Zone | pausatf.org |
| SSL Mode | Full |
| Min TLS | 1.2 |
| Always HTTPS | Enabled |
| Security Level | Medium |
| Cache Level | Aggressive |

### DNS Records (Proxied)
- `pausatf.org` → 64.225.40.54 (proxied)
- `www.pausatf.org` → 64.225.40.54 (proxied)
- `ftp.pausatf.org` → 64.225.40.54 (direct)
- `mail.pausatf.org` → 64.225.40.54 (direct)

### Email (Google Workspace)
- MX records pointing to Google (aspmx.l.google.com)
- SendGrid for transactional email

## Quick Start

```bash
# Install Ansible
pip install ansible

# Test connectivity
ansible all -i inventory -m ping

# Dry run (check mode)
ansible-playbook -i inventory site.yml --check --diff

# Full deployment
ansible-playbook -i inventory site.yml

# Deploy specific components
ansible-playbook -i inventory site.yml --tags apache
ansible-playbook -i inventory site.yml --tags php
ansible-playbook -i inventory site.yml --tags mysql
ansible-playbook -i inventory site.yml --tags wordpress
ansible-playbook -i inventory site.yml --tags cloudflare
ansible-playbook -i inventory site.yml --tags fail2ban
```

## Directory Structure

```
pausatf-ansible/
├── ansible.cfg           # Ansible configuration
├── inventory             # Server inventory
├── site.yml              # Main playbook
├── group_vars/
│   ├── all.yml          # Global variables (infrastructure, config)
│   └── vault.yml        # Secrets (credentials, API tokens)
└── roles/
    ├── common/          # Base system packages
    ├── apache/          # Apache web server
    ├── php/             # PHP configuration
    ├── mysql/           # MySQL database
    ├── wordpress/       # WordPress installation
    ├── cloudflare/      # Cloudflare CDN/DNS management
    ├── fail2ban/        # Intrusion prevention
    ├── newrelic/        # APM monitoring
    └── monitoring/      # Monit + backups
```

## Credentials & Secrets

All credentials are stored in `group_vars/vault.yml`:

| Secret | Description |
|--------|-------------|
| `vault_mysql_root_password` | MySQL root password |
| `vault_wp_main_db_password` | WordPress DB password |
| `vault_digitalocean_api_token` | DigitalOcean API token |
| `vault_cloudflare_api_token` | Cloudflare API token |
| `vault_cloudflare_zone_id` | Cloudflare zone ID |
| `vault_wp_main_*` | WordPress auth keys/salts |

### Encrypting the Vault

```bash
# Encrypt
ansible-vault encrypt group_vars/vault.yml

# Edit encrypted file
ansible-vault edit group_vars/vault.yml

# Run playbook with vault
ansible-playbook -i inventory site.yml --ask-vault-pass
```

## Available Tags

| Tag | Description |
|-----|-------------|
| `common` | Base packages and system config |
| `apache` | Apache web server |
| `php` | PHP installation and config |
| `mysql` | MySQL database |
| `wordpress` | WordPress deployment |
| `cloudflare` | Cloudflare CDN/DNS settings |
| `fail2ban` | Intrusion prevention |
| `newrelic` | New Relic APM |
| `monitoring` | Monit and backup scripts |
| `healthcheck` | Health check endpoint only |
| `security` | All security-related tasks |
| `web` | Apache + PHP |
| `verify` | Verify services are running |

## Maintenance Tasks

### WordPress CLI (WP-CLI)
```bash
# Check WordPress version
sudo -u www-data wp --path=/var/www/html core version

# List plugins with update status
sudo -u www-data wp --path=/var/www/html plugin list

# List themes
sudo -u www-data wp --path=/var/www/html theme list

# Check for available updates
sudo -u www-data wp --path=/var/www/html plugin list --update=available
sudo -u www-data wp --path=/var/www/html theme list --update=available

# Manual update all plugins
sudo -u www-data wp --path=/var/www/html plugin update --all

# Manual update all themes
sudo -u www-data wp --path=/var/www/html theme update --all
```

### WordPress Auto-Updates
Automatic security updates are scheduled weekly via cron:
```bash
# Manual run of auto-update script
/usr/local/bin/wp-update-pausatf-main.sh

# View update log
cat /var/log/wordpress-updates.log

# Check scheduled cron jobs
crontab -l | grep wp-update
```

**Excluded from auto-updates** (premium/custom):
- Plugins: `tablepress-premium`, `wp-file-manager-pro`
- Themes: `TheSource-child`, custom OceanWP children

### Backup
```bash
# Manual backup
/usr/local/bin/backup-wordpress.sh

# View logs
cat /var/log/wordpress-backup.log
```

### Fail2ban
```bash
sudo fail2ban-client status
sudo fail2ban-client status wordpress
```

### Cloudflare Cache Purge
```bash
# Via Ansible
ansible-playbook -i inventory site.yml --tags cloudflare-purge -e "cloudflare_purge_cache=true"

# Via API
curl -X POST "https://api.cloudflare.com/client/v4/zones/ZONE_ID/purge_cache" \
  -H "Authorization: Bearer API_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

### Cloudflare IP Updates
Cloudflare IP ranges are updated automatically via weekly cron job:
```bash
# Manual update
/usr/local/bin/update-cloudflare-ips.sh

# View update log
cat /var/log/cloudflare-ip-update.log

# Verify current IPs
cat /etc/apache2/conf-available/cloudflare-ips.conf
```

### Health Check Endpoint
The health check endpoint monitors PHP, MySQL, WordPress, disk space, and system load:
```bash
# Local check
curl http://localhost/health-check.php

# With token (from external monitoring)
curl "https://www.pausatf.org/health-check.php?token=YOUR_TOKEN"

# Response (200 = healthy, 503 = unhealthy)
{
  "status": "healthy",
  "timestamp": "2025-12-06T12:00:00-08:00",
  "checks": {
    "php": {"status": "healthy", "version": "7.4.33"},
    "mysql": {"status": "healthy", "connection": "ok"},
    "wordpress": {"status": "healthy", "version": "6.8.3"},
    "disk": {"status": "healthy", "used_percent": 45.2},
    "load": {"status": "healthy", "1min": 0.5}
  }
}
```

Configure uptime monitoring services (UptimeRobot, Pingdom, etc.) to poll this endpoint.

## Known Issues & Recommendations

### Current Issues
1. **PHP 7.2** is outdated - consider upgrading to 7.4 or 8.x
2. **UFW firewall** is currently disabled
3. **Transit site** runs very old WordPress 5.0.3
4. **Legacy content** in /var/www/legacy needs review

### Security Recommendations
1. Enable UFW firewall
2. Upgrade PHP to 7.4 or 8.x
3. Update WordPress on transit site
4. Review `.php.suspicious` files
5. Consider upgrading Cloudflare SSL to Full (Strict)
6. Enable Cloudflare WAF (requires paid plan)

## DigitalOcean Management

### Via doctl CLI
```bash
# Get droplet info
doctl compute droplet get 355909945

# Create snapshot
doctl compute droplet-action snapshot 355909945 --snapshot-name "pre-upgrade-$(date +%Y%m%d)"

# List backups
doctl compute droplet backups 355909945
```

### Via API
```bash
# Get droplet
curl -X GET "https://api.digitalocean.com/v2/droplets/355909945" \
  -H "Authorization: Bearer $DO_TOKEN"
```

## Support

For issues with this playbook, review the audit data or contact the infrastructure team.

---
Generated from server audit on 2025-12-06
