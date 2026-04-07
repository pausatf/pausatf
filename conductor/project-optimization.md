# Project Optimization and CI/CD Enhancement Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enhance CI/CD automation, optimize terminal experience, and perform infrastructure/security audits.

**Architecture:** Consolidating linting into a central CI workflow, adding automated testing for Ansible, and implementing terminal hooks for developer experience.

**Tech Stack:** GitHub Actions, Terraform, Zsh, Ansible, Molecule.

---

## Task 1: Terminal Tab Persistence

**Files:**
- Modify: `~/.zshrc`

- [ ] **Step 1: Add chpwd hook to .zshrc**

```bash
# Add to ~/.zshrc
function chpwd() {
  if [ -d .git ]; then
    local repo_name=$(basename $(git rev-parse --show-toplevel))
    echo -ne "\033]0;${repo_name}\007"
  fi
}
# Run once for current directory
chpwd
```

- [ ] **Step 2: Source .zshrc to verify**

Run: `source ~/.zshrc`
Expected: Terminal tab title updates to 'pausatf'.

- [ ] **Step 3: Commit (optional, as this is a local config)**

---

## Task 2: Consolidate and Optimize GitHub Actions

**Files:**
- Modify: `.github/workflows/ci.yml`
- Modify: `.github/workflows/molecule.yml`

- [ ] **Step 1: Add Ansible Lint to `ci.yml`**

```yaml
# Add to jobs in .github/workflows/ci.yml
  ansible-lint:
    name: Ansible Lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run ansible-lint
        uses: ansible/ansible-lint@main
        with:
          path: "ansible/"
```

- [ ] **Step 2: Update `molecule.yml` to run tests for 'common' role**

```yaml
# Update .github/workflows/molecule.yml
    steps:
      - uses: actions/checkout@v4
      - name: Set up Python
        uses: actions/setup-python@v5
        with:
          python-version: '3.11'
      - name: Install dependencies
        run: |
          python -m pip install --upgrade pip
          pip install molecule molecule-plugins[docker] ansible-lint
      - name: Run Molecule tests
        run: |
          cd ansible/roles/common
          molecule test
        env:
          PY_COLORS: '1'
          ANSIBLE_FORCE_COLOR: '1'
```

- [ ] **Step 3: Verify workflow syntax**

Run: `actionlint .github/workflows/*.yml` (if available)

- [ ] **Step 4: Commit changes**

```bash
git add .github/workflows/ci.yml .github/workflows/molecule.yml
git commit -m "ci: add ansible-lint and enable molecule tests for common role"
```

---

## Task 3: Terraform Variable Refactoring

**Files:**
- Modify: `terraform/environments/production/variables.tf`
- Modify: `terraform/environments/production/main.tf`

- [ ] **Step 1: Define VPC UUID variable**

```hcl
# terraform/environments/production/variables.tf
variable "vpc_uuid" {
  type        = string
  description = "The UUID of the VPC to use for production"
}
```

- [ ] **Step 2: Replace hardcoded VPC UUID in `main.tf`**

```hcl
# terraform/environments/production/main.tf
module "wordpress" {
  # ...
  vpc_uuid_override = var.vpc_uuid
  # ...
}
```

- [ ] **Step 3: Verify Terraform configuration**

Run: `terraform -chdir=terraform/environments/production validate`
Expected: Success

- [ ] **Step 4: Commit changes**

```bash
git add terraform/environments/production/variables.tf terraform/environments/production/main.tf
git commit -m "refactor(terraform): replace hardcoded VPC UUID with variable"
```

---

## Task 4: Theme Optimization (Monorepo Best Practices)

**Files:**
- Create: `.github/workflows/theme-lint.yml`

- [ ] **Step 1: Add CSS and PHP linting for themes**

```yaml
# .github/workflows/theme-lint.yml
name: Theme Quality
on: [push, pull_request]
jobs:
  stylelint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run Stylelint
        run: |
          npx stylelint "themes/**/*.css"
  php-lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: PHP Syntax Check
        run: find themes -name "*.php" -exec php -l {} \;
```

- [ ] **Step 2: Commit changes**

```bash
git add .github/workflows/theme-lint.yml
git commit -m "ci: add linting for themes (CSS and PHP syntax)"
```
