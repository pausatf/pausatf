# Script inventory

Every script, test and unit file in `scripts/`, with what it does, how it is
invoked and what it needs. The directory overview is in [README.md](README.md).
Paths are relative to the repository root.

Two groups of files are documented in their own places and only summarized here:

- `scripts/backup/`: [backup/README.md](backup/README.md)
- `scripts/docker/`: [docker/README.md](docker/README.md)

## Python tools run by CI

Both use only the Python standard library and need Python 3.

### `snapshot-retention.py`

Reads the JSON from `doctl compute snapshot list --resource droplet --output json`
on stdin and prints the IDs of snapshots to delete, one per line. When a safety
check fails it writes an error to stderr, prints no IDs and exits 1, so a caller
using `set -euo pipefail` never deletes after a failure.

```bash
doctl compute snapshot list --resource droplet --output json > snapshots.json
python3 scripts/snapshot-retention.py --droplet-id 123456 \
  --completed-name prod-nightly-20260101-100000 < snapshots.json
```

| Option | Default | Meaning |
| --- | --- | --- |
| `--droplet-id` | required | Numeric ID. Only snapshots of this droplet are considered. |
| `--completed-name` | required | The snapshot that was just taken. It must appear in the list, otherwise nothing is pruned. |
| `--keep` | `7` | Number of newest matching snapshots to keep. Must be 1 or more. |
| `--name-prefix` | `prod-nightly` | Lowercase prefix. Only names matching `<prefix>-YYYYMMDD-HHMMSS` are eligible, so manual snapshots and volume snapshots are never selected. |

Callers: `.github/workflows/do-nightly-snapshot.yml` (daily 10:00 UTC, default
prefix and keep 7) and `.github/workflows/do-weekly-stage-snapshot.yml` (Sunday
11:00 UTC, `--name-prefix stage-weekly --keep 2`). Test:
`test_snapshot_retention.py`.

### `check_phpcs_changed_lines.py`

Runs PHP_CodeSniffer on the PHP files changed in a pull request and fails only
for violations on changed lines that did not already exist in the base version.
Legacy files with old encodings are handled by passing the original bytes to
PHPCS.

```bash
python3 scripts/check_phpcs_changed_lines.py --base origin/main --phpcs ~/.composer/vendor/bin/phpcs
```

Both options are required. The script must run from a checkout where
`git diff <base>...HEAD` works (the workflow uses `fetch-depth: 0`), and it uses
`phpcs.xml.dist` at the repository root. It ignores hunks under
`content/calendar/`. It prints `path:line: TYPE [source] message` for each new
violation and exits 1. It also exits non-zero when PHPCS fails without reporting
anything, so a crash is not read as a pass.

Caller: `.github/workflows/php-quality.yml`. Test:
`test_phpcs_legacy_encoding.py`.

## Shell scripts

### `terraform-wrapper.sh`

Runs `terraform` for one directory under `terraform/environments/` with an
action allow-list and a per-environment lock. Full reference:
[TERRAFORM-WRAPPER.md](TERRAFORM-WRAPPER.md).

```bash
scripts/terraform-wrapper.sh <production|staging|dev|cloudflare|github> <plan|apply|destroy|output|init|validate>
```

### `setup-managed-db-vars.sh`

Looks up the DigitalOcean Managed MySQL cluster for an environment with `doctl`
and writes its host, user and password into
`ansible/group_vars/<environment>/vault.yml` as `vault_managed_db_host`,
`vault_managed_db_user` and `vault_managed_db_password`, encrypted with
`ansible-vault`. If the vault file exists, the script decrypts it, replaces the
`vault_managed_db_*` lines and re-encrypts it, asking before overwriting existing
values.

```bash
./scripts/setup-managed-db-vars.sh [production|staging]    # default: production
```

Requirements: `DIGITALOCEAN_TOKEN` in the environment, `doctl`, `ansible-vault`
with a vault password configured, and an existing cluster named
`pausatf-prod-db` or `pausatf-stage-db`. It prints the password's last four
characters. The script passes the token to `doctl` with `-t`, which puts it in
the process argument list.

It finishes by printing the `migrate-to-managed-db.yml` command to run next. That
playbook targets the `production` group only and requires
`-e confirm_production=yes`; see [../ansible/ROLES.md](../ansible/ROLES.md).

### `local/sync-from-stage.sh`

Copies the staging database and uploads into the local OpenLiteSpeed Compose
stack (`scripts/docker/docker-compose.ols.yml`).

```bash
./scripts/local/sync-from-stage.sh root@stage.pausatf.org
```

