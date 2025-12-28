terraform {
  required_version = ">= 1.0"

  required_providers {
    github = {
      source  = "integrations/github"
      version = "~> 6.0"
    }
  }

  backend "s3" {
    bucket                      = "pausatf-terraform-state"
    key                         = "github/terraform.tfstate"
    region                      = "us-west-2"
    endpoint                    = "sfo2.digitaloceanspaces.com"
    skip_credentials_validation = true
    skip_metadata_api_check     = true
    skip_region_validation      = true
  }
}

provider "github" {
  owner = var.github_owner
  token = var.github_token
}

# PAUSATF Infrastructure Monorepo
# Consolidated repository containing infrastructure, configuration, scripts, docs, and content
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
  allow_merge_commit     = true
  allow_squash_merge     = true
  allow_rebase_merge     = true
  allow_auto_merge       = false
  delete_branch_on_merge = true

  # Security
  vulnerability_alerts = true
  enable_dependabot    = true

  # Branch Protection
  enable_branch_protection        = true
  protected_branch                = "main"
  require_signed_commits          = true
  require_linear_history          = false
  allows_force_pushes             = false
  allows_deletions                = false
  require_conversation_resolution = true
  enforce_admins                  = false

  # Required CI checks
  required_status_checks = {
    strict = true
    contexts = [
      "terraform-validate",
      "terraform-fmt",
      "ansible-lint",
      "shellcheck"
    ]
  }

  # Required reviews
  required_pull_request_reviews = {
    dismiss_stale_reviews           = true
    require_code_owner_reviews      = false
    required_approving_review_count = 1
    require_last_push_approval      = false
  }

  # Repository topics
  topics = [
    "infrastructure-as-code",
    "terraform",
    "ansible",
    "wordpress",
    "digitalocean",
    "cloudflare",
    "monorepo",
    "devops",
    "automation",
    "configuration-management",
    "scripts",
    "documentation",
    "runbooks"
  ]
}
