<?php

/**
 * GmediaProcessor_WordpressLibrary
 */
class GmediaProcessor_WordpressLibrary extends GmediaProcessor {

	public static $cookie_key = false;

	public $selected_items = array();

	private static $me = null;

	/**
	 * GmediaProcessor_Library constructor.
	 */
	public function __construct() {
		parent::__construct();

		self::$cookie_key     = 'gmedia_library:wpmedia';
		$this->selected_items = parent::selected_items( self::$cookie_key );

	}

	public static function getMe() {
		if ( null === self::$me ) {
			self::$me = new GmediaProcessor_WordpressLibrary();
		}

		return self::$me;
	}

	protected function processor() {
		global $gmCore;

		if ( ! $gmCore->caps['gmedia_import'] ) {
			wp_die( esc_html__( 'You are not allowed to import media in Gmedia Library', 'grand-media' ) );
		}

	}
}

global $gmProcessorWPMedia;
$gmProcessorWPMedia = GmediaProcessor_WordpressLibrary::getMe();
