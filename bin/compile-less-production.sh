#!/bin/bash
#
# Compiles public/admin/less/admin-pages/*.less to public/admin/css/<name>.css
# and <name>.css.map. Needs Node lessc 4.x: npm install -g less
# (lessc 4.9.1 reproduces the committed files byte for byte).
#
# Development, the same for one file (e.g. from an IDE file watcher):
#    cd public/admin/less/admin-pages && lessc -x --source-map <name>.less ../../css/<name>.css
#
# -x is deprecated in Less 4 and prints a warning, but it is what the committed
# CSS was built with. The source map needs lessc to run in the source directory,
# so that it names the .less file relative to it.
#
# public/admin/css/bootstrap.min.css (public/admin/less/build-css.sh) cannot be
# rebuilt with Less 4: the yui-compress plugin is gone and Bootstrap 3's LESS
# fails in floor(). Treat it as a frozen vendor file.

set -Eeuo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)
SOURCE_PATH=$(realpath "$SCRIPT_DIR/../public/admin/less/admin-pages")
DEST_PATH=$(realpath "$SCRIPT_DIR/../public/admin/css")

printf 'Processing directory:\n  %s\nto\n  %s\n\n' "$SOURCE_PATH" "$DEST_PATH"
cd "$SOURCE_PATH"
for file_name in *.less; do
    file_dest="${file_name%.less}.css"
    echo "$file_name -> $file_dest"
    lessc -x --source-map "$file_name" "$DEST_PATH/$file_dest"
done
