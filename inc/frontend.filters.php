<?php
/**
 * FrontEnd Filters
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $gmGallery;

add_action( 'wp_head', 'gmogmeta_header', 2 ); // Generate og:image meta tag
add_action( 'pre_get_posts', 'gmedia_alter_query' ); // Show taxonomy archives for gmedia tags and categories
add_action( 'pre_get_posts', 'gmedia_alter_query_author', 100 ); // Show gmedia posts on author profile page
if ( (int) $gmGallery->options['wp_term_related_gmedia'] ) {
	add_filter( 'the_posts', 'gmedia_the_posts_filter_taxonomy', 10, 2 ); // Prepend related gmedia for tags and categories archives
}
add_action( 'the_post', 'gmedia_the_post' ); // Show Gmedia post types

add_filter( 'widget_comments_args', 'gmedia_widget_comments_args', 10 );

/**
 * Generate og:image meta tag
 */
function gmogmeta_header() {
	global $gmDB, $gmCore;
	$share_ref = (int) $gmCore->_get( 'gmedia_share', 0 );
	$gmedia    = false;
	$is_single = is_single();
	if ( $share_ref && ( $gmedia = $gmDB->get_gmedia( $share_ref ) ) ) {
		//$og_url_id = $gmedia->post_id;
	}
	if ( $is_single ) {
		global $post;
		if ( ! $gmedia && isset( $post->post_type ) && $post->post_type == 'gmedia' ) {
			$gmedia = $gmDB->get_post_gmedia( $post->ID );
		}
		//$og_url_id = $post->ID;
	}
	if ( $gmedia ) {
		remove_action( 'wp_head', 'rel_canonical' );
		$image_url   = $gmCore->gm_get_media_image( $gmedia, 'web' );
		$description = strip_tags( $gmedia->description );
		if ( ! $description ) {
			$description = get_bloginfo( 'description' );
		}
		?>
		<!-- Gmedia Open Graph Meta Image -->
		<meta property="og:title" content="<?php echo esc_attr( $gmedia->title ); ?>"/>
		<meta property="og:description" content="<?php echo esc_attr( $description ); ?>"/>
		<meta property="og:image" content="<?php echo $image_url; ?>"/>
		<!-- End Gmedia Open Graph Meta Image -->
		<?php
	}
}

/** Show taxonomy archives for gmedia tags and categories
 *
 * @param $query
 */
function gmedia_alter_query( $query ) {
	if ( ! empty( $query->query['gmedia_tag'] ) ) {
		$gm_tax = 'gmedia_tag';
	} elseif ( ! empty( $query->query['gmedia_category'] ) ) {
		$gm_tax = 'gmedia_category';
	} else {
		return;
	}

	global $wp_query, $gmDB, $gmGallery;
	$term = urldecode( $query->query[ $gm_tax ] );
	if ( ! $term || ! ( $term_id = $gmDB->term_exists( $term, $gm_tax ) ) ) {
		return;
	}

	$args = array( 'fields' => 'post_ids', 'status' => array( 'publish' ) );
	if ( get_current_user_id() ) {
		$args['status'][] = 'private';
	}
	if ( 'gmedia_tag' == $gm_tax ) {
		$args['tag__in']  = $term_id;
		$args['orderby']  = $gmGallery->options['in_tag_orderby'];
		$args['order']    = $gmGallery->options['in_tag_order'];
		$wp_query->is_tag = true;
	} elseif ( 'gmedia_category' == $gm_tax ) {
		$args['category__in']  = $term_id;
		$term_meta             = $gmDB->get_metadata( 'gmedia_term', $term_id );
		$args['orderby']       = isset( $term_meta['_orderby'][0] ) ? $term_meta['_orderby'][0] : $gmGallery->options['in_category_orderby'];
		$args['order']         = isset( $term_meta['_order'][0] ) ? $term_meta['_order'][0] : $gmGallery->options['in_category_order'];
		$wp_query->is_category = true;
	}
	$gmedias  = $gmDB->get_gmedias( $args );
	$post_ids = array();
	foreach ( $gmedias as $item ) {
		$post_ids[] = $item->post_id;
	}
	if ( ! empty( $post_ids ) ) {
		$query->set( $gm_tax, null );
		$query->set( 'post_type', 'gmedia' );
		$query->set( 'post__in', $post_ids );
		$query->set( 'orderby', 'post__in' );

		$wp_query->is_tag      = false;
		$wp_query->is_category = false;
		$wp_query->is_tax      = false;
		$wp_query->is_archive  = true;

		//we remove the actions hooked on the '__after_loop' (post navigation)
		remove_all_actions( '__after_loop' );
	}
}

