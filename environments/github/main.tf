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

# Infrastructure Documentation Repository
module "pausatf_infrastructure_docs" {
  source = "../../modules/github/repository"

  name        = "pausatf-infrastructure-docs"
  description = "Infrastructure documentation, runbooks, and operational guides for PAUSATF.org"
  visibility  = "public"

  has_issues   = true
  has_wiki     = false
  has_projects = false

  delete_branch_on_merge = true
  vulnerability_alerts   = true

  topics = [
    "documentation",
    "infrastructure",
    "runbooks",
    "operations",
    "wordpress",
    "digitalocean",
    "cloudflare"
  ]

  # Branch Protection
  enable_branch_protection = true
  require_signed_commits   = true
  enforce_admins           = false

  # Dependabot
  enable_dependabot = true
}

# Terraform Infrastructure Repository
module "pausatf_terraform" {
  source = "../../modules/github/repository"

  name        = "pausatf-terraform"
  description = "Terraform infrastructure as code for PAUSATF.org - DigitalOcean and Cloudflare resources"
  visibility  = "public"

  has_issues   = true
  has_wiki     = false
  has_projects = false

  delete_branch_on_merge = true
  vulnerability_alerts   = true

  topics = [
    "terraform",
    "infrastructure-as-code",
    "digitalocean",
    "cloudflare",
    "wordpress",
    "iac"
  ]

  # Branch Protection
  enable_branch_protection = true
  require_signed_commits   = true
  enforce_admins           = false

  required_status_checks = {
    strict = true
    contexts = [
      "terraform-validate",
      "terraform-fmt",
      "tfsec",
      "tflint"
    ]
  }

  # Dependabot
  enable_dependabot = true
}

# Ansible Configuration Repository
module "pausatf_ansible" {
  source = "../../modules/github/repository"

  name        = "pausatf-ansible"
  description = "Ansible configuration management for PAUSATF.org WordPress infrastructure"
  visibility  = "public"

  has_issues   = true
  has_wiki     = false
  has_projects = false

  delete_branch_on_merge = true
  vulnerability_alerts   = true

  topics = [
    "ansible",
    "configuration-management",
    "wordpress",
    "apache",
    "mysql",
    "php",
    "devops"
  ]

  # Branch Protection
  enable_branch_protection = true
  require_signed_commits   = true
  enforce_admins           = false

  required_status_checks = {
    strict = true
    contexts = [
      "ansible-lint",
      "yamllint"
    ]
  }

  # Dependabot
  enable_dependabot = true
}

# Scripts Repository
module "pausatf_scripts" {
  source = "../../modules/github/repository"

  name        = "pausatf-scripts"
  description = "Automation scripts for PAUSATF.org operations, backups, monitoring, and maintenance"
  visibility  = "public"

  has_issues   = true
  has_wiki     = false
  has_projects = false

  delete_branch_on_merge = true
  vulnerability_alerts   = true

  topics = [
    "automation",
    "scripts",
    "bash",
    "operations",
    "monitoring",
    "backup",
    "maintenance"
  ]

  # Branch Protection
  enable_branch_protection = true
  require_signed_commits   = true
  enforce_admins           = false

  required_status_checks = {
    strict = true
    contexts = [
      "shellcheck"
    ]
  }

  # Dependabot
  enable_dependabot = true
}
