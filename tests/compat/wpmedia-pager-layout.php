<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'WP Media Library pager layout test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

define( 'ABSPATH', $root . '/' );

function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function esc_html__( $text ) {
	return esc_html( $text );
}
function __( $text ) {
	return $text;
}

require_once $root . '/inc/db.connect.php';

$_GET = array( 'page' => 'GrandMedia_WordpressLibrary' );
$db   = new GmediaDB();
$db->pages    = 9;
$db->openPage = 1;

$html = '<div class="panel-fixed-header"><div class="card-header">' . $db->query_pager() . '</div></div>';

$document = new DOMDocument();
libxml_use_internal_errors( true );
$document->loadHTML( $html );
libxml_clear_errors();
$xpath = new DOMXPath( $document );

$pager_fields = $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " panel-fixed-header ")]/*[contains(concat(" ", normalize-space(@class), " "), " card-header ")]//*[contains(concat(" ", normalize-space(@class), " "), " gmedia-pager ")]//input[contains(concat(" ", normalize-space(@class), " "), " pager_current_page ")]' );
if ( 1 !== $pager_fields->length ) {
	$failures[] = 'WP Media Library must render one current-page field in its fixed toolbar';
}

$css = file_get_contents( $root . '/admin/assets/css/gmedia.admin.css' );
if ( false === $css ) {
	$failures[] = 'Could not read the admin stylesheet';
} elseif ( ! preg_match( '/form#gmedia-pager\s+input\.pager_current_page\s*\{([^}]*)\}/', $css, $matches ) ) {
	$failures[] = 'Current-page fields must define pager-specific sizing';
} elseif ( ! preg_match( '/min-height\s*:\s*0\s*;/', $matches[1] ) ) {
	$failures[] = 'Current-page fields must remove the wp-admin minimum height';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'WP Media Library pager layout passed: current-page field uses compact toolbar sizing.' . PHP_EOL;
