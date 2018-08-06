<?php
/** @var $gmDB
 * @var  $gmCore
 * @var  $gmGallery
 * @var  $module
 * @var  $settings
 * @var  $terms
 * @var  $gmedia
 * @var  $is_bot
 **/

global $wp;
$settings    = array_merge($settings, array('ID'            => $id,
                                            'url'           => add_query_arg($_SERVER['QUERY_STRING'], '', home_url($wp->request)),
                                            'module_dirurl' => $module['url'],
                                            'ajax_actions'  => array('comments' => array('action' => 'load_comments',
                                                                                         'data'   => array('post_id' => 0)
                                            )
                                            ),
));
$allsettings = array_merge($module['options'], $settings);

$base_url_host = parse_url($gmCore->upload['url'], PHP_URL_HOST);
$term_url      = remove_query_arg('gm' . $id);

if(empty($query['per_page'])){
    $query['per_page'] = $allsettings['per_page'];
}
$gmedias           = $gmDB->get_gmedias($query);
if($gmDB->openPage < $gmDB->pages){
    $load_query         = $query;
    $load_query['page'] = $gmDB->openPage + 1;
    $load_more          = urldecode(add_query_arg(array("gm{$id}" => $load_query), $term_url));
} else{
    $load_more = false;
}

if(empty($gmedias)){
    echo GMEDIA_GALLERY_EMPTY;

    return;
}
$native = !empty($atts['native'])? true : false;

if(!isset($is_bot)){
    $is_bot = false;
}
if(!isset($shortcode_raw)){
    $shortcode_raw = false;
}

