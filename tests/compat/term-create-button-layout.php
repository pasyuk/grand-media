<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Term create button layout test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

require_once __DIR__ . '/helpers/template-dom.php';

foreach ( array( 'album', 'category' ) as $taxonomy ) {
	$xpath   = gmedia_template_xpath( $root . '/admin/pages/terms/tpl/' . $taxonomy . '-create-item.php' );
	$buttons = $xpath->query(
		'//form[@id="gmedia-edit-term"]'
		. '//button[@type="submit"]'
		. '[contains(concat(" ", normalize-space(@class), " "), " gmedia-add-term ")]'
	);

	if ( 1 !== $buttons->length ) {
		$failures[] = ucfirst( $taxonomy ) . ' create form must render one height-aligned add button';
	}
}

$stylesheet = file_get_contents( $root . '/admin/assets/css/gmedia.admin.css' );
if ( false === $stylesheet ) {
	$failures[] = 'Could not read the admin stylesheet';
} elseif ( ! preg_match( '/#gmedia-edit-term\s+\.gmedia-add-term\s*\{[^}]*min-height\s*:\s*40px\s*;[^}]*\}/s', $stylesheet ) ) {
	$failures[] = 'Term create buttons must match the WordPress 40px form-control minimum height';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Term create button layout passed: Album and Category actions match adjacent controls.' . PHP_EOL;
