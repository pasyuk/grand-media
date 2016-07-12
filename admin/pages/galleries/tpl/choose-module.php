<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * @var $gmedia_modules
 */

?>
<div class="modal fade gmedia-modal" id="chooseModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php _e('Choose Module for Gallery'); ?></h4>
            </div>
            <div class="modal-body linkblock">
                <?php
                if (! empty($gmedia_modules['in'])) {
                    foreach ($gmedia_modules['in'] as $m) {
                        /**
                         * @var $module_name
                         * @var $module_url
                         * @var $module_path
                         */
                        extract($m);
                        if (! file_exists($module_path . '/index.php')) {
                            continue;
                        }
                        $module_info = array();
                        /** @noinspection PhpIncludeInspection */
                        include($module_path . '/index.php');
                        if (empty($module_info)) {
                            continue;
                        }
                        $mclass = ' module-' . $module_info['type'] . ' module-' . $module_info['status'];
                        ?>
                        <div data-href="<?php echo add_query_arg(array('gallery_module' => $module_name), $gmedia_url); ?>" class="choose-module media<?php echo $mclass; ?>">
                            <a href="<?php echo add_query_arg(array('gallery_module' => $module_name), $gmedia_url); ?>" class="thumbnail pull-left">
                                <img class="media-object" src="<?php echo $module_url . '/screenshot.png'; ?>" alt="<?php echo esc_attr($module_info['title']); ?>" width="160" height="120"/>
                            </a>

                            <div class="media-body" style="margin-left:180px;">
                                <h4 class="media-heading"><?php echo $module_info['title']; ?></h4>

                                <p class="version"><?php echo __('Version', 'grand-media') . ': ' . $module_info['version']; ?></p>

                                <div class="description"><?php echo nl2br($module_info['description']); ?></div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    _e('No installed modules', 'grand-media');
                }
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Cancel', 'grand-media'); ?></button>
            </div>
        </div>
    </div>
</div>

