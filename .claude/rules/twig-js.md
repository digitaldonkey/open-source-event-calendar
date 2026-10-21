---
paths:
  - "public/osec_themes/*/twig/agenda.twig"
  - "public/osec_themes/*/twig/oneday.twig"
  - "public/osec_themes/*/twig/month.twig"
  - "public/js/pages/calendar.js"
  - "twig_to_js_transform/**"
  - "integration_tests/mocha/js_smoke/**"
---

<!-- Loaded when working on the frontend-rendered views. CLAUDE.md keeps the "re-run the transform" rule. -->

## Twig → JS Frontend Templates

- Three Twig templates double as the **frontend-rendering** (client-side JS) templates: `public/osec_themes/vortex/twig/{agenda,oneday,month}.twig`. Frontend rendering only applies when the OSEC Settings option `use_frontend_rendering` is enabled — otherwise only their backend (PHP) rendering matters.
- **If you edit `agenda.twig`, `oneday.twig`, or `month.twig`, you must re-run the transform**, or the JS-side templates silently go stale:
  ```bash
  cd twig_to_js_transform
  nvm use
  npm install
  npm run build-twig-frontend
  ```
- What it does: compiles each `.twig` file to twig-js, then splices the compiled output into `public/js/pages/calendar.js` (the only served copy, inherited from the original vendor's unknown build tooling) — replacing only the content between matching `/*REPLACE:<template>.twig*/` marker comments — and writes the twig.js runtime (`node_modules/twig/twig.min.js`) between `/*BEGIN:twig.js runtime*/` and `/*END:twig.js runtime*/`. The standalone `public/js/{agenda,oneday,month}.js` copies were never loaded and are gone since 1.2.0.
- **twig.js 3 with `autoescape: true`** since 1.2.0 (B18; 0.7.2 had no autoescape and printed event values raw). The templates escape like PHP Twig, so `|raw` / `|e('html_attr')` in them mean the same in both. The browser output was byte-identical to 0.7.2 before autoescape and identical to PHP after it (except the view links the server builds per request). twig 3 needs Node >= 22 (`twig_to_js_transform/.nvmrc`: `lts/krypton`).
- **Escaping test**: `integration_tests/mocha/js_smoke/frontend.spec.js` "prints event values as text in a view rendered by twig.js", with the seeded "Hostile venue" event. The smoke suite runs against a sandbox with `BASE_URL=https://claude.ddev-wordpress.ddev.site OSEC_TEST_HOST_RULES='MAP *.ddev-wordpress.ddev.site ddev-router'` (seed it there first: `WP_SANDBOX_DB=claude /usr/local/bin/wp eval-file bin/dev-seed-block-test-data.php`).
- **The calendar bundle is served through PHP** (`?osec_render_js=calendar.js`, `max-age=0`), not as a static file - a fresh browser session always gets the current build.
- No original build tooling exists for these templates (this script is a workaround). If you'd rather not deal with it after a Twig edit, `use_frontend_rendering` can be turned off in OSEC Settings so only backend rendering is used.
