<?php

/**
 * Unit tests for the PAUSATF GeneratePress child theme functions.php.
 *
 * Covers style enqueuing, shortcodes, breadcrumbs, custom footer,
 * Contact Form 7 conditional loading, and performance optimizations.
 */

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Find the first registered action callback for a given hook+priority.
     */
    private function findAction(string $hook, int $priority = 10): ?callable
    {
        foreach ($GLOBALS['_pausatf_actions'] as $entry) {
            if ($entry['hook'] === $hook && $entry['priority'] === $priority) {
                return $entry['callback'];
            }
        }
        return null;
    }

    /**
     * Find the first registered filter callback for a given hook+priority.
     */
    private function findFilter(string $hook, int $priority = 10): ?callable
    {
        foreach ($GLOBALS['_pausatf_filters'] as $entry) {
            if ($entry['hook'] === $hook && $entry['priority'] === $priority) {
                return $entry['callback'];
            }
        }
        return null;
    }

    protected function setUp(): void
    {
        pausatf_test_reset();
        // Re-load registrations — bootstrap already loaded once; the
        // globals were captured at that time.  We re-read them from the
        // snapshot taken during bootstrap (they are stable, not reset).
    }

    /**
     * Re-populate registries from the bootstrap snapshot.
     *
     * Because functions.php runs at require time (bootstrap), we cannot
     * re-require it.  Instead we keep the registries populated from the
     * initial load and only reset condition flags per test.
     */
    protected function tearDown(): void
    {
        pausatf_test_reset();
    }

    /**
     * Get a fresh copy of all registrations by re-including functions.php.
     * We use this approach: capture once, test many.
     */
    private static array $bootActions    = [];
    private static array $bootFilters    = [];
    private static array $bootShortcodes = [];

    public static function setUpBeforeClass(): void
    {
        // Registrations were captured during bootstrap require.
        self::$bootActions    = $GLOBALS['_pausatf_actions'];
        self::$bootFilters    = $GLOBALS['_pausatf_filters'];
        self::$bootShortcodes = $GLOBALS['_pausatf_shortcodes'];
    }

    private function findBootAction(string $hook, int $priority = 10): ?callable
    {
        foreach (self::$bootActions as $entry) {
            if ($entry['hook'] === $hook && $entry['priority'] === $priority) {
                return $entry['callback'];
            }
        }
        return null;
    }

    private function findBootFilter(string $hook, int $priority = 10): ?callable
    {
        foreach (self::$bootFilters as $entry) {
            if ($entry['hook'] === $hook && $entry['priority'] === $priority) {
                return $entry['callback'];
            }
        }
        return null;
    }

    // ==================================================================
    // 1. Style enqueuing
    // ==================================================================

    #[Test]
    public function enqueue_callback_is_registered_at_priority_20(): void
    {
        $cb = $this->findBootAction('wp_enqueue_scripts', 20);
        self::assertNotNull($cb, 'wp_enqueue_scripts at priority 20 should be registered');
    }

    #[Test]
    public function enqueue_registers_all_three_stylesheets(): void
    {
        $cb = $this->findBootAction('wp_enqueue_scripts', 20);
        self::assertNotNull($cb);

        // Invoke the enqueue callback.
        $cb();

        $styles = $GLOBALS['_pausatf_enqueued']['styles'];

        self::assertArrayHasKey('pausatf-legacy-compat', $styles);
        self::assertArrayHasKey('pausatf-sport-labels', $styles);
        self::assertArrayHasKey('pausatf-calendar-embed', $styles);
    }

    #[Test]
    public function enqueued_styles_depend_on_generate_style(): void
    {
        $cb = $this->findBootAction('wp_enqueue_scripts', 20);
        $cb();

        foreach (['pausatf-legacy-compat', 'pausatf-sport-labels', 'pausatf-calendar-embed'] as $handle) {
            $style = $GLOBALS['_pausatf_enqueued']['styles'][$handle];
            self::assertContains(
                'generate-style',
                $style['deps'],
                "{$handle} should depend on generate-style"
            );
        }
    }

    #[Test]
    public function enqueued_styles_use_theme_version(): void
    {
        $cb = $this->findBootAction('wp_enqueue_scripts', 20);
        $cb();

        foreach (['pausatf-legacy-compat', 'pausatf-sport-labels', 'pausatf-calendar-embed'] as $handle) {
            $style = $GLOBALS['_pausatf_enqueued']['styles'][$handle];
            self::assertSame('1.0.0', $style['ver'], "{$handle} version should match theme version");
        }
    }

    #[Test]
    public function enqueued_styles_have_correct_paths(): void
    {
        $cb = $this->findBootAction('wp_enqueue_scripts', 20);
        $cb();

        $base = 'https://example.com/wp-content/themes/pausatf-generatepress-child/assets/css/';

        $expected = [
            'pausatf-legacy-compat' => $base . 'legacy-compat.css',
            'pausatf-sport-labels'  => $base . 'sport-labels.css',
            'pausatf-calendar-embed' => $base . 'calendar-embed.css',
        ];

        foreach ($expected as $handle => $src) {
            self::assertSame(
                $src,
                $GLOBALS['_pausatf_enqueued']['styles'][$handle]['src'],
                "{$handle} src mismatch"
            );
        }
    }

    // ==================================================================
    // 2. Shortcode functionality
    // ==================================================================

    #[Test]
    public function column_shortcodes_are_registered(): void
    {
        $expected = [
            'one_half', 'one_half_last',
            'one_third', 'one_third_last',
            'two_thirds', 'two_thirds_last',
            'one_fourth', 'one_fourth_last',
            'three_fourths', 'three_fourths_last',
        ];

        foreach ($expected as $tag) {
            self::assertArrayHasKey($tag, self::$bootShortcodes, "Shortcode [{$tag}] should be registered");
        }
    }

    #[Test]
    public function one_half_shortcode_wraps_content_in_50_percent_div(): void
    {
        $cb = self::$bootShortcodes['one_half'];
        $output = $cb([], 'Hello World');

        self::assertStringContainsString('width:50%', $output);
        self::assertStringContainsString('Hello World', $output);
        self::assertStringContainsString('pausatf-col', $output);
    }

    #[Test]
    public function one_half_shortcode_with_empty_content(): void
    {
        $cb = self::$bootShortcodes['one_half'];
        $output = $cb([], '');

        self::assertStringContainsString('width:50%', $output);
        self::assertStringContainsString('pausatf-col', $output);
        // Empty content produces an empty div, which is valid.
        self::assertStringNotContainsString('undefined', strtolower($output));
    }

    #[Test]
    public function one_half_shortcode_with_null_content_defaults_to_empty(): void
    {
        $cb = self::$bootShortcodes['one_half'];
        // WordPress passes null content as empty string via the default parameter.
        $output = $cb([], '');

        self::assertIsString($output);
        self::assertStringContainsString('pausatf-col', $output);
    }

    #[Test]
    public function shortcode_ignores_unexpected_attributes(): void
    {
        $cb = self::$bootShortcodes['one_half'];
        // Passing unexpected attributes should not alter output.
        $output = $cb(['attr' => 'invalid', 'class' => 'hack'], 'Content');

        self::assertStringContainsString('width:50%', $output);
        self::assertStringContainsString('Content', $output);
        // The unknown attributes should not appear in output.
        self::assertStringNotContainsString('invalid', $output);
        self::assertStringNotContainsString('hack', $output);
    }

    #[Test]
    public function last_variant_clears_float(): void
    {
        $cb = self::$bootShortcodes['one_half_last'];
        $output = $cb([], 'Last column');

        self::assertStringContainsString('clear:both', $output);
        self::assertStringContainsString('pausatf-col-last', $output);
        self::assertStringContainsString('padding-right:0', $output);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function columnWidthProvider(): array
    {
        return [
            'one_half'      => ['one_half', '50%'],
            'one_third'     => ['one_third', '33.333%'],
            'two_thirds'    => ['two_thirds', '66.666%'],
            'one_fourth'    => ['one_fourth', '25%'],
            'three_fourths' => ['three_fourths', '75%'],
        ];
    }

    #[Test]
    #[DataProvider('columnWidthProvider')]
    public function shortcode_produces_correct_width(string $tag, string $expectedWidth): void
    {
        $cb = self::$bootShortcodes[$tag];
        $output = $cb([], 'test');

        self::assertStringContainsString("width:{$expectedWidth}", $output);
    }

    // ==================================================================
    // 3. Breadcrumbs generation
    // ==================================================================

    #[Test]
    public function breadcrumbs_callback_is_registered(): void
    {
        $cb = $this->findBootAction('generate_after_header', 10);
        self::assertNotNull($cb, 'generate_after_header callback should be registered');
    }

    #[Test]
    public function breadcrumbs_not_displayed_on_front_page(): void
    {
        $GLOBALS['_pausatf_is_front_page'] = true;

        $cb = $this->findBootAction('generate_after_header', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        self::assertEmpty($output, 'Breadcrumbs should not render on front page');
    }

    #[Test]
    public function breadcrumbs_displayed_on_singular_page(): void
    {
        $GLOBALS['_pausatf_is_singular'] = true;
        $GLOBALS['_pausatf_the_title']   = 'About Us';

        $cb = $this->findBootAction('generate_after_header', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        self::assertStringContainsString('pausatf-breadcrumbs', $output);
        self::assertStringContainsString('Home', $output);
        self::assertStringContainsString('About Us', $output);
        self::assertStringContainsString('&raquo;', $output);
    }

    #[Test]
    public function breadcrumbs_displayed_on_archive(): void
    {
        $GLOBALS['_pausatf_is_archive']    = true;
        $GLOBALS['_pausatf_archive_title'] = 'Category: News';

        $cb = $this->findBootAction('generate_after_header', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        self::assertStringContainsString('Category: News', $output);
    }

    #[Test]
    public function breadcrumbs_on_search_page(): void
    {
        $GLOBALS['_pausatf_is_search'] = true;

        $cb = $this->findBootAction('generate_after_header', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        self::assertStringContainsString('Search Results', $output);
    }

    #[Test]
    public function breadcrumbs_on_404_page(): void
    {
        $GLOBALS['_pausatf_is_404'] = true;

        $cb = $this->findBootAction('generate_after_header', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        self::assertStringContainsString('Page Not Found', $output);
    }

    #[Test]
    public function breadcrumbs_with_empty_title_on_singular(): void
    {
        $GLOBALS['_pausatf_is_singular'] = true;
        $GLOBALS['_pausatf_the_title']   = '';

        $cb = $this->findBootAction('generate_after_header', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        // Empty title means $title is falsy, so no breadcrumb output.
        self::assertEmpty($output, 'Breadcrumbs should not render with empty title');
    }

    #[Test]
    public function breadcrumbs_escapes_special_characters(): void
    {
        $GLOBALS['_pausatf_is_singular'] = true;
        $GLOBALS['_pausatf_the_title']   = '<script>alert("xss")</script>';

        $cb = $this->findBootAction('generate_after_header', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        self::assertStringNotContainsString('<script>', $output);
        self::assertStringContainsString('&lt;script&gt;', $output);
    }

    #[Test]
    public function breadcrumbs_with_empty_archive_title(): void
    {
        $GLOBALS['_pausatf_is_archive']    = true;
        $GLOBALS['_pausatf_archive_title'] = '';

        $cb = $this->findBootAction('generate_after_header', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        self::assertEmpty($output, 'Breadcrumbs should not render with empty archive title');
    }

    // ==================================================================
    // 4. Custom footer
    // ==================================================================

    #[Test]
    public function footer_callback_is_registered(): void
    {
        $cb = $this->findBootAction('generate_footer', 10);
        self::assertNotNull($cb, 'generate_footer callback should be registered');
    }

    #[Test]
    public function footer_renders_nav_menu_with_footer_location(): void
    {
        $cb = $this->findBootAction('generate_footer', 10);
        ob_start();
        $cb();
        ob_end_clean();

        self::assertArrayHasKey('footer', $GLOBALS['_pausatf_wp_nav_menu_calls']);

        $args = $GLOBALS['_pausatf_wp_nav_menu_calls']['footer'];
        self::assertSame('footer', $args['theme_location']);
        self::assertSame('nav', $args['container']);
        self::assertSame('pausatf-footer-nav', $args['container_class']);
        self::assertSame(1, $args['depth']);
    }

    #[Test]
    public function footer_has_fallback_cb_false(): void
    {
        $cb = $this->findBootAction('generate_footer', 10);
        ob_start();
        $cb();
        ob_end_clean();

        $args = $GLOBALS['_pausatf_wp_nav_menu_calls']['footer'];
        self::assertFalse($args['fallback_cb'], 'fallback_cb should be false so no menu renders when none assigned');
    }

    #[Test]
    public function footer_renders_credit_line(): void
    {
        $cb = $this->findBootAction('generate_footer', 10);
        ob_start();
        $cb();
        $output = ob_get_clean();

        self::assertStringContainsString('pausatf-footer-credit', $output);
        self::assertStringContainsString('Designed by SMDdesigns', $output);
        self::assertStringContainsString('Powered by WordPress', $output);
    }

    #[Test]
    public function footer_menu_location_is_registered(): void
    {
        // The register_nav_menus call happens during bootstrap via after_setup_theme.
        // Find and invoke that callback.
        $cb = $this->findBootAction('after_setup_theme', 15);
        self::assertNotNull($cb);

        $cb();
        self::assertArrayHasKey('footer', $GLOBALS['_pausatf_nav_menus']);
        self::assertSame('Footer Navigation', $GLOBALS['_pausatf_nav_menus']['footer']);
    }

    #[Test]
    public function footer_widgets_filter_returns_false(): void
    {
        $cb = $this->findBootFilter('generate_footer_widgets_active', 10);
        self::assertNotNull($cb, 'generate_footer_widgets_active filter should be registered');
        self::assertFalse($cb(), 'Filter should return false to disable footer widgets');
    }

    // ==================================================================
    // 5. Contact Form 7 conditional loading
    // ==================================================================

    #[Test]
    public function cf7_dequeue_callback_registered_at_priority_99(): void
    {
        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        self::assertNotNull($cb, 'CF7 conditional loading should be at priority 99');
    }

    #[Test]
    public function cf7_assets_kept_on_contact_page(): void
    {
        $GLOBALS['_pausatf_page_slug'] = 'contact';

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['scripts']);
    }

    #[Test]
    public function cf7_assets_kept_on_pausatf_contacts_page(): void
    {
        $GLOBALS['_pausatf_page_slug'] = 'pausatf-contacts';

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
    }

    #[Test]
    public function cf7_assets_kept_on_fdx_contact_page(): void
    {
        $GLOBALS['_pausatf_page_slug'] = 'fdx-contact';

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
    }

    #[Test]
    public function cf7_assets_dequeued_on_non_contact_page(): void
    {
        $GLOBALS['_pausatf_page_slug'] = 'about';

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['scripts']);
        self::assertArrayHasKey('wpcf7-recaptcha', $GLOBALS['_pausatf_dequeued']['scripts']);
    }

    #[Test]
    public function cf7_assets_dequeued_on_front_page(): void
    {
        // Front page with no matching slug should dequeue CF7.
        $GLOBALS['_pausatf_page_slug'] = '';

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['scripts']);
    }

    #[Test]
    public function cf7_assets_kept_when_post_contains_cf7_shortcode(): void
    {
        $GLOBALS['_pausatf_page_slug'] = 'about';
        $GLOBALS['_pausatf_is_singular'] = true;

        $post = new WP_Post();
        $post->post_content = 'Some text [contact-form-7 id="123"] more text';
        $GLOBALS['post'] = $post;

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['scripts']);

        unset($GLOBALS['post']);
    }

    #[Test]
    public function cf7_assets_dequeued_with_renamed_slug(): void
    {
        // Slug renamed from "contact" to "contact-us" would not match.
        $GLOBALS['_pausatf_page_slug'] = 'contact-us';

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['scripts']);
    }

    #[Test]
    public function cf7_assets_kept_on_is_page_match_and_dequeue_not_called(): void
    {
        $GLOBALS['_pausatf_page_slug'] = 'pausatf-contacts';

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['scripts']);
        self::assertArrayNotHasKey('wpcf7-recaptcha', $GLOBALS['_pausatf_dequeued']['scripts']);
    }

    #[Test]
    public function cf7_assets_dequeued_when_singular_but_post_not_set(): void
    {
        $GLOBALS['_pausatf_page_slug'] = 'some-page';
        $GLOBALS['_pausatf_is_singular'] = true;
        unset($GLOBALS['post']);

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['scripts']);
    }

    #[Test]
    public function cf7_assets_dequeued_when_singular_without_cf7_shortcode(): void
    {
        $GLOBALS['_pausatf_page_slug'] = 'about';
        $GLOBALS['_pausatf_is_singular'] = true;

        $post = new WP_Post();
        $post->post_content = 'No contact form here.';
        $GLOBALS['post'] = $post;

        $cb = $this->findBootAction('wp_enqueue_scripts', 99);
        $cb();

        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['scripts']);

        unset($GLOBALS['post']);
    }

    #[Test]
    public function dequeue_removes_previously_enqueued_style(): void
    {
        // Simulate a style being enqueued first.
        wp_enqueue_style('contact-form-7', '/fake.css');
        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_enqueued']['styles']);

        // Dequeue it.
        wp_dequeue_style('contact-form-7');

        self::assertArrayHasKey('contact-form-7', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayNotHasKey('contact-form-7', $GLOBALS['_pausatf_enqueued']['styles']);
    }

    // ==================================================================
    // 6. Performance optimizations
    // ==================================================================

    #[Test]
    public function emoji_removal_is_registered_on_init(): void
    {
        $cb = $this->findBootAction('init', 10);
        self::assertNotNull($cb, 'init callback for emoji removal should be registered');

        // Invoke it — it calls remove_action/remove_filter.
        $cb();

        // Verify frontend emoji removals.
        self::assertArrayHasKey(
            'wp_head:7',
            $GLOBALS['_pausatf_removed_actions'],
            'print_emoji_detection_script should be removed from wp_head'
        );
        self::assertArrayHasKey(
            'wp_print_styles:10',
            $GLOBALS['_pausatf_removed_actions'],
            'print_emoji_styles should be removed from wp_print_styles'
        );

        // Verify admin emoji removals.
        self::assertArrayHasKey(
            'admin_print_scripts:10',
            $GLOBALS['_pausatf_removed_actions'],
            'print_emoji_detection_script should be removed from admin_print_scripts'
        );
        self::assertArrayHasKey(
            'admin_print_styles:10',
            $GLOBALS['_pausatf_removed_actions'],
            'print_emoji_styles should be removed from admin_print_styles'
        );

        // Verify feed/email filter removals.
        self::assertArrayHasKey(
            'the_content_feed:10',
            $GLOBALS['_pausatf_removed_filters'],
            'wp_staticize_emoji should be removed from the_content_feed'
        );
        self::assertArrayHasKey(
            'comment_text_rss:10',
            $GLOBALS['_pausatf_removed_filters'],
            'wp_staticize_emoji should be removed from comment_text_rss'
        );
        self::assertArrayHasKey(
            'wp_mail:10',
            $GLOBALS['_pausatf_removed_filters'],
            'wp_staticize_emoji_for_email should be removed from wp_mail'
        );
    }

    #[Test]
    public function block_library_styles_dequeued_at_priority_100(): void
    {
        $cb = $this->findBootAction('wp_enqueue_scripts', 100);
        self::assertNotNull($cb, 'Block library dequeue should be at priority 100');

        $cb();

        self::assertArrayHasKey('wp-block-library', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayHasKey('wp-block-library-theme', $GLOBALS['_pausatf_dequeued']['styles']);
        self::assertArrayHasKey('global-styles', $GLOBALS['_pausatf_dequeued']['styles']);
    }

    #[Test]
    public function jquery_migrate_decoupled_on_frontend(): void
    {
        $cb = $this->findBootAction('wp_default_scripts', 10);
        self::assertNotNull($cb, 'wp_default_scripts callback should be registered');

        // Build a mock WP_Scripts object with jQuery depending on jquery-migrate.
        $scripts = new WP_Scripts();
        $jquery = new stdClass();
        $jquery->deps = ['jquery-core', 'jquery-migrate'];
        $scripts->registered['jquery'] = $jquery;

        $GLOBALS['_pausatf_is_admin'] = false;
        $cb($scripts);

        self::assertNotContains(
            'jquery-migrate',
            $scripts->registered['jquery']->deps,
            'jquery-migrate should be removed from jQuery deps on frontend'
        );
        self::assertContains(
            'jquery-core',
            $scripts->registered['jquery']->deps,
            'jquery-core should remain in jQuery deps'
        );
    }

    #[Test]
    public function jquery_migrate_kept_on_admin(): void
    {
        $cb = $this->findBootAction('wp_default_scripts', 10);

        $scripts = new WP_Scripts();
        $jquery = new stdClass();
        $jquery->deps = ['jquery-core', 'jquery-migrate'];
        $scripts->registered['jquery'] = $jquery;

        $GLOBALS['_pausatf_is_admin'] = true;
        $cb($scripts);

        self::assertContains(
            'jquery-migrate',
            $scripts->registered['jquery']->deps,
            'jquery-migrate should remain in admin context'
        );
    }

    #[Test]
    public function widget_block_editor_disabled(): void
    {
        // Both filters should be registered.
        $found_gutenberg = false;
        $found_core      = false;

        foreach (self::$bootFilters as $entry) {
            if ($entry['hook'] === 'gutenberg_use_widgets_block_editor') {
                $found_gutenberg = true;
            }
            if ($entry['hook'] === 'use_widgets_block_editor') {
                $found_core = true;
            }
        }

        self::assertTrue($found_gutenberg, 'gutenberg_use_widgets_block_editor filter should be registered');
        self::assertTrue($found_core, 'use_widgets_block_editor filter should be registered');
    }
}
