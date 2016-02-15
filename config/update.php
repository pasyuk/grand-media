<?php
/**
 * Update Gmedia plugin
 */

function gmedia_upgrade_required_admin_notice() {
    ?>
    <div id="message" class="updated gmedia-message">
        <p><?php _e('<strong>GmediaGallery Database Update Required</strong> &#8211; We need to update your install to the latest version.', 'grand-media'); ?></p>

        <p><?php _e('<strong>Important:</strong> &#8211; It is strongly recommended that you backup your database before proceeding.', 'grand-media'); ?></p>

        <p><?php _e('The update process may take a little while, so please be patient.', 'grand-media'); ?></p>

        <p class="submit">
            <a href="<?php echo add_query_arg('do_update', 'gmedia', admin_url('admin.php?page=GrandMedia')); ?>" class="gm-update-now button-primary"><?php _e('Run the updater', 'grand-media'); ?></a>
        </p>
    </div>
    <script type="text/javascript">
        jQuery('.gm-update-now').click('click', function() {
            return confirm('<?php _e('It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'grand-media'); ?>');
        });
    </script>
    <?php

}

function gmedia_upgrade_process_admin_notice() {
    ?>
    <div id="message" class="updated gmedia-message">
        <p><?php _e('<strong>GmediaGallery Database Update Required</strong> &#8211; We need to update your install to the latest version.', 'grand-media'); ?></p>
        <p><?php _e('The update process may take a little while, so please be patient.', 'grand-media'); ?></p>
    </div>
    <?php

}

function gmedia_upgrade_progress_panel() {
    gmedia_do_update();
    ?>
<div id="gmediaUpdate" class="panel panel-default">
    <div class="panel-body">
        <div id="gmUpdateProgress">

        </div>
        <div class="gm_upgrade_busy"><img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'admin/assets/img/loading.gif' ?>" alt="updating..." /></div>
    </div>
    <script type="text/javascript">
        jQuery(function(){
            gmUpdateProgress();
        });
        function gmUpdateProgress(){
            jQuery.ajax({
                type: "get",
                dataType: "json",
                url: ajaxurl,
                data: {action: 'gmedia_upgrade_process'}
            }).done(function(data){
                if(data.content) {
                    jQuery('#gmUpdateProgress').html(data.content);
                }
                if(data.status == 'done'){
                    jQuery('.gm_upgrade_busy').remove();
                    if('' == data.content){
                        jQuery('#gmUpdateProgress').append('<p><a class="btn btn-success" href="<?php echo admin_url('admin.php?page=GrandMedia'); ?>"><?php _e('Go to Gmedia Library', 'grand-media') ?></a></p>');
                    }
                    return;
                } else {
                    setTimeout(function(){ gmUpdateProgress(); }, 2000);
                }
            });
        }
    </script>
</div>
    <?php
}

function gmedia_do_update() {
    global $wpdb;

    $info = get_transient('gmediaHeavyJob');

    ignore_user_abort(true);
    // 10 minutes execution time
    @set_time_limit(0);

    // upgrade function changed in WordPress 2.3
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // add charset & collate like wp core
    $charset_collate = '';

    if($wpdb->has_cap('collation')) {
        if(!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }
        if(!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }
    }

    $gmedia                    = $wpdb->prefix . 'gmedia';
    $gmedia_term               = $wpdb->prefix . 'gmedia_term';
    $gmedia_term_relationships = $wpdb->prefix . 'gmedia_term_relationships';

    $sql = "
	CREATE TABLE {$gmedia} (
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
	) {$charset_collate};
	CREATE TABLE {$gmedia_term} (
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
	) {$charset_collate};
	CREATE TABLE {$gmedia_term_relationships} (
		gmedia_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
		gmedia_term_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
		term_order INT(11) NOT NULL DEFAULT '0',
		gmedia_order INT(11) NOT NULL DEFAULT '0',
		PRIMARY KEY  (gmedia_id,gmedia_term_id),
		KEY gmedia_term_id (gmedia_term_id)
	) {$charset_collate}
	";
    dbDelta($sql);

    if(!$info) {
        $info = array();
    }
    $info['db_tables'] = __('Gmedia database tables updated...', 'grand-media');
    set_transient('gmediaHeavyJob', $info);

    $upgrading = get_transient('gmediaUpgrade');
    if($upgrading && (time() - $upgrading) > 30){
        $upgrading = false;
    }
    if(!wp_get_schedule('gmedia_db_update') && !$upgrading) {
        delete_transient('gmediaUpgradeSteps');
        set_transient('gmediaUpgrade', time());
        wp_schedule_single_event(time() + 1, 'gmedia_db_update');
    }

}
add_action( 'gmedia_db_update', 'gmedia_db_update' );
function gmedia_db_update() {

    ignore_user_abort(true);
    // 10 minutes execution time
    @set_time_limit(0);

    $db_version = get_option('gmediaDbVersion');
    $info = get_transient('gmediaHeavyJob');

    if(version_compare($db_version, '0.9.6', '<')) {
        gmedia_db_update__0_9_6();

    } elseif(version_compare($db_version, '1.8.0', '<')) {
        gmedia_db_update__1_8_0();

    } else {
        $info['update_complete'] = __('GmediaGallery plugin update complete.', 'grand-media');
        set_transient('gmediaHeavyJob', $info);

        gmedia_flush_rewrite_rules();

        sleep(4);
        delete_transient('gmediaHeavyJob');
        delete_transient('gmediaUpgrade');
        delete_transient('gmediaUpgradeSteps');
    }
}

