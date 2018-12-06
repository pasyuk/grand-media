<?php

/**
 * GmediaProcessor_WordpressLibrary
 */
class GmediaProcessor_WordpressLibrary extends GmediaProcessor {

    private static $me = null;
    public static $cookie_key = false;
    public $selected_items = array();

    /**
     * GmediaProcessor_Library constructor.
     */
    public function __construct() {
        parent::__construct();

        self::$cookie_key = "gmedia_library:wpmedia";
        $this->selected_items = parent::selected_items(self::$cookie_key);

    }

    protected function processor() {
        global $gmCore;

        if(!$gmCore->caps['gmedia_import']) {
            wp_die(__('You are not allowed to import media in Gmedia Library', 'grand-media'));
        }

    }

    public static function getMe() {
        if ( self::$me == null ) {
            self::$me = new GmediaProcessor_WordpressLibrary();
        }

        return self::$me;
    }
}

global $gmProcessorWPMedia;
$gmProcessorWPMedia = GmediaProcessor_WordpressLibrary::getMe();
