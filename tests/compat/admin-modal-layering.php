<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Admin modal layering test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

require_once __DIR__ . '/helpers/template-dom.php';

$xpath = gmedia_template_xpath( $root . '/admin/pages/library/library.php' );
$modal         = $xpath->query( '//*[@id="gmeditModal" and contains(concat(" ", normalize-space(@class), " "), " gmedia-modal ")]' );
$preview_modal = $xpath->query( '//*[@id="previewModal" and contains(concat(" ", normalize-space(@class), " "), " gmedia-modal ")]' );

if ( 1 !== $modal->length ) {
	$failures[] = 'The map editor must render inside the shared Gmedia admin modal';
}

if ( 1 !== $preview_modal->length ) {
	$failures[] = 'The large-image preview must render inside the shared Gmedia admin modal';
}

$css = file_get_contents( $root . '/admin/assets/css/gmedia.admin.css' );
if ( false === $css ) {
	$failures[] = 'Could not read the admin stylesheet';
} else {
	foreach (
		array(
			'/body\.grand-media-admin-page\s*>\s*\.modal-backdrop\s*\{[^}]*z-index\s*:\s*159900\s*;[^}]*\}/s' => 'place the Bootstrap backdrop on the WordPress media backdrop layer',
			'/body\.grand-media-admin-page\s*>\s*\.gmedia-modal\s*\{[^}]*z-index\s*:\s*160000\s*;[^}]*\}/s'  => 'place Gmedia modals on the WordPress media modal layer',
		) as $pattern => $requirement
	) {
		if ( ! preg_match( $pattern, $css ) ) {
			$failures[] = 'Admin modal styles must ' . $requirement;
		}
	}

	if ( preg_match( '/#previewModal\s*\{[^}]*z-index\s*:/s', $css ) ) {
		$failures[] = 'The large-image preview must not override the shared Gmedia modal layer';
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Admin modal layering passed: Gmedia dialogs cover the WordPress admin chrome.' . PHP_EOL;
