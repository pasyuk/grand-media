<?php

function gmedia_item_thumbnail( $item ) {
	global $gmCore;

	$images = $gmCore->gm_get_media_image( $item, 'all' );
	$thumb  = '<img class="gmedia-thumb" src="' . esc_url( $images['thumb'] ) . '" alt=""/>';

	if ( ! empty( $images['icon'] ) ) {
		$thumb .= '<img class="gmedia-typethumb" src="' . esc_url( $images['icon'] ) . '" alt=""/>';
	}

	return $thumb;
}

function gmedia_item_actions( $item ) {
	global $gmCore, $gmProcessor;

	$edit_data  = '';
	$edit_image = '';
	$info       = '';
	$share      = '';
	$delete     = '';
	$db_delete  = '';

	if ( ! in_array( $gmProcessor->mode, array( 'select_single', 'select_multiple' ), true ) ) {
		$share_icon = '<i class="fa-solid fa-share-from-square"></i>';
		if ( 'draft' !== $item->status ) {
			if ( ! empty( $item->post_id ) ) {
				$cloud_link = get_permalink( $item->post_id );
			} else {
				$cloud_link = $gmCore->gmcloudlink( $item->ID, 'single' );
			}
			$share = '<a target="_blank" data-bs-toggle="modal" data-bs-target="#shareModal" data-share="' . $item->ID . '" class="share-modal" title="' . __( 'Share Gmedia Post', 'grand-media' ) . '" href="' . $cloud_link . '">' . $share_icon . '</a>';
		} else {
			$share = "<span class='action-inactive'>$share_icon</span>";
		}

		$edit_icon = '<i class="fa-solid fa-pen-to-square"></i>';
		if ( gm_user_can( 'edit_media' ) ) {
			if ( ( get_current_user_id() === (int) $item->author ) || gm_user_can( 'edit_others_media' ) ) {
				$edit_data_data = $gmProcessor->gmediablank ? '' : '  data-bs-toggle="modal" data-bs-target="#previewModal" data-width="1200" data-height="500" data-cls="edit_gmedia_item" class="preview-modal"';
				$edit_data      = '<a href="' . esc_url(
						add_query_arg(
							array(
								'page'        => 'GrandMedia',
								'mode'        => 'edit',
								'gmediablank' => 'library',
								'gmedia__in'  => $item->ID,
							),
							$gmProcessor->url
						)
					) . '"' . $edit_data_data . ' id="gmdataedit' . intval( $item->ID ) . '" title="' . esc_html__( 'Edit Data', 'grand-media' ) . '">' . $edit_icon . '</a>';
			}
		} else {
			$edit_data = "<span class='action-inactive'>$edit_icon</span>";
		}

		$info_icon = '<i class="fa-solid fa-circle-info"></i>';
		$metainfo  = $gmCore->metadata_text( $item->ID );
		if ( $metainfo ) {
			$info = '<a href="#metaInfo" data-bs-toggle="modal" data-bs-target="#previewModal" data-metainfo="' . intval( $item->ID ) . '" class="preview-modal" title="' . esc_html__( 'Exif/Meta Info', 'grand-media' ) . '">' . $info_icon . '</a>';
			$info .= '<div class="metainfo hidden" id="metainfo_' . intval( $item->ID ) . '">' . nl2br( $metainfo ) . '</div>';
		} else {
			$info = "<span class='action-inactive'>$info_icon</span>";
		}

		$delete_icon = '<i class="fa-solid fa-trash-can"></i>';
		if ( ( gm_user_can( 'delete_media' ) && ( get_current_user_id() === (int) $item->author ) ) || gm_user_can( 'delete_others_media' ) ) {
			$delete = '<a class="link-danger" href="' . esc_url(
					wp_nonce_url(
						gm_get_admin_url(
							array(
								'do_gmedia' => 'delete',
								'ids'       => $item->ID,
							)
						),
						'gmedia_delete',
						'_wpnonce_delete'
					)
				// translators: file path.
				) . '" data-confirm="' . sprintf( esc_html__( "You are about to permanently delete %s file.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ), esc_url( $item->gmuid ) ) . '" title="' . esc_html__( 'Delete', 'grand-media' ) . '">' . $delete_icon . '</a>';

			if ( $gmCore->_get( 'showmore' ) ) {
				$erase_icon = '<i class="fa-solid fa-delete-left"></i>';
				$db_delete  = '<a class="link-danger" href="' . esc_url(
						wp_nonce_url(
							gm_get_admin_url(
								array(
									'do_gmedia' => 'delete__save_original',
									'ids'       => $item->ID,
								)
							),
							'gmedia_delete',
							'_wpnonce_delete'
						)
					// translators: file path.
					) . '" data-confirm="' . sprintf( esc_html__( "You are about to delete record from DB for %s file.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ), esc_url( $item->gmuid ) ) . '" title="' . esc_html__( 'Delete DB record (leave file on the server)', 'grand-media' ) . '">' . $erase_icon . '</a>';
			}
		} else {
			$delete = "<span class='action-inactive'>$delete_icon</span>";
		}
	}

	if ( 'image' === $item->type && $item->editor ) {
		$edit_image_icon = '<i class="fa-solid fa-circle-half-stroke"></i>';
		if ( ( gm_user_can( 'edit_media' ) && ( get_current_user_id() === (int) $item->author ) ) || gm_user_can( 'edit_others_media' ) ) {
			$edit_image = '<a href="' . esc_url(
					add_query_arg(
						array(
							'page'        => 'GrandMedia',
							'gmediablank' => 'image_editor',
							'id'          => $item->ID,
						),
						$gmProcessor->url
					)
				) . '" data-bs-toggle="modal" data-bs-target="#gmeditModal" class="gmedit-modal" id="gmimageedit' . intval( $item->ID ) . '" title="' . esc_html__( 'Edit Image', 'grand-media' ) . '">' . $edit_image_icon . '</a>';
		} else {
			$edit_image = "<span class='action-inactive'>$edit_image_icon</span>";
		}

		$show_icon = '<i class="fa-solid fa-maximize"></i>';
		$show      = '<a href="' . esc_url( $gmCore->gm_get_media_image( $item, 'web' ) ) . '" data-bs-toggle="modal" data-bs-target="#previewModal" data-width="' . esc_attr( $item->msize['width'] ) . '" data-height="' . esc_attr( $item->msize['height'] ) . '" class="preview-modal" title="' . esc_attr__( 'Show Large Image', 'grand-media' ) . '">' . $show_icon . '</a>';

	} elseif ( in_array( $item->ext, array( 'mp3', 'ogg', 'wav', 'ogg', 'mp4', 'mpeg', 'webm' ), true ) ) {
		$show_icon = '<i class="fa-solid fa-play"></i>';
		$show      = '<a href="' . esc_url( $item->url ) . '" data-bs-toggle="modal" data-bs-target="#previewModal" data-width="' . esc_attr( $item->msize['width'] ) . '" data-height="' . esc_attr( $item->msize['height'] ) . '" class="preview-modal" title="' . esc_attr__( 'Play', 'grand-media' ) . '">' . $show_icon . '</a>';
	} else {
		$show_icon = '<i class="fa-solid fa-cloud-arrow-down"></i>';
		$show      = '<a href="' . esc_url( $item->url ) . '" title="' . esc_html__( 'Download', 'grand-media' ) . '" download="' . esc_attr( $item->gmuid ) . '">' . $show_icon . '</a>';
	}

	$duplicate_icon = '<i class="fa-solid fa-copy"></i>';
	$duplicate      = '<a href="' . wp_nonce_url( gm_get_admin_url( array(
			'do_gmedia' => 'duplicate',
			'ids'       => $item->ID
		) ), 'gmedia_action', '_wpnonce_action' ) . '" title="' . __( 'Duplicate', 'grand-media' ) . '">' . $duplicate_icon . '</a>';

	$display_mode_gmedia = $gmProcessor->display_mode;
	if ( 'grid' === $display_mode_gmedia ) {
		$actions = compact( 'edit_data', 'edit_image', 'show', 'info', 'duplicate' );
	} else {
		$actions = compact( 'edit_data', 'edit_image', 'show', 'info', 'duplicate', 'share', 'delete', 'db_delete' );
	}

	return apply_filters( 'gmedia_item_actions', array_filter( $actions ) );
}


function gmedia_filter_message() {
	global $gmProcessor;
	do_action( 'before_gmedia_filter_message' );
	if ( ! empty( $gmProcessor->filters ) ) {
		echo '<div class="custom-message alert alert-info">';
		foreach ( $gmProcessor->filters as $key => $value ) {
			echo '<div class="custom-message-row">';
			echo '<strong><a href="#libModal" data-bs-toggle="modal" data-modal="' . esc_attr( $key ) . '" data-action="gmedia_get_modal" class="gmedia-modal">' . esc_html( $value['title'] ) . '</a>: </strong>';
			echo esc_html( implode( ', ', $value['filter'] ) );
			echo '</div>';
		}
		echo '</div>';
	}
}

function gmedia_alert_message() {
	global $gmProcessor;
	do_action( 'before_gmedia_alert_message' );
	if ( ( 'edit' === $gmProcessor->mode ) && gm_user_can( 'show_others_media' ) && ! gm_user_can( 'edit_others_media' ) ) {
		?>
        <div class="alert alert-warning alert-dismissible" role="alert" style="margin-bottom:0">
            <button type="button" class="btn-close float-end m-0" data-bs-dismiss="alert" aria-label="Close"></button>
            <strong><?php esc_html_e( 'Info:', 'grand-media' ); ?></strong> <?php esc_html_e( 'You are not allowed to edit others media', 'grand-media' ); ?>
        </div>
		<?php
	}
}

/**
 * @param object $item
 *
 * @return string
 */
function gmedia_waveform_player( $item ) {
	global $gmDB;
	$peaks = $gmDB->get_metadata( 'gmedia', $item->ID, '_peaks', true );
	if ( $peaks ) {
		if ( '[]' === $peaks ) {
			$gmDB->delete_metadata( 'gmedia', $item->ID, '_peaks' );
			$peaks = '';
		} else {
			$peaks = json_decode( $peaks );
			while ( 900 < count( $peaks ) ) {
				$chunks = array_chunk( $peaks, 2 );
				$peaks = [];
				foreach ($chunks as &$chunk) {
					$peaks[] = reset($chunk);
				}
			}
			$peaks = wp_json_encode( $peaks );
		}
	} else {
		$peaks = '';
	}

	return '
                <div class="gm-waveform-player" data-id="' . intval( $item->ID ) . '" data-file="' . esc_url( $item->url ) . '" data-peaks="' . esc_attr( $peaks ) . '">
                    <div id="ws' . intval( $item->ID ) . '"></div>' . ( $peaks ? '' : ( '<button type="button" class="btn btn-sm btn-info gm-waveform">' . esc_html__( 'Create & Save WaveForm', 'grand-media' ) . '</button>' ) ) . '<button type="button" class="btn btn-sm btn-info gm-play" style="display:none;">' . esc_html__( 'Play', 'grand-media' ) . '</button>
                    <button type="button" class="btn btn-sm btn-info gm-pause" style="display:none;">' . esc_html__( 'Pause', 'grand-media' ) . '</button>
                    <span style="float:none;" class="spinner"></span>
                </div>';
}
