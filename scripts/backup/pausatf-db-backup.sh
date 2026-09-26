#!/usr/bin/env bash
# Encrypted offsite backup of the managed production DB and legacy data.
# The age recipient is public; the corresponding private key is kept off-host.
set -euo pipefail

readonly RECIP="${BACKUP_AGE_RECIPIENT:-age1et7k772jfxd6ca5rf8395s72y0aqtdvxqf48rer8s7ssg0r6pshsgev0hw}"
readonly DEST="${BACKUP_DEST:-s3://pausatf/backups/prod}"
readonly KEEP="${BACKUP_KEEP:-14}"
readonly BACKUP_DIR="${BACKUP_DIR:-/var/backups/pausatf}"
readonly LEGACY_DIR="${LEGACY_DIR:-/home/somethingwithproof/pausatf-deployment/legacy-data}"
readonly S3CMD_CONFIG="${S3CMD_CONFIG:-/root/.s3cfg}"

mkdir -p "$BACKUP_DIR"
umask 077
DB_ENV_FILE=$(mktemp /run/pausatf-db-backup-env.XXXXXX)
trap 'rm -f -- "$DB_ENV_FILE"' EXIT

if [ ! -r "$S3CMD_CONFIG" ]; then
  echo "FAIL: s3cmd credentials/config are not readable: $S3CMD_CONFIG" >&2
  exit 1
fi

if [ ! -d "$LEGACY_DIR" ] || [ -z "$(find "$LEGACY_DIR" -mindepth 1 -print -quit)" ]; then
  echo "FAIL: legacy data directory is missing or empty: $LEGACY_DIR" >&2
  exit 1
fi

mapfile -t WORDPRESS_CONTAINERS < <(docker ps --filter label=com.docker.compose.project=pausatf-prod --filter label=com.docker.compose.service=wordpress --format '{{.ID}}')
if [ "${#WORDPRESS_CONTAINERS[@]}" -ne 1 ]; then
  echo "FAIL: expected one production WordPress container; found ${#WORDPRESS_CONTAINERS[@]}" >&2
  exit 1
fi
readonly WORDPRESS_CONTAINER="${WORDPRESS_CONTAINERS[0]}"
DB_HOST_PORT=$(docker exec "$WORDPRESS_CONTAINER" printenv WORDPRESS_DB_HOST)
DB_NAME=$(docker exec "$WORDPRESS_CONTAINER" printenv WORDPRESS_DB_NAME)
DB_USER=$(docker exec "$WORDPRESS_CONTAINER" printenv WORDPRESS_DB_USER)
DB_PASSWORD=$(docker exec "$WORDPRESS_CONTAINER" printenv WORDPRESS_DB_PASSWORD)
printf 'MYSQL_PWD=%s\n' "$DB_PASSWORD" > "$DB_ENV_FILE"
unset DB_PASSWORD
DB_HOST=${DB_HOST_PORT%%:*}
DB_PORT=${DB_HOST_PORT##*:}
[ "$DB_PORT" = "$DB_HOST_PORT" ] && DB_PORT=3306

STAMP=$(date -u +%Y%m%dT%H%M%SZ)
DB_OUT="$BACKUP_DIR/prod-db-$STAMP.sql.gz.age"
LEGACY_OUT="$BACKUP_DIR/prod-legacy-$STAMP.tar.gz.age"

docker run --rm --env-file "$DB_ENV_FILE" mysql:8 \
  mysqldump --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
  --ssl-mode=REQUIRED --single-transaction --quick --set-gtid-purged=OFF \
  --no-tablespaces --databases "$DB_NAME" \
  | gzip -9 | age -r "$RECIP" > "$DB_OUT"

DB_SIZE=$(stat -c%s "$DB_OUT")
[ "$DB_SIZE" -lt 1024 ] && { echo "FAIL: DB backup too small ($DB_SIZE bytes)" >&2; exit 1; }

tar -C "$LEGACY_DIR" -czf - . | age -r "$RECIP" > "$LEGACY_OUT"
LEGACY_SIZE=$(stat -c%s "$LEGACY_OUT")
[ "$LEGACY_SIZE" -lt 1024 ] && { echo "FAIL: legacy backup too small ($LEGACY_SIZE bytes)" >&2; exit 1; }

s3cmd --config="$S3CMD_CONFIG" put --acl-private "$DB_OUT" "$DEST/prod-db-$STAMP.sql.gz.age" >/dev/null
s3cmd --config="$S3CMD_CONFIG" put --acl-private "$LEGACY_OUT" "$DEST/prod-legacy-$STAMP.tar.gz.age" >/dev/null

# Keep the newest three local and BACKUP_KEEP remote copies of each artifact type.
# Filenames are generated from fixed prefixes and UTC timestamps, so whitespace is impossible.
# shellcheck disable=SC2012
ls -1t "$BACKUP_DIR"/prod-db-*.sql.gz.age 2>/dev/null | tail -n +4 | xargs -r rm -f
# shellcheck disable=SC2012
ls -1t "$BACKUP_DIR"/prod-legacy-*.tar.gz.age 2>/dev/null | tail -n +4 | xargs -r rm -f
for artifact_prefix in 'prod-db-' 'prod-legacy-'; do
  mapfile -t old_objects < <(
    s3cmd --config="$S3CMD_CONFIG" ls "$DEST/" \
      | awk -v prefix="$artifact_prefix" '$4 ~ ("/" prefix) {print $4}' \
      | sort \
      | head -n "-$KEEP" 2>/dev/null || true
  )
  for object in "${old_objects[@]:-}"; do
    [ -n "$object" ] && s3cmd --config="$S3CMD_CONFIG" del "$object" >/dev/null
  done
done

echo "OK $STAMP db_size=$DB_SIZE legacy_size=$LEGACY_SIZE uploaded=$DEST/"
