# Legacy Content Automated Sync Setup

This document explains how to set up automated syncing of the legacy content from the production server to the Git repository.

## Overview

Two sync methods are provided:

1. **File Watcher** (`sync-legacy-content-watcher.sh`) - Real-time sync using inotify
2. **Cron Job** (`sync-legacy-content-cron.sh`) - Periodic sync on a schedule

You can use either one or both (watcher for real-time, cron as backup).

## Prerequisites

### On Production Server

- Git installed
- rsync installed
- SSH key configured for GitHub access
- For watcher: inotify-tools installed (`apt-get install inotify-tools`)

### GitHub Repository

- Repository: https://github.com/pausatf/pausatf-legacy-content
- Server needs push access (deploy key or personal access token)

## Setup Instructions

### Step 1: Clone Repository on Server

```bash
# Clone to /opt (requires root)
cd /opt
git clone git@github.com:pausatf/pausatf-legacy-content.git
cd pausatf-legacy-content

# Configure git
git config user.name "Legacy Content Sync Bot"
git config user.email "noreply@pausatf.org"
```

### Step 2: Deploy Sync Scripts

```bash
# Copy scripts to /usr/local/bin
cp sync-legacy-content-watcher.sh /usr/local/bin/
cp sync-legacy-content-cron.sh /usr/local/bin/

# Make executable
chmod +x /usr/local/bin/sync-legacy-content-watcher.sh
chmod +x /usr/local/bin/sync-legacy-content-cron.sh
```

### Step 3: Choose Sync Method

#### Option A: File Watcher (Real-time Sync)

**Install inotify-tools:**
```bash
apt-get update
apt-get install -y inotify-tools
```

**Deploy systemd service:**
```bash
# Copy service file
cp sync-legacy-content-watcher.service /etc/systemd/system/

# Reload systemd
systemctl daemon-reload

# Enable and start service
systemctl enable sync-legacy-content-watcher
systemctl start sync-legacy-content-watcher

# Check status
systemctl status sync-legacy-content-watcher

# View logs
journalctl -u sync-legacy-content-watcher -f
# or
tail -f /var/log/legacy-content-sync.log
```

**To stop/disable:**
```bash
systemctl stop sync-legacy-content-watcher
systemctl disable sync-legacy-content-watcher
```

#### Option B: Cron Job (Scheduled Sync)

**Add to crontab:**
```bash
# Edit root's crontab
crontab -e

# Add one of these lines:

# Every 6 hours
0 */6 * * * /usr/local/bin/sync-legacy-content-cron.sh >> /var/log/legacy-content-sync-cron.log 2>&1

# Daily at 2 AM
0 2 * * * /usr/local/bin/sync-legacy-content-cron.sh >> /var/log/legacy-content-sync-cron.log 2>&1

# Every hour
0 * * * * /usr/local/bin/sync-legacy-content-cron.sh >> /var/log/legacy-content-sync-cron.log 2>&1
```

**Test manually:**
```bash
/usr/local/bin/sync-legacy-content-cron.sh
```

**View cron logs:**
```bash
tail -f /var/log/legacy-content-sync-cron.log
```

#### Option C: Both (Recommended)

Use the watcher for real-time updates and cron as a backup:

```bash
# Enable watcher service
systemctl enable sync-legacy-content-watcher
systemctl start sync-legacy-content-watcher

# Add cron job for daily backup sync at 3 AM
crontab -e
# Add: 0 3 * * * /usr/local/bin/sync-legacy-content-cron.sh >> /var/log/legacy-content-sync-cron.log 2>&1
```

## Configuration

### Watcher Script Configuration

Edit `/usr/local/bin/sync-legacy-content-watcher.sh`:

```bash
LEGACY_DIR="/var/www/legacy/public_html"     # Source directory
GIT_REPO_PATH="/opt/pausatf-legacy-content"  # Git repo path
DEBOUNCE_SECONDS=30                           # Wait time after changes
```

### Cron Script Configuration

Edit `/usr/local/bin/sync-legacy-content-cron.sh`:

```bash
LEGACY_DIR="/var/www/legacy/public_html"     # Source directory
GIT_REPO_PATH="/opt/pausatf-legacy-content"  # Git repo path
```

## How It Works

### File Watcher Process

1. Monitors `/var/www/legacy/public_html/` for file changes using inotify
2. Waits for 30-second quiet period (debounce)
3. Syncs files to `/opt/pausatf-legacy-content/` using rsync
4. Commits changes to git
5. Pushes to GitHub

### Cron Process

1. Runs on schedule (e.g., every 6 hours)
2. Pulls latest changes from GitHub
3. Syncs files from `/var/www/legacy/public_html/` using rsync
4. Commits any changes
5. Pushes to GitHub

### Excluded Files

