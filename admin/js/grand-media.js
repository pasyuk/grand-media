/*
 * jQuery functions for GRAND Flash Media
 */
var GmediaSelect;
var GmediaFunction;
jQuery(function($){
	var gmedia_DOM = $('#gmedia-container');

	GmediaSelect = {
		msg_selected: function(obj, global){
			var gm_cb = $('.' + obj + ' input'),
				qty_v = gm_cb.length,
				sel_v = gm_cb.filter(':checked').length,
				c = $('#cb_global');
			if((sel_v != qty_v) && (0 !== sel_v)){
				c.css('opacity', '0.5').prop('checked', true);
			} else if((sel_v == qty_v) && (0 !== qty_v)){
				c.css('opacity', '1').prop('checked', true);
			} else if(0 === sel_v){
				c.css('opacity', '1').prop('checked', false);
			}

			if(!$('#gm-selected').length){
				return;
			}

			var sel = $('#gm-selected'),
				arr = sel.val().split(','),
				cur;

			arr = $.grep(arr, function(e){
				return(e);
			});
			if(global){
				cur = false;
				gm_cb.each(function(){
					cur = $(this);
					if(cur.is(':checked') && ($.inArray(cur.val(), arr) === -1)){
						arr.push(cur.val());
					} else if(!(cur.is(':checked')) && ($.inArray(cur.val(), arr) !== -1)){
						arr = $.grep(arr, function(e){
							return e != cur.val();
						});
					}
				});
				sel.val(arr.join(','));
			}

			if(sel.data('userid')){
				var storedData = getStorage('gmuser_' + sel.data('userid') + '_');
				storedData.set(sel.data('key'), arr);
			}
			$('#gm-selected-qty').text(arr.length);
			if(arr.length){
				$('#gm-selected-btn').removeClass('hidden');
				$('.rel-selected-show').show();
				$('.rel-selected-hide').hide();
			}
			else{
				$('#gm-selected-btn').addClass('hidden');
				$('.rel-selected-show').hide();
				$('.rel-selected-hide').show();
			}
			sel.trigger('change');
		},
		chk_all: function(type, obj){
			$('.' + obj + ' input').filter(function(){
				return type? $(this).data('type') == type : true;
			}).prop('checked', true).closest('div.list-group-item').addClass('active-row');
		},
		chk_none: function(type, obj){
			$('.' + obj + ' input').filter(function(){
				return type? $(this).data('type') == type : true;
			}).prop('checked', false).closest('div.list-group-item').removeClass('active-row');
		},
		chk_toggle: function(type, obj){
			if(type){
				if($('.' + obj + ' input:checked').filter(function(){
					return $(this).data('type') == type;
				}).length){
					GmediaSelect.chk_none(type, obj);
				} else{
					GmediaSelect.chk_all(type, obj);
				}
			} else{
				$('.' + obj + ' input').each(function(){
					$(this).prop("checked", !$(this).prop("checked")).closest('div.list-group-item').toggleClass('active-row');
				});
			}
		},
		init: function(){
			var cb_obj = $('#cb_global').data('group');

			if($('#gm-selected').length){
				GmediaSelect.msg_selected(cb_obj);
				$('#gm-selected-clear').click(function(e){
					$('#gm-selected').val('');
					var obj = $('#cb_global').data('group');
					GmediaSelect.chk_none(false, cb_obj);
					GmediaSelect.msg_selected(cb_obj);
					e.preventDefault();
				});
				$('#gm-selected-show').click(function(){
					$('#gm-selected-btn').submit();
					e.preventDefault();
				});
			}
			$('#cb_global').click(function(e){
				if($(this).is(':checked')){
					GmediaSelect.chk_all(false, cb_obj);
				} else{
					GmediaSelect.chk_none(false, cb_obj);
				}
				GmediaSelect.msg_selected(cb_obj, true);
			});
			$('#cb_global-btn li a').click(function(e){
				var sel = $(this).data('select');
				switch(sel){
					case 'total':
						GmediaSelect.chk_all(false, cb_obj);
						break;
					case 'none':
						GmediaSelect.chk_none(false, cb_obj);
						break;
					case 'reverse':
						GmediaSelect.chk_toggle(false, cb_obj);
						break;
					case 'image':
					case 'audio':
					case 'video':
						GmediaSelect.chk_toggle(sel, cb_obj);
						break;
				}
				GmediaSelect.msg_selected(cb_obj, true);
				e.preventDefault();
			});
			$('.cb_media-object input:checkbox, .cb_term-object input:checkbox').change(function(){
				var arr = $('#gm-selected').val();
				var cur = $(this).val();
				if($(this).is(':checked')){
					if(arr){
						arr = arr + ',' + cur;
					} else{
						arr = cur;
					}
				} else{
					arr = $.grep(arr.split(','),function(a){
						return a != cur;
					}).join(',');
				}
				$('#list-item-' + cur).toggleClass('active-row');
				$('#gm-selected').val(arr);
				GmediaSelect.msg_selected(cb_obj);
			});
			$('.term-label').click(function(e){
				if('DIV' == e.target.nodeName){
					if(!$('#gm-list-table').data('edit')){
						var cb = $('input:checkbox', this);
						cb.prop("checked", !cb.prop("checked")).change();
						$(this).closest('.term-list-item').toggleClass('active-row');
					} else{
						$('#gm-list-table').data('edit', false);
					}
				}
			});
		}
	};

	GmediaFunction = {
		confirm: function(txt){
			if(!txt){
				return true;
			}
			var r = false;
			try{
				r = confirm(txt);
			}
			catch(err){
				alert('Disable Popup Blocker');
			}
			return r;
		},
		init: function(){
			$('#toplevel_page_GrandMedia').addClass('current').removeClass('wp-not-current-submenu');
			if(!("ontouchstart" in document.documentElement)){
				$('html').addClass('no-touch');
			}

			/*
			 $(document).ajaxStart(function(){
			 $('body').addClass('gmedia-busy');
			 }).ajaxStop(function(){
			 $('body').removeClass('gmedia-busy');
			 });
			 */

			$('[data-confirm]').click(function(){
				return GmediaFunction.confirm($(this).data('confirm'));
			});

			$('div.gmedia-modal').appendTo('body');
			$('a.gmedia-modal').click(function(e){
				$('body').addClass('gmedia-busy');
				var modal_div = $($(this).attr('href'));
				var post_data = {
					action: $(this).data('action'), modal: $(this).data('modal'), _wpnonce: $('#_wpnonce').val()
				};
				$.post(ajaxurl, post_data, function(data, textStatus, jqXHR){
					if(!data || ('-1' == data)){
						$('body').removeClass('gmedia-busy');
						alert(data);
						return false;
					}
					$('.modal-dialog', modal_div).html(data);
					modal_div.modal({
						backdrop: 'static',
						show: true,
						keyboard: false
					}).one('hidden.bs.modal', function(e){
						$('.modal-dialog', this).empty();
					});
					$('body').removeClass('gmedia-busy');
				});
				e.preventDefault();
			});

			$('a.gmedit-modal').click(function(e){
				e.preventDefault();
				var modal_div = $($(this).data('target'));
				$('.modal-content', modal_div).html(
					$('<iframe />', {
						name: 'gmeditFrame',
						id: 'gmeditFrame',
						width: '100%',
						height: '500',
						src: $(this).attr('href')
					}).css({display: 'block', margin: '4px 0'})
				);
				modal_div.modal({
					backdrop: true,
					show: true,
					keyboard: false
				}).one('hidden.bs.modal', function(e){
					$('.modal-content', this).empty();
				});
			});

			$('a.preview-modal').click(function(e){
				e.preventDefault();
				var data = $(this).data(),
					modal_div = $(data['target']);
				$('.modal-title', modal_div).text($(this).attr('title'));

				if(data['metainfo']){
					$('.modal-dialog', modal_div).addClass('modal-md');
					$('.modal-body', modal_div).html($('#metainfo_'+data['metainfo']).html());
				} else{
					var r = data['width']/data['height'],
						w = Math.min( $(window).width() * 0.98 - 32, data['width']),
						h = w / r;
					$('.modal-dialog', modal_div).css({'width': (data['width'] + 32), 'max-width': '98%'});
					$('.modal-body', modal_div).html(
						$('<iframe />', {
							name: 'previewFrame',
							id: 'previewFrame',
							width: '100%',
							height: h,
							src: $(this).attr('href'),
							load: function(){
								$(this.contentWindow.document.body).css('margin', 0);
								$('.modal-backdrop', modal_div).css({'width': (data['width'] + 32), 'min-width': '100%'});
							}
						}).css({display: 'block', margin: '4px 0'})
					);
				}

				modal_div.modal({
					backdrop: true,
					show: true
				}).one('hidden.bs.modal', function(e){
					$('.modal-title', this).empty();
					$('.modal-body', this).empty();
					$('.modal-dialog', this).removeAttr('style').attr('class', 'modal-dialog');
				});
			});

			$('input.sharelink').on('click focus', function(){
				this.setSelectionRange(0, this.value.length);
			});
			$('input.sharetoemail').on('keyup', function(){
				$('.sharebutton').prop('disabled', !validateEmail(this.value));
			});
			$('.sharebutton').on('click', function(){
				var sharetoemail = $('input.sharetoemail');
				if(!validateEmail(sharetoemail.val())){
					sharetoemail.focus();
					sharetoemail.parent().addClass('has-error');
					return false;
				}
				var post_data = $('#shareForm').serialize();
				$.post(ajaxurl, post_data, function(data, textStatus, jqXHR){
					$('body').removeClass('gmedia-busy');
					if(data){
						$('#gm-message').append(data);
					}
				});
				$('#shareModal').modal('hide');
			});
			$('a.share-modal').click(function(e){
				e.preventDefault();
				var data = $(this).data(),
					modal_div = $(data['target']),
					link = $(this).attr('href'),
				 	sharetoemail = $('input.sharetoemail');

				$('input.sharelink', modal_div).val(link);
				$('.sharebutton').prop('disabled', !validateEmail(sharetoemail.val()));

				modal_div.modal({
					backdrop: false,
					show: true,
					keyboard: false
				}).one('shown.bs.modal', function(){
					$('input.sharelink', this).focus();
				}).one('hidden.bs.modal', function(){
					$('input.sharelink', this).val('');
				});
			});

			$('.customfieldsubmit').on('click', function(){
				var cform = $('#newCustomFieldForm');
				if(!$('.newcustomfield-for-id', cform).val()){
					$('#newCustomFieldModal').modal('hide');
					alert('No ID');
					return false;
				}
				var post_data = cform.serialize();
				$.post(ajaxurl, post_data, function(data, textStatus, jqXHR){
					$('body').removeClass('gmedia-busy');
					if(data.success){
						$('#newCustomFieldModal').modal('hide').one('hidden.bs.modal', function(){
							if(data.newmeta_form){
								$('#newmeta').replaceWith(data.newmeta_form);
							}
						});
						$('.row:last', '#gmediacustomstuff_' + data.id).append(data.success.data);
					} else{
						if(data.error){
							if('100' == data.error.code){
								$('#newCustomFieldModal').modal('hide');
							}
							alert(data.error.message);
						} else {
							console.log(data);
						}
					}
				});
			});
			$('a.newcustomfield-modal').click(function(e){
				e.preventDefault();
				var data = $(this).data(),
					modal_div = $($(this).attr('href'));

				modal_div.modal({
					backdrop: false,
					show: true,
					keyboard: false
				}).one('shown.bs.modal', function(){
					$('input.newcustomfield-for-id', this).val(data['gmid']);
				}).one('hidden.bs.modal', function(){
					$(':input.form-control, input.newcustomfield-for-id', this).val('');
					if( $('.newcfield', this).length ){
						$('a.gmediacustomstuff').click();
					}
				});
			});
			$('.gmediacustomstuff').on('click', '.delete-custom-field', function(){
				var t = $(this).closest('.form-group'),
					post_data = convertInputsToJSON($(':input', t));
				if(!post_data){
					return false;
				}
				var meta_type = $(this).closest('fieldset').attr('data-metatype');
				post_data.action = meta_type + '_delete_custom_field';
				post_data.ID = $(this).closest('form').attr('data-id');
				post_data._customfield_nonce = $('#_customfield_nonce').val();
				$.post(ajaxurl, post_data, function(data, textStatus, jqXHR){
					$('body').removeClass('gmedia-busy');
					if(data.deleted){
						$.each(data.deleted, function(i, val){
							$('.gm-custom-meta-' + val).remove();
						});
					} else{
						if(data.error){
							alert(data.error.message);
						} else {
							console.log(data);
						}
					}
				});
			});


			$('form.edit-gmedia').on('change', ':input', function(){
				$('body').addClass('gmedia-busy');
				var post_data = {
					action: 'gmedia_update_data', data: $(this).closest('form').serialize(), _wpnonce: $('#_wpnonce').val()
				};
				$.post(ajaxurl, post_data, function(data, textStatus, jqXHR){
					console.log(data);
					var item = $('#list-item-' + data.ID);
					item.find('.modified').text(data.modified);
					item.find('.status-album').attr('class', 'form-group status-album bg-status-' + data.album_status);
					item.find('.status-item').attr('class', 'form-group status-item bg-status-' + data.status);
					if(data.tags){
						item.find('.gmedia_tags_input').val(data.tags);
					}
					if(data.meta_error){
						$.each(data.meta_error, function(i, err){
							console.log(err);
							alert(err.meta_key + ': ' + err.message);
							if(err.meta_value){
								$('.gm-custom-field-'+err.meta_id).val(err.meta_value);
							}
						});
					}
					$('body').removeClass('gmedia-busy');
				});
			});

			gmedia_DOM.on('click', '.gm-toggle-cb', function(e){
				var checkBoxes = $(this).attr('href');
				$(checkBoxes + ' :checkbox').each(function(){
					$(this).prop("checked", !$(this).prop("checked"));
				});
				e.preventDefault();
			});
			$('.linkblock').on('click', '[data-href]', function(){
				window.location.href = $(this).data('href');
			});

			$('.gmedia-import').click(function(e){
				$('#import-action').val($(this).attr('name'));
				$('#importModal').modal({
					backdrop: 'static',
					show: true,
					keyboard: false
				}).one('shown.bs.modal',function(){
					$('#import_form').submit();
				}).one('hidden.bs.modal', function(){
                    var btn = $('#import-done');
					btn.text(btn.data('reset-text')).prop('disabled', true);
					$('#import_window').attr('src', 'about:blank');
				});
			});

			$('#gmedia_modules').on('click', '.module_install', function(e){
				e.preventDefault();
				$('body').addClass('gmedia-busy');
				var module = $(this).data('module');
                var btn = $('.module_install').filter('[data-module="' + module + '"]');
				btn.text(btn.data('loading-text'));
				var post_data = {
					action: 'gmedia_module_install', download: $(this).attr('href'), module: module, _wpnonce: $('#_wpnonce').val()
				};
				var pathname = window.location.href;
				$.post(ajaxurl, post_data, function(data, status, xhr){
					$('#gmedia_modules').load(pathname + ' #gmedia_modules > *').before(data);
					$('body').removeClass('gmedia-busy');
				});
			});

            $('form').on('keydown', ':input:visible:not(:submit,:button,:reset,textarea)', function (e) {
				var charCode = e.charCode || e.keyCode || e.which;
				if (13 == charCode && !$(this).parent().hasClass('selectize-input')) {
                    var inputs = $(this).parents("form").eq(0).find(":input:visible");
					var inp = inputs[inputs.index(this) + 1];
                    if (inp !== null) {
						$(inp).focus();
						var inp_type = $(this).attr('type');
						if(!!inp_type && (inp_type == 'text' || inp_type == 'number')){
							inp.setSelectionRange(0, inp.value.length);
						}
                    }
                    e.preventDefault();
                    return false;
                }
            });

            var preset_popover = function(){
               $('#save_preset').popover({
                   container: '#module_preset',
                   content: function(){
                       return $('#_save_preset').html();
                   },
                   html: true,
                   placement: 'bottom'
               }).on('show.bs.popover', function () {
                   $(this).addClass('active');
               }).on('hide.bs.popover', function () {
                   $(this).removeClass('active');
               });
            };
            preset_popover();
            $('#gallerySettingsForm').on('click', '.ajax-submit', function(e){
                e.preventDefault();
                $('body').addClass('gmedia-busy');
                var form = $('#gallerySettingsForm');
                var post_data = form.serializeArray();
                post_data.push({name: $(this).attr('name'), value: 1});
                var post_url = form.attr('action');
                $.post(post_url, $.param(post_data), function(data, status, xhr){
                    $('body').removeClass('gmedia-busy');
                    data = $(data).find('#gmedia-container');
                    $('#gm-message').append($('#gm-message', data).html());
                    $('#save_buttons').html($('#save_buttons', data).html());
                    $('#module_preset').html($('#module_preset', data).html());
                    preset_popover();
                });
            });
            $('body').on('click', function (e) {
                if ($(e.target).data('toggle') !== 'popover'
                    && $(e.target).parents('.popover.in').length === 0) {
                    $('[data-toggle="popover"]').popover('hide');
                }
            });

            $('#module_preset').on('click', '.delpreset span', function(){
                $('body').addClass('gmedia-busy');
                var preset_item_li = $(this).closest('li');
                var preset_id = $(this).data('id');
                var post_data = {
                    action: 'gmedia_module_preset_delete', preset_id: preset_id, _wpnonce: $('#_wpnonce').val()
                };
                $.post(ajaxurl, post_data, function(data, status, xhr){
                    if(data.error){
                        $('#gm-message').append(data.error);
                    } else {
                        preset_item_li.remove();
                    }
                    $('body').removeClass('gmedia-busy');
                });
            });

			if($(".panel-fixed-header").length){
				setPanelHeadersWidth();
				setTimeout(function() {
					setPanelHeadersWidth();
				}, 800);
				$(window).resize(function(){
					setPanelHeadersWidth();
				});
				$('#collapse-menu').click(function(){
					setTimeout(function() {
						setPanelHeadersWidth();
					}, 10);
				});

				$(window).scroll(function(){
					UpdatePanelHeaders();
					/*clearTimeout($.data(this, 'scrollTimer'));
					 $.data(this, 'scrollTimer', setTimeout(function() {
					 UpdatePanelHeaders();
					 console.log("Haven't scrolled in 250ms!");
					 }, 250));*/
				}).trigger("scroll");
			}

        }
	};

	GmediaSelect.init();
	GmediaFunction.init();

});

