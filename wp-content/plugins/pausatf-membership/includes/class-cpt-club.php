<?php
/**
 * Custom post type: pausatf_club
 *
 * Public club directory. Replaces the legacy clubs.php / clubprofiles.php
 * scripts that queried the pausatf_clubs MySQL database.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PAUSATF_CPT_Club {

	public static function register(): void {
		$labels = array(
			'name'                  => _x( 'Clubs', 'post type general name', 'pausatf-membership' ),
			'singular_name'         => _x( 'Club', 'post type singular name', 'pausatf-membership' ),
			'add_new_item'          => __( 'Add New Club', 'pausatf-membership' ),
			'edit_item'             => __( 'Edit Club', 'pausatf-membership' ),
			'view_item'             => __( 'View Club', 'pausatf-membership' ),
			'view_items'            => __( 'View Clubs', 'pausatf-membership' ),
			'search_items'          => __( 'Search Clubs', 'pausatf-membership' ),
			'not_found'             => __( 'No clubs found.', 'pausatf-membership' ),
			'not_found_in_trash'    => __( 'No clubs found in trash.', 'pausatf-membership' ),
			'all_items'             => __( 'All Clubs', 'pausatf-membership' ),
			'menu_name'             => __( 'Clubs', 'pausatf-membership' ),
			'name_admin_bar'        => __( 'Club', 'pausatf-membership' ),
		);

		register_post_type(
			'pausatf_club',
			array(
				'labels'       => $labels,
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-groups',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
				'rewrite'      => array( 'slug' => 'clubs' ),
			)
		);

		self::register_meta();
	}

	private static function register_meta(): void {
		$meta_fields = array(
			'club_code'      => array(
				'description' => 'Short club code (e.g. LMTC)',
				'type'        => 'string',
			),
			'club_city'      => array(
				'description' => 'City where the club is based',
				'type'        => 'string',
			),
			'club_state'     => array(
				'description' => 'Two-letter state abbreviation',
				'type'        => 'string',
			),
			'affiliate_type' => array(
				'description' => 'USATF affiliate type (e.g. Club, School, Masters)',
				'type'        => 'string',
			),
		);

		foreach ( $meta_fields as $key => $args ) {
			register_post_meta(
				'pausatf_club',
				$key,
				array(
					'type'              => $args['type'],
					'description'       => $args['description'],
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function (): bool {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}

		// Optional: URL to the club's Athletic.net / MileSplit results page.
		// Not exposed in REST — editors set it via the custom fields meta box.
		register_post_meta(
			'pausatf_club',
			'_club_results_url',
			array(
				'type'              => 'string',
				'description'       => 'External results URL (Athletic.net, MileSplit, etc.)',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
