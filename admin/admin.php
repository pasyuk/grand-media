<?php

/**
 * GmediaAdmin - Admin Section for GRAND Media
 */
class GmediaAdmin {
	public $pages = [];
	public $body_classes = [];

	/**
	 * constructor
	 */
	public function __construct() {
		global $pagenow, $gmCore;

		add_action( 'admin_head', [ &$this, 'admin_head' ] );

		// Add the admin menu.
		add_action( 'admin_menu', [ &$this, 'add_menu' ] );

		// Add the script and style files.
		add_action( 'admin_enqueue_scripts', [ &$this, 'load_scripts' ], 20 );
		add_action( 'admin_print_scripts-widgets.php', [ &$this, 'gmedia_widget_scripts' ] );

		add_action( 'enqueue_block_editor_assets', [ &$this, 'gutenberg_assets' ] );

		add_filter( 'screen_settings', [ &$this, 'screen_settings' ], 10, 2 );
		add_filter( 'set-screen-option', [ &$this, 'screen_settings_save' ], 11, 3 );
		add_filter( 'set_screen_option_gm_screen_options', [ &$this, 'screen_settings_save' ], 11, 3 );

		$page = $gmCore->_get( 'page' );
		if ( $page && ( false !== strpos( $page, 'GrandMedia' ) ) ) {
			$this->body_classes[] = 'grand-media-admin-page';

			if ( ! isset( $_GET['gmediablank'] ) || 'library' === $gmCore->_get( 'gmediablank' ) ) {
				$this->body_classes[] = $page;
				$mode                 = $gmCore->_get( 'mode' );
				if ( $mode ) {
					$this->body_classes[] = $page . '_' . $mode;
				}
				if ( isset( $_GET['edit_term'] ) || isset( $_GET['gallery_module'] ) || isset( $_GET['preset'] ) ) {
					$this->body_classes[] = $page . '_edit';
				}
			}

			if ( ( 'admin.php' === $pagenow ) && isset( $_GET['gmediablank'] ) ) {
				add_action( 'admin_init', [ &$this, 'gmedia_blank_page' ] );
			}

			add_action( 'admin_footer', [ &$this, 'admin_footer' ] );
		}

	}

	/**
	 * admin_head
	 */
	public function admin_head() {
		global $gmCore;

		add_filter( 'admin_body_class', [ &$this, 'admin_body_class' ] );

		$page = $gmCore->_get( 'page' );
		if ( $page && ( false !== strpos( $page, 'GrandMedia' ) ) ) {
			?>
			<style id="gmedia_admin_css">html, body {
                    background: <?php echo isset( $_GET['gmediablank'] ) ? 'transparent' : '#708090'; ?>;
                }</style>
			<?php
		}
	}

	/**
	 * admin_body_class
	 *
	 * @param string $classes_string
	 *
	 * @return string
	 */
	public function admin_body_class( $classes_string ) {
		global $gmCore;

		$classes = $this->body_classes;

		$classes[] = $classes_string;
		if ( isset( $_GET['gmediablank'] ) ) {
			$classes[] = 'gmedia-blank gmedia_' . $gmCore->_get( 'gmediablank', '' );
		}
		$classes = array_filter( $classes );

		return implode( ' ', $classes );
	}

	/**
	 * Load gmedia pages in wpless interface
	 */
	public function gmedia_blank_page() {
		set_current_screen( 'GrandMedia_Settings' );

		global $gmCore, $gmProcessor, $gm_allowed_tags, $hook_suffix;
		$gmediablank = $gmCore->_get( 'gmediablank', '' );
		$hook_suffix = 'gmedia_' . $gmediablank;
		define( 'IFRAME_REQUEST', true );

		iframe_header( 'GmediaGallery' );

		echo '<div id="gmedia-container">';
		switch ( $gmediablank ) {
			case 'update_plugin':
				require_once dirname( dirname( __FILE__ ) ) . '/config/update.php';
				gmedia_do_update();
				break;
			case 'image_editor':
				require_once dirname( dirname( __FILE__ ) ) . '/inc/image-editor.php';
				gmedia_image_editor();
				break;
			case 'map_editor':
				require_once dirname( dirname( __FILE__ ) ) . '/inc/map-editor.php';
				gmedia_map_editor();
				break;
			case 'library':
				echo '<div id="gmedia_iframe_content">';
				echo '<div id="gm-message">' . wp_kses( $gmCore->alert( 'success', $gmProcessor->msg ) . $gmCore->alert( 'danger', $gmProcessor->error ), $gm_allowed_tags ) . '</div>';
				include GMEDIA_ABSPATH . 'admin/pages/library/library.php';
				echo '</div>';
				break;
			case 'comments':
				require_once dirname( __FILE__ ) . '/tpl/comments.php';
				break;
			case 'module_preview':
				require_once dirname( __FILE__ ) . '/tpl/module-preview.php';
				break;
		}
		echo '</div>';

		iframe_footer();
		exit;
	}

