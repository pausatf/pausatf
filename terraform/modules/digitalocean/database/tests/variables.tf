variable "test_name" {
  description = "Name for test database cluster"
  type        = string
  default     = "test-db-ci"
}

variable "test_engine" {
  description = "Database engine for testing"
  type        = string
  default     = "mysql"
}

variable "test_engine_version" {
  description = "Database engine version for testing"
  type        = string
  default     = "8"
}

variable "test_size" {
  description = "Database cluster size for testing"
  type        = string
  default     = "db-s-1vcpu-1gb"
}

variable "test_region" {
  description = "DigitalOcean region for testing"
  type        = string
  default     = "sfo2"
}

variable "test_node_count" {
  description = "Node count for testing"
  type        = number
  default     = 1
}

variable "test_environment" {
  description = "Environment name for testing"
  type        = string
  default     = "development"
}
