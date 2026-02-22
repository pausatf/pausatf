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
}

variable "droplet_size" {
  description = "Droplet size slug"
  type        = string
  default     = "s-1vcpu-1gb"
}

variable "droplet_image" {
  description = "Droplet base image"
  type        = string
  default     = "ubuntu-22-04-x64"
}

variable "ssh_public_key" {
  description = "SSH public key for droplet access"
  type        = string
}

variable "ssh_allowed_ips" {
  description = "IP addresses allowed to SSH into the droplet"
  type        = list(string)
  default     = []
}

variable "alert_email_addresses" {
  description = "Email addresses for monitoring alerts"
  type        = list(string)
  default     = []
}
