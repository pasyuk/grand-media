<?php
/** @var $gmDB
 * @var  $gmCore
 * @var  $gmGallery
 * @var  $id
 * @var  $query
 * @var  $module
 * @var  $settings
 * @var  $term
 * @var  $is_bot
 * @var  $shortcode_raw
 **/

global $wp;
$settings      = array_merge( $settings, array(
        'ID'        => $id,
        'url'       => remove_query_arg( 'gm' . $id . '_slide', add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) ) ),
        'moduleUrl' => $module['url']
) );
$iSlide        = $settings['initial_slide'] = (int) $gmCore->_get( 'gm' . $id . '_slide', 0 );
$base_url_host = parse_url( $gmCore->upload['url'], PHP_URL_HOST );
$term_url      = remove_query_arg( 'gm' . $id, $settings['url'] );

$allsettings = array_merge( $module['options'], $settings );
$gmedias     = $gmDB->get_gmedias( $query );

$content = array(
        'data'  => array(),
        'terms' => array()
);

foreach ( $gmedias as $item ) {
    $type = explode( '/', $item->mime_type );

    $_meta    = $gmDB->get_metadata( 'gmedia', $item->ID );
    $metadata = $_meta['_metadata'][0];
    $meta     = array(
            'width'  => empty( $metadata['web']['width'] ) ? ( empty( $metadata['width'] ) ? null : $metadata['width'] ) : $metadata['web']['width'],
            'height' => empty( $metadata['web']['height'] ) ? ( empty( $metadata['height'] ) ? null : $metadata['height'] ) : $metadata['web']['height'],
            'views'  => empty( $_meta['views'][0] ) ? 0 : (int) $_meta['views'][0],
            'likes'  => empty( $_meta['likes'][0] ) ? 0 : (int) $_meta['likes'][0]
    );

    $author['name']       = get_the_author_meta( 'display_name', $item->author );
    $author['posts_link'] = get_author_posts_url( $item->author );
    if ( ! empty( $allsettings['show_author_avatar'] ) ) {
        $avatar_img = get_avatar( $item->author, 60 );
        if ( preg_match( "/src=['\"](.*?)['\"]/i", $avatar_img, $matches ) ) {
            $author['avatar'] = $matches[1];
        }
    }
    $alttext     = !empty($_meta['_image_alt'][0])? $_meta['_image_alt'][0] : $item->title;

    $content['data'][ $item->ID ] = array(
            'id'     => $item->ID,
            'type'   => $type[0],
            'file'   => $item->gmuid,
            'meta'   => $meta,
            'title'  => $item->title,
            'alt'    => $alttext,
            'author' => $author
    );

    if ( ! empty( $allsettings['show_description'] ) ) {
        $content['data'][ $item->ID ]['description'] = str_replace( array( "\r\n", "\r", "\n" ), '', wpautop( $item->description ) );
    }

    if ( ! empty( $item->post_id ) ) {
        $content['data'][ $item->ID ]['post_link'] = get_permalink( $item->post_id );
    }
    if ( ! empty( $allsettings['show_share_button'] ) ) {
        if ( empty( $item->post_id ) ) {
            $content['data'][ $item->ID ]['post_link'] = $gmCore->gmcloudlink( $item->ID, 'single' );
        }
    }
    if ( ! empty( $allsettings['show_comments'] ) ) {
        if ( ! empty( $item->post_id ) ) {
            $cc                                 = wp_count_comments( $item->post_id );
            $content['data'][ $item->ID ]['cc'] = $cc->approved;
        } else {
            $content['data'][ $item->ID ]['cc'] = '';
        }
    }

    if ( ! empty( $allsettings['show_link_button'] ) ) {
        $content['data'][ $item->ID ]['link'] = $item->link;
        $link_target                          = '';
        if ( $item->link ) {
            $url_host = parse_url( $item->link, PHP_URL_HOST );
            if ( $url_host == $base_url_host || empty( $url_host ) ) {
                $link_target = '_self';
            } else {
                $link_target = '_blank';
            }
        }
        if ( isset( $_meta['link_target'][0] ) ) {
            $link_target = $_meta['link_target'][0];
        }
        $content['data'][ $item->ID ]['link_target'] = $link_target;
    }

    if ( ! empty( $allsettings['show_download_button'] ) ) {
        $download = '';
        if ( ! empty( $_meta['download'][0] ) ) {
            $download = $_meta['download'][0];
        } else {
            if ( 'image' == $type[0] ) {
                $download = $gmCore->gm_get_media_image( $item->ID, 'original' );
            } else {
                $download = "{$gmCore->upload['url']}/{$gmGallery->options['folder'][$type[0]]}/{$item->gmuid}";
            }
        }
        $content['data'][ $item->ID ]['download'] = $download;
    }

}


