<?php
ini_set('display_errors', 0);
ini_set('error_reporting', 0);

/** @var $gmDB
 * @var  $gmCore
 * @var  $gmGallery
 * @var  $gallery
 * @var  $module
 * @var  $settings
 * @var  $terms
 * @var  $gmedia
 * @var  $is_bot
 **/
$content = array();
$settings = array_merge($module['options'], $settings);
if(!isset($shortcode_raw)){ $shortcode_raw = false; }
$tab = sanitize_title($gallery['name']);
foreach($terms as $term){

	foreach($gmedia[$term->term_id] as $item){
		if('video' != substr($item->mime_type, 0, 5)){
			continue;
		}

		$get_cover_from = $item;
		$default_cover = wp_mime_type_icon($item->mime_type);
		$albums = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_album');
		if(!empty($albums)) {
			$album = reset( $albums );
			if ( ! empty( $album ) ) {
				$cover_id = $gmDB->get_metadata( 'gmedia_term', $album->term_id, '_cover', true );
				if ( (int) $cover_id ) {
					$get_cover_from = $cover_id;
				}
			}
		}

		$cover = $gmCore->gm_get_media_image($get_cover_from, 'web', true);
		$img_w = $img_h = '';
		if($cover == $default_cover){
			$img_w = 48;
			$img_h = 64;
			$cover_thumb = $cover;
		} else{
			$cover_thumb = $gmCore->gm_get_media_image($get_cover_from, 'thumb', true);
		}
		$meta = $gmDB->get_metadata('gmedia', $item->ID, '_metadata', true);
		if(empty($meta)){
			$meta = $gmCore->wp_read_video_metadata("{$gmCore->upload['path']}/{$gmGallery->options['folder']['video']}/{$item->gmuid}");
			$gmDB->update_metadata($meta_type = 'gmedia', $item->ID, $meta_key = '_metadata', $meta);
		}
		$height = $settings['width'] / 16 * 9;
		$content[] = array(
			 'id' => $item->ID
			,'src' => "{$gmCore->upload['url']}/{$gmGallery->options['folder']['video']}/{$item->gmuid}"
			,'type' => $item->mime_type
			,'title' => $item->title
			,'caption' => ''
			,'description' => str_replace(array("\r\n", "\r", "\n"), '', wpautop($item->description))
			,'meta' => array('length_formatted' => $meta['length_formatted'])
			,'dimensions' => array('original' => array('width' => $meta['width'], 'height' => $meta['height']), 'resized' => array('width' => intval($settings['width']), 'height' => intval($height)))
			,'image' => array('src' => $cover, 'width' => $img_w, 'height' => $img_h)
			,'thumb' => array('src' => $cover_thumb, 'width' => $img_w, 'height' => $img_h)
			,'meta2' => $meta
		);
	}
}

if(!empty($content)){
	$json_array = array(
		'type' => 'video'
		,'tracklist' => true
		,'tracknumbers' => ('1' == $settings['tracknumbers'])
		,'images' => true
		,'artists' => true
		,'tracks' => $content
	);
	?>
	<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->
	<div class="gmedia-wp-playlist wp-video-playlist wp-playlist-light" style="width:<?php echo intval($settings['width']).'px'; ?>; max-width:100%;">
		<video controls="controls" preload="none" width="640" height="480"></video>
		<div class="wp-playlist-next"></div>
		<div class="wp-playlist-prev"></div>
		<noscript>
			<ol>
				<?php foreach($content as $item){ ?>
				<li><a href='<?php echo $item['src']; ?>'><?php echo $item['title']; ?></a></li>
				<?php } ?>
			</ol>
		</noscript>
		<script type="application/json"><?php echo json_encode($json_array); ?></script>
	</div>
	<?php
} else{
	echo GMEDIA_GALLERY_EMPTY;
}