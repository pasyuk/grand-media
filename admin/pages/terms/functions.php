<?php

function gmedia_term_item_thumbnails( $term_item, $qty = 7 ) {
    global $gmCore, $gmDB, $gmGallery;
    ?>
    <div class="term-images">
        <?php
        if ( $term_item->count ) {
            $term__in = str_replace( 'gmedia_', '', $term_item->taxonomy ) . '__in';
            switch ( $term_item->taxonomy ) {
                case 'gmedia_tag':
                    $orderby = $gmGallery->options['in_tag_orderby'];
                    $order   = $gmGallery->options['in_tag_order'];
                break;
                case 'gmedia_category':
                    $orderby = $gmGallery->options['in_category_orderby'];
                    $order   = $gmGallery->options['in_category_order'];
                break;
                default:
                    $orderby = 'ID';
                    $order   = 'DESC';
                break;
            }
            $args = array(
                    'no_found_rows' => true,
                    'per_page'      => $qty,
                    $term__in       => array( $term_item->term_id ),
                    'author'        => gm_user_can( 'show_others_media' ) ? 0 : get_current_user_id(),
                    'orderby'       => isset( $term_item->meta['_orderby'][0] ) ? $term_item->meta['_orderby'][0] : $orderby,
                    'order'         => isset( $term_item->meta['_order'][0] ) ? $term_item->meta['_order'][0] : $order
            );

            $gmedias = $gmDB->get_gmedias( $args );
            if ( ! empty( $gmedias ) ) {
                foreach ( $gmedias as $gmedia_item ) {
                    ?>
                    <img style="z-index:<?php echo $qty --; ?>;" src="<?php echo $gmCore->gm_get_media_image( $gmedia_item, 'thumb', false ); ?>" alt="<?php echo $gmedia_item->ID; ?>" title="<?php echo esc_attr( $gmedia_item->title ); ?>"/>
                    <?php
                }
            }
            if ( count( $gmedias ) < $term_item->count ) {
                echo '...';
            }
        }
        ?>
    </div>
    <?php
}

function gmedia_term_item_actions( $item ) {
    global $gmCore, $gmProcessor;

    $taxterm = str_replace( 'gmedia_', '', $item->taxonomy );
    $actions = array();

    $actions['shortcode'] = '<div class="term-shortcode"><input type="text" readonly value="[gm ' . $taxterm . '=' . $item->term_id . ']"><div class="input-buffer"></div></div>';

    $filter_href  = $gmCore->get_admin_url( array( 'page' => 'GrandMedia', "{$taxterm}__in" => $item->term_id ), array(), true );
    $filter_class = 'gm_filter_in_lib';
    $count        = '';
    if ( in_array( $item->taxonomy, array( 'gmedia_album', 'gmedia_tag', 'gmedia_category' ) ) ) {
        $count = '<span class="gm_term_count">' . $item->count . '</span>';
        if ( ! $item->count ) {
            $filter_class .= ' action-inactive';
        }
    }
    $actions['filter'] = '<a title="' . __( 'Filter in Gmedia Library', 'grand-media' ) . '" href="' . $filter_href . '" class="' . $filter_class . '">' . $count . '<span class="glyphicon glyphicon-filter"></span></a>';

    $cloud_link = $gmCore->gmcloudlink( $item->term_id, $taxterm );
    if ( ! empty( $item->meta['_post_ID'][0] ) ) {
        $post_link = get_permalink( $item->meta['_post_ID'][0] );
    } else {
        $post_link = '';
    }
    $share_icon = '<span class="glyphicon glyphicon-share"></span>';
    if ( 'draft' !== $item->status ) {
        $actions['share'] = '<a target="_blank" data-target="#shareModal" data-share="' . $item->term_id . '" class="share-modal" title="' . __( 'Share', 'grand-media' ) . '" data-gmediacloud="' . $cloud_link . '" href="' . $post_link . '">' . $share_icon . '</a>';
    } else {
        $actions['share'] = "<span class='action-inactive'>$share_icon</span>";
    }

    if ( 'gmedia_tag' != $item->taxonomy ) {
        $edit_icon = '<span class="glyphicon glyphicon-edit"></span>';
        if ( $item->allow_edit ) {
            $actions['edit'] = '<a title="' . __( 'Edit', 'grand-media' ) . '" href="' . add_query_arg( array( "edit_item" => $item->term_id ), $gmProcessor->url ) . '">' . $edit_icon . '</a>';
        } else {
            $actions['edit'] = "<span class='action-inactive'>$edit_icon</span>";
        }
    }

    $trash_icon = '<span class="glyphicon glyphicon-trash"></span>';
    if ( $item->allow_delete ) {
        $actions['delete'] = '<a class="trash-icon" title="' . __( 'Delete', 'grand-media' ) . '" href="' . wp_nonce_url( add_query_arg( array( 'delete' => $item->term_id ), $gmProcessor->url ), 'gmedia_delete' ) . '" data-confirm="' . __( "You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media" ) . '">' . $trash_icon . '</a>';
    } else {
        $actions['delete'] = "<span class='action-inactive'>$trash_icon</span>";
    }

    /*if(gm_user_can("{$item->taxonomy}_manage")) {
        if((int)$item->global === get_current_user_id() || gm_user_can('edit_others_media')) {
            $action['edit'] = '<a title="' . __('Edit', 'grand-media') . '" href="' . add_query_arg(array("edit_item" => $item->term_id), $gmProcessor->url) . '">' . $edit_icon . '</a>';

            if(gm_user_can('terms_delete')) {
                $action['delete'] = '<a class="trash-icon" title="' . __('Delete', 'grand-media') . '" href="' . wp_nonce_url(add_query_arg(array('delete' => $item->term_id), $gmProcessor->url), 'gmedia_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . $trash_icon . '</a>';
            }
        }
    }*/


    return apply_filters( 'gmedia_term_item_actions', $actions );
}


