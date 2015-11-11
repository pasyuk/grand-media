<?php
/**
 * PHP Class for Wordpress SEO plugin
 *
 */
class gmediaSitemaps {

	var $images	= array();

	/**
	 * gmediaSitemaps::__construct()
	 */
	function __construct() {

		add_filter('wpseo_sitemap_urlimages', array( &$this, 'add_wpseo_xml_sitemap_images'), 10, 2);

		add_filter('the_content_feed', 'do_shortcode');

	}

	/**
	 * Filter support for WordPress SEO by Yoast 0.4.0 or higher ( http://wordpress.org/extend/plugins/wordpress-seo/ )
	 *
	 * @param array $images
	 * @param int   $post_id
	 *
	 * @return array $image list of all founded images
	 */
	function add_wpseo_xml_sitemap_images( $images, $post_id )  {
		global $gmGallery, $gmCore;

		$this->images = $images;

		// first get the content of the post/page
		$p = get_post($post_id);

		$content = $p->post_content;

		// Don't process the images in the normal way
		remove_all_shortcodes();

		add_shortcode('gmedia', 'gmedia_shortcode');
		add_shortcode('gm', 'gmedia_term_shortcode');

		// Search now for shortcodes
		do_shortcode( $content );

		if(isset($gmGallery->shortcode['gmedia'])){
			foreach($gmGallery->shortcode['gmedia'] as $termitems){
				foreach($termitems as $item){
					$newimage = array();
					$newimage['src'] = $gmCore->gm_get_media_image($item, 'web');
					if(!empty($item->title)) {
						$newimage['title'] = strip_tags( $item->title );
					}
					if(!empty($item->description)) {
						$newimage['alt'] = strip_tags( $item->description );
					}
					$this->images[] = $newimage;
				}
			}
		}

		return $this->images;
	}
}
$gmediaSitemaps = new gmediaSitemaps();
