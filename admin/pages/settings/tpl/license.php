<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $gmCore, $gmGallery;
/**
 * License Key
 *
 * @var $pk
 * @var $lk
 */

$license_type = gmedia_get_license_type();
$has_premium  = gmedia_has_premium_license();
?>
<fieldset id="gmedia_premium" class="tab-pane active">

	<?php if ( 'freemius' !== $license_type ) : ?>
		<!-- Legacy License Section -->
		<div class="legacy-license-section" style="margin-bottom: 30px; padding: 20px; background: #fff9e6; border: 1px solid #ffcc00; border-radius: 4px;">
			<?php if ( ! empty( $gmGallery->options['license_name'] ) ) : ?>
				<!-- Active Legacy License -->
				<div class="notice notice-success inline" style="margin: 15px 0;">
					<p><strong><?php esc_html_e( '✓ Legacy License Active', 'grand-media' ); ?></strong></p>
				</div>
				<div class="row">
					<div class="form-group col-sm-6">
						<label><?php esc_html_e( 'License Name', 'grand-media' ); ?>:</label>
						<input type="text" class="form-control input-sm" value="<?php echo esc_attr( $gmGallery->options['license_name'] ); ?>" disabled style="background: #f5f5f5;"/>
					</div>
				</div>
			<?php else : ?>
				<h3 style="margin-top: 0;">
					<?php esc_html_e( 'Legacy License Activation', 'grand-media' ); ?>
					<span style="font-size: 12px; font-weight: normal; color: #856404;"><?php esc_html_e( '(Legacy Method)', 'grand-media' ); ?></span>
				</h3>

				<div class="notice notice-warning inline" style="margin: 15px 0;">
					<p>
						<strong><?php esc_html_e( 'Important:', 'grand-media' ); ?></strong>
						<?php esc_html_e( 'This is the legacy license activation method. New license purchases are only available through Freemius (see section below). If you have an old license key, you can still activate it here.', 'grand-media' ); ?>
					</p>
				</div>
				<!-- Legacy License Activation Form -->
				<p><?php esc_html_e( 'If you have a legacy Gmedia Premium Key from previous purchases, you can activate it below:', 'grand-media' ); ?></p>

				<div class="row">
					<div class="form-group col-sm-5">
						<label><?php esc_html_e( 'Gmedia Premium Key', 'grand-media' ); ?>:</label>
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
							<button style="display:block;" class="btn btn-success btn-xs" type="submit" name="license-key-activate"><?php esc_html_e( 'Activate Legacy Key', 'grand-media' ); ?></button>
						</div>
					<?php } ?>
				</div>
			<?php endif; ?>

			<p class="description" style="margin-top: 10px;">
				<strong><?php esc_html_e( 'Need a new license?', 'grand-media' ); ?></strong>
				<?php
				echo sprintf(
				/* translators: %s: Link to purchase page */
						esc_html__( 'New licenses are only available through Freemius. %s', 'grand-media' ),
						'<a href="' . admin_url( 'admin.php?page=GrandMedia-pricing' ) . '" target="_blank">' . esc_html__( 'Purchase here', 'grand-media' ) . ' &rarr;</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( ! $has_premium ) : ?>
		<!-- No License Active -->
		<div class="no-license-section" style="margin-bottom: 30px; padding: 20px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px;">
			<h3 style="margin-top: 0;"><?php esc_html_e( 'Unlock Premium Features', 'grand-media' ); ?></h3>
			<p><?php esc_html_e( 'Get access to premium gallery modules, advanced features, and priority support.', 'grand-media' ); ?></p>
			<a href="<?php echo admin_url( 'admin.php?page=GrandMedia-pricing' ); ?>" class="button button-primary button-large" target="_blank"><?php esc_html_e( 'Get Gmedia Premium', 'grand-media' ); ?></a>
		</div>
		<hr/>
	<?php endif; ?>

	<!-- Premium Features Section -->
	<fieldset <?php echo( ! $has_premium ? 'disabled' : '' ); ?>>
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
				<label><input type="checkbox" name="set[wp_term_related_gmedia]"
				              value="1" <?php checked( $gmGallery->options['wp_term_related_gmedia'], '1' ); ?> /> <?php esc_html_e( 'Show Related Media from Gmedia library for WordPress native tags & categories', 'grand-media' ); ?>
				</label>
			</div>
			<div class="checkbox" style="margin:0;">
				<input type="hidden" name="set[wp_post_related_gmedia]" value="0"/>
				<label><input type="checkbox" name="set[wp_post_related_gmedia]"
				              value="1" <?php checked( $gmGallery->options['wp_post_related_gmedia'], '1' ); ?> /> <?php esc_html_e( 'Show Related Media from Gmedia library for WordPress Posts based on tags', 'grand-media' ); ?>
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
