// Writes public/osec_themes/vortex/less/osec-icons-map.less: one rule per icon class, holding the icon as an
// SVG data URI in --osec-icon (drawn by osec-icons.less as a mask in the text colour).
//
//    cd bin/icons && npm ci && npm run build
//
// Then rebuild the admin CSS (bin/compile-less-production.sh); the frontend compiles on its own.

import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const out = join(here, '../../public/osec_themes/vortex/less/osec-icons-map.less');
const modules = join(here, 'node_modules');

const sources = {
  lucide: { dir: 'lucide-static/icons', license: 'lucide-static/LICENSE', license_id: 'ISC', name: 'Lucide', url: 'https://lucide.dev' },
  simple: { dir: 'simple-icons/icons', license: 'simple-icons/LICENSE.md', license_id: 'CC0-1.0', name: 'Simple Icons', url: 'https://simpleicons.org' },
  bootstrap: { dir: 'bootstrap-icons/icons', license: 'bootstrap-icons/LICENSE', license_id: 'MIT', name: 'Bootstrap Icons', url: 'https://icons.getbootstrap.com' },
};

// Lucide icons: 110% and line weight 1.8 by default, chosen against Font Awesome 4 at 13px.
const LUCIDE_SCALE = 1.1;
const LUCIDE_SCALE_LARGE = 1.2;
const LUCIDE_STROKE = 1.8;
const LUCIDE_STROKE_ARROW = 2.6;
const largeLucide = (name) => name.startsWith('calendar') || ['unfold-vertical', 'folder-tree'].includes(name);
const arrowLucide = (name) => ['arrow', 'chevron', 'chevrons'].includes(name.split('-')[0]);

// Class (without the ai1ec- prefix) => [source, icon, options].
const icons = {
  // Navigation.
  'fa-angle-double-left': ['lucide', 'chevrons-left'],
  'fa-angle-double-right': ['lucide', 'chevrons-right'],
  'fa-angle-left': ['lucide', 'chevron-left'],
  'fa-angle-right': ['lucide', 'chevron-right'],
  'fa-chevron-left': ['lucide', 'chevron-left'],
  'fa-chevron-right': ['lucide', 'chevron-right'],
  'fa-chevron-up': ['lucide', 'chevron-up'],
  'fa-chevron-down': ['lucide', 'chevron-down'],
  'fa-caret-up': ['lucide', 'chevron-up'],
  'fa-caret-down': ['lucide', 'chevron-down'],
  'fa-arrow-left': ['lucide', 'arrow-left'],
  'fa-arrow-right': ['lucide', 'arrow-right'],
  'fa-arrow-up': ['lucide', 'arrow-up'],
  'fa-arrow-down': ['lucide', 'arrow-down'],
  'fa-external-link': ['lucide', 'external-link'],
  'fa-expand': ['lucide', 'unfold-vertical'],
  // Calendar views (views-dropdown.twig builds ai1ec-icon-{{ view }}).
  'icon-agenda': ['lucide', 'layout-list'],
  'icon-oneday': ['lucide', 'calendar-1'],
  'icon-week': ['lucide', 'calendar-range'],
  'icon-month': ['lucide', 'calendar-days'],
  // Subscribe menu.
  'icon-rss': ['lucide', 'rss'],
  'icon-google': ['simple', 'google', { scale: 0.88 }],
  'icon-windows': ['bootstrap', 'microsoft'],
  'icon-apple': ['simple', 'apple'],
  'icon-calendar': ['lucide', 'calendar-plus'],
  'fa-download': ['lucide', 'download'],
  // Event details.
  'fa-calendar': ['lucide', 'calendar'],
  'fa-calendar-o': ['lucide', 'calendar'],
  'fa-repeat': ['lucide', 'repeat'],
  'fa-map-marker': ['lucide', 'map-pin'],
  'fa-folder-open': ['lucide', 'folder-tree'],
  'fa-tags': ['lucide', 'tags'],
  'fa-ticket': ['lucide', 'ticket'],
  'fa-shopping-cart': ['lucide', 'shopping-cart'],
  'fa-user': ['lucide', 'user'],
  'fa-phone': ['lucide', 'phone'],
  'fa-envelope-o': ['lucide', 'mail'],
  // Actions and states.
  'fa-pencil': ['lucide', 'pencil'],
  'fa-print': ['lucide', 'printer'],
  'fa-plus-circle': ['lucide', 'circle-plus'],
  'fa-minus-circle': ['lucide', 'circle-minus'],
  'fa-times-circle': ['lucide', 'circle-x'],
  'fa-check': ['lucide', 'check'],
  'fa-check-circle': ['lucide', 'circle-check'],
  'fa-exclamation-triangle': ['lucide', 'triangle-alert'],
  'fa-warning': ['lucide', 'triangle-alert'],
  'fa-undo': ['lucide', 'undo-2'],
  'fa-refresh': ['lucide', 'refresh-cw'],
  'fa-spinner': ['lucide', 'loader-circle'],
  'fa-ellipsis-h': ['lucide', 'ellipsis'],
  // Devices (settings).
  'fa-desktop': ['lucide', 'monitor'],
  'fa-tablet': ['lucide', 'tablet'],
  'fa-mobile': ['lucide', 'smartphone'],
};

