<?php
$module_info = array(
    'base'         => 'jq-mplayer',
    'name'         => 'jq-mplayer',
    'title'        => 'jQ Music Player',
    'version'      => '2.12',
    'author'       => 'CodEasily.com',
    'description'  => __('This beautiful audio player is totally written in JQuery and HTML5  + visitors can set rating for each track', 'grand-media'),
    'type'         => 'music',
    'status'       => 'free',
    'price'        => '0',
    'demo'         => 'http://codeasily.com/portfolio/gmedia-gallery-modules/music-player/',
    'download'     => 'http://codeasily.com/download/jq-mplayer-module-zip/',
    'dependencies' => 'jplayer'
);
if (preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    if (isset($_GET['info'])) {
        echo '<pre>' . print_r($module_info, true) . '</pre>';
    } else {
        header("Location: {$module_info['demo']}");
        die();
    }
}