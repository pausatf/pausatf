terraform {
  required_version = ">= 1.10.0"

  required_providers {
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 5.17"
    }
  }

  backend "s3" {
    key = "cloudflare/terraform.tfstate"
  }
}

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

# =============================================================================
# Zone
# =============================================================================

# Import existing zone:
#   terraform import cloudflare_zone.pausatf 67b87131144a68ad5ed43ebfd4e6d811
resource "cloudflare_zone" "pausatf" {
  account = {
    id = var.cloudflare_account_id
  }
  name = "pausatf.org"
  type = "full"
}

# =============================================================================
# Zone Settings — SSL Full (Strict), HSTS, Brotli, TLS 1.2+
# =============================================================================

resource "cloudflare_zone_setting" "ssl" {
  zone_id    = cloudflare_zone.pausatf.id
  setting_id = "ssl"
  value      = "strict"
}

resource "cloudflare_zone_setting" "always_use_https" {
  zone_id    = cloudflare_zone.pausatf.id
  setting_id = "always_use_https"
  value      = "on"
}

resource "cloudflare_zone_setting" "min_tls_version" {
  zone_id    = cloudflare_zone.pausatf.id
  setting_id = "min_tls_version"
  value      = "1.2"
}

resource "cloudflare_zone_setting" "brotli" {
  zone_id    = cloudflare_zone.pausatf.id
  setting_id = "brotli"
  value      = "on"
}

resource "cloudflare_zone_setting" "opportunistic_encryption" {
  zone_id    = cloudflare_zone.pausatf.id
  setting_id = "opportunistic_encryption"
  value      = "on"
}

# =============================================================================
# DNS Records — Production
# =============================================================================

resource "cloudflare_dns_record" "root" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  content = "3e83d690-bff7-4a26-aead-ca84cf0a2270.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "Main site routed through the production Cloudflare Tunnel"
}

resource "cloudflare_dns_record" "www" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "www"
  content = "3e83d690-bff7-4a26-aead-ca84cf0a2270.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "WWW routed through the production Cloudflare Tunnel"
}

resource "cloudflare_dns_record" "ftp" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "ftp"
  content = "165.22.153.191"
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Production droplet (direct access)"
}

resource "cloudflare_dns_record" "mail" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "mail"
  content = "165.22.153.191"
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Mail server"
}

resource "cloudflare_dns_record" "monitor" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "monitor"
  content = "165.22.153.191"
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Monitoring dashboard"
}

resource "cloudflare_dns_record" "dev" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "dev"
  content = "157.230.128.120"
  type    = "A"
  ttl     = 1
  proxied = true
  comment = "Development droplet"
}

resource "cloudflare_dns_record" "direct_ssh" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "direct-ssh"
  content = "165.22.153.191"
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Direct SSH endpoint"
}

resource "cloudflare_dns_record" "runners" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "runners"
  content = "165.227.114.184"
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "GitHub Actions runner host"
}

# =============================================================================
# DNS Records — Staging
# =============================================================================

resource "cloudflare_dns_record" "stage" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "stage"
  content = "cbbd07dc-3e97-4923-b582-1fafab43c01b.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "Staging routed through the staging Cloudflare Tunnel"
}

resource "cloudflare_dns_record" "staging" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "staging"
  content = "64.227.85.73"
  type    = "A"
  ttl     = 1
  proxied = true
  comment = "Legacy staging endpoint (live DNS points to 64.227.85.73)"
}

resource "cloudflare_ruleset" "transit_redirect" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "Redirect transit to www"
  kind    = "zone"
  phase   = "http_request_dynamic_redirect"

  rules = [
    {
      action = "redirect"
      action_parameters = {
        from_value = {
          preserve_query_string = true
          status_code           = 301
          target_url = {
            value = "https://www.pausatf.org"
          }
        }
      }
      description = "Redirect transit.pausatf.org to www.pausatf.org"
      enabled     = true
      expression  = "(http.host eq \"transit.pausatf.org\")"
    }
  ]
}

