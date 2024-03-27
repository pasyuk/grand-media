<?php
/**
 * Application access
 */

$time = - microtime( true );

// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

global $wp;
$gmedia_app = isset( $_GET['gmedia-app'] ) || isset( $wp->query_vars['gmedia-app'] );
if ( ! $gmedia_app ) {
	die();
}

global $gmCore, $gmapp_version, $gmmodule;
$gmapp_version = floatval( $gmCore->_get( 'gmappversion', 1 ) );
$gmmodule      = absint( $gmCore->_get( 'gmmodule', 0 ) );

$out = array();

if ( isset( $_FILES['userfile']['name'] ) ) {
	$globaldata = $gmCore->_post( 'account' );
	if ( $globaldata ) {
		$globaldata = stripslashes( $globaldata );
	}
} else {
	//$globaldata = isset($GLOBALS['HTTP_RAW_POST_DATA'])? $GLOBALS['HTTP_RAW_POST_DATA'] : false;
	$globaldata = file_get_contents( 'php://input' );
}

$gmedia_options = get_option( 'gmediaOptions' );
if ( $globaldata ) {

	$json = json_decode( $globaldata );

	if ( isset( $json->counter ) ) {
		gmedia_ios_app_counters( $json->counter );
		$out['alert'] = array( 'title' => 'Success', 'message' => "\nCounters updated" );
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Access-Control-Allow-Origin: *' );
		echo wp_json_encode( $out );
		die();
	}

	require_once dirname( __FILE__ ) . '/inc/json.auth.php';
	global $gmAuth;
	$gmAuth = new Gmedia_JSON_API_Auth_Controller();

	if ( isset( $json->cookie ) && ! empty( $json->cookie ) ) {
		if ( empty( $gmedia_options['mobile_app'] ) ) {
			$out['error'] = array( 'code' => 'app_inactive', 'message' => 'Service not enabled/activated for this site' );
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			echo wp_json_encode( $out );
			die();
		}

		$user_id = $gmAuth->validate_auth_cookie( $json->cookie );
		if ( $user_id ) {
			$user = wp_set_current_user( $user_id );
			if ( isset( $json->add_term ) ) {
				$out = gmedia_ios_app_processor( 'add_term', $json->add_term );
			} elseif ( isset( $json->delete_term ) ) {
				$out = gmedia_ios_app_processor( 'delete_term', $json->delete_term );
			} elseif ( isset( $json->doLibrary ) ) {
				$job = gmedia_ios_app_processor( 'do_library', $json->doLibrary );
				$out = gmedia_ios_app_processor( 'library', $json->library, false );
				$out = array_merge( $out, $job );
			} elseif ( isset( $json->library ) ) {
				$out = gmedia_ios_app_processor( 'library', $json->library );
			} elseif ( isset( $json->library_terms ) ) {
				$args = (array) $json->library_terms;
				if ( isset( $args['taxonomy'] ) ) {
					$out = gmedia_ios_app_library_data( array( $args['taxonomy'] ), $args );
				}
			}
		} else {
			$out['error'] = array( 'code' => 'wrongcookie', 'message' => 'Not Valid User' );
		}
	} elseif ( isset( $json->login ) ) {
		if ( empty( $gmedia_options['mobile_app'] ) ) {
			$out['error'] = array( 'code' => 'app_inactive', 'message' => 'Service not enabled/activated for this site' );
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			echo wp_json_encode( $out );
			die();
		}

		$out = gmedia_ios_app_login( $json );
		if ( ! isset( $out['error'] ) ) {
			$user = wp_set_current_user( $out['user']['id'] );

			$gmedia_capabilities_list = array(
				'gmedia_library',
				'gmedia_show_others_media',
				'gmedia_edit_media',
				'gmedia_edit_others_media',
				'gmedia_delete_media',
				'gmedia_delete_others_media',
				'gmedia_upload',
				'gmedia_terms',
				'gmedia_album_manage',
				'gmedia_category_manage',
				'gmedia_tag_manage',
				'gmedia_terms_delete',
			);
			$gmedia_capabilities      = array();
			foreach ( $gmedia_capabilities_list as $cap ) {
				$gmedia_capabilities[ $cap ] = current_user_can( $cap );
			}

			$out['user']['gmedia_capabilities'] = $gmedia_capabilities;

			$data = gmedia_ios_app_library_data();
			$out  = $out + $data;
		}
	} else {
		if ( isset( $json->library ) ) {
			$out = gmedia_ios_app_processor( 'library', $json->library );
		} elseif ( isset( $json->library_terms ) ) {
			$args = (array) $json->library_terms;
			if ( isset( $args['taxonomy'] ) ) {
				if ( ! is_array( $args['taxonomy'] ) ) {
					$args['taxonomy'] = array( $args['taxonomy'] );
				}
				$out = gmedia_ios_app_library_data( (array) $args['taxonomy'], $args );
			}
		} else {
			$out = gmedia_ios_app_library_data();
		}
	}
} elseif ( 'lostpassword' === $gmCore->_get( 'action' ) ) {
	if ( function_exists( 'wp_lostpassword_url' ) ) {
		$url = wp_lostpassword_url();
	} else {
		$url = add_query_arg( 'action', 'lostpassword', wp_login_url() );
	}
	wp_safe_redirect( $url );
	exit;
}


/**
 * @param object $json
 *
 * @return array
 */
function gmedia_ios_app_login( $json ) {
	global $gmAuth;

	do {
		if ( empty( $json->login ) ) {
			$out['error'] = array( 'code' => 'nologin', 'title' => 'No Login', 'message' => 'No Login' );
			break;
		}
		if ( empty( $json->password ) ) {
			$out['error'] = array( 'code' => 'nopassword', 'title' => 'No Password', 'message' => 'No Password' );
			break;
		}
		$uid = false;
		if ( is_email( $json->login ) ) {
			$uid = email_exists( $json->login );
		}
		if ( ! $uid && ! username_exists( $json->login ) ) {
			$out['error'] = array(
				'code'    => 'nouser',
				'title'   => 'Sorry, we can\'t log you in.',
				'message' => 'No User',
			);
			break;
		}

		$args = array(
			'username'          => $json->login,
			'password'          => $json->password,
			'_wpnonce_auth_app' => wp_create_nonce( 'gmedia_auth_app' ),
		);
		$out  = $gmAuth->generate_auth_cookie( $args );

	} while ( 0 );

	return $out;
}

/**
 * @param array $data
 * @param array $args
 *
 * @return array
 */
