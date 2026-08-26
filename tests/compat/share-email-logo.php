<?php

$root = dirname( __DIR__, 2 );
$completed = false;
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Share email logo test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

define( 'ABSPATH', dirname( $root, 3 ) . '/' );
define( 'WPINC', 'wp-includes' );
function apply_filters( $hook, $value ) { return $value; }
function add_action() {}
function check_ajax_referer() { return true; }
function is_utf8_charset() { return true; }
function _canonical_charset( $charset ) { return $charset; }
function get_option( $name, $default = false ) { return 'blog_charset' === $name ? 'UTF-8' : $default; }
function wp_allowed_protocols() { return array( 'http', 'https', 'mailto' ); }
function __( $text, $domain = 'default' ) { return $text; }
function esc_html__( $text, $domain = 'default' ) { return esc_html( $text ); }
function get_the_author_meta() { return 'Test Author'; }
function plugins_url( $path, $plugin = '' ) { return 'https://example.test/wp-content/plugins/grand-media/' . ltrim( $path, '/' ); }

require_once ABSPATH . WPINC . '/compat.php';
require_once ABSPATH . WPINC . '/utf8.php';
require_once ABSPATH . WPINC . '/formatting.php';
require_once ABSPATH . WPINC . '/kses.php';
require_once ABSPATH . WPINC . '/class-wp-token-map.php';
foreach ( array( 'html5-named-character-references', 'class-wp-html-attribute-token', 'class-wp-html-span', 'class-wp-html-doctype-info', 'class-wp-html-text-replacement', 'class-wp-html-decoder', 'class-wp-html-tag-processor' ) as $html_api_file ) {
	require_once ABSPATH . WPINC . '/html-api/' . $html_api_file . '.php';
}

// Capture the real rendered email before the handler's exit; never send mail.
class Gmedia_Captured_Test_Email extends RuntimeException {}
function wp_mail( $to, $subject, $message, $headers ) {
	throw new Gmedia_Captured_Test_Email( $message );
}

$gmCore = new class {
	public function _post( $key, $default = '' ) {
		return array(
			'sharelink' => 'https://example.test/gallery?one=1&two=2',
			'email' => 'fixture@example.test',
			'message' => 'A message & more',
		)[ $key ] ?? $default;
	}
};
$user_ID = 1;
require_once $root . '/admin/ajax.php';

try {
	gmedia_share_page();
} catch ( Gmedia_Captured_Test_Email $email ) {
	$message = $email->getMessage();
}

$logo_path = 'assets/icons/icon_gmedia_120.png';
if ( empty( $message ) || false === strpos( $message, 'src="' . esc_url( plugins_url( $logo_path ) ) . '"' ) || ! is_file( $root . '/' . $logo_path ) ) {
	fwrite( STDERR, 'The share email must use the bundled Gmedia logo.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $message, 'A message &amp; more' ) || false === strpos( $message, 'width="72"' ) ) {
	fwrite( STDERR, 'Share email content or logo dimensions changed.' . PHP_EOL );
	exit( 1 );
}
$completed = true;
echo 'Share email bundled logo passed (no email sent).' . PHP_EOL;
