# Cloudflare Zone Module

Terraform module for creating and managing Cloudflare zones with comprehensive security, performance, and caching settings.

## Features

- **SSL/TLS Configuration**: Full SSL/TLS management with automatic HTTPS rewrites
- **Security Settings**: Browser checks, email obfuscation, hotlink protection
- **Performance Optimization**: HTTP/2, HTTP/3, Brotli compression, early hints, 0-RTT
- **Content Minification**: Automatic CSS, JavaScript, and HTML minification
- **DNSSEC Support**: Optional DNSSEC configuration
- **IPv6 Ready**: Full IPv6 support
- **WebSocket Support**: Enable WebSocket connections

## Usage

```hcl
module "pausatf_zone" {
  source = "../../modules/cloudflare/zone"

  account_id = var.cloudflare_account_id
  zone_name  = "pausatf.org"
  plan       = "free"

  # SSL/TLS
  ssl_mode                 = "strict"
  always_use_https         = "on"
  automatic_https_rewrites = "on"
  min_tls_version          = "1.2"
  tls_1_3_enabled          = true

  # Security
  security_level      = "medium"
  browser_check       = true
  email_obfuscation   = true
  hotlink_protection  = false

  # Performance
  brotli      = true
  early_hints = true
  http2       = true
  http3       = true
  zero_rtt    = true

  # Minification
  minify_css  = true
  minify_js   = true
  minify_html = true

  # DNSSEC
  dnssec_enabled = true
}
```

## Requirements

| Name | Version |
|------|---------|
| terraform | >= 1.0 |
| cloudflare | ~> 5.0 |

## Inputs

### Required Inputs

| Name | Description | Type |
|------|-------------|------|
| account_id | Cloudflare account ID | `string` |
| zone_name | Domain name for the zone | `string` |

### Optional Inputs

#### Zone Configuration

| Name | Description | Type | Default |
|------|-------------|------|---------|
| plan | Cloudflare plan (free, pro, business, enterprise) | `string` | `"free"` |
| type | Zone type (full, partial) | `string` | `"full"` |

#### SSL/TLS Settings

| Name | Description | Type | Default |
|------|-------------|------|---------|
| ssl_mode | SSL mode (off, flexible, full, strict) | `string` | `"strict"` |
| always_use_https | Always use HTTPS | `string` | `"on"` |
| automatic_https_rewrites | Enable automatic HTTPS rewrites | `string` | `"on"` |
| min_tls_version | Minimum TLS version (1.0, 1.1, 1.2, 1.3) | `string` | `"1.2"` |
| tls_1_3_enabled | Enable TLS 1.3 | `bool` | `true` |

#### Security Settings

| Name | Description | Type | Default |
|------|-------------|------|---------|
| security_level | Security level (off, essentially_off, low, medium, high, under_attack) | `string` | `"medium"` |
| challenge_ttl | Challenge TTL in seconds | `number` | `1800` |
| browser_check | Enable browser integrity check | `bool` | `true` |
| email_obfuscation | Enable email obfuscation | `bool` | `true` |
| hotlink_protection | Enable hotlink protection | `bool` | `false` |

#### Performance Settings

| Name | Description | Type | Default |
|------|-------------|------|---------|
| brotli | Enable Brotli compression | `bool` | `true` |
| early_hints | Enable early hints | `bool` | `true` |
| http2 | Enable HTTP/2 | `bool` | `true` |
| http3 | Enable HTTP/3 | `bool` | `true` |
| zero_rtt | Enable 0-RTT | `bool` | `true` |
| minify_css | Minify CSS | `bool` | `true` |
| minify_js | Minify JavaScript | `bool` | `true` |
| minify_html | Minify HTML | `bool` | `true` |

#### Caching Settings

| Name | Description | Type | Default |
|------|-------------|------|---------|
| browser_cache_ttl | Browser cache TTL in seconds | `number` | `14400` |

#### Network Settings

| Name | Description | Type | Default |
|------|-------------|------|---------|
| ipv6 | Enable IPv6 | `bool` | `true` |
| websockets | Enable WebSockets | `bool` | `true` |

#### Other Settings

| Name | Description | Type | Default |
|------|-------------|------|---------|
| dnssec_enabled | Enable DNSSEC | `bool` | `false` |
| development_mode | Enable development mode (disables caching) | `bool` | `false` |

## Outputs

| Name | Description | Sensitive |
|------|-------------|-----------|
| zone_id | Zone ID | No |
| zone_name | Zone name | No |
| name_servers | Cloudflare nameservers | No |
| status | Zone status | No |
| verification_key | Zone verification key | Yes |

## Examples

### Production Configuration

```hcl
module "production_zone" {
  source = "../../modules/cloudflare/zone"

  account_id = var.cloudflare_account_id
  zone_name  = "example.com"
  plan       = "pro"

  # Maximum security
  ssl_mode           = "strict"
  min_tls_version    = "1.3"
  security_level     = "high"
  dnssec_enabled     = true

  # Maximum performance
  http3              = true
  zero_rtt           = true
  brotli             = true
  early_hints        = true
}
```

### Development Configuration

```hcl
module "dev_zone" {
  source = "../../modules/cloudflare/zone"

  account_id = var.cloudflare_account_id
  zone_name  = "dev.example.com"
  plan       = "free"

  # Disable caching for development
  development_mode = true
  browser_cache_ttl = 0

  # Basic security
  security_level = "low"
}
```

## Best Practices

1. **SSL/TLS**: Always use `ssl_mode = "strict"` in production
2. **TLS Version**: Set `min_tls_version = "1.2"` or higher
3. **DNSSEC**: Enable DNSSEC for production domains
4. **Development Mode**: Only enable for testing (disables caching)
5. **Security Level**: Use `"medium"` or higher for production

## Notes

- Zone creation may take a few minutes to propagate
- Update your domain's nameservers to the Cloudflare nameservers after creation
- DNSSEC requires proper DS records to be added to your domain registrar
- Development mode automatically disables after 3 hours

## Related Modules

- [cloudflare/dns](../dns) - Manage DNS records for this zone

## Version History

- **2025-12-28**: Updated to Cloudflare provider ~> 5.0
- **2025-12-22**: Initial module creation

## Maintainer

PAUSATF Infrastructure Team
