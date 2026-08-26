<?php

// Test the real dispatcher before any iframe output or template inclusion.
$root = dirname( __DIR__, 2 );
define( 'ABSPATH', $root . '/' );
$failures = array();
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) { fwrite( STDERR, "Iframe access test did not complete.\n" ); exit( 1 ); }
} );
class Gmedia_Iframe_Denied extends RuntimeException {}
class Gmedia_Iframe_Render extends RuntimeException {}
function add_action() {}
function add_filter() {}
function current_user_can( $cap ) { return in_array( $cap, $GLOBALS['caps'], true ); }
function get_current_user_id() { return 7; }
function wp_die() { throw new Gmedia_Iframe_Denied(); }
function esc_html__( $text, $domain = null ) { return $text; }
function check_admin_referer( $action ) {
	if ( ! isset( $_GET['_wpnonce'] ) || 'valid-' . $action !== $_GET['_wpnonce'] ) { wp_die(); }
}
function set_current_screen() { throw new Gmedia_Iframe_Render(); }

$gmCore = new class() {
	public function _get( $key, $default = null ) { return isset( $_GET[ $key ] ) ? $_GET[ $key ] : $default; }
};
$gmDB = new class() {
	public function get_gmedia( $id ) {
		$authors = array( 7 => '7', 8 => '8', 9 => '0' );
		return isset( $authors[ $id ] ) ? (object) array( 'ID' => $id, 'author' => $authors[ $id ] ) : null;
	}
	public function get_term( $id ) {
		if ( ! $id ) { return (object) array( 'errors' => array( 'invalid_term' => 'Empty Term' ) ); }
		$authors = array( 7 => '7', 8 => '8', 9 => '0' );
		return isset( $authors[ $id ] ) ? (object) array( 'term_id' => $id, 'global' => $authors[ $id ], 'taxonomy' => 'gmedia_album' ) : null;
	}
};
$caps = array();
$pagenow = 'admin.php';
$_GET = array();
require $root . '/admin/admin.php';
$admin = new GmediaAdmin();

function gm_iframe_case( $label, $route, $capabilities, $query, $expected ) {
	$GLOBALS['caps'] = $capabilities;
	$_GET = array_merge( array( 'page' => 'GrandMedia_Custom', 'gmediablank' => $route ), $query );
	$status = 'returned';
	ob_start();
	try { $GLOBALS['admin']->gmedia_blank_page(); }
	catch ( Gmedia_Iframe_Denied $error ) { $status = 'denied'; }
	catch ( Gmedia_Iframe_Render $error ) { $status = 'render'; }
	$html = ob_get_clean();
	if ( $expected !== $status || '' !== $html ) { $GLOBALS['failures'][] = $label . ': expected ' . $expected . ' before iframe output, got ' . $status; }
}

$library = array( 'gmedia_library' );
$editor = array( 'gmedia_library', 'gmedia_edit_media' );
foreach ( array( 'library', 'image_editor', 'map_editor', 'comments', 'module_preview', 'update_plugin' ) as $route ) {
	gm_iframe_case( $route . ' low privilege', $route, array(), array( 'id' => '7', 'gmedia_id' => '7' ), 'denied' );
}
gm_iframe_case( 'Custom menu library route', 'library', $library, array(), 'render' );
foreach ( array( 'image_editor', 'map_editor' ) as $route ) {
	gm_iframe_case( $route . ' without edit permission', $route, $library, array( 'id' => '7' ), 'denied' );
	gm_iframe_case( $route . ' owner', $route, $editor, array( 'id' => '7' ), 'render' );
	gm_iframe_case( $route . ' other author', $route, $editor, array( 'id' => '8' ), 'denied' );
	gm_iframe_case( $route . ' shared author needs edit others', $route, $editor, array( 'id' => '9' ), 'denied' );
	gm_iframe_case( $route . ' editor of others', $route, array_merge( $editor, array( 'gmedia_edit_others_media' ) ), array( 'id' => '8' ), 'render' );
	gm_iframe_case( $route . ' edit others still needs edit permission', $route, array_merge( $library, array( 'gmedia_edit_others_media' ) ), array( 'id' => '8' ), 'denied' );
	gm_iframe_case( $route . ' absent media', $route, $editor, array( 'id' => '99' ), 'denied' );
}
foreach ( array( 'gmedia_id', 'gmedia_term_id' ) as $id_key ) {
	gm_iframe_case( $id_key . ' owner comments without edit_posts', 'comments', $library, array( $id_key => '7' ), 'render' );
	gm_iframe_case( $id_key . ' shared comments', 'comments', $library, array( $id_key => '9' ), 'render' );
	gm_iframe_case( $id_key . ' other comments', 'comments', $library, array( $id_key => '8' ), 'denied' );
	gm_iframe_case( $id_key . ' show others comments', 'comments', array_merge( $library, array( 'gmedia_show_others_media' ) ), array( $id_key => '8' ), 'render' );
	gm_iframe_case( $id_key . ' absent comments', 'comments', $library, array( $id_key => '99' ), 'denied' );
}
gm_iframe_case( 'Comment route needs an item', 'comments', $library, array(), 'denied' );
gm_iframe_case( 'Module preview uses existing module capability', 'module_preview', array( 'gmedia_module_manage' ), array(), 'render' );
gm_iframe_case( 'Library alone cannot preview modules', 'module_preview', $library, array(), 'denied' );
gm_iframe_case( 'Updater requires nonce before rendering', 'update_plugin', array( 'manage_options' ), array(), 'denied' );
gm_iframe_case( 'Updater valid route', 'update_plugin', array( 'manage_options' ), array( '_wpnonce' => 'valid-gmedia_update' ), 'render' );
gm_iframe_case( 'Extension route remains available', 'custom_extension', $library, array(), 'render' );

$completed = true;
if ( $failures ) { fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL ); exit( 1 ); }
echo "Admin iframe access passed: capability/ownership checks precede output; legitimate custom routes stay usable.\n";
