# Terraform Wrapper Script

Safe execution wrapper for Terraform commands with built-in validation and safety checks.

## Overview

`terraform-wrapper.sh` provides a secure interface for running Terraform operations against PAUSATF infrastructure. It includes environment validation, dependency checking, and safety delays for destructive operations.

## Features

- **Environment Validation**: Ensures only valid environments (production, staging) are targeted
- **Action Validation**: Restricts to safe, approved Terraform actions
- **Dependency Checking**: Verifies required tools (terraform, jq) are installed
- **Environment Variable Checks**: Warns if required API tokens are missing
- **Plan File Management**: Automatically creates and applies plan files for safety
- **Safety Delays**: Enforces waiting periods before destructive operations
- **Colored Output**: Clear visual feedback for different operation types

## Usage

```bash
./terraform-wrapper.sh <environment> <action>
```

### Arguments

| Argument | Description | Valid Values |
|----------|-------------|--------------|
| `environment` | Target environment | `production`, `staging` |
| `action` | Terraform action to perform | `plan`, `apply`, `destroy`, `output`, `init`, `validate` |

### Examples

```bash
# Initialize Terraform for production
./terraform-wrapper.sh production init

# Validate Terraform configuration for staging
./terraform-wrapper.sh staging validate

# Plan changes for production (creates tfplan file)
./terraform-wrapper.sh production plan

# Apply planned changes to staging
./terraform-wrapper.sh staging apply

# View outputs from production
./terraform-wrapper.sh production output

# Destroy staging environment (10-second safety delay)
./terraform-wrapper.sh staging destroy
```

## Actions

### init

Initializes Terraform backend and downloads required providers.

```bash
./terraform-wrapper.sh production init
```

- No safety delays
- Downloads providers and modules
- Configures remote state backend

### validate

Validates Terraform configuration syntax and logic.

```bash
./terraform-wrapper.sh production validate
```

- No modifications to infrastructure
- Checks for syntax errors
- Validates resource dependencies

### plan

Creates an execution plan showing what Terraform will do.

```bash
./terraform-wrapper.sh production plan
```

- Creates `tfplan` file in environment directory
- Shows resource additions, changes, and deletions
- No modifications to infrastructure
- Required before applying changes

### apply

Applies the changes from a plan file to infrastructure.

```bash
./terraform-wrapper.sh production apply
```

- Uses existing `tfplan` file (creates one if missing)
- 5-second safety delay before execution
- Modifies infrastructure
- Removes `tfplan` file after successful application

### output

Displays Terraform output values.

```bash
./terraform-wrapper.sh production output
```

- Shows configured output values
- No modifications to infrastructure
- Useful for retrieving IPs, URLs, etc.

### destroy

Destroys all Terraform-managed infrastructure.

```bash
./terraform-wrapper.sh production destroy
```

- **DESTRUCTIVE OPERATION**
- 10-second safety delay with warning
- Removes all resources defined in Terraform
- Cannot be undone

## Prerequisites

### Required Tools

The script checks for these dependencies:

- **terraform**: HashiCorp Terraform CLI
- **jq**: JSON processor for parsing Terraform output

Install on macOS:
```bash
brew install terraform jq
```

Install on Ubuntu/Debian:
```bash
# Terraform
wget -O- https://apt.releases.hashicorp.com/gpg | sudo gpg --dearmor -o /usr/share/keyrings/hashicorp-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/hashicorp-archive-keyring.gpg] https://apt.releases.hashicorp.com $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/hashicorp.list
sudo apt update && sudo apt install terraform

# jq
sudo apt install jq
```

### Required Environment Variables

The script expects these environment variables to be set:

```bash
export DIGITALOCEAN_ACCESS_TOKEN="your-digitalocean-token"
export CLOUDFLARE_API_TOKEN="your-cloudflare-token"
```

**Note**: The script will warn if these are not set but will continue execution.

### Recommended: Use with direnv

Create `.envrc` in the repository root:

```bash
# .envrc
export DIGITALOCEAN_ACCESS_TOKEN="dop_v1_xxxxxxxxxxxxx"
export CLOUDFLARE_API_TOKEN="xxxxxxxxxxxxx"
```

Then:
```bash
direnv allow
```

## Directory Structure

The script expects Terraform configurations in this structure:

```
pausatf-terraform/
├── terraform-wrapper.sh
└── environments/
    ├── production/
    │   ├── main.tf
    │   ├── variables.tf
    │   └── terraform.tfvars
    └── staging/
        ├── main.tf
        ├── variables.tf
        └── terraform.tfvars
```

## Safety Features

### Plan File Enforcement

When running `apply`, the script:
1. Checks for existing `tfplan` file
2. If missing, automatically runs `plan` first
3. Applies only from the plan file
4. Removes plan file after successful application

This prevents accidental application of unreviewed changes.

### Destructive Operation Delays

| Action | Delay | Warning Color |
|--------|-------|---------------|
| `apply` | 5 seconds | Yellow |
| `destroy` | 10 seconds | Red |

