<?php
/**
 * Manual license-state harness for wp-dev:
 *
 *   wp-dev eval-file tests/manual/license-state-matrix.php snapshot /private/tmp/gmedia-license-backup.json
 *   wp-dev eval-file tests/manual/license-state-matrix.php current
 *   wp-dev eval-file tests/manual/license-state-matrix.php apply-scenario no-license
 *   wp-dev eval-file tests/manual/license-state-matrix.php apply-scenario legacy-only
 *   wp-dev eval-file tests/manual/license-state-matrix.php apply-scenario both /private/tmp/gmedia-license-backup.json
 *   wp-dev eval-file tests/manual/license-state-matrix.php verify-reset-preserves-legacy
 *   wp-dev eval-file tests/manual/license-state-matrix.php restore /private/tmp/gmedia-license-backup.json
 *
 * Keep backup files outside the repo. They can contain serialized account/license state.
 */

defined( 'ABSPATH' ) || die( 'Run this with wp-dev eval-file after WordPress loads.' );

global $wpdb;

$args = isset( $args ) && is_array( $args ) ? $args : array();

function gmedia_license_harness_usage() {
	fwrite(
		STDERR,
		"Usage:\n" .
		"  snapshot <backup-file>\n" .
		"  restore <backup-file>\n" .
		"  current\n" .
		"  apply-scenario <no-license|legacy-only|both> [backup-file]\n" .
		"  verify-reset-preserves-legacy\n"
	);
	exit( 2 );
}

function gmedia_license_harness_option_patterns() {
	return array(
		'fs_',
		'_transient_fs_',
		'_transient_timeout_fs_',
		'_site_transient_fs_',
		'_site_transient_timeout_fs_',
	);
}

function gmedia_license_harness_fixed_options() {
	return array(
		'gmediaOptions',
		'fs_accounts',
		'fs_active_plugins',
		'fs_api_cache',
		'fs_debug_mode',
		'fs_gdpr',
		'fs_options',
	);
}

function gmedia_license_harness_collect_option_names() {
	global $wpdb;

	$names = gmedia_license_harness_fixed_options();

	foreach ( gmedia_license_harness_option_patterns() as $prefix ) {
		$pattern = $wpdb->esc_like( $prefix ) . '%';
		$sql  = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern );
		$rows = $wpdb->get_col( $sql );
		foreach ( $rows as $row ) {
			$names[] = $row;
		}
	}

	$names = array_values( array_unique( $names ) );
	sort( $names );

	return $names;
}

function gmedia_license_harness_is_safe_freemius_option( $name ) {
	foreach ( array( 'fs_', '_transient_fs_', '_transient_timeout_fs_', '_site_transient_fs_', '_site_transient_timeout_fs_' ) as $prefix ) {
		if ( 0 === strpos( $name, $prefix ) ) {
			return true;
		}
	}

	return false;
}

