<?php

function gmedia_term_item_thumbnails($term_item, $qty = 7){
    global $gmCore, $gmDB, $gmGallery;
    ?>
    <div class="term-images">
        <?php
        if($term_item->count){
            $term__in = str_replace('gmedia_', '', $term_item->taxonomy) . '__in';
            switch($term_item->taxonomy){
                case 'gmedia_album':
                    $orderby = $gmGallery->options['in_album_orderby'];
                    $order   = $gmGallery->options['in_album_order'];
                break;
                case 'gmedia_category':
                    $orderby = $gmGallery->options['in_category_orderby'];
                    $order   = $gmGallery->options['in_category_order'];
                break;
                case 'gmedia_tag':
                    $orderby = $gmGallery->options['in_tag_orderby'];
                    $order   = $gmGallery->options['in_tag_order'];
                break;
                default:
                    $orderby = 'ID';
                    $order   = 'DESC';
                break;
            }
            $args = array('no_found_rows' => true,
                          'per_page'      => $qty,
                          $term__in       => array($term_item->term_id),
                          'author'        => gm_user_can('show_others_media')? 0 : get_current_user_id(),
                          'orderby'       => isset($term_item->meta['_orderby'][0])? $term_item->meta['_orderby'][0] : $orderby,
                          'order'         => isset($term_item->meta['_order'][0])? $term_item->meta['_order'][0] : $order
            );

            $gmedias = $gmDB->get_gmedias($args);
            if(!empty($gmedias)){
                foreach($gmedias as $gmedia_item){
                    ?>
                    <img style="z-index:<?php echo $qty --; ?>;" src="<?php echo $gmCore->gm_get_media_image($gmedia_item, 'thumb', false); ?>" alt="<?php echo $gmedia_item->ID; ?>" title="<?php esc_attr_e($gmedia_item->title); ?>"/>
                    <?php
                }
            }
            if(count($gmedias) < $term_item->count){
                echo '...';
            }
        }
        ?>
    </div>
    <?php
}

