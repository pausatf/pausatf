# Database Maintenance Guide

**Document ID:** 11-database-maintenance
**Date:** 2025-12-21
**Status:** Active
**Priority:** High

## Overview

This document outlines database maintenance procedures for PAUSATF infrastructure, including the critical primary key requirements for DigitalOcean MySQL clusters.

## Table of Contents

1. [Primary Keys Requirement](#primary-keys-requirement)
2. [Recent Fixes](#recent-fixes)
3. [Monitoring & Verification](#monitoring--verification)
4. [Preventive Measures](#preventive-measures)
5. [Database Connection Information](#database-connection-information)
6. [Common Maintenance Tasks](#common-maintenance-tasks)

---

## Primary Keys Requirement

### Why Primary Keys are Critical

DigitalOcean MySQL clusters **require all tables to have primary keys** for proper replication functionality. Tables without primary keys can cause:

- **Service replication issues** - Replication lag or failures
- **Performance degradation** - Slower queries and updates
- **Data loss risk** - Especially for tables with >5,000 rows
- **Cluster instability** - Can affect overall database availability

### DigitalOcean Requirements

- All tables **must** have a PRIMARY KEY constraint
- UNIQUE constraints alone are **not sufficient**
- Tables exceeding 5,000 rows without primary keys are at **critical risk**
- The system variable `sql_require_primary_key` is enforced

**Reference:** [DigitalOcean MySQL Primary Keys Documentation](https://www.digitalocean.com/docs/databases/mysql/how-to/create-primary-keys/)

---

## Recent Fixes

### Critical Primary Key Fixes - December 21, 2025

**Database:** `pausatf-stage-db` (661fa8d4-077c-43d7-a47a-79bfc42737c8)
**Schema:** `wordpress_stage`

The following 7 tables were identified as missing primary keys and have been corrected:

| Table | Primary Key Added | Row Count | Risk Level | Notes |
|-------|------------------|-----------|------------|-------|
| `wp_statistics_pages` | `(uri, date)` | 738,450 | 🔴 **CRITICAL** | Well above 5K threshold - data loss risk |
| `wp_yoast_seo_meta` | `object_id` | 1,409 | 🟡 Medium | SEO metadata table |
| `wp_nxs_log` | `id` | 696 | 🟢 Low | Social sharing logs |
| `wp_cjtoolbox_form_group_xfields` | `groupId` | 0 | 🟢 Low | Form groups (empty) |
| `wp_nxs_query` | `id` | 0 | 🟢 Low | Social query queue (empty) |
| `wp_wsluserscontacts` | `id` | 0 | 🟢 Low | Social login contacts (empty) |
| `wp_wslusersprofiles` | `id` | 0 | 🟢 Low | Social login profiles (empty) |

### SQL Commands Executed

```sql
-- Critical fix for wp_statistics_pages (738,450 rows)
ALTER TABLE wp_statistics_pages
  ADD PRIMARY KEY (uri, date),
  DROP KEY date_2;

-- Standard fixes for other tables
ALTER TABLE wp_yoast_seo_meta
  ADD PRIMARY KEY (object_id),
  DROP KEY object_id;

ALTER TABLE wp_nxs_log
  ADD PRIMARY KEY (id),
  DROP KEY id;

ALTER TABLE wp_cjtoolbox_form_group_xfields
  ADD PRIMARY KEY (groupId),
  DROP KEY groupId;

ALTER TABLE wp_wsluserscontacts
  ADD PRIMARY KEY (id),
  DROP KEY id;

ALTER TABLE wp_wslusersprofiles
  ADD PRIMARY KEY (id),
  DROP KEY id;

-- wp_nxs_query required strict mode bypass due to invalid default dates
SET SESSION sql_mode = '';
ALTER TABLE wp_nxs_query
  ADD PRIMARY KEY (id),
  DROP KEY id;
SET SESSION sql_mode = DEFAULT;
```

### Results

✅ All 7 tables now have PRIMARY KEY constraints
✅ No tables remaining without primary keys
✅ Database replication stable
✅ DigitalOcean warning emails should cease

---

## Monitoring & Verification

### Check for Tables Without Primary Keys

Use this query to identify any tables missing primary keys:

```sql
SELECT DISTINCT
    t.TABLE_SCHEMA,
    t.TABLE_NAME,
    t.TABLE_ROWS,
    t.ENGINE
FROM information_schema.TABLES t
LEFT JOIN information_schema.TABLE_CONSTRAINTS tc
    ON t.TABLE_SCHEMA = tc.TABLE_SCHEMA
    AND t.TABLE_NAME = tc.TABLE_NAME
    AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
WHERE t.TABLE_SCHEMA NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
    AND t.TABLE_TYPE = 'BASE TABLE'
    AND tc.CONSTRAINT_NAME IS NULL
ORDER BY t.TABLE_ROWS DESC;
```

**Expected Result:** Empty result set (no rows returned)

### Verify Existing Primary Keys

Check which tables have primary keys and what columns they use:

```sql
SELECT
    TABLE_SCHEMA,
    TABLE_NAME,
    COLUMN_NAME,
    ORDINAL_POSITION
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'wordpress_stage'
    AND CONSTRAINT_NAME = 'PRIMARY'
ORDER BY TABLE_NAME, ORDINAL_POSITION;
```

### Check Replication Status

```bash
# Via DigitalOcean CLI
doctl databases get 661fa8d4-077c-43d7-a47a-79bfc42737c8

# Check for replication lag
doctl databases metrics 661fa8d4-077c-43d7-a47a-79bfc42737c8 --type replication_lag
```

---

## Preventive Measures

### Plugin Management

Several WordPress plugins were identified as creating tables without primary keys:

1. **WP Statistics** (`wp_statistics_pages`) - Analytics plugin
   - Configure to use proper indexing
   - Consider alternative analytics solutions (Google Analytics, Cloudflare Analytics)

2. **Yoast SEO** (`wp_yoast_seo_meta`) - SEO plugin
   - Keep updated to latest version
   - Monitor for schema changes

3. **NextScripts Social Networks Auto-Poster** (`wp_nxs_log`, `wp_nxs_query`)
   - Deprecated/unused - consider removing if not actively used
   - Clear old logs periodically

4. **WordPress Social Login** (`wp_wsluserscontacts`, `wp_wslusersprofiles`)
   - Verify if still needed
   - Consider native WordPress OAuth if possible

### Best Practices

1. **Before Installing Plugins:**
   - Review database schema changes
   - Check if plugin creates tables with proper primary keys
   - Test in staging environment first

2. **Regular Audits:**
   - Run primary key verification query monthly
   - Monitor DigitalOcean email alerts
   - Include in monthly maintenance checklist

3. **Schema Changes:**
   - Always define primary keys when creating tables
   - Use auto-increment ID columns for most tables
   - Document any custom table structures

### Development Standards

When creating custom WordPress tables:

```php
// CORRECT - With PRIMARY KEY
$sql = "CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    data TEXT,
    PRIMARY KEY (id),
    KEY user_id (user_id)
) {$charset_collate};";

// INCORRECT - Without PRIMARY KEY
$sql = "CREATE TABLE {$table_name} (
    user_id BIGINT(20) UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    data TEXT,
    UNIQUE KEY user_id (user_id)  -- UNIQUE is not sufficient!
) {$charset_collate};";
```

---

## Database Connection Information

### Production Database

**Cluster Name:** `pausatf-production-db`
**Engine:** MySQL 8.0
**Region:** nyc3

```bash
# Get connection details
doctl databases connection pausatf-production-db

# Connect via CLI
mysql -h <host> -P 25060 -u doadmin -p --ssl-mode=REQUIRED
```

### Staging Database

**Cluster ID:** `661fa8d4-077c-43d7-a47a-79bfc42737c8`
**Cluster Name:** `pausatf-stage-db`
**Engine:** MySQL 8.0
**Region:** nyc3

```bash
# Get connection details
doctl databases connection 661fa8d4-077c-43d7-a47a-79bfc42737c8

# Connection string
mysql -h pausatf-stage-db-do-user-183006-0.m.db.ondigitalocean.com \
  -P 25060 \
  -u doadmin \
  -p \
  --ssl-mode=REQUIRED \
  -D wordpress_stage
```

### Security Notes

- Always use `--ssl-mode=REQUIRED` for connections
- Store credentials in encrypted vault files
- Rotate passwords quarterly
- Use read-only users for analytics/reporting
- Never commit connection strings to git

---

## Common Maintenance Tasks

### 1. Optimize Tables

```bash
# Via WP-CLI (recommended)
wp db optimize --allow-root

# Via MySQL
mysqlcheck -o -u doadmin -p --ssl-mode=REQUIRED -h <host> -P 25060 wordpress_stage
```

### 2. Repair Tables

```bash
# Via WP-CLI
wp db repair --allow-root

# Via MySQL
mysqlcheck -r -u doadmin -p --ssl-mode=REQUIRED -h <host> -P 25060 wordpress_stage
```

### 3. Clean Up Old Data

```sql
-- Clean old WP Statistics data (keep last 90 days)
DELETE FROM wp_statistics_pages
WHERE date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);

-- Clean old social media logs (keep last 30 days)
DELETE FROM wp_nxs_log
WHERE date < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- After deletion, optimize tables
OPTIMIZE TABLE wp_statistics_pages, wp_nxs_log;
```

### 4. Monitor Table Sizes

```sql
-- Check table sizes
SELECT
    TABLE_NAME,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)',
    TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'wordpress_stage'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
LIMIT 20;
```

### 5. Backup Before Major Changes

```bash
# Export specific tables
wp db export backup-$(date +%Y%m%d-%H%M%S).sql \
  --tables=wp_statistics_pages,wp_yoast_seo_meta \
  --allow-root

# Full database backup
wp db export full-backup-$(date +%Y%m%d-%H%M%S).sql --allow-root
```

---

## Maintenance Schedule

### Daily
- Monitor DigitalOcean alerts for replication issues
- Check disk space usage

### Weekly
- Review slow query log
- Monitor table growth trends

### Monthly
- Run primary key verification query
- Clean old analytics data (90+ days)
- Optimize large tables
- Review and update this documentation

### Quarterly
- Full database audit
- Plugin schema review
- Test database restore procedures
- Rotate database credentials

---

## Troubleshooting

### Issue: Cannot Add Primary Key

**Error:** `ERROR 3750 (HY000): Unable to create or change a table without a primary key`

**Solution:** Add and drop keys in single operation:
```sql
ALTER TABLE table_name
  ADD PRIMARY KEY (column),
  DROP KEY old_key;
```

### Issue: Invalid Default Values

**Error:** `ERROR 1067 (42000): Invalid default value for 'column'`

**Solution:** Temporarily disable strict mode:
```sql
SET SESSION sql_mode = '';
ALTER TABLE table_name ADD PRIMARY KEY (id);
SET SESSION sql_mode = DEFAULT;
```

### Issue: Replication Lag

**Check Status:**
```bash
doctl databases metrics 661fa8d4-077c-43d7-a47a-79bfc42737c8 --type replication_lag
```

**Common Causes:**
- Large transactions without primary keys
- Long-running queries blocking replication
- High write volume

**Solutions:**
- Ensure all tables have primary keys
- Break large operations into smaller batches
- Optimize slow queries

---

## Related Documentation

- [05-server-migration-guide.md](05-server-migration-guide.md) - Database migration procedures
- [10-operational-procedures.md](10-operational-procedures.md) - Operational runbooks
- [DigitalOcean MySQL Primary Keys](https://www.digitalocean.com/docs/databases/mysql/how-to/create-primary-keys/)
- [MySQL 8.0 Replication Requirements](https://dev.mysql.com/doc/refman/8.0/en/replication.html)

---

## Revision History

| Date | Version | Author | Changes |
|------|---------|--------|---------|
| 2025-12-21 | 1.0 | Thomas Vincent | Initial document creation; documented primary key fixes for 7 tables in pausatf-stage-db |

---

## Emergency Contacts

**Database Issues:**
- Primary: DevOps Team
- Secondary: DigitalOcean Support (support.digitalocean.com)

**Escalation:**
- For critical data loss or replication failures, open high-priority ticket with DigitalOcean immediately
