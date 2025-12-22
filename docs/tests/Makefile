.PHONY: help test test-terraform test-ansible test-integration clean install

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
RED := \033[0;31m
NC := \033[0m # No Color

help: ## Show this help message
	@echo '$(BLUE)Available targets:$(NC)'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

install: ## Install all test dependencies
	@echo "$(BLUE)Installing test dependencies...$(NC)"
	pip install -r ../requirements.txt
	pip install pytest pytest-cov pytest-xdist
	pip install molecule[docker] molecule-plugins[docker]
	pip install terraform-compliance
	@echo "$(GREEN)Dependencies installed successfully$(NC)"

test: test-terraform test-ansible test-integration ## Run all tests

test-terraform: ## Run Terraform tests
	@echo "$(BLUE)Running Terraform compliance tests...$(NC)"
	cd ../terraform/environments/production && \
		terraform init -backend=false && \
		terraform plan -out=tfplan.binary && \
		terraform show -json tfplan.binary > tfplan.json && \
		terraform-compliance -f ../../../tests/terraform/compliance/digitalocean.feature -p tfplan.json && \
		terraform-compliance -f ../../../tests/terraform/compliance/cloudflare.feature -p tfplan.json
	@echo "$(GREEN)Terraform tests passed$(NC)"

test-ansible: ## Run Ansible role tests
	@echo "$(BLUE)Running Ansible Molecule tests...$(NC)"
	cd ../ansible/roles/common && molecule test
	cd ../ansible/roles/wordpress && molecule test
	@echo "$(GREEN)Ansible tests passed$(NC)"

test-ansible-quick: ## Run Ansible tests without destroying containers
	@echo "$(BLUE)Running Ansible Molecule tests (quick mode)...$(NC)"
	cd ../ansible/roles/common && molecule converge && molecule verify
	cd ../ansible/roles/wordpress && molecule converge && molecule verify
	@echo "$(GREEN)Ansible quick tests passed$(NC)"

test-integration: ## Run integration tests
	@echo "$(BLUE)Running integration tests...$(NC)"
	cd integration && pytest test_infrastructure.py -v
	@echo "$(GREEN)Integration tests passed$(NC)"

test-coverage: ## Run tests with coverage report
	@echo "$(BLUE)Running tests with coverage...$(NC)"
	cd integration && pytest test_infrastructure.py -v --cov=. --cov-report=html --cov-report=term
	@echo "$(GREEN)Coverage report generated in integration/htmlcov/$(NC)"

validate-terraform: ## Validate Terraform syntax
	@echo "$(BLUE)Validating Terraform...$(NC)"
	cd ../terraform && terraform fmt -check -recursive
	find ../terraform -type f -name "*.tf" -exec dirname {} \; | \
		sort -u | \
		xargs -I {} sh -c 'echo "Validating {}" && cd {} && terraform init -backend=false && terraform validate'
	@echo "$(GREEN)Terraform validation passed$(NC)"

lint-ansible: ## Lint Ansible playbooks and roles
	@echo "$(BLUE)Linting Ansible...$(NC)"
	cd ../ansible && ansible-lint playbooks/ roles/
	@echo "$(GREEN)Ansible linting passed$(NC)"

clean: ## Clean test artifacts
	@echo "$(BLUE)Cleaning test artifacts...$(NC)"
	find . -type d -name "__pycache__" -exec rm -rf {} + 2>/dev/null || true
	find . -type d -name ".pytest_cache" -exec rm -rf {} + 2>/dev/null || true
	find . -type d -name "htmlcov" -exec rm -rf {} + 2>/dev/null || true
	find . -type f -name "*.pyc" -delete
	find . -type f -name ".coverage" -delete
	find ../terraform -type f -name "tfplan.binary" -delete
	find ../terraform -type f -name "tfplan.json" -delete
	cd ../ansible/roles/common && molecule destroy || true
	cd ../ansible/roles/wordpress && molecule destroy || true
	@echo "$(GREEN)Cleaned successfully$(NC)"

smoke-test: ## Run quick smoke tests
	@echo "$(BLUE)Running smoke tests...$(NC)"
	@echo "Testing production website..."
	@curl -sf https://pausatf.org > /dev/null && echo "$(GREEN)✓ Production site OK$(NC)" || echo "$(RED)✗ Production site FAILED$(NC)"
	@echo "Testing staging website..."
	@curl -sf http://stage.pausatf.org > /dev/null && echo "$(GREEN)✓ Staging site OK$(NC)" || echo "$(RED)✗ Staging site FAILED$(NC)"
	@echo "Testing DNS..."
	@dig +short pausatf.org > /dev/null && echo "$(GREEN)✓ DNS OK$(NC)" || echo "$(RED)✗ DNS FAILED$(NC)"

docker-test: ## Run tests in Docker container
	@echo "$(BLUE)Running tests in Docker...$(NC)"
	docker run --rm -v $(PWD)/..:/workspace -w /workspace/tests \
		python:3.11-slim sh -c "pip install -r ../requirements.txt && make test"
	@echo "$(GREEN)Docker tests completed$(NC)"

ci-test: ## Run tests as they would run in CI
	@echo "$(BLUE)Running CI tests locally...$(NC)"
	make lint-ansible
	make validate-terraform
	make test-ansible-quick
	@echo "$(GREEN)All CI tests passed$(NC)"
