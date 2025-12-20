# PAUSATF.ORG RECOMMENDED UPGRADES ROADMAP
**Created:** 2025-12-20
**Last Updated:** 2025-12-20
**Maintained by:** Thomas Vincent

---

## EXECUTIVE SUMMARY

This document provides a comprehensive roadmap for infrastructure, software, and security upgrades for pausatf.org. Upgrades are prioritized by urgency, impact, and complexity.

**Current Status:**
- ✅ Cache optimization complete (93% autoload reduction)
- ✅ Performance optimized (6x faster response times)
- ✅ Security audit complete (no critical vulnerabilities)
- ⚠️ PHP 7.4.33 (EOL November 2022) - needs upgrade
- ⚠️ Ubuntu 20.04 LTS (support until April 2025) - plan upgrade

---

## UPGRADE PRIORITY MATRIX

| Priority | Item | Impact | Complexity | Timeline |
|----------|------|--------|------------|----------|
| 🔴 **CRITICAL** | PHP 8.1/8.3 Upgrade | High | Medium | Q1 2026 |
| 🟡 **HIGH** | Ubuntu 22.04/24.04 LTS | High | High | Q2 2026 |
| 🟡 **HIGH** | Database Optimization | Medium | Low | Q1 2026 |
| 🟢 **MEDIUM** | Theme Code Cleanup | Low | Low | Q1 2026 |
| 🟢 **MEDIUM** | Plugin Updates (Ongoing) | Medium | Low | Monthly |
| 🟢 **LOW** | Apache → OpenLiteSpeed Migration | Medium | High | Q3 2026+ |

---

## CRITICAL PRIORITY UPGRADES

### 1. PHP Version Upgrade (7.4.33 → 8.1 or 8.3)

**Current Status:**
- PHP: 7.4.33 (EOL: November 28, 2022)
- Security: No longer receiving security updates
- Performance: Missing modern PHP optimizations

**Recommended Target:**
- **Option A:** PHP 8.1 (Active Support until Nov 2024, Security until Nov 2025)
- **Option B:** PHP 8.3 (Active Support until Nov 2025, Security until Nov 2026) ✅ **RECOMMENDED**

**Benefits:**
- ✅ Active security updates
- ✅ 15-30% performance improvement (JIT compiler)
- ✅ Modern language features
- ✅ Better WordPress 6.9+ compatibility

**Risks:**
- ⚠️ Plugin/theme compatibility issues
- ⚠️ Deprecated function warnings
- ⚠️ Potential site downtime during migration

**Migration Steps:**

#### Phase 1: Compatibility Assessment (1-2 days)
1. **Test on staging server:**
   ```bash
   # On staging server (currently running PHP 8.4.15)
   ssh root@stage.pausatf.org
   wp plugin list --path=/var/www/html/ --allow-root
   wp theme list --path=/var/www/html/ --allow-root
   ```

2. **Check WordPress.org compatibility:**
   - All active plugins: https://wordpress.org/plugins/
   - TheSource theme: Manual code review

3. **Run PHP compatibility checker:**
   ```bash
   # Install PHP Compatibility Checker plugin
   wp plugin install php-compatibility-checker --activate --path=/var/www/html/ --allow-root
   wp php-compat run --path=/var/www/html/ --allow-root
   ```

#### Phase 2: Staging Environment Testing (2-3 days)
1. **Install PHP 8.3 on staging** (already has 8.4.15, good for testing)
2. **Test all functionality:**
   - Homepage loading
   - Admin dashboard
   - Post/page editing
   - Plugin functionality
   - Contact forms
   - Race results display
   - File uploads

3. **Performance benchmarking:**
   ```bash
   # Before upgrade (PHP 7.4)
   for i in {1..10}; do curl -w "%{time_total}\n" -o /dev/null -s https://www.pausatf.org/; done

   # After upgrade (PHP 8.3)
   # Compare results
   ```

