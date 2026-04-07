# Copilot Instructions for PAUSATF Monorepo

## Repository Overview

This is the PAUSATF infrastructure monorepo, consolidating all infrastructure, configuration, automation scripts, documentation, and legacy content for the Pacific Association USA Track & Field (PAUSATF) organization.

### Directory Structure

- **`/terraform`** – Infrastructure as Code for DigitalOcean, Cloudflare, and GitHub (uses Terraform ~1.10)
- **`/ansible`** – Configuration management playbooks and roles
- **`/scripts`** – Bash automation scripts for backup, deployment, and maintenance
- **`/docs`** – Documentation hub including guides, runbooks, and architecture docs
- **`/content`** – Legacy content archive (race results, images, PDFs)
- **`/themes`** – WordPress themes (TheSource and GeneratePress child themes)
- **`/teeters-php`** – Custom legacy PHP applications (e.g., results uploader)

## Coding Standards

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>
```

- Types: `feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `ci`
- Scopes: `terraform`, `ansible`, `scripts`, `docs`, `themes`, `ci`, `php`

### Terraform

- Format with `terraform fmt -recursive` before committing
- Validate with `terraform validate`
- Pass `tflint --recursive` and `tfsec .`
- Use descriptive variable names with `description` attributes
- Keep modules focused and reusable
- Use remote state (already configured in DigitalOcean Spaces)

### Ansible

- **Standard Role Structure**: Every role must have a `README.md` and `defaults/main.yml`.
- **Variable Naming**: Prefix role variables with the role name (e.g., `common_timezone`).
- **Testing**: Use Molecule for all custom roles.
- Lint playbooks with `ansible-lint`
- Run `yamllint .` for YAML formatting (120-char line length)
- Ensure playbooks are idempotent
- Use `ansible-vault` for sensitive data
- Test playbooks in staging before production

### PHP

- **Standards**: Follow WordPress Coding Standards (WPCS).
- **Tooling**: Use PHPCS with `phpcs.xml.dist` ruleset.
- **Testing**: Use PHPUnit for custom logic (see `teeters-php/tests`).
- Target PHP 8.1+ for local dev and 8.4 for modern environments.

### Shell Scripts

- All scripts must pass `shellcheck`
- Use `#!/usr/bin/env bash` as the shebang
- Start scripts with `set -euo pipefail`
- Add usage documentation in script headers
- Make scripts idempotent where possible

### Markdown

- Follow markdownlint rules (see `.markdownlint.json`)
- Keep line length to 120 characters (code blocks exempt)
- Verify all links are valid

## Security

- **Never commit secrets**, API keys, or credentials
- Use Ansible Vault for Ansible secrets
- Use `.tfvars` files (gitignored) for Terraform secrets
- Use GitHub Secrets for CI/CD secrets
- Pre-commit hooks include `detect-secrets`, `gitleaks`, and `detect-private-key`

## CI/CD Workflows

Path-based triggers run only relevant checks:

- **Terraform** – `terraform-validate.yml`: format, validate, TFSec, TFLint
- **Ansible** – `ansible-lint.yml`: ansible-lint, yamllint, syntax checks
- **Scripts** – `shellcheck.yml`: ShellCheck, bash syntax validation
- **Markdown** – `markdown-lint.yml`: markdownlint, link checking

All CI checks must pass before merging.

## Pull Request Guidelines

1. Branch from `main` using naming convention: `feature/*`, `fix/*`, `docs/*`, `chore/*`
2. Ensure pre-commit hooks pass locally (`pre-commit run --all-files`)
3. Test changes locally before pushing
4. All PRs require approval from @thomasvincent
5. Use "Squash and merge" or "Rebase and merge"

## WordPress Themes

- **Never modify the parent theme** (`themes/thesource/`)
- All customizations go in the child theme (`themes/thesource-child/`)
- Test in the staging environment before production
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
