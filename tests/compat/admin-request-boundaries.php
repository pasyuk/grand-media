<?php

// Exercise real handlers with inert WordPress boundaries: no mail or database writes.
$root = dirname( __DIR__, 2 );
define( 'ABSPATH', $root . '/' );
define( 'GMEDIA_ABSPATH', $root . '/' );
$failures = array();
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) {
		fwrite( STDERR, "Admin request boundary test did not complete.\n" );
		exit( 1 );
	}
} );
class Gmedia_Test_Denied extends RuntimeException {}
class Gmedia_Test_Update_Reached extends RuntimeException {}
function gm_assert( $condition, $message ) {
	if ( ! $condition ) { $GLOBALS['failures'][] = $message; }
}
function add_action() {}
function add_filter() {}
function current_user_can( $capability ) { return ! empty( $GLOBALS['test_caps'][ $capability ] ); }
function wp_die() { throw new Gmedia_Test_Denied(); }
function check_admin_referer( $action, $field = '_wpnonce' ) {
	$GLOBALS['nonce_checks'][] = $action;
	if ( ! isset( $_REQUEST[ $field ] ) || ! is_string( $_REQUEST[ $field ] ) || 'valid-' . $action !== $_REQUEST[ $field ] ) { wp_die(); }
	return 1;
}
function wp_verify_nonce( $nonce, $action ) {
	$GLOBALS['verified_nonce'] = $nonce;
	return is_string( $nonce ) && 'valid-' . $action === $nonce;
}
function wp_nonce_field( $action ) { echo '<input name="_wpnonce" value="valid-' . esc_attr( $action ) . '">'; }
function wp_nonce_url( $url, $action ) { return $url . '&_wpnonce=valid-' . $action; }
function wp_create_nonce( $action ) { return 'valid-' . $action; }
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value ); }
function sanitize_text_field( $value ) { return is_scalar( $value ) ? trim( strip_tags( (string) $value ) ) : ''; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function __( $text, $domain = null ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_url( $text ) { return esc_html( $text ); }
function esc_js( $text ) { return addslashes( $text ); }
function esc_textarea( $text ) { return esc_html( $text ); }
function esc_html__( $text, $domain = null ) { return esc_html( $text ); }
function esc_html_e( $text, $domain = null ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = null ) { echo esc_attr( $text ); }
function wp_kses_post( $html ) { return $html; }
function wp_get_current_user() { return (object) array( 'display_name' => 'Fixture', 'user_email' => 'fixture@example.test' ); }
function is_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ); }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function wp_login_url() { return home_url( '/wp-login.php' ); }
function admin_url( $path ) { return home_url( '/wp-admin/' . $path ); }
function plugins_url( $path ) { return home_url( '/plugin/' . $path ); }
function plugin_dir_url( $path ) { return home_url( '/plugin/' ); }
function add_query_arg( $key, $value, $url = '' ) { return is_array( $key ) ? $value . '&' . http_build_query( $key ) : $url . '&' . $key . '=' . $value; }
function wp_mail( ...$args ) { $GLOBALS['mail'][] = $args; return true; }
function did_action( $action ) { return $action === $GLOBALS['media_tab']; }
function wp_iframe( $callback ) { $GLOBALS['iframes'][] = $callback; }
function media_send_to_editor( $html ) { $GLOBALS['editor'][] = $html; }
function image_add_caption( $html, $id, $caption ) { return '[caption]' . $html . $caption . '[/caption]'; }
function update_post_meta( ...$args ) { $GLOBALS['metadata'][] = array_merge( array( 'update' ), $args ); }
function delete_post_meta( ...$args ) { $GLOBALS['metadata'][] = array_merge( array( 'delete' ), $args ); }
function get_transient( $name ) {
	if ( 'gmediaUpgrade' === $name ) { return $GLOBALS['upgrading']; }
	$GLOBALS['update_reached'] = true;
	throw new Gmedia_Test_Update_Reached(); // Stop at the first DB boundary, before SQL/includes.
}
function delete_transient( $name ) { $GLOBALS['deleted_transients'][] = $name; }
function wp_enqueue_style( $handle ) { $GLOBALS['enqueued'][] = $handle; }
function wp_enqueue_script( $handle ) { $GLOBALS['enqueued'][] = $handle; }

