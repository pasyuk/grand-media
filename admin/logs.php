<?php
/**
 * Gmedia Logs
 */

// don't load directly
if( !defined('ABSPATH')){
    die('-1');
}

global $user_ID, $wpdb, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$gmedia_url          = $gmProcessor->url;
$gmedia_user_options = $gmProcessor->user_options;

if(isset($_GET['do_gmedia']) && 'clear_logs' == $_GET['do_gmedia']){
    check_admin_referer('gmedia_clear_logs', '_wpnonce_clear_logs');
    $wpdb->query("DELETE FROM {$wpdb->prefix}gmedia_log WHERE 1 = 1");
}


$gmedia_filter = array();

$openPage = !empty($_GET['pager'])? (int) $_GET['pager'] : 1;

$where   = '';
$search  = '';
$orderby = 'ORDER BY l.' . esc_sql($gmedia_user_options['orderby_gmedia_log']) . ' ' . esc_sql($gmedia_user_options['sortorder_gmedia_log']);
$lim     = '';

if(isset($_POST['filter_author'])){
    $authors        = $gmCore->_post('author_ids');
    $_GET['author'] = (int) $authors;
}
if(isset($_GET['author'])){
    $author                  = (int) $_GET['author'];
    $where                   .= "AND l.log_author = '{$author}' ";
    $gmedia_filter['author'] = $author;
}
if(isset($_GET['log_event'])){
    $where                      .= $wpdb->prepare("AND l.log = '%s' ", $_GET['log_event']);
    $gmedia_filter['log_event'] = $_GET['log_event'];
}
if(isset($_GET['s'])){
    $s = trim($_GET['s']);
    if('#' == substr($s, 0, 1)){
        $ids                     = wp_parse_id_list(substr($s, 1));
        $where                   .= ' AND l.ID IN (\'' . implode("','", $ids) . '\')';
        $s                       = false;
        $gmedia_filter['search'] = $s;
    }
    if( !empty($s)){
        // added slashes screw with quote grouping when done early, so done later
        $s = stripslashes($s);

        // split the words it a array if seperated by a space or comma
        preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
        $search_terms = array_map(function($a){ return trim($a, "\"'\n\r "); }, $matches[0]);

        $n         = '%';
        $searchand = '';

        foreach((array) $search_terms as $term){
            $term      = addslashes_gpc($term);
            $search    .= "{$searchand}(g.title LIKE '{$n}{$term}{$n}') OR (g.description LIKE '{$n}{$term}{$n}')";
            $searchand = ' AND ';
        }

        $term = esc_sql($s);
        if(count($search_terms) > 1 && $search_terms[0] != $s){
            $search .= " OR (g.title LIKE '{$n}{$term}{$n}') OR (g.description LIKE '{$n}{$term}{$n}')";
        }

        if( !empty($search)){
            $search = " AND ({$search}) ";
        }
        $gmedia_filter['search'] = $s;
    }
}

$limit = intval($gmedia_user_options['per_page_gmedia_log']);
if($limit > 0){
    $offset = ($openPage - 1) * $limit;
    $lim    = " LIMIT {$offset}, {$limit}";
}

$query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}gmedia_log AS l INNER JOIN {$wpdb->prefix}gmedia AS g ON g.ID = l.ID WHERE 1=1 $where $search $orderby $lim";
//echo '<pre>' . print_r($query, true) . '</pre>';
$logs        = $wpdb->get_results($query);
$totalResult = (int) $wpdb->get_var("SELECT FOUND_ROWS()");

if((1 > $limit) || (0 == $totalResult)){
    $limit = $totalResult;
    $pages = 1;
} else{
    $pages = ceil($totalResult / $limit);
}

$gmDB->pages    = $pages;
$gmDB->openPage = $openPage;
$gmedia_pager   = $gmDB->query_pager();

