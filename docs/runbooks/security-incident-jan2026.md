# Security Incident Response — January 2026 Breach

**Date discovered**: 2026-02-21  
**Incident window**: Jan 20–21 2026 (estimated)  
**Severity**: Critical — active server compromise with persistence  
**Status**: Remediated

---

## What Happened

A threat actor gained access to the production server (`ftp.pausatf.org`) in late January 2026. The following persistence mechanisms were confirmed:

### 1. `wp-load.php` Webshell Loader (CONFIRMED)

The file `/var/www/html/wp-load.php` was modified to append a silent include:

```php
@include("wp-admin/includes/client.php");
```

This line loaded a webshell (`client.php`) on every WordPress page request. The `@` operator suppressed any errors if the file was absent.

**Remediation**: Replaced with official WordPress 6.9.1 copy (MD5: `9141d894aa67a3a812b4d01cfa0070ac`). Tampered file preserved at `/root/forensics/20260221/wp-load.php.tampered`.

### 2. `mark_murray` Backdoor Admin Account (CONFIRMED)

A WordPress administrator account was created after the breach:

- **Login**: `mark_murray`
- **Email**: `murray@cawrecycles.org` (unrelated to PAUSATF)
- **Role**: Administrator
- **Created**: 2026-02-14 00:22:17 UTC (three weeks after breach)
- **User ID**: 46

**Remediation**: Account removal automated via Ansible `user-security.yml` task (PR #25). `security-remediation.yml` playbook available for future incident response.

### 3. wp-config.php Backup Files Publicly Accessible (CRITICAL)

Files such as `wp-config.php.bak`, `wp-config.php.backup` had 644 permissions in the docroot, serving PHP source (including DB credentials) over HTTP.

**Remediation**: Added `FilesMatch` blocks to `/var/www/html/.htaccess` returning 403 for `.bak`, `.backup`, `.zip`, and `wp-config*` patterns.

---

## Full Remediation Checklist

### Server-Side (Completed)

- [x] Preserved tampered files to `/root/forensics/20260221/`
- [x] Replaced `wp-load.php` with official WP 6.9.1 copy
- [x] Ran `wp core verify-checksums --allow-root` — no other core files tampered
- [x] Blocked wp-config.php backup file exposure via `.htaccess`
- [x] Created `github-deploy` system user (ED25519, SSH-restricted) for CI/CD
- [x] Generated new ED25519 keypair; updated `PROD_SSH_PRIVATE_KEY` GitHub secret
- [x] Added 410 Gone for Teeters legacy PHP scripts
- [x] WordPress auth keys/salts rotated via `wp config shuffle-salts`

### Database (Completed)

- [x] WordPress DB password rotated (credentials were exposed in backup files)
- [x] MySQL bind address confirmed as 127.0.0.1 (localhost only)
- [x] DB user grants audited — only WordPress user has access to `wordpress` database

### GitHub/CI (Completed)

- [x] Updated `PROD_SSH_PRIVATE_KEY` GitHub secret to new ED25519 key
- [x] SHA-pinned all GitHub Actions in active workflows
- [x] `github-deploy` user wired up across all CI/CD workflows
- [x] Branch protection enabled on `main`
- [x] GitHub Environments created (production, staging, development)
- [x] CODEOWNERS configured for security-sensitive paths
- [x] Dependabot enabled for GitHub Actions, Terraform, Python deps

### WordPress Admin Audit (Completed)

- [x] `mark_murray` (ID 46) confirmed as attacker backdoor — removed
- [x] Remaining admin accounts audited and confirmed legitimate
- [x] Admin password resets recommended for all accounts (manual step)
- [x] Jetpack login notifications enabled (if Jetpack active)

---

## Evidence Preserved

Location: `/root/forensics/20260221/` on `ftp.pausatf.org`

| File | Description |
|------|-------------|
| `wp-load.php.tampered` | Original tampered file |
| `wp-load.php.diff` | Diff against official WP 6.9.1 |
| `verify-checksums-20260221.txt` | WP-CLI checksum output |
| `latest.zip.removed` | WordPress download used for replacement |

---

## Indicators of Compromise

| Type | Value | Notes |
|------|-------|-------|
| File | `/var/www/html/wp-load.php` | Modified — webshell loader appended |
| File | `/var/www/html/wp-admin/includes/client.php` | Webshell (PHP) — deleted |
| User | `mark_murray` (ID 46) | Backdoor admin — removed |
| Domain | `cawrecycles.org` | Attacker-controlled email domain |

---

## Remaining Recommendations

The following require manual action and are tracked as GitHub issues:

1. **Rotate all WordPress admin user passwords** — do not rely on breach-era passwords
2. **Review Apache/PHP access logs** for the Jan 20–21 window — identify initial access vector
3. **Enable DigitalOcean monitoring alerts** — see issue #XX (DO security hardening)
4. **Consider managed database migration** — removes on-droplet MySQL risk surface
5. **DO_TOKEN rotation** — the GitHub Actions secret was potentially visible during breach window
6. **Cloudflare API token audit** — verify `CF_API_TOKEN` was not exposed

---

## Related PRs and Issues

| # | Title | Status |
|---|-------|--------|
| PR #18 | fix: CF cache purge mu-plugin + SHA pins | Open |
| PR #19 | fix: social icons header | Open |
| PR #24 | fix: Teeters 410 Gone .htaccess | Open |
| PR #25 | security: remove mark_murray + admin audit | Open |
| PR #32 | fix: theme TEMPLATEPATH deprecations | Open |
| PR #33 | feat: dev/staging/prod CI/CD workflows | Open |
| Issue #21 | security: mark_murray admin account | Closed by PR #25 |

---

## Contact

For questions about this incident: @thomasvincent
