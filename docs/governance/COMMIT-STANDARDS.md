# Commit Standards

**Version:** 1.0
**Last Updated:** 2025-12-21
**Status:** Convention checked in review; not enforced by a hook or workflow

---

## Overview

All commits to PAUSATF repositories must follow the **Conventional Commits** specification. This ensures:
- Clear, scannable commit history
- Automated changelog generation
- Semantic versioning compatibility
- Better collaboration and code review

---

## Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Structure

**Header (Required):**
```
<type>(<scope>): <subject>
```

- **type**: Category of change (see types below)
- **scope**: Optional, area of codebase affected
- **subject**: Brief description (imperative mood, lowercase, no period)

**Body (Optional):**
- Detailed explanation of what and why (not how)
- Wrap at 72 characters
- Separate from header with blank line

**Footer (Optional):**
- Breaking changes, issue references
- Format: `BREAKING CHANGE:` or `Closes #123`

---

## Commit Types

### Primary Types

| Type | Description | Changelog Section | Example |
|------|-------------|-------------------|---------|
| `feat` | New feature | Features | `feat(firewall): add staging server firewall` |
| `fix` | Bug fix | Bug Fixes | `fix(backup): correct retention policy` |
| `docs` | Documentation only | Documentation | `docs(readme): update CI/CD status` |
| `refactor` | Code change (no feat/fix) | Refactor | `refactor(monitoring): simplify alert config` |
| `perf` | Performance improvement | Performance | `perf(cache): optimize CDN rules` |
| `test` | Add/update tests | Tests | `test(integration): add firewall tests` |
| `build` | Build system changes | Build | `build(deps): update doctl to v1.100` |
| `ci` | CI/CD changes | CI/CD | `ci(actions): add security validation` |
| `chore` | Maintenance tasks | Chore | `chore(cleanup): remove deprecated files` |
| `revert` | Revert previous commit | Reverts | `revert: revert "feat: add feature"` |

### Type Guidelines

**Use `feat` when:**
- Adding new functionality
- Adding new configuration
- Introducing new capabilities

**Use `fix` when:**
- Fixing a bug
- Correcting an error
- Resolving an issue

**Use `docs` when:**
- ONLY updating documentation
- No code changes
- No configuration changes

**Use `chore` when:**
- Updating dependencies
- Cleaning up code
- Organizational tasks

---

## Scopes

Scopes indicate the area of codebase affected. Use these standard scopes:

### Infrastructure Scopes
- `firewall` - Firewall configuration
- `droplet` - Droplet/server configuration
- `backup` - Backup systems
- `monitoring` - Monitoring and alerts
- `dns` - DNS configuration
- `ssl` - SSL/TLS certificates
- `cdn` - CDN and caching
- `database` - Database configuration
- `security` - Security improvements
- `performance` - Performance optimization

### Documentation Scopes
- `readme` - README.md changes
- `changelog` - CHANGELOG.md updates
- `contributing` - Contributing guidelines
- `runbook` - Operational runbooks
- `docs` - General documentation

### Automation Scopes
- `ci` - GitHub Actions workflows
- `terraform` - Terraform configurations
- `ansible` - Ansible playbooks
- `scripts` - Utility scripts

---

## Subject Line Rules

### DO

✅ Use imperative mood: "add feature" not "added feature"
✅ Start with lowercase letter
✅ Keep under 50 characters
✅ Be specific and descriptive
✅ Focus on what, not how

### DON'T

❌ End with a period
❌ Use past tense
❌ Be vague ("fix stuff", "update things")
❌ Include implementation details
❌ Start with capital letter (except proper nouns)

---

## Examples

### Good Commits

```
feat(firewall): add staging server firewall protection

Created firewall c12dfc7f-f43a-4b32-96c6-80ba34035b1a with rules
for SSH (22), HTTP (80), HTTPS (443), and ICMP. This protects the
staging server from internet-based attacks.

Closes #45
```

```
fix(monitoring): enable DO agent on production server

The DigitalOcean agent was not running on production, preventing
detailed metrics collection. Installed do-agent 3.18.5 and verified
service is active.
```

```
docs(infrastructure): document Phase 1 security improvements

Added comprehensive documentation for:
- Firewall configuration
- Automated security updates
- Monitoring agent installation
- Infrastructure as code updates
```

```
ci(validation): add infrastructure health check workflow

Daily automated validation of:
- Droplet backups
- Firewall status
- DNS configuration
- SSL certificates
```

```
chore(cleanup): remove deprecated backup scripts

Removed old manual backup scripts that have been replaced by
automated DigitalOcean backup policies.
```

### Bad Commits (Don't Do This)

❌ `Update stuff` - Too vague, no type or scope
❌ `Added firewall` - Past tense, no scope
❌ `Fix bug.` - Has period, too vague
❌ `FEAT: New feature` - All caps, generic
❌ `feat(firewall): We added a firewall to the staging server` - Not imperative, too wordy

---

## Breaking Changes

When introducing breaking changes, use the `BREAKING CHANGE:` footer:

```
feat(api)!: change backup policy API endpoint

BREAKING CHANGE: The backup policy endpoint has moved from
/v1/backups to /v2/backup-policies. Update all API clients.

Migration guide: docs/migrations/backup-api-v2.md
```

Note the `!` after the type/scope indicates a breaking change.

---

## Enforcement

Conventional Commits are a convention that reviewers check. No hook or workflow rejects a non-conforming message:
`.pre-commit-config.yaml` has no `commit-msg` stage, there is no `.commitlintrc.json`, and no commit-validation
workflow exists in `.github/workflows/`. Pull requests are squash-merged, so the pull request title becomes the commit
subject on `main` and should follow the same format.

