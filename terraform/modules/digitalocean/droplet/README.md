# DigitalOcean Droplet Module

Terraform module for creating and managing DigitalOcean Droplets with built-in firewall support, monitoring, and lifecycle management.

## Features

- **Automated Provisioning**: Cloud-init support for initial configuration
- **Firewall Management**: Integrated firewall rules and security groups
- **Monitoring**: Optional DigitalOcean monitoring integration
- **IPv6 Support**: Native IPv6 addressing
- **VPC Integration**: Private networking within VPCs
- **Lifecycle Management**: Create-before-destroy pattern for zero-downtime updates
- **Input Validation**: Built-in validation for names, regions, sizes, and environments
- **Backup Support**: Optional automated backups
- **Tagging**: Environment and custom tags for organization

## Usage

### Basic Droplet

```hcl
module "web_server" {
  source = "../../modules/digitalocean/droplet"

  name        = "pausatf-prod"
  region      = "sfo3"
  size        = "s-4vcpu-8gb"
  image       = "ubuntu-22-04-x64"
  environment = "production"

  ssh_key_ids = [data.digitalocean_ssh_key.deploy.id]

  monitoring_enabled = true
  backups_enabled    = true
  ipv6_enabled       = true

  tags = ["web", "wordpress", "production"]
}
```

### Droplet with Firewall Rules

```hcl
module "production_droplet" {
  source = "../../modules/digitalocean/droplet"

  name        = "pausatf-prod"
  region      = "sfo3"
  size        = "s-4vcpu-8gb"
  image       = "ubuntu-22-04-x64"
  environment = "production"

  ssh_key_ids = [var.ssh_key_id]

  # Firewall rules
  firewall_inbound_rules = [
    {
      protocol   = "tcp"
      port_range = "22"
      source_addresses = ["YOUR_IP/32"]  # Restrict SSH
    },
    {
      protocol   = "tcp"
      port_range = "80"
      source_addresses = ["0.0.0.0/0", "::/0"]  # HTTP from anywhere
    },
    {
      protocol   = "tcp"
      port_range = "443"
      source_addresses = ["0.0.0.0/0", "::/0"]  # HTTPS from anywhere
    }
  ]

  firewall_outbound_rules = [
    {
      protocol              = "tcp"
      port_range            = "1-65535"
      destination_addresses = ["0.0.0.0/0", "::/0"]
    },
    {
      protocol              = "udp"
      port_range            = "1-65535"
      destination_addresses = ["0.0.0.0/0", "::/0"]
    }
  ]

  monitoring_enabled = true
  backups_enabled    = true
}
```

### Droplet with Cloud-Init

```hcl
module "app_server" {
  source = "../../modules/digitalocean/droplet"

  name        = "app-server"
  region      = "sfo3"
  size        = "s-2vcpu-4gb"
  image       = "ubuntu-22-04-x64"
  environment = "production"

  ssh_key_ids = [var.ssh_key_id]

  user_data = file("${path.module}/cloud-init.yml")

  tags = ["app", "production"]
}
```

## Requirements

| Name | Version |
|------|---------|
| terraform | >= 1.0 |
| digitalocean | ~> 2.69 |

## Inputs

### Required Inputs

| Name | Description | Type |
|------|-------------|------|
| name | Name of the droplet (lowercase letters, numbers, hyphens only) | `string` |
| image | Operating system image or snapshot ID | `string` |
| environment | Environment name (production, staging, development) | `string` |

### Optional Inputs

#### Droplet Configuration

| Name | Description | Type | Default |
|------|-------------|------|---------|
| region | DigitalOcean region | `string` | `"sfo2"` |
| size | Size/type of the droplet | `string` | `"s-1vcpu-1gb"` |
| ssh_key_ids | List of SSH key IDs to add to the droplet | `list(string)` | `[]` |
| user_data | Cloud-init user data | `string` | `null` |
| tags | List of tags to apply to the droplet | `list(string)` | `[]` |

#### Network Configuration

| Name | Description | Type | Default |
|------|-------------|------|---------|
| vpc_uuid | UUID of the VPC to attach the droplet to | `string` | `null` |
| ipv6_enabled | Enable IPv6 | `bool` | `true` |

