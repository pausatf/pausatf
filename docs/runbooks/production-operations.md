# Production Operations Runbook

For the October 1 merged changes, follow the [deployment gates](deployment.md),
[MySQL upgrade](database-upgrades.md), [TLS ownership](tls-certificates.md) and
[recovery backup](../../scripts/backup/README.md) runbooks. Installed production behavior and recoverability
require separate evidence.

## Current production (verified 2026-11-10)

Observed read-only on 2026-11-10 with `doctl` and an SSH session on the server.
Re-check before relying on any value; nothing below was changed to collect it.

| Component | Value |
|---|---|
| Droplet | `pausatf-prod-v2` (ID `586316413`), `s-2vcpu-4gb`, SFO2, Ubuntu 26.04.1 LTS |
| SSH | `ssh-v2.pausatf.org` through Cloudflare Access (`ProxyCommand cloudflared access ssh --hostname %h`), user `somethingwithproof` with sudo. The droplet's public IP does not accept SSH or direct HTTP. |
| Stack | Docker Compose in `~/pausatf-deployment` using `docker-compose.prod.yml`. That directory is a deployed copy, not a git checkout; its source is `pausatf/pausatf-deployment`. |
| Containers | `pausatf-wordpress` (Apache, WordPress 7.1.4), `pausatf-redis`, `pausatf-mail`. There is no database container. |
| Database | Managed `pausatf-prod-db`, MySQL 8.4 (server reports 8.4.11), `db-s-1vcpu-1gb`, one node, reached over its private host. Its only trusted source is the production droplet. Maintenance window: Sunday 02:00 UTC. |
| Credentials | Passed to the container as `WORDPRESS_DB_*` from the root-only `.env` in the compose directory. Never print them. |
| Content | WordPress core in the `pausatf_prod_wordpress_core` volume at `/var/www/html`. `wp-content` is bind-mounted from the compose directory, and uploads come from the s3fs Spaces mount `/mnt/spaces-uploads`. |
| Logs | `~/pausatf-deployment/logs/apache2` and `logs/modsec`, plus `sudo docker logs pausatf-wordpress` |
| Scheduled work | `/etc/cron.d/pausatf-wpcron` runs `wp-cron.php` in the container every 5 minutes. systemd timers: `pausatf-db-backup` (runs `/usr/local/sbin/pausatf-recovery-backup`), `pausatf-media-backup`, `pausatf-backup-audit`, `pausatf-health`, `pausatf-inventory`, `pausatf-usage`, `certbot`. On 2026-11-10 the last run of each backup, audit and health unit had succeeded. |
| Snapshots | GitHub workflow `do-nightly-snapshot.yml` (seven retained). Native droplet backups are off. |

The container has no WP-CLI. Read-only checks:

```bash
ssh pausatf-prod-v2            # an ~/.ssh/config Host for ssh-v2.pausatf.org as above
sudo docker ps --format '{{.Names}} {{.Image}} {{.Status}}'
systemctl list-timers --all 'pausatf-*'
sudo docker logs --since 1h pausatf-wordpress
```

To confirm which database WordPress uses without printing credentials, pipe a
script to PHP in the container from your workstation:

```bash
cat <<'PHP' | ssh pausatf-prod-v2 'sudo docker exec -i pausatf-wordpress php'
<?php
$_SERVER['HTTP_HOST'] = 'www.pausatf.org';
define('SHORTINIT', true);
require '/var/www/html/wp-load.php';
echo preg_replace('/:.*/', '', DB_HOST), ' ', $GLOBALS['wpdb']->get_var('SELECT VERSION()'), PHP_EOL;
PHP
```

On 2026-11-10 it printed the cluster's private hostname and `8.4.11`.

A host `mysql` service check does not apply; database health belongs to the
managed cluster (`doctl databases get <id>`, `doctl databases events list <id>`).
Use the [deployment gates](deployment.md), [TLS ownership](tls-certificates.md)
and [recovery backup](../../scripts/backup/README.md) runbooks for changes.
Repository merges do not authorize operations on the server.

## Historical December 2025 operations reference

**Everything below this heading is an inherited December 28, 2025 reference,
including its accounts, versions, paths and operational commands. Its server
(`ftp.pausatf.org`, Ubuntu 20.04, host Apache and MySQL) has been retired. It is not a
current execution procedure or installed-state inventory. Do not execute its
mutating commands as current guidance.** Use the current entry points above and
verify installed state before selecting any procedure.

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
| **Droplet ID** | REDACTED_DROPLET_ID |
| **Hostname** | ftp.pausatf.org |
| **IP Address** | REDACTED_PROD_NEW_IP |
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
   - Captures: plugins, themes, theme_mods, users, site config
   - Output: Commits to `ansible/group_vars/production/wordpress.yml`
   - Includes: 27 users (11 admins, 16 editors)

2. **Encrypted Database and Legacy Backup**
   - Timer: production `pausatf-db-backup.timer` (daily)
   - Stores encrypted artifacts in private DigitalOcean Spaces; backup data is not committed to git.
   - Runbook: `scripts/backup/README.md`

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
doctl compute droplet-action snapshot REDACTED_DROPLET_ID \
  --snapshot-name "pre-change-$(date +%Y%m%d-%H%M%S)"

# Via GitHub workflow
gh workflow run do-nightly-snapshot.yml
```

**List available snapshots:**
```bash
doctl compute droplet-action list REDACTED_DROPLET_ID | grep snapshot
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

# Credential diagnostics must not print configuration values.
# Verify application connectivity in the current target context instead.
```

### Escalation Path

**Level 1: Self-service**
- Check this runbook
- Review logs
- Search documentation

**Level 2: Team review**
- Create GitHub issue
- Tag @somethingwithproof
- Include relevant logs

**Level 3: Emergency**
- Contact emergency contact
- Create DigitalOcean support ticket
- Document all actions

## Emergency Contacts

**Primary Contact:**
- Name: Thomas Vincent
- GitHub: @somethingwithproof
- For: All infrastructure issues

**Service Providers:**

**DigitalOcean Support:**
- Dashboard: https://cloud.digitalocean.com/support
- Droplet ID: REDACTED_DROPLET_ID

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