It refuses to run if the `db-data` volume already exists and the running
database is not MariaDB 13.0, and points to `docker/README.md` for the backup
and upgrade steps. Otherwise it exports the remote database with `wp db export`
over SSH into `/tmp/pausatf-stage.sql.gz`, starts the stack, imports the dump
using credentials from inside the database container, and rsyncs
`wp-content/uploads/` to `./wp-uploads/` with `--delete`. It needs
`scripts/docker/.env` with the variables `docker-compose.ols.yml` reads, Docker Compose,
`rsync` and SSH access to the host. Review what `--delete` will remove in
`./wp-uploads/` before running it. After the import, update site URLs with WP-CLI
`search-replace`.

### `backup/`

| File | Purpose |
| --- | --- |
| `pausatf-db-backup.sh` | Encrypted database and legacy-data backup uploaded to Spaces. |
| `pausatf-recovery-backup.sh` | Wrapper that runs the database backup, then archives deployment files, host configuration and container images, and uploads a recovery manifest. |
| `pausatf-db-backup.service`, `pausatf-db-backup.timer` | systemd units; the timer fires daily at 09:20 UTC. |
| `pausatf-db-backup.service.d/recovery.conf` | Production drop-in that makes the service run `pausatf-recovery-backup`. |
| `tests/test_manifest.py` | Manifest generation test. |

See [backup/README.md](backup/README.md) for installation, retention and
restore verification. A merge does not install these files on the host.

### `mail/`

| File | Purpose |
|---|---|
| `pausatf-mail-netns.sh` | Recreates `pausatf-mail` when it is attached to a WordPress container that no longer exists. Mail runs with `network_mode: "service:wordpress"`, so recreating WordPress alone strands it: WordPress gets "connection refused" on port 25 and mail cannot resolve its relay. |
| `pausatf-mail-netns.service`, `pausatf-mail-netns.timer` | Run the check every two minutes. |

Installed on `pausatf-prod-v2` on 2026-11-21 as `/usr/local/sbin/pausatf-mail-netns`
(root, mode 0750) with the two units in `/etc/systemd/system`. A repository merge
does not install or update them.

### `docker/`

| File | Purpose |
| --- | --- |
| `docker-compose.yml` | nginx proxy in front of a WordPress container, with Prometheus, Grafana and node-exporter. |
| `docker-compose.ols.yml` | OpenLiteSpeed, MariaDB and phpMyAdmin stack used by `local/sync-from-stage.sh`. |
| `nginx/`, `wordpress/` | Dockerfiles and nginx configuration for the first stack. |
| `.env.example` | Template for the ignored `.env` (database and Grafana settings). |

See [docker/README.md](docker/README.md) for the MariaDB 13.0 upgrade procedure.

## Legacy content sync (removed)

The `legacy-content/` watcher, cron script and systemd unit, and
`ansible/playbooks/deploy-legacy-content-sync.yml`, were removed. They mirrored
`/var/www/legacy/public_html/` on the retired production host into a clone of
`pausatf/pausatf-legacy-content` and pushed the result, and the playbook
downloaded the scripts from `pausatf/pausatf-scripts`. Neither repository exists
any more. The legacy archive is the `content/` directory of this repository, and
the production legacy data is covered by the encrypted recovery backup
([backup/README.md](backup/README.md)). The copies of the sync files under
`content/` came over with the old repository; nothing installs them.

## Unit tests

Run with `python3 -m unittest discover -s scripts -p 'test_*.py'` and
`python3 -m unittest discover -s scripts/backup/tests -p 'test_*.py'` from the
repository root (these are the CI commands; see [README.md](README.md#tests)).

| File | Subject |
| --- | --- |
| `test_snapshot_retention.py` | `candidates()` in `snapshot-retention.py`. |
| `test_phpcs_legacy_encoding.py` | Diff parsing and baseline scan in `check_phpcs_changed_lines.py`. |
| `test_widget_visibility.py` | `ansible/roles/wordpress/files/mu-plugins/pausatf-widget-visibility.php`, executed with `php`. |
| `test_archive_https.py` | Analytics loader `content/data/pausatf-legacy-analytics.js` and the tracked legacy pages, executed with `node`. |
| `backup/tests/test_manifest.py` | Recovery manifest generation (run by a separate `discover -s scripts/backup/tests` step in CI). |

## Credentials

No script stores a secret. `terraform-wrapper.sh` warns when
`DIGITALOCEAN_TOKEN` or `CLOUDFLARE_API_TOKEN` is unset; `setup-managed-db-vars.sh`
reads `DIGITALOCEAN_TOKEN`. In CI the token is the `DO_TOKEN` secret. Locally,
fetch values from the OS keychain or a secret manager at run time instead of
typing them on a command line.