#### Phase 3: Production Upgrade (2-4 hours, low-traffic window)
1. **Create full backup:**
   ```bash
   # DigitalOcean snapshot
   doctl compute droplet-action snapshot 355909945 --snapshot-name "pre-php83-upgrade-$(date +%Y%m%d)"

   # Database backup
   ssh root@ftp.pausatf.org "wp db export /tmp/pausatf-pre-php83.sql --path=/var/www/html/ --allow-root"
   ```

2. **Install PHP 8.3:**
   ```bash
   # Add Ondrej Sury PPA (already present)
   sudo add-apt-repository ppa:ondrej/php -y
   sudo apt update

   # Install PHP 8.3 and required modules
   sudo apt install -y php8.3 php8.3-common php8.3-mysql php8.3-xml \
     php8.3-curl php8.3-gd php8.3-imagick php8.3-cli php8.3-dev \
     php8.3-imap php8.3-mbstring php8.3-opcache php8.3-soap \
     php8.3-zip php8.3-intl libapache2-mod-php8.3
   ```

3. **Switch Apache to PHP 8.3:**
   ```bash
   sudo a2dismod php7.4
   sudo a2enmod php8.3
   sudo systemctl restart apache2
   ```

4. **Verify:**
   ```bash
   php -v  # Should show 8.3.x
   curl -I https://www.pausatf.org/  # Check site loads
   wp plugin list --path=/var/www/html/ --allow-root  # Check WP-CLI works
   ```

#### Phase 4: Post-Upgrade Monitoring (7 days)
- Monitor error logs: `tail -f /var/log/apache2/error.log`
- Check WordPress Site Health: Admin → Tools → Site Health
- Monitor page load times
- User feedback monitoring

**Rollback Plan:**
```bash
# If issues occur, rollback to PHP 7.4:
sudo a2dismod php8.3
sudo a2enmod php7.4
sudo systemctl restart apache2
```

**Timeline:** Q1 2026 (Target: January-February 2026)
**Estimated Downtime:** 15-30 minutes
**Complexity:** Medium

---

## HIGH PRIORITY UPGRADES

### 2. Operating System Upgrade (Ubuntu 20.04 LTS → 22.04 or 24.04 LTS)

**Current Status:**
- OS: Ubuntu 20.04 LTS
- Support End Date: April 2025 (Standard Support)
- Extended Support: Available until 2030 (Ubuntu Pro)

**Recommended Target:**
- **Option A:** Ubuntu 22.04 LTS (Jammy Jellyfish) - Support until April 2027
- **Option B:** Ubuntu 24.04 LTS (Noble Numbat) - Support until April 2029 ✅ **RECOMMENDED**

**Benefits:**
- ✅ 5 years of security updates (free)
- ✅ Modern kernel and system libraries
- ✅ Better hardware support
- ✅ Improved container/virtualization support

**Migration Strategy:**

**Option 1: In-Place Upgrade (Higher Risk)**
```bash
# Backup first!
doctl compute droplet-action snapshot 355909945 --snapshot-name "pre-os-upgrade"

# Perform upgrade
sudo do-release-upgrade
```
⚠️ **Not Recommended** - Too risky for production server

**Option 2: New Droplet Migration (Recommended)**
- Follow existing migration guide: [05-server-migration-guide.md](05-server-migration-guide.md)
- Create new droplet with Ubuntu 24.04 LTS
- Migrate data and configuration
- Test thoroughly before DNS cutover
- Keep old droplet running for 7-14 days

**Timeline:** Q2 2026 (Target: April-May 2026)
**Estimated Downtime:** 2-4 hours (DNS propagation)
**Complexity:** High

---

### 3. Database Optimization

**Current Status:**
- Database: MySQL/MariaDB (version TBD)
- Autoloaded Options: 298 KB (optimized Dec 2025)
- Database Size: Unknown

**Recommended Actions:**

