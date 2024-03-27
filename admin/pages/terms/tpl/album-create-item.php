<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Add Album Form
 */
global $gmProcessor, $gmGallery;
$gmedia_url = $gmProcessor->url;
?>
<form method="post" id="gmedia-edit-term" name="gmAddTerms" class="card-body" action="<?php echo esc_url( $gmedia_url ); ?>" style="padding-bottom:0; border-bottom:1px solid #ddd;">
	<div class="row">
		<div class="col-sm-6">
			<div class="form-group">
				<label><?php esc_html_e( 'Name', 'grand-media' ); ?></label>
				<input type="text" class="form-control input-sm" name="term[name]" placeholder="<?php esc_attr_e( 'Album Name', 'grand-media' ); ?>" required/>
			</div>
			<div class="form-group">
				<label><?php esc_html_e( 'Description', 'grand-media' ); ?></label>
				<?php
				wp_editor(
					'',
					'album_description',
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
			<div class="row">
				<div class="col-sm-6">
					<div class="form-group">
						<label><?php esc_html_e( 'Order gmedia', 'grand-media' ); ?></label>
						<select name="term[meta][_orderby]" class="form-control input-sm">
							<option value="ID" <?php selected( $gmGallery->options['in_album_orderby'], 'ID' ); ?>><?php esc_html_e( 'by ID', 'grand-media' ); ?></option>
							<option value="title" <?php selected( $gmGallery->options['in_album_orderby'], 'title' ); ?>><?php esc_html_e( 'by title', 'grand-media' ); ?></option>
							<option value="gmuid" <?php selected( $gmGallery->options['in_album_orderby'], 'gmuid' ); ?>><?php esc_html_e( 'by filename', 'grand-media' ); ?></option>
							<option value="date" <?php selected( $gmGallery->options['in_album_orderby'], 'date' ); ?>><?php esc_html_e( 'by date', 'grand-media' ); ?></option>
							<option value="modified" <?php selected( $gmGallery->options['in_album_orderby'], 'modified' ); ?>><?php esc_html_e( 'by last modified date', 'grand-media' ); ?></option>
							<option value="_created_timestamp" <?php selected( $gmGallery->options['in_album_orderby'], '_created_timestamp' ); ?>><?php esc_html_e( 'by created timestamp', 'grand-media' ); ?></option>
							<option value="comment_count" <?php selected( $gmGallery->options['in_album_orderby'], 'comment_count' ); ?>><?php esc_html_e( 'by comment count', 'grand-media' ); ?></option>
							<option value="views" <?php selected( $gmGallery->options['in_album_orderby'], 'views' ); ?>><?php esc_html_e( 'by views', 'grand-media' ); ?></option>
							<option value="likes" <?php selected( $gmGallery->options['in_album_orderby'], 'likes' ); ?>><?php esc_html_e( 'by likes', 'grand-media' ); ?></option>
							<option value="_size" <?php selected( $gmGallery->options['in_album_orderby'], '_size' ); ?>><?php esc_html_e( 'by file size', 'grand-media' ); ?></option>
							<option value="rand" <?php selected( $gmGallery->options['in_album_orderby'], 'rand' ); ?>><?php esc_html_e( 'Random', 'grand-media' ); ?></option>
						</select>
					</div>
					<div class="form-group">
						<label><?php esc_html_e( 'Sort order', 'grand-media' ); ?></label>
						<select name="term[meta][_order]" class="form-control input-sm">
							<option value="DESC" <?php selected( $gmGallery->options['in_album_order'], 'DESC' ); ?>><?php esc_html_e( 'DESC', 'grand-media' ); ?></option>
							<option value="ASC" <?php selected( $gmGallery->options['in_album_order'], 'ASC' ); ?>><?php esc_html_e( 'ASC', 'grand-media' ); ?></option>
						</select>
					</div>
					<div class="form-group">
						<label><?php esc_html_e( 'Module/Preset', 'grand-media' ); ?></label>
						<select class="form-control input-sm" id="term_module_preset" name="term[meta][_module_preset]">
							<option value="" <?php echo empty( $term->meta['_module_preset'][0] ) ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Default module in Global Settings', 'grand-media' ); ?></option>
							<?php
							global $gmDB, $user_ID, $gm_allowed_tags;
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
						<label><?php esc_html_e( 'Author', 'grand-media' ); ?></label>
						<?php gmedia_term_choose_author_field(); ?>
					</div>
					<div class="form-group">
						<label><?php esc_html_e( 'Status', 'grand-media' ); ?></label>
						<select name="term[status]" class="form-control input-sm">
							<option value="publish" <?php selected( $gmGallery->options['in_album_status'], 'publish' ); ?>><?php esc_html_e( 'Public', 'grand-media' ); ?></option>
							<option value="private" <?php selected( $gmGallery->options['in_album_status'], 'private' ); ?>><?php esc_html_e( 'Private', 'grand-media' ); ?></option>
							<option value="draft" <?php selected( $gmGallery->options['in_album_status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'grand-media' ); ?></option>
						</select>
					</div>
					<?php
					/*
					?>
					<div class="form-group">
						<label><?php esc_html_e('Comment Status', 'grand-media'); ?></label>
						<select name="term[comment_status]" class="form-control input-sm">
							<option <?php echo ('open' == $gmGallery->options['default_gmedia_term_comment_status'])? 'selected="selected"' : ''; ?> value="open"><?php esc_html_e('Open', 'grand-media'); ?></option>
							<option <?php echo ('closed' == $gmGallery->options['default_gmedia_term_comment_status'])? 'selected="selected"' : ''; ?> value="closed"><?php esc_html_e('Closed', 'grand-media'); ?></option>
						</select>
					</div>
					<?php
					 */
					?>
					<div class="form-group">
						<label>&nbsp;</label>
						<?php
						wp_original_referer_field( true, 'previous' );
						wp_nonce_field( 'gmedia_terms', '_wpnonce_terms' );
						?>
						<input type="hidden" name="term[taxonomy]" value="gmedia_album"/>
						<button style="display:block" type="submit" class="btn btn-primary btn-xs" name="gmedia_album_save"><?php esc_html_e( 'Add New Album', 'grand-media' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>
