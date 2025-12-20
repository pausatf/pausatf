# PAUSATF.org Cache Fixes Documentation

This repository contains comprehensive documentation and deployment scripts for fixing cache issues on pausatf.org production and staging servers.

**Created:** 2025-12-20
**Purpose:** Address persistent cache issues causing stale race results to be served to users

## Problem Summary

Jeff reported critical cache issues where static HTML race results were showing stale content for extended periods (sometimes years). The issues included:

- Missing or ineffective Cache-Control headers in `/data/2025/` directory
- Static assets cached for 1 year (max-age=31536000) instead of recommended 30 days
- Broken Cloudflare cache purge script with hardcoded URL
- Need for Cloudflare Page Rules to bypass HTML caching

## Repository Contents

### Documentation Files

- **[FINAL_SUMMARY.md](FINAL_SUMMARY.md)** - Executive summary with deployment checklist and current status
- **[cache_fix_documentation.md](cache_fix_documentation.md)** - Comprehensive technical documentation with all fixes and testing procedures
- **[PAUSATF_CACHE_AUDIT_REPORT.md](PAUSATF_CACHE_AUDIT_REPORT.md)** - Complete audit of all cache configurations found on servers
- **[CLOUDFLARE_PAGE_RULES_SETUP.md](CLOUDFLARE_PAGE_RULES_SETUP.md)** - Step-by-step guide for configuring Cloudflare Page Rules

### Deployment Package

The `deployment-package/` directory contains files ready for production deployment:

- **data_2025_htaccess** - Apache .htaccess file with aggressive no-cache headers for HTML race results
- **purge_cloudflare_cache.sh** - Fixed Cloudflare cache purge script (accepts URL parameters)
- **DEPLOYMENT_INSTRUCTIONS.txt** - Manual deployment instructions for production server

## Quick Start

1. **Read the executive summary:** Start with [FINAL_SUMMARY.md](FINAL_SUMMARY.md)
2. **Review deployment status:** Check which fixes have been applied to staging vs production
3. **Production deployment:** Follow instructions in `deployment-package/DEPLOYMENT_INSTRUCTIONS.txt`
4. **Configure Cloudflare:** Use [CLOUDFLARE_PAGE_RULES_SETUP.md](CLOUDFLARE_PAGE_RULES_SETUP.md) to set up Page Rules

## Deployment Status

### ✅ Staging Server (stage.pausatf.org - 64.227.85.73)
- **Droplet:** pausatf-stage
- **SSH:** Available via `ssh root@stage.pausatf.org`
- Cache-Control headers installed in main .htaccess
- Fixed purge script deployed to `/usr/local/bin/purge_cloudflare_cache.sh`
- OpenLiteSpeed cache module verified
- Cloudflare cache purged
- All fixes tested and working

### ⏳ Production Server (ftp.pausatf.org - 64.225.40.54)
- **Droplet:** pausatforg20230516-primary
- **SSH:** ❌ UNAVAILABLE - Port 22 connections refused
- **Access Method:** DigitalOcean Droplet Console only
- Deployment package created for manual installation
- Requires manual deployment following DEPLOYMENT_INSTRUCTIONS.txt
- SSH service needs to be enabled on server (see Step 7 in deployment instructions)

### ⏳ Cloudflare Configuration
- Page Rules need manual configuration via dashboard (API token lacks permission)
- See CLOUDFLARE_PAGE_RULES_SETUP.md for instructions

## Technical Details

### Cache Control Strategy

**HTML Race Results:**
- No caching whatsoever (no-cache, no-store, must-revalidate)
- Cloudflare bypass enforced via CF-Cache-Control header

**Static Assets (images, CSS, JS):**
- 30-day cache (per Jeff's recommendation)
- Immutable flag set for optimal performance
- Versioned filenames recommended for updates (e.g., logo-v2.png)

### Servers

**Production Server:**
- **Hostname:** ftp.pausatf.org
- **IP Address:** 64.225.40.54
- **Droplet:** pausatforg20230516-primary
- **Web Server:** Apache 2.4
- **PHP:** 7.4
- **Document Root:** /var/www/legacy/public_html/
- **SSH Access:** Currently unavailable (port 22 refused)

**Staging Server:**
- **Hostname:** stage.pausatf.org
- **IP Address:** 64.227.85.73
- **Droplet:** pausatf-stage
- **Web Server:** OpenLiteSpeed 1.8.3
- **PHP:** 8.4.15
- **Document Root:** /var/www/html/
- **SSH Access:** Available via `ssh root@stage.pausatf.org`

### Cloudflare Configuration

- **Zone ID:** your-cloudflare-zone-id
- **API Token:** (stored in environment variables)
- **Cache Purge Script:** `/usr/local/bin/purge_cloudflare_cache.sh`

## Support

For questions or issues with deployment:
- Review the comprehensive documentation in [cache_fix_documentation.md](cache_fix_documentation.md)
- Check the troubleshooting sections in deployment instructions
- Verify cache headers using: `curl -I https://www.pausatf.org/data/2025/file.html | grep -i cache`

## License

Internal documentation for pausatf.org infrastructure. Not for public distribution.

---

**Last Updated:** 2025-12-20
**Maintained by:** Thomas Vincent
