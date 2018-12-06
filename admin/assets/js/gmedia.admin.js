/**
 * Gmedia Library
 */
var gmedia_DOM;
var GmediaLibrary = {
    init: function() {
        window.gm_wavesurfer = {};
        if(jQuery('.gmedia-audio-item').length) {

            jQuery('.gm-waveform-player').each(function() {
                var data = jQuery(this).data();
                data.gmid = data.id;
                data.id = 'ws' + data.gmid;

                if(data.peaks){
                    jQuery('.gm-play', this).show();
                    jQuery('.gm-pause', this).hide();

                    GmediaLibrary.waveplayer(data, true);
                }
            });
            gmedia_DOM.on('click', '.gm-waveform', function() {
                var parent = jQuery(this).parent(),
                    data = parent.data();

                jQuery(this).remove();

                GmediaLibrary.waveplayer(data);

                if(data.peaks) {
                    //window.gm_wavesurfer[data.id].play();
                } else {
                    window.gm_wavesurfer[data.id].on('waveform-ready', function() {
                        jQuery('.gm-play', parent).hide();
                        jQuery('.gm-pause', parent).show();
                        jQuery('.spinner', parent).removeClass('is-active');
                        var peaks = window.gm_wavesurfer[data.id].exportPCM(1800, 10000, true);
                        var post_data = {
                            action: 'gmedia_save_waveform',
                            id: data.gmid,
                            peaks: peaks,
                            _wpnonce: jQuery('#_wpnonce').val()
                        };
                        jQuery.post(ajaxurl, post_data, function(return_data, textStatus, jqXHR) {
                            var data_peaks = window.gm_wavesurfer[data.id].exportPCM(450, 10000, true);
                            parent.attr('data-peaks', data_peaks);
                        });
                    });
                }
            });
            gmedia_DOM.on('click', '.gm-play, .gm-pause', function() {
                var parent = jQuery(this).parent();
                var data = parent.data();
                if(!parent.hasClass('ws-loaded')) {
                    parent.addClass('ws-loaded');
                    window.gm_wavesurfer[data.id].load(data.file, data.peaks);
                    window.gm_wavesurfer[data.id].toggleInteraction();
                    window.gm_wavesurfer[data.id].play();
                } else {
                    window.gm_wavesurfer[data.id].playPause();
                }
            });

            var resize;
            jQuery(window).on('resize.gmedia', function(){
                clearTimeout(resize);
                resize = setTimeout(function(){
                    jQuery('.gm-waveform-player').each(function() {
                        var data = jQuery(this).data();
                        if(data.peaks && window.gm_wavesurfer[data.id]){
                            window.gm_wavesurfer[data.id].load(data.file, data.peaks);
                        }
                    });
                }, 500);
            });

        }

        if(jQuery('body').hasClass('GrandMedia_edit')) {
            GmediaLibrary.editmode();
        }

        if(jQuery('body').hasClass('gmedia_library')) {
            var previewFrame = jQuery('#previewFrame', window.parent.document);
            jQuery(window).on('load.gmedia', function(){
                setTimeout(function(){
                    previewFrame.animate({'height': getDocHeight('gmedia_iframe_content') + 3}, 200);
                }, 10);
            });
            var refresh = !jQuery('body').is('.GrandMedia_select_single, .GrandMedia_select_multiple');
            var observer = new MutationObserver(function(mutations) {
                previewFrame.height(getDocHeight('gmedia_iframe_content') + 3);
                if(refresh) {
                    jQuery('#previewModal', window.parent.document).attr('data-refresh', 'true');
                }
            });
            var gmedia_iframe_content = window.document.getElementById('gmedia_iframe_content');
            observer.observe(gmedia_iframe_content, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['value']
            });
        }

    },
    waveplayer: function(data, draw) {
        window.gm_wavesurfer[data.id] = Object.create(WaveSurfer);
        window.gm_wavesurfer[data.id].init({
            container: '#' + data.id,
            waveColor: '#428bca',
            progressColor: '#31708f',
            backend: 'MediaElement',
            renderer: 'Canvas',
            height: 60,
            interact: false,
            barWidth: 0
        });
        // Play on audio load
        var parent = jQuery(window.gm_wavesurfer[data.id].container).parent();

        if(!parent.hasClass('ws-loaded')) {
            if(draw) {
                window.gm_wavesurfer[data.id].backend.setPeaks(data.peaks);
                window.gm_wavesurfer[data.id].drawBuffer();
            } else {
                parent.addClass('ws-loaded');
                window.gm_wavesurfer[data.id].load(data.file, data.peaks);
                window.gm_wavesurfer[data.id].toggleInteraction();
                window.gm_wavesurfer[data.id].play();
            }
        } else {
            window.gm_wavesurfer[data.id].play();
        }

        jQuery(window.gm_wavesurfer[data.id].container).on('click', function(e) {
            if(!parent.hasClass('ws-loaded')) {
                parent.addClass('ws-loaded');
                window.gm_wavesurfer[data.id].load(data.file, data.peaks);
                window.gm_wavesurfer[data.id].toggleInteraction();
                window.gm_wavesurfer[data.id].play();
            }
            if(window.gm_wavesurfer[data.id].isPlaying()) {
                window.gm_wavesurfer[data.id].backend.media.currentTime = 0;
            } else{
                window.gm_wavesurfer[data.id].play();
            }
        });

        window.gm_wavesurfer[data.id].on('play', function() {
            parent.find('.gm-play').hide();
            parent.find('.gm-pause').show();
            parent.find('.spinner').removeClass('is-active');

            jQuery.each(window.gm_wavesurfer, function(id) {
                if(id !== data.id && window.gm_wavesurfer[id].isPlaying()) {
                    window.gm_wavesurfer[id].pause();
                }
            });
        })
        window.gm_wavesurfer[data.id].on('pause', function() {
            parent.find('.gm-play').show();
            parent.find('.gm-pause').hide();
        });
        window.gm_wavesurfer[data.id].on('loading', function(p) {
            if(p == 100) {
                //parent.find('.spinner').removeClass('is-active');
            } else {
                parent.find('.spinner').addClass('is-active');
            }
        });
    },
    /**
     * Edit Mode
     */
    editmode: function() {
        var focus_input_val;
        // SelectBox for albums
        var combobox_albums = jQuery('.combobox_gmedia_album');
        var selectize_albums = combobox_albums.selectize({
            create: (combobox_albums.data('create')? true : false),
            onOptionAdd: function(value, data){
                jQuery.each(selectize_albums, function(i, e){
                    e.selectize.options[value] = data;
                });
            },
            onFocus: function(){
                this.$input.addClass('edit-gmedia-ignore');
                focus_input_val = this.$input.val();
            },
            onBlur: function(){
                this.$input.removeClass('edit-gmedia-ignore');
                if(focus_input_val != this.$input.val()) {
                    var inporder = this.$input.prev('.gm-order-input');
                    inporder.val('0');
                    if('' == this.$input.val()){
                        inporder.prop('disabled', true);
                    } else {
                        inporder.prop('disabled', false);
                    }
                    this.$input.trigger('change');
                }
            },
            persist: true
        });

        if(window.gmedia_categories) {
            var categories = jQuery('.combobox_gmedia_category');
            if(categories.length) {
                var categories_data = window.gmedia_categories.map(function(x) {
                    return {item: x};
                });

                var selectize_categories = categories.selectize({
                    create: function(input) {
                        if(categories.data('create')) {
                            return {
                                item: input
                            }
                        } else {
                            return false;
                        }
                    },
                    onOptionAdd: function(value, data){
                        jQuery.each(selectize_categories, function(i, e){
                            e.selectize.options[value] = data;
                        });
                    },
                    onFocus: function(){
                        this.$input.addClass('edit-gmedia-ignore');
                        focus_input_val = this.$input.val();
                    },
                    onBlur: function(){
                        this.$input.removeClass('edit-gmedia-ignore');
                        if(focus_input_val != this.$input.val()) {
                            this.$input.trigger('change');
                        }
                    },
                    createOnBlur: true,
                    delimiter: ',',
                    maxItems: null,
                    openOnFocus: true,
                    persist: true,
                    options: categories_data,
                    labelField: 'item',
                    valueField: 'item',
                    searchField: ['item'],
                    hideSelected: true
                });
            }
        }
        if(window.gmedia_tags) {
            var tags = jQuery('.combobox_gmedia_tag');
            if(tags.length) {
                var tags_data = window.gmedia_tags.map(function(x) {
                    return {item: x};
                });

                var selectize_tags = tags.selectize({
                    create: function(input) {
                        if(this.$input.data('create')) {
                            var option = { item: input }
                            tags_data.push(option);
                            return option;
                        } else {
                            return false;
                        }
                    },
                    onOptionAdd: function(value, data){
                        jQuery.each(selectize_tags, function(i, e){
                            e.selectize.options[value] = data;
                        });
                    },
                    onFocus: function(){
                        this.$input.addClass('edit-gmedia-ignore');
                        focus_input_val = this.$input.val();
                    },
                    onBlur: function(){
                        this.$input.removeClass('edit-gmedia-ignore');
                        if(focus_input_val != this.$input.val()) {
                            this.$input.trigger('change');
                        }
                    },
                    createOnBlur: true,
                    delimiter: ',',
                    maxItems: null,
                    openOnFocus: true,
                    persist: true,
                    options: tags_data,
                    labelField: 'item',
                    valueField: 'item',
                    searchField: ['item'],
                    hideSelected: true
                });

            }
        }

        // Date/Time picker
        var gmedia_date_temp;
        jQuery('.input-group.gmedia_date').datetimepicker({format: 'YYYY-MM-DD HH:mm:ss', focusOnShow: true, ignoreReadonly: true, allowInputToggle: true}).on('dp.show', function() {
            gmedia_date_temp = jQuery('input', this).val();
        }).on('dp.hide', function() {
          if(jQuery('input', this).val() !== gmedia_date_temp) {
              jQuery('input', this).trigger('modified');
            }
        });

        // Mask for filename input
        var inp_filename = jQuery('input.gmedia-filename').not('[readonly]');
        if(inp_filename.length) {
            inp_filename.alphanum({
                allow: '-_',
                disallow: '',
                allowSpace: false,
                allowNumeric: true,
                allowUpper: true,
                allowLower: true,
                allowCaseless: true,
                allowLatin: true,
                allowOtherCharSets: false,
                forceUpper: false,
                forceLower: false,
                maxLength: NaN
            });
        }

        if(jQuery('#wp-link-wrap').parent().hasClass('hidden')){
            jQuery('#wp-link-backdrop, #wp-link-wrap').appendTo('body');
        }
        jQuery(document).on('click.gmedia', '.gmedia-custom-link', function(e) {
            var editorId = jQuery(this).attr('data-target');
            wpActiveEditor = true;
            wpLink.open(editorId);
            wpLink.gmediaCustomLinkTarget = editorId;
            jQuery('#wp-link-wrap').removeClass('has-text-field').addClass('gmLinkModal').find('.link-target').css('visibility','hidden');

            return false;
        });
        function closeLinkModal(){
            jQuery('#wp-link-wrap').removeClass('gmLinkModal').find('.link-target').removeAttr('style');
            wpLink.close();
        }
        jQuery(document).on('click.gmedia', '.gmLinkModal #wp-link-submit', function(e) {
            e.preventDefault ? e.preventDefault() : e.returnValue = false;
            e.stopPropagation();

            var link = wpLink.getAttrs();
            wpLink.textarea = jQuery('#' + wpLink.gmediaCustomLinkTarget);

            if (!link.href) {
                closeLinkModal();
                return;
            }
            wpLink.textarea.val(link.href).trigger('change');
            closeLinkModal();
        });
        jQuery(document).on('click.gmedia', '#wp-link-cancel, #wp-link-close, #wp-link-backdrop', function(e) {
            closeLinkModal()
        });

        var related_sortable = jQuery('.related-media-previews');
        if(related_sortable.length){
            related_sortable.sortable({
                items: '.gmedia-related-image',
                handle: '.image-wrapper',
                placeholder: 'gmedia-related-image',
                tolerance: 'pointer',
                //helper: 'clone',
                revert: true,
                forcePlaceholderSize: true,
                stop: function(event, ui){
                    console.log(ui);
                    ui.item.find('input').trigger('change');
                }
            });
        }


    }
};

