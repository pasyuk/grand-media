<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}
global $gmCore;
?>
<div class="list-group-item">
    <div class="well well-lg text-center">
        <?php if('duplicates' === $gmCore->_get('gmedia__in')){
            echo '<h4>' . __('No duplicates in Gmedia Library.', 'grand-media') . '</h4>';
        } else{
            echo '<h4>' . __('No items to show.', 'grand-media') . '</h4>';
            if(gm_user_can('upload')) { ?>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=GrandMedia_AddMedia'); ?>" class="btn btn-success">
                        <span class="glyphicon glyphicon-plus"></span> <?php _e('Add Media', 'grand-media'); ?>
                    </a>
                </p>
            <?php }
        } ?>
    </div>
</div>
