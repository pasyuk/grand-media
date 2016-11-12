<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Edit Gallery Form
 */
?>
<form method="post" id="gmedia-edit-term" name="gmEditTerm" data-id="<?php echo $term_id; ?>" action="<?php echo $gmedia_url; ?>">
    <div class="panel-body">
        <!--<h4 style="margin-top:0;">
            <?php /*if($term_id) { */ ?>
                <span class="pull-right"><?php /*echo __('ID', 'grand-media') . ": {$term->term_id}"; */ ?></span>
                <?php /*_e('Edit Gallery'); */ ?>: <em><?php /*echo esc_html($term->name); */ ?></em>
            <?php /*} else {
                _e('New Gallery');
            } */ ?>
        </h4>-->

        <div class="row">
            <div class="col-sm-8">
                <div class="row">
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label><?php _e('Name', 'grand-media'); ?></label>
                            <input type="text" class="form-control input-sm" name="term[name]" value="<?php esc_attr_e($term->name); ?>" placeholder="<?php _e('Gallery Name', 'grand-media'); ?>" required/>
                        </div>
                        <div class="form-group">
                            <label><?php _e('Slug', 'grand-media'); ?></label>
                            <input type="text" class="form-control input-sm" name="term[slug]" value="<?php esc_attr_e($term->slug); ?>"/>
                        </div>
                        <div class="form-group">
                            <label><?php _e('Description', 'grand-media'); ?></label>
                            <?php
                            wp_editor(esc_textarea($term->description), "gallery{$term->term_id}_description", array('editor_class'  => 'form-control input-sm',
                                                                                                       'editor_height' => 120,
                                                                                                       'wpautop'       => false,
                                                                                                       'media_buttons' => false,
                                                                                                       'textarea_name' => 'term[description]',
                                                                                                       'textarea_rows' => '4',
                                                                                                       'tinymce'       => false,
                                                                                                       'quicktags'     => array('buttons' => apply_filters('gmedia_editor_quicktags', 'strong,em,link,ul,li,close'))
                            ));
                            ?>
                        </div>
                    </div>
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
                        </div>
                        <div class="form-group">
                            <label><?php _e('Query Args.', 'grand-media'); ?></label>
                            <textarea class="form-control input-sm" id="build_query_field" style="height:120px;" title="<?php _e("Click 'Build Query' button and choose query arguments for this gallery"); ?>"
                                      placeholder="<?php _e("Click 'Build Query' button for help with Query Args.\nIf you leave this field empty then whole Library will be loaded. That's could exceed your server's PHP Memory Limit.", 'grand-media') ?>"
                                      rows="2" name="term[query]"><?php echo(empty($gmedia_filter['query_args'])? '' : urldecode(build_query($gmedia_filter['query_args']))); ?></textarea>
                            <p class="help-block text-right"><a id="build_query" class="btn btn-sm btn-success buildquery-modal" href="#buildQuery" style="font-size:90%;"><?php _e('Build Query', 'grand-media'); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    <label>&nbsp;
                        <input type="hidden" name="term[term_id]" value="<?php echo $term_id; ?>"/>
                        <input type="hidden" name="term[taxonomy]" value="<?php echo $gmedia_term_taxonomy; ?>"/>
                        <?php
                        wp_nonce_field('GmediaGallery');
                        wp_referer_field();
                        ?>
                    </label>
                    <div>
                        <div class="btn-group btn-group" id="save_buttons">
                            <?php if($term->module['name'] != $term->meta['_module']){ ?>
                                <a href="<?php echo $gmedia_url; ?>" class="btn btn-default"><?php _e('Cancel preview module', 'grand-media'); ?></a>
                                <button type="submit" name="gmedia_gallery_save" class="btn btn-primary"><?php _e('Save with new module', 'grand-media'); ?></button>
                            <?php } else{ ?>
                                <?php $reset_settings = $gmCore->array_diff_keyval_recursive($default_options, $gallery_settings, true);
                                if(!empty($reset_settings)){
                                    ?>
                                    <button type="submit" name="gmedia_gallery_reset" class="btn btn-default" data-confirm="<?php _e('Confirm reset module settings to default preset') ?>"><?php _e('Reset to default', 'grand-media'); ?></button>
                                <?php } ?>
                                <button type="submit" name="gmedia_gallery_save" class="btn btn-primary"><?php _e('Save', 'grand-media'); ?></button>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <p><b><?php _e('Gallery ID:'); ?></b> #<?php echo $term_id; ?></p>
                <p><b><?php _e('Last edited:'); ?></b> <?php echo $term->meta['_edited']; ?></p>
                <p><?php echo '<b>' . __('Gallery module:') . '</b> <a href="#chooseModuleModal" data-toggle="modal" title="' . __('Change module for gallery', 'grand-media') . '">' . $term->meta['_module'] . '</a>';
                    if($term->module['name'] != $term->meta['_module']){
                        echo '<br /><b>' . __('Preview module:') . '</b> ' . $term->module['name'];
                        echo '<br /><span class="text-danger">' . sprintf(__('Note: Module changed to %s, but not saved yet'), $term->module['name']) . '</span>';
                    } ?></p>
                <input type="hidden" name="term[module]" value="<?php esc_attr_e($term->module['name']); ?>">
                <?php if($term_id){
                    $params = array();
                    if($term->module['name'] != $term->meta['_module']){
                        $params['gmedia_module'] = $term->module['name'];
                    }
                    $params['iframe'] = 1;
                    ?>
                    <p><b><?php _e('GmediaCloud page URL for current gallery:'); ?></b>
                        <br/><a target="_blank" href="<?php echo $term->cloud_link; ?>"><?php echo $term->cloud_link; ?></a>
                    </p>
                    <?php if($term->post_link){ ?>
                        <p><b><?php _e('Gmedia Post URL for current gallery:'); ?></b>
                            <br/><a target="_blank" href="<?php echo $term->post_link; ?>"><?php echo $term->post_link; ?></a>
                        </p>
                    <?php } ?>
                    <div class="help-block">
                        <?php _e('update <a href="options-permalink.php">Permalink Settings</a> if above link not working', 'grand-media'); ?>
                        <?php if(current_user_can('manage_options')){
                            echo '<br>' . __('More info about GmediaCloud Pages and GmediaCloud Settings can be found <a href="admin.php?page=GrandMedia_Settings#gmedia_settings_cloud">here</a>', 'grand-media');
                        } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
        $gmCore->gmedia_custom_meta_box($term->term_id, $meta_type = 'gmedia_term');
        do_action('gmedia_term_edit_form');
        ?>

        <hr/>
        <div class="well well-sm clearfix">
            <div class="btn-toolbar pull-right" id="module_preset">
                <div class="btn-group">
                    <button type="button" class="btn btn-default<?php echo ($term->module['name'] != $term->meta['_module'])? ' disabled' : ''; ?>" id="module_presets" data-toggle="popover"><?php _e('Module Presets', 'grand-media'); ?></button>
                </div>
                <script type="text/html" id="_module_presets">
                    <div style="padding-top: 5px;">
                        <?php if(current_user_can('manage_options')){ ?>
                            <p><button type="button" name="module_preset_save_global" class="ajax-submit btn btn-default btn-sm" style="width:100%"><?php _e('Save as Global Preset', 'grand-media'); ?></button></p>
                        <?php } ?>
                        <p style="white-space: nowrap">
                            <button type="button" name="module_preset_save_default" class="ajax-submit btn btn-default btn-sm"><?php _e('Save as Default', 'grand-media'); ?></button>
                            &nbsp; <em><?php _e('or', 'grand-media'); ?></em> &nbsp;
                            <?php if(!empty($default_preset)){ ?>
                                <button type="button" name="module_preset_restore_original" class="ajax-submit btn btn-default btn-sm"><?php _e('Restore Original', 'grand-media'); ?></button>
                                <input type="hidden" name="preset_default" value="<?php echo $default_preset['term_id']; ?>"/>
                            <?php } ?>
                        </p>
                        <div class="form-group clearfix" style="border-top: 1px solid #444444; padding-top: 5px;">
                            <label><?php _e('Save Preset as:', 'grand-media'); ?></label>

                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control input-sm" name="module_preset_name" placeholder="<?php _e('Preset Name', 'grand-media'); ?>" value=""/>
                                <span class="input-group-btn"><button type="button" name="module_preset_save" class="ajax-submit btn btn-primary"><?php _e('Save', 'grand-media'); ?></button></span>
                            </div>
                        </div>

                        <?php if(!empty($presets)){ ?>
                            <ul class="list-group presetlist">
                                <?php foreach($presets as $preset){
                                    $href = $gmCore->get_admin_url(array('preset' => $preset->term_id), array());

                                    $count = 1;
                                    $name  = trim(str_replace('[' . $term->module['name'] . ']', '', $preset->name, $count));
                                    $by    = '';
                                    if(!$name){
                                        if(!(int)$preset->global){
                                            continue;
                                        }
                                        $name = __('Default Settings', 'grand-media');
                                    }
                                    if((int)$preset->global){
                                        $by = ' <small style="white-space:nowrap">[' . get_the_author_meta('display_name', $preset->global) . ']</small>';
                                    }
                                    $li_item = '
                                        <li class="list-group-item" id="gm-preset-' . $preset->term_id . '">';
                                    if($user_ID == $preset->global || ((int)$preset->global && $gmCore->caps['gmedia_edit_others_media']) || current_user_can('manage_options')){
                                        $li_item .= '<span class="delpreset"><span class="label label-danger" data-id="' . $preset->term_id . '">&times;</span></span>';
                                    }
                                    $li_item .= '
                                            <a href="' . $href . '">' . $name . $by . '</a>
                                        </li>';

                                    $li[] = $li_item;
                                }
                                echo implode('', $li);
                                ?>
                            </ul>
                        <?php } ?>
                    </div>
                </script>
            </div>

            <h5><?php _e('Module Settings', 'grand-media'); ?></h5>
        </div>
        <?php
        $gallery_link_default = $gmCore->gmcloudlink($term->term_id, $term->taxterm, true);
        include(GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/module-settings.php');
        ?>
        <?php if(!empty($alert)){ ?>
            <script type="text/javascript">
                jQuery(function($) {
                    $('#chooseModuleModal').modal('show');
                });
            </script>
        <?php } ?>
    </div>

</form>

<?php

include(GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/modal-build-query.php');

if($term_id){
    $customfield_meta_type = 'gmedia_term';
    include(GMEDIA_ABSPATH . 'admin/tpl/modal-customfield.php');
}

?>

<?php if(gm_user_can('edit_others_media')){ ?>
    <div class="modal fade gmedia-modal" id="gallModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog"></div>
    </div>
<?php } ?>
