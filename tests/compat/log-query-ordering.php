<?php

$root = dirname( __DIR__, 2 );

// An early exit in an included file must fail, not silently skip assertions.
$completed = false;
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Log query ordering test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);
define( 'ABSPATH', $root . '/' );

require_once $root . '/admin/functions.php';

if ( ! function_exists( 'gmedia_log_orderby_sql' ) ) {
	fwrite( STDERR, 'gmedia_log_orderby_sql() is missing' . PHP_EOL );
	exit( 1 );
}

$cases = array(
	array( 'log_date', 'DESC', 'ORDER BY l.log_date DESC', 'default date ordering' ),
	array( 'ID', 'ASC', 'ORDER BY l.ID ASC', 'ID ordering' ),
	array( 'author', 'ASC', 'ORDER BY l.log_author ASC', 'author ordering maps to log_author' ),
	array( 'log_date DESC; DROP TABLE posts', 'ASC', 'ORDER BY l.log_date ASC', 'unknown column falls back safely' ),
	array( 'ID', 'DESC, ID', 'ORDER BY l.ID DESC', 'unknown direction falls back safely' ),
	array( array( 'ID' ), array( 'ASC' ), 'ORDER BY l.log_date DESC', 'non-string values fall back safely' ),
);

$failures = array();

foreach ( $cases as $case ) {
	list( $orderby, $sortorder, $expected, $label ) = $case;
	$actual = gmedia_log_orderby_sql( $orderby, $sortorder );

	if ( $expected !== $actual ) {
		$failures[] = sprintf( 'Expected %s for %s, got %s', $expected, $label, $actual );
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Log query ordering guard passed.' . PHP_EOL;