function svgFor(source, name, stroke) {
  let svg = readFileSync(join(modules, sources[source].dir, `${name}.svg`), 'utf8')
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/<title>[\s\S]*?<\/title>/g, '');
  // Only the root element: <rect> and friends need their own width and height.
  svg = svg.replace(/<svg[^>]*>/, (tag) => tag.replace(/\s+(class|width|height|role)="[^"]*"/g, ''));
  svg = svg.replace(/\s+/g, ' ').replace(/> </g, '><').replace(/ \/>/g, '/>').trim();
  if (stroke !== undefined) {
    svg = svg.replace('stroke-width="2"', `stroke-width="${stroke}"`);
  }
  // A mask only reads the alpha channel; any opaque colour works.
  return svg.replaceAll('currentColor', '#000');
}

// Quotes inside become single quotes; everything a CSS url("") or a URL would misread is escaped.
function dataUri(svg) {
  const encoded = svg.replaceAll('"', "'").replace(/[%#<>{}"\n]/g, (c) => encodeURIComponent(c));
  return `url("data:image/svg+xml,${encoded}")`;
}

const rules = [];
for (const [cls, [source, name, options = {}]] of Object.entries(icons)) {
  let { scale, stroke } = options;
  if (source === 'lucide') {
    scale ??= largeLucide(name) ? LUCIDE_SCALE_LARGE : LUCIDE_SCALE;
    stroke ??= arrowLucide(name) ? LUCIDE_STROKE_ARROW : LUCIDE_STROKE;
  }
  const declarations = [`--osec-icon: ${dataUri(svgFor(source, name, stroke))};`];
  if (scale !== undefined) {
    declarations.push(`--osec-icon-scale: ${scale};`);
  }
  rules.push(`.ai1ec-${cls} { ${declarations.join(' ')} } // ${sources[source].name}: ${name}`);
}

const version = (pkg) => JSON.parse(readFileSync(join(modules, pkg, 'package.json'), 'utf8')).version;
const header = [];
const notices = [];
for (const s of Object.values(sources)) {
  const pkg = s.dir.split('/')[0];
  const copyrights = s.license_id === 'CC0-1.0' ? [] : readFileSync(join(modules, s.license), 'utf8')
    .split('\n').filter((l) => /^copyright (\(c\)|©)/i.test(l.trim())).map((l) => l.trim() + (l.includes('Cole Bemis') ? ' (Feather)' : ''));
  notices.push(` * ${s.name} ${version(pkg)} (${s.url}), ${s.license_id}${copyrights.length ? ':' : ''}`,
    ...copyrights.map((c) => ` *   ${c}`));
  // ISC and MIT ask for the notice in all copies: the full text ships with this file.
  const text = s.license_id === 'CC0-1.0'
    ? 'Dedicated to the public domain under CC0 1.0 Universal: https://creativecommons.org/publicdomain/zero/1.0/'
    : readFileSync(join(modules, s.license), 'utf8').trim();
  header.push(`// ${s.name} ${version(pkg)} (${s.url}), ${s.license_id}:`, '//', ...text.split('\n').map((l) => `//   ${l}`.trimEnd()), '//');
}

writeFileSync(out, `// Generated by bin/icons/build-icons.mjs - do not edit; change the map there and rebuild.
//
${header.join('\n')}
/*!
 * OSEC icons from
${notices.join('\n')}
 * Brand logos are trademarks of their owners. Full licence texts: osec-icons-map.less
 */
${rules.join('\n')}
`);
console.log(`${rules.length} icons -> ${out}`);
