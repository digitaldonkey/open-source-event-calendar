#!/usr/bin/env bash
#
# Gate for `wp plugin check` (WordPress plugin-check).
#
# `wp plugin check` always exits 0, even with errors, so it cannot be used as a
# gate on its own. This wrapper runs it, keeps only findings in files that are
# actually shipped, prints a report and exits non-zero when anything remains.
#
# Exit codes:
#   0  no blocking finding
#   1  blocking findings (ERROR, or WARNING with --strict)
#   2  the check could not be run or its output could not be trusted
#
# Requires jq; yq as well, unless --release is given.
#
# Usage:
#   bin/plugin-check.sh [--strict] [--release] [--slug=<slug>] [--wporg-slug=<slug>]
#                       [--wp=<binary>] [--path=<wp-dir>]
#
#   --strict       Also fail on WARNING.
#   --release      Target is a built release, so every finding is in a shipped
#                  file: report everything instead of filtering by the release
#                  white list. Needs no yq.
#   --slug         Plugin directory to check. Default: open-source-event-calendar.
#   --wporg-slug   wordpress.org slug the checks assume, passed to plugin-check as
#                  --slug. Keeps text domain and readme checks correct when the
#                  plugin is checked from a directory with another name.
#                  Default: open-source-event-calendar.
#   --wp           WP-CLI binary. Default: /usr/local/bin/wp when executable, else wp.
#   --path         WordPress installation to run in, passed to WP-CLI as --path.

set -Eeuo pipefail

readonly EXIT_OK=0
readonly EXIT_FINDINGS=1
readonly EXIT_ERROR=2

readonly PLUGIN_CHECK_PACKAGE='wpackagist-plugin/plugin-check'
readonly MODE='new'