/**
 * Gmedia AddMedia
 */
var GmediaAddMedia = {
    init: function() {

        if(jQuery('body').hasClass('GrandMedia_AddMedia')) {
            gmedia_DOM.on('change', '#uploader_runtime select', function() {
                if('html4' == jQuery(this).val()) {
                    jQuery('#uploader_chunking').addClass('hide');
                    jQuery('#uploader_urlstream_upload').addClass('hide');
                } else {
                    jQuery('#uploader_chunking').removeClass('hide');
                    jQuery('#uploader_urlstream_upload').removeClass('hide');
                }
            });
        }

        var albums = jQuery('select#combobox_gmedia_album');
        if(albums.length) {
            var albums_data = jQuery('option', albums);
            albums.selectize({
                create: function(input) {
                    if(albums.data('create')) {
                        return {
                            value: input,
                            text: input
                        }
                    } else {
                        return false;
                    }
                },
                createOnBlur: true,
                persist: false,
                render: {
                    item: function(item, escape) {
                        if(0 === (parseInt(item.value, 10) || 0)) {
                            return '<div>' + escape(item.text) + '</div>';
                        }
                        if(item.$order) {
                            var data = jQuery(albums_data[item.$order]).data();
                            return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small></div>';
                        }
                    },
                    option: function(item, escape) {
                        if(0 === (parseInt(item.value) || 0)) {
                            return '<div>' + escape(item.text) + '</div>';
                        }
                        if(item.$order) {
                            var data = jQuery(albums_data[item.$order]).data();
                            return '<div>' + escape(data.name) + ' <small>' + escape(data.meta) + '</small></div>';
                        }
                    }
                }
            });
        }

        if(window.gmedia_tags) {
            var tags = jQuery('#combobox_gmedia_tag');
            if(tags.length) {
                var tags_data = window.gmedia_tags.map(function(x) {
                    return {item: x};
                });

                tags.selectize({
                    create: function(input) {
                        if(tags.data('create')) {
                            return {
                                item: input
                            }
                        } else {
                            return false;
                        }
                    },
                    createOnBlur: true,
                    delimiter: ',',
                    maxItems: null,
                    openOnFocus: true,
                    persist: false,
                    options: tags_data,
                    labelField: 'item',
                    valueField: 'item',
                    searchField: ['item'],
                    hideSelected: true
                });
            }
        }
        if(window.gmedia_categories) {
            var categories = jQuery('#combobox_gmedia_category');
            if(categories.length) {
                var categories_data = window.gmedia_categories.map(function(x) {
                    return {item: x};
                });

                categories.selectize({
                    create: function(input) {
                        if(categories.data('create')) {
                            return {
                                item: input
                            }
                        } else {
                            return false;
                        }
                    },
                    createOnBlur: true,
                    delimiter: ',',
                    maxItems: null,
                    openOnFocus: true,
                    persist: false,
                    options: categories_data,
                    labelField: 'item',
                    valueField: 'item',
                    searchField: ['item'],
                    hideSelected: true
                });
            }
        }


    },
    /**
     * Gmedia Import
     */
    importmode: function() {
    }
};

