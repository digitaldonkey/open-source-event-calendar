#!/bin/bash
#
# Runs PHPUnit against SQLite, as WordPress Playground and the WordPress.org Live Preview
# run the plugin. CI runs this script in db_job_sqlite.
#
# Copies the MySQL test WordPress (/tmp/wordpress, /tmp/wordpress-tests-lib, from
# bin/install-wp-tests.sh) to $OSEC_SQLITE_DIR, adds the SQLite drop-in there
# (bin/sqlite-dropin.sh, which pins the driver) and leaves the MySQL setup untouched.
# The copy is set up again when the driver or the source test WordPress changes.
#
#   bin/phpunit-sqlite.sh                    # all tests
#   bin/phpunit-sqlite.sh --filter Exception # arguments go to phpunit (default: tests)
#
# Env: OSEC_SQLITE_DIR (default /tmp/osec-sqlite-tests),
#      WP_CORE_DIR / WP_TESTS_SOURCE_DIR (the MySQL setup to copy).

set -Eeuo pipefail

cd "$(dirname "$0")/.."
DIR=${OSEC_SQLITE_DIR:-/tmp/osec-sqlite-tests}
SOURCE_WP=${WP_CORE_DIR:-/tmp/wordpress}
SOURCE_TESTS=${WP_TESTS_SOURCE_DIR:-/tmp/wordpress-tests-lib}

fail() { echo "phpunit-sqlite: $*" >&2; exit 1; }

[ -f "$SOURCE_TESTS/wp-tests-config.php" ] && [ -f "$SOURCE_WP/wp-includes/version.php" ] \
    || fail "no test WordPress at $SOURCE_WP / $SOURCE_TESTS - run bin/install-wp-tests.sh first."

stamp="driver $(bin/sqlite-dropin.sh --version), $(md5sum "$SOURCE_WP/wp-includes/version.php" "$SOURCE_TESTS/wp-tests-config.php" | cut -d' ' -f1 | tr '\n' ' ')"
if [ "$(cat "$DIR/setup.txt" 2>/dev/null)" != "$stamp" ]; then
    echo "Setting up $DIR"
    rm -rf "$DIR"
    mkdir -p "$DIR/db"
    cp -a "$SOURCE_WP" "$DIR/wordpress"
    cp -a "$SOURCE_TESTS" "$DIR/tests-lib"
    bin/sqlite-dropin.sh "$DIR/wordpress"

    sed -i "s#^define( 'ABSPATH'.*#define( 'ABSPATH', '$DIR/wordpress/' );\ndefine( 'DB_DIR', '$DIR/db/' );\ndefine( 'DB_FILE', 'phpunit.sqlite' );#" \
        "$DIR/tests-lib/wp-tests-config.php"
    grep -q "^define( 'DB_DIR', '$DIR/db/' );" "$DIR/tests-lib/wp-tests-config.php" \
        || fail "could not set ABSPATH/DB_DIR in wp-tests-config.php (format changed?)."

    echo "$stamp" > "$DIR/setup.txt"
fi

rm -f "$DIR/db/phpunit.sqlite"
status=0
WP_TESTS_DIR="$DIR/tests-lib" vendor/bin/phpunit "${@:-tests}" || status=$?
# The run must have used SQLite, not fallen back to MySQL.
[ -s "$DIR/db/phpunit.sqlite" ] || fail "no SQLite database written - did the drop-in load?"
exit $status
