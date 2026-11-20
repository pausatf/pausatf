# terraform-wrapper.sh

`scripts/terraform-wrapper.sh` runs one Terraform action in one directory under
`terraform/environments/`. It validates both arguments against an allow-list,
checks that `terraform` and `jq` exist, takes a local lock so two runs against
the same environment do not overlap, and adds a delay before `apply` and
`destroy`. It is a convenience for an operator's workstation. CI does not call it.

For the Terraform layout, state and workspaces, read
[../terraform/ENVIRONMENTS.md](../terraform/ENVIRONMENTS.md) and
[../terraform/README.md](../terraform/README.md) first; those are the authority
on how Terraform is run in this repository.

## Known limitation: `init` does not work

The `init` action runs
`terraform init -backend-config="<repo>/terraform/backend.hcl"`. That file does
not exist: it described the retired DigitalOcean Spaces backend, and the
environments now use HCP Terraform through a `cloud` block. `terraform/README.md`
says not to pass `backend.hcl`. Initialize from the environment directory
instead:

```bash
terraform login                      # once per workstation
cd terraform/environments/production
terraform init
```

The other actions (`validate`, `plan`, `apply`, `output`, `destroy`) call
`terraform` directly and work after a manual `init`.

## Usage

```bash
scripts/terraform-wrapper.sh <environment> [action]
```

The action defaults to `plan`. Run it from anywhere; the script locates the
repository root from its own path.

| Argument | Values |
| --- | --- |
| `environment` | `production`, `staging`, `dev`, `cloudflare`, `github` |
| `action` | `plan` (default), `apply`, `destroy`, `output`, `init`, `validate` |

The `hcp` root under `terraform/environments/` is not accepted by the wrapper;
run Terraform in that directory directly (it also needs `TFE_TOKEN`).

```bash
scripts/terraform-wrapper.sh production validate
scripts/terraform-wrapper.sh production plan
scripts/terraform-wrapper.sh production apply
scripts/terraform-wrapper.sh cloudflare output
```

## What each action does

| Action | Command run in `terraform/environments/<environment>` | Notes |
| --- | --- | --- |
| `init` | `terraform init -backend-config=.../terraform/backend.hcl` | Fails; see above. |
| `validate` | `terraform validate` | |
| `plan` | `terraform plan -out=tfplan` | Saves the plan to `tfplan` in the environment directory. |
| `apply` | `terraform apply tfplan` | Runs `plan` first if `tfplan` does not exist. Waits 5 seconds (Ctrl+C cancels), applies, then deletes `tfplan`. |
| `destroy` | `terraform destroy` | Prints a warning and waits 10 seconds, then Terraform's own confirmation prompt applies. |
| `output` | `terraform output` | |

When `apply` creates the plan itself, it applies that plan after the 5 second
delay without asking you to read it. Run `plan` as a separate step and review it
before `apply`.

## Checks and side effects

- Dependencies: `terraform` and `jq` must be on the path. The script does not
  currently use `jq`.
- Credentials: it warns, and continues, when `DIGITALOCEAN_TOKEN` or
  `CLOUDFLARE_API_TOKEN` is not set. Terraform itself then fails if a provider
  needs a missing credential. The script does not read, store or print token
  values. Set them from a keychain or secret manager at run time; do not type
  them into a command line or commit them in `.envrc`.
- Spaces signing region: it exports `AWS_REGION` and `AWS_DEFAULT_REGION` as
  `us-east-1` for the run, overriding any value in the caller's environment, so
  S3-compatible calls to DigitalOcean Spaces sign with the region Spaces expects.
- Lock: before running any action it writes its PID to
  `~/.terraform-locks/pausatf-<environment>.lock` and removes the file on exit. If the
  file exists and is under an hour old, the script stops and prints the PID. A
  lock older than an hour is treated as stale and removed. This lock is local to
  one workstation; it does not coordinate with CI, which uses GitHub Actions
  concurrency groups, or with HCP Terraform run locking.
- Plan files: `tfplan` is written to the environment directory and can contain
  sensitive values. The repository `.gitignore` covers `*.tfplan` but not a file
  named `tfplan`, so do not stage it.
- Shell options: `set -euo pipefail`; the script exits on the first failing
  command, and the lock is released by an `EXIT` trap.

## Errors you may see

| Message | Meaning |
| --- | --- |
| `Error: Invalid environment. Must be one of: ...` | The first argument is not in the allow-list. |
| `Error: Invalid action. Must be one of: ...` | The second argument is not in the allow-list. |
| `Error: Required dependency 'terraform' not found` | Install the missing tool; the same message appears for `jq`. |
| `Error: Terraform lock held for <environment> (PID ..., ...s ago)` | Another run holds the local lock. If that run is gone, remove the lock file named in the message. |
| `Warning: Stale lock found ... Removing.` | A lock over an hour old was removed and the run continued. |

A Terraform state lock error (`Error acquiring the state lock`) comes from HCP
Terraform, not from this script. Check the workspace run in HCP Terraform before
forcing an unlock.

## Changing the script

- New action: add it to the regular expression in `validate_action()` and to the
  `case` in `run_terraform()`, and update this file.
- New environment: add a directory under `terraform/environments/`, add its name
  to the regular expression in `validate_environment()` and to the usage text,
  and update this file.
- Run ShellCheck at warning severity, as CI does for `scripts/`:
  `shellcheck --severity=warning scripts/terraform-wrapper.sh`.