#### Features

| Name | Description | Type | Default |
|------|-------------|------|---------|
| monitoring_enabled | Enable DigitalOcean monitoring | `bool` | `true` |
| backups_enabled | Enable automated backups | `bool` | `false` |

#### Firewall Configuration

| Name | Description | Type | Default |
|------|-------------|------|---------|
| firewall_id | ID of existing firewall to attach (optional) | `string` | `null` |
| firewall_inbound_rules | List of inbound firewall rules | `list(object)` | `[]` |
| firewall_outbound_rules | List of outbound firewall rules | `list(object)` | `[]` |

### Firewall Rule Object Structure

```hcl
# Inbound rule
{
  protocol         = string           # tcp, udp, icmp
  port_range       = string           # "22", "80", "443", "8000-9000"
  source_addresses = list(string)     # ["0.0.0.0/0", "::/0"] or specific IPs
}

# Outbound rule
{
  protocol              = string      # tcp, udp, icmp
  port_range            = string      # "22", "80", "443", "8000-9000"
  destination_addresses = list(string) # ["0.0.0.0/0", "::/0"] or specific IPs
}
```

## Outputs

| Name | Description |
|------|-------------|
| id | ID of the droplet |
| name | Name of the droplet |
| ipv4_address | Public IPv4 address of the droplet |
| ipv4_address_private | Private IPv4 address of the droplet |
| ipv6_address | Public IPv6 address of the droplet |
| urn | Uniform Resource Name (URN) of the droplet |
| region | Region of the droplet |
| size | Size of the droplet |
| tags | Tags applied to the droplet |

## Examples

### Production WordPress Server

```hcl
module "pausatf_prod" {
  source = "../../modules/digitalocean/droplet"

  name        = "pausatf-prod"
  region      = "sfo3"
  size        = "s-4vcpu-8gb"
  image       = "ubuntu-20-04-x64"
  environment = "production"

  ssh_key_ids = [data.digitalocean_ssh_key.deploy.id]
  vpc_uuid    = digitalocean_vpc.main.id

  # Production features
  monitoring_enabled = true
  backups_enabled    = true
  ipv6_enabled       = true

  # Firewall: SSH restricted, HTTP/HTTPS open
  firewall_inbound_rules = [
    {
      protocol         = "tcp"
      port_range       = "22"
      source_addresses = ["10.0.0.0/8"]  # VPN/internal only
    },
    {
      protocol         = "tcp"
      port_range       = "80"
    },
    {
      protocol         = "tcp"
      port_range       = "443"
    }
  ]

  firewall_outbound_rules = [
    {
      protocol = "tcp"
    },
    {
      protocol = "udp"
    },
    {
      protocol = "icmp"
    }
  ]

  tags = ["web", "wordpress", "production", "pausatf"]
}
```

### Staging Environment

```hcl
module "pausatf_stage" {
  source = "../../modules/digitalocean/droplet"

  name        = "pausatf-stage"
  region      = "sfo3"
  size        = "s-2vcpu-4gb"
  image       = "ubuntu-22-04-x64"
  environment = "staging"

  ssh_key_ids = [var.ssh_key_id]

  # Staging: monitoring yes, backups no
  monitoring_enabled = true
  backups_enabled    = false

  tags = ["web", "staging"]
}
```

### Accessing Outputs

```hcl
# Use droplet IP in DNS
resource "cloudflare_record" "prod" {
  zone_id = var.zone_id
  name    = "@"
  type    = "A"
  value   = module.pausatf_prod.ipv4_address
  proxied = true
}

# Output for reference
output "prod_server_ip" {
  value       = module.pausatf_prod.ipv4_address
  description = "Production server public IP"
}
```

## Valid Regions

| Region Code | Location |
|-------------|----------|
| nyc1, nyc3 | New York City |
| sfo1, sfo2, sfo3 | San Francisco |
| ams3 | Amsterdam |
| sgp1 | Singapore |
| lon1 | London |
| fra1 | Frankfurt |
| tor1 | Toronto |
| blr1 | Bangalore |

## Valid Droplet Sizes

