<?php
/**
 * Comments Popup Template
 */
add_filter('comment_text', 'popuplinks');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" style="padding:0;background:transparent none;min-width:0;">
<head>
    <title><?php printf(__('%1$s - Comments on %2$s'), get_option('blogname'), the_title('', '', false)); ?></title>

    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>"/>
    <script type="text/javascript">
        window.onload = function() {
            var anchors = document.getElementsByTagName('a');
            for(var i = 0; i < anchors.length; i++) {
                anchors[i].setAttribute('target', '_blank');
            }
        }
    </script>
    <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('stylesheet_url'); ?>"/>
    <?php wp_head(); ?>
</head>
<body id="commentspopup" style="padding:10px 20px;background:transparent none;min-width:0;">
<?php
if(have_posts()) :
    while(have_posts()) : the_post();
        if(comments_open() || get_comments_number()) {
            comments_template();
        } else {
            ?>
            <div id="comments" class="comments-area"><p class="nocomments"><?php _e('Comments are closed.', 'grand-media'); ?></p></div>
            <?php
        }
    endwhile; // have_posts()
endif;

wp_footer();
?>
</body>
</html>
