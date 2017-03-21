<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

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
                <?php if(!$gmProcessor->gmediablank){ ?>
                    <a title="<?php _e('More Screen Settings', 'grand-media'); ?>" class="show-settings-link pull-right btn btn-default btn-xs"><span class="glyphicon glyphicon-cog"></span></a>
                <?php } ?>

                <?php if($gmProcessor->mode != 'edit'){
                    $view                = $gmProcessor->gmediablank? '_frame' : '';
                    $display_mode_gmedia = $gmProcessor->display_mode;
                    if(!$gmProcessor->edit_term && !in_array($gmProcessor->mode, array('select_single', 'select_mutiple'))){ ?>
                        <div class="btn-group pull-right">
                            <a title="<?php _e('Show as Grid', 'grand-media'); ?>" href="<?php echo gm_get_admin_url(array('display_mode' => 'grid')); ?>" class="btn btn<?php echo ($display_mode_gmedia == 'grid')? '-primary active' : '-default'; ?> btn-xs"><span class="glyphicon glyphicon-th"></span></a>
                            <a title="<?php _e('Show as List', 'grand-media'); ?>" href="<?php echo gm_get_admin_url(array('display_mode' => 'list')); ?>" class="btn btn<?php echo ($display_mode_gmedia == 'list')? '-primary active' : '-default'; ?> btn-xs"><span class="glyphicon glyphicon-th-list"></span></a>
                        </div>
                        <?php
                    }
                    if($display_mode_gmedia == 'grid'){ ?>
                        <a title="<?php _e('Thumbnails Fit/Fill Cell', 'grand-media'); ?>" href="<?php echo gm_get_admin_url(array('grid_cell_fit' => 'toggle')); ?>" class="fit-thumbs pull-right btn btn<?php echo ($gmedia_user_options["grid_cell_fit_gmedia{$view}"] == 'true')? '-success active' : '-default'; ?> btn-xs"><span class="glyphicon glyphicon-eye-open"></span></a>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <?php echo $gmedia_pager; ?>

        <div class="spinner"></div>

    </div>
    <div class="btn-toolbar pull-left" style="margin-bottom:7px;">
        <?php if($gmProcessor->mode != 'select_single'){ ?>
            <div class="btn-group gm-checkgroup" id="cb_global-btn">
                <span class="btn btn-default active"><input class="doaction" id="cb_global" data-group="gm-item-check" type="checkbox"/></span>
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
        <?php } ?>

        <div class="btn-group">
            <?php // todo: !!!!!
            $curr_mime = explode(',', $gmCore->_get('mime_type', 'total'));
            if(('show' == $gmCore->_get('stack') || 'selected' == $gmCore->_get('filter')) && isset($gmedia_filter['gmedia__in'])){
                if($gmProcessor->selected_items == $gmedia_filter['gmedia__in'] || $gmProcessor->stack_items == $gmedia_filter['gmedia__in']){
                    unset($gmedia_filter['gmedia__in']);
                }
            }
            ?>
            <?php if(!empty($gmedia_filter)){ ?>
                <a class="btn btn-warning" title="<?php _e('Reset Filter', 'grand-media'); ?>" rel="total" href="<?php echo gm_get_admin_url(array(), array(), $gmedia_url); ?>"><?php _e('Reset Filter', 'grand-media'); ?></a>
            <?php } else{ ?>
                <button type="button" class="btn btn-default" data-toggle="dropdown"><?php _e('Filter', 'grand-media'); ?></button>
            <?php } ?>
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <?php if(gm_user_can('show_others_media')){ ?>
                    <li role="presentation" class="dropdown-header"><?php _e('FILTER BY AUTHOR', 'grand-media'); ?></li>
                    <li class="gmedia_author">
                        <a href="#libModal" data-modal="filter_author" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Choose authors', 'grand-media'); ?></a>
                    </li>
                <?php } ?>

                <?php

                $gm_qty_badge = array('total'       => '',
                                      'image'       => '',
                                      'audio'       => '',
                                      'video'       => '',
                                      'text'        => '',
                                      'application' => '',
                                      'other'       => ''
                );

                foreach($gmedia_count as $key => $value){
                    $gm_qty_badge[ $key ] = '<span class="badge pull-right">' . (int)$value . '</span>';
                }
                ?>
                <li role="presentation" class="dropdown-header"><?php _e('TYPE', 'grand-media'); ?></li>
                <li class="total<?php echo in_array('total', $curr_mime)? ' active' : ''; ?>"><a rel="total" href="<?php echo gm_get_admin_url(array(), array('mime_type', 'pager')); ?>"><?php echo $gm_qty_badge['total'] . __('All', 'grand-media'); ?></a></li>
                <?php if($gmProcessor->mode != 'select_single'){ ?>
                    <li class="image<?php echo (in_array('image', $curr_mime)? ' active' : '') . ($gmedia_count['image']? '' : ' disabled'); ?>"><a rel="image" href="<?php echo gm_get_admin_url(array('mime_type' => 'image'), array('pager')); ?>"><?php echo $gm_qty_badge['image'] . __('Images', 'grand-media'); ?></a></li>
                    <li class="audio<?php echo (in_array('audio', $curr_mime)? ' active' : '') . ($gmedia_count['audio']? '' : ' disabled'); ?>"><a rel="audio" href="<?php echo gm_get_admin_url(array('mime_type' => 'audio'), array('pager')); ?>"><?php echo $gm_qty_badge['audio'] . __('Audio', 'grand-media'); ?></a></li>
                    <li class="video<?php echo (in_array('video', $curr_mime)? ' active' : '') . ($gmedia_count['video']? '' : ' disabled'); ?>"><a rel="video" href="<?php echo gm_get_admin_url(array('mime_type' => 'video'), array('pager')); ?>"><?php echo $gm_qty_badge['video'] . __('Video', 'grand-media'); ?></a></li>
                    <li class="application<?php echo ((in_array('application', $curr_mime) || in_array('text', $curr_mime))? ' active' : '') . ($gmedia_count['other']? '' : ' disabled'); ?>">
                        <a rel="application" href="<?php echo gm_get_admin_url(array('mime_type' => 'application,text'), array('pager')); ?>"><?php echo $gm_qty_badge['other'] . __('Other', 'grand-media'); ?></a></li>
                <?php } ?>

                <li role="presentation" class="dropdown-header"><?php _e('COLLECTIONS', 'grand-media'); ?></li>
                <li class="filter_categories<?php echo isset($gmedia_filter['category__in'])? ' active' : ''; ?>"><a href="#libModal" data-modal="filter_categories" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Categories', 'grand-media'); ?></a></li>
                <?php if(!($gmProcessor->edit_term && 'album' === $gmProcessor->taxterm)){ ?>
                    <li class="filter_albums<?php echo isset($gmedia_filter['album__in'])? ' active' : ''; ?>"><a href="#libModal" data-modal="filter_albums" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Albums', 'grand-media'); ?></a></li>
                <?php } ?>
                <li class="filter_tags<?php echo isset($gmedia_filter['tag__in'])? ' active' : ''; ?>"><a href="#libModal" data-modal="filter_tags" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Tags', 'grand-media'); ?></a></li>
                <?php do_action('gmedia_filter_list'); ?>
            </ul>
        </div>

        <?php if(!in_array($gmProcessor->mode, array('select_single', 'select_mutiple'))){ ?>
            <div class="btn-group">
                <?php if($gmProcessor->mode != 'edit'){
                    $edit_mode_href = gm_get_admin_url(array('mode' => 'edit'));
                } else{
                    $edit_mode_href = gm_get_admin_url(array(), array('mode'));
                } ?>
                <?php if(gm_user_can('edit_media')){ ?>
                    <a class="btn btn-default edit-mode-link" title="<?php _e('Toggle Edit Mode', 'grand-media'); ?>" href="<?php echo $edit_mode_href; ?>"><?php _e('Action', 'grand-media'); ?></a>
                <?php } else{ ?>
                    <button type="button" class="btn btn-default"><?php _e('Action', 'grand-media'); ?></button>
                <?php } ?>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
                <?php
                $rel_selected_show = 'rel-selected-show';
                $rel_selected_hide = 'rel-selected-hide';
                ?>
                <ul class="dropdown-menu" role="menu">
                    <?php if(!($gmProcessor->mode == 'edit')){ ?>
                        <li class="<?php echo gm_user_can('edit_media')? '' : 'disabled'; ?>">
                            <a class="edit-mode-link" href="<?php echo $edit_mode_href; ?>"><?php _e('Enter Edit Mode', 'grand-media'); ?></a>
                        </li>
                    <?php } else{ ?>
                        <li><a href="<?php echo $edit_mode_href; ?>"><?php _e('Exit Edit Mode', 'grand-media'); ?></a></li>
                    <?php } ?>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('edit_media')? '' : ' disabled'); ?>">
                        <a href="#libModal" data-modal="batch_edit" data-action="gmedia_get_modal" data-ckey="<?php echo GmediaProcessor_Library::$cookie_key; ?>" class="gmedia-modal"><?php _e('Batch Edit', 'grand-media'); ?></a></li>

                    <li class="divider <?php echo $rel_selected_show; ?>"></li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                        <a href="#libModal" data-modal="assign_album" data-action="gmedia_get_modal" data-ckey="<?php echo GmediaProcessor_Library::$cookie_key; ?>" class="gmedia-modal"><?php _e('Move to Album...', 'grand-media'); ?></a>
                    </li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                        <a href="<?php echo wp_nonce_url(gm_get_admin_url(array('do_gmedia' => 'unassign_album')), 'gmedia_action', '_wpnonce_action') ?>" data-confirm="<?php _e("You are about to remove the selected items from assigned albums.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"); ?>"><?php _e('Remove from Album', 'grand-media'); ?></a>
                    </li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                        <a href="#libModal" data-modal="assign_category" data-action="gmedia_get_modal" data-ckey="<?php echo GmediaProcessor_Library::$cookie_key; ?>" class="gmedia-modal"><?php _e('Assign Categories...', 'grand-media'); ?></a>
                    </li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                        <a href="#libModal" data-modal="unassign_category" data-action="gmedia_get_modal" data-ckey="<?php echo GmediaProcessor_Library::$cookie_key; ?>" class="gmedia-modal"><?php _e('Unassign Categories...', 'grand-media'); ?></a>
                    </li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                        <a href="#libModal" data-modal="add_tags" data-action="gmedia_get_modal" data-ckey="<?php echo GmediaProcessor_Library::$cookie_key; ?>" class="gmedia-modal"><?php _e('Add Tags...', 'grand-media'); ?></a></li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('terms')? '' : ' disabled'); ?>">
                        <a href="#libModal" data-modal="delete_tags" data-action="gmedia_get_modal" data-ckey="<?php echo GmediaProcessor_Library::$cookie_key; ?>" class="gmedia-modal"><?php _e('Delete Tags...', 'grand-media'); ?></a>
                    </li>
                    <li class="<?php echo $rel_selected_show . (gm_user_can('delete_media')? '' : ' disabled'); ?>">
                        <a href="<?php echo wp_nonce_url(gm_get_admin_url(array('do_gmedia' => 'delete',
                                                                                'ids'       => 'selected'
                                                                          ), array('filter')), 'gmedia_delete', '_wpnonce_delete') ?>" class="gmedia-delete" data-confirm="<?php _e("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"); ?>"><?php _e('Delete Selected Items', 'grand-media'); ?></a>
                    </li>

                    <?php if(!$gmProcessor->gmediablank){ ?>
                        <li class="divider <?php echo $rel_selected_show; ?>"></li>
                        <li class="<?php echo $rel_selected_show . (gm_user_can('edit_media')? '' : ' disabled'); ?>">
                            <a href="<?php echo wp_nonce_url(gm_get_admin_url(array('do_gmedia' => 'recreate'), array()), 'gmedia_action', '_wpnonce_action') ?>" class="gmedia-update"><?php _e('Re-create Images (heavy process)', 'grand-media'); ?></a>
                        </li>
                        <li class="<?php echo $rel_selected_show . (gm_user_can('edit_media')? '' : ' disabled'); ?>">
                            <a href="<?php echo wp_nonce_url(gm_get_admin_url(array('do_gmedia' => 'update_meta'), array()), 'gmedia_action', '_wpnonce_action') ?>" class="gmedia-update"><?php _e('Update Metadata in Database', 'grand-media'); ?></a>
                        </li>

                        <li class="divider"></li>
                        <li>
                            <a href="<?php echo gm_get_admin_url(array('page' => 'GrandMedia', 'gmedia__in' => 'duplicates'), array(), true); ?>"><?php _e('Show Duplicates in Library', 'grand-media'); ?></a>
                        </li>
                    <?php } ?>

                    <li class="divider <?php echo $rel_selected_hide; ?>"></li>
                    <li class="dropdown-header <?php echo $rel_selected_hide; ?>"><span><?php _e("Select items to see more actions", "grand-media"); ?></span></li>
                    <?php do_action('gmedia_action_list'); ?>

                </ul>
            </div>
        <?php }
        do_action('gmedia_library_btn_toolbar');
        ?>

        <?php
        $filter_stack     = $gmCore->_req('stack');
        $filter_stack_arg = $filter_stack? false : 'show';

        $filter_selected     = ('selected' == $gmCore->_req('filter'));
        $filter_selected_arg = $filter_selected? false : 'selected';
        ?>
        <form class="btn-group" id="gm-stack-btn" name="gm-stack-form" action="<?php echo gm_get_admin_url(array('stack' => $filter_stack_arg, 'filter' => $filter_selected), array(), $gmedia_url); ?>" method="post">
            <button type="submit" class="btn btn<?php echo ('show' == $filter_stack)? '-success' : '-info' ?>"><?php printf(__('%s in Stack', 'grand-media'), '<span id="gm-stack-qty">' . count($gmProcessor->stack_items) . '</span>'); ?></button>
            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
            <input type="hidden" id="gm-stack" data-userid="<?php echo $user_ID; ?>" data-key="gmedia_<?php echo $user_ID; ?>_libstack" name="stack_items" value="<?php echo implode(',', $gmProcessor->stack_items); ?>"/>
            <ul class="dropdown-menu" role="menu">
                <li><a id="gm-stack-show" href="#show"><?php
                        if(!$filter_stack){
                            _e('Show Stack', 'grand-media');
                        } else{
                            _e('Show Library', 'grand-media');
                        }
                        ?></a></li>
                <li><a id="gm-stack-clear" href="#clear"><?php _e('Clear Stack', 'grand-media'); ?></a></li>
                <li class="<?php echo gm_user_can('gallery_manage')? '' : 'disabled'; ?>">
                    <a href="#libModal" data-modal="quick_gallery_stack" data-action="gmedia_get_modal" data-ckey="gmedia_<?php echo $user_ID; ?>_libstack" class="gmedia-modal"><?php _e('Quick Gallery from Stack', 'grand-media'); ?></a>
                </li>
            </ul>
        </form>

        <?php if($gmProcessor->mode != 'select_single'){ ?>
            <form class="btn-group<?php echo $filter_selected? ' gm-active' : ''; ?>" id="gm-selected-btn" name="gm-selected-form" action="<?php echo gm_get_admin_url(array('stack' => $filter_stack, 'filter' => $filter_selected_arg), array(), $gmedia_url); ?>" method="post">
                <button type="submit" class="btn btn<?php echo ('selected' == $filter_selected)? '-success' : '-info' ?>"><?php printf(__('%s selected', 'grand-media'), '<span id="gm-selected-qty">' . count($gmProcessor->selected_items) . '</span>'); ?></button>
                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
                <input type="hidden" id="gm-selected" data-userid="<?php echo $user_ID; ?>" data-key="<?php echo GmediaProcessor_Library::$cookie_key; ?>" name="selected_items" value="<?php echo implode(',', $gmProcessor->selected_items); ?>"/>
                <ul class="dropdown-menu" role="menu">
                    <li><a id="gm-selected-show" href="#show"><?php
                            if(!$filter_selected){
                                _e('Show only selected items', 'grand-media');
                            } else{
                                _e('Show all gmedia items', 'grand-media');
                            }
                            ?></a></li>
                    <li><a id="gm-selected-clear" href="#clear"><?php _e('Clear selected items', 'grand-media'); ?></a></li>
                    <li><a id="gm-stack-in" href="#stack_add"><?php _e('Add selected items to Stack', 'grand-media'); ?></a></li>
                    <li><a id="gm-stack-out" href="#stack_remove"><?php _e('Remove selected items from Stack', 'grand-media'); ?></a></li>
                    <?php if($gmProcessor->mode != 'select_multiple'){ ?>
                        <li class="<?php echo gm_user_can('gallery_manage')? '' : 'disabled'; ?>">
                            <a href="#libModal" data-modal="quick_gallery" data-action="gmedia_get_modal" data-ckey="<?php echo GmediaProcessor_Library::$cookie_key; ?>" class="gmedia-modal"><?php _e('Quick Gallery from Selected', 'grand-media'); ?></a>
                        </li>
                    <?php } ?>
                </ul>
            </form>
        <?php } ?>
    </div>

</div>
