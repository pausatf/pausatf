#!/usr/bin/env bash
set -euo pipefail

# Sync DB and uploads from stage to local OLS docker
# Usage: ./scripts/local/sync-from-stage.sh root@stage.pausatf.org

REMOTE="${1:-root@stage.pausatf.org}"
SITE_PATH="/var/www/html"
LOCAL_COMPOSE="scripts/docker/docker-compose.ols.yml"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required" >&2
  exit 1
fi

# Never let a data-sync helper implicitly migrate a persistent database.
compose=(docker compose --env-file scripts/docker/.env -f "$LOCAL_COMPOSE")
database_volume=$("${compose[@]}" config --format json | python3 -c \
  'import json,sys; print(json.load(sys.stdin)["volumes"]["db-data"]["name"])')
if docker volume inspect "$database_volume" >/dev/null 2>&1; then
  database_container=$("${compose[@]}" ps -q db)
  database_version=""
  if [ -n "$database_container" ]; then
    database_version=$(docker exec "$database_container" sh -c \
      'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb -uroot -Nse "SELECT VERSION()"')
  fi
  if [[ "$database_version" != 13.0.* ]]; then
    echo "Existing database is not running verified MariaDB 13.0. Follow scripts/docker/README.md backup/restore/upgrade first." >&2
    exit 1
  fi
fi

# Dump remote DB via WP-CLI
ssh -o StrictHostKeyChecking=accept-new "$REMOTE" \
  "wp db export --allow-root --path=/var/www/html - | gzip -c" > /tmp/pausatf-stage.sql.gz

# Start local stack
DOCKER_BUILDKIT=1 "${compose[@]}" up -d

# Import DB
# Expand credentials inside the service container, never in the invoking shell.
# shellcheck disable=SC2016
gzip -dc /tmp/pausatf-stage.sql.gz | "${compose[@]}" exec -T db sh -c \
  'MYSQL_PWD="$MARIADB_PASSWORD" exec mariadb -u"$MARIADB_USER" "$MARIADB_DATABASE"'

# Sync uploads
rsync -az --delete -e "ssh -o StrictHostKeyChecking=accept-new" "$REMOTE:$SITE_PATH/wp-content/uploads/" \
  ./wp-uploads/

echo "Done. Update local URLs if needed with WP-CLI search-replace."
