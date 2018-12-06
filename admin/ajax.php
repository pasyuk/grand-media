<?php
add_action('wp_ajax_gmedia_update_data', 'gmedia_update_data');
function gmedia_update_data(){
    global $gmDB, $gmCore;
    check_ajax_referer("GmediaGallery");
    if( !current_user_can('gmedia_edit_media')){
        die('-1');
    }

    $data = $gmCore->_post('data');

    wp_parse_str($data, $gmedia);

    if( !empty($gmedia['ID'])){
        $item = $gmDB->get_gmedia($gmedia['ID']);
        if((int) $item->author != get_current_user_id()){
            if( !current_user_can('gmedia_edit_others_media')){
                die('-2');
            }
        }

        $gmedia['modified']  = current_time('mysql');
        $gmedia['mime_type'] = $item->mime_type;
        $gmedia['gmuid']     = $item->gmuid;
        if( !current_user_can('gmedia_delete_others_media')){
            $gmedia['author'] = $item->author;
        }

        $gmuid = pathinfo($item->gmuid);

        $gmedia['filename'] = preg_replace('/[^a-z0-9_\.-]+/i', '_', $gmedia['filename']);
        if(($gmedia['filename'] != $gmuid['filename']) && (current_user_can('gmedia_delete_others_media') || ((int) $item->author == get_current_user_id()))){
            $fileinfo = $gmCore->fileinfo($gmedia['filename'] . '.' . $gmuid['extension']);
            if(false !== $fileinfo){
                if('image' == $fileinfo['dirname'] && file_is_displayable_image($fileinfo['dirpath'] . '/' . $item->gmuid)){
                    if(is_file($fileinfo['dirpath_original'] . '/' . $item->gmuid)){
                        @rename($fileinfo['dirpath_original'] . '/' . $item->gmuid, $fileinfo['filepath_original']);
                    }
                    @rename($fileinfo['dirpath_thumb'] . '/' . $item->gmuid, $fileinfo['filepath_thumb']);
                }
                if(@rename($fileinfo['dirpath'] . '/' . $item->gmuid, $fileinfo['filepath'])){
                    $gmedia['gmuid'] = $fileinfo['basename'];
                }
            }
        }
        if( !current_user_can('gmedia_terms')){
            unset($gmedia['terms']);
        }

        $id = $gmDB->insert_gmedia($gmedia);
        if( !is_wp_error($id)){
            // Meta Stuff
            if(isset($gmedia['meta']) && is_array($gmedia['meta'])){
                $meta_error = array();
                foreach($gmedia['meta'] as $key => $value){
                    if($gmCore->is_digit($key)){
                        $mid = (int) $key;
                        //$value = wp_unslash( $value );
                        if( !($meta = $gmDB->get_metadata_by_mid('gmedia', $mid))){
                            $meta_error[] = array(
                                'error'    => 'no_meta',
                                'message'  => __('No record in DataBase.', 'grand-media'),
                                'meta_id'  => $mid,
                                'meta_key' => $meta->meta_key
                            );
                            continue;
                        }
                        if('' == trim($value)){
                            $meta_error[] = array(
                                'error'      => 'empty_value',
                                'message'    => __('Please provide a custom field value.', 'grand-media'),
                                'meta_id'    => $mid,
                                'meta_key'   => $meta->meta_key,
                                'meta_value' => $meta->meta_value
                            );
                            continue;
                        }

                        if($meta->meta_value != $value){
                            if( !($u = $gmDB->update_metadata_by_mid('gmedia', $mid, $value))){
                                $meta_error[] = array(
                                    'error'      => 'meta_update',
                                    'message'    => __('Something goes wrong.', 'grand-media'),
                                    'meta_id'    => $mid,
                                    'meta_key'   => $meta->meta_key,
                                    'meta_value' => $meta->meta_value
                                );
                            }
                        }
                    } elseif('_' == $key[0]){
                        if('_cover' == $key){
                            $value = ltrim($value, '#');
                        } elseif('_gps' == $key){
                            if($value){
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
        } else{
            $result = $gmDB->get_gmedia($id);
        }

        gmedia_item_more_data($result);
        if('image' != $result->type){
            include_once(GMEDIA_ABSPATH . 'admin/pages/library/functions.php');
            $result->thumbnail = gmedia_item_thumbnail($result);
        }

        if(current_user_can('gmedia_terms')){
            if( !empty($gmedia['terms']['gmedia_album'])){
                if(isset($gmedia['gmedia_album_order'])){
                    $album = $gmDB->get_the_gmedia_terms($id, 'gmedia_album');
                    if($album){
                        $album = reset($album);
                        if((int) $gmedia['gmedia_album_order'] != (int) $album->gmedia_order){
                            $gmDB->update_term_sortorder($album->term_id, array($id => (int) $gmedia['gmedia_album_order']));
                            $result->gmedia_album_order = (int) $gmedia['gmedia_album_order'];
                        }
                    }
                }
                $alb_id               = $gmedia['terms']['gmedia_album'];
                $alb                  = $gmDB->get_term($alb_id, 'gmedia_album');
                $result->album_status = $alb? $alb->status : 'none';
            } else{
                $result->album_status = 'none';
            }
        }
        if( !empty($meta_error)){
            $result->meta_error = $meta_error;
        }
	    gmedia_delete_transients( 'gm_cache' );

        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($result);
    }

    die();
}

add_action('wp_ajax_gmedit_save', 'gmedit_save');
function gmedit_save(){
    global $gmDB, $gmCore, $gmGallery;
    check_ajax_referer('gmedia_edit', '_wpnonce_edit');
    if( !current_user_can('gmedia_edit_media')){
        die('-1');
    }

    $gmedia  = array();
    $fail    = '';
    $success = '';
    $gmid    = $gmCore->_post('id');
    $image   = $gmCore->_post('image');
    $applyto = $gmCore->_post('applyto', 'web_thumb');

    $item = $gmDB->get_gmedia($gmid);
    if( !empty($item)){
        if((int) $item->author != get_current_user_id()){
            if( !current_user_can('gmedia_edit_others_media')){
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
            if(function_exists('memory_get_usage')){
                switch($extensions[ $size[2] ]){
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
                if(false !== strpos($current_limit, 'M')){
                    $current_limit_int *= $MB;
                }
                if(false !== strpos($current_limit, 'G')){
                    $current_limit_int *= 1024;
                }

                if(- 1 != $current_limit && $memoryNeeded > $current_limit_int){
                    $newLimit = $current_limit_int / $MB + ceil(($memoryNeeded - $current_limit_int) / $MB);
                    if($newLimit < 256){
                        $newLimit = 256;
                    }
                    @ini_set('memory_limit', $newLimit . 'M');
                }
            }

            $no_original = false;
            if('thumb' == $applyto){
                $editfile = $fileinfo['filepath_thumb'];
            } else{
                $editfile = $fileinfo['filepath'];
                if(('JPG' == $extensions[ $size[2] ]) && !is_file($fileinfo['filepath_original'])){
                    $no_original = true;
                    @copy($editfile, $fileinfo['filepath_original']);
                }
            }
            if( !@file_put_contents($editfile, $image['data'])){
                $fail = $fileinfo['basename'] . ": " . __('Can\'t write to file. Permission denied', 'grand-media');
                break;
            }

            $modified = isset($meta['_modified'][0])? (intval($meta['_modified'][0]) + 1) : 1;
            $gmDB->update_metadata($meta_type = 'gmedia', $item->ID, $meta_key = '_modified', $modified);

            // Web-image
            if('thumb' !== $applyto){
                if('JPG' == $extensions[ $size[2] ]){
                    $gmCore->copy_exif($fileinfo['filepath_original'], $fileinfo['filepath']);
                    if($no_original){
                        @unlink($fileinfo['filepath_original']);
                    }
                }
            }
            // Thumbnail
            if('web_thumb' == $applyto){
                $size_ratio         = $size[0] / $size[1];
                $thumbimg['resize'] = (((1 >= $size_ratio) && ($thumbimg['width'] > $size[0])) || ((1 <= $size_ratio) && ($thumbimg['height'] > $size[1])))? false : true;
                if($thumbimg['resize']){
                    $editor = wp_get_image_editor($editfile);
                    if(is_wp_error($editor)){
                        $fail = $fileinfo['basename'] . " (wp_get_image_editor): " . $editor->get_error_message();
                        break;
                    }

                    $editor->set_quality($thumbimg['quality']);
                    $ed_size  = $editor->get_size();
                    $ed_ratio = $ed_size['width'] / $ed_size['height'];
                    if(1 > $ed_ratio){
                        $resized = $editor->resize($thumbimg['width'], 0, $thumbimg['crop']);
                    } else{
                        $resized = $editor->resize(0, $thumbimg['height'], $thumbimg['crop']);
                    }
                    if(is_wp_error($resized)){
                        $fail = $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->thumb({$thumbimg['width']}, {$thumbimg['height']}, {$thumbimg['crop']})) applyto-{$applyto}: " . $resized->get_error_message();
                        break;
                    }

                    $thumbis = false;
                    if(is_file($fileinfo['filepath_thumb'])){
                        $thumbis = true;
                        rename($fileinfo['filepath_thumb'], $fileinfo['filepath_thumb'] . '.tmp');
                    }
                    $saved = $editor->save($fileinfo['filepath_thumb']);
                    if(is_wp_error($saved)){
                        if($thumbis){
                            rename($fileinfo['filepath_thumb'] . '.tmp', $fileinfo['filepath_thumb']);
                        }
                        $fail = $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->thumb): " . $saved->get_error_message();
                        break;
                    }

                } else{
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

        if(empty($fail)){
            $out = array('msg' => $gmCore->alert('info', $success), 'modified' => $gmedia['modified']);
        } else{
            $out = array('error' => $gmCore->alert('danger', $fail));
        }

        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($out);
    }

    die();
}

add_action('wp_ajax_gmedit_restore', 'gmedit_restore');
function gmedit_restore(){
    global $gmCore;
    check_ajax_referer('gmedia_edit', '_wpnonce_edit');
    if( !current_user_can('gmedia_edit_media')){
        die('-1');
    }

    $gmid = $gmCore->_post('id');
    $out  = $gmCore->recreate_images_from_original($gmid);

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($out);

    die();
}

add_action('wp_ajax_gmedia_get_modal', 'gmedia_get_modal');
function gmedia_get_modal(){
    global $gmDB, $gmCore, $gmGallery;
    check_ajax_referer("GmediaGallery");
    $user_ID      = get_current_user_id();
    $button_class = 'btn-primary';
    $gm_terms     = array();
    $modal        = $gmCore->_post('modal');
    $ckey         = $gmCore->_post('ckey');
    switch($modal){
        case 'quick_gallery':
            if( !current_user_can('gmedia_gallery_manage')){
                die('-1');
            }
            //$ckey         = "gmedia_library";
            $modal_title  = __('Quick Gallery from selected items', 'grand-media');
            $modal_button = __('Create Quick Gallery', 'grand-media');
            break;
        case 'quick_gallery_stack':
            if( !current_user_can('gmedia_gallery_manage')){
                die('-1');
            }
            //$ckey         = "gmedia_{$user_ID}_libstack";
            $modal_title  = __('Quick Gallery from Stack', 'grand-media');
            $modal_button = __('Create Quick Gallery', 'grand-media');
            break;
        case 'exclude_categories':
        case 'filter_categories':
            $modal_title  = __('Show Images from Categories', 'grand-media');
            $modal_button = __('Show Selected', 'grand-media');
            break;
        case 'assign_category':
            if( !current_user_can('gmedia_terms')){
                die('-1');
            }
            $modal_title  = __('Assign Category for Selected Images', 'grand-media');
            $modal_button = __('Assign Category', 'grand-media');
            break;
        case 'unassign_category':
            if( !current_user_can('gmedia_terms')){
                die('-1');
            }
            $button_class = 'btn-danger';
            $modal_title  = __('Unassign Categories from Selected Items', 'grand-media');
            $modal_button = __('Unassign Categories', 'grand-media');
            break;
        case 'exclude_albums':
        case 'filter_albums':
            $modal_title  = __('Filter Albums', 'grand-media');
            $modal_button = __('Show Selected', 'grand-media');
            break;
        case 'assign_album':
            if( !current_user_can('gmedia_terms')){
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
            if( !current_user_can('gmedia_terms')){
                die('-1');
            }
            $modal_title  = __('Add Tags to Selected Items', 'grand-media');
            $modal_button = __('Add Tags', 'grand-media');
            break;
        case 'delete_tags':
            if( !current_user_can('gmedia_terms')){
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
            if($gmCore->caps['gmedia_show_others_media']){
                $modal_button = __('Show Selected', 'grand-media');
            } else{
                $modal_button = false;
            }
            break;
        case 'select_author':
            $modal_title = __('Select Author', 'grand-media');
            if($gmCore->caps['gmedia_show_others_media']){
                $modal_button = __('Select', 'grand-media');
            } else{
                $modal_button = false;
            }
            break;
        case 'batch_edit':
            if( !current_user_can('gmedia_edit_media')){
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
            switch($modal){
                case 'quick_gallery':
            case 'quick_gallery_stack':
                if( !empty($ckey)){
                    $selected_in_library = isset($_COOKIE[ $ckey ])? str_replace('.', ',', $_COOKIE[ $ckey ]) : '';
                }
                if(empty($selected_in_library)){
                    _e('No selected Gmedia. Select at least one item in library.', 'grand-media');
                    break;
                }
                $gmedia_modules = get_gmedia_modules(false);
                ?>
                <div class="form-group">
                    <label><?php _e('Gallery Name', 'grand-media'); ?></label>
                    <input type="text" class="form-control input-sm" name="gallery[name]" placeholder="<?php esc_attr_e(__('Gallery Name', 'grand-media')); ?>" value="" required="required"/>
                </div>
                <div class="form-group">
                    <label><?php _e('Modue', 'grand-media'); ?></label>
                    <select class="form-control input-sm" name="gallery[module]">
                        <?php foreach($gmedia_modules['in'] as $mfold => $module){
                            echo '<optgroup label="' . esc_attr($module['title']) . '">';
                            $presets  = $gmDB->get_terms('gmedia_module', array('status' => $mfold));
                            $selected = selected($gmGallery->options['default_gmedia_module'], esc_attr($mfold), false);
                            $option   = array();
                            $option[] = '<option ' . $selected . ' value="' . esc_attr($mfold) . '">' . $module['title'] . ' - ' . __('Default Settings') . '</option>';
                            foreach($presets as $preset){
                                if( !(int) $preset->global && '[' . $mfold . ']' === $preset->name){
                                    continue;
                                }
                                $selected  = selected($gmGallery->options['default_gmedia_module'], $preset->term_id, false);
                                $by_author = '';
                                if((int) $preset->global){
                                    $by_author = ' [' . get_the_author_meta('display_name', $preset->global) . ']';
                                }
                                if('[' . $mfold . ']' === $preset->name){
                                    $option[] = '<option ' . $selected . ' value="' . $preset->term_id . '">' . $module['title'] . $by_author . ' - ' . __('Default Settings') . '</option>';
                                } else{
                                    $preset_name = str_replace('[' . $mfold . '] ', '', $preset->name);
                                    $option[]    = '<option ' . $selected . ' value="' . $preset->term_id . '">' . $module['title'] . $by_author . ' - ' . $preset_name . '</option>';
                                }
                            }
                            echo implode('', $option);
                            echo '</optgroup>';
                        } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php _e('Selected IDs', 'grand-media'); ?></label>
                    <input type="text" name="gallery[query][gmedia__in]" class="form-control input-sm" value="<?php echo $selected_in_library; ?>" required="required"/>
                </div>
            <?php
            break;
            case 'exclude_albums':
            case 'filter_albums':
            if($gmCore->caps['gmedia_show_others_media']){
                $args = array();
            } else{
                $args = array(
                    'global'  => array(0, $user_ID),
                    'orderby' => 'global_desc_name'
                );
            }
            $gm_terms = $gmDB->get_terms('gmedia_album', $args);
            ?>
                <div class="checkbox"><label><input type="checkbox" name="alb[]" value="0"> <?php _e('No Album', 'grand-media'); ?></label></div>
            <hr/>
                <?php if(count($gm_terms)){
            foreach($gm_terms as $term){
                $author_name = '';
                if($term->global){
                    if($gmCore->caps['gmedia_show_others_media']){
                        $author_name .= sprintf(__('by %s', 'grand-media'), get_the_author_meta('display_name', $term->global));
                    }
                } else{
                    $author_name .= '(' . __('shared', 'grand-media') . ')';
                }
                if('publish' != $term->status){
                    $author_name .= ' [' . $term->status . ']';
                }
                if($author_name){
                    $author_name = " <small>{$author_name}</small>";
                }
                ?>
                <div class="checkbox">
                    <label><input type="checkbox" name="alb[]" value="<?php echo $term->term_id; ?>"> <?php echo esc_html($term->name) . $author_name; ?></label>
                    <span class="badge pull-right"><?php echo $term->count; ?></span>
                </div>
            <?php
            }
            } else{
                $modal_button = false;
            }
            break;
            case 'assign_album':
            if($gmCore->caps['gmedia_edit_others_media']){
                $args = array();
            } else{
                $args = array(
                    'global'  => array(0, $user_ID),
                    'orderby' => 'global_desc_name'
                );
            }
            $gm_terms = $gmDB->get_terms('gmedia_album', $args);

            $terms_album = '';
            if(count($gm_terms)){
                foreach($gm_terms as $term){
                    $author_name = '';
                    if($term->global){
                        if($gmCore->caps['gmedia_edit_others_media']){
                            $author_name .= ' &nbsp; ' . sprintf(__('by %s', 'grand-media'), get_the_author_meta('display_name', $term->global));
                        }
                    } else{
                        $author_name .= ' &nbsp; (' . __('shared', 'grand-media') . ')';
                    }
                    if('publish' != $term->status){
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
                    <small class="help-block" style="margin-top:0;"><?php _e('Choose "No Album" to delete albums from selected items', 'grand-media'); ?></small>
                </div>
                <div class="form-group">
                    <div class="checkbox">
                        <label><input type="checkbox" name="status_global" value="1" checked> <?php _e('Make status of selected items be the same as Album status', 'grand-media'); ?>
                        </label></div>
                </div>
                <script type="text/javascript">
                    jQuery(function($){
                        var albums = $('#combobox_gmedia_album');
                        var albums_data = $('option', albums);
                        //noinspection JSDuplicatedDeclaration
                        albums.selectize({
                            <?php if($gmCore->caps['gmedia_album_manage']){ ?>
                            create: function(input){
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
                                item: function(item, escape){
                                    if(0 === (parseInt(item.value, 10) || 0)){
                                        return '<div>' + escape(item.text) + '</div>';
                                    }
                                    if(item.$order){
                                        var data = $(albums_data[item.$order]).data();
                                        return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small></div>';
                                    }
                                },
                                option: function(item, escape){
                                    if(0 === (parseInt(item.value) || 0)){
                                        return '<div>' + escape(item.text) + '</div>';
                                    }
                                    if(item.$order){
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
            case 'exclude_categories':
            case 'filter_categories':
            $gm_terms = $gmDB->get_terms('gmedia_category');
            ?>
                <div class="checkbox"><label><input type="checkbox" name="cat[]" value="0"> <?php _e('Uncategorized', 'grand-media'); ?></label></div>
                <?php
            if(count($gm_terms)){
            foreach($gm_terms as $term){
            if($term->count){
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
            $gm_terms = $gmDB->get_terms('gmedia_category', array('fields' => 'names_count'));
            $gm_terms = array_values($gm_terms);
            ?>
                <div class="form-group">
                    <input id="combobox_gmedia_category" name="cat_names" class="form-control input-sm" value="" placeholder="<?php _e('Add to Categories...', 'grand-media'); ?>"/>
                </div>
                <script type="text/javascript">
                    jQuery(function($){
                        var gm_terms = <?php echo json_encode($gm_terms); ?>;
                        //noinspection JSUnusedAssignment
                        var items = gm_terms.map(function(x){
                            //noinspection JSUnresolvedVariable
                            return {id: x.term_id, name: x.name, count: x.count};
                        });
                        //noinspection JSDuplicatedDeclaration
                        $('#combobox_gmedia_category').selectize({
                            delimiter: ',',
                            maxItems: null,
                            openOnFocus: true,
                            labelField: 'name',
                            hideSelected: true,
                            options: items,
                            searchField: ['name'],
                            valueField: 'name',
                            persist: false,
                            <?php if($gmCore->caps['gmedia_category_manage']){ ?>
                            createOnBlur: true,
                            create: function(input){
                                return {
                                    name: input
                                }
                            },
                            <?php } else{ ?>
                            create: false,
                            <?php } ?>
                            render: {
                                item: function(item, escape){
                                    return '<div>' + escape(item.name) + '</div>';
                                },
                                option: function(item, escape){
                                    return '<div>' + escape(item.name) + ' <span class="badge">' + escape(item.count) + '</span></div>';
                                }
                            }
                        });
                    });
                </script>
            <?php
            break;
            case 'unassign_category':
            // get selected items in Gmedia Library
            $selected_items = !empty($ckey)? array_filter(explode('.', $_COOKIE[ $ckey ]), 'is_numeric') : false;
            if( !empty($selected_items)){
                $gm_terms = $gmDB->get_gmedia_terms($selected_items, 'gmedia_category');
            }
            if(count($gm_terms)){
            foreach($gm_terms

            as $term){
            ?>
                <div class="checkbox">
                    <label><input type="checkbox" name="category_id[]" value="<?php echo $term->term_id; ?>"> <?php echo esc_html($term->name); ?></label>
                    <span class="badge pull-right"><?php echo $term->count; ?></span>
                </div>
                <?php
            }
            } else{
                $modal_button = false; ?>
                <p class="noterms"><?php _e('No categories', 'grand-media'); ?></p>
            <?php
            }
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
                    jQuery(function($){
                        var gm_terms = <?php echo json_encode($gm_terms); ?>;
                        //noinspection JSUnusedAssignment
                        var items = gm_terms.map(function(x){
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
                                item: function(item, escape){
                                    return '<div>' + escape(item.name) + '</div>';
                                },
                                option: function(item, escape){
                                    return '<div>' + escape(item.name) + ' <span class="badge">' + escape(item.count) + '</span></div>';
                                }
                            }
                        });
                    });
                </script>
            <?php
            } else {
            $modal_button = false; ?>
                <p class="noterms"><?php _e('No tags', 'grand-media'); ?></p>
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
                    jQuery(function($){
                        var gm_terms = <?php echo json_encode($gm_terms); ?>;
                        //noinspection JSUnusedAssignment
                        var items = gm_terms.map(function(x){
                            //noinspection JSUnresolvedVariable
                            return {id: x.term_id, name: x.name, count: x.count};
                        });
                        //noinspection JSDuplicatedDeclaration
                        $('#combobox_gmedia_tag').selectize({
                            delimiter: ',',
                            maxItems: null,
                            openOnFocus: true,
                            labelField: 'name',
                            hideSelected: true,
                            options: items,
                            searchField: ['name'],
                            valueField: 'name',
                            persist: false,
                            <?php if($gmCore->caps['gmedia_tag_manage']){ ?>
                            createOnBlur: true,
                            create: function(input){
                                return {
                                    name: input
                                }
                            },
                            <?php } else{ ?>
                            create: false,
                            <?php } ?>
                            render: {
                                item: function(item, escape){
                                    return '<div>' + escape(item.name) + '</div>';
                                },
                                option: function(item, escape){
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
            $selected_items = !empty($ckey)? array_filter(explode('.', $_COOKIE[ $ckey ]), 'is_numeric') : false;
            if( !empty($selected_items)){
                $gm_terms = $gmDB->get_gmedia_terms($selected_items, 'gmedia_tag');
            }
            if(count($gm_terms)){
            foreach($gm_terms

            as $term){
            ?>
                <div class="checkbox">
                    <label><input type="checkbox" name="tag_id[]" value="<?php echo $term->term_id; ?>"> <?php echo esc_html($term->name); ?></label>
                    <span class="badge pull-right"><?php echo $term->count; ?></span>
                </div>
                <?php
            }
            } else{
                $modal_button = false; ?>
                <p class="noterms"><?php _e('No tags', 'grand-media'); ?></p>
            <?php
            }
            break;
            case 'filter_author':
            case 'select_author':
            if($gmCore->caps['gmedia_show_others_media']){
            ?>
                <div class="form-group">
                    <label><?php _e('Choose Author', 'grand-media'); ?></label>
                    <?php
                    $user_ids = $gmCore->get_editable_user_ids();
                    if($user_ids){
                        if( !in_array($user_ID, $user_ids)){
                            array_push($user_ids, $user_ID);
                        }
                        wp_dropdown_users(array(
                            'show_option_all'  => ' &#8212; ',
                            'include'          => $user_ids,
                            'include_selected' => true,
                            'name'             => 'author_ids',
                            'selected'         => $user_ID,
                            'class'            => 'form-control combobox_authors'
                        ));
                    } else{
                        echo '<div>' . get_the_author_meta('display_name', $user_ID) . '</div>';
                    }
                    ?>
                </div>
                <script type="text/javascript">
                    jQuery(function(){
                        jQuery('.combobox_authors').selectize({
                            create: false,
                            maxItems: 1,
                            openOnFocus: true,
                            hideSelected: true
                        });
                    });
                </script>
            <?php
            } else{
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

                        <div><?php _e('Variables: <b>{filename}</b> - original file name; <b>{id}</b> - Gmedia #ID in database; <b>{index:001}</b> - index of selected file in order you select (set start number after colon).') ?></div>
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
                <div class="form-group">
                    <label><?php _e('Comment Status', 'grand-media'); ?></label>
                    <select class="form-control input-sm batch_set" name="batch_comment_status">
                        <option value=""><?php _e('Skip. Do not change', 'grand-media'); ?></option>
                        <option value="open"><?php _e('Open', 'grand-media'); ?></option>
                        <option value="closed"><?php _e('Closed', 'grand-media'); ?></option>
                    </select>
                </div>
            <?php $user_ids = current_user_can('gmedia_delete_others_media')? $gmCore->get_editable_user_ids() : false;
            if($user_ids){
            if( !in_array($user_ID, $user_ids)){
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
                        'selected'         => - 1,
                        'class'            => 'input-sm form-control'
                    ));
                    ?>
                </div>
            <?php } ?>
                <script type="text/javascript">
                    jQuery(function($){
                        $('select.batch_set').change(function(){
                            if('custom' == $(this).val()){
                                $(this).next().css({display: 'block'});
                            } else{
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
            <?php if($modal_button){ ?>
                <input type="hidden" name="<?php echo $modal; ?>"/>
                <button type="button" onclick="jQuery('#ajax-modal-form').submit()" class="btn <?php echo $button_class; ?>"><?php echo $modal_button; ?></button>
                <?php
            }
            wp_nonce_field('gmedia_action', '_wpnonce_action');
            ?>
        </div>
    </form><!-- /.modal-content -->
    <?php
    die();
}

add_action('wp_ajax_gmedia_tag_edit', 'gmedia_tag_edit');
function gmedia_tag_edit(){
    global $gmCore, $gmDB;

    check_ajax_referer('gmedia_terms', '_wpnonce_terms');
    if( !current_user_can('gmedia_tag_manage') && !current_user_can('gmedia_edit_others_media')){
        $out['error'] = $gmCore->alert('danger', __("You are not allowed to edit others media", 'grand-media'));
        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($out);
        die();
    }

    $term            = array('taxonomy' => 'gmedia_tag');
    $term['name']    = trim($gmCore->_post('tag_name', ''));
    $term['term_id'] = intval($gmCore->_post('tag_id', 0));
    if($term['name'] && !$gmCore->is_digit($term['name'])){
        if(($term_id = $gmDB->term_exists($term['term_id']))){
            if( !$gmDB->term_exists($term['name'], $term['taxonomy'])){
                $term_id = $gmDB->update_term($term['term_id'], $term);
                if(is_wp_error($term_id)){
                    $out['error'] = $gmCore->alert('danger', $term_id->get_error_message());
                } else{
                    $out['msg'] = $gmCore->alert('info', sprintf(__("Tag #%d successfully updated", 'grand-media'), $term_id));
                }
            } else{
                $out['error'] = $gmCore->alert('danger', __("A term with the name provided already exists", 'grand-media'));
            }
        } else{
            $out['error'] = $gmCore->alert('danger', __("A term with the id provided does not exists", 'grand-media'));
        }
    } else{
        $out['error'] = $gmCore->alert('danger', __("Term name can't be only digits or empty", 'grand-media'));
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($out);

    die();

}

add_action('wp_ajax_gmedia_module_preset_delete', 'gmedia_module_preset_delete');
function gmedia_module_preset_delete(){
    global $gmCore, $gmDB, $user_ID;
    $out = array('error' => '');

    check_ajax_referer('GmediaGallery');
    if( !current_user_can('gmedia_gallery_manage')){
        $out['error'] = $gmCore->alert('danger', __("You are not allowed to manage galleries", 'grand-media'));
    } else{
        $term_id = intval($gmCore->_post('preset_id', 0));
        $term    = $gmDB->get_term($term_id);
        if($term && !is_wp_error($term)){
            if(($term->global != $user_ID && !gm_user_can('delete_others_media')) || ((int) $term->global === 0 && !current_user_can('manage_options'))){
                $out['error'] = $gmCore->alert('danger', __("You are not allowed to manage galleries", 'grand-media'));

                header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
                echo json_encode($out);

                die();
            }

            $delete = $gmDB->delete_term($term_id);
            if(is_wp_error($delete)){
                $out['error'] = $delete->get_error_message();
            }
        }
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($out);

    die();

}

add_action('wp_ajax_gmedia_module_install', 'gmedia_module_install');
function gmedia_module_install(){
    global $gmCore, $gmGallery;

    check_ajax_referer('GmediaGallery');
    if( !current_user_can('gmedia_module_manage')){
        echo $gmCore->alert('danger', __('You are not allowed to install modules'));
        die();
    }

    if(($download = $gmCore->_post('download'))){
        $module = $gmCore->_post('module');
        $mzip   = download_url($download);
        if(is_wp_error($mzip)){
            echo $gmCore->alert('danger', $mzip->get_error_message());
            die();
        }

        $mzip      = str_replace("\\", "/", $mzip);
        $to_folder = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/';
        if( !wp_mkdir_p($to_folder)){
            echo $gmCore->alert('danger', sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?', 'grand-media'), $to_folder));
            die();
        }
        if( !is_writable($to_folder)){
            @chmod($to_folder, 0755);
            if( !is_writable($to_folder)){
                echo $gmCore->alert('danger', sprintf(__('Directory %s is not writable by the server.', 'grand-media'), $to_folder));
                die();
            }
        }

        global $wp_filesystem;
        // Is a filesystem accessor setup?
        if( !$wp_filesystem || !is_object($wp_filesystem)){
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }
        if( !is_object($wp_filesystem)){
            $result = new WP_Error('fs_unavailable', __('Could not access filesystem.', 'grand-media'));
        } elseif($wp_filesystem->errors->get_error_code()){
            $result = new WP_Error('fs_error', __('Filesystem error', 'grand-media'), $wp_filesystem->errors);
        } else{
            if($module && is_dir($to_folder . $module)){
                $gmCore->delete_folder($to_folder . $module);
            }
            $result = unzip_file($mzip, $to_folder);
        }

        // Once extracted, delete the package
        unlink($mzip);

        if(is_wp_error($result)){
            echo $gmCore->alert('danger', $result->get_error_message());
            die();
        } else{
            echo $gmCore->alert('success', sprintf(__("The `%s` module successfully installed", 'grand-media'), $module));
            // Try to clear cache after module update
            @$gmCore->clear_cache();
        }
    } else{
        echo $gmCore->alert('danger', __('No file specified', 'grand-media'));
    }

    die();

}


add_action('wp_ajax_gmedia_import_wpmedia_modal', 'gmedia_import_wpmedia_modal');
function gmedia_import_wpmedia_modal(){
    global $user_ID, $gmDB, $gmCore;

    check_ajax_referer('GmediaGallery');
    if( !current_user_can('gmedia_import')){
        die('-1');
    }
    ?>
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><?php _e('Import from WP Media Library'); ?></h4>
        </div>
        <div class="modal-body" style="position:relative; min-height:270px;">
            <form id="import_form" name="import_form" target="import_window" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST" accept-charset="utf-8">
                <?php wp_nonce_field('gmedia_import', '_wpnonce_import'); ?>
                <input type="hidden" name="action" value="gmedia_import_handler"/>
                <input type="hidden" id="import-action" name="import" value="import-wpmedia"/>
                <input type="hidden" name="selected" value="<?php $ckey = "gmedia_library:wpmedia";
                if(isset($_COOKIE[ $ckey ])){
                    echo str_replace('.', ',', $_COOKIE[ $ckey ]);
                } ?>"/>
                <?php if($gmCore->caps['gmedia_terms']){ ?>
                    <div class="form-group">
                        <?php
                        $term_type = 'gmedia_album';
                        $gm_terms  = $gmDB->get_terms($term_type, array('global' => array(0, $user_ID), 'orderby' => 'global_desc_name'));

                        $terms_album = '';
                        if(count($gm_terms)){
                            foreach($gm_terms as $term){
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
                        $term_type    = 'gmedia_category';
                        $gm_cat_terms = $gmDB->get_terms($term_type, array('fields' => 'names'));
                        ?>
                        <label><?php _e('Assign Categories', 'grand-media'); ?></label>
                        <input id="combobox_gmedia_category" name="terms[gmedia_category]" class="form-control input-sm" value="" placeholder="<?php _e('Uncategorized', 'grand-media'); ?>"/>
                    </div>

                    <div class="form-group">
                        <?php
                        $term_type    = 'gmedia_tag';
                        $gm_tag_terms = $gmDB->get_terms($term_type, array('fields' => 'names'));
                        ?>
                        <label><?php _e('Add Tags', 'grand-media'); ?> </label>
                        <input id="combobox_gmedia_tag" name="terms[gmedia_tag]" class="form-control input-sm" value="" placeholder="<?php _e('Add Tags...', 'grand-media'); ?>"/>
                    </div>
                    <script type="text/javascript">
                        jQuery(function($){
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

                            var gm_cat_terms = <?php echo json_encode($gm_cat_terms); ?>;
                            //noinspection JSUnusedAssignment
                            var cat_items = gm_cat_terms.map(function(x){
                                return {item: x};
                            });
                            //noinspection JSDuplicatedDeclaration
                            $('#combobox_gmedia_category').selectize({
                                <?php if($gmCore->caps['gmedia_category_manage']){ ?>
                                create: function(input){
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
                                openOnFocus: true,
                                persist: false,
                                options: cat_items,
                                labelField: 'item',
                                valueField: 'item',
                                searchField: ['item'],
                                hideSelected: true
                            });

                            var gm_tag_terms = <?php echo json_encode($gm_tag_terms); ?>;
                            //noinspection JSUnusedAssignment
                            var tag_items = gm_tag_terms.map(function(x){
                                return {item: x};
                            });
                            //noinspection JSDuplicatedDeclaration
                            $('#combobox_gmedia_tag').selectize({
                                <?php if($gmCore->caps['gmedia_tag_manage']){ ?>
                                create: function(input){
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
                                openOnFocus: true,
                                persist: false,
                                options: tag_items,
                                labelField: 'item',
                                valueField: 'item',
                                searchField: ['item'],
                                hideSelected: true
                            });
                        });
                    </script>
                <?php } else { ?>
                    <p><?php _e('You are not allowed to assign terms', 'grand-media') ?></p>
                <?php } ?>
                <div class="checkbox">
                    <label><input type="checkbox" name="skip_exists" value="skip"> <?php _e('Skip if file with the same name already exists in Gmedia Library', 'grand-media'); ?></label>
                    <div class="help-block"><?php _e('Note: duplicates will be skipped in any way (checked by file hash)') ?></div>
                </div>
                <script type="text/javascript">
                    jQuery(function($){
                        $('#import-done').one('click', function(){
                            $('#import_form').submit();
                            $(this).text($(this).data('loading-text')).prop('disabled', true);
                            $('#import_window').show();
                            $(this).one('click', function(){
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
 * @return void
 */
function gmedia_relimage(){
    /** @var $wpdb wpdb */
    global $wpdb, $gmCore, $gmDB;

    check_ajax_referer("GmediaGallery");

    // check for correct capability
    if( !current_user_can('gmedia_library')){
        die('-1');
    }

    $post_tags = array_filter(array_map('trim', explode(',', stripslashes(urldecode($gmCore->_get('tags', ''))))));
    $paged     = (int) $gmCore->_get('paged', 1);
    $per_page  = 20;
    $s         = trim(stripslashes(urldecode($gmCore->_get('search'))));
    if($s && strlen($s) > 2){
        $post_tags = array();
    } else{
        $s = '';
    }

    $gmediaLib = array();
    $relative  = (int) $gmCore->_get('rel', 1);
    $continue  = true;
    $content   = '';

    if($relative == 1){
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

    if(empty($gmediaLib) && count($post_tags)){

        if($relative == 1){
            $relative = 0;
            $paged    = 1;
            $content  .= '<li class="emptydb">' . __('No items related by tags.', 'grand-media') . '</li>' . "\n";
        }

        $tag__not_in = "'" . implode("','", array_map('esc_sql', array_unique((array) $post_tags))) . "'";
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

    if(($count = count($gmediaLib))){
        foreach($gmediaLib as $item){
            $content .= "<li class='gmedia-image-li' id='gm-img-{$item->ID}'>\n";
            $content .= "	<a target='_blank' class='gm-img' data-gmid='{$item->ID}' href='" . $gmCore->gm_get_media_image($item) . "'><img src='" . $gmCore->gm_get_media_image($item, 'thumb') . "' height='50' style='width:auto;' alt='' title='" . esc_attr($item->title) . "' /></a>\n";
            $content .= "	<div style='display: none;' class='gm-img-description'>" . esc_html($item->description) . "</div>\n";
            $content .= "</li>\n";
        }
        if(($count < $per_page) && ($relative == 0 || !empty($s))){
            $continue = false;
        }
    } else{
        if($s){
            $content .= '<li class="emptydb">' . __('No items matching the search query.', 'grand-media') . '</li>' . "\n";
        } else{
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
 * @return string folder content
 */
function gmedia_ftp_browser(){
    if( !current_user_can('gmedia_import')){
        die('No access');
    }

    // if nonce is not correct it returns -1
    check_ajax_referer('GmediaGallery');

    // start from the default path
    $root = trailingslashit(ABSPATH);
    // get the current directory
    $dir = trailingslashit(urldecode($_POST['dir']));

    if((false === strpos($dir, '..')) && file_exists($root . $dir)){
        $files = scandir($root . $dir);
        natcasesort($files);

        // The 2 counts for . and ..
        if(count($files) > 2){
            echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
            // return only directories
            foreach($files as $file){
                if(in_array($file, array('wp-admin', 'wp-includes', 'plugins', 'themes', 'thumb', 'thumbs'))){
                    continue;
                }

                if(file_exists($root . $dir . $file) && $file != '.' && $file != '..' && is_dir($root . $dir . $file)){
                    echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . esc_attr($dir . $file) . "/\">" . esc_html($file) . "</a></li>";
                }
            }
            // All files
            foreach($files as $file){
                if(file_exists($root . $dir . $file) && $file != '.' && $file != '..' && !is_dir($root . $dir . $file)){
                    $ext = preg_replace('/^.*\./', '', $file);
                    if($file === '.' . $ext){
                        continue;
                    }
                    echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . esc_attr($dir) . "\">" . esc_html($file) . "</a></li>";
                }
            }
            echo "</ul>";
        }
    }

    die();
}

add_action('wp_ajax_gmedia_set_post_thumbnail', 'gmedia_set_post_thumbnail');
function gmedia_set_post_thumbnail(){
    global $gmCore, $gmDB, $gmGallery;

    $post_ID = intval($gmCore->_post('post_id', 0));

    if( !$post_ID || !current_user_can('edit_post', $post_ID)){
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

    if($img_id){

        $image = $gmDB->get_gmedia($img_id);
        if($image){

            $args          = array(
                'post_type'    => 'attachment',
                'meta_key'     => '_gmedia_image_id',
                'meta_compare' => '==',
                'meta_value'   => $img_id
            );
            $posts         = get_posts($args);
            $attachment_id = null;

            if($posts != null){
                $attachment_id = $posts[0]->ID;
                //$target_path   = get_attached_file( $attachment_id );
            } else{
                $upload_dir = wp_upload_dir();
                $basedir    = $upload_dir['basedir'];
                $thumbs_dir = implode(DIRECTORY_SEPARATOR, array($basedir, 'gmedia_featured'));

                $type = explode('/', $image->mime_type);

                $url           = $gmCore->upload['url'] . '/' . $gmGallery->options['folder'][ $type[0] ] . '/' . $image->gmuid;
                $image_abspath = $gmCore->upload['path'] . '/' . $gmGallery->options['folder'][ $type[0] ] . '/' . $image->gmuid;

                $img_name    = current_time('ymd_Hi') . '_' . basename($image->gmuid);
                $target_path = path_join($thumbs_dir, $img_name);
                wp_mkdir_p($thumbs_dir);

                if(@copy($image_abspath, $target_path)){
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

            if($attachment_id){
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
function gmedia_upload_handler(){
    global $gmCore;

    ini_set('max_execution_time', 300);

    // HTTP headers for no cache etc
    send_nosniff_header();
    //send_origin_headers();
    nocache_headers();

    // if nonce is not correct it returns -1
    check_ajax_referer('gmedia_upload', '_wpnonce_upload');
    if( !current_user_can('gmedia_upload')){
        wp_die(__('You do not have permission to upload files in Gmedia Library.'));
    }

    // 5 minutes execution time
    @set_time_limit(5 * 60);

    // fake upload time
    usleep(10);

    $filename = $gmCore->_req('name');

    // Get parameters
    if( !$filename){
        $return = json_encode(array("error" => array("code" => 100, "message" => __("No file name.", 'grand-media'))));
        die($return);
    }

    $fileinfo = $gmCore->fileinfo($filename);
    if(false === $fileinfo){
        $return = json_encode(array("error" => array("code" => 100, "message" => __("File type not allowed.", 'grand-media')), "id" => $filename));
        die($return);
    }

    // Look for the content type header
    $contentType = '';
    if(isset($_SERVER["HTTP_CONTENT_TYPE"])){
        $contentType = $_SERVER["HTTP_CONTENT_TYPE"];
    }

    if(isset($_SERVER["CONTENT_TYPE"])){
        $contentType = $_SERVER["CONTENT_TYPE"];
    }

    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    $file_tmp = '';
    if(strpos($contentType, "multipart") !== false){
        if(isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])){
            $file_tmp = $_FILES['file']['tmp_name'];
        }
    } else{
        $file_tmp = "php://input";
    }

    if(empty($file_tmp)){
        $return = json_encode(array("error" => array("code" => 103, "message" => __("Failed to move uploaded file.", 'grand-media')), "id" => $filename));
        die($return);
    }

    $post_data = array();
    if(($params = $gmCore->_req('params', ''))){
        parse_str($params, $post_data);
    }

    $return = $gmCore->gmedia_upload_handler($file_tmp, $fileinfo, $contentType, $post_data);
    $return = json_encode($return);

    die($return);
}

add_action('wp_ajax_gmedia_import_handler', 'gmedia_import_handler');
function gmedia_import_handler(){
    global $wpdb, $gmCore, $gmDB;

    ini_set('max_execution_time', 600);

    // HTTP headers for no cache etc
    send_nosniff_header();
    //send_origin_headers();
    nocache_headers();

    check_admin_referer('gmedia_import', '_wpnonce_import');
    if( !current_user_can('gmedia_import')){
        wp_die(__('You do not have permission to upload files.'));
    }

    // 10 minutes execution time
    @set_time_limit(10 * 60);

    // fake upload time
    usleep(10);

    $import = $gmCore->_post('import');
    $terms  = $gmCore->_post('terms', array());

    if(ob_get_level() == 0){
        ob_start();
    }
    echo str_pad(' ', 4096) . PHP_EOL;
    wp_ob_end_flush_all();
    flush();
    ?>
    <html>
    <style type="text/css">
        * { margin: 0; padding: 0; }
        pre { display: block; }
        p { padding: 10px 0; font-size: 14px; }
        .ok { color: darkgreen; }
        .ko { color: darkred; }
    </style>
    <body>
    <?php
    if(('import-folder' == $import) || isset($_POST['import-folder'])){

        $path = $gmCore->_post('path');
        echo '<h4 style="margin: 0 0 10px">' . __('Import Server Folder') . " `$path`:</h4>" . PHP_EOL;

        if($path){
            $path = trim(urldecode($path), '/');
            if( !empty($path)){
                $fullpath = ABSPATH . trailingslashit($path);
                $files    = glob($fullpath . '?*.?*', GLOB_NOSORT);
                if( !empty($files)){
                    $allowed_ext = get_allowed_mime_types();
                    $allowed_ext = array_keys($allowed_ext);
                    $allowed_ext = implode('|', $allowed_ext);
                    $allowed_ext = explode('|', $allowed_ext);
                    if((GMEDIA_UPLOAD_FOLDER == basename(dirname(dirname($path)))) || (GMEDIA_UPLOAD_FOLDER == basename(dirname($path)))){
                        global $wpdb;
                        $gmedias = $wpdb->get_col("SELECT gmuid FROM {$wpdb->prefix}gmedia");
                        foreach($files as $i => $filepath){
                            $gmuid = basename($filepath);
                            if(in_array($gmuid, $gmedias)){
                                $fileinfo = $gmCore->fileinfo($gmuid, false);
                                if( !(('image' == $fileinfo['dirname']) && !is_file($fileinfo['filepath']))){
                                    unset($files[ $i ]);
                                }
                            }
                        }
                        $move   = false;
                        $exists = false;
                    } else{
                        $move   = $gmCore->_post('delete_source');
                        $exists = $gmCore->_post('skip_exists', 0);
                    }
                    foreach($files as $i => $filepath){
                        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
                        if( !in_array(strtolower($ext), $allowed_ext)){
                            unset($files[ $i ]);
                        }
                    }
                    $gmCore->gmedia_import_files($files, $terms, $move, $exists);
                } else{
                    echo sprintf(__('Folder `%s` is empty', 'grand-media'), $path) . PHP_EOL;
                }
            } else{
                echo __('No folder chosen', 'grand-media') . PHP_EOL;
            }
        }
    } elseif(('import-flagallery' == $import) || isset($_POST['import-flagallery'])){

        echo '<h4 style="margin: 0 0 10px">' . __('Import from Flagallery plugin') . ":</h4>" . PHP_EOL;

        $gallery = $gmCore->_post('gallery');
        if( !empty($gallery)){
            $album = ( !isset($terms['gmedia_album']) || empty($terms['gmedia_album']))? false : true;
            foreach($gallery as $gid){
                $flag_gallery = $wpdb->get_row($wpdb->prepare("SELECT gid, path, title, galdesc FROM {$wpdb->prefix}flag_gallery WHERE gid = %d", $gid), ARRAY_A);
                if(empty($flag_gallery)){
                    continue;
                }

                if( !$album){
                    $terms['gmedia_album'] = $flag_gallery['title'];
                    if($gmCore->is_digit($terms['gmedia_album'])){
                        $terms['gmedia_album'] = 'a' . $terms['gmedia_album'];
                    }
                    if( !$gmDB->term_exists($terms['gmedia_album'], 'gmedia_album')){
                        $term_id = $gmDB->insert_term($terms['gmedia_album'], 'gmedia_album', array('description' => htmlspecialchars_decode(stripslashes($flag_gallery['galdesc']))));
                    }
                }

                $path = ABSPATH . trailingslashit($flag_gallery['path']);

                echo '<h5 style="margin: 10px 0 5px">' . sprintf(__('Import `%s` gallery', 'grand-media'), $flag_gallery['title']) . ":</h5>" . PHP_EOL;

                $flag_pictures = $wpdb->get_results($wpdb->prepare("SELECT CONCAT('%s', filename) AS file, description, alttext AS title, link FROM {$wpdb->prefix}flag_pictures WHERE galleryid = %d", $path, $flag_gallery['gid']), ARRAY_A);
                if(empty($flag_pictures)){
                    echo '<pre>' . __('gallery contains 0 images', 'grand-media') . '</pre>';
                    continue;
                }
                $exists = $gmCore->_post('skip_exists', 0);

                //echo '<pre>'.print_r($flag_pictures, true).'</pre>';
                $gmCore->gmedia_import_files($flag_pictures, $terms, false, $exists);
            }
        } else{
            echo __('No gallery chosen', 'grand-media') . PHP_EOL;
        }
    } elseif(('import-nextgen' == $import) || isset($_POST['import-nextgen'])){

        echo '<h4 style="margin: 0 0 10px">' . __('Import from NextGen plugin') . ":</h4>" . PHP_EOL;

        $gallery = $gmCore->_post('gallery');
        if( !empty($gallery)){
            $album = ( !isset($terms['gmedia_album']) || empty($terms['gmedia_album']))? false : true;
            foreach($gallery as $gid){
                $ngg_gallery = $wpdb->get_row($wpdb->prepare("SELECT gid, path, title, galdesc FROM {$wpdb->prefix}ngg_gallery WHERE gid = %d", $gid), ARRAY_A);
                if(empty($ngg_gallery)){
                    continue;
                }

                if( !$album){
                    $terms['gmedia_album'] = $ngg_gallery['title'];
                    if($gmCore->is_digit($terms['gmedia_album'])){
                        $terms['gmedia_album'] = 'a' . $ngg_gallery['title'];
                    }
                    if( !$gmDB->term_exists($terms['gmedia_album'], 'gmedia_album')){
                        $term_id = $gmDB->insert_term($terms['gmedia_album'], 'gmedia_album', array('description' => htmlspecialchars_decode(stripslashes($ngg_gallery['galdesc']))));
                    }
                }

                $path = ABSPATH . trailingslashit($ngg_gallery['path']);

                echo '<h5 style="margin: 10px 0 5px">' . sprintf(__('Import `%s` gallery', 'grand-media'), $ngg_gallery['title']) . ":</h5>" . PHP_EOL;

                $ngg_pictures = $wpdb->get_results($wpdb->prepare("SELECT CONCAT('%s', filename) AS file, description, alttext AS title FROM {$wpdb->prefix}ngg_pictures WHERE galleryid = %d", $path, $ngg_gallery['gid']), ARRAY_A);
                if(empty($ngg_pictures)){
                    echo '<pre>' . __('gallery contains 0 images', 'grand-media') . '</pre>';
                    continue;
                }
                $exists = $gmCore->_post('skip_exists', 0);

                $gmCore->gmedia_import_files($ngg_pictures, $terms, false, $exists);
            }
        } else{
            echo __('No gallery chosen', 'grand-media') . PHP_EOL;
        }
    } elseif(('import-wpmedia' == $import) || isset($_POST['import-wpmedia'])){

        echo '<h4 style="margin: 0 0 10px">' . __('Import from WP Media Library') . ":</h4>" . PHP_EOL;

        $wpMediaLib = $gmDB->get_wp_media_lib(array('filter' => 'selected', 'selected' => $gmCore->_post('selected')));

        if( !empty($wpMediaLib)){

            $wp_media = array();
            foreach($wpMediaLib as $item){
                $wp_media[] = array(
                    'file'        => get_attached_file($item->ID),
                    'title'       => $item->post_title,
                    'description' => $item->post_content
                );
            }
            $exists = $gmCore->_post('skip_exists', 0);
            //echo '<pre>' . print_r($wp_media, true) . '</pre>';
            $gmCore->gmedia_import_files($wp_media, $terms, false, $exists);

        } else{
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
function gmedia_application(){
    global $gmCore, $gmGallery;

    // if nonce is not correct it returns -1
    check_ajax_referer('GmediaService');
    if( !current_user_can('manage_options')){
        die('-1');
    }

    $service = $gmCore->_post('service');
    if( !$service){
        die('0');
    }
    $options = $gmGallery->options;

    if('skip' === $service){
        $options['gmedia_service'] = $service;
        if((int)$options['mobile_app']){
            $options['mobile_app'] = 0;
            $service               = 'app_deactivate';
        } else{
            $gmGallery->options = $options;
            update_option('gmediaOptions', $options);
            wp_send_json_success();
        }
    } elseif('allow' === $service){
        $options['gmedia_service'] = $service;
        $service                   = 'app_activate';
    }

    if($options != $gmGallery->options){
        $gmGallery->options = $options;
        update_option('gmediaOptions', $options);
    }

    $result = $gmCore->app_service($service);

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);

    die();
}

add_action('wp_ajax_gmedia_share_page', 'gmedia_share_page');
function gmedia_share_page(){
    global $gmCore, $user_ID;
    // if nonce is not correct it returns -1
    check_ajax_referer('gmedia_share', '_wpnonce_share');

    $sharelink    = $gmCore->_post('sharelink', '');
    $email        = $gmCore->_post('email', '');
    $sharemessage = $gmCore->_post('message', '');
    if( !filter_var($email, FILTER_VALIDATE_EMAIL)){
        echo $gmCore->alert('danger', __('Invalid email', 'grand-media') . ': ' . esc_html($email));
        die();
    }

    $display_name  = get_the_author_meta('display_name', $user_ID);
    $subject       = sprintf(__('%s shared GmediaCloud Page with you', 'grand-media'), $display_name);
    $sharetitle    = sprintf(__('%s used Gmedia to share something interesting with you!', 'grand-media'), $display_name);
    $sharelinktext = __('Click here to view page', 'grand-media');
    if($sharemessage){
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
    if(wp_mail($email, $subject, $message, $headers)){
        echo $gmCore->alert('success', sprintf(__('Message sent to %s', 'grand-media'), $email));
    }

    die();
}

add_action('wp_ajax_gmedia_add_custom_field', 'gmedia_add_custom_field');
function gmedia_add_custom_field(){
    global $gmDB, $user_ID, $gmCore;
    check_ajax_referer('gmedia_custom_field', '_wpnonce_custom_field');

    $meta_type = 'gmedia';

    $pid  = (int) $_POST['ID'];
    $post = $gmDB->get_gmedia($pid);

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);

    if( !current_user_can('gmedia_edit_media') || ($user_ID != $post->author && !current_user_can('gmedia_edit_others_media'))){
        echo json_encode(array('error' => array('code' => 100, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $pid));
        die();
    }
    if(isset($_POST['metakeyselect']) && empty($_POST['metakeyselect']) && empty($_POST['metakeyinput'])){
        echo json_encode(array('error' => array('code' => 101, 'message' => __('Choose or provide a custom field name', 'grand-media')), 'id' => $pid));
        die();
    }

    if( !$mid = $gmCore->add_meta($pid, $meta_type)){
        echo json_encode(array('error' => array('code' => 102, 'message' => __('Please provide a custom field value', 'grand-media')), 'id' => $pid));
        die();
    }

    $column = sanitize_key($meta_type . '_id');
    $meta   = $gmDB->get_metadata_by_mid($meta_type, $mid);
    $pid    = (int) $meta->{$column};
    $meta   = get_object_vars($meta);
    $result = array(
        'success' => array(
            'meta_id' => $mid,
            'data'    => $gmCore->_list_meta_item($meta, $meta_type)
        ),
        'id'      => $pid
    );

    if( !empty($_POST['metakeyinput'])){
        $result['newmeta_form'] = $gmCore->meta_form($meta_type);
    }

    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_delete_custom_field', 'gmedia_delete_custom_field');
function gmedia_delete_custom_field(){
    global $gmDB, $user_ID, $gmCore;
    check_ajax_referer('gmedia_custom_field', '_wpnonce_custom_field');

    $meta_type = 'gmedia';

    $pid  = (int) $_POST['ID'];
    $post = $gmDB->get_gmedia($pid);

    if( !current_user_can('gmedia_edit_media') || ($user_ID != $post->author && !current_user_can('gmedia_edit_others_media'))){
        echo json_encode(array('error' => array('code' => 100, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $pid));
        die();
    }

    $result = array('id' => $pid);

    $deletemeta = $_POST['meta'];
    $column     = sanitize_key($meta_type . '_id');
    if(isset($deletemeta) && is_array($deletemeta)){
        foreach($deletemeta as $key => $value){
            if( !$meta = $gmDB->get_metadata_by_mid($meta_type, $key)){
                continue;
            }
            if($meta->{$column} != $pid){
                continue;
            }
            if($gmCore->is_protected_meta($meta->meta_key, $meta_type)){
                continue;
            }
            if(($del_meta = $gmDB->delete_metadata_by_mid($meta_type, $key))){
                $result['deleted'][] = $key;
            }
        }
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_term_add_custom_field', 'gmedia_term_add_custom_field');
function gmedia_term_add_custom_field(){
    global $gmDB, $user_ID, $gmCore;
    check_ajax_referer('gmedia_custom_field', '_wpnonce_custom_field');

    $meta_type = 'gmedia_term';

    $pid  = (int) $_POST['ID'];
    $post = $gmDB->get_term($pid);

    $taxonomy = $post->taxonomy;

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);

    if( !current_user_can($taxonomy . '_manage') || ($user_ID != $post->global && !current_user_can('gmedia_edit_others_media'))){
        echo json_encode(array('error' => array('code' => 100, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $pid));
        die();
    }
    if(isset($_POST['metakeyselect']) && empty($_POST['metakeyselect']) && empty($_POST['metakeyinput'])){
        echo json_encode(array('error' => array('code' => 101, 'message' => __('Choose or provide a custom field name', 'grand-media')), 'id' => $pid));
        die();
    }

    if( !$mid = $gmCore->add_meta($pid, $meta_type)){
        echo json_encode(array('error' => array('code' => 102, 'message' => __('Please provide a custom field value', 'grand-media')), 'id' => $pid));
        die();
    }

    $column = sanitize_key($meta_type . '_id');
    $meta   = $gmDB->get_metadata_by_mid($meta_type, $mid);
    $pid    = (int) $meta->{$column};
    $meta   = get_object_vars($meta);
    $result = array(
        'success' => array(
            'meta_id' => $mid,
            'data'    => $gmCore->_list_meta_item($meta, $meta_type)
        ),
        'id'      => $pid
    );

    if( !empty($_POST['metakeyinput'])){
        $result['newmeta_form'] = $gmCore->meta_form($meta_type);
    }

    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_term_delete_custom_field', 'gmedia_term_delete_custom_field');
function gmedia_term_delete_custom_field(){
    global $gmDB, $user_ID, $gmCore;
    check_ajax_referer('gmedia_custom_field', '_wpnonce_custom_field');

    $meta_type = 'gmedia_term';

    $pid  = (int) $_POST['ID'];
    $post = $gmDB->get_term($pid);

    $taxonomy = $post->taxonomy;

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);

    if( !current_user_can($taxonomy . '_manage') || ($user_ID != $post->global && !current_user_can('gmedia_edit_others_media'))){
        echo json_encode(array('error' => array('code' => 100, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $pid));
        die();
    }

    $result = array('id' => $pid);

    $deletemeta = $_POST['meta'];
    $column     = sanitize_key($meta_type . '_id');
    if(isset($deletemeta) && is_array($deletemeta)){
        foreach($deletemeta as $key => $value){
            if( !$meta = $gmDB->get_metadata_by_mid($meta_type, $key)){
                continue;
            }
            if($meta->{$column} != $pid){
                continue;
            }
            if($gmCore->is_protected_meta($meta->meta_key, $meta_type)){
                continue;
            }
            if(($del_meta = $gmDB->delete_metadata_by_mid($meta_type, $key))){
                $result['deleted'][] = $key;
            }
        }
    }

    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_term_sortorder', 'gmedia_term_sortorder');
function gmedia_term_sortorder(){
    global $gmDB, $user_ID, $gmCore;
    check_ajax_referer('gmedia_terms', '_wpnonce_terms');

    $term_id = $gmCore->_post('term_id');
    $idx0    = (int) $gmCore->_post('idx0');
    $ids     = $gmCore->_post('ids');

    if( !$idx0 || !is_array($ids)){
        die();
    }

    if( !current_user_can('gmedia_album_manage')){
        wp_send_json(array('error' => array('code' => 100, 'message' => __('You are not allowed to manage this taxonomy', 'grand-media')), 'id' => $term_id));
    }

    if( !$term_id || !($term_id = $gmDB->term_exists($term_id))){
        wp_send_json(array('error' => array('code' => 101, 'message' => __('A term with the id provided does not exists', 'grand-media')), 'id' => $term_id));
    }
    $term = $gmDB->get_term($term_id);
    if(((int) $term->global != (int) $user_ID) && !current_user_can('gmedia_edit_others_media')){
        wp_send_json(array('error' => array('code' => 102, 'message' => __('You are not allowed to edit others media', 'grand-media')), 'id' => $term_id));
    }

    $gm_ids_order = array();
    foreach($ids as $id){
        $gm_ids_order[ $id ] = $idx0;
        $idx0 ++;
    }

    $term_id = $gmDB->update_term_sortorder($term_id, $gm_ids_order);
    if(is_wp_error($term_id)){
        wp_send_json(array('error' => array('code' => 103, 'message' => $term_id->get_error_message()), 'id' => $term_id));
    }

    wp_send_json_success($term_id);
}

add_action('wp_ajax_gmedia_upgrade_process', 'gmedia_upgrade_process');
function gmedia_upgrade_process(){

    $db_version = get_option('gmediaDbVersion');
    $info       = get_transient('gmediaHeavyJob');
    $result     = array('content' => '');

    $upgrading = get_transient('gmediaUpgrade');
    if($upgrading){
        $timeout = time() - $upgrading;
    } else{
        $timeout = 0;
    }
    if($timeout > 20){
        require_once(GMEDIA_ABSPATH . 'config/update.php');
        gmedia_db_update();
    }
    $result['timeout'] = $timeout;

    if( !empty($info)){
        $result['content'] = '<div>' . implode("</div>\n<div>", $info) . '</div>';
    } elseif($db_version == GMEDIA_DBVERSION){
        $result['status'] = 'done';
    }

    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($result);
    die();

}

add_action('wp_ajax_gmedia_hash_files', 'gmedia_hash_files');
function gmedia_hash_files(){
    global $wpdb, $gmCore, $gmDB;

    check_ajax_referer('gmedia_ajax_long_operations', '_wpnonce_ajax_long_operations');

    $all_count = wp_cache_get('gmedia_count_all');
    if(false === $all_count){
        $all_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gmedia");
        wp_cache_set('gmedia_count_all', $all_count);
    }
    $sql      = "SELECT SQL_CALC_FOUND_ROWS ID, gmuid FROM {$wpdb->prefix}gmedia AS g WHERE 1 = 1
                      AND ( NOT EXISTS ( SELECT * FROM {$wpdb->prefix}gmedia_meta AS gm WHERE gm.meta_key = '_hash' AND gm.gmedia_id = g.ID )
                            OR NOT EXISTS ( SELECT * FROM {$wpdb->prefix}gmedia_meta AS gm2 WHERE gm2.meta_key = '_size' AND gm2.gmedia_id = g.ID)
                          )
                      LIMIT 20";
    $unhashed = $wpdb->get_results($sql);
    if( !$unhashed){
        $ajax_operations = get_option('gmedia_ajax_long_operations', array());
        unset($ajax_operations['gmedia_hash_files']);
        if(empty($ajax_operations)){
            delete_option('gmedia_ajax_long_operations');
        } else{
            update_option('gmedia_ajax_long_operations', $ajax_operations);
        }
        wp_cache_delete('gmedia_count_all');

        wp_send_json_success(array('progress' => '100%', 'info' => __('Indexing:', 'grand-media'), 'done' => true));
    }

    $unhashed_count = $wpdb->get_var('SELECT FOUND_ROWS()');

    foreach($unhashed as $item){
        $fileinfo  = $gmCore->fileinfo($item->gmuid, false);
        $filepath  = is_file($fileinfo['filepath_original'])? $fileinfo['filepath_original'] : $fileinfo['filepath'];
        $hash_file = hash_file('md5', $filepath);
        $gmDB->update_metadata($meta_type = 'gmedia', $item->ID, $meta_key = '_hash', $hash_file);
        $file_size = filesize($filepath);
        $gmDB->update_metadata($meta_type = 'gmedia', $item->ID, $meta_key = '_size', $file_size);
    }

    $progress = round(($all_count - $unhashed_count) * 100 / $all_count);

    wp_send_json_success(array('progress' => "{$progress}%", 'info' => __('Indexing:', 'grand-media')));
}

add_action('wp_ajax_gmedia_recreate_images', 'gmedia_recreate_images');
function gmedia_recreate_images(){
    global $gmCore;

    check_ajax_referer('gmedia_ajax_long_operations', '_wpnonce_ajax_long_operations');

    $gmid            = 0;
    $ajax_operations = get_option('gmedia_ajax_long_operations', array());
    if( !empty($ajax_operations['gmedia_recreate_images'])){
        $all_count    = count($ajax_operations['gmedia_recreate_images']);
        $recreate_ids = array_filter($ajax_operations['gmedia_recreate_images']);
        $do_count     = count($recreate_ids);

        if( !empty($recreate_ids)){
            $gmid = reset($recreate_ids);
            $gmCore->recreate_images_from_original($gmid);

            $ajax_operations['gmedia_recreate_images'][ $gmid ] = false;
            update_option('gmedia_ajax_long_operations', $ajax_operations);
        } else{
            unset($ajax_operations['gmedia_recreate_images']);
        }

        if(empty($ajax_operations)){
            delete_option('gmedia_ajax_long_operations');

            wp_send_json_success(array('progress' => '100%', 'info' => __('Done:', 'grand-media'), 'done' => true, 'id' => $gmid));
        } else{
            $progress = round(($all_count - $do_count) * 100 / $all_count);

            wp_send_json_success(array('progress' => "{$progress}%", 'info' => __('Working:', 'grand-media'), 'id' => $gmid));
        }
    }

    wp_send_json_success(array('progress' => '100%', 'info' => __('Done:', 'grand-media'), 'done' => true, 'id' => $gmid));
}

add_action('wp_ajax_gmedia_feedback', 'gmedia_feedback');
function gmedia_feedback(){
    global $gmCore;

    check_ajax_referer('gmedia_feedback', '_wpnonce_gmedia_feedback');

    $data = $gmCore->_post('data');

    if(!empty($data)){
        $current_user = wp_get_current_user();
        $title   = "Deactivate Reason: " . urldecode($data['reason']);
        $content = 'Website: ' . home_url() . "\r\n\r\n";
        foreach($data as $key=>$val){
            $content .= ucwords(str_replace('_', ' ', $key)) . ': ' . urldecode($val) . "\r\n\r\n";
        }
        $headers = array(
            "From: Gmedia Feedback <feedback@gmedia.gallery>",
            "Reply-To: {$current_user->display_name} <{$current_user->user_email}>"
        );
        wp_mail('codeasily@gmail.com', $title, $content, $headers);
    }

    wp_send_json_success();
}

add_action('wp_ajax_gmedia_save_waveform', 'gmedia_save_waveform');
add_action('wp_ajax_nopriv_gmedia_save_waveform', 'gmedia_save_waveform');
function gmedia_save_waveform(){
    global $gmCore, $gmDB;

    check_ajax_referer('GmediaGallery');

    $id    = $gmCore->_post('id');
    $peaks = $gmCore->_post('peaks');
    if($id && $peaks){
        $peaks_arr = json_decode($peaks);
        $peaks_arr = array_filter($peaks_arr, 'is_numeric');
        if(3600 !== count($peaks_arr)){
            wp_send_json_error(array('peaks_cnt' => count($peaks_arr)));
        }

        $gmDB->update_metadata('gmedia', $id, '_peaks', $peaks);
        do_action( 'clean_gmedia_cache', $id );
        wp_send_json_success(array('peaks' => $peaks));
    } else{
        wp_send_json_error();
    }
}

add_action('wp_ajax_gmedia_module_interaction', 'gmedia_module_interaction');
add_action('wp_ajax_nopriv_gmedia_module_interaction', 'gmedia_module_interaction');
function gmedia_module_interaction(){
    global $gmDB, $gmCore;

    if(empty($_SERVER['HTTP_REFERER'])){
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die();
    }

    $ref = $_SERVER['HTTP_REFERER'];
    //$uip = str_replace('.', '', $_SERVER['REMOTE_ADDR'])
    if((false === strpos($ref, get_home_url())) && (false === strpos($ref, get_site_url()))){
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die();
    }
    if(('POST' !== $_SERVER['REQUEST_METHOD']) || !isset($_SERVER['HTTP_HOST']) || !strpos(get_home_url(), $_SERVER['HTTP_HOST'])){
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        die();
    }


    if(isset($_POST['hit']) && ($gmID = intval($_POST['hit']))){
        if(null === $gmDB->get_gmedia($gmID)){
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

    if(isset($_POST['rate'])){
        /**
         * @var $uip
         * @var $gmid
         * @var $rate
         */
        extract($_POST['rate'], EXTR_OVERWRITE);
        if( !intval($gmid) || (null === $gmDB->get_gmedia($gmid))){
            die('0');
        }
        $rating   = $gmDB->get_metadata('gmedia', $gmid, '_rating', true);
        $rating   = wp_parse_args((array) $rating, array('votes' => 0, 'value' => 0));
        $old_rate = 0;

        $transient_key   = 'gm_rate_day' . date('w');
        $transient_value = get_transient($transient_key);
        if(false !== $transient_value){
            if(isset($transient_value[ $uip ][ $gmid ])){
                $old_rate = $transient_value[ $uip ][ $gmid ];
            }
            $transient_value[ $uip ][ $gmid ] = $rate;
        } else{
            $transient_value = array($uip => array($gmid => $rate));
        }
        set_transient($transient_key, $transient_value, 18 * HOUR_IN_SECONDS);

        do_action('gmedia_rate', $gmid, $rating['value']);

        $rating['votes'] = $old_rate? $rating['votes'] : $rating['votes'] + 1;
        $rating['value'] = ($rating['value'] * $rating['votes'] + $rate - $old_rate) / $rating['votes'];

        $gmDB->update_metadata('gmedia', $gmid, '_rating', $rating);

        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode(array($rating));
        die();
    }

    die();
}

add_action('wp_ajax_load_comments', 'gmedia_module_load_comments');
add_action('wp_ajax_nopriv_load_comments', 'gmedia_module_load_comments');
function gmedia_module_load_comments(){
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

    $post_id = (int) $data['post_id'];
    if($post_id){
        $comments_link  = apply_filters('gmedia_comments_link', add_query_arg('comments', 'show', get_permalink($post_id)), $post_id);
        $comments_count = wp_count_comments($post_id);
        $comments_count = $comments_count->approved;
    } else{
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

add_action('wp_ajax_gmedia_get_data', 'gmedia_get_data');
add_action('wp_ajax_nopriv_gmedia_get_data', 'gmedia_get_data');
function gmedia_get_data(){
	global $gmDB, $gmProcessor, $gmGallery;

	/** @var $gmProcessorLibrary */
	include_once(GMEDIA_ABSPATH . 'admin/processor/class.processor.library.php');

	$gmProcessorLibrary->user_options = $gmProcessor::user_options();
	$query_args = $gmProcessorLibrary->query_args();

	$cache_expiration = isset($gmGallery->options['cache_expiration'])? (int) $gmGallery->options['cache_expiration'] * HOUR_IN_SECONDS : 24 * HOUR_IN_SECONDS;
	if($cache_expiration) {
		$cache_key   = 'gm_cache_' . md5( json_encode( $query_args ) );
		$cache_value = get_transient( $cache_key );
	}

	if(!empty( $cache_value)) {
		header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
		echo $cache_value;
	} else {
		$gmedia_query = $gmDB->get_gmedias( $query_args );
		foreach ( $gmedia_query as &$item ) {
			gmedia_item_more_data( $item );
		}
		$json_string = json_encode( $gmedia_query );
		if($cache_expiration) {
			set_transient( $cache_key, $json_string, $cache_expiration );
		}

		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
		echo $json_string;
	}

    die();
}

