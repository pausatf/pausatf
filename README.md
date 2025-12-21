# PAUSATF.org Infrastructure Documentation

**Comprehensive technical documentation for pausatf.org production and staging infrastructure**

**Repository:** https://github.com/pausatf/pausatf-infrastructure-docs
**Created:** 2025-12-20
**Maintained by:** Thomas Vincent

---

## ⚠️ SECURITY NOTICE

**This is a PUBLIC repository.** Do NOT commit:
- API tokens or keys
- Passwords or credentials
- Private keys or certificates
- Database connection strings with passwords
- Any sensitive infrastructure information

**Pre-commit hooks are configured** to detect secrets. Install them:
```bash
pip install pre-commit
pre-commit install
```

All sensitive values should use placeholders like `your-cloudflare-api-token`.

**Git History:** This repository's history was cleaned on 2025-12-21 to remove accidentally committed secrets. All exposed tokens have been rotated.

---

## Overview

This repository contains complete infrastructure documentation, deployment guides, security audits, and operational procedures for the Pacific Association of USA Track and Field (PAUSATF) website infrastructure.

### What's Documented

- 🖥️ **Server Infrastructure** - DigitalOcean droplets, Apache/OpenLiteSpeed configuration
- 🌐 **Cloudflare CDN** - DNS, caching, security, Page Rules, API automation
- 🔒 **Security** - WordPress theme audits, vulnerability assessments, compliance
- 📦 **Deployments** - Cache fixes, migrations, updates, rollback procedures
- 🔍 **Troubleshooting** - Common issues, resolution steps, verification tests

---

## Repository Contents

Documentation is organized in numbered sequence for logical reading order:

| # | Document | Type | Description |
|---|----------|------|-------------|
| 01 | **[01-cache-implementation-guide.md](01-cache-implementation-guide.md)** | Guide | Complete cache fix implementation and technical details |
| 02 | **[02-cache-audit-report.md](02-cache-audit-report.md)** | Report | Pre-fix audit of cache configurations across all servers |
| 03 | **[03-cache-verification-report.md](03-cache-verification-report.md)** | Report | Production deployment verification (Dec 20, 2025) |
| 04 | **[04-security-audit-report.md](04-security-audit-report.md)** | Report | WordPress theme security assessment (9 findings) |
| 05 | **[05-server-migration-guide.md](05-server-migration-guide.md)** | Guide | Complete 10-phase DigitalOcean migration process |
| 06 | **[06-cloudflare-configuration-guide.md](06-cloudflare-configuration-guide.md)** | Guide | DNS, SSL, caching, firewall, API automation |
| 07 | **[07-performance-optimization-complete.md](07-performance-optimization-complete.md)** | Report | **Complete WordPress performance optimization (93% faster)** |
| 08 | **[08-recommended-upgrades-roadmap.md](08-recommended-upgrades-roadmap.md)** | Roadmap | **Infrastructure upgrade plan (PHP 8.3, Ubuntu 24.04, security hardening)** |
| 09 | **[09-google-workspace-email-security.md](09-google-workspace-email-security.md)** | Guide | **Google Workspace email security setup (SPF, DKIM, DMARC)** |
| 10 | **[10-operational-procedures.md](10-operational-procedures.md)** | Guide | **Day-to-day operations, updates, backups, emergency procedures** |
| 11 | **[11-database-maintenance.md](11-database-maintenance.md)** | Guide | **Database maintenance procedures, primary key requirements, troubleshooting** |
| -- | **[EXECUTIVE-SUMMARY.md](EXECUTIVE-SUMMARY.md)** | Summary | **Non-technical overview for stakeholders** |

### 📦 Deployment Package

**Location:** `deployment-package/`

Ready-to-deploy files for production server:

```
deployment-package/
├── data_2025_htaccess              # Cache headers for race results
├── purge_cloudflare_cache.sh      # Cloudflare cache purge script
└── DEPLOYMENT_INSTRUCTIONS.txt    # Step-by-step deployment guide
```

---

## Quick Start Guides

### For Cache Fix Deployment

1. **Read implementation guide:** [01-cache-implementation-guide.md](01-cache-implementation-guide.md)
2. **Review verification:** [03-cache-verification-report.md](03-cache-verification-report.md)
3. **Deploy to production:** Follow `deployment-package/DEPLOYMENT_INSTRUCTIONS.txt`
4. **Test deployment:** Use verification commands from guide

