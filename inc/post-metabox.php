<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Adds the meta box to the post or page edit screen
 *
 * @param string $page    the name of the current page
 * @param string $context the current context
 */
function gmedia_add_meta_box( $page, $context ) {
	if ( ! current_user_can( 'gmedia_library' ) ) {
		return;
	}
	$gm_options        = get_option( 'gmediaOptions' );
	$gmedia_post_types = array_merge( array( 'post', 'page' ), (array) $gm_options['gmedia_post_types_support'] );
	// Plugins that use custom post types can use this filter to show the Gmedia UI in their post type.
	$gm_post_types = apply_filters( 'gmedia_post_types', $gmedia_post_types );

	if ( function_exists( 'add_meta_box' ) && ! empty( $gm_post_types ) && in_array( $page, $gm_post_types, true ) && 'side' === $context ) {
		add_action( 'media_buttons', 'gmedia_media_buttons_context', 4 );
		add_action( 'admin_enqueue_scripts', 'gmedia_meta_box_load_scripts', 20 );
		//add_meta_box('gmedia-MetaBox', __('Gmedia Gallery MetaBox', 'grand-media'), 'gmedia_post_metabox', $page, 'side', 'low');
		add_action( 'admin_footer', 'gmedia_post_modal_tpl' );
		add_filter( 'admin_post_thumbnail_html', 'gmedia_admin_post_thumbnail_html', 10, 2 );

		add_meta_box( 'gmedia-related', __( 'Gmedia Related', 'grand-media' ), 'gmedia_related_post_metabox', $page, 'side', 'low' );
	}

}

add_action( 'do_meta_boxes', 'gmedia_add_meta_box', 20, 2 );
add_action( 'save_post', 'gmedia_related_post_metabox_save', 10, 2 );

/**
 * @return void
 */
function gmedia_media_buttons_context() {
	?>
	<div style="display:inline-block;">
		<a id="gmedia-modal" title="Gmedia Galleries" class="gmedia_button button" href="#gmedia"><span class="wp-media-buttons-icon" style="background: url('<?php echo esc_url( plugins_url( 'admin/assets/img/gm-icon.png', dirname( __FILE__ ) ) ); ?>') no-repeat top left;"></span> <?php esc_html_e( 'Gmedia', 'grand-media' ); ?></a>
	</div>
	<?php
}

/**
 * @param $hook
 */
