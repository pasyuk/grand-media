<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Modal to install Module ZIP
 *
 * @var $gmedia_url
 */
?>
<div class="modal fade gmedia-modal" id="installModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<form class="modal-content" method="post" enctype="multipart/form-data" action="<?php echo esc_url( $gmedia_url ); ?>">
			<div class="modal-header">
				<h4 class="modal-title"><?php esc_html_e( 'Install a module in .zip format' ); ?></h4>
				<button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p class="install-help"><?php esc_html_e( 'If you have a module in a .zip format, you may install it by uploading it here.' ); ?></p>
				<?php wp_nonce_field( 'gmedia_module', '_wpnonce_module' ); ?>
				<label class="screen-reader-text" for="modulezip"><?php esc_html_e( 'Module zip file' ); ?></label>
				<input type="file" id="modulezip" name="modulezip"/>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'grand-media' ); ?></button>
				<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Install', 'grand-media' ); ?></button>
			</div>
		</form>
	</div>
</div>
