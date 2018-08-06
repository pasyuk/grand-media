<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Panel heading for term
 * @var $term_id
 * @var $gmedia_term_taxonomy
 * @var $gmProcessor
 * @var $gmCore
 */
$curpage = $gmCore->_get('page', 'GrandMedia');
$refurl = strpos(wp_get_referer(), "page={$curpage}")? wp_get_referer() : $gmProcessor->url;
$referer = remove_query_arg(array('edit_term', 'gallery_module'), $refurl);
?>
<div class="panel-heading-fake"></div>
<div class="panel-heading clearfix">
    <div class="btn-toolbar pull-left">
        <a class="btn btn-default pull-left" style="margin-right:20px;" href="<?php echo $referer; ?>"><?php _e('Go Back', 'grand-media'); ?></a>

        <?php if($term_id){ ?>
            <div class="btn-group">
                <a class="btn btn-default" href="#"><?php _e('Action', 'grand-media'); ?></a>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="<?php echo add_query_arg(array('page' => 'GrandMedia', 'gallery' => $term->term_id), $gmProcessor->url); ?>"><?php _e('Show in Gmedia Library', 'grand-media'); ?></a></li>
                    <?php
                    echo '<li' . (('draft' !== $term->status)? '' : ' class="disabled"') . '><a target="_blank" class="share-modal" data-target="#shareModal" data-share="' . $term->term_id . '" data-gmediacloud="' . $term->cloud_link . '" href="' . $term->post_link . '">' . __('Share', 'grand-media') . '</a></li>';

                    echo '<li' . ($term->allow_delete? '' : ' class="disabled"') . '><a href="' . wp_nonce_url(gm_get_admin_url(array('do_gmedia_terms' => 'delete',
                                                                                                                                      'ids'             => $term->term_id
                                                                                                                                ), array('edit_term'), $gmProcessor->url), 'gmedia_delete', '_wpnonce_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . __('Delete', 'grand-media') . '</a></li>';
                    ?>
                </ul>
            </div>
        <?php } ?>
        <?php if($term_id){ ?>
            <div class="term-shortcode pull-left"><input type="text" title="<?php _e('Shortcode'); ?>" class="form-control pull-left" value="<?php echo "[gmedia id={$term_id}]"; ?>" readonly/>
                <div class="input-buffer"></div>
            </div>
        <?php }
        do_action('gmedia_gallery_btn_toolbar');
        ?>
    </div>

    <div class="btn-group pull-right" id="save_buttons_duplicate">
        <?php if($term->module['name'] != $term->meta['_module']){ ?>
            <a href="<?php echo $gmedia_url; ?>" class="btn btn-default"><?php _e('Cancel preview module', 'grand-media'); ?></a>
            <button type="button" onclick="jQuery('button[name=gmedia_gallery_save]').trigger('click');" class="btn btn-primary"><?php _e('Save with new module', 'grand-media'); ?></button>
        <?php } else{ ?>
            <?php if(!empty($reset_settings)){ ?>
                <button type="button" onclick="jQuery('button[name=gmedia_gallery_reset]').trigger('click');" class="btn btn-default"><?php _e('Reset to default', 'grand-media'); ?></button>
            <?php } ?>
            <button type="button" onclick="jQuery('button[name=gmedia_gallery_save]').trigger('click');" class="btn btn-primary"><?php _e('Save', 'grand-media'); ?></button>
        <?php } ?>
    </div>

    <div class="spinner"></div>
</div>
