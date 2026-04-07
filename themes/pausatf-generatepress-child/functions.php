<?php
/**
 * PAUSATF GeneratePress child theme functions.
 *
 * Migrated from TheSource-child v0.3-php8.
 * Preserves visual parity with the original Elegant Themes design.
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
		array('generate-style'),
		$theme_version
	);

	// Sport prefix label colors
	wp_enqueue_style(
		'pausatf-sport-labels',
		$theme_uri . '/assets/css/sport-labels.css',
		array('generate-style'),
		$theme_version
	);

	// Google Calendar iframe responsive wrapper
	wp_enqueue_style(
		'pausatf-calendar-embed',
		$theme_uri . '/assets/css/calendar-embed.css',
		array('generate-style'),
		$theme_version
	);
}, 20);

/**
 * ePanel column shortcode shims.
 *
 * Reproduce the float-based grid that TheSource's ePanel provided.
 * Content authored with [one_half], [one_third], etc. continues to work.
 */
$pausatf_column_shortcodes = array(
	'one_half'       => '50%',
	'one_third'      => '33.333%',
	'two_thirds'     => '66.666%',
	'one_fourth'     => '25%',
	'three_fourths'  => '75%',
);

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
			'<div class="pausatf-breadcrumbs"><div class="inside-container"><a href="%s">Home</a> &raquo; %s</div></div>',
			esc_url(home_url('/')),
			esc_html($title)
		);
	}
});

/**
 * Disable default GP footer widgets and copyright.
 */
add_filter('generate_footer_widgets_active', '__return_false');

add_action('after_setup_theme', function () {
	remove_action('generate_credits', 'generate_add_footer_info');
}, 20);

/**
 * GP hook: custom footer with flat nav + credit line.
 * Matches TheSource's #footer-bottom layout.
 */
add_action('generate_footer', function () {
	echo '<div class="pausatf-footer-inner">';

	wp_nav_menu(array(
		'theme_location'  => 'footer',
		'container'       => 'nav',
		'container_class' => 'pausatf-footer-nav',
		'fallback_cb'     => false,
		'depth'           => 1,
	));

	echo '<p class="pausatf-footer-credit">';
	echo 'Designed by SMDdesigns | Powered by WordPress';
	echo '</p>';
	echo '</div>';
});

/**
 * Register menu locations matching the site's three nav areas.
 *
 * GP Premium provides primary and secondary nav. We add a footer location.
 */
add_action('after_setup_theme', function () {
	register_nav_menus(array(
		'footer' => 'Footer Navigation',
	));
}, 15);

/**
 * Conditional Contact Form 7 asset loading.
 * Only load CF7 CSS/JS on pages that actually use a form.
 * Carried over from TheSource-child functions.php.
 */
add_action('wp_enqueue_scripts', function () {
	$cf7_pages = array('pausatf-contacts', 'contact', 'fdx-contact');
	$load_cf7  = false;

	if (is_page($cf7_pages)) {
		$load_cf7 = true;
	} elseif (is_singular()) {
		global $post;
		if (isset($post) && is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'contact-form-7')) {
			$load_cf7 = true;
		}
	}

	if (!$load_cf7) {
		wp_dequeue_style('contact-form-7');
		wp_dequeue_script('contact-form-7');
		wp_dequeue_script('wpcf7-recaptcha');
	}
}, 99);

/**
 * Performance: disable emoji, block editor CSS, jQuery Migrate.
 * Carried over from TheSource-child.
 */
add_action('init', function () {
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
});

add_action('wp_enqueue_scripts', function () {
	// Remove block library CSS on frontend if not using blocks in content
	wp_dequeue_style('wp-block-library');
	wp_dequeue_style('wp-block-library-theme');
	wp_dequeue_style('global-styles');
}, 100);

add_action('wp_default_scripts', function ($scripts) {
	if (!is_admin() && isset($scripts->registered['jquery'])) {
		$scripts->registered['jquery']->deps = array_diff(
			$scripts->registered['jquery']->deps,
			array('jquery-migrate')
		);
	}
});

/**
 * Disable the block-based widget editor.
 * Preserves classic widget interface for sidebar-5 management.
 */
add_filter('gutenberg_use_widgets_block_editor', '__return_false');
add_filter('use_widgets_block_editor', '__return_false');
