<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * gmediaApp()
 */
function gmediaApp() {
	global $gmCore, $gmGallery;

	$force_app_status = $gmCore->_get( 'force_app_status' );
	if ( false !== $force_app_status ) {
		$gm_options                       = get_option( 'gmediaOptions' );
		$gm_options['mobile_app']         = (int) $force_app_status;
		$gmGallery->options['mobile_app'] = $gm_options['mobile_app'];
		if ( ! $gm_options['site_ID'] ) {
			$gm_options['site_ID']         = (int) $gmCore->_get( 'force_site_id' );
			$gmGallery->options['site_ID'] = $gm_options['site_ID'];
		}
		update_option( 'gmediaOptions', $gm_options );
	}

	$alert = $gmCore->alert( 'danger', esc_html__( 'Your server is not accessable by iOS application', 'grand-media' ) );

	$site_ID    = (int) $gmGallery->options['site_ID'];
	$mobile_app = (int) $gmGallery->options['mobile_app'];

	$current_user = wp_get_current_user();

	?>
	<div class="card m-0 mw-100 p-0" id="gm_application">
		<?php wp_nonce_field( 'GmediaService' ); ?>
		<div class="card-body" id="gmedia-service-msg-panel">
			<?php
			if ( empty( $_SERVER['HTTP_X_REAL_IP'] ) && ( '127.0.0.1' === $_SERVER['REMOTE_ADDR'] || '::1' === $_SERVER['REMOTE_ADDR'] ) ) {
				echo wp_kses_post( $alert );
			} else {
				if ( ! $mobile_app || ! $site_ID ) {
					echo wp_kses_post( $alert );
					?>
					<div class="notice updated gm-message">
						<div class="gm-message-content">
							<div class="gm-plugin-icon">
								<img src="<?php echo esc_url( plugins_url( 'assets/img/icon-128x128.png', __FILE__ ) ); ?>" width="80" height="80" alt="">
							</div>
							<?php
							// translators: username.
							echo wp_kses_post( sprintf( __( '<p>Hey %s,<br>You should allow some data about your <b>Gmedia Gallery</b> to be sent to <a href="https://codeasily.com/" target="_blank" tabindex="1">codeasily.com</a> in order to use iOS application. <br />These data required if you want to use Gmedia iOS application on your iPhone.</p>', 'grand-media' ), $current_user->display_name ) );
							?>
						</div>
						<div class="gm-message-actions">
							<span class="spinner" style="float: none;"></span>
							<button class="button button-primary gm_service_action" data-action="allow" data-nonce="<?php echo esc_attr( wp_create_nonce( 'GmediaService' ) ); ?>"><?php esc_html_e( 'Allow &amp; Continue', 'grand-media' ); ?></button>
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
			}

			?>
		</div>
		<div class="card-body" id="gm_application_data">
			<?php if ( current_user_can( 'manage_options' ) ) { ?>
				<div class="container-fluid">
					<div class="row">
						<div class="col-xs-6">
							<p>
								<?php
								echo 'Server address: ' . esc_html( $_SERVER['SERVER_ADDR'] );
								echo '<br>Remote address: ' . esc_html( $_SERVER['REMOTE_ADDR'] );
								echo '<br>HTTP X Real IP: ' . ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ? esc_html( $_SERVER['HTTP_X_REAL_IP'] ) : '' );
								?>
							</p>
							<div class="gmapp-description">
								<div style="text-align:center; margin-bottom:30px;">
									<a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="vertical-align:middle; max-width:100%; margin:0 30px; max-height:88px;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/icon-128x128.png" alt=""/></a>
									<a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="vertical-align:middle; max-width:100%; margin:0 30px;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/appstore_button.png" alt=""/></a>
								</div>

								<p><?php echo wp_kses_post( __( 'You are using one of the best plugins to create media library as well as your personal cloud storage on your WordPress website. You have chosen <strong><a href="https://wordpress.org/plugins/grand-media/" target="_blank">Gmedia Gallery Plugin</a></strong> and this choice gives you great opportunities to manage and organise your media library.', 'grand-media' ) ); ?></p>
								<p><?php esc_html_e( 'We are happy to offer you a simple way to access your photos and audios by means of your iOS devices: at a few taps and you will be able to create great photo gallery and share it with your friends, readers and subscribers.', 'grand-media' ); ?></p>

								<p class="text-center"><img style="max-width:90%;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/slide1.jpg" alt=""/></p>

								<div class="text-left" style="padding-top:40%;">
									<div style="margin-right:20%">
										<h3><?php esc_html_e( 'DISCOVER and SHARE', 'grand-media' ); ?></h3>
										<p><?php esc_html_e( 'Search, learn, open new horizons, share! It is just as easy as a piece of cake! Your photos will be seen by your friends, relatives and others.', 'grand-media' ); ?></p>
									</div>
									<p><img style="max-width:90%;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/slide3.jpg" alt=""/></p>
								</div>
								<div class="text-left" style="padding-top:40%;">
									<div style="margin-right:20%">
										<h3><?php esc_html_e( 'PRIVATE CONTENT', 'grand-media' ); ?></h3>
										<p><?php esc_html_e( 'If you are one of subscribers, contributors, authors, editors or administrators, use your login and password to get an access to the private content.', 'grand-media' ); ?></p>
									</div>
									<p><img style="max-width:90%;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/slide5.jpg" alt=""/></p>
								</div>

								<div class="well well-lg text-center" style="margin-top:40%; padding-top:50px;">
									<p><?php esc_html_e( 'Download Gmedia iOS application from the App Store to manage your Gmedia&nbsp;Library from iPhone.', 'grand-media' ); ?></p>
									<div>
										<a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="vertical-align:middle; max-width:100%; margin:30px;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/appstore_button.png" alt=""/></a>
										<a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="vertical-align:middle; max-width:100%; margin:30px; max-height:88px;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/icon-128x128.png" alt=""/></a>
									</div>
								</div>
							</div>
						</div>
						<div class="col-xs-6">
							<div class="well-lg well">
								<p><?php esc_html_e( 'Below you can see information about your website that will be used by GmediaService and iOS application, so you\'ll be able to manage your Gmedia Library with your smartphone and other people can find and view your public collections.', 'grand-media' ); ?></p>
								<div class="form-group">
									<label><?php esc_html_e( 'Email', 'grand-media' ); ?>:</label>
									<input type="text" name="site_email" class="form-control input-sm" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" readonly/>
								</div>
								<div class="form-group">
									<label><?php esc_html_e( 'Site URL', 'grand-media' ); ?>:</label>
									<input type="text" name="site_url" class="form-control input-sm" value="<?php echo esc_url( home_url() ); ?>" readonly/>
								</div>
								<div class="form-group">
									<label><?php esc_html_e( 'Site Title', 'grand-media' ); ?>:</label>
									<input type="text" name="site_title" class="form-control input-sm" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" readonly/>
								</div>
								<div class="form-group">
									<label><?php esc_html_e( 'Site Description', 'grand-media' ); ?>:</label>
									<textarea rows="2" cols="10" name="site_description" class="form-control input-sm" readonly><?php echo esc_attr( get_bloginfo( 'description' ) ); ?></textarea>
								</div>
							</div>

							<div class="gmapp-description">
								<div class="text-end" style="padding-top:35%;">
									<div style="margin-left:20%">
										<h3><?php esc_html_e( 'FIND and ADD SITE it’s SIMPLY', 'grand-media' ); ?></h3>
										<p><?php esc_html_e( 'Just a few touches and our smart search bar will let you find and add your website, your friend’s website or a famous blogger’s site to your favourites list.', 'grand-media' ); ?></p>
									</div>
									<p><img style="max-width:90%;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/slide2.jpg" alt=""/></p>
								</div>

								<div class="text-end" style="padding-top:35%;">
									<div style="margin-left:20%">
										<h3><?php esc_html_e( 'MP3', 'grand-media' ); ?></h3>
										<p><?php esc_html_e( 'Take your favourite music track with you on a trip or create a playlist to travel with it! It is so simple with Gmedia. Share your energy and positive mood with your friends!', 'grand-media' ); ?></p>
									</div>
									<p><img style="max-width:90%;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/slide4.jpg" alt=""/></p>
								</div>

								<div class="text-end" style="padding-top:35%;">
									<div style="margin-left:20%">
										<h3><?php esc_html_e( 'GMEDIA LIBRARY', 'grand-media' ); ?></h3>
										<p><?php esc_html_e( 'If you are one of subscribers, contributors,authors, editors or administrators, use your login and password to get an access to private content. If your type of users has an access to Gmedia Library, you will be able to create photo collections and download pictures just from iPhone, using wide functional opportunities of our app and plugin.', 'grand-media' ); ?></p>
									</div>
									<p><img style="max-width:90%;" src="<?php echo esc_url( $gmCore->gmedia_url ); ?>/admin/assets/img/slide6.jpg" alt=""/></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
	<?php
}