function gmedia_ios_app_library_data( $data = array( 'site', 'authors', 'filter', 'gmedia_category', 'gmedia_album', 'gmedia_tag' ), $args = array() ) {
	global $user_ID, $wpdb, $gmDB, $gmGallery, $gmapp_version, $gmmodule;

	if ( null === $data ) {
		$data = array( 'site', 'authors', 'filter', 'gmedia_category', 'gmedia_album', 'gmedia_tag' );
	}

	if ( version_compare( '3', $gmapp_version, '<=' ) ) {
		$logic = 2;
		if ( version_compare( '3.1', $gmapp_version, '<' ) ) {
			$logic = 3;
		}
		if ( $gmmodule ) {
			$terms_per_page = '';
		} else {
			$terms_per_page = 40;
		}
	} else {
		$logic          = 1;
		$terms_per_page = '';
	}
	$args = array_merge( array( 'number' => $terms_per_page ), (array) $args );

	$cache_expiration = isset( $gmGallery->options['cache_expiration'] ) ? (int) $gmGallery->options['cache_expiration'] * HOUR_IN_SECONDS : 24 * HOUR_IN_SECONDS;
	if ( $cache_expiration ) {
		$cache_key   = 'gm_cache_' . md5( wp_json_encode( array( (int) $user_ID, $data, $args, $gmmodule, $gmapp_version ) ) );
		$cache_value = get_transient( $cache_key );
		if ( ! empty( $cache_value ) && is_array( $cache_value ) ) {
			return $cache_value;
		}
	}

	$out = array();

	$ep = $gmGallery->options['endpoint'];
	if ( get_option( 'permalink_structure' ) ) {
		$share_link_base = home_url( rawurlencode( $ep ) . '/$2/$1' );
	} else {
		$share_link_base = add_query_arg( array( "$ep" => '$1', 't' => '$2' ), home_url( 'index.php' ) );
	}

	if ( in_array( 'site', $data, true ) ) {
		$site_name        = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );
		$out['site']      = array(
			'title'       => $site_name ? $site_name : '',
			'description' => $site_description ? $site_description : '',
		);
	}
	if ( in_array( 'authors', $data, true ) ) {
		$out['authors'] = array(
			'data' => array(),
		);
		$gmusers        = $wpdb->get_col( "SELECT DISTINCT author FROM {$wpdb->prefix}gmedia" );
		$gmusers2       = $wpdb->get_col( "SELECT DISTINCT {$wpdb->prefix}gmedia_term.global FROM {$wpdb->prefix}gmedia_term" );
		$gmusers        = array_filter( array_unique( array_merge( $gmusers, $gmusers2 ) ) );
		//if(current_user_can('gmedia_show_others_media') || current_user_can('gmedia_edit_others_media')){
		if ( ! empty( $gmusers ) ) {
			$authors = get_users( array( 'include' => $gmusers, 'orderby' => 'display_name' ) );
			if ( $authors ) {
				foreach ( $authors as $author ) {
					$out['authors']['data'][] = array(
						'id'          => $author->ID,
						'displayname' => $author->display_name,
						'firstname'   => $author->first_name,
						'lastname'    => $author->last_name,
					);
				}
			}
		}
		/*
		} else{
			$authordata = get_userdata( $user_ID );
			$display_name = $authordata->display_name;
			$first_name = $authordata->first_name;
			$last_name = $authordata->last_name;
			$out['authors']['data'][] = array('id' => $user_ID, 'displayname' => $display_name, 'firstname' => $first_name, 'lastname' => $last_name);
		}*/
	}
	if ( in_array( 'filter', $data, true ) ) {
		$gmDB->clauses = array();
		$out['filter'] = $gmDB->count_gmedia();
		$out['filter'] = array_map( 'intval', $out['filter'] );
	}
	if ( in_array( 'gmedia_category', $data, true ) ) {
		if ( $user_ID ) {
			if ( current_user_can( 'gmedia_terms_delete' ) && current_user_can( 'gmedia_delete_others_media' ) ) {
				$cap = 4;
			} elseif ( current_user_can( 'gmedia_category_manage' ) ) {
				$cap = 2;
			} else {
				$cap = 0;
			}
		} else {
			$cap = 0;
		}
		//$default_args = array('fields' => 'name=>all');
		$default_args = array();
		$_args        = $args;
		if ( isset( $_args['per_page'] ) ) {
			$_args['number'] = $_args['per_page'];
		}
		$_args             = array_merge( $default_args, $_args );
		$gmediaTerms       = $gmDB->get_terms( 'gmedia_category', $_args );
		$props             = array(
			'per_page'     => $_args['number'],
			'total_pages'  => $gmDB->pages,
			'current_page' => $gmDB->openPage,
			'items_count'  => $gmDB->resultPerPage,
			'total_count'  => $gmDB->totalResult,
		);
		$terms             = array( '0' => esc_html__( 'Uncategorized', 'grand-media' ) );
		$out['categories'] = array(
			'list'       => $terms,
			'cap'        => $cap,
			'properties' => $props,
			'data'       => array(),
		);
		if ( ! empty( $gmediaTerms ) ) {
			foreach ( $gmediaTerms as $i => $term ) {
				$out['categories']['list']["{$term->term_id}"] = $term->name;
				gmedia_ios_app_term_data_extend( $gmediaTerms[ $i ], $share_link_base, $logic, $cap );
			}

			if ( ! empty( $_args['include'] ) ) {
				$_gmediaTerms = array();
				foreach ( $gmediaTerms as $term ) {
					$_gmediaTerms["{$term->term_id}"] = $term;
				}
				$include     = (array) $_args['include'];
				$gmediaTerms = array();
				foreach ( $include as $tid ) {
					if ( isset( $_gmediaTerms["{$tid}"] ) ) {
						$gmediaTerms[] = $_gmediaTerms["{$tid}"];
					}
				}
			}
			$out['categories']['data'] = array_values( $gmediaTerms );
		}
	}
	if ( in_array( 'gmedia_album', $data, true ) ) {
		$default_args = array();
		$_args        = $args;
		if ( 1 < $logic ) {
			$default_args['orderby'] = 'ID';
			$default_args['order']   = 'DESC';
		}

		if ( $user_ID ) {
			if ( current_user_can( 'gmedia_terms_delete' ) ) {
				$cap = 4;
			} elseif ( current_user_can( 'gmedia_album_manage' ) ) {
				$cap = 2;
			} else {
				$cap = 0;
			}
			/*
			if( !current_user_can('gmedia_edit_others_media')){
				//$default_args = array( 'status' => array('publish', 'private') );
				//$default_args['global'] = array( $user_ID, 0 );
			}*/
		} else {
			$cap          = 0;
			$default_args = array( 'status' => 'publish' );
		}
		if ( isset( $_args['per_page'] ) ) {
			$_args['number'] = $_args['per_page'];
		}
		$_args = array_merge( $default_args, $_args );

		$gmediaTerms = $gmDB->get_terms( 'gmedia_album', $_args );
		$props       = array(
			'per_page'     => $_args['number'],
			'total_pages'  => $gmDB->pages,
			'current_page' => $gmDB->openPage,
			'items_count'  => $gmDB->resultPerPage,
			'total_count'  => $gmDB->totalResult,
		);
		foreach ( $gmediaTerms as $i => $term ) {
			gmedia_ios_app_term_data_extend( $gmediaTerms[ $i ], $share_link_base, $logic, $cap );
		}
		$gmediaTerms = array_filter( $gmediaTerms );
		if ( ! empty( $_args['include'] ) ) {
			$_gmediaTerms = array();
			foreach ( $gmediaTerms as $term ) {
				$_gmediaTerms["{$term->term_id}"] = $term;
			}
			$include     = (array) $_args['include'];
			$gmediaTerms = array();
			foreach ( $include as $tid ) {
				if ( isset( $_gmediaTerms["{$tid}"] ) ) {
					$gmediaTerms[] = $_gmediaTerms["{$tid}"];
				}
			}
		}
		$props['items_count'] = count( $gmediaTerms );

		$out['albums'] = array(
			'cap'        => $cap,
			'properties' => $props,
			'data'       => array_values( $gmediaTerms ),
		);
	}
	if ( in_array( 'gmedia_tag', $data, true ) ) {
		$default_args = array();
		$_args        = $args;
		if ( $user_ID ) {
			if ( current_user_can( 'gmedia_terms_delete' ) && current_user_can( 'gmedia_delete_others_media' ) ) {
				$cap = 4;
			} elseif ( current_user_can( 'gmedia_tag_manage' ) ) {
				$cap = 2;
			} else {
				$cap = 0;
			}
		} else {
			$cap = 0;
		}
		if ( isset( $_args['per_page'] ) ) {
			$_args['number'] = $_args['per_page'];
		}
		$_args       = array_merge( $default_args, $_args );
		$gmediaTerms = $gmDB->get_terms( 'gmedia_tag', $_args );
		$props       = array(
			'per_page'     => $_args['number'],
			'total_pages'  => $gmDB->pages,
			'current_page' => $gmDB->openPage,
			'items_count'  => $gmDB->resultPerPage,
			'total_count'  => $gmDB->totalResult,
		);
		foreach ( $gmediaTerms as $i => $term ) {
			gmedia_ios_app_term_data_extend( $gmediaTerms[ $i ], $share_link_base, $logic, $cap );
		}
		$out['tags'] = array(
			'cap'        => $cap,
			'properties' => $props,
			'data'       => array_values( $gmediaTerms ),
		);
	}

	if ( $cache_expiration ) {
		set_transient( $cache_key, $out, $cache_expiration );
	}

	return $out;
}

/**
 * @param object $term
 * @param string $share_link_base
 * @param int    $logic
 * @param int    $cap
 */
