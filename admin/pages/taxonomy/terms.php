<?php
/**
 * Gmedia Terms
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$gmedia_url           = $gmProcessor->url;
$gmedia_user_options  = $gmProcessor->user_options;
$gmedia_term_taxonomy = $gmProcessor->taxonomy;

$gmedia_terms       = $gmDB->get_terms($gmedia_term_taxonomy, $gmProcessor->query_args);
$gmedia_terms_count = $gmDB->count_gmedia();
$gmedia_terms_pager = $gmDB->query_pager();

?>
    <div class="panel panel-default panel-fixed-header" id="gmedia-panel">

        <?php
        include(dirname(__FILE__) . '/tpl/terms-panel-heading.php');

        do_action('gmedia_before_terms_list');
        ?>

        <form class="list-group <?php echo $gmedia_term_taxonomy; ?>" id="gm-list-table" style="margin-bottom:4px;">
            <?php
            $taxterm = str_replace('gmedia_', '', $gmedia_term_taxonomy);
            if(count($gmedia_terms)) {
                foreach($gmedia_terms as &$item) {
                    gmedia_term_item_more_data($item);

                    $item->classes = array();
                    if('publish' != $item->status) {
                        if('private' == $item->status) {
                            $item->classes[] = 'list-group-item-info';
                        } elseif('draft' == $item->status) {
                            $item->classes[] = 'list-group-item-warning';
                        }
                    }
                    $item->classes[] = $item->global? (($item->global == $user_ID)? 'current_user' : 'other_user') : 'shared';
                    $item->selected    = in_array($item->term_id, (array)$gmProcessor->selected_items);
                    if($item->selected) {
                        $item->classes[] = 'gm-selected';
                    }

                    $allow_terms_delete = gm_user_can('terms_delete');
                    if($item->global) {
                        if((int)$item->global === get_current_user_id()) {
                            $item->allow_edit   = gm_user_can("{$taxterm}_manage");
                            $item->allow_delete = $allow_terms_delete;
                        } else {
                            $item->allow_edit   = gm_user_can('edit_others_media');
                            $item->allow_delete = ($item->allow_edit && $allow_terms_delete);
                        }
                    } else {
                        $item->allow_edit   = gm_user_can('edit_others_media');
                        $item->allow_delete = ($item->allow_edit && $allow_terms_delete);
                    }

                    include(dirname(__FILE__) . "/tpl/{$taxterm}-list-item.php");

                }
            } else {
                include(dirname(__FILE__) . '/tpl/no-items.php');
            }
            wp_original_referer_field(true, 'previous');
            wp_nonce_field('GmediaTerms');
            ?>
        </form>
        <?php
        do_action('gmedia_after_terms_list');
        ?>
    </div>

<?php

include(GMEDIA_ABSPATH . 'admin/tpl/modal-share.php');


/**
 * gmediaAlbumEdit()
 *
 * @return mixed content
 */
