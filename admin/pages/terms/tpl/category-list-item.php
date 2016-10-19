<?php // don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Category list item
 *
 * @var $item
 */
?>
<div class="cb_list-item list-group-item term-list-item <?php echo implode(' ', $item->classes); ?>">
    <div class="row cb_object">
        <div class="col-xs-6 term-label">
            <div class="checkbox">
                <input name="doaction[]" type="checkbox"<?php echo $item->selected? ' checked="checked"' : ''; ?> value="<?php echo $item->term_id; ?>"/>
                <?php if($item->allow_edit) { ?>
                    <a class="term_name" href="<?php echo add_query_arg(array('edit_term' => $item->term_id), $gmedia_url); ?>"><?php echo esc_html($item->name); ?></a>
                <?php } else { ?>
                    <span class="term_name"><?php echo esc_html($item->name); ?></span>
                <?php } ?>
                <br/><span class="term_id">ID: <?php echo $item->term_id; ?></span>

                <div class="object-actions">
                    <?php $action_links = gmedia_term_item_actions($item);
                    echo $action_links['share'];
                    echo '<br/>' . $action_links['filter'] . $action_links['delete'];
                    ?>
                </div>
            </div>
        </div>
        <div class="col-xs-6">
            <?php gmedia_term_item_thumbnails($item); ?>
        </div>
    </div>
</div>