#!/usr/bin/env python3
"""
End-to-end tests for the dev site (dev.pausatf.org).

Verifies the site works from an external perspective: HTTP responses,
redirects, content, cache headers, and protocol negotiation.

These tests hit the live site through Cloudflare. Set DEV_SITE_URL
to override the target. Uses --resolve to bypass local DNS issues.
"""

import os
import socket
import subprocess
import pytest


DEV_DOMAIN = os.getenv("DEV_DOMAIN", "dev.pausatf.org")
DEV_IP = os.getenv("DEV_DROPLET_IP", "REDACTED_DEV_IP")
DEV_URL = os.getenv("DEV_SITE_URL", f"https://{DEV_DOMAIN}")


def _curl(path, extra_args=None, use_cf=False):
    """Run curl and return (headers, body, http_code).

    Uses -D for headers and -o for body to avoid HTTP/2 splitting issues.
    By default hits the origin directly (--resolve + -k).
    Set use_cf=True to go through Cloudflare.
    """
    import tempfile

    url = f"{DEV_URL}{path}"
    hdr_file = tempfile.mktemp(suffix=".hdr")
    body_file = tempfile.mktemp(suffix=".body")

    cmd = [
        "curl", "-sL", "--max-time", "15",
        "-D", hdr_file, "-o", body_file,
        "-w", "%{http_code}",
    ]

    if use_cf:
        try:
            cf_ip = socket.gethostbyname(DEV_DOMAIN)
            cmd += ["--resolve", f"{DEV_DOMAIN}:443:{cf_ip}",
                    "--resolve", f"{DEV_DOMAIN}:80:{cf_ip}"]
        except socket.gaierror:
            pytest.skip(f"Cannot resolve {DEV_DOMAIN}")
    else:
        cmd += ["--resolve", f"{DEV_DOMAIN}:443:{DEV_IP}",
                "--resolve", f"{DEV_DOMAIN}:80:{DEV_IP}", "-k"]

    if extra_args:
        cmd += extra_args
    cmd.append(url)

    result = subprocess.run(cmd, capture_output=True, text=True, timeout=20)

    code = 0
    try:
        code = int(result.stdout.strip())
    except ValueError:
        pass

    headers = ""
    body = ""
    try:
        with open(hdr_file, "r") as f:
            headers = f.read()
    except FileNotFoundError:
        pass
    try:
        with open(body_file, "r") as f:
            body = f.read()
    except FileNotFoundError:
        pass
    finally:
        for f in (hdr_file, body_file):
            try:
                os.unlink(f)
            except OSError:
                pass

    return headers, body, code


# --- Front Page ---


@pytest.mark.smoke
class TestFrontPage:

    def test_front_page_returns_200(self):
        _, _, code = _curl("/")
        assert code == 200

    def test_front_page_has_content(self):
        _, body, _ = _curl("/")
        assert len(body) > 1000, f"Front page too small ({len(body)} bytes)"

    def test_front_page_is_html(self):
        headers, body, _ = _curl("/")
        assert "text/html" in headers
        assert "<!DOCTYPE html>" in body or "<html" in body

    def test_front_page_has_title(self):
        _, body, _ = _curl("/")
        assert "<title>" in body

    def test_front_page_wordpress_meta(self):
        _, body, _ = _curl("/")
        assert "wp-content" in body or "wordpress" in body.lower()


# --- WordPress Admin ---


@pytest.mark.smoke
class TestWPAdmin:

    def test_wp_login_accessible(self):
        headers, body, code = _curl("/wp-login.php")
        assert code == 200
        assert "user_login" in body or "log" in body

    def test_wp_admin_redirects_to_login(self):
        """wp-admin should redirect to wp-login.php when not authenticated."""
        headers, _, code = _curl("/wp-admin/", extra_args=["--max-redirs", "0", "-o", "/dev/null"])
        # Either 200 (on login page after redirect) or 302
        assert code in [200, 301, 302]


