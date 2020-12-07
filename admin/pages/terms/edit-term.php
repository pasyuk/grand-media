<?php
/**
 * Gmedia Term (Album, Category) Edit
 */

// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$term_id              = $gmCore->_get( 'edit_term' );
$gmedia_url           = add_query_arg( [ 'edit_term' => $term_id ], $gmProcessor->url );
$gmedia_user_options  = $gmProcessor->user_options;
$gmedia_term_taxonomy = $gmProcessor->taxonomy;
$taxterm              = $gmProcessor->taxterm;

if ( ! gm_user_can( "{$taxterm}_manage" ) ) {
	die( '-1' );
}

$term_id = (int) $term_id;
$term    = $gmDB->get_term( $term_id );

if ( empty( $term ) || is_wp_error( $term ) ) {
	return;
}
gmedia_term_item_more_data( $term );

do_action( 'gmedia_term_before_panel' );
?>

<?php
if ( 'album' === $taxterm ) {
	$_module_preset = ! empty( $term->meta['_module_preset'][0] ) ? $term->meta['_module_preset'][0] : '';
	$_module        = $gmCore->getModulePreset( $_module_preset );
	$limitation     = empty( $gmGallery->options['license_key'] ) && in_array( $_module['module'], [ 'amron', 'phantom', 'cubik-lite', 'photomania', 'wp-videoplayer', 'jq-mplayer', 'minima' ], true );
	if ( $limitation ) {
		?>
		<div style="overflow:hidden; margin-bottom: 6px; padding: 10px; background-color: #fff; border: 1px solid red; border-radius: 5px; font-size: 14px; font-weight: bold;"><?php _e( 'Note: Free version allows you to show maximum 40 images per gallery on the frontend. Purchase license key <a href="https://codeasily.com/gmedia-premium/" target="_blank">here</a>. It\'s a one time payment.', 'grand-media' ); ?></div>
		<?php
	}
}
?>

<div class="panel panel-default panel-fixed-header">

	<?php
	include dirname( __FILE__ ) . '/tpl/term-panel-heading.php';

	include dirname( __FILE__ ) . "/tpl/{$taxterm}-edit-item.php";
	?>

</div>

<?php
do_action( "gmedia_term_{$taxterm}_after_panel", $term );
do_action( 'gmedia_term_after_panel' );
?>
