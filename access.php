<?php
//ini_set('display_errors', '1');
//ini_set('error_reporting', E_ALL);

if (! defined('ABSPATH')) {
    die(0);
    //@require_once(dirname(__FILE__) . '/config.php');
}

global $wp;
$gmedia_app = isset($_GET['gmedia-app']) ? $_GET['gmedia-app'] : (isset($wp->query_vars['gmedia-app']) ? $wp->query_vars['gmedia-app'] : false);
if (! $gmedia_app) {
    die();
}

global $gmCore;
$out = array();

if (isset($_GET['service-link'])) {
    $transient_key = preg_replace('/[^A-Za-z0-9]/', '', $_GET['service-link']);
    if (false !== ($result = get_transient($transient_key))) {
        //$result['mypgc_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR']; //216.70.94.77
        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
        echo json_encode($result);
    }
    die();
}

$gmedia_options = get_option('gmediaOptions');
if (empty($gmedia_options['mobile_app'])) {
    $out['error'] = array('code' => 'app_inactive', 'message' => 'Service not enabled/activated for this site');
    header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
    echo json_encode($out);
    die();
}


if (isset($_FILES['userfile']['name'])) {
    $globaldata = isset($_POST['account']) ? $_POST['account'] : false;
    if ($globaldata) {
        $globaldata = stripslashes($globaldata);
    }
} else {
    $globaldata = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : false;
}

if ($globaldata) {

    $json = json_decode($globaldata);

    require_once(dirname(__FILE__) . '/inc/json.auth.php');
    global $gmAuth;
    $gmAuth = new Gmedia_JSON_API_Auth_Controller();

    if (isset($json->counter)) {
        gmedia_ios_app_counters($json->counter);
    }

    if (isset($json->cookie) && ! empty($json->cookie)) {
        $user_id = $gmAuth->validate_auth_cookie($json->cookie);
        if ($user_id) {
            $user = wp_set_current_user($user_id);
            if (isset($json->add_term)) {
                $out = gmedia_ios_app_processor('add_term', $json->add_term);
            } elseif (isset($json->delete_term)) {
                $out = gmedia_ios_app_processor('delete_term', $json->delete_term);
            } elseif (isset($json->doLibrary)) {
                $job = gmedia_ios_app_processor('do_library', $json->doLibrary);
                $out = gmedia_ios_app_processor('library', $json->library, false);
                $out = array_merge($out, $job);
            } elseif (isset($json->library)) {
                $out = gmedia_ios_app_processor('library', $json->library);
            }


        } else {
            $out['error'] = array('code' => 'wrongcookie', 'message' => 'Not Valid User');
        }
    } elseif (isset($json->login)) {
        $out = gmedia_ios_app_login($json);
        if (! isset($out['error'])) {
            $user = wp_set_current_user($out['user']['id']);

            $gmedia_capabilities_list = array(
                'gmedia_library',
                'gmedia_show_others_media',
                'gmedia_edit_media',
                'gmedia_edit_others_media',
                'gmedia_delete_media',
                'gmedia_delete_others_media',
                'gmedia_upload',
                'gmedia_terms',
                'gmedia_album_manage',
                'gmedia_tag_manage',
                'gmedia_terms_delete'
            );
            $gmedia_capabilities      = array();
            foreach ($gmedia_capabilities_list as $cap) {
                $gmedia_capabilities[$cap] = current_user_can($cap);
            }

            $out['user']['gmedia_capabilities'] = $gmedia_capabilities;

            $data = gmedia_ios_app_library_data();
            $out  = $out + $data;
        }
    } else {
        if (isset($json->library)) {
            $out = gmedia_ios_app_processor('library', $json->library);
        } else {
            $out = gmedia_ios_app_library_data();
        }
    }

} elseif ('lostpassword' == $gmCore->_get('action')) {
    if (function_exists('wp_lostpassword_url')) {
        $url = wp_lostpassword_url();
    } else {
        $url = add_query_arg('action', 'lostpassword', wp_login_url());
    }
    wp_redirect($url);
    exit;
}


/**
 * @param $json
 *
 * @return array
 */
function gmedia_ios_app_login($json)
{
    global $gmAuth;

    do {
        if (empty($json->login)) {
            $out['error'] = array('code' => 'nologin', 'title' => 'No Login', 'message' => 'No Login');
            break;
        }
        if (! isset($json->password) || empty($json->password)) {
            $out['error'] = array('code' => 'nopassword', 'title' => 'No Password', 'message' => 'No Password');
            break;
        }
        if (! ($uid = username_exists($json->login))) {
            $out['error'] = array(
                'code'    => 'nouser',
                'title'   => 'Sorry, we can\'t log you in.',
                'message' => 'No User'
            );
            break;
        }

        $args = array(
            'username' => $json->login,
            'password' => $json->password,
            'nonce'    => wp_create_nonce('auth_gmapp')
        );
        $out  = $gmAuth->generate_auth_cookie($args);

    } while (0);

    return $out;
}

