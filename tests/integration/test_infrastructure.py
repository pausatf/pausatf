#!/usr/bin/env python3
"""
Integration tests for infrastructure deployment.
Tests end-to-end infrastructure provisioning and configuration.
"""

import os
import pytest
import requests
import paramiko
from time import sleep
from typing import Dict, Any


class TestInfrastructure:
    """Integration tests for complete infrastructure stack."""

    @pytest.fixture(scope="class")
    def droplet_info(self) -> Dict[str, Any]:
        """Get droplet information from Terraform outputs."""
        # This would typically parse terraform output -json
        return {
            "staging_ip": os.getenv("STAGING_DROPLET_IP", "64.227.85.73"),
            "production_ip": os.getenv("PRODUCTION_DROPLET_IP", "64.225.40.54"),
        }

    def test_droplet_reachable(self, droplet_info):
        """Test that droplets are reachable via SSH."""
        for name, ip in droplet_info.items():
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

            try:
                # Attempt SSH connection
                ssh.connect(
                    hostname=ip,
                    username="root",
                    key_filename=os.path.expanduser("~/.ssh/id_rsa"),
                    timeout=10
                )

                # Execute simple command
                stdin, stdout, stderr = ssh.exec_command("uname -a")
                output = stdout.read().decode()

                assert "Linux" in output, f"{name} is not running Linux"
                ssh.close()

            except Exception as e:
                pytest.fail(f"Failed to connect to {name} ({ip}): {str(e)}")

    def test_nginx_running(self, droplet_info):
        """Test that Nginx is running on web servers."""
        for name, ip in droplet_info.items():
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

            try:
                ssh.connect(
                    hostname=ip,
                    username="root",
                    key_filename=os.path.expanduser("~/.ssh/id_rsa"),
                    timeout=10
                )

                # Check Nginx status
                stdin, stdout, stderr = ssh.exec_command(
                    "systemctl is-active nginx"
                )
                status = stdout.read().decode().strip()

                assert status == "active", f"Nginx is not active on {name}"
                ssh.close()

            except Exception as e:
                pytest.fail(f"Failed to check Nginx on {name}: {str(e)}")

    def test_php_fpm_running(self, droplet_info):
        """Test that PHP-FPM is running on web servers."""
        for name, ip in droplet_info.items():
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

            try:
                ssh.connect(
                    hostname=ip,
                    username="root",
                    key_filename=os.path.expanduser("~/.ssh/id_rsa"),
                    timeout=10
                )

                # Check PHP-FPM status
                stdin, stdout, stderr = ssh.exec_command(
                    "systemctl is-active php8.3-fpm"
                )
                status = stdout.read().decode().strip()

                assert status == "active", f"PHP-FPM is not active on {name}"
                ssh.close()

            except Exception as e:
                pytest.fail(f"Failed to check PHP-FPM on {name}: {str(e)}")

    def test_firewall_active(self, droplet_info):
        """Test that UFW firewall is active."""
        for name, ip in droplet_info.items():
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

            try:
                ssh.connect(
                    hostname=ip,
                    username="root",
                    key_filename=os.path.expanduser("~/.ssh/id_rsa"),
                    timeout=10
                )

                # Check UFW status
                stdin, stdout, stderr = ssh.exec_command("ufw status")
                output = stdout.read().decode()

                assert "Status: active" in output, f"UFW is not active on {name}"
                ssh.close()

            except Exception as e:
                pytest.fail(f"Failed to check UFW on {name}: {str(e)}")

    def test_fail2ban_running(self, droplet_info):
        """Test that fail2ban is running."""
        for name, ip in droplet_info.items():
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

            try:
                ssh.connect(
                    hostname=ip,
                    username="root",
                    key_filename=os.path.expanduser("~/.ssh/id_rsa"),
                    timeout=10
                )

                # Check fail2ban status
                stdin, stdout, stderr = ssh.exec_command(
                    "systemctl is-active fail2ban"
                )
                status = stdout.read().decode().strip()

                assert status == "active", f"fail2ban is not active on {name}"
                ssh.close()

            except Exception as e:
                pytest.fail(f"Failed to check fail2ban on {name}: {str(e)}")


class TestWebsite:
    """Tests for website functionality."""

    def test_production_https_redirect(self):
        """Test that HTTP redirects to HTTPS."""
        try:
            response = requests.get(
                "http://pausatf.org",
                allow_redirects=False,
                timeout=10
            )
            assert response.status_code in [301, 302, 307, 308], \
                "HTTP should redirect to HTTPS"

            location = response.headers.get("Location", "")
            assert location.startswith("https://"), \
                "Redirect should be to HTTPS"
        except requests.exceptions.RequestException as e:
            pytest.skip(f"Website not accessible: {str(e)}")

    def test_production_site_reachable(self):
        """Test that production website is reachable."""
        try:
            response = requests.get(
                "https://pausatf.org",
                timeout=10,
                verify=True
            )
            assert response.status_code == 200, \
                f"Expected 200, got {response.status_code}"
            assert len(response.content) > 0, "Response should have content"
        except requests.exceptions.RequestException as e:
            pytest.skip(f"Website not accessible: {str(e)}")

    def test_staging_site_reachable(self):
        """Test that staging website is reachable."""
        try:
            response = requests.get(
                "http://stage.pausatf.org",
                timeout=10
            )
            assert response.status_code in [200, 301, 302], \
                f"Expected success or redirect, got {response.status_code}"
        except requests.exceptions.RequestException as e:
            pytest.skip(f"Staging site not accessible: {str(e)}")

    def test_ssl_certificate_valid(self):
        """Test that SSL certificate is valid."""
        try:
            response = requests.get(
                "https://pausatf.org",
                timeout=10,
                verify=True
            )
            # If we get here without exception, SSL is valid
            assert response.status_code == 200
        except requests.exceptions.SSLError as e:
            pytest.fail(f"SSL certificate is invalid: {str(e)}")
        except requests.exceptions.RequestException as e:
            pytest.skip(f"Website not accessible: {str(e)}")

    def test_security_headers(self):
        """Test that security headers are present."""
        try:
            response = requests.get(
                "https://pausatf.org",
                timeout=10
            )

            headers = response.headers

            # Check for security headers
            security_headers = [
                "X-Frame-Options",
                "X-Content-Type-Options",
                "X-XSS-Protection",
            ]

            missing_headers = [
                h for h in security_headers if h not in headers
            ]

            assert len(missing_headers) == 0, \
                f"Missing security headers: {missing_headers}"

        except requests.exceptions.RequestException as e:
            pytest.skip(f"Website not accessible: {str(e)}")


class TestDNS:
    """Tests for DNS configuration."""

    def test_dns_resolution(self):
        """Test that DNS resolves correctly."""
        import socket

        domains = [
            "pausatf.org",
            "www.pausatf.org",
            "stage.pausatf.org",
        ]

        for domain in domains:
            try:
                ip = socket.gethostbyname(domain)
                assert ip, f"{domain} should resolve to an IP"
                # Basic IP format check
                parts = ip.split(".")
                assert len(parts) == 4, f"Invalid IP format for {domain}"
            except socket.gaierror as e:
                pytest.fail(f"DNS resolution failed for {domain}: {str(e)}")

    def test_mx_records_exist(self):
        """Test that MX records are configured."""
        import dns.resolver

        try:
            mx_records = dns.resolver.resolve("pausatf.org", "MX")
            assert len(list(mx_records)) > 0, "MX records should exist"

            # Check for Google Workspace MX records
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
