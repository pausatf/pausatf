terraform {
  required_version = ">= 1.10.0"

  required_providers {
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 5.17"
    }
  }

  cloud {
    organization = "pausatf"

    workspaces {
      name = "pausatf-cloudflare"
    }
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

resource "cloudflare_zone_setting" "tls_1_3" {
  zone_id    = cloudflare_zone.pausatf.id
  setting_id = "tls_1_3"
  value      = "on"
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

resource "cloudflare_zone_setting" "security_header" {
  zone_id    = cloudflare_zone.pausatf.id
  setting_id = "security_header"
  value = {
    strict_transport_security = {
      enabled            = true
      max_age            = 15552000
      include_subdomains = true
      preload            = true
      nosniff            = true
    }
  }
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
  comment = "PROD cutover to prod-v2 tunnel (was A 165.22.153.191)"
}

resource "cloudflare_dns_record" "www" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "www"
  content = "3e83d690-bff7-4a26-aead-ca84cf0a2270.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "PROD cutover to prod-v2 tunnel (was A 165.22.153.191)"
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

resource "cloudflare_dns_record" "direct_ssh" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "direct-ssh"
  content = "165.22.153.191"
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Direct SSH access (DNS only, not proxied)"
}

resource "cloudflare_dns_record" "runners" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "runners"
  content = "165.227.114.184"
  type    = "A"
  ttl     = 1
  proxied = false
}

# =============================================================================
# DNS Records — Staging
# =============================================================================

resource "cloudflare_dns_record" "staging" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "staging"
  content = "64.227.85.73"
  type    = "A"
  ttl     = 1
  proxied = true
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
  comment = "Production server alias"
}

resource "cloudflare_dns_record" "ssh" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "ssh"
  content = "942e32fe-29b5-4ca2-b876-b3e3b3f0b9c6.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
}

resource "cloudflare_dns_record" "ssh_stage_v2" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "ssh-stage-v2"
  content = "cbbd07dc-3e97-4923-b582-1fafab43c01b.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "cloudflared tunnel SSH (stage-v2)"
}

resource "cloudflare_dns_record" "ssh_v2" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "ssh-v2"
  content = "3e83d690-bff7-4a26-aead-ca84cf0a2270.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "pausatf-prod-v2 tunnel (migration build)"
}

resource "cloudflare_dns_record" "v2canary" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "v2canary"
  content = "3e83d690-bff7-4a26-aead-ca84cf0a2270.cfargotunnel.com"
  type    = "CNAME"
  ttl     = 1
  proxied = true
  comment = "cloudflared tunnel (prod-v2 canary)"
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
}

resource "cloudflare_dns_record" "sendgrid_delivery" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "em5172"
  content = "u51871933.wl184.sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "sendgrid"
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
}

resource "cloudflare_dns_record" "sendgrid_url7741" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "url7741"
  content = "sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
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
  comment = "sendgrid"
}

resource "cloudflare_dns_record" "sendgrid_dkim_s2" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "s2._domainkey"
  content = "s2.domainkey.u51871933.wl184.sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "sendgrid"
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
}

resource "cloudflare_dns_record" "mx_alt1" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt1.aspmx.l.google.com"
  type     = "MX"
  priority = 5
  ttl      = 1
}

resource "cloudflare_dns_record" "mx_alt2" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt2.aspmx.l.google.com"
  type     = "MX"
  priority = 5
  ttl      = 1
}

resource "cloudflare_dns_record" "mx_alt3" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt3.aspmx.l.google.com"
  type     = "MX"
  priority = 10
  ttl      = 1
}

resource "cloudflare_dns_record" "mx_alt4" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt4.aspmx.l.google.com"
  type     = "MX"
  priority = 10
  ttl      = 1
}

# =============================================================================
# DNS Records — TXT
# =============================================================================

resource "cloudflare_dns_record" "spf" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  content = "v=spf1 include:_spf.google.com include:sendgrid.net ~all"
  type    = "TXT"
  ttl     = 3600
  comment = "SPF record for Google Workspace and SendGrid"
}

resource "cloudflare_dns_record" "google_site_verification" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  content = "google-site-verification=TNLNBt7i-pSApITlOVOAVH5MT9YH16jTAXIIwHrmCLg" # pragma: allowlist secret
  type    = "TXT"
  ttl     = 600
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
  content = "\"v=DKIM1; h=sha256; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiweykoi+o48IOGuP7GR3X0MOExCUDY/BCRHoWBnh3rChl7WhdyCxW3jgq1daEjPPqoi7sJvdg5hEQVsgVRQP4DcnQDVjGMbASQtrY4WmB1VebF+RPJB2ECPsEDTpeiI5ZyUAwJaVX7r6bznU67g7LvFq35yIo4sdlmtZGV+i0H4cpYH9+3JJ78k\" \"m4KXwaf9xUJCWF6nxeD+qG6Fyruw1Qlbds2r85U9dkNDVAS3gioCvELryh1TxKGiVTkg4wqHTyHfWsp7KD3WQHYJn0RyfJJu6YEmL77zonn7p2SRMvTMP3ZEXibnC9gz3nnhR6wcYL8Q7zXypKTMD58bTixDSJwIDAQAB\"" # pragma: allowlist secret
  type    = "TXT"
  ttl     = 1
}

