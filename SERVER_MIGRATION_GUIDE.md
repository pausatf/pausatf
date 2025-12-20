# PAUSATF.ORG SERVER MIGRATION GUIDE
## Moving to a New DigitalOcean Droplet

**Last Updated:** 2025-12-20
**Estimated Downtime:** 15-30 minutes (with proper preparation)
**Difficulty:** Advanced

---

## TABLE OF CONTENTS

1. [Pre-Migration Planning](#pre-migration-planning)
2. [Current Server Inventory](#current-server-inventory)
3. [Migration Prerequisites](#migration-prerequisites)
4. [Phase 1: Backup Current Server](#phase-1-backup-current-server)
5. [Phase 2: Create New Droplet](#phase-2-create-new-droplet)
6. [Phase 3: Install Software Stack](#phase-3-install-software-stack)
7. [Phase 4: Transfer Files](#phase-4-transfer-files)
8. [Phase 5: Migrate Database](#phase-5-migrate-database)
9. [Phase 6: Configure Web Server](#phase-6-configure-web-server)
10. [Phase 7: DNS Update](#phase-7-dns-update)
11. [Phase 8: Testing & Validation](#phase-8-testing-validation)
12. [Phase 9: Go Live](#phase-9-go-live)
13. [Phase 10: Post-Migration](#phase-10-post-migration)
14. [Rollback Procedures](#rollback-procedures)
15. [Troubleshooting](#troubleshooting)

---

## PRE-MIGRATION PLANNING

### Why Migrate?

Common reasons to migrate to a new droplet:
- **SSH Access Issues:** Current production server (ftp.pausatf.org) has port 22 blocked
- **Performance:** Upgrade to faster CPU/more RAM
- **Security:** Fresh installation with latest security patches
- **OS Upgrade:** Move from older Ubuntu to latest LTS version
- **Clean Slate:** Remove accumulated technical debt

### Migration Timeline

**Recommended Schedule:**
- **Planning:** 1-2 days (inventory, testing, documentation)
- **Preparation:** 1 day (backups, new droplet setup)
- **Migration:** 2-4 hours (actual transfer during low-traffic period)
- **Testing:** 1-2 hours (validation before DNS switch)
- **Buffer:** Keep old server running for 7 days minimum

**Best Time to Migrate:**
- Low traffic period (typically late night/early morning)
- Not during peak race season
- Avoid weekends with scheduled events

### Communication Plan

**Notify stakeholders:**
- [ ] Site administrators
- [ ] Content managers
- [ ] Jeff (primary contact)
- [ ] Email list subscribers (optional)

**Sample Notification:**
```
Subject: Scheduled Maintenance - pausatf.org Server Migration

We will be performing a server migration on [DATE] at [TIME].

Expected downtime: 15-30 minutes
During this time: Website may be briefly unavailable

Purpose: Improved performance and security

We will send a confirmation email once the migration is complete.
```

---

## CURRENT SERVER INVENTORY

### Production Server (Source)

```
Hostname:     ftp.pausatf.org
IP Address:   64.225.40.54
Droplet ID:   355909945
Droplet Name: pausatforg20230516-primary
Region:       sfo2 (San Francisco)
Size:         8GB RAM / 4 vCPUs / 160GB SSD
OS:           Ubuntu (version TBD)
Web Server:   Apache 2.4
PHP:          7.4
Database:     MySQL/MariaDB (version TBD)
SSL:          Let's Encrypt (managed by certbot)
```

### Files & Directories Requiring Migration

```
/var/www/legacy/public_html/          # WordPress installation
/var/www/legacy/public_html/data/     # Race results (CRITICAL)
/etc/apache2/                          # Apache configuration
/etc/php/7.4/                          # PHP configuration
/etc/letsencrypt/                      # SSL certificates
/usr/local/bin/purge_cloudflare_cache.sh  # Custom scripts
/var/log/                              # Logs (optional)
/root/                                 # Root scripts/configs
/home/                                 # User directories (if any)
```

### Database Information

```
Database Name:    (run: mysql -e "SHOW DATABASES;")
Database User:    (check wp-config.php)
Table Prefix:     wp_ (check wp-config.php)
Estimated Size:   (run: du -sh /var/lib/mysql/)
```

### DNS Configuration

```
Domain Registrar: (TBD - check whois pausatf.org)
DNS Provider:     Cloudflare
Zone ID:          your-cloudflare-zone-id

Current Records:
  www.pausatf.org  -> 64.225.40.54 (A record)
  ftp.pausatf.org  -> 64.225.40.54 (A record)
  pausatf.org      -> (check current config)
```

---

## MIGRATION PREREQUISITES

### Required Access

- [ ] DigitalOcean account access
- [ ] Cloudflare account access (for DNS)
- [ ] SSH access to current server (or console access)
- [ ] MySQL root password
- [ ] WordPress admin credentials
- [ ] Cloudflare API token: `your-cloudflare-api-token`

### Required Tools

**On local machine:**
```bash
# Install required tools
brew install doctl     # DigitalOcean CLI (macOS)
brew install rsync     # File transfer
brew install mysql     # Database tools

# Or on Linux:
apt-get install doctl rsync mysql-client
```

### Backup Storage

**Options:**
1. **DigitalOcean Spaces** (recommended)
   - Cost: ~$5/month for 250GB
   - Fast transfers within DO network

2. **Local machine**
   - Free but slower
   - Requires sufficient disk space (estimate 20-50GB)

3. **DigitalOcean Snapshots**
   - Cost: $0.05/GB/month
   - Easy rollback

---

## PHASE 1: BACKUP CURRENT SERVER

### Step 1.1: Access Current Server

```bash
# Via DigitalOcean Console (if SSH unavailable)
# 1. Go to https://cloud.digitalocean.com/droplets
# 2. Click on "pausatforg20230516-primary"
# 3. Click "Access" → "Launch Droplet Console"

# OR via SSH (if available):
ssh root@ftp.pausatf.org
```

### Step 1.2: Create DigitalOcean Snapshot (Recommended)

**From local machine:**
```bash
# Power off droplet (CAUSES DOWNTIME)
doctl compute droplet-action shutdown 355909945 --wait

# Create snapshot
doctl compute droplet-action snapshot 355909945 \
  --snapshot-name "pausatf-pre-migration-$(date +%Y%m%d)" \
  --wait

# Power back on
doctl compute droplet-action power-on 355909945 --wait

# Verify droplet is running
doctl compute droplet get 355909945 --format Status
```

**Alternative: Snapshot without downtime (filesystem snapshot)**
```bash
# On the server console/SSH:
apt-get update && apt-get install -y rsnapshot

# Create snapshot
rsnapshot daily
```

### Step 1.3: Backup Website Files

**On current server:**
```bash
# Create backup directory
mkdir -p /root/migration-backup
cd /root/migration-backup

# Backup web files (excluding cache)
tar -czf website-files-$(date +%Y%m%d).tar.gz \
  --exclude='wp-content/cache' \
  --exclude='wp-content/uploads/cache' \
  /var/www/legacy/public_html/

# Backup Apache configs
tar -czf apache-configs-$(date +%Y%m%d).tar.gz /etc/apache2/

# Backup PHP configs
tar -czf php-configs-$(date +%Y%m%d).tar.gz /etc/php/

# Backup SSL certificates
tar -czf ssl-certs-$(date +%Y%m%d).tar.gz /etc/letsencrypt/

# Backup custom scripts
tar -czf custom-scripts-$(date +%Y%m%d).tar.gz \
  /usr/local/bin/purge_cloudflare_cache.sh \
  /root/*.sh

# Verify backups
ls -lh /root/migration-backup/
md5sum /root/migration-backup/*.tar.gz > /root/migration-backup/checksums.txt
```

### Step 1.4: Backup Database

```bash
# Get database credentials from WordPress config
DB_NAME=$(grep DB_NAME /var/www/legacy/public_html/wp-config.php | cut -d "'" -f 4)
DB_USER=$(grep DB_USER /var/www/legacy/public_html/wp-config.php | cut -d "'" -f 4)
DB_PASS=$(grep DB_PASSWORD /var/www/legacy/public_html/wp-config.php | cut -d "'" -f 4)
DB_HOST=$(grep DB_HOST /var/www/legacy/public_html/wp-config.php | cut -d "'" -f 4)

# Backup database
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  --single-transaction \
  --quick \
  --lock-tables=false \
  > /root/migration-backup/database-$(date +%Y%m%d).sql

# Compress database backup
gzip /root/migration-backup/database-$(date +%Y%m%d).sql

# Verify backup
zcat /root/migration-backup/database-$(date +%Y%m%d).sql.gz | head -50
```

### Step 1.5: Document Current Configuration

```bash
# System information
cat > /root/migration-backup/system-info.txt << EOF
=== SYSTEM INFORMATION ===
Date: $(date)
Hostname: $(hostname)
OS: $(cat /etc/os-release | grep PRETTY_NAME)
Kernel: $(uname -r)
Uptime: $(uptime)

=== WEB SERVER ===
Apache Version: $(apache2 -v)
PHP Version: $(php -v | head -1)

=== DATABASE ===
MySQL Version: $(mysql --version)

=== DISK USAGE ===
$(df -h)

=== INSTALLED PACKAGES ===
$(dpkg -l | grep -E 'apache|php|mysql|wordpress')

=== APACHE MODULES ===
$(apache2ctl -M)

=== PHP MODULES ===
$(php -m)

=== CRON JOBS ===
$(crontab -l)

=== SERVICES ===
$(systemctl list-units --type=service --state=running)
EOF

cat /root/migration-backup/system-info.txt
```

### Step 1.6: Download Backups to Local Machine

**From local machine:**
```bash
# Create local backup directory
mkdir -p ~/pausatf-migration-backup
cd ~/pausatf-migration-backup

# Download via DigitalOcean Spaces (if configured)
# OR via SCP (if SSH available):
scp -r root@ftp.pausatf.org:/root/migration-backup/* .

# OR via rsync (recommended):
rsync -avz --progress root@ftp.pausatf.org:/root/migration-backup/ .

# Verify checksums
md5sum -c checksums.txt

# List downloaded files
ls -lh
```

**If SSH unavailable, use DigitalOcean Spaces:**
```bash
# On server (via console):
# Install s3cmd
apt-get install -y s3cmd

# Configure (use DigitalOcean Spaces credentials)
s3cmd --configure

# Upload backups
s3cmd put /root/migration-backup/* s3://pausatf-backups/

# From local machine, download from Spaces
s3cmd get s3://pausatf-backups/* ~/pausatf-migration-backup/
```

---

## PHASE 2: CREATE NEW DROPLET

### Step 2.1: Choose Droplet Specifications

**Recommended Configuration:**
```
Size:         Same or larger (8GB / 4 vCPUs minimum)
Region:       sfo2 (same as current - San Francisco 2)
OS:           Ubuntu 24.04 LTS x64 (latest LTS)
Options:      - Enable monitoring
              - Enable IPv6
              - Enable backups (recommended)
              - Add your SSH keys
Hostname:     pausatf-production-new
Tags:         pausatf, production, migration
```

**Cost Comparison:**
- 8GB / 4 vCPUs / 160GB SSD: $48/month
- 16GB / 8 vCPUs / 320GB SSD: $96/month (if upgrading)

### Step 2.2: Create Droplet via CLI

```bash
# Set variables
DROPLET_NAME="pausatf-production-new"
DROPLET_SIZE="s-4vcpu-8gb"  # 8GB RAM, 4 vCPUs
DROPLET_REGION="sfo2"
DROPLET_IMAGE="ubuntu-24-04-x64"

# Get your SSH key ID
SSH_KEY_ID=$(doctl compute ssh-key list --format ID --no-header)

# Create droplet
doctl compute droplet create "$DROPLET_NAME" \
  --size "$DROPLET_SIZE" \
  --region "$DROPLET_REGION" \
  --image "$DROPLET_IMAGE" \
  --ssh-keys "$SSH_KEY_ID" \
  --enable-monitoring \
  --enable-ipv6 \
  --enable-backups \
  --tag-names "pausatf,production,migration" \
  --wait

# Get new droplet IP
NEW_IP=$(doctl compute droplet list --format Name,PublicIPv4 | grep "$DROPLET_NAME" | awk '{print $2}')
echo "New Droplet IP: $NEW_IP"

# Save for later use
echo "NEW_IP=$NEW_IP" > ~/pausatf-migration-backup/new-droplet-info.txt
```

### Step 2.3: Initial Server Setup

```bash
# SSH into new server
ssh root@$NEW_IP

# Update system
apt-get update && apt-get upgrade -y

# Set timezone
timedatectl set-timezone America/Los_Angeles

# Set hostname
hostnamectl set-hostname pausatf-production

# Configure firewall
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw --force enable

# Verify firewall
ufw status verbose
```

---

## PHASE 3: INSTALL SOFTWARE STACK

### Step 3.1: Install Apache

```bash
# Install Apache
apt-get install -y apache2

# Enable required modules
a2enmod rewrite
a2enmod headers
a2enmod ssl
a2enmod expires

# Verify installation
apache2 -v
systemctl status apache2
```

### Step 3.2: Install PHP 8.3 (or match current version)

```bash
# For PHP 8.3 (recommended):
apt-get install -y php8.3 \
  php8.3-cli \
  php8.3-common \
  php8.3-mysql \
  php8.3-zip \
  php8.3-gd \
  php8.3-mbstring \
  php8.3-curl \
  php8.3-xml \
  php8.3-bcmath \
  php8.3-imagick

# Enable PHP in Apache
a2enmod php8.3

# Verify installation
php -v

# Configure PHP (increase limits for WordPress)
cat >> /etc/php/8.3/apache2/php.ini << EOF
upload_max_filesize = 128M
post_max_size = 128M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
EOF
```

**Alternative: Install PHP 7.4 (to match current)**
```bash
# Add PHP 7.4 repository
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update

# Install PHP 7.4
apt-get install -y php7.4 php7.4-{cli,common,mysql,zip,gd,mbstring,curl,xml,bcmath,imagick}
```

### Step 3.3: Install MySQL/MariaDB

```bash
# Install MariaDB
apt-get install -y mariadb-server mariadb-client

# Secure installation
mysql_secure_installation
# Answer: Y to all questions
# Set root password (SAVE THIS!)

# Verify installation
mysql --version
systemctl status mariadb

# Test connection
mysql -u root -p
# Run: SHOW DATABASES; then exit
```

### Step 3.4: Install Additional Tools

```bash
# Install useful tools
apt-get install -y \
  curl \
  wget \
  git \
  unzip \
  vim \
  htop \
  certbot \
  python3-certbot-apache \
  rsync

# Install WP-CLI (WordPress command line)
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

# Verify WP-CLI
wp --info
```

---

## PHASE 4: TRANSFER FILES

### Step 4.1: Create Directory Structure

**On new server:**
```bash
# Create web root
mkdir -p /var/www/legacy/public_html

# Set ownership
chown -R www-data:www-data /var/www/legacy

# Create backup directory
mkdir -p /root/migration-restore
```

### Step 4.2: Transfer Files from Old Server

**Method 1: Direct rsync (if SSH available on old server)**
```bash
# From new server:
rsync -avz --progress \
  root@64.225.40.54:/var/www/legacy/public_html/ \
  /var/www/legacy/public_html/

# This preserves permissions, timestamps, and transfers incrementally
```

**Method 2: Via local machine (if SSH unavailable)**
```bash
# On local machine:
cd ~/pausatf-migration-backup

# Extract website files
tar -xzf website-files-*.tar.gz

# Upload to new server
rsync -avz --progress \
  var/www/legacy/public_html/ \
  root@$NEW_IP:/var/www/legacy/public_html/
```

**Method 3: Via DigitalOcean Spaces**
```bash
# On new server:
apt-get install -y s3cmd
s3cmd --configure

# Download from Spaces
s3cmd get s3://pausatf-backups/website-files-*.tar.gz /root/migration-restore/

# Extract
cd /root/migration-restore
tar -xzf website-files-*.tar.gz
cp -a var/www/legacy/public_html/* /var/www/legacy/public_html/
```

### Step 4.3: Set Correct Permissions

```bash
# Set ownership
chown -R www-data:www-data /var/www/legacy/public_html

# Set directory permissions
find /var/www/legacy/public_html -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/legacy/public_html -type f -exec chmod 644 {} \;

# Make sure wp-config.php is secure
chmod 600 /var/www/legacy/public_html/wp-config.php

# Verify
ls -la /var/www/legacy/public_html/
```

### Step 4.4: Transfer Configuration Files

```bash
# Download config backups
cd /root/migration-restore
scp root@64.225.40.54:/root/migration-backup/apache-configs-*.tar.gz .
scp root@64.225.40.54:/root/migration-backup/php-configs-*.tar.gz .
scp root@64.225.40.54:/root/migration-backup/custom-scripts-*.tar.gz .

# Extract and review (DON'T auto-apply - configs may differ)
tar -xzf apache-configs-*.tar.gz
tar -xzf php-configs-*.tar.gz
tar -xzf custom-scripts-*.tar.gz

# Install custom scripts
cp usr/local/bin/purge_cloudflare_cache.sh /usr/local/bin/
chmod +x /usr/local/bin/purge_cloudflare_cache.sh
```

---

## PHASE 5: MIGRATE DATABASE

### Step 5.1: Create Database and User

**On new server:**
```bash
# Log into MySQL
mysql -u root -p

# Run these SQL commands:
CREATE DATABASE pausatf_wp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pausatf_user'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON pausatf_wp.* TO 'pausatf_user'@'localhost';
FLUSH PRIVILEGES;
SHOW DATABASES;
EXIT;
```

### Step 5.2: Import Database

```bash
# Download database backup
cd /root/migration-restore
scp root@64.225.40.54:/root/migration-backup/database-*.sql.gz .

# OR from Spaces:
s3cmd get s3://pausatf-backups/database-*.sql.gz .

# Decompress
gunzip database-*.sql.gz

# Import database (this may take several minutes)
mysql -u root -p pausatf_wp < database-*.sql

# Verify import
mysql -u root -p pausatf_wp -e "SHOW TABLES;"
mysql -u root -p pausatf_wp -e "SELECT COUNT(*) FROM wp_posts;"
```

### Step 5.3: Update wp-config.php

```bash
# Edit WordPress config
vim /var/www/legacy/public_html/wp-config.php

# Update database credentials:
define('DB_NAME', 'pausatf_wp');
define('DB_USER', 'pausatf_user');
define('DB_PASSWORD', 'SECURE_PASSWORD_HERE');
define('DB_HOST', 'localhost');

# Add/verify these for security:
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

# Save and exit
```

### Step 5.4: Update WordPress URLs (if testing with different domain)

```bash
# If testing with temporary domain/IP before DNS switch:
wp search-replace 'https://www.pausatf.org' 'http://$NEW_IP' \
  --path=/var/www/legacy/public_html/ \
  --skip-columns=guid \
  --dry-run

# If looks good, run without --dry-run:
wp search-replace 'https://www.pausatf.org' 'http://$NEW_IP' \
  --path=/var/www/legacy/public_html/ \
  --skip-columns=guid

# IMPORTANT: Reverse this before go-live!
```

---

## PHASE 6: CONFIGURE WEB SERVER

### Step 6.1: Create Apache Virtual Host

```bash
# Create vhost config
cat > /etc/apache2/sites-available/pausatf.conf << 'EOF'
<VirtualHost *:80>
    ServerName pausatf.org
    ServerAlias www.pausatf.org ftp.pausatf.org
    ServerAdmin admin@pausatf.org

    DocumentRoot /var/www/legacy/public_html

    <Directory /var/www/legacy/public_html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/pausatf_error.log
    CustomLog ${APACHE_LOG_DIR}/pausatf_access.log combined

    # Enable compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
    </IfModule>
</VirtualHost>
EOF

# Enable site
a2dissite 000-default.conf
a2ensite pausatf.conf

# Test Apache config
apache2ctl configtest

# Reload Apache
systemctl reload apache2
```

### Step 6.2: Install SSL Certificate

```bash
# Install Let's Encrypt certificate
certbot --apache -d pausatf.org -d www.pausatf.org -d ftp.pausatf.org

# Choose option 2: Redirect HTTP to HTTPS

# Test auto-renewal
certbot renew --dry-run

# Verify SSL
systemctl status certbot.timer
```

### Step 6.3: Install Cache Fix Files

```bash
# Create data directory
mkdir -p /var/www/legacy/public_html/data/2025

# Install .htaccess for cache control
cat > /var/www/legacy/public_html/data/2025/.htaccess << 'EOF'
# Cache Control for Race Results Directory
<IfModule mod_headers.c>
    # HTML files - AGGRESSIVE no-cache
    <FilesMatch "\.(html|htm)$">
        Header set Cache-Control "no-cache, no-store, must-revalidate, max-age=0"
        Header set Pragma "no-cache"
        Header set Expires "0"
        Header set CF-Cache-Control "no-cache"
    </FilesMatch>

    # Static assets - 30 days
    <FilesMatch "\.(jpg|jpeg|png|gif|ico|svg|webp)$">
        Header set Cache-Control "public, max-age=2592000, immutable"
    </FilesMatch>

    <FilesMatch "\.(css|js)$">
        Header set Cache-Control "public, max-age=2592000, immutable"
    </FilesMatch>
</IfModule>

Options -Indexes

<FilesMatch "^(\.htaccess|\.htpasswd|\.git)">
    Require all denied
</FilesMatch>
EOF

# Set permissions
chown www-data:www-data /var/www/legacy/public_html/data/2025/.htaccess
chmod 644 /var/www/legacy/public_html/data/2025/.htaccess
```

---

## PHASE 7: DNS UPDATE

### Step 7.1: Pre-DNS Testing

```bash
# Test site with /etc/hosts file on local machine
# Add to /etc/hosts:
echo "$NEW_IP www.pausatf.org pausatf.org ftp.pausatf.org" | sudo tee -a /etc/hosts

# Test in browser:
# http://$NEW_IP
# https://www.pausatf.org (if /etc/hosts modified)

# Test cache headers:
curl -I http://$NEW_IP/data/2025/ | grep -i cache

# Remove from /etc/hosts when testing complete
sudo vim /etc/hosts  # Remove the line
```

### Step 7.2: Lower DNS TTL (24 hours before migration)

```bash
# Login to Cloudflare dashboard or use API
# Set TTL to 300 seconds (5 minutes) for:
# - pausatf.org A record
# - www.pausatf.org A record
# - ftp.pausatf.org A record

# Wait 24 hours before changing IP addresses
```

### Step 7.3: Update DNS Records

**Via Cloudflare Dashboard:**
1. Login to Cloudflare
2. Select pausatf.org zone
3. Go to DNS settings
4. Update A records:
   - `pausatf.org` → `$NEW_IP`
   - `www.pausatf.org` → `$NEW_IP`
   - `ftp.pausatf.org` → `$NEW_IP`
5. Keep TTL at 300 seconds initially

**Via API:**
```bash
# Set variables
ZONE_ID="your-cloudflare-zone-id"
API_TOKEN="your-cloudflare-api-token"
NEW_IP="$NEW_IP"  # From earlier

# Get record IDs
curl -X GET "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records?name=www.pausatf.org" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json"

# Update www.pausatf.org (replace RECORD_ID)
curl -X PUT "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records/RECORD_ID" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"type":"A","name":"www.pausatf.org","content":"'$NEW_IP'","ttl":300,"proxied":true}'

# Repeat for pausatf.org and ftp.pausatf.org
```

### Step 7.4: Verify DNS Propagation

```bash
# Check from multiple locations
dig www.pausatf.org +short
dig @8.8.8.8 www.pausatf.org +short  # Google DNS
dig @1.1.1.1 www.pausatf.org +short  # Cloudflare DNS

# Check globally
# Visit: https://www.whatsmydns.net/#A/www.pausatf.org
```

---

## PHASE 8: TESTING & VALIDATION

### Step 8.1: Functional Testing

```bash
# Test homepage
curl -I https://www.pausatf.org/ | head -20

# Test WordPress admin
# Visit: https://www.pausatf.org/wp-admin/

# Test race results
curl -I https://www.pausatf.org/data/2025/ | grep -i cache

# Expected: cache-control: no-cache, no-store, must-revalidate
```

### Step 8.2: Performance Testing

```bash
# Check response time
curl -o /dev/null -s -w "Time: %{time_total}s\nSize: %{size_download} bytes\n" https://www.pausatf.org/

# Check database performance
mysql -u root -p pausatf_wp -e "SHOW STATUS LIKE 'Questions';"
mysql -u root -p pausatf_wp -e "SHOW STATUS LIKE 'Uptime';"
```

### Step 8.3: SSL/Security Testing

```bash
# Test SSL certificate
openssl s_client -connect www.pausatf.org:443 -servername www.pausatf.org < /dev/null | grep -i "verify return"

# Check SSL Labs score
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=www.pausatf.org

# Verify firewall
ufw status verbose

# Check for security headers
curl -I https://www.pausatf.org/ | grep -E "X-|Strict-Transport"
```

### Step 8.4: User Acceptance Testing

- [ ] Test user login/logout
- [ ] Test race results viewing
- [ ] Test file downloads
- [ ] Test contact forms
- [ ] Test search functionality
- [ ] Test mobile responsiveness

---

## PHASE 9: GO LIVE

### Step 9.1: Final Sync (5 minutes before cutover)

```bash
# On old server, put site in maintenance mode
wp maintenance-mode activate --path=/var/www/legacy/public_html/

# Final database dump
mysqldump -h localhost -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  --single-transaction > /root/final-sync-$(date +%Y%m%d-%H%M).sql

# Transfer to new server and import
scp /root/final-sync-*.sql root@$NEW_IP:/root/
ssh root@$NEW_IP "mysql -u root -p pausatf_wp < /root/final-sync-*.sql"

# Final file sync (only changed files)
rsync -avz --delete \
  /var/www/legacy/public_html/ \
  root@$NEW_IP:/var/www/legacy/public_html/
```

### Step 9.2: Reverse Search-Replace (if URLs were changed for testing)

```bash
# On new server:
wp search-replace "http://$NEW_IP" 'https://www.pausatf.org' \
  --path=/var/www/legacy/public_html/ \
  --skip-columns=guid

# Clear cache
wp cache flush --path=/var/www/legacy/public_html/
```

### Step 9.3: Purge Cloudflare Cache

```bash
# On new server:
/usr/local/bin/purge_cloudflare_cache.sh

# Verify success
tail /var/log/cloudflare-purge.log
```

### Step 9.4: Deactivate Maintenance Mode

```bash
# On new server:
wp maintenance-mode deactivate --path=/var/www/legacy/public_html/

# Test immediately
curl -I https://www.pausatf.org/ | head -5
```

---

## PHASE 10: POST-MIGRATION

### Step 10.1: Monitor for 24 Hours

```bash
# Monitor Apache error log
tail -f /var/log/apache2/pausatf_error.log

# Monitor access log
tail -f /var/log/apache2/pausatf_access.log

# Check system resources
htop

# Monitor database
mysql -u root -p pausatf_wp -e "SHOW PROCESSLIST;"
```

### Step 10.2: Performance Optimization

```bash
# Enable OPcache
cat >> /etc/php/8.3/apache2/php.ini << EOF
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
EOF

# Restart Apache
systemctl restart apache2

# Install Redis for object caching (optional)
apt-get install -y redis-server php-redis
systemctl enable redis-server
systemctl start redis-server
```

### Step 10.3: Update Monitoring & Backups

```bash
# Setup automated backups
cat > /root/backup-script.sh << 'EOFBACKUP'
#!/bin/bash
BACKUP_DIR="/root/backups/$(date +%Y%m%d)"
mkdir -p "$BACKUP_DIR"

# Backup files
tar -czf "$BACKUP_DIR"/website-files.tar.gz /var/www/legacy/public_html/

# Backup database
mysqldump -u root -p"$MYSQL_ROOT_PASS" pausatf_wp | gzip > "$BACKUP_DIR"/database.sql.gz

# Keep only last 7 days
find /root/backups/ -type d -mtime +7 -exec rm -rf {} \;
EOFBACKUP

chmod +x /root/backup-script.sh

# Add to crontab (daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /root/backup-script.sh") | crontab -
```

### Step 10.4: Increase DNS TTL

```bash
# After 24 hours of stable operation:
# Update Cloudflare DNS TTL back to 3600 (1 hour) or 14400 (4 hours)
```

### Step 10.5: Decommission Old Server

**After 7 days of successful operation:**

```bash
# Take final snapshot of old server
doctl compute droplet-action snapshot 355909945 \
  --snapshot-name "pausatf-final-before-destroy-$(date +%Y%m%d)" \
  --wait

# Power off old server (DON'T DELETE YET)
doctl compute droplet-action shutdown 355909945 --wait

# Wait another 7 days, then delete if no issues
doctl compute droplet delete 355909945

# Clean up old floating IPs if any
doctl compute floating-ip list
```

---

## ROLLBACK PROCEDURES

### If Migration Fails During Testing Phase

**Before DNS is updated:**
1. No action needed - old server still serving traffic
2. Fix issues on new server
3. Retry migration when ready

### If Migration Fails After DNS Update

**Emergency rollback:**

```bash
# METHOD 1: Revert DNS (5-30 minutes)
# Update Cloudflare DNS back to old IP: 64.225.40.54
curl -X PUT "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records/RECORD_ID" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"type":"A","name":"www.pausatf.org","content":"64.225.40.54","ttl":300,"proxied":true}'

# METHOD 2: Power on old server (immediate)
doctl compute droplet-action power-on 355909945 --wait

# METHOD 3: Restore from snapshot
doctl compute droplet create pausatf-restored \
  --size s-4vcpu-8gb \
  --region sfo2 \
  --image SNAPSHOT_ID \
  --wait
```

### Data Loss Prevention

- Keep old server running for minimum 7 days
- Maintain DigitalOcean snapshot for 30 days
- Keep local backups for 90 days
- Document all changes made post-migration

---

## TROUBLESHOOTING

### Issue: Website shows 500 Internal Server Error

**Causes:**
- PHP version mismatch
- Missing PHP extensions
- Incorrect file permissions
- .htaccess syntax errors

**Solutions:**
```bash
# Check Apache error log
tail -100 /var/log/apache2/pausatf_error.log

# Check PHP version
php -v

# Verify file permissions
ls -la /var/www/legacy/public_html/

# Test .htaccess syntax
apache2ctl configtest

# Temporarily disable .htaccess
mv /var/www/legacy/public_html/.htaccess /root/.htaccess.backup
```

### Issue: Database connection errors

**Causes:**
- Incorrect credentials in wp-config.php
- MySQL not running
- User privileges not granted

**Solutions:**
```bash
# Test database connection
mysql -u pausatf_user -p pausatf_wp -e "SELECT 1;"

# Check MySQL status
systemctl status mariadb

# Re-grant privileges
mysql -u root -p -e "GRANT ALL PRIVILEGES ON pausatf_wp.* TO 'pausatf_user'@'localhost'; FLUSH PRIVILEGES;"
```

### Issue: Images/files not loading

**Causes:**
- Incomplete file transfer
- Wrong permissions
- Incorrect URLs in database

**Solutions:**
```bash
# Check file ownership
ls -la /var/www/legacy/public_html/wp-content/uploads/

# Fix permissions
chown -R www-data:www-data /var/www/legacy/public_html/wp-content/uploads/

# Verify URLs in database
wp search-replace --dry-run 'http://' 'https://' --path=/var/www/legacy/public_html/
```

### Issue: SSL certificate errors

**Causes:**
- Certificate not installed
- Wrong domain in certificate
- Mixed content (HTTP resources on HTTPS page)

**Solutions:**
```bash
# Reinstall certificate
certbot --apache -d pausatf.org -d www.pausatf.org -d ftp.pausatf.org --force-renewal

# Check certificate
openssl x509 -in /etc/letsencrypt/live/pausatf.org/cert.pem -text -noout

# Fix mixed content
wp search-replace 'http://www.pausatf.org' 'https://www.pausatf.org' --path=/var/www/legacy/public_html/
```

### Issue: Slow performance

**Causes:**
- Missing database indexes
- No caching configured
- Insufficient server resources

**Solutions:**
```bash
# Check server load
htop

# Optimize database
wp db optimize --path=/var/www/legacy/public_html/

# Enable OPcache (see Phase 10.2)

# Check slow queries
mysql -u root -p -e "SHOW PROCESSLIST;"
```

---

## APPENDIX: QUICK REFERENCE

### Critical Commands

```bash
# Check if website is responding
curl -I https://www.pausatf.org/

# View Apache error log
tail -f /var/log/apache2/pausatf_error.log

# Restart Apache
systemctl restart apache2

# Purge Cloudflare cache
/usr/local/bin/purge_cloudflare_cache.sh

# WordPress CLI health check
wp --info --path=/var/www/legacy/public_html/
```

### Important File Locations

```
Web Root:           /var/www/legacy/public_html/
WordPress Config:   /var/www/legacy/public_html/wp-config.php
Apache Config:      /etc/apache2/sites-available/pausatf.conf
PHP Config:         /etc/php/8.3/apache2/php.ini
SSL Certs:          /etc/letsencrypt/live/pausatf.org/
Purge Script:       /usr/local/bin/purge_cloudflare_cache.sh
Apache Logs:        /var/log/apache2/
```

### Support Resources

- **DigitalOcean Docs:** https://docs.digitalocean.com/
- **WordPress Migration:** https://wordpress.org/support/article/moving-wordpress/
- **Apache Docs:** https://httpd.apache.org/docs/2.4/
- **Let's Encrypt:** https://letsencrypt.org/docs/
- **WP-CLI:** https://wp-cli.org/

---

**Migration Prepared By:** Thomas Vincent
**Document Version:** 1.0
**Last Updated:** 2025-12-20

*This migration guide should be reviewed and updated based on actual server configuration discovered during the backup phase.*