# --- RSS Feed ---


class TestRSSFeed:

    def test_rss_feed_returns_xml(self):
        headers, body, code = _curl("/?feed=rss2")
        assert code == 200
        assert "xml" in headers.lower() or "<?xml" in body


# --- Static Assets ---


class TestStaticAssets:

    def test_wp_includes_js_served(self):
        _, _, code = _curl("/wp-includes/js/jquery/jquery.min.js")
        assert code == 200

    def test_static_asset_has_content_type(self):
        headers, _, code = _curl("/wp-includes/js/jquery/jquery.min.js")
        assert code == 200
        assert "javascript" in headers.lower() or "application/" in headers


# --- HTTP/2 ---


class TestProtocol:

    def test_http2_negotiated(self):
        """Verify HTTP/2 is negotiated on the origin."""
        cmd = [
            "curl", "-sk", "--http2", "-o", "/dev/null",
            "-w", "%{http_version}",
            "--resolve", f"{DEV_DOMAIN}:443:{DEV_IP}",
            f"https://{DEV_DOMAIN}/",
        ]
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=10)
        assert result.stdout.strip() == "2", f"Expected HTTP/2, got HTTP/{result.stdout.strip()}"


# --- HTTPS ---


class TestHTTPS:

    def test_ssl_handshake_succeeds(self):
        """Verify TLS handshake completes on the origin."""
        cmd = [
            "curl", "-sk", "-o", "/dev/null", "-w", "%{ssl_verify_result}",
            "--resolve", f"{DEV_DOMAIN}:443:{DEV_IP}",
            f"https://{DEV_DOMAIN}/",
        ]
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=10)
        # 0 = ok, 18 = self-signed (expected for origin cert)
        assert result.stdout.strip() in ["0", "18"]

    def test_443_listening(self):
        """Verify port 443 is open."""
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(5)
        result = sock.connect_ex((DEV_IP, 443))
        sock.close()
        assert result == 0, "Port 443 not open"


# --- Cloudflare Layer ---


class TestCloudflare:

    def test_site_through_cloudflare(self):
        """Verify the site works through Cloudflare edge."""
        _, body, code = _curl("/", use_cf=True)
        assert code == 200
        assert len(body) > 1000

    def test_cloudflare_ssl_valid(self):
        """Verify Cloudflare edge SSL is trusted."""
        try:
            cf_ip = socket.gethostbyname(DEV_DOMAIN)
        except socket.gaierror:
            pytest.skip(f"Cannot resolve {DEV_DOMAIN}")

        cmd = [
            "curl", "-sL", "-o", "/dev/null", "-w", "%{http_code}",
            "--resolve", f"{DEV_DOMAIN}:443:{cf_ip}",
            f"https://{DEV_DOMAIN}/",
        ]
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=15)
        assert result.stdout.strip() == "200"


# --- DNS ---


class TestDNS:

    def test_dev_domain_resolves(self):
        """Check DNS via dig subprocess to avoid local resolver cache issues."""
        result = subprocess.run(
            ["dig", "+short", DEV_DOMAIN],
            capture_output=True, text=True, timeout=10,
        )
        ips = [l.strip() for l in result.stdout.splitlines() if l.strip()]
        assert len(ips) > 0, f"DNS resolution failed for {DEV_DOMAIN}"

    def test_dev_domain_resolves_to_cloudflare(self):
        """Proxied record should resolve to Cloudflare, not the origin."""
        result = subprocess.run(
            ["dig", "+short", DEV_DOMAIN],
            capture_output=True, text=True, timeout=10,
        )
        ips = [l.strip() for l in result.stdout.splitlines() if l.strip()]
        if not ips:
            pytest.skip(f"Cannot resolve {DEV_DOMAIN}")
        assert DEV_IP not in ips, (
            f"{DEV_DOMAIN} resolves to origin IP {DEV_IP} instead of Cloudflare; "
            "DNS record may not be proxied"
        )


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
