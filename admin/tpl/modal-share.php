<div class="modal fade gmedia-modal" id="shareModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php _e('GmediaCloud Page'); ?></h4>
            </div>
            <form class="modal-body" method="post" id="shareForm">
                <div class="form-group">
                    <label><?php _e('Link to page', 'grand-media'); ?></label>
                    <input name="sharelink" type="text" class="form-control sharelink" readonly="readonly" value=""/>
                </div>
                <div class="form-group">
                    <label><?php _e('Send this link to', 'grand-media'); ?></label>
                    <input name="email" type="email" class="form-control sharetoemail" value="" placeholder="<?php _e('Email', 'grand-media'); ?>"/>
                    <textarea name="message" cols="20" rows="3" class="form-control" placeholder="<?php _e('Message (optional)', 'grand-media'); ?>"></textarea>
                </div>
                <input type="hidden" name="action" value="gmedia_share_page"/>
                <?php wp_nonce_field('share_modal', '_sharenonce'); ?>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary sharebutton" disabled="disabled"><?php _e('Send', 'grand-media'); ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'grand-media'); ?></button>
            </div>
        </div>
    </div>
</div>
