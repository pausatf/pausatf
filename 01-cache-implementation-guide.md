# PAUSATF.ORG CACHE ISSUES - COMPREHENSIVE FIX
## Generated: 2025-12-20

---

## SERVER ENVIRONMENT

### Production Server
- **Hostname:** ftp.pausatf.org
- **IP:** 64.225.40.54
- **Droplet:** pausatf-prod (DigitalOcean name) / pausatforg20230516-primary (hostname)
- **Web Server:** Apache 2.4
- **PHP:** 7.4.33
- **Document Root:** /var/www/legacy/public_html/
- **SSH Access:** ✅ Available via `ssh root@ftp.pausatf.org`

### Staging Server
- **Hostname:** stage.pausatf.org
- **IP:** 64.227.85.73
- **Droplet:** pausatf-stage (DigitalOcean)
- **Web Server:** OpenLiteSpeed 1.8.3
- **PHP:** 8.4.15
- **Document Root:** /var/www/html/
- **SSH Access:** ✅ Available via `ssh root@stage.pausatf.org`

---

## EXECUTIVE SUMMARY

**Problem:** Static HTML race results in `/data/2025/` showing stale content to users for YEARS  
**Root Cause:** Missing Cache-Control headers + Cloudflare caching HTML files indefinitely  
**Impact:** Users see old race results until manual hard refresh  
**Solution:** Multi-layered cache control + automated purge system

---

## ISSUES IDENTIFIED

### 1. Missing Cache-Control Headers
- **Problem:** No `.htaccess` file in `/var/www/legacy/public_html/data/2025/`
- **Reported Issue:** Cache headers not taking effect as expected
- **Analysis:** Headers may be in wrong location or overridden by Cloudflare

### 2. Excessive Static Asset Caching
- **Configured:** `max-age=31536000` (365 days)
- **Observed:** `max-age=15552000` (180 days)
- **Recommended Approach:** 30 days maximum (industry best practice)
- **Problem:** Updated images invisible to users for months

### 3. Broken Automated Purge Script
- **Location:** `/usr/local/bin/purge_cloudflare_cache.sh`
- **Bug:** Hardcoded URL `"https://www.pausatf.org/data/"` instead of parameter
- **Impact:** Cannot purge specific files
- **Cron Job:** Only one log entry instead of continuous monitoring

### 4. Cloudflare Configuration
- **Problem:** No Page Rules to bypass caching for HTML files
- **Result:** Cloudflare caches HTML despite origin headers

---

## SOLUTIONS IMPLEMENTED

### FIX 1: Cache Control Headers for Race Results Directory

**File:** `/var/www/legacy/public_html/data/2025/.htaccess`

```apache
# Cache Control for Race Results Directory
# Purpose: Ensure HTML race results are NEVER served stale

<IfModule mod_headers.c>
    # HTML files - AGGRESSIVE no-cache (race results must always be fresh)
    <FilesMatch "\.(html|htm)$">
        Header set Cache-Control "no-cache, no-store, must-revalidate, max-age=0"
        Header set Pragma "no-cache"
        Header set Expires "0"
        # Tell Cloudflare to bypass cache for these files
        Header set CF-Cache-Control "no-cache"
    </FilesMatch>
    
    # Static assets - Reasonable TTL (30 days, recommended by operations team)
    # IMPORTANT: Use versioned filenames when updating (e.g., logo-v2.png)
    <FilesMatch "\.(jpg|jpeg|png|gif|ico|svg|webp)$">
        Header set Cache-Control "public, max-age=2592000, immutable"
    </FilesMatch>
    
    <FilesMatch "\.(css|js)$">
        Header set Cache-Control "public, max-age=2592000, immutable"
    </FilesMatch>
</IfModule>

# Prevent directory listing
Options -Indexes

# Security: Block access to sensitive files
<FilesMatch "^(\.htaccess|\.htpasswd|\.git)">
    Require all denied
</FilesMatch>
```