function gmedia_db_update__0_9_6() {
    global $wpdb, $gmDB, $gmCore, $gmGallery;

    $info = get_transient('gmediaHeavyJob');

    $info['096_1'] = __('Start update images...', 'grand-media');
    set_transient('gmediaHeavyJob', $info);
    set_transient('gmediaUpgrade', time());

    $old_options = get_option('gmediaOptions');
    require_once(dirname(__FILE__) . '/setup.php');
    $options = gmedia_default_options();
    if(isset($old_options['product_name'])) {
        $options['license_name'] = $old_options['product_name'];
        $options['license_key']  = $old_options['gmedia_key'];
        $options['license_key2'] = $old_options['gmedia_key2'];
    }
    update_option('gmediaOptions', $options);
    $gmGallery->options = $options;

    $fix_files = glob($gmCore->upload['path'] . '/?*.?*', GLOB_NOSORT);
    if(!empty($fix_files)) {
        foreach($fix_files as $ff) {
            @rename($ff, $gmCore->upload['path'] . '/image/' . basename($ff));
        }
    }

    $gmedias = $gmDB->get_gmedias(array('mime_type' => 'image/*', 'cache_results' => false));
    $files   = array();
    foreach($gmedias as $gmedia) {
        $files[] = array(
                'id'   => $gmedia->ID,
                'file' => $gmCore->upload['path'] . '/image/' . $gmedia->gmuid,
        );
    }
    if(!empty($files)) {
        gmedia_images_update($files);
    }
    $gmCore->delete_folder($gmCore->upload['path'] . '/link');

    // try to make gallery dirs if not exists
    foreach($gmGallery->options['folder'] as $folder) {
        wp_mkdir_p($gmCore->upload['path'] . '/' . $folder);
    }

    $wpdb->update($wpdb->prefix . 'gmedia_term', array('taxonomy' => 'gmedia_album'), array('taxonomy' => 'gmedia_category'));
    $wpdb->update($wpdb->prefix . 'gmedia_term', array('taxonomy' => 'gmedia_gallery'), array('taxonomy' => 'gmedia_module'));

    $gmedias = $gmDB->get_gmedias(array('no_found_rows' => true, 'meta_key' => 'link', 'cache_results' => false));
    foreach($gmedias as $gmedia) {
        $link = $gmDB->get_metadata('gmedia', $gmedia->ID, 'link', true);
        if($link) {
            $wpdb->update($wpdb->prefix . 'gmedia', array('link' => $link), array('ID' => $gmedia->ID));
        }
    }
    $wpdb->delete($wpdb->prefix . 'gmedia_meta', array('meta_key' => 'link'));
    $wpdb->update($wpdb->prefix . 'gmedia_meta', array('meta_key' => '_cover'), array('meta_key' => 'preview'));

    $info['096_2'] = __('Gmedia database data updated...', 'grand-media');
    set_transient('gmediaHeavyJob', $info);
    set_transient('gmediaUpgrade', time());

    $galleries = $gmDB->get_terms('gmedia_gallery');
    if($galleries) {
        foreach($galleries as $gallery) {
            $old_meta = $gmDB->get_metadata('gmedia_term', $gallery->term_id);
            if(!empty($old_meta)) {
                $old_meta = array_map('reset', $old_meta);
                if(!isset($old_meta['gMediaQuery'])) {
                    continue;
                }
                $gmedia_category = $gmedia_tag = array();
                foreach($old_meta['gMediaQuery'] as $tab) {
                    if(isset($tab['cat']) && !empty($tab['cat'])) {
                        $gmedia_category[] = $tab['cat'];
                    }
                    if(isset($tab['tag__in']) && !empty($tab['tag__in'])) {
                        $gmedia_tag = array_merge($gmedia_tag, $tab['tag__in']);
                    }
                }
                $query = array();
                if(!empty($gmedia_category)) {
                    $query = array('gmedia_album' => $gmedia_category);
                } elseif(!empty($gmedia_tag)) {
                    $query = array('gmedia_tag' => $gmedia_tag);
                }
                $gallery_meta = array(
                        '_edited' => $old_meta['last_edited'],
                        '_module' => $old_meta['module_name'],
                        '_query'  => $query
                );
                foreach($gallery_meta as $key => $value) {
                    $gmDB->update_metadata('gmedia_term', $gallery->term_id, $key, $value);
                }
            }
        }
    }

    update_option("gmediaDbVersion", '0.9.6');

    $info['096_3'] = __('Gmedia Galleries updated...', 'grand-media');
    set_transient('gmediaHeavyJob', $info);
    set_transient('gmediaUpgrade', time());

    //wp_schedule_single_event(time() + 2, 'gmedia_db_update');
    gmedia_db_update();
}

