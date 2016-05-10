<?php

/**
 * GmediaProcessor_Galleries
 */
class GmediaProcessor_Galleries extends GmediaProcessor {

    public $taxonomy;
    public static $cookie_key = false;
    public $selected_items = array();
    public $query_args = array();

    /**
     * GmediaProcessor_Library constructor.
     */
    public function __construct() {
        global $user_ID;

        $this->taxonomy   = 'gmedia_gallery';
        self::$cookie_key = "gmuser_{$user_ID}_{$this->taxonomy}";
        $this->selected_items = parent::selected_items(self::$cookie_key);

        parent::__construct();
    }

    /**
     * @return array
     */
    public function query_args() {
        global $gmCore;

        $args['status']     = $gmCore->_get('status');
        $args['page']       = $gmCore->_get('pager', 1);
        $args['number']     = $gmCore->_get('per_page', $this->user_options['per_page_gmedia_gallery']);
        $args['offset']     = ($args['page'] - 1) * $args['number'];
        $args['global']     = parent::filter_by_author($gmCore->_get('author'));
        $args['include']    = $gmCore->_get('include');
        $args['search']     = $gmCore->_get('s');
        $args['orderby']    = $gmCore->_get('orderby', $this->user_options['orderby_gmedia_gallery']);
        $args['order']      = $gmCore->_get('order', $this->user_options['sortorder_gmedia_gallery']);

        if($args['search'] && ('#' == substr($args['search'], 0, 1))) {
            $args['include'] = substr($args['search'], 1);
            $args['search']       = false;
        }

        if(('selected' == $gmCore->_req('filter')) && !empty($this->selected_items)) {
            $args['include'] = $this->selected_items;
            $args['orderby']    = $gmCore->_get('orderby', 'include');
            $args['order']      = $gmCore->_get('order', 'ASC');
        }

        $query_args = apply_filters('gmedia_gallery_query_args', $args);
        $query_args['hide_empty'] = false;

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

        if(!$gmCore->caps['gmedia_gallery_manage']) {
            wp_die(__('You are not allowed to manage gmedia galleries', 'grand-media'));
        }

        include_once(GMEDIA_ABSPATH . 'admin/pages/galleries/functions.php');

        $this->query_args = $this->query_args();

        if(isset($_POST['select_author'])) {
            $authors  = $gmCore->_post('author_ids');
            $location = $gmCore->get_admin_url(array('author' => (int)$authors));
            wp_redirect($location);
            exit;
        }
        if(isset($_POST['gmedia_gallery_save'])) {
            check_admin_referer('GmediaGallery');
            $edit_gallery = (int)$gmCore->_get('edit_item');
            do {
                $term = $gmCore->_post('term');
                if(((int)$term['global'] != $user_ID) && !$gmCore->caps['gmedia_edit_others_media']) {
                    $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                    break;
                }
                $term['name'] = trim($term['name']);
                if(empty($term['name'])) {
                    $this->error[] = __('Gallery Name is not specified', 'grand-media');
                    break;
                }
                if($gmCore->is_digit($term['name'])) {
                    $this->error[] = __("Gallery name can't be only digits", 'grand-media');
                    break;
                }
                if(empty($term['module'])) {
                    $this->error[] = __('Something goes wrong... Choose module, please', 'grand-media');
                    break;
                }
                $taxonomy = 'gmedia_gallery';
                if($edit_gallery && !$gmDB->term_exists($edit_gallery)) {
                    $this->error[] = __('A term with the id provided do not exists', 'grand-media');
                    $edit_gallery  = false;
                }
                if(($term_id = $gmDB->term_exists($term['name'], $taxonomy, $term['global']))) {
                    if($term_id != $edit_gallery) {
                        $this->error[] = __('A term with the name provided already exists', 'grand-media');
                        break;
                    }
                }
                if(($meta = $gmCore->_post('meta'))) {
                    $term = array_merge_recursive(array('meta' => $meta), $term);
                }
                if($edit_gallery) {
                    $term_id = $gmDB->update_term($edit_gallery, $term);
                } else {
                    $term_id = $gmDB->insert_term($term['name'], $taxonomy, $term);
                }
                if(is_wp_error($term_id)) {
                    $this->error[] = $term_id->get_error_message();
                    break;
                }

                $module_settings = $gmCore->_post('module', array());
                $module_path     = $gmCore->get_module_path($term['module']);
                $default_options = array();
                if(file_exists($module_path['path'] . '/settings.php')) {
                    /** @noinspection PhpIncludeInspection */
                    include($module_path['path'] . '/settings.php');
                } else {
                    $this->error[] = sprintf(__('Can\'t load data from `%s` module'), $term['module']);
                    break;
                }
                $module_settings = $gmCore->array_replace_recursive($default_options, $module_settings);
                wp_parse_str($term['query'], $_query);
                $gallery_meta    = array(
                    '_edited'   => gmdate('Y-m-d H:i:s'),
                    '_query'    => $_query,
                    '_module'   => $term['module'],
                    '_settings' => array($term['module'] => $module_settings)
                );
                foreach($gallery_meta as $key => $value) {
                    $gmDB->update_metadata('gmedia_term', $term_id, $key, $value);
                }
                if($edit_gallery) {
                    $this->msg[] = sprintf(__('Gallery #%d successfuly saved', 'grand-media'), $term_id);
                } else {
                    $location = add_query_arg(array('page' => $this->page, 'edit_item' => $term_id, 'message' => 'save'), admin_url('admin.php'));
                    set_transient('gmedia_new_gallery_id', $term_id, 60);
                    wp_redirect($location);
                    exit;
                }
            } while(0);
        }
        if(('save' == $gmCore->_get('message')) && ($term_id = $gmCore->_get('edit_item'))) {
            $gmedia_new_gallery_id = get_transient('gmedia_new_gallery_id');
            if(false !== $gmedia_new_gallery_id) {
                delete_transient('gmedia_new_gallery_id');
                $this->msg[] = sprintf(__('Gallery #%d successfuly saved', 'grand-media'), $term_id);
            }
        }

        if(isset($_POST['module_preset_restore_original'])) {
            $preset_id = intval($gmCore->_post('preset_default', 0));
            $gmDB->delete_term($preset_id);
            $this->msg[] = __('Original module settings restored. Click "Reset to default" button to save original module settings for gallery', 'grand-media');
        }

        if(isset($_POST['gmedia_gallery_reset'])) {
            check_admin_referer('GmediaGallery');
            $edit_gallery = (int)$gmCore->_get('edit_item');
            do {
                if(!$gmDB->term_exists($edit_gallery)) {
                    $this->error[] = __('A term with the id provided do not exists', 'grand-media');
                    break;
                }
                if(!$gmCore->caps['gmedia_edit_others_media']) {
                    $term = $gmDB->get_term($edit_gallery);
                    if($term->global != $user_ID) {
                        $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                        break;
                    }
                }
                $gallery_settings = $gmDB->get_metadata('gmedia_term', $edit_gallery, '_settings', true);
                reset($gallery_settings);
                $gallery_module = key($gallery_settings);
                $module_path    = $gmCore->get_module_path($gallery_module);
                /**
                 * @var $default_options
                 */
                if(file_exists($module_path['path'] . '/settings.php')) {
                    /** @noinspection PhpIncludeInspection */
                    include($module_path['path'] . '/settings.php');
                    $preset = $gmDB->get_term('[' . $gallery_module . ']', 'gmedia_module');
                    if($preset) {
                        $default_preset  = maybe_unserialize($preset->description);
                        $default_options = $gmCore->array_replace_recursive($default_options, $default_preset);
                    }
                } else {
                    $this->error[] = sprintf(__('Can\'t load data from `%s` module'), $gallery_module);
                    break;
                }

                $gallery_meta = array(
                    '_edited'   => gmdate('Y-m-d H:i:s'),
                    '_settings' => array($gallery_module => $default_options)
                );
                foreach($gallery_meta as $key => $value) {
                    $gmDB->update_metadata('gmedia_term', $edit_gallery, $key, $value);
                }
                $this->msg[] = sprintf(__('Gallery settings are reset', 'grand-media'));

            } while(0);

        }

        if(isset($_POST['module_preset_save_as']) || isset($_POST['module_preset_save_default'])) {
            check_admin_referer('GmediaGallery');
            do {
                $term = $gmCore->_post('term');
                if(empty($term['module'])) {
                    $this->error[] = __('Something goes wrong... Choose module, please', 'grand-media');
                    break;
                }
                $module_settings = $gmCore->_post('module', array());
                $module_path     = $gmCore->get_module_path($term['module']);
                $default_options = array();
                if(file_exists($module_path['path'] . '/settings.php')) {
                    /** @noinspection PhpIncludeInspection */
                    include($module_path['path'] . '/settings.php');
                } else {
                    $this->error[] = sprintf(__('Can\'t load data from `%s` module'), $term['module']);
                    break;
                }
                $module_settings = $gmCore->array_replace_recursive($default_options, $module_settings);

                $preset_name = $gmCore->_post('module_preset_name', '');
                if(isset($_POST['module_preset_save_default'])) {
                    $preset_name = '[' . $term['module'] . ']';
                } else {
                    $preset_name = trim($preset_name);
                    if(empty($preset_name)) {
                        $this->error[] = __('Preset name is not specified', 'grand-media');
                        break;
                    }
                    $preset_name = '[' . $term['module'] . '] ' . $preset_name;
                }
                $args                = array();
                $args['description'] = $module_settings;
                $args['status']      = $term['module'];
                $args['global']      = $user_ID;

                $taxonomy = 'gmedia_module';
                $term_id  = $gmDB->term_exists($preset_name, $taxonomy, $user_ID);
                if($term_id) {
                    $term_id = $gmDB->update_term($term_id, $args);
                } else {
                    $term_id = $gmDB->insert_term($preset_name, $taxonomy, $args);
                }
                if(is_wp_error($term_id)) {
                    $this->error[] = $term_id->get_error_message();
                    break;
                } else {
                    $this->msg[] = sprintf(__('Preset `%s` successfuly saved', 'grand-media'), $preset_name);
                }

            } while(0);
        }

        if(($delete = $gmCore->_get('delete'))) {
            check_admin_referer('gmedia_delete');
            $taxonomy = 'gmedia_gallery';
            if('selected' == $delete) {
                $selected_items = $this->selected_items;
            } else {
                $selected_items = wp_parse_id_list($delete);
            }
            if(!$gmCore->caps['gmedia_delete_others_media']) {
                $_selected_items = $gmDB->get_terms($taxonomy, array('fields' => 'ids', 'global' => $user_ID, 'include' => $selected_items));
                if(count($_selected_items) < count($selected_items)) {
                    $this->error[] = __('You are not allowed to delete others media', 'grand-media');
                }
                $selected_items = $_selected_items;
            }
            if(($count = count($selected_items))) {
                foreach($selected_items as $item) {
                    $delete = $gmDB->delete_term($item);
                    if(!$delete) {
                        $this->error[] = sprintf(__('Error while delete gallery #%d', 'grand-media'), $item);
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
        }

    }

}

global $gmProcessor;
$gmProcessor = new GmediaProcessor_Galleries();
