# PAUSATF Droplet Cloud-Init Templates

Cloud-init configuration templates for PAUSATF DigitalOcean droplets with pre-configured web servers, security hardening, and WordPress optimization.

## Available Templates

| Template | Use Case | Web Server | PHP Version | Environment |
|----------|----------|------------|-------------|-------------|
| **cloud-init-base.yml** | Base configuration | None | N/A | All |
| **cloud-init-apache.yml** | Production WordPress | Apache 2.4 | PHP 7.4 | Production |
| **cloud-init-openlitespeed.yml** | Staging/Dev WordPress | OpenLiteSpeed 1.8 | PHP 8.4 | Staging/Dev |

## Features

### All Templates Include

- ✅ **Security Hardening**
  - UFW firewall pre-configured
  - Fail2ban with WordPress protection
  - Unattended security updates
  - SSH key-only authentication
  - Disabled root password login

- ✅ **System Configuration**
  - Pacific timezone (America/Los_Angeles)
  - UTF-8 locale
  - System utilities (curl, wget, git, vim, htop)
  - Monitoring tools (sysstat, iotop)

- ✅ **Automatic Updates**
  - Daily security updates
  - Automatic reboot if required (3 AM)

### Template-Specific Features

#### cloud-init-apache.yml (Production)

**Web Server:**
- Apache 2.4 with mod_rewrite, mod_ssl, mod_headers
- Security headers (X-Frame-Options, CSP, HSTS)
- Gzip compression and browser caching

**PHP Configuration:**
- PHP 7.4 (WordPress compatibility)
- OPcache enabled
- 256 MB memory limit
- 128 MB upload size
- WordPress-optimized settings

**Security:**
- Fail2ban WordPress jail
- Apache security hardening
- Disabled dangerous PHP functions

**Firewall Rules:**
- Port 22 (SSH)
- Port 80 (HTTP)
- Port 443 (HTTPS)

#### cloud-init-openlitespeed.yml (Staging/Dev)

**Web Server:**
- OpenLiteSpeed 1.8+
- LiteSpeed Cache compatible
- WebAdmin interface (port 7080)

**PHP Configuration:**
- PHP 8.4 (latest)
- LSPHP with OPcache
- Enhanced performance settings
- WordPress-optimized

**Object Caching:**
- Memcached 1.6+ (socket-based, 256MB)
  - Socket: `/var/www/memcached.sock`
  - No TCP (security)
- Redis 6.0+ (socket-based, 256MB)
  - Socket: `/var/run/redis/redis-server.sock`
  - LRU eviction policy
  - No persistence (cache only)
- Both run as `nobody` user for OpenLiteSpeed compatibility
- PHP extensions: lsphp84-memcached, lsphp84-redis

**Features:**
- Better performance than Apache
- Built-in cache management
- Web-based administration
- Object caching ready for LSCache plugin

**Firewall Rules:**
- Port 22 (SSH)
- Port 80 (HTTP)
- Port 443 (HTTPS)
- Port 7080 (OpenLiteSpeed Admin)

## Usage

### With Terraform

```hcl
module "production_droplet" {
  source = "../../modules/digitalocean/droplet"

  name        = "pausatf-prod"
  region      = "sfo3"
  size        = "s-4vcpu-8gb"
  image       = "ubuntu-22-04-x64"
  environment = "production"

  # Use Apache template for production
  user_data = templatefile("${path.module}/../../modules/droplet/cloud-init-apache.yml", {
    hostname    = "ftp"
    environment = "production"
  })

  ssh_key_ids = [var.ssh_key_id]

  tags = ["web", "wordpress", "production"]
}
```

### With doctl (DigitalOcean CLI)

