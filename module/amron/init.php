<?php
global $wp;

$url_query                 = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
$allsettings               = array_merge( $module['options'], $settings );
$allsettings['module_url'] = $module['url'];
$allsettings['license']    = strtolower( $gmGallery->options['license_key'] );
$allsettings['post_url']   = remove_query_arg( 'gm' . $id, add_query_arg( $url_query, '', home_url( $wp->request ) ) );
$slug                      = 'amron';

if ( isset( $_GET[ 'gm' . $id ] ) ) {
	$app_info = false;
} else {
	$app_info = array(
		'name'        => $term->name,
		'description' => $term->description,
	);
}
?>
<script type="text/javascript">
	(function() {
		this['<?php echo esc_attr( $sc_id ); ?>'] = {
			'settings':<?php echo wp_json_encode( $allsettings ); ?>,
			'appQuery':<?php echo wp_json_encode( $query ); ?>,
			'appApi':<?php echo wp_json_encode( add_query_arg( array( 'gmedia-app' => 1, 'gmappversion' => 4, 'gmmodule' => 1 ), home_url( '/' ) ) ); ?>,
			'appInfo':<?php echo wp_json_encode( $app_info ); ?>};
	})();
</script>
