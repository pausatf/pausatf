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
      name = "pausatf-staging"
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

  environment              = "staging"
  region                   = var.region
  droplet_size             = var.droplet_size
  droplet_image            = var.droplet_image
  database_size            = var.database_size
  ssh_key_fingerprints     = var.ssh_key_fingerprints
  enable_backups           = false
  enable_monitoring        = false
  create_database          = false
  create_reserved_ip       = false
  create_vpc               = false # staging uses default networking; no VPC isolation
  vpc_uuid_override        = "4ee39499-dc85-11e8-9f23-3cfdfea9fff1"
  enable_monitoring_alerts = false
  droplet_name             = "pausatf-stage-v2"
  firewall_name            = "pausatf-stage-v2-tunnel"
  environment_tag          = "staging"
  additional_tags          = ["migration-v2"]
  icmp_source_addresses    = ["0.0.0.0/0", "::/0"]

  # Staging serves traffic through its Cloudflare Tunnel, so direct web ingress is disabled.
  enable_web_ingress         = false
  firewall_http_source_cidrs = null
  ssh_allowed_ips            = var.ssh_allowed_ips

  cloud_init_content = templatefile("${path.module}/../../modules/droplet/cloud-init-openlitespeed.yml", {
    environment = "staging"
    hostname    = "pausatf-stage-v2"
  })
}

# The staging environment is the sole Terraform owner of stage.pausatf.org.
# The live record is a proxied Cloudflare Tunnel CNAME and is imported into
# the staging HCP Terraform workspace.
module "cloudflare_dns_staging" {
  source  = "../../modules/cloudflare/dns"
  zone_id = var.cloudflare_zone_id

  dns_records = [
    {
      name    = "stage"
      type    = "CNAME"
      value   = "cbbd07dc-3e97-4923-b582-1fafab43c01b.cfargotunnel.com"
      ttl     = 1
      proxied = true
      comment = "cloudflared tunnel (stage-v2)"
    }
  ]
}

moved {
  from = module.cloudflare_dns_staging.cloudflare_dns_record.this["A-stage"]
  to   = module.cloudflare_dns_staging.cloudflare_dns_record.this["CNAME-stage"]
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
