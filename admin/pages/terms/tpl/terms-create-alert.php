<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Alert capability
 */
?>
<div class="alert alert-warning alert-dismissible" role="alert" style="margin-bottom:0">
	<button type="button" class="btn-close float-end m-0" data-bs-dismiss="alert" aria-label="Close"></button>
	<strong><?php esc_html_e( 'Info:', 'grand-media' ); ?></strong> <?php esc_html_e( 'You are not allowed to add new terms', 'grand-media' ); ?>
</div>

