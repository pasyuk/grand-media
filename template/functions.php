<?php

remove_all_actions( 'wp_print_styles' );
//add_action('gmedia_head', 'gmediacloud_appbaner');
add_action( 'gmedia_head', 'gmediacloud_meta_generator' );
add_action( 'gmedia_head', 'wp_print_styles', 1000 );
add_action( 'gmedia_head', 'wp_print_head_scripts', 1000 );
add_action( 'gmedia_footer', 'wp_print_styles' );
add_action( 'gmedia_footer', 'print_footer_scripts' );
add_action( 'gmedia_footer', 'wp_print_footer_scripts' );

$gmedia_share_img = array( plugins_url( 'assets/icons/icon_gmedia_180.png', dirname( __FILE__ ) ) );

function gmediacloud_appbaner() {
	global $gmedia_id, $gmedia_type;
	if ( in_array( $gmedia_type, array( 'gallery', 'album', 'tag' ), true ) ) {
		echo '<meta name="apple-itunes-app" content="app-id=947515626, app-argument=' . esc_url( add_query_arg( array( 'type' => $gmedia_type, 'id' => $gmedia_id ), trailingslashit( home_url() ) ) ) . '">';
	}

}

function gmediacloud_meta_generator() {
	global $gmedia, $gmedia_type, $wp, $gmGallery, $gmCore, $gmedia_share_img, $gmDB;
	$icon_url    = plugins_url( 'assets/icons', dirname( __FILE__ ) );
	$current_url = home_url( add_query_arg( array(), $wp->request ) );
	?>
	<link href="<?php echo esc_url( $icon_url ); ?>/favicon.png" rel="shortcut icon"/>
	<link href="<?php echo esc_url( $icon_url ); ?>/icon_gmedia_60.png" rel="apple-touch-icon"/>
	<link href="<?php echo esc_url( $icon_url ); ?>/icon_gmedia_76.png" rel="apple-touch-icon" sizes="76x76"/>
	<link href="<?php echo esc_url( $icon_url ); ?>/icon_gmedia_120.png" rel="apple-touch-icon" sizes="120x120"/>
	<link href="<?php echo esc_url( $icon_url ); ?>/icon_gmedia_152.png" rel="apple-touch-icon" sizes="152x152"/>
	<link href="<?php echo esc_url( $icon_url ); ?>/icon_gmedia_180.png" rel="apple-touch-icon" sizes="180x180"/>

	<meta property="og:title" content="<?php echo esc_attr( the_gmedia_title( true ) ); ?>"/>
	<meta property="og:description" content="<?php echo esc_attr( $gmedia->description ); ?>"/>
	<?php
	if ( 'single' !== $gmedia_type ) {
		if ( did_action( 'gmedia_shortcode' ) && count( $gmGallery->shortcode ) ) {
			$og_imgs   = array();
			$shortcode = reset( $gmGallery->shortcode );
			$query     = array_merge(
				array(
					'status'   => 'publish',
					'nopaging' => true,
				),
				$shortcode['query']
			);
			$gmedias   = $gmDB->get_gmedias( $query );
			foreach ( $gmedias as $item ) {
				$og_imgs[] = $gmCore->gm_get_media_image( $item->ID );
			}
			$gmedia_share_img = array_merge( $og_imgs, $gmedia_share_img );
		}
	} else {
		array_unshift( $gmedia_share_img, $gmCore->gm_get_media_image( $gmedia->ID ) );
	}
	foreach ( $gmedia_share_img as $og_image ) {
		echo '<meta property="og:image" content="' . esc_attr( $og_image ) . '" />';
	}
	?>

	<meta property="og:url" content="<?php echo esc_url( $current_url ); ?>"/>
	<!--<meta property="og:type" content="article" />-->
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"/>

	<meta name="msapplication-TileImage" content="<?php echo esc_url( $icon_url ); ?>/icon_gmedia_180.png"/>
	<meta name="msapplication-TileColor" content="#ffffff"/>
	<?php
}

