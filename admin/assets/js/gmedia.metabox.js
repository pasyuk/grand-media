var gmActiveEditor = false;
(function( $, window, document, undefined ) {

	// $('body').on('click', 'textarea.wp-editor-area', function() {
	//   gmActiveEditor = $(this).attr('id');
	//   setTimeout(function() {
	//     gm_check_scode(gmActiveEditor);
	//   }, 10);
	// });
	//
	// $('#gmedia-wraper').resizable({
	//   handles: 'e',
	//   start: function(event, ui) {
	//     $('iframe').css('pointer-events', 'none');
	//   },
	//   stop: function(event, ui) {
	//     $('iframe').css('pointer-events', 'auto');
	//     $('#gmedia-images-wrap').trigger('scroll');
	//   },
	//   resize: function(event, ui) {
	//     ui.element.height('auto');
	//   }
	// });
	// $('#gmedia-galleries').resizable({
	//   handles: 's',
	//   minHeight: 94,
	//   start: function(event, ui) {
	//     $('iframe').css('pointer-events', 'none');
	//   },
	//   stop: function(event, ui) {
	//     $('iframe').css('pointer-events', 'auto');
	//     ui.element.width('auto');
	//   }
	// });
	//
	// $('li.gmedia-gallery-li').click(function(e) {
	//   if ($(e.target).hasClass('gmedia-gallery-gear')) {
	//     return;
	//   }
	//   var m, html, gm_id = $(this).attr('id').split('-')[1];
	//   if ($(this).hasClass('gmedia-selected')) {
	//     if (gm_delete_scode(gm_id)) {
	//       $(this).removeClass('gmedia-selected');
	//     }
	//     else {
	//       // Show message "Focus textarea"
	//       m = $('#gmedia-message .info-textarea');
	//       m.slideDown(100, function() {
	//         setTimeout(function() {
	//           m.slideUp(100);
	//         }, 1000);
	//       });
	//     }
	//   }
	//   else {
	//     html = '[gmedia id=' + gm_id + ']';
	//     if (gm_send_to_editor(html)) {
	//       $(this).addClass('gmedia-selected');
	//     }
	//     else {
	//       m = $('#gmedia-message .info-textarea');
	//       m.slideDown(100, function() {
	//         setTimeout(function() {
	//           m.slideUp(100);
	//         }, 1000);
	//       });
	//     }
	//   }
	//   e.preventDefault();
	// });
	//
	// setTimeout(function() {
	//   gm_check_scode(false);
	// }, 1000);
	//
	// $('textarea.wp-editor-area').on('keyup', function(e) {
	//   var k = e.keyCode || e.charCode;
	//   if (k == 8 || k == 13 || k == 46) {
	//     var m, content = $(this).val();
	//     m = content.match(/\[gmedia [ ]*id=(\d+)[ ]*?\]/g);
	//     jQuery('#gmedia-galleries-list
	// li.gmedia-gallery-li').removeClass('gmedia-selected'); if (m) {
	// jQuery.each(m, function(i, shcode) { var id = shcode.replace(/\[gmedia [
	// ]*id=(\d+)[ ]*?\]/, '$1'); jQuery('#gmGallery-' +
	// id).addClass('gmedia-selected'); }); } } });

	$( document ).on( 'click', '#gmedia-modal', function( e ) {
		e.stopPropagation();
		e.preventDefault();
		gmedia_post_modal( this );
	} );

	$( '#postimagediv' ).
			on( 'click', '#set-gmedia-post-thumbnail', function( e ) {
				e.stopPropagation();
				e.preventDefault();
				gmedia_post_modal();
				var modal = $( '#__gm-uploader' );
				var library = modal.find( '#gmedia-modal-library' );
				if ( ! library.hasClass( 'active' ) ) {
					var ifr = modal.find( 'iframe' ).clone();
					ifr.attr( 'src', library.attr( 'href' ) );
					library.trigger( 'click' );
					modal.find( 'iframe' ).replaceWith( ifr );
				}
			} );

})( jQuery, window, document, undefined );

