<?php

$root    = dirname( __DIR__, 2 );
$wp_root = dirname( $root, 3 );

$completed = false;
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Module output escaping test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

define( 'ABSPATH', $wp_root . '/' );
define( 'WPINC', 'wp-includes' );
define( 'GMEDIA_GALLERY_EMPTY', 'No Supported Files in Gallery' );

function apply_filters( $hook_name, $value ) {
	return $value;
}

function is_utf8_charset() {
	return true;
}

function get_option( $name, $default = false ) {
	return 'blog_charset' === $name ? 'UTF-8' : $default;
}

function _canonical_charset( $charset ) {
	return $charset;
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_allowed_protocols() {
	return array( 'http', 'https', 'mailto', 'ftp', 'ftps', 'tel' );
}

require_once ABSPATH . WPINC . '/compat.php';
require_once ABSPATH . WPINC . '/utf8.php';
require_once ABSPATH . WPINC . '/formatting.php';
require_once ABSPATH . WPINC . '/kses.php';
require_once ABSPATH . WPINC . '/http.php';
require_once ABSPATH . WPINC . '/class-wp-token-map.php';
foreach ( array( 'html5-named-character-references', 'class-wp-html-attribute-token', 'class-wp-html-span', 'class-wp-html-doctype-info', 'class-wp-html-text-replacement', 'class-wp-html-decoder', 'class-wp-html-tag-processor' ) as $html_api_file ) {
	require_once ABSPATH . WPINC . '/html-api/' . $html_api_file . '.php';
}

function __( $text, $domain = 'default' ) {
	if ( 'Share' === $text ) {
		return 'Share <script>alert("translation")</script>';
	}
	if ( 'Load more' === $text ) {
		return 'Load "more" & next';
	}
	if ( str_contains( $text, 'Responsive' ) || str_contains( $text, 'This module' ) ) {
		return $text . '<script>alert("module-info")</script>';
	}

	return $text;
}

function _e( $text, $domain = 'default' ) {
	echo __( $text, $domain );
}

function esc_html__( $text, $domain = 'default' ) {
	return esc_html( __( $text, $domain ) );
}

function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( __( $text, $domain ) );
}

function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html__( $text, $domain );
}

function home_url( $path = '' ) {
	return 'https://example.test/' . ltrim( $path, '/' );
}

function add_query_arg( $args, $url = '' ) {
	if ( ! is_array( $args ) ) {
		return $url;
	}
	$query = http_build_query( $args, '', '&' );

	return rtrim( $url, '?' ) . ( str_contains( $url, '?' ) ? '&' : '?' ) . $query;
}

function remove_query_arg( $key, $url = '' ) {
	return $url ?: 'https://example.test/gallery?keep=one';
}

function get_permalink( $post_id ) {
	return 'https://example.test/post/' . (int) $post_id . '?left=1&right=2';
}

function get_the_author_meta( $field, $author_id ) {
	return 'Author <svg onload="alert(1)"></svg>';
}

function get_author_posts_url( $author_id ) {
	return 'https://example.test/author/' . (int) $author_id . '?left=1&right=2';
}

function get_avatar( $author_id, $size ) {
	return '<img src="https://example.test/avatar.jpg?left=1&amp;right=2" alt="">';
}

function wp_count_comments( $post_id ) {
	return (object) array( 'approved' => 7 );
}

function gmedia_item_more_data( &$item ) {
	global $gmDB, $gmCore, $gmGallery;
	$metadata            = $gmDB->get_metadata( 'gmedia', $item->ID );
	$item->meta          = $metadata;
	$item->type          = 'image';
	$item->ext           = 'jpg';
	$item->url           = 'https://example.test/media.jpg?left=1&right=2';
	$item->url_thumb     = 'https://example.test/thumb.jpg?left=1&right=2';
	$item->url_web       = 'https://example.test/web.jpg?left=1&right=2';
	$item->url_original  = 'https://example.test/original.jpg?left=1&right=2';
	$item->alttext       = 'Alt "quoted" <script>alert(1)</script>';
	$item->gps           = '';
	$item->img_ratio     = 1.5;
	$item->thumb_width   = 300;
	$item->thumb_height  = 200;
	$item->tags          = array( (object) array( 'term_id' => 10, 'name' => 'Tag <img src=x onerror=alert(1)>' ) );
	$item->categories    = array( (object) array( 'term_id' => 11, 'name' => 'Category <script>alert(1)</script>' ) );
	$item->album         = array( (object) array( 'term_id' => 12, 'name' => 'Album <script>alert(1)</script>' ) );
}

final class Gmedia_Module_Output_DB {
	public $openPage = 1;
	public $pages    = 2;
	public $count    = 1;

	public function get_gmedias( $query ) {
		$items = array();
		for ( $i = 0; $i < $this->count; $i++ ) {
			$item       = clone $GLOBALS['gmedia_output_item'];
			$item->ID  += $i;
			$items[]    = $item;
		}

		return $items;
	}

