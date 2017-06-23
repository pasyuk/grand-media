<?php

function gmedia_item_thumbnail($item){
    global $gmCore;

    $images = $gmCore->gm_get_media_image($item, 'all');
    $thumb = '<img class="gmedia-thumb" src="' . $images['thumb'] . '" alt=""/>';

    if(!empty($images['icon'])){
        $thumb .= '<img class="gmedia-typethumb" src="' . $images['icon'] . '" alt=""/>';
    }

    return $thumb;
}

function gmedia_item_actions($item){
    global $gmCore, $gmProcessor;

    if(!in_array($gmProcessor->mode, array('select_single', 'select_multiple'))){
        $share_icon = '<span class="glyphicon glyphicon-share"></span>';
        if('draft' !== $item->status){
            if(!empty($item->post_id)){
                $cloud_link = get_permalink($item->post_id);
            } else{
                $cloud_link = $gmCore->gmcloudlink($item->ID, 'single');
            }
            $share = '<a target="_blank" data-target="#shareModal" data-share="' . $item->ID . '" class="share-modal" title="' . __('Share Gmedia Post', 'grand-media') . '" href="' . $cloud_link . '">' . $share_icon . '</a>';
        } else{
            $share = "<span class='action-inactive'>$share_icon</span>";
        }

        $edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
        if(gm_user_can('edit_media')){
            if(((int)$item->author == get_current_user_id()) || gm_user_can('edit_others_media')){
                $edit_data_data = $gmProcessor->gmediablank? '' : ' data-target="#previewModal" data-width="1200" data-height="500" data-cls="edit_gmedia_item" class="preview-modal"';
                $edit_data      = '<a href="' . add_query_arg(array('page' => 'GrandMedia', 'mode' => 'edit', 'gmediablank' => 'library', 'gmedia__in' => $item->ID), $gmProcessor->url) . '"' . $edit_data_data . ' id="gmdataedit' . $item->ID . '" title="' . __('Edit Data', 'grand-media') . '">' . $edit_icon . '</a>';
            }
        } else{
            $edit_data = "<span class='action-inactive'>$edit_icon</span>";
        }

        $info_icon = '<span class="glyphicon glyphicon-info-sign"></span>';
        $metainfo  = $gmCore->metadata_text($item->ID);
        if($metainfo){
            $info = '<a href="#metaInfo" data-target="#previewModal" data-metainfo="' . $item->ID . '" class="preview-modal" title="' . __('Exif/Meta Info', 'grand-media') . '">' . $info_icon . '</a>';
            $info .= '<div class="metainfo hidden" id="metainfo_' . $item->ID . '">' . nl2br($metainfo) . '</div>';
        } else{
            $info = "<span class='action-inactive'>$info_icon</span>";
        }

        $delete_icon = '<span class="glyphicon glyphicon-trash"></span>';
        if((gm_user_can('delete_media') && ((int)$item->author == get_current_user_id())) || gm_user_can('delete_others_media')){
            $delete = '<a class="text-danger" href="' . wp_nonce_url(gm_get_admin_url(array('do_gmedia' => 'delete',
                                                                                            'ids'       => $item->ID
                                                                                      )), 'gmedia_delete', '_wpnonce_delete') . '" data-confirm="' . sprintf(__("You are about to permanently delete %s file.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"), $item->gmuid) . '" title="' . __('Delete', 'grand-media') . '">' . $delete_icon . '</a>';

            if($gmCore->_get('showmore')){
                $erase_icon = '<span class="glyphicon glyphicon-erase"></span>';
                $db_delete  = '<a class="text-danger" href="' . wp_nonce_url(gm_get_admin_url(array('do_gmedia' => 'delete__save_original',
                                                                                                    'ids'       => $item->ID
                                                                                              )), 'gmedia_delete', '_wpnonce_delete') . '" data-confirm="' . sprintf(__("You are about to delete record from DB for %s file.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"), $item->gmuid) . '" title="' . __('Delete DB record (leave file on the server)', 'grand-media') . '">' . $erase_icon . '</a>';
            }
        } else{
            $delete = "<span class='action-inactive'>$delete_icon</span>";
        }
    }

    if('image' == $item->type && $item->editor){
        $edit_image_icon = '<span class="glyphicon glyphicon-adjust"></span>';
        if((gm_user_can('edit_media') && ((int)$item->author == get_current_user_id())) || gm_user_can('edit_others_media')){
            $edit_image = '<a href="' . add_query_arg(array('page' => 'GrandMedia', 'gmediablank' => 'image_editor', 'id' => $item->ID), $gmProcessor->url) . '" data-target="#gmeditModal" class="gmedit-modal" id="gmimageedit' . $item->ID . '" title="' . __('Edit Image', 'grand-media') . '">' . $edit_image_icon . '</a>';
        } else{
            $edit_image = "<span class='action-inactive'>$edit_image_icon</span>";
        }

        $show_icon = '<span class="glyphicon glyphicon-fullscreen"></span>';
        $show      = '<a href="' . $gmCore->gm_get_media_image($item, 'web') . '" data-target="#previewModal" data-width="' . $item->msize['width'] . '" data-height="' . $item->msize['height'] . '" class="preview-modal" title="' . esc_attr(__('Show', 'grand-media') . ' ' . $item->title) . '">' . $show_icon . '</a>';

    } elseif(in_array($item->ext, array('mp3', 'ogg', 'wav', 'ogg', 'mp4', 'mpeg', 'webm'))){
        $show_icon = '<span class="glyphicon glyphicon-play"></span>';
        $show      = '<a href="' . $item->url . '" data-target="#previewModal" data-width="' . $item->msize['width'] . '" data-height="' . $item->msize['height'] . '" class="preview-modal" title="' . esc_attr(__('Play', 'grand-media') . ' ' . $item->title) . '">' . $show_icon . '</a>';
    } else{
        $show_icon = '<span class="glyphicon glyphicon-cloud-download"></span>';
        $show      = '<a href="' . $item->url . '" title="' . __('Download', 'grand-media') . '" download="' . $item->gmuid . '">' . $show_icon . '</a>';
    }

    $duplicate_icon = '<span class="glyphicon glyphicon-duplicate"></span>';
    $duplicate      = '<a href="' . wp_nonce_url(gm_get_admin_url(array('do_gmedia' => 'duplicate', 'ids' => $item->ID)), 'gmedia_action', '_wpnonce_action') . '" title="' . __('Duplicate', 'grand-media') . '">' . $duplicate_icon . '</a>';

    $actions = compact('share', 'edit_data', 'edit_image', 'show', 'info', 'duplicate', 'delete', 'db_delete');

    return apply_filters('gmedia_item_actions', $actions);
}


