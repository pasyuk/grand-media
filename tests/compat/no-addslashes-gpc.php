<?php
/**
 * Ensure deprecated addslashes_gpc() is not used in plugin code.
 */

$root = dirname( __DIR__, 2 );
$rii  = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(
		$root,
		FilesystemIterator::SKIP_DOTS
	)
);

$matches = array();

foreach ( $rii as $file ) {
	if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
		continue;
	}

	$path     = $file->getPathname();
	$relative = substr( $path, strlen( $root ) + 1 );

	if ( 0 === strpos( $relative, 'vendor/' ) || 0 === strpos( $relative, 'tests/' ) ) {
		continue;
	}

	$contents = file_get_contents( $path );
	if ( false !== strpos( $contents, 'addslashes_gpc(' ) ) {
		$matches[] = $relative;
	}
}

if ( $matches ) {
	fwrite( STDERR, "Deprecated addslashes_gpc() usage found:\n- " . implode( "\n- ", $matches ) . "\n" );
	exit( 1 );
}

echo "No addslashes_gpc() usage found.\n";
