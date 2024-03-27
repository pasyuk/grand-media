<?php
/**
 * Comments Popup Template
 */
if ( ! function_exists( 'gmedia_comments_template' ) ) {
	function gmedia_comments_template() {
		return GMEDIA_ABSPATH . 'template/comments.php';
	}
}
add_filter( 'comments_template', 'gmedia_comments_template' );
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" style="padding:0;background:transparent none;min-width:0;overflow-y:auto;">
<head>
	<title>
		<?php
		// translators: 1 - blogname, 2 - title.
		echo esc_html( sprintf( __( '%1$s - Comments on %2$s' ), get_option( 'blogname' ), the_title( '', '', false ) ) );
		?>
	</title>

	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>"/>
	<?php // phpcs:ignore ?>
	<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>"/>
	<?php
	wp_head();
	?>
	<script type="text/javascript">
			jQuery(function($) {
				$('a').attr('target', '_blank');
				$('.comment-meta').each(function() {
					$(this).appendTo($(this).parent()).show();
					$(this).parent().find('.reply').before('<div class="clearfix"></div>');
				});
			});
	</script>
	<style type="text/css">
		html, body {
			padding: 0;
			background: transparent none;
			min-width: 0;
			overflow-y: auto;
			box-sizing: border-box;
			min-height: 0;
			height: auto !important;
			width: auto !important;
		}

		#gmediacomments {
			line-height: 120%;
			padding: 10px 0;
			margin: 0;
			box-sizing: inherit;
		}

		#gmediacomments ol.gmediacommentlist {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		#gmediacomments li {
			padding: 0 0 0 32px;
			margin: 0 0 15px;
			list-style: none;
			font-size: 14px;
			line-height: 120%;
		}

		#gmediacomments .comment-author {
			display: inline-block;
			margin: 0;
			padding: 0;
		}

		#gmediacomments span.says {
			display: none;
		}

		#gmediacomments .comment-meta {
			display: none;
			text-align: right;
			font-size: 11px;
		}

		#gmediacomments .comment-author::after {
			content: '-';
			margin: 0 5px;
		}

		#gmediacomments p {
			padding: 0;
			margin: 5px 0;
			line-height: 120%;
		}

		#gmediacomments li p {
			padding: 0;
			margin: 0;
			display: inline;
		}

		#gmediacomments li form p {
			display: block;
			margin: 5px 0;
		}

		#gmediacomments img.avatar {
			float: left;
			margin-left: -32px;
			margin-bottom: -25px;
			margin-top: 2px;
			border: none;
		}

		#gmediacomments .clearfix {
			width: 100%;
		}

		#gmediacomments .reply {
			font-size: 12px;
			float: left;
		}

		#gmediacomments .fusion-title.title {
			display: none;
		}

		#gmediacomments .gmedia_comment-form-comment label {
			display: none;
		}

		#gmediacomments p.gmedia_logged-in-as {
			font-size: 12px;
			line-height: 120%;
			margin: 5px 0;
		}

		#gmediacomments div#respond {
			margin: 10px 0;
			position: relative;
			padding: 0;
		}

		#gmediacomments .gmedia_cancel-reply {
			position: absolute;
			bottom: 5px;
			font-size: 12px;
			margin: 0;
			padding: 0;
		}

		#gmediacomments textarea#gmedia_comment {
			width: 100%;
			height: 40px;
			padding: 3px 4px;
			border-radius: 4px;
			margin: 0;
			font-size: 14px;
			border: 1px solid #101010;
		}

		#gmediacomments input.gmedia_comments-input {
			width: 100%;
			height: auto;
			line-height: 120%;
			padding: 3px 4px;
			border-radius: 4px;
			margin: 0;
			font-size: 14px;
			border: 1px solid #101010;
		}

		#gmediacomments .gmedia_comment-form p.gmedia_form-submit {
			padding: 0;
			margin: 5px 0;
			display: block;
			text-align: right;
			line-height: 120%;
		}

		#gmediacomments input#gmedia_submit {
			padding: 5px 10px;
			line-height: 120%;
			border-radius: 6px;
			letter-spacing: normal;
			display: inline;
			margin: 0;
			font-size: 12px;
		}
	</style>
</head>
<body id="commentspopup" style="padding:10px 20px;background:transparent none;min-width:0;">
<div id="gmediacomments" class="comments-area">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			} else {
				?>
				<p class="nocomments"><?php esc_html_e( 'Comments are closed.', 'grand-media' ); ?></p>
				<?php
			}
		endwhile; // have_posts().
	endif;
	?>
</div>
<?php wp_footer(); ?>
</body>
</html>
