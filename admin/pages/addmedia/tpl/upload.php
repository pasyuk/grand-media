<?php
/**
 * Gmedia Upload
 *
 * @var $user_ID
 * @var $gmProcessor
 * @var $gmCore
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! gm_user_can( 'upload' ) ) {
	esc_html_e( 'You do not have permissions to upload media', 'grand-media' );

	return;
}

$maxupsize       = wp_max_upload_size();
$maxupsize_mb    = floor( $maxupsize / 1024 / 1024 );
$maxchunksize    = floor( $maxupsize * 0.9 );
$maxchunksize_mb = floor( $maxupsize_mb * 0.9 );

$screen_options = $gmProcessor->user_options;

$gm_terms = array();

?>
<form class="row" id="gmUpload" name="upload_form" method="POST" accept-charset="utf-8" onsubmit="return false;">
	<div class="col-md-4" id="uploader_multipart_params">
		<br/>
		<?php if ( 'false' === $screen_options['uploader_chunking'] || ( 'html4' === $screen_options['uploader_runtime'] ) ) { ?>
			<p class="clearfix text-end"><span class="badge label-default"><?php echo esc_html( __( 'Maximum file size', 'grand-media' ) . ": {$maxupsize_mb}Mb" ); ?></span></p>
		<?php } else { ?>
			<p class="clearfix text-end hidden">
				<span class="badge label-default"><?php echo esc_html( __( 'Maximum $_POST size', 'grand-media' ) . ": {$maxupsize_mb}Mb" ); ?></span>
				<span class="badge label-default"><?php echo esc_html( __( 'Chunk size', 'grand-media' ) . ': ' . min( $maxchunksize_mb, $screen_options['uploader_chunk_size'] ) . 'Mb' ); ?></span>
			</p>
		<?php } ?>

		<div class="form-group form-group-xs">
			<label><?php esc_html_e( 'Title', 'grand-media' ); ?></label>
			<select name="set_title" class="form-control input-sm">
				<option value="exif"><?php esc_html_e( 'EXIF or File Name', 'grand-media' ); ?></option>
				<option value="filename"><?php esc_html_e( 'File Name', 'grand-media' ); ?></option>
				<option value="empty"><?php esc_html_e( 'Empty', 'grand-media' ); ?></option>
			</select>
		</div>
		<div class="form-group form-group-xs">
			<label><?php esc_html_e( 'Status', 'grand-media' ); ?></label>
			<select name="set_status" class="form-control input-sm">
				<option value="inherit"><?php esc_html_e( 'Same as Album or Public', 'grand-media' ); ?></option>
				<option value="publish"><?php esc_html_e( 'Public', 'grand-media' ); ?></option>
				<option value="private"><?php esc_html_e( 'Private', 'grand-media' ); ?></option>
				<option value="draft"><?php esc_html_e( 'Draft', 'grand-media' ); ?></option>
			</select>
		</div>

		<hr/>

		<?php require dirname( __FILE__ ) . '/assign-terms.php'; ?>

	</div>
	<div class="col-md-8" id="pluploadUploader">
		<p><?php esc_html_e( "You browser doesn't have Flash or HTML5 support. Check also if page have no JavaScript errors.", 'grand-media' ); ?></p>
		<?php
		$mime_types = get_allowed_mime_types( $user_ID );
		$type_ext   = array();
		$filters    = array();
		foreach ( $mime_types as $ext => $mime ) {
			$_type                = strtok( $mime, '/' );
			$type_ext[ $_type ][] = $ext;
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
					var gmedia_uploader;
					jQuery(function($) {

						function gmediaInitUploader() {
							//noinspection JSDuplicatedDeclaration
							$('#pluploadUploader').pluploadQueue({
				<?php if ( 'auto' !== $screen_options['uploader_runtime'] ) { ?>
								runtimes: '<?php echo esc_js( $screen_options['uploader_runtime'] ); ?>',
				<?php } ?>
								url: '<?php echo esc_js( $gmCore->punyencode( admin_url( 'admin-ajax.php' ) ) ); ?>',
				<?php if ( ( 'true' === $screen_options['uploader_urlstream_upload'] ) && ( 'html4' !== $screen_options['uploader_runtime'] ) ) { ?>
								urlstream_upload: true,
								multipart: false,
				<?php } else { ?>
								multipart: true,
				<?php } ?>
								multipart_params: {action: 'gmedia_upload_handler', _wpnonce_upload: '<?php echo esc_attr( wp_create_nonce( 'gmedia_upload' ) ); ?>', params: ''},
				<?php if ( 'true' === $screen_options['uploader_chunking'] && ( 'html4' !== $screen_options['uploader_runtime'] ) ) { ?>
								max_file_size: '2000Mb',
								chunk_size: <?php echo esc_js( min( $maxchunksize, $screen_options['uploader_chunk_size'] * 1024 * 1024 ) ); ?>,
				<?php } else { ?>
								max_file_size: <?php echo esc_js( $maxupsize ); ?>,
				<?php } ?>
								max_retries: 2,
								unique_names: false,
								rename: true,
								sortable: true,
								dragdrop: true,
								views: {
									list: false,
									thumbs: true,
									active: 'thumbs',
								},
								filters: <?php echo wp_json_encode( $filters ); ?>
							});
							var closebtn = '<button type="button" class="btn-close m-0 float-end" data-bs-dismiss="alert" aria-label="Close"></button>';
							var resetbtn = '<a href="#" class="plupload_reset" style="display: inline-block;"><i class="fa-solid fa-arrow-rotate-right"></i></a>';
							gmedia_uploader = $('#pluploadUploader').pluploadQueue();
							gmedia_uploader.bind('PostInit', function(up) {
								$('.plupload_filelist_footer .plupload_file_action').html(resetbtn).on('click', '.plupload_reset', function(e) {
									e.preventDefault();
									up.removeAllEventListeners();
									up.destroy();
									gmediaInitUploader();
								});
							});
							gmedia_uploader.bind('BeforeUpload', function(up, file) {
								up.settings.multipart_params.params = jQuery('#uploader_multipart_params :input').serialize();
							});
							gmedia_uploader.bind('ChunkUploaded', function(up, file, info) {
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
							gmedia_uploader.bind('FileUploaded', function(up, file, info) {
								//console.log('[FileUploaded] File:', file, "Info:", info);
								var response = jQuery.parseJSON(info.response);
								if (response && response.error) {
									file.status = plupload.FAILED;
									jQuery('<div></div>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message).appendTo('#gm-message');
									console.log('[FileUploaded] ', response.error);
								}
							});
							gmedia_uploader.bind('UploadProgress', function(up, file) {
								var percent = gmedia_uploader.total.percent;
								$('#total-progress-info .progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);
							});
							gmedia_uploader.bind('Error', function(up, args) {
								console.log('[Error] ', args);
								jQuery('<div></div>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + args.file.name + ':</strong> ' + args.message + ' ' + args.status).appendTo('#gm-message');
							});
							gmedia_uploader.bind('UploadComplete', function(up, files) {
								console.log('[UploadComplete]', files);
								$('<div></div>').addClass('alert alert-success alert-dismissable').html(closebtn + "<?php esc_attr_e( 'Upload finished', 'grand-media' ); ?>").appendTo('#gm-message');
								$('#total-progress-info .progress-bar').css('width', '0').attr('aria-valuenow', '0');
							});
						}

						gmediaInitUploader();
					});
		</script>
	</div>
</form>
