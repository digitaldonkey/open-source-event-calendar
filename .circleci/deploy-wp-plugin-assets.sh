#!/usr/bin/env bash
#
# Replaces the WordPress.org plugin SVN assets/ (banners, icons, screenshots)
# with ./assets. Runs on master pushes only.

set -Eeuo pipefail
set +o history

note() { echo "deploy-assets: $*"; }
fail() { echo "deploy-assets: $*" >&2; exit 1; }

[[ -n "${CIRCLECI:-}" ]] || fail "this script can only be run by CircleCI"
[[ "${CIRCLE_BRANCH:-}" == "master" ]] || fail "assets are deployed from master only"
: "${WP_ORG_PLUGIN_NAME:?WordPress.org plugin name not set}"
: "${WP_ORG_USERNAME:?WordPress.org username not set}"
: "${WP_ORG_SVN_PASSWORD:?WordPress.org password not set}"

SVN_URL=${WP_ORG_SVN_URL:-https://plugins.svn.wordpress.org/$WP_ORG_PLUGIN_NAME}
source_dir=$(pwd)/assets
work=/tmp/wp-org-assets
message="Deploy OSEC assets"

note "SVN:         $SVN_URL"
note "source:      $source_dir ($(find "$source_dir" -type f | wc -l) files)"

rm -rf "$work"
note "checking out assets"
svn checkout --quiet --non-interactive --depth immediates "$SVN_URL" "$work"
cd "$work"
svn update --quiet --non-interactive --set-depth infinity assets
note "assets is at revision $(svn info --show-item last-changed-revision assets)"

find assets -mindepth 1 -maxdepth 1 -exec rm -rf {} +
cp -a "$source_dir/." assets/
svn add --quiet --force --no-ignore assets
svn status assets | { grep '^!' || true; } | cut -c9- | while IFS= read -r missing; do
    svn rm --quiet --force "$missing@"
done

changes=$(svn status assets)
if [[ -z "$changes" ]]; then
    note "no changes: assets already equals ./assets, nothing to commit"
    exit 0
fi
note "message:     $message"
note "changes:     $(awk '{ c[substr($0, 1, 1)]++ } END { printf "%d added, %d modified, %d deleted, %d replaced", c["A"], c["M"], c["D"], c["R"] }' <<< "$changes")"
note "svn status:"
echo "$changes"

note "committing"
if ! printf '%s' "$WP_ORG_SVN_PASSWORD" | svn commit --non-interactive --no-auth-cache \
    --username "$WP_ORG_USERNAME" --password-from-stdin -m "$message" assets > "$work/commit.log" 2>&1; then
    cat "$work/commit.log" >&2
    fail "svn commit failed"
fi
revision=$(sed -nE 's/^Committed revision ([0-9]+)\./\1/p' "$work/commit.log")
note "committed revision ${revision:-?}: https://plugins.trac.wordpress.org/changeset/${revision:-}"
