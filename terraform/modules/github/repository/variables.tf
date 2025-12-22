variable "name" {
  description = "Name of the GitHub repository"
  type        = string
}

variable "description" {
  description = "Description of the repository"
  type        = string
  default     = ""
}

variable "visibility" {
  description = "Repository visibility (public or private)"
  type        = string
  default     = "public"
  validation {
    condition     = contains(["public", "private"], var.visibility)
    error_message = "Visibility must be either 'public' or 'private'."
  }
}

variable "has_issues" {
  description = "Enable GitHub Issues"
  type        = bool
  default     = true
}

variable "has_wiki" {
  description = "Enable GitHub Wiki"
  type        = bool
  default     = false
}

variable "has_projects" {
  description = "Enable GitHub Projects"
  type        = bool
  default     = false
}

variable "has_discussions" {
  description = "Enable GitHub Discussions"
  type        = bool
  default     = false
}

variable "has_downloads" {
  description = "Enable downloads"
  type        = bool
  default     = true
}

variable "allow_merge_commit" {
  description = "Allow merge commits"
  type        = bool
  default     = true
}

variable "allow_squash_merge" {
  description = "Allow squash merges"
  type        = bool
  default     = true
}

variable "allow_rebase_merge" {
  description = "Allow rebase merges"
  type        = bool
  default     = true
}

variable "allow_auto_merge" {
  description = "Allow auto-merge"
  type        = bool
  default     = false
}

variable "delete_branch_on_merge" {
  description = "Delete head branches after merge"
  type        = bool
  default     = true
}

variable "vulnerability_alerts" {
  description = "Enable vulnerability alerts"
  type        = bool
  default     = true
}

variable "auto_init" {
  description = "Initialize repository with README"
  type        = bool
  default     = false
}

variable "gitignore_template" {
  description = "Gitignore template to use"
  type        = string
  default     = null
}

variable "license_template" {
  description = "License template to use"
  type        = string
  default     = null
}

variable "topics" {
  description = "Repository topics"
  type        = list(string)
  default     = []
}

variable "template_repository" {
  description = "Template repository configuration"
  type = object({
    owner      = string
    repository = string
  })
  default = null
}

# Branch Protection Variables
variable "enable_branch_protection" {
  description = "Enable branch protection"
  type        = bool
  default     = true
}

variable "protected_branch" {
  description = "Branch to protect"
  type        = string
  default     = "main"
}

variable "require_signed_commits" {
  description = "Require signed commits"
  type        = bool
  default     = true
}

variable "require_linear_history" {
  description = "Require linear history"
  type        = bool
  default     = false
}

variable "allows_force_pushes" {
  description = "Allow force pushes"
  type        = bool
  default     = false
}

variable "allows_deletions" {
  description = "Allow branch deletions"
  type        = bool
  default     = false
}

variable "require_conversation_resolution" {
  description = "Require conversation resolution before merging"
  type        = bool
  default     = false
}

variable "lock_branch" {
  description = "Lock branch to read-only"
  type        = bool
  default     = false
}

variable "enforce_admins" {
  description = "Enforce branch protection for administrators"
  type        = bool
  default     = false
}

variable "required_status_checks" {
  description = "Required status checks configuration"
  type = object({
    strict   = bool
    contexts = list(string)
  })
  default = null
}

variable "required_pull_request_reviews" {
  description = "Required pull request reviews configuration"
  type = object({
    dismiss_stale_reviews           = bool
    require_code_owner_reviews      = bool
    required_approving_review_count = number
    require_last_push_approval      = bool
  })
  default = null
}

# Dependabot
variable "enable_dependabot" {
  description = "Enable Dependabot security updates"
  type        = bool
  default     = true
}