function gmedia_post_modal( el ) {
	var modal = jQuery( '#__gm-uploader' );

	if ( modal.length ) {
		modal.css( 'display', 'block' );
	}
	else {
		//var title = jQuery(el).attr('title');
		modal = jQuery( jQuery( '#tpl__gm-uploader' ).html() );
		modal.find( '.media-modal-close, .media-modal-backdrop' ).
				on( 'click', function() {
					modal.css( 'display', 'none' );
				} );
		modal.find( '.media-menu-item' ).on( 'click', function() {
			jQuery( 'iframe', modal ).attr( 'src', jQuery( this ).attr( 'href' ) );
			jQuery( this ).
					addClass( 'active' ).
					siblings( 'a' ).
					removeClass( 'active' );
			jQuery( '.media-frame-title h1', modal ).text( jQuery( this ).text() );
		} );
		jQuery( 'body' ).append( modal );
	}
}

// function gm_check_scode(id) {
//   if (!id && ('undefined' != typeof (tinymce)) && tinymce.activeEditor) {
//     id = tinymce.activeEditor.id;
//   }
//   if (!id) {
//     id = 'content';
//   }
//   var temp_html = jQuery('textarea#' + id).val();
//   if (temp_html) {
//     var temp_m = temp_html.match(/\[gmedia [ ]*id=(\d+)[ ]*?]/g);
//     if (temp_m) {
//       jQuery.each(temp_m, function(i, shcode) {
//         var id = shcode.replace(/\[gm.*id=(\d+).*?]/, '$1');
//         jQuery('#gmGallery-' + id).addClass('gmedia-selected');
//       });
//     }
//   }
// }
//
// function gm_send_to_editor(c) {
//   var b, a = typeof (tinymce) != 'undefined', f = typeof (QTags) !=
// 'undefined'; if (!wpActiveEditor) { if (a && tinymce.activeEditor) { b =
// tinymce.activeEditor; wpActiveEditor = b.id; } else { if (gmActiveEditor) {
// wpActiveEditor = gmActiveEditor; } else { return false; } } } else { if (a)
// { if (tinymce.activeEditor && (tinymce.activeEditor.id == 'mce_fullscreen'
// || tinymce.activeEditor.id == 'wp_mce_fullscreen')) { b =
// tinymce.activeEditor; } else { b = tinymce.get(wpActiveEditor); } } } if (b
// && !b.isHidden()) { //noinspection JSUnresolvedVariable if (tinymce.isIE &&
// b.windowManager.insertimagebookmark) { //noinspection JSUnresolvedVariable
// b.selection.moveToBookmark(b.windowManager.insertimagebookmark); } if
// (c.indexOf('[caption') === 0) { //noinspection JSUnresolvedVariable if
// (b.plugins.wpeditimage) { //noinspection JSUnresolvedVariable c =
// b.plugins.wpeditimage._do_shcode(c); } } else { c = '<p>' + c + '</p>'; }
// b.execCommand('mceInsertContent', false, c); } else { if (f) {
// QTags.insertContent(c); } else {
// document.getElementById(wpActiveEditor).value += c; } } return true; }
// function gm_delete_scode(c) { var b, a = typeof (tinymce) != 'undefined',
// re, html; if (!wpActiveEditor) { if (a && tinymce.activeEditor) { b =
// tinymce.activeEditor; wpActiveEditor = b.id; } else { if (gmActiveEditor) {
// wpActiveEditor = gmActiveEditor; } else { return false; } } } else { if (a)
// { if (tinymce.activeEditor && (tinymce.activeEditor.id == 'mce_fullscreen'
// || tinymce.activeEditor.id == 'wp_mce_fullscreen')) { b =
// tinymce.activeEditor; } else { b = tinymce.get(wpActiveEditor); } } } if (b
// && !b.isHidden()) { html = b.getContent(); re = new
// RegExp('(?:<p>)?\\[gmedia \\s*id=' + c + '\\s*?\\](?:<\\/p>)?', 'g');
// b.setContent(html.replace(re, '')); } else { html =
// document.getElementById(wpActiveEditor).value; re = new RegExp('\\[gmedia [
// ]*id=' + c + '[ ]*?\\](?:\\n\\n|\\n)?', 'g'); html = html.replace(re, '');
// document.getElementById(wpActiveEditor).value = html; } return true; }
// function gm_media_button(b) { var pos, el = jQuery(b).toggleClass('active');
// if (el.hasClass('active')) { pos = el.offset(); pos.top += el.height() + 1; var w = 300; jQuery('#gmedia-wraper').appendTo('body').css({'position': 'absolute', 'z-index': 99999, 'width': w}).offset(pos); jQuery('#gmedia-wraper').draggable({handle: '.title-bar'}); } else { jQuery('#gmedia-wraper').removeAttr('style').appendTo('#gmedia-MetaBox .inside'); jQuery('#gmedia-wraper').draggable('destroy'); } }
