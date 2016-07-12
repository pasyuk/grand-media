<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Edit Album Form
 */
$_module_preset = isset( $term->meta['_module_preset'][0] ) ? $term->meta['_module_preset'][0] : '';
?>
<form method="post" id="gmedia-edit-term" name="gmEditTerm" class="panel-body" data-id="<?php echo $term->term_id; ?>">
    <h4 style="margin-top:0;">
        <span class="pull-right"><?php echo __('ID', 'grand-media') . ": {$term->term_id}"; ?></span>
        <?php _e('Edit Album'); ?>: <em><?php echo esc_html($term->name); ?></em>
    </h4>

    <div class="row">
        <div class="col-xs-6">
            <div class="form-group">
                <label><?php _e('Name', 'grand-media'); ?></label>
                <input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr($term->name); ?>" placeholder="<?php _e('Album Name', 'grand-media'); ?>" required/>
            </div>
            <div class="form-group">
                <label><?php _e('Description', 'grand-media'); ?></label>
                <textarea class="form-control input-sm" style="height:128px;" rows="2" name="term[description]"><?php echo $term->description; ?></textarea>
            </div>
            <div class="text-right">
                <?php
                wp_nonce_field('GmediaTerms', 'term_save_wpnonce');
                wp_referer_field();
                ?>
                <input type="hidden" name="term[term_id]" value="<?php echo $term->term_id; ?>"/>
                <input type="hidden" name="term[taxonomy]" value="<?php echo $term->taxonomy; ?>"/>
                <button type="submit" class="btn btn-primary btn-sm" name="gmedia_album_save"><?php _e('Update', 'grand-media'); ?></button>
            </div>
        </div>
        <div class="col-xs-6">
            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        <label><?php _e('Author', 'grand-media'); ?></label>
                        <?php gmedia_term_choose_author_field($term->global); ?>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Status', 'grand-media'); ?></label>
                        <select name="term[status]" class="form-control input-sm">
                            <option value="publish"<?php selected($term->status, 'publish'); ?>><?php _e('Public', 'grand-media'); ?></option>
                            <option value="private"<?php selected($term->status, 'private'); ?>><?php _e('Private', 'grand-media'); ?></option>
                            <option value="draft"<?php selected($term->status, 'draft'); ?>><?php _e('Draft', 'grand-media'); ?></option>
                        </select>
                        <div class="cb-help-block">
                                <div class="checkbox"><label><input type="checkbox" name="term[status_global]" value="1"> <?php _e('Apply Status for all items in album', 'grand-media'); ?> </label></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Module/Preset', 'grand-media'); ?></label>
                        <select class="form-control input-sm" id="term_module_preset" name="term[meta][_module_preset]">
                            <option value=""<?php if('' === $_module_preset){ echo ' selected="selected"'; } ?>><?php _e('Default module in Global Settings', 'grand-media'); ?></option>
                            <?php global $gmDB, $user_ID, $gmGallery;
                            $gmedia_modules = get_gmedia_modules(false);

                            foreach($gmedia_modules['in'] as $mfold => $module) {
                                echo '<optgroup label="' . esc_attr($module['title']) . '">';
                                $presets           = $gmDB->get_terms('gmedia_module', array('status' => $mfold));
                                $selected          = selected($_module_preset, esc_attr($mfold), false);
                                $option            = array();
                                $option[] = '<option ' . $selected . ' value="' . esc_attr($mfold) . '">' . $module['title'] . ' - ' . __('Default Settings') . '</option>';
                                foreach($presets as $preset) {
                                    $selected = selected($_module_preset, $preset->term_id, false);
                                    $by_author =  ' [' . get_the_author_meta('display_name', $preset->global) .']';
                                    if('[' . $mfold . ']' === $preset->name) {
                                        $option[] = '<option ' . $selected . ' value="' . $preset->term_id . '">' . $module['title'] . $by_author  . ' - ' . __('Default Settings'). '</option>';
                                    } else {
                                        $preset_name = str_replace('[' . $mfold . '] ', '', $preset->name);
                                        $option[] = '<option ' . $selected . ' value="' . $preset->term_id . '">' . $module['title'] . $by_author  . ' - ' . $preset_name . '</option>';
                                    }
                                }
                                echo implode('', $option);
                                echo '</optgroup>';
                            }
                            ?>
                        </select>
                    </div>
                    <?php
                    /*if(isset($term->comment_status)){ ?>
                    <div class="form-group">
                        <a href="<?php echo admin_url("admin.php?page=GrandMedia&gmediablank=comments&gmedia_term_id={$term->term_id}"); ?>" data-target="#previewModal" data-width="900" data-height="500" class="preview-modal gmpost-com-count pull-right" title="<?php esc_attr_e('Comments', 'grand-media'); ?>">
                            <b class="comment-count"><?php echo $term->comment_count; ?></b>
                            <span class="glyphicon glyphicon-comment"></span>
                        </a>
                        <label><?php _e('Comment Status', 'grand-media'); ?></label>
                        <select name="term[comment_status]" class="form-control input-sm">
                            <option value="open"<?php selected($term->comment_status, 'open'); ?>><?php _e('Open', 'grand-media'); ?></option>
                            <option value="closed"<?php selected($term->comment_status, 'closed'); ?>><?php _e('Closed', 'grand-media'); ?></option>
                        </select>
                    </div>
                    <?php }*/ ?>
                </div>
                <div class="col-xs-6">
                    <?php if(isset($term->slug)){ ?>
                    <div class="form-group">
                        <label><?php _e('Slug', 'grand-media'); ?></label>
                        <input type="text" class="form-control input-sm" name="term[slug]" value="<?php echo esc_attr($term->slug); ?>"/>
                    </div>
                    <?php } ?>
                    <?php $cover_id = isset($term->meta['_cover'][0])? $term->meta['_cover'][0] : ''; ?>
                    <div class="form-group">
                        <label><?php _e('Album Cover', 'grand-media'); ?></label>
                        <input type="text" class="form-control input-sm" name="term[meta][_cover]" value="<?php echo esc_attr($cover_id); ?>" placeholder="<?php _e('Gmedia Image ID', 'grand-media'); ?>"/>
                    </div>
                    <?php
                    if(($cover_id = intval($cover_id))) {
                        if(($cover = $gmDB->get_gmedia($cover_id))) { ?>
                            <div class="gm-img-thumbnail" data-gmid="<?php echo $cover->ID; ?>"><?php
                                ?><img src="<?php echo $gmCore->gm_get_media_image($cover, 'thumb', true); ?>" alt="<?php echo $cover->ID; ?>" title="<?php echo esc_attr($cover->title); ?>"/><?php
                                ?><span class="label label-default">ID: <?php echo $cover->ID; ?></span><?php
                                ?></div>
                        <?php } else {
                            echo '<strong class="text-danger">' . __('No image with such ID', 'grand-media') . '</strong>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <hr/>
    <?php
    $gmCore->gmedia_custom_meta_box($term->term_id, $meta_type = 'gmedia_term');
    do_action('gmedia_term_edit_form');
    ?>
</form>

<div class="modal fade gmedia-modal" id="newCustomFieldModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php _e('Add New Custom Field'); ?></h4>
            </div>
            <form class="modal-body" method="post" id="newCustomFieldForm">
                <?php
                echo $gmCore->meta_form($meta_type = 'gmedia_term');
                wp_nonce_field('gmedia_custom_field', '_customfield_nonce');
                wp_referer_field();
                ?>
                <input type="hidden" name="action" value="gmedia_term_add_custom_field"/>
                <input type="hidden" class="newcustomfield-for-id" name="ID" value=""/>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary customfieldsubmit"><?php _e('Add', 'grand-media'); ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'grand-media'); ?></button>
            </div>
        </div>
    </div>
</div>

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
