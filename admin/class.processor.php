<?php

/**
 * Class GmediaProcessor
 */
class GmediaProcessor {

	private static $me = null;

	public $user_options = array();

	public $page;
	public $gmediablank;
	public $url;
	public $msg;
	public $error;

	public $display_mode;
	public $taxonomy;
	public $taxterm;
	public $edit_term;

	/**
	 * initiate the manage page
	 */
	public function __construct() {
		global $pagenow, $gmCore;
		// GET variables.
		$this->page = $gmCore->_get( 'page' );
		$this->url  = add_query_arg( array( 'page' => $this->page ), admin_url( 'admin.php' ) );
		if ( 'media.php' === $pagenow ) {
			add_filter( 'wp_redirect', array( $this, 'redirect' ), 10, 2 );
		}
		if ( 'edit-comments.php' === $pagenow ) {
			add_filter( 'get_comment_text', array( $this, 'gmedia_comment_text' ), 10, 3 );
		}

		add_action( 'init', array( $this, 'controller' ) );

		if ( ! $this->page || strpos( $this->page, 'GrandMedia' ) === false ) {
			return;
		}

		$this->gmediablank = $gmCore->_get( 'gmediablank' );
		if ( $this->gmediablank ) {
			$this->url = add_query_arg( array( 'gmediablank' => $this->gmediablank ), $this->url );
		}

		switch ( $this->page ) {
			case 'GrandMedia_Albums':
				$this->taxonomy = 'gmedia_album';
				break;
			case 'GrandMedia_Categories':
				$this->taxonomy = 'gmedia_category';
				break;
			case 'GrandMedia_Tags':
				$this->taxonomy = 'gmedia_tag';
				break;
			case 'GrandMedia_Galleries':
				$this->taxonomy = 'gmedia_gallery';
				break;
		}
		if ( $this->taxonomy ) {
			$this->taxterm   = str_replace( 'gmedia_', '', $this->taxonomy );
			$this->edit_term = $gmCore->_get( 'edit_term' );
		}

	}