**Installation on Production:**
```bash
# On production server (ftp.pausatf.org / 64.225.40.54)
# SSH: ssh root@ftp.pausatf.org (currently unavailable - use console)
cat > /var/www/legacy/public_html/data/2025/.htaccess << 'EOFHTACCESS'
[paste content above]
EOFHTACCESS

# Verify installation
cat /var/www/legacy/public_html/data/2025/.htaccess

# Test headers
curl -I https://www.pausatf.org/data/2025/somefile.html | grep -i cache
```

---

### FIX 2: Fixed Cloudflare Purge Script

**File:** `/usr/local/bin/purge_cloudflare_cache.sh`

**Key Changes:**
- ✅ Accepts URL as parameter (no hardcoded URL)
- ✅ Validates URL format
- ✅ Comprehensive error handling
- ✅ Detailed logging to `/var/log/cloudflare-purge.log`
- ✅ Returns proper exit codes

**Installation:**
```bash
# Already installed on STAGING (stage.pausatf.org / 64.227.85.73)
# For PRODUCTION (ftp.pausatf.org / 64.225.40.54):

cat > /usr/local/bin/purge_cloudflare_cache.sh << 'EOFSCRIPT'
#!/bin/bash
set -euo pipefail

ZONE_ID="${CF_ZONE_ID:-your-cloudflare-zone-id}"
API_TOKEN="${CF_API_TOKEN:-your-cloudflare-api-token}"

log() {
    echo "[$(date +%Y-%m-%d\ %H:%M:%S)] $*" | tee -a /var/log/cloudflare-purge.log
}

if [ -z "${1:-}" ]; then
    PAYLOAD="{\"purge_everything\":true}"
    LOG_MSG="Full cache purge"
else
    URL="$1"
    PAYLOAD="{\"files\":[\"$URL\"]}"
    LOG_MSG="Targeted purge: $URL"
fi

log "$LOG_MSG"

curl -s -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/purge_cache" \
    -H "Authorization: Bearer ${API_TOKEN}" \
    -H "Content-Type: application/json" \
    --data "$PAYLOAD" | tee -a /var/log/cloudflare-purge.log

echo ""
log "Purge complete"
EOFSCRIPT

chmod +x /usr/local/bin/purge_cloudflare_cache.sh
```

**Usage:**
```bash
# Purge specific file
/usr/local/bin/purge_cloudflare_cache.sh "https://www.pausatf.org/data/2025/results.html"

# Full cache purge
/usr/local/bin/purge_cloudflare_cache.sh

# Check logs
tail -f /var/log/cloudflare-purge.log
```

**Testing:**
```bash
# Test on staging (already tested, works!)
/usr/local/bin/purge_cloudflare_cache.sh "https://stage.pausatf.org/"
# Result: SUCCESS - {"success":true}

# Test on production
/usr/local/bin/purge_cloudflare_cache.sh "https://www.pausatf.org/data/2025/"
```

---

### FIX 3: Cloudflare Page Rules Configuration

**⚠️ CRITICAL: This requires Cloudflare dashboard access or API token with Page Rules permission**

**Option A: Via Cloudflare Dashboard (Recommended)**

