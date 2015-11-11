<?php
$module_info = array(
	'base' => 'phantom',
	'name' => 'phantom',
	'title' => 'Phantom',
	'version' => '2.8',
	'author' => 'CodEasily.com',
	'description' => 'This module will help you to easily add a grid gallery to your WordPress website or blog. The gallery is completely customizable, resizable and is compatible with all browsers and devices (iPhone, iPad and Android smartphones).

	Responsive layout | AddThis Social Sharing integrated | Unlimited number of galleries into your WordPress website | Display in a gallery an unlimited amount of images | Customize each gallery individually | Completely customizable lightbox | Design your own navigation buttons and use them | Display items at random | Browse the gallery using the mouse or a scroll | Browse the gallery on touchscreen devices using one finger (swipe thumbnails or lightbox) | Completely resizable | Change thumbnail size, border, spacing, transparency, background ...
	',
	'type' => 'gallery',
	'status' => 'free',
	'price' => '0',
	'demo' => 'http://codeasily.com/portfolio-item/gmedia-phantom/',
	'download' => 'http://codeasily.com/download/phantom-module-zip/',
	'dependencies' => ''
);
if(isset($_GET['info'])){
	echo '<pre>' . print_r($module_info, true) . '</pre>';
} elseif(preg_match('#' . basename(dirname(__FILE__)).'/'.basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
	header("Location: {$module_info['demo']}");
	die();
}