<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

/**
 * Edit Gallery Form
 */
?>

<form method="post" id="gmedia-edit-term" name="gmEditTerm" data-id="<?php echo $term_id; ?>" action="<?php echo $gmedia_url; ?>">
    <div class="panel-body">
        <h4 style="margin-top:0;">
            <?php if ( $term_id ) { ?>
                <span class="pull-right"><?php echo __( 'ID', 'grand-media' ) . ": {$term->term_id}"; ?></span>
                <?php printf( __( 'Edit %s Preset', 'grand-media' ), $term->module['info']['title'] ); ?>: <em><?php echo esc_html( $term->name ); ?></em>
            <?php } else {
                printf( __( 'New %s Preset', 'grand-media' ), $term->module['info']['title'] );
            } ?>
        </h4>
        <div class="row">
            <div class="col-sm-5">
                <div class="form-group">
                    <label><?php _e( 'Name', 'grand-media' ); ?></label>
                    <?php if ( $term_id && ! $term->name ) { ?>
                        <input type="text" class="form-control input-sm" name="term[name]" value="<?php _e( 'Default Settings', 'grand-media' ); ?>" readonly/>
                        <input type="hidden" name="module_preset_save_default" value="1"/>
                    <?php } else { ?>
                        <input type="text" class="form-control input-sm" name="term[name]" value="<?php echo esc_attr( $term->name ); ?>" placeholder="<?php echo $term->name ? esc_attr( $term->name ) : __( 'Preset Name', 'grand-media' ); ?>" required/>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label><?php _e( 'Author', 'grand-media' ); ?></label>
                    <?php gmedia_term_choose_author_field( $term->global ); ?>
                </div>
                <input type="hidden" name="term[term_id]" value="<?php echo $term_id; ?>"/>
                <input type="hidden" name="term[module]" value="<?php echo esc_attr( $term->module['name'] ); ?>"/>
                <input type="hidden" name="term[taxonomy]" value="<?php echo $gmedia_term_taxonomy; ?>"/>
                <?php
                wp_nonce_field( 'GmediaGallery' );
                wp_referer_field();
                ?>
                <div class="pull-right" id="save_buttons">
                    <button type="submit" name="module_preset_save_default" class="btn btn-default btn-sm"><?php _e( 'Save as Default Preset', 'grand-media' ); ?></button>
                    <button type="submit" name="gmedia_preset_save" class="btn btn-primary btn-sm"><?php _e( 'Save', 'grand-media' ); ?></button>
                </div>
            </div>

            <div class="col-sm-5 col-sm-offset-2">
                <div class="form-group">
                    <div class="pull-right"><a id="build_query" class="label label-primary buildquery-modal" href="#buildQuery" style="font-size:90%;"><?php _e( 'Build Query', 'grand-media' ); ?></a></div>
                    <label><?php _e( 'Query Args. for Preset Demo', 'grand-media' ); ?></label>
                    <textarea class="form-control input-sm" id="build_query_field" style="height:64px;" rows="2" name="term[query]"><?php echo( empty( $gmedia_filter['query_args'] ) ? 'limit=20' : urldecode( build_query( $gmedia_filter['query_args'] ) ) ); ?></textarea>
                </div>
            </div>
        </div>

        <hr/>
        <?php
        include( GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/module-settings.php' );
        ?>

    </div>

</form>

<?php
include( GMEDIA_ABSPATH . 'admin/pages/galleries/tpl/modal-build-query.php' );
?>
