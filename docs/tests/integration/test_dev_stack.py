#!/usr/bin/env python3
"""
Integration tests for the dev server stack.

Verifies Apache event MPM + PHP 8.4-FPM + HTTP/2 + Redis + security
services are correctly configured on the dev droplet.

Requires SSH access to the dev server (root@DEV_DROPLET_IP).
"""

import os
import pytest
import paramiko


DEV_IP = os.getenv("DEV_DROPLET_IP", "157.245.176.229")
SSH_USER = os.getenv("SSH_USER", "root")
SSH_KEY = os.path.expanduser(os.getenv("SSH_KEY_PATH", "~/.ssh/id_rsa"))


@pytest.fixture(scope="module")
def ssh():
    """Establish SSH connection to the dev server."""
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(
            hostname=DEV_IP,
            username=SSH_USER,
            key_filename=SSH_KEY,
            timeout=10,
        )
    except Exception as e:
        pytest.skip(f"Cannot connect to dev server {DEV_IP}: {e}")
    yield client
    client.close()


def _run(ssh, cmd):
    """Run a command over SSH, return (stdout, stderr, rc)."""
    _, stdout, stderr = ssh.exec_command(cmd)
    rc = stdout.channel.recv_exit_status()
    return stdout.read().decode().strip(), stderr.read().decode().strip(), rc


# --- Apache ---


