<?php
if(preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
    die('You are not allowed to call this page directly.');
}

/** *********************** **/
/** Shortcodes Declarations **/
/** *********************** **/
add_shortcode('gmedia', 'gmedia_shortcode');
add_shortcode('gm', 'gmedia_term_shortcode');

//add_filter('the_content', 'do_shortcode');
add_filter('the_content', 'get_gmedia_unformatted_shortcode_blocks', 4);

/** ******************************* **/
/** Shortcodes Functions and Markup **/
/** ******************************* **/
$gmedia_shortcode_instance = array();

/**
 * @param        $atts
 * @param string $content
 *
 * @return string
 */
function gmedia_term_shortcode($atts, $content = ''){
    /**
     * @var $id
     * @var $album
     * @var $tag
     * @var $category
     * @var $module
     * @var $preset
     */
    extract(shortcode_atts(array(
                               'id'       => 0,
                               'album'    => 0,
                               'category' => 0,
                               'tag'      => 0,
                               'module'   => '',
                               'preset'   => 0
                           ), $atts));
    if($album){
        $id = $album;
    } elseif($tag){
        $id = $tag;
    } elseif($category){
        $id = $category;
    }

    $sc_atts = array(
        'id'  => $id,
        'module' => $module,
        'preset' => $preset,
    );
    $out     = gmedia_shortcode($sc_atts, $content);

    return $out;
}

/**
 * @param        $atts
 * @param string $content
 *
 * @return string
 */