function gmedia_term_item_actions($item){
    global $gmCore, $gmProcessor;

    $taxterm = $gmProcessor->taxterm;
    $actions = array();

    //$actions['shortcode'] = '<div class="term-shortcode"><input type="text" readonly value="[gm ' . $taxterm . '=' . $item->term_id . ']"><div class="input-buffer"></div></div>';

    $filter_href  = $gmCore->get_admin_url(array('page' => 'GrandMedia', "{$taxterm}__in" => $item->term_id), array(), true);
    $filter_class = 'gm_filter_in_lib';
    $count        = '';
    if(in_array($item->taxonomy, array('gmedia_album', 'gmedia_tag', 'gmedia_category'))){
        $count = '<span class="gm_term_count">' . $item->count . '</span>';
        if(!$item->count){
            $filter_class .= ' action-inactive';
        }
    }
    $actions['filter'] = '<a title="' . __('Filter in Gmedia Library', 'grand-media') . '" href="' . $filter_href . '" class="' . $filter_class . '">' . $count . '<span class="glyphicon glyphicon-filter"></span></a>';

    $share_icon = '<span class="glyphicon glyphicon-share"></span>';
    if('draft' !== $item->status){
        $actions['share'] = '<a target="_blank" data-target="#shareModal" data-share="' . $item->term_id . '" class="text-warning share-modal" title="' . __('Share', 'grand-media') . '" data-gmediacloud="' . $item->cloud_link . '" href="' . $item->post_link . '">' . $share_icon . ' ' . __('Share', 'grand-media') . '</a>';
    } else{
        $actions['share'] = '<span class="action-inactive">' . $share_icon . ' ' . __('Share', 'grand-media') . '</span>';
    }

    $trash_icon = '<span class="glyphicon glyphicon-trash"></span>';
    if($item->allow_delete){
        $actions['delete'] = '<a class="trash-icon" title="' . __('Delete', 'grand-media') . '" href="' . wp_nonce_url(add_query_arg(array('do_gmedia_terms' => 'delete', 'ids' => $item->term_id), $gmProcessor->url), 'gmedia_delete', '_wpnonce_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . $trash_icon . '</a>';
    } else{
        $actions['delete'] = "<span class='action-inactive'>$trash_icon</span>";
    }

    return apply_filters('gmedia_term_item_actions', $actions);
}

function gmedia_terms_create_album_tpl(){
    include(dirname(__FILE__) . '/tpl/album-create-item.php');
}

function gmedia_terms_create_category_tpl(){
    include(dirname(__FILE__) . '/tpl/category-create-item.php');
}

function gmedia_terms_create_tag_tpl(){
    include(dirname(__FILE__) . '/tpl/tag-create-item.php');
}

function gmedia_terms_create_alert_tpl(){
    include(dirname(__FILE__) . '/tpl/terms-create-alert.php');
}

add_action('gmedia_term_album_after_panel', 'gmedia_term_album_after_panel');
function gmedia_term_album_after_panel($term){
    global $gmCore, $gmProcessor, $gmProcessorLibrary;

    $taxin                                            = "{$gmProcessor->taxterm}__in";
    $gmProcessorLibrary->query_args['terms_relation'] = 'AND';
    if(!empty($gmProcessorLibrary->query_args[ $taxin ])){
        $gmProcessorLibrary->query_args["{$gmProcessor->taxterm}__and"] = wp_parse_id_list(array_merge($gmProcessorLibrary->query_args[ $taxin ], array($term->term_id)));
        unset($gmProcessorLibrary->query_args[ $taxin ]);
    } else{
        $gmProcessorLibrary->query_args[ $taxin ] = array((int)$term->term_id);
    }
    $gmProcessorLibrary->display_mode = 'grid';

    $gmProcessor = $gmProcessorLibrary;

    $atts = 'class="gmedia_term__in"';
    if(isset($term->meta['_orderby'][0]) && ('custom' == $term->meta['_orderby'][0])){
        $atts .= ' id="gm-sortable" data-term_id="' . $term->term_id . '" data-action="gmedia_term_sortorder" data-_wpnonce_terms="' . wp_create_nonce('gmedia_terms') . '"';
        add_action('before_gmedia_filter_message', 'before_gmedia_filter_message');
    } else {
        add_action('before_gmedia_filter_message', 'before_gmedia_filter_message2');
    }
    echo "<div {$atts}>";
    echo $gmCore->alert('success', $gmProcessor->msg);
    echo $gmCore->alert('danger', $gmProcessor->error);
    include(GMEDIA_ABSPATH . 'admin/pages/library/library.php');
    echo '</div>';
}

function before_gmedia_filter_message(){
    global $gmProcessorLibrary;
    if(empty($gmProcessorLibrary->dbfilter)){
        echo '<div class="custom-message alert alert-info">' . __("You can drag'n'drop items below to reorder. Order saves automatically after you drop the item. Also you can set order position number manually when edit item.", 'grand-media') . '</div>';
    } else{
        echo '<div class="custom-message alert alert-warning">' . __("Drag'n'drop functionality disabled. Reset filters to enable drag'n'drop.", 'grand-media') . '</div>';
    }
}
function before_gmedia_filter_message2(){
    echo '<div class="custom-message alert alert-info">' . __("To enable drag'n'drop to reorder functionality for items you must update album's `Order gmedia` field to `Custom Order`.", 'grand-media') . '</div>';
}

add_action('gmedia_term_category_after_panel', 'gmedia_term_category_after_panel');
function gmedia_term_category_after_panel($term){
    global $gmCore, $gmProcessor, $gmProcessorLibrary;

    $taxin                                            = "{$gmProcessor->taxterm}__in";
    $gmProcessorLibrary->query_args['terms_relation'] = 'AND';
    if(!empty($gmProcessorLibrary->query_args[ $taxin ])){
        $gmProcessorLibrary->query_args["{$gmProcessor->taxterm}__and"] = wp_parse_id_list(array_merge($gmProcessorLibrary->query_args[ $taxin ], array($term->term_id)));
        unset($gmProcessorLibrary->query_args[ $taxin ]);
    } else{
        $gmProcessorLibrary->query_args[ $taxin ] = array((int)$term->term_id);
    }
    $gmProcessorLibrary->display_mode = 'grid';

    $gmProcessor = $gmProcessorLibrary;

    $atts = 'class="gmedia_term__in"';
    echo "<div {$atts}>";
    echo $gmCore->alert('success', $gmProcessor->msg);
    echo $gmCore->alert('danger', $gmProcessor->error);
    include(GMEDIA_ABSPATH . 'admin/pages/library/library.php');
    echo '</div>';
}
