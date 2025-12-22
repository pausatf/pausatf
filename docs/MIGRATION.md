# Monorepo Migration Guide

This document provides information about the pausatf organization's migration to a monorepo structure.

## Migration Overview

**Migration Date**: December 21, 2025
**Migration Method**: Git subtree with full commit history preservation

On December 21, 2025, all pausatf organization repositories were consolidated into a
single monorepo to simplify management, improve CI/CD efficiency, and provide a unified
codebase structure.

## What Changed

### Before: Multiple Repositories

Previously, the pausatf infrastructure was split across 7 separate repositories:

- `pausatf-terraform` - Infrastructure as Code
- `pausatf-ansible` - Configuration management
- `pausatf-scripts` - Automation scripts
- `pausatf-infrastructure-docs` - Infrastructure documentation
- `pausatf-legacy-content` - Legacy content archive
- `pausatf-theme-thesource` - WordPress parent theme
- `pausatf-theme-thesource-child` - WordPress child theme

### After: Single Monorepo

All repositories have been consolidated into the `pausatf` monorepo with the following
structure:

```text
pausatf/
├── terraform/              # Infrastructure as Code (formerly pausatf-terraform)
├── ansible/                # Configuration management (formerly pausatf-ansible)
├── scripts/                # Automation scripts (formerly pausatf-scripts)
├── docs/                   # Infrastructure docs (formerly pausatf-infrastructure-docs)
├── content/                # Legacy content (formerly pausatf-legacy-content)
├── themes/
│   ├── thesource/         # WordPress parent theme (formerly pausatf-theme-thesource)
│   └── thesource-child/   # WordPress child theme (formerly pausatf-theme-thesource-child)
├── .github/workflows/      # Consolidated CI/CD workflows
├── .gitignore             # Monorepo-wide gitignore
├── .pre-commit-config.yaml # Monorepo-wide pre-commit hooks
└── README.md              # Monorepo documentation
```

## For Team Members

### Updating Your Local Environment

If you had any of the old repositories cloned locally:

1. **Delete old repository clones** (optional - keep for reference if needed):

   ```bash
   rm -rf pausatf-terraform
   rm -rf pausatf-ansible
   rm -rf pausatf-scripts
   rm -rf pausatf-infrastructure-docs
   rm -rf pausatf-legacy-content
   rm -rf pausatf-theme-thesource
   rm -rf pausatf-theme-thesource-child
   ```

2. **Clone the new monorepo**:

   ```bash
   git clone git@github.com:pausatf/pausatf.git
   cd pausatf
   ```

3. **Install pre-commit hooks** (recommended):

   ```bash
   # Install pre-commit (if not already installed)
   brew install pre-commit  # macOS
   # OR
   pip install pre-commit   # Linux/other

   # Install hooks in the repository
   pre-commit install
   ```

### Working with the Monorepo

#### Making Changes to Specific Components

Even though everything is in one repository, you can still work on individual components:

```bash
# Work on Terraform
cd terraform/
# Make changes, test locally
terraform fmt
terraform validate

# Work on Ansible
cd ansible/
# Make changes, test playbooks
ansible-playbook --syntax-check playbooks/your-playbook.yml

# Work on scripts
cd scripts/
# Make changes to scripts
shellcheck your-script.sh
```

#### CI/CD Workflows

The monorepo uses **path-based triggers** to only run relevant CI/CD workflows:

- Changes to `terraform/**` → Runs Terraform validation workflow
- Changes to `ansible/**` → Runs Ansible linting workflow
- Changes to `scripts/**` → Runs ShellCheck workflow
- Changes to `**/*.md` → Runs Markdown linting workflow

This means you won't trigger unnecessary workflows when working on specific components.

#### Branching Strategy

Continue using the same branching strategy:

- `main` - Production-ready code
- `feature/*` - New features
- `fix/*` - Bug fixes
- `docs/*` - Documentation updates

#### Commit Messages

