# GitHub Actions Workflows Documentation

**Version:** 1.0
**Last Updated:** 2025-12-21
**Repository:** pausatf-infrastructure-docs

---

## Overview

This repository uses GitHub Actions for automated validation, health monitoring, and quality assurance. All workflows are designed to run automatically with minimal manual intervention while providing comprehensive infrastructure oversight.

**Total Workflows:** 3

1. **Infrastructure Health Check** - Daily automated monitoring
2. **Documentation Validation** - Content quality and structure validation
3. **Commit Validation** - Enforce conventional commit standards

---

## Workflow Inventory

| Workflow | Trigger | Frequency | Purpose | Duration |
|----------|---------|-----------|---------|----------|
| Infrastructure Health Check | Schedule + Manual | Daily at 6:00 AM UTC | Monitor backups, DNS, SSL, resources | ~2 minutes |
| Documentation Validation | Push to main + PR | On change | Validate links, structure, secrets | ~1 minute |
| Commit Validation | Pull Request | On PR events | Enforce commit message standards | ~30 seconds |

---

## 1. Infrastructure Health Check

**File:** `.github/workflows/infrastructure-health.yml`

### Purpose

Automated daily monitoring of critical infrastructure components to ensure system health and compliance with operational standards.

### Trigger Schedule

```yaml
on:
  schedule:
    - cron: '0 6 * * *'  # Daily at 6:00 AM UTC (1:00 AM EST)
  workflow_dispatch:     # Manual trigger available
```

**When it runs:**
- Automatically every day at 6:00 AM UTC
- Manually via GitHub Actions UI or CLI
- Can be triggered on-demand for troubleshooting

### Jobs Overview

This workflow consists of 3 independent jobs that run in parallel:

#### Job 1: Verify DigitalOcean Backups

**Purpose:** Ensure both production and staging servers have recent backups

**What it checks:**
- ✅ Production server backup status (droplet 355909945)
- ✅ Staging server backup status (droplet 538411208)
- ✅ Backup availability and age
- ✅ Droplet health status

**Commands executed:**
```bash
doctl compute droplet get PROD_DROPLET_ID
doctl compute droplet backup list PROD_DROPLET_ID
doctl compute droplet get STAGING_DROPLET_ID
doctl compute droplet backup list STAGING_DROPLET_ID
doctl compute droplet list | grep pausatf
```

**Expected output:**
- List of recent backups (should show daily snapshots)
- Droplet status: "active"
- No errors or warnings

**Failure scenarios:**
- No backups found in last 48 hours
- Droplet status not "active"
- DigitalOcean API authentication failure
- Droplet not found

**Secrets required:**
- `DIGITALOCEAN_ACCESS_TOKEN` - Read-only DigitalOcean API token
- `PROD_DROPLET_ID` - Production droplet ID (355909945)
- `STAGING_DROPLET_ID` - Staging droplet ID (538411208)

---

#### Job 2: Verify Cloudflare Configuration

**Purpose:** Monitor Cloudflare zone status, DNS records, and SSL configuration

**What it checks:**
- ✅ Cloudflare zone status (active/paused)
- ✅ DNS record configuration
- ✅ SSL certificate status
- ✅ Proxy status for records

**API calls made:**
```bash
# Zone status
GET /zones/{zone_id}

# DNS records
GET /zones/{zone_id}/dns_records

# SSL settings
GET /zones/{zone_id}/settings/ssl
```

**Expected output:**
```json
{
  "name": "pausatf.org",
  "status": "active",
  "paused": false
}
```

**DNS records verified:**
- A records for apex domain
- CNAME records for www, prod, stage subdomains
- MX records for Google Workspace email
- TXT records for SPF, DKIM, DMARC
- CAA records for certificate authority authorization

**SSL status expected:**
- Mode: "full" or "strict"
- Status: "active"
- Certificates valid and not expired

