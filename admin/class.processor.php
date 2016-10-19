<?php

/**
 * Class GmediaProcessor
 */
class GmediaProcessor{

    private static $me = null;
    public $page;
    public $gmediablank;
    public $url;
    public $msg;
    public $error;
    public $user_options = array();

    public $display_mode;
    public $taxonomy;
    public $taxterm;
    public $edit_term;

    /**
     * initiate the manage page
     */
    public function __construct(){
        global $pagenow, $gmCore;
        // GET variables
        $this->page = $gmCore->_get('page');
        $this->url  = add_query_arg(array('page' => $this->page), admin_url('admin.php'));
        if('media.php' === $pagenow){
            add_filter('wp_redirect', array($this, 'redirect'), 10, 2);
        }
        if('edit-comments.php' === $pagenow){
            add_filter('get_comment_text', array($this, 'gmedia_comment_text'), 10, 3);
        }

        add_action('init', array($this, 'controller'));

        if(!$this->page || strpos($this->page, 'GrandMedia') === false){
            return;
        }

        $this->gmediablank = $gmCore->_get('gmediablank');
        if($this->gmediablank){
            $this->url = add_query_arg(array('gmediablank' => $this->gmediablank), $this->url);
        }

        switch($this->page){
            case 'GrandMedia_Albums':
                $this->taxonomy = 'gmedia_album';
            break;
            case 'GrandMedia_Categories':
                $this->taxonomy = 'gmedia_category';
            break;
            case 'GrandMedia_Tags':
                $this->taxonomy = 'gmedia_tag';
            break;
            case 'GrandMedia_Galleries':
                $this->taxonomy = 'gmedia_gallery';
            break;
        }
        if($this->taxonomy){
            $this->taxterm   = str_replace('gmedia_', '', $this->taxonomy);
            $this->edit_term = $gmCore->_get('edit_term');
        }

    }

    /**
     * load only on Gmedia admin pages
     */
    public function controller(){

        $this->user_options = self::user_options();
        $view               = $this->gmediablank? '_frame' : '';
        $this->display_mode = $this->user_options["display_mode_gmedia{$view}"];

        if(!$this->page || strpos($this->page, 'GrandMedia') === false){
            return;
        }

        auth_redirect();

        $this->processor();
    }


    /**
     * Do diff process before lib shell
     */
    protected function processor(){ }

    /**
     * @return array|mixed
     */
    public static function user_options(){
        global $user_ID, $gmGallery;

        $screen_options = get_user_meta($user_ID, 'gm_screen_options', true);
        if(!is_array($screen_options)){
            $screen_options = array();
        }

        return array_merge($gmGallery->options['gm_screen_options'], $screen_options);
    }

    /**
     * @param string $key
     * @param string $post_key
     *
     * @return array
     */
    public static function selected_items($key, $post_key = 'selected_items'){

        $selected_items = array();
        if($key){
            if(isset($_POST[ $post_key ])){
                $selected_items = array_filter(explode(',', $_POST[ $post_key ]), 'is_numeric');
            } elseif(isset($_COOKIE[ $key ])){
                $selected_items = array_filter(explode('.', $_COOKIE[ $key ]), 'is_numeric');
            }
        }

        return $selected_items;
    }

    /**
     * @param string $cookie_key
     *
     * @return array
     */
    public function clear_selected_items($cookie_key){
        if($cookie_key){
            setcookie($cookie_key, '', time() - 3600);
            unset($_COOKIE[ $cookie_key ]);
        }

        return array();
    }

    /**
     * @param bool|string|array $author_id_list
     *
     * @return array|mixed
     */
    public static function filter_by_author($author_id_list = false){
        global $user_ID, $gmCore;

        if($author_id_list === false){
            $author = false;
            if(!$gmCore->caps['gmedia_show_others_media']){
                $author = array($user_ID, 0);
            }
        } else{
            $author = wp_parse_id_list($author_id_list);
            if(!$gmCore->caps['gmedia_show_others_media']){
                $author = array_intersect(array($user_ID, 0), $author);
            }
        }

        return $author;
    }

    /**
     * redirect to original referer after update
     *
     * @param $location
     * @param $status
     *
     * @return mixed
     */
    public function redirect($location, $status){
        global $pagenow;
        if('media.php' === $pagenow && isset($_POST['_wp_original_http_referer'])){
            if(strpos($_POST['_wp_original_http_referer'], 'GrandMedia') !== false){
                return $_POST['_wp_original_http_referer'];
            } else{
                return $location;
            }
        }

        return $location;
    }

    /**
     * Add thumb to gmedia comment text in admin
     *
     * @param $comment_content
     * @param $comment
     * @param $args
     *
     * @return string $comment_content
     */
    function gmedia_comment_text($comment_content, $comment, $args){
        global $post;
        if(!$post){
            return $comment_content;
        }
        //if('gmedia' == substr($post->post_type, 0, 6)) {
        if('gmedia' == $post->post_type){
            global $gmDB, $gmCore;
            $gmedia          = $gmDB->get_post_gmedia($post->ID);
            $thumb           = '<div class="alignright" style="clear:right;"><img class="gmedia-thumb" style="max-height:72px;" src="' . $gmCore->gm_get_media_image($gmedia, 'thumb', false) . '" alt=""/></div>';
            $comment_content = $thumb . $comment_content;
        }

        return $comment_content;
    }

    /**
     * Autoloader
     */
    public static function autoload(){
        $path_ = GMEDIA_ABSPATH . 'admin/processor/class.processor.';
        $page  = isset($_GET['page'])? $_GET['page'] : '';
        switch($page){
            case 'GrandMedia':
                /** @var $gmProcessorLibrary */
                include_once($path_ . 'library.php');

                return $gmProcessorLibrary;
            break;
            case 'GrandMedia_AddMedia':
                /** @var $gmProcessorAddMedia */
                include_once($path_ . 'addmedia.php');

                return $gmProcessorAddMedia;
            break;
            case 'GrandMedia_Albums':
            case 'GrandMedia_Categories':
                /** @var $gmProcessorTerms */
                include_once($path_ . 'terms.php');
                /** @var $gmProcessorLibrary */
                include_once($path_ . 'library.php');

                return $gmProcessorTerms;
            break;
            case 'GrandMedia_Tags':
                /** @var $gmProcessorTerms */
                include_once($path_ . 'terms.php');

                return $gmProcessorTerms;
            break;
            case 'GrandMedia_Galleries':
                /** @var $gmProcessorGalleries */
                include_once($path_ . 'galleries.php');

                return $gmProcessorGalleries;
            break;
            case 'GrandMedia_Modules':
                /** @var $gmProcessorModules */
                include_once($path_ . 'modules.php');

                return $gmProcessorModules;
            break;
            case 'GrandMedia_Settings':
                /** @var $gmProcessorSettings */
                include_once($path_ . 'settings.php');

                return $gmProcessorSettings;
            break;
            case 'GrandMedia_WordpressLibrary':
                /** @var $gmProcessorWPMedia */
                include_once($path_ . 'wpmedia.php');

                return $gmProcessorWPMedia;
            break;
            default:
                if(self::$me == null){
                    self::$me = new GmediaProcessor();
                }

                return self::$me;
            break;
        }
    }

}

global $gmProcessor;
$gmProcessor = GmediaProcessor::autoload();
