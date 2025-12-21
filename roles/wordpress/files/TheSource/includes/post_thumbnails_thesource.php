<?php
	add_theme_support( 'post-thumbnails' );

	global $et_theme_image_sizes;

	$et_thumb_size_format = '%dx%d';

	$et_post_thumb = sprintf( $et_thumb_size_format,
		intval( get_option( 'thesource_thumbnail_width_posts', 140 ) ),
		intval( get_option( 'thesource_thumbnail_height_posts', 140 ) )
	);

	$et_page_thumb = sprintf( $et_thumb_size_format,
		intval( get_option( 'thesource_thumbnail_width_pages', 140 ) ),
		intval( get_option( 'thesource_thumbnail_height_pages', 140 ) )
	);

	$et_usual_thumb = sprintf( $et_thumb_size_format,
		intval( get_option( 'thesource_thumbnail_width_usual', 140 ) ),
		intval( get_option( 'thesource_thumbnail_height_usual', 140 ) )
	);

	$et_theme_image_sizes = array(
		$et_post_thumb	=> 'et-posts-thumb',
		$et_page_thumb	=> 'et-pages-thumb',
		$et_usual_thumb	=> 'et-usual-thumb',
		'1400x300' 	=> 'et-featured-thumb',
		'239x133' 	=> 'et-recent-cat-thumb',
		'74x74' 	=> 'et-custom-post-thumb',
	);

	$et_page_templates_image_sizes = array(
		'184x184' 	=> 'et-blog-page-thumb',
		'207x136' 	=> 'et-gallery-page-thumb',
		'260x170' 	=> 'et-portfolio-medium-page-thumb',
		'260x315' 	=> 'et-portfolio-medium-portrait-page-thumb',
		'140x94' 	=> 'et-portfolio-small-page-thumb',
		'140x170' 	=> 'et-portfolio-small-portrait-page-thumb',
		'430x283' 	=> 'et-portfolio-large-page-thumb',
		'430x860' 	=> 'et-portfolio-large-portrait-page-thumb',
	);

	$et_theme_image_sizes = array_merge( $et_theme_image_sizes, $et_page_templates_image_sizes );

	$et_theme_image_sizes = apply_filters( 'et_theme_image_sizes', $et_theme_image_sizes );
	$crop = apply_filters( 'et_post_thumbnails_crop', true );

	if ( is_array( $et_theme_image_sizes ) ){
		foreach ( $et_theme_image_sizes as $image_size_dimensions => $image_size_name ){
			$dimensions = explode( 'x', $image_size_dimensions );
			add_image_size( $image_size_name, $dimensions[0], $dimensions[1], $crop );
		}
	}
?>