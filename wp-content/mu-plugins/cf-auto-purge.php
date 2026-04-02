<?php
/**
 * Plugin Name: Cloudflare Auto-Purge on Save
 * Description: Purges Cloudflare + Boost page cache when content is updated.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'save_post', function ( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( $post->post_status !== 'publish' ) {
        return;
    }

    // Clear Boost page cache for this URL
    $url = get_permalink( $post_id );
    if ( ! empty( $url ) ) {
        $cache_dir = WP_CONTENT_DIR . '/boost-cache/cache/';
        $path      = wp_parse_url( $url, PHP_URL_PATH );
        if ( $path && is_dir( $cache_dir . ltrim( $path, '/' ) ) ) {
            array_map( 'unlink', glob( $cache_dir . ltrim( $path, '/' ) . '*.html' ) );
        }
        // Also clear home page cache
        array_map( 'unlink', glob( $cache_dir . '*.html' ) );
    }

    // Purge Cloudflare
    $cf_key = get_option( 'cloudflare_api_key' );
    $zone   = get_option( 'cloudflare_zone_id' );

    if ( empty( $zone ) ) {
        $domain = get_option( 'cloudflare_zone_name', wp_parse_url( home_url(), PHP_URL_HOST ) );
        $resp   = wp_remote_get( "https://api.cloudflare.com/client/v4/zones?name={$domain}", [
            'headers' => [ 'Authorization' => "Bearer {$cf_key}", 'Content-Type' => 'application/json' ],
        ] );
        if ( ! is_wp_error( $resp ) ) {
            $body = json_decode( wp_remote_retrieve_body( $resp ), true );
            if ( ! empty( $body['result'][0]['id'] ) ) {
                $zone = $body['result'][0]['id'];
                update_option( 'cloudflare_zone_id', $zone );
            }
        }
    }

    if ( empty( $cf_key ) || empty( $zone ) ) {
        return;
    }

    if ( empty( $url ) ) {
        return;
    }

    // Purge the post URL and the home page
    $files = [ $url ];
    $home  = home_url( '/' );
    if ( $url !== $home ) {
        $files[] = $home;
    }

    wp_remote_post( "https://api.cloudflare.com/client/v4/zones/{$zone}/purge_cache", [
        'headers' => [ 'Authorization' => "Bearer {$cf_key}", 'Content-Type' => 'application/json' ],
        'body'    => wp_json_encode( [ 'files' => $files ] ),
    ] );
}, 99, 2 );
