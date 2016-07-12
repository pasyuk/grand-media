<?php // don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Alert capability
 */
?>
<div class="alert alert-warning alert-dismissible" role="alert" style="margin-bottom:0">
    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"><?php _e('Close', 'grand-media'); ?></span></button>
    <strong><?php _e('Info:', 'grand-media'); ?></strong> <?php _e('You are not allowed to add new terms', 'grand-media'); ?>
</div>

