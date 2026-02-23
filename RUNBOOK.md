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
| production | >= 1.10.0 | ~> 2.76 | ~> 5.17 | — |
| staging | >= 1.6.0 | ~> 2.0 | ~> 5.15 | — |
| dev | >= 1.6.0 | ~> 2.0 | ~> 5.15 | — |
| cloudflare | >= 1.10.0 | — | ~> 5.17 | — |
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

---

## Ubuntu 24.04 Rebuild (feat/ubuntu24-rebuild)

Full rebuild to Ubuntu 24.04: Apache event MPM + PHP 8.3-FPM + Redis object
cache + Managed MySQL 8 + Certbot DNS-01 via Cloudflare + SSH via Tailscale
only + Cloudflare Full (Strict) SSL.

### Prerequisites

- Terraform >= 1.10.0
- Ansible >= 2.15
- `DO_TOKEN`, `SPACES_ACCESS_KEY_ID` / `SPACES_SECRET_ACCESS_KEY` configured
- `CLOUDFLARE_API_TOKEN` configured
- Ansible vault password (macOS Keychain: `security find-generic-password -s "pausatf-ansible-vault" -a "ansible" -w`)
- Tailscale authkey generated; `vault_tailscale_authkey` added to vault
- `vault_cloudflare_api_token` populated in vault (shared with Terraform and certbot)

### Staging Rehearsal

#### Provision staging droplet

```bash
cd terraform/environments/staging
terraform init
terraform plan -var 'droplet_image=ubuntu-24-04-x64'
terraform apply
```

#### Configure staging via Ansible

```bash
cd ansible
ansible-playbook -i inventory site.yml --limit stage --check --diff
ansible-playbook -i inventory site.yml --limit stage
```

#### Staging verification gates

All gates must pass before production cutover.

```bash
# Apache event MPM
ssh stage 'apachectl -V | grep MPM'
# Expected: Server MPM: event

# PHP-FPM config test
ssh stage 'php-fpm8.3 -t'

# PHP-FPM socket
ssh stage 'ls -la /run/php/php8.3-fpm.sock'

# Redis
ssh stage 'redis-cli ping'
# Expected: PONG

ssh stage 'redis-cli CONFIG GET maxmemory-policy'
# Expected: allkeys-lru

# Certbot dry-run
ssh stage 'certbot renew --dry-run'

# WordPress
ssh stage 'wp --allow-root core is-installed --path=/var/www/html && wp --allow-root db check --path=/var/www/html'

# No external Redis
ssh stage 'ss -tlnp | grep 6379'
# Expected: 127.0.0.1:6379 only

# UFW
ssh stage 'ufw status verbose'
# Expected: 80,443 ALLOW, tailscale0 ALLOW, 22 DENY

# Tailscale
ssh stage 'tailscale status'
```

### Production Cutover

#### Pre-cutover backup

```bash
doctl compute droplet-action snapshot <DROPLET_ID> --snapshot-name "pre-ubuntu24-rebuild-$(date +%Y%m%d)"
doctl databases backups list <DB_CLUSTER_ID>
```

#### Create new production droplet

The reserved IP stays; a new droplet is created and the IP is reassigned.

```bash
cd terraform/environments/production
terraform init -upgrade
terraform plan -out=tfplan
# Expect: 1 droplet to add, 1 reserved_ip_assignment to update
# moved blocks should show zero resource recreation for VPC, DB, firewall, alerts
terraform apply tfplan
```

If `moved` blocks produce unexpected destroy/create, abort and investigate.

#### Bootstrap Tailscale

```bash
ssh root@<NEW_DROPLET_IP>
tailscale up --authkey <AUTHKEY> --hostname pausatf-prod --advertise-tags=tag:server
tailscale status
```

#### Ansible full provision

```bash
cd ansible
ansible-playbook -i inventory site.yml --limit production
```

#### Certbot certificates

Runs as part of the certbot role. Verify:

```bash
ssh pausatf-prod 'certbot certificates'
```

#### Cloudflare environment

The Cloudflare env reads the production reserved IP from remote state:

```bash
cd terraform/environments/cloudflare
terraform init -upgrade
terraform plan -out=tfplan
# Review: zone settings update to strict, cache/WAF rulesets created
terraform apply tfplan
```

#### Production verification

```bash
# Apache
ssh pausatf-prod 'apachectl -V | grep MPM'

# PHP-FPM
ssh pausatf-prod 'php-fpm8.3 -t'

# Redis
ssh pausatf-prod 'redis-cli ping'

# WordPress
ssh pausatf-prod 'wp --allow-root core is-installed --path=/var/www/html && wp --allow-root db check --path=/var/www/html'

# Certbot renewal
ssh pausatf-prod 'certbot renew --dry-run'

# Listening services
ssh pausatf-prod 'ss -tlnp | grep -E "80|443|6379"'

# UFW
ssh pausatf-prod 'ufw status verbose'

# Tailscale
ssh pausatf-prod 'tailscale status'

# Cloudflare cache — static HIT
curl -sI https://www.pausatf.org/wp-content/themes/thesource/style.css | grep -i cf-cache-status
# Expected: HIT (after cache warm)

# Cloudflare cache — admin BYPASS
curl -sI https://www.pausatf.org/wp-admin/ | grep -i cf-cache-status
# Expected: BYPASS or DYNAMIC

# External port scan
nmap -Pn -p 22,80,443 www.pausatf.org
# Expected: 22=filtered, 80=open, 443=open
```

#### Decommission old droplet

Only after all verification passes and site stable for >= 24 hours:

```bash
doctl compute droplet-action power-off <OLD_DROPLET_ID>
# After 7 days with no issues:
doctl compute droplet delete <OLD_DROPLET_ID>
```

### Rollback

#### Rollback droplet creation

```bash
doctl compute reserved-ip-action assign <RESERVED_IP> <OLD_DROPLET_ID>
terraform destroy -target=module.wordpress.digitalocean_droplet.this
```

#### Rollback Ansible

```bash
git checkout main -- ansible/
ansible-playbook -i inventory site.yml --limit production
```

#### Rollback Cloudflare

```bash
git checkout main -- terraform/environments/cloudflare/
cd terraform/environments/cloudflare && terraform apply
```

#### Full rollback

1. Restore DO snapshot from pre-cutover backup
2. Reassign reserved IP to restored droplet
3. Revert Cloudflare Terraform to `main` and apply
4. Verify site operational

The reserved IP does not change during cutover — zero DNS propagation delay.
