# PAUSATF Automation Scripts

**Operational scripts and automation tools for pausatf.org infrastructure**

[![Shell Script](https://img.shields.io/badge/Shell-Bash-4EAA25?logo=gnu-bash)](https://www.gnu.org/software/bash/)
[![Python](https://img.shields.io/badge/Python-3.11+-3776AB?logo=python)](https://www.python.org/)

---

## Overview

Collection of automation scripts for PAUSATF.org infrastructure operations, maintenance, monitoring, and deployment tasks.

## Repository Structure

```
pausatf-scripts/
├── backup/              # Backup and restore scripts
├── deployment/          # Deployment automation
├── monitoring/          # Health checks and alerts
├── maintenance/         # Routine maintenance tasks
├── security/            # Security scans and updates
├── cloudflare/          # Cloudflare API automation
└── database/            # Database maintenance scripts
```

## Scripts

### Backup
- `backup-wordpress.sh` - WordPress file and database backup
- `backup-to-spaces.sh` - Upload backups to DigitalOcean Spaces
- `restore-wordpress.sh` - Restore from backup

### Deployment
- `deploy-to-staging.sh` - Deploy changes to staging
- `deploy-to-production.sh` - Deploy to production (with checks)
- `rollback.sh` - Rollback to previous version

### Monitoring
- `health-check.sh` - Server health monitoring
- `check-ssl-expiry.sh` - SSL certificate expiration check
- `disk-usage-alert.sh` - Disk space monitoring

### Maintenance
- `update-wordpress.sh` - WordPress core updates
- `update-plugins.sh` - Plugin updates
- `optimize-database.sh` - Database optimization
- `clear-cache.sh` - Clear all caches

### Security
- `security-scan.sh` - WordPress security scan
- `update-firewall-rules.sh` - Update firewall configurations
- `rotate-secrets.sh` - Rotate API tokens and passwords

### Cloudflare
- `purge-cloudflare-cache.sh` - Purge Cloudflare cache
- `update-dns-records.sh` - Update DNS via API
- `check-cloudflare-status.sh` - Cloudflare status check

### Database
- `backup-database.sh` - MySQL database backup
- `optimize-tables.sh` - Optimize MySQL tables
- `check-primary-keys.sh` - Verify all tables have primary keys

---

## Usage

### Prerequisites

```bash
# Install required tools
brew install jq curl

# Set environment variables
export DIGITALOCEAN_TOKEN="your-token"
export CLOUDFLARE_API_TOKEN="your-token"
```

### Running Scripts

```bash
# Make executable
chmod +x script-name.sh

# Run with appropriate permissions
./backup/backup-wordpress.sh

# Or use bash directly
bash deployment/deploy-to-staging.sh
```

---

## Best Practices

### Script Standards

1. **Shebang**: Always use `#!/bin/bash`
2. **Error Handling**: Use `set -euo pipefail`
3. **Logging**: Log to both console and file
4. **Documentation**: Include header comments
5. **Validation**: Check prerequisites before execution

### Example Script Template

```bash
#!/bin/bash
# Script Name: example-script.sh
# Description: Brief description of what this script does
# Author: Thomas Vincent
# Date: 2025-12-21

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="/var/log/pausatf/example.log"

# Functions
log() {
    echo "[$(date +%Y-%m-%d\ %H:%M:%S)] $*" | tee -a "$LOG_FILE"
}

error() {
    log "ERROR: $*" >&2
    exit 1
}

# Validation
command -v jq >/dev/null 2>&1 || error "jq is required"

# Main Logic
log "Starting script..."
# Your code here
log "Script completed successfully"
```

---

## Security

### Secrets Management

**Never commit secrets to the repository!**

- Use environment variables for API tokens
- Use `.env` files (gitignored) for local development
- Use GitHub Secrets for CI/CD
- Rotate credentials regularly

### Pre-commit Hooks

Install to prevent committing secrets:

```bash
pip install pre-commit
pre-commit install
```

---

## CI/CD

Scripts are validated on every PR:
- ShellCheck linting
- Secret detection
- Syntax validation

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## License

Private repository - Internal use only

**Maintained by:** Thomas Vincent
**Organization:** Pacific Association of USA Track and Field (PAUSATF)
**Last Updated:** 2025-12-21
