<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );
?>
<div class="modal fade gmedia-modal" id="shareModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><?php esc_html_e( 'Gmedia Share' ); ?></h4>
				<button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form class="modal-body" method="post" id="shareForm">
				<div class="form-group sharelink_post">
					<label><?php esc_html_e( 'Link to WordPress Post', 'grand-media' ); ?></label>
					<div class="input-group input-group-sm">
						<span class="input-group-text">
							<input class="form-check-input mt-0" type="radio" name="sharelink" value="" checked/>
						</span>
						<input type="text" class="form-control" readonly="readonly" value=""/>
						<span class="input-group-btn">
							<a target="_blank" class="btn btn-secondary" href="" title="<?php esc_attr_e( 'Open in new Tab', 'grand-media' ); ?>"><i class='fa-solid fa-arrow-up-right-from-square'></i></a>
						</span>
					</div>
				</div>
				<div class="form-group sharelink_page">
					<label><?php esc_html_e( 'Link to GmediaCloud Page', 'grand-media' ); ?></label>
					<div class="input-group input-group-sm">
						<span class="input-group-text">
							<input class="form-check-input mt-0" type="radio" name="sharelink" value=""/>
						</span>
						<input type="text" class="form-control" readonly="readonly" value=""/>
						<span class="input-group-btn">
							<a target="_blank" class="btn btn-secondary" href="" title="<?php esc_attr_e( 'Open in new Tab', 'grand-media' ); ?>"><i class='fa-solid fa-arrow-up-right-from-square'></i></a>
						</span>
					</div>
				</div>
				<div class="form-group">
					<label><?php esc_html_e( 'Send link to', 'grand-media' ); ?></label>
					<input name="email" type="email" class="form-control sharetoemail" value="" placeholder="<?php esc_attr_e( 'Email', 'grand-media' ); ?>"/>
					<textarea style="margin-top:4px;" name="message" cols="20" rows="3" class="form-control" placeholder="<?php esc_attr_e( 'Message (optional)', 'grand-media' ); ?>"></textarea>
				</div>
				<input type="hidden" name="action" value="gmedia_share_page"/>
				<?php wp_nonce_field( 'gmedia_share', '_wpnonce_share' ); ?>
			</form>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary sharebutton" disabled="disabled"><?php esc_html_e( 'Send', 'grand-media' ); ?></button>
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'grand-media' ); ?></button>
			</div>
		</div>
	</div>
</div>
