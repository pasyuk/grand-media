<?php
/**
 * Gmedia Upload
 *
 * @var $user_ID
 * @var $gmProcessor
 * @var $gmCore
 */

// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

if(!gm_user_can('upload')) {
    _e('You do not have permissions to upload media', 'grand-media');

    return;
}

$maxupsize    = wp_max_upload_size();
$maxupsize    = floor($maxupsize * 0.99);
$maxupsize_mb = floor($maxupsize / 1024 / 1024);

$screen_options = $gmProcessor->user_options;

$gm_terms = array();

?>
<form class="row" id="gmUpload" name="upload_form" method="POST" accept-charset="utf-8" onsubmit="return false;">
    <div class="col-md-4" id="uploader_multipart_params">
        <br/>
        <?php if('false' == $screen_options['uploader_chunking'] || ('html4' == $screen_options['uploader_runtime'])) { ?>
            <p class="clearfix text-right"><span class="label label-default"><?php echo __('Maximum file size', 'grand-media') . ": {$maxupsize_mb}Mb"; ?></span></p>
        <?php } else { ?>
            <p class="clearfix text-right hidden">
                <span class="label label-default"><?php echo __('Maximum $_POST size', 'grand-media') . ": {$maxupsize_mb}Mb"; ?></span>
                <span class="label label-default"><?php echo __('Chunk size', 'grand-media') . ': ' . min($maxupsize_mb, $screen_options['uploader_chunk_size']) . 'Mb'; ?></span>
            </p>
        <?php } ?>

        <div class="form-group">
            <label><?php _e('Title', 'grand-media'); ?></label>
            <select name="set_title" class="form-control input-sm">
                <option value="exif"><?php _e('EXIF or File Name', 'grand-media'); ?></option>
                <option value="filename"><?php _e('File Name', 'grand-media'); ?></option>
                <option value="empty"><?php _e('Empty', 'grand-media'); ?></option>
            </select>
        </div>
        <div class="form-group">
            <label><?php _e('Status', 'grand-media'); ?></label>
            <select name="set_status" class="form-control input-sm">
                <option value="inherit"><?php _e('Same as Album or Public', 'grand-media'); ?></option>
                <option value="publish"><?php _e('Public', 'grand-media'); ?></option>
                <option value="private"><?php _e('Private', 'grand-media'); ?></option>
                <option value="draft"><?php _e('Draft', 'grand-media'); ?></option>
            </select>
        </div>

        <hr/>

        <?php include(dirname(__FILE__) . '/assign-terms.php'); ?>

    </div>
    <div class="col-md-8" id="pluploadUploader">
        <p><?php _e("You browser doesn't have Flash or HTML5 support. Check also if page have no JavaScript errors.", 'grand-media'); ?></p>
        <?php
        $mime_types = get_allowed_mime_types($user_ID);
        $type_ext   = array();
        $filters    = array();
        foreach($mime_types as $ext => $mime) {
            $type              = strtok($mime, '/');
            $type_ext[$type][] = $ext;
        }
        foreach($type_ext as $filter => $ext) {
            $filters[] = array(
                    'title'      => $filter,
                    'extensions' => str_replace('|', ',', implode(',', $ext))
            );
        }
        ?>
        <script type="text/javascript">
            // Convert divs to queue widgets when the DOM is ready
            jQuery(function($) {
                //noinspection JSDuplicatedDeclaration
                $("#pluploadUploader").plupload({
                    <?php if('auto' != $screen_options['uploader_runtime']){ ?>
                    runtimes: '<?php echo $screen_options['uploader_runtime']; ?>',
                    <?php } ?>
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    <?php if(('true' == $screen_options['uploader_urlstream_upload']) && ('html4' != $screen_options['uploader_runtime'])){ ?>
                    urlstream_upload: true,
                    multipart: false,
                    <?php } else{ ?>
                    multipart: true,
                    <?php } ?>
                    multipart_params: {action: 'gmedia_upload_handler', _ajax_nonce: '<?php echo wp_create_nonce('GmediaUpload'); ?>', params: ''},
                    <?php if('true' == $screen_options['uploader_chunking'] && ('html4' != $screen_options['uploader_runtime'])){ ?>
                    max_file_size: '2000Mb',
                    chunk_size: 200000<?php //echo min($maxupsize, $screen_options['uploader_chunk_size']*1024*1024); ?>,
                    <?php } else{ ?>
                    max_file_size: <?php echo $maxupsize; ?>,
                    <?php } ?>
                    max_retries: 2,
                    unique_names: false,
                    rename: true,
                    sortable: true,
                    dragdrop: true,
                    views: {
                        list: true,
                        thumbs: true,
                        active: 'thumbs'
                    },
                    filters: <?php echo json_encode($filters); ?>,
                    flash_swf_url: '<?php echo $gmCore->gmedia_url; ?>/assets/plupload/Moxie.swf',
                    silverlight_xap_url: '<?php echo $gmCore->gmedia_url; ?>/assets/plupload/Moxie.xap'

                });
                var closebtn = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
                var uploader = $("#pluploadUploader").plupload('getUploader');
                uploader.bind('StateChanged', function(up) {
                    if(up.state == plupload.STARTED) {
                        up.settings.multipart_params.params = jQuery('#uploader_multipart_params :input').serialize();
                    }
                    //console.log('[StateChanged]', up.state, up.settings.multipart_params);
                });
                uploader.bind('ChunkUploaded', function(up, file, info) {
                    //console.log('[ChunkUploaded] File:', file, "Info:", info);
                    var response = jQuery.parseJSON(info.response);
                    if(response && response.error) {
                        up.stop();
                        file.status = plupload.FAILED;
                        //jQuery('<div/>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message).appendTo('#gmedia-msg-panel');
                        console.log('[ChunkUploaded] ', response.error);
                        up.trigger('QueueChanged StateChanged');
                        up.trigger('UploadProgress', file);
                        up.start();
                    }
                });
                uploader.bind('FileUploaded', function(up, file, info) {
                    //console.log('[FileUploaded] File:', file, "Info:", info);
                    var response = jQuery.parseJSON(info.response);
                    if(response && response.error) {
                        file.status = plupload.FAILED;
                        jQuery('<div></div>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + response.id + ':</strong> ' + response.error.message).appendTo('#gmedia-msg-panel');
                        console.log('[FileUploaded] ', response.error);
                    }
                });
                uploader.bind('UploadProgress', function(up, file) {
                    var percent = uploader.total.percent;
                    $('#total-progress-info .progress-bar').css('width', percent + "%").attr('aria-valuenow', percent);
                });
                uploader.bind('Error', function(up, args) {
                    console.log('[Error] ', args);
                    jQuery('<div></div>').addClass('alert alert-danger alert-dismissable').html(closebtn + '<strong>' + args.file.name + ':</strong> ' + args.message + ' ' + args.status).appendTo('#gmedia-msg-panel');
                });
                uploader.bind('UploadComplete', function(up, files) {
                    console.log('[UploadComplete]', files);
                    $('#total-progress-info .progress-bar').css('width', '0').attr('aria-valuenow', '0');
                });

            });
        </script>
    </div>
</form>