function gmedia_db_update__1_8_0() {
    global $wpdb, $gmDB, $gmGallery;

    $info = get_transient('gmediaHeavyJob');
    $steps = get_transient('gmediaUpgradeSteps');
    if(!isset($steps['status_update'])) {
        $wpdb->update($wpdb->prefix . 'gmedia', array('status' => 'publish'), array('status' => 'public'));
        $wpdb->update($wpdb->prefix . 'gmedia_term', array('status' => 'publish'), array('status' => 'public'));
        $wpdb->update($wpdb->prefix . 'gmedia_term', array('global' => 0), array('taxonomy' => 'gmedia_tag'));
        $wpdb->update($wpdb->prefix . 'gmedia_term', array('global' => 0), array('taxonomy' => 'gmedia_category'));

        $info['180_1'] = __('Gmedia database data updated...', 'grand-media');
        $info['180_2'] = __('Adding ability to comment gmedia items...', 'grand-media');
        set_transient('gmediaHeavyJob', $info);
        set_transient('gmediaUpgrade', time());

        $steps['status_update'] = 1;
        $steps['step'] = 0;
    }

    $steps['step']++;

    if(!isset($steps['gmedia_posts'])) {
        $gm_options = $gmGallery->options;
        $step    = $steps['step'];

        $gmedias = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia WHERE post_id IS NULL OR post_id = '' OR post_id = 0 LIMIT 100");
        if(!empty($gmedias)) {
            $post_data = array(
                    'post_type'      => 'gmedia',
                    'comment_status' => $gm_options['default_gmedia_comment_status']
            );
            $total     = count($gmedias);
            $i         = 0;
            foreach($gmedias as $gmedia) {
                $i++;

                $post_data['post_author']    = $gmedia->author;
                $post_data['post_content']   = $gmedia->description;
                $post_data['post_title']     = (trim($gmedia->title)? $gmedia->title : $gmedia->gmuid);
                $post_data['post_status']    = $gmedia->status;
                $post_data['post_name']      = $gmedia->gmuid;
                $post_data['post_date']      = $gmedia->date;
                $post_data['post_modified']  = $gmedia->modified;
                $post_data['post_mime_type'] = $gmedia->mime_type;

                $post_ID = wp_insert_post($post_data);
                if($post_ID) {
                    add_metadata('post', $post_ID, '_gmedia_ID', $gmedia->ID);
                    $wpdb->update($wpdb->prefix . 'gmedia', array('post_id' => $post_ID), array('ID' => $gmedia->ID));

                    $info['180_3_' . $step] = sprintf(__('Step %d: Updating %d of %d items...', 'grand-media'), $step, $i, $total);
                    set_transient('gmediaHeavyJob', $info);
                    set_transient('gmediaUpgrade', time());
                }
            }

            $info['180_4_' . $step] = __('Update cache...', 'grand-media');
            set_transient('gmediaHeavyJob', $info);

            $gmDB->update_gmedia_caches($gmedias, false, false);

            set_transient('gmediaUpgradeSteps', $steps);
            wp_schedule_single_event(time(), 'gmedia_db_update');
        } else {
            $info['180_5'] = __('Adding other features...', 'grand-media');
            set_transient('gmediaHeavyJob', $info);

            $steps['gmedia_posts'] = 1;
        }
    }

    if(isset($steps['gmedia_posts']) && !isset($steps['terms_posts'])) {
        $taxonomies   = array('gmedia_album', 'gmedia_filter', 'gmedia_gallery');
        $gmedia_terms_with_post = $wpdb->get_col("SELECT gmedia_term_id FROM {$wpdb->prefix}gmedia_term_meta WHERE meta_key = '_post_ID' AND meta_value != ''");
        $gmedia_terms_exclude = '';
        if(!empty($gmedia_terms_with_post)){
            $gmedia_terms_exclude = "AND term_id NOT IN ('" . implode("','", $gmedia_terms_with_post) . "')";
        }
        $gmedia_terms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia_term WHERE taxonomy IN('" . implode("','", $taxonomies) . "') {$gmedia_terms_exclude} LIMIT 100");
        if(!empty($gmedia_terms)) {
            $total = count($gmedia_terms);
            $i     = 0;
            foreach($gmedia_terms as $term) {
                if($gmDB->get_metadata('gmedia_term', $term->term_id, '_post_ID', true)) {
                    continue;
                }
                $post_data = array(
                        'post_author'    => $term->global
                        , 'post_content' => $term->description
                        , 'post_title'   => $term->name
                        , 'post_status'  => $term->status
                        , 'post_type'    => $term->taxonomy
                );
                $post_ID   = wp_insert_post($post_data);
                if($post_ID) {
                    add_metadata('post', $post_ID, '_gmedia_term_ID', $term->term_id);
                    $gmDB->add_metadata('gmedia_term', $term->term_id, '_post_ID', $post_ID);

                    $i++;
                    $info['180_6_' . $step] = sprintf(__('Step %d: Updating %d of %d items...', 'grand-media'), $step, $i, $total);
                    set_transient('gmediaHeavyJob', $info);
                    set_transient('gmediaUpgrade', time());
                }
            }

            set_transient('gmediaUpgradeSteps', $steps);
            wp_schedule_single_event(time(), 'gmedia_db_update');
        } else {
            $info['180_7'] = __('Update cache...', 'grand-media');
            set_transient('gmediaHeavyJob', $info);

            foreach($taxonomies as $taxonomy) {
                wp_cache_delete('all_ids', $taxonomy);
                wp_cache_delete('get', $taxonomy);
            }
            wp_cache_set('last_changed', time(), 'gmedia_terms');
            set_transient('gmediaUpgrade', time());

            $steps['terms_posts'] = 1;
        }
    }

    if(isset($steps['terms_posts'])){
        update_option("gmediaDbVersion", '1.8.0');
        set_transient('gmediaUpgradeSteps', $steps);
        //wp_schedule_single_event(time() + 2, 'gmedia_db_update');
        gmedia_db_update();
    }

}