$gmCore = new class() {
	public function _post( $name, $default = null ) { return isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : $default; }
	public function _get( $name, $default = null ) { return isset( $_GET[ $name ] ) ? wp_unslash( $_GET[ $name ] ) : $default; }
	public function gm_get_media_image() { return 'https://example.test/photo.jpg'; }
	public function is_digit( $value ) { return ctype_digit( (string) $value ); }
	public function alert( $type, $text ) { return $text; }
};
$gmDB = new class() {
	public function get_gmedia( $id ) { return (object) array( 'ID' => $id ); }
	public function get_metadata() { return array( 'web' => array( 'width' => 640, 'height' => 480 ) ); }
	public function get_term( $id ) { return (object) array( 'term_id' => $id, 'status' => 'phantom' ); }
};
$gmGallery = (object) array( 'options' => array() );
$test_caps = array( 'gmedia_library' => true, 'gmedia_upload' => true, 'manage_options' => true, 'edit_post' => true );
require $root . '/inc/media-upload.php';
require $root . '/inc/post-metabox.php';
require $root . '/admin/support.php';
require $root . '/config/update.php';
require $root . '/admin/class.processor.php';

function gm_request( $post = array(), $get = array() ) {
	$_POST = $post;
	$_GET = $get;
	$_REQUEST = array_merge( $get, $post );
	foreach ( array( 'mail', 'iframes', 'editor', 'metadata', 'nonce_checks', 'deleted_transients', 'enqueued' ) as $key ) { $GLOBALS[ $key ] = array(); }
	$GLOBALS['update_reached'] = false;
	$GLOBALS['verified_nonce'] = null;
}
function gm_call( $callback ) {
	ob_start();
	$status = 'returned';
	try { $callback(); } catch ( Gmedia_Test_Denied $error ) { $status = 'denied'; } catch ( Gmedia_Test_Update_Reached $error ) { $status = 'update'; }
	return array( $status, ob_get_clean() );
}

// Existing media-form tokens protect all three insertion paths; GET tabs stay open.
$media_cases = array(
	'gmedia_library_insert' => array( 'ID' => '7', 'title' => 'Fixture', 'size' => 'web' ),
	'gmedia_gallery_insert' => array( 'shortcode' => '[gmedia id=7]' ),
	'gmedia_term_insert' => array( 'taxonomy' => 'gmedia_album', 'term_id' => '7', 'module_preset' => '9' ),
);
foreach ( $media_cases as $button => $fields ) {
	$media_tab = 'media_upload_gmedia_' . ( 'gmedia_library_insert' === $button ? 'library' : ( 'gmedia_gallery_insert' === $button ? 'galleries' : 'terms' ) );
	foreach ( array( null, 'invalid' ) as $nonce ) {
		gm_request( array_merge( $fields, array( $button => 'Insert', '_wpnonce' => $nonce ) ) );
		$result = gm_call( 'media_upload_gmedia' );
		gm_assert( 'denied' === $result[0] && ! $editor && ! $iframes, $button . ': reject before iframe or editor output' );
	}
	gm_request( array_merge( $fields, array( $button => 'Insert', '_wpnonce' => 'valid-media-form' ) ) );
	$result = gm_call( 'media_upload_gmedia' );
	gm_assert( 'returned' === $result[0] && 1 === count( $editor ) && array( 'media-form' ) === $nonce_checks, $button . ': valid insertion stays available' );
	if ( 'gmedia_library_insert' === $button ) {
		gm_assert( false !== strpos( $editor[0], "width='640' height='480'" ) && false !== strpos( $editor[0], "id='gmedia-image-7'" ), 'Library insertion HTML remains unchanged' );
	} else {
		gm_assert( ( 'gmedia_gallery_insert' === $button ? '[gmedia id=7]' : '[gm album=7 module=phantom preset=9]' ) === $editor[0], 'Shortcode insertion remains unchanged' );
	}
	gm_request();
	gm_call( 'media_upload_gmedia' );
	gm_assert( 1 === count( $iframes ) && ! $editor && ! $nonce_checks, 'GET tab navigation needs no nonce' );
}
$media_tab = 'media_upload_gmedia_library';
gm_request( array(), array( 'action' => 'upload' ) );
gm_call( 'media_upload_gmedia' );
gm_assert( array( 'gmedia_add_media_upload' ) === $iframes, 'Authorized upload tab remains available' );