	public function get_metadata( $type, $id ) {
		return array(
			'_metadata' => array(
				array(
					'width'  => 900,
					'height' => 600,
					'web'    => array( 'width' => 900, 'height' => 600 ),
				),
			),
			'_image_alt' => array( 'Photo alt "quoted"' ),
			'views'      => array( 4 ),
			'likes'      => array( 5 ),
			'download'   => array( 'https://example.test/download.jpg?left=1&right=2' ),
			'link_target' => array( '_blank' ),
		);
	}
}

final class Gmedia_Module_Output_Core {
	public $upload = array(
		'url'  => 'https://example.test/uploads',
		'path' => '/tmp/uploads',
	);

	public function _get( $key, $default = false ) {
		return $default;
	}

	public function gm_get_media_image( $item, $size ) {
		return "https://example.test/{$size}.jpg?left=1&right=2";
	}

	public function gmcloudlink( $id, $context ) {
		return 'https://example.test/cloud/' . (int) $id;
	}
}

function render_gmedia_module( $module_base, $is_bot = false, $shortcode_raw = false, $overrides = array() ) {
	global $gmDB, $gmCore, $gmGallery, $wp;

	$id            = 17;
	$query         = array( 'per_page' => 10 );
	$settings      = $overrides;
	$terms         = array();
	$gmedia        = array();
	$customCSS     = '';
	$atts          = array();
	$term          = null;
	$module        = array(
		'url'     => "https://example.test/modules/{$module_base}",
		'options' => array(),
	);

	include dirname( __DIR__, 2 ) . "/module/{$module_base}/settings.php";
	$module['options'] = $default_options;
	if ( 'cubik-lite' === $module_base ) {
		$module['options']['thumbCols'] = 1;
	}

	ob_start();
	include dirname( __DIR__, 2 ) . "/module/{$module_base}/init.php";

	return ob_get_clean();
}

function render_gmedia_module_info( $module_base ) {
	$_SERVER['PHP_SELF'] = "/module/{$module_base}/index.php";
	$_GET['info']        = 1;
	ob_start();
	include dirname( __DIR__, 2 ) . "/module/{$module_base}/index.php";
	$output = ob_get_clean();
	unset( $_GET['info'] );

	return $output;
}

function module_output_attribute( $output, $class, $attribute ) {
	$tags = new WP_HTML_Tag_Processor( $output );
	if ( $tags->next_tag( array( 'class_name' => $class ) ) ) {
		return $tags->get_attribute( $attribute );
	}

	return null;
}

$GLOBALS['gmedia_output_item'] = (object) array(
	'ID'          => 101,
	'post_id'     => 202,
	'author'      => 303,
	'mime_type'   => 'image/jpeg',
	'gmuid'       => 'photo.jpg',
	'title'       => '<strong>Safe title</strong><script>alert("title")</script>',
	'description' => '<p>Safe <em>description</em></p><script>alert("description")</script>',
	'link'        => 'https://example.test/item?left=1&right=2',
);
$gmDB      = new Gmedia_Module_Output_DB();
$gmCore    = new Gmedia_Module_Output_Core();
$gmGallery = (object) array( 'options' => array( 'folder' => array( 'image' => 'image' ) ) );
$wp        = (object) array( 'request' => 'gallery' );
$_SERVER['QUERY_STRING'] = 'left=1&right=2';

$failures = array();
foreach ( array( 'cubik-lite', 'phantom', 'photomania' ) as $module_base ) {
	$output = render_gmedia_module( $module_base );
	$markup = preg_replace( '#<script type="text/javascript">.*?</script>#s', '', $output );
	$tags   = new WP_HTML_Tag_Processor( $markup );
	$unsafe_attribute = false;
	while ( $tags->next_tag() ) {
		if ( $tags->get_attribute_names_with_prefix( 'on' ) ) {
			$unsafe_attribute = true;
		}
	}
	if ( str_contains( $markup, '<script>alert(' ) || $unsafe_attribute ) {
		$failures[] = "{$module_base}: executable fixture markup leaked into rendered output";
	}
	if ( ! str_contains( $output, '<strong>Safe title</strong>' ) ) {
		$failures[] = "{$module_base}: allowed rich title markup was not preserved";
	}
	if ( ! str_contains( $output, '<em>description</em>' ) ) {
		$failures[] = "{$module_base}: allowed rich description markup was not preserved";
	}
	if ( preg_match( '/\?left=1&right=2[\x22\x27]/', $markup ) ) {
		$failures[] = "{$module_base}: URL ampersand was not escaped for an HTML attribute";
	}
	if ( ! str_contains( $output, '<script type="text/javascript">' ) ) {
		$failures[] = "{$module_base}: executable module initialization script was removed";
	}

	$info = render_gmedia_module_info( $module_base );
	if ( str_contains( $info, '<script>alert("module-info")</script>' ) ) {
		$failures[] = "{$module_base}: module information preformatted output was not escaped";
	}
}

