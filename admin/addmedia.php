<?php
if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * gmedia_AddMedia()
 *
 * @return mixed content
 */
function gmedia_AddMedia() {
	global $gmCore;
	$tab         = $gmCore->_get( 'tab', 'upload' );
	$extra_tools = ( defined( 'GMEDIA_IFRAME' ) && GMEDIA_IFRAME ) ? false : true;
	?>
	<div class="panel panel-default">
		<div class="panel-heading clearfix">
			<?php if ( $extra_tools ) { ?>
				<div class="btn-toolbar pull-left">
					<div class="btn-group">
						<a class="btn btn<?php echo ( $tab == 'upload' ) ? '-primary active' : '-default'; ?>" href="<?php echo $gmCore->get_admin_url( array(), array( 'tab' ) );; ?>"><?php _e( 'Upload Files', 'grand-media' ); ?></a>
						<?php if ( $gmCore->caps['gmedia_import'] ) { ?>
							<a class="btn btn<?php echo ( $tab == 'import' ) ? '-primary active' : '-default'; ?>" href="<?php echo $gmCore->get_admin_url( array( 'tab' => 'import' ) ); ?>"><?php _e( 'Import', 'grand-media' ); ?></a><?php } ?>
					</div>
				</div>
			<?php } ?>
			<div id="total-progress-info" class="progress pull-right">
				<?php $msg = '';
				if ( $tab == 'upload' ) {
					$msg = __( 'Add files to the upload queue and click the start button', 'grand-media' );
				}
				if ( $tab == 'import' ) {
					$msg = __( 'Grab files from other sources', 'grand-media' );
				}
				?>
				<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0;">
					<div style="padding: 2px 10px;"><?php echo $msg; ?></div>
				</div>
				<div style="padding: 2px 10px;"><?php echo $msg; ?></div>
			</div>
		</div>
		<div class="panel-body" id="gmedia-msg-panel"></div>
		<div class="container-fluid gmAddMedia">
			<?php
			if ( $tab == 'upload' ) {
				gmedia_upload_files();
			}
			if ( $tab == 'import' ) {
				gmedia_import();
			}
			?>
			<?php wp_original_referer_field( true, 'previous' ); ?>
		</div>
	</div>
<?php
}


