<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Modal for Build Query
 *
 * @var $gm_album_terms
 * @var $gm_category_terms
 * @var $gm_tag_terms
 * @var $gmedia_filter
 */
global $user_ID, $gmDB, $gmCore
?>
<div class="modal fade gmedia-modal" id="buildQuery" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog mw-100" style="width:700px;">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><?php esc_html_e( 'Query Parameters' ); ?></h4>
				<div class="float-end" style="margin-top:-4px;">
					<button type="button" class="btn btn-secondary buildqueryreset"><?php esc_html_e( 'Reset', 'grand-media' ); ?></button>
					<button type="button" class="btn btn-primary buildquerysubmit"><?php esc_html_e( 'Build Query', 'grand-media' ); ?></button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'grand-media' ); ?></button>
				</div>
			</div>
			<div class="modal-body small">

				<?php
				$query_data = $gmedia_filter['query_data'];
				if ( gm_user_can( 'terms' ) ) {
					?>
					<div class="form-group">
						<?php
						$term_type = 'gmedia_album';
						$args      = array();
						if ( gm_user_can( 'edit_others_media' ) ) {
							$args['global'] = '';
						} else {
							$args['global'] = array( 0, $user_ID );
						}
						$gm_album_terms = $gmDB->get_terms( $term_type, $args );

						$no_term = array(
							'term_id' => 0,
							'name'    => __( 'No Album', 'grand-media' ),
						);
						if ( count( $gm_album_terms ) ) {
							foreach ( $gm_album_terms as &$_term ) {
								unset( $_term->description );
								unset( $_term->taxonomy );
								// translators: author name.
								$_term->by_author = $_term->global ? sprintf( esc_html__( 'by %s', 'grand-media' ), esc_html( get_the_author_meta( 'display_name', $_term->global ) ) ) : '';
								/* ('publish' === $_term->status? '' : " [{$_term->status}]") . ' &nbsp; (' . $_term->count . ')';*/
							}
						}
						$gm_album_terms      = array_merge( array( $no_term ), $gm_album_terms );
						$query_gmedia_albums = array();
						$exclude_albums      = false;
						if ( ! empty( $query_data['album__in'] ) || ( '0' === $query_data['album__in'] ) ) {
							$query_gmedia_albums = wp_parse_id_list( $query_data['album__in'] );
						} elseif ( ! empty( $query_data['album__not_in'] ) || ( '0' === $query_data['album__not_in'] ) ) {
							$query_gmedia_albums = wp_parse_id_list( $query_data['album__not_in'] );
							$exclude_albums      = true;
						}
						?>
						<label><?php esc_html_e( 'Albums', 'grand-media' ); ?> </label>

						<div class="row">
							<div class="col-sm-8">
								<input id="query_album__" name="album__in" data-include="album__in" data-exclude="album__not_in" class="form-control gm-selectize input-xs" value="<?php echo esc_attr( implode( ',', $query_gmedia_albums ) ); ?>" placeholder="<?php esc_attr_e( 'Any Album...', 'grand-media' ); ?>"/>
							</div>
							<div class="col-sm-4">
								<div class="checkbox"><label><input class="query_switch" data-target="query_album__" type="checkbox"<?php echo $exclude_albums ? ' checked="checked"' : ''; ?> /> <?php esc_html_e( 'Exclude selected Albums', 'grand-media' ); ?></label></div>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-8">
								<p class="help-block"><?php esc_html_e( 'To show all albums just select `No Album` in dropdown above then tick `Exclude selected Albums`, so it exclude all images without Album and show other images.', 'grand-media' ); ?></p>
							</div>
							<div class="col-sm-4">
								<label><?php esc_html_e( 'Albums Order', 'grand-media' ); ?> </label>
								<select name="albums_order" class="form-control input-xs">
									<option <?php selected( $query_data['albums_order'], '' ); ?> value=""><?php esc_html_e( 'No Order' ); ?></option>
									<option <?php selected( $query_data['albums_order'], 'id' ); ?> value="id"><?php esc_html_e( 'By ID (ASC)' ); ?></option>
									<option <?php selected( $query_data['albums_order'], 'id_desc' ); ?> value="desc"><?php esc_html_e( 'By ID (DESC)' ); ?></option>
									<option <?php selected( $query_data['albums_order'], 'name' ); ?> value="name"><?php esc_html_e( 'By Name (ASC)' ); ?></option>
									<option <?php selected( $query_data['albums_order'], 'name_desc' ); ?> value="name_desc"><?php esc_html_e( 'By Name (DESC)' ); ?></option>
									<option <?php selected( $query_data['albums_order'], 'date' ); ?> value="date"><?php esc_html_e( 'By Date (ASC)' ); ?></option>
									<option <?php selected( $query_data['albums_order'], 'date_desc' ); ?> value="date_desc"><?php esc_html_e( 'By Date (DESC)' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<div class="form-group">
						<?php
						$term_type         = 'gmedia_category';
						$gm_category_terms = $gmDB->get_terms( $term_type, array( 'fields' => 'names_count' ) );

						$no_term           = array(
							'term_id' => 0,
							'name'    => __( 'Uncategorized', 'grand-media' ),
						);
						$gm_category_terms = array_merge( array( $no_term ), $gm_category_terms );
						?>
						<div class="row">
							<div class="col-sm-4">
								<label><?php esc_html_e( '[IN] Categories', 'grand-media' ); ?></label>
								<input type="text" name="category__in" class="form-control input-xs gm_cat_in gm-selectize combobox_gmedia_category" value="<?php echo esc_attr( implode( ',', wp_parse_id_list( $query_data['category__in'] ) ) ); ?>" placeholder="<?php esc_attr_e( 'Either of chosen Categories...', 'grand-media' ); ?>"/>
							</div>
							<div class="col-sm-4">
								<label><?php esc_html_e( '[AND] Categories', 'grand-media' ); ?></label>
								<input type="text" name="category__and" class="form-control input-xs gm_cat_and gm-selectize combobox_gmedia_category" value="<?php echo esc_attr( implode( ',', wp_parse_id_list( $query_data['category__and'] ) ) ); ?>" placeholder="<?php esc_attr_e( 'Have all chosen Categories...', 'grand-media' ); ?>"/>
							</div>
							<div class="col-sm-4">
								<label><?php esc_html_e( '[NOT IN] Categories', 'grand-media' ); ?></label>
								<input type="text" name="category__not_in" class="form-control input-xs gm_cat_not_in gm-selectize combobox_gmedia_category" value="<?php echo esc_attr( implode( ',', wp_parse_id_list( $query_data['category__not_in'] ) ) ); ?>" placeholder="<?php esc_attr_e( 'Exclude Categories...', 'grand-media' ); ?>"/>
							</div>
						</div>
					</div>

					<div class="form-group">
						<?php
						$term_type    = 'gmedia_tag';
						$gm_tag_terms = $gmDB->get_terms( $term_type, array( 'fields' => 'names_count' ) );
						?>
						<div class="row">
							<div class="col-sm-4">
								<label><?php esc_html_e( '[IN] Tags', 'grand-media' ); ?> </label>
								<input type="text" name="tag__in" class="form-control input-xs gm-selectize combobox_gmedia_tag" value="<?php echo esc_attr( implode( ',', wp_parse_id_list( $query_data['tag__in'] ) ) ); ?>" placeholder="<?php esc_attr_e( 'Either of chosen Tags...', 'grand-media' ); ?>"/>
							</div>
							<div class="col-sm-4">
								<label><?php esc_html_e( '[AND] Tags', 'grand-media' ); ?> </label>
								<input type="text" name="tag__and" class="form-control input-xs gm-selectize combobox_gmedia_tag" value="<?php echo esc_attr( implode( ',', wp_parse_id_list( $query_data['tag__and'] ) ) ); ?>" placeholder="<?php esc_attr_e( 'Have all chosen Tags...', 'grand-media' ); ?>"/>
							</div>
							<div class="col-sm-4">
								<label><?php esc_html_e( '[NOT IN] Tags', 'grand-media' ); ?> </label>
								<input type="text" name="tag__not_in" class="form-control input-xs gm-selectize combobox_gmedia_tag" value="<?php echo esc_attr( implode( ',', wp_parse_id_list( $query_data['tag__not_in'] ) ) ); ?>" placeholder="<?php esc_attr_e( 'Exclude Tags...', 'grand-media' ); ?>"/>
							</div>
						</div>
					</div>

				<?php } ?>
				<div class="form-group">
					<label><?php esc_html_e( 'Terms Relation', 'grand-media' ); ?> </label>

					<div class="row">
						<div class="col-sm-4">
							<select name="terms_relation" class="form-control input-xs">
								<option <?php selected( $query_data['terms_relation'], '' ); ?> value=""><?php esc_html_e( 'Default (OR)' ); ?></option>
								<option <?php selected( $query_data['terms_relation'], 'AND' ); ?> value="AND"><?php esc_html_e( 'AND' ); ?></option>
								<option <?php selected( $query_data['terms_relation'], 'OR' ); ?> value="OR"><?php esc_html_e( 'OR' ); ?></option>
							</select>
						</div>
						<div class="col-sm-8">
							<p class="help-block"><?php esc_html_e( 'allows you to describe the relationship between the taxonomy queries', 'grand-media' ); ?></p>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label><?php esc_html_e( 'Search', 'grand-media' ); ?></label>

					<div class="row">
						<div class="col-sm-8">
							<input type="text" class="form-control input-xs" placeholder="<?php esc_attr_e( 'Search string or terms separated by comma', 'grand-media' ); ?>" value="<?php echo esc_attr( $query_data['s'] ); ?>" name="s">
						</div>
						<div class="col-sm-4">
							<div class="checkbox"><label><input type="checkbox" name="exact" value="yes"<?php echo $query_data['exact'] ? ' checked="checked"' : ''; ?> /> <?php esc_html_e( 'Search exactly string', 'grand-media' ); ?></label></div>
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="row">
						<div class="col-sm-8">
							<div class="float-end">
								<a
									id="_use_lib_selected"
									title="<?php esc_attr_e( 'Select Media', 'grand-media' ); ?>"
									href="<?php echo esc_url( $gmCore->get_admin_url( array( 'page' => 'GrandMedia', 'mode' => 'select_multiple', 'gmediablank' => 'library' ), array(), true ) ); ?>"
									class="label label-primary preview-modal"
									data-bs-toggle="modal"
									data-bs-target="#previewModal"
									data-width="1200"
									data-height="500"
									data-cls="select_gmedia"
								>
									<?php esc_html_e( 'Select in Library', 'grand-media' ); ?>
								</a>
							</div>
							<label><?php echo wp_kses_post( __( 'Gmedia IDs <small class="text-muted">separated by comma</small>', 'grand-media' ) ); ?> </label>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-8">
							<?php
							$query_gmedia_items = array();
							if ( ! empty( $query_data['gmedia__in'] ) ) {
								$query_gmedia_items = $query_data['gmedia__in'];
							} elseif ( ! empty( $query_data['gmedia__not_in'] ) ) {
								$query_gmedia_items = $query_data['gmedia__not_in'];
							}
							?>
							<textarea id="query_gmedia__" name="gmedia__in" data-include="gmedia__in" data-exclude="gmedia__not_in" rows="1" class="form-control input-xs" style="resize:vertical;" placeholder="<?php esc_attr_e( 'Gmedia IDs...', 'grand-media' ); ?>"><?php echo esc_textarea( implode( ',', wp_parse_id_list( $query_gmedia_items ) ) ); ?></textarea>
						</div>
						<div class="col-sm-4">
							<div class="checkbox">
								<label><input class="query_switch" data-target="query_gmedia__" type="checkbox"<?php echo ( empty( $query_data['gmedia__in'] ) && ! empty( $query_data['gmedia__not_in'] ) ) ? ' checked="checked"' : ''; ?> /> <?php esc_html_e( 'Exclude selected Items', 'grand-media' ); ?>
								</label></div>
						</div>
					</div>
					<p class="help-block"><?php esc_html_e( 'You can select items you want to add here right in Gmedia Library and then return here and click button "Use selected in Library"', 'grand-media' ); ?></p>
				</div>
				<div class="form-group">
					<div class="row">
						<div class="col-sm-4">
							<label><?php esc_html_e( 'Mime Type', 'grand-media' ); ?> </label>
							<?php
							$mime_types    = array(
								array( 'value' => 'image', 'text' => 'Image' ),
								array( 'value' => 'audio', 'text' => 'Audio' ),
								array( 'value' => 'video', 'text' => 'Video' ),
								array( 'value' => 'text', 'text' => 'Text' ),
								array( 'value' => 'application', 'text' => 'Application' ),
							);
							$mime_type_val = is_array( $query_data['mime_type'] ) ? implode( ',', $query_data['mime_type'] ) : str_replace( ', ', ',', $query_data['mime_type'] );
							?>
							<input type="text" name="mime_type" class="form-control input-xs gm-selectize gmedia-combobox" data-options='<?php echo wp_json_encode( $mime_types ); ?>' value="<?php echo esc_attr( $mime_type_val ); ?>" placeholder="<?php esc_attr_e( 'All types...', 'grand-media' ); ?>"/>
						</div>
						<div class="col-sm-4">
							<label><?php esc_html_e( 'Authors', 'grand-media' ); ?></label>
							<?php
							if ( gm_user_can( 'show_others_media' ) ) {
								$user_ids = $gmCore->get_editable_user_ids();
								if ( ! in_array( $user_ID, $user_ids, true ) ) {
									$user_ids[] = $user_ID;
								}
								$filter_users = get_users( array( 'include' => $user_ids ) );
								$users        = '';
								$_users       = array();
								if ( count( $filter_users ) ) {
									$author__in = wp_parse_id_list( $query_data['author__in'] );
									foreach ( (array) $filter_users as $user ) {
										$user->ID  = (int) $user->ID;
										$_selected = in_array( $user->ID, $author__in, true ) ? ' selected="selected"' : '';
										$users     .= '<option value="' . intval( $user->ID ) . '" ' . $_selected . '>' . esc_html( $user->display_name ) . '</option>';
										$_users[]  = array( 'value' => $user->ID, 'text' => esc_html( $user->display_name ) );
									}
								}
								$query_authors = array();
								if ( ! empty( $query_data['author__in'] ) ) {
									$query_authors = $query_data['author__in'];
								} elseif ( ! empty( $query_data['author__not_in'] ) ) {
									$query_authors = $query_data['author__not_in'];
								}
								?>
								<input id="query_author__" name="author__in" data-include="author__in" data-exclude="author__not_in" class="form-control input-xs gm-selectize gmedia-combobox" data-options='<?php echo esc_attr( str_replace( "'", "\'", wp_json_encode( $_users ) ) ); ?>' value="<?php echo esc_attr( implode( ',', wp_parse_id_list( $query_authors ) ) ); ?>" placeholder="<?php esc_attr_e( 'All authors...', 'grand-media' ); ?>"/>
							<?php } else { ?>
								<input type="text" readonly="readonly" name="author__in" class="form-control input-xs" value="<?php the_author_meta( 'display_name', $user_ID ); ?>"/>
							<?php } ?>
						</div>
						<?php if ( gm_user_can( 'show_others_media' ) ) { ?>
							<div class="col-sm-4">
								<label>&nbsp;</label>
								<div class="checkbox">
									<label><input class="query_switch" data-target="query_author__" type="checkbox"<?php echo ( empty( $query_data['author__in'] ) && ! empty( $query_data['author__not_in'] ) ) ? ' checked="checked"' : ''; ?> /> <?php esc_html_e( 'Exclude Authors', 'grand-media' ); ?></label>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="form-group">
					<div class="row">
						<div class="col-sm-4">
							<label><?php esc_html_e( 'Year', 'grand-media' ); ?></label>
							<input type="text" class="form-control input-xs" placeholder="<?php esc_attr_e( '4 digit year e.g. 2011', 'grand-media' ); ?>" value="<?php echo esc_attr( $query_data['year'] ); ?>" name="year">
						</div>
						<div class="col-sm-4">
							<label><?php esc_html_e( 'Month', 'grand-media' ); ?></label>
							<input type="text" class="form-control input-xs" placeholder="<?php esc_attr_e( 'from 1 to 12', 'grand-media' ); ?>" value="<?php echo esc_attr( $query_data['monthnum'] ); ?>" name="monthnum">
						</div>
						<div class="col-sm-4">
							<label><?php esc_html_e( 'Day', 'grand-media' ); ?></label>
							<input type="text" class="form-control input-xs" placeholder="<?php esc_attr_e( 'from 1 to 31', 'grand-media' ); ?>" value="<?php echo esc_attr( $query_data['day'] ); ?>" name="day">
						</div>
					</div>
				</div>
				<div class="form-group">
					<?php
					foreach ( $query_data['meta_query'] as $i => $q ) {
						if ( $i ) {
							continue;
						}
						?>
						<div class="row">
							<div class="col-sm-6 col-md-3">
								<label><?php esc_html_e( 'Custom Field Key', 'grand-media' ); ?></label>
								<input type="text" class="form-control input-xs" value="<?php echo esc_attr( $q['key'] ); ?>" name="meta_query[<?php echo (int) $i; ?>][key]">
								<span class="help-block small"><?php esc_html_e( 'Display items with this field key', 'grand-media' ); ?></span>
							</div>
							<div class="col-sm-6 col-md-3">
								<label><?php esc_html_e( 'Custom Field Value', 'grand-media' ); ?></label>
								<input type="text" class="form-control input-xs" value="<?php echo esc_attr( $q['value'] ); ?>" name="meta_query[<?php echo (int) $i; ?>][value]">
								<span class="help-block small"><?php esc_html_e( 'Display items with this field value', 'grand-media' ); ?></span>
							</div>
							<div class="col-sm-6 col-md-3">
								<label><?php esc_html_e( 'Compare Operator', 'grand-media' ); ?></label>
								<select class="form-control input-xs" name="meta_query[<?php echo (int) $i; ?>][compare]">
									<option <?php selected( $q['compare'], '' ); ?> value=""><?php esc_html_e( 'Default', 'grand-media' ); ?> (=)</option>
									<option <?php selected( $q['compare'], '=' ); ?> value="=">=</option>
									<option <?php selected( $q['compare'], '!=' ); ?> value="!=">!=</option>
									<option <?php selected( $q['compare'], '>' ); ?> value="&gt;">&gt;</option>
									<option <?php selected( $q['compare'], '>=' ); ?> value="&gt;=">&gt;=</option>
									<option <?php selected( $q['compare'], '<' ); ?> value="&lt;">&lt;</option>
									<option <?php selected( $q['compare'], '<=' ); ?> value="&lt;=">&lt;=</option>
									<option <?php selected( $q['compare'], 'LIKE' ); ?> value="LIKE">LIKE</option>
									<option <?php selected( $q['compare'], 'NOT LIKE' ); ?> value="NOT LIKE">NOT LIKE</option>
									<?php
									/*
									 ?>
									<option <?php selected($q['compare'], 'IN'); ?> value="IN">IN</option>
									<option <?php selected($q['compare'], 'NOT IN'); ?> value="NOT IN">NOT IN</option>
									<option <?php selected($q['compare'], 'BETWEEN'); ?> value="BETWEEN">BETWEEN</option>
									<option <?php selected($q['compare'], 'NOT BETWEEN'); ?> value="NOT BETWEEN">NOT BETWEEN</option>
									<?php
									*/
									?>
									<option <?php selected( $q['compare'], 'EXISTS' ); ?> value="EXISTS">EXISTS</option>
								</select>
								<span class="help-block small"><?php esc_html_e( 'Operator to test the field value', 'grand-media' ); ?></span>
							</div>
							<div class="col-sm-6 col-md-3">
								<label><?php esc_html_e( 'Meta Type', 'grand-media' ); ?></label>
								<select class="form-control input-xs" name="meta_query[<?php echo (int) $i; ?>][type]">
									<option <?php selected( $q['type'], '' ); ?> value=""><?php esc_html_e( 'Default', 'grand-media' ); ?> (CHAR)</option>
									<option <?php selected( $q['type'], 'CHAR' ); ?> value="CHAR">CHAR</option>
									<option <?php selected( $q['type'], 'NUMERIC' ); ?> value="NUMERIC">NUMERIC</option>
									<option <?php selected( $q['type'], 'DECIMAL' ); ?> value="DECIMAL">DECIMAL</option>
									<option <?php selected( $q['type'], 'DATE' ); ?> value="DATE">DATE</option>
									<option <?php selected( $q['type'], 'DATETIME' ); ?> value="DATETIME">DATETIME</option>
									<option <?php selected( $q['type'], 'TIME' ); ?> value="TIME">TIME</option>
									<option <?php selected( $q['type'], 'BINARY' ); ?> value="BINARY">BINARY</option>
									<option <?php selected( $q['type'], 'SIGNED' ); ?> value="SIGNED">SIGNED</option>
									<option <?php selected( $q['type'], 'UNSIGNED' ); ?> value="UNSIGNED">UNSIGNED</option>
								</select>
								<span class="help-block small"><?php esc_html_e( 'Custom field type', 'grand-media' ); ?></span>
							</div>
						</div>
					<?php } ?>
				</div>
				<div class="form-group">
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<label><?php esc_html_e( 'Order', 'grand-media' ); ?></label>
							<select class="form-control input-xs" name="order">
								<option <?php selected( $query_data['order'], '' ); ?> value=""><?php esc_html_e( 'Default (DESC)', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['order'], 'DESC' ); ?> value="DESC"><?php esc_html_e( 'DESC', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['order'], 'ASC' ); ?> value="ASC"><?php esc_html_e( 'ASC', 'grand-media' ); ?></option>
							</select>
							<span class="help-block small"><?php esc_html_e( 'Ascending or Descending order', 'grand-media' ); ?></span>
						</div>
						<div class="col-sm-6 col-md-3">
							<label><?php esc_html_e( 'Order by', 'grand-media' ); ?></label>
							<select class="form-control input-xs" name="orderby">
								<option <?php selected( $query_data['orderby'], '' ); ?> value=""><?php esc_html_e( 'Default (ID)', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'id' ); ?> value="ID"><?php esc_html_e( 'ID', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'title' ); ?> value="title"><?php esc_html_e( 'Title', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'gmuid' ); ?> value="gmuid"><?php esc_html_e( 'Filename', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'author' ); ?> value="author"><?php esc_html_e( 'Author', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'gmedia__in' ); ?> value="gmedia__in"><?php esc_html_e( 'Selected Order', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'date' ); ?> value="date"><?php esc_html_e( 'Date', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'modified' ); ?> value="modified"><?php esc_html_e( 'Modified Date', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], '_created_timestamp' ); ?> value="_created_timestamp"><?php esc_html_e( 'Created Timestamp', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'comment_count' ); ?> value="comment_count"><?php esc_html_e( 'Comment Count', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'views' ); ?> value="views"><?php esc_html_e( 'Views Count', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'likes' ); ?> value="likes"><?php esc_html_e( 'Likes Count', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], '_size' ); ?> value="_size"><?php esc_html_e( 'File Size', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'meta_value' ); ?> value="meta_value"><?php esc_html_e( 'Custom Field Value', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'meta_value_num' ); ?> value="meta_value_num"><?php esc_html_e( 'Custom Field Value (Numeric)', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'rand' ); ?> value="rand"><?php esc_html_e( 'Random', 'grand-media' ); ?></option>
								<option <?php selected( $query_data['orderby'], 'none' ); ?> value="none"><?php esc_html_e( 'None', 'grand-media' ); ?></option>
							</select>
							<span class="help-block small"><?php esc_html_e( 'Sort retrieved posts by', 'grand-media' ); ?></span>
						</div>
						<div class="col-sm-6 col-md-3">
							<label><?php esc_html_e( 'Limit', 'grand-media' ); ?></label>
							<input type="text" class="form-control input-xs" value="<?php echo esc_attr( $query_data['limit'] ); ?>" name="limit" placeholder="<?php esc_attr_e( 'leave empty for no limit', 'grand-media' ); ?>">
							<span class="help-block small"><?php esc_html_e( 'Limit number of gmedia items', 'grand-media' ); ?></span>
						</div>
					</div>
				</div>
			</div>
			<script type="text/javascript">
							jQuery(function($) {
				<?php if ( gm_user_can( 'terms' ) ) { ?>

								var gmedia_albums = <?php echo wp_json_encode( array_values( $gm_album_terms ) ); ?>;
								var gmedia_categories = <?php echo wp_json_encode( array_values( $gm_category_terms ) ); ?>;
								var gmedia_tags = <?php echo wp_json_encode( array_values( $gm_tag_terms ) ); ?>;
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
											var term_id = '';
											if (parseInt(item.term_id)) {
												count = '(' + escape(item.count) + ')';
												status = (typeof item.status !== 'undefined' && ('publish' !== item.status)) ? ' [' + item.status + '] ' : '';
												author = ' ' + item.by_author;
												term_id = '[' + item.term_id + '] ';
											}
											return '<div>' + term_id + escape(item.name) + ' <small>' + count + status + author + '</small></div>';
										},
										option: function(item, escape) {
											var count = '';
											var status = '';
											var author = '';
											var term_id = '';
											if (parseInt(item.term_id)) {
												count = '(' + escape(item.count) + ')';
												status = (typeof item.status !== 'undefined' && ('publish' !== item.status)) ? ' [' + item.status + '] ' : '';
												author = ' ' + item.by_author;
												term_id = '[' + item.term_id + '] ';
											}
											return '<div>[' + item.term_id + '] ' + escape(item.name) + ' <small>' + count + status + author + '</small></div>';
										},
									},

								});
								var cats = $('.combobox_gmedia_category').selectize({
									plugins: ['drag_drop'],
									create: false,
									options: gmedia_categories,
									preload: true,
									hideSelected: true,
									allowEmptyOption: true,
									valueField: 'term_id',
									searchField: ['name'],
									disabledField: 'disable',
									//labelField: 'name',
									render: {
										item: function(item, escape) {
											var count = '';
											var term_id = '';
											if (parseInt(item.term_id)) {
												count = ' <small>(' + escape(item.count) + ')</small>';
												term_id = '[' + item.term_id + '] ';
											}
											return '<div>' + term_id + escape(item.name) + count + '</div>';
										},
										option: function(item, escape) {
											if (('category__and' === this.$input[0].name) && !item.term_id) {
												item.disable = true;
												return '<div></div>';
											}
											var count = '';
											var term_id = '';
											if (parseInt(item.term_id)) {
												count = ' <small>(' + escape(item.count) + ')</small>';
												term_id = '[' + item.term_id + '] ';
											}
											return '<div>' + term_id + escape(item.name) + count + '</div>';
										},
									},

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
									plugins: ['drag_drop'],
									create: false,
									options: gmedia_tags,
									hideSelected: true,
									allowEmptyOption: true,
									valueField: 'term_id',
									searchField: ['name'],
									render: {
										item: function(item, escape) {
											return '<div>[' + item.term_id + '] ' + escape(item.name) + ' <small>(' + escape(item.count) + ')</small></div>';
										},
										option: function(item, escape) {
											return '<div>[' + item.term_id + '] ' + escape(item.name) + ' <small>(' + escape(item.count) + ')</small></div>';
										},
									},

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
				<?php } ?>

								$('.gmedia-combobox').each(function() {
									var select = $(this).selectize({
										create: false,
										hideSelected: true,
										options: $(this).data('options'),
									});
								});

								$('.query_switch').on('click', function() {
									var el = $('#' + $(this).attr('data-target'));
									if ($(this).is(':checked')) {
										el.attr('name', el.attr('data-exclude'));
									}
									else {
										el.attr('name', el.attr('data-include'));
									}
								});
								$('#use_lib_selected').on('click', function() {
									var field = $('#query_gmedia__');
									var valData = field.val().split(',');
									var storedData = getStorage();
									storedData = storedData.get('gmedia_library').split('.');
									valData = $.grep(valData, function(e) {
										return e;
									});
									$.each(storedData, function(i, id) {
										if (!id) {
											return true;
										}
										if ($.inArray(id, valData) === -1) {
											valData.push(id);
										}
									});
									field.val(valData.join(','));
								});

								$('.buildqueryreset').on('click', function() {
									$('input.gm-selectize, select.gm-selectize', '#buildQuery').each(function() {
										this.selectize.clear();
									});
									$('input[type="text"], select, textarea', '#buildQuery').each(function() {
										$(this).val('');
									});
									$('input[type="checkbox"]', '#buildQuery').prop('checked', false);
								});
							});

			</script>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary buildqueryreset"><?php esc_html_e( 'Reset', 'grand-media' ); ?></button>
				<button type="button" class="btn btn-primary buildquerysubmit"><?php esc_html_e( 'Build Query', 'grand-media' ); ?></button>
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'grand-media' ); ?></button>
			</div>
		</div>
	</div>
</div>
