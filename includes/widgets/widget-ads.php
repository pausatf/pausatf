<?php
class AdvWidget extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname'   => 'adv_widget',
            'description' => 'Displays advanced ad content',
        );
        parent::__construct(
            'adv_widget',                  // Widget ID
            'ET Advanced Ads Widget',      // Widget Name
            $widget_ops
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? 'Ad Box' );
        $ad_content = $instance['ad_content'] ?? '';

        echo $args['before_widget'];

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo '<div class="adv-widget-content">';
        echo wp_kses_post( $ad_content );
        echo '</div>';

        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['ad_content'] = current_user_can('unfiltered_html')
            ? $new_instance['ad_content']
            : stripslashes( wp_filter_post_kses( addslashes( $new_instance['ad_content'] ) ) );
        return $instance;
    }

    public function form( $instance ) {
        $title = esc_attr( $instance['title'] ?? 'Ad Box' );
        $ad_content = esc_textarea( $instance['ad_content'] ?? '' );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('ad_content'); ?>">Ad HTML:</label>
            <textarea class="widefat"
                      rows="10"
                      id="<?php echo $this->get_field_id('ad_content'); ?>"
                      name="<?php echo $this->get_field_name('ad_content'); ?>"><?php echo $ad_content; ?></textarea>
        </p>
        <?php
    }
}

function AdvWidgetInit() {
    register_widget('AdvWidget');
}

add_action('widgets_init', 'AdvWidgetInit');

