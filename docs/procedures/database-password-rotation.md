# Database Password Rotation Procedure

**Applies to**: WordPress production database on `ftp.pausatf.org`  
**Frequency**: Immediately after any suspected credential exposure; otherwise annually  
**Prerequisite**: Root SSH access to production server

---

## When to Rotate

- Any suspected server compromise
- wp-config.php or backup files were publicly accessible
- Employee/contractor offboarding who had DB access
- Annual rotation (schedule: first Sunday of January)

---

## Procedure

### Step 1 — Generate New Password

```bash
# On the server as root
NEW_PASS=$(openssl rand -base64 32 | tr -d '/+=' | head -c 32)
echo "New password: $NEW_PASS"
# Record in password manager BEFORE proceeding
```

### Step 2 — Update MySQL User

```bash
# Get current DB user from wp-config.php
DB_USER=$(wp config get DB_USER --path=/var/www/html --allow-root)
DB_NAME=$(wp config get DB_NAME --path=/var/www/html --allow-root)

echo "Rotating password for MySQL user: $DB_USER on database: $DB_NAME"

# Update the password
mysql -u root -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${NEW_PASS}'; FLUSH PRIVILEGES;"
```

### Step 3 — Update wp-config.php

```bash
# Use WP-CLI for clean update (handles quoting correctly)
wp config set DB_PASSWORD "${NEW_PASS}" --path=/var/www/html --allow-root

# Verify the change took effect
wp config get DB_PASSWORD --path=/var/www/html --allow-root
```

### Step 4 — Verify WordPress Connects

```bash
# This will fail with a DB error if the password is wrong
wp core version --path=/var/www/html --allow-root

# Full connectivity check
wp db check --path=/var/www/html --allow-root
```

### Step 5 — Update Ansible Vault

The Ansible `group_vars/production/wordpress.yml` contains `vault_wp_main_db_password`. Update it:

```bash
# On your local machine
ansible-vault edit ansible/group_vars/production/vault.yml
# Update vault_wp_main_db_password to the new value
```

Then commit the encrypted vault update:
```bash
git add ansible/group_vars/production/vault.yml
git commit -m "security: rotate production DB password"
git push
```

---

## MySQL Access Control Verification

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

## Rollback

If WordPress fails to connect after the password change:

```bash
# Restore the old password
mysql -u root -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${OLD_PASS}'; FLUSH PRIVILEGES;"
wp config set DB_PASSWORD "${OLD_PASS}" --path=/var/www/html --allow-root
wp core version --path=/var/www/html --allow-root
```

---

## Checklist

- [ ] New password generated with `openssl rand`
- [ ] New password recorded in password manager
- [ ] MySQL user password updated
- [ ] wp-config.php updated via `wp config set`
- [ ] WordPress connection verified with `wp core version`
- [ ] Ansible vault updated with new password
- [ ] Vault commit pushed to repository
- [ ] Old password purged from any notes/logs
