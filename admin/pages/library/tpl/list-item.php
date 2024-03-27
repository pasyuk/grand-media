<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Gmedia Item for List View in Library
 *
 * @var $gmProcessor
 * @var $gmCore
 * @var $gmDB
 * @var $item
 * @var $gmedia_url
 */
?>
<div class="cb_list-item list-group-item d-row clearfix <?php echo esc_attr( implode( ' ', $item->classes ) ); ?>" id="list-item-<?php echo absint( $item->ID ); ?>" data-id="<?php echo absint( $item->ID ); ?>" data-type="<?php echo esc_attr( $item->type ); ?>">
	<div class="gmedia_id">#<?php echo absint( $item->ID ); ?></div>
	<div class="col-4" style="max-width:310px;">
		<div class="thumbwrap">
			<div class="cb_media-object">
				<span data-clicktarget="gmcheckbox<?php echo absint( $item->ID ); ?>" class="img-thumbnail">
					<?php echo wp_kses_post( gmedia_item_thumbnail( $item ) ); ?>
				</span>
			</div>
			<label class="gm-item-check" id="gmcheckbox<?php echo absint( $item->ID ); ?>"><input name="doaction[]" type="checkbox"<?php echo $item->selected ? ' checked="checked"' : ''; ?> data-type="<?php echo esc_attr( $item->type ); ?>" value="<?php echo absint( $item->ID ); ?>"/></label>
			<label class="gm-stack hidden"><input name="stack[]" type="checkbox"<?php echo $item->in_stack ? ' checked="checked"' : ''; ?> data-type="<?php echo esc_attr( $item->type ); ?>" value="<?php echo absint( $item->ID ); ?>"/></label>
		</div>
		<?php
		if ( 'audio' === $item->type ) {
			echo wp_kses_post( gmedia_waveform_player( $item ) );
		}
		?>
		<div class="related-media-previews">
			<?php
			$related_ids = isset( $item->meta['_related'][0] ) ? $item->meta['_related'][0] : array();
			if ( ! empty( $related_ids ) ) {
				$related_media = $gmDB->get_gmedias( array( 'gmedia__in' => $related_ids, 'orderby' => 'gmedia__in' ) );
				foreach ( $related_media as $r_item ) {
					?>
					<p class="img-thumbnail gmedia-related-image"><span class="image-wrapper"><?php echo wp_kses_post( gmedia_item_thumbnail( $r_item ) ); ?></span></p>
					<?php
				}
			}
			?>
		</div>
	</div>
	<div class="col-8">
		<div class="row" style="margin:0;">
			<div class="col-lg-6">
				<p class="media-title"><?php echo esc_html( $item->title ); ?>&nbsp;</p>

				<div class="in-library media-caption"><?php echo nl2br( esc_html( $item->description ) ); ?></div>

				<p class="media-meta">
					<span class="badge label-default"><?php esc_html_e( 'Author', 'grand-media' ); ?>:</span> <?php printf( '<a class="gmedia-author" href="%s">%s</a>', esc_url( add_query_arg( array( 'author' => $item->author ), $gmedia_url ) ), esc_html( get_user_option( 'display_name', $item->author ) ) ); ?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Album', 'grand-media' ); ?>:</span>
					<?php
					if ( $item->album ) {
						$terms_album = array();
						foreach ( $item->album as $c ) {
							$terms_album[] = sprintf( '<a class="album" href="%s">%s</a>', esc_url( add_query_arg( array( 'album__in' => $c->term_id ), $gmedia_url ) ), esc_html( $c->name ) );
						}
						$terms_album = join( ', ', $terms_album );
					} else {
						$terms_album = sprintf( '<a class="album" href="%s">%s</a>', esc_url( add_query_arg( array( 'album__in' => 0 ), $gmedia_url ) ), '&#8212;' );
					}
					echo wp_kses_post( $terms_album );
					?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Category', 'grand-media' ); ?>:</span>
					<?php
					if ( $item->categories ) {
						$terms_category = array();
						foreach ( $item->categories as $c ) {
							$terms_category[] = sprintf( '<a class="category" href="%s">%s</a>', esc_url( add_query_arg( array( 'category__in' => $c->term_id ), $gmedia_url ) ), esc_html( $c->name ) );
						}
						$terms_category = join( ', ', $terms_category );
					} else {
						$terms_category = sprintf( '<a class="category" href="%s">%s</a>', esc_url( add_query_arg( array( 'category__in' => 0 ), $gmedia_url ) ), __( 'Uncategorized', 'grand-media' ) );
					}
					echo wp_kses_post( $terms_category );
					?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Tags', 'grand-media' ); ?>:</span>
					<?php
					if ( $item->tags ) {
						$terms_tag = array();
						foreach ( $item->tags as $c ) {
							$terms_tag[] = sprintf( '<a class="tag" href="%s">%s</a>', esc_url( add_query_arg( array( 'tag__in' => $c->term_id ), $gmedia_url ) ), esc_html( $c->name ) );
						}
						$terms_tag = join( ', ', $terms_tag );
					} else {
						$terms_tag = '&#8212;';
					}
					echo wp_kses_post( $terms_tag );
					?>
				</p>
			</div>
			<div class="col-lg-6">
				<div class="media-meta gmedia-actions" style="margin:0 0 10px 0;">
					<?php
					$media_action_links = gmedia_item_actions( $item );
					echo wp_kses_post( implode( ' | ', $media_action_links ) );
					?>
				</div>
				<?php if ( isset( $item->post_id ) ) { ?>
					<p class="media-meta">
						<span class="badge label-default"><?php esc_html_e( 'Comments', 'grand-media' ); ?>:</span>
						<a
							href="<?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia', 'gmediablank' => 'comments', 'gmedia_id' => $item->ID ), $gmProcessor->url ) ); ?>"
							data-bs-toggle="modal"
							data-bs-target="#previewModal"
							data-width="900"
							data-height="500"
							class="preview-modal gmpost-com-count"
							title="<?php esc_attr_e( 'Comments', 'grand-media' ); ?>">
							<b class="comment-count"><?php echo intval( $item->comment_count ); ?></b>
							<i class='fa-solid fa-comment'></i>
						</a>
					</p>
				<?php } ?>
				<p class="media-meta">
					<span class="badge label-default"><?php esc_html_e( 'Views / Likes', 'grand-media' ); ?>:</span>
					<?php echo intval( isset( $item->meta['views'][0] ) ? $item->meta['views'][0] : 0 ) . ' / ' . intval( isset( $item->meta['likes'][0] ) ? $item->meta['likes'][0] : 0 ); ?>

					<?php
					if ( isset( $item->meta['_rating'][0] ) ) {
						$ratings = maybe_unserialize( $item->meta['_rating'][0] );
						?>
						<br/><span class="badge label-default"><?php esc_html_e( 'Rating', 'grand-media' ); ?>:</span> <?php echo esc_html( round( $ratings['value'], 2 ) . ' / ' . $ratings['votes'] ); ?>
					<?php } ?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Status', 'grand-media' ); ?>:</span> <?php echo esc_html( $item->status ); ?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Link', 'grand-media' ); ?>:</span>
					<?php if ( ! empty( $item->link ) ) { ?>
						<a href="<?php echo esc_url( $item->link ); ?>"><?php echo esc_html( $item->link ); ?></a>
						<?php
					} else {
						echo '&#8212;';
					}
					?>
					<?php if ( ! empty( $item->gps ) ) { ?>
						<br/><span class="badge label-default"><?php esc_html_e( 'GPS Location', 'grand-media' ); ?>:</span> <?php echo esc_html( $item->gps ); ?>
					<?php } ?>
				</p>
				<p class="media-meta">
					<span class="badge label-default"><?php esc_html_e( 'Type', 'grand-media' ); ?>:</span> <?php echo esc_html( $item->mime_type ); ?>
					<?php
					if ( ( 'image' === $item->type ) && $item->editor && ! empty( $item->meta['_metadata'] ) ) {
						?>
						<br/><span class="badge label-default"><?php esc_html_e( 'Dimensions', 'grand-media' ); ?>:</span>
						<?php
						$is_file_original = (bool) $item->path_original;
						if ( $is_file_original ) {
							?>
							<a href="<?php echo esc_url( $item->url_original ); ?>"
								data-bs-toggle="modal" data-bs-target="#previewModal"
								data-width="<?php echo esc_attr( $item->meta['_metadata'][0]['original']['width'] ); ?>"
								data-height="<?php echo esc_attr( $item->meta['_metadata'][0]['original']['height'] ); ?>"
								class="preview-modal"
								title="<?php esc_attr_e( 'Original', 'grand-media' ); ?>"><?php echo esc_html( $item->meta['_metadata'][0]['original']['width'] . '×' . $item->meta['_metadata'][0]['original']['height'] ); ?></a>,
						<?php } else { ?>
							<span title="<?php esc_attr_e( 'Original', 'grand-media' ); ?>"><?php echo esc_html( $item->meta['_metadata'][0]['original']['width'] . '×' . $item->meta['_metadata'][0]['original']['height'] ); ?></span>,
						<?php } ?>
						<a href="<?php echo esc_url( add_query_arg( 't', time(), $item->url ) ); ?>"
							data-bs-toggle="modal" data-bs-target="#previewModal"
							data-width="<?php echo esc_attr( $item->meta['_metadata'][0]['web']['width'] ); ?>"
							data-height="<?php echo esc_attr( $item->meta['_metadata'][0]['web']['height'] ); ?>"
							class="preview-modal"
							title="<?php esc_attr_e( 'Webimage', 'grand-media' ); ?>"><?php echo esc_html( $item->meta['_metadata'][0]['web']['width'] . '×' . $item->meta['_metadata'][0]['web']['height'] ); ?></a>,
						<a href="<?php echo esc_url( add_query_arg( 't', time(), $item->url_thumb ) ); ?>"
							data-bs-toggle="modal" data-bs-target="#previewModal"
							data-width="<?php echo esc_attr( $item->meta['_metadata'][0]['thumb']['width'] ); ?>"
							data-height="<?php echo esc_attr( $item->meta['_metadata'][0]['thumb']['height'] ); ?>"
							class="preview-modal"
							title="<?php esc_attr_e( 'Thumbnail', 'grand-media' ); ?>"><?php echo esc_html( $item->meta['_metadata'][0]['thumb']['width'] . '×' . $item->meta['_metadata'][0]['thumb']['height'] ); ?></a>
					<?php } ?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Filename', 'grand-media' ); ?>:</span> <a href="<?php echo esc_url( $item->url ); ?>" download="<?php echo esc_attr( $item->gmuid ); ?>"><?php echo esc_html( $item->gmuid ); ?></a>
					<?php if ( ! empty( $item->meta['_created_timestamp'][0] ) ) { ?>
						<br/><span class="badge label-default"><?php esc_html_e( 'Created', 'grand-media' ); ?>:</span> <?php echo esc_html( gmdate( 'Y-m-d H:i:s ', $item->meta['_created_timestamp'][0] ) ); ?>
					<?php } ?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Uploaded', 'grand-media' ); ?>:</span> <?php echo esc_html( $item->date ); ?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Last Edited', 'grand-media' ); ?>:</span> <span class="gm-last-edited modified"><?php echo esc_html( $item->modified ); ?></span>
				</p>
			</div>
		</div>
	</div>
</div>
