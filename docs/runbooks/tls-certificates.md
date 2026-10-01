# TLS certificates and renewal

The Cloudflare zone and each server's origin certificate have different roles.
Cloudflare presents the public edge certificate. Cloudflare connects to the
origin using Full (strict) validation.

## Tracked policy

- `terraform/environments/cloudflare/main.tf` owns Full (strict), Always Use
  HTTPS, minimum TLS 1.2, and TLS 1.3 at the Cloudflare edge.
- `ansible/group_vars/production/main.yml` records the production Let's Encrypt
  lineage (`pausatf.org`), domains, on-host certificate mount, and renewal
  method. Certbot runs on the production host using the Cloudflare DNS-01
  plugin.
- `ansible/group_vars/staging.yml` and `ansible/group_vars/dev.yml` record
  observed Cloudflare Origin CA inventory. These `tls_origin_*` variables are
  descriptive metadata; web-server roles do not consume them. The tracked
  staging playbook selects host OpenLiteSpeed and dev selects host Apache.

## Provisioning and renewal

`ansible/site.yml` provisions host Apache in production, not Docker. It obtains
the Certbot lineage before configuring Apache SSL vhosts. The production hook
uses staging-only mode in this first phase, including on active hosts with old
vhost paths. After the web-server role rewrites and validates those paths, the
playbook reapplies Certbot in normal validation mode. This prevents an old
invalid lineage from blocking its own migration; pending files still require
validation and reload in the second phase.

In normal validation mode, the hook
copies certificates to `/etc/ssl/pausatf`, validates host Apache configuration,
and reloads the host service. Initial issuance stages the files with a pending
marker if Apache is not running yet. The independent synchronization timer
validates and reloads after provisioning; failures restore prior files, and
unchanged certificates without a pending marker do not reload Apache.

The observed live Docker deployment is separately managed and is not created by
this playbook. For that origin, explicitly set `certbot_deploy_container_name:
pausatf-wordpress` when applying the Certbot role alone, and ensure the container
mounts `/etc/ssl/pausatf` read-only. Do not apply the host web-stack playbook to
that deployment as though it provisions Docker. The container hook validates
and gracefully reloads Apache inside the named container and retries pending
deployments every 15 minutes. Inspect `journalctl -u pausatf-certbot-deploy.service`.

## Live inventory checked 2026-09-27

| Environment | Origin certificate | Renewal owner | Observed origin expiry |
| --- | --- | --- | --- |
| Production (`pausatf.org`, `www`) | Let's Encrypt, lineage `pausatf.org` | Certbot DNS-01 deploy hook | 2026-12-16 |
| Staging (`stage.pausatf.org`) | Cloudflare Origin CA | Manual rotation; schedule before expiry | 2041-07-18 |
| Dev (`dev.pausatf.org`) | Cloudflare Origin CA | Manual rotation; schedule before expiry | 2041-07-18 |

On 2026-09-27, public SNI checks for the apex, `www`, `stage`, and `dev` all
presented the same Google Trust Services edge certificate for
`pausatf.org`, valid through 2026-11-15. This confirms the public edge path;
the origin certificate details above were checked on their respective hosts.

When rotating a dev or staging Origin CA certificate, deploy the certificate
and key to `/etc/ssl/pausatf/fullchain.pem` and
`/etc/ssl/pausatf/privkey.pem`, verify hostname coverage and validity, then run
`apache2ctl configtest` and gracefully reload Apache in the matching WordPress
container for the observed Docker deployment. For host Apache or OpenLiteSpeed
provisioned by Ansible, validate and reload the actual host web service and use
the certificate paths configured by its role. Keep both files root-owned and the private key mode `0600`.

## Validation

For each hostname, check the public edge with SNI and inspect the certificate
issuer, SANs, and validity dates. To inspect the origin directly, connect to
the environment's origin IP with SNI while bypassing Cloudflare. Never treat
the public edge certificate as proof that the origin certificate is current.
