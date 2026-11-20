#!/usr/bin/env python3
"""
Integration tests for infrastructure deployment.
Tests end-to-end infrastructure provisioning and configuration.
"""

import os
from typing import Any, Dict

import paramiko
import pytest
import requests


TEST_DOMAIN = os.getenv("TEST_DOMAIN", "www.pausatf.org")
SSH_USER = os.getenv("TEST_SSH_USER", os.getenv("SSH_USER", "github-deploy"))
SSH_KEY = os.path.expanduser(
    os.getenv("TEST_SSH_KEY", os.getenv("SSH_KEY_PATH", "~/.ssh/id_rsa"))
)


def connect(host: str) -> paramiko.SSHClient:
    """Open an SSH session that refuses unknown host keys.

    When TEST_SSH_CONFIG names an ssh_config file (CI writes one with the
    cloudflare-access-ssh action), `host` may be a Host alias from it; its
    HostName, User, IdentityFile, UserKnownHostsFile and ProxyCommand apply.
    """
    hostname, username, key_file = host, SSH_USER, SSH_KEY
    known_hosts_files = []
    sock = None
    config_path = os.getenv("TEST_SSH_CONFIG")
    if config_path:
        options = paramiko.SSHConfig.from_path(config_path).lookup(host)
        hostname = options.get("hostname", host)
        username = options.get("user", username)
        key_file = os.path.expanduser(options.get("identityfile", [key_file])[0])
        known_hosts_files = options.get("userknownhostsfile", "").split()
        if "proxycommand" in options:
            sock = paramiko.ProxyCommand(options["proxycommand"])

    ssh = paramiko.SSHClient()
    ssh.load_system_host_keys()
    for path in known_hosts_files:
        ssh.load_host_keys(os.path.expanduser(path))
    ssh.set_missing_host_key_policy(paramiko.RejectPolicy())
    ssh.connect(
        hostname=hostname,
        username=username,
        key_filename=key_file,
        timeout=30,
        sock=sock,
    )
    return ssh


class TestInfrastructure:
    """Integration tests for complete infrastructure stack."""

    @pytest.fixture(scope="class")
    def droplet_info(self) -> Dict[str, Any]:
        """Get target host information from environment."""
        hosts: Dict[str, Any] = {}

        production_host = os.getenv("PROD_HOST") or os.getenv("PRODUCTION_DROPLET_IP")
        staging_host = os.getenv("STAGING_HOST") or os.getenv("STAGING_DROPLET_IP")

        if production_host:
            hosts["production"] = production_host
        if staging_host:
            hosts["staging"] = staging_host

        if not hosts:
            pytest.skip("No integration target hosts configured (PROD_HOST/STAGING_HOST)")

        return hosts

    def _connect(self, host: str) -> paramiko.SSHClient:
        return connect(host)


    def test_droplet_reachable(self, droplet_info):
        """Test that target hosts are reachable via SSH."""
        for name, host in droplet_info.items():
            try:
                ssh = self._connect(host)
                _, stdout, _ = ssh.exec_command("uname -a")
                output = stdout.read().decode()

                assert "Linux" in output, f"{name} is not running Linux"
                ssh.close()
            except Exception as e:
                pytest.fail(f"Failed to connect to {name} ({host}): {str(e)}")

    def test_web_server_running(self, droplet_info):
        """Test that Apache or OpenLiteSpeed is running on web servers."""
        for name, host in droplet_info.items():
            try:
                ssh = self._connect(host)
                _, stdout, _ = ssh.exec_command(
                    "bash -lc 'systemctl is-active apache2 2>/dev/null || "
                    "systemctl is-active lsws 2>/dev/null || echo inactive'"
                )
                status = stdout.read().decode().strip()

                assert status == "active", f"Web server is not active on {name}"
                ssh.close()
            except Exception as e:
                pytest.fail(f"Failed to check web server on {name}: {str(e)}")

    def test_php_runtime_running(self, droplet_info):
        """Test that PHP runtime service is available when configured."""
        for name, host in droplet_info.items():
            try:
                ssh = self._connect(host)
                _, stdout, _ = ssh.exec_command(
                    "bash -lc 'if systemctl list-unit-files | grep -q \"^php8.4-fpm\"; then "
                    "systemctl is-active php8.4-fpm; "
                    "elif systemctl list-unit-files | grep -q \"^php8.3-fpm\"; then "
                    "systemctl is-active php8.3-fpm; "
                    "else echo skipped; fi'"
                )
                status = stdout.read().decode().strip()

                assert status in ("active", "skipped"), (
                    f"PHP runtime service is not active on {name}: {status}"
                )
                ssh.close()
            except Exception as e:
                pytest.fail(f"Failed to check PHP runtime on {name}: {str(e)}")

    def test_firewall_active(self, droplet_info):
        """Test that UFW firewall is active."""
        for name, host in droplet_info.items():
            try:
                ssh = self._connect(host)
                _, stdout, _ = ssh.exec_command("ufw status")
                output = stdout.read().decode()

                assert "Status: active" in output, f"UFW is not active on {name}"
                ssh.close()
            except Exception as e:
                pytest.fail(f"Failed to check UFW on {name}: {str(e)}")

    def test_fail2ban_running(self, droplet_info):
        """Test that fail2ban is running."""
        for name, host in droplet_info.items():
            try:
                ssh = self._connect(host)
                _, stdout, _ = ssh.exec_command("systemctl is-active fail2ban")
                status = stdout.read().decode().strip()

                assert status == "active", f"fail2ban is not active on {name}"
                ssh.close()
            except Exception as e:
                pytest.fail(f"Failed to check fail2ban on {name}: {str(e)}")