function convertInputsToJSON(form){
	var array = jQuery(form).serializeArray();
	var json = {};

	jQuery.each(array, function() {
		json[this.name] = this.value || '';
	});

	return json;
}

function validateEmail(email) {
	var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(email);
}

function getStorage(keyPprefix){
	// use document.cookie:
	return {
		set: function(id, data){
			document.cookie = keyPprefix + id + '=' + encodeURIComponent(data);
		},
		get: function(id, data){
			var cookies = document.cookie, parsed = {};
			cookies.replace(/([^=]+)=([^;]*);?\s*/g, function(whole, key, value){
				parsed[key] = decodeURIComponent(value);
			});
			return parsed[keyPprefix + id];
		}
	};
}

/*
 function gmHashCode(str){
 var l = str.length,
 hash = 5381 * l * (str.charCodeAt(0) + l);
 for(var i = 0; i < str.length; i++){
 hash += Math.floor((str.charCodeAt(i) + i + 0.33) / (str.charCodeAt(l - i - 1) + l) + (str.charCodeAt(i) + l) * (str.charCodeAt(l - i - 1) + i + 0.33));
 }
 return hash;
 }
 function gmCreateKey(site, lic, uuid){
 if(!lic){
 lic = '0:lk';
 }
 if(!uuid){
 uuid = 'xyxx-xxyx-xxxy';
 }
 var d = gmHashCode((site + ':' + lic).toLowerCase());
 var p = d;
 uuid = uuid.replace(/[xy]/g, function(c){
 var r = d % 16 | 0, v = c == 'x'? r : (r & 0x7 | 0x8);
 d = Math.floor(d * 15 / 16);
 return v.toString(16);
 });
 var key = p + ': ' + lic + '-' + uuid;
 return key.toLowerCase();
 }
 */

function UpdatePanelHeaders() {
	jQuery(".panel-fixed-header").each(function() {
		var el         = jQuery(this),
			headerRow = jQuery(".panel-heading", this),
			offset         = el.offset(),
			scrollTop      = jQuery(window).scrollTop(),
			floatingHeader = "panel-floatingHeader",
			absoluteHeader = "panel-absoluteHeader",
			pad_top = parseInt(jQuery('html').css('padding-top'));

		if ((scrollTop > offset.top - pad_top) && (scrollTop < offset.top - pad_top + (el.height() - headerRow.outerHeight(false)) + 4)) {
			el.addClass(floatingHeader).removeClass(absoluteHeader);
		} else if (scrollTop  > (offset.top - pad_top + (el.height() - headerRow.outerHeight(false)))) {
			el.addClass(absoluteHeader).removeClass(floatingHeader);
		} else {
			el.removeClass(absoluteHeader + ' ' + floatingHeader)
		}
	});
}

function setPanelHeadersWidth() {
	jQuery(".panel-fixed-header").each(function() {
		var headerRow = jQuery(".panel-heading", this);
		headerRow.css("width", jQuery(this).innerWidth());
		jQuery(".panel-heading-fake", this).height(headerRow.outerHeight());
	});
}
