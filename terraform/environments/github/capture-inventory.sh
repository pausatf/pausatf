#!/usr/bin/env bash
# Capture GitHub repository inventory for PAUSATF organization
# Generated: $(date -u +"%Y-%m-%d %H:%M:%S UTC")

set -euo pipefail

OUTPUT_DIR="$(cd "$(dirname "$0")" && pwd)"
OUTPUT_FILE="${OUTPUT_DIR}/inventory.yml"
TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

echo "Capturing GitHub inventory for pausatf organization..."

# Get repository data
REPO_DATA=$(gh repo view pausatf/pausatf --json name,description,visibility,isPrivate,hasIssuesEnabled,hasWikiEnabled,hasProjectsEnabled,hasDiscussionsEnabled,mergeCommitAllowed,squashMergeAllowed,rebaseMergeAllowed,deleteBranchOnMerge,defaultBranchRef,createdAt,updatedAt,pushedAt,diskUsage,stargazerCount,forkCount,licenseInfo,homepageUrl)

# Get topics
TOPICS=$(gh api repos/pausatf/pausatf/topics -H "Accept: application/vnd.github.mercy-preview+json" | jq -r '.names // []')

# Get branch protection if enabled
BRANCH_PROTECTION=$(gh api repos/pausatf/pausatf/branches/main/protection 2>/dev/null || echo '{"enabled": false}')

# Get collaborators with permissions
COLLABORATORS=$(gh api repos/pausatf/pausatf/collaborators 2>/dev/null | jq -c '[.[] | {login: .login, permissions: .permissions}]' || echo '[]')

# Get recent commits
RECENT_COMMITS=$(gh api repos/pausatf/pausatf/commits?per_page=10 2>/dev/null | jq -c '[.[] | {sha: .sha[0:7], message: (.commit.message | split("\n")[0]), author: .commit.author.name, date: .commit.author.date}]' || echo '[]')

# Get open issues count
OPEN_ISSUES=$(gh issue list --repo pausatf/pausatf --state open --limit 1000 2>/dev/null | wc -l | tr -d ' ' || echo '0')

# Get open PRs count
OPEN_PRS=$(gh pr list --repo pausatf/pausatf --state open --limit 1000 2>/dev/null | wc -l | tr -d ' ' || echo '0')

# Write YAML output
cat > "$OUTPUT_FILE" <<EOF
# GitHub Repository Inventory
# Captured: ${TIMESTAMP}
# Organization: pausatf
# DO NOT EDIT MANUALLY - regenerate with capture-inventory.sh

repository:
  name: $(echo "$REPO_DATA" | jq -r '.name')
  description: $(echo "$REPO_DATA" | jq -r '.description')
  visibility: $(echo "$REPO_DATA" | jq -r '.visibility')
  is_private: $(echo "$REPO_DATA" | jq -r '.isPrivate')

  url: https://github.com/pausatf/pausatf
  homepage: $(echo "$REPO_DATA" | jq -r '.homepageUrl // "null"')

  created_at: $(echo "$REPO_DATA" | jq -r '.createdAt')
  updated_at: $(echo "$REPO_DATA" | jq -r '.updatedAt')
  pushed_at: $(echo "$REPO_DATA" | jq -r '.pushedAt')

  default_branch: $(echo "$REPO_DATA" | jq -r '.defaultBranchRef.name')

  # Repository size and activity
  size_kb: $(echo "$REPO_DATA" | jq -r '.diskUsage')
  stars: $(echo "$REPO_DATA" | jq -r '.stargazerCount')
  forks: $(echo "$REPO_DATA" | jq -r '.forkCount')
  open_issues: ${OPEN_ISSUES}
  open_pull_requests: ${OPEN_PRS}

  # Features
  features:
    issues_enabled: $(echo "$REPO_DATA" | jq -r '.hasIssuesEnabled')
    wiki_enabled: $(echo "$REPO_DATA" | jq -r '.hasWikiEnabled')
    projects_enabled: $(echo "$REPO_DATA" | jq -r '.hasProjectsEnabled')
    discussions_enabled: $(echo "$REPO_DATA" | jq -r '.hasDiscussionsEnabled')

  # Merge settings
  merge_settings:
    allow_merge_commit: $(echo "$REPO_DATA" | jq -r '.mergeCommitAllowed')
    allow_squash_merge: $(echo "$REPO_DATA" | jq -r '.squashMergeAllowed')
    allow_rebase_merge: $(echo "$REPO_DATA" | jq -r '.rebaseMergeAllowed')
    delete_branch_on_merge: $(echo "$REPO_DATA" | jq -r '.deleteBranchOnMerge')

  # License
  license: $(echo "$REPO_DATA" | jq -r '.licenseInfo.spdxId // "none"')

# Repository topics
topics: ${TOPICS}

# Collaborators and permissions
collaborators: ${COLLABORATORS}

# Recent commits (last 10)
recent_commits: ${RECENT_COMMITS}

# Branch protection
branch_protection:
  enabled: $(if echo "$BRANCH_PROTECTION" | jq -e '.enabled == false' > /dev/null 2>&1; then echo "false"; else echo "true"; fi)
EOF

# Pretty print the YAML
if command -v yq &> /dev/null; then
  yq eval -i '.' "$OUTPUT_FILE"
fi

echo "✅ Inventory captured successfully!"
echo "📄 Output: $OUTPUT_FILE"
echo ""
echo "Summary:"
echo "  Repository: pausatf/pausatf"
echo "  Size: $(echo "$REPO_DATA" | jq -r '.diskUsage') KB"
echo "  Stars: $(echo "$REPO_DATA" | jq -r '.stargazerCount')"
echo "  Forks: $(echo "$REPO_DATA" | jq -r '.forkCount')"
echo "  Open Issues: ${OPEN_ISSUES}"
echo "  Open PRs: ${OPEN_PRS}"
echo "  Last Push: $(echo "$REPO_DATA" | jq -r '.pushedAt')"
