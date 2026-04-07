<?php

/**
 * PHPUnit bootstrap for pausatf-generatepress-child unit tests.
 *
 * Stubs WordPress functions used by functions.php so the file loads
 * cleanly without a running WordPress install.  Captures hook and
 * shortcode registrations for inspection in tests.
 */

declare(strict_types=1);

// ------------------------------------------------------------------
// Constants
// ------------------------------------------------------------------

if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// ------------------------------------------------------------------
// Global registries — tests inspect these to verify behaviour.
// ------------------------------------------------------------------

/** @var array<int, array{hook: string, callback: callable, priority: int}> */
$GLOBALS['_pausatf_actions'] = [];

/** @var array<int, array{hook: string, callback: callable, priority: int}> */
$GLOBALS['_pausatf_filters'] = [];

/** @var array<string, callable> */
$GLOBALS['_pausatf_shortcodes'] = [];

/** @var array<string, array<int, array{handle: string, src: string, deps: array, ver: string|null}>> */
$GLOBALS['_pausatf_enqueued'] = ['styles' => [], 'scripts' => []];

/** @var array<string, bool> */
$GLOBALS['_pausatf_dequeued'] = ['styles' => [], 'scripts' => []];

/** @var array<string, string> */
$GLOBALS['_pausatf_nav_menus'] = [];

/** @var array<string, array> */
$GLOBALS['_pausatf_wp_nav_menu_calls'] = [];

/** @var array<string, callable> */
$GLOBALS['_pausatf_removed_actions'] = [];

/** @var array<string, callable> */
$GLOBALS['_pausatf_removed_filters'] = [];

// ------------------------------------------------------------------
// Condition flags — tests toggle these before invoking callbacks.
// ------------------------------------------------------------------

$GLOBALS['_pausatf_is_front_page'] = false;
$GLOBALS['_pausatf_is_singular']   = false;
$GLOBALS['_pausatf_is_archive']    = false;
$GLOBALS['_pausatf_is_search']     = false;
$GLOBALS['_pausatf_is_404']        = false;
$GLOBALS['_pausatf_is_page']       = false;
$GLOBALS['_pausatf_is_admin']      = false;
$GLOBALS['_pausatf_page_slug']     = '';
$GLOBALS['_pausatf_the_title']     = '';
$GLOBALS['_pausatf_archive_title'] = '';

// ------------------------------------------------------------------
// Reset helper — call at the start of each test.
// ------------------------------------------------------------------

function pausatf_test_reset(): void
{
    $GLOBALS['_pausatf_actions']    = [];
    $GLOBALS['_pausatf_filters']    = [];
    $GLOBALS['_pausatf_shortcodes'] = [];
    $GLOBALS['_pausatf_enqueued']   = ['styles' => [], 'scripts' => []];
    $GLOBALS['_pausatf_dequeued']   = ['styles' => [], 'scripts' => []];
    $GLOBALS['_pausatf_nav_menus']  = [];
    $GLOBALS['_pausatf_wp_nav_menu_calls'] = [];
    $GLOBALS['_pausatf_removed_actions']   = [];
    $GLOBALS['_pausatf_removed_filters']   = [];

    $GLOBALS['_pausatf_is_front_page'] = false;
    $GLOBALS['_pausatf_is_singular']   = false;
    $GLOBALS['_pausatf_is_archive']    = false;
    $GLOBALS['_pausatf_is_search']     = false;
    $GLOBALS['_pausatf_is_404']        = false;
    $GLOBALS['_pausatf_is_page']       = false;
    $GLOBALS['_pausatf_is_admin']      = false;
    $GLOBALS['_pausatf_page_slug']     = '';
    $GLOBALS['_pausatf_the_title']     = '';
    $GLOBALS['_pausatf_archive_title'] = '';
}

// ------------------------------------------------------------------
// WordPress function stubs
// ------------------------------------------------------------------

if (! function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $GLOBALS['_pausatf_actions'][] = [
            'hook'     => $hook,
            'callback' => $callback,
            'priority' => $priority,
        ];
        return true;
    }
}

if (! function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $GLOBALS['_pausatf_filters'][] = [
            'hook'     => $hook,
            'callback' => $callback,
            'priority' => $priority,
        ];
        return true;
    }
}

if (! function_exists('remove_action')) {
    function remove_action(string $hook, $callback, int $priority = 10): bool
    {
        $GLOBALS['_pausatf_removed_actions'][$hook . ':' . $priority] = $callback;
        return true;
    }
}

if (! function_exists('remove_filter')) {
    function remove_filter(string $hook, $callback, int $priority = 10): bool
    {
        $GLOBALS['_pausatf_removed_filters'][$hook . ':' . $priority] = $callback;
        return true;
    }
}

if (! function_exists('add_shortcode')) {
    function add_shortcode(string $tag, callable $callback): void
    {
        $GLOBALS['_pausatf_shortcodes'][$tag] = $callback;
    }
}

if (! function_exists('do_shortcode')) {
    function do_shortcode(string $content): string
    {
        return $content;
    }
}

