# MySQL Role

This role installs and configures the MySQL database server.

## Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `mysql_managed` | Whether the MySQL instance is managed (e.g. by DigitalOcean). | `false` |
| `mysql_root_password` | Root password for MySQL (MUST be provided via vault). | `""` |
| `mysql_port` | Port for MySQL to listen on. | `3306` |
| `mysql_bind_address` | Address for MySQL to bind to. | `127.0.0.1` |

## Testing

This role uses [Molecule](https://molecule.readthedocs.io/) for testing. To run the tests, ensure you have Molecule and the Docker driver installed, then run:

```bash
cd ansible/roles/mysql
molecule test
```
