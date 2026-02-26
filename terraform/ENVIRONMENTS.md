# Terraform Environments

Reference for all Terraform environments and reusable modules in this
repository.

---

## State Backend

All environments use DigitalOcean Spaces as an S3-compatible remote backend.

| Setting | Value |
|---------|-------|
| Endpoint | `sfo2.digitaloceanspaces.com` |
| Bucket | `pausatf-terraform-state` |
| Region (dummy) | `us-west-1` |
| Credentials | `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` env vars (DO Spaces keys) |

Each environment writes to its own state key (see table below). State files are
never shared between environments.

---

## Environments

### `environments/production/`

Manages the live PAUSATF website infrastructure.

**State key**: `production/terraform.tfstate`

**Providers**:
- `digitalocean/digitalocean` ~> 2.47
- `cloudflare/cloudflare` ~> 5.15

**Terraform**: >= 1.10.0

**Resources managed**:

| Resource | Name | Notes |
|----------|------|-------|
| `digitalocean_project` | PAUSATF | Groups production resources |
| `digitalocean_vpc` | pausatf-production-vpc | `10.10.0.0/16`, region `sfo2` |
| `digitalocean_droplet` | pausatf-prod | `s-4vcpu-8gb`, Ubuntu, backups enabled, cloud-init via `cloud-init-ubuntu-24.yml` |
| `digitalocean_reserved_ip` | production | Prevents IP change on rebuild |
| `digitalocean_reserved_ip_assignment` | production | Attaches reserved IP to droplet |
| `digitalocean_ssh_key` | m3 laptop | Public key from `var.ssh_public_key` |
| `digitalocean_firewall` | pausatf-production-firewall | Allows 80, 443 from anywhere; 22 from `ssh_allowed_ips` |
| `digitalocean_monitor_alert` | cpu_high | CPU > 80% for 5 min → email |
| `digitalocean_monitor_alert` | memory_high | Memory > 85% for 5 min → email |
| `digitalocean_monitor_alert` | disk_high | Disk > 75% → email |

**Variables** (set in `terraform.tfvars`, never committed):

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `do_token` | yes | — | DigitalOcean API token |
| `cloudflare_api_token` | yes | — | Cloudflare API token |
| `ssh_public_key` | yes | — | SSH public key for droplet access |
| `region` | no | `sfo2` | DigitalOcean region |
| `droplet_size` | no | `s-1vcpu-1gb` | Droplet size slug |
| `droplet_image` | no | `ubuntu-22-04-x64` | Base image |
| `ssh_allowed_ips` | no | `[]` | IPs allowed to SSH |
| `alert_email_addresses` | no | `[]` | Monitoring alert recipients |

**Outputs**: `droplet_id`, `droplet_ip`, `droplet_urn`, `database_id`,
`database_host` (sensitive), `database_uri` (sensitive), `vpc_id`, `firewall_id`

---

### `environments/staging/`

Mirrors production topology at reduced cost for pre-release testing.

**State key**: `staging/terraform.tfstate`

**Providers**:
- `digitalocean/digitalocean` ~> 2.0
- `cloudflare/cloudflare` ~> 5.15

**Terraform**: >= 1.6.0

**Resources managed**:

| Resource | Name | Notes |
|----------|------|-------|
| `digitalocean_droplet` | pausatf-stage | No backups (cost saving); cloud-init via `cloud-init-openlitespeed.yml` |
| `digitalocean_database_cluster` | pausatf-stage-db | MySQL 8, single node, maintenance Saturday 02:00 |
| `digitalocean_database_firewall` | staging | Restricts DB access to staging droplet only |
| `digitalocean_firewall` | pausatf-staging-firewall | Allows 80, 443, 22, 7080 (OLS WebAdmin) |
| `module.cloudflare_dns_staging` | — | Creates `stage.pausatf.org` A record (proxied) |

**Key difference from production**: Uses OpenLiteSpeed instead of Apache.
Database is a managed DO cluster rather than local MySQL.

---

### `environments/dev/`

Lightweight environment for development work. Mirrors staging structure.

**State key**: `dev/terraform.tfstate`

**Providers**:
- `digitalocean/digitalocean` ~> 2.0
- `cloudflare/cloudflare` ~> 5.15

**Terraform**: >= 1.6.0

**Resources managed**:

| Resource | Name | Notes |
|----------|------|-------|
| `digitalocean_droplet` | pausatf-dev | No backups; cloud-init via `cloud-init.yml` |
| `digitalocean_database_cluster` | pausatf-dev-db | MySQL 8, single node |
| `digitalocean_database_firewall` | dev | Restricts DB to dev droplet |
| `digitalocean_vpc` | pausatf-dev-vpc | Dedicated VPC |
| `digitalocean_firewall` | pausatf-dev-firewall | Allows 80, 443, 22 |
| `module.cloudflare_dns_dev` | — | Creates `dev.pausatf.org` A record (proxied) |

---

### `environments/cloudflare/`

Manages the entire `pausatf.org` Cloudflare zone — all DNS records, zone
settings, and email configuration.

**State key**: `cloudflare/terraform.tfstate`

**Providers**:
- `cloudflare/cloudflare` ~> 5.0

**Terraform**: >= 1.6.0

**Resources managed**:

