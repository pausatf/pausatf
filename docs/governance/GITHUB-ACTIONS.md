# GitHub Actions workflows

This is the reference for the workflows in `.github/workflows/` and the composite action in `.github/actions/`.
The YAML is the source of truth. This page records what each workflow does, what triggers it, and which secrets and
variables it reads, so you can tell what a run needs before you dispatch it. Secret and variable values are never
recorded here; only their names.

Two workflows have state that the YAML does not show:

- **`deploy-prod.yml` is disabled.** The workflow is set to `disabled_manually` in the repository's Actions settings,
  so its `push` to `main` trigger and its manual dispatch do not run. The file still declares both triggers. Enabling it
  again makes every push to `main` queue a production deployment, including docs-only pushes. Do not enable it without
  a deliberate production decision.
- **Terraform apply is manual.** No workflow applies Terraform. `infra-staging.yml` and `terraform-plan.yml` only plan. Applies are run from an
  operator's terminal against the HCP Terraform workspace, after a reviewed plan (see
  [Deployment](../runbooks/deployment.md) and [terraform/README.md](../../terraform/README.md)).

## Workflows

### Validation

| Workflow | Triggers | What it does |
| --- | --- | --- |
| `ci.yml` (CI) | Pull request; push to `main` | Runs the jobs listed under [CI jobs](#ci-jobs). Superseded runs are cancelled on pull requests only, so every commit on `main` keeps a complete result. Jobs run on every pull request, including dependency updates. |
| `codeql.yml` (CodeQL) | Push to `main`; pull request; Mondays 06:00 UTC; manual | CodeQL analysis for the `actions` and `javascript` languages. Needs `security-events: write`. |
| `molecule.yml` (Molecule Tests) | Pull request or push to `main` that touches `ansible/roles/**` or this workflow | Runs `molecule test` for each role in the job matrix, from `ansible/roles/<role>`. |
| `php-quality.yml` (PHP Quality) | Pull request or push to `main` that touches maintained PHP paths, `phpcs.xml.dist` or the changed-lines checker | PHP syntax lint, WordPress Coding Standards through `scripts/check_phpcs_changed_lines.py`, and actionlint over the workflows. |

No validation workflow reads a secret or variable.

### CI jobs

| Job | Check |
| --- | --- |
| Maintenance Safety Tests | `python3 -m unittest discover -s scripts -p 'test_*.py'` with PHP 8.3 available for the PHP-backed tests |
| Ansible Lint | `ansible-lint --offline --strict` in `ansible/` (ansible-lint 25.12.2) |
| YAML Lint | `yamllint` |
| Ansible Syntax Check | `ansible-playbook --syntax-check` on the playbooks and `site.yml`, plus a check that inventory host variables render |
| ShellCheck, Bash Syntax Check | ShellCheck 2.0.0 action and `bash -n` over shell scripts |
| Markdown Lint | markdownlint with `.markdownlint.json` |
| Markdown Link Check | markdown-link-check with `.markdown-link-check.json` |
| Terraform Format | `terraform fmt -check -recursive` |
| Terraform Validate | `terraform validate` for each of `production`, `staging`, `github`, `dev` and `cloudflare` |
| TFSec | tfsec, with SARIF uploaded to code scanning |
| TFLint | TFLint v0.66.0, `--recursive`, failing on errors |

Terraform checks use Terraform 1.10.3. The `hcp` root is not in the validate matrix.

### Deployment and migration

| Workflow | Triggers | Target | Notes |
| --- | --- | --- | --- |
| `deploy.yml` (reusable) | `workflow_call` only | Set by the caller | Installs Ansible through the `setup-ansible` action, decrypts the vault, runs `ansible-playbook site.yml -l <limit>` with the runner's public IP as `ansible_controller_cidr`, and optionally health-checks over SSH. Declared inputs: `environment`, `ansible_limit`, `host_env_name`, `healthcheck_host`. |
| `deploy-dev.yml` | Push to `dev`; manual | `pausatf-dev`, environment `development` | Calls `deploy.yml`. |
| `deploy-staging.yml` | Push to `staging`; manual | `pausatf-stage`, environment `staging` | Calls `deploy.yml`. |
| `deploy-prod.yml` | Push to `main`; manual. **Disabled in repository settings.** | `pausatf-prod`, environment `production` | A `staging-validate` job runs the playbook against staging, then `deploy` calls `deploy.yml`. The `healthcheck_host` it passes is a placeholder address, so the post-deploy health check would not reach a real host. |
| `infra-staging.yml` | Manual | `terraform/environments/staging` | Plan only: `terraform init` and `terraform plan -input=false -out=tfplan`. It never applies. Runs in environment `terraform-plan`. |
| `terraform-plan.yml` | Push to `main` touching `terraform/**`; Mondays 14:00 UTC; manual | `cloudflare`, `staging` and `dev` | Plan only, with `-lock=false -detailed-exitcode`; the job summary says whether each workspace has drift. Runs in environment `terraform-plan`, whose branch policy allows only `main`, so it cannot run on pull requests. |

All deploy workflows serialize on a concurrency group and do not cancel an in-progress run. The `deploy.yml` job
removes the vault file, vault password and SSH key at the end, even on failure.

These playbook runs configure the host web stack. The Docker stack that runs on the three current droplets is owned by
[pausatf-deployment](https://github.com/pausatf/pausatf-deployment); see the
[deployment runbook](../runbooks/deployment.md) before pointing any of these at a droplet.

### Operations and scheduled maintenance

| Workflow | Triggers | What it does |
| --- | --- | --- |
| `do-nightly-snapshot.yml` | Daily 10:00 UTC; manual | Snapshots the production droplet with `doctl` and keeps the seven newest. |
| `do-weekly-stage-snapshot.yml` | Sundays 11:00 UTC; manual | Snapshots the staging droplet and keeps the two newest. |
| `sync-jetpack-ips.yml` | Mondays 07:00 UTC; manual | Fetches Jetpack's published IPv4 ranges, rewrites the `jetpack_ip_ranges` list when it changed, and opens a pull request. Uses the built-in `GITHUB_TOKEN` with `contents`, `pull-requests` and `issues` write access. |
| `wordpress-update-check.yml` | Daily 09:17 UTC; manual | Reads installed WordPress core and plugin versions from production and compares them with wordpress.org on the runner. Opens, refreshes or closes one tracking issue labelled `wordpress`, `dependencies` and `ops`. It applies no updates. |
| `integration-tests.yml` | Mondays 07:00 UTC; manual (input `environment`) | Runs the HTTP and DNS classes of `docs/tests/integration/test_infrastructure.py` against `www.pausatf.org`. It needs no secrets and has no SSH access. |

`wordpress-update-check` is the only workflow with shell access to production. It connects through the Cloudflare Access
SSH application with a service token, pins the host key from `PROD_SSH_KNOWN_HOSTS`, and logs in with a key the server
forces to one read-only command. The server side is in [`ops/ci-readonly`](../../ops/ci-readonly/README.md). For other
production diagnostics or an inventory capture, run the playbooks from an operator machine.

### Composite action

`.github/actions/setup-ansible` installs Python and `ansible-core`, installs the collections in
`ansible/requirements.yml` with up to three attempts, and by default writes a dummy `ansible/.vault_pass.sh` for lint
and syntax jobs. It is the only place that pins `ansible-core` (2.17.5, Python 3.12). Inputs: `python-version`,
`ansible-core-version`, `extra-packages` and `ci-vault-stub`. Deploy jobs set `ci-vault-stub: 'false'` because they
decrypt the real vault.

## Secrets and variables by workflow

Names only. Where the value lives is noted under [Where values are stored](#where-values-are-stored).

| Workflow | Secrets | Variables |
| --- | --- | --- |
| `deploy-dev.yml` | `DEV_SSH_PRIVATE_KEY`, `DEV_SERVER_IP`, `ANSIBLE_VAULT_PASSWORD`, `ANSIBLE_VAULT_FILE` | none |
| `deploy-staging.yml` | `STAGING_SSH_PRIVATE_KEY`, `STAGE_SERVER_IP`, `ANSIBLE_VAULT_PASSWORD`, `ANSIBLE_VAULT_FILE` | none |
| `deploy-prod.yml` | `PROD_SSH_PRIVATE_KEY`, `PROD_SERVER_IP`, `STAGING_SSH_PRIVATE_KEY`, `STAGE_SERVER_IP`, `ANSIBLE_VAULT_PASSWORD`, `ANSIBLE_VAULT_FILE` | none |
| `deploy.yml` | Receives `SSH_PRIVATE_KEY`, `TARGET_SERVER_IP`, `ANSIBLE_VAULT_PASSWORD`, `ANSIBLE_VAULT_FILE` from the caller | none |
| `infra-staging.yml`, `terraform-plan.yml` | `HCP_TERRAFORM_API_TOKEN`, `DO_TOKEN`, `CLOUDFLARE_API_TOKEN`, `TF_VAR_SSH_ALLOWED_IPS` | none |
| `do-nightly-snapshot.yml` | `DO_TOKEN`; `DO_PROD_DROPLET_ID` optional | none |
| `do-weekly-stage-snapshot.yml` | `DO_TOKEN`; `DO_STAGE_DROPLET_ID` optional | none |
| `sync-jetpack-ips.yml` | built-in `GITHUB_TOKEN` | none |
| `wordpress-update-check.yml` | `PROD_CI_SSH_KEY`, `CF_ACCESS_CLIENT_ID`, `CF_ACCESS_CLIENT_SECRET` | `PROD_HOST`, `PROD_SSH_KNOWN_HOSTS` |
| `integration-tests.yml`, `ci.yml`, `codeql.yml`, `molecule.yml`, `php-quality.yml` | none | none |

When a value is missing: `deploy.yml` fails fast on a missing `TARGET_SERVER_IP`, vault password or vault file. `wordpress-update-check`
fails when its key, token, host or known_hosts value is missing.

### Where values are stored

- **Repository level.** When this was written, `gh secret list` and `gh variable list` showed `ANSIBLE_VAULT_FILE`,
  `ANSIBLE_VAULT_PASSWORD`, `DEV_SSH_PRIVATE_KEY`, `DO_PROD_DROPLET_ID`, `DO_TOKEN`, `PROD_SSH_PRIVATE_KEY`,
  `STAGING_SSH_PRIVATE_KEY`, `PROD_CI_SSH_KEY`, `CF_ACCESS_CLIENT_ID` and `CF_ACCESS_CLIENT_SECRET` as secrets, and
  `PROD_HOST` and `PROD_SSH_KNOWN_HOSTS` as variables. `PROD_CI_SSH_KEY` and the two `CF_ACCESS_*` secrets are read only
  by `wordpress-update-check`.
- **Environment level.** Environment `production` holds `ANSIBLE_VAULT_FILE` and `PROD_SSH_PRIVATE_KEY` and has required
  reviewers. Environment `terraform-plan` holds `CLOUDFLARE_API_TOKEN`, `HCP_TERRAFORM_API_TOKEN` and
  `TF_VAR_SSH_ALLOWED_IPS`. Environments `development` and `staging` have a branch policy. Environments `production_new`
  and `copilot` hold nothing.
- **Not found.** The `*_SERVER_IP` secrets and `DO_STAGE_DROPLET_ID` do not appear at repository or environment level.
  An organization-level secret would not show up in that listing, so a deploy may still work; check before relying on
  either result.

[SECRETS.md](SECRETS.md) covers handling, rotation and the vault file.

## Workflow conventions

Every workflow follows the hardening in the merged Actions hardening change and the later CI change (#293):

- Third-party actions are pinned to a commit SHA with the version in a trailing comment. Dependabot proposes the bumps.
- `permissions: contents: read` at the top level; a job asks for more only where it needs it (CodeQL, issue and pull
  request writers).
- `actions/checkout` runs with `persist-credentials: false`, except in `sync-jetpack-ips`, which pushes a branch.
- Jobs set `timeout-minutes` and run on `ubuntu-24.04`, not `ubuntu-latest`.
- User-controlled input reaches shell steps through `env:`, not through `${{ }}` interpolation in `run:`.

## Registered workflow without a file

The repository's Actions list still contains `ansible-lint.yml`, whose file is no longer in `main`. It is a stale
registration and does not run; Ansible lint now runs inside `ci.yml`.

## Dependabot

`.github/dependabot.yml` runs weekly on Mondays with a seven-day cooldown for each ecosystem:

| Ecosystem | Directories | Grouping |
| --- | --- | --- |
| `github-actions` | `/` and `/.github/actions/*` | CodeQL actions in one group; `actions/*` and `hashicorp/*` in another |
| `terraform` | `/terraform/environments/*`, `/terraform/modules/**`, `/terraform/stacks/*` | One group |
| `pip` | `/docs` | Minor and patch |
| `composer` | `/wordpress/plugins/pausatf-membership` | Minor and patch |
| `docker-compose` | `/scripts/docker` | Minor and patch |
| `docker` | `/scripts/docker/nginx`, `/scripts/docker/wordpress` | Minor and patch |

No workflow merges Dependabot pull requests automatically. They go through the same required CI checks as any other
change and are merged by a maintainer with a squash merge.

## Private-repository billing

This repository is public, so its Actions run. The organization's private repositories (pausatf-site,
pausatf-deployment, pausatf-generatepress-child, pausatf-archive-tooling) have had Actions blocked by the
organization's billing state. Their checks are run locally until that is resolved.
