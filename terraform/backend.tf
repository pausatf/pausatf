terraform {
  # Backend configuration for remote state storage
  # Using Terraform Cloud for state management with encryption and versioning
  backend "remote" {
    organization = "thomasvincent-infrastructure"

    workspaces {
      prefix = "infrastructure-"
    }
  }

  required_version = ">= 1.6.0"

  required_providers {
    digitalocean = {
      source  = "digitalocean/digitalocean"
      version = "~> 2.34.0"
    }
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 4.20.0"
    }
  }
}
