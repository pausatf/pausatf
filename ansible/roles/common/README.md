# Common Role

This role provides basic configuration for all servers, including timezone settings and user management.

## Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `common_admin_users` | List of administrative users with sudo and www-data access. | See `defaults/main.yml` |
| `common_deploy_user` | The primary user used for deployments. | `deploy` |
| `common_timezone` | System timezone configuration. | `America/Los_Angeles` |

## Testing

This role uses [Molecule](https://molecule.readthedocs.io/) for testing. To run the tests, ensure you have Molecule and the Docker driver installed, then run:

```bash
cd ansible/roles/common
molecule test
```

## Roles and Dependencies

This role is typically applied first in the `site.yml` playbook to ensure baseline configuration.
