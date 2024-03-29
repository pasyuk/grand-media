/*! Magnific Popup - v1.1.0 - 2016-02-20
* http://dimsemenov.com/plugins/magnific-popup/
* Copyright (c) 2016 Dmitry Semenov; */
!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):window.jQuery||window.Zepto)}(function(a){var b,c,d,e,f,g,h="Close",i="BeforeClose",j="AfterClose",k="BeforeAppend",l="MarkupParse",m="Open",n="Change",o="mfp",p="."+o,q="mfp-ready",r="mfp-removing",s="mfp-prevent-close",t=function(){},u=!!window.jQuery,v=a(window),w=function(a,c){b.ev.on(o+a+p,c)},x=function(b,c,d,e){var f=document.createElement("div");return f.className="mfp-"+b,d&&(f.innerHTML=d),e?c&&c.appendChild(f):(f=a(f),c&&f.appendTo(c)),f},y=function(c,d){b.ev.triggerHandler(o+c,d),b.st.callbacks&&(c=c.charAt(0).toLowerCase()+c.slice(1),b.st.callbacks[c]&&b.st.callbacks[c].apply(b,a.isArray(d)?d:[d]))},z=function(c){return c===g&&b.currTemplate.closeBtn||(b.currTemplate.closeBtn=a(b.st.closeMarkup.replace("%title%",b.st.tClose)),g=c),b.currTemplate.closeBtn},A=function(){a.magnificPopup.instance||(b=new t,b.init(),a.magnificPopup.instance=b)},B=function(){var a=document.createElement("p").style,b=["ms","O","Moz","Webkit"];if(void 0!==a.transition)return!0;for(;b.length;)if(b.pop()+"Transition"in a)return!0;return!1};t.prototype={constructor:t,init:function(){var c=navigator.appVersion;b.isLowIE=b.isIE8=document.all&&!document.addEventListener,b.isAndroid=/android/gi.test(c),b.isIOS=/iphone|ipad|ipod/gi.test(c),b.supportsTransition=B(),b.probablyMobile=b.isAndroid||b.isIOS||/(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent),d=a(document),b.popupsCache={}},open:function(c){var e;if(c.isObj===!1){b.items=c.items.toArray(),b.index=0;var g,h=c.items;for(e=0;e<h.length;e++)if(g=h[e],g.parsed&&(g=g.el[0]),g===c.el[0]){b.index=e;break}}else b.items=a.isArray(c.items)?c.items:[c.items],b.index=c.index||0;if(b.isOpen)return void b.updateItemHTML();b.types=[],f="",c.mainEl&&c.mainEl.length?b.ev=c.mainEl.eq(0):b.ev=d,c.key?(b.popupsCache[c.key]||(b.popupsCache[c.key]={}),b.currTemplate=b.popupsCache[c.key]):b.currTemplate={},b.st=a.extend(!0,{},a.magnificPopup.defaults,c),b.fixedContentPos="auto"===b.st.fixedContentPos?!b.probablyMobile:b.st.fixedContentPos,b.st.modal&&(b.st.closeOnContentClick=!1,b.st.closeOnBgClick=!1,b.st.showCloseBtn=!1,b.st.enableEscapeKey=!1),b.bgOverlay||(b.bgOverlay=x("bg").on("click"+p,function(){b.close()}),b.wrap=x("wrap").attr("tabindex",-1).on("click"+p,function(a){b._checkIfClose(a.target)&&b.close()}),b.container=x("container",b.wrap)),b.contentContainer=x("content"),b.st.preloader&&(b.preloader=x("preloader",b.container,b.st.tLoading));var i=a.magnificPopup.modules;for(e=0;e<i.length;e++){var j=i[e];j=j.charAt(0).toUpperCase()+j.slice(1),b["init"+j].call(b)}y("BeforeOpen"),b.st.showCloseBtn&&(b.st.closeBtnInside?(w(l,function(a,b,c,d){c.close_replaceWith=z(d.type)}),f+=" mfp-close-btn-in"):b.wrap.append(z())),b.st.alignTop&&(f+=" mfp-align-top"),b.fixedContentPos?b.wrap.css({overflow:b.st.overflowY,overflowX:"hidden",overflowY:b.st.overflowY}):b.wrap.css({top:v.scrollTop(),position:"absolute"}),(b.st.fixedBgPos===!1||"auto"===b.st.fixedBgPos&&!b.fixedContentPos)&&b.bgOverlay.css({height:d.height(),position:"absolute"}),b.st.enableEscapeKey&&d.on("keyup"+p,function(a){27===a.keyCode&&b.close()}),v.on("resize"+p,function(){b.updateSize()}),b.st.closeOnContentClick||(f+=" mfp-auto-cursor"),f&&b.wrap.addClass(f);var k=b.wH=v.height(),n={};if(b.fixedContentPos&&b._hasScrollBar(k)){var o=b._getScrollbarSize();o&&(n.marginRight=o)}b.fixedContentPos&&(b.isIE7?a("body, html").css("overflow","hidden"):n.overflow="hidden");var r=b.st.mainClass;return b.isIE7&&(r+=" mfp-ie7"),r&&b._addClassToMFP(r),b.updateItemHTML(),y("BuildControls"),a("html").css(n),b.bgOverlay.add(b.wrap).prependTo(b.st.prependTo||a(document.body)),b._lastFocusedEl=document.activeElement,setTimeout(function(){b.content?(b._addClassToMFP(q),b._setFocus()):b.bgOverlay.addClass(q),d.on("focusin"+p,b._onFocusIn)},16),b.isOpen=!0,b.updateSize(k),y(m),c},close:function(){b.isOpen&&(y(i),b.isOpen=!1,b.st.removalDelay&&!b.isLowIE&&b.supportsTransition?(b._addClassToMFP(r),setTimeout(function(){b._close()},b.st.removalDelay)):b._close())},_close:function(){y(h);var c=r+" "+q+" ";if(b.bgOverlay.detach(),b.wrap.detach(),b.container.empty(),b.st.mainClass&&(c+=b.st.mainClass+" "),b._removeClassFromMFP(c),b.fixedContentPos){var e={marginRight:""};b.isIE7?a("body, html").css("overflow",""):e.overflow="",a("html").css(e)}d.off("keyup"+p+" focusin"+p),b.ev.off(p),b.wrap.attr("class","mfp-wrap").removeAttr("style"),b.bgOverlay.attr("class","mfp-bg"),b.container.attr("class","mfp-container"),!b.st.showCloseBtn||b.st.closeBtnInside&&b.currTemplate[b.currItem.type]!==!0||b.currTemplate.closeBtn&&b.currTemplate.closeBtn.detach(),b.st.autoFocusLast&&b._lastFocusedEl&&a(b._lastFocusedEl).focus(),b.currItem=null,b.content=null,b.currTemplate=null,b.prevHeight=0,y(j)},updateSize:function(a){if(b.isIOS){var c=document.documentElement.clientWidth/window.innerWidth,d=window.innerHeight*c;b.wrap.css("height",d),b.wH=d}else b.wH=a||v.height();b.fixedContentPos||b.wrap.css("height",b.wH),y("Resize")},updateItemHTML:function(){var c=b.items[b.index];b.contentContainer.detach(),b.content&&b.content.detach(),c.parsed||(c=b.parseEl(b.index));var d=c.type;if(y("BeforeChange",[b.currItem?b.currItem.type:"",d]),b.currItem=c,!b.currTemplate[d]){var f=b.st[d]?b.st[d].markup:!1;y("FirstMarkupParse",f),f?b.currTemplate[d]=a(f):b.currTemplate[d]=!0}e&&e!==c.type&&b.container.removeClass("mfp-"+e+"-holder");var g=b["get"+d.charAt(0).toUpperCase()+d.slice(1)](c,b.currTemplate[d]);b.appendContent(g,d),c.preloaded=!0,y(n,c),e=c.type,b.container.prepend(b.contentContainer),y("AfterChange")},appendContent:function(a,c){b.content=a,a?b.st.showCloseBtn&&b.st.closeBtnInside&&b.currTemplate[c]===!0?b.content.find(".mfp-close").length||b.content.append(z()):b.content=a:b.content="",y(k),b.container.addClass("mfp-"+c+"-holder"),b.contentContainer.append(b.content)},parseEl:function(c){var d,e=b.items[c];if(e.tagName?e={el:a(e)}:(d=e.type,e={data:e,src:e.src}),e.el){for(var f=b.types,g=0;g<f.length;g++)if(e.el.hasClass("mfp-"+f[g])){d=f[g];break}e.src=e.el.attr("data-mfp-src"),e.src||(e.src=e.el.attr("href"))}return e.type=d||b.st.type||"inline",e.index=c,e.parsed=!0,b.items[c]=e,y("ElementParse",e),b.items[c]},addGroup:function(a,c){var d=function(d){d.mfpEl=this,b._openClick(d,a,c)};c||(c={});var e="click.magnificPopup";c.mainEl=a,c.items?(c.isObj=!0,a.off(e).on(e,d)):(c.isObj=!1,c.delegate?a.off(e).on(e,c.delegate,d):(c.items=a,a.off(e).on(e,d)))},_openClick:function(c,d,e){var f=void 0!==e.midClick?e.midClick:a.magnificPopup.defaults.midClick;if(f||!(2===c.which||c.ctrlKey||c.metaKey||c.altKey||c.shiftKey)){var g=void 0!==e.disableOn?e.disableOn:a.magnificPopup.defaults.disableOn;if(g)if(a.isFunction(g)){if(!g.call(b))return!0}else if(v.width()<g)return!0;c.type&&(c.preventDefault(),b.isOpen&&c.stopPropagation()),e.el=a(c.mfpEl),e.delegate&&(e.items=d.find(e.delegate)),b.open(e)}},updateStatus:function(a,d){if(b.preloader){c!==a&&b.container.removeClass("mfp-s-"+c),d||"loading"!==a||(d=b.st.tLoading);var e={status:a,text:d};y("UpdateStatus",e),a=e.status,d=e.text,b.preloader.html(d),b.preloader.find("a").on("click",function(a){a.stopImmediatePropagation()}),b.container.addClass("mfp-s-"+a),c=a}},_checkIfClose:function(c){if(!a(c).hasClass(s)){var d=b.st.closeOnContentClick,e=b.st.closeOnBgClick;if(d&&e)return!0;if(!b.content||a(c).hasClass("mfp-close")||b.preloader&&c===b.preloader[0])return!0;if(c===b.content[0]||a.contains(b.content[0],c)){if(d)return!0}else if(e&&a.contains(document,c))return!0;return!1}},_addClassToMFP:function(a){b.bgOverlay.addClass(a),b.wrap.addClass(a)},_removeClassFromMFP:function(a){this.bgOverlay.removeClass(a),b.wrap.removeClass(a)},_hasScrollBar:function(a){return(b.isIE7?d.height():document.body.scrollHeight)>(a||v.height())},_setFocus:function(){(b.st.focus?b.content.find(b.st.focus).eq(0):b.wrap).focus()},_onFocusIn:function(c){return c.target===b.wrap[0]||a.contains(b.wrap[0],c.target)?void 0:(b._setFocus(),!1)},_parseMarkup:function(b,c,d){var e;d.data&&(c=a.extend(d.data,c)),y(l,[b,c,d]),a.each(c,function(c,d){if(void 0===d||d===!1)return!0;if(e=c.split("_"),e.length>1){var f=b.find(p+"-"+e[0]);if(f.length>0){var g=e[1];"replaceWith"===g?f[0]!==d[0]&&f.replaceWith(d):"img"===g?f.is("img")?f.attr("src",d):f.replaceWith(a("<img>").attr("src",d).attr("class",f.attr("class"))):f.attr(e[1],d)}}else b.find(p+"-"+c).html(d)})},_getScrollbarSize:function(){if(void 0===b.scrollbarSize){var a=document.createElement("div");a.style.cssText="width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;",document.body.appendChild(a),b.scrollbarSize=a.offsetWidth-a.clientWidth,document.body.removeChild(a)}return b.scrollbarSize}},a.magnificPopup={instance:null,proto:t.prototype,modules:[],open:function(b,c){return A(),b=b?a.extend(!0,{},b):{},b.isObj=!0,b.index=c||0,this.instance.open(b)},close:function(){return a.magnificPopup.instance&&a.magnificPopup.instance.close()},registerModule:function(b,c){c.options&&(a.magnificPopup.defaults[b]=c.options),a.extend(this.proto,c.proto),this.modules.push(b)},defaults:{disableOn:0,key:null,midClick:!1,mainClass:"",preloader:!0,focus:"",closeOnContentClick:!1,closeOnBgClick:!0,closeBtnInside:!0,showCloseBtn:!0,enableEscapeKey:!0,modal:!1,alignTop:!1,removalDelay:0,prependTo:null,fixedContentPos:"auto",fixedBgPos:"auto",overflowY:"auto",closeMarkup:'<button title="%title%" type="button" class="mfp-close">&#215;</button>',tClose:"Close (Esc)",tLoading:"Loading...",autoFocusLast:!0}},a.fn.magnificPopup=function(c){A();var d=a(this);if("string"==typeof c)if("open"===c){var e,f=u?d.data("magnificPopup"):d[0].magnificPopup,g=parseInt(arguments[1],10)||0;f.items?e=f.items[g]:(e=d,f.delegate&&(e=e.find(f.delegate)),e=e.eq(g)),b._openClick({mfpEl:e},d,f)}else b.isOpen&&b[c].apply(b,Array.prototype.slice.call(arguments,1));else c=a.extend(!0,{},c),u?d.data("magnificPopup",c):d[0].magnificPopup=c,b.addGroup(d,c);return d};var C,D,E,F="inline",G=function(){E&&(D.after(E.addClass(C)).detach(),E=null)};a.magnificPopup.registerModule(F,{options:{hiddenClass:"hide",markup:"",tNotFound:"Content not found"},proto:{initInline:function(){b.types.push(F),w(h+"."+F,function(){G()})},getInline:function(c,d){if(G(),c.src){var e=b.st.inline,f=a(c.src);if(f.length){var g=f[0].parentNode;g&&g.tagName&&(D||(C=e.hiddenClass,D=x(C),C="mfp-"+C),E=f.after(D).detach().removeClass(C)),b.updateStatus("ready")}else b.updateStatus("error",e.tNotFound),f=a("<div>");return c.inlineElement=f,f}return b.updateStatus("ready"),b._parseMarkup(d,{},c),d}}});var H,I="ajax",J=function(){H&&a(document.body).removeClass(H)},K=function(){J(),b.req&&b.req.abort()};a.magnificPopup.registerModule(I,{options:{settings:null,cursor:"mfp-ajax-cur",tError:'<a href="%url%">The content</a> could not be loaded.'},proto:{initAjax:function(){b.types.push(I),H=b.st.ajax.cursor,w(h+"."+I,K),w("BeforeChange."+I,K)},getAjax:function(c){H&&a(document.body).addClass(H),b.updateStatus("loading");var d=a.extend({url:c.src,success:function(d,e,f){var g={data:d,xhr:f};y("ParseAjax",g),b.appendContent(a(g.data),I),c.finished=!0,J(),b._setFocus(),setTimeout(function(){b.wrap.addClass(q)},16),b.updateStatus("ready"),y("AjaxContentAdded")},error:function(){J(),c.finished=c.loadError=!0,b.updateStatus("error",b.st.ajax.tError.replace("%url%",c.src))}},b.st.ajax.settings);return b.req=a.ajax(d),""}}});var L,M=function(c){if(c.data&&void 0!==c.data.title)return c.data.title;var d=b.st.image.titleSrc;if(d){if(a.isFunction(d))return d.call(b,c);if(c.el)return c.el.attr(d)||""}return""};a.magnificPopup.registerModule("image",{options:{markup:'<div class="mfp-figure"><div class="mfp-close"></div><figure><div class="mfp-img"></div><figcaption><div class="mfp-bottom-bar"><div class="mfp-title"></div><div class="mfp-counter"></div></div></figcaption></figure></div>',cursor:"mfp-zoom-out-cur",titleSrc:"title",verticalFit:!0,tError:'<a href="%url%">The image</a> could not be loaded.'},proto:{initImage:function(){var c=b.st.image,d=".image";b.types.push("image"),w(m+d,function(){"image"===b.currItem.type&&c.cursor&&a(document.body).addClass(c.cursor)}),w(h+d,function(){c.cursor&&a(document.body).removeClass(c.cursor),v.off("resize"+p)}),w("Resize"+d,b.resizeImage),b.isLowIE&&w("AfterChange",b.resizeImage)},resizeImage:function(){var a=b.currItem;if(a&&a.img&&b.st.image.verticalFit){var c=0;b.isLowIE&&(c=parseInt(a.img.css("padding-top"),10)+parseInt(a.img.css("padding-bottom"),10)),a.img.css("max-height",b.wH-c)}},_onImageHasSize:function(a){a.img&&(a.hasSize=!0,L&&clearInterval(L),a.isCheckingImgSize=!1,y("ImageHasSize",a),a.imgHidden&&(b.content&&b.content.removeClass("mfp-loading"),a.imgHidden=!1))},findImageSize:function(a){var c=0,d=a.img[0],e=function(f){L&&clearInterval(L),L=setInterval(function(){return d.naturalWidth>0?void b._onImageHasSize(a):(c>200&&clearInterval(L),c++,void(3===c?e(10):40===c?e(50):100===c&&e(500)))},f)};e(1)},getImage:function(c,d){var e=0,f=function(){c&&(c.img[0].complete?(c.img.off(".mfploader"),c===b.currItem&&(b._onImageHasSize(c),b.updateStatus("ready")),c.hasSize=!0,c.loaded=!0,y("ImageLoadComplete")):(e++,200>e?setTimeout(f,100):g()))},g=function(){c&&(c.img.off(".mfploader"),c===b.currItem&&(b._onImageHasSize(c),b.updateStatus("error",h.tError.replace("%url%",c.src))),c.hasSize=!0,c.loaded=!0,c.loadError=!0)},h=b.st.image,i=d.find(".mfp-img");if(i.length){var j=document.createElement("img");j.className="mfp-img",c.el&&c.el.find("img").length&&(j.alt=c.el.find("img").attr("alt")),c.img=a(j).on("load.mfploader",f).on("error.mfploader",g),j.src=c.src,i.is("img")&&(c.img=c.img.clone()),j=c.img[0],j.naturalWidth>0?c.hasSize=!0:j.width||(c.hasSize=!1)}return b._parseMarkup(d,{title:M(c),img_replaceWith:c.img},c),b.resizeImage(),c.hasSize?(L&&clearInterval(L),c.loadError?(d.addClass("mfp-loading"),b.updateStatus("error",h.tError.replace("%url%",c.src))):(d.removeClass("mfp-loading"),b.updateStatus("ready")),d):(b.updateStatus("loading"),c.loading=!0,c.hasSize||(c.imgHidden=!0,d.addClass("mfp-loading"),b.findImageSize(c)),d)}}});var N,O=function(){return void 0===N&&(N=void 0!==document.createElement("p").style.MozTransform),N};a.magnificPopup.registerModule("zoom",{options:{enabled:!1,easing:"ease-in-out",duration:300,opener:function(a){return a.is("img")?a:a.find("img")}},proto:{initZoom:function(){var a,c=b.st.zoom,d=".zoom";if(c.enabled&&b.supportsTransition){var e,f,g=c.duration,j=function(a){var b=a.clone().removeAttr("style").removeAttr("class").addClass("mfp-animated-image"),d="all "+c.duration/1e3+"s "+c.easing,e={position:"fixed",zIndex:9999,left:0,top:0,"-webkit-backface-visibility":"hidden"},f="transition";return e["-webkit-"+f]=e["-moz-"+f]=e["-o-"+f]=e[f]=d,b.css(e),b},k=function(){b.content.css("visibility","visible")};w("BuildControls"+d,function(){if(b._allowZoom()){if(clearTimeout(e),b.content.css("visibility","hidden"),a=b._getItemToZoom(),!a)return void k();f=j(a),f.css(b._getOffset()),b.wrap.append(f),e=setTimeout(function(){f.css(b._getOffset(!0)),e=setTimeout(function(){k(),setTimeout(function(){f.remove(),a=f=null,y("ZoomAnimationEnded")},16)},g)},16)}}),w(i+d,function(){if(b._allowZoom()){if(clearTimeout(e),b.st.removalDelay=g,!a){if(a=b._getItemToZoom(),!a)return;f=j(a)}f.css(b._getOffset(!0)),b.wrap.append(f),b.content.css("visibility","hidden"),setTimeout(function(){f.css(b._getOffset())},16)}}),w(h+d,function(){b._allowZoom()&&(k(),f&&f.remove(),a=null)})}},_allowZoom:function(){return"image"===b.currItem.type},_getItemToZoom:function(){return b.currItem.hasSize?b.currItem.img:!1},_getOffset:function(c){var d;d=c?b.currItem.img:b.st.zoom.opener(b.currItem.el||b.currItem);var e=d.offset(),f=parseInt(d.css("padding-top"),10),g=parseInt(d.css("padding-bottom"),10);e.top-=a(window).scrollTop()-f;var h={width:d.width(),height:(u?d.innerHeight():d[0].offsetHeight)-g-f};return O()?h["-moz-transform"]=h.transform="translate("+e.left+"px,"+e.top+"px)":(h.left=e.left,h.top=e.top),h}}});var P="iframe",Q="//about:blank",R=function(a){if(b.currTemplate[P]){var c=b.currTemplate[P].find("iframe");c.length&&(a||(c[0].src=Q),b.isIE8&&c.css("display",a?"block":"none"))}};a.magnificPopup.registerModule(P,{options:{markup:'<div class="mfp-iframe-scaler"><div class="mfp-close"></div><iframe class="mfp-iframe" src="//about:blank" frameborder="0" allowfullscreen></iframe></div>',srcAction:"iframe_src",patterns:{youtube:{index:"youtube.com",id:"v=",src:"//www.youtube.com/embed/%id%?autoplay=1"},vimeo:{index:"vimeo.com/",id:"/",src:"//player.vimeo.com/video/%id%?autoplay=1"},gmaps:{index:"//maps.google.",src:"%id%&output=embed"}}},proto:{initIframe:function(){b.types.push(P),w("BeforeChange",function(a,b,c){b!==c&&(b===P?R():c===P&&R(!0))}),w(h+"."+P,function(){R()})},getIframe:function(c,d){var e=c.src,f=b.st.iframe;a.each(f.patterns,function(){return e.indexOf(this.index)>-1?(this.id&&(e="string"==typeof this.id?e.substr(e.lastIndexOf(this.id)+this.id.length,e.length):this.id.call(this,e)),e=this.src.replace("%id%",e),!1):void 0});var g={};return f.srcAction&&(g[f.srcAction]=e),b._parseMarkup(d,g,c),b.updateStatus("ready"),d}}});var S=function(a){var c=b.items.length;return a>c-1?a-c:0>a?c+a:a},T=function(a,b,c){return a.replace(/%curr%/gi,b+1).replace(/%total%/gi,c)};a.magnificPopup.registerModule("gallery",{options:{enabled:!1,arrowMarkup:'<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',preload:[0,2],navigateByImgClick:!0,arrows:!0,tPrev:"Previous (Left arrow key)",tNext:"Next (Right arrow key)",tCounter:"%curr% of %total%"},proto:{initGallery:function(){var c=b.st.gallery,e=".mfp-gallery";return b.direction=!0,c&&c.enabled?(f+=" mfp-gallery",w(m+e,function(){c.navigateByImgClick&&b.wrap.on("click"+e,".mfp-img",function(){return b.items.length>1?(b.next(),!1):void 0}),d.on("keydown"+e,function(a){37===a.keyCode?b.prev():39===a.keyCode&&b.next()})}),w("UpdateStatus"+e,function(a,c){c.text&&(c.text=T(c.text,b.currItem.index,b.items.length))}),w(l+e,function(a,d,e,f){var g=b.items.length;e.counter=g>1?T(c.tCounter,f.index,g):""}),w("BuildControls"+e,function(){if(b.items.length>1&&c.arrows&&!b.arrowLeft){var d=c.arrowMarkup,e=b.arrowLeft=a(d.replace(/%title%/gi,c.tPrev).replace(/%dir%/gi,"left")).addClass(s),f=b.arrowRight=a(d.replace(/%title%/gi,c.tNext).replace(/%dir%/gi,"right")).addClass(s);e.click(function(){b.prev()}),f.click(function(){b.next()}),b.container.append(e.add(f))}}),w(n+e,function(){b._preloadTimeout&&clearTimeout(b._preloadTimeout),b._preloadTimeout=setTimeout(function(){b.preloadNearbyImages(),b._preloadTimeout=null},16)}),void w(h+e,function(){d.off(e),b.wrap.off("click"+e),b.arrowRight=b.arrowLeft=null})):!1},next:function(){b.direction=!0,b.index=S(b.index+1),b.updateItemHTML()},prev:function(){b.direction=!1,b.index=S(b.index-1),b.updateItemHTML()},goTo:function(a){b.direction=a>=b.index,b.index=a,b.updateItemHTML()},preloadNearbyImages:function(){var a,c=b.st.gallery.preload,d=Math.min(c[0],b.items.length),e=Math.min(c[1],b.items.length);for(a=1;a<=(b.direction?e:d);a++)b._preloadItem(b.index+a);for(a=1;a<=(b.direction?d:e);a++)b._preloadItem(b.index-a)},_preloadItem:function(c){if(c=S(c),!b.items[c].preloaded){var d=b.items[c];d.parsed||(d=b.parseEl(c)),y("LazyLoad",d),"image"===d.type&&(d.img=a('<img class="mfp-img" />').on("load.mfploader",function(){d.hasSize=!0}).on("error.mfploader",function(){d.hasSize=!0,d.loadError=!0,y("LazyLoadError",d)}).attr("src",d.src)),d.preloaded=!0}}}});var U="retina";a.magnificPopup.registerModule(U,{options:{replaceSrc:function(a){return a.src.replace(/\.\w+$/,function(a){return"@2x"+a})},ratio:1},proto:{initRetina:function(){if(window.devicePixelRatio>1){var a=b.st.retina,c=a.ratio;c=isNaN(c)?c():c,c>1&&(w("ImageHasSize."+U,function(a,b){b.img.css({"max-width":b.img[0].naturalWidth/c,width:"100%"})}),w("ElementParse."+U,function(b,d){d.src=a.replaceSrc(d,c)}))}}}}),A()});

