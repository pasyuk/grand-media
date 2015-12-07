<?php

/**
 * GmediaProcessor_Galleries
 */
class GmediaProcessor_Galleries extends GmediaProcessor {

    public $taxonomy;
    public static $cookie_key = false;
    public $selected_items = array();

    /**
     * GmediaProcessor_Library constructor.
     */
    public function __construct() {
        global $user_ID, $gmCore;

        $this->taxonomy   = $gmCore->_get('term', 'gmedia_gallery');
        self::$cookie_key = "gmuser_{$user_ID}_{$this->taxonomy}";
        $this->selected_items = parent::selected_items(self::$cookie_key);

        parent::__construct();
    }

    protected function processor() {
        global $user_ID, $gmCore, $gmDB;

        if(!$gmCore->caps['gmedia_gallery_manage']) {
            wp_die(__('You are not allowed to manage gmedia galleries', 'grand-media'));
        }
        if(isset($_POST['select_author'])) {
            $authors  = $gmCore->_post('author_ids');
            $location = $gmCore->get_admin_url(array('author' => (int)$authors));
            wp_redirect($location);
            exit;
        }
        if(isset($_POST['gmedia_gallery_save'])) {
            check_admin_referer('GmediaGallery');
            $edit_gallery = (int)$gmCore->_get('edit_gallery');
            do {
                $gallery = $gmCore->_post('gallery');
                if(((int)$gallery['global'] != $user_ID) && !$gmCore->caps['gmedia_edit_others_media']) {
                    $this->error[] = __('You are not allowed to edit others media', 'grand-media');
                    break;
                }
                $gallery['name'] = trim($gallery['name']);
                if(empty($gallery['name'])) {
                    $this->error[] = __('Gallery Name is not specified', 'grand-media');
                    break;
                }
                if($gmCore->is_digit($gallery['name'])) {
                    $this->error[] = __("Gallery name can't be only digits", 'grand-media');
                    break;
                }
                if(empty($gallery['module'])) {
                    $this->error[] = __('Something goes wrong... Choose module, please', 'grand-media');
                    break;
                }
                $term = $gallery['term'];
                if(!isset($gallery['query'][$term]) || empty($gallery['query'][$term])) {
                    $this->error[] = __('Choose gallery source, please (tags, albums, categories...)', 'grand-media');
                    break;
                }
                $taxonomy = 'gmedia_gallery';
                if($edit_gallery && !$gmDB->term_exists($edit_gallery, $taxonomy)) {
                    $this->error[] = __('A term with the id provided do not exists', 'grand-media');
                    $edit_gallery  = false;
                }
                if(($term_id = $gmDB->term_exists($gallery['name'], $taxonomy, $gallery['global']))) {
                    if($term_id != $edit_gallery) {
                        $this->error[] = __('A term with the name provided already exists', 'grand-media');
                        break;
                    }
                }
                if($edit_gallery) {
                    $term_id = $gmDB->update_term($edit_gallery, $taxonomy, $gallery);
                } else {
                    $term_id = $gmDB->insert_term($gallery['name'], $taxonomy, $gallery);
                }
                if(is_wp_error($term_id)) {
                    $this->error[] = $term_id->get_error_message();
                    break;
                }

                $module_settings = $gmCore->_post('module', array());
                $module_path     = $gmCore->get_module_path($gallery['module']);
                $default_options = array();
                if(file_exists($module_path['path'] . '/settings.php')) {
                    /** @noinspection PhpIncludeInspection */
                    include($module_path['path'] . '/settings.php');
                } else {
                    $this->error[] = sprintf(__('Can\'t load data from `%s` module'), $gallery['module']);
                    break;
                }
                $module_settings = $gmCore->array_replace_recursive($default_options, $module_settings);
                $gallery_meta    = array(
                    '_edited'   => gmdate('Y-m-d H:i:s'),
                    '_module'   => $gallery['module'],
                    '_query'    => array($term => $gallery['query'][$term]),
                    '_settings' => array($gallery['module'] => $module_settings)
                );
                foreach($gallery_meta as $key => $value) {
                    if($edit_gallery) {
                        $gmDB->update_metadata('gmedia_term', $term_id, $key, $value);
                    } else {
                        $gmDB->add_metadata('gmedia_term', $term_id, $key, $value);
                    }
                }
                if($edit_gallery) {
                    $this->msg[] = sprintf(__('Gallery #%d successfuly saved', 'grand-media'), $term_id);
                } else {
                    $location = add_query_arg(array('page' => $this->page, 'edit_gallery' => $term_id, 'message' => 'save'), admin_url('admin.php'));
                    set_transient('gmedia_new_gallery_id', $term_id, 60);
                    wp_redirect($location);
                    exit;
                }
            } while(0);
        }
        if(('save' == $gmCore->_get('message')) && ($term_id = $gmCore->_get('edit_gallery'))) {
            $gmedia_new_gallery_id = get_transient('gmedia_new_gallery_id');
            if(false !== $gmedia_new_gallery_id) {
                delete_transient('gmedia_new_gallery_id');
                $this->msg[] = sprintf(__('Gallery #%d successfuly saved', 'grand-media'), $term_id);
            }
        }

        if(isset($_POST['module_preset_restore_original'])) {
            $preset_id = intval($gmCore->_post('preset_default', 0));
            $gmDB->delete_term($preset_id, 'gmedia_module');
            $this->msg[] = __('Original module settings restored. Click "Reset to default" button to save original module settings for gallery', 'grand-media');
        }

        if(isset($_POST['gmedia_gallery_reset'])) {
            check_admin_referer('GmediaGallery');
            $edit_gallery = (int)$gmCore->_get('edit_gallery');
            do {
                $taxonomy = 'gmedia_gallery';
                if(!$gmDB->term_exists($edit_gallery, $taxonomy)) {
                    $this->error[] = __('A term with the id provided do not exists', 'grand-media');
                    break;
                }
                if(!$gmCore->caps['gmedia_edit_others_media']) {
                    $gallery = $gmDB->get_term($edit_gallery, $taxonomy);
                    if($gallery->global != $user_ID) {
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
                $gallery = $gmCore->_post('gallery');
                if(empty($gallery['module'])) {
                    $this->error[] = __('Something goes wrong... Choose module, please', 'grand-media');
                    break;
                }
                $module_settings = $gmCore->_post('module', array());
                $module_path     = $gmCore->get_module_path($gallery['module']);
                $default_options = array();
                if(file_exists($module_path['path'] . '/settings.php')) {
                    /** @noinspection PhpIncludeInspection */
                    include($module_path['path'] . '/settings.php');
                } else {
                    $this->error[] = sprintf(__('Can\'t load data from `%s` module'), $gallery['module']);
                    break;
                }
                $module_settings = $gmCore->array_replace_recursive($default_options, $module_settings);

                $preset_name = $gmCore->_post('module_preset_name', '');
                if(isset($_POST['module_preset_save_default'])) {
                    $preset_name = '[' . $gallery['module'] . ']';
                } else {
                    $preset_name = trim($preset_name);
                    if(empty($preset_name)) {
                        $this->error[] = __('Preset name is not specified', 'grand-media');
                        break;
                    }
                    $preset_name = '[' . $gallery['module'] . '] ' . $preset_name;
                }
                $args                = array();
                $args['description'] = $module_settings;
                $args['status']      = $gallery['module'];
                $args['global']      = $user_ID;

                $taxonomy = 'gmedia_module';
                $term_id  = $gmDB->term_exists($preset_name, $taxonomy, $user_ID);
                if($term_id) {
                    $term_id = $gmDB->update_term($term_id, $taxonomy, $args);
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
                    $delete = $gmDB->delete_term($item, $taxonomy);
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
