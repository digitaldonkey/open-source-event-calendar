# Plugin directory screenshots

`assets/screenshot-1.png` … `-8.png`: the WordPress.org screenshots (captions in the plugin `README.md`), taken with
Playwright inside the DDEV web container from the test content (`tests/data/content/`, showcase events only).

```
cd bin/screenshots && npm install && cd ../..                            # once
WP_SANDBOX_DB=try_pw node bin/screenshots/shoot.js                       # all shots into assets/
WP_SANDBOX_DB=try_pw node bin/screenshots/shoot.js screenshot-2.png --out=/tmp/shots
```

From the host: `ddev osec-screenshots [try_<name>] [screenshot-N.png …] [--out=<dir>]` (`bin/ddev/commands/web/`).

1. `wp osec content generate --set=showcase --site-settings`: it replaces only content it created earlier and refuses
   a site with other calendar data, so use a sandbox (`WP_SANDBOX_DB`) when the site has own events.
2. Warns when the site's WordPress theme is not the dataset's (`site.theme`, Twenty Twenty-Five): the images would
   show another theme around the calendar.
3. Shoots every entry of `shots.json` (or only the named files) at its viewport and the device pixel ratio `scale`
   (2, like the plugin directory images). A failed shot is reported and the run goes on; exit code 1 if any failed.

Review the result with `git diff --stat assets/` and by looking at the images before committing: `assets/` is deployed
to the plugin directory from master. `screenshot-9.png` (schema.org validator) stays manual: the validator cannot
reach a local site.

The browser connection (through ddev-router), WP-CLI and the login cookies come from the visual regression suite
(`integration_tests/visual_regression/lib/`); `@playwright/test` here must stay at the same version as there and as
the DDEV `playwright` service image.

## Files

| File | Role |
|---|---|
| `shots.json` | The shot list and the device pixel ratio (`scale`). |
| `shoot.js` | The runner. |
| `package.json` | `@playwright/test`, pinned. |

## Shot keys

| Key | Meaning |
|---|---|
| `file`, `caption` | Output file and its caption (printed; the plugin `README.md` holds the published captions). |
| `path` | URL path; `{calendar}` = calendar page path, `{anchor}` = anchor date (Y-m-d), e.g. `{calendar}action~month/exact_date~{anchor}/`. |
| `width`, `height`, `mobile` | Viewport in CSS pixels; `mobile` emulates a phone (viewport meta tag, touch). |
| `admin` | Logged in as the site's first administrator. |
| `edit_event` | Edit screen of the event with this dataset key. |
| `editor_block` | Page key from the manifest (`block`): opened in the block editor, the calendar block selected, only the block is shot. |
| `scroll_to` | Scroll this element to the top. |
| `click`, `wait_for` | Click an element; wait for another to be visible. |
| `hover`, `hover_event` | Hover the first element matching the selector (containing that event's title). Without `hover` the pointer is parked in the bottom right corner. |
| `manual` | Not automated: printed as a reminder. |
