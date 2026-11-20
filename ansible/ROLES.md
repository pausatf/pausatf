# Ansible roles, playbooks and inventory

This file describes what the code under `ansible/` does today. There are 23
roles in `ansible/roles/`, one orchestration playbook (`ansible/site.yml`) and
18 playbooks in `ansible/playbooks/`. For setup and day-to-day usage see
`ansible/README.md`.

Defaults quoted below come from each role's `defaults/main.yml`. Most are
overridden for real hosts in `ansible/group_vars/` (see
[Inventory and group_vars](#inventory-and-group_vars)); where the override
matters, it is noted. Secrets are never stored in the repository. Variables that
need one are read from an Ansible Vault file (`group_vars/vault.yml`, which is
git-ignored and not present in the checkout) through `vault_*` names.

## Theme and plugin scope

- The theme Ansible deploys today is **TheSource-child**
  (`ansible/roles/wordpress/files/TheSource-child`) on top of the vendored
  TheSource parent theme (`ansible/roles/wordpress/files/TheSource`). The
  `wordpress` role copies both only for sites whose `active_theme` is
  `TheSource-child`, which is what `group_vars/all.yml` sets.
- The GeneratePress child theme (repository `pausatf-generatepress-child`) is a
  staged successor. Ansible does not deploy it.
- Dev WordPress plugins belong to the `pausatf-deployment` repository, not to
  Ansible. See `docs/runbooks/deployment.md`.
- The one plugin Ansible installs itself is `pausatf-results-manager`, through
  the `pausatf_plugin` role. The plugin list the `wordpress` role installs and
  activates comes from `wp_main_plugins` in group_vars.

## Roles

### Host groups and order at a glance

`site.yml` runs these roles in this order. Roles marked "conditional" are
skipped unless the named variable is true.

| Play (hosts) | Roles |
| --- | --- |
| all | `common`; `tailscale` when `tailscale_enabled` |
| webservers | `php` or `php-fpm`, `modsecurity`, `certbot` (stage), `apache` or `lsphp` + `openlitespeed`, `certbot` (finish), `mysql`, `redis`, `postfix`, `fail2ban`, `apparmor`, `wordpress`, `pausatf_plugin`, `backups`, `security_hardening`, `cloudflare`, `newrelic` |
| webservers (tag `discovery`) | `discovery` when `run_discovery` |
| webservers (tag `migration_prep`) | `migration_prep` when `run_migration_prep` |
| all | `monitoring` |

The web-stack block in `site.yml` has a `rescue` that prints the failing task and
checks that `apache2` (or `lsws`) is still running, and an `always` section that
re-enables `certbot.timer` and `pausatf-certbot-deploy.timer` if the certbot
role paused them.

### `common`

Baseline for every host. Sets the hostname and `/etc/hosts`, installs base
packages, configures UFW, creates the `deploy` user and the admin users
`somethingwithproof` and `jteeters` (SSH keys fetched from GitHub, passwordless
sudo), hardens `sshd`, sets the timezone, enables unattended security upgrades
and applies sysctl settings.

- UFW (`tasks/firewall.yml`): default deny, SSH only from the networks in
  `allowed_ssh_networks`, HTTP/HTTPS from anywhere or, when
  `ufw_restrict_web_to_cloudflare` is true, only from Cloudflare ranges fetched
  at run time. Cloudflare Tunnel ports (7844 TCP and UDP) are allowed. The
  firewall is reset first only when `ufw_reset` is true.
- SSH (`tasks/ssh.yml`): `AllowUsers {{ ssh_allow_users }}`.
- Variables: `timezone` (`America/Los_Angeles`), `ssh_allow_users`
  (`deploy jteeters somethingwithproof`), `allowed_ssh_networks`,
  `ufw_restrict_web_to_cloudflare`, `ufw_reset`.
- Used by: `site.yml` (all hosts), `playbooks/base.yml`,
  `playbooks/rebuild-ubuntu2404.yml`. It is also a declared dependency of
  `apache`, `certbot`, `fail2ban`, `mysql`, `php-fpm`, `redis` and `tailscale`.
- Tags: none.

### `tailscale`

Adds the Tailscale apt repository, installs and starts `tailscaled`, allows all
traffic on `tailscale0`, reports whether the node is authenticated, and, once
SSH works over Tailscale, denies SSH from the public internet in UFW. It does
not authenticate the node; it prints the manual step.

- Variables: `tailscale_enabled` (`false`), `tailscale_authkey` (empty).
- Used by: `site.yml` (only when `tailscale_enabled`), `playbooks/base.yml`,
  `playbooks/rebuild-ubuntu2404.yml`.
- Tags: none (the rebuild playbook tags it `tailscale`, `security`, `ssh`).

### `apache`

Apache 2.4 for the WordPress sites. Installs Apache, selects the MPM, enables
modules, templates `apache2.conf`, ports, MPM, security and SSL configuration,
removes the default vhost, and deploys per-site HTTP and HTTPS vhosts from
`wordpress_installs` plus `additional_vhosts`. It also:

- blocks backup-file access, `xmlrpc.php` (`apache_block_xmlrpc`), and the
  `ai1wm-backups` directory;
- restricts phpMyAdmin to localhost;
- deploys the `/data/` alias for legacy results (`legacy-data.conf.j2`);
- deploys `scorer_upload.php` to `/var/www/html/teeters-php/` with its token and
  salt written under `/etc/pausatf/` from `vault_scorer_upload_token` and
  `vault_scorer_salt`;
- optionally deploys a block-direct-IP vhost (`block_direct_ip`) and a
  localhost-only dev vhost (`apache_dev_local_enabled`).

With `use_php_fpm` the role disables `mod_php` and `mpm_prefork`, enables
`mpm_event`, `proxy` and `proxy_fcgi`.

- Key variables: `apache_mpm` (`event`; prefork is still supported through
  `mpm_prefork.conf.j2`), `apache_modules` (`rewrite`, `headers`, `ssl`,
  `proxy`, `proxy_fcgi`), `apache_block_xmlrpc` (`true`),
  `apache_main_docroot` (`/var/www/html`), `apache_dev_local_*`. A preflight
  task requires `apache_server_name` (set in `group_vars/all.yml`).
- Used by: `site.yml` when `web_server` is `apache`,
  `playbooks/webserver.yml`, `playbooks/rebuild-ubuntu2404.yml`; a dependency of
  `wordpress`.
- Tags: none inside the role; `playbooks/webserver.yml` applies `webserver`,
  `apache`.
- Molecule scenario: `roles/apache/molecule/`.

### `php`

PHP through `mod_php` from the Ondrej PPA, for hosts that do not use FPM.
Installs PHP, deploys `php.ini`, CLI settings, security hardening ini files and
OPcache settings, enables OPcache, sets the default CLI version and creates the
session directory.

- Variables: `php_version` (`8.3`), `php_open_basedir`
  (`/var/www/html:/tmp:/var/tmp:/usr/share/php`), `php_disable_functions`
  (`exec,passthru,shell_exec,system,proc_open,popen,parse_ini_file,show_source`).
- Used by: `site.yml` when `web_server` is `apache` and `use_php_fpm` is false;
  `playbooks/webserver.yml`.
- Tags: none (`playbooks/webserver.yml` applies `php`).

### `php-fpm`

PHP-FPM from the Ondrej PPA. Removes `mod_php` if present, deploys the FPM pool
(`www.conf.j2`) and `php.ini`, sets the default CLI version, creates the session
directory and starts `php<version>-fpm`. It detects Docker and skips service
management there.

- Variables: `php_version` (`8.3`), `php_fpm_socket`
  (`/run/php/php8.3-fpm.sock`), `php_fpm_max_children` (15),
  `php_fpm_start_servers` (3), `php_fpm_min_spare` (2), `php_fpm_max_spare` (5),
  `php_fpm_max_requests` (500), `php_memory_limit` (`256M`),
  `php_upload_max_filesize` and `php_post_max_size` (`64M`),
  `php_max_execution_time` and `php_max_input_time` (300), `php_max_input_vars`
  (3000), `php_opcache_*`, `php_date_timezone`.
- Used by: `site.yml` when `use_php_fpm` is true (production, production_new and
  dev set it; `group_vars/all.yml` sets it true), `playbooks/webserver.yml`,
  `playbooks/rebuild-ubuntu2404.yml`.
- Tags: none (`php`, `web`, `webserver` in the rebuild playbook).

### `lsphp`

LiteSpeed PHP (`lsphp<version>`) for OpenLiteSpeed hosts. Adds the LiteSpeed
apt repository, installs the lsphp packages and CLI settings, and gracefully
restarts `lshttpd`. Details in `roles/lsphp/README.md`.

- Variables: `php_version` (`8.3`; staging overrides it to `8.4`).
- Used by: `site.yml` and `playbooks/webserver.yml` when `web_server` is
  `openlitespeed`.
- Tags: none (`php` in `playbooks/webserver.yml`).

### `openlitespeed`

OpenLiteSpeed web server: repository, package, `lsws` service, admin password,
main config (`httpd_config.conf.j2`) and per-site vhost config
(`vhconf.conf.j2`), document root, and UFW rules for HTTP/HTTPS if UFW is
enabled. Details in `roles/openlitespeed/README.md`.

- Variables: `ols_admin_port` (`7080`), `ols_admin_password` (defaults to
  `vault_ols_admin_password`, which must exist in the vault).
- Used by: `site.yml` and `playbooks/webserver.yml` when `web_server` is
  `openlitespeed`. In `inventory/hosts.yml` that is `pausatf-stage`.
- Tags: none (`webserver`, `openlitespeed` in `playbooks/webserver.yml`).

### `modsecurity`

ModSecurity v2 WAF for Apache. Installs `libapache2-mod-security2` and
`modsecurity-crs`, creates working directories, templates `modsecurity.conf`,
deploys CRS pre- and post-rule exclusion files and the WordPress Rule Set
(`wprs/`), and configures audit-log rotation.

- Variables: `modsecurity_enabled` (`true`), `modsecurity_rule_engine` (`On`),
  `modsecurity_audit_engine` (`RelevantOnly`; production overrides to `On`),
  `modsecurity_request_body_limit` (13107200), `modsecurity_audit_log`
  (`/var/log/apache2/modsec_audit.log`), PCRE and body-size limits.
- Used by: `site.yml` when `web_server` is `apache` and
  `modsecurity_enabled`; `playbooks/webserver.yml`.
- Tags: none (`security`, `modsecurity` in `playbooks/webserver.yml`).

### `apparmor`

Installs AppArmor, enables the service, deploys a local override of the
`usr.sbin.apache2` profile for the ModSecurity paths, and puts the Apache
profile in enforce mode, or complain mode when enforcement is off.

- Variables: `apparmor_apache_enforce` (`true` in the role; `site.yml` only runs
  the role when the variable is true, defaulting to false).
- Used by: `site.yml` only. Production sets `apparmor_apache_enforce: true`.
- Tags: `apparmor`.

### `certbot`

Let's Encrypt certificates through the Cloudflare DNS-01 challenge. Installs
certbot and the Cloudflare plugin, writes the Cloudflare credentials file,
deploys a post-renewal hook and an independent synchronization service and timer
(`pausatf-certbot-deploy`) that copy the certificate to the origin directory.
It runs twice in `site.yml`: first with `certbot_deploy_stage_only: true` (pauses
the automatic jobs and stages certificates before the web server is configured),
then with `false` (validates, synchronizes, and re-enables the timers).

- Variables: `certbot_enabled` (`false`), `certbot_email`, `certbot_domains`
  (`pausatf.org`, `www.pausatf.org`), `certbot_lineage_name`,
  `certbot_deploy_target_dir` (`/etc/ssl/pausatf` in production),
  `certbot_deploy_container_name`.
- Used by: `site.yml` when `certbot_enabled`, `playbooks/webserver.yml`,
  `playbooks/rebuild-ubuntu2404.yml`. Production enables it; staging and dev set
  it false and use a manually renewed Cloudflare Origin CA certificate
  (`tls_origin_*` variables).
- Tags: none (`ssl`, `certbot` in the playbooks). Molecule scenario with deploy
  hook tests in `roles/certbot/molecule/`.

### `mysql`

Local MySQL server (the distribution `mysql-server` package).
Skipped entirely when `mysql_managed` is true. When it runs it asserts
`mysql_root_password`, installs the server and Python drivers, deploys
`mysqld.cnf`, sets the root password and `.my.cnf`, removes anonymous users and
the test database, creates databases and users from `wordpress_installs`, and
enables the slow query log.

- Variables: `mysql_managed` (`false`), `mysql_root_password` (vault),
  `mysql_bind_address` (`127.0.0.1`), `mysql_port` (3306), `mysql_datadir`,
  `mysql_max_allowed_packet` (`64M`), `mysql_expire_logs_days` (14).
- The `mysql_version` variable in group_vars is not read by the role.
- Used by: `site.yml` when `mysql_enabled` (group_vars default is false; the
  production, production_new and dev environments use DigitalOcean Managed MySQL
  with `mysql_managed: true`), `playbooks/database.yml`; a dependency of
  `wordpress` when `mysql_managed` is false.
- Tags: none.

### `redis`

Redis for the WordPress object cache. Installs `redis-server`, deploys
`redis.conf`, adds `www-data` to the redis group for socket access, starts the
service, and checks that Redis listens on 127.0.0.1 only.

- Variables: `redis_enabled` (`false` in the role, `true` in `all.yml`),
  `redis_maxmemory` (`256mb`; production sets `128mb`), `redis_maxmemory_policy`
  (`allkeys-lru`), `redis_socket` (`/var/run/redis/redis.sock`).
- Used by: `site.yml` when `redis_enabled`, `playbooks/database.yml`,
  `playbooks/rebuild-ubuntu2404.yml`.
- Tags: none (`redis`, `cache` in the rebuild playbook).

### `postfix`

Outbound mail. Installs postfix and OpenDKIM, templates `main.cf`, sets the
mailname, generates a DKIM key pair, configures OpenDKIM (`opendkim.conf`,
`TrustedHosts`, KeyTable, SigningTable), and prints the DNS record to publish.

- Variables: `postfix_enabled` (`true`), `postfix_hostname` and
  `postfix_domain` (`pausatf.org`), `postfix_dkim_enabled` (`true`),
  `postfix_dkim_selector` (`mail`).
- Used by: `site.yml` when `postfix_enabled`.
- Tags: `postfix` (set on the include in `site.yml`).

### `fail2ban`

Brute-force protection. Installs fail2ban and ipset, makes sure individual auth
events are logged, deploys `jail.local` with jails for `sshd`, `apache-auth`,
`apache-badbots`, `apache-noscript`, `apache-overflows`, `openlitespeed-auth`
and four WordPress jails (`wordpress-auth`, `wordpress-enum`, `wordpress-hard`,
`wordpress-soft`) with matching filters. It also installs daily cron refreshes
for an AbuseIPDB blocklist and a Firehol blocklist, both loaded into ipsets.

- Variables: `fail2ban_maxretry` (5), `fail2ban_bantime` (3600),
  `fail2ban_findtime` (600), `fail2ban_destemail` (`admin@pausatf.org`),
  `fail2ban_abuseipdb_enabled` (`true`), `fail2ban_abuseipdb_apikey` (empty;
  supply from the vault), `fail2ban_abuseipdb_confidence` (90),
  `fail2ban_firehol_enabled` (`true`), `fail2ban_manage_service` (`true`).
- Used by: `site.yml`, `playbooks/wordpress.yml`,
  `playbooks/rebuild-ubuntu2404.yml`.
- Tags: none (`security`, `fail2ban` in the playbooks).

### `wordpress`

WordPress application management through WP-CLI, driven by
`wordpress_installs` (a list of site dicts, each passed to
`tasks/manage-install.yml` as `wp_site`). Per site it:

- writes `.htaccess`, downloads and checksum-verifies core when missing, updates
  core, database, plugins and themes (honoring `wpcli_exclude_plugins` and
  `wpcli_exclude_themes`);
- installs and activates `redis-cache` and enables the object-cache drop-in, and
  installs LiteSpeed Cache on stage and dev;
- sets ownership and permissions, blocks PHP execution in `uploads/`;
- deploys the TheSource parent theme and the TheSource-child theme when
  `active_theme` is `TheSource-child`, and falls back to a stock theme if needed;
- runs `user-security.yml`, which removes users in `wp_users_remove` and fails if
  an administrator not listed in `wp_users` exists.

Role-wide tasks deploy the must-use plugins from `files/mu-plugins/`
(`disable-user-enum.php`, `noindex-non-prod.php`,
`pausatf-widget-visibility.php`), the Cloudflare auto-purge mu-plugin, the
`wp-cron` guard script and a cron health check (both only meaningful on the
active web host, `is_active_web`), the Jetpack Boost and Photon settings
(`jetpack.yml`), and PHP 8.4 compatibility patches (`patches.yml`, including the
`pausatf-patch-manager` mu-plugin and fixes to the vendored TheSource files).
It manages `wp-config.php` only for installs with `manage_wp_config: true`;
otherwise it only preserves runtime settings in the existing file.

`tasks/deploy-safe.yml` (pre-deploy backup, then deploy) and `tasks/wp-cli.yml`
(run one WP-CLI command) are helper task files that nothing in the repository
includes; call them with `include_role` and `tasks_from`.

- Key variables: `wordpress_installs`, `wp_users`, `wp_users_remove`,
  `wp_main_plugins` / `wp_managed_plugins`, `wordpress_db_*`,
  `wordpress_auth_key` and the other keys and salts (all vault-sourced),
  `wpcli_enabled`, `wpcli_path` (`/usr/local/bin/wp`), `wpcli_update_*`,
  `wp_environment_type`, `wp_disable_cron`, `is_active_web`,
  `jetpack_boost_defer_js`, `jetpack_photon_enabled`.
- Used by: `site.yml` when `wordpress_enabled`, `playbooks/wordpress.yml`,
  `playbooks/rebuild-ubuntu2404.yml`. Declares `mysql` and `apache` as
  dependencies (conditionally).
- Tags: `wordpress`, `wordpress_config`, `wp_cron`, `wp_plugins`, `wp_patches`,
  `mu_plugins`, `jetpack`, `cloudflare`, `security`. `playbooks/wordpress.yml`
  adds `wordpress`, `deploy`.
- Files and theme notes: `roles/wordpress/files/README.md`.

### `pausatf_plugin`

Installs the `pausatf-results-manager` plugin on the production host. It clones
`https://github.com/pausatf/pausatf-results-manager.git` into
`/var/www/html/wp-content/plugins/pausatf-results-manager`, updates it to the
requested ref if the checkout exists, sets ownership, and activates it through
WP-CLI if it is not active. Per its defaults the plugin tree is owned by
`root:root`, so the web server can read but not modify it.

- Variables: `pausatf_plugin_repo`, `pausatf_plugin_version` (`main`),
  `pausatf_plugin_path`, `pausatf_plugin_owner` and `pausatf_plugin_group`
  (`root`), `pausatf_wp_docroot` (`/var/www/html`).
- Used by: `site.yml` only, and only when `pausatf_plugin_enabled` is true. No
  inventory file sets it, so the role is off unless a run passes it.
- Tags: `pausatf_plugin`.

### `backups`

Local WordPress backup. Creates the backup and uploads directories, deploys
`backup-pausatf.sh` to `backup_script_path`, schedules a root cron job and
configures logrotate (`/etc/logrotate.d/pausatf-backup`, log
`/var/log/pausatf-backup.log`).

- Variables: `backup_dir` (`/var/backups/wordpress`), `backup_retention_days`
  (7), `backup_wp_docroot` (`/var/www/html`), `backup_cron_hour` (`*/8`),
  `backup_cron_minute` (`0`), `backup_script_path`
  (`/usr/local/sbin/backup-wordpress.sh`).
- Used by: `site.yml` when `backups_enabled` (default false; no inventory file
  sets it).
- Tags: `backups`.

### `monitoring`

Host monitoring. Installs `htop`, `iotop`, `iftop`, `nethogs`, `sysstat` and
`vnstat`, starts sysstat and vnstat, deploys a health-check endpoint
(`.well-known/` page, protected by `healthcheck_token`), an Apache health-check
script run every 30 minutes, and a WordPress backup script scheduled from
`backup_schedule`.

- Variables: `monitoring_enabled` (`true`), `healthcheck_token` (vault),
  `backup_schedule` (cron string, `0 2 * * *` in `all.yml`),
  `backup_destination`, `backup_retention_days`, `backup_keep_count`.
- The role ships `monitrc.j2` and service templates, and `all.yml` sets
  `monit_*` variables, but no task deploys monit configuration.
- Used by: the last play of `site.yml` (all hosts), `playbooks/monitoring.yml`,
  `playbooks/rebuild-ubuntu2404.yml`.
- Tags: `monitoring` (in the playbooks).

### `newrelic`

Installs the New Relic infrastructure agent and PHP agent from the New Relic
apt repository, templates `newrelic-infra.yml` and `newrelic.ini`, enables the
PHP module and starts the services. The role fails if `newrelic_license_key` is
empty.

- Variables: `newrelic_enabled` (`false` in the role, `true` in `all.yml`),
  `newrelic_license_key` (from `vault_newrelic_license_key`),
  `newrelic_app_name` (`PAUSATF WordPress`).
- Used by: `site.yml` when enabled and the key is set (otherwise a debug message
  says it was skipped), `playbooks/monitoring.yml`,
  `playbooks/rebuild-ubuntu2404.yml`.
- Tags: none (`newrelic`, `monitoring` in the playbooks).

### `cloudflare`

Manages Cloudflare settings and the origin's Cloudflare integration. It installs
helper packages, writes the API token file (root-only), deploys an Apache
RemoteIP config that trusts Cloudflare ranges and a weekly cron job that
refreshes them, verifies the zone is active, and sets SSL mode, minimum TLS
version, Always Use HTTPS and security level through the API. It also deploys
cache-purge scripts, a per-minute cache change monitor, and an inotify based
`cf-purge-watcher` systemd service. `tasks/dns.yml` manages DNS records from
`cloudflare_dns_records` when `cloudflare_manage_dns` is true; `all.yml` sets it
false because DNS is managed in Terraform.

- Variables: `cloudflare_enabled` (`false` in the role, `true` in `all.yml`),
  `cloudflare_zone_id` and `cloudflare_api_token` (vault), `cloudflare_zone`,
  `cloudflare_ssl_mode` (`full` in the role, `strict` in `all.yml`),
  `cloudflare_min_tls_version` (`1.2`), `cloudflare_security_level` (`medium`),
  `cloudflare_always_use_https` (`true`), `cloudflare_purge_cache` (`false`),
  `cloudflare_manage_dns` (`false`).
- Used by: `site.yml` only when enabled and both `cloudflare_zone_id` and
  `cloudflare_api_token` are non-empty (otherwise a warning is printed),
  `playbooks/wordpress.yml`, `playbooks/rebuild-ubuntu2404.yml`.
- Tags: `cloudflare`, `cloudflare-dns`, `cloudflare-purge`.

### `security_hardening`

Server hardening beyond `common`: SSH `PermitRootLogin`, `AllowUsers` and
`MaxAuthTries`, unattended security upgrades, `wp-config.php` mode 640,
`wp-content` and `uploads` mode 755, removal of `snapd`, disabling `cups`,
`avahi-daemon` and `bluetooth` if present, and extra sysctl settings.

- Variables: `hardening_ssh_permit_root_login` (`no`),
  `hardening_ssh_max_auth_tries` (6), `hardening_ssh_allow_users` (`deploy`,
  `jteeters`, `somethingwithproof`), `hardening_wp_docroot`,
  `hardening_disable_services`, `hardening_remove_packages`, `hardening_sysctl`.
- Used by: `site.yml` when `security_hardening_enabled` (default true).
- Tags: `security_hardening`.

### `discovery`

Read-only fact gathering. Reports Apache version, MPM, modules and vhosts, PHP
version, extensions and FPM pools, Redis status and memory, ModSecurity engine,
CRS version, paranoia level and exclusions, AppArmor state, fail2ban jails and
bans, and WordPress version, plugins, theme, DB host and uploads size, then
prints one summary. All commands tolerate failure.

- Variables: none. Enable with `-e run_discovery=true`.
- Used by: `site.yml` (play tagged `discovery`).
- Tags: `discovery`.

### `migration_prep`

Pre-cutover checks: MySQL connectivity to the managed database, Redis `PONG`,
Apache config syntax, PHP-FPM service and socket, a local WordPress HTTP check,
minimum free disk, and absence of a stale `index.html` in the docroot. It can
optionally rsync uploads.

- Variables: `managed_db_host` (`127.0.0.1`), `managed_db_port` (3306),
  `managed_wp_db_user` (`wordpress`), `managed_wp_db_password` (empty, vault),
  `wordpress_domain` (`pausatf.org`), `migration_rsync_uploads` (`false`) with
  `_src` and `_dest`, `migration_min_free_disk_gb` (10), `migration_wp_docroot`.
- Used by: `site.yml` (play tagged `migration_prep`); enable with
  `-e run_migration_prep=true`.
- Tags: `migration_prep`.

## Playbooks

Run from `ansible/`. `site.yml` is the main entry point; the files in
`playbooks/` are narrower slices or one-off operational procedures.

| File | Target hosts | Purpose |
| --- | --- | --- |
| `site.yml` | `all`, `webservers` | Full configuration of every server: see the role table above. |
| `playbooks/base.yml` | `all` | `common` and `tailscale`. |
| `playbooks/webserver.yml` | `webservers` | PHP (`php`, `php-fpm`, `lsphp`), `modsecurity`, `apache`, `openlitespeed`, `certbot`, with the same `web_server`, `use_php_fpm`, `modsecurity_enabled` and `certbot_enabled` conditions as `site.yml`. |
| `playbooks/database.yml` | `webservers` | `mysql` and `redis`. |
| `playbooks/wordpress.yml` | `webservers` | `fail2ban`, `wordpress`, `cloudflare`. |
| `playbooks/monitoring.yml` | `all` | `monitoring` and `newrelic`. |
| `playbooks/rebuild-ubuntu2404.yml` | `webservers` | Full rebuild on Ubuntu 24.04 or later: `common`, `fail2ban`, `tailscale`, `redis`, `certbot`, `php-fpm`, `apache`, `cloudflare`, `wordpress`, `newrelic`, `monitoring`, with a database-less design (Managed MySQL). Asserts the OS version and loads `group_vars/all.yml` and `group_vars/vault.yml`. |
| `playbooks/migrate-prod.yml` | `production` | Backs up production for a move to a new droplet: crontabs, custom scripts, Apache config, database dump, WordPress and legacy trees, Let's Encrypt data, package lists, and a manifest, then downloads the archive to the control machine. |
| `playbooks/restore-prod.yml` | `production` | Restores a `migrate-prod.yml` backup onto a target. Prompts for the backup directory and a `yes` confirmation. |
| `playbooks/sync-content.yml` | `production_new` | rsync of uploads, legacy content, the dev WordPress copy and `.htaccess` from the old production host (`source_host`) to the new one. |
| `playbooks/setup-cloudflared.yml` | `production_new` | Configures the `cloudflared` tunnel; requires tunnel credentials. |
| `playbooks/verify-migration.yml` | `production_new` | Post-migration service and site checks. |
| `playbooks/migrate-to-managed-db.yml` | `production` | Moves the WordPress database to DigitalOcean Managed MySQL: takes a droplet snapshot and a full dump (uploaded to Spaces), enables maintenance mode, exports and imports, compares row counts, and checks the siteurl. Requires `-e confirm_production=yes`; `force_reimport=yes` also requires `confirm_data_loss=yes`. |
| `playbooks/security-remediation.yml` | `production` | Response to the January 2026 breach. `--tags audit` runs read-only checks; mutating tasks are tagged `never, remediate` and run only with `--tags remediate`. |
| `playbooks/rotate-credentials.yml` | `webservers` | Regenerates WordPress salts (`rotate_salts`, default true) and prints a checklist; API tokens are rotated by hand. `rotate_db_password` only changes the checklist line, no task rotates the database password. |
| `playbooks/reset-wp-admin-passwords.yml` | `webservers` | Resets WordPress administrator passwords; `-e wp_target_user=<login>` limits it to one user. |
| `playbooks/capture-wordpress-config.yml` | `webservers` | Captures WordPress, PHP, web server, database (sanitized), cron, cert and `.htaccess` configuration into a local directory. Read-only on the servers. |
| `playbooks/capture-ols-config.yml` | `staging` | Captures the OpenLiteSpeed config, vhosts, lsphp packages, service status and certbot timers. |
| `playbooks/capture-wp-inventory.yml` | `production` | Records plugins, themes, users, last logins, menus and site settings with WP-CLI, and writes them to `group_vars/production/wordpress.yml`. |

Example invocations (inventory is set in `ansible.cfg`):

```bash
cd ansible
ansible-playbook site.yml --check --diff
ansible-playbook site.yml --limit staging --tags apparmor,backups
ansible-playbook site.yml --tags discovery -e run_discovery=true
ansible-playbook playbooks/security-remediation.yml -l production --tags audit
```

Several playbooks assume `ansible/.vault_pass.sh` (set as
`vault_password_file` in `ansible.cfg`) and `group_vars/vault.yml`. Neither file
is in the repository; create them locally.

## Inventory and group_vars

`ansible.cfg` sets `inventory = inventory/hosts.yml,inventory/digitalocean.yml`,
`roles_path = roles`, strict host key checking, pipelining, and fact caching
under `/tmp/ansible_facts`.

**`inventory/hosts.yml`** (static, `hosts.yml.example` is the template) defines
four environments and one aggregate group:

| Group | Host | `web_server` | Notes |
| --- | --- | --- | --- |
| `production` | `pausatf-prod` | `apache` | Address from `PROD_SERVER_IP`; also has `legacy_path`. |
| `production_new` | `pausatf-prod-new` | `apache` | Address from `PROD_NEW_SERVER_IP`; migration target. |
| `staging` | `pausatf-stage` | `openlitespeed` | Address from `STAGE_SERVER_IP`. |
| `dev` | `pausatf-dev` | `apache` | Address from `DEV_SERVER_IP`. |
| `webservers` | children of all four | | Target of the web-stack plays. |

Each address defaults to `127.0.0.1` when its environment variable is unset, so
set the variable before running against a real host.

**`inventory/digitalocean.yml`** is a dynamic inventory using the
`community.digitalocean.digitalocean` plugin and `DO_API_TOKEN`. It builds
groups from droplet tags (`production`, `staging`, `dev`, `web` as
`webservers`, `pausatf`) plus `tag_*`, `region_*`, `size_*` and `status_*` keyed
groups. It connects as `root` over the public IPv4 address. Install the
collection with `ansible-galaxy collection install -r requirements.yml` (the
requirements file lists `community.general`, `ansible.mysql` and
`ansible.posix`; `community.digitalocean` is not listed there).

**`group_vars/`** (also reachable as `inventory/group_vars`, which is a symlink):

- `all.yml` (`all.yml.example` is the template): shared defaults for every
  environment, including Cloudflare, Redis, Apache, PHP 8.3 with FPM, the
  Managed MySQL connection (`managed_db_*`, read from `vault_*`), the
  `wordpress_install_defaults` (`active_theme: TheSource-child`), WP-CLI
  update policy, SSL and CSP settings, UFW ports, New Relic, monit, health
  check and backup settings.
- `staging.yml`: `web_server: openlitespeed`, PHP 8.4, `is_active_web: false`,
  Cloudflare Origin CA certificate, debug logging, and its `wordpress_installs`.
- `dev.yml`: Apache with event MPM and FPM, PHP 8.4, `mysql_managed: true`,
  `is_active_web: false`, Origin CA certificate, and its `wordpress_installs`.
- `production/main.yml`: droplet facts, Cloudflare DNS records, Apache and
  ModSecurity settings, Managed MySQL, Redis, AppArmor enforcement, Let's
  Encrypt through certbot, `is_active_web: true`, `block_direct_ip`, and the
  production `wordpress_installs`, themes and mu-plugins.
  `production/wordpress.yml` holds the captured plugin, theme and user inventory
  that `playbooks/capture-wp-inventory.yml` regenerates
  (`wordpress.yml.example` is the template).
- `production_new/main.yml` and `production_new/wordpress.yml`: the same shape
  for the new production host.

**`scripts/tf-to-ansible.sh <environment>`** reads `terraform output -json` from
`terraform/environments/<environment>` and prints a JSON object of Ansible
variables (droplet ID and IP, `managed_db_*`, `vpc_id`, `firewall_id`,
`reserved_ip`), omitting empty values. It prints database credentials taken from
Terraform output, so pass it straight to Ansible and do not save the output:

```bash
ansible-playbook site.yml -e @<(ansible/scripts/tf-to-ansible.sh production)
```

## Overlaps and gaps in the code

- `backups` and `monitoring` both deploy a script to
  `/usr/local/sbin/backup-wordpress.sh` and use overlapping variable names
  (`backup_dir`, `backup_retention_days`, `backup_cron_hour`) with different
  defaults. Apply at most one of them per host.
- `playbooks/rebuild-ubuntu2404.yml` documents the Managed MySQL setup but its
  usage comment says `ansible-playbook -i inventory site.yml`, which runs
  `site.yml`, not the rebuild playbook.
- The `argument_specs.yml` for `common` documents `allowed_ssh_networks`; the
  role also reads `ssh_allow_users`, `ufw_reset` and
  `ufw_restrict_web_to_cloudflare`, which are not in the spec.
