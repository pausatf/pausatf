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

# Dump remote DB via WP-CLI
ssh -o StrictHostKeyChecking=no "$REMOTE" \
  "wp db export - | gzip -c" > /tmp/pausatf-stage.sql.gz

# Start local stack
DOCKER_BUILDKIT=1 docker compose -f "$LOCAL_COMPOSE" up -d

# Import DB
zcat /tmp/pausatf-stage.sql.gz | docker exec -i $(docker ps -qf name=db) \
  mysql -u"${WORDPRESS_DB_USER:-wp}" -p"${WORDPRESS_DB_PASSWORD:-wp_password}" "${WORDPRESS_DB_NAME:-wordpress}"

# Sync uploads
rsync -az --delete -e "ssh -o StrictHostKeyChecking=no" "$REMOTE:$SITE_PATH/wp-content/uploads/" \
  ./wp-uploads/

echo "Done. Update local URLs if needed with WP-CLI search-replace."
