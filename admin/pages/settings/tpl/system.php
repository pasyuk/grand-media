<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * System info (under constraction)
 */
?>
<fieldset id="gmedia_settings_sysinfo" class="tab-pane">
    <?php
    if ((function_exists('memory_get_usage')) && (ini_get('memory_limit'))) {
        $memory_limit = ini_get('memory_limit');
        $memory_usage = round(memory_get_usage() / (1024 * 1024), 1);
        echo '<p>' . __('PHP Memory Limit: ', 'grand-media') . $memory_limit . '</p>';
        echo '<p>' . __('PHP Memory Used: ', 'grand-media') . $memory_usage . 'M</p>';
    }
    ?>
    <p><?php _e('Under constraction...') ?></p>

    <?php
    if ($gmCore->_get('showdb')) {
        global $wpdb;
        $gmedia                = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia");
        $terms                 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia_term");
        $relation              = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia_term_relationships");
        $images['grand-media'] = glob($gmCore->upload['path'] . '/*', GLOB_NOSORT);
        $images['images']      = glob($gmCore->upload['path'] . '/image/*', GLOB_NOSORT);
        $images['thumbs']      = glob($gmCore->upload['path'] . '/thumb/*', GLOB_NOSORT);
        echo '<pre style="max-height:400px; overflow:auto;">' . print_r($gmedia, true) . '</pre>';
        echo '<pre style="max-height:400px; overflow:auto;">' . print_r($images, true) . '</pre>';
        echo '<pre style="max-height:400px; overflow:auto;">' . print_r($terms, true) . '</pre>';
        echo '<pre style="max-height:400px; overflow:auto;">' . print_r($relation, true) . '</pre>';
    }
    ?>
</fieldset>

