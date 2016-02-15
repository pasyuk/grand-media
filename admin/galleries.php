<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    die('You are not allowed to call this page directly.');
}

/**
 * gmediaGalleries()
 *
 * @return mixed content
 */
function gmediaGalleries()
{
    global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

    $url      = add_query_arg(array('page' => $gmProcessor->page), admin_url('admin.php'));
    $endpoint = $gmGallery->options['endpoint'];

    $gm_screen_options = get_user_meta($user_ID, 'gm_screen_options', true);
    if (! is_array($gm_screen_options)) {
        $gm_screen_options = array();
    }
    $gm_screen_options = array_merge($gmGallery->options['gm_screen_options'], $gm_screen_options);
    $orderby           = ! empty($gm_screen_options['orderby_gmedia_galleries']) ? $gm_screen_options['orderby_gmedia_galleries'] : 'name';
    $order             = ! empty($gm_screen_options['sortorder_gmedia_galleries']) ? $gm_screen_options['sortorder_gmedia_galleries'] : 'ASC';
    $per_page          = ! empty($gm_screen_options['per_page_gmedia_galleries']) ? $gm_screen_options['per_page_gmedia_galleries'] : 30;

    $filter         = ('selected' == $gmCore->_req('filter')) ? $gmProcessor->selected_items : null;
    $args           = array(
        'orderby'    => $gmCore->_get('orderby', $orderby),
        'order'      => $gmCore->_get('order', $order),
        'search'     => $gmCore->_get('s', ''),
        'number'     => $gmCore->_get('number', $per_page),
        'hide_empty' => 0,
        'page'       => $gmCore->_get('pager', 1),
        'include'    => $filter
    );
    $args['offset'] = ($args['page'] - 1) * $args['number'];

    if ($gmCore->caps['gmedia_edit_others_media']) {
        $args['global'] = $gmCore->_get('author', '');
    } else {
        $args['global'] = array($user_ID);
    }

    $taxonomy    = 'gmedia_gallery';
    $gmediaTerms = $gmDB->get_terms($taxonomy, $args);
    if (is_wp_error($gmediaTerms)) {
        echo $gmProcessor->alert('danger', $gmediaTerms->get_error_message());
        $gmediaTerms = array();
    }

    $modules = array(
            'phantom' => ''
            , 'phototravlr' => ''
            , 'realslider' => ''
            , 'mosaic' => ''
            , 'photobox' => ''
            , 'photomania' => ''
            , 'jq-mplayer' => ''
            , 'wp-videoplayer' => ''
            , 'photo-pro' => ''
            , 'optima' => ''
            , 'afflux' => ''
            , 'slider' => ''
            , 'green-style' => ''
            , 'photo-blog' => ''
            , 'minima' => ''
            , 'sphere' => ''
            , 'cube' => ''
            , 'flatwall' => ''
    );
    if (($plugin_modules = glob(GMEDIA_ABSPATH . 'module/*', GLOB_ONLYDIR | GLOB_NOSORT))) {
        foreach ($plugin_modules as $path) {
            $mfold           = basename($path);
            $modules[$mfold] = array(
                'module_name' => $mfold,
                'module_url'  => $gmCore->gmedia_url . "/module/{$mfold}",
                'module_path' => $path
            );
        }
    }
    if (($upload_modules = glob($gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/*', GLOB_ONLYDIR | GLOB_NOSORT))) {
        foreach ($upload_modules as $path) {
            $mfold           = basename($path);
            $modules[$mfold] = array(
                'module_name' => $mfold,
                'module_url'  => $gmCore->upload['url'] . "/{$gmGallery->options['folder']['module']}/{$mfold}",
                'module_path' => $path
            );
        }
    }
    $modules = array_filter($modules);
    ?>

    <div class="panel panel-default  panel-fixed-header">
        <div class="panel-heading-fake"></div>
        <div class="panel-heading clearfix">

            <?php

            include(dirname(__FILE__) . '/tpl/search-form.php');

            echo $gmDB->query_pager();
            ?>

            <div class="btn-toolbar pull-left">
                <div class="btn-group gm-checkgroup" id="cb_global-btn">
                    <span class="btn btn-default active"><input class="doaction" id="cb_global" data-group="cb_media-object" type="checkbox"/></span>
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
                            <a href="<?php echo wp_nonce_url($gmCore->get_admin_url(array('delete' => 'selected'), array('filter')), 'gmedia_delete') ?>" class="gmedia-delete" data-confirm="<?php _e("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media"); ?>"><?php _e('Delete Selected Items', 'grand-media'); ?></a>
                        </li>
                        <?php do_action('gmedia_term_action_list'); ?>
                    </ul>
                </div>

                <form class="btn-group" id="gm-selected-btn" name="gm-selected-form" action="<?php echo add_query_arg(array('filter' => 'selected'), $url); ?>" method="post">
                    <button type="submit" class="btn btn<?php echo ('selected' == $gmCore->_req('filter')) ? '-success' : '-info' ?>"><?php printf(__('%s selected', 'grand-media'), '<span id="gm-selected-qty">' . count($gmProcessor->selected_items) . '</span>'); ?></button>
                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown"><span class="caret"></span>
                        <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span></button>
                    <input type="hidden" id="gm-selected" data-userid="<?php echo $user_ID; ?>" data-key="<?php echo $taxonomy; ?>" name="selected_items" value="<?php echo implode(',', $gmProcessor->selected_items); ?>"/>
                    <ul class="dropdown-menu" role="menu">
                        <li><a id="gm-selected-show" href="#show"><?php _e('Show only selected items', 'grand-media'); ?></a></li>
                        <li><a id="gm-selected-clear" href="#clear"><?php _e('Clear selected items', 'grand-media'); ?></a></li>
                    </ul>
                </form>

            </div>

        </div>
        <div class="panel-body" id="gmedia-msg-panel"></div>
        <form class="list-group" id="gm-list-table" style="margin-bottom:4px;">
            <?php
            if (count($gmediaTerms)) {
                $lib_url = add_query_arg(array('page' => 'GrandMedia'), admin_url('admin.php'));
                foreach ($gmediaTerms as $term) {

                    $term_meta = $gmDB->get_metadata('gmedia_term', $term->term_id);
                    $term_meta = array_map('reset', $term_meta);
                    //$term_meta = array_map('maybe_unserialize', $term_meta);

                    $module      = $gmCore->get_module_path($term_meta['_module']);
                    $module_info = array('type' => '&#8212;');
                    if (file_exists($module['path'] . '/index.php')) {
                        $broken = false;
                        /** @noinspection PhpIncludeInspection */
                        include($module['path'] . '/index.php');
                    } else {
                        $broken = true;
                    }

                    if ($term->global == $user_ID) {
                        $allow_edit = $allow_delete = true;
                    } else {
                        $allow_edit = $allow_delete = $gmCore->caps['gmedia_edit_others_media'];
                    }

                    $is_selected = in_array($term->term_id, $gmProcessor->selected_items) ? true : false;

                    $list_row_class = '';
                    if ('publish' != $term->status) {
                        if ('private' == $term->status) {
                            $list_row_class = ' list-group-item-info';
                        } elseif ('draft' == $term->status) {
                            $list_row_class = ' list-group-item-warning';
                        }
                    }
                    ?>
                    <div class="cb_list-item list-group-item row d-row<?php echo $list_row_class . ($is_selected ? ' gm-selected' : ''); ?>" id="list-item-<?php echo $term->term_id; ?>" data-id="<?php echo $term->term_id; ?>" data-type="<?php echo $term_meta['_module']; ?>">
                        <div class="term_id">#<?php echo $term->term_id; ?></div>
                        <div class="col-xs-7">
                            <label class="cb_media-object cb_media-object-gallery">
                                <input name="doaction[]" type="checkbox"<?php echo $is_selected ? ' checked="checked"' : ''; ?> data-type="<?php echo $term_meta['_module']; ?>" value="<?php echo $term->term_id; ?>"/>
                            </label>

                            <div class="media-info-body" style="margin-left:35px;">
                                <p class="media-title">
                                    <?php if ($allow_edit) { ?>
                                        <a href="<?php echo add_query_arg(array('edit_gallery' => $term->term_id), $url); ?>"><?php echo esc_html($term->name); ?></a>
                                    <?php } else { ?>
                                        <span><?php echo esc_html($term->name); ?></span>
                                    <?php } ?>
                                </p>

                                <p class="media-meta">
									<span class="label label-default"><?php _e('Author', 'grand-media'); ?>:</span> <?php echo $term->global ? get_the_author_meta('display_name', $term->global) : '&#8212;'; ?>
                                </p>

                                <p class="media-caption"><?php echo nl2br(esc_html($term->description)); ?></p>

                                <p class="media-meta" title="<?php _e('Shortcode', 'grand-media'); ?>" style="font-weight:bold">
                                    <span class="label label-default"><?php _e('Shortcode', 'grand-media'); ?>:</span> [gmedia id=<?php echo $term->term_id; ?>]
                                </p>
                            </div>
                        </div>
                        <div class="col-xs-5">
                            <div class="object-actions gallery-object-actions">
                                <?php
                                /*
                                $filter_icon = '<span class="glyphicon glyphicon-filter"></span>';
                                echo '<a title="' . __('Filter in Gmedia Library', 'grand-media') . '" href="#">'.$filter_icon.'</a>';
                                */

                                $cloud_link = $gmCore->gmcloudlink($term->term_id, 'gallery');
                                if(!empty($term_meta['_post_ID'])){
                                    $post_link = get_permalink($term_meta['_post_ID']);
                                } else {
                                    $post_link = '';
                                }
                                $share_icon      = '<span class="glyphicon glyphicon-share"></span>';
                                $new_window_icon = '<span class="glyphicon glyphicon-new-window"></span>';
                                if ('draft' !== $term->status) {
                                    echo '<a target="_blank" data-target="#shareModal" data-share="' . $term->term_id . '" class="share-modal" title="' . __('Share', 'grand-media') . '" data-gmediacloud="' . $cloud_link . '" href="' . $post_link . '">' . $share_icon . '</a>';
                                    //echo '<a target="_blank" title="' . __('GmediaCloud Page', 'grand-media') . '" href="' . $cloud_link . '">' . $new_window_icon . '</a>';
                                } else {
                                    echo "<span class='action-inactive'>$share_icon</span>";
                                    echo "<span class='action-inactive'>$new_window_icon</span>";
                                }

                                $edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
                                if ($allow_edit) {
                                    echo '<a title="' . __('Edit', 'grand-media') . '" href="' . add_query_arg(array('edit_gallery' => $term->term_id), $url) . '">' . $edit_icon . '</a>';
                                } else {
                                    echo "<span class='action-inactive'>$edit_icon</span>";
                                }

                                $trash_icon = '<span class="glyphicon glyphicon-trash"></span>';
                                if ($allow_delete) {
                                    echo '<a class="trash-icon" title="' . __('Delete', 'grand-media') . '" href="' . wp_nonce_url(add_query_arg(array(
                                            'term'   => $taxonomy,
                                            'delete' => $term->term_id
                                        ), $url), 'gmedia_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . $trash_icon . '</a>';
                                } else {
                                    echo "<span class='action-inactive'>$trash_icon</span>";
                                }
                                ?>
                            </div>
                            <p class="media-meta">
                                <span class="label label-default"><?php _e('Module', 'grand-media'); ?>:</span> <?php echo $term_meta['_module']; ?>
                                <?php if ($broken) { ?>
                                    <span class="bg-danger text-center"><?php _e('Module broken. Reinstall module', 'grand-media') ?></span>
                                <?php } ?>
                                <br><span class="label label-default"><?php _e('Type', 'grand-media'); ?>:</span> <?php echo $module_info['type']; ?>
                                <br><span class="label label-default"><?php _e('Last Edited', 'grand-media'); ?>:</span> <?php echo $term_meta['_edited']; ?>
                                <br><span class="label label-default"><?php _e('Status', 'grand-media'); ?>:</span> <?php echo $term->status; ?>
                                <br><span class="label label-default"><?php _e('Source', 'grand-media'); ?>:</span>
                                <?php
                                $gallery_tabs = reset($term_meta['_query']);
                                $tax_tabs     = key($term_meta['_query']);
                                if ('gmedia__in' == $tax_tabs) {
                                    _e('Selected Gmedia', 'grand-media');
                                    $gmedia_ids = wp_parse_id_list($gallery_tabs[0]);
                                    $gal_source = sprintf('<a class="gm_gallery_source selected__in" href="%s">' . __('Show %d items in Gmedia Library', 'grand-media') . '</a>', esc_url(add_query_arg(array('gmedia__in' => implode(',', $gmedia_ids)), $lib_url)), count($gmedia_ids));
                                    echo " ($gal_source)";
                                } else {
                                    $tabs         = $gmDB->get_terms($tax_tabs, array('include' => $gallery_tabs));
                                    $terms_source = array();
                                    if ('gmedia_category' == $tax_tabs) {
                                        _e('Categories', 'grand-media');
                                        foreach ($tabs as $t) {
                                            $terms_source[] = sprintf('<a class="gm_gallery_source gm_category" href="%s">%s</a>', esc_url(add_query_arg(array('cat' => $t->term_id), $lib_url)), esc_html($gmGallery->options['taxonomies']['gmedia_category'][$t->name]));
                                        }
                                    } elseif ('gmedia_album' == $tax_tabs) {
                                        _e('Albums', 'grand-media');
                                        foreach ($tabs as $t) {
                                            $terms_source[] = sprintf('<a class="gm_gallery_source gm_album" href="%s">%s</a>', esc_url(add_query_arg(array('alb' => $t->term_id), $lib_url)), esc_html($t->name));
                                        }
                                    } elseif ('gmedia_tag' == $tax_tabs) {
                                        _e('Tags', 'grand-media');
                                        foreach ($tabs as $t) {
                                            $terms_source[] = sprintf('<a class="gm_gallery_source gm_tag" href="%s">%s</a>', esc_url(add_query_arg(array('tag_id' => $t->term_id), $lib_url)), esc_html($t->name));
                                        }
                                    } elseif ('gmedia_filter' == $tax_tabs) {
                                        _e('Filters', 'grand-media');
                                        foreach ($tabs as $t) {
                                            $terms_source[] = sprintf('<a class="gm_gallery_source gm_filter" href="%s">%s</a>', esc_url(add_query_arg(array('stack_id' => $t->term_id), $lib_url)), esc_html($t->name));
                                        }
                                    }
                                    if (! empty($terms_source)) {
                                        echo ' (' . join(', ', $terms_source) . ')';
                                    }
                                }
                                ?>
                            </p>
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
    </div>

    <!-- Modal -->
    <div class="modal fade gmedia-modal" id="chooseModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php _e('Choose Module for Gallery'); ?></h4>
                </div>
                <div class="modal-body linkblock">
                    <?php
                    if (! empty($modules)) {
                        foreach ($modules as $m) {
                            /**
                             * @var $module_name
                             * @var $module_url
                             * @var $module_path
                             */
                            extract($m);
                            if (! file_exists($module_path . '/index.php')) {
                                continue;
                            }
                            $module_info = array();
                            /** @noinspection PhpIncludeInspection */
                            include($module_path . '/index.php');
                            if (empty($module_info)) {
                                continue;
                            }
                            $mclass = ' module-' . $module_info['type'] . ' module-' . $module_info['status'];
                            ?>
                            <div data-href="<?php echo add_query_arg(array('gallery_module' => $module_name), $url); ?>" class="choose-module media<?php echo $mclass; ?>">
                                <a href="<?php echo add_query_arg(array('gallery_module' => $module_name), $url); ?>" class="thumbnail pull-left">
                                    <img class="media-object" src="<?php echo $module_url . '/screenshot.png'; ?>" alt="<?php echo esc_attr($module_info['title']); ?>" width="160" height="120"/>
                                </a>

                                <div class="media-body" style="margin-left:180px;">
                                    <h4 class="media-heading"><?php echo $module_info['title']; ?></h4>

                                    <p class="version"><?php echo __('Version', 'grand-media') . ': ' . $module_info['version']; ?></p>

                                    <div class="description"><?php echo nl2br($module_info['description']); ?></div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        _e('No installed modules', 'grand-media');
                    }
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Cancel', 'grand-media'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <?php

    include(GMEDIA_ABSPATH. 'admin/tpl/modal-share.php');

}

/**
 * gmediaGalleryEdit()
 *
 * @return mixed content
 */
function gmediaGalleryEdit()
{
    global $gmDB, $gmCore, $gmGallery, $gmProcessor, $user_ID;

    $alert = array();

    $module_name = $gmCore->_get('gallery_module');
    $gallery_id  = $gmCore->_get('edit_gallery');
    $author_new  = false;
    if ($gmCore->caps['gmedia_edit_others_media']) {
        $author = (int)$gmCore->_get('author', $user_ID);
    } else {
        $author = $user_ID;
    }

    $url = add_query_arg(array('page' => $gmProcessor->page, 'edit_gallery' => $gallery_id), admin_url('admin.php'));

    $gallery  = array(
        'name'        => '',
        'description' => '',
        'global'      => $author,
        'status'      => 'publish',
        '_edited'     => '&#8212;',
        '_module'     => '',
        '_query'      => array(),
        '_settings'   => array()
    );
    $taxonomy = 'gmedia_gallery';
    if ($gallery_id) {
        $url     = add_query_arg(array('page' => $gmProcessor->page, 'edit_gallery' => $gallery_id), admin_url('admin.php'));
        $gallery = $gmDB->get_term($gallery_id, $taxonomy, ARRAY_A);
        if (is_wp_error($gallery)) {
            $alert[] = $gallery->get_error_message();
        } elseif (empty($gallery)) {
            $alert[] = sprintf(__('No gallery with ID #%s in database'), $gallery_id);
        } else {
            if (($gallery['global'] == $author) || $gmCore->caps['gmedia_edit_others_media']) {
                $gallery_meta = $gmDB->get_metadata('gmedia_term', $gallery_id);
                $gallery_meta = array_map('reset', $gallery_meta);
                //$gallery_meta = array_map('maybe_unserialize', $gallery_meta);
                $gallery = array_merge($gallery, $gallery_meta);
                if (isset($_GET['author']) && ($gallery['global'] != $author)) {
                    unset($gallery['_query']['gmedia_album']);
                    $gallery['global'] = $author;
                    $author_new        = true;
                }
                if (! $module_name) {
                    $module_name = $gallery['_module'];
                }
            } else {
                $alert[] = __('You are not allowed to edit others media');
            }
        }
    } elseif ($module_name) {
        $url                = add_query_arg(array('page' => $gmProcessor->page, 'gallery_module' => $module_name), admin_url('admin.php'));
        $gallery['_module'] = $module_name;
    }

    $gallery_post = $gmCore->_post('gallery');
    if ($gallery_post) {
        $gallery = $gmCore->array_replace_recursive($gallery, $gallery_post);
    }

    if (! empty($alert)) {
        echo $gmProcessor->alert('danger', $alert);
        gmediaGalleries();

        return;
    }

    $modules = array(
        'phantom' => ''
        , 'phototravlr' => ''
        , 'realslider' => ''
        , 'mosaic' => ''
        , 'photobox' => ''
        , 'photomania' => ''
        , 'jq-mplayer' => ''
        , 'wp-videoplayer' => ''
        , 'photo-pro' => ''
        , 'optima' => ''
        , 'afflux' => ''
        , 'slider' => ''
        , 'green-style' => ''
        , 'photo-blog' => ''
        , 'minima' => ''
        , 'sphere' => ''
        , 'cube' => ''
        , 'flatwall' => ''
    );
    if (($plugin_modules = glob(GMEDIA_ABSPATH . 'module/*', GLOB_ONLYDIR | GLOB_NOSORT))) {
        foreach ($plugin_modules as $path) {
            $mfold           = basename($path);
            $modules[$mfold] = array(
                'place'       => 'plugin',
                'module_name' => $mfold,
                'module_url'  => "{$gmCore->gmedia_url}/module/{$mfold}",
                'module_path' => $path
            );
        }
    }
    if (($upload_modules = glob($gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/*', GLOB_ONLYDIR | GLOB_NOSORT))) {
        foreach ($upload_modules as $path) {
            $mfold           = basename($path);
            $modules[$mfold] = array(
                'place'       => 'upload',
                'module_name' => $mfold,
                'module_url'  => "{$gmCore->upload['url']}/{$gmGallery->options['folder']['module']}/{$mfold}",
                'module_path' => $path
            );
        }
    }

    $modules = array_filter($modules);

    $default_options = array();
    $presets         = false;
    $default_preset  = array();
    $load_preset     = array();

    /**
     * @var $place
     * @var $module_name
     * @var $module_url
     * @var $module_path
     */
    if ($module_name) {
        $presets = $gmDB->get_terms('gmedia_module', array('global' => $user_ID, 'status' => $module_name));
        foreach ($presets as $i => $preset) {
            if ('[' . $module_name . ']' == $preset->name) {
                $default_preset            = maybe_unserialize($preset->description);
                $default_preset['term_id'] = $preset->term_id;
                $default_preset['name']    = $preset->name;
                unset($presets[$i]);
            }
            if ((int)$preset->term_id == (int)$gmCore->_get('preset', 0)) {
                $load_preset            = maybe_unserialize($preset->description);
                $load_preset['term_id'] = $preset->term_id;
                $load_preset['name']    = $preset->name;
            }
        }

        if (isset($modules[$module_name])) {
            extract($modules[$module_name]);

            /**
             * @var $module_info
             *
             * @var $default_options
             * @var $options_tree
             */
            if (file_exists($module_path . '/index.php') && file_exists($module_path . '/settings.php')) {
                /** @noinspection PhpIncludeInspection */
                include($module_path . '/index.php');
                /** @noinspection PhpIncludeInspection */
                include($module_path . '/settings.php');

                if (! empty($default_preset)) {
                    $default_options = $gmCore->array_replace_recursive($default_options, $default_preset);
                }
            } else {
                $alert[] = sprintf(__('Module `%s` is broken. Choose another module from the list and save settings'), $module_name);
            }
        } else {
            $alert[] = sprintf(__('Can\'t get module with name `%s`. Choose module from the list and save settings'), $module_name);
        }
    } else {
        $alert[] = sprintf(__('Module is not selected for this gallery. Choose module from the list and save settings'), $module_name);
    }

    if (! empty($alert)) {
        echo $gmProcessor->alert('danger', $alert);
    }

    if (! empty($load_preset)) {
        $gallery['_settings'][$module_name] = $gmCore->array_replace_recursive($gallery['_settings'][$module_name], $load_preset);
        echo $gmProcessor->alert('info', sprintf(__('Preset `%s` loaded. To apply it for current gallery click Save button'), $load_preset['name']));
    }
    if (isset($gallery['_settings'][$module_name])) {
        $gallery_settings = $gmCore->array_replace_recursive($default_options, $gallery['_settings'][$module_name]);
    } else {
        $gallery_settings = $default_options;
    }

    /** @noinspection PhpIncludeInspection */
    include_once(GMEDIA_ABSPATH . '/inc/module.options.php');

    ?>

    <form class="panel panel-default" id="gallerySettingsForm" method="post" action="<?php echo $url; ?>">
        <div class="panel-heading clearfix">
            <div class="btn-toolbar pull-left">
                <div class="btn-group">
                    <a href="<?php echo add_query_arg(array('page' => 'GrandMedia_Galleries'), admin_url('admin.php')); ?>" class="btn btn-default"><span class="glyphicon glyphicon-arrow-left"></span> <?php _e('Manage Galleries', 'grand-media'); ?></a>
                </div>
                <div class="btn-group" id="save_buttons">
                    <?php if ($gallery['_module'] != $module_name) { ?>
                        <a href="<?php echo $url; ?>" class="btn btn-default"><?php _e('Cancel preview module', 'grand-media'); ?></a>
                        <button type="submit" name="gmedia_gallery_save" class="btn btn-primary"><?php _e('Save with new module', 'grand-media'); ?></button>
                    <?php } else { ?>
                        <?php $reset_settings = $gmCore->array_diff_keyval_recursive($default_options, $gallery_settings, true);
                        if (! empty($reset_settings)) {
                            ?>
                            <button type="submit" name="gmedia_gallery_reset" class="btn btn-default" data-confirm="<?php _e('Confirm reset gallery options') ?>"><?php _e('Reset to default', 'grand-media'); ?></button>
                        <?php } ?>
                        <button type="submit" name="gmedia_gallery_save" class="btn btn-primary"><?php _e('Save', 'grand-media'); ?></button>
                    <?php } ?>
                </div>
            </div>
            <div class="btn-toolbar pull-right" id="module_preset">
                <div class="btn-group">
                    <button type="button" class="btn btn-default" id="save_preset" data-toggle="popover"><?php _e('Module Presets', 'grand-media'); ?></button>
                </div>
                <script type="text/html" id="_save_preset">
                    <div style="padding-top: 5px;">
                        <p style="white-space: nowrap">
                            <button type="submit" name="module_preset_save_default" class="ajax-submit btn btn-default btn-sm"><?php _e('Save as Default', 'grand-media'); ?></button>
                            &nbsp; <em><?php _e('or', 'grand-media'); ?></em> &nbsp;
                            <?php if (! empty($default_preset)) { ?>
                                <button type="submit" name="module_preset_restore_original" class="ajax-submit btn btn-default btn-sm"><?php _e('Restore Original', 'grand-media'); ?></button>
                                <input type="hidden" name="preset_default" value="<?php echo $default_preset['term_id']; ?>"/>
                            <?php } ?>
                        </p>
                        <div class="form-group clearfix" style="border-top: 1px solid #444444; padding-top: 5px;">
                            <label><?php _e('Save Preset as:', 'grand-media'); ?></label>

                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control input-sm" name="module_preset_name" placeholder="<?php _e('Preset Name', 'grand-media'); ?>" value=""/>
                                <span class="input-group-btn"><button type="submit" name="module_preset_save_as" class="ajax-submit btn btn-primary"><?php _e('Save', 'grand-media'); ?></button></span>
                            </div>
                        </div>

                        <?php if (! empty($presets)) { ?>
                            <ul class="list-group presetlist">
                                <?php foreach ($presets as $preset) {
                                    $trim  = '[' . $module_name . '] ';
                                    $count = 1;
                                    ?>
                                    <li class="list-group-item">
                                        <span class="delpreset"><span class="label label-danger" data-id="<?php echo $preset->term_id; ?>">&times;</span></span>
                                        <a href="<?php echo $gmCore->get_admin_url(array('preset' => $preset->term_id), array(), $url); ?>"><?php echo str_replace($trim, '', $preset->name, $count); ?></a>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                </script>
            </div>
        </div>
        <div class="panel-body" id="gmedia-msg-panel"></div>
        <div class="panel-body" id="gmedia-edit-gallery" style="margin-bottom:4px; padding-top:0;">
            <div class="row">
                <div class="col-lg-6 tabable tabs-left">
                    <ul class="nav nav-tabs" id="galleryTabs" style="padding:10px 0;">
                        <?php if (isset($module_info)) { ?>
                            <li class="text-center">
                                <strong><?php echo $module_info['title']; ?></strong><a href="#chooseModuleModal" data-toggle="modal" style="padding:5px 0;"><img src="<?php echo $module_url . '/screenshot.png'; ?>" alt="<?php echo esc_attr($module_info['title']); ?>" width="100" style="height:auto;"/></a>
                            </li>
                        <?php } else { ?>
                            <li class="text-center"><strong><?php echo $gallery['_module']; ?></strong>

                                <p><?php _e('This module is broken or outdated. Please, go to Modules page and update/install module or choose another one for this gallery', 'grand-media'); ?></p>
                                <a href="#chooseModuleModal" data-toggle="modal" style="padding:5px 0;"><img src="<?php echo $module_url . '/screenshot.png'; ?>" alt="<?php echo esc_attr($gallery['_module']); ?>" width="100" style="height:auto;"/></a>
                            </li>
                        <?php } ?>
                        <li class="active"><a href="#general_settings" data-toggle="tab"><?php _e('General Settings', 'grand-media'); ?></a></li>
                        <?php
                        if (isset($options_tree)) {
                            gmedia_gallery_options_nav($options_tree);
                        }
                        ?>
                    </ul>

                    <div id="gallery_options_block" class="tab-content" style="padding-top:20px;">

                        <fieldset id="general_settings" class="tab-pane active">
                            <p><?php echo '<b>' . __('Gallery module:') . '</b> <a href="#chooseModuleModal" data-toggle="modal">' . $gallery['_module'] . '</a>';
                                if ($gallery['_module'] != $module_name) {
                                    echo '<br /><b>' . __('Preview module:') . '</b> ' . $module_name;
                                    echo '<br /><span class="text-muted">' . sprintf(__('Note: Module changed to %s, but not saved yet'), $module_name) . '</span>';
                                } ?></p>

                            <p><b><?php _e('Gallery author:', 'grand-media'); ?></b>
                                <?php if ($gmCore->caps['gmedia_delete_others_media']) { ?>
                                    <a href="#gallModal" data-modal="select_author" data-action="gmedia_get_modal" class="gmedia-modal" title="<?php _e('Click to choose author for gallery', 'grand-media'); ?>"><?php echo $gallery['global'] ? get_the_author_meta('display_name', $gallery['global']) : __('(no author / shared albums)'); ?></a>
                                    <?php if ($author_new) {
                                        echo '<br /><span class="text-danger">' . __('Note: Author changed but not saved yet. You can see Albums list only of chosen author') . '</span>';
                                    } ?>
                                <?php } else {
                                    echo $gallery['global'] ? get_the_author_meta('display_name', $gallery['global']) : '&#8212;';
                                } ?>
                                <input type="hidden" name="gallery[global]" value="<?php echo $gallery['global']; ?>"/></p>
                            <?php if ($gallery_id) { ?>
                                <p><b><?php _e('Shortcode:'); ?></b> [gmedia id=<?php echo $gallery_id; ?>]</p>
                            <?php } ?>
                            <input type="hidden" name="gallery[module]" value="<?php echo esc_attr($module_name); ?>">

                            <div class="form-group">
                                <label><?php _e('Gallery Name', 'grand-media'); ?></label>
                                <input type="text" class="form-control input-sm" name="gallery[name]" placeholder="<?php echo empty($gallery['name']) ? esc_attr(__('Gallery Name', 'grand-media')) : esc_attr($gallery['name']); ?>" value="<?php echo esc_attr($gallery['name']); ?>" required="required"/>
                            </div>
                            <div class="form-group">
                                <label><?php _e('Status', 'grand-media'); ?></label>
                                <select name="gallery[status]" class="form-control input-sm">
                                    <option value="publish"<?php selected($gallery['status'], 'publish'); ?>><?php _e('Public', 'grand-media'); ?></option>
                                    <option value="private"<?php selected($gallery['status'], 'private'); ?>><?php _e('Private', 'grand-media'); ?></option>
                                    <option value="draft"<?php selected($gallery['status'], 'draft'); ?>><?php _e('Draft', 'grand-media'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php _e('Show supported files from', 'grand-media'); ?></label>
                                <select data-watch="change" id="gmedia_query" class="form-control input-sm" name="gallery[term]">
                                    <?php reset($gallery['_query']);
                                    $gallery['term'] = key($gallery['_query']); ?>
                                    <?php if ($gmCore->caps['gmedia_terms']) { ?>
                                        <option value="gmedia_album"<?php selected($gallery['term'], 'gmedia_album'); ?>><?php _e('Albums', 'grand-media'); ?></option>
                                        <option value="gmedia_tag"<?php selected($gallery['term'], 'gmedia_tag'); ?>><?php _e('Tags', 'grand-media'); ?></option>
                                        <option value="gmedia_category"<?php selected($gallery['term'], 'gmedia_category'); ?>><?php _e('Categories', 'grand-media'); ?></option>
                                        <option value="gmedia_filter"<?php selected($gallery['term'], 'gmedia_filter'); ?>><?php _e('Filter', 'grand-media'); ?></option>
                                    <?php } ?>
                                    <option value="gmedia__in"<?php selected($gallery['term'], 'gmedia__in'); ?>><?php _e('Selected Gmedia', 'grand-media'); ?></option>
                                </select>
                            </div>

                            <?php if ($gmCore->caps['gmedia_terms']) { ?>
                                <div class="form-group" id="div_gmedia_category">
                                    <?php
                                    $term_type    = 'gmedia_category';
                                    $gm_terms_all = $gmGallery->options['taxonomies'][$term_type];
                                    $gm_terms     = $gmDB->get_terms($term_type, array('fields' => 'names_count'));

                                    $terms_items = '';
                                    if (count($gm_terms)) {
                                        foreach ($gm_terms as $id => $term) {
                                            $selected = (isset($gallery['_query'][$term_type]) && in_array($id, $gallery['_query'][$term_type])) ? ' selected="selected"' : '';
                                            $terms_items .= '<option value="' . $id . '"' . $selected . '>' . esc_html($gm_terms_all[$term['name']]) . ' (' . $term['count'] . ')</option>' . "\n";
                                        }
                                    }
                                    $setvalue = isset($gallery['_query'][$term_type]) ? 'data-setvalue="' . implode(',', $gallery['_query'][$term_type]) . '"' : '';
                                    ?>
                                    <label><?php _e('Choose Categories', 'grand-media'); ?></label>
                                    <select data-gmedia_query="is:gmedia_category" <?php echo $setvalue; ?> id="gmedia_category" name="gallery[query][gmedia_category][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Choose Categories...', 'grand-media')); ?>">
                                        <option value=""><?php _e('Choose Categories...', 'grand-media'); ?></option>
                                        <?php echo $terms_items; ?>
                                    </select>
                                </div>

                                <div class="form-group" id="div_gmedia_tag">
                                    <?php
                                    $term_type = 'gmedia_tag';
                                    $gm_terms  = $gmDB->get_terms($term_type, array('fields' => 'names_count'));

                                    $terms_items = '';
                                    if (count($gm_terms)) {
                                        foreach ($gm_terms as $id => $term) {
                                            $selected = (isset($gallery['_query'][$term_type]) && in_array($id, $gallery['_query'][$term_type])) ? ' selected="selected"' : '';
                                            $terms_items .= '<option value="' . $id . '"' . $selected . '>' . esc_html($term['name']) . ' (' . $term['count'] . ')</option>' . "\n";
                                        }
                                    }
                                    $setvalue = isset($gallery['_query'][$term_type]) ? 'data-setvalue="' . implode(',', $gallery['_query'][$term_type]) . '"' : '';
                                    ?>
                                    <label><?php _e('Choose Tags', 'grand-media'); ?> </label>
                                    <select data-gmedia_query="is:gmedia_tag" <?php echo $setvalue; ?> id="gmedia_tag" name="gallery[query][gmedia_tag][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Choose Tags...', 'grand-media')); ?>">
                                        <option value=""><?php echo __('Choose Tags...', 'grand-media'); ?></option>
                                        <?php echo $terms_items; ?>
                                    </select>
                                </div>

                                <div class="form-group" id="div_gmedia_album">
                                    <?php
                                    $term_type = 'gmedia_album';
                                    $args      = array();
                                    /*if($gallery['global']){
                                        if(user_can($gallery['global'], 'gmedia_edit_others_media')){
                                            $args['global'] = '';
                                        } else {
                                            $args['global'] = array( 0, $gallery['global'] );
                                        }
                                    } else{
                                        $args['global'] = 0;
                                    }*/
                                    if ($gmCore->caps['gmedia_edit_others_media']) {
                                        $args['global'] = '';
                                    } else {
                                        $args['global'] = array(0, $user_ID);
                                    }
                                    $gm_terms = $gmDB->get_terms($term_type, $args);

                                    $terms_items = '';
                                    if (count($gm_terms)) {
                                        foreach ($gm_terms as $term) {
                                            $selected = (isset($gallery['_query'][$term_type]) && in_array($term->term_id, $gallery['_query'][$term_type])) ? ' selected="selected"' : '';
                                            $terms_items .= '<option value="' . $term->term_id . '"' . $selected . '>' . esc_html($term->name) . ('publish' == $term->status ? '' : " [{$term->status}]") . ' &nbsp; (' . $term->count . ')</option>' . "\n";
                                        }
                                    }
                                    $setvalue = isset($gallery['_query'][$term_type]) ? 'data-setvalue="' . implode(',', $gallery['_query'][$term_type]) . '"' : '';
                                    ?>
                                    <label><?php _e('Choose Albums', 'grand-media'); ?> </label>
                                    <select data-gmedia_query="is:gmedia_album" <?php echo $setvalue; ?> id="gmedia_album" name="gallery[query][gmedia_album][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Choose Albums...', 'grand-media')); ?>">
                                        <option value=""><?php echo __('Choose Albums...', 'grand-media'); ?></option>
                                        <?php echo $terms_items; ?>
                                    </select>

                                    <p class="help-block"><?php _e('You can choose Albums from the same author as Gallery author or Albums without author', 'grand-media'); ?></p>
                                </div>
                                <div class="form-group" id="div_gmedia_filter">
                                    <?php
                                    $term_type = 'gmedia_filter';
                                    $args      = array();
                                    if ($gmCore->caps['gmedia_edit_others_media']) {
                                        $args['global'] = '';
                                    } else {
                                        $args['global'] = array(0, $user_ID);
                                    }
                                    $gm_terms = $gmDB->get_terms($term_type, $args);

                                    $terms_items = '';
                                    if (count($gm_terms)) {
                                        foreach ($gm_terms as $term) {
                                            $selected = (isset($gallery['_query'][$term_type]) && in_array($term->term_id, $gallery['_query'][$term_type])) ? ' selected="selected"' : '';
                                            $terms_items .= '<option value="' . $term->term_id . '"' . $selected . '>' . esc_html($term->name) . '</option>' . "\n";
                                        }
                                    }
                                    $setvalue = isset($gallery['_query'][$term_type]) ? 'data-setvalue="' . implode(',', $gallery['_query'][$term_type]) . '"' : '';
                                    ?>
                                    <label><?php _e('Choose Custom Filters', 'grand-media'); ?> </label>
                                    <select data-gmedia_query="is:gmedia_filter" <?php echo $setvalue; ?> id="gmedia_filter" name="gallery[query][gmedia_filter][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Choose Filters...', 'grand-media')); ?>">
                                        <option value=""><?php echo __('Choose Filters...', 'grand-media'); ?></option>
                                        <?php echo $terms_items; ?>
                                    </select>

                                    <p class="help-block"><?php _e('Filter - is custom query with multiple parameters.', 'grand-media'); ?>
                                        <a target="_blank" href="<?php echo add_query_arg(array('page' => 'GrandMedia_Terms', 'taxonomy' => 'gmedia_filter', 'edit_item' => '0'), admin_url('admin.php')); ?>"><?php _e('Create Filter', 'grand-media'); ?></a></p>
                                </div>
                            <?php } ?>

                            <div class="form-group" id="div_gmedia__in">
                                <label><?php _e('Selected Gmedia IDs <small class="text-muted">separated by comma</small>', 'grand-media'); ?> </label>
                                <?php $value = isset($gallery['_query']['gmedia__in'][0]) ? implode(',', wp_parse_id_list($gallery['_query']['gmedia__in'][0])) : ''; ?>
                                <textarea data-gmedia_query="is:gmedia__in" id="gmedia__in" name="gallery[query][gmedia__in][]" rows="1" class="form-control input-sm" style="resize:vertical;" placeholder="<?php echo esc_attr(__('Gmedia IDs...', 'grand-media')); ?>"><?php echo $value; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label><?php _e('Description', 'grand-media'); ?></label>
                                <textarea class="form-control input-sm" rows="5" name="gallery[description]"><?php echo esc_html($gallery['description']) ?></textarea>
                            </div>

                        </fieldset>

                        <?php
                        if (isset($options_tree)) {
                            gmedia_gallery_options_fieldset($options_tree, $default_options, $gallery_settings);
                        }
                        ?>
                    </div>

                </div>
                <div class="col-lg-6" style="padding-top:20px;">
                    <p><b><?php _e('Last edited:'); ?></b> <?php echo $gallery['_edited']; ?></p>
                    <?php if ($gallery_id) {
                        $params               = array();
                        $params['set_module'] = ($gallery['_module'] != $module_name) ? $module_name : false;
                        $params['iframe']     = 1;
                        ?>
                        <p><b><?php _e('Gallery ID:'); ?></b> #<?php echo $gallery_id; ?></p>
                        <p><b><?php _e('GmediaCloud page URL for current gallery:'); ?></b> <?php
                            $endpoint             = $gmGallery->options['endpoint'];
                            $gmedia_hashid        = gmedia_hash_id_encode($gallery_id, 'gallery');
                            $gallery_link_default = add_query_arg(array("$endpoint" => $gmedia_hashid, 't' => 'g'), home_url('index.php'));
                            if (get_option('permalink_structure')) {
                                $gallery_link = home_url(urlencode($endpoint) . '/g/' . $gmedia_hashid);
                            } else {
                                $gallery_link = $gallery_link_default;
                            } ?>
                            <br/><a target="_blank" href="<?php echo $gallery_link; ?>"><?php echo $gallery_link; ?></a>
                        </p>
                        <?php if(isset($gallery['_post_ID'])){ ?>
                        <p><b><?php _e('Gmedia Post URL for current gallery:'); ?></b>
                            <?php $post_link = get_permalink($gallery['_post_ID']); ?>
                            <br/><a target="_blank" href="<?php echo $post_link; ?>"><?php echo $post_link; ?></a>
                        </p>
                        <?php } ?>
                        <div class="help-block">
                            <?php _e('update <a href="options-permalink.php">Permalink Settings</a> if above link not working', 'grand-media'); ?>
                            <?php if (current_user_can('manage_options')) {
                                echo '<br>' . __('More info about GmediaCloud Pages and GmediaCloud Settings can be found <a href="admin.php?page=GrandMedia_Settings#gmedia_settings_cloud">here</a>', 'grand-media');
                            } ?>
                        </div>
                        <div><b><?php _e('Gallery Preview:'); ?></b></div>
                        <div class="gallery_preview" style="overflow:hidden;">
                            <iframe id="gallery_preview" name="gallery_preview" src="<?php echo add_query_arg($params, set_url_scheme($gallery_link_default, 'admin')); ?>"></iframe>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <script type="text/javascript">
                jQuery(function ($) {
                    <?php if(!empty($alert)){ ?>
                    $('#chooseModuleModal').modal('show');
                    <?php } ?>

                    var hash = window.location.hash;
                    if (hash) {
                        $('#galleryTabs a').eq(hash.replace('#tab-', '')).tab('show');
                    }
                    $('#gallerySettingsForm').on('submit', function () {
                        $(this).attr('action', $(this).attr('action') + '#tab-' + $('#galleryTabs li.active').index());
                    });

                    <?php if($gmCore->caps['gmedia_terms']){ ?>
                    $('.gmedia-combobox').each(function () {
                        var select = $(this).selectize({
                            plugins: ['drag_drop'],
                            create: false,
                            hideSelected: true
                        });
                        var val = $(this).data('setvalue');
                        if (val) {
                            val = val.toString().split(',');
                            select[0].selectize.setValue(val);
                        }
                    });
                    <?php } ?>

                    var main = $('#gallery_options_block');

                    $('input', main).filter('[data-type="color"]').minicolors({
                        animationSpeed: 50,
                        animationEasing: 'swing',
                        change: null,
                        changeDelay: 0,
                        control: 'hue',
                        //defaultValue: '',
                        hide: null,
                        hideSpeed: 100,
                        inline: false,
                        letterCase: 'lowercase',
                        opacity: false,
                        position: 'bottom left',
                        show: null,
                        showSpeed: 100,
                        theme: 'bootstrap'
                    });

                    $('[data-watch]', main).each(function () {
                        var el = $(this);
                        gmedia_options_conditional_logic(el, 0);
                        var event = el.attr('data-watch');
                        if (event) {
                            el.on(event, function () {
                                if ('change' == el.attr('data-watch')) {
                                    $(this).blur().focus();
                                }
                                gmedia_options_conditional_logic($(this), 400);
                            });
                        }
                    });

                    function gmedia_options_conditional_logic(el, slide) {
                        if (el.is(':input')) {
                            var val = el.val();
                            var id = el.attr('id').toLowerCase();
                            if (el.is(':checkbox') && !el[0].checked) {
                                val = '0';
                            }
                            $('[data-' + id + ']', main).each(function () {
                                var key = $(this).attr('data-' + id);
                                key = key.split(':');
                                //var hidden = $(this).data('hidden')? parseInt($(this).data('hidden')) : 0;
                                var hidden = $(this).data('hidden') ? $(this).data('hidden') : {};
                                var ch = true;
                                switch (key[0]) {
                                    case '=':
                                    case 'is':
                                        if (val == key[1]) {
                                            delete hidden[id];
                                            if (slide && $.isEmptyObject(hidden)) {
                                                $(this).prop('disabled', false).closest('.form-group').stop().slideDown(slide, function () {
                                                    $(this).css({display: 'block'});
                                                });
                                                if (key[2]) {
                                                    key[2] = $(this).data('value');
                                                } else {
                                                    ch = false;
                                                }
                                            } else {
                                                ch = false;
                                            }
                                            $(this).data('hidden', hidden);
                                        } else {
                                            if ($.isEmptyObject(hidden)) {
                                                if (key[2]) {
                                                    $(this).closest('.form-group').stop().slideUp(slide, function () {
                                                        $(this).css({display: 'none'});
                                                    });
                                                } else {
                                                    $(this).prop('disabled', true).closest('.form-group').stop().slideUp(slide, function () {
                                                        $(this).css({display: 'none'});
                                                    });
                                                }
                                            } else {
                                                ch = false;
                                            }
                                            hidden[id] = 1;
                                            $(this).data('hidden', hidden);
                                        }
                                        break;
                                    case '!=':
                                    case 'not':
                                        if (val == key[1]) {
                                            if ($.isEmptyObject(hidden)) {
                                                if (key[2]) {
                                                    $(this).closest('.form-group').stop().slideUp(slide, function () {
                                                        $(this).css({display: 'none'});
                                                    });
                                                } else {
                                                    $(this).prop('disabled', true).closest('.form-group').stop().slideUp(slide, function () {
                                                        $(this).css({display: 'none'});
                                                    });
                                                }
                                            } else {
                                                ch = false;
                                            }
                                            hidden[id] = 1;
                                            $(this).data('hidden', hidden);
                                        } else {
                                            delete hidden[id];
                                            if (slide && $.isEmptyObject(hidden)) {
                                                $(this).prop('disabled', false).closest('.form-group').stop().slideDown(slide, function () {
                                                    $(this).css({display: 'block'});
                                                });
                                                if (key[2] && slide) {
                                                    key[2] = $(this).data('value');
                                                } else {
                                                    ch = false;
                                                }
                                            } else {
                                                ch = false;
                                            }
                                            $(this).data('hidden', hidden);
                                        }
                                        break;
                                }
                                if (key[2] && ch) {
                                    if ($(this).is(':checkbox')) {
                                        if (+($(this).prop('checked')) != parseInt(key[2])) {
                                            $(this).data('value', ($(this).prop('checked') ? '1' : '0'));
                                            $(this).prop('checked', ('0' != key[2])).trigger('change');
                                        }
                                    } else {
                                        if ($(this).val() != key[2]) {
                                            $(this).data('value', $(this).val());
                                            $(this).val(key[2]).trigger('change');
                                        }
                                    }
                                }
                            });
                        }
                    }
                });

            </script>
        </div>
        <?php
        wp_nonce_field('GmediaGallery');
        ?>
    </form>

    <!-- Modal -->
    <?php if ($gmCore->caps['gmedia_edit_others_media']) { ?>
    <div class="modal fade gmedia-modal" id="gallModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog"></div>
    </div>
<?php } ?>

    <div class="modal fade gmedia-modal" id="chooseModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php _e('Choose Module for Gallery'); ?></h4>
                </div>
                <div class="modal-body linkblock">
                    <?php
                    if (! empty($alert)) {
                        echo $gmProcessor->alert('danger', $alert);
                    }

                    $current_module = $module_name;
                    if (! empty($modules)) {
                        foreach ($modules as $m) {
                            /**
                             * @var $module_name
                             * @var $module_url
                             * @var $module_path
                             */
                            extract($m);
                            if (($module_name == $current_module) || ! file_exists($module_path . '/index.php')) {
                                continue;
                            }
                            $module_info = array();
                            /** @noinspection PhpIncludeInspection */
                            include($module_path . '/index.php');
                            if (empty($module_info)) {
                                continue;
                            }
                            $mclass = ' module-' . $module_info['type'] . ' module-' . $module_info['status'];
                            ?>
                            <div data-href="<?php echo add_query_arg(array( 'edit_gallery' => $gallery_id, 'gallery_module' => $module_name), $url); ?>" class="choose-module media<?php echo $mclass; ?>">
                                <a href="<?php echo add_query_arg(array( 'edit_gallery' => $gallery_id, 'gallery_module' => $module_name), $url); ?>" class="thumbnail pull-left">
                                    <img class="media-object" src="<?php echo $module_url . '/screenshot.png'; ?>" alt="<?php echo esc_attr($module_info['title']); ?>" width="160" height="120"/>
                                </a>

                                <div class="media-body" style="margin-left:180px;">
                                    <h4 class="media-heading"><?php echo $module_info['title']; ?></h4>

                                    <p class="version"><?php echo __('Version', 'grand-media') . ': ' . $module_info['version']; ?></p>

                                    <div class="description"><?php echo nl2br($module_info['description']); ?></div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        _e('No installed modules', 'grand-media');
                    }
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Cancel', 'grand-media'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <?php
}