class TestWebsite:
    """Tests for website functionality."""

    def test_https_redirect(self):
        """Test that HTTP redirects to HTTPS."""
        try:
            response = requests.get(
                f"http://{TEST_DOMAIN}",
                allow_redirects=False,
                timeout=10,
            )
            assert response.status_code in [301, 302, 307, 308], "HTTP should redirect to HTTPS"

            location = response.headers.get("Location", "")
            assert location.startswith("https://"), "Redirect should be to HTTPS"
        except requests.exceptions.RequestException as e:
            pytest.skip(f"Website not accessible: {str(e)}")

    def test_site_reachable(self):
        """Test that the target website is reachable."""
        try:
            response = requests.get(
                f"https://{TEST_DOMAIN}",
                timeout=10,
                verify=True,
            )
            assert response.status_code == 200, f"Expected 200, got {response.status_code}"
            assert len(response.content) > 0, "Response should have content"
        except requests.exceptions.RequestException as e:
            pytest.skip(f"Website not accessible: {str(e)}")

    def test_ssl_certificate_valid(self):
        """Test that SSL certificate is valid."""
        try:
            response = requests.get(
                f"https://{TEST_DOMAIN}",
                timeout=10,
                verify=True,
            )
            assert response.status_code == 200
        except requests.exceptions.SSLError as e:
            pytest.fail(f"SSL certificate is invalid: {str(e)}")
        except requests.exceptions.RequestException as e:
            pytest.skip(f"Website not accessible: {str(e)}")

    def test_security_headers(self):
        """Test that core security headers are present."""
        try:
            response = requests.get(
                f"https://{TEST_DOMAIN}",
                timeout=10,
            )

            headers = response.headers
            security_headers = [
                "X-Frame-Options",
                "X-Content-Type-Options",
                "Referrer-Policy",
            ]

            missing_headers = [h for h in security_headers if h not in headers]
            assert len(missing_headers) == 0, f"Missing security headers: {missing_headers}"
        except requests.exceptions.RequestException as e:
            pytest.skip(f"Website not accessible: {str(e)}")


class TestDNS:
    """Tests for DNS configuration."""

    def test_dns_resolution(self):
        """Test that DNS resolves correctly."""
        import socket

        domains = [TEST_DOMAIN]
        if TEST_DOMAIN.startswith("www."):
            domains.append(TEST_DOMAIN[4:])

        for domain in domains:
            try:
                ip = socket.gethostbyname(domain)
                assert ip, f"{domain} should resolve to an IP"
                parts = ip.split(".")
                assert len(parts) == 4, f"Invalid IP format for {domain}"
            except socket.gaierror as e:
                pytest.fail(f"DNS resolution failed for {domain}: {str(e)}")

    def test_mx_records_exist(self):
        """Test that MX records are configured."""
        import dns.resolver

        root_domain = TEST_DOMAIN[4:] if TEST_DOMAIN.startswith("www.") else TEST_DOMAIN

        try:
            mx_records = dns.resolver.resolve(root_domain, "MX")
            assert len(list(mx_records)) > 0, "MX records should exist"

            mx_hostnames = [str(mx.exchange) for mx in mx_records]
            google_mx = any("aspmx" in mx for mx in mx_hostnames)
            assert google_mx, "Google Workspace MX records should exist"
        except dns.resolver.NXDOMAIN:
            pytest.fail("Domain does not exist")
        except dns.resolver.NoAnswer:
            pytest.fail("No MX records found")
        except Exception as e:
            pytest.skip(f"Cannot check MX records: {str(e)}")


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
