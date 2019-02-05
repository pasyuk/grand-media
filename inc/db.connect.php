<?php

/**
 * Gmedia Database Class
 */
class GmediaDB{

    var $query; // User passed query
    var $sql_query; // User passed query
    var $filter = array(); // is there filter for get_gmedias()
    var $filter_tax = array(); // is there filter by taxonomy for get_gmedias()
    var $resultLimited = 0; // Total records for limited query (with offset)
    var $resultPerPage = 0; // Total records in each pages
    var $hardlimit = 0; // Hard limit query
    var $trueTotalResult = 0; // Total records in DB without limit
    var $totalResult = 0; // Total records in DB
    var $pages = 0; // Total number of pages required
    var $perPages = 0; // per page query
    var $openPage = 0; // currently opened page
    var $clauses; // query clauses
    var $gmedia; // first gmedia object

    /**
     * Get Wordpress Media
     *
     * @param array $arg
     *
     * @return object
     */
    function get_wp_media_lib($arg){
        /** @var $wpdb wpdb */
        global $wpdb, $gmCore;
        $default_arg = array('mime_type' => '', 'orderby' => 'ID', 'order' => '', 'limit' => '0', 'filter' => '', 'selected' => false, 's' => '');
        $arg         = array_merge($default_arg, $arg);
        /** @var $mime_type
         * @var  $orderby
         * @var  $order
         * @var  $limit
         * @var  $filter
         * @var  $s
         * @var  $selected
         */
        extract($arg);
        $and     = '';
        $ord     = '';
        $lim     = '';
        $search  = '';
        $sel_ids = array();
        if($selected){
            $sel_ids = wp_parse_id_list($selected);
        } else{
            $ckey = "gmedia_library:wpmedia";
            if(isset($_COOKIE[ $ckey ])){
                $sel_ids = array_filter(explode('.', $_COOKIE[ $ckey ]), 'is_numeric');
            }
        }
        switch($mime_type){
            case 'image':
                $and .= " AND post_mime_type REGEXP '^image(.*)'";
            break;
            case 'audio':
                $and .= " AND post_mime_type  REGEXP '^audio(.*)'";
            break;
            case 'video':
                $and .= " AND post_mime_type REGEXP '^video(.*)'";
            break;
            case 'application':
                $and .= " AND post_mime_type NOT REGEXP 'image|audio|video'";
            break;
        }
        if(!empty($mime_type)){
            $this->filter['mime_type'] = (array)$mime_type;
        }
        // If a search pattern is specified, load the posts that match
        if(!empty($s)){
            // added slashes screw with quote grouping when done early, so done later
            $s = stripslashes($s);

            // split the words it a array if seperated by a space or comma
            preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
            $search_terms = array_map(function($a){ return trim($a, "\"'\n\r "); }, $matches[0]);

            $n         = '%';
            $searchand = '';

            foreach((array)$search_terms as $term){
                $term = addslashes_gpc($term);
                $search .= "{$searchand}(($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_name LIKE '{$n}{$term}{$n}'))";
                $searchand = ' AND ';
            }

            $term = esc_sql($s);
            if(count($search_terms) > 1 && $search_terms[0] != $s){
                $search .= " OR ($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_name LIKE '{$n}{$term}{$n}')";
            }

            if(!empty($search)){
                $search            = " AND ({$search}) ";
                $this->filter['s'] = (array)$search_terms;
            }
        }
        if($orderby){
            switch($orderby){
                case 'ID':
                    $orderby = 'ID';
                    if(!$order){
                        $order = 'DESC';
                    }
                break;
                case 'filename':
                    $orderby = 'post_name';
                break;
                case 'title':
                    $orderby = 'post_title';
                break;
                case 'date':
                    $orderby = 'post_modified';
                break;
                case 'selected':
                    if(count($sel_ids) > 1){
                        $orderby = 'FIELD(ID, ' . join(', ', $sel_ids) . ')';
                    } else{
                        $orderby = 'ID';
                    }
                break;
                default:
                    $orderby = preg_replace('/[^a-z_]/', ' ', $orderby);
                break;
            }
            $ord .= " ORDER BY {$orderby}";
            $ord .= ($order == 'DESC')? ' DESC' : ' ASC';
        }
        switch($filter){
            case 'selected':
                if(count($sel_ids)){
                    $and .= ' AND ID IN (' . join(', ', $sel_ids) . ')';
                    $this->filter['post__in'] = $sel_ids;
                    break;
                } else{
                    $and .= ' AND ID = 0';
                    // todo: selected query error: No selected items
                }
            break;
            default:
                //$and .= "AND NOT EXISTS ( SELECT * FROM $wpdb->postmeta WHERE ($wpdb->postmeta.post_id = $wpdb->posts.ID) AND meta_key = '_gmedia_hidden' )";
            break;
        }
        $this->openPage = $gmCore->_get('pager', '1');
        $limit          = intval($limit);
        if($limit > 0){
            $offset = ($this->openPage - 1) * $limit;
            $lim    = " LIMIT {$offset}, {$limit}";
        }
        $this->sql_query   = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE post_type = 'attachment' {$and} {$search} GROUP BY ID {$ord} {$lim}";
        $this->query       = $wpdb->get_results($this->sql_query);
        $this->totalResult = (int)$wpdb->get_var("SELECT FOUND_ROWS()");
        if((1 > $limit) || (0 == $this->totalResult)){
            $limit       = $this->totalResult;
            $this->pages = 1;
        } else{
            $this->pages = ceil($this->totalResult / $limit);
        }
        if($this->openPage > $this->pages){
            $this->openPage = $this->pages;
            if($limit > 0){
                $limit  = intval($limit);
                $offset = ($this->openPage - 1) * $limit;
                $lim    = " LIMIT {$offset}, {$limit}";
            }
            $this->sql_query = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE post_type = 'attachment' {$and} {$search} GROUP BY ID {$ord} {$lim}";
            $this->query = $wpdb->get_results($this->sql_query);
        }
        $this->resultPerPage = count($this->query);

        return $this->query;
    }

