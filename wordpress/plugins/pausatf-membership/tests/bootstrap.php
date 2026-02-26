<?php

/**
 * PHPUnit bootstrap for pausatf-membership unit tests.
 *
 * Stubs the WordPress functions and classes the plugin uses so the include
 * files load cleanly without a running WordPress install.  Only the subset
 * of WP's API that is referenced at class-definition time (outside of
 * methods) needs to be present here; method-level WP calls are handled per
 * test via overrides or are avoided by testing only pure-PHP paths.
 */

declare( strict_types=1 );

// ------------------------------------------------------------------
// Constants the plugin guards against.
// ------------------------------------------------------------------

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'PAUSATF_MEMBERSHIP_VERSION' ) ) {
    define( 'PAUSATF_MEMBERSHIP_VERSION', '0.1.0' );
}

if ( ! defined( 'PAUSATF_MEMBERSHIP_DIR' ) ) {
    define( 'PAUSATF_MEMBERSHIP_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'PAUSATF_MEMBERSHIP_URL' ) ) {
    define( 'PAUSATF_MEMBERSHIP_URL', 'https://example.com/wp-content/plugins/pausatf-membership/' );
}

// ------------------------------------------------------------------
// Minimal WordPress function stubs.
// ------------------------------------------------------------------

if ( ! function_exists( 'add_action' ) ) {
    function add_action( string $hook, mixed $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        return true;
    }
}

if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( string $tag, mixed $callback ): void {}
}

if ( ! function_exists( 'register_post_type' ) ) {
    function register_post_type( string $post_type, array $args = [] ): object {
        return new stdClass();
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( '_x' ) ) {
    function _x( string $text, string $context, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( string $text, string $domain = 'default' ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( string $url ): string {
        return $url;
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( string $str ): string {
        return trim( strip_tags( $str ) );
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( string $key ): string {
        return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) ?? '';
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( mixed $value ): mixed {
        return is_string( $value ) ? stripslashes( $value ) : $value;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $option, mixed $default = false ): mixed {
        return $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( string $option, mixed $value ): bool {
        return true;
    }
}

if ( ! function_exists( 'error_log' ) ) {
    // Already a PHP built-in, but may be suppressed in test environments.
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( mixed $thing ): bool {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id(): int {
        return 0;
    }
}

// ------------------------------------------------------------------
// WP_Error stub — the real class signature is sufficient here.
// ------------------------------------------------------------------

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private string $code;
        private string $message;

        public function __construct( string $code = '', string $message = '' ) {
            $this->code    = $code;
            $this->message = $message;
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }
    }
}

if ( ! class_exists( 'WP_Post' ) ) {
    class WP_Post {
        public int    $ID         = 0;
        public string $post_title = '';
        public string $post_type  = '';
        public string $post_status = 'publish';
    }
}

// ------------------------------------------------------------------
// Load plugin classes (order matches main plugin file).
// ------------------------------------------------------------------

require_once dirname( __DIR__ ) . '/includes/class-cpt-club.php';
require_once dirname( __DIR__ ) . '/includes/class-cpt-member.php';
require_once dirname( __DIR__ ) . '/includes/class-usatf-sync.php';
require_once dirname( __DIR__ ) . '/includes/class-score-handler.php';
