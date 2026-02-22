# PAUSATF Operations Runbook

Practical reference for day-to-day and incident operations. For architecture
and onboarding, see [README.md](README.md).

---

## Table of Contents

- [Deploy](#deploy)
- [Server Migration](#server-migration)
- [Database](#database)
- [Ansible](#ansible)
- [Terraform](#terraform)
- [WordPress](#wordpress)
- [Cloudflare](#cloudflare)
- [Incident Response](#incident-response)

---

## Deploy

### How deploys work

Deploys run `ansible/playbooks/site.yml` against the target inventory group,
applying the `common`, `wordpress`, and `monitoring` roles.

### Trigger a deploy

| Environment | Trigger | Workflow |
|-------------|---------|----------|
| Production | Push to `main` or manual dispatch | `deploy-prod.yml` |
| Staging | Push to `staging` branch | `deploy-staging.yml` |
| Dev | Push to `dev` branch or manual dispatch | `deploy-dev.yml` |

Manual dispatch via GitHub CLI:

```bash
# Production
gh workflow run deploy-prod.yml

# Dev
gh workflow run deploy-dev.yml
```

### Vault password

All three deploy workflows look for the `ANSIBLE_VAULT_PASSWORD` repository
secret. If the secret is absent the vault `--vault-password-file` flag is
omitted and encrypted vars will fail to decrypt. Confirm the secret is set
before triggering a deploy:

```bash
gh secret list --repo pausatf/pausatf
```

### Check deploy status

```bash
# List recent workflow runs
gh run list --repo pausatf/pausatf --workflow deploy-prod.yml

# Stream logs for the most recent run
gh run watch --repo pausatf/pausatf
```

The production deploy ends with a healthcheck against `https://www.pausatf.org`.
A non-2xx response fails the workflow.

---

## Server Migration

### Overview

`ansible/playbooks/migrate-prod.yml` captures a timestamped full backup of the
production droplet (WordPress files, database, Apache config, cron jobs, custom
scripts) and downloads it to `backups/migration-<timestamp>/` on the local
machine.

`ansible/playbooks/restore-prod.yml` restores that backup to a target host.

### Pre-migration checklist

- [ ] Confirm `PROD_SSH_PRIVATE_KEY` secret is valid and the key is authorized
      on `ftp.pausatf.org`
- [ ] Verify free disk space on the source (`df -h /var/www`)
- [ ] Take a DigitalOcean snapshot via the console or `do-nightly-snapshot`
      workflow as a safety net
- [ ] Record the current production IP for DNS rollback

### Run the migration capture

```bash
cd ansible
ansible-playbook -i inventory/hosts.yml playbooks/migrate-prod.yml \
  --vault-password-file ../vault.pass
```

The playbook creates `backups/migration-<timestamp>/` locally containing:

- `wordpress.tar.gz` — full WordPress directory (cache excluded)
- `wordpress-db.sql.gz` — database export
- `legacy.tar.gz` — `/var/www/legacy` with hidden files
- `apache-*.tar.gz` — Apache sites-available, sites-enabled, conf-enabled
- `crontab-*.txt` — cron jobs for root, www-data, deploy users
- `MANIFEST.txt` — contents list and manual migration steps

### Restore to a new droplet

1. Provision the new droplet (Ubuntu 22.04 LTS, `s-4vcpu-8gb` to match prod).
2. Add the new host to `ansible/inventory/hosts.yml` under `production`.
3. Run the restore playbook:

```bash
cd ansible
ansible-playbook -i inventory/hosts.yml playbooks/restore-prod.yml \
  --vault-password-file ../vault.pass
```

The playbook prompts for the backup directory path and a `yes` confirmation
before overwriting any data.

4. Apply crontabs manually (the playbook prints the commands):

```bash
ssh github-deploy@<new-ip> "crontab /tmp/pausatf-restore/crontab-root.txt"
ssh github-deploy@<new-ip> "crontab -u www-data /tmp/pausatf-restore/crontab-www-data.txt"
```

### Post-migration verification

- [ ] `curl -fsS https://www.pausatf.org` returns HTTP 200
- [ ] WordPress admin login works
- [ ] Legacy site (`ftp.pausatf.org/legacy/`) loads
- [ ] Check Apache error log: `tail -50 /var/log/apache2/error.log`
- [ ] Confirm New Relic reports the new host
- [ ] Confirm Fail2ban is active: `fail2ban-client status`

### Rollback

If the new droplet is unhealthy, update the Cloudflare A record for `@` and
`www` back to the original production IP:

```bash
cd terraform/environments/cloudflare
# Edit variables.tf or terraform.tfvars to restore old IP, then:
terraform apply
```

DNS propagation through Cloudflare proxy is near-instant.

---

## Database

### Manual DB backup

Run on the production server as root:

```bash
mysqldump --defaults-extra-file=/etc/mysql/debian.cnf wordpress \
  > /var/backups/wordpress/wordpress-$(date +%Y%m%d-%H%M%S).sql
```

Automated daily backups run at 02:00 local time to `/var/backups/wordpress/`
and are retained for 30 days (configured in `ansible/group_vars/all.yml`
`backup_*` vars).

### Rotate DB password

1. Generate a new password and encrypt it in the vault:

```bash
cd ansible
# Edit vault file
ansible-vault edit group_vars/all/vault.yml
# Update vault_wp_main_db_password
```

2. Apply via Ansible (updates MySQL user and wp-config.php):

```bash
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --tags wordpress --vault-password-file ../vault.pass
```

3. Verify WordPress can connect: `curl -fsS https://www.pausatf.org`

### DB users and permissions

| User | Database | Privileges | Notes |
|------|----------|-----------|-------|
| `wordpress` | `wordpress` | ALL on `wordpress.*` | Application user; host `localhost` only |
| `root` | all | GRANT | Local socket only; credentials in `/root/.my.cnf` |

Anonymous users and the `test` database are removed by the `mysql` role.

Slow query log is enabled (threshold: 2 seconds) at
`/var/log/mysql/mysql-slow.log`.

---

## Ansible

### Run a playbook manually

```bash
cd ansible

# Full site deploy against production
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --vault-password-file ../vault.pass

# Dry-run (check mode)
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --check --vault-password-file ../vault.pass

# Syntax check only
ansible-playbook --syntax-check playbooks/site.yml
```

### Run individual roles with tags

```bash
# Cloudflare settings only
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --tags cloudflare --vault-password-file ../vault.pass

# Purge Cloudflare cache
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --tags cloudflare-purge --vault-password-file ../vault.pass

# WordPress management only
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --tags wordpress --vault-password-file ../vault.pass
```

### Add a new host

1. Add the host to `ansible/inventory/hosts.yml` under the appropriate group.
2. Set required host vars: `ansible_host`, `ansible_user`,
   `ansible_ssh_private_key_file`, `web_server`, `php_version`.
3. Test connectivity:

```bash
ansible <hostname> -i inventory/hosts.yml -m ping
```

4. Run the site playbook with `-l <hostname>`.

### Vault usage

```bash
# Encrypt a new file
ansible-vault encrypt group_vars/all/vault.yml

# Decrypt for editing
ansible-vault decrypt group_vars/all/vault.yml

# Edit in place (preferred — never leaves plaintext on disk)
ansible-vault edit group_vars/all/vault.yml

# Encrypt a single string value
ansible-vault encrypt_string 'mysecret' --name 'vault_my_var'

# View encrypted file
ansible-vault view group_vars/all/vault.yml
```

The vault password file must be at `../vault.pass` relative to the `ansible/`
directory when running playbooks manually (matches CI behavior).

---

## Terraform

### Backend

All environments use DigitalOcean Spaces as the S3-compatible state backend:

| Environment | State key |
|-------------|-----------|
| production | `production/terraform.tfstate` |
| staging | `staging/terraform.tfstate` |
| dev | `dev/terraform.tfstate` |
| cloudflare | `cloudflare/terraform.tfstate` |
| github | `github/terraform.tfstate` |

Bucket: `pausatf-terraform-state` in `sfo2.digitaloceanspaces.com`.

Backend credentials come from `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`
environment variables (mapped to DO Spaces keys).

### Run plan and apply

```bash
# Set credentials
export AWS_ACCESS_KEY_ID=<spaces-key>
export AWS_SECRET_ACCESS_KEY=<spaces-secret>
export DIGITALOCEAN_TOKEN=<do-token>
export CLOUDFLARE_API_TOKEN=<cf-token>

# Production
cd terraform/environments/production
terraform init
terraform plan -out=tfplan
terraform apply tfplan

# Staging
cd terraform/environments/staging
terraform init
terraform plan
terraform apply -auto-approve  # safe for staging only

# Cloudflare DNS
cd terraform/environments/cloudflare
terraform init
terraform plan
terraform apply

# GitHub repository settings
cd terraform/environments/github
terraform init
terraform plan
terraform apply
```

### Provider versions

| Environment | Terraform | digitalocean | cloudflare | github |
|-------------|-----------|--------------|------------|--------|
| production | >= 1.10.0 | ~> 2.47 | ~> 5.15 | — |
| staging | >= 1.6.0 | ~> 2.0 | ~> 5.15 | — |
| dev | >= 1.6.0 | ~> 2.0 | ~> 5.15 | — |
| cloudflare | >= 1.6.0 | — | ~> 5.0 | — |
| github | >= 1.0 | — | — | ~> 6.0 |

---

## WordPress

### Update plugins/themes via Ansible

WP-CLI updates run as part of the `wordpress` role. To trigger an update pass
against production:

```bash
cd ansible
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --tags wordpress --vault-password-file ../vault.pass
```

Plugins excluded from auto-update (premium/custom): `tablepress-premium`,
`wp-file-manager-pro`.

Themes excluded from auto-update: `TheSource-child`, `pausatf-oceanwp-child`,
`pausatf-oceanwp-child-v2`, `pausatf-oceanwp-theme`.

### Capture production plugin/theme inventory

The `capture-prod-inventory` workflow runs `capture-wp-inventory.yml` against
production and commits the result to
`ansible/group_vars/production/wordpress.yml`.

Trigger manually:

```bash
gh workflow run capture-prod-inventory.yml
```

Or run the playbook directly:

```bash
cd ansible
ansible-playbook -i inventory/hosts.yml playbooks/capture-wp-inventory.yml \
  -l production
```

### WP-CLI on the server

WP-CLI is installed at `/usr/local/bin/wp`. Always run as `www-data` to avoid
file permission drift:

```bash
ssh github-deploy@ftp.pausatf.org

# List plugins
sudo -u www-data wp plugin list --path=/var/www/html

# Update a single plugin
sudo -u www-data wp plugin update jetpack --path=/var/www/html

# Check WordPress version
sudo -u www-data wp core version --path=/var/www/html

# List admin users
sudo -u www-data wp user list --role=administrator --path=/var/www/html

# Flush cache
sudo -u www-data wp cache flush --path=/var/www/html
```

---

## Cloudflare

### DNS record structure

All DNS is managed via `terraform/environments/cloudflare/main.tf`.

| Record | Type | Proxied | Purpose |
|--------|------|---------|---------|
| `@` | A | yes | Main site → production IP |
| `www` | A | yes | WWW → production IP |
| `ftp` | A | no | Direct server access (SSH, FTP) |
| `mail` | A | no | Mail server |
| `monitor` | A | no | Monitoring dashboard |
| `stage` | A | no | Staging environment |
| `staging` | A | no | Staging alias |
| `prod` | CNAME | no | Production alias → `ftp.pausatf.org` |
| `REDACTED_SENDGRID` | CNAME | no | SendGrid email delivery |
| `s1._domainkey` | CNAME | no | SendGrid DKIM |
| `s2._domainkey` | CNAME | no | SendGrid DKIM |
| MX (5 records) | MX | — | Google Workspace email |
| SPF | TXT | — | `include:_spf.google.com include:sendgrid.net ~all` |
| DMARC | TXT | — | Monitoring mode (`p=none`) |

Zone ID is stored in `vault_cloudflare_zone_id`.

### Purge cache manually

Via Ansible (runs the `cloudflare` role with the purge tag):

```bash
cd ansible
ansible-playbook -i inventory/hosts.yml site.yml -l production \
  --tags cloudflare-purge --vault-password-file ../vault.pass
```

Via curl directly:

```bash
curl -X POST "https://api.cloudflare.com/client/v4/zones/${CF_ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${CF_API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

### DNS changes

Always change DNS through Terraform, not the Cloudflare dashboard, to keep
state consistent:

```bash
cd terraform/environments/cloudflare
# Edit main.tf
terraform plan
terraform apply
```

---

## Incident Response

### High-level sequence

1. **Isolate** — If actively exploited, block the offending IP at the
   DigitalOcean firewall or enable Cloudflare "Under Attack" mode.
2. **Assess** — Determine scope: unauthorized WP admin accounts, modified
   files, DB exfiltration, server-level compromise.
3. **Rotate credentials** — Run the security remediation playbook:

```bash
cd ansible
ansible-playbook -i inventory/hosts.yml playbooks/security-remediation.yml \
  --vault-password-file ../vault.pass
```

   The playbook removes accounts listed in `wp_users_remove`, rotates auth
   keys/salts, audits remaining admin accounts, and enables Jetpack login
   notifications.

4. **Rotate remaining secrets** — Update `vault_wp_main_db_password`,
   `vault_digitalocean_api_token`, `vault_cloudflare_api_token`,
   `vault_newrelic_license_key` in the Ansible vault and re-deploy.

5. **Patch** — Apply OS and WordPress updates:

```bash
# OS patches
ansible production -i inventory/hosts.yml -m apt \
  -a "upgrade=dist update_cache=yes" --become

# WP core + plugins
ssh github-deploy@ftp.pausatf.org \
  "sudo -u www-data wp core update --path=/var/www/html && \
   sudo -u www-data wp plugin update --all --path=/var/www/html"
```

6. **Verify** — Re-run the capture playbook to snapshot the current state:

```bash
ansible-playbook -i inventory/hosts.yml playbooks/capture-wp-inventory.yml \
  -l production
```

   Confirm no unexpected admin accounts remain:

```bash
sudo -u www-data wp user list --role=administrator --path=/var/www/html
```

7. **Document** — Open a GitHub Security Advisory at
   `https://github.com/pausatf/pausatf/security/advisories/new`.

### Known past incidents

- **Jan 2026 breach** — Unauthorized WP admin `mark_murray` (ID 46, created
  2026-02-14) was created. Remediated via `security-remediation.yml`. Auth
  keys/salts rotated. See issue history for full timeline.