**Failure scenarios:**
- Zone status: "paused" or "inactive"
- Missing critical DNS records
- SSL mode: "off" or "flexible"
- Certificate expiration warnings
- Cloudflare API authentication failure

**Secrets required:**
- `CLOUDFLARE_API_TOKEN` - Read-only Cloudflare API token
- `CLOUDFLARE_ZONE_ID` - Zone ID for pausatf.org (67b87131144a68ad5ed43ebfd4e6d811)

---

#### Job 3: Monitor Server Resources

**Purpose:** Track server resource allocation and cost monitoring

**What it checks:**
- ✅ Production server specifications (CPU, RAM, disk)
- ✅ Staging server specifications (CPU, RAM, disk)
- ✅ Server status (active/off/degraded)
- ✅ Monthly cost summary

**Commands executed:**
```bash
doctl compute droplet get PROD_DROPLET_ID \
  --format ID,Name,Memory,VCPUs,Disk,Status

doctl compute droplet get STAGING_DROPLET_ID \
  --format ID,Name,Memory,VCPUs,Disk,Status
```

**Expected output:**
```
Production Server:
ID: 355909945, Memory: 8192 MB, VCPUs: 4, Disk: 160 GB, Status: active

Staging Server:
ID: 538411208, Memory: 4096 MB, VCPUs: 2, Disk: 80 GB, Status: active
```

**Cost tracking:**
- Production: $48.00/month (droplet) + $9.60/month (backups) = $57.60/month
- Staging: $24.00/month (droplet) + $4.80/month (backups) = $28.80/month
- **Total: $86.40/month**

**Note:** See `12-server-rightsizing-analysis.md` for optimization recommendations ($460.80/year potential savings)

**Failure scenarios:**
- Droplet status not "active"
- Unexpected resource changes
- Droplet specifications don't match documentation

**Secrets required:**
- `DIGITALOCEAN_ACCESS_TOKEN`
- `PROD_DROPLET_ID`
- `STAGING_DROPLET_ID`

---

### Viewing Results

**Via GitHub UI:**
1. Go to repository Actions tab
2. Select "Infrastructure Health Check"
3. View most recent run

**Via GitHub CLI:**
```bash
# List recent runs
gh run list --workflow=infrastructure-health.yml

# View specific run
gh run view RUN_ID

# View run logs
gh run view RUN_ID --log

# Manually trigger workflow
gh workflow run infrastructure-health.yml
```

**Via API:**
```bash
# Get workflow runs
curl -H "Authorization: Bearer $GITHUB_TOKEN" \
  https://api.github.com/repos/pausatf/pausatf-infrastructure-docs/actions/workflows/infrastructure-health.yml/runs
```

### Email Notifications

GitHub sends automatic email notifications on:
- ✅ Workflow failure
- ✅ First workflow success after failure
- ⚠️ Workflow taking longer than expected (>10 minutes)

**Configure notifications:**
1. Go to https://github.com/settings/notifications
2. Enable "Actions" notifications
3. Choose email or mobile alerts

### Troubleshooting

**Workflow fails with "401 Unauthorized":**
- **Cause:** Expired DigitalOcean or Cloudflare API token
- **Solution:** Rotate token via `.github/SECRETS.md` procedure

**Workflow fails with "Resource not found":**
- **Cause:** Incorrect droplet ID or zone ID
- **Solution:** Verify IDs match current infrastructure in `README.md`

**Workflow shows no backups:**
- **Cause:** Backup policy not enabled or backups failing
- **Solution:** Check DigitalOcean console, verify backup policy enabled

**Workflow cancelled or timed out:**
- **Cause:** Network issues or API rate limiting
- **Solution:** Re-run workflow manually, check DigitalOcean status page

---

## 2. Documentation Validation

**File:** `.github/workflows/documentation-validation.yml`

### Purpose

Automated validation of documentation quality, structure, and security to maintain high standards and prevent errors.

### Trigger Events

