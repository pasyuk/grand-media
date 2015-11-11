<?php
// Stop direct call
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
	die('You are not allowed to call this page directly.');
}

/**
 * Setup the default option array for the plugin
 *
 * @access internal
 * @return array
 */
function gmedia_default_options(){

	$gm['site_email'] = '';
	$gm['site_category'] = '';
	$gm['site_ID'] = '';
	$gm['mobile_app'] = 0;

	$gm['uninstall_dropdata'] = 'all'; // can be 'all', 'none', 'db'

	$gm['in_tag_orderby'] = 'ID';
	$gm['in_tag_order'] = 'DESC';
	$gm['in_category_orderby'] = 'ID';
	$gm['in_category_order'] = 'DESC';

	$gm['isolation_mode'] = '0';
	$gm['shortcode_raw'] = '0';
	$gm['debug_mode'] = WP_DEBUG? '1' : '';

	$gm['endpoint'] = 'gmedia';
	$gm['gmediacloud_module'] = '';
	$gm['gmediacloud_socialbuttons'] = '1';
	$gm['gmediacloud_footer_js'] = '';
	$gm['gmediacloud_footer_css'] = '';

	$gm['gmedia_post_types_support'] = '';

	$gm['folder']['image'] = 'image';
	$gm['folder']['image_thumb'] = 'image/thumb';
	$gm['folder']['image_original'] = 'image/original';
	$gm['folder']['audio'] = 'audio';
	$gm['folder']['video'] = 'video';
	$gm['folder']['text'] = 'text';
	$gm['folder']['application'] = 'application';
	$gm['folder']['module'] = 'module';

	$gm['thumb'] = array('width' => 300, 'height' => 300, 'quality' => 80, 'crop' => 0);
	$gm['image'] = array('width' => 2200, 'height' => 2200, 'quality' => 85, 'crop' => 0);

	$gm['modules_xml'] = 'https://dl.dropboxusercontent.com/u/6295502/gmedia_modules/modules_v1.xml';
	$gm['license_name'] = '';
	$gm['license_key'] = '';
	$gm['license_key2'] = '';

	$gm['taxonomies']['gmedia_category'] = array(
		'abstract' => __('Abstract', 'grand-media'),
		'animals' => __('Animals', 'grand-media'),
		'black-and-white' => __('Black and White', 'grand-media'),
		'celebrities' => __('Celebrities', 'grand-media'),
		'city-and-architecture' => __('City & Architecture', 'grand-media'),
		'commercial' => __('Commercial', 'grand-media'),
		'concert' => __('Concert', 'grand-media'),
		'family' => __('Family', 'grand-media'),
		'fashion' => __('Fashion', 'grand-media'),
		'film' => __('Film', 'grand-media'),
		'fine-art' => __('Fine Art', 'grand-media'),
		'food' => __('Food', 'grand-media'),
		'journalism' => __('Journalism', 'grand-media'),
		'landscapes' => __('Landscapes', 'grand-media'),
		'macro' => __('Macro', 'grand-media'),
		'nature' => __('Nature', 'grand-media'),
		'nude' => __('Nude', 'grand-media'),
		'people' => __('People', 'grand-media'),
		'performing-arts' => __('Performing Arts', 'grand-media'),
		'sport' => __('Sport', 'grand-media'),
		'still-life' => __('Still Life', 'grand-media'),
		'street' => __('Street', 'grand-media'),
		'transportation' => __('Transportation', 'grand-media'),
		'travel' => __('Travel', 'grand-media'),
		'underwater' => __('Underwater', 'grand-media'),
		'urban-exploration' => __('Urban Exploration', 'grand-media'),
		'wedding' => __('Wedding', 'grand-media')
	);
	$gm['taxonomies']['gmedia_tag'] = array();
	$gm['taxonomies']['gmedia_album'] = array();

	$gm['taxonomies']['gmedia_filter'] = array(); // not linked with gmedia_term_relationships table
	$gm['taxonomies']['gmedia_gallery'] = array(); // not linked with gmedia_term_relationships table
	$gm['taxonomies']['gmedia_module'] = array(); // not linked with gmedia_term_relationships table

	$gm['gm_screen_options']['per_page_sort_gmedia'] = 60;

	$gm['gm_screen_options']['per_page_gmedia'] = 30;
	$gm['gm_screen_options']['orderby_gmedia'] = 'ID';
	$gm['gm_screen_options']['sortorder_gmedia'] = 'DESC';

	$gm['gm_screen_options']['per_page_gmedia_terms'] = 30;
	$gm['gm_screen_options']['orderby_gmedia_terms'] = 'name';
	$gm['gm_screen_options']['sortorder_gmedia_terms'] = 'DESC';

	$gm['gm_screen_options']['per_page_gmedia_galleries'] = 30;
	$gm['gm_screen_options']['orderby_gmedia_galleries'] = 'name';
	$gm['gm_screen_options']['sortorder_gmedia_galleries'] = 'DESC';

	$gm['gm_screen_options']['per_page_wpmedia'] = 30;
	$gm['gm_screen_options']['orderby_wpmedia'] = 'ID';
	$gm['gm_screen_options']['sortorder_wpmedia'] = 'DESC';

	$gm['gm_screen_options']['uploader_runtime'] = 'auto';
	$gm['gm_screen_options']['uploader_chunking'] = 'true';
	$gm['gm_screen_options']['uploader_chunk_size'] = 8; // in Mb
	$gm['gm_screen_options']['uploader_urlstream_upload'] = 'false';

	$gm['gm_screen_options']['library_edit_quicktags'] = 'false';

	return $gm;

}