if ( empty( $content['data'] ) ) {
    echo GMEDIA_GALLERY_EMPTY;

    return;
}

$slides        = array();
$slides_thumbs = array();
$i             = 0;
foreach ( $content['data'] as $item_id => $item ) {
    $i ++;
    $web   = $gmCore->gm_get_media_image( $item['id'], 'web' );
    $thumb = $gmCore->gm_get_media_image( $item['id'], 'thumb' );
    if ( ! empty( $item['meta']['width'] ) && ! empty( $item['meta']['height'] ) ) {
        $ratio = $item['meta']['width'] / $item['meta']['height'];
    } else {
        $ratio = 1.5;
    }
    $content['data'][ $item_id ]['ratio'] = $ratio;
    if ( 1 <= $ratio ) {
        $orientation = 'gmpm_photo_landscape';
    } else {
        $orientation = 'gmpm_photo_portrait';
    }
    $img_src   = '';
    $thumb_src = '';
    if ( $is_bot || 1 === $i ) {
        $img_src   = 'src="' . $web . '"';
        $thumb_src = 'src="' . $thumb . '"';
    }
    $img_class     = '';
    $img_preloader = '';
    if ( ! $is_bot ) {
        $img_class .= ' swiper-lazy';
        $img_preloader = '<div class="swiper-lazy-preloader swiper-lazy-preloader-black"></div>';
    }
    $slides[]        = '
		<div class="swiper-slide" data-hash="gmedia' . $item['id'] . '" data-photo-id="' . $item['id'] . '"><span class="gmpm_va"></span>' . '<img ' . $img_src . ' data-src="' . $web . '" alt="' . esc_attr( $item['alt'] ) . '" data-protect="' . $item['author']['name'] . '" class="gmpm_the_photo' . $img_class . '">' . $img_preloader . '</div>';
    $slides_thumbs[] = '
		<div class="swiper-slide gmpm_photo" data-photo-id="' . $item['id'] . '">' . '<img ' . $thumb_src . ' data-src="' . $thumb . '" alt="' . esc_attr( $item['alt'] ) . '" class="gmpm_photo swiper-lazy ' . $orientation . '">' . '<span class="swiper-lazy-preloader swiper-lazy-preloader-black"></span>' . '</div>';
}
$content['data'] = array_values( $content['data'] );