# =============================================================================
# DNS Records — CNAME
# =============================================================================

resource "cloudflare_dns_record" "prod" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "prod"
  content = "ftp.pausatf.org"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "Production alias"
}

resource "cloudflare_dns_record" "ssh" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "ssh"
  content = "942e32fe-29b5-4ca2-b876-b3e3b3f0b9c6.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "SSH Cloudflare Tunnel"
}

resource "cloudflare_dns_record" "ssh_stage_v2" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "ssh-stage-v2"
  content = "cbbd07dc-3e97-4923-b582-1fafab43c01b.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "Staging SSH Cloudflare Tunnel"
}

resource "cloudflare_dns_record" "ssh_v2" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "ssh-v2"
  content = "3e83d690-bff7-4a26-aead-ca84cf0a2270.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "Production SSH Cloudflare Tunnel v2"
}

resource "cloudflare_dns_record" "v2canary" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "v2canary"
  content = "3e83d690-bff7-4a26-aead-ca84cf0a2270.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "Production canary Cloudflare Tunnel"
}

# =============================================================================
# DNS Records — SendGrid
# =============================================================================

resource "cloudflare_dns_record" "sendgrid_51871933" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "51871933"
  content = "sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid email tracking"
}

resource "cloudflare_dns_record" "sendgrid_delivery" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "em5172"
  content = "u51871933.wl184.sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid email delivery"
}

moved {
  from = cloudflare_dns_record.sendgrid_REDACTED_SENDGRID
  to   = cloudflare_dns_record.sendgrid_delivery
}

resource "cloudflare_dns_record" "sendgrid_url7068" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "url7068"
  content = "sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid link tracking"
}

resource "cloudflare_dns_record" "sendgrid_url7741" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "url7741"
  content = "sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid link tracking"
}

# =============================================================================
# DNS Records — DKIM (SendGrid)
# =============================================================================

resource "cloudflare_dns_record" "sendgrid_dkim_s1" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "s1._domainkey"
  content = "s1.domainkey.u51871933.wl184.sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid DKIM signature 1"
}

resource "cloudflare_dns_record" "sendgrid_dkim_s2" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "s2._domainkey"
  content = "s2.domainkey.u51871933.wl184.sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid DKIM signature 2"
}

# =============================================================================
# DNS Records — MX (Google Workspace)
# =============================================================================

resource "cloudflare_dns_record" "mx_primary" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "aspmx.l.google.com"
  type     = "MX"
  priority = 1
  ttl      = 1
  comment  = "Google Workspace MX (primary)"
}

resource "cloudflare_dns_record" "mx_alt1" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt1.aspmx.l.google.com"
  type     = "MX"
  priority = 5
  ttl      = 1
  comment  = "Google Workspace MX (backup 1)"
}

resource "cloudflare_dns_record" "mx_alt2" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt2.aspmx.l.google.com"
  type     = "MX"
  priority = 5
  ttl      = 1
  comment  = "Google Workspace MX (backup 2)"
}

resource "cloudflare_dns_record" "mx_alt3" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt3.aspmx.l.google.com"
  type     = "MX"
  priority = 10
  ttl      = 1
  comment  = "Google Workspace MX (backup 3)"
}

resource "cloudflare_dns_record" "mx_alt4" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt4.aspmx.l.google.com"
  type     = "MX"
  priority = 10
  ttl      = 1
  comment  = "Google Workspace MX (backup 4)"
}

# =============================================================================
# DNS Records — TXT
# =============================================================================

resource "cloudflare_dns_record" "spf" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  content = "v=spf1 include:_spf.google.com include:sendgrid.net ~all"
  type    = "TXT"
  ttl     = 1
  comment = "SPF record for Google Workspace and SendGrid"
}

