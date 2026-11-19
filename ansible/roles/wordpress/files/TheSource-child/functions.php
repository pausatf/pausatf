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
 * Performance: Add resource hints for faster loading.
 */
function pausatf_resource_hints() {
    $logo = get_option( 'thesource_logo' );
    if ( $logo ) {
        echo '<link rel="preload" href="' . esc_url( $logo ) . '" as="image" fetchpriority="high" />' . "
";
    }
    echo '<link rel="preconnect" href="https://stats.wp.com" crossorigin />' . "
";
    echo '<link rel="dns-prefetch" href="//stats.wp.com" />' . "
";
}
add_action( 'wp_head', 'pausatf_resource_hints', 1 );

/**
 * Enqueue scripts and styles for the theme
 */
function thesource_child_enqueue_scripts() {
    // Use static version for proper browser caching
    // Increment version when making changes to CSS/JS files
    $version = '1.3.0';

    // Enqueue parent theme's style.css
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    // Enqueue child theme's dropdown.css
    wp_enqueue_style( 'child-dropdown', get_stylesheet_directory_uri() . '/dropdown.css', array( 'parent-style' ), $version );

    // Enqueue red theme overrides
    wp_enqueue_style( 'red-style', get_stylesheet_directory_uri() . '/style-Red.css', array( 'child-dropdown' ), $version );

    // Add browser-specific CSS
    $user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' );

    if ( $user_agent && str_contains( $user_agent, 'Chrome' ) ) {
        wp_enqueue_style( 'chrome-fixes', get_stylesheet_directory_uri() . '/chrome-fixes.css', array( 'child-dropdown' ), $version );
    }

    // Enqueue comment-reply only on posts/pages with open comments
    if ( is_singular() && comments_open() ) {
        wp_enqueue_script( 'comment-reply' );
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
    $user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' );

    if ( ! $user_agent ) {
        return $classes;
    }

    if ( str_contains( $user_agent, 'Chrome' ) ) {
        $classes[] = 'browser-chrome';
    } elseif ( str_contains( $user_agent, 'Safari' ) ) {
        $classes[] = 'browser-safari';
    } elseif ( str_contains( $user_agent, 'Firefox' ) ) {
        $classes[] = 'browser-firefox';
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
function pausatf_social_customize_register( WP_Customize_Manager $wp_customize ) {
    $wp_customize->add_section(
        'pausatf_social',
        [
            'title'    => __( 'Social Media Links', 'TheSource' ),
            'priority' => 30,
        ]
    );

    $controls = [
        'pausatf_social_facebook' => [
            'label'   => __( 'Facebook URL', 'TheSource' ),
            'default' => 'https://www.facebook.com/pausatf',
        ],
        'pausatf_social_instagram' => [
            'label'   => __( 'Instagram URL', 'TheSource' ),
            'default' => 'https://www.instagram.com/usatfpacificassoc/',
        ],
        'pausatf_social_youtube' => [
            'label'   => __( 'YouTube URL (leave blank to hide)', 'TheSource' ),
            'default' => 'https://www.youtube.com/channel/UC4UDU5_ALy26O1vU6rAOjjA',
        ],
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

/**
 * Use classic widgets editor for legacy theme compatibility.
 *
 * The block-based widgets screen can fail for this legacy theme stack.
 * For stable operations, force the classic widgets UI in wp-admin.
 */
add_filter( 'use_widgets_block_editor', '__return_false' );

/**
 * Disable Jetpack Protect math fallback to prevent login lockouts.
 *
 * Jetpack's brute-force fallback can challenge valid users in this stack.
 * Remove those callbacks so normal login and password reset continue to work.
 */
function pausatf_disable_jetpack_math_challenge() {
    $target_class = 'Automattic\\Jetpack\\Waf\\Brute_Force_Protection\\Brute_Force_Protection_Math_Authenticate';

    if ( ! class_exists( $target_class ) ) {
        return;
    }

    global $wp_filter;

    foreach ( array( 'login_form', 'authenticate' ) as $hook_name ) {
        if ( empty( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof WP_Hook ) {
            continue;
        }

        foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                if ( empty( $callback['function'] ) || ! is_array( $callback['function'] ) ) {
                    continue;
                }

                $callable = $callback['function'];
                $owner    = $callable[0];
                $method   = isset( $callable[1] ) ? (string) $callable[1] : '';

                if ( ! in_array( $method, array( 'math_form', 'math_authenticate', 'process_generate_math_page' ), true ) ) {
                    continue;
                }

                $owner_class = is_object( $owner ) ? get_class( $owner ) : ( is_string( $owner ) ? $owner : '' );

                if ( $owner_class === $target_class ) {
                    remove_filter( $hook_name, $callable, $priority );
                }
            }
        }
    }
}
add_action( 'init', 'pausatf_disable_jetpack_math_challenge', 1000 );

/**
 * Performance: Remove unused block editor and emoji assets from frontend.
 * This classic theme does not use the block editor on the frontend.
 */
function pausatf_remove_unused_frontend_assets() {
    // Block library CSS (not used by classic theme)
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );

    // Global styles (block theme feature, not used)
    wp_dequeue_style( 'global-styles' );

    // Emoji
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'wp_enqueue_scripts', 'pausatf_remove_unused_frontend_assets', 100 );

/**
 * Performance: Remove legacy IE6/7/8 stylesheets and scripts.
 * Nobody uses these browsers in 2026. Saves 5 HTTP requests + ~50KB.
 * Added: 2026-03-18
 */
function pausatf_remove_legacy_assets() {
    // IE6/7/8 stylesheets
    wp_dequeue_style( 'ie6style' );
    wp_dequeue_style( 'ie7style' );
    wp_dequeue_style( 'ie8style' );
    wp_deregister_style( 'ie6style' );
    wp_deregister_style( 'ie7style' );
    wp_deregister_style( 'ie8style' );

    // DD_belatedPNG - IE6 PNG transparency fix from 2009
    wp_dequeue_script( 'dd_belatedpng' );
    wp_dequeue_script( 'DD_belatedPNG' );
    wp_deregister_script( 'dd_belatedpng' );
    wp_deregister_script( 'DD_belatedPNG' );

    // Remove duplicate superfish.js (parent theme loads it without version,
    // child theme loads it with ver=1.2.0 -- keep child's version only)
    wp_dequeue_script( 'et_superfish' );
}
add_action( 'wp_enqueue_scripts', 'pausatf_remove_legacy_assets', 100 );

/**
 * Performance: Remove jQuery Migrate on frontend.
 * Not needed for modern jQuery 3.7+. Saves ~12KB.
 */
function pausatf_remove_jquery_migrate( $scripts ) {
    if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
        $script = $scripts->registered['jquery'];
        if ( $script->deps ) {
            $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
        }
    }
}
add_action( 'wp_default_scripts', 'pausatf_remove_jquery_migrate' );

/**
 * Performance: Add lazy loading to images in post content.
 */
function pausatf_lazy_load_images( $content ) {
    if ( is_admin() ) {
        return $content;
    }
    return preg_replace( '/<img(?![^>]*loading=)/', '<img loading="lazy"', $content );
}
add_filter( 'the_content', 'pausatf_lazy_load_images' );

/**
 * Performance: Only load Contact Form 7 assets on pages that use it.
 */
function pausatf_conditionally_load_cf7() {
    if ( ! is_page( array( 'pausatf-contacts', 'contact', 'fdx-contact' ) ) ) {
        wp_dequeue_style( 'contact-form-7' );
        wp_dequeue_script( 'contact-form-7' );
        wp_dequeue_script( 'wpcf7-recaptcha' );
    }
}
add_action( 'wp_enqueue_scripts', 'pausatf_conditionally_load_cf7', 999 );

/**
 * Performance: Remove parent theme's browser-sniffing common.js.
 * Duplicates the child theme's body_class approach and is not needed.
 */
function pausatf_remove_common_js() {
    wp_dequeue_script( 'et-core-common' );
    wp_deregister_script( 'et-core-common' );
}
add_action( 'wp_enqueue_scripts', 'pausatf_remove_common_js', 999 );

/**
 * Social media icon styles via wp_head.
 * Inline to bypass Jetpack Boost CSS concatenation.
 */
function pausatf_social_icon_css() {
    ?>
    <style>
        /* Hide breadcrumb on front page */
        body.home #breadcrumbs {
            display: none !important;
        }

        /* Hide social icons in primary menu - only show in red bar and footer */
        #page-menu .social-instagram,
        #page-menu .social-facebook {
            display: none !important;
        }

        #footer-bottom .social-instagram a,
        #footer-bottom .social-facebook a,
        #cat-nav-content ul.nav li.social-instagram a,
        #cat-nav-content ul.nav li.social-facebook a {
            display: inline-block !important;
            width: 32px !important;
            height: 32px !important;
            font-size: 0 !important;
            color: transparent !important;
            text-indent: -9999px !important;
            overflow: hidden !important;
            vertical-align: middle !important;
            background-size: 32px 32px !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            padding: 0 !important;
        }
        #footer-bottom .social-instagram a,
        #cat-nav-content ul.nav li.social-instagram a {
            background-image: url("/wp-content/uploads/2026/02/InstaLogo32x32.png") !important;
        }
        #footer-bottom .social-facebook a,
        #cat-nav-content ul.nav li.social-facebook a {
            background-image: url("/wp-content/uploads/2026/02/Facebook_Logo_32x32.png") !important;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'pausatf_social_icon_css', 999 );
