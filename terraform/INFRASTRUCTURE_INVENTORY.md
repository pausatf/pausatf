# PAUSATF Infrastructure Inventory

**Generated:** 2025-12-27
**Purpose:** Document actual cloud resources vs. Terraform-managed resources

## Summary

This document compares the actual infrastructure running in DigitalOcean, Cloudflare, and GitHub against what is currently managed by Terraform.

## DigitalOcean Resources

### Droplets

| Resource | Name | ID | IP | Image | Size | Region | Status | In Terraform? |
|----------|------|----|----|-------|------|--------|--------|---------------|
| Production | pausatf-prod | 355909945 | 64.225.40.54 | Ubuntu pausatf.org 2023-05-16 (custom snapshot) | s-4vcpu-8gb | sfo2 | ✅ Active | ✅ Yes (but wrong image) |
| Staging | pausatf-stage | 538411208 | 64.227.85.73 | Ubuntu OpenLiteSpeed WordPress 6.8.2 on Ubuntu 24.04 (marketplace) | s-2vcpu-4gb | sfo2 | ✅ Active | ✅ Yes (but wrong image) |

**Issues:**
- Production uses custom snapshot, Terraform expects Ubuntu 22.04 base image with cloud-init
- Staging uses marketplace image, Terraform expects Ubuntu 22.04 base image with cloud-init
- Both reference old `cloud-init.yml` instead of `cloud-init-apache.yml` and `cloud-init-openlitespeed.yml`

### Databases

| Resource | Name | ID | Engine | Version | Size | Region | Nodes | Status | In Terraform? |
|----------|------|----|--------|---------|------|--------|-------|--------|---------------|
| Staging DB | pausatf-stage-db | 661fa8d4-077c-43d7-a47a-79bfc42737c8 | MySQL | 8 | db-s-1vcpu-1gb | sfo2 | 1 | ✅ Online | ✅ Yes |
| Production DB | N/A | N/A | N/A | N/A | N/A | N/A | N/A | ❌ Does not exist | ⚠️ Defined in Terraform but not created |

**Issues:**
- Terraform defines a production database that doesn't exist in reality
- Production WordPress likely uses the staging database or an external database

### Firewalls

| Resource | Name | ID | Droplets | Rules | In Terraform? |
|----------|------|----|----------|-------|---------------|
| Production | pausatf-production | a4e42798-ab22-467f-a821-daa290f56655 | pausatf-prod | ICMP, SSH, HTTP, HTTPS | ⚠️ Defined but not imported |
| Staging | pausatf-staging | c12dfc7f-f43a-4b32-96c6-80ba34035b1a | pausatf-stage | ICMP, SSH, HTTP, HTTPS | ⚠️ Defined but not imported |

**Issues:**
- Firewalls exist and are defined in Terraform, but Terraform state doesn't manage them (manual creation)
- Need to import into Terraform state

### VPCs

| Resource | Name | ID | Region | IP Range | In Terraform? |
|----------|------|----|--------|----------|---------------|
| Default NYC1 | default-nyc1 | 12ddb0a1-322f-450b-8de5-3e33e8fcf456 | nyc1 | 10.116.0.0/20 | ❌ No |
| Default SFO1 | default-sfo1 | 7c8a0e99-dd1d-4591-b956-2786d6cb5219 | sfo1 | 10.112.0.0/20 | ❌ No |
| Default SFO2 | default-sfo2 | 4ee39499-dc85-11e8-9f23-3cfdfea9fff1 | sfo2 | 10.138.0.0/16 | ❌ No |
| Default SFO3 | default-sfo3 | 60a80fc6-4809-4fa4-b8fd-8863cfc1d70d | sfo3 | 10.124.0.0/20 | ❌ No |
| Unused | unused | 338f2873-f4d1-439e-8479-0d3c550642cc | sfo2 | 10.0.0.0/24 | ❌ No |
| Production VPC | N/A | N/A | N/A | 10.10.0.0/16 | ⚠️ Defined in Terraform but not created |
| Staging VPC | N/A | N/A | N/A | 10.20.0.0/16 | ⚠️ Defined in Terraform but not created |

**Issues:**
- Terraform defines custom VPCs that don't exist in reality
- Droplets are using default VPC
- Should remove VPC definitions from Terraform or create them

### SSH Keys

| Resource | Name | ID | Fingerprint | In Terraform? |
|----------|------|----|-------------|---------------|
| M3 Laptop | m3 laptop | 46721354 | f4:5c:52:bb:23:a2:ef:d0:09:e4:59:d4:b5:c7:a7:8a | ❌ No |

**Issues:**
- SSH key used by droplets is not managed by Terraform
- Should add to Terraform as data source or resource

### Projects

| Resource | Name | ID | Purpose | Environment | Default | In Terraform? |
|----------|------|----|---------|-------------|---------|---------------|
| PAUSATF | PAUSATF | 8ddce7ba-f064-4611-8460-0771dd817342 | Website or blog | Production | ✅ Yes | ❌ No |

**Issues:**
- Project exists but is not managed by Terraform
- All PAUSATF resources should be associated with this project

### Reserved IPs

No reserved IPs currently in use.

### Spaces (Object Storage)

| Resource | Name | Usage | In Terraform? |
|----------|------|-------|---------------|
| Terraform State | pausatf-terraform-state | Backend storage | ⚠️ Backend only, not managed |

**Issues:**
- Space is used for Terraform backend but not managed as a resource

## Cloudflare Resources

### Zones

| Resource | Name | ID | Account ID | Plan | Status | In Terraform? |
|----------|------|----|------------|------|--------|---------------|
| pausatf.org | pausatf.org | 67b87131144a68ad5ed43ebfd4e6d811 | c540729070ba913814ac4557c8974099 | Free | ✅ Active | ❌ No |

