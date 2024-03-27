<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Panel heading for term
 *
 * @var $gmCore
 * @var $term_id
 * @var $term
 */
?>
<div class="card-header-fake"></div>
<div class="card-header bg-light clearfix">
	<div class="btn-toolbar gap-4 float-start">
		<a class="btn btn-secondary float-start" style="margin-right:20px;" href="<?php echo esc_url( remove_query_arg( array( 'preset_module', 'preset' ), wp_get_referer() ) ); ?>"><?php esc_html_e( 'Go Back', 'grand-media' ); ?></a>

		<?php if ( $term_id ) { ?>
			<div class="btn-group">
				<a class="btn btn-secondary" href="#"><?php esc_html_e( 'Action', 'grand-media' ); ?></a>
				<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
					<span class="visually-hidden"><?php esc_html_e( 'Toggle Dropdown', 'grand-media' ); ?></span>
				</button>
				<ul class="dropdown-menu" role="menu">
					<li><a class="dropdown-item" href="<?php echo esc_url( add_query_arg( array( 'page' => 'GrandMedia_Galleries', 'gallery_module' => $term->module['name'], 'preset' => $term->term_id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Create Gallery with this preset', 'grand-media' ); ?></a></li>
				</ul>
			</div>

			<?php // translators: preset name. ?>
			<a class="btn btn-info float-start" style="margin-left:20px;" href="<?php echo esc_url( $gmCore->get_admin_url( array( 'preset_module' => $term->module['name'] ), array( 'preset' ) ) ); ?>"><?php printf( esc_html__( 'New %s Preset', 'grand-media' ), esc_html( $term->module['info']['title'] ) ); ?></a>
		<?php } ?>
	</div>
	<div class="spinner"></div>
</div>
