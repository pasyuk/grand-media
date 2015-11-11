// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;(function ( $, window, document, undefined ) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variable rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "photomanialite",
		pluginVersion = "1.1";

	// The actual plugin constructor
	function Plugin ( element, options, content ) {
		this.el = element;
		this.$el = $(element);
		this.content = content;
		this._options = options;
		this._version = pluginVersion;
		this._name = pluginName;

		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(Plugin.prototype, {

		_defaults: {
			description_title : 'Description',
			link_color : '0099e5',
			link_color_hover : '02adea',
			ajaxurl : '',
			key : ''
		},
		_defaults_int: {
			base_gallery_width: 800,
			base_gallery_height: 500,
			gallery_min_height: 230,
			thumbs_per_view: 5,
			thumbs_space_between: 2,
			initial_slide: 0,
			slideshow_delay: 7000
		},
		_defaults_bool: {
			slideshow_autoplay: false,
			gallery_focus: false,
			gallery_maximized: false,
			gallery_focus_maximized: false,
			keyboard_help: true,
			show_download_button: true,
			show_link_button: true,
			show_description: true,
			show_author_avatar: true,
			show_like_button: true
		},

		init: function () {
			this._options = this.sanitizeOptions(this._options);
			this.opts = $.extend(true, {}, this._defaults, this._defaults_int, this._defaults_bool, this._options);
			this.is_iframe = this.$_GET('iframe');
			this.prepareDom();

			var self = this;
			var gallery_params = {
				nextButton: $('.gmpm_photo_arrow_next', self.el),
				prevButton: $('.gmpm_photo_arrow_previous', self.el),
				spaceBetween: 10,
				keyboardControl: false,
				preloadImages: false,
				lazyLoading: true,
				lazyLoadingInPrevNext: true,

				initialSlide: self.opts.initial_slide,

				onInit: function(swiper){
					setTimeout(function(){ self.slideRate(); },900);

					var ai = swiper.activeIndex,
						item = self.content.data[ai];
					if(self.storage[item.file] && 'liked' == self.storage[item.file]){
						$('.gmpm_focus_like_fave .gmpm_like', self.el).addClass('gmpm_liked');
					} else {
						$('.gmpm_focus_like_fave .gmpm_like', self.el).removeClass('gmpm_liked');
					}
				}
			};
			if(self.opts.slideshow_autoplay){
				gallery_params.autoplay = self.opts.slideshow_delay;
			}
			var thumbs_params = {
				slideActiveClass: 'firstofset',
				slideNextClass: 'secondofset',
				slidePrevClass: 'zeroofset',
				centeredSlides: false,
				watchSlidesProgress: true,
				watchSlidesVisibility: true,
				preloadImages: false,
				lazyLoading: true,
				lazyLoadingInPrevNext: true,

				spaceBetween: self.opts.thumbs_space_between,
				slidesPerView: self.opts.thumbs_per_view,
				slidesPerGroup: self.opts.thumbs_per_view,
				initialSlide: self.opts.initial_slide,

				onInit: function(swiper){
					if(!Math.floor(self.opts.initial_slide / 5)){
						swiper.lazy.load();
					}
				}
			};
			this.swiper_gallery = new Swiper($('.swiper-big-images', this.el), gallery_params);
			this.swiper_thumbs = new Swiper($('.swiper-small-images', this.el), thumbs_params);

			this.prepareGallery();
			this.eventsHandler();

			var fullScreenApi = {
					ok: false,
					is: function () {
						return false;
					},
					request: function () {
					},
					cancel: function () {
					},
					event: '',
					prefix: ''
				},
				browserPrefixes = 'webkit moz o ms khtml'.split(' ');

			// check for native support
			if (typeof document.cancelFullScreen != 'undefined') {
				fullScreenApi.ok = true;
			} else {
				// check for fullscreen support by vendor prefix
				for (var i = 0, il = browserPrefixes.length; i < il; i++) {
					fullScreenApi.prefix = browserPrefixes[i];
					if (typeof document[fullScreenApi.prefix + 'CancelFullScreen' ] != 'undefined') {
						fullScreenApi.ok = true;
						break;
					}
				}
			}

			// update methods to do something useful
			if (fullScreenApi.ok) {
				fullScreenApi.event = fullScreenApi.prefix + 'fullscreenchange';
				fullScreenApi.is = function () {
					switch (this.prefix) {
						case '':
							return document.fullScreen;
						case 'webkit':
							return document.webkitIsFullScreen;
						default:
							return document[this.prefix + 'FullScreen'];
					}
				};
				fullScreenApi.request = function (el) {
					return (this.prefix === '') ? el.requestFullScreen() : el[this.prefix + 'RequestFullScreen']();
				};
				fullScreenApi.cancel = function (el) {
					return (this.prefix === '') ? document.cancelFullScreen() : document[this.prefix + 'CancelFullScreen']();
				};
			}

			this.fsApi = fullScreenApi;

			this.galleryAutoHeight();

		},
		sanitizeOptions: function (options) {
			var self = this;
			return 	$.each(options, function(key, val){
				if(key in self._defaults_bool){
					options[key] = (!(!val || val == '0' || val == 'false'));
				} else if(key in self._defaults_int){
					options[key] = parseInt(val);
				}
			});

		},
		prepareDom: function () {
			$('.swiper-wrapper img', this.el).removeAttr('alt');

			if(window.sessionStorage){
				var elid = this.$el.attr('id');
				this.storage = sessionStorage.getItem( elid );
				if(!this.storage){
					this.storage = {};
				} else {
					this.storage = JSON.parse(this.storage);
				}
			}
		},
		prepareGallery: function () {
			var is = this.opts.initial_slide;
			$(this.swiper_thumbs.slides[is]).addClass('swiper-slide-active');

			this.galleryAutoHeight();

			this.button_state_gallery();
			this.button_state_thumbs();

			var sw_thumbs = $('.swiper-small-images', this.el),
				slide_count = $('.swiper-slide', sw_thumbs).length;
			if(slide_count < this.opts.thumbs_per_view){
				var placeholders = [],
					p_count = this.opts.thumbs_per_view - slide_count,
					sl_mr = this.opts.thumbs_space_between,
					sl_w = (sw_thumbs.width() + sl_mr)/this.opts.thumbs_per_view - sl_mr;
				for(var i = 0; i < p_count; i++){
					if(i == (p_count -1)){
						sl_mr = 0;
					}
					placeholders.push($('<div class="swiper-slide-placeholder"></div>').css({width: sl_w, marginRight: sl_mr}));
				}

				$('.swiper-wrapper', sw_thumbs).append(placeholders);
			}
			this.galleryWidth();

		},
		galleryWidth: function () {
			var el_w = this.$el.width(),
				photo_show = $('.gmpmp_photo_show', this.$el);
			if(el_w <= 800){
				photo_show.addClass('gmpm_w800');
				if(el_w <= 515){
					photo_show.addClass('gmpm_w480');
				} else{
					photo_show.removeClass('gmpm_w480');
				}
			} else {
				photo_show.removeClass('gmpm_w800');
			}
		},
		galleryAutoHeight: function () {
			var self = this,
				photo_wrap = $('.gmpm_photo_wrap', self.el),
				old_height = photo_wrap.height(),
				el_w = self.$el.width(),
				el_h = self.$el.height(),
				window_height = el_h - $('.gmpm_photo_header').outerHeight(),
				item_w = el_w - parseInt($(photo_wrap).css('padding-left')) - parseInt($(photo_wrap).css('padding-right')),
				photo_show_ratio = self.opts.base_gallery_width/self.opts.base_gallery_height,
				base_photo_show_height = Math.round(item_w/photo_show_ratio),
				photo_show_height = base_photo_show_height;

			if(self.opts.gallery_maximized){
				var ai = self.swiper_gallery.activeIndex,
					item = self.content.data[ai];
				if(('image' === item.type) && (item_w > item.meta.web.width)){
					item_w = item.meta.web.width;
				}
				var item_h = Math.round(item_w / item.ratio);
				photo_show_height = item_h + parseInt($(photo_wrap).css('padding-top')) + parseInt($(photo_wrap).css('padding-bottom'));

				if(('image' !== item.type)){
					var img_h_natural = $('img', self.swiper_gallery.slides[ai])[0].naturalHeight;
					if(photo_show_height > img_h_natural){
						photo_show_height = img_h_natural;
					}
				}
				/*if(photo_show_height < base_photo_show_height){
					photo_show_height = base_photo_show_height;
				}*/
			}
			if($('body').hasClass('gmedia-template')){
				if((!self.opts.gallery_maximized || (photo_show_height < window_height) &&  !self.is_iframe)){
					photo_show_height = window_height;
				}
			}
			if(photo_show_height < self.opts.gallery_min_height){
				photo_show_height = self.opts.gallery_min_height;
			}

			if(old_height != photo_show_height){
				photo_wrap.css({'height': photo_show_height});

				photo_wrap.one("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(){
					self.swiper_gallery.onResize();
					self.swiper_thumbs.onResize();
					setTimeout(function(){
						self.galleryWidth();
					}, 1);
				});
			}
		},
		button_state_gallery: function(){
			var photo_show = $('.gmpm_photo_show', this.el);
			if(this.swiper_gallery.isBeginning){
				$('.gmpm_photo_wrap', photo_show).removeClass('has_prev_photo');
			} else{
				$('.gmpm_photo_wrap', photo_show).addClass('has_prev_photo');
			}
			if(this.swiper_gallery.isEnd){
				$('.gmpm_photo_wrap', photo_show).removeClass('has_next_photo');
			} else{
				$('.gmpm_photo_wrap', photo_show).addClass('has_next_photo');
			}
		},
		button_state_thumbs: function(){
			var photo_show = $('.gmpm_photo_show', this.el);
			if(this.swiper_thumbs.isBeginning){
				$('.gmpm_carousel', photo_show).removeClass('gmpm_has_previous');
			} else{
				$('.gmpm_carousel', photo_show).addClass('gmpm_has_previous');
			}
			if(this.swiper_thumbs.isEnd){
				$('.gmpm_carousel', photo_show).removeClass('gmpm_has_next');
			} else{
				$('.gmpm_carousel', photo_show).addClass('gmpm_has_next');
			}
		},
		eventsHandler: function () {
			var self = this,
				photo_show = $('.gmpm_photo_show', self.el);

			var timeout;
			$(window).on('resize', function(){
				self.galleryWidth();
				clearTimeout(timeout);
				timeout = setTimeout(function(){ self.galleryAutoHeight(); },600);
			});

			$('img.gmpm_the_photo', photo_show).on('click', function(){
				self.focus();
			});

			$('.gmpm_close', photo_show).on('click', function(){
				self.focus(false);
			});

			$('.gmpm_next_button', photo_show).on('click', function(){
				self.swiper_thumbs.slideNext();
			});
			$('.gmpm_previous_button', photo_show).on('click', function(){
				self.swiper_thumbs.slidePrev();
			});

			self.swiper_thumbs.on('click', function(){
				$(self.swiper_thumbs.clickedSlide).addClass('swiper-slide-active').siblings('.swiper-slide-active').removeClass('swiper-slide-active');
				self.swiper_gallery.slideTo(self.swiper_thumbs.clickedIndex);
			});

			self.swiper_thumbs.on('onSlideChangeStart', function(swiper){
				self.button_state_thumbs();
			});

			self.swiper_gallery.on('onSlideChangeEnd', function(swiper){
				if(self.opts.gallery_maximized){
					self.galleryAutoHeight();
				}

				timeout = setTimeout(function(){ self.slideRate(); },900);
			});

			self.swiper_gallery.on('onSlideChangeStart', function(swiper){
				clearTimeout(timeout);

				self.button_state_gallery();

				var ai = swiper.activeIndex,
					item = self.content.data[ai];
				if(self.opts.show_author_avatar){
					$('.gmpm_user_avatar_link', photo_show).attr('href', item.author.posts_link).html($('<img />').attr('src', item.author.avatar));
				}
				$('.gmpm_author_link', photo_show).attr('href', item.author.posts_link).text(item.author.name);
				$('h1.gmpm_title', photo_show).text(item.title);
				if(self.opts.show_download_button){
					$('.gmpm_download_button', photo_show).attr({'href': item.download, 'download': item.file});
				}
				if(self.opts.show_link_button){
					if(item.link){
						$('.gmpm_link_button', photo_show).attr('href', item.link).removeClass('inactive');
					} else {
						$('.gmpm_link_button', photo_show).removeAttr('href').addClass('inactive');
					}
				}
				if(self.opts.show_description){
					$('.gmpm_slide_description', photo_show).html(item.description);
					if(item.description){
						$('.gmpm_photo_details', photo_show).removeClass('no-slide-description');
					} else {
						$('.gmpm_photo_details', photo_show).addClass('no-slide-description');
					}
				}
				if(self.storage[item.file] && 'liked' == self.storage[item.file]){
					$('.gmpm_focus_like_fave .gmpm_like', photo_show).addClass('gmpm_liked');
				} else {
					$('.gmpm_focus_like_fave .gmpm_like', photo_show).removeClass('gmpm_liked');
				}

				$(self.swiper_thumbs.slides[ai]).addClass('swiper-slide-active').siblings().removeClass('swiper-slide-active');
				self.swiper_thumbs.slideTo(ai);
			});

			$('.gmpm_focus_like_fave .gmpm_like', photo_show).on('click', function(){
				self.slideRate(true);
				$(this).addClass('gmpm_liked');
			});

			$('.gmpm_focus_keyboard_dismiss', photo_show).on('click', function(){
				$('.gmpm_focus_footer', photo_show).fadeOut(400);
				photo_show.addClass('gmpm_diskeys');
				setTimeout(function(){ self.swiper_gallery.update(); }, 0);
				self.opts.keyboard_help = false;
			});

			$('.gmpm_full', photo_show).on('click', function(){
				if(self.fsApi.ok){
					if(self.fsApi.is()){
						self.fsApi.cancel($('html')[0]);
					} else{
						self.fsApi.request($('html')[0]);
					}
				} else {
					self.maximize();
				}
				setTimeout(function(){ self.swiper_gallery.update(true); }, 0);
			});


			$(self.el).mouseenter(function(){
				photo_show.addClass('gmpm_mouse_enter');
				if(!photo_show.hasClass('gmpm_keyboard_active')){
					$('.gmpm_photo_show').removeClass('gmpm_keyboard_active');
					Mousetrap.reset();
					Mousetrap.bind('esc', function() {
						if(self.opts.gallery_focus_maximized){
							self.maximize();
							return;
						} else
						if(self.opts.gallery_focus){
							self.focus(false);
						} else {
							if(!photo_show.hasClass('gmpm_mouse_enter')){
								Mousetrap.reset();
								$('.gmpm_photo_show').removeClass('gmpm_keyboard_active');
							}
						}
					});
					Mousetrap.bind('m', function() { self.maximize(); });
					Mousetrap.bind('s', function() { self.slideshow(); });
					Mousetrap.bind('left', function() { self.swiper_gallery.slidePrev(); });
					Mousetrap.bind('right', function() { self.swiper_gallery.slideNext(); });
					$('.gmpm_photo_show', this).addClass('gmpm_keyboard_active');
				}
			}).mouseleave(function(){
				photo_show.removeClass('gmpm_mouse_enter');
			});
		},
		slideRate: function(like){
			var elid = this.$el.attr('id'),
				ai = this.swiper_gallery.activeIndex,
				item = this.content.data[ai];
			if(this.storage && this.storage[item.file]){
				item.viewed = this.content.data[ai].viewed = true;
				if('liked' == this.storage[item.file]){
					item.liked = this.content.data[ai].liked = true;
				}
			}
			if(!item.viewed){
				this.content.data[ai].viewed = true;
				if(this.storage){
					this.storage[item.file] = 'viewed';
					sessionStorage.setItem( elid, JSON.stringify(this.storage) );
				}
				if(this.opts.ajaxurl){
					$.post(this.opts.ajaxurl, { action: 'gmedia_module_interaction', hit: item.id }, function(r){});
				}
			}
			if(like && !item.liked){
				this.content.data[ai].liked = true;
				if(this.storage){
					this.storage[item.file] = 'liked';
					sessionStorage.setItem( elid, JSON.stringify(this.storage) );
				}
				if(this.opts.ajaxurl){
					$.post(this.opts.ajaxurl, { action: 'gmedia_module_interaction', hit: item.id, vote: 1 }, function(r){});
				}
			}
		},
		focus: function(active){
			var photo_show = $('.gmpm_photo_show', this.el);
			this.opts.gallery_focus = (undefined === active)? !this.opts.gallery_focus : active;
			if(this.opts.gallery_focus){
				photo_show.addClass('gmpm_focus');
			} else {
				photo_show.addClass('gmpm_no-transition').removeClass('gmpm_focus');
				setTimeout(function(){ photo_show.removeClass('gmpm_no-transition'); }, 0);
			}
			var self = this;
			setTimeout(function(){ self.swiper_gallery.update(true); }, 0);
		},
		slideshow: function(){
			if(this.swiper_gallery.autoplaying){
				this.swiper_gallery.stopAutoplay();
			} else {
				this.swiper_gallery.params.autoplay = this.opts.slideshow_delay;
				this.swiper_gallery.startAutoplay();
			}
		},
		maximize: function(){
			var photo_show = $('.gmpm_photo_show', this.el);
			if(this.opts.gallery_focus){
				if(this.opts.gallery_focus_maximized){
					photo_show.removeClass('gmpm_focus_maximized');
					this.opts.gallery_focus_maximized = false;
				} else{
					photo_show.addClass('gmpm_focus_maximized');
					this.opts.gallery_focus_maximized = true;
				}
			} else {
				if(this.opts.gallery_maximized){
					photo_show.removeClass('gmpm_maximized');
					this.opts.gallery_maximized = false;
				} else {
					photo_show.addClass('gmpm_maximized');
					this.opts.gallery_maximized = true;
				}
				this.galleryAutoHeight();
			}
		},
		hasProperty: function(value, index) {
			if (index instanceof Array) {
				return index.length === 0 ||
					(this.hasProperty(value, index[0])
					&& this.hasProperty(value[index[0]], index.slice(1)));
			}
			return value.hasOwnProperty(index);
		},
		$_GET: function(variable){
			var url = window.location.href.split('?')[1];
			if(url){
				url = url.split('#')[0];
				var variables = (typeof(url) === 'undefined')? [] : url.split('&'),
					i;

				for(i = 0; i < variables.length; i++){
					if(variables[i].indexOf(variable) != -1){
						return variables[i].split('=')[1];
					}
				}
			}

			return false;
		},

		__: function () { return this._version }

	});

	$.fn[ pluginName ] = function ( options, content ) {
		options = options || {};
		content = content || {};
		return this.each(function() {
			if ( !$.data( this, pluginName ) ) {
				var pluginInstance = new Plugin(this, options, content);
				$.data( this, pluginName, pluginInstance );
			}
		});
	};

})( jQuery, window, document );
