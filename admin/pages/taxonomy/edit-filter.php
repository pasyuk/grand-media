<?php
/**
 * Gmedia Filter Edit
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$gmedia_url           = $gmProcessor->url;
$gmedia_user_options  = $gmProcessor->user_options;
$gmedia_term_taxonomy = $gmProcessor->taxonomy;
$taxterm = str_replace('gmedia_', '', $gmedia_term_taxonomy);

if(!gm_user_can("filter_manage")) {
    die('-1');
}

$term_id = $gmCore->_get('edit_item');
$per_page = 40;
$cur_page = $gmCore->_get('pager', 1);

$author_new = false;
if(gm_user_can('edit_others_media')) {
    $author = (int)$gmCore->_get('author', $user_ID);
} else {
    $author = $user_ID;
}

$pager_html = '';
$term       = array(
    'name'        => '',
    'description' => '',
    'global'      => $author
);

$filter_data = array(
    'author__in'         => array()
    , 'author__not_in'   => array()
    , 'category__in'     => array() // use category id. Same as 'cat', but does not accept negative values
    , 'category__not_in' => array() // use category id. Exclude multiple categories
    , 'album__in'        => array() // use album id. Same as 'alb'
    , 'album__not_in'    => array() // use album id. Exclude multiple albums
    , 'tag__and'         => array() // use tag ids. Display posts that are tagged with all listed tags in array
    , 'tag__in'          => array() // use tag ids. To display posts from either tags listed in array. Same as 'tag'
    , 'tag__not_in'      => array() // use tag ids. Display posts that do not have any of the listed tag ids
    , 'terms_relation'   => '' //  allows you to describe the boolean relationship between the taxonomy queries. Possible values are 'OR', 'AND'. Default 'AND'
    , 'gmedia__in'       => array() // use gmedia ids. Specify posts to retrieve
    , 'gmedia__not_in'   => array() // use gmedia ids. Specify post NOT to retrieve
    , 'mime_type'        => array() // mime types

    , 'limit'            => '' // (int) - set limit
    , 'per_page'         => '' // (int) - set limit
    , 'order'            => '' // Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'
    , 'orderby'          => '' // Sort retrieved posts by parameter. Defaults to 'ID'
    , 'year'             => '' // (int) - 4 digit year
    , 'monthnum'         => '' // (int) - Month number (from 1 to 12)
    , 'day'              => '' // (int) - Day of the month (from 1 to 31)

    , 'meta_query'       => array(
        array(
            'key'     => '',
            'value'   => '',
            'compare' => '',
            'type'    => ''
        )
    )
    , 's'                => '' // (string) - search string or terms separated by comma
    , 'exact'            => false // Search exactly string if 'exact' parameter set to true

);

$filter_variable_data = $term_query = array(
    'cache_results' => false,
    'page'          => $cur_page, // number of page. Show the posts that would normally show up just on page X.
    'per_page'      => $per_page // number of post to displace or pass over. Note: Setting offset parameter will ignore the 'page' parameter.
);

$filter_form_custom_data = array(
    'gmedia_album'         => array(),
    'gmedia_category'      => array(),
    'gmedia_tag'           => array(),
    'gmedia_id'            => array(),
    'author_id'            => array(),
    'album__condition'     => 'album__in',
    'category__condition'  => 'category__in',
    'tag__condition'       => 'tag__in',
    'gmedia_id__condition' => 'gmedia__in',
    'author_id__condition' => 'author__in'
);
/**
 * @var $gmedia_album
 * @var $gmedia_category
 * @var $gmedia_tag
 * @var $gmedia_id
 * @var $author_id
 * @var $album__condition
 * @var $category__condition
 * @var $tag__condition
 * @var $gmedia_id__condition
 * @var $author_id__condition
 */
extract($filter_form_custom_data);

