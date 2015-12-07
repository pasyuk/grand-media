<?php
/**
 * Class GmediaProcessor
 */
class GmediaProcessor {

    public $page;
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
        if('media.php' === $pagenow) {
            add_filter('wp_redirect', array($this, 'redirect'), 10, 2);
        }

        add_action('init', array($this, 'controller'));

    }

    /**
     * load only on Gmedia admin pages
     */
    public function controller() {

        auth_redirect();

        $this->user_options = self::user_options();

        if(!$this->page || strpos($this->page, 'GrandMedia') === false) {
            return;
        }

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
     * @param $cookie_key
     *
     * @return array
     */
    public static function selected_items($cookie_key) {

        $selected_items = array();
        if($cookie_key) {
            if(isset($_POST['selected_items'])) {
                $selected_items = array_filter(explode(',', $_POST['selected_items']), 'is_numeric');
            } elseif(isset($_COOKIE[$cookie_key])) {
                $selected_items = array_filter(explode(',', $_COOKIE[$cookie_key]), 'is_numeric');
            }
        }

        return $selected_items;
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
            }
        } else {
            $author = array($user_ID);
        }

        return $author;
    }

    /**
     * @param string $type
     * @param string $content
     *
     * @return string
     */
    public static function alert($type = 'info', $content = '') {
        if(empty($content)) {
            return '';
        } elseif(is_array($content)) {
            $content = implode('<br />', array_filter($content));
        }
        $alert = '<div class="alert alert-' . $type . ' alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' . $content . '</div>';

        return $alert;
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
     * Autoloader
     */
    public static function autoload() {
        $path_ = GMEDIA_ABSPATH . '/admin/class.processor.';
        $page = !isset($_GET['page'])?: $_GET['page'];
        switch($page) {
            case 'GrandMedia':
                include_once($path_ . 'library.php');
            break;
            case 'GrandMedia_AddMedia':
                include_once($path_ . 'addmedia.php');
            break;
            case 'GrandMedia_Terms':
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
                include_once($path_ . 'wplib.php');
            break;
            default:
                global $gmProcessor;
                $gmProcessor = new GmediaProcessor();
            break;
        }
    }


}
GmediaProcessor::autoload();
