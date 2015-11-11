<?php

/** Skip Jetpack Photon module for Gmedia images
 * @param $skip
 * @param $src
 *
 * @return bool
 */
function jetpack_photon_skip_gmedia( $skip, $src ) {
	if ( strpos( $src, GMEDIA_UPLOAD_FOLDER.'/image' ) !== false ) {
		return true;
	}
	return $skip;
}
add_filter( 'jetpack_photon_skip_image', 'jetpack_photon_skip_gmedia', 10, 3 );