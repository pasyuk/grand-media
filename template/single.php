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
        $type = explode('/', $gmedia->mime_type, 2);
        if ('image' == $type[0]) { ?>
            <div class="single-view type-image">
                <img class="gmedia-image" src="<?php echo $gmCore->gm_get_media_image($gmedia->ID); ?>">

                <div class="gmedia-text">
                    <h2 class="single-title"><?php echo $gmedia->title; ?></h2>

                    <div class="image-description"><?php echo wpautop($gmedia->description); ?></div>
                </div>
            </div>
        <?php } else { ?>
            <div class="single-view type-download type-<?php echo $type[0]; ?>">
                <img class="gmedia-image" src="<?php echo $gmCore->gm_get_media_image($gmedia->ID); ?>">

                <div class="gmedia-text">
                    <h2 class="single-title"><?php _e('Download', 'grand-media'); ?>:
                        <a href="<?php echo "{$gmCore->upload['url']}/{$gmGallery->options['folder'][$type[0]]}/{$gmedia->gmuid}"; ?>" download="download"><?php echo $gmedia->title; ?></a>
                    </h2>

                    <div class="image-description"><?php echo wpautop($gmedia->description); ?></div>
                </div>
            </div>
        <?php } /*elseif('video' == $type[0]){
			$meta = $gmDB->get_metadata('gmedia', $gmedia->ID, '_metadata', true);
			$width = isset($meta['width'])? $meta['width'] : 640;
			$height = isset($meta['height'])? $meta['height'] : 480;
			$url = $gmCore->fileinfo($gmedia->gmuid, false);
			?>
			<div class="single-view type-video">
				<video src="<?php echo $url['fileurl']; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>"></video>
			</div>
		<?php }*/ ?>
    </div>
</div>

<?php get_gmedia_footer(); ?>