/** Show gmedia posts on author profile page
 *
 * @param WP_Query $query
 */
function gmedia_alter_query_author( $query ) {
	if ( empty( $query->query['author'] ) && empty( $query->query['author_name'] ) ) {
		return;
	}
	global $gmGallery;

	$gmedia_post_type = array();
	if ( (int) $gmGallery->options['wp_author_related_gmedia'] ) {
		$gmedia_post_type[] = 'gmedia';
	}
	if ( (int) $gmGallery->options['wp_author_related_gmedia_album'] ) {
		$gmedia_post_type[] = 'gmedia_album';
	}
	if ( (int) $gmGallery->options['wp_author_related_gmedia_gallery'] ) {
		$gmedia_post_type[] = 'gmedia_gallery';
	}

	if ( empty( $gmedia_post_type ) ) {
		return;
	}

	$post_type = $query->get( 'post_type', array( 'post' ) );
	$post_type = array_unique( array_merge( $gmedia_post_type, (array) $post_type ) );
	$query->set( 'post_type', $post_type );

	if ( get_current_user_id() ) {
		$query->set( 'post_status', array( 'publish', 'private' ) );
	}

	//we remove the actions hooked on the '__after_loop' (post navigation)
	remove_all_actions( '__after_loop' );
}

/** Add related media for tags and categories
 *
 * @param $posts
 * @param $query
 *
 * @return mixed
 */
function gmedia_the_posts_filter_taxonomy( $posts, $query ) {

	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	if ( $paged != 1 ) {
		return $posts;
	}

	if ( ! empty( $query->query['tag'] ) ) {
		$tax = 'tag';
	} elseif ( ! empty( $query->query['category'] ) ) {
		$tax = 'category';
	} else {
		return $posts;
	}

	global $wp_query, $gmDB, $gmGallery;
	$term   = urldecode( $query->query[ $tax ] );
	$gm_tax = "gmedia_{$tax}";
	if ( ! $term || ! ( $term_id = $gmDB->term_exists( $term, $gm_tax ) ) ) {
		return $posts;
	}

	$args = array( 'fields' => 'post_ids', 'status' => array( 'publish' ), 'limit' => 1 );
	if ( get_current_user_id() ) {
		$args['status'][] = 'private';
	}
	if ( 'tag' == $tax ) {
		$args['tag__in']  = $term_id;
		$args['orderby']  = $gmGallery->options['in_tag_orderby'];
		$args['order']    = $gmGallery->options['in_tag_order'];
		$wp_query->is_tag = true;
	} elseif ( 'category' == $tax ) {
		$args['category__in']  = $term_id;
		$term_meta             = $gmDB->get_metadata( 'gmedia_term', $term_id );
		$args['orderby']       = isset( $term_meta['_orderby'][0] ) ? $term_meta['_orderby'][0] : $gmGallery->options['in_category_orderby'];
		$args['order']         = isset( $term_meta['_order'][0] ) ? $term_meta['_order'][0] : $gmGallery->options['in_category_order'];
		$wp_query->is_category = true;
	}
	$gmedia_ids = $gmDB->get_gmedias( $args );
	if ( count( $gmedia_ids ) ) {
		$query->gmedia_term_post = $term_id;
		array_unshift( $posts, $gmedia_ids[0]->post_id );
	}

	return $posts;
}

/** Show related media for tags and categories
 *
 * @param $post
 */
function gmedia_the_post( $post ) {
	global $gmGallery;
	if ( 'gmedia' == substr( $post->post_type, 0, 6 ) ) {
		global $wp_query, $gmDB;
		if ( isset( $wp_query->gmedia_term_post ) ) {
			global $post;
			$term               = $gmDB->get_term( $wp_query->gmedia_term_post );
			$date               = gmdate( 'Y-m-d H:i:s' );
			$post               = (object) array(
				'ID'                    => 0,
				'term_id'               => $term->term_id,
				'post_author'           => 0,
				'post_date'             => $date,
				'post_date_gmt'         => $date,
				'post_content'          => "[gm id={$term->term_id}]",
				'post_title'            => __( 'Related Media', 'grand-media' ) . ': ' . $term->name,
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_password'         => '',
				'post_name'             => '',
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => $date,
				'post_modified_gmt'     => $date,
				'post_content_filtered' => '',
				'post_parent'           => '0',
				'guid'                  => '',
				'menu_order'            => '0',
				'post_type'             => $term->taxonomy,
				'post_mime_type'        => '',
				'comment_count'         => '0',
				'filter'                => 'raw',
			);
			$wp_query->posts[0] = $post;
			$wp_query->post     = $post;
			unset( $wp_query->gmedia_term_post );
		}
		add_filter( 'get_the_excerpt', 'gmedia_post_type__the_excerpt', 150 );
		add_filter( 'the_content', 'gmedia_post_type__the_content', 200 );
	} elseif ( is_single() ) {
		$gmedia_post_types = array_merge( array(
			'post',
			'page',
		), (array) $gmGallery->options['gmedia_post_types_support'] );
		$gm_post_types     = apply_filters( 'gmedia-post-types', $gmedia_post_types );
		if ( in_array( $post->post_type, $gm_post_types ) ) {
			$show_related = get_post_meta( $post->ID, '_related_gmedia', true );
			if ( ( '1' === $show_related ) || ( ( '' === $show_related ) && (int) $gmGallery->options['wp_post_related_gmedia'] ) ) {
				add_filter( 'the_content', 'gmedia_related__the_content', 200 );
			}
		}
	}
}

