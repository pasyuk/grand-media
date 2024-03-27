<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Add Album Form
 */
global $gmProcessor, $gmGallery, $gm_allowed_tags;
$gmedia_url = $gmProcessor->url;

$_orderby = $gmGallery->options['in_category_orderby'];
$_order   = $gmGallery->options['in_category_order'];
?>
<form method="post" id="gmedia-edit-term" name="gmAddTerms" class="card-body" action="<?php echo esc_url( $gmedia_url ); ?>" style="padding-bottom:0; border-bottom:1px solid #ddd;">
	<div class="row">
		<div class="col-sm-6">
			<div class="form-group">
				<label><?php esc_html_e( 'Name', 'grand-media' ); ?></label>
				<input type="text" class="form-control input-sm" name="term[name]" placeholder="<?php esc_attr_e( 'Category Name', 'grand-media' ); ?>" required/>
			</div>
			<div class="form-group">
				<label><?php esc_html_e( 'Description', 'grand-media' ); ?></label>
				<?php
				wp_editor(
					'',
					'category_description',
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
			<div class="form-group row">
				<div class="col-sm-6">
					<label><?php esc_html_e( 'Order gmedia', 'grand-media' ); ?></label>
					<select name="term[meta][_orderby]" class="form-control input-sm">
						<option value="ID"<?php selected( $_orderby, 'ID' ); ?>><?php esc_html_e( 'by ID', 'grand-media' ); ?></option>
						<option value="title"<?php selected( $_orderby, 'title' ); ?>><?php esc_html_e( 'by title', 'grand-media' ); ?></option>
						<option value="gmuid"<?php selected( $_orderby, 'gmuid' ); ?>><?php esc_html_e( 'by filename', 'grand-media' ); ?></option>
						<option value="date"<?php selected( $_orderby, 'date' ); ?>><?php esc_html_e( 'by date', 'grand-media' ); ?></option>
						<option value="modified"<?php selected( $_orderby, 'modified' ); ?>><?php esc_html_e( 'by last modified date', 'grand-media' ); ?></option>
						<option value="_created_timestamp" <?php selected( $_orderby, '_created_timestamp' ); ?>><?php esc_html_e( 'by created timestamp', 'grand-media' ); ?></option>
						<option value="comment_count" <?php selected( $_orderby, 'comment_count' ); ?>><?php esc_html_e( 'by comment count', 'grand-media' ); ?></option>
						<option value="views" <?php selected( $_orderby, 'views' ); ?>><?php esc_html_e( 'by views', 'grand-media' ); ?></option>
						<option value="likes" <?php selected( $_orderby, 'likes' ); ?>><?php esc_html_e( 'by likes', 'grand-media' ); ?></option>
						<option value="_size" <?php selected( $_orderby, '_size' ); ?>><?php esc_html_e( 'by file size', 'grand-media' ); ?></option>
						<option value="rand"<?php selected( $_orderby, 'rand' ); ?>><?php esc_html_e( 'Random', 'grand-media' ); ?></option>
					</select>
				</div>
				<div class="col-sm-6">
					<label><?php esc_html_e( 'Sort order', 'grand-media' ); ?></label>
					<select name="term[meta][_order]" class="form-control input-sm">
						<option value="DESC"<?php selected( $_order, 'DESC' ); ?>><?php esc_html_e( 'DESC', 'grand-media' ); ?></option>
						<option value="ASC"<?php selected( $_order, 'ASC' ); ?>><?php esc_html_e( 'ASC', 'grand-media' ); ?></option>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-6">
					<div class="form-group">
						<label><?php esc_html_e( 'Module/Preset', 'grand-media' ); ?></label>
						<select class="form-control input-sm" id="term_module_preset" name="term[meta][_module_preset]">
							<option value=""
							<?php
							if ( empty( $term->meta['_module_preset'][0] ) ) {
								echo ' selected="selected"';
							}
							?>
							><?php esc_html_e( 'Default module in Global Settings', 'grand-media' ); ?></option>
							<?php
							global $gmDB, $user_ID;
							$gmedia_modules = get_gmedia_modules( false );
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
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label>&nbsp;</label>
						<?php
						wp_original_referer_field( true, 'previous' );
						wp_nonce_field( 'gmedia_terms', '_wpnonce_terms' );
						?>
						<input type="hidden" name="term[taxonomy]" value="gmedia_category"/>
						<button style="display:block" type="submit" class="btn btn-primary btn-xs" name="gmedia_category_save"><?php esc_html_e( 'Add New Category', 'grand-media' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
