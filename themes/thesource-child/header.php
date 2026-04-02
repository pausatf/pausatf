<?php global $default_colorscheme, $shortname, $category_menu, $exclude_pages, $exclude_cats, $hide, $strdepth, $strdepth2, $page_menu; ?>

<?php $colorSchemePath = '';

	  $colorScheme = get_option($shortname . '_color_scheme');

      if ($colorScheme != $default_colorscheme) $colorSchemePath = strtolower($colorScheme) . '/'; ?>

<!DOCTYPE html>

<html <?php language_attributes(); ?>>

<head>

<meta charset="<?php bloginfo('charset'); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
















<script>

	document.documentElement.className = 'js';

</script>




<?php wp_head(); ?>



</head>

<body<?php if (is_home()) echo(' id="home"'); ?> <?php body_class(); ?>>

	<div id="header-top" class="clearfix">

		<div class="container clearfix">

			<!-- Start Logo -->

			<?php  $colorFolder = '';

			if ( $colorScheme == 'Light' || $colorScheme == 'Red' || $colorScheme == 'Blue') $colorFolder = $colorSchemePath; ?>



			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">

				<?php $logo = (get_option('thesource_logo') != '') ? get_option('thesource_logo') : get_template_directory_uri().'/images/'.$colorFolder.'logo.png'; ?>

				<img src="<?php echo esc_attr( $logo ); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" id="logo" width="1024" height="96" fetchpriority="high"/>

			</a>

			<p id="slogan"><?php bloginfo('description'); ?></p>

			<!-- End Logo -->



			<!-- Start Page-menu -->

			<div id="page-menu">

				<div id="p-menu-left"> </div>

				<div id="p-menu-content">



					<?php wp_nav_menu( array( 'theme_location' => 'primary-menu', 'container' => '', 'fallback_cb' => false, 'menu_class' => 'nav clearfix' ) ); ?>



				</div>

				<div id="p-menu-right"> </div>

			</div>	<!-- end #page-menu -->

			<!-- End Page-menu -->



			<div id="cat-nav" class="clearfix">

				<div id="cat-nav-left"> </div>

				<div id="cat-nav-content">



					<?php wp_nav_menu( array( 'theme_location' => 'secondary-menu', 'container' => '', 'fallback_cb' => false, 'menu_class' => 'superfish nav clearfix' ) ); ?>



					<!-- Start Searchbox -->

					<div id="search-form">

						<form method="get" id="searchform1" action="<?php echo esc_url( home_url( '/' ) ); ?>">

							<label for="searchinput" class="screen-reader-text">Search</label>
							<input type="text" value="<?php esc_attr_e('search...','TheSource'); ?>" name="s" id="searchinput" />



							<input type="image" src="<?php echo get_template_directory_uri(); ?>/images/<?php echo esc_attr($colorSchemePath); ?>search_btn.png" id="searchsubmit" alt="Search" />

						</form>

					</div>

				<!-- End Searchbox -->



				</div> <!-- end #cat-nav-content -->

				<div id="cat-nav-right"> </div>

			</div>	<!-- end #cat-nav -->

		</div> 	<!-- end .container -->

	</div> 	<!-- end #header-top -->







	<?php if ( (is_home() || is_front_page()) && get_option('thesource_featured') == 'on' ) get_template_part('includes/featured'); ?>



	<main id="main-content-area">
	<div id="content">

		<?php if (!is_home()) { ?>

			<div id="content-top-shadow"></div>

		<?php }; ?>

		<div class="container">
