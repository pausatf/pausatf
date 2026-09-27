terraform {
  required_version = ">= 1.10.0"

  required_providers {
    github = {
      source  = "integrations/github"
      version = "~> 6.0"
    }
  }

  cloud {
    organization = "pausatf"

    workspaces {
      name = "pausatf-github"
    }
  }
}

provider "github" {
  owner = var.github_owner
  token = var.github_token
}

# PAUSATF Infrastructure Monorepo
# Consolidated repository containing infrastructure, configuration, scripts, docs, and content
# Signed commits remain intentionally disabled to preserve the current contributor workflow.
#trivy:ignore:AVD-GIT-0004
module "pausatf_monorepo" {
  source = "../../modules/github/repository"

  name        = "pausatf"
  description = "PAUSATF Infrastructure Monorepo - Consolidated infrastructure, configuration, scripts, documentation, and content"
  visibility  = "public"

  # Features
  has_issues      = true
  has_wiki        = true
  has_projects    = true
  has_discussions = false

  # Merge settings
  allow_merge_commit     = false
  allow_squash_merge     = true
  allow_rebase_merge     = false
  allow_auto_merge       = true
  delete_branch_on_merge = true

  # Security
  vulnerability_alerts = true
  enable_dependabot    = true

  # Branch Protection
  enable_branch_protection        = true
  default_branch                  = "main"
  protected_branch                = "main"
  require_signed_commits          = false
  require_linear_history          = false
  allows_force_pushes             = false
  allows_deletions                = false
  require_conversation_resolution = true
  enforce_admins                  = true

  # Required CI checks
  required_status_checks = {
    strict = false
    contexts = [
      "Ansible Lint",
      "YAML Lint",
      "Ansible Syntax Check",
      "ShellCheck",
      "Bash Syntax Check",
      "Markdown Lint",
      "Terraform Format",
      "TFSec",
      "TFLint",
      "CodeQL",
      "Markdown Link Check",
      "Analyze (actions)",
      "Analyze (javascript)",
      "Maintenance Safety Tests"
    ]
  }

  # Required reviews
  required_pull_request_reviews = {
    dismiss_stale_reviews           = true
    require_code_owner_reviews      = false
    required_approving_review_count = 0
    require_last_push_approval      = false
  }

  # Repository topics
  topics = []
}
