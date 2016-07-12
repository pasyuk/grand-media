<?php
/**
 * Modules functions
 */

function gmedia_module_action_buttons( $module ) {
	global $gmCore, $gmProcessor;

	$buttons = array();
	if ( 'remote' == $module['place'] ) {
		$buttons['install'] = '<a class="btn btn-primary ' . ( gm_user_can( 'module_manage' ) ? 'module_install' : 'disabled' ) . '" data-module="' . $module['name'] . '" data-loading-text="' . __( 'Loading...', 'grand-media' ) . '" href="' . esc_url( $module['download'] ) . '">' . __( 'Install Module', 'grand-media' ) . '</a>';
	} else {
		$buttons['create'] = '<a class="btn btn-success" href="' . $gmCore->get_admin_url( array( 'page' => 'GrandMedia_Galleries', 'gallery_module' => $module['module_name'] ), array(), true ) . '">' . __( 'Create Gallery', 'grand-media' ) . '</a>';
	}
	if ( ! empty( $module['demo'] ) && $module['demo'] != '#' ) {
		$buttons['demo'] = '<a class="btn btn-default" target="_blank" href="' . $module['demo'] . '">' . __( 'View Demo', 'grand-media' ) . '</a>';
	}
	if ( ! empty( $module['update'] ) && 'remote' != $module['place'] ) {
		$buttons['update'] = '<a class="btn btn-warning module_install" data-module="' . $module['module_name'] . '" data-loading-text="' . __( 'Loading...', 'grand-media' ) . '" href="' . esc_url( $module['download'] ) . '">' . __( 'Update Module', 'grand-media' ) . " (v{$module['update']})</a>";
	}
	if ( ( 'upload' == $module['place'] ) && gm_user_can( 'module_manage' ) ) {
		$buttons['delete'] = '<a class="btn btn-danger" href="' . wp_nonce_url( $gmCore->get_admin_url( array( 'delete_module' => $module['module_name'] ), array(), $gmProcessor->url ), 'gmedia_module_delete' ) . '">' . __( 'Delete Module', 'grand-media' ) . '</a>';
	}
	if ( ! empty( $module['download'] ) ) {
		$buttons['download'] = '<a class="btn btn-link" href="' . $module['download'] . '" download="true">' . __( 'Download module ZIP', 'grand-media' ) . '</a>';
	}

	return $buttons;
}

function gmedia_module_preset_more_data( &$item ) {
	global $gmCore, $user_ID;


	if ( ! $item || is_wp_error( $item ) ) {
		$item = new stdClass();
		gmedia_module_preset_more_data( $item );

		return;
	}

	if ( empty( $item->term_id ) ) {
		$item->term_id     = 0;
		$item->name        = '';
		$item->taxonomy    = 'gmedia_module';
		$item->description = '';
		$item->global      = $user_ID;
		$item->status      = $gmCore->_get( 'preset_module', 'phantom' );
	} else {
		if ( ($preset_module = $gmCore->_get( 'preset_module' )) && $item->status != $preset_module ) {
			$item = new stdClass();
			gmedia_module_preset_more_data( $item );

			return;
		}

		$item->name = trim( str_replace( '[' . $item->status . ']', '', $item->name ) );
	}

	$item->module = $gmCore->get_module_path( $item->status );

	$module_info = array( 'type' => '&#8212;' );
	if ( file_exists( $item->module['path'] . '/index.php' ) ) {
		include( $item->module['path'] . '/index.php' );

		$item->module['info'] = $module_info;
	} else {
		$item->module['broken'] = true;
	}

	$getModulePreset          = $gmCore->getModulePreset( $item->status );
	$item->module['name']     = $getModulePreset['module'];
	$item->module['settings'] = maybe_unserialize($item->description);

	$item = apply_filters( 'gmedia_module_preset_more_data', $item );
}