function gmedia_term_item_more_data( &$item ) {
    global $gmDB;

    $meta       = $gmDB->get_metadata( 'gmedia_term', $item->term_id );
    $item->meta = $meta;

    if ( $item->global ) {
        $item->author_name = get_the_author_meta( 'display_name', $item->global );
    } else {
        $item->author_name = false;
    }

    if ( 'gmedia_album' == $item->taxonomy ) {
        $post_id       = isset( $meta['_post_ID'][0] ) ? (int) $meta['_post_ID'][0] : 0;
        $item->post_id = $post_id;
        if ( $post_id ) {
            $post_item = get_post( $post_id );
            if ( $post_item ) {
                $item->slug           = $post_item->post_name;
                $item->post_password  = $post_item->post_password;
                $item->comment_count  = $post_item->comment_count;
                $item->comment_status = $post_item->comment_status;
            }
        }
    }


    $item = apply_filters( 'gmedia_term_item_more_data', $item );
}

function gmedia_terms_create_album_tpl() {
    include( dirname( __FILE__ ) . '/tpl/album-create-item.php' );
}

function gmedia_terms_create_category_tpl() {
    include( dirname( __FILE__ ) . '/tpl/category-create-item.php' );
}

function gmedia_terms_create_tag_tpl() {
    include( dirname( __FILE__ ) . '/tpl/tag-create-item.php' );
}

function gmedia_terms_create_alert_tpl() {
    include( dirname( __FILE__ ) . '/tpl/terms-create-alert.php' );
}

add_action( 'gmedia_term_album_after_panel', 'gmedia_term_album_after_panel' );
function gmedia_term_album_after_panel( $term ) {
    include( dirname( __FILE__ ) . '/tpl/album-sort-gmedia.php' );
}

add_action( 'gmedia_term_category_after_panel', 'gmedia_term_category_after_panel' );
function gmedia_term_category_after_panel( $term ) {
    include( dirname( __FILE__ ) . '/tpl/category-preview-gmedia.php' );
}

add_action( 'gmedia_term_filter_after_panel', 'gmedia_term_filter_after_panel' );
function gmedia_term_filter_after_panel( $term ) {
    include( dirname( __FILE__ ) . '/tpl/filter-preview-query.php' );
}
