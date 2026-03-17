#!/usr/bin/env bash
# Run wp-cron only if this server is the active production server.
# The marker file /etc/pausatf/is-active-web is managed by Ansible
# and only placed on the server that Cloudflare DNS points to.
# Prevents split-brain cron when multiple servers share a managed DB.
set -euo pipefail

MARKER="/etc/pausatf/is-active-web"
WP_PATH="/var/www/html"

if [ ! -f "$MARKER" ]; then
    exit 0
fi

/usr/local/bin/wp cron event run --due-now --path="$WP_PATH" --allow-root --quiet 2>/dev/null || true
