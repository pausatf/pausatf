# DigitalOcean Managed Database Upgrades

## Production MySQL 8.0 to 8.4 LTS

The production `pausatf-prod-db` cluster was checked on 2026-09-27. It was
online on MySQL 8, Standard Edition, a single `db-s-1vcpu-1gb` node in `sfo2`.
DigitalOcean listed MySQL 8.4 as the only available MySQL version. The maintenance
window observed on that date was Sunday at 02:00 UTC; verify it again before execution.

DigitalOcean announced that it will begin forced upgrades of Managed MySQL 8.0
clusters to 8.4 on 2026-10-30. MySQL 8.4 is the supported LTS line. The
production Terraform stack now declares `8.4`; the DigitalOcean Terraform
provider implements a version change by calling the in-place major version
upgrade API. Review the HCP Terraform plan and verify it does not propose
resource replacement before applying it. Do not combine the major upgrade
with other production database changes.

No database upgrade was run as part of this change. Before the production
apply, complete and record these gates:

1. Check MySQL 8.4 compatibility, including removed features, authentication
   plugins, SQL modes, and the WordPress database client and active plugins.
   Run the MySQL Shell upgrade checker and resolve reported incompatibilities.
2. Confirm a recent managed backup and offsite age-encrypted database and legacy
   artifacts. The database backup script uploads `prod-db-*.sql.gz.age` and
   `prod-legacy-*.tar.gz.age` under `s3://pausatf/backups/prod/`; it does not
   produce a recovery manifest. If the merged recovery wrapper is installed (verify the systemd drop-in and journal),
   separately inspect its manifest and every referenced object.
3. Decrypt and restore the selected database backup in an isolated environment,
   then test WordPress reads and writes with MySQL 8.4. Record artifact names,
   hashes, restore results, and a rollback plan before approving production apply.
4. Verify the DigitalOcean maintenance window and review an upgrade-only HCP
   Terraform plan with no database replacement or unrelated changes.

The 2026-09-27 backup observations do not prove recoverability or current freshness;
repeat these checks before applying. The production upgrade remains blocked until
the compatibility and restore gates pass.

After the upgrade, verify the cluster is online and reports MySQL 8.4, check
WordPress can read and write the production database, and confirm the next
encrypted backup succeeds. DigitalOcean retains managed database backups for
seven days and point-in-time recovery for seven days; the encrypted Spaces
artifacts provide the separate recovery path.

References:

- [DigitalOcean MySQL version support and forced-upgrade notice](https://docs.digitalocean.com/products/databases/mysql/)
- [DigitalOcean MySQL backup and restore behavior](https://docs.digitalocean.com/products/databases/mysql/how-to/restore-from-backups/)
- [DigitalOcean Terraform database cluster resource](https://docs.digitalocean.com/reference/terraform/reference/resources/database_cluster/)
- [MySQL LTS release model](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html)
