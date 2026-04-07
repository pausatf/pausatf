# Security Hardening Role

This role implements various security hardening measures for the server and WordPress environments.

## Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `hardening_ssh_permit_root_login` | Disables root login via SSH. | `no` |
| `hardening_ssh_max_auth_tries` | Limits SSH authentication attempts. | `3` |
| `hardening_ssh_allow_users` | Explicit list of users allowed to SSH into the server. | `[]` |
| `hardening_wp_docroot` | Base directory for WordPress file system hardening. | `/var/www/html` |
| `hardening_remove_packages` | List of unnecessary packages to remove for security. | `[snapd]` |
| `hardening_disable_services` | List of non-critical services to disable. | See `defaults/main.yml` |
| `hardening_sysctl` | A map of sysctl parameters for network security hardening. | See `defaults/main.yml` |

## Testing

This role uses [Molecule](https://molecule.readthedocs.io/) for testing. To run the tests, ensure you have Molecule and the Docker driver installed, then run:

```bash
cd ansible/roles/security_hardening
molecule test
```

## Key Hardening Measures

- **SSH Configuration:** Enforces stricter access controls.
- **Service Management:** Disables unnecessary services to reduce attack surface.
- **Package Management:** Removes known vulnerable or unneeded packages.
- **Kernel Hardening:** Applies security-focused sysctl parameters.
