<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Setup Gmedia plugin
 */

/**
 * Setup the default option array for the plugin
 *
 * @access internal
 * @return array
 */
function gmedia_default_options() {

	$gm['site_ID']    = '';
	$gm['mobile_app'] = 0;

	$gm['modules_update']   = 0;
	$gm['modules_new']      = 0;
	$gm['cache_expiration'] = 24;
	$gm['disable_ads']      = '0';

	$gm['gmedia_post_slug']              = 'gmedia';
	$gm['gmedia_exclude_from_search']    = '0';
	$gm['gmedia_has_archive']            = '1';
	$gm['default_gmedia_comment_status'] = 'open'; // can be 'closed', 'open'.

	$gm['gmedia_album_post_slug']           = 'gmedia-album';
	$gm['gmedia_album_has_archive']         = '1';
	$gm['gmedia_album_exclude_from_search'] = '0';

	$gm['gmedia_gallery_post_slug']           = 'gmedia-gallery';
	$gm['gmedia_gallery_has_archive']         = '0';
	$gm['gmedia_gallery_exclude_from_search'] = '0';

	$gm['wp_term_related_gmedia'] = '0';
	$gm['wp_post_related_gmedia'] = '0';

	$gm['wp_author_related_gmedia']         = '0';
	$gm['wp_author_related_gmedia_album']   = '1';
	$gm['wp_author_related_gmedia_gallery'] = '0';

	$gm['preview_bgcolor'] = 'ffffff';

	//$gm['default_gmedia_term_comment_status'] = 'closed'; // can be 'closed', 'open'

	$gm['delete_originals']   = '0';
	$gm['disable_logs']       = '0';
	$gm['uninstall_dropdata'] = 'none'; // can be 'all', 'none', 'db'.

	$gm['name2title_capitalize'] = '1';
	$gm['in_tag_orderby']        = 'ID';
	$gm['in_tag_order']          = 'DESC';
	$gm['in_category_orderby']   = 'ID';
	$gm['in_category_order']     = 'DESC';
	$gm['in_album_orderby']      = 'ID';
	$gm['in_album_order']        = 'DESC';
	$gm['in_album_status']       = 'publish';
	$gm['default_gmedia_module'] = 'amron';
	$gm['notify_new_modules']    = '1';

	$gm['isolation_mode'] = '0';
	$gm['shortcode_raw']  = '0';
	$gm['debug_mode']     = '';

	$gm['endpoint']                  = 'gmedia';
	$gm['gmediacloud_socialbuttons'] = '1';
	$gm['gmediacloud_footer_js']     = '';
	$gm['gmediacloud_footer_css']    = '';

	$gm['gmedia_post_types_support'] = '';

	$gm['feedback'] = '1';
	$gm['twitter']  = '1';

	$gm['folder']['image']          = 'image';
	$gm['folder']['image_thumb']    = 'image/thumb';
	$gm['folder']['image_original'] = 'image/original';
	$gm['folder']['audio']          = 'audio';
	$gm['folder']['video']          = 'video';
	$gm['folder']['text']           = 'text';
	$gm['folder']['application']    = 'application';
	$gm['folder']['module']         = 'module';

	$gm['thumb'] = array( 'width' => 300, 'height' => 300, 'quality' => 80, 'crop' => 0 );
	$gm['image'] = array( 'width' => 2200, 'height' => 2200, 'quality' => 85, 'crop' => 0 );

	//$gm['modules_xml']  = 'https://codeasily.com/gmedia_modules/modules_v1.xml';
	//$gm['modules_xml']  = 'https://www.dropbox.com/s/t7oawbuxy1me5gk/modules_v1.xml?dl=1';
	$gm['modules_xml']  = 'https://www.dropbox.com/s/ysmedfuxyy5ff3w/modules_v2.xml?dl=1';
	$gm['license_name'] = '';
	$gm['purchase_key'] = '';
	$gm['license_key']  = '';
	$gm['license_key2'] = '';

	$gm['google_api_key'] = '';

	$gm['taxonomies']['gmedia_category'] = array();
	$gm['taxonomies']['gmedia_tag']      = array();
	$gm['taxonomies']['gmedia_album']    = array();

	$gm['taxonomies']['gmedia_gallery'] = array(); // not linked with gmedia_term_relationships table.
	$gm['taxonomies']['gmedia_module']  = array(); // not linked with gmedia_term_relationships table.

	$gm['gm_screen_options']['per_page_gmedia']            = 30;
	$gm['gm_screen_options']['orderby_gmedia']             = 'ID';
	$gm['gm_screen_options']['sortorder_gmedia']           = 'DESC';
	$gm['gm_screen_options']['display_mode_gmedia']        = 'grid';
	$gm['gm_screen_options']['grid_cell_fit_gmedia']       = false;
	$gm['gm_screen_options']['display_mode_gmedia_frame']  = 'grid';
	$gm['gm_screen_options']['grid_cell_fit_gmedia_frame'] = false;

	$gm['gm_screen_options']['per_page_gmedia_album_edit']    = 60;
	$gm['gm_screen_options']['per_page_gmedia_category_edit'] = 60;

	$gm['gm_screen_options']['per_page_gmedia_album']  = 30;
	$gm['gm_screen_options']['orderby_gmedia_album']   = 'name';
	$gm['gm_screen_options']['sortorder_gmedia_album'] = 'ASC';

	$gm['gm_screen_options']['per_page_gmedia_category']  = 30;
	$gm['gm_screen_options']['orderby_gmedia_category']   = 'name';
	$gm['gm_screen_options']['sortorder_gmedia_category'] = 'ASC';

	$gm['gm_screen_options']['per_page_gmedia_tag']  = 30;
	$gm['gm_screen_options']['orderby_gmedia_tag']   = 'name';
	$gm['gm_screen_options']['sortorder_gmedia_tag'] = 'ASC';

	$gm['gm_screen_options']['per_page_gmedia_gallery']  = 30;
	$gm['gm_screen_options']['orderby_gmedia_gallery']   = 'name';
	$gm['gm_screen_options']['sortorder_gmedia_gallery'] = 'ASC';

	$gm['gm_screen_options']['per_page_wpmedia']  = 30;
	$gm['gm_screen_options']['orderby_wpmedia']   = 'ID';
	$gm['gm_screen_options']['sortorder_wpmedia'] = 'DESC';

	$gm['gm_screen_options']['uploader_runtime']          = 'auto';
	$gm['gm_screen_options']['uploader_chunking']         = 'true';
	$gm['gm_screen_options']['uploader_chunk_size']       = 8; // in Mb.
	$gm['gm_screen_options']['uploader_urlstream_upload'] = 'false';

	$gm['gm_screen_options']['library_edit_quicktags'] = 'true';

	$gm['gm_screen_options']['per_page_gmedia_log']  = '100';
	$gm['gm_screen_options']['orderby_gmedia_log']   = 'log_date';
	$gm['gm_screen_options']['sortorder_gmedia_log'] = 'DESC';

	return $gm;

}