@pytest.mark.integration
class TestApache:

    def test_apache_running(self, ssh):
        out, _, _ = _run(ssh, "systemctl is-active apache2")
        assert out == "active"

    def test_apache_enabled(self, ssh):
        out, _, _ = _run(ssh, "systemctl is-enabled apache2")
        assert out == "enabled"

    def test_mpm_event_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep mpm_event")
        assert "mpm_event_module" in out

    def test_mpm_prefork_not_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep mpm_prefork")
        assert "mpm_prefork_module" not in out

    def test_http2_module_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep http2")
        assert "http2_module" in out

    def test_proxy_fcgi_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep proxy_fcgi")
        assert "proxy_fcgi_module" in out

    def test_ssl_module_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep ssl_module")
        assert "ssl_module" in out

    def test_rewrite_module_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep rewrite")
        assert "rewrite_module" in out

    def test_expires_module_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep expires")
        assert "expires_module" in out

    def test_deflate_module_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep deflate")
        assert "deflate_module" in out

    def test_configtest_passes(self, ssh):
        _, _, rc = _run(ssh, "apache2ctl configtest 2>&1")
        assert rc == 0

    def test_vhost_configured(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -S 2>&1 | grep dev.pausatf.org")
        assert "dev.pausatf.org" in out

    def test_ssl_vhost_on_443(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -S 2>&1 | grep ':443'")
        assert "443" in out


# --- PHP-FPM ---


@pytest.mark.integration
class TestPHPFPM:

    def test_php_fpm_running(self, ssh):
        out, _, _ = _run(ssh, "systemctl is-active php8.4-fpm")
        assert out == "active"

    def test_php_fpm_enabled(self, ssh):
        out, _, _ = _run(ssh, "systemctl is-enabled php8.4-fpm")
        assert out == "enabled"

    def test_fpm_socket_exists(self, ssh):
        out, _, _ = _run(ssh, "test -S /run/php/php8.4-fpm.sock && echo yes")
        assert out == "yes"

    def test_php_version(self, ssh):
        out, _, _ = _run(ssh, "php -v | head -1")
        assert "8.4" in out

    def test_fpm_pool_config_exists(self, ssh):
        out, _, _ = _run(ssh, "test -f /etc/php/8.4/fpm/pool.d/www.conf && echo yes")
        assert out == "yes"

    def test_required_php_extensions(self, ssh):
        out, _, _ = _run(ssh, "php -m")
        required = ["mysqli", "curl", "gd", "mbstring", "xml", "zip", "redis"]
        for ext in required:
            assert ext in out, f"PHP extension {ext} not loaded"


# --- Redis ---


@pytest.mark.integration
class TestRedis:

    def test_redis_running(self, ssh):
        out, _, _ = _run(ssh, "systemctl is-active redis-server")
        assert out == "active"

    def test_redis_listening_localhost(self, ssh):
        out, _, _ = _run(ssh, "ss -tlnp | grep 6379")
        assert "127.0.0.1" in out

    def test_redis_ping(self, ssh):
        out, _, _ = _run(ssh, "redis-cli ping")
        assert out == "PONG"


# --- WordPress ---


@pytest.mark.integration
class TestWordPress:

    def test_wordpress_installed(self, ssh):
        _, _, rc = _run(ssh, "/usr/local/bin/wp core is-installed --path=/var/www/html --allow-root")
        assert rc == 0

    def test_wordpress_db_connected(self, ssh):
        out, _, rc = _run(ssh, "/usr/local/bin/wp db check --path=/var/www/html --allow-root 2>&1 | tail -1")
        assert "Success" in out

    def test_active_theme_exists(self, ssh):
        out, _, _ = _run(ssh, "/usr/local/bin/wp theme list --status=active --field=name --path=/var/www/html --allow-root")
        assert out.strip() != "", "No active WordPress theme"

    def test_wp_cron_functional(self, ssh):
        """WP-Cron needs the site URL to resolve from the server itself."""
        out, _, rc = _run(ssh, "/usr/local/bin/wp cron test --path=/var/www/html --allow-root")
        if rc != 0 and "Could not resolve host" in out:
            pytest.skip("Server cannot resolve its own domain (needs /etc/hosts entry)")
        assert rc == 0

    def test_uploads_dir_exists(self, ssh):
        out, _, _ = _run(ssh, "test -d /var/www/html/wp-content/uploads && echo yes")
        assert out == "yes"

    def test_uploads_php_blocked(self, ssh):
        out, _, _ = _run(ssh, "test -f /var/www/html/wp-content/uploads/.htaccess && echo yes")
        assert out == "yes"

    def test_file_permissions(self, ssh):
        out, _, _ = _run(ssh, "stat -c '%U:%G' /var/www/html/wp-config.php")
        assert out in ("www-data:www-data", "root:root"), f"Unexpected owner: {out}"


# --- Security Services ---


@pytest.mark.integration
class TestSecurity:

    def test_ufw_active(self, ssh):
        out, _, _ = _run(ssh, "ufw status")
        assert "Status: active" in out

    def test_ufw_ports(self, ssh):
        out, _, _ = _run(ssh, "ufw status | grep -E '^(22|80|443)'")
        assert "22/tcp" in out
        assert "80/tcp" in out
        assert "443/tcp" in out

    def test_fail2ban_running(self, ssh):
        out, _, _ = _run(ssh, "systemctl is-active fail2ban")
        assert out == "active"

    def test_fail2ban_sshd_jail(self, ssh):
        out, _, _ = _run(ssh, "fail2ban-client status sshd 2>&1")
        assert "sshd" in out

    def test_modsecurity_loaded(self, ssh):
        out, _, _ = _run(ssh, "apache2ctl -M 2>&1 | grep security")
        assert "security2_module" in out

    def test_ssl_cert_exists(self, ssh):
        out, _, _ = _run(ssh, "test -f /etc/ssl/certs/dev.pausatf.org.pem && echo yes")
        assert out == "yes"

    def test_ssl_key_exists(self, ssh):
        out, _, _ = _run(ssh, "test -f /etc/ssl/private/dev.pausatf.org.key && echo yes")
        assert out == "yes"


# --- HTTP/2 ---


@pytest.mark.integration
class TestHTTP2:

    def test_h2_protocol_direct(self, ssh):
        """Verify HTTP/2 negotiation directly against Apache."""
        out, _, _ = _run(
            ssh,
            "curl -sk --http2 -o /dev/null -w '%{http_version}' https://localhost/ -H 'Host: dev.pausatf.org'"
        )
        assert out == "2"

    def test_protocols_directive(self, ssh):
        """Verify Protocols h2 is in the SSL vhost config."""
        out, _, _ = _run(ssh, "cat /etc/apache2/sites-enabled/*ssl* /etc/apache2/sites-enabled/*443* 2>/dev/null | grep -i 'Protocols'")
        assert "h2" in out
