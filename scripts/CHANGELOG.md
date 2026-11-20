# Scripts changelog

Notable changes to `scripts/`, reconstructed from the git history of this
repository (`git log -- scripts`). Routine dependency bumps under
`scripts/docker/` are omitted. Pull request numbers refer to
`pausatf/pausatf`.

An earlier version of this file listed planned directories (`deployment/`,
`monitoring/`, `maintenance/`, `security/`, `cloudflare/`, `database/`) and
scripts that were never added. They are not part of the repository, and the
file no longer lists them.

## 2026-11

- Legacy archive analytics must load over HTTPS; `test_archive_https.py` checks
  the shipped pages, and `test_phpcs_legacy_encoding.py` was added in the same
  change (#259).
- Removed the stored-expression evaluator from the widget visibility mu-plugin;
  `test_widget_visibility.py` covers the replacement (#258).
- GitHub Actions workflows hardened (pinned SHAs, least-privilege permissions);
  the workflows that call the scripts here were updated with them.

## 2026-10

- Local OpenLiteSpeed stack moved to MariaDB 13.0 with documented backup,
  restore and readiness gates; `local/sync-from-stage.sh` refuses to run against
  a database that is not 13.0 (#221).
- Production backup tracked as complete encrypted recovery sets: database,
  legacy data, deployment files, host configuration and images, with a manifest
  and `backup/tests/test_manifest.py` (#217).

## 2026-09

- Weekly staging snapshots added; `snapshot-retention.py` gained
  `--name-prefix` and `--keep` so the staging job keeps its own two snapshots
  (#203).
- `terraform-wrapper.sh` sets the Spaces signing region to `us-east-1` (#209).
- CI repaired and cloud maintenance safeguarded (#167): added
  `firewall-maintenance.py`, `snapshot-retention.py`, and the first
  `test_firewall_maintenance.py`, `test_firewall_fetch.py` and
  `test_snapshot_retention.py`, run by
  `python3 -m unittest discover -s scripts -p 'test_*.py'`.
- `check_phpcs_changed_lines.py` added with the PHPCS CI repair.

## 2026-02

- Added `setup-managed-db-vars.sh` to put Managed MySQL credentials into an
  Ansible vault file.
- `terraform-wrapper.sh` gained the per-environment lock and the `cloudflare`
  and `github` environments (#79).

## 2025-12

- `scripts/` imported from the former scripts repository: `terraform-wrapper.sh`,
  the `legacy-content/` sync watcher, cron script and systemd unit, and their
  documentation.
- Local OpenLiteSpeed stack and `local/sync-from-stage.sh` added (2025-12-27).
