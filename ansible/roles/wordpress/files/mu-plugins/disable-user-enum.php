<?php
/**
 * Disable REST API user enumeration to prevent username harvesting.
 *
 * @package PAUSATF_WordPress
 */

add_filter('rest_endpoints', function($endpoints) {
    unset($endpoints['/wp/v2/users']);
    unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    return $endpoints;
});
