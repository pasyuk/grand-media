<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Modal to install Module ZIP
 */
?>
<div class="modal fade gmedia-modal" id="installModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post" enctype="multipart/form-data" action="<?php echo $gmedia_url; ?>">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php _e('Install a plugin in .zip format'); ?></h4>
            </div>
            <div class="modal-body">
                <p class="install-help"><?php _e('If you have a module in a .zip format, you may install it by uploading it here.'); ?></p>
                <?php wp_nonce_field('GmediaModule'); ?>
                <label class="screen-reader-text" for="modulezip"><?php _e('Module zip file'); ?></label>
                <input type="file" id="modulezip" name="modulezip"/>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Cancel', 'grand-media'); ?></button>
                <button type="submit" class="btn btn-primary"><?php _e('Install', 'grand-media'); ?></button>
            </div>
        </form>
    </div>
</div>

