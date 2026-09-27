# Cloudflare Environment — `pausatf.org`

This environment describes the Cloudflare zone, DNS records, zone settings, and
rulesets for `pausatf.org`.

## Reconciliation safety

The Cloudflare Terraform backend currently has no state file. The live DNS
inventory was read from the Cloudflare API on 2026-09-26 and the declarations
here were updated to match those records. The Cloudflare API token used for the
inventory could not read zone settings or rulesets, so those declarations have
not been checked against the live account.

Do not run `terraform apply` yet. First import the existing zone and DNS records
into the correct remote state, verify zone settings and rulesets with an
appropriately scoped Cloudflare API token, and require a plan that proposes no
unreviewed DNS, setting, or ruleset changes. The legacy
`import-dns-records.sh` script is obsolete and must not be run: it targets the
old provider resource type and an incomplete, stale record map.

The live inventory contains 39 DNS records: 7 A, 14 CNAME, 5 MX, 6 TXT, and 7
CAA. This Cloudflare environment owns 37 records; `dev` is owned by the dev
environment and `stage` by the staging environment. Keep those ownership
boundaries intact so different remote states cannot overwrite the same DNS
record. The staging state was empty when checked on 2026-09-27, so import its
existing `stage` record before considering an apply.

The `_acme-challenge.www` TXT value is time-sensitive; confirm it is still
needed before importing or refreshing that record.

## Live DNS inventory

| Type | Names |
| --- | --- |
| A | `dev`, `direct-ssh`, `ftp`, `mail`, `monitor`, `runners`, `staging` |
| CNAME | `@`, `www`, `stage`, `prod`, `ssh`, `ssh-stage-v2`, `ssh-v2`, `v2canary`, `51871933`, `em5172`, `url7068`, `url7741`, `s1._domainkey`, `s2._domainkey` |
| MX | `@` (five Google Workspace records) |
| TXT | `@` (SPF and Google verification), `_dmarc`, `cf2024-1._domainkey`, `mail._domainkey`, `_acme-challenge.www` |
| CAA | `@` (seven records for Google Trust Services, DigiCert, Let's Encrypt, and iodef) |

The apex, `www`, `stage`, SSH tunnel aliases, and canary use Cloudflare Tunnel
hostnames. `dev` and `stage` are configured in their dedicated environment
states, not in this Cloudflare environment. `staging` and the direct A records
retain their observed addresses; these values are an inventory snapshot, not a
claim that those hosts are healthy or should remain pointed at those addresses.

## Backend and credentials

Terraform state is stored in DigitalOcean Spaces. Provide credentials through
the standard AWS-compatible environment variables and the Cloudflare API token
through `TF_VAR_cloudflare_api_token`; do not commit credentials or state files.

Initialize and validate with the repository's pinned Terraform version:

```bash
cd terraform/environments/cloudflare
terraform init -backend-config=../../backend.hcl
terraform validate
terraform plan
```

The plan is for review only until the state and live configuration have been
reconciled. Do not apply a plan that proposes to create existing DNS records or
replace live tunnel records.

## Related infrastructure

- Production, staging, and development droplet definitions are in their
  respective Terraform environments.
- Mail is hosted through Google Workspace and SendGrid records.
- `import-dns-records.sh` remains in the repository as historical code only; it
  does not safely import the current provider configuration.
