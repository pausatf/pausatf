"""
Pytest configuration and fixtures for infrastructure tests.
"""

import os
import pytest
from typing import Dict, Any


@pytest.fixture(scope="session")
def test_config() -> Dict[str, Any]:
    """
    Load test configuration from environment variables.
    """
    return {
        "staging_ip": os.getenv("STAGING_DROPLET_IP", "64.227.85.73"),
        "production_ip": os.getenv("PRODUCTION_DROPLET_IP", "64.225.40.54"),
        "staging_domain": os.getenv("STAGING_DOMAIN", "stage.pausatf.org"),
        "production_domain": os.getenv("PRODUCTION_DOMAIN", "pausatf.org"),
        "ssh_key_path": os.getenv("SSH_KEY_PATH", "~/.ssh/id_rsa"),
        "ssh_user": os.getenv("SSH_USER", "root"),
    }


@pytest.fixture(scope="session")
def skip_integration_tests():
    """
    Skip integration tests if running in CI without proper credentials.
    """
    if os.getenv("CI") and not os.getenv("RUN_INTEGRATION_TESTS"):
        pytest.skip("Integration tests disabled in CI")


def pytest_configure(config):
    """
    Configure pytest with custom markers.
    """
    config.addinivalue_line(
        "markers", "integration: mark test as integration test"
    )
    config.addinivalue_line(
        "markers", "slow: mark test as slow running"
    )
    config.addinivalue_line(
        "markers", "requires_ssh: mark test as requiring SSH access"
    )
    config.addinivalue_line(
        "markers", "requires_dns: mark test as requiring DNS resolution"
    )


def pytest_collection_modifyitems(config, items):
    """
    Automatically add markers based on test names and locations.
    """
    for item in items:
        # Mark tests in integration directory
        if "integration" in str(item.fspath):
            item.add_marker(pytest.mark.integration)

        # Mark tests that use SSH
        if "ssh" in item.name.lower() or "droplet" in item.name.lower():
            item.add_marker(pytest.mark.requires_ssh)

        # Mark tests that check DNS
        if "dns" in item.name.lower():
            item.add_marker(pytest.mark.requires_dns)

        # Mark slow tests
        if "slow" in item.keywords:
            item.add_marker(pytest.mark.slow)
