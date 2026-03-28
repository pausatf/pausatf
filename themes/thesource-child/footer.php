<?php $fullWidthPage = is_page_template('page-full.php'); ?>

			</div> <!-- end #main-content -->
		</div> <!-- end #main-content-wrap -->
	</div> 	<!-- end .container -->
</div> <!-- end #content -->
	</main> <!-- end #main-content-area -->

<div id="content-bottom">
	<div class="container<?php if ($fullWidthPage) echo(' nobg'); ?>"></div>
</div>

<div id="footer">
	<div class="container clearfix">

		<?php if ( !dynamic_sidebar('Footer') ) : ?>
		<?php endif; ?>
		<div class="clear"></div>

	</div> <!--end .container -->
</div> <!-- end #footer -->

<div id="footer-bottom">
	<div class="container clearfix">
		<?php global $is_footer;
		$is_footer = true; ?>

		<?php wp_nav_menu( array( 'theme_location' => 'footer-menu', 'container' => '', 'fallback_cb' => false, 'menu_class' => 'bottom-nav', 'depth' => 1 ) ); ?>

		<p id="copyright"><?php esc_html_e('Designed by ','TheSource'); ?> <a href="https://www.smd-designs.com" target="_blank" rel="noopener noreferrer" title="SMDdesigns">SMDdesigns</a> | <?php esc_html_e('Powered by ','TheSource'); ?> <a href="https://www.wordpress.org" target="_blank" rel="noopener noreferrer">WordPress</a></p>
	</div> <!--end .container -->
</div> <!-- end #footer-bottom -->


	<?php get_template_part('includes/scripts'); ?>

	<?php wp_footer(); ?>
</body>
</html>