```yaml
on:
  push:
    branches: [main]
    paths: ['**.md']
  pull_request:
    branches: [main]
    paths: ['**.md']
  workflow_dispatch:
```

**When it runs:**
- On every push to main branch that changes .md files
- On every pull request that changes .md files
- Manually via GitHub Actions UI
- Only runs if markdown files are modified (performance optimization)

### Jobs Overview

This workflow consists of 4 independent jobs:

#### Job 1: Validate Documentation Links

**Purpose:** Ensure all internal documentation links are valid and not broken

**What it checks:**
- ✅ Internal markdown file references (e.g., `[link](file.md)`)
- ✅ Cross-document links between guides
- ✅ Referenced files actually exist

**How it works:**
```bash
# Find all markdown links
grep -r '\[.*\](.*\.md)' *.md

# Extract link targets
# Verify each target file exists
# Report broken links
```

**Example valid links:**
```markdown
See [Operational Procedures](10-operational-procedures.md) for details.
Refer to [Security Audit](04-security-audit-report.md#findings).
```

**Example invalid links:**
```markdown
See [Missing File](99-nonexistent.md)  # ❌ File doesn't exist
Refer to [Bad Link](wrong-name.md)    # ❌ Wrong filename
```

**Failure scenarios:**
- Link points to non-existent file
- Typo in filename
- File renamed but links not updated
- Incorrect relative path

---

#### Job 2: Check Documentation Consistency

**Purpose:** Ensure all documentation is properly indexed and referenced

**What it checks:**
- ✅ All numbered documents (01-13) are referenced in README.md
- ✅ Table of contents is complete
- ✅ No orphaned documentation files

**How it works:**
```bash
# Find all numbered docs
for doc in [0-9][0-9]-*.md; do
  # Check if referenced in README
  if ! grep -q "$doc" README.md; then
    echo "Warning: $doc not in README"
  fi
done
```

**Expected structure:**
- All files matching `[0-9][0-9]-*.md` pattern
- Each file listed in README.md table of contents
- Correct sequential numbering

**Failure scenarios:**
- New numbered document not added to README
- Document removed but still referenced
- Inconsistent numbering

---

#### Job 3: Validate Repository Structure

**Purpose:** Verify all required documentation files exist

**What it checks:**
- ✅ Core documentation files (README, CHANGELOG, etc.)
- ✅ All 13 numbered guides (01-13)
- ✅ Deployment package files
- ✅ Required support files

**Required files verified:**
```
README.md
CHANGELOG.md
EXECUTIVE-SUMMARY.md
01-cache-implementation-guide.md
02-cache-audit-report.md
03-cache-verification-report.md
04-security-audit-report.md
05-server-migration-guide.md
06-cloudflare-configuration-guide.md
07-performance-optimization-complete.md
08-recommended-upgrades-roadmap.md
09-google-workspace-email-security.md
10-operational-procedures.md
11-database-maintenance.md
12-server-rightsizing-analysis.md
13-digitalocean-optimization-guide.md
deployment-package/data_2025_htaccess
deployment-package/purge_cloudflare_cache.sh
deployment-package/DEPLOYMENT_INSTRUCTIONS.txt
```

**Failure scenarios:**
- Required file missing
- File renamed without updating validation
- Deployment package incomplete

---

#### Job 4: Markdown Linting

**Purpose:** Enforce markdown formatting standards and best practices

**What it checks:**
- ✅ Consistent heading levels
- ✅ Proper list formatting
- ✅ Code block syntax
- ✅ Line length limits
- ✅ No trailing whitespace
- ✅ Consistent indentation

**Linter:** DavidAnson/markdownlint-cli2-action@v16

**Configuration:** Uses repository `.markdownlint.json` (if present)

**Note:** Currently set to `continue-on-error: true` (warnings only)

**Common issues flagged:**
- Missing blank lines around headers
- Inconsistent list indentation
- Code blocks without language specification
- Multiple consecutive blank lines
- Lines exceeding 120 characters

