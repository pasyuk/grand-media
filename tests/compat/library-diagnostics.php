<?php

use lsolesen\pel\Pel;
use lsolesen\pel\PelConvert;
use lsolesen\pel\PelDataWindow;
use lsolesen\pel\PelEntryAscii;
use lsolesen\pel\PelEntryException;
use lsolesen\pel\PelEntryTime;
use lsolesen\pel\PelException;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelFormat;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;
use lsolesen\pel\PelTiff;

$root      = dirname( __DIR__, 2 );
$completed = false;
$failures  = array();
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Library diagnostics test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);
define( 'ABSPATH', $root . '/' );

// Standalone boundary double; the actual PEL formatting and output paths run below.
function esc_html( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
}

require_once $root . '/inc/hashids.php';
require_once $root . '/inc/pel/autoload.php';

$ifd     = new PelIfd( PelIfd::IFD0 );
$data    = new PelDataWindow( 'abc' );
$time    = new PelEntryTime( PelTag::DATE_TIME, 0 );
$missing = __FILE__ . '/missing-<test&>.jpg';

// Escaping constructor arguments would alter messages or stored tag/type values.
$cases = array(
	array( 'hashids short alphabet', static function () { new GmediaHashIDs( '', 0, 'abc' ); }, 'Exception', 'alphabet must contain at least 16 unique characters' ),
	array( 'hashids alphabet space', static function () { new GmediaHashIDs( '', 0, 'abcdefghijklmnop ' ); }, 'Exception', 'alphabet cannot contain spaces' ),
	array( 'window invalid type', static function () { new PelDataWindow( array() ); }, 'PelInvalidArgumentException', 'Bad type for $data: array' ),
	array( 'window start', static function () use ( $data ) { $data->setWindowStart( 4 ); }, 'PelDataWindowWindowException', 'Window [4, 3] does not fit in window [0, 3]' ),
	array( 'window size', static function () use ( $data ) { $data->setWindowSize( 4 ); }, 'PelDataWindowWindowException', 'Window [0, 4] does not fit in window [0, 3]' ),
	array( 'window offset', static function () use ( $data ) { $data->getByte( 3 ); }, 'PelDataWindowOffsetException', 'Offset 3 not within [0, 2]' ),
	array( 'time invalid get type', static function () use ( $time ) { $time->getValue( 999 ); }, 'PelInvalidArgumentException', 'Expected UNIX_TIMESTAMP (1), EXIF_STRING (2), or JULIAN_DAY_COUNT (3) for $type, got 999.' ),
	array( 'time invalid unix value', static function () use ( $time ) { $time->setValue( array(), PelEntryTime::UNIX_TIMESTAMP ); }, 'PelInvalidArgumentException', 'Expected integer value for $type, got array' ),
	array( 'time invalid julian value', static function () use ( $time ) { $time->setValue( array(), PelEntryTime::JULIAN_DAY_COUNT ); }, 'PelInvalidArgumentException', 'Expected integer value for $type, got array' ),
	array( 'time invalid set type', static function () use ( $time ) { $time->setValue( 0, 999 ); }, 'PelInvalidArgumentException', 'Expected UNIX_TIMESTAMP (1), EXIF_STRING (2), or JULIAN_DAY_COUNT (3) for $type, got 999.' ),
	array( 'short exif', static function () use ( $data ) { ( new PelExif() )->load( $data ); }, 'PelInvalidDataException', 'Expected at least 6 bytes of Exif data, found just 3 bytes.' ),
	array( 'format name', static function () { PelFormat::getName( 999 ); }, 'PelIllegalFormatException', 'Unknown format: 0x3E7' ),
	array( 'format size', static function () { PelFormat::getSize( 999 ); }, 'PelIllegalFormatException', 'Unknown format: 0x3E7' ),
	array( 'invalid ifd', static function () { new PelIfd( 999 ); }, 'PelIfdException', 'Unknown IFD type: 999' ),
	array( 'date components', static function () use ( $ifd, $data ) { $ifd->newEntryFromData( PelTag::DATE_TIME, PelFormat::ASCII, 19, $data ); }, 'PelWrongComponentCountException', 'Wrong number of components found for DateTime tag: 19. Expected 20.', PelTag::DATE_TIME ),
	array( 'unsupported format', static function () use ( $ifd, $data ) { $ifd->newEntryFromData( 0xF001, PelFormat::FLOAT, 3, $data ); }, 'PelException', 'Unsupported format: Float' ),
	array( 'invalid ifd name', static function () { PelIfd::getTypeName( 999 ); }, 'PelIfdException', 'Unknown IFD type: 999' ),
	array( 'invalid entry', static function () use ( $ifd ) { $ifd->addEntry( new PelEntryAscii( 0, '<test&>' ) ); }, 'PelInvalidDataException', "IFD 0 cannot hold\n  Tag: 0x0000 (Unknown: 0x0000)\n    Format    : 2 (Ascii)\n    Components: 8\n    Value     : <test&>\n    Text      : <test&>\n" ),
	array( 'invalid offset entry', static function () use ( $ifd ) { $ifd->offsetSet( 0, '<test&>' ); }, 'PelInvalidArgumentException', 'Argument "<test&>" must be a PelEntry.' ),
	array( 'jpeg invalid type', static function () { new PelJpeg( array() ); }, 'PelInvalidArgumentException', 'Bad type for $data: array' ),
	array( 'jpeg invalid marker', static function () { new PelJpeg( new PelDataWindow( "\xff\x00" ) ); }, 'PelJpegInvalidMarkerException', 'Invalid marker found at offset 1: 0x 0' ),
	array( 'jpeg missing file', static function () use ( $missing ) { ( new PelJpeg() )->loadFile( $missing ); }, 'PelException', 'Can not open file "' . $missing . '"' ),
	array( 'tiff invalid type', static function () { new PelTiff( array() ); }, 'PelInvalidArgumentException', 'Bad type for $data: array' ),
	array( 'short tiff', static function () use ( $data ) { ( new PelTiff() )->load( $data ); }, 'PelInvalidDataException', 'Expected at least 8 bytes of TIFF data, found just 3 bytes.' ),
	array( 'tiff byte order', static function () { ( new PelTiff() )->load( new PelDataWindow( 'abcdefgh' ) ); }, 'PelInvalidDataException', 'Unknown byte order found in TIFF data: 0x6162' ),
	array( 'tiff ifd type', static function () { ( new PelTiff() )->setIfd( new PelIfd( PelIfd::EXIF ) ); }, 'PelInvalidDataException', 'Invalid type of IFD: 2, expected 0.' ),
);
foreach ( array(
	array( PelTag::DATE_TIME, PelFormat::BYTE, 'DateTime', 'BYTE', 'ASCII' ),
	array( PelTag::COPYRIGHT, PelFormat::BYTE, 'Copyright', 'BYTE', 'ASCII' ),
	array( PelTag::EXIF_VERSION, PelFormat::BYTE, 'ExifVersion', 'BYTE', 'UNDEFINED' ),
	array( PelTag::USER_COMMENT, PelFormat::BYTE, 'UserComment', 'BYTE', 'UNDEFINED' ),
	array( PelTag::XP_TITLE, PelFormat::ASCII, 'WindowsXPTitle', 'ASCII', 'BYTE' ),
) as $format_case ) {
	list( $tag, $format, $name, $found, $expected ) = $format_case;
	$cases[] = array(
		$name . ' format',
		static function () use ( $ifd, $data, $tag, $format ) { $ifd->newEntryFromData( $tag, $format, 20, $data ); },
		'PelUnexpectedFormatException',
		'Unexpected format found for ' . $name . ' tag: PelFormat::' . $found . '. Expected PelFormat::' . $expected . ' instead.',
		$tag,
	);
}