/**
 * Title                   : gmCubikLite
 * Copyright               : 2016 CodEasily.com
 * Website                 : http://www.codeasily.com
 * Inspired                : https://github.com/fofr/paulrhayes.com-experiments
 */
if ( typeof jQuery.fn.gmCubikLite == 'undefined' ) {
	(function( $, window, document ) {
		$.fn.gmCubikLite = function( method ) {
			var Container = this,
				elID = $( this ).attr( 'id' ),
				ID = elID.replace( 'GmediaGallery_', '' ),
				opt,
				ticker,
				scrlbar = 0,

				opt_str = {
					'mfp_css': '',
					'module_dirurl': ''
				},
				opt_hex = {
					'sidebarBGColor': 'ffffff',
					'lightboxControlsColor': 'ffffff', //   Tooltip Text Color (color hex code). Default value: 000000.
					'lightboxTitleColor': 'f3f3f3', //   Tooltip Text Color (color hex code). Default value: 000000.
					'lightboxTextColor': 'f3f3f3', //   Tooltip Text Color (color hex code). Default value: 000000.
					'lightboxBGColor': '0b0b0b' // Lightbox Window Color (color hex code). Default value: 000000. Set the color for the lightbox window.
				},
				opt_int = {
					'maxSize': 400, // Cube Size (auto, number).
					'spin': 50, // Auto rotation speed.
					'thumbCols': 4, // Number of Columns (auto, number).
					'facePadding': 20, // Thumbnails Padding (value in pixels).
					'faceMarging': 20, // Thumbnails Margin (value in pixels).
					'lightboxBGAlpha': 80 // Lightbox Window Alpha (value from 0 to 100).
				},
				opt_bool = {
					'deepLinks': true,
					'socialShareEnabled': true, // Social Share Enabled (true, false). Default value: true.
					'share_post_link': true,
					'show_tags': true,
					'show_categories': true,
					'show_albums': true,
					'commentsEnabled': true, // Comments Enabled (true, false). Default value: true.
					'lightbox800HideArrows': false, // Hide Arrows if window width less than 800px.
					'thumb2link': false // Open link instead of lightbox when item have "link" attr
				},

				noItems = 0,

				Storage = {views: [], likes: []},

				startWith = 0,
				scrollTop = 0,
				scrollLeft = 0,
				itemLoaded = 0,
				thumbsNavigationArrowsSpeed = 200,
				cc = 0,
				transformProp,
				transitionDurationProp,
				perspectiveProp,
				spinOn,

				methods = {
					init: function( arguments ) {// Init Plugin.
						opt = $.extend( true, {}, opt_str, opt_int, opt_bool, opt_hex, arguments[0] );
						$.each( opt, function( key, val ) {
							if ( key in opt_bool ) {
								opt[key] = (! (! val || val == '0' || val == 'false'));
							} else if ( key in opt_int ) {
								opt[key] = parseInt( val );
							}
						} );
						opt.spin = -opt.spin / 100;
						spinOn = opt.spin ? true : false;
						noItems = $( '.gmCubikLite_thumbsWrapper', Container ).find( '.gmCubikLite_thumb' ).length;

						if ( ! $( Container ).data( 'gmCubikLite_initialized' ) ) {
							methods.initGallery();
							$( Container ).data( 'gmCubikLite_initialized', true );
						}

						methods.cubikResize();
						$( window ).on( 'resize.gmCubikLite', function() {
							methods.cubikResize();
						} );
					},
					initGallery: function() {// Init the Gallery
						var currentScrollPosition = 0;
						$( document ).scroll( function() {
							currentScrollPosition = $( this ).scrollTop();
						} );
						window.onhashchange = function() {
							methods.loadGalleryDeepLink();
						};

						$( 'input, textarea' ).focus( function() {
							$( document ).scrollTop( currentScrollPosition );
						} );

						var touch = prototypes.isTouchDevice(),
							browser_class = '';
						if ( touch ) {
							browser_class += ' istouch';
						}

						$( Container ).addClass( browser_class );

						methods.initSettings();

						var el = document.createElement( 'div' ),
							transformProps = 'transform WebkitTransform MozTransform OTransform msTransform'.split( ' ' ),
							transitionDuration = 'transitionDuration WebkitTransitionDuration MozTransitionDuration OTransitionDuration msTransitionDuration'.split( ' ' ),
							perspectiveProps = 'perspective WebkitPerspective MozPerspective OPerspective msPerspective'.split( ' ' );

						transformProp = supportCSS( transformProps );
						transitionDurationProp = supportCSS( transitionDuration );
						perspectiveProp = supportCSS( perspectiveProps );

						function supportCSS( props ) {
							for ( var i = 0, l = props.length; i < l; i++ ) {
								if ( typeof $( '.gmCubikLite_thumbsWrapper', Container )[0].style[props[i]] !== 'undefined' ) {
									return props[i];
								}
							}
						}

						var timestamp, ticker, spin, mouse, movementScaleFactorX, movementScaleFactorY, viewport,
							spinDirection = -1,
							timeConstantX = 450,
							timeConstantY = 500;
						mouse = {
							start: {},
							velocities: [],
							velocity: {x: 0, y: 0},
							speed: {x: 0, y: 0},
							track: function() {
								if ( mouse.velocities.length > 1 ) {
									var lastMoveEvent = mouse.velocities.pop(), velocityEvent = mouse.velocities.pop();
									var time = lastMoveEvent.time - velocityEvent.time;
									var distanceX = lastMoveEvent.position.x - velocityEvent.position.x;
									spinDirection = distanceX > 0 ? 1 : -1;
									// this implies that the user stopped moving a finger then released.
									// There would be no events with distance zero, so the last event is stale.
									if ( time < 100 && (Date.now() - lastMoveEvent.time) < 100 ) {
										var distanceY = lastMoveEvent.position.y - velocityEvent.position.y;
										var velocityX = 2 * distanceX / time;
										var velocityY = 1 * distanceY / time;

										mouse.speed.x = mouse.velocity.x = velocityX;
										mouse.speed.y = mouse.velocity.y = velocityY;
										requestAnimationFrame( viewport.autoMove );
									} else {
										$( Container ).removeClass( 'gmCubikLite_rotating' );
									}
								} else {
									$( Container ).removeClass( 'gmCubikLite_rotating' );
								}
							}
						};
						// Reduce movement on touch screens
						movementScaleFactorX = touch ? 10 : 16;
						movementScaleFactorY = touch ? 6 : 12;
						viewport = {
							x: -30,
							y: 40,
							maxX: 60,
							minX: -60,
							el: $( '.gmCubikLite_thumbsWrapper', Container )[0],
							move: function( coords, animate ) {
								if ( coords ) {
									if ( typeof coords.x === 'number' ) {
										viewport.x = coords.x;
									}
									if ( typeof coords.y === 'number' ) {
										viewport.y = coords.y;
									}
								}

								if ( animate ) {
									var d = viewport.transition( 500 );
									clearTimeout( ticker );
									ticker = setTimeout( function() {
										viewport.transition( 0 );
										if ( viewport.y > 360 || viewport.y < -360 ) {
											viewport.x = Math.max( viewport.minX, Math.min( viewport.maxX, viewport.x ) );
											viewport.y %= 360;
											viewport.move();
										}
									}, d + 10 );
								} else {
									viewport.x = Math.max( viewport.minX, Math.min( viewport.maxX, viewport.x ) );
									viewport.y %= 360;
								}
								viewport.el.style[transformProp] = 'rotateX(' + viewport.x + 'deg) rotateY(' + viewport.y + 'deg)';
							},
							autoMove: function() {
								if ( mouse.velocity.x || mouse.velocity.y ) {
									var moveX,
										absSpeedX = Math.abs( mouse.speed.x ),
										absSpeedY = Math.abs( mouse.speed.y ),
										elapsed = timestamp - Date.now();

									if ( spinOn ) {
										var absSpin = Math.abs( opt.spin );
										if ( Math.abs( mouse.velocity.x ) >= absSpin ) {
											if ( absSpeedX > absSpin ) {
												mouse.speed.x = mouse.velocity.x * Math.exp( elapsed / timeConstantX );
											} else {
												mouse.speed.x = mouse.velocity.x = -opt.spin * spinDirection;
											}
										} else {
											if ( absSpeedX < absSpin ) {
												mouse.speed.x = mouse.velocity.x / Math.exp( elapsed / timeConstantX );
											} else {
												mouse.speed.x = mouse.velocity.x = -opt.spin * spinDirection;
											}
										}
									} else {
										if ( absSpeedX > 0.01 ) {
											mouse.speed.x = mouse.velocity.x * Math.exp( elapsed / timeConstantX );
										} else {
											mouse.speed.x = mouse.velocity.x = 0;
										}
									}

									if ( absSpeedY > 0.1 ) {
										mouse.speed.y = -mouse.velocity.y * Math.exp( elapsed / timeConstantY );
									} else {
										mouse.speed.y = mouse.velocity.y = 0;
									}

									viewport.move( {x: viewport.x + mouse.speed.y, y: viewport.y + mouse.speed.x} );
									requestAnimationFrame( viewport.autoMove );

								} else {
									$( Container ).removeClass( 'gmCubikLite_rotating' );
								}
							},
							reset: function( animate ) {
								viewport.move( {x: -30, y: 40}, animate );
							},
							keydown: function( evt ) {
								switch ( evt.keyCode ) {
									case 37: // left
										viewport.move( {y: viewport.y - 90}, true );
										break;

									case 39: // right
										viewport.move( {y: viewport.y + 90}, true );
										break;

									case 38: // up
										evt.preventDefault();
										viewport.move( {x: viewport.x + 10}, true );
										break;

									case 40: // down
										evt.preventDefault();
										viewport.move( {x: viewport.x - 10}, true );
										break;

									case 27: //esc
										viewport.reset( true );
										break;

									default:
										break;
								}
							}
						};

						viewport.transition = function( d ) {
							if ( d ) {
								viewport.el.style[transitionDurationProp] = d + 'ms';
							} else {
								viewport.el.style[transitionDurationProp] = null;
							}
							return d;
						};

						if ( opt.spin ) {
							$( Container ).addClass( 'gmCubikLite_rotating' );
							mouse.velocity.x = 0.01 * spinDirection;
							timestamp = Date.now();
							viewport.autoMove();
						}

						$( Container ).on( 'mouseenter', function() {
							$( document ).off( 'keydown.cubik-lite' ).on( 'keydown.cubik-lite', viewport.keydown );
							if ( opt.spin ) {
								spinOn = false;
								timestamp = Date.now();
								mouse.velocity.x = mouse.speed.x;
								mouse.velocity.y = -mouse.speed.y;
							}
						} ).on( 'mouseleave touchend', function() {
							$( document ).off( 'keydown.cubik-lite' );
							if ( opt.spin && ! $( this ).data( 'gmCubikLite_stop' ) ) {
								spinOn = true;
								timestamp = Date.now();
								mouse.velocity.x = mouse.speed.x;
								mouse.velocity.y = -mouse.speed.y;
								if ( touch ) {
									setTimeout( function() {
										if ( ! mouse.velocity.x ) {
											$( Container ).addClass( 'gmCubikLite_rotating' );
											mouse.velocity.x = 0.01 * spinDirection;
											viewport.autoMove();
										}
									}, 100 );
								} else {
									if ( ! mouse.velocity.x ) {
										$( Container ).addClass( 'gmCubikLite_rotating' );
										mouse.velocity.x = 0.01 * spinDirection;
										viewport.autoMove();
									}
								}
							}
						} );

						$( document ).on( 'mouseleave', function() {
							if ( opt.spin ) {
								spinOn = false;
								timestamp = Date.now();
								mouse.velocity.x = mouse.speed.x;
								mouse.velocity.y = -mouse.speed.y;
							}
						} ).on( 'mouseenter', function( e ) {
							if ( ! $( e.target ).closest( '.cubik-lite_module' ).length ) {
								if ( opt.spin && ! $( Container ).data( 'gmCubikLite_stop' ) ) {
									spinOn = true;
									timestamp = Date.now();
									mouse.velocity.x = mouse.speed.x;
									mouse.velocity.y = -mouse.speed.y;
									if ( ! mouse.velocity.x ) {
										$( Container ).addClass( 'gmCubikLite_rotating' );
										mouse.velocity.x = 0.01 * spinDirection;
										viewport.autoMove();
									}
								}
							}
						} );

						$( Container ).on( 'mousedown touchstart', function( evt ) {
							delete mouse.last;

							evt.originalEvent.touches ? evt = evt.originalEvent.touches[0] : null;

							mouse.start.x = evt.pageX;
							mouse.start.y = evt.pageY;
							mouse.velocities = [];
							mouse.velocity = {x: 0, y: 0};
							mouse.speed = {x: 0, y: 0};
							//cancelAnimationFrame(spin);
							//spinA = 0;

							timestamp = Date.now();

							$( document ).on( 'mousemove.cubik-lite touchmove.cubik-lite', function( event ) {
								// Only perform rotation if one touch or mouse (e.g. still scale with pinch and zoom)
								if ( ! touch || ! (event.originalEvent && event.originalEvent.touches.length > 1) ) {
									event.preventDefault();
									// Get touch co-ords
									event.originalEvent.touches ? event = event.originalEvent.touches[0] : null;
									$( '.gmCubikLite_thumbsContainer', Container ).trigger( 'gmCubikLite-rotate', {x: event.pageX, y: event.pageY} );
								}
							} ).one( 'mouseup touchend', function( event ) {
								$( document ).off( '.cubik-lite' );
								mouse.track();
							} );

							if ( $( evt.target ).is( 'a' ) ) {
								return false;
							}

						} ).on( 'touchmove', function( evt ) {
							if ( ! (evt.originalEvent && evt.originalEvent.touches.length > 1) ) {
								evt.preventDefault();
							}
						} );

						$( '.gmCubikLite_thumbsContainer', Container ).on( 'gmCubikLite-rotate', function( evt, movedMouse ) {

							if ( ! mouse.last ) {
								mouse.last = mouse.start;
								$( Container ).addClass( 'gmCubikLite_rotating' );
							} else {
								if ( forward( mouse.start.x, mouse.last.x ) != forward( mouse.last.x, movedMouse.x ) ) {
									mouse.start.x = mouse.last.x;
								}
								if ( forward( mouse.start.y, mouse.last.y ) != forward( mouse.last.y, movedMouse.y ) ) {
									mouse.start.y = mouse.last.y;
								}
							}
							var moveX = viewport.x + (mouse.start.y - movedMouse.y) / movementScaleFactorX;
							var moveY = viewport.y - (mouse.start.x - movedMouse.x) / movementScaleFactorY;
							viewport.move( {
								x: moveX,
								y: moveY
							} );

							mouse.last.x = movedMouse.x;
							mouse.last.y = movedMouse.y;

							mouse.velocities.push( {
								position: {x: mouse.last.x, y: mouse.last.y},
								time: timestamp
							} );

							timestamp = Date.now();

							function forward( v1, v2 ) {
								return v1 >= v2 ? true : false;
							}
						} );

						methods.initLightbox();
						methods.loadGalleryDeepLink();

						setTimeout( function() {
							$( 'a.gmCubikLite_thumbImg', Container ).off( 'click' );
						}, 10 );
					},
					initLightbox: function() {
						$( '.gmCubikLite_thumb', Container ).not( '.gmCubikLite_parsed' ).each( function() {
							var link = $( this ).attr( 'data-link' );
							if ( link ) {
								if ( link.indexOf( 'youtube.com/' ) !== -1 || link.indexOf( 'vimeo.com/' ) !== -1 ) {
									$( '.gmCubikLite_thumbImg', this ).attr( 'href', link );
									$( this ).addClass( 'mfp-iframe' ).removeAttr( 'data-link' ).removeAttr( 'data-target' ).attr( 'data-mtype', 'video' );
								} else if ( link.indexOf( '//maps.google.' ) !== -1 || (link.indexOf( 'google.' ) !== -1 && link.indexOf( '/maps/embed' ) !== -1) ) {
									$( '.gmCubikLite_thumbImg', this ).attr( 'href', link );
									$( this ).addClass( 'mfp-iframe' ).removeAttr( 'data-link' ).removeAttr( 'data-target' ).attr( 'data-mtype', 'map' );
								}
							}
							$( this ).addClass( 'gmCubikLite_parsed' );
						} );

						var thumbs_wrapper = $( '.gmCubikLite_thumbsWrapper', Container );
						thumbs_wrapper.magnificPopup( {
							type: 'image',
							delegate: '.gmCubikLite_thumb',
							preloader: true,
							closeBtnInside: false,
							fixedContentPos: 'auto',
							fixedBgPos: true,
							overflowY: '',
							closeMarkup: '<div title="%title%" class="mfp-button mfp-close">&#215;</div>',
							tLoading: '', // remove text from preloader
							callbacks: {
								elementParse: function( item ) {
									// Function will fire for each target element
									// "item.el" is a target DOM element (if present)
									// "item.src" is a source that you may modify
									var data = item.el.data();
									item = $.extend( true, item, data );
									var a_img = $( '.gmCubikLite_thumbImg', item.el );
									item.image = $( 'img', a_img ).attr( 'data-src' );
									item.src = a_img.attr( 'href' );
									item.title = a_img.attr( 'title' );
									item.viewed = prototypes.arrVal( Storage.views, parseInt( item.id ) );
									item.liked = prototypes.arrVal( Storage.likes, parseInt( item.id ) );

								},
								markupParse: function( template, values, item ) {
									values.title = $( '.gmCubikLite_title', item.el ).html();
									values.description = $( '.gmCubikLite_thumbDetails', item.el ).html();
								},

								imageLoadComplete: function() {
									var self = this;
									setTimeout( function() {
										self.wrap.addClass( 'mfp-image-loaded' );
									}, 16 );
								},
								open: function() {
									$.magnificPopup.instance.next = function() {
										if ( $.magnificPopup.instance.index < $.magnificPopup.instance.items.length - 1 ) {
											$.magnificPopup.proto.next.call( this, arguments );
										}
									};
									$.magnificPopup.instance.prev = function() {
										if ( $.magnificPopup.instance.index > 0 ) {
											$.magnificPopup.proto.prev.call( this, arguments );
										}
									};
									$.magnificPopup.instance.toggleArrows = function() {
										if ( $.magnificPopup.instance.index < $.magnificPopup.instance.items.length - 1 ) {
											$( '.mfp-arrow-right' ).show();
										}
										if ( $.magnificPopup.instance.index == $.magnificPopup.instance.items.length - 1 ) {
											$( '.mfp-arrow-right' ).hide();
										}

										if ( $.magnificPopup.instance.index > 0 ) {
											$( '.mfp-arrow-left' ).show();
										}
										if ( $.magnificPopup.instance.index == 0 ) {
											$( '.mfp-arrow-left' ).hide();
										}
									};
									$.magnificPopup.instance.updateItemHTML = function() {
										$.magnificPopup.instance.toggleArrows();
										$.magnificPopup.proto.updateItemHTML.call( this, arguments );
									};
									var orig_checkIfClose = $.magnificPopup.instance._checkIfClose;
									$.magnificPopup.instance._checkIfClose = function( target ) {
										if ( $( target ).closest( '.mfp-prevent-close' ).length ) {
											return;
										}
										return orig_checkIfClose( target );
									};

									$( document.body ).addClass( 'mfp-gmedia-open gmedia-cubik-lite' );
									itemLoaded = this.currItem.id;
									if ( opt.commentsEnabled ) {
										this.wrap.on( 'click.gmCloseComments', '.mfp-img--comments-div', function() {
											$( '#mfp_comments_' + ID, this.wrap ).trigger( 'click' );
											return false;
										} );
										this.wrap.on( 'click', '.mfp-close-comments', function() {
											$( '#mfp_comments_' + ID, this.wrap ).trigger( 'click' );
											return false;
										} );
									}
									if ( opt.socialShareEnabled ) {
										var mfp = this;
										$( mfp.wrap ).on( 'click', '.mfp-share_sharelizer', function() {
											var sharelink,
												title = mfp.currItem.title,
												imgsrc = mfp.currItem.image,
												_url = ('' + window.location.href).split( '#' ),
												url = _url[0];
											if ( opt.share_post_link ) {
												url = mfp.currItem['post_link'];
											} else {
												var separator = (url.indexOf( '?' ) === -1) ? '?' : '&',
													newParam = separator + 'gmedia_share=' + mfp.currItem['id'];
												url = url.replace( newParam, '' );
												url += newParam;
												var hash = '#!gallery-' + ID + '-' + mfp.currItem['id'];
												url += hash;
											}
											if ( $( this ).hasClass( 'mfp-share_twitter' ) ) {
												sharelink = 'https://twitter.com/home?status=' + encodeURIComponent( title + ' ' + url );
												window.open( sharelink, '_blank' );
											}
											if ( $( this ).hasClass( 'mfp-share_facebook' ) ) {
												sharelink = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent( url );
												window.open( sharelink, '_blank' );
											}
											if ( $( this ).hasClass( 'mfp-share_pinterest' ) ) {
												sharelink = 'https://pinterest.com/pin/create/button/?url=' + encodeURIComponent( url ) + '&media=' + encodeURIComponent( imgsrc ) + '&description=' + encodeURIComponent( title );
												window.open( sharelink, '_blank' );
											}
											if ( $( this ).hasClass( 'mfp-share_stumbleupon' ) ) {
												sharelink = 'http://www.stumbleupon.com/submit?url=' + encodeURIComponent( url ) + '&title=' + encodeURIComponent( title );
												window.open( sharelink, '_blank' );
											}
										} );
									}
									this.toggleArrows.call( this );
								},
								close: function() {
									$( Container ).data( 'gmCubikLite_stop', false );
									$( Container ).trigger( 'mouseleave' );
									$( document.body ).removeClass( 'mfp-gmedia-open gmedia-cubik-lite' );
									if ( 'image' != this.currItem.mtype ) {
										$( document.body ).removeClass( 'mfp-zoom-out-cur' );
										this.wrap.removeClass( 'mfp-iframe-loaded' );
									}
									this.wrap.removeClass( 'mfp-image-loaded' );

									if ( opt.commentsEnabled ) {
										this.wrap.off( 'click.gmCloseComments' );
									}
									if ( opt.deepLinks ) {
										var hash = '#!',
											url = ('' + window.location).split( '#' )[0] + hash;
										if ( !! (window.history && window.history.replaceState) ) {
											window.history.replaceState( {}, document.title, url );
										} else {
											location.replace( url );
										}
									}
									$( '#wpadminbar' ).css( {'z-index': ''} );
									$( window ).scrollTop( scrollTop );
									itemLoaded = this.currItem.id;
								},
								beforeOpen: function() {
									$( Container ).data( 'gmCubikLite_stop', true );
									$( Container ).trigger( 'mouseleave' );
									$( '#wpadminbar' ).css( {'z-index': 1000} );
									$( this.wrap ).attr( 'id', 'mfp_gm_' + ID );
									$( this.bgOverlay ).attr( 'id', 'mfp_gm_' + ID + '_bg' );
									if ( opt.lightbox800HideArrows ) {
										this.wrap.addClass( 'mfp800-hide-arrows' );
									}
									if ( opt.mfp_css && ! $( '#mfp_css_' + ID, this.wrap ).length ) {
										this.wrap.append( '<style id="mfp_css_' + ID + '">' + opt.mfp_css + '</style>' );
									}
									scrollTop = $( window ).scrollTop();
								},
								change: function() {
									if ( opt.deepLinks ) {
										var hash = '#!gallery-' + ID + '-' + this.currItem.id,
											url = ('' + window.location).split( '#' )[0] + hash;
										if ( !! (window.history && window.history.replaceState) ) {
											window.history.replaceState( {}, document.title, url );
										} else {
											location.replace( url );
										}
									}
									this.contentContainer.attr( 'data-gmtype', this.currItem.mtype ).attr( 'data-ext', this.currItem.ext );
									if ( opt.commentsEnabled ) {
										this.wrap.removeClass( 'mfp-comments-open mfp-comments-loaded' );
										$( '.mfp-comments-wrapper', this.contentContainer ).css( {height: ''} ).empty();
									}

									clearTimeout( ticker );
									if ( 'image' != this.currItem.mtype ) {
										$( document.body ).addClass( 'mfp-zoom-out-cur' );
										var self = this;
										setTimeout( function() {
											self.wrap.addClass( 'mfp-iframe-loaded' );
										}, 16 );
									}
								},
								afterChange: function() {
									if ( opt.socialShareEnabled ) {
										methods.initSocialShare( this.currItem, this );
									}

									if ( opt.commentsEnabled ) {
										methods.initComments( this.currItem, this );
									}

									var self = this;
									ticker = setTimeout( function() {
										methods.viewLike( self.currItem );
									}, 1000 );

									methods.initLikes( this.currItem, this );
									if ( this.currItem.liked ) {
										this.wrap.addClass( 'gmCubikLite-liked' );
									} else {
										this.wrap.removeClass( 'gmCubikLite-liked' );
									}

									itemLoaded = this.currItem.id;
								},
								updateStatus: function( data ) {
									if ( data.status == 'loading' ) {
										this.wrap.removeClass( 'mfp-image-loaded mfp-iframe-loaded' );
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
									'               <div class="mfp-description gmCubikLite_thumbDetails"></div>' +
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
									'       <div class="mfp-description gmCubikLite_thumbDetails"></div>' +
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
								arrowMarkup: '<div title="%title%" type="button" class="mfp-button mfp-arrow mfp-arrow-%dir%"></div>',
								tCounter: '%curr% / %total%'
							},

							mainClass: 'mfp-zoom-in',
							removalDelay: 500 //delay removal by X to allow out-animation

						} );
					},
					loadGalleryDeepLink: function() {
						var prefix = '#!gallery-';
						var h = location.hash;
						if ( h.indexOf( prefix ) === 0 ) {
							h = h.substr( prefix.length ).split( '-' );
							if ( h[0] && h[0] == ID ) {
								$( document ).scrollTop( $( Container ).offset().top );
								if ( h[1] ) {
									var active = $( '.gmCubikLite_thumb[data-id="' + h[1] + '"]', Container );
									startWith = $( '.gmCubikLite_thumb', Container ).index( active );
								} else {
									startWith = 0;
								}
								if ( -1 !== startWith ) {
									$( '.gmCubikLite_thumbsWrapper', Container ).magnificPopup( 'open', startWith );
								}
							}
						}
					},
					initSocialShare: function( item, mfp ) {
						var share_buttons = '' +
							'<div class="mfp-prevent-close mfp-button mfp-share mfp-gmedia-stuff08" id="mfp_share_' + ID + '">' +
							'     <a title="Share">' +
							'         <span class="mfp-prevent-click">' +
							'             <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="gmCubikLite_svgicon" viewBox="0 0 32 32"><path class="path1" d="M27 22c-1.411 0-2.685 0.586-3.594 1.526l-13.469-6.734c0.041-0.258 0.063-0.522 0.063-0.791s-0.022-0.534-0.063-0.791l13.469-6.734c0.909 0.94 2.183 1.526 3.594 1.526 2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5c0 0.269 0.022 0.534 0.063 0.791l-13.469 6.734c-0.909-0.94-2.183-1.526-3.594-1.526-2.761 0-5 2.239-5 5s2.239 5 5 5c1.411 0 2.685-0.586 3.594-1.526l13.469 6.734c-0.041 0.258-0.063 0.522-0.063 0.791 0 2.761 2.239 5 5 5s5-2.239 5-5c0-2.761-2.239-5-5-5z"></path></svg>' +
							'         </span>' +
							'     </a>' +
							'     <ul class="mfp-prevent-close mfp-share_sharelizers">' +
							'         <li><a class="mfp-share_facebook mfp-share_sharelizer">Facebook</a></li>' +
							'         <li><a class="mfp-share_twitter mfp-share_sharelizer">Twitter</a></li>' +
							'         <li><a class="mfp-share_pinterest mfp-share_sharelizer">Pinterest</a></li>' +
							'         <li><a class="mfp-share_stumbleupon mfp-share_sharelizer">StumbleUpon</a></li>' +
							'     </ul>' +
							'</div>';

						var mfp_share = $( share_buttons );
						mfp_share.on( 'click', function() {
							$( this ).toggleClass( 'mfp-share_open' );
						} );

						var share_div = mfp.wrap.find( '#mfp_share_' + ID );
						if ( ! share_div.length ) {
							$( '.mfp-buttons-bar', mfp.wrap ).append( mfp_share );
						} else {
							$( share_div ).replaceWith( mfp_share );
						}
					},
					initComments: function( item, mfp ) {
						var comments_button = '' +
							'<div class="mfp-prevent-close mfp-button mfp-comments" id="mfp_comments_' + ID + '">' +
							'   <a title="Comments"><span class="mfp-prevent-close mfp-comments-count">' + item['cc'] + '</span>' +
							'      <span class="mfp-prevent-click mfp_comments_icon">' +
							'          <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="gmCubikLite_svgicon" viewBox="0 0 36 32"><path class="path1" d="M15 0v0c8.284 0 15 5.435 15 12.139s-6.716 12.139-15 12.139c-0.796 0-1.576-0.051-2.339-0.147-3.222 3.209-6.943 3.785-10.661 3.869v-0.785c2.008-0.98 3.625-2.765 3.625-4.804 0-0.285-0.022-0.564-0.063-0.837-3.392-2.225-5.562-5.625-5.562-9.434 0-6.704 6.716-12.139 15-12.139zM31.125 27.209c0 1.748 1.135 3.278 2.875 4.118v0.673c-3.223-0.072-6.181-0.566-8.973-3.316-0.661 0.083-1.337 0.126-2.027 0.126-2.983 0-5.732-0.805-7.925-2.157 4.521-0.016 8.789-1.464 12.026-4.084 1.631-1.32 2.919-2.87 3.825-4.605 0.961-1.84 1.449-3.799 1.449-5.825 0-0.326-0.014-0.651-0.039-0.974 2.268 1.873 3.664 4.426 3.664 7.24 0 3.265-1.88 6.179-4.82 8.086-0.036 0.234-0.055 0.474-0.055 0.718z"></path></svg>' +
							'      </span>' +
							'   </a>' +
							'</div>';
						var mfp_comments = $( comments_button );
						mfp_comments.on( 'click', function() {
							var comments_wrapper = $( '.mfp-comments-wrapper', mfp.contentContainer );
							if ( mfp.wrap.hasClass( 'mfp-comments-open' ) ) {
								$( this ).removeClass( 'mfp-button-active' );
								comments_wrapper.css( {height: ''} );
								$( 'figure > img', mfp.wrap ).removeClass( 'mfp-img--comments-div' ).addClass( 'mfp-img' );
								mfp.wrap.removeClass( 'mfp-comments-open' );
								mfp.wrap.find( '.gmCubikLite_button_comment' ).removeClass( 'mfp-comments_open' );
								if ( $( window ).width() <= 800 ) {
									$( '.mfp-wrap' ).animate( {scrollTop: 0}, 200 );
									$( 'body' ).delay( 200 ).animate( {scrollTop: $( '#mfp_gm_' + ID ).offset().top}, 400 );
								} else {
									$( window ).scrollTop( $( '#mfp_gm_' + ID ).offset().top );
								}
							} else {
								$( this ).addClass( 'mfp-button-active' );
								var scrlbar = methods.scrollbarWidth();
								comments_wrapper.css( {height: $( 'iframe', comments_wrapper ).height()} ).parent().css( {'width': 'calc(100% + ' + scrlbar + 'px)', 'min-width': 320 + scrlbar + 'px'} );
								$( 'figure > img', mfp.wrap ).removeClass( 'mfp-img' ).addClass( 'mfp-img--comments-div' );
								mfp.wrap.addClass( 'mfp-comments-open' );
								mfp.wrap.find( '.gmCubikLite_button_comment' ).addClass( 'mfp-comments_open' );
								if ( ! mfp.wrap.hasClass( 'mfp-comments-loaded' ) ) {
									methods.loadComments( item, mfp );
								}
								if ( $( window ).width() <= 800 ) {
									$( '.mfp-wrap' ).animate( {scrollTop: ($( '.mfp-comments-container', '#mfp_gm_' + ID ).offset().top + $( '.mfp-wrap' ).scrollTop() - 150)}, 200 );
									$( 'body' ).delay( 200 ).animate( {scrollTop: ($( '.mfp-comments-container', '#mfp_gm_' + ID ).offset().top - 150)}, 400 );
								}
							}
						} );

						var comments_div = $( mfp.wrap ).find( '#mfp_comments_' + ID );
						if ( ! comments_div.length ) {
							$( '.mfp-buttons-bar', mfp.wrap ).append( mfp_comments );
						} else {
							$( comments_div ).replaceWith( mfp_comments );
						}

					},
					loadComments: function( item, mfp ) {
						var comments_content = $( '.mfp-comments-content', mfp.contentContainer ),
							comments_wrapper = $( '.mfp-comments-wrapper', comments_content );
						comments_wrapper.empty();
						mfp.wrap.removeClass( 'mfp-comments-loaded' ).addClass( 'mfp-comments-loading' );
						if ( GmediaGallery ) {
							opt.ajax_actions['comments']['data']['post_id'] = item['post_id'];
							$.ajax( {
								type: 'post',
								dataType: 'json',
								url: GmediaGallery.ajaxurl,
								data: {action: opt.ajax_actions['comments']['action'], _ajax_nonce: GmediaGallery.nonce, data: opt.ajax_actions['comments']['data']}
							} ).done( function( data ) {
								if ( data.comments_count ) {
									mfp.wrap.find( '.mfp-comments-count' ).html( data.comments_count );
									item['cc'] = data.comments_count;
								}
								if ( data.content ) {
									$( '.mfp-comments-wrapper', comments_content ).html( data.content ).find( 'iframe' ).on( 'load', function() {
										mfp.wrap.removeClass( 'mfp-comments-loading' ).addClass( 'mfp-comments-loaded' );
										var body = this.contentWindow.document.body, html = this.contentWindow.document.documentElement;
										var height = Math.max( body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight );
										$( this ).css( {height: 20 + height, overflowY: 'hidden'} ).parent().css( {height: 20 + height} );
										this.contentWindow.onbeforeunload = function() {
											mfp.wrap.removeClass( 'mfp-comments-loaded' ).addClass( 'mfp-comments-loading' );
										};
									} );
								}
							} ).fail( function() {
								mfp.wrap.removeClass( 'mfp-comments-loading' );
							} );
						}
					},
					initLikes: function( item, mfp ) {
						if ( mfp.currItem.liked ) {
							mfp.wrap.addClass( 'gmCubikLite-liked' );
						}

						var likes = '' +
							'<div class="mfp-prevent-close mfp-button mfp-likes" id="mfp_likes_' + ID + '">' +
							'   <a><span class="mfp-likes-count">' + item['likes'] + '</span>' +
							'      <span class="mfp-prevent-click mfp_likes_icon">' +
							'          <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="gmCubikLite_svgicon" viewBox="0 0 32 32"><path class="path1" d="M23.6 2c-3.363 0-6.258 2.736-7.599 5.594-1.342-2.858-4.237-5.594-7.601-5.594-4.637 0-8.4 3.764-8.4 8.401 0 9.433 9.516 11.906 16.001 21.232 6.13-9.268 15.999-12.1 15.999-21.232 0-4.637-3.763-8.401-8.4-8.401z"></path></svg>' +
							'      </span>' +
							'   </a>' +
							'</div>';
						var likes_obj = $( likes );
						likes_obj.on( 'click', function() {
							methods.viewLike( mfp.currItem, true );
							mfp.wrap.addClass( 'gmCubikLite-liked' );
							$( '.mfp-likes-count', mfp.wrap ).text( item['likes'] );
						} );

						var likes_div = $( mfp.wrap ).find( '#mfp_likes_' + ID );
						if ( ! likes_div.length ) {
							$( '.mfp-buttons-bar', mfp.wrap ).append( likes_obj );
						} else {
							$( likes_div ).replaceWith( likes_obj );
						}

					},
					viewLike: function( item, like ) {
						var id = parseInt( item.id );
						if ( ! item.viewed ) {
							item.viewed = true;
							item['views'] += 1;
							Storage.views.push( id );
							sessionStorage.setItem( 'GmediaGallery', JSON.stringify( Storage ) );
							if ( GmediaGallery.ajaxurl ) {
								$.ajax( {
									type: 'post',
									dataType: 'json',
									url: GmediaGallery.ajaxurl,
									data: {action: 'gmedia_module_interaction', hit: id}
								} ).done( function( r ) {
									if ( r && r.views ) {
										item.views = r.views;
										$( '.mfp-views-count', item.el ).text( item['views'] );
									}
								} );
							}
						}
						if ( like && ! item.liked ) {
							item.liked = true;
							item.likes += 1;
							Storage.likes.push( id );
							sessionStorage.setItem( 'GmediaGallery', JSON.stringify( Storage ) );
							$( '.mfp-likes-count', item.el ).text( item['likes'] );
							if ( GmediaGallery.ajaxurl ) {
								$.ajax( {
									type: 'post',
									dataType: 'json',
									url: GmediaGallery.ajaxurl,
									data: {action: 'gmedia_module_interaction', hit: id, vote: 1}
								} ).done( function( r ) {
									if ( r && r.likes ) {
										item.likes = r.likes;
										$( '.mfp-likes-count', item.el ).text( item['likes'] );
									}
								} );
							}
						}
					},
					initSettings: function() {// Init Settings
						if ( window.sessionStorage ) {
							var sesion_storage = sessionStorage.getItem( 'GmediaGallery' );
							if ( sesion_storage ) {
								$.extend( true, Storage, JSON.parse( sesion_storage ) );
							}
						}
						methods.initThumbs();
					},
					cubikResize: function() {
						var c_width = $( Container ).width();
						if ( c_width ) {
							if ( ! opt.maxSize ) {
								var width = c_width / 1.5;
								var padding = width / 4;
								var perspective = width * 2 + opt.faceMarging * 4;

								$( '.gmCubikLite', Container ).css( {width: width + 'px', padding: padding + 'px'} );
								$( '.gmCubikLite_thumbsContainer', Container )[0].style[perspectiveProp] = perspective + 'px';
							} else {
								if ( c_width < (opt.maxSize + opt.maxSize / 2) ) {
									var width = c_width / 1.5;
									var padding = width / 4;
									var perspective = width * 2 + opt.faceMarging * 4;

									$( '.gmCubikLite', Container ).css( {width: width + 'px', padding: padding + 'px'} );
									$( '.gmCubikLite_thumbsContainer', Container )[0].style[perspectiveProp] = perspective + 'px';
								} else {
									$( '.gmCubikLite', Container ).css( {width: null, padding: null} );
									$( '.gmCubikLite_thumbsContainer', Container )[0].style[perspectiveProp] = null;
								}
							}
						}
					},
					rpResponsive: function() {
						var hiddenBustedItems;
						setTimeout( function() {
							hiddenBustedItems = prototypes.doHideBuster( $( Container ) );
						}, 0 );
						setTimeout( function() {
							opt.width = $( Container ).width();
							prototypes.undoHideBuster( hiddenBustedItems );
						}, 0 );
					},
					initThumbs: function() {//Init Thumbnails
						var thumb_container = $( '.gmCubikLite_face .gmCubikLite_thumb', Container );
						thumb_container.on( 'click', function( e ) {
							var link = $( this ).data( 'link' ),
								target = $( this ).data( 'target' );
							if ( link && opt.thumb2link ) {
								e.stopPropagation();
								prototypes.openLink( link, target );
								return false;
							}
						} );
						setTimeout( function() {
							$( '.gmCubikLite_thumb a', thumb_container ).off( 'click' ).on( 'click', function( e ) {
								e.stopPropagation();
								e.preventDefault();
								$( this ).parent().trigger( 'click' );
								return false;
							} );
						}, 1 );

						$( '.gmCubikLite_thumbLoader .gmCubikLite_thumbImg img', Container ).css( {opacity: 0} ).each( function() {
							var image = $( this );
							var img_holder = image.closest( '.gmCubikLite_thumb' );
							var load_img = new Image();
							load_img.onload = function() {
								img_holder.removeClass( 'gmCubikLite_thumbLoader' );
								image.animate( {opacity: opt.thumbAlpha / 100}, 600, function() {
									$( this ).css( {opacity: ''} );
								} );
							};
							load_img.src = image.attr( 'src' );
						} );

					},
					scrollbarWidth: function() {
						var div = $( '<div style="position:absolute;left:-200px;top:-200px;width:50px;height:50px;overflow:scroll"><div>&nbsp;</div></div>' ).appendTo( 'body' ),
							width = 50 - div.children().innerWidth();
						div.remove();
						return width;
					}
				},

				prototypes = {
					isTouchDevice: function() {// Detect Touchscreen devices
						return 'ontouchend' in document;
					},
					openLink: function( url, target ) {// Open a link.
						switch ( target.toLowerCase() ) {
							case '_blank':
								window.open( url );
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
					arrVal: function( arr, val ) {
						return $.inArray( val, arr ) !== -1 ? val : false;
					},
					$_GET: function( variable ) {
						var url = window.location.href.split( '?' )[1];
						if ( url ) {
							url = url.split( '#' )[0];
							var variables = (typeof (url) === 'undefined') ? [] : url.split( '&' ),
								i;

							for ( i = 0; i < variables.length; i++ ) {
								if ( variables[i].indexOf( variable ) != -1 ) {
									return variables[i].split( '=' )[1];
								}
							}
						}

						return false;
					},
					doHideBuster: function( item ) {// Make all parents & current item visible
						var parent = item.parent(),
							items = [];

						if ( typeof (item.prop( 'tagName' )) !== 'undefined' && item.prop( 'tagName' ).toLowerCase() != 'body' ) {
							items = this.doHideBuster( parent );

							item.addClass( 'gmShowBuster' );
							items.push( item );
						}

						return items;
					},
					undoHideBuster: function( items ) {// Hide items in the array
						var i;

						for ( i = 0; i < items.length; i++ ) {
							items[i].removeClass( 'gmShowBuster' );
						}
					}
				};

			return methods.init.apply( this, arguments );
		};
	})( jQuery, window, document );
}
