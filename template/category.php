<?php
/* only for default template */
add_action( 'gmedia_head', 'gmedia_default_template_styles' );

get_gmedia_header(); ?>

<div class="gmedia-flex-box">
	<div class="gmedia-main-wrapper">
		<?php the_gmedia_content(); ?>
	</div>
</div>

<?php get_gmedia_footer(); ?>
