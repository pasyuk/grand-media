<?php
$module_info = array(
    'base'         => 'jq-mplayer',
    'name'         => 'jq-mplayer',
    'title'        => 'jQ Music Player',
    'version'      => '2.8',
    'author'       => 'CodEasily.com',
    'description'  => 'This beautiful audio player is totally written in JQuery and HTML5  + visitors can set rating for each track',
    'type'         => 'music',
    'status'       => 'free',
    'price'        => '0',
    'demo'         => 'http://codeasily.com/portfolio-item/gmedia-music-player/',
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