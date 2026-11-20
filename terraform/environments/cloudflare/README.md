# Cloudflare Environment — `pausatf.org`

This environment describes the Cloudflare zone, DNS records, zone settings, and
rulesets for `pausatf.org`.

## Reconciliation safety

The live DNS inventory, zone settings, and rulesets were read from Cloudflare
on 2026-09-27. The zone, 37 central DNS records, six settings, and two active
rulesets have since been imported into HCP Terraform workspace
`pausatf-cloudflare`. Dev and staging DNS records are imported into their own
HCP workspaces. No Cloudflare apply has been run during this reconciliation.

The refreshed plan now reports no changes. The first plan exposed TTL,
long-TXT-format, and record-comment differences that were adjusted in HCL to
match the live values. Re-run and review the plan before any later apply.
The legacy
`import-dns-records.sh` script is obsolete and must not be run: it targets the
old provider resource type and an incomplete, stale record map.

This Cloudflare environment owns the records listed below; `dev` is owned by the dev
environment and `stage` by the staging environment. Keep those ownership
boundaries intact so different remote states cannot overwrite the same DNS
record. The `dev` and `stage` records are now imported into their respective
environment workspaces.

### Observed zone settings and rulesets

The declared zone settings match the dashboard: Full (strict), Always Use
HTTPS, minimum TLS 1.2, Brotli, opportunistic encryption, and HSTS
(`max-age=15552000`, include subdomains, preload, and nosniff). The live cache
ruleset bypasses caching for the apex and `/data/`, caches anonymous public
pages for 7200 seconds at the edge and 300 seconds in browsers, and bypasses
cache for WordPress admin and authenticated requests. The live login rate
limit blocks five POST requests per ten seconds per source IP and Cloudflare
colo, with a ten-second mitigation timeout.

The custom WAF ruleset applies a managed challenge to `/wp-login.php` and
`/wp-admin` (except `admin-ajax.php`). The zone has no `transit` redirect
ruleset.

## Live DNS inventory

| Type | Names |
| --- | --- |
| CNAME | `@`, `www`, `stage`, `runners`, `ssh-stage-v2`, `ssh-v2`, `v2canary`, `51871933`, `em5172`, `url7068`, `url7741`, `s1._domainkey`, `s2._domainkey` |
| MX | `@` (five Google Workspace records) |
| TXT | `@` (SPF `-all` and Google verification), `_dmarc` (`p=reject`), `cf2024-1._domainkey`, `mail._domainkey` |
| CAA | `@` (seven records for Google Trust Services, DigiCert, Let's Encrypt, and iodef) |

The apex, `www`, `stage`, `runners`, SSH tunnel aliases, and canary use
Cloudflare Tunnel hostnames; no record points directly at a droplet IP. `dev`
and `stage` are configured in their dedicated environment states, not in this
Cloudflare environment. The former direct A records (`ftp`, `mail`, `monitor`,
`direct-ssh`, `staging`) and the `prod` and `ssh` aliases were removed on
2026-11-19 because their addresses no longer belonged to the account.

## Backend and credentials

State belongs to the HCP Terraform workspace `pausatf-cloudflare` in the
`pausatf` organization. The live resources are imported and the current plan is
clean. Use `terraform login`, then initialize
with the repository's pinned Terraform version:

```bash
cd terraform/environments/cloudflare
terraform init
terraform validate
terraform plan
```

Provide a narrowly scoped Cloudflare token as a sensitive environment value on
the trusted runner. The existing zone, DNS records, settings, and rulesets are
already imported. Do not apply a plan that proposes to recreate existing DNS
records or replace live tunnel records.

## Related infrastructure

- Production, staging, and development droplet definitions are in their
  respective Terraform environments.
- Mail is hosted through Google Workspace and SendGrid records.
- `import-dns-records.sh` remains in the repository as historical code only; it
  does not safely import the current provider configuration.