function gmedia_filter_message(){
    global $gmProcessor;
    do_action('before_gmedia_filter_message');
    if(!empty($gmProcessor->filters)){
        echo '<div class="custom-message alert alert-info">';
        foreach($gmProcessor->filters as $key => $value){
            echo '<div class="custom-message-row">';
            echo '<strong><a href="#libModal" data-modal="' . $key . '" data-action="gmedia_get_modal" class="gmedia-modal">' . $value['title'] . '</a>: </strong>';
            echo implode(', ', $value['filter']);
            echo '</div>';
        }
        echo '</div>';
    }
}

function gmedia_alert_message(){
    global $gmProcessor;
    do_action('before_gmedia_alert_message');
    if(($gmProcessor->mode == 'edit') && gm_user_can('show_others_media') && !gm_user_can('edit_others_media')){
        ?>
        <div class="alert alert-warning alert-dismissible" role="alert" style="margin-bottom:0">
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span><span class="sr-only"><?php _e('Close', 'grand-media'); ?></span></button>
            <strong><?php _e('Info:', 'grand-media'); ?></strong> <?php _e('You are not allowed to edit others media', 'grand-media'); ?>
        </div>
        <?php
    }
}

/**
 * @param $item
 *
 * @return string
 */
function gmedia_waveform_player($item){
    global $gmDB;
    $peaks = $gmDB->get_metadata('gmedia', $item->ID, '_peaks', true);
    if($peaks){
        if('[]' === $peaks){
            $gmDB->delete_metadata('gmedia', $item->ID, '_peaks');
            $peaks = '';
        } else{
            $peaks = json_decode($peaks);
            while(900 < count($peaks)){
                $peaks = array_map('reset', array_chunk($peaks, 2));
            }
            $peaks = json_encode($peaks);
        }
    } else{
        $peaks = '';
    }
    $content = '
                <div class="gm-waveform-player" data-id="' . $item->ID . '" data-file="' . $item->url . '" data-peaks="' . $peaks . '">
                    <div id="ws' . $item->ID . '"></div>' . ($peaks? '' : ('<button type="button" class="btn btn-sm btn-info gm-waveform">' . __('Create & Save WaveForm', 'grand-media') . '</button>')) . '<button type="button" class="btn btn-sm btn-info gm-play" style="display:none;">' . __('Play', 'grand-media') . '</button>
                    <button type="button" class="btn btn-sm btn-info gm-pause" style="display:none;">' . __('Pause', 'grand-media') . '</button>
                    <span style="float:none;" class="spinner"></span>
                </div>';

    return $content;
}

