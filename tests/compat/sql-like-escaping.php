<?php

$root = dirname( __DIR__, 2 );

$checks = array(
	'admin/logs.php'     => array(
		'forbidden_regex' => array(
			'/\$search_term\s*=\s*wp_slash\s*\(/',
		),
		'forbidden_text'  => array(
			"LIKE '{\$n}{\$search_term}{\$n}'",
		),
		'required'        => array(
			'/\$wpdb->esc_like\s*\(\s*\$search_term\s*\)/',
			'/\$wpdb->prepare\s*\(/',
		),
		'required_text'   => array(
			'{$searchand}((g.title LIKE %s) OR (g.description LIKE %s))',
		),
	),
	'inc/db.connect.php' => array(
		'forbidden_regex' => array(
			'/\$term\s*=\s*wp_slash\s*\(/',
			'/esc_sql\s*\(\s*addcslashes\s*\(/',
		),
		'forbidden_text'  => array(
			"LIKE '{\$n}{\$term}{\$n}'",
		),
		'required'        => array(
			'/\$wpdb->esc_like\s*\(\s*\$term\s*\)/',
			'/\$wpdb->prepare\s*\(/',
		),
		'required_text'   => array(
			'{$searchand}(($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_content LIKE %s) OR ($wpdb->posts.post_name LIKE %s))',
			'{$searchand}(({$wpdb->prefix}gmedia.title LIKE %s) OR ({$wpdb->prefix}gmedia.description LIKE %s) OR ({$wpdb->prefix}gmedia.gmuid LIKE %s))',
		),
	),
);

$failures = array();

foreach ( $checks as $relative_path => $rules ) {
	$path = $root . '/' . $relative_path;
	$code = file_get_contents( $path );

	if ( false === $code ) {
		$failures[] = "Could not read {$relative_path}";
		continue;
	}

	foreach ( $rules['forbidden_regex'] as $pattern ) {
		if ( preg_match( $pattern, $code ) ) {
			$failures[] = "{$relative_path} still matches forbidden pattern {$pattern}";
		}
	}

	foreach ( $rules['forbidden_text'] as $text ) {
		if ( false !== strpos( $code, $text ) ) {
			$failures[] = "{$relative_path} still contains forbidden text {$text}";
		}
	}

	foreach ( $rules['required'] as $pattern ) {
		if ( ! preg_match( $pattern, $code ) ) {
			$failures[] = "{$relative_path} does not match required pattern {$pattern}";
		}
	}

	foreach ( $rules['required_text'] as $text ) {
		if ( false === strpos( $code, $text ) ) {
			$failures[] = "{$relative_path} does not contain required text {$text}";
		}
	}
}

if ( $failures ) {
	echo implode( PHP_EOL, $failures ) . PHP_EOL;
	exit( 1 );
}

echo 'SQL LIKE search escaping guard passed.' . PHP_EOL;
