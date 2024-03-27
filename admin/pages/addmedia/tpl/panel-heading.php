<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * @var $url
 * @var $import
 */
global $gmCore, $gmProcessor;
$extra_tools = ! ( ( $gmProcessor->gmediablank || ( defined( 'GMEDIA_IFRAME' ) && GMEDIA_IFRAME ) ) );
?>
<div class="card-header bg-light clearfix">
	<?php
	if ( $extra_tools ) {
		$refurl = strpos( wp_get_referer(), 'edit_term' ) ? wp_get_referer() : false;
		?>
		<div class="btn-toolbar gap-4 float-start" style="white-space:nowrap;">
			<?php
			if ( $refurl ) {
				$referer = $gmCore->get_admin_url( array(), array(), $refurl );
				?>
				<a class="btn btn-secondary float-start" style="margin-right:20px;" href="<?php echo esc_url( $referer ); ?>"><?php esc_html_e( 'Go Back', 'grand-media' ); ?></a>
				<?php
			}
			?>

			<div class="btn-group">
				<a class="btn btn<?php echo ! $import ? '-primary active' : '-secondary'; ?>" href="<?php echo esc_url( gm_get_admin_url( array(), array( 'import' ), $url ) ); ?>"><?php esc_html_e( 'Upload Files', 'grand-media' ); ?></a>
				<?php if ( gm_user_can( 'import' ) ) { ?>
					<a class="btn btn<?php echo $import ? '-primary active' : '-secondary'; ?>" href="<?php echo esc_url( gm_get_admin_url( array( 'import' => 1 ), array(), $url ) ); ?>"><?php esc_html_e( 'Import', 'grand-media' ); ?></a>
				<?php } ?>
			</div>
			<?php if ( $import && gm_user_can( 'import' ) ) { ?>
				<a class="btn btn-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=GrandMedia_WordpressLibrary' ) ); ?>"><?php esc_html_e( 'Import from WP Media Library', 'grand-media' ); ?></a>
			<?php } ?>
		</div>
	<?php } ?>
	<div id="total-progress-info" class="progress float-end h-auto">
		<?php
		$msg = '';
		if ( ! $import ) {
			$msg = __( 'Add files to the upload queue and click the start button', 'grand-media' );
		} else {
			$msg = __( 'Grab files from other sources', 'grand-media' );
		}
		?>
		<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0;">
			<div style="padding: 2px 10px;"><?php echo esc_html( $msg ); ?></div>
		</div>
		<div style="padding: 2px 10px;"><?php echo esc_html( $msg ); ?></div>
	</div>
	<div class="spinner"></div>
</div>
