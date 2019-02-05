<?php
/**
 * Gmedia Modules
 */

// don't load directly
if( !defined('ABSPATH')){
    die('-1');
}

global $gmCore, $gmProcessor, $gmGallery, $gmDB;

$gmedia_url = $gmProcessor->url;
$modules    = $gmProcessor->modules;
$tags       = array();
if(!empty($modules['xml'])){
    foreach($modules['xml'] as $module){
        $tags = array_merge($tags, $module['tags']);
    }
}
if(!empty($tags)){
    $tags = array_unique($tags);
    sort($tags);
}
//echo '<pre style="max-height: 500px; overflow:auto;">' . print_r($modules, true) . '</pre>';

if(isset($modules['error'])){
    echo $gmCore->alert('danger', $modules['error']);
}

?>
<div id="gmedia_modules">
    <div id="gmedia_modules_wrapper" data-update="<?php echo $gmGallery->options['modules_update'] ?>">
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <div class="clearfix">
                    <a href="#installModuleModal" class="btn btn-primary pull-right<?php echo current_user_can('manage_options')? '' : ' disabled'; ?>" data-toggle="modal"><?php _e('Install Module ZIP'); ?></a>

                    <div class="btn-group pull-left filter-modules" style="margin-right: 10px;">
                        <button type="button" data-filter="collection" class="btn btn-primary"><?php _e('All Modules', 'grand-media'); ?> <span class="badge badge-error gm-module-count-<?php echo $gmGallery->options['modules_update']; ?>" title="<?php _e( 'Modules Updates', 'grand-media' ); ?>"><?php echo $gmGallery->options['modules_update']; ?></span></button>
                        <button type="button" data-filter="not-installed" class="btn btn-default"><?php _e('New Modules', 'grand-media'); ?> <span class="badge badge-success gm-module-count-<?php echo $gmGallery->options['modules_new']; ?>" title="<?php _e( 'New Modules', 'grand-media' ); ?>"><?php echo $gmGallery->options['modules_new']; ?></span></button>
                        <button type="button" data-filter="tag-trend" class="btn btn-default"><?php _e('Trends', 'grand-media'); ?></button>
                    </div>

                    <?php if(!empty($tags)){ ?>
                    <div class="btn-group pull-left">
                        <button type="button" class="btn btn-default" onclick="jQuery(this).toggleClass('active');" data-toggle="collapse" data-target="#collapseFeatures" aria-expanded="false" aria-controls="collapseFeatures">
                            Feature Filters <span class="caret"></span>
                        </button>
                    </div>
                    <?php } ?>
                </div>
                <?php if(!empty($tags)){ ?>
                <div class="collapse" id="collapseFeatures">
                    <div class="filter-modules" style="padding-top: 10px;">
                    <?php foreach($tags as $tag){ ?>
                        <span style="cursor: pointer;" data-filter="tag-<?php echo sanitize_key($tag); ?>" class="label label-default"><?php echo strtoupper($tag); ?></span>
                    <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
            <div class="panel-body" id="gmedia-msg-panel"></div>
            <div class="panel-body modules-body">
                <?php
                // installed modules
                if( !empty($modules['in'])){
                    foreach($modules['in'] as $module){
                        $module['screenshot_url'] = $module['module_url'] . '/screenshot.png';
                        $module['mclass']         = ' module-filtered module-collection module-installed';
                        if($module['update']){
                            $module['mclass'] .= ' module-update';
                        }
                        foreach($module['tags'] as $tag){
                            $module['mclass'] .= ' module-tag-' . sanitize_key($tag);
                        }

                        include(dirname(__FILE__) . '/tpl/module-item.php');

                    }
                }

                if( !empty($modules['out'])){ ?>
                    <?php
                    //$out_dirpath = dirname($gmGallery->options['modules_xml']);
                    $out_dirpath = 'https://codeasily.com/gmedia_modules';
                    foreach($modules['out'] as $module){
                        $module['mclass'] = ' module-filtered module-collection module-not-installed';
                        if($module['update']){
                            $module['mclass'] .= ' module-update';
                        }
                        foreach($module['tags'] as $tag){
                            $module['mclass'] .= ' module-tag-' . sanitize_key($tag);
                        }
                        $module['screenshot_url'] = $out_dirpath . '/' . $module['name'] . '.png';

                        include(dirname(__FILE__) . '/tpl/module-item.php');

                    }
                }
                wp_nonce_field('GmediaGallery');
                ?>
                <div class="media nomodules nomodule-not-installed">
                    <h4 class="media-heading"><?php _e('No modules to show', 'grand-media'); ?></h4>
                </div>
                <div class="media nomodules nomodule-tag">
                    <h4 class="media-heading"><?php _e('No modules to show', 'grand-media'); ?></h4>
                </div>
            </div>
        </div>

    </div>
</div>

<?php if($gmCore->caps['gmedia_module_manage']){
    include(dirname(__FILE__) . '/tpl/modal-modulezip.php');
} ?>

