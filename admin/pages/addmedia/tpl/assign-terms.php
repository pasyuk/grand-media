<?php
/**
 * @var $user_ID
 * @var $gmGallery
 * @var $gmDB
 * @var $gm_allowed_tags
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( gm_user_can( 'terms' ) ) {
	global $gmCore;
	?>
	<div class="form-group">
		<?php
		$term_type = 'gmedia_album';
		$global    = gm_user_can( 'edit_others_media' ) ? '' : array( 0, $user_ID );
		$gm_terms  = $gmDB->get_terms( $term_type, array( 'global' => $global, 'orderby' => 'global_desc_name' ) );

		$terms_album = '';
		if ( count( $gm_terms ) ) {
			$selected = (int) $gmCore->_get( 'album' );
			foreach ( $gm_terms as $_term ) {
				$author_name = '';
				if ( $_term->global ) {
					if ( gm_user_can( 'edit_others_media' ) ) {
						// translators: author name.
						$author_name .= ' &nbsp; ' . sprintf( esc_html__( 'by %s', 'grand-media' ), esc_html( get_the_author_meta( 'display_name', $_term->global ) ) );
					}
				} else {
					$author_name .= ' &nbsp; (' . __( 'shared', 'grand-media' ) . ')';
				}
				if ( 'publish' !== $_term->status ) {
					$author_name .= ' [' . $_term->status . ']';
				}
				$terms_album .= '<option value="' . intval( $_term->term_id ) . '" data-name="' . esc_attr( $_term->name ) . '" data-meta="' . esc_attr( $author_name ) . '" ' . selected( $selected, $_term->term_id, false ) . '>' . esc_html( $_term->name . $author_name ) . '</option>' . "\n";
			}
		}
		?>
		<label><?php esc_html_e( 'Add to Album', 'grand-media' ); ?> </label>
		<select id="combobox_gmedia_album" name="terms[gmedia_album]" data-create="<?php echo esc_attr( gm_user_can( 'album_manage' ) ? 'true' : 'false' ); ?>" class="form-control input-sm" placeholder="<?php esc_attr_e( 'Album Name...', 'grand-media' ); ?>">
			<option value=""></option>
			<?php echo wp_kses( $terms_album, $gm_allowed_tags ); ?>
		</select>
	</div>

	<div class="form-group">
		<?php
		$term_type         = 'gmedia_category';
		$gm_category_terms = $gmDB->get_terms( $term_type, array( 'fields' => 'id=>names' ) );
		$selected          = (int) $gmCore->_get( 'category' );
		$selected          = isset( $gm_category_terms[ $selected ] ) ? $gm_category_terms[ $selected ] : '';
		?>
		<label><?php esc_html_e( 'Assign Categories', 'grand-media' ); ?></label>
		<input id="combobox_gmedia_category" name="terms[gmedia_category]" data-create="<?php echo esc_attr( gm_user_can( 'category_manage' ) ? 'true' : 'false' ); ?>" class="form-control input-sm" value="<?php echo esc_attr( $selected ); ?>" placeholder="<?php esc_attr_e( 'Uncategorized', 'grand-media' ); ?>"/>
	</div>

	<div class="form-group">
		<?php
		$term_type    = 'gmedia_tag';
		$gm_tag_terms = $gmDB->get_terms( $term_type, array( 'fields' => 'id=>names' ) );
		$selected     = (int) $gmCore->_get( 'tag' );
		$selected     = isset( $gm_tag_terms[ $selected ] ) ? $gm_tag_terms[ $selected ] : '';
		?>
		<label><?php esc_html_e( 'Add Tags', 'grand-media' ); ?> </label>
		<input id="combobox_gmedia_tag" name="terms[gmedia_tag]" data-create="<?php echo esc_attr( gm_user_can( 'tag_manage' ) ? 'true' : 'false' ); ?>" class="form-control input-sm" value="<?php echo esc_attr( $selected ); ?>" placeholder="<?php esc_attr_e( 'Add Tags...', 'grand-media' ); ?>"/>
	</div>
	<script type="text/javascript">
			var gmedia_categories = <?php echo wp_json_encode( array_values( $gm_category_terms ) ); ?>;
			var gmedia_tags = <?php echo wp_json_encode( array_values( $gm_tag_terms ) ); ?>;
	</script>
<?php } else { ?>
	<p><?php esc_html_e( 'You are not allowed to assign terms', 'grand-media' ); ?></p>
<?php } ?>
