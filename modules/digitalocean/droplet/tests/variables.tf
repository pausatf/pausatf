variable "do_token" {
  description = "DigitalOcean API token for testing"
  type        = string
  sensitive   = true
}

variable "test_droplet_name" {
  description = "Name for test droplet"
  type        = string
  default     = "test-droplet-ci"
}

variable "test_region" {
  description = "Region for test droplet"
  type        = string
  default     = "nyc3"
}

variable "test_size" {
  description = "Size for test droplet"
  type        = string
  default     = "s-1vcpu-1gb"
}

variable "test_image" {
  description = "Image for test droplet"
  type        = string
  default     = "ubuntu-24-04-x64"
}

variable "test_environment" {
  description = "Environment for test"
  type        = string
  default     = "development"
}

variable "test_ssh_key_ids" {
  description = "SSH key IDs for test droplet"
  type        = list(string)
  default     = []
}

variable "test_vpc_uuid" {
  description = "VPC UUID for test droplet"
  type        = string
  default     = null
}
