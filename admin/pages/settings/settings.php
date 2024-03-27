<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Gmedia Settings
 */

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor, $gm_allowed_tags;

$url = $gmProcessor->url;
$pk  = isset( $gmGallery->options['purchase_key'] ) ? $gmGallery->options['purchase_key'] : '';
$lk  = isset( $gmGallery->options['license_key'] ) ? $gmGallery->options['license_key'] : '';
?>

<form id="gmediaSettingsForm" class="card m-0 mw-100 p-0" method="post" action="<?php echo esc_url( $url ); ?>">
	<div class="card-header bg-light clearfix">
		<div class="btn-toolbar gap-4 float-start">
			<div class="btn-group">
				<button type="submit" name="gmedia_settings_reset" class="btn btn-secondary" data-confirm="<?php esc_attr_e( 'Reset all Gmedia settings?', 'grand-media' ); ?>"><?php esc_html_e( 'Reset Settings', 'grand-media' ); ?></button>
				<button type="submit" name="gmedia_settings_save" class="btn btn-primary"><?php esc_html_e( 'Update', 'grand-media' ); ?></button>
			</div>
		</div>
		<?php
		wp_nonce_field( 'gmedia_settings', '_wpnonce_settings' );
		?>
	</div>
	<div class="card-body" id="gmedia-msg-panel"></div>
	<div class="container-fluid small">
		<div class="tabable tabs-left">
			<ul id="settingsTabs" class="nav nav-tabs flex-column" style="padding:10px 0;">
				<li class="nav-item"><a class="nav-link text-dark active" href="#gmedia_premium" data-bs-toggle="tab"><?php esc_html_e( 'Premium Settings', 'grand-media' ); ?></a></li>
				<li class="nav-item"><a class="nav-link text-dark" href="#gmedia_settings_other" data-bs-toggle="tab"><?php esc_html_e( 'Other Settings', 'grand-media' ); ?></a></li>
				<?php if ( current_user_can( 'manage_options' ) ) { ?>
					<li class="nav-item"><a class="nav-link text-dark" href="#gmedia_settings_permalinks" data-bs-toggle="tab"><?php esc_html_e( 'Permalinks', 'grand-media' ); ?></a></li>
					<li class="nav-item"><a class="nav-link text-dark" href="#gmedia_settings_cloud" data-bs-toggle="tab"><?php esc_html_e( 'GmediaCloud Page', 'grand-media' ); ?></a></li>
					<li class="nav-item"><a class="nav-link text-dark" href="#gmedia_settings_roles" data-bs-toggle="tab"><?php esc_html_e( 'Roles/Capabilities Manager', 'grand-media' ); ?></a></li>
				<?php } ?>
				<li class="nav-item"><a class="nav-link text-dark" href="#gmedia_settings_sysinfo" data-bs-toggle="tab"><?php esc_html_e( 'System Info', 'grand-media' ); ?></a></li>
				<li class="nav-item" id="gmedia_credits"><a class="nav-link text-danger" href="#gmedia_thanks" data-bs-toggle="tab"><?php esc_html_e( 'Thank you for support!', 'grand-media' ); ?></a></li>
			</ul>
			<div class="tab-content" style="padding-top:21px;">
				<?php
				require dirname( __FILE__ ) . '/tpl/license.php';
				require dirname( __FILE__ ) . '/tpl/common.php';
				if ( current_user_can( 'manage_options' ) ) {
					include dirname( __FILE__ ) . '/tpl/permalinks.php';
					include dirname( __FILE__ ) . '/tpl/roles.php';
				}
				require dirname( __FILE__ ) . '/tpl/system.php';
				require dirname( __FILE__ ) . '/tpl/thanks.php';
				?>

			</div>
			<div class="clear"></div>
		</div>
		<script type="text/javascript">
			jQuery(function($) {
				var hash = window.location.hash;
				if (hash) {
					hash = hash.replace('_tab', '');
					$('#settingsTabs a[href="' + hash + '"]').tab('show');
				}
				$('#gmediaSettingsForm').on('submit', function(e) {
					$(this).attr('action', $(this).attr('action') + $('#settingsTabs a.active').attr('href') + '_tab');
				});
			});
		</script>
	</div>
</form>
