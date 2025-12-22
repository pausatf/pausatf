# GitHub Repository Management

This Terraform configuration manages PAUSATF GitHub repositories and their settings.

## Prerequisites

1. **GitHub Personal Access Token**
   - Create a token at: https://github.com/settings/tokens
   - Required scopes:
     - `repo` (Full control of private repositories)
     - `admin:repo_hook` (Read/write access to repository hooks)
     - `delete_repo` (Delete repositories - optional)

2. **Terraform >= 1.0**
   - Install from: https://www.terraform.io/downloads

## Repositories Managed

This configuration manages the following repositories:

- **pausatf-infrastructure-docs**: Documentation, runbooks, operational guides
- **pausatf-terraform**: Infrastructure as Code (DigitalOcean, Cloudflare)
- **pausatf-ansible**: Configuration management (WordPress, Apache, MySQL, PHP)
- **pausatf-scripts**: Automation scripts (backups, monitoring, maintenance)

## Configuration

### Branch Protection

All repositories are configured with:
- ✅ Required GPG signed commits
- ✅ Dependabot security updates enabled
- ✅ Delete branch on merge
- ✅ Vulnerability alerts enabled

### Status Checks

- **Terraform repo**: terraform-validate, terraform-fmt, tfsec, tflint
- **Ansible repo**: ansible-lint, yamllint
- **Scripts repo**: shellcheck
- **Docs repo**: No required status checks

## Usage

### 1. Set GitHub Token

**Option A: Environment Variable (Recommended)**
```bash
export TF_VAR_github_token="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

**Option B: terraform.tfvars File**
```bash
cp terraform.tfvars.example terraform.tfvars
# Edit terraform.tfvars and add your token
```

### 2. Initialize Terraform

```bash
terraform init
```

### 3. Review Changes

```bash
terraform plan
```

### 4. Apply Configuration

```bash
terraform apply
```

## Important Notes

⚠️ **NEVER commit GitHub tokens to git!**
- Use environment variables or
- Add `terraform.tfvars` to `.gitignore` (already done)

⚠️ **Importing Existing Repositories**

If the repositories already exist, you need to import them first:

```bash
# Import existing repositories
terraform import module.pausatf_infrastructure_docs.github_repository.repo pausatf-infrastructure-docs
terraform import module.pausatf_terraform.github_repository.repo pausatf-terraform
terraform import module.pausatf_ansible.github_repository.repo pausatf-ansible
terraform import module.pausatf_scripts.github_repository.repo pausatf-scripts

# Import branch protections
terraform import 'module.pausatf_infrastructure_docs.github_branch_protection.main[0]' pausatf-infrastructure-docs:main
terraform import 'module.pausatf_terraform.github_branch_protection.main[0]' pausatf-terraform:main
terraform import 'module.pausatf_ansible.github_branch_protection.main[0]' pausatf-ansible:main
terraform import 'module.pausatf_scripts.github_branch_protection.main[0]' pausatf-scripts:main

# Import Dependabot
terraform import 'module.pausatf_infrastructure_docs.github_repository_dependabot_security_updates.repo[0]' pausatf-infrastructure-docs
terraform import 'module.pausatf_terraform.github_repository_dependabot_security_updates.repo[0]' pausatf-terraform
terraform import 'module.pausatf_ansible.github_repository_dependabot_security_updates.repo[0]' pausatf-ansible
terraform import 'module.pausatf_scripts.github_repository_dependabot_security_updates.repo[0]' pausatf-scripts
```

## Outputs

After applying, you'll see:
- Repository URLs for all managed repos
- Repository names

## Troubleshooting

### Token Permission Issues
If you get permission errors, verify your token has the required scopes.

### State File
The state file is stored in DigitalOcean Spaces at:
`s3://pausatf-terraform-state/github/terraform.tfstate`

## Security Best Practices

1. **Rotate tokens regularly** (at least every 90 days)
2. **Use fine-grained tokens** when possible
3. **Store tokens in a secret manager** for production use
4. **Enable audit logging** for token usage
5. **Use separate tokens** for different environments