if (! function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], ?string $ver = null, string $media = 'all'): void
    {
        $GLOBALS['_pausatf_enqueued']['styles'][$handle] = [
            'handle' => $handle,
            'src'    => $src,
            'deps'   => $deps,
            'ver'    => $ver,
        ];
    }
}

if (! function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], ?string $ver = null, bool $in_footer = false): void
    {
        $GLOBALS['_pausatf_enqueued']['scripts'][$handle] = [
            'handle' => $handle,
            'src'    => $src,
            'deps'   => $deps,
            'ver'    => $ver,
        ];
    }
}

if (! function_exists('wp_dequeue_style')) {
    function wp_dequeue_style(string $handle): void
    {
        $GLOBALS['_pausatf_dequeued']['styles'][$handle] = true;
        unset($GLOBALS['_pausatf_enqueued']['styles'][$handle]);
    }
}

if (! function_exists('wp_dequeue_script')) {
    function wp_dequeue_script(string $handle): void
    {
        $GLOBALS['_pausatf_dequeued']['scripts'][$handle] = true;
        unset($GLOBALS['_pausatf_enqueued']['scripts'][$handle]);
    }
}

if (! function_exists('wp_get_theme')) {
    function wp_get_theme(): object
    {
        return new class {
            public function get(string $key): string
            {
                if ($key === 'Version') {
                    return '1.0.0';
                }
                return '';
            }
        };
    }
}

if (! function_exists('get_stylesheet_directory_uri')) {
    function get_stylesheet_directory_uri(): string
    {
        return 'https://example.com/wp-content/themes/pausatf-generatepress-child';
    }
}

if (! function_exists('home_url')) {
    function home_url(string $path = ''): string
    {
        return 'https://example.com' . $path;
    }
}

if (! function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return $url;
    }
}

if (! function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (! function_exists('is_front_page')) {
    function is_front_page(): bool
    {
        return $GLOBALS['_pausatf_is_front_page'];
    }
}

if (! function_exists('is_singular')) {
    function is_singular(): bool
    {
        return $GLOBALS['_pausatf_is_singular'];
    }
}

if (! function_exists('is_archive')) {
    function is_archive(): bool
    {
        return $GLOBALS['_pausatf_is_archive'];
    }
}

if (! function_exists('is_search')) {
    function is_search(): bool
    {
        return $GLOBALS['_pausatf_is_search'];
    }
}

if (! function_exists('is_404')) {
    function is_404(): bool
    {
        return $GLOBALS['_pausatf_is_404'];
    }
}

if (! function_exists('is_page')) {
    function is_page($page = ''): bool
    {
        if (is_array($page)) {
            return in_array($GLOBALS['_pausatf_page_slug'], $page, true);
        }
        if (is_string($page) && $page !== '') {
            return $GLOBALS['_pausatf_page_slug'] === $page;
        }
        return $GLOBALS['_pausatf_is_page'];
    }
}

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        return $GLOBALS['_pausatf_is_admin'];
    }
}

if (! function_exists('get_the_title')) {
    function get_the_title(): string
    {
        return $GLOBALS['_pausatf_the_title'];
    }
}

if (! function_exists('get_the_archive_title')) {
    function get_the_archive_title(): string
    {
        return $GLOBALS['_pausatf_archive_title'];
    }
}

if (! function_exists('register_nav_menus')) {
    function register_nav_menus(array $menus): void
    {
        $GLOBALS['_pausatf_nav_menus'] = array_merge($GLOBALS['_pausatf_nav_menus'], $menus);
    }
}

if (! function_exists('wp_nav_menu')) {
    function wp_nav_menu(array $args = []): void
    {
        $location = $args['theme_location'] ?? 'unknown';
        $GLOBALS['_pausatf_wp_nav_menu_calls'][$location] = $args;

        $fallback = $args['fallback_cb'] ?? null;
        if ($fallback === false) {
            // No fallback — render nothing when no menu assigned.
            return;
        }
    }
}

if (! function_exists('__return_false')) {
    function __return_false(): bool
    {
        return false;
    }
}

// ------------------------------------------------------------------
// WP_Post stub for has_shortcode fallback tests.
// ------------------------------------------------------------------

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public string $post_content = '';
    }
}

if (! function_exists('has_shortcode')) {
    function has_shortcode(string $content, string $tag): bool
    {
        return str_contains($content, '[' . $tag);
    }
}

// ------------------------------------------------------------------
// WP_Scripts stub for jQuery Migrate removal test.
// ------------------------------------------------------------------

if (! class_exists('WP_Dependencies')) {
    class WP_Dependencies
    {
        /** @var array<string, object> */
        public array $registered = [];
    }
}

if (! class_exists('WP_Scripts')) {
    class WP_Scripts extends WP_Dependencies {}
}

// ------------------------------------------------------------------
// Load the theme's functions.php.
// Registrations happen at file load time (add_action/add_shortcode).
// ------------------------------------------------------------------

require_once dirname(__DIR__) . '/functions.php';
