<?php
/**
 * Gmedia Constants
 */

if ( ! defined( 'GMEDIA_UPLOAD_FOLDER' ) ) {
	define( 'GMEDIA_UPLOAD_FOLDER', 'grand-media' );
}

define( 'GMEDIA_FOLDER', plugin_basename( dirname( __FILE__ ) ) );
define( 'GMEDIA_ABSPATH', plugin_dir_path( __FILE__ ) );

define( 'GMEDIA_GALLERY_EMPTY', __( 'No Supported Files in Gallery', 'grand-media' ) );
