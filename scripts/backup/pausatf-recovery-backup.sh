#!/usr/bin/env bash
# Supplements the existing encrypted DB/legacy backup with code and configuration.
set -euo pipefail
umask 077
exec 9>/run/lock/pausatf-recovery-backup.lock
flock -n 9 || exit 1
readonly ROOT=/home/somethingwithproof/pausatf-deployment
readonly RECIPIENTS=/etc/pausatf-backup-recipients.txt
KEYSET=$(sha256sum "$RECIPIENTS" | cut -c1-16)
readonly DEST=s3://pausatf/backups/recovery
readonly STATE=/var/lib/pausatf-ops
mkdir -p "$STATE"
install -d -o root -g root -m 0700 /var/backups/pausatf
work=$(mktemp -d /var/backups/pausatf/recovery.XXXXXX)
trap 'rm -rf -- "$work"' EXIT
/usr/local/sbin/pausatf-db-backup.sh
stamp=$(date -u +%Y%m%dT%H%M%SZ)
# Uploads are in versioned Spaces, not this archive. Do not traverse their FUSE mount.
mkdir "$work/deployment"
rsync -a --exclude='/wp-content/uploads' --exclude='/logs' --exclude='/legacy-data' \
  --exclude='/.git' --exclude='/wp-content/cache' "$ROOT/" "$work/deployment/"
tar -C "$work/deployment" -czf - . | age -R "$RECIPIENTS" > "$work/deployment.tar.gz.age"
# Credentials remain encrypted and private in object storage.
tar -C / -czf - etc/ssl/pausatf etc/pausatf etc/apparmor.d/pausatf-wordpress \
  etc/docker/daemon.json etc/cron.d/pausatf-wpcron \
  etc/systemd/system/pausatf-db-backup.service etc/systemd/system/pausatf-db-backup.timer etc/systemd/system/pausatf-db-backup.service.d \
  usr/local/sbin/pausatf-db-backup.sh usr/local/sbin/pausatf-recovery-backup \
  root/.s3cfg | age -R "$RECIPIENTS" > "$work/host-config.tar.gz.age"
for container in pausatf-wordpress pausatf-mail; do
  image=$(docker inspect --format '{{.Image}}' "$container")
  filename="image-${image#sha256:}.tar.gz.age"
  if ! s3cmd info "$DEST/images/$KEYSET/$filename" >/dev/null 2>&1; then
    docker image save "$image" | gzip -1 | age -R "$RECIPIENTS" > "$work/$filename"
    s3cmd put --acl-private "$work/$filename" "$DEST/images/$KEYSET/$filename" >/dev/null
    rm -f -- "$work/$filename"
  fi
  printf '%s %s %s\n' "$container" "$image" "$DEST/images/$KEYSET/$filename" >> "$work/images.txt"
done
for artifact in deployment.tar.gz.age host-config.tar.gz.age; do
  test -s "$work/$artifact"
  s3cmd put --acl-private "$work/$artifact" "$DEST/sets/$stamp/$artifact" >/dev/null
  s3cmd info "$DEST/sets/$stamp/$artifact" >/dev/null
done
export stamp DEST STATE work
python3 - <<'PY'
import hashlib,json,os,time
from pathlib import Path
root=Path('/var/backups/pausatf')
artifacts={}
for kind,pattern in [('database','prod-db-*.sql.gz.age'),('legacy','prod-legacy-*.tar.gz.age')]:
    p=max(root.glob(pattern),key=lambda p:p.stat().st_mtime)
    if time.time()-p.stat().st_mtime>3600: raise SystemExit('Database or legacy artifact stale')
    artifacts[kind]={'uri':'s3://pausatf/backups/prod/'+p.name,'bytes':p.stat().st_size,'sha256':hashlib.file_digest(p.open('rb'),'sha256').hexdigest()}
for name in ['deployment.tar.gz.age','host-config.tar.gz.age']:
    p=Path(os.environ['work'])/name
    artifacts[name]={'uri':os.environ['DEST']+'/sets/'+os.environ['stamp']+'/'+name,'bytes':p.stat().st_size,'sha256':hashlib.file_digest(p.open('rb'),'sha256').hexdigest()}
manifest={'completed_at':time.time(),'artifacts':artifacts,'images':(Path(os.environ['work'])/'images.txt').read_text().splitlines(),'uploads':'s3://pausatf-static/uploads/','uploads_recovery':'Versioning enabled; noncurrent versions retained 60 days. Not an independent copy.'}
(Path(os.environ['work'])/'manifest.json').write_text(json.dumps(manifest,indent=2))
PY
s3cmd put --acl-private "$work/manifest.json" "$DEST/sets/$stamp/manifest.json" >/dev/null
# Publish freshness only after every artifact and the manifest reached offsite storage.
install -m 600 "$work/manifest.json" "$STATE/backup-success.json.tmp"
mv "$STATE/backup-success.json.tmp" "$STATE/backup-success.json"
echo "Complete recovery backup uploaded: $stamp"