function gmedia_head() {
	global $wp_styles, $wp_scripts, $gmCore;
	global $gmedia_id, $gmedia_type, $gmedia_shortcode_content;

	do_action( 'wp_enqueue_scripts' );
	add_filter( 'show_admin_bar', '__return_false' );
	if ( $gmCore->_get( 'iframe' ) ) {
		wp_deregister_script( 'swfaddress' );
	}
	$wp_styles->queue  = array();
	$wp_scripts->queue = array();

	/*
	if ( is_admin_bar_showing() ) {
		add_action('gmedia_head', 'wp_admin_bar_header', 0);
		add_action('gmedia_head', '_admin_bar_bump_cb', 0);
		add_action('gmedia_head', '_wp_admin_bar_init');
		add_action('gmedia_footer', 'wp_admin_bar_render', 1000);
	}
	*/

	$gmedia_shortcode_content = get_the_gmedia_content( $gmedia_id, $gmedia_type );

	do_action( 'gmedia_head' );
}

function gmedia_footer() {
	global $gmGallery;

	do_action( 'gmedia_footer' );

	if ( ! empty( $gmGallery->options['gmediacloud_footer_css'] ) ) {
		$css_code = stripslashes( $gmGallery->options['gmediacloud_footer_css'] );
		echo "\n<style>\n" . wp_kses_data( $css_code ) . "\n</style>\n";
	}
	if ( ! empty( $gmGallery->options['gmediacloud_footer_js'] ) ) {
		$js_code = stripslashes( $gmGallery->options['gmediacloud_footer_js'] );
		// phpcs:ignore
		echo "\n<script type=\"text/javascript\">\n" . str_replace('&amp;', '&', wp_kses_post( $js_code ) ) . "\n</script>\n";
	}
}

/**
 * @param string $sep
 * @param bool   $display
 *
 * @return string|void
 */
function gmedia_title( $sep = '|', $display = true ) {
	global $gmedia, $gmedia_type;

	$_title = __( 'GmediaGallery', 'grand-media' );
	if ( is_object( $gmedia ) && ! is_wp_error( $gmedia ) ) {
		if ( in_array( $gmedia_type, array( 'gallery', 'album', 'category', 'tag' ), true ) ) {
			$_title = $gmedia->name;
		} elseif ( 'single' === $gmedia_type ) {
			$_title = $gmedia->title;
		}
	}

	$title[] = $_title;

	if ( current_theme_supports( 'title-tag' ) ) {
		$title[] = get_bloginfo( 'name', 'display' );
	}

	$title = implode( " $sep ", $title );

	/**
	 * Filter the text of the gmedia title.
	 *
	 * @param string $title Page title.
	 * @param string $sep   Title separator.
	 */
	$title = apply_filters( 'gmedia_title', $title, $sep );

	// Send it out.
	if ( $display ) {
		echo esc_html( $title );
	} else {
		return $title;
	}
}

/**
 * @param bool $return
 *
 * @return mixed|string|void
 */
function the_gmedia_title( $return = false ) {
	global $gmedia, $gmedia_type;

	$title = __( 'GmediaGallery', 'grand-media' );
	if ( is_object( $gmedia ) && ! is_wp_error( $gmedia ) ) {
		if ( in_array( $gmedia_type, array( 'gallery', 'album', 'category', 'tag' ), true ) ) {
			$title = wp_strip_all_tags( $gmedia->name );
		} elseif ( 'single' === $gmedia_type ) {
			$title = wp_strip_all_tags( $gmedia->title );
		}
	}

	/**
	 * Filter the text of the gmedia title.
	 *
	 * @param string $title Page title.
	 * @param string $sep   Title separator.
	 */
	$title = apply_filters( 'the_gmedia_title', $title );

	if ( $return ) {
		return $title;
	} else {
		echo esc_html( $title );
	}
}

/**
 * @param $classes
 *
 * @return array
 */
function gmedia_body_class( $classes ) {
	global $gmedia_type;
	$classes = array_merge( $classes, array( 'gmedia-template', "gmedia-template-{$gmedia_type}" ) );
	if ( wp_is_mobile() ) {
		$classes[] = 'is_mobile';
	}
	if ( isset( $_GET['is_admin_preview'] ) ) {
		$classes[] = 'gmedia-module-preview';
	}
	$classes = apply_filters( 'gmedia_body_class', $classes );

	return (array) $classes;
}

add_filter( 'body_class', 'gmedia_body_class' );

