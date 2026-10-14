(function(wp) {
  var el = wp.element.createElement;
  var __ = wp.i18n.__;

  function withGmediaSource(Original, menu) {
    return function(props) {
      var state = wp.element.useState(false);
      var isOpen = state[0], setOpen = state[1];
      var frame = wp.element.useRef(null);
      var isImage = wp.data.useSelect(function(select) {
        var block = select('core/block-editor').getSelectedBlock();
        return block && block.name === 'core/image';
      }, []);

      wp.element.useEffect(function() {
        if (!isOpen) {
          return;
        }
        function receive(event) {
          if (event.origin !== window.location.origin || !frame.current || event.source !== frame.current.contentWindow ||
              !event.data || event.data.type !== 'gmedia-image-selected') {
            return;
          }
          var media = event.data.media;
          if (!media || typeof media.url !== 'string' || !/^https?:\/\//i.test(media.url)) {
            return;
          }
          props.onSelect({url: media.url, alt: media.alt || '', caption: media.caption || ''});
          setOpen(false);
        }
        window.addEventListener('message', receive);
        return function() { window.removeEventListener('message', receive); };
      }, [isOpen, props.onSelect]);

      if (!isImage || props.multiple || !props.onSelect || (!menu && props.disableMediaButtons)) {
        return el(Original, props);
      }
      function button(onClose) {
        return el(menu ? wp.components.MenuItem : wp.components.Button, {
          variant: menu ? undefined : 'secondary',
          onClick: function() {
            setOpen(true);
            if (onClose) { onClose(); }
          }
        }, __('Gmedia Library', 'grand-media'));
      }
      var children = menu ? function(context) {
        return el(wp.element.Fragment, null,
          typeof props.children === 'function' ? props.children(context) : props.children,
          button(context.onClose));
      } : el(wp.element.Fragment, null, props.children, button());
      return el(wp.element.Fragment, null,
        el(Original, Object.assign({}, props, {children: children})),
        isOpen && el(wp.components.Modal, {
          title: __('Gmedia Library', 'grand-media'),
          onRequestClose: function() { setOpen(false); },
          size: 'large',
          className: 'gmedia-image-source-modal'
        }, el('iframe', {
          ref: frame,
          title: __('Select a Gmedia image', 'grand-media'),
          src: gmedia_image_source.url
        })));
    };
  }

  wp.hooks.addFilter('editor.MediaReplaceFlow', 'gmedia/image-source', function(Original) {
    return withGmediaSource(Original, true);
  });
  wp.hooks.addFilter('editor.MediaPlaceholder', 'gmedia/image-source', function(Original) {
    return withGmediaSource(Original, false);
  });
})(window.wp);
