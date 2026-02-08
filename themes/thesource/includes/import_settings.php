<?php
add_action( 'admin_enqueue_scripts', 'import_epanel_javascript' );
function import_epanel_javascript( $hook_suffix ) {
	if ( 'admin.php' == $hook_suffix && isset( $_GET['import'] ) && isset( $_GET['step'] ) && 'wordpress' == $_GET['import'] && '1' == $_GET['step'] )
		add_action( 'admin_head', 'admin_headhook' );
}

function admin_headhook(){ ?>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$("p.submit").before("<p><input type='checkbox' id='importepanel' name='importepanel' value='1' style='margin-right: 5px;'><label for='importepanel'>Replace ePanel settings with sample data values</label></p>");
		});
	</script>
<?php }

add_action('import_end','importend');
function importend(){
	global $wpdb, $shortname;

	#make custom fields image paths point to sampledata/sample_images folder
	$sample_images_postmeta = $wpdb->get_results(
		$wpdb->prepare( "SELECT meta_id, meta_value FROM $wpdb->postmeta WHERE meta_value REGEXP %s", 'http://et_sample_images.com' )
	);
	if ( $sample_images_postmeta ) {
		foreach ( $sample_images_postmeta as $postmeta ){
			$template_dir = get_template_directory_uri();
			if ( is_multisite() ){
				switch_to_blog(1);
				$main_siteurl = site_url();
				restore_current_blog();

				$template_dir = $main_siteurl . '/wp-content/themes/' . get_template();
			}
			preg_match( '/http:\/\/et_sample_images.com\/([^.]+).jpg/', $postmeta->meta_value, $matches );
			$image_path = $matches[1];

			$local_image = preg_replace( '/http:\/\/et_sample_images.com\/([^.]+).jpg/', $template_dir . '/sampledata/sample_images/$1.jpg', $postmeta->meta_value );

			$local_image = preg_replace( '/s:55:/', 's:' . strlen( $template_dir . '/sampledata/sample_images/' . $image_path . '.jpg' ) . ':', $local_image );

			$wpdb->update( $wpdb->postmeta, array( 'meta_value' => esc_url_raw( $local_image ) ), array( 'meta_id' => $postmeta->meta_id ), array( '%s' ) );
		}
	}

	if ( !isset($_POST['importepanel']) )
		return;

	// Default import options stored as JSON for safety
	$importOptionsJson = '{"":"N","thesource_color_scheme":"Black","thesource_blog_style":"N","thesource_grab_image":"N","thesource_catnum_posts":"6","thesource_archivenum_posts":"5","thesource_searchnum_posts":"5","thesource_tagnum_posts":"5","thesource_date_format":"M j, Y","thesource_use_excerpt":"N","thesource_recent_fromcat_display":"on","thesource_home_cat_one":"Blog","thesource_home_cat_two":"Featured","thesource_home_cat_three":"Portfolio","thesource_home_cat_four":"Blog","thesource_homepage_posts":"8","thesource_exlcats_recent":"N","thesource_featured":"on","thesource_duplicate":"on","thesource_feat_cat":"Featured","thesource_featured_num":"3","thesource_slider_auto":"N","thesource_use_pages":"N","thesource_slider_autospeed":"5000","thesource_feat_pages":"N","thesource_menupages":["724"],"thesource_enable_dropdowns":"on","thesource_home_link":"on","thesource_sort_pages":"post_title","thesource_order_page":"asc","thesource_tiers_shown_pages":"3","thesource_menucats":["1"],"thesource_enable_dropdowns_categories":"on","thesource_categories_empty":"on","thesource_tiers_shown_categories":"3","thesource_sort_cat":"name","thesource_order_cat":"asc","thesource_disable_toptier":"N","thesource_postinfo2":["author","date","categories","comments"],"thesource_thumbnails":"on","thesource_show_postcomments":"on","thesource_thumbnail_width_posts":"140","thesource_thumbnail_height_posts":"140","thesource_page_thumbnails":"N","thesource_show_pagescomments":"N","thesource_thumbnail_width_pages":"140","thesource_thumbnail_height_pages":"140","thesource_postinfo1":["author","date","categories","comments"],"thesource_thumbnails_index":"on","thesource_thumbnail_width_usual":"140","thesource_thumbnail_height_usual":"140","thesource_custom_colors":"N","thesource_child_css":"N","thesource_child_cssurl":"","thesource_color_mainfont":"","thesource_color_mainlink":"","thesource_color_pagelink":"","thesource_color_pagelink_active":"","thesource_color_headings":"","thesource_color_sidebar_links":"","thesource_footer_text":"","thesource_color_footerlinks":"","thesource_seo_home_title":"N","thesource_seo_home_description":"N","thesource_seo_home_keywords":"N","thesource_seo_home_canonical":"N","thesource_seo_home_titletext":"","thesource_seo_home_descriptiontext":"","thesource_seo_home_keywordstext":"","thesource_seo_home_type":"BlogName | Blog description","thesource_seo_home_separate":" | ","thesource_seo_single_title":"N","thesource_seo_single_description":"N","thesource_seo_single_keywords":"N","thesource_seo_single_canonical":"N","thesource_seo_single_field_title":"seo_title","thesource_seo_single_field_description":"seo_description","thesource_seo_single_field_keywords":"seo_keywords","thesource_seo_single_type":"Post title | BlogName","thesource_seo_single_separate":" | ","thesource_seo_index_canonical":"N","thesource_seo_index_description":"N","thesource_seo_index_type":"Category name | BlogName","thesource_seo_index_separate":" | ","thesource_integrate_header_enable":"on","thesource_integrate_body_enable":"on","thesource_integrate_singletop_enable":"on","thesource_integrate_singlebottom_enable":"on","thesource_integration_head":"","thesource_integration_body":"","thesource_integration_single_top":"","thesource_integration_single_bottom":"","thesource_468_enable":"N","thesource_468_image":"","thesource_468_url":"","thesource_468_adsense":""}';

	// Use json_decode instead of unserialize for security
	$importedOptions = json_decode( $importOptionsJson, true );

	if ( is_array( $importedOptions ) ) {
		foreach ( $importedOptions as $key => $value ) {
			// Sanitize the key to ensure it's a valid option name
			$sanitized_key = sanitize_key( $key );

			// Only update if value is not empty and key is valid
			if ( $value !== '' && $value !== 'N' && ! empty( $sanitized_key ) ) {
				update_option( $sanitized_key, $value );
			}
		}
	}
	update_option( $shortname . '_use_pages', 'false' );
} ?>