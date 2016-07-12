<?php // don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

if ( isset( $customfield_meta_type ) && $customfield_meta_type ) { ?>
    <div class="modal fade gmedia-modal" id="newCustomFieldModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php _e( 'Add New Custom Field' ); ?></h4>
                </div>
                <form class="modal-body" method="post" id="newCustomFieldForm">
                    <?php
                    echo $gmCore->meta_form( $customfield_meta_type );
                    wp_nonce_field( 'gmedia_custom_field', '_customfield_nonce' );
                    wp_referer_field();
                    ?>
                    <input type="hidden" name="action" value="<?php echo $customfield_meta_type; ?>_add_custom_field"/>
                    <input type="hidden" class="newcustomfield-for-id" name="ID" value=""/>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary customfieldsubmit"><?php _e( 'Add', 'grand-media' ); ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e( 'Close', 'grand-media' ); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>