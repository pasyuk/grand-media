<?php
/**
 * Gmedia Comments
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';

wp_enqueue_script( 'post' );
wp_enqueue_script( 'admin-comments' );

global $gmDB, $gmCore, $gmGallery, $post;

$gmedia_id      = $gmCore->_get( 'gmedia_id' );
$gmedia_term_id = $gmCore->_get( 'gmedia_term_id' );
if ( $gmedia_id ) {
	$gmedia = $gmDB->get_gmedia( $gmedia_id );
	gmedia_item_more_data( $gmedia );
	$post_id = $gmedia->post_id;
} elseif ( $gmedia_term_id ) {
	$gmedia_term = $gmDB->get_term( $gmedia_term_id );
	gmedia_term_item_more_data( $gmedia_term );
	$post_id = $gmedia_term->post_id;
} else {
	die( '-1' );
}

$post = get_post( $post_id );
?>
<div id="commentsdiv" style="padding:1px 0;">
	<style scoped>
		#commentsdiv {
			padding-top: 1px;
		}

		#commentsdiv > .img-thumbnail {
			float: left;
			margin: 0 10px 10px;
		}

		#commentsdiv > .img-thumbnail img.gmedia-thumb {
			max-height: 72px;
		}

		#commentsdiv > h4 {
			margin-left: 10px;
		}

		#commentsdiv .fixed .column-author {
			width: 20%;
		}

		#commentsdiv .row-actions .edit {
			display: none;
		}
	</style>
	<?php
	if ( current_user_can( 'edit_posts' ) ) {
		printf( '<a target="_blank" href="%s" class="float-end">%s</a>', esc_url( add_query_arg( array( 'p' => $post_id ), admin_url( 'edit-comments.php' ) ) ), esc_html__( 'Open in new tab', 'grand-media' ) );
	}
	if ( $gmedia_id ) {
		?>
		<span class="img-thumbnail">
			<?php echo wp_kses_post( gmedia_item_thumbnail( $gmedia ) ); ?>
		</span>
	<?php } ?>
	<h4><?php echo esc_html( $post->post_title ); ?></h4>
	<?php
	post_comment_meta_box( $post );
	wp_comment_reply();
	?>
	<input id="post_ID" name="p" type="hidden" value="<?php echo absint( $post_id ); ?>"/>
</div>
<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready(function($) {
		$('table.comments-box').css('display', '');
	});
	//]]>
</script>