### For Server Migration

1. **Planning phase:** Review [05-server-migration-guide.md](05-server-migration-guide.md)
2. **Cloudflare setup:** [06-cloudflare-configuration-guide.md](06-cloudflare-configuration-guide.md)
3. **Backup first:** Always create DigitalOcean snapshots before migration
4. **Test thoroughly:** Validate on new server before DNS cutover

### For Security Review

1. **Theme audit:** [04-security-audit-report.md](04-security-audit-report.md)
2. **Risk level:** 🟡 Medium (2 high priority items to address)
3. **Quick fixes:** ~10 minutes to resolve main issues
4. **Next review:** June 2026 (6 months)

### For Infrastructure Upgrades

1. **Review roadmap:** [08-recommended-upgrades-roadmap.md](08-recommended-upgrades-roadmap.md)
2. **Critical priority:** PHP 8.3 upgrade (Q1 2026)
3. **High priority:** Ubuntu 24.04 migration (Q2 2026)
4. **Track changes:** [CHANGELOG.md](CHANGELOG.md)

---

## Server Environment

### Production Server

- **Hostname:** prod.pausatf.org
- **IP Address:** 64.225.40.54
- **Droplet:** pausatf-prod (DigitalOcean)
- **Web Server:** Apache 2.4
- **PHP:** 7.4.33
- **WordPress:** 6.9
- **Document Root:** /var/www/legacy/public_html/
- **SSH Access:** ✅ Available via `ssh root@prod.pausatf.org`

### Staging Server

- **Hostname:** stage.pausatf.org
- **IP Address:** 64.227.85.73
- **Droplet:** pausatf-stage (DigitalOcean)
- **Web Server:** OpenLiteSpeed 1.8.3
- **PHP:** 8.4.15
- **WordPress:** 6.9
- **Document Root:** /var/www/html/
- **SSH Access:** ✅ Available via `ssh root@stage.pausatf.org`

### Cloudflare Configuration

- **Zone ID:** your-cloudflare-zone-id
- **API Token:** (stored in environment variables)
- **Plan:** Free (or Pro - verify in dashboard)
- **Cache Purge Script:** `/usr/local/bin/purge_cloudflare_cache.sh`

---

## Current Status

### ✅ Completed Projects

**Cache Fix Deployment (Dec 20, 2025):**
- ✅ Improved .htaccess deployed to production `/data/2025/`
- ✅ HTML race results never cached (aggressive no-cache headers)
- ✅ Static assets cached for 30 days (down from 1 year)
- ✅ Cloudflare respecting cache directives via CF-Cache-Control headers
- ✅ All verification tests passing
- ✅ Page Rules not required (CF-Cache-Control headers sufficient)

**Server Access:**
- ✅ SSH restored to production server (prod.pausatf.org)
- ✅ Staging server fully accessible (stage.pausatf.org)

**Security Audit:**
- ✅ TheSource themes scanned (no critical vulnerabilities)
- ✅ All WordPress plugins up to date
- ✅ PHP deprecated functions check (all clear)

**Performance Optimization (Dec 20, 2025):**
- ✅ Response time reduced from 1,357ms to ~220ms (6x faster)
- ✅ WP Super Cache 3.0.3 installed and configured
- ✅ Homepage well below 600ms recommended threshold
- ✅ **Autoloaded options: 4.54 MB → 298 KB (93% reduction)**
- ✅ **Admin performance: 1,490ms → 232ms (84% faster)**
- ✅ Disabled 8 unnecessary Jetpack modules (35 → 28)
- ✅ Set widget_custom_html (4.04 MB) to not autoload
- ✅ All WordPress Site Health critical issues resolved
- See: [07-performance-optimization-complete.md](07-performance-optimization-complete.md)

**Cloudflare Performance Features (Verified Active):**
- ✅ HTTP/3 with QUIC protocol enabled
- ✅ 0-RTT (Zero Round Trip Time) connection resumption
- ✅ TLS 1.3 with Zero Round Trip (zrt mode)
- ✅ Brotli compression (15-20% better than gzip)
- ✅ Early Hints (103 status code for faster loading)
- ✅ Rocket Loader (async JavaScript loading)
- ✅ Always Use HTTPS + Automatic HTTPS Rewrites
- ✅ Aggressive cache level with origin header respect
- See: [06-cloudflare-configuration-guide.md](06-cloudflare-configuration-guide.md)

