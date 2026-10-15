# Twig-JS updater

This is a tool working around the problem that we don't have original build tools for the Bootstrap templates.

Run this to update the Twig-Javascript templates in `public/js/pages/calendar.js` from the source Twig files used in PHP.

```
cd twig_to_js_transform
nvm use
npm i
npm run build-twig-frontend
```

Only a very few templates are used in **frontend rendering**:

```
public/osec_themes/vortex/twig/[agenda|oneday|month].twig
```

It also replaces the twig.js runtime in `calendar.js` (between `/*BEGIN:twig.js runtime*/` and
`/*END:twig.js runtime*/`) with `node_modules/twig/twig.min.js`, so the runtime always matches the
version the templates were compiled with. Templates are created with `autoescape: true`.

**Updating twig.js**

twig.js 3 needs Node.js >= 22 (`.nvmrc`). After `npm update twig`, run the build and check the
calendar's views with "Use frontend rendering" on (the JS smoke suite, `npm run test:js` in
`integration_tests/`, includes an escaping test).

**Do I need this?**

In case you edited one of this twig templates and you don't really want to deal with this, you may turn off *use_frontend_rendering* in Osec Settings. So only the backend templates will be in use.