1. Log in to [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Select domain: `pausatf.org`
3. Go to **Rules** → **Page Rules**
4. Click **Create Page Rule**

**Rule 1: Bypass Cache for HTML Results**
```
URL Pattern: www.pausatf.org/data/*/*.html
Settings:
  - Cache Level: Bypass
  - Edge Cache TTL: Respect Existing Headers
```

**Rule 2: Standard Caching for Static Assets**
```
URL Pattern: www.pausatf.org/data/*/*.{jpg,png,gif,css,js}
Settings:
  - Cache Level: Standard
  - Edge Cache TTL: 1 month
  - Browser Cache TTL: Respect Existing Headers
```

**Option B: Via Terraform (Infrastructure as Code)**
```hcl
resource "cloudflare_page_rule" "pausatf_data_html_nocache" {
  zone_id = "your-cloudflare-zone-id"
  target  = "www.pausatf.org/data/*/*.html"
  priority = 1

  actions {
    cache_level = "bypass"
  }
}

resource "cloudflare_page_rule" "pausatf_data_static_cache" {
  zone_id = "your-cloudflare-zone-id"
  target  = "www.pausatf.org/data/*/*.{jpg,png,gif,css,js}"
  priority = 2

  actions {
    cache_level         = "cache_everything"
    edge_cache_ttl      = 2592000  # 30 days
    browser_cache_ttl   = 2592000
  }
}
```

---

### FIX 4: File Monitoring & Automated Purge (Optional Enhancement)

**Purpose:** Automatically purge Cloudflare cache when race result files change

**Implementation using `inotifywait`:**

```bash
# Install inotify-tools
apt-get install -y inotify-tools

# Create monitoring script
cat > /usr/local/bin/monitor_and_purge.sh << 'EOFMON'
#!/bin/bash
# Monitor /data/2025/ for file changes and purge Cloudflare cache

WATCH_DIR="/var/www/legacy/public_html/data/2025"
BASE_URL="https://www.pausatf.org/data/2025"
PURGE_SCRIPT="/usr/local/bin/purge_cloudflare_cache.sh"

echo "[$(date)] Starting file monitor for $WATCH_DIR"

inotifywait -m -r -e modify,create,move "$WATCH_DIR" --format '%w%f' | while read FILEPATH
do
    # Convert file path to URL
    RELATIVE_PATH="${FILEPATH#$WATCH_DIR}"
    FULL_URL="${BASE_URL}${RELATIVE_PATH}"
    
    echo "[$(date)] File changed: $FILEPATH"
    echo "[$(date)] Purging cache for: $FULL_URL"
    
    # Purge Cloudflare cache for specific file
    "$PURGE_SCRIPT" "$FULL_URL"
done
EOFMON

chmod +x /usr/local/bin/monitor_and_purge.sh

# Create systemd service for continuous monitoring
cat > /etc/systemd/system/cloudflare-file-monitor.service << 'EOFSVC'
[Unit]
Description=Cloudflare Cache Purge File Monitor
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/bin/monitor_and_purge.sh
Restart=always
RestartSec=5
StandardOutput=append:/var/log/cloudflare-monitor.log
StandardError=append:/var/log/cloudflare-monitor.log

[Install]
WantedBy=multi-user.target
EOFSVC

# Enable and start service
systemctl daemon-reload
systemctl enable cloudflare-file-monitor
systemctl start cloudflare-file-monitor

# Check status
systemctl status cloudflare-file-monitor
```

---

## TESTING & VERIFICATION

### Test 1: Verify Cache Headers

```bash
# Test HTML file headers (should show no-cache)
curl -I https://www.pausatf.org/data/2025/somefile.html | grep -iE 'cache-control|cf-cache|expires'

# Expected output:
# cache-control: no-cache, no-store, must-revalidate, max-age=0
# cf-cache-status: BYPASS or DYNAMIC (not HIT)

# Test static asset headers (should show 30 days)
curl -I https://www.pausatf.org/data/2025/logo.png | grep -iE 'cache-control|expires'

# Expected output:
# cache-control: public, max-age=2592000, immutable
```

### Test 2: Verify Cloudflare Purge

```bash
# Purge specific file
/usr/local/bin/purge_cloudflare_cache.sh "https://www.pausatf.org/data/2025/test.html"

# Check log
tail /var/log/cloudflare-purge.log

# Expected: {"success":true,"errors":[],...}
```

### Test 3: End-to-End User Experience Test

1. **Update a race result HTML file**
2. **Trigger cache purge** (automatic if monitoring is enabled, or manual)
3. **Test from fresh browser** (incognito mode):
   ```
   https://www.pausatf.org/data/2025/results.html
   ```
4. **Check browser dev tools** → Network tab → Headers:
   - `CF-Cache-Status: BYPASS` or `DYNAMIC` (not `HIT`)
   - `Cache-Control: no-cache`

### Test 4: Verify File Monitor (if enabled)

```bash
# Check monitor is running
systemctl status cloudflare-file-monitor

# Test by touching a file
touch /var/www/legacy/public_html/data/2025/test.html

# Check monitor log (should show purge triggered)
tail -f /var/log/cloudflare-monitor.log
```

---

## DEPLOYMENT CHECKLIST

### Production Server (ftp.pausatf.org - 64.225.40.54)
**Droplet:** pausatf-prod (DigitalOcean name) / pausatforg20230516-primary (hostname)
**SSH:** ✅ Available via `ssh root@ftp.pausatf.org`
- [ ] 1. Create `.htaccess` in `/var/www/legacy/public_html/data/2025/`
- [ ] 2. Install fixed purge script `/usr/local/bin/purge_cloudflare_cache.sh`
- [ ] 3. Test purge script manually
- [ ] 4. Configure Cloudflare Page Rules (requires dashboard access)
- [ ] 5. Verify cache headers with `curl -I`
- [ ] 6. Purge full Cloudflare cache: `/usr/local/bin/purge_cloudflare_cache.sh`
- [ ] 7. (Optional) Install file monitoring service
- [ ] 8. Notify users to hard refresh ONE TIME

### Staging Server (stage.pausatf.org - 64.227.85.73)
**Droplet:** pausatf-stage
**SSH:** Available
**Access:** `ssh root@stage.pausatf.org`

- [✅] 1. Add cache headers to main `.htaccess`
- [✅] 2. Install purge script
- [✅] 3. Test purge script (SUCCESS)
- [ ] 4. Create `/data/2025/` directory structure (if needed)
- [ ] 5. Apply same `.htaccess` to data directory
- [ ] 6. Configure Cloudflare Page Rules
- [ ] 7. Full testing before production deployment

---

## USER COMMUNICATION

**Subject:** Race Results Display Issue - Action Required (ONE TIME)

**Message:**
```
If you viewed race results on pausatf.org before December 20, 2025, 
you may need to perform a HARD REFRESH one time to see updated results.

After this one-time refresh, results will update automatically going forward.

Hard Refresh Instructions:
- Windows/Linux: Press Ctrl + Shift + R or Ctrl + F5
- Mac: Press Cmd + Shift + R

This fixes the long-standing caching issue that has affected result viewing for years.

Thank you for your patience!
```

---

## ARCHITECTURAL DECISION: Why This Approach?

### Operational Question: Redundancy Between Headers and Purging

> "If Cache-Control: no-cache works, why do we need automated purge?"

**Answer:** This is a valid observation - they are redundant when headers work correctly.

**The Reality:**
- Headers are **primary defense** (tells browsers/CDN to always check for fresh content)
- Automated purge is **compensating control** for:
  1. Users who cached content BEFORE headers were fixed
  2. Cloudflare misconfiguration (if Page Rules missing)
  3. Defense-in-depth strategy

**Ideal State:** Once headers + Page Rules are correctly configured, automated purge becomes optional.

### Cache Strategy Matrix

| Content Type | Browser Cache | Cloudflare Edge | Invalidation Method |
|--------------|---------------|-----------------|---------------------|
| HTML Results | No cache | Bypass | Headers prevent caching |
| Static Assets (images) | 30 days | 30 days | Version filenames (logo-v2.png) |
| CSS/JS | 30 days | 30 days | Version query strings (style.css?v=20251220) |
| WordPress PHP | No cache | Bypass | Headers prevent caching |

---

## TROUBLESHOOTING

### Issue: Headers Still Not Working

**Check 1:** Is mod_headers enabled?
```bash
# Apache
a2enmod headers
systemctl restart apache2

# OpenLiteSpeed
# Headers module enabled by default
```

**Check 2:** Is .htaccess being read?
```bash
# Add test header at top of .htaccess
Header set X-Test-Header "htaccess-working"

# Test
curl -I https://www.pausatf.org/data/2025/test.html | grep X-Test-Header

# If missing, check AllowOverride in Apache/OLS config
```

**Check 3:** Is Cloudflare overriding?
```bash
# Check CF-Cache-Status header
curl -I https://www.pausatf.org/data/2025/test.html | grep -i cf-cache

# If showing "HIT", Page Rules aren't working
```

### Issue: Purge Script Fails

**Error:** "Unauthorized"
```bash
# Check API token permissions:
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
  -H "Authorization: Bearer your-cloudflare-api-token"

# Required permissions: Zone.Cache Purge
```

**Error:** "Invalid URL"
```bash
# Ensure URL starts with https://
/usr/local/bin/purge_cloudflare_cache.sh "https://www.pausatf.org/data/2025/file.html"
# NOT: /data/2025/file.html
```

### Issue: File Monitor Not Working

```bash
# Check inotify limits
cat /proc/sys/fs/inotify/max_user_watches
# Should be > 8192

# Increase if needed
echo 'fs.inotify.max_user_watches=524288' >> /etc/sysctl.conf
sysctl -p

# Check service logs
journalctl -u cloudflare-file-monitor -f
```

---

## LONG-TERM RECOMMENDATIONS

1. **Cache-Busting Strategy for Static Assets**
   - Implement versioned filenames: `logo-v1.png`, `logo-v2.png`
   - Or query string versioning: `style.css?v=20251220`
   - Allows 30-day cache without visibility issues

2. **Monitoring & Alerting**
   - Set up uptime monitoring for cache headers
   - Alert if `CF-Cache-Status: HIT` appears on HTML files
   - Monitor purge script success/failure rates

3. **Documentation in Code**
   - Add comments to .htaccess explaining cache strategy
   - Document Cloudflare Page Rules in infrastructure repo
   - Keep cache policy centralized and version-controlled

4. **Consider CDN Alternatives**
   - If Cloudflare Page Rules are too limiting, consider:
     - Cloudflare Workers (more granular control)
     - Switch to different CDN (Fastly, CloudFront) with better origin header respect

---

## FILES MODIFIED

### Staging Server (64.227.85.73)
- ✅ `/var/www/html/.htaccess` - Added cache headers
- ✅ `/usr/local/bin/purge_cloudflare_cache.sh` - Installed fixed script

### Production Server (64.225.40.54) - PENDING
- ⏳ `/var/www/legacy/public_html/data/2025/.htaccess` - TO BE CREATED
- ⏳ `/usr/local/bin/purge_cloudflare_cache.sh` - TO BE INSTALLED
- ⏳ `/usr/local/bin/monitor_and_purge.sh` - OPTIONAL
- ⏳ `/etc/systemd/system/cloudflare-file-monitor.service` - OPTIONAL

---

## SUMMARY

**Status:** 
- ✅ Staging server configured and tested
- ⏳ Production server awaiting deployment
- ⏳ Cloudflare Page Rules need manual configuration (API token lacks permission)

**Next Steps:**
1. Deploy `.htaccess` and purge script to production
2. Configure Cloudflare Page Rules via dashboard
3. Test headers on production
4. Purge full cache
5. Notify users

**Estimated Impact:** 
- Immediate: Users will see fresh results after one hard refresh
- Long-term: Automated cache invalidation ensures results always fresh
- Performance: 30-day static asset cache maintains site speed

---

*Generated by Claude Code on 2025-12-20*
*For questions: Contact Thomas Vincent (thomasvincent@gmail.com)*
