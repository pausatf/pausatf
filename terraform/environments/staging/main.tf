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
    # DigitalOcean Spaces backend
    endpoint                    = "sfo2.digitaloceanspaces.com"
    region                      = "us-west-1"
    bucket                      = "pausatf-terraform-state"
    key                         = "staging/terraform.tfstate"
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

# Staging Droplet
#tfsec:ignore:digitalocean-compute-use-ssh-keys
resource "digitalocean_droplet" "staging" {
  #checkov:skip=CKV_DIO_2:staging uses cloud-init SSH configuration; no static key required
  name   = "pausatf-stage"
  region = var.region
  size   = var.droplet_size
  image  = var.droplet_image

  tags = [
    "pausatf",
    "staging",
    "web",
    "wordpress"
  ]

  monitoring = true
  ipv6       = false
  backups    = false # No backups for staging to save costs

  ssh_keys = var.ssh_key_fingerprints

  user_data = templatefile("${path.module}/../../modules/droplet/cloud-init-openlitespeed.yml", {
    environment = "staging"
    hostname    = "stage"
  })
}

# Staging Database
resource "digitalocean_database_cluster" "staging" {
  name       = "pausatf-stage-db"
  engine     = "mysql"
  version    = "8"
  size       = var.database_size
  region     = var.region
  node_count = 1

  tags = [
    "pausatf",
    "staging",
    "database"
  ]

  maintenance_window {
    day  = "saturday"
    hour = "02:00:00"
  }
}

resource "digitalocean_database_firewall" "staging" {
  cluster_id = digitalocean_database_cluster.staging.id

  rule {
    type  = "droplet"
    value = digitalocean_droplet.staging.id
  }
}

# VPC for Staging
# Note: Currently using default VPC
# Uncomment below if custom VPC is needed
#
# resource "digitalocean_vpc" "staging" {
#   name     = "pausatf-staging-vpc"
#   region   = var.region
#   ip_range = "10.20.0.0/16"
#
#   description = "Staging VPC for PAUSATF infrastructure"
# }

# Firewall for Staging
#tfsec:ignore:digitalocean-compute-no-public-ingress
#tfsec:ignore:digitalocean-compute-no-public-egress
resource "digitalocean_firewall" "staging" {
  #checkov:skip=CKV_DIO_4:staging firewall mirrors dev; tightened before production promotion
  name = "pausatf-staging-firewall"

  droplet_ids = [digitalocean_droplet.staging.id]

  # HTTP
  inbound_rule {
    protocol         = "tcp"
    port_range       = "80"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  # HTTPS
  inbound_rule {
    protocol         = "tcp"
    port_range       = "443"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  # SSH (restricted)
  inbound_rule {
    protocol         = "tcp"
    port_range       = "22"
    source_addresses = var.ssh_allowed_ips
  }

  # OpenLiteSpeed WebAdmin (restricted to admin IPs)
  inbound_rule {
    protocol         = "tcp"
    port_range       = "7080"
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

  tags = ["staging"]
}

# Cloudflare DNS for staging
module "cloudflare_dns_staging" {
  source  = "../../modules/cloudflare/dns"
  zone_id = var.cloudflare_zone_id

  dns_records = [
    {
      name    = "stage"
      type    = "A"
      value   = digitalocean_droplet.staging.ipv4_address
      ttl     = 1
      proxied = true
      comment = "Staging web droplet"
    }
  ]
}
