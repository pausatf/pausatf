# Check Mode (Dry Run) Support

All roles in this project support Ansible check mode (`--check`).

## Usage

```bash
# Dry run - see what would change without making changes
ansible-playbook -i inventory/hosts.yml site.yml --check --diff

# Dry run specific tags
ansible-playbook -i inventory/hosts.yml site.yml --check --diff --tags wordpress

# Dry run against specific environment
ansible-playbook -i inventory/hosts.yml site.yml --check --diff -l production
```

## Notes

- `command` and `shell` tasks use `check_mode: false` where idempotency
  checks are built into the task logic (e.g., WP-CLI `is-installed` checks)
- Database operations are skipped in check mode
- File operations (template, copy, file) fully support check mode
