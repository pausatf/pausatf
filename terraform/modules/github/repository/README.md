# GitHub Repository Module

Terraform module for creating and managing GitHub repositories with comprehensive branch protection, security features, and team collaboration settings.

## Features

- **Repository Management**: Create public or private repositories
- **Branch Protection**: Enforce code review and quality standards
- **Security**: Vulnerability alerts, Dependabot, signed commits
- **Team Collaboration**: Issues, Projects, Discussions, Wiki
- **Merge Strategies**: Control merge commit, squash, and rebase options
- **Templates**: Initialize from repository templates
- **Auto-merge**: Enable automatic PR merging
- **Topics**: Organize repositories with searchable topics
- **Advanced Protection**: Status checks, required reviews, conversation resolution

## Usage

### Basic Repository

```hcl
module "simple_repo" {
  source = "../../modules/github/repository"

  name        = "my-project"
  description = "My awesome project"
  visibility  = "public"

  has_issues = true
  has_wiki   = false

  topics = ["terraform", "infrastructure"]
}
```

### Production Repository with Branch Protection

```hcl
module "prod_repo" {
  source = "../../modules/github/repository"

  name        = "pausatf"
  description = "PAUSATF Infrastructure Monorepo"
  visibility  = "public"

  # Features
  has_issues      = true
  has_wiki        = false
  has_projects    = true
  has_discussions = false

  # Merge options
  allow_merge_commit     = true
  allow_squash_merge     = true
  allow_rebase_merge     = true
  allow_auto_merge       = false
  delete_branch_on_merge = true

  # Security
  vulnerability_alerts = true
  enable_dependabot    = true

  # Branch protection
  enable_branch_protection    = true
  protected_branch            = "main"
  require_signed_commits      = true
  require_linear_history      = false
  allows_force_pushes         = false
  allows_deletions            = false
  require_conversation_resolution = true
  enforce_admins              = false

  # Required status checks
  required_status_checks = {
    strict   = true
    contexts = ["ci/tests", "ci/lint"]
  }

  # Required reviews
  required_pull_request_reviews = {
    dismiss_stale_reviews           = true
    require_code_owner_reviews      = true
    required_approving_review_count = 1
    require_last_push_approval      = false
  }

  topics = ["infrastructure", "terraform", "wordpress", "cloudflare", "digitalocean"]
}
```

### Repository from Template

```hcl
module "from_template" {
  source = "../../modules/github/repository"

  name        = "new-service"
  description = "New microservice from template"
  visibility  = "private"

  template_repository = {
    owner      = "pausatf"
    repository = "service-template"
  }

  topics = ["microservice", "api"]
}
```

### Repository with Auto-init

```hcl
module "new_repo" {
  source = "../../modules/github/repository"

  name        = "new-project"
  description = "New project with auto-initialization"
  visibility  = "public"

  auto_init          = true
  gitignore_template = "Node"
  license_template   = "mit"

  topics = ["nodejs", "api"]
}
```

## Requirements

| Name | Version |
|------|---------|
| terraform | >= 1.0 |
| github | ~> 6.0 |

## Inputs

### Required Inputs

| Name | Description | Type |
|------|-------------|------|
| name | Name of the GitHub repository | `string` |

### Optional Inputs

#### Repository Configuration

| Name | Description | Type | Default |
|------|-------------|------|---------|
| description | Description of the repository | `string` | `""` |
| visibility | Repository visibility (public or private) | `string` | `"public"` |

#### Features

| Name | Description | Type | Default |
|------|-------------|------|---------|
| has_issues | Enable GitHub Issues | `bool` | `true` |
| has_wiki | Enable GitHub Wiki | `bool` | `false` |
| has_projects | Enable GitHub Projects | `bool` | `false` |
| has_discussions | Enable GitHub Discussions | `bool` | `false` |
| has_downloads | Enable downloads | `bool` | `true` |

#### Merge Settings

| Name | Description | Type | Default |
|------|-------------|------|---------|
| allow_merge_commit | Allow merge commits | `bool` | `true` |
| allow_squash_merge | Allow squash merges | `bool` | `true` |
| allow_rebase_merge | Allow rebase merges | `bool` | `true` |
| allow_auto_merge | Allow auto-merge | `bool` | `false` |
| delete_branch_on_merge | Delete head branches after merge | `bool` | `true` |

#### Security

| Name | Description | Type | Default |
|------|-------------|------|---------|
| vulnerability_alerts | Enable vulnerability alerts | `bool` | `true` |
| enable_dependabot | Enable Dependabot security updates | `bool` | `true` |