	/** Integrate the menu. */
	public function add_menu() {

		$count = '';
		if ( current_user_can( 'gmedia_module_manage' ) ) {
			global $gmGallery;
			if ( $gmGallery->options['modules_update'] ) {
				$count .= ' <span class="update-plugins count-' . intval( $gmGallery->options['modules_update'] ) . '" style="background-color: #bb391b;"><span class="plugin-count gm-module-count gm-modules-update-count" title="' . esc_html__( 'Modules Updates', 'grand-media' ) . '">' . intval( $gmGallery->options['modules_update'] ) . '</span></span>';
			}
			if ( $gmGallery->options['modules_new'] && ! empty( $gmGallery->options['notify_new_modules'] ) ) {
				$count .= ' <span class="update-plugins count-' . intval( $gmGallery->options['modules_new'] ) . '" style="background-color: #367236;"><span class="plugin-count gm-module-count gm-modules-new-count" title="' . esc_html__( 'New Modules', 'grand-media' ) . '">' . intval( $gmGallery->options['modules_new'] ) . '</span></span>';
			}
		}

		$this->pages   = [];
		$this->pages[] = add_menu_page(
			__( 'Gmedia Library', 'grand-media' ),
			"Gmedia{$count}",
			'gmedia_library',
			'GrandMedia',
			[
				&$this,
				'shell',
			],
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+CjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSIyMHB4IiBoZWlnaHQ9IjIwcHgiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMjAgMjAiIHhtbDpzcGFjZT0icHJlc2VydmUiPiAgPGltYWdlIGlkPSJpbWFnZTAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgeD0iMCIgeT0iMCIKICAgIHhsaW5rOmhyZWY9ImRhdGE6aW1hZ2UvcG5nO2Jhc2U2NCxpVkJPUncwS0dnb0FBQUFOU1VoRVVnQUFBQlFBQUFBVUNBTUFBQUM2ViswL0FBQUFCR2RCVFVFQUFMR1BDL3hoQlFBQUFDQmpTRkpOCkFBQjZKZ0FBZ0lRQUFQb0FBQUNBNkFBQWRUQUFBT3BnQUFBNm1BQUFGM0NjdWxFOEFBQUJrbEJNVkVVeFpua3haM2d4WjNoQ2RJTnUKbEtBK2NZQnBrSjJRcmJhb3Y4YTV5OUdadEx5UnJyZG1qcHMzYTN4WmhKS0txYktPckxXcndjaW52c1dWc2JxM3l0Q1hzcnRWZ1k5UwpmNDZndWNGN25hZzdibjVFZFlWeGxxS01xclN3eGN1aHVzS2Z1TUMrejlSMm1xWTZibjZ1dzhyNy9QekYxTm0weU03dDh2UHo5dmVGCnBhL2Y1K3BiaHBSSWVJZCtvS3FOcTdTZHQ3OWhpcGN5YUhuSDF0clMzdUZIZDRiSzJOMzUrL3YzK2ZxOXp0UmFoWk5QZll5Qm9xeUMKbzYwOGIzOUdkb1poaTVoT2ZJdnE3L0dwdjhiLy8vLzIrUG45L2YzQjBkWkFjb0tZczd3emFIbSt6OVZxa1oxWGc1SFQzK0xZNHVaNgpuYWh3bGFGRGRJVFAzT0JLZVloTWU0bnc5UFhoNmV2eDlmYkwyZDFUZ0k1em1LTXphWHJUM3VLWXM3dlAyOS9WNE9PY3RyN2c2T3VVCnNMbE5mSXU0eTlEbzd2QkZkb1YzbTZibTdlODViWDNJMXR1RHBLN1EzT0JZaEpHUHJMWEMwdGVsdmNSSmVZamI1ZWpOMnQ1eWw2S1cKc3JyYjVPZUFvYXhqakpuZTUrbDJtcVhFMDlpSHByQnRrNTl5bDZOOG5xazRiSDNXNGVUVTMrUFIzZUdxd01jY1RNSnpBQUFBQW5SUwpUbE51MlhMaTRXRUFBQUFCWWt0SFJFVDV0SmpCQUFBQUNYQklXWE1BQUFzVEFBQUxFd0VBbXB3WUFBQUFCM1JKVFVVSDRBc0NDRGNJCmw0WXhCZ0FBQVIxSlJFRlVHTk5qWUdCa1FnT01ESmhpSUZFNGs1bUZGY2FFQzdLeGMzQnljZlB3SWd2eWNmRUxDQW9KYzRxSThvdEIKQmNVRkpDU2xtS1JsWklYbDVCVVVsVUNDeWlxcWF1b2FtbHJhT3N4TXVucjZCb1pBUVNOakUxTW1Kak56QzBzQkt5WW1hMTBiUHFDZwpyWWFkdllLRG81T3ppNnVidTRlOHA1QVhVTkJiMGNmWHo5OHVJRkNDS1VneE9NUk9DR2hScUdaWU9MTmRSR1JVZ0FFVGsxU0VYMVEwCkUwTk1ySGRjZklKVVlwSzdiRExRMHBUVXRIUW1ob3pNTEgzVGhPd2MzY3pjUExEelRMU1lHUElMbUR3S2k0cExtRXFUSWQ3Z0tHUmkKWUF1elk3SXJpeXV2cUlTSVNWVlZBeTJxQ2ErdERtU3BxMk1KckZkcXlNbjN5MjRFQ25weEp6UWxOUHZGeHBxMDVBWUh4N2Z5SW9VUwpNc0FleU5paUF3Q3FwalN3RnBqcGxnQUFBQ1YwUlZoMFpHRjBaVHBqY21WaGRHVUFNakF4TmkweE1TMHdNbFF3T0RvMU5Ub3dPQzB3Ck56b3dNSWl4dXBvQUFBQWxkRVZZZEdSaGRHVTZiVzlrYVdaNUFESXdNVFl0TVRFdE1ESlVNRGc2TlRVNk1EZ3RNRGM2TURENTdBSW0KQUFBQUFFbEZUa1N1UW1DQyIgLz4KPC9zdmc+Cg==',
			11
		);
		$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Library', 'grand-media' ), __( 'Gmedia Library', 'grand-media' ), 'gmedia_library', 'GrandMedia', [ &$this, 'shell' ] );
		if ( current_user_can( 'gmedia_library' ) ) {
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Add Media Files', 'grand-media' ), __( 'Add/Import Files', 'grand-media' ), 'gmedia_upload', 'GrandMedia_AddMedia', [ &$this, 'shell' ] );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Tags', 'grand-media' ), __( 'Tags', 'grand-media' ), 'gmedia_tag_manage', 'GrandMedia_Tags', [ &$this, 'shell' ] );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Categories', 'grand-media' ), __( 'Categories', 'grand-media' ), 'gmedia_category_manage', 'GrandMedia_Categories', [ &$this, 'shell' ] );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Albums', 'grand-media' ), __( 'Albums', 'grand-media' ), 'gmedia_album_manage', 'GrandMedia_Albums', [ &$this, 'shell' ] );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Galleries', 'grand-media' ), __( 'Galleries', 'grand-media' ), 'gmedia_gallery_manage', 'GrandMedia_Galleries', [ &$this, 'shell' ] );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Modules', 'grand-media' ), __( 'Modules', 'grand-media' ), 'gmedia_gallery_manage', 'GrandMedia_Modules', [ &$this, 'shell' ] );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Settings', 'grand-media' ), __( 'Settings', 'grand-media' ), 'manage_options', 'GrandMedia_Settings', [ &$this, 'shell' ] );
			//$this->pages[] = add_submenu_page( 'GrandMedia', __( 'iOS Application', 'grand-media' ), __( 'iOS Application', 'grand-media' ), 'gmedia_settings', 'GrandMedia_App', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'WordPress Media Library', 'grand-media' ), __( 'WP Media Library', 'grand-media' ), 'gmedia_import', 'GrandMedia_WordpressLibrary', [ &$this, 'shell' ] );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Logs', 'grand-media' ), __( 'Gmedia Logs', 'grand-media' ), 'manage_options', 'GrandMedia_Logs', [ &$this, 'shell' ] );
			//$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Support', 'grand-media' ), __( 'Support', 'grand-media' ), 'manage_options', 'GrandMedia_Support', array( &$this, 'shell' ) );
		}

		foreach ( $this->pages as $page ) {
			add_action( "load-$page", [ &$this, 'screen_help' ] );
		}
	}

	/**
	 * Load the script for the defined page and load only this code
	 * Display shell of plugin
	 */
	public function shell() {
		global $gmCore, $gmProcessor, $gmGallery, $gm_allowed_tags;

		$sideLinks = $this->sideLinks();

		// check for upgrade.
		if ( get_option( 'gmediaDbVersion' ) !== GMEDIA_DBVERSION ) {
			if ( get_transient( 'gmediaUpgrade' ) || ( 'gmedia' === $gmCore->_get( 'do_update' ) ) ) {
				$sideLinks['grandTitle'] = __( 'Updating GmediaGallery Plugin', 'grand-media' );
				$sideLinks['sideLinks']  = '';
				$gmProcessor->page       = 'GrandMedia_Update';
			} else {
				return;
			}
		}

		//global $wpdb;
		//$query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gmedia_term");
		//foreach($query as $item){
		//    $name = $gmCore->mb_convert_encoding_utf8($item->name);
		//    $wpdb->update($wpdb->prefix . 'gmedia_term', array('name' => $name), array('term_id' => $item->term_id));
		//}
		//echo '<pre>' . print_r($query, true) . '</pre>';

		?>
		<div id="gmedia-container" class="gmedia-admin">
			<?php
			if ( 'GrandMedia_App' !== $gmProcessor->page && ! isset( $gmGallery->options['gmedia_service'] ) && current_user_can( 'manage_options' ) ) {
				$this->collect_data_permission();
			}
			?>
			<div id="gmedia-header" class="clearfix">
				<div id="gmedia-logo">Gmedia
					<small> by CodEasily.com</small>
				</div>
				<h2><?php echo esc_html( $sideLinks['grandTitle'] ); ?></h2>
				<?php
				if ( ! is_plugin_active( 'woowgallery/woowgallery.php' ) && empty( $gmGallery->options['disable_ads'] ) ) {
					?>
					<div class="promote-woowbox"><a href="https://bit.ly/gm-woowgallery" target="_blank"><img src="<?php echo esc_url( plugins_url( 'assets/img/woowbox-promote.png', __FILE__ ) ); ?>" alt="Try WoowGallery plugin"/></a></div>
					<?php
				}
				?>
			</div>
			<div class="container-fluid">
				<div class="row row-fx180-fl">
					<div class="col-2 p-0 hidden-xs" id="sidebar" role="navigation">
						<?php echo wp_kses( $sideLinks['sideLinks'], $gm_allowed_tags ); ?>

						<div id="gm-donate" class='card p-0'>
							<div class='card-header'>
								<b><?php esc_html_e( 'SOS from Ukraine', 'grand-media' ); ?></b>
							</div>
							<div class="card-body">
								<div id='donate-button-container'>
									<div id='donate-button'></div>
									<script src='https://www.paypalobjects.com/donate/sdk/donate-sdk.js' charset='UTF-8'></script>
									<script>
                                      PayPal.Donation.Button({
                                        env: 'production',
                                        hosted_button_id: 'QC8SXC3HSSJ36',
                                        image: {
                                          src: 'https://pics.paypal.com/00/s/NWYwYzFhMjktZjY2NS00MTE5LThkNmMtYjBjZjA3OTNlZDNk/file.PNG',
                                          alt: 'Donate with PayPal button',
                                          title: 'PayPal - The safer, easier way to pay online!',
                                        },
                                      }).render('#donate-button');
									</script>
								</div>

								<style>
                                    #donate-button img {
                                        max-width: 100%;
                                        box-shadow: 2px 2px 2px;
                                        border-radius: 12px;
                                        overflow: hidden;
                                    }
								</style>
								<br/>
								<p><?php esc_html_e( 'If you are able to donate, no matter how much, and if you enjoy using the Gmedia Gallery plugin and would like to help me in my time of need, please consider making a donation.', 'grand-media' ); ?></p>
								<p id="special_thanks">&#x2764;&#xfe0f; <a href="<?php echo esc_url( admin_url( 'admin.php?page=GrandMedia_Settings#gmedia_thanks_tab' ) ); ?>" style="font-weight: bold;"><?php esc_html_e( 'Special Thanks', 'grand-media' ); ?></a> &#x2764;&#xfe0f;</p>
							</div>

							<script>
                              (function() {
                                let timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                                let donate_div = document.querySelector('#gm-donate');
                                if ('Europe/Kiev' === timezone) {
                                  donate_div.style.display = 'none';
                                }
                              })();
							</script>
						</div>

						<?php
						if ( (int) $gmGallery->options['feedback'] ) {
							$installDate = get_option( 'gmediaInstallDate' );
							if ( $installDate && ( strtotime( $installDate ) < strtotime( '2 weeks ago' ) ) ) {
								?>
								<div class="card p-0 d-none d-xl-block d-sm-none">
									<div class="card-header" data-bs-toggle="collapse" data-bs-target="#support_div_collapse" aria-expanded="true" aria-controls="support_div_collapse" style="cursor:pointer;">
										<b><?php esc_html_e( 'Any feedback?', 'grand-media' ); ?></b>
									</div>
									<div class="collapse<?php echo empty( $gmGallery->options['license_key'] ) ? ' in' : ''; ?>" id="support_div_collapse">
										<div class="card-body">
											<p><?php esc_html_e( 'You can help me spread the word about GmediaGallery among the users striving to get awesome galleries on their WordPress sites.', 'grand-media' ); ?></p>

											<p>
												<a class="btn btn-primary" href="https://wordpress.org/support/view/plugin-reviews/grand-media?filter=5" target="_blank"><?php esc_html_e( 'Rate Gmedia Gallery', 'grand-media' ); ?></a>
											</p>

											<p><?php esc_html_e( 'Your reviews and ideas helps me to create new awesome modules and to improve plugin.', 'grand-media' ); ?></p>
										</div>
									</div>
								</div>
								<?php
							}
						}
						if ( (int) $gmGallery->options['twitter'] ) {
							?>
							<div class="card p-0 d-none d-xl-block d-sm-none">
								<a class="twitter-timeline" data-height="600" href="https://twitter.com/CodEasily/timelines/648240437141086212?ref_src=twsrc%5Etfw">#GmediaGallery - Curated tweets by CodEasily</a>
								<script <?php echo 'async src="https://platform.twitter.com/widgets.js" charset="utf-8"'; ?>></script>
							</div>
							<?php
						}
						?>
					</div>
					<div class="col-10">
						<div id="gm-message">
							<?php
							echo wp_kses( $gmCore->alert( 'success', $gmProcessor->msg ), $gm_allowed_tags );
							echo wp_kses( $gmCore->alert( 'danger', $gmProcessor->error ), $gm_allowed_tags );
							?>
						</div>

						<?php $this->controller(); ?>

					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function sideLinks() {
		global $submenu, $gmProcessor, $gmGallery;
		$content['sideLinks'] = '
		<div id="gmedia-navbar">
			<div class="row">
				<ul>';
		if ( empty( $gmGallery->options['license_key'] ) ) {
			$content['sideLinks'] .= "\n" . '<li class="list-group mb-3"><a class="list-group-item list-group-item-premium" target="_blank" href="https://codeasily.com/product/one-site-license/">' . esc_html__( 'Get Gmedia Premium', 'grand-media' ) . '</a></li>';
		}
		$content['sideLinks'] .= "\n" . '<li class="list-group">';
		foreach ( $submenu['GrandMedia'] as $menuItem ) {
			if ( $menuItem[2] === $gmProcessor->page ) {
				$iscur                 = ' active';
				$content['grandTitle'] = $menuItem[3];
			} else {
				$iscur = '';
			}
			$menuData = '';
			if ( 'GrandMedia_Modules' === $menuItem[2] && gm_user_can( 'module_manage' ) ) {
				if ( ! empty( $gmGallery->options['notify_new_modules'] ) ) {
					$menuData .= '<span class="badge badge-success float-end gm-module-count-' . intval( $gmGallery->options['modules_new'] ) . '" title="' . esc_attr__( 'New Modules', 'grand-media' ) . '">' . intval( $gmGallery->options['modules_new'] ) . '</span>';
				}
				$menuData .= '<span class="badge badge-error float-end gm-module-count-' . intval( $gmGallery->options['modules_update'] ) . '" title="' . esc_attr__( 'Modules Updates', 'grand-media' ) . '">' . intval( $gmGallery->options['modules_update'] ) . '</span>';
			}

			$content['sideLinks'] .= "\n" . '<a class="list-group-item list-group-item-action' . esc_attr( $iscur ) . '" href="' . esc_url( admin_url( 'admin.php?page=' . $menuItem[2] ) ) . '">' . wp_kses_post( $menuItem[0] . $menuData ) . '</a>';
		}
		$content['sideLinks'] .= "\n" . '<a class="list-group-item list-group-item-action" target="_blank" href="https://wordpress.org/support/plugin/grand-media/">' . esc_html__( 'Support', 'grand-media' ) . '</a>';
		$content['sideLinks'] .= '
				</li></ul>
			</div>
		</div>';

		return $content;
	}

	public function collect_data_permission() {
		$current_user = wp_get_current_user();
		$nonce        = wp_create_nonce( 'GmediaService' );
		?>
		<div class="notice updated gm-message gmedia-service__message">
			<div class="gm-message-content">
				<div class="gm-plugin-icon">
					<img src="<?php echo esc_url( plugins_url( 'assets/img/icon-128x128.png', __FILE__ ) ); ?>" width="90" height="90" alt="">
				</div>
				<?php
				// translators: user name.
				echo wp_kses_post( sprintf( __( '<p>Hey %s,<br>Please help us improve <b>Gmedia Gallery</b>! If you opt-in, some data about your usage of <b>Gmedia Gallery</b> will be sent to <a href="https://codeasily.com/" target="_blank" tabindex="1">codeasily.com</a>. If you skip this, that\'s okay! <b>Gmedia Gallery</b> will still work just fine.</p>', 'grand-media' ), esc_html( $current_user->display_name ) ) );
				?>
			</div>
			<div class="gm-message-actions">
				<button class="button button-secondary gm_service_action" data-action="skip" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Skip', 'grand-media' ); ?></button>
				<button class="button button-primary gm_service_action" data-action="allow" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Allow &amp; Continue', 'grand-media' ); ?></button>
			</div>
			<div class="gm-message-plus gm-closed">
				<a class="gm-mp-trigger" href="#" onclick="jQuery('.gm-message-plus').toggleClass('gm-closed gm-opened'); return false;"><?php esc_html_e( 'What permissions are being granted?', 'grand-media' ); ?></a>
				<ul>
					<li>
						<i class="dashicons dashicons-admin-users"></i>

						<div>
							<span><?php esc_html_e( 'Your Profile Overview', 'grand-media' ); ?></span>

							<p><?php esc_html_e( 'Name and email address', 'grand-media' ); ?></p>
						</div>
					</li>
					<li>
						<i class="dashicons dashicons-admin-settings"></i>

						<div>
							<span><?php esc_html_e( 'Your Site Overview', 'grand-media' ); ?></span>

							<p><?php esc_html_e( 'Site URL, WP version, PHP version, active theme &amp; plugins', 'grand-media' ); ?></p>
						</div>
					</li>
				</ul>
			</div>
		</div>
		<?php
	}

	public function controller() {

		global $gmProcessor;
		switch ( $gmProcessor->page ) {
			case 'GrandMedia_AddMedia':
				include_once dirname( __FILE__ ) . '/pages/addmedia/addmedia.php';
				break;
			case 'GrandMedia_Albums':
			case 'GrandMedia_Categories':
				if ( isset( $_GET['edit_term'] ) ) {
					include_once dirname( __FILE__ ) . '/pages/terms/edit-term.php';
				} else {
					include_once dirname( __FILE__ ) . '/pages/terms/terms.php';
				}
				break;
			case 'GrandMedia_Tags':
				include_once dirname( __FILE__ ) . '/pages/terms/terms.php';
				break;
			case 'GrandMedia_Galleries':
				if ( isset( $_GET['gallery_module'] ) || isset( $_GET['edit_term'] ) ) {
					include_once dirname( __FILE__ ) . '/pages/galleries/edit-gallery.php';
				} else {
					include_once dirname( __FILE__ ) . '/pages/galleries/galleries.php';
				}
				break;
			case 'GrandMedia_Modules':
				if ( isset( $_GET['preset_module'] ) || isset( $_GET['preset'] ) ) {
					include_once dirname( __FILE__ ) . '/pages/modules/edit-preset.php';
				} else {
					include_once dirname( __FILE__ ) . '/pages/modules/modules.php';
				}
				break;
			case 'GrandMedia_Settings':
				include_once dirname( __FILE__ ) . '/pages/settings/settings.php';
				break;
			case 'GrandMedia_App':
				include_once dirname( __FILE__ ) . '/app.php';
				gmediaApp();
				break;
			case 'GrandMedia_WordpressLibrary':
				include_once dirname( __FILE__ ) . '/wpmedia.php';
				grandWPMedia();
				break;
			case 'GrandMedia_Logs':
				include_once dirname( __FILE__ ) . '/logs.php';
				break;
			case 'GrandMedia_Support':
				include_once dirname( __FILE__ ) . '/support.php';
				gmediaSupport();
				break;
			case 'GrandMedia_Update':
				include_once GMEDIA_ABSPATH . 'config/update.php';
				gmedia_upgrade_progress_panel();
				break;
			case 'GrandMedia':
				include_once dirname( __FILE__ ) . '/pages/library/library.php';
				break;
			default:
				do_action( 'gmedia_admin_page__' . $gmProcessor->page );
				break;
		}
	}

	public function admin_footer() {
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
            var gmAjaxLongOperation = function() {
              jQuery.post(ajaxurl, {action: '<?php echo esc_js( $ajax ); ?>', _wpnonce_ajax_long_operations: '<?php echo esc_attr( $nonce ); ?>'}, function(r) {
                if (r.data) {
                  jQuery('.progress-bar-info', header).width(r.data.progress);
                  var indicator = r.data.info ? r.data.info + ' ' + r.data.progress : r.data.progress;
                  jQuery('.progress-bar-indicator', header).html(indicator);

                  if (r.data.done) {
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

	/**
	 * @param string $hook
	 */
	public function load_scripts( $hook ) {
		global $gmCore, $gmProcessor, $gmGallery;
		// no need to go on if it's not a plugin page.
		if ( 'admin.php' !== $hook && strpos( $gmCore->_get( 'page' ), 'GrandMedia' ) === false ) {
			return;
		}

		if ( $gmGallery->options['isolation_mode'] ) {
			global $wp_scripts, $wp_styles;
			foreach ( $wp_scripts->registered as $handle => $wp_script ) {
				if ( ( ( false !== strpos( $wp_script->src, '/plugins/' ) ) || ( false !== strpos( $wp_script->src, '/themes/' ) ) ) && ( false === strpos( $wp_script->src, GMEDIA_FOLDER ) ) ) {
					if ( in_array( $handle, $wp_scripts->queue, true ) ) {
						wp_dequeue_script( $handle );
					}
					wp_deregister_script( $handle );
				}
			}
			foreach ( $wp_styles->registered as $handle => $wp_style ) {
				if ( ( ( false !== strpos( $wp_style->src, '/plugins/' ) ) || ( false !== strpos( $wp_style->src, '/themes/' ) ) ) && ( false === strpos( $wp_style->src, GMEDIA_FOLDER ) ) ) {
					if ( in_array( $handle, $wp_styles->queue, true ) ) {
						wp_dequeue_style( $handle );
					}
					wp_deregister_style( $handle );
				}
			}
		}

		wp_enqueue_style( 'gmedia-bootstrap' );
		wp_enqueue_script( 'gmedia-bootstrap' );

		wp_register_style( 'selectize', $gmCore->gmedia_url . '/assets/selectize/selectize.bootstrap5.css', [ 'gmedia-bootstrap' ], '0.13.5', 'screen' );
		wp_register_script( 'selectize', $gmCore->gmedia_url . '/assets/selectize/selectize.min.js', [ 'jquery' ], '0.13.5', true );

		wp_register_style( 'spectrum', $gmCore->gmedia_url . '/assets/spectrum/spectrum.min.css', [], '1.8.0' );
		wp_register_script( 'spectrum', $gmCore->gmedia_url . '/assets/spectrum/spectrum.min.js', [ 'jquery' ], '1.8.0', true );

		$page = $gmCore->_get( 'page' );
		if ( $page ) {
			switch ( $page ) {
				case 'GrandMedia':
					if ( $gmCore->caps['gmedia_edit_media'] ) {
						if ( $gmCore->_get( 'gmediablank' ) === 'image_editor' ) {
							wp_enqueue_script( 'camanjs', $gmCore->gmedia_url . '/assets/image-editor/camanjs/caman.full.min.js', [], '4.1.2', true );

							wp_enqueue_style( 'nouislider', $gmCore->gmedia_url . '/assets/image-editor/js/jquery.nouislider.css', [ 'gmedia-bootstrap' ], '6.1.0' );
							wp_enqueue_script( 'nouislider', $gmCore->gmedia_url . '/assets/image-editor/js/jquery.nouislider.min.js', [ 'jquery' ], '6.1.0', true );

							wp_enqueue_style( 'gmedia-image-editor', $gmCore->gmedia_url . '/assets/image-editor/style.css', [ 'gmedia-bootstrap' ], '0.9.16', 'screen' );
							wp_enqueue_script( 'gmedia-image-editor', $gmCore->gmedia_url . '/assets/image-editor/image-editor.js', [ 'jquery', 'camanjs' ], '0.9.16', true );
							break;
						}
						if ( 'edit' === $gmProcessor->mode ) {
							wp_enqueue_script( 'alphanum', $gmCore->gmedia_url . '/assets/jq-plugins/jquery.alphanum.js', [ 'jquery' ], '1.0.16', true );

							wp_enqueue_script( 'jquery-ui-sortable' );

							wp_enqueue_script( 'popper', $gmCore->gmedia_url . '/assets/popper/popper.min.js', [], '2.11.2', true );
							wp_enqueue_style( 'tempus-dominus', $gmCore->gmedia_url . '/assets/tempus-dominus/css/tempus-dominus.min.css', [ 'gmedia-bootstrap' ], '6.0.0' );
							wp_enqueue_script( 'tempus-dominus', $gmCore->gmedia_url . '/assets/tempus-dominus/js/tempus-dominus.js', [ 'popper', 'gmedia-bootstrap' ], '6.0.0', true );
							wp_enqueue_script( 'moment' );
						}
					}
					wp_enqueue_script( 'wavesurfer', $gmCore->gmedia_url . '/assets/wavesurfer/wavesurfer.min.js', [ 'jquery' ], '1.1.5', true );
					break;
				case 'GrandMedia_WordpressLibrary':
					break;
				case 'GrandMedia_Albums':
					if ( isset( $_GET['edit_term'] ) ) {
						if ( $gmCore->caps['gmedia_album_manage'] ) {
							wp_enqueue_script( 'jquery-ui-sortable' );
						}

						wp_enqueue_script( 'popper', $gmCore->gmedia_url . '/assets/popper/popper.min.js', [], '2.11.2', true );
						wp_enqueue_style( 'tempus-dominus', $gmCore->gmedia_url . '/assets/tempus-dominus/css/tempus-dominus.min.css', [ 'gmedia-bootstrap' ], '6.0.0' );
						wp_enqueue_script( 'tempus-dominus', $gmCore->gmedia_url . '/assets/tempus-dominus/js/tempus-dominus.min.js', [ 'popper', 'gmedia-bootstrap' ], '6.0.0', true );
						wp_enqueue_script( 'tempus-dominus-jq', $gmCore->gmedia_url . '/assets/tempus-dominus/js/jQuery-provider.min.js', [ 'tempus-dominus', 'jquery' ], '6.0.0', true );
						wp_enqueue_script( 'moment' );
					}
					break;
				case 'GrandMedia_Categories':
					break;
				case 'GrandMedia_AddMedia':
					if ( $gmCore->caps['gmedia_upload'] ) {
						$tab = $gmCore->_get( 'tab', 'upload' );
						if ( 'upload' === $tab ) {
							wp_enqueue_style( 'jquery.plupload.queue', $gmCore->gmedia_url . '/assets/jquery.plupload.queue/css/jquery.plupload.queue.css', [], '2.3.9', 'screen' );
							wp_enqueue_script( 'jquery.plupload.queue', $gmCore->gmedia_url . '/assets/jquery.plupload.queue/jquery.plupload.queue.js', [ 'plupload' ], '2.3.9', true );
						}
					}
					if ( ! empty( $_GET['import'] ) ) {
						wp_enqueue_style( 'jqueryFileTree', $gmCore->gmedia_url . '/assets/jqueryFileTree/jqueryFileTree.css', [], '1.0.1', 'screen' );
						wp_enqueue_script(
							'jqueryFileTree',
							$gmCore->gmedia_url . '/assets/jqueryFileTree/jqueryFileTree.js',
							[
								'jquery',
							],
							'1.0.1',
							true
						);
					}
					break;
				case 'GrandMedia_Settings':
				case 'GrandMedia_App':
					// under construction.
					break;
				case 'GrandMedia_Galleries':
					if ( $gmCore->caps['gmedia_gallery_manage'] && ( isset( $_GET['gallery_module'] ) || isset( $_GET['edit_term'] ) ) ) {

						wp_enqueue_script( 'jquery-ui-sortable' );

						wp_enqueue_style( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.css', [ 'gmedia-bootstrap' ], '0.9.13' );
						wp_enqueue_script( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.js', [ 'jquery' ], '0.9.13', true );

						wp_enqueue_style( 'spectrum' );
						wp_enqueue_script( 'spectrum' );
					}
					break;
				case 'GrandMedia_Modules':
					if ( isset( $_GET['preset_module'] ) || isset( $_GET['preset'] ) ) {

						wp_enqueue_script( 'jquery-ui-sortable' );

						wp_enqueue_style( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.css', [ 'gmedia-bootstrap' ], '0.9.13' );
						wp_enqueue_script( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.js', [ 'jquery' ], '0.9.13', true );

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

	public function gmedia_widget_scripts() {
	}

	/**
	 * Enqueue the block's assets for the gutenberg editor.
	 */
	public function gutenberg_assets() {
		global $gmGallery, $gmDB, $gmCore;

		wp_enqueue_style( 'gmedia-block-editor', $gmCore->gmedia_url . '/admin/assets/css/gmedia-block.css', [], $gmGallery->version );
		wp_register_script(
			'gmedia-block-editor',
			$gmCore->gmedia_url . '/admin/assets/js/gmedia-block.js',
			[ 'wp-blocks', 'wp-element' ],
			$gmGallery->version,
			true
		);

		$default_module = $gmGallery->options['default_gmedia_module'];
		$default_preset = $gmCore->getModulePreset( $default_module );
		$default_module = $default_preset['module'];

		$modules_data    = get_gmedia_modules( false );
		$modules         = [];
		$modules_options = [];
		if ( ! empty( $modules_data['in'] ) ) {
			foreach ( $modules_data['in'] as $module_name => $module_data ) {

				$presets                = $gmDB->get_terms( 'gmedia_module', [ 'status' => $module_name ] );
				$option                 = [];
				$option[ $module_name ] = esc_html( $module_data['title'] . ' - ' . __( 'Default Settings' ) );
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
						$option[ $preset->term_id ] = esc_html( $module_data['title'] . $by_author . ' - ' . __( 'Default Settings' ) );
					} else {
						$preset_name                = str_replace( '[' . $module_name . '] ', '', $preset->name );
						$option[ $preset->term_id ] = esc_html( $module_data['title'] . $by_author . ' - ' . $preset_name );
					}
				}
				$modules_options[ $module_name ] = [ 'title' => esc_html( $module_data['title'] ), 'options' => $option ];

				$modules[ $module_name ] = [
					'name'       => esc_html( $module_data['title'] ),
					'screenshot' => esc_url( $module_data['module_url'] . '/screenshot.png' ),
				];
			}
		}

		$gm_galleries  = [];
		$gm_albums     = [];
		$gm_categories = [];
		$gm_tags       = [];

		$gm_terms = $gmDB->get_terms( 'gmedia_gallery' );
		if ( count( $gm_terms ) ) {
			foreach ( $gm_terms as $_term ) {
				unset( $_term->description );
				unset( $_term->taxonomy );
				$_term->module_name = esc_html( $gmDB->get_metadata( 'gmedia_term', $_term->term_id, '_module', true ) );
				if ( $_term->global ) {
					$display_name = get_the_author_meta( 'display_name', $_term->global );
					// translators: author name.
					$_term->name .= empty( $display_name ) ? '' : ' ' . sprintf( esc_html__( 'by %s', 'grand-media' ), esc_html( $display_name ) );
				}
				if ( $_term->status && 'publish' !== $_term->status ) {
					$_term->name .= esc_html( " [{$_term->status}]" );
				}
				$gm_galleries[ $_term->term_id ] = $_term;
			}
		}
		$gm_galleries = [ 0 => [ 'term_id' => 0, 'name' => esc_html__( ' - select gallery - ', 'grand-media' ) ] ] + $gm_galleries;

		$gm_terms = $gmDB->get_terms( 'gmedia_album' );
		if ( count( $gm_terms ) ) {
			foreach ( $gm_terms as $_term ) {
				unset( $_term->description );
				unset( $_term->taxonomy );
				$module_preset = esc_html( $gmDB->get_metadata( 'gmedia_term', $_term->term_id, '_module_preset', true ) );
				if ( $module_preset ) {
					$preset             = $gmCore->getModulePreset( $module_preset );
					$_term->module_name = esc_html( $preset['module'] );
				} else {
					$_term->module_name = '';
				}
				if ( $_term->global ) {
					$display_name = get_the_author_meta( 'display_name', $_term->global );
					// translators: author name.
					$_term->name .= empty( $display_name ) ? '' : ' ' . sprintf( esc_html__( 'by %s', 'grand-media' ), esc_html( $display_name ) );
				}
				if ( $_term->status && 'publish' !== $_term->status ) {
					$_term->name .= esc_html( " [{$_term->status}]" );
				}
				$_term->name .= esc_html( "   ({$_term->count})" );

				$gm_albums[ $_term->term_id ] = $_term;
			}
		}
		$gm_albums = [ 0 => [ 'term_id' => 0, 'name' => esc_html__( ' - select album - ', 'grand-media' ) ] ] + $gm_albums;

		$gm_terms = $gmDB->get_terms( 'gmedia_category' );
		if ( count( $gm_terms ) ) {
			foreach ( $gm_terms as $_term ) {
				unset( $_term->description );
				unset( $_term->taxonomy );
				unset( $_term->global );
				unset( $_term->status );
				$_term->name .= esc_html( "   ({$_term->count})" );

				$gm_categories[ $_term->term_id ] = $_term;
			}
		}
		$gm_categories = [ 0 => [ 'term_id' => 0, 'name' => esc_html__( ' - select category - ', 'grand-media' ) ] ] + $gm_categories;

		$gm_terms = $gmDB->get_terms( 'gmedia_tag' );
		if ( count( $gm_terms ) ) {
			foreach ( $gm_terms as $_term ) {
				unset( $_term->description );
				unset( $_term->taxonomy );
				unset( $_term->global );
				unset( $_term->status );
				$_term->name .= esc_html( "   ({$_term->count})" );

				$gm_tags[ $_term->term_id ] = $_term;
			}
		}
		$gm_tags = [ 0 => [ 'term_id' => 0, 'name' => esc_html__( ' - select tag - ', 'grand-media' ) ] ] + $gm_tags;

		$data = [
			'modules'         => $modules,
			'default_module'  => $default_module,
			'modules_options' => $modules_options,
			'gmedia_image'    => $gmCore->gmedia_url . '/admin/assets/img/gmedia-icon-320x240.png',
			'galleries'       => $gm_galleries,
			'albums'          => $gm_albums,
			'categories'      => $gm_categories,
			'tags'            => $gm_tags,
		];

		wp_localize_script( 'gmedia-block-editor', 'gmedia_data', $data );
		wp_enqueue_script( 'gmedia-block-editor' );
	}

	public function screen_help() {
		$screen    = get_current_screen();
		$screen_id = explode( 'page_', $screen->id, 2 );
		$screen_id = $screen_id[1];

		$screen->add_help_tab(
			[
				'id'      => 'help_' . $screen_id . '_support',
				'title'   => __( 'Support' ),
				'content' =>
					__(
						'<h4>First steps</h4>
<p>If you have any problems with displaying Gmedia Gallery in admin or on website. Before posting to the Forum try next:</p>
<ul>
	<li>Exclude plugin conflicts: Disable other plugins one by one and check if it resolves problem</li>
	<li>Exclude theme conflict: Temporary switch to one of default themes and check if gallery works</li>
</ul>
<h4>Links</h4>',
						'grand-media'
					)
					. '<p><a href="https://codeasily.com/community/forum/gmedia-gallery-wordpress-plugin/" target="_blank">' . esc_html__( 'Support Forum', 'grand-media' ) . '</a>
	| <a href="https://codeasily.com/contact/" target="_blank">' . esc_html__( 'Contact', 'grand-media' ) . '</a>
	| <a href="https://codeasily.com/portfolio/gmedia-gallery-modules/" target="_blank">' . esc_html__( 'Demo', 'grand-media' ) . '</a>
	| <a href="https://codeasily.com/product/one-site-license/" target="_blank">' . esc_html__( 'Premium', 'grand-media' ) . '</a>
</p>',
			]
		);

		switch ( $screen_id ) {
			case 'GrandMedia':
				break;
			case 'GrandMedia_Settings':
				if ( current_user_can( 'manage_options' ) ) {
					$screen->add_help_tab(
						[
							'id'      => 'help_' . $screen_id . '_license',
							'title'   => __( 'License Key' ),
							'content' =>
								sprintf(
									__(
										'<h4>Should I buy it, to use plugin?</h4>
<p>No, plugin is absolutely free and all modules for it are free to install.</p>
<p>Even premium modules are fully functional and free to test, but have backlink labels. To remove baclink labels from premium modules you need license key.</p>
<p>Note: License Key will remove backlinks from all current and future premium modules, so you can use all available modules on one website.</p>
<p>Do not purchase license key before testing module you like. Only if everything works fine, and you satisfied with functionality you are good to purchase license. Otherwise use <a href="%1$s" target="_blank">Gmedia Support Forum</a>.</p>
<h4>I have license key, but I can\'t activate it</h4>
<p>Contact developer <a href="mailto:%2$s">%2$s</a> with your problem and wait for additional instructions and code for manual activation</p>',
										'grand-media'
									),
									'https://wordpress.org/support/plugin/grand-media/',
									'gmediafolder@gmail.com'
								)
								. '<div><a class="btn btn-secondary" href="' . admin_url( 'admin.php?page=' . $screen_id . '&license_activate=manual' ) . '">' . __( 'Manual Activation', 'grand-media' ) . '</a></div>',
						]
					);
				}
				break;
			case 'GrandMedia_App':
				$gm_options = get_option( 'gmediaOptions' );
				$nonce      = wp_create_nonce( 'GmediaService' );
				if ( current_user_can( 'manage_options' ) && (int) $gm_options['mobile_app'] ) {
					$screen->add_help_tab(
						[
							'id'      => 'help_' . $screen_id . '_optout',
							'title'   => __( 'Opt Out', 'grand-media' ),
							'content' =>
								__(
									'<h4>We appreciate your help in making the plugin better by letting us track some usage data.</h4>
<p>Usage tracking is done in the name of making <strong>Gmedia Gallery</strong> better. Making a better user experience, prioritizing new features, and more good things.</p>
<p>By clicking "Opt Out", we will no longer be sending any data from <strong>Gmedia Gallery</strong> to <a href="https://codeasily.com" target="_blank">codeasily.com</a>.</p>',
									'grand-media'
								)
								. '<p><button class="button button-default gm_service_action"  data-action="app_deactivate" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Opt Out', 'grand-media' ) . '</button><span class="spinner" style="float: none;"></span></p>'
								. '<div style="display:none;">Test:
<button type="button" data-action="app_updateinfo" data-nonce="' . esc_attr( $nonce ) . '" class="btn btn-sm btn-primary gm_service_action">Update</button>
<button type="button" data-action="app_updatecron" data-nonce="' . esc_attr( $nonce ) . '" class="btn btn-sm btn-primary gm_service_action">CronJob</button> &nbsp;&nbsp;
<button type="button" data-action="app_deactivateplugin" data-nonce="' . esc_attr( $nonce ) . '" class="btn btn-sm btn-primary gm_service_action">Deactivate Plugin</button>
<button type="button" data-action="app_uninstallplugin" data-nonce="' . esc_attr( $nonce ) . '" class="btn btn-sm btn-primary gm_service_action">Uninstall Plugin</button>
</div>
',
						]
					);
				}
				break;
		}
	}

	/**
	 * @param string $current
	 * @param object $screen
	 *
	 * @return string
	 */
	public function screen_settings( $current, $screen ) {
		global $gmProcessor, $gmCore;
		if ( in_array( $screen->id, $this->pages, true ) ) {

			$gm_screen_options = $gmProcessor->user_options;

			$title             = '<h5><strong>' . esc_html__( 'Settings', 'grand-media' ) . '</strong></h5>';
			$wp_screen_options = '<input type="hidden" name="wp_screen_options[option]" value="gm_screen_options" /><input type="hidden" name="wp_screen_options[value]" value="' . esc_attr( $screen->id ) . '" />';
			$button            = get_submit_button( esc_html__( 'Apply', 'grand-media' ), 'button', 'screen-options-apply', false );

			$settings = false;

			$screen_id = explode( 'page_', $screen->id, 2 );

			switch ( $screen_id[1] ) {
				case 'GrandMedia':
					$settings = '
					<div class="form-inline float-start row row-cols-auto">
						<div class="form-group">
							<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_gmedia'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
						</div>
						<div class="form-group">
							<select name="gm_screen_options[orderby_gmedia]" class="form-control input-xs d-inline w-auto pe-4">
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'ID', false ) . ' value="ID">' . esc_html__( 'ID', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'title', false ) . ' value="title">' . esc_html__( 'Title', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'gmuid', false ) . ' value="gmuid">' . esc_html__( 'Filename', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'mime_type', false ) . ' value="mime_type">' . esc_html__( 'MIME Type', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'author', false ) . ' value="author">' . esc_html__( 'Author', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'date', false ) . ' value="date">' . esc_html__( 'Date', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'modified', false ) . ' value="modified">' . esc_html__( 'Last Modified', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], '_created_timestamp', false ) . ' value="_created_timestamp">' . esc_html__( 'Created Timestamp', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'comment_count', false ) . ' value="comment_count">' . esc_html__( 'Comment Count', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'views', false ) . ' value="views">' . esc_html__( 'Views Count', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'likes', false ) . ' value="likes">' . esc_html__( 'Likes Count', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], '_size', false ) . ' value="_size">' . esc_html__( 'File Size', 'grand-media' ) . '</option>
							</select> <span>' . esc_html__( 'order items', 'grand-media' ) . '</span>
						</div>
						<div class="form-group">
							<select name="gm_screen_options[sortorder_gmedia]" class="form-control input-xs d-inline w-auto pe-4">
								<option' . selected( $gm_screen_options['sortorder_gmedia'], 'DESC', false ) . ' value="DESC">' . esc_html__( 'DESC', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['sortorder_gmedia'], 'ASC', false ) . ' value="ASC">' . esc_html__( 'ASC', 'grand-media' ) . '</option>
							</select> <span>' . esc_html__( 'sort order', 'grand-media' ) . '</span>
						</div>
					';
					if ( 'edit' === $gmCore->_get( 'mode' ) ) {
						$settings .= '
						<div class="form-group">
							<select name="gm_screen_options[library_edit_quicktags]" class="form-control input-xs d-inline w-auto pe-4">
								<option' . selected( $gm_screen_options['library_edit_quicktags'], 'false', false ) . ' value="false">' . esc_html__( 'FALSE', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['library_edit_quicktags'], 'true', false ) . ' value="true">' . esc_html__( 'TRUE', 'grand-media' ) . '</option>
							</select> <span>' . esc_html__( 'Quick Tags panel for Description field', 'grand-media' ) . '</span>
						</div>
						';
					}
					$settings .= '
					</div>
					';
					break;
				case 'GrandMedia_AddMedia':
					$tab = $gmCore->_get( 'tab', 'upload' );
					if ( 'upload' === $tab ) {
						$html4_hide = ( 'html4' === $gm_screen_options['uploader_runtime'] ) ? ' hide' : '';
						$settings   = '
						<div class="form-inline float-start row row-cols-auto">
							<div id="uploader_runtime" class="form-group"><span>' . esc_html__( 'Uploader runtime:', 'grand-media' ) . ' </span>
								<select name="gm_screen_options[uploader_runtime]" class="form-control input-xs d-inline w-auto pe-4">
									<option' . selected( $gm_screen_options['uploader_runtime'], 'auto', false ) . ' value="auto">' . esc_html__( 'Auto', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_runtime'], 'html5', false ) . ' value="html5">' . esc_html__( 'HTML5 Uploader', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_runtime'], 'html4', false ) . ' value="html4">' . esc_html__( 'HTML4 Uploader', 'grand-media' ) . '</option>
								</select>
							</div>
							<div id="uploader_chunking" class="form-group' . esc_attr( $html4_hide ) . '"><span>' . esc_html__( 'Chunking:', 'grand-media' ) . ' </span>
								<select name="gm_screen_options[uploader_chunking]" class="form-control input-xs d-inline w-auto pe-4">
									<option' . selected( $gm_screen_options['uploader_chunking'], 'true', false ) . ' value="true">' . esc_html__( 'TRUE', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_chunking'], 'false', false ) . ' value="false">' . esc_html__( 'FALSE', 'grand-media' ) . '</option>
								</select>
							</div>
							<div id="uploader_urlstream_upload" class="form-group' . esc_attr( $html4_hide ) . '"><span>' . esc_html__( 'URL streem upload:', 'grand-media' ) . ' </span>
								<select name="gm_screen_options[uploader_urlstream_upload]" class="form-control input-xs d-inline w-auto pe-4">
									<option' . selected( $gm_screen_options['uploader_urlstream_upload'], 'true', false ) . ' value="true">' . esc_html__( 'TRUE', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['uploader_urlstream_upload'], 'false', false ) . ' value="false">' . esc_html__( 'FALSE', 'grand-media' ) . '</option>
								</select>
							</div>
						</div>
						';
					}
					break;
				case 'GrandMedia_Albums':
					if ( isset( $_GET['edit_term'] ) ) {
						$settings = '
						<div class="form-inline float-start row row-cols-auto">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_album_edit]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_gmedia_album_edit'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
							</div>
						</div>
						';
					} else {
						$settings = '
                        <div class="form-inline float-start row row-cols-auto">
                            <div class="form-group">
                                <input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_album]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_gmedia_album'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
                            </div>
                            <div class="form-group">
                                <select name="gm_screen_options[orderby_gmedia_album]" class="form-control input-xs d-inline w-auto pe-4">
                                    <option' . selected( $gm_screen_options['orderby_gmedia_album'], 'id', false ) . ' value="id">' . esc_html__( 'ID', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_album'], 'name', false ) . ' value="name">' . esc_html__( 'Name', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_album'], 'count', false ) . ' value="count">' . esc_html__( 'Gmedia Count', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_album'], 'global', false ) . ' value="global">' . esc_html__( 'Author ID', 'grand-media' ) . '</option>
                                </select> <span>' . esc_html__( 'order items', 'grand-media' ) . '</span>
                            </div>
                            <div class="form-group">
                                <select name="gm_screen_options[sortorder_gmedia_album]" class="form-control input-xs d-inline w-auto pe-4">
                                    <option' . selected( $gm_screen_options['sortorder_gmedia_album'], 'DESC', false ) . ' value="DESC">' . esc_html__( 'DESC', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['sortorder_gmedia_album'], 'ASC', false ) . ' value="ASC">' . esc_html__( 'ASC', 'grand-media' ) . '</option>
                                </select> <span>' . esc_html__( 'sort order', 'grand-media' ) . '</span>
                            </div>
                        </div>
                        ';
					}
					break;
				case 'GrandMedia_Categories':
					if ( isset( $_GET['edit_term'] ) ) {
						$settings = '
						<div class="form-inline float-start row row-cols-auto">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_category_edit]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_gmedia_category_edit'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
							</div>
						</div>
						';
					} else {
						$settings = '
                        <div class="form-inline float-start row row-cols-auto">
                            <div class="form-group">
                                <input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_category]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_gmedia_category'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
                            </div>
                            <div class="form-group">
                                <select name="gm_screen_options[orderby_gmedia_category]" class="form-control input-xs d-inline w-auto pe-4">
                                    <option' . selected( $gm_screen_options['orderby_gmedia_category'], 'id', false ) . ' value="id">' . esc_html__( 'ID', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_category'], 'name', false ) . ' value="name">' . esc_html__( 'Name', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['orderby_gmedia_category'], 'count', false ) . ' value="count">' . esc_html__( 'Gmedia Count', 'grand-media' ) . '</option>
                                </select> <span>' . esc_html__( 'order items', 'grand-media' ) . '</span>
                            </div>
                            <div class="form-group">
                                <select name="gm_screen_options[sortorder_gmedia_category]" class="form-control input-xs d-inline w-auto pe-4">
                                    <option' . selected( $gm_screen_options['sortorder_gmedia_category'], 'DESC', false ) . ' value="DESC">' . esc_html__( 'DESC', 'grand-media' ) . '</option>
                                    <option' . selected( $gm_screen_options['sortorder_gmedia_category'], 'ASC', false ) . ' value="ASC">' . esc_html__( 'ASC', 'grand-media' ) . '</option>
                                </select> <span>' . esc_html__( 'sort order', 'grand-media' ) . '</span>
                            </div>
                        </div>
                        ';
					}
					break;
				case 'GrandMedia_Tags':
					$settings = '
                    <div class="form-inline float-start row row-cols-auto">
                        <div class="form-group">
                            <input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_tag]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_gmedia_tag'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
                        </div>
                        <div class="form-group">
                            <select name="gm_screen_options[orderby_gmedia_tag]" class="form-control input-xs d-inline w-auto pe-4">
                                <option' . selected( $gm_screen_options['orderby_gmedia_tag'], 'id', false ) . ' value="id">' . esc_html__( 'ID', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['orderby_gmedia_tag'], 'name', false ) . ' value="name">' . esc_html__( 'Name', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['orderby_gmedia_tag'], 'count', false ) . ' value="count">' . esc_html__( 'Gmedia Count', 'grand-media' ) . '</option>
                            </select> <span>' . esc_html__( 'order items', 'grand-media' ) . '</span>
                        </div>
                        <div class="form-group">
                            <select name="gm_screen_options[sortorder_gmedia_tag]" class="form-control input-xs d-inline w-auto pe-4">
                                <option' . selected( $gm_screen_options['sortorder_gmedia_tag'], 'DESC', false ) . ' value="DESC">' . esc_html__( 'DESC', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['sortorder_gmedia_tag'], 'ASC', false ) . ' value="ASC">' . esc_html__( 'ASC', 'grand-media' ) . '</option>
                            </select> <span>' . esc_html__( 'sort order', 'grand-media' ) . '</span>
                        </div>
                    </div>
                    ';
					break;
				case 'GrandMedia_Galleries':
					if ( ! $gmCore->_get( 'edit_term' ) && ! $gmCore->_get( 'gallery_module' ) ) {
						$settings = '
						<div class="form-inline float-start row row-cols-auto">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_gallery]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_gmedia_gallery'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
							</div>
							<div class="form-group">
								<select name="gm_screen_options[orderby_gmedia_gallery]" class="form-control input-xs d-inline w-auto pe-4">
									<option' . selected( $gm_screen_options['orderby_gmedia_gallery'], 'id', false ) . ' value="id">' . esc_html__( 'ID', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_gallery'], 'name', false ) . ' value="name">' . esc_html__( 'Name', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_gallery'], 'global', false ) . ' value="global">' . esc_html__( 'Author ID', 'grand-media' ) . '</option>
								</select> <span>' . esc_html__( 'order items', 'grand-media' ) . '</span>
							</div>
							<div class="form-group">
								<select name="gm_screen_options[sortorder_gmedia_gallery]" class="form-control input-xs d-inline w-auto pe-4">
									<option' . selected( $gm_screen_options['sortorder_gmedia_gallery'], 'DESC', false ) . ' value="DESC">' . esc_html__( 'DESC', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['sortorder_gmedia_gallery'], 'ASC', false ) . ' value="ASC">' . esc_html__( 'ASC', 'grand-media' ) . '</option>
								</select> <span>' . esc_html__( 'sort order', 'grand-media' ) . '</span>
							</div>
						</div>
						';
					}
					break;
				case 'GrandMedia_WordpressLibrary':
					$settings = '<p>' . esc_html__( 'Set query options for this page to be loaded by default.', 'grand-media' ) . '</p>
					<div class="form-inline float-start row row-cols-auto">
						<div class="form-group">
							<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_wpmedia]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_wpmedia'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
						</div>
						<div class="form-group">
							<select name="gm_screen_options[orderby_wpmedia]" class="form-control input-xs d-inline w-auto pe-4">
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'ID', false ) . ' value="ID">' . esc_html__( 'ID', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'title', false ) . ' value="title">' . esc_html__( 'Title', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'filename', false ) . ' value="filename">' . esc_html__( 'Filename', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'date', false ) . ' value="date">' . esc_html__( 'Date', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'modified', false ) . ' value="modified">' . esc_html__( 'Last Modified', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'mime_type', false ) . ' value="mime_type">' . esc_html__( 'MIME Type', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_wpmedia'], 'author', false ) . ' value="author">' . esc_html__( 'Author', 'grand-media' ) . '</option>
							</select> <span>' . esc_html__( 'order items', 'grand-media' ) . '</span>
						</div>
						<div class="form-group">
							<select name="gm_screen_options[sortorder_wpmedia]" class="form-control input-xs d-inline w-auto pe-4">
								<option' . selected( $gm_screen_options['sortorder_wpmedia'], 'DESC', false ) . ' value="DESC">' . esc_html__( 'DESC', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['sortorder_wpmedia'], 'ASC', false ) . ' value="ASC">' . esc_html__( 'ASC', 'grand-media' ) . '</option>
							</select> <span>' . esc_html__( 'sort order', 'grand-media' ) . '</span>
						</div>
					</div>
					';
					break;
				case 'GrandMedia_Logs':
					$settings = '
                    <div class="form-inline float-start row row-cols-auto">
                        <div class="form-group">
                            <input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_log]" class="form-control input-xs d-inline" style="width: 5em;" value="' . esc_attr( $gm_screen_options['per_page_gmedia_log'] ) . '" /> <span>' . esc_html__( 'items per page', 'grand-media' ) . '</span>
                        </div>
                        <div class="form-group">
                            <select name="gm_screen_options[orderby_gmedia_log]" class="form-control input-xs d-inline w-auto pe-4">
                                <option' . selected( $gm_screen_options['orderby_gmedia_log'], 'log_date', false ) . ' value="log_date">' . esc_html__( 'Date', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['orderby_gmedia_log'], 'ID', false ) . ' value="ID">' . esc_html__( 'Gmedia ID', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['orderby_gmedia_log'], 'author', false ) . ' value="author">' . esc_html__( 'Author ID', 'grand-media' ) . '</option>
                            </select> <span>' . esc_html__( 'order items', 'grand-media' ) . '</span>
                        </div>
                        <div class="form-group">
                            <select name="gm_screen_options[sortorder_gmedia_log]" class="form-control input-xs d-inline w-auto pe-4">
                                <option' . selected( $gm_screen_options['sortorder_gmedia_log'], 'DESC', false ) . ' value="DESC">' . esc_html__( 'DESC', 'grand-media' ) . '</option>
                                <option' . selected( $gm_screen_options['sortorder_gmedia_log'], 'ASC', false ) . ' value="ASC">' . esc_html__( 'ASC', 'grand-media' ) . '</option>
                            </select> <span>' . esc_html__( 'sort order', 'grand-media' ) . '</span>
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
	 * @param string|array $status
	 * @param string       $option
	 * @param array        $value
	 *
	 * @return array
	 */
	public function screen_settings_save( $status, $option, $value ) {
		global $user_ID, $gmCore;
		if ( 'gm_screen_options' === $option ) {
			/*
			global $gmGallery;
			foreach ( $_POST['gm_screen_options'] as $key => $val ) {
				$gmGallery->options['gm_screen_options'][$key] = $val;
			}
			update_option( 'gmediaOptions', $gmGallery->options );
			*/
			$gm_screen_options = get_user_meta( $user_ID, 'gm_screen_options', true );
			if ( ! is_array( $gm_screen_options ) ) {
				$gm_screen_options = [];
			}
			$value = array_merge( $gm_screen_options, $gmCore->_post( 'gm_screen_options', [] ) );

			return $value;
		}

		return $status;
	}

}

global $gmAdmin;
// Start GmediaAdmin.
$gmAdmin = new GmediaAdmin();