#### A. Database Audit (1-2 hours)
```bash
# Connect to production
ssh root@ftp.pausatf.org

# Check database size
wp db size --path=/var/www/html/ --allow-root

# Check table sizes
wp db query "SELECT table_name, round(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'wordpress_db'
ORDER BY (data_length + index_length) DESC;" --path=/var/www/html/ --allow-root

# Check post revisions
wp db query "SELECT COUNT(*) FROM wp_posts WHERE post_type = 'revision';" --path=/var/www/html/ --allow-root

# Check transients
wp db query "SELECT COUNT(*) FROM wp_options WHERE option_name LIKE '%_transient_%';" --path=/var/www/html/ --allow-root
```

#### B. Database Cleanup
1. **Remove old post revisions:**
   ```bash
   # Limit to last 5 revisions per post
   wp post delete $(wp post list --post_type='revision' --format=ids --path=/var/www/html/ --allow-root) --force --path=/var/www/html/ --allow-root
   ```

2. **Clean expired transients:**
   ```bash
   wp transient delete --expired --path=/var/www/html/ --allow-root
   ```

3. **Optimize database tables:**
   ```bash
   wp db optimize --path=/var/www/html/ --allow-root
   ```

4. **Update wp-config.php to limit revisions:**
   ```php
   define('WP_POST_REVISIONS', 5);
   define('AUTOSAVE_INTERVAL', 300); // 5 minutes
   ```

#### C. Database Performance Monitoring
- Install Query Monitor plugin for slow query detection
- Enable MySQL slow query log
- Regular OPTIMIZE TABLE maintenance

**Timeline:** Q1 2026 (Target: January 2026)
**Estimated Downtime:** None (optimizations can run during low traffic)
**Complexity:** Low

---

## MEDIUM PRIORITY UPGRADES

### 4. Theme Code Cleanup

**Current Status:**
- Theme: TheSource (parent) + TheSource-child (active)
- Issues Found: IE6 PNG fix library, backup files
- Security Risk: Low

**Recommended Actions:**

#### A. Remove IE6 PNG Fix Library
```bash
# On production server
ssh root@ftp.pausatf.org

# Remove iepngfix directory
rm -rf /var/www/html/wp-content/themes/TheSource/iepngfix/

# Verify theme still works
curl -I https://www.pausatf.org/
```

#### B. Clean Backup Files from Child Theme
```bash
# List backup files
ssh root@ftp.pausatf.org "find /var/www/html/wp-content/themes/TheSource-child/ -name '*.bak' -o -name '*~' -o -name '*.backup'"

# Remove after review
ssh root@ftp.pausatf.org "find /var/www/html/wp-content/themes/TheSource-child/ -name '*.bak' -delete"
```

#### C. Theme Code Modernization (Optional)
- Remove deprecated PHP functions
- Update jQuery to modern version
- Implement responsive images
- Add accessibility improvements

**Timeline:** Q1 2026 (Target: February 2026)
**Estimated Downtime:** None
**Complexity:** Low

---

### 5. Plugin Update Strategy (Ongoing)

**Current Status:**
- All plugins: Up to date (as of Dec 2025)
- WP Super Cache: 3.0.3
- Jetpack: Active (28 modules)
- wp-fail2ban: 3.5.3

**Recommended Process:**

#### Monthly Plugin Maintenance
```bash
# First week of each month
ssh root@ftp.pausatf.org

# Check for updates
wp plugin list --update=available --path=/var/www/html/ --allow-root

# Update on staging first
ssh root@stage.pausatf.org
wp plugin update --all --path=/var/www/html/ --allow-root

# Test staging site thoroughly
# If all good, update production:
ssh root@ftp.pausatf.org
wp plugin update --all --path=/var/www/html/ --allow-root
```

#### Plugin Audit (Quarterly)
- Review installed plugins
- Remove unused plugins
- Check for alternatives with better performance
- Verify license compliance

**Timeline:** Ongoing (first Monday of each month)
**Complexity:** Low

---

## LOW PRIORITY UPGRADES

### 6. Apache → OpenLiteSpeed Migration (Optional)

