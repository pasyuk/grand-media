<?php
add_action('wp_ajax_gmedia_update_data', 'gmedia_update_data');
function gmedia_update_data() {
    global $gmDB, $gmCore;
    check_ajax_referer("GmediaGallery");
    if(!current_user_can('gmedia_edit_media')) {
        die('-1');
    }

    $data = $gmCore->_post('data');

    wp_parse_str($data, $gmedia);

    if(!empty($gmedia['ID'])) {
        $item = $gmDB->get_gmedia($gmedia['ID']);
        if((int)$item->author != get_current_user_id()) {
            if(!current_user_can('gmedia_edit_others_media')) {
                die('-2');
            }
        }

        $gmedia['modified']  = current_time('mysql');
        $gmedia['mime_type'] = $item->mime_type;
        $gmedia['gmuid']     = $item->gmuid;
        if(!current_user_can('gmedia_delete_others_media')) {
            $gmedia['author'] = $item->author;
        }

        $gmuid = pathinfo($item->gmuid);

        $gmedia['filename'] = preg_replace('/[^a-z0-9_\.-]+/i', '_', $gmedia['filename']);
        if(($gmedia['filename'] != $gmuid['filename']) && (current_user_can('gmedia_delete_others_media') || ((int)$item->author == get_current_user_id()))) {
            $fileinfo = $gmCore->fileinfo($gmedia['filename'] . '.' . $gmuid['extension']);
            if(false !== $fileinfo) {
                if('image' == $fileinfo['dirname'] && file_is_displayable_image($fileinfo['dirpath'] . '/' . $item->gmuid)) {
                    @rename($fileinfo['dirpath_original'] . '/' . $item->gmuid, $fileinfo['filepath_original']);
                    @rename($fileinfo['dirpath_thumb'] . '/' . $item->gmuid, $fileinfo['filepath_thumb']);
                }
                if(@rename($fileinfo['dirpath'] . '/' . $item->gmuid, $fileinfo['filepath'])) {
                    $gmedia['gmuid'] = $fileinfo['basename'];
                }
            }
        }
        if(!current_user_can('gmedia_terms')) {
            unset($gmedia['terms']);
        }

        $id = $gmDB->insert_gmedia($gmedia);
        if(!is_wp_error($id)) {
            // Meta Stuff
            if(isset($gmedia['meta']) && is_array($gmedia['meta'])) {
                $meta_error = array();
                foreach($gmedia['meta'] as $key => $value) {
                    if($gmCore->is_digit($key)) {
                        $mid = (int)$key;
                        //$value = wp_unslash( $value );
                        if(!($meta = $gmDB->get_metadata_by_mid('gmedia', $mid))) {
                            $meta_error[] = array(
                                    'error'    => 'no_meta',
                                    'message'  => __('No record in DataBase.', 'grand-media'),
                                    'meta_id'  => $mid,
                                    'meta_key' => $meta->meta_key
                            );
                            continue;
                        }
                        if('' == trim($value)) {
                            $meta_error[] = array(
                                    'error'      => 'empty_value',
                                    'message'    => __('Please provide a custom field value.', 'grand-media'),
                                    'meta_id'    => $mid,
                                    'meta_key'   => $meta->meta_key,
                                    'meta_value' => $meta->meta_value
                            );
                            continue;
                        }

                        if($meta->meta_value != $value) {
                            if(!($u = $gmDB->update_metadata_by_mid('gmedia', $mid, $value))) {
                                $meta_error[] = array(
                                        'error'      => 'meta_update',
                                        'message'    => __('Something goes wrong.', 'grand-media'),
                                        'meta_id'    => $mid,
                                        'meta_key'   => $meta->meta_key,
                                        'meta_value' => $meta->meta_value
                                );
                            }
                        }
                    } elseif(is_protected_meta($key)) {
                        if('_cover' == $key) {
                            $value = ltrim($value, '#');
                        } elseif('_gps' == $key) {
                            if($value) {
                                $latlng = explode(',', $value);
                                $value  = array('lat' => trim($latlng[0]), 'lng' => trim($latlng[1]));
                            }
                        }
                        $value = apply_filters('gmedia_protected_meta_value', $value, $key, $id);
                        $gmDB->update_metadata('gmedia', $id, $key, $value);
                    }
                }
            }
            $result = $gmDB->get_gmedia($id);
        } else {
            $result = $gmDB->get_gmedia($id);
        }
        if(current_user_can('gmedia_terms')) {
            $tags = $gmDB->get_the_gmedia_terms($id, 'gmedia_tag');
            if($tags) {
                $tags_list = array();
                foreach($tags as $tag) {
                    $tags_list[] = $tag->name;
                }
                $result->tags = implode(', ', $tags_list);
            }
            if(!empty($gmedia['terms']['gmedia_album'])) {
                $alb_id               = (int)$gmedia['terms']['gmedia_album'];
                $alb                  = $gmDB->get_term($alb_id, 'gmedia_album');
                $result->album_status = $alb->status;
            } else {
                $result->album_status = 'none';
            }
        }
        if(!empty($meta_error)) {
            $result->meta_error = $meta_error;
        }

        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($result);
    }

    die();
}

add_action('wp_ajax_gmedit_save', 'gmedit_save');
function gmedit_save() {
    global $gmDB, $gmCore, $gmGallery;
    check_ajax_referer("gmedit-save");
    if(!current_user_can('gmedia_edit_media')) {
        die('-1');
    }

    $gmedia  = array();
    $fail    = '';
    $success = '';
    $gmid    = $gmCore->_post('id');
    $image   = $gmCore->_post('image');
    $applyto = $gmCore->_post('applyto', 'web_thumb');

    $item = $gmDB->get_gmedia($gmid);
    if(!empty($item)) {
        if((int)$item->author != get_current_user_id()) {
            if(!current_user_can('gmedia_edit_others_media')) {
                die('-2');
            }
        }
        $meta               = $gmDB->get_metadata('gmedia', $item->ID);
        $metadata           = $meta['_metadata'][0];
        $gmedia['ID']       = $gmid;
        $gmedia['date']     = $item->date;
        $gmedia['modified'] = current_time('mysql');
        $gmedia['author']   = $item->author;

        $thumbimg = $gmGallery->options['thumb'];

        $image = $gmCore->process_gmedit_image($image);

        $fileinfo = $gmCore->fileinfo($item->gmuid, false);

        $size = @getimagesize($fileinfo['filepath']);

        do {
            $extensions = array('1' => 'GIF', '2' => 'JPG', '3' => 'PNG', '6' => 'BMP');
            if(function_exists('memory_get_usage')) {
                switch($extensions[$size[2]]) {
                    case 'GIF':
                        $CHANNEL = 1;
                    break;
                    case 'JPG':
                        $CHANNEL = $size['channels'];
                    break;
                    case 'PNG':
                        $CHANNEL = 3;
                    break;
                    case 'BMP':
                    default:
                        $CHANNEL = 6;
                    break;
                }
                $MB                = 1048576;  // number of bytes in 1M
                $K64               = 65536;    // number of bytes in 64K
                $TWEAKFACTOR       = 1.8;     // Or whatever works for you
                $memoryNeeded      = round(($size[0] * $size[1] * $size['bits'] * $CHANNEL / 8 + $K64) * $TWEAKFACTOR);
                $memoryNeeded      = memory_get_usage() + $memoryNeeded;
                $current_limit     = @ini_get('memory_limit');
                $current_limit_int = intval($current_limit);
                if(false !== strpos($current_limit, 'M')) {
                    $current_limit_int *= $MB;
                }
                if(false !== strpos($current_limit, 'G')) {
                    $current_limit_int *= 1024;
                }

                if(-1 != $current_limit && $memoryNeeded > $current_limit_int) {
                    $newLimit = $current_limit_int / $MB + ceil(($memoryNeeded - $current_limit_int) / $MB);
                    @ini_set('memory_limit', $newLimit . 'M');
                }
            }

            if('thumb' == $applyto) {
                $editfile = $fileinfo['filepath_thumb'];
            } else {
                $editfile = $fileinfo['filepath'];
            }
            if(!@file_put_contents($editfile, $image['data'])) {
                $fail = $fileinfo['basename'] . ": " . __('Can\'t write to file. Permission denied', 'grand-media');
                break;
            }

            $modified = isset($meta['_modified'][0])? (intval($meta['_modified'][0]) + 1) : 1;
            $gmDB->update_metadata($meta_type = 'gmedia', $item->ID, $meta_key = '_modified', $modified);

            // Web-image
            if('thumb' !== $applyto) {
                if(('JPG' == $extensions[$size[2]]) && !(extension_loaded('imagick') || class_exists("Imagick"))) {
                    $gmCore->copy_exif($fileinfo['filepath_original'], $fileinfo['filepath']);
                }
            }
            // Thumbnail
            if('web_thumb' == $applyto) {
                $size_ratio         = $size[0] / $size[1];
                $thumbimg['resize'] = (((1 >= $size_ratio) && ($thumbimg['width'] > $size[0])) || ((1 <= $size_ratio) && ($thumbimg['height'] > $size[1])))? false : true;
                if($thumbimg['resize']) {
                    $editor = wp_get_image_editor($editfile);
                    if(is_wp_error($editor)) {
                        $fail = $fileinfo['basename'] . " (wp_get_image_editor): " . $editor->get_error_message();
                        break;
                    }

                    $editor->set_quality($thumbimg['quality']);
                    $ed_size  = $editor->get_size();
                    $ed_ratio = $ed_size['width'] / $ed_size['height'];
                    if(1 > $ed_ratio) {
                        $resized = $editor->resize($thumbimg['width'], 0, $thumbimg['crop']);
                    } else {
                        $resized = $editor->resize(0, $thumbimg['height'], $thumbimg['crop']);
                    }
                    if(is_wp_error($resized)) {
                        $fail = $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->thumb({$thumbimg['width']}, {$thumbimg['height']}, {$thumbimg['crop']})) applyto-{$applyto}: " . $resized->get_error_message();
                        break;
                    }

                    $thumbis = false;
                    if(file_exists($fileinfo['filepath_thumb'])) {
                        $thumbis = true;
                        rename($fileinfo['filepath_thumb'], $fileinfo['filepath_thumb'] . '.tmp');
                    }
                    $saved = $editor->save($fileinfo['filepath_thumb']);
                    if(is_wp_error($saved)) {
                        if($thumbis) {
                            rename($fileinfo['filepath_thumb'] . '.tmp', $fileinfo['filepath_thumb']);
                        }
                        $fail = $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->thumb): " . $saved->get_error_message();
                        break;
                    }

                } else {
                    @copy($fileinfo['filepath'], $fileinfo['filepath_thumb']);
                }
            }


            $id = $gmDB->insert_gmedia($gmedia);

            $new_metadata         = $gmDB->generate_gmedia_metadata($id, $fileinfo);
            $metadata['web']      = $new_metadata['web'];
            $metadata['original'] = $new_metadata['original'];
            $metadata['thumb']    = $new_metadata['thumb'];

            $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $metadata);

            $success = sprintf(__('Image "%d" updated', 'grand-media'), $id);
        } while(0);

        if(empty($fail)) {
            $out = array('msg' => $gmCore->alert('info', $success), 'modified' => $gmedia['modified']);
        } else {
            $out = array('error' => $gmCore->alert('danger', $fail));
        }

        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($out);
    }

    die();
}