// Existing edit_post + GmediaGallery checks remain, with scalar normalized tokens.
foreach ( array( null, 'invalid', array( 'invalid' ) ) as $nonce ) {
	gm_request( array( '_wpnonce_related_gmedia' => $nonce, '_related_gmedia' => '7' ) );
	gm_call( static function () { gmedia_related_post_metabox_save( 42, true ); } );
	gm_assert( ! $metadata, 'Invalid metabox token must not write metadata' );
}
gm_request( array( '_wpnonce_related_gmedia' => '  \\valid-GmediaGallery  ', '_related_gmedia' => '7', '_related_gmedia_per_page' => '12' ) );
gm_call( static function () { gmedia_related_post_metabox_save( 42, true ); } );
gm_assert( 'valid-GmediaGallery' === $verified_nonce && array( array( 'update', 42, '_related_gmedia', 7 ), array( 'update', 42, '_related_gmedia_per_page', '12' ) ) === $metadata, 'Metabox token is normalized and authorized metadata values preserved' );
$test_caps['edit_post'] = false;
gm_request( array( '_wpnonce_related_gmedia' => 'valid-GmediaGallery', '_related_gmedia' => '7' ) );
gm_call( static function () { gmedia_related_post_metabox_save( 42, true ); } );
gm_assert( ! $metadata, 'Metabox still requires edit_post' );
$test_caps['edit_post'] = true;
gm_request( array( '_wpnonce_related_gmedia' => 'valid-GmediaGallery' ) );
gm_call( static function () { gmedia_related_post_metabox_save( 42, true ); } );
gm_assert( array( array( 'delete', 42, '_related_gmedia' ), array( 'delete', 42, '_related_gmedia_per_page' ) ) === $metadata, 'Metabox clearing preserves both deletion operations' );

// All mail is captured locally; fixture contains no credentials or license data.
$support = array( 'subject' => 'feature_request', 'name' => 'Fixture', 'email' => 'fixture@example.test', 'summary' => 'Fixture summary', 'message' => 'Fixture message' );
foreach ( array( null, 'invalid' ) as $nonce ) {
	gm_request( array_merge( $support, array( '_wpnonce' => $nonce ) ) );
	$result = gm_call( 'gmediaSupport' );
	gm_assert( 'denied' === $result[0] && ! $mail, 'Support submission requires its form token' );
}
$test_caps['manage_options'] = false;
gm_request( array_merge( $support, array( '_wpnonce' => 'valid-gmedia_support' ) ) );
$result = gm_call( 'gmediaSupport' );
gm_assert( 'denied' === $result[0] && ! $mail, 'Support submission requires manage_options before mail' );
$test_caps['manage_options'] = true;
gm_request( array_merge( $support, array( '_wpnonce' => 'valid-gmedia_support' ) ) );
$result = gm_call( 'gmediaSupport' );
gm_assert( 1 === count( $mail ) && 'gmediafolder@gmail.com' === $mail[0][0] && 'Feature Request' === $mail[0][1] && false !== strpos( $mail[0][2], 'Fixture message' ), 'Authorized support recipient, subject and message remain unchanged' );
gm_request();
$result = gm_call( 'gmediaSupport' );
gm_assert( 'returned' === $result[0] && ! $mail && ! $nonce_checks && false !== strpos( $result[1], 'value="valid-gmedia_support"' ), 'Support GET renders token without sending mail' );

