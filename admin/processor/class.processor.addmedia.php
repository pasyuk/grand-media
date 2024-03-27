<?php

/**
 * GmediaProcessor_AddMedia
 */
class GmediaProcessor_AddMedia extends GmediaProcessor {

	private static $me = null;

	public $import = false;
	public $url;

	/**
	 * GmediaProcessor_Library constructor.
	 */
	public function __construct() {
		parent::__construct();

		global $gmCore;

		$this->import = $gmCore->_get( 'import', false, true );
		$this->url    = add_query_arg( array( 'import' => $this->import ), $this->url );

	}

	public static function getMe() {
		if ( null === self::$me ) {
			self::$me = new GmediaProcessor_AddMedia();
		}

		return self::$me;
	}

	protected function processor() {
		global $gmCore;

		if ( ! $gmCore->caps['gmedia_upload'] ) {
			wp_die( esc_html__( 'You are not allowed to be here', 'grand-media' ) );
		}

	}
}

global $gmProcessorAddMedia;
$gmProcessorAddMedia = GmediaProcessor_AddMedia::getMe();
