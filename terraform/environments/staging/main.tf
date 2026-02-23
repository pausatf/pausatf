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
    key = "staging/terraform.tfstate"
  }
}

provider "digitalocean" {
  token = var.do_token
}

provider "cloudflare" {
  api_token = var.cloudflare_api_token
}

# WordPress stack — shared module for environment parity with production
module "wordpress" {
  source = "../../stacks/wordpress"

  environment              = "staging"
  region                   = var.region
  droplet_size             = var.droplet_size
  droplet_image            = var.droplet_image
  database_size            = var.database_size
  ssh_key_fingerprints     = var.ssh_key_fingerprints
  enable_backups           = false
  enable_monitoring        = true
  create_reserved_ip       = false
  create_vpc               = false # staging uses default VPC
  enable_monitoring_alerts = false

  # Staging uses open firewall (not CF-only)
  firewall_http_source_cidrs = ["0.0.0.0/0", "::/0"]
  ssh_allowed_ips            = var.ssh_allowed_ips

  # OLS WebAdmin port
  extra_firewall_rules = [
    {
      protocol         = "tcp"
      port_range       = "7080"
      source_addresses = var.ssh_allowed_ips
    }
  ]

  cloud_init_content = templatefile("${path.module}/../../modules/droplet/cloud-init-openlitespeed.yml", {
    environment = "staging"
    hostname    = "stage"
  })
}

# Cloudflare DNS for staging
module "cloudflare_dns_staging" {
  source  = "../../modules/cloudflare/dns"
  zone_id = var.cloudflare_zone_id

  dns_records = [
    {
      name    = "stage"
      type    = "A"
      value   = module.wordpress.droplet_ip
      ttl     = 1
      proxied = true
      comment = "Staging web droplet"
    }
  ]
}

# State migration — zero-recreation move from inline resources to stack module
moved {
  from = digitalocean_droplet.staging
  to   = module.wordpress.digitalocean_droplet.this
}

moved {
  from = digitalocean_firewall.staging
  to   = module.wordpress.digitalocean_firewall.this
}

moved {
  from = digitalocean_database_cluster.staging
  to   = module.wordpress.module.database.digitalocean_database_cluster.this
}

moved {
  from = digitalocean_database_firewall.staging
  to   = module.wordpress.module.database.digitalocean_database_firewall.this[0]
}
