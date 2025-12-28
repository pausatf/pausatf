# Cloudflare DNS Module

Terraform module for managing DNS records in a Cloudflare zone with support for proxied records, custom TTLs, and priorities.

## Features

- **Multiple Record Types**: A, AAAA, CNAME, MX, TXT, NS, SRV, CAA, etc.
- **Cloudflare Proxy**: Enable orange-cloud proxying for web traffic
- **Custom TTL**: Configure time-to-live for each record
- **Priority Support**: Set priority for MX and SRV records
- **Comments**: Add descriptive comments to records
- **Bulk Management**: Create multiple DNS records in a single declaration

## Usage

```hcl
module "dns_records" {
  source = "../../modules/cloudflare/dns"

  zone_id = module.pausatf_zone.zone_id

  dns_records = [
    # A record with Cloudflare proxy
    {
      name    = "@"
      type    = "A"
      value   = "64.225.40.54"
      proxied = true
      comment = "Production server"
    },
    # CNAME record
    {
      name    = "www"
      type    = "CNAME"
      value   = "pausatf.org"
      proxied = true
      comment = "WWW redirect"
    },
    # MX record with priority
    {
      name     = "@"
      type     = "MX"
      value    = "mail.example.com"
      priority = 10
      ttl      = 3600
      comment  = "Primary mail server"
    },
    # TXT record for SPF
    {
      name    = "@"
      type    = "TXT"
      value   = "v=spf1 include:_spf.google.com ~all"
      comment = "SPF record"
    }
  ]
}
```

## Requirements

| Name | Version |
|------|---------|
| terraform | >= 1.0 |
| cloudflare | ~> 5.0 |

## Inputs

| Name | Description | Type | Required |
|------|-------------|------|----------|
| zone_id | Cloudflare zone ID | `string` | Yes |
| dns_records | List of DNS records to create | `list(object)` | No (default: `[]`) |

### DNS Record Object Structure

```hcl
{
  name     = string           # Record name (use "@" for apex)
  type     = string           # Record type (A, AAAA, CNAME, MX, TXT, etc.)
  value    = string           # Record value
  ttl      = number           # TTL in seconds (optional, default: 1 = auto)
  proxied  = bool             # Enable Cloudflare proxy (optional, default: false)
  priority = number           # Priority for MX/SRV records (optional)
  comment  = string           # Descriptive comment (optional, default: "Managed by Terraform")
}
```

## Outputs

| Name | Description |
|------|-------------|
| record_ids | Map of DNS record IDs (key: "TYPE-NAME") |
| record_hostnames | Map of DNS record hostnames (key: "TYPE-NAME") |

## Examples

### Complete Production DNS Setup

```hcl
module "production_dns" {
  source = "../../modules/cloudflare/dns"

  zone_id = module.zone.zone_id

  dns_records = [
    # Web servers
    {
      name    = "@"
      type    = "A"
      value   = "64.225.40.54"
      proxied = true
      comment = "Production web server (ftp.pausatf.org)"
    },
    {
      name    = "www"
      type    = "CNAME"
      value   = "pausatf.org"
      proxied = true
      comment = "WWW redirect"
    },

    # Staging environment
    {
      name    = "stage"
      type    = "A"
      value   = "64.227.85.73"
      proxied = false
      ttl     = 3600
      comment = "Staging server"
    },

    # Mail configuration
    {
      name     = "@"
      type     = "MX"
      value    = "mail.pausatf.org"
      priority = 10
      ttl      = 3600
      comment  = "Primary mail server"
    },
    {
      name     = "@"
      type     = "MX"
      value    = "backup-mail.pausatf.org"
      priority = 20
      ttl      = 3600
      comment  = "Backup mail server"
    },

    # Email security (SPF, DKIM, DMARC)
    {
      name    = "@"
      type    = "TXT"
      value   = "v=spf1 include:_spf.google.com ~all"
      comment = "SPF record for Google Workspace"
    },
    {
      name    = "_dmarc"
      type    = "TXT"
      value   = "v=DMARC1; p=quarantine; rua=mailto:dmarc@pausatf.org"
      comment = "DMARC policy"
    },

    # Verification records
    {
      name    = "@"
      type    = "TXT"
      value   = "google-site-verification=abc123..."
      comment = "Google Search Console verification"
    }
  ]
}
```

