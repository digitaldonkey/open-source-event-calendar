---
paths:
  - "src/App/Model/DatabaseCharset.php"
  - "src/App/Model/DatabaseSchema.php"
  - "src/**/*Charset*"
---

<!-- Loaded when working on the schema / charset code. Moved from CLAUDE.md "Database". -->

## Database charset

**Charset**: new tables get the site's charset and collation (`$wpdb->get_charset_collate()`, utf8mb4).
`dbDelta()` never converts an existing table, so tables from older releases or restored backups stay utf8
(utf8mb3) and reject emoji / 4-byte characters (the row fails to save). `DatabaseCharset` finds them (only on
the calendar's admin screens and in Site Health, never per request) and converts them **on demand**: the
"Upgrade database charset (utf8mb4)" box on the Settings page, shown only while a table needs it (REST `POST osec/v1/database/convert`).
The conversion is one `ALTER TABLE ... CONVERT` per table; it fails atomically on a duplicate under the new
collation or a too-long index, and then says why. `wpdb` caches table charsets per request - a save in the same
request as the conversion still sees utf8mb3. SQLite reports every table as utf8mb4: nothing to convert there.
