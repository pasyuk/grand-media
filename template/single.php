<?php get_gmedia_header();

/**
 * @var $gmedia
 */
?>

<div class="gmedia-flex-box">
	<div class="gmedia-main-wrapper">
		<?php
		/**
		 * @var $gmCore
		 * @var $gmDB
		 * @var $gmGallery
		 */
		$gm_mime_type = explode( '/', $gmedia->mime_type, 2 );
		$gm_title     = wp_strip_all_tags( $gmedia->title );
		if ( 'image' === $gm_mime_type[0] ) {
			?>
			<div class="single-view type-image">
				<img class="gmedia-image" src="<?php echo esc_url( $gmCore->gm_get_media_image( $gmedia->ID ) ); ?>" alt="<?php echo esc_attr( $gm_title ); ?>">

				<div class="gmedia-text">
					<h2 class="single-title"><?php echo esc_html( $gm_title ); ?></h2>

					<div class="image-description"><?php echo wp_kses_post( wpautop( $gmedia->description ) ); ?></div>
				</div>
			</div>
		<?php } else { ?>
			<div class="single-view type-download type-<?php echo esc_attr( $gm_mime_type[0] ); ?>">
				<img class="gmedia-image" src="<?php echo esc_url( $gmCore->gm_get_media_image( $gmedia->ID ) ); ?>" alt="<?php echo esc_attr( $gm_title ); ?>">

				<div class="gmedia-text">
					<h2 class="single-title"><?php esc_html_e( 'Download', 'grand-media' ); ?>:
						<a href="<?php echo esc_url( "{$gmCore->upload['url']}/{$gmGallery->options['folder'][$gm_mime_type[0]]}/{$gmedia->gmuid}" ); ?>" download="download"><?php echo esc_html( $gm_title ); ?></a>
					</h2>

					<div class="image-description"><?php echo wp_kses_post( wpautop( $gmedia->description ) ); ?></div>
				</div>
			</div>
			<?php
		}
		/*
		 elseif ( 'video' === $gm_mime_type[0] ) {
			$meta = $gmDB->get_metadata('gmedia', $gmedia->ID, '_metadata', true);
			$width = isset($meta['width'])? $meta['width'] : 640;
			$height = isset($meta['height'])? $meta['height'] : 480;
			$url = $gmCore->fileinfo($gmedia->gmuid, false);
			?>
			<div class="single-view type-video">
				<video src="<?php echo esc_url( $url['fileurl'] ); ?>" width="<?php echo absint( $width ); ?>" height="<?php echo absint( $height ); ?>"></video>
			</div>
			<?php
		 }
		*/
		?>
	</div>
</div>

<?php get_gmedia_footer(); ?>
