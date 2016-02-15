<?php
/**
 * Common Settings
 *
 * @var $gmGallery
 */
?>
<fieldset id="gmedia_settings_other" class="tab-pane">
    <div class="form-group">
        <label><?php _e('When delete (uninstall) plugin', 'grand-media') ?>:</label>
        <select name="set[uninstall_dropdata]" class="form-control input-sm">
            <option value="all" <?php selected($gmGallery->options['uninstall_dropdata'], 'all'); ?>><?php _e('Delete database and all uploaded files', 'grand-media'); ?></option>
            <option value="db" <?php selected($gmGallery->options['uninstall_dropdata'], 'db'); ?>><?php _e('Delete database only and leave uploaded files', 'grand-media'); ?></option>
            <option value="none" <?php selected($gmGallery->options['uninstall_dropdata'], 'none'); ?>><?php _e('Do not delete database and uploaded files', 'grand-media'); ?></option>
        </select>
    </div>
    <div class="form-group row">
        <div class="col-xs-6">
            <label><?php _e('In Tags order gmedia', 'grand-media'); ?></label>
            <select name="set[in_tag_orderby]" class="form-control input-sm">
                <option value="ID" <?php selected($gmGallery->options['in_tag_orderby'], 'ID'); ?>><?php _e('by ID', 'grand-media'); ?></option>
                <option value="title" <?php selected($gmGallery->options['in_tag_orderby'], 'title'); ?>><?php _e('by title', 'grand-media'); ?></option>
                <option value="gmuid" <?php selected($gmGallery->options['in_tag_orderby'], 'gmuid'); ?>><?php _e('by filename', 'grand-media'); ?></option>
                <option value="date" <?php selected($gmGallery->options['in_tag_orderby'], 'date'); ?>><?php _e('by date', 'grand-media'); ?></option>
                <option value="modified" <?php selected($gmGallery->options['in_tag_orderby'], 'modified'); ?>><?php _e('by last modified date', 'grand-media'); ?></option>
                <option value="rand" <?php selected($gmGallery->options['in_tag_orderby'], 'rand'); ?>><?php _e('Random', 'grand-media'); ?></option>
            </select>
        </div>
        <div class="col-xs-6">
            <label><?php _e('Sort order', 'grand-media'); ?></label>
            <select name="set[in_tag_order]" class="form-control input-sm">
                <option value="DESC" <?php selected($gmGallery->options['in_tag_order'], 'DESC'); ?>><?php _e('DESC', 'grand-media'); ?></option>
                <option value="ASC" <?php selected($gmGallery->options['in_tag_order'], 'ASC'); ?>><?php _e('ASC', 'grand-media'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-xs-6">
            <label><?php _e('In Category order gmedia', 'grand-media'); ?></label>
            <select name="set[in_category_orderby]" class="form-control input-sm">
                <option value="ID" <?php selected($gmGallery->options['in_category_orderby'], 'ID'); ?>><?php _e('by ID', 'grand-media'); ?></option>
                <option value="title" <?php selected($gmGallery->options['in_category_orderby'], 'title'); ?>><?php _e('by title', 'grand-media'); ?></option>
                <option value="gmuid" <?php selected($gmGallery->options['in_category_orderby'], 'gmuid'); ?>><?php _e('by filename', 'grand-media'); ?></option>
                <option value="date" <?php selected($gmGallery->options['in_category_orderby'], 'date'); ?>><?php _e('by date', 'grand-media'); ?></option>
                <option value="modified" <?php selected($gmGallery->options['in_category_orderby'], 'modified'); ?>><?php _e('by last modified date', 'grand-media'); ?></option>
                <option value="rand" <?php selected($gmGallery->options['in_category_orderby'], 'rand'); ?>><?php _e('Random', 'grand-media'); ?></option>
            </select>
        </div>
        <div class="col-xs-6">
            <label><?php _e('Sort order', 'grand-media'); ?></label>
            <select name="set[in_category_order]" class="form-control input-sm">
                <option value="DESC" <?php selected($gmGallery->options['in_category_order'], 'DESC'); ?>><?php _e('DESC', 'grand-media'); ?></option>
                <option value="ASC" <?php selected($gmGallery->options['in_category_order'], 'ASC'); ?>><?php _e('ASC', 'grand-media'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label><?php _e('Forbid other plugins to load their JS and CSS on Gmedia admin pages', 'grand-media') ?>:</label>

        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[isolation_mode]" value="0"/>
            <label><input type="checkbox" name="set[isolation_mode]" value="1" <?php checked($gmGallery->options['isolation_mode'], '1'); ?> /> <?php _e('Enable Gmedia admin panel Isolation Mode', 'grand-media'); ?> </label>

            <p class="help-block"><?php _e('This option could help to avoid JS and CSS conflicts with other plugins in admin panel.', 'grand-media'); ?></p>
        </div>
    </div>
    <div class="form-group">
        <label><?php _e('Forbid theme to format Gmedia shortcode\'s content', 'grand-media') ?>:</label>

        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[shortcode_raw]" value="0"/>
            <label><input type="checkbox" name="set[shortcode_raw]" value="1" <?php checked($gmGallery->options['shortcode_raw'], '1'); ?> /> <?php _e('Raw output for Gmedia Shortcode', 'grand-media'); ?> </label>

            <p class="help-block"><?php _e('Some themes reformat shortcodes and break it functionality (mostly when you add description to images). Turning this on should solve this problem.', 'grand-media'); ?></p>
        </div>
    </div>
    <div class="form-group">
        <label><?php _e('Debug Mode', 'grand-media') ?>:</label>

        <div class="checkbox" style="margin:0;">
            <input type="hidden" name="set[debug_mode]" value=""/>
            <label><input type="checkbox" name="set[debug_mode]" value="1" <?php checked($gmGallery->options['debug_mode'], '1'); ?> /> <?php _e('Enable Debug Mode on Gmedia admin pages', 'grand-media'); ?> </label>
        </div>
    </div>
    <?php
    $allowed_post_types = (array)$gmGallery->options['gmedia_post_types_support'];
    $args               = array(
        'public'   => true,
        'show_ui'  => true,
        '_builtin' => false
    );
    $output             = 'objects'; // names or objects, note names is the default
    $operator           = 'and'; // 'and' or 'or'
    $post_types         = get_post_types($args, $output, $operator);
    if (! empty($post_types)) { ?>
        <div class="form-group">
            <label style="margin-bottom:-5px;"><?php _e('Enable Gmedia Library button on custom post types', 'grand-media') ?>:</label>
            <input type="hidden" name="set[gmedia_post_types_support]" value=""/>
            <?php
            foreach ($post_types as $post_type) { ?>
                <div class="checkbox"><label><input type="checkbox" name="set[gmedia_post_types_support][]" value="<?php echo $post_type->name; ?>" <?php echo in_array($post_type->name, $allowed_post_types)? 'checked="checked"' : ''; ?> /> <?php echo $post_type->label . ' (' . $post_type->name . ')'; ?></label> </div>
            <?php } ?>
        </div>
    <?php } ?>
</fieldset>

