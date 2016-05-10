<?php

/**
 * GmediaProcessor_Library
 */
class GmediaProcessor_Library extends GmediaProcessor {

    public static $cookie_key = false;
    public $edit_mode = false;
    public $selected_items = array();
    public $stack_items = array();
    public $query_args;
    public $filters = array();

    /**
     * GmediaProcessor_Library constructor.
     */
    public function __construct() {
        parent::__construct();

        global $gmCore;

        $user_ID              = get_current_user_id();
        self::$cookie_key     = "gmuser_{$user_ID}_library";
        $this->edit_mode      = $gmCore->_get('edit_mode', false, true);
        $this->url            = add_query_arg(array('page' => $this->page, 'edit_mode' => $this->edit_mode), admin_url('admin.php'));
        $this->selected_items = parent::selected_items(self::$cookie_key);
        $this->stack_items    = parent::selected_items("gmuser_{$user_ID}_library_stack", 'stack_items');

    }

    /**
     * @return array
     */
    public function query_args() {
        global $gmCore, $gmDB;

        $args['mime_type']        = $gmCore->_get('mime_type');
        $args['status']           = $gmCore->_get('status');
        $args['page']             = $gmCore->_get('pager');
        $args['per_page']         = $gmCore->_get('per_page', $this->user_options['per_page_gmedia']);
        $args['author__in']       = parent::filter_by_author($gmCore->_get('author'));
        $args['alb']              = $gmCore->_get('alb');
        $args['album__in']        = $gmCore->_get('album__in');
        $args['album__not_in']    = $gmCore->_get('album__not_in');
        $args['tag_id']           = $gmCore->_get('tag_id');
        $args['tag__in']          = $gmCore->_get('tag__in');
        $args['tag__not_in']      = $gmCore->_get('tag__not_in');
        $args['cat']              = $gmCore->_get('cat');
        $args['category__in']     = $gmCore->_get('category__in');
        $args['category__not_in'] = $gmCore->_get('category__not_in');
        $args['category__and']    = $gmCore->_get('category__and');
        $args['gmedia__in']       = $gmCore->_get('gmedia__in');
        $args['s']                = $gmCore->_get('s');
        $args['orderby']          = $gmCore->_get('orderby', $this->user_options['orderby_gmedia']);
        $args['order']            = $gmCore->_get('order', $this->user_options['sortorder_gmedia']);

        if($args['s'] && ('#' == substr($args['s'], 0, 1))) {
            $args['gmedia__in'] = substr($args['s'], 1);
            $args['s']          = false;
        }

        $show_stack = false;
        if(('show' == $gmCore->_req('stack')) && !empty($this->stack_items)) {
            $args['gmedia__in'] = $this->stack_items;
            $args['orderby']    = $gmCore->_get('orderby', 'gmedia__in');
            $args['order']      = $gmCore->_get('order', 'ASC');
            $show_stack         = true;
        }
        if(('selected' == $gmCore->_req('filter')) && !empty($this->selected_items)) {
            if($show_stack) {
                $stack_items        = wp_parse_id_list($this->stack_items);
                $selected_items     = wp_parse_id_list($this->selected_items);
                $gmedia_in          = array_intersect($stack_items, $selected_items);
                $args['gmedia__in'] = $gmedia_in;
            } else {
                $args['gmedia__in'] = $this->selected_items;
                $args['orderby']    = $gmCore->_get('orderby', 'gmedia__in');
                $args['order']      = $gmCore->_get('order', 'ASC');
            }
        }

        $query_args = apply_filters('gmedia_library_query_args', $args);

        foreach($query_args as $key => $val) {
            if(empty($val) && ('0' !== $val) && (0 !== $val)) {
                unset($query_args[$key]);
            }
        }

        if(!empty($query_args['author__in']) && $gmCore->caps['gmedia_show_others_media']) {
            $authors_names = $query_args['author__in'];
            foreach($authors_names as $i => $id) {
                $authors_names[$i] = get_the_author_meta('display_name', $id);
            }
            $this->filters['filter_author'] = array(
                'title'  => __('Filter Author', 'grand-media'),
                'filter' => $authors_names
            );
        }

        $gmDB->gmedias_album_stuff($query_args);
        if(!empty($query_args['album__in'])) {
            $albums_names = $gmDB->get_terms('gmedia_album', array('fields' => 'names', 'global' => $args['author__in'], 'include' => $query_args['album__in']));
            if(!empty($albums_names)) {
                $this->filters['filter_albums'] = array(
                    'title'  => __('Filter Album', 'grand-media'),
                    'filter' => $albums_names
                );
            }
        }
        if(!empty($query_args['album__not_in'])) {
            $albums_names = $gmDB->get_terms('gmedia_album', array('fields' => 'names', 'global' => $args['author__in'], 'include' => $query_args['album__not_in']));
            if(!empty($albums_names)) {
                $this->filters['exclude_albums'] = array(
                    'title'  => __('Exclude Album', 'grand-media'),
                    'filter' => $albums_names
                );
            }
        }

        $gmDB->gmedias_category_stuff($query_args);
        if(!empty($query_args['category__in'])) {
            $category_names = $gmDB->get_terms('gmedia_category', array('fields' => 'names', 'include' => $query_args['category__in']));
            if(!empty($category_names)) {
                $this->filters['filter_categories'] = array(
                    'title'  => __('Filter Category', 'grand-media'),
                    'filter' => $category_names
                );
            }
        }
        if(!empty($query_args['category__not_in'])) {
            $category_names = $gmDB->get_terms('gmedia_category', array('fields' => 'names', 'include' => $query_args['category__not_in']));
            if(!empty($category_names)) {
                $this->filters['exclude_categories'] = array(
                    'title'  => __('Exclude Category', 'grand-media'),
                    'filter' => $category_names
                );
            }
        }

        $gmDB->gmedias_tag_stuff($query_args);
        if(!empty($query_args['tag__in'])) {
            $tag_names = $gmDB->get_terms('gmedia_tag', array('fields' => 'names', 'include' => $query_args['tag__in']));
            if(!empty($tag_names)) {
                $this->filters['filter_tags'] = array(
                    'title'  => __('Filter Tag', 'grand-media'),
                    'filter' => $tag_names
                );
            }
        }
        if(!empty($query_args['tag__not_in'])) {
            $tag_names = $gmDB->get_terms('gmedia_tag', array('fields' => 'names', 'include' => $query_args['tag__not_in']));
            if(!empty($tag_names)) {
                $this->filters['exclude_tags'] = array(
                    'title'  => __('Exclude Tag', 'grand-media'),
                    'filter' => $tag_names
                );
            }
        }

        return $query_args;
    }

