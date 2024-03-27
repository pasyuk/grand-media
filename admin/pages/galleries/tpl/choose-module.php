<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * @var $gmedia_modules
 * @var $gmedia_url
 */

global $gmCore, $gmDB, $gmGallery, $gm_allowed_tags;
?>
<div class="modal fade gmedia-modal" id="chooseModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><?php esc_html_e( 'Choose Module for Gallery', 'grand-media' ); ?></h4>
				<button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body linkblock">
				<?php
				if ( ! empty( $gmedia_modules['in'] ) ) {
					foreach ( $gmedia_modules['in'] as $_m ) {
						/**
						 * @var $module_name
						 * @var $module_url
						 * @var $module_path
						 */
						extract( $_m );
						if ( ! is_file( $module_path . '/index.php' ) ) {
							continue;
						}
						$module_info = array();
						/** @noinspection PhpIncludeInspection */
						include $module_path . '/index.php';
						if ( empty( $module_info ) ) {
							continue;
						}
						$mclass = ' module-' . $module_info['type'] . ' module-' . $module_info['status'];
						?>
						<div class="choose-module media<?php echo esc_attr( $mclass ); ?>">
							<a class="img-thumbnail float-start" role="button" data-bs-toggle="collapse" href="#collapseDescr_<?php echo esc_attr( $module_name ); ?>" aria-expanded="false" aria-controls="collapseDescr_<?php echo esc_attr( $module_name ); ?>">
								<img class="media-object" src="<?php echo esc_url( $module_url . '/screenshot.png' ); ?>" alt="<?php echo esc_attr( $module_info['title'] ); ?>" width="100"/>
							</a>

							<div class="media-body" style="margin-left:130px;">
								<h4 class="media-heading"><?php echo esc_html( $module_info['title'] ); ?></h4>
								<p class="version" style="margin: 6px 0;"><?php echo esc_html( __( 'Version', 'grand-media' ) . ': ' . $module_info['version'] ); ?></p>
								<div class="description collapse" id="collapseDescr_<?php echo esc_attr( $module_name ); ?>"><?php echo wp_kses_post( nl2br( $module_info['description'] ) ); ?></div>
								<div class="action-buttons text-end">
									<a href="<?php echo esc_url( $gmCore->get_admin_url( array( 'page' => 'GrandMedia_Modules', 'preset_module' => $module_name ), array(), admin_url( 'admin.php' ) ) ); ?>" class="btn btn-sm btn-secondary"><?php esc_html_e( 'Create Preset', 'grand-media' ); ?></a>
									&nbsp;&nbsp;&nbsp;
									<a href="<?php echo esc_url( add_query_arg( array( 'gallery_module' => $module_name ), $gmedia_url ) ); ?>" class="btn btn-sm btn-primary"><?php esc_html_e( 'Create Gallery', 'grand-media' ); ?></a>
								</div>
							</div>
						</div>
						<?php
					}
				} else {
					esc_html_e( 'No installed modules', 'grand-media' );
				}
				?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'grand-media' ); ?></button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade gmedia-modal" id="changeModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<form class="modal-content" autocomplete="off" method="post" action="<?php echo esc_url( $gmCore->get_admin_url( array(), array(), $gmedia_url ) ); ?>">
			<div class="modal-header">
				<h4 class="modal-title"><?php esc_html_e( 'Change Module/Preset for Galleries', 'grand-media' ); ?></h4>
				<button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<?php
				if ( ! empty( $gmedia_modules['in'] ) ) {
					?>
					<div class="form-group">
						<label><?php esc_html_e( 'Change Module/Preset for Galleries', 'grand-media' ); ?>:</label>
						<select class="form-control input-sm" name="gmedia_gallery_module">
							<?php
							echo '<option value="">' . esc_html__( 'Choose Module/Preset' ) . '</option>';
							foreach ( $gmedia_modules['in'] as $mfold => $module ) {
								echo '<optgroup label="' . esc_attr( $module['title'] ) . '">';
								$presets  = $gmDB->get_terms( 'gmedia_module', array( 'status' => $mfold ) );
								$option   = array();
								$option[] = '<option value="' . esc_attr( $mfold ) . '">' . esc_html( $module['title'] . ' - ' . __( 'Default Settings' ) ) . '</option>';
								foreach ( $presets as $preset ) {
									if ( ! (int) $preset->global && '[' . $mfold . ']' === $preset->name ) {
										continue;
									}
									$by_author = '';
									if ( (int) $preset->global ) {
										$by_author = ' [' . get_the_author_meta( 'display_name', $preset->global ) . ']';
									}
									if ( '[' . $mfold . ']' === $preset->name ) {
										$option[] = '<option value="' . intval( $preset->term_id ) . '">' . esc_html( $module['title'] . $by_author . ' - ' . __( 'Default Settings' ) ) . '</option>';
									} else {
										$preset_name = str_replace( '[' . $mfold . '] ', '', $preset->name );
										$option[]    = '<option value="' . intval( $preset->term_id ) . '">' . esc_html( $module['title'] . $by_author . ' - ' . $preset_name ) . '</option>';
									}
								}
								echo wp_kses( implode( '', $option ), $gm_allowed_tags );
								echo '</optgroup>';
							}
							?>
						</select>

						<p class="help-block"><?php esc_html_e( 'Chosen module will be applied for selected galleries.', 'grand-media' ); ?></p>
					</div>
					<?php
					wp_nonce_field( 'gmedia_gallery_module', '_wpnonce_gallery_module' );
					wp_referer_field();
				} else {
					esc_html_e( 'No installed modules', 'grand-media' );
				}
				?>
			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Apply', 'grand-media' ); ?></button>
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'grand-media' ); ?></button>
			</div>
		</form>
	</div>
</div>
