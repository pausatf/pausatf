<?php
class TabbedWidget extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname'   => 'tabbed_widget',
            'description' => 'Displays tabbed Recent, Popular, and Comments.',
        );
        parent::__construct(
            'tabbed_widget',
            'ET Tabbed Widget',
            $widget_ops
        );
    }

    public function widget( $args, $instance ) {
        $title         = apply_filters( 'widget_title', $instance['title'] ?? 'Tabbed Content' );
        $show_recent   = ! empty( $instance['show_recent'] );
        $show_popular  = ! empty( $instance['show_popular'] );
        $show_comments = ! empty( $instance['show_comments'] );

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo '<div class="tabbed-widget"><ul class="tabs">';
        if ( $show_recent )   echo '<li class="tab-link active" data-tab="tab-recent">Recent</li>';
        if ( $show_popular )  echo '<li class="tab-link" data-tab="tab-popular">Popular</li>';
        if ( $show_comments ) echo '<li class="tab-link" data-tab="tab-comments">Comments</li>';
        echo '</ul><div class="tab-content-wrapper">';

        if ( $show_recent ) {
            echo '<div id="tab-recent" class="tab-content active">';
            $recent = new WP_Query(array(
                'posts_per_page' => 5,
                'post_status' => 'publish'
            ));
            while ( $recent->have_posts() ) {
                $recent->the_post();
                echo '<p><a href="' . esc_url( get_permalink() ) . '">' . get_the_title() . '</a></p>';
            }
            wp_reset_postdata();
            echo '</div>';
        }

        if ( $show_popular ) {
            echo '<div id="tab-popular" class="tab-content">';
            $popular = new WP_Query(array(
                'posts_per_page' => 5,
                'orderby' => 'comment_count',
                'post_status' => 'publish'
            ));
            while ( $popular->have_posts() ) {
                $popular->the_post();
                echo '<p><a href="' . esc_url( get_permalink() ) . '">' . get_the_title() . '</a></p>';
            }
            wp_reset_postdata();
            echo '</div>';
        }

        if ( $show_comments ) {
            echo '<div id="tab-comments" class="tab-content">';
            $comments = get_comments(array(
                'number' => 5,
                'status' => 'approve'
            ));
            foreach ( $comments as $comment ) {
                echo '<p>' . esc_html( $comment->comment_author ) . ': ' .
                     wp_trim_words( $comment->comment_content, 10 ) . '</p>';
            }
            echo '</div>';
        }

        echo '</div></div>';
        echo $args['after_widget'];
    }

    public function update( $new_instance, $old_instance ) {
        return array(
            'title'         => sanitize_text_field( $new_instance['title'] ),
            'show_recent'   => ! empty( $new_instance['show_recent'] ),
            'show_popular'  => ! empty( $new_instance['show_popular'] ),
            'show_comments' => ! empty( $new_instance['show_comments'] ),
        );
    }

    public function form( $instance ) {
        $title         = esc_attr( $instance['title'] ?? 'Tabbed Content' );
        $show_recent   = ! empty( $instance['show_recent'] );
        $show_popular  = ! empty( $instance['show_popular'] );
        $show_comments = ! empty( $instance['show_comments'] );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <input class="checkbox" type="checkbox"
                   <?php checked( $show_recent ); ?>
                   id="<?php echo $this->get_field_id('show_recent'); ?>"
                   name="<?php echo $this->get_field_name('show_recent'); ?>" />
            <label for="<?php echo $this->get_field_id('show_recent'); ?>">Show Recent Posts</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox"
                   <?php checked( $show_popular ); ?>
                   id="<?php echo $this->get_field_id('show_popular'); ?>"
                   name="<?php echo $this->get_field_name('show_popular'); ?>" />
            <label for="<?php echo $this->get_field_id('show_popular'); ?>">Show Popular Posts</label>
        </p>
        <p>
            <input class="checkbox" type="checkbox"
                   <?php checked( $show_comments ); ?>
                   id="<?php echo $this->get_field_id('show_comments'); ?>"
                   name="<?php echo $this->get_field_name('show_comments'); ?>" />
            <label for="<?php echo $this->get_field_id('show_comments'); ?>">Show Comments</label>
        </p>
        <?php
    }
}

function TabbedWidgetInit() {
    register_widget('TabbedWidget');
}

add_action('widgets_init', 'TabbedWidgetInit');

