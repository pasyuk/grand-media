<?php // don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Tag list item
 *
 * @var $item
 */
?>
<div class="cb_list-item list-group-item term-list-item <?php echo implode(' ', $item->classes); ?>">
    <div class="row cb_term-object" id="tag_<?php echo $item->term_id; ?>">
        <div class="term_id">#<?php echo $item->term_id; ?></div>
        <div class="col-xs-6 term-label">
            <div class="checkbox">
                <input name="doaction[]" type="checkbox"<?php echo $item->selected? ' checked="checked"' : ''; ?> value="<?php echo $item->term_id; ?>"/>
                <?php if($item->allow_edit) { ?>
                    <a class="edit_tag_link" href="#tag_<?php echo $item->term_id; ?>"><?php echo esc_html($item->name); ?></a>
                    <span class="edit_tag_form" style="display:none;"><input class="edit_tag_input" type="text" data-tag_id="<?php echo $item->term_id; ?>" name="gmedia_tag_name[<?php echo $item->term_id; ?>]" value="<?php echo esc_attr($item->name); ?>" placeholder="<?php echo esc_attr($item->name); ?>"/><a href="#tag_<?php echo $item->term_id; ?>" class="edit_tag_save btn btn-link glyphicon glyphicon-pencil"></a></span>
                <?php } else { ?>
                    <span><?php echo esc_html($item->name); ?></span>
                <?php } ?>

                <div class="object-actions">
                    <?php $action_links = gmedia_term_item_actions($item);
                    echo implode('', $action_links);
                    ?>
                </div>
            </div>
        </div>
        <div class="col-xs-6 term-images">
            <?php gmedia_term_item_thumbnails($item); ?>
        </div>
    </div>
</div>
