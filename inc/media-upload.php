<?php
if ( preg_match( '#' . basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @title  Add action/filter for the upload tab
 */

if ( current_user_can( 'gmedia_library' ) ) {
	add_filter( 'media_buttons_context', 'gmedia_media_buttons_context', 4 );
	add_filter( 'media_upload_tabs', 'gmedia_upload_tabs' );
	add_action( 'media_upload_gmedia_library', 'media_upload_gmedia' );
	add_action( 'media_upload_gmedia_terms', 'media_upload_gmedia' );
	add_action( 'media_upload_gmedia_galleries', 'media_upload_gmedia' );
}


/**
 * @param $context
 *
 * @return string
 */
function gmedia_media_buttons_context( $context ) {
	$button = '
	<div style="display:inline-block;">
	    <a id="gmedia-modal" title="Gmedia Galleries" class="gmedia_button button" href="#gmedia"><span class="wp-media-buttons-icon" style="background: url(' . plugins_url( GMEDIA_FOLDER . '/admin/images/gm-icon.png' ) . ') no-repeat top left;"></span> ' . __( 'Gmedia', 'grand-media' ) . '</a>
	</div>';

	return $context . $button;
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
		'gmedia_galleries' => __( 'Gmedia Galleries', 'grand-media' )
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
		if ( ( 'upload' == $action ) && current_user_can( 'gmedia_upload' ) ) {
			wp_iframe( 'gmedia_add_media_upload' );
		} else {
			wp_iframe( 'gmedia_add_media_library' );
		}
	}

	// Generate TinyMCE HTML output
	if ( isset( $_POST['gmedia_library_insert'] ) ) {

		$id = $gmCore->_post( 'ID', 0 );

		if ( ( $gmedia = $gmDB->get_gmedia( $id ) ) ) {

			$meta = $gmDB->get_metadata( 'gmedia', $gmedia->ID, '_metadata', true );

			$size    = $gmCore->_post( 'size', 'web' );
			$src     = $gmCore->gm_get_media_image( $gmedia, $size );
			$width   = $meta[ $size ]['width'];
			$height  = $meta[ $size ]['height'];
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
				jQuery('#__gm-uploader', win.document).css('display', 'none');
				/* ]]> */
			</script>
			<?php
			// Return it to TinyMCE
			media_send_to_editor( $html );
		}
	}
	if ( isset( $_POST['gmedia_gallery_insert'] ) ) {
		$sc = $gmCore->_post( 'shortcode' );
		?>
		<script type="text/javascript">
			/* <![CDATA[ */
			var win = window.dialogArguments || opener || parent || top;
			jQuery('#__gm-uploader', win.document).css('display', 'none');
			/* ]]> */
		</script>
		<?php
		// Return it to TinyMCE
		media_send_to_editor( $sc );
	}
	if ( isset( $_POST['gmedia_term_insert'] ) ) {
		$module_preset = $gmCore->_post( 'module_preset' );
		if ( $gmCore->is_digit( $module_preset ) ) {
			$module_preset = $gmDB->get_term( (int) $module_preset, 'gmedia_module' );
			$module        = $module_preset->status;
			$preset        = ' preset=' . $module_preset->term_id;
		} else {
			$module = $module_preset;
			$preset = '';
		}
		$tax     = $gmCore->_post( 'taxonomy' );
		$term_id = $gmCore->_post( 'term_id' );
		if ( $tax && $term_id && $module ) {
			$tax = str_replace( 'gmedia_', '', $tax );
			$sc  = "[gm {$tax}={$term_id} module={$module}{$preset}]";
			?>
			<script type="text/javascript">
				/* <![CDATA[ */
				var win = window.dialogArguments || opener || parent || top;
				jQuery('#__gm-uploader', win.document).css('display', 'none');
				/* ]]> */
			</script>
			<?php
			// Return it to TinyMCE
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
	if ( did_action( 'media_upload_gmedia_library' ) && ( 'upload' == $action ) && current_user_can( 'gmedia_upload' ) ) {
		if ( current_user_can( 'gmedia_terms' ) ) {
			wp_enqueue_style( 'selectize', $gmCore->gmedia_url . '/assets/selectize/selectize.bootstrap3.css', array( 'gmedia-bootstrap' ), '0.8.5', 'screen' );
			wp_enqueue_script( 'selectize', $gmCore->gmedia_url . '/assets/selectize/selectize.min.js', array( 'jquery' ), '0.8.5' );
		}
		wp_enqueue_style( 'jquery-ui-smoothness', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.min.css', array(), '1.10.2', 'screen' );
		wp_enqueue_script( 'jquery-ui-full', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js', array(), '1.10.2' );

		wp_enqueue_script( 'gmedia-plupload', $gmCore->gmedia_url . '/assets/plupload/plupload.full.min.js', array( 'jquery', 'jquery-ui-full' ), '2.1.2' );

		wp_enqueue_style( 'jquery.ui.plupload', $gmCore->gmedia_url . '/assets/plupload/jquery.ui.plupload/css/jquery.ui.plupload.css', array( 'jquery-ui-smoothness' ), '2.1.2', 'screen' );
		wp_enqueue_script( 'jquery.ui.plupload', $gmCore->gmedia_url . '/assets/plupload/jquery.ui.plupload/jquery.ui.plupload.min.js', array( 'gmedia-plupload', 'jquery-ui-full' ), '2.1.2' );
	}

}

function gmedia_add_media_galleries() {

	global $user_ID, $gmCore, $gmDB, $gmGallery, $gmProcessor;

	$post_id = intval( $gmCore->_get( 'post_id' ) );

	$gm_screen_options = get_user_meta( $user_ID, 'gm_screen_options', true );
	if ( ! is_array( $gm_screen_options ) ) {
		$gm_screen_options = array();
	}
	$gm_screen_options = array_merge( $gmGallery->options['gm_screen_options'], $gm_screen_options );
	$orderby           = ! empty( $gm_screen_options['orderby_gmedia_galleries'] ) ? $gm_screen_options['orderby_gmedia_galleries'] : 'name';
	$order             = ! empty( $gm_screen_options['sortorder_gmedia_galleries'] ) ? $gm_screen_options['sortorder_gmedia_galleries'] : 'ASC';
	$per_page          = ! empty( $gm_screen_options['per_page_gmedia_galleries'] ) ? $gm_screen_options['per_page_gmedia_galleries'] : 30;

	$args           = array(
		'orderby'    => $gmCore->_get( 'orderby', $orderby ),
		'order'      => $gmCore->_get( 'order', $order ),
		'search'     => $gmCore->_get( 's', '' ),
		'number'     => $gmCore->_get( 'number', $per_page ),
		'hide_empty' => 0,
		'page'       => $gmCore->_get( 'pager', 1 ),
		'status'     => array( 'public', 'private' )
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
		$alert       = $gmProcessor->alert( 'danger', $gmediaTerms->get_error_message() );
		$gmediaTerms = array();
	}

	$modules = array();
	if ( ( $plugin_modules = glob( GMEDIA_ABSPATH . 'module/*', GLOB_ONLYDIR | GLOB_NOSORT ) ) ) {
		foreach ( $plugin_modules as $path ) {
			$mfold             = basename( $path );
			$modules[ $mfold ] = array(
				'module_name' => $mfold,
				'module_url'  => $gmCore->gmedia_url . "/module/{$mfold}",
				'module_path' => $path
			);
		}
	}
	if ( ( $upload_modules = glob( $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/*', GLOB_ONLYDIR | GLOB_NOSORT ) ) ) {
		foreach ( $upload_modules as $path ) {
			$mfold             = basename( $path );
			$modules[ $mfold ] = array(
				'module_name' => $mfold,
				'module_url'  => $gmCore->upload['url'] . "/{$gmGallery->options['folder']['module']}/{$mfold}",
				'module_path' => $path
			);
		}
	}

	?>

	<div class="panel panel-default" id="gmedia-container">
		<div class="panel-heading clearfix">
			<form class="form-inline gmedia-search-form" role="search" method="get">
				<div class="form-group">
					<?php foreach ( $_GET as $key => $value ) {
						if ( in_array( $key, array( 'chromeless', 'post_id', 'tab', 'orderby', 'order', 'number', 'global' ) ) ) {
							?>
							<input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>"/>
						<?php
						}
					} ?>
					<input id="gmedia-search" class="form-control input-sm" type="text" name="s" placeholder="<?php _e( 'Search...', 'grand-media' ); ?>" value="<?php echo $gmCore->_get( 's', '' ); ?>"/>
				</div>
				<button type="submit" class="btn btn-default input-sm"><span class="glyphicon glyphicon-search"></span></button>
			</form>
			<?php echo $gmDB->query_pager(); ?>

			<div class="btn-group" style="margin-right:20px;">
				<a class="btn btn-primary" target="_blank" href="<?php echo add_query_arg( array( 'page' => 'GrandMedia_Modules' ), admin_url( 'admin.php' ) ); ?>"><?php _e( 'Create New Gallery', 'grand-media' ); ?></a>
			</div>

			<div class="btn-group" style="margin-right:20px;">
				<a class="btn btn-success" href="#" onclick="location.reload(true); return false;"><?php _e( 'Refresh', 'grand-media' ); ?></a>
			</div>

		</div>
		<div class="panel-body" id="gmedia-msg-panel"><?php echo $alert; ?></div>
		<div class="panel-body" id="gm-list-table">
			<div class="row">
				<div class="col-xs-7 col-md-9" style="padding: 0">
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
								if ( file_exists( $module['path'] . '/index.php' ) ) {
									$broken = false;
									include( $module['path'] . '/index.php' );
								} else {
									$broken = true;
								}

								$list_row_class = '';
								if ( 'private' == $term->status ) {
									$list_row_class = ' list-group-item-info';
								}
								?>
								<div class="gmedia-insert-item list-group-item clearfix d-row<?php echo $list_row_class; ?>" id="list-item-<?php echo $term->term_id; ?>" data-id="<?php echo $term->term_id; ?>" data-type="<?php echo $term_meta['_module']; ?>">

									<div class="media-body">
										<p class="media-title">
											<span><?php echo esc_html( $term->name ); ?></span>
										</p>

										<p class="media-meta">
											<span class="label label-default"><?php _e( 'Author', 'grand-media' ); ?>:</span> <?php echo $term->global ? get_the_author_meta( 'display_name', $term->global ) : '&#8212;'; ?>
										</p>

										<p class="media-caption"><?php echo nl2br( esc_html( $term->description ) ); ?></p>
									</div>

									<p class="media-meta hidden" style="font-weight:bold">
										<span class="label label-default"><?php _e( 'Shortcode', 'grand-media' ); ?>:</span> [gmedia id=<?php echo $term->term_id; ?>]
										<input type="hidden" name="shortcode" value="[gmedia id=<?php echo $term->term_id; ?>]"/>
									</p>

									<p class="media-meta clear hidden">
										<span class="clearfix">
											<span class="media-object pull-left" style="width:77px;margin-right:5px;">
												<?php if ( ! $broken ) { ?>
													<span class="thumbnail"><img src="<?php echo $module['url'] . '/screenshot.png'; ?>" alt="<?php echo esc_attr( $term->name ); ?>"/></span>
												<?php } else { ?>
													<span class="bg-danger text-center"><?php _e( 'Module broken. Reinstall module', 'grand-media' ) ?></span>
												<?php } ?>
											</span>
											<span class="label label-default"><?php _e( 'Module', 'grand-media' ); ?>:</span> <?php echo $term_meta['_module']; ?>
											<br><span class="label label-default"><?php _e( 'Type', 'grand-media' ); ?>:</span> <?php echo $module_info['type']; ?>
											<br><span class="label label-default"><?php _e( 'Status', 'grand-media' ); ?>:</span> <?php echo $term->status; ?>
										</span>
										<span class="label label-default"><?php _e( 'Last Edited', 'grand-media' ); ?>:</span> <?php echo $term_meta['_edited']; ?>
										<br><span class="label label-default"><?php _e( 'Source', 'grand-media' ); ?>:</span>
										<?php
										$gallery_tabs = reset( $term_meta['_query'] );
										$tax_tabs     = key( $term_meta['_query'] );
										if ( 'gmedia__in' == $tax_tabs ) {
											_e( 'Selected Gmedia', 'grand-media' );
											if ( ! ( ( (int) $term->global != $user_ID ) && ! current_user_can( 'gmedia_show_others_media' ) ) ) {
												$gmedia_ids = wp_parse_id_list( $gallery_tabs[0] );
												$gal_source = sprintf( '<a class="selected__in" target="_blank" href="%s">' . __( 'Show %d items in Gmedia Library', 'grand-media' ) . '</a>', esc_url( add_query_arg( array( 'gmedia__in' => implode( ',', $gmedia_ids ) ), $lib_url ) ), count( $gmedia_ids ) );
												echo " ($gal_source)";
											}
										} else {
											$tabs         = $gmDB->get_terms( $tax_tabs, array( 'include' => $gallery_tabs ) );
											$terms_source = array();
											if ( 'gmedia_category' == $tax_tabs ) {
												_e( 'Categories', 'grand-media' );
												foreach ( $tabs as $t ) {
													$terms_source[] = sprintf( '<a class="gm_category" target="_blank" href="%s">%s</a>', esc_url( add_query_arg( array( 'cat' => $t->term_id ), $lib_url ) ), esc_html( $gmGallery->options['taxonomies']['gmedia_category'][ $t->name ] ) );
												}
											} elseif ( 'gmedia_album' == $tax_tabs ) {
												_e( 'Albums', 'grand-media' );
												foreach ( $tabs as $t ) {
													$terms_source[] = sprintf( '<a class="gm_album" target="_blank" href="%s">%s</a>', esc_url( add_query_arg( array( 'alb' => $t->term_id ), $lib_url ) ), esc_html( $t->name ) );
												}
											} elseif ( 'gmedia_tag' == $tax_tabs ) {
												_e( 'Tags', 'grand-media' );
												foreach ( $tabs as $t ) {
													$terms_source[] = sprintf( '<a class="gm_tag" target="_blank" href="%s">%s</a>', esc_url( add_query_arg( array( 'tag_id' => $t->term_id ), $lib_url ) ), esc_html( $t->name ) );
												}
											} elseif ( 'gmedia_filter' == $tax_tabs ) {
												_e( 'Filters', 'grand-media' );
												foreach ( $tabs as $t ) {
													$terms_source[] = sprintf( '<a class="gm_filter" target="_blank" href="%s">%s</a>', esc_url( add_query_arg( array( 'stack_id' => $t->term_id ), $lib_url ) ), esc_html( $t->name ) );
												}
											}
											if ( ! empty( $terms_source ) ) {
												$terms_source = join( ', ', $terms_source );
												if ( ( (int) $term->global != $user_ID ) && ! current_user_can( 'gmedia_show_others_media' ) ) {
													$terms_source = strip_tags( $terms_source );
												}
												echo " ({$terms_source})";
											}
										}
										?>
									</p>
									<?php if ( current_user_can( 'gmedia_gallery_manage' ) ) {
										if ( ! ( ( (int) $term->global != $user_ID ) && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
											?>
											<p class="media-meta hidden"><a target="_blank" href="<?php echo add_query_arg( array(
													'page'         => 'GrandMedia_Galleries',
													'edit_gallery' => $term->term_id
												), admin_url( 'admin.php' ) ); ?>"><?php _e( 'Edit gallery', 'grand-media' ); ?></a></p>
										<?php
										}
									} ?>

								</div>
							<?php
							}
						} else {
							?>
							<div class="list-group-item">
								<div class="well well-lg text-center">
									<h4><?php _e( 'No items to show.', 'grand-media' ); ?></h4>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="col-xs-5 col-md-3 media-upload-sidebar">
					<form method="post" id="gmedia-form" role="form">
						<div id="media-upload-form-container" class="media-upload-form-container"></div>
						<div class="panel-footer">
							<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>"/>
							<?php wp_nonce_field( 'media-form' ); ?>
							<button type="submit" id="media-upload-form-submit" disabled class="btn btn-primary pull-right" name="gmedia_gallery_insert"><?php _e( 'Insert into post', 'grand-media' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(function($){
				function divFrame(){
					$('.panel-body').css({top: $('.panel-heading').outerHeight()});
				}

				divFrame();
				$(window).on('resize', function(){
					divFrame();
				});
				$('.gmedia-insert-item').on('click', function(){
					if($(this).hasClass('active-row')){
						$(this).removeClass('active-row');
						$('#media-upload-form-container').empty();
						$('#media-upload-form-submit').prop('disabled', true);
						return;
					}
					$(this).addClass('active-row').siblings().removeClass('active-row');
					var info = $(this).clone();
					info.find('.media-caption').remove().end().find('.hidden').removeClass('hidden');
					$('#media-upload-form-container').html(info.html());
					$('#media-upload-form-submit').prop('disabled', false);
				});
			});
		</script>
	</div>
<?php
}

function gmedia_add_media_terms() {

	global $user_ID, $gmCore, $gmDB, $gmGallery, $gmProcessor;

	$post_id = intval( $gmCore->_get( 'post_id' ) );

	$url = add_query_arg( array( 'post_id' => $post_id, 'tab' => 'gmedia_terms', 'chromeless' => true ), admin_url( 'media-upload.php' ) );

	$gm_screen_options = get_user_meta( $user_ID, 'gm_screen_options', true );
	if ( ! is_array( $gm_screen_options ) ) {
		$gm_screen_options = array();
	}
	$gm_screen_options = array_merge( $gmGallery->options['gm_screen_options'], $gm_screen_options );
	$orderby           = ! empty( $gm_screen_options['orderby_gmedia_terms'] ) ? $gm_screen_options['orderby_gmedia_terms'] : 'name';
	$order             = ! empty( $gm_screen_options['sortorder_gmedia_terms'] ) ? $gm_screen_options['sortorder_gmedia_terms'] : 'ASC';
	$per_page          = ! empty( $gm_screen_options['per_page_gmedia_terms'] ) ? $gm_screen_options['per_page_gmedia_terms'] : 30;
	$search_string     = $gmCore->_get( 's', '' );

	$args           = array(
		'orderby'    => $gmCore->_get( 'orderby', $orderby ),
		'order'      => $gmCore->_get( 'order', $order ),
		'search'     => $search_string,
		'number'     => $gmCore->_get( 'number', $per_page ),
		'hide_empty' => $gmCore->_get( 'hide_empty', 0 ),
		'page'       => $gmCore->_get( 'pager', 1 )
	);
	$args['offset'] = ( $args['page'] - 1 ) * $args['number'];

	$taxonomy = $gmCore->_get( 'term', 'gmedia_album' );
	if ( ! in_array( $taxonomy, array( 'gmedia_album', 'gmedia_tag', 'gmedia_category', 'gmedia_filter' ) ) ) {
		$taxonomy = 'gmedia_album';
	}

	switch ( $taxonomy ) {
		case 'gmedia_album':
			$args['status'] = array( 'public', 'private' );
			$args['global'] = $gmCore->_get( 'author', $gmCore->caps['gmedia_edit_others_media'] ? '' : array( 0, $user_ID ) );
			if ( ! $gmCore->caps['gmedia_show_others_media'] ) {
				$args['global'] = wp_parse_id_list( $args['global'] );
				$args['global'] = array_intersect( array( 0, $user_ID ), $args['global'] );
				if ( empty( $args['global'] ) ) {
					$args['global'] = array( 0, $user_ID );
				}
			}
			break;
		case 'gmedia_tag':
			if ( 'global' == $args['orderby'] ) {
				$args['orderby'] = 'id';
			}
			break;
		case 'gmedia_category':
			$args = array();
			break;
		case 'gmedia_filter':
			$args['hide_empty'] = 0;
			$args['global']     = $gmCore->_get( 'author', $gmCore->caps['gmedia_edit_others_media'] ? '' : array( 0, $user_ID ) );
			if ( ! $gmCore->caps['gmedia_show_others_media'] ) {
				$args['global'] = wp_parse_id_list( $args['global'] );
				$args['global'] = array_intersect( array( 0, $user_ID ), $args['global'] );
				if ( empty( $args['global'] ) ) {
					$args['global'] = array( 0, $user_ID );
				}
			}
			break;
	}

	$gmediaTerms = $gmDB->get_terms( $taxonomy, $args );
	$alert       = '';
	if ( is_wp_error( $gmediaTerms ) ) {
		$alert       = $gmProcessor->alert( 'danger', $gmediaTerms->get_error_message() );
		$gmediaTerms = array();
	}

	$modules = array();
	if ( ( $plugin_modules = glob( GMEDIA_ABSPATH . 'module/*', GLOB_ONLYDIR | GLOB_NOSORT ) ) ) {
		foreach ( $plugin_modules as $path ) {
			if ( ! file_exists( $path . '/index.php' ) ) {
				continue;
			}
			$module_info = array();
			include( $path . '/index.php' );
			if ( empty( $module_info ) ) {
				continue;
			}
			$mfold             = basename( $path );
			$modules[ $mfold ] = array(
				'module_name'  => $mfold,
				'module_title' => $module_info['title'] . ' v' . $module_info['version'],
				'module_url'   => $gmCore->gmedia_url . "/module/{$mfold}",
				'module_path'  => $path
			);
		}
	}
	if ( ( $upload_modules = glob( $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/*', GLOB_ONLYDIR | GLOB_NOSORT ) ) ) {
		foreach ( $upload_modules as $path ) {
			if ( ! file_exists( $path . '/index.php' ) ) {
				continue;
			}
			$module_info = array();
			include( $path . '/index.php' );
			if ( empty( $module_info ) ) {
				continue;
			}
			$mfold             = basename( $path );
			$modules[ $mfold ] = array(
				'module_name'  => $mfold,
				'module_title' => $module_info['title'] . ' v' . $module_info['version'],
				'module_url'   => $gmCore->upload['url'] . "/{$gmGallery->options['folder']['module']}/{$mfold}",
				'module_path'  => $path
			);
		}
	}

	?>

	<div class="panel panel-default">
		<div class="panel-heading clearfix">
			<?php if ( 'gmedia_category' != $taxonomy ) { ?>
				<form class="form-inline gmedia-search-form" role="search" method="get">
					<div class="form-group">
						<?php foreach ( $_GET as $key => $value ) {
							if ( in_array( $key, array( 'chromeless', 'post_id', 'tab', 'orderby', 'order', 'number', 'global' ) ) ) {
								?>
								<input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>"/>
							<?php
							}
						} ?>
						<input id="gmedia-search" class="form-control input-sm" type="text" name="s" placeholder="<?php _e( 'Search...', 'grand-media' ); ?>" value="<?php echo $gmCore->_get( 's', '' ); ?>"/>
					</div>
					<button type="submit" class="btn btn-default input-sm"><span class="glyphicon glyphicon-search"></span></button>
				</form>
				<?php echo $gmDB->query_pager(); ?>
			<?php } ?>

			<div class="btn-group" style="margin-right:20px;">
				<a class="btn btn<?php echo ( 'gmedia_album' == $taxonomy ) ? "-primary active" : '-default'; ?>"
				   href="<?php echo add_query_arg( array( 'term' => 'gmedia_album' ), $url ); ?>"><?php _e( 'Albums', 'grand-media' ); ?></a>
				<a class="btn btn<?php echo ( 'gmedia_tag' == $taxonomy ) ? "-primary active" : '-default'; ?>"
				   href="<?php echo add_query_arg( array( 'term' => 'gmedia_tag' ), $url ); ?>"><?php _e( 'Tags', 'grand-media' ); ?></a>
				<a class="btn btn<?php echo ( 'gmedia_category' == $taxonomy ) ? "-primary active" : '-default'; ?>"
				   href="<?php echo add_query_arg( array( 'term' => 'gmedia_category' ), $url ); ?>"><?php _e( 'Categories', 'grand-media' ); ?></a>
				<a class="btn btn<?php echo ( 'gmedia_filter' == $taxonomy ) ? "-primary active" : '-default'; ?>"
				   href="<?php echo add_query_arg( array( 'term' => 'gmedia_filter' ), $url ); ?>"><?php _e( 'Custom Filters', 'grand-media' ); ?></a>
			</div>

		</div>
		<div class="panel-body" id="gmedia-msg-panel"><?php echo $alert; ?></div>
		<div class="panel-body" id="gm-list-table">
			<div class="row">
				<div class="col-xs-7 col-md-9" style="padding: 0">
					<div class="list-group" id="gm-list-table" style="margin-bottom:4px;">
						<?php
						if ( count( $gmediaTerms ) ) {
							$author           = $gmCore->caps['gmedia_show_others_media'] ? 0 : $user_ID;
							$allow_edit       = $gmCore->caps['gmedia_edit_others_media'];
							$gmediaCategories = $gmGallery->options['taxonomies']['gmedia_category'];
							foreach ( $gmediaTerms as $item ) {
								$author_name    = $owner = '';
								$list_row_class = $row_class = '';
								$termItems      = array();
								$per_page       = 10;
								$item_name      = $item->name;
								if ( 'gmedia_album' == $taxonomy ) {
									if ( $item->global ) {
										$owner = get_the_author_meta( 'display_name', $item->global );
										$author_name .= sprintf( __( 'by %s', 'grand-media' ), $owner );
										if ( $item->global == $user_ID ) {
											$row_class .= ' current_user';
											$allow_edit = $gmCore->caps['gmedia_album_manage'];
										} else {
											$row_class .= ' other_user';
											$allow_edit = $gmCore->caps['gmedia_edit_others_media'];
										}
									} else {
										$owner = '&#8212;';
										$author_name .= '(' . __( 'shared', 'grand-media' ) . ')';
										$row_class .= ' shared';
										$allow_edit = $gmCore->caps['gmedia_edit_others_media'];
									}
									if ( 'public' != $item->status ) {
										$author_name .= ' [' . $item->status . ']';
										if ( 'private' == $item->status ) {
											$list_row_class = ' list-group-item-info';
										} elseif ( 'draft' == $item->status ) {
											//$list_row_class = ' list-group-item-warning';
											continue;
										}
									}
								} elseif ( 'gmedia_filter' == $taxonomy ) {
									if ( $item->global ) {
										if ( ( $owner = get_the_author_meta( 'display_name', $item->global ) ) ) {
											$author_name .= sprintf( __( 'by %s', 'grand-media' ), $owner );
										} else {
											$author_name .= '(' . __( 'deleted author', 'grand-media' ) . ')';
										}
										if ( $item->global == $user_ID ) {
											$row_class .= ' current_user';
											$allow_edit   = $gmCore->caps['gmedia_filter_manage'];
										} else {
											$row_class .= ' other_user';
											$allow_edit   = $gmCore->caps['gmedia_edit_others_media'];
										}
									} else {
										$owner = '&#8212;';
										$author_name .= '(' . __( 'no author', 'grand-media' ) . ')';
										$row_class .= ' shared';
										$allow_edit   = $gmCore->caps['gmedia_edit_others_media'];
									}

									$term_query = $gmDB->get_metadata( 'gmedia_term', $item->term_id, '_query', true );
									if(isset($term_query['per_page']) && $term_query['per_page'] > $per_page){
										$term_query['per_page'] = $per_page;
									}
									$args = array( 'cache_results' => false, 'no_found_rows' => true, 'per_page' => $per_page );
									$args = array_merge($args, $term_query);
									$termItems = $gmDB->get_gmedias( $args );
								} elseif ( 'gmedia_category' == $taxonomy ) {
									$item_name  = $gmediaCategories[ $item_name ];
									$allow_edit = false;
								}

								if ( $item->count ) {
									if ( 'gmedia_album' == $taxonomy ) {
										$term_meta = $gmDB->get_metadata( 'gmedia_term', $item->term_id );
										$term_meta = array_map( 'reset', $term_meta );
										$term_meta = array_merge( array( '_orderby' => 'ID', '_order' => 'DESC' ), $term_meta );
										$args      = array( 'no_found_rows' => true, 'per_page' => $per_page, 'album__in' => array( $item->term_id ), 'author' => $author, 'orderby' => $term_meta['_orderby'], 'order' => $term_meta['_order'] );
									} elseif ( 'gmedia_tag' == $taxonomy ) {
										$args = array(
											'no_found_rows' => true,
											'per_page'      => $per_page,
											'tag_id'        => $item->term_id,
											'author'        => $author,
											'orderby'       => $gmGallery->options['in_tag_orderby'],
											'order'         => $gmGallery->options['in_tag_order']
										);
									} elseif ( 'gmedia_category' == $taxonomy ) {
										$args = array(
											'no_found_rows' => true,
											'per_page'      => $per_page,
											'category__in'  => $item->term_id,
											'author'        => $author,
											'orderby'       => $gmGallery->options['in_category_orderby'],
											'order'         => $gmGallery->options['in_category_order']
										);
									}
									$termItems = $gmDB->get_gmedias( $args );
								}
								?>
								<div class="list-group-item term-list-item d-row<?php echo $list_row_class; ?>">
									<div class="row<?php echo $row_class; ?>">
										<div class="term_id">#<?php echo $item->term_id; ?></div>
										<div class="col-xs-5 term-label">
											<div class="no-checkbox">
												<strong class="term_name"><?php echo esc_html( $item_name ); ?></strong>
												<span class="term_info_author"><?php echo $author_name; ?></span>
												<span class="badge pull-right"><?php echo $item->count; ?></span>
											</div>
										</div>
										<div class="col-xs-7">
											<div class="term-images">
												<?php if ( ! empty( $termItems ) ) {
													foreach ( $termItems as $i ) {
														?>
														<img style="z-index:<?php echo $per_page --; ?>;"
														     src="<?php echo $gmCore->gm_get_media_image( $i, 'thumb', false ); ?>"
														     alt="<?php echo $i->ID; ?>"
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
											'gmedia_filter'   => __( 'Custom Filter', 'grand-media' )
										);
										$lib_arg   = array(
											'gmedia_album'    => 'alb',
											'gmedia_tag'      => 'tag_id',
											'gmedia_category' => 'cat',
											'gmedia_filter'   => 'custom_filter'
										);
										?>
										<input type="hidden" name="taxonomy" value="<?php echo $taxonomy; ?>"/>
										<input type="hidden" name="term_id" value="<?php echo $item->term_id; ?>"/>
										<p><strong><?php echo $tax_name[ $taxonomy ]; ?>:</strong> <?php echo esc_html( $item_name ); ?>
											<br/><strong><?php _e( 'ID', 'grand-media' ); ?>:</strong> <?php echo $item->term_id; ?>
											<?php if ( 'gmedia_album' == $taxonomy ) {
												$orderby = array(
													'custom'   => __( 'user defined', 'grand-media' ),
													'ID'       => __( 'by ID', 'grand-media' ),
													'title'    => __( 'by title', 'grand-media' ),
													'date'     => __( 'by date', 'grand-media' ),
													'modified' => __( 'by last modified date', 'grand-media' ),
													'rand'     => __( 'Random', 'grand-media' )
												); ?>
												<br/><strong><?php _e( 'Order', 'grand-media' ); ?>:</strong> <?php echo $orderby[ $term_meta['_orderby'] ]; ?>
												<br/><strong><?php _e( 'Sort order', 'grand-media' ); ?>:</strong> <?php echo $term_meta['_order']; ?>
												<br/><strong><?php _e( 'Status', 'grand-media' ); ?>:</strong> <?php echo $item->status; ?>
												<br/><strong><?php _e( 'Author', 'grand-media' ); ?>:</strong> <?php echo $owner; ?>
											<?php } ?>
										</p>
										<p>
											<a href="<?php echo add_query_arg( array( 'page'                => 'GrandMedia',
											                                          $lib_arg[ $taxonomy ] => $item->term_id
											), admin_url( 'admin.php' ) ); ?>" target="_blank"><?php _e( 'Open in Gmedia Library', 'grand-media' ); ?></a>
											<?php if ( ( 'gmedia_album' == $taxonomy ) && $allow_edit ) { ?>
												&nbsp; | &nbsp; <a href="<?php echo add_query_arg( array(
													'page'       => 'GrandMedia_Terms',
													'edit_album' => $item->term_id
												), admin_url( 'admin.php' ) ); ?>" target="_blank"><?php _e( 'Edit Album', 'grand-media' ); ?></a>
											<?php }
											elseif ( ( 'gmedia_filter' == $taxonomy ) && $allow_edit ) { ?>
												&nbsp; | &nbsp; <a href="<?php echo add_query_arg( array(
													'page'       => 'GrandMedia_Terms',
													'edit_filter' => $item->term_id
												), admin_url( 'admin.php' ) ); ?>" target="_blank"><?php _e( 'Edit Filter', 'grand-media' ); ?></a>
											<?php } ?>
										</p>
									</div>
									<?php if ( ! empty( $item->description ) ) { ?>
										<div class="term-description"><?php echo esc_html( nl2br( $item->description ) ); ?></div>
									<?php } ?>
								</div>
							<?php
							}
						} else {
							?>
							<div class="list-group-item">
								<div class="well well-lg text-center">
									<h4><?php _e( 'No items to show.', 'grand-media' ); ?></h4>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="col-xs-5 col-md-3 media-upload-sidebar">
					<form method="post" id="gmedia-form" role="form">
						<div class="media-upload-form-container">
							<div class="form-group">
								<label><?php _e( 'Choose module/preset', 'grand-media' ); ?></label>
								<select class="form-control input-sm" id="module_preset" name="module_preset" required="required">
									<option value=""><?php _e( 'Choose module/preset', 'grand-media' ); ?></option>
									<?php foreach ( $modules as $mfold => $module ) {
										echo '<optgroup label="' . esc_attr( $module['module_title'] ) . '">';
										$presets           = $gmDB->get_terms( 'gmedia_module', array( 'global' => $user_ID, 'status' => $mfold ) );
										$option            = array();
										$option['default'] = '<option value="' . esc_attr( $mfold ) . '">' . '[' . $mfold . '] ' . __( 'Default Settings' ) . '</option>';
										foreach ( $presets as $preset ) {
											if ( '[' . $mfold . ']' == $preset->name ) {
												$option['default'] = '<option value="' . $preset->term_id . '">' . '[' . $mfold . '] ' . __( 'Default Settings' ) . '</option>';
											} else {
												$option[] = '<option value="' . $preset->term_id . '">' . $preset->name . '</option>';
											}
										}
										echo implode( '', $option );
										echo '</optgroup>';
									} ?>
								</select>
							</div>
							<div id="media-upload-form-container"></div>
						</div>
						<div class="panel-footer">
							<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>"/>
							<?php wp_nonce_field( 'media-form' ); ?>
							<button type="submit" id="media-upload-form-submit" disabled class="btn btn-primary pull-right" name="gmedia_term_insert"><?php _e( 'Insert into post', 'grand-media' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(function($){
				function divFrame(){
					$('.panel-body').css({top: $('.panel-heading').outerHeight()});
				}

				divFrame();
				$(window).on('resize', function(){
					divFrame();
				});
				$('.term-list-item').on('click', function(){
					$(this).addClass('active-row').siblings().removeClass('active-row');
					var info = $('.term-info', this).clone();
					$('#media-upload-form-container').html(info.html());
					if($('#module_preset').val()){
						$('#media-upload-form-submit').prop('disabled', false);
					}
				});
				$('#module_preset').on('change', function(){
					if($(this).val() && $('#media-upload-form-container').text()){
						$('#media-upload-form-submit').prop('disabled', false);
					} else{
						$('#media-upload-form-submit').prop('disabled', true);
					}
				});
			});
		</script>
	</div>
<?php
}


function gmedia_add_media_library() {

	global $user_ID, $gmCore, $gmDB, $gmGallery;

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
		's'         => $gmCore->_get( 's', null )
	);
	$gmediaQuery = $gmDB->get_gmedias( $args );


	?>

	<div class="panel panel-default" id="gmedia-container">
		<div class="panel-heading clearfix">
			<form class="form-inline gmedia-search-form" role="search">
				<div class="form-group">
					<?php foreach ( $_GET as $key => $value ) {
						if ( in_array( $key, array( 'chromeless', 'post_id', 'tab', 'mime_type', 'tag_id', 'tag__in', 'cat', 'category__in', 'alb', 'album__in' ) ) ) {
							?>
							<input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>"/>
						<?php
						}
					} ?>
					<input id="gmedia-search" class="form-control input-sm" type="text" name="s" placeholder="<?php _e( 'Search...', 'grand-media' ); ?>" value="<?php echo $gmCore->_get( 's', '' ); ?>"/>
				</div>
				<button type="submit" class="btn btn-default input-sm"><span class="glyphicon glyphicon-search"></span></button>
			</form>
			<?php echo $gmDB->query_pager(); ?>

		</div>
		<div class="panel-body" id="gm-list-table">
			<div class="row">
				<div class="col-xs-7 col-md-9" style="text-align:justify;">
					<?php
					if ( count( $gmediaQuery ) ) {
						foreach ( $gmediaQuery as $item ) {
							$meta = $gmDB->get_metadata( 'gmedia', $item->ID );
							$type = explode( '/', $item->mime_type );

							/*
							$item_url = $gmCore->upload['url'] . '/' . $gmGallery->options['folder'][$type[0]] . '/' . $item->gmuid;
							$item_path = $gmCore->upload['path'] . '/' . $gmGallery->options['folder'][$type[0]] . '/' . $item->gmuid;

							if (function_exists('exif_imagetype')) {
								$is_webimage = (('image' == $type[0]) && in_array(exif_imagetype($item_path), array(IMAGETYPE_GIF,
																																																		IMAGETYPE_JPEG,
																																																		IMAGETYPE_PNG)))? true : false;
							} else{
								$is_webimage = (('image' == $type[0]) && in_array($type[1], array('jpeg', 'png', 'gif')))? true : false;
							}

							$tags = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_tag');
							$albs = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_album');
							$cats = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_category');
							*/
							?>
							<form class="thumbnail" id="list-item-<?php echo $item->ID; ?>" data-id="<?php echo $item->ID; ?>" data-type="<?php echo $type[0]; ?>">
								<img src="<?php echo $gmCore->gm_get_media_image( $item, 'thumb' ); ?>" style="height:100px;width:auto;" alt=""/>
								<span class="glyphicon glyphicon-ok text-success"></span>

								<div class="media-upload-form" style="display:none;">
									<input name="ID" type="hidden" value="<?php echo $item->ID; ?>"/>

									<div class="form-group">
										<label><?php _e( 'Title', 'grand-media' ); ?></label>
										<input name="title" type="text" class="form-control input-sm" placeholder="<?php _e( 'Title', 'grand-media' ); ?>" value="<?php echo esc_attr( $item->title ); ?>">
									</div>
									<div class="form-group">
										<label><?php _e( 'Link To', 'grand-media' ); ?></label>
										<select id="gmedia_url" class="form-control input-sm" style="display:block;margin-bottom:5px;">
											<option value="customurl" selected="selected"><?php _e( 'Custom URL' ); ?></option>
											<option value="weburl"><?php _e( 'Web size image' ); ?></option>
											<option value="originalurl"><?php _e( 'Original image' ); ?></option>
										</select>
										<input name="link" type="text" class="customurl form-control input-sm" value="<?php echo $item->link; ?>" placeholder="http://"/>
										<input name="link" type="text" style="display:none;font-size:80%;" readonly="readonly" disabled="disabled" class="weburl form-control input-sm" value="<?php echo $gmCore->upload['url'] . '/' . $gmGallery->options['folder']['image'] . '/' . $item->gmuid; ?>"/>
										<input name="link" type="text" style="display:none;font-size:80%;" readonly="readonly" disabled="disabled" class="originalurl form-control input-sm" value="<?php echo $gmCore->upload['url'] . '/' . $gmGallery->options['folder']['image_original'] . '/' . $item->gmuid; ?>"/>
									</div>
									<div class="form-group">
										<label><?php _e( 'Description', 'grand-media' ); ?></label>
										<textarea name="description" class="form-control input-sm" rows="4" cols="10"><?php echo esc_html( $item->description ); ?></textarea>
									</div>
									<?php //if($is_webimage){
									?>
									<?php if ( 'image' == $type[0] ) {
										//$_metadata = maybe_unserialize($meta['_metadata'][0]);
										$_metadata = $meta['_metadata'][0];
										?>
										<div class="form-group">
											<label><?php _e( 'Size', 'grand-media' ); ?></label>
											<select name="size" class="form-control input-sm">
												<option value="thumb"><?php echo 'Thumb - ' . $_metadata['thumb']['width'] . ' × ' . $_metadata['thumb']['height']; ?></option>
												<option value="web" selected="selected"><?php echo 'Web - ' . $_metadata['web']['width'] . ' × ' . $_metadata['web']['height']; ?></option>
												<option value="original"><?php echo 'Original - ' . $_metadata['original']['width'] . ' × ' . $_metadata['original']['height']; ?></option>
											</select>
										</div>
									<?php } ?>
									<?php //}
									?>
									<div class="form-group">
										<label><?php _e( 'Alignment', 'grand-media' ); ?></label>
										<select name="align" class="form-control input-sm">
											<option value="none" selected="selected"><?php _e( 'None', 'grand-media' ); ?></option>
											<option value="left"><?php _e( 'Left', 'grand-media' ); ?></option>
											<option value="center"><?php _e( 'Center', 'grand-media' ); ?></option>
											<option value="right"><?php _e( 'Right', 'grand-media' ); ?></option>
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
								<h4><?php _e( 'No items to show.', 'grand-media' ); ?></h4>
								<?php if ( $gmCore->caps['gmedia_upload'] ) { ?>
									<p>
										<a target="_blank" href="<?php echo admin_url( 'admin.php?page=GrandMedia_AddMedia' ) ?>" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> <?php _e( 'Add Media', 'grand-media' ); ?>
										</a></p>
								<?php } ?>
							</div>
						</div>
					<?php } ?>
				</div>
				<div class="col-xs-5 col-md-3 media-upload-sidebar">
					<form method="post" id="gmedia-form" role="form">
						<div id="media-upload-form-container" class="media-upload-form-container"></div>
						<div class="panel-footer">
							<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>"/>
							<?php wp_nonce_field( 'media-form' ); ?>
							<button type="submit" id="media-upload-form-submit" disabled class="btn btn-primary pull-right" name="gmedia_library_insert"><?php _e( 'Insert into post', 'grand-media' ); ?></button>
							<?php if ( $post_id && current_theme_supports( 'post-thumbnails', get_post_type( $post_id ) ) ) { ?>
								<a id="gmedia-post-thumbnail" class="btn disabled" href="javascript:void(0);"><?php _e( 'Use as featured image', 'grand-media' ); ?></a>
							<?php } ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(function($){
				function divFrame(){
					$('.panel-body').css({top: $('.panel-heading').outerHeight()});
				}

				divFrame();
				$(window).on('resize', function(){
					divFrame();
				});
				$('.thumbnail').on('click', function(){
					var form = $('#media-upload-form-container');
					var but = $('.panel-footer .btn');
					if($(this).hasClass('active')){
						$(this).removeClass('active');
						form.empty();
						but.prop('disabled', true).addClass('disabled');
						return;
					}
					$(this).addClass('active').siblings().removeClass('active');
					form.html($('.media-upload-form', this).html());
					but.prop('disabled', false).removeClass('disabled');
				});
				$('#gmedia-form').on('change', '#gmedia_url', function(){
					var val = $(this).val();
					$(this).nextAll('input.' + val).show().prop('disabled', false).siblings('input').hide().prop('disabled', true);
				});

				<?php
				if ( $post_id && current_theme_supports( 'post-thumbnails', get_post_type( $post_id ) ) ){
					$featured_nonce = wp_create_nonce( "set_post_thumbnail-$post_id" );
				?>

				$('#gmedia-post-thumbnail').on('click', function(){
					if($(this).hasClass('disabled')){
						return false;
					}
					var id = $('form.active').data('id');
					$.post(ajaxurl, {
							action: "gmedia_set_post_thumbnail", post_id: '<?php echo $post_id; ?>', img_id: id, _wpnonce: '<?php echo $featured_nonce; ?>'
						}, function(str){
							var win = window.dialogArguments || opener || parent || top;
							if(str == '0'){
								alert(win.setPostThumbnailL10n.error);
							} else if(str == '-1'){
								// image removed
							} else{
								win.WPSetThumbnailID(id);
								win.WPSetThumbnailHTML(str);
							}
							$('#__gm-uploader', win.document).css('display', 'none');
						}
					);
				});

				<?php } ?>
			});
		</script>
	</div>
<?php
}

function gmedia_add_media_upload() {

	global $gmCore, $gmDB, $gmProcessor, $gmGallery, $user_ID;

	if ( ! current_user_can( 'gmedia_upload' ) ) {
		_e( 'You do not have permissions to upload media', 'grand-media' );

		return;
	}

	$maxupsize    = wp_max_upload_size();
	$maxupsize    = floor( $maxupsize * 0.99 );
	$maxupsize_mb = floor( $maxupsize / 1024 / 1024 );

	$gm_screen_options = $gmProcessor->user_options();

	?>
	<div class="panel panel-default">
		<div class="panel-body" style="top:0">
			<form class="row" id="gmUpload" name="upload_form" method="POST" accept-charset="utf-8" onsubmit="return false;">
				<div class="col-md-8 col-md-push-4" id="pluploadUploader" style="padding: 0;">
					<p><?php _e( "You browser doesn't have Flash or HTML5 support. Check also if page have no JavaScript errors.", 'grand-media' ); ?></p>
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
							'extensions' => str_replace( '|', ',', implode( ',', $ext ) )
						);
					}
					?>
					<script type="text/javascript">
						// Convert divs to queue widgets when the DOM is ready
						jQuery(function($){
							$("#pluploadUploader").plupload({
								<?php if('auto' != $gm_screen_options['uploader_runtime']){ ?>
								runtimes: '<?php echo $gm_screen_options['uploader_runtime']; ?>',
								<?php } ?>
								url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
								<?php if(('true' == $gm_screen_options['uploader_urlstream_upload']) && ('html4' != $gm_screen_options['uploader_runtime'])){ ?>
								urlstream_upload: true,
								multipart: false,
								<?php } else{ ?>
								multipart: true,
								<?php } ?>
								multipart_params: {action: 'gmedia_upload_handler', _ajax_nonce: '<?php echo wp_create_nonce('GmediaUpload'); ?>', params: ''},
								<?php if('true' == $gm_screen_options['uploader_chunking'] && ('html4' != $gm_screen_options['uploader_runtime'])){ ?>
								max_file_size: '2000Mb',
								chunk_size: 200000<?php //echo min($maxupsize, $gm_screen_options['uploader_chunk_size']*1024*1024); ?>,
								<?php } else{ ?>
								max_file_size: <?php echo $maxupsize; ?>,
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
								filters: <?php echo json_encode($filters); ?>,
								flash_swf_url: '<?php echo $gmCore->gmedia_url; ?>/assets/plupload/Moxie.swf',
								silverlight_xap_url: '<?php echo $gmCore->gmedia_url; ?>/assets/plupload/Moxie.xap'

							});
							var closebtn = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
							var uploader = $("#pluploadUploader").plupload('getUploader');
							uploader.bind('StateChanged', function(up){
								if(up.state == plupload.STARTED){
									up.settings.multipart_params.params = jQuery('#uploader_multipart_params :input').serialize();
								}
								console.log('[StateChanged]', up.state, up.settings.multipart_params);
							});
							uploader.bind('ChunkUploaded', function(up, file, info){
								console.log('[ChunkUploaded] File:', file, "Info:", info);
								var response = $.parseJSON(info.response);
								if(response && response.error){
									up.stop();
									file.status = plupload.FAILED;
									$('<div/>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message).appendTo('#gmedia-msg-panel');
									console.log(response.error);
									up.trigger('QueueChanged StateChanged');
									up.trigger('UploadProgress', file);
									up.start();
								}
							});
							uploader.bind('FileUploaded', function(up, file, info){
								console.log('[FileUploaded] File:', file, "Info:", info);
								var response = jQuery.parseJSON(info.response);
								if(response && response.error){
									file.status = plupload.FAILED;
									$('<div/>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message).appendTo('#gmedia-msg-panel');
									console.log(response.error);
								}
							});
							uploader.bind('UploadProgress', function(up, file){
								var percent = uploader.total.percent;
								$('#total-progress-info .progress-bar').css('width', percent + "%").attr('aria-valuenow', percent);
							});
							uploader.bind('Error', function(up, args){
								console.log('[Error] ', args);
								$('<div/>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + args.file.name + ':</strong> ' + args.message + ' ' + args.status).appendTo('#gmedia-msg-panel');
							});
							uploader.bind('UploadComplete', function(up, files){
								console.log('[UploadComplete]', files);
								$('<div/>').addClass('alert alert-success alert-dismissable').html(closebtn + "<?php echo esc_attr(__('Upload finished', 'grand-media')); ?>").appendTo('#gmedia-msg-panel');
								$('#total-progress-info .progress-bar').css('width', '0').attr('aria-valuenow', '0');
							});

						});
					</script>
				</div>
				<div class="col-md-4 col-md-pull-8" id="uploader_multipart_params">
					<div id="gmedia-msg-panel"></div>
					<br/>
					<?php if ( 'false' == $gm_screen_options['uploader_chunking'] || ( 'html4' == $gm_screen_options['uploader_runtime'] ) ) { ?>
						<p class="clearfix text-right"><span class="label label-default"><?php echo __( 'Maximum file size', 'grand-media' ) . ": {$maxupsize_mb}Mb"; ?></span></p>
					<?php } else { ?>
						<p class="clearfix text-right hidden">
							<span class="label label-default"><?php echo __( 'Maximum $_POST size', 'grand-media' ) . ": {$maxupsize_mb}Mb"; ?></span>
							<span class="label label-default"><?php echo __( 'Chunk size', 'grand-media' ) . ': ' . min( $maxupsize_mb, $gm_screen_options['uploader_chunk_size'] ) . 'Mb'; ?></span>
						</p>
					<?php } ?>

					<div class="form-group">
						<label><?php _e( 'Title', 'grand-media' ); ?></label>
						<select name="set_title" class="form-control input-sm">
							<option value="exif"><?php _e( 'EXIF or File Name', 'grand-media' ); ?></option>
							<option value="filename"><?php _e( 'File Name', 'grand-media' ); ?></option>
							<option value="empty"><?php _e( 'Empty', 'grand-media' ); ?></option>
						</select>
					</div>
					<div class="form-group">
						<label><?php _e( 'Status', 'grand-media' ); ?></label>
						<select name="set_status" class="form-control input-sm">
							<option value="inherit"><?php _e( 'Same as Album or Public', 'grand-media' ); ?></option>
							<option value="public"><?php _e( 'Public', 'grand-media' ); ?></option>
							<option value="private"><?php _e( 'Private', 'grand-media' ); ?></option>
							<option value="draft"><?php _e( 'Draft', 'grand-media' ); ?></option>
						</select>
					</div>

					<hr/>

					<?php if ( $gmCore->caps['gmedia_terms'] ) { ?>
						<div class="form-group">
							<?php
							$term_type = 'gmedia_category';
							$gm_terms  = $gmGallery->options['taxonomies'][ $term_type ];

							$terms_category = '';
							if ( count( $gm_terms ) ) {
								foreach ( $gm_terms as $term_name => $term_title ) {
									$terms_category .= '<option value="' . $term_name . '">' . esc_html( $term_title ) . '</option>' . "\n";
								}
							}
							?>
							<label><?php _e( 'Assign Category', 'grand-media' ); ?>
								<small><?php _e( '(for images only)' ) ?></small>
							</label>
							<select id="gmedia_category" name="terms[gmedia_category]" class="form-control input-sm">
								<option value=""><?php _e( 'Uncategorized', 'grand-media' ); ?></option>
								<?php echo $terms_category; ?>
							</select>
						</div>
						<div class="form-group">
							<?php
							$term_type = 'gmedia_album';
							$gm_terms  = $gmDB->get_terms( $term_type, array( 'global' => array( 0, $user_ID ), 'orderby' => 'global_desc_name' ) );

							$terms_album = '';
							if ( count( $gm_terms ) ) {
								foreach ( $gm_terms as $term ) {
									$terms_album .= '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . ( $term->global ? '' : __( ' (shared)', 'grand-media' ) ) . ( 'public' == $term->status ? '' : " [{$term->status}]" ) . '</option>' . "\n";
								}
							}
							?>
							<label><?php _e( 'Add to Album', 'grand-media' ); ?> </label>
							<select id="combobox_gmedia_album" name="terms[gmedia_album]" class="form-control input-sm" placeholder="<?php _e( 'Album Name...', 'grand-media' ); ?>">
								<option value=""></option>
								<?php echo $terms_album; ?>
							</select>
						</div>
						<div class="form-group">
							<?php
							$term_type = 'gmedia_tag';
							$gm_terms  = $gmDB->get_terms( $term_type, array( 'fields' => 'names' ) );
							?>
							<label><?php _e( 'Add Tags', 'grand-media' ); ?> </label>
							<input id="combobox_gmedia_tag" name="terms[gmedia_tag]" class="form-control input-sm" value="" placeholder="<?php _e( 'Add Tags...', 'grand-media' ); ?>"/>
						</div>
						<div class="addtags-gap">&nbsp;</div>
					<?php } else { ?>
						<p><?php _e( 'You are not allowed to assign terms', 'grand-media' ) ?></p>
					<?php } ?>

					<script type="text/javascript">
						jQuery(function($){
							<?php if($gmCore->caps['gmedia_terms']){ ?>
							$('#combobox_gmedia_album').selectize({
								<?php if($gmCore->caps['gmedia_album_manage']){ ?>
								create: true,
								createOnBlur: true,
								<?php } else{ ?>
								create: false,
								<?php } ?>
								persist: false
							});
							var gm_terms = <?php echo json_encode($gm_terms); ?>;
							var items = gm_terms.map(function(x){
								return {item: x};
							});
							$('#combobox_gmedia_tag').selectize({
								<?php if($gmCore->caps['gmedia_tag_manage']){ ?>
								create: function(input){
									return {
										item: input
									}
								},
								createOnBlur: true,
								<?php } else{ ?>
								create: false,
								<?php } ?>
								delimiter: ',',
								maxItems: null,
								openOnFocus: false,
								persist: false,
								options: items,
								labelField: 'item',
								valueField: 'item',
								searchField: ['item'],
								hideSelected: true
							});
							<?php } ?>
							$('#uploader_runtime select').change(function(){
								if('html4' == $(this).val()){
									$('#uploader_chunking').addClass('hide');
									$('#uploader_urlstream_upload').addClass('hide');
								} else{
									$('#uploader_chunking').removeClass('hide');
									$('#uploader_urlstream_upload').removeClass('hide');
								}
							});
						});
					</script>
				</div>
			</form>
		</div>
	</div>
<?php
}

