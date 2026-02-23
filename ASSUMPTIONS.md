# Assumptions — Ubuntu 24.04 Rebuild

This document lists assumptions made in the `feat/ubuntu24-rebuild` changeset.
Items marked **ACTION REQUIRED** need manual steps before or during cutover.

## Secrets and Credentials

1. **ACTION REQUIRED**: `vault_tailscale_authkey` must be added to
   `ansible/group_vars/vault.yml` before running the Tailscale role. Generate
   a reusable or single-use authkey from
   https://login.tailscale.com/admin/settings/keys.

2. **ACTION REQUIRED**: `vault_cloudflare_api_token` must be populated in the
   Ansible vault. This is the same token used by Terraform for the Cloudflare
   provider and by the certbot DNS-01 plugin. The token needs `Zone:DNS:Edit`
   and `Zone:Zone:Read` permissions for `pausatf.org`.

3. The Ansible vault password is retrievable from macOS Keychain:
   `security find-generic-password -s "pausatf-ansible-vault" -a "ansible" -w`

4. Terraform backend credentials (`AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`
   mapped to DO Spaces keys) are assumed to be configured in the operator's
   environment.

## Infrastructure

5. The DigitalOcean Managed MySQL 8 cluster already exists and is managed by
   the `digitalocean/database` module. No local `mysql-server` is installed on
   the droplet — only `mysql-client-8.0`.

6. The DO Managed MySQL cluster listens on port 25060 (DO default) over the
   VPC private network. WordPress `wp-config.php` must use the managed DB
   host/port from Terraform outputs, not `localhost:3306`.

7. The existing reserved IP (`digitalocean_reserved_ip.production`) survives
   the rebuild. DNS records point to this IP, so cutover is atomic with zero
   propagation delay.

8. The `moved {}` blocks in `terraform/environments/production/main.tf` assume
   the current state contains resources at their original addresses
   (`digitalocean_vpc.production`, `digitalocean_droplet.production`, etc.).
   Run `terraform plan` first to verify zero-recreation.

## Networking and SSH

9. Tailscale must be authenticated manually (or via a one-time authkey) after
   the droplet is created. The Ansible role installs and enables `tailscaled`
   but does not call `tailscale up` automatically — the authkey is single-use.

10. The DO firewall has **no port 22 inbound rule**. SSH is exclusively via the
    Tailscale overlay network (`tailscale0` interface, 100.x.x.x/8 range).

11. UFW on the droplet allows inbound on `tailscale0` interface and denies
    port 22 from `0.0.0.0/0`. The deny rule is only applied after the
    `tailscale0` interface is confirmed present, to avoid lockout during
    initial bootstrap.

12. Tailscale ACLs must permit `tag:server` to accept SSH. This is configured
    in the Tailscale admin console, not in this repo.

## SSL / TLS

13. Certbot obtains certificates for `pausatf.org` and `www.pausatf.org` via
    the Cloudflare DNS-01 challenge. The credentials file at
    `/etc/letsencrypt/cloudflare.ini` is mode `0600 root:root`.

14. The certbot systemd timer handles automatic renewal. A post-renewal deploy
    hook at `/etc/letsencrypt/renewal-hooks/deploy/reload-apache.sh` reloads
    Apache after certificate renewal.

15. Cloudflare SSL mode is set to `Full (Strict)`, requiring a valid certificate
    on the origin server. This means certbot must succeed before Cloudflare will
    proxy traffic without errors.

16. The old SSL certificates (`/etc/ssl/private/6025999182103-cert.crt` and key)
    are not used on the new droplet. The vhost template conditionally uses
    Let's Encrypt paths when `apache_mpm == 'event'`.

## Web Server

17. Apache uses the `event` MPM with `proxy_fcgi` to connect to PHP 8.3-FPM
    via the unix socket at `/run/php/php8.3-fpm.sock`. The old `mpm_prefork` +
    `mod_php` path is preserved in the templates for backward compatibility but
    is not active on the new droplet.

18. `mod_php` (`libapache2-mod-php*`) is explicitly removed by the `php-fpm`
    role.

## Redis

19. Redis runs on the droplet at `127.0.0.1:6379` and via a unix socket at
    `/var/run/redis/redis.sock`. It is configured with `maxmemory 256mb`,
    `maxmemory-policy allkeys-lru`, and persistence disabled (`save ""`).

20. A WordPress Redis object cache plugin (e.g., `redis-cache` or the Jetpack
    Redis module) must be installed and configured separately. This changeset
    provisions Redis but does not install a WP plugin for it.

## Cloudflare

21. The `data.cloudflare_ip_ranges` data source dynamically fetches current
    Cloudflare edge IP ranges for the DO firewall rules. If Cloudflare adds
    new ranges, a `terraform apply` on the production environment will update
    the firewall.

22. The `terraform_remote_state` data source in the Cloudflare environment
    reads the production state from the same DO Spaces backend. Both
    environments must use the same S3 credentials.

23. Cache bypass rules use `http.cookie contains "wordpress_logged_in_"` which
    matches the cookie prefix for all WordPress installations regardless of
    site hash. Preview bypass uses `http.request.uri.query contains
    "preview=true"`.

24. The rate limit rule on `/wp-login.php POST` (5 requests per 10 seconds per
    IP, 1-hour block) uses Cloudflare's real client IP (`ip.src`), not
    Tailscale overlay IPs.

## Ansible

25. The `community.general` collection is required for the `ufw` module used
    in the Tailscale role. Ensure it is installed:
    `ansible-galaxy collection install community.general`

26. The old `mysql` Ansible role (which installs `mysql-server`) is no longer
    included in `site.yml`. If a future playbook run targets the old
    production droplet, the `mysql` role must be re-added to `site.yml` or
    run separately.

27. The `php` role (mod_php) is replaced by `php-fpm` in `site.yml`. The old
    role files are preserved in the repo for backward compatibility.

## Deprecations

28. Removed plugins from `group_vars/all.yml`: `app-for-cf` (redundant with
    Cloudflare Terraform management), `google-sitemap-generator` (superseded
    by built-in WordPress sitemaps since 5.5), `version-info` (information
    disclosure risk).

29. The orphaned `terraform/backend.tf` (Terraform Cloud remote backend) has
    been deleted. All environments use the S3 backend in their own `main.tf`.
