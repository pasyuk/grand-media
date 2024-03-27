<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * grandWPMedia()
 */
function grandWPMedia() {
	global $user_ID, $gmDB, $gmCore, $gmProcessor, $gmGallery, $gm_allowed_tags;

	$url = add_query_arg( array( 'page' => $gmProcessor->page ), admin_url( 'admin.php' ) );

	$gm_screen_options = get_user_meta( $user_ID, 'gm_screen_options', true );
	if ( ! is_array( $gm_screen_options ) ) {
		$gm_screen_options = array();
	}
	$gm_screen_options = array_merge( $gmGallery->options['gm_screen_options'], $gm_screen_options );

	$arg          = array(
		'mime_type' => $gmCore->_get( 'mime_type', '' ),
		'orderby'   => $gmCore->_get( 'orderby', $gm_screen_options['orderby_wpmedia'] ),
		'order'     => $gmCore->_get( 'order', $gm_screen_options['sortorder_wpmedia'] ),
		'limit'     => $gm_screen_options['per_page_wpmedia'],
		'filter'    => $gmCore->_get( 'filter', '' ),
		's'         => $gmCore->_get( 's', '' ),
	);
	$wpMediaLib   = $gmDB->get_wp_media_lib( $arg );
	$gmedia_pager = $gmDB->query_pager();

	$gm_qty = array( 'total' => '', 'image' => '', 'audio' => '', 'video' => '', 'text' => '', 'application' => '', 'other' => '' );

	$gmDbCount = $gmDB->count_wp_media( $arg );
	foreach ( $gmDbCount as $key => $value ) {
		$gm_qty[ $key ] = '<span class="badge badge-info float-end">' . (int) $value . '</span>';
	}
	?>
	<div class="card m-0 mw-100 p-0 panel-fixed-header">
		<div class="card-header-fake"></div>
		<div class="card-header bg-light clearfix" style="padding-bottom:2px;">
			<div class="float-end" style="margin-bottom:3px;">
				<div class="clearfix">
					<?php include GMEDIA_ABSPATH . 'admin/tpl/search-form.php'; ?>

					<div class="btn-toolbar gap-4 float-end" style="margin-bottom:4px; margin-left:4px;">
						<?php if ( ! $gmProcessor->gmediablank ) { ?>
							<a title="<?php esc_html_e( 'More Screen Settings', 'grand-media' ); ?>" class="show-settings-link float-end btn btn-secondary btn-xs"><i class="fa-solid fa-gear"></i></a>
						<?php } ?>
					</div>
				</div>

				<?php echo wp_kses( $gmedia_pager, $gm_allowed_tags ); ?>

				<div class="spinner"></div>

			</div>

			<div class="btn-toolbar gap-4 float-start">
				<div class="btn-group gm-checkgroup" id="cb_global-btn">
					<span class="btn btn-secondary active"><input class="doaction" id="cb_global" data-group="cb_object" type="checkbox"/></span>
					<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
						<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
					</button>
					<ul class="dropdown-menu" role="menu">
						<li><a class="dropdown-item" data-select="total" href="#"><?php esc_html_e( 'All', 'grand-media' ); ?></a></li>
						<li><a class="dropdown-item" data-select="none" href="#"><?php esc_html_e( 'None', 'grand-media' ); ?></a></li>
						<li>
							<hr class="dropdown-divider">
						</li>
						<li><a class="dropdown-item" data-select="image" href="#"><?php esc_html_e( 'Images', 'grand-media' ); ?></a></li>
						<li><a class="dropdown-item" data-select="audio" href="#"><?php esc_html_e( 'Audio', 'grand-media' ); ?></a></li>
						<li><a class="dropdown-item" data-select="video" href="#"><?php esc_html_e( 'Video', 'grand-media' ); ?></a></li>
						<li>
							<hr class="dropdown-divider">
						</li>
						<li>
							<a class="dropdown-item" data-select="reverse" href="#" title="<?php esc_attr_e( 'Reverse only visible items', 'grand-media' ); ?>"><?php esc_html_e( 'Reverse', 'grand-media' ); ?></a>
						</li>
					</ul>
				</div>

				<div class="btn-group">
					<?php $curr_mime = explode( ',', $gmCore->_get( 'mime_type', 'total' ) ); ?>
					<?php if ( ! empty( $gmDB->filter ) ) { ?>
						<a class="btn btn-warning" title="<?php esc_attr_e( 'Reset Filter', 'grand-media' ); ?>" rel="total" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Filter', 'grand-media' ); ?></a>
					<?php } else { ?>
						<button type="button" class="btn btn-secondary"><?php esc_html_e( 'Filter', 'grand-media' ); ?></button>
					<?php } ?>
					<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
						<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
					</button>
					<ul class="dropdown-menu" role="menu">
						<li role="presentation" class="dropdown-header"><?php esc_html_e( 'TYPE', 'grand-media' ); ?></li>
						<li class="total<?php echo in_array( 'total', $curr_mime, true ) ? ' active' : ''; ?>">
							<a class="dropdown-item" rel="total" href="<?php echo esc_url( $gmCore->get_admin_url( array(), array( 'mime_type', 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty['total'] . __( 'All', 'grand-media' ) ); ?></a>
						</li>
						<li class="image<?php echo ( in_array( 'image', $curr_mime, true ) ? ' active' : '' ) . ( $gmDbCount['image'] ? '' : ' disabled' ); ?>">
							<a class="dropdown-item" rel="image" href="<?php echo esc_url( $gmCore->get_admin_url( array( 'mime_type' => 'image' ), array( 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty['image'] . __( 'Images', 'grand-media' ) ); ?></a></li>
						<li class="audio<?php echo ( in_array( 'audio', $curr_mime, true ) ? ' active' : '' ) . ( $gmDbCount['audio'] ? '' : ' disabled' ); ?>">
							<a class="dropdown-item" rel="audio" href="<?php echo esc_url( $gmCore->get_admin_url( array( 'mime_type' => 'audio' ), array( 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty['audio'] . __( 'Audio', 'grand-media' ) ); ?></a></li>
						<li class="video<?php echo ( in_array( 'video', $curr_mime, true ) ? ' active' : '' ) . ( $gmDbCount['video'] ? '' : ' disabled' ); ?>">
							<a class="dropdown-item" rel="video" href="<?php echo esc_url( $gmCore->get_admin_url( array( 'mime_type' => 'video' ), array( 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty['video'] . __( 'Video', 'grand-media' ) ); ?></a></li>
						<li class="application<?php echo ( ( in_array( 'application', $curr_mime, true ) || in_array( 'text', $curr_mime, true ) ) ? ' active' : '' ) . ( $gmDbCount['other'] ? '' : ' disabled' ); ?>">
							<a class="dropdown-item" rel="application" href="<?php echo esc_url( $gmCore->get_admin_url( array( 'mime_type' => 'application,text' ), array( 'pager' ) ) ); ?>"><?php echo wp_kses_post( $gm_qty['other'] . __( 'Other', 'grand-media' ) ); ?></a></li>
						<?php do_action( 'gmedia_wp_filter_list' ); ?>
					</ul>
				</div>

				<div class="btn-group">
					<a class="btn btn-secondary" href="#"><?php esc_html_e( 'Action', 'grand-media' ); ?></a>
					<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
						<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span></button>
					<?php
					$rel_selected_show = 'rel-selected-show';
					$rel_selected_hide = 'rel-selected-hide';
					?>
					<ul class="dropdown-menu" role="menu">
						<li class="
						<?php
						echo esc_attr( $rel_selected_show );
						if ( ! $gmCore->caps['gmedia_import'] ) {
							echo ' disabled';
						}
						?>
						">
							<a href="#importModal" data-bs-toggle="modal" data-modal="import-wpmedia" data-action="gmedia_import_wpmedia_modal" class="dropdown-item gmedia-modal"><?php esc_html_e( 'Import to Gmedia Library...', 'grand-media' ); ?></a>
						</li>
						<li class="dropdown-header <?php echo esc_attr( $rel_selected_hide ); ?>"><span><?php esc_html_e( 'Select items to see more actions', 'grand-media' ); ?></span></li>
						<?php do_action( 'gmedia_action_list' ); ?>
					</ul>
				</div>

				<form class="btn-group" id="gm-selected-btn" name="gm-selected-form" action="<?php echo esc_url( add_query_arg( array( 'filter' => 'selected' ), $url ) ); ?>" method="post">
					<button type="submit" class="btn btn<?php echo ( 'selected' === $gmCore->_req( 'filter' ) ) ? '-success' : '-info'; ?>">
						<?php
						// translators: number.
						echo wp_kses_post( sprintf( __( '%s selected', 'grand-media' ), '<span id="gm-selected-qty">' . count( $gmProcessor->selected_items ) . '</span>' ) );
						?>
					</button>
					<button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
						<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span></button>
					<input type="hidden" id="gm-selected" data-userid="<?php echo absint( $user_ID ); ?>" data-key="gmedia_library:wpmedia" name="selected_items" value="<?php echo esc_attr( implode( ',', $gmProcessor->selected_items ) ); ?>"/>
					<ul class="dropdown-menu" role="menu">
						<li><a class="dropdown-item" id="gm-selected-show" href="#show"><?php esc_html_e( 'Show only selected items', 'grand-media' ); ?></a></li>
						<li><a class="dropdown-item" id="gm-selected-clear" href="#clear"><?php esc_html_e( 'Clear selected items', 'grand-media' ); ?></a></li>
					</ul>
				</form>

			</div>

		</div>
		<div class="card-body"></div>
		<?php if ( ! empty( $wpMediaLib ) ) { ?>
			<table class="table table-striped table-hover table-condenced">
				<col class="cb" style="width:40px;"/>
				<col class="id" style="width:80px;"/>
				<col class="file" style="width:100px;"/>
				<col class="type" style="width:80px;"/>
				<col class="title"/>
				<col class="descr hidden-xs"/>
				<thead>
				<tr>
					<th class="cb"><span>#</span></th>
					<th class="id" title="<?php esc_attr_e( 'Sort by ID', 'grand-media' ); ?>">
						<?php $new_order = ( 'ID' === $arg['orderby'] ) ? ( ( 'DESC' === $arg['order'] ) ? 'ASC' : 'DESC' ) : 'DESC'; ?>
						<a href="<?php echo esc_url( $gmCore->get_admin_url( array( 'orderby' => 'ID', 'order' => $new_order ) ) ); ?>"><?php esc_html_e( 'ID', 'grand-media' ); ?></a>
					</th>
					<th class="file" title="<?php esc_attr_e( 'Sort by filename', 'grand-media' ); ?>">
						<?php $new_order = ( 'filename' === $arg['orderby'] ) ? ( ( 'DESC' === $arg['order'] ) ? 'ASC' : 'DESC' ) : 'DESC'; ?>
						<a href="
						<?php
						echo esc_url(
							$gmCore->get_admin_url(
								array(
									'orderby' => 'filename',
									'order'   => $new_order,
								)
							)
						);
						?>
						"><?php esc_html_e( 'File', 'grand-media' ); ?></a>
					</th>
					<th class="type"><span><?php esc_html_e( 'Type', 'grand-media' ); ?></span></th>
					<th class="title" title="<?php esc_attr_e( 'Sort by Title', 'grand-media' ); ?>">
						<?php $new_order = ( 'title' === $arg['orderby'] ) ? ( ( 'DESC' === $arg['order'] ) ? 'ASC' : 'DESC' ) : 'DESC'; ?>
						<a href="<?php echo esc_url( $gmCore->get_admin_url( array( 'orderby' => 'title', 'order' => $new_order ) ) ); ?>"><?php esc_html_e( 'Title', 'grand-media' ); ?></a>
					</th>
					<th class="descr hidden-xs"><span><?php esc_html_e( 'Description', 'grand-media' ); ?></span></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $wpMediaLib as $item ) {
					$is_selected = in_array( $item->ID, $gmProcessor->selected_items, true );
					$image       = wp_get_attachment_image( $item->ID, array( 50, 50 ), false );
					if ( ! $image ) {
						$src = wp_mime_type_icon( $item->ID );
						if ( $src ) {
							$src_image = $gmCore->gmedia_url . '/admin/assets/img/' . wp_basename( $src );
							$image     = '<img src="' . $src_image . '" width="50" height="50" alt="icon" title="' . esc_attr( $item->post_title ) . '"/>';
						}
					}
					$item_url  = wp_get_attachment_url( $item->ID );
					$file_info = pathinfo( $item_url );
					$type      = explode( '/', $item->post_mime_type );
					?>
					<tr data-id="<?php echo absint( $item->ID ); ?>">
						<td class="cb">
							<span class="cb_object"><input name="doaction[]" type="checkbox" data-type="<?php echo esc_attr( $type[0] ); ?>" value="<?php echo absint( $item->ID ); ?>"<?php echo $is_selected ? ' checked="checked"' : ''; ?>/></span>
						</td>
						<td class="id"><span><?php echo absint( $item->ID ); ?></span></td>
						<td class="file">
							<span><a href="<?php echo esc_url( admin_url( 'media.php?action=edit&amp;attachment_id=' . $item->ID ) ); ?>"><?php echo wp_kses_post( $image ); ?></a></span>
						</td>
						<td class="type"><span><?php echo esc_html( $file_info['extension'] ); ?></span></td>
						<td class="title"><span><?php echo esc_html( $item->post_title ); ?></span></td>
						<td class="descr hidden-xs">
							<div><?php echo esc_html( $item->post_content ); ?></div>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		<?php } else { ?>
			<div class="card-body">
				<div class="well well-lg text-center">
					<h4><?php esc_html_e( 'No items to show.', 'grand-media' ); ?></h4>
				</div>
			</div>
		<?php } ?>
		<?php
		wp_original_referer_field( true, 'previous' );
		wp_nonce_field( 'GmediaGallery' );
		?>
	</div>

	<script type="text/javascript">
			function gmedia_import_done() {
				if (jQuery('#import_window').is(':visible')) {
					var btn = jQuery('#import-done');
					btn.text(btn.data('complete-text')).prop('disabled', false);
				}
			}
	</script>
	<div class="modal fade gmedia-modal" id="importModal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog"></div>
	</div>

	<?php
}
