<?php

/**
 * GmediaProcessor_WordpressLibrary
 */
class GmediaProcessor_WordpressLibrary extends GmediaProcessor {

    public static $cookie_key = false;
    public $selected_items = array();

    /**
     * GmediaProcessor_Library constructor.
     */
    public function __construct() {
        parent::__construct();

        global $user_ID;

        self::$cookie_key = "gmuser_{$user_ID}_wpmedia";
        $this->selected_items = parent::selected_items(self::$cookie_key);

    }

    protected function processor() {
        global $gmCore;

        if(!$gmCore->caps['gmedia_import']) {
            wp_die(__('You are not allowed to import media in Gmedia Library', 'grand-media'));
        }

    }

}

global $gmProcessor;
$gmProcessor = new GmediaProcessor_WordpressLibrary();
