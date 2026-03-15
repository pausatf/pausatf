# PAUSATF Infrastructure Monorepo

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
# Deploy to production (or push to main)
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
| Production | `ftp.pausatf.org` (64.225.40.54) | Apache 2 + MPM Prefork | 7.4 | `root` |
| Staging | `stage.pausatf.org` | OpenLiteSpeed | 8.3 | `root` |
| Dev | `dev.pausatf.org` | OpenLiteSpeed | 8.4 | `root` |

All servers: Ubuntu 20.04 LTS, DigitalOcean `sfo2`, MySQL 5.7 (prod local) / MySQL 8 (staging/dev managed cluster).

### Key tech stack

- **Hosting**: DigitalOcean droplets + managed DB clusters (staging/dev)
- **CDN/DNS**: Cloudflare (free plan, full SSL, aggressive caching)
- **CMS**: WordPress 6.8.3, active theme `TheSource-child`
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
| `backup-legacy.yml` | Daily 01:00 Pacific, manual dispatch | Rsyncs `/var/www/legacy` from prod; commits changes to repo |
| `capture-prod-inventory.yml` | Manual dispatch | Runs `capture-wp-inventory.yml`; commits `group_vars/production/wordpress.yml` |
| `infra-staging.yml` | Manual dispatch | Terraform plan + apply for staging environment |
| `ansible-lint.yml` | PR/push touching `ansible/`, manual | ansible-lint, yamllint, syntax check |
| `terraform-validate.yml` | PR/push touching `terraform/`, manual | fmt check, validate (prod/staging/github), tfsec, tflint |
| `shellcheck.yml` | PR/push touching `scripts/`, manual | ShellCheck + bash syntax check |
| `markdown-lint.yml` | PR/push touching `*.md`, manual | markdownlint + link check |

## Secrets Required

| Secret | Used by | Description |
|--------|---------|-------------|
| `PROD_SSH_PRIVATE_KEY` | deploy-prod, backup-legacy, capture-prod-inventory, do-nightly-snapshot | ED25519 private key authorized on `ftp.pausatf.org` as `root` |
| `DEV_SSH_PRIVATE_KEY` | deploy-dev | Private key for dev host |
| `ANSIBLE_VAULT_PASSWORD` | deploy-prod, deploy-staging, deploy-dev, capture-prod-inventory | Ansible vault decryption password |
| `DO_TOKEN` | infra-staging, do-nightly-snapshot | DigitalOcean API token |
| `DO_PROD_DROPLET_ID` | do-nightly-snapshot | Numeric droplet ID for snapshot target |
| `SPACES_ACCESS_KEY_ID` | infra-staging | DO Spaces key for Terraform state backend |
| `SPACES_SECRET_ACCESS_KEY` | infra-staging | DO Spaces secret for Terraform state backend |
| `CLOUDFLARE_API_TOKEN` | infra-staging | Cloudflare API token for Terraform |

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
4. Request review from @thomasvincent
5. Merge after approval

### CI/CD

This monorepo uses path-based triggers to only run relevant workflows:

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
- Contact @thomasvincent
