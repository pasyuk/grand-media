<?php

/**
 * Adds GrandMedia_Widget widget.
 */
class GrandMedia_Widget extends WP_Widget{

    /**
     * Register widget with WordPress.
     */
    function __construct(){
        parent::__construct('gmedia_widget', // Base ID
                            esc_html__('Gmedia Gallery', 'grand-media'), // Name
                            array('description' => esc_html__('Display Gmedia Gallery in the widget', 'grand-media'),) // Args
        );
    }

    /**
     * Front-end display of widget.
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance){
        echo $args['before_widget'];
        if(!empty($instance['title'])){
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        if(empty($instance['gallery'])){
            global $gmDB;
            $args            = array();
            $args['status']  = array('publish');
            $args['get']     = 'ids';
            $args['number']  = 1;
            $args['orderby'] = 'rand';
            $term_id         = $gmDB->get_terms('gmedia_gallery', $args);
            if(!empty($term_id)){
                $instance['gallery'] = $term_id[0];
            }
        }

        $atts    = array('id' => (int)$instance['gallery']);
        echo gmedia_shortcode($atts);

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     *
     * @return string|void
     */
    public function form($instance){
        global $gmDB;
        $title = !empty($instance['title'])? $instance['title'] : esc_html__('New title', 'grand-media');

        $args            = array();
        $args['status']  = array('publish', 'private');
        $args['orderby'] = 'name';
        $args['order']   = 'ASC';
        $gmedia_terms    = $gmDB->get_terms('gmedia_gallery', $args);

        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Title:', 'grand-media'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Choose Gallery', 'grand-media'); ?>:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('gallery')); ?>" name="<?php echo esc_attr($this->get_field_name('gallery')); ?>">
                <option value=""><?php _e('Random Gallery with status "publish"', 'grand-media'); ?></option>
                <?php
                foreach($gmedia_terms as &$item){
                    gmedia_gallery_more_data($item);
                    $selected = $instance['gallery']? selected($instance['gallery'], $item->term_id, false) : '';
                    echo "<option value='{$item->term_id}' {$selected}>{$item->name} [{$item->status}] " . ($item->author_name? sprintf(__('by %s', 'grand-media'), $item->author_name) : '(' . __('deleted author', 'grand-media') . ')') . '</option>';
                }
                ?>
            </select>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance){
        $instance            = array();
        $instance['title']   = (!empty($new_instance['title']))? strip_tags($new_instance['title']) : '';
        $instance['gallery'] = (!empty($new_instance['gallery']))? (int)$new_instance['gallery'] : '';

        return $instance;
    }

} // class GrandMedia_Widget