To check a message yourself before pushing, use commitlint:

```bash
npm install -g @commitlint/cli @commitlint/config-conventional
git log -1 --pretty=format:"%B" | commitlint --extends @commitlint/config-conventional
```

Commits are signed off (`git commit -s`), which adds a `Signed-off-by` trailer. See
[CONTRIBUTING.md](../../CONTRIBUTING.md#commit-message-format) for the repository's current commit rules.

---

## Workflow Integration

### Making a Commit

```bash
# Stage your changes
git add .

# Commit with conventional format
git commit -m "feat(firewall): add staging server protection"

# Pre-commit hook validates automatically
# If invalid, commit is rejected with error message
```

### Amending Commits

```bash
# Fix commit message if validation fails
git commit --amend -m "feat(firewall): add staging server protection"

# Force push if already pushed (use carefully)
git push --force-with-lease
```

### Interactive Rebase

For cleaning up commit history before merging:

```bash
# Rebase last 3 commits
git rebase -i HEAD~3

# Use 'reword' to fix commit messages
# Use 'squash' to combine commits
```

---

## Changelog Generation

Commits following this standard enable automated changelog generation:

```bash
# Generate changelog from commits
npx conventional-changelog-cli -p angular -i CHANGELOG.md -s

# Preview next version
npx standard-version --dry-run
```

The CHANGELOG.md is automatically organized by type:

```markdown
## [1.2.0] - 2025-12-21

### Features
- **firewall**: add staging server protection
- **monitoring**: install DO agent for enhanced metrics

### Bug Fixes
- **backup**: correct retention policy configuration

### Documentation
- **infrastructure**: document Phase 1 improvements
```

---

## Multi-Repository Standards

These standards apply to all PAUSATF repositories:

- **pausatf-infrastructure-docs** (this repository)
- **pausatf-terraform** (infrastructure provisioning)
- **pausatf-ansible** (configuration management)
- **pausatf-scripts** (automation scripts)

Each repository should have:
- ✅ `.commitlintrc.json` configuration
- ✅ Pre-commit hooks installed
- ✅ GitHub Actions validation
- ✅ COMMIT-STANDARDS.md (or link to this document)

---

## Training and Resources

### Quick Reference Card

```
feat:     New feature
fix:      Bug fix
docs:     Documentation only
refactor: Code restructuring
perf:     Performance improvement
test:     Testing changes
build:    Build system
ci:       CI/CD changes
chore:    Maintenance

Format: type(scope): lowercase subject under 50 chars
```

### External Resources

- [Conventional Commits](https://www.conventionalcommits.org/)
- [Angular Commit Guidelines](https://github.com/angular/angular/blob/main/CONTRIBUTING.md#commit)
- [Semantic Versioning](https://semver.org/)
- [commitlint](https://commitlint.js.org/)

---

## Troubleshooting

### Pre-commit Hook Not Running

```bash
# Reinstall hooks
pre-commit uninstall --hook-type commit-msg
pre-commit install --hook-type commit-msg

# Verify installation
ls -la .git/hooks/commit-msg
```

### Commit Message Rejected

**Error:** `subject may not be empty`
- **Solution:** Add descriptive subject after type/scope

**Error:** `type must be one of [feat, fix, ...]`
- **Solution:** Use valid type from approved list

**Error:** `subject must be lowercase`
- **Solution:** Use lowercase for subject (except proper nouns)

**Error:** `header must not be longer than 72 characters`
- **Solution:** Shorten subject, use body for details

### Bypassing Validation (Emergency Only)

```bash
# Skip pre-commit hooks (use only for emergencies)
git commit -m "message" --no-verify

# This should be rare and documented
```

---

## Exemptions

### When Standards Don't Apply

- Initial repository setup commits
- Merge commits from upstream
- Automated bot commits (Dependabot, etc.)
- Emergency hotfixes (document in body why standard skipped)

### Handling Legacy Commits

Pre-existing commits don't need to be rewritten. Standards apply to:
- New commits after 2025-12-21
- Amended commits
- Rebased commits

---

## Enforcement Policy

### Pull Request Review

PRs are automatically checked for:
- ✅ All commits follow conventional format
- ✅ Commit messages are descriptive
- ✅ Scope matches changed files
- ✅ Breaking changes are documented

**Failed validation = PR blocked until fixed**

### Merge Strategy

Prefer **squash merging** with conventional commit format:

```bash
# Squash merge with proper format
git merge --squash feature-branch
git commit -m "feat(feature): add new capability

Implemented new feature with the following changes:
- Component A
- Component B
- Component C

Closes #123"
```

---

## Maintenance

### Review Schedule

- **Monthly:** Review commit compliance (automated)
- **Quarterly:** Update scope list as needed
- **Annually:** Review and update standards

### Updating Standards

Changes to commit standards require:
1. Discussion in issue or PR
2. Team consensus
3. Update to this document
4. Communication to all contributors
5. Update of enforcement configuration

---

## Questions and Support

**For questions about commit standards:**
- Review this document
- Check examples section
- Ask in pull request comments
- Create issue with `question` label

**For enforcement issues:**
- Check troubleshooting section
- Verify `.commitlintrc.json` configuration
- Test pre-commit hooks manually
- Review GitHub Actions logs

---

**Document Version:** 1.0
**Effective Date:** 2025-12-21
**Maintained by:** Thomas Vincent
**Next Review:** 2026-03-21
**Related:** CONTRIBUTING.md, .commitlintrc.json, .pre-commit-config.yaml
