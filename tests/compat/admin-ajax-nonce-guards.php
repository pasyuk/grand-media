<?php

$root = dirname( __DIR__, 2 );

$ajax_code   = file_get_contents( $root . '/admin/ajax.php' );
$update_code = file_get_contents( $root . '/config/update.php' );
$admin_code  = file_get_contents( $root . '/admin/admin.php' );

$failures = array();

if ( false === $ajax_code ) {
	$failures[] = 'Could not read admin/ajax.php';
}

if ( false === $update_code ) {
	$failures[] = 'Could not read config/update.php';
}

if ( false === $admin_code ) {
	$failures[] = 'Could not read admin/admin.php';
}

if ( ! $failures ) {
	if ( ! preg_match( '/function\s+gmedia_upgrade_process\s*\(\s*\)\s*\{(?P<body>.*?)^\}/ms', $ajax_code, $matches ) ) {
		$failures[] = 'Could not find gmedia_upgrade_process() body';
	} else {
		$body = $matches['body'];

		if ( false === strpos( $body, "check_ajax_referer( 'gmedia_ajax_long_operations', '_wpnonce_ajax_long_operations' )" ) ) {
			$failures[] = 'gmedia_upgrade_process() must validate the long-operation nonce';
		}

		if ( false === strpos( $body, "current_user_can( 'manage_options' )" ) ) {
			$failures[] = 'gmedia_upgrade_process() must require manage_options capability';
		}
	}

	if ( false === strpos( $update_code, "_wpnonce_ajax_long_operations: '" ) ) {
		$failures[] = 'gmedia upgrade progress AJAX request must send the long-operation nonce';
	}

	if ( false === strpos( $update_code, "current_user_can( 'manage_options' )" ) ) {
		$failures[] = 'gmedia upgrade button must be gated to manage_options';
	}

	if ( false === strpos( $admin_code, 'current_user_can( \'manage_options\' ) && ( get_transient( \'gmediaUpgrade\' ) || ( \'gmedia\' === $gmCore->_get( \'do_update\' ) ) )' ) ) {
		$failures[] = 'gmedia update page route must be gated to manage_options';
	}
}

if ( $failures ) {
	echo implode( PHP_EOL, $failures ) . PHP_EOL;
	exit( 1 );
}

echo 'Admin AJAX nonce guard passed.' . PHP_EOL;