resource "cloudflare_dns_record" "google_site_verification" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  content = "google-site-verification=TNLNBt7i-pSApITlOVOAVH5MT9YH16jTAXIIwHrmCLg" # pragma: allowlist secret
  type    = "TXT"
  ttl     = 1
  comment = "Google Search Console verification"
}

resource "cloudflare_dns_record" "dmarc" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "_dmarc"
  content = "v=DMARC1; p=quarantine; rua=mailto:admin@pausatf.org; pct=100;"
  type    = "TXT"
  ttl     = 1
  comment = "DMARC policy (quarantine enforcement, aggregate reports to admin@pausatf.org)"
}

resource "cloudflare_dns_record" "dkim_cloudflare" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "cf2024-1._domainkey"
  # Public DKIM key published by Cloudflare; this is not a private credential.
  # pragma: allowlist secret
  content = "v=DKIM1; h=sha256; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiweykoi+o48IOGuP7GR3X0MOExCUDY/BCRHoWBnh3rChl7WhdyCxW3jgq1daEjPPqoi7sJvdg5hEQVsgVRQP4DcnQDVjGMbASQtrY4WmB1VebF+RPJB2ECPsEDTpeiI5ZyUAwJaVX7r6bznU67g7LvFq35yIo4sdlmtZGV+i0H4cpYH9+3JJ78km4KXwaf9xUJCWF6nxeD+qG6Fyruw1Qlbds2r85U9dkNDVAS3gioCvELryh1TxKGiVTkg4wqHTyHfWsp7KD3WQHYJn0RyfJJu6YEmL77zonn7p2SRMvTMP3ZEXibnC9gz3nnhR6wcYL8Q7zXypKTMD58bTixDSJwIDAQAB" # pragma: allowlist secret
  type    = "TXT"
  ttl     = 1
  comment = "Cloudflare DKIM signature"
}

resource "cloudflare_dns_record" "dkim_mail" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "mail._domainkey"
  content = "v=DKIM1; h=sha256; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtI1RFbT2Q/l8jxNfidHBMpDaw6UxnO3NwbJo58DLyKJX0WfICTpvUxPopz+xOl6Lcu27hFSZwWKgSEnDjhTdE1ytMuNNgUJ7O+n82VQQdJ5USiYGEHoIGFmuqcm6Ctwl3xQKHLeDsY56E16ry0U20necZuBOMjaRL8IAkaSUNlNpR1okwG0UC/SI/8t/KN+3b63OI/m9SFZeWajhMER+f9P3yWxo6EnMirC6tkooWlCP1DpAvJCz1CMtTewbtPUahUqhURKLVJIVYyT9mxGY0+qxMOWGMl0GoZjKZ39C0vMlWTTXNkbakav4HVUK2F6a3n1pG1xg0IRkRTXdhbNTywIDAQAB" # pragma: allowlist secret
  type    = "TXT"
  ttl     = 1
  comment = "Mail DKIM signature"
}

resource "cloudflare_dns_record" "acme_challenge_www" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "_acme-challenge.www"
  content = "JmQAJz96_x3ZwO5VqAfTMWAu5GMU7HXgGcaCpoRB2Cg" # pragma: allowlist secret
  type    = "TXT"
  ttl     = 120
  comment = "Observed ACME validation record; verify ownership before refreshing"
}

# =============================================================================
# DNS Records — CAA
# =============================================================================

resource "cloudflare_dns_record" "caa_letsencrypt_issue" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow Let's Encrypt to issue certificates"

  data = {
    flags = 0
    tag   = "issue"
    value = "letsencrypt.org"
  }
}

resource "cloudflare_dns_record" "caa_letsencrypt_issuewild" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow Let's Encrypt to issue wildcard certificates"

  data = {
    flags = 0
    tag   = "issuewild"
    value = "letsencrypt.org"
  }
}

resource "cloudflare_dns_record" "caa_digicert_issue" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow DigiCert to issue certificates"

  data = {
    flags = 0
    tag   = "issue"
    value = "digicert.com"
  }
}

