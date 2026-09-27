# Cloudflare Environment — `pausatf.org`

This Terraform environment manages the Cloudflare zone and DNS configuration
for `pausatf.org`.

## Reconciliation safety

The Cloudflare Terraform backend is currently empty, and the DNS declarations
in this branch do not match the live Tunnel-backed DNS records. A read-only
plan reports 39 creates. Do not run `terraform apply`, the legacy import
script, or the old manual import commands below while issue
[#137](https://github.com/pausatf/pausatf/issues/137) remains unresolved.

Before any apply, reconcile the Terraform declarations with the live DNS
inventory, verify zone settings and rulesets, import existing resources using
the current Cloudflare provider's resource addresses, and require a reviewed
no-change plan. The Cloudflare API token used for the DNS inventory could not
read zone settings or rulesets.

The `import-dns-records.sh` script is disabled because it uses the obsolete
`cloudflare_record` provider resource type and an incomplete, stale record
map. Do not run it. The resource address for the SendGrid delivery record was
renamed to satisfy Terraform naming rules; the `moved` block in `main.tf`
preserves state created under its previous Terraform address.

## Initialize and validate

Provide Cloudflare and DigitalOcean Spaces credentials through environment
variables. Do not commit credentials or Terraform state files.

```bash
cd terraform/environments/cloudflare
terraform init
terraform validate
terraform plan
```

`terraform plan` is read-only, but its proposed changes must be reviewed. Do
not apply a plan that proposes to create existing records or replace live
Tunnel routing.

## Zone

- Zone: `pausatf.org`
- Zone ID: `67b87131144a68ad5ed43ebfd4e6d811`
- Account ID: `c540729070ba913814ac4557c8974099`

The resource declarations are being reconciled in issue #137. Do not use them
as an authoritative live DNS inventory until that work is complete.
