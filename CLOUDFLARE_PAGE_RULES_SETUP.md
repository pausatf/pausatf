# CLOUDFLARE PAGE RULES CONFIGURATION
## For pausatf.org Cache Fix

**Note:** API token doesn't have Page Rules permission.  
**Action Required:** Configure manually via Cloudflare Dashboard

---

## ACCESS CLOUDFLARE DASHBOARD

1. Go to: https://dash.cloudflare.com/
2. Log in with your Cloudflare account
3. Select domain: **pausatf.org**
4. Navigate to: **Rules** → **Page Rules**

---

## PAGE RULE #1: Bypass HTML Results Cache

**Purpose:** Prevent Cloudflare from caching race result HTML files

### Configuration:

**URL Pattern:**
```
www.pausatf.org/data/*/*.html
```

**Settings:**
- **Cache Level:** `Bypass`
- **Edge Cache TTL:** (Optional) Set to "Respect Existing Headers"

**Priority:** 1 (highest)

### Click "Save and Deploy"

---

## PAGE RULE #2: Cache Static Assets (Optional)

**Purpose:** Optimize performance for images, CSS, JS in data directory

### Configuration:

**URL Pattern:**
```
www.pausatf.org/data/*/*.{jpg,png,gif,css,js,jpeg,svg,webp}
```

**Settings:**
- **Cache Level:** `Standard` or `Cache Everything`
- **Edge Cache TTL:** `1 month`
- **Browser Cache TTL:** `Respect Existing Headers`

**Priority:** 2

### Click "Save and Deploy"

---

## VERIFICATION

After creating the rules, test with:

```bash
# Test HTML file (should show BYPASS)
curl -I https://www.pausatf.org/data/2025/results.html | grep -i cf-cache-status
# Expected: CF-Cache-Status: BYPASS or DYNAMIC

# Test image file (should show HIT after first request)
curl -I https://www.pausatf.org/data/2025/image.jpg | grep -i cf-cache-status
# First request: MISS or DYNAMIC
# Second request: HIT
```

---

## ALTERNATIVE: Create via Terraform

If you prefer Infrastructure as Code:

```hcl
# File: cloudflare_page_rules.tf

resource "cloudflare_page_rule" "pausatf_data_html_nocache" {
  zone_id  = "your-cloudflare-zone-id"
  target   = "www.pausatf.org/data/*/*.html"
  priority = 1
  
  actions {
    cache_level = "bypass"
  }
}

resource "cloudflare_page_rule" "pausatf_data_static_cache" {
  zone_id  = "your-cloudflare-zone-id"
  target   = "www.pausatf.org/data/*/*.{jpg,png,gif,css,js,jpeg,svg,webp}"
  priority = 2
  
  actions {
    cache_level       = "cache_everything"
    edge_cache_ttl    = 2592000  # 30 days
    browser_cache_ttl = 2592000
  }
}
```

Then run:
```bash
terraform plan
terraform apply
```

---

## TROUBLESHOOTING

**If rules don't take effect:**
1. Check rule priority (lower number = higher priority)
2. Purge cache after creating rules
3. Test in incognito mode to avoid browser cache
4. Check pattern matches your actual URLs

**Page Rule Limits:**
- Free plan: 3 page rules
- Pro plan: 20 page rules
- Business plan: 50 page rules

---

*Created: 2025-12-20*
