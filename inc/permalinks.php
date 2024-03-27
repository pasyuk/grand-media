<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * gmediaPermalinks class.
 */
class gmediaPermalinks {

	private $endpoint = 'gmedia';

	/**
	 * __construct function.
	 */
	public function __construct() {
		add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'parse_request', array( $this, 'handler' ) );
		add_action( 'parse_query', array( $this, 'bridge' ) );

		add_filter( 'post_thumbnail_html', array( $this, 'gmedia_post_thumbnail' ), 10, 5 );
		add_filter( 'gmedia_shortcode_query', array( $this, 'gmedia_shortcode_query' ), 10, 2 );

		add_filter( 'show_admin_bar', array( $this, 'comments_admin_bar_hide' ) );
		add_action( 'single_template', array( $this, 'comments_gmedia_template_redirect' ) );
		add_filter( 'comment_post_redirect', array( $this, 'redirect_after_comment' ), 10, 2 );

	}

	/**
	 * Change the template used when the gmedia post permalink has ?comments
	 *
	 * @param string $templates
	 *
	 * @return string
	 */
	public function comments_gmedia_template_redirect( $templates = '' ) {
		if ( ! ( isset( $_GET['comments'] ) && is_singular( array( 'gmedia', 'gmedia_album', 'gmedia_gallery' ) ) ) ) {
			return $templates;
		}

		$templates = locate_template( 'gmedia_comments-popup.php', false );
		if ( empty( $templates ) ) {
			$templates = GMEDIA_ABSPATH . 'template/comments-popup.php';
		}

		return $templates;
	}

	/**
	 * @param $show_admin_bar
	 *
	 * @return string
	 */
	public function comments_admin_bar_hide( $show_admin_bar ) {
		if ( ! ( isset( $_GET['comments'] ) && is_singular( array( 'gmedia', 'gmedia_album', 'gmedia_gallery' ) ) ) ) {
			return $show_admin_bar;
		}

		return false;
	}

	/**
	 * @param $location
	 *
	 * @param $comment
	 *
	 * @return string
	 */
	public function redirect_after_comment( $location, $comment ) {
		global $wpdb;

		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return $location;
		}

		$ref        = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		$queryParts = explode( '#', $ref, 2 );
		$queryParts = explode( '?', $queryParts[0], 2 );
		if ( ! ( isset( $queryParts[1] ) && ! empty( $queryParts[1] ) ) ) {
			return $location;
		}
		$queryParts = explode( '&', $queryParts[1] );
		$params     = array();
		foreach ( $queryParts as $param ) {
			$item               = explode( '=', $param );
			$params[ $item[0] ] = isset( $item[1] ) ? $item[1] : '';
		}
		if ( ! isset( $params['comments'] ) ) {
			return $location;
		}

		$post = get_post( $comment->comment_post_ID );

		if ( ! in_array( $post->post_type, array( 'gmedia', 'gmedia_album', 'gmedia_gallery' ), true ) ) {
			return $location;
		}

		return $ref . '#comment-' . $wpdb->insert_id;
	}

	/**
	 * @param $rules
	 *
	 * @return array
	 */
	public function add_rewrite_rules( $rules ) {
		global $wp_rewrite, $gmGallery;
		$this->endpoint = ! empty( $gmGallery->options['endpoint'] ) ? $gmGallery->options['endpoint'] : 'gmedia';

		$this->add_endpoint();

		$new_rules = array(
			$this->endpoint . '/(g|s|a|t|k|u)/(.+?)/?$' => 'index.php?' . $this->endpoint . '=' . $wp_rewrite->preg_index( 2 ) . '&t=' . $wp_rewrite->preg_index( 1 ),
			'gmedia-app/?$'                             => 'index.php?gmedia-app=1',
		);

		return $new_rules + $rules;
	}

	/**
	 * add_endpoint function.
	 *
	 * @access public
	 * @return void
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( $this->endpoint, EP_NONE );
		add_rewrite_endpoint( 'gmedia-app', EP_NONE );
	}

	/**
	 * add_query_vars function.
	 *
	 * @access public
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		global $gmGallery;
		$endpoint = ! empty( $gmGallery->options['endpoint'] ) ? $gmGallery->options['endpoint'] : 'gmedia';

		$vars[] = $endpoint;
		$vars[] = 't';

		$vars[] = 'gmedia-app';

		return $vars;
	}

	/**
	 * Listen for gmedia requets and show gallery template.
	 *
	 * @access public
	 *
	 * @param $wp - global variable
	 */
	public function handler( $wp ) {
		global $gmGallery;
		$endpoint = ! empty( $gmGallery->options['endpoint'] ) ? $gmGallery->options['endpoint'] : 'gmedia';
		if ( isset( $wp->query_vars[ $endpoint ] ) && isset( $wp->query_vars['t'] ) && in_array( $wp->query_vars['t'], array( 'g', 'a', 't', 's', 'k', 'u' ), true ) ) {

			global $wp_query;
			$wp_query->is_single  = false;
			$wp_query->is_page    = false;
			$wp_query->is_archive = false;
			$wp_query->is_search  = false;
			$wp_query->is_home    = false;

			/*
			$template = get_query_template( 'gmedia-gallery' );
			// Get default slug-name.php
			if ( ! $template ) {
				$template = GMEDIA_ABSPATH . "/load-template.php";
			}

			load_template( $template, false );
			*/

			define( 'GMEDIACLOUD_PAGE', true );

			/* @noinspection PhpIncludeInspection */
			require_once GMEDIA_ABSPATH . 'load-template.php';

			exit();

		}

		/* Application only template */
		$is_app = ( isset( $wp->query_vars['gmedia-app'] ) && ! empty( $wp->query_vars['gmedia-app'] ) );
		if ( $is_app ) {

			global $wp_query;
			$wp_query->is_single  = false;
			$wp_query->is_page    = false;
			$wp_query->is_archive = false;
			$wp_query->is_search  = false;
			$wp_query->is_home    = false;

			$template = GMEDIA_ABSPATH . 'app/access.php';

			load_template( $template, false );
			exit();

		}

	}

	/**
	 * Listen for gmServiceLink query
	 *
	 * @access public
	 *
	 * @param $wp - global variable
	 */
	public function bridge( $wp ) {
		// phpcs:ignore
		if ( isset( $_GET['gmServiceLink'] ) ) {
			// phpcs:ignore
			$transient_key = preg_replace( '/[^A-Za-z0-9_]/', '', sanitize_text_field( wp_unslash( $_GET['gmServiceLink'] ) ) );
			$result        = get_transient( $transient_key );
			if ( false !== $result ) {
				delete_transient( $transient_key );
				header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
				echo wp_json_encode( $result );
				die();
			}
		}
	}

	/**
	 * Filter for the post content
	 *
	 * @param string       $html
	 * @param int          $post_id
	 * @param int          $post_thumbnail_id
	 * @param string|array $size Optional. Image size.  Defaults to 'thumbnail'.
	 * @param string|array $attr Optional. Query string or array of attributes.
	 *
	 * @return string html output
	 */
	public function gmedia_post_thumbnail( $html, $post_id = 0, $post_thumbnail_id = 0, $size = 'post-thumbnail', $attr = '' ) {

		if ( $post_thumbnail_id ) {
			$gmedia_id = get_post_meta( $post_thumbnail_id, '_gmedia_image_id', true );
			if ( ! empty( $gmedia_id ) ) {
				$html = str_replace( 'wp-post-image', 'wp-post-image gmedia-post-thumbnail-' . $gmedia_id, $html );
			}
		}

		return $html;
	}

	/**
	 * Filter for the shortcode gallery data
	 *
	 * @param array  $query
	 * @param string $id
	 *
	 * @return array $query
	 */
	public function gmedia_shortcode_query( $query, $id = '' ) {
		global $gmCore, $gmDB, $gmGallery;

		//$gmCore->replace_array_keys($query, array('album__in' => 'gmedia_album', 'tag__in' => 'gmedia_tag', 'category__in' => 'gmedia_category'));
		$new_query = $gmCore->_get( "gm{$id}" );
		if ( $new_query ) {
			//$query = array_merge($query, $new_query);
			$query = $new_query;
		}
		if ( empty( $query['orderby'] ) && empty( $query['order'] ) ) {
			if ( isset( $query['gmedia__in'] ) ) {
				$query_order = array(
					'orderby' => 'gmedia__in',
					'order'   => 'ASC',
				);
				$query       = array_merge( $query_order, $query );
			}
			if ( isset( $query['tag__in'] ) && ( ! isset( $query['category__in'] ) && ! isset( $query['album__in'] ) ) ) {
				$term_query_order = array(
					'orderby' => $gmGallery->options['in_tag_orderby'],
					'order'   => $gmGallery->options['in_tag_order'],
				);
				$query            = array_merge( $term_query_order, $query );
			}
			if ( isset( $query['category__in'] ) && ! isset( $query['album__in'] ) ) {
				$cat_ids = wp_parse_id_list( $query['category__in'] );
				if ( 1 === count( $cat_ids ) ) {
					$cat_meta         = $gmDB->get_metadata( 'gmedia_term', $cat_ids[0] );
					$term_query_order = array(
						'orderby' => ! empty( $cat_meta['_orderby'][0] ) ? $cat_meta['_orderby'][0] : $gmGallery->options['in_category_orderby'],
						'order'   => ! empty( $cat_meta['_order'][0] ) ? $cat_meta['_order'][0] : $gmGallery->options['in_category_order'],
					);
					$query            = array_merge( $term_query_order, $query );
				}
			}
			if ( isset( $query['album__in'] ) ) {
				$alb_ids = wp_parse_id_list( $query['album__in'] );
				if ( 1 === count( $alb_ids ) ) {
					$album_meta       = $gmDB->get_metadata( 'gmedia_term', $alb_ids[0] );
					$term_query_order = array(
						'orderby' => ! empty( $album_meta['_orderby'][0] ) ? $album_meta['_orderby'][0] : $gmGallery->options['in_album_orderby'],
						'order'   => ! empty( $album_meta['_order'][0] ) ? $album_meta['_order'][0] : $gmGallery->options['in_album_order'],
					);
					$query            = array_merge( $term_query_order, $query );
				}
			}
		}

		return $query;
	}

}

global $gmPermalinks;
$gmPermalinks = new gmediaPermalinks();
