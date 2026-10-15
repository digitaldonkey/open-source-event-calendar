#!/bin/bash
#
# Puts WordPress on SQLite, as WordPress Playground and the WordPress.org Live Preview run it: installs the
# SQLite Database Integration plugin into <wordpress-dir>/wp-content/plugins/ and its db.php drop-in into
# <wordpress-dir>/wp-content/. Where the database file goes is the caller's (DB_DIR / DB_FILE in the config).
#
#   bin/sqlite-dropin.sh /tmp/wordpress
#
# Used by bin/phpunit-sqlite.sh and the SQLite release test (CI release_test_job_sqlite).

set -Eeuo pipefail

# The release asset of WordPress/sqlite-database-integration, with the sha256 GitHub publishes for it.
VERSION=3.1.0
SHA256=02a7958a27959c00f2d579ea27f2047ad2fe6a2c4758c2459d36d0b0ae0b6624
URL=https://github.com/WordPress/sqlite-database-integration/releases/download/v$VERSION/plugin-sqlite-database-integration.zip

[ "${1:-}" = --version ] && { echo "$VERSION"; exit 0; }

fail() { echo "sqlite-dropin: $*" >&2; exit 1; }

WP_DIR=${1:?usage: bin/sqlite-dropin.sh <wordpress-dir>}
[ -d "$WP_DIR/wp-content/plugins" ] || fail "no WordPress at $WP_DIR"

plugin=$WP_DIR/wp-content/plugins/sqlite-database-integration
download=$(mktemp -d)
trap 'rm -rf "$download"' EXIT

curl -sSfL -o "$download/sqlite.zip" "$URL"
echo "$SHA256  $download/sqlite.zip" | sha256sum -c --quiet - || fail "checksum mismatch for $URL"
unzip -q "$download/sqlite.zip" -d "$download"
rm -rf "$plugin"
mv "$download/plugin-sqlite-database-integration" "$plugin"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$plugin#" \
    -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#g" \
    "$plugin/db.copy" > "$WP_DIR/wp-content/db.php"
grep -q "SQLITE_DB_DROPIN_VERSION" "$WP_DIR/wp-content/db.php" || fail "db.php is not the SQLite drop-in."
echo "SQLite Database Integration $VERSION installed in $WP_DIR"
