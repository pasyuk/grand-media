<?php

/** Exercise module entry points with optional and WordPress-slashed server values. */
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) {
		fwrite( STDERR, 'Frontend request values test did not complete.' . PHP_EOL );
		exit( 1 );
	}
} );
$root = dirname( __DIR__, 2 );
define( 'ABSPATH', dirname( $root, 3 ) . '/' );
define( 'WPINC', 'wp-includes' );
require ABSPATH . WPINC . '/compat.php';
require ABSPATH . WPINC . '/functions.php';
require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/http.php';

function apply_filters( $hook, $value ) {
	return $value;
}

function __( $text ) {
	return $text;
}

function home_url( $path ) {
	return 'https://example.test/' . $path;
}

class Gmedia_Frontend_Query_Ready extends RuntimeException {}

$gmDB = new class {
	public function get_gmedias( $query ) {
		throw new Gmedia_Frontend_Query_Ready();
	}
};
$gmCore = new class {
	public $upload = array( 'url' => 'https://example.test/uploads' );
	public function _get( $key, $default ) {
		return $default;
	}
};
$wp = (object) array( 'request' => 'gallery' );
$_SERVER['REQUEST_URI'] = '/gallery';
$failures = array();
set_error_handler( static function ( $severity, $message ) use ( &$failures ) {
	$failures[] = $message;
	return true;
} );

$query_cases = array( null, '', 'q=%E2%9C%93&path=%2Fone%2Ftwo', "q=O'Reilly&value=%22quoted%22" );
foreach ( array( 'cubik-lite', 'phantom', 'photomania' ) as $module_base ) {
	foreach ( $query_cases as $raw_query ) {
		unset( $_SERVER['QUERY_STRING'] );
		if ( null !== $raw_query ) {
			$_SERVER['QUERY_STRING'] = wp_slash( $raw_query );
		}
		$id       = 17;
		$settings = array();
		$module   = array( 'url' => 'https://example.test/module', 'options' => array() );
		$query    = array( 'per_page' => 10 );
		try {
			include $root . "/module/{$module_base}/init.php";
			$failures[] = "{$module_base}: did not reach the media query";
		} catch ( Gmedia_Frontend_Query_Ready $ready ) {
			// Keep the legacy URL-building contract, including percent-encoded values.
			$expected = add_query_arg( null === $raw_query ? '' : $raw_query, '', home_url( $wp->request ) );
			if ( 'photomania' === $module_base ) {
				$expected = remove_query_arg( 'gm17_slide', $expected );
			}
			if ( $expected !== $settings['url'] ) {
				$failures[] = "{$module_base}: URL request data did not round-trip";
			}
		}
	}

	unset( $_SERVER['PHP_SELF'] );
	ob_start();
	include $root . "/module/{$module_base}/index.php";
	if ( '' !== ob_get_clean() || $module_base !== $module_info['base'] ) {
		$failures[] = "{$module_base}: metadata include changed without a script path";
	}
}
restore_error_handler();

$completed = true;
if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
echo 'Frontend optional server inputs and encoded query values passed.' . PHP_EOL;
