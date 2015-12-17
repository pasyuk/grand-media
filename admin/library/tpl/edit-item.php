<?php
/**
 * Edit Gmedia Item
 */
?>
<form class="cb_list-item list-group-item row d-row edit-gmedia<?php echo ($item->selected? ' gm-selected ' : ' ') . implode(' ', $item->classes); ?>" id="list-item-<?php echo $item->ID; ?>" data-id="<?php echo $item->ID; ?>" data-type="<?php echo $item->type; ?>" role="form">
    <div class="col-sm-4" style="max-width:340px;">
        <input name="ID" type="hidden" value="<?php echo $item->ID; ?>"/>
        <label class="cb_media-object">
            <input name="doaction[]" type="checkbox"<?php echo $item->selected? ' checked="checked"' : ''; ?> data-type="<?php echo $item->type; ?>" class="hidden" value="<?php echo $item->ID; ?>"/>
        <span data-target="<?php echo $item->url; ?>" class="thumbnail">
            <?php gmedia_item_thumbnail($item); ?>
        </span>
        </label>
        <div class="gmedia-actions">
            <?php $media_action_links = gmedia_item_actions($item);
            unset($media_action_links['edit_data']);
            echo implode(' | ', $media_action_links);
            ?>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="row">
            <div class="form-group col-lg-6">
                <label><?php _e('Title', 'grand-media'); ?></label>
                <input name="title" type="text" class="form-control input-sm" placeholder="<?php _e('Title', 'grand-media'); ?>" value="<?php echo esc_attr($item->title); ?>">
            </div>
            <div class="form-group col-lg-6">
                <label><?php _e('Link URL', 'grand-media'); ?></label>
                <input name="link" type="text" class="form-control input-sm" value="<?php echo $item->link; ?>"/>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-6">
                <label><?php _e('Description', 'grand-media'); ?></label>
                <?php if('false' == $gmedia_user_options['library_edit_quicktags']) {
                    echo "<textarea id='gm{$item->ID}_description' class='form-control input-sm' name='description' cols='20' rows='4' style='height:174px'>" . esc_html($item->description) . '</textarea>';
                } else {
                    wp_editor(esc_html($item->description), "gm{$item->ID}_description", array(
                            'editor_class'  => 'form-control input-sm',
                            'editor_height' => 140,
                            'wpautop'       => false,
                            'media_buttons' => false,
                            'textarea_name' => 'description',
                            'textarea_rows' => '4',
                            'tinymce'       => false,
                            'quicktags'     => array('buttons' => apply_filters('gmedia_editor_quicktags', 'strong,em,link,ul,li,close'))
                    ));
                } ?>
            </div>
            <div class="col-lg-6">
                <?php if(('image' != $item->type)) { ?>
                    <div class="form-group">
                        <label><?php _e('Custom Cover', 'grand-media'); ?></label>
                        <input name="meta[_cover]" type="text" class="form-control input-sm gmedia-cover" value="<?php echo isset($meta['_cover'][0])? $meta['_cover'][0] : ''; ?>" placeholder="<?php _e('Gmedia ID or Image URL', 'grand-media'); ?>"/>
                    </div>
                <?php } ?>
                <?php if(gm_user_can('terms')) { ?>
                    <?php if($item->editor) { ?>
                        <?php
                        $cat_name  = empty($item->category)? 0 : reset($item->category)->name;
                        $term_type = 'gmedia_category';
                        $gm_terms  = $gmGallery->options['taxonomies'][$term_type];

                        $terms_category = '';
                        if(count($gm_terms)) {
                            foreach($gm_terms as $term_name => $term_title) {
                                $selected_option = ($cat_name === $term_name)? ' selected="selected"' : '';
                                $terms_category .= '<option' . $selected_option . ' value="' . $term_name . '">' . esc_html($term_title) . '</option>' . "\n";
                            }
                        }
                        ?>
                        <div class="form-group">
                            <label><?php _e('Category', 'grand-media'); ?> </label>
                            <select name="terms[gmedia_category]" class="gmedia_category form-control input-sm">
                                <option<?php echo $cat_name? '' : ' selected="selected"'; ?> value=""><?php _e('Uncategorized', 'grand-media'); ?></option>
                                <?php echo $terms_category; ?>
                            </select>
                        </div>
                    <?php } ?>

                    <?php
                    $alb_id    = empty($item->album)? 0 : reset($item->album)->term_id;
                    $term_type = 'gmedia_album';
                    $args      = array();
                    if(!gm_user_can('edit_others_media')) {
                        $args = array('global' => array(0, $user_ID), 'orderby' => 'global_desc_name');
                    }
                    $gm_terms = $gmDB->get_terms($term_type, $args);

                    $terms_album  = '';
                    $album_status = 'none';
                    if(count($gm_terms)) {
                        foreach($gm_terms as $term) {
                            $author_name = '';
                            if($term->global) {
                                if(gm_user_can('edit_others_media')) {
                                    $author_name .= ' &nbsp; ' . sprintf(__('by %s', 'grand-media'), get_the_author_meta('display_name', $term->global));
                                }
                            } else {
                                $author_name .= ' &nbsp; (' . __('shared', 'grand-media') . ')';
                            }
                            if('public' != $term->status) {
                                $author_name .= ' [' . $term->status . ']';
                            }

                            $selected_option = '';
                            if($alb_id == $term->term_id) {
                                $selected_option = ' selected="selected"';
                                $album_status    = $term->status;
                            }
                            $terms_album .= '<option' . $selected_option . ' value="' . $term->term_id . '">' . esc_html($term->name) . $author_name . '</option>' . "\n";
                        }
                    }
                    ?>
                    <div class="form-group status-album bg-status-<?php echo $album_status; ?>">
                        <label><?php _e('Album ', 'grand-media'); ?></label>
                        <select name="terms[gmedia_album]" data-create="<?php echo gm_user_can('album_manage')? 'true' : 'false'; ?>" class="combobox_gmedia_album form-control input-sm" placeholder="<?php _e('Album Name...', 'grand-media'); ?>">
                            <option<?php echo $alb_id? '' : ' selected="selected"'; ?> value=""></option>
                            <?php echo $terms_album; ?>
                        </select>
                    </div>
                    <?php
                    if(!empty($item->tags)) {
                        $terms_tag = array();
                        foreach($item->tags as $c) {
                            $terms_tag[] = esc_html($c->name);
                        }
                        $terms_tag = join(', ', $terms_tag);
                    } else {
                        $terms_tag = '';
                    }
                    ?>
                    <div class="form-group">
                        <label><?php _e('Tags ', 'grand-media'); ?></label>
                        <textarea name="terms[gmedia_tag]" class="gmedia_tags_input form-control input-sm" rows="1" cols="50"><?php echo $terms_tag; ?></textarea>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="form-group">
                    <label><?php _e('Filename', 'grand-media'); ?> <small>(ext: .<?php echo $item->ext; ?>)</small></label>
                    <input name="filename" type="text" class="form-control input-sm gmedia-filename" <?php echo (!gm_user_can('delete_others_media') && ((int)$item->author !== $user_ID))? 'readonly' : ''; ?> value="<?php echo pathinfo($item->gmuid, PATHINFO_FILENAME); ?>"/>
                </div>
                <div class="form-group">
                    <label><?php _e('Date', 'grand-media'); ?></label>

                    <div class="input-group gmedia_date input-group-sm" data-date-format="YYYY-MM-DD HH:mm:ss">
                        <input name="date" type="text" readonly="readonly" class="form-control input-sm" value="<?php echo $item->date; ?>"/>
								<span class="input-group-btn"><button type="button" class="btn btn-primary">
                                        <span class="glyphicon glyphicon-calendar"></span></button></span>
                    </div>
                </div>
                <div class="form-group status-item bg-status-<?php echo $item->status; ?>">
                    <label><?php _e('Status', 'grand-media'); ?></label>
                    <select name="status" class="form-control input-sm">
                        <option <?php selected($item->status, 'public'); ?> value="public"><?php _e('Public', 'grand-media'); ?></option>
                        <option <?php selected($item->status, 'private'); ?> value="private"><?php _e('Private', 'grand-media'); ?></option>
                        <option <?php selected($item->status, 'draft'); ?> value="draft"><?php _e('Draft', 'grand-media'); ?></option>
                    </select>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="form-group">
                    <label><?php _e('Author', 'grand-media'); ?></label>
                    <?php $user_ids = gm_user_can('delete_others_media')? $gmCore->get_editable_user_ids() : false;
                    if($user_ids) {
                        if(!in_array($user_ID, $user_ids)) {
                            array_push($user_ids, $user_ID);
                        }
                        wp_dropdown_users(array(
                                                  'include'          => $user_ids,
                                                  'include_selected' => true,
                                                  'name'             => 'author',
                                                  'selected'         => $item->author,
                                                  'class'            => 'form-control',
                                                  'multi'            => true
                                          ));
                    } else {
                        echo '<input type="hidden" name="author" value="' . $item->author . '"/>';
                        echo '<div>' . get_the_author_meta('display_name', $item->author) . '</div>';
                    }
                    ?>
                </div>
                <?php if(('image' == $item->type) || ('video' == $item->type)) { ?>
                    <div class="form-group">
                        <label><?php _e('GPS Location', 'grand-media'); ?></label>

                        <div class="input-group input-group-sm">
                            <input name="meta[_gps]" type="text" class="form-control input-sm gps_map_coordinates" value="<?php echo $item->gps; ?>" placeholder="<?php _e('Latitude, Longtitude', 'grand-media'); ?>" autocomplete="off"/>
								            <span class="input-group-btn"><a href="<?php echo admin_url("admin.php?page=GrandMedia&gmediablank=map_editor&id={$item->ID}") ?>" class="btn btn-primary gmedit-modal" data-target="#gmeditModal">
                                                    <span class="glyphicon glyphicon-map-marker"></span></a></span>
                        </div>
                    </div>
                <?php } ?>
                <p class="media-meta">
                    <span class="label label-default"><?php _e('ID', 'grand-media') ?>:</span> <strong><?php echo $item->ID; ?></strong>
                    <br/><span class="label label-default"><?php _e('Type', 'grand-media'); ?>:</span> <?php echo $item->mime_type; ?>
                    <?php if(('image' == $item->type) && !empty($item->meta['_metadata'])) { ?>
                        <br/><span class="label label-default"><?php _e('Dimensions', 'grand-media'); ?>:</span>
                        <a href="<?php echo $item->url_original; ?>"
                           data-target="#previewModal"
                           data-width="<?php echo $item->meta['_metadata'][0]['original']['width']; ?>"
                           data-height="<?php echo $item->meta['_metadata'][0]['original']['height']; ?>"
                           class="preview-modal"
                           title="<?php _e('Original', 'grand-media'); ?>"><?php echo $item->meta['_metadata'][0]['original']['width'] . '×' . $item->meta['_metadata'][0]['original']['height']; ?></a>,
                        <a href="<?php echo $item->url; ?>"
                           data-target="#previewModal"
                           data-width="<?php echo $item->meta['_metadata'][0]['web']['width']; ?>"
                           data-height="<?php echo $item->meta['_metadata'][0]['web']['height']; ?>"
                           class="preview-modal"
                           title="<?php _e('Webimage', 'grand-media'); ?>"><?php echo $item->meta['_metadata'][0]['web']['width'] . '×' . $item->meta['_metadata'][0]['web']['height']; ?></a>,
                        <a href="<?php echo $item->url_thumb; ?>"
                           data-target="#previewModal"
                           data-width="<?php echo $item->meta['_metadata'][0]['thumb']['width']; ?>"
                           data-height="<?php echo $item->meta['_metadata'][0]['thumb']['height']; ?>"
                           class="preview-modal"
                           title="<?php _e('Thumbnail', 'grand-media'); ?>"><?php echo $item->meta['_metadata'][0]['thumb']['width'] . '×' . $item->meta['_metadata'][0]['thumb']['height']; ?></a>
                        <br/><span class="label label-default"><?php _e('File Size', 'grand-media') ?>:</span> <?php echo $gmCore->filesize($item->path_original) . ', ' . $gmCore->filesize($item->path) . ', ' . $gmCore->filesize($item->path_thumb); ?>
                    <?php } else { ?>
                        <br/><span class="label label-default"><?php _e('File Size', 'grand-media') ?>:</span> <?php echo $gmCore->filesize($item->path); ?>
                    <?php } ?>
                    <?php if(!empty($item->meta['_created_timestamp'][0])) { ?>
                        <br/><span class="label label-default"><?php _e('Created', 'grand-media') ?>:</span> <?php echo date('Y-m-d H:i:s ', $item->meta['_created_timestamp'][0]); ?>
                    <?php } ?>
                    <br/><span class="label label-default"><?php _e('Uploaded', 'grand-media') ?>:</span> <?php echo $item->date; ?>
                    <br/><span class="label label-default"><?php _e('Last Edited', 'grand-media') ?>:</span> <span class="gm-last-edited modified"><?php echo $item->modified; ?></span>
                </p>
            </div>
        </div>
        <?php
        $gmCore->gmedia_custom_meta_box($item->ID);
        do_action('gmedia_edit_form');
        ?>
    </div>
</form>