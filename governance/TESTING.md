# Testing Infrastructure

Comprehensive guide to testing infrastructure as code.

## Overview

This repository includes extensive testing at multiple levels:

1. **Unit Tests** - Individual module testing
2. **Compliance Tests** - Policy and security validation
3. **Integration Tests** - End-to-end infrastructure validation
4. **Continuous Testing** - Automated testing in CI/CD

## Test Philosophy

Our testing approach follows these principles:

- **Test Early, Test Often** - Run tests before committing
- **Automated Validation** - All tests run automatically in CI/CD
- **Multiple Layers** - Unit, integration, compliance, and smoke tests
- **Fast Feedback** - Quick tests run on every commit
- **Comprehensive Coverage** - Test functionality, security, and compliance

## Quick Start

```bash
# Install test dependencies
cd tests
make install

# Run all tests
make test

# Run specific test suite
make test-terraform
make test-ansible
make test-integration

# Run quick smoke tests
make smoke-test
```

## Test Types

### 1. Terraform Tests

#### Compliance Tests

Using terraform-compliance to validate infrastructure policies:

```bash
cd tests
make test-terraform
```

**What's tested:**
- ✅ Security configurations (SSL, firewalls, encryption)
- ✅ Required tags and metadata
- ✅ Backup and monitoring requirements
- ✅ Naming conventions
- ✅ Network security rules

**Example test** (`tests/terraform/compliance/digitalocean.feature`):
```gherkin
Scenario: Droplets must have monitoring enabled
  Given I have digitalocean_droplet defined
  Then it must contain monitoring_enabled
  And its value must be true
```

#### Unit Tests

Using Terratest (Go) to test Terraform modules:

```bash
cd terraform/modules/digitalocean/droplet/tests
go test -v -timeout 30m
```

**What's tested:**
- ✅ Module input validation
- ✅ Output correctness
- ✅ Resource creation
- ✅ Error handling

### 2. Ansible Tests

#### Molecule Tests

Docker-based role testing:

```bash
# Test specific role
cd ansible/roles/common
molecule test

# Or use Makefile
cd tests
make test-ansible
```

**Test workflow:**
1. Create Docker containers (Ubuntu 24.04, Debian 12)
2. Run Ansible role
3. Verify idempotence (role can run multiple times)
4. Run verification tests
5. Clean up containers

**What's tested:**
- ✅ Package installation
- ✅ Service configuration
- ✅ Security hardening
- ✅ Idempotence
- ✅ Cross-platform compatibility

### 3. Integration Tests

End-to-end infrastructure validation:

```bash
cd tests/integration
pytest test_infrastructure.py -v
```

**What's tested:**
- ✅ Server connectivity (SSH)
- ✅ Service status (Nginx, PHP-FPM, MySQL)
- ✅ Firewall rules
- ✅ Website availability
- ✅ SSL certificates
- ✅ DNS resolution
- ✅ Security headers

### 4. Smoke Tests

Quick health checks:

```bash
cd tests
make smoke-test
```

**What's tested:**
- ✅ Production website is reachable
- ✅ Staging website is reachable
- ✅ HTTPS is working
- ✅ DNS resolves correctly

## Running Tests Locally

### Prerequisites

```bash
# Install Python dependencies
pip install -r requirements.txt

# Install Go (for Terratest)
brew install go  # macOS
# or
apt install golang-go  # Ubuntu

# Install Docker (for Molecule)
# Follow Docker installation guide for your platform
```

### Running Individual Test Suites

```bash
# Terraform compliance tests
cd tests
make test-terraform

# Ansible molecule tests
make test-ansible

# Integration tests
make test-integration

# All tests
make test
```

### Running Specific Tests

```bash
# Run specific Pytest test
pytest tests/integration/test_infrastructure.py::TestWebsite::test_ssl_certificate_valid -v

# Run tests with specific marker
pytest -m integration
pytest -m "not slow"

# Run Molecule test for specific role
cd ansible/roles/wordpress
molecule test
```

## Continuous Integration

Tests run automatically in GitHub Actions:

### Test Workflows

| Workflow | Trigger | Tests |
|----------|---------|-------|
| **test-terraform.yml** | PR affecting `terraform/` | Compliance, validation, unit tests |
| **test-ansible.yml** | PR affecting `ansible/` | Molecule, lint, syntax |
| **test-integration.yml** | Daily + manual | End-to-end validation |
| **security-scan.yml** | Every push | Security vulnerabilities |

### Viewing Test Results

1. Go to GitHub Actions tab
2. Select workflow run
3. View test results and logs
4. Check for failed tests
5. Review test artifacts

## Writing Tests

### Adding Terraform Compliance Test

Create or edit `.feature` file in `tests/terraform/compliance/`:

