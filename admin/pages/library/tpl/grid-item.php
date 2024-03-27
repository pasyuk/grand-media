<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Gmedia Item for Grid View in Library
 *
 * @var $gmProcessor
 * @var $gmCore
 * @var $gmDB
 * @var $item
 * @var $gmedia_url
 */
?>
<div class="cb_list-item gm-item-cell col-xs-6 col-sm-4 col-md-3 col-lg-2 <?php echo esc_attr( implode( ' ', $item->classes ) ); ?>" id="list-item-<?php echo absint( $item->ID ); ?>" data-id="<?php echo absint( $item->ID ); ?>" data-type="<?php echo esc_attr( $item->type ); ?>">
	<div class="img-thumbnail <?php echo ( $item->img_ratio >= 1 ) ? 'landscape' : 'portrait'; ?>">
		<div class="cb_media-object">
			<span<?php echo in_array( $gmProcessor->mode, array( 'select_single', 'select_multiple' ), true ) ? '' : ' data-clicktarget="gmcheckbox' . intval( $item->ID ) . '"'; ?> class="centered">
				<?php echo wp_kses_post( gmedia_item_thumbnail( $item ) ); ?>
			</span>
		</div>
		<label class="gm-item-check" id="gmcheckbox<?php echo absint( $item->ID ); ?>">
			<?php if ( 'select_single' !== $gmProcessor->mode ) { ?>
				<input name="doaction[]" type="checkbox"<?php echo $item->selected ? ' checked="checked"' : ''; ?> data-type="<?php echo esc_attr( $item->type ); ?>" value="<?php echo absint( $item->ID ); ?>"/>
			<?php } else { ?>
				<input name="doaction[]" type="radio" data-type="<?php echo esc_attr( $item->type ); ?>" value="<?php echo absint( $item->ID ); ?>"/>
			<?php } ?>
		</label>
		<label class="gm-stack hidden"><input name="stack[]" type="checkbox"<?php echo $item->in_stack ? ' checked="checked"' : ''; ?> data-type="<?php echo esc_attr( $item->type ); ?>" value="<?php echo absint( $item->ID ); ?>"/></label>
		<div class="gm-cell-more">
			<span class="gm-cell-more-btn fa-solid fa-bars"></span>
			<div class="gm-cell-more-content">
				<p class="media-meta">
					<strong style="word-break:break-word;"><?php echo esc_html( $item->title ); ?></strong>
					<br/><span class="badge label-default"><?php esc_html_e( 'ID', 'grand-media' ); ?>:</span> #<?php echo absint( $item->ID ); ?>
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

					<?php if ( isset( $item->post_id ) ) { ?>
						<br/><span class="badge label-default"><?php esc_html_e( 'Comments', 'grand-media' ); ?>:</span>
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
					<?php } ?>
					<br/><span class="badge label-default"><?php esc_html_e( 'Views / Likes', 'grand-media' ); ?>:</span>
					<?php echo intval( isset( $item->meta['views'][0] ) ? $item->meta['views'][0] : 0 ) . ' / ' . intval( isset( $item->meta['likes'][0] ) ? $item->meta['likes'][0] : 0 ); ?>

					<?php
					if ( isset( $item->meta['_rating'][0] ) ) {
						$ratings = maybe_unserialize( $item->meta['_rating'][0] );
						?>
						<br/><span class="badge label-default"><?php esc_html_e( 'Rating', 'grand-media' ); ?>:</span> <?php echo esc_html( round( $ratings['value'], 2 ) . ' / ' . $ratings['votes'] ); ?>
					<?php } ?>
				</p>
			</div>
		</div>
	</div>
	<div class="gm-cell-bottom">
		<div class="gm-cell-title"><span><?php echo esc_html( $item->title ); ?>&nbsp;</span></div>
		<div class="gmedia-actions">
			<?php
			$media_action_links = gmedia_item_actions( $item );
			echo wp_kses_post( implode( ' ', $media_action_links ) );
			?>
		</div>
	</div>
</div>