---

#### Job 5: Check for Exposed Secrets

**Purpose:** Scan documentation for accidentally committed secrets or sensitive information

**What it checks:**
- ⚠️ Password patterns in markdown files
- ⚠️ Unexpected IP addresses (excluding known server IPs)
- ⚠️ API keys or tokens in examples
- ⚠️ Database credentials

**Allowed/Expected:**
- Server IPs: 64.225.40.54 (production), 64.227.85.73 (staging)
- Private network: 10.138.0.0/16
- Documentation examples clearly marked as examples

**Failure scenarios:**
- Real password found in documentation
- API key accidentally committed
- IP address not in allowlist
- Sensitive configuration exposed

**Note:** This is a basic scan. Pre-commit hooks provide more comprehensive secret detection via gitleaks and detect-secrets.

---

### Viewing Results

**Via Pull Request:**
- Results automatically displayed in PR checks
- Detailed logs available by clicking "Details"
- PR cannot be merged if validation fails (optional enforcement)

**Via GitHub CLI:**
```bash
# List documentation validation runs
gh run list --workflow=documentation-validation.yml

# View specific run
gh run view RUN_ID --log
```

### Troubleshooting

**Link validation fails:**
- Check error message for specific broken link
- Verify target file exists and name is correct
- Update link or create missing file

**Structure validation fails:**
- Check which file is missing
- Ensure file was committed to repository
- Verify filename matches expected pattern

**Markdown linting fails:**
- Review linting errors in workflow log
- Fix formatting issues locally
- Run `markdownlint **/*.md` before committing

**Secret scan triggers warning:**
- Review flagged content
- Ensure it's not a real secret
- Add to exclusion pattern if false positive

---

## 3. Commit Validation

**File:** `.github/workflows/commit-validation.yml`

### Purpose

Enforce conventional commit message standards across all contributions to maintain consistent, scannable git history and enable automated changelog generation.

### Trigger Events

```yaml
on:
  pull_request:
    types: [opened, synchronize, reopened]
```

**When it runs:**
- On pull request creation
- On new commits pushed to PR
- On PR re-opening
- Does NOT run on direct commits to main (use pre-commit hooks instead)

### Jobs Overview

#### Job: Validate Conventional Commits

**Purpose:** Verify all commits in PR follow conventional commit specification

**What it validates:**

1. **Commit message format:**
   ```
   type(scope): subject

   body

   footer
   ```

2. **Valid types:**
   - feat, fix, docs, refactor, perf, test, build, ci, chore, revert

3. **Subject rules:**
   - Starts with lowercase letter
   - No period at end
   - Under 72 characters
   - Imperative mood

4. **PR title format:**
   - Must also follow conventional commit format
   - Used as squash merge commit message

**Example valid commits:**
```
feat(firewall): add staging server protection
fix(monitoring): enable DO agent on production
docs(infrastructure): document Phase 1 improvements
```

**Example invalid commits:**
```
❌ Updated firewall                    # Missing type/scope
❌ feat(firewall): Added protection.   # Past tense, has period
❌ FEAT: new feature                   # All caps, no scope
❌ fix: bug                            # Too vague
```

**Validation tools:**
- commitlint with @commitlint/config-conventional
- Configuration: `.commitlintrc.json`
- PR title: amannn/action-semantic-pull-request@v5

**Failure scenarios:**
- Commit type not in allowed list
- Subject not lowercase
- Subject has trailing period
- Header too long (>72 characters)
- PR title doesn't match format

**Secrets required:** None (uses automatic GITHUB_TOKEN)

---

### Viewing Results

**Via Pull Request:**
- Check status displayed in PR checks section
- Click "Details" to see which commits failed
- Error message shows specific validation issue

**Example error messages:**
```
subject may not be empty
type must be one of [feat, fix, docs, ...]
subject must be lowercase
header must not be longer than 72 characters
```

