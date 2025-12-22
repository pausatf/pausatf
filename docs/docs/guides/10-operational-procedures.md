# OPERATIONAL PROCEDURES FOR PAUSATF.ORG
## Day-to-Day Operations and Maintenance

**Last Updated:** 2025-12-20
**Maintained by:** Thomas Vincent

---

## TABLE OF CONTENTS

1. [WordPress Update Procedures](#wordpress-update-procedures)
2. [Backup Verification](#backup-verification)
3. [SSL Certificate Management](#ssl-certificate-management)
4. [Emergency Procedures](#emergency-procedures)
5. [Monthly Maintenance Checklist](#monthly-maintenance-checklist)
6. [Contact Information](#contact-information)

---

## WORDPRESS UPDATE PROCEDURES

### Plugin Updates (Monthly - First Monday)

**Test on Staging First:**
```bash
# Connect to staging server
ssh root@stage.pausatf.org

# Check for updates
wp plugin list --update=available --path=/var/www/html/ --allow-root

# Update all plugins
wp plugin update --all --path=/var/www/html/ --allow-root

# Test website functionality
curl -I https://stage.pausatf.org/ | grep "200 OK"

# Check WordPress Site Health
wp eval 'wp_version_check(); wp_update_plugins(); wp_update_themes();' --path=/var/www/html/ --allow-root
```

**If Staging Tests Pass, Update Production:**
```bash
# Connect to production server
ssh root@prod.pausatf.org

# Create backup first (DigitalOcean snapshot)
doctl compute droplet-action snapshot 355909945 --snapshot-name "pre-plugin-update-$(date +%Y%m%d)"

# Update plugins
wp plugin update --all --path=/var/www/html/ --allow-root

# Verify site is working
curl -I https://www.pausatf.org/ | grep "200 OK"

# Clear cache
/usr/local/bin/purge_cloudflare_cache.sh all
```

---

### WordPress Core Updates

**Minor Updates (Automatic):**
- WordPress auto-updates for security releases
- Check: Admin → Dashboard → Updates

**Major Updates (Manual):**
```bash
# Test on staging first
ssh root@stage.pausatf.org
wp core check-update --path=/var/www/html/ --allow-root
wp core update --path=/var/www/html/ --allow-root
wp core update-db --path=/var/www/html/ --allow-root

# Test thoroughly on staging
# If successful, repeat on production after creating snapshot
```

---

### Theme Updates

**Before Updating TheSource or TheSource-child:**
```bash
# Create backup of current theme
ssh root@prod.pausatf.org
cd /var/www/html/wp-content/themes/
tar -czf ~/TheSource-child-backup-$(date +%Y%m%d).tar.gz TheSource-child/

# Check for updates
wp theme list --update=available --path=/var/www/html/ --allow-root

# Test on staging first
ssh root@stage.pausatf.org
wp theme update [theme-name] --path=/var/www/html/ --allow-root
```

**Note:** TheSource is a custom theme - updates are rare. Always test theme updates carefully as they can break site layout.

---

## BACKUP VERIFICATION

### DigitalOcean Snapshots (Daily Verification)

**Check Snapshot Status:**
```bash
# List recent droplet backups
doctl compute droplet backup list 355909945  # Production
doctl compute droplet backup list 538411208  # Staging

# Check droplet features (should show "backups")
doctl compute droplet list | grep pausatf
```

**Backup Schedule:**
- **Automatic:** Daily (DigitalOcean backups feature enabled Dec 21, 2025)
- **Backup Time:** Automated by DigitalOcean (typically 3-5am UTC)
- **Manual:** Before any major changes (PHP upgrade, migrations, etc.)
- **Retention:** 4 most recent automated backups (rolling) + manual snapshots
- **Cost:** 20% of droplet cost (included in pricing)
  - Production: $9.60/month
  - Staging: $4.80/month

**Backup Verification Commands:**
```bash
# Verify backups are enabled
doctl compute droplet get 355909945  # Check Features column
doctl compute droplet get 538411208

# List available backups (should see daily backups)
doctl compute droplet backup list 355909945
doctl compute droplet backup list 538411208
```

---

### Database Backup (Monthly)

**Create Manual Database Backup:**
```bash
# Connect to production
ssh root@prod.pausatf.org

# Export database
wp db export /tmp/pausatf-db-backup-$(date +%Y%m%d).sql --path=/var/www/html/ --allow-root

# Download to local machine
scp root@prod.pausatf.org:/tmp/pausatf-db-backup-$(date +%Y%m%d).sql ~/backups/

# Verify backup file size (should be several MB)
ls -lh ~/backups/pausatf-db-backup-*.sql
```

**Database Backup Locations:**
- **DigitalOcean Snapshots:** Full droplet including database
- **Manual Exports:** `/tmp/` on server (temporary)
- **Local Copies:** `~/backups/` on admin machine

---

### Test Backup Restore (Quarterly)

**Restore Test Procedure:**
1. Create test droplet from snapshot
2. Verify WordPress loads
3. Check database integrity
4. Test login and admin functions
5. Delete test droplet

```bash
# This should be done quarterly to ensure backups are recoverable
# Full procedure in 05-server-migration-guide.md
```

---

## SSL CERTIFICATE MANAGEMENT

### Cloudflare Universal SSL (Proxied Domains)

**Domains Using Cloudflare SSL:**
- pausatf.org
- www.pausatf.org

**Certificate Details:**
- Issuer: Google Trust Services (via Cloudflare)
- Type: Universal SSL (free)
- Renewal: Automatic (managed by Cloudflare)
- Current Expiry: January 24, 2026
- Action Required: None - auto-renews

**Verify Cloudflare SSL:**
```bash
# Check SSL mode
curl -s "https://api.cloudflare.com/client/v4/zones/your-cloudflare-zone-id/settings/ssl" \
  -H "Authorization: Bearer your-cloudflare-api-token" | jq -r '.result.value'

# Expected: "full"
```

---

### Let's Encrypt SSL (Direct Access Domains)

**Domains Using Let's Encrypt:**
- ftp.pausatf.org
- prod.pausatf.org (CNAME to ftp)
- stage.pausatf.org

**Certificate Management:**
```bash
# Check certificate status
ssh root@prod.pausatf.org "certbot certificates"

# Certificates auto-renew via systemd timer
ssh root@prod.pausatf.org "systemctl status certbot.timer"

# Test renewal process (dry run)
ssh root@prod.pausatf.org "certbot renew --dry-run"
```

**Certificate Locations:**
- `/etc/letsencrypt/live/pausatf.org/`
- Auto-renewal: Enabled via certbot systemd timer

---

## EMERGENCY PROCEDURES

### Website Down

**1. Quick Checks:**
```bash
# Check if site is down or just slow
curl -I https://www.pausatf.org/

# Check Cloudflare status
curl -I https://www.pausatf.org/ | grep -i cf-cache-status

# Check DigitalOcean droplet status
doctl compute droplet get 355909945 --format Status
```

**2. If Droplet is Down:**
```bash
# Power on droplet
doctl compute droplet-action power-on 355909945

# Reboot droplet
doctl compute droplet-action reboot 355909945
```

**3. If Droplet is Up but Site Not Loading:**
```bash
# SSH into server
ssh root@prod.pausatf.org

# Check Apache status
systemctl status apache2

# Restart Apache if needed
systemctl restart apache2

# Check error logs
tail -100 /var/log/apache2/error.log
```

---

### Database Issues

**If WordPress shows "Error establishing database connection":**
```bash
# SSH to server
ssh root@prod.pausatf.org

# Check MySQL/MariaDB status
systemctl status mysql

# Restart MySQL if needed
systemctl restart mysql

# Check database
wp db check --path=/var/www/html/ --allow-root
```

---

### High CPU/Memory Usage

**Identify Issue:**
```bash
ssh root@prod.pausatf.org

# Check top processes
top -b -n 1 | head -20

# Check Apache processes
ps aux | grep apache2 | wc -l

# Check MySQL processes
ps aux | grep mysql
```

**Quick Fix:**
```bash
# Restart Apache to clear processes
systemctl restart apache2

# Clear WordPress cache
wp cache flush --path=/var/www/html/ --allow-root

# Purge Cloudflare cache
/usr/local/bin/purge_cloudflare_cache.sh all
```

---

### DNS Issues

**If domain not resolving:**
```bash
# Check DNS propagation
dig pausatf.org +short
dig www.pausatf.org +short

# Check Cloudflare status
curl -s "https://api.cloudflare.com/client/v4/zones/your-cloudflare-zone-id" \
  -H "Authorization: Bearer your-cloudflare-api-token" | jq -r '.result.status'
```

**Cloudflare Dashboard:**
https://dash.cloudflare.com/ → pausatf.org → DNS

---

## MONTHLY MAINTENANCE CHECKLIST

### First Monday of Each Month

- [ ] **Plugin Updates**
  - [ ] Check staging server for available updates
  - [ ] Update staging plugins
  - [ ] Test staging site
  - [ ] Update production plugins
  - [ ] Create snapshot before production updates

- [ ] **Security Checks**
  - [ ] Check WordPress Site Health
  - [ ] Review security plugin logs (wp-fail2ban)
  - [ ] Check for failed login attempts

- [ ] **Performance Review**
  - [ ] Test homepage load time: `curl -w "%{time_total}\n" -o /dev/null -s https://www.pausatf.org/`
  - [ ] Should be < 0.6 seconds
  - [ ] Review Cloudflare analytics

- [ ] **Backup Verification**
  - [ ] Verify weekly DigitalOcean snapshots exist
  - [ ] Create monthly database export
  - [ ] Download database backup to local machine

- [ ] **SSL Certificate Check**
  - [ ] Verify Cloudflare SSL active
  - [ ] Check Let's Encrypt renewal dates
  - [ ] Verify HTTPS redirect working

---

### Quarterly (Every 3 Months)

- [ ] **Full Security Audit**
  - [ ] Review all plugins for security updates
  - [ ] Check WordPress core version
  - [ ] Review user accounts and permissions
  - [ ] Update passwords if needed

- [ ] **Backup Restore Test**
  - [ ] Create test droplet from snapshot
  - [ ] Verify full site functionality
  - [ ] Document any issues
  - [ ] Delete test droplet

- [ ] **DNS Audit**
  - [ ] Verify all DNS records correct
  - [ ] Check CAA records still valid
  - [ ] Review DMARC reports (when enabled)

- [ ] **Performance Baseline**
  - [ ] Document current load times
  - [ ] Review autoloaded options size
  - [ ] Check database size growth

---

### Annually (December)

- [ ] **Infrastructure Review**
  - [ ] Review all documentation for accuracy
  - [ ] Update CHANGELOG with year's changes
  - [ ] Plan next year's upgrades
  - [ ] Review costs and budget

- [ ] **Security Review**
  - [ ] Full WordPress theme security scan
  - [ ] Review all third-party integrations
  - [ ] Update disaster recovery plan

---

## CONTACT INFORMATION

### Service Providers

**DigitalOcean (Hosting):**
- Dashboard: https://cloud.digitalocean.com/
- Support: https://www.digitalocean.com/support/
- Phone: 1-888-890-6714 (24/7)
- Account: [pausatf.org account]

**Cloudflare (CDN/DNS/Security):**
- Dashboard: https://dash.cloudflare.com/
- Support: https://support.cloudflare.com/
- Zone ID: your-cloudflare-zone-id

**Google Workspace (Email):**
- Admin Console: https://admin.google.com/
- Support: https://support.google.com/a/
- Domain: pausatf.org

**SendGrid (Marketing Email):**
- Dashboard: https://app.sendgrid.com/
- Support: https://support.sendgrid.com/

---

### Emergency Escalation

**Priority 1: Site Completely Down**
1. Check DigitalOcean droplet status
2. Check Cloudflare status page
3. Contact DigitalOcean support (888-890-6714)

**Priority 2: Performance Degradation**
1. Check server resources (CPU, memory)
2. Clear caches
3. Review error logs
4. Schedule restart during low-traffic period

**Priority 3: Email Issues**
1. Check Google Workspace status
2. Verify MX records via DNS
3. Contact Google Workspace support

---

### Documentation

**Repository:** https://github.com/pausatf/pausatf-infrastructure-docs

**Key Documents:**
- `README.md` - Start here
- `01-cache-implementation-guide.md` - Cache configuration
- `05-server-migration-guide.md` - Full server migration (includes DR)
- `06-cloudflare-configuration-guide.md` - DNS and CDN
- `09-google-workspace-email-security.md` - Email setup
- `EXECUTIVE-SUMMARY.md` - Non-technical overview

---

## NOTES

**Cloudflare Plan:**
- Current: Free plan
- Includes: HTTP/3, Brotli, DDoS protection, Universal SSL
- Upgrade to Pro ($20/mo) provides: Advanced DDoS, WAF, Image optimization

**WordPress Plugins to Monitor:**
- wp-fail2ban (security)
- WP Super Cache (performance)
- Jetpack (features - currently 28 modules active)
- Cloudflare (CDN integration)

**Server Specs:**
- Production: 8GB RAM / 4 vCPUs / 160GB SSD
- Staging: [check current specs via doctl]

---

**Last Updated:** 2025-12-20
**Next Review:** Monthly (first Monday)
**Maintained by:** Thomas Vincent
