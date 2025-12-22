# Disaster Recovery Runbook

## Overview

This runbook provides step-by-step procedures for recovering infrastructure in case of catastrophic failure.

## Recovery Objectives

- **RTO (Recovery Time Objective)**: 4 hours
- **RPO (Recovery Point Objective)**: 24 hours

## Prerequisites

- Access to Terraform Cloud with state backups
- DigitalOcean account credentials
- Cloudflare account credentials
- Database backups (automated daily via DigitalOcean)
- Access to this GitHub repository

## Recovery Scenarios

### Scenario 1: Complete Droplet Failure

#### Symptoms
- Droplet is unresponsive
- Cannot SSH into server
- Website is down

#### Recovery Steps

1. **Verify the issue**
   ```bash
   # Check droplet status
   doctl compute droplet list

   # Attempt to ping the server
   ping pausatf-prod.pausatf.org
   ```

2. **Create new droplet from backup**
   ```bash
   # List available backups
   doctl compute droplet-action list 355909945

   # Restore from most recent backup
   doctl compute droplet-action restore 355909945 --image-id BACKUP_IMAGE_ID
   ```

3. **Or recreate using Terraform**
   ```bash
   cd terraform/environments/production

   # Taint the failed resource
   terraform taint module.pausatf_prod.digitalocean_droplet.this

   # Apply to recreate
   ./scripts/terraform-wrapper.sh production apply
   ```

4. **Update DNS if IP changed**
   ```bash
   # Update Cloudflare DNS records
   cd terraform/environments/production
   terraform apply
   ```

5. **Restore WordPress from backup**
   ```bash
   # SSH into new server
   ssh root@NEW_IP

   # Restore WordPress files from backup
   cd /var/www/html
   # ... restore files ...
   ```

6. **Verify functionality**
   - Check website loads correctly
   - Test WordPress admin access
   - Verify SSL certificates
   - Check email functionality

### Scenario 2: Database Failure

#### Symptoms
- WordPress shows database connection errors
- Unable to connect to database

#### Recovery Steps

1. **Check database status**
   ```bash
   doctl databases get pausatf-stage-db
   ```

2. **Restore from backup**
   ```bash
   # List available backups
   doctl databases backups list pausatf-stage-db

   # Restore from backup
   doctl databases restore pausatf-stage-db --backup-id BACKUP_ID
   ```

3. **Or recreate database cluster**
   ```bash
   cd terraform/environments/production
   terraform taint module.pausatf_stage_db.digitalocean_database_cluster.this
   terraform apply
   ```

4. **Update database connection in WordPress**
   - Update wp-config.php with new credentials
   - Test database connectivity

### Scenario 3: DNS/Cloudflare Failure

#### Symptoms
- Domain not resolving
- DNS propagation issues
- Cloudflare errors

#### Recovery Steps

1. **Verify Cloudflare status**
   ```bash
   # Check zone status
   curl -X GET "https://api.cloudflare.com/client/v4/zones/67b87131144a68ad5ed43ebfd4e6d811" \
     -H "X-Auth-Email: thomasv@mac.com" \
     -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"
   ```

2. **Recreate DNS records using Terraform**
   ```bash
   cd terraform/environments/production
   terraform apply
   ```

3. **Verify DNS propagation**
   ```bash
   dig pausatf.org
   nslookup pausatf.org
   ```

### Scenario 4: Complete Infrastructure Loss

#### Recovery Steps

1. **Clone infrastructure repository**
   ```bash
   git clone https://github.com/thomasvincent/infrastructure-as-code.git
   cd infrastructure-as-code
   ```

2. **Set up authentication**
   ```bash
   export DIGITALOCEAN_ACCESS_TOKEN="your-token"
   export CLOUDFLARE_API_TOKEN="your-token"
   export CLOUDFLARE_EMAIL="your-email"
   ```

3. **Initialize Terraform**
   ```bash
   cd terraform/environments/production
   terraform init
   ```

4. **Apply infrastructure**
   ```bash
   terraform plan -out=tfplan
   terraform apply tfplan
   ```

5. **Configure servers with Ansible**
   ```bash
   cd ../../../ansible

   # Update inventory with new IPs
   vim inventory/hosts.yml

   # Run configuration
   ansible-playbook -i inventory/hosts.yml playbooks/site.yml
   ```

6. **Restore application data**
   - Restore WordPress files from backup
   - Restore database from backup
   - Verify SSL certificates
   - Test all functionality

## Post-Recovery

### Verification Checklist

- [ ] All droplets are running
- [ ] SSH access works
- [ ] Website loads correctly
- [ ] WordPress admin accessible
- [ ] Database connections working
- [ ] Email sending/receiving works
- [ ] SSL certificates valid
- [ ] DNS records correct
- [ ] Monitoring alerts cleared
- [ ] Backups resuming normally

### Communication

1. Update status page (if applicable)
2. Notify stakeholders of resolution
3. Document incident details
4. Schedule post-mortem review

## Backup Verification

Regularly test backups:

```bash
# Test DigitalOcean droplet backup
doctl compute droplet-action snapshot 355909945 --snapshot-name "test-backup-$(date +%Y%m%d)"

# Verify database backups
doctl databases backups list pausatf-stage-db

# Test restoration process monthly
```

## Contact Information

- **Infrastructure Lead**: [Your Name]
- **DigitalOcean Support**: https://cloud.digitalocean.com/support
- **Cloudflare Support**: https://dash.cloudflare.com/support

## Related Documentation

- [Infrastructure Architecture](../architecture/infrastructure-overview.md)
- [Monitoring Guide](./monitoring.md)
- [Backup Procedures](./backups.md)
