<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$module_info = array(
	'base'         => 'photomania',
	'name'         => 'photomania',
	'title'        => 'PhotoMania',
	'version'      => '1.9',
	'author'       => 'CodEasily.com',
	'description'  => __( 'Responsive Gallery based on jQuery with keyboard control, displaying thumbs, author, title and optional description, download, link button, like button, full window and full screen mode', 'grand-media' ),
	'type'         => 'gallery',
	'branch'       => '1',
	'status'       => 'free',
	'price'        => '0',
	'demo'         => 'http://codeasily.com/portfolio/gmedia-gallery-modules/photomania/',
	'download'     => 'http://codeasily.com/download/photomania-module-zip/',
	'dependencies' => 'swiper,mousetrap',
);
$gmedia_php_self = isset( $_SERVER['PHP_SELF'] ) ? wp_unslash( $_SERVER['PHP_SELF'] ) : '';
if ( preg_match( '#' . basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) . '#', $gmedia_php_self ) ) {
	if ( isset( $_GET['info'] ) ) {
		echo '<pre>' . esc_html( print_r( $module_info, true ) ) . '</pre>';
	} else {
		header( "Location: {$module_info['demo']}" );
		die();
	}
}
