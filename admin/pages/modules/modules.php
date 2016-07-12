<?php
/**
 * Gmedia Modules
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

global $gmCore, $gmProcessor, $gmGallery, $gmDB;

$gmedia_url = $gmProcessor->url;
$modules    = get_gmedia_modules();

if(isset($modules['error'])) {
    echo $gmCore->alert('danger', $modules['error']);
}

?>
<div id="gmedia_modules">
    <div class="panel panel-default">
        <div class="panel-heading clearfix">
            <a href="#installModuleModal" class="btn btn-primary pull-right<?php echo current_user_can('manage_options')? '' : ' disabled'; ?>" data-toggle="modal"><?php _e('Install Module ZIP'); ?></a>

            <h3 class="panel-title"><?php _e('Installed Modules', 'grand-media'); ?></h3>
        </div>
        <div class="panel-body" id="gmedia-msg-panel"></div>
        <div class="panel-body">
            <?php
            // installed modules
            if(!empty($modules['in'])) {
                foreach($modules['in'] as $module) {
                    $module['screenshot_url'] = $module['module_url'] . '/screenshot.png';
                    $module['mclass']         = ' module-' . $module['type'] . ' module-' . $module['status'];
                    if($module['update']) {
                        $module['mclass'] .= ' module-update';
                    }

                    include(dirname(__FILE__) . '/tpl/module-item.php');

                }
            }
            ?>
        </div>
    </div>

    <?php if(!empty($modules['out'])) { ?>
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <h3 class="panel-title"><?php _e('Not Installed Modules', 'grand-media'); ?></h3>
            </div>
            <div class="panel-body" id="gmedia-msg-panel"></div>
            <div class="panel-body">
                <?php
                $out_dirpath = dirname($gmGallery->options['modules_xml']);
                foreach($modules['out'] as $module) {
                    $module['mclass']         = ' module-' . $module['type'] . ' module-' . $module['status'];
                    $module['screenshot_url'] = $out_dirpath . '/' . $module['name'] . '.png';

                    include(dirname(__FILE__) . '/tpl/module-item.php');

                } ?>
            </div>
        </div>
    <?php }
    wp_nonce_field('GmediaGallery');
    ?>
</div>

<?php if($gmCore->caps['gmedia_module_manage']) {
    include(dirname(__FILE__) . '/tpl/modal-modulezip.php');
} ?>

