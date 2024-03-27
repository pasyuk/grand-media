<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Tag list item
 *
 * @var $item
 */
?>
<div class="cb_list-item list-group-item term-list-item <?php echo esc_attr( implode( ' ', $item->classes ) ); ?>">
	<div class="row cb_object" id="tag_<?php echo absint( $item->term_id ); ?>">
		<div class="col-sm-6 term-label">
			<div class="checkbox position-relative">
				<input name="doaction[]" type="checkbox"<?php echo $item->selected ? ' checked="checked"' : ''; ?> value="<?php echo absint( $item->term_id ); ?>"/>
				<?php if ( $item->allow_edit ) { ?>
					<a class="edit_tag_link" href="#tag_<?php echo absint( $item->term_id ); ?>"><?php echo esc_html( $item->name ); ?></a>
					<span class="edit_tag_form" style="display:none;"><input class="edit_tag_input" type="text" data-tag_id="<?php echo absint( $item->term_id ); ?>" name="gmedia_tag_name[<?php echo absint( $item->term_id ); ?>]" value="<?php echo esc_attr( $item->name ); ?>" placeholder="<?php echo esc_attr( $item->name ); ?>"/><a href="#tag_<?php echo absint( $item->term_id ); ?>" class="edit_tag_save btn btn-link fa-solid fa-pencil"></a></span>
				<?php } else { ?>
					<span><?php echo esc_html( $item->name ); ?></span>
				<?php } ?>
				<br/><span class="term_id">ID: <?php echo absint( $item->term_id ); ?></span>

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
