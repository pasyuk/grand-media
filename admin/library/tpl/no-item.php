<div class="list-group-item">
    <div class="well well-lg text-center">
        <h4><?php _e('No items to show.', 'grand-media'); ?></h4>
        <?php if(gm_user_can('upload')) { ?>
            <p>
                <a href="<?php echo admin_url('admin.php?page=GrandMedia_AddMedia'); ?>" class="btn btn-success">
                    <span class="glyphicon glyphicon-plus"></span> <?php _e('Add Media', 'grand-media'); ?>
                </a>
            </p>
        <?php } ?>
    </div>
</div>