### ⏳ Pending Items

**Theme Cleanup (Optional):**
- ⏳ Remove IE6 PNG fix library (5 min)
- ⏳ Delete backup files from child theme (5 min)
- See: [04-security-audit-report.md](04-security-audit-report.md)

**PHP Upgrade (Long-term):**
- ⏳ PHP 7.4 reached EOL (Nov 2022)
- ⏳ Plan migration to PHP 8.1 or 8.3
- See: [05-server-migration-guide.md](05-server-migration-guide.md)

---

## Documentation Structure

```
pausatf-infrastructure-docs/
│
├── README.md                                    # This file - start here
├── CHANGELOG.md                                 # All infrastructure changes (Keep a Changelog format)
│
├── 01-cache-implementation-guide.md             # Cache fix implementation details
├── 02-cache-audit-report.md                     # Pre-fix cache configuration audit
├── 03-cache-verification-report.md              # Production deployment verification
├── 04-security-audit-report.md                  # WordPress theme security assessment
├── 05-server-migration-guide.md                 # 10-phase DigitalOcean migration
├── 06-cloudflare-configuration-guide.md         # Complete CDN configuration
├── 07-performance-optimization-complete.md      # Complete WordPress optimization
├── 08-recommended-upgrades-roadmap.md           # Infrastructure upgrade roadmap
├── 09-google-workspace-email-security.md        # Google Workspace email setup
├── 10-operational-procedures.md                 # Day-to-day operations and maintenance
├── 11-database-maintenance.md                   # Database maintenance and primary keys
├── EXECUTIVE-SUMMARY.md                         # Non-technical stakeholder summary
│
└── deployment-package/
    ├── data_2025_htaccess                       # Production .htaccess
    ├── purge_cloudflare_cache.sh                # Cache purge script
    └── DEPLOYMENT_INSTRUCTIONS.txt              # Deployment steps
```

**Reading Order:** Files are numbered 01-11 in recommended reading sequence. Start with the README, then follow the numbered guides as needed. Check CHANGELOG.md for recent changes.

---

## Key Technical Details

### Cache Control Strategy

**HTML Race Results:**
```apache
Header set Cache-Control "no-cache, no-store, must-revalidate, max-age=0"
Header set Pragma "no-cache"
Header set Expires "0"
Header set CF-Cache-Control "no-cache"
```
**Result:** Never cached, always fresh ✓

**Static Assets (Images, CSS, JS):**
```apache
Header set Cache-Control "public, max-age=2592000, immutable"
```
**Result:** Cached for 30 days (2,592,000 seconds) ✓

### Cloudflare Cache Behavior

**HTML Files:**
- `cf-cache-status: DYNAMIC` (not cached) ✅

**Static Assets:**
- `cf-cache-status: HIT` (cached) ✅

### Critical File Locations

```bash
# Production Server
/var/www/legacy/public_html/data/2025/.htaccess  # Cache headers
/usr/local/bin/purge_cloudflare_cache.sh         # Purge script
/var/log/cloudflare_purge.log                    # Purge log

# WordPress Installation
/var/www/html/                                    # WordPress root
/var/www/html/wp-content/themes/TheSource/        # Parent theme
/var/www/html/wp-content/themes/TheSource-child/  # Active child theme
```

---

## Common Operations

### Test Cache Headers

```bash
# HTML file (should show no-cache)
curl -I https://www.pausatf.org/data/2025/RR5KH2025.html | grep -i cache

# Static asset (should show 30-day cache)
curl -I https://www.pausatf.org/data/2025/sf-rec-park.jpg | grep -i cache
```

### Purge Cloudflare Cache

```bash
# On production server
ssh root@prod.pausatf.org "/usr/local/bin/purge_cloudflare_cache.sh all"

# Specific file
ssh root@prod.pausatf.org "/usr/local/bin/purge_cloudflare_cache.sh 2025/filename.html"

# Check purge log
ssh root@prod.pausatf.org "tail /var/log/cloudflare_purge.log"
```

### Verify Apache Configuration

```bash
# Check mod_headers enabled
ssh root@prod.pausatf.org "apachectl -M | grep headers"

# Test Apache config
ssh root@prod.pausatf.org "apachectl configtest"

# View error log
ssh root@prod.pausatf.org "tail -f /var/log/apache2/error.log"
```

