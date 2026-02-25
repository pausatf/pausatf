<?php global $default_colorscheme, $shortname, $category_menu, $exclude_pages, $exclude_cats, $hide, $strdepth, $strdepth2, $page_menu; ?>

<?php $colorSchemePath = '';

	  $colorScheme = get_option($shortname . '_color_scheme');

      if ($colorScheme <> $default_colorscheme) $colorSchemePath = strtolower($colorScheme) . '/'; ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="https://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="https://gmpg.org/xfn/11">

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



<!-- Social icons — inline SVG; no media library dependency -->
<style>.pausatf-social-icon{float:right;margin:10px 8px 0 0;opacity:.85;transition:opacity .15s}.pausatf-social-icon:hover{opacity:1}</style>

<!-- Instagram -->
<div class="pausatf-social-icon"><a href="https://www.instagram.com/usatfpacificassoc/" target="_blank" rel="noopener" aria-label="PAUSATF on Instagram"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" aria-hidden="true"><path fill="#E4405F" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a></div>

<!-- YouTube -->
<div class="pausatf-social-icon"><a href="https://www.youtube.com/channel/UC4UDU5_ALy26O1vU6rAOjjA" target="_blank" rel="noopener" aria-label="PAUSATF on YouTube"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" aria-hidden="true"><path fill="#FF0000" d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg></a></div>

<!-- Twitter / X -->
<div class="pausatf-social-icon"><a href="https://twitter.com/UsatfPacific" target="_blank" rel="noopener" aria-label="PAUSATF on X"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" aria-hidden="true"><path fill="#000" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.74l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a></div>

<!-- Facebook -->
<div class="pausatf-social-icon"><a href="https://www.facebook.com/pausatf" target="_blank" rel="noopener" aria-label="PAUSATF on Facebook"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" aria-hidden="true"><path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a></div>


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
