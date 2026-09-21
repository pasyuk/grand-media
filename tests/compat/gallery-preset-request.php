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
	public function delete_term( $id ) { $GLOBALS['events'][] = 'delete:' . $id; }
};
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
$completed = true;
if ( $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}
echo "Gallery preset request passed: token and capability checks precede deletion.\n";
