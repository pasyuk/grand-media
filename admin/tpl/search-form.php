<?php // don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Search form template
 */
global $gmCore;
?>
<form class="form-inline gmedia-search-form" role="search" method="get">
    <div class="form-group">
        <?php foreach($_GET as $key => $value) {
            if(in_array($key, array('page', 'edit_mode', 'author', 'global', 'mime_type', 'tag_id', 'tag__in', 'cat', 'category__in', 'alb', 'album__in', 'term', 'chromeless', 'post_id', 'tab', 'orderby', 'order', 'number'))) {
                ?>
                <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>"/>
                <?php
            }
        } ?>
        <input id="gmedia-search" class="form-control input-xs" type="text" name="s" placeholder="<?php _e('Search...', 'grand-media'); ?>" value="<?php echo $gmCore->_get('s', ''); ?>"/>
    </div>
    <button type="submit" class="btn btn-default input-xs"><span class="glyphicon glyphicon-search"></span></button>
</form>
