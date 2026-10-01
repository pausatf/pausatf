# PAUSATF Infrastructure Monorepo

## Merged changes and operational gates

The October 1, 2026 merges add recovery-set receipts/retention safeguards, the MySQL 8.4 target, TLS synchronization,
Prometheus 3.15.0, local OpenLiteSpeed MariaDB 13.0, and updated documentation tooling.
Repository merges do not establish live deployment or recoverability. Production deployment runs from those merges
were cancelled before any jobs started. `deploy-prod.yml` still queues on every push to main, including docs changes;
retain environment gates and cancel unintended runs. A staging check-mode run is not a staged production rollout.

- [Database upgrade gates](docs/runbooks/database-upgrades.md)
- [TLS ownership and retry behavior](docs/runbooks/tls-certificates.md)
- [Encrypted recovery backup](scripts/backup/README.md)
- [Local MariaDB recovery procedure](scripts/docker/README.md)
- [Dependency compatibility](docs/guides/dependency-compatibility.md)

Central repository for all PAUSATF infrastructure, configuration, automation scripts, documentation, and legacy content.

## Repository Structure

- **`/terraform`** - Infrastructure as Code for DigitalOcean, Cloudflare, and GitHub
- **`/ansible`** - Configuration management playbooks and roles
- **`/scripts`** - Automation scripts for backup, deployment, and maintenance
- **`/docs`** - Documentation hub including guides, runbooks, and architecture docs
- **`/content`** - Legacy content archive (race results, images, PDFs)
- **`/themes`** - WordPress themes (TheSource parent and child themes)

## Quick Start

### Prerequisites