# Resolved without dirname/realpath: the script must survive a minimal PATH.
script_path="${BASH_SOURCE[0]}"
case "$script_path" in
    */*) script_dir="${script_path%/*}" ;;
    *)   script_dir='.' ;;
esac
SCRIPT_DIR=$(cd "$script_dir" >/dev/null 2>&1 && pwd) || exit 2
readonly ROOT="${SCRIPT_DIR%/*}"

abort() {
    printf 'plugin-check gate: %s\n' "$*" >&2
    exit "$EXIT_ERROR"
}

usage() {
    cat <<'USAGE'
Usage: bin/plugin-check.sh [--strict] [--release] [--slug=<slug>] [--wporg-slug=<slug>]
                           [--wp=<binary>] [--path=<wp-dir>]
USAGE
}

strict=false
release=false
slug='open-source-event-calendar'
wporg='open-source-event-calendar'
wp_path=''
if [ -x /usr/local/bin/wp ]; then
    wp_bin='/usr/local/bin/wp'
else
    wp_bin='wp'
fi

for argument in "$@"; do
    case "$argument" in
        --strict) strict=true ;;
        --release) release=true ;;
        -h|--help) usage; exit "$EXIT_OK" ;;
        --slug=*) slug="${argument#--slug=}" ;;
        --wporg-slug=*) wporg="${argument#--wporg-slug=}" ;;
        --wp=*) wp_bin="${argument#--wp=}" ;;
        --path=*) wp_path="${argument#--path=}" ;;
        *) abort "unknown argument $argument"$'\n'"$(usage)" ;;
    esac
done

wp_global=()
if [ -n "$wp_path" ]; then
    wp_global+=("--path=$wp_path")
fi

command -v jq >/dev/null 2>&1 || abort 'jq is required but not installed.'
if [ "$release" = false ]; then
    command -v yq >/dev/null 2>&1 \
        || abort 'yq is required to read OSEC_RELEASE_WHITE_LIST (or use --release).'
fi

# Version of plugin-check pinned in composer.lock.
if ! pinned=$(jq -r --arg name "$PLUGIN_CHECK_PACKAGE" '
        [ (.packages // [])[], ((."packages-dev") // [])[] ]
        | map(select(.name == $name))
        | if length == 0 then "" else (.[0].version | ltrimstr("v")) end
    ' "$ROOT/composer.lock" 2>/dev/null); then
    abort "cannot read $ROOT/composer.lock"
fi
[ -n "$pinned" ] || abort "$PLUGIN_CHECK_PACKAGE is not in composer.lock"

# WP-CLI loads wp-content/plugins/plugin-check, not vendor/plugin-check, so without
# this the gate silently enforces whatever version happens to be installed.
if ! installed=$("$wp_bin" plugin get plugin-check --field=version \
        ${wp_global[@]+"${wp_global[@]}"} 2>&1); then
    abort "cannot read the installed plugin-check version: $installed"
fi
installed=$(printf '%s' "$installed" | tr -d '[:space:]')
if [ "$installed" != "$pinned" ]; then
    abort "plugin-check version mismatch: WordPress has $installed, composer.lock pins $pinned."$'\n'"The gate must enforce the pinned version - install $pinned or update the composer pin deliberately."
fi

stderr_file=$(mktemp)
trap 'rm -f "$stderr_file"' EXIT

set +e
output=$("$wp_bin" plugin check "$slug" \
    --slug="$wporg" \
    --mode="$MODE" \
    --format=strict-json \
    --fields=file,line,type,code,message \
    ${wp_global[@]+"${wp_global[@]}"} 2>"$stderr_file")
status=$?
set -e
if [ "$status" -ne 0 ]; then
    abort "wp plugin check failed: $(cat "$stderr_file")$output"
fi

# Fail closed: anything that is neither a JSON array nor the documented success
# line is a broken run, never "nothing found".
trimmed=${output#"${output%%[![:space:]]*}"}
case "$trimmed" in
    \[*)
        if ! findings=$(printf '%s' "$trimmed" | jq -c 'if type == "array" then . else error("no array") end' 2>/dev/null); then
            abort 'cannot parse the JSON output of wp plugin check'
        fi
        ;;
    *)
        if printf '%s' "$output" | grep -Eq '^Success: .*No errors found\.'; then
            findings='[]'
        else
            abort "unexpected output from wp plugin check:"$'\n'"$output"
        fi
        ;;
esac

# Mirrors create_release_job: copy OSEC_RELEASE_WHITE_LIST, remove .distignore
# entries, delete every *.sh and *.cmd. Read from those files rather than
# restated here, so the gate cannot drift from what is actually packaged.
skipped=0
if [ "$release" = false ]; then
    if ! white_list=$(yq -e '.references.OSEC_RELEASE_WHITE_LIST' "$ROOT/.circleci/config.yml" 2>/dev/null); then
        abort 'could not read OSEC_RELEASE_WHITE_LIST from .circleci/config.yml'
    fi
    # Word splitting is intended: the white list is one space separated scalar.
    # shellcheck disable=SC2086
    white_list_json=$(printf '%s\n' $white_list | jq -R . | jq -s 'map(select(. != ""))')
    [ "$(printf '%s' "$white_list_json" | jq 'length')" -ge 5 ] \
        || abort 'OSEC_RELEASE_WHITE_LIST holds fewer entries than expected'

    dist_ignore_json=$(
        { grep -v -e '^[[:space:]]*#' -e '^[[:space:]]*$' "$ROOT/.distignore" || true; } \
            | sed -e 's/[[:space:]]*$//' -e 's#/$##' | jq -R . | jq -s .
    )

    before=$(printf '%s' "$findings" | jq 'length')
    findings=$(printf '%s' "$findings" | jq -c \
        --argjson wl "$white_list_json" \
        --argjson di "$dist_ignore_json" '
        def ships($p):
            if ($p | test("\\.(sh|cmd)$")) then false
            else
                (any($wl[]; . as $e
                    | if ($e | endswith("/")) then ($p | startswith($e)) else ($p == $e) end))
                and (all($di[]; . as $e
                    | ($p != $e) and (($p | startswith($e + "/")) | not)))
            end;
        map(select(ships((.file // "") | ltrimstr("./") | ltrimstr("/"))))
    ')
    skipped=$(( before - $(printf '%s' "$findings" | jq 'length') ))
fi

errors=$(printf '%s' "$findings" | jq '[ .[] | select(.type == "ERROR") ] | length')
warnings=$(printf '%s' "$findings" | jq '[ .[] | select(.type != "ERROR") ] | length')

printf '%s' "$findings" | jq -r '
    group_by(.file) | sort_by(.[0].file)[] | .[] |
    [ (.file // "?"), (.type // "?"), ((.line // "?") | tostring), (.code // "?"),
      ((.message // "") | gsub("[\t\n]"; " ")) ] | @tsv
' | sed -e 's/&quot;/"/g' -e 's/&#0*39;/'"'"'/g' -e 's/&lt;/</g' -e 's/&gt;/>/g' -e 's/&amp;/\&/g' \
  | awk -F '\t' '
        $1 != last { printf "\n%s\n", $1; last = $1 }
        { printf "  %-7s line %-5s %s\n    %s\n", $2, $3, $4, $5 }
    '

if [ "$release" = true ]; then
    target='the release build'
    suffix=''
else
    target='shipped files of the working tree'
    suffix=$(printf ' (%d finding(s) in files that do not ship)' "$skipped")
fi
printf '\nplugin-check %s (mode=%s) on %s: %d error(s), %d warning(s)%s\n' \
    "$pinned" "$MODE" "$target" "$errors" "$warnings" "$suffix"

if [ "$errors" -gt 0 ] || { [ "$strict" = true ] && [ "$warnings" -gt 0 ]; }; then
    exit "$EXIT_FINDINGS"
fi

exit "$EXIT_OK"
