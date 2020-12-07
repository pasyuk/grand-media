<?php
/** @var $gmDB
 * @var  $gmCore
 * @var  $gmGallery
 * @var  $module
 * @var  $settings
 * @var  $terms
 * @var  $gmedia
 * @var  $is_bot
 * @var  $query
 **/

global $wp;
$settings    = array_merge($settings, array('url'           => add_query_arg($_SERVER['QUERY_STRING'], '', home_url($wp->request)),
                                            'module_dirurl' => $module['url'],
                                            'ajax_actions'  => array('comments' => array('action' => 'load_comments',
                                                                                         'data'   => array('post_id' => 0)
                                            )
                                            )
));
$allsettings = array_merge($module['options'], $settings);

$base_url_host = parse_url($gmCore->upload['url'], PHP_URL_HOST);
$term_url      = remove_query_arg('gm' . $id);

$gmedias = $gmDB->get_gmedias($query);
if(empty($gmedias)){
    echo GMEDIA_GALLERY_EMPTY;

    return;
}
foreach($gmedias as $gmkey => $item){
    gmedia_item_more_data($item);

    $gps       = str_replace(' ', '', $item->gps);
    $attr_data = array('id'      => $item->ID,
                       'post_id' => $item->post_id,
                       'ratio'   => $item->img_ratio,
                       'mtype'   => $item->type,
                       'ext'     => $item->ext,
                       'views'   => empty($item->meta['views'][0])? 0 : (int)$item->meta['views'][0],
                       'likes'   => empty($item->meta['likes'][0])? 0 : (int)$item->meta['likes'][0],
    );

    if(!empty($allsettings['commentsEnabled'])){
        $cc = 0;
        if($item->post_id){
            $cc = wp_count_comments($item->post_id);
            $cc = $cc->approved;
        }
        $attr_data['cc'] = $cc;
    }

    if(!empty($allsettings['share_post_link'])){
        $attr_data['post_link'] = get_permalink($item->post_id);
    }

    $link_target = '';
    if($item->link){
        $url_host = parse_url($item->link, PHP_URL_HOST);
        if($url_host == $base_url_host || empty($url_host)){
            $link_target = '_self';
        } else{
            $link_target = '_blank';
        }
        if(isset($item->meta['link_target'][0])){
            $link_target = $item->meta['link_target'][0];
        }

        $attr_data['link']   = $item->link;
        $attr_data['target'] = $link_target;
    }

    $attr_data_html = '';
    foreach($attr_data as $key => $val){
        $attr_data_html .= " data-{$key}='" . esc_attr($val) . "'";
    }
    $item->attr_data_html = $attr_data_html;

    ob_start();

    if((int)$allsettings['show_title']){
        $title = $item->title;
        if($item->link){
            $title = "<a href='{$item->link}' target='{$link_target}'>" . ($title? $title : $item->gmuid) . '</a>';
        }
    } else{
        $title = '';
    }
    if($title || $item->description){
        ?>
        <div class="gmCubikLite_description">
            <div class="gmCubikLite_title"><?php echo $title; ?></div>
            <div class="gmCubikLite_text"><?php echo str_replace(array("\r\n", "\r", "\n"), '', wpautop($item->description)); ?></div>
        </div>
        <?php
    }

    $tags = array();
    if((int)$allsettings['show_tags'] && $item->tags){
        foreach($item->tags as $_term){
            $url    = add_query_arg(array("gm{$id}" => array('tag__in' => $_term->term_id)), $term_url);
            $tags[] = '<a href="' . urldecode($url) . '" class="gmCubikLite_tag">#' . $_term->name . '</a>';
        }
    }

    $categories = array();
    if((int)$allsettings['show_categories'] && $item->categories){
        foreach($item->categories as $_term){
            $url          = add_query_arg(array("gm{$id}" => array('category__in' => $_term->term_id)), $term_url);
            $categories[] = '<a href="' . urldecode($url) . '" class="gmCubikLite_cat">' . $_term->name . '</a>';
        }
    }

    $albums = array();
    if((int)$allsettings['show_albums'] && $item->album){
        foreach($item->album as $_term){
            $url      = add_query_arg(array("gm{$id}" => array('album__in' => $_term->term_id)), $term_url);
            $albums[] = '<a href="' . urldecode($url) . '" class="gmCubikLite_alb">' . $_term->name . '</a>';
        }
    }
    ?>
    <div class="gmCubikLite_terms">
        <?php if(count($tags)){ ?>
            <div class="gmCubikLite_terms_container"><?php echo implode(' ', $tags); ?></div>
        <?php }

        $details                               = array();
        $details[ __('Album', 'grand-media') ] = implode(', ', $albums);
        $cat_key                               = (count($categories) > 1)? __('Categories', 'grand-media') : __('Category', 'grand-media');
        $details[ $cat_key ]                   = implode(', ', $categories);
        $details                               = array_filter($details);
        if(count($details)){
            ?>
            <div class="gmCubikLite_other_terms">
                <table class="gmCubikLite_other_terms_table">
                    <?php foreach($details as $key => $value){ ?>
                        <tr class="gmCubikLite_term_row_<?php echo sanitize_key($key); ?>">
                            <td class="gmCubikLite_term_key"><?php echo ucwords($key); ?></td>
                            <td class="gmCubikLite_term_value"><?php echo $value; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <?php
        } ?>
    </div>
    <?php

    $item->details_html = ob_get_contents();
    ob_end_clean();

    $gmedias[ $gmkey ] = $item;
}
reset($gmedias);
?>
    <div class="gmCubikLite noLitebox">
        <div class="gmCubikLite_thumbsContainer">
            <div class="gmCubikLite_thumbsWrapper">
                <?php
                $gridSize = $allsettings['thumbCols'];
                for($i = 1; $i <= 6; $i ++){
                    echo "<div class='gmCubikLite_face gmCubikLite_side-{$i}'><div class='gmCubikLite_faceContainer'><div class='gmCubikLite_faceWrapper'>";
                    for($j = 1; $j <= $gridSize; $j ++){
                        $item = array_shift($gmedias);
                        if(!$item){
                            break;
                        }
                        ?>
                        <div class="gmCubikLite_thumb gmCubikLite_thumbLoader<?php echo ($item->type !== 'image')? ' mfp-iframe' : ''; ?>"<?php echo $item->attr_data_html; ?>>
                            <a class="gmCubikLite_thumbImg" href="<?php echo $item->url; ?>" title="<?php echo esc_attr($item->title); ?>"><img src="<?php echo $item->url_thumb; ?>" data-src="<?php echo $item->url_web; ?>" alt="<?php echo esc_attr($item->alttext); ?>"></a>
                            <script type="text/html" class="gmCubikLite_thumbDetails"><?php echo $item->details_html; ?></script>
                        </div>
                        <?php
                    }
                    echo '</div></div></div>';
                }
                $gmCount = count($gmedias);
                if($gmCount){
                    echo '<div class="gmCubikLite_noplace">';
                    for($i = 0; $i < $gmCount; $i ++){
                        $item = array_shift($gmedias);
                        if(!$item){
                            break;
                        }
                        ?>
                        <div class="gmCubikLite_thumb<?php echo ($item->type !== 'image')? ' mfp-iframe' : ''; ?>"<?php echo $item->attr_data_html; ?>>
                            <a class="gmCubikLite_thumbImg" href="<?php echo $item->url; ?>" title="<?php echo esc_attr($item->title); ?>"><img data-src="<?php echo $item->url_web; ?>" alt="<?php echo esc_attr($item->alttext); ?>"></a>
                            <script type="text/html" class="gmCubikLite_thumbDetails"><?php echo $item->details_html; ?></script>
                        </div>
                        <?php
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

<?php
/* Dynamic CSS */
$mfp_id  = "#mfp_gm_{$id}";
$mfp_css = '';
if(isset($settings['lightboxControlsColor'])){
    $mfp_css .= "
{$mfp_id} .mfp-arrow-right:after,
{$mfp_id} .mfp-arrow-right .mfp-a {border-left-color:#{$settings['lightboxControlsColor']};}
{$mfp_id} .mfp-arrow-left:after,
{$mfp_id} .mfp-arrow-left .mfp-a {border-right-color:#{$settings['lightboxControlsColor']};}
{$mfp_id} .mfp-close,
{$mfp_id} .mfp-comments,
{$mfp_id} .mfp-likes,
{$mfp_id} .mfp-share {color:#{$settings['lightboxControlsColor']};}
{$mfp_id} .mfp-preloader {background-color:#{$settings['lightboxControlsColor']};}";
}
if(isset($settings['lightboxTitleColor'])){
    $mfp_css .= "
{$mfp_id} .gmCubikLite_title,
{$mfp_id} .mfp-counter {color:#{$settings['lightboxTitleColor']};}";
}
if(isset($settings['lightboxTextColor'])){
    $mfp_css .= "
{$mfp_id} .gmCubikLite_text,
{$mfp_id} .gmCubikLite_other_terms,
{$mfp_id} .gmCubikLite_other_terms_table {color:#{$settings['lightboxTextColor']};}";
}
if(isset($settings['lightboxBGColor'])){
    $mfp_css .= "
{$mfp_id}_bg.mfp-bg {background-color:#{$settings['lightboxBGColor']};}";
}
if(isset($settings['lightboxBGAlpha'])){
    $alpha = $settings['lightboxBGAlpha'] / 100;
    $mfp_css .= "
{$mfp_id}_bg.mfp-bg {opacity:{$alpha};}
{$mfp_id}_bg.mfp-zoom-in.mfp-bg {opacity:0}
{$mfp_id}_bg.mfp-zoom-in.mfp-ready.mfp-bg {opacity:{$alpha};}
{$mfp_id}_bg.mfp-zoom-in.mfp-removing.mfp-bg {opacity:0}";
}
if(isset($settings['sidebarBGColor'])){
    $mfp_css .= "
{$mfp_id} .mfp-comments-content {background-color:#{$allsettings['commentsBGColor']};}";
}
if($mfp_css){
    $settings['mfp_css'] = $mfp_css;
}

$cssid = "#GmediaGallery_{$id}";
$dcss  = '';
if(isset($settings['thumbCols'])){
    $width = 100 / $settings['thumbCols'];
    $dcss .= "
{$cssid} .gmCubikLite_face .gmCubikLite_thumb {width:{$width}%;}";
}
if(isset($settings['facePadding'])){
    $dcss .= "
{$cssid} .gmCubikLite_face {padding:{$allsettings['facePadding']}px;}";
}
if(isset($settings['faceMargin'])){
    $neg = 0 - $allsettings['faceMargin'];
    $pos = 0 + $allsettings['faceMargin'];
    $dcss .= "
{$cssid} .gmCubikLite_thumbsWrapper .gmCubikLite_side-1 { -webkit-transform:translateY(-50%) translateY({$neg}px) rotateX(90deg); -moz-transform:translateY(-50%) translateY({$neg}px) rotateX(90deg); transform:translateY(-50%) translateY({$neg}px) rotateX(90deg); }
{$cssid} .gmCubikLite_thumbsWrapper .gmCubikLite_side-2 { -webkit-transform:rotateY(-90deg) translateX(50%) translateX({$pos}px) rotateY(90deg); -moz-transform:rotateY(-90deg) translateX(50%) translateX({$pos}px) rotateY(90deg); transform:rotateY(-90deg) translateX(50%) translateX({$pos}px) rotateY(90deg); }
{$cssid} .gmCubikLite_thumbsWrapper .gmCubikLite_side-3 { -webkit-transform:translateX(50%) translateX({$pos}px) rotateY(90deg); -moz-transform:translateX(50%) translateX({$pos}px) rotateY(90deg); transform:translateX(50%) translateX({$pos}px) rotateY(90deg); }
{$cssid} .gmCubikLite_thumbsWrapper .gmCubikLite_side-4 { -webkit-transform:rotateY(90deg) translateX(50%) translateX({$pos}px) rotateY(90deg); -moz-transform:rotateY(90deg) translateX(50%) translateX({$pos}px) rotateY(90deg); transform:rotateY(90deg) translateX(50%) translateX({$pos}px) rotateY(90deg); }
{$cssid} .gmCubikLite_thumbsWrapper .gmCubikLite_side-5 { -webkit-transform:translateX(-50%) translateX({$neg}px) rotateY(-90deg); -moz-transform:translateX(-50%) translateX({$neg}px) rotateY(-90deg); transform:translateX(-50%) translateX({$neg}px) rotateY(-90deg); }
{$cssid} .gmCubikLite_thumbsWrapper .gmCubikLite_side-6 { -webkit-transform:translateY(50%) translateY({$pos}px) rotateX(-90deg) rotate(180deg); -moz-transform:translateY(50%) translateY({$pos}px) rotateX(-90deg) rotate(180deg); transform:translateY(50%) translateY({$pos}px) rotateX(-90deg) rotate(180deg); }";
}
if($dcss){
    $customCSS = $dcss . $customCSS;
}

if(!$is_bot){
    if($shortcode_raw){
        echo '<pre style="display:none">';
    }
    ?>
    <script type="text/javascript">
      document.addEventListener('DOMContentLoaded', function(){
        jQuery(function() {
            var settings = <?php echo json_encode($settings); ?>;
            jQuery('#GmediaGallery_<?php echo esc_attr( $id ); ?>').gmCubikLite([settings]);
        });
      });
    </script><?php
    if($shortcode_raw){
        echo '</pre>';
    }
}

