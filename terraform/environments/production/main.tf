terraform {
  required_version = ">= 1.10.0"

  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.47"
    }
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 5.15"
    }
  }

  backend "s3" {
    # DigitalOcean Spaces backend
    endpoint                    = "sfo2.digitaloceanspaces.com"
    region                      = "us-west-1" # Dummy region for DO Spaces
    bucket                      = "pausatf-terraform-state"
    key                         = "production/terraform.tfstate"
    skip_credentials_validation = true
    skip_metadata_api_check     = true
  }
}

provider "digitalocean" {
  token = var.do_token
}

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

# DigitalOcean Project
resource "digitalocean_project" "pausatf" {
  name        = "PAUSATF"
  description = "PAUSATF - Pan African Ultimate Sports & Training Foundation"
  purpose     = "Website or blog"
  environment = "Production"

  resources = [
    digitalocean_droplet.production.urn,
    digitalocean_reserved_ip.production.urn,
    module.database.urn,
  ]
}

# SSH Key
resource "digitalocean_ssh_key" "m3_laptop" {
  name       = "m3 laptop"
  public_key = var.ssh_public_key
}

# VPC for Production
resource "digitalocean_vpc" "production" {
  name     = "pausatf-production-vpc"
  region   = var.region
  ip_range = "10.10.0.0/16"

  description = "Production VPC for PAUSATF infrastructure"
}

# Reserved IP — prevents address change on droplet rebuild
resource "digitalocean_reserved_ip" "production" {
  region = var.region
}

resource "digitalocean_reserved_ip_assignment" "production" {
  ip_address = digitalocean_reserved_ip.production.ip_address
  droplet_id = digitalocean_droplet.production.id
}

# Production Droplet
resource "digitalocean_droplet" "production" {
  name   = "pausatf-prod"
  region = var.region
  size   = var.droplet_size
  image  = var.droplet_image

  vpc_uuid = digitalocean_vpc.production.id

  tags = [
    "pausatf",
    "production",
    "web",
    "wordpress"
  ]

  monitoring = true
  ipv6       = false
  backups    = true

  ssh_keys = [digitalocean_ssh_key.m3_laptop.id]

  user_data = templatefile("${path.module}/../../modules/droplet/cloud-init-apache.yml", {
    environment = "production"
    hostname    = "ftp"
  })
}

# Monitoring Alerts
resource "digitalocean_monitor_alert" "cpu_high" {
  alerts {
    email = var.alert_email_addresses
  }
  window      = "5m"
  type        = "v1/insights/droplet/cpu"
  compare     = "GreaterThan"
  value       = 80
  enabled     = true
  entities    = [digitalocean_droplet.production.id]
  description = "Production CPU > 80% for 5 minutes"
}

resource "digitalocean_monitor_alert" "memory_high" {
  alerts {
    email = var.alert_email_addresses
  }
  window      = "5m"
  type        = "v1/insights/droplet/memory_utilization_percent"
  compare     = "GreaterThan"
  value       = 85
  enabled     = true
  entities    = [digitalocean_droplet.production.id]
  description = "Production memory > 85% for 5 minutes"
}

resource "digitalocean_monitor_alert" "disk_high" {
  alerts {
    email = var.alert_email_addresses
  }
  window      = "5m"
  type        = "v1/insights/droplet/disk_utilization_percent"
  compare     = "GreaterThan"
  value       = 75
  enabled     = true
  entities    = [digitalocean_droplet.production.id]
  description = "Production disk > 75% utilization"
}

# Production Firewall
resource "digitalocean_firewall" "production" {
  name = "pausatf-production-firewall"

  droplet_ids = [digitalocean_droplet.production.id]

  # HTTP
  inbound_rule {
    protocol   = "tcp"
    port_range = "80"
    source_addresses = [
      # Cloudflare IPv4 ranges — https://www.cloudflare.com/ips-v4
      "173.245.48.0/20", "103.21.244.0/22", "103.22.200.0/22", "103.31.4.0/22",
      "141.101.64.0/18", "108.162.192.0/18", "190.93.240.0/20", "188.114.96.0/20",
      "197.234.240.0/22", "198.41.128.0/17", "162.158.0.0/15", "104.16.0.0/13",
      "104.24.0.0/14", "172.64.0.0/13", "131.0.72.0/22",
      # Cloudflare IPv6 ranges — https://www.cloudflare.com/ips-v6
      "2400:cb00::/32", "2606:4700::/32", "2803:f800::/32", "2405:b500::/32",
      "2405:8100::/32", "2a06:98c0::/29", "2c0f:f248::/32"
    ]
  }

  # HTTPS
  inbound_rule {
    protocol   = "tcp"
    port_range = "443"
    source_addresses = [
      # Cloudflare IPv4 ranges — https://www.cloudflare.com/ips-v4
      "173.245.48.0/20", "103.21.244.0/22", "103.22.200.0/22", "103.31.4.0/22",
      "141.101.64.0/18", "108.162.192.0/18", "190.93.240.0/20", "188.114.96.0/20",
      "197.234.240.0/22", "198.41.128.0/17", "162.158.0.0/15", "104.16.0.0/13",
      "104.24.0.0/14", "172.64.0.0/13", "131.0.72.0/22",
      # Cloudflare IPv6 ranges — https://www.cloudflare.com/ips-v6
      "2400:cb00::/32", "2606:4700::/32", "2803:f800::/32", "2405:b500::/32",
      "2405:8100::/32", "2a06:98c0::/29", "2c0f:f248::/32"
    ]
  }

  # SSH (restricted)
  inbound_rule {
    protocol         = "tcp"
    port_range       = "22"
    source_addresses = var.ssh_allowed_ips
  }

  # Allow all outbound
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

  tags = ["production"]
}

# Managed Database — MySQL 8
module "database" {
  source = "../../modules/digitalocean/database"

  name           = "pausatf-production-db"
  engine         = "mysql"
  engine_version = "8"
  size           = var.database_size
  region         = var.region
  environment    = "production"

  vpc_uuid = digitalocean_vpc.production.id

  trusted_sources = [
    {
      type  = "droplet"
      value = tostring(digitalocean_droplet.production.id)
    }
  ]

  databases      = ["wordpress"]
  database_users = ["wordpress"]

  tags = ["pausatf", "production"]
}
