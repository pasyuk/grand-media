<?php

function gm_user_can($capability){
    global $gmCore;

    return isset($gmCore->caps[ 'gmedia_' . $capability ])? $gmCore->caps[ 'gmedia_' . $capability ] : false;
}

/** Get available modules
 *
 * @param bool|true $including_remote
 *
 * @return array
 */
function get_gmedia_modules($including_remote = true){
    global $gmCore, $gmGallery;

    $modules       = array();
    $modules['in'] = $gmCore->modules_order();
    if(($plugin_modules = glob(GMEDIA_ABSPATH . 'module/*', GLOB_ONLYDIR | GLOB_NOSORT))){
        foreach($plugin_modules as $path){
            $mfold                   = basename($path);
            $modules['in'][ $mfold ] = array('place'       => 'plugin',
                                             'module_name' => $mfold,
                                             'module_url'  => $gmCore->gmedia_url . "/module/{$mfold}",
                                             'module_path' => $path
            );
        }
    }
    if(($upload_modules = glob($gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/*', GLOB_ONLYDIR | GLOB_NOSORT))){
        foreach($upload_modules as $path){
            $mfold                   = basename($path);
            $modules['in'][ $mfold ] = array('place'       => 'upload',
                                             'module_name' => $mfold,
                                             'module_url'  => $gmCore->upload['url'] . "/{$gmGallery->options['folder']['module']}/{$mfold}",
                                             'module_path' => $path
            );
        }
    }

    $modules['in'] = array_filter($modules['in']);

    if(isset($modules['in']) && !empty($modules['in'])){
        foreach($modules['in'] as $mfold => $module){
            // todo: get broken modules folders and delete them
            if(!is_file($module['module_path'] . '/index.php')){
                unset($modules['in'][ $mfold ]);
                continue;
            }
            $module_info = array();
            include($module['module_path'] . '/index.php');
            if(empty($module_info)){
                unset($modules['in'][ $mfold ]);
                continue;
            }
            $modules['in'][ $mfold ]           = array_merge($module, (array)$module_info);
            $modules['in'][ $mfold ]['update'] = false;
        }
    }

    if($including_remote){
        $get_xml = wp_remote_get($gmGallery->options['modules_xml'], array('sslverify' => true));
        if(!is_wp_error($get_xml) && (200 == $get_xml['response']['code'])){
            $xml = @simplexml_load_string($get_xml['body']);
            if(!empty($xml)){
                foreach($xml as $m){
                    $name                             = (string)$m->name;
                    $modules['xml'][ $name ]          = get_object_vars($m);
                    $modules['xml'][ $name ]['place'] = 'remote';
                    if(isset($modules['in'][ $name ]) && !empty($modules['in'][ $name ])){
                        $modules['in'][ $name ] = array_merge(get_object_vars($m), $modules['in'][ $name ]);
                        if(version_compare($modules['xml'][ $name ]['version'], $modules['in'][ $name ]['version'], '>')){
                            $modules['in'][ $name ]['update'] = $modules['xml'][ $name ]['version'];
                            $modules['out'][ $name ]          = $modules['xml'][ $name ];
                        }
                    } else{
                        $modules['out'][ $name ] = $modules['xml'][ $name ];
                    }
                }
            }
        } else{
            $modules['error'] = array(__('Error loading remote xml...', 'grand-media'));
            if(is_wp_error($get_xml)){
                $modules['error'][] = $get_xml->get_error_message();
            }
        }
    }

    return $modules;
}

/** More data for Gmedia Item
 *
 * @param $item
 */
function gmedia_item_more_data(&$item){
    global $gmDB, $gmCore, $gmGallery;

    $meta     = $gmDB->get_metadata('gmedia', $item->ID);
    $metadata = isset($meta['_metadata'][0])? $meta['_metadata'][0] : array();

    $item->meta = $meta;

    $type       = explode('/', $item->mime_type);
    $item->type = $type[0];
    $item->ext  = pathinfo($item->gmuid, PATHINFO_EXTENSION);

    $item->url  = $gmCore->upload['url'] . '/' . $gmGallery->options['folder'][ $type[0] ] . '/' . $item->gmuid;
    $item->path = $gmCore->upload['path'] . '/' . $gmGallery->options['folder'][ $type[0] ] . '/' . $item->gmuid;

    $item->editor = (('image' == $item->type) && in_array($type[1], array('jpeg', 'png', 'gif')))? true : false;
    $item->gps    = '';
    if('image' == $item->type){
        $item->path_thumb    = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_thumb'] . '/' . $item->gmuid;
        $item->path_web      = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image'] . '/' . $item->gmuid;
        $item->path_original = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_original'] . '/' . $item->gmuid;
        $item->url_thumb     = $gmCore->upload['url'] . '/' . $gmGallery->options['folder']['image_thumb'] . '/' . $item->gmuid;
        $item->url_web       = $gmCore->upload['url'] . '/' . $gmGallery->options['folder']['image'] . '/' . $item->gmuid;
        $item->url_original  = $gmCore->upload['url'] . '/' . $gmGallery->options['folder']['image_original'] . '/' . $item->gmuid;
        if(!is_file($item->path_original)){
            $item->path_original = null;
            $item->url_original = $item->url_web;
        }
        if(!empty($metadata['image_meta']['GPS'])){
            $item->gps = implode(', ', $metadata['image_meta']['GPS']);
        }
    } else{
        $cover              = $gmCore->gm_get_media_image($item, 'all');
        $item->url_thumb    = $cover['thumb'];
        $item->url_web      = $cover['web'];
        $item->url_original = $cover['original'];
    }

    $item->alttext = !empty($meta['_image_alt'][0])? $meta['_image_alt'][0] : $item->title;

    if(!empty($meta['_gps'][0])){
        $item->gps = implode(', ', $meta['_gps'][0]);
    }

    $item->img_width    = isset($metadata['web']['width'])? (int)$metadata['web']['width'] : 300;
    $item->img_height   = isset($metadata['web']['height'])? (int)$metadata['web']['height'] : 300;
    $item->thumb_width  = isset($metadata['thumb']['width'])? (int)$metadata['thumb']['width'] : 300;
    $item->thumb_height = isset($metadata['thumb']['height'])? (int)$metadata['thumb']['height'] : 300;
    if(isset($item->meta['_cover'][0]) && !empty($item->meta['_cover'][0])){
        $cover_metadata = $gmDB->get_metadata('gmedia', $item->meta['_cover'][0], '_metadata', true);
        if(isset($cover_metadata['thumb']['width']) && isset($cover_metadata['thumb']['height'])){
            $item->img_width    = (int)$cover_metadata['web']['width'];
            $item->img_height   = (int)$cover_metadata['web']['height'];
            $item->thumb_width  = (int)$cover_metadata['thumb']['width'];
            $item->thumb_height = (int)$cover_metadata['thumb']['height'];
        }
    }
    $item->img_ratio = $item->img_width / $item->img_height;

    $item->msize['width']  = max($item->img_width, (isset($metadata['width'])? (int)$metadata['width'] : 640));
    $item->msize['height'] = max($item->img_height, (isset($metadata['height'])? (int)$metadata['height'] : 300));

    $item->tags = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_tag');
    if($item->tags){
        usort($item->tags, 'gmterms_usort');
    }
    $item->album      = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_album');
    $item->categories = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_category');

    $item = apply_filters('gmedia_item_more_data', $item);
}

/** Sort terms objects by term_order
 *
 * @param $a
 * @param $b
 *
 * @return mixed
 */
function gmterms_usort($a, $b){
    return $a->term_order - $b->term_order;
}

/** More data for Gmedia Term Item
 *
 * @param $item
 */
function gmedia_term_item_more_data(&$item){
    global $gmDB, $gmCore;

    $meta       = $gmDB->get_metadata('gmedia_term', $item->term_id);
    $item->meta = $meta;

    if($item->global){
        $item->author_name = get_the_author_meta('display_name', $item->global);
    } else{
        $item->author_name = false;
    }

    $item->taxterm = str_replace('gmedia_', '', $item->taxonomy);
    if('gmedia_album' == $item->taxonomy){
        $post_id       = isset($meta['_post_ID'][0])? (int)$meta['_post_ID'][0] : 0;
        $item->post_id = $post_id;
        if($post_id){
            $post_item = get_post($post_id);
            if($post_item){
                $item->slug           = $post_item->post_name;
                $item->post_password  = $post_item->post_password;
                $item->comment_count  = $post_item->comment_count;
                $item->comment_status = $post_item->comment_status;
            }
        }
    }

    $item->cloud_link = $gmCore->gmcloudlink($item->term_id, $item->taxterm);
    if(!empty($item->meta['_post_ID'][0])){
        $item->post_link = get_permalink($item->meta['_post_ID'][0]);
    } else{
        $item->post_link = '';
    }

    if(is_user_logged_in()){
        $allow_terms_delete = gm_user_can('terms_delete');
        if($item->global){
            if((int)$item->global === get_current_user_id()){
                $item->allow_edit   = gm_user_can("{$item->taxterm}_manage");
                $item->allow_delete = $allow_terms_delete;
            } else{
                $item->allow_edit   = gm_user_can('edit_others_media');
                $item->allow_delete = ($item->allow_edit && $allow_terms_delete);
            }
        } else{
            $item->allow_edit   = gm_user_can('edit_others_media');
            $item->allow_delete = ($item->allow_edit && $allow_terms_delete);
        }
    } else{
        $item->allow_edit   = false;
        $item->allow_delete = false;
    }

    $item = apply_filters('gmedia_term_item_more_data', $item);
}

/** More data for Gmedia Gallery Item
 *
 * @param $item
 */
function gmedia_gallery_more_data(&$item){
    global $gmDB, $gmCore, $user_ID;

    $item->custom            = array();
    $item->meta              = array('_edited' => '&#8212;',
                                     '_query'  => array(),
                                     '_module' => $gmCore->_get('gallery_module', 'phantom')
    );
    $item->meta['_settings'] = array($item->meta['_module'] => array());

    $item->allow_edit   = false;
    $item->allow_delete = false;

    if(empty($item->term_id)){
        $item->term_id     = 0;
        $item->name        = '';
        $item->taxonomy    = 'gmedia_gallery';
        $item->taxterm     = 'gallery';
        $item->description = '';
        $item->global      = $user_ID;
        $item->count       = 0;
        $item->status      = 'publish';
        $item->post_id     = 0;
        $item->slug        = '';

        $item->cloud_link = '';
        $item->post_link  = '';
    } else{
        $item->taxterm = str_replace('gmedia_', '', $item->taxonomy);

        $meta = $gmDB->get_metadata('gmedia_term', $item->term_id);
        foreach($meta as $key => $value){
            if($gmCore->is_protected_meta($key, 'gmedia_term')){
                $item->meta[ $key ] = $value[0];
            } else{
                $item->custom[ $key ] = $value;
            }
        }

        $post_id       = isset($meta['_post_ID'][0])? (int)$meta['_post_ID'][0] : 0;
        $item->post_id = $post_id;
        if($post_id){
            $post_item = get_post($post_id);
            if($post_item){
                $item->slug           = $post_item->post_name;
                $item->post_password  = $post_item->post_password;
                $item->comment_count  = $post_item->comment_count;
                $item->comment_status = $post_item->comment_status;
            }
        }

        $item->cloud_link = $gmCore->gmcloudlink($item->term_id, $item->taxterm);
        if(!empty($item->meta['_post_ID'])){
            $item->post_link = get_permalink($item->meta['_post_ID']);
        } else{
            $item->post_link = '';
        }

        if(is_user_logged_in()){
            $allow_terms_delete = gm_user_can('terms_delete');
            if($item->global){
                if((int)$item->global === get_current_user_id()){
                    $item->allow_edit   = gm_user_can("{$item->taxterm}_manage");
                    $item->allow_delete = $allow_terms_delete;
                } else{
                    $item->allow_edit   = gm_user_can('edit_others_media');
                    $item->allow_delete = ($item->allow_edit && $allow_terms_delete);
                }
            } else{
                $item->allow_edit   = gm_user_can('edit_others_media');
                $item->allow_delete = ($item->allow_edit && $allow_terms_delete);
            }
        }
    }

    $_module_name = $gmCore->_get('gallery_module', $item->meta['_module']);

    $item->module         = $gmCore->get_module_path($_module_name);
    $item->module['name'] = $_module_name;
    $module_info          = array('type' => '&#8212;');
    if(is_file($item->module['path'] . '/index.php')){
        include($item->module['path'] . '/index.php');

        $item->module['info'] = $module_info;
    } else{
        $item->module['broken'] = true;
    }

    if($item->global){
        $item->author_name = get_the_author_meta('display_name', $item->global);
    } else{
        $item->author_name = false;
    }

    $item = apply_filters('gmedia_gallery_more_data', $item);
}

/**
 * @param array $query
 *
 * @return array
 */
function gmedia_gallery_query_data($query = array()){
    $filter_data = array('author__in'       => array(),
                         'author__not_in'   => array(),
                         'category__and'    => array(), /* use category id. Display posts that are tagged with all listed categories in array */
                         'category__in'     => array(), /* use category id. Same as 'cat', but does not accept negative values */
                         'category__not_in' => array(), /* use category id. Exclude multiple categories */
                         'album__in'        => array(), /* use album id. Same as 'alb' */
                         'album__not_in'    => array(), /* use album id. Exclude multiple albums */
                         'tag__and'         => array(), /* use tag ids. Display posts that are tagged with all listed tags in array */
                         'tag__in'          => array(), /* use tag ids. To display posts from either tags listed in array. Same as 'tag' */
                         'tag__not_in'      => array(), /* use tag ids. Display posts that do not have any of the listed tag ids */
                         'terms_relation'   => '',      /*  allows you to describe the boolean relationship between the taxonomy queries. Possible values are 'OR', 'AND'. Default 'AND' */
                         'gmedia__in'       => array(), /* use gmedia ids. Specify posts to retrieve */
                         'gmedia__not_in'   => array(), /* use gmedia ids. Specify post NOT to retrieve */
                         'mime_type'        => array(), /* mime types */
                         'limit'            => '', /* (int) - set limit */
                         'page'             => '', /* (int) - set limit */
                         'per_page'         => '', /* (int) - set limit */
                         'order'            => '', /* Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC' */
                         'orderby'          => '', /* Sort retrieved posts by parameter. Defaults to 'ID' */
                         'year'             => '', /* (int) - 4 digit year */
                         'monthnum'         => '', /* (int) - Month number (from 1 to 12) */
                         'day'              => '', /* (int) - Day of the month (from 1 to 31) */
                         'meta_query'       => array(array('key'     => '',
                                                           'value'   => '',
                                                           'compare' => '',
                                                           'type'    => ''
                                                     )
                         ),
                         's'                => '', /* (string) - search string or terms separated by comma */
                         'exact'            => false /* Search exactly string if 'exact' parameter set to true */

    );

    $filter_data = wp_parse_args($query, $filter_data);

    $query_args = (array)gmedia_array_filter_recursive($filter_data);

    return array('query_data' => $filter_data,
                 'query_args' => $query_args
    );
}

function gmedia_array_filter_recursive($input){
    foreach($input as &$value){
        if(is_array($value)){
            $value = gmedia_array_filter_recursive($value);
        }
    }

    return array_filter($input);
}