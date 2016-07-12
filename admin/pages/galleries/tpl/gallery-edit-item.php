<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Edit Gallery Form
 */
?>

<form method="post" id="gmedia-edit-term" name="gmEditTerm" data-id="<?php echo $term_id; ?>" action="<?php echo $gmedia_url; ?>">
    <div class="panel-body">
        <!--<h4 style="margin-top:0;">
            <?php /*if($term_id) { */ ?>
                <span class="pull-right"><?php /*echo __('ID', 'grand-media') . ": {$term->term_id}"; */ ?></span>
                <?php /*_e('Edit Gallery'); */ ?>: <em><?php /*echo esc_html($term->name); */ ?></em>
            <?php /*} else {
                _e('New Gallery');
            } */ ?>
        </h4>-->

        <div class="row">
            <div class="col-sm-8">
                <div class="row">
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label><?php _e( 'Name', 'grand-media' ); ?></label>
                            <input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr( $term->name ); ?>" placeholder="<?php _e( 'Gallery Name', 'grand-media' ); ?>" required/>
                        </div>
                        <div class="form-group">
                            <label><?php _e( 'Slug', 'grand-media' ); ?></label>
                            <input type="text" class="form-control input-sm" name="term[slug]" value="<?php echo esc_attr( $term->slug ); ?>"/>
                        </div>
                        <div class="form-group">
                            <label><?php _e( 'Description', 'grand-media' ); ?></label>
                            <textarea class="form-control input-sm" style="height:64px;" rows="2" name="term[description]"><?php echo $term->description; ?></textarea>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label><?php _e( 'Author', 'grand-media' ); ?></label>
                            <?php gmedia_term_choose_author_field( $term->global ); ?>
                        </div>
                        <div class="form-group">
                            <label><?php _e( 'Status', 'grand-media' ); ?></label>
                            <select name="term[status]" class="form-control input-sm">
                                <option value="publish"<?php selected( $term->status, 'publish' ); ?>><?php _e( 'Public', 'grand-media' ); ?></option>
                                <option value="private"<?php selected( $term->status, 'private' ); ?>><?php _e( 'Private', 'grand-media' ); ?></option>
                                <option value="draft"<?php selected( $term->status, 'draft' ); ?>><?php _e( 'Draft', 'grand-media' ); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="pull-right"><a id="build_query" class="label label-primary buildquery-modal" href="#buildQuery" style="font-size:90%;"><?php _e( 'Build Query', 'grand-media' ); ?></a></div>
                            <label><?php _e( 'Query Args.', 'grand-media' ); ?></label>
                            <textarea class="form-control input-sm" id="build_query_field" style="height:64px;"
                                      placeholder="<?php _e("Click 'Build Query' button for help with Query Args.\nIf you leave this field empty then whole Library will be loaded. That's could exceed your server's PHP Memory Limit.", 'grand-media') ?>"
                                      rows="2" name="term[query]"><?php echo( empty( $gmedia_filter['query_args'] ) ? '' : urldecode( build_query( $gmedia_filter['query_args'] ) ) ); ?></textarea>
                        </div>
                    </div>
                </div>
                <?php
                $gmCore->gmedia_custom_meta_box( $term->term_id, $meta_type = 'gmedia_term' );
                do_action( 'gmedia_term_edit_form' );
                ?>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    <label>&nbsp;
                        <input type="hidden" name="term[term_id]" value="<?php echo $term_id; ?>"/>
                        <input type="hidden" name="term[taxonomy]" value="<?php echo $gmedia_term_taxonomy; ?>"/>
                        <?php
                        wp_nonce_field( 'GmediaGallery' );
                        wp_referer_field();
                        ?>
                    </label>
                    <div>
                        <div class="btn-group btn-group-sm" id="save_buttons">
                            <?php if ( $term->module['name'] != $term->meta['_module'] ) { ?>
                                <a href="<?php echo $gmedia_url; ?>" class="btn btn-default"><?php _e( 'Cancel preview module', 'grand-media' ); ?></a>
                                <button type="submit" name="gmedia_gallery_save" class="btn btn-primary"><?php _e( 'Save with new module', 'grand-media' ); ?></button>
                            <?php } else { ?>
                                <?php $reset_settings = $gmCore->array_diff_keyval_recursive( $default_options, $gallery_settings, true );
                                if ( ! empty( $reset_settings ) ) {
                                    ?>
                                    <button type="submit" name="gmedia_gallery_reset" class="btn btn-default" data-confirm="<?php _e( 'Confirm reset module settings to default preset' ) ?>"><?php _e( 'Reset to default', 'grand-media' ); ?></button>
                                <?php } ?>
                                <button type="submit" name="gmedia_gallery_save" class="btn btn-primary"><?php _e( 'Save', 'grand-media' ); ?></button>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <p><b><?php _e( 'Gallery ID:' ); ?></b> #<?php echo $term_id; ?></p>
                <p><b><?php _e( 'Last edited:' ); ?></b> <?php echo $term->meta['_edited']; ?></p>
                <p><?php echo '<b>' . __( 'Gallery module:' ) . '</b> <a href="#chooseModuleModal" data-toggle="modal">' . $term->meta['_module'] . '</a>';
                    if ( $term->module['name'] != $term->meta['_module'] ) {
                        echo '<br /><b>' . __( 'Preview module:' ) . '</b> ' . $term->module['name'];
                        echo '<br /><span class="text-danger">' . sprintf( __( 'Note: Module changed to %s, but not saved yet' ), $term->module['name'] ) . '</span>';
                    } ?></p>
                <input type="hidden" name="term[module]" value="<?php echo esc_attr( $term->module['name'] ); ?>">
                <?php if ( $term_id ) {
                    $params = array();
                    if ( $term->module['name'] != $term->meta['_module'] ) {
                        $params['gmedia_module'] = $term->module['name'];
                    }
                    $params['iframe'] = 1;
                    ?>
                    <p><b><?php _e( 'GmediaCloud page URL for current gallery:' ); ?></b> <?php
                        $endpoint             = $gmGallery->options['endpoint'];
                        $gmedia_hashid        = gmedia_hash_id_encode( $term_id, 'gallery' );
                        $gallery_link_default = add_query_arg( array( "$endpoint" => $gmedia_hashid, 't' => 'g' ), home_url( 'index.php' ) );
                        if ( get_option( 'permalink_structure' ) ) {
                            $gallery_link = home_url( urlencode( $endpoint ) . '/g/' . $gmedia_hashid );
                        } else {
                            $gallery_link = $gallery_link_default;
                        } ?>
                        <br/><a target="_blank" href="<?php echo $gallery_link; ?>"><?php echo $gallery_link; ?></a>
                    </p>
                    <?php if ( $term->post_id ) { ?>
                        <p><b><?php _e( 'Gmedia Post URL for current gallery:' ); ?></b>
                            <?php $post_link = get_permalink( $term->post_id ); ?>
                            <br/><a target="_blank" href="<?php echo $post_link; ?>"><?php echo $post_link; ?></a>
                        </p>
                    <?php } ?>
                    <div class="help-block">
                        <?php _e( 'update <a href="options-permalink.php">Permalink Settings</a> if above link not working', 'grand-media' ); ?>
                        <?php if ( current_user_can( 'manage_options' ) ) {
                            echo '<br>' . __( 'More info about GmediaCloud Pages and GmediaCloud Settings can be found <a href="admin.php?page=GrandMedia_Settings#gmedia_settings_cloud">here</a>', 'grand-media' );
                        } ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <hr/>
        <div class="well well-sm clearfix">
            <div class="btn-toolbar pull-right" id="module_preset">
                <div class="btn-group">
                    <button type="button" class="btn btn-default<?php echo ( $term->module['name'] != $term->meta['_module'] ) ? ' disabled' : ''; ?>" id="module_presets" data-toggle="popover"><?php _e( 'Module Presets', 'grand-media' ); ?></button>
                </div>
                <script type="text/html" id="_module_presets">
                    <div style="padding-top: 5px;">
                        <p style="white-space: nowrap">
                            <button type="button" name="module_preset_save_default" class="ajax-submit btn btn-default btn-sm"><?php _e( 'Save as Default', 'grand-media' ); ?></button>
                            &nbsp; <em><?php _e( 'or', 'grand-media' ); ?></em> &nbsp;
                            <?php if ( ! empty( $default_preset ) ) { ?>
                                <button type="button" name="module_preset_restore_original" class="ajax-submit btn btn-default btn-sm"><?php _e( 'Restore Original', 'grand-media' ); ?></button>
                                <input type="hidden" name="preset_default" value="<?php echo $default_preset['term_id']; ?>"/>
                            <?php } ?>
                        </p>
                        <div class="form-group clearfix" style="border-top: 1px solid #444444; padding-top: 5px;">
                            <label><?php _e( 'Save Preset as:', 'grand-media' ); ?></label>

                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control input-sm" name="module_preset_name" placeholder="<?php _e( 'Preset Name', 'grand-media' ); ?>" value=""/>
                                <span class="input-group-btn"><button type="button" name="module_preset_save_as" class="ajax-submit btn btn-primary"><?php _e( 'Save', 'grand-media' ); ?></button></span>
                            </div>
                        </div>

                        <?php if ( ! empty( $presets ) ) { ?>
                            <ul class="list-group presetlist">
                                <?php foreach ( $presets as $preset ) {
                                    $count = 1;
                                    $name  = trim( str_replace( '[' . $term->module['name'] . ']', '', $preset->name, $count ) );
                                    if ( ! $name ) {
                                        $name = __( 'Default Settings', 'grand-media' );
                                    }
                                    $by   = ' <small style="white-space:nowrap">[' . get_the_author_meta( 'display_name', $preset->global ) . ']</small>';
                                    $href = $gmCore->get_admin_url( array( 'preset' => $preset->term_id ), array() );
                                    ?>
                                    <li class="list-group-item" id="gm-preset-<?php echo $preset->term_id; ?>">
                                        <?php if ( $user_ID == $preset->global || $gmCore->caps['gmedia_edit_others_media'] ) { ?>
                                            <span class="delpreset"><span class="label label-danger" data-id="<?php echo $preset->term_id; ?>">&times;</span></span>
                                        <?php } ?>
                                        <a href="<?php echo $href; ?>"><?php echo $name . $by; ?></a>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                </script>
            </div>

            <h5><?php _e( 'Module Settings', 'grand-media' ); ?></h5>
        </div>
        <?php
        include( GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/module-settings.php' );
        ?>
        <?php if ( ! empty( $alert ) ) { ?>
            <script type="text/javascript">
                jQuery(function($) {
                    $('#chooseModuleModal').modal('show');
                });
            </script>
        <?php } ?>
    </div>

</form>

<?php

include( GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/modal-build-query.php' );

if ( $term_id ) {
    $customfield_meta_type = 'gmedia_term';
    include( GMEDIA_ABSPATH . 'admin/tpl/modal-customfield.php' );
}

?>

<?php if ( gm_user_can( 'edit_others_media' ) ) { ?>
    <div class="modal fade gmedia-modal" id="gallModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog"></div>
    </div>
<?php } ?>
