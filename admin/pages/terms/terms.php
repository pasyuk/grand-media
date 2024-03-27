<?php
/**
 * Gmedia Terms
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$gmedia_url           = $gmProcessor->url;
$gmedia_user_options  = $gmProcessor->user_options;
$gmedia_term_taxonomy = $gmProcessor->taxonomy;

$gmedia_terms       = $gmDB->get_terms( $gmedia_term_taxonomy, $gmProcessor->query_args );
$gmedia_terms_count = $gmDB->count_gmedia();
$gmedia_terms_pager = $gmDB->query_pager();

?>
	<div class="card m-0 mw-100 p-0 panel-fixed-header" id="gmedia-panel">

		<?php
		require dirname( __FILE__ ) . '/tpl/terms-panel-heading.php';

		do_action( 'gmedia_before_terms_list' );
		?>

		<form class="list-group <?php echo esc_attr( $gmedia_term_taxonomy ); ?>" id="gm-list-table" style="margin-bottom:4px; border-top-left-radius: 0; border-top-right-radius: 0;">
			<?php
			wp_original_referer_field( true, 'previous' );
			wp_nonce_field( 'gmedia_terms', '_wpnonce_terms' );
			$taxterm = $gmProcessor->taxterm;
			if ( count( $gmedia_terms ) ) {
				foreach ( $gmedia_terms as &$item ) {
					gmedia_term_item_more_data( $item );

					$item->classes = array();
					if ( 'publish' !== $item->status ) {
						if ( 'private' === $item->status ) {
							$item->classes[] = 'list-group-item-info';
						} elseif ( 'draft' === $item->status ) {
							$item->classes[] = 'list-group-item-warning';
						}
					}
					$item->classes[] = $item->global ? ( ( $item->global === $user_ID ) ? 'current_user' : 'other_user' ) : 'shared';
					$item->selected  = in_array( $item->term_id, (array) $gmProcessor->selected_items, true );
					if ( $item->selected ) {
						$item->classes[] = 'gm-selected';
					}

					include dirname( __FILE__ ) . "/tpl/{$taxterm}-list-item.php";

				}
			} else {
				include dirname( __FILE__ ) . '/tpl/no-items.php';
			}
			?>
		</form>
		<?php
		do_action( 'gmedia_after_terms_list' );
		?>
	</div>

<?php

require GMEDIA_ABSPATH . 'admin/tpl/modal-share.php';
