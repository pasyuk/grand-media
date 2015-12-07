<?php
/**
 * Gmedia Library
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

include_once(GMEDIA_ABSPATH. 'admin/library/functions.php');

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$gmedia_url          = $gmProcessor->url;
$gmedia_user_options = $gmProcessor->user_options;

$gmedia_query = $gmDB->get_gmedias($gmProcessor->query_args);
$gmedia_count = $gmDB->count_gmedia();
$gmedia_pager = $gmDB->query_pager();

$panel_class   = array();
$panel_class[] = 'panel-fixed-header';
$panel_class[] = "display-as-{$gmProcessor->user_options['display_mode_gmedia']}";
if($gmProcessor->user_options['grid_cell_fit_gmedia']) {
    $panel_class[] = 'invert-ratio';
}

?>

<?php gmedia_filter_message(); ?>

<div class="panel panel-default <?php gm_panel_classes($panel_class); ?>" id="gmedia-panel">

    <?php include(GMEDIA_ABSPATH. 'admin/library/tpl/panel-heading.php'); ?>

    <div class="panel-body"></div>
    <div class="list-group clearfix" id="gm-list-table">
        <?php
        if(count($gmedia_query)) {

            gmedia_alert_message();

            if(!$gmProcessor->edit_mode) {
                foreach($gmedia_query as &$item) {
                    gmedia_item_more_data($item);

                    include(GMEDIA_ABSPATH. 'admin/library/tpl/' . $gmedia_user_options['display_mode_gmedia'] . '-item.php');
                }
            } elseif(gm_user_can('edit_media')) {
                foreach($gmedia_query as &$item) {
                    gmedia_item_more_data($item);

                    if(((int)$item->author != $user_ID) && !gm_user_can('edit_others_media')) {
                        include(GMEDIA_ABSPATH. 'admin/library/tpl/list-item.php');
                    } else {
                        include(GMEDIA_ABSPATH. 'admin/library/tpl/edit-item.php');
                    }
                }
            }
        } else {
            include(GMEDIA_ABSPATH. 'admin/library/tpl/no-item.php');
        } ?>
    </div>

    <?php
    include(GMEDIA_ABSPATH. 'admin/library/tpl/panel-footer.php');

    wp_original_referer_field(true, 'previous');
    wp_nonce_field('GmediaGallery');
    ?>
</div>

<div class="modal fade gmedia-modal" id="libModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog"></div>
</div>
<?php if(gm_user_can('edit_media')) { ?>
    <div class="modal fade gmedia-modal" id="gmeditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content"></div>
        </div>
    </div>
<?php } ?>
<div class="modal fade gmedia-modal" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<?php
include(GMEDIA_ABSPATH . 'admin/tpl/modal-share.php');

if($gmProcessor->edit_mode) {
    include(GMEDIA_ABSPATH . 'admin/tpl/modal-customfield.php');
} ?>

