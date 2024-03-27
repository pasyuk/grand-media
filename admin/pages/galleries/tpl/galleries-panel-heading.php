<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Panel heading for terms
 *
 * @var $gmedia_term_taxonomy
 * @var $gmedia_terms_pager
 * @var $gmProcessor
 * @var $user_ID
 * @var $gmedia_url
 * @var $gmCore
 * @var $gm_allowed_tags
 */
?>
<div class="card-header-fake"></div>
<div class="card-header bg-light clearfix" style="padding-bottom:2px;">
	<div class="float-end" style="margin-bottom:3px;">
		<div class="clearfix">
			<?php require GMEDIA_ABSPATH . 'admin/tpl/search-form.php'; ?>

			<div class="btn-toolbar gap-4 float-end" style="margin-bottom:4px; margin-left:4px;">
				<a title="<?php esc_attr_e( 'More Screen Settings', 'grand-media' ); ?>" class="show-settings-link float-end btn btn-secondary btn-xs"><i class="fa-solid fa-gear"></i></a>
			</div>
		</div>

		<?php echo wp_kses( $gmedia_terms_pager, $gm_allowed_tags ); ?>

		<div class="spinner"></div>
	</div>

	<div class="btn-toolbar gap-4 float-start" style="margin-bottom:7px;">
		<div class="btn-group gm-checkgroup" id="cb_global-btn">
			<span class="btn btn-secondary active"><input class="doaction" id="cb_global" data-group="cb_object" type="checkbox"/></span>
			<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
				<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a class="dropdown-item" data-select="total" href="#"><?php esc_html_e( 'All', 'grand-media' ); ?></a></li>
				<li><a class="dropdown-item" data-select="none" href="#"><?php esc_html_e( 'None', 'grand-media' ); ?></a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" data-select="reverse" href="#" title="<?php esc_attr_e( 'Reverse only visible items', 'grand-media' ); ?>"><?php esc_html_e( 'Reverse', 'grand-media' ); ?></a></li>
			</ul>
		</div>

		<div class="btn-group">
			<a class="btn btn-primary" href="#chooseModuleModal" data-bs-toggle="modal"><?php esc_html_e( 'Create Gallery', 'grand-media' ); ?></a>
		</div>

		<?php if ( ! empty( $gmedia_terms ) ) { ?>
			<div class="btn-group">
				<a class="btn btn-secondary" href="#"><?php esc_html_e( 'Action', 'grand-media' ); ?></a>
				<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
					<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
				</button>
				<?php
				$rel_selected_show = 'rel-selected-show';
				$rel_selected_hide = 'rel-selected-hide';
				?>
				<ul class="dropdown-menu" role="menu">
					<li class="dropdown-header <?php echo esc_attr( $rel_selected_hide ); ?>"><span><?php esc_html_e( 'Select items to see more actions', 'grand-media' ); ?></span></li>
					<li class="<?php echo esc_attr( $rel_selected_show ); ?>">
						<a class="dropdown-item" href="#changeModuleModal" data-bs-toggle="modal"><?php esc_html_e( 'Change Module/Preset for Galleries', 'grand-media' ); ?></a>
					</li>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'terms_delete' ) ? '' : ' disabled' ) ); ?>">
						<a
							href="<?php echo esc_url( wp_nonce_url( $gmCore->get_admin_url( array( 'do_gmedia_terms' => 'delete', 'ids' => 'selected' ), array( 'filter' ) ), 'gmedia_delete', '_wpnonce_delete' ) ); ?>"
							class="dropdown-item gmedia-delete text-danger"
							data-confirm="<?php esc_attr_e( "You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ); ?>"
						>
							<?php esc_html_e( 'Delete Selected Items', 'grand-media' ); ?>
						</a>
					</li>
					<?php do_action( 'gmedia_galleries_action_list' ); ?>
				</ul>
			</div>

			<?php
			do_action( 'gmedia_galleries_btn_toolbar' );

			$filter_selected     = $gmCore->_req( 'filter' );
			$filter_selected_arg = $filter_selected ? false : 'selected';
			?>
			<form class="btn-group" id="gm-selected-btn" name="gm-selected-form" action="<?php echo esc_url( add_query_arg( array( 'filter' => $filter_selected_arg ), $gmedia_url ) ); ?>" method="post">
				<button type="submit" class="btn btn<?php echo ( 'selected' === $filter_selected ) ? '-success' : '-info'; ?>">
					<?php
					// translators: number.
					echo wp_kses_post( sprintf( __( '%s selected', 'grand-media' ), '<span id="gm-selected-qty">' . count( $gmProcessor->selected_items ) . '</span>' ) );
					?>
				</button>
				<button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
					<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span></button>
				<input type="hidden" id="gm-selected" data-userid="<?php echo absint( $user_ID ); ?>" data-key="<?php echo esc_attr( GmediaProcessor_Galleries::$cookie_key ); ?>" name="selected_items" value="<?php echo esc_attr( implode( ',', $gmProcessor->selected_items ) ); ?>"/>
				<ul class="dropdown-menu" role="menu">
					<li><a class="dropdown-item" id="gm-selected-show" href="#show">
					<?php
					if ( ! $filter_selected ) {
						esc_html_e( 'Show only selected items', 'grand-media' );
					} else {
						esc_html_e( 'Show all gmedia items', 'grand-media' );
					}
					?>
							</a></li>
					<li><a class="dropdown-item" id="gm-selected-clear" href="#clear"><?php esc_html_e( 'Clear selected items', 'grand-media' ); ?></a></li>
				</ul>
			</form>
		<?php } ?>

	</div>
</div>
