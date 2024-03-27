<?php

function gm_get_admin_url( $add_args = array(), $remove_args = array(), $uri = false, $preserve_args = array() ) {
	global $gmCore;

	return $gmCore->get_admin_url( $add_args, $remove_args, $uri, $preserve_args );
}

function gm_panel_classes( $classes ) {
	echo esc_attr( implode( ' ', (array) $classes ) );
}

function gmedia_term_choose_author_field( $selected = false, $_args = array() ) {
	global $gmCore;

	$user_ID = get_current_user_id();
	if ( false === $selected ) {
		$selected = $user_ID;
	}

	$user_ids = gm_user_can( 'delete_others_media' ) ? $gmCore->get_editable_user_ids() : array( $user_ID );
	if ( $user_ids && gm_user_can( 'edit_others_media' ) ) {
		if ( ! in_array( $user_ID, $user_ids, true ) ) {
			$user_ids[] = $user_ID;
		}
		$args = array(
			'include'          => $user_ids,
			'include_selected' => true,
			'name'             => 'term[global]',
			'selected'         => $selected,
			'class'            => 'form-control input-sm',
			'multi'            => true,
			'show_option_all'  => __( 'Shared', 'grand-media' ),
		);
		$args = array_merge( $args, $_args );
		wp_dropdown_users( $args );
	} else {
		echo '<input type="hidden" name="term[global]" value="' . intval( $user_ID ) . '"/>';
		echo '<div>' . esc_html( get_the_author_meta( 'display_name', $user_ID ) ) . '</div>';
	}
}
