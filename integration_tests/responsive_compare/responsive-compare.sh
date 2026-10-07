#!/bin/bash
#
# Screenshot the calendar views at several viewport widths, for the current working tree and
# for the latest tagged release, per theme - then build one comparison page per theme
# (<theme>-compare.html, plus an index.html), side by side, one section per view. Open them from
# the dev site: $BASE_URL/wp-content/uploads/claude/responsive-compare/index.html
#
# Every run starts by deleting the previous run's output (screenshots, pages, and any PDFs an
# older version of this tool left behind), so nothing outdated can end up in the comparison -
# except, with SKIP_TAG, the current $TAG's own screenshots, which that mode reuses.
#
# Runs inside the DDEV web container.
#
#   integration_tests/responsive_compare/responsive-compare.sh
#
# Options (environment):
#   THEMES=vortex,plana     OSEC themes to compare. Switching writes to the dev database.
#   BASE_FONT=13px          Pin every theme to this "Base font size" instead of its own default
#                           (vortex 13px, plana 1rem, umbra 0.8rem) - otherwise a theme-to-theme
#                           comparison varies the base size as well as the theme. The effective
#                           value is read back and printed after every switch either way. Saved
#                           LESS variables are captured up front and restored on exit.
#   VIEWS=month,week,oneday,agenda
#                           Calendar views (URL actions) to capture; oneday is the Day view.
#   WIDTHS=320,768,1440     Viewport widths (px) to capture.
#   TAG=<latest release>    Tagged release to compare against. Defaults to the newest X.Y.Z tag
#                           by version order (not by date, and ignoring tags like "dev"), so
#                           it follows each new release without editing this script - which
#                           then also needs a cached vendor/ for that tag, see below.
#   SKIP_TAG=1              Reuse the $TAG screenshots already in $OUT and only re-shoot the
#                           working tree - no stash, no checkout, no vendor/ swap. The tag
#                           doesn't change, so its shots only need taking once.
#   OUT=<dir>               Output directory, default wp-content/uploads/claude/responsive-compare.
#   BASE_URL=<url>          Site to shoot, default $DDEV_PRIMARY_URL.
#   DATE=15-9-2026          exact_date for every view not listed in VIEW_DATES.
#   VIEW_DATES=oneday=2026-9-23,week=21-9-2026
#                           Per-view exact_date overrides (view=date, comma separated) - Day and
#                           Week need their own dates to show a representative set of events.
#
# For the tag half of the run, this script `git stash`es the working tree, `git checkout`s
# $TAG in place, swaps in a cached vendor/ built for that tag (composer dependencies are
# gitignored, so checkout alone doesn't change them - and the tag's own vendor/wikimedia/less.php
# version matters, it's literally what's under test), then reverses all of that afterward. It
# never uses a git worktree - a worktree-based version of this script was tried first and
# dropped; this directory (integration_tests/responsive_compare/) is excluded from the stash
# pathspec so the running scripts can never stash themselves out from under this process.
#
# Requires a cached vendor/ for $TAG, built once:
#   git worktree add /tmp/osec-tag-<TAG> <TAG>   # or: git clone --branch <TAG> ... /tmp/osec-tag-<TAG>
#   (cd /tmp/osec-tag-<TAG> && composer install --no-dev)
#   mkdir -p /var/www/html/wp-content/osec-tag-vendor-cache
#   cp -a /tmp/osec-tag-<TAG>/vendor /var/www/html/wp-content/osec-tag-vendor-cache/<TAG>
#   rm -rf /tmp/osec-tag-<TAG>                   # (or: git worktree remove /tmp/osec-tag-<TAG>)
#
# If this script is interrupted before the "swap back" trap runs, restore by hand, from the
# plugin root:
#   rm -rf vendor && mv /var/www/html/wp-content/osec-vendor-stash vendor
#   git checkout <your-branch>
#   git stash list   # find the "responsive-compare: before <TAG>" entry, then:
#   git stash pop
set -u

cd "$(dirname "$0")" || exit 1
PLUGIN_DIR=/var/www/html/wp-content/plugins/open-source-event-calendar
cd "$PLUGIN_DIR" || exit 1

