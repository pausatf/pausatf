<?php
/**
 * Score submission handler
 *
 * Replaces legacy ScoreSheet.php (Teeters, 2007–2015).
 * Shortcode: [pausatf_scoresheet]
 *
 * Submissions are saved to the {prefix}pausatf_scores custom table created
 * during plugin activation (see pausatf_membership_activate()).
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders and processes the score-submission form.
 */
class PAUSATF_Score_Handler {

    /**
     * Nonce action used to verify form submissions.
     */
    private const NONCE_ACTION = 'pausatf_scoresheet_submit';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'pausatf_scoresheet_nonce';

    /**
     * Register the [pausatf_scoresheet] shortcode.
     *
     * Hooked to 'init'.
     */
    public static function register_shortcode(): void {
        add_shortcode( 'pausatf_scoresheet', [ self::class, 'render_shortcode' ] );
    }

    /**
     * Render [pausatf_scoresheet] shortcode.
     *
     * The form is only displayed to logged-in users.
     *
     * @param array<string,string>|string $atts Shortcode attributes (unused).
     * @return string HTML output.
     */
    public static function render_shortcode( $atts ): string {
        if ( ! is_user_logged_in() ) {
            return sprintf(
                '<p class="pausatf-scoresheet--login-required">%s <a href="%s">%s</a></p>',
                esc_html__( 'You must be logged in to submit a score sheet.', 'pausatf-membership' ),
                esc_url( wp_login_url( get_permalink() ) ),
                esc_html__( 'Log in', 'pausatf-membership' )
            );
        }

        $message = '';

        // Handle form submission.
        if (
            isset( $_POST[ self::NONCE_FIELD ] ) &&
            wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
        ) {
            $result  = self::handle_submission( $_POST );
            $message = $result instanceof WP_Error
                ? '<p class="pausatf-scoresheet__error">' . esc_html( $result->get_error_message() ) . '</p>'
                : '<p class="pausatf-scoresheet__success">' . esc_html__( 'Score sheet submitted successfully.', 'pausatf-membership' ) . '</p>';
        }

        ob_start();
        echo wp_kses_post( $message );
        self::render_form();

        return (string) ob_get_clean();
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Render the score-submission form.
     */
    private static function render_form(): void {
        // Build club <select> options from published pausatf_club posts.
        $clubs = get_posts( [
            'post_type'      => PAUSATF_CPT_Club::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ] );
        ?>
        <form class="pausatf-scoresheet" method="post">
            <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

            <p>
                <label for="pausatf_meet_name"><?php esc_html_e( 'Meet Name', 'pausatf-membership' ); ?></label>
                <input type="text" id="pausatf_meet_name" name="pausatf_meet_name" required maxlength="200">
            </p>

            <p>
                <label for="pausatf_meet_date"><?php esc_html_e( 'Meet Date', 'pausatf-membership' ); ?></label>
                <input type="date" id="pausatf_meet_date" name="pausatf_meet_date" required>
            </p>

            <p>
                <label for="pausatf_club_id"><?php esc_html_e( 'Club', 'pausatf-membership' ); ?></label>
                <select id="pausatf_club_id" name="pausatf_club_id" required>
                    <option value=""><?php esc_html_e( '— Select a club —', 'pausatf-membership' ); ?></option>
                    <?php foreach ( $clubs as $club ) : ?>
                        <option value="<?php echo esc_attr( (string) $club->ID ); ?>">
                            <?php echo esc_html( $club->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="pausatf_athlete"><?php esc_html_e( 'Athlete Name', 'pausatf-membership' ); ?></label>
                <input type="text" id="pausatf_athlete" name="pausatf_athlete" required maxlength="200">
            </p>

            <p>
                <label for="pausatf_event_name"><?php esc_html_e( 'Event', 'pausatf-membership' ); ?></label>
                <input type="text" id="pausatf_event_name" name="pausatf_event_name" required maxlength="100">
            </p>

            <p>
                <label for="pausatf_mark"><?php esc_html_e( 'Mark / Result', 'pausatf-membership' ); ?></label>
                <input type="text" id="pausatf_mark" name="pausatf_mark" required maxlength="50">
            </p>

            <p>
                <button type="submit"><?php esc_html_e( 'Submit Score Sheet', 'pausatf-membership' ); ?></button>
            </p>
        </form>
        <?php
    }

    /**
     * Validate and persist a score-sheet submission.
     *
     * @param array<string,mixed> $post Raw $_POST data.
     * @return int|WP_Error Inserted row ID on success, WP_Error on failure.
     */
    private static function handle_submission( array $post ) {
        global $wpdb;

        $meet_name  = sanitize_text_field( wp_unslash( $post['pausatf_meet_name'] ?? '' ) );
        $meet_date  = sanitize_text_field( wp_unslash( $post['pausatf_meet_date'] ?? '' ) );
        $club_id    = (int) ( $post['pausatf_club_id'] ?? 0 );
        $athlete    = sanitize_text_field( wp_unslash( $post['pausatf_athlete'] ?? '' ) );
        $event_name = sanitize_text_field( wp_unslash( $post['pausatf_event_name'] ?? '' ) );
        $mark       = sanitize_text_field( wp_unslash( $post['pausatf_mark'] ?? '' ) );

        if ( '' === $meet_name || '' === $meet_date || '' === $athlete || '' === $event_name || '' === $mark ) {
            return new WP_Error( 'missing_fields', __( 'All fields are required.', 'pausatf-membership' ) );
        }

        // Validate date.
        $parsed_date = date_create_from_format( 'Y-m-d', $meet_date );
        if ( false === $parsed_date || $meet_date !== $parsed_date->format( 'Y-m-d' ) ) {
            return new WP_Error( 'invalid_date', __( 'Invalid meet date.', 'pausatf-membership' ) );
        }

        // Validate club ID exists (0 = "no club" is allowed for unattached athletes).
        if ( $club_id > 0 ) {
            $club_post = get_post( $club_id );
            if ( ! $club_post instanceof WP_Post || PAUSATF_CPT_Club::POST_TYPE !== $club_post->post_type ) {
                return new WP_Error( 'invalid_club', __( 'Invalid club selected.', 'pausatf-membership' ) );
            }
        }

        $table = $wpdb->prefix . 'pausatf_scores';

        $inserted = $wpdb->insert(
            $table,
            [
                'meet_name'    => $meet_name,
                'meet_date'    => $meet_date,
                'club_id'      => $club_id,
                'athlete'      => $athlete,
                'event_name'   => $event_name,
                'mark'         => $mark,
                'submitted_by' => get_current_user_id(),
            ],
            [ '%s', '%s', '%d', '%s', '%s', '%s', '%d' ]
        );

        if ( false === $inserted ) {
            error_log( '[pausatf-membership] score insert failed: ' . $wpdb->last_error );
            return new WP_Error( 'db_error', __( 'Could not save the score sheet. Please try again.', 'pausatf-membership' ) );
        }

        return (int) $wpdb->insert_id;
    }
}
