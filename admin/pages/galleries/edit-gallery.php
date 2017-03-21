<?php
/**
 * Gmedia Gallery Edit
 */

// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$term_id              = $gmCore->_get('edit_term');
$gmedia_url           = add_query_arg(array('edit_term' => $term_id), $gmProcessor->url);
$gmedia_user_options  = $gmProcessor->user_options;
$gmedia_term_taxonomy = $gmProcessor->taxonomy;
$taxterm              = $gmProcessor->taxterm;

if(!gm_user_can("{$taxterm}_manage")){
    die('-1');
}

$term_id = (int)$term_id;
$term    = $gmDB->get_term($term_id);

if(empty($term) || is_wp_error($term)){
    $term_id = 0;
    $term    = new stdClass();
}
gmedia_gallery_more_data($term);

$gmedia_modules = get_gmedia_modules(false);

$default_options = array();
$presets         = false;
$default_preset  = array();
$load_preset     = array();
$global_preset   = array();

$gmedia_filter = gmedia_gallery_query_data($term->meta['_query']);

/**
 * @var $module_path
 */
if($term->module['name']){
    $presets = $gmDB->get_terms('gmedia_module', array('status' => $term->module['name']));
    foreach($presets as $i => $preset){
        if('[' . $term->module['name'] . ']' == $preset->name){
            if(0 === (int)$preset->global){
                $global_preset            = maybe_unserialize($preset->description);
                $global_preset['term_id'] = $preset->term_id;
                $global_preset['name']    = $preset->name;
                unset($presets[ $i ]);
            } elseif($user_ID == $preset->global){
                $default_preset            = maybe_unserialize($preset->description);
                $default_preset['term_id'] = $preset->term_id;
                $default_preset['name']    = $preset->name;
                unset($presets[ $i ]);
            }
        }
        if((int)$preset->term_id == (int)$gmCore->_get('preset', 0)){
            $load_preset            = maybe_unserialize($preset->description);
            $load_preset['term_id'] = $preset->term_id;
            $load_preset['name']    = $preset->name;
        }
    }

    if(isset($gmedia_modules['in'][ $term->module['name'] ])){
        extract($gmedia_modules['in'][ $term->module['name'] ]);

        /**
         * @var $module_info
         * @var $default_options
         * @var $options_tree
         */
        if(is_file($module_path . '/index.php') && is_file($module_path . '/settings.php')){
            /** @noinspection PhpIncludeInspection */
            include($module_path . '/index.php');
            /** @noinspection PhpIncludeInspection */
            include($module_path . '/settings.php');

            if(!empty($global_preset)){
                $default_options = $gmCore->array_replace_recursive($default_options, $global_preset);
            }
            if(!empty($default_preset)){
                $default_options = $gmCore->array_replace_recursive($default_options, $default_preset);
            }
        } else{
            $alert[] = sprintf(__('Module `%s` is broken. Choose another module from the list.'), $term->module['name']);
        }
    } else{
        $alert[] = sprintf(__('Can\'t get module with name `%s`. Choose module from the list.'), $term->module['name']);
    }
} else{
    $alert[] = __('Module is not selected for this gallery. Choose module from the list.');
}

if(!empty($alert)){
    echo $gmCore->alert('danger', $alert);
}

if(!empty($load_preset)){
    $term->meta['_settings'][ $term->module['name'] ] = $gmCore->array_replace_recursive($term->meta['_settings'][ $term->module['name'] ], $load_preset);
    echo $gmCore->alert('info', sprintf(__('Preset `%s` loaded. To apply it for current gallery click Save button'), $load_preset['name']));
}
if(!empty($term->meta['_settings'][ $term->module['name'] ])){
    $gallery_settings = $gmCore->array_replace_recursive($default_options, $term->meta['_settings'][ $term->module['name'] ]);
} else{
    $gallery_settings = $default_options;
}

/** @noinspection PhpIncludeInspection */
include_once(GMEDIA_ABSPATH . 'inc/module.options.php');

$reset_settings = $gmCore->array_diff_keyval_recursive($default_options, $gallery_settings, true);

do_action('gmedia_gallery_before_panel');
?>

<div class="panel panel-default panel-fixed-header">

    <?php
    include(dirname(__FILE__) . '/tpl/gallery-panel-heading.php');

    include(dirname(__FILE__) . "/tpl/{$taxterm}-edit-item.php");
    ?>

</div>

<?php
do_action("gmedia_term_{$taxterm}_after_panel", $term);
do_action('gmedia_gallery_after_panel');

include(dirname(__FILE__) . "/tpl/choose-module.php");
include(GMEDIA_ABSPATH . 'admin/tpl/modal-share.php');
?>
<div class="modal fade gmedia-modal" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="btn-toolbar pull-right" style="margin-top:-4px;">
                    <button type="button" class="btn btn-primary"><?php _e('Submit', 'grand-media'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'grand-media'); ?></button>
                </div>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

