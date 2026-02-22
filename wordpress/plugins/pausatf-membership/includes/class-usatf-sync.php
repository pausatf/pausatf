<?php
/**
 * USATF Member Sync — WP-Cron stub
 *
 * Replaces legacy fetch-members.php (Teeters, 2007).
 *
 * Scheduled daily via WP-Cron (hook: pausatf_usatf_sync).
 * Currently implemented as a CSV-based import stub pending a Sport80
 * widget API decision from USATF national.
 *
 * To trigger manually from WP-CLI:
 *   wp eval 'PAUSATF_USATF_Sync::run();'
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the daily USATF member data synchronization.
 */
class PAUSATF_USATF_Sync {

    /**
     * WordPress option key that stores the last successful sync timestamp.
     */
    private const OPTION_LAST_SYNC = 'pausatf_usatf_sync_last_run';

    /**
     * WordPress option key for the remote CSV URL (set in WP admin).
     *
     * The URL must return a UTF-8 CSV with at minimum the columns:
     *   first_name, last_name, club_name
     *
     * Configure via:
     *   wp option update pausatf_usatf_sync_csv_url 'https://...'
     */
    private const OPTION_CSV_URL = 'pausatf_usatf_sync_csv_url';

    /**
     * Register the cron hook.
     *
     * Called from the main plugin file (not hooked to 'init' directly — the
     * main file adds the action after including this class).
     */
    public static function register(): void {
        add_action( 'pausatf_usatf_sync', [ self::class, 'run' ] );
    }

    /**
     * Execute the sync.
     *
     * @return bool True on success, false on any failure.
     */
    public static function run(): bool {
        $csv_url = (string) get_option( self::OPTION_CSV_URL, '' );

        if ( '' === $csv_url ) {
            error_log( '[pausatf-membership] USATF sync skipped: no CSV URL configured (option: ' . self::OPTION_CSV_URL . ')' );
            return false;
        }

        $response = wp_remote_get(
            $csv_url,
            [
                'timeout'    => 30,
                'user-agent' => 'PAUSATF-Membership/' . PAUSATF_MEMBERSHIP_VERSION . '; WordPress/' . get_bloginfo( 'version' ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( '[pausatf-membership] USATF sync HTTP error: ' . $response->get_error_message() );
            return false;
        }

        $http_code = (int) wp_remote_retrieve_response_code( $response );
        if ( $http_code < 200 || $http_code >= 300 ) {
            error_log( '[pausatf-membership] USATF sync unexpected HTTP status: ' . $http_code );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $rows = self::parse_csv( $body );

        if ( empty( $rows ) ) {
            error_log( '[pausatf-membership] USATF sync: CSV body was empty or unparseable.' );
            return false;
        }

        $imported = 0;
        foreach ( $rows as $row ) {
            if ( self::upsert_member( $row ) ) {
                ++$imported;
            }
        }

        update_option( self::OPTION_LAST_SYNC, current_time( 'mysql' ) );
        error_log( "[pausatf-membership] USATF sync complete: {$imported} members upserted." );

        return true;
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Parse a CSV string into an associative array keyed by the header row.
     *
     * @param string $csv Raw CSV text.
     * @return array<int, array<string,string>>
     */
    private static function parse_csv( string $csv ): array {
        $csv   = ltrim( $csv, "\xEF\xBB\xBF" ); // strip UTF-8 BOM if present
        $lines = preg_split( '/\r\n|\r|\n/', trim( $csv ) );

        if ( ! is_array( $lines ) || count( $lines ) < 2 ) {
            return [];
        }

        $headers = str_getcsv( array_shift( $lines ) );
        $headers = array_map( 'trim', $headers );

        $rows = [];
        foreach ( $lines as $line ) {
            if ( '' === trim( $line ) ) {
                continue;
            }
            $values = str_getcsv( $line );
            if ( count( $values ) !== count( $headers ) ) {
                continue;
            }
            $rows[] = array_combine( $headers, $values );
        }

        return $rows;
    }

    /**
     * Create or update a pausatf_member post from a CSV row.
     *
     * Looks up an existing member by USATF member ID (meta key _pausatf_usatf_id)
     * when the CSV row contains a `usatf_id` column; otherwise falls back to
     * name + club matching.
     *
     * @param array<string,string> $row One CSV data row.
     * @return bool True if the post was inserted or updated.
     */
    private static function upsert_member( array $row ): bool {
        $first = sanitize_text_field( $row['first_name'] ?? '' );
        $last  = sanitize_text_field( $row['last_name'] ?? '' );
        $club  = sanitize_text_field( $row['club_name'] ?? '' );

        if ( '' === $first && '' === $last ) {
            return false;
        }

        $full_name = trim( "{$first} {$last}" );

        // Attempt to find an existing member post by USATF ID.
        $usatf_id  = sanitize_text_field( $row['usatf_id'] ?? '' );
        $post_id   = 0;

        if ( '' !== $usatf_id ) {
            $existing = get_posts( [
                'post_type'   => PAUSATF_CPT_Member::POST_TYPE,
                'post_status' => [ 'publish', 'draft' ],
                'meta_key'    => '_pausatf_usatf_id',   // phpcs:ignore WordPress.DB.SlowDBQuery
                'meta_value'  => $usatf_id,             // phpcs:ignore WordPress.DB.SlowDBQuery
                'numberposts' => 1,
                'fields'      => 'ids',
            ] );
            $post_id = ! empty( $existing ) ? (int) $existing[0] : 0;
        }

        $post_data = [
            'post_type'   => PAUSATF_CPT_Member::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $full_name,
        ];

        if ( $post_id > 0 ) {
            $post_data['ID'] = $post_id;
            $result = wp_update_post( $post_data, true );
        } else {
            $result = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $result ) ) {
            error_log( '[pausatf-membership] upsert_member failed for "' . $full_name . '": ' . $result->get_error_message() );
            return false;
        }

        $saved_id = (int) $result;

        // Store meta fields.
        if ( '' !== $usatf_id ) {
            update_post_meta( $saved_id, '_pausatf_usatf_id', $usatf_id );
        }
        if ( '' !== $club ) {
            update_post_meta( $saved_id, '_pausatf_club_name', $club );
        }

        return true;
    }
}

// Hook the cron action.
PAUSATF_USATF_Sync::register();
