<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var $wp
 * @var $endpoint
 */

global $gmedia, $gmedia_id, $gmedia_type, $gmedia_module, $gmedia_shortcode_content, $gmedia_share_img;

$gmedia_hashid = urldecode( $wp->query_vars[ $endpoint ] );
$gm_type       = isset( $wp->query_vars['t'] ) ? $wp->query_vars['t'] : 'g';

$gm_template = array(
	'g' => 'gallery',
	'a' => 'album',
	't' => 'tag',
	's' => 'single',
	'k' => 'category',
	'u' => 'author',
);
if ( ! isset( $gm_template[ $gm_type ] ) ) {
	locate_template( '404', true );
	exit();
}

$gmedia_type = $gm_template[ $gm_type ];
$gmedia_id   = gmedia_hash_id_decode( $gmedia_hashid, $gmedia_type );
if ( empty( $gmedia_id ) ) {
	locate_template( '404', true );
	exit();
}

global $user_ID, $gmCore, $gmDB, $gmGallery;

switch ( $gmedia_type ) {
	case 'gallery':
		$gmedia        = $gmDB->get_term( $gmedia_id );
		$gmedia_module = $gmDB->get_metadata( 'gmedia_term', $gmedia_id, '_module', true );
		break;
	case 'album':
	case 'tag':
	case 'category':
		$gmedia        = $gmDB->get_term( $gmedia_id );
		$gmedia_module = $gmDB->get_metadata( 'gmedia_term', $gmedia_id, '_module_preset', true );
		if ( ! $gmedia_module ) {
			$gmedia_module = $gmGallery->options['default_gmedia_module'];
		}
		if ( $gmCore->is_digit( $gmedia_module ) ) {
			$preset        = $gmDB->get_term( $gmedia_module );
			$gmedia_module = '';
			if ( $preset && ! is_wp_error( $preset ) ) {
				$gmedia_module = $preset->status;
			}
		}
		break;
	case 'single':
		$gmedia = $gmDB->get_gmedia( $gmedia_id );
		break;
}

$set_module = $gmCore->_get( 'gmedia_module' );
if ( $set_module && $user_ID && current_user_can( 'gmedia_gallery_manage' ) ) {
	$gmedia_module = $set_module;
}

if ( ! $gmedia_module ) {
	$gmedia_module = 'amron';
}

$module = $gmCore->get_module_path( $gmedia_module );
/* @noinspection PhpIncludeInspection */
require_once GMEDIA_ABSPATH . 'template/functions.php';

if ( is_file( $module['path'] . '/template/functions.php' ) ) {
	/* @noinspection PhpIncludeInspection */
	include_once $module['path'] . '/template/functions.php';
}

global $posts;
$posts = array();

if ( is_file( $module['path'] . "/template/{$gmedia_type}.php" ) ) {
	/* @noinspection PhpIncludeInspection */
	require_once $module['path'] . "/template/{$gmedia_type}.php";
} elseif ( in_array( $gmedia_type, array( 'album', 'tag', 'category' ), true ) && is_file( $module['path'] . '/template/gallery.php' ) ) {
	/* @noinspection PhpIncludeInspection */
	require_once $module['path'] . '/template/gallery.php';
} else {
	/* only for default template */
	add_action( 'gmedia_head', 'gmedia_default_template_styles' );
	if ( is_file( GMEDIA_ABSPATH . "template/{$gmedia_type}.php" ) ) {
		/* @noinspection PhpIncludeInspection */
		require_once GMEDIA_ABSPATH . "template/{$gmedia_type}.php";
	} else {
		/* @noinspection PhpIncludeInspection */
		require_once GMEDIA_ABSPATH . 'template/gallery.php';
	}
}
