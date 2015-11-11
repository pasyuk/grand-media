/*
 * Title                   : Afflux Gallery Module
 * Version                 : 3.7
 * Copyright               : 2013-2015 CodEasily.com
 * Website                 : http://www.codeasily.com
 */
if(typeof jQuery.fn.gmAfflux == 'undefined'){
	function gmAfflux(ID){
		return window['GmediaGallery_' + ID];
	}

	(function($, window, document, undefined){
		$.fn.gmAfflux = function(method){
			var Container = this,
				ID = '',
				flashVerion = '11',
				Content,
				ratio = 0,
				max_img_h = 0,

				opt = {
					'height': '500',
					'wmode': 'opaque',
					'imageZoom': 'FILL',
					'customCSS': '',
					'moduleUrl': '',
					'pluginUrl': '',
					'photoswipe_css': '/assets/photoswipe/photoswipe.css',
					'photoswipe_js': '/assets/photoswipe/photoswipe.jquery.min.js'
				},
				int = {
					'maxwidth': 0,
					'maxheight': 0,
					'slideshowDelay': 10,
					'thumbHeight': 100,
					'imageTitleFontSize': 14,
					'imageDescrFontSize': 12,
					'imageDescrBgAlpha': 85,
					'postID': 0
				},
				bool = {
					'lockheight': false,
					'autoSlideshow': true,
					'descrVisOnMouseover': true,
					'loveLink': false,
					'hitcounter': false
				},
				hex = {
					'bgColor': 'ffffff',
					'imagesBgColor': '000000',
					'barsBgColor': '000000',
					'catButtonColor': '75c30f',
					'catButtonColorHover': 'ffffff',
					'scrollBarTrackColor': '75c30f',
					'scrollBarButtonColor': 'f1f1f1',
					'thumbBgColor': 'ffffff',
					'thumbLoaderColor': '75c30f',
					'imageTitleColor': '75c30f',
					'imageDescrColor': 'ffffff',
					'imageDescrBgColor': '000000',
					'backButtonTextColor': 'ffffff',
					'backButtonBgColor': '000000'
				},

				methods = {
					init: function(arguments){// Init Plugin.
						if(arguments[1]){
							opt = $.extend(opt, int, hex, bool, arguments[1]);
						}
						$.each(opt, function(key, val){
							if(key in hex){
								opt[key] = '0x'+val;
							} else if(key in bool){
								opt[key] = (!(!val || val == '0' || val == 'false'));
							} else if(key in int){
								opt[key] = parseInt(val);
							}
						});
						ID = opt.ID;
						Content = arguments[0];
						methods.initGallery();
					},
					initGallery: function(){// Init the Gallery
						var parameters = {
								wmode: opt.wmode,
								allowfullscreen: 'true',
								allowScriptAccess: 'always',
								saling: 'lt',
								scale: 'noScale',
								menu: 'false',
								bgcolor: '#' + opt.bgColor.slice(2)
							},
							flashvars = {
								id: ID,
								json: 'gmAfflux'
							},
							attributes = {
								styleclass: 'gmAfflux_Flash',
								id: 'gmAfflux_ID' + ID + '_Flash'
							};
						if(opt.postID){
							flashvars.postID = opt.postID;
							flashvars.postTitle = opt.postTitle;
						}
						prototypes.swfobject_switchOffAutoHideShow();
						swfobject.embedSWF(opt.moduleUrl + '/gallery.swf', 'gmAfflux_ID' + ID + '_Container', '100%', '100%', flashVerion, opt.pluginUrl + '/inc/expressInstall.swf', flashvars, parameters, attributes, methods.callbackFn);

					},
					callbackFn: function(e){// e = {(bool) success, (string) id, (reference to the active HTML object element) ref}
						$.each(Content, function(index){
							$.each(this.data, function(index){
								if(ratio){
									ratio = Math.min(ratio, (this.websize[0] / this.websize[1]));
								} else{
									ratio = (this.websize[0] / this.websize[1]);
								}
								max_img_h = Math.max(max_img_h, this.websize[1]);
							});
						});
						if(e.success){
							var swfHover = e.ref;
							$('#gmAfflux_ID' + ID).on("mouseenter", e.ref,function(){
								if($.isFunction(swfHover['swfHover' + ID])){
									swfHover['swfHover' + ID]('true');
								}
							}).on("mouseleave", e.ref, function(){
								if($.isFunction(swfHover['swfHover' + ID])){
									swfHover['swfHover' + ID]('false');
								}
							});
							/*.on('mousewheel scroll DOMMouseScroll', e.ref, function (event) {
							 return false;
							 });*/

							// set responsive gallery height
							var hiddenBustedItems = prototypes.doHideBuster($(Container));
							var bars_height = opt.thumbHeight + ((Content.length > 1)? 40 : 10);
							var size = prototypes.responsive_size(bars_height);
							$('#gmAfflux_ID' + ID + '_Flash', Container).css({'width':size[0], 'height': size[1]});
							$(window).resize(function(){
								size = prototypes.responsive_size(bars_height);
								$('#gmAfflux_ID' + ID + '_Flash', Container).css({'width':size[0], 'height': size[1]});
							});
							$(window).trigger('resize');
							prototypes.undoHideBuster(hiddenBustedItems);
						} else{
							methods.noFlash();
						}
					},
					flashContent: function(){
						return {'settings': opt, 'content': Content};
					},
					noFlash: function(){
                        var alt_template = $('#flashmodule_alternative_' + ID, Container);
                        if (alt_template.length){
                            $('#gmAfflux_ID' + ID + '_Container', Container).html(alt_template.html()).find('img[data-src]').each(function(){
                                $(this).attr('src', $(this).data('src'));
                            });
                        }
						$('.gmcatmeta', Container).hide();
						$('.gmcatlinks a', Container).filter(':first').addClass('active').end().on('click', function(e){
							e.preventDefault();
							$('.gmcategories_holder', Container).scrollTop(0);
							var pane = $(this).attr('href');
							$(this).addClass('active').siblings().removeClass('active');
							$(pane).show().siblings('.gmcategory').hide();
						});
						$('.gmcategory', Container).not(':first').hide();

						var hiddenBustedItems = prototypes.doHideBuster($(Container));
						var bars_height = $('.gmcatlinks').outerHeight(true) + 2;
						var size = prototypes.responsive_size(0);
						$('.flashmodule_alternative', Container).css({'width':size[0]});
						$('.gmcategories_holder', Container).css({'height': size[1] - bars_height});
						$(window).resize(function(){
							bars_height = $('.gmcatlinks').outerHeight(true) + 2;
							size = prototypes.responsive_size(0);
							$('.flashmodule_alternative', Container).css({'width':size[0]});
							$('.gmcategories_holder', Container).css({'height': size[1] - bars_height});
						});
						$(window).trigger('resize');
						prototypes.undoHideBuster(hiddenBustedItems);

						if($.isFunction($.fn.photoSwipe)){
							methods.alternative();
						} else{
							$('<link/>', {
								rel: 'stylesheet',
								type: 'text/css',
								href: opt.pluginUrl + opt.photoswipe_css
							}).appendTo('head');
							$.getScript(opt.pluginUrl + opt.photoswipe_js)
								.done(function(script, textStatus){
									methods.alternative();
								});
						}
					},
					alternative: function(){
						setTimeout(function(){
							$('.flashmodule_alternative', Container).removeClass('delay');
						}, 3000);
						var gmBodyScrollTop, gmBodyScrollLeft,
							psShowCaption, psImgTitle, psImgCaption = '', curel,
							options = {
								allowUserZoom:true,
								autoStartSlideshow:opt.autoSlideshow,
								allowRotationOnUserZoom:true,
								captionAndToolbarAutoHideDelay:0,
								captionAndToolbarFlipPosition:false,
								captionAndToolbarHide:false,
								captionAndToolbarOpacity:0.8,
								captionAndToolbarShowEmptyCaptions:false,
								enableDrag:true,
								enableKeyboard:true,
								enableMouseWheel:false,
								enableUIWebViewRepositionTimeout:true,
								imageScaleMethod:'fit', //Either "fit", "fitNoUpscale" or "zoom"
								preventHide:false,
								preventSlideshow:false,
								preventDefaultTouchEvents:true,
								slideshowDelay:opt.slideshowDelay * 1000,
								slideSpeed:250,
								swipeThreshold:50,
								swipeTimeThreshold:250,
								loop:true,
								target: $('.flashmodule_alternative', Container),
								zIndex:10000,
								getToolbar: function(){
									// NB. Calling PhotoSwipe.Toolbar.getToolbar() wil return the default toolbar HTML
									var psToolbar = window.Code.PhotoSwipe.Toolbar.getToolbar();
									psToolbar = psToolbar + '<div class="ps-toolbar-descr"><div class="ps-toolbar-content"></div></div>';
									return psToolbar;
								},
								getImageCaption: function(el){
									var title = $(el).closest('.gmcatimage').find('.gmcatimage_title').html(),
										descr = $(el).closest('.gmcatimage').find('.gmcatimage_description'),
										caption = '';
									psImgTitle = ''; psImgCaption = '';
									if(title){
										psImgTitle = $('<strong></strong>').addClass('ps-title').append(title).get(0).outerHTML;
									}
									if(descr.length && descr.html()){
										psImgCaption = $('<div></div>').addClass('ps-long-description').css('display','none').append(descr.html()).get(0).outerHTML;
									}
									if(psImgTitle || psImgCaption){
										caption = $(psImgTitle + psImgCaption);
									}
									return caption;
								},
								getImageMetaData: function(el){
									return { longDescription: psImgCaption }
								}
							};
						$('.gmcategory', Container).each(function(){
							var instance;
							instance = $('a.photoswipe',this).on('click',function(){
								gmBodyScrollTop = $('.flashmodule_alternative', Container).scrollTop();
								gmBodyScrollLeft = $('.flashmodule_alternative', Container).scrollLeft();
							}).photoSwipe(options);

							instance.addEventHandler('PhotoSwipeOnSlideshowStart', function(){
								$('.ps-slidetime').animate({width:'100%'}, instance.settings.slideshowDelay, function(){
									$(this).width(0);
								});
							});
							instance.addEventHandler('PhotoSwipeOnSlideshowStop', function(){
								$('.ps-slidetime').stop().width(0);
							});
							instance.addEventHandler('PhotoSwipeOnBeforeShow', function(e){
								instance.settings.target.scrollLeft(0);
							});
							instance.addEventHandler('PhotoSwipeOnShow', function(e){
								instance.settings.target.append($('<div class="ps-slidetime"></div>').css({'z-index': instance.settings.zIndex, 'display': 'block'}));
								if(instance.settings.autoStartSlideshow){
									$('.ps-slidetime').animate({width:'100%'}, instance.settings.slideshowDelay, function(){
										$(this).width(0);
									});
								}
								psShowCaption = $('.ps-toolbar-descr').get(0);
							});
							instance.addEventHandler('PhotoSwipeOnBeforeHide', function(e){
								psShowCaption = null;
							});
							instance.addEventHandler('PhotoSwipeOnHide', function(e){
								$('.ps-slidetime').remove();
								instance.settings.target.removeClass('ps-active').scrollTop(gmBodyScrollTop).scrollLeft(gmBodyScrollLeft);
							});
							instance.addEventHandler('PhotoSwipeOnDisplayImage', function(e){
								curel = instance.getCurrentImage();
								/*if(!$(curel.refObj).hasClass('viewed')){
								 $(curel.refObj).addClass('viewed');
								 var curid = curel.refObj.dataset.id;
								 $.post(opt.pluginUrl+'/rate.php', { hit: curid }, function(r){
								 console.log(r);
								 if(r.views){
								 $(curel.refObj).find('.gmcatimage_counters i').text(r.views);
								 }
								 });
								 }*/
								if(curel.metaData.longDescription){
									$('.ps-toolbar-descr').removeClass('disabled active').addClass('enabled');
								} else {
									$('.ps-toolbar-descr').removeClass('enabled active').addClass('disabled');
								}
							});
							instance.addEventHandler('PhotoSwipeOnCaptionAndToolbarShow', function(e){
								curel = instance.getCurrentImage();
								if(curel.metaData.longDescription){
									$('.ps-toolbar-descr').removeClass('disabled active').addClass('enabled');
								} else {
									$('.ps-toolbar-descr').removeClass('enabled active').addClass('disabled');
								}
							});
							// onToolbarTap - listen out for when the toolbar is tapped
							instance.addEventHandler('PhotoSwipeOnToolbarTap', function(e){
								if (e.toolbarAction === window.Code.PhotoSwipe.Toolbar.ToolbarAction.none){
									if (e.tapTarget === psShowCaption || window.Code.Util.DOM.isChildOf(e.tapTarget, psShowCaption)){
										if($(psShowCaption).hasClass('enabled')){
											$('.ps-toolbar-descr').toggleClass('active');
											$('.ps-long-description').slideToggle(400);
										}
									}
								}
							});
						});
					}
				},

				prototypes = {
					responsive_size: function(corr){
						var w, h;
						w = Container.width();
						if(0 !== opt.maxwidth){
							w = Math.min(opt.maxwidth, w);
						}
						if(opt.lockheight){
							h = ('%' == opt.height.slice(-1))? parseInt(opt.height)+'%' : parseInt(opt.height);
							return [w, h];
						}
						h = Math.floor(w / ratio + corr);
						if(max_img_h){
							h = Math.min($(window).height(), h, max_img_h);
						} else{
							h = Math.min($(window).height(), h);
						}
						var ch = Container.height();
						var chp = Container.parent().height()/ch*100;
						if(100 == chp){
							h = Math.min(h, ch);
						}
						if((0 !== opt.maxheight) && (opt.maxheight < h)){
							h = opt.maxheight;
							//w = Math.floor((h - bars_height) * ratio);
						}
						return [w, h];
					},
					swfobject_switchOffAutoHideShow: function(){// SWFObject temporarily hides your SWF or alternative content until the library has decided which content to display
						if($.isFunction(swfobject.switchOffAutoHideShow)){
							swfobject.switchOffAutoHideShow();
						}
					},
					doHideBuster: function(item){// Make all parents & current item visible
						var parent = item.parent(),
							items = [];

						if(item.prop('tagName') !== undefined && item.prop('tagName').toLowerCase() != 'body'){
							items = prototypes.doHideBuster(parent);
						}

						if(item.css('display') == 'none'){
							item.css('display', 'block');
							items.push(item);
						}

						return items;
					},
					undoHideBuster: function(items){// Hide items in the array
						var i;

						for(i = 0; i < items.length; i++){
							items[i].css('display', 'none');
						}
					}
				};
			methods.init.apply(this, arguments);
			return methods.flashContent();
		};

	})(jQuery, window, document);
}
