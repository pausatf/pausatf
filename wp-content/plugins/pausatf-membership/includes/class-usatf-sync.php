<?php
/**
 * WP-Cron daily sync for USATF membership data.
 *
 * Sport80 (the USATF membership platform) has no public API available to
 * state-level associations. This sync expects a CSV file exported manually
 * from Sport80 and placed at the path defined by PAUSATF_SYNC_CSV_PATH
 * (set in wp-config.php or as an environment variable).
 *
 * CSV column expectations (header row required):
 *   member_id, first_name, last_name, club_code, membership_type,
 *   membership_expiry (YYYY-MM-DD), usatf_id
 *
 * To trigger a manual sync from WP-CLI:
 *   wp eval 'PAUSATF_USATF_Sync::sync();'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PAUSATF_USATF_Sync {

	private const CRON_HOOK = 'pausatf_daily_sync';

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Schedule for 03:00 UTC to avoid peak traffic.
			wp_schedule_event( strtotime( 'tomorrow 03:00 UTC' ), 'daily', self::CRON_HOOK );
		}
		add_action( self::CRON_HOOK, array( __CLASS__, 'sync' ) );
	}

	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	public static function sync(): void {
		$csv_path = PAUSATF_SYNC_CSV_PATH;

		if ( empty( $csv_path ) ) {
			error_log( '[PAUSATF] sync: PAUSATF_SYNC_CSV_PATH is not set — skipping.' );
			return;
		}

		if ( ! is_readable( $csv_path ) ) {
			error_log( sprintf( '[PAUSATF] sync: CSV file not readable at %s — skipping.', $csv_path ) );
			return;
		}

		$start   = microtime( true );
		$updated = self::import_csv( $csv_path );
		$elapsed = round( microtime( true ) - $start, 2 );

		error_log( sprintf( '[PAUSATF] sync: imported %d records in %ss from %s', $updated, $elapsed, $csv_path ) );
	}

	/**
	 * Read the CSV at $filepath, upsert pausatf_member posts, return record count.
	 *
	 * Upsert logic: match on member_id meta. If a post with that member_id
	 * already exists, update its meta. Otherwise insert a new post.
	 * The post title is set to "Last, First" for admin usability.
	 */
	public static function import_csv( string $filepath ): int {
		global $wpdb;

		$handle = fopen( $filepath, 'r' );
		if ( $handle === false ) {
			error_log( sprintf( '[PAUSATF] import_csv: fopen failed for %s', $filepath ) );
			return 0;
		}

		// Consume header row and map column names to indices.
		$header = fgetcsv( $handle );
		if ( $header === false ) {
			fclose( $handle );
			error_log( '[PAUSATF] import_csv: empty or unreadable CSV header.' );
			return 0;
		}

		$cols = array_flip( array_map( 'trim', $header ) );

		$required = array( 'member_id', 'first_name', 'last_name', 'club_code', 'membership_type', 'membership_expiry' );
		foreach ( $required as $col ) {
			if ( ! isset( $cols[ $col ] ) ) {
				fclose( $handle );
				error_log( sprintf( '[PAUSATF] import_csv: missing required column "%s".', $col ) );
				return 0;
			}
		}

		$count = 0;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$member_id = sanitize_text_field( trim( $row[ $cols['member_id'] ] ?? '' ) );
			if ( empty( $member_id ) ) {
				continue;
			}

			$first_name  = sanitize_text_field( trim( $row[ $cols['first_name'] ] ?? '' ) );
			$last_name   = sanitize_text_field( trim( $row[ $cols['last_name'] ] ?? '' ) );
			$club_code   = sanitize_text_field( trim( $row[ $cols['club_code'] ] ?? '' ) );
			$mem_type    = sanitize_text_field( trim( $row[ $cols['membership_type'] ] ?? '' ) );
			$mem_expiry  = sanitize_text_field( trim( $row[ $cols['membership_expiry'] ] ?? '' ) );
			$usatf_id    = sanitize_text_field( trim( $row[ $cols['usatf_id'] ?? -1 ] ?? '' ) );

			// Validate expiry is a plausible ISO 8601 date — reject obviously bad data.
			if ( ! empty( $mem_expiry ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $mem_expiry ) ) {
				error_log( sprintf( '[PAUSATF] import_csv: skipping member_id %s — bad expiry format "%s".', $member_id, $mem_expiry ) );
				continue;
			}

			// Look up existing post by member_id meta.
			$existing_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'member_id' AND meta_value = %s LIMIT 1",
					$member_id
				)
			);

			$post_title = trim( $last_name . ', ' . $first_name );

			if ( $existing_id > 0 ) {
				wp_update_post( array(
					'ID'         => $existing_id,
					'post_title' => $post_title,
				) );
				$post_id = $existing_id;
			} else {
				$post_id = wp_insert_post( array(
					'post_type'   => 'pausatf_member',
					'post_title'  => $post_title,
					'post_status' => 'publish',
				) );

				if ( is_wp_error( $post_id ) ) {
					error_log( sprintf( '[PAUSATF] import_csv: wp_insert_post failed for member_id %s — %s', $member_id, $post_id->get_error_message() ) );
					continue;
				}
			}

			update_post_meta( $post_id, 'member_id',         $member_id );
			update_post_meta( $post_id, 'first_name',        $first_name );
			update_post_meta( $post_id, 'last_name',         $last_name );
			update_post_meta( $post_id, 'club_code',         $club_code );
			update_post_meta( $post_id, 'membership_type',   $mem_type );
			update_post_meta( $post_id, 'membership_expiry', $mem_expiry );
			update_post_meta( $post_id, 'usatf_id',          $usatf_id );

			$count++;
		}

		fclose( $handle );
		return $count;
	}
}

add_action( 'pausatf_daily_sync', array( 'PAUSATF_USATF_Sync', 'sync' ) );
