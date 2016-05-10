<?php
/** @var $gmDB
 * @var  $gmCore
 * @var  $gmGallery
 * @var  $gallery
 * @var  $module
 * @var  $settings
 * @var  $terms
 * @var  $gmedia
 * @var  $is_bot
 **/

$settings    = array_merge($settings, array(
        'ID'            => $gallery['term_id'],
        'module_dirurl' => $module['url'],
        'ajax_actions'  => array(
                'comments' => array(
                        'action' => 'load_comments',
                        'data'   => array('post_id' => 0)
                )
        ),
));
$allsettings = array_merge($module['options'], $settings);

$content = array();
if(!isset($is_bot)) {
    $is_bot = false;
}
if(!isset($shortcode_raw)) {
    $shortcode_raw = false;
}
$tab           = sanitize_title($gallery['name']);
$base_url_host = parse_url($gmCore->upload['url'], PHP_URL_HOST);
$i             = 0;
foreach($terms as $term) {

    foreach($gmedia[$term->term_id] as $item) {
        $meta      = $gmDB->get_metadata('gmedia', $item->ID);
        $_metadata = isset($meta['_metadata'])? $meta['_metadata'][0] : array();

        $link_target = '';
        if($item->link) {
            $url_host = parse_url($item->link, PHP_URL_HOST);
            if($url_host == $base_url_host || empty($url_host)) {
                $link_target = '_self';
            } else {
                $link_target = '_blank';
            }
        }
        if(isset($meta['link_target'][0])) {
            $link_target = $meta['link_target'][0];
        }

        $image = $gmCore->gm_get_media_image($item->ID, 'web');
        $thumb = $gmCore->gm_get_media_image($item->ID, 'thumb');
        $type  = explode('/', $item->mime_type);
        $type  = $type[0];
        $ext   = pathinfo($item->gmuid, PATHINFO_EXTENSION);
        if(!isset($_metadata['web'])) {
            $img_size           = getimagesize($image);
            $_metadata['web']   = array_slice($img_size, 0, 2);
            $img_size           = getimagesize($thumb);
            $_metadata['thumb'] = array_slice($img_size, 0, 2);
        }

        $content[$i] = array(
                'id'         => $item->ID,
                'post_id'    => $item->post_id,
                'type'       => $type,
                'ext'        => strtolower($ext),
                'src'        => "/{$gmGallery->options['folder'][$type]}/{$item->gmuid}",
                'image'      => $image,
                'thumb'      => $thumb,
                'title'      => $item->title,
                'text'       => str_replace(array("\r\n", "\r", "\n"), '', wpautop($item->description)),
                'link'       => $item->link,
                'linkTarget' => $link_target,
                'date'       => $item->date,
                'websize'    => array_values($_metadata['web']),
                'thumbsize'  => array_values($_metadata['thumb'])
        );

        if(!empty($allsettings['viewsEnabled']) || !empty($allsettings['likesEnabled'])) {
            $content[$i]['views'] = empty($meta['views'][0])? 0 : (int)$meta['views'][0];
            if(!empty($allsettings['likesEnabled'])){
                $content[$i]['likes'] = empty($meta['likes'][0])? 0 : (int)$meta['likes'][0];
            }
        }
        if(!empty($allsettings['commentsEnabled'])) {
            if($item->post_id) {
                $cc = wp_count_comments($item->post_id);
                $content[$i]['cc'] = $cc->approved;
            } else {
                $content[$i]['cc'] = 0;
            }
        }

        if($allsettings['share_post_link']) {
            $content[$i]['post_link'] = get_permalink($item->post_id);
        }
        $i++;
    }
}

