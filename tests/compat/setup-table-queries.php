<?php

$root = dirname( __DIR__, 2 );
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) {
		fwrite( STDERR, 'Setup table query test did not complete.' . PHP_EOL );
		exit( 1 );
	}
} );

define( 'ABSPATH', __DIR__ . '/../fixtures/setup/' );
function current_user_can( $cap ) { return 'activate_plugins' === $cap; }
function get_role( $role ) { return new GmediaSetupTestRole(); }
function apply_filters( $hook, $value ) { return $value; }
function has_filter() { return false; }
function add_filter() {}
function esc_html__( $text, $domain ) { return $text; }
function update_option( $key, $value ) {
	if ( 'gmediaInitCheck' !== $key ) {
		throw new RuntimeException( 'Setup continued beyond the table verification boundary.' );
	}
}
class GmediaSetupTestRole {
	public function add_cap( $cap ) {}
}
class GmediaSetupTestCore {
	public function plugin_capabilities() { return array(); }
}

// Use the installed WordPress SQL preparation/LIKE escaping, without a connection.
require_once dirname( $root, 3 ) . '/wp-includes/class-wpdb.php';
class GmediaSetupTestDB extends wpdb {
	public $captured = array();
	public $suffixes = array( 'gmedia', 'gmedia_meta', 'gmedia_term', 'gmedia_term_meta', 'gmedia_term_relationships', 'gmedia_log', 'gmedia' );
	public function __construct( $prefix ) { $this->prefix = $prefix; }
	public function has_cap( $cap ) { return false; }
	public function _real_escape( $value ) { return $this->add_placeholder_escape( addslashes( $value ) ); }
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		$index = count( $this->captured );
		$this->captured[] = $this->remove_placeholder_escape( $query );
		// All six tables exist. Stop installation at its final verification check.
		return 6 === $index ? null : $this->prefix . $this->suffixes[ $index ];
	}
}
require_once $root . '/config/setup.php';
$gmCore = new GmediaSetupTestCore();

foreach ( array( 'wp_', 'site_23_', 'site%_' ) as $prefix ) {
	$wpdb = new GmediaSetupTestDB( $prefix );
	gmedia_install();
	if ( 7 !== count( $wpdb->captured ) ) {
		throw new RuntimeException( 'Expected six table probes and the final installation probe.' );
	}
	foreach ( $wpdb->captured as $index => $query ) {
		$table = $prefix . $wpdb->suffixes[ $index ];
		$expected = "SHOW TABLES LIKE '" . addslashes( addcslashes( $table, '_%\\' ) ) . "'";
		if ( $expected !== $query ) {
			throw new RuntimeException( 'Table names must be prepared as literal LIKE patterns: ' . $query );
		}
	}
}

$completed = true;
echo 'Setup table queries passed: 21 literal LIKE probes, no database writes.' . PHP_EOL;