$totalResult     = 0;
$trueTotalResult = 0;
if($term_id) {
    $term = $gmDB->get_term($term_id, $gmedia_term_taxonomy, ARRAY_A);
    if(!empty($term) && !is_wp_error($term)) {

        $term_query  = $gmDB->get_metadata('gmedia_term', $term['term_id'], '_query', true);
        $filter_data = array_merge($filter_data, $term_query);

        $term_query = array_merge($filter_variable_data, $term_query);

        if(isset($_GET['author']) && ($term['global'] != $author)) {
            $filter_data['_query']['gmedia_album'] = array();
            $term['global']                        = $author;
            $author_new                            = true;
        }

        if(!empty($filter_data['album__not_in'])) {
            $album__condition = 'album__not_in';
        }
        $gmedia_album = $filter_data[$album__condition];

        if(!empty($filter_data['category__not_in'])) {
            $category__condition = 'category__not_in';
        }
        $gmedia_category = $filter_data[$category__condition];

        if(!empty($filter_data['tag__not_in'])) {
            $tag__condition = 'tag__not_in';
        } elseif(!empty($filter_data['tag__and'])) {
            $tag__condition = 'tag__and';
        }
        $gmedia_tag = $filter_data[$tag__condition];

        if(!empty($filter_data['gmedia__not_in'])) {
            $gmedia_id__condition = 'gmedia__not_in';
        }
        $gmedia_id = $filter_data[$gmedia_id__condition];

        if(!empty($filter_data['author__not_in'])) {
            $author_id__condition = 'author__not_in';
        }
        $author_id = $filter_data[$author_id__condition];

        $termItems   = $gmDB->get_gmedias($term_query);
        $totalResult = (int)$gmDB->totalResult;
        if(!$totalResult && !empty($termItems)) {
            $totalResult = count($termItems);
        }
        if(!empty($gmDB->trueTotalResult)) {
            $trueTotalResult = $gmDB->trueTotalResult;
        }

        if(!empty($termItems)) {
            $pager_html = $gmDB->query_pager();
        }
    } else {
        $term_id = 0;
    }
}

do_action('gmedia_term_before_panel');
?>

<div class="panel panel-default">

    <?php
    include(dirname(__FILE__) . '/tpl/term-panel-heading.php');

    include(dirname(__FILE__) . "/tpl/filter-edit-item.php");
    ?>

</div>

<div class="panel panel-default" id="queryfilter">
    <div class="panel-heading clearfix">
        <h4 style="margin:7px 0;" class="pull-left"><?php _e('Query Filter'); ?></h4>
        <?php echo $pager_html; ?>
    </div>
    <div class="panel-body">
        <div class="termItems clearfix">
            <?php if(!empty($termItems)) {
                foreach($termItems as $item) {
                    $item_class = '';
                    ?>
                    <div class="gm-img-thumbnail<?php echo $item_class; ?>" data-gmid="<?php echo $item->ID; ?>"><?php
                        ?><img src="<?php echo $gmCore->gm_get_media_image($item, 'thumb', false); ?>" alt="<?php echo $item->ID; ?>" title="<?php echo esc_attr($item->title); ?>"/><?php
                        ?><span class="label label-default">ID: <?php echo $item->ID; ?></span><?php
                        ?>
                        <div class="gm-img-thumb-title"><?php echo esc_html($item->title); ?></div>
                    </div>
                    <?php
                }
            } else {
                if($term_id) { ?>
                    <p class="text-center"><?php _e('No items with selected parameters.') ?></p>
                <?php } else { ?>
                    <p class="text-center"><?php _e('Set Filter parameters and click Save button to test query.') ?></p>
                <?php }
            } ?>
        </div>
    </div>
    <div class="panel-footer clearfix" style="margin-top:20px;"><?php echo $pager_html; ?>
        <?php if($trueTotalResult) { ?>
            <div class="well well-sm pull-left" style="margin-right:10px;"><?php printf(__('Limited to: %d'), $totalResult); ?></div>
            <div class="well well-sm pull-left" style="margin:0;"><?php printf(__('Total items: %d'), $trueTotalResult); ?></div>
        <?php } else { ?>
            <div class="well well-sm pull-left" style="margin:0;"><?php printf(__('Total items: %d'), $totalResult); ?></div>
        <?php } ?>
    </div>
</div>

<?php
do_action('gmedia_term_after_panel');
?>
