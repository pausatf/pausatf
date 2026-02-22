# Legacy Teeters Scripts

PHP scripts written by Jeff Teeters (2007–2015) for PA/USATF membership and scoring data.

## Current status: **disabled (410 Gone)**

### Why they're broken

| Issue | Detail |
|-------|--------|
| PHP version | Scripts use `mysql_connect()` — removed in PHP 7.0. Server runs PHP 7.4. |
| Database | `pausatf_clubs` / `pausatf_php` DB credentials are no longer valid |
| Paths | `SECURE_DIR` points differ per script; some point to non-existent locations |

### What's needed to restore

1. **Database**: Restore `pausatf_clubs` or `pausatf_php` DB with valid credentials
2. **PHP migration**: Replace all `mysql_*` calls with PDO or `mysqli_*`
3. **Path fix**: Standardise `SECURE_DIR` to `/var/www/legacy/private/` (already has a `db.php`)
4. **`db.php`**: Update `/var/www/legacy/private/db.php` with working credentials

### Affected scripts

| Script | Purpose | SECURE_DIR path |
|--------|---------|----------------|
| `members.php` | Member lookup | `/home/pausat/private/` (hardcoded in script) |
| `clubs.php` | Club directory | `/home/pausat/private/` |
| `ScoreSheet.php` | Score submission | `/var/www/html/pausatf/private/` |
| `fetch-members.php` | USATF sync | `/var/www/legacy/private/` ✓ exists |
| `fetch-members-orig.php` | USATF sync (orig) | `/home/pausat/private/` |

### Static files (still accessible)

- `clubs.html`
- `clubs-one.html`
- `youthindex4.html`
- `youthindexJ1.html`
- `youthindexJ2.html`