### Basic (s-series)
- `s-1vcpu-1gb` - 1 vCPU, 1 GB RAM, 25 GB SSD
- `s-2vcpu-2gb` - 2 vCPUs, 2 GB RAM, 50 GB SSD
- `s-2vcpu-4gb` - 2 vCPUs, 4 GB RAM, 80 GB SSD
- `s-4vcpu-8gb` - 4 vCPUs, 8 GB RAM, 160 GB SSD
- `s-8vcpu-16gb` - 8 vCPUs, 16 GB RAM, 320 GB SSD

### CPU-Optimized (c-series)
- `c-2` - 2 dedicated vCPUs, 4 GB RAM
- `c-4` - 4 dedicated vCPUs, 8 GB RAM

### Memory-Optimized (m-series)
- `m-2vcpu-16gb` - 2 vCPUs, 16 GB RAM
- `m-4vcpu-32gb` - 4 vCPUs, 32 GB RAM

## Common Operating System Images

- `ubuntu-22-04-x64` - Ubuntu 22.04 LTS
- `ubuntu-20-04-x64` - Ubuntu 20.04 LTS
- `debian-11-x64` - Debian 11
- `centos-stream-9-x64` - CentOS Stream 9
- `rocky-9-x64` - Rocky Linux 9

## Best Practices

1. **SSH Keys**: Always configure SSH keys, never use password authentication
2. **Firewall Rules**: Restrict SSH access to specific IPs or VPN ranges
3. **Monitoring**: Enable monitoring for production droplets
4. **Backups**: Enable automated backups for production data
5. **Tags**: Use consistent tagging for organization and cost allocation
6. **VPC**: Use VPCs for private networking between droplets
7. **Naming**: Use descriptive names following pattern: `{service}-{env}`
8. **User Data**: Store cloud-init configs in version control

## Lifecycle Management

This module uses `create_before_destroy = true`, which means:
- New droplet is created before the old one is destroyed
- Ensures zero-downtime for updates
- Important: Update DNS/load balancers to point to new IP after creation
- User data changes are ignored after initial creation (prevents rebuild)

## Firewall vs Security Group

This module creates a **firewall** (not security group). Key differences:
- Firewalls apply to droplets by ID
- Can be shared across multiple droplets
- Managed separately from droplets
- Rules are stateful

## Cost Estimation

| Size | Monthly Cost (approx) |
|------|-----------------------|
| s-1vcpu-1gb | $6/month |
| s-2vcpu-2gb | $12/month |
| s-2vcpu-4gb | $18/month |
| s-4vcpu-8gb | $48/month |
| s-8vcpu-16gb | $96/month |

**Additional costs:**
- Backups: +20% of droplet cost
- Monitoring: Free
- IPv6: Free
- Bandwidth: 1TB included, $0.01/GB overage

## Troubleshooting

### Issue: Droplet creation timeout

**Solution**: Check DigitalOcean status page for regional issues. Try a different region.

### Issue: SSH connection refused

**Solution**:
1. Check firewall rules allow your IP
2. Verify SSH key was added correctly
3. Check droplet is fully booted (may take 1-2 minutes)

### Issue: User data not applied

**Solution**: User data runs once at first boot. To re-apply, create a new droplet or use Ansible/configuration management.

## Security Considerations

1. **SSH Access**: Always restrict port 22 to specific IPs or use VPN
2. **Root Login**: Disable root login and use sudo users
3. **Firewall**: Default deny all, explicitly allow needed ports
4. **Updates**: Enable automatic security updates in user_data
5. **Monitoring**: Enable monitoring to detect anomalies
6. **Backups**: Enable for production data protection

## Related Modules

- [digitalocean/database](../database) - Managed database clusters
- [droplet](../../droplet) - Cloud-init configuration

## Version History

- **2025-12-28**: Updated to DigitalOcean provider ~> 2.69
- **2025-12-22**: Initial module creation

## Current Production Usage

| Droplet | ID | Size | Region | Purpose |
|---------|-------|------|--------|---------|
| ftp.pausatf.org | 355909945 | s-4vcpu-8gb | sfo3 | Production WordPress |
| stage.pausatf.org | 538411208 | s-2vcpu-4gb | sfo3 | Staging environment |

## Maintainer

PAUSATF Infrastructure Team
