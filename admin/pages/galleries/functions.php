<?php

function gmedia_gallery_actions( $item ) {
	global $gmCore, $gmProcessor;

	$actions = array();

	$filter_href       = $gmCore->get_admin_url( array( 'page' => 'GrandMedia', 'gallery' => $item->term_id ), array(), true );
	$filter_class      = 'gm_filter_in_lib';
	$actions['filter'] = '<a title="' . esc_attr__( 'Filter in Gmedia Library', 'grand-media' ) . '" href="' . esc_url( $filter_href ) . '" class="' . esc_attr( $filter_class ) . '"><i class="fa-solid fa-filter"></i></a>';

	$share_icon = '<i class="fa-solid fa-share-from-square"></i>';
	if ( 'draft' !== $item->status ) {
		$actions['share'] = '<a target="_blank" data-bs-toggle="modal" data-bs-target="#shareModal" data-share="' . esc_attr( $item->term_id ) . '" class="share-modal" title="' . esc_attr__( 'Share', 'grand-media' ) . '" data-gmediacloud="' . esc_url( $item->cloud_link ) . '" href="' . esc_url( $item->post_link ) . '">' . $share_icon . '</a>';
	} else {
		$actions['share'] = '<span class="action-inactive">' . $share_icon . '</span>';
	}

	$edit_icon = '<i class="fa-solid fa-pen-to-square"></i>';
	if ( $item->allow_edit ) {
		$actions['edit'] = '<a title="' . esc_attr__( 'Edit', 'grand-media' ) . '" href="' . esc_url( add_query_arg( array( 'edit_term' => $item->term_id ), $gmProcessor->url ) ) . '">' . $edit_icon . '</a>';
	} else {
		$actions['edit'] = '<span class="action-inactive">' . $edit_icon . '</span>';
	}

	$trash_icon = '<i class="fa-solid fa-trash-can"></i>';
	if ( $item->allow_delete ) {
		$actions['delete'] = '<a class="trash-icon link-danger" title="' . esc_attr__( 'Delete', 'grand-media' ) . '" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'do_gmedia_terms' => 'delete', 'ids' => $item->term_id ), $gmProcessor->url ), 'gmedia_delete', '_wpnonce_delete' ) ) . '" data-confirm="' . esc_attr__( "You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ) . '">' . $trash_icon . '</a>';
	} else {
		$actions['delete'] = '<span class="action-inactive">' . $trash_icon . '</span>';
	}

	return apply_filters( 'gmedia_gallery_actions', $actions );
}
