<?php
if(preg_match('#' . basename(dirname(__FILE__)) . '/' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
    die('You are not allowed to call this page directly.');
}

/**
 * Main PHP class for the WordPress plugin GRAND Media
 */
class GmediaCore {

    var $upload;
    var $gmedia_url;
    var $caps = array();


    /**
     *
     */
    function __construct(){

        $this->upload     = $this->gm_upload_dir();
        $this->gmedia_url = plugins_url(GMEDIA_FOLDER);

        add_action('init', array(&$this, 'user_capabilities'), 8);
        add_action('init', array(&$this, 'init_actions'), 20);

        add_action('clean_gmedia_cache', array(&$this, 'clear_cache'));
//        add_action( 'deleted_gmedia_term_relationships', array( &$this, 'clear_cache' ) );
//        add_action( 'added_gmedia_meta', array( &$this, 'clear_cache' ) );
//        add_action( 'added_gmedia_term_meta', array( &$this, 'clear_cache' ) );
//        add_action( 'updated_gmedia_meta', array( &$this, 'clear_cache' ) );
//        add_action( 'updated_gmedia_term_meta', array( &$this, 'clear_cache' ) );
//        add_action( 'deleted_gmedia_meta', array( &$this, 'clear_cache' ) );
//        add_action( 'deleted_gmedia_term_meta', array( &$this, 'clear_cache' ) );
//        add_action('created_gmedia_term', array(&$this, 'clear_cache'));
        add_action('edited_gmedia_term', array(&$this, 'clear_cache'));
        add_action('deleted_gmedia_term', array(&$this, 'clear_cache'));
//        add_action( 'gmedia_clean_object_term_cache', array( &$this, 'clear_cache' ) );

        add_filter('get_the_gmedia_terms', array(&$this, 'get_the_gmedia_terms'), 10, 3);
    }

    function user_capabilities(){
        $capabilities = $this->plugin_capabilities();
        $capabilities = apply_filters('gmedia_capabilities', $capabilities);
        if(is_multisite() && is_super_admin()){
            foreach($capabilities as $cap){
                $this->caps[ $cap ] = 1;
            }
        } else{
            $curuser = wp_get_current_user();
            foreach($capabilities as $cap){
                if(isset($curuser->allcaps[ $cap ]) && intval($curuser->allcaps[ $cap ])){
                    $this->caps[ $cap ] = 1;
                } else{
                    $this->caps[ $cap ] = 0;
                }
            }
        }
    }

    function init_actions(){
        global $gmGallery;
        if( !empty($gmGallery->options['license_key']) && empty($gmGallery->options['disable_logs'])){
            add_action('gmedia_view', array(&$this, 'log_views_handler'));
            add_action('gmedia_like', array(&$this, 'log_likes_handler'));
            add_action('gmedia_rate', array(&$this, 'log_rates_handler'), 10, 2);
        }
    }

    /**
     * Check GET data
     *
     * @param string $var
     * @param mixed $def
     * @param bool $empty2false
     *
     * @return mixed
     */
    function _get($var, $def = false, $empty2false = false){
        return isset($_GET[ $var ])? (($empty2false && $this->is_empty($_GET[ $var ]))? false : $_GET[ $var ]) : $def;
    }

    /**
     * Check empty strings
     *
     * @param string $var
     *
     * @return bool
     */
    function is_empty($var){
        return !( !empty($var) && !in_array(strtolower($var), array('null', 'false')));
    }

    /**
     * Check POST data
     *
     * @param string $var
     * @param bool|mixed $def
     *
     * @return mixed
     */

    function _post($var, $def = false){
        return isset($_POST[ $var ])? $_POST[ $var ] : $def;
    }

    /**
     * Check REQUEST data
     *
     * @param string $var
     * @param mixed $def
     *
     * @return mixed
     */
    function _req($var, $def = false){
        return isset($_REQUEST[ $var ])? $_REQUEST[ $var ] : $def;
    }

    /**
     * tooltip()
     *
     * @param string $style 'tooltip', 'popover'
     * @param array $params
     * @param bool $print
     *
     * @return string
     */
    function tooltip($style, $params, $print = true){
        $show_tip = 0; // TODO show tooltips checkbox in settings
        if($show_tip){
            $tooltip = " data-toggle='$style'";
            if(is_array($params) && !empty($params)){
                foreach($params as $key => $val){
                    $tooltip .= " data-$key='$val'";
                }
            }
            if($print){
                echo $tooltip;
            } else{
                return $tooltip;
            }
        }

        return false;
    }

    /**
     * @param array $add_args
     * @param array $remove_args
     * @param bool $uri
     * @param array $preserve_args
     *
     * @return string
     */
    function get_admin_url($add_args = array(), $remove_args = array(), $uri = false, $preserve_args = array()){
        if(true === $uri){
            $uri = admin_url('admin.php');
        }
        $remove_args = empty($remove_args)? array() : (array) $remove_args;
        $_wpnonce    = array();
        foreach($_GET as $key => $value){
            if(strpos($key, '_wpnonce') !== false){
                $_wpnonce[ $key ] = $value;
            }
        }
        $remove_args = array_unique(array_merge(array('doing_wp_cron', '_wpnonce', 'do_gmedia', 'did_gmedia', 'do_gmedia_terms', 'did_gmedia_terms', 'ids'), $_wpnonce, $remove_args, array_keys($add_args)));
        $new_uri     = remove_query_arg($remove_args, $uri);
        if( !empty($preserve_args)){
            $_add_args = array();
            foreach($preserve_args as $key){
                if(($value = $this->_get($key)) !== false){
                    $_add_args[ $key ] = $value;
                }
            }
            $new_uri = add_query_arg($_add_args, $new_uri);
        }
        if( !empty($add_args)){
            $new_uri = add_query_arg($add_args, $new_uri);
        }

        return esc_url_raw($new_uri);
    }

    /**
     * @param $userAgent
     *
     * @return bool
     */
    function is_crawler($userAgent){
        $crawlers  = 'Google|msnbot|Rambler|Yahoo|AbachoBOT|accoona|FeedBurner|' . 'AcioRobot|ASPSeek|CocoCrawler|Dumbot|FAST-WebCrawler|' . 'GeonaBot|Gigabot|Lycos|MSRBOT|Scooter|AltaVista|IDBot|eStyle|Scrubby|yandex|facebook';
        $isCrawler = (preg_match("/$crawlers/i", $userAgent) > 0);

        return $isCrawler;
    }

    /**
     * @param $userAgent
     *
     * @return bool
     */
    function is_browser($userAgent){
        $browsers  = 'opera|aol|msie|firefox|chrome|konqueror|safari|netscape|navigator|mosaic|lynx|amaya|omniweb|avant|camino|flock|seamonkey|mozilla|gecko';
        $isBrowser = (preg_match("/$browsers/i", $userAgent) > 0);

        return $isBrowser;
    }

    /**
     * Get an array containing the gmedia upload directory's path and url.
     * If the path couldn't be created, then an error will be returned with the key
     * 'error' containing the error message. The error suggests that the parent
     * directory is not writable by the server.
     * On success, the returned array will have many indices:
     * 'path' - base directory and sub directory or full path to upload directory.
     * 'url' - base url and sub directory or absolute URL to upload directory.
     * 'error' - set to false.
     * @see  wp_upload_dir()
     * @uses apply_filters() Calls 'gm_upload_dir' on returned array.
     *
     * @param bool $create
     *
     * @return array See above for description.
     */
    function gm_upload_dir($create = true){
        $slash = '/';
        // If multisite (and if not the main site)
        if(is_multisite() && !is_main_site()){
            $slash = '/blogs.dir/' . get_current_blog_id() . '/';
        }

        $dir = WP_CONTENT_DIR . $slash . GMEDIA_UPLOAD_FOLDER;
        $url = WP_CONTENT_URL . $slash . GMEDIA_UPLOAD_FOLDER;

        $url = set_url_scheme($url);

        $uploads = apply_filters('gm_upload_dir', array('path' => $dir, 'url' => $url, 'error' => false));

        if($create){
            // Make sure we have an uploads dir
            if( !wp_mkdir_p($uploads['path'])){
                $message          = sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?'), $uploads['path']);
                $uploads['error'] = $message;
            }
        } elseif( !is_dir($uploads['path'])){
            $uploads['error'] = true;
        }

        return $uploads;
    }

    /**
     * @param $path
     *
     * @return bool|null
     */
    function delete_folder($path){
        $path = rtrim($path, '/');
        if(is_file($path)){
            return @unlink($path);
        } elseif(is_dir($path)){
            $files = glob($path . '/*', GLOB_NOSORT);
            if( !empty($files) && is_array($files)){
                array_map(array($this, 'delete_folder'), $files);
            }

            return @rmdir($path);
        }

        return null;
    }

    /**
     * Get an HTML img element representing an image attachment
     * @see  add_image_size()
     * @see  wp_get_attachment_image()
     * @uses apply_filters() Calls 'gm_get_attachment_image_attributes' hook on attributes array
     *
     * @param int|object $item Image object.
     * @param string $size Optional, default is empty string, could be 'thumb', 'web', 'original'
     * @param bool $cover Optional, try to get cover url
     * @param bool|string $default Optional, return if no cover and if $size != 'all'
     *
     * @return string|array img url for chosen size
     */
    function gm_get_media_image($item, $size = '', $cover = true, $default = false){
        global $gmDB, $gmGallery;

        if( !is_object($item)){
            $item = $gmDB->get_gmedia($item);
        }
        if( !$size){
            $size = 'web';
        }
        if(empty($item)){
            $image  = $default? $default : $this->gmedia_url . '/admin/assets/img/default.png';
            $images = apply_filters('gm_get_media_image', array(
                'thumb'    => $image,
                'web'      => $image,
                'original' => $image
            ));
            if('all' == $size){
                return $images;
            } else{
                return $images[ $size ];
            }
        }
        $type      = explode('/', $item->mime_type);
        $img_cover = false;
        if('image' == $type[0]){
            $images = array(
                'thumb'    => "{$this->upload['url']}/{$gmGallery->options['folder']['image_thumb']}/{$item->gmuid}",
                'web'      => "{$this->upload['url']}/{$gmGallery->options['folder']['image']}/{$item->gmuid}",
                'original' => "{$this->upload['url']}/{$gmGallery->options['folder']['image_original']}/{$item->gmuid}"
            );
            if('original' !== $size){
                $thumb_path = "{$this->upload['path']}/{$gmGallery->options['folder']['image_thumb']}/{$item->gmuid}";
                if( !is_file($thumb_path)){
                    $img_cover = true;
                }
            }
            if('all' == $size || 'original' == $size){
                $original_path = "{$this->upload['path']}/{$gmGallery->options['folder']['image_original']}/{$item->gmuid}";
                if( !is_file($original_path)){
                    $images['original'] = $images['web'];
                }
            }
        }
        if('image' != $type[0] || $img_cover){
            $ext = ltrim(strrchr($item->gmuid, '.'), '.');
            if( !$type = wp_ext2type($ext)){
                $type = 'application';
            }
            $image  = "{$this->gmedia_url}/admin/assets/img/{$type}.png";
            $images = array(
                'thumb'    => $image,
                'web'      => $image,
                'original' => $image,
                'icon'     => false
            );

            if($cover){
                $cover = $gmDB->get_metadata('gmedia', $item->ID, '_cover', true);
                if( !empty($cover)){
                    if($this->is_digit($cover)){
                        $images         = $this->gm_get_media_image((int) $cover, 'all', false);
                        $images['icon'] = $image;
                    }
                } elseif($default !== false){
                    return $default;
                } else{
                    $alb = $gmDB->get_gmedia_terms(array($item->ID), array('gmedia_album'), array('fields' => 'ids'));
                    if( !empty($alb)){
                        $cover = $gmDB->get_metadata('gmedia_term', $alb[0], '_cover', true);
                        if( !empty($cover)){
                            if($this->is_digit($cover)){
                                $images         = $this->gm_get_media_image((int) $cover, 'all', false);
                                $images['icon'] = $image;
                            }
                        }
                    }
                }
            }
        }

        if('all' == $size){
            return $images;
        } else{
            return $images[ $size ];
        }
    }

    /**
     * Get path and url to module folder
     *
     * @param string $module_name
     *
     * @return array|bool Return array( 'path', 'url' ) OR false if no module
     */
    function get_module_path($module_name){
        global $gmGallery;
        if(empty($module_name)){
            return false;
        }
        $module_dirs = array(
            'upload' => array(
                'name' => $module_name,
                'path' => $this->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/' . $module_name,
                'url'  => $this->upload['url'] . '/' . $gmGallery->options['folder']['module'] . '/' . $module_name
            ),
            'plugin' => array(
                'name' => $module_name,
                'path' => GMEDIA_ABSPATH . 'module/' . $module_name,
                'url'  => plugins_url(GMEDIA_FOLDER) . '/module/' . $module_name
            ),
            'theme'  => array(
                'name' => $module_name,
                'path' => get_template_directory() . '/gmedia-module/' . $module_name,
                'url'  => get_template_directory_uri() . '/gmedia-module/' . $module_name
            )
        );
        foreach($module_dirs as $dir){
            if(is_dir($dir['path'])){
                return $dir;
            }
        }

        $getModulePreset = $this->getModulePreset();

        if($module_name != $getModulePreset['module']){
            return $this->get_module_path($getModulePreset['module']);
        }

        return false;
    }

    /** Automatic choose upload directory based on media type
     *
     * @param string $file
     * @param int $exists
     *
     * @return array|bool
     */
    function fileinfo($file, $exists = 0){
        global $gmGallery, $user_ID;

        $file                  = basename($file);
        $file_lower            = strtolower($file);
        $filetype              = wp_check_filetype($file_lower, $mimes = null);
        $title                 = pathinfo($file, PATHINFO_FILENAME);
        $pathinfo              = pathinfo(preg_replace('/[^a-z0-9_\.-]+/i', '_', $file));
        $pathinfo['extension'] = strtolower($pathinfo['extension']);
        $suffix                = ((false !== $exists) && absint($exists))? "_$exists" : '';

        $fileinfo['extension'] = (empty($filetype['ext']))? $pathinfo['extension'] : $filetype['ext'];

        $allowed_ext = get_allowed_mime_types($user_ID);
        $allowed_ext = array_keys($allowed_ext);
        $allowed_ext = implode('|', $allowed_ext);
        $allowed_ext = explode('|', $allowed_ext);
        if( !in_array($fileinfo['extension'], $allowed_ext)){
            return false;
        }

        $fileinfo['basename_original'] = $pathinfo['filename'] . '.' . $fileinfo['extension'];
        $fileinfo['filename']          = $pathinfo['filename'] . $suffix;
        $fileinfo['basename']          = $fileinfo['filename'] . '.' . $fileinfo['extension'];
        $fileinfo['title']             = str_replace('_', ' ', esc_sql($title));
        if((int) $gmGallery->options['name2title_capitalize']){
            $fileinfo['title'] = $this->mb_ucwords_utf8($fileinfo['title']);
        }
        $fileinfo['mime_type'] = (empty($filetype['type']))? 'application/' . $fileinfo['extension'] : $filetype['type'];
        list($dirname) = explode('/', $fileinfo['mime_type']);
        $fileinfo['dirname']           = $dirname;
        $fileinfo['dirpath']           = $this->upload['path'] . '/' . $gmGallery->options['folder'][ $dirname ];
        $fileinfo['dirpath_original']  = $this->upload['path'] . '/' . $gmGallery->options['folder'][ $dirname ];
        $fileinfo['dirurl']            = $this->upload['url'] . '/' . $gmGallery->options['folder'][ $dirname ];
        $fileinfo['dirurl_original']   = $this->upload['url'] . '/' . $gmGallery->options['folder'][ $dirname ];
        $fileinfo['filepath']          = $fileinfo['dirpath'] . '/' . $fileinfo['basename'];
        $fileinfo['filepath_original'] = $fileinfo['dirpath'] . '/' . $fileinfo['basename'];
        $fileinfo['fileurl']           = $fileinfo['dirurl'] . '/' . $fileinfo['basename'];
        $fileinfo['fileurl_original']  = $fileinfo['dirurl'] . '/' . $fileinfo['basename'];
        if('image' == $dirname){
            $fileinfo['dirpath_original']  = $this->upload['path'] . '/' . $gmGallery->options['folder']['image_original'];
            $fileinfo['dirurl_original']   = $this->upload['url'] . '/' . $gmGallery->options['folder']['image_original'];
            $fileinfo['filepath_original'] = $fileinfo['dirpath_original'] . '/' . $fileinfo['basename'];
            $fileinfo['fileurl_original']  = $fileinfo['dirurl_original'] . '/' . $fileinfo['basename'];
            $fileinfo['dirpath_thumb']     = $this->upload['path'] . '/' . $gmGallery->options['folder']['image_thumb'];
            $fileinfo['dirurl_thumb']      = $this->upload['url'] . '/' . $gmGallery->options['folder']['image_thumb'];
            $fileinfo['filepath_thumb']    = $fileinfo['dirpath_thumb'] . '/' . $fileinfo['basename'];
            $fileinfo['fileurl_thumb']     = $fileinfo['dirurl_thumb'] . '/' . $fileinfo['basename'];
        }

        if((false !== $exists) && is_file($fileinfo['filepath'])){
            $exists   = absint($exists) + 1;
            $fileinfo = $this->fileinfo($file, $exists);
        }

        return $fileinfo;
    }

    /** Get file metadata
     *
     * @param $file
     * @param $fileinfo
     *
     * @return mixed|void
     */
    function get_file_metadata($file, $fileinfo = array()){

        if(empty($fileinfo)){
            $fileinfo = $this->fileinfo($file, false);
        }
        $metadata = array();
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        if(preg_match('!^image/!', $fileinfo['mime_type']) && file_is_displayable_image($fileinfo['filepath'])){
            $is_file_original  = is_file($fileinfo['filepath_original']);
            $filepath_original = $is_file_original? $fileinfo['filepath_original'] : $fileinfo['filepath'];
            $imagesize         = getimagesize($fileinfo['filepath_thumb']);
            $metadata['thumb'] = array('width' => $imagesize[0], 'height' => $imagesize[1]);
            $imagesize         = getimagesize($fileinfo['filepath']);
            $metadata['web']   = array('width' => $imagesize[0], 'height' => $imagesize[1]);
            if($is_file_original){
                $imagesize            = getimagesize($filepath_original);
                $metadata['original'] = array('width' => $imagesize[0], 'height' => $imagesize[1]);
            } else{
                $metadata['original'] = $metadata['web'];
            }

            $metadata['file'] = $this->_gm_relative_upload_path($fileinfo['filepath']);

            // fetch additional metadata from exif/iptc
            $image_meta = $this->wp_read_image_metadata($filepath_original);
            if($image_meta){
                $metadata['image_meta'] = $image_meta;
            }

        } elseif(preg_match('#^video/#', $fileinfo['mime_type'])){
            $metadata = $this->wp_read_video_metadata($fileinfo['filepath']);
        } elseif(preg_match('#^audio/#', $fileinfo['mime_type'])){
            $metadata = $this->wp_read_audio_metadata($fileinfo['filepath']);
            //unset($metadata['image']);
        }

        return apply_filters('generate_file_metadata', $metadata);
    }

    /**
     * Return relative path to an uploaded file.
     * The path is relative to the current upload dir.
     * @see  _wp_relative_upload_path()
     * @uses apply_filters() Calls '_gm_relative_upload_path' on file path.
     *
     * @param string $path Full path to the file
     *
     * @return string relative path on success, unchanged path on failure.
     */
    function _gm_relative_upload_path($path){
        $new_path = $path;

        if((false === $this->upload['error']) && (0 === strpos($new_path, $this->upload['path']))){
            $new_path = str_replace($this->upload['path'], '', $new_path);
            $new_path = ltrim($new_path, '/');
        }

        return apply_filters('_gm_relative_upload_path', $new_path, $path);
    }

    /** Set correct file permissions (chmod)
     *
     * @param string $new_file
     */
    function file_chmod($new_file){
        $stat  = stat(dirname($new_file));
        $perms = $stat['mode'] & 0000666;
        @chmod($new_file, $perms);
    }

    /**
     * The most complete and efficient way to sanitize a string before using it with your database
     *
     * @param $input string
     *
     * @return mixed
     */
    function clean_input($input){
        $search = array(/*'@<[\/\!]*?[^<>]*?>@si'*/ /* Strip out HTML tags */
            '@<script' . '[^>]*?>.*?</script>@si' /* Strip out javascript */,
            '@<style' . '[^>]*?>.*?</style>@siU' /* Strip style tags properly */,
            '@<![\s\S]*?--[ \t\n\r]*>@' /* Strip multi-line comments */
            //,'/\s{3,}/'
        );

        $output = preg_replace($search, '', $input);

        return $output;
    }

    /**
     * Sanitize a string|array before using it with your database
     *
     * @param $input string|array
     *
     * @return mixed
     */
    function sanitize($input){
        $output = $input;
        if(is_array($input)){
            foreach($input as $var => $val){
                $output[ $var ] = $this->sanitize($val);
            }
        } else{
            /** @noinspection PhpDeprecationInspection */
            if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()){
                $input = stripslashes($input);
            }
            $input  = $this->clean_input($input);
            $output = esc_sql($input);
        }

        return $output;
    }

    /**
     * Check input for existing only of digits (numbers)
     *
     * @param $digit
     *
     * @return bool
     */
    function is_digit($digit){
        if(is_int($digit)){
            return true;
        } elseif(is_string($digit) && !empty($digit)){
            return ctype_digit($digit);
        } else{
            // booleans, floats and others
            return false;
        }
    }

    /**
     * Check if user can select a author
     * @return array
     */
    function get_editable_user_ids(){
        if(current_user_can('gmedia_show_others_media') || current_user_can('gmedia_edit_others_media')){
            return get_users(array('fields' => 'ID'));
        }

        return get_current_user_id();
    }

    /**
     * Generate GmediaCloud page url
     *
     * @param      $id
     * @param      $type
     * @param bool $default
     *
     * @return string
     */
    function gmcloudlink($id, $type, $default = false){
        $options  = get_option('gmediaOptions');
        $endpoint = $options['endpoint'];
        $hashid   = gmedia_hash_id_encode($id, $type);
        $t        = array(
            'gallery'  => 'g',
            'album'    => 'a',
            'tag'      => 't',
            'single'   => 's',
            'category' => 'k',
            'author'   => 'u'
        );
        if( !$default && get_option('permalink_structure')){
            $cloud_link = home_url(urlencode($endpoint) . "/{$t[$type]}/{$hashid}");
        } else{
            $cloud_link = add_query_arg(array("$endpoint" => $hashid, 't' => $t[ $type ]), home_url('index.php'));
        }

        return $cloud_link;
    }

    /**
     * Extremely simple function to get human filesize
     *
     * @param     $file
     * @param int $decimals
     *
     * @return string
     */
    function filesize($file, $decimals = 2){
        $bytes  = filesize($file);
        $sz     = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');
        $factor = (int) floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $sz[ $factor ];
    }

    /**
     * @author  Gajus Kuizinas <g.kuizinas@anuary.com>
     * @version 1.0.0 (2013 03 19)
     *
     * @param array $arr1
     * @param array $arr2
     *
     * @return array
     */
    function array_diff_key_recursive(array $arr1, array $arr2){
        $diff      = array_diff_key($arr1, $arr2);
        $intersect = array_intersect_key($arr1, $arr2);

        foreach($intersect as $k => $v){
            if(is_array($arr1[ $k ]) && is_array($arr2[ $k ])){
                $d = $this->array_diff_key_recursive($arr1[ $k ], $arr2[ $k ]);

                if( !empty($d)){
                    $diff[ $k ] = $d;
                }
            }
        }

        return $diff;
    }

    /**
     * @param array $arr1
     * @param array $arr2
     * @param bool $update
     *
     * @return array
     */
    function array_diff_keyval_recursive(array $arr1, array $arr2, $update = false){
        $diff      = array_diff_key($arr1, $arr2);
        $intersect = array_intersect_key($arr1, $arr2);

        foreach($intersect as $k => $v){
            if(is_array($arr1[ $k ]) && is_array($arr2[ $k ])){
                $d = $this->array_diff_keyval_recursive($arr1[ $k ], $arr2[ $k ], $update);

                if( !empty($d)){
                    $diff[ $k ] = $d;
                }
            } elseif($arr1[ $k ] !== $arr2[ $k ]){
                if($update){
                    $diff[ $k ] = $arr2[ $k ];
                } else{
                    $diff[ $k ] = $arr1[ $k ];
                }
            }
        }

        return $diff;
    }

    /**
     * @param $base
     * @param $replacements
     *
     * @return mixed
     */
    function array_replace_recursive($base, $replacements){
        if(function_exists('array_replace_recursive')){
            return array_replace_recursive($base, $replacements);
        }

        foreach(array_slice(func_get_args(), 1) as $replacements){
            $bref_stack = array(&$base);
            $head_stack = array($replacements);

            do {
                end($bref_stack);

                $bref = &$bref_stack[ key($bref_stack) ];
                $head = array_pop($head_stack);

                unset($bref_stack[ key($bref_stack) ]);

                foreach(array_keys($head) as $key){
                    if(isset($bref[ $key ]) && is_array($bref[ $key ]) && is_array($head[ $key ])){
                        $bref_stack[] = &$bref[ $key ];
                        $head_stack[] = $head[ $key ];
                    } else{
                        $bref[ $key ] = $head[ $key ];
                    }
                }
            } while(count($head_stack));
        }

        return $base;
    }

    /**
     * @param $callback
     * @param $array
     *
     * @return mixed
     */
    function array_map_recursive($callback, $array){
        foreach($array as $key => $value){
            if(is_array($array[ $key ])){
                $array[ $key ] = $this->array_map_recursive($callback, $array[ $key ]);
            } else{
                $array[ $key ] = call_user_func($callback, $array[ $key ]);
            }
        }

        return $array;
    }

    /**
     * @param string $type
     * @param string $content
     *
     * @return string
     */
    public function alert($type = 'info', $content = ''){
        if(empty($content)){
            return '';
        } elseif(is_array($content)){
            $content = array_filter($content);
            $content = implode('<br />', $content);
        }
        $alert = '<div class="alert alert-' . $type . ' alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' . $content . '</div>';

        return $alert;
    }

    /**
     * @param $photo
     *
     * @return array|bool
     */
    function process_gmedit_image($photo){
        $type = null;
        if(preg_match('/^data:image\/(jpg|jpeg|png|gif)/i', $photo, $matches)){
            $type = $matches[1];
        } else{
            return false;
        }
        // Remove the mime-type header
        $data = explode('base64,', $photo);
        $data = array_reverse($data);
        $data = reset($data);

        // Use strict mode to prevent characters from outside the base64 range
        $image = base64_decode($data, true);

        if( !$image){
            return false;
        }

        return array(
            'data' => $image,
            'type' => $type
        );
    }

    /**
     * @return bool
     */
    function is_bot(){
        if(empty($_SERVER['HTTP_USER_AGENT'])){
            return false;
        }

        $spiders = array(
            "abot",
            "dbot",
            "ebot",
            "hbot",
            "kbot",
            "lbot",
            "mbot",
            "nbot",
            "obot",
            "pbot",
            "rbot",
            "sbot",
            "tbot",
            "vbot",
            "ybot",
            "zbot",
            "bot.",
            "bot/",
            "_bot",
            ".bot",
            "/bot",
            "-bot",
            ":bot",
            "(bot",
            "crawl",
            "slurp",
            "spider",
            "seek",
            "accoona",
            "acoon",
            "adressendeutschland",
            "ah-ha.com",
            "ahoy",
            "altavista",
            "ananzi",
            "anthill",
            "appie",
            "arachnophilia",
            "arale",
            "araneo",
            "aranha",
            "architext",
            "aretha",
            "arks",
            "asterias",
            "atlocal",
            "atn",
            "atomz",
            "augurfind",
            "backrub",
            "bannana_bot",
            "baypup",
            "bdfetch",
            "big brother",
            "biglotron",
            "bjaaland",
            "blackwidow",
            "blaiz",
            "blog",
            "blo.",
            "bloodhound",
            "boitho",
            "booch",
            "bradley",
            "butterfly",
            "calif",
            "cassandra",
            "ccubee",
            "cfetch",
            "charlotte",
            "churl",
            "cienciaficcion",
            "cmc",
            "collective",
            "comagent",
            "combine",
            "computingsite",
            "csci",
            "curl",
            "cusco",
            "daumoa",
            "deepindex",
            "delorie",
            "depspid",
            "deweb",
            "die blinde kuh",
            "digger",
            "ditto",
            "dmoz",
            "docomo",
            "download express",
            "dtaagent",
            "dwcp",
            "ebiness",
            "ebingbong",
            "e-collector",
            "ejupiter",
            "emacs-w3 search engine",
            "esther",
            "evliya celebi",
            "ezresult",
            "falcon",
            "felix ide",
            "ferret",
            "fetchrover",
            "fido",
            "findlinks",
            "fireball",
            "fish search",
            "fouineur",
            "funnelweb",
            "gazz",
            "gcreep",
            "genieknows",
            "getterroboplus",
            "geturl",
            "glx",
            "goforit",
            "golem",
            "grabber",
            "grapnel",
            "gralon",
            "griffon",
            "gromit",
            "grub",
            "gulliver",
            "hamahakki",
            "harvest",
            "havindex",
            "helix",
            "heritrix",
            "hku www octopus",
            "homerweb",
            "htdig",
            "html index",
            "html_analyzer",
            "htmlgobble",
            "hubater",
            "hyper-decontextualizer",
            "ia_archiver",
            "ibm_planetwide",
            "ichiro",
            "iconsurf",
            "iltrovatore",
            "image.kapsi.net",
            "imagelock",
            "incywincy",
            "indexer",
            "infobee",
            "informant",
            "ingrid",
            "inktomisearch.com",
            "inspector web",
            "intelliagent",
            "internet shinchakubin",
            "ip3000",
            "iron33",
            "israeli-search",
            "ivia",
            "jack",
            "jakarta",
            "javabee",
            "jetbot",
            "jumpstation",
            "katipo",
            "kdd-explorer",
            "kilroy",
            "knowledge",
            "kototoi",
            "kretrieve",
            "labelgrabber",
            "lachesis",
            "larbin",
            "legs",
            "libwww",
            "linkalarm",
            "link validator",
            "linkscan",
            "lockon",
            "lwp",
            "lycos",
            "magpie",
            "mantraagent",
            "mapoftheinternet",
            "marvin/",
            "mattie",
            "mediafox",
            "mediapartners",
            "mercator",
            "merzscope",
            "microsoft url control",
            "minirank",
            "miva",
            "mj12",
            "mnogosearch",
            "moget",
            "monster",
            "moose",
            "motor",
            "multitext",
            "muncher",
            "muscatferret",
            "mwd.search",
            "myweb",
            "najdi",
            "nameprotect",
            "nationaldirectory",
            "nazilla",
            "ncsa beta",
            "nec-meshexplorer",
            "nederland.zoek",
            "netcarta webmap engine",
            "netmechanic",
            "netresearchserver",
            "netscoop",
            "newscan-online",
            "nhse",
            "nokia6682/",
            "nomad",
            "noyona",
            "nutch",
            "nzexplorer",
            "objectssearch",
            "occam",
            "omni",
            "open text",
            "openfind",
            "openintelligencedata",
            "orb search",
            "osis-project",
            "pack rat",
            "pageboy",
            "pagebull",
            "page_verifier",
            "panscient",
            "parasite",
            "partnersite",
            "patric",
            "pear.",
            "pegasus",
            "peregrinator",
            "pgp key agent",
            "phantom",
            "phpdig",
            "picosearch",
            "piltdownman",
            "pimptrain",
            "pinpoint",
            "pioneer",
            "piranha",
            "plumtreewebaccessor",
            "pogodak",
            "poirot",
            "pompos",
            "poppelsdorf",
            "poppi",
            "popular iconoclast",
            "psycheclone",
            "publisher",
            "python",
            "rambler",
            "raven search",
            "roach",
            "road runner",
            "roadhouse",
            "robbie",
            "robofox",
            "robozilla",
            "rules",
            "salty",
            "sbider",
            "scooter",
            "scoutjet",
            "scrubby",
            "search.",
            "searchprocess",
            "semanticdiscovery",
            "senrigan",
            "sg-scout",
            "shai'hulud",
            "shark",
            "shopwiki",
            "sidewinder",
            "sift",
            "silk",
            "simmany",
            "site searcher",
            "site valet",
            "sitetech-rover",
            "skymob.com",
            "sleek",
            "smartwit",
            "sna-",
            "snappy",
            "snooper",
            "sohu",
            "speedfind",
            "sphere",
            "sphider",
            "spinner",
            "spyder",
            "steeler/",
            "suke",
            "suntek",
            "supersnooper",
            "surfnomore",
            "sven",
            "sygol",
            "szukacz",
            "tach black widow",
            "tarantula",
            "templeton",
            "/teoma",
            "t-h-u-n-d-e-r-s-t-o-n-e",
            "theophrastus",
            "titan",
            "titin",
            "tkwww",
            "toutatis",
            "t-rex",
            "tutorgig",
            "twiceler",
            "twisted",
            "ucsd",
            "udmsearch",
            "url check",
            "updated",
            "vagabondo",
            "valkyrie",
            "verticrawl",
            "victoria",
            "vision-search",
            "volcano",
            "voyager/",
            "voyager-hc",
            "w3c_validator",
            "w3m2",
            "w3mir",
            "walker",
            "wallpaper",
            "wanderer",
            "wauuu",
            "wavefire",
            "web core",
            "web hopper",
            "web wombat",
            "webbandit",
            "webcatcher",
            "webcopy",
            "webfoot",
            "weblayers",
            "weblinker",
            "weblog monitor",
            "webmirror",
            "webmonkey",
            "webquest",
            "webreaper",
            "websitepulse",
            "websnarf",
            "webstolperer",
            "webvac",
            "webwalk",
            "webwatch",
            "webwombat",
            "webzinger",
            "wget",
            "whizbang",
            "whowhere",
            "wild ferret",
            "worldlight",
            "wwwc",
            "wwwster",
            "xenu",
            "xget",
            "xift",
            "xirq",
            "yandex",
            "yanga",
            "yeti",
            "yodao",
            "zao/",
            "zippp",
            "zyborg",
            "...."
        );

        foreach($spiders as $spider){
            //If the spider text is found in the current user agent, then return true
            if(stripos($_SERVER['HTTP_USER_AGENT'], $spider) !== false){
                return true;
                break;
            }
        }

        return false;
    }


    /**
     * Parse ID3v2, ID3v1, and getID3 comments to extract usable data
     * @since 3.6.0
     *
     * @param array $metadata An existing array with data
     * @param array $data Data supplied by ID3 tags
     */
    function wp_add_id3_tag_data(&$metadata, $data){
        foreach(array('id3v2', 'id3v1') as $version){
            if( !empty($data[ $version ]['comments'])){
                foreach($data[ $version ]['comments'] as $key => $list){
                    if( !empty($list)){
                        $metadata[ $key ] = reset($list);
                        // fix bug in byte stream analysis
                        if('terms_of_use' === $key && 0 === strpos($metadata[ $key ], 'yright notice.')){
                            $metadata[ $key ] = 'Cop' . $metadata[ $key ];
                        }
                    }
                }
                break;
            }
        }

        if( !empty($data['id3v2']['APIC'])){
            $image = reset($data['id3v2']['APIC']);
            if( !empty($image['data'])){
                $metadata['image'] = array(
                    'data'   => $image['data'],
                    'mime'   => $image['image_mime'],
                    'width'  => $image['image_width'],
                    'height' => $image['image_height']
                );
            }
        } elseif( !empty($data['comments']['picture'])){
            $image = reset($data['comments']['picture']);
            if( !empty($image['data'])){
                $metadata['image'] = array(
                    'data' => $image['data'],
                    'mime' => $image['image_mime']
                );
            }
        }
    }

    /**
     * Get extended image metadata, exif or iptc as available.
     * Retrieves the EXIF metadata aperture, credit, camera, caption, copyright, iso
     * created_timestamp, focal_length, shutter_speed, and title.
     * The IPTC metadata that is retrieved is APP13, credit, byline, created date
     * and time, caption, copyright, and title. Also includes FNumber, Model,
     * DateTimeDigitized, FocalLength, ISOSpeedRatings, and ExposureTime.
     * @todo  Try other exif libraries if available.
     * @since 2.5.0
     *
     * @param string $file
     *
     * @return bool|array False on failure. Image metadata array on success.
     */
    function wp_read_image_metadata($file){
        if( !is_file($file)){
            return false;
        }

        list(, , $sourceImageType) = getimagesize($file);

        $meta = array();

        /*
		 * Read IPTC first, since it might contain data not available in exif such
		 * as caption, description etc.
		 */
        if(is_callable('iptcparse')){
            getimagesize($file, $info);

            if( !empty($info['APP13'])){
                $iptc = iptcparse($info['APP13']);

                // Headline, "A brief synopsis of the caption."
                if( !empty($iptc['2#105'][0])){
                    $meta['title'] = trim($iptc['2#105'][0]);
                    /*
					 * Title, "Many use the Title field to store the filename of the image,
					 * though the field may be used in many ways."
					 */
                } elseif( !empty($iptc['2#005'][0])){
                    $meta['title'] = trim($iptc['2#005'][0]);
                }

                if( !empty($iptc['2#120'][0])){ // description / legacy caption
                    $caption = trim($iptc['2#120'][0]);
                    if(empty($meta['title'])){
                        mbstring_binary_safe_encoding();
                        $caption_length = strlen($caption);
                        reset_mbstring_encoding();

                        // Assume the title is stored in 2:120 if it's short.
                        if($caption_length < 80){
                            $meta['title'] = $caption;
                        } else{
                            $meta['caption'] = $caption;
                        }
                    } elseif($caption != $meta['title']){
                        $meta['caption'] = $caption;
                    }
                }

                if( !empty($iptc['2#110'][0])) // credit
                {
                    $meta['credit'] = trim($iptc['2#110'][0]);
                } elseif( !empty($iptc['2#080'][0])) // creator / legacy byline
                {
                    $meta['credit'] = trim($iptc['2#080'][0]);
                }

                if( !empty($iptc['2#055'][0]) and !empty($iptc['2#060'][0])) // created date and time
                {
                    $meta['created_timestamp'] = strtotime($iptc['2#055'][0] . ' ' . $iptc['2#060'][0]);
                }

                if( !empty($iptc['2#116'][0])) // copyright
                {
                    $meta['copyright'] = trim($iptc['2#116'][0]);
                }

                if( !empty($iptc['2#025'])) // keywords
                {
                    $meta['keywords'] = $iptc['2#025'];
                }
            }
        }

        /**
         * Filter the image types to check for exif data.
         * @since 2.5.0
         *
         * @param array $image_types Image types to check for exif data.
         */
        if(is_callable('exif_read_data') && in_array($sourceImageType, apply_filters('wp_read_image_metadata_types', array(
                IMAGETYPE_JPEG,
                IMAGETYPE_TIFF_II,
                IMAGETYPE_TIFF_MM
            )))
        ){
            $exif = @exif_read_data($file);
            unset($exif['MakerNote']);

            // Title
            if(empty($meta['title']) && !empty($exif['Title'])){
                $meta['title'] = trim($exif['Title']);
            }
            // Descrioption
            if( !empty($exif['ImageDescription'])){
                mbstring_binary_safe_encoding();
                $description_length = strlen($exif['ImageDescription']);
                reset_mbstring_encoding();

                if(empty($meta['title']) && $description_length < 80){
                    // Assume the title is stored in ImageDescription
                    $meta['title'] = trim($exif['ImageDescription']);
                    if(empty($meta['caption']) && !empty($exif['COMPUTED']['UserComment']) && trim($exif['COMPUTED']['UserComment']) != $meta['title']){
                        $meta['caption'] = trim($exif['COMPUTED']['UserComment']);
                    }
                } elseif(empty($meta['caption']) && trim($exif['ImageDescription']) != $meta['title']){
                    $meta['caption'] = trim($exif['ImageDescription']);
                }
            } elseif(empty($meta['caption']) && !empty($exif['Comments']) && trim($exif['Comments']) != $meta['title']){
                $meta['caption'] = trim($exif['Comments']);
            }
            // Credit
            if(empty($meta['credit'])){
                if( !empty($exif['Artist'])){
                    $meta['credit'] = trim($exif['Artist']);
                } elseif( !empty($exif['Author'])){
                    $meta['credit'] = trim($exif['Author']);
                }
            }
            // Copyright
            if(empty($meta['copyright']) && !empty($exif['Copyright'])){
                $meta['copyright'] = trim($exif['Copyright']);
            }
            // Camera Make
            if( !empty($exif['Make'])){
                $meta['make'] = $exif['Make'];
            }
            // Camera Model
            if( !empty($exif['Model'])){
                $meta['model'] = trim($exif['Model']);
            }
            // Exposure Time (shutter speed)
            if( !empty($exif['ExposureTime'])){
                $meta['exposure']      = $exif['ExposureTime'] . 's';
                $meta['shutter_speed'] = (string) wp_exif_frac2dec($exif['ExposureTime']) . 's';
            }
            // Aperture
            if( !empty($exif['COMPUTED']['ApertureFNumber'])){
                $meta['aperture'] = $exif['COMPUTED']['ApertureFNumber'];
            } elseif( !empty($exif['FNumber'])){
                $meta['aperture'] = 'f/' . (string) round(wp_exif_frac2dec($exif['FNumber']), 2);
            }
            // ISO
            if( !empty($exif['ISOSpeedRatings'])){
                $meta['iso'] = is_array($exif['ISOSpeedRatings'])? reset($exif['ISOSpeedRatings']) : $exif['ISOSpeedRatings'];
                $meta['iso'] = trim($meta['iso']);
            }
            // Date
            if( !empty($exif['DateTime'])){
                $meta['date'] = $exif['DateTime'];
            }
            // Created TimeStamp
            if(empty($meta['created_timestamp']) && !empty($exif['DateTimeDigitized'])){
                $meta['created_timestamp'] = wp_exif_date2ts($exif['DateTimeDigitized']);
            }
            // Lens
            if( !empty($exif['UndefinedTag:0xA434'])){
                $meta['lens'] = $exif['UndefinedTag:0xA434'];
            }
            // Focus Distance
            if( !empty($exif['COMPUTED']['FocusDistance'])){
                $meta['distance'] = $exif['COMPUTED']['FocusDistance'];
            }
            // Focal Length
            if( !empty($exif['FocalLength'])){
                $meta['focallength'] = (string) round(wp_exif_frac2dec($exif['FocalLength'])) . 'mm';
            }
            // Focal Length 35mm
            if( !empty($exif['FocalLengthIn35mmFilm'])){
                $meta['focallength35'] = $exif['FocalLengthIn35mmFilm'] . 'mm';
            }
            // Flash data
            if( !empty($exif['Flash'])){
                // we need to interpret the result - it's given as a number and we want a human-readable description.
                $fdata = $exif['Flash'];

                switch($fdata){
                    case 0 :
                        $fdata = 'No Flash';
                        break;
                    case 1 :
                        $fdata = 'Flash';
                        break;
                    case 5 :
                        $fdata = 'Flash, strobe return light not detected';
                        break;
                    case 7 :
                        $fdata = 'Flash, strob return light detected';
                        break;
                    case 9 :
                        $fdata = 'Compulsory Flash';
                        break;
                    case 13:
                        $fdata = 'Compulsory Flash, Return light not detected';
                        break;
                    case 15:
                        $fdata = 'Compulsory Flash, Return light detected';
                        break;
                    case 16:
                        $fdata = 'No Flash';
                        break;
                    case 24:
                        $fdata = 'No Flash';
                        break;
                    case 25:
                        $fdata = 'Flash, Auto-Mode';
                        break;
                    case 29:
                        $fdata = 'Flash, Auto-Mode, Return light not detected';
                        break;
                    case 31:
                        $fdata = 'Flash, Auto-Mode, Return light detected';
                        break;
                    case 32:
                        $fdata = 'No Flash';
                        break;
                    case 65:
                        $fdata = 'Red Eye';
                        break;
                    case 69:
                        $fdata = 'Red Eye, Return light not detected';
                        break;
                    case 71:
                        $fdata = 'Red Eye, Return light detected';
                        break;
                    case 73:
                        $fdata = 'Red Eye, Compulsory Flash';
                        break;
                    case 77:
                        $fdata = 'Red Eye, Compulsory Flash, Return light not detected';
                        break;
                    case 79:
                        $fdata = 'Red Eye, Compulsory Flash, Return light detected';
                        break;
                    case 89:
                        $fdata = 'Red Eye, Auto-Mode';
                        break;
                    case 93:
                        $fdata = 'Red Eye, Auto-Mode, Return light not detected';
                        break;
                    case 95:
                        $fdata = 'Red Eye, Auto-Mode, Return light detected';
                        break;
                    default:
                        $fdata = 'Unknown: ' . $fdata;
                        break;
                }
                $meta['flashdata'] = $fdata;
            }
            // Lens Make
            if( !empty($exif['UndefinedTag:0xA433'])){
                $meta['lensmake'] = $exif['UndefinedTag:0xA433'];
            }
            // Software
            if( !empty($exif['Software'])){
                $meta['software'] = $exif['Software'];
            }
            // Orientation
            if( !empty($exif['Orientation'])){
                $meta['orientation'] = $exif['Orientation'];
            }

            $exif_sections = @exif_read_data($file, null, true);
            if(isset($exif_sections['GPS'])){
                $meta['GPS'] = $this->getGPSfromExif($exif_sections['GPS']);
            }
            unset($exif_sections);
            //$meta['exif'] = $exif;
        }

        foreach(array('title', 'caption', 'credit', 'copyright', 'model', 'iso', 'software') as $key){
            if( !empty($meta[ $key ]) && !seems_utf8($meta[ $key ])){
                $meta[ $key ] = utf8_encode($meta[ $key ]);
            }
        }
        if( !empty($meta['keywords'])){
            foreach($meta['keywords'] as $i => $key){
                if( !seems_utf8($key)){
                    $meta['keywords'][ $i ] = utf8_encode($key);
                }
            }
        }

        foreach($meta as &$value){
            if(is_string($value)){
                $value = wp_kses_post($value);
            }
        }

        /**
         * Filter the array of meta data read from an image's exif data.
         * @since 2.5.0
         *
         * @param array $meta Image meta data.
         * @param string $file Path to image file.
         * @param int $sourceImageType Type of image.
         */
        return apply_filters('wp_read_image_metadata', $meta, $file, $sourceImageType);

    }

    /**
     * Retrieve metadata from a video file's ID3 tags
     * @since 3.6.0
     *
     * @param string $file Path to file.
     *
     * @return array|boolean Returns array of metadata, if found.
     */
    function wp_read_video_metadata($file){
        if( !is_file($file)){
            return false;
        }

        $metadata = array();

        if( !class_exists('getID3')){
            require(ABSPATH . WPINC . '/ID3/getid3.php');
        }
        $id3  = new getID3();
        $data = $id3->analyze($file);

        if(isset($data['video']['lossless'])){
            $metadata['lossless'] = $data['video']['lossless'];
        }
        if( !empty($data['video']['bitrate'])){
            $metadata['bitrate'] = (int) $data['video']['bitrate'];
        }
        if( !empty($data['video']['bitrate_mode'])){
            $metadata['bitrate_mode'] = $data['video']['bitrate_mode'];
        }
        if( !empty($data['filesize'])){
            $metadata['filesize'] = (int) $data['filesize'];
        }
        if( !empty($data['mime_type'])){
            $metadata['mime_type'] = $data['mime_type'];
        }
        if( !empty($data['playtime_seconds'])){
            $metadata['length'] = (int) ceil($data['playtime_seconds']);
        }
        if( !empty($data['playtime_string'])){
            $metadata['length_formatted'] = $data['playtime_string'];
        }
        if( !empty($data['video']['resolution_x'])){
            $metadata['width'] = (int) $data['video']['resolution_x'];
        }
        if( !empty($data['video']['resolution_y'])){
            $metadata['height'] = (int) $data['video']['resolution_y'];
        }
        if( !empty($data['fileformat'])){
            $metadata['fileformat'] = $data['fileformat'];
        }
        if( !empty($data['video']['dataformat'])){
            $metadata['dataformat'] = $data['video']['dataformat'];
        }
        if( !empty($data['video']['encoder'])){
            $metadata['encoder'] = $data['video']['encoder'];
        }
        if( !empty($data['video']['codec'])){
            $metadata['codec'] = $data['video']['codec'];
        }

        if( !empty($data['audio'])){
            unset($data['audio']['streams']);
            $metadata['audio'] = $data['audio'];
        }

        $this->wp_add_id3_tag_data($metadata, $data);

        return $metadata;
    }

    /**
     * Retrieve metadata from a audio file's ID3 tags
     * @since 3.6.0
     *
     * @param string $file Path to file.
     *
     * @return array|boolean Returns array of metadata, if found.
     */
    function wp_read_audio_metadata($file){
        if( !is_file($file)){
            return false;
        }
        $metadata = array();

        if( !class_exists('getID3')){
            require(ABSPATH . WPINC . '/ID3/getid3.php');
        }
        $id3  = new getID3();
        $data = $id3->analyze($file);

        if( !empty($data['audio'])){
            unset($data['audio']['streams']);
            $metadata = $data['audio'];
        }

        if( !empty($data['fileformat'])){
            $metadata['fileformat'] = $data['fileformat'];
        }
        if( !empty($data['filesize'])){
            $metadata['filesize'] = (int) $data['filesize'];
        }
        if( !empty($data['mime_type'])){
            $metadata['mime_type'] = $data['mime_type'];
        }
        if( !empty($data['playtime_seconds'])){
            $metadata['length'] = (int) ceil($data['playtime_seconds']);
        }
        if( !empty($data['playtime_string'])){
            $metadata['length_formatted'] = $data['playtime_string'];
        }

        $this->wp_add_id3_tag_data($metadata, $data);

        if(isset($metadata['image']['data']) && !empty($metadata['image']['data'])){
            $image                     = 'data:' . $metadata['image']['mime'] . ';charset=utf-8;base64,' . base64_encode($metadata['image']['data']);
            $metadata['image']['data'] = $image;
        }

        return $metadata;
    }


    /** Write the file
     *
     * @param string $file_tmp
     * @param array $fileinfo
     * @param string $content_type
     * @param array $post_data
     *
     * @return array
     */
    function gmedia_upload_handler($file_tmp, $fileinfo, $content_type, $post_data){
        global $gmGallery, $gmDB;

        $cleanup_dir = true; // Remove old files
        $file_age    = 5 * 3600; // Temp file age in seconds
        $chunk       = (int) $this->_req('chunk', 0);
        $chunks      = (int) $this->_req('chunks', 0);

        // try to make grand-media dir if not exists
        if( !wp_mkdir_p($fileinfo['dirpath'])){
            $return = array(
                "error" => array(
                    "code"    => 100,
                    "message" => sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?', 'grand-media'), $fileinfo['dirpath'])
                ),
                "id"    => $fileinfo['basename_original']
            );

            return $return;
        }
        // Check if grand-media dir is writable
        if( !is_writable($fileinfo['dirpath'])){
            @chmod($fileinfo['dirpath'], 0755);
            if( !is_writable($fileinfo['dirpath'])){
                $return = array(
                    "error" => array(
                        "code"    => 100,
                        "message" => sprintf(__('Directory %s or its subfolders are not writable by the server.', 'grand-media'), dirname($fileinfo['dirpath']))
                    ),
                    "id"    => $fileinfo['basename_original']
                );

                return $return;
            }
        }
        // Remove old temp files
        if($cleanup_dir && is_dir($fileinfo['dirpath']) && ($_dir = opendir($fileinfo['dirpath']))){
            while(($_file = readdir($_dir)) !== false){
                $tmpfilePath = $fileinfo['dirpath'] . DIRECTORY_SEPARATOR . $_file;

                // Remove temp file if it is older than the max age and is not the current file
                if(preg_match('/\.part$/', $_file) && (filemtime($tmpfilePath) < time() - $file_age) && ($tmpfilePath != $fileinfo['filepath'] . '.part')){
                    @unlink($tmpfilePath);
                }
            }

            closedir($_dir);
        } else{
            $return = array(
                "error" => array("code" => 100, "message" => sprintf(__('Failed to open directory: %s', 'grand-media'), $fileinfo['dirpath'])),
                "id"    => $fileinfo['basename_original']
            );

            return $return;
        }

        // Open temp file
        $out = fopen($fileinfo['filepath'] . '.part', $chunk == 0? "wb" : "ab");
        if($out){
            // Read binary input stream and append it to temp file
            $in = fopen($file_tmp, "rb");

            if($in){
                while(($buff = fread($in, 4096))){
                    fwrite($out, $buff);
                }
            } else{
                $return = array("error" => array("code" => 101, "message" => __("Failed to open input stream.", 'grand-media')), "id" => $fileinfo['basename']);

                return $return;
            }
            fclose($in);
            fclose($out);
            if(strpos($content_type, "multipart") !== false){
                @unlink($file_tmp);
            }
            if( !$chunks || $chunk == ($chunks - 1)){
                sleep(1);
                // Strip the temp .part suffix off
                rename($fileinfo['filepath'] . '.part', $fileinfo['filepath']);

                $this->file_chmod($fileinfo['filepath']);

                $size        = false;
                $is_webimage = false;
                if('image' == $fileinfo['dirname']){
                    /** WordPress Image Administration API */
                    require_once(ABSPATH . 'wp-admin/includes/image.php');

                    $size = @getimagesize($fileinfo['filepath']);
                    if($size && file_is_displayable_image($fileinfo['filepath'])){
                        $extensions = array('1' => 'GIF', '2' => 'JPG', '3' => 'PNG', '6' => 'BMP');
                        if(function_exists('memory_get_usage')){
                            switch($extensions[ $size[2] ]){
                                case 'GIF':
                                    $CHANNEL = 1;
                                    break;
                                case 'JPG':
                                    $CHANNEL = $size['channels'];
                                    break;
                                case 'PNG':
                                    $CHANNEL = 3;
                                    break;
                                case 'BMP':
                                default:
                                    $CHANNEL = 6;
                                    break;
                            }
                            $MB                = 1048576;  // number of bytes in 1M
                            $K64               = 65536;    // number of bytes in 64K
                            $TWEAKFACTOR       = 1.8;     // Or whatever works for you
                            $memoryNeeded      = round(($size[0] * $size[1] * $size['bits'] * $CHANNEL / 8 + $K64) * $TWEAKFACTOR);
                            $memoryNeeded      = memory_get_usage() + $memoryNeeded;
                            $current_limit     = @ini_get('memory_limit');
                            $current_limit_int = intval($current_limit);
                            if(false !== strpos($current_limit, 'M')){
                                $current_limit_int *= $MB;
                            }
                            if(false !== strpos($current_limit, 'G')){
                                $current_limit_int *= 1024;
                            }

                            if(- 1 != $current_limit && $memoryNeeded > $current_limit_int){
                                $newLimit = $current_limit_int / $MB + ceil(($memoryNeeded - $current_limit_int) / $MB);
                                if($newLimit < 256){
                                    $newLimit = 256;
                                }
                                @ini_set('memory_limit', $newLimit . 'M');
                            }
                        }

                        if( !wp_mkdir_p($fileinfo['dirpath_thumb'])){
                            $return = array(
                                "error" => array(
                                    "code"    => 100,
                                    "message" => sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?', 'grand-media'), $fileinfo['dirpath_thumb'])
                                ),
                                "id"    => $fileinfo['basename']
                            );

                            return $return;
                        }
                        if( !is_writable($fileinfo['dirpath_thumb'])){
                            @chmod($fileinfo['dirpath_thumb'], 0755);
                            if( !is_writable($fileinfo['dirpath_thumb'])){
                                @unlink($fileinfo['filepath']);
                                $return = array(
                                    "error" => array(
                                        "code"    => 100,
                                        "message" => sprintf(__('Directory %s is not writable by the server.', 'grand-media'), $fileinfo['dirpath_thumb'])
                                    ),
                                    "id"    => $fileinfo['basename']
                                );

                                return $return;
                            }
                        }
                        if( !wp_mkdir_p($fileinfo['dirpath_original'])){
                            $return = array(
                                "error" => array(
                                    "code"    => 100,
                                    "message" => sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?', 'grand-media'), $fileinfo['dirpath_original'])
                                ),
                                "id"    => $fileinfo['basename']
                            );

                            return $return;
                        }
                        if( !is_writable($fileinfo['dirpath_original'])){
                            @chmod($fileinfo['dirpath_original'], 0755);
                            if( !is_writable($fileinfo['dirpath_original'])){
                                @unlink($fileinfo['filepath']);
                                $return = array(
                                    "error" => array(
                                        "code"    => 100,
                                        "message" => sprintf(__('Directory %s is not writable by the server.', 'grand-media'), $fileinfo['dirpath_original'])
                                    ),
                                    "id"    => $fileinfo['basename']
                                );

                                return $return;
                            }
                        }

                        // Optimized image
                        $webimg   = $gmGallery->options['image'];
                        $thumbimg = $gmGallery->options['thumb'];

                        $webimg['resize'] = (($webimg['width'] < $size[0]) || ($webimg['height'] < $size[1]))? true : false;

                        if($webimg['resize']){
                            rename($fileinfo['filepath'], $fileinfo['filepath_original']);
                        } else{
                            copy($fileinfo['filepath'], $fileinfo['filepath_original']);
                        }

                        $size_ratio = $size[0] / $size[1];

                        $angle      = 0;
                        $image_meta = @$this->wp_read_image_metadata($fileinfo['filepath_original']);
                        if( !empty($image_meta['orientation'])){
                            switch($image_meta['orientation']){
                                case 3:
                                    $angle = 180;
                                    break;
                                case 6:
                                    $angle      = 270;
                                    $size_ratio = $size[1] / $size[0];
                                    break;
                                case 8:
                                    $angle      = 90;
                                    $size_ratio = $size[1] / $size[0];
                                    break;
                            }
                        }

                        $thumbimg['resize'] = (((1 >= $size_ratio) && ($thumbimg['width'] > $size[0])) || ((1 <= $size_ratio) && ($thumbimg['height'] > $size[1])))? false : true;

                        if($webimg['resize'] || $thumbimg['resize'] || $angle){
                            $editor = wp_get_image_editor($fileinfo['filepath_original']);
                            if(is_wp_error($editor)){
                                @unlink($fileinfo['filepath']);
                                @unlink($fileinfo['filepath_original']);
                                $return = array(
                                    "error" => array("code" => $editor->get_error_code(), "message" => $editor->get_error_message()),
                                    "id"    => $fileinfo['basename'],
                                    "tip"   => 'wp_get_image_editor'
                                );

                                return $return;
                            }

                            if($angle){
                                $editor->rotate($angle);
                            }

                            if($webimg['resize'] || $angle){
                                $editor->set_quality($webimg['quality']);

                                if($webimg['resize']){
                                    $resized = $editor->resize($webimg['width'], $webimg['height'], $webimg['crop']);
                                    if(is_wp_error($resized)){
                                        @unlink($fileinfo['filepath']);
                                        @unlink($fileinfo['filepath_original']);
                                        $return = array(
                                            "error" => array("code" => $resized->get_error_code(), "message" => $resized->get_error_message()),
                                            "id"    => $fileinfo['basename'],
                                            "tip"   => "editor->resize->webimage({$webimg['width']}, {$webimg['height']}, {$webimg['crop']})"
                                        );

                                        return $return;
                                    }
                                }

                                $saved = $editor->save($fileinfo['filepath']);
                                if(is_wp_error($saved)){
                                    @unlink($fileinfo['filepath']);
                                    @unlink($fileinfo['filepath_original']);
                                    $return = array(
                                        "error" => array("code" => $saved->get_error_code(), "message" => $saved->get_error_message()),
                                        "id"    => $fileinfo['basename'],
                                        "tip"   => 'editor->save->webimage'
                                    );

                                    return $return;
                                }

                                if(('JPG' == $extensions[ $size[2] ]) && !(extension_loaded('imagick') || class_exists("Imagick"))){
                                    $this->copy_exif($fileinfo['filepath_original'], $fileinfo['filepath']);
                                }
                            }

                            // Thumbnail
                            $editor->set_quality($thumbimg['quality']);
                            if($thumbimg['resize']){
                                $ed_size  = $editor->get_size();
                                $ed_ratio = $ed_size['width'] / $ed_size['height'];
                                if(1 > $ed_ratio){
                                    $resized = $editor->resize($thumbimg['width'], 0, $thumbimg['crop']);
                                } else{
                                    $resized = $editor->resize(0, $thumbimg['height'], $thumbimg['crop']);
                                }
                                if(is_wp_error($resized)){
                                    @unlink($fileinfo['filepath']);
                                    @unlink($fileinfo['filepath_original']);
                                    $return = array(
                                        "error" => array("code" => $resized->get_error_code(), "message" => $resized->get_error_message()),
                                        "id"    => $fileinfo['basename'],
                                        "tip"   => "editor->resize->thumb({$thumbimg['width']}, {$thumbimg['height']}, {$thumbimg['crop']})"
                                    );

                                    return $return;
                                }
                            }

                            $saved = $editor->save($fileinfo['filepath_thumb']);
                            if(is_wp_error($saved)){
                                @unlink($fileinfo['filepath']);
                                @unlink($fileinfo['filepath_original']);
                                $return = array(
                                    "error" => array("code" => $saved->get_error_code(), "message" => $saved->get_error_message()),
                                    "id"    => $fileinfo['basename'],
                                    "tip"   => 'editor->save->thumb'
                                );

                                return $return;
                            }

                        } else{
                            copy($fileinfo['filepath'], $fileinfo['filepath_thumb']);
                        }
                        $is_webimage = true;
                    } else{
                        /*@unlink($fileinfo['filepath']);
                        $return = array("error" => array("code" => 104, "message" => __("Could not read image size. Invalid image was deleted.", 'grand-media')),
                                        "id"    => $fileinfo['basename_original']
                        );

                        return $return;
                        */
                    }
                }

                // Write media data to DB
                $title       = '';
                $description = '';
                $link        = '';
                $date        = null;
                if( !isset($post_data['set_title'])){
                    $post_data['set_title'] = 'filename';
                }
                if( !isset($post_data['set_status'])){
                    $post_data['set_status'] = isset($post_data['status'])? $post_data['status'] : 'inherit';
                }

                $keywords = array();
                // use image exif/iptc data for title and caption defaults if possible
                if($size){
                    if( !empty($image_meta)){
                        if('exif' == $post_data['set_title']){
                            if( !empty($image_meta['title']) && trim($image_meta['title'])){
                                $title = $image_meta['title'];
                            }
                        }
                        if( !empty($image_meta['caption']) && trim($image_meta['caption'])){
                            $description = $image_meta['caption'];
                        }
                        if( !empty($image_meta['keywords'])){
                            $keywords = $image_meta['keywords'];
                        }
                    }
                } else{
                    $file_meta = $this->get_file_metadata($fileinfo['filepath_original'], $fileinfo);
                    if( !empty($file_meta)){
                        if('exif' == $post_data['set_title']){
                            if( !empty($file_meta['title']) && trim($file_meta['title'])){
                                $title = $file_meta['title'];
                            }
                        }
                        if( !empty($file_meta['comment']) && trim($file_meta['comment'])){
                            $description = $file_meta['comment'];
                        }
                        if( !empty($file_meta['album']) && ( !isset($post_data['terms']['gmedia_album']) || empty($post_data['terms']['gmedia_album']))){
                            $post_data['terms']['gmedia_album'] = array($file_meta['album']);
                        }
                    }
                }
                if(('empty' != $post_data['set_title']) && empty($title)){
                    $title = $fileinfo['title'];
                }

                if('public' == $post_data['set_status']){
                    $post_data['set_status'] = 'publish';
                }

                $status = $post_data['set_status'];
                if('inherit' == $post_data['set_status']){
                    $gmedia_album = isset($post_data['terms']['gmedia_album'])? $post_data['terms']['gmedia_album'] : false;
                    if($gmedia_album && $this->is_digit($gmedia_album)){
                        $album = $gmDB->get_term($gmedia_album);
                        if(empty($album) || is_wp_error($album)){
                            $status = 'publish';
                        } else{
                            $status = $album->status;
                        }
                    } else{
                        $status = 'publish';
                    }
                }

                unset($post_data['gmuid'], $post_data['mime_type'], $post_data['set_title'], $post_data['set_status']);

                if(isset($post_data['terms']['gmedia_category']) && !empty($post_data['terms']['gmedia_category'])){
                    if( !is_array($post_data['terms']['gmedia_category'])){
                        $post_data['terms']['gmedia_category'] = explode(',', $post_data['terms']['gmedia_category']);
                    }
                } else{
                    $post_data['terms']['gmedia_category'] = array();
                }

                if(isset($post_data['terms']['gmedia_tag']) && !empty($post_data['terms']['gmedia_tag'])){
                    if( !is_array($post_data['terms']['gmedia_tag'])){
                        $post_data['terms']['gmedia_tag'] = explode(',', $post_data['terms']['gmedia_tag']);
                    }
                } else{
                    $post_data['terms']['gmedia_tag'] = array();
                }
                if( !empty($keywords)){
                    $post_data['terms']['gmedia_tag'] = array_unique(array_merge($post_data['terms']['gmedia_tag'], $keywords));
                }

                // Construct the media array
                $media_data = array(
                    'mime_type'   => $fileinfo['mime_type'],
                    'gmuid'       => $fileinfo['basename'],
                    'title'       => $title,
                    'link'        => $link,
                    'description' => $description,
                    'status'      => $status,
                    'date'        => $date
                );

                $media_data = $this->array_replace_recursive($media_data, $post_data);

                if( !current_user_can('gmedia_delete_others_media')){
                    $media_data['author'] = get_current_user_id();
                }

                // Save the data
                $id = $gmDB->insert_gmedia($media_data);

                $media_metadata = $gmDB->generate_gmedia_metadata($id, $fileinfo);
                if($size && !empty($image_meta)){
                    if(empty($media_metadata['image_meta'])){
                        $media_metadata['image_meta'] = $image_meta;
                    }
                    if( !empty($image_meta['created_timestamp'])){
                        $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_created_timestamp', $image_meta['created_timestamp']);
                    }
                    if( !empty($image_meta['GPS'])){
                        $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_gps', $image_meta['GPS']);
                    }
                }
                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $media_metadata);
                $hash_file = hash_file('md5', $fileinfo['filepath_original']);
                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_hash', $hash_file);
                $file_size = filesize($fileinfo['filepath_original']);
                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_size', $file_size);
                $return = array(
                    "success" => array("code" => 200, "message" => sprintf(__('File uploaded successful. Assigned ID: %s', 'grand-media'), $id)),
                    "id"      => $fileinfo['basename']
                );

                if((int) $gmGallery->options['delete_originals']){
                    @unlink($fileinfo['filepath_original']);
                }

                return $return;
            } else{
                $return = array("success" => array("code" => 199, "message" => $chunk . '/' . $chunks), "id" => $fileinfo['basename']);

                return $return;
            }
        } else{
            $return = array("error" => array("code" => 102, "message" => __("Failed to open output stream.", 'grand-media')), "id" => $fileinfo['basename']);

            return $return;
        }
    }

    /**
     * @param     $from_file
     * @param     $to_file
     */
    function copy_exif($from_file, $to_file){

        $size = @getimagesize($to_file);

        if($size){
            require_once(dirname(__FILE__) . '/pel/autoload.php');
            try {
                Pel::setJPEGQuality(100);
                /*
                 * We want the raw JPEG data from $scaled. Luckily, one can create a
                 * PelJpeg object from an image resource directly:
                 */
                $input_jpeg = new PelJpeg($from_file);
                /* Retrieve the original Exif data in $jpeg (if any). */
                $input_exif = $input_jpeg->getExif();
                /* If no Exif data was present, then $input_exif is null. */
                if($input_exif != null){

                    $input_tiff = $input_exif->getTiff();
                    if($input_tiff == null){
                        return;
                    }
                    $input_ifd0 = $input_tiff->getIfd();
                    if($input_ifd0 == null){
                        return;
                    }

                    $input_exif_ifd  = $input_ifd0->getSubIfd(PelIfd::EXIF);
                    $input_inter_ifd = $input_ifd0->getSubIfd(PelIfd::INTEROPERABILITY);

                    $orientation = $input_ifd0->getEntry(PelTag::ORIENTATION);
                    if($orientation != null){
                        $orientation->setValue(1);
                    }

                    if( !empty($input_ifd0)){
                        /*$x_resolution = $input_ifd0->getEntry( PelTag::X_RESOLUTION );
                        $y_resolution = $input_ifd0->getEntry( PelTag::Y_RESOLUTION );
                        if ( $x_resolution != null && $y_resolution != null ) {
                            //$x_res = $x_resolution->getValue();
                            //$y_res = $y_resolution->getValue();
                            $x_resolution->setValue( $y_res );
                            $y_resolution->setValue( $x_res );
                        }*/

                        $image_width  = $input_ifd0->getEntry(PelTag::IMAGE_WIDTH);
                        $image_length = $input_ifd0->getEntry(PelTag::IMAGE_LENGTH);
                        if($image_width != null && $image_length != null){
                            $image_width->setValue($size[0]);
                            $image_length->setValue($size[1]);
                        }
                    }
                    if( !empty($input_exif_ifd)){
                        $x_dimention = $input_exif_ifd->getEntry(PelTag::PIXEL_X_DIMENSION);
                        $y_dimention = $input_exif_ifd->getEntry(PelTag::PIXEL_Y_DIMENSION);
                        if($x_dimention != null && $y_dimention != null){
                            $x_dimention->setValue($size[0]);
                            $y_dimention->setValue($size[1]);
                        }
                    }
                    if( !empty($input_inter_ifd)){
                        $rel_image_width  = $input_inter_ifd->getEntry(PelTag::RELATED_IMAGE_WIDTH);
                        $rel_image_length = $input_inter_ifd->getEntry(PelTag::RELATED_IMAGE_LENGTH);
                        if($rel_image_width != null && $rel_image_length != null){
                            $rel_image_width->setValue($size[0]);
                            $rel_image_length->setValue($size[1]);
                        }
                    }

                    $output_jpeg = new PelJpeg($to_file);
                    $output_jpeg->setExif($input_exif);

                    /* We can now save the image with input_exif. */
                    $output_jpeg->saveFile($to_file);
                }
            } catch(PelException $e){
            }
        }
    }


    /**
     * @param            $files
     * @param            $_terms
     * @param            $move
     * @param int|string $exists
     */
    function gmedia_import_files($files, $_terms, $move, $exists = 0){
        global $wpdb, $gmGallery, $gmDB;

        if(ob_get_level() == 0){
            ob_start();
        }
        $eol = '</pre>' . PHP_EOL;

        $gmedia_album = isset($_terms['gmedia_album'])? $_terms['gmedia_album'] : false;
        if($gmedia_album && $this->is_digit($gmedia_album)){
            $album = $gmDB->get_term($gmedia_album);
            if(empty($album) || is_wp_error($album)){
                $_status = 'publish';
            } else{
                $_status    = $album->status;
                $album_name = $album->name;
            }
        } else{
            $_status = 'publish';
        }

        if(isset($_terms['gmedia_album']) && !empty($_terms['gmedia_album'])){
            if(is_array($_terms['gmedia_album'])){
                $_terms['gmedia_album'] = trim(reset($_terms['gmedia_album']));
            }
        } else{
            $_terms['gmedia_album'] = '';
        }
        if(isset($_terms['gmedia_category']) && !empty($_terms['gmedia_category'])){
            if( !is_array($_terms['gmedia_category'])){
                $_terms['gmedia_category'] = array_filter(array_map('trim', explode(',', $_terms['gmedia_category'])));
            }
        } else{
            $_terms['gmedia_category'] = array();
        }
        if(isset($_terms['gmedia_tag']) && !empty($_terms['gmedia_tag'])){
            if( !is_array($_terms['gmedia_tag'])){
                $_terms['gmedia_tag'] = array_filter(array_map('trim', explode(',', $_terms['gmedia_tag'])));
            }
        } else{
            $_terms['gmedia_tag'] = array();
        }

        $c = count($files);
        $i = 0;
        foreach($files as $file){

            $title       = '';
            $description = '';
            $link        = '';
            $status      = $_status;
            $terms       = $_terms;

            if(is_array($file)){
                if(isset($file['file'])){
                    extract($file);
                } else{
                    _e('Something went wrong...', 'grand-media');
                    die();
                }
            }

            wp_ob_end_flush_all();
            flush();

            $i ++;
            $prefix    = "\n<pre>$i/$c - ";
            $prefix_ko = "\n<pre class='ko'>$i/$c - ";

            if( !is_file($file)){
                echo $prefix_ko . sprintf(__('File not exists: %s', 'grand-media'), $file) . $eol;
                continue;
            }

            if('skip' === $exists){
                $file_suffix = false;
            } else{
                $file_suffix = $exists;
            }
            $fileinfo = $this->fileinfo($file, $file_suffix);

            if(('skip' === $exists) && is_file($fileinfo['filepath'])){
                echo $prefix . $fileinfo['basename_original'] . ': ' . __('file with the same name already exists', 'grand-media') . $eol;
                continue;
            }


            // try to make grand-media dir if not exists
            if( !wp_mkdir_p($fileinfo['dirpath'])){
                echo $prefix_ko . sprintf(__('Unable to create directory `%s`. Is its parent directory writable by the server?', 'grand-media'), $fileinfo['dirpath']) . $eol;
                continue;
            }
            // Check if grand-media dir is writable
            if( !is_writable($fileinfo['dirpath'])){
                @chmod($fileinfo['dirpath'], 0755);
                if( !is_writable($fileinfo['dirpath'])){
                    echo $prefix_ko . sprintf(__('Directory `%s` or its subfolders are not writable by the server.', 'grand-media'), dirname($fileinfo['dirpath'])) . $eol;
                    continue;
                }
            }

            if(($file != $fileinfo['filepath']) && !copy($file, $fileinfo['filepath'])){
                echo $prefix_ko . sprintf(__("Can't copy file from `%s` to `%s`", 'grand-media'), $file, $fileinfo['filepath']) . $eol;
                continue;
            }

            $this->file_chmod($fileinfo['filepath']);

            $hash_file    = hash_file('md5', $fileinfo['filepath']);
            $duplicate_id = $wpdb->get_var($wpdb->prepare("SELECT gmedia_id FROM {$wpdb->prefix}gmedia_meta WHERE meta_key = '_hash' AND meta_value = %s LIMIT 1;", $hash_file));
            if($duplicate_id){
                @unlink($fileinfo['filepath']);
                echo $prefix_ko . $fileinfo['basename_original'] . ": " . sprintf(__("Seems like it is duplicate of file with ID #%d", 'grand-media'), $duplicate_id) . $eol;
                continue;
            }

            $size        = false;
            $is_webimage = false;
            if('image' == $fileinfo['dirname']){
                /** WordPress Image Administration API */
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                $size = @getimagesize($fileinfo['filepath']);
                if($size && file_is_displayable_image($fileinfo['filepath'])){
                    $extensions = array('1' => 'GIF', '2' => 'JPG', '3' => 'PNG', '6' => 'BMP');
                    if(function_exists('memory_get_usage')){
                        switch($extensions[ $size[2] ]){
                            case 'GIF':
                                $CHANNEL = 1;
                                break;
                            case 'JPG':
                                $CHANNEL = $size['channels'];
                                break;
                            case 'PNG':
                                $CHANNEL = 3;
                                break;
                            case 'BMP':
                            default:
                                $CHANNEL = 6;
                                break;
                        }
                        $MB                = 1048576;  // number of bytes in 1M
                        $K64               = 65536;    // number of bytes in 64K
                        $TWEAKFACTOR       = 1.8;     // Or whatever works for you
                        $memoryNeeded      = round(($size[0] * $size[1] * $size['bits'] * $CHANNEL / 8 + $K64) * $TWEAKFACTOR);
                        $memoryNeeded      = memory_get_usage() + $memoryNeeded;
                        $current_limit     = @ini_get('memory_limit');
                        $current_limit_int = intval($current_limit);
                        if(false !== strpos($current_limit, 'M')){
                            $current_limit_int *= $MB;
                        }
                        if(false !== strpos($current_limit, 'G')){
                            $current_limit_int *= 1024;
                        }

                        if(- 1 != $current_limit && $memoryNeeded > $current_limit_int){
                            $newLimit = $current_limit_int / $MB + ceil(($memoryNeeded - $current_limit_int) / $MB);
                            if($newLimit < 256){
                                $newLimit = 256;
                            }
                            @ini_set('memory_limit', $newLimit . 'M');
                        }
                    }

                    if( !wp_mkdir_p($fileinfo['dirpath_thumb'])){
                        echo $prefix_ko . sprintf(__('Unable to create directory `%s`. Is its parent directory writable by the server?', 'grand-media'), $fileinfo['dirpath_thumb']) . $eol;
                        continue;
                    }
                    if( !is_writable($fileinfo['dirpath_thumb'])){
                        @chmod($fileinfo['dirpath_thumb'], 0755);
                        if( !is_writable($fileinfo['dirpath_thumb'])){
                            @unlink($fileinfo['filepath']);
                            echo $prefix_ko . sprintf(__('Directory `%s` is not writable by the server.', 'grand-media'), $fileinfo['dirpath_thumb']) . $eol;
                            continue;
                        }
                    }
                    if( !wp_mkdir_p($fileinfo['dirpath_original'])){
                        echo $prefix_ko . sprintf(__('Unable to create directory `%s`. Is its parent directory writable by the server?', 'grand-media'), $fileinfo['dirpath_original']) . $eol;
                        continue;
                    }
                    if( !is_writable($fileinfo['dirpath_original'])){
                        @chmod($fileinfo['dirpath_original'], 0755);
                        if( !is_writable($fileinfo['dirpath_original'])){
                            @unlink($fileinfo['filepath']);
                            echo $prefix_ko . sprintf(__('Directory `%s` is not writable by the server.', 'grand-media'), $fileinfo['dirpath_original']) . $eol;
                            continue;
                        }
                    }

                    // Optimized image
                    $webimg   = $gmGallery->options['image'];
                    $thumbimg = $gmGallery->options['thumb'];

                    $webimg['resize'] = (($webimg['width'] < $size[0]) || ($webimg['height'] < $size[1]))? true : false;

                    if($webimg['resize']){
                        rename($fileinfo['filepath'], $fileinfo['filepath_original']);
                    } else{
                        copy($fileinfo['filepath'], $fileinfo['filepath_original']);
                    }

                    $size_ratio = $size[0] / $size[1];

                    $angle      = 0;
                    $image_meta = @$this->wp_read_image_metadata($fileinfo['filepath_original']);
                    if( !empty($image_meta['orientation'])){
                        switch($image_meta['orientation']){
                            case 3:
                                $angle = 180;
                                break;
                            case 6:
                                $angle      = 270;
                                $size_ratio = $size[1] / $size[0];
                                break;
                            case 8:
                                $angle      = 90;
                                $size_ratio = $size[1] / $size[0];
                                break;
                        }
                    }

                    $thumbimg['resize'] = (((1 >= $size_ratio) && ($thumbimg['width'] > $size[0])) || ((1 <= $size_ratio) && ($thumbimg['height'] > $size[1])))? false : true;

                    if($webimg['resize'] || $thumbimg['resize'] || $angle){
                        $editor = wp_get_image_editor($fileinfo['filepath_original']);
                        if(is_wp_error($editor)){
                            @unlink($fileinfo['filepath']);
                            @unlink($fileinfo['filepath_original']);
                            echo $prefix_ko . $fileinfo['basename'] . " (wp_get_image_editor): " . $editor->get_error_message() . $eol;
                            continue;
                        }

                        if($angle){
                            $editor->rotate($angle);
                        }

                        if($webimg['resize'] || $angle){
                            $editor->set_quality($webimg['quality']);

                            if($webimg['resize']){
                                $resized = $editor->resize($webimg['width'], $webimg['height'], $webimg['crop']);
                                if(is_wp_error($resized)){
                                    @unlink($fileinfo['filepath']);
                                    @unlink($fileinfo['filepath_original']);
                                    echo $prefix_ko . $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->webimage({$webimg['width']}, {$webimg['height']}, {$webimg['crop']})): " . $resized->get_error_message() . $eol;
                                    continue;
                                }
                            }

                            $saved = $editor->save($fileinfo['filepath']);
                            if(is_wp_error($saved)){
                                @unlink($fileinfo['filepath']);
                                @unlink($fileinfo['filepath_original']);
                                echo $prefix_ko . $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->webimage): " . $saved->get_error_message() . $eol;
                                continue;
                            }

                            if(('JPG' == $extensions[ $size[2] ]) && !(extension_loaded('imagick') || class_exists("Imagick"))){
                                $this->copy_exif($fileinfo['filepath_original'], $fileinfo['filepath']);
                            }

                        }

                        // Thumbnail
                        $editor->set_quality($thumbimg['quality']);
                        if($thumbimg['resize']){
                            $ed_size  = $editor->get_size();
                            $ed_ratio = $ed_size['width'] / $ed_size['height'];
                            if(1 > $ed_ratio){
                                $resized = $editor->resize($thumbimg['width'], 0, $thumbimg['crop']);
                            } else{
                                $resized = $editor->resize(0, $thumbimg['height'], $thumbimg['crop']);
                            }
                            if(is_wp_error($resized)){
                                @unlink($fileinfo['filepath']);
                                @unlink($fileinfo['filepath_original']);
                                echo $prefix_ko . $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->thumb({$thumbimg['width']}, {$thumbimg['height']}, {$thumbimg['crop']})): " . $resized->get_error_message() . $eol;
                                continue;
                            }
                        }

                        $saved = $editor->save($fileinfo['filepath_thumb']);
                        if(is_wp_error($saved)){
                            @unlink($fileinfo['filepath']);
                            @unlink($fileinfo['filepath_original']);
                            echo $prefix_ko . $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->thumb): " . $saved->get_error_message() . $eol;
                            continue;
                        }
                    } else{
                        copy($fileinfo['filepath'], $fileinfo['filepath_thumb']);
                    }
                    $is_webimage = true;
                } else{
                    @unlink($fileinfo['filepath']);
                    echo $prefix_ko . $fileinfo['basename'] . ": " . __("Could not read image size. Invalid image was deleted.", 'grand-media') . $eol;
                    continue;
                }
            }

            // Write media data to DB
            if($size){
                if( !empty($image_meta)){
                    if(empty($title) && !empty($image_meta['title']) && trim($image_meta['title']) && !is_numeric(sanitize_title($image_meta['title']))){
                        $title = $image_meta['title'];
                    }
                    if(empty($description) && !empty($image_meta['caption']) && trim($image_meta['caption'])){
                        $description = $image_meta['caption'];
                    }
                    if( !empty($image_meta['keywords'])){
                        $terms['gmedia_tag'] = array_unique(array_merge((array) $_terms['gmedia_tag'], $image_meta['keywords']));
                    }
                }
            } else{
                $file_meta = $this->get_file_metadata($fileinfo['filepath_original'], $fileinfo);
                if( !empty($file_meta)){
                    if(empty($title) && !empty($file_meta['title']) && trim($file_meta['title']) && !is_numeric(sanitize_title($file_meta['title']))){
                        $title = $file_meta['title'];
                    }
                    if(empty($description) && !empty($file_meta['comment']) && trim($file_meta['comment'])){
                        $description = $file_meta['comment'];
                    }
                    if(empty($terms['gmedia_album']) && !empty($file_meta['album'])){
                        $terms['gmedia_album'] = array($file_meta['album']);
                    }
                }
            }

            if(empty($title)){
                $title = $fileinfo['title'];
            }

            // Construct the media_data array
            $media_data = array(
                'mime_type'   => $fileinfo['mime_type'],
                'gmuid'       => $fileinfo['basename'],
                'title'       => $title,
                'link'        => $link,
                'description' => $description,
                'status'      => $status,
                'terms'       => $terms
            );

            if( !current_user_can('gmedia_delete_others_media')){
                $media_data['author'] = get_current_user_id();
            }

            // Save the data
            $id = $gmDB->insert_gmedia($media_data);

            $media_metadata = $gmDB->generate_gmedia_metadata($id, $fileinfo);
            if($size && !empty($image_meta)){
                if(empty($media_metadata['image_meta'])){
                    $media_metadata['image_meta'] = $image_meta;
                }
                if( !empty($image_meta['created_timestamp'])){
                    $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_created_timestamp', $image_meta['created_timestamp']);
                }
                if( !empty($image_meta['GPS'])){
                    $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_gps', $image_meta['GPS']);
                }
            }
            $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $media_metadata);
            $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_hash', $hash_file);
            $file_size = filesize($fileinfo['filepath_original']);
            $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_size', $file_size);

            echo $prefix . $fileinfo['basename'] . ': <span class="ok">' . sprintf(__('success (ID #%s)', 'grand-media'), $id) . '</span>' . $eol;

            if((int) $gmGallery->options['delete_originals']){
                @unlink($fileinfo['filepath_original']);
            }
            if($move){
                @unlink($file);
            }

        }

        echo '<p><b>' . __('Category') . ':</b> ' . (!empty($_terms['gmedia_category'])? esc_html(implode(', ', $_terms['gmedia_category'])) : '-') . PHP_EOL;
        echo '<br /><b>' . __('Album') . ':</b> ' . (!empty($_terms['gmedia_album'])? (isset($album_name)? $album_name : esc_html($_terms['gmedia_album'])) : '-') . PHP_EOL;
        echo '<br /><b>' . __('Tags') . ':</b> ' . (!empty($_terms['gmedia_tag'])? esc_html(implode(', ', $_terms['gmedia_tag'])) : '-') . '</p>' . PHP_EOL;

        wp_ob_end_flush_all();
        flush();
    }

    /**
     * @param            $gmid
     */
    function duplicate_gmedia($gmid){
        global $gmDB;

        $gmedia = $gmDB->get_gmedia($gmid);
        if( !$gmedia || is_wp_error($gmedia)){
            return;
        }

        $fileinfo = $this->fileinfo($gmedia->gmuid);

        if(is_file($fileinfo['dirpath_original'] . '/' . $gmedia->gmuid)){
            $file = $fileinfo['dirpath_original'] . '/' . $gmedia->gmuid;
            copy($file, $fileinfo['filepath_original']);
        }

        if('image' == $fileinfo['dirname']){
            copy($fileinfo['dirpath'] . '/' . $gmedia->gmuid, $fileinfo['filepath']);
            copy($fileinfo['dirpath_thumb'] . '/' . $gmedia->gmuid, $fileinfo['filepath_thumb']);
        }

        // Construct the media_data array
        $media_data = array(
            'mime_type'   => $fileinfo['mime_type'],
            'gmuid'       => $fileinfo['basename'],
            'title'       => $gmedia->title,
            'link'        => $gmedia->link,
            'description' => $gmedia->description,
            'status'      => $gmedia->status,
            'terms'       => array(
                'gmedia_album'    => $gmDB->get_gmedia_terms($gmedia->ID, array('gmedia_album'), array('fields' => 'ids')),
                'gmedia_category' => $gmDB->get_gmedia_terms($gmedia->ID, array('gmedia_category'), array('fields' => 'ids')),
                'gmedia_tag'      => $gmDB->get_gmedia_terms($gmedia->ID, array('gmedia_tag'), array('fields' => 'ids', 'order' => 'term_order'))
            )
        );

        $media_data['author'] = get_current_user_id();

        // Save the data
        $id = $gmDB->insert_gmedia($media_data);

        $media_metadata = $gmDB->get_metadata('gmedia', $gmedia->ID);
        foreach($media_metadata as $key => $values){
            //if($this->is_protected_meta($key, 'gmedia')){
            foreach($values as $val){
                $gmDB->add_metadata($meta_type = 'gmedia', $id, $key, $val);
            }
            //}
        }

    }

    /**
     * @param $gmid
     *
     * @return array
     */
    function recreate_images_from_original($gmid){
        global $gmDB, $gmGallery;

        $item = $gmDB->get_gmedia($gmid);
        if( !empty($item)){

            $type = explode('/', $item->mime_type);
            $type = $type[0];
            if('image' !== $type){
                $out = array('error' => $this->alert('danger', sprintf(__('#%d: Not image type', 'grand-media'), $item->ID)));

                return $out;
            }

            $gmedia  = array();
            $fail    = '';
            $success = '';

            if((int) $item->author != get_current_user_id()){
                if( !current_user_can('gmedia_edit_others_media')){
                    $out = array('error' => $this->alert('danger', __('You are not allowed to edit others media', 'grand-media')));

                    return $out;
                }
            }
            $meta               = $gmDB->get_metadata('gmedia', $item->ID);
            $metadata           = $meta['_metadata'][0];
            $gmedia['ID']       = $gmid;
            $gmedia['date']     = $item->date;
            $gmedia['modified'] = current_time('mysql');
            $gmedia['author']   = $item->author;

            $webimg   = $gmGallery->options['image'];
            $thumbimg = $gmGallery->options['thumb'];

            $fileinfo          = $this->fileinfo($item->gmuid, false);
            $is_file_original  = is_file($fileinfo['filepath_original']);
            $filepath_original = $is_file_original? $fileinfo['filepath_original'] : (is_file($fileinfo['filepath'])? $fileinfo['filepath'] : null);
            if( !$filepath_original){
                $out = array('error' => $this->alert('danger', __('Original file does not exists', 'grand-media')));

                return $out;
            }
            $size = @getimagesize($filepath_original);

            do {
                $extensions = array('1' => 'GIF', '2' => 'JPG', '3' => 'PNG', '6' => 'BMP');
                if(function_exists('memory_get_usage')){
                    switch($extensions[ $size[2] ]){
                        case 'GIF':
                            $CHANNEL = 1;
                            break;
                        case 'JPG':
                            $CHANNEL = $size['channels'];
                            break;
                        case 'PNG':
                            $CHANNEL = 3;
                            break;
                        case 'BMP':
                        default:
                            $CHANNEL = 6;
                            break;
                    }
                    $MB                = 1048576;  // number of bytes in 1M
                    $K64               = 65536;    // number of bytes in 64K
                    $TWEAKFACTOR       = 1.8;     // Or whatever works for you
                    $memoryNeeded      = round(($size[0] * $size[1] * $size['bits'] * $CHANNEL / 8 + $K64) * $TWEAKFACTOR);
                    $memoryNeeded      = memory_get_usage() + $memoryNeeded;
                    $current_limit     = @ini_get('memory_limit');
                    $current_limit_int = intval($current_limit);
                    if(false !== strpos($current_limit, 'M')){
                        $current_limit_int *= $MB;
                    }
                    if(false !== strpos($current_limit, 'G')){
                        $current_limit_int *= 1024;
                    }

                    if(- 1 != $current_limit && $memoryNeeded > $current_limit_int){
                        $newLimit = $current_limit_int / $MB + ceil(($memoryNeeded - $current_limit_int) / $MB);
                        if($newLimit < 256){
                            $newLimit = 256;
                        }
                        @ini_set('memory_limit', $newLimit . 'M');
                    }
                }

                $size_ratio = $size[0] / $size[1];

                $angle = 0;
                if($is_file_original){
                    $image_meta = @$this->wp_read_image_metadata($filepath_original);
                    if( !empty($image_meta['orientation'])){
                        switch($image_meta['orientation']){
                            case 3:
                                $angle = 180;
                                break;
                            case 6:
                                $angle      = 270;
                                $size_ratio = $size[1] / $size[0];
                                break;
                            case 8:
                                $angle      = 90;
                                $size_ratio = $size[1] / $size[0];
                                break;
                        }
                    }
                    $webimg['resize'] = (($webimg['width'] < $size[0]) || ($webimg['height'] < $size[1]))? true : false;
                } else{
                    $webimg['resize'] = false;
                }

                $thumbimg['resize'] = (((1 >= $size_ratio) && ($thumbimg['width'] > $size[0])) || ((1 <= $size_ratio) && ($thumbimg['height'] > $size[1])))? false : true;

                if($webimg['resize'] || $thumbimg['resize'] || $angle){

                    $editor = wp_get_image_editor($filepath_original);
                    if(is_wp_error($editor)){
                        $fail = $fileinfo['basename'] . " (wp_get_image_editor): " . $editor->get_error_message();
                        break;
                    }

                    if($is_file_original){
                        if($angle){
                            $editor->rotate($angle);
                        }

                        if($webimg['resize'] || $angle){
                            // Web-image
                            $editor->set_quality($webimg['quality']);

                            if($webimg['resize']){
                                $resized = $editor->resize($webimg['width'], $webimg['height'], $webimg['crop']);
                                if(is_wp_error($resized)){
                                    $fail = $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->webimage({$webimg['width']}, {$webimg['height']}, {$webimg['crop']})): " . $resized->get_error_message();
                                    break;
                                }
                            }

                            $saved = $editor->save($fileinfo['filepath']);
                            if(is_wp_error($saved)){
                                $fail = $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->webimage): " . $saved->get_error_message();
                                break;
                            }
                            if(('JPG' == $extensions[ $size[2] ]) && !(extension_loaded('imagick') || class_exists("Imagick"))){
                                $this->copy_exif($filepath_original, $fileinfo['filepath']);
                            }
                        } else{
                            @copy($filepath_original, $fileinfo['filepath']);
                        }
                    }

                    // Thumbnail
                    $editor->set_quality($thumbimg['quality']);
                    if($thumbimg['resize']){
                        $ed_size  = $editor->get_size();
                        $ed_ratio = $ed_size['width'] / $ed_size['height'];
                        if(1 > $ed_ratio){
                            $resized = $editor->resize($thumbimg['width'], 0, $thumbimg['crop']);
                        } else{
                            $resized = $editor->resize(0, $thumbimg['height'], $thumbimg['crop']);
                        }
                        if(is_wp_error($resized)){
                            $fail = $fileinfo['basename'] . " (" . $resized->get_error_code() . " | editor->resize->thumb({$thumbimg['width']}, {$thumbimg['height']}, {$thumbimg['crop']})): " . $resized->get_error_message();
                            break;
                        }
                    }

                    $saved = $editor->save($fileinfo['filepath_thumb']);
                    if(is_wp_error($saved)){
                        $fail = $fileinfo['basename'] . " (" . $saved->get_error_code() . " | editor->save->thumb): " . $saved->get_error_message();
                        break;
                    }

                } else{
                    if($is_file_original){
                        @copy($filepath_original, $fileinfo['filepath']);
                    }
                    @copy($filepath_original, $fileinfo['filepath_thumb']);
                }

                $id = $gmDB->insert_gmedia($gmedia);

                $new_metadata         = $gmDB->generate_gmedia_metadata($id, $fileinfo);
                $metadata['thumb']    = $new_metadata['thumb'];
                $metadata['web']      = $new_metadata['web'];
                $metadata['original'] = $new_metadata['original'];

                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_metadata', $metadata);
                $gmDB->update_metadata($meta_type = 'gmedia', $id, $meta_key = '_modified', 0);

                $success = sprintf(__('Image "%d" restored from backup and saved', 'grand-media'), $id);
            } while(0);

            if(empty($fail)){
                $out = array('msg' => $this->alert('info', $success), 'modified' => $gmedia['modified']);
            } else{
                $out = array('error' => $this->alert('danger', $fail));
            }

        } else{
            $out = array('error' => $this->alert('danger', sprintf(__('#%d: No image in database', 'grand-media'), $gmid)));
        }

        return $out;
    }

    /**
     * @param string $service
     *
     * @return array|bool json
     */
    function app_service($service){
        global $gmGallery, $gmDB, $wp_version;

	    if(empty($_SERVER['HTTP_X_REAL_IP']) && ('127.0.0.1' == $_SERVER['REMOTE_ADDR'] || '::1' == $_SERVER['REMOTE_ADDR'])){
            return false;
        }
        if( !current_user_can('manage_options')){
            die('-1');
        }
        if( !$service){
            die('0');
        }

        $result  = array();
        $data    = array();
        $options = $gmGallery->options;

        if($service == 'app_deactivate'){
            $options['mobile_app'] = 0;
        }

        $data['site_email'] = get_option('admin_email');
        if(in_array($service, array('app_updateinfo')) && !is_email($data['site_email'])){
            $result['error'][] = __('Invalid email', 'grand-media');
        } else{

            $url       = home_url();
            $post_data = array('url' => $url);

            if(in_array($service, array('app_deactivateplugin', 'app_uninstallplugin'))){
                if( !empty($options['site_ID'])){
                    $post_data['site_id'] = $options['site_ID'];
                    wp_remote_post('https://gmediaservice.codeasily.com/?gmService=' . $service, array(
                        'method'  => 'POST',
                        'timeout' => 5,
                        'blocking'  => false,
                        'sslverify' => false,
                        'body'    => $post_data,

                    ));
                }

                return false;
            }

            $hash = 'gmedia_' . wp_generate_password('6', false);

            if(in_array($service, array('app_activate', 'app_updateinfo'))){
                $status = 1;
            } else{
                $status = $options['mobile_app'];
            }
            $install_date = get_option('gmediaInstallDate');

            $data['service']        = $service;
            $data['site_hash']      = $hash;
            $data['site_ID']        = $options['site_ID'];
            $data['title']          = get_bloginfo('name');
            $data['description']    = get_bloginfo('description');
            $data['url']            = $url;
            $data['license']        = $options['license_key'];
            $data['status']         = $status;
            $data['install_date']   = (int)$install_date;
            $data['counters']       = $gmDB->count_gmedia();
            $data['php_version']    = phpversion();
            $data['wp_version']     = $wp_version;
            $data['gmedia_version'] = GMEDIA_VERSION;
            $data['locale']         = get_locale();

            $theme         = wp_get_theme();
            $data['theme'] = array(
                'name'      => $theme->get('Name'),
                'version'   => $theme->get('Version'),
                'theme_uri' => $theme->get('ThemeURI')
            );

            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $active_plugins  = get_option('active_plugins');
            $plugins         = get_plugins();
            $data['plugins'] = array();
            foreach($active_plugins as $p){
                if(isset($plugins[ $p ])){
                    $data['plugins'][ $p ] = array(
                        'name'       => $plugins[ $p ]['Name'],
                        'version'    => $plugins[ $p ]['Version'],
                        'plugin_uri' => $plugins[ $p ]['PluginURI']
                    );
                }
            }

            $tagslist     = $gmDB->get_terms('gmedia_tag', array(
                'hide_empty'    => true,
                'fields'        => 'names_count',
                'orderby'       => 'count',
                'order'         => 'DESC',
                'no_found_rows' => true
            ));
            $data['tags'] = array();
            if( !is_wp_error($tagslist)){
                foreach($tagslist as $tag){
                    if($tag['count'] < 10){
                        break;
                    }
                    $data['tags'][] = $tag['name'];
                }
            }

            set_transient($hash, $data, 45);

            $post_data['hash'] = $hash;
            $gms_post          = wp_remote_post('https://gmediaservice.codeasily.com/?gmService=' . $service, array(
                'method'  => 'POST',
                'timeout' => 45,
                //'blocking'  => false,
                'sslverify' => false,
                'body'    => $post_data
            ));
            if(is_wp_error($gms_post)){
                $result['error'][] = $gms_post->get_error_message();
            }
            $gms_post_body = wp_remote_retrieve_body($gms_post);
            $_result       = (array) json_decode($gms_post_body);
            if(isset($_result['error'])){
                if( !isset($result['error'])){
                    $result['error'] = array();
                }
                $_result['error'] = (array) $_result['error'];
                $_result['error'] = array_filter($_result['error'], 'is_string');
                $result['error']  = array_merge($result['error'], $_result['error']);
            } else{
                $result = array_merge($_result, $result);
//                   $result['gms_the_data'] = $data;
//                   $result['gms_post'] = $gms_post;
//                   $result['gms_post_body'] = $gms_post_body;
                if(isset($result['message'])){
                    $result['message'] = $this->alert('info', $result['message']);
                }

                if(isset($result['site_ID'])){
                    $options['site_ID'] = $result['site_ID'];
                }
                if(isset($result['mobile_app'])){
                    $options['mobile_app'] = $result['mobile_app'];
                }
            }
            if(isset($result['error'])){
                $result['error'] = $this->alert('danger', $result['error']);
            }
        }
        update_option('gmediaOptions', $options);

        if(in_array($service, array('app_activate', 'app_updateinfo'))){
            wp_clear_scheduled_hook('gmedia_app_cronjob');
            wp_schedule_event(time(), 'gmedia_app', 'gmedia_app_cronjob');
        }

        return $result;
    }

    /**
     * @param null $modules
     */
    function modules_update($modules = null){
        $wp_installing = (bool) (defined('WP_INSTALLING') && WP_INSTALLING);
        if($wp_installing){
            return;
        }

        if( !is_array($modules)){
            $modules = get_gmedia_modules();
        }
        if(isset($modules['error'])){
            return;
        }

        global $gmGallery;
        $modules_update_count = 0;

        foreach($modules['in'] as $module){
            if( !empty($module['update']) && 'remote' != $module['place']){
                $modules_update_count ++;
            }
        }

        $gmGallery->options                   = get_option('gmediaOptions');
        $gmGallery->options['modules_update'] = $modules_update_count;
        $gmGallery->options['modules_new']    = isset($modules['out'])? count($modules['out']) : 0;

        update_option('gmediaOptions', $gmGallery->options);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    function i18n_exif_name($key){
        $_key     = strtolower($key);
        $tagnames = array(
            'aperture'          => __('Aperture', 'grand-media'),
            'credit'            => __('Credit', 'grand-media'),
            'camera'            => __('Camera', 'grand-media'),
            'model'             => __('Camera', 'grand-media'),
            'lens'              => __('Lens', 'grand-media'),
            'lensmake'          => __('Lens Make', 'grand-media'),
            'caption'           => __('Caption', 'grand-media'),
            'date'              => __('Date/Time', 'grand-media'),
            'created_timestamp' => __('Timestamp', 'grand-media'),
            'created_date'      => __('Date Created', 'grand-media'),
            'created_time'      => __('Time Created', 'grand-media'),
            'copyright'         => __('Copyright', 'grand-media'),
            'focallength'       => __('Focal length', 'grand-media'),
            'focallength35'     => __('Focal length in 35mm Film', 'grand-media'),
            'iso'               => __('ISO', 'grand-media'),
            'exposure'          => __('Exposure Time', 'grand-media'),
            'shutter_speed'     => __('Shutter speed', 'grand-media'),
            'title'             => __('Title', 'grand-media'),
            'author'            => __('Author', 'grand-media'),
            'tags'              => __('Tags', 'grand-media'),
            'subject'           => __('Subject', 'grand-media'),
            'make'              => __('Make', 'grand-media'),
            'status'            => __('Edit Status', 'grand-media'),
            'category'          => __('Category', 'grand-media'),
            'keywords'          => __('Keywords', 'grand-media'),
            'position'          => __('Author Position', 'grand-media'),
            'GPS'               => __('GPS', 'grand-media'),
            'lat'               => __('Latitude', 'grand-media'),
            'lng'               => __('Longtitude', 'grand-media'),
            'city'              => __('City', 'grand-media'),
            'location'          => __('Location', 'grand-media'),
            'state'             => __('Province/State', 'grand-media'),
            'country_code'      => __('Country code', 'grand-media'),
            'country'           => __('Country', 'grand-media'),
            'headline'          => __('Headline', 'grand-media'),
            'source'            => __('Source', 'grand-media'),
            'contact'           => __('Contact', 'grand-media'),
            'last_modfied'      => __('Last modified', 'grand-media'),
            'tool'              => __('Program tool', 'grand-media'),
            'software'          => __('Software', 'grand-media'),
            'format'            => __('Format', 'grand-media'),
            'width'             => __('Width', 'grand-media'),
            'height'            => __('Height', 'grand-media'),
            'flash'             => __('Flash', 'grand-media'),
            'flashdata'         => __('Flash', 'grand-media'),
            'orientation'       => __('Orientation', 'grand-media')
        );

        if(isset($tagnames[ $_key ])){
            $key = $tagnames[ $_key ];
        }

        return ($key);
    }

    /**
     * Determine whether a meta key is protected.
     *
     * @param string $meta_key Meta key
     * @param string|null $meta_type
     *
     * @return bool True if the key is protected, false otherwise.
     */
    function is_protected_meta($meta_key, $meta_type = null){
        $protected = ('_' == $meta_key[0]);

        return apply_filters('is_protected_gmedia_meta', $protected, $meta_key, $meta_type);
    }

    /**
     * Display custom fields form fields.
     * @since 1.6.3
     *
     * @param int $gmedia_id
     * @param string $meta_type
     */
    function gmedia_custom_meta_box($gmedia_id, $meta_type = 'gmedia'){
        global $gmDB;

        if(empty($gmedia_id)){
            return;
        }

        if( !in_array($meta_type, array('gmedia', 'gmedia_term'))){
            $meta_type = 'gmedia';
        }
        ?>
        <fieldset id="gmediacustomstuff_<?php echo $gmedia_id; ?>" class="gmediacustomstuff" data-metatype="<?php echo $meta_type; ?>">
            <legend class="label label-default" style="font-size:85%;"><?php _e('Custom Fields', 'grand-media'); ?></legend>
            <?php
            $metadata = $gmDB->has_meta($gmedia_id, $meta_type);
            foreach($metadata as $key => $value){
                if($this->is_protected_meta($metadata[ $key ]['meta_key'], $meta_type)){
                    unset($metadata[ $key ]);
                }
            } ?>
            <div class="row">
                <?php if( !empty($metadata)){
                    //$count = 0;
                    foreach($metadata as $entry){
                        echo $this->_list_meta_item($entry, $meta_type);
                    }
                } ?>
            </div>
            <a href="#newCustomFieldModal" data-gmid="<?php echo $gmedia_id; ?>" class="newcustomfield-modal label label-primary"><?php _e('Add New Custom Field', 'grand-media') ?></a>
        </fieldset>
        <p><?php _e('Custom fields can be used to add extra metadata to a gmedia item that developer can use in their templates.'); ?></p>
        <?php
    }

    /**
     * @since 1.6.3
     *
     * @param        $entry
     * @param string $meta_type
     *
     * @return string|void
     */
    function _list_meta_item($entry, $meta_type = 'gmedia'){
        if(is_serialized($entry['meta_value'])){
            if(is_serialized_string($entry['meta_value'])){
                // This is a serialized string, so we should display it.
                $entry['meta_value'] = maybe_unserialize($entry['meta_value']);
            } else{
                // This is a serialized array/object so we should NOT display it.
                return;
            }
        }

        $entry['meta_key']   = esc_attr($entry['meta_key']);
        $entry['meta_value'] = esc_textarea($entry['meta_value']); // using a <textarea />
        $entry['meta_id']    = (int) $entry['meta_id'];

        $colsm = ('gmedia' == $meta_type)? 6 : 4;
        //$delete_nonce = wp_create_nonce( 'gmedia_custom_field', '_wpnonce_custom_field' );
        $item = '
			<div class="form-group col-sm-' . $colsm . ' gm-custom-meta-' . $entry['meta_id'] . '">
				<span class="delete-custom-field glyphicon glyphicon-remove pull-right text-danger"></span>
				<label>' . $entry['meta_key'] . '</label>
				<textarea name="meta[' . $entry['meta_id'] . ']" class="gmedia-custom-field gm-custom-field-' . $entry['meta_id'] . ' vert form-control input-sm" style="height:30px;" placeholder="' . __('Value', 'grand-media') . '" rows="1" cols="30">' . $entry['meta_value'] . '</textarea>
			</div>
		';

        return $item;
    }

    /**
     * Prints the form in the Custom Fields meta box.
     * @since 1.6.3
     *
     * @param string $meta_type
     *
     * @return string
     */
    function meta_form($meta_type = 'gmedia'){
        global $wpdb;

        if( !in_array($meta_type, array('gmedia', 'gmedia_term'))){
            $meta_type = 'gmedia';
        }

        /**
         * Filter the number of custom fields to retrieve for the drop-down
         * in the Custom Fields meta box.
         *
         * @param int $limit Number of custom fields to retrieve. Default 30.
         */
        $limit = apply_filters('gmediameta_form_limit', 30);
        $sql   = "SELECT meta_key
		FROM {$wpdb->prefix}{$meta_type}_meta
		GROUP BY meta_key
		HAVING meta_key NOT LIKE %s
		ORDER BY meta_key
		LIMIT %d";
        $keys  = $wpdb->get_col($wpdb->prepare($sql, addcslashes('_', '_%\\') . '%', $limit));

        $meta_form = '
		<div id="newmeta" class="newmeta">
			<div class="row">
				<div class="form-group col-sm-6">
					<label>' . _x('Name', 'meta name') . '</label>';
        if($keys){
            natcasesort($keys);
            $meta_form .= '
						<select class="metakeyselect form-control input-sm" name="metakeyselect">
							<option value="">' . __('&mdash; Select &mdash;') . '</option>';
            foreach($keys as $key){
                if($this->is_protected_meta($key, $meta_type)){
                    continue;
                }
                $meta_form .= '
								<option value="' . esc_attr($key) . '">' . esc_html($key) . '</option>';
            }
            $meta_form .= '
						</select>
						<input type="text" class="metakeyinput hide-if-js form-control input-sm" name="metakeyinput" value="" />
						<a href="#gmediacustomstuff" class="hide-if-no-js gmediacustomstuff" onclick="jQuery(\'.metakeyinput, .metakeyselect, .enternew, .cancelnew\', \'#newmeta\').toggle();jQuery(this).parent().toggleClass(\'newcfield\');return false;">
							<span class="enternew">' . __('Enter new', 'grand-media') . '</span>
							<span class="cancelnew" style="display:none;">' . __('Cancel', 'grand-media') . '</span></a>';
        } else{
            $meta_form .= '
						<input type="text" class="metakeyinput form-control input-sm" name="metakeyinput" value="" />';
        }
        $meta_form .= '
				</div>
				<div class="form-group col-sm-6">
					<label>' . __('Value', 'grand-media') . '</label>
					<textarea class="metavalue vert form-control input-sm" name="metavalue" rows="2" cols="25"></textarea>
				</div>
			</div>
		</div>';


        return $meta_form;
    }

    /**
     * @since 1.6.3
     *
     * @param int $gmedia_ID
     * @param string $meta_type
     *
     * @return bool|int
     */
    function add_meta($gmedia_ID, $meta_type = 'gmedia'){
        global $gmDB;

        if( !in_array($meta_type, array('gmedia', 'gmedia_term'))){
            $meta_type = 'gmedia';
        }

        $gmedia_ID = (int) $gmedia_ID;

        $metakeyselect = isset($_POST['metakeyselect'])? wp_unslash(trim($_POST['metakeyselect'])) : '';
        $metakeyinput  = isset($_POST['metakeyinput'])? wp_unslash(trim($_POST['metakeyinput'])) : '';
        $metavalue     = isset($_POST['metavalue'])? $_POST['metavalue'] : '';
        if(is_string($metavalue)){
            $metavalue = trim($metavalue);
        }

        if(('0' === $metavalue || !empty ($metavalue)) && (( !empty($metakeyselect) && !empty($metakeyselect)) || !empty ($metakeyinput))){
            /*
			 * We have a key/value pair. If both the select and the input
			 * for the key have data, the input takes precedence.
			 */
            $metakey = $metakeyselect;

            if($metakeyinput){
                $metakey = $metakeyinput;
            } // default

            if($this->is_protected_meta($metakey, $meta_type)){
                return false;
            }

            $metakey = wp_slash($metakey);

            return $gmDB->add_metadata($meta_type, $gmedia_ID, $metakey, $metavalue);
        }

        return false;
    } // add_meta

    /** Get item Meta
     *
     * @param int|object $item
     *
     * @return array metadata[key] = array(name, value);
     */
    function metadata_info($item){
        global $gmDB;

        if(is_object($item)){
            $item_id = $item->ID;
        } elseif($this->is_digit($item)){
            $item_id = (int) $item;
        } else{
            return null;
        }

        $metadata = array();

        $meta = $gmDB->get_metadata('gmedia', $item_id, '_metadata', true);
        if($meta){
            if(isset($meta['image_meta'])){
                $metainfo = $meta['image_meta'];
            } else{
                $metainfo = $meta;
                if(is_array($metainfo)){
                    unset($metainfo['web'], $metainfo['original'], $metainfo['thumb'], $metainfo['file']);
                }
            }

            if( !empty($metainfo)){
                foreach($metainfo as $key => $value){
                    if(empty($value)){
                        continue;
                    }
                    $key_name         = $this->i18n_exif_name($key);
                    $key_name         = $this->mb_ucwords_utf8(str_replace('_', ' ', $key_name));
                    $value            = $this->sanitize_meta_value($value);
                    $metadata[ $key ] = array('name' => $key_name, 'value' => $value);
                }
            }
        }

        return $metadata;
    }

    /**
     * @param $value
     *
     * @return array
     */
    function sanitize_meta_value($value){
        if(is_array($value) && (bool) count(array_filter(array_keys($value), 'is_string'))){
            $value_return = array();
            foreach($value as $key => $val){
                if(empty($value)){
                    continue;
                }
                $key_name = $this->i18n_exif_name($key);
                $key_name = $this->mb_ucwords_utf8(str_replace('_', ' ', $key_name));
                if(is_array($val)){
                    $val = $this->sanitize_meta_value($val);
                }
                $value_return[ $key ] = array('name' => $key_name, 'value' => $val);
            }
        } else{
            $value_return = $value;
        }

        return $value_return;
    }

    /** Get item Meta Text
     *
     * @param int $id
     *
     * @return string Meta text;
     */
    function metadata_text($id){
        $metatext = '';
        if(($metadata = $this->metadata_info($id))){
            foreach($metadata as $meta){
                if($meta['name'] == 'Image'){
                    continue;
                }
                $metatext .= "<b>{$meta['name']}:</b>";
                if( !is_array($meta['value'])){
                    $metatext .= " {$meta['value']}\n";
                } else{
                    $value = $meta['value'];
                    $this->meta_value_array_show($metatext, $value);
                }
            }
        }

        return $metatext;
    }

    /**
     * @param     $metatext
     * @param     $value
     * @param int $pad
     */
    function meta_value_array_show(&$metatext, $value, $pad = 0){
        if((bool) count(array_filter(array_keys($value), 'is_string'))){
            $pad ++;
            foreach($value as $val){
                $metatext .= "\n" . str_pad('&nbsp;', $pad) . "- <b>{$val['name']}:</b> ";
                if(is_array($val['value'])){
                    $this->meta_value_array_show($metatext, $val['value'], $pad);
                } else{
                    $metatext .= $val['value'];
                }
            }
        } else{
            $metatext .= ' ' . implode(', ', $value);
        }
        $metatext .= "\n";
    }

    /** Get [latitude, longtitude] coordinates from EXIF
     *
     * @param array $gps Exif[GPS] array
     *
     * @return array
     */
    function getGPSfromExif($gps){
        $lat = $this->getGPS($gps['GPSLatitude'], $gps['GPSLatitudeRef']);
        $lng = $this->getGPS($gps['GPSLongitude'], $gps['GPSLongitudeRef']);

        return array('lat' => round($lat, 4), 'lng' => round($lng, 4));
    }

    /**
     * @param $coordinate
     * @param $hemisphere
     *
     * @return int
     */
    function getGPS($coordinate, $hemisphere){
        for($i = 0; $i < 3; $i ++){
            $part = explode('/', $coordinate[ $i ]);
            if(count($part) == 1){
                $coordinate[ $i ] = $part[0];
            } else if(count($part) == 2){
                $coordinate[ $i ] = floatval($part[0]) / floatval($part[1]);
            } else{
                $coordinate[ $i ] = 0;
            }
        }
        list($degrees, $minutes, $seconds) = $coordinate;
        $sign = ($hemisphere == 'W' || $hemisphere == 'S')? - 1 : 1;

        return $sign * ($degrees + $minutes / 60 + $seconds / 3600);
    }

    /**
     * Update media meta in the database
     *
     * @param $gmID
     * @param $meta
     *
     * @return mixed
     */
    function gm_hitcounter($gmID, $meta){
        /** @var wpdb $wpdb */
        global $gmDB;

        $like = $this->_post('vote');
        $like = $this->_post('like', $like);
        if((int) $like == 1){
            $meta['likes'] += 1;
            $gmDB->update_metadata('gmedia', $gmID, 'likes', $meta['likes']);
            do_action('gmedia_like', $gmID);
        } else{
            $meta['views'] += 1;
            $gmDB->update_metadata('gmedia', $gmID, 'views', $meta['views']);
            do_action('gmedia_view', $gmID);
        }

        return $meta;
    }

    /**
     * Replace keys of an array based on a key map array
     *
     * @param $array
     * @param $keymap
     *
     * @return array
     * @throws Exception
     */
    function replace_array_keys(&$array, $keymap){
        $replaced_keys = array();
        $skipped       = $keymap;
        do {
            $keymap = $skipped;
            foreach($keymap as $new_key => $original_key){
                if(isset($array[ $original_key ])){
                    if( !isset($array[ $new_key ]) || (isset($replaced_keys[ $new_key ]) && !isset($replaced_keys[ $original_key ]))){
                        $array[ $new_key ] = $array[ $original_key ];
                        unset($array[ $original_key ]);
                        $replaced_keys[ $original_key ] = $new_key;
                        unset($skipped[ $new_key ]);
                    } elseif(isset($array[ $new_key ]) && array_search($new_key, $keymap) === false){
                        throw new Exception('Trying to replace an array key with an already existing array key, without providing a new position for the existing array key in replace_array_keys().');
                    } elseif(isset($array[ $new_key ]) && $keymap[ $original_key ] == $new_key && !isset($replaced_keys[ $original_key ])){
                        //switch places.
                        $temp                           = $array[ $new_key ];
                        $array[ $new_key ]              = $array[ $original_key ];
                        $array[ $original_key ]         = $temp;
                        $replaced_keys[ $new_key ]      = $original_key;
                        $replaced_keys[ $original_key ] = $new_key;
                        unset($skipped[ $new_key ]);
                        unset($skipped[ $original_key ]);
                    }
                } else{
                    unset($skipped[ $new_key ]);
                }
            }
        } while( !empty($skipped));

        return $replaced_keys;
    }

    /**
     * @return array Gmedia Capabilities
     */
    function plugin_capabilities(){
        return array(
            'gmedia_library',
            'gmedia_show_others_media',
            'gmedia_edit_media',
            'gmedia_edit_others_media',
            'gmedia_delete_media',
            'gmedia_delete_others_media',
            'gmedia_upload',
            'gmedia_import',
            'gmedia_terms',
            'gmedia_album_manage',
            'gmedia_category_manage',
            'gmedia_tag_manage',
            'gmedia_terms_delete',
            'gmedia_gallery_manage',
            'gmedia_module_manage',
            'gmedia_settings'
        );
    }

    /**
     * @return array Gmedia Capabilities
     */
    function modules_order(){
        return array(
	        'photoblog'         => '',
	        'cicerone'          => '',
	        'albumsListMasonry' => '',
	        'albumsList'        => '',
	        'woowslider'        => '',
	        'albums-switcher'   => '',
	        'photocluster'      => '',
	        'albumsview'        => '',
	        'albumsgrid'        => '',
	        'phantom-pro'       => '',
	        'albums-stripes'    => '',
	        'cubik'             => '',
	        'desire'            => '',
	        'phototravlr'       => '',
	        'realslider'        => '',
	        'mosaic'            => '',
	        'photobox'          => '',
	        'amron'             => '',
	        'wavesurfer'        => '',
	        'flipgrid'          => '',
	        'phantom'           => '',
	        'cubik-lite'        => '',
	        'photomania'        => '',
	        'jq-mplayer'        => '',
	        'wp-videoplayer'    => '',

	        'photo-pro'         => '',
	        'optima'            => '',
	        'afflux'            => '',
	        'slider'            => '',
	        'green-style'       => '',
	        'photo-blog'        => '',
	        'minima'            => '',
	        'sphere'            => '',
	        'cube'              => '',
	        'flatwall'          => ''
        );
    }

    /**
     * @param int|string $module
     * @param string $set_module_callback
     *
     * @return array [module, settings]
     */
    function getModulePreset($module = '', $set_module_callback = ''){
        global $gmDB, $gmGallery;

        if( !$set_module_callback){
            $set_module_callback = 'amron';
        }
        if( !$module){
            return $this->getModulePreset($gmGallery->options['default_gmedia_module'], $set_module_callback);
        }

        if($this->is_digit($module)){
            $preset = $gmDB->get_term($module);
            if($preset && !is_wp_error($preset)){
                $module          = $preset->status;
                $module_settings = array($module => (array) maybe_unserialize($preset->description));
                $name            = trim(str_replace('[' . $module . ']', '', $preset->name));
            } else{
                return $this->getModulePreset($set_module_callback);
            }
        } else{
            $preset = $gmDB->get_term('[' . $module . ']', array('taxonomy' => 'gmedia_module', 'global' => '0'));
            if($preset && !is_wp_error($preset)){
                $module          = $preset->status;
                $module_settings = array($module => (array) maybe_unserialize($preset->description));
                $name            = __('Default Settings', 'grand-media');
            } else{
                $module_settings = array($module => array());
                $name            = $module;
            }
        }

        return array('module' => $module, 'settings' => $module_settings, 'name' => $name);
    }

    /**
     * Clear the caches!
     */
    function clear_cache(){
	    // Delete cache transients.
	    gmedia_delete_transients('gm_cache');

        // if W3 Total Cache is being used, clear the cache
        if(function_exists('w3tc_pgcache_flush')){
            w3tc_pgcache_flush();
        } // if WP Super Cache is being used, clear the cache
        else if(function_exists('wp_cache_clean_cache')){
            global $file_prefix, $supercachedir;
            if(empty($supercachedir) && function_exists('get_supercache_dir')){
                $supercachedir = get_supercache_dir();
            }
            wp_cache_clean_cache($file_prefix);
        } else if(class_exists('WpeCommon')){
            //be extra careful, just in case 3rd party changes things on us
            if(method_exists('WpeCommon', 'purge_memcached')){
                WpeCommon::purge_memcached();
            }
            if(method_exists('WpeCommon', 'purge_memcached')){
                WpeCommon::clear_maxcdn_cache();
            }
            if(method_exists('WpeCommon', 'purge_memcached')){
                WpeCommon::purge_varnish_cache();
            }
        } else if(class_exists('WpFastestCache')){
            global $wp_fastest_cache;
            if(method_exists('WpFastestCache', 'deleteCache') && !empty($wp_fastest_cache)){
                $wp_fastest_cache->deleteCache();
            }
        }
    }

    /**
     * Filter the gmedia terms (private and draft) for frontend and admin panel
     *
     * @param $terms
     * @param $gmedia_id
     * @param $taxonomy
     *
     * @return mixed
     */
    function get_the_gmedia_terms($terms, $gmedia_id, $taxonomy){
        if('gmedia_album' === $taxonomy){
            if( !is_user_logged_in()){
                foreach($terms as $key => $term){
                    if('publish' !== $term->status){
                        unset($terms[ $key ]);
                    }
                }
            } else{
                global $user_ID;
                foreach($terms as $key => $term){
                    if('draft' === $term->status){
                        if( !is_admin() || ($user_ID != $term->global && !gm_user_can('edit_others_media'))){
                            unset($terms[ $key ]);
                        }
                    } elseif('private' === $term->status){
                        if($user_ID != $term->global && !gm_user_can('show_others_media')){
                            unset($terms[ $key ]);
                        }
                    }
                }
            }
        }

        return $terms;
    }

    /**
     * mb_convert_encoding alternative function
     *
     * @param $string
     *
     * @return string
     */
    function mb_convert_encoding_utf8($string){
        if(function_exists('mb_convert_encoding')){
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        } else{
            $string = htmlspecialchars_decode(utf8_decode(htmlentities($string, ENT_COMPAT, 'utf-8', false)));
        }

        return $string;
    }

    /**
     * mb_convert_case alternative function
     *
     * @param $string
     *
     * @return string
     */
    function mb_ucwords_utf8($string){
        $string = $this->mb_convert_encoding_utf8($string);
        $string = ucwords($string);

        return $string;
    }

    /**
     * Converts IDN in given url address to its ASCII form, also known as punycode, if possible.
     * This function silently returns unmodified address if:
     * - No conversion is necessary (i.e. domain name is not an IDN, or is already in ASCII form)
     * - Conversion to punycode is impossible (e.g. required PHP functions are not available)
     *   or fails for any reason (e.g. domain has characters not allowed in an IDN)
     * @see PHPMailer::$CharSet
     *
     * @param string $url The url to convert
     *
     * @return string The encoded url in ASCII form
     */
    public function punyencode($url){
        $url_host = parse_url($url, PHP_URL_HOST);

        if((boolean) preg_match('/[\x80-\xFF]/', $url_host)){
            $host = $this->mb_convert_encoding_utf8($url_host);
            if(function_exists('idn_to_ascii')){
                $options = 0;
                if(($punycode = defined('INTL_IDNA_VARIANT_UTS46')? idn_to_ascii($host, $options, INTL_IDNA_VARIANT_UTS46) : idn_to_ascii($host)) !== false){
                    $new_url = str_replace($url_host, $punycode, $url);

                    return $new_url;
                }
            }

            require_once(dirname(__FILE__) . '/punycode.php');
            $gmPunycode = new gmPunycode();
            $punycode   = $gmPunycode->encode($host);
            $new_url    = str_replace($url_host, $punycode, $url);

            return $new_url;
        }

        return $url;
    }

    /**
     * Sanitizes a hex color
     *
     * @param $color
     * @param $def
     *
     * @return string
     */
    function sanitize_hex_color($color, $def = ''){
        $hash  = $color[0] === '#'? '#' : '';
        $color = ltrim($color, '#');
        if('' === $color){
            return $def;
        }

        // 3 or 6 hex digits, or the empty string.
        if(preg_match('|^([A-Fa-f0-9]{3}){1,2}$|', $color)){
            return $hash . $color;
        }

        return $def;
    }

    /**
     * Convert a hex color to rgb
     *
     * @param $hex
     *
     * @return array [r ,g, b]
     */
    function hex2rgb($hex){
        require_once(dirname(__FILE__) . '/color.php');

        $color = new gmColor($hex);
        $rgb   = $color->getRgb();

        return array($rgb['R'], $rgb['G'], $rgb['B']);
    }

    /**
     * Return gmColor class
     *
     * @param $hex
     *
     * @return object class
     */
    function color($hex = null){
        require_once(dirname(__FILE__) . '/color.php');

        return new gmColor($hex);
    }

    /**
     * Log Views
     *
     * @param $id
     */
    function log_views_handler($id){
        global $wpdb, $user_ID, $gmDB;

        $gmedia = $gmDB->get_gmedia($id);
        if( !empty($gmedia->ID)){
            $data = array(
                'log'        => 'view',
                'ID'         => $gmedia->ID,
                'log_author' => (int) $user_ID,
                'log_date'   => current_time('mysql'),
                'log_data'   => '1',
                'ip_address' => preg_replace('/(?!\d{1,3}\.\d{1,3}\.)\d/', '*', $this->ip())
            );
            $id   = $wpdb->insert($wpdb->prefix . 'gmedia_log', $data);
        }
    }

    /**
     * Log Likes
     *
     * @param $id
     */
    function log_likes_handler($id){
        global $wpdb, $user_ID, $gmDB;

        $gmedia = $gmDB->get_gmedia($id);
        if( !empty($gmedia->ID)){
            $data = array(
                'log'        => 'like',
                'ID'         => $gmedia->ID,
                'log_author' => (int) $user_ID,
                'log_date'   => current_time('mysql'),
                'log_data'   => '1',
                'ip_address' => preg_replace('/(?!\d{1,3}\.\d{1,3}\.)\d/', '*', $this->ip())
            );
            $wpdb->insert($wpdb->prefix . 'gmedia_log', $data);
        }
    }

    /**
     * Log Rating
     *
     * @param $id
     * @param $val
     */
    function log_rates_handler($id, $val){
        global $wpdb, $user_ID, $gmDB;

        $gmedia = $gmDB->get_gmedia($id);
        if( !empty($gmedia->ID)){
            $data = array(
                'log'        => 'rate',
                'ID'         => $gmedia->ID,
                'log_author' => (int) $user_ID,
                'log_date'   => current_time('mysql'),
                'log_data'   => $val,
                'ip_address' => preg_replace('/(?!\d{1,3}\.\d{1,3}\.)\d/', '*', $this->ip())
            );
            $wpdb->insert($wpdb->prefix . 'gmedia_log', $data);
        }
    }

    /**
     * Get IP address
     */
    function ip(){
        //Test if it is a shared client
        if( !empty($_SERVER['HTTP_CLIENT_IP'])){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
            //Is it a proxy address
        } elseif( !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}

global $gmCore;
$gmCore = new GmediaCore();