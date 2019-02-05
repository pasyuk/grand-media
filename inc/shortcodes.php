<?php
if ( preg_match( '#' . basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/** *********************** **/
/** Shortcodes Declarations **/
/** *********************** **/
add_shortcode( 'gmedia', 'gmedia_shortcode' );
add_shortcode( 'gm', 'gmedia_shortcode' );

//add_filter('the_content', 'do_shortcode');
add_filter( 'the_content', 'get_gmedia_unformatted_shortcode_blocks', 4 );

/** ******************************* **/
/** Shortcodes Functions and Markup **/
/** ******************************* **/
$gmedia_shortcode_instance = array();

/**
 * @param        $atts
 * @param string $shortcode_post_content
 *
 * @return string
 */
function gmedia_shortcode( $atts, $shortcode_post_content = '' ) {
	global $gmDB, $gmGallery, $gmCore;
	global $gmedia_shortcode_instance, $gmedia_shortcode_ids;

	$shortcode_raw  = ( isset( $atts['_raw'] ) && '1' === $atts['_raw'] );
	$shortcode_copy = isset( $atts['_copy'] ) ? (int) $atts['_copy'] : null;
	unset( $atts['_raw'], $atts['_copy'] );

	$atts_hash = md5( build_query( $atts ) );
	if ( ! isset( $atts['id'] ) ) {
		if ( ! empty( $atts['album'] ) ) {
			$atts['id'] = $atts['album'];
			unset( $atts['album'] );
		} elseif ( ! empty( $atts['category'] ) ) {
			$atts['id'] = $atts['category'];
			unset( $atts['category'] );
		} elseif ( ! empty( $atts['tag'] ) ) {
			$atts['id'] = $atts['tag'];
			unset( $atts['tag'] );
		}
	}

	if ( isset( $gmedia_shortcode_instance[ $atts_hash ] ) ) {
		$out = $gmedia_shortcode_instance[ $atts_hash ]['shortcode'];
		++ $gmedia_shortcode_instance[ $atts_hash ]['copy'];
		if ( ! $shortcode_raw ) {
			$sc_id   = $gmedia_shortcode_instance[ $atts_hash ]['id'];
			$sc_copy = ( null === $shortcode_copy ) ? $gmedia_shortcode_instance[ $atts_hash ]['copy'] : $shortcode_copy;
			if ( $sc_copy ) {
				$out = str_replace( $sc_id, "{$sc_id}_{$sc_copy}", $out );
			}
		}

		return $out;
	} else {
		$gmedia_shortcode_instance[ $atts_hash ] = array(
			'shortcode' => '',
			'id'        => '',
			'copy'      => 0,
		);
	}
	$shortcode_raw    = ( isset( $gmGallery->options['shortcode_raw'] ) && '1' === $gmGallery->options['shortcode_raw'] );
	$cache_expiration = isset( $gmGallery->options['cache_expiration'] ) ? (int) $gmGallery->options['cache_expiration'] * HOUR_IN_SECONDS : 24 * HOUR_IN_SECONDS;

	$query = array();
	if ( ! empty( $atts['query'] ) ) {
		if ( is_string( $atts['query'] ) ) {
			$atts['query'] = html_entity_decode( $atts['query'] );
		}
		$query = wp_parse_args( $atts['query'], array() );
	}

	$atts_module = ! empty( $atts['module'] );
	$_module     = $atts_module ? $atts['module'] : $gmGallery->options['default_gmedia_module'];
	$preset      = $gmCore->getModulePreset( $_module );
	$_module     = $preset['module'];
	$settings    = $preset['settings'];

	$id     = isset( $atts['id'] ) ? (int) $atts['id'] : 0;
	$userid = get_current_user_id();
	if ( $id && ( $term = gmedia_shortcode_id_data( $id ) ) ) {
		$is_gallery_visible = ( 'publish' !== $term->status && ! $userid ) || ( 'draft' === $term->status && $userid != $term->global && ! is_super_admin() );
		if ( apply_filters( 'is_gmedia_gallery_visible', $is_gallery_visible ) ) {
			return '';
		}

		$taxterm = str_replace( 'gmedia_', '', $term->taxonomy );
		if ( $taxterm === 'gallery' ) {
			if ( ! empty( $term->meta['_query'] ) ) {
				$query = array_merge( $query, $term->meta['_query'] );
			}
		} else {
			$query = array_merge( $query, array( "{$taxterm}__in" => $term->term_id ) );
		}

		if ( ! empty( $term->meta['_module'] ) ) {
			$_module = $term->meta['_module'];
		} elseif ( ! $atts_module && ! empty( $term->meta['_module_preset'] ) ) {
			$preset   = $gmCore->getModulePreset( $term->meta['_module_preset'] );
			$_module  = $preset['module'];
			$settings = $preset['settings'];

			$term->meta['_module']               = $_module;
			$term->meta['_settings'][ $_module ] = (array) $settings[ $_module ];
		}
		if ( isset( $term->meta['_settings'][ $_module ] ) ) {
			$settings = (array) $term->meta['_settings'];
		}
	} elseif ( isset( $atts['library'] ) && ( $quick_gallery = wp_parse_id_list( $atts['library'] ) ) ) {
		$query = array_merge( $query, array( 'gmedia__in' => $quick_gallery ) );
		if ( ! isset( $query['orderby'] ) ) {
			$query['orderby'] = 'gmedia__in';
		}
	}
	if ( empty( $term ) ) {
		$term = (object) array(
			'name'        => '',
			'description' => '',
		);
	}
	if ( isset( $atts['orderby'] ) ) {
		$query['orderby'] = $atts['orderby'];
	}
	if ( isset( $atts['order'] ) ) {
		$query['order'] = $atts['order'];
	}

	if ( $userid && current_user_can( 'gmedia_gallery_manage' ) && ( $preview_module = $gmCore->_get( 'gmedia_module' ) ) ) {
		if ( $preview_module != $_module ) {
			$_module = $preview_module;
			$preset  = $gmCore->getModulePreset( $_module );
			if ( $preset['module'] == $_module ) {
				$settings = $preset['settings'];
			} else {
				$settings = array( $_module => array() );
			}
		}
	}

	$gallery = array();
	if ( ! $id ) {
		$id = $atts_hash;
		// Backward compatibility
		$gallery = array(
			'term_id'     => $id,
			'name'        => __( 'Gallery', 'grand-media' ),
			'description' => '',
		);
	}

	$module = $gmCore->get_module_path( $_module );
	if ( ! $module || ! is_file( $module['path'] . '/index.php' ) || ! is_file( $module['path'] . '/settings.php' ) ) {
		return '<div class="gmedia_gallery gmediaShortcodeError" data-gmid="' . $id . '" data-error="' . $_module . ': folder missed or module broken">' . $shortcode_post_content . '</div>';
	}

	if ( $_module !== $module['name'] ) {
		$_module  = $module['name'];
		$settings = array( $_module => array() );
	}

	if ( ! empty( $atts['preset'] ) ) {
		$preset = $gmDB->get_term( $atts['preset'], 'gmedia_module' );
		if ( ! empty( $preset ) && ! is_wp_error( $preset ) && ( $module['name'] == $preset->status ) ) {
			$settings = array( $module['name'] => (array) maybe_unserialize( $preset->description ) );
		}
	}

	$protected_query_args = array( 'status' => array( 'publish' ) );
	if ( $userid ) {
		$protected_query_args['status'][] = 'private';
	}
	$query = array_merge( apply_filters( 'gmedia_shortcode_query', $query, $id ), $protected_query_args );

	include( $module['path'] . '/index.php' );
	include( $module['path'] . '/settings.php' );
	$module['info']    = isset( $module_info ) ? $module_info : array( 'dependencies' => '' );
	$module['options'] = isset( $default_options ) ? $default_options : array();

	$settings = apply_filters( 'gmedia_shortcode_settings', $gmCore->array_diff_keyval_recursive( (array) $settings[ $module['name'] ], $module['options'], false ) );

	$moduleCSS = isset( $gmGallery->do_module[ $_module ] ) ? '' : $gmGallery->load_module_styles( $module );
	$customCSS = ( isset( $settings['customCSS'] ) && ( '' !== trim( $settings['customCSS'] ) ) ) ? $settings['customCSS'] : '';

	$gmGallery->do_module[ $_module ] = $module;
	$gmGallery->shortcode[ $id ]      = compact( 'id', 'query', 'module', 'settings', 'term' );

	$is_bot = false;
	if ( ! ( $is_mob = wp_is_mobile() ) ) {
		$is_bot = $gmCore->is_bot();
	}
	$mob_cache_key = $is_mob ? '_mob' : '';

	if ( $cache_expiration ) {
		$cache_key   = md5( json_encode( $gmGallery->shortcode[ $id ] ) );
		$cache_value = get_transient( 'gm_cache_' . $cache_key );
		if ( ! $is_bot ) {
			$cache_value_m = get_transient( 'gm_cache_m_' . $cache_key . $mob_cache_key );
		} else {
			$cache_value_m = false;
		}
	}

	unset( $settings['customCSS'] );

	if ( empty( $module['info']['branch'] ) ) {
		$gmedia = array();
		if ( ! empty( $cache_value ) && is_array( $cache_value ) ) {
			extract( $cache_value, EXTR_OVERWRITE );
		} else {
			$query = array( $id => $query );
			if ( ! empty( $term ) ) {
				if ( in_array( $_module, array( 'afflux', 'afflux-mod', 'cube', 'flatwall', 'green-style', 'minima', 'optima', 'photo-blog', 'photo-pro', 'slider', 'sphere' ) ) ) {
					add_filter( 'jetpack_photon_skip_image', 'jetpack_photon_skip_gmedia', 10, 3 );
					$_query = array_merge( $query[ $id ], array( 'album__status' => $protected_query_args['status'] ) );
					$gmDB->gmedias_album_stuff( $_query );
					if ( ! empty( $_query['album__in'] ) && empty( $_query['album__not_in'] ) ) {
						$album__in = wp_parse_id_list( $_query['album__in'] );
						foreach ( $_query as $key => $q ) {
							if ( 'alb' === substr( $key, 0, 3 ) ) {
								unset( $_query[ $key ] );
							}
						}
						foreach ( $album__in as $alb ) {
							$album = $gmDB->get_term( $alb );
							if ( empty( $album ) || is_wp_error( $album ) || $album->count == 0 ) {
								continue;
							}
							$terms[ $alb ]     = $album;
							$new_query[ $alb ] = array_merge( $_query, array( 'album__in' => $alb ) );
						}
						if ( ! empty( $new_query ) ) {
							$query = $new_query;
						}
					}
				}
				if ( empty( $terms ) ) {
					$terms = array( $id => $term );
				}
				$gallery = (array) $term;
			} else {
				$terms = array( $id => (object) $gallery );
			}

			foreach ( $query as $term_id => $args ) {
				if ( empty( $args['orderby'] ) || empty( $args['order'] ) ) {
					$term_query_order = null;
					if ( isset( $args['tag__in'] ) && ( ! isset( $args['category__in'] ) && ! isset( $args['album__in'] ) ) ) {
						$term_query_order = array(
							'orderby' => $gmGallery->options['in_tag_orderby'],
							'order'   => $gmGallery->options['in_tag_order'],
						);
					}
					if ( isset( $args['category__in'] ) && ! isset( $args['album__in'] ) ) {
						$cat_ids = wp_parse_id_list( $args['category__in'] );
						if ( 1 === count( $cat_ids ) ) {
							$cat_meta         = $gmDB->get_metadata( 'gmedia_term', $cat_ids[0] );
							$term_query_order = array(
								'orderby' => ! empty( $cat_meta['_orderby'][0] ) ? $cat_meta['_orderby'][0] : $gmGallery->options['in_category_orderby'],
								'order'   => ! empty( $cat_meta['_order'][0] ) ? $cat_meta['_order'][0] : $gmGallery->options['in_category_order'],
							);
						}
					}
					if ( isset( $args['album__in'] ) ) {
						$alb_ids = wp_parse_id_list( $args['album__in'] );
						if ( 1 === count( $alb_ids ) ) {
							$album_meta       = $gmDB->get_metadata( 'gmedia_term', $alb_ids[0] );
							$term_query_order = array(
								'orderby' => ! empty( $album_meta['_orderby'][0] ) ? $album_meta['_orderby'][0] : $gmGallery->options['in_album_orderby'],
								'order'   => ! empty( $album_meta['_order'][0] ) ? $album_meta['_order'][0] : $gmGallery->options['in_album_order'],
							);
						}
					}
					if ( $term_query_order ) {
						$args = array_merge( $term_query_order, $args );
					}
				}
				$gmedia[ $term_id ] = $gmDB->get_gmedias( $args );
			}

			if ( $cache_expiration ) {
				set_transient( 'gm_cache_' . $cache_key, array( 'gallery' => $gallery, 'gmedia' => $gmedia, 'terms' => $terms, 'query' => $query ), $cache_expiration );
			}
		}

		if ( 0 === count( $gmedia ) ) {
			return '<div class="gmedia_gallery gmedia_gallery_empty" data-gmid="' . esc_attr( $id ) . '" data-module="' . $_module . '">' . __( 'Gallery is empty' ) . '<br />' . $shortcode_post_content . '</div>';
		}
	} else {
		if ( ! empty( $cache_value ) && is_array( $cache_value ) ) {
			$query = $cache_value;
		} else {
			$gmDB->gmedias_album_stuff( $query );
			$gmDB->gmedias_category_stuff( $query );
			$gmDB->gmedias_tag_stuff( $query );
			if ( isset( $query['album__in'] ) ) {
				$query['album__in'] = join( ',', $query['album__in'] );
			}
			if ( isset( $query['category__in'] ) ) {
				$query['category__in'] = join( ',', $query['category__in'] );
			}
			if ( isset( $query['tag__in'] ) ) {
				$query['tag__in'] = join( ',', $query['tag__in'] );
			}

			if ( $cache_expiration ) {
				set_transient( 'gm_cache_' . $cache_key, $query, $cache_expiration );
			}
		}
	}

	$sc_id = str_replace( ' ', '_', "GmediaGallery_{$id}" );

	$sc_classes = "gmedia_gallery {$_module}_module";
	if ( $is_bot ) {
		$sc_classes .= " is_bot";
	}
	if ( $is_mob ) {
		$sc_classes .= " is_mobile";
	}
	if ( ! empty( $atts['class'] ) ) {
		$sc_classes .= ' ' . esc_attr( $atts['class'] );
	}

	$sc_styles = '';
	if ( ! empty( $atts['style'] ) ) {
		$sc_styles = ' style="' . esc_attr( $atts['style'] ) . '"';
	}

	do_action( 'pre_gmedia_shortcode' );

	if ( ! empty( $cache_value_m ) ) {
		$out = $cache_value_m;
	} else {
		$out = '<div class="' . $sc_classes . '" id="' . esc_attr( $sc_id ) . '" data-gmid="' . esc_attr( $id ) . '" data-module="' . esc_attr( $_module ) . '"' . $sc_styles . '>';

		if (
			in_array( $_module, array( 'albums-switcher', 'photocluster', 'albumsview' ) )
			&& empty( $query['album__in'] ) && empty( $query['category__in'] ) && empty( $query['tag__in'] )
		) {
			$out            = __( 'This Gmedia gallery module require at least one term (album, category or tag) to be chosen in Query Builder.', 'grand-media' );
			$module_content = '';
		} else {
			ob_start();
			/** @noinspection PhpIncludeInspection */
			include( $module['path'] . '/init.php' );
			$module_content = ob_get_contents();
			ob_end_clean();

			$matches = array();
			preg_match_all( '/<img[\s\r\n]+.*?>/is', $module_content, $matches );
			$search  = array();
			$replace = array();
			foreach ( $matches[0] as $img_html ) {
				// add the noLazy class to the img element.
				if ( preg_match( '/class=["\']/i', $img_html ) ) {
					$replace_html = preg_replace( '/class=(["\'])(.*?)["\']/is', 'class=$1noLazy $2$1', $img_html );
				} else {
					$replace_html = preg_replace( '/<img/is', '<img class="noLazy"', $img_html );
				}
				array_push( $search, $img_html );
				array_push( $replace, $replace_html );
			}
			$search         = array_unique( $search );
			$replace        = array_unique( $replace );
			$module_content = str_replace( $search, $replace, $module_content );
		}

		if ( $moduleCSS || $customCSS ) {
			$out .= "<style type='text/css' class='gmedia_module_style_import'>{$moduleCSS}";
			if ( $customCSS ) {
				$out .= "/**** .{$_module}_module #{$sc_id} ****/{$customCSS}";
			}
			$out .= '</style>';
		}
		$out .= $shortcode_post_content;
		$out .= $module_content;
		$out .= '</div>';

		if ( $cache_expiration && ! $is_bot ) {
			set_transient( 'gm_cache_m_' . $cache_key . $mob_cache_key, $out, $cache_expiration );
		}
	}

	$id_duplicount = 0;
	if ( empty( $gmedia_shortcode_ids ) ) {
		$gmedia_shortcode_ids[] = (string) $id;
	} else {
		if ( in_array( $id, $gmedia_shortcode_ids ) ) {
			$id_duplicount = 1;
			while ( true ) {
				if ( in_array( "{$id}_{$id_duplicount}", $gmedia_shortcode_ids ) ) {
					$id_duplicount ++;
				} else {
					$gmedia_shortcode_ids[] = "{$id}_{$id_duplicount}";
					break;
				}
			}
		} else {
			$gmedia_shortcode_ids[] = (string) $id;
		}
	}

	if ( $id_duplicount ) {
		$_sc_id = "{$sc_id}_{$id_duplicount}";
		$out    = str_replace( $sc_id, $_sc_id, $out );

		$gmedia_shortcode_instance[ $atts_hash ]['_id'] = $_sc_id;
	}
	$gmedia_shortcode_instance[ $atts_hash ]['id']        = $sc_id;
	$gmedia_shortcode_instance[ $atts_hash ]['shortcode'] = $out;

	do_action( 'gmedia_shortcode' );

	if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
		do_action( 'gmedia_enqueue_scripts' );
	}

	return $out;

}

/**
 * Process the [gmedia _raw] shortcode in priority 4.
 * Since the gmedia raw shortcode needs to be run earlier than other shortcodes,
 * this function removes all existing shortcodes, uses the shortcode parser to grab all [gmedia blocks],
 * calls {@link do_shortcode()}, and then re-registers the old shortcodes.
 *
 * @uses $shortcode_tags
 * @uses remove_all_shortcodes()
 * @uses add_shortcode()
 * @uses do_shortcode()
 *
 * @param string $content Content to parse
 *
 * @return string Content with shortcode parsed
 */
function get_gmedia_unformatted_shortcode_blocks( $content ) {
	global $gmGallery;

	if ( ! (int) $gmGallery->options['shortcode_raw'] ) {
		return $content;
	}

	global $shortcode_tags;

	// Back up current registered shortcodes and clear them all out
	$orig_shortcode_tags = $shortcode_tags;
	remove_all_shortcodes();

	// gmedia_raw_shortcode(), below, saves the rawr blocks into $this->unformatted_shortcode_blocks[]
	add_shortcode( 'gmedia', 'gmedia_raw_shortcode' );
	add_shortcode( 'gm', 'gmedia_raw_shortcode' );

	// Do the shortcode (only the [gmedia] shortcodes are now registered)
	$content = do_shortcode( $content );

	// Put the original shortcodes back for normal processing at priority 11
	$shortcode_tags = $orig_shortcode_tags;

	return $content;
}

/**
 * @param        $atts
 * @param string $shortcode_post_content
 *
 * @return string
 */
function gmedia_raw_shortcode( $atts, $shortcode_post_content = '' ) {
	global $wp_filter, $merged_filters, $wp_current_filter;
	$wp_filter_         = $wp_filter;
	$merged_filters_    = $merged_filters;
	$wp_current_filter_ = $wp_current_filter;
	$noraw              = do_shortcode( apply_filters( 'the_content', '[raw][/raw]' ) );
	$wp_filter          = $wp_filter_;
	$merged_filters     = $merged_filters_;
	$wp_current_filter  = $wp_current_filter_;

	global $gmedia_shortcode_instance;

	unset( $atts['_raw'], $atts['_copy'] );
	$atts_hash    = md5( build_query( $atts ) );
	$atts['_raw'] = '1';
	gmedia_shortcode( $atts, $shortcode_post_content );
	unset( $atts['_raw'] );

	$atts['_copy']  = $gmedia_shortcode_instance[ $atts_hash ]['copy'];
	$shortcode_atts = '';
	// Put the shortcode tag back with raw index, so it gets processed again below.
	foreach ( $atts as $key => $value ) {
		$shortcode_atts .= " {$key}='{$value}'";
	}
	if ( ! $noraw ) {
		return "[raw][gmedia{$shortcode_atts}]{$shortcode_post_content}[/gmedia][/raw]";
	} else {
		return "[gmedia{$shortcode_atts}]{$shortcode_post_content}[/gmedia]";
	}
}

/**
 * @param $id
 *
 * @return object
 */
function gmedia_shortcode_id_data( $id ) {
	global $gmDB, $gmCore;

	$item = $gmDB->get_term( $id );

	if ( empty( $item ) || is_wp_error( $item ) ) {
		return false;
	}

	$meta = $gmDB->get_metadata( 'gmedia_term', $item->term_id );

	if ( $item->global ) {
		$item->author_name = get_the_author_meta( 'display_name', $item->global );
	} else {
		$item->author_name = '';
	}

	$post_id       = isset( $meta['_post_ID'][0] ) ? (int) $meta['_post_ID'][0] : 0;
	$item->post_id = $post_id;
	if ( $post_id ) {
		$post_item = get_post( $post_id );
		if ( $post_item ) {
			$item->slug           = $post_item->post_name;
			$item->post_password  = $post_item->post_password;
			$item->comment_count  = $post_item->comment_count;
			$item->comment_status = $post_item->comment_status;
		}
	}

	$item->custom_meta = array();
	$item->meta        = array();
	foreach ( $meta as $key => $value ) {
		if ( $gmCore->is_protected_meta( $key, 'gmedia_term' ) ) {
			$item->meta[ $key ] = $value[0];
		} else {
			$item->custom_meta[ $key ] = $value;
		}
	}

	return apply_filters( 'gmedia_shortcode_id_data', $item );
}