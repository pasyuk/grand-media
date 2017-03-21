/**
 * gmedia Globals
 * @var GmediaGallery
 */
var ajaxurl = GmediaGallery.ajaxurl;
jQuery(function($) {
    $('style.gmedia_assets_style_import').appendTo('head');
    $('style.gmedia_module_style_import').appendTo('head');

    $('script.gm_script2html').each(function(){
        var html = $(this).html();
        $(this).replaceWith(html);
    });

    setTimeout(function(){
        $('a[download]').off('click');
    }, 50);
});