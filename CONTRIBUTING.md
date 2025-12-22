# Contributing to PAUSATF Monorepo

Thank you for your interest in contributing to the PAUSATF infrastructure! This guide
will help you understand how to work with our monorepo structure.

## Table of Contents

- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Code Standards](#code-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Component-Specific Guidelines](#component-specific-guidelines)

## Getting Started

### Prerequisites

Ensure you have the following tools installed:

- **Git** (2.30+)
- **GitHub CLI** (`gh`) - for repository operations
- **Terraform** (~1.6) - for infrastructure work
- **Ansible** (latest) - for configuration management
- **Python** (3.11+) - for Ansible and pre-commit
- **pre-commit** - for automated code quality checks

### Initial Setup

1. **Clone the repository**:

   ```bash
   git clone git@github.com:pausatf/pausatf.git
   cd pausatf
   ```

2. **Install pre-commit hooks**:

   ```bash
   # macOS
   brew install pre-commit

   # Linux/other
   pip install pre-commit

   # Install hooks in repo
   pre-commit install
   ```

3. **Install component-specific tools**:

   ```bash
   # Terraform
   brew install terraform tflint tfsec

   # Ansible
   pip install ansible ansible-lint yamllint

   # ShellCheck
   brew install shellcheck
   ```

## Development Workflow

### Branching Strategy

We use a simplified Git Flow:

- `main` - Production-ready code, protected branch
- `feature/<name>` - New features
- `fix/<name>` - Bug fixes
- `docs/<name>` - Documentation updates
- `chore/<name>` - Maintenance tasks

### Making Changes

1. **Create a feature branch**:

   ```bash
   git checkout -b feature/my-feature
   # or
   git checkout -b fix/bug-description
   ```

2. **Make your changes** in the relevant component directory:

   ```bash
   # Example: Working on Terraform
   cd terraform/
   # Make changes...
   ```

3. **Run pre-commit checks** (automatically runs on commit, or manually):

   ```bash
   pre-commit run --all-files
   ```

4. **Commit your changes**:

   ```bash
   git add .
   git commit -m "feat(terraform): add new feature"
   ```

5. **Push to GitHub**:

   ```bash
   git push origin feature/my-feature
   ```

6. **Create a Pull Request** via GitHub web interface or CLI:

   ```bash
   gh pr create --title "feat(terraform): add new feature" --body "Description of changes"
   ```

### Commit Message Format

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```text
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types**:

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `chore`: Maintenance tasks
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `ci`: CI/CD changes

**Scopes** (component names):

- `terraform`
- `ansible`
- `scripts`
- `docs`
- `themes`
- `ci`

**Examples**:

```bash
feat(terraform): add DigitalOcean firewall rules
fix(ansible): resolve WordPress deployment timeout
docs(migration): update team onboarding guide
chore(deps): bump Terraform AWS provider to 5.0
```

## Code Standards

### General Standards

- **No secrets in code**: Never commit credentials, API keys, or sensitive data
- **Documentation**: Update relevant README files when adding features
- **Testing**: Test changes locally before pushing
- **Code review**: All changes require review before merging

### Terraform Standards

- **Formatting**: Use `terraform fmt` to format code
- **Validation**: Run `terraform validate` before committing
- **Security**: Pass TFSec and Checkov scans
- **Documentation**: Use terraform-docs for module documentation
- **Variables**: Use descriptive variable names and add descriptions
- **Modules**: Keep modules focused and reusable

```bash
# Check before committing
terraform fmt -recursive
terraform validate
tflint --recursive
tfsec .
```

### Ansible Standards

- **Linting**: Use ansible-lint to check playbooks
- **YAML**: Follow yamllint rules (120 char line length)
- **Idempotency**: Ensure playbooks can run multiple times safely
- **Variables**: Use descriptive variable names
- **Roles**: Keep roles focused and reusable
- **Handlers**: Use handlers for service restarts

```bash
# Check before committing
ansible-lint playbooks/
yamllint .
ansible-playbook --syntax-check playbooks/your-playbook.yml
```

### Shell Script Standards

- **ShellCheck**: All scripts must pass ShellCheck
- **Shebang**: Use `#!/usr/bin/env bash` for portability
- **Error handling**: Use `set -euo pipefail` for robust scripts
- **Functions**: Break complex scripts into functions
- **Comments**: Document complex logic

```bash
# Check before committing
shellcheck your-script.sh
bash -n your-script.sh  # Syntax check
```

### Markdown Standards

- **Linting**: Follow markdownlint rules
- **Line length**: 120 characters max (code blocks exempt)
- **Links**: Ensure all links are valid
- **Headers**: Use proper heading hierarchy

```bash
# Check before committing
markdownlint .
```

## Testing

### Local Testing

Always test your changes locally before creating a PR:

#### Terraform

```bash
cd terraform/environments/staging
terraform init
terraform plan
# Review the plan carefully
```

#### Ansible

```bash
cd ansible
# Check syntax
ansible-playbook --syntax-check playbooks/your-playbook.yml

# Run in check mode (dry run)
ansible-playbook --check playbooks/your-playbook.yml

# Test on staging first
ansible-playbook -i inventory/staging playbooks/your-playbook.yml
```

#### Scripts

```bash
cd scripts
# Syntax check
bash -n your-script.sh

# Test with dry run if available
./your-script.sh --dry-run

# Test on non-production systems first
```

### CI/CD Testing

Our CI/CD workflows automatically test:

- **Terraform**: Format, validate, TFSec, TFLint
- **Ansible**: ansible-lint, yamllint, syntax checks
- **Scripts**: ShellCheck, bash syntax validation
- **Markdown**: markdownlint, link checking
- **Security**: Secret detection (detect-secrets, gitleaks)

All checks must pass before merging.

## Pull Request Process

### Before Creating a PR

1. Ensure all pre-commit hooks pass
2. Test your changes locally
3. Update relevant documentation
4. Write clear commit messages
5. Rebase on latest main if needed

### PR Requirements

1. **Title**: Use conventional commit format
2. **Description**: Clearly explain what and why
3. **Testing**: Describe how you tested the changes
4. **Screenshots**: Include if UI/visual changes
5. **Breaking changes**: Clearly document any breaking changes

### PR Template

```markdown
## Description
Brief description of changes

## Motivation
Why is this change needed?

## Changes Made
- List of changes
- Another change

## Testing
How were these changes tested?

## Checklist
- [ ] Pre-commit hooks pass
- [ ] Tested locally
- [ ] Documentation updated
- [ ] No secrets in code
```

### Review Process

1. Automated CI/CD checks must pass
2. At least one approval required from @thomasvincent
3. Address review comments
4. Squash commits if needed
5. Merge using "Squash and merge" or "Rebase and merge"

### After Merging

1. Delete your feature branch
2. Pull latest main locally
3. Verify changes in production if applicable

## Component-Specific Guidelines

### Terraform (`terraform/`)

- Follow HashiCorp best practices
- Use variables for configurable values
- Document all modules
- Use remote state (configured)
- Separate environments (production, staging, github)

See [terraform/README.md](terraform/README.md) for detailed guidelines.

### Ansible (`ansible/`)

- Follow Ansible best practices
- Use roles for reusability
- Encrypt sensitive data with ansible-vault
- Test playbooks in staging first
- Use tags for selective execution

See [ansible/README.md](ansible/README.md) for detailed guidelines.

### Scripts (`scripts/`)

- Make scripts executable
- Add usage documentation
- Include error handling
- Log important operations
- Make scripts idempotent when possible

See [scripts/README.md](scripts/README.md) for detailed guidelines.

### Documentation (`docs/`)

- Keep docs up-to-date
- Use clear, concise language
- Include examples
- Add diagrams where helpful
- Follow markdown standards

See [docs/README.md](docs/README.md) for detailed guidelines.

### WordPress Themes (`themes/`)

- Never modify parent theme
- All changes in child theme
- Test in staging environment
- Document customizations
- Follow WordPress coding standards

See [themes/README.md](themes/README.md) for detailed guidelines.

## Security

### Secrets Management

**Never commit secrets!** Use these approaches:

1. **Environment variables**: For local development
2. **Ansible Vault**: For encrypted secrets in Ansible
3. **Terraform variables**: Use `.tfvars` files (gitignored)
4. **GitHub Secrets**: For CI/CD secrets

### Pre-commit Security Checks

Our pre-commit hooks include:

- `detect-secrets`: Scans for common secret patterns
- `gitleaks`: Detects hardcoded secrets
- `detect-private-key`: Finds SSH/TLS private keys

If you accidentally commit a secret:

1. **DO NOT** just remove it in a new commit
2. Rotate/revoke the secret immediately
3. Contact @thomasvincent for history rewrite if needed

## Getting Help

- **Documentation**: Check component-specific READMEs
- **Issues**: Open a GitHub issue for bugs or questions
- **Discussions**: Use GitHub Discussions for general questions
- **Contact**: Reach out to @thomasvincent

## License

See [LICENSE](LICENSE) file for details.

---

**Thank you for contributing to PAUSATF!**