| Type | Records | Notes |
|------|---------|-------|
| A (proxied) | `@`, `www` | Production site behind Cloudflare proxy |
| A (unproxied) | `ftp`, `mail`, `monitor`, `stage`, `staging` | Direct server access |
| CNAME | `prod`, SendGrid delivery/tracking (`REDACTED_SENDGRID`, `url7068`, `url7741`, `51871933`) | |
| CNAME | `s1._domainkey`, `s2._domainkey` | SendGrid DKIM |
| MX (5) | `@` | Google Workspace, priorities 1/5/5/10/10 |
| TXT | SPF | `include:_spf.google.com include:sendgrid.net ~all` |
| TXT | DMARC | `_dmarc`, policy `p=none` (monitoring mode) |
| TXT | Google verification, Cloudflare DKIM | |
| CAA (5) | `@` | Let's Encrypt and DigiCert; iodef to `admin@pausatf.org` |

**Variables** (set in `terraform.tfvars`):

| Variable | Description |
|----------|-------------|
| `cloudflare_api_token` | Cloudflare API token |
| `cloudflare_account_id` | Cloudflare account ID |
| `production_ip` | Production droplet IP (`REDACTED_PROD_NEW_IP`) |
| `staging_ip` | Staging droplet IP |

**DNS change process**: Always edit `main.tf` and run `terraform apply` — do
not make manual changes in the Cloudflare dashboard or state will drift.

---

### `environments/github/`

Manages the `pausatf/pausatf` GitHub repository settings via Terraform.

**State key**: `github/terraform.tfstate`

**Providers**:
- `integrations/github` ~> 6.0

**Terraform**: >= 1.0

**Resources managed** (via `modules/github/repository`):
- Repository features: issues, wiki, projects enabled
- Merge settings: merge commit, squash, rebase allowed; auto-merge off;
  delete branch on merge on
- Branch protection on `main`: signed commits required, force push blocked,
  1 required review, stale review dismissal enabled
- Required CI checks: `terraform-validate`, `terraform-fmt`, `ansible-lint`,
  `shellcheck`
- Dependabot and vulnerability alerts enabled

**Variables** (set in `terraform.tfvars`):

| Variable | Description |
|----------|-------------|
| `github_token` | GitHub personal access token |
| `github_owner` | GitHub organization/owner (`pausatf`) |

---

## Modules

### `modules/digitalocean/droplet`

Reusable DigitalOcean droplet with optional firewall.

**Inputs**:

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `name` | yes | — | Droplet name (lowercase, numbers, hyphens) |
| `environment` | yes | — | `production`, `staging`, or `development` |
| `image` | yes | — | OS image or snapshot ID |
| `region` | no | `sfo2` | DigitalOcean region |
| `size` | no | `s-1vcpu-1gb` | Droplet size slug |
| `ssh_key_ids` | no | `[]` | SSH key IDs |
| `vpc_uuid` | no | null | VPC to attach |
| `monitoring_enabled` | no | true | Enable DO monitoring |
| `backups_enabled` | no | false | Enable automated backups |
| `user_data` | no | null | Cloud-init script |
| `tags` | no | `[]` | Tags |
| `firewall_inbound_rules` | no | `[]` | Inbound firewall rules |
| `firewall_outbound_rules` | no | `[]` | Outbound firewall rules |

**Tests**: `modules/digitalocean/droplet/tests/droplet_test.go`

---

### `modules/digitalocean/database`

Reusable DigitalOcean managed database cluster (MySQL).

**Inputs**: cluster name, engine, version, size, region, node count, tags,
maintenance window, firewall rules.

---

### `modules/cloudflare/zone`

Manages a Cloudflare zone and its settings (SSL mode, TLS version, security
level, caching, HTTP/2, HTTP/3, minification, DNSSEC).

**Inputs**:

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `account_id` | yes | — | Cloudflare account ID |
| `zone_name` | yes | — | Domain name |
| `plan` | no | `free` | Zone plan |
| `ssl_mode` | no | `strict` | SSL mode |
| `min_tls_version` | no | `1.2` | Minimum TLS version |
| `security_level` | no | `medium` | Security level |
| `browser_cache_ttl` | no | `14400` | Browser cache TTL (seconds) |

---

### `modules/cloudflare/dns`

Creates a set of Cloudflare DNS records in a given zone.

**Inputs**:

| Variable | Required | Description |
|----------|----------|-------------|
| `zone_id` | yes | Cloudflare zone ID |
| `dns_records` | no | List of records: `name`, `type`, `value`, `ttl`, `proxied`, `priority`, `comment` |

Used by `environments/staging` and `environments/dev` to create their
respective subdomains.

---

### `modules/github/repository`

Manages a GitHub repository, branch protection, and merge settings.

**Inputs**: name, description, visibility, feature flags (issues, wiki,
projects, discussions), merge strategy settings, branch protection config,
required status checks, required review counts, Dependabot toggle, topics.

---

### `modules/droplet` (cloud-init templates)

Cloud-init templates used as `user_data` when provisioning droplets.

| File | Purpose |
|------|---------|
| `cloud-init-ubuntu-24.yml` | Production — Ubuntu 24.04, Apache + PHP stack |
| `cloud-init-openlitespeed.yml` | Staging — OpenLiteSpeed stack |
| `cloud-init-base.yml` | Base template (common packages) |
| `cloud-init-nginx.yml.deprecated` | Deprecated — not in use |

---

## Compliance Tests

`terraform/tests/compliance/` contains BDD-style compliance tests (`.feature`
files) for Cloudflare and DigitalOcean resource configurations. Run with a
compatible Terraform testing tool (e.g., `terraform test` or Conftest).
