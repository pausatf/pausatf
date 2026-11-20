# Scripts

Operational scripts, CI helpers and their unit tests for the pausatf repository.
Every file in this directory is documented in [SCRIPTS.md](SCRIPTS.md); this page
is the map.

## Layout

| Path | What it is | Documentation |
| --- | --- | --- |
| `firewall-maintenance.py` | Updates the DigitalOcean firewalls (Cloudflare ranges, one SSH source). Run by GitHub Actions. | [SCRIPTS.md](SCRIPTS.md#firewall-maintenancepy) |
| `snapshot-retention.py` | Chooses which droplet snapshots to delete after a new snapshot succeeds. Run by the snapshot workflows. | [SCRIPTS.md](SCRIPTS.md#snapshot-retentionpy) |
| `check_phpcs_changed_lines.py` | PHPCS gate that fails only on violations in changed lines. Run by the PHP quality workflow. | [SCRIPTS.md](SCRIPTS.md#check_phpcs_changed_linespy) |
| `terraform-wrapper.sh` | Local wrapper for `terraform` in `terraform/environments/<name>`. | [TERRAFORM-WRAPPER.md](TERRAFORM-WRAPPER.md) |
| `setup-managed-db-vars.sh` | Writes Managed MySQL credentials from DigitalOcean into an Ansible vault file. | [SCRIPTS.md](SCRIPTS.md#setup-managed-db-varssh) |
| `test_*.py` | Unit tests for the Python scripts and some WordPress and archive checks. | [Tests](#tests) |
| `backup/` | Production database and recovery backup scripts, systemd units and a test. | [backup/README.md](backup/README.md) |
| `docker/` | Local Docker Compose stacks (Apache and nginx with monitoring, and OpenLiteSpeed with MariaDB). | [docker/README.md](docker/README.md) |
| `local/sync-from-stage.sh` | Copies the staging database and uploads into the local OpenLiteSpeed stack. | [SCRIPTS.md](SCRIPTS.md#localsync-from-stagesh) |

The scripts that touch DigitalOcean, Cloudflare or production hosts are meant to
run from CI or from an operator's workstation with credentials supplied through
the environment. Nothing here stores a credential. Read each script's section in
[SCRIPTS.md](SCRIPTS.md) before running it; several modify live infrastructure.

## Tests

CI runs the Python unit tests with:

```bash
python3 -m unittest discover -s scripts -p 'test_*.py'
```

Run it from the repository root. The command takes about ten seconds and runs 27
tests. Some of them need `git`, `node` and `php` on the path: the archive test
runs JavaScript through `node`, and the widget visibility test runs the real
mu-plugin through `php`. CI installs PHP 8.3 for this job.

`scripts/backup/tests/test_manifest.py` lives in a directory without an
`__init__.py`, so the command above does not discover it. Run it directly:

```bash
python3 -m unittest scripts/backup/tests/test_manifest.py
```

| Test file | Covers |
| --- | --- |
| `test_firewall_maintenance.py` | Rule planning in `firewall-maintenance.py`: web ports untouched on the tunnel firewall, non-destructive SSH add, input validation, drift detection, dry run and partial failure reporting. |
| `test_firewall_fetch.py` | The Cloudflare range fetch path: both address families required, malformed responses never write, a missing token never reaches the network. |
| `test_snapshot_retention.py` | `snapshot-retention.py`: only matching snapshots of one droplet are candidates, fails closed if the new snapshot is not listed, retention minimum, name prefix validation. |
| `test_phpcs_legacy_encoding.py` | `check_phpcs_changed_lines.py` keeps legacy-encoded bytes intact when parsing diffs and scanning the base version. |
| `test_widget_visibility.py` | The `pausatf-widget-visibility.php` mu-plugin in the `wordpress` Ansible role: hides non-matching widgets, no longer evaluates stored PHP. |
| `test_archive_https.py` | The shared analytics loader and the tracked legacy pages under `content/`: every shipped tracking reference uses the HTTPS loader or a verified variant. |
| `backup/tests/test_manifest.py` | Manifest generation in the recovery backup, without production hosts. |

## Other CI checks on this directory

- ShellCheck at warning severity over `scripts/` (`ci.yml`, job `shellcheck`).
- `bash -n` on every `*.sh` file under `scripts/` (`ci.yml`, job `bash-syntax`).
- Markdown lint, and the repository pre-commit hooks (`.pre-commit-config.yaml`:
  shellcheck, gitleaks, detect-secrets, checkov and others).

## Conventions for new scripts

- Shell: `#!/usr/bin/env bash` or `#!/bin/bash`, `set -euo pipefail`, and clean
  ShellCheck output at warning severity.
- Python: standard library only unless there is a reason, a `--help` through
  `argparse`, and a `test_*.py` next to it so the CI command picks it up.
- Read credentials from the environment or the OS keychain at run time. Never
  put a literal credential on a command line or in a file.
- Destructive scripts take a dry-run default or an explicit `--apply`, as
  `firewall-maintenance.py` does.
- Document the script in [SCRIPTS.md](SCRIPTS.md) in the same change.

The scripts that were once planned here (deployment, monitoring, maintenance and
security helpers, a standalone WordPress backup script, Cloudflare purge scripts)
do not exist in this directory. Equivalents that do exist live elsewhere: the
WordPress backup and Cloudflare purge scripts are Ansible role templates under
`ansible/roles/backups`, `ansible/roles/backup-wordpress` and
`ansible/roles/cloudflare`; deployments run through GitHub Actions workflows
under `.github/workflows/`.
