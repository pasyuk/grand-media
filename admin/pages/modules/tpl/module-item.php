<?php
/**
 * Module List Item
 */
?>
<div class="media<?php echo $module['mclass']; ?>">
    <div class="thumbnail pull-left">
        <img class="media-object" src="<?php echo $module['screenshot_url']; ?>" alt="<?php echo esc_attr($module['title']); ?>" width="320" height="240"/>
    </div>
    <div class="media-body" style="margin-left:340px;">
        <h4 class="media-heading"><?php echo $module['title']; ?></h4>

        <p class="version"><?php echo __('Version', 'grand-media') . ': ' . $module['version']; ?></p>
        <?php if(isset($module['info'])) { ?>
            <div class="module_info"><?php echo str_replace("\n", '<br />', (string)$module['info']); ?></div>
        <?php } ?>
        <div class="description"><?php echo str_replace("\n", '<br />', (string)$module['description']); ?></div>
        <hr/>
        <p class="buttons">
            <?php
            $buttons = gmedia_module_action_buttons($module);
            echo implode(' ', $buttons);
            ?>
        </p>
    </div>
</div>
