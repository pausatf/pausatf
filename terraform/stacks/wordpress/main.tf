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

# VPC
resource "digitalocean_vpc" "this" {
  name        = "pausatf-${var.environment}-vpc"
  region      = var.region
  ip_range    = var.vpc_cidr
  description = "${title(var.environment)} VPC for PAUSATF infrastructure"
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
  name   = "pausatf-${var.environment}"
  region = var.region
  size   = var.droplet_size
  image  = var.droplet_image

  vpc_uuid = digitalocean_vpc.this.id

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

  vpc_uuid = digitalocean_vpc.this.id

  trusted_sources = [
    {
      type  = "droplet"
      value = tostring(digitalocean_droplet.this.id)
    }
  ]

  databases      = ["wordpress"]
  database_users = ["wordpress"]

  tags = ["pausatf", var.environment]
}

# Firewall — HTTP/S from Cloudflare only, no public SSH (Tailscale handles it)
# Cloudflare's published IP ranges are public by definition; ingress restricted to CF only.
#tfsec:ignore:digitalocean-compute-no-public-ingress
#tfsec:ignore:digitalocean-compute-no-public-egress
resource "digitalocean_firewall" "this" {
  name = "pausatf-${var.environment}-firewall"

  droplet_ids = [digitalocean_droplet.this.id]

  # HTTP from Cloudflare IPv4
  inbound_rule {
    protocol         = "tcp"
    port_range       = "80"
    source_addresses = data.cloudflare_ip_ranges.current.ipv4_cidrs
  }

  # HTTP from Cloudflare IPv6
  inbound_rule {
    protocol         = "tcp"
    port_range       = "80"
    source_addresses = data.cloudflare_ip_ranges.current.ipv6_cidrs
  }

  # HTTPS from Cloudflare IPv4
  inbound_rule {
    protocol         = "tcp"
    port_range       = "443"
    source_addresses = data.cloudflare_ip_ranges.current.ipv4_cidrs
  }

  # HTTPS from Cloudflare IPv6
  inbound_rule {
    protocol         = "tcp"
    port_range       = "443"
    source_addresses = data.cloudflare_ip_ranges.current.ipv6_cidrs
  }

  # No port 22 — SSH is via Tailscale overlay network

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

# Monitoring Alerts
resource "digitalocean_monitor_alert" "cpu_high" {
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
