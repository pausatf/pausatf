<?php
/**
 * Custom Post Type: pausatf_club
 *
 * Replaces legacy clubs.php (Teeters, 2007).
 * Shortcode: [pausatf_clubs]
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the pausatf_club custom post type and its [pausatf_clubs] shortcode.
 */
class PAUSATF_CPT_Club {

    /**
     * Post type slug.
     */
    public const POST_TYPE = 'pausatf_club';

    /**
     * Register the custom post type.
     *
     * Hooked to 'init'.
     */
    public static function register(): void {
        register_post_type(
            self::POST_TYPE,
            [
                'label'               => __( 'Clubs', 'pausatf-membership' ),
                'labels'              => self::labels(),
                'description'         => __( 'PA/USATF member clubs.', 'pausatf-membership' ),
                'public'              => true,
                'hierarchical'        => false,
                'show_in_menu'        => true,
                'show_in_rest'        => true,
                'menu_icon'           => 'dashicons-groups',
                'supports'            => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
                'rewrite'             => [ 'slug' => 'clubs' ],
                'has_archive'         => true,
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
            ]
        );
    }

    /**
     * Register the [pausatf_clubs] shortcode.
     *
     * Hooked to 'init'.
     */
    public static function register_shortcode(): void {
        add_shortcode( 'pausatf_clubs', [ self::class, 'render_shortcode' ] );
    }

    /**
     * Render [pausatf_clubs] shortcode.
     *
     * Accepted attributes:
     *   orderby  – WP_Query orderby param (default: title)
     *   order    – ASC | DESC (default: ASC)
     *   per_page – posts_per_page (default: -1 = all)
     *
     * @param array<string,string>|string $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function render_shortcode( $atts ): string {
        $atts = shortcode_atts(
            [
                'orderby'  => 'title',
                'order'    => 'ASC',
                'per_page' => '-1',
            ],
            $atts,
            'pausatf_clubs'
        );

        $query = new WP_Query( [
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'orderby'        => sanitize_key( $atts['orderby'] ),
            'order'          => in_array( strtoupper( $atts['order'] ), [ 'ASC', 'DESC' ], true )
                                    ? strtoupper( $atts['order'] )
                                    : 'ASC',
            'posts_per_page' => (int) $atts['per_page'],
            'no_found_rows'  => true,
        ] );

        if ( ! $query->have_posts() ) {
            return '<p class="pausatf-clubs--empty">' . esc_html__( 'No clubs found.', 'pausatf-membership' ) . '</p>';
        }

        ob_start();
        ?>
        <ul class="pausatf-clubs">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <li class="pausatf-clubs__item">
                <a class="pausatf-clubs__link" href="<?php the_permalink(); ?>">
                    <?php the_title(); ?>
                </a>
            </li>
        <?php endwhile; ?>
        </ul>
        <?php
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * @return array<string,string>
     */
    private static function labels(): array {
        return [
            'name'               => _x( 'Clubs', 'post type general name', 'pausatf-membership' ),
            'singular_name'      => _x( 'Club', 'post type singular name', 'pausatf-membership' ),
            'add_new'            => __( 'Add New Club', 'pausatf-membership' ),
            'add_new_item'       => __( 'Add New Club', 'pausatf-membership' ),
            'edit_item'          => __( 'Edit Club', 'pausatf-membership' ),
            'new_item'           => __( 'New Club', 'pausatf-membership' ),
            'view_item'          => __( 'View Club', 'pausatf-membership' ),
            'search_items'       => __( 'Search Clubs', 'pausatf-membership' ),
            'not_found'          => __( 'No clubs found.', 'pausatf-membership' ),
            'not_found_in_trash' => __( 'No clubs found in Trash.', 'pausatf-membership' ),
            'menu_name'          => __( 'Clubs', 'pausatf-membership' ),
        ];
    }
}