foreach ( $cases as $case ) {
	list( $label, $run, $class, $message ) = $case;
	$class = 'Exception' === $class ? $class : 'lsolesen\\pel\\' . $class;
	ob_start();
	try {
		$run();
		$failures[] = $label . ': expected exception was not thrown';
	} catch ( Exception $e ) {
		if ( get_class( $e ) !== $class || $e->getMessage() !== $message ) {
			$failures[] = $label . ': exception class or raw message changed';
		}
		if ( isset( $case[4] ) && ( ! $e instanceof PelEntryException || 0 !== $e->getIfdType() || $case[4] !== $e->getTag() ) ) {
			$failures[] = $label . ': integer exception metadata changed';
		}
	}
	if ( '' !== ob_get_clean() ) {
		$failures[] = $label . ': throwing an exception emitted output';
	}
}

$exception = new PelException( 'Raw %s', '<test&>' );
Pel::clearExceptions();
ob_start();
Pel::debug( '%s', '<test&>' );
Pel::warning( '%s', '<test&>' );
Pel::maybeThrow( $exception );
if ( '' !== ob_get_clean() || array( $exception ) !== Pel::getExceptions() || 'Raw <test&>' !== $exception->getMessage() ) {
	$failures[] = 'Default diagnostics must be silent and preserve the raw exception';
}
Pel::setStrictParsing( true );
try {
	Pel::maybeThrow( $exception );
	$failures[] = 'Strict parsing must throw';
} catch ( PelException $e ) {
	if ( $e !== $exception ) {
		$failures[] = 'Strict parsing must preserve exception identity';
	}
}
Pel::setStrictParsing( false );
Pel::setDebug( true );
ob_start();
Pel::debug( '<debug> %s %04d %%', '<test&>', 7 );
if ( "&lt;debug&gt; &lt;test&amp;&gt; 0007 %\n" !== ob_get_clean() ) {
	$failures[] = 'Debug output must be escaped after formatting';
}
ob_start();
Pel::warning( '<warn> %s %04d %%', '<test&>', 7 );
if ( "Warning: &lt;warn&gt; &lt;test&amp;&gt; 0007 %\n" !== ob_get_clean() ) {
	$failures[] = 'Warning output must be escaped after formatting';
}
ob_start();
$ifd->newEntryFromData( PelTag::COPYRIGHT, PelFormat::ASCII, 7, new PelDataWindow( '<test&>' ) );
$warning = ob_get_clean();
if ( ! preg_match( '/^Warning: Invalid copyright: &lt;test&amp;&gt; \(PelIfd\.php:\d+\)\n$/', $warning ) ) {
	$failures[] = 'Malformed EXIF metadata warning must escape raw image contents';
}
Pel::setDebug( false );
Pel::clearExceptions();

