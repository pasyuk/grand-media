<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Gmedia Item for Grid View in Library
 */
?>
<div class="cb_list-item gm-item-cell col-xs-6 col-sm-4 col-md-3 col-lg-2 <?php echo implode(' ', $item->classes); ?>" id="list-item-<?php echo $item->ID; ?>" data-id="<?php echo $item->ID; ?>" data-type="<?php echo $item->type; ?>">
    <div class="thumbnail <?php echo ($item->img_ratio >= 1)? 'landscape' : 'portrait'; ?>">
        <div class="cb_media-object">
            <span<?php echo in_array($gmProcessor->mode, array('select_single', 'select_multiple'))? '' : ' data-clicktarget="gmdataedit' . $item->ID . '"'; ?> class="centered">
                <?php echo gmedia_item_thumbnail($item); ?>
            </span>
        </div>
        <label class="gm-item-check">
            <?php if($gmProcessor->mode != 'select_single'){ ?>
                <input name="doaction[]" type="checkbox"<?php echo $item->selected? ' checked="checked"' : ''; ?> data-type="<?php echo $item->type; ?>" value="<?php echo $item->ID; ?>"/>
            <?php } else{ ?>
                <input name="doaction[]" type="radio" data-type="<?php echo $item->type; ?>" value="<?php echo $item->ID; ?>"/>
            <?php } ?>
        </label>
        <label class="gm-stack hidden"><input name="stack[]" type="checkbox"<?php echo $item->in_stack? ' checked="checked"' : ''; ?> data-type="<?php echo $item->type; ?>" value="<?php echo $item->ID; ?>"/></label>
        <div class="gm-cell-more">
            <span class="gm-cell-more-btn glyphicon glyphicon-menu-hamburger"></span>
            <div class="gm-cell-more-content">
                <p class="media-meta"><span class="label label-default"><?php _e('ID', 'grand-media'); ?>:</span> #<?php echo $item->ID; ?>
                    <br/><span class="label label-default"><?php _e('Album', 'grand-media'); ?>:</span>
                    <?php
                    if($item->album){
                        $terms_album = array();
                        foreach($item->album as $c){
                            $terms_album[] = sprintf('<a class="album" href="%s">%s</a>', esc_url(add_query_arg(array('album__in' => $c->term_id), $gmedia_url)), esc_html($c->name));
                        }
                        $terms_album = join(', ', $terms_album);
                    } else{
                        $terms_album = sprintf('<a class="album" href="%s">%s</a>', esc_url(add_query_arg(array('album__in' => 0), $gmedia_url)), '&#8212;');
                    }
                    echo $terms_album;
                    ?>
                    <br/><span class="label label-default"><?php _e('Category', 'grand-media'); ?>:</span>
                    <?php
                    if($item->categories){
                        $terms_category = array();
                        foreach($item->categories as $c){
                            $terms_category[] = sprintf('<a class="category" href="%s">%s</a>', esc_url(add_query_arg(array('category__in' => $c->term_id), $gmedia_url)), esc_html($c->name));
                        }
                        $terms_category = join(', ', $terms_category);
                    } else{
                        $terms_category = sprintf('<a class="category" href="%s">%s</a>', esc_url(add_query_arg(array('category__in' => 0), $gmedia_url)), __('Uncategorized', 'grand-media'));
                    }
                    echo $terms_category;
                    ?>
                    <br/><span class="label label-default"><?php _e('Tags', 'grand-media'); ?>:</span>
                    <?php
                    if($item->tags){
                        $terms_tag = array();
                        foreach($item->tags as $c){
                            $terms_tag[] = sprintf('<a class="tag" href="%s">%s</a>', esc_url(add_query_arg(array('tag__in' => $c->term_id), $gmedia_url)), esc_html($c->name));
                        }
                        $terms_tag = join(', ', $terms_tag);
                    } else{
                        $terms_tag = '&#8212;';
                    }
                    echo $terms_tag;
                    ?>

                    <?php if(isset($item->post_id)){ ?>
                        <br/><span class="label label-default"><?php _e('Comments', 'grand-media'); ?>:</span>
                        <a href="<?php echo add_query_arg(array('page' => 'GrandMedia', 'gmediablank' => 'comments', 'gmedia_id' => $item->ID), $gmProcessor->url); ?>" data-target="#previewModal" data-width="900" data-height="500" class="preview-modal gmpost-com-count" title="<?php esc_attr_e('Comments', 'grand-media'); ?>">
                            <b class="comment-count"><?php echo $item->comment_count; ?></b>
                            <span class="glyphicon glyphicon-comment"></span>
                        </a>
                    <?php } ?>
                    <br/><span class="label label-default"><?php _e('Views / Likes', 'grand-media'); ?>:</span>
                    <?php echo (isset($item->meta['views'][0])? $item->meta['views'][0] : '0') . ' / ' . (isset($item->meta['likes'][0])? $item->meta['likes'][0] : '0'); ?>

                    <?php if(isset($item->meta['_rating'][0])){
                        $ratings = maybe_unserialize($item->meta['_rating'][0]); ?>
                        <br/><span class="label label-default"><?php _e('Rating', 'grand-media'); ?>:</span> <?php echo $ratings['value'] . ' / ' . $ratings['votes']; ?>
                    <?php } ?>
                </p>
            </div>
        </div>
    </div>
    <div class="gm-cell-bottom">
        <div class="gm-cell-title"><span><?php echo esc_html($item->title); ?>&nbsp;</span></div>
        <div class="gmedia-actions">
            <?php $media_action_links = gmedia_item_actions($item);
            echo implode(' ', $media_action_links);
            ?>
        </div>
    </div>
</div>


