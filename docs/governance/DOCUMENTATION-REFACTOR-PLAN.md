# Documentation Refactoring Plan

**Date:** December 21, 2025
**Purpose:** Reorganize documentation to integrate GitHub Wiki, Discussions, and Projects

---

## Current State

**28 Markdown Files in Root Directory:**
- Mix of guides, reports, governance docs, and reference materials
- No clear hierarchy
- Hard to navigate for new users
- No integration with GitHub collaborative features

---

## Proposed New Structure

```
pausatf-infrastructure-docs/
│
├── README.md                          # Main entry - Updated with new navigation
├── CHANGELOG.md                       # All infrastructure changes
├── EXECUTIVE-SUMMARY.md               # Non-technical overview
│
├── .github/                           # GitHub configuration
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.yml
│   │   ├── documentation.yml
│   │   └── infrastructure_change.yml
│   ├── PULL_REQUEST_TEMPLATE.md
│   └── SECRETS.md
│
├── docs/                              # Technical Documentation (NEW)
│   │
│   ├── guides/                        # How-to Guides
│   │   ├── 01-cache-implementation-guide.md
│   │   ├── 05-server-migration-guide.md
│   │   ├── 06-cloudflare-configuration-guide.md
│   │   ├── 09-google-workspace-email-security.md
│   │   ├── 10-operational-procedures.md
│   │   ├── 11-database-maintenance.md
│   │   └── 13-digitalocean-optimization-guide.md
│   │
│   ├── reports/                       # Audit and Implementation Reports
│   │   ├── 02-cache-audit-report.md
│   │   ├── 03-cache-verification-report.md
│   │   ├── 04-security-audit-report.md
│   │   ├── 07-performance-optimization-complete.md
│   │   ├── 12-server-rightsizing-analysis.md
│   │   ├── 14-wordpress-security-audit-2025.md
│   │   ├── PHASE1-IMPLEMENTATION-REPORT.md
│   │   └── INFRASTRUCTURE-AS-CODE-UPDATES.md
│   │
│   ├── planning/                      # Roadmaps and Planning
│   │   └── 08-recommended-upgrades-roadmap.md
│   │
│   └── procedures/                    # Specific Procedures
│       └── THEME-DROPDOWN-FIX.md
│
├── runbooks/                          # Operational Runbooks
│   ├── deployment.md
│   └── disaster-recovery.md
│
├── governance/                        # Repository Governance (NEW)
│   ├── CONTRIBUTING.md
│   ├── COMMIT-STANDARDS.md
│   ├── TESTING.md
│   ├── AUTOMATION.md
│   ├── GITHUB-ACTIONS.md
│   ├── GITHUB-WORKFLOW-STRATEGY.md
│   └── GITHUB-WORKFLOW-QUICKSTART.md
│
├── getting-started/                   # Onboarding (NEW)
│   └── GETTING_STARTED.md
│
├── deployment-package/                # Deployment Artifacts
│   ├── data_2025_htaccess
│   ├── purge_cloudflare_cache.sh
│   └── DEPLOYMENT_INSTRUCTIONS.txt
│
├── tests/                             # Documentation Tests
│   └── ...
│
└── Wiki.md                            # Wiki page index (reference)
```

---

## Content Mapping

### docs/guides/ (7 files)
Comprehensive how-to guides for infrastructure operations

- 01-cache-implementation-guide.md
- 05-server-migration-guide.md
- 06-cloudflare-configuration-guide.md
- 09-google-workspace-email-security.md
- 10-operational-procedures.md
- 11-database-maintenance.md
- 13-digitalocean-optimization-guide.md

### docs/reports/ (8 files)
Historical audits, assessments, and implementation summaries

- 02-cache-audit-report.md
- 03-cache-verification-report.md
- 04-security-audit-report.md
- 07-performance-optimization-complete.md
- 12-server-rightsizing-analysis.md
- 14-wordpress-security-audit-2025.md
- PHASE1-IMPLEMENTATION-REPORT.md
- INFRASTRUCTURE-AS-CODE-UPDATES.md

### docs/planning/ (1 file)
Future roadmaps and strategic planning

- 08-recommended-upgrades-roadmap.md

### docs/procedures/ (1 file)
Specific step-by-step procedures

- THEME-DROPDOWN-FIX.md

### governance/ (7 files)
Repository governance and workflow documentation

- CONTRIBUTING.md
- COMMIT-STANDARDS.md
- TESTING.md
- AUTOMATION.md
- GITHUB-ACTIONS.md
- GITHUB-WORKFLOW-STRATEGY.md
- GITHUB-WORKFLOW-QUICKSTART.md

