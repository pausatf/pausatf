# backup-wordpress

Ansible role that deploys and schedules the WordPress backup script on Ubuntu hosts.

Manages: backup directory, the `/usr/local/sbin/backup-wordpress.sh` script, a root cron job, and logrotate config. Optionally offloads archives to DigitalOcean Spaces via s3cmd.

## Variables

| Variable | Default | Notes |
|---|---|---|
| `backup_dir` | `/var/backups/wordpress` | Local archive destination |
| `backup_retention_days` | `3` | Delete archives older than N days |
| `backup_retention_count` | `3` | Keep at most N archives per type |
| `backup_disk_threshold_pct` | `80` | Skip backup if disk exceeds this % |
| `backup_db_name` | `wordpress` | MySQL database name |
| `backup_db_cred_file` | `/root/.my-backup-wordpress.cnf` | MySQL credentials file (must exist pre-run) |
| `backup_log_file` | `/var/log/wordpress-backup.log` | Log path (also managed by logrotate) |
| `backup_cron_hour` | `2` | Cron hour (root crontab) |
| `backup_cron_minute` | `0` | Cron minute |
| `backup_webroot` | `/var/www/html` | WordPress document root |
| `backup_legacy_data_path` | `/var/www/legacy/public_html/data` | Legacy data dir; set to `""` to skip |
| `backup_do_spaces_bucket` | `""` | DO Spaces bucket name; leave empty to disable offload |
| `backup_do_spaces_endpoint` | `sfo2.digitaloceanspaces.com` | Spaces endpoint |
| `backup_do_spaces_prefix` | `pausatf-backups` | Key prefix inside the bucket |
| `backup_do_spaces_remote_retention_days` | `30` | Prune remote archives older than N days |

## Usage

```yaml
- hosts: wordpress
  roles:
    - role: backup-wordpress
      vars:
        backup_do_spaces_bucket: my-bucket
```

The DB credentials file must exist before this role runs. Create it with:

```ini
[client]
user     = backup
password = secret
```
