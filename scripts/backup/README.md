# Production backup

The production droplet runs a daily systemd backup timer because GitHub-hosted runners cannot reach the origin through
the Cloudflare Access boundary. The timer invokes `pausatf-db-backup.service`, whose production drop-in overrides the
service command to run `pausatf-recovery-backup`. That wrapper first runs the encrypted database and legacy-data
backup, then archives deployment files, selected host configuration, and container images. It uploads the archives and
a recovery manifest to private DigitalOcean Spaces objects under `s3://pausatf/backups/recovery/`.

The database script supplies an invocation-specific receipt containing the exact uploaded database and legacy
objects, sizes, and hashes. The wrapper uses that receipt even when image archiving takes over an hour; it never
selects artifacts by newest modification time. Content-addressed recovery images have no current-version expiration:
new recovery sets can reference an old image. Remove unreferenced images only after checking all retained manifests.
The recovery wrapper disables the standalone database script's count-based remote pruning: database objects and
recovery sets use the 45-day Spaces lifecycle, so newer retained sets keep their referenced database objects.

The database and legacy artifacts are uploaded under `s3://pausatf/backups/prod/`. Their age private key stays
off-host. The recovery wrapper uses the public recipients from `/etc/pausatf-backup-recipients.txt`; keep that file
root-owned and provisioned out of band. The host-configuration archive includes sensitive files such as TLS private
keys and `/root/.s3cfg`, so it must remain age-encrypted and private. Never commit credentials, private keys, or
decrypted archives.

`/root/.s3cfg` must be a regular root-owned file with mode `0600`. It is provisioned out of band. The database backup
script passes it explicitly to `s3cmd`; the recovery wrapper runs as root and uses `s3cmd`'s default root config.

## Tracked service files

The repository contains the database backup script and systemd unit, the recovery wrapper, and the production service
drop-in. The drop-in is needed because the base unit runs only the database/legacy backup; production runs the full
recovery wrapper instead.

To install or refresh the managed files on the production droplet:

```bash
sudo install -o root -g root -m 0700 scripts/backup/pausatf-db-backup.sh \
  /usr/local/sbin/pausatf-db-backup.sh
sudo install -o root -g root -m 0700 scripts/backup/pausatf-recovery-backup.sh \
  /usr/local/sbin/pausatf-recovery-backup
sudo install -o root -g root -m 0644 scripts/backup/pausatf-db-backup.service \
  /etc/systemd/system/pausatf-db-backup.service
sudo install -o root -g root -m 0644 scripts/backup/pausatf-db-backup.timer \
  /etc/systemd/system/pausatf-db-backup.timer
sudo install -d -o root -g root -m 0755 \
  /etc/systemd/system/pausatf-db-backup.service.d
sudo install -o root -g root -m 0644 \
  scripts/backup/pausatf-db-backup.service.d/recovery.conf \
  /etc/systemd/system/pausatf-db-backup.service.d/recovery.conf
sudo systemctl daemon-reload
sudo systemctl enable pausatf-db-backup.timer
```

Provision `/etc/pausatf-backup-recipients.txt` and `/root/.s3cfg` out of band before starting the service. Confirm the
timer and service after deployment:

```bash
sudo systemctl start pausatf-db-backup.timer
sudo systemctl list-timers pausatf-db-backup.timer
sudo systemctl start pausatf-db-backup.service
sudo systemctl status pausatf-db-backup.service
sudo s3cmd ls s3://pausatf/backups/prod/
sudo s3cmd ls s3://pausatf/backups/recovery/sets/
sudo cat /var/lib/pausatf-ops/backup-success.json
```

The wrapper publishes `backup-success.json` only after it has uploaded the artifacts and manifest and confirmed the
deployment and host-configuration objects are readable through Spaces metadata requests. Database/legacy uploads,
new image uploads, and the manifest rely on successful upload commands; the wrapper does not independently
verify every referenced object with a metadata request. The manifest records artifact sizes and SHA-256 hashes. Check
the service journal and latest manifest after every backup code change. Keep the age private key off-host and test
restoration periodically; a backup is not proven recoverable until decrypted and restored in an isolated environment.