- [Terraform](https://www.terraform.io/downloads) ~1.6
- [Ansible](https://docs.ansible.com/ansible/latest/installation_guide/intro_installation.html)
- [GitHub CLI](https://cli.github.com/)
- DigitalOcean account with API token
- Cloudflare account with API token

### Getting Started

1. Clone this repository:

   ```bash
   git clone git@github.com:pausatf/pausatf.git
   cd pausatf
   ```

2. See component-specific README files for detailed instructions:
   - [Terraform Documentation](./terraform/README.md)
   - [Ansible Documentation](./ansible/README.md)
   - [Scripts Documentation](./scripts/README.md)
   - [Infrastructure Docs](./docs/README.md)
   - [Legacy Content](./content/README.md)
   - [WordPress Themes](./themes/README.md)

### Common operations

```bash
# After production deployment authorization and operational gates
gh workflow run deploy-prod.yml

# Deploy to dev (or push to dev branch)
gh workflow run deploy-dev.yml

# Capture current prod plugin/theme inventory
gh workflow run capture-prod-inventory.yml

# Run Ansible against production manually
cd ansible
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --vault-password-file ../vault.pass

# Plan Terraform changes for production
cd terraform/environments/production
terraform init && terraform plan

# Plan Cloudflare DNS changes
cd terraform/environments/cloudflare
terraform init && terraform plan
```

For full operational procedures see [RUNBOOK.md](RUNBOOK.md).

## Architecture Overview

### Server environments

| Environment | Host | Web server | PHP | Ansible user |
|-------------|------|------------|-----|--------------|
| Production | `PROD_SERVER_IP` | Host Apache, event MPM/PHP-FPM | 8.4 | `somethingwithproof` |
| Staging | `STAGE_SERVER_IP` | Host OpenLiteSpeed | 8.4 | `somethingwithproof` |
| Dev | `DEV_SERVER_IP` | Host Apache, event MPM/PHP-FPM | 8.4 | `somethingwithproof` |

This table describes the tracked inventory and roles, not a fresh production inventory. The tracked Ansible path now
selects host Apache with event MPM/PHP-FPM for production and dev, and host OpenLiteSpeed for staging.
All three inventories select PHP 8.4 and SSH user `somethingwithproof`. Production uses managed MySQL;
Terraform targets MySQL 8.4, but the upgrade requires the [database gates](docs/runbooks/database-upgrades.md).
The separately maintained Docker production stack lives in
[pausatf-deployment](https://github.com/pausatf/pausatf-deployment). Inspect the actual target before deployment.

### Key tech stack

- **Hosting**: DigitalOcean droplets and managed database clusters, including production
- **CDN/DNS**: Cloudflare (free plan, full SSL, aggressive caching)
- **CMS**: WordPress; version/theme observations require a dated inventory capture before deployment
- **Config management**: Ansible with ansible-vault for secrets
- **IaC**: Terraform ~1.6–1.10, state in DO Spaces (`pausatf-terraform-state`)
- **Monitoring**: New Relic APM + infrastructure agent, Monit, sysstat
- **Security**: Fail2ban (SSH, Apache, WordPress jails), Cloudflare proxy

## GitHub Actions Workflows

| Workflow file | Trigger | Purpose |
|---------------|---------|---------|
| `deploy-prod.yml` | Push to `main`, manual dispatch | Runs `site.yml` against production; healthchecks `https://www.pausatf.org` |
| `deploy-staging.yml` | Push to `staging` branch | Runs `site.yml` against staging; healthchecks `https://stage.pausatf.org` |
| `deploy-dev.yml` | Push to `dev` branch, manual dispatch | Runs `site.yml` against dev |
| `do-nightly-snapshot.yml` | Daily 02:00 Pacific, manual dispatch | Creates timestamped DigitalOcean snapshot of prod droplet |
| `do-weekly-stage-snapshot.yml` | Weekly Sunday 11:00 UTC, manual dispatch | Creates a completed DigitalOcean staging snapshot and retains the two newest |
| systemd `pausatf-db-backup.timer` | 09:20 UTC daily + up to 20 minutes jitter | Tracked recovery drop-in adds encrypted deployment/configuration archives, image references and a manifest; verify installed service before relying on it |
| `wordpress-update-check.yml` | Daily 09:17 UTC, manual dispatch | Read-only production check for WordPress core and plugin updates; opens or closes a tracking issue without applying updates |
| `wp-plugin-audit.yml` | Manual dispatch | Production plugin/auth/WordPress diagnostics and explicitly requested remediation probes |
| `capture-prod-inventory.yml` | Manual dispatch | Runs `capture-wp-inventory.yml`; commits `group_vars/production/wordpress.yml` |
| `infra-staging.yml` | Manual dispatch | Terraform plan + apply for staging environment |
| `ci.yml` | PR and push to `main` | Ansible/YAML, Terraform, ShellCheck/Bash, Markdown/link and maintenance safety checks |
| `codeql.yml` | See workflow triggers | CodeQL analysis of Actions and JavaScript |
| `molecule.yml` | See workflow path filters | Role integration scenarios |

## Secrets and Variables Required

| Secret | Used by | Description |
|--------|---------|-------------|
| `PROD_SSH_PRIVATE_KEY` | deploy-prod, capture-prod-inventory, do-nightly-snapshot, wordpress-update-check | Production SSH private key; verify the user selected by each workflow and inventory |
| `STAGING_SSH_PRIVATE_KEY` | deploy-prod staging validation | SSH key for the staging check-mode job |
| `DEV_SSH_PRIVATE_KEY` | deploy-dev | Private key for dev host |
| `ANSIBLE_VAULT_PASSWORD` | deploy-prod, deploy-staging, deploy-dev, capture-prod-inventory | Ansible vault decryption password |
| `DO_TOKEN` | infra-staging, do-nightly-snapshot, do-weekly-stage-snapshot | DigitalOcean API token with permission to create and prune droplet snapshots |
| `DO_PROD_DROPLET_ID` | do-nightly-snapshot (optional) | Numeric production droplet ID; when unset or inaccessible, the workflow resolves `pausatf-prod-v2` by name |
| `DO_STAGE_DROPLET_ID` | do-weekly-stage-snapshot (optional) | Numeric staging droplet ID; when unset or inaccessible, the workflow resolves `pausatf-stage-v2` by name |
| `SPACES_ACCESS_KEY_ID` | infra-staging | DO Spaces key for Terraform state backend |
| `SPACES_SECRET_ACCESS_KEY` | infra-staging | DO Spaces secret for Terraform state backend |
| `CLOUDFLARE_API_TOKEN` | infra-staging | Cloudflare API token for Terraform |

| Variable | Used by | Description |
|----------|---------|-------------|
| `PROD_HOST` | capture-prod-inventory, wp-plugin-audit, wordpress-update-check | Production SSH host/IP for GitHub Actions workflows that run remote checks over SSH |
| `PROD_SSH_KNOWN_HOSTS` | wordpress-update-check | Trusted `known_hosts` entries for the production SSH endpoint; configure the host key out of band and keep strict host-key checking enabled |

## Development Workflow

### Branching Strategy

- `main` - Production-ready code
- `feature/*` - New features
- `fix/*` - Bug fixes
- `docs/*` - Documentation updates

### Pull Request Process

1. Create a feature branch from `main`
2. Make your changes
3. Ensure all CI checks pass
4. Follow the [maintainer review process](CONTRIBUTING.md#review-process)
5. Merge after the required current-head maintainer decision and passing checks

### CI/CD

`ci.yml` runs on pull requests and pushes to main. Some jobs skip dependency-titled PRs; inspect actual job results.
Other workflows have their own path filters. The checks include:

- **Terraform** - Validation, formatting, security scanning (TFSec), linting (TFLint)
- **Ansible** - Linting (ansible-lint), syntax checking
- **Scripts** - ShellCheck for bash scripts
- **Docs** - Markdown linting, link checking

## Component Documentation

Each component has its own README with specific instructions:

| Component | Path | Description |
| --------- | ---- | ----------- |
| Terraform | [terraform/](./terraform) | Infrastructure as Code modules and environments |
| Ansible | [ansible/](./ansible) | Configuration management for servers |
| Scripts | [scripts/](./scripts) | Automation and maintenance scripts |
| Docs | [docs/](./docs) | Guides, runbooks, and architecture documentation |
| Content | [content/](./content) | Historical race data and media archive |
| Themes | [themes/](./themes) | WordPress themes for pausatf.org |

## Migration Notes

This monorepo was created by consolidating 7 separate repositories with full git history preservation:

- `pausatf-terraform` → `terraform/`
- `pausatf-ansible` → `ansible/`
- `pausatf-scripts` → `scripts/`
- `pausatf-infrastructure-docs` → `docs/`
- `pausatf-legacy-content` → `content/`
- `pausatf-theme-thesource` → `themes/thesource/`
- `pausatf-theme-thesource-child` → `themes/thesource-child/`

**Migration Date**: December 21, 2025
**Migration Method**: Git subtree with full commit history preserved

## Contributing

We welcome contributions! Please read our [Contributing Guidelines](CONTRIBUTING.md) before submitting pull requests.

For information about the monorepo migration, see the [Migration Guide](docs/MIGRATION.md).

## Resources

- [Contributing Guidelines](CONTRIBUTING.md) - How to contribute to this project
- [Migration Guide](docs/MIGRATION.md) - Information about the monorepo migration
- [Component Documentation](#component-documentation) - See individual component READMEs

## License

See individual component directories for licensing information.

## Support

For questions or issues:

- Open a [GitHub issue](https://github.com/pausatf/pausatf/issues)
- Check the [Migration Guide](docs/MIGRATION.md) for common questions
- Contact @somethingwithproof
