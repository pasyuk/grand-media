<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Color swatch alignment test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

$css = file_get_contents( $root . '/admin/assets/css/gmedia.admin.css' );
if ( false === $css ) {
	$failures[] = 'Could not read the admin stylesheet';
} elseif ( ! preg_match( '/#gallery_options_block\s+\.minicolors-theme-bootstrap\s+\.minicolors-swatch\s*\{([^}]*)\}/', $css, $matches ) ) {
	$failures[] = 'Gallery color swatches must have a scoped alignment rule';
} else {
	$rules = $matches[1];
	if ( ! preg_match( '/(?:^|;)\s*top\s*:\s*50%\s*;/', $rules ) ) {
		$failures[] = 'Gallery color swatches must start at the input midpoint';
	}
	if ( ! preg_match( '/(?:^|;)\s*transform\s*:\s*translateY\(\s*-50%\s*\)\s*;/', $rules ) ) {
		$failures[] = 'Gallery color swatches must offset themselves by half their height';
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Color swatch alignment passed: gallery previews share the input midpoint.' . PHP_EOL;
