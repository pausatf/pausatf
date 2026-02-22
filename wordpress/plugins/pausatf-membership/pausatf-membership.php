<?php
/**
 * Plugin Name:  PAUSATF Membership
 * Plugin URI:   https://github.com/pausatf/pausatf
 * Description:  Club directory, member roster, and score-sheet for PA/USATF.
 *               Replaces the legacy Teeters PHP scripts (2007–2015) that relied
 *               on the removed mysql_* API.  Provides [pausatf_clubs],
 *               [pausatf_members], and [pausatf_scoresheet] shortcodes, two
 *               Gutenberg blocks, and a WP-Cron stub for future USATF sync.
 * Version:      0.1.0
 * Requires PHP: 7.4
 * Author:       PAUSATF
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  pausatf-membership
 */

declare( strict_types=1 );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PAUSATF_MEMBERSHIP_VERSION', '0.1.0' );
define( 'PAUSATF_MEMBERSHIP_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAUSATF_MEMBERSHIP_URL', plugin_dir_url( __FILE__ ) );

require_once PAUSATF_MEMBERSHIP_DIR . 'includes/class-cpt-club.php';
require_once PAUSATF_MEMBERSHIP_DIR . 'includes/class-cpt-member.php';
require_once PAUSATF_MEMBERSHIP_DIR . 'includes/class-usatf-sync.php';
require_once PAUSATF_MEMBERSHIP_DIR . 'includes/class-score-handler.php';

// -----------------------------------------------------------------------
// Bootstrap
// -----------------------------------------------------------------------

add_action( 'init', [ PAUSATF_CPT_Club::class, 'register' ] );
add_action( 'init', [ PAUSATF_CPT_Member::class, 'register' ] );
add_action( 'init', [ PAUSATF_Score_Handler::class, 'register_shortcode' ] );
add_action( 'init', [ PAUSATF_CPT_Club::class, 'register_shortcode' ] );
add_action( 'init', [ PAUSATF_CPT_Member::class, 'register_shortcode' ] );

add_action( 'wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'pausatf-membership',
        PAUSATF_MEMBERSHIP_URL . 'assets/pausatf-membership.css',
        [],
        PAUSATF_MEMBERSHIP_VERSION
    );
} );

// Register block assets (editor side).
add_action( 'enqueue_block_editor_assets', static function (): void {
    $blocks = [ 'club-directory', 'member-lookup' ];
    foreach ( $blocks as $block ) {
        $dir = PAUSATF_MEMBERSHIP_DIR . "blocks/{$block}/";
        if ( file_exists( $dir . 'index.js' ) ) {
            wp_enqueue_script(
                "pausatf-block-{$block}",
                PAUSATF_MEMBERSHIP_URL . "blocks/{$block}/index.js",
                [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
                PAUSATF_MEMBERSHIP_VERSION,
                true
            );
        }
    }
} );

// -----------------------------------------------------------------------
// Template override: route archive-pausatf_club / archive-pausatf_member
// -----------------------------------------------------------------------

add_filter(
    'archive_template',
    static function ( string $template ): string {
        $post_type = get_query_var( 'post_type' );
        if ( ! in_array( $post_type, [ 'pausatf_club', 'pausatf_member' ], true ) ) {
            return $template;
        }
        $plugin_template = PAUSATF_MEMBERSHIP_DIR . "templates/archive-{$post_type}.php";
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
        return $template;
    }
);

// -----------------------------------------------------------------------
// Activation / deactivation / uninstall
// -----------------------------------------------------------------------

register_activation_hook( __FILE__, 'pausatf_membership_activate' );
register_deactivation_hook( __FILE__, 'pausatf_membership_deactivate' );

/**
 * Create custom DB table and schedule cron on activation.
 */
function pausatf_membership_activate(): void {
    global $wpdb;

    $table      = $wpdb->prefix . 'pausatf_scores';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
        meet_name   VARCHAR(200) NOT NULL DEFAULT '',
        meet_date   DATE         NOT NULL,
        club_id     BIGINT(20)   UNSIGNED NOT NULL DEFAULT 0,
        athlete     VARCHAR(200) NOT NULL DEFAULT '',
        event_name  VARCHAR(100) NOT NULL DEFAULT '',
        mark        VARCHAR(50)  NOT NULL DEFAULT '',
        submitted_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        submitted_by BIGINT(20)  UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY club_id    (club_id),
        KEY meet_date  (meet_date)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Schedule the USATF sync cron if not already scheduled.
    if ( ! wp_next_scheduled( 'pausatf_usatf_sync' ) ) {
        wp_schedule_event( time(), 'daily', 'pausatf_usatf_sync' );
    }

    // Flush rewrite rules so the new CPT archives resolve.
    PAUSATF_CPT_Club::register();
    PAUSATF_CPT_Member::register();
    flush_rewrite_rules();
}

/**
 * Remove cron job on deactivation.  Table and data are preserved.
 */
function pausatf_membership_deactivate(): void {
    $timestamp = wp_next_scheduled( 'pausatf_usatf_sync' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'pausatf_usatf_sync' );
    }
    flush_rewrite_rules();
}
