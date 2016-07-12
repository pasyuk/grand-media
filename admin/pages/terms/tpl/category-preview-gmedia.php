<?php // don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Preview Gmedia in Category
 *
 * @var $term
 */
if(!$term->count) {
    return;
}

global $gmDB, $gmCore, $gmGallery;

$_orderby   = isset($term->meta['_orderby'][0])? $term->meta['_orderby'][0] : $gmGallery->options['in_category_orderby'];
$_order     = isset($term->meta['_order'][0])? $term->meta['_order'][0] : $gmGallery->options['in_category_order'];
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
        <h4 style="margin:7px 40px 7px 0;" class="pull-left"><?php _e('Category Preview'); ?></h4>
        <a class="btn btn-default" href="<?php echo add_query_arg(array('page' => 'GrandMedia', 'category__in' => $term->term_id), admin_url('admin.php')); ?>"><?php _e('Open in Gmedia Library', 'grand-media'); ?></a>
        <?php echo $pager_html; ?></div>
    <div class="panel-body">
        <div class="termItems clearfix" id="termItems">
            <?php if(!empty($termItems)) {
                foreach($termItems as $item) {
                    ?>
                    <div class="gm-img-thumbnail" data-gmid="<?php echo $item->ID; ?>"><?php
                        ?><img src="<?php echo $gmCore->gm_get_media_image($item, 'thumb', false); ?>" alt="<?php echo $item->ID; ?>" title="<?php echo esc_attr($item->title); ?>"/><?php
                        ?><span class="label label-default">ID: <?php echo $item->ID; ?></span><?php
                        ?>
                        <div class="gm-img-thumb-title"><?php echo esc_html($item->title); ?></div>
                    </div>
                    <?php
                }
            } ?>
        </div>
    </div>
    <div class="panel-footer clearfix" style="margin-top:20px;"><?php echo $pager_html; ?>
        <div class="well well-sm pull-left" style="margin:0;"><?php printf(__('Total items: %d'), $term->count); ?></div>
    </div>
</div>
