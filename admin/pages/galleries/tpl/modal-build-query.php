<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Modal for Build Query
 * @var $gm_album_terms
 * @var $gm_category_terms
 * @var $gm_tag_terms
 */
global $user_ID, $gmDB, $gmCore
?>
<div class="modal fade gmedia-modal" id="buildQuery" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" style="width:700px;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title"><?php _e('Query Parameters'); ?></h4>
			</div>
			<div class="modal-body">

				<?php
				$query_data = $gmedia_filter['query_data'];
				if(gm_user_can('terms')) { ?>
					<div class="form-group">
						<?php
						$term_type = 'gmedia_album';
						$args      = array();
						if(gm_user_can('edit_others_media')) {
							$args['global'] = '';
						} else {
							$args['global'] = array(0, $user_ID);
						}
						$gm_album_terms = $gmDB->get_terms($term_type, $args);

						$no_term = array(
							'term_id' => 0,
							'name'    => __('No Album', 'grand-media'),
						);
						if(count($gm_album_terms)) {
							foreach($gm_album_terms as &$_term) {
								unset($_term->description);
								unset($_term->taxonomy);
								$_term->by_author = $_term->global? sprintf(__('by %s', 'grand-media'), get_the_author_meta('display_name', $_term->global)) : '';
								/* ('publish' == $_term->status? '' : " [{$_term->status}]") . ' &nbsp; (' . $_term->count . ')';*/
							}
						}
						$gm_album_terms      = array_merge(array($no_term), $gm_album_terms);
						$query_gmedia_albums = array();
						if(!empty($query_data['album__in'])) {
							$query_gmedia_albums = wp_parse_id_list($query_data['album__in']);
						} elseif(!empty($query_data['album__not_in'])) {
							$query_gmedia_albums = wp_parse_id_list($query_data['album__not_in']);
						}
						?>
						<label><?php _e('Albums', 'grand-media'); ?> </label>

						<div class="row">
							<div class="col-xs-8">
								<input id="query_album__" name="album__in" data-include="album__in" data-exclude="album__not_in" class="form-control input-sm" value="<?php echo implode(',', $query_gmedia_albums) ?>" placeholder="<?php echo esc_attr(__('Any Album...', 'grand-media')); ?>"/>
							</div>
							<div class="col-xs-4">
								<div class="checkbox"><label><input class="query_switch" data-target="query_album__" type="checkbox"<?php echo (empty($query_data['album__in']) && !empty($query_data['album__not_in']))? ' checked="checked"' : ''; ?> /> <?php _e('Exclude selected Albums', 'grand-media'); ?></label></div>
							</div>
						</div>
					</div>

					<div class="form-group">
						<?php
						$term_type         = 'gmedia_category';
						$gm_category_terms = $gmDB->get_terms($term_type, array('fields' => 'names_count'));

						$no_term           = array(
							'term_id' => 0,
							'name'    => __('Uncategorized', 'grand-media'),
						);
						$gm_category_terms = array_merge(array($no_term), $gm_category_terms);
						?>
						<div class="row">
							<div class="col-xs-4">
								<label><?php _e('[IN] Categories', 'grand-media'); ?></label>
								<input name="category__in" class="form-control input-sm combobox_gmedia_category" value="<?php echo implode(',', wp_parse_id_list($query_data['category__in'])); ?>" placeholder="<?php echo esc_attr(__('Either of chosen Categories...', 'grand-media')); ?>"/>
							</div>
							<div class="col-xs-4">
								<label><?php _e('[AND] Categories', 'grand-media'); ?></label>
								<input name="category__and" class="form-control input-sm combobox_gmedia_category" value="<?php echo implode(',', wp_parse_id_list($query_data['category__and'])); ?>" placeholder="<?php echo esc_attr(__('Have all chosen Categories...', 'grand-media')); ?>"/>
							</div>
							<div class="col-xs-4">
								<label><?php _e('[NOT IN] Categories', 'grand-media'); ?></label>
								<input name="category__not_in" class="form-control input-sm combobox_gmedia_category" value="<?php echo implode(',', wp_parse_id_list($query_data['category__not_in'])); ?>" placeholder="<?php echo esc_attr(__('Exclude Categories...', 'grand-media')); ?>"/>
							</div>
						</div>
					</div>

					<div class="form-group">
						<?php
						$term_type    = 'gmedia_tag';
						$gm_tag_terms = $gmDB->get_terms($term_type, array('fields' => 'names_count'));
						?>
						<div class="row">
							<div class="col-xs-4">
								<label><?php _e('[IN] Tags', 'grand-media'); ?> </label>
								<input name="tag__in" class="form-control input-sm combobox_gmedia_tag" value="<?php echo implode(',', wp_parse_id_list($query_data['tag__in'])); ?>" placeholder="<?php echo esc_attr(__('Either of chosen Tags...', 'grand-media')); ?>"/>
							</div>
							<div class="col-xs-4">
								<label><?php _e('[AND] Tags', 'grand-media'); ?> </label>
								<input name="tag__and" class="form-control input-sm combobox_gmedia_tag" value="<?php echo implode(',', wp_parse_id_list($query_data['tag__and'])); ?>" placeholder="<?php echo esc_attr(__('Have all chosen Tags...', 'grand-media')); ?>"/>
							</div>
							<div class="col-xs-4">
								<label><?php _e('[NOT IN] Tags', 'grand-media'); ?> </label>
								<input name="tag__not_in" class="form-control input-sm combobox_gmedia_tag" value="<?php echo implode(',', wp_parse_id_list($query_data['tag__not_in'])); ?>" placeholder="<?php echo esc_attr(__('Exclude Tags...', 'grand-media')); ?>"/>
							</div>
						</div>
					</div>

				<?php } ?>
				<div class="form-group">
					<label><?php _e('Terms Relation', 'grand-media'); ?> </label>

					<div class="row">
						<div class="col-xs-4">
							<select name="terms_relation" class="form-control input-sm">
								<option <?php selected($query_data['terms_relation'], ''); ?> value=""><?php _e('Default (AND)'); ?></option>
								<option <?php selected($query_data['terms_relation'], 'AND'); ?> value=""><?php _e('AND'); ?></option>
								<option <?php selected($query_data['terms_relation'], 'OR'); ?> value="OR"><?php _e('OR'); ?></option>
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
							<input type="text" class="form-control input-sm" placeholder="<?php _e('Search string or terms separated by comma', 'grand-media'); ?>" value="<?php echo $query_data['s']; ?>" name="s">
						</div>
						<div class="col-xs-4">
							<div class="checkbox"><label><input type="checkbox" name="exact" value="yes"<?php echo $query_data['exact']? ' checked="checked"' : ''; ?> /> <?php _e('Search exactly string', 'grand-media'); ?></label></div>
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="row">
						<div class="col-xs-8">
							<div class="pull-right"><a id="use_lib_selected" class="label label-primary" href="#libselected"><?php _e('Use selected in Library', 'grand-media'); ?></a></div>
							<label><?php _e('Gmedia IDs <small class="text-muted">separated by comma</small>', 'grand-media'); ?> </label>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-8">
							<?php $query_gmedia_items = array();
							if(!empty($query_data['gmedia__in'])) {
								$query_gmedia_items = $query_data['gmedia__in'];
							} elseif(!empty($query_data['gmedia__not_in'])) {
								$query_gmedia_items = $query_data['gmedia__not_in'];
							}
							?>
							<textarea id="query_gmedia__" name="gmedia__in" data-include="gmedia__in" data-exclude="gmedia__not_in" rows="1" class="form-control input-sm" style="resize:vertical;" placeholder="<?php echo esc_attr(__('Gmedia IDs...', 'grand-media')); ?>"><?php echo implode(',', wp_parse_id_list($query_gmedia_items)); ?></textarea>
						</div>
						<div class="col-xs-4">
							<div class="checkbox"><label><input class="query_switch" data-target="query_gmedia__" type="checkbox"<?php echo (empty($query_data['gmedia__in']) && !empty($query_data['gmedia__not_in']))? ' checked="checked"' : ''; ?> /> <?php _e('Exclude selected Items', 'grand-media'); ?></label></div>
						</div>
					</div>
					<p class="help-block"><?php _e('You can select items you want to add here right in Gmedia Library and then return here and click button "Use selected in Library"', 'grand-media'); ?></p>
				</div>
				<div class="form-group">
					<div class="row">
						<div class="col-xs-4">
							<label><?php _e('Mime Type', 'grand-media'); ?> </label>
							<?php
							$mime_types = array(
								array('value' => 'image', 'text' => 'Image'),
								array('value' => 'audio', 'text' => 'Audio'),
								array('value' => 'video', 'text' => 'Video'),
								array('value' => 'text', 'text' => 'Text'),
								array('value' => 'application', 'text' => 'Application'),
							);
							?>
							<input name="mime_type" class="form-control input-sm gmedia-combobox" data-options='<?php echo json_encode($mime_types); ?>' value="<?php echo implode(',', $query_data['mime_type']); ?>" placeholder="<?php esc_attr_e(__('All types...', 'grand-media')); ?>"/>
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
								$_users = array();
								if(count($filter_users)) {
									foreach((array)$filter_users as $user) {
										$user->ID  = (int)$user->ID;
										$_selected = in_array($user->ID, $query_data['author__in'])? ' selected="selected"' : '';
										$users .= "<option value='{$user->ID}'{$_selected}>" . esc_html($user->display_name) . "</option>";
										$_users[] = array('value' => $user->ID, 'text' => esc_html($user->display_name));
									}
								}
								$query_authors = array();
								if(!empty($query_data['author__in'])) {
									$query_authors = $query_data['author__in'];
								} elseif(!empty($query_data['author__not_in'])) {
									$query_authors = $query_data['author__not_in'];
								}
								?>
								<input id="query_author__" name="author__in" data-include="author__in" data-exclude="author__not_in" class="form-control input-sm gmedia-combobox" data-options='<?php echo str_replace("'", "\'", json_encode($_users)); ?>' value="<?php echo implode(',', wp_parse_id_list($query_authors)); ?>" placeholder="<?php esc_attr_e(__('All authors...', 'grand-media')); ?>"/>
							<?php } else { ?>
								<input type="text" readonly="readonly" name="author__in" class="form-control input-sm" value="<?php the_author_meta('display_name', $user_ID); ?>"/>
							<?php } ?>
						</div>
						<?php if(gm_user_can('show_others_media')) { ?>
							<div class="col-xs-4">
								<label>&nbsp;</label>
								<div class="checkbox"><label><input class="query_switch" data-target="query_author__" type="checkbox"<?php echo (empty($query_data['author__in']) && !empty($query_data['author__not_in']))? ' checked="checked"' : ''; ?> /> <?php _e('Exclude Authors', 'grand-media'); ?></label></div>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="form-group">
					<div class="row">
						<div class="col-xs-4">
							<label><?php _e('Year', 'grand-media'); ?></label>
							<input type="text" class="form-control input-sm" placeholder="<?php _e('4 digit year e.g. 2011', 'grand-media'); ?>" value="<?php echo $query_data['year']; ?>" name="year">
						</div>
						<div class="col-xs-4">
							<label><?php _e('Month', 'grand-media'); ?></label>
							<input type="text" class="form-control input-sm" placeholder="<?php _e('from 1 to 12', 'grand-media'); ?>" value="<?php echo $query_data['monthnum']; ?>" name="monthnum">
						</div>
						<div class="col-xs-4">
							<label><?php _e('Day', 'grand-media'); ?></label>
							<input type="text" class="form-control input-sm" placeholder="<?php _e('from 1 to 31', 'grand-media'); ?>" value="<?php echo $query_data['day']; ?>" name="day">
						</div>
					</div>
				</div>
				<div class="form-group">
					<?php foreach($query_data['meta_query'] as $i => $q) {
						if($i) {
							continue;
						}
						?>
						<div class="row">
							<div class="col-xs-6 col-sm-3">
								<label><?php _e('Custom Field Key', 'grand-media'); ?></label>
								<input type="text" class="form-control input-sm" value="<?php echo $q['key']; ?>" name="meta_query[<?php echo $i; ?>][key]">
								<span class="help-block"><?php _e('Display items with this field key', 'grand-media'); ?></span>
							</div>
							<div class="col-xs-6 col-sm-3">
								<label><?php _e('Custom Field Value', 'grand-media'); ?></label>
								<input type="text" class="form-control input-sm" value="<?php echo $q['value']; ?>" name="meta_query[<?php echo $i; ?>][value]">
								<span class="help-block"><?php _e('Display items with this field value', 'grand-media'); ?></span>
							</div>
							<div class="col-xs-6 col-sm-3">
								<label><?php _e('Compare Operator', 'grand-media'); ?></label>
								<select class="form-control input-sm" name="meta_query[<?php echo $i; ?>][compare]">
									<option <?php selected($q['compare'], ''); ?> value=""><?php _e('Default', 'grand-media'); ?> (=)</option>
									<option <?php selected($q['compare'], '='); ?> value="">=</option>
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
								<select class="form-control input-sm" name="meta_query[<?php echo $i; ?>][type]">
									<option <?php selected($q['type'], ''); ?> value=""><?php _e('Default', 'grand-media'); ?> (CHAR)</option>
									<option <?php selected($q['type'], 'CHAR'); ?> value="">CHAR</option>
									<option <?php selected($q['type'], 'NUMERIC'); ?> value="NUMERIC">NUMERIC</option>
									<option <?php selected($q['type'], 'DECIMAL'); ?> value="DECIMAL">DECIMAL</option>
									<option <?php selected($q['type'], 'DATE'); ?> value="DATE">DATE</option>
									<option <?php selected($q['type'], 'DATETIME'); ?> value="DATETIME">DATETIME</option>
									<option <?php selected($q['type'], 'TIME'); ?> value="TIME">TIME</option>
									<option <?php selected($q['type'], 'BINARY'); ?> value="BINARY">BINARY</option>
									<option <?php selected($q['type'], 'SIGNED'); ?> value="SIGNED">SIGNED</option>
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
							<select class="form-control input-sm" name="order">
								<option <?php selected($query_data['order'], ''); ?> value=""><?php _e('Default (DESC)', 'grand-media'); ?></option>
								<option <?php selected($query_data['order'], 'DESC'); ?> value=""><?php _e('DESC', 'grand-media'); ?></option>
								<option <?php selected($query_data['order'], 'ASC'); ?> value="ASC"><?php _e('ASC', 'grand-media'); ?></option>
							</select>
							<span class="help-block"><?php _e('Ascending or Descending order', 'grand-media'); ?></span>
						</div>
						<div class="col-xs-6 col-sm-3">
							<label><?php _e('Order by', 'grand-media'); ?></label>
							<select class="form-control input-sm" name="orderby">
								<option <?php selected($query_data['orderby'], ''); ?> value=""><?php _e('Default (ID)', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'id'); ?> value=""><?php _e('ID', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'title'); ?> value="title"><?php _e('Title', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'gmuid'); ?> value="gmuid"><?php _e('Filename', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'date'); ?> value="date"><?php _e('Date', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'modified'); ?> value="modified"><?php _e('Modified Date', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'author'); ?> value="author"><?php _e('Author', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'gmedia__in'); ?> value="gmedia__in"><?php _e('Selected Order', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'meta_value'); ?> value="meta_value"><?php _e('Custom Field Value', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'meta_value_num'); ?> value="meta_value_num"><?php _e('Custom Field Value (Numeric)', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'rand'); ?> value="rand"><?php _e('Random', 'grand-media'); ?></option>
								<option <?php selected($query_data['orderby'], 'none'); ?> value="none"><?php _e('None', 'grand-media'); ?></option>
							</select>
							<span class="help-block"><?php _e('Sort retrieved posts by', 'grand-media'); ?></span>
						</div>
						<div class="col-xs-6 col-sm-3">
							<label><?php _e('Limit', 'grand-media'); ?></label>
							<input type="text" class="form-control input-sm" value="<?php echo $query_data['limit']; ?>" name="limit" placeholder="<?php _e('leave empty for no limit', 'grand-media'); ?>">
							<span class="help-block"><?php _e('Limit number of gmedia items', 'grand-media'); ?></span>
						</div>
					</div>
				</div>
			</div>
			<script type="text/javascript">
				jQuery(function($) {
					<?php if(gm_user_can('terms')){ ?>

					var gmedia_albums = <?php echo json_encode(array_values($gm_album_terms)); ?>;
					var gmedia_categories = <?php echo json_encode(array_values($gm_category_terms)); ?>;
					var gmedia_tags = <?php echo json_encode(array_values($gm_tag_terms)); ?>;
					$('#query_album__').selectize({
						plugins: ['drag_drop'],
						create: false,
						options: gmedia_albums,
						hideSelected: true,
						allowEmptyOption: true,
						valueField: 'term_id',
						searchField: ['name'],
						//labelField: 'name',
						render: {
							item: function(item, escape) {
								var count = '';
								var status = '';
								var author = '';
								if(item.term_id) {
									count = '(' + escape(item.count) + ')';
									status = (typeof item.status != 'undefined' && ('publish' != item.status))? ' [' + item.status + '] ' : '';
									author = ' ' + item.by_author;
								}
								return '<div>' + escape(item.name) + ' <small>' + count + status + author + '</small></div>';
							},
							option: function(item, escape) {
								var count = '';
								var status = '';
								var author = '';
								if(item.term_id) {
									count = '(' + escape(item.count) + ')';
									status = (typeof item.status != 'undefined' && ('publish' != item.status))? ' [' + item.status + '] ' : '';
									author = ' ' + item.by_author;
								}
								return '<div>' + escape(item.name) + ' <small>' + count + status + author + '</small></div>';
							}
						}

					});
					var cats = $('.combobox_gmedia_category').selectize({
						create: false,
						options: gmedia_categories,
						preload: true,
						hideSelected: true,
						allowEmptyOption: true,
						valueField: 'term_id',
						searchField: ['name'],
						//labelField: 'name',
						render: {
							item: function(item, escape) {
								var count = '';
								if(item.term_id) {
									count = ' <small>(' + escape(item.count) + ')</small>';
								}
								return '<div>' + escape(item.name) + count + '</div>';
							},
							option: function(item, escape) {
								if(('category__and' == this.$input[0].name) && !item.term_id) {
									return '';
								}
								var count = '';
								if(item.term_id) {
									count = ' <small>(' + escape(item.count) + ')</small>';
								}
								return '<div>' + escape(item.name) + count + '</div>';
							}
						}

					}).on('change', function() {
						var allSelected = [];
						jQuery.each(cats, function(i, e) {
							allSelected = jQuery.merge(allSelected, e.selectize.items);
						});

						jQuery.each(cats, function(i, e) {
							var orig_items = e.selectize.items;
							e.selectize.items = allSelected;
							e.selectize.currentResults = e.selectize.search();
							e.selectize.refreshOptions(false);
							e.selectize.items = orig_items;

						});
					});

					var tags = $('.combobox_gmedia_tag').selectize({
						create: false,
						options: gmedia_tags,
						hideSelected: true,
						allowEmptyOption: true,
						valueField: 'term_id',
						searchField: ['name'],
						render: {
							item: function(item, escape) {
								return '<div>' + escape(item.name) + ' <small>(' + escape(item.count) + ')</small></div>';
							},
							option: function(item, escape) {
								return '<div>' + escape(item.name) + ' <small>(' + escape(item.count) + ')</small></div>';
							}
						}

					}).on('change', function() {
						var allSelected = [];
						jQuery.each(tags, function(i, e) {
							allSelected = jQuery.merge(allSelected, e.selectize.items);
						});

						jQuery.each(tags, function(i, e) {
							var orig_items = e.selectize.items;
							e.selectize.items = allSelected;
							e.selectize.currentResults = e.selectize.search();
							e.selectize.refreshOptions(false);
							e.selectize.items = orig_items;

						});
					});
					;
					<?php } ?>

					$('.gmedia-combobox').each(function() {
						var select = $(this).selectize({
							create: false,
							hideSelected: true,
							options: $(this).data('options')
						});
					});

					$('.query_switch').on('click', function() {
						var el = $('#'+$(this).attr('data-target'));
						if($(this).is(':checked')) {
							el.attr('name', el.attr('data-exclude'));
						} else {
							el.attr('name', el.attr('data-include'));
						}
					});
					$('#use_lib_selected').on('click', function() {
						var field = $('#query_gmedia__');
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
						field.val(valData.join(','));
					});
				});

			</script>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary buildquerysubmit"><?php _e('Build Query', 'grand-media'); ?></button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'grand-media'); ?></button>
			</div>
		</div>
	</div>
</div>
