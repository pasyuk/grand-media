<?php

$root = dirname( __DIR__, 2 );
$stylesheets = array(
	'assets/photoswipe/photoswipe.css',
	'module/cubik-lite/css/style.css',
	'module/phantom/css/style.css',
	'module/photomania/css/styles.photomania.css',
);

$expected_assets = 21;
$resolved_assets = array();
$failures        = array();

foreach ( $stylesheets as $stylesheet ) {
	$css = file_get_contents( $root . '/' . $stylesheet );
	if ( false === $css ) {
		$failures[] = 'Could not read ' . $stylesheet;
		continue;
	}

	preg_match_all( "~url\\(['\"]?([^)'\"]+-2x\\.png)['\"]?\\)~", $css, $matches );
	foreach ( $matches[1] as $url ) {
		$path = realpath( dirname( $root . '/' . $stylesheet ) . '/' . $url );
		if ( false === $path || ! is_file( $path ) ) {
			$failures[] = sprintf( '%s does not resolve from %s', $url, $stylesheet );
			continue;
		}

		$image = getimagesize( $path );
		if ( false === $image || IMAGETYPE_PNG !== $image[2] ) {
			$failures[] = $url . ' is not a readable PNG';
			continue;
		}
		$resolved_assets[ $path ] = true;
	}
}

if ( $expected_assets !== count( $resolved_assets ) ) {
	$failures[] = sprintf( 'Expected %d distinct renamed PNGs, resolved %d', $expected_assets, count( $resolved_assets ) );
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo 'Renamed retina assets resolve as 21 readable PNGs.' . PHP_EOL;
