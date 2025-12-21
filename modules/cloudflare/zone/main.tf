terraform {
  required_providers {
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 4.20.0"
    }
  }
}

resource "cloudflare_zone" "this" {
  account_id = var.account_id
  zone       = var.zone_name
  plan       = var.plan
  type       = var.type
}

resource "cloudflare_zone_settings_override" "this" {
  zone_id = cloudflare_zone.this.id

  settings {
    # SSL/TLS
    ssl = var.ssl_mode
    always_use_https = var.always_use_https
    automatic_https_rewrites = var.automatic_https_rewrites
    min_tls_version = var.min_tls_version
    tls_1_3 = var.tls_1_3_enabled ? "on" : "off"

    # Security
    security_level = var.security_level
    challenge_ttl = var.challenge_ttl
    browser_check = var.browser_check ? "on" : "off"
    email_obfuscation = var.email_obfuscation ? "on" : "off"
    hotlink_protection = var.hotlink_protection ? "on" : "off"

    # Performance
    brotli = var.brotli ? "on" : "off"
    early_hints = var.early_hints ? "on" : "off"
    http2 = var.http2 ? "on" : "off"
    http3 = var.http3 ? "on" : "off"
    zero_rtt = var.zero_rtt ? "on" : "off"

    minify {
      css  = var.minify_css ? "on" : "off"
      js   = var.minify_js ? "on" : "off"
      html = var.minify_html ? "on" : "off"
    }

    # Caching
    browser_cache_ttl = var.browser_cache_ttl

    # Network
    ipv6 = var.ipv6 ? "on" : "off"
    websockets = var.websockets ? "on" : "off"

    # Other
    development_mode = var.development_mode ? "on" : "off"
  }
}

# DNSSEC
resource "cloudflare_zone_dnssec" "this" {
  count   = var.dnssec_enabled ? 1 : 0
  zone_id = cloudflare_zone.this.id
}