/**
 * Gmedia Terms
 */
var GmediaTerms = {
    init: function(){

        if(jQuery('body').hasClass('GrandMedia_Tags')){
            jQuery('#gm-list-table').data('edit', false);
            gmedia_DOM.on('keypress', 'input.edit_tag_input', function(e){
                var tagdiv = jQuery('#tag_' + jQuery(this).data('tag_id'));
                var charCode = e.charCode || e.keyCode || e.which;
                if(charCode == 13){
                    e.preventDefault();
                    edit_tag(tagdiv);
                }
            }).on('blur', 'input.edit_tag_input', function(){
                var tagdiv = jQuery('#tag_' + jQuery(this).data('tag_id'));
                edit_tag(tagdiv);
            });

            gmedia_DOM.on('click', '.edit_tag_link', function(e){
                e.preventDefault();
                var id = jQuery(this).attr('href');
                jQuery(this).hide();
                jQuery(id).find('.edit_tag_form').show().find('input').focus();
                jQuery('#gm-list-table').data('edit', true);
            });
            gmedia_DOM.on('click', '.edit_tag_save', function(e){
                e.preventDefault();
            });

            function edit_tag(tagdiv){
                var inp = tagdiv.find('.edit_tag_form input');
                var new_tag_name = jQuery.trim(inp.val());
                var old_tag_name = inp.attr('placeholder');
                if((old_tag_name == new_tag_name) || ('' === new_tag_name) || jQuery.isNumeric()){
                    inp.val(old_tag_name);
                    tagdiv.find('.edit_tag_form').hide();
                    tagdiv.find('.edit_tag_link').show();
                    return;
                }
                var post_data = {
                    action: 'gmedia_tag_edit',
                    tag_id: inp.data('tag_id'),
                    tag_name: new_tag_name,
                    _wpnonce_terms: jQuery('#_wpnonce_terms').val()
                };
                jQuery.post(ajaxurl, post_data, function(data, textStatus, jqXHR){
                    console.log(data);
                    if(data.error){
                        //inp.val(inp.attr('placeholder'));
                        jQuery('#gmedia-panel').before(data.error);
                    } else{
                        //new_tag_name = new_tag_name.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        inp.attr('placeholder', new_tag_name);
                        tagdiv.find('.edit_tag_link').text(new_tag_name).show();
                        //noinspection JSUnresolvedVariable
                        jQuery('#gmedia-panel').before(data.msg);
                        tagdiv.find('.edit_tag_form').hide();
                    }
                });
            }
        }

        gmedia_DOM.on('click', '.term-shortcode input', function(){
            this.setSelectionRange(0, 0);
            this.setSelectionRange(0, this.value.length);
        });
        gmedia_DOM.on('change', '.term-shortcode input', function(){
            shortcode_inp_autowidth(this);
        });
        jQuery('.term-shortcode input', gmedia_DOM).each(function(i, e){
            shortcode_inp_autowidth(this)
        });

        function shortcode_inp_autowidth(e){
            var inp = jQuery(e),
                buffer = inp.next('.input-buffer');
            buffer.text(inp.val());
            inp.width(buffer.width());
        }

        var sortable = jQuery('#gm-sortable');
        if(sortable.length && !jQuery('#gmedia-panel', sortable).hasClass('gmedia-filtered')){
            var sortdiv = jQuery('#gm-list-table', sortable);
            var post_data = sortable.data();
            post_data['idx0'] = parseInt(sortdiv.attr('data-idx0'));

            var _ids = [];
            jQuery('.gm-item-cell', sortdiv).each(function(index){
                _ids.push(jQuery(this).attr('data-id'));
            });
            sortdiv.sortable({
                items: '.gm-item-cell',
                handle: '.cb_media-object',
                placeholder: 'cb_list-item gm-item-cell col-xs-6 col-sm-4 col-md-3 col-lg-2 gmedia-image-item ui-highlight-placeholder',
                tolerance: 'pointer',
                helper: 'clone',
                revert: true,
                forcePlaceholderSize: true,
                stop: function(event, ui){
                    var ids = [];
                    jQuery('.gm-item-cell', sortdiv).each(function(index){
                        ids.push(jQuery(this).attr('data-id'));
                    });

                    if(_ids.toString() != ids.toString()){
                        _ids = ids;
                        jQuery('.panel-heading .spinner', sortable).addClass('is-active');
                        post_data['ids'] = ids
                        jQuery.post(ajaxurl, post_data, function(data, textStatus, jqXHR){
                            jQuery('.panel-heading .spinner', sortable).removeClass('is-active');
                            console.log(data);
                        });
                    }
                }
            });
        }

        gmedia_DOM.on('change', '#gmedia_term_orderby', function(){
            if('custom' == jQuery(this).val()){
                jQuery('#gmedia_term_order').val('ASC').addClass('disabled');
            } else{
                jQuery('#gmedia_term_order').removeClass('disabled');
            }
        });

        // Date/Time picker
        if(jQuery.fn.datetimepicker){
            jQuery('.input-group.gmedia_date').datetimepicker({format: 'YYYY-MM-DD HH:mm:ss', focusOnShow: false});
        }

    }
};