// The updater entry point is guarded before any transient or database mutation.
$upgrading = false;
foreach ( array( array(), array( 'reset_update_process' => '1' ) ) as $get ) {
	foreach ( array( null, 'invalid' ) as $nonce ) {
		gm_request( array(), array_merge( $get, array( '_wpnonce' => $nonce ) ) );
		$result = gm_call( 'gmedia_do_update' );
		gm_assert( 'denied' === $result[0] && ! $deleted_transients && ! $update_reached, 'Updater requires token before start/reset effects' );
	}
}
$test_caps['manage_options'] = false;
gm_request( array(), array( '_wpnonce' => 'valid-gmedia_update', 'reset_update_process' => '1' ) );
$result = gm_call( 'gmedia_do_update' );
gm_assert( 'denied' === $result[0] && ! $deleted_transients && ! $update_reached, 'Updater requires manage_options before all effects' );
$test_caps['manage_options'] = true;
gm_request( array(), array( '_wpnonce' => 'valid-gmedia_update' ) );
$result = gm_call( 'gmedia_do_update' );
gm_assert( 'update' === $result[0] && array( 'gmedia_update' ) === $nonce_checks && ! $deleted_transients, 'Valid updater start reaches original DB boundary' );
gm_request( array(), array( '_wpnonce' => 'valid-gmedia_update', 'reset_update_process' => '1' ) );
gm_call( 'gmedia_do_update' );
gm_assert( array( 'gmediaHeavyJob', 'gmediaUpgrade', 'gmediaUpgradeSteps' ) === $deleted_transients, 'Valid reset retains exact transient targets' );
gm_request();
$result = gm_call( 'gmedia_upgrade_required_admin_notice' );
gm_assert( false !== strpos( $result[1], '_wpnonce=valid-gmedia_update' ), 'Updater link supplies the action nonce' );
$upgrading = time();
gm_request();
$result = gm_call( 'gmedia_upgrade_progress_panel' );
gm_assert( 'returned' === $result[0] && ! $update_reached && ! $deleted_transients && false !== strpos( $result[1], 'valid-gmedia_ajax_long_operations' ), 'Existing upgrade progress only polls the protected worker without rerunning DB changes' );
gm_request( array(), array( 'reset_update_process' => '1' ) );
$result = gm_call( 'gmedia_upgrade_progress_panel' );
gm_assert( 'denied' === $result[0] && ! $deleted_transients, 'Running upgrade still requires a nonce for reset' );

// Read-only selection/navigation remains compatible without action nonces.
gm_request( array( 'selected_items' => '7,no,8' ) );
$_COOKIE = array( 'fixture' => '9.10' );
gm_assert( array( 0 => '7', 2 => '8' ) === GmediaProcessor::selected_items( 'fixture' ), 'POST selection retains numeric filtering and precedence' );
gm_request();
gm_assert( array( '9', '10' ) === GmediaProcessor::selected_items( 'fixture' ), 'Cookie selection remains available without a nonce' );
gm_request( array(), array( 'post' => '42', 'action' => 'edit' ) );
gmedia_meta_box_load_scripts( 'post.php' );
gm_assert( array( 'gmedia-meta-box', 'gmedia-meta-box' ) === $enqueued, 'Existing editor GET still enqueues metabox assets' );
gm_request( array(), array( 'page' => 'GrandMedia_Custom', 'custom_filter' => '7&8', 'edit_term' => '7x', 'do_gmedia' => 'fixture', '_wpnonce_action' => 'omit', 's' => 'A&B' ) );
ob_start();
include $root . '/admin/tpl/search-form.php';
$html = ob_get_clean();
gm_assert( false !== strpos( $html, 'value="GrandMedia_Custom"' ) && false !== strpos( $html, 'name="custom_filter" value="7&amp;8"' ) && false !== strpos( $html, 'name="edit_term" value="7"' ) && false === strpos( $html, 'name="do_gmedia"' ) && false === strpos( $html, 'name="_wpnonce_action"' ), 'Search preserves escaped custom navigation but omits action tokens' );

$completed = true;
if ( $failures ) { fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL ); exit( 1 ); }
echo "Admin request boundaries passed: editor, metadata, support, updater and read-only navigation.\n";
