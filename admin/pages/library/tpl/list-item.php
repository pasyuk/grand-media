<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Gmedia Item for List View in Library
 *
 * @var $gmCore
 * @var $item
 */
?>
<div class="cb_list-item list-group-item d-row clearfix <?php echo implode(' ', $item->classes); ?>" id="list-item-<?php echo $item->ID; ?>" data-id="<?php echo $item->ID; ?>" data-type="<?php echo $item->type; ?>">
    <div class="gmedia_id">#<?php echo $item->ID; ?></div>
    <div class="col-sm-4" style="max-width:340px;">
        <div class="thumbwrap">
            <div class="cb_media-object">
                <span data-clicktarget="gmdataedit<?php echo $item->ID; ?>" class="thumbnail">
                    <?php echo gmedia_item_thumbnail($item); ?>
                </span>
            </div>
            <label class="gm-item-check"><input name="doaction[]" type="checkbox"<?php echo $item->selected? ' checked="checked"' : ''; ?> data-type="<?php echo $item->type; ?>" value="<?php echo $item->ID; ?>"/></label>
            <label class="gm-stack hidden"><input name="stack[]" type="checkbox"<?php echo $item->in_stack? ' checked="checked"' : ''; ?> data-type="<?php echo $item->type; ?>" value="<?php echo $item->ID; ?>"/></label>
        </div>
        <?php
        if('audio' == $item->type) {
            echo gmedia_waveform_player($item);
        }
        ?>
        <div class="related-media-previews">
		    <?php
		    $related_ids = isset( $item->meta['_related'][0] ) ? $item->meta['_related'][0] : array();
		    if(!empty($related_ids)){
			    $related_media = $gmDB->get_gmedias(array('gmedia__in' => $related_ids, 'orderby' => 'gmedia__in'));
			    foreach($related_media as $r_item){
				    ?><p class="thumbnail gmedia-related-image"><span class="image-wrapper"><?php echo gmedia_item_thumbnail( $r_item ); ?></span></p><?php
			    }
		    }
		    ?>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="row" style="margin:0;">
            <div class="col-lg-6">
                <p class="media-title"><?php echo esc_html($item->title); ?>&nbsp;</p>

                <div class="in-library media-caption"><?php echo nl2br(esc_html($item->description)); ?></div>

                <p class="media-meta">
                    <span class="label label-default"><?php _e('Author', 'grand-media'); ?>:</span> <?php printf('<a class="gmedia-author" href="%s">%s</a>', esc_url(add_query_arg(array('author' => $item->author), $gmedia_url)), get_user_option('display_name', $item->author)); ?>
                    <br/><span class="label label-default"><?php _e('Album', 'grand-media'); ?>:</span>
                    <?php
                    if($item->album) {
                        $terms_album = array();
                        foreach($item->album as $c) {
                            $terms_album[] = sprintf('<a class="album" href="%s">%s</a>', esc_url(add_query_arg(array('album__in' => $c->term_id), $gmedia_url)), esc_html($c->name));
                        }
                        $terms_album = join(', ', $terms_album);
                    } else {
                        $terms_album = sprintf('<a class="album" href="%s">%s</a>', esc_url(add_query_arg(array('album__in' => 0), $gmedia_url)), '&#8212;');
                    }
                    echo $terms_album;
                    ?>
                    <br/><span class="label label-default"><?php _e('Category', 'grand-media'); ?>:</span>
                    <?php
                    if($item->categories) {
                        $terms_category = array();
                        foreach($item->categories as $c) {
                            $terms_category[] = sprintf('<a class="category" href="%s">%s</a>', esc_url(add_query_arg(array('category__in' => $c->term_id), $gmedia_url)), esc_html($c->name));
                        }
                        $terms_category = join(', ', $terms_category);
                    } else {
                        $terms_category = sprintf('<a class="category" href="%s">%s</a>', esc_url(add_query_arg(array('category__in' => 0), $gmedia_url)), __('Uncategorized', 'grand-media'));
                    }
                    echo $terms_category;
                    ?>
                    <br/><span class="label label-default"><?php _e('Tags', 'grand-media'); ?>:</span>
                    <?php
                    if($item->tags) {
                        $terms_tag = array();
                        foreach($item->tags as $c) {
                            $terms_tag[] = sprintf('<a class="tag" href="%s">%s</a>', esc_url(add_query_arg(array('tag__in' => $c->term_id), $gmedia_url)), esc_html($c->name));
                        }
                        $terms_tag = join(', ', $terms_tag);
                    } else {
                        $terms_tag = '&#8212;';
                    }
                    echo $terms_tag;
                    ?>
                </p>
            </div>
            <div class="col-lg-6">
                <div class="media-meta gmedia-actions" style="margin:0 0 10px 0;">
                    <?php $media_action_links = gmedia_item_actions($item);
                    echo implode(' | ', $media_action_links);
                    ?>
                </div>
                <?php if(isset($item->post_id)) { ?>
                <p class="media-meta">
                    <span class="label label-default"><?php _e('Comments', 'grand-media'); ?>:</span>
                    <a href="<?php echo add_query_arg(array('page' => 'GrandMedia', 'gmediablank' => 'comments', 'gmedia_id' => $item->ID), $gmProcessor->url); ?>" data-target="#previewModal" data-width="900" data-height="500" class="preview-modal gmpost-com-count" title="<?php esc_attr_e('Comments', 'grand-media'); ?>">
                        <b class="comment-count"><?php echo $item->comment_count; ?></b>
                        <span class="glyphicon glyphicon-comment"></span>
                    </a>
                </p>
                <?php } ?>
                <p class="media-meta">
                    <span class="label label-default"><?php _e('Views / Likes', 'grand-media'); ?>:</span>
                    <?php echo (isset($item->meta['views'][0])? $item->meta['views'][0] : '0') . ' / ' . (isset($item->meta['likes'][0])? $item->meta['likes'][0] : '0'); ?>

                    <?php if(isset($item->meta['_rating'][0])) {
                        $ratings = maybe_unserialize($item->meta['_rating'][0]); ?>
                        <br/><span class="label label-default"><?php _e('Rating', 'grand-media'); ?>:</span> <?php echo $ratings['value'] . ' / ' . $ratings['votes']; ?>
                    <?php } ?>
                    <br/><span class="label label-default"><?php _e('Status', 'grand-media'); ?>:</span> <?php echo $item->status; ?>
                    <br/><span class="label label-default"><?php _e('Link', 'grand-media'); ?>:</span>
                    <?php if(!empty($item->link)) { ?>
                        <a href="<?php echo $item->link; ?>"><?php echo $item->link; ?></a>
                        <?php
                    } else {
                        echo '&#8212;';
                    } ?>
                    <?php if(!empty($item->gps)) { ?>
                        <br/><span class="label label-default"><?php _e('GPS Location', 'grand-media'); ?>:</span> <?php echo $item->gps; ?>
                    <?php } ?>
                </p>
                <p class="media-meta">
                    <span class="label label-default"><?php _e('Type', 'grand-media'); ?>:</span> <?php echo $item->mime_type; ?>
                    <?php if(('image' == $item->type) && $item->editor && !empty($item->meta['_metadata'])) {
                        ?>
                        <br/><span class="label label-default"><?php _e('Dimensions', 'grand-media'); ?>:</span>
                    <?php
                    $is_file_original = (bool)$item->path_original;
                    if($is_file_original){ ?>
                        <a href="<?php echo $item->url_original; ?>"
                           data-target="#previewModal"
                           data-width="<?php echo $item->meta['_metadata'][0]['original']['width']; ?>"
                           data-height="<?php echo $item->meta['_metadata'][0]['original']['height']; ?>"
                           class="preview-modal"
                           title="<?php _e('Original', 'grand-media'); ?>"><?php echo $item->meta['_metadata'][0]['original']['width'] . '×' . $item->meta['_metadata'][0]['original']['height']; ?></a>,
                    <?php } else{ ?>
                        <span title="<?php _e('Original', 'grand-media'); ?>"><?php echo $item->meta['_metadata'][0]['original']['width'] . '×' . $item->meta['_metadata'][0]['original']['height']; ?></span>,
                    <?php } ?>
                        <a href="<?php echo $item->url; ?>"
                           data-target="#previewModal"
                           data-width="<?php echo $item->meta['_metadata'][0]['web']['width']; ?>"
                           data-height="<?php echo $item->meta['_metadata'][0]['web']['height']; ?>"
                           class="preview-modal"
                           title="<?php _e('Webimage', 'grand-media'); ?>"><?php echo $item->meta['_metadata'][0]['web']['width'] . '×' . $item->meta['_metadata'][0]['web']['height']; ?></a>,
                        <a href="<?php echo $item->url_thumb; ?>"
                           data-target="#previewModal"
                           data-width="<?php echo $item->meta['_metadata'][0]['thumb']['width']; ?>"
                           data-height="<?php echo $item->meta['_metadata'][0]['thumb']['height']; ?>"
                           class="preview-modal"
                           title="<?php _e('Thumbnail', 'grand-media'); ?>"><?php echo $item->meta['_metadata'][0]['thumb']['width'] . '×' . $item->meta['_metadata'][0]['thumb']['height']; ?></a>
                    <?php } ?>
                    <br/><span class="label label-default"><?php _e('Filename', 'grand-media'); ?>:</span> <a href="<?php echo $item->url; ?>" download="<?php echo $item->gmuid; ?>"><?php echo $item->gmuid; ?></a>
                    <?php if(!empty($item->meta['_created_timestamp'][0])) { ?>
                        <br/><span class="label label-default"><?php _e('Created', 'grand-media') ?>:</span> <?php echo date('Y-m-d H:i:s ', $item->meta['_created_timestamp'][0]); ?>
                    <?php } ?>
                    <br/><span class="label label-default"><?php _e('Uploaded', 'grand-media') ?>:</span> <?php echo $item->date; ?>
                    <br/><span class="label label-default"><?php _e('Last Edited', 'grand-media') ?>:</span> <span class="gm-last-edited modified"><?php echo $item->modified; ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

