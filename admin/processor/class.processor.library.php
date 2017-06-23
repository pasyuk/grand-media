<?php

/**
 * GmediaProcessor_Library
 */
class GmediaProcessor_Library extends GmediaProcessor {

    private static $me = null;
    public static $cookie_key = false;
    public $mode = false;
    public $selected_items = array();
    public $stack_items = array();
    public $query_args;
    public $filters = array();

    /**
     * GmediaProcessor_Library constructor.
     */
    public function __construct(){
        parent::__construct();

        global $gmCore;

        $user_ID          = get_current_user_id();
        self::$cookie_key = "gmedia_library";
        $this->mode       = $gmCore->_get('mode', false);
        $stack            = 'show' == $gmCore->_get('stack')? 'show' : false;
        $filter           = 'selected' == $gmCore->_get('filter')? 'selected' : false;
        if($this->edit_term){
            self::$cookie_key .= ':term' . $this->edit_term;
            if($this->edit_term && !isset($_COOKIE[ self::$cookie_key ])){
                foreach($_COOKIE as $key => $value){
                    if('gmedia_library:' === substr($key, 0, 15)){
                        setcookie($key, '', time() - 3600);
                        unset($_COOKIE[ $key ]);
                    }
                }
            }
        } elseif($this->gmediablank && 'select_multiple' == $this->mode){
            self::$cookie_key .= ':frame';
        }
        $this->url            = add_query_arg(array('mode' => $this->mode, 'stack' => $stack, 'filter' => $filter, 'edit_term' => $this->edit_term), $this->url);
        $this->selected_items = parent::selected_items(self::$cookie_key);
        $this->stack_items    = parent::selected_items("gmedia_{$user_ID}_libstack", 'stack_items');

        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    /**
     * @return array
     */
    public function query_args(){
        global $gmCore, $gmDB, $gmGallery;

        if($this->edit_term){
            $per_page = $this->user_options["per_page_{$this->taxonomy}_edit"];
            if('album' === $this->taxterm){
                $alb_meta = $gmDB->get_metadata('gmedia_term', $this->edit_term);
                $orderby  = !empty($alb_meta['_orderby'][0])? $alb_meta['_orderby'][0] : $gmGallery->options['in_album_orderby'];
                $order    = !empty($alb_meta['_order'][0])? $alb_meta['_order'][0] : $gmGallery->options['in_album_order'];
            } elseif('category' === $this->taxterm){
                $cat_meta = $gmDB->get_metadata('gmedia_term', $this->edit_term);
                $orderby  = !empty($cat_meta['_orderby'][0])? $cat_meta['_orderby'][0] : $gmGallery->options['in_category_orderby'];
                $order    = !empty($cat_meta['_order'][0])? $cat_meta['_order'][0] : $gmGallery->options['in_category_order'];
            } elseif('tag' === $this->taxterm){
                $orderby = $gmGallery->options['in_tag_orderby'];
                $order   = $gmGallery->options['in_tag_order'];
            } else{
                $orderby = $this->user_options['orderby_gmedia'];
                $order   = $this->user_options['sortorder_gmedia'];
            }
        } else{
            $per_page = $this->user_options["per_page_gmedia"];
            $orderby  = $this->user_options['orderby_gmedia'];
            $order    = $this->user_options['sortorder_gmedia'];
        }

        $args['mime_type']        = $gmCore->_get('mime_type');
        $args['status']           = $gmCore->_get('status');
        $args['page']             = $gmCore->_get('pager');
        $args['per_page']         = $gmCore->_get('per_page', $per_page);
        $args['author__in']       = parent::filter_by_author($gmCore->_get('author__in', $gmCore->_get('author')));
        $args['alb']              = $gmCore->_get('alb');
        $args['album__in']        = $gmCore->_get('album__in');
        $args['album__not_in']    = $gmCore->_get('album__not_in');
        $args['tag_id']           = $gmCore->_get('tag_id');
        $args['tag__in']          = $gmCore->_get('tag__in');
        $args['tag__and']         = $gmCore->_get('tag__and');
        $args['tag__not_in']      = $gmCore->_get('tag__not_in');
        $args['cat']              = $gmCore->_get('cat');
        $args['category__in']     = $gmCore->_get('category__in');
        $args['category__not_in'] = $gmCore->_get('category__not_in');
        $args['category__and']    = $gmCore->_get('category__and');
        $args['gmedia__in']       = $gmCore->_get('gmedia__in');
        $args['s']                = $gmCore->_get('s');
        $args['orderby']          = $gmCore->_get('orderby', $orderby);
        $args['order']            = $gmCore->_get('order', $order);
        $args['terms_relation']   = $gmCore->_get('terms_relation');
        $args['limit']            = $gmCore->_get('limit');

        if('duplicates' === $args['gmedia__in']){
            $duplicates = $gmDB->get_duplicates();
            if( !empty($duplicates['duplicate_ids'])){
                $args['gmedia__in'] = $duplicates['duplicate_ids'];
                $args['orderby']    = 'gmedia__in';

                setcookie(self::$cookie_key, implode('.', $duplicates['duplicate_select']));
                $_COOKIE[ self::$cookie_key ] = implode('.', $duplicates['duplicate_select']);
                $this->selected_items         = $duplicates['duplicate_select'];
            } else{
                unset($args['gmedia__in']);
                $this->msg[] = __('No duplicates in Gmedia Library', 'grand-media');
            }
        }

        if($args['s'] && ('#' == substr($args['s'], 0, 1))){
            $args['gmedia__in'] = substr($args['s'], 1);
            $args['s']          = false;
        }

        $show_stack = false;
        if(('show' == $gmCore->_req('stack')) && !empty($this->stack_items)){
            $args['gmedia__in'] = $this->stack_items;
            $args['orderby']    = $gmCore->_get('orderby', 'gmedia__in');
            $args['order']      = $gmCore->_get('order', 'ASC');
            $show_stack         = true;
        }
        if(('selected' == $gmCore->_req('filter')) && !empty($this->selected_items)){
            if($show_stack){
                $stack_items        = wp_parse_id_list($this->stack_items);
                $selected_items     = wp_parse_id_list($this->selected_items);
                $gmedia_in          = array_intersect($stack_items, $selected_items);
                $args['gmedia__in'] = $gmedia_in;
            } else{
                $args['gmedia__in'] = $this->selected_items;
                $args['orderby']    = $gmCore->_get('orderby', 'gmedia__in');
                $args['order']      = $gmCore->_get('order', 'ASC');
            }
        }

        $query_args = apply_filters('gmedia_library_query_args', $args);

        foreach($query_args as $key => $val){
            if(empty($val) && ('0' !== $val) && (0 !== $val)){
                unset($query_args[ $key ]);
            }
        }

        if( !empty($query_args['author__in']) && $gmCore->caps['gmedia_show_others_media']){
            $authors_names = $query_args['author__in'];
            foreach($authors_names as $i => $id){
                if((int) $id){
                    $authors_names[ $i ] = get_the_author_meta('display_name', $id);
                }
            }
            $this->filters['filter_author'] = array(
                'title'  => __('Filter Author', 'grand-media'),
                'filter' => $authors_names
            );
        }

        $gmDB->gmedias_album_stuff($query_args);
        if( !empty($query_args['album__in'])){
            if(isset($query_args['within_album'])){
                $filter_title = __('Exclude Album', 'grand-media');
                $albums_names = array();
                if( !empty($query_args['with_album__not_in'])){
                    $albums_names = $gmDB->get_terms('gmedia_album', array('fields' => 'names', 'global' => $args['author__in'], 'include' => $query_args['with_album__not_in']));
                }
                array_push($albums_names, __('Hide items without album', 'grand-media'));
            } else{
                $filter_title = __('Filter Album', 'grand-media');
                $albums_names = $gmDB->get_terms('gmedia_album', array('fields' => 'names', 'global' => $args['author__in'], 'include' => $query_args['album__in']));
            }
            if( !empty($albums_names)){
                $this->filters['filter_albums'] = array(
                    'title'  => $filter_title,
                    'filter' => $albums_names
                );
            }
        }
        if( !empty($query_args['album__not_in'])){
            if(isset($query_args['without_album'])){
                $filter_title = __('Filter Album', 'grand-media');
                $albums_names = array();
                if( !empty($query_args['with_album__in'])){
                    $albums_names = $gmDB->get_terms('gmedia_album', array('fields' => 'names', 'global' => $args['author__in'], 'include' => $query_args['with_album__in']));
                }
                array_push($albums_names, __('Show items without album', 'grand-media'));
            } else{
                $filter_title = __('Exclude Album', 'grand-media');
                $albums_names = $gmDB->get_terms('gmedia_album', array('fields' => 'names', 'global' => $args['author__in'], 'include' => $query_args['album__not_in']));
            }
            if( !empty($albums_names)){
                $this->filters['exclude_albums'] = array(
                    'title'  => $filter_title,
                    'filter' => $albums_names
                );
            }
        }

        $gmDB->gmedias_category_stuff($query_args);
        if( !empty($query_args['category__in'])){
            if(isset($query_args['within_category'])){
                $filter_title   = __('Exclude Category', 'grand-media');
                $category_names = array();
                if( !empty($query_args['with_category__not_in'])){
                    $category_names = $gmDB->get_terms('gmedia_category', array('fields' => 'names', 'include' => $query_args['with_category__not_in']));
                }
                $category_names = array_push($category_names, __('Hide items without categories', 'grand-media'));
            } else{
                $filter_title   = __('Filter Category', 'grand-media');
                $category_names = $gmDB->get_terms('gmedia_category', array('fields' => 'names', 'include' => $query_args['category__in']));
            }
            if( !empty($category_names)){
                $this->filters['filter_categories'] = array(
                    'title'  => $filter_title,
                    'filter' => $category_names
                );
            }
        }
        if( !empty($query_args['category__not_in'])){
            if(isset($query_args['without_category'])){
                $filter_title   = __('Filter Category', 'grand-media');
                $category_names = array();
                if( !empty($query_args['with_category__in'])){
                    $category_names = $gmDB->get_terms('gmedia_category', array('fields' => 'names', 'include' => $query_args['with_category__in']));
                }
                $category_names = array_push($category_names, __('Show items without categories', 'grand-media'));
            } else{
                $filter_title   = __('Exclude Category', 'grand-media');
                $category_names = $gmDB->get_terms('gmedia_category', array('fields' => 'names', 'include' => $query_args['category__not_in']));
            }
            if( !empty($category_names)){
                $this->filters['exclude_categories'] = array(
                    'title'  => $filter_title,
                    'filter' => $category_names
                );
            }
        }

        $gmDB->gmedias_tag_stuff($query_args);
        if( !empty($query_args['tag__in'])){
            $tag_names = $gmDB->get_terms('gmedia_tag', array('fields' => 'names', 'include' => $query_args['tag__in']));
            if( !empty($tag_names)){
                $this->filters['filter_tags'] = array(
                    'title'  => __('Filter Tag', 'grand-media'),
                    'filter' => $tag_names
                );
            }
        }
        if( !empty($query_args['tag__not_in'])){
            $tag_names = $gmDB->get_terms('gmedia_tag', array('fields' => 'names', 'include' => $query_args['tag__not_in']));
            if( !empty($tag_names)){
                $this->filters['exclude_tags'] = array(
                    'title'  => __('Exclude Tag', 'grand-media'),
                    'filter' => $tag_names
                );
            }
        }

        if( !empty($args['terms_relation'])){
            $this->filters['terms_relation'] = array(
                'title'  => __('Terms Relation', 'grand-media'),
                'filter' => array(strip_tags($args['terms_relation']))
            );
        }

        return $query_args;
    }

    protected function processor(){
        global $user_ID, $gmCore, $gmDB, $gmGallery;

        if( !$gmCore->caps['gmedia_library']){
            wp_die(__('You are not allowed to be here', 'grand-media'));
        }

        include_once(GMEDIA_ABSPATH . 'admin/pages/library/functions.php');

        if(in_array($this->mode, array('select_single', 'select_multiple'))){
            $this->display_mode = 'grid';
        }
        if(isset($_GET['display_mode'])){
            $display_mode = $_GET['display_mode'];
            if(in_array($display_mode, array('grid', 'list'))){
                $view               = $this->gmediablank? '_frame' : '';
                $this->user_options = array_merge($this->user_options, array("display_mode_gmedia{$view}" => $display_mode));
                update_user_meta($user_ID, 'gm_screen_options', $this->user_options);
            }
            $location = remove_query_arg('display_mode');
            wp_redirect($location);
            exit;
        }

        if(isset($_GET['grid_cell_fit'])){
            $this->user_options['grid_cell_fit_gmedia'] = !$this->user_options['grid_cell_fit_gmedia'];
            update_user_meta($user_ID, 'gm_screen_options', $this->user_options);
            if(isset($_GET['ajaxload'])){
                exit;
            }
            $location = remove_query_arg('grid_cell_fit');
            wp_redirect($location);
            exit;
        }

        if(isset($_GET['gallery'])){
            $location   = $this->url;
            $gallery_id = $gmCore->_get('gallery');
            if($gallery_id){
                $gallery_query = $gmDB->get_metadata('gmedia_term', $gallery_id, '_query', true);
                $location      = add_query_arg($gallery_query, $location);
            }
            wp_redirect($location);
            exit;
        }

        $this->query_args = $this->query_args();


        if(isset($_POST['quick_gallery'])){
            check_admin_referer('gmedia_action', '_wpnonce_action');
            do {
                if( !$gmCore->caps['gmedia_gallery_manage']){
                    $this->error[] = __('You are not allowed to manage galleries', 'grand-media');
                    break;
                }
                $gallery         = $gmCore->_post('gallery');
                $gallery['name'] = trim($gallery['name']);
                if(empty($gallery['name'])){
                    $this->error[] = __('Gallery Name is not specified', 'grand-media');
                    break;
                }
                if($gmCore->is_digit($gallery['name'])){
                    $this->error[] = __("Gallery name can't be only digits", 'grand-media');
                    break;
                }
                if(empty($gallery['query']['gmedia__in'])){
                    $this->error[] = __('Choose gmedia from library for quick gallery', 'grand-media');
                    break;
                }
                $taxonomy = 'gmedia_gallery';
                if(($term_id = $gmDB->term_exists($gallery['name'], $taxonomy))){
                    $this->error[] = __('A term with the name provided already exists', 'grand-media');
                    break;
                }
                $term_id = $gmDB->insert_term($gallery['name'], $taxonomy);
                if(is_wp_error($term_id)){
                    $this->error[] = $term_id->get_error_message();
                    break;
                }
                $getModulePreset  = $gmCore->getModulePreset($gallery['module']);
                $gallery['query'] = array_merge($gallery['query'], array('order' => 'ASC', 'orderby' => 'gmedia__in'));

                $gallery_meta = array(
                    '_edited'   => gmdate('Y-m-d H:i:s'),
                    '_query'    => $gallery['query'],
                    '_module'   => $getModulePreset['module'],
                    '_settings' => $getModulePreset['settings']
                );
                foreach($gallery_meta as $key => $value){
                    $gmDB->update_metadata('gmedia_term', $term_id, $key, $value);
                }
                $this->msg[] = sprintf(__('Gallery "%s" successfully saved. Shortcode: [gmedia id=%d]', 'grand-media'), esc_attr($gallery['name']), $term_id);
            } while(0);
        }

        if(isset($_POST['filter_categories'])){
            if(false !== ($term = $gmCore->_post('cat'))){
                $location = add_query_arg(array('category__in' => implode(',', $term)), $this->url);
                wp_redirect($location);
                exit;
            }
        }
        if(isset($_POST['filter_albums'])){
            if(false !== ($term = $gmCore->_post('alb'))){
                $location = add_query_arg(array('album__in' => implode(',', $term)), $this->url);
                wp_redirect($location);
                exit;
            }
        }
        if(isset($_POST['filter_tags'])){
            if(false !== ($term = $gmCore->_post('tag_ids'))){
                $location = add_query_arg(array('tag__in' => $term), $this->url);
                wp_redirect($location);
                exit;
            }
        }
        if(isset($_POST['filter_author'])){
            $authors  = $gmCore->_post('author_ids');
            $location = add_query_arg(array('author' => (int) $authors), $this->url);
            wp_redirect($location);
            exit;
        }

        $do_gmedia = $gmCore->_get('do_gmedia');
        if( !empty($this->selected_items) || isset($_POST['cookie_key'])){
            if(isset($_POST['assign_album'])){
                check_admin_referer('gmedia_action', '_wpnonce_action');
                if($gmCore->caps['gmedia_terms']){
                    $cookie_key = $gmCore->_post('cookie_key', self::$cookie_key);
                    $ids        = $this->selected_items($cookie_key);
                    if( !$gmCore->caps['gmedia_edit_others_media']){
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $ids));
                        if(count($selected_items) < count($ids)){
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else{
                        $selected_items = $ids;
                    }
                    $term = $gmCore->_post('alb');
                    if((false !== $term) && ($count = count($selected_items))){
                        if(empty($term)){
                            foreach($selected_items as $item){
                                $gmDB->delete_gmedia_term_relationships($item, 'gmedia_album');
                            }
                            $this->msg[] = sprintf(__('%d item(s) updated with "No Album"', 'grand-media'), $count);
                        } else{
                            $term_ids = array();
                            foreach($selected_items as $item){
                                $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_album', $append = 0);
                                if(is_wp_error($result)){
                                    $this->error[] = $result;
                                } elseif($result){
                                    foreach($result as $t_id){
                                        $term_ids[ $t_id ][] = $item;
                                    }
                                }
                            }
                            if( !empty($term_ids)){
                                global $wpdb;

                                foreach($term_ids as $term_id => $item_ids){
                                    $term = $gmDB->get_term($term_id);
                                    if(isset($_POST['status_global'])){
                                        $values = array();
                                        foreach($selected_items as $item){
                                            $values[] = $wpdb->prepare("%d", $item);
                                        }
                                        if($values){
                                            $status = esc_sql($term->status);
                                            if(false === $wpdb->query("UPDATE {$wpdb->prefix}gmedia SET status = '{$status}' WHERE ID IN (" . join(',', $values) . ")")){
                                                $this->error[] = __('Could not update statuses for gmedia items in the database');
                                            }
                                        }
                                    }
                                    $this->msg[] = sprintf(__('Album `%s` assigned to %d item(s)', 'grand-media'), esc_html($term->name), count($item_ids));
                                }
                            }
                        }

                        $this->clear_selected_items($cookie_key);
                        $this->selected_items = $this->selected_items(self::$cookie_key);
                    }
                } else{
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                }
            }
            if(isset($_POST['assign_category'])){
                check_admin_referer('gmedia_action', '_wpnonce_action');
                if($gmCore->caps['gmedia_terms']){
                    $cookie_key = $gmCore->_post('cookie_key', self::$cookie_key);
                    $ids        = $this->selected_items($cookie_key);
                    if( !$gmCore->caps['gmedia_edit_others_media']){
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $ids));
                        if(count($selected_items) < count($ids)){
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else{
                        $selected_items = $ids;
                    }
                    $term = $gmCore->_post('cat_names');
                    $term = explode(',', $term);
                    if( !empty($term) && ($count = count($selected_items))){
                        foreach($selected_items as $item){
                            $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_category', $append = 1);
                            if(is_wp_error($result)){
                                $this->error[] = $result;
                                $count --;
                            } elseif( !$result){
                                $count --;
                            }
                        }

                        $this->msg[] = sprintf(__("Categories assigned to %d image(s).", 'grand-media'), $count);

                        $this->clear_selected_items($cookie_key);
                        $this->selected_items = $this->selected_items(self::$cookie_key);
                    }
                } else{
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                }
            }
            if(isset($_POST['unassign_category'])){
                check_admin_referer('gmedia_action', '_wpnonce_action');
                if(($term = $gmCore->_post('category_id')) && $gmCore->caps['gmedia_terms']){
                    $cookie_key = $gmCore->_post('cookie_key', self::$cookie_key);
                    $ids        = $this->selected_items($cookie_key);
                    if( !$gmCore->caps['gmedia_edit_others_media']){
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $ids));
                        if(count($selected_items) < count($ids)){
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else{
                        $selected_items = $ids;
                    }
                    $term = array_map('intval', $term);
                    if(($count = count($selected_items))){
                        foreach($selected_items as $item){
                            $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_category', $append = - 1);
                            if(is_wp_error($result)){
                                $this->error[] = $result;
                                $count --;
                            } elseif( !$result){
                                $count --;
                            }
                        }
                        $this->msg[] = sprintf(__('%d category(ies) deleted from %d item(s)', 'grand-media'), count($term), $count);

                        $this->clear_selected_items($cookie_key);
                        $this->selected_items = $this->selected_items(self::$cookie_key);
                    }
                } else{
                    $this->error[] = __('You are not allowed to assign terms', 'gmedia_grand-media');
                }
            }
            if(isset($_POST['add_tags'])){
                check_admin_referer('gmedia_action', '_wpnonce_action');
                if( !$gmCore->caps['gmedia_terms']){
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                } else{
                    $term      = $gmCore->_post('tag_names');
                    $iptc_tags = $gmCore->_post('iptc_tags');
                    if($term || $iptc_tags){
                        $cookie_key = $gmCore->_post('cookie_key', self::$cookie_key);
                        $ids        = $this->selected_items($cookie_key);
                        if( !$gmCore->caps['gmedia_edit_others_media']){
                            $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $ids));
                            if(count($selected_items) < count($ids)){
                                $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                            }
                        } else{
                            $selected_items = $ids;
                        }
                        $term = explode(',', $term);
                        if(($count = count($selected_items))){
                            foreach($selected_items as $item){
                                $_term = $term;
                                if($iptc_tags){
                                    $_metadata = $gmDB->get_metadata('gmedia', $item, '_metadata', true);
                                    if(isset($_metadata['image_meta']['keywords']) && is_array($_metadata['image_meta']['keywords'])){
                                        $_term = array_merge($_metadata['image_meta']['keywords'], $term);
                                    }
                                }
                                $result = $gmDB->set_gmedia_terms($item, $_term, 'gmedia_tag', $append = 1);
                                if(is_wp_error($result)){
                                    $this->error[] = $result;
                                    $count --;
                                } elseif( !$result){
                                    $count --;
                                }
                            }
                            $this->msg[] = sprintf(__('Tags added to %d item(s)', 'grand-media'), $count);

                            $this->clear_selected_items($cookie_key);
                            $this->selected_items = $this->selected_items(self::$cookie_key);
                        }
                    } else{
                        $this->error[] = __('No tags specified', 'grand-media');
                    }
                }
            }
            if(isset($_POST['delete_tags'])){
                check_admin_referer('gmedia_action', '_wpnonce_action');
                if(($term = $gmCore->_post('tag_id')) && $gmCore->caps['gmedia_terms']){
                    $cookie_key = $gmCore->_post('cookie_key', self::$cookie_key);
                    $ids        = $this->selected_items($cookie_key);
                    if( !$gmCore->caps['gmedia_edit_others_media']){
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $ids));
                        if(count($selected_items) < count($ids)){
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else{
                        $selected_items = $ids;
                    }
                    $term = array_map('intval', $term);
                    if(($count = count($selected_items))){
                        foreach($selected_items as $item){
                            $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_tag', $append = - 1);
                            if(is_wp_error($result)){
                                $this->error[] = $result;
                                $count --;
                            } elseif( !$result){
                                $count --;
                            }
                        }
                        $this->msg[] = sprintf(__('%d tag(s) deleted from %d item(s)', 'grand-media'), count($term), $count);

                        $this->clear_selected_items($cookie_key);
                        $this->selected_items = $this->selected_items(self::$cookie_key);
                    }
                } else{
                    $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                }
            }
            if(isset($_POST['batch_edit'])){
                check_admin_referer('gmedia_action', '_wpnonce_action');
                if($gmCore->caps['gmedia_edit_media']){
                    $cookie_key = $gmCore->_post('cookie_key', self::$cookie_key);
                    $ids        = $this->selected_items($cookie_key);
                    if( !$gmCore->caps['gmedia_edit_others_media']){
                        $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $ids));
                        if(count($selected_items) < count($ids)){
                            $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        }
                    } else{
                        $selected_items = $ids;
                    }
                    if(($count = count($selected_items))){
                        $batch_data       = array();
                        $b_filename       = $gmCore->_post('batch_filename');
                        $b_title          = $gmCore->_post('batch_title');
                        $b_description    = $gmCore->_post('batch_description');
                        $b_link           = $gmCore->_post('batch_link');
                        $b_status         = $gmCore->_post('batch_status');
                        $b_comment_status = $gmCore->_post('batch_comment_status');
                        if($b_status){
                            $batch_data['status'] = $b_status;
                        }
                        if($b_comment_status){
                            $batch_data['comment_status'] = $b_comment_status;
                        }
                        $b_author = $gmCore->_post('batch_author');
                        if($b_author && ('-1' != $b_author)){
                            $batch_data['author'] = $b_author;
                        }
                        $i = 0;
                        foreach($selected_items as $item){
                            $id     = (int) $item;
                            $gmedia = $gmDB->get_gmedia($id, ARRAY_A);
                            if( !$gmedia){
                                continue;
                            }
                            $item_author = (int) $gmedia['author'];

                            if('custom' == $b_filename && ($gmCore->caps['gmedia_delete_others_media'] || ($item_author == $user_ID))){
                                $filename_custom = $gmCore->_post('batch_filename_custom');
                                if( !empty($filename_custom) && ('{filename}' !== $filename_custom)){

                                    $gmuid = pathinfo($gmedia['gmuid']);

                                    $filename_vars = array('{filename}' => $gmuid['filename'], '{id}' => $gmedia['ID']);
                                    if(preg_match_all('/{index[:]?(\d+)?}/', $filename_custom, $matches_all)){
                                        foreach($matches_all[0] as $key => $matches){
                                            $strlen                    = strlen($matches_all[1][ $key ]);
                                            $index                     = intval($matches_all[1][ $key ]) + $i;
                                            $index                     = sprintf("%0{$strlen}d", $index);
                                            $filename_vars[ $matches ] = $index;
                                        }
                                    }
                                    $filename_custom = strtr($filename_custom, $filename_vars);

                                    $filename_custom = preg_replace('/[^a-z0-9_\.-]+/i', '_', $filename_custom);
                                    if($filename_custom && $filename_custom != $gmuid['filename']){
                                        $fileinfo = $gmCore->fileinfo($filename_custom . '.' . $gmuid['extension']);
                                        if(false !== $fileinfo){
                                            if('image' == $fileinfo['dirname']){
                                                /** WordPress Image Administration API */
                                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                                if(file_is_displayable_image($fileinfo['dirpath'] . '/' . $gmedia['gmuid'])){
                                                    if(is_file($fileinfo['dirpath_original'] . '/' . $gmedia['gmuid'])){
                                                        @rename($fileinfo['dirpath_original'] . '/' . $gmedia['gmuid'], $fileinfo['filepath_original']);
                                                    }
                                                    @rename($fileinfo['dirpath_thumb'] . '/' . $gmedia['gmuid'], $fileinfo['filepath_thumb']);
                                                }
                                            }
                                            if(@rename($fileinfo['dirpath'] . '/' . $gmedia['gmuid'], $fileinfo['filepath'])){
                                                $gmedia['gmuid']     = $fileinfo['basename'];
                                                $batch_data['gmuid'] = $fileinfo['basename'];
                                            }
                                        }
                                    }
                                }
                            }
                            switch($b_title){
                                case 'empty':
                                    $batch_data['title'] = '';
                                    break;
                                case 'filename':
                                    $title               = pathinfo($gmedia['gmuid'], PATHINFO_FILENAME);
                                    $batch_data['title'] = str_replace('_', ' ', $title);
                                    if($gmGallery->options['name2title_capitalize']){
                                        $batch_data['title'] = $gmCore->mb_ucwords_utf8($batch_data['title']);
                                    }
                                    break;
                                case 'custom':
                                    $title_custom = $gmCore->_post('batch_title_custom');
                                    if(false !== $title_custom){
                                        $batch_data['title'] = $title_custom;
                                    }
                                    break;
                            }
                            switch($b_description){
                                case 'empty':
                                    $batch_data['description'] = '';
                                    break;
                                case 'metadata':
                                    $metatext = $gmCore->metadata_text($id);
                                    if($gmedia['description']){
                                        $gmedia['description'] .= "\n";
                                    }
                                    $batch_data['description'] = $gmedia['description'] . $metatext;
                                    break;
                                case 'custom':
                                    $description_custom = $gmCore->_post('batch_description_custom');
                                    if(false !== $description_custom){
                                        $what_description_custom = $gmCore->_post('what_description_custom');
                                        if('replace' == $what_description_custom){
                                            $batch_data['description'] = $description_custom;
                                        } elseif('append' == $what_description_custom){
                                            $batch_data['description'] = $gmedia['description'] . $description_custom;
                                        } elseif('prepend' == $what_description_custom){
                                            $batch_data['description'] = $description_custom . $gmedia['description'];
                                        }
                                    }
                                    break;
                            }
                            switch($b_link){
                                case 'empty':
                                    $batch_data['link'] = '';
                                    break;
                                case 'self':
                                    $fileinfo           = $gmCore->fileinfo($gmedia['gmuid'], false);
                                    $fileurl            = is_file($fileinfo['filepath_original'])? $fileinfo['fileurl_original'] : $fileinfo['fileurl'];
                                    $batch_data['link'] = $fileurl;
                                    break;
                                case 'custom':
                                    $link_custom = $gmCore->_post('batch_link_custom');
                                    if(false !== $link_custom){
                                        $batch_data['link'] = $link_custom;
                                    }
                                    break;
                            }
                            if( !empty($batch_data)){
                                $batch_data['modified'] = current_time('mysql');
                                $gmedia_data            = array_merge($gmedia, $batch_data);
                                $gmDB->insert_gmedia($gmedia_data);
                            } else{
                                $count --;
                            }

                            $i ++;
                        }
                        $this->msg[] = sprintf(__('%d item(s) updated successfully', 'grand-media'), $count);

                        $this->clear_selected_items($cookie_key);
                        $this->selected_items = $this->selected_items(self::$cookie_key);
                    }
                } else{
                    $this->error[] = __('You are not allowed to edit media', 'grand-media');
                }
            }

            if($do_gmedia){
                if('unassign_album' == $do_gmedia){
                    check_admin_referer('gmedia_action', '_wpnonce_action');
                    if($gmCore->caps['gmedia_terms']){
                        $cookie_key = $gmCore->_post('cookie_key', self::$cookie_key);
                        $ids        = $this->selected_items($cookie_key);
                        if( !$gmCore->caps['gmedia_edit_others_media']){
                            $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $ids));
                            if(count($selected_items) < count($ids)){
                                $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                            }
                        } else{
                            $selected_items = $ids;
                        }
                        if(($count = count($selected_items))){
                            foreach($selected_items as $item){
                                $gmDB->delete_gmedia_term_relationships($item, 'gmedia_album');
                            }
                            $this->msg[] = sprintf(__('%d item(s) updated with "No Album"', 'grand-media'), $count);
                            set_transient('gmedia_action_msg', $this->msg, 30);

                            $this->clear_selected_items($cookie_key);
                            $this->selected_items = $this->selected_items(self::$cookie_key);
                        }
                    } else{
                        $this->error[] = __('You are not allowed to assign terms', 'grand-media');
                        set_transient('gmedia_action_error', $this->error, 30);
                    }
                }
                if('update_meta' == $do_gmedia){
                    check_admin_referer('gmedia_action', '_wpnonce_action');
                    if($gmCore->caps['gmedia_edit_media']){
                        $cookie_key     = $gmCore->_post('cookie_key', self::$cookie_key);
                        $selected_items = $this->selected_items($cookie_key);
                        $count          = count($selected_items);
                        if($count){
                            foreach($selected_items as $item){
                                $id             = (int) $item;
                                $media_metadata = $gmDB->generate_gmedia_metadata($id);
                                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $media_metadata);
                                if( !empty($media_metadata['image_meta']['created_timestamp'])){
                                    $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_created_timestamp', $media_metadata['image_meta']['created_timestamp']);
                                }
                                if( !empty($media_metadata['image_meta']['GPS'])){
                                    $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_gps', $media_metadata['image_meta']['GPS']);
                                }
                            }
                            $this->msg[] = sprintf(__('%d item(s) updated successfully', 'grand-media'), $count);
                            set_transient('gmedia_action_msg', $this->msg, 30);
                        }
                        $this->clear_selected_items($cookie_key);
                        $this->selected_items = $this->selected_items(self::$cookie_key);
                    } else{
                        $this->error[] = __('You are not allowed to edit media', 'grand-media');
                        set_transient('gmedia_action_error', $this->error, 30);
                    }
                }
                if('recreate' == $do_gmedia){
                    check_admin_referer('gmedia_action', '_wpnonce_action');
                    if($gmCore->caps['gmedia_edit_media']){
                        $cookie_key     = $gmCore->_post('cookie_key', self::$cookie_key);
                        $selected_items = $this->selected_items($cookie_key);
                        $count          = count($selected_items);
                        if($count){
                            if( !$gmCore->caps['gmedia_edit_others_media']){
                                $edit_items     = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'mime_type' => 'image', 'gmedia__in' => $selected_items));
                                $selected_items = $edit_items;
                            } else{
                                $selected_items = $gmDB->get_gmedias(array('fields' => 'ids', 'mime_type' => 'image', 'gmedia__in' => $selected_items));
                            }
                            if(($count = count($selected_items))){
                                $ajax_operations = get_option('gmedia_ajax_long_operations', array());
                                foreach($selected_items as $si){
                                    $ajax_operations['gmedia_recreate_images'][ $si ] = $si;
                                }
                                update_option('gmedia_ajax_long_operations', $ajax_operations);
                                $this->msg[] = sprintf(__('You\'ve added %d image(s) to the re-creation queue.', 'grand-media'), $count);
                                set_transient('gmedia_action_msg', $this->msg, 30);
                            }
                        }
                        $this->clear_selected_items($cookie_key);
                        $this->selected_items = $this->selected_items(self::$cookie_key);
                    } else{
                        $this->error[] = __('You are not allowed to edit media', 'grand-media');
                        set_transient('gmedia_action_error', $this->error, 30);
                    }
                }
            }
        }
        if('duplicate' == $do_gmedia){
            check_admin_referer('gmedia_action', '_wpnonce_action');
            if($gmCore->caps['gmedia_upload'] || $gmCore->caps['gmedia_import']){
                $ids            = $gmCore->_get('ids', 'selected');
                $cookie_key     = $gmCore->_post('cookie_key', self::$cookie_key);
                $selected_items = ('selected' == $ids)? $this->selected_items($cookie_key) : wp_parse_id_list($ids);
                if( !empty($selected_items)){
                    if(($count = count($selected_items))){
                        foreach($selected_items as $gmid){
                            $gmCore->duplicate_gmedia($gmid);
                        }
                        $this->msg[] = sprintf(__('%d item was duplicated', 'grand-media'), $count);
                        set_transient('gmedia_action_msg', $this->msg, 30);
                    }
                }
            } else{
                $this->error[] = __('You are not allowed to import files', 'grand-media');
                set_transient('gmedia_action_error', $this->error, 30);
            }
        }

        if('delete' == $do_gmedia || 'delete__save_original' == $do_gmedia){
            check_admin_referer('gmedia_delete', '_wpnonce_delete');
            if($gmCore->caps['gmedia_delete_media']){
                $ids            = $gmCore->_get('ids', 'selected');
                $cookie_key     = $gmCore->_post('cookie_key', self::$cookie_key);
                $selected_items = ('selected' == $ids)? $this->selected_items($cookie_key) : wp_parse_id_list($ids);
                if( !empty($selected_items)){
                    if( !$gmCore->caps['gmedia_delete_others_media']){
                        $delete_items = $gmDB->get_gmedias(array('fields' => 'ids', 'author' => $user_ID, 'gmedia__in' => $selected_items));
                        if(count($delete_items) < count($selected_items)){
                            $this->error[] = __('You are not allowed to delete others media', 'grand-media');
                        }
                        $selected_items = $delete_items;
                    }
                    if(($count = count($selected_items))){
                        $delete_original_file = !('delete__save_original' == $do_gmedia);
                        foreach($selected_items as $item){
                            if( !$gmDB->delete_gmedia((int) $item, $delete_original_file)){
                                $this->error[] = "#{$item}: " . __('Error in deleting...', 'grand-media');
                                $count --;
                            }
                        }
                        if($count){
                            if($delete_original_file){
                                $this->msg[] = sprintf(__('%d item(s) deleted successfully', 'grand-media'), $count);
                            } else{
                                $this->msg[] = sprintf(__('%d record(s) deleted from database successfully. Original file(s) safe', 'grand-media'), $count);
                            }
                        }
                        $this->selected_items = array_diff($this->selected_items, $selected_items);
                        if(empty($this->selected_items)){
                            $this->clear_selected_items(self::$cookie_key);
                        } else{
                            setcookie(self::$cookie_key, implode('.', $this->selected_items));
                        }
                        if($cookie_key != self::$cookie_key){
                            if('selected' == $ids){
                                $this->clear_selected_items($cookie_key);
                            } else{
                                $_selected_items = $this->selected_items($cookie_key);
                                $_selected_items = array_diff($_selected_items, $selected_items);
                                if(empty($_selected_items)){
                                    $this->clear_selected_items($cookie_key);
                                } else{
                                    setcookie($cookie_key, implode('.', $_selected_items));
                                }
                            }
                        }
                        if( !empty($this->stack_items)){
                            $this->stack_items = array_diff($this->stack_items, $selected_items);
                            if(empty($this->stack_items)){
                                $this->clear_selected_items("gmedia_{$user_ID}_libstack");;
                            } else{
                                setcookie("gmedia_{$user_ID}_libstack", implode('.', $this->stack_items));
                            }
                        }
                    }
                }
            } else{
                $this->error[] = __('You are not allowed to delete files', 'grand-media');
            }
            if( !empty($this->msg)){
                set_transient('gmedia_action_msg', $this->msg, 30);
            }
            if( !empty($this->error)){
                set_transient('gmedia_action_error', $this->error, 30);
            }
        }
        if($do_gmedia){
            $_wpnonce = array();
            foreach($_GET as $key => $value){
                if(strpos($key, '_wpnonce') !== false){
                    $_wpnonce[ $key ] = $value;
                }
            }
            $remove_args = array_merge(array('do_gmedia', 'ids'), $_wpnonce);
            $location    = remove_query_arg($remove_args);
            $location    = add_query_arg('did_gmedia', $do_gmedia, $location);
            wp_redirect($location);
            exit;
        }
        if($gmCore->_get('did_gmedia')){
            $msg = get_transient('gmedia_action_msg');
            if($msg){
                delete_transient('gmedia_action_msg');
                $this->msg = (array) $msg;
            }
            $error = get_transient('gmedia_action_error');
            if($error){
                delete_transient('gmedia_action_error');
                $this->error = (array) $error;
            }
        }
    }

    public function admin_enqueue_scripts(){
        if('edit' == $this->mode){
            if('false' == $this->user_options['library_edit_quicktags']){
                wp_enqueue_script('wplink');
                wp_enqueue_style('editor-buttons');

                add_action('admin_footer', array($this, 'wpLink'));
            }
        }
    }

    public function wpLink(){
        if( !class_exists('_WP_Editors', false)){
            require_once(ABSPATH . WPINC . '/class-wp-editor.php');
        }
        _WP_Editors::wp_link_dialog();
    }

    public static function getMe(){
        if(self::$me == null){
            self::$me = new GmediaProcessor_Library();
        }

        return self::$me;
    }
}

global $gmProcessorLibrary;
$gmProcessorLibrary = GmediaProcessor_Library::getMe();
