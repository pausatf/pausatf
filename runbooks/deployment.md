# Deployment Runbook

## Overview

This runbook covers standard deployment procedures for infrastructure and application changes.

## Deployment Types

### 1. Infrastructure Changes (Terraform)

#### Pre-Deployment Checklist

- [ ] Changes reviewed and approved via PR
- [ ] Terraform plan executed and reviewed
- [ ] All CI checks passing
- [ ] Stakeholders notified of maintenance window
- [ ] Backup verified within last 24 hours

#### Deployment Steps

1. **Review the plan**
   ```bash
   cd terraform/environments/production
   ./scripts/terraform-wrapper.sh production plan
   ```

2. **Apply changes**
   ```bash
   # For manual deployment
   ./scripts/terraform-wrapper.sh production apply

   # Or trigger via GitHub Actions
   # Navigate to Actions tab and run "Terraform Apply" workflow
   ```

3. **Verify changes**
   ```bash
   # Check outputs
   terraform output

   # Verify resources
   doctl compute droplet list
   ```

#### Rollback Procedure

```bash
# Revert to previous state
cd terraform/environments/production
terraform apply -target=module.RESOURCE_NAME

# Or restore from state backup
terraform state pull > current.tfstate
# ... restore previous state ...
terraform state push previous.tfstate
```

### 2. Server Configuration Changes (Ansible)

#### Pre-Deployment Checklist

- [ ] Playbooks validated with --check mode
- [ ] Changes tested in staging environment
- [ ] Backup of current configuration
- [ ] Maintenance window scheduled

#### Deployment Steps

1. **Test in check mode**
   ```bash
   cd ansible
   ansible-playbook -i inventory/hosts.yml playbooks/site.yml --check
   ```

2. **Deploy to staging first**
   ```bash
   ansible-playbook -i inventory/hosts.yml playbooks/site.yml --limit staging
   ```

3. **Verify staging**
   - Test all critical functionality
   - Review logs for errors
   - Monitor system metrics

4. **Deploy to production**
   ```bash
   ansible-playbook -i inventory/hosts.yml playbooks/site.yml --limit production
   ```

5. **Verify production**
   ```bash
   # Check service status
   ansible production -i inventory/hosts.yml -m shell -a "systemctl status nginx php8.3-fpm"

   # Verify website accessibility
   curl -I https://pausatf.org
   ```

#### Rollback Procedure

```bash
# Revert to previous role version
cd ansible
git checkout HEAD~1 roles/ROLE_NAME
ansible-playbook -i inventory/hosts.yml playbooks/site.yml --tags ROLE_NAME
```

### 3. WordPress Updates

#### Pre-Deployment Checklist

- [ ] Full backup completed
- [ ] Database backup verified
- [ ] Staging tested successfully
- [ ] Maintenance mode enabled

#### Deployment Steps

1. **Enable maintenance mode**
   ```bash
   ssh root@pausatf-prod
   cd /var/www/html
   wp maintenance-mode activate
   ```

2. **Backup current installation**
   ```bash
   tar -czf /tmp/wordpress-backup-$(date +%Y%m%d).tar.gz /var/www/html
   ```

3. **Update WordPress**
   ```bash
   # Update core
   wp core update

   # Update plugins
   wp plugin update --all

   # Update themes
   wp theme update --all
   ```

4. **Verify and test**
   ```bash
   # Check for errors
   wp plugin list
   wp theme list

   # Test critical pages
   curl -I https://pausatf.org
   ```

5. **Disable maintenance mode**
   ```bash
   wp maintenance-mode deactivate
   ```

### 4. SSL Certificate Renewal

#### Automatic Renewal

Certbot automatically renews certificates. Verify:

```bash
ssh root@pausatf-prod
certbot renew --dry-run
```

#### Manual Renewal

```bash
ssh root@pausatf-prod
certbot renew
systemctl reload nginx
```

## Emergency Procedures

### Emergency Rollback

```bash
# Infrastructure
cd terraform/environments/production
terraform apply -target=module.RESOURCE_NAME

# Application
ssh root@pausatf-prod
cd /var/www/html
tar -xzf /tmp/wordpress-backup-YYYYMMDD.tar.gz
systemctl restart php8.3-fpm nginx
```

### Emergency Hotfix

```bash
# 1. Create hotfix branch
git checkout -b hotfix/critical-fix main

# 2. Make necessary changes

# 3. Deploy directly to production
cd ansible
ansible-playbook -i inventory/hosts.yml playbooks/site.yml --limit production

# 4. Create PR and merge after verification
```

## Post-Deployment

### Verification Steps

1. **Check service health**
   ```bash
   # Website accessibility
   curl -I https://pausatf.org
   curl -I https://stage.pausatf.org

   # SSL validity
   openssl s_client -connect pausatf.org:443 -servername pausatf.org
   ```

2. **Monitor logs**
   ```bash
   ssh root@pausatf-prod
   tail -f /var/log/nginx/error.log
   tail -f /var/log/php8.3-fpm/www-error.log
   ```

3. **Check monitoring**
   - DigitalOcean monitoring dashboard
   - Check for any new alerts

### Documentation

- Update CHANGELOG.md
- Document any manual changes
- Update runbooks if procedures changed

## Deployment Schedule

- **Staging**: Any time, automated via CI/CD
- **Production - Minor**: Tuesday/Thursday, 10 AM - 2 PM PST
- **Production - Major**: Tuesday, 10 AM - 4 PM PST (scheduled 1 week ahead)
- **Emergency**: Any time with approval

## Contact Information

- **Infrastructure Lead**: [Your Name]
- **On-Call**: [On-Call Contact]
- **Escalation**: [Escalation Contact]
