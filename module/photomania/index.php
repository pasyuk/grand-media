<?php
$module_info = array(
    'base'         => 'photomania',
    'name'         => 'photomania',
    'title'        => 'PhotoMania',
    'version'      => '1.1',
    'author'       => 'CodEasily.com',
    'description'  => 'Responsive Gallery based on jQuery with keyboard control, displaying thumbs, author, title and optional description, download, link button, like button, full window and full screen mode',
    'type'         => 'gallery',
    'status'       => 'free',
    'price'        => '0',
    'demo'         => 'http://codeasily.com/portfolio-item/gmedia-photomania/',
    'download'     => 'http://codeasily.com/download/photomania-module-zip/',
    'dependencies' => 'mousetrap'
);
if (preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    if (isset($_GET['info'])) {
        echo '<pre>' . print_r($module_info, true) . '</pre>';
    } else {
        header("Location: {$module_info['demo']}");
        die();
    }
}