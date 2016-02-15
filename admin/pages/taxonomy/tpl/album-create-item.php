<?php
/**
 * Add Album Form
 */
global $gmProcessor, $gmGallery;
$gmedia_url = $gmProcessor->url;
?>
<form method="post" id="gmedia-edit-term" name="gmAddTerms" class="panel-body" action="<?php echo $gmedia_url; ?>" style="padding-bottom:0; border-bottom:1px solid #ddd;">
    <div class="row">
        <div class="col-xs-6">
            <div class="form-group">
                <label><?php _e('Name', 'grand-media'); ?></label>
                <input type="text" class="form-control input-sm" name="term[name]" placeholder="<?php _e('Album Name', 'grand-media'); ?>" required/>
            </div>
            <div class="form-group">
                <label><?php _e('Description', 'grand-media'); ?></label>
                <textarea class="form-control input-sm" style="height:98px;" rows="2" name="term[description]"></textarea>
            </div>
        </div>
        <div class="col-xs-6">
            <div class="form-group row">
                <div class="col-xs-6">
                    <label><?php _e('Order gmedia', 'grand-media'); ?></label>
                    <select name="term[meta][_orderby]" class="form-control input-sm">
                        <option value="custom"><?php _e('user defined', 'grand-media'); ?></option>
                        <option selected="selected" value="ID"><?php _e('by ID', 'grand-media'); ?></option>
                        <option value="title"><?php _e('by title', 'grand-media'); ?></option>
                        <option value="gmuid"><?php _e('by filename', 'grand-media'); ?></option>
                        <option value="date"><?php _e('by date', 'grand-media'); ?></option>
                        <option value="modified"><?php _e('by last modified date', 'grand-media'); ?></option>
                        <option value="rand"><?php _e('Random', 'grand-media'); ?></option>
                    </select>
                </div>
                <div class="col-xs-6">
                    <label><?php _e('Sort order', 'grand-media'); ?></label>
                    <select name="term[meta][_order]" class="form-control input-sm">
                        <option selected="selected" value="DESC"><?php _e('DESC', 'grand-media'); ?></option>
                        <option value="ASC"><?php _e('ASC', 'grand-media'); ?></option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        <label><?php _e('Status', 'grand-media'); ?></label>
                        <select name="term[status]" class="form-control input-sm">
                            <option selected="selected" value="publish"><?php _e('Public', 'grand-media'); ?></option>
                            <option value="private"><?php _e('Private', 'grand-media'); ?></option>
                            <option value="draft"><?php _e('Draft', 'grand-media'); ?></option>
                        </select>
                    </div>
                    <?php /* ?>
                    <div class="form-group">
                        <label><?php _e('Comment Status', 'grand-media'); ?></label>
                        <select name="term[comment_status]" class="form-control input-sm">
                            <option <?php echo ('open' == $gmGallery->options['default_gmedia_term_comment_status'])? 'selected="selected"' : ''; ?> value="open"><?php _e('Open', 'grand-media'); ?></option>
                            <option <?php echo ('closed' == $gmGallery->options['default_gmedia_term_comment_status'])? 'selected="selected"' : ''; ?> value="closed"><?php _e('Closed', 'grand-media'); ?></option>
                        </select>
                    </div>
                    <?php */ ?>
                </div>
                <div class="col-xs-6">
                    <div class="form-group">
                        <label><?php _e('Author', 'grand-media'); ?></label>
                        <?php gmedia_term_choose_author_field(); ?>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <?php
                        wp_original_referer_field(true, 'previous');
                        wp_nonce_field('GmediaTerms', 'term_save_wpnonce');
                        ?>
                        <input type="hidden" name="term[taxonomy]" value="gmedia_album"/>
                        <button style="display:block" type="submit" class="btn btn-primary btn-sm" name="gmedia_album_save"><?php _e('Add New Album', 'grand-media'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
