<?php
$module_info = array(
    'base'         => 'afflux',
    'name'         => 'afflux',
    'title'        => 'Afflux',
    'version'      => '3.9',
    'author'       => 'CodEasily.com',
    'description'  => 'A Free Gallery Skin that supports thumbnails size change, color change, captions and autoplay. Responsive and mobile friendly gallery.',
    'type'         => 'gallery',
    'status'       => 'free',
    'price'        => '0',
    'demo'         => 'http://codeasily.com/portfolio-item/gmedia-afflux/',
    'download'     => 'http://codeasily.com/download/afflux-module-zip/',
    'dependencies' => 'swfobject'
);

if (preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    if (isset($_GET['info'])) {
        echo '<pre>' . print_r($module_info, true) . '</pre>';
    } else {
        header("Location: {$module_info['demo']}");
        die();
    }
}