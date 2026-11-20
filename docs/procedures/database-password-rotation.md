# Database Password Rotation Procedure

Rotation is manual. `ansible/playbooks/rotate-credentials.yml` rotates only the
WordPress salts and stops if it is given `rotate_db_password`.

**Frequency**: Immediately after any suspected credential exposure; otherwise annually

## When to Rotate

- Any suspected server compromise
- wp-config.php or backup files were publicly accessible
- Employee/contractor offboarding who had DB access
- Annual rotation (schedule: first Sunday of January)

## Current production: Managed MySQL and Docker Compose

This procedure has not been exercised yet. Check each value against the
[production runbook](../runbooks/production-operations.md) before you start.
WordPress on `pausatf-prod-v2` reads `WORDPRESS_DB_*` from the root-only `.env`
in `~/pausatf-deployment` and connects to the managed `pausatf-prod-db` cluster.
The site cannot reach the database from the reset in step 3 until the container
is recreated in step 5, so have both steps ready before you begin. Never print
the password; keep it in the password manager and in files created with
`umask 077`.

1. Confirm the last database backup succeeded:

   ```bash
   ssh pausatf-prod-v2 'systemctl status --no-pager pausatf-db-backup.service'
   ```

2. Find the cluster, the user WordPress connects as, and that user's current
   authentication plugin. `doctl` masks the password in this output.

   ```bash
   CLUSTER_ID=$(doctl databases list --format ID,Name --no-header | awk '$2 == "pausatf-prod-db" {print $1}')
   DB_USER=$(ssh pausatf-prod-v2 'sudo docker exec pausatf-wordpress printenv WORDPRESS_DB_USER')
   AUTH_PLUGIN=$(doctl databases user get "$CLUSTER_ID" "$DB_USER" --output json | jq -r '.. | .auth_plugin? // empty')
   echo "$CLUSTER_ID $DB_USER $AUTH_PLUGIN"
   ```

   Stop if any value is empty.

3. Reset the password. DigitalOcean generates the new value, and the old one
   stops working immediately. Passing the current plugin keeps it unchanged.

   ```bash
   umask 077
   NEW_PASS_FILE=$(mktemp)
   doctl databases user reset "$CLUSTER_ID" "$DB_USER" "$AUTH_PLUGIN" --show --output json \
     | jq -r '.. | .password? // empty' > "$NEW_PASS_FILE"
   test -s "$NEW_PASS_FILE" && echo "new password captured"
   ```

   Copy the value from the file into the password manager.

4. Replace `WORDPRESS_DB_PASSWORD` in the production `.env` with the new value:

   ```bash
   ssh -t pausatf-prod-v2 'sudoedit ~/pausatf-deployment/.env'
   ```

5. Recreate the WordPress container so it reads the new environment. A plain
   restart keeps the old environment. The stack runs as Compose project
   `pausatf-prod`; confirm with `sudo docker compose ls` first.

   ```bash
   ssh pausatf-prod-v2 'cd ~/pausatf-deployment && sudo docker compose -p pausatf-prod -f docker-compose.prod.yml up -d --force-recreate wordpress'
   ```

6. Verify. Run the database check in the
   [production runbook](../runbooks/production-operations.md) (it prints the
   database host and server version, not credentials), and check that the site
   answers:

   ```bash
   curl -sS -o /dev/null -w '%{http_code}\n' https://www.pausatf.org/
   ```

   The backup units read the password from the running container, so the next
   `pausatf-db-backup` run uses the new value. Check that run in
   `journalctl -u pausatf-db-backup`.

7. Update the Ansible vault so Ansible-rendered configuration matches. Set
   `vault_managed_wp_db_password` in the git-ignored `ansible/group_vars/vault.yml`,
   re-encrypt it, and replace the `ANSIBLE_VAULT_FILE` secret:

   ```bash
   ansible-vault edit ansible/group_vars/vault.yml
   base64 < ansible/group_vars/vault.yml | gh secret set ANSIBLE_VAULT_FILE -R pausatf/pausatf
   ```

