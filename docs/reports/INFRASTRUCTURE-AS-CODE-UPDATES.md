# INFRASTRUCTURE AS CODE UPDATES
## Configuration Changes to be Reflected in IaC Repositories

**Date:** 2025-12-21
**Phase:** 1 (Critical Security Improvements)
**Status:** Manual updates required in Terraform/Ansible repos

---

## OVERVIEW

This document outlines configuration changes made during Phase 1 implementation that should be codified in the PAUSATF Infrastructure as Code repositories:

- **pausatf-terraform:** Infrastructure provisioning (DigitalOcean, Cloudflare)
- **pausatf-ansible:** Configuration management (server configuration)
- **pausatf-scripts:** Automation scripts

---

## TERRAFORM UPDATES REQUIRED

### Repository: `pausatf-terraform`

**File: `digitalocean/firewalls.tf`**

Add staging server firewall configuration:

```hcl
# Staging server firewall
resource "digitalocean_firewall" "pausatf_staging" {
  name = "pausatf-staging"

  droplet_ids = [digitalocean_droplet.staging.id]

  # SSH access (port 22)
  inbound_rule {
    protocol         = "tcp"
    port_range       = "22"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  # HTTP (port 80)
  inbound_rule {
    protocol         = "tcp"
    port_range       = "80"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  # HTTPS (port 443)
  inbound_rule {
    protocol         = "tcp"
    port_range       = "443"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  # ICMP (ping)
  inbound_rule {
    protocol         = "icmp"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  # Outbound - allow all
  outbound_rule {
    protocol              = "tcp"
    port_range            = "1-65535"
    destination_addresses = ["0.0.0.0/0", "::/0"]
  }

  outbound_rule {
    protocol              = "udp"
    port_range            = "1-65535"
    destination_addresses = ["0.0.0.0/0", "::/0"]
  }

  outbound_rule {
    protocol              = "icmp"
    destination_addresses = ["0.0.0.0/0", "::/0"]
  }

  tags = ["pausatf", "staging", "security"]
}
```

**File: `digitalocean/droplets.tf`**

Update droplet configurations to include DO agent and monitoring:

```hcl
resource "digitalocean_droplet" "production" {
  # ... existing config ...

  # Enable monitoring, backups, and DO agent
  monitoring = true
  backups    = true

  user_data = <<-EOF
    #!/bin/bash
    # Install DigitalOcean Agent
    curl -sSL https://repos.insights.digitalocean.com/install.sh | bash

    # Configure automated security updates
    apt-get update
    apt-get install -y unattended-upgrades
    cat > /etc/apt/apt.conf.d/50unattended-upgrades <<'UPDATES'
    Unattended-Upgrade::Allowed-Origins {
        "\${distro_id}:\${distro_codename}-security";
    };
    Unattended-Upgrade::Automatic-Reboot "false";
    Unattended-Upgrade::Mail "admin@pausatf.org";
    Unattended-Upgrade::Remove-Unused-Dependencies "true";
    UPDATES
    systemctl enable --now unattended-upgrades
  EOF

  tags = ["pausatf", "production", "web"]
}

resource "digitalocean_droplet" "staging" {
  # ... existing config ...

  # Enable monitoring, backups, and DO agent
  monitoring = true
  backups    = true

  user_data = <<-EOF
    #!/bin/bash
    # Install DigitalOcean Agent
    curl -sSL https://repos.insights.digitalocean.com/install.sh | bash

    # Configure automated security updates
    apt-get update
    apt-get install -y unattended-upgrades
    cat > /etc/apt/apt.conf.d/50unattended-upgrades <<'UPDATES'
    Unattended-Upgrade::Allowed-Origins {
        "\${distro_id}:\${distro_codename}-security";
    };
    Unattended-Upgrade::Automatic-Reboot "false";
    Unattended-Upgrade::Mail "admin@pausatf.org";
    Unattended-Upgrade::Remove-Unused-Dependencies "true";
    UPDATES
    systemctl enable --now unattended-upgrades
  EOF

  tags = ["pausatf", "staging", "web"]
}
```

**File: `digitalocean/monitoring.tf` (NEW)**

Create monitoring alerts configuration:

```hcl
# CPU alert - Production
resource "digitalocean_monitor_alert" "production_cpu" {
  alerts {
    email = ["admin@pausatf.org"]
  }
  window      = "5m"
  type        = "v1/insights/droplet/cpu"
  compare     = "GreaterThan"
  value       = 80
  enabled     = true
  entities    = [digitalocean_droplet.production.id]
  description = "Production CPU High"
}

# Memory alert - Production
resource "digitalocean_monitor_alert" "production_memory" {
  alerts {
    email = ["admin@pausatf.org"]
  }
  window      = "5m"
  type        = "v1/insights/droplet/memory_utilization_percent"
  compare     = "GreaterThan"
  value       = 85
  enabled     = true
  entities    = [digitalocean_droplet.production.id]
  description = "Production Memory High"
}

# Disk alert - Production
resource "digitalocean_monitor_alert" "production_disk" {
  alerts {
    email = ["admin@pausatf.org"]
  }
  window      = "10m"
  type        = "v1/insights/droplet/disk_utilization_percent"
  compare     = "GreaterThan"
  value       = 80
  enabled     = true
  entities    = [digitalocean_droplet.production.id]
  description = "Production Disk Space Low"
}

# Load average alert - Production
resource "digitalocean_monitor_alert" "production_load" {
  alerts {
    email = ["admin@pausatf.org"]
  }
  window      = "5m"
  type        = "v1/insights/droplet/load_5"
  compare     = "GreaterThan"
  value       = 3
  enabled     = true
  entities    = [digitalocean_droplet.production.id]
  description = "Production Load High"
}

# Repeat for staging (optional)
```

---

## ANSIBLE UPDATES REQUIRED

### Repository: `pausatf-ansible`

**File: `roles/security/tasks/main.yml`**

Update security role to include automated updates:

```yaml
---
# Security hardening tasks

- name: Install unattended-upgrades
  apt:
    name: unattended-upgrades
    state: present
    update_cache: yes

- name: Configure unattended-upgrades
  template:
    src: 50unattended-upgrades.j2
    dest: /etc/apt/apt.conf.d/50unattended-upgrades
    owner: root
    group: root
    mode: '0644'
  notify: restart unattended-upgrades

- name: Enable unattended-upgrades service
  systemd:
    name: unattended-upgrades
    enabled: yes
    state: started

- name: Install DigitalOcean agent
  shell: |
    curl -sSL https://repos.insights.digitalocean.com/install.sh | bash
  args:
    creates: /usr/bin/do-agent

- name: Ensure DO agent is running
  systemd:
    name: do-agent
    enabled: yes
    state: started

- name: Configure fail2ban (existing)
  # ... existing fail2ban config ...
```

**File: `roles/security/templates/50unattended-upgrades.j2` (NEW)**

```jinja2
// Ansible managed - do not edit directly
// Template: roles/security/templates/50unattended-upgrades.j2

Unattended-Upgrade::Allowed-Origins {
    "${distro_id}:${distro_codename}-security";
};

// Do not automatically reboot (manual control)
Unattended-Upgrade::Automatic-Reboot "false";

// Email notifications
Unattended-Upgrade::Mail "{{ admin_email | default('admin@pausatf.org') }}";

// Remove unused dependencies
Unattended-Upgrade::Remove-Unused-Dependencies "true";

// Log to syslog
Unattended-Upgrade::SyslogEnable "true";
Unattended-Upgrade::SyslogFacility "daemon";
```

**File: `roles/security/handlers/main.yml`**

```yaml
---
- name: restart unattended-upgrades
  systemd:
    name: unattended-upgrades
    state: restarted

- name: restart do-agent
  systemd:
    name: do-agent
    state: restarted
```

**File: `group_vars/production.yml`**

```yaml
# Production server variables
admin_email: admin@pausatf.org
enable_automated_updates: true
do_agent_enabled: true

# Firewall configuration
firewall_enabled: true
firewall_allowed_ports:
  - 22   # SSH
  - 80   # HTTP
  - 443  # HTTPS
```

**File: `group_vars/staging.yml`**

```yaml
# Staging server variables
admin_email: admin@pausatf.org
enable_automated_updates: true
do_agent_enabled: true

# Firewall configuration
firewall_enabled: true
firewall_allowed_ports:
  - 22   # SSH
  - 80   # HTTP
  - 443  # HTTPS
```

