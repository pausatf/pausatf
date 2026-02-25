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
    endpoint                    = "sfo2.digitaloceanspaces.com"
    region                      = "us-west-1"
    bucket                      = "pausatf-terraform-state"
    key                         = "dev/terraform.tfstate"
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

# Dev Droplet (smaller)
#tfsec:ignore:digitalocean-compute-use-ssh-keys
resource "digitalocean_droplet" "dev" {
  #checkov:skip=CKV_DIO_2:dev environment uses cloud-init; no static SSH key required
  name   = "pausatf-dev"
  region = var.region
  size   = var.droplet_size
  image  = var.droplet_image

  tags = [
    "pausatf",
    "dev",
    "web",
    "wordpress"
  ]

  monitoring = true
  ipv6       = false
  backups    = false

  ssh_keys = var.ssh_key_fingerprints

  user_data = templatefile("${path.module}/../../modules/droplet/cloud-init-apache.yml", {
    environment = "dev"
    hostname    = "pausatf-dev"
  })
}

# Dev Database (single node)
resource "digitalocean_database_cluster" "dev" {
  name       = "pausatf-dev-db"
  engine     = "mysql"
  version    = "8"
  size       = var.database_size
  region     = var.region
  node_count = 1

  tags = ["pausatf", "dev", "database"]
}

resource "digitalocean_database_firewall" "dev" {
  cluster_id = digitalocean_database_cluster.dev.id

  rule {
    type  = "droplet"
    value = digitalocean_droplet.dev.id
  }
}

# Dev VPC
resource "digitalocean_vpc" "dev" {
  name     = "pausatf-dev-vpc"
  region   = var.region
  ip_range = "10.30.0.0/16"

  description = "Dev VPC for PAUSATF infrastructure"
}

# Dev Firewall
#tfsec:ignore:digitalocean-compute-no-public-ingress
#tfsec:ignore:digitalocean-compute-no-public-egress
resource "digitalocean_firewall" "dev" {
  #checkov:skip=CKV_DIO_4:dev firewall intentionally permissive for local development
  name = "pausatf-dev-firewall"

  droplet_ids = [digitalocean_droplet.dev.id]

  inbound_rule {
    protocol         = "tcp"
    port_range       = "80"
    source_addresses = ["*******/0", "::/0"]
  }

  inbound_rule {
    protocol         = "tcp"
    port_range       = "443"
    source_addresses = ["*******/0", "::/0"]
  }

  inbound_rule {
    protocol         = "tcp"
    port_range       = "22"
    source_addresses = ["*******/0", "::/0"]
  }

  outbound_rule {
    protocol              = "tcp"
    port_range            = "1-65535"
    destination_addresses = ["*******/0", "::/0"]
  }

  outbound_rule {
    protocol              = "udp"
    port_range            = "1-65535"
    destination_addresses = ["*******/0", "::/0"]
  }

  tags = ["dev"]
}

# Cloudflare DNS for dev
module "cloudflare_dns_dev" {
  source  = "../../modules/cloudflare/dns"
  zone_id = var.cloudflare_zone_id

  dns_records = [
    {
      name    = "dev"
      type    = "A"
      value   = digitalocean_droplet.dev.ipv4_address
      ttl     = 1
      proxied = true
      comment = "Dev web droplet"
    }
  ]
}