?>
<div class="panel panel-default panel-fixed-header" id="gmedia-panel">
    <div class="panel-heading-fake"></div>
    <div class="panel-heading clearfix" style="padding-bottom:2px;">
        <div class="pull-right" style="margin-bottom:3px;">
            <div class="clearfix">
                <?php include(GMEDIA_ABSPATH . 'admin/tpl/search-form.php'); ?>

                <div class="btn-toolbar pull-right" style="margin-bottom:4px; margin-left:4px;">
                    <?php if( !$gmProcessor->gmediablank){ ?>
                        <a title="<?php _e('More Screen Settings', 'grand-media'); ?>" class="show-settings-link pull-right btn btn-default btn-xs"><span class="glyphicon glyphicon-cog"></span></a>
                    <?php } ?>
                </div>
            </div>

            <?php echo $gmedia_pager; ?>

            <div class="spinner"></div>

        </div>
        <div class="btn-toolbar pull-left" style="margin-bottom:7px;">
            <div class="btn-group">
                <?php if( !empty($gmedia_filter)){ ?>
                    <a class="btn btn-warning" title="<?php _e('Reset Filter', 'grand-media'); ?>" rel="total" href="<?php echo gm_get_admin_url(array(), array(), $gmedia_url); ?>"><?php _e('Reset Filter', 'grand-media'); ?></a>
                <?php } else{ ?>
                    <button type="button" class="btn btn-default" data-toggle="dropdown"><?php _e('Filter', 'grand-media'); ?></button>
                <?php } ?>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                    <span class="sr-only"><?php _e('Toggle Dropdown', 'grand-media'); ?></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li role="presentation" class="dropdown-header"><?php _e('FILTER BY AUTHOR', 'grand-media'); ?></li>
                    <li class="gmedia_author<?php echo isset($gmedia_filter['author'])? ' active' : ''; ?>">
                        <a href="#libModal" data-modal="filter_author" data-action="gmedia_get_modal" class="gmedia-modal"><?php _e('Choose authors', 'grand-media'); ?></a>
                    </li>
                    <li role="presentation" class="dropdown-header"><?php _e('FILTER BY EVENT', 'grand-media'); ?></li>
                    <li class="gmedia_event<?php echo (isset($gmedia_filter['log_event']) && 'view' == $gmedia_filter['log_event'])? ' active' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('log_event' => 'view'), $gmedia_url) ?>"><?php _e('View / Play', 'grand-media'); ?></a>
                    </li>
                    <li class="gmedia_event<?php echo (isset($gmedia_filter['log_event']) && 'like' == $gmedia_filter['log_event'])? ' active' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('log_event' => 'like'), $gmedia_url) ?>"><?php _e('Like', 'grand-media'); ?></a>
                    </li>
                    <li class="gmedia_event<?php echo (isset($gmedia_filter['log_event']) && 'rate' == $gmedia_filter['log_event'])? ' active' : ''; ?>">
                        <a href="<?php echo add_query_arg(array('log_event' => 'rate'), $gmedia_url) ?>"><?php _e('Rate', 'grand-media'); ?></a>
                    </li>
                </ul>
            </div>
            <a class="btn btn-danger pull-left" href="<?php echo wp_nonce_url(gm_get_admin_url(array('do_gmedia' => 'clear_logs'), array(), $gmedia_url), 'gmedia_clear_logs', '_wpnonce_clear_logs') ?>" data-confirm="<?php _e("You are about to clear all Gmedia logs.\n\r'Cancel' to stop, 'OK' to clear.", "grand-media"); ?>"><?php _e('Clear Logs', 'grand-media'); ?></a>

        </div>

    </div>
    <form class="panel-body" id="gm-log-table" style="margin-bottom:4px;">
        <?php
        if(empty($gmGallery->options['license_key'])){
            ?>
            <div class="alert alert-warning" role="alert"><strong><?php _e('It\'s a premium feature. Gmedia Logger requires License Key.') ?></strong></div>
            <?php
        } elseif(!empty($gmGallery->options['disable_logs'])){
            ?>
            <div class="alert alert-warning" role="alert"><strong><?php _e('Gmedia Logger is disabled in settings.') ?></strong></div>
            <?php
        }
        ?>
        <div class="table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th><?php _e('Media', 'grand-media'); ?></th>
                    <th><?php _e('Info', 'grand-media'); ?></th>
                    <th><?php _e('Log Date', 'grand-media'); ?></th>
                    <th><?php _e('User / IP', 'grand-media'); ?></th>
                    <th><?php _e('Event', 'grand-media'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                if($logs){
                    foreach($logs as $item){
                        gmedia_item_more_data($item);
                        ?>
                        <tr>
                            <td style="width:150px;">
                                <div class="thumbnail" style="margin-bottom: 0;">
                                    <?php
                                    $images = $gmCore->gm_get_media_image($item, 'all');
                                    $thumb  = '<img class="gmedia-thumb" src="' . $images['thumb'] . '" alt=""/>';

                                    if( !empty($images['icon'])){
                                        $thumb .= '<img class="gmedia-typethumb" src="' . $images['icon'] . '" alt=""/>';
                                    }
                                    echo $thumb;
                                    ?>
                                </div>
                            </td>
                            <td>
                                <p class="media-meta"><span class="label label-default"><?php echo "#{$item->ID}"; ?>:</span> <b><?php echo esc_html($item->title); ?>&nbsp;</b></p>
                                <p class="media-meta">
                                    <span class="label label-default"><?php _e('Album', 'grand-media'); ?>:</span>
                                    <?php
                                    if($item->album){
                                        $terms_album = array();
                                        foreach($item->album as $c){
                                            $terms_album[] = esc_html($c->name);
                                        }
                                        $terms_album = join(', ', $terms_album);
                                    } else{
                                        $terms_album = '&#8212;';
                                    }
                                    echo $terms_album;
                                    ?>
                                    <br/><span class="label label-default"><?php _e('Category', 'grand-media'); ?>:</span>
                                    <?php
                                    if($item->categories){
                                        $terms_category = array();
                                        foreach($item->categories as $c){
                                            $terms_category[] = esc_html($c->name);
                                        }
                                        $terms_category = join(', ', $terms_category);
                                    } else{
                                        $terms_category = __('Uncategorized', 'grand-media');
                                    }
                                    echo $terms_category;
                                    ?>
                                    <br/><span class="label label-default"><?php _e('Tags', 'grand-media'); ?>:</span>
                                    <?php
                                    if($item->tags){
                                        $terms_tag = array();
                                        foreach($item->tags as $c){
                                            $terms_tag[] = esc_html($c->name);
                                        }
                                        $terms_tag = join(', ', $terms_tag);
                                    } else{
                                        $terms_tag = '&#8212;';
                                    }
                                    echo $terms_tag;
                                    ?>
                                </p>
                                <p class="media-meta">
                                    <span class="label label-default"><?php _e('Views / Likes', 'grand-media'); ?>:</span>
                                    <?php echo (isset($item->meta['views'][0])? $item->meta['views'][0] : '0') . ' / ' . (isset($item->meta['likes'][0])? $item->meta['likes'][0] : '0'); ?>

                                    <?php if(isset($item->meta['_rating'][0])){
                                        $ratings = maybe_unserialize($item->meta['_rating'][0]); ?>
                                        <br/><span class="label label-default"><?php _e('Rating', 'grand-media'); ?>:</span> <?php echo $ratings['value'] . ' / ' . $ratings['votes']; ?>
                                    <?php } ?>
                                    <br/><span class="label label-default"><?php _e('Type', 'grand-media'); ?>:</span> <?php echo $item->mime_type; ?>
                                    <br/><span class="label label-default"><?php _e('Filename', 'grand-media'); ?>:</span> <a href="<?php echo gm_get_admin_url(array('page' => 'GrandMedia', 'gmedia__in' => $item->ID), array(), $gmedia_url); ?>"><?php echo $item->gmuid; ?></a>
                                </p>
                            </td>
                            <td><p><?php echo $item->log_date; ?></p></td>
                            <td>
                                <p><?php
                                    $author_name = $item->log_author? get_user_option('display_name', $item->log_author) : __('Guest', 'grand-media');
                                    printf('<a class="gmedia-author" href="%s">%s</a>', esc_url(add_query_arg(array('author' => $item->log_author), $gmedia_url)), $author_name);
                                    ?></p>
                                <p class="media-meta"><span class="label label-default"><?php _e('IP Address', 'grand-media'); ?>:</span> <?php echo $item->ip_address; ?></p>
                            </td>
                            <td><p><?php
                                    switch($item->log){
                                        case 'view':
                                            _e('View / Play', 'grand-media');
                                            break;
                                        case 'like':
                                            _e('Like', 'grand-media');
                                            break;
                                        case 'rate':
                                            echo __('Rate', 'grand-media') . ": {$item->log_data}";
                                            break;
                                    }
                                    ?></p></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="5" style="font-weight: bold; text-align: center; padding: 30px 0;">' . __('No Records.', 'grand-media') . '</td></tr>';
                }
                ?>
                </tbody>

            </table>
        </div>
        <?php
        wp_original_referer_field(true, 'previous');
        wp_nonce_field('GmediaGallery');
        ?>
    </form>
</div>

<div class="modal fade gmedia-modal" id="libModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog"></div>
</div>
