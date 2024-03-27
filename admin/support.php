<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * gmediaSupport()
 *
 * @return void content
 */
function gmediaSupport() {
	global $gmCore, $gmGallery;
	$current_user = wp_get_current_user();
	$alert        = '';

	$subject = $gmCore->_post( 'subject' );
	$name    = trim( $gmCore->_post( 'name', '' ) );
	$email   = trim( $gmCore->_post( 'email', '' ) );
	$summary = trim( $gmCore->_post( 'summary', '' ) );
	$message = trim( $gmCore->_post( 'message', '' ) );

	$domain            = trim( $gmCore->_post( 'domain', '' ) );
	$link              = trim( $gmCore->_post( 'link', '' ) );
	$wp_admin_user     = trim( $gmCore->_post( 'wp_admin_user', '' ) );
	$wp_admin_password = trim( $gmCore->_post( 'wp_admin_password', '' ) );
	$ftp_host          = trim( $gmCore->_post( 'ftp_host', '' ) );
	$ftp_user          = trim( $gmCore->_post( 'ftp_user', '' ) );
	$ftp_password      = trim( $gmCore->_post( 'ftp_password', '' ) );

	if ( $subject && $name && $email && is_email( $email ) && $summary && $message ) {
		$subjects = array(
			'billing_issue'     => 'Billing Issue',
			'feature_request'   => 'Feature Request',
			'customization'     => 'Customization',
			'pre_sale_question' => 'Pre-Sale Question',
			'bug'               => 'Bug',
		);
		$title    = $subjects[ $subject ];
		$content  = "{$summary}\r\n\r\n";
		$content  .= "Email: {$name} <{$email}>\r\n\r\n";

		$section = '';
		if ( $domain ) {
			$section .= "Domain: {$domain}\r\n";
		}
		if ( $link ) {
			$section .= "Link: {$link}\r\n";
		}
		if ( $section ) {
			$content .= "{$section}\r\n";
			$section = '';
		}
		if ( $wp_admin_user && $wp_admin_password ) {
			$section .= 'WP URI: ' . wp_login_url() . "\r\n";
			$section .= "WP User: {$wp_admin_user}\r\n";
			$section .= "WP Pass: {$wp_admin_password}\r\n";
		}
		if ( $section ) {
			$content .= "{$section}\r\n";
			$section = '';
		}
		if ( $ftp_host && $ftp_user && $ftp_password ) {
			$section .= "FTP Host: {$ftp_host}\r\n";
			$section .= "FTP User: {$ftp_user}\r\n";
			$section .= "FTP Pass: {$ftp_password}\r\n";
		}
		if ( $section ) {
			$content .= "{$section}\r\n";
			$section = '';
		}

		$license = empty( $gmGallery->options['license_key'] ) ? 'FREE' : $gmGallery->options['license_key'];
		$content .= "License: {$license}\r\n";

		$content .= "Message: \r\n{$message}\r\n\r\n";
		$headers = array(
			'From: Gmedia Support <support@gmedia.gallery>',
			"Reply-To: {$name} <{$email}>",
		);
		if ( wp_mail( 'gmediafolder@gmail.com', $title, $content, $headers ) ) {
			$alert   = $gmCore->alert( 'success', esc_html__( 'Your message has been sent! We\'ll get back to you as soon as we can.', 'grand-media' ) );
			$subject = $name = $email = $summary = $message = $domain = $link = $wp_admin_user = $wp_admin_password = $ftp_host = $ftp_user = $ftp_password = '';
		} else {
			$alert = $gmCore->alert( 'danger', esc_html__( 'Can\'t send message. Something is wrong.', 'grand-media' ) );
		}
	} elseif ( ! empty( $_POST ) ) {
		$alert = $gmCore->alert( 'danger', esc_html__( 'Fill all required (*) fields, please.', 'grand-media' ) );
	}

	?>
	<div class="card m-0 mw-100 p-0" id="gm_support">
		<div class="card-body" id="gmedia-msg-panel"><?php echo wp_kses_post( $alert ); ?></div>
		<form method="post" class="card-body" id="gm_support_form">
			<?php if ( current_user_can( 'manage_options' ) ) { ?>
			<div class="container-fluid">
				<div class="form-header clearfix">
					<div class="alignleft">
						<img src="<?php echo esc_url( plugins_url( 'assets/img/icon-128x128.png', __FILE__ ) ); ?>" alt="" style="width:94px; height:94px;">
					</div>
					<div class="form-header-body">
						<h1 class="form-title">Have questions? We're happy to help!</h1>
						<h2 class="plugin-title">Gmedia Gallery</h2>
						<h3>We'll do our best to get back to you as soon as we can.</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-7">
						<section id="contact_form">
							<div>
								<fieldset>
									<div class="form-group has-feedback">
										<label class="control-label"><?php esc_html_e( 'First and Last Name', 'grand-media' ); ?> *</label>
										<input type="text" name="name" class="form-control" value="<?php echo esc_attr( $name ? $name : $current_user->display_name ); ?>" required>
										<span class="fa-solid fa-user form-control-feedback"></span>
									</div>
									<div class="form-group has-feedback">
										<label class="control-label"><?php esc_html_e( 'Your Email Address', 'grand-media' ); ?> *</label>
										<input type="email" name="email" class="form-control" value="<?php echo esc_attr( $email ? $email : $current_user->user_email ); ?>" required>
										<span class="fa-solid fa-envelope form-control-feedback"></span>
									</div>
									<div class="form-group hidden">
										<label class="control-label"><?php esc_html_e( 'Context', 'grand-media' ); ?></label>
										<select id="context_plugin" class="form-control">
											<option>GmediaGallery</option>
										</select>
									</div>
									<div class="form-group form-subjects">
										<label class="control-label"><?php esc_html_e( 'Subject', 'grand-media' ); ?> *</label>
										<div class="well well-sm" style="background-color: #fff;">
											<div class="radio">
												<label><input type="radio" name="subject" <?php /* checked($subject, 'billing_issue', true); */ ?> value="billing_issue" required data-sections=".message" data-msglabel="<?php esc_attr_e( 'Please describe the issue you are having. Be detailed but brief.', 'grand-media' ); ?>"> <?php esc_html_e( 'Billing Issue', 'grand-media' ); ?>
												</label></div>
											<div class="radio">
												<label><input type="radio" name="subject" <?php /* checked($subject, 'feature_request', true); */ ?> value="feature_request" required data-sections=".message" data-msglabel="<?php esc_attr_e( 'Describe the feature you would like to see added.', 'grand-media' ); ?>"> <?php esc_html_e( 'Feature Request', 'grand-media' ); ?>
												</label></div>
											<div class="radio">
												<label><input type="radio" name="subject" <?php /* checked($subject, 'customization', true); */ ?> value="customization" required data-sections=".site,.message" data-msglabel="<?php esc_attr_e( 'Please describe the use-case and the different features you would like to be custom developed for you.', 'grand-media' ); ?>"> <?php esc_html_e( 'Customization', 'grand-media' ); ?>
												</label></div>
											<div class="radio">
												<label><input type="radio" name="subject" <?php /* checked($subject, 'pre_sale_question', true); */ ?> value="pre_sale_question" required data-sections=".message" data-msglabel="<?php esc_attr_e( 'What would you like to know before purchasing?', 'grand-media' ); ?>"> <?php esc_html_e( 'Pre-Sale Question', 'grand-media' ); ?>
												</label></div>
											<div class="radio">
												<label><input type="radio" name="subject" <?php /* checked($subject, 'bug', true); */ ?> value="bug" required data-sections=".site,.message,.credentials" data-msglabel="<?php esc_attr_e( 'Please describe the bug and how to reproduce it.', 'grand-media' ); ?>"> <?php esc_html_e( 'Bug', 'grand-media' ); ?>
												</label></div>
										</div>
									</div>
								</fieldset>
								<div class="dynamic">
									<fieldset class="message">
										<div class="form-group has-feedback">
											<label class="control-label"><?php esc_html_e( 'Summary (In 10 words or less, summarize your issue or question)', 'grand-media' ); ?> *</label>
											<input type="text" name="summary" class="form-control" value="<?php echo esc_attr( $summary ); ?>" required>
											<span class="fa-solid fa-table-cells-large form-control-feedback"></span>
										</div>
										<div class="form-group has-feedback">
											<label class="control-label"><span id="msglabel"><?php esc_html_e( 'Please describe the issue you are having. Be detailed but brief', 'grand-media' ); ?></span> *</label>
											<textarea name="message" cols="44" rows="10" class="form-control" required><?php echo esc_textarea( $message ); ?></textarea>
											<span class="fa-solid fa-pen-to-square form-control-feedback"></span>
										</div>
									</fieldset>
									<fieldset class="site">
										<div class="form-group has-feedback">
											<label class="control-label"><?php esc_html_e( 'Your Site Address', 'grand-media' ); ?></label>
											<input type="text" name="domain" class="form-control" value="<?php echo esc_attr( $domain ? $domain : home_url() ); ?>">
											<span class="fa-solid fa-globe form-control-feedback"></span>
										</div>
										<div class="form-group has-feedback">
											<label class="control-label"><?php esc_html_e( 'If it\'s about a specific page on your site, please add the relevant link', 'grand-media' ); ?></label>
											<?php // translators: url. ?>
											<input type="text" name="link" class="form-control" value="<?php echo esc_attr( $link ); ?>" placeholder="<?php echo esc_attr( sprintf( __( 'Relevant Page on Your Site (E.g. %s)', 'grand-media' ), home_url( '/relevant-page/' ) ) ); ?>">
											<span class="fa-solid fa-globe form-control-feedback"></span>
										</div>
									</fieldset>
									<fieldset class="credentials">
										<h4 class="title" data-bs-toggle="collapse" href="#wpLogin"><span><?php esc_html_e( 'WordPress Login', 'grand-media' ); ?></span>
											<small class="fa-solid fa-plus"></small>
										</h4>
										<div id="wpLogin" class="collapse">
											<div class="form-group has-feedback">
												<label class="control-label"><?php esc_html_e( 'Username', 'grand-media' ); ?></label>
												<input type="text" name="wp_admin_user" class="form-control" value="<?php echo esc_attr( $wp_admin_user ); ?>">
												<span class="fa-solid fa-user form-control-feedback"></span>
											</div>
											<div class="form-group has-feedback">
												<label class="control-label"><?php esc_html_e( 'Password', 'grand-media' ); ?></label>
												<input type="password" name="wp_admin_password" class="form-control" value="<?php echo esc_attr( $wp_admin_password ); ?>">
												<span class="fa-solid fa-lock form-control-feedback"></span>
											</div>
											<p><?php esc_html_e( 'Instead of providing your primary admin account, create a new admin that can be disabled when the support case is closed.', 'grand-media' ); ?></p>
										</div>
									</fieldset>
									<fieldset class="credentials">
										<h4 class="title" data-bs-toggle="collapse" href="#ftpAccess"><span><?php esc_html_e( 'FTP Access', 'grand-media' ); ?></span>
											<small class="fa-solid fa-plus"></small>
										</h4>
										<div id="ftpAccess" class="collapse">
											<div class="form-group has-feedback">
												<label class="control-label"><?php esc_html_e( 'FTP Host', 'grand-media' ); ?></label>
												<input type="text" name="ftp_host" class="form-control" value="<?php echo esc_attr( $ftp_host ); ?>">
												<span class="fa-solid fa-globe form-control-feedback"></span>
											</div>
											<div class="form-group has-feedback">
												<label class="control-label"><?php esc_html_e( 'FTP User', 'grand-media' ); ?></label>
												<input type="text" name="ftp_user" class="form-control" value="<?php echo esc_attr( $ftp_user ); ?>">
												<span class="fa-solid fa-user form-control-feedback"></span>
											</div>
											<div class="form-group has-feedback">
												<label class="control-label"><?php esc_html_e( 'FTP Password', 'grand-media' ); ?></label>
												<input type="password" name="ftp_password" class="form-control" value="<?php echo esc_attr( $ftp_password ); ?>">
												<span class="fa-solid fa-lock form-control-feedback"></span>
											</div>
											<p><?php esc_html_e( 'Instead of providing your primary FTP account, create a new FTP user that can be disabled when the support case is closed.', 'grand-media' ); ?></p>
										</div>
									</fieldset>
								</div>
							</div>
							<footer style="margin-top: 20px;">
								<button class="btn btn-lg btn-primary"><?php esc_html_e( 'Submit', 'grand-media' ); ?></button>
							</footer>
						</section>
					</div>
					<div class="col-sm-5">
						<section class="well well-md">
							<h3>Frequently Asked Questions</h3>
							<div id="faq">
								<ul class="clearfix">
									<li><p>All submitted data will not be saved and is used solely for the purposes your support request. You will not be added to a mailing list, solicited without your permission, nor will your site be administered after this support case is closed.</p></li>

								</ul>
							</div>
						</section>
					</div>
				</div>
				<style>
					#gm_support .form-header {
						margin-bottom: 20px;
					}

					#gm_support .form-header-body {
						margin-left: 110px;
						padding-top: 15px;
						padding-bottom: 0;
					}

					#gm_support h1.form-title {
						font-size: 20px;
						font-weight: bold;
						line-height: 1.2em;
						margin: 0;
					}

					#gm_support h2.plugin-title {
						font-size: 18px;
						line-height: 1.2em;
						margin: 0;
					}

					#gm_support h3 {
						font-size: 14px;
						line-height: 1.8em;
						margin: 0;
					}

					#gm_support .form-subjects .radio {
						margin: 7px 0;
					}

					#gm_support .credentials h4 {
						cursor: pointer;
						color: #2e6286;
					}

					#gm_support .credentials h4:hover {
						cursor: pointer;
						color: #2e6da4;
					}

					#gm_support .dynamic fieldset {
						display: none;
					}
				</style>
				<script>
									jQuery(function($) {
										$('.form-subjects input').on('change', function() {
											console.log(this);
											var label = $(this).attr('data-msglabel'),
													sections = $(this).attr('data-sections');
											$('.dynamic fieldset').hide().filter(sections).show();
											$('#msglabel').text(label);
										});
									});
				</script>

			</div>
		</form>
		<?php } ?>
	</div>
	<?php
}