add_action('wp_ajax_gmedit_restore', 'gmedit_restore');
function gmedit_restore() {
    global $gmDB, $gmCore, $gmGallery;
    check_ajax_referer("gmedit-save");
    if(!current_user_can('gmedia_edit_media')) {
        die('-1');
    }

    $gmedia  = array();
    $fail    = '';
    $success = '';
    $gmid    = $gmCore->_post('id');

    $item = $gmDB->get_gmedia($gmid);
    if(!empty($item)) {
        if((int)$item->author != get_current_user_id()) {
            if(!current_user_can('gmedia_edit_others_media')) {
                die('-2');
            }
        }
        $meta               = $gmDB->get_metadata('gmedia', $item->ID);
        $metadata           = $meta['_metadata'][0];
        $gmedia['ID']       = $gmid;
        $gmedia['date']     = $item->date;
        $gmedia['modified'] = current_time('mysql');
        $gmedia['author']   = $item->author;

        $webimg   = $gmGallery->options['image'];
        $thumbimg = $gmGallery->options['thumb'];

        $fileinfo = $gmCore->fileinfo($item->gmuid, false);

        $size = @getimagesize($fileinfo['filepath_original']);

        do {
            $extensions = array('1' => 'GIF', '2' => 'JPG', '3' => 'PNG', '6' => 'BMP');
            if(function_exists('memory_get_usage')) {
                switch($extensions[$size[2]]) {
                    case 'GIF':
                        $CHANNEL = 1;
                    break;
                    case 'JPG':
                        $CHANNEL = $size['channels'];
                    break;
                    case 'PNG':
                        $CHANNEL = 3;
                    break;
                    case 'BMP':
                    default:
                        $CHANNEL = 6;
                    break;
                }
                $MB                = 1048576;  // number of bytes in 1M
                $K64               = 65536;    // number of bytes in 64K
                $TWEAKFACTOR       = 1.8;     // Or whatever works for you
                $memoryNeeded      = round(($size[0] * $size[1] * $size['bits'] * $CHANNEL / 8 + $K64) * $TWEAKFACTOR);
                $memoryNeeded      = memory_get_usage() + $memoryNeeded;
                $current_limit     = @ini_get('memory_limit');
                $current_limit_int = intval($current_limit);
                if(false !== strpos($current_limit, 'M')) {
                    $current_limit_int *= $MB;
                }
                if(false !== strpos($current_limit, 'G')) {
                    $current_limit_int *= 1024;
                }

                if(-1 != $current_limit && $memoryNeeded > $current_limit_int) {
                    $newLimit = $current_limit_int / $MB + ceil(($memoryNeeded - $current_limit_int) / $MB);
                    @ini_set('memory_limit', $newLimit . 'M');
                }
            }

            $size_ratio = $size[0] / $size[1];

            $angle      = 0;
            $image_meta = @$gmCore->wp_read_image_metadata($fileinfo['filepath_original']);
            if(!empty($image_meta['orientation'])) {
                switch($image_meta['orientation']) {
                    case 3:
                        $angle = 180;
                    break;
                    case 6:
                        $angle      = 270;
                        $size_ratio = $size[1] / $size[0];
                    break;
                    case 8:
                        $angle      = 90;
                        $size_ratio = $size[1] / $size[0];
                    break;
                }
            }

            $webimg['resize']   = (($webimg['width'] < $size[0]) || ($webimg['height'] < $size[1]))? true : false;
            $thumbimg['resize'] = (((1 >= $size_ratio) && ($thumbimg['width'] > $size[0])) || ((1 <= $size_ratio) && ($thumbimg['height'] > $size[1])))? false : true;

            if($webimg['resize'] || $thumbimg['resize'] || $angle) {

                $editor = wp_get_image_editor($fileinfo['filepath_original']);
                if(is_wp_error($editor)) {
                    $fail = $fileinfo['basename'] . " (wp_get_image_editor): " . $editor->get_error_message();
                    break;
                }

                if($angle) {
                    $editor->rotate($angle);
                }

                if($webimg['resize'] || $angle) {
                    // Web-image
                    $editor->set_quality($webimg['quality']);

                    if($webimg['resize']) {
                        $resized = $editor->resize($webimg['width'], $webimg['height'], $webimg['crop']);
                        if(is_wp_error($resized)) {
                            $fail = $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->webimage({$webimg['width']}, {$webimg['height']}, {$webimg['crop']})): " . $resized->get_error_message();
                            break;
                        }
                    }

                    $saved = $editor->save($fileinfo['filepath']);
                    if(is_wp_error($saved)) {
                        $fail = $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->webimage): " . $saved->get_error_message();
                        break;
                    }
                    if(('JPG' == $extensions[$size[2]]) && !(extension_loaded('imagick') || class_exists("Imagick"))) {
                        $gmCore->copy_exif($fileinfo['filepath_original'], $fileinfo['filepath']);
                    }
                } else {
                    @copy($fileinfo['filepath_original'], $fileinfo['filepath']);
                }

                // Thumbnail
                $editor->set_quality($thumbimg['quality']);
                if($thumbimg['resize']) {
                    $ed_size  = $editor->get_size();
                    $ed_ratio = $ed_size['width'] / $ed_size['height'];
                    if(1 > $ed_ratio) {
                        $resized = $editor->resize($thumbimg['width'], 0, $thumbimg['crop']);
                    } else {
                        $resized = $editor->resize(0, $thumbimg['height'], $thumbimg['crop']);
                    }
                    if(is_wp_error($resized)) {
                        $fail = $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->thumb({$thumbimg['width']}, {$thumbimg['height']}, {$thumbimg['crop']})): " . $resized->get_error_message();
                        break;
                    }
                }

                $saved = $editor->save($fileinfo['filepath_thumb']);
                if(is_wp_error($saved)) {
                    $fail = $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->thumb): " . $saved->get_error_message();
                    break;
                }

            } else {
                @copy($fileinfo['filepath_original'], $fileinfo['filepath']);
                @copy($fileinfo['filepath_original'], $fileinfo['filepath_thumb']);
            }

            $id = $gmDB->insert_gmedia($gmedia);

            $new_metadata         = $gmDB->generate_gmedia_metadata($id, $fileinfo);
            $metadata['web']      = $new_metadata['web'];
            $metadata['original'] = $new_metadata['original'];
            $metadata['thumb']    = $new_metadata['thumb'];

            $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $metadata);
            $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_modified', 0);

            $success = sprintf(__('Image "%d" restored from backup and saved', 'grand-media'), $id);
        } while(0);

        if(empty($fail)) {
            $out = array('msg' => $gmCore->alert('info', $success), 'modified' => $gmedia['modified']);
        } else {
            $out = array('error' => $gmCore->alert('danger', $fail));
        }

        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($out);
    }

    die();
}