var GmediaSelect = {
    msg_stack: function(global) {
        var gm_cb = jQuery('.gm-stack input');
        var sel = jQuery('#gm-stack');
        if(!sel.length) {
            return;
        }

        var arr = sel.val().split(',');
        arr = jQuery.grep(arr, function(e) {
            return (e);
        });

        if(global) {
            var cur = false;
            gm_cb.each(function() {
                cur = jQuery(this);
                if(cur.is(':checked') && (jQuery.inArray(cur.val(), arr) === -1)) {
                    cur.prop('checked', false);
                } else if(!(cur.is(':checked')) && (jQuery.inArray(cur.val(), arr) !== -1)) {
                    cur.prop('checked', true);
                }
            });
        }

        if(sel.data('userid')) {
            var storedData = getStorage();
            storedData.set(sel.data('key'), arr.join('.'));
        }
        jQuery('#gm-stack-qty').text(arr.length);
        if(arr.length) {
            jQuery('#gm-stack-btn').removeClass('hidden');
            jQuery('.rel-stack-show').show();
            jQuery('.rel-stack-hide').hide();
        }
        else {
            jQuery('#gm-stack-btn').addClass('hidden');
            jQuery('.rel-stack-show').hide();
            jQuery('.rel-stack-hide').show();
        }
        sel.trigger('change');
    },
    msg_selected: function(obj, global) {
        var gm_cb = jQuery('.' + obj + ' input'),
            qty_v = gm_cb.length,
            sel_v = gm_cb.filter(':checked').length,
            c = jQuery('#cb_global');
        if((sel_v != qty_v) && (0 !== sel_v)) {
            c.css('opacity', '0.5').prop('checked', true);
        } else if((sel_v == qty_v) && (0 !== qty_v)) {
            c.css('opacity', '1').prop('checked', true);
        } else if(0 === sel_v) {
            c.css('opacity', '1').prop('checked', false);
        }

        var sel = jQuery('#gm-selected');
        if(!sel.length) {
            return;
        }

        var arr = sel.val().split(',');

        arr = jQuery.grep(arr, function(e) {
            return (e);
        });
        if(global) {
            var cur = false;
            gm_cb.each(function() {
                cur = jQuery(this);
                if(cur.is(':checked') && (jQuery.inArray(cur.val(), arr) === -1)) {
                    arr.push(cur.val());
                } else if(!(cur.is(':checked')) && (jQuery.inArray(cur.val(), arr) !== -1)) {
                    arr = jQuery.grep(arr, function(e) {
                        return e != cur.val();
                    });
                }
            });
            sel.val(arr.join(','));
        }

        if(sel.data('userid')) {
            var storedData = getStorage();
            storedData.set(sel.data('key'), arr.join('.'));
        }
        jQuery('#gm-selected-qty').text(arr.length);

        var selbtn = jQuery('#gm-selected-btn');
        if(arr.length) {
            selbtn.removeClass('hidden');
            jQuery('.rel-selected-show').show();
            jQuery('.rel-selected-hide').hide();
        }
        else {
            if(!selbtn.hasClass('gm-active')) {
                jQuery('#gm-selected-btn').addClass('hidden');
            }
            jQuery('.rel-selected-show').hide();
            jQuery('.rel-selected-hide').show();
        }
        sel.trigger('change');
    },
    chk_all: function(type, obj) {
        jQuery('.' + obj + ' input').filter(function() {
            return type? jQuery(this).data('type') == type : true;
        }).prop('checked', true).closest('.cb_list-item').addClass('gm-selected');
    },
    chk_none: function(type, obj) {
        jQuery('.' + obj + ' input').filter(function() {
            return type? jQuery(this).data('type') == type : true;
        }).prop('checked', false).closest('.cb_list-item').removeClass('gm-selected');
    },
    chk_toggle: function(type, obj) {
        if(type) {
            if(jQuery('.' + obj + ' input:checked').filter(function() {
                    return jQuery(this).data('type') == type;
                }).length) {
                GmediaSelect.chk_none(type, obj);
            } else {
                GmediaSelect.chk_all(type, obj);
            }
        } else {
            jQuery('.' + obj + ' input').each(function() {
                jQuery(this).prop("checked", !jQuery(this).prop("checked")).closest('.cb_list-item').toggleClass('gm-selected');
            });
        }
    },
    init: function() {
        var cb_obj = jQuery('#cb_global').data('group');

        if(jQuery('#gm-selected').length) {
            GmediaSelect.msg_selected(cb_obj);
            gmedia_DOM.on('click', '#gm-selected-clear', function(e) {
                jQuery('#gm-selected').val('');
                GmediaSelect.chk_none(false, cb_obj);
                GmediaSelect.msg_selected(cb_obj);
                e.preventDefault();
            });
            gmedia_DOM.on('click', '#gm-selected-show', function(e) {
                jQuery('#gm-selected-btn').submit();
                e.preventDefault();
            });
            gmedia_DOM.on('click', '#gm-stack-in', function(e) {
                e.preventDefault();
                var stack_obj = jQuery('#gm-stack'),
                    sel_obj = jQuery('#gm-selected'),
                    stack = stack_obj.val().split(','),
                    selected = sel_obj.val().split(','),
                    arr = stack.concat(selected);
                arr = jQuery.grep(arr, function(e) {
                    return (e);
                });
                arr = jQuery.unique(arr);
                stack_obj.val(arr.join(','));
                GmediaSelect.msg_stack(true);
                //sel_obj.val('');
                //GmediaSelect.chk_none(false, cb_obj);
                //GmediaSelect.msg_selected(cb_obj);
            });
            gmedia_DOM.on('click', '#gm-stack-out', function(e) {
                e.preventDefault();
                var stack_obj = jQuery('#gm-stack'),
                    sel_obj = jQuery('#gm-selected'),
                    stack = stack_obj.val().split(','),
                    selected = sel_obj.val().split(','),
                    arr = jQuery(stack).not(selected).get();
                arr = jQuery.grep(arr, function(e) {
                    return (e);
                });
                arr = jQuery.unique(arr);
                stack_obj.val(arr.join(','));
                GmediaSelect.msg_stack(true);
                //sel_obj.val('');
                //GmediaSelect.chk_none(false, cb_obj);
                //GmediaSelect.msg_selected(cb_obj);
            });
        }
        gmedia_DOM.on('click', '#cb_global', function() {
            if(jQuery(this).is(':checked')) {
                GmediaSelect.chk_all(false, cb_obj);
            } else {
                GmediaSelect.chk_none(false, cb_obj);
            }
            GmediaSelect.msg_selected(cb_obj, true);
        });
        gmedia_DOM.on('click', '#cb_global-btn li a', function(e) {
            var sel = jQuery(this).data('select');
            switch(sel) {
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
        gmedia_DOM.on('change', '.gm-item-check input:checkbox, .cb_object input:checkbox', function() {
            var selected = jQuery('#gm-selected'),
                arr = selected.val();
            var cur = jQuery(this).val();
            if(jQuery(this).is(':checked')) {
                if(arr) {
                    arr = arr + ',' + cur;
                } else {
                    arr = cur;
                }
            } else {
                arr = jQuery.grep(arr.split(','), function(a) {
                    return a != cur;
                }).join(',');
            }
            jQuery('#list-item-' + cur).toggleClass('gm-selected');
            selected.val(arr);
            GmediaSelect.msg_selected(cb_obj);
        });

        gmedia_DOM.on('click', '.gm-item-check input:radio', function() {
            var id = jQuery(this).val(), img, checked_thumb, data;
            jQuery('#list-item-' + id).addClass('gm-selected').siblings().removeClass('gm-selected');
            img = jQuery(this).closest('.thumbnail').find('.gmedia-thumb').clone();
            checked_thumb = jQuery('#gmedia-panel .panel-heading .checked_thumb');
            if(!checked_thumb.length){
                checked_thumb = jQuery('<div class="checked_thumb"></div>').appendTo(jQuery('#gmedia-panel .panel-heading'));
            }
            checked_thumb.html(img);
            data = {'id':id, 'src':img.attr('src')};
            window.parent.gmediaTempData = data;
        });

        if(jQuery('#gm-stack').length) {
            GmediaSelect.msg_stack();
            gmedia_DOM.on('click', '#gm-stack-clear', function(e) {
                jQuery('#gm-stack').val('');
                jQuery('.gm-stack input').prop('checked', false);
                GmediaSelect.msg_stack();
                e.preventDefault();
            });
            gmedia_DOM.on('click', '#gm-stack-show', function(e) {
                jQuery('#gm-stack-btn').submit();
                e.preventDefault();
            });

        }
        gmedia_DOM.on('change', '.gm-stack input:checkbox', function() {
            var selected = jQuery('#gm-stack'),
                arr = selected.val();
            var cur = jQuery(this).val();
            if(jQuery(this).is(':checked')) {
                if(arr) {
                    arr = arr + ',' + cur;
                } else {
                    arr = cur;
                }
            } else {
                arr = jQuery.grep(arr.split(','), function(a) {
                    return a != cur;
                }).join(',');
            }
            selected.val(arr);
            GmediaSelect.msg_stack();
        });

        gmedia_DOM.on('click', '.term-label', function(e) {
            if('DIV' == e.target.nodeName) {
                if(!jQuery('#gm-list-table').data('edit')) {
                    var cb = jQuery('input:checkbox', this);
                    cb.prop("checked", !cb.prop("checked")).change();
                    jQuery(this).closest('.term-list-item').toggleClass('gm-selected');
                } else {
                    jQuery('#gm-list-table').data('edit', false);
                }
            }
        });
    }
}

var GmediaFunction = {
    confirm: function(txt) {
        if(!txt) {
            return true;
        }
        var r = false;
        //noinspection UnusedCatchParameterJS
        try{
            r = confirm(txt);
        }
        catch(err){
            alert('Disable Popup Blocker');
        }
        return r;
    },
    init: function() {
        jQuery('#toplevel_page_GrandMedia').addClass('current').removeClass('wp-not-current-submenu');
        if(!("ontouchstart" in document.documentElement)) {
            jQuery('html').addClass('no-touch');
        }

         //jQuery(document).ajaxStart(function(a,b,c){
         //    //jQuery('body').addClass('gmedia-busy');
         //    jQuery('.panel-heading .spinner').addClass('is-active');
         //}).ajaxStop(function(){
         //    //jQuery('body').removeClass('gmedia-busy');
         //    jQuery('.panel-heading .spinner').removeClass('is-active');
         //});

        gmedia_DOM.on('click', '[data-confirm]', function() {
            return GmediaFunction.confirm(jQuery(this).data('confirm'));
        });

        jQuery(document).on('click.gmedia', '.gm_service_action', function() {
            var el = jQuery(this),
                service = jQuery(this).attr('data-action'),
                nonce = jQuery(this).attr('data-nonce');
            var post_data = {
                action: 'gmedia_application',
                service: service,
                _wpnonce: nonce
            };
            jQuery.post(ajaxurl, post_data, function(data, textStatus, jqXHR) {
                console.log(data);
                el.siblings('.spinner').removeClass('is-active');
                if(data.error) {
                    jQuery('#gmedia-service-msg-panel').prepend(data.error);
                } else if(data.message) {
                    jQuery('#gmedia-service-msg-panel').html(data.message);
                }
            });

            el.siblings('.spinner').addClass('is-active');
            jQuery('.gmedia-service__message').remove();
        });

        gmedia_DOM.on('click', '.show-settings-link', function(e) {
            e.preventDefault();
            jQuery('#show-settings-link').trigger('click');
        });

        gmedia_DOM.on('click', '.fit-thumbs', function(e) {
            e.preventDefault();
            jQuery(this).toggleClass('btn-success btn-default');
            jQuery('.display-as-grid').toggleClass('invert-ratio');
            jQuery.get(jQuery(this).attr('href'), {ajaxload: 1});
        });

        gmedia_DOM.on('click', '.gm-cell-more-btn, .gm-cell-title', function() {
            jQuery(this).parent().toggleClass('gm-cell-more-active');
        });

        jQuery('div.gmedia-modal', gmedia_DOM).each(function(){
            var id = jQuery(this).attr('id');
            jQuery('body').children('#' + id).remove();
            jQuery(this).appendTo('body');
        });
        gmedia_DOM.on('click', 'a.gmedia-modal', function(e) {
            jQuery('body').addClass('gmedia-busy');
            var modal_div = jQuery(jQuery(this).attr('href'));
            var post_data = jQuery(this).data();
            post_data['_wpnonce'] = jQuery('#_wpnonce').val();
            jQuery.post(ajaxurl, post_data, function(data, textStatus, jqXHR) {
                if(!data || ('-1' == data)) {
                    jQuery('body').removeClass('gmedia-busy');
                    alert(data);
                    return false;
                }
                jQuery('.modal-dialog', modal_div).html(data);
                modal_div.modal({
                    backdrop: 'static',
                    show: true,
                    keyboard: false
                }).one('hidden.bs.modal', function() {
                    jQuery('.modal-dialog', this).empty();
                });
                jQuery('body').removeClass('gmedia-busy');
            });
            e.preventDefault();
        });

        gmedia_DOM.on('click', 'a.gmedit-modal', function(e) {
            e.preventDefault();
            var modal_div = jQuery(jQuery(this).data('target'));
            jQuery('.modal-content', modal_div).html(
                jQuery('<iframe />', {
                    name: 'gmeditFrame',
                    id: 'gmeditFrame',
                    width: '100%',
                    height: '500',
                    src: jQuery(this).attr('href')
                }).css({display: 'block', margin: '4px 0'})
            );
            modal_div.modal({
                backdrop: true,
                show: true,
                keyboard: false
            }).one('hidden.bs.modal', function() {
                jQuery('.modal-content', this).empty();
            });
        });

        jQuery(document).on('click.gmedia', 'a.preview-modal', function(e) {
            e.preventDefault();
            var initiator = jQuery(this),
                data = initiator.data(),
                modal_div = jQuery(data['target']),
                modal_dialog = jQuery('.modal-dialog', modal_div),
                modal_body = jQuery('.modal-body', modal_div),
                modal_title = jQuery('.modal-title', modal_div);
            modal_title.text(jQuery(this).attr('data-title')? jQuery(this).attr('data-title') : jQuery(this).attr('title'));

            if(data['metainfo']) {
                modal_dialog.addClass('modal-md');
                modal_body.html(jQuery('#metainfo_' + data['metainfo']).html());
            } else {
                var r = data['width'] / data['height'],
                    w = Math.min(jQuery(window).width() * 0.98 - 32, data['width']),
                    h = w / r;
                modal_dialog.css({'width': (data['width'] + 32), 'max-width': '98%'});
                if(data['cls']){
                    modal_dialog.addClass(data['cls']);
                }
                modal_body.html(
                    jQuery('<iframe />', {
                        name: 'previewFrame',
                        id: 'previewFrame',
                        width: '100%',
                        height: h,
                        src: jQuery(this).attr('href')
                    }).on('load', function(){
                        jQuery(this.contentWindow.document.body).css('margin', 0);
                        jQuery('.modal-backdrop', modal_div).css({'width': (data['width'] + 32), 'min-width': '100%'});
                    }).css({display: 'block', margin: '4px 0'})
                );
            }

            initiator.addClass('previewModal_initiator');
            modal_div.modal({
                backdrop: true,
                show: true
            }).one('hidden.bs.modal', function() {
                if (jQuery('div.gmedia-modal:visible').length) {
                    jQuery('body').addClass('modal-open');
                }
                modal_title.empty();
                modal_body.empty();
                modal_dialog.removeAttr('style').attr('class', 'modal-dialog');
                if(modal_div.attr('data-refresh')){
                    modal_div.removeAttr('data-refresh');
                    jQuery('.panel-heading .spinner').addClass('is-active');
                    var url = window.location.href;
                    jQuery.get(url, function(data) {
                        jQuery('#gmedia-panel').html(jQuery('#gmedia-panel', data).html());
                        GmediaInit();
                        jQuery('.panel-heading .spinner').removeClass('is-active');
                    });
                }
                initiator.removeClass('previewModal_initiator');
            });
        });

        jQuery(document).on('click.gmedia', '#previewModal .select_gmedia_image .btn-primary', function() {
            var img, form = jQuery('.previewModal_initiator').closest('form');
            form.find('.gmedia-cover-id').val(window.gmediaTempData.id).trigger('change');
            img = form.find('.gmedia-cover-image img:first-child');
            if(img.length){
                img.attr('src', window.gmediaTempData.src);
            } else {
                jQuery('<img src="" alt="" />').attr('src', window.gmediaTempData.src).appendTo(form.find('.gmedia-cover-image'));
            }
            jQuery('#previewModal').modal('hide');
        });

        jQuery(document).on('click.gmedia', '#previewModal .select_gmedia_related .btn-primary', function() {
            var relatedDiv = jQuery('.previewModal_initiator').closest('.form-group').find('.related-media-previews');
            var fields = relatedDiv.find('input');
            var valData = [],
                getData = [];
            if(fields){
                fields.each(function(){
                    valData.push(jQuery(this).val());
                });
            }
            var storage = getStorage();
            storedData = storage.get('gmedia_library:frame').split('.');
            jQuery.each(storedData, function(i, id) {
                if(!id) {
                    return true;
                }
                if(jQuery.inArray(id, valData) === -1) {
                    getData.push(id);
                }
            });
            if(getData.length){
                jQuery.get(ajaxurl, {action: 'gmedia_get_data', gmedia__in: getData}, function(data, textStatus, jqXHR) {
                    if(jQuery.isArray(data) && data.length){
                        var thumbHTML
                        jQuery.each(data, function(i, item){
                            thumbHTML = '<p class="thumbnail gmedia-related-image">' +
                                            '<span class="image-wrapper"><img class="gmedia-thumb" src="" alt=""></span>' +
                                            '<span class="gm-remove">&times;</span>' +
                                            '<input type="hidden" name="meta[_related][]" value="">' +
                                        '</p>';
                            jQuery(thumbHTML).find('img').attr('src', item.url_thumb).end().find('input').val(item.ID).end().appendTo(relatedDiv);
                        });
                        relatedDiv.sortable( "refresh" );
                        relatedDiv.closest('form').find('input[name="title"]').trigger('change');
                    }
                });
            }
            storage.set('gmedia_library:frame', '');
            jQuery('#previewModal').modal('hide');
        });

        jQuery(document).on('click.gmedia', '.related-media-previews .gm-remove', function() {
            var inpTitle = jQuery(this).closest('form').find('input[name="title"]');
            jQuery(this).closest('.gmedia-related-image').remove();
            inpTitle.trigger('change');
        });

        jQuery(document).on('click.gmedia', '#previewModal .select_gmedia:not(.assign_gmedia_term) .btn-primary', function() {
            var field = jQuery('.previewModal_initiator').closest('.form-group').find('.form-control');
            var valData = field.val().split(',');
            var storedData = getStorage();
            storedData = storedData.get('gmedia_library:frame').split('.');
            valData = jQuery.grep(valData, function(e) {
                return e.trim();
            });
            jQuery.each(storedData, function(i, id) {
                if(!id) {
                    return true;
                }
                if(jQuery.inArray(id, valData) === -1) {
                    valData.push(id);
                }
            });
            field.val(valData.join(','));
            jQuery('#previewModal').modal('hide');
        });

        jQuery(document).on('click.gmedia', '#previewModal .assign_gmedia_term .btn-primary', function() {
            jQuery('.panel-heading .spinner').addClass('is-active');
            var url = window.location.href,
                post_data = jQuery('#gmedia-assign-term').serialize();
            jQuery.post(url, post_data, function(data) {
                jQuery('.gmedia_term__in').html(jQuery('.gmedia_term__in', data).html());
                GmediaInit();
                jQuery('.panel-heading .spinner').removeClass('is-active');
            });
            jQuery('#previewModal').modal('hide');
            //console.log(window.gmediaTempData);
        });

        jQuery(document).on('click.gmedia focus.gmedia', 'input.sharelink', function() {
            this.setSelectionRange(0, this.value.length);
        });
        jQuery(document).on('keyup.gmedia', 'input.sharetoemail', function() {
            jQuery('.sharebutton').prop('disabled', !validateEmail(this.value));
        });
        jQuery(document).on('click.gmedia', '.sharebutton', function() {
            var sharetoemail = jQuery('input.sharetoemail');
            if(!validateEmail(sharetoemail.val())) {
                sharetoemail.focus();
                sharetoemail.parent().addClass('has-error');
                return false;
            }
            var post_data = jQuery('#shareForm').serialize();
            jQuery.post(ajaxurl, post_data, function(data, textStatus, jqXHR) {
                jQuery('body').removeClass('gmedia-busy');
                if(data) {
                    jQuery('#gm-message').append(data);
                }
            });
            jQuery('#shareModal').modal('hide');
        });
        gmedia_DOM.on('click', 'a.share-modal', function(e) {
            e.preventDefault();
            var data = jQuery(this).data(),
                modal_div = jQuery(data['target']),
                postlink = jQuery(this).attr('href'),
                cloudlink = jQuery(this).attr('data-gmediacloud'),
                sharetoemail = jQuery('input.sharetoemail'),
                cloudlink_checked = false;

            if(postlink) {
                jQuery('.sharelink_post', modal_div).show();
                jQuery('.sharelink_post input[type="text"]', modal_div).val(postlink);
                jQuery('.sharelink_post a', modal_div).attr('href', postlink);
            } else {
                jQuery('.sharelink_post', modal_div).hide();
                jQuery('.sharelink_post input[type="radio"]', modal_div).prop('checked', false);
                cloudlink_checked = true;
            }
            if(cloudlink) {
                jQuery('.sharelink_page', modal_div).show();
                jQuery('.sharelink_page input[type="text"]', modal_div).val(cloudlink);
                jQuery('.sharelink_page a', modal_div).attr('href', cloudlink);
                if(cloudlink_checked) {
                    jQuery('.sharelink_page input[type="radio"]', modal_div).prop('checked', true);
                }
            } else {
                jQuery('.sharelink_page', modal_div).hide();
            }
            jQuery('.sharebutton').prop('disabled', !validateEmail(sharetoemail.val()));

            modal_div.modal({
                backdrop: true,
                show: true,
                keyboard: false
            }).one('shown.bs.modal', function() {
                jQuery('input.sharelink', this).focus();
            }).one('hidden.bs.modal', function() {
                jQuery('input.sharelink', this).val('');
            });
        });

        gmedia_DOM.on('click', '.buildquery-modal', function(e) {
            e.preventDefault();
            var data = jQuery(this).data(),
                modal_div = jQuery(jQuery(this).attr('href')),
                query_field = jQuery(jQuery(this).attr('id') + '_field');
            query = query_field.val();

            modal_div.modal({
                backdrop: true,
                show: true,
                keyboard: false
            }).one('shown.bs.modal', function() {
                if(query) {
                    query = gm_parse_query(query);
                    console.log(query);
                }
            }).one('hidden.bs.modal', function() {});
        });

        jQuery(document).on('click.gmedia', '.buildquerysubmit', function() {
            var qform = jQuery('#buildQuery :input').filter(function() {
                return !!jQuery(this).val();
            });

            qform = decodeURIComponent(qform.serialize());
            console.log(qform);
            jQuery('#build_query_field').val(qform);
            jQuery('#buildQuery').modal('hide');
        });
        gmedia_DOM.on('click', 'a.newcustomfield-modal', function(e) {
            e.preventDefault();
            var data = jQuery(this).data(),
                modal_div = jQuery(jQuery(this).attr('href'));

            modal_div.modal({
                backdrop: false,
                show: true,
                keyboard: false
            }).one('shown.bs.modal', function() {
                jQuery('input.newcustomfield-for-id', this).val(data['gmid']);
            }).one('hidden.bs.modal', function() {
                jQuery(':input.form-control, input.newcustomfield-for-id', this).val('');
                if(jQuery('.newcfield', this).length) {
                    jQuery('a.gmediacustomstuff').click();
                }
            });
        });
        jQuery(document).on('click.gmedia', '.customfieldsubmit', function() {
            var cform = jQuery('#newCustomFieldForm');
            if(!jQuery('.newcustomfield-for-id', cform).val()) {
                jQuery('#newCustomFieldModal').modal('hide');
                alert('No ID');
                return false;
            }
            var post_data = cform.serialize();
            jQuery.post(ajaxurl, post_data, function(data, textStatus, jqXHR) {
                jQuery('body').removeClass('gmedia-busy');
                if(data.success) {
                    jQuery('#newCustomFieldModal').modal('hide').one('hidden.bs.modal', function() {
                        //noinspection JSUnresolvedVariable
                        if(data.newmeta_form) {
                            //noinspection JSUnresolvedVariable
                            jQuery('#newmeta').replaceWith(data.newmeta_form);
                        }
                    });
                    jQuery('.row:last', '#gmediacustomstuff_' + data.id).append(data.success.data);
                } else {
                    if(data.error) {
                        if('100' == data.error.code) {
                            jQuery('#newCustomFieldModal').modal('hide');
                        }
                        alert(data.error.message);
                    } else {
                        console.log(data);
                    }
                }
            });
        });
        gmedia_DOM.on('click', '.delete-custom-field', function() {
            var t = jQuery(this).closest('.form-group'),
                post_data = convertInputsToJSON(jQuery(':input', t));
            if(!post_data) {
                return false;
            }
            var meta_type = jQuery(this).closest('fieldset').attr('data-metatype');
            post_data.action = meta_type + '_delete_custom_field';
            post_data.ID = jQuery(this).closest('form').attr('data-id');
            post_data._wpnonce_custom_field = jQuery('#_wpnonce_custom_field').val();
            jQuery.post(ajaxurl, post_data, function(data, textStatus, jqXHR) {
                jQuery('body').removeClass('gmedia-busy');
                //noinspection JSUnresolvedVariable
                if(data.deleted) {
                    //noinspection JSUnresolvedVariable
                    jQuery.each(data.deleted, function(i, val) {
                        jQuery('.gm-custom-meta-' + val).remove();
                    });
                } else {
                    if(data.error) {
                        alert(data.error.message);
                    } else {
                        console.log(data);
                    }
                }
            });
        });


        gmedia_DOM.on('change modified', 'form.edit-gmedia :input:not([name="doaction[]"])', function() {
            if(jQuery(this).hasClass('edit-gmedia-ignore')) {
                return;
            }
            jQuery('body').addClass('gmedia-busy');
            jQuery('.panel-heading .spinner').addClass('is-active');
            var post_data = {
                action: 'gmedia_update_data', data: jQuery(this).closest('form').serialize(), _wpnonce: jQuery('#_wpnonce').val()
            };
            jQuery.post(ajaxurl, post_data, function(data, textStatus, jqXHR) {
                console.log(data);
                var item = jQuery('#list-item-' + data.ID);
                item.find('.modified').text(data.modified);
                //noinspection JSUnresolvedVariable
                item.find('.status-album').attr('class', 'form-group status-album bg-status-' + data.album_status);
                item.find('.status-item').attr('class', 'form-group status-item bg-status-' + data.status);
                if(data.thumbnail) {
                    item.find('.gmedia-cover-image').html(data.thumbnail);
                }
                //if(data.tags) {
                //    item.find('.gmedia_tags_input').val(data.tags);
                //}
                //noinspection JSUnresolvedVariable
                if(data.meta_error) {
                    jQuery.each(data.meta_error, function(i, err) {
                        console.log(err);
                        alert(err.meta_key + ': ' + err.message);
                        if(err.meta_value) {
                            jQuery('.gm-custom-field-' + err.meta_id).val(err.meta_value);
                        }
                    });
                }
                jQuery('body').removeClass('gmedia-busy');
                jQuery('.panel-heading .spinner').removeClass('is-active');
            });
        });

        gmedia_DOM.on('click', '.gm-toggle-cb', function(e) {
            var checkBoxes = jQuery(this).attr('href');
            jQuery(checkBoxes + ' :checkbox').each(function() {
                jQuery(this).prop("checked", !jQuery(this).prop("checked"));
            });
            e.preventDefault();
        });
        jQuery(document).on('click.gmedia', '.linkblock [data-href]', function() {
            window.location.href = jQuery(this).data('href');
        });

        gmedia_DOM.on('click', '.gmedia-import', function() {
            jQuery('#import-action').val(jQuery(this).attr('name'));
            jQuery('#importModal').modal({
                backdrop: 'static',
                show: true,
                keyboard: false
            }).one('shown.bs.modal', function() {
                jQuery('#import_form').submit();
            }).one('hidden.bs.modal', function() {
                var btn = jQuery('#import-done');
                btn.text(btn.data('reset-text')).prop('disabled', true);
                jQuery('#import_window').attr('src', 'about:blank');
            });
        });

        gmedia_DOM.on('click', '.module_install', function(e) {
            e.preventDefault();
            jQuery('body').addClass('gmedia-busy');
            var module = jQuery(this).data('module');
            var btn = jQuery('.module_install').filter('[data-module="' + module + '"]');
            btn.text(btn.data('loading-text'));
            var post_data = {
                action: 'gmedia_module_install', download: jQuery(this).attr('href'), module: module, _wpnonce: jQuery('#_wpnonce').val()
            };
            var pathname = window.location.href + '&time=' + jQuery.now();
            jQuery.post(ajaxurl, post_data, function(data, status, xhr) {
                setTimeout(function(){
                    jQuery('#gmedia_modules').load(pathname + ' #gmedia_modules_wrapper', function(){
                        setTimeout(function(){
                            var update_count = jQuery('#gmedia_modules').find('#gmedia_modules_wrapper').attr('data-update');
                            if(parseInt(update_count)){
                                jQuery('.gm-module-count').html(update_count);
                            } else {
                                jQuery('.gm-module-count').remove();
                            }
                        }, 1);
                    });
                }, 1);
                jQuery('#gmedia_modules').before(data);
                jQuery('body').removeClass('gmedia-busy');
            });
        });

        gmedia_DOM.on('keydown', 'form :input:visible:not(:submit,:button,:reset,textarea,.allow-key-enter)', function(e) {
            var charCode = e.charCode || e.keyCode || e.which;
            if(13 == charCode && !jQuery(this).parent().hasClass('selectize-input')) {
                var inputs = jQuery(this).parents("form").eq(0).find(":input:visible");
                var inp = inputs[inputs.index(this) + 1];
                if(inp !== null) {
                    jQuery(inp).focus();
                    var inp_type = jQuery(inp).attr('type');
                    if(!!inp_type && (inp_type == 'text' || inp_type == 'number')) {
                        console.log(inp);
                        inp.setSelectionRange(0, inp.value.length);
                    }
                }
                e.preventDefault();
                return false;
            }
        });

        var preset_popover = function() {
            jQuery('#module_presets').popover({
                container: '#module_preset',
                content: function() {
                    return jQuery('#_module_presets').html();
                },
                html: true,
                placement: 'bottom'
            }).on('show.bs.popover', function() {
                jQuery(this).addClass('active');
            }).on('hide.bs.popover', function() {
                jQuery(this).removeClass('active');
            });
        };
        preset_popover();
        gmedia_DOM.on('click', '#module_preset .ajax-submit', function(e) {
            e.preventDefault();
            jQuery('body').addClass('gmedia-busy');
            var form = jQuery('#gmedia-edit-term');
            var post_data = form.serializeArray();
            post_data.push({name: jQuery(this).attr('name'), value: 1});
            var pathname = window.location.href;
            jQuery.post(pathname, jQuery.param(post_data), function(data, status, xhr) {
                jQuery('body').removeClass('gmedia-busy');
                data = jQuery(data).find('#gmedia-container');
                jQuery('#gm-message').append(jQuery('#gm-message', data).html());
                jQuery('#save_buttons').html(jQuery('#save_buttons', data).html());
                jQuery('#save_buttons_duplicate').html(jQuery('#save_buttons_duplicate', data).html());
                jQuery('#module_preset').html(jQuery('#module_preset', data).html());
                preset_popover();
            });
        });
        jQuery(document).on('click.gmedia', function(e) {
            if(jQuery(e.target).data('toggle') !== 'popover'
                && jQuery(e.target).parents('.popover.in').length === 0) {
                jQuery('[data-toggle="popover"]').popover('hide');
            }
        });

        gmedia_DOM.on('click', '[data-clicktarget]', function(e){
            e.preventDefault();
            var id = jQuery(this).attr('data-clicktarget');
            jQuery('#' + id).click();
        });

        gmedia_DOM.on('click', '#module_preset .delpreset span, .module_presets .delpreset span', function() {
            jQuery('body').addClass('gmedia-busy');
            var module_preset = this;
            var preset_item_li = jQuery(this).closest('li');
            var preset_id = jQuery(this).data('id');
            var post_data = {
                action: 'gmedia_module_preset_delete', preset_id: preset_id, _wpnonce: jQuery('#_wpnonce').val()
            };
            jQuery.post(ajaxurl, post_data, function(data, status, xhr) {
                if(data.error) {
                    jQuery('#gm-message').append(data.error);
                } else {
                    preset_item_li.remove();
                    if('module_presets_list' !== jQuery(this).attr('id')) {
                        var _module_presets = jQuery('#module_preset').find('.popover-content').html();
                        jQuery('#_module_presets').replaceWith('<script type="text/html" id="_module_presets">' + _module_presets + '</script>');
                    }
                }
                jQuery('body').removeClass('gmedia-busy');
            });
        });

        gmedia_DOM.on('click', '.filter-modules > *', function(){
            jQuery('.filter-modules > .btn-primary').removeClass('btn-primary').addClass('btn-default');
            jQuery('.filter-modules > .label-primary').removeClass('label-primary').addClass('label-default');
            if(jQuery(this).is('button')){
                jQuery(this).addClass('btn-primary').removeClass('btn-default');
            } else{
                jQuery(this).addClass('label-primary').removeClass('label-default');
            }
            var filter = jQuery(this).attr('data-filter');
            jQuery('#gmedia_modules .media').removeClass('module-filtered').filter('.module-' + filter).addClass('module-filtered');
            if(!jQuery('#gmedia_modules .module-filtered').length){
                if('not-installed' == filter){
                    jQuery('#gmedia_modules .nomodules.nomodule-' + filter).addClass('module-filtered');
                } else{
                    jQuery('#gmedia_modules .nomodules.nomodule-tag').addClass('module-filtered');
                }
            }
        })

        if(jQuery(".panel-fixed-header").length) {
            setPanelHeadersWidth();
            setTimeout(function() {
                setPanelHeadersWidth();
            }, 800);
            jQuery(window).on('resize.gmedia', function() {
                setPanelHeadersWidth();
            });
            jQuery(document).on('click.gmedia', '#collapse-menu', function() {
                setTimeout(function() {
                    setPanelHeadersWidth();
                }, 10);
            });

            jQuery(window).on('scroll.gmedia', function() {
                UpdatePanelHeaders();
                /*clearTimeout(jQuery.data(this, 'scrollTimer'));
                 jQuery.data(this, 'scrollTimer', setTimeout(function() {
                 UpdatePanelHeaders();
                 console.log("Haven't scrolled in 250ms!");
                 }, 250));*/
            }).trigger("scroll.gmedia");
        }

    }
};


window.closeModal = function(id) {
    jQuery('#' + id).modal('hide');
};


/*
 * jQuery functions for GRAND Media
 */
function GmediaInit(){
    gmedia_DOM = jQuery('#gmedia-container');
    gmedia_DOM.off();
    jQuery(window).off('.gmedia');
    jQuery(document).off('.gmedia');

    GmediaSelect.init();
    GmediaFunction.init();

    if(jQuery('body').hasClass('GrandMedia')) {
        GmediaLibrary.init();
    }
    if(jQuery('body').hasClass('GrandMedia_AddMedia')) {
        GmediaAddMedia.init();
    }
    if(jQuery('body').is('.GrandMedia_Tags,.GrandMedia_Categories,.GrandMedia_Albums,.GrandMedia_Galleries')) {
        GmediaTerms.init();
    }

    var helper, helper_width, title;
    jQuery('[title]', gmedia_DOM).each(function(){
        title = jQuery(this).attr('title');
        if(title) {
            jQuery(this).attr('title','').attr('data-title', title);
        }
    });
    gmedia_DOM.on('mouseenter', '[title]', function(e) {
        title = jQuery(this).attr('data-title');
        if(title) {
            helper = jQuery('<div id="gmedia-data-helper">' + title + '</div>').appendTo('body');
            helper_width = 0;
            if(e.pageX > (window.innerWidth / 2)) {
                helper_width = helper.width() - 25;
                helper.addClass('tiptoleft');
            }
            helper.css({left: e.clientX - helper_width - 25, top: e.clientY + 25});
        } else{
            jQuery(this).removeAttr('title');
        }
    }).on('mousemove', '[title]', function(e) {
        if(helper) {
            helper.css({left: e.clientX - helper_width - 25, top: e.clientY + 25});
        }
    }).on('mouseleave', '[title]', function(e) {
        jQuery('#gmedia-data-helper').remove();
        helper = null;
    });
}
jQuery(document).on('ready', function() {
    GmediaInit();
});

function convertInputsToJSON(form) {
    var array = jQuery(form).serializeArray();
    var json = {};

    jQuery.each(array, function() {
        json[this.name] = this.value || '';
    });

    return json;
}

function gm_parse_query(s) {
    var j = {},
        res = s.split(/&/gm).map(function(e) {
            var o = e.split(/=/),
                pt = j;
            if(typeof o[1] == 'undefined') {
                o[1] = '';
            }
            o[0].replace(/^(\w+)\[([^&]*)\]/, '$1][$2').split(/\]\[/).map(function(e, i, a) {
                if(e === '') {
                    e = Object.keys(pt).length;
                }
                pt = (pt[e] = pt[e] || (i == a.length - 1? decodeURIComponent(o[1].replace(/\+/, ' ')) : {}));
            });
        });
    return j;
}

function validateEmail(email) {
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

function getStorage() {
    // use document.cookie:
    return {
        set: function(id, data) {
            document.cookie = id + '=' + encodeURIComponent(data);
        },
        get: function(id) {
            var cookies = document.cookie, parsed = {};
            cookies.replace(/([^=]+)=([^;]*);?\s*/g, function(whole, key, value) {
                parsed[key] = decodeURIComponent(value);
            });
            return parsed[id];
        }
    };
}

function getDocHeight(id){
    var H;
    if(id){
        H = Math.max(
            jQuery('#' + id).height(),
            document.getElementById(id).clientHeight
        );
    } else {
        H = Math.max(
            jQuery(document).height(),
            jQuery(window).height(),
            document.documentElement.clientHeight
        );
    }

    return H;
};
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
        var el = jQuery(this),
            headerRow = jQuery(".panel-heading", this),
            offset = el.offset(),
            scrollTop = jQuery(window).scrollTop(),
            floatingHeader = "panel-floatingHeader",
            absoluteHeader = "panel-absoluteHeader",
            pad_top = jQuery('#wpadminbar').height();

        if((scrollTop > offset.top - pad_top) && (scrollTop < offset.top - pad_top + (el.height() - headerRow.outerHeight(false)) + 4)) {
            el.addClass(floatingHeader).removeClass(absoluteHeader);
        } else if(scrollTop > (offset.top - pad_top + (el.height() - headerRow.outerHeight(false)))) {
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
