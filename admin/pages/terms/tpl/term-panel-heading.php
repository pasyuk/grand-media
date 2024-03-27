<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Panel heading for term
 *
 * @var $term
 * @var $term_id
 * @var $gmedia_term_taxonomy
 * @var $gmedia_terms_pager
 * @var $gmProcessor
 * @var $gmCore
 */
$taxterm = $gmProcessor->taxterm;
$curpage = $gmCore->_get( 'page', 'GrandMedia' );
$refurl  = strpos( wp_get_referer(), "page={$curpage}" ) ? wp_get_referer() : $gmProcessor->url;
$referer = remove_query_arg( array( 'edit_term' ), $refurl );
?>
<div class="card-header-fake"></div>
<div class="card-header bg-light clearfix">
	<div class="btn-toolbar gap-4 float-start">
		<a class="btn btn-secondary float-start" href="<?php echo esc_url( $referer ); ?>"><?php esc_html_e( 'Go Back', 'grand-media' ); ?></a>

		<?php if ( $term_id ) { ?>
			<div class="btn-group">
				<a class="btn btn-secondary" href="#"><?php esc_html_e( 'Action', 'grand-media' ); ?></a>
				<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
					<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
				</button>
				<ul class="dropdown-menu" role="menu">
					<?php $taxkey = $taxterm . '__in'; ?>
					<li><a class="dropdown-item" href="<?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia', $taxkey => $term->term_id ), $gmProcessor->url ) ); ?>"><?php esc_html_e( 'Show in Gmedia Library', 'grand-media' ); ?></a></li>
					<?php
					echo '<li' . ( ( 'draft' !== $term->status ) ? '' : ' class="disabled"' ) . '><a target="_blank" class="dropdown-item share-modal" data-bs-toggle="modal" data-bs-target="#shareModal" data-share="' . esc_attr( $term->term_id ) . '" data-gmediacloud="' . esc_url( $term->cloud_link ) . '" href="' . esc_url( $term->post_link ) . '">' . esc_html__( 'Share', 'grand-media' ) . '</a></li>';

					echo '<li' . ( $term->allow_delete ? '' : ' class="disabled"' ) . '><a class="dropdown-item" href="' . esc_url(
							wp_nonce_url(
								gm_get_admin_url(
									array(
										'do_gmedia_terms' => 'delete',
										'ids'             => $term->term_id,
									),
									array( 'edit_term' ),
									$gmProcessor->url
								),
								'gmedia_delete',
								'_wpnonce_delete'
							)
						) . '" data-confirm="' . esc_html__( "You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ) . '">' . esc_html__( 'Delete', 'grand-media' ) . '</a></li>';
					?>
				</ul>
			</div>

			<div class="btn-group">
				<?php
				$add_args = array(
					'page'        => 'GrandMedia',
					'mode'        => 'select_multiple',
					'gmediablank' => 'library',
				);
				$taxterm  = $term->taxterm;
				if ( 'album' === $taxterm ) {
					$add_args['album__in'] = 0;
				} elseif ( 'category' === $taxterm ) {
					$add_args['category__not_in'] = $gmProcessor->edit_term;
				}
				echo '<a href="' . esc_url( $gmCore->get_admin_url( $add_args, array(), true ) ) . '" class="btn btn-success preview-modal float-start" data-bs-toggle="modal" data-bs-target="#previewModal" data-width="1200" data-height="500" data-cls="select_gmedia assign_gmedia_term" data-title="' . esc_attr__( 'Add from Library', 'grand-media' ) . '"><span class="fa-solid fa-plus"></span> ' . esc_html__( 'Add from Library', 'grand-media' ) . '</a>';

				if ( gm_user_can( 'upload' ) && ! $gmProcessor->gmediablank ) {
					$args = array( 'page' => 'GrandMedia_AddMedia' );
					if ( $gmProcessor->edit_term ) {
						$args[ $taxterm ] = $gmProcessor->edit_term;
					}
					?>
					<a href="<?php echo esc_url( gm_get_admin_url( $args, array(), true ) ); ?>" class="btn btn-success float-start">
						<span class="fa-solid fa-upload" style="font-size: 130%;line-height: 0;vertical-align: sub;"></span> <?php esc_html_e( 'Upload', 'grand-media' ); ?>
					</a>
					<?php
				}
				?>
			</div>

			<div class="term-shortcode float-start">
				<input type="text" title="<?php esc_attr_e( 'Shortcode' ); ?>" class="form-control float-start h-100" value="<?php echo esc_attr( "[gm {$taxterm}={$term_id}]" ); ?>" readonly/>
				<div class="input-buffer"></div>
			</div>
			<?php
		}
		do_action( 'gmedia_term_btn_toolbar' );
		?>
	</div>

	<div class="spinner"></div>
</div>
