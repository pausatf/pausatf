<?php global $default_colorscheme, $shortname, $category_menu, $exclude_pages, $exclude_cats, $hide, $strdepth, $strdepth2, $page_menu; ?>

<?php $colorSchemePath = '';

	  $colorScheme = get_option($shortname . '_color_scheme');

      if ($colorScheme <> $default_colorscheme) $colorSchemePath = strtolower($colorScheme) . '/'; ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>

<meta charset="<?php bloginfo('charset'); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title><?php elegant_titles(); ?></title>

<?php elegant_description(); ?>

<?php elegant_keywords(); ?>

<?php elegant_canonical(); ?>



<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />

<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />






<script type="text/javascript">

	document.documentElement.className = 'js';

</script>



<?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>

<?php wp_head(); ?>



</head>

<body<?php if (is_home()) echo(' id="home"'); ?> <?php body_class(); ?>>

<a href="#content" class="skip-link"><?php esc_html_e( 'Skip to content', 'TheSource' ); ?></a>

	<header id="header-top" class="clearfix" role="banner">

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

			<nav id="page-menu" role="navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'TheSource' ); ?>">

				<div id="p-menu-left"> </div>

				<div id="p-menu-content">



					<?php $menuClass = 'nav clearfix';

					$primaryNav = '';



					if (function_exists('wp_nav_menu')) $primaryNav = wp_nav_menu( array( 'theme_location' => 'primary-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => $menuClass, 'echo' => false ) );

					if ($primaryNav == '') show_page_menu($menuClass);

					else echo($primaryNav); ?>



				</div>

				<div id="p-menu-right"> </div>

			</nav>	<!-- end #page-menu -->

			<!-- End Page-menu -->



			<nav id="cat-nav" class="clearfix" role="navigation" aria-label="<?php esc_attr_e( 'Category navigation', 'TheSource' ); ?>">

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



<!-- Social Media Icons -->
<nav class="social-icons" aria-label="Social media links">
	<a href="https://www.instagram.com/usatfpacificassoc/" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Instagram" class="social-icon social-icon--instagram">
		<img src="https://pausatf.org/wp-content/uploads/2021/05/InstagramLogoGray32.png" alt="Instagram" width="32" height="32" />
	</a>
	<a href="https://www.youtube.com/channel/UC4UDU5_ALy26O1vU6rAOjjA" target="_blank" rel="noopener noreferrer" aria-label="Subscribe on YouTube" class="social-icon social-icon--youtube">
		<img src="https://pausatf.org/wp-content/uploads/2014/06/YT.png" alt="YouTube" width="32" height="32" />
	</a>
	<a href="https://twitter.com/UsatfPacific" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Twitter" class="social-icon social-icon--twitter">
		<img src="https://pausatf.org/wp-content/uploads/2014/06/TW.png" alt="Twitter" width="32" height="32" />
	</a>
	<a href="https://www.facebook.com/pausatf" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Facebook" class="social-icon social-icon--facebook">
		<img src="https://pausatf.org/wp-content/uploads/2014/06/FB.png" alt="Facebook" width="32" height="32" />
	</a>
</nav>


				</div> <!-- end #cat-nav-content -->

				<div id="cat-nav-right"> </div>

			</nav>	<!-- end #cat-nav -->

		</div> 	<!-- end .container -->

	</header> 	<!-- end #header-top -->







	<?php if ( (is_home() || is_front_page()) && get_option('thesource_featured') == 'on' ) get_template_part('includes/featured'); ?>



	<main id="content" role="main">

		<?php if (!is_home()) { ?>

			<div id="content-top-shadow"></div>

		<?php }; ?>

		<div class="container">
