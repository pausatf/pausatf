# Local OpenLiteSpeed database upgrade

The OpenLiteSpeed Compose stack uses a persistent `db-data` volume. Before the
first MariaDB 13.0 startup, take and verify a logical backup with the running
11.8 image and stop the stack. Keep a separate copy of the stopped database
volume for rollback; do not start 11.8 against a volume already upgraded by 13.0.

The stack sets `MARIADB_AUTO_UPGRADE=1` so the official image runs the required
system-table upgrade when an existing database is opened. After startup, inspect
the database logs and verify WordPress reads and writes before removing backups.
This upgrades only the local development stack. The image healthcheck requires
network connections and initialized InnoDB before Compose starts the application.
A socket-only SQL check can succeed against the temporary initialization server
before WordPress can connect; use the TCP checks below.

## Back up and prove restoration before starting 13.0

Run these Bash commands from the repository root while the existing database is
still running 11.8. Provision the ignored `scripts/docker/.env` first. Keep the
backup directory outside the repository: the dump contains application data and
must never be committed. Keep application writers stopped through the upgrade.

If the existing 11.8 database is stopped, start only that service with an
explicit 11.8 override first; do not run the 13.0 Compose file directly:

```bash
umask 077
bootstrap_override=$(mktemp /tmp/pausatf-ols-11.8.XXXXXX.yml)
cat > "$bootstrap_override" <<'YAML'
services:
  db:
    image: mariadb:11.8
    healthcheck:
      test: ["CMD-SHELL", 'MYSQL_PWD="$$MARIADB_ROOT_PASSWORD" mariadb -h127.0.0.1 -uroot -e "SELECT 1"']
    environment:
      MARIADB_AUTO_UPGRADE: ""
YAML
docker compose --env-file scripts/docker/.env -f scripts/docker/docker-compose.ols.yml \
  -f "$bootstrap_override" up -d --wait --wait-timeout 180 --no-deps db
rm -f "$bootstrap_override"
```

The 11.8 overrides use an authenticated TCP check because older volumes may lack
the official image healthcheck account. Wait for database readiness, then verify `SELECT VERSION()` reports 11.8 before
the backup steps below. The sync helper refuses any existing volume whose
database is not running verified 13.0; perform this documented upgrade first.

```bash
set -euo pipefail
umask 077
compose=(docker compose --env-file scripts/docker/.env -f scripts/docker/docker-compose.ols.yml)
backup_dir="$HOME/Backups/pausatf-ols/$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$backup_dir"
database_container=$("${compose[@]}" ps -q db)
test -n "$database_container"
database_volume=$(docker inspect "$database_container" --format \
  '{{range .Mounts}}{{if eq .Destination "/var/lib/mysql"}}{{.Name}}{{end}}{{end}}')
test -n "$database_volume"
db_version=$(docker exec "$database_container" sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -h127.0.0.1 -uroot -Nse "SELECT VERSION()"')
[[ "$db_version" == 11.8.* ]]
"${compose[@]}" stop web phpmyadmin
docker exec "$database_container" sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb-dump -uroot --all-databases \
   --single-transaction --routines --events --triggers' | gzip > "$backup_dir/all-databases.sql.gz"
test -s "$backup_dir/all-databases.sql.gz"
gzip -t "$backup_dir/all-databases.sql.gz"
"${compose[@]}" stop db
docker run --rm --user 0 --entrypoint sh \
  -v "$database_volume:/source:ro" -v "$backup_dir:/backup" mariadb:11.8 \
  -c 'tar --numeric-owner -C /source -czf /backup/db-data-11.8.tar.gz .'
test -s "$backup_dir/db-data-11.8.tar.gz"
gzip -t "$backup_dir/db-data-11.8.tar.gz"
shasum -a 256 "$backup_dir/all-databases.sql.gz" "$backup_dir/db-data-11.8.tar.gz" \
  > "$backup_dir/SHA256SUMS"
shasum -a 256 -c "$backup_dir/SHA256SUMS"
```

Restore the logical dump into a disposable 11.8 database with no published port.
The environment file supplies credentials at runtime; the commands do not print
them. A zero exit from import and `mariadb-check` is required. Also verify the
expected WordPress tables and representative row counts in the restored database.