```bash
# Create droplet with Apache template
doctl compute droplet create pausatf-prod \
  --image ubuntu-22-04-x64 \
  --size s-4vcpu-8gb \
  --region sfo3 \
  --ssh-keys YOUR_SSH_KEY_ID \
  --user-data-file terraform/modules/droplet/cloud-init-apache.yml \
  --tag-names web,wordpress,production

# Create droplet with OpenLiteSpeed template
doctl compute droplet create pausatf-stage \
  --image ubuntu-22-04-x64 \
  --size s-2vcpu-4gb \
  --region sfo3 \
  --ssh-keys YOUR_SSH_KEY_ID \
  --user-data-file terraform/modules/droplet/cloud-init-openlitespeed.yml \
  --tag-names web,wordpress,staging
```

### Direct Upload

```bash
# Render template with variables
cat cloud-init-apache.yml | \
  sed "s/\${hostname}/ftp/g" | \
  sed "s/\${environment}/production/g" > /tmp/cloud-init-rendered.yml

# Use with DigitalOcean API or Web UI
```

## Template Variables

All templates support the following variables:

| Variable | Description | Example | Required |
|----------|-------------|---------|----------|
| `${hostname}` | Server hostname (without domain) | `ftp`, `stage`, `dev` | Yes |
| `${environment}` | Environment name | `production`, `staging`, `development` | Yes |

## Post-Installation Tasks

### For Apache (Production)

1. **Configure DNS**
   ```bash
   # Point DNS to droplet IP
   # Example: ftp.pausatf.org -> 64.225.40.54
   ```

2. **Set up SSL Certificate**
   ```bash
   ssh deploy@ftp.pausatf.org
   sudo certbot --apache -d pausatf.org -d www.pausatf.org
   ```

3. **Deploy WordPress**
   ```bash
   # Use Ansible playbook
   ansible-playbook -i inventory/hosts.yml playbooks/wordpress.yml -l production
   ```

4. **Verify Configuration**
   ```bash
   # Check Apache status
   sudo systemctl status apache2

   # Check PHP version
   php -v

   # Check firewall
   sudo ufw status

   # Check fail2ban
   sudo fail2ban-client status
   ```

### For OpenLiteSpeed (Staging/Dev)

1. **Access WebAdmin**
   ```
   URL: https://stage.pausatf.org:7080
   User: admin
   Password: (check /usr/local/lsws/adminpasswd)
   ```

2. **Change Admin Password**
   ```bash
   ssh deploy@stage.pausatf.org
   sudo /usr/local/lsws/admin/misc/admpass.sh
   ```

3. **Configure Virtual Host**
   - WebAdmin > Virtual Hosts
   - Verify document root: `/var/www/html`
   - Enable rewrite rules for WordPress

4. **Set up SSL Certificate**
   ```bash
   # Install certbot for OpenLiteSpeed
   sudo apt install certbot
   sudo certbot certonly --webroot -w /var/www/html -d stage.pausatf.org
   ```

5. **Deploy WordPress**
   ```bash
   ansible-playbook -i inventory/hosts.yml playbooks/wordpress.yml -l staging
   ```

6. **Configure Object Cache (LSCache Plugin)**
   - Install LiteSpeed Cache plugin from WordPress admin
   - Go to LiteSpeed Cache > Settings > Advanced > Object Cache
   - Choose either Memcached or Redis:

   **For Memcached:**
   ```
   Host: /var/www/memcached.sock
   Port: 0
   ```

   **For Redis:**
   ```
   Host: /var/run/redis/redis-server.sock
   Port: 0
   ```

   - Click "Test Connection" to verify
   - Enable object cache

## Security Configuration

### SSH Access

All templates configure SSH for key-only authentication:

```bash
# Add your SSH key during droplet creation
# Or add it manually
ssh-copy-id -i ~/.ssh/id_ed25519.pub deploy@server.pausatf.org
```

### Firewall Rules

Check firewall status:
```bash
sudo ufw status verbose
```

Add custom rules:
```bash
# Allow specific IP for SSH
sudo ufw allow from 1.2.3.4 to any port 22

# Allow MySQL from specific IP
sudo ufw allow from 10.0.0.0/8 to any port 3306
```

