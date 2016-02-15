<?php
/**
 * Panel heading for terms
 *
 * @var $gmedia_term_taxonomy
 * @var $gmedia_terms_pager
 * @var $gmProcessor
 */
?>
<div class="panel-heading-fake"></div>
<div class="panel-heading clearfix">

    <?php if('gmedia_category' != $gmedia_term_taxonomy) {

        include(GMEDIA_ABSPATH . 'admin/tpl/search-form.php');

        echo $gmedia_terms_pager;

    } ?>

    <div class="btn-toolbar pull-left">
        <?php if('gmedia_category' != $gmedia_term_taxonomy) { ?>
            <div class="btn-group gm-checkgroup" id="cb_global-btn">
                    <span class="btn btn-default active"><input class="doaction" id="cb_global"
                                                                data-group="cb_term-object" type="checkbox"/></span>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a data-select="total" href="#"><?php _e('All', 'grand-media'); ?></a></li>
                    <li><a data-select="none" href="#"><?php _e('None', 'grand-media'); ?></a></li>
                    <li class="divider"></li>
                    <li><a data-select="reverse" href="#" title="<?php _e('Reverse only visible items', 'grand-media'); ?>"><?php _e('Reverse', 'grand-media'); ?></a></li>
                </ul>
            </div>
        <?php } ?>

        <?php
        switch($gmedia_term_taxonomy) {
            case 'gmedia_filter':
                $button_title = __('Show: Filters', 'grand-media');
            break;
            case 'gmedia_tag':
                $button_title = __('Show: Tags', 'grand-media');
            break;
            case 'gmedia_category':
                $button_title = __('Show: Categories', 'grand-media');
            break;
            case 'gmedia_album':
            default:
                $button_title = __('Show: Albums', 'grand-media');
            break;
        }
        ?>
        <div class="btn-group" style="margin-right:20px;">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <?php echo $button_title ?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li<?php echo ('gmedia_album' == $gmedia_term_taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_album'), $gmedia_url); ?>"><?php _e('Albums', 'grand-media'); ?></a></li>
                <li<?php echo ('gmedia_tag' == $gmedia_term_taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_tag'), $gmedia_url); ?>"><?php _e('Tags', 'grand-media'); ?></a></li>
                <li<?php echo ('gmedia_category' == $gmedia_term_taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_category'), $gmedia_url); ?>"><?php _e('Categories', 'grand-media'); ?></a></li>
                <li class="divider"></li>
                <li<?php echo ('gmedia_filter' == $gmedia_term_taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_filter'), $gmedia_url); ?>"><?php _e('Custom Filters', 'grand-media'); ?></a></li>
            </ul>
        </div>

        <?php if(('gmedia_filter' == $gmedia_term_taxonomy) && gm_user_can('filter_manage')) { ?>
            <a class="btn btn-success pull-left" href="<?php echo add_query_arg(array('edit_item' => '0'), $gmedia_url); ?>"><?php _e('Create Filter', 'grand-media'); ?></a>
        <?php } ?>

        <?php if(('gmedia_category' != $gmedia_term_taxonomy) && !empty($gmedia_terms)) { ?>
            <div class="btn-group">
                <a class="btn btn-default" href="#"><?php _e('Action', 'grand-media'); ?></a>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                </button>
                <?php
                $rel_selected_show = 'rel-selected-show';
                $rel_selected_hide = 'rel-selected-hide';
                ?>
                <ul class="dropdown-menu" role="menu">
                    <li class="dropdown-header <?php echo $rel_selected_hide; ?>"><span><?php _e("Select items to see more actions", "grand-media"); ?></span></li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('terms_delete')? '' : ' disabled'); ?>">
                        <a href="<?php echo wp_nonce_url($gmCore->get_admin_url(array('delete' => 'selected'), array('filter')), 'gmedia_delete') ?>" class="gmedia-delete" data-confirm="<?php _e("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"); ?>"><?php _e('Delete Selected Items', 'grand-media'); ?></a>
                    </li>
                    <?php do_action('gmedia_terms_action_list'); ?>
                </ul>
            </div>

            <?php
            $filter_selected     = $gmCore->_req('filter');
            $filter_selected_arg = $filter_selected? false : 'selected';
            ?>
            <form class="btn-group" id="gm-selected-btn" name="gm-selected-form" action="<?php echo add_query_arg(array('term' => $gmedia_term_taxonomy, 'filter' => $filter_selected_arg), $gmedia_url); ?>" method="post">
                <button type="submit" class="btn btn<?php echo ('selected' == $filter_selected)? '-success' : '-info' ?>"><?php printf(__('%s selected', 'grand-media'), '<span id="gm-selected-qty">' . count($gmProcessor->selected_items) . '</span>'); ?></button>
                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
                <input type="hidden" id="gm-selected" data-userid="<?php echo $user_ID; ?>" data-key="<?php echo $gmedia_term_taxonomy; ?>" name="selected_items" value="<?php echo implode(',', $gmProcessor->selected_items); ?>"/>
                <ul class="dropdown-menu" role="menu">
                    <li><a id="gm-selected-show" href="#show"><?php
                            if(!$filter_selected) {
                                _e('Show only selected items', 'grand-media');
                            } else {
                                _e('Show all gmedia items', 'grand-media');
                            }
                            ?></a></li>
                    <li><a id="gm-selected-clear" href="#clear"><?php _e('Clear selected items', 'grand-media'); ?></a></li>
                    <?php /*
                    <li class="<?php echo gm_user_can('gallery_manage')? '' : 'disabled'; ?>">
                        <a href="#libModal" data-modal="quick_gallery" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Quick Gallery from Selected', 'grand-media'); ?></a>
                    </li>
                    <?php */ ?>
                </ul>
            </form>
        <?php } ?>

    </div>
</div>

