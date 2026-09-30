# OSEC Blocks

One wp-scripts build for all blocks. Each block lives in its own folder under `src/` with its own
`block.json`; shared React components are in `src/components/`.

| Block | Name | PHP | Status |
|---|---|---|---|
| `src/classic/` | `open-source-event-calendar/osec-calendar-classic` | `ClassicBlockController` | Server-rendered calendar (Bootstrap theme views) |
| `src/react-big-calendar/` | `open-source-event-calendar/react-big-calendar` | `BigCalendarBlockController` | Early development: [react-big-calendar](https://github.com/jquense/react-big-calendar), events from the `osec/v1/days` REST route |

Never change a block's `name`: saved posts reference it.

## Building

```
cd open-source-event-calendar/blocks
npm ci
npm run build   # or: npm run start
```

`build/` is committed and shipped. The build also writes `build/blocks-manifest.php`, which
`BootstrapController` passes to `wp_register_block_metadata_collection()`.

dayjs locales are loaded on demand, one chunk per locale in `build/react-big-calendar/dayjs-locale/`.

## Tests

```
npm run test:unit   # Vitest
```
