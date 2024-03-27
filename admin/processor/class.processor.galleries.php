<?php

/**
 * GmediaProcessor_Galleries
 */
class GmediaProcessor_Galleries extends GmediaProcessor {

	public static $cookie_key = false;

	private static $me = null;

	public $taxonomy;
	public $selected_items = array();
	public $query_args     = array();

	/**
	 * GmediaProcessor_Library constructor.
	 */
	public function __construct() {
		$this->taxonomy       = 'gmedia_gallery';
		self::$cookie_key     = 'gmedia_terms:gallery';
		$this->selected_items = parent::selected_items( self::$cookie_key );

		parent::__construct();
	}

	public static function getMe() {
		if ( null === self::$me ) {
			self::$me = new GmediaProcessor_Galleries();
		}

		return self::$me;
	}

	protected function processor() {
		global $user_ID, $gmCore, $gmDB, $gmGallery;

		if ( ! $gmCore->caps['gmedia_library'] ) {
			wp_die( esc_html__( 'You are not allowed to be here', 'grand-media' ) );
		}

		if ( ! $gmCore->caps['gmedia_gallery_manage'] ) {
			wp_die( esc_html__( 'You are not allowed to manage gmedia galleries', 'grand-media' ) );
		}

		include_once GMEDIA_ABSPATH . 'admin/pages/galleries/functions.php';

		$this->query_args = $this->query_args();

		if ( isset( $_POST['select_author'] ) ) {
			$authors  = $gmCore->_post( 'author_ids' );
			$location = $gmCore->get_admin_url( array( 'author' => (int) $authors ) );
			wp_safe_redirect( $location );
			exit;
		}
		if ( isset( $_POST['gmedia_gallery_module'] ) && '' !== $_POST['gmedia_gallery_module'] ) {
			check_admin_referer( 'gmedia_gallery_module', '_wpnonce_gallery_module' );
			$taxonomy       = 'gmedia_gallery';
			$ids            = $gmCore->_get( 'ids', 'selected' );
			$selected_items = ( 'selected' === $ids ) ? $this->selected_items : wp_parse_id_list( $ids );
			if ( ! $gmCore->caps['gmedia_delete_others_media'] ) {
				$_selected_items = $gmDB->get_terms( $taxonomy, array( 'fields' => 'ids', 'global' => $user_ID, 'include' => $selected_items ) );
				if ( count( $_selected_items ) < count( $selected_items ) ) {
					$this->error[] = esc_html__( 'You are not allowed to delete others media', 'grand-media' );
				}
				$selected_items = $_selected_items;
			}
			$count = count( $selected_items );
			if ( $count ) {
				$preset       = $gmCore->getModulePreset( $gmCore->_post( 'gmedia_gallery_module' ) );
				$gallery_meta = array(
					'_edited'   => gmdate( 'Y-m-d H:i:s' ),
					'_module'   => $preset['module'],
					'_settings' => $preset['settings'],
				);
				foreach ( $selected_items as $term_id ) {
					foreach ( $gallery_meta as $key => $value ) {
						$gmDB->update_metadata( 'gmedia_term', $term_id, $key, $value );
					}
				}
				// translators: 1 - preset name; 2 - module name; 3 - number.
				$this->msg[] = sprintf( esc_html__( 'Preset "%1$s" of module "%2$s" was applied to %3$d galleries', 'grand-media' ), esc_html( $preset['name'] ), esc_html( $preset['module'] ), (int) $count );
			}
		}
		if ( isset( $_POST['gmedia_gallery_save'] ) ) {
			check_admin_referer( 'GmediaGallery' );

			if ( isset( $_POST['preview_bgcolor'] ) ) {
				$gmGallery->options['preview_bgcolor'] = $gmCore->sanitize_hex_color( $gmCore->_post( 'preview_bgcolor' ), 'ffffff' );
				update_option( 'gmediaOptions', $gmGallery->options );
			}

			$edit_gallery = (int) $gmCore->_get( 'edit_term' );
			do {
				$term = $gmCore->_post( 'term' );
				if ( ( (int) $term['global'] !== $user_ID ) && ! $gmCore->caps['gmedia_edit_others_media'] ) {
					$this->error[] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
					break;
				}
				$term['name'] = trim( $term['name'] );
				if ( empty( $term['name'] ) ) {
					$this->error[] = esc_html__( 'Gallery Name is not specified', 'grand-media' );
					break;
				}
				if ( $gmCore->is_digit( $term['name'] ) ) {
					$this->error[] = esc_html__( "Gallery name can't be only digits", 'grand-media' );
					break;
				}
				if ( empty( $term['module'] ) ) {
					$this->error[] = esc_html__( 'Something goes wrong... Choose module, please', 'grand-media' );
					break;
				}
				$taxonomy = 'gmedia_gallery';
				if ( $edit_gallery && ! $gmDB->term_exists( $edit_gallery ) ) {
					$this->error[] = esc_html__( 'A term with the id provided does not exists', 'grand-media' );
					$edit_gallery  = false;
				}
				$term_id = $gmDB->term_exists( $term['name'], $taxonomy, $term['global'] );
				if ( $term_id ) {
					if ( $term_id !== $edit_gallery ) {
						$this->error[] = esc_html__( 'A term with the name provided already exists', 'grand-media' );
						break;
					}
				}
				$meta = $gmCore->_post( 'meta' );
				if ( $meta ) {
					$term = array_merge_recursive( array( 'meta' => $meta ), $term );
				}
				if ( $edit_gallery ) {
					$term_id = $gmDB->update_term( $edit_gallery, $term );
				} else {
					$term_id = $gmDB->insert_term( $term['name'], $taxonomy, $term );
				}
				if ( is_wp_error( $term_id ) ) {
					$this->error[] = $term_id->get_error_message();
					break;
				}

				$module_settings = $gmCore->_post( 'module', array() );
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
				foreach ( $module_settings as &$setting ) {
					if ( is_string( $setting ) && 7 === strlen( $setting ) && '#' === $setting[0] ) {
						$setting = ltrim( $setting, '#' );
					}
				}
				$module_settings = $gmCore->array_replace_recursive( $default_options, $module_settings );
				wp_parse_str( $term['query'], $_query );
				$gallery_meta = array(
					'_edited'   => gmdate( 'Y-m-d H:i:s' ),
					'_query'    => $_query,
					'_module'   => $term['module'],
					'_settings' => array( $term['module'] => $module_settings ),
				);
				foreach ( $gallery_meta as $key => $value ) {
					$gmDB->update_metadata( 'gmedia_term', $term_id, $key, $value );
				}
				if ( $edit_gallery ) {
					// translators: number.
					$this->msg[] = sprintf( esc_html__( 'Gallery #%d successfully saved', 'grand-media' ), (int) $term_id );
				} else {
					$location = add_query_arg( array( 'edit_term' => $term_id, 'message' => 'save' ), $this->url );
					set_transient( 'gmedia_new_gallery_id', $term_id, 60 );
					wp_safe_redirect( $location );
					exit;
				}
			} while ( 0 );
		}
		$term_id = $gmCore->_get( 'edit_term' );
		if ( ( 'save' === $gmCore->_get( 'message' ) ) && $term_id ) {
			$gmedia_new_gallery_id = get_transient( 'gmedia_new_gallery_id' );
			if ( false !== $gmedia_new_gallery_id ) {
				delete_transient( 'gmedia_new_gallery_id' );
				// translators: number.
				$this->msg[] = sprintf( esc_html__( 'Gallery #%d successfully saved', 'grand-media' ), (int) $term_id );
			}
		}

		if ( isset( $_POST['module_preset_restore_original'] ) ) {
			$preset_id = intval( $gmCore->_post( 'preset_default', 0 ) );
			$gmDB->delete_term( $preset_id );
			$this->msg[] = esc_html__( 'Original module settings restored. Click "Reset to default" button to save original module settings for gallery', 'grand-media' );
		}

		if ( isset( $_POST['gmedia_gallery_reset'] ) ) {
			check_admin_referer( 'GmediaGallery' );
			$edit_gallery = (int) $gmCore->_get( 'edit_term' );
			do {
				if ( ! $gmDB->term_exists( $edit_gallery ) ) {
					$this->error[] = esc_html__( 'A term with the id provided does not exists', 'grand-media' );
					break;
				}
				if ( ! $gmCore->caps['gmedia_edit_others_media'] ) {
					$term = $gmDB->get_term( $edit_gallery );
					if ( $term->global !== $user_ID ) {
						$this->error[] = esc_html__( 'You are not allowed to edit others media', 'grand-media' );
						break;
					}
				}
				$gallery_settings = $gmDB->get_metadata( 'gmedia_term', $edit_gallery, '_settings', true );
				reset( $gallery_settings );
				$gallery_module = key( $gallery_settings );
				$module_path    = $gmCore->get_module_path( $gallery_module );
				/**
				 * @var $default_options
				 */
				if ( is_file( $module_path['path'] . '/settings.php' ) ) {
					/** @noinspection PhpIncludeInspection */
					include $module_path['path'] . '/settings.php';
					$preset = $gmDB->get_term( '[' . $gallery_module . ']', array( 'taxonomy' => 'gmedia_module', 'global' => '0' ) );
					if ( $preset ) {
						$default_preset  = maybe_unserialize( $preset->description );
						$default_options = $gmCore->array_replace_recursive( $default_options, $default_preset );
					}
					$preset = $gmDB->get_term( '[' . $gallery_module . ']', 'gmedia_module' );
					if ( $preset ) {
						$default_preset  = maybe_unserialize( $preset->description );
						$default_options = $gmCore->array_replace_recursive( $default_options, $default_preset );
					}
				} else {
					// translators: module name.
					$this->error[] = sprintf( esc_html__( 'Can\'t load data from `%s` module' ), esc_html( $gallery_module ) );
					break;
				}

				$gallery_meta = array(
					'_edited'   => gmdate( 'Y-m-d H:i:s' ),
					'_settings' => array( $gallery_module => $default_options ),
				);
				foreach ( $gallery_meta as $key => $value ) {
					$gmDB->update_metadata( 'gmedia_term', $edit_gallery, $key, $value );
				}
				$this->msg[] = esc_html__( 'Gallery settings are reset', 'grand-media' );

			} while ( 0 );

		}

		if ( isset( $_POST['module_preset_save'] ) || isset( $_POST['module_preset_save_default'] ) || isset( $_POST['module_preset_save_global'] ) ) {
			check_admin_referer( 'GmediaGallery' );
			do {
				$term = $gmCore->_post( 'term' );
				if ( empty( $term['module'] ) ) {
					$this->error[] = esc_html__( 'Something goes wrong... Choose module, please', 'grand-media' );
					break;
				}
				$module_settings = $gmCore->_post( 'module', array() );
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
				$module_settings = $gmCore->array_replace_recursive( $default_options, $module_settings );

				$preset_name = $gmCore->_post( 'module_preset_name', '' );
				if ( isset( $_POST['module_preset_save_default'] ) || isset( $_POST['module_preset_save_global'] ) ) {
					$preset_name = '[' . $term['module'] . ']';
				} else {
					$preset_name = trim( $preset_name );
					if ( empty( $preset_name ) ) {
						$preset_name = current_time( 'mysql' );
					}
					$preset_name = '[' . $term['module'] . '] ' . $preset_name;
				}
				$args                = array();
				$args['description'] = $module_settings;
				$args['status']      = $term['module'];
				$args['global']      = $user_ID;
				if ( current_user_can( 'manage_options' ) && isset( $_POST['module_preset_save_global'] ) ) {
					$args['global'] = '0';
				}

				$taxonomy = 'gmedia_module';
				$term_id  = $gmDB->term_exists( $preset_name, $taxonomy, $args['global'] );
				if ( $term_id ) {
					$term_id = $gmDB->update_term( $term_id, $args );
				} else {
					$term_id = $gmDB->insert_term( $preset_name, $taxonomy, $args );
				}
				if ( is_wp_error( $term_id ) ) {
					$this->error[] = $term_id->get_error_message();
					break;
				} else {
					// translators: preset name.
					$this->msg[] = sprintf( esc_html__( 'Preset `%s` successfully saved', 'grand-media' ), esc_html( $preset_name ) );
				}
			} while ( 0 );
		}

		$do_gmedia_terms = $gmCore->_get( 'do_gmedia_terms' );
		if ( 'delete' === $do_gmedia_terms ) {
			check_admin_referer( 'gmedia_delete', '_wpnonce_delete' );
			$taxonomy       = 'gmedia_gallery';
			$ids            = $gmCore->_get( 'ids', 'selected' );
			$selected_items = ( 'selected' === $ids ) ? $this->selected_items : wp_parse_id_list( $ids );
			if ( ! $gmCore->caps['gmedia_delete_others_media'] ) {
				$_selected_items = $gmDB->get_terms( $taxonomy, array( 'fields' => 'ids', 'global' => $user_ID, 'include' => $selected_items ) );
				if ( count( $_selected_items ) < count( $selected_items ) ) {
					$this->error[] = esc_html__( 'You are not allowed to delete others media', 'grand-media' );
				}
				$selected_items = $_selected_items;
			}
			$count = count( $selected_items );
			if ( $count ) {
				foreach ( $selected_items as $item ) {
					$delete = $gmDB->delete_term( $item );
					if ( ! $delete ) {
						// translators: number.
						$this->error[] = sprintf( esc_html__( 'Error while delete gallery #%d', 'grand-media' ), (int) $item );
						$count --;
					} elseif ( is_wp_error( $delete ) ) {
						$this->error[] = $delete->get_error_message();
						$count --;
					}
				}
				if ( $count ) {
					// translators: number.
					$this->msg[] = sprintf( esc_html__( '%d item(s) deleted successfully', 'grand-media' ), (int) $count );
				}
				setcookie( self::$cookie_key, '', time() - 3600 );
				unset( $_COOKIE[ self::$cookie_key ] );
				$this->selected_items = array();
			}
			if ( ! empty( $this->msg ) ) {
				set_transient( 'gmedia_action_msg', $this->msg, 30 );
			}
			if ( ! empty( $this->error ) ) {
				set_transient( 'gmedia_action_error', $this->error, 30 );
			}
		}
		if ( $do_gmedia_terms ) {
			$_wpnonce = array();
			foreach ( $_GET as $key => $value ) {
				if ( strpos( $key, '_wpnonce' ) !== false ) {
					$_wpnonce[ $key ] = $value;
				}
			}
			$remove_args = array_merge( array( 'do_gmedia_terms', 'ids' ), $_wpnonce );
			$location    = remove_query_arg( $remove_args );
			$location    = add_query_arg( 'did_gmedia_terms', $do_gmedia_terms, $location );
			wp_safe_redirect( $location );
			exit;
		}
		if ( $gmCore->_get( 'did_gmedia_terms' ) ) {
			$msg = get_transient( 'gmedia_action_msg' );
			if ( $msg ) {
				delete_transient( 'gmedia_action_msg' );
				$this->msg = $msg;
			}
			$error = get_transient( 'gmedia_action_error' );
			if ( $error ) {
				delete_transient( 'gmedia_action_error' );
				$this->error = $error;
			}
		}
	}

