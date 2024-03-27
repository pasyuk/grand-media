<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Module Settings
 *
 * @var $term
 * @var $term_id
 * @var $default_options
 * @var $gallery_settings
 * @var $gallery_link_default
 */
global $gmGallery;
?>
<div class="row">
	<div class="col-lg-5 tabable tabs-left">
		<ul class="flex-column nav nav-tabs small" id="galleryTabs" style="max-width: 180px;">
			<?php if ( isset( $module_info ) ) { ?>
				<li class="d-flex flex-column text-center">
					<strong><?php echo esc_html( $module_info['title'] ); ?></strong><a href="#chooseModuleModal" data-bs-toggle="modal" style="padding:5px 0;"><img src="<?php echo esc_url( $term->module['url'] . '/screenshot.png' ); ?>" alt="<?php echo esc_attr( $module_info['title'] ); ?>" width="100" style="height:auto;"/></a>
				</li>
			<?php } else { ?>
				<li class="text-center"><strong><?php echo esc_html( $term->module['name'] ); ?></strong>

					<p><?php esc_html_e( 'This module is broken or outdated. Please, go to Modules page and update/install module or choose another one for this gallery', 'grand-media' ); ?></p>
					<a href="#chooseModuleModal" data-bs-toggle="modal" style="padding:5px 0;"><img src="<?php echo esc_url( $term->module['url'] . '/screenshot.png' ); ?>" alt="<?php echo esc_attr( $term->module['name'] ); ?>" width="100" style="height:auto;"/></a>
				</li>
			<?php } ?>
			<?php
			if ( isset( $options_tree ) ) {
				gmedia_gallery_options_nav( $options_tree );
			}
			?>
		</ul>

		<div id="gallery_options_block" class="tab-content">
			<?php
			if ( isset( $options_tree ) ) {
				gmedia_gallery_options_fieldset( $options_tree, $default_options, $gallery_settings );
			}
			?>
		</div>

	</div>
	<div class="col-lg-7">
		<?php
		if ( $term_id || isset( $preset_module ) ) {
			$bgcolor                    = empty( $gmGallery->options['preview_bgcolor'] ) ? 'ffffff' : $gmGallery->options['preview_bgcolor'];
			$params['is_admin_preview'] = 1;
			?>
			<div class="clearfix">
				<div class="form-group float-end" style="margin:-10px 0 5px 0;"><input type="text" data-type="color" class="form-control form-control-sm input-sm pt-0 pb-0" id="preview_color" name="preview_bgcolor" value="<?php echo esc_attr( $bgcolor ); ?>" placeholder="ffffff" size="7"/></div>
				<b><?php esc_html_e( 'Gallery Preview:' ); ?></b>
			</div>
			<div class="gallery_preview">
				<iframe id="gallery_preview" style="background-color:<?php echo esc_attr( "#$bgcolor" ); ?>;padding:5px;" name="gallery_preview" src="<?php echo esc_url( add_query_arg( $params, set_url_scheme( $gallery_link_default, 'admin' ) ) ); ?>"></iframe>
			</div>
		<?php } ?>
	</div>
