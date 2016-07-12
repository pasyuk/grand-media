<?php
/**
 * Gmedia Gallery Edit
 */

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

global $user_ID, $gmDB, $gmCore, $gmGallery, $gmProcessor;

$term_id              = (int) $gmCore->_get( 'preset', 0 );
$preset_module        = $gmCore->_get( 'preset_module' );
$gmedia_url           = add_query_arg( array( 'preset_module' => $preset_module, 'preset' => $term_id ), $gmProcessor->url );
$gmedia_term_taxonomy = 'gmedia_module';
$taxterm              = str_replace( 'gmedia_', '', $gmedia_term_taxonomy );

if ( ! gm_user_can( "{$taxterm}_manage" ) ) {
    die( '-1' );
}

$term = $gmDB->get_term( $term_id );
gmedia_module_preset_more_data( $term );

$term_id = $term->term_id;

$gmedia_modules = get_gmedia_modules( false );

$default_module_demo_query_args = get_user_option('gmedia_preset_demo_query_args');
$gmedia_filter = gmedia_gallery_query_data($default_module_demo_query_args);
$default_options = array();

if ( isset( $gmedia_modules['in'][ $term->module['name'] ] ) ) {

    /**
     * @var $module_name
     * @var $module_path
     * @var $options_tree
     * @var $default_options
     */
    extract( $gmedia_modules['in'][ $term->module['name'] ] );
    if ( file_exists( $module_path . '/index.php' ) && file_exists( $module_path . '/settings.php' ) ) {
        /** @noinspection PhpIncludeInspection */
        include( $module_path . '/index.php' );
        /** @noinspection PhpIncludeInspection */
        include( $module_path . '/settings.php' );

    } else {
        $alert[] = sprintf( __( 'Module `%s` is broken. Choose another module from the list.' ), $module_name );
    }
} else {
    $alert[] = sprintf( __( 'Can\'t get module with name `%s`. Choose module from the list.' ), $term->module['name'] );
}

if ( ! empty( $alert ) ) {
    echo $gmCore->alert( 'danger', $alert );
}

if ( ! empty( $term->module['settings'] ) ) {
    $gallery_settings = $gmCore->array_replace_recursive( $default_options, $term->module['settings'] );
} else {
    $gallery_settings = $default_options;
}

$params = array();
$gallery_link_default = add_query_arg( array( 'page' => 'GrandMedia', 'gmediablank' => 'module_preview', 'module' => $term->module['name'], 'preset' => $term->term_id, 'query' => $gmedia_filter['query_args'] ), admin_url( 'admin.php' ) );

/** @noinspection PhpIncludeInspection */
include_once( GMEDIA_ABSPATH . '/inc/module.options.php' );

do_action( 'gmedia_module_preset_before_panel' );
?>

<div class="panel panel-default panel-fixed-header">

    <?php
    include( dirname( __FILE__ ) . '/tpl/module-preset-panel-heading.php' );

    include( dirname( __FILE__ ) . "/tpl/module-preset-edit-item.php" );
    ?>

</div>

<?php
do_action( 'gmedia_module_preset_after_panel' );
?>
