<?php
//ini_set('display_errors', '1');
//ini_set('error_reporting', E_ALL);
if(!defined('ABSPATH')){
	exit;
}
/**
 * @var $wp
 * @var $endpoint
 */

global $gmedia, $gmedia_id, $gmedia_type, $gmedia_module, $gmedia_module_preset, $gmedia_shortcode_content, $gmedia_share_img;

$gmedia_hashid = urldecode($wp->query_vars[$endpoint]);
$type = isset($wp->query_vars['t'])? $wp->query_vars['t'] : 'g';

$template = array(
	'g' => 'gallery',
	'a' => 'album',
	't' => 'tag',
	's' => 'single',
	'k' => 'category',
	'f' => 'filter',
	'u' => 'author'
);
if(!isset($template[$type])){
	locate_template(array('404'), true);
	exit();
}

$gmedia_type = $template[$type];
$gmedia_id = gmedia_hash_id_decode($gmedia_hashid, $gmedia_type);
if(empty($gmedia_id)){
	exit();
}

global $user_ID, $gmCore, $gmDB, $gmGallery;

switch($gmedia_type){
	case 'gallery':
		$gmedia = $gmDB->get_term($gmedia_id, 'gmedia_gallery');
		if($gmCore->_get('set_module') && $user_ID){
			$gmedia_module = $_GET['set_module'];
		} else{
			$gmedia_module = $gmDB->get_metadata( 'gmedia_term', $gmedia_id, '_module', true);
		}
		break;
	case 'album':
	case 'tag':
	case 'category':
	case 'filter':
		$gmedia = $gmDB->get_term($gmedia_id, "gmedia_{$gmedia_type}");

		if(empty($gmGallery->options['gmediacloud_module'])){
			$gmedia_module = 'phantom';
		} else{
			if($gmCore->is_digit($gmGallery->options['gmediacloud_module'])){
				$get_preset = $gmDB->get_term( (int) $gmGallery->options['gmediacloud_module'], 'gmedia_module');
				if(!empty($get_preset) && !is_wp_error($get_preset)){
					$gmedia_module = $get_preset->status;
					$gmedia_module_preset = $get_preset->term_id;
				}
			} else {
				$gmedia_module = $gmGallery->options['gmediacloud_module'];
			}
		}
		break;
	case 'single':
		$gmedia = $gmDB->get_gmedia($gmedia_id);
		break;
}
if(!$gmedia_module){
	$gmedia_module = 'phantom';
}

$module = $gmCore->get_module_path($gmedia_module);
require_once(GMEDIA_ABSPATH . 'template/functions.php');

if(file_exists($module['path'] . "/template/functions.php")){
	include_once($module['path'] . "/template/functions.php");
}

if(file_exists($module['path'] . "/template/{$gmedia_type}.php")){
	require_once($module['path'] . "/template/{$gmedia_type}.php");
} elseif(in_array($gmedia_type, array('album', 'tag', 'category', 'filter')) && file_exists($module['path'] . "/template/gallery.php")){
	require_once($module['path'] . "/template/gallery.php");
} else{
	/* only for default template */
	add_action('gmedia_head', 'gmedia_default_template_styles');
	if(file_exists( GMEDIA_ABSPATH . "template/{$gmedia_type}.php" )) {
		require_once( GMEDIA_ABSPATH . "template/{$gmedia_type}.php" );
	} else {
		require_once( GMEDIA_ABSPATH . "template/gallery.php" );
	}
}
