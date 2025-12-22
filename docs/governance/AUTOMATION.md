# Automation & Dependency Management

This document describes the automated dependency management, security scanning, and code quality tools configured in this repository.

## Overview

The repository uses multiple automation tools to maintain code quality, security, and keep dependencies up-to-date:

- **Dependabot**: Automated dependency updates
- **Pre-commit hooks**: Local code quality and security checks
- **GitHub Actions**: CI/CD workflows for validation and security scanning

## Dependabot

### What is Dependabot?

Dependabot automatically creates pull requests to update dependencies when new versions are available. It monitors:

- GitHub Actions workflows
- Terraform providers and modules
- Docker base images
- Python packages

### Update Schedule

| Ecosystem | Day | Time | Description |
|-----------|-----|------|-------------|
| GitHub Actions | Monday | 09:00 UTC | Workflow dependencies |
| Terraform | Tuesday | 09:00 UTC | Providers and modules |
| Docker | Wednesday | 09:00 UTC | Base images |
| Python | Thursday | 09:00 UTC | pip packages |

### Configuration Location

`.github/dependabot.yml`

### How Updates Are Handled

#### Automatic Merging (Patch & Minor Updates)

For `semver-patch` and `semver-minor` updates:
1. Dependabot creates PR
2. CI checks run automatically
3. PR is auto-approved if checks pass
4. PR is auto-merged via squash merge
5. No manual intervention needed

**Example**: `terraform ~> 1.6.0` → `terraform ~> 1.6.1` ✅ Auto-merged

#### Manual Review (Major Updates)

For `semver-major` updates:
1. Dependabot creates PR
2. Bot adds warning comment about breaking changes
3. **Manual review required**
4. Check changelog for breaking changes
5. Test in staging if needed
6. Approve and merge manually

**Example**: `terraform ~> 1.6.0` → `terraform ~> 2.0.0` ⚠️ Manual review

### Monitoring Dependabot

View Dependabot PRs and activity:
- GitHub → Repository → Pull Requests → Filter by `dependabot`
- GitHub → Insights → Dependency graph → Dependabot

### Configuring Dependabot

To modify update frequency or policies:

```yaml
# .github/dependabot.yml
- package-ecosystem: "terraform"
  directory: "/terraform"
  schedule:
    interval: "weekly"  # Can be: daily, weekly, monthly
    day: "tuesday"      # Day of week
    time: "09:00"       # Time in UTC
```

## Pre-commit Hooks

### What Are Pre-commit Hooks?

Pre-commit hooks run automated checks **before** you commit code. They catch issues early in the development cycle.

### Installation

```bash
# Install pre-commit
pip install pre-commit

# Install git hooks
pre-commit install
pre-commit install --hook-type commit-msg
```

### What Gets Checked

#### 1. File Quality Checks
- ✅ YAML/JSON/TOML syntax validation
- ✅ Trailing whitespace removal
- ✅ End-of-file fixes
- ✅ Large file detection (>1MB blocked)
- ✅ Merge conflict detection
- ✅ Case conflict detection

#### 2. Terraform
- ✅ **Format**: `terraform fmt` (auto-fixes)
- ✅ **Validate**: `terraform validate`
- ✅ **Lint**: `tflint` (best practices)
- ✅ **Security**: `tfsec` (security issues)
- ✅ **Policy**: `checkov` (compliance & security)
- ✅ **Documentation**: Auto-generate README for modules

#### 3. Ansible
- ✅ **Lint**: `ansible-lint` (best practices)
- ✅ **Syntax**: YAML validation

#### 4. Docker
- ✅ **Lint**: `hadolint` (Dockerfile best practices)

#### 5. Shell Scripts
- ✅ **Lint**: `shellcheck` (bash linting)

#### 6. Python
- ✅ **Format**: `black` (auto-format)
- ✅ **Lint**: `flake8` (style guide)
- ✅ **Imports**: `isort` (organize imports)

#### 7. Markdown
- ✅ **Lint**: `markdownlint` (formatting)

#### 8. Security
- ✅ **Secret Detection**: `gitleaks` (API keys, passwords)
- ✅ **Secret Detection**: `detect-secrets` (additional patterns)
- ✅ **Private Keys**: Detect SSH/GPG keys

#### 9. Git Standards
- ✅ **Conventional Commits**: Enforce commit message format

### Running Pre-commit Hooks

#### Automatic (Before Every Commit)

Hooks run automatically when you commit:

```bash
git add .
git commit -m "feat: add new feature"
# Pre-commit hooks run automatically
```

#### Manual Execution

Run all hooks manually:

```bash
# Run on all files
pre-commit run --all-files

# Run specific hook
pre-commit run terraform_fmt --all-files

# Run on staged files only
pre-commit run
```

#### Skip Hooks (Emergency Only)

```bash
# Skip hooks for one commit (NOT recommended)
git commit --no-verify -m "emergency hotfix"
```

### Understanding Hook Output

#### Success ✅
```
Terraform fmt................................Passed
Terraform validate...........................Passed
Check for added large files..................Passed
```

#### Failure ❌
```
Terraform fmt................................Failed
- hook id: terraform_fmt
- files were modified by this hook
```

When hooks fail:
1. Review the error message
2. Fix the issue (or let auto-fix handle it)
3. Stage the fixed files
4. Try committing again

