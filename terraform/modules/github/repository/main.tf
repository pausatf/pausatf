terraform {
  required_providers {
    github = {
      source  = "integrations/github"
      version = "~> 6.0"
    }
  }
}

resource "github_repository" "repo" {
  name        = var.name
  description = var.description
  visibility  = var.visibility

  # Features
  has_issues      = var.has_issues
  has_wiki        = var.has_wiki
  has_projects    = var.has_projects
  has_discussions = var.has_discussions
  has_downloads   = var.has_downloads

  # Settings
  allow_merge_commit     = var.allow_merge_commit
  allow_squash_merge     = var.allow_squash_merge
  allow_rebase_merge     = var.allow_rebase_merge
  allow_auto_merge       = var.allow_auto_merge
  delete_branch_on_merge = var.delete_branch_on_merge

  # Security
  vulnerability_alerts = var.vulnerability_alerts

  # Auto init
  auto_init          = var.auto_init
  gitignore_template = var.gitignore_template
  license_template   = var.license_template

  # Topics
  topics = var.topics

  # Template repository
  dynamic "template" {
    for_each = var.template_repository != null ? [var.template_repository] : []
    content {
      owner      = template.value.owner
      repository = template.value.repository
    }
  }
}

resource "github_branch_protection" "main" {
  count = var.enable_branch_protection ? 1 : 0

  repository_id = github_repository.repo.node_id
  pattern       = var.protected_branch

  # Require signed commits
  require_signed_commits = var.require_signed_commits

  # Require linear history
  require_linear_history = var.require_linear_history

  # Allow force pushes
  allows_force_pushes = var.allows_force_pushes

  # Allow deletions
  allows_deletions = var.allows_deletions

  # Require conversation resolution
  require_conversation_resolution = var.require_conversation_resolution

  # Lock branch
  lock_branch = var.lock_branch

  # Required status checks
  dynamic "required_status_checks" {
    for_each = var.required_status_checks != null ? [var.required_status_checks] : []
    content {
      strict   = required_status_checks.value.strict
      contexts = required_status_checks.value.contexts
    }
  }

  # Required pull request reviews
  dynamic "required_pull_request_reviews" {
    for_each = var.required_pull_request_reviews != null ? [var.required_pull_request_reviews] : []
    content {
      dismiss_stale_reviews           = required_pull_request_reviews.value.dismiss_stale_reviews
      require_code_owner_reviews      = required_pull_request_reviews.value.require_code_owner_reviews
      required_approving_review_count = required_pull_request_reviews.value.required_approving_review_count
      require_last_push_approval      = required_pull_request_reviews.value.require_last_push_approval
    }
  }

  # Enforce for admins
  enforce_admins = var.enforce_admins
}

# Dependabot security updates
resource "github_repository_dependabot_security_updates" "repo" {
  count = var.enable_dependabot ? 1 : 0

  repository = github_repository.repo.name
  enabled    = true
}

# Repository topics
resource "github_repository_topics" "repo" {
  count = length(var.topics) > 0 ? 1 : 0

  repository = github_repository.repo.name
  topics     = var.topics
}