/**
 * @param $files
 */
function gmedia_images_update($files) {
    global $wpdb, $gmCore, $gmGallery;

    $info = get_transient('gmediaHeavyJob');

    $eol = '</pre>' . PHP_EOL;
    $c   = count($files);
    $i   = 0;
    foreach($files as $file) {

        /**
         * @var $file
         * @var $id
         */
        if(is_array($file)) {
            if(isset($file['file'])) {
                extract($file);
            } else {
                _e('Something went wrong...', 'grand-media');
                die();
            }
        }

        $i++;
        $prefix    = "\n<pre style='display:block;'>$i/$c - ";
        $prefix_ko = "\n<pre style='display:block;color:darkred;'>$i/$c - ";

        if(!is_file($file)) {
            $fileinfo = $gmCore->fileinfo($file, false);
            if(is_file($fileinfo['filepath_original'])) {
                @rename($fileinfo['filepath_original'], $fileinfo['filepath']);
            } else {
                $info['img_'.$i] = $prefix_ko . sprintf(__('File not exists: %s', 'grand-media'), $file) . $eol;
                set_transient('gmediaHeavyJob', $info);
                continue;
            }
        }

        $file_File = $file;
        $fileinfo  = $gmCore->fileinfo($file, false);

        if($file_File != $fileinfo['filepath']) {
            @rename($file_File, $fileinfo['filepath']);
            $wpdb->update($wpdb->prefix . 'gmedia', array('gmuid' => $fileinfo['basename']), array('gmuid' => basename($file_File)));
        }

        if('image' == $fileinfo['dirname']) {
            $size = @getimagesize($fileinfo['filepath']);
            if(!file_exists($fileinfo['filepath_thumb']) && file_is_displayable_image($fileinfo['filepath'])) {
                if(function_exists('memory_get_usage')) {
                    $extensions = array('1' => 'GIF', '2' => 'JPG', '3' => 'PNG', '6' => 'BMP');
                    switch($extensions[$size[2]]) {
                        case 'GIF':
                            $CHANNEL = 1;
                        break;
                        case 'JPG':
                            $CHANNEL = $size['channels'];
                        break;
                        case 'PNG':
                            $CHANNEL = 3;
                        break;
                        case 'BMP':
                        default:
                            $CHANNEL = 6;
                        break;
                    }
                    $MB                = 1048576;  // number of bytes in 1M
                    $K64               = 65536;    // number of bytes in 64K
                    $TWEAKFACTOR       = 1.8;     // Or whatever works for you
                    $memoryNeeded      = round(($size[0] * $size[1] * $size['bits'] * $CHANNEL / 8 + $K64) * $TWEAKFACTOR);
                    $memoryNeeded      = memory_get_usage() + $memoryNeeded;
                    $current_limit     = @ini_get('memory_limit');
                    $current_limit_int = intval($current_limit);
                    if(false !== strpos($current_limit, 'M')) {
                        $current_limit_int *= $MB;
                    }
                    if(false !== strpos($current_limit, 'G')) {
                        $current_limit_int *= 1024;
                    }

                    if(-1 != $current_limit && $memoryNeeded > $current_limit_int) {
                        $newLimit = $current_limit_int / $MB + ceil(($memoryNeeded - $current_limit_int) / $MB);
                        @ini_set('memory_limit', $newLimit . 'M');
                    }
                }

                if(!wp_mkdir_p($fileinfo['dirpath_thumb'])) {
                    $info['img_'.$i] = $prefix_ko . sprintf(__('Unable to create directory `%s`. Is its parent directory writable by the server?', 'grand-media'), $fileinfo['dirpath_thumb']) . $eol;
                    set_transient('gmediaHeavyJob', $info);
                    continue;
                }
                if(!is_writable($fileinfo['dirpath_thumb'])) {
                    @chmod($fileinfo['dirpath_thumb'], 0755);
                    if(!is_writable($fileinfo['dirpath_thumb'])) {
                        $info['img_'.$i] = $prefix_ko . sprintf(__('Directory `%s` is not writable by the server.', 'grand-media'), $fileinfo['dirpath_thumb']) . $eol;
                        set_transient('gmediaHeavyJob', $info);
                        continue;
                    }
                }
                if(!wp_mkdir_p($fileinfo['dirpath_original'])) {
                    $info['img_'.$i] = $prefix_ko . sprintf(__('Unable to create directory `%s`. Is its parent directory writable by the server?', 'grand-media'), $fileinfo['dirpath_original']) . $eol;
                    set_transient('gmediaHeavyJob', $info);
                    continue;
                }
                if(!is_writable($fileinfo['dirpath_original'])) {
                    @chmod($fileinfo['dirpath_original'], 0755);
                    if(!is_writable($fileinfo['dirpath_original'])) {
                        $info['img_'.$i] = $prefix_ko . sprintf(__('Directory `%s` is not writable by the server.', 'grand-media'), $fileinfo['dirpath_original']) . $eol;
                        set_transient('gmediaHeavyJob', $info);
                        continue;
                    }
                }

                // Optimized image
                $webimg   = $gmGallery->options['image'];
                $thumbimg = $gmGallery->options['thumb'];

                $webimg['resize']   = (($webimg['width'] < $size[0]) || ($webimg['height'] < $size[1]))? true : false;
                $thumbimg['resize'] = (($thumbimg['width'] < $size[0]) || ($thumbimg['height'] < $size[1]))? true : false;

                if($webimg['resize']) {
                    rename($fileinfo['filepath'], $fileinfo['filepath_original']);
                } else {
                    copy($fileinfo['filepath'], $fileinfo['filepath_original']);
                }
                if($webimg['resize'] || $thumbimg['resize']) {
                    $editor = wp_get_image_editor($fileinfo['filepath_original']);
                    if(is_wp_error($editor)) {
                        $info['img_'.$i] = $prefix_ko . $fileinfo['basename'] . " (wp_get_image_editor): " . $editor->get_error_message();
                        set_transient('gmediaHeavyJob', $info);
                        continue;
                    }

                    if($webimg['resize']) {
                        $editor->set_quality($webimg['quality']);

                        $resized = $editor->resize($webimg['width'], $webimg['height'], $webimg['crop']);
                        if(is_wp_error($resized)) {
                            $info['img_'.$i] = $prefix_ko . $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->webimage({$webimg['width']}, {$webimg['height']}, {$webimg['crop']})): " . $resized->get_error_message() . $eol;
                            set_transient('gmediaHeavyJob', $info);
                            continue;
                        }

                        $saved = $editor->save($fileinfo['filepath']);
                        if(is_wp_error($saved)) {
                            $info['img_'.$i] = $prefix_ko . $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->webimage): " . $saved->get_error_message() . $eol;
                            set_transient('gmediaHeavyJob', $info);
                            continue;
                        }
                    }

                    // Thumbnail
                    $editor->set_quality($thumbimg['quality']);

                    $resized = $editor->resize($thumbimg['width'], $thumbimg['height'], $thumbimg['crop']);
                    if(is_wp_error($resized)) {
                        $info['img_'.$i] = $prefix_ko . $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->thumb({$thumbimg['width']}, {$thumbimg['height']}, {$thumbimg['crop']})): " . $resized->get_error_message() . $eol;
                        set_transient('gmediaHeavyJob', $info);
                        continue;
                    }

                    $saved = $editor->save($fileinfo['filepath_thumb']);
                    if(is_wp_error($saved)) {
                        $info['img_'.$i] = $prefix_ko . $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->thumb): " . $saved->get_error_message() . $eol;
                        set_transient('gmediaHeavyJob', $info);
                        continue;
                    }
                } else {
                    copy($fileinfo['filepath'], $fileinfo['filepath_thumb']);
                }
            } else {
                $info['img_'.$i] = $prefix . $fileinfo['basename'] . ": " . __("Ignored", 'grand-media') . $eol;
                set_transient('gmediaHeavyJob', $info);
                continue;
            }
        } else {
            $info['img_'.$i] = $prefix_ko . $fileinfo['basename'] . ": " . __("Invalid image.", 'grand-media') . $eol;
            set_transient('gmediaHeavyJob', $info);
            continue;
        }

        global $gmDB;
        // Save the data
        $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $gmDB->generate_gmedia_metadata($id, $fileinfo));

        $info['img_'.$i] = $prefix . $fileinfo['basename'] . ': <span  style="color:darkgreen;">' . sprintf(__('success (ID #%s)', 'grand-media'), $id) . '</span>' . $eol;
        set_transient('gmediaHeavyJob', $info);
    }

    $info['imgs_done'] = '<p>' . __('Image update process complete...', 'grand-media') . '</p>';
    set_transient('gmediaHeavyJob', $info);

}

