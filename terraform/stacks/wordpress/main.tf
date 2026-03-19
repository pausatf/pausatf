# PAUSATF WordPress Stack Module
# Shared infrastructure: VPC + Droplet + Managed DB + Firewall + Reserved IP + Monitoring

terraform {
  required_version = ">= 1.10.0"

  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.76"
    }
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 5.17"
    }
  }
}

# Cloudflare IP ranges — used to restrict HTTP/S inbound to CF edge only
data "cloudflare_ip_ranges" "current" {}

# VPC — optional; staging uses default networking
resource "digitalocean_vpc" "this" {
  count       = var.create_vpc ? 1 : 0
  name        = "pausatf-${var.environment}-vpc"
  region      = var.region
  ip_range    = var.vpc_cidr
  description = "${title(var.environment)} VPC for PAUSATF infrastructure"
}

locals {
  vpc_id = var.create_vpc ? digitalocean_vpc.this[0].id : var.vpc_uuid_override
  http_source_cidrs = var.firewall_http_source_cidrs != null ? var.firewall_http_source_cidrs : concat(
    data.cloudflare_ip_ranges.current.ipv4_cidrs,
    data.cloudflare_ip_ranges.current.ipv6_cidrs
  )

  # SSH allowlist (port 22). Explicit IPs only; no broad ISP ranges.
  # Last reviewed: 2026-03-18.
  ssh_cidrs = [
    # DigitalOcean internal / VPC
    "100.128.0.0/9",
    # Tailscale CGNAT range
    "100.64.0.0/10",
    # Admin static IP
    "REDACTED_ADMIN_IP/32",
    # Jeff Teeters (UC Berkeley campus)
    "136.152.0.0/16",
  ]
}

# Reserved IP — survives droplet rebuilds
resource "digitalocean_reserved_ip" "this" {
  count  = var.create_reserved_ip ? 1 : 0
  region = var.region

  lifecycle {
    prevent_destroy = true
  }
}

resource "digitalocean_reserved_ip_assignment" "this" {
  count      = var.create_reserved_ip ? 1 : 0
  ip_address = digitalocean_reserved_ip.this[0].ip_address
  droplet_id = digitalocean_droplet.this.id
}

# Droplet
#tfsec:ignore:digitalocean-compute-use-ssh-keys
resource "digitalocean_droplet" "this" {
  #checkov:skip=CKV_DIO_2:ssh_keys wired via var.ssh_key_fingerprints
  name   = "pausatf-${var.environment}"
  region = var.region
  size   = var.droplet_size
  image  = var.droplet_image

  vpc_uuid = local.vpc_id

  tags = [
    "pausatf",
    var.environment,
    "web",
    "wordpress"
  ]

  monitoring = var.enable_monitoring
  ipv6       = false
  backups    = var.enable_backups

  ssh_keys = var.ssh_key_fingerprints

  user_data = var.cloud_init_content

  lifecycle {
    create_before_destroy = true
    ignore_changes = [
      user_data,
      image,
      ssh_keys,
      monitoring,
      name,
    ]
  }
}

# Managed Database — MySQL 8
module "database" {
  source = "../../modules/digitalocean/database"

  name           = "pausatf-${var.environment}-db"
  engine         = "mysql"
  engine_version = "8"
  size           = var.database_size
  region         = var.region
  environment    = var.environment

  vpc_uuid = local.vpc_id

  trusted_sources = [
    {
      type  = "droplet"
      value = tostring(digitalocean_droplet.this.id)
    }
  ]

  databases      = var.databases
  database_users = var.database_users

  tags = ["pausatf", var.environment]
}

# Firewall — HTTP/S source CIDRs are configurable (defaults to Cloudflare-only)
#tfsec:ignore:digitalocean-compute-no-public-ingress
#tfsec:ignore:digitalocean-compute-no-public-egress
resource "digitalocean_firewall" "this" {
  name = "pausatf-${var.environment}-firewall"

  droplet_ids = [digitalocean_droplet.this.id]

  inbound_rule {
    protocol         = "tcp"
    port_range       = "80"
    source_addresses = local.http_source_cidrs
  }

  inbound_rule {
    protocol         = "tcp"
    port_range       = "443"
    source_addresses = local.http_source_cidrs
  }

  inbound_rule {
    protocol   = "tcp"
    port_range = "22"
    # Explicit allowlist takes precedence; default is ssh_cidrs.
    # Set var.ssh_allowed_ips to ["0.0.0.0/0"] only for emergency break-glass.
    source_addresses = length(var.ssh_allowed_ips) > 0 ? var.ssh_allowed_ips : local.ssh_cidrs
  }

  dynamic "inbound_rule" {
    for_each = var.extra_firewall_rules
    content {
      protocol         = inbound_rule.value.protocol
      port_range       = inbound_rule.value.port_range
      source_addresses = inbound_rule.value.source_addresses
    }
  }

  # All outbound
  outbound_rule {
    protocol              = "tcp"
    port_range            = "1-65535"
    destination_addresses = ["0.0.0.0/0", "::/0"]
  }

  outbound_rule {
    protocol              = "udp"
    port_range            = "1-65535"
    destination_addresses = ["0.0.0.0/0", "::/0"]
  }

  tags = [var.environment]
}

# Monitoring Alerts — disabled for non-production by default
resource "digitalocean_monitor_alert" "cpu_high" {
  count = var.enable_monitoring_alerts ? 1 : 0
  alerts {
    email = var.alert_emails
  }
  window      = "5m"
  type        = "v1/insights/droplet/cpu"
  compare     = "GreaterThan"
  value       = 80
  enabled     = true
  entities    = [digitalocean_droplet.this.id]
  description = "${title(var.environment)} CPU > 80% for 5 minutes"
}

resource "digitalocean_monitor_alert" "memory_high" {
  count = var.enable_monitoring_alerts ? 1 : 0
  alerts {
    email = var.alert_emails
  }
  window      = "5m"
  type        = "v1/insights/droplet/memory_utilization_percent"
  compare     = "GreaterThan"
  value       = 85
  enabled     = true
  entities    = [digitalocean_droplet.this.id]
  description = "${title(var.environment)} memory > 85% for 5 minutes"
}

resource "digitalocean_monitor_alert" "disk_high" {
  count = var.enable_monitoring_alerts ? 1 : 0
  alerts {
    email = var.alert_emails
  }
  window      = "5m"
  type        = "v1/insights/droplet/disk_utilization_percent"
  compare     = "GreaterThan"
  value       = 75
  enabled     = true
  entities    = [digitalocean_droplet.this.id]
  description = "${title(var.environment)} disk > 75% utilization"
}

# State migration: resources that gained count require moved blocks
moved {
  from = digitalocean_vpc.this
  to   = digitalocean_vpc.this[0]
}

moved {
  from = digitalocean_monitor_alert.cpu_high
  to   = digitalocean_monitor_alert.cpu_high[0]
}

moved {
  from = digitalocean_monitor_alert.memory_high
  to   = digitalocean_monitor_alert.memory_high[0]
}

moved {
  from = digitalocean_monitor_alert.disk_high
  to   = digitalocean_monitor_alert.disk_high[0]
}
