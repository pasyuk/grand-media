<?php

function gmedia_item_thumbnail($item) {
    global $gmCore;

    echo '<img class="gmedia-thumb" src="' . $gmCore->gm_get_media_image($item, 'thumb') . '" alt=""/>';

    if(isset($item->meta['_cover'][0]) && !empty($item->meta['_cover'][0])) {
        echo '<img class="gmedia-typethumb" src="' . $gmCore->gm_get_media_image($item, 'thumb', false) . '" alt=""/>';
    }
}

function gmedia_item_actions($item) {
    global $gmCore;

    $actions = array();
    if(((int)$item->author == get_current_user_id()) || gm_user_can('edit_others_media')) {
        if(!empty($item->post_id)){
            $cloud_link = get_permalink($item->post_id);
        } else {
            $cloud_link = $gmCore->gmcloudlink($item->ID, 'single');
        }
        $actions['share']     = '<a target="_blank" data-target="#shareModal" data-share="' . $item->ID . '" class="share-modal" title="' . __('GmediaCloud Page', 'grand-media') . '" href="' . $cloud_link . '">' . __('Share', 'grand-media') . '</a>';
        if(gm_user_can('edit_media')) {
            $actions['edit_data'] = '<a href="' . admin_url("admin.php?page=GrandMedia&edit_mode=1&gmedia__in={$item->ID}") . '">' . __('Edit Data', 'grand-media') . '</a>';
        }
    }
    if('image' == $item->type) {
        if((gm_user_can('edit_media') && ((int)$item->author == get_current_user_id())) || gm_user_can('edit_others_media')) {
            $actions['edit_image'] = '<a href="' . admin_url("admin.php?page=GrandMedia&gmediablank=image_editor&id={$item->ID}") . '" data-target="#gmeditModal" class="gmedit-modal">' . __('Edit Image', 'grand-media') . '</a>';
        }
        $actions['show'] = '<a href="' . $gmCore->gm_get_media_image($item, 'web') . '" data-target="#previewModal" data-width="' . $item->msize['width'] . '" data-height="' . $item->msize['height'] . '" class="preview-modal" title="' . esc_attr($item->title) . '">' . __('Show', 'grand-media') . '</a>';

    } elseif(in_array($item->ext, array('mp4', 'mp3', 'mpeg', 'webm', 'ogg', 'wave', 'wav'))) {
        $actions['show'] = '<a href="' . $item->url . '" data-target="#previewModal" data-width="' . $item->msize['width'] . '" data-height="' . $item->msize['height'] . '" class="preview-modal" title="' . esc_attr($item->title) . '">' . __('Play', 'grand-media') . '</a>';
    }
    $metainfo = $gmCore->metadata_text($item->ID);
    if($metainfo) {
        $actions['info'] = '<a href="#metaInfo" data-target="#previewModal" data-metainfo="' . $item->ID . '" class="preview-modal" title="' . __('Exif Info', 'grand-media') . '">' . __('Exif Info', 'grand-media') . '</a>';
        $actions['info'] .= '<div class="metainfo hidden" id="metainfo_' . $item->ID . '">' . nl2br($metainfo) . '</div>';
    }
    if((gm_user_can('delete_media') && ((int)$item->author == get_current_user_id())) || gm_user_can('delete_others_media')) {
        $actions['delete'] = '<a class="text-danger" href="' . wp_nonce_url(gm_get_admin_url(array('delete' => $item->ID)), 'gmedia_delete') . '" data-confirm="' . sprintf(__("You are about to permanently delete %s file.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"), $item->gmuid) . '">' . __('Delete', 'grand-media') . '</a>';
        if($gmCore->_get('showmore')) {
            $actions['db_delete'] = '<a class="text-danger" href="' . wp_nonce_url(gm_get_admin_url(array('delete' => $item->ID, 'save_original_file' => 1)), 'gmedia_delete') . '" data-confirm="' . sprintf(__("You are about to delete record from DB for %s file.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"), $item->gmuid) . '">' . __('Delete DB record (leave file on the server)', 'grand-media') . '</a>';
        }
    }

    return apply_filters('gmedia_item_actions', $actions);
}


