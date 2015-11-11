<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * gmediaPermalinks class.
 */
class gmediaPermalinks {

	private $endpoint = 'gmedia';

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return \gmediaPermalinks
	 */
	public function __construct() {
		add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'parse_request', array( $this, 'handler' ) );

		add_filter( 'post_thumbnail_html', array( $this, 'gmedia_post_thumbnail' ), 10, 5 );
		add_filter( 'gmedia_shortcode_gallery_data', array( $this, 'gmedia_shortcode_gallery_data' ) );
	}

	/**
	 * @param $rules
	 *
	 * @return array
	 */
	function add_rewrite_rules( $rules ) {
		global $wp_rewrite, $gmGallery;
		$this->endpoint = !empty($gmGallery->options['endpoint'])? $gmGallery->options['endpoint'] : 'gmedia';

		$this->add_endpoint();

		$new_rules = array(
			$this->endpoint . '/(g|s|a|t|k|f|u)/(.+?)/?$' => 'index.php?' . $this->endpoint . '=' . $wp_rewrite->preg_index( 2 ) . '&t=' . $wp_rewrite->preg_index( 1 ),
			'gmedia-app/?$' => 'index.php?gmedia-app=1'
		);

		$new_rules = $new_rules + $rules;

		return $new_rules;
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
		$endpoint = !empty($gmGallery->options['endpoint'])? $gmGallery->options['endpoint'] : 'gmedia';

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
	public function handler($wp) {
		global $gmGallery;
		$endpoint = !empty($gmGallery->options['endpoint'])? $gmGallery->options['endpoint'] : 'gmedia';

		if ( isset($wp->query_vars[$endpoint]) ) {

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

			require_once(GMEDIA_ABSPATH . "/load-template.php");

			exit();

		}

		/* Application only template */
		$is_app = (isset($wp->query_vars['gmedia-app']) && !empty($wp->query_vars['gmedia-app']));
		if ( $is_app ) {

			global $wp_query;
			$wp_query->is_single  = false;
			$wp_query->is_page    = false;
			$wp_query->is_archive = false;
			$wp_query->is_search  = false;
			$wp_query->is_home    = false;

			$template = GMEDIA_ABSPATH . "/access.php";

			load_template( $template, false );
			exit();

		}

	}

	/**
	 * Filter for the post content
	 *
	 * @param string $html
	 * @param int $post_id
	 * @param int $post_thumbnail_id
	 * @param string|array $size Optional. Image size.  Defaults to 'thumbnail'.
	 * @param string|array $attr Optional. Query string or array of attributes.
	 *
	 * @return string html output
	 */
	function gmedia_post_thumbnail( $html, $post_id, $post_thumbnail_id, $size = 'post-thumbnail', $attr = '' ) {

		$gmedia_id = get_post_meta( $post_thumbnail_id, '_gmedia_image_id', true );
		if ( ! empty( $gmedia_id ) ) {
			$html = str_replace( 'wp-post-image', 'wp-post-image gmedia-post-thumbnail-' . $gmedia_id, $html );
		}

		return $html;
	}

	/**
	 * Filter for the shortcode gallery data
	 *
	 * @param array $gallery
	 *
	 * @return array $gallery
	 */
	function gmedia_shortcode_gallery_data( $gallery ) {
		global $gmCore;

		if(($new_query = $gmCore->_get("gm{$gallery['term_id']}"))){
			unset($gallery['custom_query']);
			$gmCore->replace_array_keys($new_query, array('gmedia_album' => 'album__in', 'gmedia_tag' => 'tag__in', 'gmedia_category' => 'category__in'));
			$gallery['_query'] = $new_query;
		}

		return $gallery;
	}

}

global $gmPermalinks;
$gmPermalinks = new gmediaPermalinks();