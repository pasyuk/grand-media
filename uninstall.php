<?php
/**
 * Uninstall Gmedia plugin
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/grand-media.php';
require_once dirname( __FILE__ ) . '/inc/functions.php';

if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	global $wpdb;
	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
	if ( $blogs ) {
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			gmedia_uninstall();
			restore_current_blog();
		}
	}
} else {
	gmedia_uninstall();
}

/**
 * Uninstall all settings and tables
 * Called via Setup hook
 *
 * @access internal
 * @return void
 */
function gmedia_uninstall() {
	/** @var $wpdb wpdb */
	global $wpdb, $gmCore, $gmDB;

	$options = get_option( 'gmediaOptions' );
	if ( (int) $options['mobile_app'] ) {
		$gmCore->app_service( 'app_uninstallplugin' );
	}

	$upload = $gmCore->gm_upload_dir( false );

	if ( ! $options ) {
		return;
	}

	// remove all tables if allowed.
	if ( ( 'all' === $options['uninstall_dropdata'] ) || 'db' === $options['uninstall_dropdata'] ) {
		$wpdb->query( "DELETE a, b FROM {$wpdb->posts} a LEFT JOIN {$wpdb->postmeta} b ON ( a.ID = b.post_id ) WHERE a.`post_type` IN ('gmedia', 'gmedia_album', 'gmedia_gallery')" );

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gmedia" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gmedia_meta" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gmedia_term" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gmedia_term_meta" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gmedia_term_relationships" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}gmedia_log" );

		delete_metadata( 'post', 0, '_gmedia_image_id', '', true );
	}

	$capabilities = $gmCore->plugin_capabilities();
	$capabilities = apply_filters( 'gmedia_capabilities', $capabilities );
	$check_order  = $gmDB->get_sorted_roles();
	foreach ( $check_order as $the_role ) {
		// If you rename the roles, then please use the role manager plugin.
		if ( empty( $the_role ) ) {
			continue;
		}
		foreach ( $capabilities as $cap ) {
			if ( $the_role->has_cap( $cap ) ) {
				$the_role->remove_cap( $cap );
			}
		}
	}

	// then remove all options.
	delete_transient( 'gmediaHeavyJob' );
	delete_transient( 'gmediaUpgrade' );
	delete_transient( 'gmediaUpgradeSteps' );
	delete_option( 'gmediaOptions' );
	delete_option( 'gmediaDbVersion' );
	delete_option( 'gmediaVersion' );
	delete_option( 'gmediaInstallDate' );
	delete_option( 'GmediaHashID_salt' );
	delete_metadata( 'user', 0, 'gm_screen_options', '', true );
	wp_clear_scheduled_hook( 'gmedia_app_cronjob' );
	wp_clear_scheduled_hook( 'gmedia_modules_update' );
	gmedia_delete_transients( 'gm_cache' );

	if ( empty( $upload['error'] ) ) {
		if ( 'all' === $options['uninstall_dropdata'] ) {
			$files_folder = $upload['path'];
			$gmCore->delete_folder( $files_folder );
		}
		/*
		else {
			$folders = $options['folder'];
			if(!empty($folders['module']) && is_dir($upload['path'] . '/' . $folders['module'])) {
				$files_folder = $upload['path'] . '/' . $folders['module'];
				$gmCore->delete_folder($files_folder);
			}
		}
		*/
	}
}
