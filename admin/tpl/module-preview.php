<?php
/**
 * Module Preset Preview
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! is_user_logged_in() || ! current_user_can( 'gmedia_module_manage' ) ) {
	die( '-1' );
}

global $wp_styles, $wp_scripts, $gmCore;
$query  = $gmCore->_req( 'query', 'limit=20' );
$module = $gmCore->_req( 'module' );
$preset = $gmCore->_req( 'preset' );
$atts   = compact( 'query', 'module', 'preset' );

do_action( 'wp_enqueue_scripts' );
$wp_styles->queue  = array();
$wp_scripts->queue = array();

do_action( 'gmedia_head' );

// Shortcode content already escaped and doing it twice broke the code.
// phpcs:ignore
echo gmedia_shortcode( $atts );

do_action( 'gmedia_enqueue_scripts' );
do_action( 'gmedia_footer' );
