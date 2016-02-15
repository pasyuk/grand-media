<?php
/**
 * Category list item
 *
 * @var $item
 */
?>
<div class="list-group-item term-list-item">
    <div class="row cb_term-object">
        <div class="term_id">#<?php echo $item->term_id; ?></div>
        <div class="col-xs-6 term-label" style="padding-top:10px; padding-bottom:10px;">
            <span class="term_name"><?php echo esc_html($item->name); ?></span>
            <div class="object-actions">
                <?php $action_links = gmedia_term_item_actions($item);
                echo implode('', $action_links);
                ?>
            </div>
        </div>
        <div class="col-xs-6">
            <?php gmedia_term_item_thumbnails($item); ?>
        </div>
    </div>
</div>
