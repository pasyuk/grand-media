<?php // don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Create tags form
 */

global $gmProcessor;
$gmedia_url = $gmProcessor->url;
?>
<form method="post" id="gmedia-edit-term" name="gmAddTerms" class="panel-body" action="<?php echo add_query_arg(array('term' => 'gmedia_tag'), $gmedia_url); ?>" style="padding-bottom:0; border-bottom:1px solid #ddd;">
    <div class="row">
        <div class="form-group col-xs-9">
            <label><?php _e('Tags', 'grand-media'); ?>
                <small class="text-muted">(<?php _e('you can type multiple tags separated by comma') ?>)</small>
            </label>
            <input type="text" class="form-control input-sm" name="term[name]" placeholder="<?php _e('Tag Names', 'grand-media'); ?>" required/>
        </div>
        <div class="col-xs-3" style="padding-top:24px;">
            <?php
            wp_original_referer_field(true, 'previous');
            wp_nonce_field('GmediaTerms', 'term_save_wpnonce');
            ?>
            <input type="hidden" name="term[taxonomy]" value="gmedia_tag"/>
            <button type="submit" class="btn btn-primary btn-sm" name="gmedia_tag_add"><?php _e('Add New Tags', 'grand-media'); ?></button>
        </div>
    </div>
</form>

