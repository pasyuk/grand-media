<?php
$module_info = array(
    'base'         => 'amron',
    'name'         => 'amron',
    'title'        => 'Amron',
    'version'      => '4.5',
    'author'       => 'GalleryCreator',
    'description'  => 'Responsive AJAX Gallery with Masonry layout. The gallery is completely customisable, resizable and is compatible with all browsers and devices (iPhone, iPad and Android smartphones). Required Gmedia Gallery plugin v1.14+',
    'type'         => 'gallery',
    'widget'       => true,
    'branch'       => '1',
    'status'       => 'free',
    'price'        => '',
    'demo'         => 'https://codeasily.com/portfolio/gmedia-gallery-modules/amron/',
    'download'     => '',
    'dependencies' => ''
);
if (preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	if (isset($_GET['info'])) {
		echo '<pre>' . print_r($module_info, true) . '</pre>';
	} else {
		header("Location: {$module_info['demo']}");
		die();
	}
}