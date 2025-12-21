# Test configuration for droplet module
terraform {
  required_version = ">= 1.6.0"

  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.34.0"
    }
  }
}

provider "digitalocean" {
  token = var.do_token
}

# Test fixture - create a droplet for testing
module "test_droplet" {
  source = "../"

  name        = var.test_droplet_name
  region      = var.test_region
  size        = var.test_size
  image       = var.test_image
  environment = var.test_environment

  ssh_key_ids = var.test_ssh_key_ids
  vpc_uuid    = var.test_vpc_uuid

  monitoring_enabled = true
  backups_enabled    = false
  ipv6_enabled       = true

  tags = [
    "test",
    "automated",
    "ci"
  ]

  firewall_inbound_rules = [
    {
      protocol         = "tcp"
      port_range       = "22"
      source_addresses = ["0.0.0.0/0"]
    }
  ]

  firewall_outbound_rules = [
    {
      protocol              = "tcp"
      port_range            = "1-65535"
      destination_addresses = ["0.0.0.0/0"]
    }
  ]
}

# Outputs for testing validation
output "droplet_id" {
  description = "ID of test droplet"
  value       = module.test_droplet.id
}

output "droplet_ipv4" {
  description = "IPv4 address of test droplet"
  value       = module.test_droplet.ipv4_address
}

output "droplet_name" {
  description = "Name of test droplet"
  value       = module.test_droplet.name
}

output "droplet_region" {
  description = "Region of test droplet"
  value       = module.test_droplet.region
}

output "droplet_size" {
  description = "Size of test droplet"
  value       = module.test_droplet.size
}
