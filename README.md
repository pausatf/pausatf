# PAUSATF.org Infrastructure Documentation

**Comprehensive technical documentation for pausatf.org production and staging infrastructure**

**Repository:** https://github.com/pausatf/pausatf-infrastructure-docs
**Created:** 2025-12-20
**Maintained by:** Thomas Vincent

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

### 📋 Executive Summaries

| Document | Description |
|----------|-------------|
| **[FINAL_SUMMARY.md](FINAL_SUMMARY.md)** | Cache fix deployment status and checklist |
| **[CACHE_VERIFICATION_REPORT.md](CACHE_VERIFICATION_REPORT.md)** | Production cache fix verification (Dec 20, 2025) |
| **[THESOURCE_SECURITY_AUDIT.md](THESOURCE_SECURITY_AUDIT.md)** | WordPress theme security assessment |

### 🔧 Technical Guides

| Document | Description | Pages |
|----------|-------------|-------|
| **[SERVER_MIGRATION_GUIDE.md](SERVER_MIGRATION_GUIDE.md)** | Complete 10-phase server migration process | 26 KB |
| **[CLOUDFLARE_CONFIGURATION_GUIDE.md](CLOUDFLARE_CONFIGURATION_GUIDE.md)** | DNS, SSL, caching, firewall, API automation | 23 KB |
| **[cache_fix_documentation.md](cache_fix_documentation.md)** | Cache header fixes and implementation | 15 KB |
| **[CLOUDFLARE_PAGE_RULES_SETUP.md](CLOUDFLARE_PAGE_RULES_SETUP.md)** | Page Rules configuration for race results | 2.7 KB |

### 📊 Audit Reports

| Document | Description |
|----------|-------------|
| **[PAUSATF_CACHE_AUDIT_REPORT.md](PAUSATF_CACHE_AUDIT_REPORT.md)** | Complete cache configuration audit |
| **[THESOURCE_SECURITY_AUDIT.md](THESOURCE_SECURITY_AUDIT.md)** | WordPress theme security scan (9 findings) |

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

1. **Read status:** [FINAL_SUMMARY.md](FINAL_SUMMARY.md) - What's been done, what's pending
2. **Deploy to production:** Follow `deployment-package/DEPLOYMENT_INSTRUCTIONS.txt`
3. **Configure Cloudflare:** Use [CLOUDFLARE_PAGE_RULES_SETUP.md](CLOUDFLARE_PAGE_RULES_SETUP.md)
4. **Verify deployment:** Commands in [CACHE_VERIFICATION_REPORT.md](CACHE_VERIFICATION_REPORT.md)

### For Server Migration

1. **Planning phase:** Review [SERVER_MIGRATION_GUIDE.md](SERVER_MIGRATION_GUIDE.md)
2. **Cloudflare setup:** [CLOUDFLARE_CONFIGURATION_GUIDE.md](CLOUDFLARE_CONFIGURATION_GUIDE.md)
3. **Backup first:** Always create DigitalOcean snapshots before migration
4. **Test thoroughly:** Validate on new server before DNS cutover

### For Security Review

1. **Theme audit:** [THESOURCE_SECURITY_AUDIT.md](THESOURCE_SECURITY_AUDIT.md)
2. **Risk level:** 🟡 Medium (2 high priority items to address)
3. **Quick fixes:** ~10 minutes to resolve main issues
4. **Next review:** June 2026 (6 months)

---

## Server Environment

### Production Server

- **Hostname:** ftp.pausatf.org
- **IP Address:** REDACTED_PROD_NEW_IP
- **Droplet:** pausatforg20230516-primary (DigitalOcean)
- **Web Server:** Apache 2.4
- **PHP:** 7.4.33
- **WordPress:** 6.9
- **Document Root:** /var/www/legacy/public_html/
- **SSH Access:** ✅ Available via `ssh root@ftp.pausatf.org`

### Staging Server

- **Hostname:** stage.pausatf.org
- **IP Address:** REDACTED_STAGE_IP
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
- ✅ Cloudflare respecting cache directives
- ✅ All verification tests passing

**Server Access:**
- ✅ SSH restored to production server (ftp.pausatf.org)
- ✅ Staging server fully accessible (stage.pausatf.org)

**Security Audit:**
- ✅ TheSource themes scanned (no critical vulnerabilities)
- ✅ All WordPress plugins up to date
- ✅ PHP deprecated functions check (all clear)

### ⏳ Pending Items

**Cloudflare Configuration:**
- ⏳ Page Rules need manual setup (API token lacks permission)
- See: [CLOUDFLARE_PAGE_RULES_SETUP.md](CLOUDFLARE_PAGE_RULES_SETUP.md)

