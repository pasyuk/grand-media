<?php

function gmedia_gallery_actions($item) {
    global $gmCore, $gmProcessor;

    $actions = array();

    $filter_href       = $gmCore->get_admin_url(array('page' => 'GrandMedia', "gallery" => $item->term_id), array(), true);
    $filter_class      = 'gm_filter_in_lib';
    $actions['filter'] = '<a title="' . __('Filter in Gmedia Library', 'grand-media') . '" href="' . $filter_href . '" class="' . $filter_class . '"><span class="glyphicon glyphicon-filter"></span></a>';

    $share_icon = '<span class="glyphicon glyphicon-share"></span>';
    if('draft' !== $item->status) {
        $actions['share'] = '<a target="_blank" data-target="#shareModal" data-share="' . $item->term_id . '" class="share-modal" title="' . __('Share', 'grand-media') . '" data-gmediacloud="' . $item->cloud_link . '" href="' . $item->post_link . '">' . $share_icon . '</a>';
    } else {
        $actions['share'] = "<span class='action-inactive'>$share_icon</span>";
    }

    $edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
    if($item->allow_edit) {
        $actions['edit'] = '<a title="' . __('Edit', 'grand-media') . '" href="' . add_query_arg(array("edit_term" => $item->term_id), $gmProcessor->url) . '">' . $edit_icon . '</a>';
    } else {
        $actions['edit'] = "<span class='action-inactive'>$edit_icon</span>";
    }

    $trash_icon = '<span class="glyphicon glyphicon-trash"></span>';
    if($item->allow_delete) {
        $actions['delete'] = '<a class="trash-icon" title="' . __('Delete', 'grand-media') . '" href="' . wp_nonce_url(add_query_arg(array('do_gmedia_terms' => 'delete', 'ids' => $item->term_id), $gmProcessor->url), 'gmedia_delete', '_wpnonce_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . $trash_icon . '</a>';
    } else {
        $actions['delete'] = "<span class='action-inactive'>$trash_icon</span>";
    }

    return apply_filters('gmedia_gallery_actions', $actions);
}
