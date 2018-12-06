<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Edit Gmedia Item
 */
?>
<form class="cb_list-item list-group-item row d-row edit-gmedia <?php echo implode( ' ', $item->classes ); ?>" id="list-item-<?php echo $item->ID; ?>" data-id="<?php echo $item->ID; ?>" data-type="<?php echo $item->type; ?>" role="form">
    <div class="col-sm-4" style="max-width:340px;">
        <input name="ID" type="hidden" value="<?php echo $item->ID; ?>"/>
        <div class="thumbwrap">
            <div class="cb_media-object">
                <span data-clicktarget="gmimageedit<?php echo $item->ID; ?>" class="thumbnail gmedia-cover-image">
                    <?php echo gmedia_item_thumbnail( $item ); ?>
                </span>
            </div>
            <label class="gm-item-check"><input name="doaction[]" type="checkbox"<?php echo $item->selected ? ' checked="checked"' : ''; ?> data-type="<?php echo $item->type; ?>" value="<?php echo $item->ID; ?>"/></label>
            <label class="gm-stack hidden"><input name="stack[]" type="checkbox"<?php echo $item->in_stack ? ' checked="checked"' : ''; ?> data-type="<?php echo $item->type; ?>" value="<?php echo $item->ID; ?>"/></label>
        </div>
        <div class="gmedia-actions">
			<?php $media_action_links = gmedia_item_actions( $item );
			unset( $media_action_links['edit_data'] );
			echo implode( ' | ', $media_action_links );
			?>
        </div>
		<?php
		if ( 'audio' == $item->type ) {
			echo gmedia_waveform_player( $item );
		}
		?>
        <div class="form-group">
            <label><?php _e( 'Related Media', 'grand-media' ); ?>:
                <a href="<?php echo $gmCore->get_admin_url(
					array(
						'page'        => 'GrandMedia',
						'mode'        => 'select_multiple',
						'gmediablank' => 'library'
					), array(), true ); ?>"
                   class="preview-modal"
                   data-target="#previewModal"
                   data-width="1200"
                   data-height="500"
                   data-cls="select_gmedia_related"
                   title="<?php _e( 'Choose Related Media', 'grand-media' ); ?>">
					<?php _e( 'choose' ); ?>
                    <span class="glyphicon glyphicon-picture"></span>
                </a>
            </label>
            <div class="related-media-previews">
				<?php
				$related_ids = isset( $item->meta['_related'][0] ) ? $item->meta['_related'][0] : array();
				if(!empty($related_ids)){
				    $related_media = $gmDB->get_gmedias(array('gmedia__in' => $related_ids, 'orderby' => 'gmedia__in'));
				    foreach($related_media as $r_item){
				        ?><p class="thumbnail gmedia-related-image">
                            <span class="image-wrapper"><?php echo gmedia_item_thumbnail( $r_item ); ?></span>
                            <span class="gm-remove">&times;</span>
                            <input type="hidden" name="meta[_related][]" value="<?php echo $r_item->ID; ?>" />
                        </p><?php
                    }
                }
				?>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="row">
            <div class="form-group col-lg-6">
                <label><?php _e( 'Title', 'grand-media' ); ?></label>
                <input name="title" type="text" class="form-control input-sm" placeholder="<?php _e( 'Title', 'grand-media' ); ?>" value="<?php esc_attr_e( $item->title ); ?>">
            </div>
            <div class="form-group col-lg-6">
                <label><?php _e( 'Link URL', 'grand-media' ); ?></label>
                <div class="input-group">
                    <input name="link" type="text" class="form-control input-sm gmedia-custom-link-field" id="gmlink<?php echo $item->ID; ?>" value="<?php echo $item->link; ?>"/>
                    <span class="input-group-btn"><button type="button" class="btn btn-sm btn-primary gmedia-custom-link" data-target="gmlink<?php echo $item->ID; ?>" title="<?php _e( 'Link to existing WP content', 'grand-media' ); ?>"><span class="glyphicon glyphicon-link"></span></button></span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-6">
                <label><?php _e( 'Description', 'grand-media' ); ?></label>
				<?php if ( ( 'false' != $gmedia_user_options['library_edit_quicktags'] ) || ( $gmProcessor->gmediablank && ( 1 == $resultPerPage ) ) ) {
					wp_editor( esc_textarea( $item->description ), "gm{$item->ID}_description", array(
						'editor_class'  => 'form-control input-sm',
						'editor_height' => 140,
						'wpautop'       => false,
						'media_buttons' => false,
						'textarea_name' => 'description',
						'textarea_rows' => '4',
						'tinymce'       => false,
						'quicktags'     => array( 'buttons' => apply_filters( 'gmedia_editor_quicktags', 'strong,em,link,ul,li,close' ) )
					) );
				} else {
					echo "<textarea id='gm{$item->ID}_description' class='form-control input-sm' name='description' cols='20' rows='4' style='height:174px'>" . esc_textarea( $item->description ) . '</textarea>';
				} ?>
            </div>
            <div class="col-lg-6">
				<?php if ( gm_user_can( 'terms' ) ) { ?>
					<?php
					$alb_id    = empty( $item->album ) ? 0 : reset( $item->album )->term_id;
					$term_type = 'gmedia_album';
					$args      = array();
					if ( ! gm_user_can( 'edit_others_media' ) ) {
						$args = array( 'global' => array( 0, $user_ID ), 'orderby' => 'global_desc_name' );
					}
					$gm_terms = $gmDB->get_terms( $term_type, $args );

					$terms_album  = '';
					$album_status = 'none';
					if ( count( $gm_terms ) ) {
						foreach ( $gm_terms as $term ) {
							$author_name = '';
							if ( $term->global ) {
								if ( gm_user_can( 'edit_others_media' ) ) {
									$author_name .= ' &nbsp; ' . sprintf( __( 'by %s', 'grand-media' ), get_the_author_meta( 'display_name', $term->global ) );
								}
							} else {
								$author_name .= ' &nbsp; (' . __( 'shared', 'grand-media' ) . ')';
							}
							if ( 'publish' != $term->status ) {
								$author_name .= ' [' . $term->status . ']';
							}

							$selected_option = '';
							if ( $alb_id == $term->term_id ) {
								$selected_option = ' selected="selected"';
								$album_status    = $term->status;
							}
							$terms_album .= '<option' . $selected_option . ' value="' . $term->term_id . '">' . esc_html( $term->name ) . $author_name . '</option>' . "\n";
						}
					}
					?>
                    <div class="form-group status-album bg-status-<?php echo $album_status; ?>">
                        <label><?php _e( 'Album ', 'grand-media' ); ?></label>
                        <input type="text" class="gm-order-input" name="gmedia_album_order" title="<?php _e( 'Sort order (custom) in the chosen Album', 'grand-media' ); ?>" value="<?php echo $alb_id ? reset( $item->album )->gmedia_order : '0'; ?>" <?php echo $alb_id ? '' : 'disabled' ?>/>
                        <select name="terms[gmedia_album]" data-create="<?php echo gm_user_can( 'album_manage' ) ? 'true' : 'false'; ?>" class="combobox_gmedia_album form-control input-sm" placeholder="<?php _e( 'Album Name...', 'grand-media' ); ?>">
                            <option<?php echo $alb_id ? '' : ' selected="selected"'; ?> value=""></option>
							<?php echo $terms_album; ?>
                        </select>
                    </div>

					<?php
					if ( ! empty( $item->categories ) ) {
						$terms_category = array();
						foreach ( $item->categories as $c ) {
							$terms_category[] = esc_html( $c->name );
						}
						$terms_category = join( ',', $terms_category );
					} else {
						$terms_category = '';
					}
					?>
                    <div class="form-group">
                        <label><?php _e( 'Categories', 'grand-media' ); ?></label>
                        <input name="terms[gmedia_category]" data-create="<?php echo gm_user_can( 'category_manage' ) ? 'true' : 'false'; ?>" class="combobox_gmedia_category form-control input-sm" value="<?php echo $terms_category; ?>" placeholder="<?php _e( 'Uncategorized', 'grand-media' ); ?>"/>
                    </div>

					<?php
					if ( ! empty( $item->tags ) ) {
						$terms_tag = array();
						foreach ( $item->tags as $c ) {
							$terms_tag[] = esc_html( $c->name );
						}
						$terms_tag = join( ',', $terms_tag );
					} else {
						$terms_tag = '';
					}
					?>
                    <div class="form-group">
                        <label><?php _e( 'Tags ', 'grand-media' ); ?></label>
                        <input name="terms[gmedia_tag]" data-create="<?php echo gm_user_can( 'tag_manage' ) ? 'true' : 'false'; ?>" class="combobox_gmedia_tag form-control input-sm" value="<?php echo $terms_tag; ?>"/>
                    </div>
				<?php } ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
				<?php if ( 'image' == $item->type ) { ?>
                    <div class="form-group">
                        <label><?php _e( 'Alternative Text', 'grand-media' ); ?></label>
                        <input type="text" class="form-control input-sm" name="meta[_image_alt]" value="<?php echo isset( $item->meta['_image_alt'][0] ) ? esc_attr( $item->meta['_image_alt'][0] ) : ''; ?>" placeholder="<?php _e( 'if empty, same as Title', 'grand-media' ); ?>"/>
                    </div>
				<?php } ?>
                <div class="form-group">
                    <label><?php _e( 'Filename', 'grand-media' ); ?>
                        <small style="white-space:nowrap;">(ext: .<?php echo $item->ext; ?>)</small>
                    </label>
                    <input name="filename" type="text" class="form-control input-sm gmedia-filename" <?php echo ( ! gm_user_can( 'delete_others_media' ) && ( (int) $item->author !== $user_ID ) ) ? 'readonly' : ''; ?> value="<?php echo pathinfo( $item->gmuid, PATHINFO_FILENAME ); ?>"/>
                </div>
                <div class="form-group">
                    <label><?php _e( 'Date', 'grand-media' ); ?></label>

                    <div class="input-group gmedia_date input-group-sm" data-date-format="YYYY-MM-DD HH:mm:ss">
                        <input name="date" type="text" class="form-control input-sm" value="<?php echo $item->date; ?>" readonly />
                        <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
                    </div>
                </div>
                <div class="form-group status-item bg-status-<?php echo $item->status; ?>">
                    <label><?php _e( 'Status', 'grand-media' ); ?></label>
                    <select name="status" class="form-control input-sm">
                        <option <?php selected( $item->status, 'publish' ); ?> value="publish"><?php _e( 'Public', 'grand-media' ); ?></option>
                        <option <?php selected( $item->status, 'private' ); ?> value="private"><?php _e( 'Private', 'grand-media' ); ?></option>
                        <option <?php selected( $item->status, 'draft' ); ?> value="draft"><?php _e( 'Draft', 'grand-media' ); ?></option>
                    </select>
                </div>
				<?php if ( ! empty( $item->post_id ) ) { ?>
                    <div class="form-group">
                        <a href="<?php echo add_query_arg( array( 'page' => 'GrandMedia', 'gmediablank' => 'comments', 'gmedia_id' => $item->ID ), $gmProcessor->url ); ?>" data-target="#previewModal" data-width="900" data-height="500" class="preview-modal gmpost-com-count pull-right" title="<?php esc_attr_e( 'Comments', 'grand-media' ); ?>">
                            <b class="comment-count"><?php echo $item->comment_count; ?></b>
                            <span class="glyphicon glyphicon-comment"></span>
                        </a>
                        <label><?php _e( 'Comment Status', 'grand-media' ); ?></label>
                        <select name="comment_status" class="form-control input-sm">
                            <option <?php selected( $item->comment_status, 'open' ); ?> value="open"><?php _e( 'Open', 'grand-media' ); ?></option>
                            <option <?php selected( $item->comment_status, 'closed' ); ?> value="closed"><?php _e( 'Closed', 'grand-media' ); ?></option>
                        </select>
                    </div>
				<?php } ?>
            </div>
            <div class="col-lg-6">
				<?php if ( 'image' != $item->type || ( 'image' == $item->type && ! $item->editor ) ) { ?>
                    <div class="form-group">
                        <label><?php _e( 'Custom Cover', 'grand-media' );
							echo ' <small>(' . __( 'media image ID', 'grand-media' ) . ')</small>'; ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control input-sm gmedia-cover-id" name="meta[_cover]" value="<?php echo isset( $item->meta['_cover'][0] ) ? $item->meta['_cover'][0] : ''; ?>" placeholder="<?php _e( 'Gmedia Image ID', 'grand-media' ); ?>"/>
                            <span class="input-group-btn"><a href="<?php echo $gmCore->get_admin_url( array(
									'page'        => 'GrandMedia',
									'mode'        => 'select_single',
									'gmediablank' => 'library',
									'filter'      => 'image'
								), array(), true ); ?>" class="btn btn-sm btn-primary preview-modal" data-target="#previewModal" data-width="1200" data-height="500" data-cls="select_gmedia_image" title="<?php _e( 'Choose Cover Image', 'grand-media' ); ?>"><span class="glyphicon glyphicon-picture"></span></a></span>
                        </div>
                    </div>
				<?php } ?>
				<?php if ( ( 'image' == $item->type ) || ( 'video' == $item->type ) ) { ?>
                    <div class="form-group">
                        <label><?php _e( 'GPS Location', 'grand-media' ); ?></label>

                        <div class="input-group input-group-sm">
                            <input name="meta[_gps]" type="text" class="form-control input-sm gps_map_coordinates" value="<?php echo $item->gps; ?>" placeholder="<?php _e( 'Latitude, Longtitude', 'grand-media' ); ?>" autocomplete="off"/>
                            <span class="input-group-btn"><a href="<?php echo add_query_arg( array( 'page' => 'GrandMedia', 'gmediablank' => 'map_editor', 'id' => $item->ID ), $gmProcessor->url ); ?>" class="btn btn-primary gmedit-modal" data-target="#gmeditModal">
                                                    <span class="glyphicon glyphicon-map-marker"></span></a></span>
                        </div>
                    </div>
				<?php } ?>
                <p class="media-meta">
                    <span class="label label-default"><?php _e( 'Author', 'grand-media' ); ?>:</span> <?php echo get_the_author_meta( 'display_name', $item->author ); ?>
                    <br/><span class="label label-default"><?php _e( 'ID', 'grand-media' ) ?>:</span> <strong><?php echo $item->ID; ?></strong>
                    <br/><span class="label label-default"><?php _e( 'Post ID', 'grand-media' ) ?>:</span> <strong><?php echo $item->post_id; ?></strong>
                    <br/><span class="label label-default"><?php _e( 'Type', 'grand-media' ); ?>:</span> <?php echo $item->mime_type; ?>
					<?php if ( ( 'image' == $item->type ) && $item->editor && ! empty( $item->meta['_metadata'] ) ) { ?>
                        <br/><span class="label label-default"><?php _e( 'Dimensions', 'grand-media' ); ?>:</span>
						<?php
						$is_file_original = (bool) $item->path_original;
						if ( $is_file_original ) { ?>
                            <a href="<?php echo $item->url_original; ?>"
                               data-target="#previewModal"
                               data-width="<?php echo $item->meta['_metadata'][0]['original']['width']; ?>"
                               data-height="<?php echo $item->meta['_metadata'][0]['original']['height']; ?>"
                               class="preview-modal"
                               title="<?php _e( 'Original', 'grand-media' ); ?>"><?php echo $item->meta['_metadata'][0]['original']['width'] . '×' . $item->meta['_metadata'][0]['original']['height']; ?></a>,
						<?php } else { ?>
                            <span title="<?php _e( 'Original', 'grand-media' ); ?>"><?php echo $item->meta['_metadata'][0]['original']['width'] . '×' . $item->meta['_metadata'][0]['original']['height']; ?></span>,
						<?php } ?>
                        <a href="<?php echo $item->url; ?>"
                           data-target="#previewModal"
                           data-width="<?php echo $item->meta['_metadata'][0]['web']['width']; ?>"
                           data-height="<?php echo $item->meta['_metadata'][0]['web']['height']; ?>"
                           class="preview-modal"
                           title="<?php _e( 'Webimage', 'grand-media' ); ?>"><?php echo $item->meta['_metadata'][0]['web']['width'] . '×' . $item->meta['_metadata'][0]['web']['height']; ?></a>,
                        <a href="<?php echo $item->url_thumb; ?>"
                           data-target="#previewModal"
                           data-width="<?php echo $item->meta['_metadata'][0]['thumb']['width']; ?>"
                           data-height="<?php echo $item->meta['_metadata'][0]['thumb']['height']; ?>"
                           class="preview-modal"
                           title="<?php _e( 'Thumbnail', 'grand-media' ); ?>"><?php echo $item->meta['_metadata'][0]['thumb']['width'] . '×' . $item->meta['_metadata'][0]['thumb']['height']; ?></a>
                        <br/><span class="label label-default"><?php _e( 'File Size', 'grand-media' ) ?>:</span> <?php
						echo ( $is_file_original ? $gmCore->filesize( $item->path_original ) : '&#8212;' ) . ', ';
						echo $gmCore->filesize( $item->path ) . ', ' . $gmCore->filesize( $item->path_thumb ); ?>
					<?php } else { ?>
                        <br/><span class="label label-default"><?php _e( 'File Size', 'grand-media' ) ?>:</span> <?php echo $gmCore->filesize( $item->path ); ?>
					<?php } ?>
					<?php if ( ! empty( $item->meta['_created_timestamp'][0] ) ) { ?>
                        <br/><span class="label label-default"><?php _e( 'Created', 'grand-media' ) ?>:</span> <?php echo date( 'Y-m-d H:i:s ', $item->meta['_created_timestamp'][0] ); ?>
					<?php } ?>
                    <br/><span class="label label-default"><?php _e( 'Uploaded', 'grand-media' ) ?>:</span> <?php echo $item->date; ?>
                    <br/><span class="label label-default"><?php _e( 'Last Edited', 'grand-media' ) ?>:</span> <span class="gm-last-edited modified"><?php echo $item->modified; ?></span>
                </p>
            </div>
        </div>
		<?php
		$gmCore->gmedia_custom_meta_box( $item->ID );
		do_action( 'gmedia_edit_form' );
		?>
    </div>
</form>