### getting-started/ (1 file)
New contributor onboarding

- GETTING_STARTED.md

---

## Wiki Content Strategy

### What Goes in GitHub Wiki (Quick Reference)

**Infrastructure Quick Reference:**
- Common Commands Cheat Sheet
- SSH Connection Guide
- Server Quick Reference (IPs, hostnames)
- Emergency Contacts
- Cloudflare Cache Purge Quick Guide

**Troubleshooting:**
- Common WordPress Errors
- Database Connection Issues
- Email Delivery Problems
- DNS Troubleshooting
- SSL Certificate Issues

**For Volunteers:**
- How to Report Issues
- How to Request Changes
- Glossary of Terms
- FAQ

**Event Coordination:**
- Pre-Event Checklist
- Post-Event Procedures
- Meet Results Upload Guide

### What Stays in Version Control (Source of Truth)

**Technical Documentation:**
- All architecture guides
- Implementation procedures
- Security audits
- Configuration guides
- Historical reports

---

## GitHub Discussions Categories

**Recommended Categories:**

1. **📢 Announcements** (Maintainers only)
   - Infrastructure updates
   - Scheduled maintenance
   - New features

2. **💡 Ideas**
   - Feature proposals
   - Infrastructure improvements
   - Workflow suggestions

3. **🙏 Q&A**
   - Technical questions
   - How-to questions
   - Troubleshooting help

4. **🏗️ Infrastructure**
   - Infrastructure discussions
   - Change proposals
   - Impact assessments

5. **📚 Documentation**
   - Documentation improvements
   - Content suggestions
   - Clarity feedback

6. **💬 General**
   - Everything else
   - Community chat
   - Off-topic

---

## GitHub Projects Structure

### Project 1: Infrastructure Roadmap (Organization-level)
**Purpose:** Track major initiatives across all 4 repos

**Views:**
- Board: Kanban (Backlog, Planned, In Progress, Done)
- Table: Sortable list with custom fields
- Roadmap: Timeline view

**Custom Fields:**
- Priority (Critical/High/Medium/Low)
- Quarter (Q1/Q2/Q3/Q4)
- Repository (terraform/ansible/scripts/docs)
- Area (wordpress/database/cloudflare/server)
- Effort (Small/Medium/Large)

**Linked Repos:**
- pausatf-infrastructure-docs
- pausatf-terraform
- pausatf-ansible
- pausatf-scripts

### Project 2: Documentation Maintenance (Repo-level)
**Purpose:** Track documentation quality and updates

**Views:**
- Board: Review Status (Needs Review, In Progress, Reviewed)
- Table: By last update date

**Custom Fields:**
- Last Reviewed (Date)
- Review Priority (High/Medium/Low)
- Doc Type (Guide/Report/Procedure)
- Status (Current/Needs Update/Outdated)

### Project 3: Event Season Prep (Optional)
**Purpose:** Infrastructure readiness for events

**Views:**
- Timeline: Event dates
- Checklist: Pre-event tasks

**Custom Fields:**
- Event Name
- Event Date
- Preparation Status

---

## Migration Steps

### Phase 1: Create Directory Structure (Week 1)

```bash
# Create new directories
mkdir -p docs/guides docs/reports docs/planning docs/procedures
mkdir -p governance getting-started

# Move files to new locations
mv 01-cache-implementation-guide.md docs/guides/
mv 02-cache-audit-report.md docs/reports/
# ... etc
```

### Phase 2: Update All Links (Week 1)

**Files to Update:**
- README.md (main navigation)
- All internal cross-references
- CHANGELOG.md references
- Issue/PR templates

**Link Format:**
```markdown
<!-- Old -->
[Cache Guide](01-cache-implementation-guide.md)

<!-- New -->
[Cache Guide](docs/guides/01-cache-implementation-guide.md)
```

### Phase 3: Create Wiki Pages (Week 2)

**Initial Wiki Pages:**
1. Home (overview and navigation)
2. Common Commands
3. Server Quick Reference
4. Troubleshooting Guide
5. FAQ
6. Glossary
7. For Volunteers

### Phase 4: Setup GitHub Projects (Week 2)

1. Create "Infrastructure Roadmap" project
2. Create "Documentation Maintenance" project
3. Import existing issues
4. Configure views and fields

### Phase 5: Configure Discussions (Week 2)

1. Create discussion categories
2. Pin welcome discussion
3. Seed with 2-3 initial discussions
4. Announce to team