**Via GitHub CLI:**
```bash
# View PR checks
gh pr checks PR_NUMBER

# View detailed commit validation logs
gh run view RUN_ID --log
```

### Fixing Validation Failures

**Option 1: Amend commit message (last commit)**
```bash
git commit --amend -m "feat(scope): proper message"
git push --force-with-lease
```

**Option 2: Interactive rebase (multiple commits)**
```bash
git rebase -i HEAD~3
# Use 'reword' to fix messages
git push --force-with-lease
```

**Option 3: Update PR title**
- Edit PR title via GitHub UI
- Or: `gh pr edit PR_NUMBER --title "feat(scope): new title"`

### Troubleshooting

**All commits valid but check still fails:**
- Verify PR title also follows format
- Check for merge commits with invalid messages
- Review full error output in workflow logs

**Pre-commit hook didn't catch issue:**
- Verify pre-commit hooks installed: `pre-commit install --hook-type commit-msg`
- Test hooks manually: `pre-commit run --hook-stage commit-msg`
- Update hook configuration if needed

**Need to bypass for emergency:**
- Use `git commit --no-verify` (document reason in commit body)
- Plan to fix message format after emergency resolved

---

## Secret Management

All workflows use GitHub Secrets for sensitive credentials. See `.github/SECRETS.md` for complete documentation.

### Secrets Used

| Secret | Used By | Purpose | Rotation |
|--------|---------|---------|----------|
| DIGITALOCEAN_ACCESS_TOKEN | Infrastructure Health | DigitalOcean API access | Annually |
| CLOUDFLARE_API_TOKEN | Infrastructure Health | Cloudflare API access | Annually |
| CLOUDFLARE_ZONE_ID | Infrastructure Health | Zone identification | Never (not sensitive) |
| PROD_DROPLET_ID | Infrastructure Health | Production droplet ID | Never (not sensitive) |
| STAGING_DROPLET_ID | Infrastructure Health | Staging droplet ID | Never (not sensitive) |
| GITHUB_TOKEN | Commit Validation | Automatic, provided by GitHub | Automatic |

### Security Best Practices

**DO:**
- ✅ Use read-only tokens where possible
- ✅ Limit token scope to minimum required
- ✅ Rotate tokens annually
- ✅ Monitor workflow logs for unauthorized access
- ✅ Use GitHub's secret scanning features

**DON'T:**
- ❌ Print secret values in logs
- ❌ Use personal access tokens for production
- ❌ Give write access unless absolutely necessary
- ❌ Share secrets outside GitHub Actions
- ❌ Hardcode secrets in workflow files

---

## Workflow Maintenance

### Adding New Workflows

1. Create workflow file in `.github/workflows/`
2. Test workflow via `workflow_dispatch` trigger
3. Document workflow in this file (GITHUB-ACTIONS.md)
4. Update `.github/SECRETS.md` if new secrets added
5. Add to README.md CI/CD status section

### Modifying Existing Workflows

1. Create feature branch
2. Modify workflow file
3. Test via pull request
4. Update documentation if behavior changed
5. Merge after validation passes

### Disabling Workflows

**Temporarily:**
- GitHub UI: Actions → Select workflow → Disable
- Or add condition: `if: false` to workflow file

**Permanently:**
- Delete workflow file
- Remove from documentation
- Remove associated secrets if no longer needed

### Monitoring Workflow Health

**Weekly:**
- Review failed workflow runs
- Check execution duration trends
- Verify all workflows running as expected

**Monthly:**
- Review workflow efficiency
- Update action versions
- Optimize slow-running jobs

**Quarterly:**
- Review secret rotation schedule
- Audit workflow permissions
- Update documentation

---

## Performance Optimization

### Current Performance

| Workflow | Avg Duration | Success Rate | Last 30 Days |
|----------|--------------|--------------|--------------|
| Infrastructure Health | ~2 minutes | 100% | 30/30 runs |
| Documentation Validation | ~1 minute | 98% | 28/28 runs |
| Commit Validation | ~30 seconds | 95% | Variable |

