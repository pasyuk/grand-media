<?php

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Add Media control layout test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

require_once __DIR__ . '/helpers/template-dom.php';

$upload_xpath = gmedia_template_xpath( $root . '/admin/pages/addmedia/tpl/upload.php' );
$native_fields = $upload_xpath->query(
	'//div[@id="uploader_multipart_params"]'
	. '//select[contains(concat(" ", normalize-space(@class), " "), " input-sm ")]'
);
if ( 2 !== $native_fields->length ) {
	$failures[] = 'The upload parameter panel must render its two native compact selects';
}

$terms_xpath = gmedia_template_xpath( $root . '/admin/pages/addmedia/tpl/assign-terms.php' );
foreach ( array( 'combobox_gmedia_album', 'combobox_gmedia_category', 'combobox_gmedia_tag' ) as $field_id ) {
	$fields = $terms_xpath->query(
		'//*[@id="' . $field_id . '"]'
		. '[contains(concat(" ", normalize-space(@class), " "), " input-sm ")]'
	);
	if ( 1 !== $fields->length ) {
		$failures[] = 'The upload term panel must render the compact field ' . $field_id;
	}
}

$stylesheet = file_get_contents( $root . '/admin/assets/css/gmedia.admin.css' );
if ( false === $stylesheet ) {
	$failures[] = 'Could not read the admin stylesheet';
} else {
	if ( ! preg_match( '/#uploader_multipart_params\s+\.selectize-control\.input-sm\s*\{[^}]*height\s*:\s*auto\s*;[^}]*\}/s', $stylesheet ) ) {
		$failures[] = 'Add Media Selectize wrappers must grow with their visible controls';
	}
	if ( ! preg_match( '/#uploader_multipart_params\s+\.selectize-control\.input-sm\s+\.selectize-input\s*\{[^}]*min-height\s*:\s*40px\s*;[^}]*padding-block\s*:\s*7px\s*;[^}]*\}/s', $stylesheet ) ) {
		$failures[] = 'Add Media Selectize fields must match and center within the 40px native controls';
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Add Media control layout passed: native and Selectize fields share a 40px minimum.' . PHP_EOL;
