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

  validation {
    condition     = contains(["sfo2", "sfo3", "nyc1", "nyc3"], var.region)
    error_message = "Region must be one of: sfo2, sfo3, nyc1, nyc3."
  }
}

variable "droplet_size" {
  description = "Droplet size for staging (smaller than production)"
  type        = string
  default     = "s-2vcpu-4gb"

  validation {
    condition     = startswith(var.droplet_size, "s-")
    error_message = "Droplet size must start with 's-' (shared CPU)."
  }
}

variable "droplet_image" {
  description = "Droplet image/snapshot"
  type        = string
  default     = "ubuntu-24-04-x64"

  validation {
    condition     = startswith(var.droplet_image, "ubuntu-")
    error_message = "Droplet image must start with 'ubuntu-'."
  }
}

variable "ssh_allowed_ips" {
  description = "IPs allowed to SSH (CIDR notation)"
  type        = list(string)
}

variable "database_size" {
  description = "Database cluster size (smaller than production)"
  type        = string
  default     = "db-s-1vcpu-1gb"

  validation {
    condition     = startswith(var.database_size, "db-s-")
    error_message = "Database size must start with 'db-s-'."
  }
}

variable "ssh_key_fingerprints" {
  description = "List of SSH key fingerprints"
  type        = list(string)
  default     = []
}
