<?php get_header(); ?>

	<div id="main-content-wrap">
		<div id="main-content" class="clearfix">
			<?php get_template_part('includes/breadcrumb'); ?>
			<div id="top-shadow"></div>

			<div id="recent-posts" class="clearfix">
				<div class="entry post clearfix">
					<h1 class="title"><?php esc_html_e('No Results Found','TheSource'); ?></h1>
					<div class="entry-content">
						<p><?php esc_html_e('The page you requested could not be found. Try refining your search, or use the navigation above to locate the post.','TheSource'); ?></p>
					</div> <!-- end .entry-content -->
				</div> <!-- end .entry -->
			</div> <!-- end #recent-posts -->

	<?php get_sidebar(); ?>
<?php get_footer(); ?>