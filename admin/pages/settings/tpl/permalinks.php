<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Permalinks and GmediaCloud page settings
 *
 * @var $gmGallery
 * @var $gmDB
 * @var $gmCore
 * @var $user_ID
 */
?>
<fieldset id="gmedia_settings_permalinks" class="tab-pane">
    <h4><?php _e('Gmedia Library Items', 'grand-media'); ?></h4>
    <div class="form-group">
        <label><?php _e('Gmedia Base', 'grand-media') ?>:</label>
        <input type="text" name="set[gmedia_post_slug]" value="<?php echo $gmGallery->options['gmedia_post_slug']; ?>" class="form-control input-sm"/>

        <p class="help-block"><?php _e('Base for gmedia post url.', 'grand-media'); ?></p>
    </div>
    <div class="form-group">
        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[gmedia_has_archive]" value="0"/>
            <label><input type="checkbox" name="set[gmedia_has_archive]" value="1" <?php checked($gmGallery->options['gmedia_has_archive'], '1'); ?> /> <?php _e('Allow Gmedia Posts Archive page', 'grand-media'); ?> </label>
        </div>
    </div>
    <div class="form-group">
        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[gmedia_exclude_from_search]" value="0"/>
            <label><input type="checkbox" name="set[gmedia_exclude_from_search]" value="1" <?php checked($gmGallery->options['gmedia_exclude_from_search'], '1'); ?> /> <?php _e('Exclude Gmedia Library Items from WordPress search results on the Frontend', 'grand-media'); ?> </label>
        </div>
    </div>
    <div class="form-group">
        <label><?php _e('Default comment status for new gmedia items', 'grand-media') ?>:</label>
        <select name="set[default_gmedia_comment_status]" class="form-control input-sm">
            <option value="open" <?php selected($gmGallery->options['default_gmedia_comment_status'], 'open'); ?>><?php _e('Open', 'grand-media'); ?></option>
            <option value="closed" <?php selected($gmGallery->options['default_gmedia_comment_status'], 'closed'); ?>><?php _e('Closed', 'grand-media'); ?></option>
        </select>

        <p class="help-block"><?php _e('(These setting may be overridden for individual gmedia items.)', 'grand-media'); ?></p>
    </div>

    <hr />
    <h4><?php _e('Gmedia Albums', 'grand-media'); ?></h4>
    <div class="form-group">
        <label><?php _e('Gmedia Album Base', 'grand-media') ?>:</label>
        <input type="text" name="set[gmedia_album_post_slug]" value="<?php echo $gmGallery->options['gmedia_album_post_slug']; ?>" class="form-control input-sm"/>

        <p class="help-block"><?php _e('Base for gmedia album post url.', 'grand-media'); ?></p>
    </div>
    <div class="form-group">
        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[gmedia_album_has_archive]" value="0"/>
            <label><input type="checkbox" name="set[gmedia_album_has_archive]" value="1" <?php checked($gmGallery->options['gmedia_album_has_archive'], '1'); ?> /> <?php _e('Allow Gmedia Albums Archive page', 'grand-media'); ?> </label>
        </div>
    </div>
    <div class="form-group">
        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[gmedia_album_exclude_from_search]" value="0"/>
            <label><input type="checkbox" name="set[gmedia_album_exclude_from_search]" value="1" <?php checked($gmGallery->options['gmedia_album_exclude_from_search'], '1'); ?> /> <?php _e('Exclude Gmedia Albums from WordPress search results on the Frontend', 'grand-media'); ?> </label>
        </div>
    </div>

    <hr />
    <h4><?php _e('Gmedia Galleries', 'grand-media'); ?></h4>
    <div class="form-group">
        <label><?php _e('Gmedia Gallery Base', 'grand-media') ?>:</label>
        <input type="text" name="set[gmedia_gallery_post_slug]" value="<?php echo $gmGallery->options['gmedia_gallery_post_slug']; ?>" class="form-control input-sm"/>

        <p class="help-block"><?php _e('Base for gmedia gallery post url.', 'grand-media'); ?></p>
    </div>
    <div class="form-group">
        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[gmedia_gallery_has_archive]" value="0"/>
            <label><input type="checkbox" name="set[gmedia_gallery_has_archive]" value="1" <?php checked($gmGallery->options['gmedia_gallery_has_archive'], '1'); ?> /> <?php _e('Allow Gmedia Galleries Archive page', 'grand-media'); ?> </label>
        </div>
    </div>
    <div class="form-group">
        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[gmedia_gallery_exclude_from_search]" value="0"/>
            <label><input type="checkbox" name="set[gmedia_gallery_exclude_from_search]" value="1" <?php checked($gmGallery->options['gmedia_gallery_exclude_from_search'], '1'); ?> /> <?php _e('Exclude Gmedia Galleries from WordPress search results on the Frontend', 'grand-media'); ?> </label>
        </div>
    </div>
</fieldset>

<fieldset id="gmedia_settings_cloud" class="tab-pane">
    <p><?php _e('GmediaCloud is full window template to show your galleries, albums and other gmedia content', 'grand-media'); ?></p>

    <p><?php _e('Each module can have it\'s own design for GmediaCloud. Here you can set default module wich will be used for sharing Albums, Tags, Categories and single Gmedia Items.', 'grand-media'); ?></p>
    <br/>

    <div class="form-group">
        <label><?php _e('HashID salt for unique template URL', 'grand-media') ?>:</label>
        <input type="text" name="GmediaHashID_salt" value="<?php echo get_option('GmediaHashID_salt'); ?>" class="form-control input-sm"/>

        <p class="help-block"><?php _e('Changing this string you\'ll change Gmedia template URLs.', 'grand-media'); ?></p>
    </div>
    <div class="form-group">
        <label><?php _e('Permalink Endpoint (GmediaCloud base)', 'grand-media') ?>:</label>
        <input type="text" name="set[endpoint]" value="<?php echo $gmGallery->options['endpoint']; ?>" class="form-control input-sm"/>

        <p class="help-block"><?php _e('Changing endpoint you\'ll change Gmedia template URLs.', 'grand-media'); ?></p>
    </div>
    <div class="form-group">
        <label><?php _e('Top Bar Social Buttons', 'grand-media'); ?></label>
        <select name="set[gmediacloud_socialbuttons]" class="form-control input-sm">
            <option value="1" <?php selected($gmGallery->options['gmediacloud_socialbuttons'], '1'); ?>><?php _e('Show Social Buttons', 'grand-media'); ?></option>
            <option value="0" <?php selected($gmGallery->options['gmediacloud_socialbuttons'], '0'); ?>><?php _e('Hide Social Buttons', 'grand-media'); ?></option>
        </select>
    </div>
    <div class="form-group">
        <label><?php _e('Additional JS code for GmediaCloud Page', 'grand-media') ?>:</label>
        <textarea name="set[gmediacloud_footer_js]" rows="4" cols="20" class="form-control input-sm"><?php echo esc_html(stripslashes($gmGallery->options['gmediacloud_footer_js'])); ?></textarea>
    </div>
    <div class="form-group">
        <label><?php _e('Additional CSS code for GmediaCloud Page', 'grand-media') ?>:</label>
        <textarea name="set[gmediacloud_footer_css]" rows="4" cols="20" class="form-control input-sm"><?php echo esc_html(stripslashes($gmGallery->options['gmediacloud_footer_css'])); ?></textarea>
    </div>
</fieldset>
