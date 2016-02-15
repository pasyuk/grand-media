<?php
/**
 * Edit Filter Form
 */
?>
<form method="post" id="gmedia-edit-term" name="gmEditTerm" class="panel-body">
    <h4 style="margin-top:0;">
        <?php if($term_id) { ?>
            <span class="pull-right"><?php echo __('ID', 'grand-media') . ": {$term['term_id']}"; ?></span>
            <?php _e('Edit Filter'); ?>: <em><?php echo esc_html($term['name']); ?></em>
        <?php } else {
            _e('Create Filter');
        } ?>
    </h4>

    <div class="row">
        <div class="col-xs-6">
            <div class="form-group">
                <label><?php _e('Filter Name', 'grand-media'); ?></label>
                <input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr($term['name']); ?>" placeholder="<?php _e('Filter Name', 'grand-media'); ?>" required/>
            </div>
            <div class="form-group pull-right">
                <?php
                wp_nonce_field('GmediaGallery');
                wp_nonce_field('GmediaTerms', 'term_save_wpnonce');
                ?>
                <input type="hidden" name="term[taxonomy]" value="gmedia_filter"/>
                <input type="hidden" name="term[term_id]" value="<?php echo $term_id; ?>"/>
                <button type="submit" class="btn btn-primary btn-sm" name="gmedia_filter_save"><?php _e('Save', 'grand-media'); ?></button>
            </div>
            <p><b><?php _e('Filter Author:', 'grand-media'); ?></b>
                <?php if(gm_user_can('delete_others_media')) { ?>
                    <a href="#gallModal" data-modal="select_author" data-action="gmedia_get_modal" class="gmedia-modal" title="<?php _e('Click to choose author for gallery', 'grand-media'); ?>"><?php echo $term['global']? get_the_author_meta('display_name', $term['global']) : __('(no author / shared albums)'); ?></a>
                    <?php if($author_new) {
                        echo '<br /><span class="text-danger">' . __('Note: Author changed but not saved yet. You can see Albums list only of chosen author') . '</span>';
                    } ?>
                <?php } else {
                    echo $term['global']? get_the_author_meta('display_name', $term['global']) : '&#8212;';
                } ?>
                <input type="hidden" name="term[global]" value="<?php echo $term['global']; ?>"/></p>

        </div>
        <div class="col-xs-6">
            <div class="form-group">
                <label><?php _e('Description', 'grand-media'); ?></label>
                <textarea class="form-control input-sm" style="height:77px;" rows="2" name="term[description]"><?php echo $term['description']; ?></textarea>
            </div>
        </div>
    </div>
    <hr/>
    <h4 style="margin-top:0;"><?php _e('Query Parameters'); ?></h4>

    <?php if(gm_user_can('terms')) { ?>
        <div class="form-group">
            <?php
            $term_type = 'gmedia_album';
            $args      = array();
            if($term['global']) {
                if(user_can($term['global'], 'gmedia_edit_others_media')) {
                    $args['global'] = '';
                } else {
                    $args['global'] = array(0, $term['global']);
                }
            } else {
                $args['global'] = 0;
            }
            $gm_terms = $gmDB->get_terms($term_type, $args);

            $terms_items = '';
            if(count($gm_terms)) {
                foreach($gm_terms as $_term) {
                    $selected = (in_array($_term->term_id, $gmedia_album))? ' selected="selected"' : '';
                    $terms_items .= '<option value="' . $_term->term_id . '"' . $selected . '>' . esc_html($_term->name) . ('publish' == $_term->status? '' : " [{$_term->status}]") . ' &nbsp; (' . $_term->count . ')</option>' . "\n";
                }
            }
            $setvalue = !empty($gmedia_album)? 'data-setvalue="' . implode(',', $gmedia_album) . '"' : '';
            ?>
            <label><?php _e('Choose Albums', 'grand-media'); ?> </label>

            <div class="row">
                <div class="col-xs-8">
                    <select <?php echo $setvalue; ?> id="gmedia_album" name="filter_data[gmedia_album][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Any Album...', 'grand-media')); ?>">
                        <option value=""<?php if(empty($gmedia_album)) {
                            echo ' selected="selected"';
                        } ?>><?php _e('Any Album...', 'grand-media'); ?></option>
                        <?php echo $terms_items; ?>
                    </select>
                </div>
                <div class="col-xs-4">
                    <select name="filter_data[album__condition]" class="form-control input-sm">
                        <option <?php selected($album__condition, 'album__in'); ?> value="album__in"><?php _e('get albums', 'grand-media'); ?></option>
                        <option <?php selected($album__condition, 'album__not_in'); ?> value="album__not_in"><?php _e('exclude albums', 'grand-media'); ?></option>
                    </select>
                </div>
            </div>
            <p class="help-block"><?php _e('You can choose Albums from the same author as Gallery author or Albums without author', 'grand-media'); ?></p>
        </div>

        <div class="form-group">
            <?php
            $term_type    = 'gmedia_category';
            $gm_terms_all = $gmGallery->options['taxonomies'][$term_type];
            $gm_terms     = $gmDB->get_terms($term_type, array('fields' => 'names_count'));

            $terms_items = '';
            if(count($gm_terms)) {
                foreach($gm_terms as $id => $_term) {
                    $selected = (in_array($id, $gmedia_category))? ' selected="selected"' : '';
                    $terms_items .= '<option value="' . $id . '"' . $selected . '>' . esc_html($gm_terms_all[$_term['name']]) . ' (' . $_term['count'] . ')</option>' . "\n";
                }
            }
            $setvalue = !empty($gmedia_category)? 'data-setvalue="' . implode(',', $gmedia_category) . '"' : '';
            ?>
            <label><?php _e('Choose Categories', 'grand-media'); ?></label>

            <div class="row">
                <div class="col-xs-8">
                    <select <?php echo $setvalue; ?> id="gmedia_category" name="filter_data[gmedia_category][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Any Category...', 'grand-media')); ?>">
                        <option value=""<?php echo empty($gmedia_category)? ' selected="selected"' : ''; ?>><?php _e('Any Category...', 'grand-media'); ?></option>
                        <?php echo $terms_items; ?>
                    </select>
                </div>
                <div class="col-xs-4">
                    <select name="filter_data[category__condition]" class="form-control input-sm">
                        <option <?php selected($category__condition, 'category__in'); ?> value="category__in"><?php _e('get categories', 'grand-media'); ?></option>
                        <option <?php selected($category__condition, 'category__not_in'); ?> value="category__not_in"><?php _e('exclude categories', 'grand-media'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <?php
            $term_type = 'gmedia_tag';
            $gm_terms  = $gmDB->get_terms($term_type, array('fields' => 'names_count'));

            $terms_items = '';
            if(count($gm_terms)) {
                foreach($gm_terms as $id => $_term) {
                    $selected = (in_array($id, $gmedia_tag))? ' selected="selected"' : '';
                    $terms_items .= '<option value="' . $id . '"' . $selected . '>' . esc_html($_term['name']) . ' (' . $_term['count'] . ')</option>' . "\n";
                }
            }
            $setvalue = !empty($gmedia_tag)? 'data-setvalue="' . implode(',', $gmedia_tag) . '"' : '';
            ?>
            <label><?php _e('Choose Tags', 'grand-media'); ?> </label>

            <div class="row">
                <div class="col-xs-8">
                    <select <?php echo $setvalue; ?> id="gmedia_tag" name="filter_data[gmedia_tag][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Any Tag...', 'grand-media')); ?>">
                        <option value=""<?php echo empty($gmedia_tag)? ' selected="selected"' : ''; ?>><?php _e('Any Tag...', 'grand-media'); ?></option>
                        <?php echo $terms_items; ?>
                    </select>
                </div>
                <div class="col-xs-4">
                    <select name="filter_data[tag__condition]" class="form-control input-sm">
                        <option <?php selected($tag__condition, 'tag__in'); ?> value="tag__in"><?php _e('get items with either tags', 'grand-media'); ?></option>
                        <option <?php selected($tag__condition, 'tag__and'); ?> value="tag__and"><?php _e('get items that have all listed tags', 'grand-media'); ?></option>
                        <option <?php selected($tag__condition, 'tag__not_in'); ?> value="tag__not_in"><?php _e('exclude items that have any of the listed tags', 'grand-media'); ?></option>
                    </select>
                </div>
            </div>
        </div>

    <?php } ?>
    <div class="form-group">
        <label><?php _e('Terms Relation', 'grand-media'); ?> </label>

        <div class="row">
            <div class="col-xs-4">
                <select name="gmedia_filter[terms_relation]" class="form-control input-sm">
                    <option <?php selected($filter_data['terms_relation'], ''); ?> value=""><?php _e('AND'); ?></option>
                    <option <?php selected($filter_data['terms_relation'], 'OR'); ?> value="OR"><?php _e('OR'); ?></option>
                </select>
            </div>
            <div class="col-xs-8">
                <p class="help-block"><?php _e('allows you to describe the relationship between the taxonomy queries', 'grand-media'); ?></p>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label><?php _e('Search', 'grand-media'); ?></label>

        <div class="row">
            <div class="col-xs-8">
                <input type="text" class="form-control input-sm" placeholder="<?php _e('Search string or terms separated by comma', 'grand-media'); ?>" value="<?php echo $filter_data['s']; ?>" name="gmedia_filter[s]">
            </div>
            <div class="col-xs-4">
                <div class="checkbox"><label><input type="checkbox" name="gmedia_filter[exact]" value="yes"<?php echo $filter_data['exact']? ' checked="checked"' : ''; ?> /> <?php _e('Search exactly string', 'grand-media'); ?></label></div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <div class="col-xs-8">
                <div class="pull-right"><a id="use_lib_selected" class="label label-primary" href="#libselected"><?php _e('Use selected in Library', 'grand-media'); ?></a></div>
                <label><?php _e('Gmedia IDs <small class="text-muted">separated by comma</small>', 'grand-media'); ?> </label>
                <?php $value = !empty($gmedia_id)? implode(',', wp_parse_id_list($gmedia_id)) : ''; ?>
                <textarea id="gmedia__ids" name="filter_data[gmedia_id]" rows="1" class="form-control input-sm" style="resize:vertical;" placeholder="<?php echo esc_attr(__('Gmedia IDs...', 'grand-media')); ?>"><?php echo $value; ?></textarea>
            </div>
            <div class="col-xs-4">
                <label>&nbsp;</label>
                <select name="filter_data[gmedia_id__condition]" class="form-control input-sm">
                    <option <?php selected($gmedia_id__condition, 'gmedia__in'); ?> value="gmedia__in"><?php _e('get gmedia IDs', 'grand-media'); ?></option>
                    <option <?php selected($gmedia_id__condition, 'gmedia__not_in'); ?> value="gmedia__not_in"><?php _e('exclude gmedia IDs', 'grand-media'); ?></option>
                </select>
            </div>
        </div>
        <p class="help-block"><?php _e('You can select items you want to add here right in Gmedia Library and then return here and click button "Use selected in Library"', 'grand-media'); ?></p>
    </div>
    <div class="form-group">
        <div class="row">
            <div class="col-xs-4">
                <label><?php _e('Mime Type', 'grand-media'); ?> </label>
                <select name="gmedia_filter[mime_type][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('All types...', 'grand-media')); ?>">
                    <option value=""><?php _e('All types...', 'grand-media'); ?></option>
                    <option <?php echo in_array('image', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="image"><?php _e('Image', 'grand-media'); ?></option>
                    <option <?php echo in_array('audio', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="audio"><?php _e('Audio', 'grand-media'); ?></option>
                    <option <?php echo in_array('video', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="video"><?php _e('Video', 'grand-media'); ?></option>
                    <option <?php echo in_array('text', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="text"><?php _e('Text', 'grand-media'); ?></option>
                    <option <?php echo in_array('application', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="application"><?php _e('Application', 'grand-media'); ?></option>
                </select>
            </div>
            <div class="col-xs-4">
                <label><?php _e('Authors', 'grand-media'); ?></label>
                <?php if(gm_user_can('show_others_media')) {
                    $user_ids = $gmCore->get_editable_user_ids();
                    if(!in_array($user_ID, $user_ids)) {
                        array_push($user_ids, $user_ID);
                    }
                    $filter_users = get_users(array('include' => $user_ids));
                    $users        = '';
                    if(count($filter_users)) {
                        foreach((array)$filter_users as $user) {
                            $user->ID  = (int)$user->ID;
                            $_selected = in_array($user->ID, $author_id)? ' selected="selected"' : '';
                            $users .= "<option value='$user->ID'$_selected>" . esc_html($user->display_name) . "</option>";
                        }
                    }
                    $setvalue = !empty($author_id)? 'data-setvalue="' . implode(',', $author_id) . '"' : '';
                    ?>
                    <select <?php echo $setvalue; ?> name="filter_data[author_id][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('All authors...', 'grand-media')); ?>">
                        <option value=""><?php _e('All authors...', 'grand-media'); ?></option>
                        <?php echo $users; ?>
                    </select>
                <?php } else { ?>
                    <input type="text" readonly="readonly" name="filter_data[author_id][]" class="gmedia-combobox form-control input-sm" value="<?php the_author_meta('display_name', $user_ID); ?>"/>
                    <input type="hidden" name="filter_data[author_id__condition]" value="author__in"/>
                <?php } ?>
            </div>
            <?php if(gm_user_can('show_others_media')) { ?>
                <div class="col-xs-4">
                    <label>&nbsp;</label>
                    <select name="filter_data[author_id__condition]" class="form-control input-sm">
                        <option <?php selected($author_id__condition, 'author__in'); ?> value="author__in"><?php _e('get authors', 'grand-media'); ?></option>
                        <option <?php selected($author_id__condition, 'author__not_in'); ?> value="author__not_in"><?php _e('exclude authors', 'grand-media'); ?></option>
                    </select>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="form-group">
        <div class="row">
            <div class="col-xs-4">
                <label><?php _e('Year', 'grand-media'); ?></label>
                <input type="text" class="form-control input-sm" placeholder="<?php _e('4 digit year e.g. 2011', 'grand-media'); ?>" value="<?php echo $filter_data['year']; ?>" name="gmedia_filter[year]">
            </div>
            <div class="col-xs-4">
                <label><?php _e('Month', 'grand-media'); ?></label>
                <input type="text" class="form-control input-sm" placeholder="<?php _e('from 1 to 12', 'grand-media'); ?>" value="<?php echo $filter_data['monthnum']; ?>" name="gmedia_filter[monthnum]">
            </div>
            <div class="col-xs-4">
                <label><?php _e('Day', 'grand-media'); ?></label>
                <input type="text" class="form-control input-sm" placeholder="<?php _e('from 1 to 31', 'grand-media'); ?>" value="<?php echo $filter_data['day']; ?>" name="gmedia_filter[day]">
            </div>
        </div>
    </div>
    <div class="form-group">
        <?php foreach($filter_data['meta_query'] as $i => $q) {
            if($i) {
                continue;
            }
            ?>
            <div class="row">
                <div class="col-xs-6 col-sm-3">
                    <label><?php _e('Custom Field Key', 'grand-media'); ?></label>
                    <input type="text" class="form-control input-sm" value="<?php echo $q['key']; ?>" name="gmedia_filter[meta_query][<?php echo $i; ?>][key]">
                    <span class="help-block"><?php _e('Display items with this field key', 'grand-media'); ?></span>
                </div>
                <div class="col-xs-6 col-sm-3">
                    <label><?php _e('Custom Field Value', 'grand-media'); ?></label>
                    <input type="text" class="form-control input-sm" value="<?php echo $q['value']; ?>" name="gmedia_filter[meta_query][<?php echo $i; ?>][value]">
                    <span class="help-block"><?php _e('Display items with this field value', 'grand-media'); ?></span>
                </div>
                <div class="col-xs-6 col-sm-3">
                    <label><?php _e('Compare Operator', 'grand-media'); ?></label>
                    <select class="form-control input-sm" name="gmedia_filter[meta_query][<?php echo $i; ?>][compare]">
                        <option value=""><?php _e('Choose..', 'grand-media'); ?></option>
                        <option <?php selected($q['compare'], '='); ?> value="=">= (<?php _e('Default', 'grand-media'); ?>)</option>
                        <option <?php selected($q['compare'], '!='); ?> value="!=">!=</option>
                        <option <?php selected($q['compare'], '>'); ?> value="&gt;">&gt;</option>
                        <option <?php selected($q['compare'], '>='); ?> value="&gt;=">&gt;=</option>
                        <option <?php selected($q['compare'], '<'); ?> value="&lt;">&lt;</option>
                        <option <?php selected($q['compare'], '<='); ?> value="&lt;=">&lt;=</option>
                        <option <?php selected($q['compare'], 'LIKE'); ?> value="LIKE">LIKE</option>
                        <option <?php selected($q['compare'], 'NOT LIKE'); ?> value="NOT LIKE">NOT LIKE</option>
                        <?php /* ?>
							<option <?php selected($q['compare'], 'IN'); ?> value="IN">IN</option>
							<option <?php selected($q['compare'], 'NOT IN'); ?> value="NOT IN">NOT IN</option>
							<option <?php selected($q['compare'], 'BETWEEN'); ?> value="BETWEEN">BETWEEN</option>
							<option <?php selected($q['compare'], 'NOT BETWEEN'); ?> value="NOT BETWEEN">NOT BETWEEN</option>
							<?php */ ?>
                        <option <?php selected($q['compare'], 'EXISTS'); ?> value="EXISTS">EXISTS</option>
                    </select>
                    <span class="help-block"><?php _e('Operator to test the field value', 'grand-media'); ?></span>
                </div>
                <div class="col-xs-6 col-sm-3">
                    <label><?php _e('Meta Type', 'grand-media'); ?></label>
                    <select class="form-control input-sm" name="gmedia_filter[meta_query][<?php echo $i; ?>][type]">
                        <option value=""><?php _e('Choose..', 'grand-media'); ?></option>
                        <option <?php selected($q['type'], 'NUMERIC'); ?> value="NUMERIC">NUMERIC</option>
                        <option <?php selected($q['type'], 'BINARY'); ?> value="BINARY">BINARY</option>
                        <option <?php selected($q['type'], 'DATE'); ?> value="DATE">DATE</option>
                        <option <?php selected($q['type'], 'CHAR'); ?> value="CHAR">CHAR (<?php _e('Default', 'grand-media'); ?>)</option>
                        <option <?php selected($q['type'], 'DATETIME'); ?> value="DATETIME">DATETIME</option>
                        <option <?php selected($q['type'], 'DECIMAL'); ?> value="DECIMAL">DECIMAL</option>
                        <option <?php selected($q['type'], 'SIGNED'); ?> value="SIGNED">SIGNED</option>
                        <option <?php selected($q['type'], 'TIME'); ?> value="TIME">TIME</option>
                        <option <?php selected($q['type'], 'UNSIGNED'); ?> value="UNSIGNED">UNSIGNED</option>
                    </select>
                    <span class="help-block"><?php _e('Custom field type', 'grand-media'); ?></span>
                </div>
            </div>
        <?php } ?>
    </div>
    <div class="form-group">
        <div class="row">
            <div class="col-xs-6 col-sm-3">
                <label><?php _e('Order', 'grand-media'); ?></label>
                <select class="form-control input-sm" name="gmedia_filter[order]">
                    <option value=""><?php _e('Choose..', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['order'], 'ASC'); ?> value="ASC"><?php _e('ASC', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['order'], 'DESC'); ?> value="DESC"><?php _e('DESC - Default', 'grand-media'); ?></option>
                </select>
                <span class="help-block"><?php _e('Ascending or Descending order', 'grand-media'); ?></span>
            </div>
            <div class="col-xs-6 col-sm-3">
                <label><?php _e('Order by', 'grand-media'); ?></label>
                <select class="form-control input-sm" name="gmedia_filter[orderby]">
                    <option value=""><?php _e('Choose..', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'none'); ?> value="none"><?php _e('None', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'rand'); ?> value="rand"><?php _e('Random', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'id'); ?> value="id"><?php _e('ID', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'title'); ?> value="title"><?php _e('Title', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'gmuid'); ?> value="gmuid"><?php _e('Filename', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'date'); ?> value="date"><?php _e('Date - Default', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'modified'); ?> value="modified"><?php _e('Modified Date', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'author'); ?> value="author"><?php _e('Author', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'gmedia__in'); ?> value="gmedia__in"><?php _e('Selected Order', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'meta_value'); ?> value="meta_value"><?php _e('Custom Field Value', 'grand-media'); ?></option>
                    <option <?php selected($filter_data['orderby'], 'meta_value_num'); ?> value="meta_value_num"><?php _e('Custom Field Value (Numeric)', 'grand-media'); ?></option>
                </select>
                <span class="help-block"><?php _e('Sort retrieved posts by', 'grand-media'); ?></span>
            </div>
            <div class="col-xs-6 col-sm-3">
                <label><?php _e('Limit', 'grand-media'); ?></label>
                <input type="text" class="form-control input-sm" value="<?php echo $filter_data['limit']; ?>" name="gmedia_filter[limit]" placeholder="<?php _e('leave empty for no limit', 'grand-media'); ?>">
                <span class="help-block"><?php _e('Limit number of gmedia items', 'grand-media'); ?></span>
            </div>
            <div class="col-xs-6 col-sm-3 text-right">
                <label style="display:block;">&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-sm" name="gmedia_filter_save"><?php _e('Save', 'grand-media'); ?></button>
            </div>
        </div>
    </div>
</form>
<script type="text/javascript">
    jQuery(function($) {
        <?php if(gm_user_can('terms')){ ?>
        $('.gmedia-combobox').each(function() {
            var select = $(this).selectize({
                create: false,
                hideSelected: true,
                allowEmptyOption: true
            });
            var val = $(this).data('setvalue');
            if(val) {
                val = val.toString().split(',');
                select[0].selectize.setValue(val);
            }
        });
        <?php } ?>

        $('#use_lib_selected').on('click', function() {
            var field = $('#gmedia__ids');
            var valData = field.val().split(',');
            var storedData = getStorage('gmuser_<?php echo $user_ID; ?>_');
            storedData = storedData.get('library').split(',');
            valData = $.grep(valData, function(e) {
                return e;
            });
            $.each(storedData, function(i, id) {
                if(!id) {
                    return true;
                }
                if($.inArray(id, valData) === -1) {
                    valData.push(id);
                }
            });
            field.val(valData.join(', '));
        });
    });

</script>

<?php if(gm_user_can('edit_others_media')) { ?>
    <div class="modal fade gmedia-modal" id="gallModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog"></div>
    </div>
<?php } ?>
