<?php

function gmedia_gallery_actions($item) {
    global $gmCore, $gmProcessor;

    $taxterm = str_replace('gmedia_', '', $item->taxonomy);
    $actions = array();

    $filter_href       = $gmCore->get_admin_url(array('page' => 'GrandMedia', "gallery" => $item->term_id), array(), true);
    $filter_class      = 'gm_filter_in_lib';
    $actions['filter'] = '<a title="' . __('Filter in Gmedia Library', 'grand-media') . '" href="' . $filter_href . '" class="' . $filter_class . '"><span class="glyphicon glyphicon-filter"></span></a>';

    $cloud_link = $gmCore->gmcloudlink($item->term_id, $taxterm);
    if(!empty($item->meta['_post_ID'])) {
        $post_link = get_permalink($item->meta['_post_ID']);
    } else {
        $post_link = '';
    }
    $share_icon = '<span class="glyphicon glyphicon-share"></span>';
    if('draft' !== $item->status) {
        $actions['share'] = '<a target="_blank" data-target="#shareModal" data-share="' . $item->term_id . '" class="share-modal" title="' . __('Share', 'grand-media') . '" data-gmediacloud="' . $cloud_link . '" href="' . $post_link . '">' . $share_icon . '</a>';
    } else {
        $actions['share'] = "<span class='action-inactive'>$share_icon</span>";
    }

    $edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
    if($item->allow_edit) {
        $actions['edit'] = '<a title="' . __('Edit', 'grand-media') . '" href="' . add_query_arg(array("edit_item" => $item->term_id), $gmProcessor->url) . '">' . $edit_icon . '</a>';
    } else {
        $actions['edit'] = "<span class='action-inactive'>$edit_icon</span>";
    }

    $trash_icon = '<span class="glyphicon glyphicon-trash"></span>';
    if($item->allow_delete) {
        $actions['delete'] = '<a class="trash-icon" title="' . __('Delete', 'grand-media') . '" href="' . wp_nonce_url(add_query_arg(array('delete' => $item->term_id), $gmProcessor->url), 'gmedia_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . $trash_icon . '</a>';
    } else {
        $actions['delete'] = "<span class='action-inactive'>$trash_icon</span>";
    }

    return apply_filters('gmedia_gallery_actions', $actions);
}


function gmedia_gallery_more_data(&$item) {
    global $gmDB, $gmCore, $user_ID;

    $item->custom            = array();
    $item->meta              = array(
        '_edited' => '&#8212;',
        '_query'  => array(),
        '_module' => $gmCore->_get('gallery_module', 'phantom')
    );
    $item->meta['_settings'] = array($item->meta['_module'] => array());

    if(empty($item->term_id)) {
        $item->term_id     = 0;
        $item->name        = '';
        $item->taxonomy    = 'gmedia_gallery';
        $item->description = '';
        $item->global      = $user_ID;
        $item->count       = 0;
        $item->status      = 'publish';
        $item->post_id     = 0;
        $item->slug        = '';

    } else {
        $meta = $gmDB->get_metadata('gmedia_term', $item->term_id);
        foreach($meta as $key => $value) {
            if(is_protected_meta($key, 'gmedia')) {
                $item->meta[$key] = $value[0];
            } else {
                $item->custom[$key] = $value;
            }
        }

        $post_id       = isset($meta['_post_ID'][0])? (int)$meta['_post_ID'][0] : 0;
        $item->post_id = $post_id;
        if($post_id) {
            $post_item = get_post($post_id);
            if($post_item) {
                $item->slug           = $post_item->post_name;
                $item->post_password  = $post_item->post_password;
                $item->comment_count  = $post_item->comment_count;
                $item->comment_status = $post_item->comment_status;
            }
        }
    }

    $_module_name = $gmCore->_get('gallery_module', $item->meta['_module']);

    $item->module         = $gmCore->get_module_path($_module_name);
    $item->module['name'] = $_module_name;
    $module_info          = array('type' => '&#8212;');
    if(file_exists($item->module['path'] . '/index.php')) {
        include($item->module['path'] . '/index.php');

        $item->module['info'] = $module_info;
    } else {
        $item->module['broken'] = true;
    }

    if($item->global) {
        $item->author_name = get_the_author_meta('display_name', $item->global);
    } else {
        $item->author_name = false;
    }

    $item = apply_filters('gmedia_gallery_more_data', $item);
}
