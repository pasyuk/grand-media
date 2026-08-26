<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$module_info = array(
	'base'         => 'phantom',
	'name'         => 'phantom',
	'title'        => 'Phantom',
	'version'      => '3.35',
	'author'       => 'CodEasily.com',
	'description'  => __( 'This module will help you to easily add a grid gallery to your WordPress website or blog. The gallery is completely customizable, resizable and is compatible with all browsers and devices (iPhone, iPad and Android smartphones).

	Responsive | Social Sharing integrated | Views/Likes Counters Support | Comments Support | Customize each gallery individually | Customizable lightbox | Deeplinking support | Change thumbnail size, border, spacing, transparency, background, controls ...', 'grand-media' ),
	'type'         => 'gallery',
	'branch'       => '1',
	'status'       => 'free',
	'price'        => '0',
	'demo'         => 'http://codeasily.com/portfolio/gmedia-gallery-modules/phantom/',
	'download'     => 'http://codeasily.com/download/phantom-module-zip/',
	'dependencies' => '',
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
