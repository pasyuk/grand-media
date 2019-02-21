/*
 * Title                   : gmPhantom
 * Copyright               : 2013-2016 CodEasily.com
 * Website                 : http://www.codeasily.com
 */
if(typeof jQuery.fn.gmPhantom == 'undefined') {
    (function($, window, document) {
        $.fn.gmPhantom = function(method) {
            var Container = this,
                elID = $(this).attr('id'),
                ID = elID.replace('GmediaGallery_', ''),
                Content,
                opt,
                timeout,

                opt_str = {
                    'thumbsNavigation': 'scroll', // Thumbnails Navigation (mouse, scroll). Default value: mouse. Set how you navigate through the thumbnails.
                    'thumbsAlign': 'center', // Thumbnails align. Default value: left.
                    'thumbsInfo': 'label', // Info Thumbnails Display (none, tooltip, label). Default value: tooltip. Display a small info text on the thumbnails, a tooltip or a label on bottom.
                    'mfp_css': '',
                    'module_dirurl': '',
                },
                opt_hex = {
                    'bgColor': 'ffffff', // Background Color (color hex code). Default value: ffffff. Set gallery background color.
                    'thumbBG': 'ffffff', // Thumbnail Border Color (color hex code). Default value: cccccc. Set the color of a thumbnail's border.
                    'thumbBorderColor': 'cccccc', // Thumbnail Border Color (color hex code). Default value: cccccc. Set the color of a thumbnail's border.
                    'tooltipTextColor': 'css', // Tooltip Text Color (color hex code). Default value: 000000. Set tooltip text color.
                    'tooltipBgColor': 'css', // Tooltip Background Color (color hex code). Default value: ffffff. Set tooltip background color.
                    'tooltipStrokeColor': 'css', // Tooltip Stroke Color (color hex code). Default value: 000000. Set tooltip stroke color.
                    'labelTextColor': 'ffffff', //   Label-Over Text Color (color hex code). Default value: 000000.
                    'labelLinkColor': 'e7e179',
                    'label8TextColor': '0b0b0b', // Label Text Color (color hex code). Default value: 000000.
                    'label8LinkColor': '3695E7',
                    'sidebarBGColor': 'ffffff',
                    'lightboxControlsColor': 'ffffff', //   Tooltip Text Color (color hex code). Default value: 000000.
                    'lightboxTitleColor': 'f3f3f3', //   Tooltip Text Color (color hex code). Default value: 000000.
                    'lightboxTextColor': 'f3f3f3', //   Tooltip Text Color (color hex code). Default value: 000000.
                    'lightboxBGColor': '0b0b0b' // Lightbox Window Color (color hex code). Default value: 000000. Set the color for the lightbox window.
                },
                opt_int = {
                    'initRPdelay': 200,
                    'maxheight': 0,
                    'thumbCols': 0, // Number of Columns (auto, number). Default value: 0. Set the number of columns for the grid.
                    'thumbRows': 0, // Number of Lines (auto, number). Default value: 0. Set the number of lines for the grid.
                    'bgAlpha': 0, // Background Alpha (value from 0 to 100). Default value: 0. Set gallery background alpha.
                    'thumbWidth': 200, // Thumbnail Width (the size in pixels). Default value: 150. Set the width of a thumbnail.
                    'thumbHeight': 180, // Thumbnail Height (the size in pixels). Default value: 150. Set the height of a thumbnail.
                    'thumbWidthMobile': 150,
                    'thumbHeightMobile': 135,
                    'thumbsSpacing': 8, // Thumbnails Spacing (value in pixels). Default value: 10. Set the space between thumbnails.
                    'thumbsVerticalPadding': 4, // Thumbnails Padding Top (value in pixels). Default value: 5. Set the top padding for the thumbnails.
                    'thumbsHorizontalPadding': 4, // Thumbnails Padding Top (value in pixels). Default value: 5. Set the top padding for the thumbnails.
                    'thumbAlpha': 90, // Thumbnail Alpha (value from 0 to 100). Default value: 85. Set the transparancy of a thumbnail.
                    'thumbAlphaHover': 100, // Thumbnail Alpha Hover (value from 0 to 100). Default value: 100. Set the transparancy of a thumbnail when hover.
                    'thumbBorderSize': 1, // Thumbnail Border Size (value in pixels). Default value: 1. Set the size of a thumbnail's border.
                    'thumbPadding': 2, // Thumbnail Padding (value in pixels). Default value: 3. Set padding value of a thumbnail.
                    'lightboxBGAlpha': 80 // Lightbox Window Alpha (value from 0 to 100). Default value: 80. Set the transparancy for the lightbox window.
                },
                opt_bool = {
                    'deepLinks': true,
                    'socialShareEnabled': true, // Social Share Enabled (true, false). Default value: true.
                    'share_post_link': true,
                    'show_tags': true,
                    'show_categories': true,
                    'show_albums': true,
                    'viewsEnabled': true,
                    'likesEnabled': true,
                    'commentsEnabled': true, // Comments Enabled (true, false). Default value: true.
                    'lightbox800HideArrows': false, // Hide Arrows if window width less than 800px.
                    'thumbScale': true, // Scale effect for thumb on mouseover
                    'labelOnHover': true, // Show thumb label only on mouseover
                    'thumb2link': false // Open link instead of lightbox when item have "link" attr
                },

                noItems = 0,

                Storage = {views: [], likes: []},

                startGalleryID = 0,
                startWith = 0,

                scrollTop = 0,
                scrollLeft = 0,
                itemLoaded = 0,
                thumbsNavigationArrowsSpeed = 200,
                cc = 0,

                methods = {
                    init: function(arguments) {// Init Plugin.
                        opt = $.extend(true, {}, opt_str, opt_int, opt_bool, opt_hex, arguments[0]);
                        $.each(opt, function(key, val) {
                            if(key in opt_bool) {
                                opt[key] = (!(!val || val == '0' || val == 'false'));
                            } else if(key in opt_int) {
                                opt[key] = parseInt(val);
                            }
                        });
                        opt.initialHeight = opt.maxheight;
                        opt.initialCols = opt.thumbCols;
                        opt.initialRows = opt.thumbRows;
                        opt.thumbWidthDesktop = opt.thumbWidth;
                        opt.thumbHeightDesktop = opt.thumbHeight;
                        opt.dratio = opt.thumbWidthDesktop / opt.thumbHeightDesktop;
                        opt.mratio = opt.thumbWidthMobile / opt.thumbHeightMobile;

                        methods.parseContent();

                        var timeout;
                        $(window).on('resize.gmPhantom', function() {
                            methods.initRP();
                            clearTimeout(timeout);
                            timeout = setTimeout(function() {
                                methods.initRP();
                            }, 600);
                        });

                        setTimeout(methods.initRP, opt.initRPdelay);
                    },
                    parseContent: function() {// Parse Content.

                        $('.gmPhantom_ThumbContainer', Container).not('.gmPhantom_parsed').each(function(){
                            var link = $(this).attr('data-link');
                            if(link) {
                                if(link.indexOf('youtube.com/') !== -1 || link.indexOf('vimeo.com/') !== -1) {
                                    $('.gmPhantom_Thumb', this).attr('href', link);
                                    $(this).addClass('mfp-iframe').removeAttr('data-link').removeAttr('data-target').attr('data-type', 'video');
                                } else if(link.indexOf('//maps.google.') !== -1 || (link.indexOf('google.') !== -1 && link.indexOf('/maps/embed') !== -1)) {
                                    $('.gmPhantom_Thumb', this).attr('href', link);
                                    $(this).addClass('mfp-iframe').removeAttr('data-link').removeAttr('data-target').attr('data-type', 'map');
                                }
                            }
                            $(this).addClass('gmPhantom_parsed');
                        });

                        noItems = $('.gmPhantom_thumbsWrapper', Container).find('> div').length;
                        methods.rpResponsive();

                        if(!$(Container).data('gmPhantom_initialized')) {
                            methods.initGallery();
                            $(Container).data('gmPhantom_initialized', true);
                        }

                    },
                    initGallery: function() {// Init the Gallery

                        var currentScrollPosition = 0;
                        $(document).scroll(function() {
                            currentScrollPosition = $(this).scrollTop();
                        });
                        window.onhashchange = function() {
                            methods.loadGalleryDeepLink();
                        };

                        $("input, textarea").focus(function() {
                            $(document).scrollTop(currentScrollPosition);
                        });

                        var browser_class = '';
                        if(prototypes.isIEBrowser()) {
                            if(prototypes.isIEBrowser() < 10) {
                                browser_class += ' msie msie9';
                            } else {
                                browser_class += ' msie';
                            }
                        }
                        if(prototypes.isTouchDevice()) {
                            browser_class += ' istouch';
                        }

                        $('.gmPhantom_thumbsWrapper', Container).addClass(browser_class);
                        if(opt.thumbsInfo == 'tooltip' && !prototypes.isTouchDevice()) {
                            $('.gmPhantom_Container', Container).append('<div class="gmPhantom_Tooltip"></div>');
                        }

                        methods.initSettings();

                        var thumbs_wrapper = $('.gmPhantom_thumbsWrapper', Container);
                        thumbs_wrapper.magnificPopup({
                            type: 'image',
                            delegate: '.gmPhantom_ThumbContainer',
                            preloader: true,
                            closeBtnInside: false,
                            fixedContentPos: 'auto',
                            fixedBgPos: true,
                            overflowY: '',
                            closeMarkup: '<div title="%title%" class="mfp-button mfp-close">&#215;</div>',
                            tLoading: '', // remove text from preloader
                            callbacks: {
                                elementParse: function(item) {
                                    // Function will fire for each target element
                                    // "item.el" is a target DOM element (if present)
                                    // "item.src" is a source that you may modify
                                    var data = item.el.data();
                                    item.gm = data;
                                    item.image = $('.gmPhantom_Thumb img', item.el).attr('data-src');
                                    item.src = $('.gmPhantom_Thumb', item.el).attr('href');
                                    item.title = $('.gmPhantom_title', item.el).html();

                                    item.gm.viewed = prototypes.arrVal(Storage.views, parseInt(item.gm.id));
                                    item.gm.liked = prototypes.arrVal(Storage.likes, parseInt(item.gm.id));

                                },
                                markupParse: function(template, values, item) {
                                    values.title = item.title;
                                    values.description = $('.gmPhantom_Details', item.el).html();
                                },

                                imageLoadComplete: function() {
                                    var self = this;
                                    setTimeout(function() {
                                        self.wrap.addClass('mfp-image-loaded');
                                    }, 16);
                                },
                                open: function() {
                                    $.magnificPopup.instance.next = function() {
                                        if($.magnificPopup.instance.index < $.magnificPopup.instance.items.length - 1) {
                                            $.magnificPopup.proto.next.call(this, arguments);
                                        }
                                    };
                                    $.magnificPopup.instance.prev = function() {
                                        if($.magnificPopup.instance.index > 0) {
                                            $.magnificPopup.proto.prev.call(this, arguments);
                                        }
                                    };
                                    $.magnificPopup.instance.toggleArrows = function() {
                                        if($.magnificPopup.instance.index < $.magnificPopup.instance.items.length - 1) {
                                            $(".mfp-arrow-right").show();
                                        }
                                        if($.magnificPopup.instance.index == $.magnificPopup.instance.items.length - 1) {
                                            $(".mfp-arrow-right").hide();
                                        }

                                        if($.magnificPopup.instance.index > 0) {
                                            $(".mfp-arrow-left").show();
                                        }
                                        if($.magnificPopup.instance.index == 0) {
                                            $(".mfp-arrow-left").hide();
                                        }
                                    };
                                    $.magnificPopup.instance.updateItemHTML = function() {
                                        $.magnificPopup.instance.toggleArrows();
                                        $.magnificPopup.proto.updateItemHTML.call(this, arguments);
                                    };
                                    var orig_checkIfClose = $.magnificPopup.instance._checkIfClose;
                                    $.magnificPopup.instance._checkIfClose = function(target) {
                                        if($(target).closest('.mfp-prevent-close').length) {
                                            return;
                                        }
                                        return orig_checkIfClose(target);
                                    };

                                    $(document.body).addClass('mfp-gmedia-open gmedia-phantom');
                                    itemLoaded = this.currItem.gm.id;
                                    if(opt.commentsEnabled) {
                                        this.wrap.on('click.gmCloseComments', '.mfp-img--comments-div', function() {
                                            $('#mfp_comments_' + ID, this.wrap).trigger('click');
                                            return false;
                                        });
                                        this.wrap.on('click', '.mfp-close-comments', function() {
                                            $('#mfp_comments_' + ID, this.wrap).trigger('click');
                                            return false;
                                        });
                                    }
                                    if(opt.socialShareEnabled) {
                                        var mfp = this;
                                        $(mfp.wrap).on('click', '.mfp-share_sharelizer', function() {
                                            var sharelink,
                                                title = mfp.currItem.title,
                                                imgsrc = mfp.currItem.image,
                                                _url = ('' + window.location.href).split('#'),
                                                url = _url[0];
                                            if(opt.share_post_link) {
                                                url = mfp.currItem.gm['post_link'];
                                            } else {
                                                var separator = (url.indexOf("?") === -1)? "?" : "&",
                                                    newParam = separator + "gmedia_share=" + mfp.currItem.gm['id'];
                                                url = url.replace(newParam,"");
                                                url += newParam;
                                                var hash = "#!gallery-" + ID + '-' + mfp.currItem.gm['id'];
                                                url += hash;
                                            }
                                            if($(this).hasClass('mfp-share_twitter')) {
                                                sharelink = 'https://twitter.com/home?status=' + encodeURIComponent(title + ' ' + url);
                                                window.open(sharelink, '_blank');
                                            }
                                            if($(this).hasClass('mfp-share_facebook')) {
                                                sharelink = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
                                                window.open(sharelink, '_blank');
                                            }
                                            if($(this).hasClass('mfp-share_pinterest')) {
                                                sharelink = 'https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(url) + '&media=' + encodeURIComponent(imgsrc) + '&description=' + encodeURIComponent(title);
                                                window.open(sharelink, '_blank');
                                            }
                                            if($(this).hasClass('mfp-share_stumbleupon')) {
                                                sharelink = 'http://www.stumbleupon.com/submit?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title);
                                                window.open(sharelink, '_blank');
                                            }
                                        });
                                    }
                                    this.toggleArrows.call(this);
                                },
                                close: function() {
                                    $(document.body).removeClass('mfp-gmedia-open gmedia-phantom');
                                    if('image' != this.currItem.gm.type) {
                                        $(document.body).removeClass('mfp-zoom-out-cur');
                                        this.wrap.removeClass('mfp-iframe-loaded');
                                    }
                                    this.wrap.removeClass('mfp-image-loaded');

                                    if(opt.commentsEnabled) {
                                        this.wrap.off('click.gmCloseComments');
                                    }
                                    if(opt.deepLinks) {
                                        var hash = '#!',
                                            url = ('' + window.location).split('#')[0] + hash;
                                        if(!!(window.history && window.history.replaceState)) {
                                            window.history.replaceState({}, document.title, url);
                                        } else {
                                            location.replace(url);
                                        }
                                    }
                                    //this.contentContainer.attr('data-gmtype', this.currItem.gm.type).attr('data-ext', this.currItem.gm.ext);
                                    $('#wpadminbar').css({'z-index': ''});
                                    $(window).scrollTop(scrollTop);
                                    itemLoaded = this.currItem.gm.id;
                                },
                                beforeOpen: function() {
                                    $('#wpadminbar').css({'z-index': 1000});
                                    $(this.wrap).attr('id', 'mfp_gm_' + ID);
                                    $(this.bgOverlay).attr('id', 'mfp_gm_' + ID + '_bg');
                                    if(opt.lightbox800HideArrows) {
                                        this.wrap.addClass('mfp800-hide-arrows');
                                    }
                                    if(opt.mfp_css && !$('#mfp_css_' + ID, this.wrap).length) {
                                        this.wrap.append('<style id="mfp_css_' + ID + '">' + opt.mfp_css + '</style>');
                                    }
                                    scrollTop = $(window).scrollTop();
                                },
                                change: function() {
                                    if(opt.deepLinks) {
                                        var hash = "#!gallery-" + ID + '-' + this.currItem.gm.id,
                                            url = ('' + window.location).split('#')[0] + hash;
                                        if(!!(window.history && window.history.replaceState)) {
                                            window.history.replaceState({}, document.title, url);
                                        } else {
                                            location.replace(url);
                                        }
                                    }
                                    this.contentContainer.attr('data-gmtype', this.currItem.gm.type).attr('data-ext', this.currItem.gm.ext);
                                    if(opt.commentsEnabled) {
                                        this.wrap.removeClass('mfp-comments-open mfp-comments-loaded');
                                        $('.mfp-comments-wrapper', this.contentContainer).css({height: ''}).empty();
                                    }
                                    if(opt.likesEnabled) {
                                        this.wrap.removeClass('phantom-gmedia-liked');
                                    }
                                    clearTimeout(timeout);
                                    if('image' != this.currItem.gm.type) {
                                        $(document.body).addClass('mfp-zoom-out-cur');
                                        var self = this;
                                        setTimeout(function() {
                                            self.wrap.addClass('mfp-iframe-loaded');
                                        }, 16);
                                    }
                                },
                                afterChange: function() {
                                    if(opt.socialShareEnabled) {
                                        methods.initSocialShare(this.currItem, this);
                                    }

                                    if(opt.commentsEnabled) {
                                        methods.initComments(this.currItem, this);
                                    }

                                    if(opt.viewsEnabled || opt.likesEnabled) {
                                        var self = this;
                                        timeout = setTimeout(function() {
                                            methods.viewLike(self.currItem);
                                        }, 1000);

                                        if(opt.viewsEnabled) {
                                            methods.initViews(this.currItem, this);
                                        }
                                        if(opt.likesEnabled) {
                                            methods.initLikes(this.currItem, this);
                                            if(this.currItem.gm.liked) {
                                                this.wrap.addClass('phantom-gmedia-liked');
                                            } else {
                                                this.wrap.removeClass('phantom-gmedia-liked');
                                            }
                                        }
                                    }
                                    itemLoaded = this.currItem.gm.id;
                                },
                                updateStatus: function(data) {
                                    //console.log(data);
                                    if(data.status == 'loading') {
                                        this.wrap.removeClass('mfp-image-loaded mfp-iframe-loaded');
                                    }
                                }
                            },
                            image: {
                                markup: '' +
                                '<div class="mfp-figure">' +
                                '   <div class="mfp-close"></div>' +
                                '   <figure>' +
                                '       <div class="mfp-img"></div>' +
                                '       <figcaption>' +
                                '           <div class="mfp-bottom-bar">' +
                                '               <div class="mfp-description gmPhantom_Details"></div>' +
                                '               <div class="mfp-counter"></div>' +
                                '           </div>' +
                                '       </figcaption>' +
                                '   </figure>' +
                                '</div>' +
                                '<div class="mfp-comments-container">' +
                                '   <div class="mfp-comments-content mfp-prevent-close"><div class="mfp-close-comments">&times;</div><div class="mfp-comments-wrapper"></div></div>' +
                                '</div>' +
                                '<div class="mfp-prevent-close mfp-buttons-bar mfp-gmedia-stuff10"></div>'
                            },
                            iframe: {
                                markup: '' +
                                '<div class="mfp-iframe-wrapper">' +
                                '   <div class="mfp-close"></div>' +
                                '   <div class="mfp-iframe-scaler">' +
                                '       <iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowtransparency="true" allowfullscreen></iframe>' +
                                '   </div>' +
                                '   <div class="mfp-bottom-bar gm-iframe-bottom-bar">' +
                                '       <div class="mfp-description gmPhantom_Details"></div>' +
                                '       <div class="mfp-counter"></div>' +
                                '   </div>' +
                                '</div>' +
                                '<div class="mfp-comments-container">' +
                                '   <div class="mfp-comments-content"><div class="mfp-close-comments">&times;</div><div class="mfp-comments-wrapper"></div></div>' +
                                '</div>' +
                                '<div class="mfp-prevent-close mfp-buttons-bar mfp-gmedia-stuff10"></div>'
                            },
                            gallery: {
                                enabled: true,
                                arrowMarkup: '<div title="%title%" class="mfp-button mfp-arrow mfp-arrow-%dir%"></div>',
                                tCounter: '%curr% / %total%'
                            },

                            mainClass: 'mfp-zoom-in',
                            removalDelay: 500, //delay removal by X to allow out-animation

                        });

                        methods.loadGalleryDeepLink();

                        $(Container).on('click', '.gmPhantom_pager', function(e) {
                            e.preventDefault();
                            $(this).parent().addClass('gmPhantom_ThumbLoader');
                            $.get($(this).attr('href'), function(data) {
                                var temp = $('<div style="visibility:hidden;position:absolute;pointer-events:none;"></div>').appendTo(Container);
                                temp.append($('#' + elID, data).html());
                                $('.gmPhantom_LoadMore', Container).replaceWith(temp.find('.gmPhantom_thumbsWrapper').html());
                                methods.initThumbs();
                                temp.remove();
                            });
                            return false;
                        });

                    },
                    loadGalleryDeepLink: function() {
                        var prefix = "#!gallery-";
                        var h = location.hash;
                        if(h.indexOf(prefix) === 0) {
                            h = h.substr(prefix.length).split('-');
                            if(h[0] && h[0] == ID) {
                                $(document).scrollTop($(Container).offset().top);
                                if(h[1]) {
                                    startWith = $('.gmPhantom_ThumbContainer[data-id="' + h[1] + '"]', Container).index();
                                } else {
                                    startWith = 0;
                                }
                                if(-1 !== startWith) {
                                    $('.gmPhantom_thumbsWrapper', Container).magnificPopup("open", startWith);
                                }
                            }
                        }
                    },
                    initSocialShare: function(item, mfp) {
                        var share_buttons = '' +
                            '<div class="mfp-prevent-close mfp-button mfp-share mfp-gmedia-stuff08" id="mfp_share_' + ID + '">' +
                            '     <a title="Share">' +
                            '         <span class="mfp-prevent-click">' +
                            '             <svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="icon-share2" viewBox="0 0 32 32"><path class="path1" d="M27 22c-1.411 0-2.685 0.586-3.594 1.526l-13.469-6.734c0.041-0.258 0.063-0.522 0.063-0.791s-0.022-0.534-0.063-0.791l13.469-6.734c0.909 0.94 2.183 1.526 3.594 1.526 2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5c0 0.269 0.022 0.534 0.063 0.791l-13.469 6.734c-0.909-0.94-2.183-1.526-3.594-1.526-2.761 0-5 2.239-5 5s2.239 5 5 5c1.411 0 2.685-0.586 3.594-1.526l13.469 6.734c-0.041 0.258-0.063 0.522-0.063 0.791 0 2.761 2.239 5 5 5s5-2.239 5-5c0-2.761-2.239-5-5-5z"></path></symbol></svg>' +
                            '             <svg class="gmPhantom_svgicon"><use xlink:href="#icon-share2"/></svg>' +
                            '         </span>' +
                            '     </a>' +
                            '     <ul class="mfp-prevent-close mfp-share_sharelizers">' +
                            '         <li><a class="mfp-share_facebook mfp-share_sharelizer">Facebook</a></li>' +
                            '         <li><a class="mfp-share_twitter mfp-share_sharelizer">Twitter</a></li>' +
                            '         <li><a class="mfp-share_pinterest mfp-share_sharelizer">Pinterest</a></li>' +
                            '         <li><a class="mfp-share_stumbleupon mfp-share_sharelizer">StumbleUpon</a></li>' +
                            '     </ul>' +
                            '</div>';

                        var mfp_share = $(share_buttons);
                        mfp_share.on('click', function() {
                            $(this).toggleClass('mfp-share_open');
                        });

                        var share_div = mfp.wrap.find('#mfp_share_' + ID);
                        if(!share_div.length) {
                            $('.mfp-buttons-bar', mfp.wrap).append(mfp_share);
                        } else {
                            $(share_div).replaceWith(mfp_share);
                        }
                    },
                    initComments: function(item, mfp) {
                        var comments_button = '' +
                            '<div class="mfp-prevent-close mfp-button mfp-comments" id="mfp_comments_' + ID + '">' +
                            '   <a title="Comments"><span class="mfp-prevent-close mfp-comments-count">' + item.gm['cc'] + '</span>' +
                            '      <span class="mfp-prevent-click mfp_comments_icon">' +
                            '          <svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="icon-bubbles2" viewBox="0 0 36 32"><path class="path1" d="M15 0v0c8.284 0 15 5.435 15 12.139s-6.716 12.139-15 12.139c-0.796 0-1.576-0.051-2.339-0.147-3.222 3.209-6.943 3.785-10.661 3.869v-0.785c2.008-0.98 3.625-2.765 3.625-4.804 0-0.285-0.022-0.564-0.063-0.837-3.392-2.225-5.562-5.625-5.562-9.434 0-6.704 6.716-12.139 15-12.139zM31.125 27.209c0 1.748 1.135 3.278 2.875 4.118v0.673c-3.223-0.072-6.181-0.566-8.973-3.316-0.661 0.083-1.337 0.126-2.027 0.126-2.983 0-5.732-0.805-7.925-2.157 4.521-0.016 8.789-1.464 12.026-4.084 1.631-1.32 2.919-2.87 3.825-4.605 0.961-1.84 1.449-3.799 1.449-5.825 0-0.326-0.014-0.651-0.039-0.974 2.268 1.873 3.664 4.426 3.664 7.24 0 3.265-1.88 6.179-4.82 8.086-0.036 0.234-0.055 0.474-0.055 0.718z"></path></symbol></svg>' +
                            '          <svg class="gmPhantom_svgicon"><use xlink:href="#icon-bubbles2"/></svg>' +
                            '      </span>' +
                            '   </a>' +
                            '</div>';
                        var mfp_comments = $(comments_button);
                        mfp_comments.on('click', function() {
                            var comments_wrapper = $('.mfp-comments-wrapper', mfp.contentContainer);
                            if(mfp.wrap.hasClass('mfp-comments-open')) {
                                $(this).removeClass('mfp-button-active');
                                comments_wrapper.css({height: ''});
                                $('figure > img', mfp.wrap).removeClass('mfp-img--comments-div').addClass('mfp-img');
                                mfp.wrap.removeClass('mfp-comments-open');
                                if($(window).width() <= 800) {
                                    $('.mfp-wrap').animate({scrollTop: 0}, 200);
                                    $('body').delay(200).animate({scrollTop: $('#mfp_gm_' + ID).offset().top}, 400);
                                } else {
                                    $(window).scrollTop($('#mfp_gm_' + ID).offset().top);
                                }
                            } else {
                                $(this).addClass('mfp-button-active');
                                var scrlbar = methods.scrollbarWidth();
                                comments_wrapper.css({height: $('iframe', comments_wrapper).height()}).parent().css({'width': 'calc(100% + ' + scrlbar + 'px)', 'min-width': 320 + scrlbar + 'px'});
                                $('figure > img', mfp.wrap).removeClass('mfp-img').addClass('mfp-img--comments-div');
                                mfp.wrap.addClass('mfp-comments-open')
                                if(!mfp.wrap.hasClass('mfp-comments-loaded')) {
                                    methods.loadComments(item, mfp);
                                }
                                if($(window).width() <= 800) {
                                    $('.mfp-wrap').animate({scrollTop: ($('.mfp-comments-container', '#mfp_gm_' + ID).offset().top + $('.mfp-wrap').scrollTop() - 150)}, 200);
                                    $('body').delay(200).animate({scrollTop: ($('.mfp-comments-container', '#mfp_gm_' + ID).offset().top - 150)}, 400);
                                }
                            }
                        });

                        var comments_div = $(mfp.wrap).find('#mfp_comments_' + ID);
                        if(!comments_div.length) {
                            $('.mfp-buttons-bar', mfp.wrap).append(mfp_comments);
                        } else {
                            $(comments_div).replaceWith(mfp_comments);
                        }

                    },
                    loadComments: function(item, mfp) {
                        var comments_content = $('.mfp-comments-content', mfp.contentContainer),
                            comments_wrapper = $('.mfp-comments-wrapper', comments_content);
                        comments_wrapper.empty();
                        mfp.wrap.removeClass('mfp-comments-loaded').addClass('mfp-comments-loading');
                        if(GmediaGallery) {
                            opt.ajax_actions['comments']['data']['post_id'] = item.gm['post_id'];
                            $.ajax({
                                type: "post",
                                dataType: "json",
                                url: GmediaGallery.ajaxurl,
                                data: {action: opt.ajax_actions['comments']['action'], _ajax_nonce: GmediaGallery.nonce, data: opt.ajax_actions['comments']['data']},
                            }).done(function(data) {
                                if(data.comments_count) {
                                    mfp.wrap.find('.mfp-comments-count').html(data.comments_count);
                                    item.gm['cc'] = data.comments_count
                                }
                                if(data.content) {
                                    $('.mfp-comments-wrapper', comments_content).html(data.content).find('iframe').on('load', function() {
                                        mfp.wrap.removeClass('mfp-comments-loading').addClass('mfp-comments-loaded');
                                        var body = this.contentWindow.document.body, html = this.contentWindow.document.documentElement;
                                        var height = Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight);
                                        $(this).css({height: 20 + height, overflowY: 'hidden'}).parent().css({height: 20 + height});
                                        this.contentWindow.onbeforeunload = function() {
                                            mfp.wrap.removeClass('mfp-comments-loaded').addClass('mfp-comments-loading');
                                        };
                                    });
                                }
                            }).fail(function() {
                                mfp.wrap.removeClass('mfp-comments-loading');
                            });
                        }
                    },
                    initLikes: function(item, mfp) {
                        if(mfp.currItem.liked) {
                            mfp.wrap.addClass('phantom-gmedia-liked');
                        }

                        var likes = '' +
                            '<div class="mfp-prevent-close mfp-button mfp-likes" id="mfp_likes_' + ID + '">' +
                            '   <a><span class="mfp-likes-count">' + item.gm['likes'] + '</span>' +
                            '      <span class="mfp-prevent-click mfp_likes_icon">' +
                            '          <svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="icon-heart" viewBox="0 0 32 32"><path class="path1" d="M23.6 2c-3.363 0-6.258 2.736-7.599 5.594-1.342-2.858-4.237-5.594-7.601-5.594-4.637 0-8.4 3.764-8.4 8.401 0 9.433 9.516 11.906 16.001 21.232 6.13-9.268 15.999-12.1 15.999-21.232 0-4.637-3.763-8.401-8.4-8.401z"></path></symbol></svg>' +
                            '          <svg class="gmPhantom_svgicon"><use xlink:href="#icon-heart"/></svg>' +
                            '      </span>' +
                            '   </a>' +
                            '</div>';
                        var likes_obj = $(likes);
                        likes_obj.on('click', function() {
                            methods.viewLike(mfp.currItem, true);
                            mfp.wrap.addClass('phantom-gmedia-liked');
                            $('.mfp-likes-count', mfp.wrap).text(item.gm['likes']);
                        });

                        var likes_div = $(mfp.wrap).find('#mfp_likes_' + ID);
                        if(!likes_div.length) {
                            $('.mfp-buttons-bar', mfp.wrap).append(likes_obj);
                        } else {
                            $(likes_div).replaceWith(likes_obj);
                        }

                    },
                    initViews: function(item, mfp) {
                        var views = '' +
                            '<div class="mfp-prevent-close mfp-button mfp-views" id="mfp_views_' + ID + '">' +
                            '   <span class="mfp-prevent-close mfp-prevent-click"><span class="mfp-prevent-close mfp-views-count">' + item.gm['views'] + '</span>' +
                            '   <span class="mfp-prevent-close mfp-prevent-click mfp_views_icon">' +
                            '      <svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="icon-eye" viewBox="0 0 1024 1024"><path class="path1" d="M512 192c-223.318 0-416.882 130.042-512 320 95.118 189.958 288.682 320 512 320 223.312 0 416.876-130.042 512-320-95.116-189.958-288.688-320-512-320zM764.45 361.704c60.162 38.374 111.142 89.774 149.434 150.296-38.292 60.522-89.274 111.922-149.436 150.296-75.594 48.218-162.89 73.704-252.448 73.704-89.56 0-176.858-25.486-252.452-73.704-60.158-38.372-111.138-89.772-149.432-150.296 38.292-60.524 89.274-111.924 149.434-150.296 3.918-2.5 7.876-4.922 11.86-7.3-9.96 27.328-15.41 56.822-15.41 87.596 0 141.382 114.616 256 256 256 141.382 0 256-114.618 256-256 0-30.774-5.452-60.268-15.408-87.598 3.978 2.378 7.938 4.802 11.858 7.302v0zM512 416c0 53.020-42.98 96-96 96s-96-42.98-96-96 42.98-96 96-96 96 42.982 96 96z"></path></symbol></svg>' +
                            '      <svg class="gmPhantom_svgicon"><use xlink:href="#icon-eye"/></svg>' +
                            '   </span></span>' +
                            '</div>';

                        var views_div = $(mfp.wrap).find('#mfp_views_' + ID);
                        if(!views_div.length) {
                            $('.mfp-buttons-bar', mfp.wrap).append(views);
                        } else {
                            $(views_div).replaceWith(views);
                        }

                    },
                    viewLike: function(item, like) {
                        var id = parseInt(item.gm.id);
                        if(!item.gm.viewed) {
                            item.gm.viewed = true;
                            item.gm['views'] += 1;
                            Storage.views.push(id);
                            sessionStorage.setItem('GmediaGallery', JSON.stringify(Storage));
                            if(GmediaGallery.ajaxurl) {
                                $.ajax({
                                    type: "post",
                                    dataType: "json",
                                    url: GmediaGallery.ajaxurl,
                                    data: {action: 'gmedia_module_interaction', hit: id}
                                }).done(function(r) {
                                    if(r.views) {
                                        item.gm.views = r.views;
                                        $('.mfp-views-count', item.el).text(item.gm['views']);
                                    }
                                });
                            }
                        }
                        if(like && !item.gm.liked) {
                            item.gm.liked = true;
                            item.gm.likes += 1;
                            Storage.likes.push(id);
                            sessionStorage.setItem('GmediaGallery', JSON.stringify(Storage));
                            $('.mfp-likes-count', item.el).text(item.gm['likes']);
                            if(GmediaGallery.ajaxurl) {
                                $.ajax({
                                    type: "post",
                                    dataType: "json",
                                    url: GmediaGallery.ajaxurl,
                                    data: {action: 'gmedia_module_interaction', hit: id, vote: 1}
                                }).done(function(r) {
                                    if(r.likes) {
                                        item.gm.likes = r.likes;
                                        $('.mfp-likes-count', item.el).text(item.gm['likes']);
                                    }
                                });
                            }
                        }
                    },
                    initSettings: function() {// Init Settings
                        if(window.sessionStorage) {
                            var sesion_storage = sessionStorage.getItem('GmediaGallery');
                            if(sesion_storage) {
                                $.extend(true, Storage, JSON.parse(sesion_storage));
                            }
                        }
                        methods.initContainer();
                        methods.initThumbs();
                        if(opt.thumbsInfo == 'tooltip' && !prototypes.isTouchDevice()) {
                            methods.initTooltip();
                        }

                    },
                    initRP: function() {// Init Resize & Positioning
                        if(!$(Container).is(':visible')) {
                            setTimeout(function() {
                                methods.initRP();
                            }, 1200);
                        } else {
                            methods.rpResponsive();
                            var rrr = opt.width;
                            if(!rrr && 50 > cc) {
                                cc++;
                                clearTimeout(timeout);
                                timeout = setTimeout(function() {
                                    methods.initRP();
                                }, 100);
                            } else {
                                cc = 0;
                                methods.rpThumbs();

                                $('.gmPhantom_Container', Container).css({'opacity': ''});
                            }
                        }
                    },
                    rpResponsive: function() {
                        var hiddenBustedItems;
                        setTimeout(function() {
                            hiddenBustedItems = prototypes.doHideBuster($(Container));
                        }, 0);

                        if($(window).width() <= 640) {
                            $('.gmPhantom_Container', Container).addClass('gmPhantom_MobileView');
                            opt.thumbWidth = opt.thumbWidthMobile;
                            opt.thumbHeight = opt.thumbHeightMobile;
                            opt.ratio = opt.mratio;
                        } else {
                            $('.gmPhantom_Container', Container).removeClass('gmPhantom_MobileView');
                            opt.thumbWidth = opt.thumbWidthDesktop;
                            opt.thumbHeight = opt.thumbHeightDesktop;
                            opt.ratio = opt.dratio;
                        }

                        setTimeout(function() {
                            opt.width = $(Container).width();
                        }, 0);
                        setTimeout(function() {
                            prototypes.undoHideBuster(hiddenBustedItems);
                        }, 0);

                    },

                    initContainer: function() {// Init Container
                        $(Container).css({'text-align': opt.thumbsAlign});

                        $('.gmPhantom_Container', Container).css({
                            'padding-top': opt.thumbsVerticalPadding,
                            'padding-bottom': opt.thumbsVerticalPadding,
                            'padding-left': opt.thumbsHorizontalPadding,
                            'padding-right': opt.thumbsHorizontalPadding
                        });
                        methods.rpContainer();
                    },
                    rpContainer: function() {// Resize & Position Container
                        if(opt.maxheight !== 0) {
                            $(Container).css({maxHeight: opt.maxheight});
                            $('.gmPhantom_Container', Container).css({maxWidth: 'none'});
                        }
                        if(opt.thumbsSpacing) {
                            $('.gmPhantom_thumbsWrapper', Container).css({marginTop: -opt.thumbsSpacing, marginLeft: -opt.thumbsSpacing});
                        }
                    },

                    initThumbs: function() {//Init Thumbnails
                        var thumb_container = $('.gmPhantom_ThumbContainer', Container);
                        if(opt.thumbsInfo == 'tooltip') {
                            if(!prototypes.isTouchDevice()) {
                                thumb_container.hover(function() {
                                        methods.showTooltip($(this));
                                    },
                                    function() {
                                        $('.gmPhantom_Tooltip', Container).css('display', 'none');
                                    });
                            } else {
                                $('.gmPhantom_thumbsWrapper', Container).removeClass('gmPhantom_LabelTolltip').addClass('gmPhantom_LabelInside');
                            }
                        }

                        thumb_container.click(function(e) {
                            var link = $(this).data('link'),
                                target = $(this).data('target');
                            if(link && opt.thumb2link) {
                                e.stopPropagation();
                                prototypes.openLink(link, target);
                                return false;
                            }
                        });
                        setTimeout(function(){
                            $('.gmPhantom_Thumb', thumb_container).off('click').on('click', function(e) {
                                e.stopPropagation();
                                e.preventDefault();
                                $(this).parent().trigger('click');
                                return false;
                            });
                        },1);
                        $('.gmPhantom_ThumbLabel a', thumb_container).on('click', function(e) {
                            e.stopPropagation();
                        });

                        $('.gmPhantom_ThumbLoader .gmPhantom_Thumb img', Container).css({opacity: 0});
	                    setTimeout(function(){
                            $('.gmPhantom_ThumbLoader .gmPhantom_Thumb img', Container).on('load', function() {
                                var image = $(this);
                                image.closest('.gmPhantom_ThumbContainer').removeClass('gmPhantom_ThumbLoader');
                                if(prototypes.isIEBrowser()) {
                                    image.parent().css('background-image', 'url("' + image.attr('src') + '")')
                                } else{
                                    image.animate({opacity: opt.thumbAlpha / 100}, 600, function() {
                                        $(this).css({opacity: ''});
                                    });
                                }
                            }).on('error', function() {}).each(function() {
                                    if(this.complete) {
                                        $(this).load();
                                    } else if(this.error) {
                                        $(this).error();
                                    }
                                });
	                    },0);

                        if(opt.maxheight !== 0) {
                            if(prototypes.isTouchDevice()) {
                                prototypes.touchNavigation($(Container), $('.gmPhantom_thumbsWrapper', Container));
                            }
                            else if(opt.thumbsNavigation == 'mouse') {
                                methods.moveThumbs();
                            }
                            else if(opt.thumbsNavigation == 'scroll') {
                                methods.initThumbsScroll();
                            }
                        }

                        methods.rpThumbs();
                    },
                    rpThumbs: function() {// Resize & Position Thumbnails
                        var thumbW = opt.thumbWidth + opt.thumbBorderSize * 2 + opt.thumbPadding * 2,
                            no = 0,
                            col = 0,
                            row = 1,
                            hiddenBustedItems = prototypes.doHideBuster($(Container));
                        opt.thumbCols = opt.initialCols;
                        opt.thumbRows = opt.initialRows;
                        if(opt.initialHeight === 0 || (opt.thumbCols === 0 && opt.thumbRows === 0)) {
                            opt.thumbCols = parseInt((opt.width + opt.thumbsSpacing - opt.thumbsHorizontalPadding * 2) / (thumbW + opt.thumbsSpacing));
                            opt.thumbRows = parseInt(noItems / opt.thumbCols);

                            if(opt.thumbCols === 0) {
                                opt.thumbCols = 1;
                            }

                            if(opt.thumbRows * opt.thumbCols < noItems) {
                                opt.thumbRows++;
                            }
                        } else {
                            if((opt.thumbRows * opt.thumbCols < noItems) && opt.thumbCols !== 0) {
                                if(noItems % opt.thumbCols === 0) {
                                    opt.thumbRows = noItems / opt.thumbCols;
                                } else {
                                    opt.thumbRows = parseInt(noItems / opt.thumbCols) + 1;
                                }
                            } else {
                                if(noItems % opt.thumbRows === 0) {
                                    opt.thumbCols = noItems / opt.thumbRows;
                                } else {
                                    opt.thumbCols = parseInt(noItems / opt.thumbRows) + 1;
                                }
                            }
                        }

                        $('.gmPhantom_thumbsWrapper > div', Container).each(function() {
                            no++;
                            col++;

                            $(this).attr('data-col', col).attr('data-row', row).css({'margin': ''});
                            if(no % opt.thumbCols === 0) {
                                col = 0;
                                row++;
                            }

                            var img = $('.gmPhantom_Thumb > img', this);
                            var thumb_ratio = $(this).data('ratio');
                            if(opt.ratio <= thumb_ratio) {
                              img.removeClass('portrait').addClass('landscape');
                            } else {
                              img.removeClass('landscape').addClass('portrait');
                                if(1 != thumb_ratio) {
                                    img.css('margin', Math.floor((opt.thumbWidth / thumb_ratio - opt.thumbHeight) / opt.thumbHeight * 10) + '% 0 0 0');
                                }
                            }

                        });
                        var thumbs_el = $('.gmPhantom_thumbsWrapper', Container),
                            thumbs_el_width = thumbW * opt.thumbCols + (opt.thumbCols - 1) * opt.thumbsSpacing,
                            scrollbar_width = 0;
                        thumbs_el.width(thumbs_el_width + opt.thumbsSpacing);
                        $('.gmPhantom_Container', Container).width(thumbs_el_width);
                        if(thumbs_el_width >= $(Container).width()) {
                            scrollbar_width = methods.scrollbarWidth();
                        }

                        if(opt.initialHeight !== 0) {
                            //var thumbH = opt.thumbHeight + opt.thumbBorderSize * 2 + opt.thumbPadding * 2,
                            //    thumbs_el_height = thumbH * opt.thumbRows + opt.thumbRows * opt.thumbsSpacing;
                            //$('.gmPhantom_thumbsWrapper', Container).height(thumbs_el_height + scrollbar_width);
                            $('.gmPhantom_Container', Container).height(thumbs_el.height() - opt.thumbsSpacing);

                            if(!prototypes.isTouchDevice()) {
                                if(opt.thumbsNavigation == 'scroll' && typeof(jQuery.fn.jScrollPane) != 'undefined') {
                                    $('.jspContainer', Container).width($('.gmPhantom_Container', Container).width());
                                }
                            }
                        }

                        prototypes.undoHideBuster(hiddenBustedItems);
                    },
                    moveThumbs: function() {// Init thumbnails move
                        var thumbs_el = $('.gmPhantom_thumbsWrapper', Container);
                        $('.gmPhantom_Container', Container).mousemove(function(e) {
                            if(itemLoaded) {
                                return;
                            }

                            var w1 = $(this).width(),
                                w2 = thumbs_el.outerWidth() - opt.thumbsSpacing,
                                h1 = $(this).height(),
                                h2 = thumbs_el.outerHeight() - opt.thumbsSpacing,
                                wdiff = w2 - w1,
                                hdiff = h2 - h1,
                                mpw, mph,
                                tx, ty;
                            if(wdiff < 0){
                                tx = 0;
                            } else {
                                mpw = e.clientX - 0.1*w1 - $(this).offset().left + parseInt($(this).css('margin-left')) + $(document).scrollLeft();
                                mpw = mpw / (w1 - w1 * 0.2);
                                if(mpw < 0) {
                                    mpw = 0;
                                } else if(mpw > 1) {
                                    mpw = 1;
                                }
                                tx = wdiff * mpw;
                            }
                            if(hdiff < 0){
                                ty = 0;
                            } else {
                                mph = e.clientY - 0.1*h1 - $(this).offset().top + parseInt($(this).css('margin-top')) + $(document).scrollTop();
                                mph = mph / (h1 - h1 * 0.2);
                                if(mph < 0) {
                                    mph = 0;
                                } else if(mph > 1) {
                                    mph = 1;
                                }
                                ty = hdiff * mph;
                            }

                            thumbs_el.css('transform', 'translate(-' + tx + 'px, -' + ty + 'px)');

                        });
                    },
                    initThumbsScroll: function() {//Init Thumbnails Scroll
                        if(typeof(jQuery.fn.jScrollPane) == 'undefined') {
                            $(Container).css('overflow', 'auto');
                        } else {
                            setTimeout(function() {
                                $(Container).jScrollPane({autoReinitialise: true});
                            }, 10);
                        }
                    },
                    scrollbarWidth: function() {
                        var div = $('<div style="position:absolute;left:-200px;top:-200px;width:50px;height:50px;overflow:scroll"><div>&nbsp;</div></div>').appendTo('body'),
                            width = 50 - div.children().innerWidth();
                        div.remove();
                        return width;
                    },
                    initTooltip: function() {// Init Tooltip
                        $(Container).on('mouseover mousemove', '.gmPhantom_ThumbContainer', function(e) {
                            var thumbs_container = $('.gmPhantom_Container', Container),
                                mousePositionX = e.clientX - $(thumbs_container).offset().left + $(document).scrollLeft(),
                                mousePositionY = e.clientY - $(thumbs_container).offset().top + $(document).scrollTop();

                            $('.gmPhantom_Tooltip', Container).css('left', mousePositionX - 10);
                            $('.gmPhantom_Tooltip', Container).css('top', mousePositionY - $('.gmPhantom_Tooltip', Container).height() - 15);
                        });
                    },
                    showTooltip: function(thumb) {// Resize, Position & Display the Tooltip
                        var title = $.trim($('.gmPhantom_title', thumb).text()),
                            HTML = [];
                        HTML.push(title);
                        HTML.push('<div class="gmPhantom_Tooltip_ArrowBorder"></div>');
                        HTML.push('<div class="gmPhantom_Tooltip_Arrow"></div>');
                        $('.gmPhantom_Tooltip', Container).html(HTML.join(""));
                        if(opt.tooltipBgColor != 'css') {
                            $('.gmPhantom_Tooltip', Container).css('background-color', '#' + opt.tooltipBgColor);
                            $('.gmPhantom_Tooltip_Arrow', Container).css('border-top-color', '#' + opt.tooltipBgColor);
                        }
                        if(opt.tooltipStrokeColor != 'css') {
                            $('.gmPhantom_Tooltip', Container).css('border-color', '#' + opt.tooltipStrokeColor);
                            $('.gmPhantom_Tooltip_ArrowBorder', Container).css('border-top-color', '#' + opt.tooltipStrokeColor);
                        }
                        if(opt.tooltipTextColor != 'css') {
                            $('.gmPhantom_Tooltip', Container).css('color', '#' + opt.tooltipTextColor);
                        }
                        if(title !== '') {
                            $('.gmPhantom_Tooltip', Container).css('display', 'block');
                        }
                    }
                },

                prototypes = {
                    isIEBrowser: function() {// Detect the browser IE
                        var myNav = navigator.userAgent.toLowerCase(),
                            msie = (myNav.indexOf('msie') == -1)? false : parseInt(myNav.split('msie')[1]);
                        msie = !msie? ((myNav.indexOf('rv:11') != -1)? 11 : false) : false;
                        return msie;
                    },
                    isTouchDevice: function() {// Detect Touchscreen devices
                        return 'ontouchend' in document;
                    },
                    touchNavigation: function(parent, child) {// One finger Navigation for touchscreen devices
                        var prevX, prevY, currX, currY, touch, moveTo, thumbsPositionX, thumbsPositionY,
                            thumbW = opt.thumbWidth + 2 * opt.thumbPadding + 2 * opt.thumbBorderSize,
                            thumbH = opt.thumbHeight + 2 * opt.thumbPadding + 2 * opt.thumbBorderSize;


                        parent.bind('touchstart', function(e) {
                            touch = e.originalEvent.touches[0];
                            prevX = touch.clientX;
                            prevY = touch.clientY;
                        });

                        parent.bind('touchmove', function(e) {
                            touch = e.originalEvent.touches[0];
                            currX = touch.clientX;
                            currY = touch.clientY;
                            thumbsPositionX = currX > prevX? parseInt(child.css('margin-left')) + (currX - prevX) : parseInt(child.css('margin-left')) - (prevX - currX);
                            thumbsPositionY = currY > prevY? parseInt(child.css('margin-top')) + (currY - prevY) : parseInt(child.css('margin-top')) - (prevY - currY);

                            if(thumbsPositionX < (-1) * (child.outerWidth() - parent.width())) {
                                thumbsPositionX = (-1) * (child.outerWidth() - parent.width());
                            }
                            else if(thumbsPositionX > 0) {
                                thumbsPositionX = 0;
                            }
                            else {
                                e.preventDefault();
                            }

                            if(thumbsPositionY < (-1) * (child.outerHeight() - parent.height())) {
                                thumbsPositionY = (-1) * (child.outerHeight() - parent.height());
                            }
                            else if(thumbsPositionY > 0) {
                                thumbsPositionY = 0;
                            }
                            else {
                                e.preventDefault();
                            }

                            prevX = currX;
                            prevY = currY;

                            if(parent.width() < child.outerWidth()) {
                                child.css('margin-left', thumbsPositionX);
                            }
                            if(parent.height() < child.outerHeight()) {
                                child.css('margin-top', thumbsPositionY);
                            }
                        });

                    },

                    openLink: function(url, target) {// Open a link.
                        switch(target.toLowerCase()) {
                            case '_blank':
                                window.open(url);
                                break;
                            case '_top':
                                top.location.href = url;
                                break;
                            case '_parent':
                                parent.location.href = url;
                                break;
                            default:
                                window.location = url;
                        }
                    },
                    arrVal: function(arr, val) {
                        return $.inArray(val, arr) !== -1? val : false;
                    },
                    $_GET: function(variable) {
                        var url = window.location.href.split('?')[1];
                        if(url) {
                            url = url.split('#')[0];
                            var variables = (typeof(url) === 'undefined')? [] : url.split('&'),
                                i;

                            for(i = 0; i < variables.length; i++) {
                                if(variables[i].indexOf(variable) != -1) {
                                    return variables[i].split('=')[1];
                                }
                            }
                        }

                        return false;
                    },
                    doHideBuster: function(item) {// Make all parents & current item visible
                        var parent = item.parent(),
                            items = [];

                        if(typeof(item.prop('tagName')) !== 'undefined' && item.prop('tagName').toLowerCase() != 'body') {
                            items = this.doHideBuster(parent);

                            item.addClass('gmShowBuster');
                            items.push(item);
                        }

                        return items;
                    },
                    undoHideBuster: function(items) {// Hide items in the array
                        var i;

                        for(i = 0; i < items.length; i++) {
                            items[i].removeClass('gmShowBuster');
                        }
                    }
                };

            return methods.init.apply(this, arguments);
        }
    })(jQuery, window, document);
}