# Production Operations Runbook

**Document Version:** 1.0
**Last Updated:** December 28, 2025
**Environment:** Production (pausatf.org)
**Maintained By:** Thomas Vincent

## Table of Contents

- [Overview](#overview)
- [Production Environment](#production-environment)
- [Access and Authentication](#access-and-authentication)
- [Daily Operations](#daily-operations)
- [WordPress Management](#wordpress-management)
- [Backup and Recovery](#backup-and-recovery)
- [Monitoring and Health Checks](#monitoring-and-health-checks)
- [Incident Response](#incident-response)
- [Troubleshooting](#troubleshooting)
- [Emergency Contacts](#emergency-contacts)

## Overview

This runbook provides operational procedures for the PAUSATF production WordPress environment. All operations should be performed with caution and follow the principle of least privilege.

### Operational Principles

1. **Read-Only First**: Always use read-only commands for inspection
2. **Test on Staging**: Test all changes on staging before production
3. **Backup Before Changes**: Create backups before any modifications
4. **Document Everything**: Log all changes in GitHub issues/commits
5. **Use Deploy User**: Never use root for routine operations

## Production Environment

### Infrastructure Details

| Component | Value |
|-----------|-------|
| **Provider** | DigitalOcean |
| **Droplet ID** | 355909945 |
| **Hostname** | ftp.pausatf.org |
| **IP Address** | 64.225.40.54 |
| **Size** | 8GB RAM / 4 vCPU / 160GB SSD |
| **Region** | San Francisco 2 (sfo2) |
| **OS** | Ubuntu 20.04.6 LTS |
| **Web Server** | Apache 2.4.41 |
| **PHP** | 7.4.33 |
| **MySQL** | 5.7 |
| **WordPress** | 6.9 |

### Directory Structure

```
Production Server Layout:
/var/www/
├── html/                    # WordPress installation
│   ├── wp-admin/
│   ├── wp-content/
│   │   ├── plugins/         # 24 active plugins
│   │   ├── themes/          # TheSource + TheSource-child
│   │   └── uploads/         # Media library
│   ├── wp-config.php        # WordPress configuration
│   └── index.php
└── legacy/                  # Legacy static site (2000-2025)
    └── public_html/         # Historical content, manually updated
```

### Current WordPress Configuration

**Active Theme:** TheSource-child (v0.2)
**Parent Theme:** TheSource (v4.8.13)

**Active Plugins (24 total):**
- accordions (2.3.19)
- advanced-database-cleaner (4.0.4)
- app-for-cf (1.9.8)
- category-posts (4.9.22)
- classic-editor (1.6.7)
- cloudflare (4.14.2)
- contact-form-7 (6.1.4)
- jetpack (15.3.1)
- media-library-plus (8.3.6)
- ml-slider (3.104.0)
- google-site-kit (1.168.0)
- tablepress-premium (3.2.6)
- updraftplus (2.25.9.0)
- version-info (1.3.3)
- widget-logic (6.02)
- widget-options (4.1.3)
- wp-file-manager-pro (8.4.3)
- wpforms-lite (1.9.8.7)
- wp-mail-smtp (4.7.1)
- wp-super-cache (3.0.3)
- fix-translations (1.0) [must-use]
- wp-fail2ban (3.5.3) [must-use]
- advanced-cache.php [dropin]

## Access and Authentication

### SSH Access

**Deploy User (Recommended for Operations):**
```bash
# Connect as deploy user
ssh -i ~/.ssh/pausatf-prod deploy@ftp.pausatf.org

# Deploy user permissions:
# - Member of: deploy, www-data, sudo
# - Can read WordPress files
# - Can run WP-CLI commands via sg wrapper
# - Can sudo for system operations
```

**Root User (Emergency Only):**
```bash
# Connect as root (use sparingly)
ssh root@ftp.pausatf.org

# Root access should only be used for:
# - Emergency system recovery
# - User account management
# - System package updates
```

### SSH Key Management

**Production SSH Key:**
- **Location:** `~/.ssh/pausatf-prod`
- **Type:** ED25519
- **User:** deploy
- **GitHub Secret:** `PROD_SSH_PRIVATE_KEY`

**Key Security:**
- Never commit private keys to git
- Store passphrase in secure password manager
- Rotate keys annually or after personnel changes

### WordPress Operations Wrapper

Due to file permissions, all WP-CLI commands must use the `sg` wrapper:

```bash
# CORRECT - Use sg wrapper
ssh deploy@ftp.pausatf.org 'sg www-data -c "wp plugin list --path=/var/www/html"'

# INCORRECT - Will fail with permission error
ssh deploy@ftp.pausatf.org 'wp plugin list --path=/var/www/html'
```

## Daily Operations

### Health Check Routine

**Daily Morning Checklist:**

```bash
# 1. Check WordPress site is responding
curl -I https://www.pausatf.org/ | grep "HTTP/2 200"

# 2. Check SSL certificate validity
curl -vI https://www.pausatf.org/ 2>&1 | grep "expire date"

# 3. Check disk space
ssh deploy@ftp.pausatf.org 'df -h /var/www'

# 4. Check system load
ssh deploy@ftp.pausatf.org 'uptime'

# 5. Check for failed services
ssh deploy@ftp.pausatf.org 'sudo systemctl --failed'

# 6. Review recent WordPress errors
ssh deploy@ftp.pausatf.org 'tail -20 /var/www/html/wp-content/debug.log'
```

### Automated Monitoring

**Nightly Automated Tasks:**

1. **WordPress Inventory Capture** (2:00 AM PT)
   - Workflow: `.github/workflows/capture-prod-inventory.yml`
   - Captures: plugins, themes, theme_mods
   - Output: Commits to `ansible/group_vars/production/wordpress.yml`

2. **Legacy Directory Backup** (3:00 AM PT)
   - Workflow: `.github/workflows/backup-legacy.yml`
   - Syncs: `/var/www/legacy` → `backups/legacy/` in git
   - Frequency: Daily

3. **DigitalOcean Snapshot** (2:00 AM PT)
   - Workflow: `.github/workflows/do-nightly-snapshot.yml`
   - Creates: Full droplet snapshot
   - Naming: `prod-nightly-YYYYMMDD-HHMMSS`
   - Retention: Managed by DigitalOcean (7 days)

### Cron Jobs

**Root crontab:**
```cron
# WordPress cron
* * * * * wget -q -O - https://www.pausatf.org/wp-cron.php?doing_wp_cron > /dev/null 2>&1

# Apache health check
*/30 * * * * /etc/cron.d/httpd-check.sh
```

**www-data crontab:**
```cron
# Cloudflare cache monitoring
* * * * * /usr/local/bin/monitor_and_purge.sh >> /var/log/cloudflare_purge.log 2>&1
```

## WordPress Management

### Plugin Operations

**List all plugins (read-only):**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp plugin list --path=/var/www/html"'
```

**Check for plugin updates:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp plugin list --update=available --path=/var/www/html"'
```

**Get plugin info:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp plugin get jetpack --path=/var/www/html"'
```

### Theme Operations

**List all themes:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp theme list --path=/var/www/html"'
```

**Get active theme:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp theme status TheSource-child --path=/var/www/html"'
```

### WordPress Core Operations

**Check WordPress version:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp core version --path=/var/www/html"'
```

**Check for WordPress updates:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp core check-update --path=/var/www/html"'
```

**WordPress health check:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp site health --path=/var/www/html"'
```

### Database Operations

**Database info:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp db size --path=/var/www/html"'
```

**Table count:**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp db query \"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()\" --path=/var/www/html"'
```

**Export database (backup):**
```bash
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp db export /tmp/wp-backup-$(date +%Y%m%d).sql --path=/var/www/html"'
```

## Backup and Recovery

### Inventory Capture (Automated)

**Manual trigger of nightly capture:**
```bash
# Via GitHub CLI
gh workflow run capture-prod-inventory.yml

# Via Ansible playbook
cd /path/to/pausatf
ansible-playbook -i ansible/inventory/hosts.yml \
  ansible/playbooks/capture-wp-inventory.yml \
  -l production
```

**Review captured inventory:**
```bash
cat ansible/group_vars/production/wordpress.yml
```

### Full Migration Backup

**Create comprehensive migration backup:**
```bash
cd /path/to/pausatf
ansible-playbook -i ansible/inventory/hosts.yml \
  ansible/playbooks/migrate-prod.yml \
  -l production
```

**Backup includes:**
- WordPress files and database
- Legacy directory
- Apache configuration
- Cron jobs
- Custom scripts
- System information

**Backup location:**
```
backups/migration-YYYYMMDD-HHMMSS/
```

**Review backup:**
```bash
ls -lh backups/migration-*/
cat backups/migration-*/MANIFEST.txt
```

### DigitalOcean Snapshots

**Create manual snapshot:**
```bash
# Via doctl
doctl compute droplet-action snapshot 355909945 \
  --snapshot-name "pre-change-$(date +%Y%m%d-%H%M%S)"

# Via GitHub workflow
gh workflow run do-nightly-snapshot.yml
```

**List available snapshots:**
```bash
doctl compute droplet-action list 355909945 | grep snapshot
```

**Restore from snapshot:**
```bash
# 1. Create new droplet from snapshot
doctl compute droplet create pausatf-prod-restore \
  --image SNAPSHOT_ID \
  --size s-4vcpu-8gb \
  --region sfo3

# 2. Test the restored droplet
# 3. Update DNS if needed
```

## Monitoring and Health Checks

### Uptime Monitoring

**Cloudflare Analytics:**
- Dashboard: https://dash.cloudflare.com/
- Zone: pausatf.org (67b87131144a68ad5ed43ebfd4e6d811)
- Metrics: Traffic, bandwidth, threats, performance

**WordPress Health:**
```bash
# Check WordPress Site Health
# Admin → Tools → Site Health
# Or via WP-CLI:
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp site health --path=/var/www/html"'
```

### Resource Monitoring

**Current utilization (as of 2025-12-28):**
- RAM: 1.6 GB / 8 GB (20%)
- Disk: 39 GB / 160 GB (26%)
- Load: Low (under-provisioned)

**Check current usage:**
```bash
# Memory
ssh deploy@ftp.pausatf.org 'free -h'

# Disk
ssh deploy@ftp.pausatf.org 'df -h'

# Load average
ssh deploy@ftp.pausatf.org 'uptime'

# Top processes
ssh deploy@ftp.pausatf.org 'top -bn1 | head -20'
```

### Log Monitoring

**Apache logs:**
```bash
# Error log
ssh deploy@ftp.pausatf.org 'sudo tail -f /var/log/apache2/error.log'

# Access log
ssh deploy@ftp.pausatf.org 'sudo tail -f /var/log/apache2/access.log'
```

**WordPress debug log:**
```bash
ssh deploy@ftp.pausatf.org 'tail -f /var/www/html/wp-content/debug.log'
```

**MySQL slow query log:**
```bash
ssh deploy@ftp.pausatf.org 'sudo tail -f /var/log/mysql/slow-queries.log'
```

## Incident Response

### Site Down Response

**Step 1: Verify the issue**
```bash
# Check from external location
curl -I https://www.pausatf.org/

# Check CloudFlare status
# https://www.cloudflarestatus.com/
```

**Step 2: Check server status**
```bash
# SSH connectivity
ssh deploy@ftp.pausatf.org 'echo "Server reachable"'

# Apache status
ssh deploy@ftp.pausatf.org 'sudo systemctl status apache2'

# MySQL status
ssh deploy@ftp.pausatf.org 'sudo systemctl status mysql'
```

**Step 3: Review recent changes**
```bash
# Check recent WordPress updates
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp core version --extra --path=/var/www/html"'

# Check Apache config test
ssh deploy@ftp.pausatf.org 'sudo apache2ctl configtest'

# Review error logs
ssh deploy@ftp.pausatf.org 'sudo tail -100 /var/log/apache2/error.log'
```

**Step 4: Restart services (if needed)**
```bash
# Restart Apache
ssh deploy@ftp.pausatf.org 'sudo systemctl restart apache2'

# Restart MySQL
ssh deploy@ftp.pausatf.org 'sudo systemctl restart mysql'

# Clear WordPress cache
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp cache flush --path=/var/www/html"'
```

### Performance Degradation

**Quick diagnostics:**
```bash
# Check load
ssh deploy@ftp.pausatf.org 'uptime'

# Check memory
ssh deploy@ftp.pausatf.org 'free -m'

# Check disk I/O
ssh deploy@ftp.pausatf.org 'iostat -x 1 5'

# Check database queries
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp db query \"SHOW PROCESSLIST\" --path=/var/www/html"'
```

**Clear caches:**
```bash
# WordPress object cache
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp cache flush --path=/var/www/html"'

# WP Super Cache
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp super-cache flush --path=/var/www/html"'

# Cloudflare cache
curl -X POST "https://api.cloudflare.com/client/v4/zones/67b87131144a68ad5ed43ebfd4e6d811/purge_cache" \
  -H "Authorization: Bearer YOUR_CF_TOKEN" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

### Security Incident

**Step 1: Isolate**
```bash
# Enable maintenance mode
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp maintenance-mode activate --path=/var/www/html"'
```

**Step 2: Assess**
```bash
# Check for file modifications
ssh deploy@ftp.pausatf.org \
  'find /var/www/html -type f -mtime -1 -ls'

# Check for suspicious users
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp user list --path=/var/www/html"'

# Check for suspicious plugins
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp plugin list --status=inactive --path=/var/www/html"'
```

**Step 3: Contact security team**
- See [Emergency Contacts](#emergency-contacts)
- Document all findings
- Preserve logs and evidence

## Troubleshooting

### Common Issues

**Issue: Permission denied for deploy user**
```bash
# Verify group membership
ssh root@ftp.pausatf.org 'id deploy'

# Should show: groups=1000(deploy),27(sudo),33(www-data)

# If missing www-data, add it:
ssh root@ftp.pausatf.org 'usermod -a -G www-data deploy'
```

**Issue: WP-CLI permission error**
```bash
# Always wrap WP-CLI in sg
sg www-data -c "wp plugin list --path=/var/www/html"
```

**Issue: WordPress white screen**
```bash
# Enable debug mode
ssh deploy@ftp.pausatf.org \
  'echo "define(\"WP_DEBUG\", true);" | sudo tee -a /var/www/html/wp-config.php'

# Check debug log
ssh deploy@ftp.pausatf.org 'tail -f /var/www/html/wp-content/debug.log'

# Disable debug when done
ssh deploy@ftp.pausatf.org \
  'sudo sed -i "/WP_DEBUG/d" /var/www/html/wp-config.php'
```

**Issue: Database connection error**
```bash
# Test database connection
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp db check --path=/var/www/html"'

# Verify MySQL is running
ssh deploy@ftp.pausatf.org 'sudo systemctl status mysql'

# Check credentials in wp-config.php
ssh deploy@ftp.pausatf.org 'sudo grep "DB_" /var/www/html/wp-config.php'
```

### Escalation Path

**Level 1: Self-service**
- Check this runbook
- Review logs
- Search documentation

**Level 2: Team review**
- Create GitHub issue
- Tag @thomasvincent
- Include relevant logs

**Level 3: Emergency**
- Contact emergency contact
- Create DigitalOcean support ticket
- Document all actions

## Emergency Contacts

**Primary Contact:**
- Name: Thomas Vincent
- GitHub: @thomasvincent
- For: All infrastructure issues

**Service Providers:**

**DigitalOcean Support:**
- Dashboard: https://cloud.digitalocean.com/support
- Droplet ID: 355909945

**Cloudflare Support:**
- Dashboard: https://dash.cloudflare.com/support
- Zone ID: 67b87131144a68ad5ed43ebfd4e6d811

**PAUSATF Organization:**
- Website: https://www.pausatf.org
- Emergency: (See website contact page)

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-12-28 | Thomas Vincent | Initial production operations runbook |

## Related Documentation

- [Ansible README](../../ansible/README.md)
- [Deployment Runbook](deployment.md)
- [Disaster Recovery Runbook](disaster-recovery.md)
- [Migration Guide](../MIGRATION.md)
- [GitHub Secrets](../.github/SECRETS.md)

---

**Document Classification:** Internal
**Review Schedule:** Quarterly
**Next Review:** March 2026