    /**
     * @param $arg
     *
     * @return mixed
     */
    function count_wp_media($arg){
        /** @var $wpdb wpdb */
        global $wpdb;
        /** @var $filter
         * @var $selected
         * @var $s
         */
        extract($arg);
        $search = '';
        if(isset($selected)){
            $sel_ids = wp_parse_id_list($selected);
        } elseif(isset($_COOKIE["gmedia_library:wpmedia"])){
            $sel_ids = array_filter(explode('.', $_COOKIE["gmedia_library:wpmedia"]), 'is_numeric');
        } else{
            $sel_ids = array();
        }

        switch($filter){
            case 'selected':
                if(count($sel_ids)){
                    $filter = ' AND ID IN (' . join(', ', $sel_ids) . ')';
                    break;
                }
            default:
                //$filter = "AND NOT EXISTS ( SELECT * FROM $wpdb->postmeta WHERE ($wpdb->postmeta.post_id = $wpdb->posts.ID) AND meta_key = '_gmedia_hidden' )";
                $filter = '';
            break;
        }
        // If a search pattern is specified, load the posts that match
        if(!empty($s)){
            // added slashes screw with quote grouping when done early, so done later
            $s = stripslashes($s);

            // split the words it a array if seperated by a space or comma
            preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
            $search_terms = array_map(function($a){ return trim($a, "\"'\n\r "); }, $matches[0]);

            $n         = '%';
            $searchand = '';

            foreach((array)$search_terms as $term){
                $term = addslashes_gpc($term);
                $search .= "{$searchand}(($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_name LIKE '{$n}{$term}{$n}'))";
                $searchand = ' AND ';
            }

            $term = esc_sql($s);
            if(count($search_terms) > 1 && $search_terms[0] != $s){
                $search .= " OR ($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_name LIKE '{$n}{$term}{$n}')";
            }

            if(!empty($search)){
                $search = " AND ({$search}) ";
            }

        }
        $count = $wpdb->get_results("SELECT COUNT(*) as total,
						SUM(CASE WHEN post_mime_type LIKE 'image%' THEN 1 ELSE 0 END) as image,
						SUM(CASE WHEN post_mime_type LIKE 'audio%' THEN 1 ELSE 0 END) as audio,
						SUM(CASE WHEN post_mime_type LIKE 'video%' THEN 1 ELSE 0 END) as video,
						SUM(CASE WHEN post_mime_type LIKE 'text%' THEN 1 ELSE 0 END) as text,
						SUM(CASE WHEN post_mime_type LIKE 'application%' THEN 1 ELSE 0 END) as application
						FROM $wpdb->posts WHERE post_type = 'attachment' {$filter} {$search}", ARRAY_A);

        //$count[0]['hidden']      = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = '_gmedia_hidden' {$search}" );
        $count[0]['other'] = (int)$count[0]['text'] + (int)$count[0]['application'];

        return $count[0];
    }

    /**
     * @return mixed
     */
    function count_gmedia(){
        /** @var $wpdb wpdb */
        global $wpdb;
        /**
         * @var  $whichauthor
         * @var  $whichmimetype
         * @var  $groupby
         * @var  $orderby
         * @var  $limits
         */
        $join        = '';
        $where       = '';
        $whichauthor = '';
        if($this->clauses){
            extract($this->clauses, EXTR_OVERWRITE);
        }

        $count = $wpdb->get_results("SELECT count(DISTINCT {$wpdb->prefix}gmedia.ID) as total,
			(SELECT count(DISTINCT {$wpdb->prefix}gmedia.ID) FROM {$wpdb->prefix}gmedia $join WHERE {$wpdb->prefix}gmedia.mime_type LIKE 'image%' $where $whichauthor) as image,
			(SELECT count(DISTINCT {$wpdb->prefix}gmedia.ID) FROM {$wpdb->prefix}gmedia $join WHERE {$wpdb->prefix}gmedia.mime_type LIKE 'audio%' $where $whichauthor) as audio,
			(SELECT count(DISTINCT {$wpdb->prefix}gmedia.ID) FROM {$wpdb->prefix}gmedia $join WHERE {$wpdb->prefix}gmedia.mime_type LIKE 'video%' $where $whichauthor) as video,
			(SELECT count(DISTINCT {$wpdb->prefix}gmedia.ID) FROM {$wpdb->prefix}gmedia $join WHERE {$wpdb->prefix}gmedia.mime_type LIKE 'text%' $where $whichauthor) as text,
			(SELECT count(DISTINCT {$wpdb->prefix}gmedia.ID) FROM {$wpdb->prefix}gmedia $join WHERE {$wpdb->prefix}gmedia.mime_type LIKE 'application%' $where $whichauthor) as application
			 FROM {$wpdb->prefix}gmedia $join WHERE 1=1 $where $whichauthor
			", ARRAY_A);

        if($count){
            $count          = $count[0];
            $count['other'] = (int)$count['text'] + (int)$count['application'];
            $count['total'] = (int)$count['image'] + (int)$count['audio'] + (int)$count['video'] + (int)$count['other'];
        } else{
            $count = array('image'       => 0,
                           'audio'       => 0,
                           'video'       => 0,
                           'text'        => 0,
                           'application' => 0,
                           'other'       => 0,
                           'total'       => 0
            );
        }

        return $count;
    }

    /**
     * function to display the pagination
     * @return string
     */
    function query_pager(){
        if(empty($this->pages) || $this->pages == 1 || $this->resultLimited){
            return '';
        }
        $params = $_GET;
        foreach ($params as $key => $value) {
            if (strpos($key, '_wpnonce') !== false) {
                unset($params[$key]);
            }
        }
        unset($params['pager'], $params['do_gmedia'], $params['did_gmedia'], $params['do_gmedia_terms'], $params['did_gmedia_terms'], $params['ids'], $params['doing_wp_cron']);
        $new_query_string = http_build_query($params);
        //$self             = admin_url( 'admin.php?' . $new_query_string );
        $self = '?' . $new_query_string;
        if($this->openPage <= 0){
            $next = 2;
        } else{
            $next = $this->openPage + 1;
        }
        $prev = $this->openPage - 1;
        $last = $this->pages;
        //$total  = $this->totalResult;
        $result = '<div class="btn-toolbar pull-right gmedia-pager">';
        $result .= '<div class="btn-group btn-group-xs">';

        $li_class = ($this->openPage > 1)? '' : ' disabled';
        $result .= "<a class='btn btn-default{$li_class}' href='{$self}'><span class='glyphicon glyphicon-fast-backward'></span></a>";
        $result .= "<a class='btn btn-default{$li_class}' href='{$self}&pager=$prev'><span class='glyphicon glyphicon-step-backward'></span></a>";

        $result .= '</div>';

        $result .= '<form name="gmedia-pager" method="get" id="gmedia-pager" class="input-group btn-group input-group-xs" action="">';
        $result .= '<span class="input-group-addon">' . __("Page", "grand-media") . '</span>';
        foreach($params as $key => $value){
            $result .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
        $result .= '<input class="form-control pager_current_page allow-key-enter" name="pager" type="text" value="' . $this->openPage . '" /><span class="input-group-addon">' . __("of", "grand-media") . ' ' . $this->pages . '</span>';
        $result .= '</form>';

        $result .= '<div class="btn-group btn-group-xs">';
        $li_class = ($this->openPage < $this->pages)? '' : ' disabled';
        $result .= "<a class='btn btn-default{$li_class}' href='{$self}&amp;pager=$next'><span class='glyphicon glyphicon-step-forward'></span></a>";
        $result .= "<a class='btn btn-default{$li_class}' href='{$self}&amp;pager=$last'><span class='glyphicon glyphicon-fast-forward'></span></a>";
        $result .= '</div>';

        $result .= '</div>';

        return $result;
    }

    /**
     * Insert media.
     * If you set the 'ID' in the $object parameter, it will mean that you are
     * updating and attempt to update the media. You can also set the
     * media url or title by setting the key 'gmuid' or 'title'.
     * You can set the dates for the media manually by setting the 'date' key value.
     * The $object parameter can have the following:
     *   'author' - Default is current user ID. The ID of the user, who added the attachment.
     *   'mime_type' - Will be set to media. Do not override!!!
     *   'gmuid' - Filename.
     *   'description' - Media content.
     *   'date' - Date.
     *   'modified' - Date modified.
     *   'link' - Media external link.
     *   'status' - Status.
     * @uses $wpdb
     * @uses $user_ID
     * @uses do_action() Calls 'edit_gmedia' on $post_ID if this is an update.
     * @uses do_action() Calls 'add_gmedia' on $post_ID if this is not an update.
     * @see  wp_insert_attachment().
     *
     * @param string|array $object Arguments to override defaults.
     *
     * @return int Media ID.
     */
    function insert_gmedia($object){
        /** @var $wpdb wpdb */
        global $wpdb, $user_ID, $gmCore, $gmGallery;

        // TODO media status (vip, password?)
        $defaults = array('ID' => false, 'author' => $user_ID);
        $object   = wp_parse_args($object, $defaults);
        if(isset($object['title'])){
            $object['title'] = strip_tags($object['title'], '<span>');
            $object['title'] = $gmCore->mb_convert_encoding_utf8($object['title']);
        }
        if(isset($object['description'])){
            $object['description'] = $gmCore->clean_input($object['description']);
            $object['description'] = $gmCore->mb_convert_encoding_utf8($object['description']);
        }
        if(isset($object['link'])){
            $object['link'] = esc_url_raw($object['link']);
        }
        if(!isset($object['status']) || empty($object['status'])){
            $object['status'] = 'publish';
        }

        // export array as variables
        extract($object, EXTR_SKIP);

        // Are we updating or creating?
        $media_ID = 0;
        $update   = false;
        if(!empty($ID)){
            $media_ID = (int)$ID;
            $update   = true;
            if(isset($date) && empty($date)){
                unset($date);
            }
            $modified = current_time('mysql');
        } else{
            if(empty($date)){
                $date = current_time('mysql');
            }
            $modified = $date;
        }

        // expected_slashed (everything!)
        $data = compact(array('author', 'date', 'description', 'title', 'gmuid', 'modified', 'mime_type', 'link', 'status'));
        $data = stripslashes_deep($data);

        if($update){
            $wpdb->update($wpdb->prefix . 'gmedia', $data, array('ID' => $media_ID));
        } else{
            $wpdb->insert($wpdb->prefix . 'gmedia', $data);
            $media_ID = (int)$wpdb->insert_id;
        }

        wp_cache_delete($media_ID, 'gmedias');

        $gmedia    = $this->get_gmedia($media_ID);
        $post_ID   = $gmedia->post_id;
        $post_data = array('post_author'    => $gmedia->author,
                           'post_content'   => $gmedia->description,
                           'post_title'     => (trim($gmedia->title)? $gmedia->title : $gmedia->gmuid),
                           'post_status'    => $gmedia->status,
                           'post_type'      => 'gmedia',
                           'post_name'      => $gmedia->gmuid,
                           'post_date'      => $gmedia->date,
                           'post_modified'  => $gmedia->modified,
                           'post_mime_type' => $gmedia->mime_type
        );

        if(!empty($comment_status)){
            $post_data['comment_status'] = $comment_status;
        } elseif(!$update){
            $post_data['comment_status'] = $gmGallery->options['default_gmedia_comment_status'];
        }

        if(!empty($post_ID)){
            $post_data['ID'] = $post_ID;
            if(!wp_update_post($post_data)){
                unset($post_data['ID']);
                $post_ID = wp_insert_post($post_data);
                if($post_ID){
                    add_metadata('post', $post_ID, '_gmedia_ID', $media_ID);
                    $wpdb->update($wpdb->prefix . 'gmedia', array('post_id' => $post_ID), array('ID' => $media_ID));
                    wp_cache_delete($media_ID, 'gmedias');
                }
            }
        } else{
            $post_ID = wp_insert_post($post_data);
            if($post_ID){
                add_metadata('post', $post_ID, '_gmedia_ID', $media_ID);
                $wpdb->update($wpdb->prefix . 'gmedia', array('post_id' => $post_ID), array('ID' => $media_ID));
                wp_cache_delete($media_ID, 'gmedias');
            }
        }

        if(isset($terms) && is_array($terms) && count($terms)){
            foreach($terms as $taxonomy => $_terms){
                $taxonomy = trim($taxonomy);
                if(in_array($taxonomy, array('gmedia_tag', 'gmedia_category')) && !is_array($_terms)){
                    $_terms = explode(',', $_terms);
                } else{
                    $_terms = (array)$_terms;
                }
                if(!empty($taxonomy)){
                    $this->set_gmedia_terms($media_ID, $_terms, $taxonomy, $append = 0);
                }
            }
        }

        wp_cache_delete($media_ID, 'gmedia_meta');
        if(!empty($post_ID)){
            wp_cache_delete($post_ID, 'wpgmedias');
        }

        $this->clean_object_term_cache($media_ID);

        do_action('clean_gmedia_cache', $media_ID);

        if($update){
            do_action('edit_gmedia', $media_ID);
        } else{
            do_action('add_gmedia', $media_ID);
        }

        return $media_ID;
    }

    /**
     * Trashes or deletes gmedia.
     * When gmedia is deleted, the file will also be removed.
     * Deletion removes all gmedia meta fields, taxonomy, comments, etc. associated
     * with the gmedia.
     * @see  wp_delete_attachment()
     * @uses $wpdb
     * @uses do_action() Calls 'delete_gmedia' hook on gmedia ID.
     *
     * @param int  $gmedia_id            Gmedia ID.
     * @param bool $delete_original_file delete oroginal image?
     *
     * @return mixed False on failure. Gmedia data on success.
     */
    function delete_gmedia($gmedia_id, $delete_original_file = true){
        /** @var $wpdb wpdb */
        global $wpdb, $gmGallery, $gmCore;

        if(!$gmedia = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gmedia WHERE ID = %d", $gmedia_id))){
            return $gmedia;
        }

        $this->delete_gmedia_term_relationships($gmedia_id, array('gmedia_album', 'gmedia_tag', 'gmedia_category'));

        $gmedia_meta_ids = $wpdb->get_col($wpdb->prepare("SELECT meta_id FROM {$wpdb->prefix}gmedia_meta WHERE gmedia_id = %d ", $gmedia_id));
        if(!empty($gmedia_meta_ids)){
            do_action('delete_gmedia_meta', $gmedia_meta_ids);
            $in_gmedia_meta_ids = "'" . implode("', '", $gmedia_meta_ids) . "'";
            $wpdb->query("DELETE FROM {$wpdb->prefix}gmedia_meta WHERE meta_id IN($in_gmedia_meta_ids)");
            do_action('deleted_gmedia_meta', $gmedia_meta_ids);
        }

        do_action('delete_gmedia', $gmedia_id);
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}gmedia WHERE ID = %d", $gmedia_id));
        if(!empty($gmedia->post_id)){
            wp_delete_post($gmedia->post_id, true);
        }
        do_action('deleted_gmedia', $gmedia_id);


        if(!empty($files)){
            foreach($files as $cachefile){
                $cachefile = apply_filters('gm_delete_file', $cachefile);
                @unlink($cachefile);
            }
        }

        $type = strtok($gmedia->mime_type, '/');
        if('image' == $type){
            $folders = array($gmGallery->options['folder']['image'],
                             $gmGallery->options['folder']['image_thumb']
            );
            if($delete_original_file){
                $folders[] = $gmGallery->options['folder']['image_original'];
            }
            foreach($folders as $dir){
                $file = apply_filters('gm_delete_file', $gmCore->upload['path'] . '/' . $dir . '/' . $gmedia->gmuid);
                @unlink($file);
            }
        } else{
            if($delete_original_file){
                $dir = $gmCore->upload['path'] . '/' . $gmGallery->options['folder'][ $type ];

                $filepath = $dir . '/' . $gmedia->gmuid;
                $file     = apply_filters('gm_delete_file', $filepath);
                @unlink($file);
            }

            /*
			$files = glob( $filepath . '*', GLOB_NOSORT);
			if(!empty($files)){
				foreach($files as $file){
					$file = apply_filters('gm_delete_file', $file);
					@unlink($file);
				}
			}
			*/
        }

        wp_cache_delete($gmedia_id, 'gmedias');
        wp_cache_delete($gmedia_id, 'gmedia_meta');
        if(!empty($gmedia->post_id)){
            wp_cache_delete($gmedia->post_id, 'wpgmedias');
        }
        $this->clean_object_term_cache($gmedia_id);

        do_action('clean_gmedia_cache', $gmedia_id);

        return $gmedia;
    }

    /**
     * Will unlink the object from the taxonomy or taxonomies.
     * Will remove all relationships between the object and any terms in
     * a particular taxonomy or taxonomies. Does not remove the term or
     * taxonomy itself.
     * @see  wp_delete_object_term_relationships()
     * @uses $wpdb
     *
     * @param int          $object_id  The term Object Id that refers to the term
     * @param string|array $taxonomies List of Taxonomy Names or single Taxonomy name.
     */
    function delete_gmedia_term_relationships($object_id, $taxonomies){
        /** @var $wpdb wpdb */
        global $wpdb;

        $object_id = (int)$object_id;

        if(!is_array($taxonomies)){
            $taxonomies = array($taxonomies);
        }

        foreach((array)$taxonomies as $taxonomy){
            $term_ids    = $this->get_gmedia_terms($object_id, $taxonomy, array('fields' => 'ids'));
            $in_term_ids = "'" . implode("', '", $term_ids) . "'";
            do_action('delete_gmedia_term_relationships', $object_id, $term_ids);
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}gmedia_term_relationships WHERE gmedia_id = %d AND gmedia_term_id IN ($in_term_ids)", $object_id));
            do_action('deleted_gmedia_term_relationships', $object_id, $term_ids);
            $this->update_term_count($term_ids);
        }
    }

    /**
     * Generate gmedia meta data.
     * @see wp_generate_attachment_metadata()
     *
     * @param int   $media_id Gmedia Id to process.
     * @param array $fileinfo from fileinfo() function
     *
     * @return mixed Metadata for media.
     */
    function generate_gmedia_metadata($media_id, $fileinfo = array()){
        global $gmCore;
        $media    = $this->get_gmedia($media_id);
        $metadata = $gmCore->get_file_metadata($media->gmuid, $fileinfo);

        return apply_filters('generate_gmedia_metadata', $metadata, $media_id);

    }

    /**
     * Retrieves gmedia data given a gmedia ID or gmedia object.
     * @see  get_post()
     * @uses $wpdb
     *
     * @param int|object $media  Gmedia ID or gmedia object.
     * @param string     $output Optional, default is Object. Either OBJECT, ARRAY_A, or ARRAY_N.
     *
     * @return mixed Gmedia data
     */
    function get_gmedia($media, $output = OBJECT){
        /** @var $wpdb wpdb */
        global $wpdb;
        $null = null;

        if(empty($media)){
            return $null;
        } elseif(is_object($media)){
            $_media = $media;
            wp_cache_add($media->ID, $_media, 'gmedias');
            if(!empty($media->post_id)){
                wp_cache_add($media->post_id, $_media, 'wpgmedias');
            }
        } else{
            $media_id = (int)$media;
            if(!$_media = wp_cache_get($media_id, 'gmedias')){
                $_media = $wpdb->get_row($wpdb->prepare("SELECT {$wpdb->prefix}gmedia.*, wp.ID as post_id, wp.post_name, wp.post_password, wp.comment_status, wp.comment_count FROM {$wpdb->prefix}gmedia LEFT JOIN {$wpdb->posts} AS wp ON ({$wpdb->prefix}gmedia.post_id = wp.ID) WHERE {$wpdb->prefix}gmedia.ID = %d LIMIT 1", $media_id));
                if(!$_media){
                    return $null;
                }
                wp_cache_add($_media->ID, $_media, 'gmedias');
                if(!empty($_media->post_id)){
                    wp_cache_add($_media->post_id, $_media, 'wpgmedias');
                }
            }
        }

        if($output == OBJECT){
            return $_media;
        } elseif($output == ARRAY_A){
            $__media = get_object_vars($_media);

            return $__media;
        } elseif($output == ARRAY_N){
            $__media = array_values(get_object_vars($_media));

            return $__media;
        } else{
            return $_media;
        }
    }

    /**
     * Retrieves gmedia data given a post ID or post object.
     * @uses $wpdb
     *
     * @param int|object $post   Post ID or gmedia post object.
     * @param string     $output Optional, default is Object. Either OBJECT, ARRAY_A, or ARRAY_N.
     *
     * @return mixed Gmedia data
     */
    function get_post_gmedia($post, $output = OBJECT){
        /** @var $wpdb wpdb */
        global $wpdb;
        $null = null;

        if(empty($post)){
            return $null;
        }

        if(is_object($post)){
            $post_id = $post->ID;
        } else{
            $post_id = (int)$post;
        }

        if(!$_media = wp_cache_get($post_id, 'wpgmedias')){
            $_media = $wpdb->get_row($wpdb->prepare("SELECT {$wpdb->prefix}gmedia.*, wp.ID as post_id, wp.post_name, wp.post_password, wp.comment_status, wp.comment_count FROM {$wpdb->prefix}gmedia LEFT JOIN {$wpdb->posts} AS wp ON ({$wpdb->prefix}gmedia.post_id = wp.ID) WHERE {$wpdb->prefix}gmedia.post_id = %d LIMIT 1", $post_id));
            if(!$_media){
                return $null;
            }
            wp_cache_add($_media->ID, $_media, 'gmedias');
            if(!empty($_media->post_id)){
                wp_cache_add($_media->post_id, $_media, 'wpgmedias');
            }
        }

        if($output == OBJECT){
            return $_media;
        } elseif($output == ARRAY_A){
            $__media = get_object_vars($_media);

            return $__media;
        } elseif($output == ARRAY_N){
            $__media = array_values(get_object_vars($_media));

            return $__media;
        } else{
            return $_media;
        }
    }

    /**
     * Retrieve the gmedia posts based on query variables.
     * There are a few filters and actions that can be used to modify the gmedia
     * database query.
     * 'status' (string|array) - Gmedia status
     * 'author' (int) - Display or Exclude gmedias from several specific authors
     * 'author_name' (string) - Author name (nice_name)
     * 'cat' (int) - comma separated list of positive or negative category IDs. Display posts that have this category(ies)
     * 'category_name' (string) - Display posts that have this category, using category name
     * 'category__in' (array) - use category id. Same as 'cat', but does not accept negative values
     * 'category__not_in (array) - use category id. Exclude multiple categories
     * 'alb' (int) - comma separated list of positive or negative album IDs. Display posts that have this album(s)
     * 'album_name' (string) - Display posts that have this album, using album name
     * 'album__in' (array) - use album id. Same as 'alb'
     * 'album__not_in (array) - use album id. Exclude multiple albums
     * 'tag' (string) - use tag name. Display posts that have "either" of tags separated by comma.
     *         Display posts that have "all" of tags separated by '+'
     * 'tag_id' (int) - use tag id.
     * 'tag__and' (array) - use tag ids. Display posts that are tagged with all listed tags in array
     * 'tag__in' (array) - use tag ids. To display posts from either tags listed in array. Same as 'tag'
     * 'tag__not_in' (array) - use tag ids. Display posts that do not have any of the listed tag ids
     * 'tag_name__and' (array) - use tag names.
     * 'tag_name__in' (array) - use tag names.
     * 'terms_relation' (string) -  allows you to describe the boolean relationship between the taxonomy queries.
     *         Possible values are 'OR', 'AND'.
     * 'gmedia_id' (int) - use gmedia id.
     * 'wppost_id' (int) - use gmedia id.
     * 'name' (string) - use gmedia title.
     * 'gmedia__in' (array) - use gmedia ids. Specify posts to retrieve.
     * 'gmedia__not_in' (array) - use gmedia ids. Specify post NOT to retrieve.
     * 'wppost__in' (array) - use gmedia ids. Specify posts to retrieve.
     * 'wppost__not_in' (array) - use gmedia ids. Specify post NOT to retrieve.
     * 'per_page' (int) - number of post to show per page. Use 'per_page'=>-1 to show all posts.
     * 'nopaging' (bool) - show all posts or use pagination. Default value is 'false', use paging.
     * 'page' (int) - number of page. Show the posts that would normally show up just on page X.
     * 'offset' (int) - number of post to displace or pass over. Note: Setting offset parameter will ignore the 'page' parameter.
     * 'order' (string) - Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'
     * 'orderby' (string) - Sort retrieved posts by parameter. Defaults to 'ID'
     * - 'none' - No order.
     * - 'ID' - Order by gmedia id. Note the captialization.
     * - 'author' - Order by author.
     * - 'title' - Order by title.
     * - 'date' - Order by date.
     * - 'modified' - Order by last modified date.
     * - 'rand' - Random order.
     * - 'gmedia__in' - Order by 'gmedia__in' parameter. Note: 'gmedia__in' parameter must be specified.
     * - 'wppost__in' - Order by 'wppost__in' parameter. Note: 'wppost__in' parameter must be specified.
     * - 'meta_value' - Note that a 'meta_key=keyname' must also be present in the query. Note also that the sorting will be
     *         alphabetical which is fine for strings (i.e. words), but can be unexpected for numbers
     *         (e.g. 1, 3, 34, 4, 56, 6, etc, rather than 1, 3, 4, 6, 34, 56 as you might naturally expect).
     * - 'meta_value_num' - Order by numeric meta value. Also note that a 'meta_key=keyname' must also be present in the query.
     *         This value allows for numerical sorting as noted above in 'meta_value'.
     * 'm' (int) - Up to 14 numbers. YEAR(4) MONTH(2) DAYOFMONTH(2) HOUR(2) MINUTE(2) SECOND(2).
     *         Also you can query with 'year' (int) - 4 digit year; 'monthnum' (int) - Month number (from 1 to 12);
     *         'w' (int) - Week of the year (from 0 to 53); 'day' (int) - Day of the month (from 1 to 31);
     *         'hour' (int) - Hour (from 0 to 23); 'minute' (int) - Minute (from 0 to 60); 'second' (int) - Second (0 to 60).
     * 'meta_key' (string) - Custom field key.
     * 'meta_value' (string) - Custom field value.
     * 'meta_value_num' (number) - Custom field value.
     * 'meta_compare' (string) - Operator to test the 'meta_value'. Possible values are '!=', '>', '>=', '<', or '<='. Default value is '='.
     * 'meta_query' (array) - Custom field parameters (array of associative arrays):
     * - 'key' (string) The meta key
     * - 'value' (string|array) - The meta value (Note: Array support is limited to a compare value of 'IN', 'NOT IN', 'BETWEEN', or 'NOT BETWEEN')
     * - 'compare' (string) - (optional) How to compare the key to the value.
     *              Possible values: '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'.
     *              Default: '='
     * - 'type' (string) - (optional) The type of the value.
     *              Possible values: 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'.
     *              Default: 'CHAR'
     * 's' (string) - search string or terms separated by comma. Search exactly string if 'exact' parameter set to true
     * 'fields' (string) - 'ids': return an array of gmedia IDs; 'post_ids': return an array of gmedia IDs and post_id's
     * 'robots' - bool Default is empty
     * @see get_posts()
     * @return array List of posts.
     */
    function get_gmedias(){
        /** @var $wpdb wpdb */
        global $wpdb, $_wp_using_ext_object_cache;

        // First let's clear some variables
        $whichmimetype = '';
        $whichstatus   = '';
        $whichauthor   = '';
        $where         = '';
        $countwhere    = '';
        $limits        = '';
        $join          = '';
        $search        = '';
        $groupby       = '';
        $fields        = '';
        $page          = 1;
        $album         = array('order' => false,
                               'alias' => ''
        );
        $array         = array('null_tags' => false
        );

        $keys = array('error',
                      'status',
                      'author',
                      'author_name',
                      'cat',
                      'category_name',
                      'alb',
                      'album_name',
                      'tag',
                      'tag_id',
                      'terms_relation',
                      'gmedia_id',
                      'wppost_id',
                      'name',
                      'page',
                      'offset',
                      'm',
                      'year',
                      'monthnum',
                      'w',
                      'day',
                      'hour',
                      'minute',
                      'second',
                      'meta_key',
                      'meta_value',
                      's',
                      'fields',
                      'robots'
        );

        foreach($keys as $key){
            if(!isset($array[ $key ])){
                $array[ $key ] = '';
            }
        }

        $array_keys = array('category__in',
                            'category__not_in',
                            'category__and',
                            'album__in',
                            'album__not_in',
                            'gmedia__in',
                            'gmedia__not_in',
                            'wppost__in',
                            'wppost__not_in',
                            'tag__in',
                            'tag__not_in',
                            'tag__and',
                            'tag_name__in',
                            'tag_name__and',
                            'author__in',
                            'author__not_in',
                            'meta_query'
        );

        foreach($array_keys as $key){
            if(!isset($array[ $key ])){
                $array[ $key ] = array();
            }
        }

        $args = func_get_args();
        if(isset($args[0])){
            $q = array_merge($array, $args[0]);
        } else{
            $q = $array;
        }

        if(!empty($q['robots'])){
            $is_robots = true;
        }

        $q['gmedia_id'] = absint($q['gmedia_id']);
        $q['wppost_id'] = absint($q['wppost_id']);
        $q['year']      = absint($q['year']);
        $q['monthnum']  = absint($q['monthnum']);
        $q['day']       = absint($q['day']);
        $q['w']         = absint($q['w']);
        $q['m']         = absint($q['m']);
        $q['page']      = absint($q['page']);
        $q['cat']       = preg_replace('|[^0-9,-]|', '', $q['cat']); // comma separated list of positive or negative integers
        $q['alb']       = preg_replace('|[^0-9,-]|', '', $q['alb']); // comma separated list of positive or negative integers
        $q['name']      = trim($q['name']);
        if('' !== $q['hour']){
            $q['hour'] = absint($q['hour']);
        }
        if('' !== $q['minute']){
            $q['minute'] = absint($q['minute']);
        }
        if('' !== $q['second']){
            $q['second'] = absint($q['second']);
        }


        if(!isset($q['limit']) || empty($q['limit'])){
            $q['limit'] = 0;
        }
        $q['limit'] = absint($q['limit']);

        if(!isset($q['per_page']) || empty($q['per_page'])){
            $q['per_page'] = - 1;
        }
        if(!isset($q['nopaging'])){
            if($q['per_page'] == - 1){
                $q['nopaging'] = true;
            } else{
                $q['nopaging'] = false;
            }
        }
        $q['per_page'] = (int)$q['per_page'];
        if($q['per_page'] < - 1){
            $q['per_page'] = abs($q['per_page']);
        }

        // If true, forcibly turns off SQL_CALC_FOUND_ROWS even when limits are present.
        if(isset($q['no_found_rows'])){
            $q['no_found_rows'] = (bool)$q['no_found_rows'];
        } else{
            $q['no_found_rows'] = false;
        }

        switch($q['fields']){
            case 'ids':
                $fields = "{$wpdb->prefix}gmedia.ID";
            break;
            case 'post_ids':
                $fields = "{$wpdb->prefix}gmedia.ID, {$wpdb->prefix}gmedia.post_id";
            break;
            default:
                $fields = "{$wpdb->prefix}gmedia.*";
                $fields .= ", wp.ID as post_id, wp.post_name, wp.post_password, wp.comment_status, wp.comment_count";
        }
        $join .= " LEFT JOIN {$wpdb->posts} AS wp ON ({$wpdb->prefix}gmedia.post_id = wp.ID)";

        // If a month is specified in the querystring, load that month
        if($q['m']){
            $q['m'] = '' . preg_replace('|[^0-9]|', '', $q['m']);
            $where .= " AND YEAR({$wpdb->prefix}gmedia.date)=" . substr($q['m'], 0, 4);
            if(strlen($q['m']) > 5){
                $where .= " AND MONTH({$wpdb->prefix}gmedia.date)=" . substr($q['m'], 4, 2);
            }
            if(strlen($q['m']) > 7){
                $where .= " AND DAYOFMONTH({$wpdb->prefix}gmedia.date)=" . substr($q['m'], 6, 2);
            }
            if(strlen($q['m']) > 9){
                $where .= " AND HOUR({$wpdb->prefix}gmedia.date)=" . substr($q['m'], 8, 2);
            }
            if(strlen($q['m']) > 11){
                $where .= " AND MINUTE({$wpdb->prefix}gmedia.date)=" . substr($q['m'], 10, 2);
            }
            if(strlen($q['m']) > 13){
                $where .= " AND SECOND({$wpdb->prefix}gmedia.date)=" . substr($q['m'], 12, 2);
            }
            $this->filter['m'] = $q['m'];
        }

        if('' !== $q['hour']){
            $where .= " AND HOUR({$wpdb->prefix}gmedia.date)='" . $q['hour'] . "'";
            $this->filter['hour'] = $q['hour'];
        }

        if('' !== $q['minute']){
            $where .= " AND MINUTE({$wpdb->prefix}gmedia.date)='" . $q['minute'] . "'";
            $this->filter['minute'] = $q['minute'];
        }

        if('' !== $q['second']){
            $where .= " AND SECOND({$wpdb->prefix}gmedia.date)='" . $q['second'] . "'";
            $this->filter['second'] = $q['second'];
        }

        if($q['year']){
            $where .= " AND YEAR({$wpdb->prefix}gmedia.date)='" . $q['year'] . "'";
            $this->filter['year'] = $q['year'];
        }

        if($q['monthnum']){
            $where .= " AND MONTH({$wpdb->prefix}gmedia.date)='" . $q['monthnum'] . "'";
            $this->filter['monthnum'] = $q['monthnum'];
        }

        if($q['day']){
            $where .= " AND DAYOFMONTH({$wpdb->prefix}gmedia.date)='" . $q['day'] . "'";
            $this->filter['day'] = $q['day'];
        }

        if($q['w']){
            $where .= ' AND ' . _wp_mysql_week("`{$wpdb->prefix}gmedia`.`date`") . " = '" . $q['w'] . "'";
            $this->filter['w'] = $q['w'];
        }

        if('' != $q['name']){
            $q['name'] = esc_sql($q['name']);
            $where .= " AND {$wpdb->prefix}gmedia.title = '" . $q['name'] . "'";
            $this->filter['name'] = $q['name'];
        }

        // If a gmedia number is specified, load that gmedia
        if($q['gmedia_id']){
            $where .= " AND {$wpdb->prefix}gmedia.ID = " . $q['gmedia_id'];
            $this->filter['gmedia__in'] = (array)$q['gmedia_id'];
        } elseif($q['gmedia__in']){
            if(!is_array($q['gmedia__in'])){
                $q['gmedia__in'] = explode(',', $q['gmedia__in']);
            }
            $gmedia__in                 = array_filter(array_map('absint', $q['gmedia__in']));
            $this->filter['gmedia__in'] = $gmedia__in;
            $gmedia__in                 = implode(',', $gmedia__in);
            if($gmedia__in){
                $where .= " AND {$wpdb->prefix}gmedia.ID IN ($gmedia__in)";
            }
        } elseif($q['gmedia__not_in']){
            if(!is_array($q['gmedia__not_in'])){
                $q['gmedia__not_in'] = explode(',', $q['gmedia__not_in']);
            }
            $gmedia__not_in                 = array_filter(array_map('absint', $q['gmedia__not_in']));
            $this->filter['gmedia__not_in'] = $gmedia__not_in;
            $gmedia__not_in                 = implode(',', $gmedia__not_in);
            if($gmedia__not_in){
                $where .= " AND {$wpdb->prefix}gmedia.ID NOT IN ($gmedia__not_in)";
            }
        }

        // If a linked wp post number is specified, load that gmedia
        if($q['wppost_id']){
            $where .= " AND {$wpdb->prefix}gmedia.post_id = " . $q['wppost_id'];
            $this->filter['wppost__in'] = (array)$q['wppost_id'];
        } elseif($q['wppost__in']){
            if(!is_array($q['wppost__in'])){
                $q['wppost__in'] = explode(',', $q['wppost__in']);
            }
            $wppost__in                 = array_filter(array_map('absint', $q['wppost__in']));
            $this->filter['wppost__in'] = $wppost__in;
            $wppost__in                 = implode(',', $wppost__in);
            if($wppost__in){
                $where .= " AND {$wpdb->prefix}gmedia.post_id IN ($wppost__in)";
            }
        } elseif($q['wppost__not_in']){
            if(!is_array($q['wppost__not_in'])){
                $q['wppost__not_in'] = explode(',', $q['wppost__not_in']);
            }
            $wppost__not_in                 = array_filter(array_map('absint', $q['wppost__not_in']));
            $this->filter['wppost__not_in'] = $wppost__not_in;
            $wppost__not_in                 = implode(',', $wppost__not_in);
            if($wppost__not_in){
                $where .= " AND {$wpdb->prefix}gmedia.post_id NOT IN ($wppost__not_in)";
            }
        }

        // If a search pattern is specified, load the posts that match
        if(!empty($q['s'])){
            // added slashes screw with quote grouping when done early, so done later
            $q['s'] = stripslashes($q['s']);
            if(empty($q['exact'])){
                $q['search_terms'] = array_filter(array_map('trim', explode(' ', $q['s'])));
            } else{
                $q['search_terms'] = $q['s'];
            }
            $n         = '%';
            $searchand = '';
            foreach((array)$q['search_terms'] as $term){
                $term = esc_sql(addcslashes($term, '_%\\'));
                $search .= "{$searchand}(({$wpdb->prefix}gmedia.title LIKE '{$n}{$term}{$n}') OR ({$wpdb->prefix}gmedia.description LIKE '{$n}{$term}{$n}') OR ({$wpdb->prefix}gmedia.gmuid LIKE '{$n}{$term}{$n}'))";
                $searchand = ' AND ';
            }

            if(!empty($search)){
                $search = " AND ({$search}) ";
                /* TODO not display private media when user not logged in
				if ( !is_user_logged_in() )
					$search .= " AND ({$wpdb->prefix}gmedia_meta.password = '') ";
				*/
                $this->filter['s'] = (array)$q['search_terms'];
            }
        }

        // Category stuff
        $this->gmedias_category_stuff($q);

        if(!empty($q['category__and']) && 1 === count((array)$q['category__and'])){
            $q['category__and'] = (array)$q['category__and'];
            if(!isset($q['category__in'])){
                $q['category__in'] = array();
            }
            $q['category__in'][] = absint(reset($q['category__and']));
            unset($q['category__and']);
        }

        if(!empty($q['category__in'])){
            $tax_query[] = array('taxonomy' => 'gmedia_category',
                                 'terms'    => $q['category__in'],
                                 'operator' => 'IN'
            );
        }

        if(!empty($q['category__not_in'])){
            $tax_query[] = array('taxonomy' => 'gmedia_category',
                                 'terms'    => $q['category__not_in'],
                                 'operator' => 'NOT IN'
            );
        }

        if(!empty($q['category__and'])){
            $tax_query[] = array('taxonomy' => 'gmedia_category',
                                 'terms'    => $q['category__and'],
                                 'operator' => 'AND'
            );
        }

        // Album stuff
        $this->gmedias_album_stuff($q);

        if(!empty($q['album__in'])){
            $tax_query[] = array('taxonomy' => 'gmedia_album',
                                 'terms'    => $q['album__in'],
                                 'operator' => 'IN'
            );
            if(1 == count($q['album__in'])){
                $album['order'] = true;
            }
        }

        if(!empty($q['album__not_in'])){
            $tax_query[] = array('taxonomy' => 'gmedia_album',
                                 'terms'    => $q['album__not_in'],
                                 'operator' => 'NOT IN'
            );
        }

        // Tag stuff
        $this->gmedias_tag_stuff($q);

        if(!empty($q['tag__in'])){
            $tax_query[] = array('taxonomy' => 'gmedia_tag',
                                 'terms'    => $q['tag__in'],
                                 'operator' => 'IN'
            );
        }

        if(!empty($q['tag__not_in'])){
            $tax_query[] = array('taxonomy' => 'gmedia_tag',
                                 'terms'    => $q['tag__not_in'],
                                 'operator' => 'NOT IN'
            );
        }

        if(!empty($q['tag__and'])){
            $tax_query[] = array('taxonomy' => 'gmedia_tag',
                                 'terms'    => $q['tag__and'],
                                 'operator' => 'AND'
            );
        }

        if(!empty($q['tag_name__in']) || (isset($q['tag_name__in_null']) && $q['null_tags'])){
            $tax_query[] = array('taxonomy' => 'gmedia_tag',
                                 'terms'    => $q['tag_name__in'],
                                 'operator' => 'IN'
            );
        }

        if(!empty($q['tag_name__and']) || (isset($q['tag_name__and_null']) && $q['null_tags'])){
            $tax_query[] = array('taxonomy' => 'gmedia_tag',
                                 'terms'    => $q['tag_name__and'],
                                 'operator' => 'AND'
            );
        }

        if(!empty($tax_query)){
            if(isset($q['terms_relation']) && strtoupper($q['terms_relation']) == 'AND'){
                $terms_relation = 'AND';
            } else{
                $terms_relation = 'OR';
            }
            $clauses['join']  = '';
            $clauses['where'] = array();
            $i                = 0;
            foreach($tax_query as $query){
                /** @var $taxonomy
                 * @var  $terms
                 * @var  $field
                 * @var  $operator
                 * @var  $include_children
                 */
                extract($query);

                $taxterm = str_replace('gmedia_', '', $taxonomy);

                if('IN' == $operator){

                    if(empty($terms)){
                        continue;
                    }

                    $this->filter["{$taxterm}__in"] = $terms;
                    $terms                          = implode(',', $terms);

                    $alias = $i? 'tr' . $i : 'tr';

                    $clauses['join'] .= " INNER JOIN {$wpdb->prefix}gmedia_term_relationships AS $alias";
                    $clauses['join'] .= " ON ({$wpdb->prefix}gmedia.ID = $alias.gmedia_id)";

                    $clauses['where'][] = "$alias.gmedia_term_id $operator ($terms)";

                    if($album['order'] && ('gmedia_album' == $taxonomy)){
                        $album['alias'] = $alias;
                        if('ids' != $q['fields'] || 'post_ids' != $q['fields']){
                            $fields .= ", $alias.*";
                        }
                    }
                } elseif('NOT IN' == $operator){

                    if(empty($terms)){
                        continue;
                    }

                    $this->filter["{$taxterm}__not_in"] = $terms;
                    $terms                              = implode(',', $terms);

                    $clauses['where'][] = "{$wpdb->prefix}gmedia.ID NOT IN (
						SELECT gmedia_id
						FROM {$wpdb->prefix}gmedia_term_relationships
						WHERE gmedia_term_id IN ($terms)
					)";
                } elseif('AND' == $operator){

                    if(empty($terms)){
                        continue;
                    }

                    $num_terms = count($terms);

                    $this->filter["{$taxterm}__and"] = $terms;
                    $terms                           = implode(',', $terms);

                    $clauses['where'][] = "(
						SELECT COUNT(1)
						FROM {$wpdb->prefix}gmedia_term_relationships
						WHERE gmedia_term_id IN ($terms)
						AND gmedia_id = {$wpdb->prefix}gmedia.ID
					) = $num_terms";
                }

                $i ++;
            }

            if(!empty($clauses['where'])){
                $clauses['where'] = ' AND ( ' . implode(" $terms_relation ", $clauses['where']) . ' )';
            } else{
                $clauses['where'] = '';
            }

            $join .= $clauses['join'];
            $where .= $clauses['where'];
        }

