#!/usr/bin/env bash
#
# Replaces the WordPress.org plugin SVN assets/ (banners, icons, screenshots)
# with ./assets. Runs on master pushes only.

set -Eeuo pipefail
set +o history

fail() { echo "deploy-assets: $*" >&2; exit 1; }

[[ -n "${CIRCLECI:-}" ]] || fail "this script can only be run by CircleCI"
[[ "${CIRCLE_BRANCH:-}" == "master" ]] || fail "assets are deployed from master only"
: "${WP_ORG_PLUGIN_NAME:?WordPress.org plugin name not set}"
: "${WP_ORG_USERNAME:?WordPress.org username not set}"
: "${WP_ORG_SVN_PASSWORD:?WordPress.org password not set}"

SVN_URL=${WP_ORG_SVN_URL:-https://plugins.svn.wordpress.org/$WP_ORG_PLUGIN_NAME}
source_dir=$(pwd)/assets
work=/tmp/wp-org-assets

rm -rf "$work"
svn checkout --quiet --non-interactive --depth immediates "$SVN_URL" "$work"
cd "$work"
svn update --quiet --non-interactive --set-depth infinity assets

find assets -mindepth 1 -maxdepth 1 -exec rm -rf {} +
cp -a "$source_dir/." assets/
svn add --quiet --force --no-ignore assets
svn status assets | { grep '^!' || true; } | cut -c9- | while IFS= read -r missing; do
    svn rm --quiet --force "$missing@"
done

svn status assets
printf '%s' "$WP_ORG_SVN_PASSWORD" | svn commit --quiet --non-interactive --no-auth-cache \
    --username "$WP_ORG_USERNAME" --password-from-stdin -m "Deploy OSEC assets" assets