#### Initialization

| Name | Description | Type | Default |
|------|-------------|------|---------|
| auto_init | Initialize repository with README | `bool` | `false` |
| gitignore_template | Gitignore template to use | `string` | `null` |
| license_template | License template to use | `string` | `null` |

#### Organization

| Name | Description | Type | Default |
|------|-------------|------|---------|
| topics | Repository topics | `list(string)` | `[]` |

#### Template

| Name | Description | Type | Default |
|------|-------------|------|---------|
| template_repository | Template repository configuration | `object` | `null` |

#### Branch Protection

| Name | Description | Type | Default |
|------|-------------|------|---------|
| enable_branch_protection | Enable branch protection | `bool` | `true` |
| protected_branch | Branch to protect | `string` | `"main"` |
| require_signed_commits | Require signed commits | `bool` | `true` |
| require_linear_history | Require linear history | `bool` | `false` |
| allows_force_pushes | Allow force pushes | `bool` | `false` |
| allows_deletions | Allow branch deletions | `bool` | `false` |
| require_conversation_resolution | Require conversation resolution before merging | `bool` | `false` |
| lock_branch | Lock branch to read-only | `bool` | `false` |
| enforce_admins | Enforce branch protection for administrators | `bool` | `false` |

#### Advanced Protection

| Name | Description | Type | Default |
|------|-------------|------|---------|
| required_status_checks | Required status checks configuration | `object` | `null` |
| required_pull_request_reviews | Required pull request reviews configuration | `object` | `null` |

### Required Status Checks Object

```hcl
{
  strict   = bool           # Require branches to be up to date before merging
  contexts = list(string)   # List of status check context names (e.g., ["ci/tests"])
}
```

### Required Pull Request Reviews Object

```hcl
{
  dismiss_stale_reviews           = bool    # Dismiss approvals when new commits are pushed
  require_code_owner_reviews      = bool    # Require review from code owners
  required_approving_review_count = number  # Number of approving reviews required (1-6)
  require_last_push_approval      = bool    # Require approval on most recent push
}
```

## Outputs

| Name | Description |
|------|-------------|
| repository_id | GitHub repository ID |
| repository_node_id | GitHub repository node ID |
| repository_name | Repository name |
| repository_full_name | Repository full name (owner/repo) |
| repository_html_url | Repository URL |
| repository_ssh_clone_url | SSH clone URL |
| repository_http_clone_url | HTTP clone URL |
| repository_git_clone_url | Git clone URL |
| default_branch | Default branch name |

## Examples

### Monorepo with Strict Protection

```hcl
module "infrastructure_repo" {
  source = "../../modules/github/repository"

  name        = "infrastructure"
  description = "Infrastructure as Code monorepo"
  visibility  = "private"

  # Enable collaboration features
  has_issues      = true
  has_projects    = true
  has_discussions = true

  # Strict merge settings
  allow_merge_commit     = false  # Only squash or rebase
  allow_squash_merge     = true
  allow_rebase_merge     = true
  delete_branch_on_merge = true

  # Maximum security
  vulnerability_alerts = true
  enable_dependabot    = true

  # Strict branch protection
  enable_branch_protection        = true
  protected_branch                = "main"
  require_signed_commits          = true
  require_linear_history          = true
  allows_force_pushes             = false
  allows_deletions                = false
  require_conversation_resolution = true
  enforce_admins                  = true  # Even admins must follow rules

  # Required CI checks
  required_status_checks = {
    strict   = true
    contexts = [
      "ci/terraform-validate",
      "ci/terraform-fmt",
      "ci/ansible-lint",
      "security/trivy-scan"
    ]
  }

  # Required reviews
  required_pull_request_reviews = {
    dismiss_stale_reviews           = true
    require_code_owner_reviews      = true
    required_approving_review_count = 2
    require_last_push_approval      = true
  }

  topics = ["infrastructure-as-code", "terraform", "ansible", "security"]
}
```

### Open Source Project

```hcl
module "oss_project" {
  source = "../../modules/github/repository"

  name        = "awesome-tool"
  description = "An awesome open source tool"
  visibility  = "public"

  auto_init          = true
  license_template   = "mit"
  gitignore_template = "Python"

  # Community features
  has_issues      = true
  has_wiki        = true
  has_discussions = true

  # Flexible merge options for contributors
  allow_merge_commit     = true
  allow_squash_merge     = true
  allow_rebase_merge     = true
  delete_branch_on_merge = true

  # Basic protection
  enable_branch_protection = true
  protected_branch         = "main"
  require_signed_commits   = false  # Optional for contributors

  required_pull_request_reviews = {
    dismiss_stale_reviews           = true
    require_code_owner_reviews      = false
    required_approving_review_count = 1
    require_last_push_approval      = false
  }

  topics = ["python", "cli", "automation", "open-source"]
}
```

