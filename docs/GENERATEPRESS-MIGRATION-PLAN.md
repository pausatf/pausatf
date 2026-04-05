# GeneratePress Migration Plan: pausatf.org

Version: 1.0
Date: 2026-04-04
Status: Draft

---

## Table of Contents

1. [Discovery / Audit](#1-discovery--audit)
2. [Theme Migration Strategy](#2-theme-migration-strategy)
3. [Old-to-New Mapping Table](#3-old-to-new-mapping-table)
4. [Visual Parity Rules](#4-visual-parity-rules)
5. [Implementation Plan](#5-implementation-plan)
6. [Code Guidance](#6-code-guidance)
7. [QA / Acceptance Criteria](#7-qa--acceptance-criteria)
8. [Risks / Gotchas](#8-risks--gotchas)
9. [Final Recommendation](#9-final-recommendation)

---

## 1. Discovery / Audit

### Theme Layer

- [x] Active theme identified: TheSource-child v0.3-php8 (child of TheSource v4.8.13, Elegant Themes)
- [x] Parent template inventory: 18 templates (404, comments, footer, header, home, index, page, page-blog, page-contact, page-full, page-gallery, page-login, page-search, page-sitemap, page-template-portfolio, searchform, sidebar, single)
- [x] Child override inventory: 4 files (header.php, footer.php, single.php, page-blog.php)
- [x] No page builder present. Classic Editor with ePanel shortcodes.
- [x] GeneratePress is NOT currently installed. OceanWP was previously evaluated and abandoned.

### Navigation

- [x] 3 menu locations registered: primary-menu, secondary-menu, footer-menu
- [x] Only "Top_Menu" (49 items) assigned, to secondary-menu and footer-menu
- [x] primary-menu location is unassigned
- [x] Dual nav bar pattern: #page-menu (top-right, dark bg #232323) + #cat-nav (full-width red sprite bar with Superfish dropdowns and search)

### Widgets

- [x] 6 sidebars registered
- [x] sidebar-5 has 21 widgets (mostly custom_html), primary content delivery mechanism for sidebar
- [x] Footer widget areas are empty
- [x] Widget-logic plugin provides per-page visibility rules
- [x] pausatf-core MU-plugin supplements widget visibility

### Custom Post Types

- [x] CPTs identified: pausatf_club, pausatf_member, committee_update, race_result, meeting_minutes
- [x] Registered by pausatf-content-architecture MU-plugin
- [x] Taxonomies, roles, and Schema.org/JSON-LD also handled by this MU-plugin

### Shortcodes

- [x] ePanel column shortcodes in active use: `[one_half]`, `[one_third]`, `[one_fourth]`, `[two_thirds]`, `[three_fourths]`, plus `_last` variants
- [x] Custom shortcodes: `[pausatf_members]`, `[pausatf_clubs]`, `[pausatf_scoresheet]`

### Plugins (Production)

- [x] Content: accordions, classic-editor, ml-slider, tablepress-premium
- [x] Forms: contact-form-7
- [x] Performance: cloudflare, jetpack-boost, wp-super-cache
- [x] SEO/Analytics: jetpack, google-site-kit
- [x] Backup: jetpack-backup, updraftplus
- [x] Utility: akismet, category-posts, widget-logic, wp-mail-smtp
- [x] Two copies of membership plugin found. The `/wordpress/plugins/` version (with Gutenberg blocks) is the correct one.

### MU-Plugins

- [x] pausatf-content-architecture.php: CPTs, taxonomies, roles, Schema.org/JSON-LD
- [x] pausatf-core.php: widget visibility logic
- [x] cf-auto-purge.php: Cloudflare cache purge on post update

### Infrastructure

- [x] Ansible + Terraform monorepo. 24 Ansible roles.
- [x] DigitalOcean droplet + managed MySQL. Cloudflare CDN.
- [x] Dev environment available via Ansible (OLS + PHP 8.4)
- [x] Legacy content at /var/www/legacy/ served via Apache Alias; some PHP scripts redirect to x10host.com

### Layout and Design

- [x] Fixed 960px container, not responsive. No media queries.
- [x] Two-column float layout: #recent-posts (637px) + #sidebar (323px)
- [x] Header: 150px tall, white bg, logo left, Georgia italic tagline
- [x] Color palette documented: primary red #b4282e, dark red #8d1a13, near-black #383737/#1c1c1c, body text #757474, cyan #00b7f3, nav dark #232323
- [x] Typography: all system fonts. Body Arial/Verdana 12px. Headings Arial. Tagline Georgia italic.
- [x] Homepage: Instagram graphic + TablePress CTA + Google Calendar iframe (19 feeds, AGENDA mode)
- [x] Footer: empty widget areas, flat bottom-nav mirroring top nav, credit line
- [x] Breadcrumbs: Georgia italic, absolute positioned, "Home >> [Title]" separator
- [x] No hero image, no slider/carousel on homepage
- [x] SEO: no Yoast/RankMath. Jetpack Open Graph. MU-plugin JSON-LD.

---

## 2. Theme Migration Strategy

### 2.1 GeneratePress Premium Required

GeneratePress Free lacks several features this migration depends on. GeneratePress Premium (GP Premium) is required for:

- **Elements module**: custom header Element, footer Element, hook-based content injection, per-page/CPT layout control
- **Secondary Navigation module**: rebuilds the dual nav bar without custom template code
- **Sections module**: layout blocks for homepage reconstruction
- **Colors module**: granular color control beyond Customizer defaults
- **Typography module**: system font stack configuration without custom CSS
- **Blog module**: post layout, excerpt, metadata display control
- **Menu Plus module**: sticky nav, mobile menu options, search in nav

### 2.2 Child Theme: pausatf-generatepress-child

All customizations go into a child theme, not GP Premium settings alone. This preserves portability and version control.

```
wp-content/themes/pausatf-generatepress-child/
  style.css
  functions.php
  assets/
    css/
      legacy-compat.css      # ePanel shortcode shims, sidebar widget styles
      sport-labels.css        # RR/XC/YOUTH/PA color-coded prefix labels
      calendar-embed.css      # Google Calendar iframe responsive wrapper
    js/
      legacy-compat.js        # any JS shims (Superfish removal, etc.)
    images/
      logo.png                # existing site logo
  templates/
    (GP Elements handle most template logic; raw PHP templates only if needed for CPTs)
```

### 2.3 Customization Placement Map

| Customization | Mechanism | Rationale |
|---|---|---|
| Site-wide colors | GP Customizer > Colors module | Native GP; survives updates |
| System font stacks | GP Customizer > Typography module | Native GP; no Google Fonts needed |
| Global container width (960px initial, later responsive) | GP Customizer > Layout > Container | Single setting |
| Primary nav (#cat-nav equivalent) | GP Primary Navigation | Maps directly |
| Secondary nav (#page-menu equivalent) | GP Secondary Navigation module | Dedicated module |
| Search in nav bar | Menu Plus module > Navigation Search | Built-in |
| Header layout (logo left, tagline right) | GP Customizer > Header | Native |
| Footer content (flat nav + credit) | GP Element (Hook: `generate_footer`) | Full HTML/PHP control |
| Sidebar widget area | GP Customizer > Sidebars + migrated widgets | Retain sidebar-5 pattern initially |
| Homepage layout | GP Element (Hook: `generate_after_header`, type: Block) + page content | Replace home.php template |
| Breadcrumbs | GP Element (Hook: `generate_after_header`) or `generate_inside_container_top` | Custom HTML with Georgia italic style |
| Per-page layout overrides | GP Elements with Display Rules (per page/CPT/taxonomy) | Replaces widget-logic per-page visibility |
| CPT archive/single templates | GP Elements with Display Rules filtered to CPT | Replaces page-template-portfolio.php etc. |
| Sport prefix label colors | Child theme Additional CSS or `assets/css/sport-labels.css` | Purely presentational |
| ePanel column shortcodes | Shortcode shim in child `functions.php` + CSS in `legacy-compat.css` | Backward compat until content is migrated to blocks |

### 2.4 ePanel Shortcode Migration

The ePanel column shortcodes (`[one_half]`, `[one_third]`, etc.) are used throughout post/page content. Two options:

**Option A (recommended for launch): CSS shim.** Register the shortcodes in the child theme `functions.php` with simple `<div>` wrappers and float/flex CSS. This preserves all existing content without editing posts.

**Option B (post-launch): Block migration.** Use WP-CLI to search for shortcode usage, then convert to WordPress Columns blocks. This is a content migration task, not a theme task. Do it after launch when the theme is stable.

### 2.5 Sidebar Widget Migration (21 widgets in sidebar-5)

The 21 widgets in sidebar-5 are mostly `custom_html` widgets with per-page visibility rules (widget-logic plugin). Migration approach:

1. **Phase 1 (launch):** Keep sidebar-5 as a GP sidebar. Migrate all 21 widgets as-is. Retain widget-logic plugin for visibility rules. This is zero-risk.
2. **Phase 2 (post-launch):** Audit each widget. Convert page-specific widgets to GP Elements with Display Rules (these are more maintainable than widget-logic conditions). Convert global widgets to GP Hook Elements or keep as widgets. Retire widget-logic plugin when all visibility rules are handled by GP Elements.

### 2.6 Dual Nav Bar Reconstruction

Current structure:

- `#page-menu`: dark (#232323) bar, absolute positioned top-right, white bold links. Functions as a utility/secondary nav.
- `#cat-nav`: full-width bar below header, red sprite background, Superfish dropdown JS, search form on right side.

GP reconstruction:

- **#page-menu** becomes GP Secondary Navigation. Set background to #232323, text color #fff, font-weight bold. Position: above header.
- **#cat-nav** becomes GP Primary Navigation. Set background to #b4282e (primary red). Enable Menu Plus search icon. Superfish JS is replaced by GP's built-in dropdown behavior.
- The red image sprites on #cat-nav nav items are purely decorative. Replace with CSS `background-color: #b4282e` and `:hover` state `background-color: #8d1a13`. If exact sprite reproduction is required, use `background-image` on specific menu item classes.

### 2.7 Footer Reconstruction

Current footer is minimal: empty widget areas, a flat `<ul class="bottom-nav">` mirroring the top nav, and a credit line "Designed by SMDdesigns | Powered by WordPress".

GP reconstruction: Create a GP Element (type: Hook, location: `generate_footer`). Output a `<nav>` with the footer menu and a `<p>` credit line. Style via child theme CSS. Disable GP's default footer widgets and copyright in Customizer.

### 2.8 960px Fixed to Responsive

The current site is a fixed 960px layout with no media queries. Migration strategy:

1. **Phase 1 (launch):** Set GP container width to 960px. Set sidebar width to 323px. Content area becomes ~637px (matching current). This produces visual parity with the existing site.
2. **Phase 2 (post-launch):** Enable responsive breakpoints. GP's default breakpoints are 1024px (tablet) and 768px (mobile). At tablet, stack sidebar below content. At mobile, collapse nav to hamburger menu. This requires testing all 21 sidebar widgets at mobile widths and verifying Google Calendar iframe behavior at narrow viewports.

---

## 3. Old-to-New Mapping Table

| Component | Current Implementation | GP Replacement | Difficulty | Risk | Notes |
|---|---|---|---|---|---|
| Theme framework | TheSource v4.8.13 (Elegant Themes) | GeneratePress Premium 3.x | Medium | Medium | Complete theme swap; all template logic changes |
| Child theme | TheSource-child v0.3-php8 | pausatf-generatepress-child | Low | Low | New child theme from scratch |
| Header (150px, logo left, tagline) | header.php override in child theme | GP Customizer > Header + Typography module | Low | Low | Logo upload, tagline font set to Georgia italic |
| Primary nav (#cat-nav, red bar, dropdowns) | Superfish JS + sprite images + searchform.php | GP Primary Navigation + Menu Plus search | Medium | Medium | Superfish removal; sprite-to-CSS transition |
| Secondary nav (#page-menu, dark bar) | Custom markup in header.php | GP Secondary Navigation module | Low | Low | Direct module mapping |
| Search in nav | `searchform.php` template + #cat-nav markup | Menu Plus > Navigation Search | Low | Low | Built-in feature |
| Page layout (960px fixed, 2-col float) | CSS floats, hardcoded widths | GP Customizer > Layout (container 960px, sidebar 323px) | Low | Low | Direct setting mapping |
| Sidebar (sidebar-5, 21 widgets) | `register_sidebar()` + widget-logic plugin | GP sidebar + widget-logic (phase 1); GP Elements (phase 2) | Low (ph1) / Med (ph2) | Low | Phase 1 is a direct carry-over |
| Footer (flat nav + credit) | footer.php override | GP Element hook at `generate_footer` | Low | Low | Simple HTML output |
| Breadcrumbs ("Home >> Title", Georgia italic) | TheSource built-in breadcrumb function | GP Element hook at `generate_after_header` or `generate_inside_container_top` | Low | Low | Custom HTML; style in child CSS |
| Homepage layout | home.php template (Instagram graphic + TablePress CTA + Calendar iframe) | GP Element (Block type) or static page with blocks | Medium | Medium | Content must be manually reconstructed |
| ePanel column shortcodes | TheSource ePanel ([one_half], etc.) | Shortcode shim in child functions.php + CSS | Low | Low | Shim preserves all existing content |
| Custom shortcodes ([pausatf_members], etc.) | pausatf-content-architecture MU-plugin | No change; MU-plugin is theme-independent | None | None | Survives theme swap unchanged |
| CPT templates (club, result, minutes, etc.) | Default WP template hierarchy | GP Elements with Display Rules per CPT | Medium | Medium | Must test each CPT archive and single view |
| Sport prefix labels (RR, XC, YOUTH, PA) | Inline styles or custom_html widgets in sidebar | Child theme CSS (sport-labels.css) | Low | Low | Purely CSS |
| Google Calendar iframe | Raw HTML in page content (19 feeds, AGENDA mode) | No change; iframe is content, not theme | None | None | Verify iframe responsive-ready for phase 2 |
| TablePress tables | tablepress-premium plugin | No change; plugin is theme-independent | None | Low | Verify table CSS does not conflict with GP |
| Contact Form 7 | CF7 plugin, conditionally loaded per slug | No change; plugin is theme-independent | None | Low | Verify form styling under GP |
| ML Slider | ml-slider plugin | No change; plugin is theme-independent | None | Low | Verify slider CSS under GP |
| Widget visibility (per-page rules) | widget-logic plugin + pausatf-core MU-plugin | widget-logic (ph1); GP Elements Display Rules (ph2) | Low / Med | Low | Phase 2 replaces plugin with native GP |
| SEO / JSON-LD | pausatf-content-architecture MU-plugin + Jetpack OG | No change; MU-plugin is theme-independent | None | None | Verify OG meta still outputs correctly |
| Cloudflare cache purge | cf-auto-purge MU-plugin | No change | None | None | Theme-independent |
| Legacy content (/var/www/legacy/) | Apache Alias | No change; server config, not theme | None | None | Verify alias still functions |
| Accordion plugin | accordions plugin | No change | None | Low | Verify accordion CSS under GP |
| WP Super Cache | wp-super-cache plugin | No change; flush cache after theme switch | None | Low | Must purge on activation |
| Two-column sidebar (#firstcol/#secondcol) | Custom HTML widgets, floated 160px+162px divs | Preserve as custom_html widget; style in child CSS | Low | Low | Fragile layout; keep as-is for now |

---

## 4. Visual Parity Rules

The goal for Phase 1 is pixel-level fidelity with the current live site. No redesign. The new theme must be indistinguishable from the old one to site visitors.

### 4.1 Layout

- Container width: `960px`, centered.
- Content area (#recent-posts equivalent): `637px`.
- Sidebar: `323px`.
- Content/sidebar gap: match current (inspect for exact margin; likely `0` with padding inside each column).
- Header height: `150px`.
- No full-width sections. Everything inside the 960px container except nav bars (which may be full-width with content constrained to 960px).

### 4.2 Colors (exact hex values)

| Element | Color |
|---|---|
| Page background | `#ffffff` |
| Body text | `#757474` |
| Headings | `#383737` |
| Primary red (nav bg, links, accents) | `#b4282e` |
| Dark red (hover states) | `#8d1a13` |
| Near-black (text emphasis, footer text) | `#1c1c1c` |
| Secondary nav background (#page-menu) | `#232323` |
| Secondary nav text | `#ffffff` |
| Cyan accent (links, CTAs) | `#00b7f3` |
| Sidebar widget title text | inherited, Georgia italic, centered |

### 4.3 Typography

No Google Fonts. All system fonts.

| Element | Font | Size | Weight | Style |
|---|---|---|---|---|
| Body text | `Arial, Verdana, sans-serif` | `12px` | normal | normal |
| Headings (h1-h6) | `Arial, sans-serif` | varies | bold | normal |
| Post titles | `Arial, sans-serif` | `36px` | bold | normal |
| Tagline | `Georgia, serif` | `14px` | normal | italic |
| Breadcrumbs | `Georgia, serif` | `14px` | normal | italic |
| Widget titles (sidebar) | `Georgia, serif` | varies | normal | italic, `text-align: center` |
| Nav links (primary) | `Arial, sans-serif` | per current | bold | normal |
| Nav links (secondary) | `Arial, sans-serif` | per current | bold | normal, `color: #fff` |

### 4.4 Spacing

- Preserve existing padding and margins. Inspect the live site for exact values on:
  - `.post` padding
  - Sidebar widget margin-bottom
  - Header internal padding (logo to edge, tagline position)
  - Nav item padding
  - Footer padding
- Do not introduce new spacing values. GP's default spacing differs from TheSource. Override all GP spacing defaults in Customizer or child CSS.

### 4.5 Navigation

- Secondary nav (#page-menu): dark bar (#232323) at very top of page or top-right corner. White bold text links. No dropdowns.
- Primary nav (#cat-nav): full-width red bar (#b4282e). Dropdown menus on hover. Search form/icon on the right side. Hover state: #8d1a13.
- Dropdown menus: verify background color, text color, and hover behavior match current Superfish styling.
- Mobile: not applicable for Phase 1 (site is not responsive). Phase 2 addresses mobile nav.

### 4.6 Breadcrumbs

- Font: Georgia, 14px, italic.
- Separator: `>>` (double angle bracket, not a single chevron).
- Format: `Home >> Page Title`
- Position: below header, above content, absolute positioned per current CSS.

### 4.7 Footer

- Minimal: flat nav links + credit line "Designed by SMDdesigns | Powered by WordPress".
- No footer widgets visible (areas are empty).
- Background: match current (white or very light).
- Nav links: unstyled list, horizontal.

### 4.8 Homepage

- No hero image, no slider.
- Content column: Instagram announcement graphic (image), then TablePress row with JOIN/SHOP CTAs, then Google Calendar iframe.
- Sidebar: same as all other pages (sidebar-5 widgets).

### 4.9 Images and Media

- Logo: preserve exact current logo file and dimensions.
- No lazy-loading changes (preserve current behavior).
- Sport prefix label colors in sidebar "Recent PA News" widget must match current color coding.

---

## 5. Implementation Plan

### Prerequisites

- GeneratePress theme (free) installed
- GeneratePress Premium plugin purchased and activated
- Dev environment provisioned via Ansible (OLS + PHP 8.4)
- Full site backup via UpdraftPlus or Jetpack Backup
- Database export from managed MySQL

### Stage 1: Dev Environment Setup (estimated 2 hours)

1. Provision dev environment using existing Ansible playbook. Confirm OLS + PHP 8.4 + managed MySQL connectivity.
2. Clone production database and uploads to dev.
3. Install GeneratePress theme on dev. Activate it only on dev (production stays on TheSource-child).
4. Install and activate GP Premium on dev. Enable all modules: Elements, Secondary Navigation, Menu Plus, Colors, Typography, Blog, Sections.
5. Create `pausatf-generatepress-child` directory in `wp-content/themes/`. Add `style.css` (with `Template: generatepress`) and empty `functions.php`.
6. Activate the child theme on dev. Confirm site loads (it will look like default GP at this point).

### Stage 2: Global Settings (estimated 3 hours)

1. **Layout:** Customizer > Layout > Container > set width to `960px`. Set content/sidebar layout to "Content / Sidebar". Set sidebar width to `323px`.
2. **Colors:** Customizer > Colors > set all values per Section 4.2. Body text `#757474`, headings `#383737`, links `#b4282e`, link hover `#8d1a13`.
3. **Typography:** Customizer > Typography > set body font family to `Arial, Verdana, sans-serif`, size `12px`. Set headings to `Arial, sans-serif`. Disable Google Fonts.
4. **Header:** Customizer > Header > set height to `150px`. Upload logo. Set header background to `#ffffff`. Configure tagline: Georgia, 14px, italic.
5. **Primary Navigation:** Customizer > Primary Navigation > set background `#b4282e`, text `#ffffff`, hover background `#8d1a13`. Enable Menu Plus search.
6. **Secondary Navigation:** Customizer > Secondary Navigation > set background `#232323`, text `#ffffff`, font-weight bold. Position: above header.
7. **Sidebar:** Confirm sidebar-5 widgets appear. Verify widget-logic plugin is active and conditions fire correctly.
8. **Footer:** Customizer > Footer > disable default footer widgets. Disable default copyright. (Footer content will be added via GP Element in Stage 3.)

### Stage 3: Elements and Hooks (estimated 4 hours)

1. **Footer Element:** Create GP Element. Type: Hook. Hook: `generate_footer`. Output: `<nav>` with footer menu via `wp_nav_menu()` + credit `<p>`. Set Display Rules: Entire Site.
2. **Breadcrumb Element:** Create GP Element. Type: Hook. Hook: `generate_after_header` (or `generate_inside_container_top`, test both for correct position). Output: breadcrumb HTML matching "Home >> Title" pattern, Georgia italic. Display Rules: Entire Site, exclude homepage.
3. **Homepage Element:** Create GP Element. Type: Block or Hook. Reconstruct homepage content: Instagram graphic, TablePress CTA row, Google Calendar iframe. Alternatively, set a static front page and build the content in the block editor.
4. **CPT Layout Elements:** For each CPT (pausatf_club, pausatf_member, committee_update, race_result, meeting_minutes), create GP Elements to control archive and single layouts. Set Display Rules to the specific CPT.

### Stage 4: Child Theme Code (estimated 3 hours)

1. **ePanel shortcode shims:** Add shortcode registrations to `functions.php`. Add CSS to `assets/css/legacy-compat.css`. Enqueue in `functions.php`.
2. **Sport prefix label CSS:** Add to `assets/css/sport-labels.css`. Enqueue.
3. **Calendar iframe CSS:** Add responsive wrapper to `assets/css/calendar-embed.css`. Enqueue.
4. **Remaining TheSource-specific CSS:** Inspect live site for styles not covered by GP Customizer. Add overrides to child `style.css` or a dedicated override file.
5. **Dequeue unnecessary GP scripts/styles** if needed (e.g., if GP loads Google Fonts by default despite Customizer setting).

### Stage 5: Content and Plugin Verification (estimated 3 hours)

1. Walk every page template type: home, page, single post, archive, search, 404, category, tag.
2. Walk every CPT: pausatf_club archive/single, pausatf_member, committee_update, race_result, meeting_minutes.
3. Verify all TablePress tables render correctly (tables 93, 94, 98, 99, 101, 114, 138, 147, 152, 182, 197).
4. Verify Contact Form 7 renders and submits on contact pages.
5. Verify ML Slider renders.
6. Verify accordion plugin renders.
7. Verify Google Calendar iframe loads on homepage and /all-events/.
8. Verify all 3 MU-plugins function: content-architecture CPTs/taxonomies/JSON-LD, core widget visibility, cf-auto-purge.
9. Verify membership plugin (the `/wordpress/plugins/` version with Gutenberg blocks) functions.
10. Verify legacy content alias (/var/www/legacy/) still serves correctly.

### Stage 6: Visual Regression Testing (estimated 2 hours)

1. Capture screenshots of every major page on the current production site (BackstopJS or manual screenshots at 1024px width).
2. Capture matching screenshots on the dev site with the new theme.
3. Diff the screenshots. Flag any visual discrepancies.
4. Fix discrepancies. Re-capture. Repeat until visual parity is achieved per Section 4 rules.

### Stage 7: Performance Baseline (estimated 1 hour)

1. Run Lighthouse on dev site. Record scores.
2. Compare with production Lighthouse scores.
3. GP should be faster than TheSource (lighter framework). Confirm no regressions.
4. Verify WP Super Cache and Cloudflare integration still function. Purge all caches.
5. Verify Jetpack Boost settings are compatible.

### Stage 8: Production Cutover (estimated 1 hour)

1. Full production backup (database + files) via UpdraftPlus.
2. Install GeneratePress + GP Premium on production.
3. Upload pausatf-generatepress-child to production.
4. Import GP Customizer settings (export from dev, import on prod) or replicate manually.
5. Recreate GP Elements on production (Elements are stored in the database, not theme files; they do not transfer via theme upload).
6. Activate pausatf-generatepress-child on production.
7. Purge all caches: WP Super Cache, Cloudflare (via cf-auto-purge or manual), Jetpack Boost.
8. Verify site loads correctly. Walk critical pages.
9. Keep TheSource-child installed (not active) for 30 days as rollback option.

### Stage 9: Post-Launch Phase 2 (estimated 8-12 hours, non-urgent)

1. Enable responsive breakpoints. Test at 1024px, 768px, 480px.
2. Migrate sidebar widgets from widget-logic to GP Elements with Display Rules.
3. Migrate ePanel shortcode content to WordPress Columns blocks (WP-CLI search + manual editing).
4. Retire widget-logic plugin.
5. Retire classic-editor plugin if all content is block-compatible.
6. Remove TheSource and TheSource-child themes from the server.

---

## 6. Code Guidance

### 6.1 Child Theme style.css

```css
/*
Theme Name: PAUSATF GeneratePress Child
Template: generatepress
Version: 1.0.0
Description: Child theme for pausatf.org, migrated from TheSource-child.
Author: PAUSATF
Text Domain: pausatf-gp-child
*/
```

### 6.2 Child Theme functions.php

```php
<?php
/**
 * PAUSATF GeneratePress child theme functions.
 *
 * @package pausatf-gp-child
 */

defined('ABSPATH') || exit;

/**
 * Enqueue child theme assets.
 */
add_action('wp_enqueue_scripts', function () {
    $theme_version = wp_get_theme()->get('Version');
    $theme_uri     = get_stylesheet_directory_uri();

    // Legacy compatibility (ePanel shortcode grid, sidebar layout)
    wp_enqueue_style(
        'pausatf-legacy-compat',
        $theme_uri . '/assets/css/legacy-compat.css',
        ['generate-style'],
        $theme_version
    );

    // Sport prefix label colors
    wp_enqueue_style(
        'pausatf-sport-labels',
        $theme_uri . '/assets/css/sport-labels.css',
        ['generate-style'],
        $theme_version
    );

    // Google Calendar iframe responsive wrapper
    wp_enqueue_style(
        'pausatf-calendar-embed',
        $theme_uri . '/assets/css/calendar-embed.css',
        ['generate-style'],
        $theme_version
    );
}, 20);

/**
 * ePanel column shortcode shims.
 *
 * Reproduce the float-based grid that TheSource's ePanel provided.
 * Content authored with [one_half], [one_third], etc. continues to work.
 */
$pausatf_column_shortcodes = [
    'one_half'       => '50%',
    'one_third'      => '33.333%',
    'two_thirds'     => '66.666%',
    'one_fourth'     => '25%',
    'three_fourths'  => '75%',
];

foreach ($pausatf_column_shortcodes as $tag => $width) {
    add_shortcode($tag, function ($atts, $content = '') use ($width) {
        return sprintf(
            '<div class="pausatf-col" style="width:%s;float:left;box-sizing:border-box;padding-right:15px;">%s</div>',
            esc_attr($width),
            do_shortcode($content)
        );
    });

    // _last variant clears the float and removes right padding.
    add_shortcode($tag . '_last', function ($atts, $content = '') use ($width) {
        return sprintf(
            '<div class="pausatf-col pausatf-col-last" style="width:%s;float:left;box-sizing:border-box;padding-right:0;">%s</div><div style="clear:both;"></div>',
            esc_attr($width),
            do_shortcode($content)
        );
    });
}

/**
 * GP hook: custom breadcrumbs matching TheSource "Home >> Title" style.
 */
add_action('generate_after_header', function () {
    if (is_front_page()) {
        return;
    }

    $title = '';
    if (is_singular()) {
        $title = get_the_title();
    } elseif (is_archive()) {
        $title = get_the_archive_title();
    } elseif (is_search()) {
        $title = 'Search Results';
    } elseif (is_404()) {
        $title = 'Page Not Found';
    }

    if ($title) {
        printf(
            '<div class="pausatf-breadcrumbs"><a href="%s">Home</a> &raquo; %s</div>',
            esc_url(home_url('/')),
            esc_html($title)
        );
    }
});

/**
 * Disable default GP footer widgets and copyright.
 */
add_filter('generate_footer_widgets_active', '__return_false');

remove_action('generate_credits', 'generate_add_footer_info');

/**
 * GP hook: custom footer with flat nav + credit line.
 */
add_action('generate_footer', function () {
    echo '<div class="pausatf-footer-inner">';

    wp_nav_menu([
        'theme_location' => 'footer-menu',
        'container'       => 'nav',
        'container_class' => 'pausatf-footer-nav',
        'fallback_cb'     => false,
        'depth'           => 1,
    ]);

    echo '<p class="pausatf-footer-credit">';
    echo 'Designed by SMDdesigns | Powered by WordPress';
    echo '</p>';
    echo '</div>';
});

/**
 * Register menu locations matching the site's three nav areas.
 */
add_action('after_setup_theme', function () {
    register_nav_menus([
        'primary'   => 'Primary Navigation',
        'secondary' => 'Secondary Navigation',
        'footer'    => 'Footer Navigation',
    ]);
}, 15);
```

### 6.3 legacy-compat.css

```css
/* ePanel column shortcode grid */
.pausatf-col {
    float: left;
    box-sizing: border-box;
    padding-right: 15px;
}
.pausatf-col-last {
    padding-right: 0;
}

/* Two-column sidebar widget layout (#firstcol / #secondcol) */
#firstcol {
    float: left;
    width: 160px;
}
#secondcol {
    float: left;
    width: 162px;
}

/* Clearfix for shortcode rows */
.pausatf-col-last + .pausatf-col {
    clear: left;
}
```

### 6.4 sport-labels.css

```css
/*
 * Color-coded sport prefix labels in "Recent PA News" sidebar widget.
 * Inspect the actual widget HTML for selector accuracy; these selectors
 * are placeholders based on the audit. Adjust to match real markup.
 */
.sport-label-rr {
    color: #b4282e;
    font-weight: bold;
}
.sport-label-xc {
    color: #8d1a13;
    font-weight: bold;
}
.sport-label-youth {
    color: #00b7f3;
    font-weight: bold;
}
.sport-label-pa {
    color: #383737;
    font-weight: bold;
}
```

### 6.5 calendar-embed.css

```css
/* Responsive wrapper for Google Calendar iframe (Phase 2).
 * For Phase 1 the iframe keeps its fixed width inside the 637px content column.
 * Wrap the iframe in <div class="pausatf-calendar-wrap"> when enabling responsive. */
.pausatf-calendar-wrap {
    position: relative;
    width: 100%;
    padding-bottom: 75%; /* adjust ratio to match the current iframe height */
    height: 0;
    overflow: hidden;
}
.pausatf-calendar-wrap iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}
```

### 6.6 Breadcrumb CSS (add to child style.css)

```css
.pausatf-breadcrumbs {
    font-family: Georgia, serif;
    font-size: 14px;
    font-style: italic;
    color: #757474;
    padding: 5px 0;
    margin-bottom: 10px;
}
.pausatf-breadcrumbs a {
    color: #b4282e;
    text-decoration: none;
}
.pausatf-breadcrumbs a:hover {
    color: #8d1a13;
}
```

### 6.7 Footer CSS (add to child style.css)

```css
.pausatf-footer-inner {
    text-align: center;
    padding: 15px 0;
}
.pausatf-footer-nav ul {
    list-style: none;
    padding: 0;
    margin: 0 0 10px;
}
.pausatf-footer-nav li {
    display: inline;
    padding: 0 8px;
}
.pausatf-footer-nav a {
    text-decoration: none;
    color: #383737;
}
.pausatf-footer-nav a:hover {
    color: #b4282e;
}
.pausatf-footer-credit {
    font-size: 11px;
    color: #757474;
}
```

### 6.8 GP Customizer Settings Reference

This is a reference for values to set in Customizer. GP Premium supports export/import of these as JSON.

```json
{
    "generate_settings": {
        "container_width": "960",
        "body_font_family": "Arial, Verdana, sans-serif",
        "body_font_size": "12",
        "heading_font_family": "Arial, sans-serif",
        "header_background_color": "#ffffff",
        "header_text_color": "#383737",
        "navigation_background_color": "#b4282e",
        "navigation_text_color": "#ffffff",
        "navigation_background_hover_color": "#8d1a13",
        "navigation_text_hover_color": "#ffffff",
        "sidebar_width": "323",
        "content_color": "#757474",
        "heading_color": "#383737",
        "link_color": "#b4282e",
        "link_color_hover": "#8d1a13",
        "footer_widget_area_active": false
    }
}
```

**Assumption:** Actual GP option names may differ by version. Verify against GP Premium documentation at time of implementation.

---

## 7. QA / Acceptance Criteria

### 7.1 Visual Parity

- [ ] Container width is exactly 960px on desktop
- [ ] Content area width is 637px
- [ ] Sidebar width is 323px
- [ ] Header height is 150px
- [ ] Logo position matches (floated left)
- [ ] Tagline renders in Georgia, 14px, italic
- [ ] Secondary nav bar: background #232323, text #ffffff, bold
- [ ] Primary nav bar: background #b4282e, text #ffffff, hover #8d1a13
- [ ] Nav dropdowns function (hover-activated, no Superfish dependency)
- [ ] Search form/icon appears in primary nav bar
- [ ] Body text is Arial/Verdana, 12px, color #757474
- [ ] Post titles are Arial, 36px, bold
- [ ] Headings are Arial, bold, color #383737
- [ ] Widget titles are Georgia, italic, centered
- [ ] Breadcrumbs display "Home >> Title" in Georgia, 14px, italic; hidden on homepage
- [ ] Footer shows flat nav + "Designed by SMDdesigns | Powered by WordPress"
- [ ] Footer widget areas are not visible
- [ ] Homepage displays Instagram graphic, TablePress CTA row, Google Calendar iframe in order
- [ ] No Google Fonts loaded (confirm via browser network tab)

### 7.2 Content Integrity

- [ ] All posts render correctly (title, content, metadata, featured image)
- [ ] All pages render correctly
- [ ] ePanel shortcodes render grid layout: [one_half], [one_third], [one_fourth], [two_thirds], [three_fourths], and _last variants
- [ ] pausatf_club CPT archive and single views render
- [ ] pausatf_member CPT functions with membership plugin
- [ ] committee_update CPT archive and single views render
- [ ] race_result CPT archive and single views render
- [ ] meeting_minutes CPT archive and single views render
- [ ] [pausatf_members] shortcode output correct
- [ ] [pausatf_clubs] shortcode output correct
- [ ] [pausatf_scoresheet] shortcode output correct

### 7.3 Plugin Compatibility

- [ ] TablePress tables render (spot-check tables 93, 94, 98, 99, 101, 114, 138, 147, 152, 182, 197)
- [ ] Contact Form 7 renders and submits on contact pages
- [ ] ML Slider renders on pages where used
- [ ] Accordion plugin renders correctly
- [ ] Google Calendar iframe loads on homepage and /all-events/
- [ ] Widget-logic visibility rules fire correctly (test 3-5 page-specific widgets)
- [ ] Classic Editor still functions for content editing
- [ ] Jetpack features (OG tags, stats) function
- [ ] Jetpack Boost optimizations active
- [ ] Google Site Kit dashboard accessible
- [ ] WP Super Cache generates and serves cached pages
- [ ] Cloudflare integration (cf-auto-purge) clears cache on post update
- [ ] WP Mail SMTP sends test email
- [ ] Akismet active on comment forms
- [ ] UpdraftPlus backup runs successfully

### 7.4 MU-Plugins

- [ ] pausatf-content-architecture.php: CPTs in admin, taxonomies functional, roles correct, JSON-LD in page source
- [ ] pausatf-core.php: widget visibility logic applies
- [ ] cf-auto-purge.php: cache purges on post save

### 7.5 Navigation

- [ ] "Top_Menu" (49 items) renders in both secondary-menu and footer-menu locations
- [ ] All 49 menu items link to correct destinations
- [ ] Dropdown menus on primary nav work on hover
- [ ] No broken links in navigation

### 7.6 Performance

- [ ] Lighthouse Performance score equal to or better than production baseline
- [ ] No render-blocking resources introduced by theme swap
- [ ] No Google Fonts HTTP requests
- [ ] Page weight (total transfer size) equal to or less than production

### 7.7 Infrastructure

- [ ] Legacy content at /var/www/legacy/ still served via Apache Alias
- [ ] Legacy PHP redirect scripts function (x10host.com redirects)
- [ ] Ansible can deploy to the server without theme-related errors
- [ ] SSL/TLS certificate valid, no mixed content warnings

### 7.8 Rollback

- [ ] TheSource-child theme files remain on server (inactive)
- [ ] Switching back to TheSource-child in Appearance > Themes restores the old site
- [ ] Database backup from pre-cutover is verified restorable

---

## 8. Risks / Gotchas

### 8.1 GP Elements Are Database-Stored

GP Elements (hooks, blocks, layout overrides) are stored as custom post types in the WordPress database, not as files in the theme directory. This means:

- Elements do NOT transfer when you upload a theme to a new server. They must be recreated or exported via GP's built-in site export.
- Database migrations (dev to prod) must include the `wp_posts` entries for GP Elements.
- Version-controlling Elements requires a plugin like WP All Export or manual SQL dumps.

**Mitigation:** Document every GP Element (hook location, display rules, content) in a spreadsheet or a dedicated file in the repo. Recreate manually on production if database migration is not feasible.

### 8.2 The 49-Item Menu

The "Top_Menu" has 49 items. GP's navigation modules handle large menus, but:

- Dropdown depth must be tested. If the menu has 3+ nesting levels, verify GP dropdown CSS handles it.
- The menu is assigned to both secondary-menu and footer-menu. Verify GP allows the same menu object in two locations without conflict.
- 49 items in a footer nav will be visually long. Confirm this matches the current site's footer layout.

### 8.3 Widget-Logic Plugin Dependency

The widget-logic plugin uses PHP conditionals (e.g., `is_page(42)`) to control widget visibility. GP Elements have their own Display Rules system. During Phase 1, both systems coexist. Risks:

- Widget-logic conditions may interact unpredictably with GP's conditional loading.
- Test thoroughly: load 5+ pages that have different widget configurations and verify the correct widgets appear.

### 8.4 ePanel Shortcode Content Volume

The ePanel column shortcodes are embedded in post/page content across the site. The shim approach preserves them, but:

- The shim uses inline `float` styles. GP includes `* { box-sizing: border-box; }`, so floats should work. Test with actual content, not empty columns.
- Nested shortcodes (e.g., `[one_half][one_third]...[/one_third][/one_half]`) will break. Verify no content uses nesting.
- Content with shortcodes will not render correctly in the block editor's visual mode. Classic Editor must remain active for these pages.

### 8.5 Google Calendar Iframe (19 Feeds)

The Google Calendar embed uses 19 color-coded calendar feeds in AGENDA mode. This is raw HTML in page content, not a plugin. Risks:

- The iframe URL may be very long. Verify it is not truncated by any GP content filter.
- The iframe is fixed-width in the current layout. When Phase 2 enables responsive breakpoints, the iframe needs a responsive wrapper or it will overflow on mobile.

### 8.6 TheSource-Specific CSS Classes in Content

Some post/page content may contain TheSource-specific CSS classes (e.g., `et_pb_*`, TheSource utility classes). These classes will have no styling under GP.

- Content may lose formatting silently (no errors, just unstyled divs).
- **Pre-migration audit step:** Run `wp db query "SELECT ID, post_title FROM wp_posts WHERE post_content LIKE '%et_pb_%' AND post_status='publish'"` to find affected posts. Add CSS shims for any found classes, or edit the content.

### 8.7 Superfish JS Removal

TheSource uses Superfish for dropdown menus. GP does not load Superfish; it has its own dropdown CSS/JS.

- If any custom code or plugin depends on Superfish being loaded, it will break.
- The Superfish CSS classes on existing menu items will be inert under GP. This is fine unless custom CSS targets them.

### 8.8 Sidebar Widget Count (21 Widgets)

21 widgets in a single sidebar is unusually high. Most are custom_html.

- Page load may be slow if all 21 render on every page (widget-logic should prevent this, but verify).
- GP's sidebar styling (padding, margins, borders, widget title styles) differs from TheSource. All need manual CSS override.

### 8.9 Two Copies of Membership Plugin

The simpler copy in `/wp-content/plugins/` and the more mature version in `/wordpress/plugins/` could conflict.

- If both are activated, function name collisions or duplicate CPT registrations will cause fatal errors.
- **Pre-migration action:** Verify only the `/wordpress/plugins/` version is active. Deactivate and remove the simpler copy.

### 8.10 Legacy Content Alias

`/var/www/legacy/` is served via Apache Alias. The Ansible infrastructure manages this. The theme migration does not affect server config, but:

- If the dev environment uses OpenLiteSpeed (OLS) instead of Apache, the Alias directive will not work on dev. Test legacy content access on dev only if the dev server uses Apache or has an equivalent OLS rewrite rule.
- Some legacy PHP scripts redirect to x10host.com. Verify these are intentional and not a sign of compromised legacy code.

### 8.11 Cache Purge on Theme Switch

GP is compatible with WP Super Cache, but:

- After theme activation, the entire WP Super Cache must be purged. Stale cached pages will serve TheSource markup with GP stylesheets.
- Cloudflare cache must also be purged. The cf-auto-purge MU-plugin purges on post save, but a theme switch does not trigger post saves. Manual Cloudflare purge is required at cutover time.

### 8.12 No Dedicated Staging Environment

The infrastructure uses Ansible + Terraform with a single DigitalOcean droplet. If there is no separate staging environment:

- The dev environment (OLS + PHP 8.4) is the only place to test before production.
- Consider provisioning a temporary staging droplet via Terraform for the cutover day. Destroy it after the 30-day rollback window closes.

---

## 9. Final Recommendation

### Architecture Decision

Use **GeneratePress Premium with a child theme** (`pausatf-generatepress-child`).

**Rationale:**

1. **Lightweight framework.** GP's base is ~30KB, compared to TheSource's heavier footprint. Performance improves without additional effort.
2. **Hook system replaces template overrides.** The current child theme overrides 4 PHP templates (header.php, footer.php, single.php, page-blog.php). GP's Elements and hook system can replace all 4 without PHP template files in the child theme. This means GP core updates never conflict with child theme templates.
3. **Secondary Navigation is a first-class module.** The dual nav bar pattern that required custom header.php code in TheSource is a settings toggle in GP Premium.
4. **Display Rules replace widget-logic.** The widget-logic plugin's PHP conditionals are fragile and hard to audit. GP Elements with Display Rules provide the same per-page control through the admin UI, with no code to maintain.
5. **MU-plugins are theme-independent.** The three MU-plugins (content-architecture, core, cf-auto-purge) function without modification. CPTs, taxonomies, JSON-LD, widget visibility, and cache purge are all decoupled from the theme layer.
6. **No page builder lock-in.** The current site uses no page builder. Moving to GP preserves this simplicity. Content remains standard WordPress content with shortcodes, not proprietary builder markup.

### Migration Phases

| Phase | Scope | Duration | Risk |
|---|---|---|---|
| Phase 1: Visual parity | Theme swap with identical appearance | ~19 hours across stages 1-8 | Low to Medium |
| Phase 2: Responsive + cleanup | Enable breakpoints, migrate widgets to Elements, retire shortcode shims | ~8-12 hours | Low |

### Constraints

- **Do not redesign during migration.** Phase 1 is a platform swap, not a visual refresh. Every pixel must match.
- **Do not remove plugins during migration.** widget-logic, classic-editor, and all current plugins remain active through Phase 1.
- **Do not edit post/page content during migration.** The ePanel shortcode shims preserve existing content as-is.
- **Keep TheSource-child for 30 days post-cutover.** Rollback must be a single theme switch in the admin.

### Out of Scope

- Visual redesign (responsive layout, new color scheme, new typography). Separate project after Phase 2.
- SEO plugin installation (Yoast, RankMath). The MU-plugin JSON-LD continues to serve. Evaluate separately.
- Content audit or cleanup (stale pages, broken links, orphaned media). Separate project.
- Plugin rationalization (replacing WP Super Cache, consolidating Jetpack modules). Separate project.
- Membership plugin deduplication (removing the `/wp-content/plugins/` copy). Should be done before migration as a prerequisite, but is not a theme task.

### Cost

- GeneratePress Premium: $59/year (unlimited sites) or $249 lifetime.
- Estimated labor: ~27-31 hours total across both phases.
- Infrastructure: zero additional cost (uses existing dev environment and production droplet).
