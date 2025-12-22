# Contributing to PAUSATF Terraform Infrastructure

Thank you for contributing! Please follow these guidelines.

## Code of Conduct

- Be professional and respectful
- Focus on constructive feedback
- Follow security best practices

## Development Workflow

### 1. Create Feature Branch

```bash
git checkout main
git pull origin main
git checkout -b feature/your-feature-name
```

### 2. Make Changes

- Edit Terraform files
- Follow HCL style guide
- Run formatting: `terraform fmt -recursive`
- Run validation: `terraform validate`

### 3. Test Locally

```bash
cd environments/staging  # Test in staging first!
terraform init
terraform plan
```

### 4. Commit Changes

Pre-commit hooks will run automatically:
- Terraform formatting check
- Terraform validation
- Security scanning (tfsec)
- Secret detection (gitleaks)
- Linting (tflint)

```bash
git add .
git commit -m "feat: add staging database configuration"
```

### 5. Push and Create PR

```bash
git push origin feature/your-feature-name
# Create PR on GitHub
```

### 6. PR Requirements

- ✅ All CI checks passing
- ✅ 1 approval required
- ✅ Plan output reviewed
- ✅ No security issues
- ✅ Documentation updated

## Commit Message Format

Follow Conventional Commits:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `refactor`: Code refactoring
- `test`: Testing
- `chore`: Maintenance

**Examples:**
```
feat(droplet): add production web server
fix(database): correct MySQL version constraint
docs(readme): update deployment instructions
refactor(networking): simplify VPC configuration
```

## Code Standards

### Terraform Style

```hcl
# Good
resource "digitalocean_droplet" "web_server" {
  name   = var.droplet_name
  region = var.region
  size   = var.droplet_size
  image  = var.droplet_image

  tags = [
    "web",
    "production",
  ]
}

# Bad - inconsistent formatting
resource "digitalocean_droplet" "web_server" {
  name = var.droplet_name
  region=var.region
  size= var.droplet_size
  image =var.droplet_image
  tags=["web","production"]
}
```

### Variable Naming

```hcl
# Good
variable "droplet_count" {}
variable "db_cluster_size" {}
variable "environment_name" {}

# Bad
variable "DropletCount" {}  # Use snake_case
variable "db" {}            # Too vague
variable "x" {}             # Meaningless
```

### Documentation

All variables and outputs must be documented:

```hcl
variable "droplet_size" {
  description = "The size of the droplet (e.g., s-1vcpu-1gb)"
  type        = string
  default     = "s-2vcpu-4gb"
}

output "droplet_ip" {
  description = "The public IP address of the droplet"
  value       = digitalocean_droplet.main.ipv4_address
}
```

## Security Requirements

### Never Commit Secrets

❌ **Bad:**
```hcl
variable "do_token" {
  default = "dop_v1_abcdef123456..."  # NEVER DO THIS!
}
```

✅ **Good:**
```hcl
variable "do_token" {
  description = "DigitalOcean API token"
  type        = string
  sensitive   = true
  # No default value - must be provided via env var or tfvars
}
```

### Use Sensitive Flag

```hcl
variable "database_password" {
  description = "MySQL root password"
  type        = string
  sensitive   = true
}

output "db_connection_string" {
  description = "Database connection string"
  value       = "mysql://..."
  sensitive   = true
}
```

### Encryption

All data must be encrypted:
```hcl
resource "digitalocean_database_cluster" "main" {
  # ...

  # Enable encryption
  private_network_uuid = digitalocean_vpc.main.id

  tags = ["encrypted"]
}
```

## Testing

### Staging First

Always test in staging before production:

```bash
# 1. Apply to staging
cd environments/staging
terraform apply

# 2. Verify staging works
# Test website, database, etc.

# 3. Apply to production
cd ../production
terraform apply
```

### Validation Checks

Run before every commit:

```bash
# Format
terraform fmt -recursive -check

# Validate
terraform validate

# Security scan
tfsec .

# Compliance scan
checkov -d .

# Lint
tflint --recursive
```

## Module Development

### Module Structure

```
modules/droplet/
├── main.tf          # Resources
├── variables.tf     # Input variables
├── outputs.tf       # Output values
├── versions.tf      # Provider versions
├── README.md        # Documentation
└── examples/        # Usage examples
    └── basic/
        └── main.tf
```

### Module Documentation

Use terraform-docs format:

```markdown
## Requirements

| Name | Version |
|------|---------|
| terraform | >= 1.0 |
| digitalocean | ~> 2.0 |

## Inputs

| Name | Description | Type | Default | Required |
|------|-------------|------|---------|:--------:|
| name | Droplet name | `string` | n/a | yes |

## Outputs

| Name | Description |
|------|-------------|
| id | Droplet ID |
```

## PR Review Checklist

Reviewers should verify:

- [ ] Code follows style guide
- [ ] All variables documented
- [ ] Sensitive data properly marked
- [ ] No hardcoded secrets
- [ ] Tests pass in staging
- [ ] tfsec scan clean
- [ ] terraform validate passes
- [ ] Plan output makes sense
- [ ] No unexpected resource destruction
- [ ] Documentation updated

## Questions?

- **Documentation:** https://github.com/pausatf/pausatf-infrastructure-docs
- **Issues:** https://github.com/pausatf/pausatf-terraform/issues
- **Maintainer:** Thomas Vincent