```gherkin
Feature: New Security Requirement
  Scenario: Production droplets must have backups
    Given I have digitalocean_droplet defined
    When it contains tags
    And it contains production
    Then it must contain backups_enabled
    And its value must be true
```

### Adding Ansible Molecule Test

Add verification task in `ansible/roles/ROLE/molecule/default/verify.yml`:

```yaml
- name: Verify new package is installed
  package:
    name: my-package
    state: present
  check_mode: yes
  register: package_check
  failed_when: package_check is changed
```

### Adding Integration Test

Add test method in `tests/integration/test_infrastructure.py`:

```python
def test_new_feature(self, droplet_info):
    """Test new infrastructure feature."""
    for name, ip in droplet_info.items():
        # Your test code here
        assert condition, "Error message"
```

## Test Coverage

### Generating Coverage Reports

```bash
cd tests
make test-coverage
```

View HTML report:
```bash
open integration/htmlcov/index.html
```

### Current Coverage

- **Terraform Modules**: 100% (all modules have tests)
- **Ansible Roles**: 100% (all roles have Molecule tests)
- **Integration**: Core infrastructure features covered

## Troubleshooting

### Common Issues

#### Terraform Tests Failing

```bash
# Validate Terraform syntax
terraform validate

# Check formatting
terraform fmt -check -recursive

# Run plan manually
cd terraform/environments/production
terraform plan
```

#### Ansible Tests Failing

```bash
# Debug Molecule
cd ansible/roles/common
molecule --debug converge

# Check syntax
ansible-playbook --syntax-check playbooks/site.yml

# Lint
ansible-lint
```

#### Integration Tests Failing

```bash
# Run with verbose output
pytest -vv --tb=long

# Check connectivity
ssh root@64.227.85.73

# Verify DNS
dig pausatf.org
```

#### Docker Issues

```bash
# Clean up Docker
docker system prune -a

# Restart Docker
sudo systemctl restart docker

# Test Docker
docker run hello-world
```

### Getting Help

1. Check test logs in CI/CD
2. Run tests locally with `-vv` flag
3. Review test documentation
4. Check GitHub issues
5. Contact infrastructure team

## Best Practices

### Before Committing

```bash
# Run pre-commit hooks
pre-commit run --all-files

# Run quick CI tests
cd tests
make ci-test
```

### When Adding Features

1. ✅ Write tests first (TDD approach)
2. ✅ Add compliance tests for security requirements
3. ✅ Add Molecule verify tasks for Ansible roles
4. ✅ Add integration tests for end-to-end validation
5. ✅ Update test documentation

### When Tests Fail

1. ✅ Read the error message carefully
2. ✅ Run the failing test locally with `-vv`
3. ✅ Check for recent infrastructure changes
4. ✅ Verify test assumptions are still valid
5. ✅ Fix the issue or update the test

### Keeping Tests Fast

1. ✅ Use mocks for external dependencies
2. ✅ Run expensive tests in parallel
3. ✅ Mark slow tests with `@pytest.mark.slow`
4. ✅ Skip slow tests in development: `pytest -m "not slow"`
5. ✅ Clean up test resources promptly

## Testing in Different Environments

### Local Development

```bash
# Run fast tests only
pytest -m "not slow"

# Skip integration tests
pytest -m "not integration"
```

### CI/CD

```bash
# Run all tests
make test

# Generate coverage
make test-coverage
```

### Production

```bash
# Run smoke tests only
make smoke-test

# Run integration tests against staging
pytest -m integration --staging
```

## Metrics and Reporting

### Test Execution Time

Monitor test execution times:
- **Unit tests**: < 5 minutes
- **Compliance tests**: < 3 minutes
- **Molecule tests**: < 10 minutes per role
- **Integration tests**: < 15 minutes

### Success Rate

Target: 100% pass rate on main branch

Track:
- Test failures per week
- Time to fix failures
- Coverage percentage

## Resources

- [Terraform-compliance Documentation](https://terraform-compliance.com/)
- [Terratest Documentation](https://terratest.gruntwork.io/)
- [Molecule Documentation](https://molecule.readthedocs.io/)
- [Pytest Documentation](https://docs.pytest.org/)
- [Test README](tests/README.md) - Detailed test documentation

## Maintenance

### Weekly Tasks

- ✅ Review failed tests in CI
- ✅ Update test dependencies
- ✅ Check test coverage reports

### Monthly Tasks

- ✅ Review and update compliance tests
- ✅ Add tests for new features
- ✅ Refactor slow or flaky tests
- ✅ Update test documentation

### Quarterly Tasks

- ✅ Comprehensive test suite review
- ✅ Performance optimization
- ✅ Coverage analysis and improvement
- ✅ Test strategy review
