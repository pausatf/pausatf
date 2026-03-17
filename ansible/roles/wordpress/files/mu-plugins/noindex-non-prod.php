<?php
/**
 * Prevent search engines from indexing non-production environments.
 * Only active when WP_ENVIRONMENT_TYPE is not 'production'.
 */
if (wp_get_environment_type() !== 'production') {
    add_action('wp_head', function() {
        echo '<meta name="robots" content="noindex, nofollow, noarchive">' . PHP_EOL;
    });
    add_filter('wp_robots', function($robots) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
        return $robots;
    });
}
