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
  description = "Droplet size for production"
  type        = string
  default     = "s-4vcpu-8gb"
}

variable "droplet_image" {
  description = "Droplet image/snapshot"
  type        = string
  default     = "ubuntu-20-04-x64"
}

variable "database_size" {
  description = "Database cluster size"
  type        = string
  default     = "db-s-2vcpu-4gb"
}

variable "ssh_public_key" {
  description = "SSH public key content"
  type        = string
  sensitive   = true
}

variable "ssh_key_fingerprints" {
  description = "List of SSH key fingerprints (deprecated - use ssh_key resource)"
  type        = list(string)
  default     = []
}

variable "ssh_allowed_ips" {
  description = "IPs allowed to SSH (CIDR notation)"
  type        = list(string)
  default     = ["0.0.0.0/0"] # CHANGE THIS in production!
}

variable "environment" {
  description = "Environment name"
  type        = string
  default     = "production"
}
