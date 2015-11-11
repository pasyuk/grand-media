<?php
// define plugin dir
define( 'GMEDIA_FOLDER', plugin_basename( dirname( __FILE__ ) ) );
define( 'GMEDIA_UPLOAD_FOLDER', 'grand-media' );
define( 'GMEDIA_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'GMEDIA_GALLERY_EMPTY', __('No Supported Files in Gallery', 'grand-media') );

/**
 * @return array Gmedia Capabilities
 */
function gmedia_plugin_capabilities(){
	return array(
		'gmedia_library'
		,	'gmedia_show_others_media'
		,	'gmedia_edit_media'
		,	'gmedia_edit_others_media'
		,	'gmedia_delete_media'
		,	'gmedia_delete_others_media'
		,'gmedia_upload'
		,	'gmedia_import'
		,'gmedia_terms'
		,	'gmedia_album_manage'
		,	'gmedia_filter_manage'
		,	'gmedia_tag_manage'
		,	'gmedia_terms_delete'
		,'gmedia_gallery_manage'
		,'gmedia_module_manage'
		,'gmedia_settings'
	);
}
