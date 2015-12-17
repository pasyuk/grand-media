<?php
/**
 * Gmedia Library
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

include_once(GMEDIA_ABSPATH . 'admin/functions.php');
include_once(GMEDIA_ABSPATH . 'admin/addmedia/functions.php');

global $user_ID, $gmGallery, $gmProcessor, $gmCore, $gmDB;

$url    = $gmProcessor->url;
$import = $gmProcessor->import;
?>

<div class="panel panel-default">

    <?php include(GMEDIA_ABSPATH . 'admin/addmedia/tpl/panel-heading.php'); ?>

    <div class="panel-body" id="gmedia-msg-panel"></div>
    <div class="container-fluid gmAddMedia">
        <?php
        if(!$import) {
            include(GMEDIA_ABSPATH. 'admin/addmedia/tpl/upload.php');
        } else {
            include(GMEDIA_ABSPATH. 'admin/addmedia/tpl/import.php');
        }

        wp_original_referer_field(true, 'previous');
        ?>
    </div>
</div>


