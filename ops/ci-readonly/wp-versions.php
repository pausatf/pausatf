<?php
// Prints installed WordPress core and plugin versions as JSON. Runs inside the
// pausatf-wordpress container as www-data, fed on stdin by pausatf-ci-readonly.
// Read-only: no option, transient or file writes. Prod has no outbound access
// to api.wordpress.org, so the workflow compares versions on the runner.
$_SERVER['HTTP_HOST'] = 'www.pausatf.org';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/';
define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugins = [];
foreach (get_plugins() as $file => $data) {
    $plugins[] = [
        'slug' => strpos($file, '/') !== false ? dirname($file) : basename($file, '.php'),
        'name' => $data['Name'],
        'version' => $data['Version'],
        'status' => is_plugin_active($file) ? 'active' : 'inactive',
    ];
}

echo wp_json_encode([
    'core_version' => get_bloginfo('version'),
    'plugins' => $plugins,
]), PHP_EOL;
