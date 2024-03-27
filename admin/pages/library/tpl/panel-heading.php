<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * @var $gmCore
 * @var $gmProcessor
 * @var $user_ID
 * @var $gmedia_user_options
 * @var $gmedia_pager
 * @var $gmedia_url
 * @var $gmedia_count
 * @var $gm_allowed_tags
 */
?>
<div class="card-header-fake"></div>
<div class="card-header bg-light clearfix" style="padding-bottom:2px;">
	<div class="float-end" style="margin-bottom:3px;">
		<div class="clearfix">
			<?php require GMEDIA_ABSPATH . 'admin/tpl/search-form.php'; ?>

			<div class="btn-toolbar gap-2 float-end" style="margin-bottom:4px; margin-left:4px;">
				<?php
				if ( 'edit' !== $gmProcessor->mode ) {
					$view                = $gmProcessor->gmediablank ? '_frame' : '';
					$display_mode_gmedia = $gmProcessor->display_mode;
					if ( 'grid' === $display_mode_gmedia ) {
						?>
						<a title="<?php esc_attr_e( 'Thumbnails Fit/Fill Cell', 'grand-media' ); ?>" href="<?php echo esc_url( gm_get_admin_url( array( 'grid_cell_fit' => 'toggle' ) ) ); ?>" class="fit-thumbs btn btn<?php echo ( 'true' === $gmedia_user_options["grid_cell_fit_gmedia{$view}"] ) ? '-success active' : '-secondary'; ?> btn-xs"><i class='fa-solid fa-eye'></i></a>
						<?php
					}
					if ( ! $gmProcessor->edit_term && ! in_array( $gmProcessor->mode, array( 'select_single', 'select_mutiple' ), true ) ) {
						?>
						<div class="btn-group">
							<a title="<?php esc_attr_e( 'Show as Grid', 'grand-media' ); ?>" href="<?php echo esc_url( gm_get_admin_url( array( 'display_mode' => 'grid' ) ) ); ?>" class="btn btn<?php echo ( 'grid' === $display_mode_gmedia ) ? '-primary active' : '-secondary'; ?> btn-xs"><i class='fa-solid fa-table-cells'></i></a>
							<a title="<?php esc_attr_e( 'Show as List', 'grand-media' ); ?>" href="<?php echo esc_url( gm_get_admin_url( array( 'display_mode' => 'list' ) ) ); ?>" class="btn btn<?php echo ( 'list' === $display_mode_gmedia ) ? '-primary active' : '-secondary'; ?> btn-xs"><i class='fa-solid fa-table-list'></i></a>
						</div>
						<?php
					}
				}
				?>

				<?php if ( ! $gmProcessor->gmediablank ) { ?>
					<a title="<?php esc_attr_e( 'More Screen Settings', 'grand-media' ); ?>" class="show-settings-link btn btn-secondary btn-xs"><i class="fa-solid fa-gear"></i></a>
				<?php } ?>
			</div>
		</div>

		<?php echo wp_kses( $gmedia_pager, $gm_allowed_tags ); ?>

		<div class="spinner"></div>

	</div>
	<div class="btn-toolbar gap-2 float-start" style="margin-bottom:7px;">
		<?php if ( 'select_single' !== $gmProcessor->mode ) { ?>
			<div class="btn-group gm-checkgroup" id="cb_global-btn">
				<span class="btn btn-secondary active"><input class="doaction" id="cb_global" data-group="gm-item-check" type="checkbox"/></span>
				<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded='false'><span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span></button>
				<ul class="dropdown-menu" role="menu">
					<li><a class="dropdown-item" data-select="total" href="#"><?php esc_html_e( 'All', 'grand-media' ); ?></a></li>
					<li><a class="dropdown-item" data-select="none" href="#"><?php esc_html_e( 'None', 'grand-media' ); ?></a></li>
					<li>
						<hr class='dropdown-divider'>
					</li>
					<li><a class="dropdown-item" data-select="image" href="#"><?php esc_html_e( 'Images', 'grand-media' ); ?></a></li>
					<li><a class="dropdown-item" data-select="audio" href="#"><?php esc_html_e( 'Audio', 'grand-media' ); ?></a></li>
					<li><a class="dropdown-item" data-select="video" href="#"><?php esc_html_e( 'Video', 'grand-media' ); ?></a></li>
					<li>
						<hr class='dropdown-divider'>
					</li>
					<li><a class="dropdown-item" data-select="reverse" href="#" title="<?php esc_attr_e( 'Reverse only visible items', 'grand-media' ); ?>"><?php esc_html_e( 'Reverse', 'grand-media' ); ?></a></li>
				</ul>
			</div>
		<?php } ?>

		<div class="btn-group">
			<?php
			// todo: !!!!!
			$curr_mime = explode( ',', $gmCore->_get( 'mime_type', 'total' ) );
			if ( isset( $gmedia_filter['gmedia__in'] ) ) {
				if ( ( 'show' === $gmCore->_get( 'stack' ) || 'selected' === $gmCore->_get( 'filter' ) ) ) {
					if ( $gmProcessor->selected_items === $gmedia_filter['gmedia__in'] || $gmProcessor->stack_items === $gmedia_filter['gmedia__in'] ) {
						unset( $gmedia_filter['gmedia__in'] );
					}
				} elseif ( $gmProcessor->edit_term ) {
					unset( $gmedia_filter['gmedia__in'] );
				}
			}
			?>
			<?php if ( ! empty( $gmedia_filter ) ) { ?>
				<a class="btn btn-warning" title="<?php esc_attr_e( 'Reset Filter', 'grand-media' ); ?>" rel="total" href="<?php echo esc_url( gm_get_admin_url( array(), array(), $gmedia_url ) ); ?>"><?php esc_html_e( 'Reset Filter', 'grand-media' ); ?></a>
			<?php } else { ?>
				<button type="button" class="btn btn-secondary" data-bs-toggle="dropdown"><?php esc_html_e( 'Filter', 'grand-media' ); ?></button>
			<?php } ?>
			<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
				<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<?php if ( gm_user_can( 'show_others_media' ) ) { ?>
					<li role="presentation" class="dropdown-header"><?php esc_html_e( 'FILTER BY AUTHOR', 'grand-media' ); ?></li>
					<li class="gmedia_author">
						<a href="#libModal" data-bs-toggle="modal" data-modal="filter_author" data-action="gmedia_get_modal" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Choose authors', 'grand-media' ); ?></a>
					</li>
				<?php } ?>

				<?php
				$gm_qty_badge = array(
					'total'       => '',
					'image'       => '',
					'audio'       => '',
					'video'       => '',
					'text'        => '',
					'application' => '',
					'other'       => '',
				);

				foreach ( $gmedia_count as $key => $value ) {
					$gm_qty_badge[ $key ] = '<span class="badge badge-info float-end">' . (int) $value . '</span>';
				}
				?>
				<li role="presentation" class="dropdown-header"><?php esc_html_e( 'TYPE', 'grand-media' ); ?></li>
				<li class="total<?php echo in_array( 'total', $curr_mime, true ) ? ' active' : ''; ?>">
					<a class="dropdown-item" rel="total" href="<?php echo esc_url( gm_get_admin_url( array(), array( 'mime_type', 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty_badge['total'] . esc_html__( 'All', 'grand-media' ) ); ?></a>
				</li>
				<?php if ( 'select_single' !== $gmProcessor->mode ) { ?>
					<li class="image<?php echo ( in_array( 'image', $curr_mime, true ) ? ' active' : '' ) . ( $gmedia_count['image'] ? '' : ' disabled' ); ?>">
						<a class="dropdown-item" rel="image" href="<?php echo esc_url( gm_get_admin_url( array( 'mime_type' => 'image' ), array( 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty_badge['image'] . esc_html__( 'Images', 'grand-media' ) ); ?></a></li>
					<li class="audio<?php echo ( in_array( 'audio', $curr_mime, true ) ? ' active' : '' ) . ( $gmedia_count['audio'] ? '' : ' disabled' ); ?>">
						<a class="dropdown-item" rel="audio" href="<?php echo esc_url( gm_get_admin_url( array( 'mime_type' => 'audio' ), array( 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty_badge['audio'] . esc_html__( 'Audio', 'grand-media' ) ); ?></a></li>
					<li class="video<?php echo ( in_array( 'video', $curr_mime, true ) ? ' active' : '' ) . ( $gmedia_count['video'] ? '' : ' disabled' ); ?>">
						<a class="dropdown-item" rel="video" href="<?php echo esc_url( gm_get_admin_url( array( 'mime_type' => 'video' ), array( 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty_badge['video'] . esc_html__( 'Video', 'grand-media' ) ); ?></a></li>
					<li class="application<?php echo ( ( in_array( 'application', $curr_mime, true ) || in_array( 'text', $curr_mime, true ) ) ? ' active' : '' ) . ( $gmedia_count['other'] ? '' : ' disabled' ); ?>">
						<a class="dropdown-item" rel="application" href="<?php echo esc_url( gm_get_admin_url( array( 'mime_type' => 'application,text' ), array( 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty_badge['other'] . esc_html__( 'Other', 'grand-media' ) ); ?></a></li>
				<?php } ?>

				<li role="presentation" class="dropdown-header"><?php esc_html_e( 'COLLECTIONS', 'grand-media' ); ?></li>
				<li class="filter_categories<?php echo isset( $gmedia_filter['category__in'] ) ? ' active' : ''; ?>">
					<a href="#libModal" data-bs-toggle="modal" data-modal="filter_categories" data-action="gmedia_get_modal" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Categories', 'grand-media' ); ?></a></li>
				<?php if ( ! ( $gmProcessor->edit_term && 'album' === $gmProcessor->taxterm ) ) { ?>
					<li class="filter_albums<?php echo isset( $gmedia_filter['album__in'] ) ? ' active' : ''; ?>">
						<a href="#libModal" data-bs-toggle="modal" data-modal="filter_albums" data-action="gmedia_get_modal" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Albums', 'grand-media' ); ?></a></li>
				<?php } ?>
				<li class="filter_tags<?php echo isset( $gmedia_filter['tag__in'] ) ? ' active' : ''; ?>"><a href="#libModal" data-bs-toggle="modal" data-modal="filter_tags" data-action="gmedia_get_modal" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Tags', 'grand-media' ); ?></a></li>
				<?php do_action( 'gmedia_filter_list' ); ?>
			</ul>
		</div>

		<?php if ( ! in_array( $gmProcessor->mode, array( 'select_single', 'select_mutiple' ), true ) ) { ?>
			<div class="btn-group">
				<?php
				if ( 'edit' !== $gmProcessor->mode ) {
					$edit_mode_href = gm_get_admin_url( array( 'mode' => 'edit' ) );
				} else {
					$edit_mode_href = gm_get_admin_url( array(), array( 'mode' ) );
				}
				?>
				<?php if ( gm_user_can( 'edit_media' ) ) { ?>
					<a class="btn btn-secondary edit-mode-link" title="<?php esc_attr_e( 'Toggle Edit Mode', 'grand-media' ); ?>" href="<?php echo esc_url( $edit_mode_href ); ?>"><?php esc_html_e( 'Action', 'grand-media' ); ?></a>
				<?php } else { ?>
					<button type="button" class="btn btn-secondary"><?php esc_html_e( 'Action', 'grand-media' ); ?></button>
				<?php } ?>
				<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
					<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span></button>
				<?php
				$rel_selected_show = 'rel-selected-show';
				$rel_selected_hide = 'rel-selected-hide';
				?>
				<ul class="dropdown-menu" role="menu">
					<?php if ( ! ( 'edit' === $gmProcessor->mode ) ) { ?>
						<li class="<?php echo esc_attr( gm_user_can( 'edit_media' ) ? '' : 'disabled' ); ?>">
							<a class="dropdown-item edit-mode-link" href="<?php echo esc_url( $edit_mode_href ); ?>"><?php esc_html_e( 'Enter Edit Mode', 'grand-media' ); ?></a>
						</li>
					<?php } else { ?>
						<li><a class="dropdown-item" href="<?php echo esc_url( $edit_mode_href ); ?>"><?php esc_html_e( 'Exit Edit Mode', 'grand-media' ); ?></a></li>
					<?php } ?>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'edit_media' ) ? '' : ' disabled' ) ); ?>">
						<a href="#libModal" data-bs-toggle="modal" data-modal="batch_edit" data-action="gmedia_get_modal" data-ckey="<?php echo esc_attr( GmediaProcessor_Library::$cookie_key ); ?>" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Batch Edit', 'grand-media' ); ?></a></li>

					<li class="<?php echo esc_attr( $rel_selected_show ); ?>">
						<hr class="dropdown-divider">
					</li>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'terms' ) ? '' : ' disabled' ) ); ?>">
						<a href="#libModal" data-bs-toggle="modal" data-modal="assign_album" data-action="gmedia_get_modal" data-ckey="<?php echo esc_attr( GmediaProcessor_Library::$cookie_key ); ?>" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Move to Album...', 'grand-media' ); ?></a>
					</li>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'terms' ) ? '' : ' disabled' ) ); ?>">
						<a class="dropdown-item" href="<?php echo esc_url( wp_nonce_url( gm_get_admin_url( array( 'do_gmedia' => 'unassign_album' ) ), 'gmedia_action', '_wpnonce_action' ) ); ?>" data-confirm="<?php esc_attr_e( "You are about to remove the selected items from assigned albums.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ); ?>"><?php esc_html_e( 'Remove from Album', 'grand-media' ); ?></a>
					</li>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'terms' ) ? '' : ' disabled' ) ); ?>">
						<a href="#libModal" data-bs-toggle="modal" data-modal="assign_category" data-action="gmedia_get_modal" data-ckey="<?php echo esc_attr( GmediaProcessor_Library::$cookie_key ); ?>" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Assign Categories...', 'grand-media' ); ?></a>
					</li>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'terms' ) ? '' : ' disabled' ) ); ?>">
						<a href="#libModal" data-bs-toggle="modal" data-modal="unassign_category" data-action="gmedia_get_modal" data-ckey="<?php echo esc_attr( GmediaProcessor_Library::$cookie_key ); ?>" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Unassign Categories...', 'grand-media' ); ?></a>
					</li>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'terms' ) ? '' : ' disabled' ) ); ?>">
						<a href="#libModal" data-bs-toggle="modal" data-modal="add_tags" data-action="gmedia_get_modal" data-ckey="<?php echo esc_attr( GmediaProcessor_Library::$cookie_key ); ?>" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Add Tags...', 'grand-media' ); ?></a></li>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'terms' ) ? '' : ' disabled' ) ); ?>">
						<a href="#libModal" data-bs-toggle="modal" data-modal="delete_tags" data-action="gmedia_get_modal" data-ckey="<?php echo esc_attr( GmediaProcessor_Library::$cookie_key ); ?>" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Delete Tags...', 'grand-media' ); ?></a>
					</li>
					<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'delete_media' ) ? '' : ' disabled' ) ); ?>">
						<a
							href="<?php echo esc_url( wp_nonce_url( gm_get_admin_url( array( 'do_gmedia' => 'delete', 'ids' => 'selected' ), array( 'filter' ) ), 'gmedia_delete', '_wpnonce_delete' ) ); ?>"
							class="dropdown-item gmedia-delete text-danger"
							data-confirm="<?php esc_attr_e( "You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", 'grand-media' ); ?>"
						><?php esc_html_e( 'Delete Selected Items', 'grand-media' ); ?></a>
					</li>

					<?php if ( ! $gmProcessor->gmediablank ) { ?>
						<li class="<?php echo esc_attr( $rel_selected_show ); ?>">
							<hr class="dropdown-divider">
						</li>
						<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'edit_media' ) ? '' : ' disabled' ) ); ?>">
							<a href="<?php echo esc_url( wp_nonce_url( gm_get_admin_url( array( 'do_gmedia' => 'recreate' ), array() ), 'gmedia_action', '_wpnonce_action' ) ); ?>" class="dropdown-item gmedia-update"><?php esc_html_e( 'Re-create Images (heavy process)', 'grand-media' ); ?></a>
						</li>
						<li class="<?php echo esc_attr( $rel_selected_show . ( gm_user_can( 'edit_media' ) ? '' : ' disabled' ) ); ?>">
							<a href="<?php echo esc_url( wp_nonce_url( gm_get_admin_url( array( 'do_gmedia' => 'update_meta' ), array() ), 'gmedia_action', '_wpnonce_action' ) ); ?>" class="dropdown-item gmedia-update"><?php esc_html_e( 'Update Metadata in Database', 'grand-media' ); ?></a>
						</li>

						<li>
							<hr class="dropdown-divider">
						</li>
						<li>
							<a class="dropdown-item" href="<?php echo esc_url( gm_get_admin_url( array( 'page' => 'GrandMedia', 'gmedia__in' => 'duplicates' ), array(), true ) ); ?>"><?php esc_html_e( 'Show Duplicates in Library', 'grand-media' ); ?></a>
						</li>
					<?php } ?>

					<li class="<?php echo esc_attr( $rel_selected_hide ); ?>">
						<hr class="dropdown-divider">
					</li>
					<li class="dropdown-header <?php echo esc_attr( $rel_selected_hide ); ?>"><span><?php esc_html_e( 'Select items to see more actions', 'grand-media' ); ?></span></li>
					<?php do_action( 'gmedia_action_list' ); ?>

				</ul>
			</div>
			<?php
		}
		do_action( 'gmedia_library_btn_toolbar' );
		?>

		<?php
		$filter_stack     = $gmCore->_req( 'stack' );
		$filter_stack_arg = $filter_stack ? false : 'show';

		$filter_selected     = ( 'selected' === $gmCore->_req( 'filter' ) );
		$filter_selected_arg = $filter_selected ? false : 'selected';
		?>
		<form class="btn-group" id="gm-stack-btn" name="gm-stack-form" action="<?php echo esc_url( gm_get_admin_url( array( 'stack' => $filter_stack_arg, 'filter' => $filter_selected ), array(), $gmedia_url ) ); ?>" method="post">
			<?php // translators: number. ?>
			<button type="submit" class="btn btn<?php echo ( 'show' === $filter_stack ) ? '-success' : '-info'; ?>"><?php echo wp_kses_post( sprintf( __( '%s in Stack', 'grand-media' ), '<span id="gm-stack-qty">' . count( $gmProcessor->stack_items ) . '</span>' ) ); ?></button>
			<button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
				<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
			</button>
			<input type="hidden" id="gm-stack" data-userid="<?php echo absint( $user_ID ); ?>" data-key="gmedia_<?php echo absint( $user_ID ); ?>_libstack" name="stack_items" value="<?php echo esc_attr( implode( ',', $gmProcessor->stack_items ) ); ?>"/>
			<ul class="dropdown-menu" role="menu">
				<li><a class='dropdown-item' id="gm-stack-show" href="#show">
						<?php
						if ( ! $filter_stack ) {
							esc_html_e( 'Show Stack', 'grand-media' );
						} else {
							esc_html_e( 'Show Library', 'grand-media' );
						}
						?>
					</a></li>
				<li><a class='dropdown-item' id="gm-stack-clear" href="#clear"><?php esc_html_e( 'Clear Stack', 'grand-media' ); ?></a></li>
				<li class="<?php echo esc_attr( gm_user_can( 'gallery_manage' ) ? '' : 'disabled' ); ?>">
					<a href="#libModal" data-bs-toggle="modal" data-modal="quick_gallery_stack" data-action="gmedia_get_modal" data-ckey="gmedia_<?php echo absint( $user_ID ); ?>_libstack" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Quick Gallery from Stack', 'grand-media' ); ?></a>
				</li>
			</ul>
		</form>

		<?php if ( 'select_single' !== $gmProcessor->mode ) { ?>
			<form class="btn-group<?php echo $filter_selected ? ' gm-active' : ''; ?>" id="gm-selected-btn" name="gm-selected-form" action="<?php echo esc_url( gm_get_admin_url( array( 'stack' => $filter_stack, 'filter' => $filter_selected_arg ), array(), $gmedia_url ) ); ?>" method="post">
				<?php // translators: number. ?>
				<button type="submit" class="btn btn<?php echo $filter_selected ? '-success' : '-info'; ?>"><?php echo wp_kses_post( sprintf( __( '%s selected', 'grand-media' ), '<span id="gm-selected-qty">' . count( $gmProcessor->selected_items ) . '</span>' ) ); ?></button>
				<button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
					<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span></button>
				<input type="hidden" id="gm-selected" data-userid="<?php echo absint( $user_ID ); ?>" data-key="<?php echo esc_attr( GmediaProcessor_Library::$cookie_key ); ?>" name="selected_items" value="<?php echo esc_attr( implode( ',', $gmProcessor->selected_items ) ); ?>"/>
				<ul class="dropdown-menu" role="menu">
					<li><a class='dropdown-item' id="gm-selected-show" href="#show">
							<?php
							if ( ! $filter_selected ) {
								esc_html_e( 'Show only selected items', 'grand-media' );
							} else {
								esc_html_e( 'Show all gmedia items', 'grand-media' );
							}
							?>
						</a></li>
					<li><a class='dropdown-item' id="gm-selected-clear" href="#clear"><?php esc_html_e( 'Clear selected items', 'grand-media' ); ?></a></li>
					<li><a class='dropdown-item' id="gm-stack-in" href="#stack_add"><?php esc_html_e( 'Add selected items to Stack', 'grand-media' ); ?></a></li>
					<li><a class='dropdown-item' id="gm-stack-out" href="#stack_remove"><?php esc_html_e( 'Remove selected items from Stack', 'grand-media' ); ?></a></li>
					<?php if ( 'select_multiple' !== $gmProcessor->mode ) { ?>
						<li class="<?php echo esc_attr( gm_user_can( 'gallery_manage' ) ? '' : 'disabled' ); ?>">
							<a href="#libModal" data-bs-toggle="modal" data-modal="quick_gallery" data-action="gmedia_get_modal" data-ckey="<?php echo esc_attr( GmediaProcessor_Library::$cookie_key ); ?>" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Quick Gallery from Selected', 'grand-media' ); ?></a>
						</li>
					<?php } ?>
				</ul>
			</form>
		<?php } ?>
	</div>

</div>
