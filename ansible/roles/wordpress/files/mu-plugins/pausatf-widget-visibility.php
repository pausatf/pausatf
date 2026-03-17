<?php
/**
 * Plugin Name: PAUSATF Widget Visibility & Classic Mode
 * Description: Deterministic widget visibility with code-defined overrides and editor-managed fallback. Replaces widget-logic plugin.
 * Version: 1.0.0
 * Author: PAUSATF Dev Team
 */

declare(strict_types=1);

namespace Pausatf\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

// ---------------------------------------------------------------------------
// 1. Restore Classic Widget Interface (disable block widget editor)
// ---------------------------------------------------------------------------
add_filter('use_widgets_block_editor', '__return_false', 10, 0);

// ---------------------------------------------------------------------------
// 2. Code-Defined Visibility Rules (highest priority, fail-closed)
// ---------------------------------------------------------------------------

/**
 * Return the code-defined visibility rules map.
 * Widget IDs mapped to callables returning bool.
 * These override any editor-stored widget_logic values.
 *
 * @return array<string, callable(): bool>
 */
function get_code_rules(): array {
    return [
        // Widget-logic's parser fails for widget-61 due to lifecycle race condition.
        // Override with code-defined rule using page IDs.
        'custom_html-61' => fn(): bool => is_page([70819, 71502, 71472, 70939, 73880]),
    ];
}

// ---------------------------------------------------------------------------
// 3. Editor-Managed Visibility (widget_logic key in widget options)
// ---------------------------------------------------------------------------

/** Allowlisted WordPress conditional functions for eval. */
const ALLOWED_FUNCTIONS = [
    'is_home', 'is_front_page', 'is_page', 'is_single', 'is_singular',
    'is_archive', 'is_category', 'is_tag', 'is_tax', 'is_author',
    'is_search', 'is_404', 'is_attachment', 'is_feed',
    'is_post_type_archive', 'is_date', 'is_year', 'is_month', 'is_day',
    'is_user_logged_in', 'current_user_can', 'in_category', 'has_tag',
    'is_page_template', 'is_paged', 'wp_is_mobile', 'is_preview',
    'is_sticky', 'has_post_thumbnail', 'is_active_sidebar',
];

/**
 * Read widget_logic from a widget's stored options.
 */
function get_widget_logic(string $widget_id): string {
    if (!preg_match('/^(.+)-(\d+)$/', $widget_id, $m)) {
        return '';
    }
    $opts = get_option('widget_' . $m[1]);
    if (!is_array($opts) || empty($opts[(int) $m[2]])) {
        return '';
    }
    return trim((string) ($opts[(int) $m[2]]['widget_logic'] ?? ''));
}

/**
 * Evaluate a widget_logic expression safely.
 *
 * @param string $logic PHP expression using allowlisted conditionals.
 * @return bool True = show widget. Returns true on empty logic or error (fail-open for editor rules).
 */
function eval_logic(string $logic): bool {
    if ($logic === '') {
        return true;
    }

    // Reject any non-whitelisted function calls
    $test = preg_replace('/\b(' . implode('|', ALLOWED_FUNCTIONS) . ')\s*\(/', '_OK_(', $logic);
    if (preg_match('/[a-zA-Z_]\w*\s*\(/', $test)) {
        error_log(sprintf('[PAUSATF Widgets] Blocked non-whitelisted function in logic: %s', $logic));
        return true; // fail-open for editor-managed rules
    }

    try {
        // Prepend global namespace to all allowed function calls so eval works inside this namespace
        $pattern = '/\b(' . implode('|', ALLOWED_FUNCTIONS) . ')\s*\(/';
        $namespaced = preg_replace_callback($pattern, function ($m) {
            return '\\' . $m[1] . '(';
        }, $logic);
        // phpcs:ignore Squiz.PHP.Eval.Discouraged -- controlled eval with strict allowlist
        $result = @eval('return (' . $namespaced . ');');
        return (bool) $result;
    } catch (\Throwable $e) {
        error_log(sprintf('[PAUSATF Widgets] Eval error for logic "%s": %s (namespaced: %s)', $logic, $e->getMessage(), $namespaced));
        return false; // fail-closed: hide widget on error
    }
}

// ---------------------------------------------------------------------------
// 4. Main Filter: Apply visibility rules to sidebars_widgets
// ---------------------------------------------------------------------------

/**
 * Filter sidebars_widgets to enforce visibility.
 * Priority: code-defined rules (fail-closed) > editor widget_logic (fail-open) > show all.
 *
 * @param array<string, array<int, string>> $sidebars_widgets
 * @return array<string, array<int, string>>
 */
// enforce_visibility removed: replaced by filter_widget_display above

// Use widget_display_callback for code-defined overrides only.
// Widget-logic plugin handles all editor-managed rules.
add_filter('widget_display_callback', __NAMESPACE__ . '\filter_widget_display', 10, 3);

function filter_widget_display($instance, $widget, $args) {
    if (is_admin()) {
        return $instance;
    }

    $widget_id = $args['widget_id'] ?? '';
    $code_rules = get_code_rules();

    // Only apply code-defined rules; let widget-logic handle the rest
    if (array_key_exists($widget_id, $code_rules)) {
        try {
            return $code_rules[$widget_id]() ? $instance : false;
        } catch (\Throwable $e) {
            error_log(sprintf('[PAUSATF Widgets] Code rule exception for %s: %s', $widget_id, $e->getMessage()));
            return false;
        }
    }

    return $instance;
}

// ---------------------------------------------------------------------------
// 5. Admin UI: Widget Logic field on classic widget forms
// ---------------------------------------------------------------------------

/**
 * Add widget_logic textarea to widget admin form.
 */
function widget_logic_form($widget, $return, $instance): void {
    $logic = $instance['widget_logic'] ?? '';
    ?>
    <p>
        <label for="<?php echo esc_attr($widget->get_field_id('widget_logic')); ?>">
            <?php esc_html_e('Show widget when (conditional tag):'); ?>
        </label>
        <textarea class="widefat"
                  id="<?php echo esc_attr($widget->get_field_id('widget_logic')); ?>"
                  name="<?php echo esc_attr($widget->get_field_name('widget_logic')); ?>"
                  rows="2"
                  placeholder="e.g. is_page('youth') || is_page('cross-country')"
        ><?php echo esc_textarea($logic); ?></textarea>
        <small><?php esc_html_e('Leave empty to show on all pages. Uses WP conditional tags.'); ?></small>
    </p>
    <?php
}

add_action('in_widget_form', __NAMESPACE__ . '\widget_logic_form', 10, 3);

/**
 * Save widget_logic on widget update.
 */
function widget_logic_save($instance, $new_instance, $old_instance, $widget) {
    $instance['widget_logic'] = sanitize_text_field($new_instance['widget_logic'] ?? '');
    return $instance;
}

add_filter('widget_update_callback', __NAMESPACE__ . '\widget_logic_save', 10, 4);
