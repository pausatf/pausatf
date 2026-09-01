# Production backup

Production backups run on the droplet because GitHub-hosted runners cannot reach the origin through its
Cloudflare Access boundary. The systemd timer creates age-encrypted database and legacy-data artifacts and uploads
them privately to DigitalOcean Spaces.

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

The age private key and object-storage credentials must not be stored in this repository or on the droplet.
