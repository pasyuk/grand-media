<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Module List Item
 *
 * @var $module
 */

global $gmGallery, $gmDB, $gmCore, $user_ID, $gm_allowed_tags;
?>
<div class="media<?php echo esc_attr( $module['mclass'] ); ?>">
	<div class="row">
		<div class="col-sm-3">
			<div class="img-thumbnail">
				<img class="media-object" src="<?php echo esc_url( $module['screenshot_url'] ); ?>" alt="<?php echo esc_attr( $module['title'] ); ?>" width="320" height="240"/>
			</div>
		</div>
		<div class="<?php echo( ( 'remote' === $module['place'] ) ? 'col-sm-9' : 'col-sm-5' ); ?>">
			<h4 class="media-heading"><?php echo esc_html( $module['title'] ); ?></h4>

			<p class="version"><?php echo esc_html( __( 'Version', 'grand-media' ) . ': ' . $module['version'] ); ?></p>
			<?php if ( isset( $module['info'] ) ) { ?>
				<div class="module_info"><?php echo wp_kses_post( str_replace( "\n", '<br />', (string) $module['info'] ) ); ?></div>
			<?php } ?>
			<div class="description"><?php echo wp_kses_post( str_replace( "\n", '<br />', (string) $module['description'] ) ); ?></div>
			<hr/>
			<p class="buttons">
				<?php
				$buttons = gmedia_module_action_buttons( $module );
				echo wp_kses( implode( ' ', $buttons ), $gm_allowed_tags );
				?>
			</p>
		</div>
		<?php
		if ( 'remote' !== $module['place'] ) {
			?>
			<div class="col-sm-4">
				<div id="module_presets_list" class="module_presets module_presets_<?php echo esc_attr( $module['name'] ); ?>">
					<h4 class="media-heading" style="margin-bottom:10px;">
						<?php if ( 'free' === $module['status'] || ! empty( $gmGallery->options['license_name'] ) || ! empty( $module['buy'] ) ) { ?>
							<a href="<?php echo esc_url( $gmCore->get_admin_url( array( 'page' => 'GrandMedia_Modules', 'preset_module' => $module['name'] ), array(), admin_url( 'admin.php' ) ) ); ?>" class="addpreset float-end"><span class="badge-success rounded-1">+</span></a>
						<?php } else { ?>
							<a href="https://codeasily.com/gmedia-premium/" title="<?php esc_attr_e( 'Get Premium', 'grand-media' ); ?>" class="addpreset float-end"><span class="badge-success rounded-1">+</span></a>
						<?php } ?>
						<?php esc_html_e( 'Presets', 'grand-media' ); ?></h4>
					<?php
					$presets = $gmDB->get_terms( 'gmedia_module', array( 'status' => $module['name'] ) );
					if ( ! empty( $presets ) ) {
						?>
						<ul class="list-group presetlist">
							<?php
							$li = array();
							foreach ( $presets as $preset ) {
								$href = $gmCore->get_admin_url( array( 'page' => 'GrandMedia_Modules', 'preset' => $preset->term_id ), array(), admin_url( 'admin.php' ) );

								$count         = 1;
								$name          = trim( str_replace( '[' . $module['name'] . ']', '', $preset->name, $count ) );
								$by            = '';
								$global_preset = false;
								if ( ! $name ) {
									if ( (int) $preset->global ) {
										$name = esc_html__( 'Default Settings', 'grand-media' );
									} else {
										$name          = esc_html__( 'Global Settings', 'grand-media' );
										$global_preset = true;
									}
								}
								if ( (int) $preset->global ) {
									$by = ' <small style="white-space:nowrap">[' . esc_html( get_the_author_meta( 'display_name', $preset->global ) ) . ']</small>';
								}
								$li_item = '
                                <li class="list-group-item" id="gm-preset-' . esc_attr( $preset->term_id ) . '">
                                    <span class="gm-preset-id">ID: ' . esc_html( $preset->term_id ) . '</span>';
								if ( $user_ID === $preset->global || $gmCore->caps['gmedia_edit_others_media'] ) {
									$li_item .= '<span class="delpreset"><span class="badge-error rounded-1" data-id="' . esc_attr( $preset->term_id ) . '">&times;</span></span>';
								}
								$li_item .= '
                                    <a href="' . esc_url( $href ) . '">' . $name . $by . '</a>
                                </li>';
								if ( $global_preset ) {
									if ( current_user_can( 'manage_options' ) ) {
										array_unshift( $li, $li_item );
									}
								} else {
									$li[] = $li_item;
								}
							}
							echo wp_kses_post( implode( '', $li ) );
							?>
						</ul>
					<?php } ?>
				</div>
			</div>
			<?php
		}
		?>
	</div>
</div>
