<?php
/**
 * Custom post type: pausatf_member
 *
 * Private member roster. Not publicly queryable — members contain PII
 * (name, club, expiry date, USATF ID). Access is gated at the shortcode
 * and template level via current_user_can('read').
 *
 * Replaces the legacy members.php / fetch-members.php scripts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PAUSATF_CPT_Member {

	public static function register(): void {
		$labels = array(
			'name'               => _x( 'Members', 'post type general name', 'pausatf-membership' ),
			'singular_name'      => _x( 'Member', 'post type singular name', 'pausatf-membership' ),
			'add_new_item'       => __( 'Add New Member', 'pausatf-membership' ),
			'edit_item'          => __( 'Edit Member', 'pausatf-membership' ),
			'search_items'       => __( 'Search Members', 'pausatf-membership' ),
			'not_found'          => __( 'No members found.', 'pausatf-membership' ),
			'not_found_in_trash' => __( 'No members found in trash.', 'pausatf-membership' ),
			'all_items'          => __( 'All Members', 'pausatf-membership' ),
			'menu_name'          => __( 'Members', 'pausatf-membership' ),
			'name_admin_bar'     => __( 'Member', 'pausatf-membership' ),
		);

		register_post_type(
			'pausatf_member',
			array(
				'labels'              => $labels,
				// Private: PII — do not expose publicly.
				'public'              => false,
				'publicly_queryable'  => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'show_ui'             => true,       // visible in wp-admin
				'show_in_menu'        => true,
				'capability_type'     => 'post',
				'menu_icon'           => 'dashicons-id',
				'supports'            => array( 'title', 'custom-fields' ),
				'rewrite'             => false,
			)
		);

		self::register_meta();
	}

	private static function register_meta(): void {
		$meta_fields = array(
			'member_id'         => array(
				'description' => 'Internal member record ID from the source system',
				'type'        => 'string',
			),
			'first_name'        => array(
				'description' => 'Member first name',
				'type'        => 'string',
			),
			'last_name'         => array(
				'description' => 'Member last name',
				'type'        => 'string',
			),
			'club_code'         => array(
				'description' => 'Club code matching pausatf_club post meta',
				'type'        => 'string',
			),
			'membership_type'   => array(
				'description' => 'USATF membership category (e.g. Youth, Open, Masters)',
				'type'        => 'string',
			),
			'membership_expiry' => array(
				'description' => 'Membership expiry date — UTC ISO 8601 date string (YYYY-MM-DD)',
				'type'        => 'string',
			),
			'usatf_id'          => array(
				'description' => 'USATF member ID from Sport80',
				'type'        => 'string',
			),
		);

		foreach ( $meta_fields as $key => $args ) {
			register_post_meta(
				'pausatf_member',
				$key,
				array(
					'type'              => $args['type'],
					'description'       => $args['description'],
					'single'            => true,
					// Never expose member PII via REST.
					'show_in_rest'      => false,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function (): bool {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
