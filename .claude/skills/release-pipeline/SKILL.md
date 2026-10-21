---
name: release-pipeline
description: How this plugin's CircleCI release pipeline works - static_release_job and plugin-check gates (and what they do not cover), probe branches, what each branch/tag triggers, tagging a WordPress.org release, release-context.sh. Use when changing .circleci/, release scripts or generated release files, probing a gate, or tagging a release.
---

# Release pipeline and CircleCI branches

## Release & Build Tooling

- Two jobs gate a release: `static_release_job` (four generated files - see the inventory below for which, and for the three it does *not* cover) and `release_test_job`, which runs WordPress plugin-check against the built zip. Anything shipped **must be committed to git first**, or the first job's `git status` checks fail
- `README.txt` is generated from `README.md` — never hand-edit it: `ddev wp osec make_readme`
- `hooks-and-filters.md` is generated from PHPDoc by the separate [`hookster_markdown`](https://github.com/digitaldonkey/hookster_markdown) tool and must be regenerated before each release:
  ```bash
  git clone git@github.com:digitaldonkey/hookster_markdown.git
  cd hookster_markdown && npm install && npm run build
  ```

### What `static_release_job` gates - and what it does not

Four steps in one job under `set -Eeuxo pipefail`, so **the job stops at the first failing gate** and
the later ones never run. Each was verified in both directions on 2026-09-24 - a passing run *and* a
deliberately broken commit that turned it red:

| Step | Regenerates | What actually gates |
|---|---|---|
| Verify WordPress version metadata | nothing - `osec prepare_release` is read-only by design | the command's own `WP_CLI::error()` exit 1; the `git status` wrapper around it is vestigial and can never fire |
| Verify Readme.txt | `README.txt` from `README.md` + plugin headers | `git status --porcelain` at the repo root |
| Verify Twig frontend templates | `public/js/pages/calendar.js` | `git status -s` from `twig_to_js_transform/` |
| Verify hooks-and-filters.md | `hooks-and-filters.md` | `git status --porcelain` at the repo root |

**The hooks gate was inert until `fcaab93d`**, and its failure mode is the one to watch for: it ran
`git status` *after* `cd hookster_markdown`, a separately **cloned repository**, so it inspected a tree
the build never touches and answered "No changes detected" unconditionally. The Twig step uses the same
`cd` + `git status` shape and is fine, because `twig_to_js_transform/` is a subdirectory of *this* repo
and `git status` from a subdirectory reports the whole repo with `../`-prefixed paths. **A nested clone
breaks such a check; a subdirectory does not.**

A hand-edit alone does not reproduce a readme failure: `make_readme` overwrites `README.txt`, so an
uncommitted edit is silently restored and the tree comes back clean. The stale content must be committed.

**Not gated, though shipped in `OSEC_RELEASE_WHITE_LIST`:**

- **`languages/`** - stale as of 2026-09-24. The committed `.pot` carries `POT-Creation-Date: 2025-05-20`
  and `Project-Id-Version: … 1.0.2` with 512 msgids; `wp i18n make-pot` yields 569. Strings added since
  (e.g. the recurrence-truncated notice) are absent, so they are untranslatable in all three locales, and
  the `.po` files sit at 512 too. `a58d837b` hand-edited 4 lines of the `.pot` rather than regenerating it.
- **`calendar_block/build/`** - tracked output of `wp-scripts build` from `calendar_block/src/`. In sync
  today (both last touched by `1d6c041f`), but nothing stops the next `src/` edit shipping a stale bundle.

**A probe branch's name must match the job's own filter, and the right name differs per job** - get it
wrong and the probe passes by never running the gate, the same false pass one level up.
`static_release_job` is filtered to `only: /^(master|release-.*)$/`, so **probe it from a `release-…`
branch**. Use **one branch per gate** (`set -e` hides every gate after the first failure), and trim the
workflow to `build -> static_job -> static_release_job` to keep the probe off the Selenium matrix. For the
plugin-check gate the rule inverts - see below. Nothing can publish from such a branch: all three deploy
jobs are `only: /master/` *and* carry a `circleci-agent step halt` guard.

### The other release gate: plugin-check in `release_test_job`

`bin/plugin-check.sh` (run locally, by GrumPHP's `all_tests` / `prepare_release`, and by
`release_test_job`'s "WordPress plugin-check (release build)" step against the unzipped release).
**`wp plugin check` always exits 0**, so the wrapper is what makes it a gate: ERROR fails, WARNING only
prints unless `--strict`. Mechanics, filtering and the one known false positive are in `TESTING.md`.

Verified in both directions (2026-09-24):

- green - `a5627205` on `feature/plugin-check-gate`, run `0b4e26c9…`: step `exit=0`,
  `0 error(s), 1 warning(s)`. This also settles that the WP-CLI 3.0 dev build at `$WP_CLI` registers
  plugin-check's command;
- red - probe `ci-probe-plugin-check` (`6e27471b`), run `b67fed37…`: one line putting
  `leaflet.js` back on `https://unpkg.com/` made the step `exit=1` with
  `PluginCheck.CodeAnalysis.Offloading.OffloadedContent` and failed the job.

**Do not name this probe branch `release-…`.** `release_test_job` (8.2) carries **no** branch filter,
while `release_test_job_php_8_{3,4,5}` are `master|release-*` - a `release-…` name buys four Selenium
jobs instead of one.

Two things a probe of *this* gate must get right, or it proves nothing:

- **inject into a file that ships** (`src/`, i.e. in `OSEC_RELEASE_WHITE_LIST` and not in `.distignore`) -
  the wrapper drops findings in files the zip never contains, so a probe in `tests/` or `bin/` is filtered
  away. Verify by rebuilding the release tree locally and grepping for the line;
- **keep `phpcs` and `phpunit` green**, because `create_release_job` requires `static_job` and `db_job`;
  a probe that trips one of those never reaches the gate. A probe commit therefore still deserves a
  `phpcs` run.

Cost is small: once the step fails, the later steps are skipped, so Apache and `node/install-packages`
never run and the `when: always` integration step dies in seconds for lack of `node_modules` - no Selenium
time. The whole red run took 3m07s.


## CircleCI Behavior by Branch

What the CircleCI pipeline (`.circleci/config.yml`) does differently depending on which branch or tag triggers it:

- `master` — runs the full test matrix; replaces the WordPress.org SVN **trunk** and the GitHub `dev` prerelease, on every push (skipped when master has moved on, so a rerun never rolls back); contains the latest bugfixes
- tag `X.Y.Z` — runs the full test matrix, then creates SVN `tags/X.Y.Z` and the GitHub release `X.Y.Z` (see below)
- tag `X.Y.Z-dryrun` — the same, but prints what it would commit and publishes nothing
- `release-*` — runs the full test matrix; does not create a release
- Next-release branch (e.g. `1.2.x-dev` if the current release is `1.1.x`) — a dev branch for the next semantic major version; should include all bugfixes from master; no release

### Releasing to WordPress.org

```bash
# on master, once the release commit (version bumped everywhere) is pushed and green
git tag -a 1.2.0 -m "Release 1.2.0"
git push origin 1.2.0
```

- **`.circleci/release-context.sh` decides, once, in `release_decision_job`** (master and release tags only,
  after all tests; `build` stays release-free) and writes `/tmp/release.env` (`RELEASE_MODE`
  none/dev/tagged/dryrun, `RELEASE_VERSION`, `RELEASE_UPDATE_TRUNK`) for the GitHub and SVN jobs. A tag that
  fails a check fails that job, so nothing is deployed:
  - `X.Y.Z` or `X.Y.Z-dryrun` only;
  - the tagged commit is on master;
  - the tag equals plugin header `Version` and `Stable Tag`, `README.txt` `Stable Tag` and `OSEC_VERSION`;
  - the tag is higher than every other `X.Y.Z` tag.
- **Trunk (and the GitHub `dev` release) is only replaced while the pipeline's commit is master's HEAD.**
  Checked in `release_decision_job` and again right before each deploy (`.circleci/still-master-head.sh`), because
  master can move on while the pipeline runs. A master pipeline that was overtaken deploys nothing; a tag on
  an older master commit creates just `tags/X.Y.Z`. If the check itself fails (fetch error), the job fails.
- **The release commit reaches trunk through its master pipeline before the tag exists** - WordPress.org
  serves trunk as that version until the tag pipeline finishes. Tag it before pushing anything else to master.
- **An existing `tags/X.Y.Z` is never touched**, so rerunning a tag pipeline is harmless.
- **The tag is committed as a working-copy copy of the updated trunk**, so a release uploads only what differs
  from trunk. A whole-tag upload (~1,500 files) timed out on "Committing transaction..." in 2026-09.
- **Only the maintainer can push `X.Y.Z` tags**: GitHub tag ruleset "Release tags" (`refs/tags/*.*.*`,
  creation/update/deletion restricted, bypass: repository admin). `ghr` therefore must not use `-delete`
  for a release tag.
- **`.circleci/tests/test-release-scripts.sh`** (run in `release_decision_job`) covers the release scripts against a local git
  origin and a local `file://` SVN repository. Run it after any change to them.
- Before 2026-09-29 a pushed tag ran nothing and the "tagged" branch of the old deploy script could never
  be taken, so 1.1.12 - 1.1.14 never got SVN tags; `tags/1.1.14` and a repaired `tags/1.1.5` were added by
  hand (r3719399).