function gmedia_shortcode($atts, $content = ''){
    global $gmDB, $gmGallery, $gmCore;
    global $gmedia_shortcode_instance;

    /**
     * @var $id
     */
    extract(shortcode_atts(array('id' => ''), $atts, 'gmedia'));

    $shortcode_raw = (isset($gmGallery->options['shortcode_raw']) && '1' === $gmGallery->options['shortcode_raw']);
    if($shortcode_raw && !empty($atts['shortcode_raw'])){
        return $gmedia_shortcode_instance['shortcode_raw'][$atts['shortcode_raw']];
    }

    $userid = get_current_user_id();

    $query    = !empty($atts['query'])? $atts['query'] : array();
    $module   = !empty($atts['module'])? $atts['module'] : $gmGallery->options['default_gmedia_module'];
    $settings = array($module => array());

    if(!empty($id) && $gmCore->is_digit($id) && ($term = gmedia_shortcode_id_data($id))){
        if(
            ('publish' != $term->status && !$userid) ||
            ('draft' == $term->status && $userid != $term->global)
        ){
            return '';
        }
        $taxterm = str_replace('gmedia_', '', $term->taxonomy);
        if($taxterm == 'gallery'){
            if(!empty($term->meta['_query'])){
                $query = array_merge($query, $term->meta['_query']);
            }
        } else{
            $query = array_merge($query, array("{$taxterm}__in" => $term->term_id));
        }
        if(!empty($term->meta['_module'])){
            $module = $term->meta['_module'];
        }
        if(!empty($term->meta['_settings'][$module])){
            $settings = $term->meta['_settings'];
        }
    }

    $_module = $module;

    if($userid && current_user_can('gmedia_gallery_manage') && ($preview_module = $gmCore->_get('gmedia_module'))){
        $module = $preview_module;
    }

    $module  = $gmCore->get_module_path($module);
    if(
        !$module ||
        !file_exists($module['path'] . '/index.php') ||
        !file_exists($module['path'] . '/settings.php')
    ){
        return '<div class="gmedia_gallery gmediaShortcodeError" data-id="' . $id . '" data-error="' . $_module . ': folder missed or module broken">' . $content . '</div>';
    }

    if($_module != $module['name']){
        $_module  = $module['name'];
        $settings = array($_module => array());
    }

    if(empty($id)){
        $string2hash = json_encode(array( $_module => $query ));
        $id          = wp_hash($string2hash);
    }

    include($module['path'] . '/index.php');
    include($module['path'] . '/settings.php');

    $module['info']    = isset($module_info)? $module_info : array('dependencies' => '');
    $module['options'] = isset($default_options)? $default_options : array();

    if(!empty($atts['preset'])){
        $preset = $gmDB->get_term($atts['preset'], 'gmedia_module');
        if(!empty($preset) && !is_wp_error($preset) && ($module['name'] == $preset->status)){
            $settings = array($module['name'] => maybe_unserialize($preset->description));
        }
    }

    $settings = $gmCore->array_diff_keyval_recursive($settings[$module['name']], $module['options'], false);

    $protected_query_args = array('status' => array('publish'));
    if($userid){
        $protected_query_args['status'][] = 'private';
    }

    $query = array_merge(apply_filters('gmedia_shortcode_query', $query, $id), $protected_query_args);

    $gmGallery->do_module[$_module] = $module;
    $gmGallery->shortcode           = compact('id', 'query', 'module', 'settings', 'term');

    $customCSS = (isset($settings['customCSS']) && ('' != trim($settings['customCSS'])))? $settings['customCSS'] : '';
    unset($settings['customCSS']);

    if(!empty($term) && empty($module['info']['branch'])){
        $query = array($id => $query);
        if(in_array($_module, array('afflux', 'afflux-mod', 'cube', 'flatwall', 'green-style', 'minima', 'optima', 'photo-blog', 'photo-pro', 'slider', 'sphere'))){
            $_query = array_merge($query[$id], array('album__status' => $protected_query_args['status']));
            $gmDB->gmedias_album_stuff($_query);
            if(!empty($_query['album__in']) && empty($_query['album__not_in'])){
                $album__in = wp_parse_id_list($_query['album__in']);
                foreach($_query as $key => $q){
                    if('alb' == substr($key, 0, 3)){
                        unset($_query[$key]);
                    }
                }
                foreach($album__in as $alb){
                    $album = $gmDB->get_term($alb);
                    if(empty($album) || is_wp_error($album) || $album->count == 0){
                        continue;
                    }
                    $terms[$alb]     = $album;
                    $new_query[$alb] = array_merge($_query, array('album__in' => $alb));
                }
                if(!empty($new_query)){
                    $query = $new_query;
                }
            }
        }
        if(empty($terms)){
            $terms = array($id => $term);
        }

        $gmedia = array();
        foreach($query as $term_id => $args){
            if(isset($args['album__in'])){
                $alb_ids = wp_parse_id_list($args['album__in']);
                if(1 == count($alb_ids)) {
                    $album_meta        = $gmDB->get_metadata('gmedia_term', $alb_ids[0]);
                    $album_query_order = array(
                        'orderby' => !empty($album_meta['_orderby'][0])? $album_meta['_orderby'][0] : $gmGallery->options['in_album_orderby'],
                        'order'   => !empty($album_meta['_order'][0])? $album_meta['_order'][0] : $gmGallery->options['in_album_order']
                    );
                    $args = array_merge($album_query_order, $args);
                }
            }
            $gmedia[$term_id] = $gmDB->get_gmedias($args);
        }

        if(empty($gmedia)){
            return '<div class="gmedia_gallery gmedia_gallery_empty">' . __('Gallery is empty') . '<br />' . $content . '</div>';
        }
        $gallery = (array) $term;
    }


    $is_bot = false;
    if(!($is_mob = wp_is_mobile())){
        $is_bot = $gmCore->is_bot();
    }
    $sc_classes = "gmedia_gallery {$_module}_module";
    if($is_bot){
        $sc_classes .= " is_bot";
    }
    if($is_mob){
        $sc_classes .= " is_mobile";
    }
    $sc_id = str_replace(' ', '_', "GmediaGallery_{$id}");

    do_action('pre_gmedia_shortcode');

    $out = '<div class="' . $sc_classes . '" id="' . $sc_id . '" data-gallery="' . esc_attr($id) . '" data-module="' . $_module . '">';
    $out .= $content;

    ob_start();
    /** @noinspection PhpIncludeInspection */
    include($module['path'] . '/init.php');
    $module_content = ob_get_contents();
    ob_end_clean();

    if($customCSS){
        $out .= "<style type='text/css' scoped='scoped'>/**** Custom CSS {$_module} #{$id} ****/{$customCSS}</style>";
    }

    $out .= $module_content;
    $out .= '</div>';

    do_action('gmedia_shortcode');

    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
        do_action('gmedia_footer_scripts');
    }

    return $out;

}

