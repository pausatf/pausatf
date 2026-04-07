# Deep Infrastructure Audit and Standardization Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Capture absolute ground-truth state from DigitalOcean and all servers (dev, stage, prod), port undocumented configurations into Infrastructure-as-Code (Terraform/Ansible), and enforce the strict absence of local databases.

**Architecture:** We will use `doctl` and Terraform imports to capture cloud-level drift. We will create a new surgical Ansible playbook (`capture-deep-system-state.yml`) to download critical configuration files (FSTAB, Apache, Certs, User Homes, SSH, Cron, Mail, Legacy Data). Finally, we will update the `mysql` role to aggressively stop and purge any local databases.

**Tech Stack:** Terraform, Ansible, doctl, Bash.

---

## Task 1: Terraform Cloud Drift Audit (DigitalOcean)

**Files:**
- Modify: `terraform/environments/production/main.tf` (if drift found)
- Modify: `terraform/environments/staging/main.tf` (if drift found)

- [ ] **Step 1: Run DigitalOcean State Extraction**
Using the local terminal, execute `doctl` commands to extract current state for droplets, firewalls, and domains:
```bash
doctl compute droplet list --output json > droplet_state.json
doctl compute firewall list --output json > firewall_state.json
doctl compute domain records list pausatf.org --output json > dns_state.json
```

- [ ] **Step 2: Compare State and Update Terraform**
Manually inspect the JSON outputs against `terraform/environments/production/main.tf` and `terraform/environments/staging/main.tf`. Update Terraform files with any missing rules (e.g., custom firewall inbound rules, missed A/TXT records, changed droplet sizes).

- [ ] **Step 3: Apply Terraform Plan**
Run `terraform plan` in the respective environment directories to confirm no unexpected destructive changes will occur, ensuring the Terraform definitions are perfectly aligned with reality.

---

## Task 2: Deep Server Configuration Capture (Ansible)

**Files:**
- Create: `ansible/playbooks/capture-deep-system-state.yml`
- Create: `.gitignore` update for `deep-capture/`

- [ ] **Step 1: Add `deep-capture/` to `.gitignore`**
Append `deep-capture/` to `ansible/.gitignore` and the root `.gitignore`.

