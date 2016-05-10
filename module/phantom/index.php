<?php
$module_info = array(
    'base'         => 'phantom',
    'name'         => 'phantom',
    'title'        => 'Phantom',
    'version'      => '3.5',
    'author'       => 'CodEasily.com',
    'description'  => 'This module will help you to easily add a grid gallery to your WordPress website or blog. The gallery is completely customizable, resizable and is compatible with all browsers and devices (iPhone, iPad and Android smartphones).

	Responsive | Social Sharing integrated | Views/Likes Counters Support | Comments Support | Customize each gallery individually | Customizable lightbox | Deeplinking support | Change thumbnail size, border, spacing, transparency, background, controls ...
	',
    'type'         => 'gallery',
    'status'       => 'free',
    'price'        => '0',
    'demo'         => 'http://codeasily.com/portfolio-item/gmedia-phantom/',
    'download'     => 'http://codeasily.com/download/phantom-module-zip/',
    'dependencies' => 'magnific-popup'
);
if (preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    if (isset($_GET['info'])) {
        echo '<pre>' . print_r($module_info, true) . '</pre>';
    } else {
        header("Location: {$module_info['demo']}");
        die();
    }
}