function get_gmedia_header() {
	global $gmedia_module, $gmCore;
	$module = $gmCore->get_module_path( $gmedia_module );
	if ( is_file( $module['path'] . '/template/head.php' ) ) {
		/* @noinspection PhpIncludeInspection */
		include_once $module['path'] . '/template/head.php';
	} else {
		/* @noinspection PhpIncludeInspection */
		include_once GMEDIA_ABSPATH . 'template/head.php';
	}
}

function get_gmedia_footer() {
	global $gmedia_module, $gmCore;
	$module = $gmCore->get_module_path( $gmedia_module );
	if ( is_file( $module['path'] . '/template/foot.php' ) ) {
		/* @noinspection PhpIncludeInspection */
		include_once $module['path'] . '/template/foot.php';
	} else {
		/* @noinspection PhpIncludeInspection */
		include_once GMEDIA_ABSPATH . 'template/foot.php';
	}
}

/**
 * @param      $gmedia_id
 * @param      $gmedia_type
 *
 * @param null $set
 *
 * @return string
 */
function get_the_gmedia_content( $gmedia_id, $gmedia_type, $set = null ) {
	$content = '';
	if ( in_array( $gmedia_type, array( 'gallery', 'album', 'tag', 'category' ), true ) ) {
		$atts    = array(
			'id' => $gmedia_id,
		);
		$content = gmedia_shortcode( $atts );
		do_action( 'gmedia_enqueue_scripts' );
	}

	return $content;
}

function the_gmedia_content() {
	global $gmedia_shortcode_content;

	// Shortcode content already escaped and doing it twice broke the code.
	// phpcs:ignore
	echo $gmedia_shortcode_content;
}

function gmediacloud_social_sharing() {
	global $gmGallery;

	$gmediacloud_socialbuttons = isset( $gmGallery->options['gmediacloud_socialbuttons'] ) ? intval( $gmGallery->options['gmediacloud_socialbuttons'] ) : 1;
	if ( 0 === $gmediacloud_socialbuttons ) {
		return;
	}
	if ( apply_filters( 'gmediacloud_social_sharing', wp_is_mobile() ) ) {
		return;
	}

	global $wp, $gmedia, $gmedia_share_img;

	$url        = rawurlencode( esc_url_raw( home_url( add_query_arg( array(), $wp->request ) ) ) );
	$text       = $gmedia->description;
	$title      = wp_strip_all_tags( the_gmedia_title( true ) );
	$image      = rawurlencode( $gmedia_share_img[0] );
	$title_text = rawurlencode( $title . ' ' . wp_strip_all_tags( $text ) );
	$mailbody   = esc_attr( $text . ' ' . $url );
	?>
	<style>
		/*@import url('//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css') all;*/
		/*.fa span { display:none; }*/
		.gmedia-socialsharebuttons {
			float: right;
			margin-right: 30px;
			margin-top: 2px;
		}

		.share-btn,
		.share-btn:visited {
			display: inline-block;
			color: #ffffff;
			border: none;
			padding: 2px 7px;
			min-width: 2.1em;
			opacity: 0.9;
			box-shadow: 0 2px 0 0 rgba(0, 0, 0, 0.2);
			outline: none;
			text-align: center;
			box-sizing: border-box;
			text-decoration: none;
		}

		.share-btn:hover {
			color: #eeeeee;
			text-decoration: none;
		}

		.share-btn:active {
			position: relative;
			top: 2px;
			box-shadow: none;
			color: #e2e2e2;
			outline: none;
		}

		.share-btn.facebook {
			background: #3B5998;
		}

		.share-btn.twitter {
			background: #55acee;
		}

		.share-btn.pinterest-p {
			background: #cb2027;
		}

		.share-btn.vk {
			background: #2a6db4;
		}

		.share-btn.email {
			background: #444444;
		}
	</style>
	<div class="gmedia-socialsharebuttons">
		<!-- Facebook -->
		<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $url ); ?>&t=<?php echo esc_attr( $title_text ); ?>" target="_blank" class="share-btn facebook">
			<i class="fa fa-facebook"><span>Facebook</span></i>
		</a>
		<!-- Twitter -->
		<a href="https://twitter.com/share?url=<?php echo esc_attr( $url ); ?>&text=<?php echo esc_attr( $title_text ); ?>" target="_blank" class="share-btn twitter">
			<i class="fa fa-twitter"><span>Twitter</span></i>
		</a>
		<!-- Pinterest -->
		<a href="https://pinterest.com/pin/create/button/?url=<?php echo esc_attr( $url ); ?>&description=<?php echo esc_attr( $title_text ); ?>&media=<?php echo esc_attr( $image ); ?>" target="_blank" class="share-btn pinterest-p">
			<i class="fa fa-pinterest-p"><span>Pinterest</span></i>
		</a>
		<!-- VK -->
		<a href="https://vk.com/share.php?url=<?php echo esc_attr( $url ); ?>" target="_blank" class="share-btn vk">
			<i class="fa fa-vk"><span>VK</span></i>
		</a>
		<!-- Email -->
		<a href="mailto:?subject=<?php echo esc_attr( $title ); ?>&body=<?php echo esc_attr( $mailbody ); ?>" target="_blank" class="share-btn email">
			<i class="fa fa-envelope"><span>Email</span></i>
		</a>
	</div>
	<?php
}

