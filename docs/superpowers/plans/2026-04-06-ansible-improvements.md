# Ansible Configuration Improvement Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor hardcoded configurations, standardize role metadata, and expand testing coverage for the Ansible codebase.

**Architecture:** Moving hardcoded values to role defaults, ensuring every role has a standard documentation and variable structure, and initializing Molecule for core system roles.

**Tech Stack:** Ansible, Molecule, YAML.

---

## Task 1: Refactor Admin User Management in `common` Role

**Files:**
- Create/Modify: `ansible/roles/common/defaults/main.yml`
- Modify: `ansible/roles/common/tasks/main.yml`

- [ ] **Step 1: Define admin users in defaults**

```yaml
# ansible/roles/common/defaults/main.yml
---
common_admin_users:
  - name: "somethingwithproof"
    github: "somethingwithproof"
    groups: "sudo,www-data"
  - name: "jteeters"
    github: "jeffteeters"
    groups: "sudo,www-data"

common_deploy_user: "deploy"
```

- [ ] **Step 2: Update `roles/common/tasks/main.yml` to use variables**

Replace hardcoded loops with `common_admin_users`.

- [ ] **Step 3: Verify with `ansible-lint`**

Run: `ansible-lint ansible/roles/common`
Expected: PASS

- [ ] **Step 4: Commit changes**

```bash
git add ansible/roles/common/defaults/main.yml ansible/roles/common/tasks/main.yml
git commit -m "refactor(common): move hardcoded admin users to role defaults"
```

---

## Task 2: Standardize Role Metadata for Core Roles

**Files:**
- Create: `ansible/roles/common/README.md`
- Create: `ansible/roles/wordpress/README.md`
- Create: `ansible/roles/wordpress/defaults/main.yml`
- Create: `ansible/roles/security_hardening/README.md`
- Create: `ansible/roles/security_hardening/defaults/main.yml`

- [ ] **Step 1: Create `README.md` for `common` role**

```markdown
# Role: common
Base system configuration for PAUSATF servers.

## Variables
- `common_admin_users`: List of admin users with names, GitHub handles (for SSH keys), and groups.
- `common_deploy_user`: Name of the primary deployment user.
- `timezone`: System timezone (default: America/Los_Angeles).
```

- [ ] **Step 2: Create `README.md` and `defaults/main.yml` for `wordpress` role**

```yaml
# ansible/roles/wordpress/defaults/main.yml
---
wordpress_enabled: true
wpcli_enabled: true
is_active_web: false
wp_environment_type: "production"
wordpress_installs: []
wp_main_plugins: []
```

- [ ] **Step 3: Create `README.md` and `defaults/main.yml` for `security_hardening` role**

```yaml
# ansible/roles/security_hardening/defaults/main.yml
---
hardening_ssh_permit_root_login: "no"
hardening_ssh_max_auth_tries: 3
hardening_ssh_allow_users: []
hardening_wp_docroot: "/var/www/html"
hardening_remove_packages:
  - telnet
  - rsh-client
hardening_disable_services:
  - bluetooth
  - cups
hardening_sysctl:
  "net.ipv4.conf.all.rp_filter": "1"
  "net.ipv4.conf.default.rp_filter": "1"
```

- [ ] **Step 4: Commit changes**

```bash
git add ansible/roles/common/README.md \
        ansible/roles/wordpress/README.md \
        ansible/roles/wordpress/defaults/main.yml \
        ansible/roles/security_hardening/README.md \
        ansible/roles/security_hardening/defaults/main.yml
git commit -m "docs: standardize role metadata for common, wordpress, and security_hardening"
```

---

## Task 3: Initialize Molecule Tests for `common` Role

**Files:**
- Create: `ansible/roles/common/molecule/default/molecule.yml`
- Create: `ansible/roles/common/molecule/default/converge.yml`
- Create: `ansible/roles/common/molecule/default/verify.yml`

- [ ] **Step 1: Create Molecule configuration**

```yaml
# ansible/roles/common/molecule/default/molecule.yml
---
dependency:
  name: galaxy
driver:
  name: docker
platforms:
  - name: instance
    image: "geerlingguy/docker-ubuntu2204-ansible:latest"
    command: ""
    volumes:
      - /sys/fs/cgroup:/sys/fs/cgroup:rw
    cgroupns_mode: host
    privileged: true
    pre_build_image: true
provisioner:
  name: ansible
verifier:
  name: ansible
```

- [ ] **Step 2: Create converge playbook**

```yaml
# ansible/roles/common/molecule/default/converge.yml
---
- name: Converge
  hosts: all
  tasks:
    - name: "Include common role"
      include_role:
        name: "common"
```

- [ ] **Step 3: Create verification playbook**

```yaml
# ansible/roles/common/molecule/default/verify.yml
---
- name: Verify
  hosts: all
  tasks:
    - name: Check if deploy user exists
      ansible.builtin.getent:
        database: passwd
        key: deploy
```

- [ ] **Step 4: Run Molecule test (Dry Run or local if Docker available)**

Run: `molecule test -s default` (Note: Skip if Docker is not available in environment)

- [ ] **Step 5: Commit changes**

```bash
git add ansible/roles/common/molecule/
git commit -m "test(common): add baseline molecule tests"
```