/**
 * @param array $data
 *
 * @return array
 */
function gmedia_ios_app_library_data(
    $data = array('site', 'authors', 'filter', 'gmedia_category', 'gmedia_album', 'gmedia_tag')
) {
    global $user_ID, $gmCore, $gmDB, $gmGallery;

    $out = array();

    $ep = $gmGallery->options['endpoint'];
    if (get_option('permalink_structure')) {
        $share_link = home_url(urlencode($ep) . '/$2/$1');
    } else {
        $share_link = add_query_arg(array("$ep" => '$1', 't' => '$2'), home_url('index.php'));
    }

    if (in_array('site', $data)) {
        $out['site'] = array(
            'title'       => get_bloginfo('name'),
            'description' => get_bloginfo('description')
        );
    }
    if (in_array('authors', $data)) {
        $out['authors'] = array(
            'data' => array()
        );
        //if(current_user_can('gmedia_show_others_media') || current_user_can('gmedia_edit_others_media')){
        $authors = get_users(array('who' => 'authors', 'orderby' => 'display_name'));
        if ($authors) {
            foreach ($authors as $author) {
                $out['authors']['data'][] = array(
                    'id'          => $author->ID,
                    'displayname' => $author->display_name,
                    'firstname'   => $author->first_name,
                    'lastname'    => $author->last_name
                );
            }
        }
        /*} else{
            $authordata = get_userdata( $user_ID );
            $display_name = $authordata->display_name;
            $first_name = $authordata->first_name;
            $last_name = $authordata->last_name;
            $out['authors']['data'][] = array('id' => $user_ID, 'displayname' => $display_name, 'firstname' => $first_name, 'lastname' => $last_name);
        }*/
    }
    if (in_array('filter', $data)) {
        $gmDB->clauses = array();
        $out['filter'] = $gmDB->count_gmedia();
        $out['filter'] = array_map('intval', $out['filter']);
    }
    if (in_array('gmedia_category', $data)) {
        /*
        if($user_ID){
            $cap = (is_super_admin($user_ID) || user_can($user_ID, 'gmedia_category_delete'))? 4 : (user_can($user_ID, 'gmedia_category_edit')? 2 : 0);
        } else{
            $cap = 0;
        }
        */
        $gmediaTerms       = $gmDB->get_terms('gmedia_category', array('fields' => 'name=>all'));
        $terms             = array_merge(array('0' => __('Uncategorized', 'grand-media')),
            $gmGallery->options['taxonomies']['gmedia_category']);
        $out['categories'] = array(
            'list' => $terms,
            'cap'  => 0,
            'data' => array()
        );
        if (! empty($gmediaTerms)) {
            foreach ($gmediaTerms as $name => $term) {
                unset($gmediaTerms[$name]->description, $gmediaTerms[$name]->global, $gmediaTerms[$name]->status);
                $gmediaTerms[$name]->title = $terms[$name];

                $gmedia_hashid                 = gmedia_hash_id_encode($gmediaTerms[$name]->term_id, 'category');
                $gmediaTerms[$name]->sharelink = str_replace(array('$1', '$2'), array(urlencode($gmedia_hashid), 'k'),
                    $share_link);

                $gmediaTerms[$name]->cap = 0;
            }

            $out['categories']['data'] = array_values($gmediaTerms);
        }
    }
    if (in_array('gmedia_album', $data)) {
        $args = array();
        if ($user_ID) {
            if (current_user_can('gmedia_delete_terms')) {
                $cap = 4;
            } elseif (current_user_can('gmedia_album_edit')) {
                $cap = 2;
            } else {
                $cap = 0;
            }
            /*if( !current_user_can('gmedia_edit_others_media')){
                //$args = array( 'status' => array('public', 'private') );
                //$args['global'] = array( $user_ID, 0 );
            }*/
        } else {
            $cap  = 0;
            $args = array('status' => 'public');
        }
        $gmediaTerms = $gmDB->get_terms('gmedia_album', $args);
        foreach ($gmediaTerms as $i => $term) {
            $author_id = (int)$term->global;
            if ($author_id) {
                if (($author_id != $user_ID) && ('draft' == $term->status) && ! current_user_can('gmedia_edit_others_media')) {
                    unset($gmediaTerms[$i]);
                    continue;
                }
                $authordata = get_userdata($author_id);
                if ($authordata) {
                    $display_name = $authordata->display_name;
                    $first_name   = $authordata->first_name;
                    $last_name    = $authordata->last_name;
                } else {
                    $display_name = __('Deleted User', 'grand-media');
                    $first_name   = '';
                    $last_name    = '';
                }
            } else {
                $display_name = __('Shared', 'grand-media');
                $first_name   = $last_name = '';
            }
            $gmediaTerms[$i]->user = array(
                'id'          => $author_id,
                'displayname' => $display_name,
                'firstname'   => $first_name,
                'lastname'    => $last_name
            );

            $term_meta            = $gmDB->get_metadata('gmedia_term', $term->term_id);
            $term_meta            = array_map('reset', $term_meta);
            $term_meta            = array_merge(array('_orderby' => 'ID', '_order' => 'DESC'), $term_meta);
            $term_meta['orderby'] = $term_meta['_orderby'];
            $term_meta['order']   = $term_meta['_order'];

            if (! isset($term_meta['_cover'])) {
                $term_meta['_cover'] = '';
            }
            if ($term_meta['_cover']) {
                $cover_id = intval($term_meta['_cover']);
                $cover    = gmedia_ios_app_processor('library', array('gmedia__in' => array($cover_id)), false);
                if (isset($cover['data'][0])) {
                    $term_meta['_cover']        = $cover['data'][0];
                    $gmediaTerms[$i]->thumbnail = $gmCore->gm_get_media_image($cover_id, 'thumb', false);
                } else {
                    $term_meta['_cover'] = '';
                }
            }
            if (! $term_meta['_cover'] && $term->count) {
                $args = array(
                    'no_found_rows' => true,
                    'mime_type'     => 'image/*',
                    'per_page'      => 1,
                    'album__in'     => array($term->term_id),
                    'status'        => 'public'
                );
                if ($user_ID) {
                    $args['status'] = array('public', 'private');
                    if ($author_id === $user_ID) {
                        $args['status'] = '';
                    }
                } else {
                    $args['status'] = 'public';
                }

                $termItems = $gmDB->get_gmedias($args);
                if (! empty($termItems)) {
                    $cover = gmedia_ios_app_processor('library', array('gmedia__in' => array($termItems[0]->ID)),
                        false);
                    if (isset($cover['data'][0])) {
                        $term_meta['_cover'] = $cover['data'][0];
                    }
                    $gmediaTerms[$i]->thumbnail = $gmCore->gm_get_media_image($termItems[0], 'thumb', false);
                }
            }

            $gmediaTerms[$i]->meta = $term_meta;

            $gmedia_hashid              = gmedia_hash_id_encode($term->term_id, 'album');
            $gmediaTerms[$i]->sharelink = str_replace(array('$1', '$2'), array(urlencode($gmedia_hashid), 'a'),
                $share_link);

            $gmediaTerms[$i]->cap = (4 == $cap) ? 4 : 0;
        }
        $out['albums'] = array(
            'cap'  => $cap,
            'data' => array_values($gmediaTerms)
        );
    }
    if (in_array('gmedia_tag', $data)) {
        if ($user_ID) {
            $cap = (is_super_admin($user_ID) || user_can($user_ID, 'gmedia_tag_delete')) ? 4 : (user_can($user_ID,
                'gmedia_tag_edit') ? 2 : 0);
        } else {
            $cap = 0;
        }
        $gmediaTerms = $gmDB->get_terms('gmedia_tag');
        foreach ($gmediaTerms as $i => $term) {
            unset($gmediaTerms[$i]->description, $gmediaTerms[$i]->global, $gmediaTerms[$i]->status);

            $gmedia_hashid              = gmedia_hash_id_encode($term->term_id, 'tag');
            $gmediaTerms[$i]->sharelink = str_replace(array('$1', '$2'), array(urlencode($gmedia_hashid), 't'),
                $share_link);

            $gmediaTerms[$i]->cap = (4 == $cap) ? 4 : 0;
        }
        $out['tags'] = array(
            'cap'  => $cap,
            'data' => $gmediaTerms
        );
    }

    return $out;
}

