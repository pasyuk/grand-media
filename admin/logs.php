<?php
/**
 * Gmedia Logs
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $user_ID, $wpdb, $gmDB, $gmCore, $gmGallery, $gmProcessor, $gm_allowed_tags;

$gmedia_url          = $gmProcessor->url;
$gmedia_user_options = $gmProcessor->user_options;

if ( isset( $_GET['do_gmedia'] ) && 'clear_logs' === $_GET['do_gmedia'] ) {
	check_admin_referer( 'gmedia_clear_logs', '_wpnonce_clear_logs' );
	$wpdb->query( "DELETE FROM {$wpdb->prefix}gmedia_log WHERE 1 = 1" );
}


$gmedia_filter = array();

$openPage = ! empty( $_GET['pager'] ) ? (int) $_GET['pager'] : 1;

$where       = '';
$log_search  = '';
$log_orderby = 'ORDER BY l.' . esc_sql( $gmedia_user_options['orderby_gmedia_log'] ) . ' ' . esc_sql( $gmedia_user_options['sortorder_gmedia_log'] );
$lim         = '';

if ( isset( $_POST['filter_author'] ) ) {
	$authors        = $gmCore->_post( 'author_ids' );
	$_GET['author'] = (int) $authors;
}
if ( isset( $_GET['author'] ) ) {
	$author                  = (int) $_GET['author'];
	$where                   .= "AND l.log_author = '{$author}' ";
	$gmedia_filter['author'] = $author;
}
if ( isset( $_GET['log_event'] ) ) {
	$gmedia_filter['log_event'] = $gmCore->_get( 'log_event' );

	$where .= $wpdb->prepare( 'AND l.log = %s ', $gmedia_filter['log_event'] );
}
if ( isset( $_GET['s'] ) ) {
	$log_s = trim( $gmCore->_get( 's' ) );
	if ( '#' === substr( $log_s, 0, 1 ) ) {
		$ids                     = wp_parse_id_list( substr( $log_s, 1 ) );
		$where                   .= ' AND l.ID IN (\'' . implode( "','", $ids ) . '\')';
		$log_s                   = false;
		$gmedia_filter['search'] = $log_s;
	}
	if ( ! empty( $log_s ) ) {
		// added slashes screw with quote grouping when done early, so done later.
		$log_s = stripslashes( $log_s );

		// split the words in an array if seperated by a space or comma.
		preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $log_s, $matches );
		$search_terms = array_map(
			function ( $a ) {
				return trim( $a, "\"'\n\r " );
			},
			$matches[0]
		);

		$n         = '%';
		$searchand = '';

		foreach ( (array) $search_terms as $search_term ) {
			$search_term = addslashes_gpc( $search_term );
			$log_search  .= "{$searchand}(g.title LIKE '{$n}{$search_term}{$n}') OR (g.description LIKE '{$n}{$search_term}{$n}')";
			$searchand   = ' AND ';
		}

		$search_term = esc_sql( $log_s );
		if ( count( $search_terms ) > 1 && $search_terms[0] !== $log_s ) {
			$log_search .= " OR (g.title LIKE '{$n}{$search_term}{$n}') OR (g.description LIKE '{$n}{$search_term}{$n}')";
		}

		if ( ! empty( $log_search ) ) {
			$log_search = " AND ({$log_search}) ";
		}
		$gmedia_filter['search'] = $log_s;
	}
}

$limit = intval( $gmedia_user_options['per_page_gmedia_log'] );
if ( $limit > 0 ) {
	$offset = ( $openPage - 1 ) * $limit;
	$lim    = " LIMIT {$offset}, {$limit}";
}

$query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}gmedia_log AS l INNER JOIN {$wpdb->prefix}gmedia AS g ON g.ID = l.ID WHERE 1=1 $where $log_search $log_orderby $lim";
//echo '<pre>' . print_r($query, true) . '</pre>';
$logs        = $wpdb->get_results( $query );
$totalResult = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

if ( ( 1 > $limit ) || ( 0 === $totalResult ) ) {
	$limit     = $totalResult;
	$log_pages = 1;
} else {
	$log_pages = ceil( $totalResult / $limit );
}

$gmDB->pages    = $log_pages;
$gmDB->openPage = $openPage;
$gmedia_pager   = $gmDB->query_pager();

?>
<div class="card m-0 mw-100 p-0 panel-fixed-header" id="gmedia-panel">
	<div class="card-header-fake"></div>
	<div class="card-header bg-light clearfix" style="padding-bottom:2px;">
		<div class="float-end" style="margin-bottom:3px;">
			<div class="clearfix">
				<?php require GMEDIA_ABSPATH . 'admin/tpl/search-form.php'; ?>

				<div class="btn-toolbar gap-4 float-end" style="margin-bottom:4px; margin-left:4px;">
					<?php if ( ! $gmProcessor->gmediablank ) { ?>
						<a title="<?php esc_attr_e( 'More Screen Settings', 'grand-media' ); ?>" class="show-settings-link float-end btn btn-secondary btn-xs"><i class="fa-solid fa-gear"></i></a>
					<?php } ?>
				</div>
			</div>

			<?php echo wp_kses( $gmedia_pager, $gm_allowed_tags ); ?>

			<div class="spinner"></div>

		</div>
		<div class="btn-toolbar gap-4 float-start" style="margin-bottom:7px;">
			<div class="btn-group">
				<?php if ( ! empty( $gmedia_filter ) ) { ?>
					<a class="btn btn-warning" title="<?php esc_attr_e( 'Reset Filter', 'grand-media' ); ?>" rel="total" href="<?php echo esc_url( gm_get_admin_url( array(), array(), $gmedia_url ) ); ?>"><?php esc_html_e( 'Reset Filter', 'grand-media' ); ?></a>
				<?php } else { ?>
					<button type="button" class="btn btn-secondary" data-bs-toggle="dropdown"><?php esc_html_e( 'Filter', 'grand-media' ); ?></button>
				<?php } ?>
				<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
					<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
				</button>
				<ul class="dropdown-menu" role="menu">
					<li role="presentation" class="dropdown-header"><?php esc_html_e( 'FILTER BY AUTHOR', 'grand-media' ); ?></li>
					<li class="gmedia_author<?php echo esc_attr( isset( $gmedia_filter['author'] ) ? ' active' : '' ); ?>">
						<a class="dropdown-item gmedia-modal" href="#libModal" data-bs-toggle="modal" data-modal="filter_author" data-action="gmedia_get_modal"><?php esc_html_e( 'Choose authors', 'grand-media' ); ?></a>
					</li>
					<li role="presentation" class="dropdown-header"><?php esc_html_e( 'FILTER BY EVENT', 'grand-media' ); ?></li>
					<li class="gmedia_event<?php echo esc_attr( ( isset( $gmedia_filter['log_event'] ) && 'view' === $gmedia_filter['log_event'] ) ? ' active' : '' ); ?>">
						<a class="dropdown-item" href="<?php echo esc_url( add_query_arg( array( 'log_event' => 'view' ), $gmedia_url ) ); ?>"><?php esc_html_e( 'View / Play', 'grand-media' ); ?></a>
					</li>
					<li class="gmedia_event<?php echo esc_attr( ( isset( $gmedia_filter['log_event'] ) && 'like' === $gmedia_filter['log_event'] ) ? ' active' : '' ); ?>">
						<a class="dropdown-item" href="<?php echo esc_url( add_query_arg( array( 'log_event' => 'like' ), $gmedia_url ) ); ?>"><?php esc_html_e( 'Like', 'grand-media' ); ?></a>
					</li>
					<li class="gmedia_event<?php echo esc_attr( ( isset( $gmedia_filter['log_event'] ) && 'rate' === $gmedia_filter['log_event'] ) ? ' active' : '' ); ?>">
						<a class="dropdown-item" href="<?php echo esc_url( add_query_arg( array( 'log_event' => 'rate' ), $gmedia_url ) ); ?>"><?php esc_html_e( 'Rate', 'grand-media' ); ?></a>
					</li>
				</ul>
			</div>
			<a class="btn btn-danger float-start" href="<?php echo esc_url( wp_nonce_url( gm_get_admin_url( array( 'do_gmedia' => 'clear_logs' ), array(), $gmedia_url ), 'gmedia_clear_logs', '_wpnonce_clear_logs' ) ); ?>" data-confirm="<?php esc_attr_e( "You are about to clear all Gmedia logs.\n\r'Cancel' to stop, 'OK' to clear.", 'grand-media' ); ?>"><?php esc_html_e( 'Clear Logs', 'grand-media' ); ?></a>

		</div>

	</div>
	<form class="card-body" id="gm-log-table" style="margin-bottom:4px;">
		<?php
		if ( empty( $gmGallery->options['license_key'] ) ) {
			?>
			<div class="alert alert-warning" role="alert"><strong><?php esc_html_e( 'It\'s a premium feature. Gmedia Logger requires License Key.' ); ?></strong></div>
			<?php
		} elseif ( ! empty( $gmGallery->options['disable_logs'] ) ) {
			?>
			<div class="alert alert-warning" role="alert"><strong><?php esc_html_e( 'Gmedia Logger is disabled in settings.' ); ?></strong></div>
			<?php
		}
		?>
		<div class="table-responsive">
			<table class="table table-condensed table-hover">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Media', 'grand-media' ); ?></th>
					<th><?php esc_html_e( 'Info', 'grand-media' ); ?></th>
					<th><?php esc_html_e( 'Log Date', 'grand-media' ); ?></th>
					<th><?php esc_html_e( 'User / IP', 'grand-media' ); ?></th>
					<th><?php esc_html_e( 'Event', 'grand-media' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				if ( $logs ) {
					foreach ( $logs as $item ) {
						gmedia_item_more_data( $item );
						?>
						<tr>
							<td style="width:150px;">
								<div class="img-thumbnail" style="margin-bottom: 0;">
									<?php
									$images = $gmCore->gm_get_media_image( $item, 'all' );
									$thumb  = '<img class="gmedia-thumb" src="' . $images['thumb'] . '" alt=""/>';

									if ( ! empty( $images['icon'] ) ) {
										$thumb .= '<img class="gmedia-typethumb" src="' . $images['icon'] . '" alt=""/>';
									}
									echo wp_kses_post( $thumb );
									?>
								</div>
							</td>
							<td>
								<p class="media-meta"><span class="badge label-default">#<?php echo (int) $item->ID; ?>:</span> <b><?php echo esc_html( $item->title ); ?>&nbsp;</b></p>
								<p class="media-meta">
									<span class="badge label-default"><?php esc_html_e( 'Album', 'grand-media' ); ?>:</span>
									<?php
									if ( $item->album ) {
										$terms_album = array();
										foreach ( $item->album as $c ) {
											$terms_album[] = $c->name;
										}
										$terms_album = join( ', ', $terms_album );
									} else {
										$terms_album = '&#8212;';
									}
									echo esc_html( $terms_album );
									?>
									<br/><span class="badge label-default"><?php esc_html_e( 'Category', 'grand-media' ); ?>:</span>
									<?php
									if ( $item->categories ) {
										$terms_category = array();
										foreach ( $item->categories as $c ) {
											$terms_category[] = $c->name;
										}
										$terms_category = join( ', ', $terms_category );
									} else {
										$terms_category = __( 'Uncategorized', 'grand-media' );
									}
									echo esc_html( $terms_category );
									?>
									<br/><span class="badge label-default"><?php esc_html_e( 'Tags', 'grand-media' ); ?>:</span>
									<?php
									if ( $item->tags ) {
										$terms_tag = array();
										foreach ( $item->tags as $c ) {
											$terms_tag[] = $c->name;
										}
										$terms_tag = join( ', ', $terms_tag );
									} else {
										$terms_tag = '&#8212;';
									}
									echo esc_html( $terms_tag );
									?>
								</p>
								<p class="media-meta">
									<span class="badge label-default"><?php esc_html_e( 'Views / Likes', 'grand-media' ); ?>:</span>
									<?php echo ( isset( $item->meta['views'][0] ) ? (int) $item->meta['views'][0] : '0' ) . ' / ' . ( isset( $item->meta['likes'][0] ) ? (int) $item->meta['likes'][0] : '0' ); ?>

									<?php
									if ( isset( $item->meta['_rating'][0] ) ) {
										$ratings = maybe_unserialize( $item->meta['_rating'][0] );
										?>
										<br/><span class="badge label-default"><?php esc_html_e( 'Rating', 'grand-media' ); ?>:</span> <?php echo esc_html( round( $ratings['value'], 2 ) . ' / ' . $ratings['votes'] ); ?>
									<?php } ?>
									<br/><span class="badge label-default"><?php esc_html_e( 'Type', 'grand-media' ); ?>:</span> <?php echo esc_html( $item->mime_type ); ?>
									<br/><span class="badge label-default"><?php esc_html_e( 'Filename', 'grand-media' ); ?>:</span> <a href="<?php echo esc_url( gm_get_admin_url( array( 'page' => 'GrandMedia', 'gmedia__in' => $item->ID ), array(), $gmedia_url ) ); ?>"><?php echo esc_html( $item->gmuid ); ?></a>
								</p>
							</td>
							<td><p><?php echo esc_html( $item->log_date ); ?></p></td>
							<td>
								<p>
									<?php
									$author_name = $item->log_author ? get_user_option( 'display_name', $item->log_author ) : __( 'Guest', 'grand-media' );
									printf( '<a class="gmedia-author" href="%s">%s</a>', esc_url( add_query_arg( array( 'author' => $item->log_author ), $gmedia_url ) ), esc_html( $author_name ) );
									?>
								</p>
								<p class="media-meta"><span class="badge label-default"><?php esc_html_e( 'IP Address', 'grand-media' ); ?>:</span> <?php echo esc_html( $item->ip_address ); ?></p>
							</td>
							<td><p>
									<?php
									switch ( $item->log ) {
										case 'view':
											esc_html_e( 'View / Play', 'grand-media' );
											break;
										case 'like':
											esc_html_e( 'Like', 'grand-media' );
											break;
										case 'rate':
											echo esc_html( __( 'Rate', 'grand-media' ) . ": {$item->log_data}" );
											break;
									}
									?>
								</p></td>
						</tr>
						<?php
					}
				} else {
					echo '<tr><td colspan="5" style="font-weight: bold; text-align: center; padding: 30px 0;">' . esc_html__( 'No Records.', 'grand-media' ) . '</td></tr>';
				}
				?>
				</tbody>

			</table>
		</div>
		<?php
		wp_original_referer_field( true, 'previous' );
		wp_nonce_field( 'GmediaGallery' );
		?>
	</form>
</div>

<div class="modal fade gmedia-modal" id="libModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog"></div>
</div>
