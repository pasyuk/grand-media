<?php

/**
 * GmediaProcessor_AddMedia
 */
class GmediaProcessor_AddMedia extends GmediaProcessor {

    protected function processor() {
        global $gmCore;

        if(!$gmCore->caps['gmedia_upload']) {
            wp_die(__('You are not allowed to be here', 'grand-media'));
        }

    }

}

global $gmProcessor;
$gmProcessor = new GmediaProcessor_AddMedia();
