<?php
/**
 * Gmedia Terms
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$gmedia_url           = $gmProcessor->url;
$gmedia_user_options  = $gmProcessor->user_options;
$gmedia_term_taxonomy = $gmProcessor->taxonomy;

$gmedia_terms       = $gmDB->get_terms($gmedia_term_taxonomy, $gmProcessor->query_args);
$gmedia_terms_count = $gmDB->count_gmedia();
$gmedia_terms_pager = $gmDB->query_pager();
$gmedia_modules     = get_gmedia_modules(false);

?>
    <div class="panel panel-default panel-fixed-header" id="gmedia-panel">

        <?php
        include(dirname(__FILE__) . '/tpl/galleries-panel-heading.php');

        do_action('gmedia_before_galleries_list');
        ?>

        <form class="list-group <?php echo $gmedia_term_taxonomy; ?>" id="gm-list-table" style="margin-bottom:4px;">
            <?php
            $taxterm = $gmProcessor->taxterm;
            if(count($gmedia_terms)) {
                foreach($gmedia_terms as &$item) {
                    gmedia_gallery_more_data($item);

                    $item->classes = array();
                    if('publish' != $item->status) {
                        if('private' == $item->status) {
                            $item->classes[] = 'list-group-item-info';
                        } elseif('draft' == $item->status) {
                            $item->classes[] = 'list-group-item-warning';
                        }
                    }
                    $item->classes[] = $item->global? (($item->global == $user_ID)? 'current_user' : 'other_user') : 'shared';
                    $item->selected    = in_array($item->term_id, (array)$gmProcessor->selected_items);
                    if($item->selected) {
                        $item->classes[] = 'gm-selected';
                    }

                    include(dirname(__FILE__) . "/tpl/{$taxterm}-list-item.php");

                }
            } else {
                include(GMEDIA_ABSPATH . 'admin/pages/terms/tpl/no-items.php');
            }
            wp_original_referer_field(true, 'previous');
            wp_nonce_field('GmediaTerms');
            ?>
        </form>
        <?php
        do_action('gmedia_after_terms_list');
        ?>
    </div>

<?php
include(dirname(__FILE__) . "/tpl/choose-module.php");
include(GMEDIA_ABSPATH . 'admin/tpl/modal-share.php');