$photo_show_class = '';
if ( ! empty( $allsettings['gallery_maximized'] ) ) {
    $photo_show_class .= ' gmpm_maximized';
}
if ( ! empty( $allsettings['gallery_focus'] ) ) {
    $photo_show_class .= ' gmpm_focus';
}
if ( ! empty( $allsettings['gallery_focus_maximized'] ) ) {
    $photo_show_class .= ' gmpm_focus_maximized';
}
if ( empty( $allsettings['keyboard_help'] ) ) {
    $photo_show_class .= ' gmpm_diskeys';
}
if ( ! $is_bot ) {
    $photo_show_class .= ' gmpm_preload';
}
?>

    <div class="gmpm_photo_show gmpm_w960 gmpm_w640 gmpm_w480<?php echo $photo_show_class; ?>">

        <div class="gmpm_photo_wrap has_prev_photo has_next_photo">
            <div class="swiper-container swiper-big-images">
                <div class="gmpm_photo_arrow_next gmpm_photo_arrow gmpm_next">
                    <div title="Next" class="gmpm_arrow"></div>
                </div>
                <div class="gmpm_photo_arrow_previous gmpm_photo_arrow gmpm_prev">
                    <div title="Previous" class="gmpm_arrow"></div>
                </div>
                <div class="swiper-wrapper">
                    <?php
                    echo implode( '', $slides );
                    ?>
                </div>
            </div>
        </div>

        <div class="gmpm_photo_header">
            <div class="gmpm_wrapper gmpm_clearfix">
                <div class="gmpm_focus_actions">
                    <?php if ( ! empty( $allsettings['show_share_button'] ) ) { ?>
                        <ul class="gmpm_focus_share">
                            <li style="list-style:none;" class="gmpm_share_wrapper">
                                <a class="gmpm_button gmpm_share"><?php _e( 'Share', 'grand-media' ); ?></a>
                                <ul class="gmpm_sharelizers gmpm_clearfix">
                                    <li style="list-style:none;"><a class="gmpm_button gmpm_facebook gmpm_sharelizer"><?php _e( 'Facebook', 'grand-media' ); ?></a></li>
                                    <li style="list-style:none;"><a class="gmpm_button gmpm_twitter gmpm_sharelizer"><?php _e( 'Twitter', 'grand-media' ); ?></a></li>
                                    <li style="list-style:none;"><a class="gmpm_button gmpm_pinterest gmpm_sharelizer"><?php _e( 'Pinterest', 'grand-media' ); ?></a></li>
                                    <li style="list-style:none;"><a class="gmpm_button gmpm_google gmpm_sharelizer"><?php _e( 'Google+', 'grand-media' ); ?></a></li>
                                    <li style="list-style:none;"><a class="gmpm_button gmpm_stumbleupon gmpm_sharelizer"><?php _e( 'StumbleUpon', 'grand-media' ); ?></a></li>
                                </ul>
                            </li>
                        </ul>
                    <?php } ?>
                    <?php if ( ! empty( $allsettings['show_like_button'] ) ) { ?>
                        <ul class="gmpm_focus_like_fave gmpm_clearfix">
                            <li style="list-style:none;"><a class="gmpm_button gmpm_like"><?php _e( 'Like', 'grand-media' ); ?></a></li>
                        </ul>
                    <?php } ?>
                    <ul class="gmpm_focus_arrows gmpm_clearfix">
                        <li style="list-style:none;"><a class="gmpm_button gmpm_photo_arrow_previous gmpm_prev"><?php _e( 'Previous', 'grand-media' ); ?></a></li>
                        <li style="list-style:none;"><a class="gmpm_button gmpm_photo_arrow_next gmpm_next"><?php _e( 'Next', 'grand-media' ); ?></a></li>
                    </ul>
                </div>
                <div class="gmpm_name_wrap gmpm_clearfix<?php if ( empty( $allsettings['show_author_avatar'] ) ) {
                    echo ' gmpm_no_avatar';
                } ?>">
                    <?php if ( ! empty( $allsettings['show_author_avatar'] ) ) { ?>
                        <div class="gmpm_user_avatar">
                            <a class="gmpm_user_avatar_link" href="<?php echo urldecode( $content['data'][ $iSlide ]['author']['posts_link'] ); ?>"><img src="<?php echo $content['data'][ $iSlide ]['author']['avatar']; ?>" alt=""/></a>
                        </div>
                    <?php } ?>
                    <div class="gmpm_title_author">
                        <div class="gmpm_title"><?php echo $content['data'][ $iSlide ]['title']; ?></div>
	                    <?php if ( ! empty( $allsettings['show_author_name'] ) ) { ?>
                        <div class="gmpm_author_name">
                            <a class="gmpm_author_link" href="<?php echo urldecode( $content['data'][ $iSlide ]['author']['posts_link'] ); ?>"><?php echo $content['data'][ $iSlide ]['author']['name']; ?></a>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="gmpm_actions gmpm_clearfix">
                    <div class="gmpm_carousel gmpm_has_previous gmpm_has_next">
                        <div class="gmpm_previous_button"></div>
                        <div class="gmpm_photo_carousel">
                            <div class="swiper-container swiper-small-images">
                                <div class="swiper-wrapper">
                                    <?php echo implode( '', $slides_thumbs ); ?>
                                </div>
                            </div>
                        </div>
                        <div class="gmpm_next_button"></div>
                    </div>
                    <?php
                    $show_share_button = ! empty( $allsettings['show_share_button'] );
                    $show_like_button  = ! empty( $allsettings['show_like_button'] );
                    if ( $show_share_button || $show_like_button ) { ?>
                        <div class="gmpm_big_button_wrap<?php echo ( ! $show_share_button || ! $show_like_button ) ? ' gmpm_one_button' : ''; ?>">
                            <?php if ( $show_share_button ) { ?>
                                <div class="gmpm_share_wrapper">
                                    <a class="gmpm_button gmpm_share"><?php _e( 'Share', 'grand-media' ); ?></a>

                                    <div class="gmpm_sharelizers_wrap">
                                        <ul class="gmpm_sharelizers">
                                            <li style="list-style:none;"><a class="gmpm_button gmpm_facebook gmpm_sharelizer"><?php _e( 'Facebook', 'grand-media' ); ?></a></li>
                                            <li style="list-style:none;"><a class="gmpm_button gmpm_twitter gmpm_sharelizer"><?php _e( 'Twitter', 'grand-media' ); ?></a></li>
                                            <li style="list-style:none;"><a class="gmpm_button gmpm_pinterest gmpm_sharelizer"><?php _e( 'Pinterest', 'grand-media' ); ?></a></li>
                                            <li style="list-style:none;"><a class="gmpm_button gmpm_google gmpm_sharelizer"><?php _e( 'Google+', 'grand-media' ); ?></a></li>
                                            <li style="list-style:none;"><a class="gmpm_button gmpm_stumbleupon gmpm_sharelizer"><?php _e( 'StumbleUpon', 'grand-media' ); ?></a></li>
                                        </ul>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ( $show_like_button ) { ?>
                                <a class="gmpm_button gmpm_like"><?php _e( 'Like', 'grand-media' ); ?></a>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <?php if ( ! empty( $allsettings['show_comments'] ) ) {
                        if ( '' === $content['data'][ $iSlide ]['cc'] ) {
                            $link_class = ' gmpm_inactive';
                            $link_href  = '';
                        } else {
                            $link_class = '';
                            $link_href  = "href='{$content['data'][$iSlide]['post_link']}#comments' target='_blank'";
                        }
                        ?>
                        <div class="gmpm_big_button_wrap">
                            <a class="gmpm_big_button gmpm_comments_button<?php echo $link_class; ?>" <?php echo $link_href; ?>>
                                <span class="gmpm_count_icon">
                                    <span class="gmpm_comments_count"><?php echo $content['data'][ $iSlide ]['cc'] ?></span>
                                    <span class="gmpm_comments_icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
                                            <symbol id="icon-bubbles2" viewBox="0 0 1152 1024">
                                                <path class="path1" d="M480 0v0c265.096 0 480 173.914 480 388.448s-214.904 388.448-480 388.448c-25.458 0-50.446-1.62-74.834-4.71-103.106 102.694-222.172 121.108-341.166 123.814v-25.134c64.252-31.354 116-88.466 116-153.734 0-9.106-0.712-18.048-2.030-26.794-108.558-71.214-177.97-179.988-177.97-301.89 0-214.534 214.904-388.448 480-388.448zM996 870.686c0 55.942 36.314 104.898 92 131.772v21.542c-103.126-2.318-197.786-18.102-287.142-106.126-21.14 2.65-42.794 4.040-64.858 4.040-95.47 0-183.408-25.758-253.614-69.040 144.674-0.506 281.26-46.854 384.834-130.672 52.208-42.252 93.394-91.826 122.414-147.348 30.766-58.866 46.366-121.582 46.366-186.406 0-10.448-0.45-20.836-1.258-31.168 72.57 59.934 117.258 141.622 117.258 231.676 0 104.488-60.158 197.722-154.24 258.764-1.142 7.496-1.76 15.16-1.76 22.966z"></path>
                                            </symbol>
                                        </svg>
                                        <svg class="gmMosaic-svgicon">
                                            <use xlink:href="#icon-bubbles2"/>
                                        </svg>
                                    </span>
                                </span>
                                <span class="gmpm_label"><?php echo $allsettings['comments_button_text']; ?></span>
                            </a>
                        </div>
                    <?php } ?>
                    <?php if ( ! empty( $allsettings['show_download_button'] ) ) { ?>
                        <div class="gmpm_big_button_wrap">
                            <a class="gmpm_big_button gmpm_download_button" href="<?php echo $content['data'][ $iSlide ]['download']; ?>" download="<?php esc_attr_e( $content['data'][ $iSlide ]['file'] ); ?>">
                                <span class="gmpm_icon"></span>
                                <span class="gmpm_label"><?php echo $allsettings['download_button_text']; ?></span>
                            </a>
                        </div>
                    <?php } ?>
                    <?php if ( ! empty( $allsettings['show_link_button'] ) ) {
                        if ( empty( $content['data'][ $iSlide ]['link'] ) ) {
                            $link_class = ' gmpm_inactive';
                            $link_href  = '';
                        } else {
                            $link_class = '';
                            $link_href  = "href='{$content['data'][$iSlide]['link']}' target='{$content['data'][$iSlide]['link_target']}'";
                        }
                        ?>
                        <div class="gmpm_big_button_wrap">
                            <a class="gmpm_big_button gmpm_link_button<?php echo $link_class; ?>" <?php echo $link_href; ?>>
                                <span class="gmpm_icon"></span>
                                <span class="gmpm_label"><?php echo $allsettings['link_button_text']; ?></span>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="gmpm_focus_close_full">
            <span><a class="gmpm_button gmpm_close"><?php _e( 'Close', 'grand-media' ); ?></a></span>
            <span><a class="gmpm_button gmpm_full"><?php _e( 'Full', 'grand-media' ); ?></a></span>
        </div>
        <div class="gmpm_photo_details">
            <div class="gmpm_description_wrap<?php echo empty( $content['data'][ $iSlide ]['description'] ) ? ' empty-item-description' : ''; ?>">
                <?php if ( ! empty( $allsettings['show_description'] ) ) { ?>
                    <?php if ( ! empty( $allsettings['description_title'] ) ) { ?>
                        <div class="details_title"><?php echo $allsettings['description_title']; ?></div>
                    <?php } ?>
                    <div class="gmpm_description_text_wrap">
                        <div class="gmpm_slide_description"><?php echo $content['data'][ $iSlide ]['description']; ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="gmpm_focus_footer">
            <div class="gmpm_focus_keyboard">
                <div class="gmpm_focus_keyboard_title"><?php _e( 'Keyboard Shortcuts', 'grand-media' ); ?> <a class="gmpm_focus_keyboard_dismiss"><?php _e( 'Dismiss', 'grand-media' ); ?></a></div>
                <ul>
                    <li style="list-style:none;"><a data-key="p" class="gmpm_key">S</a><span class="gmpm_label"><?php _e( 'Slideshow', 'grand-media' ); ?></span></li>
                    <li style="list-style:none;"><a data-key="m" class="gmpm_key">M</a><span class="gmpm_label"><?php _e( 'Maximize', 'grand-media' ); ?></span></li>
                    <li style="list-style:none;"><a data-key="left" class="gmpm_key">&nbsp;</a><span class="gmpm_label"><?php _e( 'Previous', 'grand-media' ); ?></span></li>
                    <li style="list-style:none;"><a data-key="right" class="gmpm_key">&nbsp;</a><span class="gmpm_label"><?php _e( 'Next', 'grand-media' ); ?></span></li>
                    <li style="list-style:none;"><a data-key="escape" class="gmpm_key gmpm_esc">esc</a><span class="gmpm_label"><?php _e( 'Close', 'grand-media' ); ?></span></li>
                </ul>
            </div>
        </div>

    </div>

<?php if ( $shortcode_raw ) {
    echo '<pre style="display:none">';
}
?>
    <script type="text/javascript">
        jQuery(function($) {
            var settings = <?php echo json_encode($settings); ?>;
            var content = <?php echo json_encode($content); ?>;
            var container = $('#GmediaGallery_<?php echo $id; ?>');
            container.photomania(settings, content);
            window.GmediaGallery_<?php echo $id; ?> = container.data('photomania');
        });
    </script><?php if ( $shortcode_raw ) {
    echo '</pre>';
} ?>
<?php

$cssid     = "#GmediaGallery_{$id}";
$color_css = '';
if ( isset( $settings['link_color'] ) ) {
    $color_css .= "
{$cssid} .gmpm_photo_details .gmpm_description_wrap a,
{$cssid} .gmpm_big_button_wrap .gmpm_button.gmpm_like.gmpm_liked,
{$cssid} .gmpm_big_button_wrap .gmpm_button.gmpm_like.gmpm_liked:hover {color:#{$settings['link_color']};}
{$cssid} .gmpm_photo_show .gmpm_big_button {background-color:#{$settings['link_color']};}";
}
if ( isset( $settings['link_color_hover'] ) ) {
    $color_css .= "
{$cssid} .swiper-small-images div.gmpm_photo.swiper-slide-active {border-color:#{$settings['link_color_hover']};}
{$cssid} .gmpm_photo_header .gmpm_name_wrap .gmpm_title_author a:hover,
{$cssid} .gmpm_photo_details .gmpm_description_wrap a:hover {color:#{$settings['link_color_hover']};}
{$cssid} .gmpm_photo_show .gmpm_big_button:hover,
{$cssid} .gmpm_focus_actions ul .gmpm_button.like.gmpm_liked {background-color:#{$settings['link_color_hover']};}";
}

$gmpm_css = "
{$cssid} .gmpm_preload {opacity:0;}";
if ( 'fit' == $allsettings['scale_mode'] ) {
    $gmpm_css .= "
{$cssid} .swiper-big-images img.gmpm_the_photo { max-height:100%; max-width:100%; display:inline; width:auto; height:auto; object-fit:unset; vertical-align:middle; border:none; }";
} else {
    $gmpm_css .= "
{$cssid} .swiper-big-images img.gmpm_the_photo { max-height:100%; max-width:100%; display:inline; width:100%; height:100%; object-fit:cover; vertical-align:middle; border:none; }";
}
$gmpm_css .= "
{$cssid} .gmpm_focus .swiper-big-images img.gmpm_the_photo { width:auto; height:auto; object-fit:unset; }
{$cssid} .swiper-small-images img.gmpm_photo { max-width:none; max-height:none; }
{$cssid} .swiper-small-images img.gmpm_photo.gmpm_photo_landscape { width:auto; height:100%; }
{$cssid} .swiper-small-images img.gmpm_photo.gmpm_photo_portrait { width:100%; height:auto; }
{$cssid} .gmpm_gallery_sources_list p { margin:7px 0; padding:0; font-size:inherit; }";
$customCSS = $gmpm_css . $color_css . $customCSS;
