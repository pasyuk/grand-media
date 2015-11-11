<?php
$module_info = array(
	'base' => 'photomania',
	'name' => 'photomania',
	'title' => 'PhotoMania',
	'version' => '1.1',
	'author' => 'Rattus',
	'description' => 'Responsive Gallery based on jQuery with keyboard control, displaying thumbs, author, title and optional description, download, link button, like button, full window and full screen mode',
	'type' => 'gallery',
	'status' => 'free',
	'price' => '0',
	'demo' => 'http://codeasily.com/portfolio-item/gmedia-photomania/',
	'download' => '',
	'dependencies' => 'mousetrap'
);
if(isset($_GET['info'])){
	echo '<pre>' . print_r($module_info, true) . '</pre>';
} elseif(preg_match('#' . basename(dirname(__FILE__)).'/'.basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
	header("Location: {$module_info['demo']}");
	die();
}