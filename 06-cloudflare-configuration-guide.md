# CLOUDFLARE CONFIGURATION GUIDE FOR PAUSATF.ORG
## Complete Setup & Optimization

**Last Updated:** 2025-12-20
**Zone ID:** your-cloudflare-zone-id
**Domain:** pausatf.org

---

## TABLE OF CONTENTS

1. [Initial Setup](#initial-setup)
2. [DNS Configuration](#dns-configuration)
3. [SSL/TLS Settings](#ssltls-settings)
4. [Page Rules](#page-rules)
5. [Caching Configuration](#caching-configuration)
6. [Firewall Rules](#firewall-rules)
7. [Speed Optimization](#speed-optimization)
8. [Security Settings](#security-settings)
9. [API Management](#api-management)
10. [Monitoring & Analytics](#monitoring--analytics)
11. [Migration-Specific Settings](#migration-specific-settings)

---

## INITIAL SETUP

### Account Information

```
Zone ID:     your-cloudflare-zone-id
API Token:   your-cloudflare-api-token
Plan Type:   Free (or Pro/Business - check current plan)
Name Servers:
  - ns1.cloudflare.com
  - ns2.cloudflare.com
```

### Verify Cloudflare is Active

```bash
# Check nameservers
dig pausatf.org NS +short

# Expected output:
# ns1.cloudflare.com
# ns2.cloudflare.com

# Check Cloudflare headers
curl -I https://www.pausatf.org/ | grep -i cf-

# Expected headers:
# cf-cache-status: HIT or MISS or BYPASS
# cf-ray: [unique ID]
# server: cloudflare
```

---

## DNS CONFIGURATION

### Current DNS Records (Production)

```
TYPE    NAME                VALUE               TTL     PROXY STATUS
A       pausatf.org         64.225.40.54       Auto    Proxied (orange cloud)
A       www.pausatf.org     64.225.40.54       Auto    Proxied (orange cloud)
A       prod.pausatf.org     64.225.40.54       Auto    Proxied (orange cloud)
AAAA    pausatf.org         [IPv6 if enabled]  Auto    Proxied
AAAA    www.pausatf.org     [IPv6 if enabled]  Auto    Proxied
MX      pausatf.org         [mail server]      Auto    DNS Only (gray cloud)
TXT     pausatf.org         [SPF/DKIM/etc]     Auto    DNS Only
```

### DNS Record Configuration via Dashboard

**Step-by-step:**

1. Login to Cloudflare: https://dash.cloudflare.com/
2. Select domain: pausatf.org
3. Go to: DNS → Records

**For each record:**

```
Record Type: A
Name: @ (for pausatf.org) or www or ftp
IPv4 address: [droplet IP]
Proxy status: Proxied (orange cloud ON) ← IMPORTANT
TTL: Auto
```

### DNS Record Configuration via API

```bash
# Set environment variables
export CF_ZONE_ID="your-cloudflare-zone-id"
export CF_API_TOKEN="your-cloudflare-api-token"
export NEW_SERVER_IP="[new droplet IP]"

# List all DNS records
curl -X GET "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" | jq .

# Create/Update www.pausatf.org A record
curl -X POST "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "A",
    "name": "www",
    "content": "'${NEW_SERVER_IP}'",
    "ttl": 1,
    "proxied": true
  }'

# Create/Update prod.pausatf.org A record
curl -X POST "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "A",
    "name": "ftp",
    "content": "'${NEW_SERVER_IP}'",
    "ttl": 1,
    "proxied": true
  }'

# Create/Update pausatf.org root A record
curl -X POST "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "A",
    "name": "@",
    "content": "'${NEW_SERVER_IP}'",
    "ttl": 1,
    "proxied": true
  }'
```

### Update Existing DNS Record via API

```bash
# First, get the record ID
RECORD_ID=$(curl -s -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records?name=www.pausatf.org" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq -r '.result[0].id')

echo "Record ID: $RECORD_ID"

# Update the record
curl -X PUT \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records/${RECORD_ID}" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "A",
    "name": "www",
    "content": "'${NEW_SERVER_IP}'",
    "ttl": 1,
    "proxied": true
  }'
```

### DNS Migration Strategy

**24 Hours Before Migration:**
```bash
# Lower TTL to 300 seconds (5 minutes) for faster propagation
curl -X PUT \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records/${RECORD_ID}" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "A",
    "name": "www",
    "content": "64.225.40.54",
    "ttl": 300,
    "proxied": true
  }'
```

**During Migration:**
```bash
# Update to new IP
# (see update commands above with NEW_SERVER_IP)
```

**24 Hours After Migration:**
```bash
# Increase TTL back to Auto (1 hour with proxied enabled)
curl -X PUT \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records/${RECORD_ID}" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "type": "A",
    "name": "www",
    "content": "'${NEW_SERVER_IP}'",
    "ttl": 1,
    "proxied": true
  }'
```

---

## SSL/TLS SETTINGS

### Recommended SSL/TLS Configuration

**Navigate to:** SSL/TLS tab in Cloudflare dashboard

#### Overview Settings

```
SSL/TLS encryption mode: Full (strict)
  ↑ Requires valid SSL certificate on origin server
  ↑ Use this after installing Let's Encrypt on new server

Alternative: Full (not recommended - allows self-signed)
  ↑ Use temporarily during testing if needed

NEVER USE: Flexible (deprecated, insecure)
```

**Set via API:**
```bash
curl -X PATCH "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/ssl" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"value":"full"}'
```

#### Edge Certificates

```
Always Use HTTPS: ON
  ↑ Automatically redirects http:// to https://

Minimum TLS Version: 1.2
  ↑ Blocks outdated TLS 1.0 and 1.1

Opportunistic Encryption: ON

TLS 1.3: Enabled

Automatic HTTPS Rewrites: ON
  ↑ Fixes mixed content issues

Certificate Transparency Monitoring: ON
```

**Enable Always Use HTTPS via API:**
```bash
curl -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/always_use_https" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"value":"on"}'
```

#### Origin Server Certificate (Optional)

**Create Cloudflare Origin Certificate for extra security:**

1. Go to: SSL/TLS → Origin Server
2. Click "Create Certificate"
3. Generate:
   - Hostnames: `*.pausatf.org`, `pausatf.org`
   - Validity: 15 years
4. Copy private key and certificate
5. Install on origin server:

```bash
# On new droplet:
cat > /etc/ssl/certs/cloudflare-origin.pem << 'EOF'
[paste certificate here]
EOF

cat > /etc/ssl/private/cloudflare-origin.key << 'EOF'
[paste private key here]
EOF

chmod 600 /etc/ssl/private/cloudflare-origin.key

# Update Apache to use these certificates
# Edit /etc/apache2/sites-available/pausatf-le-ssl.conf
SSLCertificateFile      /etc/ssl/certs/cloudflare-origin.pem
SSLCertificateKeyFile   /etc/ssl/private/cloudflare-origin.key
```

#### HSTS (HTTP Strict Transport Security)

```
Status: Enabled
Max Age: 6 months (recommended: 1 year after testing)
Include Subdomains: Yes
Preload: No (enable after 6 months of stable operation)
```

**Enable via API:**
```bash
curl -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/security_header" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "value": {
      "strict_transport_security": {
        "enabled": true,
        "max_age": 15552000,
        "include_subdomains": true,
        "preload": false
      }
    }
  }'
```

---

## PAGE RULES

### Critical Page Rules for Race Results Caching

**Navigate to:** Rules → Page Rules

#### Rule 1: Bypass Cache for HTML Race Results

```
URL Pattern: www.pausatf.org/data/*/*.html
  or: *pausatf.org/data/*/*.html (includes all subdomains)

Settings:
  ✓ Cache Level: Bypass
  ✓ Disable Apps
  ✓ Disable Performance

Order: 1 (highest priority)
```

**Why:** HTML race results must NEVER be cached by Cloudflare.
The origin server sends no-cache headers, but this ensures Cloudflare respects them.

**Create via Dashboard:**
1. Go to: Rules → Page Rules
2. Click "Create Page Rule"
3. Enter URL pattern: `*pausatf.org/data/*/*.html`
4. Add Setting: Cache Level → Bypass
5. Save and Deploy

**Create via API:**
```bash
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/pagerules" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "targets": [
      {
        "target": "url",
        "constraint": {
          "operator": "matches",
          "value": "*pausatf.org/data/*/*.html"
        }
      }
    ],
    "actions": [
      {
        "id": "cache_level",
        "value": "bypass"
      }
    ],
    "priority": 1,
    "status": "active"
  }'
```

#### Rule 2: Cache Static Assets in Data Directory

```
URL Pattern: *pausatf.org/data/*/*.jpg
  (create separate rules for each extension, or use workers for wildcard)

Settings:
  ✓ Cache Level: Cache Everything
  ✓ Edge Cache TTL: 1 month (2592000 seconds)
  ✓ Browser Cache TTL: 1 month (2592000 seconds)

Order: 2
```

**Multiple rules needed for:**
- `*pausatf.org/data/*/*.jpg`
- `*pausatf.org/data/*/*.png`
- `*pausatf.org/data/*/*.gif`
- `*pausatf.org/data/*/*.css`
- `*pausatf.org/data/*/*.js`

**Create via API (example for JPG):**
```bash
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/pagerules" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "targets": [
      {
        "target": "url",
        "constraint": {
          "operator": "matches",
          "value": "*pausatf.org/data/*/*.jpg"
        }
      }
    ],
    "actions": [
      {
        "id": "cache_level",
        "value": "cache_everything"
      },
      {
        "id": "edge_cache_ttl",
        "value": 2592000
      },
      {
        "id": "browser_cache_ttl",
        "value": 2592000
      }
    ],
    "priority": 2,
    "status": "active"
  }'
```

#### Rule 3: Cache WordPress Frontend (Optional)

```
URL Pattern: www.pausatf.org/*

Settings:
  ✓ Cache Level: Standard
  ✓ Edge Cache TTL: 2 hours (7200 seconds)
  ✓ Browser Cache TTL: 4 hours (14400 seconds)
  ✓ Bypass Cache on Cookie: wp-*|wordpress_*|comment_*
    (prevents caching for logged-in users)

Order: 99 (lowest priority - catches everything else)
```

#### Rule 4: Disable Caching for WordPress Admin

```
URL Pattern: www.pausatf.org/wp-admin/*

Settings:
  ✓ Cache Level: Bypass
  ✓ Disable Apps
  ✓ Disable Performance
  ✓ Security Level: High

Order: 1
```

#### Rule 5: Disable Caching for WordPress Login

```
URL Pattern: www.pausatf.org/wp-login.php*

Settings:
  ✓ Cache Level: Bypass
  ✓ Disable Apps
  ✓ Security Level: High

Order: 1
```

### Page Rules Best Practices

**Important Notes:**
- Free plan: 3 page rules maximum
- Pro plan: 20 page rules maximum
- Order matters: Lower number = higher priority
- Test each rule with curl before deploying

**Test Page Rules:**
```bash
# Test HTML bypass (should show cf-cache-status: BYPASS)
curl -I https://www.pausatf.org/data/2025/test.html | grep -i cf-cache

# Test static asset caching (should show cf-cache-status: HIT after first request)
curl -I https://www.pausatf.org/data/2025/test.jpg | grep -i cf-cache
curl -I https://www.pausatf.org/data/2025/test.jpg | grep -i cf-cache  # Second request
```

### View Existing Page Rules

```bash
# List all page rules
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/pagerules" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result[] | {id: .id, priority: .priority, targets: .targets, actions: .actions, status: .status}'
```

---

## CACHING CONFIGURATION

### Cache Settings Overview

**Navigate to:** Caching → Configuration

#### Caching Level

```
Setting: Standard
  ✓ Caches static content automatically
  ✓ Respects origin Cache-Control headers
  ✓ Bypasses cache for dynamic content

Alternative: Aggressive (not recommended for WordPress)
```

**Set via API:**
```bash
curl -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/cache_level" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"value":"aggressive"}'
```

#### Browser Cache TTL

```
Setting: Respect Existing Headers (recommended)
  ↑ Allows origin .htaccess to control browser caching

Alternative: 4 hours (if origin doesn't send headers)
```

#### Crawlers Hints

```
Setting: ON
  ✓ Allows Cloudflare to serve cached content to search engine crawlers
```

#### Always Online

```
Setting: ON
  ✓ Serves limited cached version if origin is down
  ✓ Only works for previously cached pages
```

### Cache Purging

#### Full Cache Purge

**Via Dashboard:**
1. Go to: Caching → Configuration
2. Click "Purge Everything"
3. Confirm

**Via API:**
```bash
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

**Via Script (already deployed):**
```bash
# On server:
/usr/local/bin/purge_cloudflare_cache.sh
```

#### Purge by URL

**Single URL:**
```bash
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "files": [
      "https://www.pausatf.org/data/2025/results.html"
    ]
  }'
```

**Multiple URLs:**
```bash
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "files": [
      "https://www.pausatf.org/data/2025/results.html",
      "https://www.pausatf.org/data/2025/race1.html",
      "https://www.pausatf.org/data/2025/race2.html"
    ]
  }'
```

**Via Deployed Script:**
```bash
# On server:
/usr/local/bin/purge_cloudflare_cache.sh "https://www.pausatf.org/data/2025/results.html"
```

#### Purge by Tag/Host (Pro+ feature)

**Purge by hostname:**
```bash
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "hosts": [
      "www.pausatf.org"
    ]
  }'
```

#### Purge by Prefix (Enterprise only)

```bash
# Purge everything under /data/2025/
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "prefixes": [
      "www.pausatf.org/data/2025/"
    ]
  }'
```

### Cache Analytics

**View cache performance:**
```bash
# Get cache analytics (last 24 hours)
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/analytics/dashboard?since=-1440&continuous=true" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result.timeseries[] | {time: .until, cached: .requests.cached, uncached: .requests.uncached}'
```

---

## FIREWALL RULES

### Recommended Firewall Configuration

**Navigate to:** Security → WAF

#### Managed Rules

```
Cloudflare Managed Ruleset: ON
Cloudflare OWASP Core Ruleset: ON
```

#### Custom Rules

**Rule 1: Block Bad Bots**
```
Expression: (cf.client.bot) and not (cf.verified_bot_category in {"Search Engine Crawler"})
Action: Block
```

**Rule 2: Rate Limiting for Login**
```
Expression: (http.request.uri.path eq "/wp-login.php")
Action: Rate Limit (10 requests per minute per IP)
```

**Rule 3: Allow Only Specific Countries (Optional)**
```
Expression: not (ip.geoip.country in {"US" "CA"})
Action: Challenge
  ↑ Only if your audience is primarily US/Canada
```

### Configure via API

```bash
# Create firewall rule to block bad bots
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/firewall/rules" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "filter": {
      "expression": "(cf.client.bot) and not (cf.verified_bot_category in {\"Search Engine Crawler\"})",
      "paused": false
    },
    "action": "block",
    "description": "Block bad bots"
  }'
```

---

## SPEED OPTIMIZATION

### Auto Minify

**Navigate to:** Speed → Optimization

```
Auto Minify:
  ✓ JavaScript: ON
  ✓ CSS: ON
  ✓ HTML: ON
```

**Enable via API:**
```bash
curl -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/minify" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{
    "value": {
      "css": "on",
      "html": "on",
      "js": "on"
    }
  }'
```

### Brotli Compression

```
Brotli: ON ✅
  ✓ Better compression than gzip (15-20% smaller files)
  ✓ Faster page loads
  ✓ Supported by all modern browsers
```

### Rocket Loader

```
Rocket Loader: ON ✅
  ✓ Async JavaScript loading
  ✓ Improves page load times
  ⚠ Monitor for compatibility with WordPress themes/plugins
```

**Note:** Rocket Loader is currently enabled and working well with TheSource theme. Disable if JavaScript issues occur.

### HTTP/2, HTTP/3, and Modern Protocols

```
HTTP/2: ON ✅ (enabled by default)
HTTP/3 (with QUIC): ON ✅
0-RTT (Zero Round Trip Time): ON ✅
TLS 1.3: ON (zrt mode - Zero Round Trip) ✅
Early Hints: ON ✅ (103 status code for faster resource loading)
```

**Benefits:**
- HTTP/3 uses QUIC protocol over UDP (faster than TCP)
- 0-RTT reduces connection time for repeat visitors
- TLS 1.3 with zrt mode provides fastest encryption handshake
- Early Hints send resource hints before full response

**Enable HTTP/3 (already enabled):**
```bash
curl -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/http3" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"value":"on"}'
```

**Enable 0-RTT (already enabled):**
```bash
curl -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/0rtt" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"value":"on"}'
```

### Image Optimization (Pro+ feature)

```
Polish: Not enabled (Pro plan required)
WebP: Not enabled (Pro plan required)
```

**Alternative:** Use WordPress plugins for image optimization (Imagify, ShortPixel, etc.)

---

## SECURITY SETTINGS

### Security Level

```
Setting: Medium (recommended)
  ↑ Challenges visitors with moderate threat scores

High: More aggressive, may challenge legitimate users
Low: More permissive, less security
```

### Bot Fight Mode

```
Setting: ON (Free plan)
  ↑ Challenges definite bots
  ↑ Blocks automated traffic

Super Bot Fight Mode (Pro+): Even more aggressive
```

### Privacy Pass Support

```
Setting: ON
  ↑ Reduces challenges for Privacy Pass users
```

### Security Headers

**Add via Transform Rules or Workers:**

```javascript
// Example security headers
Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:;
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

---

## API MANAGEMENT

### Current API Token

```
Token: your-cloudflare-api-token
Permissions:
  - Zone:Cache Purge
  - Zone:DNS:Edit
  - Zone:Zone:Read
  (verify actual permissions in dashboard)
```

### Create New API Token

**Via Dashboard:**
1. Go to: My Profile → API Tokens
2. Click "Create Token"
3. Select "Custom token"
4. Set permissions:
   - Zone:Cache Purge:Purge
   - Zone:DNS:Edit
   - Zone:Zone:Read
   - Zone:Zone Settings:Edit
5. Zone Resources: Include → Specific zone → pausatf.org
6. Create token and save securely

### Test API Token

```bash
# Test token validity
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq .

# Expected response:
# "status": "active"
```

---

## MONITORING & ANALYTICS

### Analytics Dashboard

**Navigate to:** Analytics & Logs → Traffic

**Key Metrics to Monitor:**
- Requests (total, cached, uncached)
- Bandwidth (saved by caching)
- Threats (blocked/challenged)
- Status codes (200, 404, 500, etc.)

### Get Analytics via API

```bash
# Last 24 hours
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/analytics/dashboard?since=-1440" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result.totals'

# Cache performance
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/analytics/dashboard?since=-1440" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result.totals.requests | {total: .all, cached: .cached, uncached: .uncached}'
```

### Set Up Notifications

**Via Dashboard:**
1. Go to: Notifications
2. Create notification for:
   - SSL Certificate Expiration
   - DDoS Attack
   - Origin Error Rate Alert
   - Traffic Anomalies

---

## MIGRATION-SPECIFIC SETTINGS

### Pre-Migration Checklist

```bash
# 1. Lower DNS TTL
curl -X PUT \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records/${RECORD_ID}" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"type":"A","name":"www","content":"64.225.40.54","ttl":300,"proxied":true}'

# 2. Enable Development Mode (pauses caching for 3 hours)
curl -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/development_mode" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"value":"on"}'
```

### During Migration

```bash
# 1. Update DNS to new IP
# (see DNS Configuration section)

# 2. Purge entire cache
curl -X POST \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

### Post-Migration

```bash
# 1. Disable Development Mode
curl -X PATCH \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/development_mode" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"value":"off"}'

# 2. Increase DNS TTL back to Auto
curl -X PUT \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records/${RECORD_ID}" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"type":"A","name":"www","content":"'${NEW_SERVER_IP}'","ttl":1,"proxied":true}'

# 3. Monitor cache hit rate
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/analytics/dashboard?since=-60" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result.totals.requests'
```

### Verify Configuration

```bash
# Check DNS resolution
dig www.pausatf.org +short

# Check SSL/TLS mode
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/ssl" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result.value'

# Check page rules
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/pagerules" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result | length'

# Test caching behavior
curl -I https://www.pausatf.org/data/2025/test.html | grep -i cf-cache
# Expected: cf-cache-status: BYPASS

curl -I https://www.pausatf.org/data/2025/test.jpg | grep -i cf-cache
# Expected: cf-cache-status: MISS (first time)
curl -I https://www.pausatf.org/data/2025/test.jpg | grep -i cf-cache
# Expected: cf-cache-status: HIT (second time)
```

---

## TROUBLESHOOTING

### Cache Not Working

**Symptoms:** All requests show `cf-cache-status: BYPASS`

**Checks:**
```bash
# 1. Verify proxied status
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result[] | {name: .name, proxied: .proxied}'

# 2. Check if Development Mode is enabled
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/development_mode" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  | jq '.result.value'

# 3. Verify origin headers aren't forcing no-cache globally
curl -I https://www.pausatf.org/ | grep -i cache-control
```

### SSL Errors

**Symptoms:** ERR_SSL_VERSION_OR_CIPHER_MISMATCH

**Fix:**
```bash
# Check SSL mode
curl -X GET \
  "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/ssl" \
  -H "Authorization: Bearer ${CF_API_TOKEN}"

# Ensure origin has valid SSL certificate
ssh root@prod.pausatf.org "certbot certificates"
```

### 520/521/522 Errors

**520:** Web server returned unknown error
**521:** Web server is down
**522:** Connection timed out

**Fix:**
```bash
# Check origin server is accessible
nc -zv $NEW_SERVER_IP 80
nc -zv $NEW_SERVER_IP 443

# Check Apache is running
ssh root@prod.pausatf.org "systemctl status apache2"

# Check firewall
ssh root@prod.pausatf.org "ufw status"
```

---

## APPENDIX: Complete Configuration Script

```bash
#!/bin/bash
# Complete Cloudflare configuration script for pausatf.org

set -euo pipefail

# Configuration
CF_ZONE_ID="your-cloudflare-zone-id"
CF_API_TOKEN="your-cloudflare-api-token"
NEW_SERVER_IP="${1:-}"  # Pass as argument

if [ -z "$NEW_SERVER_IP" ]; then
    echo "Usage: $0 <new_server_ip>"
    exit 1
fi

echo "Configuring Cloudflare for pausatf.org with IP: $NEW_SERVER_IP"

# 1. Update DNS records
echo "Updating DNS records..."
for name in "@" "www" "ftp"; do
    RECORD_ID=$(curl -s -X GET \
        "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records?name=${name}.pausatf.org" \
        -H "Authorization: Bearer ${CF_API_TOKEN}" \
        | jq -r '.result[0].id')

    if [ "$RECORD_ID" != "null" ]; then
        curl -s -X PUT \
            "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/dns_records/${RECORD_ID}" \
            -H "Authorization: Bearer ${CF_API_TOKEN}" \
            -H "Content-Type: application/json" \
            --data '{
                "type": "A",
                "name": "'$name'",
                "content": "'$NEW_SERVER_IP'",
                "ttl": 1,
                "proxied": true
            }'
        echo "✓ Updated $name.pausatf.org"
    fi
done

# 2. Set SSL mode to Full (strict)
echo "Setting SSL mode..."
curl -s -X PATCH \
    "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/ssl" \
    -H "Authorization: Bearer ${CF_API_TOKEN}" \
    -H "Content-Type: application/json" \
    --data '{"value":"full"}'
echo "✓ SSL mode set to Full"

# 3. Enable Always Use HTTPS
echo "Enabling Always Use HTTPS..."
curl -s -X PATCH \
    "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/always_use_https" \
    -H "Authorization: Bearer ${CF_API_TOKEN}" \
    -H "Content-Type: application/json" \
    --data '{"value":"on"}'
echo "✓ Always Use HTTPS enabled"

# 4. Enable minification
echo "Enabling minification..."
curl -s -X PATCH \
    "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/settings/minify" \
    -H "Authorization: Bearer ${CF_API_TOKEN}" \
    -H "Content-Type: application/json" \
    --data '{"value":{"css":"on","html":"on","js":"on"}}'
echo "✓ Minification enabled"

# 5. Purge cache
echo "Purging cache..."
curl -s -X POST \
    "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
    -H "Authorization: Bearer ${CF_API_TOKEN}" \
    -H "Content-Type: application/json" \
    --data '{"purge_everything":true}'
echo "✓ Cache purged"

echo ""
echo "Cloudflare configuration complete!"
echo "DNS propagation may take 5-30 minutes"
echo ""
echo "Verify with:"
echo "  dig www.pausatf.org +short"
echo "  curl -I https://www.pausatf.org/ | grep cf-"
```

**Save and run:**
```bash
chmod +x cloudflare-configure.sh
./cloudflare-configure.sh [new-server-ip]
```

---

**Document Created By:** Thomas Vincent
**Last Updated:** 2025-12-20
**Version:** 1.0
