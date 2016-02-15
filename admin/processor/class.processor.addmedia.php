<?php

/**
 * GmediaProcessor_AddMedia
 */
class GmediaProcessor_AddMedia extends GmediaProcessor {

    public $url;
    public $import = false;

    /**
     * GmediaProcessor_Library constructor.
     */
    public function __construct() {
        parent::__construct();

        global $gmCore;

        $this->import = $gmCore->_get('import', false, true);
        $this->url    = add_query_arg(array('page' => $this->page, 'import' => $this->import), admin_url('admin.php'));

    }

    protected function processor() {
        global $gmCore;

        if(!$gmCore->caps['gmedia_upload']) {
            wp_die(__('You are not allowed to be here', 'grand-media'));
        }

    }

}

global $gmProcessor;
$gmProcessor = new GmediaProcessor_AddMedia();
