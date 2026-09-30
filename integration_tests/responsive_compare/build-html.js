/**
 * Build the comparison pages from the PNGs capture.js produced: current code vs a tagged
 * release, side by side at each captured width, one section per calendar view. Writes one page
 * per theme (<theme>-compare.html) plus an index.html linking them, next to the PNGs, so the
 * whole directory can be opened straight from the dev site.
 *
 * Usage: node build-html.js <outDir> <tagLabel> <theme...>
 * Env: WIDTHS      comma list, default 320,768,1440
 *      VIEWS       comma list, default month,week,oneday,agenda
 *      DATE        default exact_date, shown per view unless VIEW_DATES overrides it
 *      VIEW_DATES  per-view exact_date overrides, e.g. oneday=2026-9-23,week=21-9-2026
 */
const path = require('path');
const fs = require('fs');

const [outDir, tagLabel, ...themes] = process.argv.slice(2);
const widths = (process.env.WIDTHS || '320,768,1440').split(',').map(Number);
const views = (process.env.VIEWS || 'month,week,oneday,agenda').split(',');
const viewLabels = { month: 'Month', week: 'Week', oneday: 'Day', agenda: 'Agenda' };
const viewDates = Object.fromEntries((process.env.VIEW_DATES || '')
    .split(',').filter(Boolean).map((pair) => pair.split('=')));
const dateOf = (view) => viewDates[view] || process.env.DATE || '15-9-2026';

if (!outDir || !tagLabel || themes.length === 0) {
    console.error('Usage: node build-html.js <outDir> <tagLabel> <theme...>');
    process.exit(1);
}

const style = `
    body { font-family: system-ui, sans-serif; margin: 0; padding: 0 16px 48px; background: #f6f6f6; color: #222; }
    header { position: sticky; top: 0; background: #f6f6f6; padding: 12px 0 8px; z-index: 1;
        border-bottom: 1px solid #ddd; }
    h1 { font-size: 18px; margin: 0 0 6px; }
    h2 { font-size: 16px; margin: 32px 0 8px; }
    nav a { margin-right: 14px; }
    .meta { font-size: 12px; color: #666; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; background: #fff; }
    td { width: 50%; vertical-align: top; border: 1px solid #ccc; padding: 8px; }
    .cap { font: 12px monospace; margin: 0 0 6px; color: #333; }
    .missing { font-size: 12px; color: #b00; font-style: italic; }
    img { max-width: 100%; display: block; border: 1px solid #eee; }
    a img { cursor: zoom-in; }`;

function page(title, body) {
    return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${title}</title>
<style>${style}</style>
</head>
<body>
${body}
</body>
</html>
`;
}

function section(theme, view) {
    const rows = widths.map((width) => {
        const cells = ['current', tagLabel].map((version) => {
            const file = `${theme}-${view}-${version}-${width}.png`;
            const label = version === 'current' ? 'Current (working tree)' : version;
            const factsFile = path.join(outDir, file.replace(/\.png$/, '.json'));
            const facts = fs.existsSync(factsFile) ? JSON.parse(fs.readFileSync(factsFile, 'utf8')) : {};
            return `<td>
    <div class="cap">${label} - ${width}px${facts.timelyFontSize
        ? ` - .timely ${facts.timelyFontSize}`
        : (facts.viewFontSize ? ` - no .timely, view ${facts.viewFontSize}` : '')}</div>
    ${fs.existsSync(path.join(outDir, file))
        ? `<a href="${file}" target="_blank"><img src="${file}" alt="${label} at ${width}px" loading="lazy"></a>`
        : '<div class="missing">missing</div>'}
</td>`;
        }).join('\n');
        return `<tr>${cells}</tr>`;
    }).join('\n');
    return `<section id="${view}">
<h2>${viewLabels[view] || view} view <span class="meta">exact_date~${dateOf(view)}</span></h2>
<table>
${rows}
</table>
</section>`;
}

const stamp = new Date().toISOString().replace('T', ' ').slice(0, 16) + ' UTC';
const themeNav = themes.map((t) => `<a href="${t}-compare.html">${t}</a>`).join('');

for (const theme of themes) {
    const viewNav = views.map((v) => `<a href="#${v}">${viewLabels[v] || v}</a>`).join('');
    const file = path.join(outDir, `${theme}-compare.html`);
    fs.writeFileSync(file, page(`${theme} - current vs ${tagLabel}`, `<header>
<h1>${theme} theme - current vs ${tagLabel}</h1>
<nav>${viewNav} <span class="meta">| themes: ${themeNav} | <a href="index.html">index</a> | built ${stamp}</span></nav>
</header>
${views.map((v) => section(theme, v)).join('\n')}`));
    console.log(file);
}

const index = path.join(outDir, 'index.html');
fs.writeFileSync(index, page(`Responsive compare - current vs ${tagLabel}`, `<header>
<h1>Responsive compare - current vs ${tagLabel}</h1>
<p class="meta">Built ${stamp}. Widths: ${widths.join(', ')}px.</p>
</header>
<ul>
${themes.map((t) => `<li><a href="${t}-compare.html">${t}</a>: ${views.map((v) =>
    `<a href="${t}-compare.html#${v}">${viewLabels[v] || v}</a>`).join(', ')}</li>`).join('\n')}
</ul>`));
console.log(index);
