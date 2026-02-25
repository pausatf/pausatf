variable "environment" {
  description = "Environment name (production, staging, development)"
  type        = string

  validation {
    condition     = contains(["production", "staging", "dev", "development"], var.environment)
    error_message = "Environment must be production, staging, dev, or development."
  }
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
  description = "Droplet size slug"
  type        = string
  default     = "s-1vcpu-1gb"

  validation {
    condition     = startswith(var.droplet_size, "s-")
    error_message = "Droplet size must start with 's-' (shared CPU)."
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

variable "cloud_init_content" {
  description = "Rendered cloud-init user data"
  type        = string
  default     = null
}

variable "ssh_key_fingerprints" {
  description = "List of SSH key IDs/fingerprints for droplet access"
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

variable "vpc_cidr" {
  description = "VPC IP range in CIDR notation"
  type        = string
  default     = "10.10.0.0/16"
}

variable "alert_emails" {
  description = "Email addresses for monitoring alerts"
  type        = list(string)
  default     = []
}

variable "enable_backups" {
  description = "Enable automated droplet backups"
  type        = bool
  default     = false
}

variable "enable_monitoring" {
  description = "Enable DigitalOcean monitoring agent"
  type        = bool
  default     = true
}

variable "create_reserved_ip" {
  description = "Create a reserved IP in the stack module. Set false if the environment manages its own."
  type        = bool
  default     = true
}

variable "create_vpc" {
  description = "Create a VPC. Set false to use default networking."
  type        = bool
  default     = true
}

variable "vpc_uuid_override" {
  description = "Use an existing VPC UUID instead of creating one. Only used when create_vpc is false."
  type        = string
  default     = null
}

variable "firewall_http_source_cidrs" {
  description = "Source CIDRs for HTTP/HTTPS inbound rules. Defaults to Cloudflare IPs."
  type        = list(string)
  default     = null
}

variable "enable_monitoring_alerts" {
  description = "Create DigitalOcean monitoring alerts."
  type        = bool
  default     = true
}

variable "ssh_allowed_ips" {
  description = "IPs allowed SSH access via firewall. Empty list means no SSH rule."
  type        = list(string)
  default     = []
}

variable "extra_firewall_rules" {
  description = "Additional inbound firewall rules."
  type = list(object({
    protocol         = string
    port_range       = string
    source_addresses = list(string)
  }))
  default = []
}

variable "databases" {
  description = "List of databases to create in the managed cluster."
  type        = list(string)
  default     = ["wordpress"]
}

variable "database_users" {
  description = "List of database users to create."
  type        = list(string)
  default     = ["wordpress"]
}
