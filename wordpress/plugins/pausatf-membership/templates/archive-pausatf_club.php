<?php
/**
 * Archive template: pausatf_club
 *
 * Used when WordPress serves /clubs/ — the public club directory.
 * Falls back to the theme's archive.php if this file does not exist at the
 * time the theme is switched (the plugin filter re-injects it on the fly).
 *
 * @package PAUSATF_Membership
 */

declare( strict_types=1 );

get_header();
?>

<main id="primary" class="site-main">

    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e( 'PA/USATF Club Directory', 'pausatf-membership' ); ?></h1>
    </header>

    <?php if ( have_posts() ) : ?>

        <ul class="pausatf-clubs">
            <?php while ( have_posts() ) : the_post(); ?>
                <li class="pausatf-clubs__item">
                    <h2 class="pausatf-clubs__name">
                        <a class="pausatf-clubs__link" href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h2>
                    <?php if ( has_excerpt() ) : ?>
                        <div class="pausatf-clubs__excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
        </ul>

        <?php the_posts_pagination(); ?>

    <?php else : ?>

        <p class="pausatf-clubs--empty"><?php esc_html_e( 'No clubs found.', 'pausatf-membership' ); ?></p>

    <?php endif; ?>

</main>

<?php
get_sidebar();
get_footer();
