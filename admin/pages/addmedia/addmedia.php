<?php
/**
 * Gmedia AddMedia
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

global $user_ID, $gmGallery, $gmProcessor, $gmCore, $gmDB;

$url    = $gmProcessor->url;
$import = $gmProcessor->import;
?>

<div class="panel panel-default">

    <?php include(dirname(__FILE__) . '/tpl/panel-heading.php'); ?>

    <div class="panel-body" id="gmedia-msg-panel"></div>
    <div class="container-fluid gmAddMedia">
        <?php
        if(!$import) {
            include(dirname(__FILE__) . '/tpl/upload.php');
        } else {
            include(dirname(__FILE__) . '/tpl/import.php');
        }

        wp_original_referer_field(true, 'previous');
        ?>
    </div>
</div>


