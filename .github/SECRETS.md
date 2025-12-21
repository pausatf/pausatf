# GitHub Secrets Configuration

This document describes all GitHub repository secrets configured for CI/CD automation.

**Last Updated:** 2025-12-21

---

## Configured Secrets

### DigitalOcean Credentials

**DIGITALOCEAN_ACCESS_TOKEN**
- **Description:** DigitalOcean API access token for doctl operations
- **Usage:** Automated backup verification, droplet monitoring, resource checks
- **Permissions:** Read access to droplets, backups, and account information
- **Rotation:** Should be rotated annually or if compromised
- **Created:** 2025-12-21

**PROD_DROPLET_ID**
- **Description:** Production server droplet ID (355909945)
- **Usage:** Direct droplet operations and monitoring
- **Value Type:** Numeric ID
- **Created:** 2025-12-21

**STAGING_DROPLET_ID**
- **Description:** Staging server droplet ID (538411208)
- **Usage:** Direct droplet operations and monitoring
- **Value Type:** Numeric ID
- **Created:** 2025-12-21

---

### Cloudflare Credentials

**CLOUDFLARE_API_TOKEN**
- **Description:** Cloudflare API token for DNS and CDN operations
- **Usage:** DNS verification, cache purging, SSL status checks
- **Permissions:** Zone read access, DNS read access, SSL/TLS read access
- **Rotation:** Should be rotated annually or if compromised
- **Created:** 2025-12-21

**CLOUDFLARE_ZONE_ID**
- **Description:** Cloudflare zone ID for pausatf.org (67b87131144a68ad5ed43ebfd4e6d811)
- **Usage:** Zone-specific API operations
- **Value Type:** UUID
- **Created:** 2025-12-21

---

### Server Connection Details

**PROD_SSH_HOST**
- **Description:** Production server SSH hostname (prod.pausatf.org)
- **Usage:** Future SSH-based deployment automation
- **Value Type:** Hostname/FQDN
- **Created:** 2025-12-21

**STAGING_SSH_HOST**
- **Description:** Staging server SSH hostname (stage.pausatf.org)
- **Usage:** Future SSH-based deployment automation
- **Value Type:** Hostname/FQDN
- **Created:** 2025-12-21

---

## GitHub Actions Workflows

### Infrastructure Health Check
**File:** `.github/workflows/infrastructure-health.yml`

**Schedule:** Daily at 6:00 AM UTC

**Secrets Used:**
- `DIGITALOCEAN_ACCESS_TOKEN` - Droplet and backup checks
- `CLOUDFLARE_API_TOKEN` - DNS and SSL verification
- `CLOUDFLARE_ZONE_ID` - Zone-specific operations
- `PROD_DROPLET_ID` - Production monitoring
- `STAGING_DROPLET_ID` - Staging monitoring

**What It Does:**
1. Verifies DigitalOcean backups are running daily
2. Checks droplet status and health
3. Validates Cloudflare DNS configuration
4. Checks SSL certificate status
5. Monitors resource usage

---

### Documentation Validation
**File:** `.github/workflows/documentation-validation.yml`

**Trigger:** On push to main branch, pull requests, or manual dispatch

**Secrets Used:** None (documentation validation only)

**What It Does:**
1. Validates internal documentation links
2. Checks documentation structure consistency
3. Verifies all required files exist
4. Runs markdown linting
5. Scans for accidentally exposed secrets

---

## Security Best Practices

### Secret Management

**DO:**
- ✅ Rotate secrets annually or when team members leave
- ✅ Use read-only tokens where possible (current setup)
- ✅ Limit token scope to minimum required permissions
- ✅ Monitor GitHub Actions logs for unauthorized access
- ✅ Document all secrets and their purpose
- ✅ Use GitHub's secret scanning features

**DON'T:**
- ❌ Never commit secrets to the repository
- ❌ Never log secret values in workflow outputs
- ❌ Never share secrets via unencrypted channels
- ❌ Never use personal access tokens for production systems
- ❌ Never give tokens write access unless absolutely necessary

---

### Token Permissions

**DigitalOcean API Token Permissions:**
- ✅ Read access to Droplets
- ✅ Read access to Images/Backups
- ✅ Read access to Account information
- ❌ **NO** write/delete access to Droplets
- ❌ **NO** billing modification access

