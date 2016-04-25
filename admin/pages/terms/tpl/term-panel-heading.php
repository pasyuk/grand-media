<?php
/**
 * Panel heading for term
 *
 * @var $term_id
 * @var $gmedia_term_taxonomy
 * @var $gmedia_terms_pager
 * @var $gmProcessor
 */
$taxterm    = str_replace('gmedia_', '', $gmedia_term_taxonomy);
?>
<div class="panel-heading clearfix">
    <div class="btn-toolbar pull-left">
        <a class="btn btn-default pull-left" style="margin-right:20px;" href="<?php echo remove_query_arg(array('edit_item'), wp_get_referer()); ?>"><?php _e('Go Back', 'grand-media'); ?></a>

        <?php if($term_id) { ?>
            <div class="btn-group">
                <a class="btn btn-default" href="#"><?php _e('Action', 'grand-media'); ?></a>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <?php $taxkey = $taxterm . '__in'; ?>
                    <li><a href="<?php echo add_query_arg(array('page' => 'GrandMedia', $taxkey => $term->term_id), admin_url('admin.php')); ?>"><?php _e('Show in Gmedia Library', 'grand-media'); ?></a></li>
                </ul>
            </div>
        <?php } ?>
    </div>

</div>
