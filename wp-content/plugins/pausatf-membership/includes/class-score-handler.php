<?php
/**
 * Scoresheet submission handler.
 *
 * Creates and manages the custom table {prefix}pausatf_scores.
 * Replaces the legacy ScoreSheet.php which used deprecated mysql_* calls
 * and had no CSRF protection or rate limiting.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PAUSATF_Score_Handler {

	public const NONCE_ACTION = 'pausatf_scoresheet_nonce';

	// Rate limit: max submissions per IP per rolling hour.
	private const RATE_LIMIT      = 10;
	private const RATE_WINDOW_SEC = 3600;

	public static function install_schema(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'pausatf_scores';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			meet_name varchar(255) NOT NULL,
			meet_date date NOT NULL,
			club_code varchar(32) NOT NULL,
			athlete_name varchar(255) NOT NULL,
			event_name varchar(128) NOT NULL,
			result varchar(64) NOT NULL,
			submitted_by varchar(255) NOT NULL,
			submitted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY club_code (club_code),
			KEY meet_date (meet_date)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Validate, sanitize, and insert a scoresheet submission.
	 *
	 * @param array<string, string> $data Raw POST data.
	 * @return int|WP_Error Inserted row ID on success, WP_Error on failure.
	 */
	public static function handle_submission( array $data ): int|WP_Error {
		// Nonce verification — must be checked before any data processing.
		$nonce = $data['_wpnonce'] ?? '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return new WP_Error( 'bad_nonce', __( 'Security check failed. Please refresh the page and try again.', 'pausatf-membership' ) );
		}

		// Rate limit by IP — 10 submissions per IP per hour via transient.
		$rate_error = self::check_rate_limit();
		if ( is_wp_error( $rate_error ) ) {
			return $rate_error;
		}

		// Sanitize all fields.
		$meet_name    = sanitize_text_field( $data['meet_name'] ?? '' );
		$meet_date    = sanitize_text_field( $data['meet_date'] ?? '' );
		$club_code    = sanitize_text_field( $data['club_code'] ?? '' );
		$athlete_name = sanitize_text_field( $data['athlete_name'] ?? '' );
		$event_name   = sanitize_text_field( $data['event_name'] ?? '' );
		$result       = sanitize_text_field( $data['result'] ?? '' );
		$submitted_by = sanitize_email( $data['submitted_by'] ?? '' );

		// Validate required fields.
		$required = compact( 'meet_name', 'meet_date', 'club_code', 'athlete_name', 'event_name', 'result', 'submitted_by' );
		foreach ( $required as $field => $value ) {
			if ( empty( $value ) ) {
				return new WP_Error(
					'missing_field',
					sprintf( __( 'Required field "%s" is missing.', 'pausatf-membership' ), $field )
				);
			}
		}

		// Validate date format (YYYY-MM-DD).
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $meet_date ) ) {
			return new WP_Error( 'bad_date', __( 'Meet date must be in YYYY-MM-DD format.', 'pausatf-membership' ) );
		}

		// Validate email.
		if ( ! is_email( $submitted_by ) ) {
			return new WP_Error( 'bad_email', __( 'Submitter email address is not valid.', 'pausatf-membership' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pausatf_scores';

		$inserted = $wpdb->insert(
			$table,
			array(
				'meet_name'    => $meet_name,
				'meet_date'    => $meet_date,
				'club_code'    => $club_code,
				'athlete_name' => $athlete_name,
				'event_name'   => $event_name,
				'result'       => $result,
				'submitted_by' => $submitted_by,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $inserted === false ) {
			error_log( sprintf( '[PAUSATF] score insert failed: %s', $wpdb->last_error ) );
			return new WP_Error( 'db_error', __( 'Could not save your submission. Please try again later.', 'pausatf-membership' ) );
		}

		self::increment_rate_count();

		return (int) $wpdb->insert_id;
	}

	/**
	 * Check whether the current IP has exceeded the rate limit.
	 *
	 * @return true|WP_Error True if allowed, WP_Error if rate limited.
	 */
	private static function check_rate_limit(): true|WP_Error {
		$key   = self::rate_transient_key();
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_LIMIT ) {
			return new WP_Error(
				'rate_limited',
				__( 'Too many submissions. Please wait before submitting again.', 'pausatf-membership' )
			);
		}

		return true;
	}

	private static function increment_rate_count(): void {
		$key   = self::rate_transient_key();
		$count = (int) get_transient( $key );

		// set_transient with expiry only when creating; increment after.
		if ( $count === 0 ) {
			set_transient( $key, 1, self::RATE_WINDOW_SEC );
		} else {
			// Transient already set; bump the value without resetting the window.
			// We use $wpdb directly to avoid touching the expiry.
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s",
					'_transient_' . $key
				)
			);
		}
	}

	private static function rate_transient_key(): string {
		// Hash the IP to avoid storing it in plain text in the options table.
		$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
		return 'pausatf_score_rate_' . md5( $ip );
	}
}
