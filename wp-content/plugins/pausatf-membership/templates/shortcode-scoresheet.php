<?php
/**
 * Scoresheet submission form template.
 *
 * Loaded via ob_start() from pausatf_scoresheet_shortcode().
 *
 * Variables available:
 *   $clubs_query       (WP_Query)  — pausatf_club posts for the dropdown
 *   $submission_errors (string[])  — validation errors from a failed POST
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Repopulate fields after a failed submission so the user doesn't retype everything.
$prev = array(
	'meet_name'    => sanitize_text_field( $_POST['meet_name'] ?? '' ),
	'meet_date'    => sanitize_text_field( $_POST['meet_date'] ?? '' ),
	'club_code'    => sanitize_text_field( $_POST['club_code'] ?? '' ),
	'athlete_name' => sanitize_text_field( $_POST['athlete_name'] ?? '' ),
	'event_name'   => sanitize_text_field( $_POST['event_name'] ?? '' ),
	'result'       => sanitize_text_field( $_POST['result'] ?? '' ),
	'submitted_by' => sanitize_email( $_POST['submitted_by'] ?? '' ),
);
?>
<div class="pausatf-scoresheet">

	<?php if ( ! empty( $submission_errors ) ) : ?>
		<div class="pausatf-scoresheet-errors" role="alert">
			<ul>
				<?php foreach ( $submission_errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form class="pausatf-scoresheet-form" method="post">
		<?php wp_nonce_field( PAUSATF_Score_Handler::NONCE_ACTION ); ?>

		<p>
			<label for="pausatf-meet-name"><?php esc_html_e( 'Meet Name', 'pausatf-membership' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="text"
				id="pausatf-meet-name"
				name="meet_name"
				value="<?php echo esc_attr( $prev['meet_name'] ); ?>"
				required
				maxlength="255"
			>
		</p>

		<p>
			<label for="pausatf-meet-date"><?php esc_html_e( 'Meet Date', 'pausatf-membership' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="date"
				id="pausatf-meet-date"
				name="meet_date"
				value="<?php echo esc_attr( $prev['meet_date'] ); ?>"
				required
			>
		</p>

		<p>
			<label for="pausatf-club-code"><?php esc_html_e( 'Club', 'pausatf-membership' ); ?> <span aria-hidden="true">*</span></label>
			<select id="pausatf-club-code" name="club_code" required>
				<option value=""><?php esc_html_e( '— Select a club —', 'pausatf-membership' ); ?></option>
				<?php while ( $clubs_query->have_posts() ) : $clubs_query->the_post(); ?>
					<?php
					$code  = get_post_meta( get_the_ID(), 'club_code', true );
					$label = get_the_title();
					if ( ! empty( $code ) ) {
						$label .= ' (' . $code . ')';
					}
					?>
					<option
						value="<?php echo esc_attr( $code ); ?>"
						<?php selected( $prev['club_code'], $code ); ?>
					><?php echo esc_html( $label ); ?></option>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			</select>
		</p>

		<p>
			<label for="pausatf-athlete-name"><?php esc_html_e( 'Athlete Name', 'pausatf-membership' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="text"
				id="pausatf-athlete-name"
				name="athlete_name"
				value="<?php echo esc_attr( $prev['athlete_name'] ); ?>"
				required
				maxlength="255"
			>
		</p>

		<p>
			<label for="pausatf-event-name"><?php esc_html_e( 'Event', 'pausatf-membership' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="text"
				id="pausatf-event-name"
				name="event_name"
				value="<?php echo esc_attr( $prev['event_name'] ); ?>"
				required
				maxlength="128"
				placeholder="<?php esc_attr_e( 'e.g. 100m, Shot Put', 'pausatf-membership' ); ?>"
			>
		</p>

		<p>
			<label for="pausatf-result"><?php esc_html_e( 'Result', 'pausatf-membership' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="text"
				id="pausatf-result"
				name="result"
				value="<?php echo esc_attr( $prev['result'] ); ?>"
				required
				maxlength="64"
				placeholder="<?php esc_attr_e( 'e.g. 11.23, 14.56m', 'pausatf-membership' ); ?>"
			>
		</p>

		<p>
			<label for="pausatf-submitted-by"><?php esc_html_e( 'Your Email', 'pausatf-membership' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="email"
				id="pausatf-submitted-by"
				name="submitted_by"
				value="<?php echo esc_attr( $prev['submitted_by'] ); ?>"
				required
				maxlength="255"
			>
		</p>

		<p>
			<button type="submit" name="pausatf_scoresheet_submit" value="1">
				<?php esc_html_e( 'Submit Scoresheet', 'pausatf-membership' ); ?>
			</button>
		</p>

		<p class="pausatf-required-note">
			<span aria-hidden="true">*</span> <?php esc_html_e( 'Required fields', 'pausatf-membership' ); ?>
		</p>
	</form>

</div>
