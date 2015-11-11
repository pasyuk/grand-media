<?php
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
if(!isset($is_bot)){ $is_bot = false; }
if(!isset($shortcode_raw)){ $shortcode_raw = false; }
$tab = sanitize_title($gallery['name']);
$base_url_host = parse_url($gmCore->upload['url'], PHP_URL_HOST);
foreach($terms as $term){

	foreach($gmedia[$term->term_id] as $item){
		if('image' != substr($item->mime_type, 0, 5)){
			continue;
		}
		$meta = $gmDB->get_metadata('gmedia', $item->ID);
		$_metadata = $meta['_metadata'][0];
		unset($meta['_metadata']);

		$link_target = '';
		if($item->link){
			$url_host = parse_url($item->link, PHP_URL_HOST);
			if($url_host == $base_url_host || empty($url_host)){
				$link_target = '_self';
			}	else{
				$link_target = '_blank';
			}
		}
		if(isset($meta['link_target'][0])){
			$link_target = $meta['link_target'][0];
		}

		$content[] = array(
			'id' => $item->ID
			,'image' => "/{$gmGallery->options['folder']['image']}/{$item->gmuid}"
			,'thumb' => "/{$gmGallery->options['folder']['image_thumb']}/{$item->gmuid}"
			,'captionTitle' => $item->title
			,'captionText' => str_replace(array("\r\n", "\r", "\n"), '', wpautop($item->description))
			,'media' => ''
			,'link' => $item->link
			,'linkTarget' => $link_target
			,'date' => $item->date
			,'websize' => array_values($_metadata['web'])
			,'thumbsize' => array_values($_metadata['thumb'])
		);
	}
}

if(!empty($content)){
	$settings = array_merge($settings, array(
		'ID' => $gallery['term_id'],
		'moduleUrl' => $module['url'],
		'pluginUrl' => $gmCore->gmedia_url,
		'libraryUrl' => $gmCore->upload['url']
	));
	$json_settings = json_encode($settings);
	$settings = array_merge($module['options'], $settings);
	?>
<?php if(!$is_bot) {
		if ( $shortcode_raw ) {
			echo '<pre style="display:none">';
		}
		?>
		<script type="text/javascript">
			jQuery(function(){
				var settings = <?php echo $json_settings; ?>;
				var content = <?php echo json_encode($content); ?>;
				jQuery('#GmediaGallery_<?php echo $gallery['term_id'] ?>').gmPhantom([content, settings]);
			});
		</script><?php
		if ( $shortcode_raw ) {
			echo '</pre>';
		}
	}
?>
<div class="gmPhantom_Container delay"<?php if(!$is_bot){ echo ' style="opacity:0.01"'; } ?>>
	<div class="gmPhantom_Background"></div>
	<div class="gmPhantom_thumbsWrapper">
		<?php $i = 0; $wrapper_r = $settings['thumbWidth']/$settings['thumbHeight'];
		$tw_size = "width:{$settings['thumbWidth']}px;height:{$settings['thumbHeight']}px;";
		foreach($content as $item){
			$thumb_r = $item['thumbsize'][0]/$item['thumbsize'][1];
			if($wrapper_r < $thumb_r){
				$orientation = 'landscape';
				$margin = 'margin:0 0 0 -'.floor(($settings['thumbHeight']*$thumb_r - $settings['thumbWidth'])/$settings['thumbWidth']*50).'%;';
			} else{
				$orientation = 'portrait';
				$margin = 'margin:-'.floor(($settings['thumbWidth']/$thumb_r - $settings['thumbHeight'])/$settings['thumbHeight']*25).'% 0 0 0;';
			}
			?><div class="gmPhantom_ThumbContainer gmPhantom_ThumbLoader" data-ratio="<?php echo "$wrapper_r/$thumb_r"; ?>" style="<?php echo $tw_size; ?>" data-no="<?php echo $i++; ?>"><?php
			?><div class="gmPhantom_Thumb"><img style="<?php echo $margin; ?>" class="<?php echo $orientation; ?>" src="<?php echo $settings['libraryUrl'].$item['thumb']; ?>" alt="<?php echo esc_attr($item['captionTitle']); ?>" /></div><?php
			if(($settings['thumbsInfo'] == 'label') && ($item['captionTitle'] != '')){
				if(!empty($item['link'])){
					$item['captionTitle'] = "<a href='{$item['link']}' target='{$item['linkTarget']}'>{$item['captionTitle']}</a>";
				}
				?><div class="gmPhantom_ThumbLabel"><?php echo $item['captionTitle']; ?></div><div style="display:none;" class="gmPhantom_ThumbCaption"><?php echo $item['captionText']; ?></div><?php
			} ?></div><?php
		} ?><br style="clear:both;" />
	</div>
</div>

<?php
} else{
	echo '<div class="gmedia-no-files">' . GMEDIA_GALLERY_EMPTY . '</div>';
}


