<?php
// don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Module List Item
 */

global $gmDB, $gmCore, $user_ID;
?>
<div class="media<?php echo $module['mclass']; ?>">
    <div class="row">
        <div class="col-sm-3">
            <div class="thumbnail">
                <img class="media-object" src="<?php echo $module['screenshot_url']; ?>" alt="<?php esc_attr_e($module['title']); ?>" width="320" height="240"/>
            </div>
        </div>
        <div class="<?php echo(($module['place'] === 'remote')? 'col-sm-9' : 'col-sm-5'); ?>">
            <h4 class="media-heading"><?php echo $module['title']; ?></h4>

            <p class="version"><?php echo __('Version', 'grand-media') . ': ' . $module['version']; ?></p>
            <?php if(isset($module['info'])){ ?>
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
        <?php
        if($module['place'] !== 'remote'){
            ?>
            <div class="col-sm-4">
                <div id="module_presets_list" class="module_presets module_presets_<?php echo $module['name'] ?>">
                    <h4 class="media-heading" style="margin-bottom:10px;">
                        <a href="<?php echo $gmCore->get_admin_url(array('page' => 'GrandMedia_Modules', 'preset_module' => $module['name']), array(), admin_url('admin.php')); ?>" class="addpreset pull-right"><span class="label label-success">+</span></a>
                        <?php _e('Presets', 'grand-media'); ?></h4>
                    <?php
                    $presets = $gmDB->get_terms('gmedia_module', array('status' => $module['name']));
                    if(!empty($presets)){
                        ?>
                        <ul class="list-group presetlist">
                            <?php
                            $li = array();
                            foreach($presets as $preset){
                                $href = $gmCore->get_admin_url(array('page' => 'GrandMedia_Modules', 'preset' => $preset->term_id), array(), admin_url('admin.php'));

                                $count         = 1;
                                $name          = trim(str_replace('[' . $module['name'] . ']', '', $preset->name, $count));
                                $by            = '';
                                $global_preset = false;
                                if(!$name){
                                    if((int)$preset->global){
                                        $name = __('Default Settings', 'grand-media');
                                    } else{
                                        $name          = __('Global Settings', 'grand-media');
                                        $global_preset = true;
                                    }
                                }
                                if((int)$preset->global){
                                    $by = ' <small style="white-space:nowrap">[' . get_the_author_meta('display_name', $preset->global) . ']</small>';
                                }
                                $li_item = '
                                <li class="list-group-item" id="gm-preset-' . $preset->term_id . '">
                                    <span class="gm-preset-id">ID: ' . $preset->term_id . '</span>';
                                if($user_ID == $preset->global || $gmCore->caps['gmedia_edit_others_media']){
                                    $li_item .= '<span class="delpreset"><span class="label label-danger" data-id="' . $preset->term_id . '">&times;</span></span>';
                                }
                                $li_item .= '
                                    <a href="' . $href . '">' . $name . $by . '</a>
                                </li>';
                                if($global_preset){
                                    if(current_user_can('manage_options')){
                                        array_unshift($li, $li_item);
                                    }
                                } else{
                                    $li[] = $li_item;
                                }
                            }
                            echo implode('', $li);
                            ?>
                        </ul>
                    <?php } ?>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</div>
