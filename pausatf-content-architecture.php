<?php
/**
 * Plugin Name: PAUSATF Content Architecture
 * Description: Dev-first structured content model, editor workflow, and SEO/query hooks.
 * Version: 0.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pausatf_architecture_post_types() {
    return array( 'committee_update', 'race_result', 'meeting_minutes' );
}

function pausatf_architecture_base_roles() {
    return array( 'administrator', 'editor', 'committee_editor', 'committee_manager' );
}

function pausatf_architecture_collect_post_type_caps( $post_type ) {
    $object = get_post_type_object( $post_type );
    if ( ! $object || empty( $object->cap ) ) {
        return array();
    }

    $caps = array();
    foreach ( (array) $object->cap as $cap ) {
        if ( is_string( $cap ) && '' !== $cap ) {
            $caps[] = $cap;
        }
    }

    return array_values( array_unique( $caps ) );
}

function pausatf_architecture_caps_schema_version() {
    return '0.2.1';
}

function pausatf_architecture_register_post_types() {
    register_post_type(
        'committee_update',
        array(
            'labels'       => array(
                'name'          => 'Committee Updates',
                'singular_name' => 'Committee Update',
            ),
            'public'        => true,
            'show_in_rest'  => true,
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-megaphone',
            'capability_type' => array( 'committee_update', 'committee_updates' ),
            'map_meta_cap'  => true,
            'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
            'template'      => array(
                array( 'core/paragraph', array( 'placeholder' => 'Write a plain-language summary for members.' ) ),
                array( 'core/heading', array( 'level' => 3, 'content' => 'What changed' ) ),
                array( 'core/list', array( 'placeholder' => 'List the practical changes.' ) ),
                array( 'core/heading', array( 'level' => 3, 'content' => 'Action needed' ) ),
                array( 'core/paragraph', array( 'placeholder' => 'Explain what committee members should do next.' ) ),
            ),
            'template_lock' => 'insert',
        )
    );

    register_post_type(
        'race_result',
        array(
            'labels'        => array(
                'name'          => 'Race Results',
                'singular_name' => 'Race Result',
            ),
            'public'        => true,
            'show_in_rest'  => true,
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-awards',
            'capability_type' => array( 'race_result', 'race_results' ),
            'map_meta_cap'  => true,
            'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
            'template'      => array(
                array( 'core/paragraph', array( 'placeholder' => 'Write a short race summary.' ) ),
                array( 'core/heading', array( 'level' => 3, 'content' => 'Top results' ) ),
                array( 'core/list', array( 'placeholder' => 'Top finishers and times.' ) ),
                array( 'core/heading', array( 'level' => 3, 'content' => 'Links' ) ),
                array( 'core/paragraph', array( 'placeholder' => 'Official results link and related resources.' ) ),
            ),
            'template_lock' => 'insert',
        )
    );

    register_post_type(
        'meeting_minutes',
        array(
            'labels'        => array(
                'name'          => 'Meeting Minutes',
                'singular_name' => 'Meeting Minutes',
            ),
            'public'        => true,
            'show_in_rest'  => true,
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-media-document',
            'capability_type' => array( 'meeting_minute', 'meeting_minutes' ),
            'map_meta_cap'  => true,
            'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
            'template'      => array(
                array( 'core/heading', array( 'level' => 3, 'content' => 'Attendance' ) ),
                array( 'core/list', array( 'placeholder' => 'List attendees and absent members.' ) ),
                array( 'core/heading', array( 'level' => 3, 'content' => 'Decisions and actions' ) ),
                array( 'core/paragraph', array( 'placeholder' => 'Summarize decisions and assigned action items.' ) ),
                array( 'core/heading', array( 'level' => 3, 'content' => 'Next meeting' ) ),
                array( 'core/paragraph', array( 'placeholder' => 'Date, time, and location of next meeting.' ) ),
            ),
            'template_lock' => 'insert',
        )
    );
}
add_action( 'init', 'pausatf_architecture_register_post_types', 9 );

function pausatf_architecture_sync_roles_and_caps() {
    $role_blueprints = array(
        'committee_editor'  => array(
            'name' => 'Committee Editor',
            'caps' => array(
                'read'               => true,
                'upload_files'       => true,
                'edit_posts'         => true,
                'delete_posts'       => true,
                'publish_posts'      => true,
                'read_private_posts' => true,
            ),
        ),
        'committee_manager' => array(
            'name' => 'Committee Manager',
            'caps' => array(
                'read'               => true,
                'upload_files'       => true,
                'edit_posts'         => true,
                'delete_posts'       => true,
                'publish_posts'      => true,
                'read_private_posts' => true,
                'manage_categories'  => true,
                'edit_others_posts'  => true,
            ),
        ),
    );

    $target_caps_schema = pausatf_architecture_caps_schema_version();
    $stored_caps_schema = get_option( 'pausatf_architecture_caps_schema_version', '' );
    $needs_sync         = ( $stored_caps_schema !== $target_caps_schema );

    if ( ! $needs_sync ) {
        foreach ( array_keys( $role_blueprints ) as $role_name ) {
            if ( ! get_role( $role_name ) ) {
                $needs_sync = true;
                break;
            }
        }
    }

    if ( ! $needs_sync ) {
        return;
    }

    foreach ( $role_blueprints as $role_name => $blueprint ) {
        if ( ! get_role( $role_name ) ) {
            add_role( $role_name, $blueprint['name'], $blueprint['caps'] );
        }

        $role = get_role( $role_name );
        if ( ! $role ) {
            continue;
        }

        foreach ( $blueprint['caps'] as $cap => $grant ) {
            if ( $grant ) {
                $role->add_cap( $cap );
            }
        }
    }

    $content_caps = array();
    foreach ( pausatf_architecture_post_types() as $post_type ) {
        $content_caps = array_merge( $content_caps, pausatf_architecture_collect_post_type_caps( $post_type ) );
    }
    $content_caps = array_values( array_unique( $content_caps ) );

    foreach ( pausatf_architecture_base_roles() as $role_name ) {
        $role = get_role( $role_name );
        if ( ! $role ) {
            continue;
        }

        foreach ( $content_caps as $cap ) {
            $role->add_cap( $cap );
        }
    }

    update_option( 'pausatf_architecture_caps_schema_version', $target_caps_schema, false );
}
add_action( 'init', 'pausatf_architecture_sync_roles_and_caps', 30 );

function pausatf_architecture_register_taxonomies() {
    $post_types = array_merge( array( 'post' ), pausatf_architecture_post_types() );

    register_taxonomy(
        'committee',
        $post_types,
        array(
            'labels'            => array(
                'name'          => 'Committees',
                'singular_name' => 'Committee',
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'committee' ),
        )
    );

    register_taxonomy(
        'discipline',
        $post_types,
        array(
            'labels'            => array(
                'name'          => 'Disciplines',
                'singular_name' => 'Discipline',
            ),
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'discipline' ),
        )
    );

    register_taxonomy(
        'audience',
        $post_types,
        array(
            'labels'            => array(
                'name'          => 'Audiences',
                'singular_name' => 'Audience',
            ),
            'public'            => true,
            'hierarchical'      => false,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'audience' ),
        )
    );
}
add_action( 'init', 'pausatf_architecture_register_taxonomies', 10 );

function pausatf_architecture_seed_default_terms() {
    $defaults = array(
        'committee'  => array(
            'Road',
            'Track & Field',
            'Cross Country',
            'Officials',
            'Coaches',
            'Youth',
            'Masters',
        ),
        'discipline' => array(
            'Road',
            'Track & Field',
            'Cross Country',
            'Race Walking',
            'Trail & Mountain',
        ),
        'audience'   => array(
            'Athletes',
            'Coaches',
            'Officials',
            'Clubs',
            'Volunteers',
            'General Public',
        ),
    );

    foreach ( $defaults as $taxonomy => $terms ) {
        foreach ( $terms as $term_name ) {
            if ( ! term_exists( $term_name, $taxonomy ) ) {
                wp_insert_term( $term_name, $taxonomy );
            }
        }
    }
}
add_action( 'init', 'pausatf_architecture_seed_default_terms', 40 );

function pausatf_architecture_register_meta() {
    $post_types = pausatf_architecture_post_types();

    foreach ( $post_types as $post_type ) {
        register_post_meta(
            $post_type,
            'pausatf_event_date',
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $post_type,
            'pausatf_event_location',
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $post_type,
            'pausatf_event_link',
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'esc_url_raw',
                'auth_callback'     => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $post_type,
            'pausatf_contact_email',
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_email',
                'auth_callback'     => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );
    }
}
add_action( 'init', 'pausatf_architecture_register_meta', 11 );

function pausatf_architecture_strip_html_titles( $data, $postarr ) {
    $target_types = pausatf_architecture_post_types();

    if ( empty( $data['post_type'] ) || ! in_array( $data['post_type'], $target_types, true ) ) {
        return $data;
    }

    if ( isset( $data['post_title'] ) ) {
        $data['post_title'] = wp_strip_all_tags( $data['post_title'], true );
    }

    return $data;
}
add_filter( 'wp_insert_post_data', 'pausatf_architecture_strip_html_titles', 20, 2 );

function pausatf_architecture_register_patterns() {
    if ( ! function_exists( 'register_block_pattern' ) || ! function_exists( 'register_block_pattern_category' ) ) {
        return;
    }

    register_block_pattern_category(
        'pausatf-committee',
        array( 'label' => 'PAUSATF Committee Content' )
    );

    register_block_pattern(
        'pausatf/committee-update',
        array(
            'title'      => 'Committee Update Layout',
            'categories' => array( 'pausatf-committee' ),
            'content'    => "<!-- wp:heading {\"level\":3} --><h3>Summary</h3><!-- /wp:heading --><!-- wp:paragraph --><p></p><!-- /wp:paragraph --><!-- wp:heading {\"level\":3} --><h3>What members need to know</h3><!-- /wp:heading --><!-- wp:list --><ul><li></li></ul><!-- /wp:list -->",
        )
    );

    register_block_pattern(
        'pausatf/race-result',
        array(
            'title'      => 'Race Result Layout',
            'categories' => array( 'pausatf-committee' ),
            'content'    => "<!-- wp:heading {\"level\":3} --><h3>Race Summary</h3><!-- /wp:heading --><!-- wp:paragraph --><p></p><!-- /wp:paragraph --><!-- wp:heading {\"level\":3} --><h3>Top Finishers</h3><!-- /wp:heading --><!-- wp:list --><ul><li></li></ul><!-- /wp:list -->",
        )
    );

    register_block_pattern(
        'pausatf/meeting-minutes',
        array(
            'title'      => 'Meeting Minutes Layout',
            'categories' => array( 'pausatf-committee' ),
            'content'    => "<!-- wp:heading {\"level\":3} --><h3>Attendance</h3><!-- /wp:heading --><!-- wp:list --><ul><li></li></ul><!-- /wp:list --><!-- wp:heading {\"level\":3} --><h3>Decisions</h3><!-- /wp:heading --><!-- wp:paragraph --><p></p><!-- /wp:paragraph -->",
        )
    );

    register_block_pattern(
        'pausatf/committee-hub',
        array(
            'title'      => 'Committee Hub (Query Loops)',
            'categories' => array( 'pausatf-committee' ),
            'content'    =>
                "<!-- wp:heading {\"level\":2} --><h2>Latest Committee Updates</h2><!-- /wp:heading -->" .
                "<!-- wp:query {\"query\":{\"perPage\":\"8\",\"postType\":\"committee_update\",\"order\":\"desc\",\"orderBy\":\"date\"},\"displayLayout\":{\"type\":\"list\"}} --><div class=\"wp-block-query\"><!-- wp:post-template -->" .
                "<!-- wp:post-title {\"isLink\":true} /--><!-- wp:post-date /--><!-- wp:post-excerpt {\"moreText\":\"Read update\"} /-->" .
                "<!-- /wp:post-template --><!-- wp:query-pagination {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"space-between\"}} --><!-- wp:query-pagination-previous /--><!-- wp:query-pagination-next /--><!-- /wp:query-pagination --></div><!-- /wp:query -->" .
                "<!-- wp:heading {\"level\":2} --><h2>Latest Race Results</h2><!-- /wp:heading -->" .
                "<!-- wp:query {\"query\":{\"perPage\":\"8\",\"postType\":\"race_result\",\"order\":\"desc\",\"orderBy\":\"date\"},\"displayLayout\":{\"type\":\"list\"}} --><div class=\"wp-block-query\"><!-- wp:post-template -->" .
                "<!-- wp:post-title {\"isLink\":true} /--><!-- wp:post-date /--><!-- wp:post-excerpt {\"moreText\":\"View results\"} /-->" .
                "<!-- /wp:post-template --></div><!-- /wp:query -->" .
                "<!-- wp:heading {\"level\":2} --><h2>Recent Meeting Minutes</h2><!-- /wp:heading -->" .
                "<!-- wp:query {\"query\":{\"perPage\":\"8\",\"postType\":\"meeting_minutes\",\"order\":\"desc\",\"orderBy\":\"date\"},\"displayLayout\":{\"type\":\"list\"}} --><div class=\"wp-block-query\"><!-- wp:post-template -->" .
                "<!-- wp:post-title {\"isLink\":true} /--><!-- wp:post-date /--><!-- wp:post-excerpt {\"moreText\":\"Read minutes\"} /-->" .
                "<!-- /wp:post-template --></div><!-- /wp:query -->",
        )
    );
}
add_action( 'init', 'pausatf_architecture_register_patterns', 12 );

function pausatf_architecture_register_committee_review_status() {
    register_post_status(
        'committee_review',
        array(
            'label'                     => 'Committee Review',
            'public'                    => false,
            'internal'                  => false,
            'protected'                 => true,
            'private'                   => false,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'label_count'               => _n_noop( 'Committee Review <span class="count">(%s)</span>', 'Committee Review <span class="count">(%s)</span>' ),
        )
    );
}
add_action( 'init', 'pausatf_architecture_register_committee_review_status', 13 );

function pausatf_architecture_add_committee_review_to_dropdown() {
    global $post;

    if ( ! $post || ! in_array( $post->post_type, pausatf_architecture_post_types(), true ) ) {
        return;
    }
    ?>
    <script>
    jQuery(function($){
        var status = 'committee_review';
        if ($('#post_status option[value="' + status + '"]').length === 0) {
            $('#post_status').append('<option value="' + status + '">Committee Review</option>');
        }
        if ($('#hidden_post_status').val() === status) {
            $('#post_status').val(status);
            $('#post-status-display').text('Committee Review');
        }
    });
    </script>
    <?php
}
add_action( 'admin_footer-post.php', 'pausatf_architecture_add_committee_review_to_dropdown' );
add_action( 'admin_footer-post-new.php', 'pausatf_architecture_add_committee_review_to_dropdown' );

function pausatf_architecture_display_committee_review_state( $states, $post ) {
    if ( 'committee_review' === $post->post_status ) {
        $states[] = 'Committee Review';
    }

    return $states;
}
add_filter( 'display_post_states', 'pausatf_architecture_display_committee_review_state', 10, 2 );

function pausatf_architecture_disable_theme_seo_hooks() {
    remove_action( 'wp_head', 'pausatf_gp_child_output_seo_head_tags', 1 );
    remove_filter( 'wp_robots', 'pausatf_gp_child_filter_wp_robots', 20 );
    remove_filter( 'robots_txt', 'pausatf_gp_child_append_robots_txt_sitemap', 999 );

    remove_action( 'wp_head', 'thesource_child_output_seo_head_tags', 1 );
    remove_filter( 'wp_robots', 'thesource_child_filter_wp_robots', 20 );
    remove_filter( 'robots_txt', 'thesource_child_append_robots_txt_sitemap', 999 );
}
add_action( 'after_setup_theme', 'pausatf_architecture_disable_theme_seo_hooks', 100 );

function pausatf_architecture_has_seo_plugin() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function pausatf_architecture_meta_description( $post ) {
    if ( ! empty( $post->post_excerpt ) ) {
        return wp_strip_all_tags( $post->post_excerpt, true );
    }

    if ( ! empty( $post->post_content ) ) {
        return wp_trim_words( wp_strip_all_tags( $post->post_content, true ), 35, '…' );
    }

    return wp_strip_all_tags( get_bloginfo( 'description' ), true );
}

function pausatf_architecture_canonical_url() {
    if ( is_singular() ) {
        return get_permalink( get_queried_object_id() );
    }

    if ( is_front_page() ) {
        return home_url( '/' );
    }

    return '';
}

function pausatf_architecture_image_url( $post ) {
    if ( $post && has_post_thumbnail( $post ) ) {
        $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'full' );
        if ( ! empty( $image[0] ) ) {
            return $image[0];
        }
    }

    return '';
}

function pausatf_architecture_event_schema( $post, $canonical, $description, $image_url ) {
    if ( ! $post || ! in_array( $post->post_type, pausatf_architecture_post_types(), true ) ) {
        return array();
    }

    $event_date = get_post_meta( $post->ID, 'pausatf_event_date', true );
    if ( empty( $event_date ) ) {
        return array();
    }

    $timestamp = strtotime( $event_date );
    if ( false === $timestamp ) {
        return array();
    }

    $schema = array(
        '@type'       => 'Event',
        'name'        => wp_strip_all_tags( get_the_title( $post ), true ),
        'description' => $description,
        'startDate'   => gmdate( 'c', $timestamp ),
        'url'         => $canonical,
    );

    $location = get_post_meta( $post->ID, 'pausatf_event_location', true );
    if ( ! empty( $location ) ) {
        $schema['location'] = array(
            '@type' => 'Place',
            'name'  => wp_strip_all_tags( $location, true ),
        );
    }

    $event_link = get_post_meta( $post->ID, 'pausatf_event_link', true );
    if ( ! empty( $event_link ) ) {
        $schema['sameAs'] = esc_url_raw( $event_link );
    }

    if ( ! empty( $image_url ) ) {
        $schema['image'] = $image_url;
    }

    return $schema;
}

function pausatf_architecture_output_seo_head_tags() {
    if ( is_admin() || wp_doing_ajax() || pausatf_architecture_has_seo_plugin() ) {
        return;
    }

    if ( ! is_singular() && ! is_front_page() ) {
        return;
    }

    $post        = is_singular() ? get_post( get_queried_object_id() ) : null;
    $title       = wp_strip_all_tags( wp_get_document_title(), true );
    $canonical   = pausatf_architecture_canonical_url();
    $description = $post ? pausatf_architecture_meta_description( $post ) : wp_strip_all_tags( get_bloginfo( 'description' ), true );
    $image_url   = pausatf_architecture_image_url( $post );
    $og_type     = is_singular() ? 'article' : 'website';

    $graph = array(
        '@context' => 'https://schema.org',
        '@graph'   => array(
            array(
                '@type' => 'Organization',
                '@id'   => home_url( '/#organization' ),
                'name'  => wp_strip_all_tags( get_bloginfo( 'name' ), true ),
                'url'   => home_url( '/' ),
            ),
            array(
                '@type' => 'WebSite',
                '@id'   => home_url( '/#website' ),
                'url'   => home_url( '/' ),
                'name'  => wp_strip_all_tags( get_bloginfo( 'name' ), true ),
            ),
        ),
    );

    if ( ! empty( $canonical ) ) {
        $graph['@graph'][] = array(
            '@type'       => is_singular() ? 'Article' : 'WebPage',
            '@id'         => $canonical . '#webpage',
            'url'         => $canonical,
            'name'        => $title,
            'description' => $description,
        );
    }

    $event_schema = pausatf_architecture_event_schema( $post, $canonical, $description, $image_url );
    if ( ! empty( $event_schema ) ) {
        $graph['@graph'][] = $event_schema;
    }
    ?>
    <!-- PAUSATF_ARCH_SEO -->
    <?php if ( ! empty( $description ) ) : ?>
    <meta name="description" content="<?php echo esc_attr( $description ); ?>" />
    <?php endif; ?>
    <?php if ( ! empty( $canonical ) && ( ! is_singular() || ! has_action( 'wp_head', 'rel_canonical' ) ) ) : ?>
    <link rel="canonical" href="<?php echo esc_url( $canonical ); ?>" />
    <?php endif; ?>
    <meta property="og:site_name" content="<?php echo esc_attr( wp_strip_all_tags( get_bloginfo( 'name' ), true ) ); ?>" />
    <meta property="og:type" content="<?php echo esc_attr( $og_type ); ?>" />
    <meta property="og:title" content="<?php echo esc_attr( $title ); ?>" />
    <?php if ( ! empty( $description ) ) : ?>
    <meta property="og:description" content="<?php echo esc_attr( $description ); ?>" />
    <?php endif; ?>
    <?php if ( ! empty( $canonical ) ) : ?>
    <meta property="og:url" content="<?php echo esc_url( $canonical ); ?>" />
    <?php endif; ?>
    <?php if ( ! empty( $image_url ) ) : ?>
    <meta property="og:image" content="<?php echo esc_url( $image_url ); ?>" />
    <?php endif; ?>
    <meta name="twitter:card" content="<?php echo esc_attr( ! empty( $image_url ) ? 'summary_large_image' : 'summary' ); ?>" />
    <meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>" />
    <?php if ( ! empty( $description ) ) : ?>
    <meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>" />
    <?php endif; ?>
    <?php if ( ! empty( $image_url ) ) : ?>
    <meta name="twitter:image" content="<?php echo esc_url( $image_url ); ?>" />
    <?php endif; ?>
    <script type="application/ld+json"><?php echo wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>
    <?php
}
add_action( 'wp_head', 'pausatf_architecture_output_seo_head_tags', 1 );

function pausatf_architecture_filter_wp_robots( $robots ) {
    if ( pausatf_architecture_has_seo_plugin() ) {
        return $robots;
    }

    if ( is_search() || is_tag() || is_author() || is_date() || is_attachment() || ( is_archive() && is_paged() ) ) {
        $robots['noindex'] = true;
    }

    $robots['max-image-preview'] = 'large';
    $robots['max-snippet']       = -1;
    $robots['max-video-preview'] = -1;

    return $robots;
}
add_filter( 'wp_robots', 'pausatf_architecture_filter_wp_robots', 20 );

function pausatf_architecture_append_robots_txt_sitemap( $output, $public ) {
    if ( empty( $public ) || pausatf_architecture_has_seo_plugin() ) {
        return $output;
    }

    $sitemap_line = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
    if ( false !== stripos( $output, $sitemap_line ) ) {
        return $output;
    }

    return rtrim( $output ) . "\n" . $sitemap_line . "\n";
}
add_filter( 'robots_txt', 'pausatf_architecture_append_robots_txt_sitemap', 999, 2 );
