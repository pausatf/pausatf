# PAUSATF Infrastructure Monorepo

## Purpose

Central monorepo for PA-USATF (Pacific Association of USA Track & Field) infrastructure: Terraform IaC, Ansible configuration management, automation scripts, documentation, legacy content archive, and WordPress themes for pausatf.org.

## Stack

| Component | Technology |
|-----------|------------|
| IaC | Terraform ~1.6 (DigitalOcean, Cloudflare, GitHub providers) |
| Config Management | Ansible (roles-based, vault for secrets) |
| Web Platform | WordPress 6.8+ on Apache 2.4 / PHP 7.2+ / MySQL 5.7 |
| CDN/DNS | Cloudflare |
| Hosting | DigitalOcean (Ubuntu 20.04) |
| CI | GitHub Actions (path-based triggers) |
| Scripts | Bash (shellcheck clean) |

## Standards

- **Terraform**: Use `tofu validate` / `tflint` / `tfsec`. Pin provider versions with `~>`. Tag all resources. Follow Google Terraform style.
- **Ansible**: FQCN for all modules. Vault for secrets. `ansible-lint` clean. Explicit `changed_when`/`failed_when`.
- **Shell scripts**: `set -euo pipefail`. ShellCheck clean. Google Shell Style Guide.
- **WordPress themes**: WPCS via `phpcs`. Escape all output. Sanitize all input. Enqueue assets properly.
- **Commits**: Conventional Commits (`type(scope): description`). Scope by component: `terraform`, `ansible`, `scripts`, `docs`, `content`, `themes`.

## Build / Validate / Test

```bash
# Terraform
cd terraform && terraform fmt -check && terraform validate
tflint --recursive
tfsec .

# Ansible
ansible-lint ansible/
ansible-playbook -i ansible/inventory ansible/site.yml --check --diff

# Scripts
shellcheck scripts/**/*.sh

# Themes
cd themes/thesource-child && phpcs --standard=WordPress .
```

## Key Conventions

- Branching: `main` (production), `feature/*`, `fix/*`, `docs/*`
- Secrets never in code; use Ansible Vault or environment variables
- Legacy content in `content/` is read-only archival; new content goes to WordPress
- Each component has its own README with detailed instructions
