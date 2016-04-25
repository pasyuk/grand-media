<?php
/**
 * FrontEnd Filters
 */

add_action('the_post', 'gmedia_the_post');
function gmedia_the_post($post) {
    if('gmedia' == substr($post->post_type, 0, 6)) {
        add_filter('get_the_excerpt', 'gmedia_post_type__the_excerpt', 15);
        add_filter('the_content', 'gmedia_post_type__the_content', 20);
    }
}
function gmedia_post_type__the_excerpt($content) {
    remove_filter('get_the_excerpt', 'gmedia_post_type__the_excerpt', 15);
    remove_filter('the_content', 'gmedia_post_type__the_content', 20);
    $content = wp_trim_excerpt();
    return gmedia_post_type__the_content($content);
}
function gmedia_post_type__the_content($content) {
    global $post, $gmDB, $gmCore;

    remove_filter('the_content', 'gmedia_post_type__the_content', 20);
    if($post->post_type == 'gmedia') {
        $gmedia_id = get_post_meta($post->ID, '_gmedia_ID', true);
        $gmedia    = $gmDB->get_gmedia($gmedia_id);
        if($gmedia) {
            include_once(GMEDIA_ABSPATH . 'admin/pages/library/functions.php');
            gmedia_item_more_data($gmedia);
            if('image' == $gmedia->type) {
                $image_url = $gmCore->gm_get_media_image($gmedia, 'web');
                $content_img = '<a class="gmedia-item-link" href="' . $gmedia->url . '"><img class="gmedia-item" style="max-width:100%;" src="' . $image_url . '" alt="' . esc_attr($gmedia->title) . '"/></a>';
                $content     = $content_img . "\n" . $content;
            } else{
                $ext1 = wp_get_audio_extensions();
                $ext2 = wp_get_video_extensions();
                $ext = array_merge($ext1, $ext2);
                if(in_array($gmedia->ext, $ext)) {
                    $embed = apply_filters('the_content', "[embed]$gmedia->url[/embed]");
                    $content =  $embed . "\n" . $content;
                } else {
                    $cover_url = $gmCore->gm_get_media_image($gmedia, 'web');
                    $content_img = '<a class="gmedia-item-link" href="' . $gmedia->url . '" download="true"><img class="gmedia-item" style="max-width:100%;" src="' . $cover_url . '" alt="' . esc_attr($gmedia->title) . '"/></a>';
                    $content     = $content_img . "\n" . $content;
                }
            }
        }
    } else {
        if('get_the_excerpt' != current_filter()) {
            $term_id = get_post_meta($post->ID, '_gmedia_term_ID', true);
            if(in_array($post->post_type, array('gmedia_album'))) {
                $content .= do_shortcode("[gm id={$term_id} module=phantom]");
            } elseif($post->post_type == 'gmedia_gallery') {
                $content .= do_shortcode("[gmedia id={$term_id}]");
            }
        }
    }

    return $content;
}

/*
add_filter('the_title', 'gmedia_post_type__the_title', 10, 2);
function gmedia_post_type__the_title($title, $id = null) {
    if($id && !trim($title)) {
        $post = get_post($id);
        if(isset($post->post_type) && (strpos($post->post_type, 'gmedia') !== false)) {
            global $gmDB;
            if($post->post_type == 'gmedia') {
                $gmedia_id = get_post_meta($post->ID, '_gmedia_ID', true);
                $gmedia    = $gmDB->get_gmedia($gmedia_id);
                if($gmedia) {
                    $title = trim($gmedia->title)? $gmedia->title : $gmedia->gmuid;
                }
            } else {
                $term_id = get_post_meta($post->ID, '_gmedia_term_ID', true);
                $gmedia_term = $gmDB->get_term($term_id, $post->post_type);
                if($gmedia_term) {
                    $title = $gmedia_term->name;
                }
            }
        }
    }

    return $title;
}
*/
