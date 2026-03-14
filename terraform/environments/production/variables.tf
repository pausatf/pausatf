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

variable "region" {
  description = "DigitalOcean region"
  type        = string
  default     = "sfo2"

  validation {
    condition     = contains(["sfo2", "sfo3", "nyc1", "nyc3"], var.region)
    error_message = "Region must be one of: sfo2, sfo3, nyc1, nyc3."
  }
}

variable "droplet_image" {
  description = "Droplet base image"
  type        = string
  default     = "ubuntu-24-04-x64"

  validation {
    condition     = startswith(var.droplet_image, "ubuntu-")
    error_message = "Droplet image must start with 'ubuntu-'."
  }
}

variable "ssh_public_key" {
  description = "SSH public key for droplet access"
  type        = string
}

variable "alert_email_addresses" {
  description = "Email addresses for monitoring alerts"
  type        = list(string)
  default     = []
}

variable "ssh_allowed_ips" {
  description = "IPs allowed SSH access (CIDR notation)"
  type        = list(string)
  default     = []
}

variable "database_size" {
  description = "Database cluster size slug"
  type        = string
  default     = "db-s-1vcpu-1gb"

  validation {
    condition     = startswith(var.database_size, "db-s-")
    error_message = "Database size must start with 'db-s-'."
  }
}
