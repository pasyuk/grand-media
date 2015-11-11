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

$settings = array_merge($settings, array(
	'ID' => $gallery['term_id'],
	'name' => $gallery['name'],
	'moduleUrl' => $module['url'],
	'pluginUrl' => $gmCore->gmedia_url,
	'libraryUrl' => $gmCore->upload['url'],
	'ajaxurl' => admin_url('admin-ajax.php')
));
if($gmCore->_get('slide', false)){
	$iSlide = (int) $_GET['slide'];
	$settings['initial_slide'] = $iSlide;
} else {
	$iSlide = 0;
}
$allsettings = array_merge($module['options'], $settings);

$content = array(
	'term' => array(),
	'data' => array()
);
if(!isset($shortcode_raw)){ $shortcode_raw = false; }
foreach($terms as $term) {

	$content['term'][ $term->term_id ] = array(
		'term_id'          => $term->term_id,
		'title'       => $term->name,
		'description' => str_replace( array( "\r\n", "\r", "\n" ), '', wpautop( $term->description ) )
	);

	foreach($gmedia[$term->term_id] as $item){
		$type = substr($item->mime_type, 0, 5);
		$meta = $gmDB->get_metadata('gmedia', $item->ID);
		$_metadata = $meta['_metadata'][0];
		unset($meta['_metadata'], $_metadata['image']);

		$author['posts_link'] = get_author_posts_url($item->author);
		if(function_exists('get_avatar_url')) {
			$author['avatar'] = get_avatar_url( $item->author, array( 'size' => 50 ) );
		} else{
			$avatar_img = get_avatar( $item->author, 50 );
			if(preg_match("/src=['\"](.*?)['\"]/i", $avatar_img, $matches)){
				$author['avatar'] = $matches[1];
			}
		}
		$author['name'] = get_the_author_meta( 'display_name', $item->author);

		$download = '';
		if(!empty($allsettings['show_download_button'])){
			if(isset($meta['download'])) {
				$download = $meta['download'][0];
			} else {
				if('image' == $type){
					$download = $gmCore->gm_get_media_image($item->ID, 'original');
				} else {
					$download = "{$gmCore->upload['url']}/{$gmGallery->options['folder'][$type]}/{$item->gmuid}";
				}
			}
		}
		$link = empty($allsettings['show_link_button'])? '' : $item->link;
		$description = empty($allsettings['show_description'])? '' : str_replace(array("\r\n", "\r", "\n"), '', wpautop($item->description));


		$content['data'][$item->ID] = array(
			'id' => $item->ID,
			'type' => $type,
			'file' => $item->gmuid,
			'title' => $item->title,
			'description' => $description,
			'download' => $download,
			'link' => $link,
			'date' => $item->date,
			'meta' => $_metadata,
			'author' => $author,
			'term_id' => $term->term_id
		);

	}
}


