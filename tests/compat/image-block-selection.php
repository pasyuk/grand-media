<?php
$root = dirname( __DIR__, 2 );
define( 'ABSPATH', $root . '/' );
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) { fwrite( STDERR, "Image selection check did not complete.\n" ); exit( 1 ); }
} );
class Selection_Denied extends RuntimeException {}
function add_filter() {}
function add_action() {}
function current_user_can( $cap ) { return in_array( $cap, $GLOBALS['caps'], true ); }
function get_current_user_id() { return 7; }
function check_admin_referer() { if ( empty( $_POST['_wpnonce'] ) ) { throw new Selection_Denied(); } }
function wp_die() { throw new Selection_Denied(); }
function esc_html__( $value ) { return $value; }
function did_action( $action ) { return 'media_upload_gmedia_library' === $action; }
function wp_iframe() {}
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return $value; }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function wp_kses_post( $value ) { return strip_tags( $value, '<b>' ); }
function esc_url_raw( $value ) { return $value; }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function media_send_to_editor() { throw new RuntimeException( 'Classic editor insertion used for a block selection' ); }
$gmCore = new class {
	public function _get( $key, $default = null ) { return $_GET[ $key ] ?? $default; }
	public function _post( $key, $default = null ) { return $_POST[ $key ] ?? $default; }
	public function gm_get_media_image( $media, $size ) { return 'https://example.test/' . $size . '.jpg'; }
};
$gmDB = new class {
	public function get_gmedia( $id ) { return $id ? (object) array( 'ID' => $id, 'author' => $id, 'mime_type' => 10 === $id ? 'audio/mpeg' : 'image/jpeg' ) : null; }
	public function get_metadata() { return array( 'web' => array( 'width' => 640, 'height' => 480 ) ); }
};
$caps = array( 'gmedia_library' );
$_GET = array( 'gmedia_block' => 1 );
require $root . '/inc/media-upload.php';
foreach ( array(
	array( 7, array( 'gmedia_library' ), true, true ),
	array( 8, array( 'gmedia_library' ), true, false ),
	array( 8, array( 'gmedia_library', 'gmedia_show_others_media' ), true, true ),
	array( 7, array(), true, false ),
	array( 7, array( 'gmedia_library' ), false, false ),
	array( 0, array( 'gmedia_library' ), true, false ),
	array( 10, array( 'gmedia_library', 'gmedia_show_others_media' ), true, false ),
) as $case ) {
	list( $id, $caps, $nonce, $allowed ) = $case;
	$_POST = array( 'gmedia_library_insert' => 1, 'ID' => $id, '_wpnonce' => $nonce, 'title' => 'Example' );
	ob_start();
	try { media_upload_gmedia(); $denied = false; }
	catch ( Selection_Denied $error ) { $denied = true; }
	catch ( RuntimeException $error ) { ob_end_clean(); fwrite( STDERR, $error->getMessage() . "\n" ); exit( 1 ); }
	$output = ob_get_clean();
	if ( $denied === $allowed || ( $allowed && ( false === strpos( $output, 'gmedia-image-selected' ) || false === strpos( $output, 'web.jpg' ) ) ) ) {
		fwrite( STDERR, "Image selection permissions or response failed for ID {$id}.\n" ); exit( 1 );
	}
}
$completed = true;
echo "Image block selection, ownership, media type and nonce checks passed.\n";