resource "cloudflare_dns_record" "dkim_mail" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "mail._domainkey"
  content = "v=DKIM1; h=sha256; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtI1RFbT2Q/l8jxNfidHBMpDaw6UxnO3NwbJo58DLyKJX0WfICTpvUxPopz+xOl6Lcu27hFSZwWKgSEnDjhTdE1ytMuNNgUJ7O+n82VQQdJ5USiYGEHoIGFmuqcm6Ctwl3xQKHLeDsY56E16ry0U20necZuBOMjaRL8IAkaSUNlNpR1okwG0UC/SI/8t/KN+3b63OI/m9SFZeWajhMER+f9P3yWxo6EnMirC6tkooWlCP1DpAvJCz1CMtTewbtPUahUqhURKLVJIVYyT9mxGY0+qxMOWGMl0GoZjKZ39C0vMlWTTXNkbakav4HVUK2F6a3n1pG1xg0IRkRTXdhbNTywIDAQAB" # pragma: allowlist secret
  type    = "TXT"
  ttl     = 3600
}

resource "cloudflare_dns_record" "acme_challenge_www" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "_acme-challenge.www"
  content = "JmQAJz96_x3ZwO5VqAfTMWAu5GMU7HXgGcaCpoRB2Cg" # pragma: allowlist secret
  type    = "TXT"
  ttl     = 120
}

# =============================================================================
# DNS Records — CAA
# =============================================================================

resource "cloudflare_dns_record" "caa_letsencrypt_issue" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow Let's Encrypt to issue SSL certificates"

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
  comment = "Allow Let's Encrypt to issue wildcard SSL certificates"

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
  comment = "Allow DigiCert (Cloudflare) to issue SSL certificates"

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
  comment = "Allow DigiCert (Cloudflare) to issue wildcard SSL certificates"

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
  comment = "Email for CAA violation reports"

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

  data = {
    flags = 0
    tag   = "issuewild"
    value = "pki.goog"
  }
}

# =============================================================================
# Cache Rules — match the live Cloudflare ruleset
# =============================================================================

resource "cloudflare_ruleset" "cache_rules" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "pausatf cache rules"
  kind    = "zone"
  phase   = "http_request_cache_settings"

  rules = [
    {
      action = "set_cache_settings"
      action_parameters = {
        cache = false
      }
      description = "Apex is never cacheable so the apex->www 301 page rule always fires (fixes cached-200 at apex)"
      enabled     = true
      expression  = "(http.host eq \"pausatf.org\")"
    },
    {
      action = "set_cache_settings"
      action_parameters = {
        cache = false
      }
      description = "Bypass cache for /data (Jeff Teeters results, must be fresh)"
      enabled     = true
      expression  = "(starts_with(http.request.uri.path, \"/data/\"))"
    },
    {
      action = "set_cache_settings"
      action_parameters = {
        cache = true
        browser_ttl = {
          default = 300
          mode    = "override_origin"
        }
        edge_ttl = {
          default = 7200
          mode    = "override_origin"
        }
      }
      description = "Cache public pages for anonymous visitors"
      enabled     = true
      expression  = "(http.host eq \"www.pausatf.org\" and not starts_with(http.request.uri.path, \"/wp-admin\") and not starts_with(http.request.uri.path, \"/wp-login\") and not starts_with(http.request.uri.path, \"/wp-json\") and not starts_with(http.request.uri.path, \"/wp-cron\") and not starts_with(http.request.uri.path, \"/data/\") and not any(http.request.cookies[\"wordpress_logged_in_*\"][*] ne \"\"))"
    },
    {
      action = "set_cache_settings"
      action_parameters = {
        cache = false
      }
      description = "Bypass cache for admin and logged-in users"
      enabled     = true
      expression  = " starts_with(http.request.uri.path, \"/wp-admin\") or\n  starts_with(http.request.uri.path, \"/wp-login\") or\n  starts_with(http.request.uri.path, \"/wp-json\") or\n  http.cookie contains \"wordpress_logged_in_\" or\n  http.cookie contains \"wp-postpass_\" or\n  http.cookie contains \"comment_author_\""
    },
  ]
}

# =============================================================================
# Rate Limiting — match the live wp-login brute-force rule
# =============================================================================

resource "cloudflare_ruleset" "rate_limit" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "default"
  kind    = "zone"
  phase   = "http_ratelimit"

  rules = [
    {
      action = "block"
      ratelimit = {
        characteristics     = ["ip.src", "cf.colo.id"]
        period              = 10
        requests_per_period = 5
        mitigation_timeout  = 10
      }
      description = "wp-login brute-force rate limit"
      enabled     = true
      expression  = "(http.request.uri.path eq \"/wp-login.php\" and http.request.method eq \"POST\")"
    },
  ]
}