---

## SCRIPTS UPDATES REQUIRED

### Repository: `pausatf-scripts`

**File: `monitoring/check-firewall-status.sh` (NEW)**

```bash
#!/bin/bash
# Check firewall status across all servers
# Usage: ./check-firewall-status.sh

echo "=== DigitalOcean Firewall Status ==="
doctl compute firewall list

echo ""
echo "=== Verifying Droplet Protection ==="
doctl compute droplet list --format ID,Name,Tags | grep pausatf | while read -r id name tags; do
    echo "Droplet: $name ($id)"
    doctl compute firewall list --format Name,DropletIDs | grep "$id" && echo "  ✅ Protected" || echo "  ❌ No firewall"
done
```

**File: `monitoring/check-security-updates.sh` (NEW)**

```bash
#!/bin/bash
# Check automated security update status
# Usage: ./check-security-updates.sh [production|staging]

SERVER="${1:-production}"

if [ "$SERVER" = "production" ]; then
    HOST="prod.pausatf.org"
elif [ "$SERVER" = "staging" ]; then
    HOST="stage.pausatf.org"
else
    echo "Usage: $0 [production|staging]"
    exit 1
fi

echo "=== Checking Security Updates on $SERVER ==="
ssh root@$HOST "
    echo '--- Unattended Upgrades Status ---'
    systemctl status unattended-upgrades --no-pager | head -10

    echo ''
    echo '--- Recent Update Log (last 20 lines) ---'
    tail -20 /var/log/unattended-upgrades/unattended-upgrades.log 2>/dev/null || echo 'No updates yet'

    echo ''
    echo '--- Pending Security Updates ---'
    apt-get update -qq
    apt-get upgrade -s | grep -i security || echo 'No pending security updates'
"
```

**File: `deployment/pre-deployment-checklist.sh`**

Update to include new security checks:

```bash
#!/bin/bash
# Pre-deployment checklist

echo "=== Pre-Deployment Security Checklist ==="

# Check firewalls
echo "1. Checking firewalls..."
FIREWALLS=$(doctl compute firewall list --format Name --no-header | wc -l)
if [ "$FIREWALLS" -ge 2 ]; then
    echo "   ✅ Firewalls active ($FIREWALLS found)"
else
    echo "   ⚠️  WARNING: Expected 2 firewalls, found $FIREWALLS"
fi

# Check DO agents
echo "2. Checking DigitalOcean agents..."
AGENTS=$(doctl compute droplet list --format Name,Features | grep droplet_agent | wc -l)
if [ "$AGENTS" -ge 2 ]; then
    echo "   ✅ DO agents installed ($AGENTS droplets)"
else
    echo "   ⚠️  WARNING: DO agent not on all droplets"
fi

# Check automated updates
echo "3. Checking automated security updates..."
for server in prod.pausatf.org stage.pausatf.org; do
    STATUS=$(ssh root@$server "systemctl is-active unattended-upgrades" 2>/dev/null)
    if [ "$STATUS" = "active" ]; then
        echo "   ✅ $server: automated updates active"
    else
        echo "   ⚠️  $server: automated updates not active"
    fi
done

# Check monitoring alerts (manual verification)
echo "4. Monitoring alerts (verify manually)..."
echo "   Visit: https://cloud.digitalocean.com/monitoring/alerts"
echo "   Expected: 4+ alerts configured"

echo ""
echo "=== Checklist Complete ==="
```

---

## GITHUB ACTIONS UPDATES

**File: `.github/workflows/infrastructure-validation.yml` (NEW)**

Add infrastructure validation workflow:

