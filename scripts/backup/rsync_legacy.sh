#!/usr/bin/env bash
set -euo pipefail

# Sync legacy content from prod (ftp.pausatf.org) into backups/legacy/
# Requires SSH key (provided by CI) with access to root@ftp.pausatf.org.

REMOTE_HOST="${REMOTE_HOST:-ftp.pausatf.org}"
REMOTE_USER="${REMOTE_USER:-root}"
REMOTE_PATH="${REMOTE_PATH:-/var/www/legacy}"
LOCAL_DIR="${LOCAL_DIR:-backups/legacy}"

mkdir -p "$LOCAL_DIR"

# Strict host key checking is disabled unless KNOWN_HOSTS is preseeded by CI
rsync -az --delete -e "ssh -o StrictHostKeyChecking=no" \
  "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/" \
  "${LOCAL_DIR}/"

# Basic sanity
find "$LOCAL_DIR" -type f | wc -l
