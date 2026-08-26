<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Library order input layout test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

require_once __DIR__ . '/helpers/template-dom.php';

$xpath        = gmedia_template_xpath( $root . '/admin/pages/library/tpl/edit-item.php' );
$order_inputs = $xpath->query( '//input[contains(concat(" ", normalize-space(@class), " "), " gm-order-input ")]' );
if ( 1 !== $order_inputs->length ) {
	$failures[] = 'Library edit items must render one album order input';
}

$css = file_get_contents( $root . '/admin/assets/css/gmedia.admin.css' );
if ( false === $css ) {
	$failures[] = 'Could not read the admin stylesheet';
} elseif ( ! preg_match( '/input\.gm-order-input\s*\{([^}]*)\}/', $css, $matches ) ) {
	$failures[] = 'Album order inputs must define their compact sizing';
} else {
	$rules = $matches[1];
	foreach (
		array(
			'/min-height\s*:\s*0\s*;/'    => 'remove the wp-admin minimum height',
			'/height\s*:\s*auto\s*;/'     => 'use content-based height',
			'/padding\s*:\s*1px\s+3px\s*;/' => 'keep minimal padding',
		) as $pattern => $requirement
	) {
		if ( ! preg_match( $pattern, $rules ) ) {
			$failures[] = 'Album order inputs must ' . $requirement;
		}
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Library order input layout passed: the field keeps its compact natural height.' . PHP_EOL;
