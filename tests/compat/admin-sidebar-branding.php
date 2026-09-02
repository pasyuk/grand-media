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
function esc_url( $url ) { return $url; }
function wp_kses( $html, $allowed = array() ) { return $html; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function get_option() { return GMEDIA_DBVERSION; }
function is_plugin_active() { return true; }
function wp_enqueue_script() {}
function wp_add_inline_script() {}
function gmedia_has_premium_license() { return false; }
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
		'feedback'          => 0,
		'twitter'           => 1,
		'disable_ads'       => 1,
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
	false === strpos( $head_html, 'height: 20px' )
) {
	$failures[] = 'High-resolution admin menu icon must keep the standard 20x20 layout size';
}

ob_start();
$admin->shell();
$html = ob_get_clean();
$profile_link = '<a class="twitter-timeline" data-height="600" href="https://twitter.com/CodEasily?ref_src=twsrc%5Etfw">Tweets by CodEasily</a>';
if ( false === strpos( $html, $profile_link ) ) {
	$failures[] = 'Admin sidebar must render the supported CodEasily profile timeline';
}
if ( false !== strpos( $html, '/timelines/' ) ) {
	$failures[] = 'Admin sidebar must not render the retired Collection timeline URL';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Admin sidebar branding passed: high-resolution Gmedia icon and supported profile timeline.' . PHP_EOL;