THEMES=${THEMES:-vortex,plana}
WIDTHS=${WIDTHS:-320,768,1440}
VIEWS=${VIEWS:-month,week,oneday,agenda}
TAG=${TAG:-$(git tag --list --sort=-v:refname | grep -E '^[0-9]+\.[0-9]+\.[0-9]+$' | head -n 1)}
OUT=${OUT:-/var/www/html/wp-content/uploads/claude/responsive-compare}
OUT_URL_BASE=wp-content/uploads/claude/responsive-compare
BASE_URL=${BASE_URL:-${DDEV_PRIMARY_URL:-https://ddev-wordpress.ddev.site}}
DATE=${DATE:-15-9-2026}
VIEW_DATES=${VIEW_DATES:-oneday=2026-9-23,week=21-9-2026}
# Each theme ships its own "Base font size" default (vortex 13px, plana 1rem, umbra 0.8rem),
# so a theme-to-theme comparison is otherwise measuring two variables at once. Set this to pin
# every theme to the same base size; leave empty to shoot each theme as it comes.
BASE_FONT=${BASE_FONT:-}
SKIP_TAG=${SKIP_TAG:-}
WP=${WP:-/usr/local/bin/wp}
VENDOR_CACHE=/var/www/html/wp-content/osec-tag-vendor-cache/${TAG}
VENDOR_STASH=/var/www/html/wp-content/osec-vendor-stash
# Relative to $PLUGIN_DIR - this script's own directory, excluded from the stash so it can
# never disappear out from under the running process.
SELF_DIR=integration_tests/responsive_compare

export BASE_URL VIEWS VIEW_DATES WIDTHS DATE

fail() { echo "responsive-compare: $*" >&2; exit 1; }

[ -n "$TAG" ] || fail "no X.Y.Z release tag found - set TAG explicitly"
echo "== comparing against $TAG"

command -v node >/dev/null || fail "node not found - run this inside the DDEV web container"
if [ -n "$SKIP_TAG" ]; then
    for theme in ${THEMES//,/ }; do
        for view in ${VIEWS//,/ }; do
            for width in ${WIDTHS//,/ }; do
                [ -f "$OUT/$theme-$view-$TAG-$width.png" ] \
                    || fail "SKIP_TAG set, but $OUT/$theme-$view-$TAG-$width.png does not exist - run once without it"
            done
        done
    done
else
    [ -d "$VENDOR_CACHE" ] || fail "no cached vendor/ for $TAG at $VENDOR_CACHE - see header comment"
fi
[ -d integration_tests/node_modules/selenium-webdriver ] || fail "run 'npm install' in integration_tests first"
[ -f "${SELF_DIR}/capture.js" ] || fail "capture.js not found under $SELF_DIR"
if [ -e .git/MERGE_HEAD ] || [ -d .git/rebase-merge ] || [ -d .git/rebase-apply ]; then
    fail "a merge/rebase is in progress - resolve it before running this"
fi
curl -s -m 5 http://selenium-chrome:4444/status | grep -q '"ready": *true' \
    || fail "no Selenium node reachable at http://selenium-chrome:4444"

command -v flock >/dev/null && {
    # A theme switch, a stash, and a checkout are all global to the site/repo: two runs at
    # once would corrupt each other's shots (or worse, each other's git state).
    exec 9>"${LOCK:-/tmp/osec-responsive-compare.lock}"
    flock -n 9 || fail "another comparison run is in progress - wait for it to finish"
}

original_branch=$(git rev-parse --abbrev-ref HEAD)
[ -n "$original_branch" ] && [ "$original_branch" != HEAD ] \
    || fail "not on a branch (detached HEAD?) - refusing to guess where to come back to"

original_theme=$($WP eval 'global $osec_app; $t = $osec_app->options->get("osec_current_theme"); echo $t["stylesheet"];' --path=/var/www/html 2>/dev/null)
[ -n "$original_theme" ] || fail "could not read the current OSEC theme via $WP"

# Switching theme deletes the saved LESS variables below, which would otherwise take any tuned
# theme options with it for good. Keep a copy and put it back on the way out.
original_less_vars=$($WP eval '
  global $osec_app;
  echo base64_encode(serialize($osec_app->options->get(
    \Osec\App\Controller\LessController::DB_KEY_FOR_LESS_VARIABLES, [])));' \
  --path=/var/www/html 2>/dev/null)

restore_less_vars() {
    [ -n "$original_less_vars" ] || return
    OSEC_VARS="$original_less_vars" $WP eval '
      global $osec_app;
      $key = \Osec\App\Controller\LessController::DB_KEY_FOR_LESS_VARIABLES;
      $vars = @unserialize(base64_decode(getenv("OSEC_VARS")));
      if (is_array($vars) && $vars) {
        $osec_app->options->set($key, $vars);
      } else {
        $osec_app->options->delete($key);
      }' --path=/var/www/html >/dev/null 2>&1
}

# The effective base size, saved variables unioned with the current theme's own defaults - i.e.
# what the LESS compile will actually use, not just what happens to be stored.
read_base_font() {
    $WP eval '
      global $osec_app;
      $vars = \Osec\App\Controller\LessController::factory($osec_app)->get_saved_variables(false);
      echo isset($vars["baseFontSize"]["value"]) ? $vars["baseFontSize"]["value"] : "";' \
      --path=/var/www/html 2>/dev/null
}

switch_theme() {
    OSEC_THEME="$1" $WP eval '
      global $osec_app;
      $t = $osec_app->options->get("osec_current_theme");
      $t["theme_dir"] = $t["theme_root"] . "/" . getenv("OSEC_THEME");
      $t["theme_url"] = preg_replace("~/[a-z]+$~", "/" . getenv("OSEC_THEME"), $t["theme_url"]);
      $t["stylesheet"] = getenv("OSEC_THEME");
      $osec_app->options->delete(\Osec\App\Controller\LessController::DB_KEY_FOR_LESS_VARIABLES);
      $osec_app->options->set("osec_current_theme", $t);' --path=/var/www/html >/dev/null 2>&1 \
      || fail "could not switch the theme to $1"
    # Must happen before the recompile below, or the shot is of the previous base size.
    if [ -n "$BASE_FONT" ]; then
        OSEC_BASE_FONT="$BASE_FONT" $WP eval '
          global $osec_app;
          $key = \Osec\App\Controller\LessController::DB_KEY_FOR_LESS_VARIABLES;
          $vars = \Osec\App\Controller\LessController::factory($osec_app)
                    ->get_saved_variables(false);
          $vars["baseFontSize"]["value"] = getenv("OSEC_BASE_FONT");
          $osec_app->options->set($key, $vars);' --path=/var/www/html >/dev/null 2>&1 \
          || fail "could not pin baseFontSize to $BASE_FONT for theme $1"
    fi
    # Always read it back: the base size is per theme, so a switch silently changes it unless
    # it is pinned - and then every em-based size, and the whole compiled stylesheet, differ
    # between the two halves of the comparison for a reason that has nothing to do with the code.
    base_font=$(read_base_font)
    [ -n "$base_font" ] || fail "could not read the base font size after switching to $1"
    echo "-- theme $1, base font size $base_font"
    if [ -n "$BASE_FONT" ] && [ "$base_font" != "$BASE_FONT" ]; then
        fail "base font size is $base_font after switching to $1, expected $BASE_FONT"
    fi
    # Force a real recompile. The cache-bust URL alone does not: a stored stylesheet is handed
    # back, so every shot after a theme switch silently kept the previous theme's stylesheet and
    # base size (measured: 16px instead of a pinned 13px). Both layouts, as the tagged release may
    # be 1.1.x - see CLAUDE.md, "CSS Compile Caching":
    #  - 1.1.x: <prefix>_osec_compiled.css in the plugin cache/css/ or open_source_event_calendar_cache/css/
    #    in uploads; the option osec_compiled.css needs a fresh timestamp (a number links the route).
    #  - 1.2+: osec-compiled-<blog_id>.css in osec_cache/css/ in uploads (or the override); without
    #    the option osec_css the page links the route, which compiles.
    local uploads=/var/www/html/wp-content/uploads
    rm -f "$PLUGIN_DIR"/cache/css/*_osec_compiled.css "$PLUGIN_DIR"/cache/css/osec-compiled-*.css \
        "$uploads"/open_source_event_calendar_cache/css/*osec_compiled.css \
        "$uploads"/osec_cache/css/osec-compiled-*.css
    $WP option delete osec_css --path=/var/www/html >/dev/null 2>&1 || true
    $WP option update osec_compiled.css "$(date +%s)" --path=/var/www/html >/dev/null 2>&1 \
        || fail "could not reset the compiled-CSS option for theme $1"
    curl -sk "$BASE_URL/?osec-css-cache=$(date +%s)" -o /dev/null
    curl -sk "$BASE_URL/calendar/action~month/exact_date~$DATE/" -o /dev/null
    # The tagged release's LessController (unlike current) reuses one Less_Parser across
    # every compile a php-fpm worker does in its lifetime. With recompile-per-request that
    # means: fine on a worker's first compile since it started, a thrown
    # Less_Exception_Compiler on its second+ - silently dropping the inline CSS, not a 500 -
    # so it's flaky per request depending on which pooled worker picks it up (confirmed: a
    # plana capture right after a vortex one in the same run came back completely
    # unstyled). Restarting guarantees the very next request is a fresh worker's first
    # compile, sidestepping the bug without touching the tagged code.
    reset_fpm_opcache
}

swapped=0
# `wp eval opcache_reset()` only clears opcache for that one throwaway CLI process - the
# actual web requests are served by long-lived php-fpm workers with their own opcache/statics,
# which keep old state (including, pre-checkout-swap, a since-changed vendor/autoload class
# hash - "Class ...ComposerStaticInitXXXX not found" as a fatal 500) after the swap. Has to be
# cleared in the FPM pool itself.
reset_fpm_opcache() {
    supervisorctl restart php-fpm >/dev/null 2>&1 || fail "could not restart php-fpm to clear opcache"
    # Wait for the pool to actually accept connections again before the next request. Checked
    # at the process level, not with an HTTP request: OSEC's echo_css() hooks wp_head, which
    # fires on every front-end page including the site root, so a probe request here would
    # itself be "the worker's first compile" and leave the caller's very next request - the
    # one that's actually supposed to get that fresh worker, per the tagged-release bug above -
    # exposed to the same flake if it lands on the same worker.
    for _ in $(seq 1 20); do
        supervisorctl status php-fpm 2>/dev/null | grep -q RUNNING && { sleep 1; return; }
        sleep 0.5
    done
    fail "php-fpm did not come back up after restart"
}

stashed=0
swap_to_tag() {
    [ "$swapped" = "1" ] && return
    # Only stash if there's actually something to stash - `git stash push` with nothing to
    # save doesn't create an entry, and popping later would then pop an unrelated older stash.
    if [ -n "$(git status --porcelain -- . ":!${SELF_DIR}")" ]; then
        git stash push -u -m "responsive-compare: before $TAG" -- . ":!${SELF_DIR}" \
            || fail "git stash failed"
        stashed=1
    fi
    git checkout --quiet "$TAG" || {
        [ "$stashed" = 1 ] && git stash pop --quiet
        fail "git checkout $TAG failed"
    }
    # vendor/ is gitignored, so checkout alone doesn't touch it - and the tag's own compiled
    # dependencies (specifically vendor/wikimedia/less.php) are exactly what's under test here,
    # not whatever current happens to have installed.
    rm -rf "$VENDOR_STASH"
    mv vendor "$VENDOR_STASH" 2>/dev/null
    cp -a "$VENDOR_CACHE" vendor || fail "could not stage cached vendor/ for $TAG"
    swapped=1
    reset_fpm_opcache
}
swap_to_current() {
    [ "$swapped" = "0" ] && return
    rm -rf vendor
    mv "$VENDOR_STASH" vendor || fail "could not restore vendor/ - it's saved at $VENDOR_STASH, move it back by hand"
    git checkout --quiet "$original_branch" || fail "git checkout $original_branch failed - working tree may be at $TAG, stash may still hold your changes"
    if [ "$stashed" = 1 ]; then
        git stash pop --quiet || fail "git stash pop failed - resolve manually, your changes are still in the stash"
        stashed=0
    fi
    swapped=0
    reset_fpm_opcache
}

# Always land back on the original branch/vendor/theme/LESS variables, even on failure or
# interrupt. restore_less_vars runs last: switch_theme wipes them, and re-pins BASE_FONT.
trap 'swap_to_current; switch_theme "$original_theme" >/dev/null 2>&1; restore_less_vars' EXIT

mkdir -p "$OUT"
# Clear out the previous run. Only the file types this tool writes, and only in its own
# directory; with SKIP_TAG the $TAG screenshots stay, since they are what that mode reuses.
keep=()
[ -n "$SKIP_TAG" ] && keep=(! -name "*-${TAG}-*.png" ! -name "*-${TAG}-*.json")
find "$OUT" -maxdepth 1 -type f \( -name '*.png' -o -name '*.json' -o -name '*.html' -o -name '*.pdf' \) \
    "${keep[@]}" -print -delete | sed 's/^/removed /'
IFS=',' read -r -a theme_list <<< "$THEMES"
IFS=',' read -r -a width_list <<< "$WIDTHS"

capture_version() {
    local label=$1
    for theme in "${theme_list[@]}"; do
        switch_theme "$theme"
        echo "== $label / $theme"
        node "${SELF_DIR}/capture.js" "$label" "$theme" "$OUT" "${width_list[@]}" \
            || echo "responsive-compare: capture failed for $label/$theme" >&2
    done
}

capture_version current
if [ -z "$SKIP_TAG" ]; then
    swap_to_tag
    # Releases up to 1.1.14 parse neither date form in the URL and silently show today instead;
    # a timestamp is the one form every release accepts. Current keeps the written form, since
    # parsing it is part of what is under test there.
    DATE_AS_TIMESTAMP=1 capture_version "$TAG"
    swap_to_current
else
    echo "== $TAG: reusing existing screenshots in $OUT (SKIP_TAG)"
fi
switch_theme "$original_theme"

# Every shot records the font size .timely really rendered with (capture.js). The option read
# back after each switch only proves what was stored - this proves what was drawn, so a stale
# stylesheet can't pass for a comparison at the pinned size again.
# Only for px: the measurement is a computed px value, so a rem/em pin can't be compared as-is.
if [[ "$BASE_FONT" == *px ]]; then
    report=$(node -e '
        const fs = require("fs"), path = require("path");
        const [dir, want] = process.argv.slice(1);
        const lines = [];
        for (const f of fs.readdirSync(dir).filter((n) => n.endsWith(".json")).sort()) {
            const d = JSON.parse(fs.readFileSync(path.join(dir, f), "utf8"));
            const name = f.replace(/\.json$/, "");
            if (d.timelyFontSize === null) {
                lines.push("WARN " + name + ": no .timely around the view - the setting cannot apply (view renders at " + d.viewFontSize + ")");
            } else if (d.timelyFontSize !== want) {
                lines.push("FAIL " + name + ": .timely " + d.timelyFontSize);
            }
        }
        console.log(lines.join("\n"));' "$OUT" "$BASE_FONT")
    [ -z "$report" ] || echo "$report"
    echo "$report" | grep -q '^FAIL' && fail "shots not rendered at BASE_FONT=$BASE_FONT (see FAIL lines)"
    echo "== every .timely rendered at $BASE_FONT"
fi

echo "== building comparison pages"
node "${SELF_DIR}/build-html.js" "$OUT" "$TAG" "${theme_list[@]}" >/dev/null \
    || fail "could not build the comparison pages"

echo
echo "== open: $BASE_URL/$OUT_URL_BASE/index.html"
for theme in "${theme_list[@]}"; do
    echo "   $BASE_URL/$OUT_URL_BASE/$theme-compare.html"
done
