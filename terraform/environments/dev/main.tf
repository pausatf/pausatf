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
    key = "dev/terraform.tfstate"
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
  vpc_cidr                 = "10.30.0.0/16"
  enable_backups           = false
  enable_monitoring        = true
  create_reserved_ip       = false
  enable_monitoring_alerts = false

  # Dev uses open firewall (not CF-only)
  firewall_http_source_cidrs = ["0.0.0.0/0", "::/0"]
  ssh_allowed_ips            = var.ssh_allowed_ips

  cloud_init_content = templatefile("${path.module}/../../modules/droplet/cloud-init-ubuntu-24.yml", {
    environment = "dev"
    hostname    = "pausatf-dev"
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
      comment = "Dev web droplet"
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

moved {
  from = digitalocean_vpc.dev
  to   = module.wordpress.digitalocean_vpc.this[0]
}

moved {
  from = digitalocean_database_cluster.dev
  to   = module.wordpress.module.database.digitalocean_database_cluster.this
}

moved {
  from = digitalocean_database_firewall.dev
  to   = module.wordpress.module.database.digitalocean_database_firewall.this[0]
}
