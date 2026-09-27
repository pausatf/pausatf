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

  cloud {
    organization = "pausatf"

    workspaces {
      name = "pausatf-dev"
    }
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

  environment              = "dev"
  region                   = var.region
  droplet_size             = var.droplet_size
  droplet_image            = var.droplet_image
  database_size            = var.database_size
  ssh_key_fingerprints     = var.ssh_key_fingerprints
  enable_backups           = false
  enable_monitoring        = false
  create_database          = false
  create_reserved_ip       = false
  create_vpc               = false
  vpc_uuid_override        = "4ee39499-dc85-11e8-9f23-3cfdfea9fff1"
  enable_monitoring_alerts = false
  droplet_name             = "pausatf-dev-v2"
  firewall_name            = "pausatf-nonprod-cf-lock"
  environment_tag          = "development"
  additional_tags          = ["migration-v2"]
  icmp_source_addresses    = ["0.0.0.0/0", "::/0"]

  # HTTP/S access is limited to Cloudflare's published ranges.
  firewall_http_source_cidrs = null
  ssh_allowed_ips            = var.ssh_allowed_ips

  cloud_init_content = templatefile("${path.module}/../../modules/droplet/cloud-init-ubuntu-24.yml", {
    environment = "dev"
    hostname    = "pausatf-dev-v2"
  })
}

# Cloudflare DNS for dev
module "cloudflare_dns_dev" {
  source  = "../../modules/cloudflare/dns"
  zone_id = var.cloudflare_zone_id

  dns_records = [
    {
      name    = "dev"
      type    = "A"
      value   = module.wordpress.droplet_ip
      ttl     = 1
      proxied = true
      comment = "migrated to dev-v2 26.04 (was 157.245.176.229)"
    }
  ]
}

# State migration — zero-recreation move from inline resources to stack module
moved {
  from = digitalocean_droplet.dev
  to   = module.wordpress.digitalocean_droplet.this
}

moved {
  from = digitalocean_firewall.dev
  to   = module.wordpress.digitalocean_firewall.this
}