### Accessing Outputs

```hcl
# Clone URL for CI/CD
output "clone_url" {
  value = module.prod_repo.repository_ssh_clone_url
}

# Repository URL for documentation
output "repo_url" {
  value = module.prod_repo.repository_html_url
}

# Use in other resources
resource "github_team_repository" "infra_team" {
  team_id    = github_team.infrastructure.id
  repository = module.prod_repo.repository_name
  permission = "maintain"
}
```

## Gitignore Templates

Common templates:
- `Node` - Node.js
- `Python` - Python
- `Go` - Go
- `Java` - Java
- `Ruby` - Ruby
- `Terraform` - Terraform
- `VisualStudio` - Visual Studio

Full list: https://github.com/github/gitignore

## License Templates

Common licenses:
- `mit` - MIT License
- `apache-2.0` - Apache License 2.0
- `gpl-3.0` - GNU GPLv3
- `bsd-3-clause` - BSD 3-Clause
- `unlicense` - Unlicense (public domain)

Full list: https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/licensing-a-repository

## Merge Strategies

### Merge Commit
- Preserves full commit history
- Creates merge commit
- Best for: Feature branch workflow

### Squash Merge
- Combines all commits into one
- Clean linear history
- Best for: Small features, bug fixes

### Rebase Merge
- Replays commits on top of base
- No merge commit
- Best for: Linear history preference

## Branch Protection Best Practices

1. **Signed Commits**: Enforce for security-sensitive repositories
2. **Status Checks**: Require CI/CD to pass before merge
3. **Code Reviews**: Require at least 1 approval for production code
4. **Linear History**: Consider for clean git history
5. **Conversation Resolution**: Ensure all comments are addressed
6. **Dismiss Stale Reviews**: Re-review when new commits are added
7. **Code Owners**: Use CODEOWNERS file with required reviews
8. **Enforce for Admins**: Apply rules to all team members

## Security Best Practices

1. **Vulnerability Alerts**: Always enable
2. **Dependabot**: Enable automatic security updates
3. **Signed Commits**: Require for production repositories
4. **Branch Protection**: Protect main/production branches
5. **Secret Scanning**: Enable in repository settings
6. **Code Scanning**: Set up CodeQL or similar
7. **Private Repos**: Use for sensitive code
8. **Access Control**: Limit who can push to protected branches

## Common Patterns

### Development Workflow

```hcl
# Development repo with moderate protection
enable_branch_protection        = true
require_signed_commits          = false
required_approving_review_count = 1
allow_squash_merge              = true
```

### Production Workflow

```hcl
# Production repo with strict protection
enable_branch_protection        = true
require_signed_commits          = true
require_linear_history          = true
required_approving_review_count = 2
require_last_push_approval      = true
enforce_admins                  = true
```

### Open Source Workflow

```hcl
# OSS repo with contributor-friendly settings
enable_branch_protection        = true
require_signed_commits          = false
required_approving_review_count = 1
allow_merge_commit              = true
allow_squash_merge              = true
allow_rebase_merge              = true
```

## Troubleshooting

### Issue: Cannot enable branch protection

**Solution**: Ensure the branch exists before applying protection. Create initial commit first.

### Issue: Status checks not found

**Solution**: Status check context names must match exactly. Check your CI/CD configuration.

### Issue: Required reviews blocking auto-merge

**Solution**: Ensure `allow_auto_merge = true` and reviews are configured to approve automatically (via GitHub Actions).

## Cost Considerations

- **Public repositories**: Free for unlimited repositories
- **Private repositories**: Included in GitHub Free, Pro, Team plans
- **GitHub Actions minutes**: Limited per plan (Free: 2000 min/month)
- **Storage**: Limited per plan (Free: 500 MB)

## Integration with CI/CD

This module works seamlessly with:
- GitHub Actions (recommended)
- CircleCI
- Travis CI
- Jenkins
- GitLab CI (via mirroring)

## Related Resources

- `github_team_repository` - Grant team access to repository
- `github_repository_collaborator` - Add individual collaborators
- `github_repository_webhook` - Configure webhooks
- `github_actions_secret` - Manage repository secrets

## Version History

- **2025-12-28**: Updated to GitHub provider ~> 6.0
- **2025-12-22**: Initial module creation

## Maintainer

PAUSATF Infrastructure Team
