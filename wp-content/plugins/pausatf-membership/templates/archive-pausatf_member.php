<?php
/**
 * Member roster template — NOT a public page.
 *
 * Loaded via ob_start() from pausatf_members_shortcode().
 * Login check is already performed in the shortcode callback; this template
 * is only reached when the user is authenticated.
 *
 * Variables available:
 *   $members_query (WP_Query)
 *   $club          (string) — active club filter, may be empty
 *   $per_page      (int)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Double-check — defence in depth. Shortcode callback already gates this.
if ( ! current_user_can( 'read' ) ) {
	echo '<p>' . esc_html__( 'You do not have permission to view this page.', 'pausatf-membership' ) . '</p>';
	return;
}
?>
<div class="pausatf-members">

	<form class="pausatf-members-filter" method="get">
		<label for="pausatf-club-filter"><?php esc_html_e( 'Filter by club:', 'pausatf-membership' ); ?></label>
		<input
			type="text"
			id="pausatf-club-filter"
			name="club"
			value="<?php echo esc_attr( $club ); ?>"
			placeholder="<?php esc_attr_e( 'Club code, e.g. LMTC', 'pausatf-membership' ); ?>"
		>
		<button type="submit"><?php esc_html_e( 'Filter', 'pausatf-membership' ); ?></button>
		<?php if ( ! empty( $club ) ) : ?>
			<a href="<?php echo esc_url( remove_query_arg( 'club' ) ); ?>"><?php esc_html_e( 'Clear', 'pausatf-membership' ); ?></a>
		<?php endif; ?>
	</form>

	<?php if ( $members_query->have_posts() ) : ?>
		<table class="pausatf-members-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Last Name', 'pausatf-membership' ); ?></th>
					<th scope="col"><?php esc_html_e( 'First Name', 'pausatf-membership' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Club', 'pausatf-membership' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Type', 'pausatf-membership' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Expires', 'pausatf-membership' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php while ( $members_query->have_posts() ) : $members_query->the_post(); ?>
					<?php
					$post_id   = get_the_ID();
					$first     = get_post_meta( $post_id, 'first_name', true );
					$last      = get_post_meta( $post_id, 'last_name', true );
					$club_code = get_post_meta( $post_id, 'club_code', true );
					$mem_type  = get_post_meta( $post_id, 'membership_type', true );
					$expiry    = get_post_meta( $post_id, 'membership_expiry', true );

					// Flag expired memberships for easy visual scanning.
					$expired     = ! empty( $expiry ) && $expiry < gmdate( 'Y-m-d' );
					$row_class   = $expired ? ' class="pausatf-expired"' : '';
					?>
					<tr<?php echo $row_class; // phpcs:ignore WordPress.Security.EscapeOutput -- only static strings ?>>
						<td><?php echo esc_html( $last ); ?></td>
						<td><?php echo esc_html( $first ); ?></td>
						<td><?php echo esc_html( $club_code ); ?></td>
						<td><?php echo esc_html( $mem_type ); ?></td>
						<td><?php echo esc_html( $expiry ); ?></td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>

		<div class="pausatf-pagination">
			<?php
			echo paginate_links( array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => max( 1, absint( get_query_var( 'paged' ) ) ),
				'total'   => $members_query->max_num_pages,
			) );
			?>
		</div>

	<?php else : ?>
		<p><?php esc_html_e( 'No members found.', 'pausatf-membership' ); ?></p>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>
