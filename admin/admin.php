<?php

/**
 * GmediaAdmin - Admin Section for GRAND Media
 */
class GmediaAdmin {
	var $pages = array();
	var $body_classes = array();

	/**
	 * constructor
	 */
	function __construct() {
		global $pagenow;

		add_action( 'admin_head', array( &$this, 'admin_head' ) );

		// Add the admin menu
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );

		// Add the script and style files
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_scripts' ), 20 );
		add_action( 'admin_print_scripts-widgets.php', array( &$this, 'gmedia_widget_scripts' ) );

		add_action( 'enqueue_block_editor_assets', array( &$this, 'gutenberg_assets' ) );

		add_filter( 'screen_settings', array( &$this, 'screen_settings' ), 10, 2 );
		add_filter( 'set-screen-option', array( &$this, 'screen_settings_save' ), 11, 3 );

		if ( isset( $_GET['page'] ) && ( false !== strpos( $_GET['page'], 'GrandMedia' ) ) ) {
			$this->body_classes[] = 'grand-media-admin-page';

			if ( ! isset( $_GET['gmediablank'] ) || 'library' === $_GET['gmediablank'] ) {
				$this->body_classes[] = $_GET['page'];
				if ( ! empty( $_GET['mode'] ) ) {
					$this->body_classes[] = $_GET['page'] . '_' . $_GET['mode'];
				}
				if ( isset( $_GET['edit_term'] ) || isset( $_GET['gallery_module'] ) || isset( $_GET['preset'] ) ) {
					$this->body_classes[] = $_GET['page'] . '_edit';
				}
			}

			if ( ( 'admin.php' == $pagenow ) && isset( $_GET['gmediablank'] ) ) {
				add_action( 'admin_init', array( &$this, 'gmedia_blank_page' ) );
			}

			add_action( 'admin_footer', array( &$this, 'admin_footer' ) );
		}

	}

	/**
	 * admin_head
	 */
	function admin_head() {
		add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ) );

		if ( isset( $_GET['page'] ) && ( false !== strpos( $_GET['page'], 'GrandMedia' ) ) ) {
			?>
			<style type="text/css" id="gmedia_admin_css">html, body { background: <?php echo isset( $_GET['gmediablank'] )? 'transparent' : '#708090'; ?>; }</style>
			<?php
		}
	}

	/**
	 * admin_body_class
	 *
	 * @param $classes_string
	 *
	 * @return string
	 */
	function admin_body_class( $classes_string ) {
		$classes = $this->body_classes;

		$classes[] = $classes_string;
		if ( isset( $_GET["gmediablank"] ) ) {
			$classes[] = "gmedia-blank gmedia_{$_GET['gmediablank']}";
		}
		$classes = array_filter( $classes );

		return implode( ' ', $classes );
	}

	/**
	 * Load gmedia pages in wpless interface
	 */
	function gmedia_blank_page() {
		set_current_screen( 'GrandMedia_Settings' );

		global $gmCore, $gmProcessor;
		$gmediablank = $gmCore->_get( 'gmediablank', '' );
		define( 'IFRAME_REQUEST', true );

		iframe_header( 'GmediaGallery' );

		echo '<div id="gmedia-container">';
		switch ( $gmediablank ) {
			case 'update_plugin':
				require_once( dirname( dirname( __FILE__ ) ) . '/config/update.php' );
				gmedia_do_update();
				break;
			case 'image_editor':
				require_once( dirname( dirname( __FILE__ ) ) . '/inc/image-editor.php' );
				gmedia_image_editor();
				break;
			case 'map_editor':
				require_once( dirname( dirname( __FILE__ ) ) . '/inc/map-editor.php' );
				gmedia_map_editor();
				break;
			case 'library':
				echo '<div id="gmedia_iframe_content">';
				echo '<div id="gm-message">' . $gmCore->alert( 'success', $gmProcessor->msg ) . $gmCore->alert( 'danger', $gmProcessor->error ) . '</div>';
				include( GMEDIA_ABSPATH . 'admin/pages/library/library.php' );
				echo '</div>';
				break;
			case 'comments':
				require_once( dirname( __FILE__ ) . '/tpl/comments.php' );
				break;
			case 'module_preview':
				require_once( dirname( __FILE__ ) . '/tpl/module-preview.php' );
				break;
		}
		echo '</div>';

		iframe_footer();
		exit;
	}

	// integrate the menu
	function add_menu() {

		$count = '';
		if ( current_user_can( 'gmedia_module_manage' ) ) {
			global $gmGallery;
			if ( $gmGallery->options['modules_update'] ) {
				$count .= " <span class='update-plugins count-{$gmGallery->options['modules_update']}' style='background-color: #bb391b;'><span class='plugin-count gm-module-count gm-modules-update-count' title='" . __( 'Modules Updates', 'grand-media' ) . "'>{$gmGallery->options['modules_update']}</span></span>";
			}
			if ( $gmGallery->options['modules_new'] ) {
				$count .= " <span class='update-plugins count-{$gmGallery->options['modules_new']}' style='background-color: #367236;'><span class='plugin-count gm-module-count gm-modules-new-count' title='" . __( 'New Modules', 'grand-media' ) . "'>{$gmGallery->options['modules_new']}</span></span>";
			}
		}

		$this->pages   = array();
		$this->pages[] = add_menu_page( __( 'Gmedia Library', 'grand-media' ), "Gmedia{$count}", 'gmedia_library', 'GrandMedia', array(
			&$this,
			'shell',
		), 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSIyMHB4IiBoZWlnaHQ9IjIwcHgiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMjAgMjAiIHhtbDpzcGFjZT0icHJlc2VydmUiPiAgPGltYWdlIGlkPSJpbWFnZTAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgeD0iMCIgeT0iMCIKICAgIHhsaW5rOmhyZWY9ImRhdGE6aW1hZ2UvcG5nO2Jhc2U2NCxpVkJPUncwS0dnb0FBQUFOU1VoRVVnQUFBQlFBQUFBVUNBTUFBQUM2ViswL0FBQUFCR2RCVFVFQUFMR1BDL3hoQlFBQUFDQmpTRkpOCkFBQjZKZ0FBZ0lRQUFQb0FBQUNBNkFBQWRUQUFBT3BnQUFBNm1BQUFGM0NjdWxFOEFBQUJrbEJNVkVVeFpua3haM2d4WjNoQ2RJTnUKbEtBK2NZQnBrSjJRcmJhb3Y4YTV5OUdadEx5UnJyZG1qcHMzYTN4WmhKS0txYktPckxXcndjaW52c1dWc2JxM3l0Q1hzcnRWZ1k5UwpmNDZndWNGN25hZzdibjVFZFlWeGxxS01xclN3eGN1aHVzS2Z1TUMrejlSMm1xWTZibjZ1dzhyNy9QekYxTm0weU03dDh2UHo5dmVGCnBhL2Y1K3BiaHBSSWVJZCtvS3FOcTdTZHQ3OWhpcGN5YUhuSDF0clMzdUZIZDRiSzJOMzUrL3YzK2ZxOXp0UmFoWk5QZll5Qm9xeUMKbzYwOGIzOUdkb1poaTVoT2ZJdnE3L0dwdjhiLy8vLzIrUG45L2YzQjBkWkFjb0tZczd3emFIbSt6OVZxa1oxWGc1SFQzK0xZNHVaNgpuYWh3bGFGRGRJVFAzT0JLZVloTWU0bnc5UFhoNmV2eDlmYkwyZDFUZ0k1em1LTXphWHJUM3VLWXM3dlAyOS9WNE9PY3RyN2c2T3VVCnNMbE5mSXU0eTlEbzd2QkZkb1YzbTZibTdlODViWDNJMXR1RHBLN1EzT0JZaEpHUHJMWEMwdGVsdmNSSmVZamI1ZWpOMnQ1eWw2S1cKc3JyYjVPZUFvYXhqakpuZTUrbDJtcVhFMDlpSHByQnRrNTl5bDZOOG5xazRiSDNXNGVUVTMrUFIzZUdxd01jY1RNSnpBQUFBQW5SUwpUbE51MlhMaTRXRUFBQUFCWWt0SFJFVDV0SmpCQUFBQUNYQklXWE1BQUFzVEFBQUxFd0VBbXB3WUFBQUFCM1JKVFVVSDRBc0NDRGNJCmw0WXhCZ0FBQVIxSlJFRlVHTk5qWUdCa1FnT01ESmhpSUZFNGs1bUZGY2FFQzdLeGMzQnljZlB3SWd2eWNmRUxDQW9KYzRxSThvdEIKQmNVRkpDU2xtS1JsWklYbDVCVVVsVUNDeWlxcWF1b2FtbHJhT3N4TXVucjZCb1pBUVNOakUxTW1Kak56QzBzQkt5WW1hMTBiUHFDZwpyWWFkdllLRG81T3ppNnVidTRlOHA1QVhVTkJiMGNmWHo5OHVJRkNDS1VneE9NUk9DR2hScUdaWU9MTmRSR1JVZ0FFVGsxU0VYMVEwCkUwTk1ySGRjZklKVVlwSzdiRExRMHBUVXRIUW1ob3pNTEgzVGhPd2MzY3pjUExEelRMU1lHUElMbUR3S2k0cExtRXFUSWQ3Z0tHUmkKWUF1elk3SXJpeXV2cUlTSVNWVlZBeTJxQ2ErdERtU3BxMk1KckZkcXlNbjN5MjRFQ25weEp6UWxOUHZGeHBxMDVBWUh4N2Z5SW9VUwpNc0FleU5paUF3Q3FwalN3RnBqcGxnQUFBQ1YwUlZoMFpHRjBaVHBqY21WaGRHVUFNakF4TmkweE1TMHdNbFF3T0RvMU5Ub3dPQzB3Ck56b3dNSWl4dXBvQUFBQWxkRVZZZEdSaGRHVTZiVzlrYVdaNUFESXdNVFl0TVRFdE1ESlVNRGc2TlRVNk1EZ3RNRGM2TURENTdBSW0KQUFBQUFFbEZUa1N1UW1DQyIgLz4KPC9zdmc+Cg==', 11 );
		$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Library', 'grand-media' ), __( 'Gmedia Library', 'grand-media' ), 'gmedia_library', 'GrandMedia', array( &$this, 'shell' ) );
		if ( current_user_can( 'gmedia_library' ) ) {
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Add Media Files', 'grand-media' ), __( 'Add/Import Files', 'grand-media' ), 'gmedia_upload', 'GrandMedia_AddMedia', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Tags', 'grand-media' ), __( 'Tags', 'grand-media' ), 'gmedia_tag_manage', 'GrandMedia_Tags', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Categories', 'grand-media' ), __( 'Categories', 'grand-media' ), 'gmedia_category_manage', 'GrandMedia_Categories', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Albums', 'grand-media' ), __( 'Albums', 'grand-media' ), 'gmedia_album_manage', 'GrandMedia_Albums', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Galleries', 'grand-media' ), __( 'Galleries', 'grand-media' ), 'gmedia_gallery_manage', 'GrandMedia_Galleries', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Modules', 'grand-media' ), __( 'Modules', 'grand-media' ), 'gmedia_gallery_manage', 'GrandMedia_Modules', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Settings', 'grand-media' ), __( 'Settings', 'grand-media' ), 'manage_options', 'GrandMedia_Settings', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'iOS Application', 'grand-media' ), __( 'iOS Application', 'grand-media' ), 'gmedia_settings', 'GrandMedia_App', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'WordPress Media Library', 'grand-media' ), __( 'WP Media Library', 'grand-media' ), 'gmedia_import', 'GrandMedia_WordpressLibrary', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Logs', 'grand-media' ), __( 'Gmedia Logs', 'grand-media' ), 'manage_options', 'GrandMedia_Logs', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Support', 'grand-media' ), __( 'Support', 'grand-media' ), 'manage_options', 'GrandMedia_Support', array( &$this, 'shell' ) );
		}

		foreach ( $this->pages as $page ) {
			add_action( "load-$page", array( &$this, 'screen_help' ) );
		}
	}

	/**
	 * Load the script for the defined page and load only this code
	 * Display shell of plugin
	 */
	function shell() {
		global $gmCore, $gmProcessor, $gmGallery;

		$sideLinks = $this->sideLinks();

		// check for upgrade
		if ( get_option( 'gmediaDbVersion' ) != GMEDIA_DBVERSION ) {
			if ( get_transient( 'gmediaUpgrade' ) || ( isset( $_GET['do_update'] ) && ( 'gmedia' == $_GET['do_update'] ) ) ) {
				$sideLinks['grandTitle'] = __( 'Updating GmediaGallery Plugin', 'grand-media' );
				$sideLinks['sideLinks']  = '';
				$gmProcessor->page       = 'GrandMedia_Update';
			} else {
				return;
			}
		}

//        global $wpdb;
//        $query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia_term");
//        foreach($query as $item){
//            $name = $gmCore->mb_convert_encoding_utf8($item->name);
//            $wpdb->update($wpdb->prefix . 'gmedia_term', array('name' => $name), array('term_id' => $item->term_id));
//        }
//        echo '<pre>' . print_r($query, true) . '</pre>';

		?>
		<div id="gmedia-container" class="gmedia-admin">
			<?php
			if ( $gmProcessor->page !== 'GrandMedia_App' && ! isset( $gmGallery->options['gmedia_service'] ) && current_user_can( 'manage_options' ) ) {
				$this->collect_data_permission();
			}
			?>
			<div id="gmedia-header" class="clearfix">
				<div id="gmedia-logo">Gmedia
					<small> by CodEasily.com</small>
				</div>
				<h2><?php echo $sideLinks['grandTitle']; ?></h2>
				<?php
				if ( ! is_plugin_active( 'woowbox/woowbox.php' ) && empty( $gmGallery->options['disable_ads'] ) ) {
					?>
					<div class="promote-woowbox"><a href="https://bit.ly/woowbox" target="_blank"><img src="<?php echo plugins_url( '/grand-media/admin/assets/img/woowbox-promote.png' ) ?>" alt="Try WoowBox Gallery plugin"/></a></div>
					<?php
				}
				?>
			</div>
			<div class="container-fluid">
				<div class="row row-fx180-fl">
					<div class="col-sm-2 hidden-xs" id="sidebar" role="navigation">
						<?php echo $sideLinks['sideLinks']; ?>

						<?php
						if ( (int) $gmGallery->options['feedback'] ) {
							$installDate = get_option( 'gmediaInstallDate' );
							if ( $installDate && ( strtotime( $installDate ) < strtotime( '2 weeks ago' ) ) ) { ?>
								<div class="row panel panel-default visible-lg-block">
									<div class="panel-heading" data-toggle="collapse" data-target="#support_div_collapse" aria-expanded="true" aria-controls="support_div_collapse" style="cursor:pointer;">
										<b><?php _e( 'Any feedback?', 'grand-media' ); ?></b>
									</div>
									<div class="collapse<?php if ( empty( $gmGallery->options['license_key'] ) ) {
										echo ' in';
									} ?>" id="support_div_collapse">
										<div class="panel-body">
											<p><?php _e( 'You can help me spread the word about GmediaGallery among the users striving to get awesome galleries on their WordPress sites.', 'grand-media' ); ?></p>

											<p>
												<a class="btn btn-primary" href="https://wordpress.org/support/view/plugin-reviews/grand-media?filter=5" target="_blank"><?php _e( 'Rate Gmedia Gallery', 'grand-media' ); ?></a>
											</p>

											<p><?php _e( 'Your reviews and ideas helps me to create new awesome modules and to improve plugin.', 'grand-media' ); ?></p>
										</div>
									</div>
								</div>
							<?php }
						}
						if ( (int) $gmGallery->options['twitter'] ) {
							?>
							<div class="row panel visible-lg-block">
								<a class="twitter-timeline" href="https://twitter.com/CodEasily/timelines/648240437141086212?ref_src=twsrc%5Etfw">#GmediaGallery - Curated tweets by CodEasily</a>
								<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
							</div>
							<?php
						} ?>
					</div>
					<div class="col-sm-10 col-xs-12">
						<div id="gm-message"><?php
							echo $gmCore->alert( 'success', $gmProcessor->msg );
							echo $gmCore->alert( 'danger', $gmProcessor->error );
							?></div>

						<?php $this->controller(); ?>

					</div>
				</div>
			</div>
		</div>
		<?php
	}

	function sideLinks() {
		global $submenu, $gmProcessor, $gmGallery;
		$content['sideLinks'] = '
		<div id="gmedia-navbar">
			<div class="row">
				<ul class="list-group"><li>';
		if ( empty( $gmGallery->options['license_key'] ) ) {
			$content['sideLinks'] .= "\n" . '<a class="list-group-item list-group-item-premium" target="_blank" href="https://codeasily.com/product/one-site-license/">' . __( 'Get Gmedia Premium', 'grand-media' ) . '</a></li><li>';
		}
		foreach ( $submenu['GrandMedia'] as $menuKey => $menuItem ) {
			if ( $menuItem[2] == $gmProcessor->page ) {
				$iscur                 = ' active';
				$content['grandTitle'] = $menuItem[3];
			} else {
				$iscur = '';
			}
			$menuData = '';
			if ( $menuItem[2] == 'GrandMedia_Modules' && gm_user_can( 'module_manage' ) ) {
				$menuData = '<span class="badge badge-success pull-right gm-module-count-' . $gmGallery->options['modules_new'] . '" title="' . __( 'New Modules', 'grand-media' ) . '">' . $gmGallery->options['modules_new'] . '</span>';
				$menuData .= '<span class="badge badge-error pull-right gm-module-count-' . $gmGallery->options['modules_update'] . '" title="' . __( 'Modules Updates', 'grand-media' ) . '">' . $gmGallery->options['modules_update'] . '</span>';
			}

			$content['sideLinks'] .= "\n" . '<a class="list-group-item' . $iscur . '" href="' . admin_url( 'admin.php?page=' . $menuItem[2] ) . '">' . $menuItem[0] . $menuData . '</a>';
		}
		$content['sideLinks'] .= '
				</li></ul>
			</div>
		</div>';

		return $content;
	}

	function collect_data_permission() {
		$current_user = wp_get_current_user();
		$nonce        = wp_create_nonce( 'GmediaService' );
		?>
		<div class="notice updated gm-message gmedia-service__message">
			<div class="gm-message-content">
				<div class="gm-plugin-icon">
					<img src="<?php echo plugins_url( '/grand-media/admin/assets/img/icon-128x128.png' ) ?>" width="90" height="90">
				</div>
				<?php printf( __( '<p>Hey %s,<br>Please help us improve <b>Gmedia Gallery</b>! If you opt-in, some data about your usage of <b>Gmedia Gallery</b> will be sent to <a href="https://codeasily.com/" target="_blank" tabindex="1">codeasily.com</a>.
                    These data also required if you will use Gmedia iOS application on your iPhone.
                    If you skip this, that\'s okay! <b>Gmedia Gallery</b> will still work just fine.</p>', 'grand-media' ), $current_user->display_name ); ?>
			</div>
			<div class="gm-message-actions">
				<button class="button button-secondary gm_service_action" data-action="skip" data-nonce="<?php echo $nonce; ?>"><?php _e( 'Skip', 'grand-media' ); ?></button>
				<button class="button button-primary gm_service_action" data-action="allow" data-nonce="<?php echo $nonce; ?>"><?php _e( 'Allow &amp; Continue', 'grand-media' ); ?></button>
			</div>
			<div class="gm-message-plus gm-closed">
				<a class="gm-mp-trigger" href="#" onclick="jQuery('.gm-message-plus').toggleClass('gm-closed gm-opened'); return false;"><?php _e( 'What permissions are being granted?', 'grand-media' ); ?></a>
				<ul>
					<li>
						<i class="dashicons dashicons-admin-users"></i>

						<div>
							<span><?php _e( 'Your Profile Overview', 'grand-media' ); ?></span>

							<p><?php _e( 'Name and email address', 'grand-media' ); ?></p>
						</div>
					</li>
					<li>
						<i class="dashicons dashicons-admin-settings"></i>

						<div>
							<span><?php _e( 'Your Site Overview', 'grand-media' ); ?></span>

							<p><?php _e( 'Site URL, WP version, PHP version, active theme &amp; plugins', 'grand-media' ); ?></p>
						</div>
					</li>
				</ul>
			</div>
		</div>
		<?php
	}

	function admin_footer() {
		$ajax_operations = get_option( 'gmedia_ajax_long_operations' );
		if ( empty( $ajax_operations ) || ! is_array( $ajax_operations ) ) {
			return;
		}
		reset( $ajax_operations );
		$ajax = key( $ajax_operations );
		if ( empty( $ajax ) ) {
			delete_option( 'gmedia_ajax_long_operations' );

			return;
		}
		$nonce = wp_create_nonce( 'gmedia_ajax_long_operations' );
		?>
		<script type="text/javascript">
          jQuery(document).ready(function($) {
            var header = $('#gmedia-header');
            header.append('<div id="ajax-long-operation"><div class="progress"><div class="progress-bar progress-bar-info" style="width: 0%;"></div><div class="progress-bar-indicator">0%</div></div></div>');
            gmAjaxLongOperation = function() {
              jQuery.post(ajaxurl, {action: '<?php echo $ajax; ?>', _wpnonce_ajax_long_operations: '<?php echo $nonce; ?>'}, function(r) {
                if(r.data) {
                  jQuery('.progress-bar-info', header).width(r.data.progress);
                  var indicator = r.data.info ? r.data.info + ' ' + r.data.progress : r.data.progress;
                  jQuery('.progress-bar-indicator', header).html(indicator);

                  if(r.data.done) {
                    return;
                  }
                }
                gmAjaxLongOperation();
              });
            };
            gmAjaxLongOperation();
          });
		</script>
		<?php
	}

	function controller() {

		global $gmProcessor;
		switch ( $gmProcessor->page ) {
			case 'GrandMedia_AddMedia':
				include_once( dirname( __FILE__ ) . '/pages/addmedia/addmedia.php' );
				break;
			case 'GrandMedia_Albums':
				if ( isset( $_GET['edit_term'] ) ) {
					include_once( dirname( __FILE__ ) . '/pages/terms/edit-term.php' );
				} else {
					include_once( dirname( __FILE__ ) . '/pages/terms/terms.php' );
				}
				break;
			case 'GrandMedia_Categories':
				if ( isset( $_GET['edit_term'] ) ) {
					include_once( dirname( __FILE__ ) . '/pages/terms/edit-term.php' );
				} else {
					include_once( dirname( __FILE__ ) . '/pages/terms/terms.php' );
				}
				break;
			case 'GrandMedia_Tags':
				include_once( dirname( __FILE__ ) . '/pages/terms/terms.php' );
				break;
			case 'GrandMedia_Galleries':
				if ( isset( $_GET['gallery_module'] ) || isset( $_GET['edit_term'] ) ) {
					include_once( dirname( __FILE__ ) . '/pages/galleries/edit-gallery.php' );
				} else {
					include_once( dirname( __FILE__ ) . '/pages/galleries/galleries.php' );
				}
				break;
			case 'GrandMedia_Modules':
				if ( isset( $_GET['preset_module'] ) || isset( $_GET['preset'] ) ) {
					include_once( dirname( __FILE__ ) . '/pages/modules/edit-preset.php' );
				} else {
					include_once( dirname( __FILE__ ) . '/pages/modules/modules.php' );
				}
				break;
			case 'GrandMedia_Settings':
				include_once( dirname( __FILE__ ) . '/pages/settings/settings.php' );
				break;
			case 'GrandMedia_App':
				include_once( dirname( __FILE__ ) . '/app.php' );
				gmediaApp();
				break;
			case 'GrandMedia_WordpressLibrary':
				include_once( dirname( __FILE__ ) . '/wpmedia.php' );
				grandWPMedia();
				break;
			case 'GrandMedia_Logs':
				include_once( dirname( __FILE__ ) . '/logs.php' );
				break;
			case 'GrandMedia_Support':
				include_once( dirname( __FILE__ ) . '/support.php' );
				gmediaSupport();
				break;
			case 'GrandMedia_Update':
				include_once( GMEDIA_ABSPATH . 'config/update.php' );
				gmedia_upgrade_progress_panel();
				break;
			case 'GrandMedia':
				include_once( dirname( __FILE__ ) . '/pages/library/library.php' );
				break;
			default:
				do_action( 'gmedia_admin_page-' . $gmProcessor->page );
				break;
		}
	}

	/**
	 * @param $hook
	 */
	function load_scripts( $hook ) {
		global $gmCore, $gmProcessor, $gmGallery;
		// no need to go on if it's not a plugin page
		if ( 'admin.php' != $hook && strpos( $gmCore->_get( 'page' ), 'GrandMedia' ) === false ) {
			return;
		}

		if ( $gmGallery->options['isolation_mode'] ) {
			global $wp_scripts, $wp_styles;
			foreach ( $wp_scripts->registered as $handle => $wp_script ) {
				if ( ( ( false !== strpos( $wp_script->src, '/plugins/' ) ) || ( false !== strpos( $wp_script->src, '/themes/' ) ) ) && ( false === strpos( $wp_script->src, GMEDIA_FOLDER ) ) ) {
					if ( in_array( $handle, $wp_scripts->queue ) ) {
						wp_dequeue_script( $handle );
					}
					wp_deregister_script( $handle );
				}
			}
			foreach ( $wp_styles->registered as $handle => $wp_style ) {
				if ( ( ( false !== strpos( $wp_style->src, '/plugins/' ) ) || ( false !== strpos( $wp_style->src, '/themes/' ) ) ) && ( false === strpos( $wp_style->src, GMEDIA_FOLDER ) ) ) {
					if ( in_array( $handle, $wp_styles->queue ) ) {
						wp_dequeue_style( $handle );
					}
					wp_deregister_style( $handle );
				}
			}
		}

		wp_enqueue_style( 'gmedia-bootstrap' );
		wp_enqueue_script( 'gmedia-bootstrap' );

		wp_register_script( 'selectize', $gmCore->gmedia_url . '/assets/selectize/selectize.min.js', array( 'jquery' ), '0.12.1' );
		wp_register_style( 'selectize', $gmCore->gmedia_url . '/assets/selectize/selectize.bootstrap3.css', array( 'gmedia-bootstrap' ), '0.12.1', 'screen' );

		wp_register_style( 'spectrum', $gmCore->gmedia_url . '/assets/spectrum/spectrum.min.css', array(), '1.8.0' );
		wp_register_script( 'spectrum', $gmCore->gmedia_url . '/assets/spectrum/spectrum.min.js', array( 'jquery' ), '1.8.0', true );

		if ( isset( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) {
				case "GrandMedia" :
					if ( $gmCore->caps['gmedia_edit_media'] ) {
						if ( $gmCore->_get( 'gmediablank' ) == 'image_editor' ) {
							wp_enqueue_script( 'camanjs', $gmCore->gmedia_url . '/assets/image-editor/camanjs/caman.full.min.js', array(), '4.1.2' );

							wp_enqueue_style( 'nouislider', $gmCore->gmedia_url . '/assets/image-editor/js/jquery.nouislider.css', array( 'gmedia-bootstrap' ), '6.1.0' );
							wp_enqueue_script( 'nouislider', $gmCore->gmedia_url . '/assets/image-editor/js/jquery.nouislider.min.js', array( 'jquery' ), '6.1.0' );

							wp_enqueue_style( 'gmedia-image-editor', $gmCore->gmedia_url . '/assets/image-editor/style.css', array( 'gmedia-bootstrap' ), '0.9.16', 'screen' );
							wp_enqueue_script( 'gmedia-image-editor', $gmCore->gmedia_url . '/assets/image-editor/image-editor.js', array( 'jquery', 'camanjs' ), '0.9.16' );
							break;
						}
						if ( $gmProcessor->mode == 'edit' ) {
							wp_enqueue_script( 'alphanum', $gmCore->gmedia_url . '/assets/jq-plugins/jquery.alphanum.js', array( 'jquery' ), '1.0.16' );

							wp_enqueue_script( 'jquery-ui-sortable' );

							wp_enqueue_script( 'moment', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/moment.min.js', array( 'jquery' ), '2.22.2' );
							wp_enqueue_style( 'datetimepicker', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css', array( 'gmedia-bootstrap' ), '4.17.47' );
							wp_enqueue_script( 'datetimepicker', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js', array(
								'jquery',
								'moment',
								'gmedia-bootstrap',
							), '4.17.47' );
						}
					}
					wp_enqueue_script( 'wavesurfer', $gmCore->gmedia_url . '/assets/wavesurfer/wavesurfer.min.js', array( 'jquery' ), '1.1.5' );
					break;
				case "GrandMedia_WordpressLibrary" :
					break;
				case "GrandMedia_Albums" :
					if ( isset( $_GET['edit_term'] ) ) {
						if ( $gmCore->caps['gmedia_album_manage'] ) {
							wp_enqueue_style( 'jquery-ui-smoothness', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.min.css', array(), '1.10.2', 'screen' );
							wp_enqueue_script( 'jquery-ui-full', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js', array(), '1.10.2' );
						}

						wp_enqueue_script( 'moment', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/moment.min.js', array( 'jquery' ), '2.5.1' );
						wp_enqueue_style( 'datetimepicker', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css', array( 'gmedia-bootstrap' ), '2.1.32' );
						wp_enqueue_script( 'datetimepicker', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js', array(
							'jquery',
							'moment',
							'gmedia-bootstrap',
						), '2.1.32' );
					}
					break;
				case "GrandMedia_Categories" :
					break;
				case "GrandMedia_AddMedia" :
					if ( $gmCore->caps['gmedia_upload'] ) {
						$tab = $gmCore->_get( 'tab', 'upload' );
						if ( $tab == 'upload' ) {
							wp_enqueue_style( 'jquery-ui-smoothness', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.min.css', array(), '1.10.2', 'screen' );
							wp_enqueue_script( 'jquery-ui-full', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js', array(), '1.10.2' );

							wp_enqueue_script( 'gmedia-plupload', $gmCore->gmedia_url . '/assets/plupload/plupload.full.min.js', array( 'jquery', 'jquery-ui-full' ), '2.1.2' );

							wp_enqueue_style( 'jquery.ui.plupload', $gmCore->gmedia_url . '/assets/plupload/jquery.ui.plupload/css/jquery.ui.plupload.css', array( 'jquery-ui-smoothness' ), '2.1.2', 'screen' );
							wp_enqueue_script( 'jquery.ui.plupload', $gmCore->gmedia_url . '/assets/plupload/jquery.ui.plupload/jquery.ui.plupload.min.js', array(
								'gmedia-plupload',
								'jquery-ui-full',
							), '2.1.2' );

						}
					}
					break;
				case "GrandMedia_Settings" :
				case "GrandMedia_App" :
					// under construction
					break;
				case "GrandMedia_Galleries" :
					if ( $gmCore->caps['gmedia_gallery_manage'] && ( isset( $_GET['gallery_module'] ) || isset( $_GET['edit_term'] ) ) ) {

						wp_enqueue_style( 'jquery-ui-smoothness', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.min.css', array(), '1.10.2', 'screen' );
						wp_enqueue_script( 'jquery-ui-resizable' );

						wp_enqueue_script( 'jquery-ui-sortable' );

						wp_enqueue_style( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.css', array( 'gmedia-bootstrap' ), '0.9.13' );
						wp_enqueue_script( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.js', array( 'jquery' ), '0.9.13' );

						wp_enqueue_style( 'spectrum' );
						wp_enqueue_script( 'spectrum' );
					}
					break;
				case "GrandMedia_Modules" :
					if ( isset( $_GET['preset_module'] ) || isset( $_GET['preset'] ) ) {

						wp_enqueue_style( 'jquery-ui-smoothness', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.min.css', array(), '1.10.2', 'screen' );
						wp_enqueue_script( 'jquery-ui-resizable' );

						wp_enqueue_script( 'jquery-ui-sortable' );

						wp_enqueue_style( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.css', array( 'gmedia-bootstrap' ), '0.9.13' );
						wp_enqueue_script( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.js', array( 'jquery' ), '0.9.13' );

						wp_enqueue_style( 'spectrum' );
						wp_enqueue_script( 'spectrum' );
					}
					break;
			}
		}
		wp_enqueue_style( 'selectize' );
		wp_enqueue_script( 'selectize' );

		wp_enqueue_style( 'grand-media' );
		wp_enqueue_script( 'grand-media' );

	}

	function gmedia_widget_scripts() {

	}

	/**
	 * Enqueue the block's assets for the gutenberg editor.
	 */
	function gutenberg_assets() {
		global $gmGallery, $gmDB, $gmCore;

		wp_enqueue_style( 'gmedia-block-editor', $gmCore->gmedia_url . '/admin/assets/css/gmedia-block.css', array(), $gmGallery->version );
		wp_register_script(
			'gmedia-block-editor',
			$gmCore->gmedia_url . '/admin/assets/js/gmedia-block.js',
			array( 'wp-blocks', 'wp-element' ),
			$gmGallery->version
		);

		$default_module = $gmGallery->options['default_gmedia_module'];
		$default_preset = $gmCore->getModulePreset( $default_module );
		$default_module = $default_preset['module'];

		$modules_data    = get_gmedia_modules( false );
		$modules         = array();
		$modules_options = array();
		if ( ! empty( $modules_data['in'] ) ) {
			foreach ( $modules_data['in'] as $module_name => $module_data ) {

				$presets                = $gmDB->get_terms( 'gmedia_module', array( 'status' => $module_name ) );
				$option                 = array();
				$option[ $module_name ] = $module_data['title'] . ' - ' . __( 'Default Settings' );
				foreach ( $presets as $preset ) {
					if ( ! (int) $preset->global && '[' . $module_name . ']' === $preset->name ) {
						continue;
					}
					$by_author = '';
					if ( (int) $preset->global ) {
						$display_name = get_the_author_meta( 'display_name', $preset->global );
						$by_author    = $display_name ? ' [' . $display_name . ']' : '';
					}
					if ( '[' . $module_name . ']' === $preset->name ) {
						$option[ $preset->term_id ] = $module_data['title'] . $by_author . ' - ' . __( 'Default Settings' );
					} else {
						$preset_name                = str_replace( '[' . $module_name . '] ', '', $preset->name );
						$option[ $preset->term_id ] = $module_data['title'] . $by_author . ' - ' . $preset_name;
					}
				}
				$modules_options[ $module_name ] = array( 'title' => $module_data['title'], 'options' => $option );

				$modules[ $module_name ] = array(
					'name'       => $module_data['title'],
					'screenshot' => $module_data['module_url'] . '/screenshot.png',
				);
			}
		}

		$gm_galleries  = array();
		$gm_albums     = array();
		$gm_categories = array();
		$gm_tags       = array();

		$gm_terms = $gmDB->get_terms( 'gmedia_gallery' );
		if ( count( $gm_terms ) ) {
			foreach ( $gm_terms as $_term ) {
				unset( $_term->description );
				unset( $_term->taxonomy );
				$_term->module_name = $gmDB->get_metadata( 'gmedia_term', $_term->term_id, '_module', true );
				if ( $_term->global ) {
					$display_name = sprintf( __( 'by %s', 'grand-media' ), get_the_author_meta( 'display_name', $_term->global ) );
					$_term->name  .= 'by ' === $display_name ? '' : ' ' . $display_name;
				}
				if ( $_term->status && 'publish' !== $_term->status ) {
					$_term->name .= " [{$_term->status}]";
				}
				$gm_galleries[ $_term->term_id ] = $_term;
			}
		}
		$gm_galleries = array(
			                0 => array(
				                'term_id' => 0,
				                'name'    => __( ' - select gallery - ', 'grand-media' ),
			                ),
		                ) + $gm_galleries;

		$gm_terms = $gmDB->get_terms( 'gmedia_album' );
		if ( count( $gm_terms ) ) {
			foreach ( $gm_terms as $_term ) {
				unset( $_term->description );
				unset( $_term->taxonomy );
				$module_preset = $gmDB->get_metadata( 'gmedia_term', $_term->term_id, '_module_preset', true );
				if ( $module_preset ) {
					$preset             = $gmCore->getModulePreset( $module_preset );
					$_term->module_name = $preset['module'];
				} else {
					$_term->module_name = '';
				}
				if ( $_term->global ) {
					$display_name = sprintf( __( 'by %s', 'grand-media' ), get_the_author_meta( 'display_name', $_term->global ) );
					$_term->name  .= 'by ' === $display_name ? '' : ' ' . $display_name;
				}
				if ( $_term->status && 'publish' !== $_term->status ) {
					$_term->name .= " [{$_term->status}]";
				}
				$_term->name                  .= "   ({$_term->count})";
				$gm_albums[ $_term->term_id ] = $_term;
			}
		}
		$gm_albums = array(
			             0 => array(
				             'term_id' => 0,
				             'name'    => __( ' - select album - ', 'grand-media' ),
			             ),
		             ) + $gm_albums;

		$gm_terms = $gmDB->get_terms( 'gmedia_category' );
		if ( count( $gm_terms ) ) {
			foreach ( $gm_terms as $_term ) {
				unset( $_term->description );
				unset( $_term->taxonomy );
				unset( $_term->global );
				unset( $_term->status );
				$_term->name                      .= "   ({$_term->count})";
				$gm_categories[ $_term->term_id ] = $_term;
			}
		}
		$gm_categories = array(
			                 0 => array(
				                 'term_id' => 0,
				                 'name'    => __( ' - select category - ', 'grand-media' ),
			                 ),
		                 ) + $gm_categories;

		$gm_terms = $gmDB->get_terms( 'gmedia_tag' );
		if ( count( $gm_terms ) ) {
			foreach ( $gm_terms as $_term ) {
				unset( $_term->description );
				unset( $_term->taxonomy );
				unset( $_term->global );
				unset( $_term->status );
				$_term->name                .= "   ({$_term->count})";
				$gm_tags[ $_term->term_id ] = $_term;
			}
		}
		$gm_tags = array(
			           0 => array(
				           'term_id' => 0,
				           'name'    => __( ' - select tag - ', 'grand-media' ),
			           ),
		           ) + $gm_tags;

		$data = array(
			'modules'         => $modules,
			'default_module'  => $default_module,
			'modules_options' => $modules_options,
			'gmedia_image'    => $gmCore->gmedia_url . '/admin/assets/img/gmedia-icon-320x240.png',
			'galleries'       => $gm_galleries,
			'albums'          => $gm_albums,
			'categories'      => $gm_categories,
			'tags'            => $gm_tags,
		);

		wp_localize_script( 'gmedia-block-editor', 'gmedia_data', $data );
		wp_enqueue_script( 'gmedia-block-editor' );
	}

	function screen_help() {
		$screen    = get_current_screen();
		$screen_id = explode( 'page_', $screen->id, 2 );
		$screen_id = $screen_id[1];

		$screen->add_help_tab( array(
			'id'      => 'help_' . $screen_id . '_support',
			'title'   => __( 'Support' ),
			'content' => __( '<h4>First steps</h4>
<p>If you have any problems with displaying Gmedia Gallery in admin or on website. Before posting to the Forum try next:</p>
<ul>
	<li>Exclude plugin conflicts: Disable other plugins one by one and check if it resolve problem</li>
	<li>Exclude theme conflict: Temporary switch to one of default themes and check if gallery works</li>
</ul>
<h4>Links</h4>', 'grand-media' )
			             . '<p><a href="https://codeasily.com/community/forum/gmedia-gallery-wordpress-plugin/" target="_blank">' . __( 'Support Forum', 'grand-media' ) . '</a>
	| <a href="https://codeasily.com/contact/" target="_blank">' . __( 'Contact', 'grand-media' ) . '</a>
	| <a href="https://codeasily.com/portfolio/gmedia-gallery-modules/" target="_blank">' . __( 'Demo', 'grand-media' ) . '</a>
	| <a href="https://codeasily.com/product/one-site-license/" target="_blank">' . __( 'Premium', 'grand-media' ) . '</a>
</p>',
		) );

		switch ( $screen_id ) {
			case 'GrandMedia' :
				break;
			case 'GrandMedia_Settings' :
				if ( current_user_can( 'manage_options' ) ) {
					$screen->add_help_tab( array(
						'id'      => 'help_' . $screen_id . '_license',
						'title'   => __( 'License Key' ),
						'content' => sprintf( __( '<h4>Should I buy it, to use plugin?</h4>
<p>No, plugin is absolutely free and all modules for it are free to install.</p>
<p>Even premium modules are fully functional and free to test, but have backlink labels. To remove baclink labels from premium modules you need license key.</p>
<p>Note: License Key will remove backlinks from all current and future premium modules, so you can use all available modules on one website.</p>
<p>Do not purchase license key before testing module you like. Only if everything works fine and you satisfied with functionality you are good to purchase license. Otherwise use <a href="%1$s" target="_blank">Gmedia Support Forum</a>.</p>
<h4>I have license key but I can\'t activate it</h4>
<p>Contact developer <a href="mailto:%2$s">%2$s</a> with your problem and wait for additional instructions and code for manual activation</p>', 'grand-media' ), 'https://codeasily.com/community/forum/gmedia-gallery-wordpress-plugin/', 'gmediafolder@gmail.com' )
						             . '<div><a class="btn btn-default" href="' . admin_url( 'admin.php?page=' . $screen_id . '&license_activate=manual' ) . '">' . __( 'Manual Activation', 'grand-media' ) . '</a></div>',
					) );
				}
				break;
			case 'GrandMedia_App' :
				$gm_options = get_option( 'gmediaOptions' );
				$nonce      = wp_create_nonce( 'GmediaService' );
				if ( current_user_can( 'manage_options' ) && (int) $gm_options['mobile_app'] ) {
					$screen->add_help_tab( array(
						'id'      => 'help_' . $screen_id . '_optout',
						'title'   => __( 'Opt Out' ),
						'content' => __( '<h4>We appreciate your help in making the plugin better by letting us track some usage data.</h4>
<p>Usage tracking is done in the name of making <strong>Gmedia Gallery</strong> better. Making a better user experience, prioritizing new features, and more good things.</p>
<p>By clicking "Opt Out", we will no longer be sending any data from <strong>Gmedia Gallery</strong> to <a href="https://codeasily.com" target="_blank">codeasily.com</a>.</p>
<p>You\'ll also not be able to use Gmedia iOS application.</p>', 'grand-media' )
						             . '<p><button class="button button-default gm_service_action"  data-action="app_deactivate" data-nonce="' . $nonce . '">' . __( 'Opt Out', 'grand-media' ) . '</button><span class="spinner" style="float: none;"></span></p>'
						             . '<div style="display:none;">Test: 
<button type="button" data-action="app_updateinfo" data-nonce="' . $nonce . '" class="btn btn-sm btn-primary gm_service_action">Update</button>
<button type="button" data-action="app_updatecron" data-nonce="' . $nonce . '" class="btn btn-sm btn-primary gm_service_action">CronJob</button> &nbsp;&nbsp;
<button type="button" data-action="app_deactivateplugin" data-nonce="' . $nonce . '" class="btn btn-sm btn-primary gm_service_action">Deactivate Plugin</button>
<button type="button" data-action="app_uninstallplugin" data-nonce="' . $nonce . '" class="btn btn-sm btn-primary gm_service_action">Uninstall Plugin</button>
</div>
',
					) );
				}
				break;
		}
	}

	/**
	 * @param $current
	 * @param $screen
	 *
	 * @return string
	 */
	function screen_settings( $current, $screen ) {
		global $gmProcessor, $gmCore;
		if ( in_array( $screen->id, $this->pages ) ) {

			$gm_screen_options = $gmProcessor->user_options;

			$title             = '<h5><strong>' . __( 'Settings', 'grand-media' ) . '</strong></h5>';
			$wp_screen_options = '<input type="hidden" name="wp_screen_options[option]" value="gm_screen_options" /><input type="hidden" name="wp_screen_options[value]" value="' . $screen->id . '" />';
			$button            = get_submit_button( __( 'Apply', 'grand-media' ), 'button', 'screen-options-apply', false );

			$settings = false;

			$screen_id = explode( 'page_', $screen->id, 2 );

			switch ( $screen_id[1] ) {
				case 'GrandMedia' :
					$settings = '
					<div class="form-inline pull-left">
						<div class="form-group">
							<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
						</div>
						<div class="form-group">
							<select name="gm_screen_options[orderby_gmedia]" class="form-control input-sm">
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'ID', false ) . ' value="ID">' . __( 'ID', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'title', false ) . ' value="title">' . __( 'Title', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'gmuid', false ) . ' value="gmuid">' . __( 'Filename', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'mime_type', false ) . ' value="mime_type">' . __( 'MIME Type', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'author', false ) . ' value="author">' . __( 'Author', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'date', false ) . ' value="date">' . __( 'Date', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'modified', false ) . ' value="modified">' . __( 'Last Modified', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], '_created_timestamp', false ) . ' value="_created_timestamp">' . __( 'Created Timestamp', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'comment_count', false ) . ' value="comment_count">' . __( 'Comment Count', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'views', false ) . ' value="views">' . __( 'Views Count', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'likes', false ) . ' value="likes">' . __( 'Likes Count', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], '_size', false ) . ' value="_size">' . __( 'File Size', 'grand-media' ) . '</option>
							</select> <span>' . __( 'order items', 'grand-media' ) . '</span>
						</div>
						<div class="form-group">
							<select name="gm_screen_options[sortorder_gmedia]" class="form-control input-sm">
								<option' . selected( $gm_screen_options['sortorder_gmedia'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['sortorder_gmedia'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
							</select> <span>' . __( 'sort order', 'grand-media' ) . '</span>
						</div>
					';
					if ( 'edit' == $gmCore->_get( 'mode' ) ) {
						$settings .= '
						<div class="form-group">
							<select name="gm_screen_options[library_edit_quicktags]" class="form-control input-sm">
								<option' . selected( $gm_screen_options['library_edit_quicktags'], 'false', false ) . ' value="false">' . __( 'FALSE', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['library_edit_quicktags'], 'true', false ) . ' value="true">' . __( 'TRUE', 'grand-media' ) . '</option>
							</select> <span>' . __( 'Quick Tags panel for Description field', 'grand-media' ) . '</span>
						</div>
						';
					}
					$settings .= '
					</div>
					';
					break;
				case 'GrandMedia_AddMedia' :
					$tab = $gmCore->_get( 'tab', 'upload' );
					if ( 'upload' == $tab ) {
						$html4_hide = ( 'html4' == $gm_screen_options['uploader_runtime'] ) ? ' hide' : '';
						$settings   = '
						<div class="form-inline pull-left">
							<div id="uploader_runtime" class="form-group"><span>' . __( 'Uploader runtime:', 'grand-media' ) . ' </span>
								<select name="gm_screen_options[uploader_runtime]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['uploader_runtime'], 'auto', false ) . ' value="auto">' . __( 'Auto', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_runtime'], 'html5', false ) . ' value="html5">' . __( 'HTML5 Uploader', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_runtime'], 'flash', false ) . ' value="flash">' . __( 'Flash Uploader', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_runtime'], 'html4', false ) . ' value="html4">' . __( 'HTML4 Uploader', 'grand-media' ) . '</option>
								</select>
							</div>
							<div id="uploader_chunking" class="form-group' . $html4_hide . '"><span>' . __( 'Chunking:', 'grand-media' ) . ' </span>
								<select name="gm_screen_options[uploader_chunking]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['uploader_chunking'], 'true', false ) . ' value="true">' . __( 'TRUE', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_chunking'], 'false', false ) . ' value="false">' . __( 'FALSE', 'grand-media' ) . '</option>
								</select>
							</div>
							<div id="uploader_urlstream_upload" class="form-group' . $html4_hide . '"><span>' . __( 'URL streem upload:', 'grand-media' ) . ' </span>
								<select name="gm_screen_options[uploader_urlstream_upload]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['uploader_urlstream_upload'], 'true', false ) . ' value="true">' . __( 'TRUE', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_urlstream_upload'], 'false', false ) . ' value="false">' . __( 'FALSE', 'grand-media' ) . '</option>
								</select>
							</div>
						</div>
						';
					}
					break;
				case 'GrandMedia_Albums' :
					if ( isset( $_GET['edit_term'] ) ) {
						$settings = '
						<div class="form-inline pull-left">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_album_edit]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_album_edit'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
							</div>
						</div>
						';
					} else {
						$settings = '
                        <div class="form-inline pull-left">
                            <div class="form-group">
                                <input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_album]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_album'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
                            </div>
                            <div class="form-group">
                                <select name="gm_screen_options[orderby_gmedia_album]" class="form-control input-sm">
                                    <option' . selected( $gm_screen_options['orderby_gmedia_album'], 'id', false ) . ' value="id">' . __( 'ID', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_album'], 'name', false ) . ' value="name">' . __( 'Name', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_album'], 'count', false ) . ' value="count">' . __( 'Gmedia Count', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_album'], 'global', false ) . ' value="global">' . __( 'Author ID', 'grand-media' ) . '</option>
                                </select> <span>' . __( 'order items', 'grand-media' ) . '</span>
                            </div>
                            <div class="form-group">
                                <select name="gm_screen_options[sortorder_gmedia_album]" class="form-control input-sm">
                                    <option' . selected( $gm_screen_options['sortorder_gmedia_album'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['sortorder_gmedia_album'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
                                </select> <span>' . __( 'sort order', 'grand-media' ) . '</span>
                            </div>
                        </div>
                        ';
					}
					break;
				case 'GrandMedia_Categories' :
					if ( isset( $_GET['edit_term'] ) ) {
						$settings = '
						<div class="form-inline pull-left">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_category_edit]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_category_edit'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
							</div>
						</div>
						';
					} else {
						$settings = '
                        <div class="form-inline pull-left">
                            <div class="form-group">
                                <input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_category]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_category'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
                            </div>
                            <div class="form-group">
                                <select name="gm_screen_options[orderby_gmedia_category]" class="form-control input-sm">
                                    <option' . selected( $gm_screen_options['orderby_gmedia_category'], 'id', false ) . ' value="id">' . __( 'ID', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_category'], 'name', false ) . ' value="name">' . __( 'Name', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_category'], 'count', false ) . ' value="count">' . __( 'Gmedia Count', 'grand-media' ) . '</option>
                                </select> <span>' . __( 'order items', 'grand-media' ) . '</span>
                            </div>
                            <div class="form-group">
                                <select name="gm_screen_options[sortorder_gmedia_category]" class="form-control input-sm">
                                    <option' . selected( $gm_screen_options['sortorder_gmedia_category'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['sortorder_gmedia_category'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
                                </select> <span>' . __( 'sort order', 'grand-media' ) . '</span>
                            </div>
                        </div>
                        ';
					}
					break;
				case 'GrandMedia_Tags' :
					$settings = '
                    <div class="form-inline pull-left">
                        <div class="form-group">
                            <input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_tag]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_tag'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
                        </div>
                        <div class="form-group">
                            <select name="gm_screen_options[orderby_gmedia_tag]" class="form-control input-sm">
                                <option' . selected( $gm_screen_options['orderby_gmedia_tag'], 'id', false ) . ' value="id">' . __( 'ID', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['orderby_gmedia_tag'], 'name', false ) . ' value="name">' . __( 'Name', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['orderby_gmedia_tag'], 'count', false ) . ' value="count">' . __( 'Gmedia Count', 'grand-media' ) . '</option>
                            </select> <span>' . __( 'order items', 'grand-media' ) . '</span>
                        </div>
                        <div class="form-group">
                            <select name="gm_screen_options[sortorder_gmedia_tag]" class="form-control input-sm">
                                <option' . selected( $gm_screen_options['sortorder_gmedia_tag'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['sortorder_gmedia_tag'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
                            </select> <span>' . __( 'sort order', 'grand-media' ) . '</span>
                        </div>
                    </div>
                    ';
					break;
				case 'GrandMedia_Galleries' :
					if ( ! $gmCore->_get( 'edit_term' ) && ! $gmCore->_get( 'gallery_module' ) ) {
						$settings = '
						<div class="form-inline pull-left">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_gallery]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_gallery'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
							</div>
							<div class="form-group">
								<select name="gm_screen_options[orderby_gmedia_gallery]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['orderby_gmedia_gallery'], 'id', false ) . ' value="id">' . __( 'ID', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_gallery'], 'name', false ) . ' value="name">' . __( 'Name', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_gallery'], 'global', false ) . ' value="global">' . __( 'Author ID', 'grand-media' ) . '</option>
								</select> <span>' . __( 'order items', 'grand-media' ) . '</span>
							</div>
							<div class="form-group">
								<select name="gm_screen_options[sortorder_gmedia_gallery]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['sortorder_gmedia_gallery'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['sortorder_gmedia_gallery'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
								</select> <span>' . __( 'sort order', 'grand-media' ) . '</span>
							</div>
						</div>
						';
					}
					break;
				case 'GrandMedia_WordpressLibrary' :
					$settings = '<p>' . __( 'Set query options for this page to be loaded by default.', 'grand-media' ) . '</p>
					<div class="form-inline pull-left">
						<div class="form-group">
							<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_wpmedia]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_wpmedia'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
						</div>
						<div class="form-group">
							<select name="gm_screen_options[orderby_wpmedia]" class="form-control input-sm">
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'ID', false ) . ' value="ID">' . __( 'ID', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'title', false ) . ' value="title">' . __( 'Title', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'filename', false ) . ' value="filename">' . __( 'Filename', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'date', false ) . ' value="date">' . __( 'Date', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'modified', false ) . ' value="modified">' . __( 'Last Modified', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'mime_type', false ) . ' value="mime_type">' . __( 'MIME Type', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'author', false ) . ' value="author">' . __( 'Author', 'grand-media' ) . '</option>
							</select> <span>' . __( 'order items', 'grand-media' ) . '</span>
						</div>
						<div class="form-group">
							<select name="gm_screen_options[sortorder_wpmedia]" class="form-control input-sm">
								<option' . selected( $gm_screen_options['sortorder_wpmedia'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['sortorder_wpmedia'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
							</select> <span>' . __( 'sort order', 'grand-media' ) . '</span>
						</div>
					</div>
					';
					break;
				case 'GrandMedia_Logs' :
					$settings = '
                    <div class="form-inline pull-left">
                        <div class="form-group">
                            <input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_log]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_log'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
                        </div>
                        <div class="form-group">
                            <select name="gm_screen_options[orderby_gmedia_log]" class="form-control input-sm">
                                <option' . selected( $gm_screen_options['orderby_gmedia_log'], 'log_date', false ) . ' value="log_date">' . __( 'Date', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['orderby_gmedia_log'], 'ID', false ) . ' value="ID">' . __( 'Gmedia ID', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['orderby_gmedia_log'], 'author', false ) . ' value="author">' . __( 'Author ID', 'grand-media' ) . '</option>
                            </select> <span>' . __( 'order items', 'grand-media' ) . '</span>
                        </div>
                        <div class="form-group">
                            <select name="gm_screen_options[sortorder_gmedia_log]" class="form-control input-sm">
                                <option' . selected( $gm_screen_options['sortorder_gmedia_log'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['sortorder_gmedia_log'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
                            </select> <span>' . __( 'sort order', 'grand-media' ) . '</span>
                        </div>
                    </div>
                    ';
					break;
			}

			if ( $settings ) {
				$current = $title . $settings . $wp_screen_options . $button;
			}

		}

		return $current;
	}

	/**
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return array
	 */
	function screen_settings_save( $status, $option, $value ) {
		global $user_ID;
		if ( 'gm_screen_options' == $option ) {
			/*
			global $gmGallery;
			foreach ( $_POST['gm_screen_options'] as $key => $val ) {
				$gmGallery->options['gm_screen_options'][$key] = $val;
			}
			update_option( 'gmediaOptions', $gmGallery->options );
			*/
			$gm_screen_options = get_user_meta( $user_ID, 'gm_screen_options', true );
			if ( ! is_array( $gm_screen_options ) ) {
				$gm_screen_options = array();
			}
			$value = array_merge( $gm_screen_options, $_POST['gm_screen_options'] );

			return $value;
		}

		return $status;
	}

}

global $gmAdmin;
// Start GmediaAdmin
$gmAdmin = new GmediaAdmin();
