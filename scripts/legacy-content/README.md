# Legacy Content Sync Scripts

Automated scripts for syncing PAUSATF legacy static content to GitHub.

## Overview

These scripts keep the [pausatf-legacy-content](https://github.com/pausatf/pausatf-legacy-content) GitHub repository in sync with the production server's `/var/www/legacy/public_html/` directory.

## Scripts

### sync-legacy-content-watcher.sh

Real-time file watcher using inotify that automatically detects changes and syncs to GitHub.

**Features:**
- Monitors file system events (create, modify, delete, move)
- 30-second debounce to prevent excessive commits
- Automatic exclusion of metadata files
- Comprehensive error handling and logging

**Requirements:**
- inotify-tools package
- Git repository cloned to `/opt/pausatf-legacy-content`
- GitHub SSH access configured

**Deployment:**
```bash
cp sync-legacy-content-watcher.sh /usr/local/bin/
cp sync-legacy-content-watcher.service /etc/systemd/system/
chmod +x /usr/local/bin/sync-legacy-content-watcher.sh
systemctl enable sync-legacy-content-watcher
systemctl start sync-legacy-content-watcher
```

### sync-legacy-content-cron.sh

Scheduled sync script designed to run via cron on a regular interval.

**Features:**
- Lock file prevents concurrent runs
- Comprehensive change detection and reporting
- Safe for frequent execution (checks for changes before committing)
- Full logging of all operations

**Deployment:**
```bash
cp sync-legacy-content-cron.sh /usr/local/bin/
chmod +x /usr/local/bin/sync-legacy-content-cron.sh

# Add to crontab (every 6 hours)
crontab -e
# Add: 0 */6 * * * /usr/local/bin/sync-legacy-content-cron.sh >> /var/log/legacy-content-sync-cron.log 2>&1
```

### sync-legacy-content-watcher.service

Systemd service file for running the watcher as a system service.

**Features:**
- Automatic restart on failure
- Security hardening (PrivateTmp, NoNewPrivileges)
- Centralized logging

## Documentation

See [SYNC-SETUP.md](SYNC-SETUP.md) for complete setup instructions, configuration options, troubleshooting, and maintenance procedures.

## Deployment Methods

### Option 1: Real-time Sync (Watcher)

Best for: Servers where content changes frequently

```bash
# Deploy watcher service
systemctl enable sync-legacy-content-watcher
systemctl start sync-legacy-content-watcher
```

### Option 2: Scheduled Sync (Cron)

Best for: Servers where content changes infrequently

```bash
# Add cron job for daily sync at 2 AM
0 2 * * * /usr/local/bin/sync-legacy-content-cron.sh >> /var/log/legacy-content-sync-cron.log 2>&1
```

### Option 3: Both (Recommended)

Use watcher for real-time updates and cron as a backup.

## Monitoring

### Check Watcher Status
```bash
systemctl status sync-legacy-content-watcher
journalctl -u sync-legacy-content-watcher -f
tail -f /var/log/legacy-content-sync.log
```

### Check Cron Status
```bash
tail -f /var/log/legacy-content-sync-cron.log
```

## Integration with IaC

These scripts are part of the PAUSATF Infrastructure as Code ecosystem:

- **Scripts Repository**: https://github.com/pausatf/pausatf-scripts (this repo)
- **Legacy Content Repository**: https://github.com/pausatf/pausatf-legacy-content
- **Ansible Playbooks**: See `pausatf-ansible` for automated deployment
- **Documentation**: See `pausatf-infrastructure-docs`

## Related Resources

- [Legacy Content Repository](https://github.com/pausatf/pausatf-legacy-content)
- [Infrastructure Documentation](https://github.com/pausatf/pausatf-infrastructure-docs)
- [Ansible Playbooks](https://github.com/pausatf/pausatf-ansible)