</div>
<script type="text/javascript">
	jQuery(function($) {
		var hash = window.location.hash;
		if (hash) {
			$('#galleryTabs a').eq(hash.replace('#tab-', '')).tab('show');
		}
		$('#gmedia-edit-term').on('submit', function(e) {
			if ($('#build_query_field').val() === '') {
				var conf_txt = "<?php esc_attr_e( 'Warning: Query Args. field is empty! Show in gallery all files from Gmedia Library?', 'grand-media' ); ?>";
				if (!GmediaFunction.confirm(conf_txt)) {
					e.preventDefault();
					return false;
				}
			}
			$(this).attr('action', $(this).attr('action') + '#tab-' + $('#galleryTabs a.active').parent().index());
		});

		var main = $('#gallery_options_block');

		$('input', main).filter('[data-type="color"]').minicolors({
			animationSpeed: 50,
			animationEasing: 'swing',
			change: null,
			changeDelay: 0,
			control: 'hue',
			//defaultValue: '',
			hide: null,
			hideSpeed: 100,
			inline: false,
			letterCase: 'lowercase',
			opacity: false,
			position: 'bottom left',
			show: null,
			showSpeed: 100,
			theme: 'bootstrap'
		});
		$('#preview_color').on('change', function() {
			var color = $(this).val();
			$('#gallery_preview').css({backgroundColor: '#' + color});
		}).minicolors({
			animationSpeed: 50,
			animationEasing: 'swing',
			change: null,
			changeDelay: 0,
			control: 'hue',
			//defaultValue: '',
			hide: null,
			hideSpeed: 100,
			inline: false,
			letterCase: 'lowercase',
			opacity: false,
			position: 'bottom right',
			show: null,
			showSpeed: 100,
			theme: 'bootstrap'
		});

		$('input', main).filter('[data-type="rgba"]').spectrum({
			showInput: true,
			showAlpha: true,
			allowEmpty: false,
			preferredFormat: 'rgb'
		});

		$('[data-watch]', main).each(function() {
			var el = $(this);
			gmedia_options_conditional_logic(el, 0);
			var event = el.attr('data-watch');
			if (event) {
				el.on(event, function() {
					if ('change' === el.attr('data-watch')) {
						$(this).blur().focus();
					}
					gmedia_options_conditional_logic($(this), 400);
				});
			}
		});

		function gmedia_options_conditional_logic(el, slide) {
			if (el.is(':input')) {
				var val = el.val();
				var id = el.attr('id').toLowerCase();
				if (el.is(':checkbox') && !el[0].checked) {
					val = '0';
				}
				$('[data-' + id + ']', main).each(function() {
					var key = $(this).attr('data-' + id);
					key = key.split(':');
					//var hidden = $(this).data('hidden')? parseInt($(this).data('hidden')) : 0;
					var hidden = $(this).data('hidden') ? $(this).data('hidden') : {};
					var ch = true;
					switch (key[0]) {
						case '=':
						case 'is':
							if (val === key[1]) {
								delete hidden[id];
								if (slide && $.isEmptyObject(hidden)) {
									$(this).prop('disabled', false).closest('.form-group').stop().slideDown(slide, function() {
										$(this).css({display: 'block'});
									});
									if (key[2]) {
										key[2] = $(this).data('value');
									}
									else {
										ch = false;
									}
								}
								else {
									ch = false;
								}
								$(this).data('hidden', hidden);
							}
							else {
								if ($.isEmptyObject(hidden)) {
									if (key[2]) {
										$(this).closest('.form-group').stop().slideUp(slide, function() {
											$(this).css({display: 'none'});
										});
									}
									else {
										$(this).prop('disabled', true).closest('.form-group').stop().slideUp(slide, function() {
											$(this).css({display: 'none'});
										});
									}
								}
								else {
									ch = false;
								}
								hidden[id] = 1;
								$(this).data('hidden', hidden);
							}
							break;
						case '!=':
						case 'not':
							if (val === key[1]) {
								if ($.isEmptyObject(hidden)) {
									if (key[2]) {
										$(this).closest('.form-group').stop().slideUp(slide, function() {
											$(this).css({display: 'none'});
										});
									}
									else {
										$(this).prop('disabled', true).closest('.form-group').stop().slideUp(slide, function() {
											$(this).css({display: 'none'});
										});
									}
								}
								else {
									ch = false;
								}
								hidden[id] = 1;
								$(this).data('hidden', hidden);
							}
							else {
								delete hidden[id];
								if (slide && $.isEmptyObject(hidden)) {
									$(this).prop('disabled', false).closest('.form-group').stop().slideDown(slide, function() {
										$(this).css({display: 'block'});
									});
									if (key[2] && slide) {
										key[2] = $(this).data('value');
									}
									else {
										ch = false;
									}
								}
								else {
									ch = false;
								}
								$(this).data('hidden', hidden);
							}
							break;
					}
					if (key[2] && ch) {
						if ($(this).is(':checkbox')) {
							if (+($(this).prop('checked')) !== parseInt(key[2])) {
								$(this).data('value', ($(this).prop('checked') ? '1' : '0'));
								$(this).prop('checked', ('0' !== key[2])).trigger('change');
							}
						}
						else {
							if ($(this).val() !== key[2]) {
								$(this).data('value', $(this).val());
								$(this).val(key[2]).trigger('change');
							}
						}
					}
				});
			}
		}
	});

</script>
