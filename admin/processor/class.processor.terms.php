<?php

/**
 * GmediaProcessor_Terms
 */
class GmediaProcessor_Terms extends GmediaProcessor {

    public $taxonomy;
    public static $cookie_key = false;
    public $selected_items = array();
    public $query_args = array();

    /**
     * GmediaProcessor_Library constructor.
     */
    public function __construct() {
        parent::__construct();

        global $user_ID;

        switch($this->page){
            case 'GrandMedia_Albums':
                $this->taxonomy = 'gmedia_album';
            break;
            case 'GrandMedia_Categories':
                $this->taxonomy = 'gmedia_category';
            break;
            case 'GrandMedia_Tags':
                $this->taxonomy = 'gmedia_tag';
            break;
        }
        self::$cookie_key = "gmuser_{$user_ID}_{$this->taxonomy}";
        $this->selected_items = parent::selected_items(self::$cookie_key);

    }

    /**
     * @return array
     */
    public function query_args() {
        global $gmCore;

        $args['status']     = $gmCore->_get('status');
        $args['page']       = $gmCore->_get('pager', 1);
        $args['number']     = $gmCore->_get('per_page', $this->user_options["per_page_{$this->taxonomy}"]);
        $args['offset']     = ($args['page'] - 1) * $args['number'];
        $args['global']     = parent::filter_by_author($gmCore->_get('author'));
        $args['include']    = $gmCore->_get('include');
        $args['search']     = $gmCore->_get('s');
        $args['orderby']    = $gmCore->_get('orderby', $this->user_options["orderby_{$this->taxonomy}"]);
        $args['order']      = $gmCore->_get('order', $this->user_options["sortorder_{$this->taxonomy}"]);
        $args['hide_empty'] = $gmCore->_get('hide_empty');

        if($args['search'] && ('#' == substr($args['search'], 0, 1))) {
            $args['include'] = substr($args['search'], 1);
            $args['search']       = false;
        }

        if(('selected' == $gmCore->_req('filter')) && !empty($this->selected_items)) {
            $args['include'] = $this->selected_items;
            $args['orderby']    = $gmCore->_get('orderby', 'include');
            $args['order']      = $gmCore->_get('order', 'ASC');
        }

        $query_args = apply_filters('gmedia_terms_query_args', $args);

        foreach($query_args as $key => $val){
            if(empty($val) && ('0' !== $val) && (0 !== $val)){
                unset($query_args[$key]);
            }
        }

        return $query_args;
    }

