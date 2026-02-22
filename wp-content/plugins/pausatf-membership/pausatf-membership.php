<?php
/**
 * Plugin Name:       PAUSATF Membership
 * Description:       Club directory, member roster, and scoresheet submission for PA USATF. Replaces legacy Teeters PHP scripts (2007–2015).
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            PAUSATF
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pausatf-membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PAUSATF_PLUGIN_VERSION', '0.1.0' );
define( 'PAUSATF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAUSATF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// CSV export path for Sport80 sync — override via wp-config.php or env.
// Sport80 has no public API; the sync expects a manually placed CSV file.
if ( ! defined( 'PAUSATF_SYNC_CSV_PATH' ) ) {
	$_env_csv = getenv( 'PAUSATF_SYNC_CSV_PATH' );
	define( 'PAUSATF_SYNC_CSV_PATH', $_env_csv ?: '' );
}

require_once PAUSATF_PLUGIN_DIR . 'includes/class-cpt-club.php';
require_once PAUSATF_PLUGIN_DIR . 'includes/class-cpt-member.php';
require_once PAUSATF_PLUGIN_DIR . 'includes/class-score-handler.php';
require_once PAUSATF_PLUGIN_DIR . 'includes/class-usatf-sync.php';
require_once PAUSATF_PLUGIN_DIR . 'includes/shortcodes.php';

register_activation_hook( __FILE__, 'pausatf_activate' );
register_deactivation_hook( __FILE__, 'pausatf_deactivate' );

function pausatf_activate(): void {
	PAUSATF_CPT_Club::register();
	PAUSATF_CPT_Member::register();
	flush_rewrite_rules();
	PAUSATF_Score_Handler::install_schema();
	PAUSATF_USATF_Sync::schedule();
}

function pausatf_deactivate(): void {
	flush_rewrite_rules();
	PAUSATF_USATF_Sync::unschedule();
}

add_action( 'init', array( 'PAUSATF_CPT_Club', 'register' ) );
add_action( 'init', array( 'PAUSATF_CPT_Member', 'register' ) );

add_action( 'wp_enqueue_scripts', 'pausatf_enqueue_scripts' );

function pausatf_enqueue_scripts(): void {
	// Only load on pages that use our shortcodes — checked via has_shortcode()
	// on the queried post. Enqueueing here is a lightweight placeholder; a
	// block-based approach would use block.json assets instead.
	if ( ! is_singular() ) {
		return;
	}
	$post = get_queried_object();
	if ( ! ( $post instanceof WP_Post ) ) {
		return;
	}
	$shortcodes = array( 'pausatf_members', 'pausatf_clubs', 'pausatf_scoresheet' );
	$needed     = false;
	foreach ( $shortcodes as $tag ) {
		if ( has_shortcode( $post->post_content, $tag ) ) {
			$needed = true;
			break;
		}
	}
	if ( ! $needed ) {
		return;
	}
	wp_enqueue_style(
		'pausatf-membership',
		PAUSATF_PLUGIN_URL . 'assets/pausatf-membership.css',
		array(),
		PAUSATF_PLUGIN_VERSION
	);
}

add_shortcode( 'pausatf_members',    'pausatf_members_shortcode' );
add_shortcode( 'pausatf_clubs',      'pausatf_clubs_shortcode' );
add_shortcode( 'pausatf_scoresheet', 'pausatf_scoresheet_shortcode' );
