# DigitalOcean Managed Database Upgrades

## Production MySQL 8.0 to 8.4 LTS

The production `pausatf-prod-db` cluster was checked on 2026-09-27. It was
online on MySQL 8, Standard Edition, a single `db-s-1vcpu-1gb` node in `sfo2`.
DigitalOcean listed MySQL 8.4 as the only available MySQL version. Its current
maintenance window is Sunday at 02:00 UTC.

DigitalOcean announced that it will begin forced upgrades of Managed MySQL 8.0
clusters to 8.4 on 2026-10-30. MySQL 8.4 is the supported LTS line. The
production Terraform stack now declares `8.4`; the DigitalOcean Terraform
provider implements a version change by calling the in-place major version
upgrade API. Review the HCP Terraform plan and verify it does not propose
resource replacement before applying it. Do not combine the major upgrade
with other production database changes.

No database upgrade was run as part of this change. Before the production
apply, confirm a recent managed backup and a successful offsite encrypted
recovery set. Both were current when checked on 2026-09-27: DigitalOcean had
daily managed backups through that date, and the host recovery timer completed
successfully with its recovery manifest and encrypted objects uploaded to
Spaces. Verify the maintenance window in DigitalOcean before applying.

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
