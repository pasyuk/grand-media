/*
 * Title                   : gmPhantom
 * Version                 : 3.5
 * Copyright               : 2013-2015 CodEasily.com
 * Website                 : http://www.codeasily.com
 */
if(typeof jQuery.fn.gmPhantom == 'undefined') {
    (function($, window, document) {
        $.fn.gmPhantom = function(method) {
            var Container = this,
                elID = $(this).attr('id'),
                ID = '',
                Content,
                opt,
                timeout,

                opt_str = {
                    'thumbsNavigation': 'scroll', // Thumbnails Navigation (mouse, scroll). Default value: mouse. Set how you navigate through the thumbnails.
                    'thumbsAlign': 'left', // Thumbnails align. Default value: left.
                    'thumbsInfo': 'label', // Info Thumbnails Display (none, tooltip, label). Default value: tooltip. Display a small info text on the thumbnails, a tooltip or a label on bottom.
                    'mfp_css': '',
                    'module_dirurl': '',
                },
                opt_hex = {
                    'bgColor': 'ffffff', // Background Color (color hex code). Default value: ffffff. Set gallery background color.
                    'thumbBG': 'ffffff', // Thumbnail Border Color (color hex code). Default value: cccccc. Set the color of a thumbnail's border.
                    'thumbBorderColor': 'cccccc', // Thumbnail Border Color (color hex code). Default value: cccccc. Set the color of a thumbnail's border.
                    'tooltipTextColor': '0b0b0b', // Tooltip Text Color (color hex code). Default value: 000000. Set tooltip text color.
                    'tooltipBgColor': 'ffffff', // Tooltip Background Color (color hex code). Default value: ffffff. Set tooltip background color.
                    'tooltipStrokeColor': '000000', // Tooltip Stroke Color (color hex code). Default value: 000000. Set tooltip stroke color.
                    'labelTextColor': 'e7e7e7', //   Label-Over Text Color (color hex code). Default value: 000000.
                    'labelLinkColor': 'e7e179',
                    'label8TextColor': '0b0b0b', // Label Text Color (color hex code). Default value: 000000.
                    'label8LinkColor': '3695E7',
                    'lightboxControlsColor': 'ffffff', //   Tooltip Text Color (color hex code). Default value: 000000.
                    'lightboxTitleColor': 'f3f3f3', //   Tooltip Text Color (color hex code). Default value: 000000.
                    'lightboxTextColor': 'f3f3f3', //   Tooltip Text Color (color hex code). Default value: 000000.
                    'lightboxBGColor': '0b0b0b' // Lightbox Window Color (color hex code). Default value: 000000. Set the color for the lightbox window.
                },
                opt_int = {
                    'initRPdelay': 100,
                    'maxheight': 0,
                    'thumbCols': 0, // Number of Columns (auto, number). Default value: 0. Set the number of columns for the grid.
                    'thumbRows': 0, // Number of Lines (auto, number). Default value: 0. Set the number of lines for the grid.
                    'bgAlpha': 0, // Background Alpha (value from 0 to 100). Default value: 0. Set gallery background alpha.
                    'thumbWidth': 160, // Thumbnail Width (the size in pixels). Default value: 150. Set the width of a thumbnail.
                    'thumbHeight': 120, // Thumbnail Height (the size in pixels). Default value: 150. Set the height of a thumbnail.
                    'thumbWidthMobile': 96,
                    'thumbHeightMobile': 72,
                    'thumbsSpacing': 10, // Thumbnails Spacing (value in pixels). Default value: 10. Set the space between thumbnails.
                    'thumbsVerticalPadding': 5, // Thumbnails Padding Top (value in pixels). Default value: 5. Set the top padding for the thumbnails.
                    'thumbsHorizontalPadding': 3, // Thumbnails Padding Top (value in pixels). Default value: 5. Set the top padding for the thumbnails.
                    'thumbAlpha': 85, // Thumbnail Alpha (value from 0 to 100). Default value: 85. Set the transparancy of a thumbnail.
                    'thumbAlphaHover': 100, // Thumbnail Alpha Hover (value from 0 to 100). Default value: 100. Set the transparancy of a thumbnail when hover.
                    'thumbBorderSize': 1, // Thumbnail Border Size (value in pixels). Default value: 1. Set the size of a thumbnail's border.
                    'thumbPadding': 5, // Thumbnail Padding (value in pixels). Default value: 3. Set padding value of a thumbnail.
                    'lightboxBGAlpha': 80 // Lightbox Window Alpha (value from 0 to 100). Default value: 80. Set the transparancy for the lightbox window.
                },
                opt_bool = {
                    'deepLinks': true,
                    'socialShareEnabled': true, // Social Share Enabled (true, false). Default value: true.
                    'share_post_link': false,
                    'viewsEnabled': true,
                    'likesEnabled': true,
                    'commentsEnabled': true, // Comments Enabled (true, false). Default value: true.
                    'lightbox800HideArrows': false, // Hide Arrows if window width less than 800px.
                    'thumbScale': true, // Scale effect for thumb on mouseover
                    'labelOnHover': true, // Show thumb label only on mouseover
                    'thumb2link': true // Open link instead of lightbox when item have "link" attr
                },

                IDs = [],
                Post_IDs = [],
                Types = [],
                Extentions = [],
                Sources = [],
                Images = [],
                Thumbs = [],
                CaptionTitle = [],
                CaptionText = [],
                CommentsCount = [],
                ViewsCount = [],
                LikesCount = [],
                PostLinks = [],
                Links = [],
                LinksTarget = [],
                noItems = 0,

                magItems = {},
                Storage = {},

                startGalleryID = 0,
                startWith = 0,

                scrollTop = 0,
                scrollLeft = 0,
                itemLoaded = 0,
                thumbsNavigationArrowsSpeed = 200,
                cc = 0,

                methods = {
                    init: function(arguments) {// Init Plugin.
                        opt = $.extend(true, {}, opt_str, opt_int, opt_bool, opt_hex, arguments[1]);
                        $.each(opt, function(key, val) {
                            if(key in opt_bool) {
                                opt[key] = (!(!val || val == '0' || val == 'false'));
                            } else if(key in opt_int) {
                                opt[key] = parseInt(val);
                            }
                        });
                        ID = opt.ID;
                        opt.initialHeight = opt.maxheight;
                        opt.initialCols = opt.thumbCols;
                        opt.initialRows = opt.thumbRows;
                        opt.thumbWidthDesktop = opt.thumbWidth;
                        opt.thumbHeightDesktop = opt.thumbHeight;
                        opt.dratio = opt.thumbWidthDesktop / opt.thumbHeightDesktop;
                        opt.mratio = opt.thumbWidthMobile / opt.thumbHeightMobile;

                        Content = arguments[0];
                        methods.parseContent();

                        $(window).bind('resize.gmPhantom', methods.initRP);

                        setTimeout(methods.initRP, opt.initRPdelay);
                    },
                    parseContent: function() {// Parse Content.
                        $.each(Content, function(index) {
                            $.each(Content[index], function(key) {
                                switch(key) {
                                    case 'id':
                                        IDs.push(Content[index][key]);
                                        break;
                                    case 'post_id':
                                        Post_IDs.push(Content[index][key]);
                                        break;
                                    case 'type':
                                        Types.push(Content[index][key]);
                                        break;
                                    case 'ext':
                                        Extentions.push(Content[index][key]);
                                        break;
                                    case 'src':
                                        Sources.push(GmediaGallery.upload_dirurl + Content[index][key]);
                                        break;
                                    case 'image':
                                        Images.push(Content[index][key]);
                                        break;
                                    case 'thumb':
                                        Thumbs.push(Content[index][key]);
                                        break;
                                    case 'title':
                                        CaptionTitle.push(Content[index][key]);
                                        break;
                                    case 'text':
                                        CaptionText.push(Content[index][key]);
                                        break;
                                    case 'cc':
                                        CommentsCount.push(Content[index][key]);
                                        break;
                                    case 'views':
                                        ViewsCount.push(Content[index][key]);
                                        break;
                                    case 'likes':
                                        LikesCount.push(Content[index][key]);
                                        break;
                                    case 'post_link':
                                        PostLinks.push(Content[index][key]);
                                        break;
                                    case 'link':
                                        Links.push(Content[index][key]);
                                        break;
                                    case 'linkTarget':
                                        if(Content[index][key] === '') {
                                            LinksTarget.push('_self');
                                        }
                                        else {
                                            LinksTarget.push(Content[index][key]);
                                        }
                                        break;
                                }
                            });
                        });

                        noItems = Thumbs.length;
                        methods.rpResponsive();

                        methods.initGallery();

                    },
                    initGallery: function() {// Init the Gallery

                        var currentScrollPosition = 0;
                        $(document).scroll(function(){
                            currentScrollPosition = $(this).scrollTop();
                        });
                        window.onhashchange = function() {
                            methods.loadGalleryDeepLink();
                        };

                        $("input, textarea").focus(function(){
                            $(document).scrollTop(currentScrollPosition);
                        });

                        var browser_class = '';
                        if (prototypes.isIEBrowser()) {
                            if (prototypes.isIEBrowser() < 8) {
                                browser_class += ' msie msie7';
                            } else {
                                browser_class += ' msie';
                            }
                        }
                        if (prototypes.isTouchDevice()) {
                            browser_class += ' istouch';
                        }

                        $('.gmPhantom_thumbsWrapper', Container).addClass(browser_class);
                        if (opt.thumbsInfo == 'tooltip' && !prototypes.isTouchDevice()) {
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
                                    var no = parseInt(item.el.data('no'));
                                    item.no = no;
                                    item.gm_id = IDs[no];
                                    item.gm_type = Types[no];
                                    item.gm_ext = Extentions[no];
                                    if('image' == item.gm_type) {
                                        item.src = Images[no];
                                    } else {
                                        item.src = Sources[no];
                                    }
                                    item.title = CaptionTitle[no];
                                    item.description = CaptionText[no];
                                    item.gm_link = Links[no];
                                    item.gm_link_target = LinksTarget[no];

                                    if(opt.viewsEnabled || opt.likesEnabled) {
                                        item.views = ViewsCount[no];
                                        if(opt.likesEnabled) {
                                            item.likes = LikesCount[no];
                                        }
                                        if(!Storage[item.gm_id]){
                                            Storage[item.gm_id] = {}
                                        }
                                        if (Storage[item.gm_id].status) {
                                            item.viewed  = true;
                                            if(opt.likesEnabled) {
                                                if('liked' == Storage[item.gm_id].status) {
                                                    item.liked = true;
                                                } else {
                                                    item.liked = false;
                                                }
                                            }
                                        }
                                    }

                                    if(typeof(magItems[item.index]) == 'undefined') {
                                        magItems[item.index] = item;
                                    }
                                },
                                markupParse: function(template, values, item) {
                                    if(item.gm_link) {
                                        values.title = '<a href="' + item.gm_link + '" target="' + item.gm_link_target + '">' + item.title + '</a>';
                                    } else {
                                        values.title = item.title;

                                    }
                                    values.description = item.description;
                                },

                                imageLoadComplete: function() {
                                    var self = this;
                                    setTimeout(function() {
                                        self.wrap.addClass('mfp-image-loaded');
                                    }, 16);
                                },
                                open: function() {
                                    $(document.body).addClass('mfp-gmedia-open');
                                    itemLoaded = 1;
                                    if(opt.commentsEnabled){
                                        this.wrap.on('click.gmCloseComments', '.mfp-img--comments-div', function() {
                                            $('#mfp_comments_' + ID, this.wrap).trigger('click');
                                            return false;
                                        });
                                    }
                                },
                                close: function() {
                                    $(document.body).removeClass('mfp-gmedia-open');
                                    if('image' != magItems[this.currItem.index].gm_type) {
                                        $(document.body).removeClass('mfp-zoom-out-cur');
                                        this.wrap.removeClass('mfp-iframe-loaded');
                                    }
                                    this.wrap.removeClass('mfp-image-loaded');

                                    if(opt.commentsEnabled){
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
                                    this.contentContainer.attr('data-gmtype', magItems[this.currItem.index].gm_type).attr('data-ext', magItems[this.currItem.index].gm_ext);
                                    $('#wpadminbar').css({'z-index':''});
                                    itemLoaded = 0;
                                    $(window).scrollTop(scrollTop);
                                },
                                beforeOpen: function() {
                                    $('#wpadminbar').css({'z-index':1000});
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
                                        var hash = "#!gallery-" + ID + '-' + magItems[this.currItem.index].gm_id,
                                            url = ('' + window.location).split('#')[0] + hash;
                                        if(!!(window.history && window.history.replaceState)) {
                                            window.history.replaceState({}, document.title, url);
                                        } else {
                                            location.replace(url);
                                        }
                                    }
                                    this.contentContainer.attr('data-gmtype', magItems[this.currItem.index].gm_type).attr('data-ext', magItems[this.currItem.index].gm_ext);
                                    if(opt.commentsEnabled) {
                                        this.wrap.removeClass('mfp-comments-open mfp-comments-loaded');
                                        $('.mfp-comments-wrapper', this.contentContainer).css({height: ''}).empty();
                                    }
                                    if(opt.likesEnabled) {
                                        this.wrap.removeClass('phantom-gmedia-liked');
                                    }
                                    clearTimeout(timeout);
                                    if('image' != magItems[this.currItem.index].gm_type) {
                                        $(document.body).addClass('mfp-zoom-out-cur');
                                        var self = this;
                                        setTimeout(function() {
                                            self.wrap.addClass('mfp-iframe-loaded');
                                        }, 16);
                                    }
                                },
                                afterChange: function() {
                                    if(opt.socialShareEnabled) {
                                        methods.initSocialShare(magItems[this.currItem.index].no, this);
                                    }
                                    if(opt.viewsEnabled || opt.likesEnabled) {
                                        var self = this;
                                        timeout = setTimeout(function() {
                                            methods.viewLike(self.currItem);
                                        }, 1000);

                                        if(opt.viewsEnabled) {
                                            methods.initViews(magItems[this.currItem.index].no, this);
                                        }
                                        if(opt.likesEnabled) {
                                            methods.initLikes(magItems[this.currItem.index].no, this);
                                            if (Storage[magItems[this.currItem.index].gm_id] && Storage[magItems[this.currItem.index].gm_id].status && 'liked' == Storage[magItems[this.currItem.index].gm_id].status) {
                                                this.wrap.addClass('phantom-gmedia-liked');
                                            } else {
                                                this.wrap.removeClass('phantom-gmedia-liked');
                                            }
                                        }
                                    }
                                    if(opt.commentsEnabled) {
                                        methods.initComments(magItems[this.currItem.index].no, this);
                                    }
                                },
                                updateStatus: function(data) {
                                    //console.log(data);
                                    //if(data.status == 'ready') {}
                                }
                            },
                            image: {
                                markup: ''+
                                '<div class="mfp-figure">' +
                                '   <div class="mfp-close"></div>' +
                                '   <figure>' +
                                '       <div class="mfp-img"></div>' +
                                '       <figcaption>' +
                                '           <div class="mfp-bottom-bar">' +
                                '               <div class="mfp-title"></div>' +
                                '               <div class="mfp-description"></div>' +
                                '               <div class="mfp-counter"></div>' +
                                '           </div>' +
                                '       </figcaption>' +
                                '   </figure>' +
                                '</div>' +
                                '<div class="mfp-comments-container">' +
                                '   <div class="mfp-comments-content"><div class="mfp-comments-wrapper"></div></div>' +
                                '</div>' +
                                '<div class="mfp-prevent-close mfp-buttons-bar"></div>'
                            },
                            iframe: {
                                markup: ''+
                                '<div class="mfp-iframe-wrapper">' +
                                '   <div class="mfp-close"></div>' +
                                '   <div class="mfp-iframe-scaler">' +
                                '       <iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowtransparency="true" allowfullscreen></iframe>' +
                                '   </div>' +
                                '   <div class="mfp-bottom-bar gm-iframe-bottom-bar">' +
                                '       <div class="mfp-title"></div>' +
                                '       <div class="mfp-description"></div>' +
                                '       <div class="mfp-counter"></div>' +
                                '   </div>' +
                                '</div>' +
                                '<div class="mfp-comments-container">' +
                                '   <div class="mfp-comments-content"><div class="mfp-comments-wrapper"></div></div>' +
                                '</div>' +
                                '<div class="mfp-prevent-close mfp-buttons-bar"></div>'
                            },
                            gallery: {
                                enabled: true,
                                arrowMarkup: '<div title="%title%" type="button" class="mfp-button mfp-arrow mfp-arrow-%dir%"></div>',
                                tCounter: '%curr% / %total%'
                            },

                            mainClass: 'mfp-zoom-in',
                            removalDelay: 500, //delay removal by X to allow out-animation

                        });

                        methods.loadGalleryDeepLink();

                    },
                    loadGalleryDeepLink: function() {
                        var prefix = "#!gallery-";
                        var h = location.hash;
                        if(h.indexOf(prefix) === 0) {
                            h = h.substr(prefix.length).split('-');
                            if(h[0] && parseInt(h[0]) == ID) {
                                $(document).scrollTop($(Container).offset().top);
                                if(h[1]) {
                                    startWith = IDs.indexOf(h[1]);
                                } else {
                                    startWith = 0;
                                }
                                if(-1 !== startWith) {
                                    $('.gmPhantom_thumbsWrapper', Container).magnificPopup("open", startWith);
                                }
                            }
                        }
                    },
                    initSocialShare: function(no, mfp) {
                        var share_buttons = ''+
                            '<div class="mfp-prevent-close mfp-button mfp-share mfp-gmedia-stuff08" id="mfp_share_' + ID + '">'+
                            '     <a class="mfp-prevent-close" title="Share">'+
                            '         <span class="mfp-prevent-click">'+
                            '             <svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="icon-share2" viewBox="0 0 1024 1024"><path class="path1" d="M864 704c-45.16 0-85.92 18.738-115.012 48.83l-431.004-215.502c1.314-8.252 2.016-16.706 2.016-25.328s-0.702-17.076-2.016-25.326l431.004-215.502c29.092 30.090 69.852 48.828 115.012 48.828 88.366 0 160-71.634 160-160s-71.634-160-160-160-160 71.634-160 160c0 8.622 0.704 17.076 2.016 25.326l-431.004 215.504c-29.092-30.090-69.852-48.83-115.012-48.83-88.366 0-160 71.636-160 160 0 88.368 71.634 160 160 160 45.16 0 85.92-18.738 115.012-48.828l431.004 215.502c-1.312 8.25-2.016 16.704-2.016 25.326 0 88.368 71.634 160 160 160s160-71.632 160-160c0-88.364-71.634-160-160-160z"></path></symbol></svg>'+
                            '             <svg class="gmPhantom_svgicon"><use xlink:href="#icon-share2"/></svg>'+
                            '         </span>'+
                            '     </a>'+
                            '     <ul class="mfp-prevent-close mfp-share_sharelizers">'+
                            '         <li><a class="mfp-prevent-close mfp-share_facebook mfp-share_sharelizer">Facebook</a></li>'+
                            '         <li><a class="mfp-prevent-close mfp-share_twitter mfp-share_sharelizer">Twitter</a></li>'+
                            '         <li><a class="mfp-prevent-close mfp-share_pinterest mfp-share_sharelizer">Pinterest</a></li>'+
                            '         <li><a class="mfp-prevent-close mfp-share_google mfp-share_sharelizer">Google+</a></li>'+
                            '         <li><a class="mfp-prevent-close mfp-share_stumbleupon mfp-share_sharelizer">StumbleUpon</a></li>'+
                            '     </ul>'+
                            '</div>';

                        var mfp_share = $(share_buttons);
                        mfp_share.on('click', function(){
                            $(this).toggleClass('mfp-share_open');
                        });

                        $('.mfp-share_sharelizer', mfp_share).on('click', function () {
                            var sharelink,
                                title = CaptionTitle[no],
                                imgsrc = Images[no],
                                url = window.location.href;
                            if(!opt.deepLinks) {
                                var hash = "#!gallery-" + ID + '-' + IDs[no];
                                url = ('' + window.location).split('#')[0] + hash;
                            }
                            if(opt.share_post_link){
                                url = PostLinks[no];
                            }
                            if ($(this).hasClass('mfp-share_twitter')) {
                                sharelink = 'https://twitter.com/home?status=' + encodeURIComponent(title + ' ' + url);
                                window.open(sharelink, '_blank');
                            }
                            if ($(this).hasClass('mfp-share_facebook')) {
                                sharelink = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
                                window.open(sharelink, '_blank');
                            }
                            if ($(this).hasClass('mfp-share_pinterest')) {
                                sharelink = 'https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(url) + '&media=' + encodeURIComponent(imgsrc) + '&description=' + encodeURIComponent(title);
                                window.open(sharelink, '_blank');
                            }
                            if ($(this).hasClass('mfp-share_google')) {
                                sharelink = 'https://plus.google.com/share?url=' + encodeURIComponent(url);
                                window.open(sharelink, '_blank');
                            }
                            if ($(this).hasClass('mfp-share_stumbleupon')) {
                                sharelink = 'http://www.stumbleupon.com/submit?url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent(title);
                                window.open(sharelink, '_blank');
                            }
                        });

                        var share_div = mfp.wrap.find('#mfp_share_' + ID);
                        if(!share_div.length) {
                            $('.mfp-buttons-bar', mfp.wrap).append(mfp_share);
                        } else {
                            $(share_div).replaceWith(mfp_share);
                        }
                    },
                    initComments: function(no, mfp) {
                        var comments_button = ''+
                            '<div class="mfp-prevent-close mfp-button mfp-comments" id="mfp_comments_' + ID + '">'+
                            '   <a class="mfp-prevent-close" title="Comments"><span class="mfp-prevent-close mfp-comments-count">' + CommentsCount[no] + '</span>'+
                            '      <span class="mfp-prevent-click mfp_comments_icon">'+
                            '          <svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="icon-bubbles2" viewBox="0 0 1152 1024"><path class="path1" d="M480 0v0c265.096 0 480 173.914 480 388.448s-214.904 388.448-480 388.448c-25.458 0-50.446-1.62-74.834-4.71-103.106 102.694-222.172 121.108-341.166 123.814v-25.134c64.252-31.354 116-88.466 116-153.734 0-9.106-0.712-18.048-2.030-26.794-108.558-71.214-177.97-179.988-177.97-301.89 0-214.534 214.904-388.448 480-388.448zM996 870.686c0 55.942 36.314 104.898 92 131.772v21.542c-103.126-2.318-197.786-18.102-287.142-106.126-21.14 2.65-42.794 4.040-64.858 4.040-95.47 0-183.408-25.758-253.614-69.040 144.674-0.506 281.26-46.854 384.834-130.672 52.208-42.252 93.394-91.826 122.414-147.348 30.766-58.866 46.366-121.582 46.366-186.406 0-10.448-0.45-20.836-1.258-31.168 72.57 59.934 117.258 141.622 117.258 231.676 0 104.488-60.158 197.722-154.24 258.764-1.142 7.496-1.76 15.16-1.76 22.966z"></path></symbol></svg>'+
                            '          <svg class="gmPhantom_svgicon"><use xlink:href="#icon-bubbles2"/></svg>'+
                            '      </span>'+
                            '   </a>'+
                            '</div>';
                        var mfp_comments = $(comments_button);
                        mfp_comments.on('click', function(){
                            var comments_wrapper = $('.mfp-comments-wrapper', mfp.contentContainer);
                            if(mfp.wrap.hasClass('mfp-comments-open')) {
                                comments_wrapper.css({height: ''});
                                $('figure > img', mfp.wrap).removeClass('mfp-img--comments-div').addClass('mfp-img');
                                mfp.wrap.removeClass('mfp-comments-open');
                                if($(window).width() <= 800) {
                                    $('body').delay(200).animate({scrollTop: $('#mfp_gm_' + ID).offset().top}, 400);
                                } else {
                                    $(window).scrollTop($('#mfp_gm_' + ID).offset().top);
                                }
                            } else {
                                comments_wrapper.css({height: $('iframe', comments_wrapper).height()});
                                $('figure > img', mfp.wrap).removeClass('mfp-img').addClass('mfp-img--comments-div');
                                mfp.wrap.addClass('mfp-comments-open')
                                if(!mfp.wrap.hasClass('mfp-comments-loaded')) {
                                    methods.loadComments(no, mfp);
                                }
                                if($(window).width() <= 800){
                                    $('body').delay(200).animate({scrollTop: ($('.mfp-comments-container', '#mfp_gm_'+ID).offset().top - 250)}, 400);
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
                    initLikes: function(no, mfp) {
                        if(mfp.currItem.liked){
                            mfp.wrap.addClass('phantom-gmedia-liked');
                        }

                        var likes = ''+
                            '<div class="mfp-prevent-close mfp-button mfp-likes" id="mfp_likes_' + ID + '">'+
                            '   <a class="mfp-prevent-close"><span class="mfp-prevent-close mfp-likes-count">' + LikesCount[no] + '</span>'+
                            '      <span class="mfp-prevent-click mfp_likes_icon">'+
                            '          <svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="icon-heart" viewBox="0 0 1024 1024"><path class="path1" d="M755.188 64c-107.63 0-200.258 87.554-243.164 179-42.938-91.444-135.578-179-243.216-179-148.382 0-268.808 120.44-268.808 268.832 0 301.846 304.5 380.994 512.022 679.418 196.154-296.576 511.978-387.206 511.978-679.418 0-148.392-120.43-268.832-268.812-268.832z"></path></symbol></svg>'+
                            '          <svg class="gmPhantom_svgicon"><use xlink:href="#icon-heart"/></svg>'+
                            '      </span>'+
                            '   </a>'+
                            '</div>';
                        var likes_obj = $(likes);
                        likes_obj.on('click', function(){
                            methods.viewLike(mfp.currItem, true);
                            mfp.wrap.addClass('phantom-gmedia-liked');
                            $('.mfp-likes-count', this).text(LikesCount[no]);
                        });

                        var likes_div = $(mfp.wrap).find('#mfp_likes_' + ID);
                        if(!likes_div.length) {
                            $('.mfp-buttons-bar', mfp.wrap).append(likes_obj);
                        } else {
                            $(likes_div).replaceWith(likes_obj);
                        }

                    },
                    initViews: function(no, mfp) {
                        var views = ''+
                            '<div class="mfp-prevent-close mfp-button mfp-views" id="mfp_views_' + ID + '">'+
                            '   <span class="mfp-prevent-close mfp-prevent-click"><span class="mfp-prevent-close mfp-views-count">' + ViewsCount[no] + '</span>'+
                            '   <span class="mfp-prevent-close mfp-prevent-click mfp_views_icon">'+
                            '      <svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="icon-eye" viewBox="0 0 1024 1024"><path class="path1" d="M512 192c-223.318 0-416.882 130.042-512 320 95.118 189.958 288.682 320 512 320 223.312 0 416.876-130.042 512-320-95.116-189.958-288.688-320-512-320zM764.45 361.704c60.162 38.374 111.142 89.774 149.434 150.296-38.292 60.522-89.274 111.922-149.436 150.296-75.594 48.218-162.89 73.704-252.448 73.704-89.56 0-176.858-25.486-252.452-73.704-60.158-38.372-111.138-89.772-149.432-150.296 38.292-60.524 89.274-111.924 149.434-150.296 3.918-2.5 7.876-4.922 11.86-7.3-9.96 27.328-15.41 56.822-15.41 87.596 0 141.382 114.616 256 256 256 141.382 0 256-114.618 256-256 0-30.774-5.452-60.268-15.408-87.598 3.978 2.378 7.938 4.802 11.858 7.302v0zM512 416c0 53.020-42.98 96-96 96s-96-42.98-96-96 42.98-96 96-96 96 42.982 96 96z"></path></symbol></svg>'+
                            '      <svg class="gmPhantom_svgicon"><use xlink:href="#icon-eye"/></svg>'+
                            '   </span></span>'+
                            '</div>';

                        var views_div = $(mfp.wrap).find('#mfp_views_' + ID);
                        if(!views_div.length) {
                            $('.mfp-buttons-bar', mfp.wrap).append(views);
                        } else {
                            $(views_div).replaceWith(views);
                        }

                    },
                    loadComments: function(no, mfp){
                        var comments_content = $('.mfp-comments-content', mfp.contentContainer),
                            comments_wrapper = $('.mfp-comments-wrapper', comments_content);
                        comments_wrapper.empty();
                        mfp.wrap.removeClass('mfp-comments-loaded').addClass('mfp-comments-loading');
                        if (GmediaGallery) {
                            opt.ajax_actions['comments']['data']['post_id'] = Post_IDs[no];
                            $.ajax({
                                type : "post",
                                dataType : "json",
                                url: GmediaGallery.ajaxurl,
                                data: {action: opt.ajax_actions['comments']['action'], _ajax_nonce: GmediaGallery.nonce, data: opt.ajax_actions['comments']['data']},
                            }).done(function(data){
                                if(data.comments_count){
                                    mfp.wrap.find('.mfp-comments-count').html(data.comments_count);
                                    CommentsCount[no] = data.comments_count
                                }
                                if(data.content) {
                                    $('.mfp-comments-wrapper', comments_content).html(data.content).find('iframe').on('load', function() {
                                        mfp.wrap.removeClass('mfp-comments-loading').addClass('mfp-comments-loaded');
                                        var body = this.contentWindow.document.body, html = this.contentWindow.document.documentElement;
                                        var height = Math.max( body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight );
                                        $(this).css({height: 20 + height, overflowY: 'hidden'}).parent().css({height: 20 + height});
                                        this.contentWindow.onbeforeunload = function() {
                                            mfp.wrap.removeClass('mfp-comments-loaded').addClass('mfp-comments-loading');
                                        };
                                        $.ajax({
                                            type : "post",
                                            dataType : "json",
                                            url: GmediaGallery.ajaxurl,
                                            data: {action: opt.ajax_actions['comments']['action'], _ajax_nonce: GmediaGallery.nonce, data: opt.ajax_actions['comments']['data']},
                                        }).done(function(data){
                                            if(data.comments_count){
                                                mfp.wrap.find('.mfp-comments-count').html(data.comments_count);
                                                CommentsCount[no] = data.comments_count
                                            }
                                        });
                                    });
                                }
                            }).fail(function(){
                                mfp.wrap.removeClass('mfp-comments-loading');
                            });
                        }
                    },
                    viewLike: function (item, like) {
                        var id = item.gm_id;
                        if(!Storage[id]){
                            Storage[id] = {}
                        }
                        if (Storage[id].status) {
                            item.viewed  = true;
                            if(opt.likesEnabled) {
                                if('liked' == Storage[id].status) {
                                    item.liked = true;
                                }
                            }
                        }
                        if (!item.viewed) {
                            item.viewed = true;
                            ViewsCount[item.no] += 1;
                            Storage[id].status = 'viewed';
                            sessionStorage.setItem(elID, JSON.stringify(Storage));
                            if (GmediaGallery.ajaxurl) {
                                $.ajax({
                                    type : "post",
                                    dataType : "json",
                                    url: GmediaGallery.ajaxurl,
                                    data: {action: 'gmedia_module_interaction', hit: id}
                                }).done(function(r){
                                    if(r.views){
                                        item.views = ViewsCount[item.no] = r.views;
                                    }
                                });
                            }
                        }
                        if (opt.likesEnabled && like && !item.liked) {
                            item.liked = true;
                            item.likes += 1;
                            LikesCount[item.no] = item.likes;
                            Storage[id].status = 'liked';
                            sessionStorage.setItem(elID, JSON.stringify(Storage));
                            if (GmediaGallery.ajaxurl) {
                                $.ajax({
                                    type : "post",
                                    dataType : "json",
                                    url: GmediaGallery.ajaxurl,
                                    data: {action: 'gmedia_module_interaction', hit: id, vote: 1}
                                }).done(function(r){
                                    if(r.likes){
                                        item.likes = LikesCount[item.no] = r.likes;
                                    }
                                });
                            }
                        }
                    },
                    initSettings: function() {// Init Settings
                        if (window.sessionStorage) {
                            Storage = sessionStorage.getItem(elID);
                            if (Storage) {
                                Storage = JSON.parse(Storage);
                            } else {
                                Storage = {};
                            }
                        }
                        methods.initContainer();
                        methods.initThumbs();
                        if(opt.thumbsInfo == 'tooltip' && !prototypes.isTouchDevice()) {
                            methods.initTooltip();
                        }

                    },
                    initRP: function() {// Init Resize & Positioning
                        var rrr = methods.rpResponsive();
                        if(!rrr && 50 > cc) {
                            cc++;
                            clearTimeout(timeout);
                            timeout = setTimeout(function() {
                                methods.initRP();
                            }, 100);
                        } else {
                            cc = 0;
                            methods.rpContainer();
                            methods.rpThumbs();
                        }
                    },
                    rpResponsive: function() {
                        var hiddenBustedItems = prototypes.doHideBuster($(Container));

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

                        setTimeout(function(){ opt.width = $(Container).width(); }, 0);
                        prototypes.undoHideBuster(hiddenBustedItems);

                        return opt.width;
                    },

                    initContainer: function() {// Init Container
                        $('.gmPhantom_Container', Container).removeClass('delay');
                        $('.gmPhantom_Container', Container).css({'display': 'block', 'text-align': opt.thumbsAlign});

                        if(opt.maxheight === 0) {
                            $('.gmPhantom_Container', Container).css('overflow', 'visible');
                        }
                        $('.gmPhantom_Background', Container).css('opacity', opt.bgAlpha / 100);
                        if(opt.bgAlpha !== 0) {
                            $('.gmPhantom_Background', Container).css('background-color', '#' + opt.bgColor);
                        }
                        $('.gmPhantom_thumbsWrapper', Container).css({
                            'padding-top': opt.thumbsVerticalPadding,
                            'padding-bottom': opt.thumbsVerticalPadding,
                            'padding-left': opt.thumbsHorizontalPadding,
                            'padding-right': opt.thumbsHorizontalPadding
                        });
                        if(opt.thumbsAlign == 'left') {
                            $('.gmPhantom_thumbsWrapper', Container).css({'margin-left': 0});
                        } else if(opt.thumbsAlign == 'right') {
                            $('.gmPhantom_thumbsWrapper', Container).css({'margin-right': 0});
                        }
                        methods.rpContainer();
                    },
                    rpContainer: function() {// Resize & Position Container
                        $('.gmPhantom_Container', Container).width(opt.width);
                        if(opt.maxheight === 0) {
                            $('.gmPhantom_Container', Container).css('height', 'auto');
                            $('.gmPhantom_thumbsWrapper', Container).css('height', 'auto');
                        } else {
                            $('.gmPhantom_Container', Container).css({height: 'auto', 'max-height': opt.maxheight});
                        }
                    },

                    initThumbs: function() {//Init Thumbnails
                        if(opt.maxheight === 0) {
                            $('.gmPhantom_thumbsWrapper', Container).css({'overflow': 'visible', 'position': 'relative'});
                        }

                        var thumb_container = $('.gmPhantom_ThumbContainer', Container);
                        if(opt.thumbsInfo == 'tooltip') {
                            if(!prototypes.isTouchDevice()) {
                                thumb_container.hover(function() {
                                            var no = $(this).data('no');
                                            methods.showTooltip(no);
                                        },
                                        function() {
                                            $('.gmPhantom_Tooltip', Container).css('display', 'none');
                                        });
                            } else {
                                $('.gmPhantom_thumbsWrapper', Container).removeClass('gmPhantom_LabelTolltip').addClass('gmPhantom_LabelInside');
                            }
                        }

                        thumb_container.click(function(e) {
                            var no = $(this).data('no');
                            if(Links[no] !== '' && opt.thumb2link) {
                                e.stopPropagation();
                                prototypes.openLink(Links[no], LinksTarget[no]);
                            }
                        });
                        $('.gmPhantom_Thumb', thumb_container).click(function(e) {
                            e.stopPropagation();
                            e.preventDefault();
                            $(this).parent().trigger('click');
                            return false;
                        });
                        $('.gmPhantom_ThumbLabel a', thumb_container).click(function(e) {
                            e.stopPropagation();
                        });

                        $('.gmPhantom_Thumb img', Container).each(function() {
                            var image = $(this);
                            var img_holder = image.closest('.gmPhantom_ThumbContainer');
                            image.css('opacity', 0);
                            var load_img = new Image();
                            $(load_img).load(function() {
                                img_holder.removeClass('gmPhantom_ThumbLoader');
                                image.animate({'opacity': opt.thumbAlpha}, 600, function(){
                                    $(this).css({'opacity':''});
                                });
                            }).attr('src', image.attr('src'));
                        });

                        if(opt.maxheight !== 0) {
                            if(prototypes.isTouchDevice()) {
                                prototypes.touchNavigation($('.gmPhantom_Container', Container), $('.gmPhantom_thumbsWrapper', Container));
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
                            hiddenBustedItems = prototypes.doHideBuster($(Container));
                        if(opt.initialHeight === 0 || (opt.initialCols === 0 && opt.initialRows === 0)) {
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

                        var thumbs_spacing = opt.thumbsSpacing;
                        if(0 == thumbs_spacing){
                            thumbs_spacing = -opt.thumbBorderSize;

                      }
                        $('.gmPhantom_ThumbContainer', Container).each(function() {
                            no++;

                            $(this).css({'margin': ''});
                            if(thumbs_spacing) {
                                if(no > opt.thumbCols) {
                                    $(this).css('margin-top', thumbs_spacing);
                                }
                                if(no % opt.thumbCols != 1 && opt.thumbCols != 1) {
                                    $(this).css('margin-left', thumbs_spacing);
                                }
                            }
                            if(no % opt.thumbCols === 0) {
                                $(this).css('margin-right', '-1px');
                            }

                            var img = $('.gmPhantom_Thumb > img', this);
                            var thumb_ratio = $(this).data('ratio');
                            if(opt.ratio < thumb_ratio){
                                img.attr('class', 'landscape').attr('style', 'margin:0 0 0 -' + Math.floor((opt.thumbHeight * thumb_ratio - opt.thumbWidth) / opt.thumbWidth * 50) + '%');
                            } else {
                                img.attr('class', 'portrait').attr('style', 'margin:-' + Math.floor((opt.thumbWidth / thumb_ratio - opt.thumbHeight) / opt.thumbHeight * 25) + '% 0 0 0');
                            }

                        });
                        var thumbs_el = $('.gmPhantom_thumbsWrapper', Container),
                            thumbs_el_width = thumbW * opt.thumbCols + (opt.thumbCols - 1) * opt.thumbsSpacing,
                            scrollbar_width = 0;
                        thumbs_el.width(thumbs_el_width);
                        if(thumbs_el_width >= $('.gmPhantom_Container', Container).width()) {
                            scrollbar_width = methods.scrollbarWidth();
                        }

                        if(opt.initialHeight !== 0) {
                            var thumbH = opt.thumbHeight + opt.thumbBorderSize * 2 + opt.thumbPadding * 2,
                                thumbs_el_height = thumbH * opt.thumbRows + (opt.thumbRows - 1) * opt.thumbsSpacing;
                            $('.gmPhantom_thumbsWrapper', Container).height(thumbs_el_height + scrollbar_width);

                            if((opt.thumbsNavigation == 'mouse')) {
                                if($('.gmPhantom_Container', Container).width() > thumbs_el.outerWidth()) {
                                    thumbs_el.css('margin-left', ($('.gmPhantom_Container', Container).width() - thumbs_el.outerWidth()) / 2);
                                } else {
                                    thumbs_el.css('margin-left', 0);
                                }
                                thumbs_el.css('margin-top', 0);
                            }

                            if(opt.thumbsNavigation == 'scroll' && typeof(jQuery.fn.jScrollPane) != 'undefined') {
                                $('.gmPhantom_Container .jspContainer', Container).width($('.gmPhantom_thumbsWrapper', Container).width());
                            }
                        }

                        methods.rpContainer();

                        prototypes.undoHideBuster(hiddenBustedItems);
                    },
                    moveThumbs: function() {// Init thumbnails move
                        var thumbs_el = $('.gmPhantom_thumbsWrapper', Container);
                        $('.gmPhantom_Container', Container).mousemove(function(e) {
                            if(itemLoaded) {
                                return;
                            }
                            var thumbW, thumbH, mousePosition, thumbsPosition;

                            if(thumbs_el.outerWidth() > $(this).width()) {
                                thumbW = opt.thumbWidth + opt.thumbBorderSize * 2 + opt.thumbPadding * 2 + opt.thumbsSpacing - opt.thumbsSpacing / opt.thumbRows + opt.thumbsHorizontalPadding / opt.thumbCols;
                                mousePosition = e.clientX - $(this).offset().left + parseInt($(this).css('margin-left')) + $(document).scrollLeft();
                                thumbsPosition = 0 - (mousePosition - thumbW) * (thumbs_el.outerWidth() - $(this).width()) / ($(this).width() - 2 * thumbW);
                                if(thumbsPosition < (-1) * (thumbs_el.outerWidth() - $(this).width())) {
                                    thumbsPosition = (-1) * (thumbs_el.outerWidth() - $(this).width());
                                }
                                if(thumbsPosition > 0) {
                                    thumbsPosition = 0;
                                }
                                thumbs_el.css('margin-left', thumbsPosition);
                                //thumbs_el.animate({'margin-left': thumbsPosition}, { duration: 200, queue: false });
                            }

                            if(thumbs_el.outerHeight() > $(this).height()) {
                                thumbH = opt.thumbHeight + opt.thumbBorderSize * 2 + opt.thumbPadding * 2 + opt.thumbsSpacing - opt.thumbsSpacing / opt.thumbRows + opt.thumbsVerticalPadding / opt.thumbRows;
                                mousePosition = e.clientY - $(this).offset().top + parseInt($(this).css('margin-top')) + $(document).scrollTop();
                                thumbsPosition = 0 - (mousePosition - thumbH) * (thumbs_el.outerHeight() - $(this).height()) / ($(this).height() - 2 * thumbH);
                                if(thumbsPosition < (-1) * (thumbs_el.outerHeight() - $(this).height())) {
                                    thumbsPosition = (-1) * (thumbs_el.outerHeight() - $(this).height());
                                }
                                if(thumbsPosition > 0) {
                                    thumbsPosition = 0;
                                }
                                thumbs_el.css('margin-top', thumbsPosition);
                                //thumbs_el.animate({'margin-top': thumbsPosition}, { duration: 200, queue: false });
                            }
                        });
                    },
                    initThumbsScroll: function() {//Init Thumbnails Scroll
                        if(typeof(jQuery.fn.jScrollPane) == 'undefined') {
                            $('.gmPhantom_Container', Container).css('overflow', 'auto');
                        } else {
                            setTimeout(function() {
                                $('.gmPhantom_Container', Container).jScrollPane({autoReinitialise: true});
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
                        $('.gmPhantom_ThumbContainer', Container).on('mouseover mousemove', function(e) {
                            var thumbs_wrapper = $('.gmPhantom_thumbsWrapper', Container),
                                mousePositionX = e.clientX - $(thumbs_wrapper).offset().left + parseInt($(thumbs_wrapper).css('margin-left')) + $(document).scrollLeft(),
                                mousePositionY = e.clientY - $(thumbs_wrapper).offset().top + parseInt($(thumbs_wrapper).css('margin-top')) + $(document).scrollTop();

                            $('.gmPhantom_Tooltip', Container).css('left', mousePositionX - 10);
                            $('.gmPhantom_Tooltip', Container).css('top', mousePositionY - $('.gmPhantom_Tooltip', Container).height() - 15);
                        });
                    },
                    showTooltip: function(no) {// Resize, Position & Display the Tooltip
                        var HTML = [];
                        HTML.push(CaptionTitle[no]);
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
                        if(CaptionTitle[no] !== '') {
                            $('.gmPhantom_Tooltip', Container).css('display', 'block');
                        }
                    }
                },

                prototypes = {
                    isIEBrowser: function () {// Detect the browser IE
                        var myNav = navigator.userAgent.toLowerCase();
                        return (myNav.indexOf('msie') == -1) ? false : parseInt(myNav.split('msie')[1]);
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

                        if (typeof(item.prop('tagName')) !== 'undefined' && item.prop('tagName').toLowerCase() != 'body') {
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