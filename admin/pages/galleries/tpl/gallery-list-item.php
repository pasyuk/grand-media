<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Gallery list item
 *
 * @var $item
 * @var $gmedia_url
 */
?>
<div class="cb_list-item list-group-item gallery-list-item <?php echo esc_attr( implode( ' ', $item->classes ) ); ?>" id="list-item-<?php echo absint( $item->term_id ); ?>" data-id="<?php echo absint( $item->term_id ); ?>" data-type="<?php echo esc_attr( $item->module['name'] ); ?>">
	<div class="row cb_object">
		<div class="col-sm-7">
			<label class="cb_media-object cb_media-object-gallery">
				<input name="doaction[]" type="checkbox"<?php echo $item->selected ? ' checked="checked"' : ''; ?> data-type="<?php echo esc_attr( $item->module['name'] ); ?>" value="<?php echo absint( $item->term_id ); ?>"/>
			</label>

			<div class="media-info-body" style="margin-left:35px;">
				<p class="media-title">
					<?php if ( $item->allow_edit ) { ?>
						<a class="term_name" href="<?php echo esc_url( add_query_arg( array( 'edit_term' => $item->term_id ), $gmedia_url ) ); ?>"><?php echo esc_html( $item->name ); ?></a>
					<?php } else { ?>
						<span class="term_name"><?php echo esc_html( $item->name ); ?></span>
					<?php } ?>
				</p>

				<p class="media-meta">
					<span class="badge label-default"><?php esc_html_e( 'Author', 'grand-media' ); ?>:</span> <?php echo $item->global ? esc_html( $item->author_name ) : '&#8212;'; ?>
				</p>

				<p class="media-caption"><?php echo esc_html( nl2br( $item->description ) ); ?></p>

				<p class="media-meta" title="<?php esc_attr_e( 'Shortcode', 'grand-media' ); ?>" style="font-weight:bold">
					<span class="badge label-default"><?php esc_html_e( 'Shortcode', 'grand-media' ); ?>:</span> [gmedia id=<?php echo absint( $item->term_id ); ?>]
				</p>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="object-actions gallery-object-actions">
				<?php
				$action_links = gmedia_gallery_actions( $item );
				echo wp_kses_post( implode( '', $action_links ) );
				?>
			</div>
			<p class="media-meta">
				<span class="badge label-default"><?php esc_html_e( 'Module', 'grand-media' ); ?>:</span> <?php echo esc_html( $item->module['name'] ); ?>
				<?php if ( empty( $item->module['info'] ) ) { ?>
					<span class="bg-danger text-center"><?php esc_html_e( 'Module broken. Reinstall module', 'grand-media' ); ?></span>
				<?php } ?>
				<br><span class="badge label-default"><?php esc_html_e( 'Last Edited', 'grand-media' ); ?>:</span> <?php echo esc_html( $item->meta['_edited'] ); ?>
				<br><span class="badge label-default"><?php esc_html_e( 'Query Args.', 'grand-media' ); ?>:</span> <?php echo ! empty( $item->meta['_query'] ) ? esc_html( str_replace( ',"', ', "', wp_json_encode( $item->meta['_query'] ) ) ) : ''; ?>
				<?php
				/*
				$gallery_tabs = reset($term_meta['_query']);
				$tax_tabs     = key($term_meta['_query']);
				if ('gmedia__in' == $tax_tabs) {
					esc_html_e('Selected Gmedia', 'grand-media');
					$gmedia_ids = wp_parse_id_list($gallery_tabs[0]);
					// translators: number.
					$gal_source = sprintf('<a class="gm_gallery_source selected__in" href="%s">' . esc_html__('Show %d items in Gmedia Library', 'grand-media') . '</a>', esc_url(add_query_arg(array('gmedia__in' => implode(',', $gmedia_ids)), $lib_url)), count($gmedia_ids));
					echo wp_kses_post(" ($gal_source)");
				} else {
					$tabs         = $gmDB->get_terms($tax_tabs, array('include' => $gallery_tabs));
					$terms_source = array();
					if ('gmedia_category' == $tax_tabs) {
						esc_html_e('Categories', 'grand-media');
						foreach ($tabs as $t) {
							$terms_source[] = sprintf('<a class="gm_gallery_source gm_category" href="%s">%s</a>', esc_url(add_query_arg(array('cat' => $t->term_id), $lib_url)), esc_html($t->name));
						}
					} elseif ('gmedia_album' == $tax_tabs) {
						esc_html_e('Albums', 'grand-media');
						foreach ($tabs as $t) {
							$terms_source[] = sprintf('<a class="gm_gallery_source gm_album" href="%s">%s</a>', esc_url(add_query_arg(array('alb' => $t->term_id), $lib_url)), esc_html($t->name));
						}
					} elseif ('gmedia_tag' == $tax_tabs) {
						esc_html_e('Tags', 'grand-media');
						foreach ($tabs as $t) {
							$terms_source[] = sprintf('<a class="gm_gallery_source gm_tag" href="%s">%s</a>', esc_url(add_query_arg(array('tag_id' => $t->term_id), $lib_url)), esc_html($t->name));
						}
					}
					if (! empty($terms_source)) {
						echo ' (' . wp_kses_post( join(', ', $terms_source) ) . ')';
					}
				}
				*/
				?>
			</p>
		</div>
	</div>
</div>
