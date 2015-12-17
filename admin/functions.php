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
