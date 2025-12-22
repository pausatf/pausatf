#!/bin/bash
# Cloudflare Cache Purge Script - Fixed Version
set -euo pipefail

ZONE_ID="${CF_ZONE_ID:-your-cloudflare-zone-id}"
API_TOKEN="${CF_API_TOKEN:-your-cloudflare-api-token}"

log() {
    echo "[$(date +%Y-%m-%d\ %H:%M:%S)] $*" | tee -a /var/log/cloudflare-purge.log
}

if [ -z "${1:-}" ]; then
    PAYLOAD="{\"purge_everything\":true}"
    LOG_MSG="Full cache purge"
else
    URL="$1"
    PAYLOAD="{\"files\":[\"$URL\"]}"
    LOG_MSG="Targeted purge: $URL"
fi

log "$LOG_MSG"

curl -s -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/purge_cache" \
    -H "Authorization: Bearer ${API_TOKEN}" \
    -H "Content-Type: application/json" \
    --data "$PAYLOAD" | tee -a /var/log/cloudflare-purge.log

echo ""
log "Purge complete"
