<?php

/**
 * Guards the fix for the anonymous gmedia_get_data authorization issue.
 *
 * Two independent failures let a logged-out visitor read non-published media:
 *   1. An author allow-list that narrowed to empty was treated by the query
 *      layer as "no author filter", so it returned every author's media.
 *   2. The library query took `status` straight from the request, so an
 *      anonymous caller was never restricted to published media.
 *
 * These are source-level guards: the end-to-end behaviour is verified against a
 * live site (see the PR), but this keeps the security-critical lines from being
 * reverted in a plain `php tests/compat/*.php` run with no WordPress available.
 */

$root = dirname( __DIR__, 2 );

$processor_code = file_get_contents( $root . '/admin/class.processor.php' );
$library_code   = file_get_contents( $root . '/admin/processor/class.processor.library.php' );

$failures = array();

if ( false === $processor_code ) {
	$failures[] = 'Could not read admin/class.processor.php';
}

if ( false === $library_code ) {
	$failures[] = 'Could not read admin/processor/class.processor.library.php';
}

if ( ! $failures ) {
	if ( ! preg_match( '/function\s+filter_by_author\s*\([^)]*\)\s*\{(?P<body>.*?)^\t\}/ms', $processor_code, $m ) ) {
		$failures[] = 'Could not find filter_by_author() body';
	} else {
		$body = $m['body'];

		// After intersecting with the caller's own scope, an empty result must
		// fall back to that scope, never stay empty (which reads as "no filter").
		if ( false === strpos( $body, 'array_intersect( array( $user_ID, 0 ), $author )' ) ) {
			$failures[] = 'filter_by_author() must still intersect requested authors with the caller scope';
		}

		if ( ! preg_match( '/if\s*\(\s*empty\(\s*\$author\s*\)\s*\)\s*\{.*?\$author\s*=\s*array\(\s*\$user_ID,\s*0\s*\);/s', $body ) ) {
			$failures[] = 'filter_by_author() must fall back to the caller scope when the intersection is empty (fail closed, not open)';
		}
	}

	// The library query must not let an unauthenticated caller choose the status.
	if ( ! preg_match( '/\$args\[\s*[\'"]status[\'"]\s*\]\s*=\s*is_user_logged_in\(\)\s*\?\s*\$gmCore->_get\(\s*[\'"]status[\'"]\s*\)\s*:\s*[\'"]publish[\'"]/', $library_code ) ) {
		$failures[] = 'query_args() must force status=publish for anonymous (not logged-in) callers';
	}
}

if ( $failures ) {
	echo implode( PHP_EOL, $failures ) . PHP_EOL;
	exit( 1 );
}

echo 'Anonymous media authorization guard passed.' . PHP_EOL;
