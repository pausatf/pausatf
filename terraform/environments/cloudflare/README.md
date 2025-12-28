# Cloudflare Environment - pausatf.org DNS Management

This Terraform environment manages the Cloudflare zone and all DNS records for `pausatf.org`.

## Resources Managed

### Zone
- **pausatf.org** (ID: 67b87131144a68ad5ed43ebfd4e6d811)
- Plan: Free
- Account: c540729070ba913814ac4557c8974099

### DNS Records (29 total)

#### A Records (7)
- `pausatf.org` → 64.225.40.54 (proxied)
- `www.pausatf.org` → 64.225.40.54 (proxied)
- `ftp.pausatf.org` → 64.225.40.54 (direct)
- `mail.pausatf.org` → 64.225.40.54 (direct)
- `monitor.pausatf.org` → 64.225.40.54 (direct)
- `stage.pausatf.org` → 64.227.85.73 (direct)
- `staging.pausatf.org` → 64.227.85.73 (direct)

#### CNAME Records (8)
- `prod.pausatf.org` → ftp.pausatf.org
- SendGrid email delivery (4 records)
- SendGrid DKIM signatures (2 records)

#### MX Records (5)
- Google Workspace mail servers (priority 1, 5, 5, 10, 10)

#### TXT Records (4)
- SPF record (Google Workspace + SendGrid)
- Google Search Console verification
- DMARC policy
- Cloudflare DKIM signature
- ACME challenge (temporary)

#### CAA Records (5)
- Let's Encrypt (issue + issuewild)
- DigiCert (issue + issuewild)
- iodef notification email

## Initial Setup

### 1. Set Environment Variables

```bash
export TF_VAR_cloudflare_api_token="your-cloudflare-api-token"
export AWS_ACCESS_KEY_ID="your-do-spaces-key"
export AWS_SECRET_ACCESS_KEY="your-do-spaces-secret"
```

### 2. Initialize Terraform

```bash
cd terraform/environments/cloudflare
terraform init
```

### 3. Import Existing Zone

The zone already exists and needs to be imported:

```bash
terraform import cloudflare_zone.pausatf 67b87131144a68ad5ed43ebfd4e6d811
```

### 4. Import Existing DNS Records

All DNS records already exist and need to be imported. Use the import script:

```bash
bash import-dns-records.sh
```

Or manually import each record (see "Import Commands" section below).

### 5. Verify Plan

```bash
terraform plan
```

This should show no changes if all resources are imported correctly.

## Import Commands

<details>
<summary>Click to expand full import commands</summary>

```bash
# Import zone (if not already imported)
terraform import cloudflare_zone.pausatf 67b87131144a68ad5ed43ebfd4e6d811

# Import DNS records
# Note: Use 'curl' to get record IDs first:
# curl -s "https://api.cloudflare.com/client/v4/zones/67b87131144a68ad5ed43ebfd4e6d811/dns_records" \
#   -H "Authorization: Bearer $TF_VAR_cloudflare_api_token" | jq -r '.result[] | "\(.type) \(.name) = \(.id)"'

# Example imports (replace RECORD_ID with actual IDs):
terraform import cloudflare_record.root RECORD_ID
terraform import cloudflare_record.www RECORD_ID
terraform import cloudflare_record.ftp RECORD_ID
# ... (repeat for all 29 records)
```

</details>

## Making Changes

### Update Production IP

If the production droplet IP changes:

```bash
terraform apply -var="production_ip=NEW_IP_ADDRESS"
```

### Update Staging IP

If the staging droplet IP changes:

```bash
terraform apply -var="staging_ip=NEW_IP_ADDRESS"
```

### Add New DNS Record

1. Add the resource to `main.tf`
2. Run `terraform plan` to preview
3. Run `terraform apply` to create

### Remove DNS Record

1. Comment out or remove the resource from `main.tf`
2. Run `terraform plan` to preview
3. Run `terraform apply` to delete

## Integration with Other Environments

The production and staging environments reference these DNS records:

**Production (`terraform/environments/production/`):**
- Uses `ftp.pausatf.org` (64.225.40.54)
- Main site: `pausatf.org` and `www.pausatf.org` (proxied through Cloudflare)

**Staging (`terraform/environments/staging/`):**
- Uses `stage.pausatf.org` (64.227.85.73)
- Also available as `staging.pausatf.org`

## Security Notes

- **Proxied records:** Root and www are proxied through Cloudflare for DDoS protection and caching
- **Direct records:** All other records bypass Cloudflare proxy for direct access
- **CAA records:** Restrict SSL certificate issuance to Let's Encrypt and DigiCert only
- **SPF/DMARC:** Email authentication configured for Google Workspace and SendGrid

## Email Configuration

### Google Workspace
- MX records point to Google's mail servers
- SPF includes `_spf.google.com`
- Primary MX: aspmx.l.google.com (priority 1)

### SendGrid
- DKIM signatures: s1 and s2
- Link tracking: url7068, url7741
- Email tracking: em5172
- SPF includes `sendgrid.net`

## Troubleshooting

### DNS Not Propagating

```bash
# Check Cloudflare DNS
dig @curt.ns.cloudflare.com pausatf.org
dig @eva.ns.cloudflare.com pausatf.org

# Check public DNS
dig pausatf.org
nslookup pausatf.org
```

### Import Conflicts

If Terraform detects drift after import:

```bash
# Show differences
terraform plan

# Pull current state
terraform refresh

# Force overwrite (use with caution)
terraform apply -replace=cloudflare_record.RESOURCE_NAME
```

### Verify All Records

```bash
# List all managed records
terraform state list | grep cloudflare_record

# Show specific record
terraform state show cloudflare_record.root
```

## Maintenance

### Regular Tasks

1. **Review DNS records quarterly** - Remove unused records
2. **Update IP addresses** - When droplets are recreated
3. **Rotate DKIM keys annually** - For email security
4. **Monitor CAA records** - Ensure only authorized CAs can issue certificates

### Backup

DNS records are backed up via:
- Terraform state in DigitalOcean Spaces
- Cloudflare's built-in versioning
- Git repository (this code)

## Related Documentation

- [Cloudflare API Documentation](https://developers.cloudflare.com/api/)
- [Terraform Cloudflare Provider](https://registry.terraform.io/providers/cloudflare/cloudflare/latest/docs)
- [Main Infrastructure Inventory](../../INFRASTRUCTURE_INVENTORY.md)
