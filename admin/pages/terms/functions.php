<?php

function gmedia_term_item_thumbnails( $term_item, $qty = 7 ) {
	global $gmCore, $gmDB, $gmGallery;
	?>
	<div class="term-images">
		<?php
		if ( $term_item->count ) {
			$term__in = str_replace( 'gmedia_', '', $term_item->taxonomy ) . '__in';
			switch ( $term_item->taxonomy ) {
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
			$args = array(
				'no_found_rows' => true,
				'per_page'      => $qty,
				$term__in       => array( $term_item->term_id ),
				'author'        => gm_user_can( 'show_others_media' ) ? 0 : get_current_user_id(),
				'orderby'       => isset( $term_item->meta['_orderby'][0] ) ? $term_item->meta['_orderby'][0] : $orderby,
				'order'         => isset( $term_item->meta['_order'][0] ) ? $term_item->meta['_order'][0] : $order,
			);

			$gmedias = $gmDB->get_gmedias( $args );
			if ( ! empty( $gmedias ) ) {
				foreach ( $gmedias as $gmedia_item ) {
					?>
					<img style="z-index:<?php echo (int) $qty --; ?>;" src="<?php echo esc_url( $gmCore->gm_get_media_image( $gmedia_item, 'thumb', false ) ); ?>" alt="<?php echo esc_attr( $gmedia_item->ID ); ?>" title="<?php echo esc_attr( $gmedia_item->title ); ?>"/>
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

	$taxterm = $gmProcessor->taxterm;
	$actions = array();

	//$actions['shortcode'] = '<div class="term-shortcode"><input type="text" readonly value="[gm ' . $taxterm . '=' . $item->term_id . ']"><div class="input-buffer"></div></div>';

	$filter_href  = $gmCore->get_admin_url( array( 'page' => 'GrandMedia', "{$taxterm}__in" => $item->term_id ), array(), true );
	$filter_class = 'gm_filter_in_lib';
	$count        = '';
	if ( in_array( $item->taxonomy, array( 'gmedia_album', 'gmedia_tag', 'gmedia_category' ), true ) ) {
		$count = '<span class="gm_term_count">' . intval( $item->count ) . '</span>';
		if ( ! $item->count ) {
			$filter_class .= ' action-inactive';
		}
	}
	$actions['filter'] = '<a title="' . esc_html__( 'Filter in Gmedia Library', 'grand-media' ) . '" href="' . esc_url( $filter_href ) . '" class="' . esc_attr( $filter_class ) . '">' . $count . '<i class="fa-solid fa-filter"></i></a>';

	$share_icon = '<i class="fa-solid fa-share-from-square"></i>';
	if ( 'draft' !== $item->status ) {
		$actions['share'] = '<a target="_blank" data-bs-toggle="modal" data-bs-target="#shareModal" data-share="' . esc_attr( $item->term_id ) . '" class="text-warning share-modal" title="' . esc_attr__( 'Share', 'grand-media' ) . '" data-gmediacloud="' . esc_url( $item->cloud_link ) . '" href="' . esc_url( $item->post_link ) . '">' . wp_kses_post( $share_icon . ' ' . __( 'Share', 'grand-media' ) ) . '</a>';
	} else {
		$actions['share'] = '<span class="action-inactive">' . wp_kses_post( $share_icon . ' ' . __( 'Share', 'grand-media' ) ) . '</span>';
	}

	$trash_icon = '<i class="fa-solid fa-trash-can"></i>';
	if ( $item->allow_delete ) {
		$actions['delete'] = '<a class="trash-icon link-danger" title="' . esc_attr__( 'Delete', 'grand-media' ) . '" href="' . esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'do_gmedia_terms' => 'delete',
							'ids'             => $item->term_id,
						),
						$gmProcessor->url
					),
					'gmedia_delete',
					'_wpnonce_delete'
				)
			) . '" data-confirm="' . esc_attr__( "You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ) . '">' . wp_kses_post( $trash_icon ) . '</a>';
	} else {
		$actions['delete'] = '<span class="action-inactive">' . wp_kses_post( $trash_icon ) . '</span>';
	}

	return apply_filters( 'gmedia_term_item_actions', $actions );
}

function gmedia_terms_create_album_tpl() {
	include dirname( __FILE__ ) . '/tpl/album-create-item.php';
}

function gmedia_terms_create_category_tpl() {
	include dirname( __FILE__ ) . '/tpl/category-create-item.php';
}

function gmedia_terms_create_tag_tpl() {
	include dirname( __FILE__ ) . '/tpl/tag-create-item.php';
}

function gmedia_terms_create_alert_tpl() {
	include dirname( __FILE__ ) . '/tpl/terms-create-alert.php';
}

add_action( 'gmedia_term_album_after_panel', 'gmedia_term_album_after_panel' );
function gmedia_term_album_after_panel( $term ) {
	global $gmCore, $gmProcessor, $gmProcessorLibrary;

	$taxin = "{$gmProcessor->taxterm}__in";

	$gmProcessorLibrary->query_args['terms_relation'] = 'AND';
	if ( ! empty( $gmProcessorLibrary->query_args[ $taxin ] ) ) {
		$gmProcessorLibrary->query_args["{$gmProcessor->taxterm}__and"] = wp_parse_id_list( array_merge( $gmProcessorLibrary->query_args[ $taxin ], array( $term->term_id ) ) );
		unset( $gmProcessorLibrary->query_args[ $taxin ] );
	} else {
		$gmProcessorLibrary->query_args[ $taxin ] = array( (int) $term->term_id );
	}
	$gmProcessorLibrary->display_mode = 'grid';

	$gmProcessor = $gmProcessorLibrary;

	$atts = 'class="gmedia_term__in"';
	if ( isset( $term->meta['_orderby'][0] ) && ( 'custom' === $term->meta['_orderby'][0] ) ) {
		$atts .= ' id="gm-sortable" data-term_id="' . esc_attr( $term->term_id ) . '" data-action="gmedia_term_sortorder" data-_wpnonce_terms="' . esc_attr( wp_create_nonce( 'gmedia_terms' ) ) . '"';
		add_action( 'before_gmedia_filter_message', 'before_gmedia_filter_message' );
	} else {
		add_action( 'before_gmedia_filter_message', 'before_gmedia_filter_message2' );
	}
	echo '<div ' . wp_kses_data( $atts ) . '>';
	echo wp_kses_post( $gmCore->alert( 'success', $gmProcessor->msg ) );
	echo wp_kses_post( $gmCore->alert( 'danger', $gmProcessor->error ) );
	include GMEDIA_ABSPATH . 'admin/pages/library/library.php';
	echo '</div>';
}

function before_gmedia_filter_message() {
	global $gmProcessorLibrary;
	if ( empty( $gmProcessorLibrary->dbfilter ) ) {
		echo '<div class="custom-message alert alert-info">' . esc_html__( "You can drag'n'drop items below to reorder. Order saves automatically after you drop the item. Also you can set order position number manually when edit item.", 'grand-media' ) . '</div>';
	} else {
		echo '<div class="custom-message alert alert-warning">' . esc_html__( "Drag'n'drop functionality disabled. Reset filters to enable drag'n'drop.", 'grand-media' ) . '</div>';
	}
}

function before_gmedia_filter_message2() {
	echo '<div class="custom-message alert alert-info">' . esc_html__( "To enable drag'n'drop to reorder functionality for items you must update album's `Order gmedia` field to `Custom Order`.", 'grand-media' ) . '</div>';
}

add_action( 'gmedia_term_category_after_panel', 'gmedia_term_category_after_panel' );
function gmedia_term_category_after_panel( $term ) {
	global $gmCore, $gmProcessor, $gmProcessorLibrary;

	$taxin = "{$gmProcessor->taxterm}__in";

	$gmProcessorLibrary->query_args['terms_relation'] = 'AND';
	if ( ! empty( $gmProcessorLibrary->query_args[ $taxin ] ) ) {
		$gmProcessorLibrary->query_args["{$gmProcessor->taxterm}__and"] = wp_parse_id_list( array_merge( $gmProcessorLibrary->query_args[ $taxin ], array( $term->term_id ) ) );
		unset( $gmProcessorLibrary->query_args[ $taxin ] );
	} else {
		$gmProcessorLibrary->query_args[ $taxin ] = array( (int) $term->term_id );
	}
	$gmProcessorLibrary->display_mode = 'grid';

	$gmProcessor = $gmProcessorLibrary;

	echo '<div class="gmedia_term__in">';
	echo wp_kses_post( $gmCore->alert( 'success', $gmProcessor->msg ) );
	echo wp_kses_post( $gmCore->alert( 'danger', $gmProcessor->error ) );
	include GMEDIA_ABSPATH . 'admin/pages/library/library.php';
	echo '</div>';
}
