<?php

/**
 * GmediaAdmin - Admin Section for GRAND Media
 *
 */
class GmediaAdmin {
	var $pages = array();

	/**
	 * constructor
	 */
	function __construct() {
		global $pagenow;

		// Add the admin menu
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );

		// Add the script and style files
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_scripts' ) );

		add_filter( 'screen_settings', array( &$this, 'screen_settings' ), 10, 2 );
		add_filter( 'set-screen-option', array( &$this, 'screen_settings_save' ), 11, 3 );

		if ( ( 'admin.php' == $pagenow ) && isset( $_GET['page'] ) && ( false !== strpos( $_GET['page'], 'GrandMedia' ) ) && isset( $_GET['gmediablank'] ) ) {
			add_action( 'admin_init', array( &$this, 'gmedia_blank_page' ) );
		}

	}

	/**
	 * Load gmedia pages in wpless interface
	 */
	function gmedia_blank_page() {
		set_current_screen( 'GrandMedia_Settings' );

		global $gmCore;
		$gmediablank = $gmCore->_get( 'gmediablank', '' );
		/*
		add_filter('admin_body_class', function(){
			$gmediablank = isset($_GET['gmediablank'])? $_GET['gmediablank'] : '';
			return "gmedia-blank $gmediablank"; });
		*/
		add_filter( 'admin_body_class', create_function( '', '$gmediablank = isset($_GET["gmediablank"])? $_GET["gmediablank"] : ""; return "gmedia-blank $gmediablank";' ) );
		define( 'IFRAME_REQUEST', true );

		iframe_header( 'GmediaGallery' );

		switch ( $gmediablank ) {
			case 'update_plugin':
				require_once( dirname( dirname( __FILE__ ) ) . '/update.php' );
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
		}

		iframe_footer();
		exit;
	}

	/**
	 * @return string
	 */
	function gmedia_blank_page_body_class() {
		return 'gmedia-blank';
	}

	// integrate the menu
	function add_menu() {
		$gmediaURL     = plugins_url( GMEDIA_FOLDER );
		$this->pages   = array();
		$this->pages[] = add_object_page( __( 'Gmedia Library', 'grand-media' ), 'Gmedia Gallery', 'gmedia_library', 'GrandMedia', array( &$this, 'shell' ), $gmediaURL . '/admin/images/gm-icon.png' );
		$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Library', 'grand-media' ), __( 'Gmedia Library', 'grand-media' ), 'gmedia_library', 'GrandMedia', array( &$this, 'shell' ) );
		if ( current_user_can( 'gmedia_library' ) ) {
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Add Media Files', 'grand-media' ), __( 'Add/Import Files', 'grand-media' ), 'gmedia_upload', 'GrandMedia_AddMedia', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Albums, Tags, Filters...', 'grand-media' ), __( 'Albums, Tags, Filters...', 'grand-media' ), 'gmedia_library', 'GrandMedia_Terms', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Galleries', 'grand-media' ), __( 'Create/Manage Galleries...', 'grand-media' ), 'gmedia_gallery_manage', 'GrandMedia_Galleries', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Modules', 'grand-media' ), __( 'Modules', 'grand-media' ), 'gmedia_gallery_manage', 'GrandMedia_Modules', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Gmedia Settings', 'grand-media' ), __( 'Settings', 'grand-media' ), 'manage_options', 'GrandMedia_Settings', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Mobile Application', 'grand-media' ), __( 'Mobile Application', 'grand-media' ), 'gmedia_settings', 'GrandMedia_App', array( &$this, 'shell' ) );
			$this->pages[] = add_submenu_page( 'GrandMedia', __( 'Wordpress Media Library', 'grand-media' ), __( 'WP Media Library', 'grand-media' ), 'gmedia_import', 'GrandMedia_WordpressLibrary', array( &$this, 'shell' ) );
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
		global $gmProcessor, $gmGallery;

		// check for upgrade
		if ( get_option( 'gmediaDbVersion' ) != GMEDIA_DBVERSION ) {
			if ( isset( $_GET['do_update'] ) && ( 'gmedia' == $_GET['do_update'] ) ) {
				$update_frame      = '<iframe name="gmedia_update" id="gmedia_update" width="100%" height="500" src="' . admin_url( 'admin.php?page=GrandMedia&gmediablank=update_plugin' ) . '"></iframe>';
				$gmProcessor->page = 'GrandMedia_Update';
			} else {
				return;
			}
		}

		$sideLinks = $this->sideLinks();

		if ( isset( $update_frame ) ) {
			$sideLinks['grandTitle'] = __( 'Updating GmediaGallery Plugin', 'grand-media' );
		}
		?>
		<div id="gmedia-container">
			<div id="gmedia-header" class="clearfix">
				<div id="gmedia-logo">Gmedia
					<small> by CodEasily.com</small>
				</div>
				<h2><?php echo $sideLinks['grandTitle']; ?></h2>
			</div>
			<div class="container-fluid">
				<div class="row row-fx180-fl">
					<div class="col-sm-2 hidden-xs" id="sidebar" role="navigation">
						<?php echo $sideLinks['sideLinks']; ?>

						<?php if($gmGallery->options['installDate'] && (strtotime($gmGallery->options['installDate']) < strtotime('2 weeks ago'))){ ?>
						<div class="row panel panel-default visible-lg-block">
							<div class="panel-heading" data-toggle="collapse" data-target="#support_div_collapse" aria-expanded="true" aria-controls="support_div_collapse" style="cursor:pointer;">
								<b><?php _e('Any feedback?','grand-media'); ?></b>
							</div>
							<div class="collapse<?php if(empty($gmGallery->options['license_key'])){ echo ' in'; } ?>" id="support_div_collapse">
								<div class="panel-body">
									<p><?php _e('You can help me spread the word about GmediaGallery among the users striving to get awesome galleries on their WordPress sites.','grand-media'); ?></p>
									<p><a class="btn btn-primary" href="https://wordpress.org/support/view/plugin-reviews/grand-media?filter=5" target="_blank"><?php _e('Rate Gmedia Gallery','grand-media'); ?></a></p>
									<p><?php _e('Your reviews and ideas helps me to create new awesome modules and to improve plugin.','grand-media'); ?></p>
								</div>
							</div>
						</div>
						<?php } ?>
						<div class="row panel visible-lg-block">
							<a class="twitter-timeline" href="https://twitter.com/CodEasily/timelines/648240437141086212" data-widget-id="648245214201692161"></a> <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
						</div>
					</div>
					<div class="col-sm-10 col-xs-12">
						<div id="gm-message"><?php
							echo $gmProcessor->alert( 'success', $gmProcessor->msg );
							echo $gmProcessor->alert( 'danger', $gmProcessor->error );
							?></div>
						<?php
						if ( isset( $update_frame ) ) {
							?>
							<div class="panel panel-default">
								<div class="panel-body"><?php echo $update_frame; ?></div>
							</div>
						<?php
						} else {
							$this->controller();
						}
						?>
					</div>
				</div>
			</div>
		</div>
	<?php
	}

	function sideLinks() {
		global $submenu, $gmProcessor;
		$content['sideLinks'] = '
		<div id="gmedia-navbar">
			<div class="row">
				<ul class="list-group">';
		foreach ( $submenu['GrandMedia'] as $menuKey => $menuItem ) {
			if ( $submenu['GrandMedia'][ $menuKey ][2] == $gmProcessor->page ) {
				$iscur                 = ' active';
				$content['grandTitle'] = $submenu['GrandMedia'][ $menuKey ][3];
			} else {
				$iscur = '';
			}

			$content['sideLinks'] .= "\n" . '<a class="list-group-item' . $iscur . '" href="' . admin_url( 'admin.php?page=' . $submenu['GrandMedia'][ $menuKey ][2] ) . '">' . $submenu['GrandMedia'][ $menuKey ][0] . '</a>';
		}
		$content['sideLinks'] .= '
				</ul>
			</div>
		</div>';

		return $content;
	}

	function controller() {

		global $gmCore, $gmProcessor;
		switch ( $gmProcessor->page ) {
			case 'GrandMedia_AddMedia':
				include_once( dirname( __FILE__ ) . '/addmedia.php' );
				gmedia_AddMedia();
				break;
			case 'GrandMedia_Terms':
				include_once( dirname( __FILE__ ) . '/terms.php' );
				if ( isset( $_GET['edit_album'] ) ) {
					gmediaAlbumEdit();
				} elseif ( isset( $_GET['edit_filter'] ) ) {
					gmediaFilterEdit();
				} else {
					gmediaTerms();
				}
				break;
			case 'GrandMedia_Galleries':
				include_once( dirname( __FILE__ ) . '/galleries.php' );
				if ( isset( $_GET['gallery_module'] ) || isset( $_GET['edit_gallery'] ) ) {
					gmediaGalleryEdit();
				} else {
					gmediaGalleries();
				}
				break;
			case 'GrandMedia_Modules':
				include_once( dirname( __FILE__ ) . '/modules.php' );
				gmediaModules();
				break;
			case 'GrandMedia_Settings':
				include_once( dirname( __FILE__ ) . '/settings.php' );
				gmSettings();
				break;
			case 'GrandMedia_App':
				include_once( dirname( __FILE__ ) . '/app.php' );
				gmediaApp();
				break;
			case 'GrandMedia_WordpressLibrary':
				include_once( dirname( __FILE__ ) . '/wpmedia.php' );
				grandWPMedia();
				break;
			case 'GrandMedia':
			default:
				include_once( dirname( __FILE__ ) . '/gmedia.php' );
				gmediaLib();
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

		if($gmGallery->options['isolation_mode']) {
			global $wp_scripts, $wp_styles;
			foreach ( $wp_scripts->registered as $handle => $wp_script ) {
				if ( ( false !== strpos( $wp_script->src, '/plugins/' ) ) && ( false === strpos( $wp_script->src, GMEDIA_FOLDER ) ) ) {
					if ( in_array( $handle, $wp_scripts->queue ) ) {
						wp_dequeue_script( $handle );
					}
					wp_deregister_script( $handle );
				}
			}
			foreach ( $wp_styles->registered as $handle => $wp_style ) {
				if ( ( false !== strpos( $wp_style->src, '/plugins/' ) ) && ( false === strpos( $wp_style->src, GMEDIA_FOLDER ) ) ) {
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
						if ( $gmProcessor->mode ) {
							wp_enqueue_script( 'alphanum', $gmCore->gmedia_url . '/assets/jq-plugins/jquery.alphanum.js', array( 'jquery' ), '1.0.16' );

							wp_enqueue_script( 'moment', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/moment.min.js', array( 'jquery' ), '2.5.1' );
							wp_enqueue_style( 'datetimepicker', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css', array( 'gmedia-bootstrap' ), '2.1.32' );
							wp_enqueue_script( 'datetimepicker', $gmCore->gmedia_url . '/assets/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js', array( 'jquery', 'moment', 'gmedia-bootstrap' ), '2.1.32' );
						}
					}
					if ( $gmCore->caps['gmedia_terms'] ) {
						wp_enqueue_style( 'selectize' );
						wp_enqueue_script( 'selectize' );
					}
					break;
				case "GrandMedia_WordpressLibrary" :
					if ( $gmCore->caps['gmedia_import'] ) {
						wp_enqueue_style( 'selectize' );
						wp_enqueue_script( 'selectize' );
					}
					break;
				case "GrandMedia_Terms" :
					if ( $gmCore->_get( 'edit_album' ) && $gmCore->caps['gmedia_album_manage'] ) {
						wp_enqueue_style( 'jquery-ui-smoothness', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.min.css', array(), '1.10.2', 'screen' );
						wp_enqueue_script( 'jquery-ui-full', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js', array(), '1.10.2' );

						wp_enqueue_script( 'tinysort', $gmCore->gmedia_url . '/assets/jq-plugins/jquery.tinysort.js', array( 'jquery' ), '1.5.6' );
					} elseif ( isset($_GET['edit_filter']) && $gmCore->caps['gmedia_filter_manage'] ) {
						wp_enqueue_style( 'selectize' );
						wp_enqueue_script( 'selectize' );
					}

					break;
				case "GrandMedia_AddMedia" :
					if ( $gmCore->caps['gmedia_terms'] ) {
						wp_enqueue_style( 'selectize' );
						wp_enqueue_script( 'selectize' );
					}
					if ( $gmCore->caps['gmedia_upload'] ) {
						$tab = $gmCore->_get( 'tab', 'upload' );
						if ( $tab == 'upload' ) {
							wp_enqueue_style( 'jquery-ui-smoothness', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.min.css', array(), '1.10.2', 'screen' );
							wp_enqueue_script( 'jquery-ui-full', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js', array(), '1.10.2' );

							wp_enqueue_script( 'gmedia-plupload', $gmCore->gmedia_url . '/assets/plupload/plupload.full.min.js', array( 'jquery', 'jquery-ui-full' ), '2.1.2' );

							wp_enqueue_style( 'jquery.ui.plupload', $gmCore->gmedia_url . '/assets/plupload/jquery.ui.plupload/css/jquery.ui.plupload.css', array( 'jquery-ui-smoothness' ), '2.1.2', 'screen' );
							wp_enqueue_script( 'jquery.ui.plupload', $gmCore->gmedia_url . '/assets/plupload/jquery.ui.plupload/jquery.ui.plupload.min.js', array( 'gmedia-plupload', 'jquery-ui-full' ), '2.1.2' );

						}
					}
					break;
				case "GrandMedia_Settings" :
				case "GrandMedia_App" :
					// under construction
					break;
				case "GrandMedia_Galleries" :
					if ( $gmCore->caps['gmedia_gallery_manage'] && ( isset( $_GET['gallery_module'] ) || isset( $_GET['edit_gallery'] ) ) ) {
						wp_enqueue_script( 'jquery-ui-sortable' );
						wp_enqueue_style( 'selectize' );
						wp_enqueue_script( 'selectize' );

						wp_enqueue_style( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.css', array( 'gmedia-bootstrap' ), '0.9.13' );
						wp_enqueue_script( 'jquery.minicolors', $gmCore->gmedia_url . '/assets/minicolors/jquery.minicolors.js', array( 'jquery' ), '0.9.13' );
					}
					break;
			}
		}

		wp_enqueue_style( 'grand-media' );
		wp_enqueue_script( 'grand-media' );

	}

	function screen_help() {
		$screen    = get_current_screen();
		$screen_id = explode( 'page_', $screen->id, 2 );
		$screen_id = $screen_id[1];

		$screen->add_help_tab( array(
			'id'      => 'help_' . $screen_id . '_support',
			'title'   => __( 'Support' ),
			'content' => '<h4>First steps</h4>
<p>If you have any problems with displaying Gmedia Gallery in admin or on website. Before posting to the Forum try next:</p>
<ul>
	<li>Exclude plugin conflicts: Disable other plugins one by one and check if it resolve problem</li>
	<li>Exclude theme conflict: Temporary switch to one of default themes and check if gallery works</li>
</ul>
<h4>Links</h4>
<p><a href="http://codeasily.com/community/forum/gmedia-gallery-wordpress-plugin/" target="_blank">' . __( 'Support Forum', 'grand-media' ) . '</a>
	| <a href="http://codeasily.com/contact/" target="_blank">' . __( 'Contact', 'grand-media' ) . '</a>
	| <a href="http://codeasily.com/portfolio/gmedia-gallery-modules/" target="_blank">' . __( 'Demo', 'grand-media' ) . '</a>
	| <a href="http://codeasily.com/product/one-site-license/" target="_blank">' . __( 'Premium', 'grand-media' ) . '</a>
</p>',
		) );

		switch ( $screen_id ) {
			case 'GrandMedia' :
				break;
			case 'GrandMedia_Settings' :
				if(current_user_can('manage_options')) {
					$screen->add_help_tab( array(
						'id'      => 'help_' . $screen_id . '_license',
						'title'   => __( 'License Key' ),
						'content' => '<h4>Should I buy it, to use plugin?</h4>
<p>No, plugin is absolutely free and all modules for it are free to install.</p>
<p>Even premium modules are fully functional and free to test, but have backlink labels. To remove baclink labels from premium modules you need license key.</p>
<p>Note: License Key will remove backlinks from all current and future premium modules, so you can use all available modules on one website.</p>
<p>Do not purchase license key before testing module you like. Only if everything works fine and you satisfied with functionality you are good to purchase license. Otherwise use <a href="http://codeasily.com/community/forum/gmedia-gallery-wordpress-plugin/" target="_blank">' . __( 'Gmedia Support Forum', 'grand-media' ) . '</a>.</p>
<h4>I have license key but I can\'t activate it</h4>
<p>Contact developer <a href="mailto:gmediafolder@gmail.com">gmediafolder@gmail.com</a> with your problem and wait for additional instructions and code for manual activation</p>
<div><a class="btn btn-default" href="' . admin_url( 'admin.php?page=' . $screen_id . '&license_activate=manual' ) . '">Manual Activation</a></div>',
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

			$gm_screen_options = $gmProcessor->user_options();

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
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'date', false ) . ' value="date">' . __( 'Date', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'modified', false ) . ' value="modified">' . __( 'Last Modified', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'mime_type', false ) . ' value="mime_type">' . __( 'MIME Type', 'grand-media' ) . '</option>
								<option' . selected( $gm_screen_options['orderby_gmedia'], 'author', false ) . ' value="author">' . __( 'Author', 'grand-media' ) . '</option>
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
				case 'GrandMedia_Terms' :
					$taxonomy = $gmCore->_get( 'term', 'gmedia_album' );
					if ( $gmCore->_get( 'edit_album' ) ) {
						$settings = '
						<div class="form-inline pull-left">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_sort_gmedia]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_sort_gmedia'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
							</div>
						</div>
						';
					} elseif( !in_array($taxonomy, array('gmedia_category', 'gmedia_filter')) ){
						$settings = '
						<div class="form-inline pull-left">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_terms]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_terms'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
							</div>
							<div class="form-group">
								<select name="gm_screen_options[orderby_gmedia_terms]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['orderby_gmedia_terms'], 'id', false ) . ' value="id">' . __( 'ID', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_terms'], 'name', false ) . ' value="name">' . __( 'Name', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_terms'], 'count', false ) . ' value="count">' . __( 'Gmedia Count', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_terms'], 'global', false ) . ' value="global">' . __( 'Author ID (for albums only)', 'grand-media' ) . '</option>
								</select> <span>' . __( 'order items', 'grand-media' ) . '</span>
							</div>
							<div class="form-group">
								<select name="gm_screen_options[sortorder_gmedia_terms]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['sortorder_gmedia_terms'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['sortorder_gmedia_terms'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
								</select> <span>' . __( 'sort order', 'grand-media' ) . '</span>
							</div>
						</div>
						';
					}
					break;
				case 'GrandMedia_Galleries' :
					if ( !$gmCore->_get( 'edit_gallery' ) && !$gmCore->_get( 'gallery_module' ) ) {
						$settings = '
						<div class="form-inline pull-left">
							<div class="form-group">
								<input type="number" max="999" min="0" step="5" size="3" name="gm_screen_options[per_page_gmedia_galleries]" class="form-control input-sm" style="width: 5em;" value="' . $gm_screen_options['per_page_gmedia_galleries'] . '" /> <span>' . __( 'items per page', 'grand-media' ) . '</span>
							</div>
							<div class="form-group">
								<select name="gm_screen_options[orderby_gmedia_galleries]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['orderby_gmedia_galleries'], 'id', false ) . ' value="id">' . __( 'ID', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_galleries'], 'name', false ) . ' value="name">' . __( 'Name', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['orderby_gmedia_galleries'], 'global', false ) . ' value="global">' . __( 'Author ID', 'grand-media' ) . '</option>
								</select> <span>' . __( 'order items', 'grand-media' ) . '</span>
							</div>
							<div class="form-group">
								<select name="gm_screen_options[sortorder_gmedia_galleries]" class="form-control input-sm">
									<option' . selected( $gm_screen_options['sortorder_gmedia_galleries'], 'DESC', false ) . ' value="DESC">' . __( 'DESC', 'grand-media' ) . '</option>
									<option' . selected( $gm_screen_options['sortorder_gmedia_galleries'], 'ASC', false ) . ' value="ASC">' . __( 'ASC', 'grand-media' ) . '</option>
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
// Start GmediaAdmin
new GmediaAdmin();
