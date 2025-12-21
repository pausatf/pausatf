terraform {
  required_version = ">= 1.6.0"

  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.0"
    }
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 4.0"
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
resource "digitalocean_droplet" "staging" {
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

  user_data = templatefile("${path.module}/../../modules/droplet/cloud-init.yml", {
    environment = "staging"
    hostname    = "pausatf-stage"
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
resource "digitalocean_vpc" "staging" {
  name     = "pausatf-staging-vpc"
  region   = var.region
  ip_range = "10.20.0.0/16"

  description = "Staging VPC for PAUSATF infrastructure"
}

# Firewall for Staging
resource "digitalocean_firewall" "staging" {
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

  # SSH (more permissive for staging)
  inbound_rule {
    protocol         = "tcp"
    port_range       = "22"
    source_addresses = ["0.0.0.0/0", "::/0"]
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