/**
 * sets gmedia capabilities to administrator role
 **/
function gmedia_capabilities() {
	global $gmCore;
	// Set the capabilities for the administrator.
	$role = get_role( 'administrator' );
	// We need this role, no other chance.
	if ( empty( $role ) ) {
		update_option( 'gmediaInitCheck', esc_html__( 'Sorry, Gmedia Gallery works only with a role called administrator', 'grand-media' ) );

		return;
	}
	$capabilities = $gmCore->plugin_capabilities();
	$capabilities = apply_filters( 'gmedia_capabilities', $capabilities );
	foreach ( $capabilities as $cap ) {
		$role->add_cap( $cap );
	}
}

/**
 * creates all tables for the plugin
 * called during register_activation hook
 *
 * @access internal
 * @return void
 **/
function gmedia_install() {
	/** @var $wpdb wpdb */
	global $wpdb, $gmGallery, $gmCore;

	// Check for capability.
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	gmedia_capabilities();

	gmedia_db_tables();

	// check one table again, to be sure.
	$gmedia = $wpdb->prefix . 'gmedia';
	if ( $wpdb->get_var( "show tables like '$gmedia'" ) !== $gmedia ) {
		update_option( 'gmediaInitCheck', esc_html__( 'GmediaGallery: Tables could not created, please check your database settings', 'grand-media' ) );

		return;
	}

	if ( ! get_option( 'GmediaHashID_salt' ) ) {
		$ustr = wp_generate_password( 12, false );
		add_option( 'GmediaHashID_salt', $ustr );
	}

	// set the default settings, if we didn't upgrade.
	if ( empty( $gmGallery->options ) ) {
		$gmGallery->options = gmedia_default_options();
		// Set installation date.
		if ( ! get_option( 'gmediaInstallDate' ) ) {
			$installDate = time();
			add_option( 'gmediaInstallDate', $installDate );
		}
	} else {
		$default_options = gmedia_default_options();
		unset( $gmGallery->options['folder'], $gmGallery->options['taxonomies'] );
		$new_options                             = $gmCore->array_diff_key_recursive( $default_options, $gmGallery->options );
		$gmGallery->options                      = $gmCore->array_replace_recursive( $gmGallery->options, $new_options );
		$gmGallery->options['gm_screen_options'] = $default_options['gm_screen_options'];
	}
	update_option( 'gmediaOptions', $gmGallery->options );

	// try to make gallery dirs if not exists.
	foreach ( $gmGallery->options['folder'] as $folder ) {
		wp_mkdir_p( $gmCore->upload['path'] . '/' . $folder );
	}

	add_option( 'gmediaActivated', time() );
}

