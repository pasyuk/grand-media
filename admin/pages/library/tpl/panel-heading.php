<?php
/**
 * @var $gmCore
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

                <?php if(!$gmProcessor->edit_mode) { ?>
                    <div class="btn-group pull-right">
                        <a title="<?php _e('Show as Grid', 'grand-media'); ?>" href="<?php echo gm_get_admin_url(array('display_mode' => 'grid')); ?>" class="btn btn<?php echo ($gmedia_user_options['display_mode_gmedia'] == 'grid')? '-primary active' : '-default'; ?> btn-xs"><span class="glyphicon glyphicon-th"></span></a>
                        <a title="<?php _e('Show as List', 'grand-media'); ?>" href="<?php echo gm_get_admin_url(array('display_mode' => 'list')); ?>" class="btn btn<?php echo ($gmedia_user_options['display_mode_gmedia'] == 'list')? '-primary active' : '-default'; ?> btn-xs"><span class="glyphicon glyphicon-th-list"></span></a>
                    </div>
                    <?php if($gmedia_user_options['display_mode_gmedia'] == 'grid') { ?>
                        <a title="<?php _e('Thumbnails Fit/Fill Cell', 'grand-media'); ?>" href="<?php echo gm_get_admin_url(array('grid_cell_fit' => 'toggle')); ?>" class="fit-thumbs pull-right btn btn<?php echo ($gmedia_user_options['grid_cell_fit_gmedia'] == 'true')? '-success active' : '-default'; ?> btn-xs"><span class="glyphicon glyphicon-eye-open"></span></a>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <?php echo $gmedia_pager; ?>

    </div>
    <div class="btn-toolbar pull-left" style="margin-bottom:7px;">
        <div class="btn-group gm-checkgroup" id="cb_global-btn">
            <span class="btn btn-default active"><input class="doaction" id="cb_global" data-group="cb_media-object" type="checkbox"/></span>
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
            <ul class="dropdown-menu" role="menu">
                <li><a data-select="total" href="#"><?php _e('All', 'grand-media'); ?></a></li>
                <li><a data-select="none" href="#"><?php _e('None', 'grand-media'); ?></a></li>
                <li class="divider"></li>
                <li><a data-select="image" href="#"><?php _e('Images', 'grand-media'); ?></a></li>
                <li><a data-select="audio" href="#"><?php _e('Audio', 'grand-media'); ?></a></li>
                <li><a data-select="video" href="#"><?php _e('Video', 'grand-media'); ?></a></li>
                <li class="divider"></li>
                <li><a data-select="reverse" href="#" title="<?php _e('Reverse only visible items', 'grand-media'); ?>"><?php _e('Reverse', 'grand-media'); ?></a></li>
            </ul>
        </div>

        <div class="btn-group">
            <?php // todo: !!!!!
            $curr_mime = explode(',', $gmCore->_get('mime_type', 'total')); ?>
            <?php if($gmDB->filter) { ?>
                <a class="btn btn-warning" title="<?php _e('Reset Filter', 'grand-media'); ?>" rel="total" href="<?php echo $gmedia_url; ?>"><?php _e('Reset Filter', 'grand-media'); ?></a>
            <?php } else { ?>
                <button type="button" class="btn btn-default" data-toggle="dropdown"><?php _e('Filter', 'grand-media'); ?></button>
            <?php } ?>
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <?php if(gm_user_can('show_others_media')) { ?>
                    <li role="presentation" class="dropdown-header"><?php _e('FILTER BY AUTHOR', 'grand-media'); ?></li>
                    <li class="gmedia_author">
                        <a href="#libModal" data-modal="filter_author" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Choose authors', 'grand-media'); ?></a>
                    </li>
                <?php } ?>

                <?php
                $gm_qty_badge = array(
                        'total'       => '',
                        'image'       => '',
                        'audio'       => '',
                        'video'       => '',
                        'text'        => '',
                        'application' => '',
                        'other'       => ''
                );

                foreach($gmedia_count as $key => $value) {
                    $gm_qty_badge[$key] = '<span class="badge pull-right">' . (int)$value . '</span>';
                }
                ?>
                <li role="presentation" class="dropdown-header"><?php _e('TYPE', 'grand-media'); ?></li>
                <li class="total<?php echo in_array('total', $curr_mime)? ' active' : ''; ?>"><a rel="total" href="<?php echo gm_get_admin_url(array(), array('mime_type', 'pager')); ?>"><?php echo $gm_qty_badge['total'] . __('All', 'grand-media'); ?></a></li>
                <li class="image<?php echo (in_array('image', $curr_mime)? ' active' : '') . ($gmedia_count['image']? '' : ' disabled'); ?>"><a rel="image" href="<?php echo gm_get_admin_url(array('mime_type' => 'image'), array('pager')); ?>"><?php echo $gm_qty_badge['image'] . __('Images', 'grand-media'); ?></a></li>
                <li class="audio<?php echo (in_array('audio', $curr_mime)? ' active' : '') . ($gmedia_count['audio']? '' : ' disabled'); ?>"><a rel="audio" href="<?php echo gm_get_admin_url(array('mime_type' => 'audio'), array('pager')); ?>"><?php echo $gm_qty_badge['audio'] . __('Audio', 'grand-media'); ?></a></li>
                <li class="video<?php echo (in_array('video', $curr_mime)? ' active' : '') . ($gmedia_count['video']? '' : ' disabled'); ?>"><a rel="video" href="<?php echo gm_get_admin_url(array('mime_type' => 'video'), array('pager')); ?>"><?php echo $gm_qty_badge['video'] . __('Video', 'grand-media'); ?></a></li>
                <li class="application<?php echo ((in_array('application', $curr_mime) || in_array('text', $curr_mime))? ' active' : '') . ($gmedia_count['other']? '' : ' disabled'); ?>"><a rel="application" href="<?php echo gm_get_admin_url(array('mime_type' => 'application,text'), array('pager')); ?>"><?php echo $gm_qty_badge['other'] . __('Other', 'grand-media'); ?></a></li>

                <li role="presentation" class="dropdown-header"><?php _e('COLLECTIONS', 'grand-media'); ?></li>
                <li class="filter_categories<?php echo isset($gmDB->filter_tax['gmedia_category'])? ' active' : ''; ?>"><a href="#libModal" data-modal="filter_categories" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Categories', 'grand-media'); ?></a></li>
                <li class="filter_albums<?php echo isset($gmDB->filter_tax['gmedia_album'])? ' active' : ''; ?>"><a href="#libModal" data-modal="filter_albums" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Albums', 'grand-media'); ?></a></li>
                <li class="filter_tags<?php echo isset($gmDB->filter_tax['gmedia_tag'])? ' active' : ''; ?>"><a href="#libModal" data-modal="filter_tags" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Tags', 'grand-media'); ?></a></li>
                <?php do_action('gmedia_filter_list'); ?>
            </ul>
        </div>

        <div class="btn-group">
            <?php if(!$gmProcessor->edit_mode) {
                $action_args    = array('edit_mode' => 1);
                $edit_mode_href = gm_get_admin_url($action_args);
                $action_args2   = array('edit_mode' => 1, 'filter' => 'selected', 'pager' => false, 's' => false);
                $edit_mode_data = 'data-href="' . $edit_mode_href . '" data-href_sel="' . gm_get_admin_url($action_args2) . '"';
            } else {
                $edit_mode_href = gm_get_admin_url(array(), array('edit_mode'));
                $edit_mode_data = '';
            } ?>
            <?php if(gm_user_can('edit_media')) { ?>
                <a class="btn btn-default edit-mode-link" title="<?php _e('Toggle Edit Mode', 'grand-media'); ?>" href="<?php echo $edit_mode_href; ?>" <?php echo $edit_mode_data; ?>><?php _e('Action', 'grand-media'); ?></a>
            <?php } else { ?>
                <button type="button" class="btn btn-default"><?php _e('Action', 'grand-media'); ?></button>
            <?php } ?>
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
            <?php
            $rel_selected_show = 'rel-selected-show';
            $rel_selected_hide = 'rel-selected-hide';
            ?>
            <ul class="dropdown-menu" role="menu">
                <?php if(!$gmProcessor->edit_mode) { ?>
                    <li class="<?php echo gm_user_can('edit_media')? '' : 'disabled'; ?>">
                        <a class="edit-mode-link" href="<?php echo $edit_mode_href; ?>" <?php echo $edit_mode_data; ?>><?php _e('Enter Edit Mode', 'grand-media'); ?></a>
                    </li>
                <?php } else { ?>
                    <li><a href="<?php echo $edit_mode_href; ?>"><?php _e('Exit Edit Mode', 'grand-media'); ?></a></li>
                <?php } ?>
                <li class="<?php echo $rel_selected_show . (gm_user_can('edit_media')? '' : ' disabled'); ?>">
                    <a href="#libModal" data-modal="batch_edit" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Batch Edit', 'grand-media'); ?></a></li>

                <li class="divider"></li>
                <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                    <a href="#libModal" data-modal="assign_album" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Move to Album...', 'grand-media'); ?></a>
                </li>
                <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                    <a href="#libModal" data-modal="assign_category" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Assign Categories...', 'grand-media'); ?></a>
                </li>
                <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                    <a href="#libModal" data-modal="unassign_category" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Unassign Categories...', 'grand-media'); ?></a>
                </li>
                <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                    <a href="#libModal" data-modal="add_tags" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Add Tags...', 'grand-media'); ?></a></li>
                <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                    <a href="#libModal" data-modal="delete_tags" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Delete Tags...', 'grand-media'); ?></a>
                </li>
                <li class="<?php echo $rel_selected_show . (gm_user_can('delete_media')? '' : ' disabled'); ?>">
                    <a href="<?php echo wp_nonce_url(gm_get_admin_url(array('delete' => 'selected'), array('filter')), 'gmedia_delete') ?>" class="gmedia-delete" data-confirm="<?php _e("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"); ?>"><?php _e('Delete Selected Items', 'grand-media'); ?></a>
                </li>

                <li class="divider <?php echo $rel_selected_show; ?>"></li>
                <li class="<?php echo $rel_selected_show . (gm_user_can('edit_media')? '' : ' disabled'); ?>">
                    <a href="<?php echo wp_nonce_url(gm_get_admin_url(array('update_meta' => 'selected'), array()), 'gmedia_update_meta') ?>" class="gmedia-update"><?php _e('Update Metadata in Database', 'grand-media'); ?></a>
                </li>

                <li class="dropdown-header <?php echo $rel_selected_hide; ?>"><span><?php _e("Select items to see more actions", "grand-media"); ?></span></li>
                <?php do_action('gmedia_action_list'); ?>
            </ul>
        </div>

        <?php
        $filter_stack     = $gmCore->_req('stack');
        $filter_stack_arg = $filter_stack? false : 'show';

        $filter_selected     = $gmCore->_req('filter');
        $filter_selected_arg = $filter_selected? false : 'selected';
        ?>
        <form class="btn-group" id="gm-stack-btn" name="gm-stack-form" action="<?php echo add_query_arg(array('stack' => $filter_stack_arg, 'filter' => $filter_selected), $gmedia_url); ?>" method="post">
            <button type="submit" class="btn btn<?php echo ('show' == $filter_stack)? '-success' : '-info' ?>"><?php printf(__('%s in Stack', 'grand-media'), '<span id="gm-stack-qty">' . count($gmProcessor->stack_items) . '</span>'); ?></button>
            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
            <input type="hidden" id="gm-stack" data-userid="<?php echo $user_ID; ?>" data-key="library_stack" name="stack_items" value="<?php echo implode(',', $gmProcessor->stack_items); ?>"/>
            <ul class="dropdown-menu" role="menu">
                <li><a id="gm-stack-show" href="#show"><?php
                        if(!$filter_stack) {
                            _e('Show Stack', 'grand-media');
                        } else {
                            _e('Show Library', 'grand-media');
                        }
                        ?></a></li>
                <li><a id="gm-stack-clear" href="#clear"><?php _e('Clear Stack', 'grand-media'); ?></a></li>
                <li class="<?php echo gm_user_can('gallery_manage')? '' : 'disabled'; ?>">
                    <a href="#libModal" data-modal="quick_gallery_stack" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Quick Gallery from Stack', 'grand-media'); ?></a>
                </li>
            </ul>
        </form>

        <form class="btn-group" id="gm-selected-btn" name="gm-selected-form" action="<?php echo add_query_arg(array('stack' => $filter_stack, 'filter' => $filter_selected_arg), $gmedia_url); ?>" method="post">
            <button type="submit" class="btn btn<?php echo ('selected' == $filter_selected)? '-success' : '-info' ?>"><?php printf(__('%s selected', 'grand-media'), '<span id="gm-selected-qty">' . count($gmProcessor->selected_items) . '</span>'); ?></button>
            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
            <input type="hidden" id="gm-selected" data-userid="<?php echo $user_ID; ?>" data-key="library" name="selected_items" value="<?php echo implode(',', $gmProcessor->selected_items); ?>"/>
            <ul class="dropdown-menu" role="menu">
                <li><a id="gm-selected-show" href="#show"><?php
                        if(!$filter_selected) {
                            _e('Show only selected items', 'grand-media');
                        } else {
                            _e('Show all gmedia items', 'grand-media');
                        }
                        ?></a></li>
                <li><a id="gm-selected-clear" href="#clear"><?php _e('Clear selected items', 'grand-media'); ?></a></li>
                <li><a id="gm-stack-in" href="#stack_add"><?php _e('Add selected items to Stack', 'grand-media'); ?></a></li>
                <li><a id="gm-stack-out" href="#stack_remove"><?php _e('Remove selected items from Stack', 'grand-media'); ?></a></li>
                <li class="<?php echo gm_user_can('gallery_manage')? '' : 'disabled'; ?>">
                    <a href="#libModal" data-modal="quick_gallery" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Quick Gallery from Selected', 'grand-media'); ?></a>
                </li>
            </ul>
        </form>

    </div>

</div>
