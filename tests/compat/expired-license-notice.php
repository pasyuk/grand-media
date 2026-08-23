<?php

$root     = dirname( __DIR__, 2 );
$failures = array();

// Child run: no gmg_fs() defined, so the helper must not assume Freemius exists.
$no_fs_mode = '1' === getenv( 'GMEDIA_TEST_NO_FS' );

if ( ! $no_fs_mode ) {
	$GLOBALS['gmedia_test_fs_state'] = array(
		'features_enabled' => false,
		'active_valid'     => false,
	);

	/**
	 * Minimal stand-in for the Freemius instance, driven by the global above.
	 */
	class Gmedia_Test_Fs_Stub {
		public function has_features_enabled_license() {
			return $GLOBALS['gmedia_test_fs_state']['features_enabled'];
		}

		public function has_active_valid_license() {
			return $GLOBALS['gmedia_test_fs_state']['active_valid'];
		}
	}

	function gmg_fs() {
		return new Gmedia_Test_Fs_Stub();
	}
}

require_once $root . '/inc/functions.php';

if ( ! function_exists( 'gmedia_has_expired_premium_license' ) ) {
	fwrite( STDERR, 'gmedia_has_expired_premium_license() is missing' . PHP_EOL );
	exit( 1 );
}

if ( $no_fs_mode ) {
	if ( false !== gmedia_has_expired_premium_license() ) {
		fwrite( STDERR, 'Helper must return false when gmg_fs() is unavailable' . PHP_EOL );
		exit( 1 );
	}

	exit( 0 );
}

// features_enabled, active_valid, expected. Expired-but-enabled is the only true case.
$cases = array(
	array( true, false, true, 'expired license with features still enabled' ),
	array( true, true, false, 'healthy unexpired license' ),
	array( false, false, false, 'no premium license at all' ),
	array( false, true, false, 'features disabled despite a valid license' ),
);

foreach ( $cases as $case ) {
	list( $features_enabled, $active_valid, $expected, $label ) = $case;

	$GLOBALS['gmedia_test_fs_state'] = array(
		'features_enabled' => $features_enabled,
		'active_valid'     => $active_valid,
	);

	$actual = gmedia_has_expired_premium_license();

	if ( $expected !== $actual ) {
		$failures[] = sprintf(
			'Expected %s for %s, got %s',
			$expected ? 'true' : 'false',
			$label,
			$actual ? 'true' : 'false'
		);
	}
}

// The helper is only useful if the settings template actually renders the notice.
$template = $root . '/admin/pages/settings/tpl/license.php';

if ( ! is_file( $template ) ) {
	$failures[] = 'License settings template is missing';
} else {
	$markup = file_get_contents( $template );

	if ( false === strpos( $markup, 'gmedia_has_expired_premium_license' ) ) {
		$failures[] = 'License settings template must render the expired-license notice';
	}

	if ( false === strpos( $markup, 'get_account_url' ) ) {
		$failures[] = 'Expired-license notice must link to the Freemius account page';
	}
}

// Re-run without gmg_fs() to cover the missing-Freemius guard.
$child = sprintf(
	'GMEDIA_TEST_NO_FS=1 %s %s',
	escapeshellarg( PHP_BINARY ),
	escapeshellarg( __FILE__ )
);

exec( $child, $child_output, $child_status );

if ( 0 !== $child_status ) {
	$failures[] = 'Missing-Freemius guard failed: ' . implode( ' ', $child_output );
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo 'Expired license notice guard passed.' . PHP_EOL;
