# Ansible Roles

Reference for all roles in `ansible/roles/`. Each role is self-contained and
idempotent. Roles are composed in `playbooks/site.yml`.

---

## Role: `common`

**Purpose**: Baseline OS configuration applied to every host.

**Key tasks**:
- Installs essential packages (curl, git, vim, ufw, fail2ban, python3)
- Creates the `deploy` system user with sudo access
- Configures UFW firewall rules, SSH hardening, and sysctl security settings
- Enables unattended security upgrades

**Tags**: none (always runs)

**Vars required**: none (uses defaults)

---

## Role: `apache`

**Purpose**: Installs and configures Apache 2 with MPM Prefork for WordPress on
the production host.

**Key tasks**:
- Installs `apache2`, `apache2-utils`, `libapache2-mod-security2`
- Enables required modules from `apache_modules` var; disables `mpm_event`/`mpm_worker`
- Deploys virtual host configs (HTTP + SSL) from templates for each entry in `wordpress_installs`
- Deploys hardening configs: `block-backups.conf` (denies `.bak`, `.sql`, `.zip`, etc.) and `block-xmlrpc.conf`
- Verifies config with `apache2ctl configtest` before marking complete

**Tags**: none

**Vars required**: `apache_modules`, `wordpress_installs`, `apache_mpm_prefork`

---

## Role: `php`

**Purpose**: Installs PHP (version from `php_version`) plus legacy PHP 7.2 for
Apache on the production host.

**Key tasks**:
- Adds the `ondrej/php` PPA and installs all required extensions
- Deploys `php.ini` for both the Apache SAPI and CLI
- Deploys OPcache configuration and enables the module
- Sets the default CLI PHP version via `update-alternatives`

**Tags**: none

**Vars required**: `php_version`, `php_memory_limit`, `php_opcache_*` settings
from `group_vars/all.yml`

---

## Role: `mysql`

**Purpose**: Installs MySQL 5.7, hardens defaults, and creates WordPress
databases and users.

**Key tasks**:
- Installs `mysql-server`, `python3-pymysql` (required for Ansible MySQL modules)
- Sets root password; removes anonymous users and the `test` database
- Creates databases and users for each entry in `wordpress_installs` with `ALL` privileges scoped to `localhost`
- Enables slow query log (threshold: 2 s) to `/var/log/mysql/mysql-slow.log`

**Tags**: none

**Vars required**: `mysql_root_password` (vault), `wordpress_installs[*].db_name`,
`wordpress_installs[*].db_user`, `wordpress_installs[*].db_password`

---

## Role: `wordpress`

**Purpose**: Manages WordPress application state via WP-CLI — does not touch
the web server.

**Key tasks**:
- Installs WP-CLI to `/usr/local/bin/wp`
- Iterates `wordpress_installs` and delegates to `manage-install.yml` for each:
  core version, plugin updates (excluding `wpcli_exclude_plugins`), and theme
  updates (excluding `wpcli_exclude_themes`)

**Tags**: `wordpress`

**Vars required**: `wordpress_installs`, `wpcli_enabled`, `wpcli_exclude_plugins`,
`wpcli_exclude_themes`

---

## Role: `openlitespeed`

**Purpose**: Installs and configures OpenLiteSpeed on staging and dev hosts.

**Key tasks**:
- Adds the LiteSpeed apt repository and installs `openlitespeed`
- Deploys main config (`httpd_config.conf`) and WordPress vhost config from templates
- Sets the OLS admin password (idempotent via `admpass.sh`)
- Opens ports 80 and 443 in UFW if enabled

**Tags**: none

**Vars required**: `vault_ols_admin_password`, `wordpress_installs[0].path`,
`ufw_enabled`

---

## Role: `lsphp`

**Purpose**: Installs LiteSpeed PHP (`lsphp`) and extensions for use with
OpenLiteSpeed.

**Key tasks**:
- Adds the LiteSpeed apt repository
- Computes the lsphp package suffix from `php_version` (e.g. `8.3` → `83`)
- Installs `lsphp<suffix>` plus extensions: mysql, curl, gd, imagick, mbstring,
  intl, zip, soap, bcmath, opcache, redis, memcached
