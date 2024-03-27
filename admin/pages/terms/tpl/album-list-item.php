<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Album list item
 *
 * @var $item
 * @var $gmedia_url
 */
?>
<div class="cb_list-item list-group-item term-list-item <?php echo esc_attr( implode( ' ', $item->classes ) ); ?>">
	<div class="row cb_object">
		<div class="col-sm-6 term-label">
			<div class="checkbox position-relative">
				<input name="doaction[]" type="checkbox"<?php echo $item->selected ? ' checked="checked"' : ''; ?> value="<?php echo absint( $item->term_id ); ?>"/>
				<?php if ( $item->allow_edit ) { ?>
					<a class="term_name" href="<?php echo esc_url( add_query_arg( array( 'edit_term' => $item->term_id ), $gmedia_url ) ); ?>"><?php echo esc_html( $item->name ); ?></a>
				<?php } else { ?>
					<span class="term_name"><?php echo esc_html( $item->name ); ?></span>
				<?php } ?>
				<br/><span class="term_info_author">
				<?php
				if ( $item->global ) {
					// translators: author name.
					echo $item->author_name ? sprintf( esc_html__( 'by %s', 'grand-media' ), esc_html( $item->author_name ) ) : '(' . esc_html__( 'deleted author', 'grand-media' ) . ')';
				} else {
					echo '(' . esc_html__( 'no author', 'grand-media' ) . ')';
				}
				?>
					</span>
				<br/><span class="term_id">ID: <?php echo absint( $item->term_id ); ?></span>
				<?php if ( ! empty( $item->post_date ) ) { ?>
					<date class="term_date"><?php echo esc_html( $item->post_date ); ?></date>
				<?php } ?>

				<div class="object-actions">
					<?php
					$action_links = gmedia_term_item_actions( $item );
					echo wp_kses_post( $action_links['share'] );
					echo '<br/>' . wp_kses_post( $action_links['filter'] . $action_links['delete'] );
					?>
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<?php gmedia_term_item_thumbnails( $item ); ?>
		</div>
	</div>
</div>
