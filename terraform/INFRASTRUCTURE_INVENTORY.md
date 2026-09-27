# PAUSATF Infrastructure Inventory

**Verified:** 2026-09-27
**Source:** DigitalOcean API, HCP Terraform states, GitHub API, and Cloudflare inventory recorded in the reconciliation notes.
**Purpose:** Track which PAUSATF resources exist, where their Terraform configuration lives, and whether HCP Terraform currently owns their state.

## Ownership model

- Git source of truth: [`pausatf/pausatf`](https://github.com/pausatf/pausatf), under `terraform/`.
- Terraform state service: HCP Terraform organization `pausatf`.
- Runtime infrastructure: DigitalOcean and Cloudflare.
- Current environment workspaces: `pausatf-production`, `pausatf-staging`, `pausatf-dev`, `pausatf-cloudflare`, `pausatf-github`, and `pausatf-hcp`.
- The six active workspaces and three retired legacy workspaces use HCP-managed state with local execution. Provider credentials must be present in the trusted runner environment.
- Production state was migrated from the versioned `pausatf-terraform-state` Space to HCP Terraform. The old S3 object is retained as a recovery copy; do not use it as an active backend.

## DigitalOcean

| Environment/resource | Live resource | Terraform configuration | HCP state | Notes |
|---|---|---|---|---|
| Production web | `pausatf-prod-v2`, droplet `586316413`, SFO2, `s-2vcpu-4gb`, Ubuntu 26.04 LTS | `environments/production` via `stacks/wordpress` | Imported | Firewall `pausatf-prod-v2-fw` (`5fb8b83b-12bb-4256-8ffc-ccf275f55a7e`) imported. |
| Staging web | `pausatf-stage-v2`, droplet `586535212`, SFO2, `s-2vcpu-4gb`, Ubuntu 26.04 LTS | `environments/staging` via `stacks/wordpress` | Imported | Firewall `pausatf-stage-v2-tunnel` (`b17da40d-aac5-48d0-8553-160a77212e82`) imported. |
| Development web | `pausatf-dev-v2`, droplet `586535214`, SFO2, `s-1vcpu-2gb`, Ubuntu 26.04 LTS | `environments/dev` via `stacks/wordpress` | Imported | Firewall `pausatf-nonprod-cf-lock` (`96d0d607-9071-4a7c-8c61-10614453f98c`) imported. |
| Production database | `pausatf-prod-db`, MySQL 8, SFO2, `db-s-1vcpu-1gb`, one node; ID `ad2a900b-8181-4506-805d-d19eef90b337` | `environments/production` and `modules/digitalocean/database` | Imported | Trusted source is the production droplet. Daily database backups were present for eight consecutive days; confirm retention in the DO console/API. |
| VPC | Shared `default-sfo2`, ID `4ee39499-dc85-11e8-9f23-3cfdfea9fff1` | Referenced as a data source in each DO environment | Read via data source | Shared default VPC is not owned or modified by this project. |
| Project | `PAUSATF`, ID `8ddce7ba-f064-4611-8460-0771dd817342` | Production configuration | Imported | Project membership also contains resources that appear unrelated to PAUSATF; do not move/delete those without confirming ownership. |
| SSH key | `m3 laptop`, ID `46721354` | Production configuration | Imported | Existing key reference. |
| Spaces `pausatf` | SFO3, private | `environments/production/spaces.tf` | Imported | Versioning enabled; noncurrent versions expire after 60 days; production backups expire after 45 days, recovery images after 60 days, recovery sets after 45 days. |
| Spaces `pausatf-static` | SFO3, private | `environments/production/spaces.tf` | Imported | Versioning enabled; noncurrent versions expire after 60 days; incomplete multipart uploads abort after 7 days. |
| Spaces `pausatf-backups` | SFO2, private | `environments/production/spaces.tf` | Imported | Versioning enabled; noncurrent versions expire after 60 days; dev 30 days; production and staging 60 days. |
| Spaces `pausatf-terraform-state` | SFO2, private | `environments/production/spaces.tf` | Imported | Versioning enabled; retained as a recovery archive after migration to HCP Terraform. |

The three current droplets, their firewalls, the production database, project, SSH key, and four Spaces buckets are represented in HCP state. A state-only apply persisted the four database resource address moves in production; it reported zero resources added, changed, or destroyed. No live DigitalOcean resources were modified. Droplet `user_data` and attached SSH key changes are ignored by lifecycle rules because the live systems have migration-managed state; review that policy before changing it.

Custom snapshot workflows are active: production nightly with seven retained snapshots and staging weekly with two. Native DigitalOcean droplet backup policies are not enabled. This is separate from the managed database's daily backups.

The `PAUSATF` DigitalOcean project also lists a `trading-dashboard-backup` Space and a GLMR trading-data volume snapshot. They appear unrelated and are intentionally excluded from PAUSATF Terraform ownership pending ownership confirmation. Other account droplets are also outside this inventory.

## Cloudflare

The `pausatf.org` zone (zone ID `67b87131144a68ad5ed43ebfd4e6d811`) and its DNS, zone settings, and security rules are declared in `environments/cloudflare`. The Cloudflare configuration was reconciled against the live account in PR #210, merged as `fcc9fa9850b2572e135cd07fe562828b1fb2d812`.

The Cloudflare HCP workspace now owns the zone, 37 central DNS records, six zone settings, and two active rulesets. The dev and staging DNS records are imported to their dedicated states. The first refreshed plan found six metadata/content/TTL differences; configuration was aligned to live values, and the next plan reported no changes. No Cloudflare apply has been run.

The dev and staging droplet/firewall state imports use Cloudflare's public IP ranges data source. This does not import or change Cloudflare account resources.

## GitHub

The `pausatf/pausatf` repository and these settings are now imported into `pausatf-github`:

- Repository settings
- Default branch
- Main branch protection
- Dependabot security updates
- Vulnerability alerts
- Repository topics, managed as a `github_repository` attribute (no topics are currently set)

The configuration was updated to match the live settings: squash-only merge, auto-merge enabled, administrator-enforced branch protection, the current CI contexts, non-strict status checks, and zero required approving reviews. A fresh plan reports no changes. The generated inventory is in `environments/github/inventory.yml`.

## HCP Terraform and legacy state

| Workspace | State status | Follow-up |
|---|---|---|
| `pausatf-production` | Current production DO resources, database, and Spaces imported; migrated from S3 | Latest plan: no changes; database state addresses are migrated. |
| `pausatf-staging` | Current staging droplet, firewall, and stage DNS imported | Latest plan: no changes. |
| `pausatf-dev` | Current dev droplet, firewall, and dev DNS imported | Latest plan: no changes. |
| `pausatf-cloudflare` | Zone, 37 central DNS records, six settings, and two rulesets imported | Latest plan: no changes. |
| `pausatf-github` | Repository, default branch, branch protection, Dependabot, vulnerability alerts, and topics are tracked | Latest plan: no changes. |
| `pausatf-hcp` | HCP workspace safety and execution settings for six active and three retired PAUSATF workspaces | All nine workspaces imported; latest plan: no changes. |
| `pausatf`, `painfra`, `do-terraform` | Retired legacy workspaces; all state entries were removed after backing up their state, and no live DigitalOcean resources were destroyed | Workspace metadata and local execution are managed by `pausatf-hcp`; backups are in `/tmp/pausatf-legacy-state-20260927/`. |

State-only removals were made from legacy workspaces after saving mode-600 state backups. The legacy `do-terraform` workspace's obsolete VPC references and requested domain entries were removed earlier; the remaining stale resources and duplicate references were then removed from all three legacy states. No live DigitalOcean resource was destroyed.

## Remaining reconciliation

1. Capture TLS settings and certificate renewal/termination responsibilities in Terraform/Ansible for dev, staging, and production.
2. Audit supported software versions, especially managed database engine versions and DigitalOcean maintenance options.
3. Reconcile and document database backup retention and restore testing, and retain a tested recovery copy of the pre-migration Terraform state.

**Safety:** Imports and state-only changes did not create, modify, or destroy live services. The sole apply persisted database resource address moves and reported zero resources added, changed, or destroyed. Review an environment plan and obtain the normal PR approval before any infrastructure apply.