function gmedia_default_template_styles() {
	?>
	<style>
		* {
			box-sizing: border-box;
		}

		body.gmedia-template {
			font-family: "Arial", "Verdana", serif;
			font-size: 13px;
		}

		.gmedia-template-wrapper {
			display: -webkit-box;
			display: -moz-box;
			display: -ms-flexbox;
			display: -webkit-flex;
			display: flex;
			-webkit-flex-flow: column;
			flex-flow: column;
			position: absolute;
			left: 0;
			top: 0;
			right: 0;
			bottom: 0;
		}

		header {
			position: relative;
			min-height: 30px;
			background-color: #0f0f0f;
			color: #f1f1f1;
			padding: 5px 0 3px 30px;
			font-family: "Arial", "Verdana", serif;
			z-index: 10;
		}

		header.has-description {
			padding-right: 30px;
		}

		.gmedia-header-title {
			display: inline-block;
			font-size: 16px;
			vertical-align: bottom;
			margin-top: 2px;
		}

		.gmedia-header-description {
			position: absolute;
			top: 100%;
			left: 0;
			right: 0;
			font-size: 13px;
			overflow: visible;
			background-color: #0f0f0f;
			padding: 10px 30px;
			border-bottom: 1px solid #444444;
		}

		.gmedia-header-description {
			display: none;
		}

		.gmedia-header-description-button {
			position: absolute;
			top: 5px;
			right: 15px;
			width: 18px;
			height: 20px;
			background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNi4wLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB3aWR0aD0iNTEycHgiIGhlaWdodD0iNTEycHgiIHZpZXdCb3g9IjAgMCA1MTIgNTEyIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCA1MTIgNTEyIiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxwYXRoIGZpbGw9IiNGRkZGRkYiIGQ9Ik0yOTMuNzUxLDQ1NS44NjhjLTIwLjE4MSwyMC4xNzktNTMuMTY1LDE5LjkxMy03My42NzMtMC41OTVsMCwwYy0yMC41MDgtMjAuNTA4LTIwLjc3My01My40OTMtMC41OTQtNzMuNjcyICBsMTg5Ljk5OS0xOTBjMjAuMTc4LTIwLjE3OCw1My4xNjQtMTkuOTEzLDczLjY3MiwwLjU5NWwwLDBjMjAuNTA4LDIwLjUwOSwyMC43NzIsNTMuNDkyLDAuNTk1LDczLjY3MUwyOTMuNzUxLDQ1NS44Njh6Ii8+DQo8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNMjIwLjI0OSw0NTUuODY4YzIwLjE4LDIwLjE3OSw1My4xNjQsMTkuOTEzLDczLjY3Mi0wLjU5NWwwLDBjMjAuNTA5LTIwLjUwOCwyMC43NzQtNTMuNDkzLDAuNTk2LTczLjY3MiAgbC0xOTAtMTkwYy0yMC4xNzgtMjAuMTc4LTUzLjE2NC0xOS45MTMtNzMuNjcxLDAuNTk1bDAsMGMtMjAuNTA4LDIwLjUwOS0yMC43NzIsNTMuNDkyLTAuNTk1LDczLjY3MUwyMjAuMjQ5LDQ1NS44Njh6Ii8+DQo8L3N2Zz4=);
			background-size: contain;
			cursor: pointer;
		}

		.gmedia-menu {
			float: right;
			margin: 0 30px 0 0;
			padding: 0;
		}

		.gmedia-menu .gmedia-menu-items {
			margin-right: 30px;
			float: right;
			margin-top: 2px;
		}

		.gmedia-menu .gmedia-menu-items a,
		.gmedia-menu .gmedia-menu-items a:visited {
			display: inline-block;
			color: #ffffff;
			background: #444444;
			border: none;
			padding: 2px 7px;
			min-width: 2.1em;
			opacity: 0.9;
			box-shadow: 0 2px 0 0 rgba(0, 0, 0, 0.2);
			outline: none;
			text-align: center;
			box-sizing: border-box;
			text-decoration: none;
		}

		.gmedia-menu .gmedia-menu-items a i span {
			font-style: normal;
		}

		.gmedia-menu .gmedia-menu-items a:hover {
			color: #eeeeee;
		}

		.gmedia-menu .gmedia-menu-items a:active {
			position: relative;
			top: 2px;
			box-shadow: none;
			color: #e2e2e2;
			outline: none;
		}

		.gmedia-flex-box {
			-webkit-box-flex: 1;
			-moz-box-flex: 1;
			-webkit-flex: 1;
			-ms-flex: 1;
			flex: 1;
			position: relative;
		}

		.gmedia-main-wrapper {
			overflow: auto;
			position: absolute;
			left: 0;
			right: 0;
			top: 0;
			bottom: 0;
		}

		body.admin-bar .gmedia-template-wrapper {
			top: 32px;
		}

		.gmedia-main-wrapper .gmedia_gallery {
			width: 100%;
			height: 100%;
			text-align: center;
		}

		body.is_mobile .gmedia-main-wrapper .gmedia_gallery {
			height: auto;
		}

		.gmedia-main-wrapper .gmedia_gallery > div {
			margin-left: auto;
			margin-right: auto;
			text-align: left;
		}

		.gmedia-main-wrapper .gmedia_gallery.is_mobile {
			height: auto;
			min-height: 100%;
		}

		.gmedia-main-wrapper object {
			width: 100% !important;
			height: 100% !important;
			display: block;
		}

		a {
			color: #2e6286;
			text-decoration: underline;
		}

		a:hover, a:active, a:visited {
			color: #2e6286;
			text-decoration: none;
		}

		body.gmedia-template-single {
			background-color: #bbbbbb;
		}

		.single-view {
			max-width: 1280px;
			min-width: 320px;
			padding: 10px 10px 20px;
			margin: 0 auto;
		}

		.single-view img {
			max-width: 100%;
			height: auto;
		}

		.single-title {
			font-size: 18px;
			font-weight: bold;
		}

		.type-download .single-title {
			font-size: 18px;
		}

		.image-description {
			text-align: left
		}

		.gmedia-no-files {
			text-align: center;
			font-size: 16px;
			padding: 30px 10px;
		}

		.gmediaShortcodeError {
			text-align: left;
			font-size: 14px;
			padding: 30px 10px;
		}

		@media screen and ( max-width: 782px ) {
			body.admin-bar .gmedia-template-wrapper {
				top: 46px;
			}
		}
	</style>
	<?php
}

function gmedia_video_head_scripts() {
	wp_enqueue_style( 'mediaelement' );
	wp_enqueue_script( 'mediaelement' );
}

function gmedia_video_foot_scripts() {
	?>
	<script type="text/javascript">
			jQuery(function($) {
				var video = $('video');

				function video_responsive() {
					var vw = video.width(),
							vh = video.height(),
							r = vw / vh,
							bw = $(window).width(),
							bh = $(window).height(),
							mar;
					if (r > bw / bh) {
						vh = bw / r;
						vw = bw;
						mar = (bh - vh) / 2;
						mar = (mar > 0) ? mar + 'px 0 0 0' : '0';
					}
					else {
						vw = bh * r;
						vh = bh;
						mar = (bh - vh) / 2;
						mar = (mar > 0) ? '0 0 0 ' + mar + 'px' : '0';
					}
					$('body').css({margin: mar});
					video.attr('width', vw).attr('height', vh);
				}

				video_responsive();
				$(window).on('resize', function() {
					video_responsive();
				});
				video.mediaelementplayer();
			});
	</script>
	<?php
}
