<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $gmCore, $gm_allowed_tags;

if ( isset( $customfield_meta_type ) && $customfield_meta_type ) { ?>
	<div class="modal fade gmedia-modal" id="newCustomFieldModal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title"><?php esc_html_e( 'Add New Custom Field' ); ?></h4>
					<button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form class="modal-body" method="post" id="newCustomFieldForm">
					<?php
					echo wp_kses( $gmCore->meta_form( $customfield_meta_type ), $gm_allowed_tags );
					wp_nonce_field( 'gmedia_custom_field', '_wpnonce_custom_field' );
					wp_referer_field();
					?>
					<input type="hidden" name="action" value="<?php echo esc_attr( $customfield_meta_type ); ?>_add_custom_field"/>
					<input type="hidden" class="newcustomfield-for-id" name="ID" value=""/>
				</form>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary customfieldsubmit"><?php esc_html_e( 'Add', 'grand-media' ); ?></button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'grand-media' ); ?></button>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
