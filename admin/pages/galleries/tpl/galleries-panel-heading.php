<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Panel heading for terms
 * @var $gmedia_term_taxonomy
 * @var $gmedia_terms_pager
 * @var $gmProcessor
 */
?>
<div class="panel-heading-fake"></div>
<div class="panel-heading clearfix" style="padding-bottom:2px;">
    <div class="pull-right" style="margin-bottom:3px;">
        <div class="clearfix">
            <?php include(GMEDIA_ABSPATH . 'admin/tpl/search-form.php'); ?>

            <div class="btn-toolbar pull-right" style="margin-bottom:4px; margin-left:4px;">
                <a title="<?php _e('More Screen Settings', 'grand-media'); ?>" class="show-settings-link pull-right btn btn-default btn-xs"><span class="glyphicon glyphicon-cog"></span></a>
            </div>
        </div>

        <?php echo $gmedia_terms_pager; ?>

        <div class="spinner"></div>
    </div>

    <div class="btn-toolbar pull-left" style="margin-bottom:7px;">
        <div class="btn-group gm-checkgroup" id="cb_global-btn">
                <span class="btn btn-default active"><input class="doaction" id="cb_global"
                                                            data-group="cb_object" type="checkbox"/></span>
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

        <div class="btn-group" style="margin-right:20px;">
            <a class="btn btn-primary" href="#chooseModuleModal" data-toggle="modal"><?php _e('Create Gallery', 'grand-media'); ?></a>
        </div>

        <?php if(!empty($gmedia_terms)){ ?>
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
                    <li class="<?php echo $rel_selected_show; ?>">
                        <a href="#changeModuleModal" data-toggle="modal"><?php _e('Change Module/Preset for Galleries', 'grand-media'); ?></a>
                    </li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('terms_delete')? '' : ' disabled'); ?>">
                        <a href="<?php echo wp_nonce_url($gmCore->get_admin_url(array('do_gmedia_terms' => 'delete',
                                                                                      'ids'             => 'selected'
                                                                                ), array('filter')), 'gmedia_delete', '_wpnonce_delete') ?>" class="gmedia-delete" data-confirm="<?php _e("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"); ?>"><?php _e('Delete Selected Items', 'grand-media'); ?></a>
                    </li>
                    <?php do_action('gmedia_galleries_action_list'); ?>
                </ul>
            </div>

            <?php
            do_action('gmedia_galleries_btn_toolbar');

            $filter_selected = $gmCore->_req('filter');
            $filter_selected_arg = $filter_selected? false : 'selected';
            ?>
            <form class="btn-group" id="gm-selected-btn" name="gm-selected-form" action="<?php echo add_query_arg(array('filter' => $filter_selected_arg), $gmedia_url); ?>" method="post">
                <button type="submit" class="btn btn<?php echo ('selected' == $filter_selected)? '-success' : '-info' ?>"><?php printf(__('%s selected', 'grand-media'), '<span id="gm-selected-qty">' . count($gmProcessor->selected_items) . '</span>'); ?></button>
                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
                <input type="hidden" id="gm-selected" data-userid="<?php echo $user_ID; ?>" data-key="<?php echo GmediaProcessor_Galleries::$cookie_key; ?>" name="selected_items" value="<?php echo implode(',', $gmProcessor->selected_items); ?>"/>
                <ul class="dropdown-menu" role="menu">
                    <li><a id="gm-selected-show" href="#show"><?php
                            if(!$filter_selected){
                                _e('Show only selected items', 'grand-media');
                            } else{
                                _e('Show all gmedia items', 'grand-media');
                            }
                            ?></a></li>
                    <li><a id="gm-selected-clear" href="#clear"><?php _e('Clear selected items', 'grand-media'); ?></a></li>
                </ul>
            </form>
        <?php } ?>

    </div>
</div>

