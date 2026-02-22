<?php
/**
 * Purge Cloudflare cache when a post is published or updated.
 *
 * Requires CF_API_TOKEN and CF_ZONE_ID constants defined in wp-config.php.
 * Neither value should be committed to the repository.
 *
 * Example wp-config.php additions (server-side only):
 *   define( 'CF_API_TOKEN', 'your-cache-purge-scoped-token' );
 *   define( 'CF_ZONE_ID',   'your-zone-id' );
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action(
    'save_post',
    static function ( int $post_id, WP_Post $post ): void {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        if ( ! in_array( $post->post_status, [ 'publish', 'future' ], true ) ) {
            return;
        }

        $token   = defined( 'CF_API_TOKEN' ) ? (string) CF_API_TOKEN : '';
        $zone_id = defined( 'CF_ZONE_ID' )   ? (string) CF_ZONE_ID   : '';

        if ( '' === $token || '' === $zone_id ) {
            return;
        }

        $files = array_filter( [ get_permalink( $post_id ), home_url( '/' ) ] );

        $body = wp_json_encode( [ 'files' => array_values( $files ) ] );
        if ( false === $body ) {
            error_log( '[cf-cache-purge] failed to encode purge payload for post ' . $post_id );
            return;
        }

        $response = wp_remote_post(
            "https://api.cloudflare.com/client/v4/zones/{$zone_id}/purge_cache",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => $body,
                'timeout' => 10,
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( '[cf-cache-purge] purge failed for post ' . $post_id . ': ' . $response->get_error_message() );
            return;
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 300 ) {
            error_log( '[cf-cache-purge] purge returned HTTP ' . $status . ' for post ' . $post_id );
            return;
        }

        $cf_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $cf_body['success'] ) && false === $cf_body['success'] ) {
            $errors = wp_json_encode( $cf_body['errors'] ?? [] );
            error_log( '[cf-cache-purge] Cloudflare success:false for post ' . $post_id . ': ' . $errors );
        }
    },
    10,
    2
);
