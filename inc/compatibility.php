<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/** Skip Jetpack Photon module for Gmedia images
 *
 * @param bool   $skip
 * @param string $src
 *
 * @return bool
 */
function jetpack_photon_skip_gmedia( $skip, $src ) {
	if ( strpos( $src, GMEDIA_UPLOAD_FOLDER . '/image' ) !== false ) {
		return true;
	}

	return $skip;
}

/**
 * Skip Gmedia images for Jetpack lazy load.
 *
 * @param bool  $skip
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
 *
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
 *
 * @param bool $pass
 *
 * @return bool
 */
function wpss_gmedia_check_bypass( $pass ) {
	$is_app = ( isset( $_GET['gmedia-app'] ) && ! empty( $_GET['gmedia-app'] ) );
	if ( $is_app ) {
		return true;
	}

	return $pass;
}

add_filter( 'wpss_misc_form_spam_check_bypass', 'wpss_gmedia_check_bypass' );

/** Allow Edit Comments for Gmedia Users
 *
 * @param array   $allcaps
 * @param array   $caps
 * @param array   $args
 * @param WP_User $user
 *
 * @return array
 */
function gmedia_user_has_cap( $allcaps, $caps, $args, $user ) {
	if ( is_array( $caps ) && count( $caps ) ) {
		global $post_id, $gmDB;
		foreach ( $caps as $cap ) {
			$gmedia = false;
			if ( 'read_private_gmedia_posts' === $cap ) {
				if ( $user ) {
					$allcaps[ $cap ] = 1;
				}
			} elseif ( ! empty( $allcaps['gmedia_edit_media'] ) && in_array( $cap, array( 'edit_comment', 'moderate_comments', 'edit_post', 'edit_posts', 'edit_published_posts' ), true ) ) {
				if ( 'moderate_comments' === $cap && ! empty( $allcaps['moderate_comments'] ) ) {
					return $allcaps;
				}
				if ( 'edit_published_posts' === $cap && ! empty( $allcaps['edit_published_posts'] ) ) {
					return $allcaps;
				}

				$pid = isset( $_REQUEST['p'] ) ? absint( $_REQUEST['p'] ) : ( $post_id ? $post_id : false );
				if ( ! $pid && isset( $_REQUEST['id'] ) ) {
					$comment = get_comment( absint( $_REQUEST['id'] ) );
					if ( $comment ) {
						$pid = $comment->comment_post_ID;
					}
				}
				if ( $pid ) {
					$gmedia = $gmDB->get_post_gmedia( $pid );
				}
				if ( $gmedia && $gmedia->author === $user->ID ) {
					$allcaps[ $cap ] = 1;
				}
			}
		}
	}

	return $allcaps;
}

add_filter( 'user_has_cap', 'gmedia_user_has_cap', 10, 4 );

/**
 * Add custom tags to kses
 *
 * @param array        $tags
 * @param string|array $context
 *
 * @return array
 */
function gmedia_wpkses_post_tags( $tags, $context ) {

	if ( 'explicit' !== $context ) {
		return $tags;
	}

	if ( ! isset( $tags['template']['data-gmedia'] ) ) {
		return $tags;
	}

	$tags['a']['onclick']      = array();
	$tags['button']['onclick'] = array();

	// iframe.
	$tags['iframe'] = array(
		'src'             => array(),
		'height'          => array(),
		'width'           => array(),
		'frameborder'     => array(),
		'allowfullscreen' => array(),
		'style'           => array(),
		'data-*'          => true,
	);
	// form fields - input.
	$tags['form'] = array(
		'class'   => array(),
		'id'      => array(),
		'name'    => array(),
		'action'  => array(),
		'method'  => array(),
		'style'   => array(),
		'data-*'  => true,
		'onclick' => array(),
	);
	// form fields - textarea.
	$tags['textarea'] = array(
		'class'       => array(),
		'id'          => array(),
		'name'        => array(),
		'disabled'    => array(),
		'maxlength'   => array(),
		'placeholder' => array(),
		'readonly'    => array(),
		'required'    => array(),
		'cols'        => array(),
		'rows'        => array(),
		'style'       => array(),
		'data-*'      => true,
		'onclick'     => array(),
	);
	// form fields - input.
	$tags['input'] = array(
		'class'       => array(),
		'id'          => array(),
		'name'        => array(),
		'value'       => array(),
		'type'        => array(),
		'checked'     => array(),
		'disabled'    => array(),
		'maxlength'   => array(),
		'max'         => array(),
		'min'         => array(),
		'step'        => array(),
		'placeholder' => array(),
		'readonly'    => array(),
		'required'    => array(),
		'style'       => array(),
		'data-*'      => true,
		'onclick'     => array(),
	);
	// select.
	$tags['select'] = array(
		'class'    => array(),
		'id'       => array(),
		'name'     => array(),
		'value'    => array(),
		'type'     => array(),
		'selected' => array(),
		'disabled' => array(),
		'readonly' => array(),
		'required' => array(),
		'multiple' => array(),
		'size'     => array(),
		'style'    => array(),
		'data-*'   => true,
	);
	// select options.
	$tags['option'] = array(
		'value'    => array(),
		'selected' => array(),
		'data-*'   => true,
	);
	// style.
	$tags['style'] = array(
		'id' => array(),
	);
	$tags['link']  = array(
		'rel'   => array(),
		'id'    => array(),
		'href'  => array(),
		'media' => array(),
	);
	// script.
	$tags['script']   = array(
		'type' => array(),
		'id'   => array(),
	);
	$tags['noscript'] = array(
		'class'  => array(),
		'app-id' => array(),
	);

	return $tags;
}

add_filter( 'wp_kses_allowed_html', 'gmedia_wpkses_post_tags', 10, 2 );
add_filter(
	'safe_style_css',
	function ( $styles ) {
		$styles[] = 'display';

		return $styles;
	}
);
