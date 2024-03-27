<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Edit Gallery Form
 *
 * @var $term_id
 * @var $term
 * @var $gmedia_url
 * @var $gmedia_term_taxonomy
 * @var $gmCore
 * @var $default_options
 * @var $gallery_settings
 * @var $user_ID
 */
?>
<form method="post" id="gmedia-edit-term" name="gmEditTerm" data-id="<?php echo absint( $term_id ); ?>" action="<?php echo esc_url( $gmedia_url ); ?>">
	<div class="card-body">
		<?php
		/*
		?>
		<h4 style="margin-top:0;">
			<?php if($term_id) { ?>
				<span class="float-end"><?php echo esc_html( __('ID', 'grand-media') . ": {$term->term_id}" ); ?></span>
				<?php esc_html_e('Edit Gallery', 'grand-media'); ?>: <em><?php echo esc_html($term->name); ?></em>
			<?php
			} else {
				esc_html_e('New Gallery');
			}
			?>
			</h4>
		<?php
		*/
		?>

		<div class="row">
			<div class="col-md-8">
				<div class="row">
					<div class="col-sm-6">
						<div class="form-group">
							<label><?php esc_html_e( 'Name', 'grand-media' ); ?></label>
							<input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr( $term->name ); ?>" placeholder="<?php esc_attr_e( 'Gallery Name', 'grand-media' ); ?>" required/>
						</div>
						<div class="form-group">
							<label><?php esc_html_e( 'Slug', 'grand-media' ); ?></label>
							<input type="text" class="form-control input-sm" name="term[slug]" value="<?php echo esc_attr( $term->slug ); ?>"/>
						</div>
						<div class="form-group">
							<label><?php esc_html_e( 'Description', 'grand-media' ); ?></label>
							<?php
							wp_editor(
								$term->description,
								"gallery{$term->term_id}_description",
								array(
									'editor_class'  => 'form-control input-sm',
									'editor_height' => 120,
									'wpautop'       => false,
									'media_buttons' => false,
									'textarea_name' => 'term[description]',
									'textarea_rows' => '4',
									'tinymce'       => false,
									'quicktags'     => array( 'buttons' => apply_filters( 'gmedia_editor_quicktags', 'strong,em,link,ul,li,close' ) ),
								)
							);
							?>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="form-group">
							<label><?php esc_html_e( 'Author', 'grand-media' ); ?></label>
							<?php gmedia_term_choose_author_field( $term->global ); ?>
						</div>
						<div class="form-group">
							<label><?php esc_html_e( 'Status', 'grand-media' ); ?></label>
							<select name="term[status]" class="form-control input-sm">
								<option value="publish"<?php selected( $term->status, 'publish' ); ?>><?php esc_html_e( 'Public', 'grand-media' ); ?></option>
								<option value="private"<?php selected( $term->status, 'private' ); ?>><?php esc_html_e( 'Private', 'grand-media' ); ?></option>
								<option value="draft"<?php selected( $term->status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'grand-media' ); ?></option>
							</select>
						</div>
						<div class="form-group">
							<label><?php esc_html_e( 'Query Args.', 'grand-media' ); ?></label>
							<textarea
								class="form-control input-sm"
								id="build_query_field"
								style="height:120px;"
								title="<?php esc_attr_e( "Click 'Build Query' button and choose query arguments for this gallery" ); ?>"
								placeholder="<?php esc_attr_e( "Click 'Build Query' button for help with Query Args.\nIf you leave this field empty then whole Library will be loaded. That's could exceed your server's PHP Memory Limit.", 'grand-media' ); ?>"
								rows="2"
								name="term[query]"
							><?php echo empty( $gmedia_filter['query_args'] ) ? '' : esc_html( urldecode( build_query( $gmedia_filter['query_args'] ) ) ); ?></textarea>
							<p class="help-block text-end pt-3"><a id="build_query" class="btn btn-sm btn-success buildquery-modal" data-bs-toggle="modal" href="#buildQuery" style="font-size:90%;"><?php esc_html_e( 'Build Query', 'grand-media' ); ?></a></p>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="form-group">
					<label>&nbsp;
						<input type="hidden" name="term[term_id]" value="<?php echo absint( $term_id ); ?>"/>
						<input type="hidden" name="term[taxonomy]" value="<?php echo esc_attr( $gmedia_term_taxonomy ); ?>"/>
						<?php
						wp_nonce_field( 'GmediaGallery' );
						wp_referer_field();
						?>
					</label>
					<div>
						<div class="btn-group btn-group" id="save_buttons">
							<?php if ( $term->module['name'] !== $term->meta['_module'] ) { ?>
								<a href="<?php echo esc_url( $gmedia_url ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Cancel preview module', 'grand-media' ); ?></a>
								<button type="submit" name="gmedia_gallery_save" class="btn btn-primary"><?php esc_html_e( 'Save with new module', 'grand-media' ); ?></button>
							<?php } else { ?>
								<?php
								$reset_settings = $gmCore->array_diff_keyval_recursive( $default_options, $gallery_settings, true );
								if ( ! empty( $reset_settings ) ) {
									?>
									<button type="submit" name="gmedia_gallery_reset" class="btn btn-secondary" data-confirm="<?php esc_attr_e( 'Confirm reset module settings to default preset' ); ?>"><?php esc_html_e( 'Reset to default', 'grand-media' ); ?></button>
								<?php } ?>
								<button type="submit" name="gmedia_gallery_save" class="btn btn-primary"><?php esc_html_e( 'Save', 'grand-media' ); ?></button>
							<?php } ?>
						</div>
					</div>
				</div>

				<p><b><?php esc_html_e( 'Gallery ID:' ); ?></b> #<?php echo intval( $term_id ); ?></p>
				<p><b><?php esc_html_e( 'Last edited:' ); ?></b> <?php echo esc_html( $term->meta['_edited'] ); ?></p>
				<p>
					<?php
					echo '<b>' . esc_html__( 'Gallery module:' ) . '</b> <a href="#chooseModuleModal" data-bs-toggle="modal" title="' . esc_attr__( 'Change module for gallery', 'grand-media' ) . '">' . esc_html( $term->meta['_module'] ) . '</a>';
					if ( $term->module['name'] !== $term->meta['_module'] ) {
						echo '<br /><b>' . esc_html__( 'Preview module:' ) . '</b> ' . esc_html( $term->module['name'] );
						// translators: module name.
						echo '<br /><span class="text-danger">' . sprintf( esc_html__( 'Note: Module changed to %s, but not saved yet' ), esc_html( $term->module['name'] ) ) . '</span>';
					}
					?>
				</p>
				<input type="hidden" name="term[module]" value="<?php echo esc_attr( $term->module['name'] ); ?>">
				<?php
				if ( $term_id ) {
					$params = array();
					if ( $term->module['name'] !== $term->meta['_module'] ) {
						$params['gmedia_module'] = $term->module['name'];
					}
					$params['iframe'] = 1;
					?>
					<p><b><?php esc_html_e( 'GmediaCloud page URL for current gallery:' ); ?></b>
						<br/><a target="_blank" href="<?php echo esc_url( $term->cloud_link ); ?>"><?php echo esc_html( $term->cloud_link ); ?></a>
					</p>
					<?php if ( $term->post_link ) { ?>
						<p><b><?php esc_html_e( 'Gmedia Post URL for current gallery:' ); ?></b>
							<br/><a target="_blank" href="<?php echo esc_url( $term->post_link ); ?>"><?php echo esc_html( $term->post_link ); ?></a>
						</p>
					<?php } ?>
					<div class="help-block text-secondary small">
						<?php echo wp_kses_post( __( 'update <a href="options-permalink.php">Permalink Settings</a> if above link not working', 'grand-media' ) ); ?>
						<?php
						if ( current_user_can( 'manage_options' ) ) {
							echo '<br>' . wp_kses_post( __( 'More info about GmediaCloud Pages and GmediaCloud Settings can be found <a href="admin.php?page=GrandMedia_Settings#gmedia_settings_cloud">here</a>', 'grand-media' ) );
						}
						?>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
		$gmCore->gmedia_custom_meta_box( $term->term_id, $meta_type = 'gmedia_term' );
		do_action( 'gmedia_term_edit_form' );
		?>

		<hr/>
		<div class="align-items-center bg-gradient bg-light border d-flex justify-content-between p-2 mb-4 rounded">
			<h5 class="m-0"><?php esc_html_e( 'Module Settings', 'grand-media' ); ?></h5>

			<div class="btn-toolbar gap-4 float-end" id="module_preset">
				<div class="btn-group">
					<button type="button" class="btn btn-secondary<?php echo ( $term->module['name'] !== $term->meta['_module'] ) ? ' disabled' : ''; ?>" id="module_presets"><?php esc_html_e( 'Module Presets', 'grand-media' ); ?></button>
				</div>
				<script type="text/html" id="_module_presets">
					<div style="padding-top: 5px;">
						<?php if ( current_user_can( 'manage_options' ) ) { ?>
							<p>
								<button type="button" name="module_preset_save_global" class="ajax-submit btn btn-secondary btn-xs" style="width:100%"><?php esc_html_e( 'Save as Global Preset', 'grand-media' ); ?></button>
							</p>
						<?php } ?>
						<p style="white-space: nowrap">
							<button type="button" name="module_preset_save_default" class="ajax-submit btn btn-secondary btn-xs"><?php esc_html_e( 'Save as Default', 'grand-media' ); ?></button>
							&nbsp; <em><?php esc_html_e( 'or', 'grand-media' ); ?></em> &nbsp;
							<?php if ( ! empty( $default_preset ) ) { ?>
								<button type="button" name="module_preset_restore_original" class="ajax-submit btn btn-secondary btn-xs"><?php esc_html_e( 'Restore Original', 'grand-media' ); ?></button>
								<input type="hidden" name="preset_default" value="<?php echo absint( $default_preset['term_id'] ); ?>"/>
							<?php } ?>
						</p>
						<div class="form-group clearfix" style="border-top: 1px solid #444444; padding-top: 5px;">
							<label><?php esc_html_e( 'Save Preset as:', 'grand-media' ); ?></label>

							<div class="input-group input-group-xs">
								<input type="text" class="form-control form-control-sm" name="module_preset_name" placeholder="<?php esc_attr_e( 'Preset Name', 'grand-media' ); ?>" value=""/>
								<span class="input-group-btn"><button type="button" name="module_preset_save" class="ajax-submit btn btn-primary h-100"><?php esc_html_e( 'Save', 'grand-media' ); ?></button></span>
							</div>
						</div>

						<?php if ( ! empty( $presets ) ) { ?>
							<ul class="list-group presetlist">
								<?php
								$li = array();
								foreach ( $presets as $preset ) {
									$href = $gmCore->get_admin_url( array( 'preset' => $preset->term_id ), array() );

									$count = 1;
									$name  = trim( str_replace( '[' . $term->module['name'] . ']', '', $preset->name, $count ) );
									$by    = '';
									if ( ! $name ) {
										if ( ! (int) $preset->global ) {
											continue;
										}
										$name = __( 'Default Settings', 'grand-media' );
									}
									if ( (int) $preset->global ) {
										$by = ' <small style="white-space:nowrap">[' . esc_html( get_the_author_meta( 'display_name', $preset->global ) ) . ']</small>';
									}
									$li_item = '
                                        <li class="list-group-item" id="gm-preset-' . intval( $preset->term_id ) . '">';
									if ( $user_ID === $preset->global || ( (int) $preset->global && $gmCore->caps['gmedia_edit_others_media'] ) || current_user_can( 'manage_options' ) ) {
										$li_item .= '<span class="delpreset"><span class="badge-error rounded-1" data-id="' . intval( $preset->term_id ) . '">&times;</span></span>';
									}
									$li_item .= '
                                            <a href="' . esc_url( $href ) . '">' . esc_html( $name ) . $by . '</a>
                                        </li>';

									$li[] = $li_item;
								}
								echo wp_kses_post( implode( '', $li ) );
								?>
							</ul>
						<?php } ?>
					</div>
				</script>
			</div>
		</div>
		<?php
		$gallery_link_default = $gmCore->gmcloudlink( $term->term_id, $term->taxterm, true );
		require GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/module-settings.php';
		?>
		<?php if ( ! empty( $alert ) ) { ?>
			<script type="text/javascript">
							jQuery(function($) {
								$('#chooseModuleModal').modal('show');
							});
			</script>
		<?php } ?>
	</div>

</form>

<?php

require GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/modal-build-query.php';

if ( $term_id ) {
	$customfield_meta_type = 'gmedia_term';
	include GMEDIA_ABSPATH . 'admin/tpl/modal-customfield.php';
}

?>

<?php if ( gm_user_can( 'edit_others_media' ) ) { ?>
	<div class="modal fade gmedia-modal" id="gallModal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog"></div>
	</div>
<?php } ?>
