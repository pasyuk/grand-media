<?php // don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Edit Album Form
 */

$_orderby       = isset( $term->meta['_orderby'][0] ) ? $term->meta['_orderby'][0] : $gmGallery->options['in_category_orderby'];
$_order         = isset( $term->meta['_order'][0] ) ? $term->meta['_order'][0] : $gmGallery->options['in_category_order'];
$_module_preset = isset( $term->meta['_module_preset'][0] ) ? $term->meta['_module_preset'][0] : '';
?>
<form method="post" id="gmedia-edit-term" name="gmEditTerm" class="panel-body" data-id="<?php echo $term->term_id; ?>">
    <h4 style="margin-top:0;">
        <span class="pull-right"><?php echo __( 'ID', 'grand-media' ) . ": {$term->term_id}"; ?></span>
        <?php _e( 'Edit Category' ); ?>: <em><?php echo esc_html( $term->name ); ?></em>
    </h4>

    <div class="row">
        <div class="col-xs-6">
            <div class="form-group">
                <label><?php _e( 'Name', 'grand-media' ); ?></label>
                <input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr( $term->name ); ?>" placeholder="<?php _e( 'Category Name', 'grand-media' ); ?>" required/>
            </div>
            <div class="form-group">
                <label><?php _e( 'Description', 'grand-media' ); ?></label>
                <textarea class="form-control input-sm" style="height:98px;" rows="2" name="term[description]"><?php echo $term->description; ?></textarea>
            </div>
            <div class="text-right">
                <?php
                wp_nonce_field( 'GmediaTerms', 'term_save_wpnonce' );
                wp_referer_field();
                ?>
                <input type="hidden" name="term[term_id]" value="<?php echo $term->term_id; ?>"/>
                <input type="hidden" name="term[taxonomy]" value="<?php echo $term->taxonomy; ?>"/>
                <button type="submit" class="btn btn-primary btn-sm" name="gmedia_category_save"><?php _e( 'Update', 'grand-media' ); ?></button>
            </div>
        </div>
        <div class="col-xs-6">
            <div class="row">
                <div class="col-xs-6">
                    <div class="form-group">
                        <label><?php _e( 'Order gmedia', 'grand-media' ); ?></label>
                        <select name="term[meta][_orderby]" class="form-control input-sm">
                            <option value="ID"<?php selected( $_orderby, 'ID' ); ?>><?php _e( 'by ID', 'grand-media' ); ?></option>
                            <option value="title"<?php selected( $_orderby, 'title' ); ?>><?php _e( 'by title', 'grand-media' ); ?></option>
                            <option value="gmuid"<?php selected( $_orderby, 'gmuid' ); ?>><?php _e( 'by filename', 'grand-media' ); ?></option>
                            <option value="date"<?php selected( $_orderby, 'date' ); ?>><?php _e( 'by date', 'grand-media' ); ?></option>
                            <option value="modified"<?php selected( $_orderby, 'modified' ); ?>><?php _e( 'by last modified date', 'grand-media' ); ?></option>
                            <option value="rand"<?php selected( $_orderby, 'rand' ); ?>><?php _e( 'Random', 'grand-media' ); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php _e( 'Sort order', 'grand-media' ); ?></label>
                        <select name="term[meta][_order]" class="form-control input-sm">
                            <option value="DESC"<?php selected( $_order, 'DESC' ); ?>><?php _e( 'DESC', 'grand-media' ); ?></option>
                            <option value="ASC"<?php selected( $_order, 'ASC' ); ?>><?php _e( 'ASC', 'grand-media' ); ?></option>
                        </select>
                    </div>
                    <?php

                    ?>
                    <div class="form-group">
                        <label><?php _e( 'Module/Preset', 'grand-media' ); ?></label>
                        <select class="form-control input-sm" id="term_module_preset" name="term[meta][_module_preset]">
                            <option value=""<?php if ( empty( $term->meta['_module_preset'][0] ) ) {
                                echo ' selected="selected"';
                            } ?>><?php _e( 'Default module in Global Settings', 'grand-media' ); ?></option>
                            <?php global $gmDB, $user_ID, $gmGallery;
                            $gmedia_modules = get_gmedia_modules( false );
                            foreach ( $gmedia_modules['in'] as $mfold => $module ) {
                                echo '<optgroup label="' . esc_attr( $module['title'] ) . '">';
                                $presets  = $gmDB->get_terms( 'gmedia_module', array( 'status' => $mfold ) );
                                $selected = selected( $_module_preset, esc_attr( $mfold ), false );
                                $option   = array();
                                $option[] = '<option ' . $selected . ' value="' . esc_attr( $mfold ) . '">' . $module['title'] . ' - ' . __( 'Default Settings' ) . '</option>';
                                foreach ( $presets as $preset ) {
                                    $selected  = selected( $_module_preset, $preset->term_id, false );
                                    $by_author = ' [' . get_the_author_meta( 'display_name', $preset->global ) . ']';
                                    if ( '[' . $mfold . ']' === $preset->name ) {
                                        $option[] = '<option ' . $selected . ' value="' . $preset->term_id . '">' . $module['title'] . $by_author . ' - ' . __( 'Default Settings' ) . '</option>';
                                    } else {
                                        $preset_name = str_replace( '[' . $mfold . '] ', '', $preset->name );
                                        $option[]    = '<option ' . $selected . ' value="' . $preset->term_id . '">' . $module['title'] . $by_author . ' - ' . $preset_name . '</option>';
                                    }
                                }
                                echo implode( '', $option );
                                echo '</optgroup>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-xs-6">
                    <?php $cover_id = isset( $term->meta['_cover'][0] ) ? $term->meta['_cover'][0] : ''; ?>
                    <div class="form-group">
                        <label><?php _e( 'Category Cover', 'grand-media' ); ?></label>
                        <input type="text" class="form-control input-sm" name="term[meta][_cover]" value="<?php echo esc_attr( $cover_id ); ?>" placeholder="<?php _e( 'Gmedia Image ID', 'grand-media' ); ?>"/>
                    </div>
                    <?php
                    if ( ( $cover_id = intval( $cover_id ) ) ) {
                        if ( ( $cover = $gmDB->get_gmedia( $cover_id ) ) ) { ?>
                            <div class="gm-img-thumbnail" data-gmid="<?php echo $cover->ID; ?>"><?php
                                ?><img src="<?php echo $gmCore->gm_get_media_image( $cover, 'thumb', true ); ?>" alt="<?php echo $cover->ID; ?>" title="<?php echo esc_attr( $cover->title ); ?>"/><?php
                                ?><span class="label label-default">ID: <?php echo $cover->ID; ?></span><?php
                                ?></div>
                        <?php } else {
                            echo '<strong class="text-danger">' . __( 'No image with such ID', 'grand-media' ) . '</strong>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <hr/>
    <?php
    $gmCore->gmedia_custom_meta_box( $term->term_id, $meta_type = 'gmedia_term' );
    do_action( 'gmedia_term_edit_form' );
    ?>
</form>

<div class="modal fade gmedia-modal" id="newCustomFieldModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php _e( 'Add New Custom Field' ); ?></h4>
            </div>
            <form class="modal-body" method="post" id="newCustomFieldForm">
                <?php
                echo $gmCore->meta_form( $meta_type = 'gmedia_term' );
                wp_nonce_field( 'gmedia_custom_field', '_customfield_nonce' );
                wp_referer_field();
                ?>
                <input type="hidden" name="action" value="gmedia_term_add_custom_field"/>
                <input type="hidden" class="newcustomfield-for-id" name="ID" value=""/>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary customfieldsubmit"><?php _e( 'Add', 'grand-media' ); ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e( 'Close', 'grand-media' ); ?></button>
            </div>
        </div>
    </div>
</div>
