<?php
class PopularWidget extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname'   => 'popular_widget',
            'description' => 'Displays the most popular posts.',
        );
        parent::__construct(
            'popular_widget',            // Widget ID
            'ET Popular Posts',          // Widget name
            $widget_ops
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] ?? 'Popular Posts' );
        $num_posts = ! empty( $instance['num_posts'] ) ? absint( $instance['num_posts'] ) : 5;

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $popular = new WP_Query(array(
            'posts_per_page' => $num_posts,
            'orderby'        => 'comment_count',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'ignore_sticky_posts' => true,
        ));

        if ( $popular->have_posts() ) {
            echo '<ul class="popular-posts">';
            while ( $popular->have_posts() ) {
                $popular->the_post();
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
        $title = esc_attr( $instance['title'] ?? 'Popular Posts' );
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

function PopularWidgetInit() {
    register_widget('PopularWidget');
}

add_action('widgets_init', 'PopularWidgetInit');

