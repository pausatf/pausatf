#!/usr/bin/env bash
set -euo pipefail

# Sync legacy content from prod (ftp.pausatf.org) into backups/legacy/
# Requires SSH key at ~/.ssh/id_ed25519 with access to github-deploy@ftp.pausatf.org.
# CI sets up the key in backup-legacy.yml before calling this script.

REMOTE_HOST="${REMOTE_HOST:-ftp.pausatf.org}"
REMOTE_USER="${REMOTE_USER:-github-deploy}"
REMOTE_PATH="${REMOTE_PATH:-/var/www/legacy}"
LOCAL_DIR="${LOCAL_DIR:-backups/legacy}"
SSH_KEY="${SSH_KEY_FILE:-${HOME}/.ssh/id_ed25519}"

mkdir -p "$LOCAL_DIR"

# IdentitiesOnly prevents SSH from falling back to other key files
# (runner environments may have a broken id_rsa that causes libcrypto errors)
rsync -az --delete \
  -e "ssh -i ${SSH_KEY} -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new" \
  "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/" \
  "${LOCAL_DIR}/"

find "$LOCAL_DIR" -type f | wc -l
