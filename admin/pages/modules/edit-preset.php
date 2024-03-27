<?php
/**
 * Gmedia Gallery Edit
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$term_id              = (int) $gmCore->_get( 'preset', 0 );
$preset_module        = $gmCore->_get( 'preset_module' );
$gmedia_url           = add_query_arg( array( 'preset_module' => $preset_module, 'preset' => $term_id ), $gmProcessor->url );
$gmedia_term_taxonomy = 'gmedia_module';
$taxterm              = str_replace( 'gmedia_', '', $gmedia_term_taxonomy );

if ( ! gm_user_can( "{$taxterm}_manage" ) ) {
	die( '-1' );
}

$term = $gmDB->get_term( $term_id );
gmedia_module_preset_more_data( $term );

$term_id = $term->term_id;

$gmedia_modules = get_gmedia_modules( false );

$default_module_demo_query_args = get_user_option( 'gmedia_preset_demo_query_args' );
$gmedia_filter                  = gmedia_gallery_query_data( $default_module_demo_query_args );
$default_options                = array();

if ( isset( $gmedia_modules['in'][ $term->module['name'] ] ) ) {

	/**
	 * @var $module_name
	 * @var $module_path
	 * @var $options_tree
	 * @var $default_options
	 */
	extract( $gmedia_modules['in'][ $term->module['name'] ] );
	if ( is_file( $module_path . '/index.php' ) && is_file( $module_path . '/settings.php' ) ) {
		/** @noinspection PhpIncludeInspection */
		include $module_path . '/index.php';
		/** @noinspection PhpIncludeInspection */
		include $module_path . '/settings.php';

	} else {
		// translators: module name.
		$alert[] = sprintf( esc_html__( 'Module `%s` is broken. Choose another module from the list.' ), esc_html( $module_name ) );
	}
} else {
	// translators: module name.
	$alert[] = sprintf( esc_html__( 'Can\'t get module with name `%s`. Choose module from the list.' ), esc_html( $term->module['name'] ) );
}

if ( ! empty( $alert ) ) {
	echo wp_kses_post( $gmCore->alert( 'danger', $alert ) );
}

if ( ! empty( $term->module['settings'] ) ) {
	$gallery_settings = $gmCore->array_replace_recursive( $default_options, $term->module['settings'] );
} else {
	$gallery_settings = $default_options;
}

$params               = array();
$gallery_link_default = add_query_arg( array( 'page' => 'GrandMedia', 'gmediablank' => 'module_preview', 'module' => $term->module['name'], 'preset' => $term->term_id, 'query' => $gmedia_filter['query_args'] ), admin_url( 'admin.php' ) );

/** @noinspection PhpIncludeInspection */
require_once GMEDIA_ABSPATH . 'inc/module.options.php';

do_action( 'gmedia_module_preset_before_panel' );
?>

<div class="card m-0 mw-100 p-0 panel-fixed-header">

	<?php
	require dirname( __FILE__ ) . '/tpl/module-preset-panel-heading.php';

	require dirname( __FILE__ ) . '/tpl/module-preset-edit-item.php';
	?>

</div>

<?php
do_action( 'gmedia_module_preset_after_panel' );
?>
<div class="modal fade gmedia-modal" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"></h4>
				<div class="btn-toolbar gap-4 float-end" style="margin-top:-4px;">
					<button type="button" class="btn btn-primary"><?php esc_html_e( 'Submit', 'grand-media' ); ?></button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'grand-media' ); ?></button>
				</div>
			</div>
			<div class="modal-body"></div>
		</div>
	</div>
</div>