function gmedia_item_more_data(&$item) {
    global $gmDB, $gmCore, $gmGallery;

    $meta     = $gmDB->get_metadata('gmedia', $item->ID);
    $metadata = isset($meta['_metadata'][0])? $meta['_metadata'][0] : array();

    $item->meta = $meta;

    $type       = explode('/', $item->mime_type);
    $item->type = $type[0];
    $item->ext  = pathinfo($item->gmuid, PATHINFO_EXTENSION);

    $item->url  = $gmCore->upload['url'] . '/' . $gmGallery->options['folder'][$type[0]] . '/' . $item->gmuid;
    $item->path = $gmCore->upload['path'] . '/' . $gmGallery->options['folder'][$type[0]] . '/' . $item->gmuid;

    if(function_exists('exif_imagetype')) {
        $item->editor = (('image' == $type[0]) && in_array(exif_imagetype($item->path), array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))? true : false;
    } else {
        $item->editor = (('image' == $type[0]) && in_array($type[1], array('jpeg', 'png', 'gif')))? true : false;
    }
    $item->gps = '';
    if($item->editor) {
        $item->url_original  = $gmCore->upload['url'] . '/' . $gmGallery->options['folder']['image_original'] . '/' . $item->gmuid;
        $item->url_thumb     = $gmCore->upload['url'] . '/' . $gmGallery->options['folder']['image_thumb'] . '/' . $item->gmuid;
        $item->path_original = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_original'] . '/' . $item->gmuid;
        $item->path_thumb    = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_thumb'] . '/' . $item->gmuid;
        if(!empty($metadata['image_meta']['GPS'])) {
            $item->gps = implode(', ', $metadata['image_meta']['GPS']);
        }
    }
    if(!empty($meta['_gps'][0])) {
        $item->gps = implode(', ', $meta['_gps'][0]);
    }

    $item->msize['width']  = isset($metadata['web']['width'])? $metadata['web']['width'] : (isset($metadata['width'])? $metadata['width'] : '640');
    $item->msize['height'] = isset($metadata['web']['height'])? $metadata['web']['height'] : (isset($metadata['height'])? $metadata['height'] : '200');

    $item->thumb_ratio = 1;
    if(isset($metadata['thumb']['width']) && isset($metadata['thumb']['height'])) {
        $item->thumb_ratio = $metadata['thumb']['width'] / $metadata['thumb']['height'];
    }

    $item->tags     = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_tag');
    $item->album    = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_album');
    $item->categories = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_category');

    $item = apply_filters('gmedia_item_more_data', $item);
}

function gmedia_filter_message() {
    global $gmProcessor;
    if(!empty($gmProcessor->filters)) {
        echo '<div class="custom-message alert alert-info">';
        foreach($gmProcessor->filters as $key => $value) {
            echo '<div class="custom-message-row">';
            echo '<strong><a href="#libModal" data-modal="' . $key . '" data-action="gmedia_get_modal" class="gmedia-modal">' . $value['title'] . '</a>: </strong>';
            echo implode(', ', $value['filter']);
            echo '</div>';
        }
        echo '</div>';
    }
}

function gmedia_alert_message() {
    global $gmProcessor;
    if($gmProcessor->edit_mode && gm_user_can('show_others_media') && !gm_user_can('edit_others_media')) {
        ?>
        <div class="alert alert-warning alert-dismissible" role="alert" style="margin-bottom:0">
            <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">&times;</span><span class="sr-only"><?php _e('Close', 'grand-media'); ?></span></button>
            <strong><?php _e('Info:', 'grand-media'); ?></strong> <?php _e('You are not allowed to edit others media', 'grand-media'); ?>
        </div>
        <?php
    }
}