- [ ] **Step 2: Create the Capture Playbook**
Create `ansible/playbooks/capture-deep-system-state.yml`:
```yaml
---
- name: Capture Deep System State (FSTAB, Apache, Certs, Cron, Mail, Home, Legacy)
  hosts: webservers
  become: true
  vars:
    output_dir: "../../deep-capture/{{ inventory_hostname }}"

  tasks:
    - name: Create local output directory
      delegate_to: localhost
      ansible.builtin.file:
        path: "{{ output_dir }}"
        state: directory
        mode: "0755"
      run_once: false
      become: false

    - name: Fetch flat configuration files
      ansible.builtin.fetch:
        src: "{{ item }}"
        dest: "{{ output_dir }}/{{ item | basename }}"
        flat: yes
      loop:
        - /etc/fstab
        - /etc/ssh/sshd_config
      ignore_errors: true

    - name: Fetch Mail (Postfix) configuration
      ansible.builtin.fetch:
        src: /etc/postfix/main.cf
        dest: "{{ output_dir }}/postfix_main.cf"
        flat: yes
      ignore_errors: true

    - name: Archive and fetch Apache configurations
      ansible.builtin.shell: tar -czf /tmp/apache2-conf.tar.gz -C /etc apache2
      changed_when: false

    - name: Download Apache Archive
      ansible.builtin.fetch:
        src: /tmp/apache2-conf.tar.gz
        dest: "{{ output_dir }}/apache2-conf.tar.gz"
        flat: yes

    - name: Archive and fetch Certs
      ansible.builtin.shell: tar -czf /tmp/letsencrypt-certs.tar.gz -C /etc letsencrypt
      changed_when: false

    - name: Download Certs Archive
      ansible.builtin.fetch:
        src: /tmp/letsencrypt-certs.tar.gz
        dest: "{{ output_dir }}/letsencrypt-certs.tar.gz"
        flat: yes

    - name: Capture Home Directories Listing
      ansible.builtin.shell: ls -laR /home > /tmp/home_dirs.txt
      changed_when: false

    - name: Download Home Listing
      ansible.builtin.fetch:
        src: /tmp/home_dirs.txt
        dest: "{{ output_dir }}/home_dirs.txt"
        flat: yes

    - name: Capture Legacy Data (Jeff's Stuff) Listing
      ansible.builtin.shell: ls -laR /var/www/legacy > /tmp/legacy_data.txt
      changed_when: false
      ignore_errors: true

    - name: Download Legacy Listing
      ansible.builtin.fetch:
        src: /tmp/legacy_data.txt
        dest: "{{ output_dir }}/legacy_data.txt"
        flat: yes

    - name: Capture detailed Cron and Timers
      ansible.builtin.shell: |
        echo "=== SYSTEM CRON ===" > /tmp/cron_state.txt
        cat /etc/crontab >> /tmp/cron_state.txt 2>/dev/null
        echo -e "\n=== CRON.D ===" >> /tmp/cron_state.txt
        for f in /etc/cron.d/*; do echo "--- $f ---"; cat "$f"; done >> /tmp/cron_state.txt 2>/dev/null
        echo -e "\n=== ROOT CRON ===" >> /tmp/cron_state.txt
        crontab -l -u root >> /tmp/cron_state.txt 2>/dev/null
        echo -e "\n=== WWW-DATA CRON ===" >> /tmp/cron_state.txt
        crontab -l -u www-data >> /tmp/cron_state.txt 2>/dev/null
        echo -e "\n=== DEPLOY CRON ===" >> /tmp/cron_state.txt
        crontab -l -u deploy >> /tmp/cron_state.txt 2>/dev/null
      changed_when: false

    - name: Download Cron State
      ansible.builtin.fetch:
        src: /tmp/cron_state.txt
        dest: "{{ output_dir }}/cron_state.txt"
        flat: yes

    - name: Cleanup remote temporary files
      ansible.builtin.file:
        path: "{{ item }}"
        state: absent
      loop:
        - /tmp/apache2-conf.tar.gz
        - /tmp/letsencrypt-certs.tar.gz
        - /tmp/home_dirs.txt
        - /tmp/legacy_data.txt
        - /tmp/cron_state.txt
```

- [ ] **Step 3: Run the Capture Playbook**
Execute: `ansible-playbook -i ansible/inventory/hosts.yml ansible/playbooks/capture-deep-system-state.yml`
*(Note: Requires agent access to target hosts or to be executed by a human).*

---

## Task 3: Enforce Complete Removal of Local Databases

**Files:**
- Modify: `ansible/roles/mysql/tasks/main.yml`

- [ ] **Step 1: Ensure Local MySQL is Stopped and Disabled**
Update `ansible/roles/mysql/tasks/main.yml` to include a cleanup block when `mysql_managed` is `true` or `mysql_enabled` is `false`.

```yaml
# Add to the bottom of ansible/roles/mysql/tasks/main.yml

- name: Purge local MySQL installations when using Managed DB
  when: (mysql_managed | default(false)) or not (mysql_enabled | default(true))
  block:
    - name: Ensure MySQL service is stopped and disabled
      ansible.builtin.service:
        name: mysql
        state: stopped
        enabled: false
      failed_when: false
      ignore_errors: true

    - name: Uninstall MySQL server packages
      ansible.builtin.apt:
        name:
          - mysql-server
          - mysql-server-core-*
          - mariadb-server
          - mariadb-server-core-*
        state: absent
        purge: true
```

- [ ] **Step 2: Commit the changes**
```bash
git add ansible/roles/mysql/tasks/main.yml ansible/playbooks/capture-deep-system-state.yml
git commit -m "feat: add deep capture playbook and enforce removal of local databases"
```
