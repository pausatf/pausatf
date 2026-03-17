#!/usr/bin/env bash
# Export Terraform outputs as Ansible extra-vars JSON
# Usage: ansible-playbook site.yml -e @<(ansible/scripts/tf-to-ansible.sh production)
set -euo pipefail

ENV="${1:?Usage: tf-to-ansible.sh <environment>}"
TF_DIR="$(cd "$(dirname "$0")/../../terraform/environments/${ENV}" && pwd)"

cd "$TF_DIR"

# Verify state exists
if ! terraform output -json >/dev/null 2>&1; then
    echo "ERROR: No Terraform state for environment '${ENV}'" >&2
    exit 1
fi

# Extract outputs and map to Ansible variable names
python3 -c "
import json, subprocess, sys

raw = subprocess.check_output(['terraform', 'output', '-json'], text=True)
tf = json.loads(raw)

ansible_vars = {
    # DigitalOcean droplet
    'digitalocean_droplet_id': tf.get('droplet_id', {}).get('value', ''),
    'digitalocean_public_ip': tf.get('droplet_ip', {}).get('value', ''),

    # Managed database (maps to Ansible vault vars)
    'managed_db_host': tf.get('database_host', {}).get('value', ''),
    'managed_db_private_host': tf.get('database_private_host', {}).get('value', ''),
    'managed_db_port': tf.get('database_port', {}).get('value', 25060),
    'managed_db_name': tf.get('database_name', {}).get('value', 'wordpress'),
    'managed_wp_db_user': tf.get('database_user', {}).get('value', ''),
    'managed_wp_db_password': tf.get('database_password', {}).get('value', ''),

    # Network
    'vpc_id': tf.get('vpc_id', {}).get('value', ''),
    'firewall_id': tf.get('firewall_id', {}).get('value', ''),
    'reserved_ip': tf.get('reserved_ip_address', {}).get('value', ''),
}

# Filter out empty values
ansible_vars = {k: v for k, v in ansible_vars.items() if v}

json.dump(ansible_vars, sys.stdout, indent=2)
print()
"
