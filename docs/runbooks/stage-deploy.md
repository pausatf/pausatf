# Staging deploy

This runbook covers the tracked staging path: the Terraform `staging` root and the Ansible `site.yml` run against
`pausatf-stage`. It does not describe the Docker stack that the live staging droplet currently runs. That stack is
owned by [pausatf-deployment](https://github.com/pausatf/pausatf-deployment); see the
[deployment runbook](deployment.md) before applying anything here to a live host.

## Prerequisites

- An HCP Terraform login (`terraform login`) with access to the `pausatf-staging` workspace.
- Terraform 1.10 or later. CI and `infra-staging.yml` use 1.10.3.
- Variables for the staging root, from `terraform/environments/staging/terraform.tfvars.example`: `do_token`,
  `cloudflare_api_token`, `cloudflare_zone_id`, `region`, `droplet_size`, `database_size`, `ssh_key_fingerprints` and
  `environment`. Keep the filled file out of Git, and pass the tokens as `TF_VAR_*` environment values where you can.
- The Ansible vault password and, for the workflow path, the secrets listed in
  [GITHUB-ACTIONS.md](../governance/GITHUB-ACTIONS.md#secrets-and-variables-by-workflow).

## Terraform

The staging root declares the staging droplet and firewall through `stacks/wordpress` and the `stage.pausatf.org` DNS
record through the Cloudflare DNS module.

Plan from the workflow or from a terminal:

```bash
gh workflow run infra-staging.yml        # plan only, environment terraform-plan
```

```bash
cd terraform/environments/staging
terraform init
terraform plan
```

`infra-staging.yml` never applies. Apply from a terminal, on a trusted runner, only after reviewing the plan for
replacements, destroys and unrelated changes:

```bash
terraform apply
```

## Ansible

Pushing to the `staging` branch runs `deploy-staging.yml`, which calls the reusable `deploy.yml` with
`ansible_limit: pausatf-stage` in the `staging` environment. To run it by hand:

```bash
cd ansible
ansible-playbook -i inventory/hosts.yml site.yml -l pausatf-stage --check --diff
ansible-playbook -i inventory/hosts.yml site.yml -l pausatf-stage
```

The inventory reads the target address from `STAGE_SERVER_IP`. Run the check-mode pass first and read its output.
`site.yml` configures host OpenLiteSpeed for staging. If the staging droplet is running the Docker stack, a host-stack
run does not manage it.

## Verify

- Load `https://stage.pausatf.org` and sign in.
- Check the web server and PHP service status on the host.
- Run `wp core version` and `wp plugin list` as the web user.

## Rollback

Re-run Ansible from the previous known-good commit. If the droplet itself is the problem, recreate it from Terraform and
restore data from a [verified backup](../../scripts/backup/README.md); a weekly DigitalOcean snapshot of staging also
exists (`do-weekly-stage-snapshot.yml`, last two kept).
