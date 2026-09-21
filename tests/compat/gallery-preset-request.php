<?php

// Exercise the real gallery processor with inert WordPress and database boundaries.
$root = dirname( __DIR__, 2 );
define( 'ABSPATH', $root . '/' );
define( 'GMEDIA_ABSPATH', $root . '/' );
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) {
		fwrite( STDERR, "Gallery preset request test did not complete.\n" );
		exit( 1 );
	}
} );
class Gmedia_Test_Denied extends RuntimeException {}
class WP_Error {}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_die( $message = '' ) { throw new Gmedia_Test_Denied( $message ); }
function esc_html__( $text, $domain = '' ) { return $text; }
function check_admin_referer( $action, $field = '_wpnonce' ) {
	$GLOBALS['events'][] = 'nonce:' . $action;
	if ( ! isset( $_REQUEST[ $field ] ) || 'valid-' . $action !== $_REQUEST[ $field ] ) {
		wp_die( 'Invalid token' );
	}
}
class GmediaProcessor {
	public $msg = array();
	public $error = array();
	public function __construct() {}
	public static function selected_items( $key ) { return array(); }
}
$gmCore = new class() {
	public $caps = array();
	public function _get( $key, $default = null ) { return isset( $_GET[ $key ] ) ? $_GET[ $key ] : $default; }
	public function _post( $key, $default = null ) { return isset( $_POST[ $key ] ) ? $_POST[ $key ] : $default; }
};
$gmDB = new class() {
	public $preset;
	public function get_term( $id ) { return 77 === $id ? $this->preset : null; }
	public function delete_term( $id ) { $GLOBALS['events'][] = 'delete:' . $id; }
};
$user_ID = 12;
$own_preset = (object) array( 'term_id' => '77', 'taxonomy' => 'gmedia_module', 'name' => '[amron]', 'status' => 'amron', 'global' => '12' );
$gmDB->preset = $own_preset;
require $root . '/admin/processor/class.processor.galleries.php';
class Gmedia_Test_Galleries extends GmediaProcessor_Galleries {
	public function query_args() { return array(); }
	public function run() { $this->processor(); }
}
$failures = array();
$cases = array(
	array( true, true, null, true, array( 'nonce:GmediaGallery' ) ),
	array( true, true, 'invalid', true, array( 'nonce:GmediaGallery' ) ),
	array( true, true, 'valid-GmediaGallery', false, array( 'nonce:GmediaGallery', 'delete:77' ) ),
	array( false, true, 'valid-GmediaGallery', true, array() ),
	array( true, false, 'valid-GmediaGallery', true, array() ),
);
foreach ( $cases as $index => $case ) {
	list( $library, $manage, $nonce, $expected_denied, $expected_events ) = $case;
	$gmCore->caps = array( 'gmedia_library' => $library, 'gmedia_gallery_manage' => $manage );
	$_GET = array();
	$_POST = array( 'module_preset_restore_original' => '1', 'preset_default' => '77' );
	if ( null !== $nonce ) {
		$_POST['_wpnonce'] = $nonce;
	}
	$_REQUEST = $_POST;
	$events = array();
	$denied = false;
	$processor = new Gmedia_Test_Galleries();
	try {
		$processor->run();
	} catch ( Gmedia_Test_Denied $error ) {
		$denied = true;
	}
	if ( $expected_denied !== $denied || $expected_events !== $events || ( $denied && $processor->msg ) ) {
		$failures[] = 'Case ' . $index . ': request guards must precede mutation and success output.';
	}
}
$gmCore->caps = array( 'gmedia_library' => true, 'gmedia_gallery_manage' => true );
$_POST['_wpnonce'] = 'valid-GmediaGallery';
$_REQUEST = $_POST;
$invalid_presets = array( null, new WP_Error() );
foreach ( array( 'global' => '13', 'taxonomy' => 'gmedia_album', 'name' => '[amron] Custom', 'status' => '' ) as $field => $value ) {
	$preset = clone $own_preset;
	$preset->$field = $value;
	$invalid_presets[] = $preset;
}
$global_preset = clone $own_preset;
$global_preset->global = '0';
$invalid_presets[] = $global_preset;
$gallery = clone $own_preset;
$gallery->taxonomy = 'gmedia_gallery';
$invalid_presets[] = $gallery;
foreach ( $invalid_presets as $index => $preset ) {
	$gmDB->preset = $preset;
	$events = array();
	$processor = new Gmedia_Test_Galleries();
	$denied = false;
	try {
		$processor->run();
	} catch ( Gmedia_Test_Denied $error ) {
		$denied = true;
	}
	if ( ! $denied || array( 'nonce:GmediaGallery' ) !== $events || $processor->msg ) {
		$failures[] = 'Preset case ' . $index . ': only the current user default module preset may be restored.';
	}
}
$completed = true;
if ( $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}
echo "Gallery preset request passed: token, capabilities and preset ownership/type checks precede deletion.\n";
