#!/bin/bash
#
# Runs PHPUnit against SQLite, as WordPress Playground and the WordPress.org Live Preview
# run the plugin. CI runs this script in db_job_sqlite.
#
# Copies the MySQL test WordPress (/tmp/wordpress, /tmp/wordpress-tests-lib, from
# bin/install-wp-tests.sh) to $OSEC_SQLITE_DIR, adds the SQLite Database Integration
# drop-in there and leaves the MySQL setup untouched. The copy is set up again when the
# driver version or the source test WordPress changes.
#
#   bin/phpunit-sqlite.sh                    # all tests
#   bin/phpunit-sqlite.sh --filter Exception # arguments go to phpunit (default: tests)
#
# Env: OSEC_SQLITE_DIR (default /tmp/osec-sqlite-tests),
#      WP_CORE_DIR / WP_TESTS_SOURCE_DIR (the MySQL setup to copy).

set -Eeuo pipefail

# The release asset of WordPress/sqlite-database-integration, with the sha256 GitHub publishes for it.
VERSION=3.1.0
SHA256=02a7958a27959c00f2d579ea27f2047ad2fe6a2c4758c2459d36d0b0ae0b6624
URL=https://github.com/WordPress/sqlite-database-integration/releases/download/v$VERSION/plugin-sqlite-database-integration.zip

cd "$(dirname "$0")/.."
DIR=${OSEC_SQLITE_DIR:-/tmp/osec-sqlite-tests}
SOURCE_WP=${WP_CORE_DIR:-/tmp/wordpress}
SOURCE_TESTS=${WP_TESTS_SOURCE_DIR:-/tmp/wordpress-tests-lib}

fail() { echo "phpunit-sqlite: $*" >&2; exit 1; }

[ -f "$SOURCE_TESTS/wp-tests-config.php" ] && [ -f "$SOURCE_WP/wp-includes/version.php" ] \
    || fail "no test WordPress at $SOURCE_WP / $SOURCE_TESTS - run bin/install-wp-tests.sh first."

stamp="driver $VERSION, $(md5sum "$SOURCE_WP/wp-includes/version.php" "$SOURCE_TESTS/wp-tests-config.php" | cut -d' ' -f1 | tr '\n' ' ')"
if [ "$(cat "$DIR/setup.txt" 2>/dev/null)" != "$stamp" ]; then
    echo "Setting up $DIR (SQLite Database Integration $VERSION)"
    rm -rf "$DIR"
    mkdir -p "$DIR/db" "$DIR/download"
    cp -a "$SOURCE_WP" "$DIR/wordpress"
    cp -a "$SOURCE_TESTS" "$DIR/tests-lib"

    curl -sSfL -o "$DIR/download/sqlite.zip" "$URL"
    echo "$SHA256  $DIR/download/sqlite.zip" | sha256sum -c --quiet - || fail "checksum mismatch for $URL"
    unzip -q "$DIR/download/sqlite.zip" -d "$DIR/download"
    plugin=$DIR/wordpress/wp-content/plugins/sqlite-database-integration
    mv "$DIR/download/plugin-sqlite-database-integration" "$plugin"
    sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$plugin#" \
        -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#g" \
        "$plugin/db.copy" > "$DIR/wordpress/wp-content/db.php"
    grep -q "SQLITE_DB_DROPIN_VERSION" "$DIR/wordpress/wp-content/db.php" || fail "db.php is not the SQLite drop-in."

    sed -i "s#^define( 'ABSPATH'.*#define( 'ABSPATH', '$DIR/wordpress/' );\ndefine( 'DB_DIR', '$DIR/db/' );\ndefine( 'DB_FILE', 'phpunit.sqlite' );#" \
        "$DIR/tests-lib/wp-tests-config.php"
    grep -q "^define( 'DB_DIR', '$DIR/db/' );" "$DIR/tests-lib/wp-tests-config.php" \
        || fail "could not set ABSPATH/DB_DIR in wp-tests-config.php (format changed?)."

    rm -rf "$DIR/download"
    echo "$stamp" > "$DIR/setup.txt"
fi

rm -f "$DIR/db/phpunit.sqlite"
status=0
WP_TESTS_DIR="$DIR/tests-lib" vendor/bin/phpunit "${@:-tests}" || status=$?
# The run must have used SQLite, not fallen back to MySQL.
[ -s "$DIR/db/phpunit.sqlite" ] || fail "no SQLite database written - did the drop-in load?"
exit $status