resource "cloudflare_dns_record" "caa_digicert_issuewild" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow DigiCert to issue wildcard certificates"

  data = {
    flags = 0
    tag   = "issuewild"
    value = "digicert.com"
  }
}

resource "cloudflare_dns_record" "caa_iodef" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Certificate issue notification email"

  data = {
    flags = 0
    tag   = "iodef"
    value = "mailto:admin@pausatf.org"
  }
}

resource "cloudflare_dns_record" "caa_google_issue" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow Google Trust Services to issue certificates"

  data = {
    flags = 0
    tag   = "issue"
    value = "pki.goog"
  }
}

resource "cloudflare_dns_record" "caa_google_issuewild" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow Google Trust Services to issue wildcard certificates"

  data = {
    flags = 0
    tag   = "issuewild"
    value = "pki.goog"
  }
}

# =============================================================================
# Cache Rules — static asset caching + WP admin/auth bypass
# =============================================================================

resource "cloudflare_ruleset" "cache_rules" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "PAUSATF cache rules"
  kind    = "zone"
  phase   = "http_request_cache_settings"

  rules = [
    # Bypass cache for wp-admin and wp-login.php
    {
      action = "set_cache_settings"
      action_parameters = {
        cache = false
      }
      description = "Bypass cache for WordPress admin"
      enabled     = true
      expression  = "(starts_with(http.request.uri.path, \"/wp-admin\") or http.request.uri.path eq \"/wp-login.php\")"
    },
    # Bypass cache when WP auth cookies present
    {
      action = "set_cache_settings"
      action_parameters = {
        cache = false
      }
      description = "Bypass cache for authenticated users"
      enabled     = true
      expression  = "(http.cookie contains \"wordpress_logged_in_\" or http.cookie contains \"wp-postpass_\")"
    },
    # Bypass cache for preview requests
    {
      action = "set_cache_settings"
      action_parameters = {
        cache = false
      }
      description = "Bypass cache for preview requests"
      enabled     = true
      expression  = "(http.request.uri.query contains \"preview=true\")"
    },
    # Cache static assets aggressively
    {
      action = "set_cache_settings"
      action_parameters = {
        cache = true
        edge_ttl = {
          mode    = "override_origin"
          default = 604800 # 7 days
        }
        browser_ttl = {
          mode    = "override_origin"
          default = 86400 # 1 day
        }
      }
      description = "Cache static assets (7d edge, 1d browser)"
      enabled     = true
      expression  = "(http.request.uri.path.extension in {\"css\" \"js\" \"jpg\" \"jpeg\" \"png\" \"gif\" \"ico\" \"woff\" \"woff2\" \"ttf\" \"svg\" \"webp\"})"
    },
  ]
}

# =============================================================================
# WAF — Block xmlrpc.php
# =============================================================================

resource "cloudflare_ruleset" "waf_custom" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "PAUSATF WAF custom rules"
  kind    = "zone"
  phase   = "http_request_firewall_custom"

  rules = [
    {
      action      = "block"
      description = "Block xmlrpc.php access"
      enabled     = true
      expression  = "(http.request.uri.path eq \"/xmlrpc.php\")"
    },
  ]
}

# =============================================================================
# Rate Limiting — wp-login brute force protection
# =============================================================================

resource "cloudflare_ruleset" "rate_limit" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "PAUSATF rate limiting"
  kind    = "zone"
  phase   = "http_ratelimit"

  rules = [
    {
      action = "block"
      action_parameters = {
        response = {
          status_code  = 429
          content      = "Rate limit exceeded."
          content_type = "text/plain"
        }
      }
      ratelimit = {
        characteristics     = ["ip.src"]
        period              = 10
        requests_per_period = 5
        mitigation_timeout  = 3600
      }
      description = "Rate limit wp-login POST (5 req/10s per IP, block 1h)"
      enabled     = true
      expression  = "(http.request.uri.path eq \"/wp-login.php\" and http.request.method eq \"POST\")"
    },
  ]
}
