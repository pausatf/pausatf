<?php
/**
 * Club directory template.
 *
 * Loaded via ob_start() from pausatf_clubs_shortcode().
 * Variables available: $clubs_query (WP_Query)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pausatf-clubs">
	<?php if ( $clubs_query->have_posts() ) : ?>
		<table class="pausatf-clubs-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Club', 'pausatf-membership' ); ?></th>
					<th scope="col"><?php esc_html_e( 'City', 'pausatf-membership' ); ?></th>
					<th scope="col"><?php esc_html_e( 'State', 'pausatf-membership' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Type', 'pausatf-membership' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Results', 'pausatf-membership' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php while ( $clubs_query->have_posts() ) : $clubs_query->the_post(); ?>
					<?php
					$post_id        = get_the_ID();
					$city           = get_post_meta( $post_id, 'club_city', true );
					$state          = get_post_meta( $post_id, 'club_state', true );
					$affiliate_type = get_post_meta( $post_id, 'affiliate_type', true );
					$results_url    = get_post_meta( $post_id, '_club_results_url', true );
					?>
					<tr>
						<td>
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</td>
						<td><?php echo esc_html( $city ); ?></td>
						<td><?php echo esc_html( $state ); ?></td>
						<td><?php echo esc_html( $affiliate_type ); ?></td>
						<td>
							<?php if ( ! empty( $results_url ) ) : ?>
								<a href="<?php echo esc_url( $results_url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Results', 'pausatf-membership' ); ?>
								</a>
							<?php else : ?>
								&mdash;
							<?php endif; ?>
						</td>
					</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No clubs found.', 'pausatf-membership' ); ?></p>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>
