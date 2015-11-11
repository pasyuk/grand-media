<?php
$module_info = array(
	'base' => 'minima',
	'name' => 'minima',
	'title' => 'Minima',
	'version' => '2.8',
	'author' => 'CodEasily.com',
	'description' => 'Multi-tab professional image gallery skin with slideshow feature. This is the free light version of <a target="_blank" href="http://codeasily.com/portfolio-item/gmedia-optima/">Optima Module</a>.',
	'type' => 'gallery',
	'status' => 'free',
	'price' => '0',
	'demo' => '',
	'download' => 'http://codeasily.com/download/minima-module-zip/',
	'dependencies' => 'swfobject'
);
if(isset($_GET['info'])){
	echo '<pre>' . print_r($module_info, true) . '</pre>';
} elseif(preg_match('#' . basename(dirname(__FILE__)).'/'.basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
	header("Location: {$module_info['demo']}");
	die();
}