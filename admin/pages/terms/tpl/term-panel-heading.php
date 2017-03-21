<?php // don't load directly
if(!defined('ABSPATH')){
    die('-1');
}

/**
 * Panel heading for term
 * @var $term_id
 * @var $gmedia_term_taxonomy
 * @var $gmedia_terms_pager
 * @var $gmProcessor
 * @var $gmCore
 */
$taxterm = $gmProcessor->taxterm;
$curpage = $gmCore->_get('page', 'GrandMedia');
$refurl = strpos(wp_get_referer(), "page={$curpage}")? wp_get_referer() : $gmProcessor->url;
$referer = remove_query_arg(array('edit_term'), $refurl);
?>
<div class="panel-heading-fake"></div>
<div class="panel-heading clearfix">
    <div class="btn-toolbar pull-left">
        <a class="btn btn-default pull-left" style="margin-right:20px;" href="<?php echo $referer; ?>"><?php _e('Go Back', 'grand-media'); ?></a>

        <?php if($term_id){ ?>
            <div class="btn-group">
                <a class="btn btn-default" href="#"><?php _e('Action', 'grand-media'); ?></a>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <?php $taxkey = $taxterm . '__in'; ?>
                    <li><a href="<?php echo add_query_arg(array('page' => 'GrandMedia', $taxkey => $term->term_id), $gmProcessor->url); ?>"><?php _e('Show in Gmedia Library', 'grand-media'); ?></a></li>
                    <?php
                    echo '<li' . (('draft' !== $term->status)? '' : ' class="disabled"') . '><a target="_blank" class="share-modal" data-target="#shareModal" data-share="' . $term->term_id . '" data-gmediacloud="' . $term->cloud_link . '" href="' . $term->post_link . '">' . __('Share', 'grand-media') . '</a></li>';

                    echo '<li' . ($term->allow_delete? '' : ' class="disabled"') . '><a href="' . wp_nonce_url(gm_get_admin_url(array('do_gmedia_terms' => 'delete',
                                                                                                                                      'ids'             => $term->term_id
                                                                                                                                ), array('edit_term'), $gmProcessor->url), 'gmedia_delete', '_wpnonce_delete') . '" data-confirm="' . __("You are about to permanently delete the selected items.\n\r'Cancel' to stop, 'OK' to delete.", "grand-media") . '">' . __('Delete', 'grand-media') . '</a></li>';
                    ?>
                </ul>
            </div>

            <div class="btn-group" style="margin-left:20px;">
            <?php
            $add_args = array('page'        => 'GrandMedia',
                              'mode'        => 'select_multiple',
                              'gmediablank' => 'library'
            );
            $taxterm = $term->taxterm;
            if('album' == $taxterm){
                $add_args['album__in'] = 0;
            } elseif('category' == $taxterm){
                $add_args['category__not_in'] = $gmProcessor->edit_term;
            }
            echo '<a href="' . $gmCore->get_admin_url($add_args, array(), true) . '" class="btn btn-success preview-modal pull-left" data-target="#previewModal" data-width="1200" data-height="500" data-cls="select_gmedia assign_gmedia_term" data-title="' . __('Add from Library', 'grand-media') . '"><span class="glyphicon glyphicon-plus"></span> ' . __('Add from Library', 'grand-media') . '</a>';

            if(gm_user_can('upload') && !$gmProcessor->gmediablank){
                $args = array('page' => 'GrandMedia_AddMedia');
                if($gmProcessor->edit_term){
                    $taxterm = $term->taxterm;
                    $args[ $taxterm ] = $gmProcessor->edit_term;
                }
                ?>
                <a href="<?php echo gm_get_admin_url($args, array(), true); ?>" class="btn btn-success pull-left">
                    <span class="glyphicon glyphicon-upload" style="font-size: 130%;line-height: 0;vertical-align: sub;"></span> <?php _e('Upload', 'grand-media'); ?>
                </a>
                <?php
            }
            ?>
            </div>

            <div class="term-shortcode pull-left"><input type="text" title="<?php _e('Shortcode'); ?>" class="form-control pull-left" value="<?php echo "[gm {$taxterm}={$term_id}]"; ?>" readonly/>
                <div class="input-buffer"></div>
            </div>
        <?php }
        do_action('gmedia_term_btn_toolbar');
        ?>
    </div>

    <div class="spinner"></div>
</div>
