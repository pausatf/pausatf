# Local OpenLiteSpeed database upgrade

The OpenLiteSpeed Compose stack uses a persistent `db-data` volume. Before the
first MariaDB 13.0 startup, take and verify a logical backup with the running
11.8 image and stop the stack. Keep a separate copy of the stopped database
volume for rollback; do not start 11.8 against a volume already upgraded by 13.0.

The stack sets `MARIADB_AUTO_UPGRADE=1` so the official image runs the required
system-table upgrade when an existing database is opened. After startup, inspect
the database logs and verify WordPress reads and writes before removing backups.
This upgrades only the local development stack.

See the [official image environment variables](https://mariadb.com/docs/server/server-management/automated-mariadb-deployment-and-administration/docker-and-mariadb/mariadb-server-docker-official-image-environment-variables).