```bash
restore_name="pausatf-ols-restore-$$"
restore_volume="pausatf-ols-restore-$$"
docker volume create "$restore_volume"
docker run --rm -d --name "$restore_name" --env-file scripts/docker/.env \
  -v "$restore_volume:/var/lib/mysql" mariadb:11.8
ready=0
for _attempt in {1..60}; do
  if docker exec "$restore_name" sh -c \
    'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -h127.0.0.1 -uroot -e "SELECT 1"' >/dev/null 2>&1; then
    ready=1
    break
  fi
  sleep 2
done
test "$ready" = 1
gzip -dc "$backup_dir/all-databases.sql.gz" | docker exec -i "$restore_name" sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" exec mariadb -h127.0.0.1 -uroot'
docker exec "$restore_name" sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-check -uroot --all-databases'
docker exec "$restore_name" sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -h127.0.0.1 -uroot -e \
   "SELECT TABLE_SCHEMA,TABLE_NAME,TABLE_ROWS FROM information_schema.tables \
    WHERE TABLE_SCHEMA NOT IN (\"mysql\",\"sys\",\"information_schema\",\"performance_schema\")"'
docker stop "$restore_name"
docker volume rm "$restore_volume"
```

Do not start 13.0 until both the logical restore drill and physical-volume rehearsal below pass, including
application-data checks. Then
start only the database, inspect its upgrade log and version, and start the
application after the database checks succeed:

```bash
"${compose[@]}" up -d --wait --wait-timeout 180 db
"${compose[@]}" logs db
"${compose[@]}" exec -T db sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -h127.0.0.1 -uroot -Nse "SELECT VERSION()"'
"${compose[@]}" exec -T db sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-check -uroot --all-databases'
"${compose[@]}" up -d web phpmyadmin
```

Require a 13.0 version, successful table checks, and successful WordPress reads and
writes. Keep the backup until those checks pass.

## Roll back using a new volume

Stop all application/database containers. Restore the stopped 11.8 volume copy
into a **new** volume and override the image and volume mapping. Never mount the
upgraded 13.0 volume under 11.8. Rehearse this procedure before the
upgrade by starting only `db`, checking its restored version and tables, then
stopping it; retain the original volume for the actual upgrade.

```bash
"${compose[@]}" stop web phpmyadmin db
shasum -a 256 -c "$backup_dir/SHA256SUMS"
rollback_volume="pausatf-ols-rollback-$(date -u +%Y%m%dT%H%M%SZ)"
docker volume create "$rollback_volume"
docker run --rm --user 0 --entrypoint sh \
  -v "$rollback_volume:/restore" -v "$backup_dir:/backup:ro" mariadb:11.8 \
  -c 'tar --numeric-owner -C /restore -xzf /backup/db-data-11.8.tar.gz'
cat > "$backup_dir/rollback.yml" <<YAML
services:
  db:
    image: mariadb:11.8
    healthcheck:
      test: ["CMD-SHELL", 'MYSQL_PWD="$$MARIADB_ROOT_PASSWORD" mariadb -h127.0.0.1 -uroot -e "SELECT 1"']
    environment:
      MARIADB_AUTO_UPGRADE: ""
volumes:
  db-data:
    external: true
    name: "$rollback_volume"
YAML
"${compose[@]}" -f "$backup_dir/rollback.yml" up -d --wait --wait-timeout 180 db
"${compose[@]}" -f "$backup_dir/rollback.yml" logs db
"${compose[@]}" -f "$backup_dir/rollback.yml" exec -T db sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -h127.0.0.1 -uroot -Nse "SELECT VERSION()"'
"${compose[@]}" -f "$backup_dir/rollback.yml" exec -T db sh -c \
  'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-check -uroot --all-databases'
"${compose[@]}" -f "$backup_dir/rollback.yml" up -d web phpmyadmin
```

Require an 11.8 version, successful table checks, and the expected WordPress data
before reopening writes. During a rehearsal, stop the rollback database and keep
writers stopped; omit the final application startup and return to the original
Compose mapping only when ready for the upgrade. Use the override on subsequent
rollback starts so Compose cannot silently reopen the upgraded volume.

See the [official image environment variables](https://mariadb.com/docs/server/server-management/automated-mariadb-deployment-and-administration/docker-and-mariadb/mariadb-server-docker-official-image-environment-variables).

See the [official image healthcheck documentation](https://mariadb.com/docs/server/server-management/automated-mariadb-deployment-and-administration/docker-and-mariadb/using-healthcheck-sh).
