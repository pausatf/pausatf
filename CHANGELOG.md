# Changelog

All notable changes to the PAUSATF.org infrastructure and documentation are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to semantic versioning principles for infrastructure changes.

---

## [Unreleased]

---

## [2025-12-21] - WordPress Security Audit and Remediation

### Added
- Comprehensive WordPress security audit documentation (14-wordpress-security-audit-2025.md)
- Complete backup of WordPress installation (13.5 GB total):
  - Database backup: 438 MB (`pausatf-db-backup-20251221-163238.sql`)
  - WordPress files backup: 12 GB (`pausatf-files-backup-20251221-163300.tar.gz`)
  - Legacy directory backup: 1.1 GB (`pausatf-legacy-backup-20251221-164348.tar.gz`)
- Administrator user account export to CSV for review
- Detailed security posture assessment with actionable recommendations
- Backup retention and recovery procedures documented

### Changed
- wp-config.php file permissions: 644 → 640 (removed world-readable access)
- Plugin auto-update configuration: enabled for google-site-kit, wp-super-cache, advanced-database-cleaner
- All plugins now configured with auto-updates enabled

### Removed
- Unexpected files from WordPress core:
  - `wp-admin/false` (PHP error log, 878 bytes)
  - `wp-admin/.rnd` (OpenSSL random seed file, 1024 bytes)
  - `wp-includes/SimplePie/src/Core.php` (legacy SimplePie file, 2235 bytes)
- Test user account: thomastest (subscriber, user ID 43)

### Security
- **CRITICAL:** Secured wp-config.php to prevent unauthorized access to database credentials
- Eliminated suspicious files that could be potential security vectors
- Removed unnecessary user account reducing attack surface
- Verified WordPress core integrity (checksums pass)
- Documented 11 administrator accounts for organizational review (security concern)
- All 20 active plugins verified and up-to-date
- wp-fail2ban and Jetpack security features confirmed active
- Cloudflare WAF and DDoS protection verified operational

### Fixed
- Database optimization warnings (non-critical schema issues documented)
- File permission vulnerabilities in wp-config.php
- Plugin auto-update gaps that could delay security patches

### Performance
- Database integrity check: All 133 tables verified OK
- Disk usage audit: 22 GB total WordPress installation
  - 3.0 GB uploads
  - 309 MB plugins
  - 14 MB themes

### Recommendations Implemented
- ✅ wp-config.php permissions hardened
- ✅ Unexpected WordPress core files removed
- ✅ Test user account deleted
- ✅ Administrator accounts exported for review
- ✅ All plugin auto-updates enabled
- ⏳ Administrator account reduction (11 → 3-5) - pending organizational review
- ⏳ Two-factor authentication implementation - recommended
- ⏳ Off-site backup storage configuration - recommended

### Documentation
- Added WordPress security audit to README completed projects
- Updated documentation index with document #14
- All changes tracked in detailed change log within audit report
- Backup restoration procedures documented
- Next steps and recommendations documented for short-term and long-term actions

---

## [2025-12-21] - Database Maintenance & Primary Keys

### Added
- Database maintenance documentation (11-database-maintenance.md)
- Comprehensive guide for DigitalOcean MySQL primary key requirements
- Troubleshooting procedures for database issues
- Monthly maintenance checklist for database health
- Preventive measures for plugin-created tables

### Fixed
- **CRITICAL:** Added primary keys to 7 tables in `pausatf-stage-db`
  - `wp_statistics_pages` (738,450 rows) - PRIMARY KEY on (uri, date)
  - `wp_yoast_seo_meta` (1,409 rows) - PRIMARY KEY on object_id
  - `wp_nxs_log` (696 rows) - PRIMARY KEY on id
  - `wp_cjtoolbox_form_group_xfields` - PRIMARY KEY on groupId
  - `wp_nxs_query` - PRIMARY KEY on id
  - `wp_wsluserscontacts` - PRIMARY KEY on id
  - `wp_wslusersprofiles` - PRIMARY KEY on id

