(function(blocks, element) {
  var el = element.createElement,
    source = blocks.source,
    setCategories = blocks.setCategories,
    getCategories = blocks.getCategories,
    createBlock = blocks.createBlock;

  var icons = {};
  var iconColor = '#316778';
  icons.iconAlbum = el(
    'svg',
    {key: 'gm-album', width: 24, height: 24, style: {fill: iconColor}, viewBox: '0 0 24 24'},
    el('path', {d: 'M24,6c0-2.2-1.8-4-4-4H4C1.8,2,0,3.8,0,6v12c0,2.2,1.8,4,4,4h16c2.2,0,4-1.8,4-4V6z M6,6c1.1,0,2,0.9,2,2   c0,1.1-0.9,2-2,2S4,9.1,4,8C4,6.9,4.9,6,6,6z M22,18c0,1.1-0.9,2-2,2H4.4c-0.9,0-1.3-1.1-0.7-1.7l3.6-3.6c0.4-0.4,1-0.4,1.4,0   l0.6,0.6c0.4,0.4,1,0.4,1.4,0l6.6-6.6c0.4-0.4,1-0.4,1.4,0l3,3c0.2,0.2,0.3,0.4,0.3,0.7V18z'})
  );
  icons.iconGal = el(
    'svg',
    {key: 'gm-gallery', width: 24, height: 24, style: {fill: iconColor}, viewBox: '0 0 512 512'},
    [
      el('path', {d: 'M464,128h-16v-16c0-26.51-21.49-48-48-48h-16V48c0-26.51-21.49-48-48-48H48C21.49,0,0,21.49,0,48v288    c0,26.51,21.49,48,48,48h16v16c0,26.51,21.49,48,48,48h16v16c0,26.51,21.49,48,48,48h288c26.51,0,48-21.49,48-48V176    C512,149.49,490.51,128,464,128z M32,265.44V48c0-8.837,7.163-16,16-16h288c8.837,0,16,7.163,16,16v153.44l-36.64-36.64    c-6.241-6.204-16.319-6.204-22.56,0L224,233.44l-68.64-68.8c-6.241-6.204-16.319-6.204-22.56,0L32,265.44z M112,416    c-8.837,0-16-7.163-16-16v-16h240c26.51,0,48-21.49,48-48V96h16c8.837,0,16,7.163,16,16v288c0,8.837-7.163,16-16,16H112z M480,464    c0,8.837-7.163,16-16,16H176c-8.837,0-16-7.163-16-16v-16h240c26.51,0,48-21.49,48-48V160h16c8.837,0,16,7.163,16,16V464z'})
    ]
  );
  icons.iconCat = el(
    'svg',
    {key: 'gm-category', width: 24, height: 24, style: {fill: iconColor}, viewBox: '0 0 24 24'},
    el('path', {d: 'M24,6c0-2.2-1.8-4-4-4H4C1.8,2,0,3.8,0,6v12c0,2.2,1.8,4,4,4h16c2.2,0,4-1.8,4-4V6z M6,6c1.1,0,2,0.9,2,2   c0,1.1-0.9,2-2,2S4,9.1,4,8C4,6.9,4.9,6,6,6z M22,18c0,1.1-0.9,2-2,2H4.4c-0.9,0-1.3-1.1-0.7-1.7l3.6-3.6c0.4-0.4,1-0.4,1.4,0   l0.6,0.6c0.4,0.4,1,0.4,1.4,0l6.6-6.6c0.4-0.4,1-0.4,1.4,0l3,3c0.2,0.2,0.3,0.4,0.3,0.7V18z'})
  );
  icons.iconTag = el(
    'svg',
    {key: 'gm-tag', width: 24, height: 24, style: {fill: iconColor}, viewBox: '0 0 427 427'},
    [
      el('path', {d: 'M414.08,204.373L222.187,12.48C214.4,4.8,203.733,0,192,0H42.667C19.093,0,0,19.093,0,42.667V192    c0,11.84,4.8,22.507,12.587,30.187l192,192c7.68,7.68,18.347,12.48,30.08,12.48s22.507-4.8,30.187-12.48l149.333-149.333    c7.68-7.787,12.48-18.453,12.48-30.187C426.667,222.827,421.867,212.16,414.08,204.373z M74.667,106.667    c-17.707,0-32-14.293-32-32s14.293-32,32-32s32,14.293,32,32S92.373,106.667,74.667,106.667z'})
    ]
  );
  icons.attention = el(
    'svg',
    {key: 'gm-attention', width: 34, height: 34, style: {fill: iconColor}, viewBox: '0 0 30 30'},
    [
      el('path', {d: 'M15,3C8.373,3,3,8.373,3,15c0,6.627,5.373,12,12,12s12-5.373,12-12C27,8.373,21.627,3,15,3z M16.212,8l-0.2,9h-2.024l-0.2-9  H16.212z M15.003,22.189c-0.828,0-1.323-0.441-1.323-1.182c0-0.755,0.494-1.196,1.323-1.196c0.822,0,1.316,0.441,1.316,1.196  C16.319,21.748,15.825,22.189,15.003,22.189z'})
    ]
  );

  var category = {
    slug: 'gmedia/gallery',
    title: 'Gmedia Gallery',
    icon: icons.iconAlbum
  };
  var catt = [category];
  catt = catt.concat(getCategories());
  setCategories(catt);

  function GmediaGallery(atts) {
    var id = atts.id;
    var tagtext = '[gmedia id=' + id + ']';

    return el('div', {className: 'gmedia-shortcode'}, tagtext);
  }

  function GmediaTerm(atts, type) {
    var id = atts.id;
    var module = atts.module_name ? ' module=' + atts.module_name : '';
    var preset = atts.module_preset ? ' preset=' + atts.module_preset : '';
    type = type || 'id';
    var tagtext = '[gm ' + type + '=' + id + module + preset + ']';

    return el('div', {className: 'gmedia-shortcode'}, tagtext);
  }

  blocks.registerBlockType('gmedia/gallery', {
    title: 'Gmedia Gallery',
    icon: {src: icons.iconGal},
    description: 'Add Gmedia Gallery',
    keywords: [
      'gallery',
      'images',
      'video',
      'youtube',
      'vimeo',
      'video',
      'audio',
      'mp3',
      'lightbox'
    ],
    category: category.slug,
    attributes: {
      id: {
        type: 'integer'
      }
    },
    transforms: {
      to: [
        {
          type: 'block',
          blocks: ['gmedia/album'],
          transform: function() {
            return createBlock('gmedia/album', {});
          }
        },
        {
          type: 'block',
          blocks: ['gmedia/category'],
          transform: function() {
            return createBlock('gmedia/category', {});
          }
        },
        {
          type: 'block',
          blocks: ['gmedia/tag'],
          transform: function() {
            return createBlock('gmedia/tag', {});
          }
        }
      ]
    },

    edit: function(props) {
      var id = props.attributes.id;
      var elclass = 'gmedia-id';
      var image = gmedia_data.gmedia_image;
      var form_fields = [];
      var children = [];
      var options = [];

      function setGallery(event) {
        event.preventDefault();
        var form = jQuery(event.target).closest('form.gmedia-preview');
        var id = parseInt(form.find('.gmedia-id').val());
        props.setAttributes({
          id: id
        });
      }

      form_fields.push(
        el('h3', null, 'Gmedia Gallery')
      );

      // Choose galleries
      Object.keys(gmedia_data.galleries).forEach(function(key) {
        options.push(
          el('option', {value: gmedia_data.galleries[key].term_id}, gmedia_data.galleries[key].name)
        );
      });
      if (!id) {
        elclass += ' gmedia-required';
      }
      form_fields.push(
        el('select', {className: elclass, value: id, onChange: setGallery}, options)
      );

      if (id) {
        form_fields.push(GmediaGallery(props.attributes));
      }

      children.push(
        el('div', {className: 'form-fields'}, form_fields)
      );

      if (id) {
        var module = gmedia_data.galleries[id].module_name;
        image = (gmedia_data.modules[module] && gmedia_data.modules[module].screenshot)
          ? gmedia_data.modules[module].screenshot : undefined;
      }
      if (image) {
        children.push(el('img', {className: 'gmedia-module-screenshot', src: image}));
      }
      else {
        children.push(el('div', {
          style: {
            width: '240px', height: '140px', color: 'red', margin: '10px', padding: '15px', display: 'flex',
            flexDirection: 'column', justifyContent: 'center', alignItems: 'center', backgroundCcolor: '#fef8ee', textAlign: 'center'
          }
        }, [
          'This gallery is broken, select another!',
          icons.attention
        ]));
      }
      return el('form', {className: 'gmedia-preview', onSubmit: setGallery}, children);
    },

    save: function(props) {
      if (typeof props.attributes.id == 'undefined') {
        return;
      }
      return GmediaGallery(props.attributes);
    }

  });

  blocks.registerBlockType('gmedia/album', {
    title: 'Gmedia Album',
    icon: {src: icons.iconAlbum},
    description: 'Add Gmedia Album',
    keywords: [
      'gallery',
      'images',
      'video',
      'youtube',
      'vimeo',
      'video',
      'audio',
      'mp3',
      'lightbox',
      'album'
    ],
    category: category.slug,
    attributes: {
      id: {
        type: 'integer'
      },
      module_name: {
        type: 'string'
      },
      module_preset: {
        type: 'string'
      }
    },
    transforms: {
      to: [
        {
          type: 'block',
          blocks: ['gmedia/gallery'],
          transform: function() {
            return createBlock('gmedia/gallery', {});
          }
        },
        {
          type: 'block',
          blocks: ['gmedia/category'],
          transform: function() {
            return createBlock('gmedia/category', {});
          }
        },
        {
          type: 'block',
          blocks: ['gmedia/tag'],
          transform: function() {
            return createBlock('gmedia/tag', {});
          }
        }
      ]
    },

    edit: function(props) {
      var id = props.attributes.id;
      var module_name = props.attributes.module_name;
      var module = props.attributes.module_preset ? props.attributes.module_preset : module_name;
      var default_module = gmedia_data.default_module;
      var elclass = 'gmedia-id';
      var image = gmedia_data.gmedia_image;
      var form_fields = [];
      var children = [];
      var modules = [];
      var options = [];

      function setGallery(event) {
        event.preventDefault();
        var form = jQuery(event.target).closest('form.gmedia-preview');
        var id = parseInt(form.find('.gmedia-id').val());
        var module = form.find('.gmedia-overwrite-module');
        var module_name = module.find('option:selected');
        var module_preset = '';
        if (module_name.attr('value')) {
          module_name = module_name.closest('optgroup').attr('module');
          if (module.val() !== module_name) {
            module_preset = module.val();
          }
        }
        else {
          module_name = '';
        }
        props.setAttributes({
          id: id,
          module_name: module_name,
          module_preset: module_preset
        });
      }

      form_fields.push(
        el('h3', null, 'Gmedia Album')
      );

      // Choose galleries
      Object.keys(gmedia_data.albums).forEach(function(key) {
        options.push(
          el('option', {value: gmedia_data.albums[key].term_id}, gmedia_data.albums[key].name)
        );
      });
      if (!id) {
        elclass += ' gmedia-required';
      }
      form_fields.push(
        el('select', {className: elclass, value: id, onChange: setGallery}, options)
      );

      modules.push(
        el('option', {value: ''}, ' - default module -')
      );
      Object.keys(gmedia_data.modules_options).forEach(function(key) {
        options = [];
        Object.keys(gmedia_data.modules_options[key].options).forEach(function(m) {
          options.push(
            el('option', {value: m}, gmedia_data.modules_options[key].options[m])
          );
        });
        modules.push(
          el('optgroup', {label: gmedia_data.modules_options[key].title, module: key}, null, options)
        );
      });
      form_fields.push(
        el('select', {className: 'gmedia-overwrite-module', value: module, onChange: setGallery}, modules)
      );

      if (id) {
        form_fields.push(GmediaTerm(props.attributes, 'album'));
      }

      children.push(
        el('div', {className: 'form-fields'}, form_fields)
      );

      if (id) {
        var term_module = gmedia_data.albums[id].module_name;
        image = module_name ? gmedia_data.modules[module_name].screenshot : (term_module ? gmedia_data.modules[term_module].screenshot : gmedia_data.modules[default_module].screenshot);
      }
      children.push(
        el('img', {className: 'gmedia-module-screenshot', src: image})
      );

      return el('form', {className: 'gmedia-preview', onSubmit: setGallery}, children);
    },

    save: function(props) {
      if (typeof props.attributes.id == 'undefined') {
        return;
      }
      return GmediaTerm(props.attributes, 'album');
    }

  });

  blocks.registerBlockType('gmedia/category', {
    title: 'Gmedia Category',
    icon: {src: icons.iconCat},
    description: 'Add Gmedia Category',
    keywords: [
      'gallery',
      'images',
      'video',
      'youtube',
      'vimeo',
      'video',
      'audio',
      'mp3',
      'lightbox',
      'category'
    ],
    category: category.slug,
    attributes: {
      id: {
        type: 'integer'
      },
      module_name: {
        type: 'string'
      },
      module_preset: {
        type: 'string'
      }
    },
    transforms: {
      to: [
        {
          type: 'block',
          blocks: ['gmedia/album'],
          transform: function() {
            return createBlock('gmedia/album', {});
          }
        },
        {
          type: 'block',
          blocks: ['gmedia/gallery'],
          transform: function() {
            return createBlock('gmedia/gallery', {});
          }
        },
        {
          type: 'block',
          blocks: ['gmedia/tag'],
          transform: function() {
            return createBlock('gmedia/tag', {});
          }
        }
      ]
    },

    edit: function(props) {
      var id = props.attributes.id;
      var module_name = props.attributes.module_name;
      var module = props.attributes.module_preset ? props.attributes.module_preset : module_name;
      var default_module = gmedia_data.default_module;
      var elclass = 'gmedia-id';
      var image = gmedia_data.gmedia_image;
      var form_fields = [];
      var children = [];
      var modules = [];
      var options = [];

      function setGallery(event) {
        event.preventDefault();
        var form = jQuery(event.target).closest('form.gmedia-preview');
        var id = parseInt(form.find('.gmedia-id').val());
        var module = form.find('.gmedia-overwrite-module');
        var module_name = module.find('option:selected');
        var module_preset = '';
        if (module_name.attr('value')) {
          module_name = module_name.closest('optgroup').attr('module');
          if (module.val() !== module_name) {
            module_preset = module.val();
          }
        }
        else {
          module_name = '';
        }
        props.setAttributes({
          id: id,
          module_name: module_name,
          module_preset: module_preset
        });
      }

      form_fields.push(
        el('h3', null, 'Gmedia Category')
      );

      // Choose galleries
      Object.keys(gmedia_data.categories).forEach(function(key) {
        options.push(
          el('option', {value: gmedia_data.categories[key].term_id}, gmedia_data.categories[key].name)
        );
      });
      if (!id) {
        elclass += ' gmedia-required';
      }
      form_fields.push(
        el('select', {className: elclass, value: id, onChange: setGallery}, options)
      );

      modules.push(
        el('option', {value: ''}, ' - default module -')
      );
      Object.keys(gmedia_data.modules_options).forEach(function(key) {
        options = [];
        Object.keys(gmedia_data.modules_options[key].options).forEach(function(m) {
          options.push(
            el('option', {value: m}, gmedia_data.modules_options[key].options[m])
          );
        });
        modules.push(
          el('optgroup', {label: gmedia_data.modules_options[key].title, module: key}, null, options)
        );
      });
      form_fields.push(
        el('select', {className: 'gmedia-overwrite-module', value: module, onChange: setGallery}, modules)
      );

      if (id) {
        form_fields.push(GmediaTerm(props.attributes, 'category'));
      }

      children.push(
        el('div', {className: 'form-fields'}, form_fields)
      );

      if (id) {
        var term_module = gmedia_data.categories[id].module_name;
        image = module_name ? gmedia_data.modules[module_name].screenshot : (term_module ? gmedia_data.modules[term_module].screenshot : gmedia_data.modules[default_module].screenshot);
      }
      children.push(
        el('img', {className: 'gmedia-module-screenshot', src: image})
      );

      return el('form', {className: 'gmedia-preview', onSubmit: setGallery}, children);
    },

    save: function(props) {
      if (typeof props.attributes.id == 'undefined') {
        return;
      }
      return GmediaTerm(props.attributes, 'category');
    }

  });

  blocks.registerBlockType('gmedia/tag', {
    title: 'Gmedia Tag',
    icon: {src: icons.iconTag},
    description: 'Publish gallery by Gmedia Tag',
    keywords: [
      'gallery',
      'images',
      'video',
      'youtube',
      'vimeo',
      'video',
      'audio',
      'mp3',
      'lightbox',
      'tag'
    ],
    category: category.slug,
    attributes: {
      id: {
        type: 'integer'
      },
      module_name: {
        type: 'string'
      },
      module_preset: {
        type: 'string'
      }
    },
    transforms: {
      to: [
        {
          type: 'block',
          blocks: ['gmedia/album'],
          transform: function() {
            return createBlock('gmedia/album', {});
          }
        },
        {
          type: 'block',
          blocks: ['gmedia/category'],
          transform: function() {
            return createBlock('gmedia/category', {});
          }
        },
        {
          type: 'block',
          blocks: ['gmedia/gallery'],
          transform: function() {
            return createBlock('gmedia/gallery', {});
          }
        }
      ]
    },

    edit: function(props) {
      var id = props.attributes.id;
      var module_name = props.attributes.module_name;
      var module = props.attributes.module_preset ? props.attributes.module_preset : module_name;
      var default_module = gmedia_data.default_module;
      var elclass = 'gmedia-id';
      var image = gmedia_data.gmedia_image;
      var form_fields = [];
      var children = [];
      var modules = [];
      var options = [];

      function setGallery(event) {
        event.preventDefault();
        var form = jQuery(event.target).closest('form.gmedia-preview');
        var id = parseInt(form.find('.gmedia-id').val());
        var module = form.find('.gmedia-overwrite-module');
        var module_name = module.find('option:selected');
        var module_preset = '';
        if (module_name.attr('value')) {
          module_name = module_name.closest('optgroup').attr('module');
          if (module.val() !== module_name) {
            module_preset = module.val();
          }
        }
        else {
          module_name = '';
        }
        props.setAttributes({
          id: id,
          module_name: module_name,
          module_preset: module_preset
        });
      }

      form_fields.push(
        el('h3', null, 'Gmedia Tag')
      );
      // Choose galleries
      Object.keys(gmedia_data.tags).forEach(function(key) {
        options.push(
          el('option', {value: gmedia_data.tags[key].term_id}, gmedia_data.tags[key].name)
        );
      });
      if (!id) {
        elclass += ' gmedia-required';
      }
      form_fields.push(
        el('select', {className: elclass, value: id, onChange: setGallery}, options)
      );

      modules.push(
        el('option', {value: ''}, ' - default module -')
      );
      Object.keys(gmedia_data.modules_options).forEach(function(key) {
        options = [];
        Object.keys(gmedia_data.modules_options[key].options).forEach(function(m) {
          options.push(
            el('option', {value: m}, gmedia_data.modules_options[key].options[m])
          );
        });
        modules.push(
          el('optgroup', {label: gmedia_data.modules_options[key].title, module: key}, null, options)
        );
      });
      form_fields.push(
        el('select', {className: 'gmedia-overwrite-module', value: module, onChange: setGallery}, modules)
      );

      if (id) {
        form_fields.push(GmediaTerm(props.attributes, 'tag'));
      }

      children.push(
        el('div', {className: 'form-fields'}, form_fields)
      );

      if (id) {
        var term_module = gmedia_data.tags[id].module_name;
        image = module_name ? gmedia_data.modules[module_name].screenshot : (term_module ? gmedia_data.modules[term_module].screenshot : gmedia_data.modules[default_module].screenshot);
      }
      children.push(
        el('img', {className: 'gmedia-module-screenshot', src: image})
      );

      return el('form', {className: 'gmedia-preview', onSubmit: setGallery}, children);
    },

    save: function(props) {
      if (typeof props.attributes.id == 'undefined') {
        return;
      }
      return GmediaTerm(props.attributes, 'tag');
    }

  });
})(
  window.wp.blocks,
  window.wp.element
);