### Development Environment DNS

```hcl
module "dev_dns" {
  source = "../../modules/cloudflare/dns"

  zone_id = module.zone.zone_id

  dns_records = [
    {
      name    = "dev"
      type    = "A"
      value   = "127.0.0.1"
      proxied = false
      ttl     = 300  # Short TTL for dev
      comment = "Development environment"
    }
  ]
}
```

### Accessing Outputs

```hcl
# Get specific record ID
output "apex_record_id" {
  value = module.dns_records.record_ids["A-@"]
}

# Get all hostnames
output "all_hostnames" {
  value = module.dns_records.record_hostnames
}
```

## Supported Record Types

| Type | Description | Requires Priority |
|------|-------------|-------------------|
| A | IPv4 address | No |
| AAAA | IPv6 address | No |
| CNAME | Canonical name | No |
| MX | Mail exchange | Yes |
| TXT | Text record | No |
| NS | Name server | No |
| SRV | Service record | Yes |
| CAA | Certificate Authority Authorization | No |
| PTR | Pointer record | No |
| SOA | Start of Authority | No |

## Best Practices

1. **Apex Records**: Use `"@"` for the zone apex (root domain)
2. **Cloudflare Proxy**:
   - Enable `proxied = true` for web traffic (A, AAAA, CNAME)
   - Disable for mail, DNS, and other service records
3. **TTL Settings**:
   - Use `ttl = 1` (auto) for proxied records
   - Use `ttl = 300` (5 min) for development
   - Use `ttl = 3600` (1 hour) or higher for production
4. **Comments**: Always add meaningful comments for documentation
5. **MX Records**: Always set priority (lower = higher priority)

## Cloudflare Proxy Behavior

When `proxied = true`:
- Traffic routes through Cloudflare's network
- Hides origin server IP
- Enables DDoS protection and WAF
- TTL is automatically managed by Cloudflare
- Only works with A, AAAA, and CNAME records

When `proxied = false`:
- Direct DNS resolution to origin
- Required for MX, NS, TXT, and most other record types
- Custom TTL can be set

## Common Use Cases

### 1. Subdomain for Service

```hcl
{
  name    = "api"
  type    = "A"
  value   = "10.0.0.5"
  proxied = true
  comment = "API server"
}
```

### 2. Wildcard Record

```hcl
{
  name    = "*"
  type    = "A"
  value   = "64.225.40.54"
  proxied = false
  comment = "Wildcard subdomain"
}
```

### 3. Email Verification

```hcl
{
  name    = "default._domainkey"
  type    = "TXT"
  value   = "v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3..."
  comment = "DKIM public key"
}
```

## Troubleshooting

### Issue: Record not updating

**Solution**: Check if the record is proxied. Proxied records may take longer to propagate.

### Issue: MX record creation fails

**Solution**: Ensure `priority` is set for MX records and `proxied = false`.

### Issue: CNAME conflicts with other records

**Solution**: CNAME records cannot coexist with other records at the same name. Use A/AAAA instead.

## Notes

- Records are identified by `"${type}-${name}"` in outputs
- Cloudflare automatically manages TTL for proxied records (ignores `ttl` parameter)
- Changes to DNS records may take up to 5 minutes to propagate globally
- Maximum of 1000 DNS records per zone on free plan

## Related Modules

- [cloudflare/zone](../zone) - Create and configure Cloudflare zones

## Version History

- **2025-12-28**: Updated to Cloudflare provider ~> 5.0
- **2025-12-22**: Initial module creation

## Maintainer

PAUSATF Infrastructure Team
