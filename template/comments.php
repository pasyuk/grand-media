<?php
// Do not delete these lines.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( have_comments() ) : ?>
	<ol class="gmediacommentlist">
		<?php
		$wp_list_comments_args = array(
			'per_page'    => 100,
			'avatar_size' => 25,
			'format'      => 'xhtml',
		);
		wp_list_comments( $wp_list_comments_args );
		?>
	</ol>
	<div class="navigation">
		<div class="alignright"><?php next_comments_link( __( 'read other comments', 'grand-media' ) ); ?></div>
	</div>
<?php else : // this is displayed if there are no comments so far. ?>
	<?php if ( comments_open() ) : ?>
		<!-- If comments are open, but there are no comments. -->

	<?php else : // comments are closed. ?>
		<!-- If comments are closed. -->
		<p class="gmedia_nocomments"><?php esc_html_e( 'Comments are closed.' ); ?></p>

	<?php endif; ?>
<?php endif; ?>

<?php
if ( comments_open() ) :
	$postid            = get_the_ID();
	$commenter         = wp_get_current_commenter();
	$user              = wp_get_current_user();
	$user_identity     = $user->exists() ? $user->display_name : '';
	$fields            = array(
		'author' => '<p class="gmedia_comment-form-author"><input id="author" class="gmedia_comments-input" name="author" type="text" placeholder="' . __( 'Name' ) . '" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" required="required" /></p>',
		'email'  => '<p class="gmedia_comment-form-email"><input id="email" class="gmedia_comments-input" name="email" type="email" placeholder="' . __( 'Email' ) . '" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30" required="required" /></p>',
	);
	$comment_form_args = array(
		'fields'               => $fields,
		'comment_field'        => '<p class="gmedia_comment-form-comment"><textarea id="gmedia_comment" name="comment" cols="45" rows="2" required="required" placeholder="' . _x( 'Comment', 'noun' ) . '"></textarea></p>',
		'must_log_in'          => '<p class="gmedia_must-log-in">' . sprintf( __( '<a href="%s">Log in</a> to post a comment.' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $postid ) ) ) ) . '</p>',
		'logged_in_as'         => '<p class="gmedia_logged-in-as">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s">Log out?</a>' ), get_edit_user_link(), esc_html( $user_identity ), wp_logout_url( apply_filters( 'the_permalink', get_permalink( $postid ) ) ) ) . '</p>',
		'comment_notes_before' => '',
		'comment_notes_after'  => '',
		'id_form'              => 'gmedia_commentform',
		'id_submit'            => 'gmedia_submit',
		'class_form'           => 'gmedia_comment-form',
		'class_submit'         => 'gmedia_submit',
		'name_submit'          => 'submit',
		'title_reply'          => '',
		'title_reply_to'       => '',
		'title_reply_before'   => '',
		'title_reply_after'    => '',
		'cancel_reply_before'  => '<div class="gmedia_cancel-reply">',
		'cancel_reply_after'   => '</div>',
		'cancel_reply_link'    => __( 'Cancel reply' ),
		'label_submit'         => __( 'Post Comment' ),
		'submit_button'        => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
		'submit_field'         => '<p class="gmedia_form-submit">%1$s %2$s</p>',
		'format'               => 'html5',
	);
	comment_form( $comment_form_args );
endif; ?>