### Fail2ban

Check banned IPs:
```bash
sudo fail2ban-client status wordpress
sudo fail2ban-client status sshd
```

Unban an IP:
```bash
sudo fail2ban-client set wordpress unbanip 1.2.3.4
```

## Monitoring

### Log Files

| Service | Log Location |
|---------|--------------|
| Cloud-init | `/var/log/cloud-init.log` |
| Cloud-init output | `/var/log/cloud-init-output.log` |
| Apache access | `/var/log/apache2/access.log` |
| Apache error | `/var/log/apache2/error.log` |
| OpenLiteSpeed access | `/usr/local/lsws/logs/pausatf-access.log` |
| OpenLiteSpeed error | `/usr/local/lsws/logs/pausatf-error.log` |
| PHP errors | `/var/log/php_errors.log` |
| Memcached | Check via `systemctl status memcached` |
| Redis | `/var/log/redis/redis-server.log` |
| Fail2ban | `/var/log/fail2ban.log` |
| UFW | `/var/log/ufw.log` |

### System Status

```bash
# Check cloud-init status
cloud-init status

# View cloud-init logs
sudo cat /var/log/cloud-init.log

# Check service status
systemctl status apache2   # or lsws for OpenLiteSpeed
systemctl status fail2ban
systemctl status ufw
```

## Troubleshooting

### Cloud-init Not Running

```bash
# Check cloud-init status
cloud-init status --long

# Re-run cloud-init (CAUTION: destructive)
sudo cloud-init clean
sudo cloud-init init
sudo cloud-init modules --mode=config
sudo cloud-init modules --mode=final
```

### Apache Won't Start

```bash
# Check configuration
sudo apache2ctl configtest

# Check error logs
sudo tail -f /var/log/apache2/error.log

# Verify port 80/443 not in use
sudo netstat -tlnp | grep :80
```

### OpenLiteSpeed Won't Start

```bash
# Check service status
sudo systemctl status lsws

# Check error logs
sudo tail -f /usr/local/lsws/logs/error.log

# Restart service
sudo systemctl restart lsws
```

### PHP Not Working

```bash
# Check PHP version
php -v

# Test PHP-FPM (Apache)
sudo systemctl status php7.4-fpm

# Test LSPHP (OpenLiteSpeed)
/usr/local/lsws/lsphp84/bin/php -v

# Check PHP error log
sudo tail -f /var/log/php_errors.log
```

### Firewall Blocking Legitimate Traffic

```bash
# Check firewall rules
sudo ufw status numbered

# Delete a rule
sudo ufw delete [number]

# Temporarily disable (for testing only)
sudo ufw disable
```

### Object Cache Not Working

**Check Memcached:**
```bash
# Check service status
sudo systemctl status memcached

# Check socket exists and has correct permissions
ls -la /var/www/memcached.sock

# Test connection
echo "stats" | nc -U /var/www/memcached.sock

# Restart memcached
sudo systemctl restart memcached
```

**Check Redis:**
```bash
# Check service status
sudo systemctl status redis-server

# Check socket exists and has correct permissions
ls -la /var/run/redis/redis-server.sock

# Test connection
redis-cli -s /var/run/redis/redis-server.sock ping

# View Redis stats
redis-cli -s /var/run/redis/redis-server.sock info stats

# Restart Redis
sudo systemctl restart redis-server
```

**Verify PHP Extensions:**
```bash
# Check if memcached extension is loaded
/usr/local/lsws/lsphp84/bin/php -m | grep memcached

# Check if redis extension is loaded
/usr/local/lsws/lsphp84/bin/php -m | grep redis
```

## Performance Tuning

### Apache (Production)

**Enable HTTP/2:**
```bash
sudo a2enmod http2
sudo systemctl restart apache2
```

