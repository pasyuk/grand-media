<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

/**
 * Module List Item
 */

global $gmDB, $gmCore, $user_ID;
?>
<div class="media<?php echo $module['mclass']; ?>">
    <div class="row">
        <div class="col-sm-3">
            <div class="thumbnail">
                <img class="media-object" src="<?php echo $module['screenshot_url']; ?>" alt="<?php echo esc_attr( $module['title'] ); ?>" width="320" height="240"/>
            </div>
        </div>
        <div class="col-sm-5">
            <h4 class="media-heading"><?php echo $module['title']; ?></h4>

            <p class="version"><?php echo __( 'Version', 'grand-media' ) . ': ' . $module['version']; ?></p>
            <?php if ( isset( $module['info'] ) ) { ?>
                <div class="module_info"><?php echo str_replace( "\n", '<br />', (string) $module['info'] ); ?></div>
            <?php } ?>
            <div class="description"><?php echo str_replace( "\n", '<br />', (string) $module['description'] ); ?></div>
            <hr/>
            <p class="buttons">
                <?php
                $buttons = gmedia_module_action_buttons( $module );
                echo implode( ' ', $buttons );
                ?>
            </p>
        </div>
        <div class="col-sm-4">
            <div id="module_presets_list" class="module_presets module_presets_<?php echo $module['name'] ?>">
                <h4 class="media-heading" style="margin-bottom:10px;">
                    <a href="<?php echo $gmCore->get_admin_url( array( 'page' => 'GrandMedia_Modules', 'preset_module' => $module['name'] ), array(), admin_url( 'admin.php' ) ); ?>" class="addpreset pull-right"><span class="label label-success">+</span></a>
                    <?php _e( 'Presets', 'grand-media' ); ?></h4>
                <?php
                $presets = $gmDB->get_terms( 'gmedia_module', array( 'status' => $module['name'] ) );
                if ( ! empty( $presets ) ) {
                    ?>
                    <ul class="list-group presetlist">
                        <?php foreach ( $presets as $preset ) {
                            $count = 1;
                            $name  = trim( str_replace( '[' . $module['name'] . ']', '', $preset->name, $count ) );
                            if ( ! $name ) {
                                $name = __( 'Default Settings', 'grand-media' );
                            }
                            $by   = ' <small style="white-space:nowrap">[' . get_the_author_meta( 'display_name', $preset->global ) . ']</small>';
                            $href = $gmCore->get_admin_url( array( 'page' => 'GrandMedia_Modules', 'preset' => $preset->term_id ), array(), admin_url( 'admin.php' ) );
                            ?>
                            <li class="list-group-item" id="gm-preset-<?php echo $preset->term_id; ?>">
                                <span class="gm-preset-id">ID: <?php echo $preset->term_id; ?></span>
                                <?php if ( $user_ID == $preset->global || $gmCore->caps['gmedia_edit_others_media'] ) { ?>
                                    <span class="delpreset"><span class="label label-danger" data-id="<?php echo $preset->term_id; ?>">&times;</span></span>
                                <?php } ?>
                                <a href="<?php echo $href; ?>"><?php echo $name . $by; ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
