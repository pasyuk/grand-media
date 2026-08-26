<?php

$root = dirname( __DIR__, 2 );
$completed = false;
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'UTF-8 conversion test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

if ( ! in_array( '--child', $argv, true ) ) {
	$failed = false;
	foreach ( array( array( '', '' ), array( 'mb_convert_encoding,utf8_encode,utf8_decode', '' ), array( 'mb_convert_encoding,utf8_encode,utf8_decode', ' --legacy-charset' ) ) as $mode ) {
		$command = escapeshellarg( PHP_BINARY ) . ' -d disable_functions=' . escapeshellarg( $mode[0] ) . ' ' . escapeshellarg( __FILE__ ) . ' --child' . $mode[1];
		passthru( $command, $status );
		if ( 0 !== $status ) {
			$failed = true;
		}
	}
	if ( $failed ) {
		exit( 1 );
	}
	$completed = true;
	exit;
}

define( 'ABSPATH', dirname( $root, 3 ) . '/' );
define( 'WPINC', 'wp-includes' );
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
function is_utf8_charset() { return ! in_array( '--legacy-charset', $GLOBALS['argv'], true ); }
function _wp_can_use_pcre_u() { return true; }

require_once ABSPATH . WPINC . '/utf8.php';
require_once ABSPATH . WPINC . '/formatting.php';
require_once $root . '/inc/core.php';

$failures = array();
foreach ( array( '', 'ASCII & <markup> "quoted"', 'Київ — Café 日本 😀', '&amp; stays encoded' ) as $text ) {
	if ( $text !== $gmCore->mb_convert_encoding_utf8( $text ) ) {
		$failures[] = 'Valid UTF-8 text changed during conversion.';
	}
}
foreach ( array( "Caf\xe9", "bad\xff" ) as $text ) {
	if ( 1 !== preg_match( '//u', $gmCore->mb_convert_encoding_utf8( $text ) ) ) {
		$failures[] = 'Invalid UTF-8 survived conversion on a non-UTF-8 site.';
	}
}

if ( ! method_exists( $gmCore, 'iso_8859_1_to_utf8' ) ) {
	$failures[] = 'Latin-1 metadata conversion helper is missing.';
} else {
	$latin1 = '';
	for ( $byte = 0; $byte < 256; ++$byte ) {
		$latin1 .= chr( $byte );
	}
	$expected = implode( '', array_map( static function ( $byte ) {
		return $byte < 128 ? chr( $byte ) : ( $byte < 192 ? "\xC2" : "\xC3" ) . chr( 128 + ( $byte % 64 ) );
	}, range( 0, 255 ) ) );
	if ( $expected !== $gmCore->iso_8859_1_to_utf8( $latin1 ) ) {
		$failures[] = 'Latin-1 conversion did not preserve all 256 byte values.';
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
$completed = true;
echo 'UTF-8 conversion passed (' . ( function_exists( 'mb_convert_encoding' ) ? 'mbstring' : 'fallback' ) . ').' . PHP_EOL;