**Tune MPM settings:**
```bash
# Edit /etc/apache2/mods-available/mpm_prefork.conf
<IfModule mpm_prefork_module>
    StartServers             4
    MinSpareServers          4
    MaxSpareServers          8
    MaxRequestWorkers        150
    MaxConnectionsPerChild   3000
</IfModule>
```

### OpenLiteSpeed (Staging/Dev)

**Enable LiteSpeed Cache:**
1. Install LiteSpeed Cache plugin for WordPress
2. Configure via WebAdmin > Cache

**Tune PHP OPcache:**
```bash
# Edit /usr/local/lsws/lsphp84/etc/php/8.4/litespeed/conf.d/99-wordpress-optimization.ini
opcache.memory_consumption = 512
opcache.max_accelerated_files = 20000
```

**Optimize Object Caching:**

*Memcached tuning* (edit `/etc/memcached.conf`):
```bash
# Increase memory allocation (default 256MB)
-m 512

# Increase connection limit (default 1024)
-c 2048

# Increase max item size (default 4MB)
-I 8M
```

*Redis tuning* (edit `/etc/redis/redis.conf`):
```bash
# Increase memory limit (default 256MB)
maxmemory 512mb

# Optimize for caching (LRU eviction)
maxmemory-policy allkeys-lru

# Tune for performance
tcp-backlog 2048
```

**Benchmark Object Cache:**
```bash
# Test memcached performance
memcached-tool /var/www/memcached.sock stats

# Test Redis performance
redis-cli -s /var/run/redis/redis-server.sock --latency
redis-cli -s /var/run/redis/redis-server.sock --intrinsic-latency 100
```

## Migration Guide

### From Apache to OpenLiteSpeed

1. Backup current configuration
2. Create new droplet with OpenLiteSpeed template
3. Migrate WordPress files and database
4. Test thoroughly
5. Update DNS
6. Decommission old droplet

### From OpenLiteSpeed to Apache

1. Backup current configuration
2. Create new droplet with Apache template
3. Migrate WordPress files and database
4. Reconfigure permalinks if needed
5. Test thoroughly
6. Update DNS
7. Decommission old droplet

## Best Practices

1. **Always use SSH keys** - Never enable password authentication
2. **Change default passwords** - Especially OpenLiteSpeed admin password
3. **Enable SSL/TLS** - Use Let's Encrypt for free certificates
4. **Monitor logs** - Set up log rotation and monitoring
5. **Regular updates** - Keep system and WordPress updated
6. **Backup before changes** - Always backup before major changes
7. **Test in staging** - Test changes in staging before production
8. **Use strong passwords** - For databases and WordPress admin
9. **Limit SSH access** - Use IP whitelisting when possible
10. **Monitor resources** - Set up alerts for CPU, memory, disk usage

## Related Resources

- [DigitalOcean Droplet Module](../digitalocean/droplet) - Terraform module for creating droplets
- [Ansible WordPress Role](../../../ansible/roles/wordpress) - WordPress deployment automation
- [Cloud-init Documentation](https://cloudinit.readthedocs.io/)
- [Apache Documentation](https://httpd.apache.org/docs/2.4/)
- [OpenLiteSpeed Documentation](https://openlitespeed.org/kb/)

## Version History

- **2025-12-28**: Created comprehensive cloud-init templates
  - Added cloud-init-base.yml
  - Added cloud-init-apache.yml (Production)
  - Added cloud-init-openlitespeed.yml (Staging/Dev)
  - Deprecated old nginx template

## Current Production Usage

| Droplet | Template | IP | Size | Status |
|---------|----------|-----|------|--------|
| ftp.pausatf.org | Apache | 64.225.40.54 | s-4vcpu-8gb | Production |
| stage.pausatf.org | OpenLiteSpeed | 64.227.85.73 | s-2vcpu-4gb | Staging |

## Maintainer

PAUSATF Infrastructure Team