**Issues:**
- Zone exists but is not managed by Terraform
- Should import or create zone resource

### DNS Records

| Type | Name | Content | Proxied | Purpose | In Terraform? |
|------|------|---------|---------|---------|---------------|
| A | pausatf.org | 64.225.40.54 | ✅ Yes | Main site | ❌ No |
| A | www.pausatf.org | 64.225.40.54 | ✅ Yes | WWW redirect | ❌ No |
| A | ftp.pausatf.org | 64.225.40.54 | ❌ No | Production droplet | ❌ No |
| A | mail.pausatf.org | 64.225.40.54 | ❌ No | Mail server | ❌ No |
| A | monitor.pausatf.org | 64.225.40.54 | ❌ No | Monitoring | ❌ No |
| A | stage.pausatf.org | 64.227.85.73 | ❌ No | Staging | ✅ Yes |
| A | staging.pausatf.org | 64.227.85.73 | ❌ No | Staging alias | ❌ No |
| CNAME | prod.pausatf.org | ftp.pausatf.org | ❌ No | Production alias | ❌ No |
| MX | pausatf.org | Google Workspace (5 records) | ❌ No | Email | ❌ No |
| TXT | pausatf.org | SPF, Google verification | ❌ No | Email, verification | ❌ No |
| TXT | _dmarc.pausatf.org | DMARC policy | ❌ No | Email security | ❌ No |
| CNAME | SendGrid records | Multiple SendGrid CNAMEs | ❌ No | Email delivery | ❌ No |
| CAA | pausatf.org | Let's Encrypt, DigiCert | ❌ No | SSL certificate authority | ❌ No |

**Issues:**
- Only `stage.pausatf.org` A record is managed by Terraform
- All other DNS records are manually configured
- Should import all critical DNS records into Terraform

## GitHub Resources

### Repositories

| Resource | Name | Visibility | Description | In Terraform? |
|----------|------|------------|-------------|---------------|
| Monorepo | pausatf | Public | PAUSATF Infrastructure Monorepo | ✅ Yes |

**Issues:**
- Repository exists in Terraform
- Branch protection not enabled (defined but not applied)
- Topics not set (defined but not applied)

### Branch Protection

| Branch | Required Reviews | Signed Commits | Status Checks | In Terraform? | Applied? |
|--------|------------------|----------------|---------------|---------------|----------|
| main | 1 | ✅ Required | terraform-validate, terraform-fmt, ansible-lint, shellcheck | ✅ Defined | ❌ Not applied |

**Issues:**
- Branch protection is defined in Terraform but not applied to repository
- Need to run `terraform apply` to enable

### Repository Settings

| Setting | Configured Value | Actual Value | In Sync? |
|---------|------------------|--------------|----------|
| Has Issues | ✅ Enabled | ✅ Enabled | ✅ Yes |
| Has Wiki | ✅ Enabled | ✅ Enabled | ✅ Yes |
| Has Projects | ✅ Enabled | ✅ Enabled | ✅ Yes |
| Has Discussions | ❌ Disabled | ❌ Disabled | ✅ Yes |

### Topics

**Defined in Terraform:**
- infrastructure-as-code
- terraform
- ansible
- wordpress
- digitalocean
- cloudflare
- monorepo
- devops
- automation
- configuration-management
- scripts
- documentation
- runbooks

**Actual Topics:** None

**Issues:**
- Topics defined in Terraform but not applied to repository

## Action Items

### High Priority

1. **Update cloud-init template references**
   - Production: Change from `cloud-init.yml` to `cloud-init-apache.yml`
   - Staging: Change from `cloud-init.yml` to `cloud-init-openlitespeed.yml`

2. **Remove non-existent resources from Terraform**
   - Remove production database cluster definition
   - Remove custom VPC definitions (use default VPCs)

3. **Apply GitHub configuration**
   - Run `terraform apply` in `terraform/environments/github/` to enable branch protection and topics

4. **Create Cloudflare zone management**
   - Import pausatf.org zone into Terraform
   - Add all critical DNS records to Terraform

### Medium Priority

5. **Import existing firewalls into Terraform state**
   ```bash
   terraform import digitalocean_firewall.production a4e42798-ab22-467f-a821-daa290f56655
   terraform import digitalocean_firewall.staging c12dfc7f-f43a-4b32-96c6-80ba34035b1a
   ```

6. **Add SSH key to Terraform**
   - Create data source or import existing SSH key resource

7. **Add DigitalOcean project to Terraform**
   - Create or import PAUSATF project resource
   - Associate all resources with project

### Low Priority

8. **Consider creating production database**
   - If needed, create the database defined in Terraform
   - Or remove from Terraform if external database is preferred

9. **Document snapshot management**
   - Production uses custom snapshot instead of cloud-init
   - Document process for creating/updating snapshots
   - Or migrate to cloud-init based deployment

## Terraform State Locations

- **Production:** `s3://pausatf-terraform-state/production/terraform.tfstate` (DigitalOcean Spaces)
- **Staging:** `s3://pausatf-terraform-state/staging/terraform.tfstate` (DigitalOcean Spaces)
- **GitHub:** `s3://pausatf-terraform-state/github/terraform.tfstate` (DigitalOcean Spaces)
- **Dev:** Local state (no backend configured)

## Notes

- Production droplet (pausatf-prod) uses a custom snapshot from 2023-05-16, which may contain WordPress data and configuration not captured in Terraform
- Staging droplet (pausatf-stage) uses DigitalOcean's marketplace image, which includes pre-configured OpenLiteSpeed and WordPress
- Both approaches bypass the cloud-init templates defined in Terraform
- Consider standardizing on cloud-init templates for consistency and reproducibility
