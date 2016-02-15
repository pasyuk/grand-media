<?php
/**
 * Modules functions
 */

function gmedia_get_modules() {
    global $gmCore, $gmGallery;

    $modules = array();
    $modules['in'] = array(
        'phantom' => ''
        , 'phototravlr' => ''
        , 'realslider' => ''
        , 'mosaic' => ''
        , 'photobox' => ''
        , 'photomania' => ''
        , 'jq-mplayer' => ''
        , 'wp-videoplayer' => ''
        , 'photo-pro' => ''
        , 'optima' => ''
        , 'afflux' => ''
        , 'slider' => ''
        , 'green-style' => ''
        , 'photo-blog' => ''
        , 'minima' => ''
        , 'sphere' => ''
        , 'cube' => ''
        , 'flatwall' => ''
    );
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

    $get_xml = wp_remote_get($gmGallery->options['modules_xml'], array('sslverify' => true));
    if(!is_wp_error($get_xml) && (200 == $get_xml['response']['code'])) {
        $xml = @simplexml_load_string($get_xml['body']);
        if(!empty($xml)) {
            foreach($xml as $m) {
                $name                  = (string)$m->name;
                $modules['xml'][$name] = get_object_vars($m);
                $modules['xml'][$name]['place'] = 'remote';
                if(isset($modules['in'][$name]) && !empty($modules['in'][$name])) {
                    $modules['in'][$name] = array_merge($modules['in'][$name], get_object_vars($m));
                    if(version_compare((float)$modules['xml'][$name]['version'], (float)$modules['in'][$name]['version'], '>')) {
                        $modules['in'][$name]['update'] = true;
                        $modules['out'][$name] = $modules['xml'][$name];
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

    return $modules;
}

function gmedia_module_action_buttons($module) {
    global $gmCore, $gmProcessor;

    $buttons = array();
    if('remote' == $module['place']) {
        $buttons['install'] = '<a class="btn btn-primary ' . (gm_user_can('module_manage')? 'module_install' : 'disabled') . '" data-module="' . $module['name'] . '" data-loading-text="' . __('Loading...', 'grand-media') . '" href="' . esc_url($module['download']) . '">' . __('Install Module', 'grand-media') . '</a>';
    } else {
        $buttons['create'] = '<a class="btn btn-success" href="' . $gmCore->get_admin_url(array('page' => 'GrandMedia_Galleries', 'gallery_module' => $module['module_name']), array(), true) . '">' . __('Create Gallery', 'grand-media') . '</a>';
    }
    if(!empty($module['demo']) && $module['demo'] != '#') {
        $buttons['demo'] = '<a class="btn btn-default" target="_blank" href="' . $module['demo'] . '">' . __('View Demo', 'grand-media') . '</a>';
    }
    if($module['update'] && 'remote' != $module['place']) {
        $buttons['update'] = '<a class="btn btn-warning module_install" data-module="' . $module['module_name'] . '" data-loading-text="' . __('Loading...', 'grand-media') . '" href="' . esc_url($module['download']) . '">' . __('Update Module', 'grand-media') . " (v{$module['version']})</a>";
    }
    if(('upload' == $module['place']) && gm_user_can('module_manage')) {
        $buttons['delete'] = '<a class="btn btn-danger" href="' . wp_nonce_url($gmCore->get_admin_url(array('delete_module' => $module['module_name']), array(), $gmProcessor->url), 'gmedia_module_delete') . '">' . __('Delete Module', 'grand-media') . '</a>';
    }
    if(!empty($module['download'])) {
        $buttons['download'] = '<a class="btn btn-link" href="' . $module['download'] . '" download="true">' . __('Download module ZIP', 'grand-media') . '</a>';
    }

    return $buttons;
}