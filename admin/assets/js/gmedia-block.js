(function(blocks, element) {
  var el = element.createElement,
    source = blocks.source;

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
    icon: 'format-gallery',
    category: 'common',
    attributes: {
      id: {
        type: 'integer'
      }
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
      if(!id) {
        elclass += ' gmedia-required';
      }
      form_fields.push(
        el('select', {className: elclass, value: id, onChange: setGallery}, options)
      );

      if(id) {
        form_fields.push(GmediaGallery(props.attributes));
      }

      children.push(
        el('div', {className: 'form-fields'}, form_fields)
      );

      if(id) {
        var module = gmedia_data.galleries[id].module_name;
        image = gmedia_data.modules[module].screenshot;
      }
      children.push(
        el('img', {className: 'gmedia-module-screenshot', src: image})
      );

      return el('form', {className: 'gmedia-preview', onSubmit: setGallery}, children);
    },

    save: function(props) {
      if(typeof props.attributes.id == 'undefined') {
        return;
      }
      return GmediaGallery(props.attributes);
    }

  });

  blocks.registerBlockType('gmedia/album', {
    title: 'Gmedia Album',
    icon: 'format-gallery',
    category: 'common',
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

    edit: function(props) {
      var id = props.attributes.id;
      var module_name = props.attributes.module_name;
      var module = props.attributes.module_preset? props.attributes.module_preset : module_name;
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
        if(module_name.attr('value')) {
          module_name = module_name.closest('optgroup').attr('module');
          if(module.val() != module_name){
            module_preset = module.val();
          }
        } else {
          module_name = ''
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
      if(!id) {
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
          el('optgroup', {label: gmedia_data.modules_options[key].title, module: key}, null, options),
        );
      });
      form_fields.push(
        el('select', {className: 'gmedia-overwrite-module', value: module, onChange: setGallery}, modules)
      );

      if(id) {
        form_fields.push(GmediaTerm(props.attributes, 'album'));
      }

      children.push(
        el('div', {className: 'form-fields'}, form_fields)
      );

      if(id) {
        var term_module = gmedia_data.albums[id].module_name;
        image = module_name ? gmedia_data.modules[module_name].screenshot : (term_module ? gmedia_data.modules[term_module].screenshot : gmedia_data.modules[default_module].screenshot);
      }
      children.push(
        el('img', {className: 'gmedia-module-screenshot', src: image})
      );

      return el('form', {className: 'gmedia-preview', onSubmit: setGallery}, children);
    },

    save: function(props) {
      if(typeof props.attributes.id == 'undefined') {
        return;
      }
      return GmediaTerm(props.attributes, 'album');
    }

  });

  blocks.registerBlockType('gmedia/category', {
    title: 'Gmedia Category',
    icon: 'format-gallery',
    category: 'common',
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

    edit: function(props) {
      var id = props.attributes.id;
      var module_name = props.attributes.module_name;
      var module = props.attributes.module_preset? props.attributes.module_preset : module_name;
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
        if(module_name.attr('value')) {
          module_name = module_name.closest('optgroup').attr('module');
          if(module.val() != module_name){
            module_preset = module.val();
          }
        } else {
          module_name = ''
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
      if(!id) {
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
          el('optgroup', {label: gmedia_data.modules_options[key].title, module: key}, null, options),
        );
      });
      form_fields.push(
        el('select', {className: 'gmedia-overwrite-module', value: module, onChange: setGallery}, modules)
      );

      if(id) {
        form_fields.push(GmediaTerm(props.attributes, 'category'));
      }

      children.push(
        el('div', {className: 'form-fields'}, form_fields)
      );

      if(id) {
        var term_module = gmedia_data.categories[id].module_name;
        image = module_name ? gmedia_data.modules[module_name].screenshot : (term_module ? gmedia_data.modules[term_module].screenshot : gmedia_data.modules[default_module].screenshot);
      }
      children.push(
        el('img', {className: 'gmedia-module-screenshot', src: image})
      );

      return el('form', {className: 'gmedia-preview', onSubmit: setGallery}, children);
    },

    save: function(props) {
      if(typeof props.attributes.id == 'undefined') {
        return;
      }
      return GmediaTerm(props.attributes, 'category');
    }

  });

  blocks.registerBlockType('gmedia/tag', {
    title: 'Gmedia Tag',
    icon: 'format-gallery',
    category: 'common',
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

    edit: function(props) {
      var id = props.attributes.id;
      var module_name = props.attributes.module_name;
      var module = props.attributes.module_preset? props.attributes.module_preset : module_name;
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
        if(module_name.attr('value')) {
          module_name = module_name.closest('optgroup').attr('module');
          if(module.val() != module_name){
            module_preset = module.val();
          }
        } else {
          module_name = ''
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
      if(!id) {
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
          el('optgroup', {label: gmedia_data.modules_options[key].title, module: key}, null, options),
        );
      });
      form_fields.push(
        el('select', {className: 'gmedia-overwrite-module', value: module, onChange: setGallery}, modules)
      );

      if(id) {
        form_fields.push(GmediaTerm(props.attributes, 'tag'));
      }

      children.push(
        el('div', {className: 'form-fields'}, form_fields)
      );

      if(id) {
        var term_module = gmedia_data.tags[id].module_name;
        image = module_name ? gmedia_data.modules[module_name].screenshot : (term_module ? gmedia_data.modules[term_module].screenshot : gmedia_data.modules[default_module].screenshot);
      }
      children.push(
        el('img', {className: 'gmedia-module-screenshot', src: image})
      );

      return el('form', {className: 'gmedia-preview', onSubmit: setGallery}, children);
    },

    save: function(props) {
      if(typeof props.attributes.id == 'undefined') {
        return;
      }
      return GmediaTerm(props.attributes, 'tag');
    }

  });
})(
  window.wp.blocks,
  window.wp.element
);
