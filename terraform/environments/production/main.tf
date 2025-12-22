terraform {
  required_version = ">= 1.6.0"

  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.0"
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

# Production Droplet
resource "digitalocean_droplet" "production" {
  name   = "pausatf-prod"
  region = var.region
  size   = var.droplet_size
  image  = var.droplet_image

  tags = [
    "pausatf",
    "production",
    "web",
    "wordpress"
  ]

  monitoring = true
  ipv6       = false
  backups    = true

  ssh_keys = var.ssh_key_fingerprints

  user_data = templatefile("${path.module}/../../modules/droplet/cloud-init.yml", {
    environment = "production"
    hostname    = "pausatf-prod"
  })
}

# Production Database
resource "digitalocean_database_cluster" "production" {
  name       = "pausatf-production-db"
  engine     = "mysql"
  version    = "8"
  size       = var.database_size
  region     = var.region
  node_count = 1

  tags = [
    "pausatf",
    "production",
    "database"
  ]

  maintenance_window {
    day  = "sunday"
    hour = "04:00:00"
  }
}

resource "digitalocean_database_firewall" "production" {
  cluster_id = digitalocean_database_cluster.production.id

  rule {
    type  = "droplet"
    value = digitalocean_droplet.production.id
  }
}

# VPC for Production
resource "digitalocean_vpc" "production" {
  name     = "pausatf-production-vpc"
  region   = var.region
  ip_range = "10.10.0.0/16"

  description = "Production VPC for PAUSATF infrastructure"
}

# Firewall for Production
resource "digitalocean_firewall" "production" {
  name = "pausatf-production-firewall"

  droplet_ids = [digitalocean_droplet.production.id]

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
