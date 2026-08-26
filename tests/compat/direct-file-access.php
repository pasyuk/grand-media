<?php

$root = dirname( __DIR__, 2 );

// These WordPress-only files must stop before output or calls into WordPress.
$files = array(
	'admin/admin.php',
	'admin/ajax.php',
	'admin/class.processor.php',
	'admin/functions.php',
	'admin/pages/galleries/functions.php',
	'admin/pages/library/functions.php',
	'admin/pages/modules/functions.php',
	'admin/pages/terms/functions.php',
	'admin/processor/class.processor.addmedia.php',
	'admin/processor/class.processor.galleries.php',
	'admin/processor/class.processor.library.php',
	'admin/processor/class.processor.modules.php',
	'admin/processor/class.processor.settings.php',
	'admin/processor/class.processor.terms.php',
	'admin/processor/class.processor.wpmedia.php',
	'config.php',
	'inc/db.connect.php',
	'inc/functions.php',
	'inc/hashids.php',
	'inc/image-editor.php',
	'inc/map-editor.php',
	'inc/pel/autoload.php',
	'module/amron/init.php',
	'module/cubik-lite/index.php',
	'module/cubik-lite/init.php',
	'module/cubik-lite/settings.php',
	'module/jq-mplayer/index.php',
	'module/jq-mplayer/init.php',
	'module/jq-mplayer/settings.php',
	'module/phantom/index.php',
	'module/phantom/init.php',
	'module/phantom/settings.php',
	'module/photomania/index.php',
	'module/photomania/init.php',
	'module/photomania/settings.php',
	'module/wp-videoplayer/index.php',
	'module/wp-videoplayer/init.php',
	'module/wp-videoplayer/settings.php',
	'template/album.php',
	'template/category.php',
	'template/comments-popup.php',
	'template/foot.php',
	'template/functions.php',
	'template/gallery.php',
	'template/head.php',
	'template/single.php',
	'template/tag.php',
);

$failures = array();

foreach ( $files as $file ) {
	// The marker also catches files that return normally without a guard.
	$code    = 'require ' . var_export( $root . '/' . $file, true ) . '; echo "GMEDIA_FILE_RETURNED";';
	$command = escapeshellarg( PHP_BINARY ) . ' -n -d display_errors=stderr -d log_errors=0 -r ' . escapeshellarg( $code ) . ' 2>&1';
	$output  = array();
	exec( $command, $output, $status );

	if ( 0 !== $status || array() !== $output ) {
		$failures[] = $file . ' must exit silently before execution without WordPress (exit ' . $status . ').';
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo 'Direct file access guards passed for ' . count( $files ) . ' files.' . PHP_EOL;
