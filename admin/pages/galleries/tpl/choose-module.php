<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * @var $gmedia_modules
 * @var $gmedia_url
 */

global $gmCore, $gmDB, $gmGallery;
?>
<div class="modal fade gmedia-modal" id="chooseModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php _e('Choose Module for Gallery', 'grand-media'); ?></h4>
            </div>
            <div class="modal-body linkblock">
                <?php
                if(!empty($gmedia_modules['in'])){
                    foreach($gmedia_modules['in'] as $m){
                        /**
                         * @var $module_name
                         * @var $module_url
                         * @var $module_path
                         */
                        extract($m);
                        if(!is_file($module_path . '/index.php')){
                            continue;
                        }
                        $module_info = array();
                        /** @noinspection PhpIncludeInspection */
                        include($module_path . '/index.php');
                        if(empty($module_info)){
                            continue;
                        }
                        $mclass = ' module-' . $module_info['type'] . ' module-' . $module_info['status'];
                        ?>
                        <div class="choose-module media<?php echo $mclass; ?>">
                            <a class="thumbnail pull-left" role="button" data-toggle="collapse" href="#collapseDescr_<?php echo $module_name; ?>" aria-expanded="false" aria-controls="collapseDescr_<?php echo $module_name; ?>">
                                <img class="media-object" src="<?php echo $module_url . '/screenshot.png'; ?>" alt="<?php esc_attr_e($module_info['title']); ?>" width="100"/>
                            </a>

                            <div class="media-body" style="margin-left:180px;">
                                <h4 class="media-heading"><?php echo $module_info['title']; ?></h4>
                                <p class="version" style="margin: 6px 0;"><?php echo __('Version', 'grand-media') . ': ' . $module_info['version']; ?></p>
                                <div class="description collapse" id="collapseDescr_<?php echo $module_name; ?>"><?php echo nl2br($module_info['description']); ?></div>
                                <div class="action-buttons text-right">
                                    <a href="<?php echo $gmCore->get_admin_url(array('page' => 'GrandMedia_Modules', 'preset_module' => $module_name), array(), admin_url('admin.php')); ?>" class="btn btn-default"><?php _e('Create Preset', 'grand-media'); ?></a>
                                    &nbsp;&nbsp;&nbsp;
                                    <a href="<?php echo add_query_arg(array('gallery_module' => $module_name), $gmedia_url); ?>" class="btn btn-primary"><?php _e('Create Gallery', 'grand-media'); ?></a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else{
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

<div class="modal fade gmedia-modal" id="changeModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" autocomplete="off" method="post" action="<?php echo $gmCore->get_admin_url(array(), array(), $gmedia_url); ?>">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php _e('Change Module/Preset for Galleries', 'grand-media'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                if(!empty($gmedia_modules['in'])){
                    ?>
                    <div class="form-group">
                        <label><?php _e('Change Module/Preset for Galleries', 'grand-media') ?>:</label>
                        <select class="form-control input-sm" name="gmedia_gallery_module">
                            <?php
                            echo '<option value="">' . __('Choose Module/Preset') . '</option>';
                            foreach($gmedia_modules['in'] as $mfold => $module){
                                echo '<optgroup label="' . esc_attr($module['title']) . '">';
                                $presets  = $gmDB->get_terms('gmedia_module', array('status' => $mfold));
                                $option   = array();
                                $option[] = '<option value="' . esc_attr($mfold) . '">' . $module['title'] . ' - ' . __('Default Settings') . '</option>';
                                foreach($presets as $preset){
                                    if(!(int)$preset->global && '[' . $mfold . ']' === $preset->name){
                                        continue;
                                    }
                                    $by_author = '';
                                    if((int)$preset->global){
                                        $by_author = ' [' . get_the_author_meta('display_name', $preset->global) . ']';
                                    }
                                    if('[' . $mfold . ']' === $preset->name){
                                        $option[] = '<option value="' . $preset->term_id . '">' . $module['title'] . $by_author . ' - ' . __('Default Settings') . '</option>';
                                    } else{
                                        $preset_name = str_replace('[' . $mfold . '] ', '', $preset->name);
                                        $option[]    = '<option value="' . $preset->term_id . '">' . $module['title'] . $by_author . ' - ' . $preset_name . '</option>';
                                    }
                                }
                                echo implode('', $option);
                                echo '</optgroup>';
                            } ?>
                        </select>

                        <p class="help-block"><?php _e('Chosen module will be applied for selected galleries.', 'grand-media'); ?></p>
                    </div>
                    <?php
                    wp_nonce_field( 'gmedia_gallery_module', '_wpnonce_gallery_module' );
                    wp_referer_field();
                } else{
                    _e('No installed modules', 'grand-media');
                }
                ?>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><?php _e( 'Apply', 'grand-media' ); ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Cancel', 'grand-media'); ?></button>
            </div>
        </form>
    </div>
</div>