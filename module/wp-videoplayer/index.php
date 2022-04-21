<?php
$module_info = array(
    'base'         => 'wp-videoplayer',
    'name'         => 'wp-videoplayer',
    'title'        => 'WP Video Player',
    'version'      => '1.4',
    'author'       => 'CodEasily.com',
    'description'  => __('Video player with playlist based on built in Wordpress Video Player', 'grand-media'),
    'type'         => 'video',
    'status'       => 'free',
    'price'        => '0',
    'demo'         => '',
    'download'     => 'http://codeasily.com/download/wp-videoplayer-module-zip/',
    'dependencies' => 'wp-util,backbone,mediaelement'
);
if (preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    if (isset($_GET['info'])) {
        echo '<pre>' . print_r($module_info, true) . '</pre>';
    } else {
        header("Location: {$module_info['demo']}");
        die();
    }
}