        // Meta stuff
        $meta_query = array();
        // Simple query needs to be first for orderby=meta_value to work correctly
        foreach(array('key', 'compare', 'type') as $key){
            if(!empty($q["meta_$key"])){
                $meta_query[0][ $key ] = $q["meta_$key"];
            }
        }
        // Query sets 'meta_value' = '' by default
        if(isset($q['meta_value']) && !empty($q['meta_value'])){
            $meta_query[0]['value'] = $q['meta_value'];
        }

        if(!empty($q['meta_query']) && is_array($q['meta_query'])){
            $meta_query = array_merge($meta_query, $q['meta_query']);
        }
        if(!empty($meta_query)){
            $primary_table     = $wpdb->prefix . 'gmedia';
            $primary_id_column = 'ID';
            $meta_table        = $wpdb->prefix . 'gmedia_meta';
            $meta_id_column    = 'gmedia_id';

            if(isset($meta_query['relation']) && strtoupper($meta_query['relation']) == 'OR'){
                $relation = 'OR';
            } else{
                $relation = 'AND';
            }
            $meta_query = array_filter($meta_query, 'is_array');

            $clauses['join']  = array();
            $clauses['where'] = array();

            foreach($meta_query as $key => $query){
                if(!isset($query['key']) || empty($query['key'])){
                    continue;
                }
                $meta_key  = trim($query['key']);
                $meta_type = isset($query['type'])? strtoupper($query['type']) : 'CHAR';

                if('NUMERIC' == $meta_type){
                    $meta_type = 'SIGNED';
                } elseif(!in_array($meta_type, array('BINARY',
                                                     'CHAR',
                                                     'DATE',
                                                     'DATETIME',
                                                     'DECIMAL',
                                                     'SIGNED',
                                                     'TIME',
                                                     'UNSIGNED'
                ))
                ){
                    $meta_type = 'CHAR';
                }

                $i     = count($clauses['join']);
                $alias = $i? 'mt' . $i : $meta_table;

                // Set JOIN
                $clauses['join'][ $i ] = "INNER JOIN $meta_table";
                $clauses['join'][ $i ] .= $i? " AS $alias" : '';
                $clauses['join'][ $i ] .= " ON ($primary_table.$primary_id_column = $alias.$meta_id_column)";

                $clauses['where'][ $key ] = '';
                if(!empty($meta_key)){
                    $clauses['where'][ $key ] = $wpdb->prepare("$alias.meta_key = %s", $meta_key);
                }

                if(!isset($query['value'])){
                    if(empty($clauses['where'][ $key ])){
                        unset($clauses['join'][ $i ]);
                    }
                    continue;
                }

                $meta_value = $query['value'];

                $meta_compare = is_array($meta_value)? 'IN' : '=';
                if(isset($query['compare'])){
                    $meta_compare = strtoupper($query['compare']);
                }

                if(!in_array($meta_compare, array('=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'))){
                    $meta_compare = '=';
                }

                if(in_array($meta_compare, array('IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'))){
                    if(!is_array($meta_value)){
                        $meta_value = preg_split('/[,\s]+/', $meta_value);
                    }

                    if(empty($meta_value)){
                        unset($clauses['join'][ $i ]);
                        continue;
                    }
                } else{
                    $meta_value = trim($meta_value);
                }

                if('IN' == substr($meta_compare, - 2)){
                    $meta_compare_string = '(' . substr(str_repeat(',%s', count($meta_value)), 1) . ')';
                } elseif('BETWEEN' == substr($meta_compare, - 7)){
                    $meta_value          = array_slice($meta_value, 0, 2);
                    $meta_compare_string = '%s AND %s';
                } elseif('LIKE' == substr($meta_compare, - 4)){
                    $meta_value          = '%' . addcslashes($meta_value, '_%\\') . '%';
                    $meta_compare_string = '%s';
                } else{
                    $meta_compare_string = '%s';
                }

                if(!empty($clauses['where'][ $key ])){
                    $clauses['where'][ $key ] .= ' AND ';
                }

                $clauses['where'][ $key ] = ' (' . $clauses['where'][ $key ] . $wpdb->prepare("CAST($alias.meta_value AS {$meta_type}) {$meta_compare} {$meta_compare_string})", $meta_value);
            }

            $clauses['where'] = array_filter($clauses['where']);

            if(empty($clauses['where'])){
                $clauses['where'] = '';
            } else{
                $clauses['where'] = ' AND (' . implode("\n{$relation} ", $clauses['where']) . ' )';
            }

            $clauses['join'] = implode("\n", $clauses['join']);
            if(!empty($clauses['join'])){
                $clauses['join'] = ' ' . $clauses['join'];
            }

            $join .= $clauses['join'];
            $where .= $clauses['where'];

            $this->filter['meta_query'] = $meta_query;
        }
        unset($clauses);

        if(!empty($tax_query) || !empty($meta_query)){
            $groupby = "{$wpdb->prefix}gmedia.ID";
        }

        // Status
        if($q['status']){
            $q['status']            = array_map('esc_sql', array_unique((array)$q['status']));
            $this->filter['status'] = $q['status'];
            $status                 = "'" . str_replace(",", "','", implode(",", $q['status'])) . "'";
            $whichstatus .= " AND {$wpdb->prefix}gmedia.status IN ({$status})";
        }

        // Author/user stuff for ID
        if(!empty($q['author']) && $q['author'] != '0'){
            $q['author'] = addslashes_gpc('' . urldecode($q['author']));
            $authors     = array_unique(array_map('intval', preg_split('/[,\s]+/', $q['author'])));
            foreach($authors as $author){
                $key         = $author > 0? 'author__in' : 'author__not_in';
                $q[ $key ][] = abs($author);
            }
            $q['author'] = implode(',', $authors);
        }

        if(!empty($q['author__not_in'])){
            $author__not_in                 = array_map('absint', array_unique(wp_parse_id_list($q['author__not_in'])));
            $this->filter['author__not_in'] = $author__not_in;
            $author__not_in                 = implode(',', $author__not_in);
            $whichauthor .= " AND {$wpdb->prefix}gmedia.author NOT IN ($author__not_in) ";
        } elseif(!empty($q['author__in'])){
            $author__in                 = array_map('absint', array_unique(wp_parse_id_list($q['author__in'])));
            $this->filter['author__in'] = $author__in;
            $author__in                 = implode(',', $author__in);
            $whichauthor .= " AND {$wpdb->prefix}gmedia.author IN ($author__in) ";
        }

        // Author stuff for name
        if('' != $q['author_name']){
            $q['author_name'] = esc_sql($q['author_name']);
            $author           = get_user_by('slug', $q['author_name']);
            if($author){
                $q['author'] = $author->ID;
                $whichauthor .= " AND ({$wpdb->prefix}gmedia.author = " . absint($q['author']) . ')';
                $this->filter['author__in'] = $q['author'];
            }
        }

        // MIME-Type stuff
        if(isset($q['mime_type']) && !empty($q['mime_type'])){
            if(is_string($q['mime_type'])){
                $q['mime_type'] = array_map('trim', explode(',', $q['mime_type']));
            }
            $whichmimetype             = $this->gmedia_mime_type_where($q['mime_type'], $wpdb->prefix . 'gmedia');
            $this->filter['mime_type'] = $q['mime_type'];
        }

        $where .= $whichstatus . $search;

	    $empty_order = empty($q['order']);
        if($empty_order || ((strtoupper($q['order']) != 'ASC') && (strtoupper($q['order']) != 'DESC'))){
            $q['order'] = 'DESC';
        }

