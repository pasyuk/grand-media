<?php
/**
 * Gmedia Modules
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $gmCore, $gmProcessor, $gmGallery, $gmDB;

$gmedia_url = $gmProcessor->url;
$modules    = $gmProcessor->modules;
$tags       = array();
if ( ! empty( $modules['xml'] ) ) {
	foreach ( $modules['xml'] as $module ) {
		$tags = array_merge( $tags, $module['tags'] );
	}
}
if ( ! empty( $tags ) ) {
	$tags = array_unique( $tags );
	sort( $tags );
}
//echo '<pre style="max-height: 500px; overflow:auto;">' . print_r($modules, true) . '</pre>';

if ( isset( $modules['error'] ) ) {
	echo wp_kses_post( $gmCore->alert( 'danger', $modules['error'] ) );
}

?>
<div id="gmedia_modules">
	<div id="gmedia_modules_wrapper" data-update="<?php echo esc_attr( $gmGallery->options['modules_update'] ); ?>">
		<div class="card m-0 mw-100 p-0">
			<div class="card-header bg-light clearfix">
				<div class="clearfix">
					<a href="#installModuleModal" class="btn btn-primary float-end<?php echo current_user_can( 'manage_options' ) ? '' : ' disabled'; ?>" data-bs-toggle="modal"><?php esc_html_e( 'Install Module ZIP' ); ?></a>

					<div class="btn-group float-start filter-modules" style="margin-right: 10px;">
						<button type="button" data-filter="collection" class="btn btn-primary"><?php esc_html_e( 'All Modules', 'grand-media' ); ?>
							<span class="badge badge-error gm-module-count-<?php echo intval( $gmGallery->options['modules_update'] ); ?>" title="<?php esc_attr_e( 'Modules Updates', 'grand-media' ); ?>"><?php echo intval( $gmGallery->options['modules_update'] ); ?></span></button>
						<button type="button" data-filter="not-installed" class="btn btn-secondary"><?php esc_html_e( 'New Modules', 'grand-media' ); ?>
							<span class="badge badge-success gm-module-count-<?php echo intval( $gmGallery->options['modules_new'] ); ?>" title="<?php esc_attr_e( 'New Modules', 'grand-media' ); ?>"><?php echo intval( $gmGallery->options['modules_new'] ); ?></span></button>
						<button type="button" data-filter="tag-trend" class="btn btn-secondary"><?php esc_html_e( 'Trends', 'grand-media' ); ?></button>
					</div>

					<?php if ( ! empty( $tags ) ) { ?>
						<div class="btn-group float-start">
							<button type="button" class="btn btn-secondary dropdown-toggle" onclick="jQuery(this).toggleClass('active');" data-bs-toggle="collapse" data-bs-target="#collapseFeatures" aria-expanded="false" aria-controls="collapseFeatures">
								Feature Filters
							</button>
						</div>
					<?php } ?>
				</div>
				<?php if ( ! empty( $tags ) ) { ?>
					<div class="collapse" id="collapseFeatures">
						<div class="filter-modules" style="padding-top: 10px;">
							<?php foreach ( $tags as $_tag ) { ?>
								<span style="cursor: pointer;" data-filter="tag-<?php echo sanitize_key( $_tag ); ?>" class="badge bg-secondary"><?php echo esc_html( strtoupper( $_tag ) ); ?></span>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			</div>
			<div class="card-body" id="gmedia-msg-panel"></div>
			<div class="card-body modules-body">
				<?php
				// installed modules.
				if ( ! empty( $modules['in'] ) ) {
					foreach ( $modules['in'] as $module ) {
						$module['screenshot_url'] = $module['module_url'] . '/screenshot.png';
						$module['mclass']         = ' module-filtered module-collection module-installed';
						if ( $module['update'] ) {
							$module['mclass'] .= ' module-update';
						}
						foreach ( $module['tags'] as $_tag ) {
							$module['mclass'] .= ' module-tag-' . sanitize_key( $_tag );
						}

						include dirname( __FILE__ ) . '/tpl/module-item.php';

					}
				}

				if ( ! empty( $modules['out'] ) ) {
					?>
					<?php
					//$out_dirpath = dirname($gmGallery->options['modules_xml']);
					$out_dirpath = 'https://codeasily.com/gmedia_modules';
					foreach ( $modules['out'] as $module ) {
						$module['mclass'] = ' module-filtered module-collection module-not-installed';
						if ( $module['update'] ) {
							$module['mclass'] .= ' module-update';
						}
						foreach ( $module['tags'] as $_tag ) {
							$module['mclass'] .= ' module-tag-' . sanitize_key( $_tag );
						}
						$module['screenshot_url'] = $out_dirpath . '/' . $module['name'] . '.png';

						include dirname( __FILE__ ) . '/tpl/module-item.php';

					}
				}
				wp_nonce_field( 'GmediaGallery' );
				?>
				<div class="media nomodules nomodule-not-installed">
					<h4 class="media-heading"><?php esc_html_e( 'No modules to show', 'grand-media' ); ?></h4>
				</div>
				<div class="media nomodules nomodule-tag">
					<h4 class="media-heading"><?php esc_html_e( 'No modules to show', 'grand-media' ); ?></h4>
				</div>
			</div>
		</div>

	</div>
</div>

<?php if ( $gmCore->caps['gmedia_module_manage'] ) {
	include dirname( __FILE__ ) . '/tpl/modal-modulezip.php';
} ?>
