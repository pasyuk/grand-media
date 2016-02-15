<?php
if (preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
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
function gmedia_term_shortcode($atts, $content = '')
{
    /**
     * @var $album
     * @var $tag
     * @var $category
     * @var $filter
     * @var $id
     * @var $module
     * @var $preset
     * @var $_raw
     */
    extract(shortcode_atts(array(
        'album' => 0,
        'tag' => 0,
        'category' => 0,
        'filter' => 0,
        'id' => 0,
        'module' => '',
        'preset' => 0,
        '_raw' => false
    ), $atts));
    if ($album) {
        //$_tax = 'album';
        $id = $album;
    } elseif ($tag) {
        //$_tax = 'tag';
        $id = $tag;
    } elseif ($filter) {
        //$_tax = 'filter';
        $id = $filter;
    } elseif ($category) {
        //$_tax = 'category';
        $id = $category;
    }
    $sc_atts = array(
        'id'   => $id,
        'set_module' => $module,
        'preset' => $preset,
        //'_tax' => $_tax,
        '_raw' => $_raw
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
function gmedia_shortcode($atts, $content = '')
{
    global $gmDB, $gmGallery, $gmCore;
    global $gmedia_shortcode_instance;

    /**
     * @var $id
     * @var $set_module
     * @var $preset
     * @var $_raw
     */
    extract(shortcode_atts(array(
        'id'   => 0,
        'set_module' => '',
        'preset' => 0,
        //'_tax' => 'gallery',
        '_raw' => false
    ), $atts, 'gmedia'));

    $shortcode_raw = (isset($gmGallery->options['shortcode_raw']) && '1' === $gmGallery->options['shortcode_raw']);
    if ($shortcode_raw && false !== $_raw) {
        return $gmedia_shortcode_instance['shortcode_raw'][$_raw];
    }

    $id = intval($id);
    if (! $id) {
        return $content;
    }

    $_gallery = array(
        'term_id' => 0,
        'name' => '',
        'description' => '',
        'status' => 'publish',
        '_edited' => '&#8212;',
        '_module' => 'phantom',
        '_query' => array(),
        '_settings' => array()
    );

    $taxonomy = $gmDB->get_tax_by_term_id($id);
    $gallery  = $gmDB->get_term($id, $taxonomy, ARRAY_A);
    if (is_wp_error($gallery)) {
        return '<div class="gmedia_gallery gmediaShortcodeError">#' . $id . ': ' . $gallery->get_error_message() . '<br />' . $content . '</div>';
    } elseif (empty($gallery)) {
        return '<div class="gmedia_gallery gmediaShortcodeError">#' . $id . ': ' . sprintf(__('No gallery with ID #%s in database'), $id) . '<br />' . $content . '</div>';
    } else {
        if (is_user_logged_in()) {
            if (($gallery['status'] == 'draft') && ($gallery['global'] != get_current_user_id())) {
                return '';
            }
        } else {
            if (in_array($gallery['status'], array('private', 'draft'))) {
                return '';
            }
        }
        $gallery      = array_merge($_gallery, $gallery);
        $gallery_meta = $gmDB->get_metadata('gmedia_term', $id);
        $gallery_meta = array_map('reset', $gallery_meta);
        //$gallery_meta = array_map('maybe_unserialize', $gallery_meta);
        $gallery = array_merge($gallery, $gallery_meta);
    }

    if (! empty($set_module) && $gallery['_module'] != $set_module) {
        $gallery['_module']                        = sanitize_key($set_module);
        $gallery['_settings'][$gallery['_module']] = array();
    } elseif (! isset($gallery['_settings'][$gallery['_module']])) {
        $gallery['_settings'][$gallery['_module']] = array();
    }

    if ('gmedia_filter' === $taxonomy) {
        $gallery['custom_query'] = $gallery['_query'];
        $gallery['_query']       = array();
    }
    if (empty($gallery['_query']) && ('gmedia_gallery' !== $taxonomy)) {
        $gallery['_query'][$taxonomy] = array($id);
    }

    $gallery = apply_filters('gmedia_shortcode_gallery_data', $gallery);

    $module = $gmCore->get_module_path($gallery['_module']);
    if (! $module) {
        return '<div class="gmedia_gallery gmediaShortcodeError">#' . $id . ': ' . __('Gmedia Module folder missed.', 'grand-media') . '<br />' . $content . '</div>';
    }

    if (file_exists($module['path'] . '/index.php') && file_exists($module['path'] . '/settings.php')) {
        $module_info = array('dependencies' => '');
        /** @noinspection PhpIncludeInspection */
        include($module['path'] . '/index.php');
        $module['info'] = $module_info;
        /** @noinspection PhpIncludeInspection */
        include($module['path'] . '/settings.php');
        /** @var $default_options */
        if (isset($default_options)) {
            $module['options'] = $default_options;
        } else {
            return '<div class="gmedia_gallery gmediaShortcodeError">#' . $id . ': ' . sprintf(__('Module `%s` is outdated. Update module to latest version'), $gallery['_module']) . '<br />' . $content . '</div>';
        }
    } else {
        return '<div class="gmedia_gallery gmediaShortcodeError">#' . $id . ': ' . sprintf(__('Module `%s` is broken. Choose another module for this gallery'), $gallery['_module']) . '<br />' . $content . '</div>';
    }

    $settings = $gmCore->array_diff_keyval_recursive($gallery['_settings'][$gallery['_module']], $module['options'], false);

    if (! empty($preset)) {
        $preset = $gmDB->get_term($preset, 'gmedia_module');
        if (! empty($preset) && ! is_wp_error($preset) && ($gallery['_module'] == $preset->status)) {
            $presettings = maybe_unserialize($preset->description);
            $settings    = $gmCore->array_diff_keyval_recursive($presettings, $settings, false);
        }
    }
    $customCSS = (isset($settings['customCSS']) && ('' != trim($settings['customCSS']))) ? $settings['customCSS'] : '';
    unset($settings['customCSS']);

    $terms  = array();
    $gmedia = array();
    if (! empty($gallery['_query'])) {
        $gmedia_status = array('publish');
        if (is_user_logged_in()) {
            $gmedia_status[] = 'private';
        }
        foreach ($gallery['_query'] as $tax => $term_ids) {
            if (! empty($term_ids)) {
                if ('gmedia__in' == $tax) {
                    $term_id          = (int)$gallery['term_id'];
                    $terms[$term_id]  = $gmDB->get_term($term_id, 'gmedia_gallery');
                    $term_ids         = implode(',', wp_parse_id_list($term_ids[0]));
                    $gmedia[$term_id] = $gmDB->get_gmedias(array('gmedia__in' => $term_ids, 'orderby' => 'gmedia__in', 'order' => 'ASC', 'status' => $gmedia_status));
                    continue;
                }
                $term_ids = wp_parse_id_list($term_ids);
                foreach ($term_ids as $term_id) {
                    $terms[$term_id] = $gmDB->get_term($term_id, $tax);
                    if (! empty($terms[$term_id]) && ! is_wp_error($terms[$term_id])) {
                        if ($terms[$term_id]->count) {
                            if ('gmedia_category' == $tax) {
                                $terms[$term_id]->name = $gmGallery->options['taxonomies']['gmedia_category'][$terms[$term_id]->name];
                                $args                  = array('category__in' => $term_id, 'orderby' => $gmGallery->options['in_category_orderby'], 'order' => $gmGallery->options['in_category_order'], 'status' => $gmedia_status);
                                $gmedia[$term_id]      = $gmDB->get_gmedias($args);
                            } elseif ('gmedia_album' == $tax) {
                                if (('draft' == $terms[$term_id]->status) || (('private' == $terms[$term_id]->status) && ! is_user_logged_in())) {
                                    unset($terms[$term_id]);
                                    continue;
                                }
                                $term_meta        = $gmDB->get_metadata('gmedia_term', $term_id);
                                $term_meta        = array_map('reset', $term_meta);
                                $term_meta        = array_merge(array('_orderby' => 'ID', '_order' => 'DESC'), $term_meta);
                                $args             = array('album__in' => $term_id, 'orderby' => $term_meta['_orderby'], 'order' => $term_meta['_order'], 'status' => $gmedia_status);
                                $gmedia[$term_id] = $gmDB->get_gmedias($args);
                            } elseif ('gmedia_tag' == $tax) {
                                $args             = array('tag__in' => $term_id, 'orderby' => $gmGallery->options['in_tag_orderby'], 'order' => $gmGallery->options['in_tag_order'], 'status' => $gmedia_status);
                                $gmedia[$term_id] = $gmDB->get_gmedias($args);
                            }
                        } elseif ('gmedia_filter' == $tax) {
                            if (isset($gallery['custom_query'])) {
                                $args = $gallery['custom_query'];
                            } else {
                                $args = $gmDB->get_metadata('gmedia_term', $term_id, '_query', true);
                            }
                            $args             = array_merge($args, array('status' => $gmedia_status));
                            $gmedia[$term_id] = $gmDB->get_gmedias($args);
                        } else {
                            unset($terms[$term_id]);
                        }
                    } else {
                        unset($terms[$term_id]);
                    }
                }
            } else {
                return '<div class="gmedia_gallery gmediaShortcodeError">#' . $id . ': ' . sprintf(__('Choose gallery source, please.'), $gallery['_module']) . '<br />' . $content . '</div>';
            }
        }
    } else {
        return '<div class="gmedia_gallery gmediaShortcodeError">#' . $id . ': ' . sprintf(__('Choose gallery source, please.'), $gallery['_module']) . '<br />' . $content . '</div>';
    }

    $gmGallery->do_module[$gallery['_module']] = $module;
    $gmGallery->shortcode                      = compact('module', 'gallery', 'terms', 'gmedia');

    /** @noinspection PhpUnusedLocalVariableInspection */
    $is_bot = false;
    if (! ($is_mob = wp_is_mobile())) {
        $is_bot = $gmCore->is_bot();
    }

    do_action('pre_gmedia_shortcode');

    $out = '<div class="gmedia_gallery ' . $gallery['_module'] . '_module' . ($is_mob ? ' is_mobile' : '') . '" id="GmediaGallery_' . $id . '" data-gallery="' . $id . '" data-module="' . $gallery['_module'] . '">';
    $out .= $content;

    ob_start();
    /** @noinspection PhpIncludeInspection */
    include($module['path'] . '/init.php');
    $module_content = ob_get_contents();
    ob_end_clean();

    if ($customCSS) {
        $out .= "<style type='text/css' scoped='scoped'>/**** Custom CSS {$gallery['_module']} #{$id} ****/{$customCSS}</style>";
    }

    $out .= $module_content;
    $out .= '</div>';

    do_action('gmedia_shortcode');

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
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
function get_gmedia_unformatted_shortcode_blocks($content)
{
    global $gmGallery;

    if ('0' == $gmGallery->options['shortcode_raw']) {
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
function gmedia_raw_shortcode($atts, $content = '')
{
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
    $raw_index                                    = count($gmedia_shortcode_instance['shortcode_raw']) - 1;
    $shortcode_atts                               = '';
    // Put the shortcode tag back with raw index, so it gets processed again below.
    $atts['_raw'] = $raw_index;
    foreach ($atts as $key => $value) {
        $shortcode_atts .= " $key=$value";
    }
    if (! $noraw) {
        //return "[raw]".gmedia_shortcode($atts, $content)."[/raw]";
        return "[raw][gmedia{$shortcode_atts}]{$content}[/gmedia][/raw]";
    } else {
        return "[gmedia{$shortcode_atts}]{$content}[/gmedia]";
    }
}