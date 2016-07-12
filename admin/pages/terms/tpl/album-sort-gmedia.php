<?php // don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Sort Gmedia in Album
 *
 * @var $term
 */
global $gmDB, $gmCore;

$_orderby   = isset($term->meta['_orderby'][0])? $term->meta['_orderby'][0] : '';
$_order     = isset($term->meta['_order'][0])? $term->meta['_order'][0] : '';
$per_page   = !empty($gmedia_user_options['per_page_sort_gmedia'])? $gmedia_user_options['per_page_sort_gmedia'] : 60;
$cur_page   = $gmCore->_get('pager', 1);

$args = array(
        'album__in' => $term->term_id,
        'orderby'   => $_orderby,
        'order'     => $_order,
        'per_page'  => $per_page,
        'page'      => $cur_page
);

$termItems  = $gmDB->get_gmedias($args);
$pager_html = $gmDB->query_pager();


?>
<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <h4 style="margin:7px 40px 7px 0;" class="pull-left"><?php _e('Sort Album'); ?></h4>
        <a class="btn btn-default" href="<?php echo add_query_arg(array('page' => 'GrandMedia', 'album__in' => $term->term_id), admin_url('admin.php')); ?>"><?php _e('Open in Gmedia Library', 'grand-media'); ?></a>
        <?php echo $pager_html; ?>

    </div>
    <form method="post" id="gmedia-sort-term" name="gmSortTerm" class="panel-body">
        <div class="order-form" style="border-bottom:1px solid #ddd; margin-bottom:15px;">
            <div class="row">
                <div class="col-xs-3">
                    <div class="form-group">
                        <label><?php _e('Order gmedia', 'grand-media'); ?></label>
                        <select name="term[meta][_orderby]" id="gmedia_term_orderby" class="form-control input-sm">
                            <option value="custom"<?php selected($_orderby, 'custom'); ?>><?php _e('Custom Order', 'grand-media'); ?></option>
                            <option value="ID"<?php selected($_orderby, 'ID'); ?>><?php _e('by ID', 'grand-media'); ?></option>
                            <option value="title"<?php selected($_orderby, 'title'); ?>><?php _e('by title', 'grand-media'); ?></option>
                            <option value="gmuid"<?php selected($_orderby, 'gmuid'); ?>><?php _e('by filename', 'grand-media'); ?></option>
                            <option value="date"<?php selected($_orderby, 'date'); ?>><?php _e('by date', 'grand-media'); ?></option>
                            <option value="modified"<?php selected($_orderby, 'modified'); ?>><?php _e('by last modified date', 'grand-media'); ?></option>
                            <option value="rand"<?php selected($_orderby, 'rand'); ?>><?php _e('Random', 'grand-media'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="form-group">
                        <label><?php _e('Sort order', 'grand-media'); ?></label>
                        <select id="gmedia_term_order" name="term[meta][_order]" class="form-control input-sm">
                            <option value="DESC"<?php selected($_order, 'DESC'); ?>><?php _e('DESC', 'grand-media'); ?></option>
                            <option value="ASC"<?php selected($_order, 'ASC'); ?>><?php _e('ASC', 'grand-media'); ?></option>
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
                    <?php
                    wp_nonce_field('GmediaTerms', 'term_save_wpnonce');
                    wp_referer_field();
                    ?>
                    <input type="hidden" name="term[term_id]" value="<?php echo $term->term_id; ?>"/>
                    <input type="hidden" name="term[taxonomy]" value="<?php echo $term->taxonomy; ?>"/>
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
                    $('#gmedia_term_order').val('ASC');
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
                $('#gmedia_term_order').val('ASC');
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
