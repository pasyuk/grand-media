<?php

$root = dirname( __DIR__, 2 );
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) {
		fwrite( STDERR, "App request tests did not complete.\n" );
		exit( 1 );
	}
} );
define( 'ABSPATH', dirname( $root, 3 ) . '/' );
define( 'WP_CONTENT_DIR', sys_get_temp_dir() );
define( 'WP_CONTENT_URL', 'https://example.test/wp-content' );
define( 'GMEDIA_UPLOAD_FOLDER', 'gmedia-test' );
function is_multisite() { return false; }
function is_main_site() { return true; }
function plugins_url() { return 'https://example.test/grand-media'; }
function set_url_scheme( $url ) { return $url; }
function apply_filters( $hook, $value ) { return $value; }
function wp_mkdir_p() { return true; }
function add_action() {}
function add_filter() {}
function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }
function sanitize_text_field( $value ) { return is_scalar( $value ) ? trim( strip_tags( $value ) ) : ''; }
function wp_kses_post( $value ) { return strip_tags( $value, '<p><b><strong><a><br>' ); }
function urldecode_deep( $value ) { return is_array( $value ) ? array_map( 'urldecode_deep', $value ) : urldecode( $value ); }
function map_deep( $value, $callback ) { return is_array( $value ) ? array_map( static function ( $item ) use ( $callback ) { return map_deep( $item, $callback ); }, $value ) : call_user_func( $callback, $value ); }
function absint( $value ) { return abs( (int) $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function current_user_can( $cap ) { return ! empty( $GLOBALS['app_test_caps'][ $cap ] ); }
function get_option( $key, $default = false ) {
	if ( 'gmediaOptions' === $key ) { return $GLOBALS['gmGallery']->options; }
	return array( 'blog_charset' => 'UTF-8', 'admin_email' => 'owner@example.test' )[ $key ] ?? $default;
}
function home_url() { return 'https://example.test'; }
function wp_get_current_user() { return (object) array( 'display_name' => 'Owner' ); }
function wp_nonce_field() {}
function wp_create_nonce() { return 'valid'; }
function get_bloginfo() { return 'Fixture'; }
class App_Test_Denied extends RuntimeException {}
function wp_die() { throw new App_Test_Denied(); }
function check_admin_referer( $action ) {
	if ( 'GmediaService' !== $action || 'valid' !== ( $_GET['_wpnonce'] ?? '' ) ) { throw new App_Test_Denied(); }
}
function update_option( $key, $value ) { $GLOBALS['app_test_updates'][] = $value; return true; }

require_once $root . '/inc/core.php';
require_once $root . '/admin/app.php';
$gmGallery = (object) array( 'options' => array( 'site_ID' => 0, 'mobile_app' => 0, 'license_key' => '', 'cache_expiration' => 0 ) );
$wp = (object) array( 'query_vars' => array( 'gmedia-app' => true ) );
$_GET = array();
$_POST = array();
$_FILES = array();
if ( in_array( '--multipart-array', $argv, true ) ) {
	$_POST['account'] = array( 'invalid' );
	$_FILES['userfile'] = array( 'name' => 'fixture.png' );
}
ob_start();
require_once $root . '/app/access.php';
ob_end_clean();
if ( in_array( '--multipart-array', $argv, true ) ) {
	$completed = true;
	echo "Malformed multipart account rejected without a runtime error.\n";
	exit;
}
$gmapp_version = 3.2;
$user_ID = 5;
$gmDB = new class {
	public $changes = array();
	public function get_gmedia( $id ) { return (object) array( 'ID' => $id, 'author' => 17 === (int) $id ? 5 : 7 ); }
	public function delete_gmedia_term_relationships( $id, $taxonomy ) { $this->changes[] = $id; }
};
$failures = array();
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );

foreach ( array( array(), array( 'userfile' => array() ), array( 'userfile' => array( 'tmp_name' => array(), 'name' => array(), 'error' => array() ) ) ) as $files ) {
	$_FILES = $files;
	$app_test_caps = array( 'gmedia_upload' => true );
	try {
		$result = gmedia_ios_app_processor( 'do_library', array( 'action' => 'add_media', 'item' => array() ) );
		if ( empty( $result['error'] ) ) { $failures[] = 'Malformed upload was not rejected.'; }
	} catch ( Throwable $error ) { $failures[] = 'Malformed upload raised ' . $error->getMessage(); }
}
$_FILES = array();
foreach ( array( array( array( 'gmedia_upload' => true ), 17 ), array( array( 'gmedia_upload' => true, 'gmedia_edit_media' => true ), 18 ) ) as $case ) {
	$app_test_caps = $case[0];
	try {
		$result = gmedia_ios_app_processor( 'do_library', array( 'action' => 'add_media', 'item' => array( 'ID' => $case[1] ) ) );
		if ( empty( $result['error'] ) || false === strpos( $result['error']['message'], 'allowed to edit' ) ) { $failures[] = 'Existing media upload was not rejected before upload processing.'; }
	} catch ( Throwable $error ) { $failures[] = 'Existing media authorization did not precede upload processing.'; }
}
foreach ( array( array( false, 17 ), array( true, 18 ) ) as $case ) {
	$app_test_caps = array( 'gmedia_upload' => true, 'gmedia_edit_media' => true, 'gmedia_edit_others_media' => $case[0] );
	$result = gmedia_ios_app_processor( 'do_library', array( 'action' => 'add_media', 'item' => array( 'ID' => $case[1] ) ) );
	if ( empty( $result['error'] ) || false !== strpos( $result['error']['message'], 'allowed to edit' ) ) { $failures[] = 'Permitted media replacement did not reach upload validation.'; }
}
foreach ( array( array( false, false, array() ), array( true, false, array( 17 ) ), array( true, true, array( 17, 18 ) ) ) as $case ) {
	$app_test_caps = array( 'gmedia_edit_media' => true, 'gmedia_terms' => $case[0], 'gmedia_edit_others_media' => $case[1] );
	$gmDB->changes = array();
	gmedia_ios_app_processor( 'do_library', array( 'action' => 'assign_album', 'assign_album' => array( '0' ), 'selected' => array( 17, 18 ) ) );
	if ( $case[2] !== $gmDB->changes ) { $failures[] = 'Album removal exceeded the permitted media scope.'; }
}

$app_test_caps = array( 'manage_options' => true );
foreach ( array( array(), array( 'REMOTE_ADDR' => array(), 'SERVER_ADDR' => array(), 'HTTP_X_REAL_IP' => array() ) ) as $server ) {
	$_SERVER = $server;
	try {
		$gmCore->app_service( 'app_deactivateplugin' );
		ob_start();
		gmediaApp();
		ob_end_clean();
	} catch ( Throwable $error ) {
		if ( ob_get_level() ) { ob_end_clean(); }
		$failures[] = 'Missing or malformed server addresses raised ' . $error->getMessage();
	}
}
$_SERVER = array( 'REMOTE_ADDR' => '192.0.2.1', 'SERVER_ADDR' => '192.0.2.2' );
foreach ( array( array( false, 'valid', false ), array( true, '', false ), array( true, 'valid', true ) ) as $case ) {
	$app_test_caps = array( 'manage_options' => $case[0] );
	$_GET = array( 'force_app_status' => '1', 'force_site_id' => '12', '_wpnonce' => $case[1] );
	$app_test_updates = array();
	ob_start();
	try { gmediaApp(); } catch ( App_Test_Denied $error ) {}
	ob_end_clean();
	if ( $case[2] !== ! empty( $app_test_updates ) ) { $failures[] = 'App override did not require both administrator permission and the service nonce.'; }
}
restore_error_handler();
passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --multipart-array', $account_status );
if ( 0 !== $account_status ) { $failures[] = 'Multipart account arrays must not reach string decoding.'; }
if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
$completed = true;
echo "App request guards passed: upload shape, media ownership, album permissions, server defaults and admin override.\n";
