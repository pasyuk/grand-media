const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const filters = {};
let selected = 'core/image', open = false, received, listener;
const frame = { contentWindow: {} };
const el = (type, props, ...children) => ({type, props: {...props, ...(children.length ? {children} : {})}});
const wp = {
  hooks: {addFilter: (name, key, fn) => { filters[name] = fn; }},
  element: {createElement: el, Fragment: 'Fragment', useState: () => [open, v => { open = v; }], useRef: () => ({current: frame}), useEffect: fn => { fn(); }},
  data: {useSelect: fn => fn(() => ({getSelectedBlock: () => ({name: selected})}))},
  components: {Modal: 'Modal', Button: 'Button', MenuItem: 'MenuItem'},
  i18n: {__: s => s}
};
const window = {wp, location: {origin: 'https://example.test'}, addEventListener: (type, fn) => { listener = fn; }, removeEventListener: () => {}};
const file = path.resolve(__dirname, '../../admin/assets/js/gmedia-image-source.js');
if (!fs.existsSync(file)) { console.error('Image block source integration is missing'); process.exit(1); }
vm.runInNewContext(fs.readFileSync(file, 'utf8'), {window, URL, gmedia_image_source: {url: 'https://example.test/wp-admin/media-upload.php'}});
for (const name of ['editor.MediaReplaceFlow', 'editor.MediaPlaceholder']) {
  assert.equal(typeof filters[name], 'function');
  const Wrapped = filters[name]('Original');
  const props = {allowedTypes:['image'], onSelect: media => { received = media; }, children:'existing'};
  selected = 'core/video';
  assert.equal(Wrapped(props).type, 'Original');
  selected = 'core/image';
  const tree = Wrapped(props);
  assert.equal(tree.type, 'Fragment');
}
const Wrapped = filters['editor.MediaReplaceFlow']('Original');
let tree = Wrapped({allowedTypes:['image'], onSelect: media => { received = media; }, children: () => 'existing'});
let closed = false;
const menu = tree.props.children[0].props.children({onClose: () => {closed = true;}});
menu.props.children[1].props.onClick();
assert.equal(open, true);
assert.equal(closed, true);
tree = Wrapped({allowedTypes:['image'], onSelect: media => { received = media; }});
assert.equal(tree.props.children[1].type, 'Modal');
const message = {type: 'gmedia-image-selected', media:{url:'https://example.test/image.jpg', alt:'Example', id:99}};
listener({origin:'https://untrusted.test', source:frame.contentWindow, data:message});
assert.equal(received, undefined);
listener({origin:window.location.origin, source:{}, data:message});
assert.equal(received, undefined);
listener({origin:window.location.origin, source:frame.contentWindow, data:message});
assert.equal(received.url, 'https://example.test/image.jpg');
assert.equal(received.id, undefined, 'Gmedia ID must not become a WordPress attachment ID');
assert.equal(open, false);
console.log('Image source menu, selection and message boundaries passed.');
