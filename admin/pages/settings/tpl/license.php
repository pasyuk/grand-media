<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * License Key
 */
?>
<fieldset id="gmedia_premium" class="tab-pane active">
    <p><?php _e('Enter License Key to remove backlink label from premium gallery modules.') ?></p>

    <div class="row">
        <div class="form-group col-xs-5">
            <label><?php _e('License Key', 'grand-media') ?>: <?php if (isset($gmGallery->options['license_name'])) {
                    echo '<em>' . $gmGallery->options['license_name'] . '</em>';
                } ?></label>
            <input type="text" name="set[license_key]" id="license_key" class="form-control input-sm" value="<?php echo $lk; ?>"/>

            <div class="manual_license_activate"<?php echo(('manual' == $gmCore->_get('license_activate')) ? '' : ' style="display:none;"'); ?>>
                <label style="margin-top:7px;"><?php _e('License Name', 'grand-media') ?>:</label>
                <input type="text" name="set[license_name]" id="license_name" class="form-control input-sm" value="<?php echo $gmGallery->options['license_name']; ?>"/>
                <label style="margin-top:7px;"><?php _e('Additional Key', 'grand-media') ?>:</label>
                <input type="text" name="set[license_key2]" id="license_key2" class="form-control input-sm" value="<?php echo $gmGallery->options['license_key2']; ?>"/>
            </div>
        </div>
        <?php if (! ('manual' == $gmCore->_get('license_activate') || ! empty($lk))) { ?>
            <div class="form-group col-xs-7">
                <label>&nbsp;</label>
                <button style="display:block;" class="btn btn-success btn-sm" type="submit" name="license-key-activate"><?php _e('Activate Key', 'grand-media'); ?></button>
            </div>
        <?php } ?>
    </div>
</fieldset>
