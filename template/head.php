<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?php gmedia_title( '|', true ); ?></title>
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<style>body {margin: 0;padding: 0;}</style>
	<script>(function() { document.documentElement.className = 'js'; })();</script>
	<?php gmedia_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="gmedia-template-wrapper">

	<?php
	if ( ! isset( $_GET['iframe'] ) ) {
		global $gmedia;
		?>
		<header<?php echo ( $gmedia->description ) ? ' class="has-description"' : ''; ?>>
			<menu class="gmedia-menu">
				<?php
				gmediacloud_social_sharing();
				$home_url = home_url();
				?>
				<div class="gmedia-menu-items">
					<a href="<?php echo esc_url( $home_url ); ?>" class="btn btn-homepage" title="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"><i class="fa fa-home"><span><?php esc_html_e( 'Home', 'grand-media' ); ?></span></i></a>
					<?php
					if ( ! empty( $_SERVER['HTTP_REFERER'] ) && ( $home_url !== $_SERVER['HTTP_REFERER'] ) ) {
						$back_btn = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
						echo '<a href="' . esc_url( $back_btn ) . '" class="btn btn-goback"><i class="fa fa-arrow-left"><span>' . esc_html__( 'Go Back', 'grand-media' ) . '</span></i></a>';
					}
					?>
				</div>
			</menu>
			<div class="gmedia-header-title"><?php the_gmedia_title(); ?></div>
			<?php if ( $gmedia->description ) { ?>
				<div class="gmedia-header-description"><?php echo wp_kses_post( wpautop( $gmedia->description ) ); ?></div>
				<span class="gmedia-header-description-button" onclick="jQuery('.gmedia-header-description').toggle()"></span>
			<?php } ?>
		</header>
		<?php
	}
	?>