**Current Status:**
- Production: Apache 2.4
- Staging: OpenLiteSpeed 1.8.3

**Benefits:**
- 40-50% better performance vs Apache
- Built-in cache acceleration
- Better concurrent connection handling
- Drop-in replacement with Apache compatibility

**Migration Considerations:**
- .htaccess compatibility (OpenLiteSpeed supports Apache .htaccess)
- Learning curve for configuration
- Different log format
- Module compatibility

**Recommended Approach:**
- Monitor staging server OpenLiteSpeed performance
- Compare metrics vs production Apache
- Consider during next server migration
- Not urgent - current Apache performance is acceptable

**Timeline:** Q3 2026+ (or next server migration)
**Complexity:** High

---

## SECURITY HARDENING ROADMAP

### Immediate (Completed ✅)
- ✅ wp-fail2ban installed and active
- ✅ Cloudflare firewall enabled
- ✅ SSL/TLS via Let's Encrypt
- ✅ WordPress 6.9 (latest)
- ✅ All plugins up to date

### Q1 2026
- [ ] Enable WordPress auto-updates for minor/security releases
- [ ] Implement security headers (CSP, HSTS, X-Frame-Options)
- [ ] Two-factor authentication for admin accounts
- [ ] Regular malware scanning (Wordfence or Sucuri)

### Q2 2026
- [ ] File integrity monitoring (AIDE or Tripwire)
- [ ] Intrusion detection system (OSSEC)
- [ ] Automated security scanning (weekly)
- [ ] Security incident response plan

---

## PERFORMANCE OPTIMIZATION ROADMAP

### Completed (Dec 2025) ✅
- ✅ WP Super Cache installed
- ✅ Autoloaded options optimized (4.54 MB → 298 KB)
- ✅ Jetpack modules optimized (35 → 28)
- ✅ Cache headers configured
- ✅ Cloudflare CDN active

### Q1 2026
- [ ] Image optimization (WebP conversion, lazy loading)
- [ ] CSS/JS minification and concatenation
- [ ] Database query optimization
- [ ] Implement Redis object cache (optional)

### Q2 2026
- [ ] HTTP/3 support
- [ ] Brotli compression
- [ ] Critical CSS extraction
- [ ] Resource hints (preload, prefetch, preconnect)

---

## MONITORING & MAINTENANCE ROADMAP

### Current Status
- Manual monitoring via WordPress Site Health
- Cloudflare analytics
- Server access logs

### Q1 2026
- [ ] Uptime monitoring (UptimeRobot or StatusCake)
- [ ] Performance monitoring (New Relic or Datadog)
- [ ] Error tracking (Sentry or Rollbar)
- [ ] Automated backup verification

### Q2 2026
- [ ] Centralized logging (ELK stack or Graylog)
- [ ] APM (Application Performance Monitoring)
- [ ] Real User Monitoring (RUM)
- [ ] Automated incident response

---

## COST ESTIMATE

| Item | One-Time Cost | Monthly Cost | Notes |
|------|---------------|--------------|-------|
| PHP 8.3 Upgrade | $0 | $0 | Free software |
| Ubuntu 24.04 Upgrade | $0 | $0 | Free (via migration) |
| Database Optimization | $0 | $0 | DIY |
| Theme Cleanup | $0 | $0 | DIY |
| Security Tools (optional) | $0-200 | $0-20 | Wordfence Premium, Sucuri |
| Monitoring (optional) | $0 | $0-50 | UptimeRobot free tier, or paid |
| **TOTAL** | **$0-200** | **$0-70** | Minimal investment |

---

## SUCCESS METRICS

### Performance
- **Current:** 220ms average response time ✅
- **Target Q1 2026:** <150ms (with PHP 8.3)
- **Target Q2 2026:** <100ms (with HTTP/3 + optimizations)

### Security
- **Current:** 0 critical vulnerabilities ✅
- **Target:** Maintain 0 critical, reduce medium/low
- **Scanning:** Weekly automated scans

