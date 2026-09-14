---
name: prepare-release
description: Interactively walks through this plugin's pre-release checklist - runs all_tests, checks release metadata (WordPress core version, plugin header consistency), applies fixes with confirmation, and regenerates README.txt / hooks-and-filters.md. Use when the user asks to prepare, cut, or check readiness for a release of open-source-event-calendar.
---

# Prepare Release

Walks through this plugin's release-readiness checklist end to end, asking
before every file edit or environment change. Never commits - that's left
to the user.

## Steps

1. **Run the test suite.** Always re-run it yourself, even if the user says
   it already passed:
   ```
   vendor/bin/grumphp run --testsuite=all_tests
   ```
   If this fails, stop and report the failure. Do not proceed to release
   prep on a red test suite.

2. **Run the release check:**
   ```
   wp osec prepare_release
   ```
   This is read-only - it never edits any file. It fails on the *first*
   issue it hits (they run sequentially in one try/catch), so you'll only
   see one problem per run. Loop: fix the one reported issue → re-run this
   command → repeat, until it reports "Release metadata looks good."

   Route the failure message to the matching step below:

3. **If the failure says "WordPress core is outdated":**
   Ask the user before running `wp core update` (this mutates the dev
   environment's live WordPress install). If they agree, run it, then go
   back to step 2.

4. **If the failure says "'Tested up to' ... is behind the latest WordPress version":**
   Ask the user before editing. If they agree, update the `Tested up to:`
   line in `open-source-event-calendar.php`'s plugin header docblock to the
   major.minor version named in the error message, then go back to step 2.

5. **If the failure says "version mismatch - OSEC_VERSION: ..."** (or the
   user is bumping to a new release version even without this failure):
   Ask the user for the target version number. Then ask before applying it
   - if they agree, update all three of these together in one pass:
   - `constants.php`: the `OSEC_VERSION` constant
   - `open-source-event-calendar.php`'s header: the `Version:` line
   - `open-source-event-calendar.php`'s header: the `Stable Tag:` line

   Then go back to step 2.

6. **Once step 2 passes cleanly**, regenerate the release docs (these two
   commands write to disk - that's expected and correct here):
   ```
   wp osec make_readme
   cd hookster_markdown && node build.js
   ```

7. **Show a diff summary** of everything touched, for the user to review:
   ```
   git diff --stat README.txt hooks-and-filters.md open-source-event-calendar.php constants.php
   ```
   Offer to show the full diff of any of these files if the user wants to
   look closer.

8. **Do not commit.** Tell the user the working tree is ready for review
   and that committing is their call, per this repo's convention (never
   commit unless explicitly asked).

## Notes

- `wp osec prepare_release` does not check `Requires at least` - that's a
  deliberate compatibility-floor decision (see `hookster_markdown`-adjacent
  git history / `git log -S"Requires at least: 6.7"`), not something to
  sync to "latest." Don't touch it as part of this flow.
- If `wp osec prepare_release` fails with a wordpress.org connectivity
  error ("version-check returned HTTP ..." or "could not parse the current
  WordPress version"), that's a network problem, not a release-metadata
  problem - report it plainly rather than treating it like steps 3-5.
