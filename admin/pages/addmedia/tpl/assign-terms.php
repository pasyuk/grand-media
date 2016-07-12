<?php
/**
 * @var $user_ID
 * @var $gmGallery
 * @var $gmDB
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

if(gm_user_can('terms')) { ?>
    <div class="form-group">
        <?php
        $term_type = 'gmedia_album';
        $global    = gm_user_can('edit_others_media')? '' : array(0, $user_ID);
        $gm_terms  = $gmDB->get_terms($term_type, array('global' => $global, 'orderby' => 'global_desc_name'));

        $terms_album = '';
        if(count($gm_terms)) {
            foreach($gm_terms as $term) {
                $author_name = '';
                if($term->global) {
                    if(gm_user_can('edit_others_media')) {
                        $author_name .= ' &nbsp; ' . sprintf(__('by %s', 'grand-media'), get_the_author_meta('display_name', $term->global));
                    }
                } else {
                    $author_name .= ' &nbsp; (' . __('shared', 'grand-media') . ')';
                }
                if('publish' != $term->status) {
                    $author_name .= ' [' . $term->status . ']';
                }
                $terms_album .= '<option value="' . $term->term_id . '" data-name="' . esc_html($term->name) . '" data-meta="' . $author_name . '">' . esc_html($term->name) . $author_name . '</option>' . "\n";
            }
        }
        ?>
        <label><?php _e('Add to Album', 'grand-media'); ?> </label>
        <select id="combobox_gmedia_album" name="terms[gmedia_album]" data-create="<?php echo gm_user_can('album_manage')? 'true' : 'false'; ?>" class="form-control input-sm" placeholder="<?php _e('Album Name...', 'grand-media'); ?>">
            <option value=""></option>
            <?php echo $terms_album; ?>
        </select>
    </div>

    <div class="form-group">
        <?php
        $term_type = 'gmedia_category';
        $gm_category_terms  = $gmDB->get_terms($term_type, array('fields' => 'names'));
        ?>
        <label><?php _e('Assign Categories', 'grand-media'); ?></label>
        <input id="combobox_gmedia_category" name="terms[gmedia_category]" data-create="<?php echo gm_user_can('category_manage')? 'true' : 'false'; ?>" class="form-control input-sm" value="" placeholder="<?php _e('Uncategorized', 'grand-media'); ?>"/>
    </div>

    <div class="form-group">
        <?php
        $term_type = 'gmedia_tag';
        $gm_tag_terms  = $gmDB->get_terms($term_type, array('fields' => 'names'));
        ?>
        <label><?php _e('Add Tags', 'grand-media'); ?> </label>
        <input id="combobox_gmedia_tag" name="terms[gmedia_tag]" data-create="<?php echo gm_user_can('tag_manage')? 'true' : 'false'; ?>" class="form-control input-sm" value="" placeholder="<?php _e('Add Tags...', 'grand-media'); ?>"/>
    </div>
    <script type="text/javascript">
        var gmedia_categories = <?php echo json_encode($gm_category_terms); ?>;
        var gmedia_tags = <?php echo json_encode($gm_tag_terms); ?>;
    </script>
<?php } else { ?>
    <p><?php _e('You are not allowed to assign terms', 'grand-media') ?></p>
<?php } ?>
