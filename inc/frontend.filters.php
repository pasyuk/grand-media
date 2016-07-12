<?php
/**
 * FrontEnd Filters
 */
if(!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

add_action('pre_get_posts', 'gmedia_alter_query');

function gmedia_alter_query($query){
    $gm_tax = '';
    if(!empty($query->query['gmedia_tag'])){
        $gm_tax = 'gmedia_tag';
    } elseif(!empty($query->query['gmedia_category'])){
        $gm_tax = 'gmedia_category';
    }
    if($gm_tax){
        global $wp_query, $gmDB, $gmGallery;
        $term = urldecode($query->query[$gm_tax]);
        if($term && ($term_id = $gmDB->term_exists($term, $gm_tax))){
            $args     = array( 'tag_id'  => $term_id );
            if('gmedia_tag' == $gm_tax){
                $args['orderby'] = $gmGallery->options['in_tag_orderby'];
                $args['order']   = $gmGallery->options['in_tag_order'];
                $wp_query->is_tag = true;
            } elseif('gmedia_category' == $gm_tax){
                $term_meta = $gmDB->get_metadata('gmedia_term', $term_id);
                $args['orderby'] = isset($term_meta['_orderby'][0])? $term_meta['_orderby'][0] : $gmGallery->options['in_category_orderby'];
                $args['order']   = isset($term_meta['_order'][0])? $term_meta['_order'][0] : $gmGallery->options['in_category_order'];
                $wp_query->is_category = true;
            }
            $gmedias  = $gmDB->get_gmedias($args);
            $post_ids = array();
            foreach($gmedias as $item){
                $post_ids[] = $item->post_id;
            }
            if(!empty($post_ids)){
                $query->set($gm_tax, null);
                $query->set('post_type', 'gmedia');
                $query->set('post__in', $post_ids);
                $query->set('orderby', 'post__in');

                $wp_query->is_tax = true;
                $wp_query->is_archive = true;

                //we remove the actions hooked on the '__after_loop' (post navigation)
                remove_all_actions('__after_loop');
            }
        }
    }
}

function set_gmedia_query(){
    global $wp_query, $wp_the_query;
    switch(current_filter()){
        case '__before_loop':
            $tax = get_query_var('taxonomy');
            if(isset($tax) && 'gmedia_tag' == $tax){
                var_dump($tax);
            }
            //replace the current query by a custom query
            //Note : the initial query is stored in another global named $wp_the_query
            $wp_query = new WP_Query(array(
                                             'post_type'   => 'post',
                                             'post_status' => 'publish',
                                             //others parameters...
                                     ));
        break;

        default:
            //back to the initial WP query stored in $wp_the_query
            $wp_query = $wp_the_query;
        break;
    }
}

add_action('wp_head', 'gmogmeta_header');
function gmogmeta_header(){
    if(is_single()){
        global $post;
        if(isset($post->post_type) && $post->post_type == 'gmedia'){
            global $gmDB, $gmCore;
            $gmedia_id = get_post_meta($post->ID, '_gmedia_ID', true);
            $gmedia    = $gmDB->get_gmedia($gmedia_id);
            if($gmedia){
                $image_url = $gmCore->gm_get_media_image($gmedia, 'web');
                ?>
                <!-- Gmedia Open Graph Meta Image -->
                <meta property="og:image" content="<?php echo $image_url; ?>"/>
                <!-- End Gmedia Open Graph Meta Image -->
                <?php
            }
        }
    }
}

add_action('the_post', 'gmedia_the_post');
function gmedia_the_post($post){
    if('gmedia' == substr($post->post_type, 0, 6)){
        add_filter('get_the_excerpt', 'gmedia_post_type__the_excerpt', 15);
        add_filter('the_content', 'gmedia_post_type__the_content', 20);
    }
}

function gmedia_post_type__the_excerpt($content){
    remove_filter('get_the_excerpt', 'gmedia_post_type__the_excerpt', 15);
    remove_filter('the_content', 'gmedia_post_type__the_content', 20);
    $content = wp_trim_excerpt();

    return gmedia_post_type__the_content($content);
}

function gmedia_post_type__the_content($content){
    global $post, $gmDB, $gmCore;

    remove_filter('the_content', 'gmedia_post_type__the_content', 20);
    if($post->post_type == 'gmedia'){
        $gmedia_id = get_post_meta($post->ID, '_gmedia_ID', true);
        $gmedia    = $gmDB->get_gmedia($gmedia_id);
        if($gmedia){
            include_once(GMEDIA_ABSPATH . 'admin/pages/library/functions.php');
            gmedia_item_more_data($gmedia);

            ob_start();

            if($gmedia->link){
                $gmedia_link = $gmedia->link;
                $base_url_host = parse_url($gmCore->upload['url'], PHP_URL_HOST);
                $url_host      = parse_url($gmedia->link, PHP_URL_HOST);
                if($url_host == $base_url_host || empty($url_host)){
                    $link_target = ' target="_self"';
                } else{
                    $link_target = ' target="_blank"';
                }
                if(isset($gmedia->meta['link_target'][0])){
                    $link_target = ' target="' . $gmedia->meta['link_target'][0] . '"';
                }
            } else {
                $gmedia_link = $gmedia->url;
                $link_target = '';
            }

            if('image' == $gmedia->type){
                ?>
                <a class="gmedia-item-link" href="<?php echo $gmedia_link; ?>"<?php echo $link_target; ?>><img class="gmedia-item" style="max-width:100%;" src="<?php echo $gmedia->url; ?>" alt="<?php esc_attr_e($gmedia->title); ?>"/></a>
                <?php
            } else{
                $ext1 = wp_get_audio_extensions();
                $ext2 = wp_get_video_extensions();
                $ext  = array_merge($ext1, $ext2);
                if(in_array($gmedia->ext, $ext)){
                    $embed = apply_filters('the_content', "[embed]$gmedia->url[/embed]");
                    echo $embed;
                } else{
                    $cover_url = $gmCore->gm_get_media_image($gmedia, 'web');
                    ?>
                    <a class="gmedia-item-link" href="<?php echo $gmedia->url; ?>" download="true"><img class="gmedia-item" style="max-width:100%;" src="<?php echo $cover_url; ?>" alt="<?php esc_attr_e($gmedia->title); ?>"/></a>
                    <?php
                }
            }
            if(is_single()){
                /** more info */

                $author_name       = get_the_author_meta('display_name', $gmedia->author);
                $author_posts_link = get_author_posts_url($gmedia->author);
                $avatar_img = get_avatar($gmedia->author, 60);
                if(preg_match("/src=['\"](.*?)['\"]/i", $avatar_img, $matches)) {
                    $author_avatar = $matches[1];
                }
                ?>
                <div class="gmsingle_wrapper gmsingle_clearfix">
                    <div class="gmsingle_photo_header gmsingle_clearfix">
                        <div class="gmsingle_name_wrap gmsingle_clearfix">
                            <?php if(!empty($author_avatar)){ ?>
                            <div class="gmsingle_user_avatar">
                                <a class="gmsingle_user_avatar_link" href="<?php echo urldecode($author_posts_link); ?>"><img src="<?php echo $author_avatar; ?>" alt=""/></a>
                            </div>
                            <?php } ?>
                            <div class="gmsingle_title_author">
                                <div class="gmsingle_title"><?php
                                    if(('image' != $gmedia->type) && $gmedia->link){
                                        echo "<a href='{$gmedia_link}'{$link_target}>{$gmedia->title}&nbsp;&#x1f517;</a>";
                                    } else {
                                        echo $gmedia->title;
                                    }
                                    ?>&nbsp;</div>
    
                                <div class="gmsingle_author_name">
                                    <a class="gmsingle_author_link" href="<?php echo urldecode($author_posts_link); ?>"><?php echo $author_name; ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="gmsingle_photo_info">
                        <div class="gmsingle_description_wrap">
                            <?php
                            echo $content; //the_content();

                            if(!empty($gmedia->album)){
                                $term_name    = $gmedia->album[0]->name;
                                $term_post_id = $gmDB->get_metadata('gmedia_term', $gmedia->album[0]->term_id, '_post_ID', true);
                                if(!empty($term_post_id)){
                                    $term_url = get_permalink($term_post_id);
                                } else{
                                    $term_url = $gmCore->gmcloudlink($gmedia->album[0]->term_id, 'album');
                                }
                                ?>
                                <div class="gmsingle_terms">
                                    <span class="gmsingle_term_label"><?php _e('Album'); ?>:</span>
                                    <span class="gmsingle_album"><span class="gmsingle_term"><a href="<?php echo $term_url; ?>"><?php echo $term_name; ?></a></span></span>
                                </div>
                                <?php
                            }
                            if(!empty($gmedia->categories)){
                                $item_cats = array();
                                foreach($gmedia->categories as $term){
                                    $term->slug = $term->name;
                                    $term_url   = get_term_link($term);
                                    //$term_url = $gmCore->gmcloudlink($term->term_id, 'category');
                                    $item_cats[] = "<span class='gmsingle_term'><a href='{$term_url}'>{$term->name}</a></span>";
                                }
                                ?>
                                <div class="gmsingle_terms">
                                    <span class="gmsingle_term_label"><?php _e('Categories'); ?>:</span>
                                    <span class="gmsingle_categories"><?php echo implode(' ', $item_cats); ?></span>
                                </div>
                                <?php
                            }
                            if(!empty($gmedia->tags)){
                                $item_tags = array();
                                foreach($gmedia->tags as $term){
                                    $term->slug = $term->name;
                                    $term_url   = get_term_link($term);
                                    //$term_url    = $gmCore->gmcloudlink($term->term_id, 'tag');
                                    $item_tags[] = "<span class='gmsingle_term'><a href='{$term_url}'>#{$term->name}</a></span>";
                                }
                                ?>
                                <div class="gmsingle_terms">
                                    <span class="gmsingle_term_label"><?php _e('Tags'); ?>:</span>
                                    <span class="gmsingle_tags"><?php echo implode(' ', $item_tags); ?></span>
                                </div>
                            <?php } ?>
                        </div>

                        <?php if($gmedia->gps){ ?>
                            <div class="gmsingle_location_section">
                                <div class="gmsingle_details_title"><?php _e('Location'); ?></div>

                                <div class="gmsingle_location_info">
                                    <a href='https://www.google.com/maps/place/{$loc}' target='_blank'><img src='//maps.googleapis.com/maps/api/staticmap?size=320x240&zoom=10&scale=2&maptype=roadmap&markers=<?php echo $gmedia->gps; ?>' alt='' width='320' height='240'/></a>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="gmsingle_details_section">
                            <div class="gmsingle_details_title"><?php _e('Details', 'grand-media'); ?></div>

                            <div class="gmsingle_slide_details">
                                <?php /* ?>
                                <div class='gmsingle_badges'>
                                    <div class='gmsingle_badges__column'>
                                        <div class='gmsingle_badges__label'><?php _e('Views', 'grand-media'); ?></div>
                                        <div class='gmsingle_badges__count'><?php echo $gmedia->meta['views'][0]; ?></div>
                                    </div>
                                    <div class='gmsingle_badges__column'>
                                        <div class='gmsingle_badges__label'><?php _e('Likes', 'grand-media'); ?></div>
                                        <div class='gmsingle_badges__count gmsingle_like_count'><?php echo $gmedia->meta['likes'][0]; ?></div>
                                    </div>
                                    <div class='gmsingle_clearfix'></div>
                                </div>
                                <?php
                                */
                                $exif = $gmCore->metadata_info($gmedia->ID);

                                $details = array();
                                if(!empty($exif)){
                                    $details['model']           = empty($exif['model'])? '' : $exif['model']['value'];
                                    $details['lens']            = empty($exif['lens'])? '' : $exif['lens']['value'];
                                    $details['camera_settings'] = array(
                                            'focallength' => empty($exif['focallength'])? (empty($exif['focallength35'])? '' : $exif['focallength35']['value']) : $exif['focallength']['value'],
                                            'aperture'    => empty($exif['aperture'])? '' : str_replace('f', 'ƒ', $exif['aperture']['value']),
                                            'exposure'    => empty($exif['exposure'])? '' : $exif['exposure']['value'],
                                            'iso'         => empty($exif['iso'])? '' : 'ISO ' . $exif['iso']['value']
                                    );
                                    $details['camera_settings'] = array_filter($details['camera_settings']);
                                    $details['taken']           = empty($exif['created_timestamp'])? '' : date_i18n(get_option('date_format'), $exif['created_timestamp']['value']);
                                }
                                $details['uploaded'] = date_i18n(get_option('date_format'), strtotime($gmedia->date));

                                if(!empty($details['model'])){ ?>
                                    <div class='gmsingle_exif'>
                                        <div class='gmsingle_label gmsingle_exif_model'><?php echo $details['model']; ?></div>
                                        <?php if(!empty($details['lens'])){ ?>
                                            <div class='gmsingle_label_small gmsingle_exif_lens'><?php echo $details['lens']; ?></div>
                                        <?php }
                                        $camera_settings = array();
                                        foreach($details['camera_settings'] as $key => $value){
                                            $camera_settings[] = "<span class='gmsingle_exif_{$key}'>{$value}</span>";
                                        }
                                        if(!empty($camera_settings)){ ?>
                                            <div class='gmsingle_label_small gmsingle_camera_settings'><?php echo implode('<span class="gmsingle_separator"> / </span>', $camera_settings); ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                                <div class='gmsingle_meta'>
                                    <?php if(!empty($details['taken'])){ ?>
                                        <div class='gmsingle_clearfix'>
                                            <span class='gmsingle_meta_key'><?php _e('Created', 'grand-media'); ?></span>
                                            <span class='gmsingle_meta_value'><?php echo $details['taken']; ?></span>
                                        </div>
                                    <?php } ?>
                                    <div class='gmsingle_clearfix'>
                                        <span class='gmsingle_meta_key'><?php _e('Uploaded', 'grand-media'); ?></span>
                                        <span class='gmsingle_meta_value'><?php echo $details['uploaded']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <style type="text/css">
                    .gmsingle_clearfix { display:block; }
                    .gmsingle_clearfix::after { visibility:hidden; display:block; font-size:0; content:' '; clear:both; height:0; }
                    .gmsingle_wrapper { margin: 0 auto; }
                    .gmsingle_wrapper * { -webkit-box-sizing:border-box; -moz-box-sizing:border-box; box-sizing:border-box; }
                    .gmsingle_photo_header { margin-bottom:15px; }
                    .gmsingle_name_wrap { padding:24px 0 2px 80px; height:85px; max-width:100%; overflow:hidden; white-space:nowrap; position:relative; }
                    .gmsingle_name_wrap .gmsingle_user_avatar { position:absolute; top:20px; left:0; }
                    .gmsingle_name_wrap .gmsingle_user_avatar a.gmsingle_user_avatar_link { display:block; text-decoration:none; }
                    .gmsingle_name_wrap .gmsingle_user_avatar img { height:60px; width:auto; overflow:hidden; border-radius:3px; }
                    .gmsingle_name_wrap .gmsingle_title_author { display:inline-block; vertical-align:top; max-width:100%; }
                    .gmsingle_name_wrap .gmsingle_title_author .gmsingle_title { text-rendering:auto; font-weight:100; font-size:24px; width:100%; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; margin:0; padding:1px 0; height:1.1em; line-height:1; box-sizing:content-box; text-transform:none; letter-spacing: 0px; text-transform: capitalize; }
                    .gmsingle_name_wrap .gmsingle_title_author > div { font-size:14px; }
                    .gmsingle_name_wrap .gmsingle_title_author .gmsingle_author_name { float:left; }
                    .gmsingle_name_wrap .gmsingle_title_author a { font-size:inherit; }

                    .gmsingle_photo_info { display:flex; flex-wrap:wrap; }
                    .gmsingle_details_title { margin:0; padding:0; text-transform:uppercase; font-size:18px; line-height:1em; font-weight:300; height:1.1em; display:inline-block; overflow:visible; border:none; }
                    .gmsingle_description_wrap { flex: 1; overflow:hidden; min-width: 220px; max-width:100%; padding-right:7px; margin-bottom:30px; }
                    .gmsingle_description_wrap .gmsingle_terms { overflow:hidden; margin:0; position:relative; font-size:14px; font-weight:300; }
                    .gmsingle_description_wrap .gmsingle_term_label { margin-right: 10px; }
                    .gmsingle_description_wrap .gmsingle_term_label:empty { display:none; }
                    .gmsingle_description_wrap .gmsingle_terms .gmsingle_term { display:inline-block; margin:0 12px 1px 0; }
                    .gmsingle_description_wrap .gmsingle_terms .gmsingle_term a { white-space:nowrap; }

                    .gmsingle_details_section { flex: 1; width:33%; padding-right:7px; padding-left:7px; min-width: 220px; max-width:100%; }
                    .gmsingle_details_section .gmsingle_slide_details { margin:20px 0; }
                    .gmsingle_location_section { flex: 1; width:27%; padding-right:7px; padding-left:7px; min-width: 220px; max-width:100%; }
                    .gmsingle_location_section .gmsingle_location_info { margin:20px 0; }
                    .gmsingle_location_section .gmsingle_location_info * { display:block; }
                    .gmsingle_location_section .gmsingle_location_info img { width:100%; height:auto; }
                    .gmsingle_badges { border-bottom:1px solid rgba(0,0,0,0.1); padding-bottom:17px; margin-bottom:12px; text-align:left; font-weight:300; }
                    .gmsingle_badges__column { display:inline-block; vertical-align:top; width:40%; min-width:80px; }
                    .gmsingle_badges__column .gmsingle_badges__label { font-size:14px; }
                    .gmsingle_badges__column .gmsingle_badges__count { font-size:20px; line-height:1em; margin-top:1px; }
                    .gmsingle_exif { border-bottom:1px solid rgba(0,0,0,0.1); padding-bottom:12px; margin-bottom:12px; text-align:left; font-size:14px; line-height:1.7em; font-weight:300; }
                    .gmsingle_exif .gmsingle_camera_settings .gmsingle_separator { font-weight:200; padding:0 5px; display:inline-block; }
                    .gmsingle_meta { padding-bottom:12px; margin-bottom:12px; text-align:left; font-size:14px; line-height: 1.2em; font-weight:300;}
                    .gmsingle_meta .gmsingle_meta_key { float:left; padding:3px 0; width:40%; min-width:80px; }
                    .gmsingle_meta .gmsingle_meta_value { float:left; white-space:nowrap; padding:3px 0; text-transform:capitalize; }
                </style>
                <?php
            } else {
                echo $content; //the_content();
            }

            $ob_content = ob_get_contents();
            ob_end_clean();

            $content = $ob_content;

        }
    } else{
        if('get_the_excerpt' != current_filter()){
            $term_id = get_post_meta($post->ID, '_gmedia_term_ID', true);
            if(in_array($post->post_type, array('gmedia_album'))){
                $content .= do_shortcode("[gm id={$term_id}]");
            } elseif($post->post_type == 'gmedia_gallery'){
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
