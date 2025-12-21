# Contributing to Infrastructure as Code

Thank you for contributing to our infrastructure repository! This document provides guidelines and best practices for contributing.

## Code of Conduct

- Be respectful and professional
- Collaborate constructively
- Focus on what's best for the infrastructure
- Welcome newcomers and help them learn

## Getting Started

### Prerequisites

- Git
- Terraform >= 1.6.0
- Ansible >= 2.15.0
- doctl (DigitalOcean CLI)
- Pre-commit hooks installed

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/thomasvincent/infrastructure-as-code.git
   cd infrastructure-as-code
   ```

2. **Install pre-commit hooks**
   ```bash
   pip install pre-commit
   pre-commit install
   ```

3. **Set up credentials**
   ```bash
   export DIGITALOCEAN_ACCESS_TOKEN="your-token"
   export CLOUDFLARE_API_TOKEN="your-token"
   ```

## Development Workflow

### 1. Create a Feature Branch

```bash
git checkout -b feature/description
# or
git checkout -b fix/description
```

### 2. Make Your Changes

- Follow the coding standards below
- Test your changes thoroughly
- Update documentation as needed

### 3. Commit Your Changes

We use [Conventional Commits](https://www.conventionalcommits.org/):

```bash
git commit -m "feat: add new module for X"
git commit -m "fix: correct firewall rule"
git commit -m "docs: update README"
git commit -m "refactor: simplify terraform module"
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks
- `ci`: CI/CD changes

### 4. Push and Create Pull Request

```bash
git push origin feature/description
```

Then create a Pull Request on GitHub.

## Coding Standards

### Terraform

- Use descriptive resource names
- Follow snake_case naming convention
- Add descriptions to all variables
- Mark sensitive outputs as sensitive
- Use modules for reusability
- Keep environments DRY

Example:
```hcl
variable "droplet_size" {
  description = "Size of the droplet to create"
  type        = string
  default     = "s-1vcpu-1gb"

  validation {
    condition     = can(regex("^(s|c|g|m|so)-", var.droplet_size))
    error_message = "Size must be a valid DigitalOcean droplet size."
  }
}
```

### Ansible

- Use YAML syntax
- Follow role-based organization
- Use meaningful variable names
- Add comments for complex tasks
- Use handlers for service restarts
- Implement idempotency

Example:
```yaml
---
- name: Install and configure Nginx
  apt:
    name: nginx
    state: present
  notify: restart nginx

- name: Configure Nginx site
  template:
    src: site.conf.j2
    dest: /etc/nginx/sites-available/site
    mode: '0644'
  notify: reload nginx
```

### Documentation

- Update README.md for significant changes
- Add runbooks for new procedures
- Document all variables and outputs
- Include examples in documentation
- Keep CHANGELOG.md updated

## Testing

### Terraform

```bash
# Format code
terraform fmt -recursive

# Validate syntax
terraform validate

# Plan changes
terraform plan

# Run tflint
tflint --config .tflint.hcl
```

### Ansible

```bash
# Syntax check
ansible-playbook --syntax-check playbooks/site.yml

# Dry run
ansible-playbook --check playbooks/site.yml

# Lint
ansible-lint playbooks/ roles/
```

### Pre-commit Hooks

Pre-commit hooks run automatically before each commit:

```bash
# Run manually
pre-commit run --all-files
```

## Pull Request Process

1. **Create descriptive PR**
   - Clear title using conventional commit format
   - Detailed description of changes
   - Link to related issues

2. **Ensure all checks pass**
   - Pre-commit hooks
   - Terraform plan
   - Ansible lint
   - GitHub Actions workflows

3. **Request review**
   - At least one approval required
   - Address review feedback promptly

4. **Merge**
   - Squash commits for clean history
   - Delete branch after merge

## Infrastructure Changes

### High-Risk Changes

Changes affecting these require extra caution:
- Production droplets
- Database clusters
- DNS records
- Firewall rules
- SSL certificates

**Process:**
1. Test in staging first
2. Create detailed plan
3. Schedule maintenance window
4. Get approval from infrastructure lead
5. Have rollback plan ready

### Low-Risk Changes

Changes like these can proceed normally:
- Documentation updates
- Non-production resources
- Monitoring configurations
- CI/CD improvements

## Emergency Procedures

### Hotfix Process

For critical production issues:

```bash
# 1. Create hotfix branch from main
git checkout -b hotfix/critical-issue main

# 2. Make minimal necessary changes

# 3. Test in staging if possible

# 4. Deploy to production

# 5. Create PR and merge immediately after
```

### Rollback Process

If deployment fails:

```bash
# Terraform
cd terraform/environments/production
terraform apply -target=module.RESOURCE_NAME

# Ansible
cd ansible
git checkout HEAD~1
ansible-playbook -i inventory/hosts.yml playbooks/site.yml
```

## Security

### Secrets Management

- **NEVER** commit secrets to Git
- Use environment variables
- Use Ansible Vault for Ansible secrets
- Store sensitive data in GitHub Secrets
- Use Terraform Cloud for state encryption

### Security Checklist

- [ ] No secrets in code
- [ ] Sensitive outputs marked as sensitive
- [ ] Pre-commit hooks detect secrets
- [ ] Firewall rules follow least-privilege
- [ ] SSL/TLS properly configured
- [ ] Regular dependency updates

## Resources

- [Terraform Best Practices](https://www.terraform-best-practices.com/)
- [Ansible Best Practices](https://docs.ansible.com/ansible/latest/user_guide/playbooks_best_practices.html)
- [GitHub Flow](https://guides.github.com/introduction/flow/)
- [Conventional Commits](https://www.conventionalcommits.org/)

## Questions?

- Create a GitHub issue
- Check existing documentation
- Ask in team chat
- Contact infrastructure lead

## License

By contributing, you agree that your contributions will be licensed under the same license as this project.
