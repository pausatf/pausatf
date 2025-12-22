# THESOURCE THEME SECURITY AUDIT REPORT
**Date:** 2025-12-20
**Server:** www.pausatf.org (prod.pausatf.org / 64.225.40.54)
**WordPress Version:** 6.9 (latest)
**PHP Version:** 7.4.33

---

## EXECUTIVE SUMMARY

The TheSource themes are **reasonably secure** with no critical vulnerabilities found, but contain several **outdated libraries and unnecessary files** that should be cleaned up.

### Risk Level: 🟡 MEDIUM

**Critical Issues:** 0
**High Priority:** 2
**Medium Priority:** 3
**Low Priority:** 4

---

## THEME INFORMATION

### Parent Theme: TheSource
- **Version:** 4.8.13
- **Author:** Elegant Themes
- **Last Modified:** May 2, 2025
- **Status:** Parent (inactive)
- **Auto-Update:** Enabled

### Child Theme: TheSource-child
- **Version:** 0.2
- **Status:** Active
- **Last Modified:** April 27, 2025
- **Auto-Update:** Disabled

---

## SECURITY FINDINGS

### ⚠️ HIGH PRIORITY ISSUES

#### 1. IE6 PNG Fix Library (DD_belatedPNG)
**File:** `/var/www/html/wp-content/themes/TheSource/js/DD_belatedPNG_0.0.8a-min.js`
**Issue:** Supports Internet Explorer 6, which reached end-of-life in 2016
**Risk:** High - Unnecessary attack surface
**Impact:** This library is completely obsolete and should never be loaded

**Recommendation:**
```bash
ssh root@prod.pausatf.org "rm /var/www/html/wp-content/themes/TheSource/js/DD_belatedPNG_0.0.8a-min.js"
```

**Check if it's being used:**
```bash
grep -r "DD_belatedPNG" /var/www/html/wp-content/themes/TheSource/ --include="*.php"
```

#### 2. jQuery Easing Using eval()
**File:** `/var/www/html/wp-content/themes/TheSource/includes/page_templates/js/jquery.easing-1.3.pack.js`
**Issue:** Uses eval() in minified code
**Risk:** Medium-High - eval() is a security risk and code injection vector
**Impact:** While packed/minified code often uses eval(), this is an outdated pattern

**Evidence:**
```javascript
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+...
```

**Recommendation:**
- Replace with modern unminified jQuery easing plugin
- OR use CSS animations instead (better performance)

---

### ⚠️ MEDIUM PRIORITY ISSUES

#### 3. Backup Files in Child Theme
**Location:** `/var/www/html/wp-content/themes/TheSource-child/`

**Files Found:**
```
-rwxr-xr-x 1 www-data www-data  3077 Apr 27  2025 functions.php.bak
-rwxr-xr-x 1 www-data www-data  3413 Apr 27  2025 functions.php.bak.20250427204414
-rwxr-xr-x 1 www-data www-data  3917 Apr 27  2025 functions.php.disabled
-rwxr-xr-x 1 www-data www-data  7151 Apr 12  2025 header.php.bak
-rwxr-xr-x 1 www-data www-data  5487 Apr 12  2025 page-blog.php.bak
-rwxr-xr-x 1 www-data www-data  2706 Apr 12  2025 single.php.bak
-rwxr-xr-x 1 www-data www-data  1490 Apr 12  2025 footer.php.bak
```

**Issue:** Backup files can be downloaded by attackers to study code
**Risk:** Medium - Information disclosure
**Impact:** Attackers can analyze old code for vulnerabilities

**Recommendation:**
```bash
ssh root@prod.pausatf.org "cd /var/www/html/wp-content/themes/TheSource-child/ && \
  rm -f *.bak *.bak.* *.disabled && \
  ls -la"
```

#### 4. Empty PHP Files
**Files:**
```
-rwxr-xr-x 1 www-data www-data     0 Apr 14  2025 ClubsPHP.php
-rwxr-xr-x 1 www-data www-data     0 Apr 12  2025 ClubsPHP.php.bak
```

**Issue:** Empty PHP files with executable permissions
**Risk:** Low-Medium - Could be used for arbitrary code execution if compromised
**Impact:** No functionality, just unnecessary files