$bytes = implode( '', array_map( 'chr', range( 0, 255 ) ) );
ob_start();
PelConvert::bytesToDump( $bytes );
$dump = ob_get_clean();
if ( preg_match( '/[^0-9A-F \n]/', $dump ) || 768 !== strlen( str_replace( "\n", '', $dump ) ) || 11 !== substr_count( $dump, "\n" ) ) {
	$failures[] = 'All byte values must produce only complete hexadecimal output';
}
foreach ( array( array( '', 0, "\n" ), array( "\0<&\xff", 0, "00 3C 26 FF \n" ), array( "\0<&\xff", 2, "00 3C \n" ), array( str_repeat( 'A', 24 ), 0, str_repeat( '41 ', 24 ) . "\n\n" ) ) as $dump_case ) {
	ob_start();
	PelConvert::bytesToDump( $dump_case[0], $dump_case[1] );
	if ( $dump_case[2] !== ob_get_clean() ) {
		$failures[] = 'Hexadecimal output, truncation or line wrapping changed';
	}
}
foreach ( array( PelConvert::LITTLE_ENDIAN, PelConvert::BIG_ENDIAN ) as $endian ) {
	foreach ( array( 0, 38, 60, 65535, 0x3C264122 ) as $number ) {
		if ( $number !== PelConvert::bytesToLong( PelConvert::longToBytes( $number, $endian ), 0, $endian ) ) {
			$failures[] = 'Binary integer round-trip changed';
		}
	}
	$tiff_bytes = $endian ? "II\x2a\0\0\0\0\0" : "MM\0\x2a\0\0\0\0";
	$tiff       = new PelTiff( new PelDataWindow( $tiff_bytes ) );
	if ( $tiff_bytes !== $tiff->getBytes( $endian ) ) {
		$failures[] = 'TIFF binary header round-trip changed';
	}
}
$entry = new PelEntryAscii( PelTag::IMAGE_DESCRIPTION, '<test&>' );
if ( "<test&>\0" !== $entry->getBytes( PelConvert::LITTLE_ENDIAN ) ) {
	$failures[] = 'EXIF metadata must not be HTML escaped in image bytes';
}
$jpeg_bytes = "\xff\xd8\xff\xfe\0\x09<test&>\xff\xd9";
$jpeg       = new PelJpeg( new PelDataWindow( $jpeg_bytes ) );
if ( $jpeg_bytes !== $jpeg->getBytes() ) {
	$failures[] = 'JPEG binary comment round-trip changed';
}
$hashids = new GmediaHashIDs( 'this is my salt' );
if ( 'laHquq' !== $hashids->encode( 1, 2, 3 ) || array( 1, 2, 3 ) !== $hashids->decode( 'laHquq' ) ) {
	$failures[] = 'Hashids known-vector round-trip changed';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
$completed = true;
echo 'Library diagnostics passed: 31 exception paths, escaped debug output, unchanged binary data.' . PHP_EOL;
