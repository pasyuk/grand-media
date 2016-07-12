<?php

/**
 * GmediaProcessor_Modules
 */
class GmediaProcessor_Modules extends GmediaProcessor{

	protected function processor() {
		global $gmDB, $gmCore, $gmGallery, $user_ID;

		if ( ! $gmCore->caps['gmedia_gallery_manage'] ) {
			wp_die( __( 'You are not allowed to manage gmedia galleries', 'grand-media' ) );
		}
		if ( ! $gmCore->caps['gmedia_module_manage'] ) {
			wp_die( __( 'You are not allowed to manage gmedia modules', 'grand-media' ) );
		}

		include_once( GMEDIA_ABSPATH . 'admin/pages/modules/functions.php' );

		if ( isset( $_POST['gmedia_preset_save'] ) || isset( $_POST['module_preset_save_default'] ) ) {
			check_admin_referer( 'GmediaGallery' );
			$edit_preset = (int) $gmCore->_get( 'preset' );
			do{
				$term = $gmCore->_post( 'term' );

				if ( isset( $term['query'] ) ) {
					wp_parse_str( $term['query'], $_query );
					update_user_option( $user_ID, 'gmedia_preset_demo_query_args', $_query );
				}

				if ( ( (int) $term['global'] != $user_ID ) && ! $gmCore->caps['gmedia_edit_others_media'] ) {
					$this->error[] = __( 'You are not allowed to edit others media', 'grand-media' );
					break;
				}

				$term['name'] = trim( $term['name'] );
				if ( empty( $term['name'] ) ) {
					$this->error[] = __( 'Preset Name is not specified', 'grand-media' );
					break;
				}
				if ( $gmCore->is_digit( $term['name'] ) ) {
					$this->error[] = __( "Preset name can't be only digits", 'grand-media' );
					break;
				}
				if ( empty( $term['module'] ) ) {
					$this->error[] = __( 'Something goes wrong... Choose module, please', 'grand-media' );
					break;
				}

				$taxonomy            = 'gmedia_module';
				if ( isset( $_POST['module_preset_save_default'] ) ) {
					$term['name'] = '[' . $term['module'] . ']';
					$edit_preset = $term['term_id'] = $gmDB->term_exists( $term['name'], $taxonomy, $term['global'] );
				} else {
					$term['name'] = trim( $term['name'] );
					if ( empty( $term['name'] ) ) {
						$this->error[] = __( 'Preset name is not specified', 'grand-media' );
						break;
					}
					$term['name'] = '[' . $term['module'] . '] ' . $term['name'];

					if ( $edit_preset && ! $gmDB->term_exists( $edit_preset ) ) {
						$this->error[] = __( 'A term with the id provided do not exists', 'grand-media' );
						$edit_preset   = false;
					}
					if ( ( $term_id = $gmDB->term_exists( $term['name'], $taxonomy, $term['global'] ) ) ) {
						if ( $term_id != $edit_preset ) {
							$this->error[] = __( 'A term with the name provided already exists', 'grand-media' );
							break;
						}
					}
				}


				$module_settings = $gmCore->_post( 'module', array() );
				$module_path     = $gmCore->get_module_path( $term['module'] );
				$default_options = array();
				if ( file_exists( $module_path['path'] . '/settings.php' ) ) {
					/** @noinspection PhpIncludeInspection */
					include( $module_path['path'] . '/settings.php' );
				} else {
					$this->error[] = sprintf( __( 'Can\'t load data from `%s` module' ), $term['module'] );
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
					$this->msg[] = sprintf( __( 'Preset #%d successfuly saved', 'grand-media' ), $term_id );
				} else {
					$location = add_query_arg( array( 'page' => $this->page, 'preset' => $term_id, 'message' => 'save' ), admin_url( 'admin.php' ) );
					set_transient( 'gmedia_new_preset_id', $term_id, 60 );
					wp_redirect( $location );
					exit;
				}
			} while( 0 );
		}
		if ( ( 'save' == $gmCore->_get( 'message' ) ) && ( $term_id = $gmCore->_get( 'preset' ) ) ) {
			$gmedia_new_preset_id = get_transient( 'gmedia_new_preset_id' );
			if ( false !== $gmedia_new_preset_id ) {
				delete_transient( 'gmedia_new_preset_id' );
				$this->msg[] = sprintf( __( 'Preset #%d successfuly saved', 'grand-media' ), $term_id );
			}
		}

		if ( isset( $_FILES['modulezip']['tmp_name'] ) ) {
			if ( ! empty( $_FILES['modulezip']['tmp_name'] ) ) {
				check_admin_referer( 'GmediaModule' );
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( __( 'You are not allowed to install module ZIP', 'grand-media' ) );
				}
				$to_folder = $gmCore->upload['path'] . '/' . $gmGallery->options['folder']['module'] . '/';
				if ( ! wp_mkdir_p( $to_folder ) ) {
					$this->error[] = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?', 'grand-media' ), $to_folder );

					return;
				}
				if ( ! is_writable( $to_folder ) ) {
					@chmod( $to_folder, 0755 );
					if ( ! is_writable( $to_folder ) ) {
						//@unlink( $_FILES['modulezip']['tmp_name'] );
						$this->error[] = sprintf( __( 'Directory %s is not writable by the server.', 'grand-media' ), $to_folder );

						return;
					}
				}
				$filename = wp_unique_filename( $to_folder, $_FILES['modulezip']['name'] );

				// Move the file to the modules dir
				if ( false === @move_uploaded_file( $_FILES['modulezip']['tmp_name'], $to_folder . $filename ) ) {
					$this->error[] = sprintf( __( 'The uploaded file could not be moved to %s', 'flag' ), $to_folder . $filename );
				} else {
					global $wp_filesystem;
					// Is a filesystem accessor setup?
					if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
						WP_Filesystem();
					}
					if ( ! is_object( $wp_filesystem ) ) {
						$result = new WP_Error( 'fs_unavailable', __( 'Could not access filesystem.', 'flag' ) );
					} elseif ( $wp_filesystem->errors->get_error_code() ) {
						$result = new WP_Error( 'fs_error', __( 'Filesystem error', 'flag' ), $wp_filesystem->errors );
					} else {
						$maybe_folder_dir = basename( $_FILES['modulezip']['name'], '.zip' );
						$maybe_folder_dir = sanitize_file_name( $maybe_folder_dir );
						if ( $maybe_folder_dir && is_dir( $to_folder . $maybe_folder_dir ) ) {
							$gmCore->delete_folder( $to_folder . $maybe_folder_dir );
						}
						$result = unzip_file( $to_folder . $filename, $to_folder );
					}
					// Once extracted, delete the package
					unlink( $to_folder . $filename );
					if ( is_wp_error( $result ) ) {
						$this->error[] = $result->get_error_message();
					} else {
						$this->msg[] = sprintf( __( "The `%s` file unzipped to module's directory", 'flag' ), $filename );
					}
				}
			} else {
				$this->error[] = __( 'No file specified', 'grand-media' );
			}
		}

		if ( isset( $_GET['delete_module'] ) ) {
			if ( $gmCore->_get( '_wpnonce' ) ) {
				$mfold = preg_replace( '/[^a-z0-9_-]+/i', '_', $_GET['delete_module'] );
				$mpath = "{$gmCore->upload['path']}/{$gmGallery->options['folder']['module']}/{$mfold}";
				if ( $mfold && file_exists( $mpath ) ) {
					check_admin_referer( 'gmedia_module_delete' );
					$gmCore->delete_folder( $mpath );
					$location = remove_query_arg( array( '_wpnonce' ) );
					set_transient( 'gmedia_module_deleted', sprintf( __( "The `%s` module folder was deleted", 'flag' ), $mpath ), 60 );
					wp_redirect( $location );
				}
			} elseif ( false !== ( $message = get_transient( 'gmedia_module_deleted' ) ) ) {
				delete_transient( 'gmedia_module_deleted' );
				$this->msg[] = $message;
			}
		}

	}

}

global $gmProcessor;
$gmProcessor = new GmediaProcessor_Modules();