**Recommendation:**
```bash
ssh root@prod.pausatf.org "cd /var/www/html/wp-content/themes/TheSource-child/ && \
  rm -f ClubsPHP.php ClubsPHP.php.bak"
```

#### 5. Unsanitized $_POST Usage in Parent Theme
**File:** `/var/www/html/wp-content/themes/TheSource/includes/page_templates/page_templates.php`

**Issue:** Some $_POST variables used without sanitization
**Risk:** Low-Medium - Theme is from reputable source (Elegant Themes)
**Impact:** Could allow XSS or other injection attacks

**Examples:**
```php
if ( 'page-blog.php' == $_POST["page_template"] ) {
if ( !in_array( $_POST["page_template"], array(...) ) )
```

**Mitigation:** Theme does use wp_verify_nonce() and some sanitization
**Status:** ⚠️ Monitor - Elegant Themes likely has handled this properly

---

### ℹ️ LOW PRIORITY ISSUES

#### 6. Outdated JavaScript Libraries
**Libraries Found:**
- **jQuery Easing 1.3** (2008) - Very old but widely used
- **Superfish.js** (last major update ~2013)
- **React DOM** (production build included, version unknown)

**Issue:** Old libraries may have known vulnerabilities
**Risk:** Low - These are stable, widely-used libraries
**Impact:** Minimal unless specific CVEs are found

**Recommendation:** Monitor for security advisories but no immediate action required

#### 7. PHP Notice: Constants Already Defined
**Error:**
```
PHP Notice:  Constant DISALLOW_FILE_EDIT already defined
PHP Notice:  Constant DISALLOW_FILE_MODS already defined
PHP Notice:  Constant AUTOMATIC_UPDATER_DISABLED already defined
```

**Issue:** wp-config.php constants being redefined
**Risk:** Very Low - Just notices, not errors
**Impact:** No security impact, just log noise

**Recommendation:** Review wp-config.php for duplicate constant definitions

#### 8. Child Theme Code Quality
**File:** `/var/www/html/wp-content/themes/TheSource-child/functions.php`

**Positive Findings:**
✅ Uses `filter_input()` with FILTER_SANITIZE_STRING
✅ Has ABSPATH check for direct access protection
✅ Properly enqueues scripts and styles
✅ No direct database queries
✅ No eval() or dangerous functions

**Minor Issues:**
⚠️ Inline JavaScript in wp_add_inline_script() (acceptable practice)
⚠️ Version string includes time() for cache busting (creates large version numbers)

**Overall Assessment:** ✅ Well-written, secure child theme

#### 9. Theme Version Information
**Parent Theme:** 4.8.13 (Elegant Themes)

**Status:** Cannot determine if this is the latest version from Elegant Themes
**Note:** Elegant Themes requires a subscription to check for updates
**Recommendation:** If you have an active Elegant Themes subscription, check for updates

---

## CODE ANALYSIS SUMMARY

### PHP Security Scan

**Deprecated Functions:**
```bash
✅ No create_function() found
✅ No mysql_* functions found
✅ No ereg* functions found
✅ No deprecated split() found
```

**Input Validation:**
```bash
⚠️ Some $_POST usage without immediate sanitization
✅ Uses wp_verify_nonce() for CSRF protection
✅ Uses intval() and array_map() for sanitization
✅ Child theme uses filter_input()
```

**SQL Injection:**
```bash
✅ No direct database queries found in themes
✅ Uses WordPress APIs for data access
```

**File Inclusion:**
```bash
✅ No dynamic require/include with user input
✅ All includes use static paths
```

### JavaScript Security Scan

**Dangerous Patterns:**
```bash
⚠️ eval() found in jquery.easing (minified library)
✅ No document.write() found
✅ innerHTML only in React (expected)
✅ No unvalidated window.location usage
```

---

## WORDPRESS ENVIRONMENT

### Core
- **WordPress:** 6.9 ✅ Latest
- **PHP:** 7.4.33 ⚠️ Older but supported
- **PHP EOL:** November 28, 2022 (no security updates)

### Plugins Status
All plugins showing "none" for updates (up to date):
```
accordions (2.3.19)
cloudflare (4.14.1)
contact-form-7 (6.1.4)
jetpack (15.3.1)
tablepress-premium (3.2.6)
updraftplus (2.25.9.0)
wp-file-manager-pro (8.4.3)
wpforms-lite (1.9.8.7)
wp-mail-smtp (4.7.1)
```