```yaml
name: Infrastructure Validation

on:
  schedule:
    - cron: '0 12 * * 0'  # Weekly on Sunday
  workflow_dispatch:

jobs:
  validate-security:
    name: Validate Security Configuration
    runs-on: ubuntu-latest

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Install doctl
        uses: digitalocean/action-doctl@v2
        with:
          token: ${{ secrets.DIGITALOCEAN_ACCESS_TOKEN }}

      - name: Validate Firewalls
        run: |
          echo "=== Checking Firewalls ==="
          FIREWALLS=$(doctl compute firewall list --format Name --no-header | wc -l)
          if [ "$FIREWALLS" -lt 2 ]; then
            echo "❌ ERROR: Expected 2 firewalls, found $FIREWALLS"
            exit 1
          fi
          echo "✅ $FIREWALLS firewalls configured"

      - name: Validate DO Agents
        run: |
          echo "=== Checking DO Agents ==="
          PROD_AGENT=$(doctl compute droplet get ${{ secrets.PROD_DROPLET_ID }} --format Features --no-header | grep droplet_agent || echo "")
          STAGE_AGENT=$(doctl compute droplet get ${{ secrets.STAGING_DROPLET_ID }} --format Features --no-header | grep droplet_agent || echo "")

          if [ -z "$PROD_AGENT" ]; then
            echo "❌ ERROR: Production droplet missing DO agent"
            exit 1
          fi

          if [ -z "$STAGE_AGENT" ]; then
            echo "❌ ERROR: Staging droplet missing DO agent"
            exit 1
          fi

          echo "✅ DO agents active on both droplets"

      - name: Validate Monitoring Alerts
        run: |
          echo "=== Checking Monitoring Alerts ==="
          ALERTS=$(doctl monitoring alert list --format UUID --no-header | wc -l)
          if [ "$ALERTS" -lt 4 ]; then
            echo "⚠️  WARNING: Expected 4+ alerts, found $ALERTS"
          else
            echo "✅ $ALERTS monitoring alerts configured"
          fi
```

---

## CONFIGURATION SUMMARY

### Changes Made (Dec 21, 2025)

**DigitalOcean:**
1. ✅ Created staging firewall (c12dfc7f-f43a-4b32-96c6-80ba34035b1a)
2. ✅ Installed DO agent on production (do-agent 3.18.5)
3. ✅ Installed DO agent on staging (do-agent 3.18.5)
4. ✅ Enabled automated security updates (production)
5. ✅ Enabled automated security updates (staging)
6. ⏳ Monitoring alerts (pending manual configuration)

**Current IDs:**
- Production Droplet: 355909945
- Staging Droplet: 538411208
- Production Firewall: a4e42798-ab22-467f-a821-daa290f56655
- Staging Firewall: c12dfc7f-f43a-4b32-96c6-80ba34035b1a

---

## IMPLEMENTATION PRIORITY

**High Priority (Do First):**
1. ✅ Update Terraform firewall configuration
2. ✅ Update Ansible security role
3. ✅ Create monitoring alerts in Terraform

**Medium Priority (Next):**
4. ✅ Add validation scripts
5. ✅ Update GitHub Actions workflows
6. ✅ Document in group_vars

**Low Priority (When Time Permits):**
7. Test Terraform changes in staging environment
8. Run Ansible playbooks to verify idempotency
9. Add integration tests for security features

---

## TESTING PROCEDURE

### Before Applying IaC Changes

```bash
# 1. Test Terraform changes
cd pausatf-terraform
terraform plan

# 2. Test Ansible changes
cd pausatf-ansible
ansible-playbook playbooks/security.yml --check --diff

# 3. Verify current state matches desired state
./scripts/monitoring/check-firewall-status.sh
./scripts/monitoring/check-security-updates.sh production
./scripts/monitoring/check-security-updates.sh staging
```

### After Applying IaC Changes

```bash
# Verify firewall
doctl compute firewall list

# Verify DO agents
doctl compute droplet list --format Name,Features

# Verify automated updates
ssh root@prod.pausatf.org "systemctl status unattended-upgrades"
ssh root@stage.pausatf.org "systemctl status unattended-upgrades"

# Run validation workflow
gh workflow run infrastructure-validation.yml --repo pausatf/pausatf-infrastructure-docs
```

---

## ROLLBACK PROCEDURE

If IaC changes cause issues:

**Terraform:**
```bash
terraform state list | grep firewall
terraform destroy -target=digitalocean_firewall.pausatf_staging
```

**Ansible:**
```bash
# Disable automated updates
ansible -m systemd -a "name=unattended-upgrades state=stopped enabled=no" all
```

---

**Document Version:** 1.0
**Created:** 2025-12-21
**Maintained by:** Thomas Vincent
**Related:** PHASE1-IMPLEMENTATION-REPORT.md, 13-digitalocean-optimization-guide.md