### Configuration Files

- `.pre-commit-config.yaml` - Main configuration
- `.yamllint.yml` - YAML linting rules
- `.markdownlint.json` - Markdown linting rules
- `.secrets.baseline` - Secret detection baseline

### Updating Hooks

Hooks are automatically updated weekly via GitHub Actions.

Manual update:
```bash
pre-commit autoupdate
```

## GitHub Actions Workflows

### 1. Terraform Plan
**Trigger**: Pull requests affecting `terraform/`

**Actions**:
- Run `terraform fmt` check
- Run `terraform init`
- Run `terraform validate`
- Run `terraform plan`
- Comment plan output on PR

**Purpose**: Review infrastructure changes before merging

### 2. Terraform Apply
**Trigger**: Push to `main` or manual workflow dispatch

**Actions**:
- Run `terraform init`
- Run `terraform apply`
- Output results

**Purpose**: Deploy infrastructure changes

### 3. Ansible Lint
**Trigger**: Pull requests or pushes affecting `ansible/`

**Actions**:
- Install Ansible and ansible-lint
- Lint playbooks and roles
- Validate syntax
- Run check mode

**Purpose**: Ensure Ansible code quality

### 4. Security Scanning
**Trigger**: Push to main, PRs, weekly schedule (Sundays), manual

**Scans**:
1. **Secret Scanning**: Gitleaks
2. **Terraform Security**: tfsec, Checkov
3. **Docker Security**: Trivy
4. **Python Security**: Safety, Bandit
5. **Dependency Review**: GitHub's dependency review

**Results**: Uploaded to GitHub Security tab

### 5. Pre-commit Update
**Trigger**: Weekly (Mondays) or manual

**Actions**:
- Run `pre-commit autoupdate`
- Create PR with updates

**Purpose**: Keep hooks up-to-date

### 6. Dependabot Auto-merge
**Trigger**: Dependabot PRs

**Actions**:
- Extract PR metadata
- Auto-approve patch/minor updates
- Auto-merge if checks pass
- Comment on major updates

**Purpose**: Automate safe dependency updates

## Best Practices

### For Dependabot

✅ **DO**:
- Review major updates carefully
- Test major updates in staging first
- Check changelogs for breaking changes
- Keep auto-merge enabled for patch/minor

❌ **DON'T**:
- Ignore Dependabot PRs for months
- Skip CI checks on Dependabot PRs
- Merge major updates without testing
- Disable Dependabot completely

### For Pre-commit

✅ **DO**:
- Run `pre-commit run --all-files` after updating hooks
- Fix issues immediately when hooks fail
- Keep hooks configuration updated
- Use hooks on feature branches

❌ **DON'T**:
- Skip hooks with `--no-verify` regularly
- Commit broken code and fix in CI
- Ignore hook failures
- Disable hooks you don't understand

### For Security Scanning

✅ **DO**:
- Review security findings promptly
- Fix critical/high severity issues immediately
- Update dependencies with known vulnerabilities
- Monitor GitHub Security tab weekly

❌ **DON'T**:
- Ignore security alerts
- Disable security scanning
- Commit secrets (even in private repos)
- Skip security reviews on PRs

## Troubleshooting

### Dependabot Not Creating PRs

**Check**:
1. Is Dependabot enabled in repository settings?
2. Are there existing PRs? (Max limit may be reached)
3. Check Dependabot logs in Insights → Dependency graph

**Fix**:
- Merge or close existing Dependabot PRs
- Check `.github/dependabot.yml` syntax
- Increase `open-pull-requests-limit`

### Pre-commit Hooks Failing

**Common Issues**:

1. **Hook not found**
   ```bash
   pip install pre-commit
   pre-commit install
   ```

2. **terraform_fmt failing**
   ```bash
   terraform fmt -recursive
   git add .
   ```

3. **Secrets detected**
   - Remove the secret
   - If false positive, update `.secrets.baseline`

4. **Slow hooks**
   - Hooks run on changed files only by default
   - Use `pre-commit run --files <file>` for specific files

### CI/CD Failing

**Terraform Plan Failing**:
- Check Terraform syntax locally
- Ensure secrets are configured in GitHub
- Review plan output in PR comment

**Security Scan Failing**:
- Review findings in job logs
- Fix security issues
- Re-run workflow

## Maintenance

### Weekly Tasks
- ✅ Review Dependabot PRs (automated)
- ✅ Check GitHub Security tab for alerts
- ✅ Monitor workflow runs for failures

### Monthly Tasks
- ✅ Review `.pre-commit-config.yaml` for new hooks
- ✅ Update pre-commit hook versions
- ✅ Review and update `.secrets.baseline`

### Quarterly Tasks
- ✅ Review and update Dependabot configuration
- ✅ Review security scanning configuration
- ✅ Audit all automated workflows

## Resources

- [Dependabot Documentation](https://docs.github.com/en/code-security/dependabot)
- [Pre-commit Documentation](https://pre-commit.com/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Gitleaks](https://github.com/gitleaks/gitleaks)
- [tfsec](https://aquasecurity.github.io/tfsec/)
- [Checkov](https://www.checkov.io/)

## Support

For issues or questions:
1. Check this documentation
2. Review workflow logs in GitHub Actions
3. Create GitHub issue
4. Contact infrastructure team
