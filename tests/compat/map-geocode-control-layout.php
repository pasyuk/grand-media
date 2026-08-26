<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Map geocode control layout test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

define( 'ABSPATH', $root . '/' );

function esc_attr_e( $text ) {
	echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html_e( $text ) {
	echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}
function esc_js( $text ) {
	return addslashes( (string) $text );
}
function wp_nonce_field() {}

$gmCore = new class() {
	public function _get() {
		return 1;
	}
};
$gmDB   = new class() {
	public function get_metadata() {
		return array();
	}
};

require_once $root . '/inc/map-editor.php';

ob_start();
gmedia_map_editor();
$html = ob_get_clean();

$document = new DOMDocument();
libxml_use_internal_errors( true );
$document->loadHTML( $html );
libxml_clear_errors();
$xpath = new DOMXPath( $document );

$buttons = $xpath->query(
	'//*[@id="map-floating-panel"]'
	. '//input[@id="geocode_address"]'
	. '/following-sibling::span[contains(concat(" ", normalize-space(@class), " "), " input-group-btn ")]'
	. '/button[@id="geocode_submit"]'
);

if ( 1 !== $buttons->length ) {
	$failures[] = 'The map editor must render one Geocode button beside its address field';
} elseif ( ! preg_match( '/(?:^|\s)h-100(?:\s|$)/', $buttons->item( 0 )->getAttribute( 'class' ) ) ) {
	$failures[] = 'The nested Geocode button must fill the height of its input group';
}

$bootstrap = file_get_contents( $root . '/assets/bootstrap/css/bootstrap.css' );
if ( false === $bootstrap ) {
	$failures[] = 'Could not read the Bootstrap stylesheet';
} elseif ( ! preg_match( '/\.h-100\s*\{[^}]*height\s*:\s*100%\s*!important\s*;[^}]*\}/s', $bootstrap ) ) {
	$failures[] = 'The bundled Bootstrap stylesheet must provide the full-height utility';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Map geocode control layout passed: the button fills the address field height.' . PHP_EOL;