function gmedia_license_harness_get_option_row( $name ) {
	global $wpdb;

	return $wpdb->get_row(
		$wpdb->prepare( "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name ),
		ARRAY_A
	);
}

function gmedia_license_harness_set_option_row( $name, $value, $autoload ) {
	global $wpdb;

	$exists = null !== gmedia_license_harness_get_option_row( $name );
	if ( $exists ) {
		update_option( $name, $value );
	} else {
		add_option( $name, $value, '', 'yes' === $autoload );
	}

	$wpdb->update(
		$wpdb->options,
		array( 'autoload' => $autoload ),
		array( 'option_name' => $name ),
		array( '%s' ),
		array( '%s' )
	);
	wp_cache_delete( $name, 'options' );
}

function gmedia_license_harness_encode_value( $value ) {
	return base64_encode( serialize( $value ) );
}

function gmedia_license_harness_decode_value( $value ) {
	return unserialize( base64_decode( $value ), array( 'allowed_classes' => true ) );
}

function gmedia_license_harness_redact( $value ) {
	if ( is_string( $value ) ) {
		return '' === $value ? 'empty' : 'present';
	}

	return empty( $value ) ? 'empty' : 'present';
}

function gmedia_license_harness_legacy_test_values() {
	return array(
		'license_name' => 'Manual Test Legacy License',
		'purchase_key' => 'manual-test-purchase-key',
		'license_key'  => 'manual-test-license-key',
		'license_key2' => 'manual-test-license-key-2',
	);
}

function gmedia_license_harness_snapshot( $file ) {
	$payload = array(
		'created_at' => gmdate( 'c' ),
		'format'     => 'gmedia-license-state-v1',
		'options'    => array(),
	);

	foreach ( gmedia_license_harness_collect_option_names() as $name ) {
		$row = gmedia_license_harness_get_option_row( $name );
		if ( null === $row ) {
			$payload['options'][ $name ] = array(
				'exists' => false,
			);
			continue;
		}

		$payload['options'][ $name ] = array(
			'exists'   => true,
			'autoload' => $row['autoload'],
			'value'    => gmedia_license_harness_encode_value( maybe_unserialize( $row['option_value'] ) ),
		);
	}

	$json = wp_json_encode( $payload, JSON_PRETTY_PRINT );
	if ( false === file_put_contents( $file, $json ) ) {
		fwrite( STDERR, "Could not write backup file: {$file}\n" );
		exit( 1 );
	}

	chmod( $file, 0600 );

	echo wp_json_encode(
		array(
			'backup_file'    => $file,
			'tracked_count'  => count( $payload['options'] ),
			'sensitive_note' => 'Backup contains serialized option values; do not commit it.',
		),
		JSON_PRETTY_PRINT
	) . PHP_EOL;
}

function gmedia_license_harness_read_snapshot( $file ) {
	if ( ! is_file( $file ) ) {
		fwrite( STDERR, "Backup file not found: {$file}\n" );
		exit( 1 );
	}

	$payload = json_decode( file_get_contents( $file ), true );
	if ( ! is_array( $payload ) || 'gmedia-license-state-v1' !== ( $payload['format'] ?? '' ) || ! isset( $payload['options'] ) || ! is_array( $payload['options'] ) ) {
		fwrite( STDERR, "Invalid backup file: {$file}\n" );
		exit( 1 );
	}

	return $payload;
}

function gmedia_license_harness_delete_stale_freemius_options( $snapshot ) {
	$backup_names = array_keys( $snapshot['options'] );
	foreach ( gmedia_license_harness_collect_option_names() as $name ) {
		if ( in_array( $name, $backup_names, true ) ) {
			continue;
		}
		if ( gmedia_license_harness_is_safe_freemius_option( $name ) ) {
			delete_option( $name );
		}
	}
}

function gmedia_license_harness_restore( $file ) {
	$snapshot = gmedia_license_harness_read_snapshot( $file );

	gmedia_license_harness_delete_stale_freemius_options( $snapshot );

	foreach ( $snapshot['options'] as $name => $record ) {
		if ( empty( $record['exists'] ) ) {
			delete_option( $name );
			continue;
		}

		$autoload = isset( $record['autoload'] ) ? (string) $record['autoload'] : 'yes';
		$value    = gmedia_license_harness_decode_value( $record['value'] );
		gmedia_license_harness_set_option_row( $name, $value, $autoload );
	}

	echo wp_json_encode(
		array(
			'restored_from'  => $file,
			'restored_count' => count( $snapshot['options'] ),
		),
		JSON_PRETTY_PRINT
	) . PHP_EOL;
}

function gmedia_license_harness_update_legacy_fields( $enabled ) {
	$options = get_option( 'gmediaOptions', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options['license_name'] = $enabled ? 'Manual Test Legacy License' : '';
	$options['purchase_key'] = '';
	$options['license_key']  = '';
	$options['license_key2'] = '';

	update_option( 'gmediaOptions', $options );
}

function gmedia_license_harness_set_admin_context() {
	global $gmCore;

	$admins = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		)
	);
	if ( empty( $admins ) ) {
		fwrite( STDERR, "No administrator user found for reset verification.\n" );
		exit( 1 );
	}

	wp_set_current_user( (int) $admins[0] );
	if ( isset( $gmCore->caps ) && is_array( $gmCore->caps ) ) {
		$gmCore->caps['gmedia_settings'] = true;
	}
}

function gmedia_license_harness_verify_reset_preserves_legacy() {
	global $gmGallery;

	gmedia_license_harness_set_admin_context();

	$options = get_option( 'gmediaOptions', array() );
	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$raw_legacy_values                = gmedia_license_harness_legacy_test_values();
	$options                          = array_merge( $options, $raw_legacy_values );
	$options['issue_20_reset_marker'] = 'remove-me-on-reset';
	update_option( 'gmediaOptions', $options );
	if ( method_exists( $gmGallery, 'load_options' ) ) {
		$gmGallery->load_options();
	} else {
		$gmGallery->options = $options;
	}
	$expected = array_intersect_key( $gmGallery->options, $raw_legacy_values );

	if ( ! class_exists( 'GmediaProcessor' ) ) {
		include_once GMEDIA_ABSPATH . 'admin/class.processor.php';
	}
	if ( ! class_exists( 'GmediaProcessor_Settings' ) ) {
		$_GET['page'] = 'GrandMedia_Settings';
		include_once GMEDIA_ABSPATH . 'admin/processor/class.processor.settings.php';
	}

	$old_post    = $_POST;
	$old_request = $_REQUEST;
	$_POST       = array(
		'gmedia_settings_reset' => '1',
		'_wpnonce_settings'    => wp_create_nonce( 'gmedia_settings' ),
	);
	$_REQUEST    = array_merge( $_REQUEST, $_POST );

	$processor = GmediaProcessor_Settings::getMe();
	$method    = new ReflectionMethod( 'GmediaProcessor_Settings', 'processor' );
	$method->setAccessible( true );
	$method->invoke( $processor );

	$_POST    = $old_post;
	$_REQUEST = $old_request;

	$after     = get_option( 'gmediaOptions', array() );
	$preserved = array();
	foreach ( array_keys( $expected ) as $field ) {
		$preserved[ $field ] = gmedia_license_harness_bool_text( isset( $after[ $field ] ) && $after[ $field ] === $expected[ $field ] );
	}

	$all_preserved  = ! in_array( 'no', $preserved, true );
	$marker_removed = ! array_key_exists( 'issue_20_reset_marker', $after );

	echo wp_json_encode(
		array(
			'reset_executed'          => gmedia_license_harness_bool_text( $marker_removed ),
			'legacy_fields_redacted' => array_map( 'gmedia_license_harness_redact', array_intersect_key( $after, $expected ) ),
			'legacy_fields_preserved' => $preserved,
			'passed'                  => gmedia_license_harness_bool_text( $all_preserved && $marker_removed ),
		),
		JSON_PRETTY_PRINT
	) . PHP_EOL;

	if ( ! $all_preserved || ! $marker_removed ) {
		exit( 1 );
	}
}

