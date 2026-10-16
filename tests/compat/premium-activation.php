<?php
/** Regression: loading the second edition must reach the SDK activation hook. */
$root = dirname( __DIR__, 2 );
define( 'ABSPATH', dirname( $root, 3 ) . '/' );
define( 'WP_FS__DEV_MODE', false );
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) {
		fwrite( STDERR, "Premium activation regression did not complete.\n" );
		exit( 1 );
	}
} );

// Isolate SDK networking and gallery construction; execute the real entry file.
class Gmedia {}
function gmg_fs() { return null; }
require $root . '/grand-media.php';
$cleanup = new ReflectionFunction( 'gmg_fs_uninstall_cleanup' );
require $root . '/grand-media.php';
if ( $cleanup->getFileName() !== ( new ReflectionFunction( 'gmg_fs_uninstall_cleanup' ) )->getFileName() ) {
	throw new RuntimeException( 'The second edition replaced uninstall cleanup.' );
}

// Execute the bundled SDK activation method, isolating its network/account work.
// This guards against replacing its existing free/premium switching with custom logic.
$source = file_get_contents( $root . '/vendor/freemius/includes/class-freemius.php' );
$start = strpos( $source, 'function _activate_plugin_event_hook()' );
// Extract by balanced PHP tokens, so comments and strings cannot end the method.
$tokens = token_get_all( '<?php ' . substr( $source, $start ) );
$method = '';
$depth = 0;
$opened = false;
foreach ( array_slice( $tokens, 1 ) as $token ) {
	$method .= is_array( $token ) ? $token[1] : $token;
	if ( '{' === $token || ( is_array( $token ) && in_array( $token[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) ) { ++$depth; $opened = true; }
	if ( '}' === $token && 0 === --$depth && $opened ) { break; }
}
class FS_Api { public static function clear_cache() {} }
class Activation_Boundaries {
	public $_logger;
	public $_storage;
	public $_slug = 'grand-media';
	public $_free_plugin_basename = 'grand-media/grand-media.php';
	public $_is_network_active = false;
	public $_is_multisite_integrated = false;
	public $_anonymous_mode = false;
	public $_isAutoInstall = false;
	public function __construct() {
		$this->_logger = new class { public function __call( $name, $args ) {} };
		$this->_storage = new stdClass();
	}
	public function __call( $name, $args ) { return false; }
	public function is_user_admin() { return true; }
	public function is_plugin() { return true; }
	public function premium_plugin_basename() { return 'grand-media-premium/grand-media.php'; }
	public function apply_filters( $name, $value ) { return $value; }
}
eval( 'class Activation_Under_Test extends Activation_Boundaries { ' . $method . ' }' );

// Real WordPress plugin state transitions, with in-memory options and hooks.
require ABSPATH . 'wp-admin/includes/plugin.php';
function plugin_basename( $file ) { return $file; }
function current_filter() { return $GLOBALS['activation_hook']; }
function is_multisite() { return false; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function update_option( $key, $value ) { $GLOBALS['options'][ $key ] = $value; }
function wp_is_recovery_mode() { return false; }
function do_action( $name, ...$args ) { $GLOBALS['actions'][] = $name; }

foreach ( array( 'grand-media', 'grand-media-premium' ) as $target ) {
	$other = 'grand-media' === $target ? 'grand-media-premium' : 'grand-media';
	$GLOBALS['options'] = array(
		'active_plugins' => array( "$other/grand-media.php", 'unrelated/plugin.php' ),
		'gmedia_options' => array( 'gallery' => 'keep', 'license' => 'fixture' ),
	);
	$before = $GLOBALS['options']['gmedia_options'];
	$GLOBALS['actions'] = array();
	$GLOBALS['activation_hook'] = "activate_$target/grand-media.php";
	( new Activation_Under_Test() )->_activate_plugin_event_hook();
	if ( array( 'unrelated/plugin.php' ) !== array_values( $GLOBALS['options']['active_plugins'] ) ) {
		throw new RuntimeException( "$target activation must deactivate only the other edition." );
	}
	if ( $before !== $GLOBALS['options']['gmedia_options'] || in_array( 'uninstall_' . $other . '/grand-media.php', $GLOBALS['actions'], true ) ) {
		throw new RuntimeException( 'Switching editions must not uninstall or modify gallery settings.' );
	}
}
$completed = true;
echo "Premium activation regression passed (duplicate bootstrap, both switching directions, settings preserved).\n";