/**
 * @param $content
 *
 * @return mixed|string
 */
function gmedia_post_type__the_excerpt( $content ) {
	global $post;

	if ( 'gmedia' !== substr( $post->post_type, 0, 6 ) ) {
		return $content;
	}

	remove_filter( 'get_the_excerpt', 'gmedia_post_type__the_excerpt', 150 );
	remove_filter( 'the_content', 'gmedia_post_type__the_content', 200 );
	$content = wp_trim_excerpt();

	return gmedia_post_type__the_content( $content );
}

/**
 * @param $content
 *
 * @return mixed|string
 */
function gmedia_post_type__the_content( $content ) {
	global $post, $gmDB, $gmCore, $gmGallery;

	if ( 'gmedia' !== substr( $post->post_type, 0, 6 ) ) {
		return $content;
	}

	if ( isset( $post->gmedia_content ) ) {
		$post->post_content = $post->gmedia_content;

		return $post->gmedia_content;
	}

	remove_filter( 'the_content', 'gmedia_post_type__the_content', 200 );

	$output = '';
	if ( $post->post_type == 'gmedia' ) {
		$gmedia_id = get_post_meta( $post->ID, '_gmedia_ID', true );
		$gmedia    = $gmDB->get_gmedia( $gmedia_id );
		if ( $gmedia ) {
			gmedia_item_more_data( $gmedia );

			ob_start();

			$embed_code = false;
			if ( $gmedia->link ) {
				$gmedia_link   = $gmedia->link;
				$base_url_host = parse_url( $gmCore->upload['url'], PHP_URL_HOST );
				$url_host      = parse_url( $gmedia->link, PHP_URL_HOST );
				if ( $url_host == $base_url_host || empty( $url_host ) ) {
					$link_target = ' target="_self"';
				} else {
					$link_target = ' target="_blank"';
					$embed_code  = wp_oembed_get( $gmedia_link );
				}
				if ( isset( $gmedia->meta['link_target'][0] ) ) {
					$link_target = ' target="' . $gmedia->meta['link_target'][0] . '"';
				}
			} else {
				$gmedia_link = $gmedia->url;
				$link_target = '';
			}

			if ( 'image' == $gmedia->type ) {
				if ( $embed_code ) {
					echo '<div class="gmedia-item">' . $embed_code . '</div>';
				} else {
					?>
					<a class="gmedia-item-link" rel="gmedia-item"
						href="<?php echo $gmedia_link; ?>"<?php echo $link_target; ?>><img class="gmedia-item"
							style="max-width:100%;"
							src="<?php echo $gmedia->url; ?>"
							alt="<?php esc_attr_e( $gmedia->title ); ?>"/></a>
					<?php
				}

				if ( is_single() && ! $embed_code ) {
					/** more info */

					$author_name       = get_the_author_meta( 'display_name', $gmedia->author );
					$author_posts_link = get_author_posts_url( $gmedia->author );
					$avatar_img        = get_avatar( $gmedia->author, 60 );
					if ( preg_match( "/src=['\"](.*?)['\"]/i", $avatar_img, $matches ) ) {
						$author_avatar = $matches[1];
					}
					?>
					<div class="gmsingle_wrapper gmsingle_clearfix">
						<script type="text/html" class="gm_script2html">
							<div class="gmsingle_photo_header gmsingle_clearfix">
								<div class="gmsingle_name_wrap gmsingle_clearfix">
									<?php if ( ! empty( $author_avatar ) ) { ?>
										<div class="gmsingle_user_avatar">
											<a class="gmsingle_user_avatar_link"
												href="<?php echo urldecode( $author_posts_link ); ?>"><img
													src="<?php echo $author_avatar; ?>" alt=""/></a>
										</div>
									<?php } ?>
									<div class="gmsingle_title_author">
										<div class="gmsingle_title"><?php
											if ( ( 'image' != $gmedia->type ) && $gmedia->link ) {
												echo "<a href='{$gmedia_link}'{$link_target}>{$gmedia->title}&nbsp;&#x1f517;</a>";
											} else {
												echo $gmedia->title;
											}
											?>&nbsp;
										</div>
										<div class="gmsingle_author_name">
											<a class="gmsingle_author_link"
												href="<?php echo urldecode( $author_posts_link ); ?>"><?php echo $author_name; ?></a>
										</div>
									</div>
								</div>
							</div>
						</script>
						<div class="gmsingle_photo_info">
							<div class="gmsingle_description_wrap">
								<?php echo apply_filters( 'the_gmedia_content', wpautop( $gmedia->description ) ); ?>
								<script type="text/html" class="gm_script2html">
									<?php
									if ( ! empty( $gmedia->album ) ) {
										$term_name    = $gmedia->album[0]->name;
										$term_post_id = $gmDB->get_metadata( 'gmedia_term', $gmedia->album[0]->term_id, '_post_ID', true );
										if ( ! empty( $term_post_id ) ) {
											$term_url = get_permalink( $term_post_id );
										} else {
											$term_url = $gmCore->gmcloudlink( $gmedia->album[0]->term_id, 'album' );
										}
										?>
										<div class="gmsingle_terms">
											<span class="gmsingle_term_label"><?php _e( 'Album' ); ?>:</span>
											<span class="gmsingle_album"><span class="gmsingle_term"><a
														href="<?php echo $term_url; ?>"><?php echo $term_name; ?></a></span></span>
										</div>
										<?php
									}
									if ( ! empty( $gmedia->categories ) ) {
										$item_cats = array();
										foreach ( $gmedia->categories as $term ) {
											$term->slug = $term->name;
											$term_url   = get_term_link( $term );
											//$term_url = $gmCore->gmcloudlink($term->term_id, 'category');
											$item_cats[] = "<span class='gmsingle_term'><a href='{$term_url}'>{$term->name}</a></span>";
										}
										?>
										<div class="gmsingle_terms">
											<span class="gmsingle_term_label"><?php _e( 'Categories' ); ?>:</span>
											<span class="gmsingle_categories"><?php echo implode( ' ', $item_cats ); ?></span>
										</div>
										<?php
									}
									if ( ! empty( $gmedia->tags ) ) {
										$item_tags = array();
										foreach ( $gmedia->tags as $term ) {
											$term->slug = $term->name;
											$term_url   = get_term_link( $term );
											//$term_url    = $gmCore->gmcloudlink($term->term_id, 'tag');
											$item_tags[] = "<span class='gmsingle_term'><a href='{$term_url}'>#{$term->name}</a></span>";
										}
										?>
										<div class="gmsingle_terms">
											<span class="gmsingle_term_label"><?php _e( 'Tags' ); ?>:</span>
											<span class="gmsingle_tags"><?php echo implode( ' ', $item_tags ); ?></span>
										</div>
									<?php } ?>
								</script>
							</div>
							<script type="text/html" class="gm_script2html">
								<?php if ( $gmedia->gps ) {
									$loc = str_replace( ' ', '', $gmedia->gps );
									?>
									<div class="gmsingle_location_section">
										<div class="gmsingle_details_title"><?php _e( 'Location' ); ?></div>

										<div class="gmsingle_location_info">
											<a href='https://www.google.com/maps/place/<?php echo $loc; ?>'
												target='_blank'><img
													src='//maps.googleapis.com/maps/api/staticmap?key=<?php esc_attr_e( $gmGallery->options['google_api_key'] ); ?>&size=320x240&zoom=10&scale=2&maptype=roadmap&markers=<?php echo $loc; ?>'
													alt='' width='320' height='240'/></a>
										</div>
									</div>
								<?php } ?>
								<div class="gmsingle_details_section">
									<div class="gmsingle_details_title"><?php _e( 'Details', 'grand-media' ); ?></div>

									<div class="gmsingle_slide_details">
										<?php /* ?>
                                <div class='gmsingle_badges'>
                                    <div class='gmsingle_badges__column'>
                                        <div class='gmsingle_badges__label'><?php _e('Views', 'grand-media'); ?></div>
                                        <div class='gmsingle_badges__count'><?php echo $gmedia->meta['views'][0]; ?></div>
                                    </div>
                                    <div class='gmsingle_badges__column'>
                                        <div class='gmsingle_badges__label'><?php _e('Likes', 'grand-media'); ?></div>
                                        <div class='gmsingle_badges__count gmsingle_like_count'><?php echo $gmedia->meta['likes'][0]; ?></div>
                                    </div>
                                    <div class='gmsingle_clearfix'></div>
                                </div>
                                <?php
                                */
										$exif = $gmCore->metadata_info( $gmedia->ID );

										$details = array();
										if ( ! empty( $exif ) ) {
											$details['model']           = empty( $exif['model'] ) ? '' : $exif['model']['value'];
											$details['lens']            = empty( $exif['lens'] ) ? '' : $exif['lens']['value'];
											$details['camera_settings'] = array(
												'focallength' => empty( $exif['focallength'] ) ? ( empty( $exif['focallength35'] ) ? '' : $exif['focallength35']['value'] ) : $exif['focallength']['value'],
												'aperture'    => empty( $exif['aperture'] ) ? '' : str_replace( 'f', 'ƒ', $exif['aperture']['value'] ),
												'exposure'    => empty( $exif['exposure'] ) ? '' : $exif['exposure']['value'],
												'iso'         => empty( $exif['iso'] ) ? '' : 'ISO ' . $exif['iso']['value'],
											);
											$details['camera_settings'] = array_filter( $details['camera_settings'] );
											$details['taken']           = empty( $exif['created_timestamp'] ) ? '' : date_i18n( get_option( 'date_format' ), $exif['created_timestamp']['value'] );
										}
										$details['uploaded'] = date_i18n( get_option( 'date_format' ), strtotime( $gmedia->date ) );

										if ( ! empty( $details['model'] ) ) { ?>
											<div class='gmsingle_exif'>
												<div class='gmsingle_label gmsingle_exif_model'><?php echo $details['model']; ?></div>
												<?php if ( ! empty( $details['lens'] ) ) { ?>
													<div class='gmsingle_label_small gmsingle_exif_lens'><?php echo $details['lens']; ?></div>
												<?php }
												$camera_settings = array();
												foreach ( $details['camera_settings'] as $key => $value ) {
													$camera_settings[] = "<span class='gmsingle_exif_{$key}'>{$value}</span>";
												}
												if ( ! empty( $camera_settings ) ) { ?>
													<div class='gmsingle_label_small gmsingle_camera_settings'><?php echo implode( '<span class="gmsingle_separator"> / </span>', $camera_settings ); ?></div>
												<?php } ?>
											</div>
										<?php } ?>
										<div class='gmsingle_meta'>
											<?php if ( ! empty( $details['taken'] ) ) { ?>
												<div class='gmsingle_clearfix'>
													<span class='gmsingle_meta_key'><?php _e( 'Created', 'grand-media' ); ?></span>
													<span class='gmsingle_meta_value'><?php echo $details['taken']; ?></span>
												</div>
											<?php } ?>
											<div class='gmsingle_clearfix'>
												<span class='gmsingle_meta_key'><?php _e( 'Uploaded', 'grand-media' ); ?></span>
												<span class='gmsingle_meta_value'><?php echo $details['uploaded']; ?></span>
											</div>
										</div>
									</div>
								</div>
							</script>
						</div>
					</div>
					<style type="text/css">
						.gmsingle_clearfix {
							display: block;
						}

						.gmsingle_clearfix::after {
							visibility: hidden;
							display: block;
							font-size: 0;
							content: ' ';
							clear: both;
							height: 0;
						}

						.gmsingle_wrapper {
							margin: 0 auto;
						}

						.gmsingle_wrapper * {
							-webkit-box-sizing: border-box;
							-moz-box-sizing: border-box;
							box-sizing: border-box;
						}

						.gmsingle_photo_header {
							margin-bottom: 15px;
						}

						.gmsingle_name_wrap {
							padding: 24px 0 2px 80px;
							height: 85px;
							max-width: 100%;
							overflow: hidden;
							white-space: nowrap;
							position: relative;
						}

						.gmsingle_name_wrap .gmsingle_user_avatar {
							position: absolute;
							top: 20px;
							left: 0;
						}

						.gmsingle_name_wrap .gmsingle_user_avatar a.gmsingle_user_avatar_link {
							display: block;
							text-decoration: none;
						}

						.gmsingle_name_wrap .gmsingle_user_avatar img {
							height: 60px !important;
							width: auto;
							overflow: hidden;
							border-radius: 3px;
						}

						.gmsingle_name_wrap .gmsingle_title_author {
							display: inline-block;
							vertical-align: top;
							max-width: 100%;
						}

						.gmsingle_name_wrap .gmsingle_title_author .gmsingle_title {
							text-rendering: auto;
							font-weight: 100;
							font-size: 24px;
							width: 100%;
							overflow: hidden;
							white-space: nowrap;
							text-overflow: ellipsis;
							margin: 0;
							padding: 1px 0;
							height: 1.1em;
							line-height: 1;
							box-sizing: content-box;
							text-transform: none;
							letter-spacing: 0px;
							text-transform: capitalize;
						}

						.gmsingle_name_wrap .gmsingle_title_author > div {
							font-size: 14px;
						}

						.gmsingle_name_wrap .gmsingle_title_author .gmsingle_author_name {
							float: left;
						}

						.gmsingle_name_wrap .gmsingle_title_author a {
							font-size: inherit;
						}

						.gmsingle_photo_info {
							display: flex;
							flex-wrap: wrap;
						}

						.gmsingle_details_title {
							margin: 0;
							padding: 0;
							text-transform: uppercase;
							font-size: 18px;
							line-height: 1em;
							font-weight: 300;
							height: 1.1em;
							display: inline-block;
							overflow: visible;
							border: none;
						}

						.gmsingle_description_wrap {
							flex: 1;
							overflow: hidden;
							min-width: 220px;
							max-width: 100%;
							padding-right: 7px;
							margin-bottom: 30px;
						}

						.gmsingle_description_wrap .gmsingle_terms {
							overflow: hidden;
							margin: 0;
							position: relative;
							font-size: 14px;
							font-weight: 300;
						}

						.gmsingle_description_wrap .gmsingle_term_label {
							margin-right: 10px;
						}

						.gmsingle_description_wrap .gmsingle_term_label:empty {
							display: none;
						}

						.gmsingle_description_wrap .gmsingle_terms .gmsingle_term {
							display: inline-block;
							margin: 0 12px 1px 0;
						}

						.gmsingle_description_wrap .gmsingle_terms .gmsingle_term a {
							white-space: nowrap;
						}

						.gmsingle_details_section {
							flex: 1;
							width: 33%;
							padding-right: 7px;
							padding-left: 7px;
							min-width: 220px;
							max-width: 100%;
						}

						.gmsingle_details_section .gmsingle_slide_details {
							margin: 20px 0;
						}

						.gmsingle_location_section {
							flex: 1;
							width: 27%;
							padding-right: 7px;
							padding-left: 7px;
							min-width: 220px;
							max-width: 100%;
						}

						.gmsingle_location_section .gmsingle_location_info {
							margin: 20px 0;
						}

						.gmsingle_location_section .gmsingle_location_info * {
							display: block;
						}

						.gmsingle_location_section .gmsingle_location_info img {
							width: 100%;
							height: auto;
						}

						.gmsingle_badges {
							border-bottom: 1px solid rgba(0, 0, 0, 0.1);
							padding-bottom: 17px;
							margin-bottom: 12px;
							text-align: left;
							font-weight: 300;
						}

						.gmsingle_badges__column {
							display: inline-block;
							vertical-align: top;
							width: 40%;
							min-width: 80px;
						}

						.gmsingle_badges__column .gmsingle_badges__label {
							font-size: 14px;
						}

						.gmsingle_badges__column .gmsingle_badges__count {
							font-size: 20px;
							line-height: 1em;
							margin-top: 1px;
						}

						.gmsingle_exif {
							border-bottom: 1px solid rgba(0, 0, 0, 0.1);
							padding-bottom: 12px;
							margin-bottom: 12px;
							text-align: left;
							font-size: 14px;
							line-height: 1.7em;
							font-weight: 300;
						}

						.gmsingle_exif .gmsingle_camera_settings .gmsingle_separator {
							font-weight: 200;
							padding: 0 5px;
							display: inline-block;
						}

						.gmsingle_meta {
							padding-bottom: 12px;
							margin-bottom: 12px;
							text-align: left;
							font-size: 14px;
							line-height: 1.2em;
							font-weight: 300;
						}

						.gmsingle_meta .gmsingle_meta_key {
							float: left;
							padding: 3px 0;
							width: 40%;
							min-width: 80px;
						}

						.gmsingle_meta .gmsingle_meta_value {
							float: left;
							white-space: nowrap;
							padding: 3px 0;
							text-transform: capitalize;
						}
					</style>
					<?php
				} else {
					echo apply_filters( 'the_gmedia_content', wpautop( $gmedia->description ) );

					if ( ! empty( $gmedia->album ) ) {
						$term_name    = $gmedia->album[0]->name;
						$term_post_id = $gmDB->get_metadata( 'gmedia_term', $gmedia->album[0]->term_id, '_post_ID', true );
						if ( ! empty( $term_post_id ) ) {
							$term_url = get_permalink( $term_post_id );
						} else {
							$term_url = $gmCore->gmcloudlink( $gmedia->album[0]->term_id, 'album' );
						}
						?>
						<p class="gmsingle_terms">
							<span class="gmsingle_term_label"><?php _e( 'Album' ); ?>:</span>
							<span class="gmsingle_album"><span class="gmsingle_term"><a
										href="<?php echo $term_url; ?>"><?php echo $term_name; ?></a></span></span>
						</p>
						<?php
					}
					if ( ! empty( $gmedia->categories ) ) {
						$item_cats = array();
						foreach ( $gmedia->categories as $term ) {
							$term->slug = $term->name;
							$term_url   = get_term_link( $term );
							//$term_url = $gmCore->gmcloudlink($term->term_id, 'category');
							$item_cats[] = "<span class='gmsingle_term'><a href='{$term_url}'>{$term->name}</a></span>";
						}
						?>
						<p class="gmsingle_terms">
							<span class="gmsingle_term_label"><?php _e( 'Categories' ); ?>:</span>
							<span class="gmsingle_categories"><?php echo implode( ' ', $item_cats ); ?></span>
						</p>
						<?php
					}
					if ( ! empty( $gmedia->tags ) ) {
						$item_tags = array();
						foreach ( $gmedia->tags as $term ) {
							$term->slug = $term->name;
							$term_url   = get_term_link( $term );
							//$term_url    = $gmCore->gmcloudlink($term->term_id, 'tag');
							$item_tags[] = "<span class='gmsingle_term'><a href='{$term_url}'>#{$term->name}</a></span>";
						}
						?>
						<p class="gmsingle_terms">
							<span class="gmsingle_term_label"><?php _e( 'Tags' ); ?>:</span>
							<span class="gmsingle_tags"><?php echo implode( ' ', $item_tags ); ?></span>
						</p>
					<?php } ?>
					<style type="text/css">
						.gmsingle_terms { margin-top: 10px; }
					</style>
					<?php
				}

			} elseif ( 'audio' == $gmedia->type && ( $module = $gmCore->get_module_path( 'wavesurfer' ) ) && $module['name'] === 'wavesurfer' ) {
				echo gmedia_shortcode( array( 'module' => 'wavesurfer', 'library' => $gmedia->ID, 'native' => true ) );
				if ( is_single() ) {
					echo apply_filters( 'the_gmedia_content', wpautop( $gmedia->description ) );
				}
			} else {
				$ext1 = wp_get_audio_extensions();
				$ext2 = wp_get_video_extensions();
				$ext  = array_merge( $ext1, $ext2 );
				if ( in_array( $gmedia->ext, $ext ) ) {
					global $wp_embed;
					$embed = $wp_embed->run_shortcode( "[embed]$gmedia->url[/embed]" );
					if ( '[' == substr( $embed, 0, 1 ) ) {
						$embed = do_shortcode( $embed );
					}
					echo $embed;
				} else {
					$cover_url = $gmCore->gm_get_media_image( $gmedia, 'web' );
					?>
					<a class="gmedia-item-link" href="<?php echo $gmedia->url; ?>" download="true"><img
							class="gmedia-item" style="max-width:100%;" src="<?php echo $cover_url; ?>"
							alt="<?php esc_attr_e( $gmedia->title ); ?>"/></a>
					<?php
				}

				if ( ! empty( $gmedia->album ) ) {
					$term_name    = $gmedia->album[0]->name;
					$term_post_id = $gmDB->get_metadata( 'gmedia_term', $gmedia->album[0]->term_id, '_post_ID', true );
					if ( ! empty( $term_post_id ) ) {
						$term_url = get_permalink( $term_post_id );
					} else {
						$term_url = $gmCore->gmcloudlink( $gmedia->album[0]->term_id, 'album' );
					}
					?>
					<p class="gmsingle_terms">
						<span class="gmsingle_term_label"><?php _e( 'Album' ); ?>:</span>
						<span class="gmsingle_album"><span class="gmsingle_term"><a
									href="<?php echo $term_url; ?>"><?php echo $term_name; ?></a></span></span>
					</p>
					<?php
				}
				if ( ! empty( $gmedia->categories ) ) {
					$item_cats = array();
					foreach ( $gmedia->categories as $term ) {
						$term->slug = $term->name;
						$term_url   = get_term_link( $term );
						//$term_url = $gmCore->gmcloudlink($term->term_id, 'category');
						$item_cats[] = "<span class='gmsingle_term'><a href='{$term_url}'>{$term->name}</a></span>";
					}
					?>
					<p class="gmsingle_terms">
						<span class="gmsingle_term_label"><?php _e( 'Categories' ); ?>:</span>
						<span class="gmsingle_categories"><?php echo implode( ' ', $item_cats ); ?></span>
					</p>
					<?php
				}
				if ( ! empty( $gmedia->tags ) ) {
					$item_tags = array();
					foreach ( $gmedia->tags as $term ) {
						$term->slug = $term->name;
						$term_url   = get_term_link( $term );
						//$term_url    = $gmCore->gmcloudlink($term->term_id, 'tag');
						$item_tags[] = "<span class='gmsingle_term'><a href='{$term_url}'>#{$term->name}</a></span>";
					}
					?>
					<p class="gmsingle_terms">
						<span class="gmsingle_term_label"><?php _e( 'Tags' ); ?>:</span>
						<span class="gmsingle_tags"><?php echo implode( ' ', $item_tags ); ?></span>
					</p>
				<?php } ?>
				<style type="text/css">
					.gmsingle_terms { margin-top: 10px; }
				</style>
				<?php
			}

			$output = ob_get_contents();
			ob_end_clean();

		}

	} else {
		if ( ! isset( $post->term_id ) ) {
			$post->term_id = get_post_meta( $post->ID, '_gmedia_term_ID', true );
		}
		$output      = '';
		$gmedia_term = $gmDB->get_term( $post->term_id );
		if ( $gmedia_term && ! is_wp_error( $gmedia_term ) ) {
			add_filter( 'comments_open', '__return_false' );
			$current_filter = current_filter();
			if ( 'get_the_excerpt' == $current_filter ) {
				$cover_id = $gmDB->get_metadata( 'gmedia_term', $post->term_id, '_cover', true );
				if ( (int) $cover_id && ( $cover = $gmDB->get_gmedia( (int) $cover_id ) ) ) {
					$output .= '<div class="gmedia-term-cover"><img class="gmedia-item" style="max-width:100%;" src="' . $gmCore->gm_get_media_image( $cover, 'web', true ) . '" alt="' . esc_attr_e( $gmedia_term->name ) . '"/></div>';
				}
			}

			$output .= apply_filters( 'the_gmedia_content', wpautop( $gmedia_term->description ) );

			if ( 'get_the_excerpt' != $current_filter ) {
				$output .= do_shortcode( "[gmedia id={$post->term_id}]" );
			}
		}
	}

	if ( is_single() ) {
		$before = '<div class="GmediaGallery_SinglePage">';
		$after  = '</div>';
	} else {
		$before = '<div class="GmediaGallery_ArchivePage">';
		$after  = '</div>';
	}


	$output = $before . $output . $after;

	$output = str_replace( array( "\r\n", "\r", "\n" ), '', $output );
	$output = preg_replace( '/ {2,}/', ' ', $output );

	$post->post_content   = $output;
	$post->gmedia_content = $output;

	return $output;
}