function gmediaAlbumEdit() {
    global $gmDB, $gmCore, $gmProcessor, $gmGallery, $user_ID;

    if(!$gmCore->caps['gmedia_album_manage']) {
        die('-1');
    }

    $gmedia_url = add_query_arg(array('page' => $gmProcessor->page), admin_url('admin.php'));

    $gm_screen_options = get_user_meta($user_ID, 'gm_screen_options', true);
    if(!is_array($gm_screen_options)) {
        $gm_screen_options = array();
    }
    $gm_screen_options = array_merge($gmGallery->options['gm_screen_options'], $gm_screen_options);

    $taxonomy = $gmProcessor->taxonomy;
    $term_id  = $gmCore->_get('edit_item');

    $term = $gmDB->get_term($term_id, $taxonomy);

    if(!empty($term) && !is_wp_error($term)) {

        $term_meta  = $gmDB->get_metadata('gmedia_term', $term->term_id);
        $term_meta  = array_map('reset', $term_meta);
        $term_meta  = array_merge(array('_cover' => '', '_orderby' => 'ID', '_order' => 'DESC'), $term_meta);
        $per_page   = !empty($gm_screen_options['per_page_sort_gmedia'])? $gm_screen_options['per_page_sort_gmedia'] : 60;
        $cur_page   = $gmCore->_get('pager', 1);
        $pager_html = '';

        $termItems = array();
        if($term->count) {
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

            HEADER

            EDIT FORM HERE

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
                    <?php if(!empty($termItems)) {
                        foreach($termItems as $item) {
                            ?>
                            <div class="gm-img-thumbnail" data-gmid="<?php echo $item->ID; ?>"><?php
                                ?><img src="<?php echo $gmCore->gm_get_media_image($item, 'thumb', false); ?>" alt="<?php echo $item->ID; ?>" title="<?php echo esc_attr($item->title); ?>"/><?php
                                ?><input type="text" name="term[gmedia_ids][<?php echo $item->ID; ?>]" value="<?php echo isset($item->gmedia_order)? $item->gmedia_order : '0'; ?>"/><?php
                                ?><span class="label label-default">ID: <?php echo $item->ID; ?></span><?php
                                ?>
                                <div class="gm-img-thumb-title"><?php echo esc_html($item->title); ?></div>
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
                jQuery(function($) {
                    var sortdiv = $('#termItems');
                    var items = $('.gm-img-thumbnail', sortdiv);

                    sortdiv.sortable({
                        items: '.gm-img-thumbnail',
                        handle: 'img',
                        placeholder: 'gm-img-thumbnail ui-highlight-placeholder',
                        forcePlaceholderSize: true,
                        //revert: true,
                        stop: function(event, ui) {
                            $('#gmedia_term_orderby').val('custom');
                            var cur_order, prev_order, next_order;
                            var self = ui.item,
                                    prev_item = self.prev(),
                                    next_item = self.next();
                            prev_order = prev_item.length? parseInt($('input', prev_item).val()) : 0;
                            var img_order_asc = ('ASC' == $('#gmedia_term_order').val());
                            if(img_order_asc) {
                                cur_order = prev_order + 1;
                                $('input', self).val(cur_order);
                                while(next_item.length) {
                                    next_order = parseInt(next_item.find('input').val());
                                    if(cur_order < next_order) {
                                        break;
                                    }
                                    cur_order += 1;
                                    next_item.find('input').val(cur_order);
                                    next_item = next_item.next();
                                }

                            } else {
                                next_order = next_item.length? parseInt($('input', next_item).val()) : (prev_order? (prev_order - 1) : 0);
                                cur_order = next_order + 1;
                                $('input', self).val(cur_order);
                                while(prev_item.length) {
                                    prev_order = parseInt(prev_item.find('input').val());
                                    if(cur_order < prev_order) {
                                        break;
                                    }
                                    cur_order += 1;
                                    prev_item.find('input').val(cur_order);
                                    prev_item = prev_item.prev();
                                }
                            }
                        }
                    });

                    $('input', items).on('change', function() {
                        $('#gmedia_term_orderby').val('custom');
                        sortdiv.css({height: sortdiv.height()});
                        var items = $('.gm-img-thumbnail', sortdiv);

                        var new_order = $.isNumeric($(this).val())? parseInt($(this).val()) : -1;
                        $(this).val(new_order).closest('.gm-img-thumbnail').css({zIndex: 1000});

                        var ipos = [];
                        items.each(function(i, el) {
                            var pos = $(el).position();
                            $.data(el, 'pos', pos);
                            ipos[i] = pos;
                        });

                        var img_order_asc = ('ASC' == $('#gmedia_term_order').val());
                        var order = img_order_asc? 'asc' : 'desc';
                        items.tsort('input', {
                            useVal: true,
                            order: order
                        }, 'span.label', {order: order}).each(function(i, el) {
                            var from = $.data(el, 'pos');
                            var to = ipos[i];
                            $(el).css({position: 'absolute', top: from.top, left: from.left}).animate({
                                top: to.top,
                                left: to.left
                            }, 500);
                        }).promise().done(function() {
                            items.removeAttr('style');
                            sortdiv.removeAttr('style');
                        });

                        $(this).val(((0 > new_order)? 0 : new_order));
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
function gmediaFilterEdit() {
    global $gmDB, $gmCore, $gmProcessor, $gmGallery, $user_ID;

    if(!$gmCore->caps['gmedia_filter_manage']) {
        die('-1');
    }

    $gmedia_url = add_query_arg(array('page' => $gmProcessor->page), admin_url('admin.php'));

    /*$gm_screen_options = get_user_meta($user_ID, 'gm_screen_options', true);
	if(!is_array($gm_screen_options)){
		$gm_screen_options = array();
	}
	$gm_screen_options = array_merge($gmGallery->options['gm_screen_options'], $gm_screen_options);*/
    //$per_page = !empty($gm_screen_options['per_page_sort_gmedia'])? $gm_screen_options['per_page_sort_gmedia'] : 40;
    $per_page = 40;
    $cur_page = $gmCore->_get('pager', 1);

    $taxonomy = $gmProcessor->taxonomy;
    $term_id  = (int)$gmCore->_get('edit_item');

    $author_new = false;
    if($gmCore->caps['gmedia_edit_others_media']) {
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
            'author__in'         => array()
            , 'author__not_in'   => array()
            , 'category__in'     => array() // use category id. Same as 'cat', but does not accept negative values
            , 'category__not_in' => array() // use category id. Exclude multiple categories
            , 'album__in'        => array() // use album id. Same as 'alb'
            , 'album__not_in'    => array() // use album id. Exclude multiple albums
            , 'tag__and'         => array() // use tag ids. Display posts that are tagged with all listed tags in array
            , 'tag__in'          => array() // use tag ids. To display posts from either tags listed in array. Same as 'tag'
            , 'tag__not_in'      => array() // use tag ids. Display posts that do not have any of the listed tag ids
            , 'terms_relation'   => '' //  allows you to describe the boolean relationship between the taxonomy queries. Possible values are 'OR', 'AND'. Default 'AND'
            , 'gmedia__in'       => array() // use gmedia ids. Specify posts to retrieve
            , 'gmedia__not_in'   => array() // use gmedia ids. Specify post NOT to retrieve
            , 'mime_type'        => array() // mime types

            , 'limit'            => '' // (int) - set limit
            , 'per_page'         => '' // (int) - set limit
            , 'order'            => '' // Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'
            , 'orderby'          => '' // Sort retrieved posts by parameter. Defaults to 'ID'
            , 'year'             => '' // (int) - 4 digit year
            , 'monthnum'         => '' // (int) - Month number (from 1 to 12)
            , 'day'              => '' // (int) - Day of the month (from 1 to 31)

            , 'meta_query'       => array(
                    array(
                            'key'     => '',
                            'value'   => '',
                            'compare' => '',
                            'type'    => ''
                    )
            )
            , 's'                => '' // (string) - search string or terms separated by comma
            , 'exact'            => false // Search exactly string if 'exact' parameter set to true

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

    $totalResult     = 0;
    $trueTotalResult = 0;
    if($term_id) {
        $term = $gmDB->get_term($term_id, $taxonomy, ARRAY_A);
        if(!empty($term) && !is_wp_error($term)) {

            $term_query  = $gmDB->get_metadata('gmedia_term', $term['term_id'], '_query', true);
            $filter_data = array_merge($filter_data, $term_query);

            $term_query = array_merge($filter_variable_data, $term_query);

            if(isset($_GET['author']) && ($term['global'] != $author)) {
                $filter_data['_query']['gmedia_album'] = array();
                $term['global']                        = $author;
                $author_new                            = true;
            }

            if(!empty($filter_data['album__not_in'])) {
                $album__condition = 'album__not_in';
            }
            $gmedia_album = $filter_data[$album__condition];

            if(!empty($filter_data['category__not_in'])) {
                $category__condition = 'category__not_in';
            }
            $gmedia_category = $filter_data[$category__condition];

            if(!empty($filter_data['tag__not_in'])) {
                $tag__condition = 'tag__not_in';
            } elseif(!empty($filter_data['tag__and'])) {
                $tag__condition = 'tag__and';
            }
            $gmedia_tag = $filter_data[$tag__condition];

            if(!empty($filter_data['gmedia__not_in'])) {
                $gmedia_id__condition = 'gmedia__not_in';
            }
            $gmedia_id = $filter_data[$gmedia_id__condition];

            if(!empty($filter_data['author__not_in'])) {
                $author_id__condition = 'author__not_in';
            }
            $author_id = $filter_data[$author_id__condition];

            $termItems   = $gmDB->get_gmedias($term_query);
            $totalResult = (int)$gmDB->totalResult;
            if(!$totalResult && !empty($termItems)) {
                $totalResult = count($termItems);
            }
            if(!empty($gmDB->trueTotalResult)) {
                $trueTotalResult = $gmDB->trueTotalResult;
            }

            if(!empty($termItems)) {
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
                        <li><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_album'), $gmedia_url); ?>"><?php _e('Albums', 'grand-media'); ?></a></li>
                        <li><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_tag'), $gmedia_url); ?>"><?php _e('Tags', 'grand-media'); ?></a></li>
                        <li><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_category'), $gmedia_url); ?>"><?php _e('Categories', 'grand-media'); ?></a></li>
                        <li class="divider"></li>
                        <li class="active"><a href="<?php echo add_query_arg(array('taxonomy' => 'gmedia_filter'), $gmedia_url); ?>"><?php _e('Custom Filters', 'grand-media'); ?></a></li>
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
                <?php if($term_id) { ?>
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
                        <?php if($gmCore->caps['gmedia_delete_others_media']) { ?>
                            <a href="#gallModal" data-modal="select_author" data-action="gmedia_get_modal" class="gmedia-modal" title="<?php _e('Click to choose author for gallery', 'grand-media'); ?>"><?php echo $term['global']? get_the_author_meta('display_name', $term['global']) : __('(no author / shared albums)'); ?></a>
                            <?php if($author_new) {
                                echo '<br /><span class="text-danger">' . __('Note: Author changed but not saved yet. You can see Albums list only of chosen author') . '</span>';
                            } ?>
                        <?php } else {
                            echo $term['global']? get_the_author_meta('display_name', $term['global']) : '&#8212;';
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

            <?php if($gmCore->caps['gmedia_terms']) { ?>
                <div class="form-group">
                    <?php
                    $term_type = 'gmedia_album';
                    $args      = array();
                    if($term['global']) {
                        if(user_can($term['global'], 'gmedia_edit_others_media')) {
                            $args['global'] = '';
                        } else {
                            $args['global'] = array(0, $term['global']);
                        }
                    } else {
                        $args['global'] = 0;
                    }
                    $gm_terms = $gmDB->get_terms($term_type, $args);

                    $terms_items = '';
                    if(count($gm_terms)) {
                        foreach($gm_terms as $_term) {
                            $selected = (in_array($_term->term_id, $gmedia_album))? ' selected="selected"' : '';
                            $terms_items .= '<option value="' . $_term->term_id . '"' . $selected . '>' . esc_html($_term->name) . ('publish' == $_term->status? '' : " [{$_term->status}]") . ' &nbsp; (' . $_term->count . ')</option>' . "\n";
                        }
                    }
                    $setvalue = !empty($gmedia_album)? 'data-setvalue="' . implode(',', $gmedia_album) . '"' : '';
                    ?>
                    <label><?php _e('Choose Albums', 'grand-media'); ?> </label>

                    <div class="row">
                        <div class="col-xs-8">
                            <select <?php echo $setvalue; ?> id="gmedia_album" name="filter_data[gmedia_album][]" class="gmedia-combobox form-control input-sm" multiple="multiple" placeholder="<?php echo esc_attr(__('Any Album...', 'grand-media')); ?>">
                                <option value=""<?php if(empty($gmedia_album)) {
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
                    if(count($gm_terms)) {
                        foreach($gm_terms as $id => $_term) {
                            $selected = (in_array($id, $gmedia_category))? ' selected="selected"' : '';
                            $terms_items .= '<option value="' . $id . '"' . $selected . '>' . esc_html($gm_terms_all[$_term['name']]) . ' (' . $_term['count'] . ')</option>' . "\n";
                        }
                    }
                    $setvalue = !empty($gmedia_category)? 'data-setvalue="' . implode(',', $gmedia_category) . '"' : '';
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
                    if(count($gm_terms)) {
                        foreach($gm_terms as $id => $_term) {
                            $selected = (in_array($id, $gmedia_tag))? ' selected="selected"' : '';
                            $terms_items .= '<option value="' . $id . '"' . $selected . '>' . esc_html($_term['name']) . ' (' . $_term['count'] . ')</option>' . "\n";
                        }
                    }
                    $setvalue = !empty($gmedia_tag)? 'data-setvalue="' . implode(',', $gmedia_tag) . '"' : '';
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
                        <div class="pull-right"><a id="use_lib_selected" class="label label-primary" href="#libselected"><?php _e('Use selected in Library', 'grand-media'); ?></a></div>
                        <label><?php _e('Gmedia IDs <small class="text-muted">separated by comma</small>', 'grand-media'); ?> </label>
                        <?php $value = !empty($gmedia_id)? implode(',', wp_parse_id_list($gmedia_id)) : ''; ?>
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
                        <?php if($gmCore->caps['gmedia_show_others_media']) {
                            $user_ids = $gmCore->get_editable_user_ids();
                            if(!in_array($user_ID, $user_ids)) {
                                array_push($user_ids, $user_ID);
                            }
                            $filter_users = get_users(array('include' => $user_ids));
                            $users        = '';
                            if(count($filter_users)) {
                                foreach((array)$filter_users as $user) {
                                    $user->ID  = (int)$user->ID;
                                    $_selected = in_array($user->ID, $author_id)? ' selected="selected"' : '';
                                    $users .= "<option value='$user->ID'$_selected>" . esc_html($user->display_name) . "</option>";
                                }
                            }
                            $setvalue = !empty($author_id)? 'data-setvalue="' . implode(',', $author_id) . '"' : '';
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
                    <?php if($gmCore->caps['gmedia_show_others_media']) { ?>
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
                <?php foreach($filter_data['meta_query'] as $i => $q) {
                    if($i) {
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
                        <input type="text" class="form-control input-sm" value="<?php echo $filter_data['limit']; ?>" name="gmedia_filter[limit]" placeholder="<?php _e('leave empty for no limit', 'grand-media'); ?>">
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
            jQuery(function($) {
                <?php if($gmCore->caps['gmedia_terms']){ ?>
                $('.gmedia-combobox').each(function() {
                    var select = $(this).selectize({
                        create: false,
                        hideSelected: true,
                        allowEmptyOption: true
                    });
                    var val = $(this).data('setvalue');
                    if(val) {
                        val = val.toString().split(',');
                        select[0].selectize.setValue(val);
                    }
                });
                <?php } ?>

                $('#use_lib_selected').on('click', function() {
                    var field = $('#gmedia__ids');
                    var valData = field.val().split(',');
                    var storedData = getStorage('gmuser_<?php echo $user_ID; ?>_');
                    storedData = storedData.get('library').split(',');
                    valData = $.grep(valData, function(e) {
                        return e;
                    });
                    $.each(storedData, function(i, id) {
                        if(!id) {
                            return true;
                        }
                        if($.inArray(id, valData) === -1) {
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
                <?php if(!empty($termItems)) {
                    foreach($termItems as $item) {
                        $item_class = '';
                        ?>
                        <div class="gm-img-thumbnail<?php echo $item_class; ?>" data-gmid="<?php echo $item->ID; ?>"><?php
                            ?><img src="<?php echo $gmCore->gm_get_media_image($item, 'thumb', false); ?>" alt="<?php echo $item->ID; ?>" title="<?php echo esc_attr($item->title); ?>"/><?php
                            ?><span class="label label-default">ID: <?php echo $item->ID; ?></span><?php
                            ?>
                            <div class="gm-img-thumb-title"><?php echo esc_html($item->title); ?></div>
                        </div>
                        <?php
                    }
                } else {
                    if($term_id) { ?>
                        <p class="text-center"><?php _e('No items with selected parameters.') ?></p>
                    <?php } else { ?>
                        <p class="text-center"><?php _e('Set Filter parameters and click Save button to test query.') ?></p>
                    <?php }
                } ?>
            </div>
        </div>
        <div class="panel-footer clearfix" style="margin-top:20px;"><?php echo $pager_html; ?>
            <?php if($trueTotalResult) { ?>
                <div class="well well-sm pull-left" style="margin-right:10px;"><?php printf(__('Limited to: %d'), $totalResult); ?></div>
                <div class="well well-sm pull-left" style="margin:0;"><?php printf(__('Total items: %d'), $trueTotalResult); ?></div>
            <?php } else { ?>
                <div class="well well-sm pull-left" style="margin:0;"><?php printf(__('Total items: %d'), $totalResult); ?></div>
            <?php } ?>
        </div>
    </div>

    <?php if($gmCore->caps['gmedia_edit_others_media']) { ?>
        <div class="modal fade gmedia-modal" id="gallModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog"></div>
        </div>
    <?php } ?>

    <?php
}
