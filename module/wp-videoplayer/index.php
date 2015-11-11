<?php
$module_info = array(
	'base' => 'wp-videoplayer',
	'name' => 'wp-videoplayer',
	'title' => 'Video Player',
	'version' => '1.4',
	'author' => 'CodEasily.com',
	'description' => 'Video player with playlist based on built in Wordpress Video Player',
	'type' => 'video',
	'status' => 'free',
	'price' => '0',
	'demo' => '',
	'download' => '',
	'dependencies' => 'wp-util,backbone,mediaelement'
);
if(isset($_GET['info'])){
	echo '<pre>' . print_r($module_info, true) . '</pre>';
} elseif(preg_match('#' . basename(dirname(__FILE__)).'/'.basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
	header("Location: {$module_info['demo']}");
	die();
}