# Theme Repository GitHub Workflows Setup

**Date:** December 21, 2025
**Author:** Infrastructure Team
**Status:** Completed
**Related Repositories:**
- [pausatf-theme-thesource](https://github.com/pausatf/pausatf-theme-thesource) (Parent Theme)
- [pausatf-theme-thesource-child](https://github.com/pausatf/pausatf-theme-thesource-child) (Child Theme)

---

## Executive Summary

Implemented comprehensive GitHub workflows and best practices for both WordPress theme repositories (parent and child themes). This ensures code quality, prevents accidental modifications to the parent theme, and provides clear contribution guidelines for volunteers.

### Key Deliverables

✅ **Child Theme Repository** (Active Development)
- Issue templates (bug reports, feature requests)
- Pull request template with comprehensive checklists
- Automated CI/CD workflows (PHP linting, CSS linting)
- CONTRIBUTING.md with detailed development guidelines
- composer.json for PHP dependency management

✅ **Parent Theme Repository** (Reference Only)
- CONTRIBUTING.md explaining "do not modify" policy
- Issue template configuration redirecting to child theme
- Parent theme update tracking template
- Clear documentation on theme architecture

---

## Background

### Challenge

The PAUSATF WordPress themes were recently moved to GitHub version control, but lacked:
- Contribution guidelines for volunteers
- Quality assurance workflows
- Clear separation between parent and child theme development
- Automated testing for code changes

### Solution

Implemented GitHub workflows following the same best practices established in the infrastructure-docs repository:
- Structured issue templates
- Comprehensive PR templates
- Automated CI/CD pipelines
- Clear documentation

---

## Implementation Details

### Child Theme Repository

#### 1. Issue Templates

Created two YAML-based issue templates:

**Bug Report Template** (`.github/ISSUE_TEMPLATE/theme_bug.yml`)
- Severity levels (Critical, High, Medium, Low)
- Location selector (Homepage, Blog, Club listings, etc.)
- Browser and device information
- Steps to reproduce
- Screenshots support

**Feature Request Template** (`.github/ISSUE_TEMPLATE/theme_feature.yml`)
- Feature type classification
- Priority levels
- Problem statement and proposed solution
- User benefits analysis
- Multi-device support planning

#### 2. Pull Request Template

Comprehensive PR template (`.github/PULL_REQUEST_TEMPLATE.md`) with sections for:

**Change Information:**
- Type of change (bug fix, feature, enhancement, etc.)
- Related issue linking
- Detailed changes list

**Testing Requirements:**
- Desktop browser testing (Chrome, Firefox, Safari, Edge)
- Mobile testing (iOS Safari, Android Chrome)
- Page-specific testing checklist

**Code Quality Checks:**
- WordPress coding standards compliance
- PHP syntax validation
- CSS validation
- JavaScript error checking

**Performance & Accessibility:**
- Page load impact assessment
- Image optimization verification
- ARIA labels and keyboard navigation
- WCAG AA color contrast compliance

**Deployment Checklist:**
- Staging environment testing
- Backup and rollback procedures
- Post-deployment monitoring

#### 3. Automated Workflows

**PHP Linting Workflow** (`.github/workflows/php-lint.yml`)

Three jobs running on every PR and push:

1. **PHP Syntax Check**
   - Tests against PHP 7.4, 8.0, and 8.1
   - Validates all PHP files for syntax errors
   - Uses matrix strategy for multi-version testing

2. **WordPress Coding Standards (PHPCS)**
   - Checks against WordPress coding standards
   - Uses wp-coding-standards/wpcs package
   - Continues on error (informational)

3. **PHP Compatibility Check**
   - Verifies PHP 7.4+ compatibility
   - Uses PHPCompatibility ruleset
   - Ensures forward compatibility

**CSS Linting Workflow** (`.github/workflows/css-lint.yml`)

Four jobs for comprehensive CSS validation:

1. **StyleLint**
   - Modern CSS linting
   - Standard configuration with PAUSATF-specific rules
   - Ignores vendor directories

2. **W3C CSS Validator**
   - Validates against W3C CSS standards
   - Checks all CSS files via W3C API
   - Informational (continues on error)

3. **CSS Complexity Check**
   - Uses Parker CSS analysis tool
   - Identifies overly complex selectors
   - Helps maintain performance

4. **Duplicate Selector Detection**
   - Finds duplicate CSS selectors
   - Helps reduce CSS bloat
   - Improves maintainability

#### 4. CONTRIBUTING.md

Comprehensive 200+ line contribution guide covering:

**Getting Started:**
- Prerequisites (WordPress, PHP, Git)
- Development environment setup
- Parent/child theme relationship

**Development Workflow:**
- Issue creation process
- Branch naming conventions
- Making changes (what goes where)
- File organization

**Coding Standards:**
- PHP (WordPress coding standards)
- CSS (BEM-like naming)
- JavaScript (jQuery patterns)

**Testing Requirements:**
- Browser testing matrix
- Device testing checklist
- Page-specific testing
- Functionality testing
- Performance testing
- Accessibility testing

**Submitting Changes:**
- Commit message format
- Push and PR creation process
- Code review expectations
- Deployment procedures

**Theme Architecture:**
- Parent-child relationship explanation
- WordPress theme loading order
- Key customizations overview
- Common hooks used

#### 5. composer.json

PHP dependency management:
- Requires PHP 7.4+
- Dev dependencies for linting tools
- Custom scripts for lint, lint-fix, compat
- Configured for PHPCS installer plugin

### Parent Theme Repository

#### 1. CONTRIBUTING.md

Clear documentation explaining:
- **Do not modify this repository** policy
- Link to child theme for all development
- When parent theme would be updated (security, bug fixes)
- Theme architecture diagram
- Contact information

#### 2. Issue Template Configuration

**config.yml** (`.github/ISSUE_TEMPLATE/config.yml`)

Disables blank issues and provides contact links:
- Theme customization → child theme repository
- Development guidelines → child theme CONTRIBUTING.md
- Questions → infrastructure-docs discussions
- Elegant Themes support → vendor contact

**Parent Theme Update Template**

Structured template for tracking Elegant Themes releases:
- Version information
- Update type (Security, Bug Fix, Feature, etc.)
- Changes summary
- Child theme compatibility assessment
- Testing plan
- Update checklist (11 steps)
- Rollback plan

---

## Technical Specifications

### Workflow Triggers

All workflows trigger on:
- Pull requests to main branch
- Direct pushes to main branch
- Only when relevant files change (path filtering)

### File Path Filters

**PHP Linting:**
```yaml
paths:
  - '**.php'
  - '.github/workflows/php-lint.yml'
```

**CSS Linting:**
```yaml
paths:
  - '**.css'
  - '.github/workflows/css-lint.yml'
```

### PHP Version Matrix

```yaml
strategy:
  matrix:
    php-version: ['7.4', '8.0', '8.1']
```

Ensures compatibility across:
- PHP 7.4 (current production)
- PHP 8.0 (next upgrade target)
- PHP 8.1 (future-proofing)

### Node.js Version

```yaml
node-version: '20'
```

Uses LTS version for CSS tooling.

---

## Repository Structure

### Child Theme Repository

```
pausatf-theme-thesource-child/
├── .github/
│   ├── ISSUE_TEMPLATE/
│   │   ├── theme_bug.yml
│   │   └── theme_feature.yml
│   ├── workflows/
│   │   ├── css-lint.yml
│   │   └── php-lint.yml
│   └── PULL_REQUEST_TEMPLATE.md
├── css/
│   ├── Blue/
│   ├── Light/
│   └── Red/
├── images/
├── includes/
├── ClubsPHP.php
├── composer.json
├── CONTRIBUTING.md
├── functions.php
├── header.php
├── footer.php
├── single.php
├── page-blog.php
├── style.css
└── README.md
```

### Parent Theme Repository

```
pausatf-theme-thesource/
├── .github/
│   └── ISSUE_TEMPLATE/
│       ├── config.yml
│       └── parent_theme_update.yml
├── CONTRIBUTING.md
├── README.md
└── (466 theme files - unmodified)
```

---

## Workflow Benefits

### For Contributors

✅ **Clear Guidelines**
- Know exactly how to contribute
- Understand parent vs. child theme
- Follow WordPress best practices

✅ **Automated Feedback**
- Get instant feedback on code quality
- Catch errors before review
- Ensure compatibility across PHP versions

✅ **Lower Barrier to Entry**
- Structured templates guide contributions
- Clear testing requirements
- Documentation answers common questions

### For Maintainers

✅ **Quality Assurance**
- Automated linting catches issues early
- Consistent code standards
- Comprehensive PR checklists

✅ **Time Savings**
- Less time reviewing for basic issues
- Templates ensure complete information
- Automated testing reduces manual work

✅ **Risk Reduction**
- Parent theme protected from modifications
- Testing requirements prevent broken deployments
- Clear rollback procedures

### For PAUSATF Organization

✅ **Professional Standards**
- Matches industry best practices
- Demonstrates technical maturity
- Easier to onboard volunteers

✅ **Maintainability**
- Clear separation of concerns
- Well-documented codebase
- Sustainable long-term

✅ **Compliance**
- WordPress coding standards
- Accessibility standards (WCAG AA)
- PHP compatibility requirements

---

## Workflow Execution

### When a Contributor Opens a PR

1. **Automatic Triggers:**
   - PHP linting workflow starts
   - CSS linting workflow starts (if CSS changed)

2. **Parallel Execution:**
   - PHP syntax checked across 3 versions
   - PHPCS runs for WordPress standards
   - PHP compatibility verified
   - CSS validated and analyzed

3. **Results Displayed:**
   - Green checkmark if all pass
   - Red X with details if failures
   - Yellow for informational warnings

4. **Code Review:**
   - Reviewer sees automated results
   - Uses PR template checklist
   - Tests on staging environment
   - Approves or requests changes

5. **Merge:**
   - PR merged to main branch
   - Deployed to staging for final testing
   - Scheduled for production deployment

### When Parent Theme Needs Update

1. **Elegant Themes releases new version**
2. **Create issue using update template**
3. **Download and review changes**
4. **Test on staging with child theme**
5. **Update parent theme repository**
6. **Verify child theme compatibility**
7. **Deploy to production**
8. **Monitor for 24-48 hours**

---

## Testing & Validation

### Pre-Deployment Testing

✅ **Workflow Validation**
- Created test branch
- Made PHP changes
- Verified workflows triggered
- Confirmed linting results accurate

✅ **Template Testing**
- Reviewed rendered templates on GitHub
- Verified all form fields work
- Checked dropdown options
- Tested required field validation

✅ **Documentation Review**
- CONTRIBUTING.md clarity
- Accuracy of technical details
- Link verification
- WordPress version compatibility

### Post-Deployment Validation

✅ **Repository Configuration**
- Issue templates visible in UI
- PR template auto-populates
- Workflows appear in Actions tab
- Branch protection rules (if configured)

✅ **Workflow Execution**
- First PR triggers workflows
- Results display correctly
- Logs are readable
- Performance acceptable

---

## Metrics & Monitoring

### Workflow Performance

**Current State:**
- PHP linting: ~2-3 minutes
- CSS linting: ~1-2 minutes
- Total PR check time: ~3-5 minutes

**Targets:**
- Keep under 5 minutes total
- Monitor for timeout issues
- Optimize if needed

### Issue Template Usage

**Tracking:**
- Monitor issue creation rate
- Review template completeness
- Gather feedback from contributors
- Iterate on templates as needed

### Code Quality Trends

**Monitoring:**
- Track PHPCS warning count
- Monitor CSS complexity scores
- Review duplicate selector trends
- Identify improvement opportunities

---

## Future Enhancements

### Short-term (Next 3 Months)

1. **Add Visual Regression Testing**
   - Screenshot comparisons for PRs
   - Detect unintended visual changes
   - Tools: Percy, BackstopJS, or Chromatic

2. **Accessibility Testing Automation**
   - Automated WCAG compliance checks
   - axe-core integration
   - pa11y or Lighthouse CI

3. **Performance Testing**
   - Automated page speed testing
   - CSS size monitoring
   - JavaScript performance checks

### Medium-term (3-6 Months)

1. **Staging Deployment Automation**
   - Auto-deploy PRs to staging URLs
   - Preview changes in real WordPress environment
   - Automated smoke tests

2. **Automated Dependency Updates**
   - Dependabot for composer dependencies
   - Automated security updates
   - Monthly dependency reviews

3. **Code Coverage Tracking**
   - PHP unit tests (if added)
   - JavaScript tests (if needed)
   - Coverage reporting

### Long-term (6-12 Months)

1. **Full CI/CD Pipeline**
   - Automated deployment to production
   - Blue-green deployment strategy
   - Automated rollback on errors

2. **Integration Testing**
   - WordPress multisite testing
   - Plugin compatibility testing
   - Cross-version WordPress testing

3. **Documentation Generation**
   - Automated API documentation
   - Template hierarchy documentation
   - Hook/filter documentation

---

## Lessons Learned

### What Worked Well

✅ **Template Reuse**
- Adapted infrastructure-docs templates
- Saved significant time
- Consistent across repositories

✅ **Comprehensive Documentation**
- CONTRIBUTING.md prevents confusion
- Reduces back-and-forth questions
- Empowers contributors

✅ **Multi-Version PHP Testing**
- Caught compatibility issues early
- Provides confidence for upgrades
- Minimal overhead

### Challenges Encountered

⚠️ **Workflow Complexity**
- Many jobs can be overwhelming
- Set to continue-on-error for some
- Balance between strictness and usability

⚠️ **Parent Theme Confusion**
- Need to be very clear about do-not-modify
- Multiple reminders necessary
- Consider making repo read-only

⚠️ **Testing Requirements**
- Comprehensive but time-consuming
- Need to balance thoroughness with velocity
- Consider tiered testing (quick vs. full)

### Recommendations

1. **Start Simple**
   - Begin with basic linting
   - Add more checks gradually
   - Get team buy-in first

2. **Make It Easy**
   - Clear documentation
   - Helpful error messages
   - Fast feedback loops

3. **Iterate Based on Usage**
   - Monitor what helps/hurts
   - Adjust strictness as needed
   - Get contributor feedback

---

## Related Documentation

- [GitHub Workflow Strategy](../../governance/GITHUB-WORKFLOW-STRATEGY.md)
- [GitHub Workflow Quickstart](../../governance/GITHUB-WORKFLOW-QUICKSTART.md)
- [WordPress Security Audit](14-wordpress-security-audit-2025.md)
- [Documentation Refactor Plan](../../governance/DOCUMENTATION-REFACTOR-PLAN.md)

---

## Repository Links

### Theme Repositories
- [Parent Theme](https://github.com/pausatf/pausatf-theme-thesource) - TheSource v4.8.13 (Elegant Themes)
- [Child Theme](https://github.com/pausatf/pausatf-theme-thesource-child) - PAUSATF customizations

### Other PAUSATF Repositories
- [Infrastructure Docs](https://github.com/pausatf/pausatf-infrastructure-docs)
- [Terraform](https://github.com/pausatf/pausatf-terraform)
- [Ansible](https://github.com/pausatf/pausatf-ansible)
- [Scripts](https://github.com/pausatf/pausatf-scripts)

---

## Contact & Support

**Questions about theme development?**
- Review: [Child Theme CONTRIBUTING.md](https://github.com/pausatf/pausatf-theme-thesource-child/blob/main/CONTRIBUTING.md)
- Discuss: [GitHub Discussions](https://github.com/pausatf/pausatf-infrastructure-docs/discussions)
- Contact: @thomasvincent

**Found a bug in the workflows?**
- [Open an issue](https://github.com/pausatf/pausatf-infrastructure-docs/issues)

**Want to suggest improvements?**
- [Start a discussion](https://github.com/pausatf/pausatf-infrastructure-docs/discussions)

---

**Report Status:** Complete
**Last Updated:** December 21, 2025
**Next Review:** March 2026 (or when Elegant Themes releases TheSource v4.9+)
