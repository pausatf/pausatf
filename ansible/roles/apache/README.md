# Apache Role

This role installs and configures the Apache web server, optimized for WordPress.

## Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `apache_mpm` | Apache MPM module to use. | `event` |
| `apache_modules` | List of Apache modules to enable. | See `defaults/main.yml` |
| `apache_block_xmlrpc` | Whether to block XML-RPC requests. | `true` |
| `apache_main_docroot` | Primary document root for the web server. | `/var/www/html` |

## Testing

This role uses [Molecule](https://molecule.readthedocs.io/) for testing. To run the tests, ensure you have Molecule and the Docker driver installed, then run:

```bash
cd ansible/roles/apache
molecule test
```