$photomania = render_gmedia_module( 'photomania' );
if ( str_contains( $photomania, 'Share <script>alert("translation")</script>' ) ) {
	$failures[] = 'photomania: translated text-node markup was not escaped';
}
if ( ! preg_match( '/var content = (?P<json>\{.*?\});/s', $photomania, $matches ) ) {
	$failures[] = 'photomania: serialized content was not found';
} else {
	$content = json_decode( $matches['json'], true );
	if ( JSON_ERROR_NONE !== json_last_error() ) {
		$failures[] = 'photomania: serialized content was not valid JSON';
	} elseif ( str_contains( $content['data'][0]['description'], '<script' ) || ! str_contains( $content['data'][0]['description'], '<em>description</em>' ) ) {
		$failures[] = 'photomania: serialized description did not preserve only allowed rich HTML';
	}
}
if ( ! str_contains( $photomania, '<span class="gmpm_comments_count">7</span>' ) ) {
	$failures[] = 'photomania: comments count rendering changed';
}

$attribute_cases = array(
	array( $photomania, 'gmpm_the_photo', 'data-src', 'https://example.test/web.jpg?left=1&right=2' ),
	array( $photomania, 'gmpm_the_photo', 'data-protect', 'Author <svg onload="alert(1)"></svg>' ),
	array( $photomania, 'swiper-slide', 'data-photo-id', '101' ),
	array( $photomania, 'swiper-slide', 'data-hash', 'gmedia101' ),
	array( $photomania, 'gmpm_comments_button', 'href', 'https://example.test/post/202?left=1&right=2#comments' ),
	array( $photomania, 'gmpm_download_button', 'download', 'photo.jpg' ),
	array( $photomania, 'gmpm_link_button', 'href', 'https://example.test/item?left=1&right=2' ),
	array( $photomania, 'gmpm_link_button', 'target', '_blank' ),
);
foreach ( $attribute_cases as $case ) {
	list( $output, $class, $attribute, $expected ) = $case;
	if ( $expected !== module_output_attribute( $output, $class, $attribute ) ) {
		$failures[] = "photomania: {$class} {$attribute} lost its original decoded value";
	}
}

$phantom = render_gmedia_module( 'phantom' );
if ( 'Load "more" & next' !== module_output_attribute( $phantom, 'gmPhantom_pager', 'title' ) || ! str_contains( $phantom, '<span class="gmPhantom_page">2</span>' ) ) {
	$failures[] = 'phantom: pagination title or next-page number changed';
}
foreach ( array( 'cubik-lite' => 'gmCubikLite_thumb', 'phantom' => 'gmPhantom_ThumbContainer' ) as $module_base => $class ) {
	$output = render_gmedia_module( $module_base );
	if ( 'https://example.test/item?left=1&right=2' !== module_output_attribute( $output, $class, 'data-link' ) || '101' !== module_output_attribute( $output, $class, 'data-id' ) ) {
		$failures[] = "{$module_base}: pre-escaped data attributes were lost or double escaped";
	}
	if ( str_contains( render_gmedia_module( $module_base, true ), '<script type="text/javascript">' ) ) {
		$failures[] = "{$module_base}: bot rendering unexpectedly initialized JavaScript";
	}
}

$gmDB->count = 7;
$cubik       = render_gmedia_module( 'cubik-lite' );
if ( 7 !== substr_count( $cubik, '<script type="text/html" class="gmCubikLite_thumbDetails">' ) || ! str_contains( $cubik, 'class="gmCubikLite_noplace"' ) ) {
	$failures[] = 'cubik-lite: face and overflow HTML templates were not preserved';
}
$gmDB->count = 1;

$GLOBALS['gmedia_output_item']->post_id = 0;
$inactive = render_gmedia_module( 'photomania' );
if ( ! str_contains( $inactive, '<span class="gmpm_comments_count"></span>' ) || null !== module_output_attribute( $inactive, 'gmpm_comments_button', 'href' ) ) {
	$failures[] = 'photomania: inactive comments must remain blank and have no link';
}
$GLOBALS['gmedia_output_item']->post_id = 202;

foreach ( array( 'cubik-lite', 'phantom', 'photomania' ) as $module_base ) {
	$raw = render_gmedia_module( $module_base, false, true );
	if ( ! str_contains( $raw, '<pre style="display:none">' ) || ! str_contains( $raw, '</pre>' ) ) {
		$failures[] = "{$module_base}: raw-shortcode script wrapper was removed";
	}
	$gmDB->count = 0;
	if ( GMEDIA_GALLERY_EMPTY !== render_gmedia_module( $module_base ) ) {
		$failures[] = "{$module_base}: empty-gallery rendering changed";
	}
	$gmDB->count = 1;
}

$labels = array(
	'comments_button_text' => 'Discuss <b>now</b>',
	'download_button_text' => 'Download <b>now</b>',
	'link_button_text'     => 'Open <b>now</b>',
	'description_title'   => 'Details <b>here</b>',
);
$label_output = render_gmedia_module( 'photomania', false, false, $labels );
if ( str_contains( $label_output, '<b>now</b>' ) || str_contains( $label_output, '<b>here</b>' ) || ! str_contains( $label_output, 'Discuss &lt;b&gt;now&lt;/b&gt;' ) ) {
	$failures[] = 'photomania: configurable text labels were not escaped';
}

$completed = true;
if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo 'Module output escaping contracts passed.' . PHP_EOL;