    protected function processor() {
        global $user_ID, $gmCore, $gmDB;

        if(!$gmCore->caps['gmedia_library']) {
            wp_die(__('You are not allowed to be here', 'grand-media'));
        }

        include_once(GMEDIA_ABSPATH . 'admin/pages/terms/functions.php');

        $this->query_args = $this->query_args();

        $taxonomy = $this->taxonomy;
        switch($taxonomy){
            case 'gmedia_album':
                if(gm_user_can('album_manage')) {
                    add_action('gmedia_before_terms_list', 'gmedia_terms_create_album_tpl');
                } else {
                    add_action('gmedia_before_terms_list', 'gmedia_terms_create_alert_tpl');
                }
            break;
            case 'gmedia_category':
                if(gm_user_can('category_manage')) {
                    add_action('gmedia_before_terms_list', 'gmedia_terms_create_category_tpl');
                } else {
                    add_action('gmedia_before_terms_list', 'gmedia_terms_create_alert_tpl');
                }
            break;
            case 'gmedia_tag':
                if(gm_user_can('tag_manage')) {
                    add_action('gmedia_before_terms_list', 'gmedia_terms_create_tag_tpl');
                } else {
                    add_action('gmedia_before_terms_list', 'gmedia_terms_create_alert_tpl');
                }
            break;
        }

        if(($delete = $gmCore->_get('delete'))) {
            check_admin_referer('gmedia_delete');
            if($gmCore->caps['gmedia_terms_delete']) {
                if('selected' == $gmCore->_get('delete')) {
                    $selected_items = $this->selected_items;
                } else {
                    $selected_items = wp_parse_id_list($delete);
                }
                if(!$gmCore->caps['gmedia_delete_others_media']) {
                    $_selected_items = array();
                    if('gmedia_album' == $taxonomy) {
                        $_selected_items = $gmDB->get_terms($taxonomy, array('fields' => 'ids', 'global' => $user_ID, 'include' => $selected_items));
                    }
                    if(count($_selected_items) < count($selected_items)) {
                        $this->error[] = __('You are not allowed to delete others media', 'grand-media');
                    }
                    $selected_items = $_selected_items;
                }
                if(($count = count($selected_items))) {
                    foreach($selected_items as $item) {
                        $delete = $gmDB->delete_term($item);
                        if(!$delete) {
                            $count--;
                        } elseif(is_wp_error($delete)) {
                            $this->error[] = $delete->get_error_message();
                            $count--;
                        }
                    }
                    if($count) {
                        $this->msg[] = sprintf(__('%d item(s) deleted successfuly', 'grand-media'), $count);
                    }
                    setcookie("gmuser_{$user_ID}_{$taxonomy}", '', time() - 3600);
                    unset($_COOKIE["gmuser_{$user_ID}_{$taxonomy}"]);
                    $this->selected_items = array();
                }
            } else {
                $this->error[] = __('You are not allowed to delete terms', 'grand-media');
            }
        } elseif(isset($_POST['gmedia_album_save'])) {
            check_admin_referer('GmediaTerms', 'term_save_wpnonce');
            $edit_term = (int)$gmCore->_get('edit_item');
            do {
                if(!$gmCore->caps['gmedia_album_manage']) {
                    $this->error[] = __('You are not allowed to manage albums', 'grand-media');
                    break;
                }
                $term = $gmCore->_post('term');
                if(($meta = $gmCore->_post('meta'))) {
                    $term = array_merge_recursive(array('meta' => $meta), $term);
                }
                $term['name'] = trim($term['name']);
                if(empty($term['name'])) {
                    $this->error[] = __('Term Name is not specified', 'grand-media');
                    break;
                }
                if($gmCore->is_digit($term['name'])) {
                    $this->error[] = __("Term Name can't be only digits", 'grand-media');
                    break;
                }
                $taxonomy = 'gmedia_album';
                if($edit_term && !$gmDB->term_exists($edit_term)) {
                    $this->error[] = __('A term with the id provided do not exists', 'grand-media');
                    $edit_term     = false;
                }
                if(($term_id = $gmDB->term_exists($term['name'], $taxonomy, $term['global']))) {
                    if($term_id != $edit_term) {
                        $this->error[] = __('A term with the name provided already exists', 'grand-media');
                        break;
                    }
                }
                if($edit_term) {
                    $_term = $gmDB->get_term($edit_term);
                    if(((int)$_term->global != (int)$user_ID) && !current_user_can('gmedia_edit_others_media')) {
                        $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        break;
                    }
                    $term_id = $gmDB->update_term($edit_term, $term);
                } else {
                    $term_id = $gmDB->insert_term($term['name'], $term['taxonomy'], $term);
                }
                if(is_wp_error($term_id)) {
                    $this->error[] = $term_id->get_error_message();
                    break;
                }

                $this->msg[] = sprintf(__('Album `%s` successfuly saved', 'grand-media'), $term['name']);

            } while(0);
        } elseif(isset($_POST['gmedia_term_sort_save'])) {
            check_admin_referer('GmediaTerms', 'term_save_wpnonce');
            do {
                if(!$gmCore->caps['gmedia_album_manage']) {
                    $this->error[] = __('You are not allowed to manage albums', 'grand-media');
                    break;
                }
                $term_data = $gmCore->_post('term');
                $taxonomy  = 'gmedia_album';
                if(!($term_id = $gmDB->term_exists($term_data['term_id']))) {
                    $this->error[] = __('A term with the id provided do not exists', 'grand-media');
                    break;
                } else {
                    $_term = $gmDB->get_term($term_id);
                    if(((int)$_term->global != (int)$user_ID) && !current_user_can('gmedia_edit_others_media')) {
                        $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        break;
                    }
                    //$term_meta = $term_data['meta'];
                    $term_id = $gmDB->update_term_sortorder($term_id, $term_data);

                    if(is_wp_error($term_id)) {
                        $this->error[] = $term_id->get_error_message();
                        break;
                    }

                    $this->msg[] = sprintf(__('Album `%s` successfuly saved', 'grand-media'), $_term->name);
                }

            } while(0);
        } elseif(isset($_POST['gmedia_category_save'])) {
            check_admin_referer('GmediaTerms', 'term_save_wpnonce');
            $edit_term = (int)$gmCore->_get('edit_item');
            do {
                if(!$gmCore->caps['gmedia_category_manage']) {
                    $this->error[] = __('You are not allowed to manage categories', 'grand-media');
                    break;
                }
                $term = $gmCore->_post('term');
                if(($meta = $gmCore->_post('meta'))) {
                    $term = array_merge_recursive(array('meta' => $meta), $term);
                }
                $term['name'] = trim($term['name']);
                if(empty($term['name'])) {
                    $this->error[] = __('Term Name is not specified', 'grand-media');
                    break;
                }
                if($gmCore->is_digit($term['name'])) {
                    $this->error[] = __("Term Name can't be only digits", 'grand-media');
                    break;
                }
                $taxonomy = 'gmedia_category';
                if($edit_term && !$gmDB->term_exists($edit_term)) {
                    $this->error[] = __('A term with the id provided do not exists', 'grand-media');
                    $edit_term     = false;
                }
                if(($term_id = $gmDB->term_exists($term['name'], $taxonomy))) {
                    if($term_id != $edit_term) {
                        $this->error[] = __('A term with the name provided already exists', 'grand-media');
                        break;
                    }
                }
                if($edit_term) {
                    if(!current_user_can('gmedia_edit_others_media')) {
                        $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        break;
                    }
                    $term_id = $gmDB->update_term($edit_term, $term);
                } else {
                    $term_id = $gmDB->insert_term($term['name'], $term['taxonomy'], $term);
                }
                if(is_wp_error($term_id)) {
                    $this->error[] = $term_id->get_error_message();
                    break;
                }

                $this->msg[] = sprintf(__('Category `%s` successfuly saved', 'grand-media'), $term['name']);

            } while(0);
        } elseif(isset($_POST['gmedia_tag_add'])) {
            if($gmCore->caps['gmedia_tag_manage']) {
                check_admin_referer('GmediaTerms', 'term_save_wpnonce');
                $term        = $gmCore->_post('term');
                $terms       = array_filter(array_map('trim', explode(',', $term['name'])));
                $terms_added = 0;
                $terms_qty   = count($terms);
                foreach($terms as $term_name) {
                    if($gmCore->is_digit($term_name)) {
                        $this->error['tag_name_digit'] = __("Term Name can't be only digits", 'grand-media');
                        continue;
                    }

                    if(!$gmDB->term_exists($term_name, $term['taxonomy'])) {
                        $term_id = $gmDB->insert_term($term_name, $term['taxonomy']);
                        if(is_wp_error($term_id)) {
                            $this->error[] = $term_id->get_error_message();
                        } else {
                            $this->msg['tag_add'] = sprintf(__('%d of %d tags successfuly added', 'grand-media'), ++$terms_added, $terms_qty);
                        }
                    } else {
                        $this->error['tag_add'] = __('Some of provided tags are already exists', 'grand-media');
                    }
                }
            } else {
                $this->error[] = __('You are not allowed to manage tags', 'grand-media');
            }
        }
    }

}

global $gmProcessor;
$gmProcessor = new GmediaProcessor_Terms();
