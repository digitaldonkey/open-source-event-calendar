#!/bin/bash
#
# Compiles the admin CSS. Needs Node lessc 4.x: npm install -g less@4
# (lessc 4.9.1 reproduces the committed files byte for byte).
#
# - public/admin/less/admin-pages/*.less -> public/admin/css/<name>.css and <name>.css.map
# - public/admin/less/timely-bootstrap.less -> public/admin/css/bootstrap.min.css
#
# Development, the same for one file (e.g. from an IDE file watcher):
#    cd public/admin/less/admin-pages && lessc -x --source-map <name>.less ../../css/<name>.css
#
# -x is deprecated in Less 4 and prints a warning, but it is what the committed
# CSS was built with. The source map needs lessc to run in the source directory,
# so that it names the .less file relative to it.
#
# timely-bootstrap.less is Bootstrap 3, written for Less 1.x math: it needs
# --math=always (gone in Less 5, hence the version check) and prints deprecation
# warnings for mixins called without parentheses.

set -Eeuo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)
LESS_PATH=$(realpath "$SCRIPT_DIR/../public/admin/less")
SOURCE_PATH="$LESS_PATH/admin-pages"
DEST_PATH=$(realpath "$SCRIPT_DIR/../public/admin/css")

if [[ "$(lessc --version)" != lessc\ 4.* ]]; then
    echo "Needs lessc 4.x, found: $(lessc --version)" >&2
    exit 1
fi

printf 'Processing directory:\n  %s\nto\n  %s\n\n' "$SOURCE_PATH" "$DEST_PATH"
cd "$SOURCE_PATH"
for file_name in *.less; do
    file_dest="${file_name%.less}.css"
    echo "$file_name -> $file_dest"
    lessc -x --source-map "$file_name" "$DEST_PATH/$file_dest"
done

echo "timely-bootstrap.less -> bootstrap.min.css"
cd "$LESS_PATH"
lessc --math=always timely-bootstrap.less "$DEST_PATH/bootstrap.min.css"
