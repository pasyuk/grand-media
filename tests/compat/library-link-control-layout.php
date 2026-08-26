<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Library link control layout test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

require_once __DIR__ . '/helpers/template-dom.php';

$xpath   = gmedia_template_xpath( $root . '/admin/pages/library/tpl/edit-item.php' );
$buttons = $xpath->query(
	'//input[contains(concat(" ", normalize-space(@class), " "), " gmedia-custom-link-field ")]'
	. '/following-sibling::span[contains(concat(" ", normalize-space(@class), " "), " input-group-btn ")]'
	. '/button[contains(concat(" ", normalize-space(@class), " "), " gmedia-custom-link ")]'
);

if ( 1 !== $buttons->length ) {
	$failures[] = 'Library edit items must render one Link URL button beside its field';
} elseif ( ! preg_match( '/(?:^|\s)h-100(?:\s|$)/', $buttons->item( 0 )->getAttribute( 'class' ) ) ) {
	$failures[] = 'The Link URL button must fill the height of its input group';
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
echo 'Library link control layout passed: the button fills the adjacent field height.' . PHP_EOL;