function gmedia_meta_box_load_scripts( $hook ) {
	if ( ( in_array( $hook, array( 'post.php', 'edit.php' ), true ) && isset( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) || 'post-new.php' === $hook ) {
		wp_enqueue_style( 'gmedia-meta-box', plugins_url( 'admin/assets/css/gmedia.metabox.css', dirname( __FILE__ ) ), array(), '1.0.0' );
		wp_enqueue_script( 'gmedia-meta-box', plugins_url( 'admin/assets/js/gmedia.metabox.js', dirname( __FILE__ ) ), array( 'jquery', 'gmedia-global-backend' ), '1.4.3', true );
	}
}

function gmedia_post_modal_tpl() {
	global $post;
	?>
	<script type="text/html" id="tpl__gm-uploader">
		<div id="__gm-uploader" tabindex="0">
			<div class="media-modal wp-core-ui">
				<a class="media-modal-close" style="right:0;top:0;line-height:40px;width:40px;height:40px;text-align:center;text-decoration:none;" href="javascript:void(0)"><span class="media-modal-icon" style="margin-top:0;"></span></a>

				<div class="media-modal-content">
					<div class="media-frame wp-core-ui hide-router hide-toolbar">
						<div class="media-frame-title"><h1><?php esc_html_e( 'Gmedia Collections', 'grand-media' ); ?></h1></div>
						<div class="media-frame-menu">
							<div class="media-menu">
								<a id="gmedia-modal-terms" class="media-menu-item active" target="gmedia_frame"
									href="<?php echo esc_url( add_query_arg( array( 'post_id' => $post->ID, 'tab' => 'gmedia_terms', 'chromeless' => true ), admin_url( 'media-upload.php' ) ) ); ?>">
									<?php esc_html_e( 'Gmedia Collections', 'grand-media' ); ?></a>
								<a id="gmedia-modal-library" class="media-menu-item" target="gmedia_frame"
									href="<?php echo esc_url( add_query_arg( array( 'post_id' => $post->ID, 'tab' => 'gmedia_library', 'chromeless' => true ), admin_url( 'media-upload.php' ) ) ); ?>">
									<?php esc_html_e( 'Gmedia Library', 'grand-media' ); ?></a>
								<a id="gmedia-modal-galleries" class="media-menu-item" target="gmedia_frame"
									href="<?php echo esc_url( add_query_arg( array( 'post_id' => $post->ID, 'tab' => 'gmedia_galleries', 'chromeless' => true ), admin_url( 'media-upload.php' ) ) ); ?>">
									<?php esc_html_e( 'Gmedia Galleries', 'grand-media' ); ?></a>
								<?php if ( current_user_can( 'gmedia_upload' ) ) { ?>
									<a id="gmedia-modal-upload" class="media-menu-item" target="gmedia_frame"
										href="<?php echo esc_url( add_query_arg( array( 'post_id' => $post->ID, 'tab' => 'gmedia_library', 'action' => 'upload', 'chromeless' => true ), admin_url( 'media-upload.php' ) ) ); ?>">
										<?php esc_html_e( 'Gmedia Upload', 'grand-media' ); ?></a>
								<?php } ?>
							</div>
						</div>
						<div class="media-frame-content">
							<div class="media-iframe">
								<iframe name="gmedia_frame" src="<?php echo esc_url( add_query_arg( array( 'post_id' => $post->ID, 'tab' => 'gmedia_terms', 'chromeless' => true ), admin_url( 'media-upload.php' ) ) ); ?>"></iframe>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="media-modal-backdrop"></div>
		</div>
	</script>
	<?php
}

/*
 * add_tinymce_plugin()
 * Load the TinyMCE plugin : tinymce_gmedia_plugin.js
 *
 * @param array $plugin_array
 *
 * @return array $plugin_array

function gmedia_tinymce_plugin( $plugin_array ) {

$plugin_array['gmedia'] = plugins_url( 'admin/assets/js/gmedia.tinymce.js', dirname( __FILE__ ) );

return $plugin_array;
}
//add_filter( 'mce_external_plugins', 'gmedia_tinymce_plugin', 5 );
*/

/** Build custom field meta box
 *
 * @param $post
 */
function gmedia_related_post_metabox( $post ) {
	wp_nonce_field( 'GmediaGallery', '_wpnonce_related_gmedia' );
	$related          = get_post_meta( $post->ID, '_related_gmedia', true );
	$related_per_page = get_post_meta( $post->ID, '_related_gmedia_per_page', true );
	?>
	<div id="gmedia-wraper">
		<div class="form-group">
			<label><?php esc_html_e( 'Show Related Gmedia based on tags', 'grand-media' ); ?>:</label>
			<select name="_related_gmedia" class="form-control input-sm">
				<option value="" <?php selected( $related, '' ); ?>><?php esc_html_e( 'Global Setting', 'grand-media' ); ?></option>
				<option value="1" <?php selected( $related, '1' ); ?>><?php esc_html_e( 'Enabled', 'grand-media' ); ?></option>
				<option value="0" <?php selected( $related, '0' ); ?>><?php esc_html_e( 'Disabled', 'grand-media' ); ?></option>
			</select>
		</div>
		<div class="form-group">
			<label><?php esc_html_e( 'Limit the quantity of displayed items', 'grand-media' ); ?>:</label>
			<input type="text" name="_related_gmedia_per_page" value="<?php echo esc_attr( $related_per_page ); ?>" placeholder="<?php esc_attr_e( 'Leave empty for no limit', 'grand-media' ); ?>" class="form-control input-sm"/>
		</div>
	</div>
	<?php
}

/** Store custom field meta box data
 *
 * @param $post_id
 * @param $update
 */
function gmedia_related_post_metabox_save( $post_id, $update ) {
	// return if autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	// verify taxonomies meta box nonce.
	if ( ! isset( $_POST['_wpnonce_related_gmedia'] ) || ! wp_verify_nonce( $_POST['_wpnonce_related_gmedia'], 'GmediaGallery' ) ) {
		return;
	}
	// Check the user's permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// store custom fields values.
	if ( isset( $_REQUEST['_related_gmedia'] ) && '' !== $_REQUEST['_related_gmedia'] ) {
		update_post_meta( $post_id, '_related_gmedia', (int) $_REQUEST['_related_gmedia'] );
	} else {
		delete_post_meta( $post_id, '_related_gmedia' );
	}
	if ( isset( $_REQUEST['_related_gmedia_per_page'] ) && (int) $_REQUEST['_related_gmedia_per_page'] ) {
		update_post_meta( $post_id, '_related_gmedia_per_page', sanitize_text_field( (int) $_REQUEST['_related_gmedia_per_page'] ) );
	} else {
		delete_post_meta( $post_id, '_related_gmedia_per_page' );
	}
}

/** Build gmedia meta box
 *
 * @param $post
 */
function gmedia_post_metabox( $post ) {
	global $gmCore, $gmDB, $user_ID;
	$t = $gmCore->gmedia_url . '/admin/assets/img/blank.gif';
	?>
	<div id="gmedia-wraper">
		<div id="gmedia-message">
			<span class="info-init text-info" style="display: none;"><?php esc_html_e( 'Initializing...', 'grand-media' ); ?></span>
			<span class="info-textarea text-warning" style="display: none;"><?php esc_html_e( 'Choose text area first', 'grand-media' ); ?></span>
		</div>
		<div id="gmedia-source">
			<div id="gmedia-galleries">
				<div class="title-bar">
					<span class="gmedia-galleries-title"><?php esc_html_e( 'Gmedia Galleries', 'grand-media' ); ?></span>
					<a title="<?php esc_attr_e( 'Create Gallery', 'grand-media' ); ?>" class="button button-primary button-small gm-add-button" target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=GrandMedia_Modules' ) ); ?>">
						<?php esc_html_e( 'Create Gallery', 'grand-media' ); ?></a>
				</div>
				<div id="gmedia-galleries-wrap">
					<ul id="gmedia-galleries-list">
						<?php
						$taxonomy = 'gmedia_gallery';
						if ( $gmCore->caps['gmedia_edit_others_media'] ) {
							$args = array();
						} else {
							$args = array( 'global' => $user_ID );
						}

						$gmediaTerms = $gmDB->get_terms( $taxonomy, $args );

						if ( count( $gmediaTerms ) ) {
							foreach ( $gmediaTerms as $item ) {
								$module_folder = $gmDB->get_metadata( 'gmedia_term', $item->term_id, '_module', true );
								$module_dir    = $gmCore->get_module_path( $module_folder );
								if ( ! $module_dir ) {
									continue;
								}

								/** @var $module array */
								$module_info = array();
								/* @noinspection PhpIncludeInspection */
								include $module_dir['path'] . '/index.php';

								?>
								<li class="gmedia-gallery-li" id="gmGallery-<?php echo absint( $item->term_id ); ?>">
									<p class="gmedia-gallery-title">
										<span class="gmedia-gallery-preview"><img src="<?php echo esc_url( $module_dir['url'] . '/screenshot.png' ); ?>" alt=""/></span><span><?php echo esc_html( $item->name ); ?></span>
									</p>

									<p class="gmedia-gallery-source">
										<span class="gmedia-gallery-module"><?php echo esc_html( __( 'module', 'grand-media' ) . ': ' . $module_info['title'] ); ?></span>
									</p>

									<div class="gmedia-insert">
										<div class="gmedia-remove-button">
											<img src="<?php echo esc_url( $t ); ?>" alt=""/><?php esc_html_e( 'click to remove shortcode', 'grand-media' ); ?>
											<br/>
											<small>[gmedia id=<?php echo absint( $item->term_id ); ?>]</small>
										</div>
										<div class="gmedia-insert-button">
											<img src="<?php echo esc_url( $t ); ?>" alt=""/><?php esc_html_e( 'click to insert shortcode', 'grand-media' ); ?>
											<br/>
											<small>[gmedia id=<?php echo absint( $item->term_id ); ?>]</small>
										</div>
									</div>
									<div class="gmedia-selector"></div>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=GrandMedia_Galleries&amp;edit_term=' . $item->term_id ) ); ?>"
										title="Edit Gallery #<?php echo absint( $item->term_id ); ?> in New Window" target="_blank" class="gmedia-gallery-gear"><?php esc_html_e( 'edit', 'grand-media' ); ?></a>
								</li>
								<?php
							}
						} else {
							echo '<li class="emptydb">' . esc_html__( 'No Galleries.', 'grand-media' ) . ' <a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=GrandMedia_Modules' ) ) . '">' . esc_html__( 'Create', 'grand-media' ) . '</a></li>';
						}
						?>
					</ul>
				</div>
			</div>
			<div id="gmedia-social">
				<p><a target="_blank" href="https://wordpress.org/extend/plugins/grand-media/"><?php esc_html_e( 'Rate Gmedia at Wordpress.org', 'grand-media' ); ?></a></p>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Filter for the post meta box. look for a NGG image if the ID is "ngg-<imageID>"
 *
 * @param string   $content
 * @param int|null $post_id
 *
 * @return string html output
 */
function gmedia_admin_post_thumbnail_html( $content, $post_id = null ) {
	if ( null === $post_id ) {
		global $post;

		if ( ! is_object( $post ) ) {
			return $content;
		}
		$post_id = $post->ID;
	}

	$set_thumbnail_link = '<p class="hide-if-no-js"><a title="' . esc_attr__( 'Set GmediaGallery featured image', 'grand-media' ) . '" href="javascript:void(0)" id="set-gmedia-post-thumbnail">%s</a></p>';

	$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
	if ( $thumbnail_id ) {
		$gmedia_id = get_post_meta( $thumbnail_id, '_gmedia_image_id', true );
		if ( ! empty( $gmedia_id ) ) {
			$content = str_replace( 'attachment-post-thumbnail', 'attachment-post-thumbnail gmedia-post-thumbnail gmedia-image-' . $gmedia_id, $content );
		}
		$content = sprintf( $set_thumbnail_link, esc_html__( 'Replace from Gmedia Library', 'grand-media' ) ) . $content;
	} else {
		$content = sprintf( $set_thumbnail_link, esc_html__( 'Set Gmedia featured image', 'grand-media' ) ) . $content;
	}

	return $content;
}
