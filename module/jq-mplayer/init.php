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
if ( ! isset( $shortcode_raw ) ) {
	$shortcode_raw = false;
}
$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
$settings    = array_merge(
	$settings,
	array(
		'ID'         => $gallery['term_id'],
		'moduleUrl'  => $module['url'],
		'pluginUrl'  => $gmCore->gmedia_url,
		'libraryUrl' => $gmCore->upload['url'],
		'ajaxurl'    => admin_url( 'admin-ajax.php' ),
		'ip'         => str_replace( '.', '', $remote_addr ),
	)
);
$allsettings = array_merge( $module['options'], $settings );

$tab = sanitize_title( $gallery['name'] );
foreach ( $terms as $term ) {

	foreach ( $gmedia[ $term->term_id ] as $item ) {
		$ext = substr( $item->gmuid, - 3 );

		if ( ! in_array( $ext, array( 'mp3', 'ogg', 'wav', 'mp4' ), true ) ) {
			if ( 'webm' !== ( $ext = substr( $item->gmuid, - 4 ) ) ) {
				continue;
			}
		}
		if ( 'ogg' === $ext ) {
			$ext = 'oga';
		}
		$default_cover = '';

		$_metadata = $gmDB->get_metadata( 'gmedia', $item->ID, '_metadata', true );
		//		if(isset($_metadata['image']['data']) && !empty($_metadata['image']['data'])){
		//			$default_cover = $_metadata['image']['data'];
		//		} else {
		$albums = $gmDB->get_the_gmedia_terms( $item->ID, 'gmedia_album' );
		if ( ! empty( $albums ) ) {
			$album = reset( $albums );
			if ( ! empty( $album ) ) {
				$cover_id = $gmDB->get_metadata( 'gmedia_term', $album->term_id, '_cover', true );
				if ( (int) $cover_id ) {
					$default_cover = $gmCore->gm_get_media_image( $cover_id, 'thumb', true, $default_cover );
				}
			}
		}
		//		}
		$cover  = $gmCore->gm_get_media_image( $item, 'thumb', true, $default_cover );
		$rating = $gmDB->get_metadata( 'gmedia', $item->ID, '_rating', true );
		$rating = array_merge( array( 'value' => 0, 'votes' => 0 ), (array) $rating );
		$file   = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['audio']}/{$item->gmuid}";
		$button = $item->link;
		if ( ! $button && (int) $allsettings['downloadTrack'] ) {
			$button = $file;
		}
		$content[] = array(
			'id'     => $item->ID,
			$ext     => $file,
			'cover'  => $cover,
			'title'  => stripslashes( $item->title ),
			'text'   => str_replace( array( "\r\n", "\r", "\n" ), '', wpautop( $item->description ) ),
			'button' => $button,
			'rating' => $rating['value'],
			'votes'  => $rating['votes'],
		);
	}
}

if ( ! empty( $content ) ) {
	$jqmp_autoplay_setting = intval( $allsettings['autoplay'] );
	if ( $jqmp_autoplay_setting ) {
		$gmedia_shortcode_instance['music_autoplay'] = isset( $gmedia_shortcode_instance['music_autoplay'] ) ? $gmedia_shortcode_instance['music_autoplay'] + 1 : 0;
		if ( $gmedia_shortcode_instance['music_autoplay'] ) {
			$settings['autoplay'] = '0';
		}
	}

	if ( $shortcode_raw ) {
		echo '<pre style="display:none">';
	}
	?>
	<script type="text/javascript">
			document.addEventListener( 'DOMContentLoaded', function() {
				jQuery( function() {
					var settings = <?php echo wp_json_encode( $settings ); ?>;
					var content = <?php echo wp_json_encode( $content ); ?>;
					jQuery( '#GmediaGallery_<?php echo esc_attr( $gallery['term_id'] ); ?>' ).data( 'uid', '<?php echo esc_attr( $gallery['term_id'] ); ?>' ).gmMusicPlayer( content, settings );
				} );
			} );
	</script>
	<?php
	if ( $shortcode_raw ) {
		echo '</pre>';
	}
} else {
	echo esc_html( GMEDIA_GALLERY_EMPTY );
}