/**
 * @param      $action
 * @param      $data
 *
 * @param bool $filter
 *
 * @return array
 */
function gmedia_ios_app_processor($action, $data, $filter = true)
{
    global $gmCore, $gmDB, $gmGallery, $user_ID;

    $out = array();

    $error = array();
    $alert = array();
    $data  = (array)$data;
    switch ($action) {
        case 'do_library':

            if (! isset($data['action'])) {
                return $out;
            }
            switch ($data['action']) {

                case 'add_media':
                    if (! current_user_can('gmedia_upload')) {
                        $out['error'] = array(
                            'code'    => 'nocapability',
                            'title'   => __("You can't do this", 'grand-media'),
                            'message' => __('You have no permission to do this operation', 'grand-media')
                        );

                        return $out;
                    }

                    usleep(10);

                    if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                        $file_name = $_FILES['userfile']['name'];
                        $file_tmp  = $_FILES['userfile']['tmp_name'];
                    } else {
                        $error[] = __("Failed to move uploaded file.", 'grand-media');
                        break;
                    }

                    $fileinfo = $gmCore->fileinfo($file_name);
                    if (false === $fileinfo) {
                        break;
                    }

                    $gmedia = (array)$data['item'];
                    if (! current_user_can('gmedia_terms')) {
                        unset($gmedia['categories'], $gmedia['albums'], $gmedia['tags']);
                    } else {
                        if (! empty($gmedia['categories'])) {
                            $cat                                = $gmedia['categories'][0]->name;
                            $gmedia['terms']['gmedia_category'] = $cat;
                        }
                        if (empty($gmedia['albums'])) {
                            $gmedia['terms']['gmedia_album'] = '';
                        } else {
                            $alb                             = isset($gmedia['albums'][0]->term_id) ? $gmedia['albums'][0]->term_id : $gmedia['albums'][0]->name;
                            $gmedia['terms']['gmedia_album'] = $alb;
                        }
                        if (empty($gmedia['tags'])) {
                            $gmedia['terms']['gmedia_tag'] = '';
                        } else {
                            $tags = array();
                            foreach ($gmedia['tags'] as $tag) {
                                $tags[] = isset($tag->term_id) ? $tag->term_id : $tag->name;
                            }
                            $gmedia['terms']['gmedia_tag'] = implode(',', $tags);
                        }
                        unset($gmedia['categories'], $gmedia['albums'], $gmedia['tags']);
                    }
                    /*if(isset($gmedia['terms'])){
                        $post_data = array('terms' => $gmedia['terms']);
                    } else{
                        $post_data = array();
                    }*/

                    $return = $gmCore->gmedia_upload_handler($file_tmp, $fileinfo, 'multipart', $gmedia);
                    if (isset($return['error'])) {
                        $error[] = $return['error']['message'];
                    } else {
                        $alert[] = $return['success']['message'];
                    }
                    break;

                case 'update_media':
                    $gmedia = (array)$data['item'];
                    if (! empty($gmedia['ID'])) {
                        $item = $gmDB->get_gmedia($gmedia['ID']);

                        $gmedia['modified']  = current_time('mysql');
                        $gmedia['mime_type'] = $item->mime_type;
                        $gmedia['gmuid']     = $item->gmuid;
                        if (! current_user_can('gmedia_delete_others_media')) {
                            $gmedia['author'] = $item->author;
                        }


                        if (! current_user_can('gmedia_terms')) {
                            unset($gmedia['categories'], $gmedia['albums'], $gmedia['tags']);
                        } else {
                            if (! empty($gmedia['categories'])) {
                                $cat                                = $gmedia['categories'][0]->name;
                                $gmedia['terms']['gmedia_category'] = $cat;
                            }
                            if (empty($gmedia['albums'])) {
                                $gmedia['terms']['gmedia_album'] = '';
                            } else {
                                if (isset($gmedia['albums'][0]->term_id)) {
                                    $gmedia['terms']['gmedia_album'] = $gmedia['albums'][0]->term_id;
                                } elseif (current_user_can('gmedia_album_manage')) {
                                    $gmedia['terms']['gmedia_album'] = $gmedia['albums'][0]->name;
                                }
                            }
                            if (empty($gmedia['tags'])) {
                                $gmedia['terms']['gmedia_tag'] = '';
                            } else {
                                $tags = array();
                                foreach ($gmedia['tags'] as $tag) {
                                    if (isset($tag->term_id)) {
                                        $tags[] = $tag->term_id;
                                    } elseif (current_user_can('gmedia_tag_manage')) {
                                        $tags[] = $tag->name;
                                    }
                                }
                                $gmedia['terms']['gmedia_tag'] = implode(',', $tags);
                            }
                            unset($gmedia['categories'], $gmedia['albums'], $gmedia['tags']);
                        }

                        if ((int)$item->author == get_current_user_id()) {
                            if (! current_user_can('gmedia_edit_media')) {
                                $alert[] = __('You are not allowed to edit media', 'grand-media');
                            }
                        } else {
                            if (! current_user_can('gmedia_edit_others_media')) {
                                $alert[] = __('You are not allowed to edit others media', 'grand-media');
                            }
                        }
                        if (empty($alert)) {
                            $gmDB->insert_gmedia($gmedia);
                        } else {
                            if (current_user_can('gmedia_terms') && count($gmedia['terms'])) {
                                foreach ($gmedia['terms'] as $tax => $terms) {
                                    $result = $gmDB->set_gmedia_terms($gmedia['ID'], $terms, $tax, $append = 0);
                                    if (is_wp_error($result)) {
                                        $error[] = $result->get_error_message();
                                    }
                                }
                            }
                        }
                    }
                    break;

                case 'assign_category':
                    if (! current_user_can('gmedia_terms')) {
                        $error[] = __('You are not allowed to manage categories', 'grand-media');
                        break;
                    }
                    $term = $data['assign_category'][0];
                    if (false === $term) {
                        break;
                    }
                    $count = count($data['selected']);
                    if ('0' == $term) {
                        foreach ($data['selected'] as $item) {
                            $gmDB->delete_gmedia_term_relationships($item, 'gmedia_category');
                        }
                        $alert[] = sprintf(__('%d items updated with "Uncategorized"', 'grand-media'), $count);
                    } else {
                        foreach ($data['selected'] as $item) {
                            $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_category', $append = 0);
                            if (is_wp_error($result)) {
                                $error[] = $result->get_error_message();
                                $count--;
                            } elseif (! $result) {
                                $count--;
                            }
                        }
                        if (isset($gmGallery->options['taxonomies']['gmedia_category'][$term])) {
                            $cat_name = $gmGallery->options['taxonomies']['gmedia_category'][$term];
                            $alert[]  = sprintf(__("Category `%s` assigned to %d images.", 'grand-media'),
                                esc_html($cat_name), $count);
                        } else {
                            $error[] = sprintf(__("Category `%s` can't be assigned.", 'grand-media'), $term);
                        }
                    }
                    break;

                case 'assign_album':
                    if (! current_user_can('gmedia_terms')) {
                        $error[] = __('You are not allowed to manage albums', 'grand-media');
                    }
                    $term  = $data['assign_album'][0];
                    $count = count($data['selected']);
                    if ('0' == $term) {
                        foreach ($data['selected'] as $item) {
                            $gmDB->delete_gmedia_term_relationships($item, 'gmedia_album');
                        }
                        $alert[] = sprintf(__('%d items updated with "No Album"', 'grand-media'), $count);
                    } else {
                        foreach ($data['selected'] as $item) {
                            $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_album', $append = 0);
                            if (is_wp_error($result)) {
                                $error[] = $result->get_error_message();
                                $count--;
                            } elseif (! $result) {
                                $count--;
                            }
                        }
                        if ($gmCore->is_digit($term)) {
                            $alb_name = $gmDB->get_alb_name($term);
                        } else {
                            $alb_name = $term;
                        }
                        $alert[] = sprintf(__('Album `%s` assigned to %d items', 'grand-media'), esc_html($alb_name),
                            $count);
                    }
                    break;

                case 'add_tags':
                    if (! current_user_can('gmedia_terms')) {
                        $error[] = __('You are not allowed manage tags', 'grand-media');
                        break;
                    }
                    if (empty($data['add_tags'])) {
                        $error[] = __('No tags provided', 'grand-media');
                        break;
                    }
                    $term  = $data['add_tags'];
                    $count = count($data['selected']);
                    foreach ($data['selected'] as $item) {
                        $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_tag', $append = 1);
                        if (is_wp_error($result)) {
                            $error[] = $result->get_error_message();
                            $count--;
                        } elseif (! $result) {
                            $count--;
                        }
                    }
                    $alert[] = sprintf(__('%d tags added to %d items', 'grand-media'), count($term), $count);
                    break;

                case 'delete_tags':
                    if (! current_user_can('gmedia_delete_others_media')) {
                        $error[] = __('You are not allowed to delete others media', 'grand-media');
                        break;
                    }
                    if (empty($data['delete_tags'])) {
                        $error[] = __('No tags provided', 'grand-media');
                        break;
                    }
                    $term  = array_map('intval', $data['delete_tags']);
                    $count = count($data['selected']);
                    foreach ($data['selected'] as $item) {
                        $result = $gmDB->set_gmedia_terms($item, $term, 'gmedia_tag', $append = -1);
                        if (is_wp_error($result)) {
                            $error[] = $result->get_error_message();
                            $count--;
                        } elseif (! $result) {
                            $count--;
                        }
                    }
                    $alert[] = sprintf(__('%d tags deleted from %d items', 'grand-media'), count($term), $count);
                    break;

                case 'delete':
                    if (! current_user_can('gmedia_delete_media')) {
                        $error[] = __('You are not allowed to delete this post.');
                        break;
                    }
                    $count = count($data['selected']);
                    foreach ($data['selected'] as $item) {
                        $gm_item = $gmDB->get_gmedia($item);
                        if (((int)$gm_item->author != $user_ID) && ! current_user_can('gmedia_delete_others_media')) {
                            $error[] = "#{$item}: " . __('You are not allowed to delete media others media',
                                    'grand-media');
                            continue;
                        }
                        if (! $gmDB->delete_gmedia((int)$item)) {
                            $error[] = "#{$item}: " . __('Error in deleting...', 'grand-media');
                            $count--;
                        }
                    }
                    if ($count) {
                        $alert[] = sprintf(__('%d items deleted successfuly', 'grand-media'), $count);
                    }
                    break;
            }

            $filter = gmedia_ios_app_library_data(array('filter', 'gmedia_category', 'gmedia_album', 'gmedia_tag'));
            $out    = array_merge($out, $filter);
            break;

        case 'library':
            $ep = $gmGallery->options['endpoint'];
            if (get_option('permalink_structure')) {
                $share_link = home_url(urlencode($ep) . '/$2/$1');
            } else {
                $share_link = add_query_arg(array("$ep" => '$1', 't' => '$2'), home_url('index.php'));
            }
            $filter = $filter ? gmedia_ios_app_library_data(array('filter')) : array();

            $args      = array(
                'mime_type'    => 'image/*',
                'orderby'      => 'ID',
                'order'        => 'DESC',
                'per_page'     => 100,
                'page'         => 1,
                'tag__in'      => null,
                'category__in' => null,
                'album__in'    => null,
                'gmedia__in'   => null,
                'author'       => 0,
                'status'       => null
            );
            $data      = wp_parse_args($data, $args);
            $false_out = array_merge($filter, array(
                'properties' => array(
                    'request' => isset($data['request']) ? $data['request'] : null
                ),
                'data'       => array()
            ));

            //$is_collection = ! ( empty( $data['album__in'] ) && empty( $data['tag__in'] ) && empty( $data['category__in'] ) );
            $is_admin = isset($data['admin']) ? intval($data['admin']) : 0;
            if (! is_user_logged_in()) {
                $data['status'] = array('public');
                //if ( $is_collection ) {
                if (! empty($data['album__in'])) {
                    $alb = $gmDB->get_term((int)$data['album__in'][0], 'gmedia_album');
                    if (! (isset($alb->status) && ('public' == $alb->status))) {
                        $out = $false_out;
                        break;
                    }
                }
                //} else {
                //$out = $false_out;
                //break;
                //}
            } else {
                if ($is_admin && ! current_user_can('gmedia_library')) {
                    $out = $false_out;
                    break;
                }
                if ($is_admin && ! current_user_can('gmedia_show_others_media')) {
                    $data['author'] = $user_ID;
                }
            }

            $gmedias = $gmDB->get_gmedias($data);
            foreach ($gmedias as $i => $item) {

                if (! $is_admin && ('draft' == $item->status) && ((int)$user_ID != (int)$item->author)) {
                    //if (!current_user_can('gmedia_edit_others_media')){
                    unset($gmedias[$i]);
                    continue;
                    //}
                }

                $author_id  = $item->author;
                $authordata = get_userdata($author_id);
                if ($authordata) {
                    $display_name = $authordata->display_name;
                    $first_name   = $authordata->first_name;
                    $last_name    = $authordata->last_name;
                } else {
                    $display_name = __('Deleted User', 'grand-media');
                    $first_name   = '';
                    $last_name    = '';
                }
                $gmedias[$i]->user = array(
                    'id'          => $author_id,
                    'displayname' => $display_name,
                    'firstname'   => $first_name,
                    'last_name'   => $last_name
                );
                $gmedias[$i]->date = strtotime($item->date);

                $meta = $gmDB->get_metadata('gmedia', $item->ID);
                //$_metadata          = maybe_unserialize( $meta['_metadata'][0] );
                $_metadata        = $meta['_metadata'][0];
                $type             = explode('/', $item->mime_type);
                $item_url         = $gmCore->upload['url'] . '/' . $gmGallery->options['folder'][$type[0]] . '/' . $item->gmuid;
                $gmedias[$i]->url = $item_url;
                $terms            = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_tag');
                $tags             = array();
                if ($terms) {
                    $terms = array_values((array)$terms);
                    foreach ($terms as $term) {
                        $tags[] = array('term_id' => $term->term_id, 'name' => $term->name);
                    }
                }
                $gmedias[$i]->tags = $tags;

                $terms  = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_album');
                $albums = array();
                if ($terms) {
                    $terms = array_values((array)$terms);
                    foreach ($terms as $term) {
                        $albums[] = array(
                            'term_id' => $term->term_id,
                            'name'    => $term->name,
                            'status'  => $term->status
                        );
                    }
                }
                $gmedias[$i]->albums = $albums;

                if ('image' == $type[0]) {
                    $terms      = $gmDB->get_the_gmedia_terms($item->ID, 'gmedia_category');
                    $categories = array();
                    if ($terms) {
                        $terms = array_values((array)$terms);
                        foreach ($terms as $term) {
                            $categories[] = array('term_id' => $term->term_id, 'name' => $term->name);
                        }
                    }
                    $gmedias[$i]->categories = $categories;

                    $gmedias[$i]->meta                     = array(
                        'thumb'    => $_metadata['thumb'],
                        'web'      => $_metadata['web'],
                        'original' => $_metadata['original']
                    );
                    $gmedias[$i]->meta['thumb']['link']    = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image_thumb']}/{$item->gmuid}";
                    $gmedias[$i]->meta['web']['link']      = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image']}/{$item->gmuid}";
                    $gmedias[$i]->meta['original']['link'] = "{$gmCore->upload['url']}/{$gmGallery->options['folder']['image_original']}/{$item->gmuid}";
                    $gmedias[$i]->meta['views']            = 0;
                    $gmedias[$i]->meta['likes']            = 0;
                    if (isset($meta['views'][0])) {
                        $gmedias[$i]->meta['views'] = $meta['views'][0];
                    }
                    if (isset($meta['likes'][0])) {
                        $gmedias[$i]->meta['likes'] = $meta['likes'][0];
                    }
                    if (isset($_metadata['image_meta'])) {
                        $gmedias[$i]->meta['data'] = $_metadata['image_meta'];
                    }
                } else {
                    $gmedias[$i]->meta = array(
                        'thumb' => array(
                            'link'   => $gmCore->gm_get_media_image($item, 'thumb'),
                            'width'  => 300,
                            'height' => 300
                        )
                    );
                    if (! empty($_metadata)) {
                        $gmedias[$i]->meta['data'] = $_metadata;
                    }
                }
                if (! empty($meta['_gps'][0])) {
                    $gmedias[$i]->meta['data']['GPS'] = $meta['_gps'][0];
                }
                if (isset($meta['_rating'][0])) {
                    $gmedias[$i]->meta['rating'] = maybe_unserialize($meta['_rating'][0]);
                }

                $item_name              = $item->title ? $item->title : pathinfo($item->gmuid, PATHINFO_FILENAME);
                $gmedia_hashid          = gmedia_hash_id_encode($item->ID, 'single');
                $gmedias[$i]->sharelink = str_replace(array('$1', '$2'), array(urlencode($gmedia_hashid), 's'),
                    $share_link);
            }
            $out = array_merge($filter, array(
                'properties' => array(
                    'request'      => isset($data['request']) ? $data['request'] : null,
                    'total_pages'  => $gmDB->pages,
                    'current_page' => $gmDB->openPage,
                    'items_count'  => $gmDB->gmediaCount,
                    'total_count'  => $gmDB->totalResult
                ),
                'data'       => array_values($gmedias)
            ));
            break;
        case 'delete_term':
            $taxonomy = $data['taxonomy'];
            if (! empty($data['items'])) {
                if (! current_user_can('gmedia_terms_delete')) {
                    $error[] = __('You have no permission to do this operation', 'grand-media');
                    break;
                }
                $count = count($data['items']);
                foreach ($data['items'] as $item) {
                    if (! current_user_can('gmedia_edit_others_media')) {
                        if ('gmedia_album' == $taxonomy) {
                            $term = $gmDB->get_term($item, $taxonomy);
                            if ((int)$term->global != (int)$user_ID) {
                                $error['delete_album'] = __('You are not allowed to edit others media', 'grand-media');
                                $count--;
                                continue;
                            }
                        } else {
                            $error[] = __('You are not allowed to edit others media', 'grand-media');
                            $count--;
                            continue;
                        }
                    }
                    $delete = $gmDB->delete_term($item, $taxonomy);
                    if (is_wp_error($delete)) {
                        $error[] = $delete->get_error_message();
                        $count--;
                    }
                }
                if ($count) {
                    $alert[] = sprintf(__('%d items deleted successfuly', 'grand-media'), $count);
                }
            }
            $out = gmedia_ios_app_library_data(array('filter', $taxonomy));
            break;
        case 'add_term':
            $taxonomy  = $data['taxonomy'];
            $edit_term = isset($data['term_id']) ? (int)$data['term_id'] : 0;
            $term      = $data;
            if ('gmedia_album' == $taxonomy) {
                if (! current_user_can('gmedia_album_manage')) {
                    $out['error'] = array(
                        'code'    => 'nocapability',
                        'title'   => __("You can't do this", 'grand-media'),
                        'message' => __('You have no permission to do this operation', 'grand-media')
                    );

                    return $out;
                }
                do {
                    $term['name'] = trim($term['name']);
                    if (empty($term['name'])) {
                        $error[] = __('Term Name is not specified', 'grand-media');
                        break;
                    }
                    if ($gmCore->is_digit($term['name'])) {
                        $error[] = __("Term Name can't be only digits", 'grand-media');
                        break;
                    }
                    if ($edit_term && ! $gmDB->term_exists($edit_term, $taxonomy)) {
                        $error[]   = __('A term with the id provided do not exists', 'grand-media');
                        $edit_term = false;
                    }
                    if (($term_id = $gmDB->term_exists($term['name'], $taxonomy))) {
                        if ($term_id != $edit_term) {
                            $error[] = __('A term with the name provided already exists', 'grand-media');
                            break;
                        }
                    }
                    if ($edit_term) {
                        $_term = $gmDB->get_term($edit_term, $taxonomy);
                        if (((int)$_term->global != (int)$user_ID) && ! current_user_can('gmedia_edit_others_media')) {
                            $error[] = __('You are not allowed to edit others media', 'grand-media');
                            break;
                        }
                        $term_id = $gmDB->update_term($edit_term, $taxonomy, $term);
                    } else {
                        if (! current_user_can('gmedia_edit_others_media')) {
                            $term['global'] = intval($user_ID);
                        }
                        $term_id = $gmDB->insert_term($term['name'], $taxonomy, $term);
                    }
                    if (is_wp_error($term_id)) {
                        $error[] = $term_id->get_error_message();
                        break;
                    }
                    $term_meta = array();
                    if (isset($term['_orderby'])) {
                        $term_meta['_orderby'] = $term['_orderby'];
                    } elseif (isset($term['orderby'])) {
                        $term_meta['_orderby'] = $term['orderby'];
                    }
                    if (isset($term['_order'])) {
                        $term_meta['_order'] = $term['_order'];
                    } elseif (isset($term['order'])) {
                        $term_meta['_order'] = $term['order'];
                    }
                    foreach ($term_meta as $key => $value) {
                        if ($edit_term) {
                            $gmDB->update_metadata('gmedia_term', $term_id, $key, $value);
                        } else {
                            $gmDB->add_metadata('gmedia_term', $term_id, $key, $value);
                        }
                    }

                    $alert[] = sprintf(__('Album `%s` successfuly saved', 'grand-media'), $term['name']);

                } while (0);
                $out = gmedia_ios_app_library_data(array('filter', $taxonomy));
            } elseif ('gmedia_tag' == $taxonomy) {
                if (! current_user_can('gmedia_tag_manage')) {
                    $out['error'] = array(
                        'code'    => 'nocapability',
                        'title'   => __("You can't do this", 'grand-media'),
                        'message' => __('You have no permission to do this operation', 'grand-media')
                    );

                    return $out;
                }
                if ($edit_term) {
                    if (! current_user_can('gmedia_edit_others_media')) {
                        $error[] = __('You are not allowed to edit others media', 'grand-media');
                        break;
                    }
                    $term['name']    = trim($term['name']);
                    $term['term_id'] = intval($term['term_id']);
                    if ($term['name'] && ! $gmCore->is_digit($term['name'])) {
                        if (($term_id = $gmDB->term_exists($term['term_id'], $taxonomy))) {
                            if (! $gmDB->term_exists($term['name'], $taxonomy)) {
                                $term_id = $gmDB->update_term($term['term_id'], $taxonomy, $term);
                                if (is_wp_error($term_id)) {
                                    $error[] = $term_id->get_error_message();
                                } else {
                                    $alert[] = sprintf(__("Tag %d successfuly updated", 'grand-media'), $term_id);
                                }
                            } else {
                                $error[] = __('A term with the name provided already exists', 'grand-media');
                            }
                        } else {
                            $error[] = __("A term with the id provided do not exists", 'grand-media');
                        }
                    } else {
                        $error[] = __("Term name can't be only digits or empty", 'grand-media');
                    }
                } else {
                    $terms       = array_filter(array_map('trim', explode(',', $term['name'])));
                    $terms_added = 0;
                    $terms_qty   = count($terms);
                    foreach ($terms as $term_name) {
                        if ($gmCore->is_digit($term_name)) {
                            continue;
                        }

                        if (! $gmDB->term_exists($term_name, $taxonomy)) {
                            $term_id = $gmDB->insert_term($term_name, $taxonomy);
                            if (is_wp_error($term_id)) {
                                $error[] = $term_id->get_error_message();
                            } else {
                                $alert['tag_add'] = sprintf(__('%d of %d tags successfuly added', 'grand-media'),
                                    ++$terms_added, $terms_qty);
                            }
                        } else {
                            $alert['tag_add'] = __('Some of provided tags are already exists', 'grand-media');
                        }
                    }
                }
                $out = gmedia_ios_app_library_data(array('filter', $taxonomy));
            }

            break;
        default:
            break;
    }

    if (! empty($error)) {
        $out['error'] = array('code' => $action, 'title' => 'ERROR', 'message' => implode("\n", $error));
    }
    if (! empty($alert)) {
        $out['alert'] = array('title' => 'Success', 'message' => implode("\n", $alert));
    }

    return $out;
}


/**
 * @param $data
 */
function gmedia_ios_app_counters($data)
{
    global $gmDB;

    $data          = (array)$data;
    $items_counter = array();
    if (! empty($data['views'])) {
        foreach ($data['views'] as $gmID) {
            $items_counter[$gmID]['views'] = 1;
        }
        if (! empty($data['likes'])) {
            foreach ($data['likes'] as $gmID) {
                $items_counter[$gmID]['likes'] = 1;
            }
        }
    }

    if (! empty($items_counter)) {
        foreach ($items_counter as $gmID => $counters) {
            if (null === $gmDB->get_gmedia($gmID)) {
                continue;
            }
            $counters['views'] = $gmDB->get_metadata('gmedia', $gmID, 'views', true);
            $counters['views'] += 1;
            $gmDB->update_metadata('gmedia', $gmID, 'views', $counters['views']);
            if (isset($counters['likes'])) {
                $counters['likes'] = $gmDB->get_metadata('gmedia', $gmID, 'likes', true);
                $counters['likes'] += 1;
                $gmDB->update_metadata('gmedia', $gmID, 'likes', $counters['likes']);
            }

        }
    }

}

header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
echo json_encode($out);