- Deploys a tuned `99-ansible.ini` (OPcache, upload limits, execution time)
- Restarts `lshttpd` to pick up the new PHP version

**Tags**: none

**Vars required**: `php_version`

---

## Role: `fail2ban`

**Purpose**: Configures Fail2ban to protect SSH and WordPress login endpoints.

**Key tasks**:
- Installs `fail2ban`
- Deploys `jail.local` from template with configured ban time (1 h), find time
  (10 min), and max retry (5)
- Deploys custom filters `wordpress.conf` and `wordpress-hard.conf` to
  `/etc/fail2ban/filter.d/`
- Disables rsyslog repeated-message reduction so Fail2ban sees each authentication
  event and routes the WordPress jails to `/var/log/auth.log`, where the MU
  plugin's `LOG_AUTHPRIV` events are written on Ubuntu.

**Tags**: none

**Vars required**: `fail2ban_bantime`, `fail2ban_findtime`, `fail2ban_maxretry`,
`fail2ban_jails`

---

## Role: `cloudflare`

**Purpose**: Manages Cloudflare zone settings from the server side and keeps
Cloudflare IP ranges current in Apache.

**Key tasks**:
- Deploys `cloudflare-ips.conf` to Apache so real visitor IPs are logged
  (not Cloudflare proxy IPs)
- Downloads current IPv4/IPv6 Cloudflare ranges weekly
- Sets SSL mode, minimum TLS version, and Always Use HTTPS via the Cloudflare
  API
- Optionally purges the entire Cloudflare cache when `cloudflare_purge_cache: true`
- DNS record management is disabled by default (`cloudflare_manage_dns: false`);
  DNS is owned by Terraform

**Tags**: `cloudflare`, `cloudflare-purge`, `cloudflare-dns`

**Vars required**: `cloudflare_zone_id` (vault), `cloudflare_api_token` (vault),
`cloudflare_ssl_mode`, `cloudflare_min_tls_version`, `cloudflare_always_use_https`

---

## Role: `monitoring`

**Purpose**: Installs host-level monitoring utilities.

**Key tasks**:
- Installs `htop`, `iotop`, `iftop`, `nethogs`, `sysstat`, `vnstat`
- Enables `sysstat` collection and starts `vnstat` for bandwidth tracking

**Tags**: none

**Vars required**: none

---

## Role: `newrelic`

**Purpose**: Installs the New Relic infrastructure agent and PHP APM agent.

**Key tasks**:
- Adds the New Relic apt repository and installs `newrelic-infra`
- Deploys `newrelic-infra.yml` with the license key (mode 0600)
- Installs `newrelic-php5` agent and deploys `newrelic.ini` for the configured
  PHP version
- Enables the PHP module via `phpenmod`

**Tags**: none

**Vars required**: `newrelic_license_key` (vault), `newrelic_app_name`,
`php_version`

---

## Role: `pausatf_site`

**Purpose**: Deploys the consolidated PAUSATF Site plugin (`pausatf/pausatf-site`)
and, where enabled, retires the plugins it absorbed. Enabled for dev only.

**Key tasks**:
- Fetches the private repository over SSH into a root-only bare clone, exports
  the pinned `pausatf_site_version` (a full commit SHA) into a root-only staging
  directory and swaps it into `wp-content/plugins/pausatf-seo` with one rename;
  refuses to replace a `pausatf-seo` entry it did not install, and re-checks
  the destination immediately before the swap
- Writes the deploy key from `pausatf_site_deploy_key` (vault) to
  `pausatf_site_git_key_file` as a root-only file
- Activates `pausatf-seo` with WP-CLI
- With `pausatf_site_retire_superseded`, refused on production: deactivates
  PAUSATF Passwordless Login and PAUSATF Core, then removes their directories
  and the superseded must-use files, keeping tar archives in
  `/var/backups/pausatf-site-retired`. An entry containing links, special or
  hard-linked files, or files owned outside `pausatf_site_retire_owners` is
  left in place and the run stops. `pausatf-results-manager` is not changed.

**Tags**: `pausatf_site`

**Vars required**: `pausatf_site_enabled`; the host's root account needs read
access to `pausatf/pausatf-site` (dev: `vault_pausatf_site_deploy_key` and
`pausatf_site_git_key_file`); `python3` and `tar` on the host