	/**
	 * @return array
	 */
	public function query_args() {
		global $gmCore;

		$args['status']  = $gmCore->_get( 'status' );
		$args['page']    = $gmCore->_get( 'pager', 1 );
		$args['number']  = $gmCore->_get( 'per_page', $this->user_options['per_page_gmedia_gallery'] );
		$args['offset']  = ( $args['page'] - 1 ) * $args['number'];
		$args['global']  = parent::filter_by_author( $gmCore->_get( 'author' ) );
		$args['include'] = $gmCore->_get( 'include' );
		$args['search']  = $gmCore->_get( 's' );
		$args['orderby'] = $gmCore->_get( 'orderby', $this->user_options['orderby_gmedia_gallery'] );
		$args['order']   = $gmCore->_get( 'order', $this->user_options['sortorder_gmedia_gallery'] );

		if ( $args['search'] && ( '#' === substr( $args['search'], 0, 1 ) ) ) {
			$args['include'] = substr( $args['search'], 1 );
			$args['search']  = false;
		}

		if ( ( 'selected' === $gmCore->_req( 'filter' ) ) && ! empty( $this->selected_items ) ) {
			$args['include'] = $this->selected_items;
			$args['orderby'] = $gmCore->_get( 'orderby', 'include' );
			$args['order']   = $gmCore->_get( 'order', 'ASC' );
		}

		$query_args               = apply_filters( 'gmedia_gallery_query_args', $args );
		$query_args['hide_empty'] = false;

		foreach ( $query_args as $key => $val ) {
			if ( empty( $val ) && ( '0' !== $val ) && ( 0 !== $val ) ) {
				unset( $query_args[ $key ] );
			}
		}

		return $query_args;
	}
}

global $gmProcessorGalleries;
$gmProcessorGalleries = GmediaProcessor_Galleries::getMe();
