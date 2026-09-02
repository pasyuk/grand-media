<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Admin sidebar branding test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);
define( 'ABSPATH', $root . '/' );
define( 'GMEDIA_DBVERSION', 'test-version' );

$menu_args = array();

function add_action() {}
function add_filter() {}
function current_user_can() { return false; }
function add_menu_page( ...$args ) {
	$GLOBALS['menu_args'] = $args;

	return 'toplevel_page_GrandMedia';
}
function add_submenu_page() { return 'gmedia-submenu'; }
function __( $text, $domain = null ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $text, $domain = null ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html_e( $text, $domain = null ) { echo esc_html__( $text, $domain ); }
function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return $url; }
function wp_kses( $html, $allowed = array() ) { return $html; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function get_option() { return GMEDIA_DBVERSION; }
function is_plugin_active() { return true; }
function wp_enqueue_script() {}
function wp_add_inline_script() {}
function gmedia_has_premium_license() { return false; }
function gmedia_get_license_type() { return 'freemius'; }
function gmedia_has_expired_premium_license() { return false; }
function checked( $checked, $current = true, $echo = true ) {
	$result = ( (string) $checked === (string) $current ) ? 'checked="checked"' : '';
	if ( $echo ) {
		echo $result;
	}

	return $result;
}
function plugins_url( $path = '', $plugin = '' ) {
	return 'https://example.test/wp-content/plugins/grand-media/admin/' . ltrim( $path, '/' );
}

$gmCore = new class() {
	public function _get( $name, $default = '' ) { return $default; }
	public function alert() { return ''; }
};
$gmProcessor = (object) array( 'page' => 'GrandMedia', 'msg' => array(), 'error' => array() );
$gmGallery   = (object) array(
	'options' => array(
		'modules_update'    => 0,
		'modules_new'       => 0,
		'notify_new_modules' => 0,
		'feedback'               => 0,
		'twitter'                => 1,
		'disable_ads'            => 1,
		'delete_originals'       => 0,
		'disable_logs'           => 0,
		'wp_term_related_gmedia' => 0,
		'wp_post_related_gmedia' => 0,
	),
);
$gm_allowed_tags = array();
$pagenow         = 'admin.php';

require_once $root . '/admin/admin.php';

class Gmedia_Admin_Sidebar_Branding_Test extends GmediaAdmin {
	public function sideLinks() { return array( 'grandTitle' => 'Library', 'sideLinks' => '' ); }
	public function controller() {}
}

$admin = new Gmedia_Admin_Sidebar_Branding_Test();
$admin->add_menu();
$expected_icon = 'https://example.test/wp-content/plugins/grand-media/admin/assets/img/icon-128x128.png';
if ( ! isset( $menu_args[5] ) || $expected_icon !== $menu_args[5] ) {
	$failures[] = 'Admin menu must use the existing high-resolution Gmedia brand icon';
}

ob_start();
$admin->admin_head();
$head_html = ob_get_clean();
if (
	false === strpos( $head_html, '#adminmenu #toplevel_page_GrandMedia .wp-menu-image img' ) ||
	false === strpos( $head_html, 'width: 20px' ) ||
	false === strpos( $head_html, 'height: 20px' ) ||
	false === strpos( $head_html, 'box-sizing: content-box' )
) {
	$failures[] = 'High-resolution admin menu icon must keep a square 20x20 content box';
}

ob_start();
$admin->shell();
$html = ob_get_clean();
if (
	false !== strpos( $html, 'twitter-timeline' ) ||
	false !== strpos( $html, 'platform.twitter.com/widgets.js' )
) {
	$failures[] = 'Admin sidebar must not render the unreliable external X timeline';
}

$pk = '';
$lk = '';
ob_start();
require $root . '/admin/pages/settings/tpl/license.php';
$settings_html = ob_get_clean();
if ( false !== strpos( $settings_html, 'name="set[twitter]"' ) ) {
	$failures[] = 'Admin settings must not offer a control for the removed X timeline';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Admin sidebar branding passed: high-resolution Gmedia icon without an external X timeline.' . PHP_EOL;