        // Order by
        if(empty($q['orderby']) || ('none' == $q['orderby'])){
            $orderby = "{$wpdb->prefix}gmedia.ID " . $q['order'];
        } else{
            // Used to filter values
            $allowed_keys = array('ID', 'author', 'date', 'title', 'filename', 'gmuid', 'modified', 'mime_type', 'gmedia__in', 'rand', 'comment_count');
            if($album['order'] && !empty($album['alias'])){
                $allowed_keys[] = 'custom';
            }
            $q['orderby'] = urldecode($q['orderby']);
            $q['orderby'] = addslashes_gpc($q['orderby']);
            if(in_array($q['orderby'], array('_created_timestamp', 'views', 'likes', '_size'))){
                $q['meta_key'] = $q['orderby'];
                $q['orderby'] = 'meta_value_num';
                $join .= " LEFT JOIN {$wpdb->prefix}gmedia_meta ON ({$wpdb->prefix}gmedia.ID = {$wpdb->prefix}gmedia_meta.gmedia_id AND " . $wpdb->prepare("{$wpdb->prefix}gmedia_meta.meta_key = %s", $q['meta_key']) . ")";
            }
            if(!empty($q['meta_key'])){
                $allowed_keys[] = $q['meta_key'];
                $allowed_keys[] = 'meta_value';
                $allowed_keys[] = 'meta_value_num';
            }
            if(in_array($q['orderby'], array('custom', 'title', 'date', 'modified', 'comment_count', 'meta_value', 'meta_value_num'))){
                $q['orderby'] .= ' ID.DESC';
                $allowed_keys[] = 'ID.DESC';
            }
            $orderby_array = array();
            foreach(explode(' ', $q['orderby']) as $_orderby){
                // Only allow certain values for safety
                if(!in_array($_orderby, $allowed_keys)){
                    continue;
                }

                switch($_orderby){
                    case 'rand':
                        $orderby = 'RAND()';
                    break;
                    case $q['meta_key']:
                    case 'meta_value':
                        $orderby = "{$wpdb->prefix}gmedia_meta.meta_value";
                    break;
                    case 'meta_value_num':
                        $orderby = "{$wpdb->prefix}gmedia_meta.meta_value+0";
                    break;
                    case 'gmedia__in':
                        if(count($q['gmedia__in']) > 1){
                            $orderby = "FIELD({$wpdb->prefix}gmedia.ID, " . join(', ', $q['gmedia__in']) . ")";
                        } else{
                            $orderby = "{$wpdb->prefix}gmedia.ID";
                        }
	                    if($empty_order) {
		                    $q['order'] = 'ASC';
	                    }
		                break;
                    case 'filename':
                        $orderby = "{$wpdb->prefix}gmedia.gmuid";
                    break;
                    case 'custom':
                        $orderby = "{$album['alias']}.gmedia_order";
                    break;
                    case 'comment_count':
                        $orderby = "wp.comment_count";
                    break;
                    case 'ID.DESC':
                        $orderby = "{$wpdb->prefix}gmedia.ID";
                    break;
                    default:
                        $orderby = "{$wpdb->prefix}gmedia." . $_orderby;
                }

                if('ID.DESC' !== $_orderby){
                    $orderby .= " {$q['order']}";
                } else{
                    $orderby .= " DESC";
                }
                $orderby_array[] = $orderby;
            }
            $orderby = implode(', ', $orderby_array);

            if(empty($orderby)){
                $orderby = "{$wpdb->prefix}gmedia.ID " . $q['order'];
            }
        }

        // Paging
        $page = $q['page'];
        if(empty($page)){
            $page = 1;
        }
        if(empty($q['nopaging'])){
            if($q['limit'] && ($q['per_page'] > $q['limit'])){
                $q['per_page'] = $q['limit'];
            }
            if(empty($q['offset']) && ((0 != $q['offset']) || ('0' != $q['offset']))){
                $per_page = $q['per_page'];
                $offset   = ($page - 1) * $per_page;
                if($q['limit'] && (($offset + $per_page) > $q['limit'])){
                    $per_page = $q['limit'] - $offset;
                    if(0 > $per_page){
                        $per_page = 0;
                    }
                }
                $pgstrt = $offset . ', ';
                $limits = 'LIMIT ' . $pgstrt . $per_page;
            } else{ // we're ignoring $page and using 'offset'
                $q['offset']         = absint($q['offset']);
                $pgstrt              = $q['offset'] . ', ';
                $limits              = 'LIMIT ' . $pgstrt . $q['per_page'];
                $this->resultLimited = true;
            }
        } elseif($q['limit']){
            $limits = 'LIMIT ' . $q['limit'];
        }

        // Announce current selection parameters.  For use by caching plugins.
        do_action('gmedia_selection', $where . $whichauthor . $whichmimetype . $groupby . $orderby . $limits . $join);

        if(!empty($groupby)){
            $groupby = 'GROUP BY ' . $groupby;
        }
        if(!empty($orderby)){
            $orderby = 'ORDER BY ' . $orderby;
        }

        $found_rows = '';
        if(!$q['no_found_rows'] && !empty($limits)){
            $found_rows = 'SQL_CALC_FOUND_ROWS';
        }

        $this->sql_query = "SELECT $found_rows $fields FROM {$wpdb->prefix}gmedia $join WHERE 1=1 $where $whichauthor $whichmimetype $groupby $orderby $limits";

        $clauses       = compact('join', 'where', 'whichauthor', 'whichmimetype', 'groupby', 'orderby', 'limits');
        $this->clauses = $clauses;

        if('ids' == $q['fields']){
            $gmedias = $wpdb->get_col($this->sql_query);

            return $gmedias;
        }

        $gmedias = $wpdb->get_results($this->sql_query);

        if('post_ids' == $q['fields']){

            return $gmedias;
        }

        $gmedia_count = count($gmedias);

        if(!$q['no_found_rows'] && !empty($limits)){
            $this->totalResult = (int)$wpdb->get_var('SELECT FOUND_ROWS()');
            if($q['limit']){
                $this->hardlimit       = $q['limit'];
                $this->trueTotalResult = $this->totalResult;
                if($this->totalResult > $q['limit']){
                    $this->totalResult = $q['limit'];
                }
            }
        } else{
            $this->totalResult     = $gmedia_count;
            $this->trueTotalResult = $this->totalResult;
        }
        if($q['per_page'] && (- 1 != $q['per_page'])){
            $this->pages    = ceil($this->totalResult / $q['per_page']);
            $this->perPages = (int)$q['per_page'];
        } else{
            $this->pages    = 1;
            $this->perPages = $gmedia_count;
        }
        $this->openPage      = $page;
        $this->resultPerPage = $gmedia_count;

        if(!isset($q['cache_results'])){
            if($_wp_using_ext_object_cache){
                $q['cache_results'] = false;
            } else{
                $q['cache_results'] = true;
            }
        }

        if(!isset($q['update_gmedia_term_cache'])){
            $q['update_gmedia_term_cache'] = true;
        }

        if(!isset($q['update_gmedia_meta_cache'])){
            $q['update_gmedia_meta_cache'] = true;
        }

        if($q['cache_results']){
            $this->update_gmedia_caches($gmedias, $q['update_gmedia_term_cache'], $q['update_gmedia_meta_cache']);
        }

        if($gmedia_count > 0){
            $this->gmedia = $gmedias[0];
        }
        $this->query = $gmedias;

