#!/usr/bin/env bash
#
# Deploys the release zip to the WordPress.org plugin SVN, as decided by
# release-context.sh (read from $RELEASE_ENV):
#
#   dev     trunk
#   tagged  tags/X.Y.Z, and trunk when RELEASE_UPDATE_TRUNK is true
#   dryrun  like tagged, but prints what it would commit
#   none    nothing
#
# The tag is created as a working copy copy of the updated trunk, so a release
# only uploads what differs from trunk. Uploading a whole tag (~1,500 files)
# timed out on "Committing transaction..." in 2026-09.
#
# An existing tags/X.Y.Z is never touched. Trunk is only replaced while the
# pipeline's commit is still master's HEAD (checked again here, since master
# can move on while the tests run).

set -Eeuo pipefail
set +o history

script_dir=$(cd "$(dirname "$0")" && pwd)
note() { echo "deploy: $*"; }
fail() { echo "deploy: $*" >&2; exit 1; }

: "${WP_ORG_PLUGIN_NAME:?WordPress.org plugin name not set}"
RELEASE_ENV=${RELEASE_ENV:-/tmp/release.env}
RELEASE_ZIP=${RELEASE_ZIP:-/tmp/${OSEC_RELEASE_FILE:?release file name not set}}
SVN_URL=${WP_ORG_SVN_URL:-https://plugins.svn.wordpress.org/$WP_ORG_PLUGIN_NAME}
WORK=${DEPLOY_WORK_DIR:-/tmp/wp-org-deploy}

[[ -s "$RELEASE_ENV" ]] || fail "$RELEASE_ENV is missing"
# shellcheck source=/dev/null
source "$RELEASE_ENV"

case "${RELEASE_MODE:-}" in
    none)
        note "nothing to deploy for this pipeline"
        exit 0
        ;;
    dev | tagged | dryrun) ;;
    *) fail "unknown RELEASE_MODE '${RELEASE_MODE:-}'" ;;
esac
if [[ "$RELEASE_MODE" != "dryrun" ]]; then
    : "${WP_ORG_USERNAME:?WordPress.org username not set}"
    : "${WP_ORG_SVN_PASSWORD:?WordPress.org password not set}"
fi
[[ -s "$RELEASE_ZIP" ]] || fail "release zip $RELEASE_ZIP not found"

tag_path="tags/${RELEASE_VERSION:-}"

if [[ "${RELEASE_UPDATE_TRUNK:-false}" == "true" ]]; then
    head_rc=0
    "$script_dir/still-master-head.sh" || head_rc=$?
    case $head_rc in
        0) ;;
        1)
            if [[ "$RELEASE_MODE" == "dev" ]]; then
                note "master has moved on, trunk is left to the newer pipeline"
                exit 0
            fi
            note "master has moved on, trunk stays: only $tag_path is deployed"
            RELEASE_UPDATE_TRUNK=false
            ;;
        *) fail "could not check whether this commit is still master's HEAD" ;;
    esac
fi
if [[ "$RELEASE_MODE" != "dev" ]] && svn ls "$SVN_URL/$tag_path" > /dev/null 2>&1; then
    note "$tag_path already exists on WordPress.org and is left untouched"
    exit 0
fi

note "mode:        $RELEASE_MODE${RELEASE_VERSION:+ $RELEASE_VERSION}, replace trunk: ${RELEASE_UPDATE_TRUNK:-false}"
note "SVN:         $SVN_URL"
note "release zip: $RELEASE_ZIP"

rm -rf "$WORK"
mkdir -p "$WORK/build"
unzip -q "$RELEASE_ZIP" -d "$WORK/build"
build="$WORK/build/$WP_ORG_PLUGIN_NAME"
[[ -f "$build/$WP_ORG_PLUGIN_NAME.php" ]] || fail "zip does not contain $WP_ORG_PLUGIN_NAME/$WP_ORG_PLUGIN_NAME.php"

# Only trunk is fetched; tags and assets stay empty in the working copy.
note "checking out trunk"
svn checkout --quiet --non-interactive --depth immediates "$SVN_URL" "$WORK/svn"
cd "$WORK/svn"
svn update --quiet --non-interactive --set-depth infinity trunk
note "trunk is at revision $(svn info --show-item last-changed-revision trunk)"

find trunk -mindepth 1 -maxdepth 1 -exec rm -rf {} +
cp -a "$build/." trunk/
svn add --quiet --force --no-ignore trunk
svn status trunk | { grep '^!' || true; } | cut -c9- | while IFS= read -r missing; do
    svn rm --quiet --force "$missing@"
done

sha=${CIRCLE_SHA1:-unknown}
sha=${sha:0:8}
if [[ "$RELEASE_MODE" == "dev" ]]; then
    targets=(trunk)
    message="Deploy development build $sha"
else
    # Copy of the updated trunk: carries trunk's local changes, so only the
    # difference to the committed trunk is uploaded. Committing tags/X.Y.Z
    # without trunk leaves trunk as it is on the server.
    svn copy --quiet trunk "$tag_path"
    targets=("$tag_path")
    [[ "${RELEASE_UPDATE_TRUNK:-false}" == "true" ]] && targets+=(trunk)
    message="Release $RELEASE_VERSION ($sha)"
fi

# Counts of `svn status` by change type, e.g. "2 added, 1 modified, 0 deleted".
summarize() {
    svn status "$@" | awk '
        { c[substr($0, 1, 1)]++ }
        END { printf "%d added, %d modified, %d deleted, %d replaced\n", c["A"], c["M"], c["D"], c["R"] }'
}

changes=$(svn status "${targets[@]}")
if [[ -z "$changes" ]]; then
    note "no changes: ${targets[*]} already equals this build, nothing to commit"
    exit 0
fi
note "to commit:   ${targets[*]}"
note "message:     $message"
note "changes:     $(summarize "${targets[@]}")"
[[ "$RELEASE_MODE" == "dev" ]] || note "$tag_path:  copy of trunk (A +), plus the trunk changes listed below"
note "svn status (first 50 lines):"
head -n 50 <<< "$changes"
lines=$(wc -l <<< "$changes")
[[ $lines -le 50 ]] || note "... and $((lines - 50)) more lines"

if [[ "$RELEASE_MODE" == "dryrun" ]]; then
    note "dry run: nothing committed"
    exit 0
fi

note "committing"
if ! printf '%s' "$WP_ORG_SVN_PASSWORD" | svn commit --non-interactive --no-auth-cache \
    --username "$WP_ORG_USERNAME" --password-from-stdin -m "$message" "${targets[@]}" > "$WORK/commit.log" 2>&1; then
    cat "$WORK/commit.log" >&2
    fail "svn commit failed"
fi
revision=$(sed -nE 's/^Committed revision ([0-9]+)\./\1/p' "$WORK/commit.log")
note "committed revision ${revision:-?}: https://plugins.trac.wordpress.org/changeset/${revision:-}"

if [[ "$RELEASE_MODE" == "tagged" ]]; then
    released=$(svn cat "$SVN_URL/$tag_path/$WP_ORG_PLUGIN_NAME.php" | sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p')
    [[ "$released" == "$RELEASE_VERSION" ]] || fail "$tag_path was not created on WordPress.org (found version '$released')"
    note "verified:    $SVN_URL/$tag_path says Version: $released"
fi
