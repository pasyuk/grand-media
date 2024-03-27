<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Panel heading for term
 *
 * @var $term_id
 * @var $term
 * @var $gmedia_url
 * @var $gmedia_term_taxonomy
 * @var $gmProcessor
 * @var $gmCore
 */
$curpage = $gmCore->_get( 'page', 'GrandMedia' );
$refurl  = strpos( wp_get_referer(), "page={$curpage}" ) ? wp_get_referer() : $gmProcessor->url;
$referer = remove_query_arg( array( 'edit_term', 'gallery_module' ), $refurl );
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
					<li><a class="dropdown-item" href="<?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia', 'gallery' => $term->term_id ), $gmProcessor->url ) ); ?>"><?php esc_html_e( 'Show in Gmedia Library', 'grand-media' ); ?></a></li>
					<?php
					echo '<li' . ( ( 'draft' !== $term->status ) ? '' : ' class="disabled"' ) . '><a target="_blank" class="dropdown-item share-modal" data-bs-toggle="modal" data-bs-target="#shareModal" data-share="' . esc_attr( $term->term_id ) . '" data-gmediacloud="' . esc_url( $term->cloud_link ) . '" href="' . esc_url( $term->post_link ) . '">' . esc_html__( 'Share', 'grand-media' ) . '</a></li>';

					echo '<li' . ( $term->allow_delete ? '' : ' class="disabled"' ) . '>
					<a
					 class="dropdown-item"
					 href="' . esc_url( wp_nonce_url( gm_get_admin_url( array( 'do_gmedia_terms' => 'delete', 'ids' => $term->term_id ), array( 'edit_term' ), $gmProcessor->url ), 'gmedia_delete', '_wpnonce_delete' ) ) . '"
					 data-confirm="' . esc_html__( "You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ) . '">' . esc_html__( 'Delete', 'grand-media' ) . '</a></li>';
					?>
				</ul>
			</div>
		<?php } ?>
		<?php if ( $term_id ) { ?>
			<div class="term-shortcode float-start"><input type="text" title="<?php esc_attr_e( 'Shortcode' ); ?>" class="form-control h-100 float-start" value="<?php echo esc_attr( '[gmedia id=' . absint( $term_id ) . ']' ); ?>" readonly/>
				<div class="input-buffer"></div>
			</div>
			<?php
		}
		do_action( 'gmedia_gallery_btn_toolbar' );
		?>
	</div>

	<div class="btn-group float-end" id="save_buttons_duplicate">
		<?php if ( $term->module['name'] !== $term->meta['_module'] ) { ?>
			<a href="<?php echo esc_url( $gmedia_url ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Cancel preview module', 'grand-media' ); ?></a>
			<button type="button" onclick="jQuery('button[name=gmedia_gallery_save]').trigger('click');" class="btn btn-primary"><?php esc_html_e( 'Save with new module', 'grand-media' ); ?></button>
		<?php } else { ?>
			<?php if ( ! empty( $reset_settings ) ) { ?>
				<button type="button" onclick="jQuery('button[name=gmedia_gallery_reset]').trigger('click');" class="btn btn-secondary"><?php esc_html_e( 'Reset to default', 'grand-media' ); ?></button>
			<?php } ?>
			<button type="button" onclick="jQuery('button[name=gmedia_gallery_save]').trigger('click');" class="btn btn-primary"><?php esc_html_e( 'Save', 'grand-media' ); ?></button>
		<?php } ?>
	</div>

	<div class="spinner"></div>
</div>