        return $gmedias;
    }

    /**
     * @return array
     */
    public function get_duplicates(){
        global $wpdb;

//		$sql = "SELECT gmedia_id, meta_value, COUNT(*) AS dups FROM {$wpdb->prefix}gmedia_meta GROUP BY meta_value HAVING dups > 1";
        $sql = "SELECT DISTINCT g.gmedia_id, g.meta_value, g2.dupe_count
				FROM {$wpdb->prefix}gmedia_meta AS g
				JOIN (
					SELECT count(*) AS dupe_count, meta_value
					FROM {$wpdb->prefix}gmedia_meta
					WHERE meta_key = '_hash'
					GROUP BY meta_value
					HAVING dupe_count > 1
				) AS g2 ON g.meta_value = g2.meta_value ORDER BY g.meta_value, g.gmedia_id ASC;";

        $duplicate_ids    = array();
        $duplicate_hashes = array();
        $duplicate_select = array();

        $duplicates = $wpdb->get_results($sql);

        if($duplicates){
            foreach($duplicates as $dups){
                $duplicate_ids[] = $dups->gmedia_id;
                if(!isset($duplicate_hashes[ $dups->meta_value ])){
                    $duplicate_hashes[ $dups->meta_value ] = $dups->dupe_count;
                } else{
                    $duplicate_select[] = $dups->gmedia_id;
                }
            }
            $duplicate_ids = array_reverse($duplicate_ids);
        }

        return compact('duplicate_ids', 'duplicate_select', 'duplicate_hashes');
    }

    /**
     * Category stuff for gmedia query.
     *
     * @param $q
     */
    function gmedias_category_stuff(&$q){
        global $wpdb;

        if(isset($q['category_name']) && !empty($q['category_name'])){
            $q['category_name'] = "'" . esc_sql($q['category_name']) . "'";
            $cat                = $wpdb->get_var("
					SELECT term_id
					FROM {$wpdb->prefix}gmedia_term
					WHERE taxonomy = 'gmedia_category'
					AND name = {$q['category_name']}
				");
            if($cat){
            	unset($q['category_name']);
                $q['category__in'][] = $cat;
            }
        }
        if(isset($q['cat'])){
            if(!empty($q['cat']) && ('0' !== $q['cat']) && (0 !== $q['cat'])){
                $q['cat']  = '' . urldecode($q['cat']) . '';
                $q['cat']  = addslashes_gpc($q['cat']);
                $cat_array = preg_split('/[,\s]+/', $q['cat']);
                $q['cat']  = '';
                $req_cats  = array();
                foreach((array)$cat_array as $cat){
                    $cat        = intval($cat);
                    $req_cats[] = $cat;
                    $in         = ($cat >= 0);
                    $cat        = abs($cat);
                    if($in){
                        $q['category__in'][] = $cat;
                    } else{
                        $q['category__not_in'][] = $cat;
                    }
                }
                $q['cat'] = implode(',', $req_cats);
            } elseif(('0' === $q['cat']) || (0 === $q['cat'])){
                $q['category__not_in'] = $this->get_terms('gmedia_category', array('fields' => 'ids'));
                $q['without_category'] = true;
            }
        }

        if(isset($q['category__in']) && (!empty($q['category__in']) || ('0' === $q['category__in']) || (0 === $q['category__in']))){
            $q['category__in'] = wp_parse_id_list($q['category__in']);
            if(in_array(0, $q['category__in'])){
                $q['category__in']     = array_filter($q['category__in']);
                $q['category__not_in'] = array_diff($this->get_terms('gmedia_category', array('fields' => 'ids')), $q['category__in']);
                $q['without_category'] = true;
                if(!empty($q['category__in'])){
                    $q['with_category__in'] = $q['category__in'];
                }
                $q['category__in'] = array();
            }
        }
        if(isset($q['category__not_in']) && (!empty($q['category__not_in']) || ('0' === $q['category__not_in']) || (0 === $q['category__not_in']))){
            $q['category__not_in'] = wp_parse_id_list($q['category__not_in']);
            if(in_array(0, $q['category__not_in'])){
                $q['category__not_in'] = array_filter($q['category__not_in']);
                $q['category__in']     = array_diff($this->get_terms('gmedia_category', array('fields' => 'ids')), $q['category__not_in']);
                $q['within_category']  = true;
                if(!empty($q['category__not_in'])){
                    $q['with_category__not_in'] = $q['category__not_in'];
                }
                $q['category__not_in'] = array();
            }
        }
        if(isset($q['category__and']) && !empty($q['category__and'])){
            $q['category__and'] = wp_parse_id_list($q['category__and']);
        }

    }

    /**
     * Album stuff for gmedia query.
     *
     * @param $q
     */
    function gmedias_album_stuff(&$q){
        global $wpdb;

        if(isset($q['album_name']) && !empty($q['album_name'])){
            $q['album_name'] = "'" . esc_sql($q['album_name']) . "'";
            $alb             = $wpdb->get_var("
					SELECT term_id
					FROM {$wpdb->prefix}gmedia_term
					WHERE taxonomy = 'gmedia_album'
					AND name = {$q['album_name']}
				");
            if($alb){
            	unset($q['album_name']);
                $q['album__in'][] = $alb;
            }
        }
        if(isset($q['alb'])){
            if(!empty($q['alb']) && ('0' !== $q['alb']) && (0 !== $q['alb'])){
                $q['alb']  = '' . urldecode($q['alb']) . '';
                $q['alb']  = addslashes_gpc($q['alb']);
                $alb_array = preg_split('/[,\s]+/', $q['alb']);
                $q['alb']  = '';
                $req_albs  = array();
                foreach((array)$alb_array as $alb){
                    if(!($alb = intval($alb))){
                        continue;
                    }
                    $in  = ($alb >= 0);
                    $alb = abs($alb);
                    if($in){
                        /*if(isset($q['album__status'])){
                            $alb_obj = $this->get_term($alb, 'gmedia_album');
                            if(empty($alb_obj) || (is_wp_error($alb_obj) || !in_array($alb_obj->status, (array) $q['album__status']))){
                                continue;
                            }
                        }*/
                        $q['album__in'][] = $alb;
                        $req_albs[]       = $alb;
                    } else{
                        $q['album__not_in'][] = $alb;
                        $req_albs[]           = - $alb;
                    }
                }
                $q['alb'] = implode(',', $req_albs);
            } elseif(('0' === $q['alb']) || (0 === $q['alb'])){
                $q['album__not_in'] = $this->get_terms('gmedia_album', array('fields' => 'ids'));
                $q['without_album'] = true;
            }
        }

        if(isset($q['album__in']) && (!empty($q['album__in']) || ('0' === $q['album__in']) || (0 === $q['album__in']))){
            $q['album__in'] = wp_parse_id_list($q['album__in']);
            $without_album  = in_array(0, $q['album__in'])? true : false;
            if(isset($q['album__status'])){
                $q['album__in'] = $this->get_terms('gmedia_album', array('fields' => 'ids', 'orderby' => 'include', 'include' => $q['album__in'], 'status' => $q['album__status']));
            }
            if($without_album){
                $q['album__in']     = array_filter($q['album__in']);
                $q['album__not_in'] = array_diff($this->get_terms('gmedia_album', array('fields' => 'ids')), $q['album__in']);
                $q['without_album'] = true;
                if(!empty($q['album__in'])){
                    $q['with_album__in'] = $q['album__in'];
                }
                $q['album__in'] = array();
            }
        }
        if(isset($q['album__not_in']) && (!empty($q['album__not_in']) || ('0' === $q['album__not_in']) || (0 === $q['album__not_in']))){
            $q['album__not_in'] = wp_parse_id_list($q['album__not_in']);
            if(in_array(0, $q['album__not_in'])){
                $q['album__not_in'] = array_filter($q['album__not_in']);
                if(isset($q['album__status'])){
                    $q['album__in'] = array_diff($this->get_terms('gmedia_album', array('fields' => 'ids', 'status' => $q['album__status'])), $q['album__not_in']);
                } else{
                    $q['album__in'] = array_diff($this->get_terms('gmedia_album', array('fields' => 'ids')), $q['album__not_in']);
                }
                $q['within_album'] = true;
                if(!empty($q['album__not_in'])){
                    $q['with_album__not_in'] = $q['album__not_in'];
                }
                $q['album__not_in'] = array();
            }
        }
    }

    /**
     * Tag stuff for gmedia query.
     *
     * @param $q
     */
    function gmedias_tag_stuff(&$q){
        global $wpdb;

        if(isset($q['tag']) && '' != $q['tag']){
            if(strpos($q['tag'], ',') !== false){
                $tags = preg_split('/[,\s]+/', $q['tag']);
                foreach((array)$tags as $tag){
                    $q['tag_name__in'][] = $tag;
                }
            } else if(preg_match('/[+\s]+/', $q['tag']) || !empty($q['alb'])){
                $tags = preg_split('/[+\s]+/', $q['tag']);
                foreach((array)$tags as $tag){
                    $q['tag_name__and'][] = $tag;
                }
            } else{
                $q['tag_name__in'][] = $q['tag'];
            }
        }

	    if(isset($q['tag_name__in']) && !empty($q['tag_name__in'])){
		    $q['tag_name__in'] = "'" . implode("','", array_map('esc_sql', array_unique((array)$q['tag_name__in']))) . "'";
		    $tag__in = $wpdb->get_col("
					SELECT term_id
					FROM {$wpdb->prefix}gmedia_term
					WHERE taxonomy = 'gmedia_tag'
					AND name IN ({$q['tag_name__in']})
				");
		    if(!empty($tag__in)){
			    unset($q['tag_name__in']);
			    $q['tag__in'] = $tag__in;
		    }
	    }

	    if(isset($q['tag_name__and']) && !empty($q['tag_name__and'])){
		    $q['tag_name__and'] = "'" . implode("','", array_map('esc_sql', array_unique((array)$q['tag_name__and']))) . "'";
		    $tag__and = $wpdb->get_col("
					SELECT term_id
					FROM {$wpdb->prefix}gmedia_term
					WHERE taxonomy = 'gmedia_tag'
					AND name IN ({$q['tag_name__and']})
				");
		    if(!empty($tag__and)){
			    unset($q['tag_name__and']);
			    $q['tag__and'] = $tag__and;
		    }
	    }

        if(isset($q['tag__in']) && !empty($q['tag__in'])){
            $q['tag__in'] = wp_parse_id_list($q['tag__in']);
        } elseif(isset($q['tag_id']) && !empty($q['tag_id'])){
            $q['tag__in'] = array(absint($q['tag_id']));
        }


        if(isset($q['tag__not_in']) && !empty($q['tag__not_in'])){
            $q['tag__not_in'] = wp_parse_id_list($q['tag__not_in']);
        }

        if(isset($q['tag__and']) && !empty($q['tag__and'])){
            $q['tag__and'] = wp_parse_id_list($q['tag__and']);
        }
    }

    /**
     * Convert MIME types into SQL.
     * @see wp_post_mime_type_where()
     *
     * @param string|array $mime_types  List of mime types or comma separated string of mime types.
     * @param string       $table_alias Optional. Specify a table alias, if needed.
     *
     * @return string The SQL AND clause for mime searching.
     */
    function gmedia_mime_type_where($mime_types, $table_alias = ''){
        $where     = '';
        $wildcards = array('', '%', '%/%');
        if(is_string($mime_types)){
            $mime_types = array_map('trim', explode(',', $mime_types));
        }
        foreach((array)$mime_types as $mime_type){
            $mime_type = preg_replace('/\s/', '', $mime_type);
            $slashpos  = strpos($mime_type, '/');
            if(false !== $slashpos){
                $mime_group    = preg_replace('/[^-*.a-zA-Z0-9]/', '', substr($mime_type, 0, $slashpos));
                $mime_subgroup = preg_replace('/[^-*.+a-zA-Z0-9]/', '', substr($mime_type, $slashpos + 1));
                if(empty($mime_subgroup)){
                    $mime_subgroup = '*';
                } else{
                    $mime_subgroup = str_replace('/', '', $mime_subgroup);
                }
                $mime_pattern = "$mime_group/$mime_subgroup";
            } else{
                $mime_pattern = preg_replace('/[^-*.a-zA-Z0-9]/', '', $mime_type);
                if(false === strpos($mime_pattern, '*')){
                    $mime_pattern .= '/*';
                }
            }

            $mime_pattern = preg_replace('/\*+/', '%', $mime_pattern);

            if(in_array($mime_type, $wildcards)){
                return '';
            }

            if(false !== strpos($mime_pattern, '%')){
                $wheres[] = empty($table_alias)? "mime_type LIKE '$mime_pattern'" : "$table_alias.mime_type LIKE '$mime_pattern'";
            } else{
                $wheres[] = empty($table_alias)? "mime_type = '$mime_pattern'" : "$table_alias.mime_type = '$mime_pattern'";
            }
        }
        if(!empty($wheres)){
            $where = ' AND (' . join(' OR ', $wheres) . ') ';
        }

        return $where;
    }

    /**
     * Some postmeta stuff.
     *
     * @param int    $gmedia
     * @param string $meta_type
     *
     * @return mixed
     */
    function has_meta($gmedia, $meta_type = 'gmedia'){
        global $wpdb;

        if(!in_array($meta_type, array('gmedia', 'gmedia_term'))){
            $meta_type = 'gmedia';
        }

        return $wpdb->get_results($wpdb->prepare("SELECT *
			FROM {$wpdb->prefix}{$meta_type}_meta WHERE {$meta_type}_id = %d
			ORDER BY meta_key,meta_id", $gmedia), ARRAY_A);
    }

    /**
     * Add metadata for the specified object.
     * @see  add_metadata()
     * @uses $wpdb WordPress database object for queries.
     * @uses do_action() Calls 'added_{$meta_type}_meta' with meta_id of added metadata entry,
     *       object ID, meta key, and meta value
     *
     * @param string $meta_type  Type of object metadata is for (e.g., gmedia, gmedia_term)
     * @param int    $object_id  ID of the object metadata is for
     * @param string $meta_key   Metadata key
     * @param string $meta_value Metadata value
     * @param bool   $unique     Optional, default is false.  Whether the specified metadata key should be
     *                           unique for the object.  If true, and the object already has a value for the specified
     *                           metadata key, no change will be made
     *
     * @return bool The meta ID on successful update, false on failure.
     */
    function add_metadata($meta_type, $object_id, $meta_key, $meta_value, $unique = false){
        if(!$meta_type || !$meta_key || !in_array($meta_type, array('gmedia', 'gmedia_term')) || !is_numeric($object_id)){
            return false;
        }

        if(!$object_id = absint($object_id)){
            return false;
        }

        /** @var $wpdb wpdb */
        global $wpdb;

        $table = $wpdb->prefix . $meta_type . '_meta';

        $column = esc_sql($meta_type . '_id');

        // expected_slashed ($meta_key)
        $meta_key   = wp_unslash($meta_key);
        $meta_value = wp_unslash($meta_value);
        $meta_value = sanitize_meta($meta_key, $meta_value, $meta_type);

        $check = apply_filters("{$meta_type}_add_metadata", null, $object_id, $meta_key, $meta_value, $unique);
        if(null !== $check){
            return $check;
        }

        if($unique && $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id))){
            return false;
        }

        $_meta_value = $meta_value;
        $meta_value  = maybe_serialize($meta_value);

        do_action("{$meta_type}_add_meta", $object_id, $meta_key, $_meta_value);

        $result = $wpdb->insert($table, array($column      => $object_id,
                                              'meta_key'   => $meta_key,
                                              'meta_value' => $meta_value
        ));

        if(!$result){
            return false;
        }

        $mid = (int)$wpdb->insert_id;

        wp_cache_delete($object_id, $meta_type . '_meta');

        do_action("{$meta_type}_added_meta", $mid, $object_id, $meta_key, $_meta_value);

        return $mid;
    }

    /**
     * Update metadata for the specified object.  If no value already exists for the specified object
     * ID and metadata key, the metadata will be added.
     * @see  update_metadata()
     * @uses $wpdb WordPress database object for queries.
     * @uses do_action() Calls 'update_{$meta_type}_meta' before updating metadata with meta_id of
     *       metadata entry to update, object ID, meta key, and meta value
     * @uses do_action() Calls 'updated_{$meta_type}_meta' after updating metadata with meta_id of
     *       updated metadata entry, object ID, meta key, and meta value
     *
     * @param string       $meta_type  Type of object metadata is for (e.g., gmedia, gmedia_term)
     * @param int          $object_id  ID of the object metadata is for
     * @param string       $meta_key   Metadata key
     * @param string|array $meta_value Metadata value
     * @param string       $prev_value Optional.  If specified, only update existing metadata entries with
     *                                 the specified value.  Otherwise, update all entries.
     *
     * @return bool True on successful update, false on failure.
     */
    function update_metadata($meta_type, $object_id, $meta_key, $meta_value, $prev_value = ''){
        if(!$meta_type || !$meta_key || !in_array($meta_type, array('gmedia', 'gmedia_term')) || !is_numeric($object_id)){
            return false;
        }

        if(!$object_id = absint($object_id)){
            return false;
        }

        /** @var $wpdb wpdb */
        global $wpdb;

        $table = $wpdb->prefix . $meta_type . '_meta';

        $column    = esc_sql($meta_type . '_id');
        $id_column = 'meta_id';

        // expected_slashed ($meta_key)
        $meta_key     = stripslashes($meta_key);
        $passed_value = $meta_value;
        $meta_value   = stripslashes_deep($meta_value);
        $meta_value   = sanitize_meta($meta_key, $meta_value, $meta_type);

        $check = apply_filters("{$meta_type}_update_metadata", null, $object_id, $meta_key, $meta_value, $prev_value);
        if(null !== $check){
            return (bool)$check;
        }

        if(!$meta_id = $wpdb->get_var($wpdb->prepare("SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id))){
            return $this->add_metadata($meta_type, $object_id, $meta_key, $passed_value);
        }

        // Compare existing value to new value if no prev value given and the key exists only once.
        if(empty($prev_value)){
            $old_value = $this->get_metadata($meta_type, $object_id, $meta_key);
            if(count($old_value) == 1){
                if($old_value[0] === $meta_value){
                    return false;
                }
            }
        }

        $_meta_value = $meta_value;
        $meta_value  = maybe_serialize($meta_value);

        $data  = compact('meta_value');
        $where = array($column => $object_id, 'meta_key' => $meta_key);

        if(!empty($prev_value)){
            $prev_value          = maybe_serialize($prev_value);
            $where['meta_value'] = $prev_value;
        }

        do_action("{$meta_type}_update_meta", $meta_id, $object_id, $meta_key, $_meta_value);

        $wpdb->update($table, $data, $where);

        wp_cache_delete($object_id, $meta_type . '_meta');

        do_action("{$meta_type}_updated_meta", $meta_id, $object_id, $meta_key, $_meta_value);

        return true;
    }

    /**
     * Delete metadata for the specified object.
     * @see  delete_metadata()
     * @uses $wpdb WordPress database object for queries.
     * @uses do_action() Calls 'deleted_{$meta_type}_meta' after deleting with meta_id of
     *       deleted metadata entries, object ID, meta key, and meta value
     *
     * @param string $meta_type  Type of object metadata is for (e.g., gmedia, gmedia_term)
     * @param int    $object_id  ID of the object metadata is for
     * @param string $meta_key   Metadata key
     * @param string $meta_value Optional. Metadata value.  If specified, only delete metadata entries
     *                           with this value.  Otherwise, delete all entries with the specified meta_key.
     * @param bool   $delete_all Optional, default is false.  If true, delete matching metadata entries
     *                           for all objects, ignoring the specified object_id.  Otherwise, only delete matching
     *                           metadata entries for the specified object_id.
     *
     * @return bool True on successful delete, false on failure.
     */
    function delete_metadata($meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = false){
        if(!$meta_type || !$meta_key || !in_array($meta_type, array('gmedia', 'gmedia_term')) || !is_numeric($object_id)){
            return false;
        }

        if((!$object_id = absint($object_id)) && !$delete_all){
            return false;
        }

        /** @var $wpdb wpdb */
        global $wpdb;

        $table = $wpdb->prefix . $meta_type . '_meta';

        $type_column = esc_sql($meta_type . '_id');
        $id_column   = 'meta_id';
        // expected_slashed ($meta_key)
        $meta_key   = stripslashes($meta_key);
        $meta_value = stripslashes_deep($meta_value);

        $check = apply_filters("{$meta_type}_delete_metadata", null, $object_id, $meta_key, $meta_value, $delete_all);
        if(null !== $check){
            return (bool)$check;
        }

        $_meta_value = $meta_value;
        $meta_value  = maybe_serialize($meta_value);

        $query = $wpdb->prepare("SELECT $id_column FROM $table WHERE meta_key = %s", $meta_key);

        if(!$delete_all){
            $query .= $wpdb->prepare(" AND $type_column = %d", $object_id);
        }

        if($meta_value){
            $query .= $wpdb->prepare(" AND meta_value = %s", $meta_value);
        }

        $meta_ids = $wpdb->get_col($query);
        if(!count($meta_ids)){
            return false;
        }

        /** @var $object_ids */
        if($delete_all){
            $object_ids = $wpdb->get_col($wpdb->prepare("SELECT $type_column FROM $table WHERE meta_key = %s", $meta_key));
        }

        do_action("delete_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value);

        $query = "DELETE FROM $table WHERE $id_column IN( " . implode(',', $meta_ids) . " )";

        $count = $wpdb->query($query);

        if(!$count){
            return false;
        }

        if($delete_all){
            foreach((array)$object_ids as $o_id){
                wp_cache_delete($o_id, $meta_type . '_meta');
            }
        } else{
            wp_cache_delete($object_id, $meta_type . '_meta');
        }

        do_action("{$meta_type}_deleted_meta", $meta_ids, $object_id, $meta_key, $_meta_value);

        return true;
    }

    /**
     * Retrieve metadata for the specified object.
     * @see get_metadata()
     *
     * @param string $meta_type Type of object metadata is for (e.g., gmedia, or gmedia_term)
     * @param int    $object_id ID of the object metadata is for
     * @param string $meta_key  Optional.  Metadata key.  If not specified, retrieve all metadata for
     *                          the specified object.
     * @param bool   $single    Optional, default is false.  If true, return only the first value of the
     *                          specified meta_key.  This parameter has no effect if meta_key is not specified.
     *
     * @return string|array Single metadata value, or array of values
     */
    function get_metadata($meta_type, $object_id, $meta_key = '', $single = false){
        if(!$meta_type || !in_array($meta_type, array('gmedia', 'gmedia_term')) || !is_numeric($object_id)){
            return false;
        }

        if(!$object_id = absint($object_id)){
            return false;
        }

        $check = apply_filters("{$meta_type}_get_metadata", null, $object_id, $meta_key, $single);
        if(null !== $check){
            if($single && is_array($check)){
                return $check[0];
            } else{
                return $check;
            }
        }

        $meta_cache = wp_cache_get($object_id, $meta_type . '_meta');

        if(!$meta_cache){
            $meta_cache = $this->update_meta_cache($meta_type, array($object_id));
            $meta_cache = $meta_cache[ $object_id ];
        }

        if(!$meta_key){
            global $gmCore;
            $meta_cache = $gmCore->array_map_recursive('maybe_unserialize', $meta_cache);

            return $meta_cache;
        }

        if(isset($meta_cache[ $meta_key ])){
            if($single){
                return maybe_unserialize($meta_cache[ $meta_key ][0]);
            } else{
                return array_map('maybe_unserialize', $meta_cache[ $meta_key ]);
            }
        }

        if($single){
            return '';
        } else{
            return array();
        }
    }

    /**
     * Get meta data by meta ID
     * @since 1.6.3
     *
     * @param string $meta_type Type of object metadata is for (e.g., gmedia, gmedia_term)
     * @param int    $meta_id   ID for a specific meta row
     *
     * @return object Meta object or false.
     */
    function get_metadata_by_mid($meta_type, $meta_id){
        global $wpdb;

        if(!$meta_type || !in_array($meta_type, array('gmedia', 'gmedia_term')) || !is_numeric($meta_id)){
            return false;
        }

        $meta_id = absint($meta_id);
        if(!$meta_id){
            return false;
        }

        $table = $wpdb->prefix . $meta_type . '_meta';

        $meta = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE meta_id = %d", $meta_id));

        if(empty($meta)){
            return false;
        }

        if(isset($meta->meta_value)){
            $meta->meta_value = maybe_unserialize($meta->meta_value);
        }

        return $meta;
    }

    /**
     * Update meta data by meta ID
     * @since 1.6.3
     *
     * @param string      $meta_type  Type of object metadata is for (e.g., gmedia, gmedia_term)
     * @param int         $meta_id    ID for a specific meta row
     * @param string      $meta_value Metadata value
     * @param string|bool $meta_key   Optional, you can provide a meta key to update it
     *
     * @return bool True on successful update, false on failure.
     */
    function update_metadata_by_mid($meta_type, $meta_id, $meta_value, $meta_key = false){
        global $wpdb;

        // Make sure everything is valid.
        if(!$meta_type || !in_array($meta_type, array('gmedia', 'gmedia_term')) || !is_numeric($meta_id)){
            return false;
        }

        $meta_id = absint($meta_id);
        if(!$meta_id){
            return false;
        }

        $table = $wpdb->prefix . $meta_type . '_meta';

        $column    = sanitize_key($meta_type . '_id');
        $id_column = 'meta_id';

        // Fetch the meta and go on if it's found.
        if(($meta = $this->get_metadata_by_mid($meta_type, $meta_id))){
            $original_key = $meta->meta_key;
            $object_id    = $meta->{$column};

            // If a new meta_key (last parameter) was specified, change the meta key,
            // otherwise use the original key in the update statement.
            if(false === $meta_key){
                $meta_key = $original_key;
            } elseif(!is_string($meta_key)){
                return false;
            }

            // Sanitize the meta
            $_meta_value = $meta_value;
            $meta_value  = sanitize_meta($meta_key, $meta_value, $meta_type);
            $meta_value  = maybe_serialize($meta_value);

            // Format the data query arguments.
            $data = array('meta_key'   => $meta_key,
                          'meta_value' => $meta_value
            );

            // Format the where query arguments.
            $where               = array();
            $where[ $id_column ] = $meta_id;

            do_action("{$meta_type}_update_meta", $meta_id, $object_id, $meta_key, $_meta_value);

            // Run the update query, all fields in $data are %s, $where is a %d.
            $result = $wpdb->update($table, $data, $where, '%s', '%d');
            if(!$result){
                return false;
            }

            // Clear the caches.
            wp_cache_delete($object_id, $meta_type . '_meta');

            do_action("{$meta_type}_updated_meta", $meta_id, $object_id, $meta_key, $_meta_value);

            return true;
        }

        // And if the meta was not found.
        return false;
    }

    /**
     * Delete meta data by meta ID
     * @since 1.6.3
     *
     * @param string $meta_type Type of object metadata is for (e.g., gmedia, gmedia_term)
     * @param int    $meta_id   ID for a specific meta row
     *
     * @return bool True on successful delete, false on failure.
     */
    function delete_metadata_by_mid($meta_type, $meta_id){
        global $wpdb;

        // Make sure everything is valid.
        if(!$meta_type || !in_array($meta_type, array('gmedia', 'gmedia_term')) || !is_numeric($meta_id)){
            return false;
        }

        $meta_id = absint($meta_id);
        if(!$meta_id){
            return false;
        }

        $table = $wpdb->prefix . $meta_type . '_meta';

        // object and id columns
        $column    = sanitize_key($meta_type . '_id');
        $id_column = 'meta_id';

        // Fetch the meta and go on if it's found.
        if(($meta = $this->get_metadata_by_mid($meta_type, $meta_id))){
            $object_id = $meta->{$column};

            do_action("{$meta_type}_delete_meta", (array)$meta_id, $object_id, $meta->meta_key, $meta->meta_value);

            // Run the query, will return true if deleted, false otherwise
            $result = (bool)$wpdb->delete($table, array($id_column => $meta_id));

            // Clear the caches.
            wp_cache_delete($object_id, $meta_type . '_meta');

            do_action("{$meta_type}_deleted_meta", (array)$meta_id, $object_id, $meta->meta_key, $meta->meta_value);

            return $result;
        }

        // Meta id was not found.
        return false;
    }

    /**
     * Determine if a meta key is set for a given object
     * @see metadata_exists()
     *
     * @param string $meta_type Type of object metadata is for (e.g., gmedia or gmedia_term)
     * @param int    $object_id ID of the object metadata is for
     * @param string $meta_key  Metadata key.
     *
     * @return boolean true of the key is set, false if not.
     */
    function metadata_exists($meta_type, $object_id, $meta_key){
        if(!$meta_type || !in_array($meta_type, array('gmedia', 'gmedia_term')) || !is_numeric($object_id)){
            return false;
        }

        if(!$object_id = absint($object_id)){
            return false;
        }

        $check = apply_filters("{$meta_type}_get_metadata", null, $object_id, $meta_key, true);
        if(null !== $check){
            return true;
        }

        $meta_cache = wp_cache_get($object_id, $meta_type . '_meta');

        if(!$meta_cache){
            $meta_cache = $this->update_meta_cache($meta_type, array($object_id));
            $meta_cache = $meta_cache[ $object_id ];
        }

        if(isset($meta_cache[ $meta_key ])){
            return true;
        }

        return false;
    }

    /**
     * Get all Term data from database by Term ID.
     * The usage of the get_term function is to apply filters to a term object. It
     * is possible to get a term object from the database before applying the
     * filters.
     * There are two hooks, one is specifically for each term, named 'get_gmedia_term', and
     * the second is for the taxonomy name, 'term_$taxonomy'. Both hooks gets the
     * term object, and the taxonomy name as parameters. Both hooks are expected to
     * return a Term object.
     * 'get_gmedia_term' hook - Takes two parameters the term Object and the taxonomy name.
     * Must return term object. Used in get_term() as a catch-all filter for every
     * $term.
     * 'get_$taxonomy' hook - Takes two parameters the term Object and the taxonomy
     * name. Must return term object. $taxonomy will be the taxonomy name, so for
     * example, if 'gmedia_album', it would be 'get_gmedia_album' as the filter name. Useful
     * for custom taxonomies or plugging into default taxonomies.
     * @uses $wpdb
     * @see  get_term()
     *
     * @param int|object   $term   If integer, will get from database. If object will apply filters and return $term.
     * @param string|array $args   Taxonomy name that $term is part of or args with user_id and taxonomy.
     * @param string       $output Constant OBJECT, ARRAY_A, or ARRAY_N
     *
     * @return mixed|null|WP_Error Term Row from database. Will return null if $term is empty.
     */
    function get_term($term, $args = null, $output = OBJECT){
        /** @var $wpdb wpdb */
        global $wpdb, $user_ID, $gmCore;
        $null = null;

        if(empty($term)){
            $error = new WP_Error('invalid_term', __('Empty Term'));

            return $error;
        }

        if(is_object($term)){
            $term     = $term->term_id;
            $taxonomy = $term->taxonomy;
        }

        if($gmCore->is_digit($term)){
            $term = (int)$term;
            if(!$_term = wp_cache_get($term, 'gmedia_term')){
                $_term = $wpdb->get_row($wpdb->prepare("SELECT t.* FROM {$wpdb->prefix}gmedia_term AS t WHERE t.term_id = %d LIMIT 1", $term));
                if(!$_term){
                    return $null;
                }
                wp_cache_add($term, $_term, 'gmedia_term');
            }
        } else{
            $global   = $user_ID;
            $taxonomy = is_string($args)? $args : false;
            if(is_array($args)){
                if(isset($args['global'])){
                    $global = (int)$args['global'];
                }
                if(isset($args['taxonomy'])){
                    $taxonomy = $args['taxonomy'];
                }
            }
            if($taxonomy){
                $_term = $wpdb->get_row($wpdb->prepare("SELECT t.* FROM {$wpdb->prefix}gmedia_term AS t WHERE t.taxonomy = %s AND t.name = %s AND t.global = %d LIMIT 1", $taxonomy, $term, $global));
                if(!$_term){
                    return $null;
                }
                wp_cache_add($_term->term_id, $_term, 'gmedia_term');
            } else{
                return $null;
            }
        }

        $_term = apply_filters('get_gmedia_term', $_term, $_term->taxonomy);
        $_term = apply_filters("get_{$_term->taxonomy}", $_term, $_term->taxonomy);
        //$_term = sanitize_term($_term, $_term->taxonomy, $filter); // TODO sanitize_term after applying filters

        if($output == OBJECT){
            return $_term;
        } elseif($output == ARRAY_A){
            $__term = get_object_vars($_term);

            return $__term;
        } elseif($output == ARRAY_N){
            $__term = array_values(get_object_vars($_term));

            return $__term;
        } else{
            return $_term;
        }
    }

    /**
     * Updates Terms to Taxonomy in cache.
     *
     * @param array $terms List of term objects to change.
     */
    function update_term_cache($terms){
        foreach((array)$terms as $term){
            // Create a copy in case the array was passed by reference.
            $_term = clone $term;

            // Object ID should not be cached.
            unset($_term->object_id);

            wp_cache_add($term->term_id, $_term, 'gmedia_term');
        }
    }

    /**
     * Get Taxonomy by Term ID.
     * @uses $wpdb
     *
     * @param int $term_id
     *
     * @return string $taxonomy
     */
    function get_tax_by_term_id($term_id){
        /** @var $wpdb wpdb */
        global $wpdb, $gmCore;
        $null = null;

        if(!$gmCore->is_digit($term_id)){
            return $null;
        }
        $taxonomy = $wpdb->get_var($wpdb->prepare("SELECT taxonomy FROM {$wpdb->prefix}gmedia_term WHERE term_id = %d", $term_id));

        return $taxonomy;
    }

    /**
     * Retrieve the name of a term.
     * @see get_cat_name()
     *
     * @param object|int $term
     *
     * @return string Term name, or an empty string if term doesn't exist.
     */
    function get_term_name($term){
        if(is_object($term)){
            return $term->name;
        }
        $term = $this->get_term((int)$term);
        if(!$term || is_wp_error($term)){
            return '';
        }

        return $term->name;
    }

    /**
     * Retrieve the terms in a given taxonomy or list of taxonomies.
     * You can fully inject any customizations to the query before it is sent, as
     * well as control the output with a filter.
     * The 'get_gmedia_terms' filter will be called when the cache has the term and will
     * pass the found term along with the array of $taxonomies and array of $args.
     * This filter is also called before the array of terms is passed and will pass
     * the array of terms, along with the $taxonomies and $args.
     * The 'get_gmedia_terms_orderby' filter passes the ORDER BY clause for the query
     * along with the $args array.
     * The 'get_gmedia_terms_fields' filter passes the fields for the SELECT query
     * along with the $args array.
     * The list of arguments that $args can contain, which will overwrite the defaults:
     * orderby - Default is 'name'. Can be name, count, global, description or nothing
     * (will use term_id), Passing a custom value other than these will cause it to
     * order based on the custom value.
     * order - Default is ASC. Can use DESC.
     * hide_empty - Default is false. Will return empty terms, which means
     * terms whose count is 0 according to the given taxonomy.
     * exclude - Default is an empty array.  An array, comma- or space-delimited string
     * of term ids to exclude from the return array.  If 'include' is non-empty,
     * 'exclude' is ignored.
     * include - Default is an empty array.  An array, comma- or space-delimited string
     * of term ids to include in the return array.
     * number - The maximum number of terms to return.  Default is to return them all.
     * offset - The number by which to offset the terms query.
     * status - can be used generaly for albums. Defaults: 'publish', 'private', 'draft'.
     * fields - Default is 'all', which returns an array of term objects.
     * If 'fields' is 'ids' or 'names', returns an array of
     * integers or strings, respectively.
     * search - Returned terms' names will contain the value of 'search',
     * case-insensitive.  Default is an empty string.
     * name__like - Returned terms' names will begin with the value of 'name__like',
     * case-insensitive. Default is empty string.
     * The 'get' argument, if set to 'all' instead of its default empty string,
     * returns terms regardless of ancestry or whether the terms are empty.
     * The 'global' argument, when used, should be set to the integer of a term ID.  Its default is
     * the empty string '', which has a different meaning from the integer 0.
     * If set to an integer value, all returned terms will have as an immediate
     * ancestor the term whose ID is specified by that integer according to the given taxonomy.
     * The 'global' argument is different from 'child_of' in that a term X is considered a 'global'
     * of term Y only if term X is the father of term Y, not its grandfather or great-grandfather, etc.
     * @uses $wpdb
     * @uses wp_parse_args() Merges the defaults with those defined by $args and allows for strings.
     * @see  get_terms()
     *
     * @param string|array $taxonomies Taxonomy name or list of Taxonomy names
     * @param string|array $args       The values of what to search for when returning terms
     *
     * @return array|WP_Error List of Term Objects. Will return WP_Error, if any of $taxonomies does not exist.
     */
    function get_terms($taxonomies, $args = array()){
        /** @var $wpdb wpdb */
        global $wpdb;

        if(!is_array($taxonomies)){
            $taxonomies = array($taxonomies);
        }

        $defaults = array('orderby'       => 'name',
                          'order'         => 'ASC',
                          'hide_empty'    => false,
                          'exclude'       => array(),
                          'include'       => array(),
                          'get'           => '',
                          'number'        => '',
                          'fields'        => 'all',
                          'name__like'    => '',
                          'offset'        => '',
                          'search'        => '',
                          'global'        => '',
                          'status'        => '',
                          'page'          => 1,
                          'no_found_rows' => false
        );
        // $args can be whatever, only use the args defined in defaults
        $args           = array_intersect_key((array)$args, $defaults);
        $args           = wp_parse_args($args, $defaults);
        $args['number'] = absint($args['number']);
        $args['offset'] = absint($args['offset']);

        if('all' == $args['get']){
            $args['hide_empty'] = false;
        }

        $args = apply_filters('gmedia_get_terms_args', $args, $taxonomies);

        /** @var $orderby
         * @var  $order
         * @var  $hide_empty
         * @var  $exclude
         * @var  $include
         * @var  $number
         * @var  $fields
         * @var  $get
         * @var  $name_like
         * @var  $offset
         * @var  $search
         * @var  $global
         * @var  $status
         * @var  $page
         * @var  $no_found_rows
         * */
        extract($args, EXTR_SKIP);

        $key          = md5(serialize(compact(array_keys($defaults))) . serialize($taxonomies));
        $last_changed = wp_cache_get('last_changed', 'gmedia_terms');
        if(!$last_changed){
            $last_changed = time();
            wp_cache_set('last_changed', $last_changed, 'gmedia_terms');
        }
        $cache_key = "gmedia_get_terms:$key:$last_changed";
        $cache     = wp_cache_get($cache_key, 'gmedia_terms');
        if(false !== $cache){
            $cache_tax = wp_cache_get($cache_key, 'gmedia_terms_counts');
            if(false != $cache_tax){
                $this->openPage      = $cache_tax['openPage'];
                $this->resultPerPage = $cache_tax['resultPerPage'];
                $this->totalResult   = $cache_tax['totalResult'];
                $this->pages         = $cache_tax['pages'];
                $this->filter_tax    = $cache_tax['filter_tax'];
            }
            $args['terms_counts'] = $cache_tax;

            $cache = apply_filters('gmedia_get_terms', $cache, $taxonomies, $args);

            return $cache;
        }

        $order = strtoupper($order);
        if('' !== $order && !in_array($order, array('ASC', 'DESC'))){
            $order = 'ASC';
        }

        $_orderby = strtolower($orderby);
        if('count' == $_orderby){
            $orderby = "ORDER BY t.count $order, t.term_id $order";
        } else if('name' == $_orderby){
            $orderby = "ORDER BY t.name $order";
        } else if('description' == $_orderby){
            $orderby = "ORDER BY t.description $order, t.term_id $order";
        } else if('include' == $_orderby && !empty($args['include'])){
            $include = implode(',', array_map('absint', $args['include']));
            $orderby = "ORDER BY FIELD( t.term_id, $include )";
        } else if('global' == $_orderby){
            $orderby = "ORDER BY t.global ASC, t.term_id $order";
        } else if('global_desc_name' == $_orderby){
            $orderby = "ORDER BY t.global DESC, t.name $order";
        } else if('global_asc_name' == $_orderby){
            $orderby = "ORDER BY t.global ASC, t.name $order";
        } else if('rand' == $_orderby){
            $orderby = 'ORDER BY RAND()';
        } else if('none' == $_orderby){
            $orderby = '';
        } elseif(empty($_orderby) || 'id' == $_orderby){
            $orderby = "ORDER BY t.term_id $order";
        } else{
            $orderby = "ORDER BY t.name $order";
        }

        $orderby = apply_filters('gmedia_get_terms_orderby', $orderby, $args);

        $where_     = "t.taxonomy IN ('" . implode("', '", $taxonomies) . "')";
        $where      = '';
        $inclusions = '';
        if(!empty($include)){
            $exclude                      = '';
            $include                      = wp_parse_id_list($include);
            $inclusions                   = implode(',', $include);
            $this->filter_tax['term__in'] = $include;
        }

        if(!empty($inclusions)){
            $inclusions = ' AND t.term_id IN ( ' . $inclusions . ' )';
            $where .= $inclusions;
        }

        $exclusions = '';
        if(!empty($exclude)){
            $exterms                          = wp_parse_id_list($exclude);
            $this->filter_tax['term__not_in'] = $exterms;
            if(empty($exclusions)){
                $exclusions = implode(',', $exterms);
            } else{
                $exclusions .= ', ' . implode(',', $exterms);
            }
        }

        if(!empty($exclusions)){
            $exclusions = ' AND t.term_id NOT IN (' . $exclusions . ')';
        }

        $exclusions = apply_filters('list_gmedia_terms_exclusions', $exclusions, $args);
        if(!empty($exclusions)){
            $where .= $exclusions;
        }

        if(!empty($name__like)){
            $this->filter_tax['name__like'] = $name__like;
            $name__like                     = addcslashes($name__like, '_%\\');
            $where .= $wpdb->prepare(" AND t.name LIKE %s", $name__like . '%');
        }

        if(!empty($global) || '0' === $global || 0 === $global){
            $global                     = wp_parse_id_list($global);
            $this->filter_tax['global'] = $global;
            $where .= " AND t.global IN ('" . implode("', '", $global) . "')";
        }

        if($hide_empty){
            $where .= ' AND t.count > 0';
        }

        if(!empty($number)){
            if(!empty($page)){
                $page   = (int)$page? (int)$page : 1;
                $offset = ($page - 1) * $number;
            }
            if($offset){
                $limits = 'LIMIT ' . $offset . ',' . $number;
            } else{
                $limits = 'LIMIT ' . $number;
            }
        } else{
            $limits = '';
        }

        if(!empty($search)){
            $search = addcslashes($search, '_%\\');
            $where .= $wpdb->prepare(" AND (t.name LIKE %s)", '%' . $search . '%');
        }

        if(!empty($status)){
            if(is_array($status)){
                $arr_status = array();
                foreach($status as $_status){
                    $arr_status[] = $wpdb->prepare("t.status = %s", $_status);
                }
                $where .= ' AND (' . implode(' OR ', $arr_status) . ')';
            } else{
                $where .= $wpdb->prepare(" AND t.status = %s", $status);
            }
        }

        switch($fields){
            case 'ids':
            case 'id=>global':
                $selects = array('t.term_id', 't.global');
            break;
            case 'names':
                $selects = array('t.name');
            break;
            case 'id=>names':
                $selects = array('t.term_id', 't.name');
            break;
            case 'names_count':
                $selects = array('t.term_id', 't.name', 't.count');
            break;
            case 'count':
                $orderby = '';
                $selects = array('COUNT(*)');
            break;
            case 'all':
            default:
                $selects = array('t.*');
            break;
        }

        $_fields = $fields;

        $fields = implode(', ', apply_filters('gmedia_get_terms_fields', $selects, $args, $taxonomies));

        $join = "";

        $pieces  = array('fields', 'join', 'where', 'orderby', 'order', 'limits');
        $clauses = apply_filters('gmedia_terms_clauses', compact($pieces), $taxonomies, $args);
        foreach($pieces as $piece){
            $$piece = isset($clauses[ $piece ])? $clauses[ $piece ] : '';
        }

        $found_rows = '';
        if(!$no_found_rows && !empty($limits)){
            $found_rows = 'SQL_CALC_FOUND_ROWS';
        }

        $where_where = $where_ . $where;
        $this->sql_query       = "SELECT $found_rows $fields FROM {$wpdb->prefix}gmedia_term AS t $join WHERE $where_where $orderby $limits";

        $fields = $_fields;

        if('count' == $fields){
            $term_count = $wpdb->get_var($this->sql_query);

            return $term_count;
        }

        $terms = $wpdb->get_results($this->sql_query);

        $this->openPage      = $page;
        $this->resultPerPage = count($terms);
        if(!$no_found_rows){
            if(!empty($limits)){
                $this->totalResult = $wpdb->get_var('SELECT FOUND_ROWS()');
                $this->pages       = ceil($this->totalResult / $number);
                $this->perPages    = (int)$number;
            } else{
                $this->totalResult = $this->resultPerPage;
                $this->pages       = 1;
                $this->perPages    = $this->resultPerPage;
            }
        }
        $terms_counts = array('openPage'      => $this->openPage,
                              'resultPerPage' => $this->resultPerPage,
                              'totalResult'   => $this->totalResult,
                              'pages'         => $this->pages,
                              'perPages'      => $this->perPages,
                              'filter_tax'    => $this->filter_tax
        );

        if('all' == $fields){
            $this->update_term_cache($terms);
        }

        if(empty($terms)){
            wp_cache_add($cache_key, array(), 'gmedia_terms', 86400); // one day
            wp_cache_add($cache_key, array(), 'gmedia_terms_counts', 86400); // one day
            $terms = apply_filters('gmedia_get_terms', array(), $taxonomies, $args);

            return $terms;
        }

        reset($terms);
        $_terms = array();
        if('id=>global' == $fields){
            while(($term = array_shift($terms))){
                $_terms[ $term->term_id ] = $term->global;
            }
            $terms = $_terms;
        } elseif('ids' == $fields){
            while(($term = array_shift($terms))){
                $_terms[] = $term->term_id;
            }
            $terms = $_terms;
        } elseif('names' == $fields){
            while(($term = array_shift($terms))){
                $_terms[] = $term->name;
            }
            $terms = $_terms;
        } elseif('id=>names' == $fields){
            while(($term = array_shift($terms))){
                $_terms[ $term->term_id ] = $term->name;
            }
            $terms = $_terms;
        } elseif('name=>all' == $fields){
            while(($term = array_shift($terms))){
                $_terms[ $term->name ] = $term;
            }
            $terms = $_terms;
        } elseif('names_count' == $fields){
            while(($term = array_shift($terms))){
                $_terms[ $term->term_id ] = array('name' => $term->name, 'count' => $term->count, 'term_id' => $term->term_id);
            }
            $terms = $_terms;
        }

        if(0 < $number && intval(@count($terms)) > $number){
            $terms = array_slice($terms, $offset, $number);
        }

        wp_cache_add($cache_key, $terms, 'gmedia_terms', 86400); // one day
        wp_cache_add($cache_key, $terms_counts, 'gmedia_terms_counts', 86400); // one day
        $args['terms_counts'] = $terms_counts;

        $terms = apply_filters('gmedia_get_terms', $terms, $taxonomies, $args);

        return $terms;
    }

    /**
     * Adds a new term to the database. Optionally marks it as an alias of an existing term.
     * Error handling is assigned for the nonexistence of the $taxonomy and $term
     * parameters before inserting. If both the term id and taxonomy exist
     * previously, then an array will be returned that contains the term id and the
     * contents of what is returned. The keys of the array are 'term_id' containing numeric values.
     * It is assumed that the term does not yet exist or the above will apply. The
     * term will be first added to the term table and related to the taxonomy
     * if everything is well. If everything is correct, then several actions will be
     * run prior to a filter and then several actions will be run after the filter
     * is run.
     * The arguments decide how the term is handled based on the $args parameter.
     * The following is a list of the available overrides and the defaults.
     * 'description'. There is no default. If exists, will be added to the database
     * along with the term. Expected to be a string.
     * 'global'. Expected to be numeric and default is 0 (zero). Will assign value
     * of 'global' to the term.
     * @see  wp_insert_term()
     * @uses $wpdb
     * @uses apply_filters() Calls 'pre_insert_gmedia_term' hook with term and taxonomy as parameters.
     * @uses do_action() Calls 'create_gmedia_term' hook with the term id and taxonomy id as parameters.
     * @uses do_action() Calls 'created_gmedia_term' hook with the term id and taxonomy id as parameters.
     *
     * @param string       $term     The term to add or update.
     * @param string       $taxonomy The taxonomy to which to add the term
     * @param array|string $args     Change the values of the inserted term
     *
     * @return int|WP_Error The Term ID array('term_id'=>$term_id)
     */
    function insert_term($term, $taxonomy, $args = array()){
        /** @var $wpdb wpdb */
        global $wpdb, $gmGallery, $gmCore, $user_ID;

        $term = apply_filters('pre_insert_gmedia_term', $term, $taxonomy);
        if(is_wp_error($term)){
            return $term;
        }

        if(is_int($term) && 0 == $term){
            return new WP_Error('gm_invalid_term_id', __('Invalid term ID'));
        }

        if('' == trim($term)){
            return new WP_Error('gm_empty_term_name', __('A name is required for this term'));
        }

        $defaults = array('description' => '', 'global' => intval($user_ID), 'status' => 'publish');
        $taxterm  = str_replace('gmedia_', '', $taxonomy);
        switch($taxterm){
            case 'album':
                $defaults['status']           = $gmGallery->options['in_album_status'];
                $defaults['meta']['_orderby'] = $gmGallery->options['in_album_orderby'];
                $defaults['meta']['_order']   = $gmGallery->options['in_album_order'];
            break;
            case 'category':
                $defaults['meta']['_orderby'] = $gmGallery->options['in_category_orderby'];
                $defaults['meta']['_order']   = $gmGallery->options['in_category_order'];
            break;
        }
        $args             = wp_parse_args($args, $defaults);
        $args['name']     = $term;
        $args['taxonomy'] = $taxonomy;
        // TODO $args = sanitize_term($args, $taxonomy, 'db');
        /** @var $name
         * @var  $description
         * @var  $global
         * @var  $status
         * @var  $comment_status
         * @var  $slug
         * @var  $meta
         */
        extract($args, EXTR_SKIP);

        // expected_slashed ($name)
        $name = stripslashes($name);
        $name = $gmCore->mb_convert_encoding_utf8($name);
        if('gmedia_module' == $taxonomy){
            $description = maybe_serialize($description);
        } else{
            $description = stripslashes($description);
            $description = $gmCore->mb_convert_encoding_utf8($description);
            if(in_array($taxonomy, array('gmedia_tag', 'gmedia_category'))){
                $global = 0;
            }
        }

        if(($term_id = $this->term_exists($name, $taxonomy, $global))){
            // Same name, same global.
            return new WP_Error('gm_term_exists', __('A term with the name provided already exists.'), $term_id);
        }

        do_action("create_gmedia_term", $term_id, $taxonomy);

        // This term does not exist, Create it.
        if(false === $wpdb->insert($wpdb->prefix . 'gmedia_term', compact('name', 'taxonomy', 'description', 'global', 'status') + array('count' => 0))){
            return new WP_Error('gm_db_insert_error', __('Could not insert term into the database'), $wpdb->last_error);
        }
        $term_id = (int)$wpdb->insert_id;

        if(isset($meta) && is_array($meta) && !empty($meta)){
            $meta_type = 'gmedia_term';
            foreach($meta as $key => $value){
                if(in_array($key, array('_cover', '_orderby', '_order', '_module_preset'))){
                    $value = ltrim($value, '#');
                    $this->add_metadata($meta_type, $term_id, $key, $value);
                    continue;
                }
                $key = trim($key);
                if(!empty($key) && !$gmCore->is_digit($key) && !empty($value) && !$gmCore->is_protected_meta($key, $meta_type)){
                    $this->add_metadata($meta_type, $term_id, $key, $value);
                }
            }
        }

        if(in_array($taxonomy, array('gmedia_album', 'gmedia_gallery'))){
            $post_data = array('post_author'  => $global,
                               'post_content' => $description,
                               'post_title'   => $name,
                               'post_status'  => $status,
                               'post_type'    => $taxonomy
            );
            if(isset($slug) && !empty($slug)){
                $post_data['post_name'] = $slug;
            }
            if(!empty($comment_status)){
                $post_data['comment_status'] = $comment_status;
            } else{
                $post_data['comment_status'] = $gmGallery->options['default_gmedia_comment_status'];
            }
            $_post_ID = wp_insert_post($post_data);
            if($_post_ID){
                add_metadata('post', $_post_ID, '_gmedia_term_ID', $term_id);
                $this->add_metadata('gmedia_term', $term_id, '_post_ID', $_post_ID);
            }

        }

        $this->clean_term_cache($term_id);

        do_action("created_gmedia_term", $term_id, $taxonomy);

        return $term_id;
    }

    /**
     * Update term based on arguments provided.
     * The $args will indiscriminately override all values with the same field name.
     * Care must be taken to not override important information need to update or
     * update will fail (or perhaps create a new term, neither would be acceptable).
     * @see  wp_update_term()
     * @uses $wpdb
     * @uses do_action() Will call both 'edit_gmedia_term' and 'edit_$taxonomy'.
     *
     * @param int          $term_id The ID of the term
     * @param array|string $args    Overwrite term field values
     *
     * @return int|WP_Error Returns Term ID
     */
    function update_term($term_id, $args = array()){
        /** @var $wpdb wpdb */
        global $wpdb, $gmCore, $gmGallery;

        $term_id = (int)$term_id;

        // First, get all of the original args
        $term = $this->get_term($term_id, null, ARRAY_A);

        if(is_wp_error($term) || empty($term)){
            return $term;
        }

        // Escape data pulled from DB.
        $term     = add_magic_quotes($term);
        $taxonomy = $term['taxonomy'];

        // Merge old and new args with new args overwriting old ones.
        $args = array_merge($term, $args);

        $defaults = array('global' => $term['global'], 'name' => $term['name'], 'description' => '', 'status' => 'publish', 'meta' => array());
        $args     = wp_parse_args($args, $defaults);

        /** @var $name
         * @var  $description
         * @var  $status
         * @var  $global
         * @var  $meta
         */
        extract($args, EXTR_SKIP);

        // expected_slashed ($name)
        $name = stripslashes($name);
        $name = $gmCore->mb_convert_encoding_utf8($name);
        if('' == trim($name)){
            return new WP_Error('gm_empty_term_name', __('A name is required for term'));
        }

        if(current_user_can('gmedia_edit_others_media')){
            $global = (int)$global;
        } else{
            $global = $term['global'];
        }

        if('gmedia_module' == $taxonomy){
            $description = maybe_serialize($description);
        } else{
            $description = stripslashes($description);
            $description = $gmCore->mb_convert_encoding_utf8($description);
            if(in_array($taxonomy, array('gmedia_tag', 'gmedia_category'))){
                $global = 0;
            }
        }

        $term_id = $wpdb->get_var($wpdb->prepare("SELECT t.term_id FROM {$wpdb->prefix}gmedia_term AS t WHERE t.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id));
        do_action("edit_gmedia_term", $term_id, $taxonomy);
        $wpdb->update($wpdb->prefix . 'gmedia_term', compact('term_id', 'name', 'taxonomy', 'description', 'global', 'status'), array('term_id' => $term_id));

        if(('gmedia_album' == $taxonomy)){
            $default_meta = array('_orderby' => 'ID',
                                  '_order'   => 'DESC'
            );
            $meta         = array_merge($default_meta, $meta);
            if(isset($status_global)){
                $db_gmedia_ids = $this->get_gmedias(array('no_found_rows' => true, 'album__in' => $term_id, 'fields' => 'post_ids'));
                if(!empty($db_gmedia_ids)){
                    $values = array('gm' => array(), 'wp' => array());
                    foreach($db_gmedia_ids as $gmids){
                        $values['gm'][] = $wpdb->prepare("%d", $gmids->ID);
                        wp_cache_delete($gmids->ID, 'gmedias');
                        if(!empty($gmids->post_id)){
                            $values['wp'][] = $wpdb->prepare("%d", $gmids->post_id);
                            wp_cache_delete($gmids->post_id, 'wpgmedias');
                            wp_cache_delete($gmids->post_id, 'posts');
                        }
                    }
                    if(!empty($values['gm'])){
                        $status = esc_sql($status);
                        if(false === $wpdb->query("UPDATE {$wpdb->prefix}gmedia SET status = '{$status}' WHERE ID IN (" . join(',', $values['gm']) . ")")){
                            return new WP_Error('db_insert_error', __('Could not update statuses for gmedia items in the database'), $wpdb->last_error);
                        }
                        if(!empty($values['wp'])){
                            $wpdb->query("UPDATE $wpdb->posts SET post_status = '{$status}' WHERE ID IN (" . join(',', $values['wp']) . ")");
                        }
                    }
                }
            }
        }

        if(!empty($meta) && is_array($meta)){
            $meta_type = 'gmedia_term';
            foreach($meta as $key => $value){
                if(in_array($key, array('_cover', '_orderby', '_order', '_module_preset'))){
                    $value = ltrim($value, '#');
                    $this->update_metadata($meta_type, $term_id, $key, $value);
                } elseif($gmCore->is_digit($key)){
                    $mid = (int)$key;
                    //$value = wp_unslash( $value );
                    if(!($meta = $this->get_metadata_by_mid('gmedia_term', $mid))){
                        continue;
                    }
                    if('' == trim($value)){
                        $this->delete_metadata_by_mid($meta_type, $key);
                        continue;
                    }
                    if($meta->meta_value != $value){
                        $this->update_metadata_by_mid($meta_type, $mid, $value);
                    }
                }
            }
        }

        if(in_array($taxonomy, array('gmedia_album', 'gmedia_gallery'))){
            $post_data = array('post_author'   => $global,
                               'post_content'  => $description,
                               'post_title'    => $name,
                               'post_status'   => $status,
                               'post_type'     => $taxonomy
            );
            if(!empty($slug)){
                $post_data['post_name'] = $slug;
            }
            if(!empty($comment_status)){
                $post_data['comment_status'] = $comment_status;
            } else{
                $post_data['comment_status'] = $gmGallery->options['default_gmedia_comment_status'];
            }
            $_post_ID = $this->get_metadata('gmedia_term', $term_id, '_post_ID', true);
            if($_post_ID){
                $post_data['ID'] = $_post_ID;

	            if ( ! empty( $post_date ) ) {
		            $post_data['post_date']     = $post_date;
		            $post_data['post_date_gmt'] = get_gmt_from_date( $post_date );
	            }

	            if(!wp_update_post($post_data)){
                    unset($post_data['ID']);
                    $_post_ID = wp_insert_post($post_data);
                    if($_post_ID){
                        add_metadata('post', $_post_ID, '_gmedia_term_ID', $term_id);
                        $this->update_metadata('gmedia_term', $term_id, '_post_ID', $_post_ID);
                    }
                }
            } else{
                $_post_ID = wp_insert_post($post_data);
                if($_post_ID){
                    add_metadata('post', $_post_ID, '_gmedia_term_ID', $term_id);
                    $this->update_metadata('gmedia_term', $term_id, '_post_ID', $_post_ID);
                }
            }
        }

        do_action("edit_$taxonomy", $term_id);

        $term_id = apply_filters('gmedia_term_id_filter', $term_id);

        $this->clean_term_cache($term_id);

        do_action("edited_gmedia_term", $term_id, $taxonomy);
        do_action("edited_$taxonomy", $term_id);

        return $term_id;
    }

    /**
     * Update term's items sort order based on arguments provided.
     * @uses $wpdb
     * @uses do_action() Will call both 'sort_gmedia_term' and 'sort_$taxonomy'.
     *
     * @param int   $term_id The ID of the term
     * @param array $gm_ids_order
     *
     * @return int|WP_Error Returns Term ID
     */
    function update_term_sortorder($term_id, $gm_ids_order = array()){
        /** @var $wpdb wpdb */
        global $wpdb;

        $term_id = $this->term_exists($term_id);
        if(!$term_id){
            return new WP_Error('gm_invalid_term_id', __('Invalid term ID'));
        }

        do_action("sort_gmedia_term", $term_id);

        if(empty($gm_ids_order)){
            $term_meta = $this->get_metadata('gmedia_term', $term_id);
            $_orderby  = isset($term_meta['_orderby'][0])? $term_meta['_orderby'][0] : 'ID';
            $_order    = isset($term_meta['_order'][0])? $term_meta['_order'][0] : 'DESC';
            if($_orderby == 'custom'){
                $_orderby = 'ID';
                $_order   = 'DESC';
            }
            $gm_ids_order = $this->get_gmedias(array('no_found_rows' => true,
                                                     'album__in'     => $term_id,
                                                     'orderby'       => $_orderby,
                                                     'order'         => $_order,
                                                     'fields'        => 'ids'
                                               ));
            $gm_ids_order = array_merge(array(0), $gm_ids_order);
            unset($gm_ids_order[0]);
            $gm_ids_order = array_flip($gm_ids_order);
        }

        $final_gmedia_ids = array();
        if(!empty($gm_ids_order)){
            $_db_gmedia_ids = $this->get_gmedias(array('no_found_rows' => true, 'album__in' => $term_id, 'orderby' => 'custom', 'order' => 'ASC', 'fields' => 'ids'));
            $db_gmedia_ids  = array_merge(array(0), $_db_gmedia_ids);
            unset($db_gmedia_ids[0]);
            $db_gmedia_ids = array_flip($db_gmedia_ids);

            if($gm_ids_order != $db_gmedia_ids){
                $final_gmedia_ids = array_intersect_key($gm_ids_order, $db_gmedia_ids) + $db_gmedia_ids;
                asort($final_gmedia_ids, SORT_NUMERIC);
                $final_gmedia_ids = array_keys($final_gmedia_ids);
                $final_gmedia_ids = array_diff_assoc($final_gmedia_ids, $_db_gmedia_ids);

                $values = array();
                foreach($final_gmedia_ids as $gmedia_order => $gmedia_id){
                    $values[] = $wpdb->prepare("(%d, %d, %d)", $gmedia_id, $term_id, ($gmedia_order + 1));
                }
                if($values){
                    if(false === $wpdb->query("INSERT INTO {$wpdb->prefix}gmedia_term_relationships (gmedia_id, gmedia_term_id, gmedia_order) VALUES " . join(',', $values) . " ON DUPLICATE KEY UPDATE gmedia_order = VALUES(gmedia_order)")){
                        return new WP_Error('db_insert_error', __('Could not insert gmedia term relationship into the database'), $wpdb->last_error);
                    }
                }
                $this->clean_object_term_cache($final_gmedia_ids);
            }
        }

        $this->clean_term_cache($term_id);

        do_action("sorted_gmedia_term", $term_id);

        return $final_gmedia_ids;
    }

    /**
     * Check if Term exists.
     * Returns the index of a defined term, or 0 (false) if the term doesn't exist.
     * @see  term_exists()
     * @uses $wpdb
     *
     * @param int|string $term     The term to check
     * @param string     $taxonomy The taxonomy name to use
     * @param bool|int   $global   global parameter under which to confine the exists search.
     *
     * @return int Get the term id or Term Object, if exists.
     */
    function term_exists($term, $taxonomy = '', $global = false){
        /** @var $wpdb wpdb */
        global $wpdb, $gmCore;

        $select = "SELECT term_id FROM {$wpdb->prefix}gmedia_term AS t WHERE ";

        if($gmCore->is_digit($term)){
            if(0 == $term){
                return 0;
            }
            $where = 't.term_id = %d';
            if(!empty($taxonomy)){
                return $wpdb->get_var($wpdb->prepare($select . $where . " AND t.taxonomy = %s", $term, $taxonomy));
            } else{
                return $wpdb->get_var($wpdb->prepare($select . $where, $term));
            }
        }

        if('' === $term = trim(stripslashes($term))){
            return 0;
        }

        $where        = 't.name = %s';
        $where_fields = array($term);
        if(!empty($taxonomy)){
            if(false !== $global){
                $global         = (int)$global;
                $where_fields[] = $global;
                $where .= ' AND t.global = %d';
            }

            $where_fields[] = $taxonomy;

            return $wpdb->get_var($wpdb->prepare("SELECT t.term_id FROM {$wpdb->prefix}gmedia_term AS t WHERE $where AND t.taxonomy = %s", $where_fields));
        }

        return $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->prefix}gmedia_term AS t WHERE $where", $where_fields));
    }

    /**
     * Create Term and Taxonomy Relationships.
     * Relates an object to a term and taxonomy type. Creates the
     * term and taxonomy relationship if it doesn't already exist. Creates a term if
     * it doesn't exist.
     * A relationship means that the term is grouped in or belongs to the taxonomy.
     * A term has no meaning until it is given context by defining which taxonomy it
     * exists under.
     * @see  wp_set_object_terms()
     * @uses $wpdb
     *
     * @param int              $object_id The object to relate to.
     * @param array|int|string $terms     The slug or id of the term, will replace all existing
     *                                    related terms in this taxonomy.
     * @param array|string     $taxonomy  The context in which to relate the term to the object.
     * @param int              $append    If 1, don't delete existing tags, just add on. If 0, replace the tags with the new tags. If -1, remove given tags.
     *
     * @return array|WP_Error Affected Term IDs
     */
    function set_gmedia_terms($object_id, $terms, $taxonomy, $append = 0){
        /** @var $wpdb wpdb */
        global $wpdb, $gmCore;

        $object_id = (int)$object_id;

        if(!is_array($terms)){
            $terms = array($terms);
        }
        $terms = array_filter(array_map('trim', (array)$terms));

        if($append == 0){
            if(empty($terms)){
                $this->delete_gmedia_term_relationships($object_id, $taxonomy);

                return 0;
            }
            $old_term_ids = $this->get_gmedia_terms($object_id, $taxonomy, array('fields' => 'ids', 'orderby' => 'none'));
        } else{
            $old_term_ids = array();
        }

        $term_ids     = array();
        $new_term_ids = array();
        if(!empty($terms)){
            foreach($terms as $term){
                if(!strlen(trim($term))){
                    continue;
                }
                if(('gmedia_album' == $taxonomy) && !$gmCore->is_digit($term)){
                    $global = get_current_user_id();
                } else{
                    $global = false;
                }
                if(!$term_id = $this->term_exists($term, $taxonomy, $global)){
                    // Skip if a non-existent term ID is passed or if taxonomy is category or if user is not allowed to add new terms.
                    if($gmCore->is_digit($term) || ($append < 0) || !current_user_can($taxonomy . '_manage')){
                        continue;
                    }
                    if($global){
                        $args = array('global' => $global);
                    } else{
                        $args = array();
                    }
                    $term_id = $this->insert_term($term, $taxonomy, $args);
                    if(is_wp_error($term_id)){
                        return $term_id;
                    }
                } else{
                    if(in_array($taxonomy, array('gmedia_album')) && !current_user_can('gmedia_edit_others_media')){
                        $alb = $this->get_term($term_id);
                        if($alb->global && ($alb->global != get_current_user_id())){
                            continue;
                        }
                    }
                }
                $term_ids[] = $term_id;

                if($append < 0){
                    continue;
                }

                if($wpdb->get_var($wpdb->prepare("SELECT gmedia_term_id FROM {$wpdb->prefix}gmedia_term_relationships WHERE gmedia_id = %d AND gmedia_term_id = %d", $object_id, $term_id))){
                    continue;
                }
                do_action('add_gmedia_term_relationships', $object_id, $term_id);
                $wpdb->insert($wpdb->prefix . 'gmedia_term_relationships', array('gmedia_id' => $object_id, 'gmedia_term_id' => $term_id));
                do_action('added_gmedia_term_relationships', $object_id, $term_id);
                $new_term_ids[] = $term_id;
            }
        }

        if(!empty($new_term_ids)){
            $this->update_term_count($term_ids);
        }

        if($append < 1){
            if($append == 0){
                $delete_terms = array_diff($old_term_ids, $term_ids);
            } else{
                $delete_terms = $term_ids;
            }
            if(!empty($delete_terms)){
                $in_delete_terms = "'" . implode("', '", $delete_terms) . "'";
                do_action('delete_gmedia_term_relationships', $object_id, $delete_terms);
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}gmedia_term_relationships WHERE gmedia_id = %d AND gmedia_term_id IN ($in_delete_terms)", $object_id));
                do_action('deleted_gmedia_term_relationships', $object_id, $delete_terms);
                $this->update_term_count($delete_terms);
            }
        }

        // TODO sort terms (albums)
        $sort = ('gmedia_tag' == $taxonomy)? true : false;
        if(!$append && $sort){
            $values         = array();
            $term_order     = 0;
            $final_term_ids = $this->get_gmedia_terms($object_id, $taxonomy, array('fields' => 'ids'));
            foreach($term_ids as $term_id){
                if(in_array($term_id, $final_term_ids)){
                    $values[] = $wpdb->prepare("(%d, %d, %d)", $object_id, $term_id, ++ $term_order);
                }
            }
            if($values){
                if(false === $wpdb->query("INSERT INTO {$wpdb->prefix}gmedia_term_relationships (gmedia_id, gmedia_term_id, term_order) VALUES " . join(',', $values) . " ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)")){
                    return new WP_Error('db_insert_error', __('Could not insert gmedia term relationship into the database'), $wpdb->last_error);
                }
            }
        }

        wp_cache_delete($object_id, $taxonomy . '_relationships');

        do_action('set_gmedia_terms', $object_id, $terms, $term_ids, $taxonomy, $append, $old_term_ids);

        return $term_ids;
    }

    /**
     * Retrieve the terms of the taxonomy that are attached to the gmedia.
     * @see get_the_terms()
     *
     * @param int    $id       gmedia ID
     * @param string $taxonomy Taxonomy name.
     *
     * @return array|bool False on failure. Array of term objects on success.
     */
    function get_the_gmedia_terms($id, $taxonomy){
        $id = (int)$id;

        if(!$id){
            return false;
        }

        $terms = wp_cache_get($id, "{$taxonomy}_relationships");
        if(false === $terms){
            $terms = $this->get_gmedia_terms($id, $taxonomy);
            wp_cache_add($id, $terms, $taxonomy . '_relationships');
        }

        $terms = apply_filters('get_the_gmedia_terms', $terms, $id, $taxonomy);

        if(empty($terms)){
            return false;
        }

        return $terms;
    }

    /**
     * Retrieves the terms associated with the given object(s), in the supplied taxonomies.
     * The following information has to do the $args parameter and for what can be
     * contained in the string or array of that parameter, if it exists.
     * The first argument is called, 'orderby' and has the default value of 'name'.
     * The other value that is supported is 'count'.
     * The second argument is called, 'order' and has the default value of 'ASC'.
     * The only other value that will be acceptable is 'DESC'.
     * The final argument supported is called, 'fields' and has the default value of
     * 'all'. There are multiple other options that can be used instead. Supported
     * values are as follows: 'all', 'ids', 'names', and finally
     * 'all_with_object_id'.
     * The fields argument also decides what will be returned. If 'all' or
     * 'all_with_object_id' is chosen or the default kept intact, then all matching
     * terms objects will be returned. If either 'ids' or 'names' is used, then an
     * array of all matching term ids or term names will be returned respectively.
     * @see  wp_get_object_terms()
     * @uses $wpdb
     *
     * @param int|array    $object_ids The ID(s) of the object(s) to retrieve.
     * @param string|array $taxonomies The taxonomies to retrieve terms from.
     * @param array|string $args       Change what is returned
     *
     * @return array The requested term data or empty array if no terms found. WP_Error if $taxonomy does not exist.
     */
    function get_gmedia_terms($object_ids, $taxonomies, $args = array()){
        /** @var $wpdb wpdb */
        global $wpdb;

        if(!is_array($taxonomies)){
            $taxonomies = array($taxonomies);
        }

        if(!is_array($object_ids)){
            $object_ids = array($object_ids);
        }
        $object_ids = array_map('intval', $object_ids);

        $defaults = array('orderby' => 'name', 'order' => 'ASC', 'fields' => 'all', 'unique' => true);
        $args     = wp_parse_args($args, $defaults);

        $terms = array();

        /** @var $orderby
         * @var  $order
         * @var  $fields
         * @var  $unique
         */
        extract($args, EXTR_SKIP);

        if($unique){
            $groupby = 'GROUP BY t.term_id';
        } else{
            $groupby = '';
        }

        if('count' == $orderby){
            $orderby = 't.count';
        } else if('name' == $orderby){
            $orderby = 't.name';
        } else if('global' == $orderby){
            $orderby = 't.global';
        } else if('term_order' == $orderby){
            $orderby = 'tr.term_order';
        } else if('none' == $orderby){
            $orderby = '';
            $order   = '';
        } else{
            $orderby = 't.term_id';
        }

        if(!empty($orderby)){
            $orderby = "ORDER BY $orderby";
        }

        $taxonomies = "'" . implode("', '", $taxonomies) . "'";
        $object_ids = implode(', ', $object_ids);

        $select_this = '';
        if('all' == $fields){
            $select_this = 't.*, tr.term_order, tr.gmedia_order';
        } else if('ids' == $fields){
            $select_this = 't.term_id';
        } else if('names' == $fields){
            $select_this = 't.name';
        } else if('all_with_object_id' == $fields){
            $select_this = 't.*, tr.gmedia_id, tr.term_order, tr.gmedia_order';
            $groupby     = '';
        }

        $query = "SELECT $select_this FROM {$wpdb->prefix}gmedia_term AS t INNER JOIN {$wpdb->prefix}gmedia_term_relationships AS tr ON tr.gmedia_term_id = t.term_id WHERE t.taxonomy IN ($taxonomies) AND tr.gmedia_id IN ($object_ids) $groupby $orderby $order";

        if('all' == $fields || 'all_with_object_id' == $fields){
            $terms = array_merge($terms, $wpdb->get_results($query));
            $this->update_term_cache($terms);
        } else if('ids' == $fields || 'names' == $fields){
            $terms = array_merge($terms, $wpdb->get_col($query));
        }

        if(!$terms){
            $terms = array();
        }

        return apply_filters('get_gmedia_terms', $terms, $object_ids, $taxonomies, $args);
    }

    /**
     * Removes a term from the database.
     * If the term is a parent of other terms, then the children will be updated to
     * that term's parent.
     * The $args 'default' will only override the terms found, if there is only one
     * term found. Any other and the found terms are used.
     * The $args 'force_default' will force the term supplied as default to be
     * assigned even if the object was not going to be termless
     * @see  wp_delete_term()
     * @uses $wpdb
     * @uses do_action() Calls both 'delete_gmedia_term' and 'gm_delete_$taxonomy' action
     *       hooks, passing term object, term id. 'gm_delete_term' gets an additional
     *       parameter with the $taxonomy parameter.
     *
     * @param int          $term_id Term ID
     * @param array|string $args    Optional. Change 'default' term id and override found term ids.
     *
     * @return bool|WP_Error Returns false if not term; term_id if completes delete action.
     */
    function delete_term($term_id, $args = array()){
        /** @var $wpdb wpdb */
        global $wpdb;

        $term_id = (int)$term_id;

        if(!$term_id = $this->term_exists($term_id)){
            return false;
        }
        if(is_wp_error($term_id)){
            return $term_id;
        }

        extract($args, EXTR_SKIP);

        if(!isset($taxonomy)){
            $taxonomy = $this->get_tax_by_term_id($term_id);
        }

        $objects = $wpdb->get_col($wpdb->prepare("SELECT gmedia_id FROM {$wpdb->prefix}gmedia_term_relationships WHERE gmedia_term_id = %d", $term_id));

        foreach((array)$objects as $object){
            $terms = $this->get_gmedia_terms($object, $taxonomy, array('fields' => 'ids', 'orderby' => 'none'));
            $terms = array_diff($terms, array($term_id));
            $terms = array_map('intval', $terms);
            $this->set_gmedia_terms($object, $terms, $taxonomy);
        }

        $_post_ID             = $this->get_metadata('gmedia_term', $term_id, '_post_ID', true);
        $gmedia_term_meta_ids = $wpdb->get_col($wpdb->prepare("SELECT meta_id FROM {$wpdb->prefix}gmedia_term_meta WHERE gmedia_term_id = %d ", $term_id));
        if(!empty($gmedia_term_meta_ids)){
            do_action('delete_gmedia_term_meta', $gmedia_term_meta_ids);
            $in_gmedia_term_meta_ids = "'" . implode("', '", $gmedia_term_meta_ids) . "'";
            $wpdb->query("DELETE FROM {$wpdb->prefix}gmedia_term_meta WHERE meta_id IN($in_gmedia_term_meta_ids)");
            do_action('deleted_gmedia_term_meta', $gmedia_term_meta_ids);
        }

        do_action('delete_gmedia_term', $term_id);
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}gmedia_term WHERE term_id = %d", $term_id));
        if($_post_ID){
            wp_delete_post($_post_ID, true);
        }
        do_action('deleted_gmedia_term', $term_id);

        $this->clean_term_cache($term_id);

        do_action("delete_$taxonomy", $term_id);

        return $term_id;
    }

    /**
     * Will remove all of the term ids from the cache.
     * @uses $wpdb
     * @see  clean_term_cache()
     *
     * @param int|array $ids Single or list of Term IDs
     */
    function clean_term_cache($ids){
        if(!is_array($ids)){
            $ids = array($ids);
        }

        foreach($ids as $id){
            wp_cache_delete($id, 'gmedia_term');
        }

        do_action('gmedia_clean_term_cache', $ids);

        wp_cache_set('last_changed', time(), 'gmedia_terms');
    }


    /**
     * Call major cache updating functions for list of Post objects.
     * @see  update_post_caches()
     *
     * @param array $gmedias           Array of gmedia objects
     * @param bool  $update_term_cache Whether to update the term cache. Default is true.
     * @param bool  $update_meta_cache Whether to update the meta cache. Default is true.
     *
     * @return null if we didn't match any gmedia objects
     */
    function update_gmedia_caches(&$gmedias, $update_term_cache = true, $update_meta_cache = true){
        // No point in doing all this work if we didn't match any gmedia objects.
        if(!$gmedias){
            return null;
        }

        foreach($gmedias as $gmedia){
            wp_cache_add($gmedia->ID, $gmedia, 'gmedias');
            if(!empty($gmedia->post_id)){
                wp_cache_add($gmedia->post_id, $gmedia, 'wpgmedias');
            }
        }

        $gmedia_ids = array();
        foreach($gmedias as $gmedia){
            $gmedia_ids[] = $gmedia->ID;
        }

        if($update_term_cache){
            $gmedia_ids = array_map('intval', $gmedia_ids);
            $taxonomies = array('gmedia_album', 'gmedia_category', 'gmedia_tag');

            $ids = array();
            foreach((array)$gmedia_ids as $id){
                foreach($taxonomies as $taxonomy){
                    if(false === wp_cache_get($id, "{$taxonomy}_relationships")){
                        $ids[] = $id;
                        break;
                    }
                }
            }

            if(!empty($ids)){
                $terms = $this->get_gmedia_terms($ids, $taxonomies, array('fields' => 'all_with_object_id'));

                $object_terms = array();
                foreach((array)$terms as $term){
                    $object_terms[ $term->gmedia_id ][ $term->taxonomy ][ $term->term_id ] = $term;
                }

                foreach($ids as $id){
                    foreach($taxonomies as $taxonomy){
                        if(!isset($object_terms[ $id ][ $taxonomy ])){
                            if(!isset($object_terms[ $id ])){
                                $object_terms[ $id ] = array();
                            }
                            $object_terms[ $id ][ $taxonomy ] = array();
                        }
                    }
                }

                foreach($object_terms as $id => $value){
                    foreach($value as $taxonomy => $terms){
                        wp_cache_set($id, $terms, "{$taxonomy}_relationships");
                    }
                }
            }
        }

        if($update_meta_cache){
            $this->update_meta_cache('gmedia', $gmedia_ids);
        }

    }

    /**
     * Update the metadata cache for the specified objects.
     * @see  update_meta_cache()
     * @uses $wpdb WordPress database object for queries.
     *
     * @param string    $meta_type  Type of object metadata is for (e.g., gmedia, gmedia_term)
     * @param int|array $object_ids array or comma delimited list of object IDs to update cache for
     *
     * @return mixed Metadata cache for the specified objects, or false on failure.
     */
    function update_meta_cache($meta_type, $object_ids){
        /** @var $wpdb wpdb */
        global $wpdb;

        if(empty($meta_type) || empty($object_ids) || !in_array($meta_type, array('gmedia', 'gmedia_term'))){
            return false;
        }

        $table = $wpdb->prefix . $meta_type . '_meta';

        $column = esc_sql($meta_type . '_id');

        if(!is_array($object_ids)){
            $object_ids = preg_replace('|[^0-9,]|', '', $object_ids);
            $object_ids = explode(',', $object_ids);
        }

        $object_ids = array_map('intval', $object_ids);

        $cache_key = $meta_type . '_meta';
        $ids       = array();
        $cache     = array();
        foreach($object_ids as $id){
            $cached_object = wp_cache_get($id, $cache_key);
            if(false === $cached_object){
                $ids[] = $id;
            } else{
                $cache[ $id ] = $cached_object;
            }
        }

        if(empty($ids)){
            return $cache;
        }

        // Get meta info
        $id_list   = join(',', $ids);
        $meta_list = $wpdb->get_results("SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list)", ARRAY_A);

        if(!empty($meta_list)){
            foreach($meta_list as $metarow){
                $mpid = intval($metarow[ $column ]);
                $mkey = $metarow['meta_key'];
                $mval = $metarow['meta_value'];

                // Force subkeys to be array type:
                if(!isset($cache[ $mpid ]) || !is_array($cache[ $mpid ])){
                    $cache[ $mpid ] = array();
                }
                if(!isset($cache[ $mpid ][ $mkey ]) || !is_array($cache[ $mpid ][ $mkey ])){
                    $cache[ $mpid ][ $mkey ] = array();
                }

                // Add a value to the current pid/key:
                $cache[ $mpid ][ $mkey ][] = $mval;
            }
        }

        foreach($ids as $id){
            if(!isset($cache[ $id ])){
                $cache[ $id ] = array();
            }
            wp_cache_add($id, $cache[ $id ], $cache_key);
        }

        return $cache;
    }

    /**
     * Removes the taxonomy relationship to terms from the cache.
     * Will remove the entire taxonomy relationship containing term $object_id.
     * @see  clean_object_term_cache()
     * @see  get_object_taxonomies() for more on $object_type
     * @uses do_action() Will call action hook named, 'gmedia_clean_object_term_cache' after completion.
     *       Passes, function params in same order.
     *
     * @param int|array $object_ids Single or list of term object ID(s)
     * @param array     $taxonomies
     */
    function clean_object_term_cache($object_ids, $taxonomies = array()){
        if(!is_array($object_ids)){
            $object_ids = array($object_ids);
        }
        if(empty($taxonomies)){
            $taxonomies = array('gmedia_album', 'gmedia_category', 'gmedia_tag');
        }

        foreach($object_ids as $id){
            foreach($taxonomies as $taxonomy){
                wp_cache_delete($id, "{$taxonomy}_relationships");
            }
        }

        do_action('gmedia_clean_object_term_cache', $object_ids, $taxonomies);
    }

    /**
     * Will update term count based on number of objects.
     * @see  wp_update_term_count()
     * @uses $wpdb
     *
     * @param array $terms List of Term taxonomy IDs
     *
     * @return bool Always true when complete.
     */
    function update_term_count($terms){
        /** @var $wpdb wpdb */
        global $wpdb;

        if(!is_array($terms)){
            $terms = array($terms);
        }

        foreach((array)$terms as $term_id){
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}gmedia_term_relationships WHERE gmedia_term_id = %d", $term_id));

            do_action('update_gmedia_term_count', $term_id);
            $wpdb->update($wpdb->prefix . 'gmedia_term', compact('count'), array('term_id' => $term_id));
            do_action('updated_gmedia_term_count', $term_id);
        }

        $this->clean_term_cache($terms);

        return true;
    }

    /**
     * This function returns all roles, sorted by user level (lowest to highest)
     * @return array
     */
    function get_sorted_roles(){
        global $wp_roles;
        $roles  = $wp_roles->role_objects;
        $sorted = array();

        if(class_exists('RoleManager')){
            foreach($roles as $role_key => $role_name){
                $role = get_role($role_key);
                if(empty($role)){
                    continue;
                }
                $role_user_level            = array_reduce(array_keys($role->capabilities), array('WP_User', 'level_reduction'), 0);
                $sorted[ $role_user_level ] = $role;
            }
            $sorted = array_values($sorted);
        } else{
            $role_order = array("subscriber", "contributor", "author", "editor", "administrator");
            foreach($role_order as $role_key){
                $sorted[ $role_key ] = get_role($role_key);
            }
        }

        return $sorted;
    }

    /**
     * This function return the lowest roles which has the capabilities
     *
     * @param $capability
     *
     * @return bool
     */
    function get_role($capability){
        $check_order = $this->get_sorted_roles();

        $args = array_slice(func_get_args(), 1);
        $args = array_merge(array($capability), $args);

        foreach($check_order as $check_role){
            if(empty($check_role)){
                return false;
            }

            if(call_user_func_array(array(&$check_role, 'has_cap'), $args)){
                return $check_role->name;
            }
        }

        return false;
    }

    /**
     * This function set or remove the $capability
     *
     * @param $lowest_role
     * @param $capability
     */
    function set_capability($lowest_role, $capability){
        $check_order = $this->get_sorted_roles();

        $add_capability = false;

        foreach($check_order as $the_role){
            $role = $the_role->name;

            if($lowest_role == $role){
                $add_capability = true;
            }

            // If you rename the roles, the please use the role manager plugin
            if(empty($the_role)){
                continue;
            }

            /** @noinspection PhpUndefinedMethodInspection */
            $add_capability? $the_role->add_cap($capability) : $the_role->remove_cap($capability);
        }

    }

    /**
     * Reassign media to other blog user
     *
     * @param $user_id
     * @param $reassign
     */
    function reassign_media($user_id, $reassign){
        $gmedias    = $this->get_gmedias(array('nopaging' => true, 'author' => $user_id));
        $taxonomies = $this->get_terms(array('gmedia_album', 'gmedia_gallery', 'gmedia_module'), array('global' => $user_id));
        if(empty($reassign)){
            $reassign = get_current_user_id();
        }
        if(!empty($gmedias)){
            $modified = current_time('mysql');
            foreach($gmedias as $item){
                $item->author   = $reassign;
                $item->modified = $modified;
                $this->insert_gmedia($item);
            }
        }
        if(!empty($taxonomies)){
            foreach($taxonomies as $item){
                $this->update_term($item->term_id, array('global' => $reassign));
            }
        }
    }

}

global $gmDB;
$gmDB = new GmediaDB;
