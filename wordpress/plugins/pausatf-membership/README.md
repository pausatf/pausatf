# PAUSATF Membership Plugin

WordPress plugin that replaces the legacy Teeters PHP scripts (2007–2015)
for PA/USATF membership management and scoring.

## Background

The original scripts (`members.php`, `clubs.php`, `ScoreSheet.php`,
`fetch-members.php`) relied on the `mysql_*` API removed in PHP 7.0.  All
are currently disabled (410 Gone) via `.htaccess` in `content/teeters/`.

This plugin provides a modern, WordPress-native replacement.

## Features

| Legacy Script | Replacement |
| --- | --- |
| `clubs.php` | CPT `pausatf_club` + `[pausatf_clubs]` shortcode + block |
| `members.php` | CPT `pausatf_member` + `[pausatf_members]` shortcode + block |
| `ScoreSheet.php` | `[pausatf_scoresheet]` shortcode → `{prefix}pausatf_scores` table |
| `fetch-members.php` | WP-Cron daily sync stub (`pausatf_usatf_sync`) |

## Installation

1. Copy `wordpress/plugins/pausatf-membership/` into
   `wp-content/plugins/pausatf-membership/` on the server.
2. Activate via **Plugins → PAUSATF Membership** in the WP admin.
   Activation creates the `{prefix}pausatf_scores` table and schedules the
   daily cron job.

## Shortcodes

### `[pausatf_clubs]`

Renders a bullet list of all published clubs.

| Attribute  | Default | Description                          |
|------------|---------|--------------------------------------|
| `orderby`  | `title` | WP_Query `orderby` param             |
| `order`    | `ASC`   | `ASC` or `DESC`                      |
| `per_page` | `-1`    | Max clubs to display (−1 = all)      |

### `[pausatf_members]`

Renders a member roster table.  **Login required** — shows a login link to
unauthenticated visitors.

| Attribute  | Default | Description                          |
|------------|---------|--------------------------------------|
| `club`     | *(all)* | Filter by club post ID or slug       |
| `per_page` | `50`    | Rows per page                        |
| `orderby`  | `title` | WP_Query `orderby` param             |
| `order`    | `ASC`   | `ASC` or `DESC`                      |

### `[pausatf_scoresheet]`

Renders a score-submission form.  **Login required.**
Submissions are stored in `{prefix}pausatf_scores`.

## WP-Cron: USATF Member Sync

The cron hook `pausatf_usatf_sync` runs daily.  It fetches a UTF-8 CSV from
the URL stored in the WordPress option `pausatf_usatf_sync_csv_url`.

Configure the URL from WP-CLI:

```sh
wp option update pausatf_usatf_sync_csv_url 'https://example.com/usatf-members.csv'
```

Expected CSV columns (minimum):

```text
first_name,last_name,club_name
```

Optional: include a `usatf_id` column to enable idempotent upserts.

## Gutenberg Blocks

Two dynamic blocks are registered:

- **Club Directory** (`pausatf-membership/club-directory`)
- **Member Lookup** (`pausatf-membership/member-lookup`)

Both render server-side and accept the same attributes as their shortcode
equivalents.

## File Structure

```text
wordpress/plugins/pausatf-membership/
├── pausatf-membership.php          # Plugin bootstrap
├── assets/
│   └── pausatf-membership.css      # Front-end styles
├── blocks/
│   ├── club-directory/index.js     # Gutenberg block (editor)
│   └── member-lookup/index.js      # Gutenberg block (editor)
├── includes/
│   ├── class-cpt-club.php          # pausatf_club CPT + [pausatf_clubs]
│   ├── class-cpt-member.php        # pausatf_member CPT + [pausatf_members]
│   ├── class-score-handler.php     # [pausatf_scoresheet] + DB insert
│   └── class-usatf-sync.php        # WP-Cron sync stub
└── templates/
    ├── archive-pausatf_club.php    # Public club archive template
    └── archive-pausatf_member.php  # (Future) member archive template
```

## Development Notes

- PHP 7.4+ required.
- No build step required — the block `index.js` files use the global
  `wp.*` variables provided by WordPress core.  Add a build pipeline
  (webpack / @wordpress/scripts) when JSX or tree-shaking is needed.
- All DB writes use `$wpdb->insert()` with typed format arrays — no raw
  SQL string interpolation.
- Secrets (DB credentials, API tokens) are never stored in the plugin;
  they live in `wp-config.php` or WordPress options set server-side.
