# Terraform Environments

This file describes the current environment layout. Live resource IDs and reconciliation status are maintained in [INFRASTRUCTURE_INVENTORY.md](INFRASTRUCTURE_INVENTORY.md).

## State and common settings

All six Terraform roots use HCP Terraform organization `pausatf`. Each root selects its own workspace in its `cloud` block. HCP stores state while workspace execution is local, allowing CLI runs to resolve shared modules outside an individual environment directory. Run `terraform login` and `terraform init` from the environment directory on a trusted runner with provider credentials set; the `hcp` root also needs `TFE_TOKEN`. Do not use the retired `backend.hcl` S3 configuration.

Terraform requires version 1.10 or later. Use the repository's mise-pinned version. Inject provider credentials as sensitive environment values in trusted CLI or CI runners. Never commit API tokens, provider credentials, state files, or credential-filled tfvars files.

| Directory | Workspace | Purpose | Current state |
|---|---|---|---|
| `environments/production/` | `pausatf-production` | Production DigitalOcean droplet/firewall, managed MySQL, project and SSH-key references, PAUSATF Spaces | Current resources imported, production state migrated from the versioned Spaces backend, and latest plan has no changes. |
| `environments/staging/` | `pausatf-staging` | Staging DigitalOcean droplet/firewall and `stage.pausatf.org` DNS | Resources imported; latest plan has no changes. |
| `environments/dev/` | `pausatf-dev` | Development DigitalOcean droplet/firewall and `dev.pausatf.org` DNS | Resources imported; latest plan has no changes. |
| `environments/cloudflare/` | `pausatf-cloudflare` | `pausatf.org` zone, central DNS, zone settings, and rulesets | Resources imported; latest plan has no changes. |
| `environments/github/` | `pausatf-github` | `pausatf/pausatf` repository settings, default branch, branch protection, Dependabot, alerts, and topics | Resources imported; latest plan has no changes. |
| `environments/hcp/` | `pausatf-hcp` | HCP workspace settings for all PAUSATF workspaces | Six active and three retired workspaces imported and managed; latest plan has no changes. |

## DigitalOcean design

Production, staging, and dev web droplets use the shared WordPress stack in `stacks/wordpress`. All use the existing shared `default-sfo2` VPC as a data source; Terraform does not own or modify that VPC. The production environment owns the managed MySQL cluster and four Spaces buckets. Staging and dev do not have separately provisioned managed databases in the current live inventory.

Firewall web ingress is limited to Cloudflare address ranges. SSH source CIDRs are explicit and include the Tailscale range; unrestricted SSH is rejected by variable validation. The production database firewall trusts the production droplet. Review the inventory for the current firewall and database IDs.

## Cloudflare ownership

The central Cloudflare root owns the zone, 37 central DNS records, settings, and rulesets. `dev.pausatf.org` belongs to the dev root and `stage.pausatf.org` to staging; never declare the same record in multiple states. These resources are imported in their respective workspaces, and all three roots currently plan with no changes.

## GitHub configuration

The GitHub root manages the `pausatf/pausatf` repository using the reusable module in `modules/github/repository`. Repository settings, default branch, main branch protection, Dependabot updates, vulnerability alerts, and topics are imported into HCP state. The latest plan reports no changes. The current GitHub Actions infrastructure workflow still needs an HCP API token secret before it can initialize and plan against the HCP backend.

## Routine workflow

```bash
cd terraform/environments/<environment>
terraform login
terraform init
terraform validate
terraform plan
```

Import pre-existing resources before managing them. Back up state before state-only removals or workspace consolidation. Never apply against an empty or partially imported state, and inspect all proposed replacement or destroy actions before approval.
