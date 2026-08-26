<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Admin resources test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);
define( 'ABSPATH', $root . '/' );
define( 'GMEDIA_DBVERSION', 'test-version' );

$enqueued = array();
$inline   = array();
$db_ready = true;

// Capture WordPress resource boundaries; no remote SDK is fetched or executed.
function wp_enqueue_script( $handle, $src = '', $deps = array(), $version = false, $footer = false ) {
	$GLOBALS['enqueued'][ $handle ] = array( $src, $deps, $version, $footer );
}
function wp_add_inline_script( $handle, $data, $position = 'after' ) {
	$GLOBALS['inline'][ $handle ] = array( $data, $position );
}
function add_action() {}
function add_filter() {}
function get_option( $name ) { return $GLOBALS['db_ready'] ? GMEDIA_DBVERSION : 'old-version'; }
function current_user_can() { return false; }
function is_plugin_active() { return true; }
function __( $text, $domain = null ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return esc_html( $text ); }
function esc_html__( $text, $domain = null ) { return esc_html( $text ); }
function esc_html_e( $text, $domain = null ) { echo esc_html( $text ); }
function esc_url( $url ) { return str_replace( '&', '&#038;', $url ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path . '&source=fixture'; }
function wp_kses( $html, $allowed = array() ) { return $html; }
function checked() {}
function gmedia_get_license_type() { return 'none'; }
function gmedia_has_premium_license() { return false; }
function gmedia_has_expired_premium_license() { return false; }

$gmCore = new class() {
	public function _get( $name, $default = '' ) { return $default; }
	public function alert() { return ''; }
};
$gmProcessor = (object) array( 'page' => 'GrandMedia', 'msg' => array(), 'error' => array() );
$gmGallery   = (object) array(
	'options' => array_fill_keys( array( 'feedback', 'twitter', 'license_name', 'license_key2', 'delete_originals', 'disable_logs', 'wp_term_related_gmedia', 'wp_post_related_gmedia', 'disable_ads' ), '' ),
);
$gm_allowed_tags = array();
$pagenow         = 'index.php';

require_once $root . '/admin/admin.php';

// Keep the actual shell while isolating unrelated menu/page-controller work.
class Gmedia_Admin_Resources_Test extends GmediaAdmin {
	public function sideLinks() { return array( 'grandTitle' => 'Library', 'sideLinks' => '' ); }
	public function controller() {}
}
$admin = new Gmedia_Admin_Resources_Test();
$admin->load_scripts( 'index.php' );
if ( $enqueued || $inline ) {
	$failures[] = 'Unrelated admin screens must not load donation resources';
}

$db_ready = false;
ob_start();
$admin->shell();
if ( '' !== ob_get_clean() || $enqueued || $inline ) {
	$failures[] = 'A blocked shell must not render or enqueue donation resources';
}
$db_ready = true;
ob_start();
$admin->shell();
$html = ob_get_clean();
if ( false === strpos( $html, "id='donate-button'" ) || false !== strpos( $html, 'donate-sdk.js' ) || false !== strpos( $html, 'PayPal.Donation.Button' ) ) {
	$failures[] = 'Donation shell must retain its container without raw SDK or initializer tags';
}
$handle = 'gmedia-paypal-donate';
if ( ! isset( $enqueued[ $handle ] ) || array( 'https://www.paypalobjects.com/donate/sdk/donate-sdk.js', array(), null, true ) !== $enqueued[ $handle ] ) {
	$failures[] = 'Donation SDK must be enqueued in the footer with its original URL';
}
if ( ! isset( $inline[ $handle ] ) || 'after' !== $inline[ $handle ][1] ) {
	$failures[] = 'Donation initializer must run after its SDK';
} else {
	foreach ( array(
		'PayPal.Donation.Button({',
		"env: 'production'",
		"hosted_button_id: 'QC8SXC3HSSJ36'",
		"src: 'https://pics.paypal.com/00/s/NWYwYzFhMjktZjY2NS00MTE5LThkNmMtYjBjZjA3OTNlZDNk/file.PNG'",
		"alt: 'Donate with PayPal button'",
		"title: 'PayPal - The safer, easier way to pay online!'",
		"}).render('#donate-button');",
	) as $setting ) {
		if ( false === strpos( $inline[ $handle ][0], $setting ) ) {
			$failures[] = 'Donation initializer changed an existing setting: ' . $setting;
		}
	}
}

$pk = '';
$lk = '';
ob_start();
include $root . '/admin/pages/settings/tpl/license.php';
$license_html = ob_get_clean();
$pricing_href = 'href="https://example.test/wp-admin/admin.php?page=GrandMedia-pricing&#038;source=fixture"';
if ( 2 !== substr_count( $license_html, $pricing_href ) ) {
	$failures[] = 'Both pricing links must escape the admin URL in their href attributes';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
$completed = true;
echo 'Admin resources passed: scoped footer SDK, unchanged donation settings, escaped pricing links.' . PHP_EOL;
