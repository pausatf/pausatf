# Staging Deploy (OpenLiteSpeed + Terraform DNS)

Prereqs
- Terraform variables configured in `terraform/environments/staging/terraform.tfvars` (DO token, Cloudflare token, zone_id, SSH keys).
- Ansible vault with required secrets (DB passwords, salts, etc.).

Steps
1) Provision or update staging infra (creates droplet, DB and DNS for stage.pausatf.org):

```bash path=null start=null
cd terraform/environments/staging
terraform init
terraform plan
terraform apply
```

2) Configure stage with Ansible (no prod hosts touched):

```bash path=null start=null
cd ../../../ansible
ansible-playbook -i inventory/hosts.yml site.yml -l staging -e @group_vars/vault.yml
```

3) Verify
- Visit https://stage.pausatf.org
- OLS admin: https://stage.pausatf.org:7080 (use vault_ols_admin_password)
- WP health: `wp core version` via SSH

Rollback
- Re-run previous stable tag in Ansible, or destroy & recreate staging via Terraform.
