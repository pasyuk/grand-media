<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Search form template
 */
global $gmCore;
?>
<form class="form-inline gmedia-search-form" role="search" method="get">
	<div class="form-group">
		<?php
		foreach ( $_GET as $key => $value ) {
			if ( ! in_array( $key, array( 'doing_wp_cron', 'do_gmedia', 'did_gmedia', 'do_gmedia_terms', 'did_gmedia_terms', 'ids', 's', 'pager' ), true ) ) {
				if ( strpos( $key, '_wpnonce' ) !== false ) {
					continue;
				}
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}
				if ( 'edit_term' === $key ) {
					$value = (int) $value;
				}
				?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
				<?php
			}
		}
		$gm_search_string = $gmCore->_get( 's', '' );
		?>
		<input id="gmedia-search" class="form-control input-xs allow-key-enter" type="text" name="s" placeholder="<?php esc_html_e( 'Search...', 'grand-media' ); ?>" value="<?php echo esc_attr( $gm_search_string ); ?>"/>
	</div>
	<button type="submit" class="btn btn-secondary btn-xs"><i class='fa-solid fa-magnifying-glass'></i></button>
</form>