### Security
- Resolved data loss risk for wp_statistics_pages (well above 5,000 row threshold)
- Improved database replication stability
- All tables now comply with DigitalOcean MySQL requirements

### Performance
- Eliminated replication lag caused by missing primary keys
- Improved query performance on previously unindexed tables

---

## [2025-12-20] - DNS Security Enhancements

### Added
- Server right-sizing analysis document (11-server-rightsizing-analysis.md)
- Daily automated backups enabled on both production and staging servers
- DigitalOcean backup policy configured for nightly backups
- Comprehensive cost optimization analysis ($460.80/year potential savings)
- Resource usage monitoring and baseline documentation
- Migration checklist for future right-sizing efforts

### Changed
- Production disk usage: 71 GB → 39 GB (freed 32 GB of 2019 backups)
- Staging disk usage: 48 GB → 26 GB (freed 22 GB of test installations)
- Server specifications updated in README with resource utilization metrics
- Backup status documented across all infrastructure guides

### Removed
- Production: html_clean.tar (7.5 GB), html_HACKED_wordfence.tar (7.5 GB), legacy_wordfence_backup.tar (1.7 GB), wp-content_wordfence_backup.tar (647 MB), transit/ directory (15 GB)
- Staging: html.land test installation (22 GB), html.backup (129 MB)
- Total disk space freed: 54 GB across both servers

### Cost Analysis
- Current monthly costs: $86.40 ($57.60 production + $28.80 staging)
- Right-sized potential: $48.00/month (44% reduction)
- Annual savings potential: $460.80/year if migrated to optimal droplet sizes
- Note: DigitalOcean disk constraints prevent in-place downsizing (requires migration)

---

## [2025-12-21] - Server Right-Sizing and Backup Configuration

### Added
- CNAME record for prod.pausatf.org pointing to production server
- SPF record for email authentication (Google Workspace + SendGrid)
- CAA records for SSL certificate authority authorization:
  - Let's Encrypt (server certificates)
  - DigiCert (Cloudflare certificates)
  - Wildcard certificate support for both
  - Violation reporting via email (iodef)
- DNS best practices section in Cloudflare configuration guide
- Executive summary document for non-technical stakeholders
- Apache → OpenLiteSpeed migration rationale with performance comparison

### Changed
- All documentation references updated from ftp.pausatf.org to prod.pausatf.org
- Expanded Apache → OpenLiteSpeed migration section with real performance data
- Documentation now includes actual resource usage metrics and cost breakdowns

### Security
- Implemented DNS security best practices (SPF, CAA)
- Protected domain from unauthorized SSL certificate issuance
- Enhanced email authentication to prevent spoofing
- Daily backups now protecting against data loss

---

## [2025-12-20] - Infrastructure Documentation Package

### Added
- Comprehensive infrastructure documentation repository created
- 08 numbered documentation files covering all aspects of infrastructure
- Deployment package with ready-to-deploy cache fixes
- Recommended upgrades roadmap for 2026

### Changed
- Repository renamed from `cache-fixes-documentation` to `pausatf-infrastructure-docs`
- Documentation reorganized with numbered sequence (01-08)
- DigitalOcean droplet renamed from `pausatforg20230516-primary` to `pausatf-prod`
- All documentation updated with accurate server specifications

### Fixed
- SSH access status corrected across all documentation (was incorrectly marked as blocked)
- PHP version updated to 7.4.33 where previously incomplete
- Ubuntu OS version documented as 20.04 LTS
- Droplet naming clarified (DigitalOcean name vs server hostname)

---

## [2025-12-20] - Performance Optimization

### Added
- WP Super Cache 3.0.3 installed and configured
- Database optimization for autoloaded options
- Comprehensive performance monitoring
- Performance optimization documentation (07-performance-optimization-complete.md)