function gmedia_upload_files() {
	global $gmCore, $gmDB, $gmProcessor, $gmGallery, $user_ID;

	if ( ! $gmCore->caps['gmedia_upload'] ) {
		_e( 'You do not have permissions to upload media', 'grand-media' );

		return;
	}

	$maxupsize    = wp_max_upload_size();
	$maxupsize    = floor( $maxupsize * 0.99 );
	$maxupsize_mb = floor( $maxupsize / 1024 / 1024 );

	$gm_screen_options = $gmProcessor->user_options();

	?>
	<form class="row" id="gmUpload" name="upload_form" method="POST" accept-charset="utf-8" onsubmit="return false;">
	<div class="col-md-4" id="uploader_multipart_params">
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
					<small><?php _e( '(for images only)', 'grand-media' ) ?></small>
				</label>
				<select id="gmedia_category" name="terms[gmedia_category]" class="form-control input-sm">
					<option value=""><?php _e( 'Uncategorized', 'grand-media' ); ?></option>
					<?php echo $terms_category; ?>
				</select>
			</div>
			<div class="form-group">
				<?php
				$term_type = 'gmedia_album';
				$global = $gmCore->caps['gmedia_edit_others_media'] ? '' : array( 0, $user_ID );
				$gm_terms  = $gmDB->get_terms( $term_type, array( 'global' => $global, 'orderby' => 'global_desc_name' ) );

				$terms_album = '';
				if ( count( $gm_terms ) ) {
					foreach ( $gm_terms as $term ) {
						$author_name = '';
						if ( $term->global ) {
							if ( $gmCore->caps['gmedia_edit_others_media'] ) {
								$author_name .= ' &nbsp; ' . sprintf( __( 'by %s', 'grand-media' ), get_the_author_meta( 'display_name', $term->global ) );
							}
						} else {
							$author_name .= ' &nbsp; (' . __( 'shared', 'grand-media' ) . ')';
						}
						if ( 'public' != $term->status ) {
							$author_name .= ' [' . $term->status . ']';
						}
						$terms_album .= '<option value="' . $term->term_id . '" data-name="' . esc_html( $term->name ) . '" data-meta="' . $author_name . '">' . esc_html( $term->name ) . $author_name . '</option>' . "\n";
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
		<?php } else { ?>
			<p><?php _e( 'You are not allowed to assign terms', 'grand-media' ) ?></p>
		<?php } ?>

		<script type="text/javascript">
			jQuery(function ($) {
				<?php if($gmCore->caps['gmedia_terms']){ ?>
				var albums = $('#combobox_gmedia_album');
				var albums_data = $('option', albums);
				albums.selectize({
					<?php if($gmCore->caps['gmedia_album_manage']){ ?>
					create: function (input) {
						return {
							value: input,
							text: input
						}
					},
					createOnBlur: true,
					<?php } else{ ?>
					create: false,
					<?php } ?>
					persist: false,
					render: {
						item: function (item, escape) {
							if (0 === (parseInt(item.value, 10) || 0)) {
								return '<div>' + escape(item.text) + '</div>';
							}
							if (item.$order) {
								var data = $(albums_data[item.$order]).data();
								return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small></div>';
							}
						},
						option: function (item, escape) {
							if (0 === (parseInt(item.value) || 0)) {
								return '<div>' + escape(item.text) + '</div>';
							}
							if (item.$order) {
								var data = $(albums_data[item.$order]).data();
								return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small></div>';
							}
						}
					}
				});
				var gm_terms = <?php echo json_encode($gm_terms); ?>;
				var items = gm_terms.map(function (x) {
					return {item: x};
				});
				$('#combobox_gmedia_tag').selectize({
					<?php if($gmCore->caps['gmedia_tag_manage']){ ?>
					create: function (input) {
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
				$('#uploader_runtime select').change(function () {
					if ('html4' == $(this).val()) {
						$('#uploader_chunking').addClass('hide');
						$('#uploader_urlstream_upload').addClass('hide');
					} else {
						$('#uploader_chunking').removeClass('hide');
						$('#uploader_urlstream_upload').removeClass('hide');
					}
				});
			});
		</script>
	</div>
	<div class="col-md-8" id="pluploadUploader">
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
			jQuery(function ($) {
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
				uploader.bind('StateChanged', function (up) {
					if (up.state == plupload.STARTED) {
						up.settings.multipart_params.params = jQuery('#uploader_multipart_params :input').serialize();
					}
					//console.log('[StateChanged]', up.state, up.settings.multipart_params);
				});
				uploader.bind('ChunkUploaded', function (up, file, info) {
					//console.log('[ChunkUploaded] File:', file, "Info:", info);
					var response = jQuery.parseJSON(info.response);
					if (response && response.error) {
						up.stop();
						file.status = plupload.FAILED;
						//jQuery('<div/>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message).appendTo('#gmedia-msg-panel');
						console.log('[ChunkUploaded] ', response.error);
						up.trigger('QueueChanged StateChanged');
						up.trigger('UploadProgress', file);
						up.start();
					}
				});
				uploader.bind('FileUploaded', function (up, file, info) {
					//console.log('[FileUploaded] File:', file, "Info:", info);
					var response = jQuery.parseJSON(info.response);
					if (response && response.error) {
						file.status = plupload.FAILED;
						jQuery('<div/>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message).appendTo('#gmedia-msg-panel');
						console.log('[FileUploaded] ', response.error);
					}
				});
				uploader.bind('UploadProgress', function (up, file) {
					var percent = uploader.total.percent;
					$('#total-progress-info .progress-bar').css('width', percent + "%").attr('aria-valuenow', percent);
				});
				uploader.bind('Error', function (up, args) {
					console.log('[Error] ', args);
					jQuery('<div/>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + args.file.name + ':</strong> ' + args.message + ' ' + args.status).appendTo('#gmedia-msg-panel');
				});
				uploader.bind('UploadComplete', function (up, files) {
					console.log('[UploadComplete]', files);
					$('#total-progress-info .progress-bar').css('width', '0').attr('aria-valuenow', '0');
				});

			});
		</script>
	</div>
	</form>
<?php
}


function gmedia_import() {
	global $wpdb, $gmCore, $gmGallery, $gmDB, $user_ID;

	if ( ! $gmCore->caps['gmedia_import'] ) {
		_e( 'You do not have permissions to import media', 'grand-media' );

		return;
	}

	$gmediaURL = plugins_url( GMEDIA_FOLDER );
	?>
	<form class="row" id="import_form" name="import_form" target="import_window" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST" accept-charset="utf-8" style="padding:20px 0 10px;">
	<div class="col-md-4">
		<fieldset id="import_params" class="import-params">
			<?php wp_nonce_field( 'GmediaImport' ); ?>
			<input type="hidden" name="action" value="gmedia_import_handler"/>
			<input type="hidden" id="import-action" name="import" value=""/>

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
						<small><?php _e( '(for images only)', 'grand-media' ) ?></small>
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
			<?php } else { ?>
				<p><?php _e( 'You are not allowed to assign terms', 'grand-media' ) ?></p>
			<?php } ?>
			<script type="text/javascript">
				jQuery(function ($) {
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
					var items = gm_terms.map(function (x) {
						return {item: x};
					});
					$('#combobox_gmedia_tag').selectize({
						<?php if($gmCore->caps['gmedia_tag_manage']){ ?>
						create: function (input) {
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
				});
			</script>
		</fieldset>
	</div>

	<div class="col-md-8 tabable">
		<ul class="nav nav-tabs" style="padding:0 10px;">
			<li class="active"><a href="#import_folder" data-toggle="tab"><?php _e( 'Import Server Folder', 'grand-media' ); ?></a></li>
			<?php if ( ( $import['flagallery'] = $wpdb->get_var( "show tables like '{$wpdb->prefix}flag_gallery'" ) ) ) { ?>
				<li><a href="#import_flagallery" data-toggle="tab"><?php _e( 'FlAGallery plugin', 'grand-media' ); ?></a></li>
			<?php
			}
			if ( ( $import['nextgen'] = $wpdb->get_var( "show tables like '{$wpdb->prefix}ngg_gallery'" ) ) ) {
				?>
				<li><a href="#import_nextgen" data-toggle="tab"><?php _e( 'NextGen plugin', 'grand-media' ); ?></a></li>
			<?php } ?>
		</ul>
		<div class="tab-content">
			<fieldset id="import_folder" class="tab-pane active">
				<?php echo "<style type='text/css'>@import url('{$gmediaURL}/assets/jqueryFileTree/jqueryFileTree.css');</style>\n"; ?>
				<?php echo "<script type='text/javascript' src='{$gmediaURL}/assets/jqueryFileTree/jqueryFileTree.js'></script>\n"; ?>
				<input type="hidden" id="folderpath" name="path" value="/"/>

				<div class="tab-inside">
					<h5><?php _e( 'Sever folders', 'grand-media' ) ?>:</h5>

					<div id="file_browser"></div>
				</div>
				<div class="tab-footer">
					<div class="checkbox pull-left"><label><input type="checkbox" name="delete_source" value="1"/> <?php _e( 'delete source files after importing', 'grand-media' ) ?>
						</label></div>
					<button class="pull-right btn btn-info gmedia-import" type="button" name="import-folder" value="true"><?php _e( 'Import folder', 'grand-media' ); ?></button>
				</div>
				<script type="text/javascript">
					/* <![CDATA[ */
					jQuery(document).ready(function () {
						jQuery("#file_browser").fileTree({
							script: ajaxurl + "?action=gmedia_ftp_browser&_ajax_nonce=<?php echo wp_create_nonce( 'grandMedia' ) ;?>",
							root: '/',
							loadMessage: "<?php _e('loading...', 'grand-media'); ?>"
						}, function (path) {
							jQuery("#folderpath").val(path);
						});
					});
					/* ]]> */
				</script>
			</fieldset>

			<?php if ( ! empty( $import['flagallery'] ) ) { ?>
				<fieldset id="import_flagallery" class="tab-pane">
					<?php
					$import['flagallery'] = $wpdb->get_results( "SELECT gid, title, galdesc FROM {$wpdb->prefix}flag_gallery" );
					if ( ! empty( $import['flagallery'] ) ) {
						?>
						<div class="tab-inside">
							<p><?php _e( 'If Album is not specified, then gallery name will be used as Album', 'grand-media' ) ?></p>
							<h5><?php _e( 'Flagallery Galleries', 'grand-media' ) ?>:
								<small>(<a href="#toggle-flaggalery" class="gm-toggle-cb"><?php _e( 'Toggle checkboxes', 'grand-media' ) ?></a>)</small>
							</h5>
							<div id="toggle-flaggalery">
								<?php foreach ( $import['flagallery'] as $gallery ) { ?>
									<div class="checkbox">
										<label><input type="checkbox" name="gallery[]" value="<?php echo $gallery->gid ?>"/> <span><?php echo esc_html( $gallery->title ); ?></span></label>
										<?php /* if(!empty($gallery->galdesc)){
											echo '<div class="help-block"> ' . stripslashes($gallery->galdesc) . '</div>';
										} */
										?>
									</div>
								<?php } ?>
							</div>
						</div>
						<div class="tab-footer">
							<button class="pull-right btn btn-info gmedia-import" type="button" name="import-flagallery" value="true"><?php _e( 'Import', 'grand-media' ); ?></button>
						</div>
					<?php } else { ?>
						<p class="tab-inside"><?php _e( 'There are no created galleries in this plugin.', 'grand-media' ) ?></p>
					<?php } ?>
				</fieldset>
			<?php } ?>

			<?php if ( ! empty( $import['nextgen'] ) ) { ?>
				<fieldset id="import_nextgen" class="tab-pane">
					<?php
					$import['nextgen'] = $wpdb->get_results( "SELECT gid, title, galdesc FROM {$wpdb->prefix}ngg_gallery" );
					if ( ! empty( $import['nextgen'] ) ) {
						?>
						<div class="tab-inside">
							<p><?php _e( 'If Album is not specified, then gallery name will be used as Album', 'grand-media' ) ?></p>
							<h5><?php _e( 'NextGen Galleries', 'grand-media' ) ?>:
								<small>(<a href="#toggle-nextgen" class="gm-toggle-cb"><?php _e( 'Toggle checkboxes', 'grand-media' ) ?></a>)</small>
							</h5>
							<div id="toggle-nextgen">
								<?php foreach ( $import['nextgen'] as $gallery ) { ?>
									<div class="checkbox">
										<label><input type="checkbox" name="gallery[]" value="<?php echo $gallery->gid ?>"/> <span><?php echo esc_html( $gallery->title ); ?></span></label>
										<?php /* if(!empty($gallery->galdesc)){
											echo '<div class="help-block"> ' . stripslashes($gallery->galdesc) . '</div>';
										} */
										?>
									</div>
								<?php } ?>
							</div>
						</div>
						<div class="tab-footer">
							<button class="pull-right btn btn-info gmedia-import" type="button" name="import-nextgen" value="true"><?php _e( 'Import', 'grand-media' ); ?></button>
						</div>
					<?php } else { ?>
						<p class="tab-inside"><?php _e( 'There are no created galleries in this plugin.', 'grand-media' ) ?></p>
					<?php } ?>
				</fieldset>
			<?php } ?>
		</div>
		<div class="clear"></div>
	</div>
	</form>

	<div class="modal fade gmedia-modal" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title"><?php _e( 'Import', 'grand-media' ); ?></h4>
				</div>
				<div class="modal-body">
					<script type="text/javascript">
						function gmedia_import_done() {
							if (jQuery('#importModal').is(':visible')) {
								var btn = jQuery('#import-done');
								btn.text(btn.data('complete-text')).prop('disabled', false);
							}
						}
					</script>
					<iframe name="import_window" id="import_window" src="about:blank" width="100%" height="300" onload="gmedia_import_done()"></iframe>
				</div>
				<div class="modal-footer">
					<button type="button" id="import-done" class="btn btn-primary" data-dismiss="modal" data-complete-text="<?php _e( 'Close', 'grand-media' ); ?>" disabled="disabled"><?php _e( 'Working...', 'grand-media' ); ?></button>
				</div>
			</div>
			<!-- /.modal-content -->
		</div>
	</div>
<?php
}