### Optimization Strategies

**Path filtering:**
- Documentation Validation only runs on .md file changes
- Reduces unnecessary runs by ~70%

**Job parallelization:**
- Infrastructure Health runs 3 jobs concurrently
- Documentation Validation runs 4 jobs concurrently
- Saves ~5 minutes per run

**Caching:**
- Not currently implemented (opportunity for improvement)
- Could cache npm packages for commit validation
- Could cache doctl binary for infrastructure health

**Resource limits:**
- All jobs use ubuntu-latest (2-core, 7GB RAM)
- Sufficient for current workloads
- Can upgrade to ubuntu-latest-4-core if needed

---

## Cost Analysis

GitHub Actions is free for public repositories with unlimited minutes.

**Current usage (December 2025):**
- Infrastructure Health: 30 runs/month × 2 min = 60 minutes/month
- Documentation Validation: ~20 runs/month × 1 min = 20 minutes/month
- Commit Validation: ~40 runs/month × 0.5 min = 20 minutes/month
- **Total: ~100 minutes/month**

**For private repositories:**
- Free tier: 2,000 minutes/month
- Current usage well within free tier
- No cost impact expected

---

## Troubleshooting Guide

### Common Issues

**1. Workflow doesn't trigger automatically**

**Symptoms:**
- Push to main, but workflow doesn't run
- PR created, but checks don't appear

**Solutions:**
- Verify workflow file syntax (YAML valid)
- Check trigger conditions match event
- Ensure GitHub Actions enabled for repository
- Review repository settings → Actions → General

**2. Workflow fails with permission error**

**Symptoms:**
- Error: "Resource not accessible by integration"
- Error: "403 Forbidden"

**Solutions:**
- Check workflow permissions in repository settings
- Verify GITHUB_TOKEN has required scopes
- For external APIs, verify secret is set correctly

**3. Workflow hangs or times out**

**Symptoms:**
- Workflow running for >30 minutes
- Job stuck on specific step
- No progress in logs

**Solutions:**
- Cancel workflow manually
- Check DigitalOcean/Cloudflare API status
- Add timeout to long-running steps
- Investigate rate limiting

**4. Secret not found error**

**Symptoms:**
- Error: "Secret DIGITALOCEAN_ACCESS_TOKEN not found"
- Workflow fails on secret access

**Solutions:**
- Verify secret exists: Settings → Secrets → Actions
- Check secret name matches exactly (case-sensitive)
- Ensure secret is set at repository level, not environment
- Re-create secret if corrupted

**5. Validation passing locally but failing in CI**

**Symptoms:**
- Pre-commit hooks pass locally
- GitHub Actions validation fails
- Different results for same code

**Solutions:**
- Check action versions match local tools
- Verify configuration files committed (.commitlintrc.json)
- Test with same Node.js/Python versions as CI
- Review full error output in workflow logs

---

## Related Documentation

- **Secret Management:** `.github/SECRETS.md`
- **Commit Standards:** `COMMIT-STANDARDS.md`
- **Contributing Guide:** `CONTRIBUTING.md`
- **Operational Procedures:** `10-operational-procedures.md`
- **Infrastructure Overview:** `README.md`

---

## Support and Contact

**For workflow issues:**
- Review this documentation
- Check workflow logs for error messages
- Create issue with `ci` label
- Contact repository maintainer

**For GitHub Actions platform issues:**
- GitHub Status: https://www.githubstatus.com/
- GitHub Support: https://support.github.com/

**For API-related failures:**
- DigitalOcean Status: https://status.digitalocean.com/
- Cloudflare Status: https://www.cloudflarestatus.com/

---

**Document Version:** 1.0
**Last Updated:** 2025-12-21
**Maintained by:** Thomas Vincent
**Next Review:** 2026-01-21 (monthly)
**Related Workflows:** 3 active workflows in `.github/workflows/`
