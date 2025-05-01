# Twig-JS updater

This is a tool working around the problem that we don't have original build tools for the Bootstrap templates. 

Run this to update Twig-Javascript templates from the source Twig files used in PHP.

```
cd twig_to_js_transform
nvm use
npm i 
npm run transform
```

Only a very few templates are used in **frontend rendering**:

```
public/osec_themes/vortex/twig/[agenda|oneday|month].twig
```

**DO NOT UPDATE**

Is mandatory to use twig:"^0.7.2 for to keep old stuff from ai1ec working.

**Do I need this?**

In case you edited one of this twig templates and you don't really want to deal with this, you may turn off *use_frontend_rendering* in Osec Settings. So only the backend templates will be in use.