Press `Ctrl+C` during the delay to cancel.

### Environment Validation

The script blocks execution if:
- Environment is not `production` or `staging`
- Action is not in the approved list
- Required dependencies are missing

## Error Handling

The script uses `set -euo pipefail` for strict error handling:
- `-e`: Exit on any error
- `-u`: Exit on undefined variables
- `-o pipefail`: Fail on pipe errors

## Output

### Successful Plan

```
==> Running Terraform plan for production environment
==> Working directory: /path/to/terraform/environments/production
[Terraform plan output]
Plan saved to tfplan. Review carefully before applying.
==> Terraform plan completed successfully
```

### Successful Apply

```
==> Running Terraform apply for production environment
==> Working directory: /path/to/terraform/environments/production
About to apply changes to production. Press Ctrl+C to cancel.
[5-second delay]
[Terraform apply output]
==> Terraform apply completed successfully
```

### Missing Dependency Error

```
Error: Required dependency 'terraform' not found
```

### Invalid Environment Error

```
Error: Invalid environment. Must be 'production' or 'staging'
```

## Integration with IaC Ecosystem

This script is part of the PAUSATF Infrastructure as Code ecosystem:

- **Terraform Configurations**: [pausatf-terraform](https://github.com/pausatf/pausatf-terraform)
- **Ansible Playbooks**: [pausatf-ansible](https://github.com/pausatf/pausatf-ansible)
- **Infrastructure Docs**: [pausatf-infrastructure-docs](https://github.com/pausatf/pausatf-infrastructure-docs)

## Workflow Example

Typical workflow for infrastructure changes:

```bash
# 1. Make changes to Terraform configuration
vim environments/production/main.tf

# 2. Validate syntax
./terraform-wrapper.sh production validate

# 3. Review planned changes
./terraform-wrapper.sh production plan

# 4. Review the plan output carefully
# (Check tfplan file if needed)

# 5. Apply changes
./terraform-wrapper.sh production apply

# 6. Verify with outputs
./terraform-wrapper.sh production output
```

## Troubleshooting

### "No plan file found"

When running `apply`:
```bash
No plan file found. Running plan first...
```

**Solution**: The script automatically creates a plan. Review it before it applies.

### "Environment variable not set"

```bash
Warning: Environment variable DIGITALOCEAN_ACCESS_TOKEN is not set
```

**Solution**: Export required environment variables before running:
```bash
export DIGITALOCEAN_ACCESS_TOKEN="your-token"
export CLOUDFLARE_API_TOKEN="your-token"
```

### "Command not found: terraform"

**Solution**: Install Terraform (see Prerequisites section).

### State Lock Errors

If Terraform state is locked:
```bash
Error: Error acquiring the state lock
```

**Solution**:
1. Check if another Terraform operation is running
2. If stale lock, force unlock: `cd environments/production && terraform force-unlock <lock-id>`

## Security Considerations

### State Files

Terraform state files contain sensitive information:
- Resource IDs
- IP addresses
- Sometimes passwords or tokens

**Best Practices**:
- Use remote state backend (S3, Terraform Cloud)
- Never commit `terraform.tfstate` to Git
- Encrypt state files at rest
- Limit access to state files

### API Tokens

The script uses sensitive API tokens:
- Store in environment variables, never in code
- Use `.envrc` with direnv (gitignored)
- Rotate tokens regularly
- Use minimal required permissions

### Plan Files

Plan files (`tfplan`) can contain sensitive data:
- Automatically removed after apply
- Gitignored by default
- Should not be shared publicly

## Related Documentation

- [Terraform Documentation](https://www.terraform.io/docs)
- [DigitalOcean Terraform Provider](https://registry.terraform.io/providers/digitalocean/digitalocean/latest/docs)
- [Cloudflare Terraform Provider](https://registry.terraform.io/providers/cloudflare/cloudflare/latest/docs)
- [pausatf-terraform Repository](https://github.com/pausatf/pausatf-terraform)

## Maintenance

### Updating the Script

To update the wrapper script:

```bash
cd ~/pausatf-scripts
# Edit the script
vim terraform-wrapper.sh
# Test changes
./terraform-wrapper.sh staging validate
# Commit if working
git add terraform-wrapper.sh
git commit -m "Update terraform wrapper"
git push
```

### Adding New Actions

To add a new Terraform action:

1. Update `validate_action()` regex to include new action
2. Add case in `run_terraform()` function
3. Document the new action in this file
4. Test thoroughly in staging first

### Adding New Environments

To add a new environment (e.g., `development`):

1. Update `validate_environment()` regex
2. Create `terraform/environments/development/` directory
3. Add Terraform configuration files
4. Test with `./terraform-wrapper.sh development plan`

## Support

For issues or questions:
- [Open an issue](https://github.com/pausatf/pausatf-infrastructure-docs/issues)
- [Start a discussion](https://github.com/pausatf/pausatf-infrastructure-docs/discussions)
- Contact: @thomasvincent
