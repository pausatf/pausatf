<?php get_header(); ?>

<?php if (get_option('thesource_recent_fromcat_display') == 'on') { ?>
	<div id="from-categories" class="clearfix">
		<?php $last = false; ?>
		<?php $headingColor = 'orange'; ?>
		<div class="recent-cat first">
			<?php $cat_option='thesource_home_cat_one'; ?>
			<?php query_posts("posts_per_page=1&cat=".get_catId(get_option($cat_option)));
				  while (have_posts()) : the_post(); ?>
					  <?php include(TEMPLATEPATH . '/includes/recent-cat.php'); ?>
			<?php endwhile; wp_reset_query(); ?>
		</div> <!-- end .recent-cat-first -->

		<?php $headingColor = 'green'; ?>
		<div class="recent-cat">
			<?php $cat_option='thesource_home_cat_two'; ?>
			<?php query_posts("posts_per_page=1&cat=".get_catId(get_option($cat_option)));
				  while (have_posts()) : the_post(); ?>
					   <?php include(TEMPLATEPATH . '/includes/recent-cat.php'); ?>
			<?php endwhile; wp_reset_query(); ?>
		</div> <!-- end .recent-cat -->

		<?php $headingColor = 'light-blue'; ?>
		<div class="recent-cat">
			<?php $cat_option='thesource_home_cat_three'; ?>
			<?php query_posts("posts_per_page=1&cat=".get_catId(get_option($cat_option)));
				  while (have_posts()) : the_post(); ?>
					   <?php include(TEMPLATEPATH . '/includes/recent-cat.php'); ?>
			<?php endwhile; wp_reset_query(); ?>
		</div> <!-- end .recent-cat -->

		<?php $headingColor = 'blue'; ?>
		<div class="recent-cat last">
		<?php $last = true; ?>
			<?php $cat_option='thesource_home_cat_four'; ?>
			<?php query_posts("posts_per_page=1&cat=".get_catId(get_option($cat_option)));
				  while (have_posts()) : the_post(); ?>
					   <?php include(TEMPLATEPATH . '/includes/recent-cat.php'); ?>
			<?php endwhile; wp_reset_query(); ?>
		</div> <!-- end .recent-cat -->
	</div> <!-- end #from-categories -->
<?php }; ?>

<div id="main-content-wrap">
	<div id="main-content" class="clearfix">
		<div id="recent-posts" class="clearfix">
			<h4 class="title"><?php esc_html_e('Recent Posts','TheSource'); ?></h4>
			<?php get_template_part('includes/entry'); ?>
		</div> <!-- end #recent-posts -->

		<?php get_sidebar(); ?>

<?php get_footer(); ?>