### Changed
- Autoloaded options reduced from 4.54 MB to 298 KB (93% reduction)
- Jetpack modules optimized from 35 to 28 active modules
- `widget_custom_html` option set to not autoload (freed 4.04 MB)

### Performance Improvements
- Server response time: 1,357ms → ~220ms (6x faster)
- Admin dashboard: 1,490ms → 232ms (84% faster)
- Homepage maintained at ~220ms (well below 600ms threshold)
- All WordPress Site Health critical issues resolved

### Removed
- 8 unnecessary Jetpack modules disabled:
  - blaze
  - woocommerce-analytics
  - videopress
  - latex
  - markdown
  - notes
  - gravatar-hovercards
  - json-api

---

## [2025-12-20] - Cache Fix Deployment

### Added
- Improved .htaccess file deployed to production `/data/2025/`
- Cloudflare cache purge script with argument support
- CF-Cache-Control headers for Cloudflare-specific directives
- Cache verification documentation (03-cache-verification-report.md)

### Changed
- HTML race results: Never cached (aggressive no-cache headers)
- Static assets: Cached for 30 days (reduced from 1 year)
- Cache-Control headers now include multiple directives for maximum compatibility

### Fixed
- Race results now always show fresh content (no more year-old cached data)
- Cloudflare respecting origin cache directives via CF-Cache-Control
- All cache verification tests passing

### Security
- Directory listing disabled in race results directory
- Sensitive files (.htaccess, .htpasswd, .git) blocked from access

---

## [2025-12-20] - Security Audit

### Added
- TheSource theme security audit report (04-security-audit-report.md)
- OWASP Top 10 compliance assessment
- PHP deprecated functions check

### Security Findings
- **Risk Level:** Medium (no critical vulnerabilities)
- **Findings:** 9 total (2 high priority, 7 informational)
- **High Priority Items:**
  1. IE6 PNG fix library (outdated, no longer needed)
  2. Backup files in child theme directory

### Verified Secure
- No SQL injection vulnerabilities
- No XSS (Cross-Site Scripting) vulnerabilities
- No CSRF (Cross-Site Request Forgery) issues
- No file inclusion vulnerabilities
- All WordPress plugins up to date
- wp-fail2ban 3.5.3 active (brute force protection)
- Cloudflare 4.14.1 active (DDoS protection)

---

## [2025-12-20] - Infrastructure Documentation

### Added
- 01-cache-implementation-guide.md - Complete cache fix implementation
- 02-cache-audit-report.md - Pre-fix cache configuration audit
- 03-cache-verification-report.md - Production deployment verification
- 04-security-audit-report.md - WordPress theme security assessment
- 05-server-migration-guide.md - 10-phase DigitalOcean migration process
- 06-cloudflare-configuration-guide.md - Complete CDN configuration
- 07-performance-optimization-complete.md - WordPress optimization guide
- 08-recommended-upgrades-roadmap.md - Infrastructure upgrade roadmap
- README.md - Comprehensive repository guide
- CHANGELOG.md - This file

### Changed
- Repository description updated to reflect comprehensive infrastructure scope
- Documentation organized in logical numbered sequence
- All cross-references updated throughout documentation

### Removed
- CLOUDFLARE_PAGE_RULES_SETUP.md (consolidated into 06-cloudflare-configuration-guide.md)
- FINAL_SUMMARY.md (superseded by 03-cache-verification-report.md)
- Redundant performance reports (consolidated into 07-performance-optimization-complete.md)

---

## [2025-12-06 to 2025-09-14] - Historical Changes

### Infrastructure Changes
- Production server cache purge script enhanced (Sep 14, 2025)
- Multiple Cloudflare cache purges performed
- Cache configuration iteratively improved
- WordPress 6.9 upgraded from earlier version
- PHP 7.4.33 maintained (latest 7.4.x release)

---

## Infrastructure Inventory