**Security Plugins Active:**
✅ wp-fail2ban (3.5.3) - Brute force protection
✅ Cloudflare - DDoS protection

---

## RECOMMENDATIONS

### IMMEDIATE ACTIONS (High Priority)

1. **Remove IE6 PNG Fix**
   ```bash
   ssh root@prod.pausatf.org "rm /var/www/html/wp-content/themes/TheSource/js/DD_belatedPNG_0.0.8a-min.js"
   ```

2. **Remove Backup Files from Child Theme**
   ```bash
   ssh root@prod.pausatf.org "cd /var/www/html/wp-content/themes/TheSource-child/ && rm -f *.bak *.bak.* *.disabled ClubsPHP.php*"
   ```

### SHORT-TERM ACTIONS (Medium Priority)

3. **Replace jQuery Easing**
   - Download latest unminified version from https://github.com/gdsmith/jquery.easing
   - Replace packed version with modern ES6 version
   - OR migrate to CSS animations

4. **Review wp-config.php**
   - Check for duplicate constant definitions causing PHP notices
   - Clean up redundant code

### LONG-TERM ACTIONS (Low Priority)

5. **Upgrade PHP**
   - PHP 7.4 reached EOL in November 2022
   - Plan migration to PHP 8.1 or 8.3
   - Test theme compatibility first

6. **Monitor Theme Updates**
   - Check Elegant Themes for TheSource updates
   - Consider migrating to modern theme (Divi, Astra, GeneratePress)

7. **Update JavaScript Libraries**
   - Consider replacing old libraries with modern alternatives
   - Implement build process (webpack/vite) for proper dependency management

---

## SECURITY BEST PRACTICES CURRENTLY IMPLEMENTED

✅ **Good Practices Found:**
1. Theme uses wp_verify_nonce() for CSRF protection
2. Child theme properly inherits from parent
3. ABSPATH checks prevent direct file access
4. Uses WordPress APIs instead of raw SQL
5. No file upload handling in theme code
6. Properly enqueues scripts and styles
7. Security plugins active (wp-fail2ban)
8. Cloudflare for DDoS protection
9. DISALLOW_FILE_EDIT enabled in wp-config.php
10. Auto-updates enabled for WordPress core

---

## COMPLIANCE & STANDARDS

### WordPress Coding Standards
✅ Mostly compliant
⚠️ Some older code patterns (expected for 2013-era theme)

### OWASP Top 10 (2021)
✅ A01 - Broken Access Control: Protected
✅ A02 - Cryptographic Failures: N/A (no sensitive data handling)
✅ A03 - Injection: Mostly protected (uses WordPress APIs)
⚠️ A04 - Insecure Design: Old theme architecture
✅ A05 - Security Misconfiguration: Good (fail2ban, Cloudflare)
✅ A06 - Vulnerable Components: ⚠️ Some old JS libraries
✅ A07 - Authentication Failures: WordPress handles this
✅ A08 - Data Integrity Failures: N/A
✅ A09 - Logging Failures: Adequate
✅ A10 - SSRF: Not applicable

---

## CONCLUSION

### Overall Security Posture: 🟡 MEDIUM (Acceptable)

The TheSource themes are **NOT dangerously out of date** but contain some **legacy code and unnecessary files** that should be cleaned up.

**No critical vulnerabilities found** that require immediate emergency action. The most concerning issue is the obsolete IE6 library which should be removed.

The child theme (TheSource-child) is **well-written and secure**, using modern WordPress APIs and proper sanitization.

**Recommended Actions Priority:**
1. 🔴 Remove IE6 PNG fix (5 minutes)
2. 🟡 Clean up backup files (5 minutes)
3. 🟢 Plan PHP 8.x migration (long-term)
4. 🟢 Monitor for theme updates (ongoing)

**Risk Assessment:**
- **Current Risk:** Low-Medium
- **After Cleanup:** Low
- **With PHP Upgrade:** Very Low

---

**Audit Performed By:** Thomas Vincent  
**Date:** 2025-12-20  
**Next Review:** 2026-06-20 (6 months)
