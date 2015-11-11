<?php
/* only for default template */
add_action('gmedia_head', 'gmedia_default_template_styles');

/**
 * @var $gmedia
 * @var $gmGallery
 */
get_gmedia_header(); ?>

	<header>
		<menu class="gmedia-menu">
			<?php gmediacloud_social_sharing();
			$home_url = home_url();
			?>
			<div class="gmedia-menu-items">
				<a href="<?php echo $home_url; ?>" class="btn btn-homepage" title="<?php echo esc_attr(get_bloginfo('name')); ?>"><i class="fa fa-home"><span><?php _e('Home', 'grand-media') ?></span></i></a>
				<?php if(!empty($_SERVER['HTTP_REFERER']) && ($home_url != $_SERVER['HTTP_REFERER'])){
					echo "<a href='{$_SERVER['HTTP_REFERER']}' class='btn btn-goback'><i class='fa fa-arrow-left'><span>".__('Go Back', 'grand-media')."</span></i></a>";
				} ?>
			</div>
		</menu>
		<div class="gmedia-header-title"><?php the_gmedia_title(); ?></div>
	</header>
	<div class="gmedia-flex-box">
		<div class="gmedia-main-wrapper">
			<?php the_gmedia_content();	?>
		</div>
	</div>

<?php get_gmedia_footer(); ?>