Both scripts automatically exclude:
- `.DS_Store` (macOS metadata)
- `._*` (macOS resource forks)
- `.mirrorinfo*` (FTP sync metadata)
- `.ftpquota` (FTP quota files)
- `.wysiwygPro_*` (Editor temp files)
- `Thumbs.db` (Windows metadata)
- `*.swp` (Vim swap files)
- `*.tmp` (Temporary files)

## Monitoring

### Check Watcher Status

```bash
# Service status
systemctl status sync-legacy-content-watcher

# Live logs
journalctl -u sync-legacy-content-watcher -f

# Log file
tail -f /var/log/legacy-content-sync.log
```

### Check Cron Status

```bash
# Cron log
tail -f /var/log/legacy-content-sync-cron.log

# Last cron run
grep "legacy-content-sync-cron" /var/log/syslog | tail -20
```

### Check Git Repository

```bash
cd /opt/pausatf-legacy-content

# Check status
git status

# View recent commits
git log --oneline -10

# Check for pending changes
git diff
```

### Check GitHub

Visit: https://github.com/pausatf/pausatf-legacy-content/commits/main

Recent commits should show auto-sync messages from the bot.

## Troubleshooting

### Watcher Not Starting

```bash
# Check service status
systemctl status sync-legacy-content-watcher

# View error logs
journalctl -u sync-legacy-content-watcher --no-pager

# Verify inotify-tools installed
which inotifywait

# Test manually
/usr/local/bin/sync-legacy-content-watcher.sh
```

### Cron Not Running

```bash
# Check cron service
systemctl status cron

# View cron log
grep CRON /var/log/syslog

# Test script manually
/usr/local/bin/sync-legacy-content-cron.sh

# Verify crontab
crontab -l
```

### Git Push Failures

```bash
# Test GitHub SSH access
ssh -T git@github.com

# Check Git config
cd /opt/pausatf-legacy-content
git config --list

# Manual push
git push origin main

# Check for conflicts
git status
git pull origin main
```

### Lock File Issues (Cron)

If cron script reports "Another sync is already running":

```bash
# Check for stale lock
ls -la /var/run/legacy-content-sync.lock

# Remove if stale (verify no sync is actually running)
rm /var/run/legacy-content-sync.lock
```

## Security Considerations

### SSH Key for GitHub

The server needs an SSH key configured for GitHub access:

```bash
# Generate key (if not exists)
ssh-keygen -t ed25519 -C "pausatf-prod-legacy-sync"

# Add to GitHub as deploy key
cat ~/.ssh/id_ed25519.pub
# Go to: https://github.com/pausatf/pausatf-legacy-content/settings/keys
# Add deploy key with write access
```

### File Permissions

```bash
# Scripts should be owned by root
chown root:root /usr/local/bin/sync-legacy-content-*.sh

# Executable by root only
chmod 700 /usr/local/bin/sync-legacy-content-*.sh

# Git repo should be accessible
chown -R root:www-data /opt/pausatf-legacy-content
chmod -R 755 /opt/pausatf-legacy-content
```

### Log Rotation

Create `/etc/logrotate.d/legacy-content-sync`:

```
/var/log/legacy-content-sync.log
/var/log/legacy-content-sync-cron.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0640 root root
}
```

## Maintenance

### Updating Scripts

```bash
# Pull latest changes
cd /opt/pausatf-legacy-content
git pull origin main

# Copy updated scripts
cp sync-legacy-content-watcher.sh /usr/local/bin/
cp sync-legacy-content-cron.sh /usr/local/bin/
chmod +x /usr/local/bin/sync-legacy-content-*.sh

# Restart watcher service if using it
systemctl restart sync-legacy-content-watcher
```

### Manual Sync

If you need to manually trigger a sync:

```bash
# Using cron script
/usr/local/bin/sync-legacy-content-cron.sh

# Or restart watcher to force immediate sync
systemctl restart sync-legacy-content-watcher
```

### Disabling Sync

**Temporarily:**
```bash
# Stop watcher
systemctl stop sync-legacy-content-watcher

# Comment out cron job
crontab -e
# Add # before the sync line
```

**Permanently:**
```bash
# Disable and stop watcher
systemctl disable sync-legacy-content-watcher
systemctl stop sync-legacy-content-watcher

# Remove cron job
crontab -e
# Delete the sync line
```

## Related Documentation

- [Legacy Content README](README.md)
- [Infrastructure Documentation](https://github.com/pausatf/pausatf-infrastructure-docs)
- [Scripts Repository](https://github.com/pausatf/pausatf-scripts)

## Support

For issues or questions:
- [Open an issue](https://github.com/pausatf/pausatf-infrastructure-docs/issues)
- [Start a discussion](https://github.com/pausatf/pausatf-infrastructure-docs/discussions)
- Contact: @thomasvincent
