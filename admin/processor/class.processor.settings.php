<?php

/**
 * GmediaProcessor_Settings
 */
class GmediaProcessor_Settings extends GmediaProcessor {
	private static $me = null;

	public static function getMe() {
		if ( null === self::$me ) {
			self::$me = new GmediaProcessor_Settings();
		}

		return self::$me;
	}

	protected function processor() {
		global $gmCore, $gmGallery, $gmDB;

		if ( ! $gmCore->caps['gmedia_settings'] ) {
			wp_die( esc_html__( 'You are not allowed to change gmedia settings', 'grand-media' ) );
		}
		$lk_check = isset( $_POST['license-key-activate'] );
		if ( isset( $_POST['gmedia_settings_save'] ) ) {
			check_admin_referer( 'gmedia_settings', '_wpnonce_settings' );

			$set = $gmCore->_post( 'set', array() );

			if ( ! empty( $set['purchase_key'] ) && ( empty( $set['license_key'] ) || empty( $set['license_key2'] ) ) ) {
				$lk_check = true;
			}
			if ( empty( $set['purchase_key'] ) && ( ! empty( $set['license_key'] ) || ! empty( $set['license_key2'] ) ) ) {
				$set['license_name'] = '';
				$set['purchase_key'] = '';
				$set['license_key']  = '';
				$set['license_key2'] = '';
				$this->error[]       = esc_html__( 'License Key empty...', 'grand-media' );
			}

			$set['google_api_key'] = trim( $set['google_api_key'] );

			$flush_rewrite_rules = false;
			if ( empty( $set['endpoint'] ) ) {
				$set['endpoint'] = 'gmedia';
			}
			if (
				$set['endpoint'] !== $gmGallery->options['endpoint']
				|| $set['gmedia_post_slug'] !== $gmGallery->options['gmedia_post_slug']
				|| $set['gmedia_album_post_slug'] !== $gmGallery->options['gmedia_album_post_slug']
				|| $set['gmedia_gallery_post_slug'] !== $gmGallery->options['gmedia_gallery_post_slug']
				|| $set['gmedia_has_archive'] !== $gmGallery->options['gmedia_has_archive']
				|| $set['gmedia_album_has_archive'] !== $gmGallery->options['gmedia_album_has_archive']
				|| $set['gmedia_gallery_has_archive'] !== $gmGallery->options['gmedia_gallery_has_archive']
			) {
				$flush_rewrite_rules        = true;
				$set['flush_rewrite_rules'] = true;
			}

			foreach ( $set as $key => $val ) {
				$gmGallery->options[ $key ] = $val;
			}

			$capabilities = $gmCore->_post( 'capability', array() );
			if ( ! empty( $capabilities ) && current_user_can( 'manage_options' ) ) {
				global $wp_roles;
				$_roles = $wp_roles->roles;
				$_roles = array_keys( apply_filters( 'editable_roles', $_roles ) );
				$roles  = array_flip( $_roles );

				// upload cap.
				if ( $roles[ $capabilities['gmedia_upload'] ] < $roles[ $capabilities['gmedia_import'] ] ) {
					$capabilities['gmedia_import'] = $capabilities['gmedia_upload'];
				}
				// edit/delete cap.
				if ( $roles[ $capabilities['gmedia_edit_media'] ] < $roles[ $capabilities['gmedia_edit_others_media'] ] ) {
					$capabilities['gmedia_edit_others_media'] = $capabilities['gmedia_edit_media'];
				}
				if ( $roles[ $capabilities['gmedia_edit_media'] ] < $roles[ $capabilities['gmedia_delete_media'] ] ) {
					$capabilities['gmedia_delete_media'] = $capabilities['gmedia_edit_media'];
				}
				if ( $roles[ $capabilities['gmedia_delete_media'] ] < $roles[ $capabilities['gmedia_delete_others_media'] ] ) {
					$capabilities['gmedia_delete_others_media'] = $capabilities['gmedia_delete_media'];
				}
				if ( $roles[ $capabilities['gmedia_edit_others_media'] ] < $roles[ $capabilities['gmedia_delete_others_media'] ] ) {
					$capabilities['gmedia_delete_others_media'] = $capabilities['gmedia_edit_others_media'];
				}
				if ( $roles[ $capabilities['gmedia_show_others_media'] ] < $roles[ $capabilities['gmedia_edit_others_media'] ] ) {
					$capabilities['gmedia_edit_others_media'] = $capabilities['gmedia_show_others_media'];
				}
				if ( $roles[ $capabilities['gmedia_show_others_media'] ] < $roles[ $capabilities['gmedia_delete_others_media'] ] ) {
					$capabilities['gmedia_delete_others_media'] = $capabilities['gmedia_show_others_media'];
				}
				// terms cap.
				if ( $roles[ $capabilities['gmedia_terms'] ] < $roles[ $capabilities['gmedia_album_manage'] ] ) {
					$capabilities['gmedia_album_manage'] = $capabilities['gmedia_terms'];
				}
				if ( $roles[ $capabilities['gmedia_terms'] ] < $roles[ $capabilities['gmedia_category_manage'] ] ) {
					$capabilities['gmedia_category_manage'] = $capabilities['gmedia_terms'];
				}
				if ( $roles[ $capabilities['gmedia_terms'] ] < $roles[ $capabilities['gmedia_tag_manage'] ] ) {
					$capabilities['gmedia_tag_manage'] = $capabilities['gmedia_terms'];
				}
				if ( $roles[ $capabilities['gmedia_terms'] ] < $roles[ $capabilities['gmedia_terms_delete'] ] ) {
					$capabilities['gmedia_terms_delete'] = $capabilities['gmedia_terms'];
				} else {
					$rolekey = max( $roles[ $capabilities['gmedia_album_manage'] ], $roles[ $capabilities['gmedia_tag_manage'] ] );
					$role    = $_roles[ $rolekey ];
					if ( $role < $roles[ $capabilities['gmedia_terms_delete'] ] ) {
						$capabilities['gmedia_terms_delete'] = $role;
					}
				}

				foreach ( $capabilities as $key => $val ) {
					$gmDB->set_capability( $val, $key );
				}
			}

			if ( isset( $set['delete_originals'] ) && (int) $set['delete_originals'] ) {
				//if (($handle = opendir($gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_original']))) {
				//    while (false !== ($file = readdir($handle))) {
				//        // do something with the file
				//        // note that '.' and '..' is returned even
				//        @unlink($file);
				//    }
				//    closedir($handle);
				//}
				$files = glob( $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['image_original'] . '/*', GLOB_NOSORT );
				if ( ! empty( $files ) ) {
					foreach ( $files as $file ) {
						@unlink( $file );
					}
				}
			}

			update_option( 'gmediaOptions', $gmGallery->options );
			if ( isset( $_POST['GmediaHashID_salt'] ) ) {
				update_option( 'GmediaHashID_salt', (string) $gmCore->_post( 'GmediaHashID_salt', '' ) );
			}
			gmedia_delete_transients( 'gm_cache' );
			if ( $flush_rewrite_rules ) {
				flush_rewrite_rules( false );
			}
			$this->msg[] .= esc_html__( 'Settings saved', 'grand-media' );
		}

		if ( $lk_check ) {
			check_admin_referer( 'gmedia_settings', '_wpnonce_settings' );
			$license_key = $gmCore->_post( 'set' );
			if ( ! empty( $license_key['purchase_key'] ) ) {
				global $wp_version;
				$gmedia_ua = "WordPress/{$wp_version} | ";
				$gmedia_ua .= 'Gmedia/' . constant( 'GMEDIA_VERSION' );

				$response = wp_remote_post(
					'https://codeasily.com/rest/gmedia-key.php',
					array(
						'body'        => array( 'key' => $license_key['purchase_key'], 'site' => site_url() ),
						'headers'     => array(
							'Content-Type' => 'application/x-www-form-urlencoded; ' . 'charset=' . get_option( 'blog_charset' ),
							'Host'         => 'codeasily.com',
							'User-Agent'   => $gmedia_ua,
						),
						'httpversion' => '1.0',
						'timeout'     => 45,
					)
				);

				if ( is_wp_error( $response ) ) {
					$this->error[] = $response->get_error_message();
					$this->error[] = esc_html__( 'Use Help Screen (top right button) for more info', 'grand-media' );
				} else {
					$result = json_decode( $response['body'] );
					if ( isset( $result->error ) ) {
						if ( $result && 200 === $result->error->code ) {
							$gmGallery->options['license_name'] = $result->content;
							$gmGallery->options['purchase_key'] = $result->dkey;
							$gmGallery->options['license_key']  = $result->key;
							$gmGallery->options['license_key2'] = $result->key2;
							// translators: link tag.
							$this->msg[] = sprintf( esc_html__( 'License Key activated successfully. You can see all activated websites with this key on your account page %s', 'grand-media' ), '<a href="https://codeasily.com/my-account/" target="_blank">https://codeasily.com/my-account/</a>' );
						} else {
							$gmGallery->options['license_name'] = '';
							$gmGallery->options['purchase_key'] = '';
							$gmGallery->options['license_key']  = '';
							$gmGallery->options['license_key2'] = '';
							$this->error[]                      = esc_html( __( 'Error', 'grand-media' ) . ': ' . $result->error->message );
						}
						update_option( 'gmediaOptions', $gmGallery->options );
					} else {
						$this->error[] = esc_html__( 'Something went wrong.. Try again later or use Help Screen (top right button) for more info', 'grand-media' );
					}
				}
			} else {
				$this->error[] = esc_html__( 'Empty License Key', 'grand-media' );
			}
		}

		if ( isset( $_POST['gmedia_settings_reset'] ) ) {
			check_admin_referer( 'gmedia_settings', '_wpnonce_settings' );
			include_once GMEDIA_ABSPATH . 'config/setup.php';
			$_temp_options      = $gmGallery->options;
			$gmGallery->options = gmedia_default_options();
			// don't reset license.
			$gmGallery->options['license_name'] = $_temp_options['license_name'];
			$gmGallery->options['purchase_key'] = $_temp_options['purchase_key'];
			$gmGallery->options['license_key']  = $_temp_options['license_key'];
			$gmGallery->options['license_key2'] = $_temp_options['license_key2'];
			// don't reset mobile app.
			$gmGallery->options['site_ID']    = $_temp_options['site_ID'];
			$gmGallery->options['mobile_app'] = (int) $_temp_options['mobile_app'];
			if ( $gmGallery->options['mobile_app'] && isset( $_temp_options['gmedia_service'] ) ) {
				$gmGallery->options['gmedia_service'] = $_temp_options['gmedia_service'];
			}
			delete_metadata( 'user', 0, 'gm_screen_options', '', true );
			update_option( 'gmediaOptions', $gmGallery->options );

			if ( $gmCore->_post( 'capability' ) && current_user_can( 'manage_options' ) ) {
				$capabilities = $gmCore->plugin_capabilities();
				$capabilities = apply_filters( 'gmedia_capabilities', $capabilities );
				//$role = get_role('administrator');
				foreach ( $capabilities as $cap ) {
					$gmDB->set_capability( 'administrator', $cap );
				}
			}
			gmedia_delete_transients( 'gm_cache' );
			$this->msg[] .= esc_html__( 'All settings set to default', 'grand-media' );
		}

	}
}

global $gmProcessorSettings;
$gmProcessorSettings = GmediaProcessor_Settings::getMe();