function gmedia_ios_app_term_data_extend( &$term, $share_link_base, $logic = 0, $cap = 0 ) {
	global $gmCore, $gmDB, $gmGallery, $user_ID;

	$taxterm      = str_replace( 'gmedia_', '', $term->taxonomy );
	$default_meta = array();

	if ( 'gmedia_album' === $term->taxonomy ) {
		$author_id = (int) $term->global;
		if ( $author_id ) {
			if ( ( $author_id !== $user_ID ) && ( 'draft' === $term->status ) && ! current_user_can( 'gmedia_edit_others_media' ) ) {
				$term = null;

				return;
			}
			$authordata = get_userdata( $author_id );
			if ( $authordata ) {
				$display_name = $authordata->display_name;
				$first_name   = $authordata->first_name;
				$last_name    = $authordata->last_name;
			} else {
				$display_name = esc_html__( 'Deleted User', 'grand-media' );
				$first_name   = '';
				$last_name    = '';
			}
		} else {
			$display_name = esc_html__( 'Shared', 'grand-media' );
			$first_name   = '';
			$last_name    = '';
		}
		$term->user = array(
			'id'          => $author_id,
			'displayname' => $display_name,
			'firstname'   => $first_name,
			'lastname'    => $last_name,
		);

		$default_meta = array( '_orderby' => 'ID', '_order' => 'DESC' );

		if ( 1 === $logic ) {
			if ( 'publish' === $term->status ) {
				$term->status = 'public';
			}
		}
	}
	if ( 'gmedia_category' === $term->taxonomy ) {
		unset( $term->global, $term->status );
		$term->title  = $term->name;
		$default_meta = array( '_orderby' => $gmGallery->options['in_category_orderby'], '_order' => $gmGallery->options['in_category_order'] );
	}
	if ( 'gmedia_tag' === $term->taxonomy ) {
		unset( $term->description, $term->global, $term->status );

		$default_meta = array( '_orderby' => $gmGallery->options['in_tag_orderby'], '_order' => $gmGallery->options['in_tag_order'] );
	}

	$term_meta = $gmDB->get_metadata( 'gmedia_term', $term->term_id );
	foreach ( $term_meta as $key => $value ) {
		if ( is_array( $value ) ) {
			if ( $gmCore->is_protected_meta( $key, 'gmedia_term' ) ) {
				$term_meta[ $key ] = $value[0];
			} elseif ( 1 === count( $value ) ) {
				$term_meta[ $key ] = $value[0];
			}
		}
	}
	$term_meta            = array_merge( $default_meta, $term_meta );
	$term_meta['orderby'] = $term_meta['_orderby'];
	$term_meta['order']   = $term_meta['_order'];

	if ( in_array( $term->taxonomy, array( 'gmedia_album', 'gmedia_category' ), true ) ) {
		if ( empty( $term_meta['_cover'] ) ) {
			$term_meta['_cover'] = false;
		} else {
			$term_meta['_cover'] = intval( $term_meta['_cover'] );
		}
		if ( $term_meta['_cover'] ) {
			$cover_id = (int) $term_meta['_cover'];
			$cover    = gmedia_ios_app_processor( 'library', array( 'gmedia__in' => array( $cover_id ) ), false, false );
			if ( isset( $cover['data'][0] ) ) {
				$term_meta['_cover'] = $cover['data'][0];
				$term->thumbnail     = $gmCore->gm_get_media_image( $cover_id, 'thumb', false );
			} else {
				$term_meta['_cover'] = false;
			}
		}
		if ( ! $term_meta['_cover'] && $term->count ) {
			$gmargs = array(
				'no_found_rows'  => true,
				'mime_type'      => 'image/*',
				'per_page'       => 1,
				"{$taxterm}__in" => array( $term->term_id ),
				'status'         => 'publish',
				'orderby'        => $term_meta['orderby'],
				'order'          => $term_meta['order'],
			);
			if ( $user_ID ) {
				$gmargs['status'] = array( 'publish', 'private' );
			}

			$termItems = $gmDB->get_gmedias( $gmargs );
			if ( ! empty( $termItems ) ) {
				$cover = gmedia_ios_app_processor( 'library', array( 'gmedia__in' => array( $termItems[0]->ID ) ), false, false );
				if ( isset( $cover['data'][0] ) ) {
					$term_meta['_cover'] = $cover['data'][0];
					$term->thumbnail     = $gmCore->gm_get_media_image( $termItems[0], 'thumb', false );
				}
			}
		}
	}

	$term->meta = $term_meta;

	$t               = array(
		'album'    => 'a',
		'tag'      => 't',
		'category' => 'k',
	);
	$gmedia_hashid   = gmedia_hash_id_encode( $term->term_id, $taxterm );
	$term->sharelink = str_replace( array( '$1', '$2' ), array( rawurlencode( $gmedia_hashid ), $t[ $taxterm ] ), $share_link_base );
	if ( 'album' === $taxterm ) {
		$post_id = isset( $term_meta['_post_ID'][0] ) ? (int) $term_meta['_post_ID'][0] : 0;
		if ( $post_id ) {
			$term->sharelink = (string) get_permalink( $post_id );
		}
	}

	$term->cap = ( 4 === $cap ) ? 4 : 0;
}

function gmedia_object_to_array( $obj ) {
	if ( is_object( $obj ) ) {
		$obj = (array) $obj;
	}
	if ( is_array( $obj ) ) {
		$new = array();
		foreach ( $obj as $key => $val ) {
			$new[ $key ] = gmedia_object_to_array( $val );
		}
	} else {
		$new = $obj;
	}

	return $new;
}

/**
 * @param string $action
 * @param array  $data
 * @param bool   $filter
 * @param bool   $cache
 *
 * @return array
 */