function gmedia_license_harness_delete_current_freemius_options() {
	foreach ( gmedia_license_harness_collect_option_names() as $name ) {
		if ( gmedia_license_harness_is_safe_freemius_option( $name ) ) {
			delete_option( $name );
		}
	}
}

function gmedia_license_harness_apply_scenario( $scenario, $file = '' ) {
	switch ( $scenario ) {
		case 'no-license':
			gmedia_license_harness_delete_current_freemius_options();
			gmedia_license_harness_update_legacy_fields( false );
			break;

		case 'legacy-only':
			gmedia_license_harness_delete_current_freemius_options();
			gmedia_license_harness_update_legacy_fields( true );
			break;

		case 'both':
			if ( $file ) {
				gmedia_license_harness_restore( $file );
			}
			gmedia_license_harness_update_legacy_fields( true );
			break;

		default:
			fwrite( STDERR, "Unknown scenario: {$scenario}\n" );
			exit( 2 );
	}

	echo wp_json_encode(
		array(
			'applied_scenario' => $scenario,
			'next_step'         => 'Run current in a fresh wp-dev eval-file process.',
		),
		JSON_PRETTY_PRINT
	) . PHP_EOL;
}

function gmedia_license_harness_bool_text( $value ) {
	if ( null === $value ) {
		return 'missing';
	}

	return $value ? 'yes' : 'no';
}

function gmedia_license_harness_current() {
	$options = get_option( 'gmediaOptions', array() );
	$fs      = function_exists( 'gmg_fs' ) ? gmg_fs() : null;

	$data = array(
		'gmedia'  => array(
			'license_name'        => gmedia_license_harness_redact( $options['license_name'] ?? '' ),
			'purchase_key'        => gmedia_license_harness_redact( $options['purchase_key'] ?? '' ),
			'license_key'         => gmedia_license_harness_redact( $options['license_key'] ?? '' ),
			'license_key2'        => gmedia_license_harness_redact( $options['license_key2'] ?? '' ),
			'helper_license_type' => function_exists( 'gmedia_get_license_type' ) ? gmedia_get_license_type() : 'missing',
			'helper_has_premium'  => function_exists( 'gmedia_has_premium_license' ) ? gmedia_license_harness_bool_text( gmedia_has_premium_license() ) : 'missing',
		),
		'freemius' => array(
			'exists' => gmedia_license_harness_bool_text( (bool) $fs ),
		),
	);

	if ( $fs ) {
		foreach ( array( 'is_registered', 'is_premium', 'has_paid_plan', 'has_premium_version', 'is_trial', 'is_paying', 'has_active_valid_license', 'has_features_enabled_license', 'can_use_premium_code' ) as $method ) {
			$data['freemius'][ $method ] = method_exists( $fs, $method ) ? gmedia_license_harness_bool_text( (bool) $fs->$method() ) : 'missing';
		}
	}

	echo wp_json_encode( $data, JSON_PRETTY_PRINT ) . PHP_EOL;
}

$command = array_shift( $args );
if ( ! $command ) {
	gmedia_license_harness_usage();
}

switch ( $command ) {
	case 'snapshot':
		$file = array_shift( $args );
		if ( ! $file ) {
			gmedia_license_harness_usage();
		}
		gmedia_license_harness_snapshot( $file );
		break;

	case 'restore':
		$file = array_shift( $args );
		if ( ! $file ) {
			gmedia_license_harness_usage();
		}
		gmedia_license_harness_restore( $file );
		break;

	case 'current':
		gmedia_license_harness_current();
		break;

	case 'apply-scenario':
		$scenario = array_shift( $args );
		if ( ! $scenario ) {
			gmedia_license_harness_usage();
		}
		gmedia_license_harness_apply_scenario( $scenario, array_shift( $args ) ?: '' );
		break;

	case 'verify-reset-preserves-legacy':
		gmedia_license_harness_verify_reset_preserves_legacy();
		break;

	default:
		gmedia_license_harness_usage();
}