?>
    <div class="gmPhantom_Container noLightbox" <?php echo $is_bot? '' : 'style="opacity:0"'; ?>>
        <?php
        $thumbsWrapper_class = (int)$allsettings['thumbScale']? ' gmPhantom_ThumbScale' : '';
        if('label' == $allsettings['thumbsInfo']){
            if((int)$allsettings['labelOnHover']){
                $thumbsWrapper_class .= ' gmPhantom_LabelHover';
            } else{
                $thumbsWrapper_class .= ' gmPhantom_LabelInside';
            }
        } elseif('label_bottom' == $allsettings['thumbsInfo']){
            $thumbsWrapper_class .= ' gmPhantom_LabelBottom';
        } elseif('tooltip' == $allsettings['thumbsInfo']){
            $thumbsWrapper_class .= ' gmPhantom_LabelTooltip';
        } elseif('none' == $allsettings['thumbsInfo']){
            $thumbsWrapper_class .= ' gmPhantom_LabelNone';
        }
        ?>
        <div class="gmPhantom_thumbsWrapper<?php echo $thumbsWrapper_class; ?>">
            <?php
            $web_size   = array();
            $thumb_size = array();
            foreach($gmedias as $item){
                gmedia_item_more_data($item);
                $image = $item->url_web;
                $thumb = $item->url_thumb;
                $type  = $item->type;
                $ext   = strtolower($item->ext);

                $meta      = $item->meta;
                $description = str_replace(array("\r\n", "\r", "\n"), '', wpautop($item->description));
                $title       = $item->title;
                $alttext     = $item->alttext;

                $link_target = '';
                if($item->link){
                    $link_target = $allsettings['link_target'];
                    if('auto' == $link_target){
                        $url_host = parse_url($item->link, PHP_URL_HOST);
                        if($url_host == $base_url_host || empty($url_host)){
                            $link_target = '_self';
                        } else{
                            $link_target = '_blank';
                        }
                    }
                    if(isset($meta['link_target'][0])){
                        $link_target = $meta['link_target'][0];
                    }
                    $title = "<a href='{$item->link}' target='{$link_target}'>" . ($title? $title : $item->gmuid) . '</a>';
                }

                if('image' === $type){
                    $download_link = $item->url_original;
                } else{
                    $download_link = $item->url;
                }

                $thumb_r   = $item->thumb_width / $item->thumb_height;
                $item_data = array('id'      => $item->ID,
                                   'post_id' => $item->post_id,
                                   'ratio'   => $thumb_r,
                                   'type'    => $type,
                                   'ext'     => $ext
                );

                $item_data['views'] = empty($meta['views'][0])? 0 : (int)$meta['views'][0];
                $item_data['likes'] = empty($meta['likes'][0])? 0 : (int)$meta['likes'][0];

                if(!empty($allsettings['commentsEnabled'])){
                    $cc = 0;
                    if($item->post_id){
                        $cc = wp_count_comments($item->post_id);
                        $cc = $cc->approved;
                    }
                    $item_data['cc'] = $cc;
                }

                if($allsettings['share_post_link']){
                    $item_data['post_link'] = get_permalink($item->post_id);
                }

                if($item->link){
                    $item_data['link']   = $item->link;
                    $item_data['target'] = $link_target;
                }

                $item_data_html = '';
                foreach($item_data as $key => $val){
                    $val = esc_attr($val);
                    $item_data_html .= " data-{$key}=\"{$val}\"";
                }
                ?>
            <div class="gmPhantom_ThumbContainer gmPhantom_ThumbLoader<?php echo(!in_array($type, array('image'))? " mfp-iframe" : ''); ?>"<?php echo $item_data_html; ?>>
                <a href="<?php echo (!empty($allsettings['thumb2link']) && $item->link)? $item->link : $item->url; ?>" class="gmPhantom_Thumb"><img src="<?php echo $thumb; ?>" data-src="<?php echo $image; ?>" alt="<?php esc_attr_e($alttext); ?>"/></a>
                <?php
                if(in_array($allsettings['thumbsInfo'], array('label', 'label_bottom'))){ ?>
                    <div class="gmPhantom_ThumbLabel"><span class="gmPhantom_ThumbLabel_title"><?php echo $title; ?></span></div>
                    <?php
                } ?>
                <div style="display:none;" class="gmPhantom_Details">
                    <?php
                    if(!(int)$allsettings['show_title']){
                        $title = '';
                    }
                    if($title || $description){ ?>
                        <div class="gmPhantom_description">
                            <div class="gmPhantom_title"><?php echo $title; ?></div>
                            <div class="gmPhantom_text"><?php echo $description; ?></div>
                        </div>
                    <?php } ?>
                    <?php
                    $tags = array();
                    if((int)$allsettings['show_tags'] && ($terms = $item->tags)){
                        foreach($terms as $_term){
                            $url    = add_query_arg(array("gm{$id}" => array('tag__in' => $_term->term_id)), $term_url);
                            $tags[] = '<a href="' . urldecode($url) . '" class="gmPhantom_tag">#' . $_term->name . '</a>';
                        }
                    }

                    $categories = array();
                    if((int)$allsettings['show_categories'] && ($terms = $item->categories)){
                        foreach($terms as $_term){
                            $url          = add_query_arg(array("gm{$id}" => array('category__in' => $_term->term_id)), $term_url);
                            $categories[] = '<a href="' . urldecode($url) . '" class="gmPhantom_cat">' . $_term->name . '</a>';
                        }
                    }

                    $albums = array();
                    if((int)$allsettings['show_albums'] && ($terms = $item->album)){
                        foreach($terms as $_term){
                            $url      = add_query_arg(array("gm{$id}" => array('album__in' => $_term->term_id)), $term_url);
                            $albums[] = '<a href="' . urldecode($url) . '" class="gmPhantom_alb">' . $_term->name . '</a>';
                        }
                    }
                    ?>
                    <div class="gmPhantom_terms">
                        <?php
                        if(count($tags)){
                            ?>
                            <div class="gmPhantom_tags_container"><?php echo implode(' ', $tags); ?></div>
                        <?php }

                        $details                               = array();
                        $details[ __('Album', 'grand-media') ] = implode(', ', $albums);
                        $cat_key                               = (count($categories) > 1)? __('Categories', 'grand-media') : __('Category', 'grand-media');
                        $details[ $cat_key ]                   = implode(', ', $categories);
                        $details                               = array_filter($details);
                        if(count($details)){
                            ?>
                            <div class="gmPhantom_other_terms">
                                <table class="gmPhantom_other_terms_table">
                                    <?php foreach($details as $key => $value){ ?>
                                        <tr class="gmPhantom_term_row_<?php echo sanitize_key($key); ?>">
                                            <td class="gmPhantom_term_key"><?php echo ucwords($key); ?></td>
                                            <td class="gmPhantom_term_value"><?php echo $value; ?></td>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </div>
                            <?php
                        } ?>
                    </div>
                </div>
                </div><?php
            }
            if($load_more){ ?>
                <div class="gmPhantom_LoadMore">
                    <?php $nextpage = $gmDB->openPage + 1;
                    echo "<a class='gmPhantom_pager' href='{$load_more}' title='" . __('Load more', 'grand-media') . "'><span class='gmPhantom_dots'>&bull;&bull;&bull;</span><span class='gmPhantom_page'>{$nextpage}</span></a>";
                    ?>
                </div>
            <?php } ?>
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
{$mfp_id} .gmPhantom_title,
{$mfp_id} .mfp-counter {color:#{$settings['lightboxTitleColor']};}";
}
if(isset($settings['lightboxTextColor'])){
    $mfp_css .= "
{$mfp_id} .gmPhantom_text,
{$mfp_id} .gmPhantom_other_terms,
{$mfp_id} .gmPhantom_other_terms_table {color:#{$settings['lightboxTextColor']};}";
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
if(isset($settings['thumbWidth']) || isset($settings['thumbHeight']) || isset($settings['thumbWidthMobile']) || isset($settings['thumbHeightMobile'])){
    $fsize1 = min($allsettings['thumbHeight'] / 2, $allsettings['thumbWidth'] / 2);
    $fsize2 = min($allsettings['thumbHeightMobile'] / 2, $allsettings['thumbWidthMobile'] / 2);
    $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LoadMore {width:{$allsettings['thumbWidth']}px; height:{$allsettings['thumbHeight']}px;}
{$cssid} .gmPhantom_MobileView .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_MobileView .gmPhantom_LoadMore {width:{$allsettings['thumbWidthMobile']}px; height:{$allsettings['thumbHeightMobile']}px;}
{$cssid} .gmPhantom_LoadMore .gmPhantom_pager {font-size:{$fsize1}px;line-height:{$allsettings['thumbHeight']}px;}
{$cssid} .gmPhantom_MobileView .gmPhantom_LoadMore .gmPhantom_pager {font-size:{$fsize2}px;line-height:{$allsettings['thumbHeightMobile']}px;}
{$cssid} .gmPhantom_LoadMore[data-col=\"1\"] {width:auto;height:50px;}
{$cssid} .gmPhantom_LoadMore[data-col=\"1\"] .gmPhantom_pager,
{$cssid} .gmPhantom_MobileView .gmPhantom_LoadMore[data-col=\"1\"] .gmPhantom_pager {font-size:40px;line-height:50px;}";
}
if(isset($settings['thumbsAlign'])){
    $margin = 'margin-left:auto;margin-right:auto;';
    if('left' == $settings['thumbsAlign']){
        $margin = 'margin-left:0;';
    } elseif('right' == $settings['thumbsAlign']){
        $margin = 'margin-right:0;';
    }
    $dcss .= "
{$cssid} .gmPhantom_Container {{$margin}}";
}
if(isset($settings['thumbsSpacing'])){
    $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LoadMore {margin:{$settings['thumbsSpacing']}px 0 0 {$settings['thumbsSpacing']}px;}
{$cssid} .gmPhantom_LoadMore[data-col=\"1\"] {transform:translate(0, {$settings['thumbsSpacing']}px);margin-bottom:{$settings['thumbsSpacing']}px;}";
}
if(isset($settings['thumbPadding'])){
    $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LoadMore {padding:{$allsettings['thumbPadding']}px;}
{$cssid} .gmPhantom_LabelBottom .gmPhantom_ThumbContainer {padding-bottom:36px;}
{$cssid} .gmPhantom_LabelBottom .gmPhantom_LoadMore {padding-top:36px;}
{$cssid} .gmPhantom_LabelBottom .gmPhantom_LoadMore[data-col=\"1\"] {padding-top:{$allsettings['thumbPadding']}px;}";
}
if(isset($settings['thumbBG'])){
    if('' == $settings['thumbBG']){
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer {background-color:transparent;}";
    } else{
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LabelBottom .gmPhantom_ThumbLabel {background-color:#{$settings['thumbBG']};}";
    }
}
if(isset($settings['thumbAlpha'])){
    $alpha = $settings['thumbAlpha'] / 100;
    $dcss .= "
{$cssid} .gmPhantom_ThumbContainer .gmPhantom_Thumb {opacity:{$alpha};}";
}
if(isset($settings['thumbAlphaHover'])){
    $alpha = $settings['thumbAlphaHover'] / 100;
    $dcss .= "
{$cssid} .gmPhantom_ThumbContainer:hover .gmPhantom_Thumb {opacity:{$alpha};}";
}
if(isset($settings['thumbBorderSize']) || isset($settings['thumbBorderColor'])){
    if((int)$allsettings['thumbBorderSize']){
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LoadMore {border:{$allsettings['thumbBorderSize']}px solid #{$allsettings['thumbBorderColor']};}";
    } else{
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LoadMore {border:none;}";
    }
}
if(isset($settings['thumbBorderSize'])){
    if((int)$settings['thumbBorderSize'] == 0){
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LoadMore {box-shadow:none;}";
    } else{
        $dcss .= "
{$cssid} .gmPhantom_ThumbContainer,
{$cssid} .gmPhantom_LoadMore {box-shadow:0 0 5px -2px #{$allsettings['thumbBorderColor']};}";
    }
}
if(isset($settings['label8TextColor'])){
    $dcss .= "
{$cssid} .gmPhantom_ThumbLabel {color:#{$allsettings['label8TextColor']};}";
}
if(isset($settings['label8LinkColor'])){
    $dcss .= "
{$cssid} .gmPhantom_ThumbLabel a {color:#{$allsettings['label8LinkColor']};}";
}
if(isset($settings['labelTextColor'])){
    $dcss .= "
{$cssid} .gmPhantom_LabelInside .gmPhantom_ThumbLabel,
{$cssid} .gmPhantom_LabelHover .gmPhantom_ThumbLabel {color:#{$allsettings['labelTextColor']};}";
}
if(isset($settings['labelLinkColor'])){
    $dcss .= "
{$cssid} .gmPhantom_LabelInside .gmPhantom_ThumbLabel a,
{$cssid} .gmPhantom_LabelHover .gmPhantom_ThumbLabel a,
{$cssid} .gmPhantom_LabelInside .gmPhantom_ThumbLabel a:hover,
{$cssid} .gmPhantom_LabelHover .gmPhantom_ThumbLabel a:hover {color:#{$allsettings['labelLinkColor']};}";
}
if((int) $allsettings['bgAlpha'] > 0){
    if(method_exists($gmCore, 'hex2rgb')) {
        $rgb   = implode(',', $gmCore->hex2rgb($allsettings['bgColor']));
        $alpha = $allsettings['bgAlpha'] / 100;
        $dcss .= "
{$cssid} .gmPhantom_Container {background-color:rgba({$rgb},{$alpha});}";
    } else {
        $dcss .= "
{$cssid} .gmPhantom_Container {background-color:#{$allsettings['bgColor']};}";
    }
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
        jQuery(function() {
            var settings = <?php echo json_encode($settings); ?>;
            jQuery('#GmediaGallery_<?php echo $id; ?>').gmPhantom([settings]);
        });
    </script><?php
    if($shortcode_raw){
        echo '</pre>';
    }
}