**Cloudflare API Token Permissions:**
- ✅ Zone:Read
- ✅ DNS:Read
- ✅ SSL and Certificates:Read
- ❌ **NO** Zone Settings:Edit
- ❌ **NO** DNS Records:Edit (for safety)

---

## Secret Rotation Procedure

### When to Rotate

1. **Immediately** if:
   - Secret is compromised or exposed
   - Team member with access leaves organization
   - Suspicious activity detected in logs

2. **Scheduled** rotation:
   - Annually (recommended minimum)
   - After major infrastructure changes
   - When updating security policies

### How to Rotate

**DigitalOcean Token:**
```bash
# 1. Generate new token in DigitalOcean console
# https://cloud.digitalocean.com/account/api/tokens

# 2. Update GitHub secret
gh secret set DIGITALOCEAN_ACCESS_TOKEN \
  --repo pausatf/pausatf-infrastructure-docs \
  --body "NEW_TOKEN_HERE"

# 3. Test workflow runs successfully with new token

# 4. Revoke old token in DigitalOcean console
```

**Cloudflare Token:**
```bash
# 1. Generate new token in Cloudflare dashboard
# https://dash.cloudflare.com/profile/api-tokens

# 2. Update GitHub secret
gh secret set CLOUDFLARE_API_TOKEN \
  --repo pausatf/pausatf-infrastructure-docs \
  --body "NEW_TOKEN_HERE"

# 3. Test workflow runs successfully

# 4. Revoke old token in Cloudflare dashboard
```

---

## Workflow Troubleshooting

### Common Issues

**Workflow fails with "401 Unauthorized"**
- **Cause:** Expired or invalid API token
- **Solution:** Rotate the token using procedure above

**Workflow fails with "403 Forbidden"**
- **Cause:** Insufficient token permissions
- **Solution:** Verify token has correct scopes in provider dashboard

**Workflow fails with "Resource not found"**
- **Cause:** Incorrect droplet ID or zone ID
- **Solution:** Verify IDs match current infrastructure

### Viewing Workflow Runs

```bash
# List recent workflow runs
gh run list --repo pausatf/pausatf-infrastructure-docs

# View specific run details
gh run view RUN_ID --repo pausatf/pausatf-infrastructure-docs

# View run logs
gh run view RUN_ID --log --repo pausatf/pausatf-infrastructure-docs
```

---

## Future Enhancements

### Planned Secret Additions

**SSH_PRIVATE_KEY** (Future)
- For automated deployments via SSH
- Should be a dedicated deploy key (read-only where possible)
- Required for: Automated cache purging, emergency rollbacks

**SLACK_WEBHOOK_URL** (Optional)
- For workflow notifications
- Alerts on backup failures or infrastructure issues

**WORDPRESS_API_TOKEN** (Future)
- For WordPress core/plugin update automation
- Requires WordPress Application Passwords feature

---

## Monitoring and Alerts

### GitHub Actions Email Notifications

By default, GitHub sends email notifications on workflow failures to repository admins.

**To customize:**
1. Go to: https://github.com/settings/notifications
2. Configure Actions notifications
3. Enable mobile notifications for critical workflows

### Recommended Monitoring

- [ ] Review workflow runs weekly
- [ ] Check backup verification results daily (automated)
- [ ] Monitor for failed workflows in GitHub notifications
- [ ] Review Cloudflare/DigitalOcean audit logs monthly
- [ ] Test secret rotation procedure quarterly

---

## Contact and Support

**For secret rotation or security issues:**
- Repository Admin: Thomas Vincent
- DigitalOcean Support: https://www.digitalocean.com/support/
- Cloudflare Support: https://support.cloudflare.com/
- GitHub Support: https://support.github.com/

**Emergency Procedures:**
- See: `10-operational-procedures.md` - Emergency Procedures section
- For compromised secrets: Immediately rotate all affected tokens
- For infrastructure emergencies: Use DigitalOcean console direct access

---

**Document Version:** 1.0
**Last Updated:** 2025-12-21
**Next Review:** 2026-01-21 (monthly)
**Maintained by:** Thomas Vincent
