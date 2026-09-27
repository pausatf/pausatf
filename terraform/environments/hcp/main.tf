terraform {
  required_version = ">= 1.10.0"

  required_providers {
    tfe = {
      source  = "hashicorp/tfe"
      version = "~> 0.80.0"
    }
  }

  cloud {
    organization = "pausatf"

    workspaces {
      name = "pausatf-hcp"
    }
  }
}

provider "tfe" {}

locals {
  workspaces = {
    "pausatf-production" = "1.10.3"
    "pausatf-staging"    = "1.10.3"
    "pausatf-dev"        = "1.10.3"
    "pausatf-cloudflare" = "1.10.3"
    "pausatf-github"     = "1.10.3"
    "pausatf-hcp"        = "1.10.3"
    "pausatf"            = "1.11.4"
    "painfra"            = "1.5.0"
    "do-terraform"       = "1.11.3"
  }
}

# These workspaces all store state in HCP Terraform. Local execution is needed
# because the application workspaces load shared Terraform modules from sibling
# directories in the monorepo. Each workspace still records state versions and
# runs in HCP Terraform.
resource "tfe_workspace" "pausatf" {
  for_each = local.workspaces

  name                  = each.key
  organization          = "pausatf"
  description           = contains(["pausatf", "painfra", "do-terraform"], each.key) ? "Retired legacy workspace; its state was backed up and cleared on 2026-09-27. Active infrastructure is managed by the current PAUSATF environment workspaces." : null
  terraform_version     = each.value
  auto_apply            = false
  allow_destroy_plan    = true
  queue_all_runs        = false
  force_delete          = false
  file_triggers_enabled = contains(["pausatf", "painfra", "do-terraform"], each.key) ? false : true

  lifecycle {
    prevent_destroy = true
  }
}

resource "tfe_workspace_settings" "pausatf" {
  for_each = tfe_workspace.pausatf

  workspace_id        = each.value.id
  execution_mode      = "local"
  global_remote_state = false
}
