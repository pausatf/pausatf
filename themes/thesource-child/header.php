<?php global $default_colorscheme, $shortname, $category_menu, $exclude_pages, $exclude_cats, $hide, $strdepth, $strdepth2, $page_menu; ?>

<?php $colorSchemePath = '';

	  $colorScheme = get_option($shortname . '_color_scheme');

      if ($colorScheme <> $default_colorscheme) $colorSchemePath = strtolower($colorScheme) . '/'; ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>

<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

<title><?php elegant_titles(); ?></title>

<?php elegant_description(); ?>

<?php elegant_keywords(); ?>

<?php elegant_canonical(); ?>



<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />

<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />



<!--[if lt IE 7]>

	<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie6style.css" />

	<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/js/DD_belatedPNG_0.0.8a-min.js"></script>

	<script type="text/javascript">DD_belatedPNG.fix('img#logo, #cat-nav-left, #cat-nav-right, #search-form, #cat-nav-content, div.top-overlay, .slide .description, div.overlay, a#prevlink, a#nextlink, .slide a.readmore, .slide a.readmore span, .recent-cat .entry .title, #recent-posts .entry p.date, .footer-widget ul li, #tabbed-area ul#tab_controls li span');</script>

<![endif]-->

<!--[if IE 7]>

	<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie7style.css" />

<![endif]-->

<!--[if IE 8]>

	<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie8style.css" />

<![endif]-->



<script type="text/javascript">

	document.documentElement.className = 'js';

</script>



<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>

<?php wp_head(); ?>



</head>

<body<?php if (is_home()) echo(' id="home"'); ?> <?php body_class(); ?>>

	<div id="header-top" class="clearfix">

		<div class="container clearfix">

			<!-- Start Logo -->

			<?php  $colorFolder = '';

			if ( $colorScheme == 'Light' || $colorScheme == 'Red' || $colorScheme == 'Blue') $colorFolder = $colorSchemePath; ?>



			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">

				<?php $logo = (get_option('thesource_logo') <> '') ? get_option('thesource_logo') : get_template_directory_uri().'/images/'.$colorFolder.'logo.png'; ?>

				<img src="<?php echo esc_attr( $logo ); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" id="logo"/>

			</a>

			<p id="slogan"><?php bloginfo('description'); ?></p>

			<!-- End Logo -->



			<!-- Start Page-menu -->

			<div id="page-menu">

				<div id="p-menu-left"> </div>

				<div id="p-menu-content">



					<?php $menuClass = 'nav clearfix';

					$primaryNav = '';



					if (function_exists('wp_nav_menu')) $primaryNav = wp_nav_menu( array( 'theme_location' => 'primary-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => $menuClass, 'echo' => false ) );

					if ($primaryNav == '') show_page_menu($menuClass);

					else echo($primaryNav); ?>



				</div>

				<div id="p-menu-right"> </div>

			</div>	<!-- end #page-menu -->

			<!-- End Page-menu -->



			<div id="cat-nav" class="clearfix">

				<div id="cat-nav-left"> </div>

				<div id="cat-nav-content">



					<?php $menuClass = 'superfish nav clearfix';

					$secondaryNav = '';



					if (function_exists('wp_nav_menu')) $secondaryNav = wp_nav_menu( array( 'theme_location' => 'secondary-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => $menuClass, 'echo' => false ) );

					if ($secondaryNav == '') show_categories_menu($menuClass);

					else echo($secondaryNav); ?>



					<!-- Start Searchbox -->

					<div id="search-form">

						<form method="get" id="searchform1" action="<?php echo esc_url( home_url( '/' ) ); ?>">

							<input type="text" value="<?php esc_attr_e('search...','TheSource'); ?>" name="s" id="searchinput" />



							<input type="image" src="<?php echo get_template_directory_uri(); ?>/images/<?php echo esc_attr($colorSchemePath); ?>search_btn.png" id="searchsubmit" />

						</form>

					</div>

				<!-- End Searchbox -->



<!-- Start Social Icons -->
<style>
.social-icon-link { display:inline-block; }
.social-icon-link svg { width:28px; height:28px; fill:#888; transition:fill 0.2s; vertical-align:middle; }
.social-icon-link:hover svg { fill:#555; }
</style>
<?php
$social_links = [
	'facebook'  => [
		'url'   => get_theme_mod( 'pausatf_social_facebook',  'https://www.facebook.com/pausatf' ),
		'label' => 'Facebook',
		'svg'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22 12a10 10 0 1 0-11.563 9.873v-6.988H7.898V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.885h-2.33v6.988A10.003 10.003 0 0 0 22 12z"/></svg>',
	],
	'instagram' => [
		'url'   => get_theme_mod( 'pausatf_social_instagram', 'https://www.instagram.com/usatfpacificassoc/' ),
		'label' => 'Instagram',
		'svg'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
	],
	'youtube'   => [
		'url'   => get_theme_mod( 'pausatf_social_youtube', 'https://www.youtube.com/channel/UC4UDU5_ALy26O1vU6rAOjjA' ),
		'label' => 'YouTube',
		'svg'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
	],
];
foreach ( array_reverse( $social_links ) as $network => $opts ) :
	if ( empty( $opts['url'] ) ) continue;
?>
<div style="float:right; margin: 12px 10px 0 0;">
	<a href="<?php echo esc_url( $opts['url'] ); ?>" class="social-icon-link social-icon-<?php echo esc_attr( $network ); ?>"
	   target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $opts['label'] ); ?>">
		<?php echo $opts['svg']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
</div>
<?php endforeach; ?>
<!-- End Social Icons -->


				</div> <!-- end #cat-nav-content -->

				<div id="cat-nav-right"> </div>

			</div>	<!-- end #cat-nav -->

		</div> 	<!-- end .container -->

	</div> 	<!-- end #header-top -->







	<?php if ( (is_home() || is_front_page()) && get_option('thesource_featured') == 'on' ) get_template_part('includes/featured'); ?>



	<div id="content">

		<?php if (!is_home()) { ?>

			<div id="content-top-shadow"></div>

		<?php }; ?>

		<div class="container">