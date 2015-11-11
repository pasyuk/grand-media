<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
	die('You are not allowed to call this page directly.');
}

/**
 * gmSettings()
 *
 * @return mixed content
 */
function gmediaApp(){
	global $gmCore, $gmProcessor, $gmGallery;

	if(false !== ($force_app_status = $gmCore->_get('force_app_status'))){
		$gm_options = get_option('gmediaOptions');
		$gm_options['mobile_app'] = (int) $force_app_status;
		update_option('gmediaOptions', $gm_options);
	}
	$alert = '';
	$btn_state = '';
	if('127.0.0.1' == $_SERVER['SERVER_ADDR']){
		$alert =  $gmProcessor->alert('danger', __('Your server is not accessable by iOS application', 'grand-media'));
		$btn_state = ' disabled';
	}

	$email = $gmGallery->options['site_email']? $gmGallery->options['site_email'] : get_option('admin_email');
	$category = $gmGallery->options['site_category'];
	$site_ID = $gmGallery->options['site_ID'];
	$mobile_app = (int) $gmGallery->options['mobile_app'];

	?>
	<form class="panel panel-default" method="post" id="gm_application">
		<div class="panel-heading clearfix">
			<div class="btn-toolbar pull-left gm_service_actions">
				<div class="btn-group<?php if(!$mobile_app){ echo ' hidden'; } ?>">
					<button type="button" name="gmedia_application_deactivate" id="app_deactivate" class="btn btn-danger<?php echo $btn_state; ?>" data-confirm="<?php _e('Disable access to your Gmedia Library via iOS application?') ?>"><?php _e('Disable Service', 'grand-media'); ?></button>
					<button type="button" name="gmedia_application_updateinfo" id="app_updateinfo" class="btn btn-primary<?php echo $btn_state; ?>"><?php _e('Update Info', 'grand-media'); ?></button>
				</div>
				<button type="button" name="gmedia_application_activate" id="app_activate" class="btn btn-primary<?php echo $btn_state; if($mobile_app){ echo ' hidden'; } ?>"><?php _e('Enable Service', 'grand-media'); ?></button>
			</div>
			<?php
			wp_nonce_field('GmediaService');
			?>
		</div>
		<div class="panel-body" id="gmedia-msg-panel"><?php echo $alert; ?></div>
		<div class="panel-body" id="gm_application_data">
			<?php if(current_user_can('manage_options')){ ?>
				<div class="container-fluid">
					<div class="row">
						<div class="col-xs-7">
							<!--<p><?php echo 'Server address: '.$_SERVER['SERVER_ADDR'];
							echo '<br>Remote address: '.$_SERVER['REMOTE_ADDR'];
							?></p>-->
							<p><?php _e('On the right side you can see information about your website that will be used by iOS application, so you\'ll be able to manage your Gmedia Library with your smartphone.', 'grand-media'); ?></p>
							<p><?php _e('Download Gmedia iOS application from the App Store to manage your Gmedia Library from iPhone.', 'grand-media'); ?></p>
							<div class="text-center"><img style="max-width:100%;" src="<?php echo $gmCore->gmedia_url; ?>/admin/images/mobile_app.png" />
								<br /><a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="max-width:100%;" src="<?php echo $gmCore->gmedia_url; ?>/admin/images/appstore_button.png" /></a></div>
						</div>
						<div class="col-xs-5">
							<div class="form-group">
								<label><?php _e('Email', 'grand-media') ?>:</label>
								<input type="text" name="email" class="form-control input-sm" value="<?php echo $email; ?>"/>
							</div>
							<div class="form-group">
								<?php
								$term_type = 'gmedia_category';
								$gm_terms  = $gmGallery->options['taxonomies'][ $term_type ];

								$terms_category = '';
								if ( count( $gm_terms ) ) {
									foreach ( $gm_terms as $term_name => $term_title ) {
										$selected_option = ( $category === $term_name ) ? ' selected="selected"' : '';
										$terms_category .= '<option' . $selected_option . ' value="' . $term_name . '">' . esc_html( $term_title ) . '</option>' . "\n";
									}
								}
								?>
								<label><?php _e( 'Which category fit your site most?', 'grand-media' ); ?></label>
								<select id="gmedia_category" name="category" class="form-control input-sm">
									<option value=""<?php echo $category? '' : ' selected="selected"'; ?>><?php _e( 'Uncategorized', 'grand-media' ); ?></option>
									<?php echo $terms_category; ?>
								</select>
							</div>
							<div class="form-group">
								<label><?php _e('Site URL', 'grand-media') ?>:</label>
								<input type="text" readonly="readonly" name="url" class="form-control input-sm" value="<?php echo home_url(); ?>"/>
							</div>
							<div class="form-group">
								<label><?php _e('Site Title', 'grand-media') ?>:</label>
								<input type="text" readonly="readonly" name="title" class="form-control input-sm" value="<?php bloginfo('name'); ?>"/>
							</div>
							<div class="form-group">
								<label><?php _e('Site Description', 'grand-media') ?>:</label>
								<textarea readonly="readonly" rows="2" cols="10" name="description" class="form-control input-sm"><?php bloginfo('description'); ?></textarea>
							</div>
						</div>
					</div>
				</div>
				<script type="text/javascript">
					jQuery(function ($) {

						function gmedia_application(service){
							var post_data = {
								action: 'gmedia_application',
								service: service,
								data: $('#gm_application_data :input').serialize(),
								_wpnonce: $('#_wpnonce').val()
							};
							$.post(ajaxurl, post_data, function (data, textStatus, jqXHR) {
								console.log(data);
								if (data.error) {
									$('#gmedia-msg-panel').append(data.error);
								} else if(data.message) {
									$('#gmedia-msg-panel').append(data.message);
								}
								if(parseInt(data.mobile_app)){
									$('.gm_service_actions > .btn-group').removeClass('hidden');
									$('#app_activate').addClass('hidden');
								} else{
									$('.gm_service_actions > .btn-group').addClass('hidden');
									$('#app_activate').removeClass('hidden');
								}
							});
						}

						<?php if($mobile_app){ ?>
						gmedia_application('app_checkstatus');
						<?php } ?>

						$('.gm_service_actions button').on('click', function(){
							var service = $(this).attr('id');
							gmedia_application(service);
						});

					});

				</script>
			<?php } ?>
		</div>
	</form>
<?php
}