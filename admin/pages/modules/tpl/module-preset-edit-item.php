<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Edit Gallery Form
 *
 * @var $term_id
 * @var $gmedia_url
 * @var $term
 * @var $gmedia_term_taxonomy
 */
global $user_ID;
?>

<form method="post" id="gmedia-edit-term" name="gmEditTerm" data-id="<?php echo absint( $term_id ); ?>" action="<?php echo esc_url( $gmedia_url ); ?>">
	<div class="card-body">
		<h4 style="margin-top:0;">
			<?php
			if ( $term_id ) {
				$is_preset = 'edit';
				?>
				<span class="float-end"><?php echo esc_html( __( 'ID', 'grand-media' ) . ": {$term->term_id}" ); ?></span>
				<?php
				// translators: preset name.
				printf( esc_html__( 'Edit %s Preset', 'grand-media' ), esc_html( $term->module['info']['title'] ) );
				echo ': <em>' . esc_html( $term->name ) . '</em>';
			} else {
				$is_preset = 'new';
				// translators: preset name.
				printf( esc_html__( 'New %s Preset', 'grand-media' ), esc_html( $term->module['info']['title'] ) );
			}
			?>
		</h4>
		<div class="row justify-content-between">
			<div class="col-sm-6">
				<div class="form-group">
					<label><?php esc_html_e( 'Name', 'grand-media' ); ?></label>
					<?php
					if ( $term_id && ! $term->name ) {
						if ( (int) $term->global ) {
							$is_preset = 'default';
						} else {
							$is_preset = 'global';
						}
						?>
						<input type="text" class="form-control input-sm" name="term[name]" value="<?php 'global' === $is_preset ? esc_attr_e( 'Global Settings', 'grand-media' ) : esc_attr_e( 'Default Settings', 'grand-media' ); ?>"
						       readonly/>
						<input type="hidden" name="module_preset_save_default" value="1"/>
					<?php } else { ?>
						<input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr( $term->name ); ?>"
						       placeholder="<?php echo esc_attr( $term->name ? $term->name : __( 'Preset Name', 'grand-media' ) ); ?>"/>
					<?php } ?>
				</div>
				<div class="form-group">
					<label><?php esc_html_e( 'Author', 'grand-media' ); ?></label>
					<?php
					if ( 'global' === $is_preset ) {
						echo '<input type="hidden" name="term[global]" value="0"/>';
						echo '<div>' . esc_html__( 'Global Preset', 'grand-media' ) . '</div>';
					} else {
						$_args = array( 'show_option_all' => '' );
						if ( ! (int) $term->global ) {
							$_args['selected'] = $user_ID;
						}
						gmedia_term_choose_author_field( $term->global, $_args );
					}
					?>
				</div>
				<input type="hidden" name="term[term_id]" value="<?php echo absint( $term_id ); ?>"/>
				<input type="hidden" name="term[module]" value="<?php echo esc_attr( $term->module['name'] ); ?>"/>
				<input type="hidden" name="term[taxonomy]" value="<?php echo esc_attr( $gmedia_term_taxonomy ); ?>"/>
				<?php
				wp_nonce_field( 'GmediaGallery' );
				wp_referer_field();
				?>
				<div class="float-end" id="save_buttons">
					<?php if ( 'global' !== $is_preset ) { ?>
						<button type="submit" name="module_preset_save_global" class="btn btn-secondary btn-sm"><?php esc_html_e( 'Save as Global Preset', 'grand-media' ); ?></button>
						<?php if ( 'default' !== $is_preset ) { ?>
							<button type="submit" name="module_preset_save_default" class="btn btn-secondary btn-sm"><?php esc_html_e( 'Save as Default User Preset', 'grand-media' ); ?></button>
							<?php
						}
					}
					$submit_name = 'module_preset_save';
					if ( 'default' === $is_preset ) {
						$submit_name = 'module_preset_save_default';
					}
					if ( 'global' === $is_preset ) {
						$submit_name = 'module_preset_save_global';
					}
					?>
					<button type="submit" name="<?php echo esc_attr( $submit_name ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Save', 'grand-media' ); ?></button>
				</div>
			</div>

			<div class="col-sm-6">
				<div class="form-group">
					<label><?php esc_html_e( 'Query Args. for Preset Demo', 'grand-media' ); ?></label>
					<textarea class="form-control input-sm" id="build_query_field" style="height:64px;" rows="2"
					          name="term[query]"><?php echo empty( $gmedia_filter['query_args'] ) ? 'limit=20' : esc_html( urldecode( build_query( $gmedia_filter['query_args'] ) ) ); ?></textarea>
					<div class='float-end mt-1'><a id='build_query' class='btn btn-primary btn-xs buildquery-modal' href='#buildQuery' data-bs-toggle='modal' style='font-size:90%;'><?php esc_html_e( 'Build Query', 'grand-media' ); ?></a>
					</div>
				</div>
			</div>
		</div>

		<hr/>
		<?php
		require GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/module-settings.php';
		?>

	</div>

</form>

<?php
require GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/modal-build-query.php';
?>