### Phase 6: Update Documentation (Week 3)

1. Add "Where to Find What" guide
2. Update README with all new features
3. Add Wiki links throughout docs
4. Create navigation aids

---

## Updated README Structure

### Proposed README Sections

1. **Header**
   - Title, badges, description
   - Quick links (Discussions, Wiki, Projects, Issues)

2. **Navigation Hub**
   - Where to find what
   - Quick decision tree
   - Link to Quick Start guide

3. **Documentation Index**
   - Organized by category
   - Links to docs/ subdirectories
   - Link to Wiki for quick reference

4. **GitHub Features**
   - How to use Discussions
   - How to use Wiki
   - How to use Projects
   - How to use Issues

5. **For Different Audiences**
   - For Infrastructure Team
   - For Volunteers
   - For Board Members
   - For New Contributors

6. **Server Environment** (existing)

7. **Current Status** (existing)

8. **Quick Operations** (existing)

9. **Troubleshooting** (existing)
   - Link to Wiki troubleshooting pages

10. **Contributing** (enhanced)
    - Link to governance/
    - Link to Quick Start guide

---

## Link Integration Strategy

### Cross-Linking Between Resources

**In Documentation → Wiki:**
```markdown
For quick reference, see the [Wiki: Common Commands](https://github.com/pausatf/pausatf-infrastructure-docs/wiki/Common-Commands).

Full documentation: [Complete Guide](docs/guides/01-cache-implementation-guide.md)
```

**In Wiki → Documentation:**
```markdown
This is a quick reference. For complete details, see:
[Full Documentation](https://github.com/pausatf/pausatf-infrastructure-docs/blob/main/docs/guides/01-cache-implementation-guide.md)
```

**In Discussions → Everything:**
```markdown
Related:
- 📖 Documentation: [Server Migration Guide](link)
- 📚 Wiki: [SSH Quick Reference](link)
- 🎯 Issue: #123
- 📋 Project: Infrastructure Roadmap
```

**In README → All Features:**
```markdown
## Where to Find Information

| I need... | Go to... |
|-----------|----------|
| Quick commands | [Wiki](https://github.com/pausatf/pausatf-infrastructure-docs/wiki) |
| Technical guides | [docs/guides/](docs/guides/) |
| Ask a question | [Discussions (Q&A)](https://github.com/pausatf/pausatf-infrastructure-docs/discussions) |
| Report a bug | [Issues](https://github.com/pausatf/pausatf-infrastructure-docs/issues) |
| See roadmap | [Projects](https://github.com/orgs/pausatf/projects) |
```

---

## Benefits of Refactored Structure

### Better Organization
- Clear hierarchy (guides vs reports vs procedures)
- Easier to navigate
- Logical grouping

### Enhanced Discoverability
- README as central hub
- Wiki for quick access
- Discussions for community
- Projects for visibility

### Improved Collaboration
- Lower barrier (Wiki, Discussions)
- Clear workflows (templates, guides)
- Better tracking (Projects)

### Scalability
- Room to grow
- Clear patterns
- Easy to maintain

---

## Success Metrics

**After 1 Month:**
- ✅ All files in new structure
- ✅ All links updated
- ✅ 5-10 wiki pages created
- ✅ 2 GitHub Projects active
- ✅ 10+ Discussions started

**After 3 Months:**
- ✅ 20+ wiki pages
- ✅ Regular Discussion activity (5+ per week)
- ✅ Issues using templates (90%+)
- ✅ Projects actively used for tracking
- ✅ 3+ volunteer contributors

**After 6 Months:**
- ✅ Comprehensive wiki coverage
- ✅ Active community in Discussions
- ✅ Cross-repository coordination via Projects
- ✅ Documented workflows being followed
- ✅ New contributors onboarding smoothly

---

## Implementation Checklist

### Immediate (This Week)
- [ ] Review this plan with team
- [ ] Make decision on directory structure
- [ ] Create new directories
- [ ] Begin file migration

### Short-term (Next 2 Weeks)
- [ ] Complete file migration
- [ ] Update all internal links
- [ ] Create initial Wiki pages
- [ ] Setup GitHub Projects
- [ ] Configure Discussion categories

### Ongoing
- [ ] Maintain Wiki content
- [ ] Monitor Discussions
- [ ] Update Projects
- [ ] Review metrics monthly

---

**Status:** Proposal - Awaiting Team Review
**Next Step:** Get approval from infrastructure team
**Timeline:** 3-4 weeks for complete implementation
