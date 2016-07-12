<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * @var $url
 * @var $import
 */
$extra_tools = (defined('GMEDIA_IFRAME') && GMEDIA_IFRAME)? false : true;
?>
<div class="panel-heading clearfix">
    <?php if($extra_tools) { ?>
        <div class="btn-toolbar pull-left">
            <div class="btn-group">
                <a class="btn btn<?php echo !$import? '-primary active' : '-default'; ?>" href="<?php echo $url; ?>"><?php _e('Upload Files', 'grand-media'); ?></a>
                <?php if(gm_user_can('import')) { ?>
                    <a class="btn btn<?php echo $import? '-primary active' : '-default'; ?>" href="<?php echo gm_get_admin_url(array('import' => 1), null, $url); ?>"><?php _e('Import', 'grand-media'); ?></a>
                <?php } ?>
            </div>
            <?php if($import && gm_user_can('import')) { ?>
                <a class="btn btn-default" href="<?php echo admin_url('admin.php?page=GrandMedia_WordpressLibrary'); ?>"><?php _e('Import from WP Media Library', 'grand-media'); ?></a>
            <?php } ?>
        </div>
    <?php } ?>
    <div id="total-progress-info" class="progress pull-right">
        <?php $msg = '';
        if(!$import) {
            $msg = __('Add files to the upload queue and click the start button', 'grand-media');
        } else {
            $msg = __('Grab files from other sources', 'grand-media');
        }
        ?>
        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0;">
            <div style="padding: 2px 10px;"><?php echo $msg; ?></div>
        </div>
        <div style="padding: 2px 10px;"><?php echo $msg; ?></div>
    </div>
    <div class="spinner"></div>
</div>
