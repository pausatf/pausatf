<?php
class AdsenseWidget extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'classname'   => 'adsense_widget',
			'description' => 'Displays Adsense Ads',
		);
		parent::__construct(
			'adsense_widget',            // Widget ID
			'ET Adsense Widget',         // Widget name
			$widget_ops
		);
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? 'Adsense' : esc_html( $instance['title'] ) );
		$adsenseCode = empty( $instance['adsenseCode'] ) ? '' : $instance['adsenseCode'];

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		?>
		<div style="overflow: hidden;">
			<?php echo $adsenseCode; ?>
			<div class="clearfix"></div>
		</div>
		<?php
		echo $args['after_widget'];
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['adsenseCode'] = current_user_can('unfiltered_html')
			? $new_instance['adsenseCode']
			: stripslashes( wp_filter_post_kses( addslashes( $new_instance['adsenseCode'] ) ) );
		return $instance;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Adsense', 'adsenseCode' => '' ) );
		$title = esc_attr( $instance['title'] );
		$adsenseCode = esc_textarea( $instance['adsenseCode'] );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
				   name="<?php echo $this->get_field_name('title'); ?>" type="text"
				   value="<?php echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('adsenseCode'); ?>">Adsense Code:</label>
			<textarea cols="20" rows="12" class="widefat"
				id="<?php echo $this->get_field_id('adsenseCode'); ?>"
				name="<?php echo $this->get_field_name('adsenseCode'); ?>"><?php echo $adsenseCode; ?></textarea>
		</p>
		<?php
	}
}

function AdsenseWidgetInit() {
	register_widget('AdsenseWidget');
}

add_action('widgets_init', 'AdsenseWidgetInit');

