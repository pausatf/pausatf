variable "name" {
  description = "Name of the database cluster"
  type        = string
}

variable "engine" {
  description = "Database engine (mysql, pg, redis, mongodb, kafka, opensearch)"
  type        = string
  default     = "mysql"

  validation {
    condition     = contains(["mysql", "pg", "redis", "mongodb", "kafka", "opensearch"], var.engine)
    error_message = "Engine must be one of: mysql, pg, redis, mongodb, kafka, opensearch."
  }
}

variable "engine_version" {
  description = "Version of the database engine"
  type        = string
  default     = "8"
}

variable "size" {
  description = "Database cluster size/tier"
  type        = string
  default     = "db-s-1vcpu-1gb"
}

variable "region" {
  description = "DigitalOcean region"
  type        = string
  default     = "sfo2"
}

variable "node_count" {
  description = "Number of nodes in the cluster (1, 2, or 3)"
  type        = number
  default     = 1

  validation {
    condition     = contains([1, 2, 3], var.node_count)
    error_message = "Node count must be 1, 2, or 3."
  }
}

variable "vpc_uuid" {
  description = "UUID of the VPC for private networking"
  type        = string
  default     = null
}

variable "maintenance_window_day" {
  description = "Day of week for maintenance (monday-sunday)"
  type        = string
  default     = "sunday"
}

variable "maintenance_window_hour" {
  description = "Hour for maintenance window (00:00-23:00)"
  type        = string
  default     = "02:00"
}

variable "tags" {
  description = "List of tags"
  type        = list(string)
  default     = []
}

variable "environment" {
  description = "Environment name"
  type        = string
}

variable "trusted_sources" {
  description = "List of trusted sources for database firewall"
  type = list(object({
    type  = string  # ip_addr, droplet, k8s, tag
    value = string
  }))
  default = []
}

variable "databases" {
  description = "List of databases to create"
  type        = list(string)
  default     = []
}

variable "database_users" {
  description = "List of database users to create"
  type        = list(string)
  default     = []
}
