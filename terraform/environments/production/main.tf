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
      name = "pausatf-production"
    }
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

  environment                 = "production"
  region                      = var.region
  droplet_size                = var.droplet_size
  droplet_image               = var.droplet_image
  database_size               = var.database_size
  ssh_key_fingerprints        = [digitalocean_ssh_key.m3_laptop.id]
  alert_emails                = var.alert_email_addresses
  enable_backups              = false
  enable_monitoring           = true
  enable_monitoring_alerts    = length(var.alert_email_addresses) > 0
  ssh_allowed_ips             = var.ssh_allowed_ips
  droplet_name                = "pausatf-prod-v2"
  database_cluster_name       = "pausatf-prod-db"
  firewall_name               = "pausatf-prod-v2-fw"
  environment_tag             = "production"
  additional_tags             = ["migration-v2"]
  icmp_source_addresses       = ["0.0.0.0/0"]
  outbound_tcp_udp_port_range = "1-65535"
  create_database             = true

  create_vpc        = false
  vpc_uuid_override = "4ee39499-dc85-11e8-9f23-3cfdfea9fff1"

  cloud_init_content = templatefile("${path.module}/../../modules/droplet/cloud-init-ubuntu-24.yml", {
    environment = "production"
    hostname    = "pausatf-prod-v2"
  })

  create_reserved_ip = false
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
