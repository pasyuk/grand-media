<?php

function gm_get_admin_url($add_args = array(), $remove_args = array(), $uri = false) {
    global $gmCore;

    return $gmCore->get_admin_url($add_args, $remove_args, $uri);
}

function gm_panel_classes($classes) {
    echo implode(' ', (array)$classes);
}

function gm_user_can($capability) {
    global $gmCore;

    return isset($gmCore->caps['gmedia_' . $capability])? $gmCore->caps['gmedia_' . $capability] : false;
}

function gmedia_term_choose_author_field($selected = false) {
    global $gmCore;

    $user_ID = get_current_user_id();
    if(false === $selected) {
        $selected = $user_ID;
    }

    $user_ids = gm_user_can('delete_others_media')? $gmCore->get_editable_user_ids() : array($user_ID);
    if($user_ids && gm_user_can('edit_others_media')) {
        if(!in_array($user_ID, $user_ids)) {
            array_push($user_ids, $user_ID);
        }
        wp_dropdown_users(array(
                              'include'          => $user_ids,
                              'include_selected' => true,
                              'name'             => 'term[global]',
                              'selected'         => $selected,
                              'class'            => 'form-control input-sm',
                              'multi'            => true,
                              'show_option_all'  => __('Shared', 'grand-media')
                          ));
    } else {
        echo '<input type="hidden" name="term[global]" value="' . $user_ID . '"/>';
        echo '<div>' . get_the_author_meta('display_name', $user_ID) . '</div>';
    }
}

/** Get available modules
 * @param bool|true $including_remote
 *
 * @return array
 */
function get_gmedia_modules($including_remote = true) {
    global $gmCore, $gmGallery;

    $modules       = array();
    $modules['in'] = $gmCore->modules_order();
    if(($plugin_modules = glob(GMEDIA_ABSPATH . 'module/*', GLOB_ONLYDIR | GLOB_NOSORT))) {
        foreach($plugin_modules as $path) {
            $mfold                 = basename($path);
            $modules['in'][$mfold] = array(
                'place'       => 'plugin',
                'module_name' => $mfold,
                'module_url'  => $gmCore->gmedia_url . "/module/{$mfold}",
                'module_path' => $path
            );
        }
    }
    if(($upload_modules = glob($gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/*', GLOB_ONLYDIR | GLOB_NOSORT))) {
        foreach($upload_modules as $path) {
            $mfold                 = basename($path);
            $modules['in'][$mfold] = array(
                'place'       => 'upload',
                'module_name' => $mfold,
                'module_url'  => $gmCore->upload['url'] . "/{$gmGallery->options['folder']['module']}/{$mfold}",
                'module_path' => $path
            );
        }
    }

    $modules['in'] = array_filter($modules['in']);

    if(isset($modules['in']) && !empty($modules['in'])) {
        foreach($modules['in'] as $mfold => $module) {
            // todo: get broken modules folders and delete them
            if(!file_exists($module['module_path'] . '/index.php')) {
                unset($modules['in'][$mfold]);
                continue;
            }
            $module_info = array();
            include($module['module_path'] . '/index.php');
            if(empty($module_info)) {
                unset($modules['in'][$mfold]);
                continue;
            }
            $modules['in'][$mfold]           = array_merge($module, (array)$module_info);
            $modules['in'][$mfold]['update'] = false;
        }
    }

    if($including_remote) {
        $get_xml = wp_remote_get($gmGallery->options['modules_xml'], array('sslverify' => true));
        if(!is_wp_error($get_xml) && (200 == $get_xml['response']['code'])) {
            $xml = @simplexml_load_string($get_xml['body']);
            if(!empty($xml)) {
                foreach($xml as $m) {
                    $name                           = (string)$m->name;
                    $modules['xml'][$name]          = get_object_vars($m);
                    $modules['xml'][$name]['place'] = 'remote';
                    if(isset($modules['in'][$name]) && !empty($modules['in'][$name])) {
                        $modules['in'][$name] = array_merge(get_object_vars($m), $modules['in'][$name]);
                        if(version_compare((float)$modules['xml'][$name]['version'], (float)$modules['in'][$name]['version'], '>')) {
                            $modules['in'][$name]['update'] = $modules['xml'][$name]['version'];
                            $modules['out'][$name]          = $modules['xml'][$name];
                        }
                    } else {
                        $modules['out'][$name] = $modules['xml'][$name];
                    }
                }
            }
        } else {
            $modules['error'] = array(__('Error loading remote xml...', 'grand-media'));
            if(is_wp_error($get_xml)) {
                $modules['error'][] = $get_xml->get_error_message();
            }
        }
    }

    return $modules;
}