Follow the same commit message conventions. The monorepo supports conventional commits:

```text
feat(terraform): add new DigitalOcean droplet configuration
fix(ansible): resolve WordPress deployment issue
docs(migration): update team documentation
chore(deps): bump Terraform providers
```

### Finding Historical Changes

All git history has been preserved! You can still find commits from the original repositories:

```bash
# View history for a specific component
git log terraform/
git log ansible/

# Search for specific changes
git log --all --grep="your search term"

# View file history
git log --follow terraform/main.tf
```

### Pre-commit Hooks

The monorepo includes comprehensive pre-commit hooks:

- **General**: Large file checks, merge conflict detection, YAML validation
- **Security**: Secret detection (detect-secrets, gitleaks)
- **Terraform**: Formatting, validation, TFLint, TFSec, Checkov
- **Ansible**: ansible-lint, yamllint, Checkov
- **Scripts**: ShellCheck for bash scripts
- **Markdown**: Markdown linting

Run hooks manually:

```bash
# Run on all files
pre-commit run --all-files

# Run on staged files only
pre-commit run

# Run specific hook
pre-commit run terraform_fmt
```

## Migration Benefits

### For Developers

- **Single clone**: Only one repository to clone and manage
- **Unified CI/CD**: Consistent workflows across all components
- **Easier code sharing**: Share code between components without complex Git submodules
- **Atomic changes**: Make changes across multiple components in a single commit/PR
- **Simplified dependency management**: Manage all dependencies in one place

### For Operations

- **Simplified access control**: Manage permissions once for all infrastructure code
- **Better traceability**: See all infrastructure changes in one timeline
- **Easier backup**: Single repository to backup and protect
- **Reduced overhead**: One set of CI/CD workflows to maintain

### For the Project

- **Improved collaboration**: Easier to see how components interact
- **Better discoverability**: All code in one place makes it easier to find
- **Consistent tooling**: One set of linting, formatting, and security tools
- **Reduced complexity**: No need to manage dependencies between repos

## Frequently Asked Questions

### Q: What happened to the old repositories?

**A**: The old repositories have been deleted from GitHub. All code and history has been preserved in the monorepo.

### Q: Can I still see the commit history from the old repos?

**A**: Yes! All commit history was preserved using `git subtree`. Use `git log <component>/`
to view history for specific components.

### Q: Do I need to update any local scripts or automation?

**A**: Yes, if you have scripts that reference the old repository URLs, update them to point to `pausatf/pausatf` instead.

### Q: How do I know which workflows will run on my PR?

**A**: GitHub Actions will only run workflows that match the changed file paths.
Check the workflow files in `.github/workflows/` to see the path triggers.

### Q: Can I work on just one component without cloning everything?

**A**: While you need to clone the full monorepo, you can use Git sparse-checkout to only
pull specific directories if repo size becomes an issue. However, with the current size,
this shouldn't be necessary.

### Q: What if I want to create a PR that only affects one component?

**A**: Just make your changes in that component's directory. The CI/CD system will only
run relevant checks based on which files you modified.

## Support

If you have questions about the monorepo migration or need help updating your workflow:

- Open a GitHub issue in the pausatf/pausatf repository
- Contact @thomasvincent
- Check the main [README.md](../README.md) for general monorepo documentation

## Technical Details

### Migration Process

The migration used Git subtree to preserve full commit history:

```bash
# Example of how each repo was migrated
git remote add terraform https://github.com/pausatf/pausatf-terraform.git
git fetch terraform main
git subtree add --prefix=terraform terraform main
```

This approach ensures:

- All commits are preserved with original authors and timestamps
- File history can be traced back through the migration
- No git history is lost or rewritten

### Repository Statistics

- **Total commits migrated**: 100+
- **Components consolidated**: 7
- **Workflows created**: 4
- **Lines of code**: ~50,000+

---

**Last Updated**: December 21, 2025
**Maintained By**: @thomasvincent