8. If WordPress connects as the `wordpress` user, Terraform also tracks it
   (`digitalocean_database_user` in the production stack) with a computed
   password. The next production plan should show no change to that user; if it
   proposes replacing it, stop and investigate before applying.

9. Remove the temporary file: `rm -P "$NEW_PASS_FILE"`.

There is no way back to the old password. If WordPress cannot connect after
step 5, check the `.env` value against the password manager and recreate the
container again. If the new value is lost, repeat from step 3.

Staging and dev read `vault_wp_stage_db_password` and
`vault_wp_dev_db_password`. Before rotating there, find where each host's
WordPress reads the password (container environment or `wp-config.php`) and
follow the same order: reset, update the consumer, verify, then update the vault.

## Retired host (reference only)

**Applies to**: the retired production server `ftp.pausatf.org` (host MySQL).
**Prerequisite**: Root SSH access to production server

### Procedure

#### Step 1 — Generate New Password

```bash
# On the server as root
NEW_PASS=$(openssl rand -base64 32 | tr -d '/+=' | head -c 32)
echo "New password: $NEW_PASS"
# Record in password manager BEFORE proceeding
```

#### Step 2 — Update MySQL User

```bash
# Get current DB user from wp-config.php
DB_USER=$(wp config get DB_USER --path=/var/www/html --allow-root)
DB_NAME=$(wp config get DB_NAME --path=/var/www/html --allow-root)

echo "Rotating password for MySQL user: $DB_USER on database: $DB_NAME"

# Update the password
mysql -u root -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${NEW_PASS}'; FLUSH PRIVILEGES;"
```

#### Step 3 — Update wp-config.php

```bash
# Use WP-CLI for clean update (handles quoting correctly)
wp config set DB_PASSWORD "${NEW_PASS}" --path=/var/www/html --allow-root

# Verify the change took effect
wp config get DB_PASSWORD --path=/var/www/html --allow-root
```

#### Step 4 — Verify WordPress Connects

```bash
# This will fail with a DB error if the password is wrong
wp core version --path=/var/www/html --allow-root

# Full connectivity check
wp db check --path=/var/www/html --allow-root
```

#### Step 5 — Update Ansible Vault

Production now runs on DigitalOcean Managed MySQL. Update the runtime DB secret used by
`wp-config.php`:

```bash
# On your local machine
ansible-vault edit ansible/group_vars/production/vault.yml
# Update vault_managed_wp_db_password to the new value
# (Optional legacy compatibility fallback: vault_wp_main_db_password)
```

Then commit the encrypted vault update:
```bash
git add ansible/group_vars/production/vault.yml
git commit -m "security: rotate production DB password"
git push
```

---

### MySQL Access Control Verification

After rotation, confirm the lockdown is correct:

```bash
# Check bind address — should be 127.0.0.1 or socket only
mysql -u root -e "SHOW VARIABLES LIKE 'bind_address';"

# Check WordPress user grants — should be @'localhost' only
mysql -u root -e "SHOW GRANTS FOR '${DB_USER}'@'localhost';"

# Verify no remote root access
mysql -u root -e "SELECT Host, User, plugin FROM mysql.user WHERE User = 'root';"
# Expected: only 'localhost' and 'socket' entries

# Confirm no wildcard host grants for any user
mysql -u root -e "SELECT Host, User FROM mysql.user WHERE Host NOT IN ('localhost', '127.0.0.1', '::1', 'socket');"
# Expected: empty result set
```

---

### Rollback

If WordPress fails to connect after the password change:

```bash
# Restore the old password
mysql -u root -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${OLD_PASS}'; FLUSH PRIVILEGES;"
wp config set DB_PASSWORD "${OLD_PASS}" --path=/var/www/html --allow-root
wp core version --path=/var/www/html --allow-root
```

---

### Checklist

- [ ] New password generated with `openssl rand`
- [ ] New password recorded in password manager
- [ ] MySQL user password updated
- [ ] wp-config.php updated via `wp config set`
- [ ] WordPress connection verified with `wp core version`
- [ ] Ansible vault updated with new password
- [ ] Vault commit pushed to repository
- [ ] Old password purged from any notes/logs
