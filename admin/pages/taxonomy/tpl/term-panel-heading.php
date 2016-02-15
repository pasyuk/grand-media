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
        <div class="btn-group" style="margin-right:20px;">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <?php _e('Return to') ?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li<?php echo ('gmedia_album' == $gmedia_term_taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_album'), $gmedia_url); ?>"><?php _e('Albums', 'grand-media'); ?></a></li>
                <li<?php echo ('gmedia_tag' == $gmedia_term_taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_tag'), $gmedia_url); ?>"><?php _e('Tags', 'grand-media'); ?></a></li>
                <li<?php echo ('gmedia_category' == $gmedia_term_taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_category'), $gmedia_url); ?>"><?php _e('Categories', 'grand-media'); ?></a></li>
                <li class="divider"></li>
                <li<?php echo ('gmedia_filter' == $gmedia_term_taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_filter'), $gmedia_url); ?>"><?php _e('Custom Filters', 'grand-media'); ?></a></li>
            </ul>
        </div>

        <?php if($term_id) { ?>
            <div class="btn-group">
                <a class="btn btn-default" href="#"><?php _e('Action', 'grand-media'); ?></a>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <?php $taxkey = ('gmedia_filter' == $gmedia_term_taxonomy)? 'custom_filter' :  $taxterm . '__in'; ?>
                    <li><a href="<?php echo add_query_arg(array('page' => 'GrandMedia', $taxkey => $term->term_id), admin_url('admin.php')); ?>"><?php _e('Show in Gmedia Library', 'grand-media'); ?></a></li>
                </ul>
            </div>
        <?php } ?>
    </div>

</div>
