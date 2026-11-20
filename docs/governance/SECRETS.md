# Secrets and variables

This page lists the GitHub secrets and variables the workflows in this repository read, what each one is for, and how to
handle and rotate them. It records names only. Never put a secret value in a file, an issue, a pull request or a command
line. The workflow-by-workflow mapping is in [GITHUB-ACTIONS.md](GITHUB-ACTIONS.md#secrets-and-variables-by-workflow).

## Secrets

| Name | Used by | Purpose |
| --- | --- | --- |
| `ANSIBLE_VAULT_PASSWORD` | Deploy workflows, `migrate-server` | Password for the Ansible vault. |
| `ANSIBLE_VAULT_FILE` | Deploy workflows | The encrypted `ansible/group_vars/vault.yml`, base64 encoded. The workflow decodes it to disk for the run and deletes it afterwards. |
| `PROD_SSH_PRIVATE_KEY` | `deploy-prod`, `migrate-server`, `wordpress-update-check`, `integration-tests`, `capture-prod-inventory`, `prod-widgets-log-probe`, `wp-plugin-audit` | SSH key for the production `github-deploy` user. |
| `STAGING_SSH_PRIVATE_KEY` | `deploy-staging`, `deploy-prod` (staging validation), `migrate-server` | SSH key for staging. |
| `DEV_SSH_PRIVATE_KEY` | `deploy-dev`, `migrate-server` | SSH key for dev. |
| `PROD_NEW_SSH_PRIVATE_KEY` | `deploy-prod-new` | SSH key for the replacement production group. |
| `MIGRATION_NEW_SSH_PRIVATE_KEY` | `migrate-server` | SSH key for the new server during a migration. |
| `PROD_SERVER_IP`, `STAGE_SERVER_IP`, `DEV_SERVER_IP`, `PROD_NEW_SERVER_IP` | Matching deploy workflows | Target address. The deploy job exports it under the inventory variable named by the caller's `host_env_name`. |
| `DO_TOKEN` | Snapshot, firewall, `migrate-server` and `infra-staging` workflows | DigitalOcean API token. The snapshot and firewall workflows need droplet snapshot and firewall write access, so treat it as a write token. |
| `DO_PROD_DROPLET_ID`, `DO_STAGE_DROPLET_ID` | Snapshot workflows | Optional numeric droplet IDs. When unset or inaccessible, the workflow looks the droplet up by name. |
| `CLOUDFLARE_API_TOKEN` | `infra-staging`, `migrate-server` | Cloudflare API token. `migrate-server` uses it for the DNS cutover and rollback, so it needs DNS edit on the zone. |
| `HCP_TERRAFORM_API_TOKEN` | `infra-staging` | HCP Terraform token, exported as `TF_TOKEN_app_terraform_io`. |
| `TF_VAR_SSH_ALLOWED_IPS` | `infra-staging` | Value for the Terraform `ssh_allowed_ips` variable. |
| `GITHUB_TOKEN` | `sync-jetpack-ips` and workflows that call the API | Built in. Not stored by anyone. |

Secrets added for #295 and not yet read by any merged workflow: `CF_ACCESS_CLIENT_ID` and `CF_ACCESS_CLIENT_SECRET`
(a Cloudflare Access service token). `PROD_CI_SSH_KEY` also exists but no current workflow reads it.

## Variables

| Name | Used by | Purpose |
| --- | --- | --- |
| `PROD_HOST` | `wordpress-update-check`, `integration-tests`, `capture-prod-inventory`, `prod-widgets-log-probe`, `wp-plugin-audit` | Production SSH host. |
| `PROD_SSH_KNOWN_HOSTS` | `wordpress-update-check` | Trusted `known_hosts` lines for the production SSH endpoint. Set it from a host key verified out of band. |

Pending #295: it adds the variable `PROD_SSH_HOST` (the Cloudflare Access SSH hostname), requires a `known_hosts` line
for that name in `PROD_SSH_KNOWN_HOSTS`, and stops reading `PROD_HOST`.

## Scopes

Repository-level and environment-level secrets are both in use. `production` holds `ANSIBLE_VAULT_FILE` and
`PROD_SSH_PRIVATE_KEY` and requires reviewer approval. `terraform-plan` holds `CLOUDFLARE_API_TOKEN`,
`HCP_TERRAFORM_API_TOKEN` and `TF_VAR_SSH_ALLOWED_IPS`. A job gets an environment's secrets only when it declares that
environment. `gh secret list --env <name> -R pausatf/pausatf` shows what an environment holds.

## Ansible vault

Production values such as database passwords, salts and the Tailscale and Cloudflare tokens live in
`ansible/group_vars/vault.yml`, encrypted with Ansible Vault and never committed in clear text. Operators keep the vault
password in their own keychain or password manager. The deploy workflows rebuild the file from `ANSIBLE_VAULT_FILE` and
`ANSIBLE_VAULT_PASSWORD` for the run only.

## Handling rules

- Do not print a secret. Do not echo it in a `run:` step, and do not enable shell tracing in a step that has secrets in
  its environment.
- Pass a value to a command through the environment or stdin, not as an argument. For example, set a secret from a file
  or an interactive prompt:

  ```bash
  gh secret set DO_TOKEN -R pausatf/pausatf        # prompts for the value
  gh secret set DO_TOKEN -R pausatf/pausatf < token.txt
  ```

- Scope tokens to what the workflow does. A token that only reads should not be able to write.
- Remove temporary files that hold keys. The deploy jobs delete the SSH key, vault password and decoded vault file in an
  `always()` step.
- If a value was exposed, rotate it first and clean up second.

## Rotation

1. Create the new credential at the provider (DigitalOcean, Cloudflare, HCP Terraform or the host's `authorized_keys`).
2. Store it with `gh secret set`, using the prompt or stdin form above, at the same scope as the old one.
3. Run one workflow that uses it, such as a manual `do-nightly-snapshot` for `DO_TOKEN`, and confirm it succeeds.
4. Revoke the old credential at the provider.

For the managed database password, follow [database-password-rotation.md](../procedures/database-password-rotation.md).

## Troubleshooting

| Symptom | Likely cause |
| --- | --- |
| `401 Unauthorized` from a provider | Expired or revoked token. Rotate it. |
| `403 Forbidden` | Token scope is too narrow for the call. |
| `ANSIBLE_VAULT_PASSWORD and ANSIBLE_VAULT_FILE secrets are required` | The job did not declare the environment that holds the secret, or the secret is unset. |
| `TARGET_SERVER_IP secret is required` | The matching `*_SERVER_IP` secret is not visible to the calling job. |
| A probe workflow skips or fails on SSH | `PROD_SSH_PRIVATE_KEY`, `PROD_HOST` or `PROD_SSH_KNOWN_HOSTS` is missing or the host key changed. |

List recent runs with `gh run list -R pausatf/pausatf` and read one with `gh run view <id> --log`.

The repository has no SECURITY.md. Report a suspected exposure privately to the maintainer, @somethingwithproof, and
not in a public issue.
