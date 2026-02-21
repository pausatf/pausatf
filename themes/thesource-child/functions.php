<?php
/**
 * TheSource Child Theme Functions
 *
 * @package TheSource-child
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue scripts and styles for the theme
 */
function thesource_child_enqueue_scripts() {
    // Use static version for proper browser caching
    // Increment version when making changes to CSS/JS files
    $version = '1.2.0';
    
    // Enqueue parent theme's style.css
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    
    // Enqueue child theme's dropdown.css
    wp_enqueue_style( 'child-dropdown', get_stylesheet_directory_uri() . '/dropdown.css', array( 'parent-style' ), $version );
    
    // Enqueue red theme overrides
    wp_enqueue_style( 'red-style', get_stylesheet_directory_uri() . '/style-Red.css', array( 'child-dropdown' ), $version );
 
    // Add browser-specific CSS
    $user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
    
    if ( $user_agent && false !== strpos( $user_agent, 'Chrome' ) ) {
        wp_enqueue_style( 'chrome-fixes', get_stylesheet_directory_uri() . '/chrome-fixes.css', array( 'child-dropdown' ), $version );
    }
    
    // Enqueue jQuery first
    wp_enqueue_script( 'jquery' );
    
    // Enqueue superfish.js
    wp_enqueue_script( 'superfish', get_template_directory_uri() . '/js/superfish.js', array( 'jquery' ), $version, true );
    
    // Initialize superfish
    wp_add_inline_script(
        'superfish',
        'jQuery(document).ready(function($) {
            setTimeout(function() {
                try {
                    $("ul.superfish, ul.nav").superfish({
                        delay: 200,
                        animation: {opacity:"show",height:"show"},
                        speed: "fast",
                        autoArrows: true,
                        dropShadows: false,
                        disableHI: false,
                        onBeforeShow: function() {
                            $(this).css("display", "none").show();
                        }
                    });
                    console.log("Superfish initialized successfully");
                } catch(e) {
                    console.error("Error initializing superfish: " + e.message);
                }
            }, 500);
        });'
    );
}
add_action( 'wp_enqueue_scripts', 'thesource_child_enqueue_scripts', 999 );

/**
 * Add browser class to body
 *
 * @param array $classes The body classes.
 * @return array Modified body classes
 */
function thesource_child_browser_body_class( $classes ) {
    $user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
    
    if ( ! $user_agent ) {
        return $classes;
    }
    
    if ( false !== strpos( $user_agent, 'Chrome' ) ) {
        $classes[] = 'browser-chrome';
    } elseif ( false !== strpos( $user_agent, 'Safari' ) ) {
        $classes[] = 'browser-safari';
    } elseif ( false !== strpos( $user_agent, 'Firefox' ) ) {
        $classes[] = 'browser-firefox';
    } elseif ( false !== strpos( $user_agent, 'MSIE' ) || false !== strpos( $user_agent, 'Trident' ) ) {
        $classes[] = 'browser-ie';
    } elseif ( false !== strpos( $user_agent, 'Edge' ) ) {
        $classes[] = 'browser-edge';
    }
    
    return $classes;
}
add_filter( 'body_class', 'thesource_child_browser_body_class' );

/**
 * Register social media link controls in the Customizer.
 * Allows Cynci to update URLs via Appearance → Customize → Social Media Links.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function pausatf_social_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'pausatf_social',
		[
			'title'    => __( 'Social Media Links', 'TheSource' ),
			'priority' => 30,
		]
	);

	$controls = [
		'pausatf_social_facebook'  => [ 'label' => __( 'Facebook URL', 'TheSource' ),                 'default' => 'https://www.facebook.com/pausatf' ],
		'pausatf_social_instagram' => [ 'label' => __( 'Instagram URL', 'TheSource' ),                'default' => 'https://www.instagram.com/usatfpacificassoc/' ],
		'pausatf_social_youtube'   => [ 'label' => __( 'YouTube URL (leave blank to hide)', 'TheSource' ), 'default' => 'https://www.youtube.com/channel/UC4UDU5_ALy26O1vU6rAOjjA' ],
	];

	foreach ( $controls as $id => $args ) {
		$wp_customize->add_setting( $id, [
			'default'           => $args['default'],
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( $id, [
			'label'   => $args['label'],
			'section' => 'pausatf_social',
			'type'    => 'url',
		] );
	}
}
add_action( 'customize_register', 'pausatf_social_customize_register' );
