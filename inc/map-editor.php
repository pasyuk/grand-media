<?php
/**
 * @title  Image Editor
 */

function gmedia_map_editor() {
	global $gmCore, $gmDB;
	$gmid = $gmCore->_get( 'id' );
	$meta = $gmDB->get_metadata( 'gmedia', $gmid );

	$latlng       = array( 'lat' => 0, 'lng' => 0 );
	$marker       = false;
	$latlng_reset = '';

	if ( ! empty( $meta['_metadata'][0]['image_meta']['GPS'] ) ) {
		$latlng       = $meta['_metadata'][0]['image_meta']['GPS'];
		$marker       = true;
		$latlng_reset = implode( ', ', $latlng );
	}

	if ( ! empty( $meta['_gps'][0] ) ) {
		$latlng = $meta['_gps'][0];
		$marker = true;
	}
	//$latlng_literal = json_encode($latlng);
	?>

	<div class="card m-0 mw-100 p-0" id="gmedit">
		<div class="card-body">
			<div id="map-floating-panel">
				<style>#map-floating-panel input, #map-floating-panel button { font-family: "Roboto", "sans-serif"; font-size: 13px; }</style>
				<div class="input-group input-group-sm">
					<input id="geocode_address" type="text" placeholder="<?php esc_attr_e( 'location address', 'grand-media' ); ?>" value="" class="form-control input-sm gps_map_coordinates">
					<span class="input-group-btn">
						<button id="geocode_submit" class="btn btn-lg btn-success" type="button"><?php esc_html_e( 'Geocode', 'grand-media' ); ?></button>
					</span>
				</div>
			</div>
			<div id="map" style="height:410px;"></div>
		</div>
		<div class="card-footer clearfix">
			<div class="float-start well-sm"><?php esc_html_e( 'Coordinates:', 'grand-media' ); ?> <span id="latlng">&ndash;</span></div>
			<div class="btn-toolbar gap-3 float-end">
				<div class="btn-group">
					<button type="button" class="btn btn-secondary gps_cancel"><?php esc_html_e( 'Cancel', 'grand-media' ); ?></button>
				</div>
				<div class="btn-group">
					<button type="button" class="btn btn-secondary gps_reset"><?php esc_html_e( 'Reset', 'grand-media' ); ?></button>
				</div>
				<div class="btn-group">
					<button type="button" class="btn btn-primary gps_save"><?php esc_html_e( 'Save', 'grand-media' ); ?></button>
				</div>
				<?php wp_nonce_field( 'gmedia_edit', '_wpnonce_edit' ); ?>
			</div>
		</div>
	</div>
	<!--suppress JSUnresolvedVariable, JSUnresolvedFunction -->
	<script type="text/javascript" defer>
		jQuery(function($) {
			setTimeout(function() {
				checkMapsApi();
			}, 0);

			$('.gps_cancel').on('click', function() {
				window.parent.closeModal('gmeditModal');
			});
			var parent_doc = window.parent.document;
			//var gps_field = $('#list-item-<?php echo intval( $gmid ); ?> .gps_map_coordinates', parent_doc);
			$('.gps_reset').on('click', function() {
				$('#list-item-<?php echo intval( $gmid ); ?> .gps_map_coordinates', parent_doc).val('<?php echo esc_js( $latlng_reset ); ?>');
				parent.jQuery('#list-item-<?php echo intval( $gmid ); ?> .gps_map_coordinates').trigger('change');
				window.parent.closeModal('gmeditModal');
			});
			$('.gps_save').on('click', function() {
				if (coord) {
					$('#list-item-<?php echo intval( $gmid ); ?> .gps_map_coordinates', parent_doc).val(coord);
					parent.jQuery('#list-item-<?php echo intval( $gmid ); ?> .gps_map_coordinates').trigger('change');
				}
				window.parent.closeModal('gmeditModal');
			});
		});

		var latlng, map, marker, coord, coord_div = jQuery('#latlng');

		function checkMapsApi() {
			if (typeof window.google === 'object' && typeof window.google.maps === 'object') {
				handleApiReady();
			}
			else {
				var script = document.createElement('script');
				script.type = 'text/javascript';
				script.src = '//maps.googleapis.com/maps/api/js?v=3&key=' + GmediaGallery.google_api_key + '&callback=handleApiReady';
				document.body.appendChild(script);
			}
		}

		function handleApiReady() {
			console.log('Google Maps API version: ' + google.maps.version);
			latlng = new google.maps.LatLng(<?php echo esc_js( "{$latlng['lat']}, {$latlng['lng']}" ); ?>);
			map = new google.maps.Map(document.getElementById('map'), {
				center: latlng,
				zoom: <?php echo $marker ? '11' : '2'; ?>,
				mapTypeControl: false,
				streetViewControl: false,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			});
			var geocoder = new google.maps.Geocoder();

			document.getElementById('geocode_submit').addEventListener('click', function() {
				geocodeAddress(geocoder, map);
			});
			google.maps.event.addListener(map, 'click', function(event) {
				placeMarker(event.latLng);
			});
			<?php
			if ( $marker ) {
				echo 'placeMarker(latlng);';
			}
			?>
		}

		function placeMarker(location) {
			//console.log(location);
			if (marker) {
				marker.setPosition(location);
			}
			else {
				marker = new google.maps.Marker({
					position: location,
					map: map,
					title: 'Set lat/lon values for this property',
					draggable: true
				});
				google.maps.event.addListener(marker, 'dragend', function(a) {
					//console.log(a);
					coord = a.latLng.lat().toFixed(4) + ', ' + a.latLng.lng().toFixed(4);
					coord_div.html(coord);
				});
			}
			coord = location.lat().toFixed(4) + ', ' + location.lng().toFixed(4);
			coord_div.html(coord);
		}

		function geocodeAddress(geocoder, resultsMap) {
			var address = document.getElementById('geocode_address').value;
			geocoder.geocode({'address': address}, function(results, status) {
				if (status === google.maps.GeocoderStatus.OK) {
					resultsMap.setCenter(results[0].geometry.location);
					placeMarker(results[0].geometry.location);
				}
				else {
					alert('<?php esc_html_e( 'Geocode was not successful for the following reason:', 'grand-media' ); ?> ' + status);
				}
			});
		}

	</script>
	<?php
}
