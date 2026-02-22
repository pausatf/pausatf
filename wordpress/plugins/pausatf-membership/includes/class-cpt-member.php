<?php
/**
 * Custom Post Type: pausatf_member
 *
 * Replaces legacy members.php (Teeters, 2007).
 * Shortcode: [pausatf_members] — login-required; filters by club.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the pausatf_member custom post type and its [pausatf_members] shortcode.
 */
class PAUSATF_CPT_Member {

    /**
     * Post type slug.
     */
    public const POST_TYPE = 'pausatf_member';

    /**
     * Register the custom post type.
     *
     * Hooked to 'init'.
     */
    public static function register(): void {
        register_post_type(
            self::POST_TYPE,
            [
                'label'           => __( 'Members', 'pausatf-membership' ),
                'labels'          => self::labels(),
                'description'     => __( 'PA/USATF association members.', 'pausatf-membership' ),
                // Members are private — not publicly browsable by default.
                'public'          => false,
                'show_ui'         => true,
                'show_in_menu'    => true,
                'show_in_rest'    => true,
                'menu_icon'       => 'dashicons-id',
                'supports'        => [ 'title', 'custom-fields' ],
                'rewrite'         => false,
                'has_archive'     => false,
                'capability_type' => 'post',
                'map_meta_cap'    => true,
            ]
        );
    }

    /**
     * Register the [pausatf_members] shortcode.
     *
     * Hooked to 'init'.
     */
    public static function register_shortcode(): void {
        add_shortcode( 'pausatf_members', [ self::class, 'render_shortcode' ] );
    }

    /**
     * Render [pausatf_members] shortcode.
     *
     * This shortcode requires the visitor to be logged in — mirrors the
     * login-gated behaviour of the original members.php Teeters script.
     *
     * Accepted attributes:
     *   club     – pausatf_club post ID or slug to filter by (default: empty = all)
     *   per_page – posts_per_page (default: 50)
     *   orderby  – WP_Query orderby param (default: title)
     *   order    – ASC | DESC (default: ASC)
     *
     * @param array<string,string>|string $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function render_shortcode( $atts ): string {
        if ( ! is_user_logged_in() ) {
            return sprintf(
                '<p class="pausatf-members--login-required">%s <a href="%s">%s</a></p>',
                esc_html__( 'You must be logged in to view the member roster.', 'pausatf-membership' ),
                esc_url( wp_login_url( get_permalink() ) ),
                esc_html__( 'Log in', 'pausatf-membership' )
            );
        }

        $atts = shortcode_atts(
            [
                'club'     => '',
                'per_page' => '50',
                'orderby'  => 'title',
                'order'    => 'ASC',
            ],
            $atts,
            'pausatf_members'
        );

        $args = [
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'orderby'        => sanitize_key( $atts['orderby'] ),
            'order'          => in_array( strtoupper( $atts['order'] ), [ 'ASC', 'DESC' ], true )
                                    ? strtoupper( $atts['order'] )
                                    : 'ASC',
            'posts_per_page' => (int) $atts['per_page'],
            'no_found_rows'  => false,
        ];

        // Filter by club if the attribute is provided.
        $club = sanitize_text_field( $atts['club'] );
        if ( '' !== $club ) {
            // Accept either a numeric post ID or a post slug; resolve slug → ID.
            if ( is_numeric( $club ) ) {
                $club_id = (int) $club;
            } else {
                $club_post = get_page_by_path( $club, OBJECT, PAUSATF_CPT_Club::POST_TYPE );
                $club_id   = $club_post instanceof WP_Post ? (int) $club_post->ID : 0;
            }

            if ( $club_id > 0 ) {
                $args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
                    [
                        'key'     => '_pausatf_club_id',
                        'value'   => $club_id,
                        'compare' => '=',
                        'type'    => 'NUMERIC',
                    ],
                ];
            }
        }

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return '<p class="pausatf-members--empty">' . esc_html__( 'No members found.', 'pausatf-membership' ) . '</p>';
        }

        ob_start();
        ?>
        <table class="pausatf-members">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Name', 'pausatf-membership' ); ?></th>
                    <th><?php esc_html_e( 'Club', 'pausatf-membership' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <tr class="pausatf-members__row">
                    <td class="pausatf-members__name"><?php the_title(); ?></td>
                    <td class="pausatf-members__club">
                        <?php
                        $club_id = (int) get_post_meta( get_the_ID(), '_pausatf_club_id', true );
                        if ( $club_id > 0 ) {
                            $club_post = get_post( $club_id );
                            if ( $club_post instanceof WP_Post ) {
                                echo esc_html( $club_post->post_title );
                            }
                        }
                        ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
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
            'name'               => _x( 'Members', 'post type general name', 'pausatf-membership' ),
            'singular_name'      => _x( 'Member', 'post type singular name', 'pausatf-membership' ),
            'add_new'            => __( 'Add New Member', 'pausatf-membership' ),
            'add_new_item'       => __( 'Add New Member', 'pausatf-membership' ),
            'edit_item'          => __( 'Edit Member', 'pausatf-membership' ),
            'new_item'           => __( 'New Member', 'pausatf-membership' ),
            'view_item'          => __( 'View Member', 'pausatf-membership' ),
            'search_items'       => __( 'Search Members', 'pausatf-membership' ),
            'not_found'          => __( 'No members found.', 'pausatf-membership' ),
            'not_found_in_trash' => __( 'No members found in Trash.', 'pausatf-membership' ),
            'menu_name'          => __( 'Members', 'pausatf-membership' ),
        ];
    }
}
