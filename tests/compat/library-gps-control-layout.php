<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Library GPS control layout test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

require_once __DIR__ . '/helpers/template-dom.php';

$xpath   = gmedia_template_xpath( $root . '/admin/pages/library/tpl/edit-item.php' );
$buttons = $xpath->query(
	'//input[contains(concat(" ", normalize-space(@class), " "), " gps_map_coordinates ")]'
	. '/following-sibling::span[contains(concat(" ", normalize-space(@class), " "), " input-group-btn ")]'
	. '/a[contains(concat(" ", normalize-space(@class), " "), " gmedit-modal ")]'
);

if ( 1 !== $buttons->length ) {
	$failures[] = 'Library edit items must render one GPS map button beside its field';
} else {
	$button_classes = preg_split( '/\s+/', trim( $buttons->item( 0 )->getAttribute( 'class' ) ) );
	if ( ! in_array( 'h-100', $button_classes, true ) ) {
		$failures[] = 'The GPS map button must fill the height of its input group';
	}
	foreach ( array( 'd-inline-flex', 'align-items-center', 'justify-content-center' ) as $required_class ) {
		if ( ! in_array( $required_class, $button_classes, true ) ) {
			$failures[] = 'The GPS map button must center its icon with Bootstrap flex utilities';
			break;
		}
	}
}

$bootstrap = file_get_contents( $root . '/assets/bootstrap/css/bootstrap.css' );
if ( false === $bootstrap ) {
	$failures[] = 'Could not read the Bootstrap stylesheet';
} else {
	foreach (
		array(
			'/\.h-100\s*\{[^}]*height\s*:\s*100%\s*!important\s*;[^}]*\}/s'                       => 'provide the full-height utility',
			'/\.d-inline-flex\s*\{[^}]*display\s*:\s*inline-flex\s*!important\s*;[^}]*\}/s'         => 'provide the inline-flex display utility',
			'/\.align-items-center\s*\{[^}]*align-items\s*:\s*center\s*!important\s*;[^}]*\}/s'      => 'provide the vertical centering utility',
			'/\.justify-content-center\s*\{[^}]*justify-content\s*:\s*center\s*!important\s*;[^}]*\}/s' => 'provide the horizontal centering utility',
		) as $pattern => $requirement
	) {
		if ( ! preg_match( $pattern, $bootstrap ) ) {
			$failures[] = 'The bundled Bootstrap stylesheet must ' . $requirement;
		}
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Library GPS control layout passed: the map button fills the field height and centers its icon.' . PHP_EOL;