### WordPress Health Check

```bash
# List themes
ssh root@prod.pausatf.org "wp theme list --path=/var/www/html/ --allow-root"

# List plugins
ssh root@prod.pausatf.org "wp plugin list --path=/var/www/html/ --allow-root"

# Check for updates
ssh root@prod.pausatf.org "wp core check-update --path=/var/www/html/ --allow-root"
```

---

## Troubleshooting

### Cache Not Working

**Symptoms:** Users still seeing stale content

**Checks:**
```bash
# 1. Verify .htaccess exists
ssh root@prod.pausatf.org "cat /var/www/legacy/public_html/data/2025/.htaccess"

# 2. Check Apache mod_headers
ssh root@prod.pausatf.org "apachectl -M | grep headers"

# 3. Test headers
curl -I https://www.pausatf.org/data/2025/test.html | grep -i cache

# 4. Purge Cloudflare cache
ssh root@prod.pausatf.org "/usr/local/bin/purge_cloudflare_cache.sh all"
```

### SSH Connection Issues

**Symptoms:** Connection refused on port 22

**Solutions:**
```bash
# Test connectivity
nc -zv prod.pausatf.org 22

# Check from DigitalOcean
doctl compute droplet get 355909945 --format Status

# Use DigitalOcean console if SSH fails
# https://cloud.digitalocean.com/droplets → Launch Droplet Console
```

### WordPress Issues

**See:** [05-server-migration-guide.md](05-server-migration-guide.md) - Troubleshooting section

---

## Support Resources

### Official Documentation
- **DigitalOcean:** https://docs.digitalocean.com/
- **Cloudflare:** https://developers.cloudflare.com/
- **WordPress:** https://wordpress.org/documentation/
- **Apache:** https://httpd.apache.org/docs/2.4/

### API Documentation
- **Cloudflare API:** https://developers.cloudflare.com/api/
- **DigitalOcean API:** https://docs.digitalocean.com/reference/api/

### Tools Used
- **WP-CLI:** https://wp-cli.org/
- **doctl:** https://docs.digitalocean.com/reference/doctl/

---

## Security & Compliance

### Current Security Posture

- **WordPress:** 6.9 (latest) ✅
- **Plugins:** All up to date ✅
- **PHP:** 7.4.33 (EOL - plan upgrade) ⚠️
- **Themes:** No critical vulnerabilities ✅
- **Firewall:** wp-fail2ban + Cloudflare ✅
- **SSL:** Let's Encrypt (auto-renew) ✅

### OWASP Top 10 Compliance

See: [04-security-audit-report.md](04-security-audit-report.md) - Compliance section

### Security Plugins Active
- wp-fail2ban 3.5.3 (brute force protection)
- Cloudflare 4.14.1 (DDoS protection)

---

## Changelog

### 2025-12-20
- ✅ Cache fix deployed to production
- ✅ SSH access restored to production server
- ✅ Cloudflare cache purged
- ✅ TheSource security audit completed
- ✅ Server migration guide created
- ✅ Cloudflare configuration guide created
- ✅ All documentation committed to repository

### Next Review
- **Cache Configuration:** Ongoing monitoring
- **Security Audit:** June 2026 (6 months)
- **PHP Upgrade:** Q2 2026 (plan migration to 8.x)

---

## Contributing

This is a private documentation repository for pausatf.org infrastructure.

**For updates:**
1. SSH into servers to verify current state
2. Update relevant documentation files
3. Commit with descriptive messages
4. Include verification steps and test results

**Commit Message Format:**
```
[Category] Brief description

- Change 1
- Change 2
- Verification: test results

🤖 Generated with Claude Code
Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

---

## License

Internal documentation for pausatf.org infrastructure. Not for public distribution.

**Maintained by:** Thomas Vincent
**Organization:** Pacific Association of USA Track and Field (PAUSATF)
**Last Updated:** 2025-12-21

---

## Quick Links

- **GitHub Repository:** https://github.com/pausatf/pausatf-infrastructure-docs
- **Production Site:** https://www.pausatf.org
- **Staging Site:** https://stage.pausatf.org
- **Cloudflare Dashboard:** https://dash.cloudflare.com/
- **DigitalOcean Console:** https://cloud.digitalocean.com/
