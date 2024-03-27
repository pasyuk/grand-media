<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * @title  Add action/filter for the upload tab
 */

if ( current_user_can( 'gmedia_library' ) ) {
	add_filter( 'media_upload_tabs', 'gmedia_upload_tabs' );
	add_action( 'media_upload_gmedia_library', 'media_upload_gmedia' );
	add_action( 'media_upload_gmedia_terms', 'media_upload_gmedia' );
	add_action( 'media_upload_gmedia_galleries', 'media_upload_gmedia' );
}


/**
 * @param $tabs
 *
 * @return array
 */
function gmedia_upload_tabs( $tabs ) {

	$newtab = array(
		'gmedia_library'   => __( 'Gmedia Library', 'grand-media' ),
		'gmedia_terms'     => __( 'Gmedia Collections', 'grand-media' ),
		'gmedia_galleries' => __( 'Gmedia Galleries', 'grand-media' ),
	);

	if ( is_array( $tabs ) ) {
		return array_merge( $tabs, $newtab );
	}

	return $newtab;
}

function media_upload_gmedia() {
	global $gmCore, $gmDB;

	add_action( 'admin_enqueue_scripts', 'gmedia_add_media_popup_enqueue_scripts' );

	$action = $gmCore->_get( 'action' );
	if ( did_action( 'media_upload_gmedia_galleries' ) ) {
		wp_iframe( 'gmedia_add_media_galleries' );
	} elseif ( did_action( 'media_upload_gmedia_terms' ) ) {
		wp_iframe( 'gmedia_add_media_terms' );
	} elseif ( did_action( 'media_upload_gmedia_library' ) ) {
		if ( ( 'upload' === $action ) && current_user_can( 'gmedia_upload' ) ) {
			wp_iframe( 'gmedia_add_media_upload' );
		} else {
			wp_iframe( 'gmedia_add_media_library' );
		}
	}

	// Generate TinyMCE HTML output.
	if ( isset( $_POST['gmedia_library_insert'] ) ) {

		$id = $gmCore->_post( 'ID', 0 );

		$gmedia = $gmDB->get_gmedia( $id );
		if ( $gmedia ) {

			$meta = $gmDB->get_metadata( 'gmedia', $gmedia->ID, '_metadata', true );

			$size    = sanitize_key( $gmCore->_post( 'size', 'web' ) );
			$src     = esc_url( $gmCore->gm_get_media_image( $gmedia, $size ) );
			$width   = esc_attr( $meta[ $size ]['width'] );
			$height  = esc_attr( $meta[ $size ]['height'] );
			$title   = esc_attr( $gmCore->_post( 'title', '' ) );
			$align   = esc_attr( $gmCore->_post( 'align', 'none' ) );
			$link    = trim( esc_attr( $gmCore->_post( 'link', '' ) ) );
			$caption = trim( $gmCore->_post( 'description', '' ) );

			$html = "<img src='{$src}' width='{$width}' height='{$height}' alt='{$title}' title='{$title}' id='gmedia-image-{$id}' class='gmedia-singlepic align{$align}' />";

			if ( $link ) {
				$html = "<a href='{$link}'>{$html}</a>";
			}
			if ( $caption ) {
				$html = image_add_caption( $html, false, $caption, $title, $align, $src, $size, $title );
			}

			?>
			<script type="text/javascript">
							/* <![CDATA[ */
							var win = window.dialogArguments || opener || parent || top;
							jQuery( '#__gm-uploader', win.document ).css( 'display', 'none' );
							/* ]]> */
			</script>
			<?php
			// Return it to TinyMCE.
			media_send_to_editor( $html );
		}
	}
	if ( isset( $_POST['gmedia_gallery_insert'] ) ) {
		$sc = $gmCore->_post( 'shortcode' );
		?>
		<script type="text/javascript">
					/* <![CDATA[ */
					var win = window.dialogArguments || opener || parent || top;
					jQuery( '#__gm-uploader', win.document ).css( 'display', 'none' );
					/* ]]> */
		</script>
		<?php
		// Return it to TinyMCE.
		media_send_to_editor( $sc );
	}
	if ( isset( $_POST['gmedia_term_insert'] ) ) {
		$module_preset = $gmCore->_post( 'module_preset' );
		$module        = '';
		$preset        = '';
		if ( ! empty( $module_preset ) ) {
			if ( $gmCore->is_digit( $module_preset ) ) {
				$module_preset = $gmDB->get_term( (int) $module_preset );
				$module        = ' module=' . $module_preset->status;
				$preset        = ' preset=' . $module_preset->term_id;
			} else {
				$module = ' module=' . esc_attr( $module_preset );
			}
		}
		$tax     = $gmCore->_post( 'taxonomy' );
		$term_id = $gmCore->_post( 'term_id' );
		if ( $tax && $term_id ) {
			$tax = str_replace( 'gmedia_', '', $tax );
			$sc  = "[gm {$tax}={$term_id}{$module}{$preset}]";
			?>
			<script type="text/javascript">
							/* <![CDATA[ */
							var win = window.dialogArguments || opener || parent || top;
							jQuery( '#__gm-uploader', win.document ).css( 'display', 'none' );
							/* ]]> */
			</script>
			<?php
			// Return it to TinyMCE.
			media_send_to_editor( $sc );
		}
	}

}

function gmedia_add_media_popup_enqueue_scripts() {
	global $gmCore;

	wp_dequeue_script( 'imgareaselect' );
	wp_dequeue_script( 'image-edit' );
	wp_dequeue_script( 'set-post-thumbnail' );
	wp_dequeue_script( 'media-gallery' );
	wp_dequeue_script( 'plupload' );
	wp_dequeue_script( 'plupload-handlers' );
	wp_dequeue_style( 'imgareaselect' );

	wp_enqueue_style( 'gmedia-bootstrap' );
	wp_enqueue_script( 'gmedia-bootstrap' );

	wp_enqueue_style( 'grand-media' );
	wp_enqueue_script( 'grand-media' );

	$action = $gmCore->_get( 'action' );
	if ( did_action( 'media_upload_gmedia_library' ) && ( 'upload' === $action ) && current_user_can( 'gmedia_upload' ) ) {
		if ( current_user_can( 'gmedia_terms' ) ) {
			wp_enqueue_style( 'selectize', $gmCore->gmedia_url . '/assets/selectize/selectize.bootstrap5.css', array( 'gmedia-bootstrap' ), '0.13.5', 'screen' );
			wp_enqueue_script( 'selectize', $gmCore->gmedia_url . '/assets/selectize/selectize.min.js', array( 'jquery' ), '0.13.5', true );
		}
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style( 'jquery.plupload.queue', $gmCore->gmedia_url . '/assets/jquery.plupload.queue/css/jquery.plupload.queue.css', array(), '2.3.9', 'screen' );
		wp_enqueue_script( 'jquery.plupload.queue', $gmCore->gmedia_url . '/assets/jquery.plupload.queue/jquery.plupload.queue.min.js', array( 'plupload' ), '2.3.9', true );
	}

}

