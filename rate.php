<?php
//ini_set( 'display_errors', 0 );
//ini_set( 'error_reporting', 0 );
define('WP_USE_THEMES', false);
@require_once (dirname(__FILE__).'/config.php');

if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request');
	die();
}

$ref = $_SERVER['HTTP_REFERER'];
if ( (false === strpos( $ref, get_home_url() )) && (false === strpos( $ref, get_site_url()) )) {
	header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request');
	die();
}
if(('POST' !== $_SERVER['REQUEST_METHOD']) || !isset($_SERVER['HTTP_HOST']) || !(strpos( get_home_url(), $_SERVER['HTTP_HOST'] )) || !empty($_SERVER['QUERY_STRING'])){
	header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request');
	die();
}


if ( isset($_POST['hit']) && ($gmID = intval($_POST['hit'])) ) {
	/** @var $wpdb wpdb */
	global $wpdb, $gmDB;
	if(null === $gmDB->get_gmedia($gmID)){
		die('0');
	}
	$meta['views'] = $gmDB->get_metadata('gmedia', $gmID, 'views', true);
	$meta['likes'] = $gmDB->get_metadata('gmedia', $gmID, 'likes', true);

	$meta = array_map('intval', $meta);
	$meta = gm_hitcounter($gmID, $meta);

	header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
	echo json_encode($meta);
	die();
}

/**
 * Update media meta in the database
 */
function gm_hitcounter($gmID, $meta) {
	/** @var wpdb $wpdb */
	global $gmDB;
	if( isset($_POST['vote']) ) {
		$meta['likes'] +=1;
		$gmDB->update_metadata('gmedia', $gmID, 'likes', $meta['likes']);
	}
	else {
		$meta['views'] +=1;
		$gmDB->update_metadata('gmedia', $gmID, 'views', $meta['views']);
	}
	return $meta;
}