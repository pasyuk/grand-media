<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    die('You are not allowed to call this page directly.');
}

/**
 * gmediaTerms()
 *
 * @return mixed content
 */
function gmediaTerms()
{
    global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

    $url = add_query_arg(array('page' => $gmProcessor->page), admin_url('admin.php'));

    $gm_screen_options = get_user_meta($user_ID, 'gm_screen_options', true);
    if (! is_array($gm_screen_options)) {
        $gm_screen_options = array();
    }
    $gm_screen_options = array_merge($gmGallery->options['gm_screen_options'], $gm_screen_options);
    $orderby           = ! empty($gm_screen_options['orderby_gmedia_terms']) ? $gm_screen_options['orderby_gmedia_terms'] : 'name';
    $order             = ! empty($gm_screen_options['sortorder_gmedia_terms']) ? $gm_screen_options['sortorder_gmedia_terms'] : 'ASC';
    $per_page          = ! empty($gm_screen_options['per_page_gmedia_terms']) ? $gm_screen_options['per_page_gmedia_terms'] : 30;

    $taxonomy = $gmCore->_get('term', 'gmedia_album');

    if ($gmCore->caps['gmedia_show_others_media']) {
        $author = 0;
    } else {
        $author = $user_ID;
    }

    $filter        = ('selected' == $gmCore->_req('filter')) ? $gmProcessor->selected_items : null;
    $search_string = $gmCore->_get('s', '');
    if ('#' == substr($search_string, 0, 1)) {
        $filter        = substr($search_string, 1);
        $search_string = '';
    }
    $args           = array(
        'orderby'    => $gmCore->_get('orderby', $orderby),
        'order'      => $gmCore->_get('order', $order),
        'search'     => $search_string,
        'number'     => $gmCore->_get('number', $per_page),
        'hide_empty' => $gmCore->_get('hide_empty', 0),
        'page'       => $gmCore->_get('pager', 1),
        'include'    => $filter
    );
    $args['offset'] = ($args['page'] - 1) * $args['number'];

    $allow_edit = 0;
    switch ($taxonomy) {
        case 'gmedia_filter':
            $args['hide_empty'] = 0;
            $allow_edit         = $gmCore->caps['gmedia_filter_manage'];
            $args['global']     = $gmCore->_get('author', $gmCore->caps['gmedia_edit_others_media'] ? '' : array(0, $user_ID));
            if (! $gmCore->caps['gmedia_show_others_media']) {
                $args['global'] = wp_parse_id_list($args['global']);
                $args['global'] = array_intersect(array(0, $user_ID), $args['global']);
                if (empty($args['global'])) {
                    $args['global'] = array(0, $user_ID);
                }
            }
            break;
        case 'gmedia_album':
            $allow_edit     = $gmCore->caps['gmedia_album_manage'];
            $args['global'] = $gmCore->_get('author', $gmCore->caps['gmedia_edit_others_media'] ? '' : array(0, $user_ID));
            if (! $gmCore->caps['gmedia_show_others_media']) {
                $args['global'] = wp_parse_id_list($args['global']);
                $args['global'] = array_intersect(array(0, $user_ID), $args['global']);
                if (empty($args['global'])) {
                    $args['global'] = array(0, $user_ID);
                }
            }
            break;
        case 'gmedia_tag':
            $allow_edit = $gmCore->caps['gmedia_tag_manage'];
            if ('global' == $args['orderby']) {
                $args['orderby'] = 'id';
            }
            break;
        case 'gmedia_category':
            $args = array();
            break;
    }
    $gmediaTerms = $gmDB->get_terms($taxonomy, $args);

    ?>
    <div class="panel panel-default panel-fixed-header" id="gmedia-panel">
        <div class="panel-heading-fake"></div>
        <div class="panel-heading clearfix">

            <?php if ('gmedia_category' != $taxonomy) { ?>
                <form class="form-inline gmedia-search-form" role="search" method="get">
                    <div class="form-group">
                        <input type="hidden" name="page" value="<?php echo $gmProcessor->page; ?>"/>
                        <input type="hidden" name="term" value="<?php echo $taxonomy; ?>"/>
                        <input id="gmedia-search" class="form-control input-sm" type="text" name="s" placeholder="<?php _e('Search...', 'grand-media'); ?>" value="<?php echo $gmCore->_get('s', ''); ?>"/>
                    </div>
                    <button type="submit" class="btn btn-default input-sm"><span class="glyphicon glyphicon-search"></span></button>
                </form>
                <?php echo $gmDB->query_pager(); ?>
            <?php } ?>

            <div class="btn-toolbar pull-left">
                <?php if ('gmedia_category' != $taxonomy) { ?>
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
                switch ($taxonomy) {
                    case 'gmedia_album':
                        $button_title = __('Show: Albums', 'grand-media');
                        break;
                    case 'gmedia_filter':
                        $button_title = __('Show: Filters', 'grand-media');
                        break;
                    case 'gmedia_tag':
                        $button_title = __('Show: Tags', 'grand-media');
                        break;
                    case 'gmedia_category':
                        $button_title = __('Show: Categories', 'grand-media');
                        break;
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
                        <li<?php echo ('gmedia_album' == $taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('term' => 'gmedia_album'), $url); ?>"><?php _e('Albums', 'grand-media'); ?></a></li>
                        <li<?php echo ('gmedia_tag' == $taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('term' => 'gmedia_tag'), $url); ?>"><?php _e('Tags', 'grand-media'); ?></a></li>
                        <li<?php echo ('gmedia_category' == $taxonomy)? ' class="active"' : '';?>><a href="<?php echo add_query_arg(array('term' => 'gmedia_category'), $url); ?>"><?php _e('Categories', 'grand-media'); ?></a></li>
                        <li class="divider"></li>
                        <li<?php echo ('gmedia_filter' == $taxonomy)? ' class="active"' : ''; ?>><a href="<?php echo add_query_arg(array('term' => 'gmedia_filter'), $url); ?>"><?php _e('Custom Filters', 'grand-media'); ?></a></li>
                    </ul>
                </div>

                <?php if (('gmedia_filter' == $taxonomy) && $gmCore->caps['gmedia_filter_manage']) { ?>
                    <a class="btn btn-success pull-left" href="<?php echo add_query_arg(array('edit_filter' => '0'), $url); ?>"><?php _e('Create Filter', 'grand-media'); ?></a>
                <?php } ?>

                <?php if (('gmedia_category' != $taxonomy) && ! empty($gmediaTerms)) { ?>
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
                            <li class="<?php echo $rel_selected_show . ($gmCore->caps['gmedia_terms_delete']? '' : ' disabled'); ?>">
                                <a href="<?php echo wp_nonce_url($gmCore->get_admin_url(array('delete' => 'selected'), array('filter')), 'gmedia_delete') ?>" class="gmedia-delete" data-confirm="<?php _e("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"); ?>"><?php _e('Delete Selected Items', 'grand-media'); ?></a>
                            </li>
                            <?php do_action('gmedia_term_action_list'); ?>
                        </ul>
                    </div>

                    <form class="btn-group" id="gm-selected-btn" name="gm-selected-form" action="<?php echo add_query_arg(array( 'term' => $taxonomy, 'filter' => 'selected'), $url); ?>" method="post">
                        <button type="submit" class="btn btn<?php echo ('selected' == $gmCore->_req('filter')) ? '-success' : '-info' ?>"><?php printf(__('%s selected', 'grand-media'), '<span id="gm-selected-qty">' . count($gmProcessor->selected_items) . '</span>'); ?></button>
                        <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span> <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
                        <input type="hidden" id="gm-selected" data-userid="<?php echo $user_ID; ?>" data-key="<?php echo $taxonomy; ?>" name="selected_items" value="<?php echo implode(',', $gmProcessor->selected_items); ?>"/>
                        <ul class="dropdown-menu" role="menu">
                            <li><a id="gm-selected-show" href="#show"><?php _e('Show only selected items', 'grand-media'); ?></a></li>
                            <li><a id="gm-selected-clear" href="#clear"><?php _e('Clear selected items', 'grand-media'); ?></a></li>
                        </ul>
                    </form>
                <?php } ?>

            </div>
        </div>


        <?php if ('gmedia_album' == $taxonomy) { ?>
            <?php if ($allow_edit) { ?>
                <form method="post" id="gmedia-edit-term" name="gmAddTerms" class="panel-body" action="<?php echo $url; ?>" style="padding-bottom:0; border-bottom:1px solid #ddd;">
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label><?php _e('Name', 'grand-media'); ?></label>
                                <input type="text" class="form-control input-sm" name="term[name]" placeholder="<?php _e('Album Name', 'grand-media'); ?>" required/>
                            </div>
                            <div class="form-group">
                                <label><?php _e('Description', 'grand-media'); ?></label>
                                <textarea class="form-control input-sm" style="height:75px;" rows="2" name="term[description]"></textarea>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group row">
                                <div class="col-xs-6">
                                    <label><?php _e('Order gmedia', 'grand-media'); ?></label>
                                    <select name="term[meta][_orderby]" class="form-control input-sm">
                                        <option value="custom"><?php _e('user defined', 'grand-media'); ?></option>
                                        <option selected="selected" value="ID"><?php _e('by ID', 'grand-media'); ?></option>
                                        <option value="title"><?php _e('by title', 'grand-media'); ?></option>
                                        <option value="gmuid"><?php _e('by filename', 'grand-media'); ?></option>
                                        <option value="date"><?php _e('by date', 'grand-media'); ?></option>
                                        <option value="modified"><?php _e('by last modified date', 'grand-media'); ?></option>
                                        <option value="rand"><?php _e('Random', 'grand-media'); ?></option>
                                    </select>
                                </div>
                                <div class="col-xs-6">
                                    <label><?php _e('Sort order', 'grand-media'); ?></label>
                                    <select name="term[meta][_order]" class="form-control input-sm">
                                        <option selected="selected" value="DESC"><?php _e('DESC', 'grand-media'); ?></option>
                                        <option value="ASC"><?php _e('ASC', 'grand-media'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-xs-6">
                                    <label><?php _e('Status', 'grand-media'); ?></label>
                                    <select name="term[status]" class="form-control input-sm">
                                        <option selected="selected" value="public"><?php _e('Public', 'grand-media'); ?></option>
                                        <option value="private"><?php _e('Private', 'grand-media'); ?></option>
                                        <option value="draft"><?php _e('Draft', 'grand-media'); ?></option>
                                    </select>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label><?php _e('Author', 'grand-media'); ?></label>
                                        <?php $user_ids = $gmCore->caps['gmedia_delete_others_media'] ? $gmCore->get_editable_user_ids() : array($user_ID);
                                        if ($user_ids && $gmCore->caps['gmedia_edit_others_media']) {
                                            if (! in_array($user_ID, $user_ids)) {
                                                array_push($user_ids, $user_ID);
                                            }
                                            wp_dropdown_users(array(
                                                'include'          => $user_ids,
                                                'include_selected' => true,
                                                'name'             => 'term[global]',
                                                'selected'         => $user_ID,
                                                'class'            => 'form-control input-sm',
                                                'multi'            => true,
                                                'show_option_all'  => __('Shared', 'grand-media')
                                            ));
                                        } else {
                                            echo '<input type="hidden" name="term[global]" value="' . $user_ID . '"/>';
                                            echo '<div>' . get_the_author_meta('display_name', $user_ID) . '</div>';
                                        }
                                        ?>
                                    </div>
                                    <div class="form-group">
                                        <?php
                                        wp_original_referer_field(true, 'previous');
                                        wp_nonce_field('GmediaTerms', 'term_save_wpnonce');
                                        ?>
                                        <input type="hidden" name="term[taxonomy]" value="gmedia_album"/>
                                        <button style="display:block" type="submit" class="btn btn-primary btn-sm" name="gmedia_album_save"><?php _e('Add New Album', 'grand-media'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php } else { ?>
                <div class="alert alert-warning alert-dismissible" role="alert" style="margin-bottom:0">
                    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"><?php _e('Close', 'grand-media'); ?></span> </button>
                    <strong><?php _e('Info:', 'grand-media'); ?></strong> <?php _e('You are not allowed to add new terms', 'grand-media'); ?>
                </div>
            <?php } ?>
            <form class="list-group <?php echo $taxonomy; ?>" id="gm-list-table" style="margin-bottom:4px;">
                <?php
                if (count($gmediaTerms)) {
                    foreach ($gmediaTerms as $item) {
                        $termItems = array();
                        $per_page  = 7;
                        if ($item->count) {
                            $term_meta = $gmDB->get_metadata('gmedia_term', $item->term_id);
                            $term_meta = array_map('reset', $term_meta);
                            $term_meta = array_merge(array('_orderby' => 'ID', '_order' => 'DESC'), $term_meta);
                            $args      = array(
                                'no_found_rows' => true,
                                'per_page'      => $per_page,
                                'album__in'     => array($item->term_id),
                                'author'        => $author,
                                'orderby'       => $term_meta['_orderby'],
                                'order'         => $term_meta['_order']
                            );
                            $termItems = $gmDB->get_gmedias($args);
                        }
                        $is_selected        = in_array($item->term_id, $gmProcessor->selected_items) ? true : false;
                        $author_name        = '';
                        $list_row_class     = $row_class = '';
                        $allow_terms_delete = $gmCore->caps['gmedia_terms_delete'];
                        if ($item->global) {
                            if (($display_author_name = get_the_author_meta('display_name', $item->global))) {
                                $author_name .= sprintf(__('by %s', 'grand-media'), $display_author_name);
                            } else {
                                $author_name .= '(' . __('deleted author', 'grand-media') . ')';
                            }
                            if ($item->global == $user_ID) {
                                $row_class .= ' current_user';
                                $allow_edit   = $gmCore->caps['gmedia_album_manage'];
                                $allow_delete = $allow_terms_delete;
                            } else {
                                $row_class .= ' other_user';
                                $allow_edit   = $gmCore->caps['gmedia_edit_others_media'];
                                $allow_delete = ($allow_edit && $allow_terms_delete);
                            }
                        } else {
                            $author_name .= '(' . __('no author', 'grand-media') . ')';
                            $row_class .= ' shared';
                            $allow_edit   = $gmCore->caps['gmedia_edit_others_media'];
                            $allow_delete = ($allow_edit && $allow_terms_delete);
                        }
                        if ('public' != $item->status) {
                            $author_name .= ' [' . $item->status . ']';
                            if ('private' == $item->status) {
                                $list_row_class = ' list-group-item-info';
                            } elseif ('draft' == $item->status) {
                                $list_row_class = ' list-group-item-warning';
                            }
                        }
                        ?>
                        <div class="cb_list-item list-group-item term-list-item<?php echo ($is_selected ? ' gm-selected' : '') . $list_row_class; ?>">
                            <div class="row cb_term-object<?php echo $row_class; ?>">
                                <div class="term_id">#<?php echo $item->term_id; ?></div>
                                <div class="col-xs-6 term-label">
                                    <div class="checkbox">
                                        <input name="doaction[]" type="checkbox"<?php echo $is_selected ? ' checked="checked"' : ''; ?> value="<?php echo $item->term_id; ?>"/>
                                        <?php if ($allow_edit) { ?>
                                            <a class="term_name" href="<?php echo add_query_arg(array('edit_album' => $item->term_id), $url); ?>"><?php echo esc_html($item->name); ?></a>
                                        <?php } else { ?>
                                            <span class="term_name"><?php echo esc_html($item->name); ?></span>
                                        <?php } ?>
                                        <span class="term_info_author"><?php echo $author_name; ?></span>

                                        <div class="object-actions">
                                            <?php
                                            if ($item->count) {
                                                $filter_href_attr = 'class="gm_filter_in_lib" href="' . $gmCore->get_admin_url(array( 'page' => 'GrandMedia', 'album__in' => $item->term_id), array(), true) . '"';
                                            } else {
                                                $filter_href_attr = 'class="gm_filter_in_lib action-inactive"';
                                            }
                                            echo '<a title="' . __('Filter in Gmedia Library', 'grand-media') . '" ' . $filter_href_attr . '><span class="gm_term_count">' . $item->count . '</span><span class="glyphicon glyphicon-filter"></span></a>';

                                            $cloud_link = $gmCore->gmcloudlink($item->term_id, 'album');
                                            $share_icon = '<span class="glyphicon glyphicon-share"></span>';
                                            if ('draft' !== $item->status) {
                                                echo '<a target="_blank" data-target="#shareModal" data-share="' . $item->term_id . '" class="share-modal" title="' . __('Share', 'grand-media') . '" href="' . $cloud_link . '">' . $share_icon . '</a>';
                                            } else {
                                                echo "<span class='action-inactive'>$share_icon</span>";
                                            }

                                            $edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
                                            if ($allow_edit) {
                                                echo '<a title="' . __('Edit', 'grand-media') . '" href="' . add_query_arg(array('edit_album' => $item->term_id), $url) . '">' . $edit_icon . '</a>';
                                            } else {
                                                echo "<span class='action-inactive'>$edit_icon</span>";
                                            }

                                            if ($allow_terms_delete) {
                                                $trash_icon = '<span class="glyphicon glyphicon-trash"></span>';
                                                if ($allow_delete) {
                                                    echo '<a class="trash-icon" title="' . __('Delete', 'grand-media') . '" href="' . wp_nonce_url(add_query_arg(array( 'term' => $taxonomy, 'delete' => $item->term_id), $url), 'gmedia_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . $trash_icon . '</a>';
                                                } else {
                                                    echo "<span class='action-inactive'>$trash_icon</span>";
                                                }
                                            }
                                            ?>
                                        </div>
                                        <?php if (! empty($item->description)) { ?>
                                            <div class="term-description"><?php echo esc_html(nl2br($item->description)); ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="term-images">
                                        <?php if (! empty($termItems)) {
                                            foreach ($termItems as $i) {
                                                ?>
                                                <img style="z-index:<?php echo $per_page--; ?>;" src="<?php echo $gmCore->gm_get_media_image($i, 'thumb', false); ?>" alt="<?php echo $i->ID; ?>" title="<?php echo esc_attr($i->title); ?>"/>
                                                <?php
                                            }
                                        }
                                        if (count($termItems) < $item->count) {
                                            echo '...';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="list-group-item">
                        <div class="well well-lg text-center">
                            <h4><?php _e('No items to show.', 'grand-media'); ?></h4>
                        </div>
                    </div>
                <?php } ?>
                <?php
                wp_original_referer_field(true, 'previous');
                wp_nonce_field('GmediaTerms');
                ?>
            </form>


        <?php } elseif ('gmedia_filter' == $taxonomy) { ?>
            <form class="list-group" id="gm-list-table" style="margin-bottom:4px;">
                <?php
                if (count($gmediaTerms)) {
                    foreach ($gmediaTerms as $item) {
                        $term_query = $gmDB->get_metadata('gmedia_term', $item->term_id, '_query', true);

                        $is_selected        = in_array($item->term_id, $gmProcessor->selected_items) ? true : false;
                        $author_name        = '';
                        $row_class          = '';
                        $allow_terms_delete = $gmCore->caps['gmedia_terms_delete'];
                        if ($item->global) {
                            if (($display_author_name = get_the_author_meta('display_name', $item->global))) {
                                $author_name .= sprintf(__('by %s', 'grand-media'), $display_author_name);
                            } else {
                                $author_name .= '(' . __('deleted author', 'grand-media') . ')';
                            }
                            if ($item->global == $user_ID) {
                                $row_class .= ' current_user';
                                $allow_edit   = $gmCore->caps['gmedia_filter_manage'];
                                $allow_delete = $allow_terms_delete;
                            } else {
                                $row_class .= ' other_user';
                                $allow_edit   = $gmCore->caps['gmedia_edit_others_media'];
                                $allow_delete = ($allow_edit && $allow_terms_delete);
                            }
                        } else {
                            $author_name .= '(' . __('no author', 'grand-media') . ')';
                            $row_class .= ' shared';
                            $allow_edit   = $gmCore->caps['gmedia_edit_others_media'];
                            $allow_delete = ($allow_edit && $allow_terms_delete);
                        }
                        ?>
                        <div class="cb_list-item list-group-item term-list-item<?php echo $is_selected ? ' gm-selected' : ''; ?>">
                            <div class="row cb_term-object<?php echo $row_class; ?>">
                                <div class="term_id">#<?php echo $item->term_id; ?></div>
                                <div class="col-xs-6 term-label">
                                    <div class="checkbox">
                                        <input name="doaction[]" type="checkbox"<?php echo $is_selected ? ' checked="checked"' : ''; ?> value="<?php echo $item->term_id; ?>"/>
                                        <?php if ($allow_edit) { ?>
                                            <a class="term_name" href="<?php echo add_query_arg(array('edit_filter' => $item->term_id), $url); ?>"><?php echo esc_html($item->name); ?></a>
                                        <?php } else { ?>
                                            <span class="term_name"><?php echo esc_html($item->name); ?></span>
                                        <?php } ?>
                                        <span class="term_info_author"><?php echo $author_name; ?></span>

                                        <div class="object-actions">
                                            <?php
                                            $filter_icon = '<span class="glyphicon glyphicon-filter"></span>';
                                            echo '<a title="' . __('Filter in Gmedia Library', 'grand-media') . '" href="' . $gmCore->get_admin_url(array( 'page' => 'GrandMedia', 'custom_filter' => $item->term_id), array(), true) . '">' . $filter_icon . '</a>';

                                            $cloud_link = $gmCore->gmcloudlink($item->term_id, 'filter');
                                            $share_icon = '<span class="glyphicon glyphicon-share"></span>';
                                            echo '<a target="_blank" data-target="#shareModal" data-share="' . $item->term_id . '" class="share-modal" title="' . __('Share', 'grand-media') . '" href="' . $cloud_link . '">' . $share_icon . '</a>';

                                            $edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
                                            if ($allow_edit) {
                                                echo '<a title="' . __('Edit', 'grand-media') . '" href="' . add_query_arg(array('edit_filter' => $item->term_id), $url) . '">' . $edit_icon . '</a>';
                                            } else {
                                                echo "<span class='action-inactive'>$edit_icon</span>";
                                            }

                                            if ($allow_terms_delete) {
                                                $trash_icon = '<span class="glyphicon glyphicon-trash"></span>';
                                                if ($allow_delete) {
                                                    echo '<a class="trash-icon" title="' . __('Delete', 'grand-media') . '" href="' . wp_nonce_url(add_query_arg(array( 'term' => $taxonomy, 'delete' => $item->term_id), $url), 'gmedia_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . $trash_icon . '</a>';
                                                } else {
                                                    echo "<span class='action-inactive'>$trash_icon</span>";
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <p class="term-meta" style="margin:10px 0;">
                                        <?php echo '<b>' . __('Query:') . "</b> " . str_replace(',"', ', "', json_encode($term_query)); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (! empty($item->description)) { ?>
                                <div class="term-description"><?php echo nl2br(esc_html($item->description)); ?></div>
                            <?php } ?>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="list-group-item">
                        <div class="well well-lg text-center">
                            <h4><?php _e('No items to show.', 'grand-media'); ?></h4>
                        </div>
                    </div>
                <?php } ?>
                <?php
                wp_original_referer_field(true, 'previous');
                wp_nonce_field('GmediaTerms');
                ?>
            </form>


        <?php } elseif ('gmedia_tag' == $taxonomy) { ?>
            <?php if ($allow_edit) { ?>
                <form method="post" id="gmedia-edit-term" name="gmAddTerms" class="panel-body" action="<?php echo add_query_arg(array('term' => $taxonomy), $url); ?>" style="padding-bottom:0; border-bottom:1px solid #ddd;">
                    <div class="row">
                        <div class="form-group col-xs-9">
                            <label><?php _e('Tags', 'grand-media'); ?>
                                <small class="text-muted">(<?php _e('you can type multiple tags separated by comma') ?>)</small>
                            </label>
                            <input type="text" class="form-control input-sm" name="term[name]" placeholder="<?php _e('Tag Names', 'grand-media'); ?>" required/>
                        </div>
                        <div class="col-xs-3" style="padding-top:24px;">
                            <?php
                            wp_original_referer_field(true, 'previous');
                            wp_nonce_field('GmediaTerms', 'term_save_wpnonce');
                            ?>
                            <input type="hidden" name="term[taxonomy]" value="gmedia_tag"/>
                            <button type="submit" class="btn btn-primary btn-sm" name="gmedia_tag_add"><?php _e('Add New Tags', 'grand-media'); ?></button>
                        </div>
                    </div>
                </form>
            <?php } else { ?>
                <div class="alert alert-warning alert-dismissible" role="alert" style="margin-bottom:0">
                    <button type="button" class="close" data-dismiss="alert">
                        <span aria-hidden="true">&times;</span><span class="sr-only"><?php _e('Close', 'grand-media'); ?></span>
                    </button>
                    <strong><?php _e('Info:', 'grand-media'); ?></strong> <?php _e('You are not allowed to add new terms', 'grand-media'); ?>
                </div>
            <?php } ?>
            <form class="list-group" id="gm-list-table" style="margin-bottom:4px;">
                <?php
                if (count($gmediaTerms)) {
                    if ($gmCore->caps['gmedia_edit_others_media']) {
                        $allow_edit   = true;
                        $allow_delete = $gmCore->caps['gmedia_terms_delete'];
                    } else {
                        $allow_edit   = false;
                        $allow_delete = false;
                    }
                foreach ($gmediaTerms as $item) {
                    $termItems = array();
                    $per_page  = 5;
                    if ($item->count) {
                        $args      = array(
                            'no_found_rows' => true,
                            'per_page'      => $per_page,
                            'orderby'       => $gmGallery->options['in_tag_orderby'],
                            'order'         => $gmGallery->options['in_tag_order'],
                            'tag_id'        => $item->term_id,
                            'author'        => $author
                        );
                        $termItems = $gmDB->get_gmedias($args);
                    }
                    $is_selected = in_array($item->term_id, $gmProcessor->selected_items) ? true : false;
                    ?>
                    <div class="cb_list-item list-group-item term-list-item<?php echo $is_selected ? ' gm-selected' : ''; ?>">
                        <div class="row cb_term-object" id="tag_<?php echo $item->term_id; ?>">
                            <div class="term_id">#<?php echo $item->term_id; ?></div>
                            <div class="col-xs-6 term-label">
                                <div class="checkbox">
                                    <input name="doaction[]" type="checkbox"<?php echo $is_selected ? ' checked="checked"' : ''; ?> value="<?php echo $item->term_id; ?>"/>
                                    <?php if ($allow_edit) { ?>
                                        <a class="edit_tag_link" href="#tag_<?php echo $item->term_id; ?>"><?php echo esc_html($item->name); ?></a>
                                        <span class="edit_tag_form" style="display:none;"><input class="edit_tag_input" type="text" data-tag_id="<?php echo $item->term_id; ?>" name="gmedia_tag_name[<?php echo $item->term_id; ?>]" value="<?php echo esc_attr($item->name); ?>" placeholder="<?php echo esc_attr($item->name); ?>"/><a href="#tag_<?php echo $item->term_id; ?>" class="edit_tag_save btn btn-link glyphicon glyphicon-pencil"></a></span>

                                    <?php } else { ?>
                                        <span><?php echo esc_html($item->name); ?></span>
                                    <?php } ?>

                                    <div class="object-actions">
                                        <?php
                                        if ($item->count) {
                                            $filter_href_attr = 'class="gm_filter_in_lib" href="' . $gmCore->get_admin_url(array( 'page' => 'GrandMedia', 'tag__in' => $item->term_id), array(), true) . '"';
                                        } else {
                                            $filter_href_attr = 'class="gm_filter_in_lib action-inactive"';
                                        }
                                        echo '<a title="' . __('Filter in Gmedia Library', 'grand-media') . '" ' . $filter_href_attr . '><span class="gm_term_count">' . $item->count . '</span><span class="glyphicon glyphicon-filter"></span></a>';

                                        $cloud_link = $gmCore->gmcloudlink($item->term_id, 'tag');
                                        $share_icon = '<span class="glyphicon glyphicon-share"></span>';
                                        echo '<a target="_blank" data-target="#shareModal" data-share="' . $item->term_id . '" class="share-modal" title="' . __('Share', 'grand-media') . '" href="' . $cloud_link . '">' . $share_icon . '</a>';

                                        /*
									$edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
									if ( $allow_edit ){
										echo '<a class="edit_tag_link" title="' . __('Edit', 'grand-media') . '" href="#tag_' . $item->term_id . '">'.$edit_icon.'</a>';
									}
									*/

                                        if ($allow_delete) {
                                            $trash_icon = '<span class="glyphicon glyphicon-trash"></span>';
                                            echo '<a title="' . __('Delete', 'grand-media') . '" href="' . wp_nonce_url(add_query_arg(array( 'term' => $taxonomy, 'delete' => $item->term_id), $url), 'gmedia_delete') . '">' . $trash_icon . '</a>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6 term-images">
                                <?php if (! empty($termItems)) {
                                    foreach ($termItems as $i) {
                                        ?>
                                        <img style="z-index:<?php echo $per_page--; ?>;" src="<?php echo $gmCore->gm_get_media_image($i, 'thumb', false); ?>" alt="<?php echo $i->ID; ?>" title="<?php echo esc_attr($i->title); ?>"/>
                                        <?php
                                    }
                                }
                                if (count($termItems) < $item->count) {
                                    echo '...';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($allow_edit){ ?>
                    <script type="text/javascript">
                        jQuery(function ($) {
                            $('#gm-list-table').data('edit', false);
                            $('input.edit_tag_input').keypress(function (e) {
                                var tagdiv = $('#tag_' + $(this).data('tag_id'));
                                var charCode = e.charCode || e.keyCode || e.which;
                                if (charCode == 13) {
                                    e.preventDefault();
                                    edit_tag(tagdiv);
                                }
                            }).blur(function () {
                                var tagdiv = $('#tag_' + $(this).data('tag_id'));
                                edit_tag(tagdiv);
                            });

                            $('.edit_tag_link').click(function (e) {
                                e.preventDefault();
                                var id = $(this).attr('href');
                                $(this).hide();
                                $(id).find('.edit_tag_form').show().find('input').focus();
                                $('#gm-list-table').data('edit', true);
                            });
                            $('.edit_tag_save').click(function (e) {
                                e.preventDefault();
                            });

                            function edit_tag(tagdiv) {
                                var inp = tagdiv.find('.edit_tag_form input');
                                var new_tag_name = $.trim(inp.val());
                                var old_tag_name = inp.attr('placeholder');
                                if ((old_tag_name == new_tag_name) || ('' === new_tag_name) || $.isNumeric()) {
                                    inp.val(old_tag_name);
                                    tagdiv.find('.edit_tag_form').hide();
                                    tagdiv.find('.edit_tag_link').show();
                                    return;
                                }
                                var post_data = {
                                    action: 'gmedia_tag_edit',
                                    tag_id: inp.data('tag_id'),
                                    tag_name: new_tag_name,
                                    _wpnonce: $('#_wpnonce').val()
                                };
                                $.post(ajaxurl, post_data, function (data, textStatus, jqXHR) {
                                    console.log(data);
                                    if (data.error) {
                                        //inp.val(inp.attr('placeholder'));
                                        $('#gmedia-panel').before(data.error);
                                    } else {
                                        //new_tag_name = new_tag_name.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                        inp.attr('placeholder', new_tag_name);
                                        tagdiv.find('.edit_tag_link').text(new_tag_name).show();
                                        //noinspection JSUnresolvedVariable
                                        $('#gmedia-panel').before(data.msg);
                                        tagdiv.find('.edit_tag_form').hide();
                                    }
                                });
                            }
                        });
                    </script>
                <?php } ?>
                <?php
                } else {
                ?>
                    <div class="list-group-item">
                        <div class="well well-lg text-center">
                            <h4><?php _e('No items to show.', 'grand-media'); ?></h4>
                        </div>
                    </div>
                <?php } ?>
                <?php
                wp_original_referer_field(true, 'previous');
                wp_nonce_field('GmediaTerms');
                ?>
            </form>


        <?php } elseif ('gmedia_category' == $taxonomy) { ?>
            <div class="panel-body"></div>
            <div class="list-group" id="gm-list-table" style="margin-bottom:4px;">
                <?php
                $gmediaCategories = $gmGallery->options['taxonomies']['gmedia_category'];
                foreach ($gmediaTerms as $item) {
                    $cat[$item->name] = $item;
                }
                unset($gmediaTerms);

                foreach ($gmediaCategories as $name => $title) {
                    $termItems = array();
                    $per_page  = 10;
                    if (isset($cat[$name])) {
                        $count   = $cat[$name]->count;
                        $term_id = $cat[$name]->term_id;
                        if ($count) {
                            $args      = array(
                                'no_found_rows' => true,
                                'per_page'      => $per_page,
                                'orderby'       => $gmGallery->options['in_category_orderby'],
                                'order'         => $gmGallery->options['in_category_order'],
                                'category__in'  => array($term_id),
                                'author'        => $author
                            );
                            $termItems = $gmDB->get_gmedias($args);
                        }
                    } else {
                        $count   = 0;
                        $term_id = '##';
                    }
                    ?>
                    <div class="list-group-item term-list-item">
                        <div class="row cb_term-object">
                            <div class="term_id">#<?php echo $term_id; ?></div>
                            <div class="col-xs-6" style="padding-top:10px; padding-bottom:10px;">
                                <?php echo esc_html($title); ?>
                                <div class="object-actions">
                                    <?php
                                    if ($count) {
                                        $filter_href_attr = 'class="gm_filter_in_lib" href="' . $gmCore->get_admin_url(array( 'page' => 'GrandMedia', 'category__in' => $term_id), array(), true) . '"';
                                    } else {
                                        $filter_href_attr = 'class="gm_filter_in_lib action-inactive"';
                                    }
                                    echo '<a title="' . __('Filter in Gmedia Library', 'grand-media') . '" ' . $filter_href_attr . '><span class="gm_term_count">' . $count . '</span><span class="glyphicon glyphicon-filter"></span></a>';

                                    $share_icon = '<span class="glyphicon glyphicon-share"></span>';
                                    if (isset($cat[$name]->term_id)) {
                                        $cloud_link = $gmCore->gmcloudlink($term_id, 'category');
                                        echo '<a target="_blank" data-target="#shareModal" data-share="' . $term_id . '" class="share-modal" title="' . __('Share', 'grand-media') . '" href="' . $cloud_link . '">' . $share_icon . '</a>';
                                    } else {
                                        echo "<span class='action-inactive'>$share_icon</span>";
                                    }

                                    /*
								$edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
								echo '<a title="' . __('Edit', 'grand-media') . '" href="' . add_query_arg( array( 'edit_album' => $item->term_id ), $url ) . '">'.$edit_icon.'</a>';
								*/
                                    ?>
                                </div>
                            </div>
                            <div class="col-xs-6 term-images">
                                <?php if (! empty($termItems)) {
                                    foreach ($termItems as $i) {
                                        ?>
                                        <img style="z-index:<?php echo $per_page--; ?>;" src="<?php echo $gmCore->gm_get_media_image($i, 'thumb', false); ?>" alt="<?php echo $i->ID; ?>" title="<?php echo esc_attr($i->title); ?>"/>
                                        <?php
                                    }
                                }
                                if (count($termItems) < $count) {
                                    echo '...';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

    </div>

    <div class="modal fade gmedia-modal" id="shareModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php _e('GmediaCloud Page'); ?></h4>
                </div>
                <form class="modal-body" method="post" id="shareForm">
                    <div class="form-group">
                        <label><?php _e('Link to page', 'grand-media'); ?></label>
                        <input name="sharelink" type="text" class="form-control sharelink" readonly="readonly" value=""/>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Send this link to', 'grand-media'); ?></label>
                        <input name="email" type="email" class="form-control sharetoemail" value="" placeholder="<?php _e('Email', 'grand-media'); ?>"/>
                        <textarea name="message" cols="20" rows="3" class="form-control" placeholder="<?php _e('Message (optional)', 'grand-media'); ?>"></textarea>
                    </div>
                    <input type="hidden" name="action" value="gmedia_share_page"/>
                    <?php wp_nonce_field('share_modal', '_sharenonce'); ?>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary sharebutton" disabled="disabled"><?php _e('Send', 'grand-media'); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'grand-media'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <?php
}


/**
 * gmediaAlbumEdit()
 *
 * @return mixed content
 */
function gmediaAlbumEdit()
{
    global $gmDB, $gmCore, $gmProcessor, $gmGallery, $user_ID;

    if (! $gmCore->caps['gmedia_album_manage']) {
        die('-1');
    }

    $url = add_query_arg(array('page' => $gmProcessor->page), admin_url('admin.php'));

    $gm_screen_options = get_user_meta($user_ID, 'gm_screen_options', true);
    if (! is_array($gm_screen_options)) {
        $gm_screen_options = array();
    }
    $gm_screen_options = array_merge($gmGallery->options['gm_screen_options'], $gm_screen_options);

    $taxonomy = 'gmedia_album';
    $term_id  = $gmCore->_get('edit_album');

    $term = $gmDB->get_term($term_id, $taxonomy);

    if (! empty($term) && ! is_wp_error($term)) {

        $term_meta  = $gmDB->get_metadata('gmedia_term', $term->term_id);
        $term_meta  = array_map('reset', $term_meta);
        $term_meta  = array_merge(array('_cover' => '', '_orderby' => 'ID', '_order' => 'DESC'), $term_meta);
        $per_page   = ! empty($gm_screen_options['per_page_sort_gmedia']) ? $gm_screen_options['per_page_sort_gmedia'] : 60;
        $cur_page   = $gmCore->_get('pager', 1);
        $pager_html = '';

        $termItems = array();
        if ($term->count) {
            $args      = array(
                'album__in' => $term->term_id,
                'orderby'   => $term_meta['_orderby'],
                'order'     => $term_meta['_order'],
                'per_page'  => $per_page,
                'page'      => $cur_page
            );
            $termItems = $gmDB->get_gmedias($args);

            $pager_html = $gmDB->query_pager();
        }

        ?>
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <div class="btn-toolbar pull-left">
                    <div class="btn-group" style="margin-right:20px;">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <?php _e('Return to') ?> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li class="active"><a href="<?php echo add_query_arg(array('term' => 'gmedia_album'), $url); ?>"><?php _e('Albums', 'grand-media'); ?></a></li>
                            <li><a href="<?php echo add_query_arg(array('term' => 'gmedia_tag'), $url); ?>"><?php _e('Tags', 'grand-media'); ?></a></li>
                            <li><a href="<?php echo add_query_arg(array('term' => 'gmedia_category'), $url); ?>"><?php _e('Categories', 'grand-media'); ?></a></li>
                            <li class="divider"></li>
                            <li><a href="<?php echo add_query_arg(array('term' => 'gmedia_filter'), $url); ?>"><?php _e('Custom Filters', 'grand-media'); ?></a></li>
                        </ul>
                    </div>

                    <div class="btn-group">
                        <a class="btn btn-default" href="#"><?php _e('Action', 'grand-media'); ?></a>
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                            <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="<?php echo add_query_arg(array( 'page' => 'GrandMedia', 'alb' => $term->term_id), admin_url('admin.php')); ?>"><?php _e('Show Album in Gmedia Library', 'grand-media'); ?></a> </li>
                        </ul>
                    </div>
                </div>

            </div>

            <form method="post" id="gmedia-edit-term" name="gmEditTerm" class="panel-body" data-id="<?php echo $term->term_id; ?>">
                <h4 style="margin-top:0;">
                    <span class="pull-right"><?php echo __('ID', 'grand-media') . ": {$term->term_id}"; ?></span>
                    <?php _e('Edit Album'); ?>: <em><?php echo esc_html($term->name); ?></em>
                </h4>

                <div class="row">
                    <div class="col-xs-6">
                        <div class="form-group row">
                            <div class="col-xs-6">
                                <label><?php _e('Name', 'grand-media'); ?></label>
                                <input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr($term->name); ?>" placeholder="<?php _e('Album Name', 'grand-media'); ?>" required/>
                            </div>
                            <div class="col-xs-6">
                                <label><?php _e('Author', 'grand-media'); ?></label>
                                <?php $user_ids = $gmCore->caps['gmedia_delete_others_media'] ? $gmCore->get_editable_user_ids() : array($user_ID);
                                if ($user_ids && $gmCore->caps['gmedia_edit_others_media']) {
                                    if (! in_array($user_ID, $user_ids)) {
                                        array_push($user_ids, $user_ID);
                                    }
                                    $selected_user = get_the_author_meta('display_name', $term->global) ? $term->global : 0;
                                    wp_dropdown_users(array(
                                        'include'          => $user_ids,
                                        'include_selected' => true,
                                        'name'             => 'term[global]',
                                        'selected'         => $selected_user,
                                        'class'            => 'form-control input-sm',
                                        'multi'            => true,
                                        'show_option_all'  => __('Shared', 'grand-media')
                                    ));
                                } else {
                                    echo '<input type="hidden" name="term[global]" value="' . $user_ID . '"/>';
                                    echo '<div>' . get_the_author_meta('display_name', $user_ID) . '</div>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php _e('Description', 'grand-media'); ?></label>
                            <textarea class="form-control input-sm" style="height:53px;" rows="2" name="term[description]"><?php echo $term->description; ?></textarea>
                        </div>
                        <div class="text-right">
                            <?php wp_nonce_field('GmediaTerms', 'term_save_wpnonce'); ?>
                            <input type="hidden" name="term[term_id]" value="<?php echo $term->term_id; ?>"/>
                            <input type="hidden" name="term[taxonomy]" value="gmedia_album"/>
                            <button type="submit" class="btn btn-primary btn-sm" name="gmedia_album_save"><?php _e('Update', 'grand-media'); ?></button>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label><?php _e('Status', 'grand-media'); ?></label>
                                    <select name="term[status]" class="form-control input-sm">
                                        <option value="public"<?php selected($term->status, 'public'); ?>><?php _e('Public', 'grand-media'); ?></option>
                                        <option value="private"<?php selected($term->status, 'private'); ?>><?php _e('Private', 'grand-media'); ?></option>
                                        <option value="draft"<?php selected($term->status, 'draft'); ?>><?php _e('Draft', 'grand-media'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox"><label><input type="checkbox" name="term[status_global]" value="1"> <?php _e('Apply Status for all items in album', 'grand-media'); ?> </label></div>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label><?php _e('Album Cover', 'grand-media'); ?></label>
                                    <input type="text" class="form-control input-sm" name="term[meta][_cover]" value="<?php echo esc_attr($term_meta['_cover']); ?>" placeholder="<?php _e('Gmedia Image ID', 'grand-media'); ?>"/>
                                </div>
                                <?php if ($term_meta['_cover']) {
                                    $cover_id = intval($term_meta['_cover']);
                                    if (($cover = $gmDB->get_gmedia($cover_id))) { ?>
                                        <div class="gm-img-thumbnail" data-gmid="<?php echo $cover->ID; ?>"><?php
                                            ?><img src="<?php echo $gmCore->gm_get_media_image($cover, 'thumb', true); ?>" alt="<?php echo $cover->ID; ?>" title="<?php echo esc_attr($cover->title); ?>"/><?php
                                            ?><span class="label label-default">ID: <?php echo $cover->ID; ?></span><?php
                                            ?></div>
                                    <?php } else {
                                        echo '<strong class="text-danger">' . __('No image with such ID', 'grand-media') . '</strong>';
                                    }
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <hr/>
                <?php
                $gmCore->gmedia_custom_meta_box($term->term_id, $meta_type = 'gmedia_term');
                do_action('gmedia_term_edit_form');
                ?>
            </form>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading clearfix">
                <h4 style="margin:7px 0;" class="pull-left"><?php _e('Sort Album'); ?></h4>

                <?php echo $pager_html; ?>

            </div>
            <form method="post" id="gmedia-sort-term" name="gmSortTerm" class="panel-body">
                <div class="order-form" style="border-bottom:1px solid #ddd; margin-bottom:15px;">
                    <div class="row">
                        <div class="col-xs-3">
                            <div class="form-group">
                                <label><?php _e('Order gmedia', 'grand-media'); ?></label>
                                <select name="term[meta][_orderby]" id="gmedia_term_orderby" class="form-control input-sm">
                                    <option value="custom"<?php selected($term_meta['_orderby'], 'custom'); ?>><?php _e('Custom Order', 'grand-media'); ?></option>
                                    <option value="ID"<?php selected($term_meta['_orderby'], 'ID'); ?>><?php _e('by ID', 'grand-media'); ?></option>
                                    <option value="title"<?php selected($term_meta['_orderby'], 'title'); ?>><?php _e('by title', 'grand-media'); ?></option>
                                    <option value="gmuid"<?php selected($term_meta['_orderby'], 'gmuid'); ?>><?php _e('by filename', 'grand-media'); ?></option>
                                    <option value="date"<?php selected($term_meta['_orderby'], 'date'); ?>><?php _e('by date', 'grand-media'); ?></option>
                                    <option value="modified"<?php selected($term_meta['_orderby'], 'modified'); ?>><?php _e('by last modified date', 'grand-media'); ?></option>
                                    <option value="rand"<?php selected($term_meta['_orderby'], 'rand'); ?>><?php _e('Random', 'grand-media'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-3">
                            <div class="form-group">
                                <label><?php _e('Sort order', 'grand-media'); ?></label>
                                <select id="gmedia_term_order" name="term[meta][_order]" class="form-control input-sm">
                                    <option value="DESC"<?php selected($term_meta['_order'], 'DESC'); ?>><?php _e('DESC', 'grand-media'); ?></option>
                                    <option value="ASC"<?php selected($term_meta['_order'], 'ASC'); ?>><?php _e('ASC', 'grand-media'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group pull-left" style="margin-right:30px;">
                                <label style="visibility:hidden;">-</label>

                                <div class="checkbox"><label><input id="reset_order_option" type="checkbox" name="term[reset_custom_order]" value="1"> <?php _e('Reset custom order', 'grand-media'); ?> </label></div>
                            </div>
                            <div class="form-group pull-left">
                                <label style="visibility:hidden;">-</label>
                                <button style="display:block" type="submit" class="btn btn-primary btn-sm" name="gmedia_term_sort_save"><?php _e('Update', 'grand-media'); ?></button>
                            </div>
                            <?php wp_nonce_field('GmediaTerms', 'term_save_wpnonce'); ?>
                            <input type="hidden" name="term[term_id]" value="<?php echo $term->term_id; ?>"/>
                            <input type="hidden" name="term[taxonomy]" value="gmedia_album"/>
                        </div>
                    </div>
                    <p><?php _e('Use your mouse (drag&drop) for custom sorting of images or manually enter index number in top right field of each image.') ?></p>
                </div>
                <div class="termItems clearfix" id="termItems">
                    <?php if (! empty($termItems)) {
                        foreach ($termItems as $item) {
                            ?>
                            <div class="gm-img-thumbnail" data-gmid="<?php echo $item->ID; ?>"><?php
                                ?><img src="<?php echo $gmCore->gm_get_media_image($item, 'thumb', false); ?>" alt="<?php echo $item->ID; ?>" title="<?php echo esc_attr($item->title); ?>"/><?php
                                ?><input type="text" name="term[gmedia_ids][<?php echo $item->ID; ?>]" value="<?php echo isset($item->gmedia_order) ? $item->gmedia_order : '0'; ?>"/><?php
                                ?><span class="label label-default">ID: <?php echo $item->ID; ?></span><?php
                                ?><div class="gm-img-thumb-title"><?php echo esc_html($item->title); ?></div>
                            </div>
                            <?php
                        }
                    } ?>

                </div>
            </form>
            <div class="panel-footer clearfix" style="margin-top:20px;"><?php echo $pager_html; ?>
                <div class="well well-sm pull-left" style="margin:0;"><?php printf(__('Total items: %d'), $term->count); ?></div>
            </div>

            <script type="text/javascript">
                jQuery(function ($) {
                    var sortdiv = $('#termItems');
                    var items = $('.gm-img-thumbnail', sortdiv);

                    sortdiv.sortable({
                        items: '.gm-img-thumbnail',
                        handle: 'img',
                        placeholder: 'gm-img-thumbnail ui-highlight-placeholder',
                        forcePlaceholderSize: true,
                        //revert: true,
                        stop: function (event, ui) {
                            $('#gmedia_term_orderby').val('custom');
                            var cur_order, prev_order, next_order;
                            var self = ui.item,
                                prev_item = self.prev(),
                                next_item = self.next();
                            prev_order = prev_item.length ? parseInt($('input', prev_item).val()) : 0;
                            var img_order_asc = ('ASC' == $('#gmedia_term_order').val());
                            if (img_order_asc) {
                                cur_order = prev_order + 1;
                                $('input', self).val(cur_order);
                                while (next_item.length) {
                                    next_order = parseInt(next_item.find('input').val());
                                    if (cur_order < next_order) {
                                        break;
                                    }
                                    cur_order += 1;
                                    next_item.find('input').val(cur_order);
                                    next_item = next_item.next();
                                }

                            } else {
                                next_order = next_item.length ? parseInt($('input', next_item).val()) : (prev_order ? (prev_order - 1) : 0);
                                cur_order = next_order + 1;
                                $('input', self).val(cur_order);
                                while (prev_item.length) {
                                    prev_order = parseInt(prev_item.find('input').val());
                                    if (cur_order < prev_order) {
                                        break;
                                    }
                                    cur_order += 1;
                                    prev_item.find('input').val(cur_order);
                                    prev_item = prev_item.prev();
                                }
                            }
                        }
                    });

                    $('input', items).on('change', function () {
                        $('#gmedia_term_orderby').val('custom');
                        sortdiv.css({height: sortdiv.height()});
                        var items = $('.gm-img-thumbnail', sortdiv);

                        var new_order = $.isNumeric($(this).val()) ? parseInt($(this).val()) : -1;
                        $(this).val(new_order).closest('.gm-img-thumbnail').css({zIndex: 1000});

                        var ipos = [];
                        items.each(function (i, el) {
                            var pos = $(el).position();
                            $.data(el, 'pos', pos);
                            ipos[i] = pos;
                        });

                        var img_order_asc = ('ASC' == $('#gmedia_term_order').val());
                        var order = img_order_asc ? 'asc' : 'desc';
                        items.tsort('input', {
                            useVal: true,
                            order: order
                        }, 'span.label', {order: order}).each(function (i, el) {
                            var from = $.data(el, 'pos');
                            var to = ipos[i];
                            $(el).css({position: 'absolute', top: from.top, left: from.left}).animate({
                                top: to.top,
                                left: to.left
                            }, 500);
                        }).promise().done(function () {
                            items.removeAttr('style');
                            sortdiv.removeAttr('style');
                        });

                        $(this).val(((0 > new_order) ? 0 : new_order));
                    });
                });
            </script>

        </div>


        <div class="modal fade gmedia-modal" id="newCustomFieldModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php _e('Add New Custom Field'); ?></h4>
                    </div>
                    <form class="modal-body" method="post" id="newCustomFieldForm">
                        <?php
                        echo $gmCore->meta_form($meta_type = 'gmedia_term');
                        wp_nonce_field('gmedia_custom_field', '_customfield_nonce');
                        ?>
                        <input type="hidden" name="action" value="gmedia_term_add_custom_field"/>
                        <input type="hidden" class="newcustomfield-for-id" name="ID" value=""/>
                    </form>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary customfieldsubmit"><?php _e('Add', 'grand-media'); ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'grand-media'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {

    }
}

/**
 * gmediaFilterEdit()
 *
 * @return mixed content
 */
function gmediaFilterEdit()
{
    global $gmDB, $gmCore, $gmProcessor, $gmGallery, $user_ID;

    if (! $gmCore->caps['gmedia_filter_manage']) {
        die('-1');
    }

    $url = add_query_arg(array('page' => $gmProcessor->page), admin_url('admin.php'));

    /*$gm_screen_options = get_user_meta($user_ID, 'gm_screen_options', true);
	if(!is_array($gm_screen_options)){
		$gm_screen_options = array();
	}
	$gm_screen_options = array_merge($gmGallery->options['gm_screen_options'], $gm_screen_options);*/
    //$per_page = !empty($gm_screen_options['per_page_sort_gmedia'])? $gm_screen_options['per_page_sort_gmedia'] : 40;
    $per_page = 40;
    $cur_page = $gmCore->_get('pager', 1);

    $taxonomy = 'gmedia_filter';
    $term_id  = (int)$gmCore->_get('edit_filter');

    $author_new = false;
    if ($gmCore->caps['gmedia_edit_others_media']) {
        $author = (int)$gmCore->_get('author', $user_ID);
    } else {
        $author = $user_ID;
    }

    $pager_html = '';
    $term       = array(
        'name'        => '',
        'description' => '',
        'global'      => $author
    );

    $filter_data = array(
         'author__in'       => array()
        ,'author__not_in'   => array()
        ,'category__in'     => array() // use category id. Same as 'cat', but does not accept negative values
        ,'category__not_in' => array() // use category id. Exclude multiple categories
        ,'album__in'        => array() // use album id. Same as 'alb'
        ,'album__not_in'    => array() // use album id. Exclude multiple albums
        ,'tag__and'         => array() // use tag ids. Display posts that are tagged with all listed tags in array
        ,'tag__in'          => array() // use tag ids. To display posts from either tags listed in array. Same as 'tag'
        ,'tag__not_in'      => array() // use tag ids. Display posts that do not have any of the listed tag ids
        ,'terms_relation'   => '' //  allows you to describe the boolean relationship between the taxonomy queries. Possible values are 'OR', 'AND'. Default 'AND'
        ,'gmedia__in'       => array() // use gmedia ids. Specify posts to retrieve
        ,'gmedia__not_in'   => array() // use gmedia ids. Specify post NOT to retrieve
        ,'mime_type'        => array() // mime types

        ,'per_page'         => '' // (int) - set limit
        ,'order'            => '' // Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'
        ,'orderby'          => '' // Sort retrieved posts by parameter. Defaults to 'ID'
        ,'year'             => '' // (int) - 4 digit year
        ,'monthnum'         => '' // (int) - Month number (from 1 to 12)
        ,'day'              => '' // (int) - Day of the month (from 1 to 31)

        ,'meta_query'       => array(
            array(
                'key'     => '',
                'value'   => '',
                'compare' => '',
                'type'    => ''
            )
        )
        ,'s'                => '' // (string) - search string or terms separated by comma
        ,'exact'            => false // Search exactly string if 'exact' parameter set to true

    );

    $filter_variable_data = $term_query = array(
        'cache_results' => false,
        'page'          => $cur_page, // number of page. Show the posts that would normally show up just on page X.
        'per_page'      => $per_page // number of post to displace or pass over. Note: Setting offset parameter will ignore the 'page' parameter.
    );

    $filter_form_custom_data = array(
        'gmedia_album'         => array(),
        'gmedia_category'      => array(),
        'gmedia_tag'           => array(),
        'gmedia_id'            => array(),
        'author_id'            => array(),
        'album__condition'     => 'album__in',
        'category__condition'  => 'category__in',
        'tag__condition'       => 'tag__in',
        'gmedia_id__condition' => 'gmedia__in',
        'author_id__condition' => 'author__in'
    );
    /**
     * @var $gmedia_album
     * @var $gmedia_category
     * @var $gmedia_tag
     * @var $gmedia_id
     * @var $author_id
     * @var $album__condition
     * @var $category__condition
     * @var $tag__condition
     * @var $gmedia_id__condition
     * @var $author_id__condition
     */
    extract($filter_form_custom_data);

    $totalResult = 0;
    $limit       = 0;
    if ($term_id) {
        $term = $gmDB->get_term($term_id, $taxonomy, ARRAY_A);
        if (! empty($term) && ! is_wp_error($term)) {

            $term_query  = $gmDB->get_metadata('gmedia_term', $term['term_id'], '_query', true);
            $filter_data = array_merge($filter_data, $term_query);

            if (isset($term_query['per_page'])) {
                //$term_query['offset'] = 0;
                $limit = $term_query['per_page'];
                unset($term_query['per_page']);
            }
            $term_query = array_merge($filter_variable_data, $term_query);

            if (isset($_GET['author']) && ($term['global'] != $author)) {
                $filter_data['_query']['gmedia_album'] = array();
                $term['global']                        = $author;
                $author_new                            = true;
            }

            if (! empty($filter_data['album__not_in'])) {
                $album__condition = 'album__not_in';
            }
            $gmedia_album = $filter_data[$album__condition];

            if (! empty($filter_data['category__not_in'])) {
                $category__condition = 'category__not_in';
            }
            $gmedia_category = $filter_data[$category__condition];

            if (! empty($filter_data['tag__not_in'])) {
                $tag__condition = 'tag__not_in';
            } elseif (! empty($filter_data['tag__and'])) {
                $tag__condition = 'tag__and';
            }
            $gmedia_tag = $filter_data[$tag__condition];

            if (! empty($filter_data['gmedia__not_in'])) {
                $gmedia_id__condition = 'gmedia__not_in';
            }
            $gmedia_id = $filter_data[$gmedia_id__condition];

            if (! empty($filter_data['author__not_in'])) {
                $author_id__condition = 'author__not_in';
            }
            $author_id = $filter_data[$author_id__condition];

            $termItems   = $gmDB->get_gmedias($term_query);
            $totalResult = (int)$gmDB->totalResult;
            if (! $totalResult && ! empty($termItems)) {
                $totalResult = count($termItems);
            }

            if (! empty($termItems)) {
                $pager_html = $gmDB->query_pager();
            }
        } else {
            $term_id = 0;
        }
    }

    ?>
    <div class="panel panel-default">
        <div class="panel-heading clearfix">
            <div class="btn-toolbar pull-left">
                <div class="btn-group" style="margin-right:20px;">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <?php _e('Return to') ?> <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="<?php echo add_query_arg(array('term' => 'gmedia_album'), $url); ?>"><?php _e('Albums', 'grand-media'); ?></a></li>
                        <li><a href="<?php echo add_query_arg(array('term' => 'gmedia_tag'), $url); ?>"><?php _e('Tags', 'grand-media'); ?></a></li>
                        <li><a href="<?php echo add_query_arg(array('term' => 'gmedia_category'), $url); ?>"><?php _e('Categories', 'grand-media'); ?></a></li>
                        <li class="divider"></li>
                        <li class="active"><a href="<?php echo add_query_arg(array('term' => 'gmedia_filter'), $url); ?>"><?php _e('Custom Filters', 'grand-media'); ?></a></li>
                    </ul>
                </div>

                <?php if ($term_id) { ?>
                    <div class="btn-group">
                        <a class="btn btn-default" href="#"><?php _e('Action', 'grand-media'); ?></a>
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                            <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="<?php echo add_query_arg(array(
                                    'page'          => 'GrandMedia',
                                    'custom_filter' => $term['term_id']
                                ), admin_url('admin.php')); ?>"><?php _e('Show Filter in Gmedia Library', 'grand-media'); ?></a>
                            </li>
                        </ul>
                    </div>
                <?php } ?>
            </div>

        </div>

        <form method="post" id="gmedia-edit-term" name="gmEditTerm" class="panel-body">
            <h4 style="margin-top:0;">
                <?php if ($term_id) { ?>
                    <span class="pull-right"><?php echo __('ID', 'grand-media') . ": {$term['term_id']}"; ?></span>
                    <?php _e('Edit Filter'); ?>: <em><?php echo esc_html($term['name']); ?></em>
                <?php } else {
                    _e('Create Filter');
                } ?>
            </h4>

            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        <label><?php _e('Filter Name', 'grand-media'); ?></label>
                        <input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr($term['name']); ?>" placeholder="<?php _e('Filter Name', 'grand-media'); ?>" required/>
                    </div>
                    <div class="form-group pull-right">
                        <?php
                        wp_nonce_field('GmediaGallery');
                        wp_nonce_field('GmediaTerms', 'term_save_wpnonce');
                        ?>
                        <input type="hidden" name="term[taxonomy]" value="gmedia_filter"/>
                        <input type="hidden" name="term[term_id]" value="<?php echo $term_id; ?>"/>
                        <button type="submit" class="btn btn-primary btn-sm" name="gmedia_filter_save"><?php _e('Save', 'grand-media'); ?></button>
                    </div>
                    <p><b><?php _e('Filter Author:', 'grand-media'); ?></b>
                        <?php if ($gmCore->caps['gmedia_delete_others_media']) { ?>
                            <a href="#gallModal" data-modal="select_author" data-action="gmedia_get_modal" class="gmedia-modal" title="<?php _e('Click to choose author for gallery', 'grand-media'); ?>"><?php echo $term['global'] ? get_the_author_meta('display_name', $term['global']) : __('(no author / shared albums)'); ?></a>
                            <?php if ($author_new) {
                                echo '<br /><span class="text-danger">' . __('Note: Author changed but not saved yet. You can see Albums list only of chosen author') . '</span>';
                            } ?>
                        <?php } else {
                            echo $term['global'] ? get_the_author_meta('display_name', $term['global']) : '&#8212;';
                        } ?>
                        <input type="hidden" name="term[global]" value="<?php echo $term['global']; ?>"/></p>

                </div>
                <div class="col-xs-6">
                    <div class="form-group">
                        <label><?php _e('Description', 'grand-media'); ?></label>
                        <textarea class="form-control input-sm" style="height:77px;" rows="2" name="term[description]"><?php echo $term['description']; ?></textarea>
                    </div>
                </div>
            </div>
            <hr/>
            <h4 style="margin-top:0;"><?php _e('Query Parameters'); ?></h4>

            <?php if ($gmCore->caps['gmedia_terms']) { ?>
                <div class="form-group">
                    <?php
                    $term_type = 'gmedia_album';
                    $args      = array();
                    if ($term['global']) {
                        if (user_can($term['global'], 'gmedia_edit_others_media')) {
                            $args['global'] = '';
                        } else {
                            $args['global'] = array(0, $term['global']);
                        }
                    } else {
                        $args['global'] = 0;
                    }
                    $gm_terms = $gmDB->get_terms($term_type, $args);

                    $terms_items = '';
                    if (count($gm_terms)) {
                        foreach ($gm_terms as $_term) {
                            $selected = (in_array($_term->term_id, $gmedia_album)) ? ' selected="selected"' : '';
                            $terms_items .= '<option value="' . $_term->term_id . '"' . $selected . '>' . esc_html($_term->name) . ('public' == $_term->status ? '' : " [{$_term->status}]") . ' &nbsp; (' . $_term->count . ')</option>' . "\n";
                        }
                    }
                    $setvalue = ! empty($gmedia_album) ? 'data-setvalue="' . implode(',', $gmedia_album) . '"' : '';
                    ?>
                    <label><?php _e('Choose Albums', 'grand-media'); ?> </label>

                    <div class="row">
                        <div class="col-xs-8">
                            <select <?php echo $setvalue; ?> id="gmedia_album" name="filter_data[gmedia_album][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Any Album...', 'grand-media')); ?>">
                                <option value=""<?php if (empty($gmedia_album)) {
                                    echo ' selected="selected"';
                                } ?>><?php _e('Any Album...', 'grand-media'); ?></option>
                                <?php echo $terms_items; ?>
                            </select>
                        </div>
                        <div class="col-xs-4">
                            <select name="filter_data[album__condition]" class="form-control input-sm">
                                <option <?php selected($album__condition, 'album__in'); ?> value="album__in"><?php _e('get albums', 'grand-media'); ?></option>
                                <option <?php selected($album__condition, 'album__not_in'); ?> value="album__not_in"><?php _e('exclude albums', 'grand-media'); ?></option>
                            </select>
                        </div>
                    </div>
                    <p class="help-block"><?php _e('You can choose Albums from the same author as Gallery author or Albums without author', 'grand-media'); ?></p>
                </div>

                <div class="form-group">
                    <?php
                    $term_type    = 'gmedia_category';
                    $gm_terms_all = $gmGallery->options['taxonomies'][$term_type];
                    $gm_terms     = $gmDB->get_terms($term_type, array('fields' => 'names_count'));

                    $terms_items = '';
                    if (count($gm_terms)) {
                        foreach ($gm_terms as $id => $_term) {
                            $selected = (in_array($id, $gmedia_category)) ? ' selected="selected"' : '';
                            $terms_items .= '<option value="' . $id . '"' . $selected . '>' . esc_html($gm_terms_all[$_term['name']]) . ' (' . $_term['count'] . ')</option>' . "\n";
                        }
                    }
                    $setvalue = ! empty($gmedia_category) ? 'data-setvalue="' . implode(',', $gmedia_category) . '"' : '';
                    ?>
                    <label><?php _e('Choose Categories', 'grand-media'); ?></label>

                    <div class="row">
                        <div class="col-xs-8">
                            <select <?php echo $setvalue; ?> id="gmedia_category" name="filter_data[gmedia_category][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Any Category...', 'grand-media')); ?>">
                                <option value=""<?php echo empty($gmedia_category)? ' selected="selected"' : ''; ?>><?php _e('Any Category...', 'grand-media'); ?></option>
                                <?php echo $terms_items; ?>
                            </select>
                        </div>
                        <div class="col-xs-4">
                            <select name="filter_data[category__condition]" class="form-control input-sm">
                                <option <?php selected($category__condition, 'category__in'); ?> value="category__in"><?php _e('get categories', 'grand-media'); ?></option>
                                <option <?php selected($category__condition, 'category__not_in'); ?> value="category__not_in"><?php _e('exclude categories', 'grand-media'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <?php
                    $term_type = 'gmedia_tag';
                    $gm_terms  = $gmDB->get_terms($term_type, array('fields' => 'names_count'));

                    $terms_items = '';
                    if (count($gm_terms)) {
                        foreach ($gm_terms as $id => $_term) {
                            $selected = (in_array($id, $gmedia_tag)) ? ' selected="selected"' : '';
                            $terms_items .= '<option value="' . $id . '"' . $selected . '>' . esc_html($_term['name']) . ' (' . $_term['count'] . ')</option>' . "\n";
                        }
                    }
                    $setvalue = ! empty($gmedia_tag) ? 'data-setvalue="' . implode(',', $gmedia_tag) . '"' : '';
                    ?>
                    <label><?php _e('Choose Tags', 'grand-media'); ?> </label>

                    <div class="row">
                        <div class="col-xs-8">
                            <select <?php echo $setvalue; ?> id="gmedia_tag" name="filter_data[gmedia_tag][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Any Tag...', 'grand-media')); ?>">
                                <option value=""<?php echo empty($gmedia_tag)? ' selected="selected"' : ''; ?>><?php _e('Any Tag...', 'grand-media'); ?></option>
                                <?php echo $terms_items; ?>
                            </select>
                        </div>
                        <div class="col-xs-4">
                            <select name="filter_data[tag__condition]" class="form-control input-sm">
                                <option <?php selected($tag__condition, 'tag__in'); ?> value="tag__in"><?php _e('get items with either tags', 'grand-media'); ?></option>
                                <option <?php selected($tag__condition, 'tag__and'); ?> value="tag__and"><?php _e('get items that have all listed tags', 'grand-media'); ?></option>
                                <option <?php selected($tag__condition, 'tag__not_in'); ?> value="tag__not_in"><?php _e('exclude items that have any of the listed tags', 'grand-media'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

            <?php } ?>
            <div class="form-group">
                <label><?php _e('Terms Relation', 'grand-media'); ?> </label>

                <div class="row">
                    <div class="col-xs-4">
                        <select name="gmedia_filter[terms_relation]" class="form-control input-sm">
                            <option <?php selected($filter_data['terms_relation'], ''); ?> value=""><?php _e('AND'); ?></option>
                            <option <?php selected($filter_data['terms_relation'], 'OR'); ?> value="OR"><?php _e('OR'); ?></option>
                        </select>
                    </div>
                    <div class="col-xs-8">
                        <p class="help-block"><?php _e('allows you to describe the relationship between the taxonomy queries', 'grand-media'); ?></p>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label><?php _e('Search', 'grand-media'); ?></label>

                <div class="row">
                    <div class="col-xs-8">
                        <input type="text" class="form-control input-sm" placeholder="<?php _e('Search string or terms separated by comma', 'grand-media'); ?>" value="<?php echo $filter_data['s']; ?>" name="gmedia_filter[s]">
                    </div>
                    <div class="col-xs-4">
                        <div class="checkbox"><label><input type="checkbox" name="gmedia_filter[exact]" value="yes"<?php echo $filter_data['exact']? ' checked="checked"' : ''; ?> /> <?php _e('Search exactly string', 'grand-media'); ?></label></div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-8">
                        <div class="pull-right"><a id="use_lib_selected" class="label label-primary" href="#libselected"><?php _e('Use selected in Library', 'grand-media'); ?></a> </div>
                        <label><?php _e('Gmedia IDs <small class="text-muted">separated by comma</small>', 'grand-media'); ?> </label>
                        <?php $value = ! empty($gmedia_id) ? implode(',', wp_parse_id_list($gmedia_id)) : ''; ?>
                        <textarea id="gmedia__ids" name="filter_data[gmedia_id]" rows="1" class="form-control input-sm" style="resize:vertical;" placeholder="<?php echo esc_attr(__('Gmedia IDs...', 'grand-media')); ?>"><?php echo $value; ?></textarea>
                    </div>
                    <div class="col-xs-4">
                        <label>&nbsp;</label>
                        <select name="filter_data[gmedia_id__condition]" class="form-control input-sm">
                            <option <?php selected($gmedia_id__condition, 'gmedia__in'); ?> value="gmedia__in"><?php _e('get gmedia IDs', 'grand-media'); ?></option>
                            <option <?php selected($gmedia_id__condition, 'gmedia__not_in'); ?> value="gmedia__not_in"><?php _e('exclude gmedia IDs', 'grand-media'); ?></option>
                        </select>
                    </div>
                </div>
                <p class="help-block"><?php _e('You can select items you want to add here right in Gmedia Library and then return here and click button "Use selected in Library"', 'grand-media'); ?></p>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-4">
                        <label><?php _e('Mime Type', 'grand-media'); ?> </label>
                        <select name="gmedia_filter[mime_type][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('All types...', 'grand-media')); ?>">
                            <option value=""><?php _e('All types...', 'grand-media'); ?></option>
                            <option <?php echo in_array('image', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="image"><?php _e('Image', 'grand-media'); ?></option>
                            <option <?php echo in_array('audio', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="audio"><?php _e('Audio', 'grand-media'); ?></option>
                            <option <?php echo in_array('video', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="video"><?php _e('Video', 'grand-media'); ?></option>
                            <option <?php echo in_array('text', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="text"><?php _e('Text', 'grand-media'); ?></option>
                            <option <?php echo in_array('application', $filter_data['mime_type'])? 'selected="selected"' : ''; ?> value="application"><?php _e('Application', 'grand-media'); ?></option>
                        </select>
                    </div>
                    <div class="col-xs-4">
                        <label><?php _e('Authors', 'grand-media'); ?></label>
                        <?php if ($gmCore->caps['gmedia_show_others_media']) {
                            $user_ids = $gmCore->get_editable_user_ids();
                            if (! in_array($user_ID, $user_ids)) {
                                array_push($user_ids, $user_ID);
                            }
                            $filter_users = get_users(array('include' => $user_ids));
                            $users        = '';
                            if (count($filter_users)) {
                                foreach ((array)$filter_users as $user) {
                                    $user->ID  = (int)$user->ID;
                                    $_selected = in_array($user->ID, $author_id) ? ' selected="selected"' : '';
                                    $users .= "<option value='$user->ID'$_selected>" . esc_html($user->display_name) . "</option>";
                                }
                            }
                            $setvalue = ! empty($author_id) ? 'data-setvalue="' . implode(',', $author_id) . '"' : '';
                            ?>
                            <select <?php echo $setvalue; ?> name="filter_data[author_id][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('All authors...', 'grand-media')); ?>">
                                <option value=""><?php _e('All authors...', 'grand-media'); ?></option>
                                <?php echo $users; ?>
                            </select>
                        <?php } else { ?>
                            <input type="text" readonly="readonly" name="filter_data[author_id][]" class="gmedia-combobox form-control input-sm" value="<?php the_author_meta('display_name', $user_ID); ?>"/>
                            <input type="hidden" name="filter_data[author_id__condition]" value="author__in"/>
                        <?php } ?>
                    </div>
                    <?php if ($gmCore->caps['gmedia_show_others_media']) { ?>
                        <div class="col-xs-4">
                            <label>&nbsp;</label>
                            <select name="filter_data[author_id__condition]" class="form-control input-sm">
                                <option <?php selected($author_id__condition, 'author__in'); ?> value="author__in"><?php _e('get authors', 'grand-media'); ?></option>
                                <option <?php selected($author_id__condition, 'author__not_in'); ?> value="author__not_in"><?php _e('exclude authors', 'grand-media'); ?></option>
                            </select>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-4">
                        <label><?php _e('Year', 'grand-media'); ?></label>
                        <input type="text" class="form-control input-sm" placeholder="<?php _e('4 digit year e.g. 2011', 'grand-media'); ?>" value="<?php echo $filter_data['year']; ?>" name="gmedia_filter[year]">
                    </div>
                    <div class="col-xs-4">
                        <label><?php _e('Month', 'grand-media'); ?></label>
                        <input type="text" class="form-control input-sm" placeholder="<?php _e('from 1 to 12', 'grand-media'); ?>" value="<?php echo $filter_data['monthnum']; ?>" name="gmedia_filter[monthnum]">
                    </div>
                    <div class="col-xs-4">
                        <label><?php _e('Day', 'grand-media'); ?></label>
                        <input type="text" class="form-control input-sm" placeholder="<?php _e('from 1 to 31', 'grand-media'); ?>" value="<?php echo $filter_data['day']; ?>" name="gmedia_filter[day]">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <?php foreach ($filter_data['meta_query'] as $i => $q) {
                    if ($i) {
                        continue;
                    }
                    ?>
                    <div class="row">
                        <div class="col-xs-6 col-sm-3">
                            <label><?php _e('Custom Field Key', 'grand-media'); ?></label>
                            <input type="text" class="form-control input-sm" value="<?php echo $q['key']; ?>" name="gmedia_filter[meta_query][<?php echo $i; ?>][key]">
                            <span class="help-block"><?php _e('Display items with this field key', 'grand-media'); ?></span>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <label><?php _e('Custom Field Value', 'grand-media'); ?></label>
                            <input type="text" class="form-control input-sm" value="<?php echo $q['value']; ?>" name="gmedia_filter[meta_query][<?php echo $i; ?>][value]">
                            <span class="help-block"><?php _e('Display items with this field value', 'grand-media'); ?></span>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <label><?php _e('Compare Operator', 'grand-media'); ?></label>
                            <select class="form-control input-sm" name="gmedia_filter[meta_query][<?php echo $i; ?>][compare]">
                                <option value=""><?php _e('Choose..', 'grand-media'); ?></option>
                                <option <?php selected($q['compare'], '='); ?> value="=">= (<?php _e('Default', 'grand-media'); ?>)</option>
                                <option <?php selected($q['compare'], '!='); ?> value="!=">!=</option>
                                <option <?php selected($q['compare'], '>'); ?> value="&gt;">&gt;</option>
                                <option <?php selected($q['compare'], '>='); ?> value="&gt;=">&gt;=</option>
                                <option <?php selected($q['compare'], '<'); ?> value="&lt;">&lt;</option>
                                <option <?php selected($q['compare'], '<='); ?> value="&lt;=">&lt;=</option>
                                <option <?php selected($q['compare'], 'LIKE'); ?> value="LIKE">LIKE</option>
                                <option <?php selected($q['compare'], 'NOT LIKE'); ?> value="NOT LIKE">NOT LIKE</option>
                                <?php /* ?>
							<option <?php selected($q['compare'], 'IN'); ?> value="IN">IN</option>
							<option <?php selected($q['compare'], 'NOT IN'); ?> value="NOT IN">NOT IN</option>
							<option <?php selected($q['compare'], 'BETWEEN'); ?> value="BETWEEN">BETWEEN</option>
							<option <?php selected($q['compare'], 'NOT BETWEEN'); ?> value="NOT BETWEEN">NOT BETWEEN</option>
							<?php */ ?>
                                <option <?php selected($q['compare'], 'EXISTS'); ?> value="EXISTS">EXISTS</option>
                            </select>
                            <span class="help-block"><?php _e('Operator to test the field value', 'grand-media'); ?></span>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <label><?php _e('Meta Type', 'grand-media'); ?></label>
                            <select class="form-control input-sm" name="gmedia_filter[meta_query][<?php echo $i; ?>][type]">
                                <option value=""><?php _e('Choose..', 'grand-media'); ?></option>
                                <option <?php selected($q['type'], 'NUMERIC'); ?> value="NUMERIC">NUMERIC</option>
                                <option <?php selected($q['type'], 'BINARY'); ?> value="BINARY">BINARY</option>
                                <option <?php selected($q['type'], 'DATE'); ?> value="DATE">DATE</option>
                                <option <?php selected($q['type'], 'CHAR'); ?> value="CHAR">CHAR (<?php _e('Default', 'grand-media'); ?>)</option>
                                <option <?php selected($q['type'], 'DATETIME'); ?> value="DATETIME">DATETIME</option>
                                <option <?php selected($q['type'], 'DECIMAL'); ?> value="DECIMAL">DECIMAL</option>
                                <option <?php selected($q['type'], 'SIGNED'); ?> value="SIGNED">SIGNED</option>
                                <option <?php selected($q['type'], 'TIME'); ?> value="TIME">TIME</option>
                                <option <?php selected($q['type'], 'UNSIGNED'); ?> value="UNSIGNED">UNSIGNED</option>
                            </select>
                            <span class="help-block"><?php _e('Custom field type', 'grand-media'); ?></span>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-6 col-sm-3">
                        <label><?php _e('Order', 'grand-media'); ?></label>
                        <select class="form-control input-sm" name="gmedia_filter[order]">
                            <option value=""><?php _e('Choose..', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['order'], 'ASC'); ?> value="ASC"><?php _e('ASC', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['order'], 'DESC'); ?> value="DESC"><?php _e('DESC - Default', 'grand-media'); ?></option>
                        </select>
                        <span class="help-block"><?php _e('Ascending or Descending order', 'grand-media'); ?></span>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <label><?php _e('Order by', 'grand-media'); ?></label>
                        <select class="form-control input-sm" name="gmedia_filter[orderby]">
                            <option value=""><?php _e('Choose..', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'none'); ?> value="none"><?php _e('None', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'rand'); ?> value="rand"><?php _e('Random', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'id'); ?> value="id"><?php _e('ID', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'title'); ?> value="title"><?php _e('Title', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'gmuid'); ?> value="gmuid"><?php _e('Filename', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'date'); ?> value="date"><?php _e('Date - Default', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'modified'); ?> value="modified"><?php _e('Modified Date', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'author'); ?> value="author"><?php _e('Author', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'gmedia__in'); ?> value="gmedia__in"><?php _e('Selected Order', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'meta_value'); ?> value="meta_value"><?php _e('Custom Field Value', 'grand-media'); ?></option>
                            <option <?php selected($filter_data['orderby'], 'meta_value_num'); ?> value="meta_value_num"><?php _e('Custom Field Value (Numeric)', 'grand-media'); ?></option>
                        </select>
                        <span class="help-block"><?php _e('Sort retrieved posts by', 'grand-media'); ?></span>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <label><?php _e('Limit', 'grand-media'); ?></label>
                        <input type="text" class="form-control input-sm" value="<?php echo $filter_data['per_page']; ?>" name="gmedia_filter[per_page]" placeholder="<?php _e('leave empty for no limit', 'grand-media'); ?>">
                        <span class="help-block"><?php _e('Limit number of gmedia items', 'grand-media'); ?></span>
                    </div>
                    <div class="col-xs-6 col-sm-3 text-right">
                        <label style="display:block;">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm" name="gmedia_filter_save"><?php _e('Save', 'grand-media'); ?></button>
                    </div>
                </div>
            </div>
        </form>
        <script type="text/javascript">
            jQuery(function ($) {
                <?php if($gmCore->caps['gmedia_terms']){ ?>
                $('.gmedia-combobox').each(function () {
                    var select = $(this).selectize({
                        create: false,
                        hideSelected: true,
                        allowEmptyOption: true
                    });
                    var val = $(this).data('setvalue');
                    if (val) {
                        val = val.toString().split(',');
                        select[0].selectize.setValue(val);
                    }
                });
                <?php } ?>

                $('#use_lib_selected').on('click', function () {
                    var field = $('#gmedia__ids');
                    var valData = field.val().split(',');
                    var storedData = getStorage('gmuser_<?php echo $user_ID; ?>_');
                    storedData = storedData.get('library').split(',');
                    valData = $.grep(valData, function (e) {
                        return e;
                    });
                    $.each(storedData, function (i, id) {
                        if (!id) {
                            return true;
                        }
                        if ($.inArray(id, valData) === -1) {
                            valData.push(id);
                        }
                    });
                    field.val(valData.join(', '));
                });
            });

        </script>
    </div>

    <div class="panel panel-default" id="queryfilter">
        <div class="panel-heading clearfix">
            <h4 style="margin:7px 0;" class="pull-left"><?php _e('Query Filter'); ?></h4>
            <?php echo $pager_html; ?>
        </div>
        <div class="panel-body">
            <div class="termItems clearfix">
                <?php if (! empty($termItems)) {
                    $ic = ((int)$term_query['page'] - 1) * (int)$term_query['per_page'];
                    foreach ($termItems as $item) {
                        $ic++;
                        $item_class = '';
                        if ($limit && $limit < $ic) {
                            $item_class = ' item-after-limit';
                        }
                        ?>
                        <div class="gm-img-thumbnail<?php echo $item_class; ?>" data-gmid="<?php echo $item->ID; ?>"><?php
                            ?><img src="<?php echo $gmCore->gm_get_media_image($item, 'thumb', false); ?>" alt="<?php echo $item->ID; ?>" title="<?php echo esc_attr($item->title); ?>"/><?php
                            ?><span class="label label-default">ID: <?php echo $item->ID; ?></span><?php
                            ?><div class="gm-img-thumb-title"><?php echo esc_html($item->title); ?></div>
                        </div>
                        <?php
                    }
                } else {
                    if ($term_id) { ?>
                        <p class="text-center"><?php _e('No items with selected parameters.') ?></p>
                    <?php } else { ?>
                        <p class="text-center"><?php _e('Set Filter parameters and click Save button to test query.') ?></p>
                    <?php }
                } ?>
            </div>
        </div>
        <div class="panel-footer clearfix" style="margin-top:20px;"><?php echo $pager_html; ?>
            <?php if ($limit) { ?>
                <div class="well well-sm pull-left" style="margin-right:10px;"><?php printf(__('Limited to: %d'), $limit); ?></div>
            <?php } ?>
            <div class="well well-sm pull-left" style="margin:0;"><?php printf(__('Total items: %d'), $totalResult); ?></div>
        </div>
    </div>

    <?php if ($gmCore->caps['gmedia_edit_others_media']) { ?>
    <div class="modal fade gmedia-modal" id="gallModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog"></div>
    </div>
<?php } ?>

    <?php
}
