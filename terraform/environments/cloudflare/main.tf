terraform {
  required_version = ">= 1.6.0"

  required_providers {
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 5.0"
    }
  }

  backend "s3" {
    # DigitalOcean Spaces backend
    endpoint                    = "sfo2.digitaloceanspaces.com"
    region                      = "us-west-1"
    bucket                      = "pausatf-terraform-state"
    key                         = "cloudflare/terraform.tfstate"
    skip_credentials_validation = true
    skip_metadata_api_check     = true
  }
}

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

# Import existing zone
# terraform import cloudflare_zone.pausatf 67b87131144a68ad5ed43ebfd4e6d811
resource "cloudflare_zone" "pausatf" {
  account_id = var.cloudflare_account_id
  zone       = "pausatf.org"
  plan       = "free"
  type       = "full"
}

# A Records - Production
resource "cloudflare_record" "root" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  content = var.production_ip
  type    = "A"
  ttl     = 1
  proxied = true
  comment = "Main site (production)"
}

resource "cloudflare_record" "www" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "www"
  content = var.production_ip
  type    = "A"
  ttl     = 1
  proxied = true
  comment = "WWW redirect to main site"
}

resource "cloudflare_record" "ftp" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "ftp"
  content = var.production_ip
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Production droplet (direct access)"
}

resource "cloudflare_record" "mail" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "mail"
  content = var.production_ip
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Mail server"
}

resource "cloudflare_record" "monitor" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "monitor"
  content = var.production_ip
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Monitoring dashboard"
}

# A Records - Staging
resource "cloudflare_record" "stage" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "stage"
  content = var.staging_ip
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Staging environment"
}

resource "cloudflare_record" "staging" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "staging"
  content = var.staging_ip
  type    = "A"
  ttl     = 1
  proxied = false
  comment = "Staging environment (alias)"
}

# CNAME Records
resource "cloudflare_record" "prod" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "prod"
  content = "ftp.pausatf.org"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "Production alias"
}

# SendGrid Email Records
resource "cloudflare_record" "sendgrid_51871933" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "51871933"
  content = "sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid email tracking"
}

resource "cloudflare_record" "sendgrid_em5172" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "em5172"
  content = "u51871933.wl184.sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid email delivery"
}

resource "cloudflare_record" "sendgrid_url7068" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "url7068"
  content = "sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid link tracking"
}

resource "cloudflare_record" "sendgrid_url7741" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "url7741"
  content = "sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid link tracking"
}

# DKIM Records (SendGrid)
resource "cloudflare_record" "sendgrid_dkim_s1" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "s1._domainkey"
  content = "s1.domainkey.u51871933.wl184.sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid DKIM signature 1"
}

resource "cloudflare_record" "sendgrid_dkim_s2" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "s2._domainkey"
  content = "s2.domainkey.u51871933.wl184.sendgrid.net"
  type    = "CNAME"
  ttl     = 1
  proxied = false
  comment = "SendGrid DKIM signature 2"
}

# MX Records (Google Workspace)
resource "cloudflare_record" "mx_primary" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "aspmx.l.google.com"
  type     = "MX"
  priority = 1
  ttl      = 1
  comment  = "Google Workspace MX (primary)"
}

resource "cloudflare_record" "mx_alt1" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt1.aspmx.l.google.com"
  type     = "MX"
  priority = 5
  ttl      = 1
  comment  = "Google Workspace MX (backup 1)"
}

resource "cloudflare_record" "mx_alt2" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt2.aspmx.l.google.com"
  type     = "MX"
  priority = 5
  ttl      = 1
  comment  = "Google Workspace MX (backup 2)"
}

resource "cloudflare_record" "mx_alt3" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt3.aspmx.l.google.com"
  type     = "MX"
  priority = 10
  ttl      = 1
  comment  = "Google Workspace MX (backup 3)"
}

resource "cloudflare_record" "mx_alt4" {
  zone_id  = cloudflare_zone.pausatf.id
  name     = "@"
  content  = "alt4.aspmx.l.google.com"
  type     = "MX"
  priority = 10
  ttl      = 1
  comment  = "Google Workspace MX (backup 4)"
}

# TXT Records
resource "cloudflare_record" "spf" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  content = "v=spf1 include:_spf.google.com include:sendgrid.net ~all"
  type    = "TXT"
  ttl     = 1
  comment = "SPF record for Google Workspace and SendGrid"
}

resource "cloudflare_record" "google_site_verification" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  content = "google-site-verification=TNLNBt7i-pSApITlOVOAVH5MT9YH16jTAXIIwHrmCLg"
  type    = "TXT"
  ttl     = 1
  comment = "Google Search Console verification"
}

resource "cloudflare_record" "dmarc" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "_dmarc"
  content = "v=DMARC1; p=none;"
  type    = "TXT"
  ttl     = 1
  comment = "DMARC policy (monitoring mode)"
}

resource "cloudflare_record" "dkim_cloudflare" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "cf2024-1._domainkey"
  content = "v=DKIM1; h=sha256; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiweykoi+o48IOGuP7GR3X0MOExCUDY/BCRHoWBnh3rChl7WhdyCxW3jgq1daEjPPqoi7sJvdg5hEQVsgVRQP4DcnQDVjGMbASQtrY4WmB1VebF+RPJB2ECPsEDTpeiI5ZyUAwJaVX7r6bznU67g7LvFq35yIo4sdlmtZGV+i0H4cpYH9+3JJ78km4KXwaf9xUJCWF6nxeD+qG6Fyruw1Qlbds2r85U9dkNDVAS3gioCvELryh1TxKGiVTkg4wqHTyHfWsp7KD3WQHYJn0RyfJJu6YEmL77zonn7p2SRMvTMP3ZEXibnC9gz3nnhR6wcYL8Q7zXypKTMD58bTixDSJwIDAQAB"
  type    = "TXT"
  ttl     = 1
  comment = "Cloudflare DKIM signature"
}

# ACME Challenge (Let's Encrypt)
# Note: This may be temporary and could be removed after SSL cert is issued
resource "cloudflare_record" "acme_challenge_www" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "_acme-challenge.www"
  content = "JmQAJz96_x3ZwO5VqAfTMWAu5GMU7HXgGcaCpoRB2Cg"
  type    = "TXT"
  ttl     = 1
  comment = "Let's Encrypt ACME challenge (may be temporary)"
}

# CAA Records (Certificate Authority Authorization)
resource "cloudflare_record" "caa_letsencrypt_issue" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow Let's Encrypt to issue certificates"

  data {
    flags = 0
    tag   = "issue"
    value = "letsencrypt.org"
  }
}

resource "cloudflare_record" "caa_letsencrypt_issuewild" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow Let's Encrypt to issue wildcard certificates"

  data {
    flags = 0
    tag   = "issuewild"
    value = "letsencrypt.org"
  }
}

resource "cloudflare_record" "caa_digicert_issue" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow DigiCert to issue certificates"

  data {
    flags = 0
    tag   = "issue"
    value = "digicert.com"
  }
}

resource "cloudflare_record" "caa_digicert_issuewild" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Allow DigiCert to issue wildcard certificates"

  data {
    flags = 0
    tag   = "issuewild"
    value = "digicert.com"
  }
}

resource "cloudflare_record" "caa_iodef" {
  zone_id = cloudflare_zone.pausatf.id
  name    = "@"
  type    = "CAA"
  ttl     = 1
  comment = "Certificate issue notification email"

  data {
    flags = 0
    tag   = "iodef"
    value = "mailto:admin@pausatf.org"
  }
}
