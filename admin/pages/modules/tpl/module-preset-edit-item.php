<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Edit Gallery Form
 */
global $user_ID;
?>

<form method="post" id="gmedia-edit-term" name="gmEditTerm" data-id="<?php echo $term_id; ?>" action="<?php echo $gmedia_url; ?>">
    <div class="panel-body">
        <h4 style="margin-top:0;">
            <?php if($term_id){
                $is_preset = 'edit';
                ?>
                <span class="pull-right"><?php echo __('ID', 'grand-media') . ": {$term->term_id}"; ?></span>
                <?php printf(__('Edit %s Preset', 'grand-media'), $term->module['info']['title']); ?>: <em><?php echo esc_html($term->name); ?></em>
            <?php } else{
                $is_preset = 'new';
                printf(__('New %s Preset', 'grand-media'), $term->module['info']['title']);
            } ?>
        </h4>
        <div class="row">
            <div class="col-sm-5">
                <div class="form-group">
                    <label><?php _e('Name', 'grand-media'); ?></label>
                    <?php if($term_id && !$term->name){
                        if((int)$term->global){
                            $is_preset = 'default';
                        } else{
                            $is_preset = 'global';
                        }
                        ?>
                        <input type="text" class="form-control input-sm" name="term[name]" value="<?php $is_preset === 'global'? _e('Global Settings', 'grand-media') : _e('Default Settings', 'grand-media'); ?>" readonly/>
                        <input type="hidden" name="module_preset_save_default" value="1"/>
                    <?php } else{ ?>
                        <input type="text" class="form-control input-sm" name="term[name]" value="<?php esc_attr_e($term->name); ?>" placeholder="<?php echo $term->name? esc_attr($term->name) : __('Preset Name', 'grand-media'); ?>"/>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label><?php _e('Author', 'grand-media'); ?></label>
                    <?php
                    if($is_preset === 'global'){
                        echo '<input type="hidden" name="term[global]" value="0"/>';
                        echo '<div>' . __('Global Preset', 'grand-media') . '</div>';
                    } else{
                        $_args = array('show_option_all' => '');
                        if(!(int)$term->global){
                            $_args['selected'] = $user_ID;
                        }
                        gmedia_term_choose_author_field($term->global, $_args);
                    } ?>
                </div>
                <input type="hidden" name="term[term_id]" value="<?php echo $term_id; ?>"/>
                <input type="hidden" name="term[module]" value="<?php esc_attr_e($term->module['name']); ?>"/>
                <input type="hidden" name="term[taxonomy]" value="<?php echo $gmedia_term_taxonomy; ?>"/>
                <?php
                wp_nonce_field('GmediaGallery');
                wp_referer_field();
                ?>
                <div class="pull-right" id="save_buttons">
                    <?php if($is_preset !== 'global'){ ?>
                        <button type="submit" name="module_preset_save_global" class="btn btn-default btn-sm"><?php _e('Save as Global Preset', 'grand-media'); ?></button>
                        <?php if($is_preset !== 'default'){ ?>
                            <button type="submit" name="module_preset_save_default" class="btn btn-default btn-sm"><?php _e('Save as Default User Preset', 'grand-media'); ?></button>
                            <?php
                        }
                    }
                    $submit_name = 'module_preset_save';
                    if($is_preset === 'default'){
                        $submit_name = 'module_preset_save_default';
                    }
                    if($is_preset === 'global'){
                        $submit_name = 'module_preset_save_global';
                    }
                    ?>
                    <button type="submit" name="<?php echo $submit_name; ?>" class="btn btn-primary btn-sm"><?php _e('Save', 'grand-media'); ?></button>
                </div>
            </div>

            <div class="col-sm-5 col-sm-offset-2">
                <div class="form-group">
                    <div class="pull-right"><a id="build_query" class="label label-primary buildquery-modal" href="#buildQuery" style="font-size:90%;"><?php _e('Build Query', 'grand-media'); ?></a></div>
                    <label><?php _e('Query Args. for Preset Demo', 'grand-media'); ?></label>
                    <textarea class="form-control input-sm" id="build_query_field" style="height:64px;" rows="2" name="term[query]"><?php echo(empty($gmedia_filter['query_args'])? 'limit=20' : urldecode(build_query($gmedia_filter['query_args']))); ?></textarea>
                </div>
            </div>
        </div>

        <hr/>
        <?php
        include(GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/module-settings.php');
        ?>

    </div>

</form>

<?php
include(GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/modal-build-query.php');
?>
