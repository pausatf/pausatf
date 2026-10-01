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
- `ansible/group_vars/staging.yml` and `ansible/group_vars/dev.yml` record the
  Cloudflare Origin CA certificate issuer, hostname, mounted paths, and TLS
  1.2 minimum for the Apache origin containers. Their origin certificates are
  provisioned outside Ansible; private keys must not be committed.

The production Certbot deploy hook installs renewed files into
`/etc/ssl/pausatf`, which is mounted read-only in `pausatf-wordpress`. It runs
Apache's config test before a graceful reload and restores the prior files if
the test or reload fails. The role installs the hook before issuance and also
synchronizes existing lineages on deployment, including an empty target directory.
The `pausatf-certbot-deploy.timer` retries synchronization every 15 minutes
independently of renewal eligibility, so a failed deployment can recover after
Certbot has already renewed its lineage. Unchanged certificates do not reload
Apache. Inspect `journalctl -u pausatf-certbot-deploy.service` and the mounted
certificate expiry when diagnosing failed deployment. This keeps renewal
independent of a host Apache service.

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
container. Keep both files root-owned and the private key mode `0600`.

## Validation

For each hostname, check the public edge with SNI and inspect the certificate
issuer, SANs, and validity dates. To inspect the origin directly, connect to
the environment's origin IP with SNI while bypassing Cloudflare. Never treat
the public edge certificate as proof that the origin certificate is current.
