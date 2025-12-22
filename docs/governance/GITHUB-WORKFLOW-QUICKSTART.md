# GitHub Workflow Quick Start Guide

**For:** PAUSATF Infrastructure Team & Volunteers
**Created:** December 21, 2025
**Purpose:** Quick guide to using GitHub's collaboration features

---

## What's New?

We've enabled GitHub's collaboration features to make it easier for everyone to contribute:

✅ **GitHub Wiki** - Quick reference guides (no Git required!)
✅ **GitHub Discussions** - Ask questions, share ideas (no Git required!)
✅ **Issue Templates** - Structured way to report bugs or request changes
✅ **Pull Request Templates** - Consistent documentation updates
✅ **GitHub Projects** - Visual work tracking (coming soon)

---

## Quick Navigation

| I want to... | Use this... | Skill Level |
|--------------|-------------|-------------|
| Ask a question | [GitHub Discussions](#github-discussions) | 🟢 Beginner |
| Report a website bug | [GitHub Issues](#creating-issues) | 🟢 Beginner |
| Find quick reference info | [GitHub Wiki](#github-wiki) | 🟢 Beginner |
| Update quick reference info | [GitHub Wiki](#editing-wiki-pages) | 🟡 Intermediate |
| Request infrastructure change | [GitHub Issues](#creating-issues) | 🟡 Intermediate |
| Update technical documentation | [Pull Requests](#pull-requests) | 🔴 Advanced |
| Track project work | [GitHub Projects](#github-projects) | 🟡 Intermediate |

---

## GitHub Discussions

**Perfect for:** Questions, ideas, brainstorming, proposals

**URL:** https://github.com/pausatf/pausatf-infrastructure-docs/discussions

### How to Start a Discussion

1. Go to **Discussions** tab in the repo
2. Click **New discussion**
3. Choose a category:
   - **💡 Ideas** - Propose new features or improvements
   - **🙏 Q&A** - Ask questions (others can upvote answers)
   - **📢 Announcements** - Team announcements
   - **🏗️ Infrastructure** - Discuss infrastructure changes
   - **📚 Documentation** - Discuss documentation improvements
   - **💬 General** - Everything else

4. Write your question/idea
5. Click **Start discussion**

### Discussion Etiquette

- ✅ Search existing discussions first
- ✅ Be specific in your title
- ✅ Add context and details
- ✅ Mark helpful answers with ✓
- ✅ Close discussion when resolved

### Example Discussions

**Good Question:**
```
Title: How do I purge Cloudflare cache for a specific race result?
Category: Q&A

I uploaded new results for the 2025 Winter Series 5K but the old
results still show on the website. I know we have a cache purge
script but I'm not sure how to use it. Can someone point me to
the right procedure?
```

**Good Idea:**
```
Title: Add automated backups before major events
Category: Ideas

During our last championship meet, we had heavy traffic and I was
worried about the database. What if we automated a pre-event backup
script that runs the day before major meets? We could trigger it
from our event calendar.
```

---

## GitHub Wiki

**Perfect for:** Quick reference, FAQs, how-to guides, troubleshooting

**URL:** https://github.com/pausatf/pausatf-infrastructure-docs/wiki

### Reading Wiki Pages

1. Go to **Wiki** tab in the repo
2. Browse the sidebar or search
3. Click any page to read

### Editing Wiki Pages (Direct - No PR Required!)

**Note:** You need write access to the repository

1. Navigate to the wiki page
2. Click **Edit** button
3. Make your changes (uses Markdown)
4. Add a commit message describing your change
5. Click **Save Page**

That's it! No branches, no pull requests needed for wiki.

### Creating New Wiki Pages

1. Go to **Wiki** tab
2. Click **New Page**
3. Enter page title
4. Write content in Markdown
5. Click **Save Page**

### Wiki Best Practices

- ✅ Keep pages short and focused (link to detailed docs for deep-dives)
- ✅ Use clear, descriptive titles
- ✅ Add navigation links between related pages
- ✅ Update the Home page when adding new pages
- ✅ Link back to version-controlled docs for source of truth
- ⚠️ Don't put sensitive information in wiki (API keys, passwords)

### Suggested Wiki Pages (To Create)

**Infrastructure Quick Reference:**
- Common wp-cli Commands
- SSH Connection Quick Start
- Cloudflare Cache Purge Guide
- Emergency Contacts
- Server Quick Reference (IPs, hostnames)

**Troubleshooting:**
- Common WordPress Issues
- Database Connection Errors
- Email Delivery Problems
- DNS Propagation Delays

**For Volunteers:**
- How to Report Issues
- How to Request Infrastructure Changes
- How to Access Documentation
- Glossary of Technical Terms

**Event Coordination:**
- Pre-Event Infrastructure Checklist
- Post-Event Data Backup Procedure
- Meet Results Upload Guide

---

## GitHub Issues

**Perfect for:** Bug reports, feature requests, tracking work

**URL:** https://github.com/pausatf/pausatf-infrastructure-docs/issues

### Creating Issues

1. Go to **Issues** tab
2. Click **New issue**
3. Choose a template:
   - 🐛 **Bug Report** - Something is broken
   - 🏗️ **Infrastructure Change** - Request a change to servers/DNS/etc.
   - 📝 **Documentation** - Request doc updates
   - 🔓 **Blank issue** - For everything else

4. Fill out the template
5. Click **Submit new issue**

### Issue Templates Explained

#### Bug Report Template
Use when: Website is down, feature broken, error messages

Required information:
- Severity (Critical/High/Medium/Low)
- Component (Website/Database/Email/etc.)
- Environment (Production/Staging)
- Steps to reproduce
- Expected vs. actual behavior

#### Infrastructure Change Template
Use when: Need server config change, DNS update, new WordPress plugin

Required information:
- Infrastructure area (WordPress/Database/DNS/etc.)
- Environment (Production/Staging/Both)
- Priority (Critical/High/Medium/Low)
- Change description
- Impact assessment
- Testing plan

#### Documentation Template
Use when: Docs are wrong, missing, or unclear

Required information:
- Type of update (Missing/Incorrect/Outdated/Unclear)
- Affected document(s)
- What's wrong
- Suggested improvement

### Working with Issues

**Add Labels:** Helps organize (e.g., `bug`, `enhancement`, `urgent`)
**Assign People:** Who's working on it?
**Add to Project:** Track progress visually
**Link PRs:** Connect fixes to issues
**Close when Done:** With summary comment

---

## Pull Requests

**Perfect for:** Updating technical documentation, code changes

**URL:** https://github.com/pausatf/pausatf-infrastructure-docs/pulls

### Creating a Pull Request (PR)

**For Technical Documentation Changes:**

1. **Create a branch:**
   ```bash
   git checkout -b docs/update-security-audit
   ```

2. **Make your changes:**
   - Edit files
   - Test links
   - Check formatting

3. **Commit changes:**
   ```bash
   git add .
   git commit -m "[Docs] Update security audit procedure"
   ```

4. **Push branch:**
   ```bash
   git push origin docs/update-security-audit
   ```

5. **Open PR on GitHub:**
   - Go to repository
   - Click **Pull requests** → **New pull request**
   - Choose your branch
   - Fill out PR template
   - Click **Create pull request**

6. **Wait for review:**
   - Address any feedback
   - Make requested changes
   - Re-request review

7. **Merge when approved:**
   - Maintainer will merge
   - Your changes go live!

### PR Best Practices

- ✅ Keep PRs small and focused (one topic per PR)
- ✅ Fill out the template completely
- ✅ Link related issues (`Fixes #123`)
- ✅ Update CHANGELOG.md
- ✅ Test your changes
- ✅ Respond to review comments promptly
- ⚠️ Don't force-push after review starts

---

## GitHub Projects

**Perfect for:** Visual work tracking, roadmap planning, sprint management

**URL:** https://github.com/orgs/pausatf/projects

### Using Project Boards

**View Work:**
1. Go to **Projects** tab
2. Select a project (e.g., "Infrastructure Roadmap")
3. See cards in columns (To Do, In Progress, Done)

**Add Issues to Projects:**
1. Open an issue
2. Click **Projects** in right sidebar
3. Select project and status
4. Issue appears in project board

**Update Status:**
- Drag cards between columns
- Or update status in issue sidebar

### Recommended Projects

**Infrastructure Roadmap (Organization-level)**
- Links work across all 4 repos
- Quarterly planning
- Major initiatives

**Documentation Maintenance**
- Doc review schedule
- Update requests
- Stale content tracking

**Event Season Preparation**
- Pre-event checklists
- Infrastructure readiness
- Backup verification

---

## Labels

**Purpose:** Organize and filter issues/PRs

### Standard Labels

**Type:**
- `bug` - Something is broken
- `enhancement` - New feature or improvement
- `documentation` - Documentation updates
- `security` - Security-related
- `question` - Question or discussion needed

**Priority:**
- `critical` - Service down, data loss risk
- `high` - Major impact
- `medium` - Moderate impact
- `low` - Minor issue or enhancement

**Status:**
- `needs-triage` - Needs initial review
- `in-progress` - Actively being worked on
- `blocked` - Waiting on something
- `ready-for-review` - Ready for PR review

**Area:**
- `wordpress` - WordPress-related
- `cloudflare` - CDN/caching
- `digitalocean` - Infrastructure/servers
- `database` - MySQL/database
- `dns` - DNS configuration

**Repository:**
- `terraform` - Affects Terraform repo
- `ansible` - Affects Ansible repo
- `scripts` - Affects scripts repo
- `docs` - Documentation repo

### How to Add Labels

**On Issues/PRs:**
1. Click **Labels** in right sidebar
2. Select appropriate labels
3. Click away to save

---

## Workflow Decision Tree

### "Where Should I Go?"

```
What do you want to do?

├─ I have a QUESTION
│  └─ GitHub Discussions (Q&A category)
│
├─ I want to PROPOSE an idea
│  └─ GitHub Discussions (Ideas category)
│
├─ I need QUICK REFERENCE info
│  └─ GitHub Wiki (read)
│
├─ I want to UPDATE quick reference
│  └─ GitHub Wiki (edit directly - no PR needed)
│
├─ Website is BROKEN
│  └─ GitHub Issues (Bug Report template)
│
├─ I need an INFRASTRUCTURE CHANGE
│  └─ GitHub Issues (Infrastructure Change template)
│
├─ Documentation is WRONG
│  └─ GitHub Issues (Documentation template)
│     OR edit directly via Pull Request
│
└─ I want to see WHAT'S BEING WORKED ON
   └─ GitHub Projects (board view)
```

### "How Should I Contribute?"

```
What's your Git skill level?

├─ BEGINNER (no Git experience)
│  ├─ Ask questions → GitHub Discussions
│  ├─ Report issues → GitHub Issues (use templates)
│  └─ Quick edits → GitHub Wiki
│
├─ INTERMEDIATE (some Git experience)
│  ├─ Edit wiki → Direct wiki editing
│  ├─ Track work → GitHub Projects
│  └─ Small doc changes → GitHub web editor + PR
│
└─ ADVANCED (comfortable with Git)
   ├─ Technical docs → Branch + PR workflow
   ├─ Code changes → Branch + PR + tests
   └─ Infrastructure → Terraform/Ansible + docs + PR
```

---

## Common Scenarios

### Scenario 1: Race Results Not Updating

**You:** Event coordinator uploading results
**Problem:** New results uploaded but old ones still showing

**Steps:**
1. Go to **Discussions** → **Q&A**
2. Search for "cache" or "results"
3. If no answer, ask: "How do I clear cache for race results?"
4. Infrastructure team replies with wiki link
5. Follow wiki procedure: "Cloudflare Cache Purge Guide"
6. Mark answer as ✓ Accepted

### Scenario 2: WordPress Admin Loading Slowly

**You:** Website administrator
**Problem:** WordPress admin dashboard very slow

**Steps:**
1. Go to **Issues** → **New issue**
2. Choose "🐛 Bug Report" template
3. Fill out:
   - Severity: Medium
   - Component: WordPress Admin
   - Description: "Dashboard takes 30+ seconds to load"
   - Impact: "Can't update pages quickly"
4. Submit issue
5. Infrastructure team investigates
6. They find and fix the issue
7. Issue closed with explanation

### Scenario 3: Need Pre-Event Checklist

**You:** Board member planning championship meet
**Problem:** Want to ensure infrastructure ready

**Steps:**
1. Go to **Discussions** → **Ideas**
2. Propose: "Create pre-event infrastructure checklist"
3. Infrastructure team discusses
4. Creates issue to track work
5. Adds wiki page: "Pre-Event Infrastructure Checklist"
6. Links wiki page in discussion
7. Discussion marked as resolved

### Scenario 4: Found Outdated Documentation

**You:** Volunteer reading setup guide
**Problem:** SSH instructions don't match current server

**Steps:**
1. Go to **Issues** → **New issue**
2. Choose "📝 Documentation" template
3. Fill out:
   - Type: Outdated information
   - Document: 05-server-migration-guide.md
   - Issue: "SSH hostname is ftp.pausatf.org but should be prod.pausatf.org"
   - Suggestion: "Update all references to use prod.pausatf.org"
4. Submit issue
5. Maintainer fixes via PR
6. Issue closed, docs updated

---

## Best Practices Summary

### DO ✅

- Search before posting (avoid duplicates)
- Use templates when provided
- Be specific and detailed
- Add labels to issues
- Link related items (#123, PR #45)
- Update work status in projects
- Close issues when resolved
- Say thanks when helped!

### DON'T ❌

- Post sensitive info (passwords, keys)
- Open duplicate issues
- Skip template questions
- Leave issues/PRs abandoned
- Force-push to PR after review
- Make unrelated changes in same PR
- Merge your own PRs without review

---

## Getting Help

### For GitHub Help

- **GitHub Docs:** https://docs.github.com/
- **Discussions:** Ask in Q&A category
- **Issues:** Create documentation issue

### For PAUSATF Infrastructure Help

- **Quick Questions:** GitHub Discussions (Q&A)
- **Longer Issues:** GitHub Issues
- **Emergency:** Use emergency contact info in Wiki

### For Git Help

- **Git Basics:** https://git-scm.com/book/en/v2
- **GitHub Desktop:** https://desktop.github.com/ (GUI alternative)
- **Ask Team:** Discussions → Q&A

---

## Next Steps

### For Infrastructure Team

1. ✅ Review this guide
2. ✅ Create initial Wiki pages (see suggestions above)
3. ✅ Set up Discussion categories
4. ✅ Create first GitHub Project
5. ✅ Add repository labels
6. ✅ Train volunteers on workflow

### For Volunteers

1. ✅ Read this guide
2. ✅ Explore GitHub Discussions
3. ✅ Browse the Wiki
4. ✅ Try creating a Discussion (Q&A)
5. ✅ Watch for announcements

### For Board Members

1. ✅ Review the GitHub Projects board
2. ✅ Understand workflow at high level
3. ✅ Know where to ask questions (Discussions)
4. ✅ Provide feedback on process

---

## Metrics and Success

### How We'll Measure Success

- **Discussions Activity:** Questions asked and answered
- **Wiki Usage:** Page views, edits
- **Issue Resolution Time:** How fast we fix things
- **Contribution Growth:** More people contributing
- **Documentation Quality:** Fewer "docs are wrong" issues

### Quarterly Review

Every 3 months, we'll review:
- What's working well?
- What's confusing?
- What should we improve?
- Are we meeting volunteer needs?

---

## FAQ

**Q: Do I need a GitHub account?**
A: To read, no. To post/contribute, yes (free account).

**Q: What if I break something?**
A: Git tracks everything. We can undo mistakes easily.

**Q: Can I edit docs directly on GitHub?**
A: Wiki: Yes! Technical docs: Via PR only (for quality control).

**Q: How do I know what needs work?**
A: Check GitHub Projects boards and `help-wanted` label.

**Q: What if my question isn't answered in Discussions?**
A: Post it! The team monitors and will respond.

**Q: Can I contribute without coding skills?**
A: Absolutely! Documentation, wiki pages, and testing all help.

---

## Quick Reference Links

| Resource | URL |
|----------|-----|
| **Discussions** | https://github.com/pausatf/pausatf-infrastructure-docs/discussions |
| **Wiki** | https://github.com/pausatf/pausatf-infrastructure-docs/wiki |
| **Issues** | https://github.com/pausatf/pausatf-infrastructure-docs/issues |
| **Projects** | https://github.com/orgs/pausatf/projects |
| **Main Docs** | https://github.com/pausatf/pausatf-infrastructure-docs |
| **This Guide** | https://github.com/pausatf/pausatf-infrastructure-docs/blob/main/GITHUB-WORKFLOW-QUICKSTART.md |

---

**Last Updated:** December 21, 2025
**Maintainer:** PAUSATF Infrastructure Team
**Questions?** Ask in [GitHub Discussions (Q&A)](https://github.com/pausatf/pausatf-infrastructure-docs/discussions)
