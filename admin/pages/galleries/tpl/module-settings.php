<?php
// don't load directly
if(!defined('ABSPATH')) {
    die('-1');
}

/**
 * Module Settings
 */
?>
<div class="row">
	<div class="col-lg-5 tabable tabs-left">
		<ul class="nav nav-tabs" id="galleryTabs">
			<?php if(isset($module_info)) { ?>
				<li class="text-center">
					<strong><?php echo $module_info['title']; ?></strong><a href="#chooseModuleModal" data-toggle="modal" style="padding:5px 0;"><img src="<?php echo $term->module['url'] . '/screenshot.png'; ?>" alt="<?php echo esc_attr($module_info['title']); ?>" width="100" style="height:auto;"/></a>
				</li>
			<?php } else { ?>
				<li class="text-center"><strong><?php echo $term->module['name']; ?></strong>

					<p><?php _e('This module is broken or outdated. Please, go to Modules page and update/install module or choose another one for this gallery', 'grand-media'); ?></p>
					<a href="#chooseModuleModal" data-toggle="modal" style="padding:5px 0;"><img src="<?php echo $term->module['url'] . '/screenshot.png'; ?>" alt="<?php echo esc_attr($term->module['name']); ?>" width="100" style="height:auto;"/></a>
				</li>
			<?php } ?>
			<?php
			if(isset($options_tree)) {
				gmedia_gallery_options_nav($options_tree);
			}
			?>
		</ul>

		<div id="gallery_options_block" class="tab-content">
			<?php
			if(isset($options_tree)) {
				gmedia_gallery_options_fieldset($options_tree, $default_options, $gallery_settings);
			}
			?>
		</div>

	</div>
	<div class="col-lg-7">
		<?php if($term_id || isset($preset_module)) { ?>
			<div><b><?php _e('Gallery Preview:'); ?></b></div>
			<div class="gallery_preview">
				<iframe id="gallery_preview" name="gallery_preview" src="<?php echo add_query_arg($params, set_url_scheme($gallery_link_default, 'admin')); ?>"></iframe>
			</div>
		<?php } ?>
	</div>
</div>
<script type="text/javascript">
	jQuery(function($) {
		var hash = window.location.hash;
		if(hash) {
			$('#galleryTabs a').eq(hash.replace('#tab-', '')).tab('show');
		}
		$('.gallery_preview').resizable();
		$('#gmedia-edit-term').on('submit', function(e) {
            if($('#build_query_field').val() == ''){
                var conf_txt = "<?php _e("Warning: Query Args. field is empty! Show in gallery all files from Gmedia Library?") ?>";
                if(!GmediaFunction.confirm(conf_txt)){
                    e.preventDefault();
                    return false;
                }
            }
			$(this).attr('action', $(this).attr('action') + '#tab-' + $('#galleryTabs li.active').index());
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

		$('[data-watch]', main).each(function() {
			var el = $(this);
			gmedia_options_conditional_logic(el, 0);
			var event = el.attr('data-watch');
			if(event) {
				el.on(event, function() {
					if('change' == el.attr('data-watch')) {
						$(this).blur().focus();
					}
					gmedia_options_conditional_logic($(this), 400);
				});
			}
		});

		function gmedia_options_conditional_logic(el, slide) {
			if(el.is(':input')) {
				var val = el.val();
				var id = el.attr('id').toLowerCase();
				if(el.is(':checkbox') && !el[0].checked) {
					val = '0';
				}
				$('[data-' + id + ']', main).each(function() {
					var key = $(this).attr('data-' + id);
					key = key.split(':');
					//var hidden = $(this).data('hidden')? parseInt($(this).data('hidden')) : 0;
					var hidden = $(this).data('hidden')? $(this).data('hidden') : {};
					var ch = true;
					switch(key[0]) {
						case '=':
						case 'is':
							if(val == key[1]) {
								delete hidden[id];
								if(slide && $.isEmptyObject(hidden)) {
									$(this).prop('disabled', false).closest('.form-group').stop().slideDown(slide, function() {
										$(this).css({display: 'block'});
									});
									if(key[2]) {
										key[2] = $(this).data('value');
									} else {
										ch = false;
									}
								} else {
									ch = false;
								}
								$(this).data('hidden', hidden);
							} else {
								if($.isEmptyObject(hidden)) {
									if(key[2]) {
										$(this).closest('.form-group').stop().slideUp(slide, function() {
											$(this).css({display: 'none'});
										});
									} else {
										$(this).prop('disabled', true).closest('.form-group').stop().slideUp(slide, function() {
											$(this).css({display: 'none'});
										});
									}
								} else {
									ch = false;
								}
								hidden[id] = 1;
								$(this).data('hidden', hidden);
							}
							break;
						case '!=':
						case 'not':
							if(val == key[1]) {
								if($.isEmptyObject(hidden)) {
									if(key[2]) {
										$(this).closest('.form-group').stop().slideUp(slide, function() {
											$(this).css({display: 'none'});
										});
									} else {
										$(this).prop('disabled', true).closest('.form-group').stop().slideUp(slide, function() {
											$(this).css({display: 'none'});
										});
									}
								} else {
									ch = false;
								}
								hidden[id] = 1;
								$(this).data('hidden', hidden);
							} else {
								delete hidden[id];
								if(slide && $.isEmptyObject(hidden)) {
									$(this).prop('disabled', false).closest('.form-group').stop().slideDown(slide, function() {
										$(this).css({display: 'block'});
									});
									if(key[2] && slide) {
										key[2] = $(this).data('value');
									} else {
										ch = false;
									}
								} else {
									ch = false;
								}
								$(this).data('hidden', hidden);
							}
							break;
					}
					if(key[2] && ch) {
						if($(this).is(':checkbox')) {
							if(+($(this).prop('checked')) != parseInt(key[2])) {
								$(this).data('value', ($(this).prop('checked')? '1' : '0'));
								$(this).prop('checked', ('0' != key[2])).trigger('change');
							}
						} else {
							if($(this).val() != key[2]) {
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