/**
 * @param $content
 *
 * @return mixed|string
 */
function gmedia_related__the_content( $content ) {
	global $post, $gmDB, $gmGallery, $user_ID;

	remove_filter( 'the_content', 'gmedia_related__the_content', 200 );

	$post_terms = get_the_tags( $post->ID );
	if ( empty( $post_terms ) ) {
		return $content;
	}

	$args = array(
		'status'    => array( 'publish' ),
		'orderby'   => $gmGallery->options['in_tag_orderby'],
		'order'     => $gmGallery->options['in_tag_order'],
		'null_tags' => true,
	);
	if ( $user_ID ) {
		$args['status'][] = 'private';
	}
	foreach ( $post_terms as $term ) {
		$args['tag_name__in'][] = $term->name;
	}

	$per_page = (int) get_post_meta( $post->ID, '_related_gmedia_per_page', true );
	if ( $per_page ) {
		$args['per_page'] = $per_page;
	}

	$gmedias = $gmDB->get_gmedias( $args );
	if ( empty( $gmedias ) ) {
		return $content;
	}

	unset( $args['null_tags'] );
	$gmedia_content = gmedia_shortcode( array( 'query' => $args ) );

	$gmedia_content = str_replace( array( "\r\n", "\r", "\n" ), '', $gmedia_content );
	$gmedia_content = preg_replace( '/ {2,}/', ' ', $gmedia_content );

	$content .= apply_filters( 'before_gmedia_related__the_content', '' );
	$content .= $gmedia_content;
	$content .= apply_filters( 'after_gmedia_related__the_content', '' );

	return $content;
}

function gmedia_widget_comments_args( $args ) {
	if ( get_current_user_id() ) {
		$args['post_status'] = array( 'publish', 'private' );
	}

	return $args;
}