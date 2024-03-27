<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $gmCore, $gmGallery;
/**
 * License Key
 *
 * @var $pk
 * @var $lk
 */
?>
<fieldset id="gmedia_premium" class="tab-pane active">
	<p><?php esc_html_e( 'Enter Gmedia Premium Key to remove backlink label from premium gallery modules and unlock settings below.' ); ?></p>

	<div class="row">
		<div class="form-group col-sm-5">
			<label><?php esc_html_e( 'Gmedia Premium Key', 'grand-media' ); ?>:
				<?php
				if ( isset( $gmGallery->options['license_name'] ) ) {
					echo '<em>' . esc_html( $gmGallery->options['license_name'] ) . '</em>';
				}
				?>
			</label>
			<input type="text" name="set[purchase_key]" id="purchase_key" class="form-control input-sm" value="<?php echo esc_attr( $pk ); ?>"/>

			<div class="manual_license_activate"<?php echo( ( 'manual' === $gmCore->_get( 'license_activate' ) ) ? '' : ' style="display:none;"' ); ?>>
				<label style="margin-top:7px;"><?php esc_html_e( 'License Name', 'grand-media' ); ?>:</label>
				<input type="text" name="set[license_name]" id="license_name" class="form-control input-sm" value="<?php echo esc_attr( $gmGallery->options['license_name'] ); ?>"/>
				<label style="margin-top:7px;"><?php esc_html_e( 'License Key', 'grand-media' ); ?>:</label>
				<input type="text" name="set[license_key]" id="license_key" class="form-control input-sm" value="<?php echo esc_attr( $lk ); ?>"/>
				<label style="margin-top:7px;"><?php esc_html_e( 'Additional Key', 'grand-media' ); ?>:</label>
				<input type="text" name="set[license_key2]" id="license_key2" class="form-control input-sm" value="<?php echo esc_attr( $gmGallery->options['license_key2'] ); ?>"/>
			</div>
		</div>
		<?php if ( ! ( 'manual' === $gmCore->_get( 'license_activate' ) || ! empty( $pk ) ) ) { ?>
			<div class="form-group col-sm-7">
				<label>&nbsp;</label>
				<button style="display:block;" class="btn btn-success btn-sm" type="submit" name="license-key-activate"><?php esc_html_e( 'Activate Key', 'grand-media' ); ?></button>
			</div>
		<?php } ?>
	</div>
	<fieldset <?php echo( empty( $gmGallery->options['license_name'] ) ? 'disabled' : '' ); ?>>
		<hr/>
		<div class="form-group">
			<label><?php esc_html_e( 'Delete original images', 'grand-media' ); ?>:</label>
			<div class="checkbox" style="margin:0;">
				<input type="hidden" name="set[delete_originals]" value="0"/>
				<label><input type="checkbox" name="set[delete_originals]" value="1" <?php checked( $gmGallery->options['delete_originals'], '1' ); ?> /> <?php esc_html_e( 'Do not keep original images on the server', 'grand-media' ); ?>
				</label>
			</div>
			<p class="help-block"><?php esc_html_e( 'Warning: You can\'t undo this operation. Checking this option you agree to delete original images. You will not be able: restore images after modification in the Image Editor; re-create web-optimized images; ...', 'grand-media' ); ?></p>
		</div>

		<div class="form-group">
			<label><?php esc_html_e( 'Disable Logs', 'grand-media' ); ?>:</label>
			<div class="checkbox" style="margin:0;">
				<input type="hidden" name="set[disable_logs]" value="0"/>
				<label><input type="checkbox" name="set[disable_logs]" value="1" <?php checked( $gmGallery->options['disable_logs'], '1' ); ?> /> <?php esc_html_e( 'Disable Gmedia Logs page', 'grand-media' ); ?>
				</label>
			</div>
		</div>

		<hr/>
		<div class="form-group">
			<label><?php esc_html_e( 'Gmedia Tags & Categories', 'grand-media' ); ?></label>
			<div class="checkbox" style="margin:0;">
				<input type="hidden" name="set[wp_term_related_gmedia]" value="0"/>
				<label><input type="checkbox" name="set[wp_term_related_gmedia]" value="1" <?php checked( $gmGallery->options['wp_term_related_gmedia'], '1' ); ?> /> <?php esc_html_e( 'Show Related Media from Gmedia library for WordPress native tags & categories', 'grand-media' ); ?>
				</label>
			</div>
			<div class="checkbox" style="margin:0;">
				<input type="hidden" name="set[wp_post_related_gmedia]" value="0"/>
				<label><input type="checkbox" name="set[wp_post_related_gmedia]" value="1" <?php checked( $gmGallery->options['wp_post_related_gmedia'], '1' ); ?> /> <?php esc_html_e( 'Show Related Media from Gmedia library for WordPress Posts based on tags', 'grand-media' ); ?>
				</label>
			</div>
		</div>

		<hr/>
		<div class="form-group">
			<label><?php esc_html_e( 'Show "Any Feedback?" in the Sidebar', 'grand-media' ); ?>:</label>
			<div class="checkbox" style="margin:0;">
				<input type="hidden" name="set[feedback]" value="0"/>
				<label><input type="checkbox" name="set[feedback]" value="1" <?php checked( $gmGallery->options['feedback'], '1' ); ?> /> <?php esc_html_e( 'Show "Any Feedback?"', 'grand-media' ); ?>
				</label>
			</div>
			<p class="help-block"><?php esc_html_e( 'I\'d be very happy if you leave positive feedback about plugin on the WordPress.org Directory. Thank You!', 'grand-media' ); ?></p>
		</div>
		<div class="form-group">
			<label><?php esc_html_e( 'Show Twitter News in the Sidebar', 'grand-media' ); ?>:</label>
			<div class="checkbox" style="margin:0;">
				<input type="hidden" name="set[twitter]" value="0"/>
				<label><input type="checkbox" name="set[twitter]" value="1" <?php checked( $gmGallery->options['twitter'], '1' ); ?> /> <?php esc_html_e( 'Show Twitter News', 'grand-media' ); ?>
				</label>
			</div>
			<p class="help-block"><?php esc_html_e( 'Follow Gmedia on twitter to not miss info about new modules and plugin updates.', 'grand-media' ); ?></p>
		</div>
		<div class="form-group">
			<label><?php esc_html_e( 'Hide WoowGallery Ad Banner', 'grand-media' ); ?>:</label>
			<div class="checkbox" style="margin:0;">
				<input type="hidden" name="set[disable_ads]" value="0"/>
				<label><input type="checkbox" name="set[disable_ads]" value="1" <?php checked( $gmGallery->options['disable_ads'], '1' ); ?> /> <?php esc_html_e( 'Hide WoowGallery Banner', 'grand-media' ); ?>
				</label>
			</div>
		</div>
	</fieldset>

</fieldset>