if(!empty($content)) {
    $content = array_values($content);

    $mfp_id  = "#mfp_gm_{$gallery['term_id']}";
    $mfp_css = '';
    if(isset($settings['lightboxControlsColor'])) {
        $mfp_css .= "
{$mfp_id} .mfp-arrow-right:after,
{$mfp_id} .mfp-arrow-right .mfp-a {border-left-color:#{$settings['lightboxControlsColor']};}
{$mfp_id} .mfp-arrow-left:after,
{$mfp_id} .mfp-arrow-left .mfp-a {border-right-color:#{$settings['lightboxControlsColor']};}
{$mfp_id} .mfp-close,
{$mfp_id} .mfp-comments,
{$mfp_id} .mfp-likes,
{$mfp_id} .mfp-views,
{$mfp_id} .mfp-share {color:#{$settings['lightboxControlsColor']};}
{$mfp_id} .mfp-preloader {background-color:#{$settings['lightboxControlsColor']};}";
    }
    if(isset($settings['lightboxTitleColor'])) {
        $mfp_css .= "
{$mfp_id} .mfp-title,
{$mfp_id} .mfp-counter {color:#{$settings['lightboxTitleColor']};}";
    }
    if(isset($settings['lightboxTextColor'])) {
        $mfp_css .= "
{$mfp_id} .mfp-description {color:#{$settings['lightboxTextColor']};}";
    }
    if(isset($settings['lightboxBGColor'])) {
        $mfp_css .= "
{$mfp_id}_bg.mfp-bg {background-color:#{$settings['lightboxBGColor']};}";
    }
    if(isset($settings['lightboxBGAlpha'])) {
        $alpha = $settings['lightboxBGAlpha'] / 100;
        $mfp_css .= "
{$mfp_id}_bg.mfp-bg {opacity:{$alpha};}
{$mfp_id}_bg.mfp-zoom-in.mfp-bg {opacity:0}
{$mfp_id}_bg.mfp-zoom-in.mfp-ready.mfp-bg {opacity:{$alpha};}
{$mfp_id}_bg.mfp-zoom-in.mfp-removing.mfp-bg {opacity:0}";
    }
    if(isset($settings['commentsBGColor']) || isset($settings['commentsBGAlpha'])) {
        if(method_exists($gmCore, 'hex2rgb')) {
            $rgb   = implode(',', $gmCore->hex2rgb($allsettings['commentsBGColor']));
            $alpha = $allsettings['commentsBGAlpha'] / 100;
            $mfp_css .= "
{$mfp_id} .mfp-comments-content {background-color:rgba({$rgb},{$alpha});}";
        } else {
            $mfp_css .= "
{$mfp_id} .mfp-comments-content {background-color:#{$allsettings['commentsBGColor']};}";
        }
    }
    if($mfp_css) {
        $settings['mfp_css'] = $mfp_css;
    }

    $cssid = "#GmediaGallery_{$gallery['term_id']}";
    $dcss  = '';
    if(isset($settings['thumbWidth']) || isset($settings['thumbHeight']) || isset($settings['thumbWidthMobile']) || isset($settings['thumbHeightMobile'])) {
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer {width:{$allsettings['thumbWidth']}px; height:{$allsettings['thumbHeight']}px;}
{$cssid} .gmPhantom_MobileView .gmPhantom_ThumbContainer {width:{$allsettings['thumbWidthMobile']}px; height:{$allsettings['thumbHeightMobile']}px;}";
    }
    if(isset($settings['thumbPadding'])) {
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer {padding:{$allsettings['thumbPadding']}px;}";
    }
    if(isset($settings['thumbBG'])) {
        if('' == $settings['thumbBG']) {
            $dcss .= "
{$cssid} .gmPhantom_ThumbContainer {background-color:transparent;}";
        } else {
            $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LabelBottom .gmPhantom_ThumbLabel {background-color:#{$settings['thumbBG']};}";
        }
    }
    if(isset($settings['thumbAlpha'])) {
        $alpha = $settings['thumbAlpha'] / 100;
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer .gmPhantom_Thumb {opacity:{$alpha};}";
    }
    if(isset($settings['thumbAlphaHover'])) {
        $alpha = $settings['thumbAlphaHover'] / 100;
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer:hover .gmPhantom_Thumb {opacity:{$alpha};}";
    }
    if(isset($settings['thumbBorderSize']) || isset($settings['thumbBorderColor'])) {
        if((int)$settings['thumbBorderSize']) {
            $dcss .= "
{$cssid} .gmPhantom_ThumbContainer {border:{$allsettings['thumbBorderSize']}px solid #{$allsettings['thumbBorderColor']};}";
        } else {
            $dcss .= "
{$cssid} .gmPhantom_ThumbContainer {border:none;}";
        }
    }
    if(isset($settings['thumbBorderSize'])) {
        if((int)$settings['thumbBorderSize'] > 1) {
            $dcss .= "
{$cssid} .gmPhantom_ThumbContainer {box-shadow:0 0 5px -2px;}";
        } else {
            $dcss .= "
{$cssid} .gmPhantom_ThumbContainer {box-shadow:none;}";
        }
    }
    if(isset($settings['label8TextColor'])) {
        $dcss .= "
{$cssid} .gmPhantom_ThumbLabel {color:#{$allsettings['label8TextColor']};}";
    }
    if(isset($settings['label8LinkColor'])) {
        $dcss .= "
{$cssid} .gmPhantom_ThumbLabel a {color:#{$allsettings['label8LinkColor']};}";
    }
    if(isset($settings['labelTextColor'])) {
        $dcss .= "
{$cssid} .gmPhantom_LabelInside .gmPhantom_ThumbLabel,
{$cssid} .gmPhantom_LabelHover .gmPhantom_ThumbLabel {color:#{$allsettings['labelTextColor']};}";
    }
    if(isset($settings['labelLinkColor'])) {
        $dcss .= "
{$cssid} .gmPhantom_LabelInside .gmPhantom_ThumbLabel a,
{$cssid} .gmPhantom_LabelHover .gmPhantom_ThumbLabel a {color:#{$allsettings['labelLinkColor']};}";
    }
    if($dcss) {
        $customCSS = $dcss . $customCSS;
    }


    $json_settings = json_encode($settings);

    ?>
    <?php if(!$is_bot) {
        if($shortcode_raw) {
            echo '<pre style="display:none">';
        }
        ?>
        <script type="text/javascript">
            jQuery(function() {
                var settings = <?php echo $json_settings; ?>;
                var content = <?php echo json_encode($content); ?>;
                jQuery('#GmediaGallery_<?php echo $gallery['term_id'] ?>').gmPhantom([content, settings]);
            });
        </script><?php
        if($shortcode_raw) {
            echo '</pre>';
        }
    }
    ?>
    <div class="gmPhantom_Container noLightbox delay">
        <div class="gmPhantom_Background"></div>
        <?php
        $thumbsWrapper_class = (int)$allsettings['thumbScale']? ' gmPhantom_ThumbScale' : '';
        if('label' == $allsettings['thumbsInfo']) {
            if((int)$allsettings['labelOnHover']) {
                $thumbsWrapper_class .= ' gmPhantom_LabelHover';
            } else {
                $thumbsWrapper_class .= ' gmPhantom_LabelInside';
            }
        } elseif('label_bottom' == $allsettings['thumbsInfo']) {
            $thumbsWrapper_class .= ' gmPhantom_LabelBottom';
        } elseif('tooltip' == $allsettings['thumbsInfo']) {
            $thumbsWrapper_class .= ' gmPhantom_LabelTooltip';
        } elseif('none' == $allsettings['thumbsInfo']) {
            $thumbsWrapper_class .= ' gmPhantom_LabelNone';
        }
        ?>
        <div class="gmPhantom_thumbsWrapper<?php echo $thumbsWrapper_class; ?>">
            <?php $i   = 0;
            $wrapper_r = $allsettings['thumbWidth'] / $allsettings['thumbHeight'];
            $mobile_wrapper_r = $allsettings['thumbWidthMobile'] / $allsettings['thumbHeightMobile'];
            foreach($content as $item) {
                $thumb_r = $item['thumbsize'][0] / $item['thumbsize'][1];
                if($wrapper_r < $thumb_r) {
                    $orientation = 'landscape';
                    $margin      = 'margin:0 0 0 -' . floor(($allsettings['thumbHeight'] * $thumb_r - $allsettings['thumbWidth']) / $allsettings['thumbWidth'] * 50) . '%;';
                } else {
                    $orientation = 'portrait';
                    $margin      = 'margin:-' . floor(($allsettings['thumbWidth'] / $thumb_r - $allsettings['thumbHeight']) / $allsettings['thumbHeight'] * 25) . '% 0 0 0;';
                }

                $class = '';
                $style = 'left:50%; top:50%; transform:translate(-50%, -50%);'
                ?>
                <div class="gmPhantom_ThumbContainer gmPhantom_ThumbLoader<?php echo(!in_array($item['type'], array('image'))? " mfp-iframe" : ''); ?>" data-ratio="<?php echo $thumb_r; ?>" data-no="<?php echo $i++; ?>"><?php
                ?><a href="<?php echo $gmCore->upload['url'] . $item['src']; ?>" class="gmPhantom_Thumb"><img style="<?php echo $style; ?>" src="<?php echo $item['thumb']; ?>" alt="<?php echo esc_attr($item['title']); ?>"/></a><?php
                if(in_array($allsettings['thumbsInfo'], array('label', 'label_bottom')) && ($item['title'] != '')) {
                    if(!empty($item['link'])) {
                        $item['title'] = "<a href='{$item['link']}' target='{$item['linkTarget']}'>{$item['title']}</a>";
                    }
                    ?>
                    <div class="gmPhantom_ThumbLabel"><?php echo $item['title']; ?></div>
                    <div style="display:none;" class="gmPhantom_ThumbCaption"><?php echo $item['text']; ?></div><?php
                } ?></div><?php
            } ?><br style="clear:both;"/>
        </div>

        <?php if(isset($counts['total_pages']) && !empty($counts['total_pages']) && 1 < intval($counts['total_pages'])){ ?>
        <div class="gmPhantom_pagination">
            <?php
            $params = $_GET;
            $gmid = 'gm' . $gallery['term_id'];
            $counts['total_pages'] = (int)$counts['total_pages'];
            for($x = 1; $x <= $counts['total_pages']; $x++){
                $li_class = $x == $counts['current_page']? ' gmPhantom_current_page' : '';
                $params[$gmid]['page'] = $x;
                $new_query_string = http_build_query($params);
                $self = '?' . urldecode($new_query_string);
                echo "<a class='gmPhantom_pager{$li_class}' href='{$self}'>{$x}</a>";
            }
            ?>
        </div>
        <?php } ?>
    </div>
    <?php
} else {
    echo '<div class="gmedia-no-files">' . GMEDIA_GALLERY_EMPTY . '</div>';
}


