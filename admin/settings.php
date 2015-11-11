<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
	die('You are not allowed to call this page directly.');
}

/**
 * gmSettings()
 *
 * @return mixed content
 */
function gmSettings(){
	global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

	$url = add_query_arg(array('page' => $gmProcessor->page), admin_url('admin.php'));
	$lk = isset($gmGallery->options['license_key'])? $gmGallery->options['license_key'] : '';
	?>

	<form id="gmediaSettingsForm" class="panel panel-default" method="post" action="<?php echo $url; ?>">
		<div class="panel-heading clearfix">
			<div class="btn-toolbar pull-left">
				<div class="btn-group">
					<button type="submit" name="gmedia_settings_reset" class="btn btn-default" data-confirm="<?php _e('Reset all Gmedia settings?', 'grand-media') ?>"><?php _e('Reset Settings', 'grand-media'); ?></button>
					<button type="submit" name="gmedia_settings_save" class="btn btn-primary"><?php _e('Update', 'grand-media'); ?></button>
				</div>
			</div>
			<?php
			wp_nonce_field('GmediaSettings');
			?>
		</div>
		<div class="panel-body" id="gmedia-msg-panel"></div>
		<div class="container-fluid">
			<div class="tabable tabs-left">
				<ul id="settingsTabs" class="nav nav-tabs" style="padding:10px 0;">
					<li class="active"><a href="#gmedia_premium" data-toggle="tab"><?php _e('Premium Settings', 'grand-media'); ?></a></li>
					<li><a href="#gmedia_settings_other" data-toggle="tab"><?php _e('Other Settings', 'grand-media'); ?></a></li>
					<?php if(current_user_can('manage_options')){ ?>
						<li><a href="#gmedia_settings_cloud" data-toggle="tab"><?php _e('GmediaCloud Page', 'grand-media'); ?></a></li>
						<li><a href="#gmedia_settings_roles" data-toggle="tab"><?php _e('Roles/Capabilities Manager', 'grand-media'); ?></a></li>
					<?php } ?>
					<li><a href="#gmedia_settings_sysinfo" data-toggle="tab"><?php _e('System Info', 'grand-media'); ?></a></li>
				</ul>
				<div class="tab-content" style="padding-top:21px;">
					<fieldset id="gmedia_premium" class="tab-pane active">
						<p><?php _e('Enter License Key to remove backlink label from premium gallery modules.') ?></p>

						<div class="row">
							<div class="form-group col-xs-5">
								<label><?php _e('License Key', 'grand-media') ?>: <?php if(isset($gmGallery->options['license_name'])){
										echo '<em>' . $gmGallery->options['license_name'] . '</em>';
									} ?></label>
								<input type="text" name="set[license_key]" id="license_key" class="form-control input-sm" value="<?php echo $lk; ?>"/>
								<div class="manual_license_activate"<?php echo (('manual' == $gmCore->_get('license_activate'))? '' : ' style="display:none;"'); ?>>
									<label style="margin-top:7px;"><?php _e('License Name', 'grand-media') ?>:</label>
									<input type="text" name="set[license_name]" id="license_name" class="form-control input-sm" value="<?php echo $gmGallery->options['license_name']; ?>"/>
									<label style="margin-top:7px;"><?php _e('Additional Key', 'grand-media') ?>:</label>
									<input type="text" name="set[license_key2]" id="license_key2" class="form-control input-sm" value="<?php echo $gmGallery->options['license_key2']; ?>"/>
								</div>
							</div>
							<?php if(!('manual' == $gmCore->_get('license_activate') || !empty($lk))){ ?>
							<div class="form-group col-xs-7">
								<label>&nbsp;</label>
								<button style="display:block;" class="btn btn-success btn-sm" type="submit" name="license-key-activate"><?php _e('Activate Key', 'grand-media'); ?></button>
							</div>
							<?php } ?>
						</div>
					</fieldset>

					<fieldset id="gmedia_settings_other" class="tab-pane">
						<div class="form-group">
							<label><?php _e('When delete (uninstall) plugin', 'grand-media') ?>:</label>
							<select name="set[uninstall_dropdata]" class="form-control input-sm">
								<option value="all" <?php selected($gmGallery->options['uninstall_dropdata'], 'all'); ?>><?php _e('Delete database and all uploaded files', 'grand-media'); ?></option>
								<option value="db" <?php selected($gmGallery->options['uninstall_dropdata'], 'db'); ?>><?php _e('Delete database only and leave uploaded files', 'grand-media'); ?></option>
								<option value="none" <?php selected($gmGallery->options['uninstall_dropdata'], 'none'); ?>><?php _e('Do not delete database and uploaded files', 'grand-media'); ?></option>
							</select>
						</div>
						<div class="form-group row">
							<div class="col-xs-6">
								<label><?php _e('In Tags order gmedia', 'grand-media'); ?></label>
								<select name="set[in_tag_orderby]" class="form-control input-sm">
									<option value="ID" <?php selected($gmGallery->options['in_tag_orderby'], 'ID'); ?>><?php _e('by ID', 'grand-media'); ?></option>
									<option value="title" <?php selected($gmGallery->options['in_tag_orderby'], 'title'); ?>><?php _e('by title', 'grand-media'); ?></option>
									<option value="gmuid" <?php selected($gmGallery->options['in_tag_orderby'], 'gmuid'); ?>><?php _e('by filename', 'grand-media'); ?></option>
									<option value="date" <?php selected($gmGallery->options['in_tag_orderby'], 'date'); ?>><?php _e('by date', 'grand-media'); ?></option>
									<option value="modified" <?php selected($gmGallery->options['in_tag_orderby'], 'modified'); ?>><?php _e('by last modified date', 'grand-media'); ?></option>
									<option value="rand" <?php selected($gmGallery->options['in_tag_orderby'], 'rand'); ?>><?php _e('Random', 'grand-media'); ?></option>
								</select>
							</div>
							<div class="col-xs-6">
								<label><?php _e('Sort order', 'grand-media'); ?></label>
								<select name="set[in_tag_order]" class="form-control input-sm">
									<option value="DESC" <?php selected($gmGallery->options['in_tag_order'], 'DESC'); ?>><?php _e('DESC', 'grand-media'); ?></option>
									<option value="ASC" <?php selected($gmGallery->options['in_tag_order'], 'ASC'); ?>><?php _e('ASC', 'grand-media'); ?></option>
								</select>
							</div>
						</div>
						<div class="form-group row">
							<div class="col-xs-6">
								<label><?php _e('In Category order gmedia', 'grand-media'); ?></label>
								<select name="set[in_category_orderby]" class="form-control input-sm">
									<option value="ID" <?php selected($gmGallery->options['in_category_orderby'], 'ID'); ?>><?php _e('by ID', 'grand-media'); ?></option>
									<option value="title" <?php selected($gmGallery->options['in_category_orderby'], 'title'); ?>><?php _e('by title', 'grand-media'); ?></option>
									<option value="gmuid" <?php selected($gmGallery->options['in_category_orderby'], 'gmuid'); ?>><?php _e('by filename', 'grand-media'); ?></option>
									<option value="date" <?php selected($gmGallery->options['in_category_orderby'], 'date'); ?>><?php _e('by date', 'grand-media'); ?></option>
									<option value="modified" <?php selected($gmGallery->options['in_category_orderby'], 'modified'); ?>><?php _e('by last modified date', 'grand-media'); ?></option>
									<option value="rand" <?php selected($gmGallery->options['in_category_orderby'], 'rand'); ?>><?php _e('Random', 'grand-media'); ?></option>
								</select>
							</div>
							<div class="col-xs-6">
								<label><?php _e('Sort order', 'grand-media'); ?></label>
								<select name="set[in_category_order]" class="form-control input-sm">
									<option value="DESC" <?php selected($gmGallery->options['in_category_order'], 'DESC'); ?>><?php _e('DESC', 'grand-media'); ?></option>
									<option value="ASC" <?php selected($gmGallery->options['in_category_order'], 'ASC'); ?>><?php _e('ASC', 'grand-media'); ?></option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label><?php _e('Forbid other plugins to load their JS and CSS on Gmedia admin pages', 'grand-media') ?>:</label>

							<div class="checkbox" style="margin:0;">
								<input type="hidden" name="set[isolation_mode]" value="0"/>
								<label><input type="checkbox" name="set[isolation_mode]" value="1" <?php checked($gmGallery->options['isolation_mode'], '1'); ?> /> <?php _e('Enable Gmedia admin panel Isolation Mode', 'grand-media'); ?></label>

								<p class="help-block"><?php _e('This option could help to avoid JS and CSS conflicts with other plugins in admin panel.', 'grand-media'); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label><?php _e('Forbid theme to format Gmedia shortcode\'s content', 'grand-media') ?>:</label>

							<div class="checkbox" style="margin:0;">
								<input type="hidden" name="set[shortcode_raw]" value="0"/>
								<label><input type="checkbox" name="set[shortcode_raw]" value="1" <?php checked($gmGallery->options['shortcode_raw'], '1'); ?> /> <?php _e('Raw output for Gmedia Shortcode', 'grand-media'); ?></label>

								<p class="help-block"><?php _e('Some themes reformat shortcodes and break it functionality (mostly when you add description to images). Turning this on should solve this problem.', 'grand-media'); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label><?php _e('Debug Mode', 'grand-media') ?>:</label>

							<div class="checkbox" style="margin:0;">
								<input type="hidden" name="set[debug_mode]" value=""/>
								<label><input type="checkbox" name="set[debug_mode]" value="1" <?php checked($gmGallery->options['debug_mode'], '1'); ?> /> <?php _e('Enable Debug Mode on Gmedia admin pages', 'grand-media'); ?></label>
							</div>
						</div>
						<?php
						$allowed_post_types = (array) $gmGallery->options['gmedia_post_types_support'];
						$args = array(
							'public' => true,
							'_builtin' => false
						);
						$output = 'objects'; // names or objects, note names is the default
						$operator = 'and'; // 'and' or 'or'
						$post_types = get_post_types($args, $output, $operator);
						if(!empty($post_types)){ ?>
							<div class="form-group">
								<label style="margin-bottom:-5px;"><?php _e('Enable Gmedia Library button on custom post types', 'grand-media') ?>:</label>
								<input type="hidden" name="set[gmedia_post_types_support]" value=""/>
								<?php
								foreach($post_types as $post_type){ ?>
									<div class="checkbox">
										<label><input type="checkbox" name="set[gmedia_post_types_support][]" value="<?php echo $post_type->name; ?>" <?php if(in_array($post_type->name, $allowed_post_types)){
												echo 'checked="checked"';
											}; ?> /> <?php echo $post_type->label . ' (' . $post_type->name . ')'; ?></label>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</fieldset>

					<?php if(current_user_can('manage_options')){ ?>
						<fieldset id="gmedia_settings_cloud" class="tab-pane">
							<p><?php _e('GmediaCloud is full window template to show your galleries, albums and other gmedia content', 'grand-media'); ?></p>
							<p><?php _e('Each module can have it\'s own design for GmediaCloud. Here you can set default module wich will be used for sharing Albums, Tags, Categories and single Gmedia Items.', 'grand-media'); ?></p>
							<br/>
							<div class="form-group">
								<label><?php _e('HashID salt for unique template URL', 'grand-media') ?>:</label>
								<input type="text" name="GmediaHashID_salt" value="<?php echo get_option('GmediaHashID_salt'); ?>" class="form-control input-sm" />

								<p class="help-block"><?php _e('Changing this string you\'ll change Gmedia template URLs.', 'grand-media'); ?></p>
							</div>
							<div class="form-group">
								<label><?php _e('Permalink Endpoint (GmediaCloud base)', 'grand-media') ?>:</label>
								<input type="text" name="set[endpoint]" value="<?php echo $gmGallery->options['endpoint']; ?>" class="form-control input-sm" />

								<p class="help-block"><?php _e('Changing endpoint you\'ll change Gmedia template URLs.', 'grand-media'); ?></p>
							</div>
							<?php
							$modules = array();
							if ( ( $plugin_modules = glob( GMEDIA_ABSPATH . 'module/*', GLOB_ONLYDIR | GLOB_NOSORT ) ) ) {
								foreach ( $plugin_modules as $path ) {
									if ( ! file_exists( $path . '/index.php' ) ) {
										continue;
									}
									$module_info = array();
									include( $path . '/index.php' );
									if ( empty( $module_info ) ) {
										continue;
									}
									$mfold             = basename( $path );
									$modules[ $mfold ] = array(
										'module_name'  => $mfold,
										'module_title' => $module_info['title'] . ' v' . $module_info['version'],
										'module_url'   => $gmCore->gmedia_url . "/module/{$mfold}",
										'module_path'  => $path
									);
								}
							}
							if ( ( $upload_modules = glob( $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/*', GLOB_ONLYDIR | GLOB_NOSORT ) ) ) {
								foreach ( $upload_modules as $path ) {
									if ( ! file_exists( $path . '/index.php' ) ) {
										continue;
									}
									$module_info = array();
									include( $path . '/index.php' );
									if ( empty( $module_info ) ) {
										continue;
									}
									$mfold             = basename( $path );
									$modules[ $mfold ] = array(
										'module_name'  => $mfold,
										'module_title' => $module_info['title'] . ' v' . $module_info['version'],
										'module_url'   => $gmCore->upload['url'] . "/{$gmGallery->options['folder']['module']}/{$mfold}",
										'module_path'  => $path
									);
								}
							}
							?>
							<div class="form-group">
								<label><?php _e('Choose module/preset for GmediaCloud Page', 'grand-media') ?>:</label>
								<select class="form-control input-sm" name="set[gmediacloud_module]">
									<option value=""><?php _e('Choose module/preset', 'grand-media'); ?></option>
									<?php foreach ( $modules as $mfold => $module ) {
										echo '<optgroup label="' . esc_attr($module['module_title']) . '">';
										$presets = $gmDB->get_terms( 'gmedia_module', array( 'global' => $user_ID, 'status' => $mfold ) );
										$selected = selected($gmGallery->options['gmediacloud_module'], esc_attr($mfold), false);
										$option = array();
										$option['default'] = '<option '.$selected.' value="' . esc_attr($mfold) . '">' . '[' . $mfold . '] ' . __( 'Default Settings' ) . '</option>';
										foreach ( $presets as $preset ) {
											$selected = selected($gmGallery->options['gmediacloud_module'], $preset->term_id, false);
											if ( '[' . $mfold . ']' == $preset->name ) {
												$option['default'] = '<option '.$selected.' value="' . $preset->term_id . '">' . '[' . $mfold . '] ' . __( 'Default Settings' ) . '</option>';
											} else {
												$option[] = '<option '.$selected.' value="' . $preset->term_id . '">' . $preset->name . '</option>';
											}
										}
										echo implode('', $option);
										echo '</optgroup>';
									} ?>
								</select>

								<p class="help-block"><?php _e('by default will be used Phantom module', 'grand-media'); ?></p>
							</div>
							<div class="form-group">
								<label><?php _e('Top Bar Social Buttons', 'grand-media'); ?></label>
								<select name="set[gmediacloud_socialbuttons]" class="form-control input-sm">
									<option value="1" <?php selected($gmGallery->options['gmediacloud_socialbuttons'], '1'); ?>><?php _e('Show Social Buttons', 'grand-media'); ?></option>
									<option value="0" <?php selected($gmGallery->options['gmediacloud_socialbuttons'], '0'); ?>><?php _e('Hide Social Buttons', 'grand-media'); ?></option>
								</select>
							</div>
							<div class="form-group">
								<label><?php _e('Additional JS code for GmediaCloud Page', 'grand-media') ?>:</label>
								<textarea name="set[gmediacloud_footer_js]" rows="4" cols="20" class="form-control input-sm"><?php echo esc_html(stripslashes($gmGallery->options['gmediacloud_footer_js'])); ?></textarea>
							</div>
							<div class="form-group">
								<label><?php _e('Additional CSS code for GmediaCloud Page', 'grand-media') ?>:</label>
								<textarea name="set[gmediacloud_footer_css]" rows="4" cols="20" class="form-control input-sm"><?php echo esc_html(stripslashes($gmGallery->options['gmediacloud_footer_css'])); ?></textarea>
							</div>
						</fieldset>

						<fieldset id="gmedia_settings_roles" class="tab-pane">
							<p><?php _e('Select the lowest role which should be able to access the follow capabilities. Gmedia Gallery supports the standard roles from WordPress.', 'grand-media'); ?></p>

							<div class="form-group">
								<label><?php _e('Gmedia Library', 'grand-media') ?>:</label>
								<select name="capability[gmedia_library]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_library')); ?></select>

								<p class="help-block"><?php _e('Who can view Gmedia Gallery admin pages', 'grand-media'); ?></p>
							</div>
							<hr/>

							<div class="form-group">
								<label><?php _e('Upload Media Files', 'grand-media') ?>:</label>
								<select name="capability[gmedia_upload]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_upload')); ?></select>

								<p class="help-block"><?php _e('Who can upload files to Gmedia Library', 'grand-media'); ?></p>
							</div>
							<div class="col-xs-offset-1">
								<div class="form-group">
									<label><?php _e('Import Media Files', 'grand-media') ?>:</label>
									<select name="capability[gmedia_import]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_import')); ?></select>

									<p class="help-block"><?php _e('Who can import files to Gmedia Library', 'grand-media'); ?></p>
								</div>
							</div>

							<div class="form-group">
								<label><?php _e('Show Others Media in Library', 'grand-media') ?>:</label>
								<select name="capability[gmedia_show_others_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_show_others_media')); ?></select>

								<p class="help-block"><?php _e('Who can see files uploaded by other users', 'grand-media'); ?></p>
							</div>
							<div class="form-group">
								<label><?php _e('Edit Media', 'grand-media') ?>:</label>
								<select name="capability[gmedia_edit_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_edit_media')); ?></select>

								<p class="help-block"><?php _e('Who can edit media title, description and other properties of uploaded files', 'grand-media'); ?></p>
							</div>
							<div class="col-xs-offset-1">
								<div class="form-group">
									<label><?php _e('Edit Others Media', 'grand-media') ?>:</label>
									<select name="capability[gmedia_edit_others_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_edit_others_media')); ?></select>

									<p class="help-block"><?php _e('Who can edit files, albums/tags and galleries of other users', 'grand-media'); ?></p>
								</div>
							</div>
							<div class="form-group">
								<label><?php _e('Delete Media', 'grand-media') ?>:</label>
								<select name="capability[gmedia_delete_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_delete_media')); ?></select>

								<p class="help-block"><?php _e('Who can delete uploaded files from Gmedia Library', 'grand-media'); ?></p>
							</div>
							<div class="col-xs-offset-1">
								<div class="form-group">
									<label><?php _e('Delete Others Media', 'grand-media') ?>:</label>
									<select name="capability[gmedia_delete_others_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_delete_others_media')); ?></select>

									<p class="help-block"><?php _e('Who can delete files, albums/tags and galleries of other users', 'grand-media'); ?></p>
								</div>
							</div>

							<div class="form-group">
								<label><?php _e('Albums, Tags...', 'grand-media') ?>:</label>
								<select name="capability[gmedia_terms]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_terms')); ?></select>

								<p class="help-block"><?php _e('Who can assign available terms to media files', 'grand-media'); ?></p>
							</div>
							<div class="col-xs-offset-1">
								<div class="form-group">
									<label><?php _e('Manage Albums', 'grand-media') ?>:</label>
									<select name="capability[gmedia_album_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_album_manage')); ?></select>

									<p class="help-block"><?php _e('Who can create and edit own albums. It is required "Edit Others Media" capability to edit others and shared albums', 'grand-media'); ?></p>
								</div>
								<div class="form-group">
									<label><?php _e('Manage Filters', 'grand-media') ?>:</label>
									<select name="capability[gmedia_filter_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_filter_manage')); ?></select>

									<p class="help-block"><?php _e('Who can create and edit own custom filters. It is required "Edit Others Media" capability to edit filters you do not own', 'grand-media'); ?></p>
								</div>
								<div class="form-group">
									<label><?php _e('Manage Tags', 'grand-media') ?>:</label>
									<select name="capability[gmedia_tag_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_tag_manage')); ?></select>

									<p class="help-block"><?php _e('Who can create new tags. It is required "Edit Others Media" capability to edit tags', 'grand-media'); ?></p>
								</div>
								<div class="form-group">
									<label><?php _e('Delete Terms', 'grand-media') ?>:</label>
									<select name="capability[gmedia_terms_delete]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_terms_delete')); ?></select>

									<p class="help-block"><?php _e('Who can delete own albums. It is required "Delete Others Media" capability to delete others terms', 'grand-media'); ?></p>
								</div>
							</div>

							<div class="form-group">
								<label><?php _e('Galleries', 'grand-media') ?>:</label>
								<select name="capability[gmedia_gallery_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_gallery_manage')); ?></select>

								<p class="help-block"><?php _e('Who can create, edit and delete own galleries', 'grand-media'); ?></p>
							</div>

							<div class="form-group">
								<label><?php _e('Modules', 'grand-media') ?>:</label>
								<select name="capability[gmedia_module_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_module_manage')); ?></select>

								<p class="help-block"><?php _e('Who can manage modules', 'grand-media'); ?></p>
							</div>

							<div class="form-group">
								<label><?php _e('Settings', 'grand-media') ?>:</label>
								<select name="capability[gmedia_settings]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_settings')); ?></select>

								<p class="help-block"><?php _e('Who can change settings. Note: Capabilites can be changed only by administrator', 'grand-media'); ?></p>
							</div>

						</fieldset>
					<?php } ?>

					<fieldset id="gmedia_settings_sysinfo" class="tab-pane">
						<?php
						if((function_exists('memory_get_usage')) && (ini_get('memory_limit'))){
							$memory_limit = ini_get('memory_limit');
							$memory_usage = round( memory_get_usage() / (1024 * 1024), 1 );
							echo '<p>' . __('PHP Memory Limit: ', 'grand-media') . $memory_limit . '</p>';
							echo '<p>' . __('PHP Memory Used: ', 'grand-media') . $memory_usage . 'M</p>';
						}
						?>
						<p><?php _e('Under constraction...') ?></p>

						<?php
						if($gmCore->_get('showdb')){
							global $wpdb;
							$gmedia = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia");
							$terms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia_term");
							$relation = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia_term_relationships");
							$images['grand-media'] = glob($gmCore->upload['path'] . '/*', GLOB_NOSORT);
							$images['images'] = glob($gmCore->upload['path'] . '/image/*', GLOB_NOSORT);
							$images['thumbs'] = glob($gmCore->upload['path'] . '/thumb/*', GLOB_NOSORT);
							echo '<pre style="max-height:400px; overflow:auto;">' . print_r($gmedia, true) . '</pre>';
							echo '<pre style="max-height:400px; overflow:auto;">' . print_r($images, true) . '</pre>';
							echo '<pre style="max-height:400px; overflow:auto;">' . print_r($terms, true) . '</pre>';
							echo '<pre style="max-height:400px; overflow:auto;">' . print_r($relation, true) . '</pre>';
						}
						?>
					</fieldset>
				</div>
				<div class="clear"></div>
			</div>
			<script type="text/javascript">
				jQuery(function($){
					var hash = window.location.hash;
					if(hash){
						hash = hash.replace('_tab', '');
						$('#settingsTabs a[href="'+hash+'"]').tab('show');
					}
					$('#gmediaSettingsForm').on('submit', function(){
						$(this).attr('action', $(this).attr('action') + $('#settingsTabs li.active a').attr('href') + '_tab');
					});
				});
			</script>
		</div>
	</form>
<?php
}