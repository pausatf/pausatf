<?php
/**
 * SEO Meta Tags - Open Graph and Schema.org markup
 *
 * @package TheSource-child
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Open Graph meta tags to the head
 */
function thesource_child_open_graph_meta() {
	$og_title       = get_bloginfo( 'name' );
	$og_description = get_bloginfo( 'description' );
	$og_url         = home_url( '/' );
	$og_image       = get_template_directory_uri() . '/images/logo.png';
	$og_type        = 'website';

	// Single post/page overrides
	if ( is_singular() ) {
		global $post;
		$og_title = get_the_title();
		$og_url   = get_permalink();
		$og_type  = 'article';

		if ( has_excerpt( $post->ID ) ) {
			$og_description = get_the_excerpt();
		} elseif ( ! empty( $post->post_content ) ) {
			$og_description = wp_trim_words( strip_shortcodes( $post->post_content ), 30, '...' );
		}

		if ( has_post_thumbnail( $post->ID ) ) {
			$og_image = get_the_post_thumbnail_url( $post->ID, 'large' );
		}
	}

	// Escape all values
	$og_title       = esc_attr( $og_title );
	$og_description = esc_attr( $og_description );
	$og_url         = esc_url( $og_url );
	$og_image       = esc_url( $og_image );
	?>

	<!-- Open Graph Meta Tags -->
	<meta property="og:title" content="<?php echo $og_title; ?>" />
	<meta property="og:description" content="<?php echo $og_description; ?>" />
	<meta property="og:type" content="<?php echo esc_attr( $og_type ); ?>" />
	<meta property="og:url" content="<?php echo $og_url; ?>" />
	<meta property="og:image" content="<?php echo $og_image; ?>" />
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
	<meta property="og:locale" content="<?php echo esc_attr( get_locale() ); ?>" />

	<!-- Twitter Card Meta Tags -->
	<meta name="twitter:card" content="summary_large_image" />
	<meta name="twitter:title" content="<?php echo $og_title; ?>" />
	<meta name="twitter:description" content="<?php echo $og_description; ?>" />
	<meta name="twitter:image" content="<?php echo $og_image; ?>" />
	<meta name="twitter:site" content="@UsatfPacific" />

	<?php
}
add_action( 'wp_head', 'thesource_child_open_graph_meta', 5 );

/**
 * Add Schema.org JSON-LD structured data
 */
function thesource_child_schema_markup() {
	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(),
	);

	// Organization schema
	$organization = array(
		'@type'  => 'SportsOrganization',
		'@id'    => home_url( '/#organization' ),
		'name'   => get_bloginfo( 'name' ),
		'url'    => home_url( '/' ),
		'logo'   => array(
			'@type' => 'ImageObject',
			'url'   => get_template_directory_uri() . '/images/logo.png',
		),
		'sameAs' => array(
			'https://www.facebook.com/pausatf',
			'https://twitter.com/UsatfPacific',
			'https://www.instagram.com/usatfpacificassoc/',
			'https://www.youtube.com/channel/UC4UDU5_ALy26O1vU6rAOjjA',
		),
		'sport'  => 'Track and Field',
	);
	$schema['@graph'][] = $organization;

	// WebSite schema with search
	$website = array(
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'url'             => home_url( '/' ),
		'name'            => get_bloginfo( 'name' ),
		'description'     => get_bloginfo( 'description' ),
		'publisher'       => array(
			'@id' => home_url( '/#organization' ),
		),
		'potentialAction' => array(
			array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		),
	);
	$schema['@graph'][] = $website;

	// Add article schema for single posts
	if ( is_singular( 'post' ) ) {
		global $post;
		$article = array(
			'@type'         => 'Article',
			'@id'           => get_permalink() . '#article',
			'headline'      => get_the_title(),
			'datePublished' => get_the_date( 'c' ),
			'dateModified'  => get_the_modified_date( 'c' ),
			'author'        => array(
				'@type' => 'Person',
				'name'  => get_the_author(),
			),
			'publisher'     => array(
				'@id' => home_url( '/#organization' ),
			),
			'mainEntityOfPage' => array(
				'@id' => get_permalink(),
			),
		);

		if ( has_post_thumbnail() ) {
			$article['image'] = get_the_post_thumbnail_url( $post->ID, 'large' );
		}

		$schema['@graph'][] = $article;
	}

	?>
	<!-- Schema.org JSON-LD -->
	<script type="application/ld+json">
	<?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
	</script>
	<?php
}
add_action( 'wp_head', 'thesource_child_schema_markup', 6 );
