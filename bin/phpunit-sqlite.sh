#!/bin/bash
#
# Runs PHPUnit against SQLite, as WordPress Playground and the WordPress.org Live Preview
# run the plugin. CI does the same in db_job_sqlite.
#
# Copies the MySQL test WordPress (/tmp/wordpress, /tmp/wordpress-tests-lib, from
# bin/install-wp-tests.sh) to $OSEC_SQLITE_DIR, adds the SQLite Database Integration
# drop-in there and leaves the MySQL setup untouched.
#
#   bin/phpunit-sqlite.sh                    # all tests
#   bin/phpunit-sqlite.sh --filter Exception # arguments go to phpunit
#
# Env: OSEC_SQLITE_DIR (default /tmp/osec-sqlite-tests), SQLITE_PLUGIN_VERSION (default 3.0.2),
#      WP_CORE_DIR / WP_TESTS_SOURCE_DIR (the MySQL setup to copy).

set -Eeuo pipefail

cd "$(dirname "$0")/.."
DIR=${OSEC_SQLITE_DIR:-/tmp/osec-sqlite-tests}
VERSION=${SQLITE_PLUGIN_VERSION:-3.0.2}
SOURCE_WP=${WP_CORE_DIR:-/tmp/wordpress}
SOURCE_TESTS=${WP_TESTS_SOURCE_DIR:-/tmp/wordpress-tests-lib}

[ -f "$SOURCE_TESTS/wp-tests-config.php" ] || { echo "No test WordPress at $SOURCE_TESTS - run bin/install-wp-tests.sh first." >&2; exit 1; }

if [ ! -f "$DIR/wordpress/wp-content/db.php" ] || ! grep -q SQLITE_DB_DROPIN_VERSION "$DIR/wordpress/wp-content/db.php"; then
    echo "Setting up $DIR (SQLite Database Integration $VERSION)"
    rm -rf "$DIR"
    mkdir -p "$DIR/db"
    cp -a "$SOURCE_WP" "$DIR/wordpress"
    cp -a "$SOURCE_TESTS" "$DIR/tests-lib"
    plugin=$DIR/wordpress/wp-content/plugins/sqlite-database-integration
    curl -sSfL -o "$DIR/sqlite.zip" "https://downloads.wordpress.org/plugin/sqlite-database-integration.$VERSION.zip"
    unzip -q "$DIR/sqlite.zip" -d "$DIR/wordpress/wp-content/plugins/"
    sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$plugin#" \
        -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#g" \
        "$plugin/db.copy" > "$DIR/wordpress/wp-content/db.php"
    sed -i "s#^define( 'ABSPATH'.*#define( 'ABSPATH', '$DIR/wordpress/' );\ndefine( 'DB_DIR', '$DIR/db/' );\ndefine( 'DB_FILE', 'phpunit.sqlite' );#" \
        "$DIR/tests-lib/wp-tests-config.php"
fi

rm -f "$DIR/db/phpunit.sqlite"
status=0
WP_TESTS_DIR="$DIR/tests-lib" vendor/bin/phpunit "${@:-tests}" || status=$?
# The run must have used SQLite, not fallen back to MySQL.
[ -s "$DIR/db/phpunit.sqlite" ] || { echo "No SQLite database written - did the drop-in load?" >&2; exit 1; }
exit $status
