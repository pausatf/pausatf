#!/usr/bin/env bash
set -euo pipefail
#
# GeneratePress Migration Setup Script
# Run this on the dev server after Ansible deploys the child theme.
#
# Prerequisites:
#   - WordPress installed and accessible via WP-CLI
#   - GeneratePress theme installed (free version)
#   - GeneratePress Premium plugin installed and activated
#   - pausatf-generatepress-child theme uploaded to wp-content/themes/
#   - Database cloned from production
#
# Usage: bash setup-generatepress.sh [--wp-path /var/www/html]

WP_PATH="${1:---path=/var/www/html}"
WP="wp ${WP_PATH}"

echo "=== Stage 1: Install and activate GeneratePress ==="

# Install GP free theme if not present
$WP theme is-installed generatepress 2>/dev/null || $WP theme install generatepress

# Activate the child theme (requires parent to be installed)
$WP theme activate pausatf-generatepress-child
echo "Child theme activated."

# Enable GP Premium modules
$WP option update generate_package_secondary_nav 'activated'
$WP option update generate_package_menu_plus 'activated'
$WP option update generate_package_colors 'activated'
$WP option update generate_package_typography 'activated'
$WP option update generate_package_blog 'activated'
$WP option update generate_package_elements 'activated'
$WP option update generate_package_sections 'activated'
echo "GP Premium modules activated."

echo ""
echo "=== Stage 2: Global Customizer Settings ==="

# Container width: 960px (matching TheSource fixed layout)
$WP option patch update generate_settings container_width 960

# Content/sidebar layout
$WP option patch update generate_settings layout_setting 'right-sidebar'
$WP option patch update generate_settings blog_layout_setting 'right-sidebar'

# Sidebar width: 323px
$WP option patch update generate_settings right_sidebar_width 35

# Header
$WP option patch update generate_settings header_layout_setting 'default'
$WP option patch update generate_settings header_alignment_setting 'left'

# Colors: body
$WP option patch update generate_settings body_text_color '#757474'
$WP option patch update generate_settings body_link_color '#b4282e'
$WP option patch update generate_settings body_link_color_hover '#8d1a13'

# Colors: header
$WP option patch update generate_settings header_background_color '#ffffff'
$WP option patch update generate_settings header_text_color '#383737'
$WP option patch update generate_settings header_link_color '#b4282e'

# Colors: headings
$WP option patch update generate_settings h1_color '#383737'
$WP option patch update generate_settings h2_color '#383737'
$WP option patch update generate_settings h3_color '#383737'

# Colors: primary navigation (red bar)
$WP option patch update generate_settings navigation_background_color '#b4282e'
$WP option patch update generate_settings navigation_text_color '#ffffff'
$WP option patch update generate_settings navigation_background_hover_color '#8d1a13'
$WP option patch update generate_settings navigation_text_hover_color '#ffffff'
$WP option patch update generate_settings navigation_background_current_color '#8d1a13'
$WP option patch update generate_settings navigation_text_current_color '#ffffff'

# Colors: submenus
$WP option patch update generate_settings subnavigation_background_color '#ffffff'
$WP option patch update generate_settings subnavigation_text_color '#383737'
$WP option patch update generate_settings subnavigation_background_hover_color '#b4282e'
$WP option patch update generate_settings subnavigation_text_hover_color '#ffffff'

# Colors: secondary navigation (dark bar)
$WP option patch update generate_settings secondary_navigation_background_color '#232323'
$WP option patch update generate_settings secondary_navigation_text_color '#ffffff'

# Colors: sidebar
$WP option patch update generate_settings sidebar_widget_title_color '#383737'
$WP option patch update generate_settings sidebar_text_color '#757474'
$WP option patch update generate_settings sidebar_link_color '#b4282e'

# Colors: footer
$WP option patch update generate_settings footer_background_color '#1c1c1c'
$WP option patch update generate_settings footer_text_color '#757474'
$WP option patch update generate_settings footer_link_color '#ffffff'
$WP option patch update generate_settings footer_link_hover_color '#b4282e'

# Typography: body
$WP option patch update generate_settings font_body 'Arial, Verdana, sans-serif'
$WP option patch update generate_settings body_font_size '12'
$WP option patch update generate_settings body_line_height '21'

# Typography: headings
$WP option patch update generate_settings font_heading_1 'Arial, sans-serif'
$WP option patch update generate_settings heading_1_font_size '30'
$WP option patch update generate_settings heading_1_weight '400'
$WP option patch update generate_settings heading_2_font_size '24'
$WP option patch update generate_settings heading_3_font_size '20'
$WP option patch update generate_settings heading_4_font_size '18'
$WP option patch update generate_settings heading_5_font_size '16'

# Typography: entry title
$WP option patch update generate_settings single_post_title_font_size '36'
$WP option patch update generate_settings single_post_title_weight 'bold'

# Disable Google Fonts
$WP option patch update generate_settings font_body_variants ''
$WP option patch update generate_settings google_font_display 'swap'

echo "Customizer settings applied."

echo ""
echo "=== Stage 3: Menu Assignment ==="

# Assign Top_Menu to GP nav locations
# GP uses 'primary' and 'secondary' as location slugs
MENU_ID=$($WP menu list --format=csv --fields=term_id,name | grep 'Top_Menu' | cut -d',' -f1)

if [ -n "$MENU_ID" ]; then
    $WP menu location assign "$MENU_ID" primary
    $WP menu location assign "$MENU_ID" secondary
    $WP menu location assign "$MENU_ID" footer
    echo "Top_Menu (ID: $MENU_ID) assigned to primary, secondary, and footer locations."
else
    echo "WARNING: Top_Menu not found. Menu assignment skipped."
fi

echo ""
echo "=== Stage 4: Verify Plugins ==="

# Ensure required plugins are active
for plugin in classic-editor contact-form-7 tablepress widget-logic; do
    if $WP plugin is-active "$plugin" 2>/dev/null; then
        echo "OK: $plugin is active"
    else
        echo "WARNING: $plugin is not active. Attempting activation..."
        $WP plugin activate "$plugin" 2>/dev/null || echo "FAILED: could not activate $plugin"
    fi
done

echo ""
echo "=== Stage 5: Flush and Verify ==="

$WP rewrite flush
$WP cache flush
$WP transient delete --all

echo "Caches flushed."
echo ""
echo "Setup complete. Visit https://dev.pausatf.org to verify."
echo ""
echo "Manual steps remaining:"
echo "  1. Upload logo via Customizer > Header > Logo"
echo "  2. Create GP Elements for CPT layouts (Appearance > Elements)"
echo "  3. Run visual regression comparison against production"
echo "  4. Test all TablePress tables, CF7 forms, and Google Calendar embeds"
