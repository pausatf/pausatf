# Disaster Recovery Runbook

**Document Version:** 2.0
**Last Updated:** December 28, 2025
**Owner:** Thomas Vincent
**Review Frequency:** Quarterly

## Table of Contents

- [Overview](#overview)
- [Recovery Objectives](#recovery-objectives)
- [Prerequisites](#prerequisites)
- [Backup Strategy](#backup-strategy)
- [Recovery Scenarios](#recovery-scenarios)
- [Migration Procedures](#migration-procedures)
- [Post-Recovery](#post-recovery)
- [Testing and Validation](#testing-and-validation)

## Overview

This runbook provides comprehensive disaster recovery procedures for the PAUSATF production infrastructure. It covers both emergency recovery scenarios and planned migration to new infrastructure.

### Key Principles

1. **Prevention First**: Automated daily backups minimize data loss
2. **Multiple Recovery Paths**: Snapshot, migration backup, or Terraform rebuild
3. **Documented Procedures**: Clear step-by-step instructions
4. **Tested Regularly**: Quarterly DR drills
5. **Automation**: Ansible playbooks for consistent recovery

## Recovery Objectives

| Metric | Target | Notes |
|--------|--------|-------|
| **RTO** (Recovery Time Objective) | 2 hours | Time to restore service |
| **RPO** (Recovery Point Objective) | 24 hours | Maximum data loss |
| **Planned Migration** | 4-6 hours | Non-emergency infrastructure change |
| **Emergency Recovery** | 1-2 hours | Critical service restoration |

### Service Priority

1. **Critical (P0)**: WordPress website, database
2. **High (P1)**: Email, SSL certificates
3. **Medium (P2)**: Legacy static content
4. **Low (P3)**: Analytics, monitoring

## Prerequisites

### Required Access

- [x] GitHub repository access (pausatf/pausatf)
- [x] DigitalOcean console access + API token
- [x] Cloudflare dashboard access + API token
- [x] SSH private key (`~/.ssh/pausatf-prod`)
- [x] GitHub CLI (`gh`) installed and authenticated
- [x] Ansible installed (v2.9+)
- [x] doctl (DigitalOcean CLI) installed

### Required Credentials

Store securely in password manager:

| Credential | Purpose | Location |
|------------|---------|----------|
| **DO_TOKEN** | DigitalOcean API | GitHub secret |
| **DO_PROD_DROPLET_ID** | Production droplet ID | 355909945 |
| **CLOUDFLARE_API_TOKEN** | Cloudflare API | GitHub secret |
| **PROD_SSH_PRIVATE_KEY** | Production SSH | GitHub secret / local |
| **SPACES_ACCESS_KEY_ID** | Terraform state backend | GitHub secret |
| **SPACES_SECRET_ACCESS_KEY** | Terraform state backend | GitHub secret |

## Backup Strategy

### Automated Backups

**Nightly Backups (All run at 2-3 AM PT):**

1. **DigitalOcean Snapshots**
   - Workflow: `.github/workflows/do-nightly-snapshot.yml`
   - Frequency: Daily
   - Retention: 7 days (DigitalOcean managed)
   - Size: ~40GB
   - Recovery Time: 10-15 minutes

2. **WordPress Inventory**
   - Workflow: `.github/workflows/capture-prod-inventory.yml`
   - Frequency: Daily
   - Captures: Plugins, themes, configurations
   - Location: `ansible/group_vars/production/wordpress.yml` (committed to git)

3. **Legacy Directory Backup**
   - Workflow: `.github/workflows/backup-legacy.yml`
   - Frequency: Daily
   - Location: `backups/legacy/` (committed to git)
   - Size: ~5GB

### On-Demand Backups

**Migration Backup (Manual):**

```bash
# Create comprehensive migration backup
cd /path/to/pausatf
ansible-playbook -i ansible/inventory/hosts.yml \
  ansible/playbooks/migrate-prod.yml \
  -l production

# Output location
ls -lh backups/migration-YYYYMMDD-HHMMSS/
```

**Backup Contents:**
- WordPress files and database
- Legacy directory (with hidden files)
- Apache configuration
- Cron jobs (all users)
- Custom scripts
- System packages list
- PHP/MySQL configuration

## Recovery Scenarios

### Scenario 1: Complete Droplet Failure

**Symptoms:**
- Droplet unresponsive via SSH
- Website down (502/503 errors)
- DigitalOcean console shows droplet offline

**Recovery Path: Restore from Snapshot (Fastest - 15 minutes)**

```bash
# Step 1: Verify the issue
doctl compute droplet get 355909945

# Step 2: List available snapshots
doctl compute droplet-action list 355909945 | grep snapshot
doctl compute snapshot list

# Step 3: Create new droplet from latest snapshot
doctl compute droplet create pausatf-prod-restore \
  --image SNAPSHOT_ID \
  --size s-4vcpu-8gb \
  --region sfo3 \
  --ssh-keys YOUR_SSH_KEY_ID \
  --enable-backups

# Step 4: Get new IP
NEW_IP=$(doctl compute droplet get pausatf-prod-restore --format PublicIPv4 --no-header)
echo "New IP: $NEW_IP"

# Step 5: Update DNS
# Via Cloudflare dashboard or:
# terraform/environments/production - update IP and apply

# Step 6: Verify
curl -I http://$NEW_IP/
ssh root@$NEW_IP 'systemctl status apache2 mysql'

# Step 7: Test WordPress
curl -I https://www.pausatf.org/

# Step 8: Update inventory
# ansible/inventory/hosts.yml - update ansible_host
```

**Recovery Path: Restore from Migration Backup (Comprehensive - 2 hours)**

If snapshot is unavailable or corrupted:

```bash
# Step 1: Provision new droplet
doctl compute droplet create pausatf-prod-new \
  --image ubuntu-22-04-x64 \
  --size s-4vcpu-8gb \
  --region sfo3 \
  --ssh-keys YOUR_SSH_KEY_ID \
  --enable-backups

# Step 2: Get new IP and update inventory
NEW_IP=$(doctl compute droplet get pausatf-prod-new --format PublicIPv4 --no-header)

# Update ansible/inventory/hosts.yml
# pausatf-prod:
#   ansible_host: NEW_IP_HERE

# Step 3: Restore using Ansible
cd /path/to/pausatf
ansible-playbook -i ansible/inventory/hosts.yml \
  ansible/playbooks/restore-prod.yml \
  -l production

# When prompted:
# - Enter backup path: backups/migration-YYYYMMDD-HHMMSS
# - Confirm: yes

# Step 4: Manual crontab restoration
ssh root@$NEW_IP
crontab /tmp/pausatf-restore/crontab-root.txt
crontab -u www-data /tmp/pausatf-restore/crontab-www-data.txt

# Step 5: Update DNS (see Scenario 3)

# Step 6: Verify (see Post-Recovery checklist)
```

### Scenario 2: Database Corruption/Loss

**Symptoms:**
- WordPress shows "Error establishing database connection"
- Database queries failing
- MySQL won't start

**Recovery Steps:**

```bash
# Step 1: Check MySQL status
ssh deploy@ftp.pausatf.org 'sudo systemctl status mysql'

# Step 2: Try to repair
ssh deploy@ftp.pausatf.org 'sg www-data -c "wp db repair --path=/var/www/html"'

# Step 3: If repair fails, restore from migration backup
cd backups/migration-*/

# Extract and import database
scp wordpress-db.sql.gz root@ftp.pausatf.org:/tmp/
ssh root@ftp.pausatf.org 'gunzip /tmp/wordpress-db.sql.gz'

# Get DB credentials
ssh deploy@ftp.pausatf.org 'grep DB_ /var/www/html/wp-config.php'

# Import database
ssh root@ftp.pausatf.org \
  'mysql -u USERNAME -p DATABASE_NAME < /tmp/wordpress-db.sql'

# Step 4: Verify
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp db check --path=/var/www/html"'
```

### Scenario 3: DNS/Cloudflare Failure

**Symptoms:**
- Domain not resolving
- Cloudflare showing errors
- DNS propagation issues

**Recovery Steps:**

```bash
# Step 1: Verify Cloudflare status
curl -X GET "https://api.cloudflare.com/client/v4/zones/67b87131144a68ad5ed43ebfd4e6d811" \
  -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" | jq .

# Step 2: Check current DNS
dig pausatf.org
dig www.pausatf.org

# Step 3: Recreate DNS via Terraform
cd terraform/environments/production
terraform plan
terraform apply

# Step 4: Verify propagation
for i in {1..10}; do
  echo "Check $i:"
  dig +short pausatf.org @1.1.1.1
  sleep 30
done
```

### Scenario 4: WordPress Compromise/Hack

**Symptoms:**
- Suspicious files detected
- Unknown admin users
- Site defaced or redirecting
- Unusual traffic patterns

**Emergency Response:**

```bash
# Step 1: ISOLATE - Enable maintenance mode
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp maintenance-mode activate --path=/var/www/html"'

# Or add .maintenance file
ssh deploy@ftp.pausatf.org \
  'echo "<?php \$upgrading = time(); ?>" | sudo tee /var/www/html/.maintenance'

# Step 2: SNAPSHOT - Preserve evidence
doctl compute droplet-action snapshot 355909945 \
  --snapshot-name "security-incident-$(date +%Y%m%d-%H%M%S)"

# Step 3: ASSESS
ssh deploy@ftp.pausatf.org 'find /var/www/html -type f -mtime -7 -ls' > /tmp/recent-changes.txt

ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp user list --path=/var/www/html"' > /tmp/users.txt

# Step 4: RESTORE from known-good backup
# Use migration backup from before compromise
ansible-playbook -i ansible/inventory/hosts.yml \
  ansible/playbooks/restore-prod.yml \
  -l production

# Step 5: HARDEN
# - Change all passwords
# - Update all plugins/themes
# - Review user accounts
# - Check for backdoors

# Step 6: Monitor and document
# - Enable debug logging
# - Monitor for reinfection
# - Document incident
```

### Scenario 5: Complete Infrastructure Loss

**Recovery Steps (Cold Start):**

```bash
# Step 1: Clone repository
git clone git@github.com:pausatf/pausatf.git
cd pausatf

# Step 2: Set up credentials
# Add to ~/.bashrc or ~/.zshrc:
export DIGITALOCEAN_ACCESS_TOKEN="your-token"
export CLOUDFLARE_API_TOKEN="your-token"
export TF_VAR_do_token="$DIGITALOCEAN_ACCESS_TOKEN"
export TF_VAR_cloudflare_api_token="$CLOUDFLARE_API_TOKEN"

# Step 3: Provision infrastructure with Terraform
cd terraform/environments/production
terraform init
terraform plan -out=tfplan
terraform apply tfplan

# Step 4: Get new droplet IP
NEW_IP=$(terraform output -raw droplet_ip)

# Step 5: Update Ansible inventory
cd ../../../ansible
# Edit inventory/hosts.yml with NEW_IP

# Step 6: Deploy using Ansible restore playbook
ansible-playbook -i inventory/hosts.yml \
  playbooks/restore-prod.yml \
  -l production

# Provide latest migration backup when prompted

# Step 7: Verify (see Post-Recovery checklist)
```

## Migration Procedures

### Planned Migration to New Droplet

**Use Case:** Upgrading to Ubuntu 22.04, changing droplet size, changing regions

**Timeline:** 4-6 hours (including testing)

**Step 1: Create Migration Backup (Day before)**

```bash
cd /path/to/pausatf
ansible-playbook -i ansible/inventory/hosts.yml \
  ansible/playbooks/migrate-prod.yml \
  -l production

# Review backup
BACKUP_DIR=$(ls -dt backups/migration-* | head -1)
cat $BACKUP_DIR/MANIFEST.txt
ls -lh $BACKUP_DIR/
```

**Step 2: Provision New Droplet (Day of migration)**

```bash
# Via Terraform (recommended)
cd terraform/environments/production

# Update variables for new configuration
# terraform.tfvars - update droplet size, image, etc.

terraform plan -out=tfplan
terraform apply tfplan

# Or via doctl
doctl compute droplet create pausatf-prod-v2 \
  --image ubuntu-22-04-x64 \
  --size s-4vcpu-8gb \
  --region sfo3 \
  --ssh-keys YOUR_SSH_KEY_ID \
  --enable-backups
```

**Step 3: Update Inventory**

```yaml
# ansible/inventory/hosts.yml
pausatf-prod:
  ansible_host: NEW_DROPLET_IP    # Update this
  ansible_user: root              # Initial as root
  ansible_python_interpreter: /usr/bin/python3
  wordpress_path: /var/www/html
  legacy_path: /var/www/legacy/public_html
  web_server: apache
  php_version: "7.4"
```

**Step 4: Restore to New Droplet**

```bash
cd /path/to/pausatf
ansible-playbook -i ansible/inventory/hosts.yml \
  ansible/playbooks/restore-prod.yml \
  -l production

# Provide backup path when prompted:
# backups/migration-YYYYMMDD-HHMMSS
```

**Step 5: Post-Restoration Configuration**

```bash
# SSH to new droplet
NEW_IP=$(grep ansible_host ansible/inventory/hosts.yml | grep prod | awk '{print $2}')
ssh root@$NEW_IP

# Restore crontabs
crontab /tmp/pausatf-restore/crontab-root.txt
crontab -u www-data /tmp/pausatf-restore/crontab-www-data.txt

# Create deploy user (if needed)
useradd -m -s /bin/bash -G www-data,sudo deploy
mkdir -p /home/deploy/.ssh
cp /root/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# Test services
systemctl status apache2 mysql
curl -I http://localhost/
```

**Step 6: Testing (On New Droplet)**

```bash
# Add test entry to /etc/hosts on your laptop:
# NEW_DROPLET_IP www.pausatf.org pausatf.org

# Test from browser
open http://www.pausatf.org/

# Test WordPress admin
open http://www.pausatf.org/wp-admin/

# Test legacy site
open http://www.pausatf.org/data/

# Remove /etc/hosts entry when done
```

**Step 7: DNS Cutover**

```bash
# Update Cloudflare DNS to point to new IP
cd terraform/environments/production

# Update DNS module with new IP
# terraform apply

# Or via Cloudflare dashboard:
# Zone: pausatf.org
# A record: pausatf.org → NEW_IP
# A record: www.pausatf.org → NEW_IP
# A record: ftp.pausatf.org → NEW_IP (direct, no proxy)

# Monitor DNS propagation
watch -n 5 'dig +short pausatf.org @1.1.1.1'
```

**Step 8: Post-Cutover Monitoring (First 24 hours)**

```bash
# Monitor logs
ssh deploy@ftp.pausatf.org 'tail -f /var/log/apache2/error.log'

# Monitor performance
curl -w "@curl-format.txt" -o /dev/null -s https://www.pausatf.org/

# Check for errors in WordPress
ssh deploy@ftp.pausatf.org \
  'sg www-data -c "wp site health --path=/var/www/html"'

# Monitor uptime
# Add to uptime monitoring service
```

**Step 9: Decommission Old Droplet (After 7 days)**

```bash
# Take final snapshot
doctl compute droplet-action snapshot 355909945 \
  --snapshot-name "final-backup-old-prod-$(date +%Y%m%d)"

# Power off old droplet (don't destroy immediately)
doctl compute droplet-action power-off 355909945

# After 30 days, destroy if no issues
doctl compute droplet delete 355909945
```

## Post-Recovery

### Verification Checklist

**Infrastructure:**
- [ ] All droplets running and accessible via SSH
- [ ] Correct droplet size and specifications
- [ ] Firewall rules applied
- [ ] Backups enabled on new droplet

**Services:**
- [ ] Apache running and responding
- [ ] MySQL running and accepting connections
- [ ] PHP version correct
- [ ] Cron jobs restored and running

**WordPress:**
- [ ] Website loads (https://www.pausatf.org)
- [ ] WordPress admin accessible
- [ ] All plugins active and functioning
- [ ] Theme displaying correctly
- [ ] Media uploads working
- [ ] Contact forms working
- [ ] No PHP errors in logs

**Database:**
- [ ] Database connection working
- [ ] All tables present
- [ ] Data integrity verified
- [ ] Database backups resuming

**DNS/SSL:**
- [ ] DNS resolving correctly
- [ ] SSL certificate valid
- [ ] HTTPS redirects working
- [ ] Cloudflare proxy active

**Legacy Site:**
- [ ] Static files accessible
- [ ] Directory listings working (if applicable)
- [ ] Historical data intact

**Email:**
- [ ] MX records correct
- [ ] Test email sent successfully
- [ ] SPF/DKIM/DMARC records intact

**Monitoring:**
- [ ] Uptime monitoring active
- [ ] Log aggregation working
- [ ] Alerts configured
- [ ] GitHub Actions workflows passing

### Communication Template

```
Subject: [RESOLVED] PAUSATF Infrastructure Recovery Complete

Status: Resolved
Incident: [Brief description]
Start Time: [YYYY-MM-DD HH:MM PT]
Resolution Time: [YYYY-MM-DD HH:MM PT]
Duration: [X hours Y minutes]

Summary:
[What happened and why]

Impact:
[Services affected and duration]

Resolution:
[How it was resolved]

Root Cause:
[Why it happened]

Preventive Measures:
[What we're doing to prevent recurrence]

Next Steps:
[Any follow-up actions]

Contact: Thomas Vincent (@thomasvincent)
```

## Testing and Validation

### Quarterly DR Drill

**Schedule:** First Saturday of every quarter

**Procedure:**

```bash
# Q1 Drill: Snapshot Recovery
# 1. Take snapshot
# 2. Create test droplet from snapshot
# 3. Verify functionality
# 4. Document time taken
# 5. Destroy test droplet

# Q2 Drill: Migration Backup Recovery
# 1. Create migration backup
# 2. Provision new test droplet
# 3. Run restore playbook
# 4. Verify functionality
# 5. Document issues encountered
# 6. Update procedures

# Q3 Drill: Cold Start Recovery
# 1. Start with only git repo
# 2. Provision via Terraform
# 3. Restore via Ansible
# 4. Verify functionality
# 5. Time entire process

# Q4 Drill: Database Recovery
# 1. Export database
# 2. Corrupt test database
# 3. Restore from backup
# 4. Verify data integrity
```

**Success Criteria:**
- RTO met (< 2 hours for snapshot, < 4 hours for full restore)
- All services functional
- No data loss
- Procedures followed without improvisation

## Related Documentation

- [Production Operations Runbook](production-operations.md)
- [Ansible README](../../ansible/README.md)
- [Terraform README](../../terraform/README.md)
- [GitHub Secrets Documentation](../.github/SECRETS.md)
- [Deployment Runbook](deployment.md)

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-12-20 | Thomas Vincent | Initial disaster recovery procedures |
| 2.0 | 2025-12-28 | Thomas Vincent | Added migration procedures, enhanced scenarios, Ansible integration |

---

**Next Review Date:** March 2026
**Document Owner:** Thomas Vincent (@thomasvincent)
**Classification:** Internal - Critical
