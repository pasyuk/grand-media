<?php
/**
 * Gmedia Library
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor, $gmProcessorLibrary, $gm_allowed_tags;

$panel_class         = array();
$gmedia_url          = $gmProcessor->url;
$gmedia_user_options = $gmProcessor->user_options;

$_get_filter = $gmCore->_get( 'filter' );
if ( $_get_filter && ( 'selected' !== $_get_filter ) ) {
	$gmProcessorLibrary->query_args['mime_type'] = $_get_filter;
}

$gmedia_query = $gmDB->get_gmedias( $gmProcessorLibrary->query_args );

$gmedia_filter = $gmDB->filter;
$resultPerPage = $gmDB->resultPerPage;
$openPage      = $gmDB->openPage;
$perPages      = $gmDB->perPages;
$idx0          = $perPages * ( $openPage - 1 );

if ( isset( $gmedia_filter['author__in'] ) && ! gm_user_can( 'show_others_media' ) ) {
	unset( $gmDB->filter['author__in'] );
	unset( $gmedia_filter['author__in'] );
}
if ( $_get_filter && ( 'selected' !== $_get_filter ) ) {
	unset( $gmDB->filter['mime_type'] );
	unset( $gmedia_filter['mime_type'] );
}
if ( $gmProcessor->edit_term ) {
	$taxin = "{$gmProcessor->taxterm}__in";
	if ( isset( $gmedia_filter[ $taxin ] ) && $gmedia_filter[ $taxin ] === $gmProcessorLibrary->query_args[ $taxin ] ) {
		unset( $gmDB->filter[ $taxin ] );
		unset( $gmedia_filter[ $taxin ] );
	}
	$gmProcessorLibrary->dbfilter = $gmedia_filter;
}

$gmedia_count = $gmDB->count_gmedia();
$gmedia_pager = $gmDB->query_pager();

$display_mode_gmedia = $gmProcessor->display_mode;

$panel_class[] = 'panel-fixed-header';
$panel_class[] = "display-as-{$display_mode_gmedia}";
if ( $gmProcessor->user_options['grid_cell_fit_gmedia'] ) {
	$panel_class[] = 'invert-ratio';
}
if ( ! empty( $gmedia_filter ) ) {
	$panel_class[] = 'gmedia-filtered';
}
if ( $gmProcessorLibrary->mode ) {
	$panel_class[] = "mode__{$gmProcessorLibrary->mode}";
}

?>

<?php gmedia_filter_message(); ?>

<div class="card m-0 mw-100 p-0 <?php gm_panel_classes( $panel_class ); ?>" id="gmedia-panel">

	<?php require dirname( __FILE__ ) . '/tpl/panel-heading.php'; ?>

	<div class="card-body"></div>
	<div class="list-group clearfix <?php echo 'grid' === $display_mode_gmedia ? 'list-group-horizontal' : ''; ?>" id="gm-list-table" data-idx0="<?php echo absint( $idx0 + 1 ); ?>">
		<?php
		if ( count( $gmedia_query ) ) {

			gmedia_alert_message();

			if ( ! ( 'edit' === $gmProcessor->mode ) ) {
				foreach ( $gmedia_query as &$item ) {
					gmedia_item_more_data( $item );

					$item->classes = array( 'gmedia-' . $item->type . '-item' );
					if ( 'publish' !== $item->status ) {
						if ( 'private' === $item->status ) {
							$item->classes[] = 'list-group-item-info';
						} elseif ( 'draft' === $item->status ) {
							$item->classes[] = 'list-group-item-warning';
						}
					}
					$item->selected = in_array( $item->ID, (array) $gmProcessor->selected_items, true );
					if ( $item->selected && ( 'select_single' !== $gmProcessor->mode ) ) {
						$item->classes[] = 'gm-selected';
					}
					$item->in_stack = in_array( $item->ID, (array) $gmProcessor->stack_items, true );

					include dirname( __FILE__ ) . "/tpl/{$display_mode_gmedia}-item.php";
				}
				if ( 'grid' === $display_mode_gmedia ) {
					echo '<div class="gm-item-cell-blank"></div><div class="gm-item-cell-blank"></div><div class="gm-item-cell-blank"></div><div class="gm-item-cell-blank"></div><div class="gm-item-cell-blank"></div><div class="gm-item-cell-blank"></div>';
				}
			} elseif ( gm_user_can( 'edit_media' ) ) {
				$gm_category_terms = $gmDB->get_terms( 'gmedia_category', array( 'fields' => 'names' ) );
				$gm_tag_terms      = $gmDB->get_terms( 'gmedia_tag', array( 'fields' => 'names' ) );
				?>
				<script type="text/javascript">
									var gmedia_categories = <?php echo wp_json_encode( $gm_category_terms ); ?>;
									var gmedia_tags = <?php echo wp_json_encode( $gm_tag_terms ); ?>;
				</script>
				<?php
				foreach ( $gmedia_query as &$item ) {
					gmedia_item_more_data( $item );

					$item->classes = array( 'gmedia-' . $item->type . '-item' );
					if ( 'publish' !== $item->status ) {
						if ( 'private' === $item->status ) {
							$item->classes[] = 'list-group-item-info';
						} elseif ( 'draft' === $item->status ) {
							$item->classes[] = 'list-group-item-warning';
						}
					}
					$item->selected = in_array( $item->ID, (array) $gmProcessor->selected_items, true );
					if ( $item->selected ) {
						$item->classes[] = 'gm-selected';
					}
					$item->in_stack = in_array( $item->ID, (array) $gmProcessor->stack_items, true );

					if ( ( (int) $item->author !== $user_ID ) && ! gm_user_can( 'edit_others_media' ) ) {
						include dirname( __FILE__ ) . '/tpl/list-item.php';
					} else {
						include dirname( __FILE__ ) . '/tpl/edit-item.php';
					}
				}
			}
		} else {
			include dirname( __FILE__ ) . '/tpl/no-items.php';
		}
		?>
	</div>

	<?php
	require dirname( __FILE__ ) . '/tpl/panel-footer.php';

	wp_original_referer_field( true, 'previous' );
	wp_nonce_field( 'GmediaGallery' );
	?>
</div>

<div class="modal fade gmedia-modal" id="libModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog"></div>
</div>
<?php if ( gm_user_can( 'edit_media' ) ) { ?>
	<div class="modal fade gmedia-modal" id="gmeditModal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-xl">
			<div class="modal-content"></div>
		</div>
	</div>
<?php } ?>
<div class="modal fade gmedia-modal" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"></h4>
				<div class="btn-toolbar gap-4 float-end" style="margin-top:-4px;">
					<button type="button" class="btn btn-primary"><?php esc_html_e( 'Submit', 'grand-media' ); ?></button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'grand-media' ); ?></button>
				</div>
			</div>
			<div class="modal-body"></div>
		</div>
	</div>
</div>

<?php
require GMEDIA_ABSPATH . 'admin/tpl/modal-share.php';

if ( 'edit' === $gmProcessor->mode ) {
	$customfield_meta_type = 'gmedia';
	include GMEDIA_ABSPATH . 'admin/tpl/modal-customfield.php';
}
?>
