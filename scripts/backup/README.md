# Production backup

Production backups run on the droplet because GitHub-hosted runners cannot reach the origin through its
Cloudflare Access boundary. The systemd timer creates age-encrypted database and legacy-data artifacts and uploads
them privately to DigitalOcean Spaces.

Provision `/root/.s3cfg` out of band on the droplet with a least-privilege Spaces key. Keep it owned by root with
mode `0600`; the service explicitly passes this file to every `s3cmd` invocation. It is included only in the
age-encrypted host-configuration recovery archive. Do not commit the key or place it in GitHub workflow logs.

Install or refresh the managed files from the repository:

```bash
sudo install -o root -g root -m 0700 scripts/backup/pausatf-db-backup.sh \
  /usr/local/sbin/pausatf-db-backup.sh
sudo install -o root -g root -m 0644 scripts/backup/pausatf-db-backup.service \
  /etc/systemd/system/pausatf-db-backup.service
sudo install -o root -g root -m 0644 scripts/backup/pausatf-db-backup.timer \
  /etc/systemd/system/pausatf-db-backup.timer
sudo systemctl daemon-reload
sudo systemctl enable --now pausatf-db-backup.timer
```

Run and verify a backup after any script change:

```bash
sudo systemctl start pausatf-db-backup.service
sudo systemctl status pausatf-db-backup.service
sudo s3cmd ls s3://pausatf/backups/prod/
```

The age private key stays off-host. The root-only Spaces config is provisioned on the droplet as described above;
its encrypted recovery copy permits restoration if the host is lost. Restore operations require the off-host age
private key. Keep the most recent three artifacts locally and `BACKUP_KEEP` remote artifacts per database/legacy
type; the recovery wrapper separately writes a manifest only after its backup set has uploaded and verified.
