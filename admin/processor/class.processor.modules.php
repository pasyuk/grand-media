<?php

/**
 * GmediaProcessor_Modules
 */
class GmediaProcessor_Modules extends GmediaProcessor {
	private static $me = null;

	public $modules = array();

	public static function getMe() {
		if ( null === self::$me ) {
			self::$me = new GmediaProcessor_Modules();
		}

		return self::$me;
	}

	protected function processor() {
		global $gmDB, $gmCore, $gmGallery, $user_ID;

		if ( ! $gmCore->caps['gmedia_gallery_manage'] ) {
			wp_die( esc_html__( 'You are not allowed to manage gmedia galleries', 'grand-media' ) );
		}
		if ( ! $gmCore->caps['gmedia_module_manage'] ) {
			wp_die( esc_html__( 'You are not allowed to manage gmedia modules', 'grand-media' ) );
		}

		include_once GMEDIA_ABSPATH . 'admin/pages/modules/functions.php';

		if ( isset( $_POST['module_preset_save'] ) || isset( $_POST['module_preset_save_default'] ) || isset( $_POST['module_preset_save_global'] ) ) {
			check_admin_referer( 'GmediaGallery' );

			if ( isset( $_POST['preview_bgcolor'] ) ) {
				$gmGallery->options['preview_bgcolor'] = $gmCore->sanitize_hex_color( $gmCore->_post( 'preview_bgcolor' ), 'ffffff' );
				update_option( 'gmediaOptions', $gmGallery->options );
			}

			$edit_preset = (int) $gmCore->_get( 'preset' );
			do {
				$term = $gmCore->_post( 'term' );

				if ( isset( $term['query'] ) ) {
					wp_parse_str( $term['query'], $_query );
					update_user_option( $user_ID, 'gmedia_preset_demo_query_args', $_query );
				}

				if ( ( (int) $term['global'] !== $user_ID ) && ! $gmCore->caps['gmedia_edit_others_media'] ) {
					$this->error[] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
					break;
				}

				if ( empty( $term['module'] ) ) {
					$this->error[] = esc_html__( 'Something goes wrong... Choose module, please', 'grand-media' );
					break;
				}

				$taxonomy = 'gmedia_module';
				if ( isset( $_POST['module_preset_save_default'] ) || isset( $_POST['module_preset_save_global'] ) ) {
					$term['name'] = '[' . $term['module'] . ']';
					if ( current_user_can( 'manage_options' ) && isset( $_POST['module_preset_save_global'] ) ) {
						$term['global'] = '0';
					}
					$edit_preset     = $gmDB->term_exists( $term['name'], $taxonomy, $term['global'] );
					$term['term_id'] = $edit_preset;
				} else {
					$term['name'] = trim( $term['name'] );
					if ( empty( $term['name'] ) ) {
						$term['name'] = current_time( 'mysql' );
					}
					if ( $gmCore->is_digit( $term['name'] ) ) {
						$this->error[] = esc_html__( "Preset name can't be only digits", 'grand-media' );
						break;
					}
					$term['name'] = '[' . $term['module'] . '] ' . $term['name'];

					if ( $edit_preset && ! $gmDB->term_exists( $edit_preset ) ) {
						$this->error[] = esc_html__( 'A term with the id provided does not exists', 'grand-media' );
						$edit_preset   = false;
					}
					$term_id = $gmDB->term_exists( $term['name'], $taxonomy, $term['global'] );
					if ( $term_id ) {
						if ( $term_id !== $edit_preset ) {
							$this->error[] = esc_html__( 'A term with the name provided already exists', 'grand-media' );
							break;
						}
					}
				}

				$module_settings = $gmCore->_post( 'module', array() );
				foreach ( $module_settings as &$setting ) {
					if ( is_string( $setting ) && 7 === strlen( $setting ) && '#' === $setting[0] ) {
						$setting = ltrim( $setting, '#' );
					}
				}
				$module_path     = $gmCore->get_module_path( $term['module'] );
				$default_options = array();
				if ( is_file( $module_path['path'] . '/settings.php' ) ) {
					/** @noinspection PhpIncludeInspection */
					include $module_path['path'] . '/settings.php';
				} else {
					// translators: module name.
					$this->error[] = sprintf( esc_html__( 'Can\'t load data from `%s` module' ), esc_html( $term['module'] ) );
					break;
				}
				$term['description'] = $gmCore->array_replace_recursive( $default_options, $module_settings );
				$term['status']      = $term['module'];

				if ( $edit_preset ) {
					$term_id = $gmDB->update_term( $edit_preset, $term );
				} else {
					$term_id = $gmDB->insert_term( $term['name'], $taxonomy, $term );
				}
				if ( is_wp_error( $term_id ) ) {
					$this->error[] = $term_id->get_error_message();
					break;
				}

				if ( $edit_preset ) {
					// translators: ID.
					$this->msg[] = sprintf( esc_html__( 'Preset #%d successfully saved', 'grand-media' ), $term_id );
				} else {
					$location = add_query_arg( array( 'preset' => $term_id, 'message' => 'save' ), $this->url );
					set_transient( 'gmedia_new_preset_id', $term_id, 60 );
					wp_safe_redirect( $location );
					exit;
				}
			} while ( 0 );
		}
		$term_id = $gmCore->_get( 'preset' );
		if ( ( 'save' === $gmCore->_get( 'message' ) ) && $term_id ) {
			$gmedia_new_preset_id = get_transient( 'gmedia_new_preset_id' );
			if ( false !== $gmedia_new_preset_id ) {
				gmedia_delete_transients( 'gm_cache' );
				delete_transient( 'gmedia_new_preset_id' );
				// translators: ID.
				$this->msg[] = sprintf( esc_html__( 'Preset #%d successfully saved', 'grand-media' ), $term_id );
			}
		}

		if ( isset( $_FILES['modulezip']['tmp_name'] ) ) {
			if ( ! empty( $_FILES['modulezip']['tmp_name'] ) && isset( $_FILES['modulezip']['name'] ) ) {
				check_admin_referer( 'gmedia_module', '_wpnonce_module' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You are not allowed to install module ZIP', 'grand-media' ) );
				}
				$to_folder = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/';
				if ( ! wp_mkdir_p( $to_folder ) ) {
					// translators: dirname.
					$this->error[] = sprintf( esc_html__( 'Unable to create directory %s. Is its parent directory writable by the server?', 'grand-media' ), esc_html( $to_folder ) );

					return;
				}
				if ( ! is_writable( $to_folder ) ) {
					@chmod( $to_folder, 0755 );
					if ( ! is_writable( $to_folder ) ) {
						//@unlink( $_FILES['modulezip']['tmp_name'] );
						// translators: dirname.
						$this->error[] = sprintf( esc_html__( 'Directory %s is not writable by the server.', 'grand-media' ), esc_html( $to_folder ) );

						return;
					}
				}
				$modulezip_name     = sanitize_text_field( wp_unslash( $_FILES['modulezip']['name'] ) );
				$modulezip_tmp_name = sanitize_text_field( wp_unslash( $_FILES['modulezip']['tmp_name'] ) );
				$filename           = wp_unique_filename( $to_folder, $modulezip_name );

				// Move the file to the modules dir.
				if ( false === @move_uploaded_file( $modulezip_tmp_name, $to_folder . $filename ) ) {
					// translators: path.
					$this->error[] = sprintf( esc_html__( 'The uploaded file could not be moved to %s', 'grand-media' ), esc_html( $to_folder . $filename ) );
				} else {
					global $wp_filesystem;
					// Is a filesystem accessor setup?
					if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						WP_Filesystem();
					}
					if ( ! is_object( $wp_filesystem ) ) {
						$result = new WP_Error( 'fs_unavailable', esc_html__( 'Could not access filesystem.', 'grand-media' ) );
					} elseif ( $wp_filesystem->errors->get_error_code() ) {
						$result = new WP_Error( 'fs_error', esc_html__( 'Filesystem error', 'grand-media' ), $wp_filesystem->errors );
					} else {
						$maybe_folder_dir = basename( $modulezip_name, '.zip' );
						$maybe_folder_dir = sanitize_file_name( $maybe_folder_dir );
						if ( $maybe_folder_dir && is_dir( $to_folder . $maybe_folder_dir ) ) {
							$gmCore->delete_folder( $to_folder . $maybe_folder_dir );
						}
						$result = unzip_file( $to_folder . $filename, $to_folder );
					}
					// Once extracted, delete the package.
					unlink( $to_folder . $filename );
					if ( is_wp_error( $result ) ) {
						$this->error[] = $result->get_error_message();
					} else {
						// translators: filename.
						$this->msg[] = sprintf( esc_html__( "The `%s` file unzipped to module's directory", 'grand-media' ), esc_html( $filename ) );
					}
				}
				gmedia_delete_transients( 'gm_cache' );
			} else {
				$this->error[] = esc_html__( 'No file specified', 'grand-media' );
			}
		}

