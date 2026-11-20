# Automation and dependency management

This page describes the automation that runs on changes to this repository: Dependabot, the local pre-commit hooks, and
the CI checks. For each workflow's triggers and secrets see [GITHUB-ACTIONS.md](GITHUB-ACTIONS.md).

## Dependabot

Configuration is in `.github/dependabot.yml`. Every ecosystem runs weekly on Monday with a seven-day cooldown, so a new
release waits a week before Dependabot proposes it.

| Ecosystem | Scanned directories | Grouping |
| --- | --- | --- |
| `github-actions` | `/` and `/.github/actions/*` (the composite actions, which `/` does not scan) | CodeQL actions together; `actions/*` and `hashicorp/*` together |
| `terraform` | `/terraform/environments/*`, `/terraform/modules/**`, `/terraform/stacks/*` | One group |
| `pip` | `/docs` (the documentation and test tooling requirements) | Minor and patch |
| `composer` | `/wordpress/plugins/pausatf-membership` | Minor and patch |
| `docker-compose` | `/scripts/docker` | Minor and patch |
| `docker` | `/scripts/docker/nginx`, `/scripts/docker/wordpress` | Minor and patch |

`/terraform` itself holds no `.tf` files, which is why the Terraform entry lists the subdirectories. JavaScript assets in
this repository are vendored and have no package manifest, so there is no npm entry. Add one if a manifest is introduced.

### How updates are handled

No workflow approves or merges Dependabot pull requests. Each one runs the same required CI checks as any other pull
request; since #293 no CI job is skipped because the title starts with `chore(deps):`. A maintainer reviews the
changelog for the bump, checks that CI passed on the current head, and merges with a squash merge.

Two constraints to keep in mind when reviewing:

- `ansible-core` is pinned in one place, `.github/actions/setup-ansible/action.yml`. Dependabot does not manage that
  value, and the `docs/requirements.txt` floor for Ansible tooling is a separate pin. Moving to a supported
  `ansible-core` line needs a Molecule run and a syntax check.
- Terraform provider bumps change what a plan produces. A merged bump does not apply anything. Review a fresh plan
  before the next apply.

## Pre-commit hooks

Hooks run locally before a commit. Install them once:

```bash
pip install pre-commit    # or: brew install pre-commit
pre-commit install
pre-commit run --all-files
```

`.pre-commit-config.yaml` configures these hooks:

| Area | Hooks |
| --- | --- |
| General | `check-added-large-files` (1000 KB), `check-merge-conflict`, `check-yaml`, `end-of-file-fixer`, `trailing-whitespace`, `mixed-line-ending`, `detect-private-key` |
| Secrets | `detect-secrets` (excluding docs, Markdown and some legacy paths), `gitleaks` |
| Terraform | `terraform_fmt`, `terraform_validate`, `terraform_docs`, `terraform_tflint`, `terraform_trivy`, and `checkov` |
| Ansible and YAML | `ansible-lint`, `yamllint` |
| Shell | `shellcheck` |
| Markdown | `markdownlint` (rules in `.markdownlint.json`) |
| PHP | A local `phpcs` hook |
| Workflows | `actionlint` |

There is no `commit-msg` stage, so commit messages are not validated by a hook; see
[COMMIT-STANDARDS.md](COMMIT-STANDARDS.md). Hook versions are pinned in the config file. Update them by hand with
`pre-commit autoupdate` and review the diff; no workflow does it.

Skipping hooks with `--no-verify` is for emergencies. CI repeats the lint, validate and security checks that matter,
except secret detection: `detect-secrets` and `gitleaks` run only here. Do not skip them.

## CI

`ci.yml` runs on every pull request and on pushes to `main`, and covers Ansible lint and syntax, YAML, ShellCheck, Bash
syntax, Markdown lint, link check, Terraform format and validate, tfsec, TFLint and the Python maintenance tests.
`codeql.yml`, `molecule.yml` and `php-quality.yml` cover CodeQL, role scenarios and PHP. The job list is in
[GITHUB-ACTIONS.md](GITHUB-ACTIONS.md#ci-jobs).

## Not automated

- Deployment of production. `deploy-prod.yml` is disabled in repository settings.
- Terraform apply. Plans run on demand with `infra-staging.yml`; applies are manual.
- Updates to WordPress core and plugins. `wordpress-update-check.yml` only reports them in an issue.
- Merging Dependabot pull requests.
