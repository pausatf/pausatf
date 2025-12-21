variable "do_token" {
  description = "DigitalOcean API token"
  type        = string
  sensitive   = true
}

variable "cloudflare_api_token" {
  description = "Cloudflare API token"
  type        = string
  sensitive   = true
}

variable "cloudflare_zone_id" {
  description = "Cloudflare Zone ID for pausatf.org"
  type        = string
  sensitive   = true
}

variable "region" {
  description = "DigitalOcean region"
  type        = string
  default     = "sfo2"
}

variable "droplet_size" {
  description = "Droplet size for staging (smaller than production)"
  type        = string
  default     = "s-2vcpu-4gb"
}

variable "droplet_image" {
  description = "Droplet image/snapshot"
  type        = string
  default     = "ubuntu-20-04-x64"
}

variable "database_size" {
  description = "Database cluster size (smaller than production)"
  type        = string
  default     = "db-s-1vcpu-1gb"
}

variable "ssh_key_fingerprints" {
  description = "List of SSH key fingerprints"
  type        = list(string)
  default     = []
}

variable "environment" {
  description = "Environment name"
  type        = string
  default     = "staging"
}
