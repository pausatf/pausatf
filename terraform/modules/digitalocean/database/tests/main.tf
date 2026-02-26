terraform {
  required_version = ">= 1.10.0"

  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.76"
    }
  }
}

provider "digitalocean" {
  # Token sourced from DIGITALOCEAN_ACCESS_TOKEN env var during tests
}

module "database" {
  source = "../"

  name           = var.test_name
  engine         = var.test_engine
  engine_version = var.test_engine_version
  size           = var.test_size
  region         = var.test_region
  node_count     = var.test_node_count
  environment    = var.test_environment
}

output "cluster_name" {
  description = "The name of the database cluster"
  value       = module.database.name
}

output "cluster_urn" {
  description = "The uniform resource name of the database cluster"
  value       = module.database.urn
}
