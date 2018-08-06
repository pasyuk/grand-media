<?php
$module_info = array(
    'base'         => 'photomania',
    'name'         => 'photomania',
    'title'        => 'PhotoMania',
    'version'      => '1.5',
    'author'       => 'CodEasily.com',
    'description'  => __('Responsive Gallery based on jQuery with keyboard control, displaying thumbs, author, title and optional description, download, link button, like button, full window and full screen mode', 'grand-media'),
    'type'         => 'gallery',
    'branch'       => '1',
    'status'       => 'free',
    'price'        => '0',
    'demo'         => 'http://codeasily.com/portfolio/gmedia-gallery-modules/photomania/',
    'download'     => 'http://codeasily.com/download/photomania-module-zip/',
    'dependencies' => 'swiper,mousetrap'
);
if (preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    if (isset($_GET['info'])) {
        echo '<pre>' . print_r($module_info, true) . '</pre>';
    } else {
        header("Location: {$module_info['demo']}");
        die();
    }
}