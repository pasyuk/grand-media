<?php
/**
 * Gmedia Import
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! gm_user_can( 'upload' ) ) {
	esc_html_e( 'You do not have permissions to import media', 'grand-media' );

	return;
}

global $wpdb;

$gmediaURL  = plugins_url( '', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
$gm_terms   = array();
$import_tab = array();
?>
<form class="row" id="import_form" name="import_form" target="import_window" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="POST" accept-charset="utf-8" style="padding:20px 0 10px;">
	<div class="col-md-4">
		<fieldset id="import_params" class="import-params">
			<?php wp_nonce_field( 'gmedia_import', '_wpnonce_import' ); ?>
			<input type="hidden" name="action" value="gmedia_import_handler"/>
			<input type="hidden" id="import-action" name="import" value=""/>

			<?php require dirname( __FILE__ ) . '/assign-terms.php'; ?>

		</fieldset>
	</div>

	<div class="col-md-8 tabable">
		<ul class="nav nav-tabs" style="padding:0 10px;">
			<li class="nav-item m-0"><a class="nav-link active" href="#import_folder" data-bs-toggle="tab"><?php esc_html_e( 'Import Server Folder', 'grand-media' ); ?></a></li>
			<?php
			$import_tab['flagallery'] = $wpdb->get_var( "show tables like '{$wpdb->prefix}flag_gallery'" );
			if ( $import_tab['flagallery'] ) {
				?>
				<li class="nav-item m-0"><a class="nav-link" href="#import_flagallery" data-bs-toggle="tab"><?php esc_html_e( 'FlAGallery plugin', 'grand-media' ); ?></a></li>
				<?php
			}
			$import_tab['nextgen'] = $wpdb->get_var( "show tables like '{$wpdb->prefix}ngg_gallery'" );
			if ( $import_tab['nextgen'] ) {
				?>
				<li class="nav-item m-0"><a class="nav-link" href="#import_nextgen" data-bs-toggle="tab"><?php esc_html_e( 'NextGen plugin', 'grand-media' ); ?></a></li>
				<?php
			}
			?>
		</ul>
		<div class="tab-content">
			<fieldset id="import_folder" class="tab-pane active">
				<input type="hidden" id="folderpath" name="path" value="/"/>

				<div class="tab-inside">
					<h5><?php esc_html_e( 'Sever folders', 'grand-media' ); ?>:</h5>

					<div id="file_browser"></div>
				</div>
				<div class="tab-footer">
					<div class="checkbox float-start">
						<div><label><input type="checkbox" name="delete_source" value="1"/> <?php esc_html_e( 'delete source files after importing', 'grand-media' ); ?></label></div>
						<div><label><input type="checkbox" name="skip_exists" value="skip"> <?php esc_html_e( 'Skip if file with the same name already exists in Gmedia Library', 'grand-media' ); ?></label></div>
						<div class="help-block"><?php esc_html_e( 'Note: duplicates will be skipped in any way (checked by file hash)' ); ?></div>
					</div>
					<button class="float-end btn btn-info gmedia-import" data-bs-toggle="modal" data-bs-target="#importModal" type="button" name="import-folder" value="true"><?php esc_html_e( 'Import folder', 'grand-media' ); ?></button>
				</div>
				<script type="text/javascript">
									/* <![CDATA[ */
									jQuery(document).ready(function() {
										jQuery('#file_browser').fileTree({
											script: ajaxurl + "?action=gmedia_ftp_browser&_wpnonce=<?php echo esc_js( wp_create_nonce( 'GmediaGallery' ) ); ?>",
											root: '/',
											loadMessage: "<?php esc_attr_e( 'loading...', 'grand-media' ); ?>",
										}, function(path) {
											jQuery('#folderpath').val(path);
										});
									});
									/* ]]> */
				</script>
			</fieldset>

			<?php if ( ! empty( $import_tab['flagallery'] ) ) { ?>
				<fieldset id="import_flagallery" class="tab-pane">
					<?php
					$import_tab['flagallery'] = $wpdb->get_results( "SELECT gid, title, galdesc FROM {$wpdb->prefix}flag_gallery" );
					if ( ! empty( $import_tab['flagallery'] ) ) {
						?>
						<div class="tab-inside">
							<p><?php esc_html_e( 'If Album is not specified, then gallery name will be used as Album', 'grand-media' ); ?></p>
							<h5><?php esc_html_e( 'Flagallery Galleries', 'grand-media' ); ?>:
								<small>(<a href="#toggle-flaggalery" class="gm-toggle-cb"><?php esc_html_e( 'Toggle checkboxes', 'grand-media' ); ?></a>)</small>
							</h5>
							<div id="toggle-flaggalery">
								<?php foreach ( $import_tab['flagallery'] as $gallery ) { ?>
									<div class="checkbox">
										<label><input type="checkbox" name="gallery[]" value="<?php echo absint( $gallery->gid ); ?>"/>
											<span><?php echo esc_html( $gallery->title ); ?></span></label>
										<?php /* if(!empty($gallery->galdesc)){ echo '<div class="help-block"> ' . stripslashes($gallery->galdesc) . '</div>'; } */ ?>
									</div>
								<?php } ?>
							</div>
						</div>
						<div class="tab-footer">
							<div class="checkbox float-start">
								<label><input type="checkbox" name="skip_exists" value="skip"> <?php esc_html_e( 'Skip if file with the same name already exists in Gmedia Library', 'grand-media' ); ?></label>
								<div class="help-block"><?php esc_html_e( 'Note: duplicates will be skipped in any way (checked by file hash)' ); ?></div>
							</div>
							<button class="float-end btn btn-info gmedia-import" data-bs-toggle="modal" data-bs-target="#importModal" type="button" name="import-flagallery" value="true"><?php esc_html_e( 'Import', 'grand-media' ); ?></button>
						</div>
					<?php } else { ?>
						<p class="tab-inside"><?php esc_html_e( 'There are no created galleries in this plugin.', 'grand-media' ); ?></p>
					<?php } ?>
				</fieldset>
			<?php } ?>

			<?php if ( ! empty( $import_tab['nextgen'] ) ) { ?>
				<fieldset id="import_nextgen" class="tab-pane">
					<?php
					$import_tab['nextgen'] = $wpdb->get_results( "SELECT gid, title, galdesc FROM {$wpdb->prefix}ngg_gallery" );
					if ( ! empty( $import_tab['nextgen'] ) ) {
						?>
						<div class="tab-inside">
							<p><?php esc_html_e( 'If Album is not specified, then gallery name will be used as Album', 'grand-media' ); ?></p>
							<h5><?php esc_html_e( 'NextGen Galleries', 'grand-media' ); ?>:
								<small>(<a href="#toggle-nextgen" class="gm-toggle-cb"><?php esc_html_e( 'Toggle checkboxes', 'grand-media' ); ?></a>)</small>
							</h5>
							<div id="toggle-nextgen">
								<?php foreach ( $import_tab['nextgen'] as $gallery ) { ?>
									<div class="checkbox">
										<label><input type="checkbox" name="gallery[]" value="<?php echo absint( $gallery->gid ); ?>"/> <span><?php echo esc_html( $gallery->title ); ?></span></label>
										<?php /* if(!empty($gallery->galdesc)){ echo '<div class="help-block"> ' . stripslashes($gallery->galdesc) . '</div>'; } */ ?>
									</div>
								<?php } ?>
							</div>
						</div>
						<div class="tab-footer">
							<div class="checkbox float-start">
								<label><input type="checkbox" name="skip_exists" value="skip"> <?php esc_html_e( 'Skip if file with the same name already exists in Gmedia Library', 'grand-media' ); ?></label>
								<div class="help-block"><?php esc_html_e( 'Note: duplicates will be skipped in any way (checked by file hash)' ); ?></div>
							</div>
							<button class="float-end btn btn-info gmedia-import" data-bs-toggle="modal" data-bs-target="#importModal" type="button" name="import-nextgen" value="true"><?php esc_html_e( 'Import', 'grand-media' ); ?></button>
						</div>
					<?php } else { ?>
						<p class="tab-inside"><?php esc_html_e( 'There are no created galleries in this plugin.', 'grand-media' ); ?></p>
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
				<h4 class="modal-title"><?php esc_html_e( 'Import', 'grand-media' ); ?></h4>
				<button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
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
				<button type="button" id="import-done" class="btn btn-primary" data-bs-dismiss="modal" data-complete-text="<?php esc_attr_e( 'Close', 'grand-media' ); ?>" disabled="disabled"><?php esc_html_e( 'Working...', 'grand-media' ); ?></button>
			</div>
		</div>
	</div>
</div>

