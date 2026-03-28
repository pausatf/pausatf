<?php
/*
 * Plugin Name: PAUSATF Core
 * Description: Widget visibility override for pausatf.org
 * Version: 1.2.0
 * Requires PHP: 8.1
 * Author: Thomas Vincent
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('widget_display_callback', function ($instance, $widget, $args) {
    if (is_admin()) {
        return $instance;
    }

    $id = $args['widget_id'] ?? '';

    // Map widget IDs to page IDs or slugs where they should appear
    $overrides = [
        'custom_html-61' => [70819, 71502, 71472, 70939, 73880], // Reno Indoor
        'custom_html-37' => ['officials', 'officials-live', 591, 66874], // Officials Directory
        'custom_html-47' => ['officials', 'officials-live', 591, 66874], // Officials Info Links
        'custom_html-56' => ['officials', 'officials-live', 591, 66874], // Officials Tributes
    ];

    if (isset($overrides[$id])) {
        $allowed = $overrides[$id];
        foreach ($allowed as $page) {
            if (is_page($page)) {
                return $instance;
            }
        }
        return false;
    }

    return $instance;
}, 10, 3);
