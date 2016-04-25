<?php
/**
 * Class GmediaProcessor
 */
class GmediaProcessor {

    public $page;
    public $url;
    public $msg;
    public $error;
    public $user_options = array();

    /**
     * initiate the manage page
     */
    public function __construct() {
        global $pagenow, $gmCore;
        // GET variables
        $this->page = $gmCore->_get('page');
        $this->url = add_query_arg(array('page' => $this->page), admin_url('admin.php'));
        if('media.php' === $pagenow) {
            add_filter('wp_redirect', array($this, 'redirect'), 10, 2);
        }
        if('edit-comments.php' === $pagenow) {
            add_filter('get_comment_text', array($this, 'gmedia_comment_text'), 10, 3);
        }

        add_action('init', array($this, 'controller'));

    }

    /**
     * load only on Gmedia admin pages
     */
    public function controller() {

        $this->user_options = self::user_options();

        if(!$this->page || strpos($this->page, 'GrandMedia') === false) {
            return;
        }

        auth_redirect();

        $this->processor();
    }


    /**
     * Do diff process before lib shell
     */
    protected function processor() {}

    /**
     * @return array|mixed
     */
    public static function user_options() {
        global $user_ID, $gmGallery;

        $screen_options = get_user_meta($user_ID, 'gm_screen_options', true);
        if(!is_array($screen_options)) {
            $screen_options = array();
        }
        return array_merge($gmGallery->options['gm_screen_options'], $screen_options);
    }

    /**
     * @param string $cookie_key
     *
     * @param string $post_key
     *
     * @return array
     */
    public static function selected_items($cookie_key, $post_key = 'selected_items') {

        $selected_items = array();
        if($cookie_key) {
            if(isset($_POST[$post_key])) {
                $selected_items = array_filter(explode(',', $_POST[$post_key]), 'is_numeric');
            } elseif(isset($_COOKIE[$cookie_key])) {
                $selected_items = array_filter(explode(',', $_COOKIE[$cookie_key]), 'is_numeric');
            }
        }

        return $selected_items;
    }

    /**
     * @param string $cookie_key
     *
     * @return array
     */
    public function clear_selected_items($cookie_key) {
        global $user_ID;

        if($cookie_key) {
            setcookie("gmuser_{$user_ID}_{$cookie_key}", '', time() - 3600);
            unset($_COOKIE["gmuser_{$user_ID}_{$cookie_key}"]);
        }
        return array();
    }

    /**
     * @param bool|string|array $author_id_list
     *
     * @return array|mixed
     */
    public static function filter_by_author($author_id_list = false) {
        global $user_ID, $gmCore;

        $author = false;
        if($gmCore->caps['gmedia_show_others_media']) {
            if(!empty($author_id_list)) {
                $author = wp_parse_id_list($author_id_list);
                $author = array_intersect(array($user_ID, 0), $author);
            }
        } else {
            $author = array($user_ID, 0);
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
    public function redirect($location, $status) {
        global $pagenow;
        if('media.php' === $pagenow && isset($_POST['_wp_original_http_referer'])) {
            if(strpos($_POST['_wp_original_http_referer'], 'GrandMedia') !== false) {
                return $_POST['_wp_original_http_referer'];
            } else {
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
    function gmedia_comment_text($comment_content, $comment, $args) {
        global $post;
        if(!$post){
            return $comment_content;
        }
        //if('gmedia' == substr($post->post_type, 0, 6)) {
        if('gmedia' == $post->post_type) {
            global $gmDB, $gmCore;
            $gmedia = $gmDB->get_post_gmedia($post->ID);
            $thumb = '<div class="alignright"><img class="gmedia-thumb" style="max-height:72px;" src="' . $gmCore->gm_get_media_image($gmedia, 'thumb', false) . '" alt=""/></div>';
            $comment_content = $thumb . $comment_content;
        }
        return $comment_content;
    }

    /**
     * Autoloader
     */
    public static function autoload() {
        $path_ = GMEDIA_ABSPATH . '/admin/processor/class.processor.';
        $page = isset($_GET['page'])? $_GET['page'] : '';
        switch($page) {
            case 'GrandMedia':
                include_once($path_ . 'library.php');
            break;
            case 'GrandMedia_AddMedia':
                include_once($path_ . 'addmedia.php');
            break;
            case 'GrandMedia_Albums':
            case 'GrandMedia_Categories':
            case 'GrandMedia_Tags':
                include_once($path_ . 'terms.php');
            break;
            case 'GrandMedia_Galleries':
                include_once($path_ . 'galleries.php');
            break;
            case 'GrandMedia_Modules':
                include_once($path_ . 'modules.php');
            break;
            case 'GrandMedia_Settings':
                include_once($path_ . 'settings.php');
            break;
            case 'GrandMedia_WordpressLibrary':
                include_once($path_ . 'wpmedia.php');
            break;
            default:
                global $gmProcessor;
                $gmProcessor = new GmediaProcessor();
            break;
        }
    }


}
GmediaProcessor::autoload();
