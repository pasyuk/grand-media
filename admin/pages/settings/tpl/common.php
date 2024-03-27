<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Common Settings
 *
 * @var $gmGallery
 * @var $gmDB
 */
?>
<fieldset id="gmedia_settings_other" class="tab-pane">
	<div class="form-group">
		<label><?php esc_html_e( 'When delete (uninstall) plugin', 'grand-media' ); ?>:</label>
		<select name="set[uninstall_dropdata]" class="form-control input-sm">
			<option value="all" <?php selected( $gmGallery->options['uninstall_dropdata'], 'all' ); ?>><?php esc_html_e( 'Delete database and all uploaded files', 'grand-media' ); ?></option>
			<option value="db" <?php selected( $gmGallery->options['uninstall_dropdata'], 'db' ); ?>><?php esc_html_e( 'Delete database only and leave uploaded files', 'grand-media' ); ?></option>
			<option value="none" <?php selected( $gmGallery->options['uninstall_dropdata'], 'none' ); ?>><?php esc_html_e( 'Do not delete database and uploaded files', 'grand-media' ); ?></option>
		</select>
	</div>

	<hr/>
	<div class="form-group">
		<label><?php esc_html_e( 'Google API Key', 'grand-media' ); ?></label>
		<div class="row">
			<div class="col-sm-6">
				<input type="text" name="set[google_api_key]" class="form-control input-sm" value="<?php echo esc_attr( $gmGallery->options['google_api_key'] ); ?>">
			</div>
			<div class="col-sm-6">
				<p style="padding-top: 5px"><a target="_blank" href="https://support.google.com/googleapi/answer/6158862"><?php esc_html_e( 'How to create a Google API Key', 'grand-media' ); ?></a></p>
			</div>
		</div>
		<p class="help-block"><?php esc_html_e( 'This API key is required to using Google Map in admin and in the gallery modules.', 'grand-media' ); ?></p>
	</div>

	<hr/>
	<div class="form-group">
		<label><?php esc_html_e( 'Cache expiration', 'grand-media' ); ?></label>
		<div class="row">
			<div class="col-sm-6">
				<input type="number" name="set[cache_expiration]" class="form-control input-sm" value="<?php echo absint( $gmGallery->options['cache_expiration'] ); ?>">
			</div>
		</div>
		<p class="help-block"><?php esc_html_e( 'Set 0 to disable cache for Gmedia Modules. Cache also reset automatically every time you save or update item in Gmedia Library or update any term or gallery.', 'grand-media' ); ?></p>
	</div>

	<hr/>
	<div class="form-group row">
		<div class="col-sm-6">
			<label><?php esc_html_e( 'In Tags order gmedia', 'grand-media' ); ?></label>
			<select name="set[in_tag_orderby]" class="form-control input-sm">
				<option value="ID" <?php selected( $gmGallery->options['in_tag_orderby'], 'ID' ); ?>><?php esc_html_e( 'by ID', 'grand-media' ); ?></option>
				<option value="title" <?php selected( $gmGallery->options['in_tag_orderby'], 'title' ); ?>><?php esc_html_e( 'by title', 'grand-media' ); ?></option>
				<option value="gmuid" <?php selected( $gmGallery->options['in_tag_orderby'], 'gmuid' ); ?>><?php esc_html_e( 'by filename', 'grand-media' ); ?></option>
				<option value="date" <?php selected( $gmGallery->options['in_tag_orderby'], 'date' ); ?>><?php esc_html_e( 'by date', 'grand-media' ); ?></option>
				<option value="modified" <?php selected( $gmGallery->options['in_tag_orderby'], 'modified' ); ?>><?php esc_html_e( 'by last modified date', 'grand-media' ); ?></option>
				<option value="comment_count" <?php selected( $gmGallery->options['in_tag_orderby'], 'comment_count' ); ?>><?php esc_html_e( 'by comment count', 'grand-media' ); ?></option>
				<option value="rand" <?php selected( $gmGallery->options['in_tag_orderby'], 'rand' ); ?>><?php esc_html_e( 'Random', 'grand-media' ); ?></option>
			</select>
		</div>
		<div class="col-sm-6">
			<label><?php esc_html_e( 'Sort order', 'grand-media' ); ?></label>
			<select name="set[in_tag_order]" class="form-control input-sm">
				<option value="DESC" <?php selected( $gmGallery->options['in_tag_order'], 'DESC' ); ?>><?php esc_html_e( 'DESC', 'grand-media' ); ?></option>
				<option value="ASC" <?php selected( $gmGallery->options['in_tag_order'], 'ASC' ); ?>><?php esc_html_e( 'ASC', 'grand-media' ); ?></option>
			</select>
		</div>
	</div>

	<hr/>
	<div class="form-group">
		<div class="row">
			<div class="col-sm-6">
				<label><?php esc_html_e( 'In Category order gmedia (set default order)', 'grand-media' ); ?></label>
				<select name="set[in_category_orderby]" class="form-control input-sm">
					<option value="ID" <?php selected( $gmGallery->options['in_category_orderby'], 'ID' ); ?>><?php esc_html_e( 'by ID', 'grand-media' ); ?></option>
					<option value="title" <?php selected( $gmGallery->options['in_category_orderby'], 'title' ); ?>><?php esc_html_e( 'by title', 'grand-media' ); ?></option>
					<option value="gmuid" <?php selected( $gmGallery->options['in_category_orderby'], 'gmuid' ); ?>><?php esc_html_e( 'by filename', 'grand-media' ); ?></option>
					<option value="date" <?php selected( $gmGallery->options['in_category_orderby'], 'date' ); ?>><?php esc_html_e( 'by date', 'grand-media' ); ?></option>
					<option value="modified" <?php selected( $gmGallery->options['in_category_orderby'], 'modified' ); ?>><?php esc_html_e( 'by last modified date', 'grand-media' ); ?></option>
					<option value="comment_count" <?php selected( $gmGallery->options['in_category_orderby'], 'comment_count' ); ?>><?php esc_html_e( 'by comment count', 'grand-media' ); ?></option>
					<option value="rand" <?php selected( $gmGallery->options['in_category_orderby'], 'rand' ); ?>><?php esc_html_e( 'Random', 'grand-media' ); ?></option>
				</select>
			</div>
			<div class="col-sm-6">
				<label><?php esc_html_e( 'Sort order', 'grand-media' ); ?></label>
				<select name="set[in_category_order]" class="form-control input-sm">
					<option value="DESC" <?php selected( $gmGallery->options['in_category_order'], 'DESC' ); ?>><?php esc_html_e( 'DESC', 'grand-media' ); ?></option>
					<option value="ASC" <?php selected( $gmGallery->options['in_category_order'], 'ASC' ); ?>><?php esc_html_e( 'ASC', 'grand-media' ); ?></option>
				</select>
			</div>
		</div>
		<p class="help-block"><?php esc_html_e( 'This option could be rewritten by individual category settings.', 'grand-media' ); ?></p>
	</div>

	<hr/>
	<div class="form-group">
		<div class="row">
			<div class="col-sm-6">
				<label><?php esc_html_e( 'In Album order gmedia (set default order)', 'grand-media' ); ?></label>
				<select name="set[in_album_orderby]" class="form-control input-sm">
					<option value="ID" <?php selected( $gmGallery->options['in_album_orderby'], 'ID' ); ?>><?php esc_html_e( 'by ID', 'grand-media' ); ?></option>
					<option value="title" <?php selected( $gmGallery->options['in_album_orderby'], 'title' ); ?>><?php esc_html_e( 'by title', 'grand-media' ); ?></option>
					<option value="gmuid" <?php selected( $gmGallery->options['in_album_orderby'], 'gmuid' ); ?>><?php esc_html_e( 'by filename', 'grand-media' ); ?></option>
					<option value="date" <?php selected( $gmGallery->options['in_album_orderby'], 'date' ); ?>><?php esc_html_e( 'by date', 'grand-media' ); ?></option>
					<option value="modified" <?php selected( $gmGallery->options['in_album_orderby'], 'modified' ); ?>><?php esc_html_e( 'by last modified date', 'grand-media' ); ?></option>
					<option value="comment_count" <?php selected( $gmGallery->options['in_album_orderby'], 'comment_count' ); ?>><?php esc_html_e( 'by comment count', 'grand-media' ); ?></option>
					<option value="rand" <?php selected( $gmGallery->options['in_album_orderby'], 'rand' ); ?>><?php esc_html_e( 'Random', 'grand-media' ); ?></option>
				</select>
			</div>
			<div class="col-sm-6">
				<label><?php esc_html_e( 'Sort order', 'grand-media' ); ?></label>
				<select name="set[in_album_order]" class="form-control input-sm">
					<option value="DESC" <?php selected( $gmGallery->options['in_album_order'], 'DESC' ); ?>><?php esc_html_e( 'DESC', 'grand-media' ); ?></option>
					<option value="ASC" <?php selected( $gmGallery->options['in_album_order'], 'ASC' ); ?>><?php esc_html_e( 'ASC', 'grand-media' ); ?></option>
				</select>
			</div>
		</div>
		<p class="help-block"><?php esc_html_e( 'This option could be rewritten by individual category settings.', 'grand-media' ); ?></p>
	</div>
	<div class="form-group">
		<label><?php esc_html_e( 'Set default Album status', 'grand-media' ); ?></label>
		<select name="set[in_album_status]" class="form-control input-sm">
			<option value="publish" <?php selected( $gmGallery->options['in_album_status'], 'publish' ); ?>><?php esc_html_e( 'Public', 'grand-media' ); ?></option>
			<option value="private" <?php selected( $gmGallery->options['in_album_status'], 'private' ); ?>><?php esc_html_e( 'Private', 'grand-media' ); ?></option>
			<option value="draft" <?php selected( $gmGallery->options['in_album_status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'grand-media' ); ?></option>
		</select>
	</div>

	<hr/>
	<?php $gmedia_modules = get_gmedia_modules( false ); ?>
	<div class="form-group">
		<label><?php esc_html_e( 'Choose default module', 'grand-media' ); ?>:</label>
		<select class="form-control input-sm" name="set[default_gmedia_module]">
			<?php
			foreach ( $gmedia_modules['in'] as $mfold => $module ) {
				echo '<optgroup label="' . esc_attr( $module['title'] ) . '">';
				$presets  = $gmDB->get_terms( 'gmedia_module', array( 'status' => $mfold ) );
				$selected = selected( $gmGallery->options['default_gmedia_module'], esc_attr( $mfold ), false );
				$option   = array();
				$option[] = '<option ' . $selected . ' value="' . esc_attr( $mfold ) . '">' . esc_html( $module['title'] . ' - ' . __( 'Default Settings' ) ) . '</option>';
				foreach ( $presets as $preset ) {
					if ( ! (int) $preset->global && '[' . $mfold . ']' === $preset->name ) {
						continue;
					}
					$selected  = selected( $gmGallery->options['default_gmedia_module'], $preset->term_id, false );
					$by_author = '';
					if ( (int) $preset->global ) {
						$by_author = ' [' . get_the_author_meta( 'display_name', $preset->global ) . ']';
					}
					if ( '[' . $mfold . ']' === $preset->name ) {
						$option[] = '<option ' . $selected . ' value="' . esc_attr( $preset->term_id ) . '">' . esc_html( $module['title'] . $by_author . ' - ' . __( 'Default Settings' ) ) . '</option>';
					} else {
						$preset_name = str_replace( '[' . $mfold . '] ', '', $preset->name );
						$option[]    = '<option ' . $selected . ' value="' . esc_attr( $preset->term_id ) . '">' . esc_html( $module['title'] . $by_author . ' - ' . $preset_name ) . '</option>';
					}
				}
				echo wp_kses( implode( '', $option ), $gm_allowed_tags );
				echo '</optgroup>';
			}
			?>
		</select>

		<p class="help-block"><?php esc_html_e( 'Chosen module will be used for terms pages.', 'grand-media' ); ?></p>
	</div>

	<hr/>
	<div class="form-group">
		<label><?php esc_html_e( 'Notifications', 'grand-media' ); ?></label>
		<div class="checkbox" style="margin:0;">
			<input type="hidden" name="set[notify_new_modules]" value="0"/>
			<label><input type="checkbox" name="set[notify_new_modules]" value="1" <?php checked( $gmGallery->options['notify_new_modules'], '1' ); ?> /> <?php esc_html_e( 'Show notification label about new modules (green conter)', 'grand-media' ); ?> </label>
		</div>
	</div>

	<hr/>
	<div class="form-group">
		<label><?php esc_html_e( 'Choose what to show on Author Profile pages', 'grand-media' ); ?></label>
		<div class="checkbox" style="margin:0;">
			<input type="hidden" name="set[wp_author_related_gmedia]" value="0"/>
			<label><input type="checkbox" name="set[wp_author_related_gmedia]" value="1" <?php checked( $gmGallery->options['wp_author_related_gmedia'], '1' ); ?> /> <?php esc_html_e( 'Gmedia Posts (media items from Gmedia Libary)', 'grand-media' ); ?> </label>
		</div>
		<div class="checkbox" style="margin:0;">
			<input type="hidden" name="set[wp_author_related_gmedia_album]" value="0"/>
			<label><input type="checkbox" name="set[wp_author_related_gmedia_album]" value="1" <?php checked( $gmGallery->options['wp_author_related_gmedia_album'], '1' ); ?> /> <?php esc_html_e( 'Gmedia Albums', 'grand-media' ); ?> </label>
		</div>
		<div class="checkbox" style="margin:0;">
			<input type="hidden" name="set[wp_author_related_gmedia_gallery]" value="0"/>
			<label><input type="checkbox" name="set[wp_author_related_gmedia_gallery]" value="1" <?php checked( $gmGallery->options['wp_author_related_gmedia_gallery'], '1' ); ?> /> <?php esc_html_e( 'Gmedia Galleries', 'grand-media' ); ?> </label>
		</div>
	</div>

	<hr/>
	<div class="form-group">
		<label><?php esc_html_e( 'When set title from filename', 'grand-media' ); ?>:</label>

		<div class="checkbox" style="margin:0;">
			<input type="hidden" name="set[name2title_capitalize]" value="0"/>
			<label><input type="checkbox" name="set[name2title_capitalize]" value="1" <?php checked( $gmGallery->options['name2title_capitalize'], '1' ); ?> /> <?php esc_html_e( 'Make the first letter of each word capitalized (Title Case)', 'grand-media' ); ?> </label>
		</div>
	</div>

	<hr/>
	<div class="form-group">
		<label><?php esc_html_e( 'Forbid other plugins to load their JS and CSS on Gmedia admin pages', 'grand-media' ); ?>:</label>

		<div class="checkbox" style="margin:0;">
			<input type="hidden" name="set[isolation_mode]" value="0"/>
			<label><input type="checkbox" name="set[isolation_mode]" value="1" <?php checked( $gmGallery->options['isolation_mode'], '1' ); ?> /> <?php esc_html_e( 'Enable Gmedia admin panel Isolation Mode', 'grand-media' ); ?> </label>

			<p class="help-block"><?php esc_html_e( 'This option could help to avoid JS and CSS conflicts with other plugins in admin panel.', 'grand-media' ); ?></p>
		</div>
	</div>
	<div class="form-group">
		<label><?php esc_html_e( 'Forbid theme to format Gmedia shortcode\'s content', 'grand-media' ); ?>:</label>

		<div class="checkbox" style="margin:0;">
			<input type="hidden" name="set[shortcode_raw]" value="0"/>
			<label><input type="checkbox" name="set[shortcode_raw]" value="1" <?php checked( $gmGallery->options['shortcode_raw'], '1' ); ?> /> <?php esc_html_e( 'Raw output for Gmedia Shortcode', 'grand-media' ); ?> </label>

			<p class="help-block"><?php esc_html_e( 'Some themes reformat shortcodes and break it functionality (mostly when you add description to images). Turning this on should solve this problem.', 'grand-media' ); ?></p>
		</div>
	</div>
	<div class="form-group">
		<label><?php esc_html_e( 'Debug Mode', 'grand-media' ); ?>:</label>

		<div class="checkbox" style="margin:0;">
			<input type="hidden" name="set[debug_mode]" value=""/>
			<label><input type="checkbox" name="set[debug_mode]" value="1" <?php checked( $gmGallery->options['debug_mode'], '1' ); ?> /> <?php esc_html_e( 'Enable Debug Mode on Gmedia admin pages', 'grand-media' ); ?> </label>
		</div>
	</div>
	<?php
	$allowed_post_types = (array) $gmGallery->options['gmedia_post_types_support'];
	$args               = array(
		'public'   => true,
		'show_ui'  => true,
		'_builtin' => false,
	);
	$output             = 'objects'; // names or objects, note names is the default.
	$operator           = 'and'; // 'and' or 'or'.
	$post_types         = get_post_types( $args, $output, $operator );
	if ( ! empty( $post_types ) ) {
		?>
		<div class="form-group">
			<label style="margin-bottom:-5px;"><?php esc_html_e( 'Enable Gmedia Library button on custom post types', 'grand-media' ); ?>:</label>
			<input type="hidden" name="set[gmedia_post_types_support]" value=""/>
			<?php
			foreach ( $post_types as $p_type ) {
				?>
				<div class="checkbox">
					<label>
						<input type="checkbox" name="set[gmedia_post_types_support][]" value="<?php echo esc_attr( $p_type->name ); ?>" <?php echo in_array( $p_type->name, $allowed_post_types, true ) ? 'checked="checked"' : ''; ?> /> <?php echo esc_html( $p_type->label . ' (' . $p_type->name . ')' ); ?>
					</label>
				</div>
			<?php } ?>
		</div>
	<?php } ?>
</fieldset>