add_action('wp_ajax_gmedia_get_modal', 'gmedia_get_modal');
function gmedia_get_modal() {
    global $gmDB, $gmCore, $gmGallery;
    check_ajax_referer("GmediaGallery");
    $user_ID      = get_current_user_id();
    $button_class = 'btn-primary';
    $gm_terms     = array();
    $modal        = $gmCore->_post('modal');
    switch($modal) {
        case 'quick_gallery':
            if(!current_user_can('gmedia_gallery_manage')) {
                die('-1');
            }
            $modal_title  = __('Quick Gallery from selected items', 'grand-media');
            $modal_button = __('Create Quick Gallery', 'grand-media');
        break;
        case 'exclude_categories':
        case 'filter_categories':
            $modal_title  = __('Show Images from Categories', 'grand-media');
            $modal_button = __('Show Selected', 'grand-media');
        break;
        case 'assign_category':
            if(!current_user_can('gmedia_terms')) {
                die('-1');
            }
            $modal_title  = __('Assign Category for Selected Images', 'grand-media');
            $modal_button = __('Assign Category', 'grand-media');
        break;
        case 'exclude_albums':
        case 'filter_albums':
            $modal_title  = __('Filter Albums', 'grand-media');
            $modal_button = __('Show Selected', 'grand-media');
        break;
        case 'assign_album':
            if(!current_user_can('gmedia_terms')) {
                die('-1');
            }
            $modal_title  = __('Assign Album for Selected Items', 'grand-media');
            $modal_button = __('Assign Album', 'grand-media');
        break;
        case 'exclude_tags':
        case 'filter_tags':
            $modal_title  = __('Filter by Tags', 'grand-media');
            $modal_button = __('Show Selected', 'grand-media');
        break;
        case 'add_tags':
            if(!current_user_can('gmedia_terms')) {
                die('-1');
            }
            $modal_title  = __('Add Tags to Selected Items', 'grand-media');
            $modal_button = __('Add Tags', 'grand-media');
        break;
        case 'delete_tags':
            if(!current_user_can('gmedia_terms')) {
                die('-1');
            }
            $button_class = 'btn-danger';
            $modal_title  = __('Delete Tags from Selected Items', 'grand-media');
            $modal_button = __('Delete Tags', 'grand-media');
        break;
        case 'custom_filter':
            $modal_title  = __('Custom Filters', 'grand-media');
            $modal_button = __('Show Selected', 'grand-media');
        break;
        case 'filter_author':
            $modal_title = __('Filter by Author', 'grand-media');
            if($gmCore->caps['gmedia_show_others_media']) {
                $modal_button = __('Show Selected', 'grand-media');
            } else {
                $modal_button = false;
            }
        break;
        case 'select_author':
            $modal_title = __('Select Author', 'grand-media');
            if($gmCore->caps['gmedia_show_others_media']) {
                $modal_button = __('Select', 'grand-media');
            } else {
                $modal_button = false;
            }
        break;
        case 'batch_edit':
            if(!current_user_can('gmedia_edit_media')) {
                die('-1');
            }
            $modal_title  = __('Batch Edit', 'grand-media');
            $modal_button = __('Batch Save', 'grand-media');
        break;
        default:
            $modal_title  = ' ';
            $modal_button = false;
        break;
    }

    $form_action = !empty($_SERVER['HTTP_REFERER'])? $gmCore->get_admin_url(array(), array(), $_SERVER['HTTP_REFERER']) : '';
    ?>
    <form class="modal-content" id="ajax-modal-form" autocomplete="off" method="post" action="<?php echo $form_action; ?>">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php echo $modal_title; ?></h4>
        </div>
        <div class="modal-body">
            <?php
            switch($modal) {
            case 'quick_gallery':
                global $user_ID;
                $ckey     = "gmuser_{$user_ID}_library";
                $selected = isset($_COOKIE[$ckey])? $_COOKIE[$ckey] : '';
                if(empty($selected)) {
                    _e('No selected Gmedia. Select at least one item in library.', 'grand-media');
                    break;
                }
                $modules = array();
                if(($plugin_modules = glob(GMEDIA_ABSPATH . 'module/*', GLOB_ONLYDIR | GLOB_NOSORT))) {
                    foreach($plugin_modules as $path) {
                        $mfold           = basename($path);
                        $modules[$mfold] = array(
                                'place'       => 'plugin',
                                'module_name' => $mfold,
                                'module_url'  => "{$gmCore->gmedia_url}/module/{$mfold}",
                                'module_path' => $path
                        );
                    }
                }
                if(($upload_modules = glob($gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/*', GLOB_ONLYDIR | GLOB_NOSORT))) {
                    foreach($upload_modules as $path) {
                        $mfold           = basename($path);
                        $modules[$mfold] = array(
                                'place'       => 'upload',
                                'module_name' => $mfold,
                                'module_url'  => "{$gmCore->upload['url']}/{$gmGallery->options['folder']['module']}/{$mfold}",
                                'module_path' => $path
                        );
                    }
                }
                ?>
                <div class="form-group">
                    <label><?php _e('Gallery Name', 'grand-media'); ?></label>
                    <input type="text" class="form-control input-sm" name="gallery[name]" placeholder="<?php echo esc_attr(__('Gallery Name', 'grand-media')); ?>" value="" required="required"/>
                </div>
                <div class="form-group">
                    <label><?php _e('Modue', 'grand-media'); ?></label>
                    <select class="form-control input-sm" name="gallery[module]">
                        <?php
                        if(!empty($modules)) {
                            foreach($modules as $m) {
                                /**
                                 * @var $module_name
                                 * @var $module_url
                                 * @var $module_path
                                 */
                                extract($m);
                                if(!file_exists($module_path . '/index.php')) {
                                    continue;
                                }
                                $module_info = array();
                                /** @noinspection PhpIncludeInspection */
                                include($module_path . '/index.php');
                                if(empty($module_info)) {
                                    continue;
                                }
                                ?>
                                <option value="<?php echo $module_name; ?>"><?php echo $module_info['title']; ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php _e('Selected IDs', 'grand-media'); ?></label>
                    <input type="text" name="gallery[query][gmedia__in][]" class="form-control input-sm" value="<?php echo $selected; ?>" required="required"/>
                </div>
            <?php
            break;
            case 'exclude_categories':
            case 'filter_categories':
                $gm_terms = $gmDB->get_terms('gmedia_category');
                ?>
                    <div class="checkbox"><label><input type="checkbox" name="cat[]" value="0"> <?php _e('Uncategorized', 'grand-media'); ?></label></div>
                    <?php
                    if(count($gm_terms)) {
                        foreach($gm_terms as $term) {
                            if($term->count) {
                                ?>
                                <div class="checkbox">
                                    <label><input type="checkbox" name="cat[]" value="<?php echo $term->term_id; ?>"> <?php echo esc_html($term->name); ?></label>
                                    <span class="badge pull-right"><?php echo $term->count; ?></span>
                                </div>
                            <?php
                            }
                        }
                    }
            break;
            case 'assign_category':
            $term_type = 'gmedia_category';
            $gm_terms  = $gmGallery->options['taxonomies'][$term_type];
            ?>
                <div class="radio"><label><input type="radio" name="cat" value="0"> <?php _e('Uncategorized', 'grand-media'); ?></label></div>
            <?php if(count($gm_terms)) {
                foreach($gm_terms as $term_name => $term_title) {
                    echo '<div class="radio"><label><input type="radio" name="cat" value="' . $term_name . '"> ' . esc_html($term_title) . '</label></div>';
                }
            }
            break;
            case 'exclude_albums':
            case 'filter_albums':
            if($gmCore->caps['gmedia_show_others_media']) {
                $args = array();
            } else {
                $args = array(
                        'global'  => array(0, $user_ID),
                        'orderby' => 'global_desc_name'
                );
            }
            $gm_terms = $gmDB->get_terms('gmedia_album', $args);
            ?>
                <div class="checkbox"><label><input type="checkbox" name="alb[]" value="0"> <?php _e('No Album', 'grand-media'); ?></label></div>
            <hr/>
                <?php if(count($gm_terms)) {
            foreach($gm_terms as $term) {
                $author_name = '';
                if($term->global) {
                    if($gmCore->caps['gmedia_show_others_media']) {
                        $author_name .= sprintf(__('by %s', 'grand-media'), get_the_author_meta('display_name', $term->global));
                    }
                } else {
                    $author_name .= '(' . __('shared', 'grand-media') . ')';
                }
                if('publish' != $term->status) {
                    $author_name .= ' [' . $term->status . ']';
                }
                if($author_name) {
                    $author_name = " <small>{$author_name}</small>";
                }
                ?>
                <div class="checkbox">
                    <label><input type="checkbox" name="alb[]" value="<?php echo $term->term_id; ?>"> <?php echo esc_html($term->name) . $author_name; ?></label>
                    <span class="badge pull-right"><?php echo $term->count; ?></span>
                </div>
            <?php
            }
            } else {
                $modal_button = false;
            }
            break;
            case 'assign_album':
            if($gmCore->caps['gmedia_edit_others_media']) {
                $args = array();
            } else {
                $args = array(
                        'global'  => array(0, $user_ID),
                        'orderby' => 'global_desc_name'
                );
            }
            $gm_terms = $gmDB->get_terms('gmedia_album', $args);

            $terms_album = '';
            if(count($gm_terms)) {
                foreach($gm_terms as $term) {
                    $author_name = '';
                    if($term->global) {
                        if($gmCore->caps['gmedia_edit_others_media']) {
                            $author_name .= ' &nbsp; ' . sprintf(__('by %s', 'grand-media'), get_the_author_meta('display_name', $term->global));
                        }
                    } else {
                        $author_name .= ' &nbsp; (' . __('shared', 'grand-media') . ')';
                    }
                    if('publish' != $term->status) {
                        $author_name .= ' [' . $term->status . ']';
                    }
                    $terms_album .= '<option value="' . $term->term_id . '" data-count="' . $term->count . '" data-name="' . esc_html($term->name) . '" data-meta="' . $author_name . '">' . esc_html($term->name) . $author_name . '</option>' . "\n";
                }
            }
            ?>
                <div class="form-group">
                    <label><?php _e('Move to Album', 'grand-media'); ?> </label>
                    <select id="combobox_gmedia_album" name="alb" class="form-control" placeholder="<?php _e('Album Name...', 'grand-media'); ?>">
                        <option></option>
                        <option value="0"><?php _e('No Album', 'grand-media'); ?></option>
                        <?php echo $terms_album; ?>
                    </select>
                </div>
                <div class="form-group">
                    <div class="checkbox">
                        <label><input type="checkbox" name="status_global" value="1" checked> <?php _e('Make status of selected items be the same as Album status', 'grand-media'); ?>
                        </label></div>
                </div>
                <script type="text/javascript">
                    jQuery(function($) {
                        var albums = $('#combobox_gmedia_album');
                        var albums_data = $('option', albums);
                        //noinspection JSDuplicatedDeclaration
                        albums.selectize({
                            <?php if($gmCore->caps['gmedia_album_manage']){ ?>
                            create: function(input) {
                                return {
                                    value: input,
                                    text: input
                                }
                            },
                            createOnBlur: true,
                            <?php } else{ ?>
                            create: false,
                            <?php } ?>
                            persist: false,
                            render: {
                                item: function(item, escape) {
                                    if(0 === (parseInt(item.value, 10) || 0)) {
                                        return '<div>' + escape(item.text) + '</div>';
                                    }
                                    if(item.$order) {
                                        var data = $(albums_data[item.$order]).data();
                                        return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small></div>';
                                    }
                                },
                                option: function(item, escape) {
                                    if(0 === (parseInt(item.value) || 0)) {
                                        return '<div>' + escape(item.text) + '</div>';
                                    }
                                    if(item.$order) {
                                        var data = $(albums_data[item.$order]).data();
                                        return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small>' + ' <span class="badge pull-right">' + escape(data.count) + '</span></div>';
                                    }
                                }
                            }

                        });
                    });
                </script>
            <?php
            break;
            case 'exclude_tags':
            case 'filter_tags':
            $gm_terms = $gmDB->get_terms('gmedia_tag', array('fields' => 'names_count'));
            $gm_terms = array_values($gm_terms);
            if(count($gm_terms)){
            ?>
                <div class="form-group">
                    <input id="combobox_gmedia_tag" name="tag_ids" class="form-control input-sm" value="" placeholder="<?php _e('Filter Tags...', 'grand-media'); ?>"/></div>
                <script type="text/javascript">
                    jQuery(function($) {
                        var gm_terms = <?php echo json_encode($gm_terms); ?>;
                        //noinspection JSUnusedAssignment
                        var items = gm_terms.map(function(x) {
                            //noinspection JSUnresolvedVariable
                            return {id: x.term_id, name: x.name, count: x.count};
                        });
                        $('#combobox_gmedia_tag').selectize({
                            delimiter: ',',
                            maxItems: null,
                            openOnFocus: true,
                            labelField: 'name',
                            hideSelected: true,
                            options: items,
                            searchField: ['name'],
                            valueField: 'id',
                            create: false,
                            render: {
                                item: function(item, escape) {
                                    return '<div>' + escape(item.name) + '</div>';
                                },
                                option: function(item, escape) {
                                    return '<div>' + escape(item.name) + ' <span class="badge">' + escape(item.count) + '</span></div>';
                                }
                            }
                        });
                    });
                </script>
            <?php
            } else {
            $modal_button = false; ?>
                <p class="notags"><?php _e('No tags', 'grand-media'); ?></p>
            <?php
            }
            break;
            case 'add_tags':
            $gm_terms = $gmDB->get_terms('gmedia_tag', array('fields' => 'names_count'));
            $gm_terms = array_values($gm_terms);
            ?>
                <div class="form-group">
                    <input id="combobox_gmedia_tag" name="tag_names" class="form-control input-sm" value="" placeholder="<?php _e('Add Tags...', 'grand-media'); ?>"/>
                </div>
                <div class="checkbox">
                    <label><input type="checkbox" name="iptc_tags" value="1"> <?php _e('Import IPTC Keywords from selected images to Tags'); ?></label>
                </div>
                <script type="text/javascript">
                    jQuery(function($) {
                        var gm_terms = <?php echo json_encode($gm_terms); ?>;
                        //noinspection JSUnusedAssignment
                        var items = gm_terms.map(function(x) {
                            //noinspection JSUnresolvedVariable
                            return {id: x.term_id, name: x.name, count: x.count};
                        });
                        //noinspection JSDuplicatedDeclaration
                        $('#combobox_gmedia_tag').selectize({
                            delimiter: ',',
                            maxItems: null,
                            openOnFocus: false,
                            labelField: 'name',
                            hideSelected: true,
                            options: items,
                            searchField: ['name'],
                            valueField: 'name',
                            persist: false,
                            <?php if($gmCore->caps['gmedia_tag_manage']){ ?>
                            createOnBlur: true,
                            create: function(input) {
                                return {
                                    name: input
                                }
                            },
                            <?php } else{ ?>
                            create: false,
                            <?php } ?>
                            render: {
                                item: function(item, escape) {
                                    return '<div>' + escape(item.name) + '</div>';
                                },
                                option: function(item, escape) {
                                    return '<div>' + escape(item.name) + ' <span class="badge">' + escape(item.count) + '</span></div>';
                                }
                            }
                        });
                    });
                </script>
            <?php
            break;
            case 'delete_tags':
            // get selected items in Gmedia Library
            $ckey           = "gmuser_{$user_ID}_library";
            $selected_items = array_filter(explode(',', $_COOKIE[$ckey]), 'is_numeric');
            if(!empty($selected_items)) {
                $gm_terms = $gmDB->get_gmedia_terms($selected_items, 'gmedia_tag');
            }
            if(count($gm_terms)){
            foreach($gm_terms as $term){
            ?>
                <div class="checkbox">
                    <label><input type="checkbox" name="tag_id[]" value="<?php echo $term->term_id; ?>"> <?php echo esc_html($term->name); ?></label>
                    <span class="badge pull-right"><?php echo $term->count; ?></span>
                </div>
                <?php
            }
            } else {
                $modal_button = false; ?>
                <p class="notags"><?php _e('No tags', 'grand-media'); ?></p>
            <?php
            }
            break;
            case 'custom_filter':
            if($gmCore->caps['gmedia_show_others_media']) {
                $args = array();
            } else {
                $args = array(
                        'global'  => array(0, $user_ID),
                        'orderby' => 'global_desc_name'
                );
            }
            $gm_terms = $gmDB->get_terms('gmedia_filter', $args);

            if(count($gm_terms)) { ?>
                <div class="form-group">
                    <label><?php _e('Choose Filter', 'grand-media'); ?> </label>
                    <select id="combobox_gmedia_filter" name="custom_filter_id" class="form-control" placeholder="<?php _e('Filter Name...', 'grand-media'); ?>">
                        <option></option>
                        <?php foreach($gm_terms as $term) {
                            $author_name = '';
                            if($term->global) {
                                if($gmCore->caps['gmedia_show_others_media']) {
                                    $author_name .= ' &nbsp; ' . sprintf(__('by %s', 'grand-media'), get_the_author_meta('display_name', $term->global));
                                }
                            } else {
                                $author_name .= ' &nbsp; (' . __('shared', 'grand-media') . ')';
                            }
                            echo '<option value="' . $term->term_id . '" data-name="' . esc_html($term->name) . '" data-meta="' . esc_attr($author_name) . '">' . esc_html($term->name) . $author_name . '</option>' . "\n";
                        } ?>
                    </select>
                </div>
                <script type="text/javascript">
                    jQuery(function($) {
                        var items = $('#combobox_gmedia_filter');
                        var items_data = $('option', items);
                        items.selectize({
                            create: false,
                            persist: false,
                            render: {
                                item: function(item, escape) {
                                    if(0 === (parseInt(item.value, 10) || 0)) {
                                        return '<div>' + escape(item.text) + '</div>';
                                    }
                                    if(item.$order) {
                                        var data = $(items_data[item.$order]).data();
                                        return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small></div>';
                                    }
                                },
                                option: function(item, escape) {
                                    if(0 === (parseInt(item.value) || 0)) {
                                        return '<div>' + escape(item.text) + '</div>';
                                    }
                                    if(item.$order) {
                                        var data = $(items_data[item.$order]).data();
                                        return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small></div>';
                                    }
                                }
                            }

                        });
                    });
                </script>
            <?php } else { ?>
                <p><?php
                    _e('There is no Filters created yet.');
                    if($gmCore->caps['gmedia_filter_manage']) {
                        ?>
                        <a href="<?php echo add_query_arg(array('page' => 'GrandMedia_Terms', 'taxonomy' => 'gmedia_filter', 'edit_item' => '0'), admin_url('admin.php')); ?>"><?php _e('Create Filter', 'grand-media'); ?></a>
                    <?php } ?>
                </p>
            <?php }
            break;
            case 'filter_author':
            case 'select_author':
            if($gmCore->caps['gmedia_show_others_media']){
            ?>
                <div class="form-group">
                    <label><?php _e('Choose Author', 'grand-media'); ?></label>
                    <?php
                    $user_ids = $gmCore->get_editable_user_ids();
                    if($user_ids) {
                        if(!in_array($user_ID, $user_ids)) {
                            array_push($user_ids, $user_ID);
                        }
                        wp_dropdown_users(array(
                                                  'show_option_all'  => ' &#8212; ',
                                                  'include'          => $user_ids,
                                                  'include_selected' => true,
                                                  'name'             => 'author_ids',
                                                  'selected'         => $user_ID,
                                                  'class'            => 'form-control'
                                          ));
                    } else {
                        echo '<div>' . get_the_author_meta('display_name', $user_ID) . '</div>';
                    }
                    ?>
                </div>
            <?php
            } else {
                echo '<p>' . __('You are not allowed to see others media') . '</p>';
                echo '<p><strong>' . get_the_author_meta('display_name', $user_ID) . '</strong></p>';
            }
            break;
            case 'batch_edit':
            ?>
                <p><?php _e('Note, data will be saved to all selected items in Gmedia Library.') ?></p>
                <div class="form-group">
                    <label><?php _e('Filename', 'grand-media'); ?></label>
                    <select class="form-control input-sm batch_set" name="batch_filename">
                        <option value=""><?php _e('Skip. Do not change', 'grand-media'); ?></option>
                        <option value="custom"><?php _e('Custom', 'grand-media'); ?></option>
                    </select>

                    <div class="batch_set_custom" style="margin-top:5px;display:none;">
                        <input class="form-control input-sm" name="batch_filename_custom" value="" placeholder="<?php echo 'newname_{id}'; ?>"/>

                        <div><?php _e('Variables: <b>{filename}</b> - original file name; <b>{id}</b> - Gmedia #ID in database; <b>{index:1}</b> - index of selected file in order you select (set start number after colon).') ?></div>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php _e('Title', 'grand-media'); ?></label>
                    <select class="form-control input-sm batch_set" name="batch_title">
                        <option value=""><?php _e('Skip. Do not change', 'grand-media'); ?></option>
                        <option value="empty"><?php _e('Empty Title', 'grand-media'); ?></option>
                        <option value="filename"><?php _e('From Filename', 'grand-media'); ?></option>
                        <option value="custom"><?php _e('Custom', 'grand-media'); ?></option>
                    </select>
                    <input class="form-control input-sm batch_set_custom" style="margin-top:5px;display:none;" name="batch_title_custom" value="" placeholder="<?php _e('Enter custom title here', 'grand-media'); ?>"/>
                </div>
                <div class="form-group">
                    <label><?php _e('Description', 'grand-media'); ?></label>
                    <select class="form-control input-sm batch_set" name="batch_description">
                        <option value=""><?php _e('Skip. Do not change', 'grand-media'); ?></option>
                        <option value="metadata"><?php _e('Add MetaInfo to Description', 'grand-media'); ?></option>
                        <option value="empty"><?php _e('Empty Description', 'grand-media'); ?></option>
                        <option value="custom"><?php _e('Custom', 'grand-media'); ?></option>
                    </select>

                    <div class="batch_set_custom" style="margin-top:5px;display:none;">
                        <select class="form-control input-sm" name="what_description_custom" style="margin-bottom:5px;">
                            <option value="replace"><?php _e('Replace', 'grand-media'); ?></option>
                            <option value="append"><?php _e('Append', 'grand-media'); ?></option>
                            <option value="prepend"><?php _e('Prepend', 'grand-media'); ?></option>
                        </select>
                        <textarea class="form-control input-sm" cols="30" rows="3" name="batch_description_custom" placeholder="<?php _e('Enter description here', 'grand-media'); ?>"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php _e('Link', 'grand-media'); ?></label>
                    <select class="form-control input-sm batch_set" name="batch_link">
                        <option value=""><?php _e('Skip. Do not change', 'grand-media'); ?></option>
                        <option value="empty"><?php _e('Empty Link', 'grand-media'); ?></option>
                        <option value="self"><?php _e('Link to original file', 'grand-media'); ?></option>
                        <option value="custom"><?php _e('Custom', 'grand-media'); ?></option>
                    </select>
                    <input class="form-control input-sm batch_set_custom" style="margin-top:5px;display:none;" name="batch_link_custom" value="" placeholder="<?php _e('Enter url here'); ?>"/>
                </div>
                <div class="form-group">
                    <label><?php _e('Status', 'grand-media'); ?></label>
                    <select class="form-control input-sm batch_set" name="batch_status">
                        <option value=""><?php _e('Skip. Do not change', 'grand-media'); ?></option>
                        <option value="publish"><?php _e('Public', 'grand-media'); ?></option>
                        <option value="private"><?php _e('Private', 'grand-media'); ?></option>
                        <option value="draft"><?php _e('Draft', 'grand-media'); ?></option>
                    </select>
                </div>
            <?php $user_ids = current_user_can('gmedia_delete_others_media')? $gmCore->get_editable_user_ids() : false;
            if($user_ids){
            if(!in_array($user_ID, $user_ids)) {
                array_push($user_ids, $user_ID);
            }
            ?>
                <div class="form-group">
                    <label><?php _e('Author', 'grand-media'); ?></label>
                    <?php wp_dropdown_users(array(
                                                    'show_option_none' => __('Skip. Do not change', 'grand-media'),
                                                    'include'          => $user_ids,
                                                    'include_selected' => true,
                                                    'name'             => 'batch_author',
                                                    'selected'         => -1,
                                                    'class'            => 'input-sm form-control'
                                            ));
                    ?>
                </div>
            <?php } ?>
                <script type="text/javascript">
                    jQuery(function($) {
                        $('select.batch_set').change(function() {
                            if('custom' == $(this).val()) {
                                $(this).next().css({display: 'block'});
                            } else {
                                $(this).next().css({display: 'none'});
                            }
                        });
                    });
                </script>
                <?php
            break;
                default:
                    _e('Ops! Something wrong.', 'grand-media');
                break;
            }
            ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Cancel', 'grand-media'); ?></button>
            <?php if($modal_button) { ?>
                <input type="hidden" name="<?php echo $modal; ?>"/>
                <button type="button" onclick="jQuery('#ajax-modal-form').submit()" name="<?php echo $modal; ?>" class="btn <?php echo $button_class; ?>"><?php echo $modal_button; ?></button>
                <?php
            }
            wp_nonce_field('gmedia_modal');
            ?>
        </div>
    </form><!-- /.modal-content -->
    <?php
    die();
}

add_action('wp_ajax_gmedia_tag_edit', 'gmedia_tag_edit');
function gmedia_tag_edit() {
    global $gmCore, $gmDB;

    check_ajax_referer('GmediaTerms');
    if(!current_user_can('gmedia_tag_manage') && !current_user_can('gmedia_edit_others_media')) {
        $out['error'] = $gmCore->alert('danger', __("You are not allowed to edit others media", 'grand-media'));
        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($out);
        die();
    }

    $term            = array('taxonomy' => 'gmedia_tag');
    $term['name']    = trim($gmCore->_post('tag_name', ''));
    $term['term_id'] = intval($gmCore->_post('tag_id', 0));
    if($term['name'] && !$gmCore->is_digit($term['name'])) {
        if(($term_id = $gmDB->term_exists($term['term_id'], $term['taxonomy']))) {
            if(!$gmDB->term_exists($term['name'], $term['taxonomy'])) {
                $term_id = $gmDB->update_term($term['term_id'], $term['taxonomy'], $term);
                if(is_wp_error($term_id)) {
                    $out['error'] = $gmCore->alert('danger', $term_id->get_error_message());
                } else {
                    $out['msg'] = $gmCore->alert('info', sprintf(__("Tag #%d successfuly updated", 'grand-media'), $term_id));
                }
            } else {
                $out['error'] = $gmCore->alert('danger', __("A term with the name provided already exists", 'grand-media'));
            }
        } else {
            $out['error'] = $gmCore->alert('danger', __("A term with the id provided do not exists", 'grand-media'));
        }
    } else {
        $out['error'] = $gmCore->alert('danger', __("Term name can't be only digits or empty", 'grand-media'));
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($out);

    die();

}

add_action('wp_ajax_gmedia_module_preset_delete', 'gmedia_module_preset_delete');
function gmedia_module_preset_delete() {
    global $gmCore, $gmDB;
    $out = array('error' => '');

    check_ajax_referer('GmediaGallery');
    if(!current_user_can('gmedia_gallery_manage')) {
        $out['error'] = $gmCore->alert('danger', __("You are not allowed to manage galleries", 'grand-media'));
    } else {
        $taxonomy = 'gmedia_module';
        $term_id  = intval($gmCore->_post('preset_id', 0));
        $delete   = $gmDB->delete_term($term_id, $taxonomy);
        if(is_wp_error($delete)) {
            $out['error'] = $delete->get_error_message();
        }
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($out);

    die();

}

add_action('wp_ajax_gmedia_module_install', 'gmedia_module_install');
function gmedia_module_install() {
    global $gmCore, $gmGallery;

    check_ajax_referer('GmediaModule');
    if(!current_user_can('gmedia_module_manage')) {
        echo $gmCore->alert('danger', __('You are not allowed to install modules'));
        die();
    }

    if(($download = $gmCore->_post('download'))) {
        $module = $gmCore->_post('module');
        $mzip   = download_url($download);
        if(is_wp_error($mzip)) {
            echo $gmCore->alert('danger', $mzip->get_error_message());
            die();
        }

        $mzip      = str_replace("\\", "/", $mzip);
        $to_folder = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/';
        if(!wp_mkdir_p($to_folder)) {
            echo $gmCore->alert('danger', sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?', 'grand-media'), $to_folder));
            die();
        }
        if(!is_writable($to_folder)) {
            @chmod($to_folder, 0755);
            if(!is_writable($to_folder)) {
                echo $gmCore->alert('danger', sprintf(__('Directory %s is not writable by the server.', 'grand-media'), $to_folder));
                die();
            }
        }

        global $wp_filesystem;
        // Is a filesystem accessor setup?
        if(!$wp_filesystem || !is_object($wp_filesystem)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }
        if(!is_object($wp_filesystem)) {
            $result = new WP_Error('fs_unavailable', __('Could not access filesystem.', 'flag'));
        } elseif($wp_filesystem->errors->get_error_code()) {
            $result = new WP_Error('fs_error', __('Filesystem error', 'flag'), $wp_filesystem->errors);
        } else {
            $result = unzip_file($mzip, $to_folder);
        }

        // Once extracted, delete the package
        unlink($mzip);

        if(is_wp_error($result)) {
            echo $gmCore->alert('danger', $result->get_error_message());
            die();
        } else {
            echo $gmCore->alert('success', sprintf(__("The `%s` module successfuly installed", 'flag'), $module));
        }
    } else {
        echo $gmCore->alert('danger', __('No file specified', 'grand-media'));
    }

    die();

}


add_action('wp_ajax_gmedia_import_wpmedia_modal', 'gmedia_import_wpmedia_modal');
function gmedia_import_wpmedia_modal() {
    global $user_ID, $gmDB, $gmCore, $gmGallery;

    check_ajax_referer('GmediaGallery');
    if(!current_user_can('gmedia_import')) {
        die('-1');
    }

    $gm_terms = array();
    ?>
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php _e('Import from WP Media Library'); ?></h4>
        </div>
        <div class="modal-body" style="position:relative; min-height:270px;">
            <form id="import_form" name="import_form" target="import_window" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST" accept-charset="utf-8">
                <?php wp_nonce_field('GmediaImport'); ?>
                <input type="hidden" name="action" value="gmedia_import_handler"/>
                <input type="hidden" id="import-action" name="import" value="import-wpmedia"/>
                <input type="hidden" name="selected" value="<?php $ckey = "gmuser_{$user_ID}_wpmedia";
                if(isset($_COOKIE[$ckey])) {
                    echo $_COOKIE[$ckey];
                } ?>"/>
                <?php if($gmCore->caps['gmedia_terms']) { ?>
                    <div class="form-group">
                        <?php
                        $term_type = 'gmedia_category';
                        $gm_terms  = $gmGallery->options['taxonomies'][$term_type];

                        $terms_category = '';
                        if(count($gm_terms)) {
                            foreach($gm_terms as $term_name => $term_title) {
                                $terms_category .= '<option value="' . $term_name . '">' . esc_html($term_title) . '</option>' . "\n";
                            }
                        }
                        ?>
                        <label><?php _e('Assign Category', 'grand-media'); ?>
                            <small><?php _e('(for images only)') ?></small>
                        </label>
                        <select id="gmedia_category" name="terms[gmedia_category]" class="form-control input-sm">
                            <option value=""><?php _e('Uncategorized', 'grand-media'); ?></option>
                            <?php echo $terms_category; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <?php
                        $term_type = 'gmedia_album';
                        $gm_terms  = $gmDB->get_terms($term_type, array('global' => array(0, $user_ID), 'orderby' => 'global_desc_name'));

                        $terms_album = '';
                        if(count($gm_terms)) {
                            foreach($gm_terms as $term) {
                                $terms_album .= '<option value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . ($term->global? '' : __(' (shared)', 'grand-media')) . ('publish' == $term->status? '' : " [{$term->status}]") . '</option>' . "\n";
                            }
                        }
                        ?>
                        <label><?php _e('Add to Album', 'grand-media'); ?> </label>
                        <select id="combobox_gmedia_album" name="terms[gmedia_album]" class="form-control input-sm" placeholder="<?php _e('Album Name...', 'grand-media'); ?>">
                            <option value=""></option>
                            <?php echo $terms_album; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <?php
                        $term_type = 'gmedia_tag';
                        $gm_terms  = $gmDB->get_terms($term_type, array('fields' => 'names'));
                        ?>
                        <label><?php _e('Add Tags', 'grand-media'); ?> </label>
                        <input id="combobox_gmedia_tag" name="terms[gmedia_tag]" class="form-control input-sm" value="" placeholder="<?php _e('Add Tags...', 'grand-media'); ?>"/>
                    </div>
                <?php } else { ?>
                    <p><?php _e('You are not allowed to assign terms', 'grand-media') ?></p>
                <?php } ?>
                <div class="checkbox">
                    <label><input type="checkbox" name="skip_exists" value="skip"> <?php _e('Skip if file with the same name already exists in Gmedia Library', 'grand-media'); ?>
                    </label></div>
                <script type="text/javascript">
                    jQuery(function($) {
                        <?php if($gmCore->caps['gmedia_terms']){ ?>
                        //noinspection JSDuplicatedDeclaration
                        $('#combobox_gmedia_album').selectize({
                            <?php if($gmCore->caps['gmedia_album_manage']){ ?>
                            create: true,
                            createOnBlur: true,
                            <?php } else{ ?>
                            create: false,
                            <?php } ?>
                            persist: false
                        });
                        var gm_terms = <?php echo json_encode($gm_terms); ?>;
                        //noinspection JSUnusedAssignment
                        var items = gm_terms.map(function(x) {
                            return {item: x};
                        });
                        //noinspection JSDuplicatedDeclaration
                        $('#combobox_gmedia_tag').selectize({
                            <?php if($gmCore->caps['gmedia_tag_manage']){ ?>
                            create: function(input) {
                                return {
                                    item: input
                                }
                            },
                            createOnBlur: true,
                            <?php } else{ ?>
                            create: false,
                            <?php } ?>
                            delimiter: ',',
                            maxItems: null,
                            openOnFocus: false,
                            persist: false,
                            options: items,
                            labelField: 'item',
                            valueField: 'item',
                            searchField: ['item'],
                            hideSelected: true
                        });
                        <?php } ?>

                        $('#import-done').one('click', function() {
                            $('#import_form').submit();
                            $(this).text($(this).data('loading-text')).prop('disabled', true);
                            $('#import_window').show();
                            $(this).one('click', function() {
                                $('#importModal').modal('hide');
                            });
                        });

                    });
                </script>
            </form>
            <iframe name="import_window" id="import_window" src="about:blank" style="display:none; position:absolute; left:0; top:0; width:100%; height:100%; z-index:1000; background-color:#ffffff; padding:20px 20px 0 20px;" onload="gmedia_import_done()"></iframe>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Cancel', 'grand-media'); ?></button>
            <button type="button" id="import-done" class="btn btn-primary" data-complete-text="<?php _e('Close', 'grand-media'); ?>" data-loading-text="<?php _e('Working...', 'grand-media'); ?>" data-reset-text="<?php _e('Import', 'grand-media'); ?>"><?php _e('Import', 'grand-media'); ?></button>
        </div>
    </div><!-- /.modal-content -->
    <?php
    die();
}

add_action('wp_ajax_gmedia_relimage', 'gmedia_relimage');
/**
 * Do Actions via Ajax
 * TODO add related images to post
 * TODO check author for related images
 *
 * @return void
 */
function gmedia_relimage() {
    /** @var $wpdb wpdb */
    global $wpdb, $gmCore, $gmDB;

    check_ajax_referer("grandMedia");

    // check for correct capability
    if(!current_user_can('gmedia_library')) {
        die('-1');
    }

    $post_tags = array_filter(array_map('trim', explode(',', stripslashes(urldecode($gmCore->_get('tags', ''))))));
    $paged     = (int)$gmCore->_get('paged', 1);
    $per_page  = 20;
    $s         = trim(stripslashes(urldecode($gmCore->_get('search'))));
    if($s && strlen($s) > 2) {
        $post_tags = array();
    } else {
        $s = '';
    }

    $gmediaLib = array();
    $relative  = (int)$gmCore->_get('rel', 1);
    $continue  = true;
    $content   = '';

    if($relative == 1) {
        $arg       = array(
                'mime_type'    => 'image/*',
                'orderby'      => 'ID',
                'order'        => 'DESC',
                'per_page'     => $per_page,
                'page'         => $paged,
                's'            => $s,
                'tag_name__in' => $post_tags,
                'null_tags'    => true
        );
        $gmediaLib = $gmDB->get_gmedias($arg);
    }

    if(empty($gmediaLib) && count($post_tags)) {

        if($relative == 1) {
            $relative = 0;
            $paged    = 1;
            $content .= '<li class="emptydb">' . __('No items related by tags.', 'grand-media') . '</li>' . "\n";
        }

        $tag__not_in = "'" . implode("','", array_map('esc_sql', array_unique((array)$post_tags))) . "'";
        $tag__not_in = $wpdb->get_col("
			SELECT term_id
			FROM {$wpdb->prefix}gmedia_term
			WHERE taxonomy = 'gmedia_tag'
			AND name IN ({$tag__not_in})
		");

        $arg       = array(
                'mime_type'   => 'image/*',
                'orderby'     => 'ID',
                'order'       => 'DESC',
                'per_page'    => $per_page,
                'page'        => $paged,
                'tag__not_in' => $tag__not_in
        );
        $gmediaLib = $gmDB->get_gmedias($arg);
    }

    if(($count = count($gmediaLib))) {
        foreach($gmediaLib as $item) {
            $content .= "<li class='gmedia-image-li' id='gm-img-{$item->ID}'>\n";
            $content .= "	<a target='_blank' class='gm-img' data-gmid='{$item->ID}' href='" . $gmCore->gm_get_media_image($item) . "'><img src='" . $gmCore->gm_get_media_image($item, 'thumb') . "' height='50' style='width:auto;' alt='' title='" . esc_attr($item->title) . "' /></a>\n";
            $content .= "	<div style='display: none;' class='gm-img-description'>" . esc_html($item->description) . "</div>\n";
            $content .= "</li>\n";
        }
        if(($count < $per_page) && ($relative == 0 || !empty($s))) {
            $continue = false;
        }
    } else {
        if($s) {
            $content .= '<li class="emptydb">' . __('No items matching the search query.', 'grand-media') . '</li>' . "\n";
        } else {
            $content .= '<li class="emptydb">' . __('No items to show', 'grand-media') . '</li>' . "\n";
        }
        $continue = false;
    }
    $result = array('paged' => $paged, 'rel' => $relative, 'continue' => $continue, 'content' => $content, 'data' => $post_tags);
    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);

    die();

}

add_action('wp_ajax_gmedia_ftp_browser', 'gmedia_ftp_browser');
/**
 * jQuery File Tree PHP Connector
 * @author  Cory S.N. LaViska - A Beautiful Site (http://abeautifulsite.net/)
 * @version 1.0.1
 *
 * @return string folder content
 */
function gmedia_ftp_browser() {
    if(!current_user_can('gmedia_import')) {
        die('No access');
    }

    // if nonce is not correct it returns -1
    check_ajax_referer('grandMedia');

    // start from the default path
    $root = trailingslashit(ABSPATH);
    // get the current directory
    $dir = trailingslashit(urldecode($_POST['dir']));

    if((false === strpos($dir, '..')) && file_exists($root . $dir)) {
        $files = scandir($root . $dir);
        natcasesort($files);

        // The 2 counts for . and ..
        if(count($files) > 2) {
            echo "<ul class=\"jqueryDirTree\" style=\"display: none;\">";
            // return only directories
            foreach($files as $file) {
                if(in_array($file, array('wp-admin', 'wp-includes', 'plugins', 'themes', 'thumb', 'thumbs'))) {
                    continue;
                }

                if(file_exists($root . $dir . $file) && $file != '.' && $file != '..' && is_dir($root . $dir . $file)) {
                    echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . esc_attr($dir . $file) . "/\">" . esc_html($file) . "</a></li>";
                }
            }
            echo "</ul>";
        }
    }

    die();
}

add_action('wp_ajax_gmedia_set_post_thumbnail', 'gmedia_set_post_thumbnail');
function gmedia_set_post_thumbnail() {
    global $gmCore, $gmDB, $gmGallery;

    $post_ID = intval($gmCore->_post('post_id', 0));

    if(!$post_ID || !current_user_can('edit_post', $post_ID)) {
        die('-1');
    }

    // if nonce is not correct it returns -1
    check_ajax_referer('set_post_thumbnail-' . $post_ID);

    $img_id = intval($gmCore->_post('img_id', 0));

    /*
	// delete the image
	if ( $thumbnail_id == '-1' ) {
		delete_post_meta( $post_ID, '_thumbnail_id' );
		die('0');
	}
	*/

    if($img_id) {

        $image = $gmDB->get_gmedia($img_id);
        if($image) {

            $args          = array(
                    'post_type'    => 'attachment',
                    'meta_key'     => '_gmedia_image_id',
                    'meta_compare' => '==',
                    'meta_value'   => $img_id
            );
            $posts         = get_posts($args);
            $attachment_id = null;

            if($posts != null) {
                $attachment_id = $posts[0]->ID;
                //$target_path   = get_attached_file( $attachment_id );
            } else {
                $upload_dir = wp_upload_dir();
                $basedir    = $upload_dir['basedir'];
                $thumbs_dir = implode(DIRECTORY_SEPARATOR, array($basedir, 'gmedia_featured'));

                $type = explode('/', $image->mime_type);

                $url           = $gmCore->upload['url'] . '/' . $gmGallery->options['folder'][$type[0]] . '/' . $image->gmuid;
                $image_abspath = $gmCore->upload['path'] . '/' . $gmGallery->options['folder'][$type[0]] . '/' . $image->gmuid;

                $img_name    = current_time('ymd_Hi') . '_' . basename($image->gmuid);
                $target_path = path_join($thumbs_dir, $img_name);
                wp_mkdir_p($thumbs_dir);

                if(@copy($image_abspath, $target_path)) {
                    $title   = sanitize_title($image->title);
                    $caption = $gmCore->sanitize($image->description);

                    $attachment = array(
                            'post_title'     => $title,
                            'post_content'   => $caption,
                            'post_status'    => 'attachment',
                            'post_parent'    => 0,
                            'post_mime_type' => $image->mime_type,
                            'guid'           => $url
                    );

                    //require for wp_generate_attachment_metadata which generates image related meta-data also creates thumbs
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    // Save the data
                    $attachment_id = wp_insert_attachment($attachment, $target_path);
                    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $target_path));
                    add_post_meta($attachment_id, '_gmedia_image_id', $img_id, true);
                }
            }

            if($attachment_id) {
                delete_post_meta($post_ID, '_thumbnail_id');
                add_post_meta($post_ID, '_thumbnail_id', $attachment_id, true);

                echo _wp_post_thumbnail_html($attachment_id, $post_ID);
                die();
            }
        }
    }

    die('0');
}

add_action('wp_ajax_gmedia_upload_handler', 'gmedia_upload_handler');
function gmedia_upload_handler() {
    global $gmCore;

    ini_set('max_execution_time', 300);

    // HTTP headers for no cache etc
    nocache_headers();

    // if nonce is not correct it returns -1
    check_ajax_referer('GmediaUpload');
    if(!current_user_can('gmedia_upload')) {
        wp_die(__('You do not have permission to upload files in Gmedia Library.'));
    }

    // 5 minutes execution time
    @set_time_limit(5 * 60);

    // fake upload time
    usleep(10);

    $filename = $gmCore->_req('name');

    // Get parameters
    if(!$filename) {
        $return = json_encode(array("error" => array("code" => 100, "message" => __("No file name.", 'grand-media'))));
        die($return);
    }

    $fileinfo = $gmCore->fileinfo($filename);
    if(false === $fileinfo) {
        $return = json_encode(array("error" => array("code" => 100, "message" => __("File type not allowed.", 'grand-media')), "id" => $filename));
        die($return);
    }

    // Look for the content type header
    $contentType = '';
    if(isset($_SERVER["HTTP_CONTENT_TYPE"])) {
        $contentType = $_SERVER["HTTP_CONTENT_TYPE"];
    }

    if(isset($_SERVER["CONTENT_TYPE"])) {
        $contentType = $_SERVER["CONTENT_TYPE"];
    }

    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    $file_tmp = '';
    if(strpos($contentType, "multipart") !== false) {
        if(isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $file_tmp = $_FILES['file']['tmp_name'];
        }
    } else {
        $file_tmp = "php://input";
    }

    if(empty($file_tmp)) {
        $return = json_encode(array("error" => array("code" => 103, "message" => __("Failed to move uploaded file.", 'grand-media')), "id" => $filename));
        die($return);
    }

    $post_data = array();
    if(($params = $gmCore->_req('params', ''))) {
        parse_str($params, $post_data);
    }

    $return = $gmCore->gmedia_upload_handler($file_tmp, $fileinfo, $contentType, $post_data);
    $return = json_encode($return);

    die($return);
}

add_action('wp_ajax_gmedia_import_handler', 'gmedia_import_handler');
function gmedia_import_handler() {
    global $wpdb, $gmCore, $gmDB;

    ini_set('max_execution_time', 600);

    // HTTP headers for no cache etc
    nocache_headers();

    check_admin_referer('GmediaImport');
    if(!current_user_can('gmedia_import')) {
        wp_die(__('You do not have permission to upload files.'));
    }

    // 10 minutes execution time
    @set_time_limit(10 * 60);

    // fake upload time
    usleep(10);

    $import = $gmCore->_post('import');
    $terms  = $gmCore->_post('terms', array());

    if(ob_get_level() == 0) {
        ob_start();
    }
    echo str_pad(' ', 4096) . PHP_EOL;
    wp_ob_end_flush_all();
    flush();
    ?>
    <html>
    <style type="text/css">
        * { margin:0; padding:0; }
        pre { display:block; }
        p { padding:10px 0; font-size:14px; }
        .ok { color:darkgreen; }
        .ko { color:darkred; }
    </style>
    <body>
    <?php
    if(('import-folder' == $import) || isset($_POST['import-folder'])) {

        $path = $gmCore->_post('path');
        echo '<h4 style="margin: 0 0 10px">' . __('Import Server Folder') . " `$path`:</h4>" . PHP_EOL;

        if($path) {
            $path = trim(urldecode($path), '/');
            if(!empty($path)) {
                $fullpath = ABSPATH . trailingslashit($path);
                $files    = glob($fullpath . '?*.?*', GLOB_NOSORT);
                if(!empty($files)) {
                    $allowed_ext = get_allowed_mime_types();
                    $allowed_ext = array_keys($allowed_ext);
                    $allowed_ext = implode('|', $allowed_ext);
                    $allowed_ext = explode('|', $allowed_ext);
                    if((GMEDIA_UPLOAD_FOLDER == basename(dirname(dirname($path)))) || (GMEDIA_UPLOAD_FOLDER == basename(dirname($path)))) {
                        global $wpdb;
                        $gmedias = $wpdb->get_col("SELECT gmuid FROM {$wpdb->prefix}gmedia");
                        foreach($files as $i => $filepath) {
                            $gmuid = basename($filepath);
                            if(in_array($gmuid, $gmedias)) {
                                $fileinfo = $gmCore->fileinfo($gmuid, false);
                                if(!(('image' == $fileinfo['dirname']) && !file_exists($fileinfo['filepath']))) {
                                    unset($files[$i]);
                                }
                            }
                        }
                        $move   = false;
                        $exists = false;
                    } else {
                        $move   = $gmCore->_post('delete_source');
                        $exists = 0;
                    }
                    foreach($files as $i => $filepath) {
                        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
                        if(!in_array(strtolower($ext), $allowed_ext)) {
                            unset($files[$i]);
                        }
                    }
                    $gmCore->gmedia_import_files($files, $terms, $move, $exists);
                } else {
                    echo sprintf(__('Folder `%s` is empty', 'grand-media'), $path) . PHP_EOL;
                }
            } else {
                echo __('No folder chosen', 'grand-media') . PHP_EOL;
            }
        }
    } elseif(('import-flagallery' == $import) || isset($_POST['import-flagallery'])) {

        echo '<h4 style="margin: 0 0 10px">' . __('Import from Flagallery plugin') . ":</h4>" . PHP_EOL;

        $gallery = $gmCore->_post('gallery');
        if(!empty($gallery)) {
            $album = (!isset($terms['gmedia_album']) || empty($terms['gmedia_album']))? false : true;
            foreach($gallery as $gid) {
                $flag_gallery = $wpdb->get_row($wpdb->prepare("SELECT gid, path, title, galdesc FROM {$wpdb->prefix}flag_gallery WHERE gid = %d", $gid), ARRAY_A);
                if(empty($flag_gallery)) {
                    continue;
                }

                if(!$album) {
                    $terms['gmedia_album'] = $flag_gallery['title'];
                    if($gmCore->is_digit($terms['gmedia_album'])) {
                        $terms['gmedia_album'] = 'a' . $terms['gmedia_album'];
                    }
                    if(!$gmDB->term_exists($terms['gmedia_album'], 'gmedia_album')) {
                        $term_id = $gmDB->insert_term($terms['gmedia_album'], 'gmedia_album', array('description' => htmlspecialchars_decode(stripslashes($flag_gallery['galdesc']))));
                    }
                }

                $path = ABSPATH . trailingslashit($flag_gallery['path']);

                echo '<h5 style="margin: 10px 0 5px">' . sprintf(__('Import `%s` gallery', 'grand-media'), $flag_gallery['title']) . ":</h5>" . PHP_EOL;

                $flag_pictures = $wpdb->get_results($wpdb->prepare("SELECT CONCAT('%s', filename) AS file, description, alttext AS title, link FROM {$wpdb->prefix}flag_pictures WHERE galleryid = %d", $path, $flag_gallery['gid']), ARRAY_A);
                if(empty($flag_pictures)) {
                    echo '<pre>' . __('gallery contains 0 images', 'grand-media') . '</pre>';
                    continue;
                }
                //echo '<pre>'.print_r($flag_pictures, true).'</pre>';
                $gmCore->gmedia_import_files($flag_pictures, $terms, false);
            }
        } else {
            echo __('No gallery chosen', 'grand-media') . PHP_EOL;
        }
    } elseif(('import-nextgen' == $import) || isset($_POST['import-nextgen'])) {

        echo '<h4 style="margin: 0 0 10px">' . __('Import from NextGen plugin') . ":</h4>" . PHP_EOL;

        $gallery = $gmCore->_post('gallery');
        if(!empty($gallery)) {
            $album = (!isset($terms['gmedia_album']) || empty($terms['gmedia_album']))? false : true;
            foreach($gallery as $gid) {
                $ngg_gallery = $wpdb->get_row($wpdb->prepare("SELECT gid, path, title, galdesc FROM {$wpdb->prefix}ngg_gallery WHERE gid = %d", $gid), ARRAY_A);
                if(empty($ngg_gallery)) {
                    continue;
                }

                if(!$album) {
                    $terms['gmedia_album'] = $ngg_gallery['title'];
                    if($gmCore->is_digit($terms['gmedia_album'])) {
                        $terms['gmedia_album'] = 'a' . $ngg_gallery['title'];
                    }
                    if(!$gmDB->term_exists($terms['gmedia_album'], 'gmedia_album')) {
                        $term_id = $gmDB->insert_term($terms['gmedia_album'], 'gmedia_album', array('description' => htmlspecialchars_decode(stripslashes($ngg_gallery['galdesc']))));
                    }
                }

                $path = ABSPATH . trailingslashit($ngg_gallery['path']);

                echo '<h5 style="margin: 10px 0 5px">' . sprintf(__('Import `%s` gallery', 'grand-media'), $ngg_gallery['title']) . ":</h5>" . PHP_EOL;

                $ngg_pictures = $wpdb->get_results($wpdb->prepare("SELECT CONCAT('%s', filename) AS file, description, alttext AS title FROM {$wpdb->prefix}ngg_pictures WHERE galleryid = %d", $path, $ngg_gallery['gid']), ARRAY_A);
                if(empty($ngg_pictures)) {
                    echo '<pre>' . __('gallery contains 0 images', 'grand-media') . '</pre>';
                    continue;
                }
                $gmCore->gmedia_import_files($ngg_pictures, $terms, false);
            }
        } else {
            echo __('No gallery chosen', 'grand-media') . PHP_EOL;
        }
    } elseif(('import-wpmedia' == $import) || isset($_POST['import-wpmedia'])) {

        echo '<h4 style="margin: 0 0 10px">' . __('Import from WP Media Library') . ":</h4>" . PHP_EOL;

        $wpMediaLib = $gmDB->get_wp_media_lib(array('filter' => 'selected', 'selected' => $gmCore->_post('selected')));

        if(!empty($wpMediaLib)) {

            $wp_media = array();
            foreach($wpMediaLib as $item) {
                $wp_media[] = array(
                        'file'        => get_attached_file($item->ID),
                        'title'       => $item->post_title,
                        'description' => $item->post_content
                );
            }
            $exists = $gmCore->_post('skip_exists', 0);
            //echo '<pre>' . print_r($wp_media, true) . '</pre>';
            $gmCore->gmedia_import_files($wp_media, $terms, false, $exists);

        } else {
            echo __('No items chosen', 'grand-media') . PHP_EOL;
        }
    }
    ?>
    </body>
    </html>
    <?php
    wp_ob_end_flush_all();

    die();
}

add_action('wp_ajax_gmedia_application', 'gmedia_application');
function gmedia_application() {
    global $gmCore, $gmGallery;

    // if nonce is not correct it returns -1
    check_ajax_referer('GmediaService');
    if(!current_user_can('manage_options')) {
        die('-1');
    }

    $service = $gmCore->_post('service');
    if(!$service) {
        die('0');
    }
    $_data = $gmCore->_post('data');
    wp_parse_str($_data, $data);

    $options                     = $gmGallery->options;
    $options['site_email']       = $data['site_email'];
    $options['site_title']       = $data['site_title'];
    $options['site_description'] = $data['site_description'];
    if($options != $gmGallery->options) {
        $gmGallery->options = $options;
        update_option('gmediaOptions', $options);
    }

    $result = $gmCore->app_service($service);

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);

    die();
}

add_action('wp_ajax_gmedia_share_page', 'gmedia_share_page');
function gmedia_share_page() {
    global $gmCore, $user_ID;
    // if nonce is not correct it returns -1
    check_ajax_referer('share_modal', '_sharenonce');

    $sharelink    = $gmCore->_post('sharelink', '');
    $email        = $gmCore->_post('email', '');
    $sharemessage = $gmCore->_post('message', '');
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo $gmCore->alert('danger', __('Invalid email', 'grand-media') . ': ' . esc_html($email));
        die();
    }

    $display_name  = get_the_author_meta('display_name', $user_ID);
    $subject       = sprintf(__('%s shared GmediaCloud Page with you', 'grand-media'), $display_name);
    $sharetitle    = sprintf(__('%s used Gmedia to share something interesting with you!', 'grand-media'), $display_name);
    $sharelinktext = __('Click here to view page', 'grand-media');
    if($sharemessage) {
        $sharemessage = '<blockquote>"' . nl2br(esc_html($sharemessage)) . '"</blockquote>';
    }
    $footer  = '© ' . date('Y') . ' GmediaGallery';
    $message = <<<EOT
<center>
<table cellpadding="0" cellspacing="0" style="border-radius:4px;border:1px #dceaf5 solid;" border="0" align="center">
	<tr><td colspan="3" height="20"></td></tr>
	<tr style="line-height:0;">
		<td width="100%" style="font-size:0;" align="center" height="1">
			<img width="72" style="max-height:72px;width:72px;" alt="GmediaGallery" src="http://mypgc.co/images/email/logo-128.png" />
		</td>
	</tr>
	<tr><td>
			<table cellpadding="0" cellspacing="0" style="line-height:25px;" border="0" align="center">
				<tr><td colspan="3" height="20"></td></tr>
				<tr>
					<td width="36"></td>
					<td width="454" align="left" style="color:#444444;border-collapse:collapse;font-size:11pt;font-family:proxima_nova,'Open Sans','Lucida Grande','Segoe UI',Arial,Verdana,'Lucida Sans Unicode',Tahoma,'Sans Serif';max-width:454px;" valign="top">{$sharetitle}<br />
						{$sharemessage}
						<br /><a style="color:#0D8FB3" href="{$sharelink}">{$sharelinktext}</a>.</td>
					<td width="36"></td>
				</tr>
				<tr><td colspan="3" height="36"></td></tr>
			</table>
		</td>
	</tr>
</table>
<table cellpadding="0" cellspacing="0" align="center" border="0">
	<tr><td height="10"></td></tr>
	<tr><td style="padding:0;border-collapse:collapse;">
			<table cellpadding="0" cellspacing="0" align="center" border="0">
				<tr style="color:#a8b9c6;font-size:11px;font-family:proxima_nova,'Open Sans','Lucida Grande','Segoe UI',Arial,Verdana,'Lucida Sans Unicode',Tahoma,'Sans Serif';">
					<td width="128" align="left"></td>
					<td width="400" align="right">{$footer}</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</center>
EOT;

    $headers = array('Content-Type: text/html; charset=UTF-8');
    if(wp_mail($email, $subject, $message, $headers)) {
        echo $gmCore->alert('success', sprintf(__('Message sent to %s', 'grand-media'), $email));
    }

    die();
}

add_action('wp_ajax_gmedia_add_custom_field', 'gmedia_add_custom_field');
function gmedia_add_custom_field() {
    global $gmDB, $user_ID, $gmCore;
    check_ajax_referer('gmedia_custom_field', '_customfield_nonce');

    $meta_type = 'gmedia';

    $pid  = (int)$_POST['ID'];
    $post = $gmDB->get_gmedia($pid);

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);

    if(!current_user_can('gmedia_edit_media') || ($user_ID != $post->author && !current_user_can('gmedia_edit_others_media'))) {
        echo json_encode(array('error' => array('code' => 100, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $pid));
        die();
    }
    if(isset($_POST['metakeyselect']) && empty($_POST['metakeyselect']) && empty($_POST['metakeyinput'])) {
        echo json_encode(array('error' => array('code' => 101, 'message' => __('Choose or provide a custom field name', 'grand-media')), 'id' => $pid));
        die();
    }

    if(!$mid = $gmCore->add_meta($pid, $meta_type)) {
        echo json_encode(array('error' => array('code' => 102, 'message' => __('Please provide a custom field value', 'grand-media')), 'id' => $pid));
        die();
    }

    $column = sanitize_key($meta_type . '_id');
    $meta   = $gmDB->get_metadata_by_mid($meta_type, $mid);
    $pid    = (int)$meta->{$column};
    $meta   = get_object_vars($meta);
    $result = array(
            'success' => array(
                    'meta_id' => $mid,
                    'data'    => $gmCore->_list_meta_item($meta)
            ),
            'id'      => $pid
    );

    if(!empty($_POST['metakeyinput'])) {
        $result['newmeta_form'] = $gmCore->meta_form($meta_type);
    }

    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_delete_custom_field', 'gmedia_delete_custom_field');
function gmedia_delete_custom_field() {
    global $gmDB, $user_ID;
    check_ajax_referer('gmedia_custom_field', '_customfield_nonce');

    $meta_type = 'gmedia';

    $pid  = (int)$_POST['ID'];
    $post = $gmDB->get_gmedia($pid);

    if(!current_user_can('gmedia_edit_media') || ($user_ID != $post->author && !current_user_can('gmedia_edit_others_media'))) {
        echo json_encode(array('error' => array('code' => 100, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $pid));
        die();
    }

    $result = array('id' => $pid);

    $deletemeta = $_POST['meta'];
    $column     = sanitize_key($meta_type . '_id');
    if(isset($deletemeta) && is_array($deletemeta)) {
        foreach($deletemeta as $key => $value) {
            if(!$meta = $gmDB->get_metadata_by_mid($meta_type, $key)) {
                continue;
            }
            if($meta->{$column} != $pid) {
                continue;
            }
            if(is_protected_meta($meta->meta_key, $meta_type)) {
                continue;
            }
            if(($del_meta = $gmDB->delete_metadata_by_mid($meta_type, $key))) {
                $result['deleted'][] = $key;
            }
        }
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_term_add_custom_field', 'gmedia_term_add_custom_field');
function gmedia_term_add_custom_field() {
    global $gmDB, $user_ID, $gmCore;
    check_ajax_referer('gmedia_custom_field', '_customfield_nonce');

    $meta_type = 'gmedia_term';

    $pid      = (int)$_POST['ID'];
    $taxonomy = 'gmedia_album';
    $post     = $gmDB->get_term($pid, $taxonomy);

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);

    if(!current_user_can('gmedia_album_manage') || ($user_ID != $post->global && !current_user_can('gmedia_edit_others_media'))) {
        echo json_encode(array('error' => array('code' => 100, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $pid));
        die();
    }
    if(isset($_POST['metakeyselect']) && empty($_POST['metakeyselect']) && empty($_POST['metakeyinput'])) {
        echo json_encode(array('error' => array('code' => 101, 'message' => __('Choose or provide a custom field name', 'grand-media')), 'id' => $pid));
        die();
    }

    if(!$mid = $gmCore->add_meta($pid, $meta_type)) {
        echo json_encode(array('error' => array('code' => 102, 'message' => __('Please provide a custom field value', 'grand-media')), 'id' => $pid));
        die();
    }

    $column = sanitize_key($meta_type . '_id');
    $meta   = $gmDB->get_metadata_by_mid($meta_type, $mid);
    $pid    = (int)$meta->{$column};
    $meta   = get_object_vars($meta);
    $result = array(
            'success' => array(
                    'meta_id' => $mid,
                    'data'    => $gmCore->_list_meta_item($meta)
            ),
            'id'      => $pid
    );

    if(!empty($_POST['metakeyinput'])) {
        $result['newmeta_form'] = $gmCore->meta_form($meta_type);
    }

    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_term_delete_custom_field', 'gmedia_term_delete_custom_field');
function gmedia_term_delete_custom_field() {
    global $gmDB, $user_ID;
    check_ajax_referer('gmedia_custom_field', '_customfield_nonce');

    $meta_type = 'gmedia_term';

    $pid      = (int)$_POST['ID'];
    $taxonomy = 'gmedia_album';
    $post     = $gmDB->get_term($pid, $taxonomy);

    if(!current_user_can('gmedia_album_manage') || ($user_ID != $post->global && !current_user_can('gmedia_edit_others_media'))) {
        echo json_encode(array('error' => array('code' => 100, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $pid));
        die();
    }

    $result = array('id' => $pid);

    $deletemeta = $_POST['meta'];
    $column     = sanitize_key($meta_type . '_id');
    if(isset($deletemeta) && is_array($deletemeta)) {
        foreach($deletemeta as $key => $value) {
            if(!$meta = $gmDB->get_metadata_by_mid($meta_type, $key)) {
                continue;
            }
            if($meta->{$column} != $pid) {
                continue;
            }
            if(is_protected_meta($meta->meta_key, $meta_type)) {
                continue;
            }
            if(($del_meta = $gmDB->delete_metadata_by_mid($meta_type, $key))) {
                $result['deleted'][] = $key;
            }
        }
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_upgrade_process', 'gmedia_upgrade_process');
function gmedia_upgrade_process() {

    $db_version = get_option('gmediaDbVersion');
    $info = get_transient('gmediaHeavyJob');
    $result = array( 'content' => '' );

    $upgrading = get_transient('gmediaUpgrade');
    if($upgrading){
        $timeout = time() - $upgrading;
    } else {
        $timeout = 0;
    }
    if($timeout > 20){
        require_once(GMEDIA_ABSPATH.'config/update.php');
        gmedia_db_update();
    }
    $result['timeout'] = $timeout;

    if(!empty($info)) {
        $result['content'] = '<div>' . implode("</div>\n<div>", $info) . '</div>';
    } elseif($db_version == GMEDIA_DBVERSION) {
        $result['status'] = 'done';
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_module_interaction', 'gmedia_module_interaction');
add_action('wp_ajax_nopriv_gmedia_module_interaction', 'gmedia_module_interaction');
function gmedia_module_interaction() {
    global $gmDB, $gmCore;

    if(empty($_SERVER['HTTP_REFERER'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die();
    }

    $ref = $_SERVER['HTTP_REFERER'];
    //$uip = str_replace('.', '', $_SERVER['REMOTE_ADDR'])
    if((false === strpos($ref, get_home_url())) && (false === strpos($ref, get_site_url()))) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die();
    }
    if(('POST' !== $_SERVER['REQUEST_METHOD']) || !isset($_SERVER['HTTP_HOST']) || !strpos(get_home_url(), $_SERVER['HTTP_HOST'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die();
    }


    if(isset($_POST['hit']) && ($gmID = intval($_POST['hit']))) {
        if(null === $gmDB->get_gmedia($gmID)) {
            die('0');
        }
        $meta['views'] = $gmDB->get_metadata('gmedia', $gmID, 'views', true);
        $meta['likes'] = $gmDB->get_metadata('gmedia', $gmID, 'likes', true);

        $meta = array_map('intval', $meta);
        $meta = $gmCore->gm_hitcounter($gmID, $meta);

        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($meta);
        die();
    }

    if(isset($_POST['rate'])) {
        /**
         * @var $uip
         * @var $gmid
         * @var $rate
         */
        extract($_POST['rate'], EXTR_OVERWRITE);
        if(!intval($gmid) || (null === $gmDB->get_gmedia($gmid))) {
            die('0');
        }
        $rating   = $gmDB->get_metadata('gmedia', $gmid, '_rating', true);
        $old_rate = 0;

        $transient_key   = 'gm_rate_day' . date('w');
        $transient_value = get_transient($transient_key);
        if(false !== $transient_value) {
            if(isset($transient_value[$uip][$gmid])) {
                $old_rate = $transient_value[$uip][$gmid];
            }
            $transient_value[$uip][$gmid] = $rate;
        } else {
            $transient_value = array($uip => array($gmid => $rate));
        }
        set_transient($transient_key, $transient_value, 18 * HOUR_IN_SECONDS);

        $rating_votes = $old_rate? $rating['votes'] : $rating['votes'] + 1;
        $rating_value = ($rating['value'] * $rating['votes'] + $rate - $old_rate) / $rating_votes;
        $rating       = array('value' => $rating_value, 'votes' => $rating_votes);

        $gmDB->update_metadata('gmedia', $gmid, '_rating', $rating);

        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode(array($rating));
        die();
    }

    die();
}

add_action('wp_ajax_load_comments', 'gmedia_module_load_comments');
add_action('wp_ajax_nopriv_load_comments', 'gmedia_module_load_comments');
function gmedia_module_load_comments() {
    global $gmCore;

    /*    if(empty($_SERVER['HTTP_REFERER'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            die();
        }

        $ref = $_SERVER['HTTP_REFERER'];
        //$uip = str_replace('.', '', $_SERVER['REMOTE_ADDR'])
        if((false === strpos($ref, get_home_url())) && (false === strpos($ref, get_site_url()))) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            die();
        }
        if(('POST' !== $_SERVER['REQUEST_METHOD']) || !isset($_SERVER['HTTP_HOST']) || !strpos(get_home_url(), $_SERVER['HTTP_HOST'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
            die();
        }*/

    check_ajax_referer('GmediaGallery');

    $data = $gmCore->_post('data', false);

    $post_id = (int)$data['post_id'];
    if($post_id) {
        $comments_link  = apply_filters('get_comments_link', add_query_arg('comments', 'show', get_permalink($post_id), $post_id));
        $comments_count = wp_count_comments($post_id);
        $comments_count = $comments_count->approved;
    } else {
        $comments_link  = '//about:blank';
        $comments_count = 0;
    }

    $result                   = array();
    $result['comments_count'] = $comments_count;
    $result['content']        = "<iframe class='gmedia-comments' src='{$comments_link}' frameborder='0' allowtransparency='true'>";

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);

    die();
}