		if ( isset( $_GET['delete_module'] ) ) {
			if ( $gmCore->_get( '_wpnonce_module_delete' ) ) {
				$mfold = preg_replace( '/[^a-z0-9_-]+/i', '_', $gmCore->_get( 'delete_module', '' ) );
				$mpath = "{$gmCore->upload['path']}/{$gmGallery->options['folder']['module']}/{$mfold}";
				if ( $mfold && file_exists( $mpath ) ) {
					check_admin_referer( 'gmedia_module_delete', '_wpnonce_module_delete' );
					$gmCore->delete_folder( $mpath );
					$location = remove_query_arg( array( '_wpnonce_module_delete' ) );
					// translators: path.
					set_transient( 'gmedia_module_deleted', sprintf( esc_html__( 'The `%s` module folder was deleted', 'grand-media' ), esc_html( $mpath ) ), 60 );
					wp_safe_redirect( $location );
				}
			} elseif ( false !== ( $message = get_transient( 'gmedia_module_deleted' ) ) ) {
				gmedia_delete_transients( 'gm_cache' );
				delete_transient( 'gmedia_module_deleted' );
				$this->msg[] = $message;
			}
		}

		$this->modules = get_gmedia_modules();
		wp_clear_scheduled_hook( 'gmedia_modules_update' );
		wp_schedule_event( time(), 'daily', 'gmedia_modules_update' );
		$gmCore->modules_update( $this->modules );
	}
}

global $gmProcessorModules;
$gmProcessorModules = GmediaProcessor_Modules::getMe();