/**
 * Create DB Tables
 */
function gmedia_db_tables() {
	/** @var $wpdb wpdb */
	global $wpdb;

	// upgrade function changed in WordPress 2.3.
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// add charset & collate like wp core.
	$charset_collate = '';

	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}
	$charset_collate .= ' ROW_FORMAT=DYNAMIC';

	$gmedia                    = $wpdb->prefix . 'gmedia';
	$gmedia_meta               = $wpdb->prefix . 'gmedia_meta';
	$gmedia_term               = $wpdb->prefix . 'gmedia_term';
	$gmedia_term_meta          = $wpdb->prefix . 'gmedia_term_meta';
	$gmedia_term_relationships = $wpdb->prefix . 'gmedia_term_relationships';
	$gmedia_log                = $wpdb->prefix . 'gmedia_log';

	if ( $wpdb->get_var( "show tables like '$gmedia'" ) !== $gmedia ) {
		$sql = 'SET GLOBAL innodb_file_format = Barracuda, innodb_large_prefix = ON;';
		$sql .= "CREATE TABLE {$gmedia} (
			ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			author BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			description LONGTEXT NOT NULL,
			title TEXT NOT NULL,
			gmuid VARCHAR(255) NOT NULL DEFAULT '',
			link VARCHAR(255) NOT NULL DEFAULT '',
			modified DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			mime_type VARCHAR(100) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'publish',
			post_id BIGINT(20) UNSIGNED DEFAULT NULL,
			PRIMARY KEY  (ID),
			KEY gmuid (gmuid),
			KEY type_status_date (mime_type,status,date,ID),
			KEY author (author),
			KEY post_id (post_id)
		) {$charset_collate}";
		dbDelta( $sql );
	}

	if ( $wpdb->get_var( "show tables like '$gmedia_meta'" ) !== $gmedia_meta ) {
		$sql = 'SET GLOBAL innodb_file_format = Barracuda, innodb_large_prefix = ON;';
		$sql .= "CREATE TABLE {$gmedia_meta} (
			meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			gmedia_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			meta_key VARCHAR(255) DEFAULT NULL,
			meta_value LONGTEXT,
			PRIMARY KEY  (meta_id),
			KEY gmedia_id (gmedia_id),
			KEY meta_key (meta_key),
			INDEX `_hash` (meta_value(32))
		) {$charset_collate}";
		dbDelta( $sql );
	}

	if ( $wpdb->get_var( "show tables like '$gmedia_term'" ) !== $gmedia_term ) {
		$sql = 'SET GLOBAL innodb_file_format = Barracuda, innodb_large_prefix = ON;';
		$sql .= "CREATE TABLE {$gmedia_term} (
			term_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(200) NOT NULL DEFAULT '',
			taxonomy VARCHAR(32) NOT NULL DEFAULT '',
			description LONGTEXT NOT NULL,
			global BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			count BIGINT(20) NOT NULL DEFAULT '0',
			status VARCHAR(20) NOT NULL DEFAULT 'publish',
			PRIMARY KEY  (term_id),
			KEY taxonomy (taxonomy),
			KEY name (name)
		) {$charset_collate}";
		dbDelta( $sql );
	}

	if ( $wpdb->get_var( "show tables like '$gmedia_term_meta'" ) !== $gmedia_term_meta ) {
		$sql = 'SET GLOBAL innodb_file_format = Barracuda, innodb_large_prefix = ON;';
		$sql .= "CREATE TABLE {$gmedia_term_meta} (
			meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			gmedia_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			meta_key VARCHAR(255) DEFAULT NULL,
			meta_value LONGTEXT,
			PRIMARY KEY  (meta_id),
			KEY gmedia_term_id (gmedia_term_id),
			KEY meta_key (meta_key)
		) {$charset_collate}";
		dbDelta( $sql );
	}

	if ( $wpdb->get_var( "show tables like '$gmedia_term_relationships'" ) !== $gmedia_term_relationships ) {
		$sql = 'SET GLOBAL innodb_file_format = Barracuda, innodb_large_prefix = ON;';
		$sql .= "CREATE TABLE {$gmedia_term_relationships} (
			gmedia_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			gmedia_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			term_order INT(11) NOT NULL DEFAULT '0',
			gmedia_order INT(11) NOT NULL DEFAULT '0',
			PRIMARY KEY  (gmedia_id,gmedia_term_id),
			KEY gmedia_term_id (gmedia_term_id)
		) {$charset_collate}";
		dbDelta( $sql );
	}

	if ( $wpdb->get_var( "show tables like '$gmedia_log'" ) !== $gmedia_log ) {
		$sql = 'SET GLOBAL innodb_file_format = Barracuda, innodb_large_prefix = ON;';
		$sql .= "CREATE TABLE {$gmedia_log} (
			log VARCHAR(200) NOT NULL DEFAULT '',
			ID BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			log_author BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			log_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			log_data LONGTEXT,
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			KEY log (log),
			KEY ID (ID),
			KEY log_author (log_author),
			KEY log_date (log_date),
			KEY ip_address (ip_address)
		) {$charset_collate}";
		dbDelta( $sql );
	}

}

/**
 * Called via Setup and register_deactivate hook
 *
 * @access internal
 * @return void
 */
function gmedia_deactivate() {
	global $gmCore;

	flush_rewrite_rules( false );

	wp_clear_scheduled_hook( 'gmedia_app_cronjob' );
	wp_clear_scheduled_hook( 'gmedia_modules_update' );

	$options = get_option( 'gmediaOptions' );
	if ( (int) $options['mobile_app'] || (int) $options['site_ID'] ) {
		$gmCore->app_service( 'app_deactivateplugin' );
	}

	// remove & reset the init check option.
	delete_option( 'gmediaInitCheck' );
}
