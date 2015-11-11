/*
 * Title                   : Minima Gallery Module
 * Version                 : 2.8
 * Copyright               : 2013-2015 CodEasily.com
 * Website                 : http://www.codeasily.com
 */
if(typeof jQuery.fn.gmMinima == 'undefined'){
	function gmMinima(ID){
		return window['GmediaGallery_' + ID];
	}

	(function($, window, document, undefined){
		$.fn.gmMinima = function(method){
			var Container = this,
				ID = '',
				flashVerion = '11',
				Content,
				ratio = 0,
				max_img_h = 0,

				opt = {
					'height': '500',
					'property0': 'opaque', /* wmode */
					'customCSS': '',
					'moduleUrl': '',
					'pluginUrl': '',
					'photoswipe_css': '/assets/photoswipe/photoswipe.css',
					'photoswipe_js': '/assets/photoswipe/photoswipe.jquery.min.js'
				},
				int = {
					'maxwidth': 0,
					'maxheight': 0,
					'slideshowDelay': 10, /* slideshowDelay */
					'thumbnailsWidth': 75, /* thumbnailsWidth */
					'thumbnailsHeight': 75, /* thumbnailsHeight */
					'descriptionBGAlpha': 75, /* imageDescrBgAlpha */
					'galleryTitleFontSize': 15, /* galleryTitleFontSize */
					'titleFontSize': 12, /* imageTitleFontSize */
					'descriptionFontSize': 11, /* imageDescrFontSize */
					'postID': 0
				},
				bool = {
					'lockheight': false,
					'autoSlideshow': true, /* autoSlideshow */
					'counterStatus': true, /* counterStatus */
					'hitcounter': false,
					'swfMouseWheel': false,
					'loveLink': false
				},
				hex = {
					'property1': 'ffffff', /* bgColor */
					'barBgColor': '282828', /* barsBgColor */
					'labelColor': '75c30f', /* catButtonColor */
					'labelColorOver': 'ffffff', /* catButtonColorHover */
					'backgroundColorButton': '000000', /* backgroundColorButton */
					'descriptionBGColor': '000000', /* imageDescrBgColor */
					'imageTitleColor': '75c30f', /* imageTitleColor */
					'imageDescriptionColor': 'ffffff', /* imageDescrColor */
					'linkColor': '75c30f', /* linkColor */
					'backButtonColorText': 'ffffff', /* backButtonTextColor */
					'backButtonColorBg': '000000' /* backButtonBgColor */
				},

				methods = {
					init: function(arguments){// Init Plugin.
						if(arguments[1]){
							opt = $.extend(opt, int, hex, bool, arguments[1]);
						}
						$.each(opt, function(key, val){
							if(key in hex){
								opt[key] = '0x' + val;
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
								wmode: opt.property0,
								allowfullscreen: 'true',
								allowScriptAccess: 'always',
								saling: 'lt',
								scale: 'noScale',
								menu: 'false',
								bgcolor: '#' + opt.property1.slice(2)
							},
							flashvars = {
								id: ID,
								json: 'gmMinima'
							},
							attributes = {
								styleclass: 'gmMinima_Flash',
								id: 'gmMinima_ID' + ID + '_Flash'
							};
						if(opt.postID){
							flashvars.postID = opt.postID;
							flashvars.postTitle = opt.postTitle;
						}
						prototypes.swfobject_switchOffAutoHideShow();
						swfobject.embedSWF(opt.moduleUrl + '/gallery.swf', 'gmMinima_ID' + ID + '_Container', '100%', '100%', flashVerion, opt.pluginUrl + '/inc/expressInstall.swf', flashvars, parameters, attributes, methods.callbackFn);

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
							$('#gmMinima_ID' + ID).on("mouseenter", e.ref,function(){
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
							var bars_height = opt.thumbnailsHeight + 80;
							var size = prototypes.responsive_size(bars_height);
							$('#gmMinima_ID' + ID + '_Flash', Container).css({'width':size[0], 'height': size[1]});
							$(window).resize(function(){
								size = prototypes.responsive_size(bars_height);
								$('#gmMinima_ID' + ID + '_Flash', Container).css({'width':size[0], 'height': size[1]});
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
					noFlash: function() {
                        var alt_template = $('#flashmodule_alternative_' + ID, Container);
                        if (alt_template.length){
                            $('#gmMinima_ID' + ID + '_Container', Container).html(alt_template.html()).find('img[data-src]').each(function(){
                                $(this).attr('src', $(this).data('src'));
                            });
                        }
						$('.gmcatmeta', Container).hide();
						$('.gmcatlinks a', Container).filter(':first').addClass('active').end().on('click', function(e){
							e.preventDefault();
							var pane = $(this).attr('href');
							$(this).addClass('active').siblings().removeClass('active');
							$(pane).show().siblings('.gmcategory').hide();
						});
						$('.gmcategory', Container).not(':first').hide();
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
								allowUserZoom:false,
								autoStartSlideshow:opt.autoSlideshow,
								allowRotationOnUserZoom:false,
								captionAndToolbarAutoHideDelay:0,
								captionAndToolbarFlipPosition:false,
								captionAndToolbarHide:false,
								captionAndToolbarOpacity:0.8,
								captionAndToolbarShowEmptyCaptions:true,
								enableDrag:true,
								enableKeyboard:true,
								enableMouseWheel:true,
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
								//target: $('body'),
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
								gmBodyScrollTop = $('body').scrollTop();
								gmBodyScrollLeft = $('body').scrollLeft();
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
								$(window).scrollLeft(0);
								$('html').addClass('ps-noscroll');
							});
							instance.addEventHandler('PhotoSwipeOnShow', function(e){
								$('body').append($('<div class="ps-slidetime"></div>').css({'z-index': instance.settings.zIndex, 'display': 'block'}));
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
								$('html').removeClass('ps-noscroll');
								$('.ps-slidetime').remove();
								$(window).scrollTop(gmBodyScrollTop).scrollLeft(gmBodyScrollLeft);
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
