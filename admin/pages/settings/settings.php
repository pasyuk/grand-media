<?php
/**
 * Gmedia Settings
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}


global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$url = add_query_arg(array('page' => $gmProcessor->page), admin_url('admin.php'));
$lk  = isset($gmGallery->options['license_key'])? $gmGallery->options['license_key'] : '';
?>

<form id="gmediaSettingsForm" class="panel panel-default" method="post" action="<?php echo $url; ?>">
    <div class="panel-heading clearfix">
        <div class="btn-toolbar pull-left">
            <div class="btn-group">
                <button type="submit" name="gmedia_settings_reset" class="btn btn-default" data-confirm="<?php _e('Reset all Gmedia settings?', 'grand-media') ?>"><?php _e('Reset Settings', 'grand-media'); ?></button>
                <button type="submit" name="gmedia_settings_save" class="btn btn-primary"><?php _e('Update', 'grand-media'); ?></button>
            </div>
        </div>
        <?php
        wp_nonce_field('GmediaSettings');
        ?>
    </div>
    <div class="panel-body" id="gmedia-msg-panel"></div>
    <div class="container-fluid">
        <div class="tabable tabs-left">
            <ul id="settingsTabs" class="nav nav-tabs" style="padding:10px 0;">
                <li class="active"><a href="#gmedia_premium" data-toggle="tab"><?php _e('Premium Settings', 'grand-media'); ?></a></li>
                <li><a href="#gmedia_settings_other" data-toggle="tab"><?php _e('Other Settings', 'grand-media'); ?></a></li>
                <?php if(current_user_can('manage_options')) { ?>
                    <li><a href="#gmedia_settings_permalinks" data-toggle="tab"><?php _e('Permalinks', 'grand-media'); ?></a></li>
                    <li><a href="#gmedia_settings_cloud" data-toggle="tab"><?php _e('GmediaCloud Page', 'grand-media'); ?></a></li>
                    <li><a href="#gmedia_settings_roles" data-toggle="tab"><?php _e('Roles/Capabilities Manager', 'grand-media'); ?></a></li>
                <?php } ?>
                <li><a href="#gmedia_settings_sysinfo" data-toggle="tab"><?php _e('System Info', 'grand-media'); ?></a></li>
            </ul>
            <div class="tab-content" style="padding-top:21px;">
                <?php
                include(dirname(__FILE__) . '/tpl/license.php');
                include(dirname(__FILE__) . '/tpl/common.php');
                if(current_user_can('manage_options')) {
                    include(dirname(__FILE__) . '/tpl/permalinks.php');
                    include(dirname(__FILE__) . '/tpl/roles.php');
                }
                include(dirname(__FILE__) . '/tpl/system.php');
                ?>

            </div>
            <div class="clear"></div>
        </div>
        <script type="text/javascript">
            jQuery(function($) {
                var hash = window.location.hash;
                if(hash) {
                    hash = hash.replace('_tab', '');
                    $('#settingsTabs a[href="' + hash + '"]').tab('show');
                }
                $('#gmediaSettingsForm').on('submit', function() {
                    $(this).attr('action', $(this).attr('action') + $('#settingsTabs li.active a').attr('href') + '_tab');
                });
            });
        </script>
    </div>
</form>
