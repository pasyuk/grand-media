<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])){
    die('You are not allowed to call this page directly.');
}

/**
 * gmediaApp()
 * @return mixed content
 */
function gmediaApp(){
    global $gmCore, $gmGallery;

    if(false !== ($force_app_status = $gmCore->_get('force_app_status'))){
        $gm_options               = get_option('gmediaOptions');
        $gm_options['mobile_app'] = (int)$force_app_status;
        $gmGallery->options['mobile_app'] = $gm_options['mobile_app'];
        if(!$gm_options['site_ID']){
            $gm_options['site_ID'] = (int)$gmCore->_get('force_site_id');
            $gmGallery->options['site_ID'] = $gm_options['site_ID'];
        }
        update_option('gmediaOptions', $gm_options);
    }

    $alert = $gmCore->alert('danger', __('Your server is not accessable by iOS application', 'grand-media'));

    $site_ID          = (int)$gmGallery->options['site_ID'];
    $mobile_app       = (int)$gmGallery->options['mobile_app'];

    $current_user = wp_get_current_user();

    ?>
    <div class="panel panel-default" id="gm_application">
        <?php wp_nonce_field('GmediaService'); ?>
        <div class="panel-body" id="gmedia-service-msg-panel"><?php
            if(empty($_SERVER['HTTP_X_REAL_IP']) && ('127.0.0.1' == $_SERVER['REMOTE_ADDR'] || '::1' == $_SERVER['REMOTE_ADDR'])){
                echo $alert;
            } else{
                if(!$mobile_app || !$site_ID){
                    echo $alert;
                    ?>
                    <div class="notice updated gm-message">
                        <div class="gm-message-content">
                            <div class="gm-plugin-icon">
                                <img src="<?php echo plugins_url('/grand-media/admin/assets/img/icon-128x128.png') ?>" width="80" height="80">
                            </div>
                            <?php printf( __('<p>Hey %s,<br>You should allow some data about your <b>Gmedia Gallery</b> to be sent to <a href="https://codeasily.com/" target="_blank" tabindex="1">codeasily.com</a> in order to use iOS application.
                        <br />These data required if you want to use Gmedia iOS application on your iPhone.</p>', 'grand-media'), $current_user->display_name ); ?>
                        </div>
                        <div class="gm-message-actions">
                            <span class="spinner" style="float: none;"></span>
                            <button class="button button-primary gm_service_action" data-action="allow" data-nonce="<?php echo wp_create_nonce('GmediaService'); ?>"><?php _e('Allow &amp; Continue', 'grand-media'); ?></button>
                        </div>
                        <div class="gm-message-plus gm-closed">
                            <a class="gm-mp-trigger" href="#" onclick="jQuery('.gm-message-plus').toggleClass('gm-closed gm-opened'); return false;"><?php _e('What permissions are being granted?', 'grand-media'); ?></a>
                            <ul>
                                <li>
                                    <i class="dashicons dashicons-admin-users"></i>

                                    <div>
                                        <span><?php _e('Your Profile Overview', 'grand-media'); ?></span>

                                        <p><?php _e('Name and email address', 'grand-media'); ?></p>
                                    </div>
                                </li>
                                <li>
                                    <i class="dashicons dashicons-admin-settings"></i>

                                    <div>
                                        <span><?php _e('Your Site Overview', 'grand-media'); ?></span>

                                        <p><?php _e('Site URL, WP version, PHP version, active theme &amp; plugins', 'grand-media'); ?></p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php
                }
            }

        ?></div>
        <div class="panel-body" id="gm_application_data">
            <?php if(current_user_can('manage_options')){ ?>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-6">
                            <p><?php echo 'Server address: ' . $_SERVER['SERVER_ADDR'];
                            echo '<br>Remote address: ' . $_SERVER['REMOTE_ADDR'];
                            echo '<br>HTTP X Real IP: ' . ( isset($_SERVER['HTTP_X_REAL_IP'])? $_SERVER['HTTP_X_REAL_IP'] : '' );
                            ?></p>
                            <div class="gmapp-description">
                                <div style="text-align:center; margin-bottom:30px;">
                                    <a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="vertical-align:middle; max-width:100%; margin:0 30px; max-height:88px;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/icon-128x128.png" alt=""/></a>
                                    <a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="vertical-align:middle; max-width:100%; margin:0 30px;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/appstore_button.png"/></a>
                                </div>

                                <p><?php _e('You are using one of the best plugins to create media library as well as your personal cloud storage on your WordPress website. You have chosen <strong><a href="https://wordpress.org/plugins/grand-media/" target="_blank">Gmedia Gallery Plugin</a></strong> and this choice gives you great opportunities to manage and organise your media library.', 'grand-media'); ?></p>
                                <p><?php _e('We are happy to offer you a simple way to access your photos and audios by means of your iOS devices: at a few taps and you will be able to create great photo gallery and share it with your friends, readers and subscribers.', 'grand-media'); ?></p>

                                <p class="text-center"><img style="max-width:90%;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/slide1.jpg" alt=""/></p>

                                <div class="text-left" style="padding-top:40%;">
                                    <div style="margin-right:20%">
                                        <h3><?php _e('DISCOVER and SHARE', 'grand-media'); ?></h3>
                                        <p><?php _e('Search, learn, open new horizons, share! It is just as easy as a piece of cake! Your photos will be seen by your friends, relatives and others.', 'grand-media'); ?></p>
                                    </div>
                                    <p><img style="max-width:90%;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/slide3.jpg" alt=""/></p>
                                </div>
                                <div class="text-left" style="padding-top:40%;">
                                    <div style="margin-right:20%">
                                        <h3><?php _e('PRIVATE CONTENT', 'grand-media'); ?></h3>
                                        <p><?php _e('If you are one of subscribers, contributors, authors, editors or administrators, use your login and password to get an access to the private content.', 'grand-media'); ?></p>
                                    </div>
                                    <p><img style="max-width:90%;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/slide5.jpg" alt=""/></p>
                                </div>

                                <div class="well well-lg text-center" style="margin-top:40%; padding-top:50px;">
                                <p><?php _e('Download Gmedia iOS application from the App Store to manage your Gmedia&nbsp;Library from iPhone.', 'grand-media'); ?></p>
                                <div>
                                    <a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="vertical-align:middle; max-width:100%; margin:30px;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/appstore_button.png"/></a>
                                    <a target="_blank" href="https://itunes.apple.com/ua/app/gmedia/id947515626?mt=8"><img style="vertical-align:middle; max-width:100%; margin:30px; max-height:88px;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/icon-128x128.png" alt=""/></a>
                                </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="well-lg well">
                                <p><?php _e('Below you can see information about your website that will be used by GmediaService and iOS application, so you\'ll be able to manage your Gmedia Library with your smartphone and other people can find and view your public collections.', 'grand-media'); ?></p>
                                <div class="form-group">
                                    <label><?php _e('Email', 'grand-media') ?>:</label>
                                    <input type="text" name="site_email" class="form-control input-sm" value="<?php esc_attr_e(get_option('admin_email')); ?>" readonly/>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Site URL', 'grand-media') ?>:</label>
                                    <input type="text" name="site_url" class="form-control input-sm" value="<?php echo home_url(); ?>" readonly/>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Site Title', 'grand-media') ?>:</label>
                                    <input type="text" name="site_title" class="form-control input-sm" value="<?php esc_attr_e(get_bloginfo('name')); ?>" readonly/>
                                </div>
                                <div class="form-group">
                                    <label><?php _e('Site Description', 'grand-media') ?>:</label>
                                    <textarea rows="2" cols="10" name="site_description" class="form-control input-sm" readonly><?php esc_attr_e(get_bloginfo('description')); ?></textarea>
                                </div>
                            </div>

                            <div class="gmapp-description">
                                <div class="text-right" style="padding-top:35%;">
                                    <div style="margin-left:20%">
                                        <h3><?php _e('FIND and ADD SITE it’s SIMPLY', 'grand-media'); ?></h3>
                                        <p><?php _e('Just a few touches and our smart search bar will let you find and add your website, your friend’s website or a famous blogger’s site to your favourites list.', 'grand-media'); ?></p>
                                    </div>
                                    <p><img style="max-width:90%;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/slide2.jpg" alt=""/></p>
                                </div>

                                <div class="text-right" style="padding-top:35%;">
                                    <div style="margin-left:20%">
                                        <h3><?php _e('MP3', 'grand-media'); ?></h3>
                                        <p><?php _e('Take your favourite music track with you on a trip or create a playlist to travel with it! It is so simple with Gmedia. Share your energy and positive mood with your friends!', 'grand-media'); ?></p>
                                    </div>
                                    <p><img style="max-width:90%;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/slide4.jpg" alt=""/></p>
                                </div>

                                <div class="text-right" style="padding-top:35%;">
                                    <div style="margin-left:20%">
                                        <h3><?php _e('GMEDIA LIBRARY', 'grand-media'); ?></h3>
                                        <p><?php _e('If you are one of subscribers, contributors,authors, editors or administrators, use your login and password to get an access to private content. If your type of users has an access to Gmedia Library, you will be able to create photo collections and download pictures just from iPhone, using wide functional opportunities of our app and plugin.', 'grand-media'); ?></p>
                                    </div>
                                    <p><img style="max-width:90%;" src="<?php echo $gmCore->gmedia_url; ?>/admin/assets/img/slide6.jpg" alt=""/></p>
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