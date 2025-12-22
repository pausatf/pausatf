variable "github_owner" {
  description = "GitHub organization or user name"
  type        = string
  default     = "pausatf"
}

variable "github_token" {
  description = "GitHub personal access token with repo and admin:repo_hook permissions"
  type        = string
  sensitive   = true
}
