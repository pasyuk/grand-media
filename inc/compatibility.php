<?php

/** Skip Jetpack Photon module for Gmedia images
 *
 * @param $skip
 * @param $src
 *
 * @return bool
 */
if(!defined('ABSPATH')){
    exit;
} // Exit if accessed directly

function jetpack_photon_skip_gmedia($skip, $src){
    if(strpos($src, GMEDIA_UPLOAD_FOLDER . '/image') !== false){
        return true;
    }

    return $skip;
}

/**
 * Skip Gmedia images for Jetpack lazy load.
 * @param bool $skip
 * @param array $attributes
 *
 * @return bool
 */
function jetpack_no_lazy_for_gmedia( $skip, $attributes ) {
	if ( isset( $attributes['src'] ) && strpos( $attributes['src'], 'grand-media' ) ) {
		return true;
	}

	return $skip;
}
add_filter( 'jetpack_lazy_images_skip_image_with_attributes', 'jetpack_no_lazy_for_gmedia', 10, 2 );

/**
 * Skip Gmedia images for a3 Lazy Load.
 * @param string $classes
 *
 * @return string
 */
function a3_no_lazy_for_gmedia( $classes ) {
	return 'noLazy,' . $classes;
}
add_filter( 'a3_lazy_load_skip_images_classes', 'a3_no_lazy_for_gmedia', 10 );

/**
 * WP-SpamShield plugin compatibility
 * @param $pass
 *
 * @return bool
 */
function wpss_gmedia_check_bypass($pass){
    $is_app = (isset($_GET['gmedia-app']) && !empty($_GET['gmedia-app']));
    if($is_app) {
        return true;
    }

    return $pass;
}
add_filter('wpss_misc_form_spam_check_bypass', 'wpss_gmedia_check_bypass');

/** Allow Edit Comments for Gmedia Users
 *
 * @param $allcaps
 * @param $caps
 * @param $args
 * @param $user
 *
 * @return mixed
 */
function gmedia_user_has_cap($allcaps, $caps, $args, $user){
    if(is_array($caps) && count($caps)){
        global $post_id, $gmDB;
        foreach($caps as $cap){
            $gmedia = false;
            if($cap == 'read_private_gmedia_posts'){
                if($user){
                    $allcaps[$cap] = 1;
                }
            } elseif(!empty($allcaps['gmedia_edit_media']) && in_array($cap, array('edit_comment', 'moderate_comments', 'edit_post', 'edit_posts'))){
                    if('moderate_comments' == $cap && !empty($allcaps['moderate_comments'])){
                        return $allcaps;
                    }
                    if('edit_published_posts' == $cap && !empty($allcaps['edit_published_posts'])){
                        return $allcaps;
                    }

                    $pid = isset($_REQUEST['p'])? $_REQUEST['p'] : ($post_id? $post_id : false);
                    if(!$pid && isset($_REQUEST['id'])){
                        if(($comment = get_comment($_REQUEST['id']))){
                            $pid = $comment->comment_post_ID;
                        }
                    }
                    if($pid){
                        $gmedia = $gmDB->get_post_gmedia($pid);
                    }
                    if($gmedia && $gmedia->author == $user->ID){
                        $allcaps[$cap]       = 1;
                    }
                }

        }

    }

    return $allcaps;
}
add_filter('user_has_cap', 'gmedia_user_has_cap', 10, 4);

