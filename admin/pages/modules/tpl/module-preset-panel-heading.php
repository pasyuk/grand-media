<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Panel heading for term
 * @var $gmCore
 * @var $term_id
 * @var $term
 */
?>
<div class="panel-heading-fake"></div>
<div class="panel-heading clearfix">
    <div class="btn-toolbar pull-left">
        <a class="btn btn-default pull-left" style="margin-right:20px;" href="<?php echo remove_query_arg(array('preset_module', 'preset'), wp_get_referer()); ?>"><?php _e('Go Back', 'grand-media'); ?></a>

        <?php if($term_id){ ?>
            <div class="btn-group">
                <a class="btn btn-default" href="#"><?php _e('Action', 'grand-media'); ?></a>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="<?php echo add_query_arg(array('page' => 'GrandMedia_Galleries', 'gallery_module' => $term->module['name'], 'preset' => $term->term_id), admin_url('admin.php')); ?>"><?php _e('Create Gallery with this preset', 'grand-media'); ?></a></li>
                </ul>
            </div>

            <a class="btn btn-info pull-left" style="margin-left:20px;" href="<?php echo $gmCore->get_admin_url(array('preset_module' => $term->module['name']), array('preset')); ?>"><?php printf(__('New %s Preset', 'grand-media'), $term->module['info']['title']); ?></a>
        <?php } ?>
    </div>
    <div class="spinner"></div>
</div>