function gmedia_ios_app_processor( $action, $data, $filter = true, $cache = true ) {
	global $gmCore, $gmDB, $gmGallery, $user_ID, $gmapp_version, $gmmodule;

	$out = array();

	if ( version_compare( '3', $gmapp_version, '<=' ) ) {
		$logic = 2;
		if ( version_compare( '3.1', $gmapp_version, '<' ) ) {
			$logic = 3;
		}
	} else {
		$logic = 1;
	}

	$error      = array();
	$error_info = array();
	$alert      = array();
	$alert_info = array();

	$data = gmedia_object_to_array( $data );

	switch ( $action ) {
		case 'do_library':
			if ( ! isset( $data['action'] ) ) {
				return $out;
			}
			$filter = array();

			switch ( $data['action'] ) {

				case 'add_media':
					if ( ! current_user_can( 'gmedia_upload' ) ) {
						$out['error'] = array(
							'code'    => 'nocapability',
							'title'   => esc_html__( "You can't do this", 'grand-media' ),
							'message' => esc_html__( 'You have no permission to do this operation', 'grand-media' ),
						);

						return $out;
					}

					usleep( 10 );

					if ( is_uploaded_file( $_FILES['userfile']['tmp_name'] ) ) {
						$file_name = sanitize_text_field( wp_unslash( $_FILES['userfile']['name'] ) );
						$file_tmp  = sanitize_text_field( wp_unslash( $_FILES['userfile']['tmp_name'] ) );
					} else {
						$file_error = intval( wp_unslash( $_FILES['userfile']['error'] ) );
						switch ( $file_error ) {
							case 0:
								//no error; possible file attack!
								$error[] = esc_html__( 'There was a problem with your upload.', 'grand-media' );
								break;
							case 1:
								//uploaded file exceeds the upload_max_filesize directive in php.ini.
								$error[] = esc_html__( 'Uploaded file exceeds the upload_max_filesize directive in php.ini', 'grand-media' );
								break;
							case 2:
								//uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.
								$error[] = esc_html__( 'Uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the form', 'grand-media' );
								break;
							case 3:
								//uploaded file was only partially uploaded.
								$error[] = esc_html__( 'The file you are trying upload was only partially uploaded.', 'grand-media' );
								break;
							case 4:
								//no file was uploaded.
								$error[] = esc_html__( 'You must select an image for upload.', 'grand-media' );
								break;
							default:
								//a default error, just in case!  :).
								$error[] = esc_html__( 'There was a problem with your upload.', 'grand-media' );
								break;
						}
						break;
					}

					$fileinfo = $gmCore->fileinfo( $file_name );
					if ( false === $fileinfo ) {
						break;
					}

					$gmedia = (array) $data['item'];
					if ( current_user_can( 'gmedia_terms' ) ) {
						if ( empty( $gmedia['albums'] ) ) {
							$gmedia['terms']['gmedia_album'] = '';
						} else {
							$alb                             = isset( $gmedia['albums'][0]['term_id'] ) ? $gmedia['albums'][0]['term_id'] : $gmedia['albums'][0]['name'];
							$gmedia['terms']['gmedia_album'] = $alb;
						}
						if ( empty( $gmedia['categories'] ) ) {
							$gmedia['terms']['gmedia_category'] = '';
						} else {
							$categories = array();
							foreach ( $gmedia['categories'] as $category ) {
								$categories[] = isset( $category['term_id'] ) ? $category['term_id'] : $category['name'];
							}
							$gmedia['terms']['gmedia_category'] = implode( ',', $categories );
						}
						if ( empty( $gmedia['tags'] ) ) {
							$gmedia['terms']['gmedia_tag'] = '';
						} else {
							$tags = array();
							foreach ( $gmedia['tags'] as $tag ) {
								$tags[] = isset( $tag['term_id'] ) ? $tag['term_id'] : $tag['name'];
							}
							$gmedia['terms']['gmedia_tag'] = implode( ',', $tags );
						}
					}
					unset( $gmedia['categories'], $gmedia['albums'], $gmedia['tags'] );

					if ( isset( $gmedia['status'] ) && 'public' === $gmedia['status'] ) {
						$gmedia['status'] = 'publish';
					}

					$return = $gmCore->gmedia_upload_handler( $file_tmp, $fileinfo, 'multipart', $gmedia );
					if ( isset( $return['error'] ) ) {
						$error[] = $return['error']['message'];
					} else {
						$alert[] = $return['success']['message'];
					}
					break;

				case 'update_media':
					if ( ! current_user_can( 'gmedia_edit_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit media', 'grand-media' );
						break;
					}
					$gmedia = (array) $data['item'];
					if ( ! empty( $gmedia['ID'] ) ) {
						$item = $gmDB->get_gmedia( $gmedia['ID'] );
						if ( ! $item || ( $user_ID !== $item->author && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
							$error[] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
							break;
						}

						unset( $gmedia['date'], $gmedia['mime_type'], $gmedia['gmuid'], $gmedia['modified'] );
						//$gmedia['modified']  = current_time('mysql');
						if ( ! current_user_can( 'gmedia_delete_others_media' ) ) {
							$gmedia['author'] = $item->author;
						}
						if ( isset( $gmedia['status'] ) && 'public' === $gmedia['status'] ) {
							$gmedia['status'] = 'publish';
						}

						if ( current_user_can( 'gmedia_terms' ) ) {
							if ( empty( $gmedia['albums'] ) ) {
								$gmedia['terms']['gmedia_album'] = '';
							} else {
								if ( isset( $gmedia['albums'][0]['term_id'] ) ) {
									$gmedia['terms']['gmedia_album'] = $gmedia['albums'][0]['term_id'];
								} elseif ( current_user_can( 'gmedia_album_manage' ) ) {
									$gmedia['terms']['gmedia_album'] = $gmedia['albums'][0]['name'];
								}
							}
							if ( empty( $gmedia['categories'] ) ) {
								$gmedia['terms']['gmedia_category'] = '';
							} else {
								$categories = array();
								foreach ( $gmedia['categories'] as $category ) {
									if ( isset( $category['term_id'] ) ) {
										$categories[] = $category['term_id'];
									} elseif ( current_user_can( 'gmedia_category_manage' ) ) {
										$categories[] = $category['name'];
									}
								}
								$gmedia['terms']['gmedia_category'] = $categories;
							}
							if ( empty( $gmedia['tags'] ) ) {
								$gmedia['terms']['gmedia_tag'] = '';
							} else {
								$tags = array();
								foreach ( $gmedia['tags'] as $tag ) {
									if ( isset( $tag['term_id'] ) ) {
										$tags[] = $tag['term_id'];
									} elseif ( current_user_can( 'gmedia_tag_manage' ) ) {
										$tags[] = $tag['name'];
									}
								}
								$gmedia['terms']['gmedia_tag'] = $tags;
							}
						}
						unset( $gmedia['categories'], $gmedia['albums'], $gmedia['tags'] );

						$gmDB->insert_gmedia( $gmedia );
					}
					break;

				case 'assign_album':
					if ( ! current_user_can( 'gmedia_edit_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit media', 'grand-media' );
						break;
					}
					if ( ! current_user_can( 'gmedia_terms' ) ) {
						$error[] = esc_html__( 'You are not allowed to manage albums', 'grand-media' );
					}
					$term  = $data['assign_album'][0];
					$count = count( $data['selected'] );
					if ( '0' === $term ) {
						foreach ( $data['selected'] as $item ) {
							$gmDB->delete_gmedia_term_relationships( $item, 'gmedia_album' );
						}
						// translators: number.
						$alert[] = sprintf( esc_html__( '%d item(s) updated with "No Album"', 'grand-media' ), $count );
					} else {
						foreach ( $data['selected'] as $item ) {
							$gm_item = $gmDB->get_gmedia( $item );
							if ( ! $gm_item || ( $user_ID !== $gm_item->author && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
								continue;
							}
							$result = $gmDB->set_gmedia_terms( $item, $term, 'gmedia_album', 0 );
							if ( is_wp_error( $result ) ) {
								$error[] = $result->get_error_message();
								$count --;
							} elseif ( ! $result ) {
								$count --;
							}
						}
						if ( $gmCore->is_digit( $term ) ) {
							$alb_name = $gmDB->get_term_name( $term );
						} else {
							$alb_name = $term;
						}
						// translators: 1 - album name, 2 - number.
						$alert[] = sprintf( esc_html__( 'Album `%1$s` assigned to %2$d item(s)', 'grand-media' ), $alb_name, $count );
					}
					break;

				case 'assign_category':
					if ( ! current_user_can( 'gmedia_edit_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit media', 'grand-media' );
						break;
					}
					if ( ! current_user_can( 'gmedia_terms' ) ) {
						$error[] = esc_html__( 'You are not allowed to manage categories', 'grand-media' );
						break;
					}
					if ( empty( $data['assign_category'] ) ) {
						$error[] = esc_html__( 'No categories provided', 'grand-media' );
						break;
					}
					$terms = $data['assign_category'];
					$count = count( $data['selected'] );
					if ( 1 === count( $terms ) && '0' === $terms[0] ) {
						foreach ( $data['selected'] as $item ) {
							$gm_item = $gmDB->get_gmedia( $item );
							if ( ! $gm_item || ( $user_ID !== $gm_item->author && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
								continue;
							}
							$gmDB->delete_gmedia_term_relationships( $item, 'gmedia_category' );
						}
						// translators: number.
						$alert[] = sprintf( esc_html__( '%d item(s) updated with "Uncategorized"', 'grand-media' ), $count );
					} else {
						foreach ( $data['selected'] as $item ) {
							$gm_item = $gmDB->get_gmedia( $item );
							if ( ! $gm_item || ( $user_ID !== $gm_item->author && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
								continue;
							}
							$result = $gmDB->set_gmedia_terms( $item, $terms, 'gmedia_category', 0 );
							if ( is_wp_error( $result ) ) {
								$error[] = $result->get_error_message();
								$count --;
							} elseif ( ! $result ) {
								$count --;
							}
						}
						// translators: 1 - number, 2 - number.
						$alert[] = sprintf( esc_html__( '%1$d category(ies) added to %2$d item(s)', 'grand-media' ), count( $terms ), $count );
					}
					break;

				case 'unassign_category':
					if ( ! current_user_can( 'gmedia_edit_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit media', 'grand-media' );
						break;
					}
					if ( empty( $data['unassign_category'] ) ) {
						$error[] = esc_html__( 'No categories provided', 'grand-media' );
						break;
					}
					$terms = array_map( 'intval', $data['unassign_category'] );
					$count = count( $data['selected'] );
					foreach ( $data['selected'] as $item ) {
						$gm_item = $gmDB->get_gmedia( $item );
						if ( ! $gm_item || ( $user_ID !== $gm_item->author && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
							continue;
						}
						$result = $gmDB->set_gmedia_terms( $item, $terms, 'gmedia_category', - 1 );
						if ( is_wp_error( $result ) ) {
							$error[] = $result->get_error_message();
							$count --;
						} elseif ( ! $result ) {
							$count --;
						}
					}
					// translators: 1 - number, 2 - number.
					$alert[] = sprintf( esc_html__( '%1$d category(ies) deleted from %2$d item(s)', 'grand-media' ), count( $terms ), $count );
					break;

				case 'add_tags':
					if ( ! current_user_can( 'gmedia_edit_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit media', 'grand-media' );
						break;
					}
					if ( ! current_user_can( 'gmedia_terms' ) ) {
						$error[] = esc_html__( 'You are not allowed manage tags', 'grand-media' );
						break;
					}
					if ( empty( $data['add_tags'] ) ) {
						$error[] = esc_html__( 'No tags provided', 'grand-media' );
						break;
					}
					$terms = $data['add_tags'];
					$count = count( $data['selected'] );
					foreach ( $data['selected'] as $item ) {
						$gm_item = $gmDB->get_gmedia( $item );
						if ( ! $gm_item || ( $user_ID !== $gm_item->author && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
							continue;
						}
						$result = $gmDB->set_gmedia_terms( $item, $terms, 'gmedia_tag', 1 );
						if ( is_wp_error( $result ) ) {
							$error[] = $result->get_error_message();
							$count --;
						} elseif ( ! $result ) {
							$count --;
						}
					}
					// translators: 1 - number, 2 - number.
					$alert[] = sprintf( esc_html__( '%1$d tag(s) added to %2$d item(s)', 'grand-media' ), count( $terms ), $count );
					break;

				case 'add_cover':
					if ( ! current_user_can( 'gmedia_edit_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit media', 'grand-media' );
						break;
					}
					$cover = (int) $data['add_cover'];
					$count = count( $data['selected'] );
					foreach ( $data['selected'] as $item ) {
						$gm_item = $gmDB->get_gmedia( $item );
						if ( ! $gm_item || ( $user_ID !== $gm_item->author && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
							$count --;
							continue;
						}
						if ( 'image' === substr( $gm_item->mime_type, 0, 5 ) ) {
							$count --;
							continue;
						}
						if ( $cover ) {
							$gmDB->update_metadata( 'gmedia', $gm_item->ID, '_cover', $cover );
						} else {
							$gmDB->delete_metadata( 'gmedia', $gm_item->ID, '_cover' );
						}
					}
					// translators: 1 -number, 2 - number.
					$alert[] = sprintf( esc_html__( '%d item(s) updated', 'grand-media' ), $count );
					break;

				case 'delete_tags':
					if ( ! current_user_can( 'gmedia_edit_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit media', 'grand-media' );
						break;
					}
					if ( empty( $data['delete_tags'] ) ) {
						$error[] = esc_html__( 'No tags provided', 'grand-media' );
						break;
					}
					$terms = array_map( 'intval', $data['delete_tags'] );
					$count = count( $data['selected'] );
					foreach ( $data['selected'] as $item ) {
						$gm_item = $gmDB->get_gmedia( $item );
						if ( ! $gm_item || ( $user_ID !== $gm_item->author && ! current_user_can( 'gmedia_edit_others_media' ) ) ) {
							continue;
						}
						$result = $gmDB->set_gmedia_terms( $item, $terms, 'gmedia_tag', - 1 );
						if ( is_wp_error( $result ) ) {
							$error[] = $result->get_error_message();
							$count --;
						} elseif ( ! $result ) {
							$count --;
						}
					}
					// translators: 1 - number, 2 - number.
					$alert[] = sprintf( esc_html__( '%1$d tag(s) deleted from %2$d item(s)', 'grand-media' ), count( $terms ), $count );
					break;

				case 'delete':
					if ( ! current_user_can( 'gmedia_delete_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to delete this post.' );
						break;
					}
					$count = count( $data['selected'] );
					foreach ( $data['selected'] as $item ) {
						$gm_item = $gmDB->get_gmedia( $item );
						if ( ( (int) $gm_item->author !== $user_ID ) && ! current_user_can( 'gmedia_delete_others_media' ) ) {
							$error[] = "#{$item}: " . esc_html__( 'You are not allowed to delete media others media', 'grand-media' );
							continue;
						}
						if ( ! $gmDB->delete_gmedia( (int) $item ) ) {
							$error[] = "#{$item}: " . esc_html__( 'Error in deleting...', 'grand-media' );
							$count --;
						}
					}
					if ( $count ) {
						// translators: number.
						$alert[] = sprintf( esc_html__( '%d items deleted successfully', 'grand-media' ), $count );
					}
					break;
			}
			if ( 1 === $logic ) {
				$filter = gmedia_ios_app_library_data( array( 'filter', 'gmedia_category', 'gmedia_album', 'gmedia_tag' ) );
			}
			$out = array_merge( $out, $filter );
			break;

		case 'library':
			$cache_expiration = isset( $gmGallery->options['cache_expiration'] ) ? (int) $gmGallery->options['cache_expiration'] * HOUR_IN_SECONDS : 24 * HOUR_IN_SECONDS;
			if ( $cache && $cache_expiration ) {
				$cache_key   = 'gm_cache_' . md5( wp_json_encode( array( (int) $user_ID, $data, $filter, $gmmodule, $gmapp_version ) ) );
				$cache_value = get_transient( $cache_key );
				if ( ! empty( $cache_value ) && is_array( $cache_value ) ) {
					$out = $cache_value;
					break;
				}
			}

			$ep = $gmGallery->options['endpoint'];
			if ( get_option( 'permalink_structure' ) ) {
				$share_link_base = home_url( rawurlencode( $ep ) . '/$2/$1' );
			} else {
				$share_link_base = add_query_arg( array( "$ep" => '$1', 't' => '$2' ), home_url( 'index.php' ) );
			}
			$filter = $filter ? gmedia_ios_app_library_data( array( 'filter' ) ) : array();

			$mime_type = ( $logic > 2 ) ? array( 'image', 'audio' ) : 'image';
			$args      = array(
				'mime_type'    => $mime_type,
				'orderby'      => 'ID',
				'order'        => 'DESC',
				'per_page'     => 100,
				'page'         => 1,
				'tag__in'      => null,
				'category__in' => null,
				'album__in'    => null,
				'gmedia__in'   => null,
				'author'       => 0,
				'status'       => null,
			);

			if ( $gmmodule ) {
				$args['per_page'] = - 1;
			}

			$terms_ids_query = array();
			if ( ! empty( $data['tag__in'] ) ) {
				$tag_ids = wp_parse_id_list( $data['tag__in'] );
				if ( empty( $data['category__in'] ) && empty( $data['album__in'] ) ) {
					$args['orderby'] = $gmGallery->options['in_tag_orderby'];
					$args['order']   = $gmGallery->options['in_tag_order'];
				}
				$terms_ids_query = array_merge( $terms_ids_query, $tag_ids );
			}
			if ( ! empty( $data['category__in'] ) ) {
				$cat_ids = wp_parse_id_list( $data['category__in'] );
				if ( 1 === count( $cat_ids ) ) {
					$cat_meta        = $gmDB->get_metadata( 'gmedia_term', $cat_ids[0] );
					$args['orderby'] = ! empty( $cat_meta['_orderby'][0] ) ? $cat_meta['_orderby'][0] : $gmGallery->options['in_category_orderby'];
					$args['order']   = ! empty( $cat_meta['_order'][0] ) ? $cat_meta['_order'][0] : $gmGallery->options['in_category_order'];
				}
				$terms_ids_query = array_merge( $terms_ids_query, $cat_ids );
			}
			if ( ! empty( $data['album__in'] ) ) {
				$alb_ids = wp_parse_id_list( $data['album__in'] );
				if ( 1 === count( $alb_ids ) ) {
					$album_meta      = $gmDB->get_metadata( 'gmedia_term', $alb_ids[0] );
					$args['orderby'] = ! empty( $album_meta['_orderby'][0] ) ? $album_meta['_orderby'][0] : $gmGallery->options['in_album_orderby'];
					$args['order']   = ! empty( $album_meta['_order'][0] ) ? $album_meta['_order'][0] : $gmGallery->options['in_album_order'];
				}
				$terms_ids_query = array_merge( $terms_ids_query, $alb_ids );
			}

			$data      = wp_parse_args( $data, $args );
			$false_out = array_merge(
				$filter,
				array(
					'properties' => array(
						'request' => isset( $data['request'] ) ? $data['request'] : null,
					),
					'data'       => array(),
				)
			);

			$terms_ids = array();
			if ( ! empty( $terms_ids_query ) ) {
				$terms_ids = $gmDB->get_terms( array( 'gmedia_album', 'gmedia_category', 'gmedia_tag' ), array( 'include' => $terms_ids_query ) );
				if ( ! empty( $terms_ids ) && ! is_wp_error( $terms_ids ) ) {
					foreach ( $terms_ids as $i => $term ) {
						gmedia_ios_app_term_data_extend( $terms_ids[ $i ], $share_link_base );
					}
					$terms_ids = array_filter( $terms_ids );
					if ( empty( $terms_ids ) ) {
						$out = $false_out;
						break;
					}
				} else {
					$terms_ids = array();
				}
			}
			$req_terms = array();
			if ( ! empty( $terms_ids ) ) {
				foreach ( $terms_ids as $term ) {
					$taxterm = str_replace( 'gmedia_', '', $term->taxonomy );

					$req_terms["{$taxterm}__in"]["{$term->term_id}"] = $term;
				}
			}

			$is_admin = isset( $data['admin'] ) ? intval( $data['admin'] ) : 0;
			if ( ! is_user_logged_in() ) {
				$logged_in      = false;
				$data['status'] = array( 'publish' );
				if ( ! empty( $req_terms['album__in'] ) ) {
					$break = false;
					foreach ( $req_terms['album__in'] as $alb ) {
						if ( ! ( isset( $alb->status ) && ( 'publish' === $alb->status ) ) ) {
							$break = true;
						}
					}
					if ( $break ) {
						$out = $false_out;
						break;
					}
				}
			} else {
				$logged_in = true;
				if ( $is_admin && ! current_user_can( 'gmedia_library' ) ) {
					$out = $false_out;
					break;
				}
				if ( $is_admin && ! current_user_can( 'gmedia_show_others_media' ) ) {
					$data['author'] = $user_ID;
				}
			}

			$_data             = $data;
			$_data['per_page'] = - 1;
			$_data['status']   = null;
			$_data['fields']   = 'ids';
			$all_gmedias_ids   = $gmDB->get_gmedias( $_data );
			$gmedias           = $gmDB->get_gmedias( $data );
			$properties        = array_merge(
				$req_terms,
				array(
					'request'      => isset( $data['request'] ) ? $data['request'] : null,
					'total_pages'  => $gmDB->pages,
					'current_page' => $gmDB->openPage,
					'items_count'  => $gmDB->resultPerPage,
					'total_count'  => $gmDB->totalResult,
					'count'        => count( $all_gmedias_ids ),
					//'args' => $data
				)
			);
			foreach ( $gmedias as $i => $item ) {

				//if((!$logged_in && 'publish' !== $item->status) || (!$is_admin && ('draft' === $item->status) && ((int)$user_ID !== (int)$item->author))) {
				if ( ( ! $is_admin || ( $is_admin && ! current_user_can( 'gmedia_edit_others_media' ) ) ) && ( ( 'draft' === $item->status ) && ! ( (int) $user_ID === (int) $item->author || current_user_can( 'manage_options' ) ) ) ) {
					unset( $gmedias[ $i ] );
					$properties['total_count'] --;
					$properties['items_count'] --;
					continue;
				}

				$author_id  = $item->author;
				$authordata = get_userdata( $author_id );
				if ( $authordata ) {
					$display_name = $authordata->display_name;
					$first_name   = $authordata->first_name;
					$last_name    = $authordata->last_name;
				} else {
					$display_name = esc_html__( 'Deleted User', 'grand-media' );
					$first_name   = '';
					$last_name    = '';
				}
				$gmedias[ $i ]->user = array(
					'id'          => $author_id,
					'displayname' => $display_name,
					'firstname'   => $first_name,
					'last_name'   => $last_name,
				);
				$gmedias[ $i ]->date = strtotime( $item->date );

				$meta = $gmDB->get_metadata( 'gmedia', $item->ID );
				//$_metadata        = maybe_unserialize( $meta['_metadata'][0] );
				$_metadata = $meta['_metadata'][0];
				unset( $meta['_metadata'] );

				$type               = explode( '/', $item->mime_type );
				$item_url           = $gmCore->upload['url'] . '/' . $gmGallery->options['folder'][ $type[0] ] . '/' . $item->gmuid;
				$gmedias[ $i ]->url = $item_url;
				$terms              = $gmDB->get_the_gmedia_terms( $item->ID, 'gmedia_tag' );
				$tags               = array();
				if ( $terms ) {
					$terms = array_values( (array) $terms );
					foreach ( $terms as $term ) {
						$tags[] = array( 'term_id' => $term->term_id, 'name' => $term->name );
					}
				}
				$gmedias[ $i ]->tags = $tags;

				$terms  = $gmDB->get_the_gmedia_terms( $item->ID, 'gmedia_album' );
				$albums = array();
				if ( $terms ) {
					$terms = array_values( (array) $terms );
					foreach ( $terms as $term ) {
						$albums[] = array(
							'term_id' => $term->term_id,
							'name'    => $term->name,
							'status'  => ( 1 === $logic && 'publish' === $term->status ) ? 'public' : $term->status,
						);
					}
				}
				$gmedias[ $i ]->albums = $albums;

				$terms      = $gmDB->get_the_gmedia_terms( $item->ID, 'gmedia_category' );
				$categories = array();
				if ( $terms ) {
					$terms = array_values( (array) $terms );
					foreach ( $terms as $term ) {
						$categories[] = array( 'term_id' => $term->term_id, 'name' => $term->term_id, 'title' => $term->name );
					}
				}
				$gmedias[ $i ]->categories = $categories;

				if ( 'image' === $type[0] ) {
					$gmedias[ $i ]->meta                  = array(
						'thumb'    => $_metadata['thumb'],
						'web'      => $_metadata['web'],
						'original' => $_metadata['original'],
					);
					$gmedias[ $i ]->meta['thumb']['link'] = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image_thumb']}/{$item->gmuid}";
					$gmedias[ $i ]->meta['web']['link']   = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image']}/{$item->gmuid}";
					if ( is_file( "{$gmCore->upload['path']}/{$gmGallery->options['folder']['image_original']}/{$item->gmuid}" ) ) {
						$gmedias[ $i ]->meta['original']['link'] = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image_original']}/{$item->gmuid}";
					} else {
						$gmedias[ $i ]->meta['original']['link'] = '';
					}

					if ( isset( $_metadata['image_meta'] ) ) {
						$gmedias[ $i ]->meta['data'] = $_metadata['image_meta'];
					}
					unset( $meta['image_meta'] );

				} else {
					$cover_gmedia = false;
					if ( ! empty( $meta['_cover'][0] ) ) {
						$cover_gmedia = $gmDB->get_gmedia( $meta['_cover'][0] );
						if ( $cover_gmedia ) {
							$cover_metadata                       = $gmDB->get_metadata( 'gmedia', $cover_gmedia->ID, '_metadata', true );
							$gmedias[ $i ]->meta                  = array(
								'thumb'    => $cover_metadata['thumb'],
								'web'      => $cover_metadata['web'],
								'original' => $cover_metadata['original'],
							);
							$gmedias[ $i ]->meta['thumb']['link'] = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image_thumb']}/{$cover_gmedia->gmuid}";
							$gmedias[ $i ]->meta['web']['link']   = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image']}/{$cover_gmedia->gmuid}";
							if ( is_file( "{$gmCore->upload['path']}/{$gmGallery->options['folder']['image_original']}/{$cover_gmedia->gmuid}" ) ) {
								$gmedias[ $i ]->meta['original']['link'] = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image_original']}/{$cover_gmedia->gmuid}";
							} else {
								$gmedias[ $i ]->meta['original']['link'] = '';
							}
						}
					}
					unset( $meta['_cover'] );

					if ( ! $cover_gmedia ) {
						$gmedias[ $i ]->meta = array(
							'thumb' => array(
								'link'   => $gmCore->gm_get_media_image( $item, 'thumb', false ),
								'width'  => 300,
								'height' => 300,
							),
						);
					}
					if ( ! empty( $_metadata ) ) {
						$gmedias[ $i ]->meta['data'] = $_metadata;
					}
				}

				$gmedias[ $i ]->meta['views'] = 0;
				$gmedias[ $i ]->meta['likes'] = 0;
				if ( isset( $meta['views'][0] ) ) {
					$gmedias[ $i ]->meta['views'] = $meta['views'][0];
				}
				unset( $meta['views'] );

				if ( isset( $meta['likes'][0] ) ) {
					$gmedias[ $i ]->meta['likes'] = $meta['likes'][0];
				}
				unset( $meta['likes'] );

				if ( ! empty( $meta['_gps'][0] ) ) {
					$gmedias[ $i ]->meta['data']['GPS'] = $meta['_gps'][0];
				}
				unset( $meta['_gps'] );

				if ( isset( $meta['_rating'][0] ) ) {
					$gmedias[ $i ]->meta['rating'] = maybe_unserialize( $meta['_rating'][0] );
				}
				unset( $meta['_rating'] );

				if ( isset( $meta['_related'][0] ) ) {
					$gmedias[ $i ]->meta['related'] = maybe_unserialize( $meta['_related'][0] );
				}
				unset( $meta['_related'] );
				unset( $meta['_created_timestamp'], $meta['_hash'], $meta['_image_alt'] );

				if ( ! empty( $meta ) ) {
					foreach ( $meta as $key => $val ) {
						if ( '_peaks' === $key ) {
							$gmedias[ $i ]->meta[ $key ] = json_decode( $val[0] );
						} else {
							$gmedias[ $i ]->meta[ $key ] = maybe_unserialize( $val );
						}
					}
				}

				//$item_name = $item->title ? $item->title : pathinfo( $item->gmuid, PATHINFO_FILENAME );
				if ( ! empty( $gmedias[ $i ]->post_id ) ) {
					$gmedias[ $i ]->sharelink = get_permalink( $gmedias[ $i ]->post_id );
				} else {
					$gmedia_hashid            = gmedia_hash_id_encode( $item->ID, 'single' );
					$gmedias[ $i ]->sharelink = str_replace(
						array( '$1', '$2' ),
						array(
							rawurlencode( $gmedia_hashid ),
							's',
						),
						$share_link_base
					);
				}
				if ( 1 === $logic && 'publish' === $item->status ) {
					$gmedias[ $i ]->status = 'public';
				}
			}
			$out = array_merge(
				$filter,
				array(
					'properties' => $properties,
					'data'       => array_values( $gmedias ),
				)
			);

			if ( $cache && $cache_expiration ) {
				set_transient( $cache_key, $out, $cache_expiration );
			}

			break;
		case 'delete_term':
			$taxonomy = $data['taxonomy'];
			if ( ! empty( $data['items'] ) ) {
				if ( ! current_user_can( 'gmedia_terms_delete' ) ) {
					$error[] = esc_html__( 'You have no permission to do this operation', 'grand-media' );
					break;
				}
				$count = count( $data['items'] );
				foreach ( $data['items'] as $item ) {
					if ( ! current_user_can( 'gmedia_edit_others_media' ) ) {
						if ( 'gmedia_album' === $taxonomy ) {
							$term = $gmDB->get_term( $item, $taxonomy );
							if ( (int) $term->global !== (int) $user_ID ) {
								$error['delete_album'] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
								$count --;
								continue;
							}
						} else {
							$error[] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
							$count --;
							continue;
						}
					}
					$delete = $gmDB->delete_term( $item );
					if ( is_wp_error( $delete ) ) {
						$error[] = $delete->get_error_message();
						$count --;
					}
				}
				if ( $count ) {
					// translators: number.
					$alert[] = sprintf( esc_html__( '%d items deleted successfully', 'grand-media' ), $count );
				}
			}
			$out = gmedia_ios_app_library_data( array( 'filter', $taxonomy ) );
			break;
		case 'add_term':
			$taxonomy  = $data['taxonomy'];
			$edit_term = isset( $data['term_id'] ) ? (int) $data['term_id'] : 0;
			$term      = $data;
			$term_id   = 0;
			if ( 'gmedia_album' === $taxonomy ) {
				if ( ! current_user_can( 'gmedia_album_manage' ) ) {
					$out['error'] = array(
						'code'    => 'nocapability',
						'title'   => esc_html__( "You can't do this", 'grand-media' ),
						'message' => esc_html__( 'You have no permission to do this operation', 'grand-media' ),
					);

					return $out;
				}
				$args = array();
				do {
					$term['name'] = trim( $term['name'] );
					if ( empty( $term['name'] ) ) {
						$error[] = esc_html__( 'Term Name is not specified', 'grand-media' );
						break;
					}
					if ( $gmCore->is_digit( $term['name'] ) ) {
						$error[] = esc_html__( "Term Name can't be only digits", 'grand-media' );
						break;
					}
					if ( 1 === $logic && isset( $term['status'] ) && 'public' === $term['status'] ) {
						$term['status'] = 'publish';
					}
					if ( $edit_term && ! $gmDB->term_exists( $edit_term, $taxonomy ) ) {
						$error[]   = esc_html__( 'A term with the id provided does not exists', 'grand-media' );
						$edit_term = false;
					}
					$term_author = isset( $term['global'] ) ? $term['global'] : false;
					$term_id     = $gmDB->term_exists( $term['name'], $taxonomy, $term_author );
					if ( $term_id && $term_id !== $edit_term ) {
						$error[]                         = esc_html__( 'A term with the name provided already exists', 'grand-media' );
						$error_info['terms'][ $term_id ] = $term['name'];
						break;
					}
					if ( $edit_term ) {
						$_term = $gmDB->get_term( $edit_term, $taxonomy );
						if ( ( (int) $_term->global !== (int) $user_ID ) && ! current_user_can( 'gmedia_edit_others_media' ) ) {
							$error[] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
							break;
						}
						$term_id = $gmDB->update_term( $edit_term, $term );
					} else {
						if ( ! current_user_can( 'gmedia_edit_others_media' ) ) {
							$term['global'] = intval( $user_ID );
						}
						$term_id = $gmDB->insert_term( $term['name'], $taxonomy, $term );
					}
					if ( is_wp_error( $term_id ) ) {
						$error[] = $term_id->get_error_message();
						$term_id = 0;
						break;
					}
					$alert_info['terms'][ $term_id ] = $term['name'];
					$term_meta                       = array();
					if ( isset( $term['_orderby'] ) ) {
						$term_meta['_orderby'] = $term['_orderby'];
					} elseif ( isset( $term['orderby'] ) ) {
						$term_meta['_orderby'] = $term['orderby'];
					}
					if ( isset( $term['_order'] ) ) {
						$term_meta['_order'] = $term['_order'];
					} elseif ( isset( $term['order'] ) ) {
						$term_meta['_order'] = $term['order'];
					}
					if ( isset( $term['cover_id'] ) ) {
						$term_meta['_cover'] = (int) $term['cover_id'] ? $term['cover_id'] : '';
					}
					foreach ( $term_meta as $key => $value ) {
						$gmDB->update_metadata( 'gmedia_term', $term_id, $key, $value );
					}

					// translators: album name.
					$alert[] = sprintf( esc_html__( 'Album `%s` successfully saved', 'grand-media' ), $term['name'] );

				} while ( 0 );
				if ( 1 < $logic && $edit_term && $term_id ) {
					$lib_data = array( 'album__in' => array( $term_id ), 'admin' => 1 );
					if ( ! empty( $data['per_page'] ) ) {
						$lib_data['per_page'] = $data['per_page'];
					}
					$out = gmedia_ios_app_processor( 'library', $lib_data, true, false );
				} else {
					$out = gmedia_ios_app_library_data( array( 'filter', $taxonomy ), $args );
				}
			} elseif ( 'gmedia_category' === $taxonomy ) {
				if ( ! current_user_can( 'gmedia_category_manage' ) ) {
					$out['error'] = array(
						'code'    => 'nocapability',
						'title'   => esc_html__( "You can't do this", 'grand-media' ),
						'message' => esc_html__( 'You have no permission to do this operation', 'grand-media' ),
					);

					return $out;
				}
				$args = array();
				if ( $edit_term ) {
					if ( ! current_user_can( 'gmedia_edit_others_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
						break;
					}
					$term['name']    = trim( $term['name'] );
					$term['term_id'] = intval( $term['term_id'] );
					if ( $term['name'] && ! $gmCore->is_digit( $term['name'] ) ) {
						$term_id = $gmDB->term_exists( $term['term_id'], $taxonomy );
						if ( $term_id ) {
							$db_term = $gmDB->get_term( $term_id );
							if ( ( $db_term->name === $term['name'] ) || ( $db_term->name !== $term['name'] && ! ( $gmDB->term_exists( $term['name'], $taxonomy ) ) ) ) {
								$term_id = $gmDB->update_term( $term['term_id'], $term );
								if ( is_wp_error( $term_id ) ) {
									$error[] = $term_id->get_error_message();
									$term_id = 0;
								} else {
									// translators: number.
									$alert[]                         = sprintf( esc_html__( 'Category %d successfully updated', 'grand-media' ), $term_id );
									$alert_info['terms'][ $term_id ] = $term['name'];
									if ( 1 < $logic ) {
										$args['include'][] = $term_id;
									}

									$term_meta = array();
									if ( isset( $term['_orderby'] ) ) {
										$term_meta['_orderby'] = $term['_orderby'];
									} elseif ( isset( $term['orderby'] ) ) {
										$term_meta['_orderby'] = $term['orderby'];
									}
									if ( isset( $term['_order'] ) ) {
										$term_meta['_order'] = $term['_order'];
									} elseif ( isset( $term['order'] ) ) {
										$term_meta['_order'] = $term['order'];
									}
									foreach ( $term_meta as $key => $value ) {
										$gmDB->update_metadata( 'gmedia_term', $term_id, $key, $value );
									}
								}
							} else {
								$error[]                         = esc_html__( 'A term with the name provided already exists', 'grand-media' );
								$error_info['terms'][ $term_id ] = $term['name'];
							}
						} else {
							$error[] = esc_html__( 'A term with the id provided does not exists', 'grand-media' );
						}
					} else {
						$error[] = esc_html__( "Term name can't be only digits or empty", 'grand-media' );
					}
				} else {
					$terms       = array_filter( array_map( 'trim', explode( ',', $term['name'] ) ) );
					$terms_added = 0;
					$terms_qty   = count( $terms );
					foreach ( $terms as $term_name ) {
						if ( $gmCore->is_digit( $term_name ) ) {
							continue;
						}

						$term_id = $gmDB->term_exists( $term_name, $taxonomy );
						if ( ! $term_id ) {
							$term_id = $gmDB->insert_term( $term_name, $taxonomy );
							if ( is_wp_error( $term_id ) ) {
								$error[] = $term_id->get_error_message();
								$term_id = 0;
							} else {
								// translators: 1 - number, 2 - number.
								$alert['category_add']           = sprintf( esc_html__( '%1$d of %2$d categories successfully added', 'grand-media' ), ++ $terms_added, $terms_qty );
								$alert_info['terms'][ $term_id ] = $term_name;
							}
						} else {
							$alert['category_add']           = esc_html__( 'Some of provided categories are already exists', 'grand-media' );
							$alert_info['terms'][ $term_id ] = $term_name;
						}
					}
				}
				if ( 1 < $logic && $edit_term && $term_id ) {
					$lib_data = array( 'category__in' => array( $term_id ), 'admin' => 1 );
					if ( ! empty( $data['per_page'] ) ) {
						$lib_data['per_page'] = $data['per_page'];
					}
					$out = gmedia_ios_app_processor( 'library', $lib_data, true, false );
				} else {
					$out = gmedia_ios_app_library_data( array( 'filter', $taxonomy ), $args );
				}
			} elseif ( 'gmedia_tag' === $taxonomy ) {
				if ( ! current_user_can( 'gmedia_tag_manage' ) ) {
					$out['error'] = array(
						'code'    => 'nocapability',
						'title'   => esc_html__( "You can't do this", 'grand-media' ),
						'message' => esc_html__( 'You have no permission to do this operation', 'grand-media' ),
					);

					return $out;
				}
				$args = array();
				if ( $edit_term ) {
					if ( ! current_user_can( 'gmedia_edit_others_media' ) ) {
						$error[] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
						break;
					}
					$term['name']    = trim( $term['name'] );
					$term['term_id'] = intval( $term['term_id'] );
					if ( $term['name'] && ! $gmCore->is_digit( $term['name'] ) ) {
						$term_id = $gmDB->term_exists( $term['term_id'], $taxonomy );
						if ( $term_id ) {
							if ( ! ( $gmDB->term_exists( $term['name'], $taxonomy ) ) ) {
								$term_id = $gmDB->update_term( $term['term_id'], $term );
								if ( is_wp_error( $term_id ) ) {
									$error[] = $term_id->get_error_message();
									$term_id = 0;
								} else {
									// translators: number.
									$alert[]                         = sprintf( esc_html__( 'Tag %d successfully updated', 'grand-media' ), $term_id );
									$alert_info['terms'][ $term_id ] = $term['name'];
								}
							} else {
								$error[]                         = esc_html__( 'A term with the name provided already exists', 'grand-media' );
								$error_info['terms'][ $term_id ] = $term['name'];
							}
						} else {
							$error[] = esc_html__( 'A term with the id provided does not exists', 'grand-media' );
						}
					} else {
						$error[] = esc_html__( "Term name can't be only digits or empty", 'grand-media' );
					}
				} else {
					$terms       = array_filter( array_map( 'trim', explode( ',', $term['name'] ) ) );
					$terms_added = 0;
					$terms_qty   = count( $terms );
					foreach ( $terms as $term_name ) {
						if ( $gmCore->is_digit( $term_name ) ) {
							continue;
						}

						$term_id = $gmDB->term_exists( $term_name, $taxonomy );
						if ( ! $term_id ) {
							$term_id = $gmDB->insert_term( $term_name, $taxonomy );
							if ( is_wp_error( $term_id ) ) {
								$error[] = $term_id->get_error_message();
								$term_id = 0;
							} else {
								// translators: 1 - number, 2 - number.
								$alert['tag_add']                = sprintf( esc_html__( '%1$d of %2$d tags successfully added', 'grand-media' ), ++ $terms_added, $terms_qty );
								$alert_info['terms'][ $term_id ] = $term_name;
							}
						} else {
							$alert['tag_add']                = esc_html__( 'Some of provided tags are already exists', 'grand-media' );
							$alert_info['terms'][ $term_id ] = $term_name;
						}
					}
				}
				if ( 1 < $logic && $edit_term && $term_id ) {
					$lib_data = array( 'tag__in' => array( $term_id ), 'admin' => 1 );
					if ( ! empty( $data['per_page'] ) ) {
						$lib_data['per_page'] = $data['per_page'];
					}
					$out = gmedia_ios_app_processor( 'library', $lib_data, true, false );
				} else {
					$out = gmedia_ios_app_library_data( array( 'filter', $taxonomy ), $args );
				}
			}
			break;
		default:
			break;
	}

	if ( ! empty( $error ) ) {
		$out['error'] = array( 'code' => $action, 'title' => 'ERROR', 'message' => implode( "\n", $error ) );
		if ( ! empty( $error_info ) ) {
			$out['error'] = array_merge( $out['error'], $error_info );
		}
	}
	if ( ! empty( $alert ) ) {
		$out['alert'] = array( 'title' => 'Success', 'message' => implode( "\n", $alert ) );
		if ( ! empty( $alert_info ) ) {
			$out['alert'] = array_merge( $out['alert'], $alert_info );
		}
	}

	return $out;
}


/**
 * @param array $data
 */
function gmedia_ios_app_counters( $data ) {
	global $gmDB;

	$data          = (array) $data;
	$items_counter = array();
	if ( ! empty( $data['views'] ) ) {
		foreach ( $data['views'] as $gmID ) {
			$items_counter[ $gmID ]['views'] = 1;
		}
		if ( ! empty( $data['likes'] ) ) {
			foreach ( $data['likes'] as $gmID ) {
				$items_counter[ $gmID ]['likes'] = 1;
			}
		}
	}

	if ( ! empty( $items_counter ) ) {
		foreach ( $items_counter as $gmID => $counters ) {
			if ( null === $gmDB->get_gmedia( $gmID ) ) {
				continue;
			}
			$counters['views'] = $gmDB->get_metadata( 'gmedia', $gmID, 'views', true );
			$counters['views'] += 1;
			$gmDB->update_metadata( 'gmedia', $gmID, 'views', $counters['views'] );
			do_action( 'gmedia_view', $gmID );
			if ( isset( $counters['likes'] ) ) {
				$counters['likes'] = $gmDB->get_metadata( 'gmedia', $gmID, 'likes', true );
				$counters['likes'] += 1;
				$gmDB->update_metadata( 'gmedia', $gmID, 'likes', $counters['likes'] );
				do_action( 'gmedia_like', $gmID );
			}
		}
	}

}

$time += microtime( true );
//$time = microtime(true) - _SERVER["REQUEST_TIME_FLOAT"];
$out['microtime'] = $time;
$out['key']       = $gmedia_options['license_key'];

header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
header( 'Access-Control-Allow-Origin: *' );
echo wp_json_encode( $out );
