# Changelog

All notable changes to PAUSATF Ansible configuration will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to semantic versioning.

---

## [Unreleased]

---

## [1.0.0] - 2025-12-21

### Added
- Initial Ansible configuration management repository
- Complete WordPress server playbook (site.yml)
- Apache web server role with SSL/TLS configuration
- MySQL database role with optimization settings
- PHP role with multiple version support (7.2, 7.4, 8.1, 8.4)
- WordPress deployment role with TheSource theme
- Cloudflare integration role with IP allowlisting
- Fail2ban security role with WordPress protection
- Monitoring role with health checks and backups
- NewRelic APM integration role
- Redis caching role
- UFW firewall role
- Common system configuration role
- Production and staging inventory configurations
- Group variables for environment-specific settings
- Pre-commit hooks (ansible-lint, yamllint, checkov)
- GitHub Actions workflow for linting and syntax validation
- Security scanning with checkov
- Secret detection with gitleaks and detect-secrets

### Security
- Fail2ban with WordPress-specific filters
- UFW firewall configuration
- Cloudflare IP allowlisting
- SSL/TLS certificate management
- Automated security updates
- WordPress hardening configurations

---

## Infrastructure Managed

### Production Server
- Hostname: pausatf-prod
- IP: 64.225.40.54
- OS: Ubuntu 20.04 LTS
- Web Server: Apache 2.4
- PHP: 7.4 (with 8.1, 8.4 available)
- Database: MySQL 5.7
- WordPress: 6.9

### Staging Server
- Hostname: pausatf-stage
- IP: 64.227.85.73
- OS: Ubuntu 20.04 LTS
- Web Server: OpenLiteSpeed 1.8.3
- PHP: 8.4
- WordPress: 6.9

### Roles
- apache: Web server configuration
- cloudflare: CDN integration
- common: Base system setup
- fail2ban: Security and brute force protection
- monitoring: Health checks and backups
- mysql: Database server
- newrelic: Application monitoring
- php: PHP-FPM configuration
- redis: Object caching
- ufw: Firewall management
- wordpress: WordPress deployment and theme management
