<?php
/**
 * Gmedia AddMedia
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

global $user_ID, $gmGallery, $gmProcessor, $gmCore, $gmDB, $gm_allowed_tags;

$url    = $gmProcessor->url;
$import = $gmProcessor->import;
?>

<div class="card m-0 mw-100 p-0">

	<?php require dirname( __FILE__ ) . '/tpl/panel-heading.php'; ?>

	<div class="card-body" id="gmedia-msg-panel"></div>
	<div class="container-fluid gmAddMedia">
		<?php
		if ( ! $import ) {
			include dirname( __FILE__ ) . '/tpl/upload.php';
		} else {
			include dirname( __FILE__ ) . '/tpl/import.php';
		}

		wp_original_referer_field( true, 'previous' );
		?>
	</div>
</div>
