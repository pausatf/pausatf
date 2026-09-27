# PAUSATF Terraform Infrastructure

Terraform configuration for PAUSATF DigitalOcean infrastructure, Cloudflare configuration, and GitHub repository settings.

## Source and state

- **Configuration:** [`pausatf/pausatf`](https://github.com/pausatf/pausatf), in this `terraform/` directory.
- **State:** HCP Terraform organization `pausatf`.
- **Active workspaces:** `pausatf-production`, `pausatf-staging`, `pausatf-dev`, `pausatf-cloudflare`, `pausatf-github`, and `pausatf-hcp`.
- **Retired workspaces:** `pausatf`, `painfra`, and `do-terraform`; their state is empty and backed up.
- **Runtime services:** DigitalOcean and Cloudflare.

Each environment contains an HCP Terraform `cloud` block. HCP stores the six active states and three empty legacy workspace states; execution mode is managed as local so Terraform can read sibling modules within this monorepo. Authenticate with `terraform login`, use the Terraform version pinned by the repository, and run Terraform from the environment directory on a trusted runner with provider credentials in its environment. The `hcp` root also needs `TFE_TOKEN` for the HCP Terraform provider. Do not pass the legacy `backend.hcl`; it describes the retired Spaces backend. The versioned Space `pausatf-terraform-state` and the old production state object remain as recovery material, not active state.

Mark provider tokens and keys sensitive wherever they are stored. Do not commit `.tfvars` containing credentials, state files, or API tokens. The production Spaces bucket resources have `prevent_destroy`; do not remove this guard without an approved recovery plan.

## Environments and current ownership

| Environment | Workspace | Resources |
|---|---|---|
| Production | `pausatf-production` | Production droplet, firewall, managed MySQL, project and SSH key references, and four PAUSATF Spaces buckets. |
| Staging | `pausatf-staging` | Staging droplet, firewall, and `stage.pausatf.org` DNS; imported and plan-clean. |
| Development | `pausatf-dev` | Development droplet, firewall, and `dev.pausatf.org` DNS; imported and plan-clean. |
| Cloudflare | `pausatf-cloudflare` | `pausatf.org` zone, central DNS, zone settings, and rulesets; imported and plan-clean. |
| GitHub | `pausatf-github` | `pausatf/pausatf` repository settings, default branch, branch protection, Dependabot, vulnerability alerts, and topics; imported and plan-clean. |
| HCP Terraform | `pausatf-hcp` | Manages workspace settings for six active and three retired PAUSATF workspaces, including execution mode, Terraform version, and safe apply/destroy defaults. |

See [INFRASTRUCTURE_INVENTORY.md](INFRASTRUCTURE_INVENTORY.md) for live IDs, state status, known drift, exclusions, and remaining work. Latest plans for production, staging, dev, Cloudflare, and GitHub report no changes.

## Initialize and inspect an environment

```bash
cd terraform/environments/production # or staging, dev, cloudflare, github, hcp
terraform login
terraform init
terraform validate
terraform plan
```

Use the Terraform version specified by mise in this repository. Plans are read-only until explicitly applied, but review them carefully: resource replacement, destruction, duplicate DNS creation, and settings changes require investigation first. The Cloudflare resources have been imported; require a fresh reviewed plan before any apply.

## Safety and changes

1. Make changes on a topic branch and open a PR with the relevant infrastructure issue linked.
2. Run formatting, validation, and the repository CI checks.
3. Refresh each affected HCP workspace and review the current-head plan.
4. Import pre-existing resources before managing them. Keep a resource in one workspace only.
5. Apply only after review and the required GitHub approvals.

Terraform state operations such as import and `state rm` change state ownership without changing the remote infrastructure. Back up state before state surgery and record the resource IDs and reason in the inventory.

## Documentation

- [Infrastructure inventory](INFRASTRUCTURE_INVENTORY.md)
- [Environment conventions](ENVIRONMENTS.md)
- [Cloudflare environment safety notes](environments/cloudflare/README.md)
- [DigitalOcean database module](modules/digitalocean/database/README.md)
- [Droplet and cloud-init module](modules/droplet/README.md)
