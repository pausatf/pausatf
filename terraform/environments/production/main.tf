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

  backend "s3" {
    key = "production/terraform.tfstate"
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
  description = "Pacific Association of USA Track & Field"
  purpose     = "Website or blog"
  environment = "Production"
  is_default  = true

  resources = [
    module.wordpress.droplet_urn,
    digitalocean_reserved_ip.production.urn,
    module.wordpress.database_urn,
  ]

  lifecycle {
    ignore_changes = [
      resources,
    ]
  }
}

# SSH Key
resource "digitalocean_ssh_key" "m3_laptop" {
  name       = "m3 laptop"
  public_key = var.ssh_public_key
}

# WordPress stack
module "wordpress" {
  source = "../../stacks/wordpress"

  environment              = "production"
  region                   = var.region
  droplet_size             = "s-4vcpu-8gb"
  droplet_image            = var.droplet_image
  database_size            = var.database_size
  ssh_key_fingerprints     = [digitalocean_ssh_key.m3_laptop.id]
  alert_emails             = var.alert_email_addresses
  enable_backups           = true
  enable_monitoring        = true
  enable_monitoring_alerts = length(var.alert_email_addresses) > 0
  ssh_allowed_ips          = var.ssh_allowed_ips

  create_vpc        = false
  vpc_uuid_override = var.vpc_uuid

  cloud_init_content = templatefile("${path.module}/../../modules/droplet/cloud-init-ubuntu-24.yml", {
    environment = "production"
    hostname    = "pausatf-prod"
  })

  create_reserved_ip = false
}

# Alias for reserved IP (project resource reference needs direct resource)
# The stack module creates the reserved IP; reference it for the project.
resource "digitalocean_reserved_ip" "production" {
  region = var.region

  lifecycle {
    prevent_destroy = true
  }
}

resource "digitalocean_reserved_ip_assignment" "production" {
  ip_address = digitalocean_reserved_ip.production.ip_address
  droplet_id = module.wordpress.droplet_id
}

# moved blocks — zero-recreation migration from inline resources to stack module
moved {
  from = digitalocean_vpc.production
  to   = module.wordpress.digitalocean_vpc.this
}

moved {
  from = digitalocean_droplet.production
  to   = module.wordpress.digitalocean_droplet.this
}

moved {
  from = module.database
  to   = module.wordpress.module.database
}

moved {
  from = digitalocean_firewall.production
  to   = module.wordpress.digitalocean_firewall.this
}

moved {
  from = digitalocean_monitor_alert.cpu_high
  to   = module.wordpress.digitalocean_monitor_alert.cpu_high
}

moved {
  from = digitalocean_monitor_alert.memory_high
  to   = module.wordpress.digitalocean_monitor_alert.memory_high
}

moved {
  from = digitalocean_monitor_alert.disk_high
  to   = module.wordpress.digitalocean_monitor_alert.disk_high
}

moved {
  from = module.wordpress.digitalocean_reserved_ip.this[0]
  to   = digitalocean_reserved_ip.production
}

moved {
  from = module.wordpress.digitalocean_reserved_ip_assignment.this[0]
  to   = digitalocean_reserved_ip_assignment.production
}
