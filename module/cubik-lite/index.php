<?php
$module_info = array(
    'base'         => 'cubik-lite',
    'name'         => 'cubik-lite',
    'title'        => 'Cubik Lite',
    'version'      => '1.7',
    'author'       => 'GalleryCreator',
    'description'  => __('Perfect gallery module for widget. This is a light version of Cubik module.

Responsive and mobile friendly &bull; Working in all major browsers &bull; built with HTML5 & CSS3', 'grand-media'),
    'type'         => 'gallery',
    'branch'       => '1',
    'status'       => 'free',
    'price'        => '',
    'demo'         => 'http://codeasily.com/portfolio/gmedia-gallery-modules/cubik-3d-photo-gallery-for-wordpress/',
    'download'     => 'http://codeasily.com/download/cubik-lite-module-zip/',
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
