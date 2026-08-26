<?php

$root = dirname( __DIR__, 2 );

if ( in_array( '--owned-string-id', $argv, true ) ) {
	define( 'ABSPATH', $root . '/' );

	function add_action() {}
	function check_ajax_referer() {}
	function current_user_can() {
		return true;
	}
	function sanitize_key( $value ) {
		return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) );
	}
	function get_option( $name ) {
		return 'UTF-8';
	}
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
	function esc_html__( $value ) {
		return $value;
	}

	$user_ID = 1;
	$gmCore  = new class() {
		public function _post( $name, $default = null ) {
			return isset( $_POST[ $name ] ) ? $_POST[ $name ] : $default;
		}
		public function is_protected_meta() {
			return false;
		}
	};
	$gmDB    = new class() {
		public function get_gmedia( $id ) {
			return (object) array( 'ID' => $id, 'author' => '1' );
		}
		public function get_metadata_by_mid() {
			return (object) array( 'gmedia_id' => '869', 'meta_key' => 'custom data' );
		}
		public function delete_metadata_by_mid() {
			return true;
		}
	};

	$_POST = array(
		'ID'   => '869',
		'meta' => array( '5018' => 'fixture' ),
	);

	require_once $root . '/admin/ajax.php';
	gmedia_delete_custom_field();
}

$completed = false;
register_shutdown_function(
	static function () use ( &$completed ) {
		if ( ! $completed ) {
			fwrite( STDERR, 'Custom field deletion test did not complete.' . PHP_EOL );
			exit( 1 );
		}
	}
);

ob_start();
passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --owned-string-id', $status );
$output = ob_get_clean();
$result = json_decode( $output, true );

if ( 0 !== $status ) {
	fwrite( STDERR, 'Custom field deletion fixture failed.' . PHP_EOL );
	exit( 1 );
}
if ( array( 5018 ) !== ( isset( $result['deleted'] ) ? $result['deleted'] : array() ) ) {
	fwrite( STDERR, 'Owned custom field with a string database ID was not deleted.' . PHP_EOL );
	exit( 1 );
}

$completed = true;
echo 'Custom field deletion passed: database IDs are normalized before ownership comparison.' . PHP_EOL;