**Theme Cleanup (Optional):**
- ⏳ Remove IE6 PNG fix library (5 min)
- ⏳ Delete backup files from child theme (5 min)
- See: [THESOURCE_SECURITY_AUDIT.md](THESOURCE_SECURITY_AUDIT.md)

**PHP Upgrade (Long-term):**
- ⏳ PHP 7.4 reached EOL (Nov 2022)
- ⏳ Plan migration to PHP 8.1 or 8.3
- See: [SERVER_MIGRATION_GUIDE.md](SERVER_MIGRATION_GUIDE.md)

---

## Documentation Structure

```
pausatf-infrastructure-docs/
│
├── README.md                              # This file
│
├── Executive Summaries/
│   ├── FINAL_SUMMARY.md                   # Cache fix status
│   ├── CACHE_VERIFICATION_REPORT.md       # Deployment verification
│   └── THESOURCE_SECURITY_AUDIT.md        # Security assessment
│
├── Technical Guides/
│   ├── SERVER_MIGRATION_GUIDE.md          # 10-phase migration
│   ├── CLOUDFLARE_CONFIGURATION_GUIDE.md  # Complete CDN setup
│   ├── cache_fix_documentation.md         # Cache implementation
│   └── CLOUDFLARE_PAGE_RULES_SETUP.md     # Page Rules guide
│
├── Audit Reports/
│   ├── PAUSATF_CACHE_AUDIT_REPORT.md      # Cache config audit
│   └── THESOURCE_SECURITY_AUDIT.md        # Theme security scan
│
└── deployment-package/
    ├── data_2025_htaccess                 # Production .htaccess
    ├── purge_cloudflare_cache.sh          # Cache purge script
    └── DEPLOYMENT_INSTRUCTIONS.txt        # Deployment steps
```

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
ssh root@ftp.pausatf.org "/usr/local/bin/purge_cloudflare_cache.sh all"

# Specific file
ssh root@ftp.pausatf.org "/usr/local/bin/purge_cloudflare_cache.sh 2025/filename.html"

# Check purge log
ssh root@ftp.pausatf.org "tail /var/log/cloudflare_purge.log"
```

### Verify Apache Configuration

```bash
# Check mod_headers enabled
ssh root@ftp.pausatf.org "apachectl -M | grep headers"

# Test Apache config
ssh root@ftp.pausatf.org "apachectl configtest"

# View error log
ssh root@ftp.pausatf.org "tail -f /var/log/apache2/error.log"
```

### WordPress Health Check

```bash
# List themes
ssh root@ftp.pausatf.org "wp theme list --path=/var/www/html/ --allow-root"

# List plugins
ssh root@ftp.pausatf.org "wp plugin list --path=/var/www/html/ --allow-root"

# Check for updates
ssh root@ftp.pausatf.org "wp core check-update --path=/var/www/html/ --allow-root"
```

---

## Troubleshooting

### Cache Not Working

**Symptoms:** Users still seeing stale content

**Checks:**
```bash
# 1. Verify .htaccess exists
ssh root@ftp.pausatf.org "cat /var/www/legacy/public_html/data/2025/.htaccess"

# 2. Check Apache mod_headers
ssh root@ftp.pausatf.org "apachectl -M | grep headers"

# 3. Test headers
curl -I https://www.pausatf.org/data/2025/test.html | grep -i cache

# 4. Purge Cloudflare cache
ssh root@ftp.pausatf.org "/usr/local/bin/purge_cloudflare_cache.sh all"
```

### SSH Connection Issues

**Symptoms:** Connection refused on port 22

**Solutions:**
```bash
# Test connectivity
nc -zv ftp.pausatf.org 22

# Check from DigitalOcean
doctl compute droplet get REDACTED_DROPLET_ID --format Status

# Use DigitalOcean console if SSH fails
# https://cloud.digitalocean.com/droplets → Launch Droplet Console
```

### WordPress Issues

**See:** [SERVER_MIGRATION_GUIDE.md](SERVER_MIGRATION_GUIDE.md) - Troubleshooting section

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

See: [THESOURCE_SECURITY_AUDIT.md](THESOURCE_SECURITY_AUDIT.md) - Compliance section

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
**Last Updated:** 2025-12-20

---

## Quick Links

- **GitHub Repository:** https://github.com/pausatf/pausatf-infrastructure-docs
- **Production Site:** https://www.pausatf.org
- **Staging Site:** https://stage.pausatf.org
- **Cloudflare Dashboard:** https://dash.cloudflare.com/
- **DigitalOcean Console:** https://cloud.digitalocean.com/
