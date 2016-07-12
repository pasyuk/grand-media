<?php
/**
 * Gmedia Comments
 */
if(!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

require_once(ABSPATH . 'wp-admin/includes/meta-boxes.php');

wp_enqueue_script('post');
wp_enqueue_script('admin-comments');

global $gmDB, $gmCore, $gmGallery, $post;

$gmedia_id      = $gmCore->_get('gmedia_id');
$gmedia_term_id = $gmCore->_get('gmedia_term_id');
if($gmedia_id) {
    $gmedia  = $gmDB->get_gmedia($gmedia_id);
    gmedia_item_more_data($gmedia);
    $post_id = $gmedia->post_id;
} elseif($gmedia_term_id) {
    $gmedia_term = $gmDB->get_term($gmedia_term_id);
    gmedia_term_item_more_data($gmedia_term);
    $post_id = $gmedia_term->post_id;
} else {
    die('-1');
}

$post = get_post($post_id);
?>
<div id="commentsdiv" style="padding:1px 0;">
    <style type="text/css" scoped>
        #commentsdiv {padding-top:1px;}
        #commentsdiv > .thumbnail {float:left; margin:0 10px 10px;}
        #commentsdiv > .thumbnail img.gmedia-thumb {max-height:72px;}
        #commentsdiv > h4 {margin-left:10px;}
        #commentsdiv .fixed .column-author {width:20%;}
    </style>
    <?php
    printf( '<a target="_blank" href="%s" class="pull-right">%s</a>',
            esc_url( add_query_arg( array( 'p' => $post_id ), admin_url( 'edit-comments.php' ) ) ),
            __('Open in new tab'));
    if($gmedia_id) { ?>
        <span class="thumbnail">
            <?php gmedia_item_thumbnail($gmedia); ?>
        </span>
    <?php } ?>
    <h4><?php echo $post->post_title; ?></h4>
    <?php
    post_comment_meta_box($post);
    wp_comment_reply();
    ?>
    <input id="post_ID" name="p" type="hidden" value="<?php echo $post_id; ?>"/>
</div>
<script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready(function($) {
        $("table.comments-box").css("display", "")
    });
    //]]>
</script>