if(!empty($content['data'])){
	$json_settings = json_encode($settings);

	$slides = array();
	$slides_thumbs = array();
	foreach($content['data'] as $id => $item){
		$web = $gmCore->gm_get_media_image($item['id'], 'web');
		$thumb = $gmCore->gm_get_media_image($item['id'], 'thumb');
		if('image' == $item['type']) {
			$ratio = $item['meta']['web']['width'] / $item['meta']['web']['height'];
		} elseif(isset($item['meta']['width']) && isset($item['meta']['height'])) {
			$ratio = $item['meta']['width'] / $item['meta']['height'];
		} else {
			$ratio = 1.5;
		}
		$content['data'][$id]['ratio'] = $ratio;
		if(1 <= $ratio){
			$orientation = 'landscape';
		} else{
			$orientation = 'portrait';
		}
		$slides[] = '
		<div class="swiper-slide" id="gmpm_ID_'.$item['id'].'"><span class="gmpm_va"></span>'
            .'<img data-src="'.$web.'" alt="'.esc_attr($item['title']).'" data-protect="'.$item['author']['name'].'" class="gmpm_the_photo swiper-lazy">'
            .'<div class="swiper-lazy-preloader swiper-lazy-preloader-black"></div>'
        .'</div>';
		$slides_thumbs[] = '
		<div class="swiper-slide gmpm_photo" data-photo-id="'.$item['id'].'">'
			.'<img data-src="'.$thumb.'" alt="" class="gmpm_photo swiper-lazy '.$orientation.'">'
            .'<span class="swiper-lazy-preloader swiper-lazy-preloader-black"></span>'
		.'</div>';
	}
	$content['data'] = array_values($content['data']);

	$photo_show_class = '';
	if(!empty($allsettings['gallery_maximized'])){
		$photo_show_class .= ' gmpm_maximized';
	}
	if(!empty($allsettings['gallery_focus'])){
		$photo_show_class .= ' gmpm_focus';
	}
	if(!empty($allsettings['gallery_focus_maximized'])){
		$photo_show_class .= ' gmpm_focus_maximized';
	}
	if(empty($allsettings['keyboard_help'])){
		$photo_show_class .= ' gmpm_diskeys';
	}
	?>

	<div class="gmpm_photo_show<?php echo $photo_show_class; ?>">

		<div class="gmpm_photo_wrap has_prev_photo has_next_photo">
			<div class="swiper-container swiper-big-images">
				<div class="gmpm_photo_arrow_next gmpm_photo_arrow gmpm_next">
					<div title="Next" class="gmpm_arrow"></div>
				</div>
				<div class="gmpm_photo_arrow_previous gmpm_photo_arrow gmpm_prev">
					<div title="Previous" class="gmpm_arrow"></div>
				</div>
				<div class="swiper-wrapper">
					<?php
					echo implode('', $slides);
					?>
				</div>
			</div>
		</div>

		<div class="gmpm_photo_header">
			<div class="gmpm_wrapper gmpm_clearfix">
				<div class="gmpm_name_wrap gmpm_clearfix">
					<?php if(!empty($allsettings['show_author_avatar'])){ ?>
					<div class="gmpm_user_avatar">
						<a class="gmpm_user_avatar_link" href="<?php echo $content['data'][$iSlide]['author']['posts_link']; ?>"><img src="<?php echo $content['data'][$iSlide]['author']['avatar']; ?>" alt="" /></a>
					</div>
					<?php } ?>
					<div class="gmpm_title_author">
						<h1 class="gmpm_title"><?php echo $content['data'][$iSlide]['title']; ?></h1>
						<div class="gmpm_author_name"><a class="gmpm_author_link" href="<?php echo $content['data'][$iSlide]['author']['posts_link']; ?>"><?php echo $content['data'][$iSlide]['author']['name']; ?></a></div>
					</div>
				</div>
				<div class="gmpm_actions gmpm_clearfix">
					<div class="gmpm_carousel gmpm_has_previous gmpm_has_next">
						<div class="gmpm_previous_button"></div>
						<div class="gmpm_photo_carousel">
							<div class="swiper-container swiper-small-images">
								<div class="swiper-wrapper">
									<?php echo implode('', $slides_thumbs); ?>
								</div>
							</div>
						</div>
						<div class="gmpm_next_button"></div>
					</div>
					<?php if(!empty($allsettings['show_download_button'])){ ?>
					<div class="gmpm_big_button_wrap">
						<a class="gmpm_big_button gmpm_download_button" href="<?php echo $content['data'][$iSlide]['download']; ?>" download="<?php echo esc_attr($content['data'][$iSlide]['file']); ?>">
							<span class="gmpm_icon"></span>
							<span class="gmpm_label"><?php echo $allsettings['download_button_text']; ?></span>
						</a>
					</div>
					<?php } ?>
					<?php if(!empty($allsettings['show_link_button'])){ ?>
					<div class="gmpm_big_button_wrap">
						<a class="gmpm_big_button gmpm_link_button<?php echo empty($content['data'][$iSlide]['link'])? ' inactive' : '' ?>" href="<?php echo $content['data'][$iSlide]['link']; ?>" target="<?php echo $allsettings['link_button_target']; ?>">
							<span class="gmpm_icon"></span>
							<span class="gmpm_label"><?php echo $allsettings['link_button_text']; ?></span>
						</a>
					</div>
					<?php } ?>
				</div>
				<div class="gmpm_focus_actions">
					<?php if(!empty($allsettings['show_like_button'])){ ?>
					<ul class="gmpm_focus_like_fave gmpm_clearfix">
						<li><a class="gmpm_button gmpm_like"><?php _e('Like', 'grand-media'); ?></a></li>
					</ul>
					<?php } ?>
					<ul class="gmpm_focus_arrows gmpm_clearfix">
						<li><a class="gmpm_button gmpm_photo_arrow_previous gmpm_prev"><?php _e('Previous', 'grand-media'); ?></a></li>
						<li><a class="gmpm_button gmpm_photo_arrow_next gmpm_next"><?php _e('Next', 'grand-media'); ?></a></li>
					</ul>
					<ul class="gmpm_focus_close_full gmpm_clearfix">
						<li><a class="gmpm_button gmpm_close"><?php _e('Close', 'grand-media'); ?></a></li>
						<li><a class="gmpm_button gmpm_full"><?php _e('Full', 'grand-media'); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php if(!empty($allsettings['show_description'])){ ?>
		<div class="gmpm_photo_details no-details-tab<?php echo empty($content['data'][$iSlide]['description'])? ' no-slide-description' : ''; ?>">
			<div class="gmpm_wrapper gmpm_clearfix">
				<div class="gmpm_description_wrap">
					<?php if(!empty($allsettings['description_title'])){ ?>
					<h2><?php echo $allsettings['description_title']; ?></h2>
					<?php } ?>

					<div class="gmpm_description_text_wrap">
						<div class="gmpm_slide_description"><?php echo $content['data'][$iSlide]['description']; ?></div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>

		<div class="gmpm_focus_footer">
			<div class="gmpm_focus_keyboard">
				<h6><?php _e('Keyboard Shortcuts', 'grand-media'); ?> <a class="gmpm_focus_keyboard_dismiss"><?php _e('Dismiss', 'grand-media'); ?></a></h6>
				<ul>
					<li><a data-key="p" class="gmpm_key">S</a><span class="gmpm_label"><?php _e('Slideshow', 'grand-media'); ?></span></li>
					<li><a data-key="m" class="gmpm_key">M</a><span class="gmpm_label"><?php _e('Maximize', 'grand-media'); ?></span></li>
					<li><a data-key="left" class="gmpm_key">&nbsp;</a><span class="gmpm_label"><?php _e('Previous', 'grand-media'); ?></span></li>
					<li><a data-key="right" class="gmpm_key">&nbsp;</a><span class="gmpm_label"><?php _e('Next', 'grand-media'); ?></span></li>
					<li><a data-key="escape" class="gmpm_key gmpm_esc">esc</a><span class="gmpm_label"><?php _e('Close', 'grand-media'); ?></span></li>
				</ul>
			</div>
		</div>

	</div>

	<?php
	if($shortcode_raw){ echo '<pre style="display:none">'; } ?>
	<script type="text/javascript">
		jQuery(function($){
			var settings = <?php echo $json_settings; ?>;
			var content = <?php echo json_encode($content); ?>;
			var container = $('#GmediaGallery_<?php echo $gallery['term_id'] ?>');
			container.photomanialite(settings, content);
			window.GmediaGallery_<?php echo $gallery['term_id'] ?> = container.data('photomanialite');
		});
	</script><?php if($shortcode_raw){ echo '</pre>'; } ?>
<?php
} else{
	echo GMEDIA_GALLERY_EMPTY;
}
