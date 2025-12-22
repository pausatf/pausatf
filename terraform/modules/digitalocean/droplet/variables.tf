variable "name" {
  description = "Name of the droplet"
  type        = string

  validation {
    condition     = can(regex("^[a-z0-9-]+$", var.name))
    error_message = "Droplet name must contain only lowercase letters, numbers, and hyphens."
  }
}

variable "region" {
  description = "DigitalOcean region"
  type        = string
  default     = "sfo2"

  validation {
    condition     = contains(["nyc1", "nyc3", "sfo1", "sfo2", "sfo3", "ams3", "sgp1", "lon1", "fra1", "tor1", "blr1"], var.region)
    error_message = "Region must be a valid DigitalOcean region."
  }
}

variable "size" {
  description = "Size/type of the droplet"
  type        = string
  default     = "s-1vcpu-1gb"

  validation {
    condition     = can(regex("^(s|c|g|m|so)-", var.size))
    error_message = "Size must be a valid DigitalOcean droplet size."
  }
}

variable "image" {
  description = "Operating system image or snapshot ID"
  type        = string
}

variable "ssh_key_ids" {
  description = "List of SSH key IDs to add to the droplet"
  type        = list(string)
  default     = []
}

variable "vpc_uuid" {
  description = "UUID of the VPC to attach the droplet to"
  type        = string
  default     = null
}

variable "monitoring_enabled" {
  description = "Enable DigitalOcean monitoring"
  type        = bool
  default     = true
}

variable "backups_enabled" {
  description = "Enable automated backups"
  type        = bool
  default     = false
}

variable "ipv6_enabled" {
  description = "Enable IPv6"
  type        = bool
  default     = true
}

variable "user_data" {
  description = "Cloud-init user data"
  type        = string
  default     = null
}

variable "tags" {
  description = "List of tags to apply to the droplet"
  type        = list(string)
  default     = []
}

variable "environment" {
  description = "Environment name (production, staging, development)"
  type        = string

  validation {
    condition     = contains(["production", "staging", "development"], var.environment)
    error_message = "Environment must be production, staging, or development."
  }
}

variable "firewall_id" {
  description = "ID of existing firewall to attach (optional)"
  type        = string
  default     = null
}

variable "firewall_inbound_rules" {
  description = "List of inbound firewall rules"
  type = list(object({
    protocol         = string
    port_range       = optional(string)
    source_addresses = optional(list(string))
  }))
  default = []
}

variable "firewall_outbound_rules" {
  description = "List of outbound firewall rules"
  type = list(object({
    protocol              = string
    port_range            = optional(string)
    destination_addresses = optional(list(string))
  }))
  default = []
}