function gmedia_add_media_galleries() {

	global $user_ID, $gmCore, $gmDB, $gmGallery, $gm_allowed_tags;

	$post_id = intval( $gmCore->_get( 'post_id' ) );

	$gm_screen_options = get_user_meta( $user_ID, 'gm_screen_options', true );
	if ( ! is_array( $gm_screen_options ) ) {
		$gm_screen_options = array();
	}
	$gm_screen_options = array_merge( $gmGallery->options['gm_screen_options'], $gm_screen_options );
	$orderby           = ! empty( $gm_screen_options['orderby_gmedia_gallery'] ) ? $gm_screen_options['orderby_gmedia_gallery'] : 'name';
	$order             = ! empty( $gm_screen_options['sortorder_gmedia_gallery'] ) ? $gm_screen_options['sortorder_gmedia_gallery'] : 'ASC';
	$per_page          = ! empty( $gm_screen_options['per_page_gmedia_gallery'] ) ? $gm_screen_options['per_page_gmedia_gallery'] : 30;

	$args = array(
		'orderby'    => $gmCore->_get( 'orderby', $orderby ),
		'order'      => $gmCore->_get( 'order', $order ),
		'search'     => $gmCore->_get( 's', '' ),
		'number'     => $gmCore->_get( 'number', $per_page ),
		'hide_empty' => 0,
		'page'       => $gmCore->_get( 'pager', 1 ),
		//'status'     => array('publish', 'private')
	);

	$args['offset'] = ( $args['page'] - 1 ) * $args['number'];

	if ( current_user_can( 'gmedia_show_others_media' ) ) {
		$args['global'] = $gmCore->_get( 'author', '' );
	} else {
		$args['global'] = array( $user_ID );
	}

	$taxonomy    = 'gmedia_gallery';
	$gmediaTerms = $gmDB->get_terms( $taxonomy, $args );
	$alert       = '';
	if ( is_wp_error( $gmediaTerms ) ) {
		$alert       = $gmCore->alert( 'danger', $gmediaTerms->get_error_message() );
		$gmediaTerms = array();
	}

	$gmedia_modules = get_gmedia_modules( false );
	?>

	<div class="card m-0 mw-100 min-vh-100 p-0" id="gmedia-container">
		<div class="card-header bg-light clearfix">
			<?php include GMEDIA_ABSPATH . 'admin/tpl/search-form.php'; ?>
			<div class="float-end">
				<?php echo wp_kses( $gmDB->query_pager(), $gm_allowed_tags ); ?>
			</div>

			<div class="btn-group" style="margin-right:20px;">
				<a class="btn btn-primary" target="_blank" href="<?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia_Modules' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Create New Gallery', 'grand-media' ); ?></a>
			</div>

			<div class="btn-group" style="margin-right:20px;">
				<a class="btn btn-success" href="#" onclick="window.location = window.location.href; return false;"><?php esc_html_e( 'Refresh', 'grand-media' ); ?></a>
			</div>

		</div>
		<div class="card-body" id="gmedia-msg-panel"><?php echo wp_kses_post( $alert ); ?></div>
		<div class="card-body p-0" id="gm-list-table">
			<div class="row m-0">
				<div class="col-sm-7 col-md-9" style="padding: 0">
					<div class="list-group">
						<?php
						if ( count( $gmediaTerms ) ) {
							$lib_url = add_query_arg( array( 'page' => 'GrandMedia' ), admin_url( 'admin.php' ) );
							foreach ( $gmediaTerms as $term ) {

								$term_meta = $gmDB->get_metadata( 'gmedia_term', $term->term_id );
								$term_meta = array_map( 'reset', $term_meta );
								//$term_meta = array_map('maybe_unserialize', $term_meta);

								$module      = $gmCore->get_module_path( $term_meta['_module'] );
								$module_info = array( 'type' => '&#8212;' );
								if ( is_file( $module['path'] . '/index.php' ) ) {
									$broken = false;
									/* @noinspection PhpIncludeInspection */
									include $module['path'] . '/index.php';
								} else {
									$broken = true;
								}

								$list_row_class = '';
								if ( 'private' === $term->status ) {
									$list_row_class = ' list-group-item-info';
								} elseif ( 'draft' === $term->status ) {
									$list_row_class = ' list-group-item-warning';
								}
								?>
								<div class="gmedia-insert-item list-group-item clearfix d-row<?php echo esc_attr( $list_row_class ); ?>" id="list-item-<?php echo absint( $term->term_id ); ?>" data-id="<?php echo absint( $term->term_id ); ?>" data-type="<?php echo esc_attr( $term_meta['_module'] ); ?>">

									<div class="media-body">
										<p class="media-title">
											<span><?php echo esc_html( $term->name ); ?></span>
										</p>

										<p class="media-meta">
											<span class="badge badge-info label-default"><?php esc_html_e( 'Author', 'grand-media' ); ?>:</span> <?php echo $term->global ? esc_html( get_the_author_meta( 'display_name', $term->global ) ) : '&#8212;'; ?>
										</p>

										<p class="media-caption"><?php echo nl2br( esc_html( $term->description ) ); ?></p>
									</div>

									<p class="media-meta hidden" style="font-weight:bold">
										<span class="badge badge-info label-default"><?php esc_html_e( 'Shortcode', 'grand-media' ); ?>:</span> [gmedia id=<?php echo absint( $term->term_id ); ?>]
										<input type="hidden" name="shortcode" value="[gmedia id=<?php echo absint( $term->term_id ); ?>]"/>
									</p>

									<p class="media-meta clear hidden">
										<span class="clearfix">
											<span class="media-object float-start" style="width:85px;margin-right:5px;">
												<?php if ( ! $broken ) { ?>
													<span class="img-thumbnail"><img src="<?php echo esc_url( $module['url'] . '/screenshot.png' ); ?>" alt="<?php echo esc_attr( $term->name ); ?>"/></span>
												<?php } else { ?>
													<span class="bg-danger text-center"><?php esc_html_e( 'Module broken. Reinstall module', 'grand-media' ); ?></span>
												<?php } ?>
											</span>
											<span class="badge badge-info label-default"><?php esc_html_e( 'Module', 'grand-media' ); ?>:</span> <?php echo esc_html( $term_meta['_module'] ); ?>
											<br><span class="badge badge-info label-default"><?php esc_html_e( 'Type', 'grand-media' ); ?>:</span> <?php echo esc_html( $module_info['type'] ); ?>
											<br><span class="badge badge-info label-default"><?php esc_html_e( 'Status', 'grand-media' ); ?>:</span> <?php echo esc_html( $term->status ); ?>
										</span>
										<span class="badge badge-info label-default"><?php esc_html_e( 'Last Edited', 'grand-media' ); ?>:</span> <?php echo esc_html( $term_meta['_edited'] ); ?>
										<br><span class="badge badge-info label-default"><?php esc_html_e( 'Query Args.', 'grand-media' ); ?>:</span> <?php echo ! empty( $term_meta['_query'] ) ? esc_html( str_replace( ',"', ', "', wp_json_encode( $term_meta['_query'] ) ) ) : ''; ?>
									</p>
									<?php
									if ( 'draft' === $term->status ) {
										if ( $term->global && ! is_super_admin( $term->global ) ) {
											?>
											<p class="text-danger">
												<?php
												// translators: author name.
												echo esc_html( sprintf( __( 'This gallery will be loaded only for %s and admin users', 'grand-media' ), get_the_author_meta( 'display_name', $term->global ) ) );
												?>
											</p>
											<?php
										} else {
											?>
											<p class="text-danger"><?php esc_html_e( 'This gallery will be loaded only for admin users', 'grand-media' ); ?></p>
											<?php
										}
									} elseif ( 'private' === $term->status ) {
										?>
										<p class="text-warning"><?php esc_html_e( 'This gallery will be loaded only for logged in users', 'grand-media' ); ?></p>
										<?php
									}
									?>
									<?php
									if ( current_user_can( 'gmedia_gallery_manage' ) ) {
										if ( ! ( ( (int) $term->global !== $user_ID ) && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
											?>
											<p class="media-meta hidden"><a target="_blank" href="<?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia_Galleries', 'edit_term' => $term->term_id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit gallery', 'grand-media' ); ?></a>
											</p>
											<?php
										}
									}
									?>

								</div>
								<?php
							}
						} else {
							?>
							<div class="list-group-item">
								<div class="well well-lg text-center">
									<h4><?php esc_html_e( 'No items to show.', 'grand-media' ); ?></h4>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="col-sm-5 col-md-3 media-upload-sidebar">
					<form method="post" id="gmedia-form" role="form">
						<div id="media-upload-form-container" class="media-upload-form-container"></div>
						<div class="card-footer">
							<input type="hidden" name="post_id" id="post_id" value="<?php echo absint( $post_id ); ?>"/>
							<?php wp_nonce_field( 'media-form' ); ?>
							<button type="submit" id="media-upload-form-submit" disabled class="btn btn-primary float-end" name="gmedia_gallery_insert"><?php esc_html_e( /* @lang text */ 'Insert into post', 'grand-media' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<script type="text/javascript">
					jQuery( function( $ ) {
						function divFrame() {
							$( '.card-body' ).css( {top: $( '.card-header' ).outerHeight()} );
						}

						divFrame();
						$( window ).on( 'resize', function() {
							divFrame();
						} );
						$( '.gmedia-insert-item' ).on( 'click', function() {
							var mufc = $( '#media-upload-form-container' ),
								mufs = $( '#media-upload-form-submit' );
							if ( $( this ).hasClass( 'gm-selected' ) ) {
								$( this ).removeClass( 'gm-selected' );
								mufc.empty();
								mufs.prop( 'disabled', true );
								return;
							}
							$( this ).addClass( 'gm-selected' ).siblings().removeClass( 'gm-selected' );
							var info = $( this ).clone();
							info.find( '.media-caption' ).remove().end().find( '.hidden' ).removeClass( 'hidden' );
							mufc.html( info.html() );
							mufs.prop( 'disabled', false );
						} );
					} );
		</script>
	</div>
	<?php
}

function gmedia_add_media_terms() {

	global $user_ID, $gmCore, $gmDB, $gmGallery, $gm_allowed_tags;

	$post_id = intval( $gmCore->_get( 'post_id' ) );

	$url = add_query_arg( array( 'post_id' => $post_id, 'tab' => 'gmedia_terms', 'chromeless' => true ), admin_url( 'media-upload.php' ) );

	$taxonomy = $gmCore->_get( 'term', 'gmedia_album' );
	if ( ! in_array( $taxonomy, array( 'gmedia_album', 'gmedia_tag', 'gmedia_category' ), true ) ) {
		$taxonomy = 'gmedia_album';
	}

	$gm_screen_options = get_user_meta( $user_ID, 'gm_screen_options', true );
	if ( ! is_array( $gm_screen_options ) ) {
		$gm_screen_options = array();
	}
	$gm_screen_options = array_merge( $gmGallery->options['gm_screen_options'], $gm_screen_options );
	$orderby           = ! empty( $gm_screen_options[ 'orderby_' . $taxonomy ] ) ? $gm_screen_options[ 'orderby_' . $taxonomy ] : 'name';
	$order             = ! empty( $gm_screen_options[ 'sortorder_' . $taxonomy ] ) ? $gm_screen_options[ 'sortorder_' . $taxonomy ] : 'ASC';
	$per_page          = ! empty( $gm_screen_options[ 'per_page_' . $taxonomy ] ) ? $gm_screen_options[ 'per_page_' . $taxonomy ] : 30;
	$search_string     = $gmCore->_get( 's', '' );

	$args           = array(
		'orderby'    => $gmCore->_get( 'orderby', $orderby ),
		'order'      => $gmCore->_get( 'order', $order ),
		'search'     => $search_string,
		'number'     => $gmCore->_get( 'number', $per_page ),
		'hide_empty' => $gmCore->_get( 'hide_empty', 0 ),
		'page'       => $gmCore->_get( 'pager', 1 ),
	);
	$args['offset'] = ( $args['page'] - 1 ) * $args['number'];

	switch ( $taxonomy ) {
		case 'gmedia_album':
			//$args['status'] = array('publish', 'private');
			$args['global'] = $gmCore->_get( 'author', $gmCore->caps['gmedia_edit_others_media'] ? '' : array( 0, $user_ID ) );
			if ( ! $gmCore->caps['gmedia_show_others_media'] ) {
				$args['global'] = wp_parse_id_list( $args['global'] );
				$args['global'] = array_intersect( array( 0, $user_ID ), $args['global'] );
				if ( empty( $args['global'] ) ) {
					$args['global'] = array( 0, $user_ID );
				}
			}
			break;
		case 'gmedia_category':
		case 'gmedia_tag':
			if ( 'global' === $args['orderby'] ) {
				$args['orderby'] = 'id';
			}
			break;
	}

	$gmediaTerms = $gmDB->get_terms( $taxonomy, $args );
	$alert       = '';
	if ( is_wp_error( $gmediaTerms ) ) {
		$alert       = $gmCore->alert( 'danger', $gmediaTerms->get_error_message() );
		$gmediaTerms = array();
	}

	$gmedia_modules = get_gmedia_modules( false );
	?>

	<div class="card m-0 mw-100 min-vh-100 p-0">
		<div class="card-header bg-light clearfix">
			<?php include GMEDIA_ABSPATH . 'admin/tpl/search-form.php'; ?>
			<div class="float-end">
				<?php echo wp_kses( $gmDB->query_pager(), $gm_allowed_tags ); ?>
			</div>

			<div class="btn-group" style="margin-right:20px;">
				<a class="btn btn<?php echo ( 'gmedia_album' === $taxonomy ) ? '-primary active' : '-secondary'; ?>"
					href="<?php echo esc_url( add_query_arg( array( 'term' => 'gmedia_album' ), $url ) ); ?>"><?php esc_html_e( 'Albums', 'grand-media' ); ?></a>
				<a class="btn btn<?php echo ( 'gmedia_category' === $taxonomy ) ? '-primary active' : '-secondary'; ?>"
					href="<?php echo esc_url( add_query_arg( array( 'term' => 'gmedia_category' ), $url ) ); ?>"><?php esc_html_e( 'Categories', 'grand-media' ); ?></a>
				<a class="btn btn<?php echo ( 'gmedia_tag' === $taxonomy ) ? '-primary active' : '-secondary'; ?>"
					href="<?php echo esc_url( add_query_arg( array( 'term' => 'gmedia_tag' ), $url ) ); ?>"><?php esc_html_e( 'Tags', 'grand-media' ); ?></a>
			</div>

		</div>
		<div class="card-body" id="gmedia-msg-panel"><?php echo wp_kses_post( $alert ); ?></div>
		<div class="card-body p-0" id="gm-list-table">
			<div class="row m-0">
				<div class="col-sm-7 col-md-9" style="padding: 0">
					<div class="list-group" id="gm-list-table" style="margin-bottom:4px;">
						<?php
						if ( count( $gmediaTerms ) ) {
							$author     = $gmCore->caps['gmedia_show_others_media'] ? 0 : $user_ID;
							$allow_edit = $gmCore->caps['gmedia_edit_others_media'];
							foreach ( $gmediaTerms as $item ) {
								$author_name    = $owner = '';
								$list_row_class = $row_class = '';
								$termItems      = array();
								$per_page       = 10;
								$item_name      = $item->name;
								if ( 'gmedia_album' === $taxonomy ) {
									if ( $item->global ) {
										$owner = get_the_author_meta( 'display_name', $item->global );

										// translators: author name.
										$author_name .= sprintf( __( 'by %s', 'grand-media' ), esc_html( $owner ) );
										if ( $item->global === $user_ID ) {
											$row_class .= ' current_user';

											$allow_edit = $gmCore->caps['gmedia_album_manage'];
										} else {
											$row_class .= ' other_user';

											$allow_edit = $gmCore->caps['gmedia_edit_others_media'];
										}
									} else {
										$owner      = '&#8212;';
										$allow_edit = $gmCore->caps['gmedia_edit_others_media'];

										$author_name .= '(' . __( 'shared', 'grand-media' ) . ')';
										$row_class   .= ' shared';
									}
									if ( 'publish' !== $item->status ) {
										$author_name .= ' [' . $item->status . ']';
										if ( 'private' === $item->status ) {
											$list_row_class = ' list-group-item-info';
										} elseif ( 'draft' === $item->status ) {
											$list_row_class = ' list-group-item-warning';
											//continue;
										}
									}
								}
								if ( $item->count ) {
									if ( 'gmedia_album' === $taxonomy ) {
										$term_meta = $gmDB->get_metadata( 'gmedia_term', $item->term_id );
										$term_meta = array_map( 'reset', $term_meta );
										$term_meta = array_merge( array( '_orderby' => $gmGallery->options['in_album_orderby'], '_order' => $gmGallery->options['in_album_order'] ), $term_meta );
										$args      = array(
											'no_found_rows' => true,
											'per_page'      => $per_page,
											'album__in'     => array( $item->term_id ),
											'author'        => $author,
											'orderby'       => $term_meta['_orderby'],
											'order'         => $term_meta['_order'],
										);
									} elseif ( 'gmedia_category' === $taxonomy ) {
										$term_meta = $gmDB->get_metadata( 'gmedia_term', $item->term_id );
										$term_meta = array_map( 'reset', $term_meta );
										$term_meta = array_merge( array( '_orderby' => $gmGallery->options['in_category_orderby'], '_order' => $gmGallery->options['in_category_order'] ), $term_meta );
										$args      = array(
											'no_found_rows' => true,
											'per_page'      => $per_page,
											'category__in'  => $item->term_id,
											'author'        => $author,
											'orderby'       => $term_meta['_orderby'],
											'order'         => $term_meta['_order'],
										);
									} elseif ( 'gmedia_tag' === $taxonomy ) {
										$args = array(
											'no_found_rows' => true,
											'per_page'      => $per_page,
											'tag_id'        => $item->term_id,
											'author'        => $author,
											'orderby'       => $gmGallery->options['in_tag_orderby'],
											'order'         => $gmGallery->options['in_tag_order'],
										);
									}
									$termItems = $gmDB->get_gmedias( $args );
								}
								if ( 'gmedia_tag' !== $taxonomy ) {
									$_module_preset = ! empty( $term_meta['_module_preset'] ) ? $term_meta['_module_preset'] : $gmGallery->options['default_gmedia_module'];
								} else {
									$_module_preset = $gmGallery->options['default_gmedia_module'];
								}
								$by_author   = '';
								$preset_name = __( 'Default Settings' );
								if ( $gmCore->is_digit( $_module_preset ) ) {
									$preset = $gmDB->get_term( $_module_preset );
									$mfold  = $preset->status;
									if ( (int) $preset->global ) {
										$by_author = ' [' . get_the_author_meta( 'display_name', $preset->global ) . ']';
									}
									if ( '[' . $mfold . ']' !== $preset->name ) {
										$preset_name = str_replace( '[' . $mfold . '] ', '', $preset->name );
									}
								} else {
									$mfold = $_module_preset;
								}
								$module_preset = $gmedia_modules['in'][ $mfold ]['title'] . $by_author . ' - ' . $preset_name;
								?>
								<div class="list-group-item term-list-item d-row<?php echo esc_attr( $list_row_class ); ?>">
									<div class="row<?php echo esc_attr( $row_class ); ?>">
										<div class="col-sm-5 term-label">
											<div class="no-checkbox">
												<span class="term_name"><?php echo esc_html( $item_name ); ?></span>
												<span class="term_info_author"><?php echo esc_html( $author_name ); ?></span>
												<span class="badge badge-success float-end"><?php echo esc_html( $item->count ); ?></span>
											</div>
										</div>
										<div class="col-sm-7">
											<div class="term-images">
												<?php
												if ( ! empty( $termItems ) ) {
													foreach ( $termItems as $i ) {
														?>
														<img style="z-index:<?php echo (int) ( $per_page -- ); ?>;"
															src="<?php echo esc_url( $gmCore->gm_get_media_image( $i, 'thumb', false ) ); ?>"
															alt="<?php echo absint( $i->ID ); ?>"
															title="<?php echo esc_attr( $i->title ); ?>"/>
														<?php
													}
												}
												if ( count( $termItems ) < $item->count ) {
													echo '...';
												}
												?>
											</div>
										</div>
									</div>
									<div class="term-info hidden">
										<?php
										$term_meta = $gmDB->get_metadata( 'gmedia_term', $item->term_id );
										$term_meta = array_map( 'reset', $term_meta );
										$term_meta = array_merge( array( '_orderby' => 'ID', '_order' => 'DESC' ), $term_meta );
										$tax_name  = array(
											'gmedia_album'    => __( 'Album', 'grand-media' ),
											'gmedia_tag'      => __( 'Tag', 'grand-media' ),
											'gmedia_category' => __( 'Category', 'grand-media' ),
										);
										$lib_arg   = array(
											'gmedia_album'    => 'album__in',
											'gmedia_tag'      => 'tag__in',
											'gmedia_category' => 'category__in',
										);
										?>
										<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>"/>
										<input type="hidden" name="term_id" value="<?php echo absint( $item->term_id ); ?>"/>

										<p><strong><?php echo esc_html( $tax_name[ $taxonomy ] ); ?>:</strong> <?php echo esc_html( $item_name ); ?>
											<br/><strong><?php esc_html_e( 'ID', 'grand-media' ); ?>:</strong> <?php echo absint( $item->term_id ); ?>
											<?php
											if ( 'gmedia_tag' !== $taxonomy ) {
												$orderby = array(
													'custom'   => __( 'user defined', 'grand-media' ),
													'ID'       => __( 'by ID', 'grand-media' ),
													'title'    => __( 'by title', 'grand-media' ),
													'gmuid'    => __( 'by filename', 'grand-media' ),
													'date'     => __( 'by date', 'grand-media' ),
													'modified' => __( 'by last modified date', 'grand-media' ),
													'rand'     => __( 'Random', 'grand-media' ),
												);
												?>
												<br/><strong><?php esc_html_e( 'Order', 'grand-media' ); ?>:</strong> <?php echo esc_html( $orderby[ $term_meta['_orderby'] ] ); ?>
												<br/><strong><?php esc_html_e( 'Sort order', 'grand-media' ); ?>:</strong> <?php echo esc_html( $term_meta['_order'] ); ?>
												<?php if ( 'gmedia_album' === $taxonomy ) { ?>
													<br/><strong><?php esc_html_e( 'Status', 'grand-media' ); ?>:</strong> <?php echo esc_html( $item->status ); ?>
													<br/><strong><?php esc_html_e( 'Author', 'grand-media' ); ?>:</strong> <?php echo esc_html( $owner ); ?>
												<?php } ?>
											<?php } ?>
											<br/><strong><?php esc_html_e( 'Module/Preset', 'grand-media' ); ?>:</strong> <?php echo esc_html( $module_preset ); ?>
										</p>
										<?php
										if ( 'gmedia_album' === $taxonomy ) {
											if ( 'draft' === $item->status ) {
												if ( $item->global && ! is_super_admin( $item->global ) ) {
													?>
													<p class="text-danger">
														<?php
														// translators: author name.
														echo esc_html( sprintf( __( 'This gallery will be loaded only for %s and admin users', 'grand-media' ), $owner ) );
														?>
													</p>
													<?php
												} else {
													?>
													<p class="text-danger"><?php esc_html_e( 'This gallery will be loaded only for admin users', 'grand-media' ); ?></p>
													<?php
												}
											} elseif ( 'private' === $item->status ) {
												?>
												<p class="text-warning"><?php esc_html_e( 'This gallery will be loaded only for logged in users', 'grand-media' ); ?></p>
												<?php
											}
										}
										?>
										<p>
											<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia', $lib_arg[ $taxonomy ] => $item->term_id ), admin_url( 'admin.php' ) ) ); ?>" target="_blank"><?php esc_html_e( 'View in Gmedia Library', 'grand-media' ); ?></a>
											<?php if ( ( 'gmedia_album' === $taxonomy ) && $allow_edit ) { ?>
												&nbsp; | &nbsp; <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia_Albums', 'edit_term' => $item->term_id ), admin_url( 'admin.php' ) ) ); ?>" target="_blank"><?php esc_html_e( 'Edit Album', 'grand-media' ); ?></a>
											<?php } elseif ( ( 'gmedia_category' === $taxonomy ) && $allow_edit ) { ?>
												&nbsp; | &nbsp; <a href=" <?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia_Categories', 'edit_term' => $item->term_id ), admin_url( 'admin.php' ) ) ); ?>" target="_blank"><?php esc_html_e( 'Edit Category', 'grand-media' ); ?></a>
											<?php } ?>
										</p>
									</div>
									<?php
									/*
									if( ! empty( $item->description ) ) { ?>
										<div class="term-description"><?php echo esc_html( nl2br( $item->description ) ); ?></div>
									<?php }*/
									?>
								</div>
								<?php
							}
						} else {
							?>
							<div class="list-group-item">
								<div class="well well-lg text-center">
									<h4><?php esc_html_e( 'No items to show.', 'grand-media' ); ?></h4>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="col-sm-5 col-md-3 media-upload-sidebar">
					<form method="post" id="gmedia-form" role="form">
						<div class="media-upload-form-container">
							<div class="form-group">
								<label><?php esc_html_e( 'Overwrite Module/Preset', 'grand-media' ); ?></label>
								<select class="form-control input-sm" id="module_preset" name="module_preset">
									<option value=""><?php esc_html_e( 'Do not overwrite', 'grand-media' ); ?></option>
									<?php
									foreach ( $gmedia_modules['in'] as $mfold => $module ) {
										echo '<optgroup label="' . esc_attr( $module['title'] ) . '">';
										$presets  = $gmDB->get_terms( 'gmedia_module', array( 'status' => $mfold ) );
										$option   = array();
										$option[] = '<option value="' . esc_attr( $mfold ) . '">' . esc_html( $module['title'] ) . ' - ' . esc_html__( 'Default Settings' ) . '</option>';
										foreach ( $presets as $preset ) {
											if ( ! (int) $preset->global && '[' . $mfold . ']' === $preset->name ) {
												continue;
											}
											$by_author = '';
											if ( (int) $preset->global ) {
												$by_author = ' [' . get_the_author_meta( 'display_name', $preset->global ) . ']';
											}
											if ( '[' . $mfold . ']' === $preset->name ) {
												$option[] = '<option value="' . intval( $preset->term_id ) . '">' . esc_html( $module['title'] . $by_author ) . ' - ' . esc_html__( 'Default Settings' ) . '</option>';
											} else {
												$preset_name = str_replace( '[' . $mfold . '] ', '', $preset->name );
												$option[]    = '<option value="' . intval( $preset->term_id ) . '">' . esc_html( $module['title'] . $by_author . ' - ' . $preset_name ) . '</option>';
											}
										}
										echo wp_kses( implode( '', $option ), $gm_allowed_tags );
										echo '</optgroup>';
									}
									?>
								</select>
								<p class="help-block"><?php esc_html_e( 'Overwrite Module/Preset of chosen term via shortcode parameters. Create Presets on Modules page or while edit/create some galleries.', 'grand-media' ); ?></p>
							</div>
							<div id="media-upload-form-container"></div>
						</div>
						<div class="card-footer">
							<input type="hidden" name="post_id" id="post_id" value="<?php echo absint( $post_id ); ?>"/>
							<?php wp_nonce_field( 'media-form' ); ?>
							<button type="submit" id="media-upload-form-submit" disabled class="btn btn-primary float-end" name="gmedia_term_insert"><?php esc_html_e( /* @lang text */ 'Insert into post', 'grand-media' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<script type="text/javascript">
					jQuery( function( $ ) {
						function divFrame() {
							$( '.card-body' ).css( {top: $( '.card-header' ).outerHeight()} );
						}

						divFrame();
						$( window ).on( 'resize', function() {
							divFrame();
						} );
						$( '.term-list-item' ).on( 'click', function() {
							$( this ).addClass( 'gm-selected' ).siblings().removeClass( 'gm-selected' );
							var info = $( '.term-info', this ).clone();
							$( '#media-upload-form-container' ).html( info.html() );
							$( '#media-upload-form-submit' ).prop( 'disabled', false );
						} );
						/*$('#module_preset').on('change', function () {
							 if ($(this).val() && $('#media-upload-form-container').text()) {
							    $('#media-upload-form-submit').prop('disabled', false);
							 } else {
							    $('#media-upload-form-submit').prop('disabled', true);
							 }
						 });*/
					} );
		</script>
	</div>
	<?php
}


function gmedia_add_media_library() {

	global $user_ID, $gmCore, $gmDB, $gm_allowed_tags;

	wp_enqueue_style( 'gmedia-bootstrap' );
	wp_enqueue_script( 'gmedia-bootstrap' );

	wp_enqueue_style( 'grand-media' );
	wp_enqueue_script( 'grand-media' );

	$post_id = intval( $gmCore->_get( 'post_id' ) );

	if ( current_user_can( 'gmedia_show_others_media' ) ) {
		$author = 0;
	} else {
		$author = $user_ID;
	}
	$args        = array(
		'mime_type' => $gmCore->_get( 'mime_type', 'image/*' ),
		'author'    => $author,
		'orderby'   => 'ID',
		'order'     => 'DESC',
		'per_page'  => 50,
		'page'      => $gmCore->_get( 'pager', 1 ),
		's'         => $gmCore->_get( 's', null ),
	);
	$gmediaQuery = $gmDB->get_gmedias( $args );

	?>

	<div class="card m-0 mw-100 min-vh-100 p-0" id="gmedia-container">
		<div class="card-header bg-light clearfix">
			<?php include GMEDIA_ABSPATH . 'admin/tpl/search-form.php'; ?>
			<div class="float-end">
				<?php echo wp_kses( $gmDB->query_pager(), $gm_allowed_tags ); ?>
			</div>
		</div>
		<div class="card-body p-0" id="gm-list-table">
			<div class="row m-0">
				<div class="col-sm-7 col-md-9" style="text-align:justify;white-space:normal;">
					<?php
					if ( count( $gmediaQuery ) ) {
						foreach ( $gmediaQuery as $item ) {
							gmedia_item_more_data( $item );
							?>
							<form class="img-thumbnail" id="list-item-<?php echo absint( $item->ID ); ?>" data-id="<?php echo absint( $item->ID ); ?>" data-type="<?php echo esc_attr( $item->type ); ?>">
								<img src="<?php echo esc_url( $gmCore->gm_get_media_image( $item, 'thumb' ) ); ?>" style="height:100px;width:auto;" alt=""/>
								<span class="fa-solid fa-check text-success"></span>

								<div class="media-upload-form" style="display:none;">
									<input name="ID" type="hidden" value="<?php echo absint( $item->ID ); ?>"/>

									<div class="form-group">
										<label><?php esc_html_e( 'Title', 'grand-media' ); ?></label>
										<input name="title" type="text" class="form-control input-sm" placeholder="<?php esc_attr_e( 'Title', 'grand-media' ); ?>" value="<?php echo esc_attr( $item->title ); ?>">
									</div>
									<div class="form-group">
										<label><?php esc_html_e( 'Link To', 'grand-media' ); ?></label>
										<select id="gmedia_url" class="form-control input-sm" style="display:block;margin-bottom:5px;">
											<option value="customurl" selected="selected"><?php esc_html_e( 'Custom URL' ); ?></option>
											<option value="weburl"><?php esc_html_e( 'Web size image' ); ?></option>
											<?php if ( $item->path_original ) { ?>
												<option value="originalurl"><?php esc_html_e( 'Original image' ); ?></option>
											<?php } ?>
										</select>
										<input name="link" type="text" class="customurl form-control input-sm" value="<?php echo esc_attr( $item->link ); ?>" placeholder="https://"/>
										<input name="link" type="text" style="display:none;font-size:80%;" readonly="readonly" disabled="disabled" class="weburl form-control input-sm" value="<?php echo esc_attr( $item->url_web ); ?>"/>
										<?php if ( $item->path_original ) { ?>
											<input name="link" type="text" style="display:none;font-size:80%;" readonly="readonly" disabled="disabled" class="originalurl form-control input-sm" value="<?php echo esc_attr( $item->url_original ); ?>"/>
										<?php } ?>
									</div>
									<div class="form-group">
										<label><?php esc_html_e( 'Description', 'grand-media' ); ?></label>
										<textarea name="description" class="form-control input-sm" rows="4" cols="10"><?php echo esc_textarea( $item->description ); ?></textarea>
									</div>
									<?php
									if ( 'image' === $item->type ) {
										$_metadata = $item->meta['_metadata'][0];
										?>
										<div class="form-group">
											<label><?php esc_html_e( 'Size', 'grand-media' ); ?></label>
											<select name="size" class="form-control input-sm">
												<option value="thumb"><?php echo 'Thumb - ' . intval( $_metadata['thumb']['width'] ) . ' × ' . intval( $_metadata['thumb']['height'] ); ?></option>
												<option value="web" selected="selected"><?php echo 'Web - ' . intval( $_metadata['web']['width'] ) . ' × ' . intval( $_metadata['web']['height'] ); ?></option>
												<?php if ( $item->path_original ) { ?>
													<option value="original"><?php echo 'Original - ' . intval( $_metadata['original']['width'] ) . ' × ' . intval( $_metadata['original']['height'] ); ?></option>
												<?php } ?>
											</select>
										</div>
									<?php } ?>
									<div class="form-group">
										<label><?php esc_html_e( 'Alignment', 'grand-media' ); ?></label>
										<select name="align" class="form-control input-sm">
											<option value="none" selected="selected"><?php esc_html_e( 'None', 'grand-media' ); ?></option>
											<option value="left"><?php esc_html_e( 'Left', 'grand-media' ); ?></option>
											<option value="center"><?php esc_html_e( 'Center', 'grand-media' ); ?></option>
											<option value="right"><?php esc_html_e( 'Right', 'grand-media' ); ?></option>
										</select>
									</div>
								</div>
							</form>
							<?php
						}
					} else {
						?>
						<div class="list-group-item">
							<div class="well well-lg text-center">
								<h4><?php esc_html_e( 'No items to show.', 'grand-media' ); ?></h4>
								<?php if ( $gmCore->caps['gmedia_upload'] ) { ?>
									<p><a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=GrandMedia_AddMedia' ) ); ?>" class="btn btn-success">
											<i class='fa-solid fa-plus'></i> <?php esc_html_e( 'Add Media', 'grand-media' ); ?>
										</a></p>
								<?php } ?>
							</div>
						</div>
					<?php } ?>
				</div>
				<div class="col-sm-5 col-md-3 media-upload-sidebar">
					<form method="post" id="gmedia-form" role="form">
						<div id="media-upload-form-container" class="media-upload-form-container"></div>
						<div class="card-footer">
							<input type="hidden" name="post_id" id="post_id" value="<?php echo absint( $post_id ); ?>"/>
							<?php wp_nonce_field( 'media-form' ); ?>
							<button type="submit" id="media-upload-form-submit" disabled class="btn btn-primary float-end" name="gmedia_library_insert"><?php esc_html_e( /* @lang text */ 'Insert into post', 'grand-media' ); ?></button>
							<?php if ( $post_id && current_theme_supports( 'post-thumbnails', get_post_type( $post_id ) ) ) { ?>
								<a id="gmedia-post-thumbnail" class="btn disabled" href="javascript:void(0);"><?php esc_html_e( 'Use as featured image', 'grand-media' ); ?></a>
							<?php } ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!--suppress JSUnresolvedVariable -->
		<script type="text/javascript">
					jQuery( function( $ ) {
						function divFrame() {
							$( '.card-body' ).css( {top: $( '.card-header' ).outerHeight()} );
						}

						divFrame();
						$( window ).on( 'resize', function() {
							divFrame();
						} );
						$( '.img-thumbnail' ).on( 'click', function() {
							var form = $( '#media-upload-form-container' );
							var but = $( '.card-footer .btn' );
							if ( $( this ).hasClass( 'active' ) ) {
								$( this ).removeClass( 'active' );
								form.empty();
								but.prop( 'disabled', true ).addClass( 'disabled' );
								return;
							}
							$( this ).addClass( 'active' ).siblings().removeClass( 'active' );
							form.html( $( '.media-upload-form', this ).html() );
							but.prop( 'disabled', false ).removeClass( 'disabled' );
						} );
						$( '#gmedia-form' ).on( 'change', '#gmedia_url', function() {
							var val = $( this ).val();
							$( this ).nextAll( 'input.' + val ).show().prop( 'disabled', false ).siblings( 'input' ).hide().prop( 'disabled', true );
						} );

			  <?php
			  if ( $post_id && current_theme_supports( 'post-thumbnails', get_post_type( $post_id ) ) ) {
			  $featured_nonce = wp_create_nonce( "set_post_thumbnail-$post_id" );
			  ?>

						var win = window.dialogArguments || opener || parent || top;
						if ( win.WPSetThumbnailID ) {
							$( '#gmedia-post-thumbnail' ).on( 'click', function() {
								if ( $( this ).hasClass( 'disabled' ) ) {
									return false;
								}
								var id = $( 'form.active' ).data( 'id' );
								$.post( ajaxurl, {
										action: 'gmedia_set_post_thumbnail', post_id: '<?php echo absint( $post_id ); ?>', img_id: id, _wpnonce: '<?php echo esc_js( $featured_nonce ); ?>'
									}, function( str ) {
										var win = window.dialogArguments || opener || parent || top;
										if ( str === '0' ) {
											alert( win.setPostThumbnailL10n.error );
										} else if ( str === '-1' ) {
											// image removed
										} else {
											win.WPSetThumbnailID( id );
											win.WPSetThumbnailHTML( str );
										}
										$( '#__gm-uploader', win.document ).css( 'display', 'none' );
									}
								);
							} );
						} else {
							$( '#gmedia-post-thumbnail' ).remove();
						}

			  <?php
			  }
			  ?>
					} );
		</script>
	</div>
	<?php
}

function gmedia_add_media_upload() {

	global $gmCore, $gmDB, $gmProcessor, $user_ID, $gm_allowed_tags;

	if ( ! current_user_can( 'gmedia_upload' ) ) {
		esc_html_e( 'You do not have permissions to upload media', 'grand-media' );

		return;
	}

	$maxupsize       = wp_max_upload_size();
	$maxupsize_mb    = floor( $maxupsize / 1024 / 1024 );
	$maxchunksize    = floor( $maxupsize * 0.9 );
	$maxchunksize_mb = floor( $maxupsize_mb * 0.9 );

	$gm_screen_options = $gmProcessor->user_options;

	?>
	<div class="card m-0 mw-100 min-vh-100 p-0">
		<div class="card-body p-0" style="top:0">
			<form class="row m-0" id="gmUpload" name="upload_form" method="POST" accept-charset="utf-8" onsubmit="return false;">
				<div class="col-md-8 col-md-push-4" id="pluploadUploader" style="padding: 0;">
					<p><?php esc_html_e( 'You browser doesn\'t have Flash or HTML5 support. Check also if page have no JavaScript errors.', 'grand-media' ); ?></p>
					<?php
					$mime_types = get_allowed_mime_types( $user_ID );
					$type_ext   = array();
					$filters    = array();
					foreach ( $mime_types as $ext => $mime ) {
						$type                = strtok( $mime, '/' );
						$type_ext[ $type ][] = $ext;
					}
					foreach ( $type_ext as $filter => $ext ) {
						$filters[] = array(
							'title'      => $filter,
							'extensions' => str_replace( '|', ',', implode( ',', $ext ) ),
						);
					}
					?>
					<script type="text/javascript">
											// Convert divs to queue widgets when the DOM is ready
											jQuery( function( $ ) {
												//noinspection JSDuplicatedDeclaration
												function gmediaInitUploader() {
													$( '#pluploadUploader' ).pluploadQueue( {
							  <?php if ( 'auto' !== $gm_screen_options['uploader_runtime'] ) { ?>
														runtimes: '<?php echo esc_js( $gm_screen_options['uploader_runtime'] ); ?>',
							  <?php } ?>
														url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							  <?php if ( ( 'true' === $gm_screen_options['uploader_urlstream_upload'] ) && ( 'html4' !== $gm_screen_options['uploader_runtime'] ) ) { ?>
														urlstream_upload: true,
														multipart: false,
							  <?php } else { ?>
														multipart: true,
							  <?php } ?>
														multipart_params: {action: 'gmedia_upload_handler', _wpnonce_upload: '<?php echo esc_js( wp_create_nonce( 'gmedia_upload' ) ); ?>', params: ''},
							  <?php if ( 'true' === $gm_screen_options['uploader_chunking'] && ( 'html4' !== $gm_screen_options['uploader_runtime'] ) ) { ?>
														max_file_size: '2000Mb',
														chunk_size: <?php echo (int) min( $maxchunksize, $gm_screen_options['uploader_chunk_size'] * 1024 * 1024 ); ?>,
							  <?php } else { ?>
														max_file_size: <?php echo (int) $maxupsize; ?>,
							  <?php } ?>
														max_retries: 2,
														unique_names: false,
														rename: true,
														sortable: true,
														dragdrop: true,
														views: {
															list: true,
															thumbs: true,
															active: 'thumbs'
														},
														filters: <?php echo wp_json_encode( $filters ); ?>,
													} );
													var closebtn = '<button type="button" class="btn-close float-end m-0" data-bs-dismiss="alert" aria-label="Close"></button>';
													var resetbtn = '<a href="#" class="plupload_reset" style="display: inline-block;"><i class="fa-solid fa-arrow-rotate-right"></i></a>';
													var uploader = $( '#pluploadUploader' ).pluploadQueue();
													uploader.bind( 'PostInit', function( up ) {
														$( '.plupload_filelist_footer .plupload_file_action' ).html( resetbtn ).on( 'click', '.plupload_reset', function( e ) {
															e.preventDefault();
															up.removeAllEventListeners();
															up.destroy();
															gmediaInitUploader();
														} );
													} );
													uploader.bind( 'BeforeUpload', function( up, file ) {
														up.settings.multipart_params.params = jQuery( '#uploader_multipart_params :input' ).serialize();
													} );
													uploader.bind( 'ChunkUploaded', function( up, file, info ) {
														console.log( '[ChunkUploaded] File:', file, 'Info:', info );
														var response = $.parseJSON( info.response );
														if ( response && response.error ) {
															up.stop();
															file.status = plupload.FAILED;
															$( '<div></div>' ).addClass( 'alert alert-danger alert-dismissable' ).html( closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message ).appendTo( '#gmedia-msg-panel' );
															console.log( response.error );
															up.trigger( 'QueueChanged StateChanged' );
															up.trigger( 'UploadProgress', file );
															up.start();
														}
													} );
													uploader.bind( 'FileUploaded', function( up, file, info ) {
														console.log( '[FileUploaded] File:', file, 'Info:', info );
														var response = jQuery.parseJSON( info.response );
														if ( response && response.error ) {
															file.status = plupload.FAILED;
															$( '<div></div>' ).addClass( 'alert alert-danger alert-dismissable' ).html( closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message ).appendTo( '#gmedia-msg-panel' );
															console.log( response.error );
														}
													} );
													uploader.bind( 'UploadProgress', function( up, file ) {
														var percent = uploader.total.percent;
														$( '#total-progress-info .progress-bar' ).css( 'width', percent + '%' ).attr( 'aria-valuenow', percent );
													} );
													uploader.bind( 'Error', function( up, args ) {
														console.log( '[Error] ', args );
														$( '<div></div>' ).addClass( 'alert alert-danger alert-dismissable' ).html( closebtn + '<strong>' + args.file.name + ':</strong> ' + args.message + ' ' + args.status ).appendTo( '#gmedia-msg-panel' );
													} );
													uploader.bind( 'UploadComplete', function( up, files ) {
														console.log( '[UploadComplete]', files );
														$( '<div></div>' ).addClass( 'alert alert-success alert-dismissable' ).html( closebtn + "<?php esc_attr_e( 'Upload finished', 'grand-media' ); ?>" ).appendTo( '#gmedia-msg-panel' );
														$( '#total-progress-info .progress-bar' ).css( 'width', '0' ).attr( 'aria-valuenow', '0' );
													} );
												}

												gmediaInitUploader();

											} );
					</script>
				</div>
				<div class="col-md-4 col-md-pull-8" id="uploader_multipart_params">
					<div id="gmedia-msg-panel"></div>
					<br/>
					<?php if ( 'false' === $gm_screen_options['uploader_chunking'] || ( 'html4' === $gm_screen_options['uploader_runtime'] ) ) { ?>
						<p class="clearfix text-end"><span class="badge badge-info label-default"><?php echo esc_html( __( 'Maximum file size', 'grand-media' ) . ": {$maxupsize_mb}Mb" ); ?></span></p>
					<?php } else { ?>
						<p class="clearfix text-end hidden">
							<span class="badge badge-info label-default"><?php echo esc_html( __( 'Maximum $_POST size', 'grand-media' ) . ": {$maxupsize_mb}Mb" ); ?></span>
							<span class="badge badge-info label-default"><?php echo esc_html( __( 'Chunk size', 'grand-media' ) . ': ' . min( $maxchunksize_mb, $gm_screen_options['uploader_chunk_size'] ) . 'Mb' ); ?></span>
						</p>
					<?php } ?>

					<div class="form-group">
						<label><?php esc_html_e( 'Title', 'grand-media' ); ?></label>
						<select name="set_title" class="form-control input-sm">
							<option value="exif"><?php esc_html_e( 'EXIF or File Name', 'grand-media' ); ?></option>
							<option value="filename"><?php esc_html_e( 'File Name', 'grand-media' ); ?></option>
							<option value="empty"><?php esc_html_e( 'Empty', 'grand-media' ); ?></option>
						</select>
					</div>
					<div class="form-group">
						<label><?php esc_html_e( 'Status', 'grand-media' ); ?></label>
						<select name="set_status" class="form-control input-sm">
							<option value="inherit"><?php esc_html_e( 'Same as Album or Public', 'grand-media' ); ?></option>
							<option value="publish"><?php esc_html_e( 'Public', 'grand-media' ); ?></option>
							<option value="private"><?php esc_html_e( 'Private', 'grand-media' ); ?></option>
							<option value="draft"><?php esc_html_e( 'Draft', 'grand-media' ); ?></option>
						</select>
					</div>

					<hr/>

					<?php if ( $gmCore->caps['gmedia_terms'] ) { ?>
						<div class="form-group">
							<?php
							$term_type = 'gmedia_album';
							$gm_terms  = $gmDB->get_terms( $term_type, array( 'global' => array( 0, $user_ID ), 'orderby' => 'global_desc_name' ) );

							$terms_album = '';
							if ( count( $gm_terms ) ) {
								foreach ( $gm_terms as $term ) {
									$terms_album .= '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name . ( $term->global ? '' : __( ' (shared)', 'grand-media' ) ) . ( 'publish' === $term->status ? '' : " [{$term->status}]" ) ) . '</option>' . "\n";
								}
							}
							?>
							<label><?php esc_html_e( 'Add to Album', 'grand-media' ); ?> </label>
							<select id="combobox_gmedia_album" name="terms[gmedia_album]" class="form-control input-sm" placeholder="<?php esc_attr_e( 'Album Name...', 'grand-media' ); ?>">
								<option value=""></option>
								<?php echo wp_kses( $terms_album, $gm_allowed_tags ); ?>
							</select>
						</div>

						<div class="form-group">
							<?php
							$term_type    = 'gmedia_category';
							$gm_cat_terms = $gmDB->get_terms( $term_type, array( 'fields' => 'names' ) );
							?>
							<label><?php esc_html_e( 'Assign Categories', 'grand-media' ); ?></label>
							<input id="combobox_gmedia_category" name="terms[gmedia_category]" class="form-control input-sm" value="" placeholder="<?php esc_attr_e( 'Uncategorized', 'grand-media' ); ?>"/>
						</div>

						<div class="form-group">
							<?php
							$term_type    = 'gmedia_tag';
							$gm_tag_terms = $gmDB->get_terms( $term_type, array( 'fields' => 'names' ) );
							?>
							<label><?php esc_html_e( 'Add Tags', 'grand-media' ); ?> </label>
							<input id="combobox_gmedia_tag" name="terms[gmedia_tag]" class="form-control input-sm" value="" placeholder="<?php esc_attr_e( 'Add Tags...', 'grand-media' ); ?>"/>
						</div>
						<div class="addtags-gap">&nbsp;</div>

						<script type="text/javascript">
													jQuery( function( $ ) {
														$( '#combobox_gmedia_album' ).selectize( {
								<?php if ( $gmCore->caps['gmedia_album_manage'] ) { ?>
															create: true,
															createOnBlur: true,
								<?php } else { ?>
															create: false,
								<?php } ?>
															persist: false
														} );

														var gm_cat_terms = <?php echo wp_json_encode( $gm_cat_terms ); ?>;
														//noinspection JSUnusedAssignment
														var cat_items = gm_cat_terms.map( function( x ) {
															return {item: x};
														} );
														//noinspection JSDuplicatedDeclaration
														$( '#combobox_gmedia_category' ).selectize( {
								<?php if ( $gmCore->caps['gmedia_category_manage'] ) { ?>
															create: function( input ) {
																return {
																	item: input
																};
															},
															createOnBlur: true,
								<?php } else { ?>
															create: false,
								<?php } ?>
															delimiter: ',',
															maxItems: null,
															openOnFocus: true,
															persist: false,
															options: cat_items,
															labelField: 'item',
															valueField: 'item',
															searchField: ['item'],
															hideSelected: true
														} );

														var gm_tag_terms = <?php echo wp_json_encode( $gm_tag_terms ); ?>;
														//noinspection JSUnusedAssignment
														var tag_items = gm_tag_terms.map( function( x ) {
															return {item: x};
														} );
														$( '#combobox_gmedia_tag' ).selectize( {
								<?php if ( $gmCore->caps['gmedia_tag_manage'] ) { ?>
															create: function( input ) {
																return {
																	item: input
																};
															},
															createOnBlur: true,
								<?php } else { ?>
															create: false,
								<?php } ?>
															delimiter: ',',
															maxItems: null,
															openOnFocus: true,
															persist: false,
															options: tag_items,
															labelField: 'item',
															valueField: 'item',
															searchField: ['item'],
															hideSelected: true
														} );
													} );
						</script>
					<?php } else { ?>
						<p><?php esc_html_e( 'You are not allowed to assign terms', 'grand-media' ); ?></p>
					<?php } ?>

					<script type="text/javascript">
											jQuery( function( $ ) {
												$( '#uploader_runtime select' ).change( function() {
													if ( 'html4' === $( this ).val() ) {
														$( '#uploader_chunking' ).addClass( 'hide' );
														$( '#uploader_urlstream_upload' ).addClass( 'hide' );
													} else {
														$( '#uploader_chunking' ).removeClass( 'hide' );
														$( '#uploader_urlstream_upload' ).removeClass( 'hide' );
													}
												} );
											} );
					</script>
				</div>
			</form>
		</div>
	</div>
	<?php
}