### Uptime
- **Current:** Unknown (no monitoring)
- **Target:** 99.9% uptime (8.76 hours downtime/year max)

### Load Time
- **Current:** ~220ms server + CloudFlare edge cache
- **Target:** <1 second full page load (including frontend)

---

## IMPLEMENTATION CHECKLIST

### Before Any Upgrade
- [ ] Create DigitalOcean snapshot
- [ ] Export database backup
- [ ] Document current configuration
- [ ] Test on staging environment
- [ ] Schedule during low-traffic window
- [ ] Notify stakeholders

### During Upgrade
- [ ] Follow documented procedure
- [ ] Monitor error logs in real-time
- [ ] Keep rollback plan ready
- [ ] Document any issues encountered

### After Upgrade
- [ ] Verify site functionality
- [ ] Check WordPress Site Health
- [ ] Monitor for 24-48 hours
- [ ] Update documentation
- [ ] Archive old backups (after 7-14 days)

---

## ROLLBACK PROCEDURES

### PHP Upgrade Rollback
```bash
sudo a2dismod php8.3
sudo a2enmod php7.4
sudo systemctl restart apache2
```

### OS Upgrade Rollback
```bash
# Restore from DigitalOcean snapshot
doctl compute droplet-action restore 355909945 --snapshot-id <snapshot-id>

# OR revert DNS to old droplet
# Update Cloudflare DNS records
```

### Database Rollback
```bash
# Restore from backup
wp db import /tmp/pausatf-pre-upgrade.sql --path=/var/www/html/ --allow-root
```

---

## QUARTERLY REVIEW SCHEDULE

**Q1 2026 (Jan-Mar):**
- PHP 8.3 upgrade
- Database optimization
- Theme cleanup
- Security hardening phase 1

**Q2 2026 (Apr-Jun):**
- Ubuntu 24.04 migration
- Security hardening phase 2
- Performance optimization phase 2

**Q3 2026 (Jul-Sep):**
- Monitoring implementation
- Ongoing maintenance
- Consider OpenLiteSpeed migration

**Q4 2026 (Oct-Dec):**
- Year-end review
- Plan 2027 roadmap
- Security audit

---

## RESPONSIBLE PARTIES

**System Administrator:** Thomas Vincent
- Server infrastructure
- OS/PHP upgrades
- Security implementation

**WordPress Administrator:** TBD
- Plugin updates
- Content management
- User management

**Backup Contact:** DigitalOcean Support
- Emergency server access
- Snapshot management

---

## DOCUMENTATION UPDATES

After each upgrade, update the following documentation:
- `README.md` - Server environment section
- `01-cache-implementation-guide.md` - Server specs
- `05-server-migration-guide.md` - Current server inventory
- This document (`08-recommended-upgrades-roadmap.md`) - Completion status

---

## CHANGE LOG

### 2025-12-20
- Initial roadmap created
- PHP 8.3 upgrade planned for Q1 2026
- Ubuntu 24.04 migration planned for Q2 2026
- Database optimization scheduled for Q1 2026

---

## REFERENCES

**PHP Support Timeline:**
- PHP 7.4: https://www.php.net/supported-versions.php
- PHP 8.3: https://www.php.net/releases/8.3/en.php

**Ubuntu Release Information:**
- Ubuntu 20.04: https://wiki.ubuntu.com/FocalFossa/ReleaseNotes
- Ubuntu 24.04: https://wiki.ubuntu.com/NobleNumbat/ReleaseNotes

**WordPress Requirements:**
- https://wordpress.org/about/requirements/

**DigitalOcean Documentation:**
- Snapshots: https://docs.digitalocean.com/products/images/snapshots/
- Droplet Migration: https://docs.digitalocean.com/products/droplets/how-to/migrate/

---

**Maintained by:** Thomas Vincent
**Organization:** Pacific Association of USA Track and Field (PAUSATF)
**Last Updated:** 2025-12-20
