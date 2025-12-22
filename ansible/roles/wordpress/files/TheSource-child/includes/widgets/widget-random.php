<?php
class RandomWidget extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname'   => 'random_widget',
            'description' => 'Displays random posts',
        );
        parent::__construct(
            'random_widget',            // Widget ID
            'ET Random Posts',          // Widget name
            $widget_ops
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? 'Random Posts' );
        $num_posts = ! empty( $instance['num_posts'] ) ? absint( $instance['num_posts'] ) : 5;

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $random = new WP_Query(array(
            'posts_per_page' => $num_posts,
            'orderby'        => 'rand',
            'post_status'    => 'publish',
            'ignore_sticky_posts' => true,
        ));

        if ( $random->have_posts() ) {
            echo '<ul class="random-posts">';
            while ( $random->have_posts() ) {
                $random->the_post();
                echo '<li><a href="' . esc_url( get_permalink() ) . '">' . get_the_title() . '</a></li>';
            }
            echo '</ul>';
            wp_reset_postdata();
        }

        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['num_posts'] = absint( $new_instance['num_posts'] );
        return $instance;
    }

    public function form( $instance ) {
        $title = esc_attr( $instance['title'] ?? 'Random Posts' );
        $num_posts = esc_attr( $instance['num_posts'] ?? 5 );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('num_posts'); ?>">Number of posts to show:</label>
            <input class="tiny-text"
                   id="<?php echo $this->get_field_id('num_posts'); ?>"
                   name="<?php echo $this->get_field_name('num_posts'); ?>"
                   type="number" step="1" min="1"
                   value="<?php echo $num_posts; ?>" size="3" />
        </p>
        <?php
    }
}

function RandomWidgetInit() {
    register_widget('RandomWidget');
}

add_action('widgets_init', 'RandomWidgetInit');