    protected function processor() {
        global $user_ID, $gmCore, $gmDB, $gmGallery;

        if(!$gmCore->caps['gmedia_library']) {
            wp_die(__('You are not allowed to be here', 'grand-media'));
        }

        include_once(GMEDIA_ABSPATH . 'admin/pages/library/functions.php');

        if(isset($_GET['display_mode'])) {
            $display_mode = $_GET['display_mode'];
            if(in_array($display_mode, array('grid', 'list'))) {
                $this->user_options = array_merge($this->user_options, array('display_mode_gmedia' => $display_mode));
                update_user_meta($user_ID, 'gm_screen_options', $this->user_options);
            }
            $location = remove_query_arg('display_mode');
            wp_redirect($location);
            exit;
        }

        if(isset($_GET['grid_cell_fit'])) {
            $this->user_options['grid_cell_fit_gmedia'] = !$this->user_options['grid_cell_fit_gmedia'];
            update_user_meta($user_ID, 'gm_screen_options', $this->user_options);
            if(isset($_GET['ajaxload'])) {
                exit;
            }
            $location = remove_query_arg('grid_cell_fit');
            wp_redirect($location);
            exit;
        }

        if(isset($_GET['gallery'])) {
            $location = $this->url;
            $gallery_id = $gmCore->_get('gallery');
            if($gallery_id) {
                $gallery_query = $gmDB->get_metadata('gmedia_term', $gallery_id, '_query', true);
                $location = add_query_arg($gallery_query, $location);
            }
            wp_redirect($location);
            exit;
        }

        $this->query_args = $this->query_args();


        if(isset($_POST['quick_gallery'])) {
            check_admin_referer('gmedia_modal');
            do {
                if(!$gmCore->caps['gmedia_gallery_manage']) {
                    $this->error[] = __('You are not allowed to manage galleries', 'grand-media');
                    break;
                }
                $gallery         = $gmCore->_post('gallery');
                $gallery['name'] = trim($gallery['name']);
                if(empty($gallery['name'])) {
                    $this->error[] = __('Gallery Name is not specified', 'grand-media');
                    break;
                }
                if($gmCore->is_digit($gallery['name'])) {
                    $this->error[] = __("Gallery name can't be only digits", 'grand-media');
                    break;
                }
                if(empty($gallery['query']['gmedia__in'])) {
                    $this->error[] = __('Choose gmedia from library for quick gallery', 'grand-media');
                    break;
                }
                $taxonomy = 'gmedia_gallery';
                if(($term_id = $gmDB->term_exists($gallery['name'], $taxonomy))) {
                    $this->error[] = __('A term with the name provided already exists', 'grand-media');
                    break;
                }
                $term_id = $gmDB->insert_term($gallery['name'], $taxonomy);
                if(is_wp_error($term_id)) {
                    $this->error[] = $term_id->get_error_message();
                    break;
                }
                $gallery_module = $gallery['module'];
                $module_settings = array($gallery_module => array());
                if($gmCore->is_digit($gallery_module)) {
                    $preset = $gmDB->get_term($gallery_module);
                    if(!empty($preset) && !is_wp_error($preset)){
                        $gallery_module = $preset->status;
                        $module_settings = array(
                            $gallery_module => maybe_unserialize($preset->description)
                        );
                    } else {
                        $gallery_module = $gmGallery->options['default_gmedia_module'];
                        $module_settings = array(
                            $gallery_module => array()
                        );
                    }
                }
                $gallery['query'] = array_merge($gallery['query'], array('order' => 'ASC', 'orderby' => 'gmedia__in'));

                $gallery_meta = array(
                    '_edited'   => gmdate('Y-m-d H:i:s'),
                    '_query'    => $gallery['query'],
                    '_module'   => $gallery_module,
                    '_settings' => $module_settings
                );
                foreach($gallery_meta as $key => $value) {
                    $gmDB->update_metadata('gmedia_term', $term_id, $key, $value);
                }
                $this->msg[] = sprintf(__('Gallery "%s" successfuly saved. Shortcode: [gmedia id=%d]', 'grand-media'), esc_attr($gallery['name']), $term_id);
            } while(0);
        }

        if(isset($_POST['filter_categories'])) {
            if(false !== ($term = $gmCore->_post('cat'))) {
                $location = add_query_arg(array('page' => $this->page, 'edit_mode' => $this->edit_mode, 'category__in' => implode(',', $term)), admin_url('admin.php'));
                wp_redirect($location);
                exit;
            }
        }
        if(isset($_POST['filter_albums'])) {
            if(false !== ($term = $gmCore->_post('alb'))) {
                $location = add_query_arg(array('page' => $this->page, 'edit_mode' => $this->edit_mode, 'album__in' => implode(',', $term)), admin_url('admin.php'));
                wp_redirect($location);
                exit;
            }
        }
        if(isset($_POST['filter_tags'])) {
            if(false !== ($term = $gmCore->_post('tag_ids'))) {
                $location = add_query_arg(array('page' => $this->page, 'edit_mode' => $this->edit_mode, 'tag__in' => $term), admin_url('admin.php'));
                wp_redirect($location);
                exit;
            }
        }
        if(isset($_POST['custom_filter'])) {
            if(false !== ($term = $gmCore->_post('custom_filter_id'))) {
                $location = add_query_arg(array('page' => $this->page, 'edit_mode' => $this->edit_mode, 'custom_filter' => $term), admin_url('admin.php'));
                wp_redirect($location);
                exit;
            }
        }
        if(isset($_POST['filter_author'])) {
            $authors  = $gmCore->_post('author_ids');
            $location = add_query_arg(array('page' => $this->page, 'edit_mode' => $this->edit_mode, 'author' => (int)$authors), admin_url('admin.php'));
            wp_redirect($location);
            exit;
        }

        if(!empty($this->selected_items)) {
            if(isset($_POST['assign_album'])) {
                check_admin_referer('gmedia_modal');
                if($gmCore->caps['gmedia_terms']) {
                    if(!$gmCore->caps['gmedia_edit_others_media']) {
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $this->selected_items));
                        if(count($selected_items) < count($this->selected_items)) {
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else {
                        $selected_items = $this->selected_items;
                    }
                    $term = $gmCore->_post('alb');
                    if((false !== $term) && ($count = count($selected_items))) {
                        if(empty($term)) {
                            foreach($selected_items as $item) {
                                $gmDB->delete_gmedia_term_relationships($item, 'gmedia_album');
                            }
                            $this->msg[] = sprintf(__('%d item(s) updated with "No Album"', 'grand-media'), $count);
                        } else {
                            $term_ids = array();
                            foreach($selected_items as $item) {
                                $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_album', $append = 0);
                                if(is_wp_error($result)) {
                                    $this->error[] = $result;
                                } elseif($result) {
                                    foreach($result as $t_id) {
                                        $term_ids[$t_id][] = $item;
                                    }
                                }
                            }
                            if(!empty($term_ids)) {
                                global $wpdb;

                                foreach($term_ids as $term_id => $item_ids) {
                                    $term = $gmDB->get_term($term_id);
                                    if(isset($_POST['status_global'])) {
                                        $values = array();
                                        foreach($selected_items as $item) {
                                            $values[] = $wpdb->prepare("%d", $item);
                                        }
                                        if($values) {
                                            $status = esc_sql($term->status);
                                            if(false === $wpdb->query("UPDATE {$wpdb->prefix}gmedia SET status = '{$status}' WHERE ID IN (" . join(',', $values) . ")")) {
                                                $this->error[] = __('Could not update statuses for gmedia items in the database');
                                            }
                                        }
                                    }
                                    $this->msg[] = sprintf(__('Album `%s` assigned to %d item(s)', 'grand-media'), esc_html($term->name), count($item_ids));
                                }
                            }
                        }

                        $this->selected_items = $this->clear_selected_items('library');
                    }
                } else {
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                }
            }
            if(isset($_POST['assign_category'])) {
                check_admin_referer('gmedia_modal');
                if($gmCore->caps['gmedia_terms']) {
                    if(!$gmCore->caps['gmedia_edit_others_media']) {
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $this->selected_items));
                        if(count($selected_items) < count($this->selected_items)) {
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else {
                        $selected_items = $this->selected_items;
                    }
                    $term = $gmCore->_post('cat_names');
                    $term = explode(',', $term);
                    if(!empty($term) && ($count = count($selected_items))) {
                        foreach($selected_items as $item) {
                            $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_category', $append = 1);
                            if(is_wp_error($result)) {
                                $this->error[] = $result;
                                $count--;
                            } elseif(!$result) {
                                $count--;
                            }
                        }

                        $this->msg[] = sprintf(__("Categories assigned to %d image(s).", 'grand-media'), $count);

                        $this->selected_items = $this->clear_selected_items('library');
                    }
                } else {
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                }
            }
            if(isset($_POST['unassign_category'])) {
                check_admin_referer('gmedia_modal');
                if(($term = $gmCore->_post('category_id')) && $gmCore->caps['gmedia_terms']) {
                    if(!$gmCore->caps['gmedia_edit_others_media']) {
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $this->selected_items));
                        if(count($selected_items) < count($this->selected_items)) {
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else {
                        $selected_items = $this->selected_items;
                    }
                    $term = array_map('intval', $term);
                    if(($count = count($selected_items))) {
                        foreach($selected_items as $item) {
                            $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_category', $append = -1);
                            if(is_wp_error($result)) {
                                $this->error[] = $result;
                                $count--;
                            } elseif(!$result) {
                                $count--;
                            }
                        }
                        $this->msg[] = sprintf(__('%d category(ies) deleted from %d item(s)', 'grand-media'), count($term), $count);

                        $this->selected_items = $this->clear_selected_items('library');
                    }
                } else {
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                }
            }
            if(isset($_POST['add_tags'])) {
                check_admin_referer('gmedia_modal');
                if(!$gmCore->caps['gmedia_terms']) {
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                } else {
                    $term      = $gmCore->_post('tag_names');
                    $iptc_tags = $gmCore->_post('iptc_tags');
                    if($term || $iptc_tags) {
                        if(!$gmCore->caps['gmedia_edit_others_media']) {
                            $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $this->selected_items));
                            if(count($selected_items) < count($this->selected_items)) {
                                $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                            }
                        } else {
                            $selected_items = $this->selected_items;
                        }
                        $term = explode(',', $term);
                        if(($count = count($selected_items))) {
                            foreach($selected_items as $item) {
                                $_term = $term;
                                if($iptc_tags) {
                                    $_metadata = $gmDB->get_metadata('gmedia', $item, '_metadata', true);
                                    if(isset($_metadata['image_meta']['keywords']) && is_array($_metadata['image_meta']['keywords'])) {
                                        $_term = array_merge($_metadata['image_meta']['keywords'], $term);
                                    }
                                }
                                $result = $gmDB->set_gmedia_terms($item, $_term, 'gmedia_tag', $append = 1);
                                if(is_wp_error($result)) {
                                    $this->error[] = $result;
                                    $count--;
                                } elseif(!$result) {
                                    $count--;
                                }
                            }
                            $this->msg[] = sprintf(__('Tags added to %d item(s)', 'grand-media'), $count);

                            $this->selected_items = $this->clear_selected_items('library');
                        }
                    } else {
                        $this->error[] = __('No tags specified', 'grand-media');
                    }
                }
            }
            if(isset($_POST['delete_tags'])) {
                check_admin_referer('gmedia_modal');
                if(($term = $gmCore->_post('tag_id')) && $gmCore->caps['gmedia_terms']) {
                    if(!$gmCore->caps['gmedia_edit_others_media']) {
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $this->selected_items));
                        if(count($selected_items) < count($this->selected_items)) {
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else {
                        $selected_items = $this->selected_items;
                    }
                    $term = array_map('intval', $term);
                    if(($count = count($selected_items))) {
                        foreach($selected_items as $item) {
                            $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_tag', $append = -1);
                            if(is_wp_error($result)) {
                                $this->error[] = $result;
                                $count--;
                            } elseif(!$result) {
                                $count--;
                            }
                        }
                        $this->msg[] = sprintf(__('%d tag(s) deleted from %d item(s)', 'grand-media'), count($term), $count);

                        $this->selected_items = $this->clear_selected_items('library');
                    }
                } else {
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                }
            }
            if(isset($_POST['batch_edit'])) {
                check_admin_referer('gmedia_modal');
                if($gmCore->caps['gmedia_edit_media']) {
                    if(!$gmCore->caps['gmedia_edit_others_media']) {
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $this->selected_items));
                        if(count($selected_items) < count($this->selected_items)) {
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else {
                        $selected_items = $this->selected_items;
                    }
                    if(($count = count($selected_items))) {
                        $batch_data       = array();
                        $b_filename       = $gmCore->_post('batch_filename');
                        $b_title          = $gmCore->_post('batch_title');
                        $b_description    = $gmCore->_post('batch_description');
                        $b_link           = $gmCore->_post('batch_link');
                        $b_status         = $gmCore->_post('batch_status');
                        $b_comment_status = $gmCore->_post('batch_comment_status');
                        if($b_status) {
                            $batch_data['status'] = $b_status;
                        }
                        if($b_comment_status) {
                            $batch_data['comment_status'] = $b_comment_status;
                        }
                        $b_author = $gmCore->_post('batch_author');
                        if($b_author && ('-1' != $b_author)) {
                            $batch_data['author'] = $b_author;
                        }
                        $i = 0;
                        foreach($selected_items as $item) {
                            $id          = (int)$item;
                            $gmedia      = $gmDB->get_gmedia($id, ARRAY_A);
                            $item_author = (int)$gmedia['author'];

                            if('custom' == $b_filename && ($gmCore->caps['gmedia_delete_others_media'] || ($item_author == $user_ID))) {
                                $filename_custom = $gmCore->_post('batch_filename_custom');
                                if(!empty($filename_custom) && ('{filename}' !== $filename_custom)) {

                                    $gmuid = pathinfo($gmedia['gmuid']);

                                    $filename_vars = array('{filename}' => $gmuid['filename'], '{id}' => $gmedia['ID']);
                                    if(preg_match_all('/{index[:]?(\d+)?}/', $filename_custom, $matches_all)) {
                                        foreach($matches_all[0] as $key => $matches) {
                                            $index                   = intval($matches_all[1][$key]) + $i;
                                            $filename_vars[$matches] = $index;
                                        }
                                    }
                                    $filename_custom = strtr($filename_custom, $filename_vars);

                                    $filename_custom = preg_replace('/[^a-z0-9_\.-]+/i', '_', $filename_custom);
                                    if($filename_custom && $filename_custom != $gmuid['filename']) {
                                        $fileinfo = $gmCore->fileinfo($filename_custom . '.' . $gmuid['extension']);
                                        if(false !== $fileinfo) {
                                            if('image' == $fileinfo['dirname']) {
                                                /** WordPress Image Administration API */
                                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                                if(file_is_displayable_image($fileinfo['dirpath'] . '/' . $gmedia['gmuid'])) {
                                                    @rename($fileinfo['dirpath_original'] . '/' . $gmedia['gmuid'], $fileinfo['filepath_original']);
                                                    @rename($fileinfo['dirpath_thumb'] . '/' . $gmedia['gmuid'], $fileinfo['filepath_thumb']);
                                                }
                                            }
                                            if(@rename($fileinfo['dirpath'] . '/' . $gmedia['gmuid'], $fileinfo['filepath'])) {
                                                $gmedia['gmuid']     = $fileinfo['basename'];
                                                $batch_data['gmuid'] = $fileinfo['basename'];
                                            }
                                        }
                                    }
                                }
                            }
                            switch($b_title) {
                                case 'empty':
                                    $batch_data['title'] = '';
                                break;
                                case 'filename':
                                    $title               = pathinfo($gmedia['gmuid'], PATHINFO_FILENAME);
                                    $batch_data['title'] = ucwords(str_replace('_', ' ', $title));
                                break;
                                case 'custom':
                                    $title_custom = $gmCore->_post('batch_title_custom');
                                    if(false !== $title_custom) {
                                        $batch_data['title'] = $title_custom;
                                    }
                                break;
                            }
                            switch($b_description) {
                                case 'empty':
                                    $batch_data['description'] = '';
                                break;
                                case 'metadata':
                                    $metatext = $gmCore->metadata_text($id);
                                    if($gmedia['description']) {
                                        $gmedia['description'] .= "\n";
                                    }
                                    $batch_data['description'] = $gmedia['description'] . $metatext;
                                break;
                                case 'custom':
                                    $description_custom = $gmCore->_post('batch_description_custom');
                                    if(false !== $description_custom) {
                                        $what_description_custom = $gmCore->_post('what_description_custom');
                                        if('replace' == $what_description_custom) {
                                            $batch_data['description'] = $description_custom;
                                        } elseif('append' == $what_description_custom) {
                                            $batch_data['description'] = $gmedia['description'] . $description_custom;
                                        } elseif('prepend' == $what_description_custom) {
                                            $batch_data['description'] = $description_custom . $gmedia['description'];
                                        }
                                    }
                                break;
                            }
                            switch($b_link) {
                                case 'empty':
                                    $batch_data['link'] = '';
                                break;
                                case 'self':
                                    $fileinfo           = $gmCore->fileinfo($gmedia['gmuid'], false);
                                    $batch_data['link'] = $fileinfo['fileurl_original'];
                                break;
                                case 'custom':
                                    $link_custom = $gmCore->_post('batch_link_custom');
                                    if(false !== $link_custom) {
                                        $batch_data['link'] = $link_custom;
                                    }
                                break;
                            }
                            if(!empty($batch_data)) {
                                $batch_data['modified'] = current_time('mysql');
                                $gmedia_data            = array_merge($gmedia, $batch_data);
                                $gmDB->insert_gmedia($gmedia_data);
                            } else {
                                $count--;
                            }

                            $i++;
                        }
                        $this->msg[] = sprintf(__('%d item(s) updated successfuly', 'grand-media'), $count);

                        $this->selected_items = $this->clear_selected_items('library');
                    }
                } else {
                    $this->error[] = __('You are not allowed to edit media', 'grand-media');
                }
            }
            if('selected' == $gmCore->_get('update_meta')) {
                check_admin_referer('gmedia_update_meta');
                if($gmCore->caps['gmedia_edit_media']) {
                    $count = count($this->selected_items);
                    if($count) {
                        foreach($this->selected_items as $item) {
                            $id             = (int)$item;
                            $media_metadata = $gmDB->generate_gmedia_metadata($id);
                            $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $media_metadata);
                            if(!empty($media_metadata['image_meta']['created_timestamp'])) {
                                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_created_timestamp', $media_metadata['image_meta']['created_timestamp']);
                            }
                            if(!empty($media_metadata['image_meta']['GPS'])) {
                                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_gps', $media_metadata['image_meta']['GPS']);
                            }
                        }
                        $this->msg[] = sprintf(__('%d item(s) updated successfuly', 'grand-media'), $count);
                    }
                    $this->selected_items = $this->clear_selected_items('library');
                } else {
                    $this->error[] = __('You are not allowed to edit media', 'grand-media');
                }
            }
        }
        if(($delete_gmedia = $gmCore->_get('delete'))) {
            check_admin_referer('gmedia_delete');
            if($gmCore->caps['gmedia_delete_media']) {
                if('selected' == $delete_gmedia) {
                    $selected_items = $this->selected_items;
                } else {
                    $selected_items = wp_parse_id_list($delete_gmedia);
                }
                if(!empty($selected_items)) {
                    if(!$gmCore->caps['gmedia_delete_others_media']) {
                        $delete_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $selected_items));
                        if(count($delete_items) < count($selected_items)) {
                            $this->error[] = __('You are not allowed to delete others media', 'grand-media');
                        }
                        $selected_items = $delete_items;
                    }
                    if(($count = count($selected_items))) {
                        $delete_original_file = intval($gmCore->_get('save_original_file'))? false : true;
                        foreach($selected_items as $item) {
                            if(!$gmDB->delete_gmedia((int)$item, $delete_original_file)) {
                                $this->error[] = "#{$item}: " . __('Error in deleting...', 'grand-media');
                                $count--;
                            }
                        }
                        if($count) {
                            if($delete_original_file) {
                                $this->msg[] = sprintf(__('%d item(s) deleted successfuly', 'grand-media'), $count);
                            } else {
                                $this->msg[] = sprintf(__('%d record(s) deleted from database successfuly. Original file(s) safe', 'grand-media'), $count);
                            }
                        }
                        $this->selected_items = array_diff($this->selected_items, $selected_items);
                        if(empty($this->selected_items)) {
                            $this->clear_selected_items('library');
                        } else {
                            setcookie("gmuser_{$user_ID}_library", implode(',', $this->selected_items));
                        }
                        if(!empty($this->stack_items)) {
                            $this->stack_items = array_diff($this->stack_items, $selected_items);
                            if(empty($this->stack_items)) {
                                $this->clear_selected_items('library_stack');;
                            } else {
                                setcookie("gmuser_{$user_ID}_library_stack", implode(',', $this->stack_items));
                            }
                        }
                    }
                }
            } else {
                $this->error[] = __('You are not allowed to delete files', 'grand-media');
            }
        }

    }

}

global $gmProcessor;
$gmProcessor = new GmediaProcessor_Library();
