<?php

$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) {
		fwrite( STDERR, "Media query ordering check did not complete.\n" );
		exit( 1 );
	}
} );
define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
function do_action() {}
function absint( $value ) { return abs( (int) $value ); }
function wp_slash( $value ) { return addslashes( $value ); }
function esc_sql( $value ) { return addslashes( $value ); }
require ABSPATH . 'inc/db.connect.php';
$wpdb = new class {
	public $prefix = 'wp_';
	public $posts = 'wp_posts';
	public function get_col( $sql ) { return array(); }
};
$db = new GmediaDB();
$cases = array(
	array( array( 'invalid', 9, '4' ), 0, 'FIELD(wp_gmedia.ID, 9, 4) ASC' ),
	array( '9,4', 0, 'FIELD(wp_gmedia.ID, 9, 4) ASC' ),
	array( array( 'invalid', 9, '4' ), 9, 'FIELD(wp_gmedia.ID, 9, 4) ASC' ),
	array( array( 'invalid', 0 ), 0, 'wp_gmedia.ID ASC' ),
	array( array(), 0, 'wp_gmedia.ID ASC' ),
	array( '9', 9, 'wp_gmedia.ID ASC' ),
);
foreach ( $cases as $case ) {
	list( $ids, $id, $expected ) = $case;
	$db->get_gmedias( array( 'gmedia__in' => $ids, 'gmedia_id' => $id, 'orderby' => 'gmedia__in', 'fields' => 'ids' ) );
	if ( 'ORDER BY ' . $expected !== $db->clauses['orderby'] ) {
		fwrite( STDERR, "Unexpected media ordering: {$db->clauses['orderby']}\n" );
		exit( 1 );
	}
}
$completed = true;
echo "Media query ordering passed.\n";
