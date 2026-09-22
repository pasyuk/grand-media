<?php

// Real persistence methods and admin handler; all SQL/post boundaries are inert.
$root = dirname( __DIR__, 2 );
define( 'ABSPATH', $root . '/' );
define( 'GMEDIA_ABSPATH', $root . '/' );
define( 'OBJECT', 'OBJECT' );
define( 'ARRAY_A', 'ARRAY_A' );
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) { fwrite( STDERR, "Album save error tests did not complete.\n" ); exit( 1 ); }
} );
class WP_Error {
	public $code, $message, $data;
	public function __construct( $code, $message = '', $data = null ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function apply_filters( $hook, $value ) { return $value; }
function add_action() {}
function do_action( $hook ) { $GLOBALS['actions'][] = $hook; }
function wp_cache_delete() {}
function current_user_can() { return true; }
function gm_user_can() { return true; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function wp_kses_post( $value ) { return $value; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function absint( $value ) { return abs( (int) $value ); }
function esc_sql( $value ) { return $value; }
function sanitize_key( $value ) { return $value; }
function sanitize_meta( $key, $value, $type ) { return $value; }
function maybe_serialize( $value ) { return is_array( $value ) || is_object( $value ) ? serialize( $value ) : $value; }
function maybe_unserialize( $value ) { return $value; }
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value ); }
function stripslashes_deep( $value ) { return wp_unslash( $value ); }
function add_magic_quotes( $value ) { return array_map( 'addslashes', $value ); }
function get_gmt_from_date( $value ) { return $value; }
function check_admin_referer() {}
function wp_insert_post( $data, $wp_error = false ) {
	$GLOBALS['post_inserts']++;
	if ( 'post_insert' === $GLOBALS['wpdb']->failure ) { return $wp_error ? new WP_Error( 'db_insert_error', 'Injected failure' ) : 0; }
	$GLOBALS['post_data'] = $data;
	return 90;
}
function wp_update_post( $data, $wp_error = false ) {
	if ( 'post_update' === $GLOBALS['wpdb']->failure ) { return $wp_error ? new WP_Error( 'db_update_error', 'Injected failure' ) : 0; }
	$GLOBALS['post_data'] = $data;
	return $data['ID'];
}
function wp_delete_post( $id, $force = false ) { $GLOBALS['post_deletes']++; return (object) array( 'ID' => $id ); }
function add_metadata( $type, $id, $key, $value ) { return 'post_link' !== $GLOBALS['wpdb']->failure; }

class Album_Test_WPDB {
	public $prefix = 'fixture_', $posts = 'fixture_posts', $insert_id = 0, $last_error = '';
	public $failure = '', $term = array(), $meta = array();
	public function prepare( $sql, ...$args ) {
		return preg_replace_callback( '/%[sd]/', static function ( $match ) use ( &$args ) {
			$value = array_shift( $args );
			return '%d' === $match[0] ? (string) (int) $value : "'" . $value . "'";
		}, $sql );
	}
	private function fails( $operation ) {
		$this->last_error = $this->failure === $operation ? 'Injected SQL failure' : '';
		return '' !== $this->last_error;
	}
	public function insert( $table, $data ) {
		if ( 'fixture_gmedia_term' === $table ) {
			if ( $this->fails( 'term_insert' ) ) { return false; }
			$this->term = array_merge( $data, array( 'term_id' => '77' ) );
			$this->insert_id = 77;
		} else {
			$key = $data['meta_key'];
			if ( $this->fails( 'meta_insert:' . $key ) ) { return false; }
			$this->meta[ $key ] = (string) $data['meta_value'];
			$this->insert_id = 501;
		}
		return 1;
	}
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( 'fixture_gmedia_term' === $table ) {
			if ( $this->fails( 'term_update' ) ) { return false; }
			$this->term = array_merge( $this->term, $data );
			return 0; // Valid no-op SQL results must not be treated as failure.
		}
		$key = isset( $where['meta_key'] ) ? $where['meta_key'] : 'custom';
		if ( $this->fails( 'meta_update:' . $key ) ) { return false; }
		$value = (string) $data['meta_value'];
		$changed = $this->meta[ $key ] !== $value;
		$this->meta[ $key ] = $value;
		return $changed ? 1 : 0;
	}
	public function get_var( $sql ) {
		$this->last_error = '';
		if ( false !== strpos( $sql, 'SELECT meta_id' ) ) {
			preg_match( "/meta_key = '([^']+)'/", $sql, $matches );
			return isset( $this->meta[ $matches[1] ] ) ? '501' : null;
		}
		return $this->term ? '77' : null;
	}
	public function query( $sql ) { return $this->fails( false !== strpos( $sql, 'fixture_posts' ) ? 'status_posts' : 'status_media' ) ? false : 1; }
	public function delete( $table, $where ) {
		if ( $this->fails( 'meta_delete:custom' ) ) { return false; }
		unset( $this->meta['custom'] );
		return 1;
	}
}
require $root . '/inc/db.connect.php';
class Album_Test_DB extends GmediaDB {
	public $cleaned = array();
	public function term_exists( $term, $taxonomy = '', $global = false ) { return $GLOBALS['wpdb']->term ? 77 : 0; }
	public function get_term( $term, $args = null, $output = OBJECT ) {
		if ( 'term_read' === $GLOBALS['wpdb']->failure || ( 'term_read_update' === $GLOBALS['wpdb']->failure && ARRAY_A === $output ) ) { return null; }
		$data = $GLOBALS['wpdb']->term;
		return ARRAY_A === $output ? $data : (object) $data;
	}
	public function get_metadata( $type, $id, $key = '', $single = false ) {
		$values = array_map( static function ( $value ) { return array( $value ); }, $GLOBALS['wpdb']->meta );
		if ( '' === $key ) { return $values; }
		return $single ? ( isset( $values[ $key ] ) ? $values[ $key ][0] : '' ) : ( isset( $values[ $key ] ) ? $values[ $key ] : array() );
	}
	public function get_metadata_by_mid( $type, $id ) {
		if ( 'meta_read' === $GLOBALS['wpdb']->failure || ! isset( $GLOBALS['wpdb']->meta['custom'] ) ) { return false; }
		return (object) array( 'meta_id' => '501', 'gmedia_term_id' => '77', 'meta_key' => 'custom', 'meta_value' => $GLOBALS['wpdb']->meta['custom'] );
	}
	public function get_gmedias() { return array( (object) array( 'ID' => '5', 'post_id' => '6' ) ); }
	public function clean_term_cache( $ids ) { $this->cleaned[] = $ids; }
	public function update_term_sortorder( $term_id, $ids = array() ) {
		return 'sort_reset' === $GLOBALS['wpdb']->failure ? new WP_Error( 'db_error', 'Could not save album order' ) : array();
	}
}
class GmediaProcessor {
	public $msg = array(), $error = array(), $taxonomy = 'gmedia_album', $taxterm = 'album';
	public function __construct() {}
	public static function selected_items( $key ) { return array(); }
}
$_COOKIE = array();
require $root . '/admin/processor/class.processor.terms.php';
class Album_Test_Processor extends GmediaProcessor_Terms {
	public function query_args() { return array(); }
	public function run() { $this->processor(); }
}
$gmCore = new class() {
	public $caps = array( 'gmedia_library' => true, 'gmedia_album_manage' => true );
	public function mb_convert_encoding_utf8( $value ) { return $value; }
	public function is_digit( $value ) { return is_numeric( $value ); }
	public function is_protected_meta() { return false; }
	public function _get( $key, $default = null ) { return isset( $_GET[ $key ] ) ? $_GET[ $key ] : $default; }
	public function _post( $key, $default = null ) { return isset( $_POST[ $key ] ) ? $_POST[ $key ] : $default; }
};
$user_ID = 12;
$gmGallery = (object) array( 'options' => array( 'in_album_status' => 'publish', 'in_album_orderby' => 'ID', 'in_album_order' => 'DESC', 'default_gmedia_comment_status' => 'closed' ) );
function album_fixture( $editing, $failure = '' ) {
	global $wpdb, $gmDB, $actions, $post_inserts, $post_data;
	$wpdb = new Album_Test_WPDB();
	$wpdb->failure = $failure;
	$gmDB = new Album_Test_DB();
	$GLOBALS['post_deletes'] = 0; $actions = array(); $post_inserts = 0; $post_data = array();
	if ( $editing ) {
		$wpdb->term = array( 'term_id' => '77', 'name' => 'Album', 'description' => '', 'global' => '12', 'taxonomy' => 'gmedia_album', 'status' => 'publish', 'count' => '0' );
		$wpdb->meta = array( '_orderby' => 'ID', '_order' => 'DESC', '_cover' => '8', '_post_ID' => '90' );
	}
	$_GET = $editing ? array( 'edit_term' => '77' ) : array();
	$_POST = array( 'gmedia_album_save' => '1', 'term' => array( 'name' => 'Album', 'taxonomy' => 'gmedia_album', 'global' => '12', 'description' => '', 'status' => 'publish', 'slug' => 'album', 'meta' => array( '_orderby' => 'title', '_order' => 'ASC', '_cover' => '9' ) ) );
}
$failures = array();
function album_assert( $ok, $message ) { if ( ! $ok ) { $GLOBALS['failures'][] = $message; } }
foreach ( array( false, true ) as $editing ) {
	$cases = $editing
		? array( '', 'term_update', 'meta_update:_orderby', 'meta_update:_order', 'meta_update:_cover', 'post_update', 'sort_reset', 'meta_update:custom', 'meta_delete:custom', 'status_media', 'status_posts', 'term_read', 'term_read_update', 'meta_read' )
		: array( '', 'term_insert', 'meta_insert:_orderby', 'meta_insert:_order', 'meta_insert:_cover', 'post_insert', 'post_link', 'meta_insert:_post_ID', 'meta_insert:custom' );
	foreach ( $cases as $failure ) {
		album_fixture( $editing, $failure );
		if ( 'meta_insert:custom' === $failure ) { $_POST['term']['meta']['custom'] = 'new'; }
		if ( in_array( $failure, array( 'meta_update:custom', 'meta_delete:custom', 'meta_read' ), true ) ) {
			$wpdb->meta['custom'] = 'old';
			$_POST['term']['meta'][501] = 'meta_delete:custom' === $failure ? '' : 'new';
		}
		if ( in_array( $failure, array( 'status_media', 'status_posts' ), true ) ) { $_POST['term']['status_global'] = '1'; }
		if ( 'sort_reset' === $failure ) { $_POST['term']['reset_custom_order'] = '1'; }
		$processor = new Album_Test_Processor();
		$processor->run();
		$label = ( $editing ? 'edit:' : 'create:' ) . ( $failure ? $failure : 'success' );
		album_assert( '' === $failure ? ( ! $processor->error && 1 === count( $processor->msg ) ) : ( $processor->error && ! $processor->msg ), $label . ' must show the actual save outcome' );
		if ( '' === $failure ) {
			album_assert( 'title' === $wpdb->meta['_orderby'] && 'ASC' === $wpdb->meta['_order'] && '9' === $wpdb->meta['_cover'], $label . ' must persist album settings' );
			album_assert( 'album' === $post_data['post_name'] && '90' === $wpdb->meta['_post_ID'], $label . ' must persist the related post' );
		} elseif ( 'term_insert' !== $failure && 'sort_reset' !== $failure && 'term_read' !== $failure && 'term_read_update' !== $failure ) {
			album_assert( $gmDB->cleaned, $label . ' must invalidate partial album state' );
			album_assert( in_array( 'clean_gmedia_cache', $actions, true ), $label . ' must invalidate rendered frontend caches' );
			album_assert( ! in_array( 'created_gmedia_term', $actions, true ) && ! in_array( 'edited_gmedia_term', $actions, true ), $label . ' must not fire successful-save actions' );
		}
		if ( 'post_update' === $failure ) { album_assert( 0 === $post_inserts, 'Failed update must not create a replacement post' ); }
	}
}
album_fixture( true );
$_POST['term']['meta'] = array( '_orderby' => 'ID', '_order' => 'DESC', '_cover' => '8' );
$processor = new Album_Test_Processor(); $processor->run();
album_assert( ! $processor->error && 1 === count( $processor->msg ), 'Unchanged metadata and zero affected rows are successful saves' );
// A failed creation can leave an album row. Editing that row must complete the save.
album_fixture( false, 'meta_insert:_cover' );
$processor = new Album_Test_Processor(); $processor->run();
album_assert( $wpdb->term && $processor->error, 'Partial creation remains identifiable for recovery' );
$wpdb->failure = '';
$_GET['edit_term'] = '77';
$processor = new Album_Test_Processor(); $processor->run();
album_assert( ! $processor->error && 1 === count( $processor->msg ) && '9' === $wpdb->meta['_cover'], 'Editing a partially created album retries missing metadata' );
album_assert( 1 === $post_inserts, 'Recovery creates only the missing WordPress post' );

// An unchanged custom value may still produce zero affected SQL rows after normalization.
album_fixture( true );
$wpdb->meta['custom'] = '3';
$_POST['term']['meta'][501] = 3;
$processor = new Album_Test_Processor(); $processor->run();
album_assert( ! $processor->error && $processor->msg, 'Unchanged normalized custom field stays successful' );

// Creating a missing related post while editing uses the same error handling as creation.
foreach ( array( 'post_insert', 'post_link', 'meta_insert:_post_ID' ) as $failure ) {
	album_fixture( true, $failure );
	unset( $wpdb->meta['_post_ID'] );
	$processor = new Album_Test_Processor(); $processor->run();
	album_assert( $processor->error && ! $processor->msg, 'Missing related post: ' . $failure . ' must reach the UI' );
}

// Failed links must not leave a newly created unlinked post on retry.
foreach ( array( 'post_link', 'meta_insert:_post_ID' ) as $failure ) {
	album_fixture( false, $failure );
	$processor = new Album_Test_Processor(); $processor->run();
	album_assert( 1 === $post_deletes, 'Failed link removes only its new post: ' . $failure );
	$wpdb->failure = ''; $_GET['edit_term'] = '77';
	$processor = new Album_Test_Processor(); $processor->run();
	album_assert( ! $processor->error && 1 === $post_inserts - $post_deletes, 'Retry leaves one linked post: ' . $failure );
}
$completed = true;
if ( $failures ) { fwrite( STDERR, implode( "\n", $failures ) . "\n" ); exit( 1 ); }
echo "Album save errors passed: create/edit failures reach the UI; no-op saves remain successful.\n";
