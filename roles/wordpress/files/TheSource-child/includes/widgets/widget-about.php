<?php
/**
 * About Me Widget for TheSource Child Theme
 */

class AboutMeWidget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'about_me_widget', // Base ID
            __('About Me', 'text_domain'), // Name
            array( 'description' => __( 'A short About Me widget', 'text_domain' ) )
        );
    }

    // Front-end display
    function widget( $args, $instance ) {
        extract($args);
        $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? 'About Me' : esc_html( $instance['title'] ) );
        $imagePath = empty( $instance['imagePath'] ) ? '' : esc_url( $instance['imagePath'] );
        $aboutText = empty( $instance['aboutText'] ) ? '' : $instance['aboutText'];

        echo $before_widget;

        if ( $title )
            echo $before_title . $title . $after_title;
        ?>
        <div class="clearfix">
            <img src="<?php echo et_new_thumb_resize( et_multisite_thumbnail($imagePath), 74, 74, '', true ); ?>" id="about-image" alt="" />
            <?php echo wp_kses_post( $aboutText ); ?>
        </div>
        <?php
        echo $after_widget;
    }

    // Back-end widget form
    function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array(
            'title'     => 'About Me',
            'imagePath' => '',
            'aboutText' => ''
        ) );

        $title = esc_attr( $instance['title'] );
        $imagePath = esc_url( $instance['imagePath'] );
        $aboutText = esc_textarea( $instance['aboutText'] );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text"
                   value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('imagePath'); ?>">Image URL:</label>
            <textarea cols="20" rows="2" class="widefat"
                      id="<?php echo $this->get_field_id('imagePath'); ?>"
                      name="<?php echo $this->get_field_name('imagePath'); ?>"><?php echo $imagePath; ?></textarea>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('aboutText'); ?>">Text:</label>
            <textarea cols="20" rows="5" class="widefat"
                      id="<?php echo $this->get_field_id('aboutText'); ?>"
                      name="<?php echo $this->get_field_name('aboutText'); ?>"><?php echo $aboutText; ?></textarea>
        </p>
        <?php
    }

    // Save widget settings
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title']     = sanitize_text_field( $new_instance['title'] );
        $instance['imagePath'] = esc_url( $new_instance['imagePath'] );
        $instance['aboutText'] = current_user_can('unfiltered_html')
            ? $new_instance['aboutText']
            : stripslashes( wp_filter_post_kses( addslashes($new_instance['aboutText']) ) );
        return $instance;
    }
}

// Register the widget
function AboutMeWidgetInit() {
    register_widget('AboutMeWidget');
}
add_action('widgets_init', 'AboutMeWidgetInit');

