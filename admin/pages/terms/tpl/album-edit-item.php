<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Edit Album Form
 */
$_orderby       = !empty($term->meta['_orderby'][0])? $term->meta['_orderby'][0] : $gmGallery->options['in_album_orderby'];
$_order         = !empty($term->meta['_order'][0])? $term->meta['_order'][0] : $gmGallery->options['in_album_order'];
$_module_preset = !empty($term->meta['_module_preset'][0])? $term->meta['_module_preset'][0] : '';
?>
<form method="post" id="gmedia-edit-term" name="gmEditTerm" class="panel-body" data-id="<?php echo $term->term_id; ?>" action="<?php echo gm_get_admin_url(); ?>">
    <h4 style="margin-top:0;">
        <span class="pull-right"><?php echo __('ID', 'grand-media') . ": {$term->term_id}"; ?></span>
        <?php _e('Edit Album'); ?>: <em><?php echo esc_html($term->name); ?></em>
    </h4>

    <div class="row">
        <div class="col-xs-6">
            <div class="form-group">
                <label><?php _e('Name', 'grand-media'); ?></label>
                <input type="text" class="form-control input-sm" name="term[name]" value="<?php esc_attr_e($term->name); ?>" placeholder="<?php _e('Album Name', 'grand-media'); ?>" required/>
            </div>
            <div class="form-group row">
                <div class="col-xs-6">
                    <label><?php _e('Author', 'grand-media'); ?></label>
                    <?php gmedia_term_choose_author_field($term->global); ?>
                </div>
                <div class="col-xs-6">
                    <label><?php _e('Status', 'grand-media'); ?></label>
                    <select name="term[status]" class="form-control input-sm">
                        <option value="publish"<?php selected($term->status, 'publish'); ?>><?php _e('Public', 'grand-media'); ?></option>
                        <option value="private"<?php selected($term->status, 'private'); ?>><?php _e('Private', 'grand-media'); ?></option>
                        <option value="draft"<?php selected($term->status, 'draft'); ?>><?php _e('Draft', 'grand-media'); ?></option>
                    </select>
                    <div class="cb-help-block">
                        <div class="checkbox" style="margin-bottom:0;"><label><input type="checkbox" name="term[status_global]" value="1"> <?php _e('Apply Status for all items in album', 'grand-media'); ?> </label></div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label><?php _e('Description', 'grand-media'); ?></label>
                <?php
                wp_editor(esc_textarea($term->description), "album{$term->term_id}_description", array('editor_class'  => 'form-control input-sm',
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
            <div class="text-right">
                <?php
                wp_nonce_field('gmedia_terms', '_wpnonce_terms');
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
                        <label><?php _e('Order gmedia', 'grand-media'); ?></label>
                        <select name="term[meta][_orderby]" id="gmedia_term_orderby" class="form-control input-sm">
                            <option value="custom"<?php selected($_orderby, 'custom'); ?>><?php _e('Custom Order', 'grand-media'); ?></option>
                            <option value="ID"<?php selected($_orderby, 'ID'); ?>><?php _e('by ID', 'grand-media'); ?></option>
                            <option value="title"<?php selected($_orderby, 'title'); ?>><?php _e('by title', 'grand-media'); ?></option>
                            <option value="gmuid"<?php selected($_orderby, 'gmuid'); ?>><?php _e('by filename', 'grand-media'); ?></option>
                            <option value="date"<?php selected($_orderby, 'date'); ?>><?php _e('by date', 'grand-media'); ?></option>
                            <option value="modified"<?php selected($_orderby, 'modified'); ?>><?php _e('by last modified date', 'grand-media'); ?></option>
                            <option value="_created_timestamp" <?php selected($_orderby, '_created_timestamp'); ?>><?php _e('by created timestamp', 'grand-media'); ?></option>
                            <option value="comment_count" <?php selected($_orderby, 'comment_count'); ?>><?php _e('by comment count', 'grand-media'); ?></option>
                            <option value="views" <?php selected($_orderby, 'views'); ?>><?php _e('by views', 'grand-media'); ?></option>
                            <option value="likes" <?php selected($_orderby, 'likes'); ?>><?php _e('by likes', 'grand-media'); ?></option>
                            <option value="_size" <?php selected($_orderby, '_size'); ?>><?php _e('by file size', 'grand-media'); ?></option>
                            <option value="rand"<?php selected($_orderby, 'rand'); ?>><?php _e('Random', 'grand-media'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Sort order', 'grand-media'); ?></label>
                        <select id="gmedia_term_order" name="term[meta][_order]" class="form-control input-sm<?php echo ('custom' == $_orderby)? ' disabled' : ''; ?>">
                            <option value="DESC"<?php selected($_order, 'DESC'); ?>><?php _e('DESC', 'grand-media'); ?></option>
                            <option value="ASC"<?php selected($_order, 'ASC'); ?>><?php _e('ASC', 'grand-media'); ?></option>
                        </select>
                        <div class="cb-help-block">
                            <div class="checkbox" style="margin-bottom:0;"><label><input id="reset_order_option" type="checkbox" name="term[reset_custom_order]" value="1"> <?php _e('Reset custom order', 'grand-media'); ?> </label></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Module/Preset', 'grand-media'); ?></label>
                        <select class="form-control input-sm" id="term_module_preset" name="term[meta][_module_preset]">
                            <option value=""<?php if('' === $_module_preset){
                                echo ' selected="selected"';
                            } ?>><?php _e('Default module in Global Settings', 'grand-media'); ?></option>
                            <?php global $gmDB, $user_ID, $gmGallery;
                            $gmedia_modules = get_gmedia_modules(false);

                            foreach($gmedia_modules['in'] as $mfold => $module){
                                echo '<optgroup label="' . esc_attr($module['title']) . '">';
                                $presets  = $gmDB->get_terms('gmedia_module', array('status' => $mfold));
                                $selected = selected($_module_preset, esc_attr($mfold), false);
                                $option   = array();
                                $option[] = '<option ' . $selected . ' value="' . esc_attr($mfold) . '">' . $module['title'] . ' - ' . __('Default Settings') . '</option>';
                                foreach($presets as $preset){
                                    if(!(int)$preset->global && '[' . $mfold . ']' === $preset->name){
                                        continue;
                                    }
                                    $selected  = selected($_module_preset, $preset->term_id, false);
                                    $by_author = '';
                                    if((int)$preset->global){
                                        $by_author = ' [' . get_the_author_meta('display_name', $preset->global) . ']';
                                    }
                                    if('[' . $mfold . ']' === $preset->name){
                                        $option[] = '<option ' . $selected . ' value="' . $preset->term_id . '">' . $module['title'] . $by_author . ' - ' . __('Default Settings') . '</option>';
                                    } else{
                                        $preset_name = str_replace('[' . $mfold . '] ', '', $preset->name);
                                        $option[]    = '<option ' . $selected . ' value="' . $preset->term_id . '">' . $module['title'] . $by_author . ' - ' . $preset_name . '</option>';
                                    }
                                }
                                echo implode('', $option);
                                echo '</optgroup>';
                            }
                            ?>
                        </select>
                    </div>
                    <p><a href="<?php echo $term->cloud_link?>" target="_blank"><?php _e('View GmediaCloud Page', 'grand-media'); ?></a></p>
                    <?php if($term->post_link){ ?>
                        <p><a href="<?php echo $term->post_link ?>" target="_blank"><?php _e('View Wordpress Post', 'grand-media'); ?></a></p>
                    <?php }

                    /*if(isset($term->comment_status)){ ?>
                    <div class="form-group">
                        <a href="<?php echo add_query_arg(array('page' => 'GrandMedia', 'gmediablank' => 'comments', 'gmedia_term_id' => $term->term_id), $gmProcessor->url); ?>" data-target="#previewModal" data-width="900" data-height="500" class="preview-modal gmpost-com-count pull-right" title="<?php esc_attr_e('Comments', 'grand-media'); ?>">
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
                            <input type="text" class="form-control input-sm" name="term[slug]" value="<?php esc_attr_e($term->slug); ?>"/>
                        </div>
                    <?php } ?>
                    <?php if(isset($term->post_date)){ ?>
                        <div class="form-group">
                            <label><?php _e('Date', 'grand-media'); ?></label>

                            <div class="input-group gmedia_date input-group-sm" data-date-format="YYYY-MM-DD HH:mm:ss">
                                <input name="term[post_date]" type="text" readonly="readonly" class="form-control input-sm" value="<?php echo $term->post_date; ?>" tabindex="-1"/>
                                <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                            </div>
                        </div>
                    <?php } ?>
                    <?php $cover_id = isset($term->meta['_cover'][0])? $term->meta['_cover'][0] : ''; ?>
                    <div class="form-group">
                        <label><?php _e('Album Cover', 'grand-media'); ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control input-sm gmedia-cover-id" name="term[meta][_cover]" value="<?php esc_attr_e($cover_id); ?>" placeholder="<?php _e('Gmedia Image ID', 'grand-media'); ?>"/>
                            <span class="input-group-btn"><a href="<?php echo $gmCore->get_admin_url(array('page'        => 'GrandMedia',
                                                                                                           'mode'        => 'select_single',
                                                                                                           'gmediablank' => 'library',
                                                                                                           'filter'      => 'image'
                                                                                                     ), array(), true); ?>" class="btn btn-sm btn-primary preview-modal" data-target="#previewModal" data-width="1200" data-height="500" data-cls="select_gmedia_image" title="<?php _e('Choose Cover Image', 'grand-media'); ?>"><span class="glyphicon glyphicon-picture"></span></a></span>
                        </div>
                    </div>
                    <div class="gm-img-thumbnail gmedia-cover-image"><?php
                        if(($cover_id = intval($cover_id))){
                            if(($cover = $gmDB->get_gmedia($cover_id))){
                                ?><img src="<?php echo $gmCore->gm_get_media_image($cover, 'thumb', true); ?>" alt="" /><?php
                            } else{
                                echo '<strong class="text-danger">' . __('No image with such ID', 'grand-media') . '</strong>';
                            }
                        }
                        ?></div>
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
<form style="display:none;" id="gmedia-assign-term" autocomplete="off" method="post" action="<?php echo gm_get_admin_url(); ?>">
    <input type="hidden" name="alb" value="<?php echo $term->term_id; ?>">
    <input type="hidden" name="status_global" value="0">
    <input type="hidden" name="cookie_key" value="gmedia_library:frame">
    <input type="hidden" name="assign_album"/>
    <?php wp_nonce_field('gmedia_action', '_wpnonce_action'); ?>
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
                wp_nonce_field('gmedia_custom_field', '_wpnonce_custom_field');
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