/**
 * sets gmedia capabilities to administrator role
 **/
function gmedia_capabilities(){
	// Set the capabilities for the administrator
	$role = get_role('administrator');
	// We need this role, no other chance
	if(empty($role)){
		update_option("gmediaInitCheck", __('Sorry, Gmedia Gallery works only with a role called administrator', 'grand-media'));

		return;
	}
	$capabilities = gmedia_plugin_capabilities();
	$capabilities = apply_filters('gmedia_capabilities', $capabilities);
	foreach($capabilities as $cap){
		$role->add_cap($cap);
	}
}

/**
 * creates all tables for the plugin
 * called during register_activation hook
 *
 * @access internal
 * @return void
 **/
function gmedia_install(){
	/** @var $wpdb wpdb */
	global $wpdb, $gmGallery, $gmCore;

	// Check for capability
	if(!current_user_can('activate_plugins')){
		return;
	}

	gmedia_capabilities();

	// upgrade function changed in WordPress 2.3	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	// add charset & collate like wp core
	$charset_collate = '';

	if($wpdb->has_cap('collation')){
		if(!empty($wpdb->charset)){
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if(!empty($wpdb->collate)){
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}

	$gmedia = $wpdb->prefix . 'gmedia';
	$gmedia_meta = $wpdb->prefix . 'gmedia_meta';
	$gmedia_term = $wpdb->prefix . 'gmedia_term';
	$gmedia_term_meta = $wpdb->prefix . 'gmedia_term_meta';
	$gmedia_term_relationships = $wpdb->prefix . 'gmedia_term_relationships';

	if($wpdb->get_var("show tables like '$gmedia'") != $gmedia){
		$sql = "CREATE TABLE {$gmedia} (
			ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			author BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			description LONGTEXT NOT NULL,
			title TEXT NOT NULL,
			gmuid VARCHAR(255) NOT NULL DEFAULT '',
			link VARCHAR(255) NOT NULL DEFAULT '',
			modified DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			mime_type VARCHAR(100) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'public',
			PRIMARY KEY  (ID),
			KEY gmuid (gmuid),
			KEY type_status_date (mime_type,status,date,ID),
			KEY author (author)
		) {$charset_collate}";
		dbDelta($sql);
	}

	if($wpdb->get_var("show tables like '$gmedia_meta'") != $gmedia_meta){
		$sql = "CREATE TABLE {$gmedia_meta} (
			meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			gmedia_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			meta_key VARCHAR(255) DEFAULT NULL,
			meta_value LONGTEXT,
			PRIMARY KEY  (meta_id),
			KEY gmedia_id (gmedia_id),
			KEY meta_key (meta_key)
		) {$charset_collate}";
		dbDelta($sql);
	}

	if($wpdb->get_var("show tables like '$gmedia_term'") != $gmedia_term){
		$sql = "CREATE TABLE {$gmedia_term} (
			term_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(200) NOT NULL DEFAULT '',
			taxonomy VARCHAR(32) NOT NULL DEFAULT '',
			description LONGTEXT NOT NULL,
			global BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			count BIGINT(20) NOT NULL DEFAULT '0',
			status VARCHAR(20) NOT NULL DEFAULT 'public',
			PRIMARY KEY  (term_id),
			KEY taxonomy (taxonomy),
			KEY name (name)
		) {$charset_collate}";
		dbDelta($sql);
	}

	if($wpdb->get_var("show tables like '$gmedia_term_meta'") != $gmedia_term_meta){
		$sql = "CREATE TABLE {$gmedia_term_meta} (
			meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			gmedia_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			meta_key VARCHAR(255) DEFAULT NULL,
			meta_value LONGTEXT,
			PRIMARY KEY  (meta_id),
			KEY gmedia_term_id (gmedia_term_id),
			KEY meta_key (meta_key)
		) {$charset_collate}";
		dbDelta($sql);
	}

	if($wpdb->get_var("show tables like '$gmedia_term_relationships'") != $gmedia_term_relationships){
		$sql = "CREATE TABLE {$gmedia_term_relationships} (
			gmedia_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			gmedia_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			term_order INT(11) NOT NULL DEFAULT '0',
			gmedia_order INT(11) NOT NULL DEFAULT '0',
			PRIMARY KEY  (gmedia_id,gmedia_term_id),
			KEY gmedia_term_id (gmedia_term_id)
		) {$charset_collate}";
		dbDelta($sql);
	}

	// check one table again, to be sure
	if($wpdb->get_var("show tables like '$gmedia'") != $gmedia){
		update_option("gmediaInitCheck", __('GmediaGallery: Tables could not created, please check your database settings', 'grand-media'));

		return;
	}

	if(!get_option('GmediaHashID_salt')){
		$ustr = wp_generate_password(12, false);
		add_option('GmediaHashID_salt', $ustr);
	}

	// set the default settings, if we didn't upgrade
	if(empty($gmGallery->options)){
		$gmGallery->options = gmedia_default_options();
		// Set installation date
		$gmGallery->options['installDate'] = time();
		update_option('gmediaOptions', $gmGallery->options);
	} else{
		$default_options = gmedia_default_options();
		unset($gmGallery->options['folder'], $gmGallery->options['taxonomies']);
		$new_options = $gmCore->array_diff_key_recursive($default_options, $gmGallery->options);
		$gmGallery->options = $gmCore->array_replace_recursive($gmGallery->options, $new_options);
		update_option('gmediaOptions', $gmGallery->options);
	}

	// try to make gallery dirs if not exists
	foreach($gmGallery->options['folder'] as $folder){
		wp_mkdir_p($gmCore->upload['path'] . '/' . $folder);
	}
}

/**
 * Called via Setup and register_deactivate hook
 *
 * @access internal
 * @return void
 */
function gmedia_deactivate(){
	$gm_options = get_option('gmediaOptions');
	if((int) $gm_options['mobile_app']){
		global $gmCore;
		$gmCore->app_service('app_deactivate');
	}
	// remove & reset the init check option
	delete_option('gmediaInitCheck');
}

