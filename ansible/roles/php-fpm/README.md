# PHP-FPM Role

This role installs and configures PHP-FPM, optimized for WordPress and high performance.

## Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `php_version` | The version of PHP to install. | `8.3` |
| `php_fpm_socket` | Path to the PHP-FPM socket. | Derived from `php_version` |
| `php_memory_limit` | PHP memory limit. | `256M` |
| `php_date_timezone` | Timezone for PHP configuration. | `America/Los_Angeles` |

## Testing

This role uses [Molecule](https://molecule.readthedocs.io/) for testing. To run the tests, ensure you have Molecule and the Docker driver installed, then run:

```bash
cd ansible/roles/php-fpm
molecule test
```
