<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * System info (under construction)
 */
?>
<fieldset id="gmedia_settings_sysinfo" class="tab-pane">
	<?php
	if ( ( function_exists( 'memory_get_usage' ) ) && ( ini_get( 'memory_limit' ) ) ) {
		$memory_limit = ini_get( 'memory_limit' );
		$memory_usage = round( memory_get_usage() / ( 1024 * 1024 ), 1 );
		echo '<p>' . esc_html( __( 'PHP Memory Limit: ', 'grand-media' ) . $memory_limit ) . '</p>';
		echo '<p>' . esc_html( __( 'PHP Memory Used: ', 'grand-media' ) . $memory_usage ) . 'M</p>';
	}
	?>
	<p><?php esc_html_e( 'Under construction...', 'grand-media' ); ?></p>

</fieldset>
