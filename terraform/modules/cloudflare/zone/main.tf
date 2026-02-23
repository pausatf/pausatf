terraform {
  required_version = ">= 1.6.0"

  required_providers {
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 5.0"
    }
  }
}

resource "cloudflare_zone" "this" {
  account = { id = var.account_id }
  name    = var.zone_name
  type    = var.type
}

# SSL/TLS settings
resource "cloudflare_zone_setting" "ssl" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "ssl"
  value      = var.ssl_mode
}

resource "cloudflare_zone_setting" "always_use_https" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "always_use_https"
  value      = var.always_use_https
}

resource "cloudflare_zone_setting" "automatic_https_rewrites" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "automatic_https_rewrites"
  value      = var.automatic_https_rewrites
}

resource "cloudflare_zone_setting" "min_tls_version" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "min_tls_version"
  value      = var.min_tls_version
}

resource "cloudflare_zone_setting" "tls_1_3" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "tls_1_3"
  value      = var.tls_1_3_enabled ? "on" : "off"
}

# Security settings
resource "cloudflare_zone_setting" "security_level" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "security_level"
  value      = var.security_level
}

resource "cloudflare_zone_setting" "challenge_ttl" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "challenge_ttl"
  value      = var.challenge_ttl
}

resource "cloudflare_zone_setting" "browser_check" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "browser_check"
  value      = var.browser_check ? "on" : "off"
}

resource "cloudflare_zone_setting" "email_obfuscation" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "email_obfuscation"
  value      = var.email_obfuscation ? "on" : "off"
}

resource "cloudflare_zone_setting" "hotlink_protection" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "hotlink_protection"
  value      = var.hotlink_protection ? "on" : "off"
}

# Performance settings
resource "cloudflare_zone_setting" "brotli" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "brotli"
  value      = var.brotli ? "on" : "off"
}

resource "cloudflare_zone_setting" "early_hints" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "early_hints"
  value      = var.early_hints ? "on" : "off"
}

resource "cloudflare_zone_setting" "http2" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "http2"
  value      = var.http2 ? "on" : "off"
}

resource "cloudflare_zone_setting" "http3" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "http3"
  value      = var.http3 ? "on" : "off"
}

resource "cloudflare_zone_setting" "zero_rtt" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "0rtt"
  value      = var.zero_rtt ? "on" : "off"
}

resource "cloudflare_zone_setting" "minify" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "minify"
  value = {
    css  = var.minify_css ? "on" : "off"
    js   = var.minify_js ? "on" : "off"
    html = var.minify_html ? "on" : "off"
  }
}

# Caching settings
resource "cloudflare_zone_setting" "browser_cache_ttl" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "browser_cache_ttl"
  value      = var.browser_cache_ttl
}

# Network settings
resource "cloudflare_zone_setting" "ipv6" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "ipv6"
  value      = var.ipv6 ? "on" : "off"
}

resource "cloudflare_zone_setting" "websockets" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "websockets"
  value      = var.websockets ? "on" : "off"
}

resource "cloudflare_zone_setting" "development_mode" {
  zone_id    = cloudflare_zone.this.id
  setting_id = "development_mode"
  value      = var.development_mode ? "on" : "off"
}

# DNSSEC
resource "cloudflare_zone_dnssec" "this" {
  count   = var.dnssec_enabled ? 1 : 0
  zone_id = cloudflare_zone.this.id
}
