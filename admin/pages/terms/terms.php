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

?>
    <div class="panel panel-default panel-fixed-header" id="gmedia-panel">

        <?php
        include(dirname(__FILE__) . '/tpl/terms-panel-heading.php');

        do_action('gmedia_before_terms_list');
        ?>

        <form class="list-group <?php echo $gmedia_term_taxonomy; ?>" id="gm-list-table" style="margin-bottom:4px;">
            <?php
            $taxterm = str_replace('gmedia_', '', $gmedia_term_taxonomy);
            if(count($gmedia_terms)) {
                foreach($gmedia_terms as &$item) {
                    gmedia_term_item_more_data($item);

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

                    $allow_terms_delete = gm_user_can('terms_delete');
                    if($item->global) {
                        if((int)$item->global === get_current_user_id()) {
                            $item->allow_edit   = gm_user_can("{$taxterm}_manage");
                            $item->allow_delete = $allow_terms_delete;
                        } else {
                            $item->allow_edit   = gm_user_can('edit_others_media');
                            $item->allow_delete = ($item->allow_edit && $allow_terms_delete);
                        }
                    } else {
                        $item->allow_edit   = gm_user_can('edit_others_media');
                        $item->allow_delete = ($item->allow_edit && $allow_terms_delete);
                    }

                    include(dirname(__FILE__) . "/tpl/{$taxterm}-list-item.php");

                }
            } else {
                include(dirname(__FILE__) . '/tpl/no-items.php');
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

include(GMEDIA_ABSPATH . 'admin/tpl/modal-share.php');
