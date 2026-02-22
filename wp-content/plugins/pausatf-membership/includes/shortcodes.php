<?php
/**
 * Shortcode callbacks.
 *
 * [pausatf_members]    — private member roster (login required)
 * [pausatf_clubs]      — public club directory
 * [pausatf_scoresheet] — scoresheet submission form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [pausatf_members club="" per_page="25"]
 *
 * Requires the visitor to be logged in — member data contains PII.
 *
 * @param array<string, string>|string $atts
 * @return string
 */
function pausatf_members_shortcode( $atts ): string {
	if ( ! is_user_logged_in() ) {
		return '<p class="pausatf-login-required">' .
			wp_kses_post( sprintf(
				/* translators: %s: login URL */
				__( 'You must be <a href="%s">logged in</a> to view the member roster.', 'pausatf-membership' ),
				esc_url( wp_login_url( get_permalink() ) )
			) ) .
			'</p>';
	}

	$atts = shortcode_atts(
		array(
			'club'     => '',
			'per_page' => '25',
		),
		$atts,
		'pausatf_members'
	);

	$club     = sanitize_text_field( $atts['club'] );
	$per_page = absint( $atts['per_page'] );
	if ( $per_page < 1 || $per_page > 200 ) {
		$per_page = 25;
	}

	$paged = max( 1, absint( get_query_var( 'paged' ) ) );

	// Override club filter from URL param if present (e.g. ?club=LMTC).
	$url_club = sanitize_text_field( $_GET['club'] ?? '' );
	if ( ! empty( $url_club ) ) {
		$club = $url_club;
	}

	$query_args = array(
		'post_type'      => 'pausatf_member',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'orderby'        => 'meta_value',
		'meta_key'       => 'last_name',
		'order'          => 'ASC',
	);

	if ( ! empty( $club ) ) {
		$query_args['meta_query'] = array(
			array(
				'key'     => 'club_code',
				'value'   => $club,
				'compare' => '=',
			),
		);
	}

	$members_query = new WP_Query( $query_args );

	ob_start();
	include PAUSATF_PLUGIN_DIR . 'templates/archive-pausatf_member.php';
	return ob_get_clean();
}

/**
 * [pausatf_clubs state=""]
 *
 * Publicly accessible club directory.
 *
 * @param array<string, string>|string $atts
 * @return string
 */
function pausatf_clubs_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'state' => '',
		),
		$atts,
		'pausatf_clubs'
	);

	$state = sanitize_text_field( $atts['state'] );

	$query_args = array(
		'post_type'      => 'pausatf_club',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	if ( ! empty( $state ) ) {
		$query_args['meta_query'] = array(
			array(
				'key'     => 'club_state',
				'value'   => $state,
				'compare' => '=',
			),
		);
	}

	$clubs_query = new WP_Query( $query_args );

	ob_start();
	include PAUSATF_PLUGIN_DIR . 'templates/archive-pausatf_club.php';
	return ob_get_clean();
}

/**
 * [pausatf_scoresheet]
 *
 * On POST: validate, handle submission, show confirmation or errors.
 * On GET: render the form.
 *
 * @param array<string, string>|string $atts
 * @return string
 */
function pausatf_scoresheet_shortcode( $atts ): string {
	$submission_result = null;
	$submission_errors = array();

	if ( isset( $_POST['pausatf_scoresheet_submit'] ) ) {
		$result = PAUSATF_Score_Handler::handle_submission( $_POST );

		if ( is_wp_error( $result ) ) {
			$submission_errors = $result->get_error_messages();
		} else {
			// Success — show confirmation and bail early.
			return '<div class="pausatf-scoresheet-success">' .
				esc_html__( 'Scoresheet submitted successfully. Thank you!', 'pausatf-membership' ) .
				'</div>';
		}
	}

	// Build club list for the dropdown.
	$clubs_query = new WP_Query( array(
		'post_type'      => 'pausatf_club',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	ob_start();
	include PAUSATF_PLUGIN_DIR . 'templates/shortcode-scoresheet.php';
	return ob_get_clean();
}
