# PAUSATF Infrastructure Monorepo

Central repository for all PAUSATF infrastructure, configuration, automation scripts, documentation, and legacy content.

## Repository Structure

- **`/terraform`** - Infrastructure as Code for DigitalOcean, Cloudflare, and GitHub
- **`/ansible`** - Configuration management playbooks and roles
- **`/scripts`** - Automation scripts for backup, deployment, and maintenance
- **`/docs`** - Documentation hub including guides, runbooks, and architecture docs
- **`/content`** - Legacy content archive (race results, images, PDFs)

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

## Migration Notes

This monorepo was created by consolidating 5 separate repositories with full git history preservation:

- `pausatf-terraform` → `terraform/`
- `pausatf-ansible` → `ansible/`
- `pausatf-scripts` → `scripts/`
- `pausatf-infrastructure-docs` → `docs/`
- `pausatf-legacy-content` → `content/`

**Migration Date**: December 21, 2025

## Contributing

Please read our contributing guidelines and code of conduct before submitting pull requests.

## License

See individual component directories for licensing information.

## Support

For questions or issues, please open a GitHub issue or contact @thomasvincent.