function gmedia_flush_rewrite_rules() {
    flush_rewrite_rules(false);
}

function gmedia_restore_original_images() {
    global $wpdb, $gmGallery, $gmCore, $gmDB;

    $fix_files = glob($gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_original'] . '/?*.?*_backup', GLOB_NOSORT);
    if(!empty($fix_files)) {
        foreach($fix_files as $ff) {
            $gmuid = basename($ff, '_backup');
            $id    = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}gmedia WHERE gmuid = %s", $gmuid));
            if($id) {
                $gmDB->update_metadata('gmedia', $id, '_modified', 1);
                @rename($ff, $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_original'] . '/' . $gmuid);
            } else {
                @unlink($gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_original'] . '/' . $gmuid . '_backup');
            }
        }
    }
}


function gmedia_quite_update() {
    global $wpdb, $gmDB, $gmCore, $gmGallery;
    $current_version = get_option('gmediaVersion', null);
    //$current_db_version = get_option( 'gmediaDbVersion', null );
    if((null !== $current_version)) {
        $options = get_option('gmediaOptions');
        if(!is_array($options)) {
            $options = array();
        }
        require_once(dirname(__FILE__) . '/setup.php');
        $default_options = gmedia_default_options();
        if(!get_option('gmediaInstallDate')) {
            $date = $wpdb->get_var("SELECT {$wpdb->prefix}gmedia.date FROM {$wpdb->prefix}gmedia ORDER BY ID ASC");
            if(!$date) {
                $date = '1 month ago';
            }
            $installDate = strtotime($date);
            add_option('gmediaInstallDate', $installDate);
        }

        if(version_compare($current_version, '0.9.23', '<')) {
            if(isset($options['license_name'])) {
                $default_options['license_name'] = $options['license_name'];
                $default_options['license_key']  = $options['license_key'];
                $default_options['license_key2'] = $options['license_key2'];
            } elseif(isset($options['product_name'])) {
                $default_options['license_name'] = $options['product_name'];
                $default_options['license_key']  = $options['gmedia_key'];
                $default_options['license_key2'] = $options['gmedia_key2'];
            }
            update_option('gmediaOptions', $default_options);
        } else {
            $new_options        = $gmCore->array_diff_key_recursive($default_options, $options);
            $gmGallery->options = $gmCore->array_replace_recursive($options, $new_options);
            update_option('gmediaOptions', $gmGallery->options);
        }

        if(version_compare($current_version, '1.2.0', '<')) {
            gmedia_capabilities();
        }

        if(version_compare($current_version, '1.4.4', '<')) {
            if(!get_option('GmediaHashID_salt')) {
                $ustr = wp_generate_password(12, false);
                add_option('GmediaHashID_salt', $ustr);
            }
        }

        if(version_compare($current_version, '1.6.01', '<')) {
            $gmDB->set_capability('administrator', 'gmedia_filter_manage');
        }

        if(version_compare($current_version, '1.6.3', '<')) {
            $wpdb->update($wpdb->prefix . 'gmedia_meta', array('meta_key' => '_cover'), array('meta_key' => 'cover'));
            $wpdb->update($wpdb->prefix . 'gmedia_meta', array('meta_key' => '_rating'), array('meta_key' => 'rating'));
        }
        if(version_compare($current_version, '1.6.5', '<')) {
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_key' => '_edited'), array('meta_key' => 'edited'));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_key' => '_settings'), array('meta_key' => 'settings'));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_key' => '_query'), array('meta_key' => 'query'));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_key' => '_module'), array('meta_key' => 'module'));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_key' => '_order'), array('meta_key' => 'order'));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_key' => '_orderby'), array('meta_key' => 'orderby'));
        }
        if(version_compare($current_version, '1.6.6', '<')) {
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_value' => 'ID'), array('meta_key' => '_orderby', 'meta_value' => ''));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_value' => 'DESC'), array('meta_key' => '_order', 'meta_value' => ''));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_value' => 'title'), array('meta_key' => '_orderby', 'meta_value' => 'title ID'));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_value' => 'date'), array('meta_key' => '_orderby', 'meta_value' => 'date ID'));
            $wpdb->update($wpdb->prefix . 'gmedia_term_meta', array('meta_value' => 'modified'), array('meta_key' => '_orderby', 'meta_value' => 'modified ID'));
        }
        if(version_compare($current_version, '1.7.1', '<')) {
            $gmedia_ids = $gmDB->get_gmedias(array('mime_type' => 'audio', 'fields' => 'ids'));
            foreach($gmedia_ids as $id) {
                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $gmDB->generate_gmedia_metadata($id));
            }
        }
        if(version_compare($current_version, '1.7.20', '<')) {
            gmedia_restore_original_images();
        }

        $gmCore->delete_folder($gmCore->upload['path'] . '/module/afflux');
        $gmCore->delete_folder($gmCore->upload['path'] . '/module/jq-mplayer');
        $gmCore->delete_folder($gmCore->upload['path'] . '/module/minima');
        $gmCore->delete_folder($gmCore->upload['path'] . '/module/phantom');
        $gmCore->delete_folder($gmCore->upload['path'] . '/module/wp-videoplayer');

        update_option("gmediaVersion", GMEDIA_VERSION);

        $gmCore->app_service('app_updatecron');
    }
}
