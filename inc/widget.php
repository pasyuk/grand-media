<?php

/**
 * Adds Gmedia Widgets.
 */

class GrandMedia_Gallery_Widget extends WP_Widget{

    /**
     * Register widget with WordPress.
     */
    function __construct(){
        parent::__construct('gmedia_gallery_widget', // Base ID
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
        if(empty($instance['term_id'])){
            global $gmDB;
            $term_id = $gmDB->get_terms('gmedia_gallery', array('status' => array('publish'), 'fields' => 'ids', 'number' => 1, 'orderby' => 'rand'));
            if(empty($term_id)){
                return;
            }
            $instance['term_id'] = $term_id[0];
        }

        echo $args['before_widget'];
        if(!empty($instance['title'])){
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $atts = array('id' => (int)$instance['term_id']);
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
        $gmedia_terms    = $gmDB->get_terms('gmedia_gallery', array('status' => array('publish', 'private'), 'orderby' => 'name', 'order' => 'ASC'));
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Title:', 'grand-media'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Choose Gallery', 'grand-media'); ?>:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('term_id')); ?>" name="<?php echo esc_attr($this->get_field_name('term_id')); ?>">
                <option value=""><?php _e('Random Gallery with status "publish"', 'grand-media'); ?></option>
                <?php
                foreach($gmedia_terms as &$item){
                    gmedia_gallery_more_data($item);
                    $selected = $instance['term_id']? selected($instance['term_id'], $item->term_id, false) : '';
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
        $instance['term_id'] = (!empty($new_instance['term_id']))? (int)$new_instance['term_id'] : '';

        return $instance;
    }

} // class GrandMedia_Gallery_Widget

class GrandMedia_Album_Widget extends WP_Widget{

    /**
     * Register widget with WordPress.
     */
    function __construct(){
        parent::__construct('gmedia_album_widget', // Base ID
                            esc_html__('Gmedia Album', 'grand-media'), // Name
                            array('description' => esc_html__('Display Gmedia Album in the widget', 'grand-media'),) // Args
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
        if(empty($instance['term_id'])){
            global $gmDB;
            $term_id = $gmDB->get_terms('gmedia_album', array('status' => array('publish'), 'fields' => 'ids', 'number' => 1, 'orderby' => 'rand', 'hide_empty' => true));
            if(empty($term_id)){
                return;
            }
            $instance['term_id'] = $term_id[0];
        }

        echo $args['before_widget'];
        if(!empty($instance['title'])){
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $atts = array('id' => (int)$instance['term_id']);
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
        $gmedia_terms    = $gmDB->get_terms('gmedia_album', array('status' => array('publish', 'private'), 'orderby' => 'name', 'order' => 'ASC'));
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Title:', 'grand-media'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Choose Album', 'grand-media'); ?>:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('term_id')); ?>" name="<?php echo esc_attr($this->get_field_name('term_id')); ?>">
                <option value=""><?php _e('Random Album with status "publish"', 'grand-media'); ?></option>
                <?php
                foreach($gmedia_terms as &$item){
                    gmedia_term_item_more_data($item);
                    $selected = $instance['term_id']? selected($instance['term_id'], $item->term_id, false) : '';
                    echo "<option value='{$item->term_id}' {$selected}>{$item->name} ({$item->count}) [{$item->status}] " . ($item->author_name? sprintf(__('by %s', 'grand-media'), $item->author_name) : '(' . __('deleted author', 'grand-media') . ')') . '</option>';
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
        $instance['term_id'] = (!empty($new_instance['term_id']))? (int)$new_instance['term_id'] : '';

        return $instance;
    }

} // class GrandMedia_Album_Widget