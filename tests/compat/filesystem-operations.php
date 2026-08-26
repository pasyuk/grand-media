<?php

$root = dirname( __DIR__, 2 );

$completed = false;
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Filesystem operations test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

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

require_once ABSPATH . 'wp-includes/class-wp-error.php';
require_once $root . '/inc/core.php';

$core = ( new ReflectionClass( 'GmediaCore' ) )->newInstanceWithoutConstructor();
$wp_filesystem = new stdClass();
$configured_filesystem = $wp_filesystem;
if ( ! method_exists( $core, 'local_filesystem' ) ) {
	fwrite( STDERR, 'The local filesystem accessor is missing.' . PHP_EOL );
	exit( 1 );
}
$filesystem = $core->local_filesystem();
if ( ! $filesystem instanceof WP_Filesystem_Direct || $configured_filesystem !== $wp_filesystem || $filesystem !== $core->local_filesystem() ) {
	fwrite( STDERR, 'Local operations must reuse a direct accessor without changing the configured filesystem.' . PHP_EOL );
	exit( 1 );
}
$file = tempnam( sys_get_temp_dir(), 'gmedia-delete-' );
if ( false === $file ) {
	fwrite( STDERR, 'Could not create filesystem test fixture.' . PHP_EOL );
	exit( 1 );
}

$result = $core->delete_folder( $file );
if ( true !== $result || file_exists( $file ) ) {
	fwrite( STDERR, 'delete_folder() did not preserve successful file deletion behavior.' . PHP_EOL );
	exit( 1 );
}
if ( null !== $core->delete_folder( $file ) ) {
	fwrite( STDERR, 'Missing paths must retain the null result.' . PHP_EOL );
	exit( 1 );
}

$directory = $file . '-directory';
mkdir( $directory, 0700 );
file_put_contents( $directory . '/.keep', 'hidden fixture' );
file_put_contents( $directory . '/visible', 'visible fixture' );
foreach ( array( 0700, 0555 ) as $initial_mode ) {
	chmod( $directory, $initial_mode );
	clearstatcache( true, $directory );
	$core->file_chmod( $directory, 0755 );
	clearstatcache( true, $directory );
	if ( 0755 !== ( fileperms( $directory ) & 0777 ) ) {
		chmod( $directory, 0755 );
		fwrite( STDERR, 'Local directory permission repair did not set 0755.' . PHP_EOL );
		exit( 1 );
	}
}
if ( false !== $core->delete_folder( $directory ) || ! is_file( $directory . '/.keep' ) || is_file( $directory . '/visible' ) ) {
	fwrite( STDERR, 'Directory cleanup must not expand its scope to hidden files.' . PHP_EOL );
	exit( 1 );
}
unlink( $directory . '/.keep' );
if ( true !== $core->delete_folder( $directory ) || is_dir( $directory ) ) {
	fwrite( STDERR, 'Empty directory deletion failed.' . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Filesystem operations compatibility passed.' . PHP_EOL;
