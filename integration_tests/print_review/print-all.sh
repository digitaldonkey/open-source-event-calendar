#!/bin/bash
#
# Regenerate every print PDF for manual review and print a summary table.
#
# Runs inside the DDEV web container. From the host use the custom command
# (.ddev/commands/web/print-preview), which also prints the URL of the output directory:
#
#   ddev print-preview [--themes=plana] [--browsers=chrome] [--date=15-9-2026] [--keep]
#
# or call it directly, from the container or with ddev exec:
#
#   integration_tests/print_review/print-all.sh
#
# Options (environment):
#   THEMES=plana,vortex     OSEC themes to print. Switching writes to the dev database.
#   BROWSERS=chrome,firefox Browsers to print with. Firefox needs the selenium-firefox
#                           container from .ddev/docker-compose.selenium-firefox.yaml; a
#                           browser whose Selenium node is unreachable is skipped.
#   OUT=<dir>               Output directory, default wp-content/uploads/claude.
#   BASE_URL=<url>          Site to print, default $DDEV_PRIMARY_URL.
#   DATE=15-9-2026          exact_date of the printed views.
#   BUSY_DATE=23-9-2026     Date used for the "busy" run (many parallel events).
#   QUIET_DATE=15-10-2026   Date used for the "quiet month" run.
#   KEEP=1                  Keep existing PDFs instead of deleting them first.
#
# The print button and Ctrl+P are printed for every view; in addition one busy day/week/month
# and one quiet month, which are the two cases that decide the page count.
set -u

cd "$(dirname "$0")" || exit 1

THEMES=${THEMES:-plana,vortex}
BROWSERS=${BROWSERS:-chrome,firefox}
OUT=${OUT:-/var/www/html/wp-content/uploads/claude}
BASE_URL=${BASE_URL:-${DDEV_PRIMARY_URL:-https://ddev-wordpress.ddev.site}}
DATE=${DATE:-15-9-2026}
BUSY_DATE=${BUSY_DATE:-23-9-2026}
QUIET_DATE=${QUIET_DATE:-15-10-2026}
# DDEV's stable WP-CLI; a bare `wp` is the plugin's vendor/bin/wp dev build (see CLAUDE.md).
WP=${WP:-/usr/local/bin/wp}

export BASE_URL

fail() { echo "print-all: $*" >&2; exit 1; }

command -v node >/dev/null || fail "node not found - run this inside the DDEV web container"
command -v flock >/dev/null && {
    # One run at a time: two would fight over the theme in the database and over the
    # output directory, and files would end up labelled with the wrong theme.
    exec 9>"${LOCK:-/tmp/osec-print-all.lock}"
    flock -n 9 || fail "another print run is in progress - wait for it to finish"
}
[ -d ../node_modules/selenium-webdriver ] || fail "run 'npm install' in integration_tests first"

# Theme lives in the database, so restore whatever was set when we started.
original_theme=$($WP eval 'global $osec_app; $t = $osec_app->options->get("osec_current_theme"); echo $t["stylesheet"];' --path=/var/www/html 2>/dev/null)
[ -n "$original_theme" ] || fail "could not read the current OSEC theme via $WP"

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
    # Drop the cached CSS and warm the calendar page: with OSEC_PARSE_LESS_FILES_AT_EVERY_REQUEST
    # every request recompiles the theme LESS, which can outlast the browser's wait.
    curl -sk "$BASE_URL/?osec-css-cache=$(date +%s)" -o /dev/null
    curl -sk "$BASE_URL/calendar/action~month/exact_date~$DATE/" -o /dev/null
}

run() { # browser label date modes views...
    local browser=$1 label=$2 date=$3 modes=$4
    shift 4
    DATE="$date" MODES="$modes" node print-pdfs.js "$browser" "$label" "$OUT" "$@" \
        || echo "print-all: $browser/$label failed" >&2
}

if [ "${KEEP:-}" != "1" ]; then
    # Only files this script named (label-view-mode-YYYYMMDD-HHMM.pdf), so PDFs put
    # in the directory by hand survive a run.
    rm -f "$OUT"/*-[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]-[0-9][0-9][0-9][0-9].pdf
fi
mkdir -p "$OUT"

trap 'switch_theme "$original_theme"' EXIT

IFS=',' read -r -a browser_list <<< "$BROWSERS"
available=()
for browser in "${browser_list[@]}"; do
    if curl -s -m 5 "http://selenium-$browser:4444/status" | grep -q '"ready": *true'; then
        available+=("$browser")
    else
        echo "print-all: skipping $browser - no Selenium node at http://selenium-$browser:4444" >&2
    fi
done
[ ${#available[@]} -gt 0 ] || fail "no Selenium node reachable"
browser_list=("${available[@]}")
IFS=',' read -r -a theme_list <<< "$THEMES"

for theme in "${theme_list[@]}"; do
    switch_theme "$theme"
    for browser in "${browser_list[@]}"; do
        # Every file carries the browser, so names stay comparable between browsers.
        prefix="$browser-$theme"
        echo "== $theme / $browser"
        run "$browser" "$prefix"             "$DATE"       ctrlp,button month week oneday agenda
        run "$browser" "$prefix-busy"        "$BUSY_DATE"  button       month week oneday
        run "$browser" "$prefix-quietmonth"  "$QUIET_DATE" ctrlp,button month
    done
done

echo
echo "== $OUT"
printf '%-60s %-10s %s\n' FILE ORIENTATION PAGES
for f in "$OUT"/*.pdf; do
    [ -e "$f" ] || continue
    # Firefox writes compressed PDFs, so ask ImageMagick rather than grepping for MediaBox.
    size=$(magick identify -density 72 -format '%w %h' "$f[0]" 2>/dev/null)
    pages=$(magick identify -density 18 "$f" 2>/dev/null | wc -l)
    set -- $size
    orientation=portrait
    [ "${1:-0}" -gt "${2:-0}" ] && orientation=landscape
    printf '%-60s %-10s %s\n' "$(basename "$f")" "$orientation" "$pages"
done
