#!/bin/bash
#
# Terraform Wrapper Script
# Provides safe execution of Terraform commands with validation
#

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

# Default values
ENVIRONMENT="${1:-}"
ACTION="${2:-plan}"

# Usage function
usage() {
    cat << EOF
Usage: $0 <environment> <action>

Arguments:
  environment    Environment to target (production, staging, dev, cloudflare, github)
  action         Terraform action (plan, apply, destroy, output, init, validate)

Examples:
  $0 production plan
  $0 staging apply
  $0 production output
  $0 cloudflare plan
  $0 github apply

EOF
    exit 1
}

# Validate environment
validate_environment() {
    if [[ ! "$ENVIRONMENT" =~ ^(production|staging|dev|cloudflare|github)$ ]]; then
        echo -e "${RED}Error: Invalid environment. Must be one of: production, staging, dev, cloudflare, github${NC}"
        usage
    fi
}

# Validate action
validate_action() {
    if [[ ! "$ACTION" =~ ^(plan|apply|destroy|output|init|validate)$ ]]; then
        echo -e "${RED}Error: Invalid action. Must be one of: plan, apply, destroy, output, init, validate${NC}"
        usage
    fi
}

# Check required tools
check_dependencies() {
    local deps=("terraform" "jq")
    for dep in "${deps[@]}"; do
        if ! command -v "$dep" &> /dev/null; then
            echo -e "${RED}Error: Required dependency '$dep' not found${NC}"
            exit 1
        fi
    done
}

# Check for required environment variables
check_env_vars() {
    local required_vars=("DIGITALOCEAN_TOKEN" "CLOUDFLARE_API_TOKEN")
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var:-}" ]]; then
            echo -e "${YELLOW}Warning: Environment variable $var is not set${NC}"
        fi
    done
}

# Advisory lock — prevents concurrent local runs per environment.
# CI relies on GitHub Actions concurrency groups instead.
LOCK_DIR="${HOME}/.terraform-locks"
LOCK_FILE="${LOCK_DIR}/pausatf-${ENVIRONMENT:-unknown}.lock"

acquire_lock() {
    mkdir -p "$LOCK_DIR"
    if [ -f "$LOCK_FILE" ]; then
        local lock_pid
        lock_pid=$(cat "$LOCK_FILE" 2>/dev/null || echo "unknown")
        local lock_age
        lock_age=$(( $(date +%s) - $(stat -f %m "$LOCK_FILE" 2>/dev/null || stat -c %Y "$LOCK_FILE" 2>/dev/null || echo 0) ))
        if [ "$lock_age" -gt 3600 ]; then
            echo -e "${YELLOW}Warning: Stale lock found (${lock_age}s old, PID $lock_pid). Removing.${NC}"
            rm -f "$LOCK_FILE"
        else
            echo -e "${RED}Error: Terraform lock held for $ENVIRONMENT (PID $lock_pid, ${lock_age}s ago)${NC}"
            echo -e "${RED}If this is stale, remove: rm $LOCK_FILE${NC}"
            exit 1
        fi
    fi
    echo $$ > "$LOCK_FILE"
    trap 'release_lock' EXIT
}

release_lock() {
    rm -f "$LOCK_FILE"
}

# Run terraform command
run_terraform() {
    local tf_dir="$ROOT_DIR/terraform/environments/$ENVIRONMENT"

    echo -e "${GREEN}==>${NC} Running Terraform $ACTION for $ENVIRONMENT environment"
    echo -e "${GREEN}==>${NC} Working directory: $tf_dir"

    cd "$tf_dir" || exit 1

    case "$ACTION" in
        init)
            terraform init
            ;;
        validate)
            terraform validate
            ;;
        plan)
            terraform plan -out=tfplan
            echo -e "${YELLOW}Plan saved to tfplan. Review carefully before applying.${NC}"
            ;;
        apply)
            if [[ ! -f "tfplan" ]]; then
                echo -e "${YELLOW}No plan file found. Running plan first...${NC}"
                terraform plan -out=tfplan
            fi
            echo -e "${YELLOW}About to apply changes to $ENVIRONMENT. Press Ctrl+C to cancel.${NC}"
            sleep 5
            terraform apply tfplan
            rm -f tfplan
            ;;
        destroy)
            echo -e "${RED}WARNING: About to destroy resources in $ENVIRONMENT!${NC}"
            echo -e "${RED}Press Ctrl+C within 10 seconds to cancel...${NC}"
            sleep 10
            terraform destroy
            ;;
        output)
            terraform output
            ;;
    esac
}

# Main execution
main() {
    if [[ -z "$ENVIRONMENT" ]]; then
        usage
    fi

    validate_environment
    validate_action
    check_dependencies
    check_env_vars
    acquire_lock
    run_terraform

    echo -e "${GREEN}==>${NC} Terraform $ACTION completed successfully"
}

main "$@"
