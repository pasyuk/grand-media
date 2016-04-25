<?php
/**
 * Gmedia Term (Album, Category) Edit
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$term_id              = $gmCore->_get('edit_item');
$gmedia_url           = add_query_arg(array('edit_item' => $term_id), $gmProcessor->url);
$gmedia_user_options  = $gmProcessor->user_options;
$gmedia_term_taxonomy = $gmProcessor->taxonomy;
$taxterm = str_replace('gmedia_', '', $gmedia_term_taxonomy);

if(!gm_user_can("{$taxterm}_manage")) {
    die('-1');
}

$term_id = (int) $term_id;
$term    = $gmDB->get_term($term_id);

if(empty($term) || is_wp_error($term)) {
    return;
}
gmedia_term_item_more_data($term);

do_action('gmedia_term_before_panel');
?>

<div class="panel panel-default">

    <?php
    include(dirname(__FILE__) . '/tpl/term-panel-heading.php');

    include(dirname(__FILE__) . "/tpl/{$taxterm}-edit-item.php");
    ?>

</div>

<?php
do_action("gmedia_term_{$taxterm}_after_panel", $term);
do_action('gmedia_term_after_panel');
?>
