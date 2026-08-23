<?php

$root   = dirname( __DIR__, 2 );
$script = $root . '/tests/manual/license-state-matrix.php';

$failures = array();

if ( ! is_file( $script ) ) {
	$failures[] = 'Manual license state matrix harness is missing';
} else {
	$code = file_get_contents( $script );

	if ( false === $code ) {
		$failures[] = 'Could not read manual license state matrix harness';
	} else {
		foreach ( array( 'snapshot', 'restore', 'current', 'apply-scenario', 'verify-reset-preserves-legacy', 'verify-legacy-activation-endpoint' ) as $command ) {
			if ( false === strpos( $code, "'{$command}'" ) && false === strpos( $code, "\"{$command}\"" ) ) {
				$failures[] = "Harness must support {$command} command";
			}
		}

		foreach ( array( 'gmediaOptions', 'fs_accounts', 'fs_active_plugins', 'fs_options', 'fs_api_cache' ) as $option_name ) {
			if ( false === strpos( $code, "'{$option_name}'" ) && false === strpos( $code, "\"{$option_name}\"" ) ) {
				$failures[] = "Harness must track {$option_name}";
			}
		}

		foreach ( array( 'chmod', '0600', 'redact', 'delete_stale_freemius_options' ) as $required_text ) {
			if ( false === strpos( $code, $required_text ) ) {
				$failures[] = "Harness must include {$required_text}";
			}
		}

		foreach ( array( 'GmediaProcessor_Settings', 'gmedia_settings_reset', '_wpnonce_settings', 'load_options' ) as $reset_text ) {
			if ( false === strpos( $code, $reset_text ) ) {
				$failures[] = "Harness reset verification must include {$reset_text}";
			}
		}

		if ( false === strpos( $code, 'esc_like' ) ) {
			$failures[] = 'Harness must escape SQL LIKE option prefixes';
		}
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo 'License state harness guard passed.' . PHP_EOL;