### Production Server (prod.pausatf.org)
- **IP Address:** 64.225.40.54
- **Droplet:** pausatf-prod (DigitalOcean)
- **Region:** San Francisco (sfo2)
- **Size:** 8GB RAM / 4 vCPUs / 160GB SSD
- **OS:** Ubuntu 20.04 LTS
- **Web Server:** Apache 2.4
- **PHP:** 7.4.33 (EOL - upgrade planned Q1 2026)
- **WordPress:** 6.9 (latest)
- **SSH:** Port 22 (active)

### Staging Server (stage.pausatf.org)
- **IP Address:** 64.227.85.73
- **Droplet:** pausatf-stage (DigitalOcean)
- **Region:** San Francisco (sfo2)
- **Web Server:** OpenLiteSpeed 1.8.3
- **PHP:** 8.4.15 (latest)
- **WordPress:** 6.9 (latest)
- **SSH:** Port 22 (active)

### Cloudflare CDN
- **Zone ID:** your-cloudflare-zone-id
- **Plan:** Free
- **DNS Records:**
  - pausatf.org → 64.225.40.54 (proxied)
  - www.pausatf.org → 64.225.40.54 (proxied)
  - prod.pausatf.org → CNAME to ftp.pausatf.org (not proxied)
  - ftp.pausatf.org → 64.225.40.54 (not proxied)
  - stage.pausatf.org → 64.227.85.73 (not proxied)

---

## Planned Changes (2026)

### Q1 2026
- [ ] PHP 8.3 upgrade on production server
- [ ] Database optimization and cleanup
- [ ] Theme code cleanup (remove IE6 PNG fix, backup files)
- [ ] Security hardening phase 1 (2FA, security headers)
- [ ] Monitor resource usage trends for right-sizing validation

### Q2 2026
- [ ] Ubuntu 24.04 LTS migration (new droplet)
- [ ] Server right-sizing migration (combined with Ubuntu upgrade)
  - Production: 8GB → 4GB RAM droplet (save $307/year)
  - Staging: 4GB → 2GB RAM droplet (save $96/year)
  - Total savings: $403/year (44% cost reduction)
- [ ] Security hardening phase 2 (malware scanning, IDS)
- [ ] Performance optimization phase 2 (image optimization)
- [ ] Apache → OpenLiteSpeed migration evaluation

### Q3 2026
- [ ] Monitoring implementation (uptime, APM, RUM)
- [ ] Consider Apache → OpenLiteSpeed migration
- [ ] Ongoing maintenance and optimization

### Q4 2026
- [ ] Year-end infrastructure review
- [ ] Security audit update
- [ ] Plan 2027 roadmap

---

## Version History

| Date | Version | Description |
|------|---------|-------------|
| 2025-12-20 | 1.0.0 | Initial comprehensive documentation package |
| 2025-12-20 | 1.1.0 | Performance optimization complete (93% faster) |
| 2025-12-20 | 1.2.0 | Documentation accuracy audit and corrections |
| 2025-12-20 | 1.3.0 | Upgrade roadmap added, prod.pausatf.org CNAME |

---

## Links

- **GitHub Repository:** https://github.com/pausatf/pausatf-infrastructure-docs
- **Production Site:** https://www.pausatf.org
- **Staging Site:** https://stage.pausatf.org
- **Cloudflare Dashboard:** https://dash.cloudflare.com/
- **DigitalOcean Console:** https://cloud.digitalocean.com/

---

## Contributing

For infrastructure changes:
1. Test on staging environment first
2. Create DigitalOcean snapshot before production changes
3. Document all changes in this CHANGELOG
4. Update relevant documentation files
5. Commit with descriptive messages
6. Include verification steps

**Commit Message Format:**
```
[Category] Brief description

- Change 1
- Change 2
- Verification: test results

```

---

**Maintained by:** Thomas Vincent
**Organization:** Pacific Association of USA Track and Field (PAUSATF)
**Repository:** https://github.com/pausatf/pausatf-infrastructure-docs
**Format:** [Keep a Changelog](https://keepachangelog.com/)
**Last Updated:** 2025-12-20
