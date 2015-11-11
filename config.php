<?php
/**
 * Bootstrap file for getting the ABSPATH constant to wp-load.php
 * This is requried when a plugin requires access not via the admin screen.
 *
 * If the wp-load.php file is not found, then an error will be displayed
 */

/** Define the server path to the file wp-config here, if you placed WP-CONTENT outside the classic file structure */

if ( ! isset( $path ) ) {
	$path = ''; // It should be end with a trailing slash
};

/** That's all, stop editing from here **/

if ( ! defined( 'WP_LOAD_PATH' ) ) {

	/** classic root path if wp-content and plugins is below wp-config.php */
	preg_match( '|^(.*?/)(wp-content)/|i', str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), $_m );
	$classic_root = $_m[1];

	if ( file_exists( $path . 'wp-load.php' ) ) {
		define( 'WP_LOAD_PATH', $path );
	} elseif ( file_exists( $classic_root . 'wp-load.php' ) ) {
		define( 'WP_LOAD_PATH', $classic_root );
	} else {
		$classic_root = dirname( dirname( dirname( dirname( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ) ) ) ) ) . '/';
		if ( file_exists( $classic_root . 'wp-load.php' ) ) {
			define( 'WP_LOAD_PATH', $classic_root );
		} else {
			exit( "Could not find wp-load.php" );
		}
	}
}

// let's load WordPress
require_once( WP_LOAD_PATH . 'wp-load.php' );
