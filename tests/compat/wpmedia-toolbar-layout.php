<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'WP Media Library toolbar layout test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

define( 'ABSPATH', $root . '/' );
define( 'GMEDIA_ABSPATH', $root . '/' );

function get_user_meta() {
	return array();
}
function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( $args );
}
function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . $path;
}
function esc_url( $url ) {
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function absint( $value ) {
	return abs( (int) $value );
}
function esc_attr_e( $text ) {
	echo esc_attr( $text );
}
function esc_html_e( $text ) {
	echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}
function __( $text ) {
	return $text;
}
function wp_kses( $html ) {
	return $html;
}
function wp_kses_post( $html ) {
	return $html;
}
function do_action() {}
function wp_original_referer_field() {}
function wp_nonce_field() {}

$user_ID         = 1;
$gm_allowed_tags = array();
$gmGallery       = (object) array(
	'options' => array(
		'gm_screen_options' => array(
			'orderby_wpmedia'   => 'ID',
			'sortorder_wpmedia' => 'DESC',
			'per_page_wpmedia'  => 20,
		),
	),
);
$gmProcessor     = (object) array(
	'page'           => 'GrandMedia_WordpressLibrary',
	'gmediablank'    => false,
	'selected_items' => array(),
);
$gmCore          = new class() {
	public $caps = array( 'gmedia_import' => true );
	public function _get( $key, $default = '' ) {
		return $default;
	}
	public function _req( $key, $default = '' ) {
		return $default;
	}
	public function get_admin_url() {
		return 'https://example.test/wp-admin/admin.php?page=GrandMedia_WordpressLibrary';
	}
};
$gmDB            = new class() {
	public $filter = array();
	public function get_wp_media_lib() {
		return array();
	}
	public function query_pager() {
		return '<form id="gmedia-pager"></form>';
	}
	public function count_wp_media() {
		return array_fill_keys( array( 'total', 'image', 'audio', 'video', 'text', 'application', 'other' ), 0 );
	}
};

$_GET = array();
require_once $root . '/admin/wpmedia.php';

ob_start();
grandWPMedia();
$html = ob_get_clean();

$document = new DOMDocument();
libxml_use_internal_errors( true );
$document->loadHTML( $html );
libxml_clear_errors();
$xpath = new DOMXPath( $document );

$search_fields = $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " panel-fixed-header ")]/*[contains(concat(" ", normalize-space(@class), " "), " card-header ")]//form[contains(concat(" ", normalize-space(@class), " "), " gmedia-search-form ")]//input[@id="gmedia-search"]' );
if ( 1 !== $search_fields->length ) {
	$failures[] = 'WP Media Library search field must render in the fixed panel toolbar';
}

$css = file_get_contents( $root . '/admin/assets/css/gmedia.admin.css' );
if ( false === $css ) {
	$failures[] = 'Could not read the admin stylesheet';
} elseif ( ! preg_match( '/\.panel-fixed-header\s*>\s*\.card-header\s+\.gmedia-search-form\s+input\.form-control\s*\{([^}]*)\}/', $css, $matches ) ) {
	$failures[] = 'Fixed panel search fields must define a toolbar sizing rule';
} else {
	$rules = $matches[1];
	foreach (
		array(
			'/min-height\s*:\s*0\s*;/'                  => 'remove the wp-admin minimum height',
			'/height\s*:\s*auto\s*;/'                   => 'use content-based height',
			'/padding-block\s*:\s*0\.15rem\s*;/'       => 'match the toolbar button padding',
			'/font-size\s*:\s*15px\s*!important\s*;/' => 'match the toolbar button font size',
		) as $pattern => $requirement
	) {
		if ( ! preg_match( $pattern, $rules ) ) {
			$failures[] = 'Fixed panel search fields must ' . $requirement;
		}
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'WP Media Library toolbar layout passed: search field uses the panel sizing scope.' . PHP_EOL;