/**
 * Process the [gmedia _raw] shortcode in priority 4.
 *
 * Since the gmedia raw shortcode needs to be run earlier than other shortcodes,
 * this function removes all existing shortcodes, uses the shortcode parser to grab all [gmedia blocks],
 * calls {@link do_shortcode()}, and then re-registers the old shortcodes.
 *
 * @uses $shortcode_tags
 * @uses remove_all_shortcodes()
 * @uses add_shortcode()
 * @uses do_shortcode()
 *
 * @param string $content Content to parse
 *
 * @return string Content with shortcode parsed
 */
function get_gmedia_unformatted_shortcode_blocks($content){
    global $gmGallery;

    if('0' == $gmGallery->options['shortcode_raw']){
        return $content;
    }

    global $shortcode_tags;

    // Back up current registered shortcodes and clear them all out
    $orig_shortcode_tags = $shortcode_tags;
    remove_all_shortcodes();

    // my_shortcode_handler1(), below, saves the rawr blocks into $this->unformatted_shortcode_blocks[]
    add_shortcode('gmedia', 'gmedia_raw_shortcode');

    // Do the shortcode (only the [rawr] shortcode is now registered)
    $content = do_shortcode($content);

    // Put the original shortcodes back for normal processing at priority 11
    $shortcode_tags = $orig_shortcode_tags;

    return $content;
}

/**
 * @param        $atts
 * @param string $content
 *
 * @return string
 */
function gmedia_raw_shortcode($atts, $content = ''){
    global $wp_filter, $merged_filters, $wp_current_filter;
    $wp_filter_         = $wp_filter;
    $merged_filters_    = $merged_filters;
    $wp_current_filter_ = $wp_current_filter;
    $noraw              = do_shortcode(apply_filters('the_content', '[raw][/raw]'));
    $wp_filter          = $wp_filter_;
    $merged_filters     = $merged_filters_;
    $wp_current_filter  = $wp_current_filter_;

    global $gmedia_shortcode_instance;
    // Store the unformatted content for later:
    $gmedia_shortcode_instance['shortcode_raw'][] = gmedia_shortcode($atts, $content);

    $atts['shortcode_raw'] = count($gmedia_shortcode_instance['shortcode_raw']) - 1;
    $shortcode_atts        = '';

    // Put the shortcode tag back with raw index, so it gets processed again below.
    foreach($atts as $key => $value){
        $shortcode_atts .= " {$key}='{$value}'";
    }
    if(!$noraw){
        //return "[raw]".gmedia_shortcode($atts, $content)."[/raw]";
        return "[raw][gmedia{$shortcode_atts}]{$content}[/gmedia][/raw]";
    } else{
        return "[gmedia{$shortcode_atts}]{$content}[/gmedia]";
    }
}

/**
 * @param $id
 *
 * @return object
 */
function gmedia_shortcode_id_data($id){
    global $gmDB;

    $item = $gmDB->get_term($id);

    if(empty($item) || is_wp_error($item)){
        return false;
    }

    $meta = $gmDB->get_metadata('gmedia_term', $item->term_id);

    if($item->global){
        $item->author_name = get_the_author_meta('display_name', $item->global);
    } else{
        $item->author_name = '';
    }

    $post_id       = isset($meta['_post_ID'][0])? (int) $meta['_post_ID'][0] : 0;
    $item->post_id = $post_id;
    if($post_id){
        $post_item = get_post($post_id);
        if($post_item){
            $item->slug           = $post_item->post_name;
            $item->post_password  = $post_item->post_password;
            $item->comment_count  = $post_item->comment_count;
            $item->comment_status = $post_item->comment_status;
        }
    }

    $item->custom_meta = array();
    $item->meta        = array();
    foreach($meta as $key => $value){
        if(is_protected_meta($key, 'gmedia')){
            $item->meta[$key] = $value[0];
        } else{
            $item->custom_meta[$key] = $value;
        }
    }

    return apply_filters('gmedia_shortcode_id_data', $item);
}