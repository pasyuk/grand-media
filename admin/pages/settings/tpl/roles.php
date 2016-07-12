<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Capabilities Manager
 *
 * @var $gmDB
 */
?>
<fieldset id="gmedia_settings_roles" class="tab-pane">
    <p><?php _e('Select the lowest role which should be able to access the follow capabilities. Gmedia Gallery supports the standard roles from WordPress.', 'grand-media'); ?></p>

    <div class="form-group">
        <label><?php _e('Gmedia Library', 'grand-media') ?>:</label>
        <select name="capability[gmedia_library]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_library')); ?></select>

        <p class="help-block"><?php _e('Who can view Gmedia Gallery admin pages', 'grand-media'); ?></p>
    </div>
    <hr/>

    <div class="form-group">
        <label><?php _e('Upload Media Files', 'grand-media') ?>:</label>
        <select name="capability[gmedia_upload]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_upload')); ?></select>

        <p class="help-block"><?php _e('Who can upload files to Gmedia Library', 'grand-media'); ?></p>
    </div>
    <div class="col-xs-offset-1">
        <div class="form-group">
            <label><?php _e('Import Media Files', 'grand-media') ?>:</label>
            <select name="capability[gmedia_import]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_import')); ?></select>

            <p class="help-block"><?php _e('Who can import files to Gmedia Library', 'grand-media'); ?></p>
        </div>
    </div>

    <div class="form-group">
        <label><?php _e('Show Others Media in Library', 'grand-media') ?>:</label>
        <select name="capability[gmedia_show_others_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_show_others_media')); ?></select>

        <p class="help-block"><?php _e('Who can see files uploaded by other users', 'grand-media'); ?></p>
    </div>
    <div class="form-group">
        <label><?php _e('Edit Media', 'grand-media') ?>:</label>
        <select name="capability[gmedia_edit_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_edit_media')); ?></select>

        <p class="help-block"><?php _e('Who can edit media title, description and other properties of uploaded files', 'grand-media'); ?></p>
    </div>
    <div class="col-xs-offset-1">
        <div class="form-group">
            <label><?php _e('Edit Others Media', 'grand-media') ?>:</label>
            <select name="capability[gmedia_edit_others_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_edit_others_media')); ?></select>

            <p class="help-block"><?php _e('Who can edit files, albums/tags and galleries of other users', 'grand-media'); ?></p>
        </div>
    </div>
    <div class="form-group">
        <label><?php _e('Delete Media', 'grand-media') ?>:</label>
        <select name="capability[gmedia_delete_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_delete_media')); ?></select>

        <p class="help-block"><?php _e('Who can delete uploaded files from Gmedia Library', 'grand-media'); ?></p>
    </div>
    <div class="col-xs-offset-1">
        <div class="form-group">
            <label><?php _e('Delete Others Media', 'grand-media') ?>:</label>
            <select name="capability[gmedia_delete_others_media]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_delete_others_media')); ?></select>

            <p class="help-block"><?php _e('Who can delete files, albums/tags and galleries of other users', 'grand-media'); ?></p>
        </div>
    </div>

    <div class="form-group">
        <label><?php _e('Albums, Tags...', 'grand-media') ?>:</label>
        <select name="capability[gmedia_terms]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_terms')); ?></select>

        <p class="help-block"><?php _e('Who can assign available terms to media files', 'grand-media'); ?></p>
    </div>
    <div class="col-xs-offset-1">
        <div class="form-group">
            <label><?php _e('Manage Albums', 'grand-media') ?>:</label>
            <select name="capability[gmedia_album_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_album_manage')); ?></select>

            <p class="help-block"><?php _e('Who can create and edit own albums. It is required "Edit Others Media" capability to edit others and shared albums', 'grand-media'); ?></p>
        </div>
        <div class="form-group">
            <label><?php _e('Manage Categories', 'grand-media') ?>:</label>
            <select name="capability[gmedia_category_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_category_manage')); ?></select>

            <p class="help-block"><?php _e('Who can create new categories. It is required "Edit Others Media" capability to edit categories', 'grand-media'); ?></p>
        </div>
        <div class="form-group">
            <label><?php _e('Manage Tags', 'grand-media') ?>:</label>
            <select name="capability[gmedia_tag_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_tag_manage')); ?></select>

            <p class="help-block"><?php _e('Who can create new tags. It is required "Edit Others Media" capability to edit tags', 'grand-media'); ?></p>
        </div>
        <div class="form-group">
            <label><?php _e('Delete Terms', 'grand-media') ?>:</label>
            <select name="capability[gmedia_terms_delete]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_terms_delete')); ?></select>

            <p class="help-block"><?php _e('Who can delete own albums. It is required "Delete Others Media" capability to delete others terms', 'grand-media'); ?></p>
        </div>
    </div>

    <div class="form-group">
        <label><?php _e('Galleries', 'grand-media') ?>:</label>
        <select name="capability[gmedia_gallery_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_gallery_manage')); ?></select>

        <p class="help-block"><?php _e('Who can create, edit and delete own galleries', 'grand-media'); ?></p>
    </div>

    <div class="form-group">
        <label><?php _e('Modules', 'grand-media') ?>:</label>
        <select name="capability[gmedia_module_manage]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_module_manage')); ?></select>

        <p class="help-block"><?php _e('Who can manage modules', 'grand-media'); ?></p>
    </div>

    <div class="form-group">
        <label><?php _e('Settings', 'grand-media') ?>:</label>
        <select name="capability[gmedia_settings]" class="form-control input-sm"><?php wp_dropdown_roles($gmDB->get_role('gmedia_settings')); ?></select>

        <p class="help-block"><?php _e('Who can change settings. Note: Capabilites can be changed only by administrator', 'grand-media'); ?></p>
    </div>

</fieldset>
