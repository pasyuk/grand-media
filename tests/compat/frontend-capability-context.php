<?php

/** Exercise the actual compatibility filters against WordPress capability arguments. */
$completed = false;
register_shutdown_function( static function () use ( &$completed ) {
	if ( ! $completed ) {
		fwrite( STDERR, 'Frontend capability context test did not complete.' . PHP_EOL );
		exit( 1 );
	}
} );
define( 'ABSPATH', dirname( __DIR__, 5 ) . '/' );

function add_filter() {}

function absint( $value ) {
	return abs( (int) $value );
}

function get_comment( $id ) {
	$posts = array( 101 => 11, 102 => 12, 103 => 13 );
	return isset( $posts[ $id ] ) ? (object) array( 'comment_post_ID' => $posts[ $id ] ) : null;
}

$gmDB = new class {
	public $string_authors = false;

	public function get_post_gmedia( $id ) {
		$owners = array( 11 => 7, 12 => 8, 14 => 0 );
		return isset( $owners[ $id ] ) ? (object) array( 'author' => $this->string_authors ? (string) $owners[ $id ] : $owners[ $id ] ) : null;
	}
};

require dirname( __DIR__, 2 ) . '/inc/compatibility.php';

$failures = array();
$user     = (object) array( 'ID' => 7 );
$basecaps = array( 'gmedia_edit_media' => true );

// Core passes the requested meta capability, user ID and target object ID.
// Its edit_comment mapping supplies the parent's primitive edit capabilities.
$cases = array(
	array( 'owned comment without request context', 'edit_comment', 101, 'edit_posts', array(), 0, true ),
	array( 'owned published comment', 'edit_comment', 101, 'edit_published_posts', array(), 0, true ),
	array( 'owned post without request context', 'edit_post', 11, 'edit_posts', array(), 0, true ),
	array( 'foreign comment with unrelated request post', 'edit_comment', 102, 'edit_posts', array( 'p' => 11 ), 0, false ),
	array( 'foreign comment with unrelated request comment', 'edit_comment', 102, 'edit_posts', array( 'id' => 101 ), 0, false ),
	array( 'ordinary post with unrelated global post', 'edit_post', 13, 'edit_posts', array(), 11, false ),
	array( 'ordinary comment', 'edit_comment', 103, 'edit_posts', array( 'p' => 11 ), 0, false ),
	array( 'missing comment', 'edit_comment', 999, 'edit_posts', array( 'p' => 11 ), 0, false ),
	array( 'global editing permission', 'edit_posts', null, 'edit_posts', array( 'p' => 11 ), 0, false ),
	array( 'global moderation permission', 'moderate_comments', null, 'moderate_comments', array( 'p' => 11 ), 0, false ),
	array( 'unrelated target-bearing capability', 'edit_user', 11, 'edit_posts', array( 'p' => 11 ), 0, false ),
);

foreach ( array( false, true ) as $string_authors ) {
	$gmDB->string_authors = $string_authors;
	foreach ( $cases as $case ) {
		list( $label, $requested, $object_id, $primitive, $_REQUEST, $post_id, $allowed ) = $case;
		$args = array( $requested, $user->ID );
		if ( null !== $object_id ) {
			$args[] = $object_id;
		}
		$result = gmedia_user_has_cap( $basecaps, array( $primitive ), $args, $user );
		if ( ! empty( $result[ $primitive ] ) !== $allowed ) {
			$failures[] = $label . ( $string_authors ? ' (database string author)' : ' (integer author)' );
		}
	}
}
$gmDB->string_authors = false;
$guest = (object) array( 'ID' => 0 );
$result = gmedia_user_has_cap( $basecaps, array( 'edit_posts' ), array( 'edit_post', 0, 14 ), $guest );
if ( ! empty( $result['edit_posts'] ) ) {
	$failures[] = 'Anonymous user must not own unassigned media';
}

$existing = array( 'moderate_comments' => true, 'edit_posts' => true, 'edit_published_posts' => true );
if ( $existing !== gmedia_user_has_cap( $existing, array_keys( $existing ), array( 'moderate_comments', 7 ), $user ) ) {
	$failures[] = 'Existing WordPress grants changed';
}
if ( gmedia_user_has_cap( array(), array( 'edit_posts' ), array( 'edit_comment', 7, 101 ), $user ) ) {
	$failures[] = 'Media-edit permission was not required';
}

// A query flag alone is not an app route; pretty routes do not need that flag.
$_GET = array( 'gmedia-app' => 1 );
$wp   = (object) array( 'query_vars' => array() );
if ( wpss_gmedia_check_bypass( false ) ) {
	$failures[] = 'Unrouted request bypassed the compatibility check';
}
if ( ! wpss_gmedia_check_bypass( true ) ) {
	$failures[] = 'Another compatibility bypass was revoked';
}
$_GET = array();
$wp->query_vars['gmedia-app'] = '1';
if ( ! wpss_gmedia_check_bypass( false ) ) {
	$failures[] = 'Routed app login lost its compatibility bypass';
}
$wp->query_vars['gmedia-app'] = '0';
if ( wpss_gmedia_check_bypass( false ) ) {
	$failures[] = 'Inactive app route bypassed the compatibility check';
}

$completed = true;
if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
echo 'Frontend capability and routed-app compatibility checks passed.' . PHP_EOL;
