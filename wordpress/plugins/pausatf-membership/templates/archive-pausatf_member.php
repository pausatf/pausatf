<?php
/**
 * Archive template: pausatf_member
 *
 * The pausatf_member post type is not publicly browsable (public => false),
 * so this template is never served by WordPress directly.  It exists for
 * future use if the CPT is made public, and as a fallback for
 * WP_Query-based theme implementations.
 *
 * Member data is surfaced through the [pausatf_members] shortcode instead.
 *
 * @package PAUSATF_Membership
 */

declare( strict_types=1 );

// Redirect to the login page if the visitor is not authenticated.
if ( ! is_user_logged_in() ) {
    auth_redirect();
}

get_header();
?>

<main id="primary" class="site-main">

    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e( 'PA/USATF Member Roster', 'pausatf-membership' ); ?></h1>
    </header>

    <?php if ( have_posts() ) : ?>

        <table class="pausatf-members">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Name', 'pausatf-membership' ); ?></th>
                    <th><?php esc_html_e( 'Club', 'pausatf-membership' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php while ( have_posts() ) : the_post(); ?>
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

        <?php the_posts_pagination(); ?>

    <?php else : ?>

        <p class="pausatf-members--empty"><?php esc_html_e( 'No members found.', 'pausatf-membership' ); ?></p>

    <?php endif; ?>

</main>

<?php
get_sidebar();
get_footer();