	/**
	 * @param string $key
	 * @param string $post_key
	 *
	 * @return array
	 */
	public static function selected_items( $key, $post_key = 'selected_items' ) {

		$selected_items = array();
		if ( $key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				$sel_string     = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				$selected_items = array_filter( explode( ',', $sel_string ), 'is_numeric' );
			} elseif ( isset( $_COOKIE[ $key ] ) ) {
				$sel_string     = sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) );
				$selected_items = array_filter( explode( '.', $sel_string ), 'is_numeric' );
			}
		}

		return $selected_items;
	}

	/**
	 * @param bool|string|array $author_id_list
	 *
	 * @return array
	 */
	public static function filter_by_author( $author_id_list = false ) {
		global $user_ID, $gmCore;

		if ( false === $author_id_list ) {
			$author = false;
			if ( ! $gmCore->caps['gmedia_show_others_media'] ) {
				$author = array( $user_ID, 0 );
			}
		} else {
			$author = wp_parse_id_list( $author_id_list );
			if ( ! $gmCore->caps['gmedia_show_others_media'] ) {
				$author = array_intersect( array( $user_ID, 0 ), $author );
			}
		}

		return $author;
	}

	/**
	 * Autoloader
	 */
	public static function autoload() {
		global $gmCore;
		$path_ = GMEDIA_ABSPATH . 'admin/processor/class.processor.';
		$page  = $gmCore->_get( 'page', '' );
		switch ( $page ) {
			case 'GrandMedia':
				/** @var $gmProcessorLibrary */
				include_once $path_ . 'library.php';

				return $gmProcessorLibrary;
			case 'GrandMedia_AddMedia':
				/** @var $gmProcessorAddMedia */
				include_once $path_ . 'addmedia.php';

				return $gmProcessorAddMedia;
			case 'GrandMedia_Albums':
			case 'GrandMedia_Categories':
				/** @var $gmProcessorTerms */
				include_once $path_ . 'terms.php';
				/** @var $gmProcessorLibrary */
				include_once $path_ . 'library.php';

				return $gmProcessorTerms;
			case 'GrandMedia_Tags':
				/** @var $gmProcessorTerms */
				include_once $path_ . 'terms.php';

				return $gmProcessorTerms;
			case 'GrandMedia_Galleries':
				/** @var $gmProcessorGalleries */
				include_once $path_ . 'galleries.php';

				return $gmProcessorGalleries;
			case 'GrandMedia_Modules':
				/** @var $gmProcessorModules */
				include_once $path_ . 'modules.php';

				return $gmProcessorModules;
			case 'GrandMedia_Settings':
				/** @var $gmProcessorSettings */
				include_once $path_ . 'settings.php';

				return $gmProcessorSettings;
			case 'GrandMedia_WordpressLibrary':
				/** @var $gmProcessorWPMedia */
				include_once $path_ . 'wpmedia.php';

				return $gmProcessorWPMedia;
			default:
				if ( null === self::$me ) {
					self::$me = new GmediaProcessor();
				}

				return self::$me;
		}
	}

	/**
	 * Load only on Gmedia admin pages
	 */
	public function controller() {

		$this->user_options = self::user_options();
		$view               = $this->gmediablank ? '_frame' : '';
		$this->display_mode = $this->user_options["display_mode_gmedia{$view}"];

		if ( ! $this->page || strpos( $this->page, 'GrandMedia' ) === false ) {
			return;
		}

		auth_redirect();

		$this->processor();
	}

	/**
	 * @return array|mixed
	 */
	public static function user_options() {
		global $user_ID, $gmGallery;

		$screen_options = get_user_meta( $user_ID, 'gm_screen_options', true );
		if ( ! is_array( $screen_options ) ) {
			$screen_options = array();
		}

		return array_merge( $gmGallery->options['gm_screen_options'], $screen_options );
	}

	/**
	 * Do diff process before lib shell
	 */
	protected function processor() {}

	/**
	 * @param string $cookie_key
	 *
	 * @return array
	 */
	public function clear_selected_items( $cookie_key ) {
		if ( $cookie_key ) {
			setcookie( $cookie_key, '', time() - 3600 );
			unset( $_COOKIE[ $cookie_key ] );
		}

		return array();
	}

	/**
	 * redirect to original referer after update
	 *
	 * @param string $location
	 * @param string $status
	 *
	 * @return mixed
	 */
	public function redirect( $location, $status ) {
		global $pagenow, $gmCore;
		$ref = $gmCore->_post( '_wp_original_http_referer' );
		if ( 'media.php' === $pagenow && $ref ) {
			if ( strpos( $ref, 'GrandMedia' ) !== false ) {
				return $ref;
			} else {
				return $location;
			}
		}

		return $location;
	}

	/**
	 * Add thumb to gmedia comment text in admin
	 *
	 * @param string $comment_content
	 * @param string $comment
	 * @param array  $args
	 *
	 * @return string $comment_content
	 */
	public function gmedia_comment_text( $comment_content, $comment, $args ) {
		global $post;
		if ( ! $post ) {
			return $comment_content;
		}
		//if('gmedia' === substr($post->post_type, 0, 6)) {
		if ( 'gmedia' === $post->post_type ) {
			global $gmDB, $gmCore;
			$gmedia          = $gmDB->get_post_gmedia( $post->ID );
			$thumb           = '<div class="alignright" style="clear:right;"><img class="gmedia-thumb" style="max-height:72px;" src="' . esc_url( $gmCore->gm_get_media_image( $gmedia, 'thumb', false ) ) . '" alt=""/></div>';
			$comment_content = $thumb . $comment_content;
		}

		return $comment_content;
	}

}

global $gmProcessor;
$gmProcessor = GmediaProcessor::autoload();
